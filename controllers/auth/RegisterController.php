<?php
/**
 * Admin Multi-Step Registration Wizard Controller
 */

// Retrieve gateway settings
$settingsRows = Database::fetchAll("SELECT * FROM `gateway_settings`");
$gw = [];
foreach ($settingsRows as $row) {
    $gw[$row['gateway_name']][$row['setting_key']] = $row['setting_value'];
}

$error = '';
$success = '';
$step = (int)($_GET['step'] ?? ($_POST['step'] ?? 1));

// Initialize registration session if not set
if (empty($_SESSION['admin_register_wizard'])) {
    $_SESSION['admin_register_wizard'] = [
        'company_name'   => '',
        'domain_prefix'  => '',
        'first_name'     => '',
        'last_name'      => '',
        'email'          => '',
        'verified'       => false,
        'step'           => 1
    ];
}

$wizard = &$_SESSION['admin_register_wizard'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        if ($step === 1) {
            // STEP 1: Account Creation & Domain Prefixes
            $companyName  = trim($_POST['company_name'] ?? '');
            $domainPrefix = strtolower(trim($_POST['domain_prefix'] ?? ''));
            $firstName    = trim($_POST['first_name'] ?? '');
            $lastName     = trim($_POST['last_name'] ?? '');
            $email        = strtolower(trim($_POST['email'] ?? ''));
            $password     = $_POST['password'] ?? '';
            $confirm      = $_POST['confirm_password'] ?? '';

            // Clean domain prefix to alphanumeric only
            $domainPrefix = preg_replace('/[^a-z0-9\-]/', '', $domainPrefix);

            // Validation checks
            if (!$companyName || !$domainPrefix || !$firstName || !$lastName || !$email || !$password) {
                $error = 'All fields are required to proceed.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                // Check if email already exists
                $emailExists = Database::fetchOne("SELECT id FROM `users` WHERE email = ?", [$email]);
                
                // Formulate target domains dynamically
                $appDomain = parse_url(Config::get('config', 'app.url') ?? 'http://eliteali.com', PHP_URL_HOST) ?: 'eliteali.com';
                $mainDomain = $domainPrefix . '.' . $appDomain;
                $trackDomain = $domainPrefix . '-track.' . $appDomain;

                // Check duplicate domains in tenants table
                $dupMain = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_main_domain = ? OR custom_domain = ?", [$mainDomain, $mainDomain]);
                $dupTrack = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_tracking_domain = ?", [$trackDomain]);

                if ($emailExists) {
                    $error = 'This email address is already registered on our tracker system.';
                } elseif ($dupMain || $dupTrack) {
                    $error = 'The workspace subdomain/prefix is already in use. Please pick another prefix.';
                } else {
                    // All validations passed! Save parameters to session
                    $wizard['company_name']  = $companyName;
                    $wizard['domain_prefix'] = $domainPrefix;
                    $wizard['first_name']    = $firstName;
                    $wizard['last_name']     = $lastName;
                    $wizard['email']         = $email;
                    $wizard['password']      = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Generate numeric verification OTP code
                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Clear older pending verifications for this email
                    Database::query("DELETE FROM `email_verifications` WHERE email = ? AND verified_at IS NULL", [$email]);

                    // Insert OTP into database verifications table
                    Database::insert('email_verifications', [
                        'email'      => $email,
                        'code'       => $otp,
                        'attempts'   => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    // Send email OTP via mailer class
                    $siteName = Config::get('config', 'app.name') ?? 'EliteAli Tracker';
                    $body = '<div style="font-family: sans-serif; max-width: 480px; margin: 0 auto; color: #1E293B;">
                        <h2 style="color: #6366F1; margin-top: 0;">Confirm Your Identity</h2>
                        <p>Hello <strong>' . htmlspecialchars($firstName) . '</strong>,</p>
                        <p>Thank you for initiating your administrator workspace registration on ' . htmlspecialchars($siteName) . '. Use the OTP verification code below to verify your email address:</p>
                        <div style="background: #F1F5F9; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0;">
                            <span style="font-size: 38px; font-weight: 800; letter-spacing: 8px; color: #4F46E5;">' . $otp . '</span>
                        </div>
                        <p style="color: #64748B; font-size: 13px;">This verification code is strictly valid for <strong>15 minutes</strong>. If you did not initiate this registration request, you can safely discard this message.</p>
                    </div>';

                    try {
                        Mailer::sendRaw($email, $firstName . ' ' . $lastName, "[$siteName] Admin Signup Verification Code: $otp", $body, 'email_verification');
                    } catch (\Throwable $_e) {}

                    $wizard['step'] = 2;
                    Helpers::redirect('/signup?step=2');
                }
            }
        } elseif ($step === 2) {
            // STEP 2: Email OTP verification
            $code = trim($_POST['verification_code'] ?? '');
            
            if (!$code) {
                $error = 'Verification code is required.';
            } else {
                $verifyRec = Database::fetchOne("SELECT * FROM `email_verifications` WHERE email = ? AND verified_at IS NULL ORDER BY created_at DESC LIMIT 1", [$wizard['email']]);
                
                if ($verifyRec) {
                    $expires = strtotime($verifyRec['created_at']) + 900; // 15 mins expiry
                    if (time() > $expires) {
                        $error = 'The verification code has expired. Please go back to step 1 and try again.';
                    } elseif ($verifyRec['code'] === $code) {
                        // Mark code as verified
                        Database::update('email_verifications', ['verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$verifyRec['id']]);
                        
                        $wizard['verified'] = true;
                        $wizard['step'] = 3;
                        Helpers::redirect('/signup?step=3');
                    } else {
                        // Increment attempts
                        Database::query("UPDATE `email_verifications` SET attempts = attempts + 1 WHERE id = ?", [$verifyRec['id']]);
                        $error = 'Invalid verification code. Please check your inbox and try again.';
                    }
                } else {
                    $error = 'No active verification request found. Please go back to step 1.';
                }
            }
        } elseif ($step === 3) {
            // STEP 3: Plan Selection & Tenant Account Deployment
            if (empty($wizard['verified'])) {
                $error = 'Identity verification is required first.';
                $wizard['step'] = 1;
                Helpers::redirect('/signup?step=1');
                exit;
            }

            $option = $_POST['plan_option'] ?? 'trial';
            $planId = (int)($_POST['plan_id'] ?? 1);

            // Fetch selected plan
            $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE id = ? AND status = 'active'", [$planId]);
            if (!$plan) {
                $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE status = 'active' ORDER BY price ASC LIMIT 1");
            }

            if (!$plan) {
                $error = 'No active subscription plans are currently configured. Please contact network support.';
            } else {
                try {
                    Database::begin();
                    
                    $appDomain = parse_url(Config::get('config', 'app.url') ?? 'http://eliteali.com', PHP_URL_HOST) ?: 'eliteali.com';
                    $mainDomain = $wizard['domain_prefix'] . '.' . $appDomain;
                    $trackDomain = $wizard['domain_prefix'] . '-track.' . $appDomain;

                    $tenantStatus = ($option === 'trial') ? 'active' : 'active';
                    
                    // 1. Create Tenant Record
                    $tenantId = Database::insert('tenants', [
                        'company_name'          => $wizard['company_name'],
                        'status'                => $tenantStatus,
                        'custom_domain'         => $mainDomain,
                        'admin_main_domain'     => $mainDomain,
                        'admin_tracking_domain' => $trackDomain,
                        'domain_verified'       => 1, // Auto-verified in registrar bounds
                        'ssl_active'            => 1,
                        'domain_health'         => 'active',
                        'trial_ends_at'         => ($option === 'trial') ? date('Y-m-d H:i:s', strtotime("+14 days")) : null,
                        'created_at'            => date('Y-m-d H:i:s')
                    ]);

                    // 2. Create Owner User
                    $userId = Database::insert('users', [
                        'email'         => strtolower($wizard['email']),
                        'password_hash' => $wizard['password'],
                        'role'          => 'admin',
                        'status'        => 'active',
                        'first_name'    => $wizard['first_name'],
                        'last_name'     => $wizard['last_name'],
                        'tenant_id'     => $tenantId,
                        'email_verified_at' => date('Y-m-d H:i:s'),
                        'created_at'    => date('Y-m-d H:i:s')
                    ]);

                    // 3. Map Owner User in tenant_users
                    Database::insert('tenant_users', [
                        'tenant_id'             => $tenantId,
                        'user_id'               => $userId,
                        'role'                  => 'ADMIN_OWNER',
                        'force_password_change' => 0, // Since they created their own password!
                        'created_at'            => date('Y-m-d H:i:s')
                    ]);

                    // 4. Bind Tracking Domain
                    Database::insert('tracking_domains', [
                        'tenant_id' => $tenantId,
                        'domain'    => $trackDomain,
                        'is_active' => 1,
                        'created_at'=> date('Y-m-d H:i:s')
                    ]);

                    // 5. Create Subscription Plan
                    if ($option === 'trial') {
                        // Option A: 14-day free trial subscription
                        Database::insert('subscriptions', [
                            'tenant_id'        => $tenantId,
                            'plan_id'          => $plan['id'],
                            'price'            => 0.00,
                            'duration'         => 14,
                            'status'           => 'trial',
                            'starts_at'        => date('Y-m-d H:i:s'),
                            'ends_at'          => date('Y-m-d H:i:s', strtotime("+14 days")),
                            'billing_provider' => 'trial',
                            'auto_renew'       => 0,
                            'created_at'       => date('Y-m-d H:i:s')
                        ]);

                        Tenant::logBilling($tenantId, 'subscription_created', 0.00, "Initialized 14-day free trial on {$plan['name']} plan.");
                        
                        // Send trial confirmation email
                        $siteName = Config::get('config', 'app.name') ?? 'EliteAli';
                        $body = "<p>Hello {$wizard['first_name']},</p><p>Your 14-day free trial has started! Log in to deploy smartlinks and custom campaigns.</p>";
                        try { Mailer::sendRaw($wizard['email'], $wizard['first_name'], "Free Trial Started — $siteName", $body, 'marketing'); } catch(\Throwable $_e) {}

                        Database::commit();

                        // Log them in immediately!
                        session_regenerate_id(true);
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['user_id']        = $userId;
                        $_SESSION['user_role']      = 'admin';
                        $_SESSION['user_email']     = strtolower($wizard['email']);
                        $_SESSION['user_name']      = trim($wizard['first_name'] . ' ' . $wizard['last_name']);
                        $_SESSION['tenant_id']      = $tenantId;

                        // Clear registration session
                        unset($_SESSION['admin_register_wizard']);

                        Helpers::redirect('/admin/dashboard');
                    } else {
                        // Option B: Paid Plan (1-day grace period for immediate checkout redirection)
                        Database::insert('subscriptions', [
                            'tenant_id'        => $tenantId,
                            'plan_id'          => $plan['id'],
                            'price'            => $plan['price'],
                            'duration'         => 1, // Grace period duration
                            'status'           => 'active',
                            'starts_at'        => date('Y-m-d H:i:s'),
                            'ends_at'          => date('Y-m-d H:i:s', strtotime("+1 day")),
                            'billing_provider' => 'stripe',
                            'auto_renew'       => 1,
                            'created_at'       => date('Y-m-d H:i:s')
                        ]);

                        Tenant::logBilling($tenantId, 'subscription_created', $plan['price'], "Created pending paid subscription for {$plan['name']}. Redirecting to checkout.");

                        Database::commit();

                        // Log them in immediately so checkout runs in their tenant context!
                        session_regenerate_id(true);
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['user_id']        = $userId;
                        $_SESSION['user_role']      = 'admin';
                        $_SESSION['user_email']     = strtolower($wizard['email']);
                        $_SESSION['user_name']      = trim($wizard['first_name'] . ' ' . $wizard['last_name']);
                        $_SESSION['tenant_id']      = $tenantId;

                        // Clear registration session
                        unset($_SESSION['admin_register_wizard']);

                        // Redirect to checkout with plan_id
                        Helpers::redirect('/admin/subscription/checkout?plan_id=' . $plan['id']);
                    }
                } catch (\Throwable $e) {
                    Database::rollback();
                    $error = 'Workspace deployment failed: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all active subscription plans
$plans = Database::fetchAll("SELECT * FROM `subscription_plans` WHERE status = 'active' ORDER BY price ASC");

$pageTitle = 'Create Admin Workspace Workspace';
require BASE_PATH . '/views/auth/register.php';
