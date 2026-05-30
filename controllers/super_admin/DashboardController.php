<?php
/**
 * Super Admin - Dashboard Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$pageTitle = 'Super Admin Dashboard';

// Counts
$totalTenants = Database::count('tenants', '1');
$activeSubs = Database::count('subscriptions', "status='active' AND ends_at > NOW()");
$expiredSubs = Database::count('tenants', "status='expired'");

// Revenue calculations (from billing logs or payments)
$revenue30d = Database::fetchOne("SELECT SUM(amount) as total FROM `billing_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0.00;
$revenue6m = Database::fetchOne("SELECT SUM(amount) as total FROM `billing_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY)")['total'] ?? 0.00;
$revenue1y = Database::fetchOne("SELECT SUM(amount) as total FROM `billing_logs` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)")['total'] ?? 0.00;

// Recent Tenants
$recentTenants = Database::fetchAll(
    "SELECT t.*, s.status as sub_status, s.ends_at, p.name as plan_name 
     FROM `tenants` t 
     LEFT JOIN `subscriptions` s ON s.tenant_id = t.id AND s.status = 'active'
     LEFT JOIN `subscription_plans` p ON p.id = s.plan_id
     ORDER BY t.created_at DESC LIMIT 5"
);

// Recent Billing logs
$recentBilling = Database::fetchAll(
    "SELECT b.*, t.company_name 
     FROM `billing_logs` b 
     JOIN `tenants` t ON t.id = b.tenant_id 
     ORDER BY b.created_at DESC LIMIT 5"
);

require BASE_PATH . '/views/super_admin/dashboard.php';
