<?php
$pageTitle = 'Advertiser Registration';
$errors = [];

// ── Master switch ────────────────────────────────────────────────────────
// When the admin disables Advertiser Registration (Settings → Security),
// neither the form (GET) nor a programmatic submission (POST) is honoured.
// We respond 403 + render a friendly closed-page so search engines and
// clients see the intent clearly. Default = enabled, so existing installs
// behave exactly like before until an admin flips the switch.
$_advRegEnabled = (Config::get('config', 'app.advertiser_registration_enabled') ?? '1') === '1';
if (!$_advRegEnabled) {
    http_response_code(403);
    $_advClosedMsg = trim((string)Config::get('config', 'app.advertiser_registration_closed_message'))
                     ?: 'Advertiser registration is currently closed.';
    require BASE_PATH . '/views/auth/advertiser_registration_closed.php';
    return;
}

// ── VPN/Proxy/TOR registration guard ─────────────────────────────────────
if (Helpers::isPost()) {
    $_vpnCheck = RegistrationVpnGuard::checkIp(Helpers::getIp());
    if ($_vpnCheck['blocked']) {
        http_response_code(403);
        $_vpnBlockReason = $_vpnCheck['reason'];
        require BASE_PATH . '/views/auth/registration_vpn_blocked.php';
        return;
    }
}

try { Database::query("ALTER TABLE users ADD COLUMN skype VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users ADD COLUMN telegram VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN discord VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE advertisers ADD COLUMN registration_answers TEXT NULL"); } catch(Exception $e) {}
$questions = Database::fetchAll(
    "SELECT * FROM `registration_questions` WHERE target_role IN ('advertiser','both') AND is_active=1 ORDER BY sort_order"
);

if (Helpers::isPost()) {
    $ip = Helpers::getIp();
    $isRateLimited = !RegistrationSecurity::checkRateLimit($ip);

    if ($isRateLimited) {
        $errors[] = 'Too many registration attempts from your IP. Please try again in 15 minutes.';
    } elseif (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $errors[] = 'Invalid form submission.';
    } else {
        // ── Cloudflare Turnstile verification ─────────────────────────────
        if (Turnstile::isEnabled()) {
            $tsToken = trim($_POST['cf-turnstile-response'] ?? '');
            if (!Turnstile::verify($tsToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
                $errors[] = 'CAPTCHA verification failed. Please complete the challenge and try again.';
            }
        }

        $fname    = Helpers::post('first_name');
        $lname    = Helpers::post('last_name');
        $email    = strtolower(Helpers::postRaw('email'));
        $company  = Helpers::post('company');
        $phone    = Helpers::post('phone');
        $country  = Helpers::post('country');
        $skype    = trim(Helpers::post('skype') ?? '') ?: null;
        $telegram = trim(Helpers::post('telegram') ?? '') ?: null;
        $discord  = trim(Helpers::post('discord') ?? '') ?: null;
        $pass     = Helpers::postRaw('password');
        $confirm  = Helpers::postRaw('confirm_password');

        // Mandatory consent — Privacy Policy AND Terms & Conditions both required.
        $agreePrivacy = isset($_POST['agree_privacy']) && $_POST['agree_privacy'] === '1';
        $agreeTerms   = isset($_POST['agree_terms'])   && $_POST['agree_terms']   === '1';
        if (!$agreePrivacy) $errors[] = 'You must accept the Privacy Policy to register.';
        if (!$agreeTerms)   $errors[] = 'You must accept the Terms & Conditions to register.';

        if (!$fname || !$lname)   $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } elseif (RegistrationSecurity::isDisposableEmail($email)) {
            $errors[] = 'Registration using temporary or disposable email addresses is not allowed.';
        }
        if (!$skype && !$telegram && !$discord) $errors[] = 'At least one contact method is required (Telegram, Skype, or Discord).';
        
        // Enforce strong password complexity rules
        $passErrors = RegistrationSecurity::validatePassword($pass);
        if (!empty($passErrors)) {
            $errors = array_merge($errors, $passErrors);
        }
        if ($pass !== $confirm)   $errors[] = 'Passwords do not match.';

        if (!$errors && Database::fetchOne("SELECT id FROM `users` WHERE email=?", [$email])) {
            $errors[] = 'Email already registered.';
        }

        $answers = [];
        foreach ($questions as $q) {
            $val = $_POST['q_' . $q['id']] ?? '';
            if (is_array($val)) $val = implode(', ', $val);
            if ($q['is_required'] && empty($val)) {
                $errors[] = 'Please answer: ' . $q['question_text'];
            }
            $answers[$q['id']] = $val;
        }

        if (!$errors) {
            Database::begin();
            try {
                $userId = Database::insert('users', [
                    'email'         => $email,
                    'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role'          => 'advertiser',
                    'status'        => 'pending',
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                    'company'       => $company,
                    'phone'         => $phone,
                    'country'       => strtoupper(substr($country, 0, 2)),
                    'skype'         => $skype,
                    'telegram'      => $telegram,
                    'discord'       => $discord,
                ]);

                $advCode = 'ADV' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                Database::insert('advertisers', [
                    'user_id'              => $userId,
                    'advertiser_code'      => $advCode,
                    'billing_email'        => $email,
                    'registration_answers' => json_encode($answers),
                ]);

                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'info',
                    'title'       => 'New Advertiser Registration',
                    'message'     => "$fname $lname has registered as an advertiser and is pending approval.",
                    'link'        => '/admin/advertisers',
                ]);

                // Email verification
                $emailVerifyEnabled = (Config::get('config','app.email_verification') === '1');
                if ($emailVerifyEnabled) {
                    $verifyToken = bin2hex(random_bytes(32));
                    Database::update('users', ['email_verify_token' => $verifyToken], 'id=?', [$userId]);
                    $appUrl   = rtrim(Config::get('config','app.url') ?? '', '/');
                    $siteName = Config::get('config','app.name') ?? 'AffiliateTracker';
                    $verifyUrl = $appUrl . '/verify-email?token=' . $verifyToken;
                    $body = '<div style="font-family:sans-serif;max-width:520px;margin:0 auto">
                        <h2 style="color:#4F46E5">Verify Your Email — ' . htmlspecialchars($siteName) . '</h2>
                        <p>Hello <strong>' . htmlspecialchars("$fname $lname") . '</strong>,</p>
                        <p>Thank you for registering as an advertiser. Please verify your email address:</p>
                        <div style="text-align:center;margin:28px 0">
                            <a href="' . $verifyUrl . '" style="background:#4F46E5;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px">Verify Email Address</a>
                        </div>
                        <p style="color:#64748B;font-size:13px">Or copy this link: <a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>
                        <p style="color:#94A3B8;font-size:12px">If you did not create this account, you can safely ignore this email.</p>
                    </div>';
                    try { Mailer::sendRaw($email, "$fname $lname", "Verify your email — $siteName", $body, 'email_verification'); } catch(\Exception $_e) {}
                }

                Database::commit();

                // Log successful attempt
                RegistrationSecurity::logAttempt($ip, 'advertiser', $email, true);

                $regMsg = Config::get('config','app.registration_message') ?: "Registration successful.\nYour account is currently inactive. Please contact support for activation.\nTelegram: @affscashnet";
                if ($emailVerifyEnabled) {
                    $regMsg = "Registration successful!\nA verification link has been sent to $email.\nPlease verify your email to continue.";
                }
                Helpers::flash('success', $regMsg);
                Helpers::redirect('/login');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Registration failed. Please try again.';
                // Log failed attempt (DB Exception)
                RegistrationSecurity::logAttempt($ip, 'advertiser', $email, false);
            }
        } else {
            // Log failed attempt (Validation failure)
            RegistrationSecurity::logAttempt($ip, 'advertiser', $email, false);
        }
    }
}

require BASE_PATH . '/views/auth/register_advertiser.php';
