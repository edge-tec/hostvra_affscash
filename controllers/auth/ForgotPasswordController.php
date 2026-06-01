<?php
// Redirect if already logged in
if (Auth::id()) { Helpers::redirect('/' . Auth::role() . '/dashboard'); }

// Schema guard — add reset columns if they don't exist yet
try { Database::query("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME DEFAULT NULL"); } catch(Exception $e) {}

$pageTitle = 'Forgot Password';
$error     = '';
$success   = '';

if (Helpers::isPost()) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email = strtolower(trim(Helpers::postRaw('email') ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Look up the user — but always show the same success message
            // to prevent email enumeration attacks
            $user = Database::fetchOne(
                "SELECT id, first_name, last_name, email, status FROM users WHERE email = ?",
                [$email]
            );

            if ($user && $user['status'] === 'active') {
                // Rate-limit: allow only one email per 60 seconds
                $existing = Database::fetchOne(
                    "SELECT password_reset_expires FROM users WHERE id = ?",
                    [$user['id']]
                );
                $canSend = true;
                if (!empty($existing['password_reset_expires'])) {
                    $expiresAt = strtotime($existing['password_reset_expires']);
                    $issuedAt  = $expiresAt - 3600; // token lifetime = 1 hour
                    if ((time() - $issuedAt) < 60) {
                        $canSend = false;
                    }
                }

                if ($canSend) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600);

                    Database::query(
                        "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
                        [$token, $expires, $user['id']]
                    );

                    $siteName  = Config::get('config', 'app.name') ?? 'AffiliateTracker';
                    $siteUrl   = rtrim(Config::get('config', 'app.url') ?? '', '/');
                    $resetLink = $siteUrl . '/reset-password?token=' . urlencode($token);
                    $toName    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';

                    $body = '
<p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
<p>We received a request to reset your password for your <strong>' . htmlspecialchars($siteName) . '</strong> account.</p>
<div style="text-align:center;margin:28px 0">
  <a href="' . htmlspecialchars($resetLink) . '"
     style="background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block">
    Reset My Password
  </a>
</div>
<p style="color:#64748B;font-size:13px">This link expires in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email — your password will not change.</p>
<hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0">
<p style="color:#94A3B8;font-size:12px">If the button above does not work, copy and paste this URL into your browser:<br>
  <a href="' . htmlspecialchars($resetLink) . '" style="color:#4F46E5;word-break:break-all;font-size:11px">' . htmlspecialchars($resetLink) . '</a>
</p>';
                    $body = Mailer::applyTheme($body, 'Password Reset Request');

                    try {
                        Mailer::sendRaw($user['email'], $toName, "[$siteName] Reset your password", $body, 'password_reset');
                    } catch (Exception $e) {
                        // Log silently — user still sees the generic success message
                    }
                }
            }

            // Always show this — never reveal whether an email exists
            $success = 'If an account with that email address exists, a password reset link has been sent. Please check your inbox and spam folder.';
        }
    }
}

require BASE_PATH . '/views/auth/forgot_password.php';
