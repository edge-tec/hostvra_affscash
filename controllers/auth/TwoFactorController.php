<?php
$pageTitle = 'Two-Step Verification';
$error = '';

// Must have a pending 2FA session
if (empty($_SESSION['2fa_pending_user_id'])) {
    Helpers::redirect('/login');
}

// Expired?
if (time() > ($_SESSION['2fa_expires'] ?? 0)) {
    unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],
          $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],
          $_SESSION['2fa_code'], $_SESSION['2fa_expires'], $_SESSION['2fa_pending_remember']);
    Helpers::flash('error', 'Verification code expired. Please sign in again.');
    Helpers::redirect('/login');
}

// Surface the active method to the view ('totp' for Google Authenticator,
// 'email' for the legacy email OTP). Defaults to 'email' for back-compat.
$twoFaMethod = $_SESSION['2fa_method'] ?? 'email';

if (Helpers::isPost()) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $error = 'Invalid form submission.';
    } else {
        // Per-user OTP brute-force guard: 6 wrong codes ⇒ kill the pending session.
        $_SESSION['2fa_attempts'] = (int)($_SESSION['2fa_attempts'] ?? 0);
        if ($_SESSION['2fa_attempts'] >= 6) {
            unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],
                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],
                  $_SESSION['2fa_code'], $_SESSION['2fa_method'],
                  $_SESSION['2fa_expires'], $_SESSION['2fa_attempts'], $_SESSION['2fa_pending_remember']);
            Helpers::flash('error', 'Too many invalid codes. Please sign in again.');
            Helpers::redirect('/login');
        }

        $entered = trim(Helpers::postRaw('otp_code'));

        // Verify against either method based on the pending session.
        $valid = false;
        if ($twoFaMethod === 'totp') {
            try {
                $row = Database::fetchOne("SELECT google2fa_secret FROM users WHERE id=?", [(int)$_SESSION['2fa_pending_user_id']]);
                $secret = $row ? Totp::decrypt((string)$row['google2fa_secret']) : '';
                if ($secret !== '') $valid = Totp::verify($secret, $entered);
            } catch (\Throwable $_e) {}
        } else {
            $valid = password_verify($entered, $_SESSION['2fa_code'] ?? '');
        }

        if ($valid) {
            // OTP correct — complete login
            $userId = (int)$_SESSION['2fa_pending_user_id'];
            $role   = $_SESSION['2fa_pending_role'];

            // Clear 2FA pending state
            if (!empty($_SESSION['2fa_pending_remember'])) {
                Auth::setRememberCookie($userId);
            }
            unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_pending_role'],
                  $_SESSION['2fa_pending_email'], $_SESSION['2fa_pending_name'],
                  $_SESSION['2fa_code'], $_SESSION['2fa_expires'],
                  $_SESSION['2fa_method'], $_SESSION['2fa_attempts'], $_SESSION['2fa_pending_remember']);

            session_regenerate_id(true);
            $_SESSION['login_attempts'] = 0;
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_role'] = $role;

            $user = Database::fetchOne("SELECT * FROM `users` WHERE `id`=?", [$userId]);
            if (!$user) { Helpers::redirect('/login'); }

            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name']  = trim($user['first_name'] . ' ' . $user['last_name']);

            if ($role === 'affiliate') {
                $aff = Database::fetchOne("SELECT id FROM affiliates WHERE user_id=?", [$userId]);
                $_SESSION['affiliate_id'] = $aff['id'] ?? null;
            } elseif ($role === 'advertiser') {
                $adv = Database::fetchOne("SELECT id FROM advertisers WHERE user_id=?", [$userId]);
                $_SESSION['advertiser_id'] = $adv['id'] ?? null;
            } elseif ($role === 'affiliate_manager') {
                // no extra id needed
            }

            Database::query("UPDATE users SET last_login=NOW() WHERE id=?", [$userId]);
            try { Activity::logLogin($userId, $role, session_id(), $_SESSION['user_name']); } catch(\Exception $_e) {}

            Helpers::redirect("/$role/dashboard");
        } else {
            $_SESSION['2fa_attempts']++;
            $error = 'Invalid authentication code.';
        }
    }
}

require BASE_PATH . '/views/auth/two_factor.php';
