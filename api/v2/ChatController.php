<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    
    $action = $_GET['action'] ?? 'list';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? $_GET['action'] ?? 'send';
        if (isset($_POST['action'])) {
            $action = $_POST['action']; // for multipart form data
        }
    }

    $affId = Auth::affiliateId();
    $role = 'affiliate';
    $userId = $_SESSION['user_id'] ?? $affId;

    if ($action === 'messages' || $action === 'list') {
        // Find open conversation
        $openConv = Database::fetchOne("SELECT id FROM support_conversations WHERE affiliate_id=? AND owner_type='affiliate' AND status='open'", [$affId]);
        
        $messages = [];
        if ($openConv) {
            $convId = $openConv['id'];
            $messages = Database::fetchAll(
                "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
                        sm.attachment_path, sm.attachment_name, sm.attachment_type, sm.attachment_size,
                        CONCAT(u.first_name,' ',u.last_name) as sender_name
                 FROM support_messages sm JOIN users u ON u.id=sm.sender_id
                 WHERE sm.conversation_id=? AND sm.is_deleted=0
                 ORDER BY sm.created_at ASC LIMIT 500",
                [$convId]
            );

            // Mark unread messages from admin as read
            Database::query(
                "UPDATE support_messages SET is_read=1
                 WHERE conversation_id=? AND sender_role NOT IN ('affiliate','advertiser') AND is_read=0",
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
            'sender_id'       => $userId,
            'sender_role'     => $role,
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
        
        $messageText = trim($input['message'] ?? '');
        $attachmentId = (int)($input['attachment_id'] ?? 0);

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

        // Ensure a conversation exists
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
            // Update the stub message
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
                'sender_id' => $userId,
                'sender_role' => $role,
                'message' => $messageText,
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => 0
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
        
        // When serving images to an app we usually can just force inline
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
            // Ensure the message belongs to this affiliate and they are the sender
            $msg = Database::fetchOne("SELECT id FROM support_messages WHERE id=? AND affiliate_id=? AND sender_role=?", [$msgId, $affId, $role]);
            if ($msg) {
                Database::query("UPDATE support_messages SET is_deleted=1 WHERE id=?", [$msgId]);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Unauthorized or message not found']);
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
