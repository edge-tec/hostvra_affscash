<?php
header('Content-Type: application/json');
if (!Auth::id()) { echo json_encode(['error'=>'Unauthorized']); exit; }

// ensure is_read column exists
try { Database::query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE notifications ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP"); } catch(Exception $e) {}

// Heartbeat — track when each user last hit the API so the live-chat widget can
// show "Support Online" / "Support Offline" based on admin presence. The poller
// in every admin layout calls /api/notifications once a minute, so admin
// last_active_at stays fresh while they have a tab open.
try { Database::query("ALTER TABLE users ADD COLUMN last_active_at DATETIME DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("UPDATE users SET last_active_at = NOW() WHERE id = ?", [Auth::id()]); } catch (\Throwable $_e) {}

$action = Helpers::get('action') ?: Helpers::postRaw('action');
$userId = Auth::id();
$role   = Auth::role();

if ($action === 'mark_read') {
    $id = (int)Helpers::postRaw('id');
    // Only mark user-specific notifications as read to prevent clearing broadcast notifications for everyone.
    if ($id) Database::query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?", [$id, $userId]);
    else Database::query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$userId]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'unread_count') {
    $unread = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM notifications WHERE (user_id=? OR (user_id IS NULL AND target_role=?)) AND is_read=0",
        [$userId, $role]
    );
    echo json_encode(['unread' => (int)($unread['cnt'] ?? 0)]);
    exit;
}

// default: get recent notifications
$rows = Database::fetchAll(
    "SELECT * FROM notifications WHERE (user_id=? OR (user_id IS NULL AND target_role=?)) ORDER BY created_at DESC LIMIT 20",
    [$userId, $role]
);
$unread = 0;
foreach ($rows as $r) { if (!$r['is_read']) $unread++; }
echo json_encode(['notifications'=>$rows, 'unread'=>$unread]);
