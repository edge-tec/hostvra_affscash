<?php
/**
 * Admin Subscription - Renewal Dashboard Controller
 */
Auth::check('admin');

$pageTitle = 'Subscription Renewal & Plan Selection';

// Resolve current subscription info
$tenantId = (int)$_SESSION['tenant_id'];
$activeSub = Tenant::getSubscription($tenantId);

// Fetch all active subscription tiers available
$plans = Database::fetchAll("SELECT * FROM `subscription_plans` WHERE status = 'active' ORDER BY price ASC");

require BASE_PATH . '/views/admin/subscription/renew.php';
