<?php
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden.']));
}
/**
 * Notifications API v2 Controller
 *
 * Endpoints:
 *   - list (default): paginated notifications with deep link data
 *   - unread_count: lightweight count-only endpoint for badge polling
 *   - mark_read: mark single or all notifications as read
 *   - delete: delete a specific notification
 *   - register_token: register FCM device token
 */
Auth::check(); // Allow any logged-in user
$userId = Auth::id();
$role   = Auth::role();
$action = Helpers::get('action', 'list');

header('Content-Type: application/json');

// ── Ensure tables exist ──────────────────────────────────────────────────
try {
    Database::query("
        CREATE TABLE IF NOT EXISTS `user_devices` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `device_token` VARCHAR(500) NOT NULL,
            `device_id` VARCHAR(255) DEFAULT NULL,
            `platform` VARCHAR(50) DEFAULT 'android',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_token` (`device_token`),
            INDEX `idx_user` (`user_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Throwable $e) {}

// Ensure notification columns exist
try { Database::query("ALTER TABLE notifications ADD COLUMN `type` VARCHAR(50) DEFAULT 'info'"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE notifications ADD COLUMN `link` VARCHAR(500) DEFAULT NULL"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE notifications ADD COLUMN `notification_type` VARCHAR(50) DEFAULT NULL"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE notifications ADD COLUMN `deep_link_route` VARCHAR(255) DEFAULT NULL"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE notifications ADD COLUMN `target_role` VARCHAR(50) DEFAULT NULL"); } catch (\Throwable $e) {}

// Per-user broadcast read tracking
try {
    Database::query("
        CREATE TABLE IF NOT EXISTS `notification_reads` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `notification_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `read_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_notif_user` (`notification_id`, `user_id`),
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (\Throwable $e) {}

// ── Register FCM token ───────────────────────────────────────────────────
if ($action === 'register_token') {
    $token    = Helpers::post('token');
    $platform = Helpers::post('platform', 'android');
    $deviceId = Helpers::post('device_id', '');
    if ($token) {
        // Upsert: if token exists, update user_id (handles account switching)
        Database::query(
            "INSERT INTO user_devices (user_id, device_token, device_id, platform) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), device_id=VALUES(device_id), updated_at=CURRENT_TIMESTAMP",
            [$userId, $token, $deviceId, $platform]
        );
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing token']);
    }
    exit;
}

// ── Unread count (lightweight endpoint for polling) ──────────────────────
if ($action === 'unread_count') {
    // Count user-targeted unread
    $userUnread = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0",
        [$userId]
    );

    // Count broadcast (role-targeted) unread — exclude those the user has already read
    $broadcastUnread = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications n 
         WHERE n.user_id IS NULL AND n.target_role IN (?, 'all')
         AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?)",
        [$role, $userId]
    );

    $total = (int)($userUnread['cnt'] ?? 0) + (int)($broadcastUnread['cnt'] ?? 0);
    echo json_encode(['success' => true, 'unread' => $total]);
    exit;
}

// ── Mark read ────────────────────────────────────────────────────────────
if ($action === 'mark_read') {
    $id = (int)Helpers::post('id');
    if ($id) {
        // Check if it's a user notification or broadcast
        $notif = Database::fetchOne("SELECT user_id, target_role FROM notifications WHERE id=?", [$id]);
        if ($notif) {
            if ($notif['user_id']) {
                // User-targeted: update is_read directly
                Database::query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, $userId]);
            } else {
                // Broadcast: insert into notification_reads for this user
                try {
                    Database::query(
                        "INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)",
                        [$id, $userId]
                    );
                } catch (\Throwable $e) {}
            }
        }
    } else {
        // Mark ALL as read
        Database::query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$userId]);
        // Mark all broadcasts as read for this user
        try {
            $broadcastIds = Database::fetchAll(
                "SELECT n.id FROM notifications n 
                 WHERE n.user_id IS NULL AND n.target_role IN (?, 'all')
                 AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?)",
                [$role, $userId]
            );
            foreach ($broadcastIds as $bn) {
                Database::query(
                    "INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)",
                    [$bn['id'], $userId]
                );
            }
        } catch (\Throwable $e) {}
    }
    
    // Emit silent push to Android app to decrement the badge count
    require_once __DIR__ . '/../../core/BadgeSyncHelper.php';
    BadgeSyncHelper::emitReadSync($userId);
    
    echo json_encode(['success' => true]);
    exit;
}

// ── Delete notification ──────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)Helpers::post('id');
    if ($id) {
        // Only delete user's own notifications
        Database::query("DELETE FROM notifications WHERE id=? AND user_id=?", [$id, $userId]);
        // Also remove from broadcast reads
        try {
            Database::query("DELETE FROM notification_reads WHERE notification_id=? AND user_id=?", [$id, $userId]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing notification id']);
    }
    exit;
}

// ── List notifications (paginated) ───────────────────────────────────────
$page    = max(1, (int)Helpers::get('page', 1));
$perPage = min(50, max(10, (int)Helpers::get('per_page', 20)));
$offset  = ($page - 1) * $perPage;

// Fetch user-targeted + broadcast notifications for this user's role
$rows = Database::fetchAll(
    "SELECT n.id, n.title, n.message, n.type, n.link, n.notification_type, n.deep_link_route,
            n.is_read, n.user_id as notif_user_id, n.target_role, n.created_at,
            nr.id as read_id
     FROM notifications n
     LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = ?
     WHERE (n.user_id = ? OR (n.user_id IS NULL AND n.target_role IN (?, 'all')))
     ORDER BY n.created_at DESC
     LIMIT ? OFFSET ?",
    [$userId, $userId, $role, $perPage + 1, $offset]
);

// Check if there are more pages
$hasMore = count($rows) > $perPage;
if ($hasMore) {
    $rows = array_slice($rows, 0, $perPage);
}

// Format notifications with proper read status
$notifications = [];
foreach ($rows as $r) {
    $isRead = false;
    if ($r['notif_user_id']) {
        // User-targeted notification
        $isRead = (bool)$r['is_read'];
    } else {
        // Broadcast notification — check notification_reads
        $isRead = !empty($r['read_id']);
    }

    $notifications[] = [
        'id'                => (int)$r['id'],
        'title'             => $r['title'],
        'message'           => $r['message'],
        'type'              => $r['type'] ?? 'info',
        'link'              => $r['link'],
        'notification_type' => $r['notification_type'],
        'deep_link_route'   => $r['deep_link_route'],
        'is_read'           => $isRead ? 1 : 0,
        'created_at'        => gmdate('Y-m-d H:i:s', strtotime($r['created_at'])),
    ];
}

// Count total unread
$userUnread = Database::fetchOne(
    "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0",
    [$userId]
);
$broadcastUnread = Database::fetchOne(
    "SELECT COUNT(*) as cnt FROM notifications n 
     WHERE n.user_id IS NULL AND n.target_role IN (?, 'all')
     AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?)",
    [$role, $userId]
);
$unread = (int)($userUnread['cnt'] ?? 0) + (int)($broadcastUnread['cnt'] ?? 0);

echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'unread'        => $unread,
    'page'          => $page,
    'per_page'      => $perPage,
    'has_more'      => $hasMore,
]);
