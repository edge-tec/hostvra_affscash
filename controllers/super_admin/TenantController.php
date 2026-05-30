<?php
/**
 * Super Admin - Tenant Management Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $companyName    = trim($_POST['company_name'] ?? '');
        $mainDomain     = trim($_POST['admin_main_domain'] ?? '') ?: null;
        $trackingDomain = trim($_POST['admin_tracking_domain'] ?? '') ?: null;
        $firstName      = trim($_POST['first_name'] ?? '');
        $lastName       = trim($_POST['last_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $password       = $_POST['password'] ?? '';
        $planId         = (int)($_POST['plan_id'] ?? 1);
        $status         = $_POST['status'] ?? 'trial';

        // Check duplicate domain allocations
        $dupMain = null;
        if ($mainDomain) {
            $dupMain = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_main_domain = ?", [$mainDomain]);
        }
        $dupTrack = null;
        if ($trackingDomain) {
            $dupTrack = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_tracking_domain = ?", [$trackingDomain]);
        }

        if (!$companyName || !$firstName || !$lastName || !$email || !$password) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($dupMain) {
            $error = 'Admin Main Domain is already assigned to another account.';
        } elseif ($dupTrack) {
            $error = 'Tracking Domain is already assigned to another account.';
        } else {
            // Check if email already exists
            $existing = Database::fetchOne("SELECT id FROM `users` WHERE email = ?", [strtolower($email)]);
            if ($existing) {
                $error = 'Email address is already in use.';
            } else {
                try {
                    Database::begin();

                    // 1. Create Tenant
                    $tenantId = Database::insert('tenants', [
                        'company_name'          => $companyName,
                        'status'                => $status,
                        'custom_domain'         => $mainDomain ?: null,
                        'admin_main_domain'     => $mainDomain,
                        'admin_tracking_domain' => $trackingDomain,
                        'created_at'            => date('Y-m-d H:i:s')
                    ]);

                    // 2. Create Admin Owner User
                    $userId = Database::insert('users', [
                        'email'         => strtolower($email),
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                        'role'          => 'admin',
                        'status'        => 'active',
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
                        'tenant_id'     => $tenantId,
                        'created_at'    => date('Y-m-d H:i:s')
                    ]);

                    // 3. Map User in tenant_users
                    Database::insert('tenant_users', [
                        'tenant_id'             => $tenantId,
                        'user_id'               => $userId,
                        'role'                  => 'ADMIN_OWNER',
                        'force_password_change' => 1,
                        'created_at'            => date('Y-m-d H:i:s')
                    ]);

                    // 4. Create Active Subscription for selected plan
                    $plan = Database::fetchOne("SELECT * FROM `subscription_plans` WHERE id = ?", [$planId]);
                    if ($plan) {
                        $duration = (int)$plan['duration'];
                        Database::insert('subscriptions', [
                            'tenant_id'  => $tenantId,
                            'plan_id'    => $planId,
                            'price'      => $plan['price'],
                            'duration'   => $duration,
                            'status'     => 'active',
                            'starts_at'  => date('Y-m-d H:i:s'),
                            'ends_at'    => date('Y-m-d H:i:s', strtotime("+$duration days")),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                        // Log Billing Log
                        Tenant::logBilling($tenantId, 'subscription_created', $plan['price'], "Created {$plan['name']} plan for {$companyName}");
                    }

                    // 5. Automatically bind the tracking domain
                    if ($trackingDomain) {
                        Database::insert('tracking_domains', [
                            'tenant_id' => $tenantId,
                            'domain'    => $trackingDomain,
                            'is_active' => 1,
                            'created_at'=> date('Y-m-d H:i:s')
                        ]);
                    }

                    Database::commit();
                    $success = 'Tenant created successfully!';
                    Helpers::redirect('/super_admin/tenants?success=' . urlencode($success));
                } catch (\Throwable $e) {
                    Database::rollback();
                    $error = 'Failed to create tenant: ' . $e->getMessage();
                }
            }
        }
    }
} elseif ($action === 'impersonate') {
    $tenantId = (int)($_GET['id'] ?? 0);
    $adminUser = Database::fetchOne("SELECT u.* FROM `users` u JOIN `tenant_users` tu ON tu.user_id = u.id WHERE u.tenant_id = ? AND tu.role = 'ADMIN_OWNER' LIMIT 1", [$tenantId]);
    if ($adminUser) {
        Auth::impersonate($adminUser['id']);
        Helpers::redirect('/admin/dashboard');
    } else {
        $error = 'Could not find admin user for impersonation.';
        Helpers::redirect('/super_admin/tenants?error=' . urlencode($error));
    }
} elseif ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'trial';

        if (in_array($newStatus, ['trial', 'active', 'expired', 'suspended', 'cancelled'])) {
            Database::update('tenants', ['status' => $newStatus], 'id = ?', [$tenantId]);
            
            // Suspend users associated with the tenant if suspended
            $userStatus = ($newStatus === 'suspended') ? 'suspended' : 'active';
            Database::update('users', ['status' => $userStatus], 'tenant_id = ?', [$tenantId]);
            
            $success = "Tenant status updated to {$newStatus}.";
            Helpers::redirect('/super_admin/tenants?success=' . urlencode($success));
        } else {
            $error = 'Invalid status.';
        }
    }
} elseif ($action === 'update_domains' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $mainDomain = trim($_POST['admin_main_domain'] ?? '') ?: null;
        $trackingDomain = trim($_POST['admin_tracking_domain'] ?? '') ?: null;

        // Check duplicate domain allocations
        $dupMain = null;
        if ($mainDomain) {
            $dupMain = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_main_domain = ? AND id != ?", [$mainDomain, $tenantId]);
        }
        $dupTrack = null;
        if ($trackingDomain) {
            $dupTrack = Database::fetchOne("SELECT id FROM `tenants` WHERE admin_tracking_domain = ? AND id != ?", [$trackingDomain, $tenantId]);
        }

        if ($dupMain) {
            $error = 'Admin Main Domain is already assigned to another account.';
        } elseif ($dupTrack) {
            $error = 'Tracking Domain is already assigned to another account.';
        } else {
            Database::update('tenants', [
                'custom_domain'         => $mainDomain ?: null,
                'admin_main_domain'     => $mainDomain,
                'admin_tracking_domain' => $trackingDomain,
                'domain_verified'       => 0, // Reset verification status upon domain update
                'ssl_active'            => 0,
                'domain_health'         => 'unknown'
            ], 'id = ?', [$tenantId]);

            // Sync with tracking_domains table
            if ($trackingDomain) {
                Database::query("DELETE FROM `tracking_domains` WHERE tenant_id = ?", [$tenantId]);
                Database::insert('tracking_domains', [
                    'tenant_id' => $tenantId,
                    'domain'    => $trackingDomain,
                    'is_active' => 1,
                    'created_at'=> date('Y-m-d H:i:s')
                ]);
            } else {
                Database::query("DELETE FROM `tracking_domains` WHERE tenant_id = ?", [$tenantId]);
            }

            try {
                Activity::log('domain_update', 'tenants', $tenantId, "Super Admin updated domains: Main={$mainDomain}, Tracking={$trackingDomain}");
            } catch (\Throwable $t) {}

            $success = 'Tenant domains updated successfully!';
            Helpers::redirect('/super_admin/tenants?success=' . urlencode($success));
        }
    }
}

// Fetch all tenants
$tenants = Database::fetchAll(
    "SELECT t.*, s.status as sub_status, s.ends_at, p.name as plan_name, u.email as admin_email, u.first_name, u.last_name
     FROM `tenants` t
     LEFT JOIN `subscriptions` s ON s.tenant_id = t.id AND s.status = 'active'
     LEFT JOIN `subscription_plans` p ON p.id = s.plan_id
     LEFT JOIN `users` u ON u.tenant_id = t.id AND u.role = 'admin'
     GROUP BY t.id
     ORDER BY t.id DESC"
);

$plans = Database::fetchAll("SELECT * FROM `subscription_plans` WHERE status = 'active'");

require BASE_PATH . '/views/super_admin/tenants.php';
