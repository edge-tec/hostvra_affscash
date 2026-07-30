<?php
header('Content-Type: application/json');
$action = Helpers::get('action') ?: Helpers::postRaw('action');

// ─── Idempotent schema for the chat system ───────────────────────────────
// Original messages table.
try { Database::query("CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aff (affiliate_id),
    INDEX idx_created (created_at)
)");
$tableInfo = Database::fetchOne("SHOW CREATE TABLE support_messages");
if (isset($tableInfo['Create Table']) && strpos($tableInfo['Create Table'], 'utf8mb4') === false) {
    Database::query("ALTER TABLE support_messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
} catch(Exception $e) {}

// New columns: conversation grouping, edits, attachments, soft delete.
try { Database::query("ALTER TABLE support_messages ADD COLUMN conversation_id INT UNSIGNED NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN edited_at DATETIME NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN attachment_path VARCHAR(500) NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN attachment_name VARCHAR(255) NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN attachment_type VARCHAR(50) NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN attachment_size INT NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN read_at DATETIME NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD COLUMN delivered_at DATETIME NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD INDEX idx_conv (conversation_id)"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD INDEX idx_read_status (affiliate_id, owner_type, is_read, sender_role)"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD INDEX idx_conv_read (conversation_id, is_read)"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD INDEX idx_read_at (read_at)"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_messages ADD INDEX idx_delivered_at (delivered_at)"); } catch(\Throwable $e) {}
// `owner_type` lets the same `affiliate_id` column store either an affiliate
// or an advertiser id. Defaults to 'affiliate' so every legacy row remains valid.
try { Database::query("ALTER TABLE support_messages      ADD COLUMN owner_type VARCHAR(20) NOT NULL DEFAULT 'affiliate'"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE support_conversations ADD COLUMN owner_type VARCHAR(20) NOT NULL DEFAULT 'affiliate'"); } catch(\Throwable $e) {}

// Conversations (tickets) — created automatically on first message; closed by
// admin when resolved. A new affiliate message after closure spawns a new row
// so old tickets stay archived intact.
try { Database::query("CREATE TABLE IF NOT EXISTS support_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    subject VARCHAR(255) NULL,
    closed_by_user_id INT UNSIGNED NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME NULL,
    INDEX idx_aff_status (affiliate_id, status),
    INDEX idx_last (last_message_at)
)"); } catch(\Throwable $e) {}

// One-time backfill: every affiliate with messages but no conversation row gets
// one default OPEN conversation, and all their orphan messages get attached.
try {
    $orphanAffiliates = Database::fetchAll(
        "SELECT DISTINCT sm.affiliate_id
         FROM support_messages sm
         LEFT JOIN support_conversations sc ON sc.affiliate_id = sm.affiliate_id
         WHERE sm.conversation_id IS NULL
           AND sc.id IS NULL"
    );
    foreach ($orphanAffiliates as $oa) {
        $newConvId = Database::insert('support_conversations', [
            'affiliate_id'    => (int)$oa['affiliate_id'],
            'status'          => 'open',
            'created_at'      => date('Y-m-d H:i:s'),
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
        Database::query(
            "UPDATE support_messages SET conversation_id=? WHERE affiliate_id=? AND conversation_id IS NULL",
            [$newConvId, (int)$oa['affiliate_id']]
        );
    }
    // Affiliates who already had a conversation row but stranded older messages
    // get those linked to their latest conversation.
    Database::query(
        "UPDATE support_messages sm
         JOIN (
             SELECT affiliate_id, MAX(id) AS last_conv
             FROM support_conversations GROUP BY affiliate_id
         ) sc ON sc.affiliate_id = sm.affiliate_id
         SET sm.conversation_id = sc.last_conv
         WHERE sm.conversation_id IS NULL"
    );
} catch (\Throwable $e) {}

if (!Auth::id()) { echo json_encode(['error'=>'Unauthorized']); exit; }
$role = Auth::role();

// ─── Helpers ─────────────────────────────────────────────────────────────

/** Whitelist of attachment mime types + their canonical extensions. */
function chat_allowed_attachments(): array {
    return [
        'image/jpeg'        => 'jpg',
        'image/jpg'         => 'jpg',
        'image/png'         => 'png',
        'image/webp'        => 'webp',
        'application/pdf'   => 'pdf',
        'text/csv'          => 'csv',
        'application/csv'   => 'csv',
        // Some browsers label CSV as Excel; allow only when the filename extension is .csv (validated below).
        'application/vnd.ms-excel' => 'csv',
        'text/plain'        => 'csv',
    ];
}

/** Validate role can act on a given affiliate_id (used by every authenticated action). */
function chat_can_access_affiliate(int $affId, string $role): bool {
    if ($affId <= 0) return false;
    if ($role === 'admin') return true;
    if ($role === 'affiliate') {
        return (int)Auth::affiliateId() === $affId;
    }
    if ($role === 'affiliate_manager') {
        $ids = Auth::managerAffiliateIds();
        return in_array($affId, array_map('intval', $ids), true);
    }
    return false;
}

/**
 * Resolve the "chat owner" for the current session.
 * Returns [ownerId, ownerType] — ownerType is 'affiliate' or 'advertiser'.
 * `affiliate_id` is the column name in the DB even when the row belongs to an
 * advertiser; we partition rows by `owner_type` to keep them separate.
 */
function chat_owner_for_role(string $role): array {
    if ($role === 'advertiser') {
        return [(int)Auth::advertiserId(), 'advertiser'];
    }
    if ($role === 'affiliate') {
        return [(int)Auth::affiliateId(), 'affiliate'];
    }
    // admin / manager don't have a personal owner — they act on behalf of others.
    return [0, 'affiliate'];
}

/** Gate an action on an (ownerId, ownerType) pair. */
function chat_can_access_owner(int $ownerId, string $ownerType, string $role): bool {
    if ($ownerId <= 0) return false;
    if ($role === 'admin') return true;
    if ($ownerType === 'affiliate') {
        if ($role === 'affiliate') return (int)Auth::affiliateId() === $ownerId;
        if ($role === 'affiliate_manager') {
            $ids = Auth::managerAffiliateIds();
            return in_array($ownerId, array_map('intval', $ids), true);
        }
    } elseif ($ownerType === 'advertiser') {
        if ($role === 'advertiser') return (int)Auth::advertiserId() === $ownerId;
    }
    return false;
}

/** Get the currently-open conversation for an owner, optionally creating one. */
function chat_get_open_conversation(int $ownerId, bool $createIfMissing = true, string $ownerType = 'affiliate'): ?int {
    if ($ownerId <= 0) return null;
    try {
        $row = Database::fetchOne(
            "SELECT id FROM support_conversations
             WHERE affiliate_id=? AND owner_type=? AND status='open'
             ORDER BY id DESC LIMIT 1",
            [$ownerId, $ownerType]
        );
        if ($row) return (int)$row['id'];
        if (!$createIfMissing) return null;
        $newId = Database::insert('support_conversations', [
            'affiliate_id'    => $ownerId,
            'owner_type'      => $ownerType,
            'status'          => 'open',
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$newId;
    } catch (\Throwable $e) { return null; }
}

/** Bump the conversation's last_message_at — used after each message. */
function chat_touch_conversation(int $convId): void {
    try {
        Database::update('support_conversations',
            ['last_message_at' => date('Y-m-d H:i:s')],
            'id=?', [$convId]
        );
    } catch (\Throwable $e) {}
}

// ─── ACTION: send ────────────────────────────────────────────────────────
if ($action === 'send') {
    $msg = trim(Helpers::postRaw('message') ?? '');
    // CSRF for all mutating actions
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    if ($msg === '' && empty(Helpers::postRaw('attachment_id'))) {
        echo json_encode(['error' => 'Empty message']); exit;
    }

    // Optional attached file (uploaded separately via action=upload, then referenced here).
    // The upload action creates a "stub" row with is_deleted=1 + attachment_path set;
    // we look it up here in that exact state to inherit its file metadata.
    $attachmentId = (int)Helpers::postRaw('attachment_id');
    $attachment = null;
    if ($attachmentId > 0) {
        try {
            $attachment = Database::fetchOne(
                "SELECT * FROM support_messages WHERE id=? AND is_deleted=1 AND attachment_path IS NOT NULL",
                [$attachmentId]
            );
        } catch (\Throwable $e) {}
        // Only allow re-using if the stub belongs to the current sender.
        if (!$attachment || (int)$attachment['sender_id'] !== (int)Auth::id()) $attachment = null;
    }

    // Resolve owner context.
    if ($role === 'affiliate' || $role === 'advertiser') {
        [$affId, $ownerType] = chat_owner_for_role($role);
        if (!$affId) { echo json_encode(['error' => 'No account context']); exit; }
    } else {
        $affId      = (int)Helpers::postRaw('affiliate_id');
        $ownerType  = Helpers::postRaw('owner_type') === 'advertiser' ? 'advertiser' : 'affiliate';
        if (!chat_can_access_owner($affId, $ownerType, $role)) {
            echo json_encode(['error' => 'Forbidden']); exit;
        }
    }

    // Pick conversation: open existing, else create. This is also the path that
    // gives users a brand-new ticket after their last conversation closed.
    $convId = chat_get_open_conversation($affId, true, $ownerType);

    try {
        Database::insert('support_messages', [
            'affiliate_id'    => $affId,
            'owner_type'      => $ownerType,
            'sender_id'       => Auth::id(),
            'sender_role'     => $role === 'affiliate' ? 'affiliate' : ($role === 'advertiser' ? 'advertiser' : $role),
            'message'         => $msg,
            'conversation_id' => $convId,
            'attachment_path' => $attachment['attachment_path'] ?? null,
            'attachment_name' => $attachment['attachment_name'] ?? null,
            'attachment_type' => $attachment['attachment_type'] ?? null,
            'attachment_size' => $attachment['attachment_size'] ?? null,
        ]);
        // If the user combined an upload with a text message, remove the temp stub.
        if ($attachment) {
            Database::query("DELETE FROM support_messages WHERE id=?", [(int)$attachment['id']]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to save message']); exit;
    }

    chat_touch_conversation($convId);

    // Notifications — preserved behaviour, but now linked to the affiliate page + FCM Push.
    try {
        require_once BASE_PATH . '/core/NotificationHelper.php';
        $me = Auth::currentUser();
        if ($role === 'affiliate') {
            $mgr = Database::fetchOne("SELECT am.user_id FROM affiliate_manager_affiliates ama JOIN affiliate_managers am ON am.id=ama.manager_id WHERE ama.affiliate_id=? LIMIT 1", [$affId]);
            if (!$mgr) $mgr = Database::fetchOne("SELECT am.user_id FROM affiliates af JOIN affiliate_managers am ON am.id=af.manager_id WHERE af.id=? LIMIT 1", [$affId]);
            $notifUserId = $mgr['user_id'] ?? null;
            
            $msgPreview = ($me['first_name'] ?? 'Affiliate') . ': ' . mb_substr($msg !== '' ? $msg : ($attachment['attachment_name'] ?? 'attachment'), 0, 60);
            
            if ($notifUserId) {
                NotificationHelper::notifyUser($notifUserId, 'New Support Message', $msgPreview, 'info', '/admin/support?aff=' . $affId, ['type' => 'chat']);
            } else {
                NotificationHelper::notifyRole('admin', 'New Support Message', $msgPreview, 'info', '/admin/support?aff=' . $affId, ['type' => 'chat']);
            }
        } elseif ($role === 'advertiser') {
            // Advertiser-to-admin support ticket — always goes to admin queue.
            $msgPreview = ($me['first_name'] ?? 'Advertiser') . ': ' . mb_substr($msg !== '' ? $msg : ($attachment['attachment_name'] ?? 'attachment'), 0, 60);
            NotificationHelper::notifyRole('admin', 'New Advertiser Support Message', $msgPreview, 'info', '/admin/support?owner_type=advertiser&aff=' . $affId, ['type' => 'chat']);
        } else {
            // Admin / manager → owner. Resolve the owner's user_id based on owner_type.
            $ownerUser = null;
            if ($ownerType === 'advertiser') {
                $ownerUser = Database::fetchOne("SELECT user_id FROM advertisers WHERE id=?", [$affId]);
                $link = '/advertiser/dashboard';
            } else {
                $ownerUser = Database::fetchOne("SELECT user_id FROM affiliates  WHERE id=?", [$affId]);
                $link = '/affiliate/support';
            }
            if ($ownerUser) {
                $msgPreview = ($me['first_name'] ?? 'Support') . ': ' . mb_substr($msg !== '' ? $msg : ($attachment['attachment_name'] ?? 'attachment'), 0, 60);
                NotificationHelper::notifyUser((int)$ownerUser['user_id'], 'New Message from Support', $msgPreview, 'info', $link, ['type' => 'chat']);
            }
        }
    } catch (Exception $e) {}

    echo json_encode(['ok' => true, 'conversation_id' => $convId]);
    exit;
}

// ─── ACTION: upload ──────────────────────────────────────────────────────
// Two-step: client uploads the file (gets an attachment_id back), then calls
// `send` with attachment_id (and optional caption). Keeps the message row's
// authorship and the file metadata in a single table.
if ($action === 'upload') {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
        echo json_encode(['error' => 'No file uploaded.']); exit;
    }

    $file       = $_FILES['file'];
    $maxBytes   = 10 * 1024 * 1024;          // 10 MB cap
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Upload failed (code ' . (int)$file['error'] . ').']); exit;
    }
    if ((int)$file['size'] > $maxBytes) {
        echo json_encode(['error' => 'File is larger than 10 MB.']); exit;
    }

    // Validate the mime type via finfo (DON'T trust the client-supplied value).
    $detected = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $allowed  = chat_allowed_attachments();
    if (!isset($allowed[$detected])) {
        echo json_encode(['error' => 'File type not allowed. Use JPG, PNG, WEBP, PDF or CSV.']); exit;
    }
    // Extension must also match the canonical for the mime — defends against double-extension tricks.
    $rawName   = (string)($file['name'] ?? 'file');
    $rawExt    = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
    $canonExt  = $allowed[$detected];
    $okExts    = ($canonExt === 'jpg') ? ['jpg','jpeg'] : [$canonExt];
    if (!in_array($rawExt, $okExts, true)) {
        echo json_encode(['error' => 'Filename extension does not match the file type.']); exit;
    }

    // Per-owner folder. End users can only upload to their own folder.
    if ($role === 'affiliate' || $role === 'advertiser') {
        [$affId, $ownerType] = chat_owner_for_role($role);
    } else {
        $affId      = (int)Helpers::postRaw('affiliate_id');
        $ownerType  = Helpers::postRaw('owner_type') === 'advertiser' ? 'advertiser' : 'affiliate';
    }
    if (!chat_can_access_owner($affId, $ownerType, $role)) {
        echo json_encode(['error' => 'Forbidden']); exit;
    }

    // Owner-prefixed folder (e.g. `support/advertiser-7/`) so paths can't collide
    // between affiliate#7 and advertiser#7.
    $folder = ($ownerType === 'advertiser') ? ('advertiser-' . $affId) : ((string)$affId);
    $dir = BASE_PATH . '/uploads/support/' . $folder;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($dir . '/index.html', '');
    $stored = bin2hex(random_bytes(16)) . '.' . $canonExt;
    $dest   = $dir . '/' . $stored;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['error' => 'Could not store the file.']); exit;
    }
    @chmod($dest, 0644);

    // Path stored in DB is RELATIVE to BASE_PATH/uploads/support — never user-controlled.
    $relPath = $folder . '/' . $stored;
    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $rawName);

    // Create a stub message row holding only the attachment. `send` consumes it.
    try {
        $msgId = Database::insert('support_messages', [
            'affiliate_id'    => $affId,
            'owner_type'      => $ownerType,
            'sender_id'       => Auth::id(),
            'sender_role'     => $role === 'affiliate' ? 'affiliate' : ($role === 'advertiser' ? 'advertiser' : $role),
            'message'         => '',
            'conversation_id' => null,                       // attached on send
            'attachment_path' => $relPath,
            'attachment_name' => mb_substr($safeName, 0, 255),
            'attachment_type' => $detected,
            'attachment_size' => (int)$file['size'],
            'is_deleted'      => 1,                          // hidden until linked
        ]);
    } catch (\Throwable $e) {
        @unlink($dest);
        echo json_encode(['error' => 'DB error storing attachment.']); exit;
    }

    echo json_encode([
        'ok'              => true,
        'attachment_id'   => (int)$msgId,
        'attachment_name' => $safeName,
        'attachment_size' => (int)$file['size'],
        'attachment_type' => $detected,
    ]);
    exit;
}

// ─── ACTION: download — serves an attachment with auth check ─────────────
if ($action === 'download') {
    $msgId = (int)Helpers::get('id');
    $row = Database::fetchOne("SELECT * FROM support_messages WHERE id=? AND attachment_path IS NOT NULL", [$msgId]);
    if (!$row) { http_response_code(404); header('Content-Type: text/plain'); echo 'Not found'; exit; }
    if (!chat_can_access_owner((int)$row['affiliate_id'], (string)($row['owner_type'] ?? 'affiliate'), $role)) {
        http_response_code(403); header('Content-Type: text/plain'); echo 'Forbidden'; exit;
    }
    $abs = BASE_PATH . '/uploads/support/' . $row['attachment_path'];
    if (!is_file($abs)) { http_response_code(404); header('Content-Type: text/plain'); echo 'File missing'; exit; }
    // Force inline for images so they can preview; PDF inline too; CSV forced download.
    $type   = (string)($row['attachment_type'] ?? 'application/octet-stream');
    $name   = (string)($row['attachment_name'] ?? 'file');
    $inline = (strpos($type, 'image/') === 0 || $type === 'application/pdf');
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($abs));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes(basename($name)) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');
    readfile($abs);
    exit;
}

// ─── ACTION: messages — fetch conversation messages ──────────────────────
if ($action === 'messages') {
    // Resolve owner. Affiliates/advertisers always look at THEIR own context;
    // admin/manager pass affiliate_id (+ optional owner_type) explicitly.
    if ($role === 'affiliate' || $role === 'advertiser') {
        [$affId, $ownerType] = chat_owner_for_role($role);
    } else {
        $affId      = (int)Helpers::get('affiliate_id');
        $ownerType  = Helpers::get('owner_type') === 'advertiser' ? 'advertiser' : 'affiliate';
    }
    if (!chat_can_access_owner($affId, $ownerType, $role)) {
        echo json_encode(['messages'=>[], 'error'=>'Forbidden']); exit;
    }

    // Optional: filter by a specific conversation (admin browsing history).
    $convFilter = (int)Helpers::get('conversation_id');
    $where  = "sm.affiliate_id=? AND sm.owner_type=? AND sm.is_deleted=0";
    $params = [$affId, $ownerType];
    if ($convFilter > 0) { $where .= " AND sm.conversation_id=?"; $params[] = $convFilter; }
    else if ($role === 'affiliate' || $role === 'advertiser') {
        // End users only ever see the currently-open conversation. Closed
        // tickets are archived and not surfaced in their inbox.
        $openId = chat_get_open_conversation($affId, false, $ownerType);
        if ($openId) { $where .= " AND sm.conversation_id=?"; $params[] = $openId; }
        else        { echo json_encode(['messages'=>[], 'status'=>'no_open']); exit; }
    }

    $since = (int)Helpers::get('since');
    if ($since) { $where .= " AND sm.id > ?"; $params[] = $since; }

    $rows = Database::fetchAll(
        "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read, sm.read_at, sm.delivered_at,
                sm.edited_at, sm.conversation_id,
                sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                CONCAT(u.first_name,' ',u.last_name) as sender_name
         FROM support_messages sm JOIN users u ON u.id=sm.sender_id
         WHERE $where
         ORDER BY sm.created_at ASC LIMIT 200",
        $params
    );

    // 1) Mark unread incoming messages as delivered if not already set.
    // 2) Mark unread incoming messages as read with exact read_at timestamp.
    try {
        if ($role === 'affiliate' || $role === 'advertiser') {
            // End user — deliver and read messages sent by admin/manager.
            Database::query(
                "UPDATE support_messages SET delivered_at = IFNULL(delivered_at, NOW())
                 WHERE affiliate_id=? AND owner_type=? AND sender_role NOT IN ('affiliate','advertiser') AND delivered_at IS NULL",
                [$affId, $ownerType]
            );
            Database::query(
                "UPDATE support_messages SET is_read=1, read_at = IFNULL(read_at, NOW()), delivered_at = IFNULL(delivered_at, NOW())
                 WHERE affiliate_id=? AND owner_type=? AND sender_role NOT IN ('affiliate','advertiser') AND is_read=0",
                [$affId, $ownerType]
            );
        } else {
            // Admin/manager — deliver and read messages sent by affiliate/advertiser.
            Database::query(
                "UPDATE support_messages SET delivered_at = IFNULL(delivered_at, NOW())
                 WHERE affiliate_id=? AND owner_type=? AND sender_role IN ('affiliate','advertiser') AND delivered_at IS NULL",
                [$affId, $ownerType]
            );
            Database::query(
                "UPDATE support_messages SET is_read=1, read_at = IFNULL(read_at, NOW()), delivered_at = IFNULL(delivered_at, NOW())
                 WHERE affiliate_id=? AND owner_type=? AND sender_role IN ('affiliate','advertiser') AND is_read=0",
                [$affId, $ownerType]
            );
        }
    } catch (\Throwable $e) {}

    // Process and enrich rows with read status, delivered_at, read_at and formatted timestamps.
    $processedRows = [];
    foreach ($rows as $r) {
        $isRead = (int)$r['is_read'];
        $readAt = $r['read_at'];
        $deliveredAt = $r['delivered_at'];
        
        // Determine live status: 'read', 'delivered', or 'sent'
        $status = 'sent';
        if ($isRead === 1) {
            $status = 'read';
        } elseif ($deliveredAt !== null) {
            $status = 'delivered';
        }

        $formattedReadAt = null;
        if ($readAt) {
            $formattedReadAt = date('g:i A', strtotime($readAt));
        }

        $r['is_read'] = $isRead;
        $r['read_at'] = $readAt;
        $r['delivered_at'] = $deliveredAt;
        $r['status'] = $status;
        $r['formatted_read_at'] = $formattedReadAt;
        $processedRows[] = $r;
    }

    // Resolve current conversation status so the UI can render the badge.
    $convInfo = null;
    try {
        if ($convFilter > 0) {
            $convInfo = Database::fetchOne("SELECT id, status, closed_at FROM support_conversations WHERE id=? AND affiliate_id=? AND owner_type=?", [$convFilter, $affId, $ownerType]);
        } else {
            $convInfo = Database::fetchOne("SELECT id, status, closed_at FROM support_conversations WHERE affiliate_id=? AND owner_type=? ORDER BY id DESC LIMIT 1", [$affId, $ownerType]);
        }
    } catch (\Throwable $e) {}

    echo json_encode([
        'messages'     => $processedRows,
        'conversation' => $convInfo,
        'my_user_id'   => (int)Auth::id(),
    ]);
    exit;
}

// ─── ACTION: mark_read — batch mark unread messages as read ───────────────
if ($action === 'mark_read') {
    if ($role === 'affiliate' || $role === 'advertiser') {
        [$affId, $ownerType] = chat_owner_for_role($role);
    } else {
        $affId      = (int)(Helpers::get('affiliate_id') ?: Helpers::postRaw('affiliate_id'));
        $ownerType  = (Helpers::get('owner_type') ?: Helpers::postRaw('owner_type')) === 'advertiser' ? 'advertiser' : 'affiliate';
    }
    if (!chat_can_access_owner($affId, $ownerType, $role)) {
        echo json_encode(['error' => 'Forbidden']); exit;
    }
    $convId = (int)(Helpers::get('conversation_id') ?: Helpers::postRaw('conversation_id'));

    $markedCount = 0;
    try {
        $where  = "affiliate_id=? AND owner_type=? AND is_read=0";
        $params = [$affId, $ownerType];
        if ($role === 'affiliate' || $role === 'advertiser') {
            $where .= " AND sender_role NOT IN ('affiliate','advertiser')";
        } else {
            $where .= " AND sender_role IN ('affiliate','advertiser')";
        }
        if ($convId > 0) {
            $where .= " AND conversation_id=?";
            $params[] = $convId;
        }
        $markedCount = Database::query(
            "UPDATE support_messages SET is_read=1, read_at=IFNULL(read_at, NOW()), delivered_at=IFNULL(delivered_at, NOW()) WHERE $where",
            $params
        );
    } catch (\Throwable $e) {}

    echo json_encode(['ok' => true, 'marked_count' => (int)$markedCount]);
    exit;
}

// ─── ACTION: edit a message ──────────────────────────────────────────────
if ($action === 'edit_message') {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    $msgId  = (int)Helpers::postRaw('message_id');
    $newTxt = trim((string)Helpers::postRaw('message'));
    if ($msgId <= 0 || $newTxt === '') { echo json_encode(['error' => 'Missing fields.']); exit; }

    $row = Database::fetchOne("SELECT * FROM support_messages WHERE id=? AND is_deleted=0", [$msgId]);
    if (!$row) { echo json_encode(['error' => 'Message not found.']); exit; }

    // Allowed: the original sender, or an admin. No 5-minute grace either — the
    // affiliate has a clear "(edited)" badge so revisions stay accountable.
    $isOwner = ((int)$row['sender_id'] === (int)Auth::id());
    if (!$isOwner && $role !== 'admin') {
        echo json_encode(['error' => 'Forbidden']); exit;
    }
    // Attachments are not editable as text — only the caption portion is.
    Database::update('support_messages', [
        'message'   => mb_substr($newTxt, 0, 4000),
        'edited_at' => date('Y-m-d H:i:s'),
    ], 'id=?', [$msgId]);

    echo json_encode(['ok' => true]);
    exit;
}

// ─── ACTION: delete a message (admin, manager, affiliate) ───────────────────────────────
if ($action === 'delete_message') {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    $msgId = (int)Helpers::postRaw('message_id');
    if ($msgId <= 0) { echo json_encode(['error' => 'Missing id.']); exit; }

    $row = Database::fetchOne("SELECT * FROM support_messages WHERE id=?", [$msgId]);
    if (!$row) { echo json_encode(['error' => 'Not found.']); exit; }

    // Users can only delete their own messages. Admin can delete any.
    if ($role !== 'admin' && (int)$row['sender_id'] !== (int)Auth::id()) {
        echo json_encode(['error' => 'Forbidden']); exit;
    }

    // Soft-delete so audit history remains intact. The attachment file is also
    // removed from disk to free space.
    Database::update('support_messages', ['is_deleted' => 1], 'id=?', [$msgId]);
    if (!empty($row['attachment_path'])) {
        @unlink(BASE_PATH . '/uploads/support/' . $row['attachment_path']);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ─── ACTION: clear conversation history (admin only) ─────────────────────
if ($action === 'clear_history') {
    if ($role !== 'admin') { echo json_encode(['error' => 'Forbidden']); exit; }
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    $convId = (int)Helpers::postRaw('conversation_id');
    if ($convId <= 0) { echo json_encode(['error' => 'Missing conversation_id.']); exit; }
    // Soft-delete every message in this conversation. Files are removed from disk.
    try {
        $files = Database::fetchAll("SELECT attachment_path FROM support_messages WHERE conversation_id=? AND attachment_path IS NOT NULL", [$convId]);
        foreach ($files as $f) {
            if (!empty($f['attachment_path'])) @unlink(BASE_PATH . '/uploads/support/' . $f['attachment_path']);
        }
        Database::query("UPDATE support_messages SET is_deleted=1 WHERE conversation_id=?", [$convId]);
    } catch (\Throwable $e) {
        echo json_encode(['error' => 'Failed to clear history.']); exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ─── ACTION: close / reopen a conversation (admin only) ──────────────────
if ($action === 'close_conversation' || $action === 'reopen_conversation') {
    if ($role !== 'admin') { echo json_encode(['error' => 'Forbidden']); exit; }
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid form submission.']); exit;
    }
    $convId = (int)Helpers::postRaw('conversation_id');
    if ($convId <= 0) { echo json_encode(['error' => 'Missing conversation_id.']); exit; }
    if ($action === 'close_conversation') {
        Database::update('support_conversations', [
            'status'            => 'closed',
            'closed_at'         => date('Y-m-d H:i:s'),
            'closed_by_user_id' => (int)Auth::id(),
        ], 'id=?', [$convId]);
    } else {
        Database::update('support_conversations', [
            'status'            => 'open',
            'closed_at'         => null,
            'closed_by_user_id' => null,
        ], 'id=?', [$convId]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ─── ACTION: conversations list (sidebar) ────────────────────────────────
if ($action === 'conversations') {
    // For admin/manager: list owners with their CURRENTLY-OPEN conversation
    // metadata. owner_type filter switches between affiliate ('affiliate', default)
    // and advertiser ('advertiser') buckets.
    $statusFilter = Helpers::get('status') ?: 'open';                // open|closed|all
    $ownerType    = Helpers::get('owner_type') === 'advertiser' ? 'advertiser' : 'affiliate';

    if ($role === 'affiliate_manager' && $ownerType === 'advertiser') {
        // Managers don't see advertiser conversations.
        echo json_encode(['conversations' => []]); exit;
    }

    $statusSql = '';
    if ($statusFilter === 'open')   $statusSql = "AND sc.status='open'";
    if ($statusFilter === 'closed') $statusSql = "AND sc.status='closed'";

    if ($ownerType === 'advertiser') {
        $rows = Database::fetchAll(
            "SELECT adv.id as affiliate_id, CONCAT(u.first_name,' ',u.last_name) as name, adv.advertiser_code as affiliate_code,
                    sc.id as conversation_id, sc.status, sc.closed_at, sc.last_message_at,
                    (SELECT message FROM support_messages WHERE conversation_id=sc.id AND is_deleted=0 ORDER BY created_at DESC LIMIT 1) as last_msg,
                    (SELECT COUNT(*) FROM support_messages WHERE conversation_id=sc.id AND sender_role='advertiser' AND is_read=0 AND is_deleted=0) as unread
             FROM advertisers adv
             JOIN users u ON u.id=adv.user_id
             LEFT JOIN support_conversations sc ON sc.id = (
                 SELECT id FROM support_conversations
                 WHERE affiliate_id=adv.id AND owner_type='advertiser' $statusSql
                 ORDER BY id DESC LIMIT 1
             )
             ORDER BY sc.last_message_at IS NULL ASC, sc.last_message_at DESC"
        );
    } else {
        if ($role === 'affiliate_manager') {
            $affIds = Auth::managerAffiliateIds();
            if (empty($affIds)) { echo json_encode(['conversations'=>[]]); exit; }
            $in = implode(',', array_fill(0, count($affIds), '?'));
            $aWhere = "af.id IN ($in)";
            $aParams = $affIds;
        } else {
            $aWhere = "1=1";
            $aParams = [];
        }
        $rows = Database::fetchAll(
            "SELECT af.id as affiliate_id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code,
                    sc.id as conversation_id, sc.status, sc.closed_at, sc.last_message_at,
                    (SELECT message FROM support_messages WHERE conversation_id=sc.id AND is_deleted=0 ORDER BY created_at DESC LIMIT 1) as last_msg,
                    (SELECT COUNT(*) FROM support_messages WHERE conversation_id=sc.id AND sender_role='affiliate' AND is_read=0 AND is_deleted=0) as unread
             FROM affiliates af
             JOIN users u ON u.id=af.user_id
             LEFT JOIN support_conversations sc ON sc.id = (
                 SELECT id FROM support_conversations
                 WHERE affiliate_id=af.id AND owner_type='affiliate' $statusSql
                 ORDER BY id DESC LIMIT 1
             )
             WHERE $aWhere
             ORDER BY sc.last_message_at IS NULL ASC, sc.last_message_at DESC",
            $aParams
        );
    }
    echo json_encode(['conversations' => $rows]);
    exit;
}

// ─── ACTION: full conversation history for one affiliate (admin) ─────────
if ($action === 'conversations_history') {
    $affId      = (int)Helpers::get('affiliate_id');
    $ownerType  = Helpers::get('owner_type') === 'advertiser' ? 'advertiser' : 'affiliate';
    if (!chat_can_access_owner($affId, $ownerType, $role)) {
        echo json_encode(['conversations' => []]); exit;
    }
    $rows = Database::fetchAll(
        "SELECT sc.id, sc.status, sc.closed_at, sc.created_at, sc.last_message_at,
                (SELECT COUNT(*) FROM support_messages WHERE conversation_id=sc.id AND is_deleted=0) as message_count
         FROM support_conversations sc
         WHERE sc.affiliate_id=? AND sc.owner_type=?
         ORDER BY sc.id DESC LIMIT 50",
        [$affId, $ownerType]
    );
    echo json_encode(['conversations' => $rows]);
    exit;
}

// ─── ACTION: unread count ────────────────────────────────────────────────
if ($action === 'unread_count') {
    if ($role === 'affiliate') {
        $affId = Auth::affiliateId();
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM support_messages WHERE affiliate_id=? AND owner_type='affiliate' AND sender_role!='affiliate' AND is_read=0 AND is_deleted=0", [$affId]);
    } elseif ($role === 'advertiser') {
        $advId = Auth::advertiserId();
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM support_messages WHERE affiliate_id=? AND owner_type='advertiser' AND sender_role!='advertiser' AND is_read=0 AND is_deleted=0", [$advId]);
    } elseif ($role === 'affiliate_manager') {
        $affIds = Auth::managerAffiliateIds();
        if (empty($affIds)) { echo json_encode(['count'=>0]); exit; }
        $in = implode(',', array_fill(0, count($affIds), '?'));
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM support_messages WHERE affiliate_id IN ($in) AND owner_type='affiliate' AND sender_role='affiliate' AND is_read=0 AND is_deleted=0", $affIds);
    } else {
        // Admin — both affiliate and advertiser sides count.
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM support_messages WHERE sender_role IN ('affiliate','advertiser') AND is_read=0 AND is_deleted=0");
    }
    echo json_encode(['count'=>(int)($count['cnt']??0)]);
    exit;
}

// ─── ACTION: support availability — admin online indicator ───────────────
if ($action === 'support_status') {
    try { Database::query("ALTER TABLE users ADD COLUMN last_active_at DATETIME DEFAULT NULL"); } catch (\Throwable $_e) {}
    $online = false;
    try {
        $row = Database::fetchOne(
            "SELECT id FROM users
             WHERE role = 'admin' AND status = 'active'
               AND last_active_at IS NOT NULL
               AND last_active_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
             LIMIT 1"
        );
        $online = !empty($row);
    } catch (\Throwable $e) {}
    echo json_encode(['online' => $online]);
    exit;
}

echo json_encode(['error'=>'Unknown action']);
