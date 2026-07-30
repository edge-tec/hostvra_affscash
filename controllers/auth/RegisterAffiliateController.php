<?php
$pageTitle = 'Affiliate Registration';
$errors = [];
try { Database::query("ALTER TABLE users ADD COLUMN skype VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN email_verify_token VARCHAR(64) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users ADD COLUMN telegram VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN discord VARCHAR(200) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE users ADD COLUMN address VARCHAR(500) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE affiliates ADD COLUMN registration_ip VARCHAR(45) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliates ADD COLUMN social_platform VARCHAR(50) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliates ADD COLUMN social_profile_url VARCHAR(500) DEFAULT NULL"); } catch(\Throwable $_e) {}

// ── VPN/Proxy/TOR registration guard ─────────────────────────────────────
$_vpnRegEnabled = (Config::get('config', 'vpn_detection.registration_enabled') ?? '1') === '1';
if ($_vpnRegEnabled && Helpers::isPost()) {
    $_vpnCheck = RegistrationVpnGuard::checkIp(Helpers::getIp());
    if ($_vpnCheck['blocked']) {
        http_response_code(403);
        $_vpnBlockReason = $_vpnCheck['reason'];
        require BASE_PATH . '/views/auth/registration_vpn_blocked.php';
        return;
    }
}

// ── Referral code handling ─────────────────────────────────────────────────
// Store ?ref= in session so it persists across page reloads / form submissions
if (!isset($_SESSION)) session_start();
$refCode = '';
if (!empty($_GET['ref'])) {
    $_SESSION['referral_code'] = strtoupper(trim($_GET['ref']));
}
if (!empty($_SESSION['referral_code'])) {
    $refCode = $_SESSION['referral_code'];
}
// Resolve who is referring
$referrerInfo = $refCode ? Referral::resolveCode($refCode) : null;

$questions = Database::fetchAll(
    "SELECT * FROM `registration_questions` WHERE target_role IN ('affiliate','both') AND is_active=1 ORDER BY sort_order"
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
        $phone    = trim(Helpers::post('phone') ?? '');
        $country  = strtoupper(trim(Helpers::post('country') ?? ''));
        $address  = trim(Helpers::post('address') ?? '');
        $skype    = trim(Helpers::post('skype') ?? '') ?: null;
        $telegram = trim(Helpers::post('telegram') ?? '') ?: null;
        $discord  = trim(Helpers::post('discord') ?? '') ?: null;
        $socialPlatform   = strtolower(trim(Helpers::post('social_platform') ?? ''));
        $socialProfileUrl = trim(Helpers::post('social_profile_url') ?? '');
        $pass     = Helpers::postRaw('password');
        $confirm  = Helpers::postRaw('confirm_password');

        // Mandatory consent — Privacy Policy AND Terms & Conditions both required.
        $agreePrivacy   = isset($_POST['agree_privacy'])   && $_POST['agree_privacy']   === '1';
        $agreeTerms     = isset($_POST['agree_terms'])     && $_POST['agree_terms']     === '1';
        $agreeAffiliate = isset($_POST['agree_affiliate']) && $_POST['agree_affiliate'] === '1';
        $agreeFraud     = isset($_POST['agree_fraud'])     && $_POST['agree_fraud']     === '1';
        
        if (!$agreePrivacy)   $errors[] = 'You must accept the Privacy Policy to register.';
        if (!$agreeTerms)     $errors[] = 'You must accept the Terms & Conditions to register.';
        if (!$agreeAffiliate) $errors[] = 'You must accept the Affiliate Agreement to register.';
        if (!$agreeFraud)     $errors[] = 'You must accept the Anti-Fraud Policy to register.';

        if (!$fname || !$lname)   $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } elseif (RegistrationSecurity::isDisposableEmail($email)) {
            $errors[] = 'Registration using temporary or disposable email addresses is not allowed.';
        }
        if (!$phone)              $errors[] = 'Phone number is required.';
        if (!$country)            $errors[] = 'Country is required.';
        if (!$address)            $errors[] = 'Street address is required.';
        if (!$skype && !$telegram && !$discord) $errors[] = 'At least one contact method is required (Telegram, Skype, or Discord).';

        // Social Media Verification — mandatory
        $validPlatforms = ['facebook','instagram','x','linkedin','tiktok','youtube','telegram','reddit','snapchat','pinterest','threads','other'];
        if (!$socialPlatform || !in_array($socialPlatform, $validPlatforms, true)) {
            $errors[] = 'Please select a social media platform.';
        }
        if (!$socialProfileUrl) {
            $errors[] = 'Please enter a valid social media profile URL.';
        } elseif (!str_starts_with($socialProfileUrl, 'https://')) {
            $errors[] = 'Social media URL must begin with https://';
        } elseif ($socialPlatform && $socialPlatform !== 'other' && in_array($socialPlatform, $validPlatforms, true)) {
            $platformDomains = [
                'facebook'  => ['facebook.com','fb.com'],
                'instagram' => ['instagram.com'],
                'x'         => ['x.com','twitter.com'],
                'linkedin'  => ['linkedin.com'],
                'tiktok'    => ['tiktok.com'],
                'youtube'   => ['youtube.com','youtu.be'],
                'telegram'  => ['t.me','telegram.me'],
                'reddit'    => ['reddit.com'],
                'snapchat'  => ['snapchat.com'],
                'pinterest' => ['pinterest.com'],
                'threads'   => ['threads.net'],
            ];
            if (isset($platformDomains[$socialPlatform])) {
                $parsedHost = strtolower(parse_url($socialProfileUrl, PHP_URL_HOST) ?? '');
                $parsedHost = preg_replace('/^www\./', '', $parsedHost);
                $domainMatch = false;
                foreach ($platformDomains[$socialPlatform] as $allowedDomain) {
                    if ($parsedHost === $allowedDomain || str_ends_with($parsedHost, '.' . $allowedDomain)) {
                        $domainMatch = true;
                        break;
                    }
                }
                if (!$domainMatch) {
                    $errors[] = 'The URL does not match the selected social media platform.';
                }
            }
        }
        
        // Enforce strong password complexity rules
        $passErrors = RegistrationSecurity::validatePassword($pass);
        if (!empty($passErrors)) {
            $errors = array_merge($errors, $passErrors);
        }
        if ($pass !== $confirm)   $errors[] = 'Passwords do not match.';

        // Check if email exists
        if (!$errors && Database::fetchOne("SELECT id FROM `users` WHERE email=?", [$email])) {
            $errors[] = 'Email already registered.';
        }

        // Collect question answers
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
            // ── Run Referral migration BEFORE opening the transaction ────────
            // DDL statements (CREATE TABLE) cause an implicit commit in MySQL/MariaDB.
            // Calling migrate() inside a transaction would silently commit it,
            // leaving rollback() with no active transaction → fatal PDOException.
            Referral::migrate();

            Database::begin();
            try {
                $userId = Database::insert('users', [
                    'email'         => $email,
                    'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role'          => 'affiliate',
                    'status'        => 'pending',
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                    'company'       => $company,
                    'phone'         => $phone,
                    'country'       => substr($country, 0, 2),
                    'address'       => $address,
                    'skype'         => $skype,
                    'telegram'      => $telegram,
                    'discord'       => $discord,
                ]);

                $affCode = 'AFF' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                Database::insert('affiliates', [
                    'user_id'               => $userId,
                    'affiliate_code'        => $affCode,
                    'registration_answers'  => json_encode($answers),
                    'registration_ip'       => Helpers::getIp(),
                    'social_platform'       => $socialPlatform ?: null,
                    'social_profile_url'    => $socialProfileUrl ?: null,
                ]);

                // ── Hook referral ─────────────────────────────────────────
                // Must run AFTER affiliate row is inserted so we have the aff ID.
                // Referral::migrate() was already called above — no DDL will run here.
                if ($referrerInfo && ($referrerInfo['status'] ?? '') === 'active') {
                    $newAffRow = Database::fetchOne("SELECT id FROM affiliates WHERE user_id=?", [$userId]);
                    if ($newAffRow) {
                        Referral::recordSignup(
                            (int)$referrerInfo['user_id'],
                            $referrerInfo['role'],
                            (int)$newAffRow['id'],
                            $userId
                        );
                    }
                }
                // Clear referral code from session after use
                unset($_SESSION['referral_code']);

                // Notify admin via push + in-app
                require_once BASE_PATH . '/core/NotificationHelper.php';
                NotificationHelper::notifyRole(
                    'admin',
                    'New Affiliate Registration',
                    "$fname $lname has registered as an affiliate and is pending approval.",
                    'info',
                    '/admin/affiliates',
                    ['type' => 'account'],
                    'affiliate_registered',
                    'affiliate_details/' . $userId
                );

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
                        <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
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
                RegistrationSecurity::logAttempt($ip, 'affiliate', $email, true);

                // Send account creation email
                Mailer::sendEvent($email, "$fname $lname", 'affiliate_created', [
                    'name'           => "$fname $lname",
                    'email'          => $email,
                    'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                    'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
                    'affiliate_code' => $affCode,
                ]);

                $regMsg = Config::get('config','app.registration_message') ?: "Registration successful.\nYour account is currently inactive. Please contact support for activation.\nTelegram: @affscashnet";
                if ($emailVerifyEnabled) {
                    $regMsg = "Registration successful!\nA verification link has been sent to $email.\nPlease verify your email to continue.";
                }
                Helpers::flash('success', $regMsg);
                Helpers::redirect('/login');
            } catch (\Exception $e) {
                // Only rollback if a transaction is still active.
                // DDL inside Referral::migrate() could have auto-committed it.
                try { Database::rollback(); } catch (\Throwable $rbEx) {}
                $errors[] = 'Registration failed. Please try again.';
                // Log failed attempt
                RegistrationSecurity::logAttempt($ip, 'affiliate', $email, false);
            }
        } else {
            // Log failed attempt
            RegistrationSecurity::logAttempt($ip, 'affiliate', $email, false);
        }
    }
}

require BASE_PATH . '/views/auth/register_affiliate.php';
