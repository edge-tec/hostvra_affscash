<?php
/**
 * Admin endpoint that disables Google Authenticator for any user.
 *
 * Posted to as a CSRF-protected form from the admin "edit user" pages
 * (affiliate / manager / admin). Clears the secret, drops the enabled flag and
 * the enabled_at timestamp, then redirects back to where the admin came from.
 *
 *   POST /admin/users/2fa-reset
 *     user_id        : int    target user
 *     redirect_back  : string (optional) same-host URL to return to
 */
Auth::check('admin');

if (!Helpers::isPost() || !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    Helpers::flash('error', 'Invalid form submission.');
    Helpers::redirect('/admin/affiliates');
}

// Idempotent migration — safe to run before the UPDATE in case the user installs
// the feature on a fresh DB and immediately resets a user.
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled`    TINYINT(1)   NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_secret`     VARCHAR(255) DEFAULT NULL"); }       catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled_at` DATETIME     DEFAULT NULL"); }       catch (\Throwable $_e) {}

$targetId = (int)Helpers::post('user_id');
if ($targetId <= 0) {
    Helpers::flash('error', 'Invalid user.');
    Helpers::redirect('/admin/affiliates');
}

try {
    $row = Database::fetchOne("SELECT id, email FROM users WHERE id=?", [$targetId]);
    if (!$row) {
        Helpers::flash('error', 'User not found.');
    } else {
        Database::update('users', [
            'google2fa_enabled'    => 0,
            'google2fa_secret'     => null,
            'google2fa_enabled_at' => null,
        ], 'id=?', [$targetId]);
        Helpers::flash('success', 'Google Authenticator has been reset for ' . $row['email'] . '. The user will need to re-enable it from their account.');
    }
} catch (\Throwable $e) {
    Helpers::flash('error', 'Reset failed: ' . $e->getMessage());
}

$back = Helpers::postRaw('redirect_back') ?: '/admin/affiliates';
if (!is_string($back) || !str_starts_with($back, '/')) $back = '/admin/affiliates';
Helpers::redirect($back);
