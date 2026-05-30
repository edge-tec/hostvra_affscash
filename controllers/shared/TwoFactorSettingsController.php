<?php
/**
 * Shared "Account Settings → Security → Google Authenticator" page.
 *
 * Used by admin, affiliate and affiliate_manager roles. The route layer guards
 * access by role; this controller assumes the user is already authenticated
 * (the route prefix /<role>/2fa runs Auth::check('<role>') first).
 *
 * Flows:
 *   - View status (Enabled / Disabled).
 *   - "Enable" → generate secret + QR + setup instructions, ask the user to enter
 *     the 6-digit code their authenticator app shows. On success: persist the
 *     encrypted secret and flip google2fa_enabled=1.
 *   - "Disable" → require either current account password OR a current OTP code
 *     from the still-paired authenticator before clearing the secret.
 *   - "Reconnect" / "Regenerate QR" → start the Enable flow again, replacing the
 *     old secret only after the new one is verified.
 */

if (empty($_SESSION['user_id'])) { Helpers::redirect('/login'); }

$pageTitle = 'Two-Factor Authentication';
$userId    = (int)Auth::id();
$role      = Auth::role();

// Idempotent migration — safe on every request, no-ops once the columns exist.
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled`    TINYINT(1)   NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_secret`     VARCHAR(255) DEFAULT NULL"); }       catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled_at` DATETIME     DEFAULT NULL"); }       catch (\Throwable $_e) {}

// Page-relative URL used for form actions / redirects (matches the parent route).
$g2faBase = '/' . $role . '/2fa';

// ── Action handler ──────────────────────────────────────────────────────────
$flashErr = '';
$flashOk  = '';

if (Helpers::isPost()) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Helpers::flash('error', 'Invalid form submission.');
        Helpers::redirect($g2faBase);
    }
    $action = Helpers::post('action');

    // Brute-force guard — same shape as the login-page guard.
    $_SESSION['g2fa_attempts'] = (int)($_SESSION['g2fa_attempts'] ?? 0);
    if ($_SESSION['g2fa_attempts'] >= 8) {
        Helpers::flash('error', 'Too many invalid attempts. Please try again in a few minutes.');
        Helpers::redirect($g2faBase);
    }

    if ($action === 'start') {
        // Generate a fresh pending secret + show the QR. Saved only after the
        // user successfully enters a code — never written to the users table here.
        $_SESSION['g2fa_pending_secret']  = Totp::generateSecret(16);
        $_SESSION['g2fa_pending_started'] = time();
        Helpers::redirect($g2faBase . '?step=verify');
    }

    if ($action === 'verify') {
        $code   = trim((string)Helpers::postRaw('otp_code'));
        $secret = (string)($_SESSION['g2fa_pending_secret'] ?? '');
        if ($secret === '') {
            Helpers::flash('error', 'Setup session expired — please start again.');
            Helpers::redirect($g2faBase);
        }
        if (Totp::verify($secret, $code)) {
            Database::update('users', [
                'google2fa_enabled'    => 1,
                'google2fa_secret'     => Totp::encrypt($secret),
                'google2fa_enabled_at' => date('Y-m-d H:i:s'),
            ], 'id=?', [$userId]);
            unset($_SESSION['g2fa_pending_secret'], $_SESSION['g2fa_pending_started']);
            $_SESSION['g2fa_attempts'] = 0;
            Helpers::flash('success', '✓ Google Authenticator is now active for your account.');
        } else {
            $_SESSION['g2fa_attempts']++;
            Helpers::flash('error', 'Invalid authentication code. Please try again.');
            Helpers::redirect($g2faBase . '?step=verify');
        }
        Helpers::redirect($g2faBase);
    }

    if ($action === 'disable') {
        // Require either the account password OR a still-valid TOTP code from
        // the currently-paired authenticator. Either is sufficient.
        $row = Database::fetchOne("SELECT password_hash, google2fa_secret FROM users WHERE id=?", [$userId]);
        $pwd     = (string)Helpers::postRaw('current_password');
        $code    = trim((string)Helpers::postRaw('otp_code'));
        $secret  = $row ? Totp::decrypt((string)$row['google2fa_secret']) : '';

        $okPwd  = ($pwd  !== '' && password_verify($pwd, $row['password_hash'] ?? ''));
        $okCode = ($code !== '' && $secret !== '' && Totp::verify($secret, $code));

        if (!$okPwd && !$okCode) {
            $_SESSION['g2fa_attempts']++;
            Helpers::flash('error', 'Verification failed. Enter your account password or a valid 6-digit code.');
            Helpers::redirect($g2faBase);
        }

        Database::update('users', [
            'google2fa_enabled'    => 0,
            'google2fa_secret'     => null,
            'google2fa_enabled_at' => null,
        ], 'id=?', [$userId]);
        unset($_SESSION['g2fa_pending_secret'], $_SESSION['g2fa_pending_started']);
        $_SESSION['g2fa_attempts'] = 0;
        Helpers::flash('success', 'Google Authenticator has been disabled for your account.');
        Helpers::redirect($g2faBase);
    }

    Helpers::redirect($g2faBase);
}

// ── Page state for the view ─────────────────────────────────────────────────
$user = Database::fetchOne("SELECT email, google2fa_enabled, google2fa_enabled_at FROM users WHERE id=?", [$userId]);
$enabled       = !empty($user['google2fa_enabled']);
$enabledAt     = $user['google2fa_enabled_at'] ?? null;
$accountEmail  = (string)($user['email'] ?? '');
$siteName      = (string)(Config::get('config', 'app.name') ?? 'AffiliateTracker');
$step          = Helpers::get('step') === 'verify' && !empty($_SESSION['g2fa_pending_secret']) ? 'verify' : ($enabled ? 'manage' : 'intro');
$pendingSecret = (string)($_SESSION['g2fa_pending_secret'] ?? '');
$otpauthUri    = $pendingSecret !== '' ? Totp::otpauthUri($siteName, $accountEmail, $pendingSecret) : '';
$qrUrl         = $pendingSecret !== '' ? Totp::qrCodeUrl($otpauthUri) : '';

require BASE_PATH . '/views/shared/two_factor_settings.php';
