<?php
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden.']));
}
header('Content-Type: application/json');

try {
    Auth::check('admin');
    
    $action = $_GET['action'] ?? 'list';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? $_GET['action'] ?? 'send';
        if (isset($_POST['action'])) {
            $action = $_POST['action']; // for multipart form data
        }
    }

    $adminId = Auth::id();
    $role = 'admin';

    // ─── ACTION: conversations ───────────────────────────────────────────────
    if ($action === 'conversations' || $action === 'list') {
        $status = $_GET['status'] ?? 'open';
        $ownerType = $_GET['owner_type'] ?? 'affiliate'; // affiliate, advertiser, manager
        
        // Build the query to fetch conversations and their latest messages
        $sql = "
            SELECT c.id as conversation_id, c.affiliate_id, c.status, c.last_message_at, c.owner_type,
                   (SELECT COUNT(*) FROM support_messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_role = c.owner_type) as unread,
                   (SELECT message FROM support_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) as last_msg,
                   u.first_name, u.last_name, 
                   COALESCE(af.affiliate_code, u.company, u.email) as entity_code
            FROM support_conversations c
            LEFT JOIN affiliates af ON c.owner_type = 'affiliate' AND c.affiliate_id = af.id
            LEFT JOIN advertisers adv ON c.owner_type = 'advertiser' AND c.affiliate_id = adv.id
            LEFT JOIN affiliate_managers am ON c.owner_type = 'manager' AND c.affiliate_id = am.id
            LEFT JOIN users u ON u.id = COALESCE(af.user_id, adv.user_id, am.user_id)
            WHERE c.status = ? AND c.owner_type = ?
            ORDER BY c.last_message_at DESC
        ";
        
        $convs = Database::fetchAll($sql, [$status, $ownerType]);
        
        $result = [];
        foreach ($convs as $c) {
            $name = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            if (!$name) $name = 'Unknown';
            
            $result[] = [
                'conversation_id' => (int)$c['conversation_id'],
                'affiliate_id'    => (int)$c['affiliate_id'],
                'name'            => $name,
                'affiliate_code'  => $c['entity_code'] ?? '',
                'status'          => $c['status'],
                'last_message_at' => $c['last_message_at'],
                'unread'          => (int)$c['unread'],
                'last_msg'        => $c['last_msg'] ?: ''
            ];
        }

        echo json_encode(['success' => true, 'data' => ['conversations' => $result]]);
        exit;
    }

    // ─── ACTION: messages ────────────────────────────────────────────────────
    if ($action === 'messages') {
        $ownerId = (int)($_GET['affiliate_id'] ?? 0);
        $ownerType = $_GET['owner_type'] ?? 'affiliate';
        $convId = (int)($_GET['conversation_id'] ?? 0);
        $since = (int)($_GET['since'] ?? 0);

        if (!$ownerId) {
            echo json_encode(['success' => false, 'error' => 'Missing affiliate_id']); exit;
        }

        if (!$convId) {
            // Find the most recent open conversation, or create one if none exists
            $openConv = Database::fetchOne(
                "SELECT id, status FROM support_conversations WHERE affiliate_id=? AND owner_type=? AND status='open' ORDER BY id DESC LIMIT 1",
                [$ownerId, $ownerType]
            );
            if ($openConv) {
                $convId = (int)$openConv['id'];
                $status = $openConv['status'];
            } else {
                $status = 'open';
            }
        } else {
            $convRow = Database::fetchOne("SELECT status FROM support_conversations WHERE id=?", [$convId]);
            $status = $convRow['status'] ?? 'open';
        }

        $messages = [];
        if ($convId) {
            $sql = "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
                           sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                           CONCAT(u.first_name,' ',u.last_name) as sender_name, sm.edited_at
                    FROM support_messages sm 
                    LEFT JOIN users u ON u.id=sm.sender_id
                    WHERE sm.conversation_id=? AND sm.is_deleted=0 AND sm.id > ?
                    ORDER BY sm.created_at ASC LIMIT 500";
            $messages = Database::fetchAll($sql, [$convId, $since]);

            // Mark unread messages as read
            $updated = Database::query(
                "UPDATE support_messages SET is_read=1, read_by_admin=1
                 WHERE conversation_id=? AND sender_role = ? AND is_read=0",
                [$convId, $ownerType]
            );
            if ($updated > 0) {
                require_once __DIR__ . '/../../../core/BadgeSyncHelper.php';
                BadgeSyncHelper::emitReadSync(Auth::id());
            }
        }

        // Format for JSON
        $formattedMsgs = [];
        foreach ($messages as $m) {
            $formattedMsgs[] = [
                'id' => (int)$m['id'],
                'sender_id' => (int)$m['sender_id'],
                'sender_role' => $m['sender_role'],
                'sender_name' => $m['sender_name'] ?: 'System',
                'message' => $m['message'],
                'created_at' => $m['created_at'],
                'is_read' => (int)$m['is_read'],
                'attachment_path' => $m['attachment_path'],
                'attachment_name' => $m['attachment_name'],
                'attachment_type' => $m['attachment_type'],
                'attachment_size' => $m['attachment_size'] ? (int)$m['attachment_size'] : null,
                'edited_at' => $m['edited_at']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'conversation' => ['id' => $convId, 'status' => $status],
                'messages' => $formattedMsgs
            ]
        ]);
        exit;
    }

    // ─── ACTION: send ────────────────────────────────────────────────────────
    if ($action === 'send') {
        $ownerId = (int)($input['affiliate_id'] ?? $_POST['affiliate_id'] ?? 0);
        $ownerType = $input['owner_type'] ?? $_POST['owner_type'] ?? 'affiliate';
        $messageText = trim($input['message'] ?? $_POST['message'] ?? '');
        $attachmentId = (int)($input['attachment_id'] ?? $_POST['attachment_id'] ?? 0);

        if (!$ownerId) {
            echo json_encode(['success' => false, 'error' => 'Missing affiliate_id']); exit;
        }

        if (empty($messageText) && $attachmentId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Empty message']); exit;
        }

        // Find or create open conversation
        $openConv = Database::fetchOne(
            "SELECT id FROM support_conversations WHERE affiliate_id=? AND owner_type=? AND status='open' ORDER BY id DESC LIMIT 1",
            [$ownerId, $ownerType]
        );

        if ($openConv) {
            $convId = (int)$openConv['id'];
            Database::query("UPDATE support_conversations SET last_message_at=NOW() WHERE id=?", [$convId]);
        } else {
            $convId = Database::insert('support_conversations', [
                'affiliate_id' => $ownerId,
                'owner_type' => $ownerType,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'last_message_at' => date('Y-m-d H:i:s')
            ]);
        }

        $attachment = null;
        if ($attachmentId > 0) {
            $attachment = Database::fetchOne(
                "SELECT * FROM support_messages WHERE id=? AND is_deleted=1 AND attachment_path IS NOT NULL AND affiliate_id=?",
                [$attachmentId, $ownerId]
            );
        }

        if ($attachment) {
            Database::query(
                "UPDATE support_messages SET conversation_id=?, message=?, is_deleted=0, created_at=NOW() WHERE id=?",
                [$convId, $messageText, $attachmentId]
            );
            $msgId = $attachmentId;
        } else {
            $msgId = Database::insert('support_messages', [
                'conversation_id' => $convId,
                'affiliate_id' => $ownerId,
                'owner_type' => $ownerType,
                'sender_id' => $adminId,
                'sender_role' => 'admin',
                'message' => $messageText,
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0,
                'is_deleted' => 0
            ]);
        }

        // ── Notify the recipient about the admin's reply ──
        try {
            $preview = mb_substr($messageText !== '' ? $messageText : 'Sent an attachment', 0, 60);

            if ($ownerType === 'affiliate') {
                $user = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$ownerId]);
                if ($user && $user['user_id']) {
                    Database::insert('notifications', [
                        'user_id'     => (int)$user['user_id'],
                        'target_role' => 'affiliate',
                        'type'        => 'info',
                        'title'       => 'New Support Reply',
                        'message'     => 'Admin: ' . $preview,
                        'link'        => '/affiliate/support',
                        'is_read'     => 0,
                        'notification_type' => 'support_message',
                        'deep_link_route'   => 'chat'
                    ]);
                    if (file_exists(BASE_PATH . '/core/FirebaseMessaging.php')) {
                        require_once BASE_PATH . '/core/FirebaseMessaging.php';
                        FirebaseMessaging::sendToUser((int)$user['user_id'], 'New Support Reply', 'Admin: ' . $preview, ['type' => 'support']);
                    }
                }

                // Also notify the assigned manager
                $mgr = Database::fetchOne("SELECT am.user_id FROM affiliates a JOIN affiliate_managers am ON am.id=a.affiliate_manager_id WHERE a.id=?", [$ownerId]);
                if ($mgr && $mgr['user_id']) {
                    Database::insert('notifications', [
                        'user_id'     => (int)$mgr['user_id'],
                        'target_role' => 'affiliate_manager',
                        'type'        => 'info',
                        'title'       => 'Admin Replied to Affiliate',
                        'message'     => 'Admin: ' . $preview,
                        'link'        => '/manager/support',
                        'is_read'     => 0,
                        'notification_type' => 'support_message',
                        'deep_link_route'   => 'manager_support'
                    ]);
                }
            } elseif ($ownerType === 'manager') {
                $user = Database::fetchOne("SELECT user_id FROM affiliate_managers WHERE id=?", [$ownerId]);
                if ($user && $user['user_id']) {
                    Database::insert('notifications', [
                        'user_id'     => (int)$user['user_id'],
                        'target_role' => 'affiliate_manager',
                        'type'        => 'info',
                        'title'       => 'New Message from Admin',
                        'message'     => 'Admin: ' . $preview,
                        'link'        => '/manager/support',
                        'is_read'     => 0,
                        'notification_type' => 'support_message',
                        'deep_link_route'   => 'manager_support'
                    ]);
                    if (file_exists(BASE_PATH . '/core/FirebaseMessaging.php')) {
                        require_once BASE_PATH . '/core/FirebaseMessaging.php';
                        FirebaseMessaging::sendToUser((int)$user['user_id'], 'New Message from Admin', 'Admin: ' . $preview, ['type' => 'support']);
                    }
                }
            }
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'data' => ['message_id' => $msgId]]);
        exit;
    }

    // ─── ACTION: upload ──────────────────────────────────────────────────────
    if ($action === 'upload') {
        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
            throw new Exception("No file uploaded.");
        }

        $file = $_FILES['file'];
        $ownerId = (int)($_POST['affiliate_id'] ?? 0);
        $ownerType = $_POST['owner_type'] ?? 'affiliate';

        if (!$ownerId) {
            throw new Exception("Missing affiliate_id for upload.");
        }

        $maxBytes = 10 * 1024 * 1024; // 10 MB cap
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed (code " . (int)$file['error'] . ").");
        }
        if ((int)$file['size'] > $maxBytes) {
            throw new Exception("File is larger than 10 MB.");
        }

        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        $allowed = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
            'text/csv'        => 'csv',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain'      => 'txt',
            'application/zip' => 'zip',
        ];
        if (!isset($allowed[$detected])) {
            throw new Exception("File type not allowed. Use JPG, PNG, WEBP, PDF, CSV, DOC, DOCX, XLS, XLSX, TXT, or ZIP.");
        }

        $rawName = (string)($file['name'] ?? 'file');
        $rawExt = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
        $canonExt = $allowed[$detected];
        $okExts = ($canonExt === 'jpg') ? ['jpg','jpeg'] : [$canonExt];
        if (!in_array($rawExt, $okExts, true)) {
            throw new Exception("Filename extension does not match the file type.");
        }

        $folder = (string)$ownerId;
        $dir = BASE_PATH . '/uploads/support/' . $folder;
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents($dir . '/index.html', '');
        
        $stored = bin2hex(random_bytes(16)) . '.' . $canonExt;
        $dest = $dir . '/' . $stored;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception("Could not store the file.");
        }
        @chmod($dest, 0644);

        $relPath = $folder . '/' . $stored;
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $rawName);

        $msgId = Database::insert('support_messages', [
            'affiliate_id'    => $ownerId,
            'owner_type'      => $ownerType,
            'sender_id'       => $adminId,
            'sender_role'     => 'admin',
            'message'         => '',
            'conversation_id' => null,
            'attachment_path' => $relPath,
            'attachment_name' => mb_substr($safeName, 0, 255),
            'attachment_type' => $detected,
            'attachment_size' => (int)$file['size'],
            'is_deleted'      => 1,
            'created_at'      => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'success'         => true,
            'attachment_id'   => (int)$msgId,
            'attachment_name' => $safeName,
            'attachment_size' => (int)$file['size'],
            'attachment_type' => $detected,
        ]);
        exit;
    }

    // ─── ACTION: download ───────────────────────────────────────────────────
    if ($action === 'download') {
        $msgId = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT * FROM support_messages WHERE id=? AND attachment_path IS NOT NULL", [$msgId]);
        
        if (!$row) {
            http_response_code(404);
            die('Not found');
        }

        $abs = BASE_PATH . '/uploads/support/' . $row['attachment_path'];
        if (!is_file($abs)) {
            http_response_code(404);
            die('File missing');
        }

        $type = (string)($row['attachment_type'] ?? 'application/octet-stream');
        $name = (string)($row['attachment_name'] ?? 'file');
        
        $inline = (strpos($type, 'image/') === 0 || $type === 'application/pdf');
        
        header('Content-Type: ' . $type);
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes(basename($name)) . '"');
        readfile($abs);
        exit;
    }

    // ─── ACTION: close_conversation ──────────────────────────────────────────
    if ($action === 'close_conversation') {
        $convId = (int)($input['conversation_id'] ?? 0);
        if ($convId > 0) {
            Database::query(
                "UPDATE support_conversations SET status='closed', closed_by_user_id=?, closed_at=NOW() WHERE id=?",
                [$adminId, $convId]
            );
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // ─── ACTION: reopen_conversation ─────────────────────────────────────────
    if ($action === 'reopen_conversation') {
        $convId = (int)($input['conversation_id'] ?? 0);
        if ($convId > 0) {
            Database::query(
                "UPDATE support_conversations SET status='open', closed_by_user_id=NULL, closed_at=NULL WHERE id=?",
                [$convId]
            );
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // ─── ACTION: delete_message ──────────────────────────────────────────────
    if ($action === 'delete_message') {
        $msgId = (int)($input['message_id'] ?? $_POST['message_id'] ?? 0);
        if ($msgId > 0) {
            // Admin can delete any message
            Database::query("UPDATE support_messages SET is_deleted=1 WHERE id=?", [$msgId]);
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Invalid message_id']);
        exit;
    }

    if ($action === 'edit_message') {
        $msgId = (int)($input['message_id'] ?? $_POST['message_id'] ?? 0);
        $newText = trim($input['message'] ?? $_POST['message'] ?? '');
        
        if ($msgId > 0 && !empty($newText)) {
            $msg = Database::fetchOne("SELECT id FROM support_messages WHERE id=? AND sender_role='admin'", [$msgId]);
            if ($msg) {
                Database::query(
                    "UPDATE support_messages SET message=?, is_edited=1, updated_at=NOW() WHERE id=?", 
                    [$newText, $msgId]
                );
                
                $updatedMsg = Database::fetchOne(
                    "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read, sm.is_edited, sm.updated_at, sm.is_deleted,
                            sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                            CONCAT(u.first_name,' ',u.last_name) as sender_name
                     FROM support_messages sm JOIN users u ON u.id=sm.sender_id
                     WHERE sm.id=?",
                    [$msgId]
                );
                
                echo json_encode(['success' => true, 'message' => $updatedMsg]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Unauthorized, message not found, or empty text']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
