<?php
/**
 * Super Admin - Landing Page Access Control Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle') {
            $tenantId = (int)($_POST['tenant_id'] ?? 0);
            $status = (int)($_POST['landing_enabled'] ?? 1);

            Database::update('tenants', ['landing_enabled' => $status], 'id = ?', [$tenantId]);
            Database::update('users', ['landing_enabled' => $status], 'tenant_id = ? AND role = ?', [$tenantId, 'admin']);

            // Log activity log
            $statusText = $status ? 'Enabled' : 'Disabled';
            Activity::log('landing_control', 'tenants', $tenantId, "Super Admin {$statusText} Landing Page for tenant #{$tenantId}");

            $success = "Landing Page access has been {$statusText} for tenant #{$tenantId}.";
            Helpers::redirect('/super_admin/landing_control?success=' . urlencode($success));

        } elseif ($action === 'bulk_update') {
            $tenantIds = $_POST['tenant_ids'] ?? [];
            $status = (int)($_POST['landing_enabled'] ?? 1);

            if (!empty($tenantIds) && is_array($tenantIds)) {
                $statusText = $status ? 'Enabled' : 'Disabled';
                
                foreach ($tenantIds as $tid) {
                    $tid = (int)$tid;
                    Database::update('tenants', ['landing_enabled' => $status], 'id = ?', [$tid]);
                    Database::update('users', ['landing_enabled' => $status], 'tenant_id = ? AND role = ?', [$tid, 'admin']);
                    
                    Activity::log('landing_control', 'tenants', $tid, "Super Admin bulk-{$statusText} Landing Page access.");
                }

                $success = "Landing Page access successfully bulk-{$statusText} for " . count($tenantIds) . " tenants.";
                Helpers::redirect('/super_admin/landing_control?success=' . urlencode($success));
            } else {
                $error = 'No tenants selected.';
            }
        }
    }
}

// Fetch all tenants
$tenants = Database::fetchAll(
    "SELECT t.*, u.email as admin_email, u.first_name, u.last_name
     FROM `tenants` t
     LEFT JOIN `users` u ON u.tenant_id = t.id AND u.role = 'admin'
     GROUP BY t.id
     ORDER BY t.id DESC"
);

// Fetch landing page toggles audit logs
$auditLogs = Database::fetchAll(
    "SELECT a.*, u.email as user_email 
     FROM `activity_log` a 
     LEFT JOIN `users` u ON u.id = a.user_id 
     WHERE a.action = 'landing_control' 
     ORDER BY a.created_at DESC LIMIT 100"
);

$pageTitle = 'Landing Access Control';
require BASE_PATH . '/views/super_admin/landing_control.php';
