<?php
Auth::check(); // Allow any logged in user
$userId = Auth::id();
$role   = Auth::role();
$action = Helpers::get('action', 'list');

try {
    Database::query("
        CREATE TABLE IF NOT EXISTS `user_devices` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `device_token` VARCHAR(500) NOT NULL,
            `platform` VARCHAR(50) DEFAULT 'android',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_token` (`device_token`),
            INDEX `idx_user` (`user_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Throwable $e) {}

if ($action === 'register_token') {
    $token = Helpers::post('token');
    $platform = Helpers::post('platform', 'android');
    if ($token) {
        Database::query(
            "INSERT INTO user_devices (user_id, device_token, platform) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP",
            [$userId, $token, $platform]
        );
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing token']);
    }
    exit;
}

if ($action === 'mark_read') {
    $id = (int)Helpers::post('id');
    if ($id) {
        Database::query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, $userId]);
    } else {
        Database::query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

$rows = Database::fetchAll(
    "SELECT id, title, message, link, is_read, created_at FROM notifications 
     WHERE (user_id=? OR (user_id IS NULL AND target_role=?)) 
     ORDER BY created_at DESC LIMIT 50",
    [$userId, $role]
);

$unread = 0;
foreach ($rows as $r) { if (!$r['is_read']) $unread++; }

echo json_encode([
    'success' => true,
    'notifications' => $rows,
    'unread' => $unread
]);
