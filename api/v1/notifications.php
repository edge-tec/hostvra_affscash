<?php
define('BASE_PATH', dirname(__DIR__, 2));
define('CONFIG_PATH', BASE_PATH . '/config');

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/FraudIQ.php';

Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config','app.timezone') ?? 'UTC');
Auth::start();

header('Content-Type: application/json');

if (!Auth::id()) {
    echo json_encode(['count' => 0]);
    exit;
}

if (isset($_GET['count'])) {
    $userId = Auth::id();
    $role   = Auth::role();

    $count = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications WHERE is_read=0 AND (user_id=? OR target_role=? OR target_role='all')",
        [$userId, $role]
    );
    echo json_encode(['count' => (int)($count['cnt'] ?? 0)]);
    exit;
}

if (isset($_GET['mark_read'])) {
    $userId = Auth::id();
    // Only mark user-specific notifications as read to prevent clearing broadcast notifications for everyone.
    Database::query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$userId]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['count' => 0]);
