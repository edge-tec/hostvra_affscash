<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate_manager');
    
    $action = $_GET['action'] ?? 'list';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? $_GET['action'] ?? 'send';
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
        }
    }

    $managerAffIds = Auth::managerAffiliateIds();
    $mgrUserId = Auth::id();
    $mgrRow = Database::fetchOne("SELECT am.id FROM affiliate_managers am WHERE am.user_id=?", [$mgrUserId]);
    $mgrId = $mgrRow['id'] ?? 0;
    
    if (empty($managerAffIds)) {
        echo json_encode(['success' => true, 'conversations' => [], 'messages' => []]);
        exit;
    }

    $in = implode(',', array_fill(0, count($managerAffIds), '?'));

    if ($action === 'list') {
        // Fetch all open/closed conversations for this manager's affiliates
        $statusFilter = $_GET['status'] ?? 'open';
        $statusSql = '';
        if ($statusFilter === 'open')   $statusSql = "AND status='open'";
        if ($statusFilter === 'closed') $statusSql = "AND status='closed'";

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
             WHERE af.id IN ($in) " . ($statusFilter === 'open' ? "" : "AND sc.id IS NOT NULL") . "
             ORDER BY COALESCE(sc.last_message_at, '2000-01-01') DESC",
            $managerAffIds
        );

        echo json_encode([
            'success' => true,
            'conversations' => $rows
        ]);
        exit;
    }

    if ($action === 'messages') {
        $convId = (int)($_GET['conversation_id'] ?? 0);
        $affId = (int)($_GET['affiliate_id'] ?? 0);

        if (!in_array($affId, $managerAffIds)) {
            throw new Exception("Unauthorized to view this affiliate's messages.");
        }

        $messages = [];
        if ($convId > 0) {
            $messages = Database::fetchAll(
                "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
                        sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                        CONCAT(u.first_name,' ',u.last_name) as sender_name
                 FROM support_messages sm JOIN users u ON u.id=sm.sender_id
                 WHERE sm.conversation_id=? AND sm.is_deleted=0
                 ORDER BY sm.created_at ASC LIMIT 500",
                [$convId]
            );

            // Mark unread messages from affiliate as read
            Database::query(
                "UPDATE support_messages SET is_read=1
                 WHERE conversation_id=? AND sender_role='affiliate' AND is_read=0",
                [$convId]
            );
        }

        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        exit;
    }

    if ($action === 'upload') {
        if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'] ?? '')) {
            throw new Exception("No file uploaded.");
        }

        $file = $_FILES['file'];
        $affId = (int)($_POST['affiliate_id'] ?? 0);
        if (!in_array($affId, $managerAffIds)) {
            throw new Exception("Unauthorized to upload for this affiliate.");
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
        ];
        if (!isset($allowed[$detected])) {
            throw new Exception("File type not allowed. Use JPG, PNG, WEBP, PDF or CSV.");
        }

        $rawName = (string)($file['name'] ?? 'file');
        $rawExt = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
        $canonExt = $allowed[$detected];
        $okExts = ($canonExt === 'jpg') ? ['jpg','jpeg'] : [$canonExt];
        if (!in_array($rawExt, $okExts, true)) {
            throw new Exception("Filename extension does not match the file type.");
        }

        $folder = (string)$affId;
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
            'affiliate_id'    => $affId,
            'owner_type'      => 'affiliate',
            'sender_id'       => $mgrUserId,
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

    if ($action === 'send') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("POST method required for sending messages");
        }
        
        $affId = (int)($input['affiliate_id'] ?? 0);
        $messageText = trim($input['message'] ?? '');
        $attachmentId = (int)($input['attachment_id'] ?? 0);

        if (!in_array($affId, $managerAffIds)) {
            throw new Exception("Unauthorized to message this affiliate.");
        }
        if (empty($messageText) && $attachmentId <= 0) {
            throw new Exception("Message or attachment cannot be empty");
        }

        // Fetch attachment if provided
        $attachment = null;
        if ($attachmentId > 0) {
            $attachment = Database::fetchOne(
                "SELECT * FROM support_messages WHERE id=? AND is_deleted=1 AND attachment_path IS NOT NULL AND affiliate_id=?",
                [$attachmentId, $affId]
            );
            if (!$attachment) {
                throw new Exception("Invalid or expired attachment");
            }
        }

        // Find or create open conversation
        $openConv = Database::fetchOne("SELECT id FROM support_conversations WHERE affiliate_id=? AND owner_type='affiliate' AND status='open'", [$affId]);
        if (!$openConv) {
            $convId = Database::insert('support_conversations', [
                'affiliate_id' => $affId,
                'owner_type' => 'affiliate',
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'last_message_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $convId = $openConv['id'];
            Database::query("UPDATE support_conversations SET last_message_at=NOW() WHERE id=?", [$convId]);
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
                'affiliate_id' => $affId,
                'owner_type' => 'affiliate',
                'sender_id' => $mgrUserId,
                'sender_role' => 'admin',
                'message' => $messageText,
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0
            ]);
        }

        // Trigger notification
        $ownerUser = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
        $me = Database::fetchOne("SELECT first_name FROM users WHERE id=?", [$mgrUserId]);
        if ($ownerUser) {
            Database::insert('notifications', [
                'user_id'     => $ownerUser['user_id'],
                'target_role' => null,
                'type'        => 'info',
                'title'       => 'New Message from Manager',
                'message'     => ($me['first_name'] ?? 'Manager') . ': ' . mb_substr($messageText !== '' ? $messageText : 'Attachment', 0, 60),
                'link'        => '/affiliate/support',
                'is_read'     => 0,
            ]);
        }

        $newMessage = Database::fetchOne(
            "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
                    sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                    CONCAT(u.first_name,' ',u.last_name) as sender_name
             FROM support_messages sm JOIN users u ON u.id=sm.sender_id
             WHERE sm.id=?",
            [$msgId]
        );

        echo json_encode([
            'success' => true,
            'message' => $newMessage
        ]);
        exit;
    }

    if ($action === 'download') {
        $msgId = (int)($_GET['id'] ?? 0);
        $affId = (int)($_GET['affiliate_id'] ?? 0);

        if (!in_array($affId, $managerAffIds)) {
            http_response_code(403);
            die('Forbidden');
        }

        $row = Database::fetchOne("SELECT * FROM support_messages WHERE id=? AND attachment_path IS NOT NULL AND affiliate_id=?", [$msgId, $affId]);
        
        if (!$row) {
            http_response_code(404);
            die('Not found or unauthorized');
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

    if ($action === 'delete_message') {
        $msgId = (int)($input['message_id'] ?? $_POST['message_id'] ?? 0);
        if ($msgId > 0) {
            $msg = Database::fetchOne("SELECT id, sender_id FROM support_messages WHERE id=?", [$msgId]);
            // Manager can delete messages they sent themselves
            if ($msg && (int)$msg['sender_id'] === (int)$mgrUserId) {
                Database::query("UPDATE support_messages SET is_deleted=1 WHERE id=?", [$msgId]);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Unauthorized or invalid message_id']);
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
