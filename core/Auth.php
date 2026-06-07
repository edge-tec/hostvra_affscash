<?php
/**
 * Auth - Session-based authentication
 */
class Auth {
    private static ?array $currentUser = null;
    private static ?array $managerPerms = null;
    private static bool $banTableReady = false;

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $cfg = Config::get('config', 'session');
            session_set_cookie_params([
                'lifetime' => $cfg['lifetime'] ?? 7200,
                'secure'   => $cfg['secure'] ?? false,
                'httponly' => true,
                'samesite' => $cfg['same_site'] ?? 'Lax',
            ]);
            session_name('AFFILIATETRACKSID');
            session_start();
        }

        if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
            self::autoLoginFromCookie($_COOKIE['remember_token']);
        }
    }

    public static function login(string $email, string $password, bool $remember = false): array {
        $clientIp = Helpers::getIp();

        // Database-backed IP rate limiting to prevent session bypass brute-force
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `login_failures` (
                `ip_address` VARCHAR(45) PRIMARY KEY,
                `attempts` INT NOT NULL DEFAULT 0,
                `last_attempt` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}

        try {
            $failRec = Database::fetchOne("SELECT `attempts`, `last_attempt` FROM `login_failures` WHERE `ip_address` = ?", [$clientIp]);
            if ($failRec && $failRec['attempts'] >= 5) {
                if (strtotime($failRec['last_attempt']) > time() - 900) {
                    return ['success' => false, 'error' => 'Too many failed attempts from your IP. Try again in 15 minutes.'];
                } else {
                    Database::query("UPDATE `login_failures` SET `attempts` = 0 WHERE `ip_address` = ?", [$clientIp]);
                }
            }
        } catch (\Throwable $e) {}

        $user = Database::fetchOne("SELECT * FROM `users` WHERE `email` = ?", [strtolower(trim($email))]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            try {
                Database::query(
                    "INSERT INTO `login_failures` (`ip_address`, `attempts`, `last_attempt`) VALUES (?, 1, NOW())
                     ON DUPLICATE KEY UPDATE `attempts` = `attempts` + 1, `last_attempt` = NOW()",
                    [$clientIp]
                );
            } catch (\Throwable $e) {}
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }
        
        // Reset failures on success
        try { Database::query("DELETE FROM `login_failures` WHERE `ip_address` = ?", [$clientIp]); } catch (\Throwable $e) {}
        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is ' . $user['status'] . '. Please contact support.'];
        }

        // --- REALTIME INACTIVITY CHECK ---
        if ($user['role'] === 'affiliate' && !empty($user['last_login'])) {
            $inactivityEnabled = (Config::get('config', 'app.inactivity_enabled') ?? '0') === '1';
            if ($inactivityEnabled) {
                $inactivityDays = (int)(Config::get('config', 'app.inactivity_days') ?? 30);
                if ($inactivityDays > 0) {
                    $daysInactive = (time() - strtotime($user['last_login'])) / 86400;
                    if ($daysInactive >= $inactivityDays) {
                        Database::query("UPDATE `users` SET `status`='suspended', `inactivity_deactivated_at`=NOW() WHERE `id`=?", [$user['id']]);
                        return ['success' => false, 'error' => 'Your account has been suspended due to ' . $inactivityDays . ' days of inactivity. Please contact support.'];
                    }
                }
            }
        }

        // ── Login IP Ban check ────────────────────────────────────────────────
        // Enforce for all roles except affiliate_manager. Admin is blocked too.
        if ($user['role'] !== 'affiliate_manager') {
            $clientIp = Helpers::getIp();
            if (self::isIpBanned($clientIp)) {
                return ['success' => false, 'error' => 'Your IP address has been blocked by the administrator. Please contact support.'];
            }
        }

        // ── Fraud Blocklist check ─────────────────────────────────────────────
        // Admins are intentionally exempted to avoid self-lockout via the UI.
        if ($user['role'] !== 'admin') {
            $clientIp = Helpers::getIp();
            $affCode  = null;
            $affId    = 0;
            if ($user['role'] === 'affiliate') {
                $a = Database::fetchOne("SELECT id, affiliate_code FROM `affiliates` WHERE user_id=?", [$user['id']]);
                $affId   = (int)($a['id'] ?? 0);
                $affCode = $a['affiliate_code'] ?? null;
            }
            $blEntry = Blocklist::match([
                'ip'             => $clientIp,
                'ua'             => Helpers::getUserAgent(),
                'affiliate_id'   => $affId,
                'affiliate_code' => $affCode,
            ], 'all');
            if (Blocklist::isBlocking($blEntry)) {
                Blocklist::recordHit((int)$blEntry['id']);
                return ['success' => false, 'error' => 'Access denied. Your account or IP is on the platform blocklist.'];
            }
        }

        // Email verification check
        if (Config::get('config', 'app.email_verification') === '1' && empty($user['email_verified_at'])) {
            return ['success' => false, 'error' => 'Your email address has not been verified yet. Please check your inbox for the verification link.'];
        }

        // ── Per-user Google Authenticator (TOTP) check ────────────────────
        // Idempotent: tries to add the columns if they don't exist yet, then reads them.
        try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled`    TINYINT(1)   NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}
        try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_secret`     VARCHAR(255) DEFAULT NULL"); }       catch (\Throwable $_e) {}
        try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled_at` DATETIME     DEFAULT NULL"); }       catch (\Throwable $_e) {}

        if (!empty($user['google2fa_enabled']) && !empty($user['google2fa_secret'])) {
            // User has personally configured Google Authenticator — go straight
            // to the OTP page (no email is sent). Same pending-session keys the
            // existing email-OTP flow uses, plus a method marker.
            $_SESSION['2fa_pending_user_id'] = $user['id'];
            $_SESSION['2fa_pending_role']    = $user['role'];
            $_SESSION['2fa_pending_email']   = $user['email'];
            $_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['2fa_pending_remember'] = $remember;
            $_SESSION['2fa_method']          = 'totp';
            $_SESSION['2fa_expires']         = time() + 600;
            $_SESSION['2fa_attempts']        = 0;
            $_SESSION['login_attempts']      = 0;
            return ['success' => true, '2fa_required' => true];
        }

        // Two-Factor Authentication check (legacy email OTP — global setting)
        if (Config::get('config', 'app.2fa_enabled') === '1') {
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['2fa_pending_user_id'] = $user['id'];
            $_SESSION['2fa_pending_role']    = $user['role'];
            $_SESSION['2fa_pending_email']   = $user['email'];
            $_SESSION['2fa_pending_name']    = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['2fa_pending_remember'] = $remember;
            $_SESSION['2fa_method']          = 'email';
            $_SESSION['2fa_code']            = password_hash($otp, PASSWORD_BCRYPT);
            $_SESSION['2fa_expires']         = time() + 600;
            $_SESSION['login_attempts']      = 0;
            $siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
            $toName   = trim($user['first_name'] . ' ' . $user['last_name']);
            $body = '<div style="font-family:sans-serif;max-width:480px;margin:0 auto">
                <h2 style="color:#4F46E5">Two-Step Verification</h2>
                <p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                <p>Use the code below to complete your sign-in to <strong>' . htmlspecialchars($siteName) . '</strong>:</p>
                <div style="background:#EEF2FF;border-radius:12px;padding:24px;text-align:center;margin:20px 0">
                    <span style="font-size:42px;font-weight:900;letter-spacing:12px;color:#4F46E5">' . $otp . '</span>
                </div>
                <p style="color:#64748B;font-size:13px">This code expires in <strong>10 minutes</strong>. Never share it with anyone.</p>
            </div>';
            try { Mailer::sendRaw($user['email'], $toName, "[$siteName] Your verification code: $otp", $body, '2fa_otp'); } catch(\Exception $_e) {}
            return ['success' => true, '2fa_required' => true];
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['login_attempts'] = 0;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

        // Load role-specific ID
        if ($user['role'] === 'affiliate') {
            $aff = Database::fetchOne("SELECT `id` FROM `affiliates` WHERE `user_id` = ?", [$user['id']]);
            $_SESSION['affiliate_id'] = $aff['id'] ?? null;
        } elseif ($user['role'] === 'advertiser') {
            $adv = Database::fetchOne("SELECT `id` FROM `advertisers` WHERE `user_id` = ?", [$user['id']]);
            $_SESSION['advertiser_id'] = $adv['id'] ?? null;
        }

        Database::query("UPDATE `users` SET `last_login`=NOW() WHERE `id`=?", [$user['id']]);
        self::$currentUser = $user;

        // Log login activity
        try {
            $userName = trim(($user['first_name']??'').' '.($user['last_name']??''));
            Activity::logLogin($user['id'], $user['role'], session_id(), $userName);
        } catch (Exception $e) {}

        if ($remember) {
            self::setRememberCookie($user['id']);
        }

        return ['success' => true, 'role' => $user['role']];
    }

    public static function check(?string $requiredRole = null): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Strict verification against active sessions (handles Force Logout)
        if (session_id()) {
            try {
                $active = Database::fetchOne("SELECT id FROM user_active_sessions WHERE session_id=?", [session_id()]);
                if (!$active) {
                    self::logout();
                } else {
                    // Update last_active on normal page navigation to prevent unexpected logouts
                    // if JS heartbeat fails or is blocked by adblockers.
                    Database::query("UPDATE user_active_sessions SET last_active=NOW(), current_page=? WHERE session_id=?", [
                        mb_substr($_SERVER['REQUEST_URI'] ?? '/', 0, 300), 
                        session_id()
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        if ($requiredRole !== null && $_SESSION['user_role'] !== $requiredRole) {
            // Allow admin to access everything
            if ($_SESSION['user_role'] !== 'admin') {
                header('Location: /' . $_SESSION['user_role'] . '/dashboard');
                exit;
            }
        }
    }

    public static function checkAny(array $roles): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Strict verification against active sessions (handles Force Logout)
        if (session_id()) {
            try {
                $active = Database::fetchOne("SELECT id FROM user_active_sessions WHERE session_id=?", [session_id()]);
                if (!$active) {
                    self::logout();
                } else {
                    // Update last_active on normal page navigation to prevent unexpected logouts
                    Database::query("UPDATE user_active_sessions SET last_active=NOW(), current_page=? WHERE session_id=?", [
                        mb_substr($_SERVER['REQUEST_URI'] ?? '/', 0, 300), 
                        session_id()
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        if (!in_array($_SESSION['user_role'], $roles)) {
            header('Location: /' . $_SESSION['user_role'] . '/dashboard');
            exit;
        }
    }

    public static function logout(): void {
        // Log logout before session is destroyed
        try {
            if (!empty($_SESSION['user_id'])) {
                Activity::logLogout((int)$_SESSION['user_id'], session_id());
                try { Database::query("UPDATE `users` SET `remember_token`=NULL WHERE `id`=?", [$_SESSION['user_id']]); } catch (\Exception $e) {}
            }
            setcookie('remember_token', '', time() - 3600, '/');
        } catch (Exception $e) {}
        session_destroy();
        header('Location: /login');
        exit;
    }

    public static function currentUser(): ?array {
        if (self::$currentUser !== null) return self::$currentUser;
        if (empty($_SESSION['user_id'])) return null;
        self::$currentUser = Database::fetchOne("SELECT * FROM `users` WHERE `id`=?", [$_SESSION['user_id']]);
        return self::$currentUser;
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public static function affiliateId(): ?int {
        return $_SESSION['affiliate_id'] ?? null;
    }

    public static function advertiserId(): ?int {
        return $_SESSION['advertiser_id'] ?? null;
    }

    public static function isAdmin(): bool {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function impersonate(int $userId): bool {
        $user = Database::fetchOne("SELECT * FROM `users` WHERE `id`=?", [$userId]);
        if (!$user) return false;

        // Save original admin session
        $_SESSION['impersonating']       = true;
        $_SESSION['admin_user_id']       = $_SESSION['user_id'];
        $_SESSION['admin_user_name']     = $_SESSION['user_name'];
        $_SESSION['admin_user_role']     = $_SESSION['user_role'];

        // Switch to target user
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = trim($user['first_name'] . ' ' . $user['last_name']);

        unset($_SESSION['affiliate_id'], $_SESSION['advertiser_id']);

        if ($user['role'] === 'affiliate') {
            $aff = Database::fetchOne("SELECT `id` FROM `affiliates` WHERE `user_id`=?", [$user['id']]);
            $_SESSION['affiliate_id'] = $aff['id'] ?? null;
        } elseif ($user['role'] === 'advertiser') {
            $adv = Database::fetchOne("SELECT `id` FROM `advertisers` WHERE `user_id`=?", [$user['id']]);
            $_SESSION['advertiser_id'] = $adv['id'] ?? null;
        }

        self::$currentUser = null;
        return true;
    }

    public static function stopImpersonating(): void {
        if (empty($_SESSION['impersonating'])) return;

        $_SESSION['user_id']   = $_SESSION['admin_user_id'];
        $_SESSION['user_role'] = $_SESSION['admin_user_role'];
        $_SESSION['user_name'] = $_SESSION['admin_user_name'];

        unset($_SESSION['impersonating'], $_SESSION['admin_user_id'],
              $_SESSION['admin_user_name'], $_SESSION['admin_user_role'],
              $_SESSION['affiliate_id'], $_SESSION['advertiser_id']);

        self::$currentUser = null;
    }

    public static function isImpersonating(): bool {
        return !empty($_SESSION['impersonating']);
    }

    public static function hasPermission(string $permission): bool {
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'admin') return true;
        if ($role !== 'affiliate_manager') return false;
        if (self::$managerPerms === null) {
            $mgr = Database::fetchOne("SELECT `permissions` FROM `affiliate_managers` WHERE `user_id`=?", [self::id()]);
            self::$managerPerms = $mgr ? (json_decode($mgr['permissions'] ?? '[]', true) ?: []) : [];
        }
        return in_array('all', self::$managerPerms) || in_array($permission, self::$managerPerms);
    }

    /**
     * Check whether $ip falls inside a $cidr range.
     * Supports both IPv4 (e.g. 10.0.0.0/8) and IPv6 (e.g. 2001:db8::/32).
     */
    private static function isIpBanned(string $ip): bool {
        if ($ip === '' || $ip === '0.0.0.0' || $ip === '::1' || $ip === '127.0.0.1') return false;

        // Ensure the ban table and is_active column exist (once per process).
        if (!self::$banTableReady) {
            self::$banTableReady = true;
            try {
                Database::query("CREATE TABLE IF NOT EXISTS `login_ip_bans` (
                    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `ip_address` VARCHAR(50) NOT NULL,
                    `reason`     VARCHAR(255) DEFAULT NULL,
                    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
                    `created_by` INT UNSIGNED DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uq_ip` (`ip_address`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } catch (\Exception $_e) {}
            try {
                Database::query("ALTER TABLE `login_ip_bans` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1");
            } catch (\Exception $_e) {}
        }

        try {
            // Exact IP match (active bans only)
            if (Database::fetchOne(
                "SELECT id FROM `login_ip_bans` WHERE ip_address = ? AND is_active = 1 LIMIT 1",
                [$ip]
            )) return true;

            // CIDR range check (active bans only)
            $cidrs = Database::fetchAll(
                "SELECT ip_address FROM `login_ip_bans` WHERE ip_address LIKE '%/%' AND is_active = 1"
            );
            foreach ($cidrs as $row) {
                if (self::ipInCidr($ip, $row['ip_address'])) return true;
            }
        } catch (\Exception $_e) {
            // Table truly doesn't exist — allow login
        }

        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool {
        if (strpos($cidr, '/') === false) return false;
        [$range, $prefix] = explode('/', $cidr, 2);
        $prefix = (int)$prefix;

        $ipBin    = inet_pton($ip);
        $rangeBin = inet_pton($range);
        if ($ipBin === false || $rangeBin === false) return false;
        if (strlen($ipBin) !== strlen($rangeBin))    return false; // v4 vs v6 mismatch

        $bytes = strlen($ipBin);
        $fullBytes = (int)floor($prefix / 8);
        $remBits   = $prefix % 8;

        // Compare full bytes
        for ($i = 0; $i < $fullBytes && $i < $bytes; $i++) {
            if ($ipBin[$i] !== $rangeBin[$i]) return false;
        }
        // Compare remaining bits
        if ($remBits > 0 && $fullBytes < $bytes) {
            $mask = 0xFF & (0xFF << (8 - $remBits));
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($rangeBin[$fullBytes]) & $mask)) {
                return false;
            }
        }
        return true;
    }

    public static function managerAffiliateIds(): array {
        if (($_SESSION['user_role'] ?? '') === 'admin') return []; // admin sees all
        // affiliates.manager_id stores affiliate_managers.id (not users.id)
        $rows = Database::fetchAll(
            "SELECT af.id FROM affiliates af
             JOIN affiliate_managers am ON am.id = af.manager_id
             WHERE am.user_id = ?",
            [self::id()]
        );
        return array_column($rows, 'id');
    }


    public static function setRememberCookie(int $userId): void {
        try { Database::query("ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $_e) {}
        $token = bin2hex(random_bytes(32));
        try { Database::query("UPDATE `users` SET `remember_token`=? WHERE `id`=?", [$token, $userId]); } catch (\Throwable $e) {}
        // 30 days
        setcookie('remember_token', $token, time() + 2592000, '/', '', false, true); 
    }

    private static function autoLoginFromCookie(string $token): void {
        try { Database::query("ALTER TABLE `users` ADD COLUMN `remember_token` VARCHAR(64) DEFAULT NULL"); } catch (\Throwable $_e) {}
        try {
            $user = Database::fetchOne("SELECT * FROM `users` WHERE `remember_token`=?", [$token]);
            if ($user && $user['status'] === 'active') {
                
                // --- REALTIME INACTIVITY CHECK ---
                if ($user['role'] === 'affiliate' && !empty($user['last_login'])) {
                    $inactivityEnabled = (Config::get('config', 'app.inactivity_enabled') ?? '0') === '1';
                    if ($inactivityEnabled) {
                        $inactivityDays = (int)(Config::get('config', 'app.inactivity_days') ?? 30);
                        if ($inactivityDays > 0) {
                            $daysInactive = (time() - strtotime($user['last_login'])) / 86400;
                            if ($daysInactive >= $inactivityDays) {
                                Database::query("UPDATE `users` SET `status`='suspended', `inactivity_deactivated_at`=NOW() WHERE `id`=?", [$user['id']]);
                                setcookie('remember_token', '', time() - 3600, '/');
                                return; // Stop auto-login because they are now suspended
                            }
                        }
                    }
                }
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                
                if ($user['role'] === 'affiliate') {
                    $aff = Database::fetchOne("SELECT `id` FROM `affiliates` WHERE `user_id` = ?", [$user['id']]);
                    $_SESSION['affiliate_id'] = $aff['id'] ?? null;
                } elseif ($user['role'] === 'advertiser') {
                    $adv = Database::fetchOne("SELECT `id` FROM `advertisers` WHERE `user_id` = ?", [$user['id']]);
                    $_SESSION['advertiser_id'] = $adv['id'] ?? null;
                }
                
                Database::query("UPDATE `users` SET `last_login`=NOW() WHERE `id`=?", [$user['id']]);
                try { Activity::logLogin($user['id'], $user['role'], session_id(), $_SESSION['user_name']); } catch (\Exception $e) {}
            } else {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (\Throwable $e) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }

    public static function generateCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
