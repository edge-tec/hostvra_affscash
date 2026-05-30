<?php
/**
 * Super Admin - Security & Audit Controller
 */
Auth::check();
if (Auth::role() !== 'super_admin') {
    Helpers::redirect('/');
}

$pageTitle = 'Security & Audit Logs';

// Fetch all audit activity logs
$activityLogs = Database::fetchAll(
    "SELECT a.*, u.email as user_email, u.role as user_role 
     FROM `activity_log` a 
     LEFT JOIN `users` u ON u.id = a.user_id 
     ORDER BY a.created_at DESC LIMIT 200"
);

// Fetch IP bans if any
$ipBans = [];
try {
    $ipBans = Database::fetchAll("SELECT * FROM `login_ip_bans` ORDER BY created_at DESC");
} catch (\Throwable $e) {}

require BASE_PATH . '/views/super_admin/security.php';
