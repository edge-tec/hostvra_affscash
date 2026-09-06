<?php
/**
 * Admin → Manager messaging inbox.
 *
 * Threaded inbox keyed by manager_id. Admin can read every thread and reply
 * to any manager. Stores rows in manager_messages (created on first use).
 * Read state is tracked separately for admin and manager so unread badges
 * work on both sides without trampling each other.
 *
 * Attachments stay simple: single file upload per message saved under
 * /assets/uploads/manager_messages/, validated by MIME + size. No avatars,
 * no rich text — just plain-text body + optional attachment.
 */
Auth::check('admin');
ManagerPermissions::ensureSchema();
$pageTitle = 'Manager Messages';

$action     = Helpers::get('action') ?: 'index';
$activeMgr  = (int)Helpers::get('manager_id');

// ── Compose / send a reply ───────────────────────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'send' && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $targetMgr = (int)Helpers::postRaw('manager_id');
    $body      = trim((string)Helpers::postRaw('body'));
    $attachPath = null;
    $attachName = null;

    if ($targetMgr > 0 && $body !== '') {
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
                 VALUES (?, 'admin', ?, ?, ?, ?, 1, 0)",
                [$targetMgr, (int)Auth::id(), $body, $attachPath, $attachName]
            );
            // Activity log on the manager side so admin actions are visible too.
            ManagerPermissions::logActivity($targetMgr, 'message_received', 'admin', (string)(Auth::id() ?? 0), substr($body, 0, 200));
            Helpers::flash('success', 'Message sent.');
        } catch (\Throwable $e) {
            Helpers::flash('error', 'Could not send message.');
        }
    }
    Helpers::redirect('/admin/affiliate-managers/messages?manager_id=' . $targetMgr);
}

// Mark current thread as read by admin when opening it.
if ($activeMgr > 0) {
    try {
        Database::query(
            "UPDATE manager_messages SET read_by_admin = 1
             WHERE manager_id = ? AND sender_role = 'manager' AND read_by_admin = 0",
            [$activeMgr]
        );
    } catch (\Throwable $_) {}
}

// ── Thread list (sidebar) + selected thread (main panel) ─────────────────────
try {
    $threads = Database::fetchAll(
        "SELECT am.id AS manager_id,
                CONCAT(u.first_name,' ',u.last_name) AS name,
                u.email,
                (SELECT body FROM manager_messages mm
                  WHERE mm.manager_id = am.id ORDER BY mm.created_at DESC LIMIT 1) AS last_body,
                (SELECT created_at FROM manager_messages mm
                  WHERE mm.manager_id = am.id ORDER BY mm.created_at DESC LIMIT 1) AS last_at,
                (SELECT COUNT(*) FROM manager_messages mm
                  WHERE mm.manager_id = am.id AND mm.sender_role = 'manager' AND mm.read_by_admin = 0) AS unread
         FROM affiliate_managers am
         JOIN users u ON u.id = am.user_id
         WHERE u.status <> 'deleted'
         ORDER BY (last_at IS NULL), last_at DESC, name ASC"
    ) ?: [];
} catch (\Throwable $_) { $threads = []; }

$messages = [];
$activeMgrMeta = null;
if ($activeMgr > 0) {
    try {
        $activeMgrMeta = Database::fetchOne(
            "SELECT am.id, u.email, CONCAT(u.first_name,' ',u.last_name) AS name
             FROM affiliate_managers am
             JOIN users u ON u.id = am.user_id
             WHERE am.id = ? LIMIT 1",
            [$activeMgr]
        );
        $messages = Database::fetchAll(
            "SELECT * FROM manager_messages WHERE manager_id = ? ORDER BY created_at ASC LIMIT 500",
            [$activeMgr]
        ) ?: [];
    } catch (\Throwable $_) {}
}

require BASE_PATH . '/views/admin/affiliate_managers/messages.php';
