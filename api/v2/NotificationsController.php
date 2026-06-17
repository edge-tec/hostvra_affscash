<?php
Auth::check(); // Allow any logged in user
$userId = Auth::id();
$role   = Auth::role();
$action = Helpers::get('action', 'list');

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
