<?php
/**
 * Affiliate Manager → Admin direct messaging.
 *
 * Single-thread per manager (the manager only ever talks to "Admin", treated
 * as a collective inbox). Loaded under /affiliate_manager/admin-messages.
 * Read-state is per-side so admin and manager each get their own unread badge.
 */
Auth::check('affiliate_manager');
ManagerPermissions::ensureSchema();

$pageTitle = 'Messages with Admin';

$myMgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [(int)Auth::id()]);
$myMgrId  = (int)($myMgrRow['id'] ?? 0);
if ($myMgrId <= 0) { Helpers::redirect('/affiliate_manager/dashboard'); }

// Send a new message to admin.
if (Helpers::isPost() && Helpers::post('action') === 'send' && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $body = trim((string)Helpers::postRaw('body'));
    $attachPath = null;
    $attachName = null;
    if ($body !== '') {
        if (!empty($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $size = (int)$_FILES['attachment']['size'];
            $mimeMap = [
                'image/png'       => 'png',
                'image/jpeg'      => 'jpg',
                'image/gif'       => 'gif',
                'image/webp'      => 'webp',
                'application/pdf' => 'pdf',
                'text/plain'      => 'txt',
            ];
            if ($size <= 5 * 1024 * 1024 && isset($mimeMap[$mime])) {
                $dir = BASE_PATH . '/assets/uploads/manager_messages/';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $ext  = $mimeMap[$mime];
                $safe = 'msg_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (@move_uploaded_file($_FILES['attachment']['tmp_name'], $dir . $safe)) {
                    $attachPath = '/assets/uploads/manager_messages/' . $safe;
                    $attachName = (string)$_FILES['attachment']['name'];
                }
            }
        }
        try {
            Database::query(
                "INSERT INTO manager_messages
                     (manager_id, sender_role, sender_user_id, body, attachment_path, attachment_name, read_by_admin, read_by_manager)
                 VALUES (?, 'manager', ?, ?, ?, ?, 0, 1)",
                [$myMgrId, (int)Auth::id(), $body, $attachPath, $attachName]
            );
            ManagerPermissions::logActivity($myMgrId, 'message_sent', 'admin', null, substr($body, 0, 200));
        } catch (\Throwable $_) {}
    }
    Helpers::redirect('/affiliate_manager/admin-messages');
}

// Mark admin → manager messages as read on view.
try {
    Database::query(
        "UPDATE manager_messages SET read_by_manager = 1
         WHERE manager_id = ? AND sender_role = 'admin' AND read_by_manager = 0",
        [$myMgrId]
    );
} catch (\Throwable $_) {}

// Load the full conversation.
try {
    $messages = Database::fetchAll(
        "SELECT * FROM manager_messages WHERE manager_id = ? ORDER BY created_at ASC LIMIT 500",
        [$myMgrId]
    ) ?: [];
} catch (\Throwable $_) { $messages = []; }

require BASE_PATH . '/views/affiliate_manager/admin_messages.php';
