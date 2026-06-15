<?php
// Redirect if already logged in
if (Auth::id()) { Helpers::redirect('/' . Auth::role() . '/dashboard'); }

// Schema guard — ensure columns exist
try { Database::query("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME DEFAULT NULL"); } catch(Exception $e) {}

$pageTitle  = 'Reset Password';
$error      = '';
$token      = trim(Helpers::get('token') ?? '');
$user       = null;
$tokenValid = false;

// Validate the token on every request (GET and POST)
if ($token !== '') {
    $user = Database::fetchOne(
        "SELECT id, first_name, last_name, email
         FROM users
         WHERE password_reset_token = ?
           AND password_reset_expires > NOW()",
        [$token]
    );
    $tokenValid = (bool)$user;
}

if (!$tokenValid) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
}

if (Helpers::isPost() && $tokenValid) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $password = Helpers::postRaw('password')        ?? '';
        $confirm  = Helpers::postRaw('password_confirm') ?? '';

        // Enforce same strong password rules as registration
        $passErrors = RegistrationSecurity::validatePassword($password);
        if (!empty($passErrors)) {
            $error = implode(' ', $passErrors);
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match. Please try again.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update password and invalidate the token in one query
            Database::query(
                "UPDATE users
                 SET password_hash = ?,
                     password_reset_token = NULL,
                     password_reset_expires = NULL
                 WHERE id = ?",
                [$hash, $user['id']]
            );

            // Also destroy any active sessions for this user for security
            // (Sessions are PHP-based so we can only inform them to re-login)

            Helpers::flash('success', 'Your password has been reset successfully. Please sign in with your new password.');
            Helpers::redirect('/login');
        }
    }
}

require BASE_PATH . '/views/auth/reset_password.php';
