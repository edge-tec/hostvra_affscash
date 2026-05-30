<?php
/**
 * Activity — Login logging, live session tracking, UA parsing, geo lookup
 */
class Activity {

    private static bool $tablesReady = false;

    // ── Schema ────────────────────────────────────────────────────────────────
    public static function ensureTables(): void {
        if (self::$tablesReady) return;
        try {
            Database::query("CREATE TABLE IF NOT EXISTS user_login_logs (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                user_id       INT NOT NULL,
                session_id    VARCHAR(128) DEFAULT NULL,
                role          VARCHAR(30) NOT NULL DEFAULT 'affiliate',
                ip_address    VARCHAR(45) NOT NULL,
                country       VARCHAR(100) DEFAULT NULL,
                country_code  VARCHAR(5)   DEFAULT NULL,
                city          VARCHAR(100) DEFAULT NULL,
                device_type   VARCHAR(20)  DEFAULT NULL,
                browser       VARCHAR(60)  DEFAULT NULL,
                os            VARCHAR(60)  DEFAULT NULL,
                user_agent    TEXT         DEFAULT NULL,
                login_time    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                logout_time   DATETIME     DEFAULT NULL,
                duration_sec  INT          DEFAULT NULL,
                is_active     TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_ull_user   (user_id),
                INDEX idx_ull_time   (login_time),
                INDEX idx_ull_active (is_active),
                INDEX idx_ull_ip     (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS user_active_sessions (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                session_id    VARCHAR(128) UNIQUE NOT NULL,
                user_id       INT NOT NULL,
                role          VARCHAR(30) NOT NULL DEFAULT 'affiliate',
                user_name     VARCHAR(120) DEFAULT NULL,
                ip_address    VARCHAR(45)  DEFAULT NULL,
                country       VARCHAR(100) DEFAULT NULL,
                country_code  VARCHAR(5)   DEFAULT NULL,
                city          VARCHAR(100) DEFAULT NULL,
                device_type   VARCHAR(20)  DEFAULT NULL,
                browser       VARCHAR(60)  DEFAULT NULL,
                os            VARCHAR(60)  DEFAULT NULL,
                current_page  VARCHAR(300) DEFAULT NULL,
                logged_in_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_active   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                login_log_id  INT DEFAULT NULL,
                INDEX idx_uas_user   (user_id),
                INDEX idx_uas_active (last_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS ip_geo_cache (
                ip_address   VARCHAR(45) PRIMARY KEY,
                country      VARCHAR(100) DEFAULT NULL,
                country_code VARCHAR(5)   DEFAULT NULL,
                city         VARCHAR(100) DEFAULT NULL,
                cached_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        self::$tablesReady = true;
    }

    // ── Real client IP ─────────────────────────────────────────────────────────
    public static function clientIp(): string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ── User-Agent parser ──────────────────────────────────────────────────────
    public static function parseUA(string $ua): array {
        // Device
        if (preg_match('/ipad|tablet|(android(?!.*mobile))/i', $ua)) {
            $device = 'Tablet';
        } elseif (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $ua)) {
            $device = 'Mobile';
        } else {
            $device = 'Desktop';
        }

        // Browser (order matters — check specific before generic)
        if      (preg_match('/Edg\//i', $ua))             $browser = 'Edge';
        elseif  (preg_match('/OPR\/|Opera\//i', $ua))     $browser = 'Opera';
        elseif  (preg_match('/SamsungBrowser/i', $ua))    $browser = 'Samsung';
        elseif  (preg_match('/UCBrowser/i', $ua))         $browser = 'UC Browser';
        elseif  (preg_match('/Chrome\/[\d]/i', $ua))      $browser = 'Chrome';
        elseif  (preg_match('/Firefox\/[\d]/i', $ua))     $browser = 'Firefox';
        elseif  (preg_match('/Safari\/[\d]/i', $ua))      $browser = 'Safari';
        elseif  (preg_match('/MSIE|Trident/i', $ua))      $browser = 'IE';
        else                                               $browser = 'Other';

        // OS
        if      (preg_match('/Windows NT 10/i', $ua))         $os = 'Windows 10/11';
        elseif  (preg_match('/Windows NT 6\.3/i', $ua))       $os = 'Windows 8.1';
        elseif  (preg_match('/Windows NT 6\.2/i', $ua))       $os = 'Windows 8';
        elseif  (preg_match('/Windows NT 6\.1/i', $ua))       $os = 'Windows 7';
        elseif  (preg_match('/Windows/i', $ua))               $os = 'Windows';
        elseif  (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) $os = 'macOS ' . str_replace('_','.',$m[1]);
        elseif  (preg_match('/Android ([\d.]+)/i', $ua, $m))  $os = 'Android '.$m[1];
        elseif  (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))$os = 'iOS '.str_replace('_','.',$m[1]);
        elseif  (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m)) $os = 'iPadOS '.str_replace('_','.',$m[1]);
        elseif  (preg_match('/Linux/i', $ua))                 $os = 'Linux';
        else                                                   $os = 'Other';

        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    // ── Geo lookup with DB cache ───────────────────────────────────────────────
    public static function geoLookup(string $ip): array {
        $local = ['127.0.0.1','::1','0.0.0.0'];
        if (in_array($ip, $local) || str_starts_with($ip,'192.168.') || str_starts_with($ip,'10.')) {
            return ['country'=>'Local','country_code'=>'LO','city'=>'Localhost'];
        }

        // Check DB cache (1 week TTL)
        try {
            self::ensureTables();
            $cached = Database::fetchOne(
                "SELECT country,country_code,city FROM ip_geo_cache WHERE ip_address=? AND cached_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                [$ip]
            );
            if ($cached) return $cached;
        } catch (Exception $e) {}

        // Fetch from ip-api.com
        $result = ['country'=>'Unknown','country_code'=>'','city'=>'Unknown'];
        try {
            $ctx = stream_context_create(['http'=>['timeout'=>3,'ignore_errors'=>true]]);
            $raw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city", false, $ctx);
            if ($raw) {
                $data = json_decode($raw, true);
                if (!empty($data['status']) && $data['status'] === 'success') {
                    $result = [
                        'country'      => $data['country']     ?? 'Unknown',
                        'country_code' => $data['countryCode'] ?? '',
                        'city'         => $data['city']        ?? 'Unknown',
                    ];
                }
            }
        } catch (Exception $e) {}

        // Cache result
        try {
            Database::query(
                "INSERT INTO ip_geo_cache (ip_address,country,country_code,city,cached_at) VALUES (?,?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE country=VALUES(country),country_code=VALUES(country_code),city=VALUES(city),cached_at=NOW()",
                [$ip, $result['country'], $result['country_code'], $result['city']]
            );
        } catch (Exception $e) {}

        return $result;
    }

    // ── Log login ──────────────────────────────────────────────────────────────
    public static function logLogin(int $userId, string $role, string $sessionId, string $userName = ''): int {
        self::ensureTables();
        $ip  = self::clientIp();
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $parsed = self::parseUA($ua);
        $geo    = self::geoLookup($ip);

        // Mark any previous active sessions for this user as inactive
        try {
            Database::query(
                "UPDATE user_login_logs SET is_active=0, logout_time=NOW(),
                 duration_sec=TIMESTAMPDIFF(SECOND,login_time,NOW())
                 WHERE user_id=? AND is_active=1", [$userId]
            );
            Database::query(
                "DELETE FROM user_active_sessions WHERE user_id=?", [$userId]
            );
        } catch (Exception $e) {}

        // Insert login log
        $logId = 0;
        try {
            $logId = Database::insert('user_login_logs', [
                'user_id'      => $userId,
                'session_id'   => $sessionId,
                'role'         => $role,
                'ip_address'   => $ip,
                'country'      => $geo['country'],
                'country_code' => $geo['country_code'],
                'city'         => $geo['city'],
                'device_type'  => $parsed['device'],
                'browser'      => $parsed['browser'],
                'os'           => $parsed['os'],
                'user_agent'   => mb_substr($ua, 0, 500),
                'is_active'    => 1,
            ]);
        } catch (Exception $e) {}

        // Insert active session
        try {
            Database::query(
                "INSERT INTO user_active_sessions
                 (session_id,user_id,role,user_name,ip_address,country,country_code,city,device_type,browser,os,current_page,logged_in_at,last_active,login_log_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)
                 ON DUPLICATE KEY UPDATE last_active=NOW(), user_name=VALUES(user_name)",
                [
                    $sessionId, $userId, $role, $userName, $ip,
                    $geo['country'], $geo['country_code'], $geo['city'],
                    $parsed['device'], $parsed['browser'], $parsed['os'],
                    $_SERVER['REQUEST_URI'] ?? '/', $logId
                ]
            );
        } catch (Exception $e) {}

        return $logId;
    }

    // ── Log logout ─────────────────────────────────────────────────────────────
    public static function logLogout(int $userId, string $sessionId): void {
        try {
            self::ensureTables();
            Database::query(
                "UPDATE user_login_logs SET is_active=0, logout_time=NOW(),
                 duration_sec=TIMESTAMPDIFF(SECOND,login_time,NOW())
                 WHERE session_id=? AND user_id=? AND is_active=1",
                [$sessionId, $userId]
            );
            Database::query("DELETE FROM user_active_sessions WHERE session_id=?", [$sessionId]);
        } catch (Exception $e) {}
    }

    // ── Heartbeat (called every 30s from JS) ───────────────────────────────────
    public static function heartbeat(int $userId, string $sessionId, string $page): bool {
        try {
            self::ensureTables();
            Database::query(
                "UPDATE user_active_sessions SET last_active=NOW(), current_page=? WHERE session_id=? AND user_id=?",
                [mb_substr($page, 0, 300), $sessionId, $userId]
            );
            // If session row missing (e.g. after Force Logout or 10 min idle),
            // do not re-insert it so the session can be killed.
            if (!Database::fetchOne("SELECT id FROM user_active_sessions WHERE session_id=?", [$sessionId])) {
                return false;
            }
            return true;
        } catch (Exception $e) { return true; }
    }

    // ── Clean expired sessions (> 10 min inactive) ────────────────────────────
    public static function cleanExpired(int $minutes = 10): void {
        try {
            // Get sessions that have expired
            $expired = Database::fetchAll(
                "SELECT session_id, user_id FROM user_active_sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$minutes]
            );
            foreach ($expired as $s) {
                Database::query(
                    "UPDATE user_login_logs SET is_active=0, logout_time=last_active,
                     duration_sec=TIMESTAMPDIFF(SECOND,login_time,last_active)
                     WHERE session_id=? AND user_id=? AND is_active=1",
                    [$s['session_id'], $s['user_id']]
                );
            }
            Database::query(
                "DELETE FROM user_active_sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$minutes]
            );
        } catch (Exception $e) {}
    }

    // ── Online user count ─────────────────────────────────────────────────────
    public static function onlineCount(): int {
        try {
            self::ensureTables();
            self::cleanExpired();
            $r = Database::fetchOne("SELECT COUNT(*) as cnt FROM user_active_sessions");
            return (int)($r['cnt'] ?? 0);
        } catch (Exception $e) { return 0; }
    }

    /**
     * Log general admin or system activity to central audit log table
     */
    public static function log(string $action, string $entityType, ?int $entityId, string $note = '', ?array $oldData = null, ?array $newData = null): void {
        try {
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $tenantId = Tenant::getTenantId();
            Database::insert('activity_log', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_data' => $oldData ? json_encode($oldData) : null,
                'new_data' => $newData ? json_encode($newData) : ($note ? json_encode(['note' => $note]) : null),
                'ip_address' => self::clientIp(),
                'tenant_id' => $tenantId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // fail-safe
        }
    }
}

