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
            Database::query(
                "UPDATE support_messages SET is_read=1
                 WHERE conversation_id=? AND sender_role = ? AND is_read=0",
                [$convId, $ownerType]
            );
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

        // Just handling text message for now, attachment handling from Android is a bit more complex (multipart)
        // so we'll support basic text message to start, or if attachmentId is provided.
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

        if ($ownerType === 'affiliate') {
            try {
                $user = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$ownerId]);
                if ($user && $user['user_id']) {
                    Database::insert('notifications', [
                        'user_id' => (int)$user['user_id'],
                        'target_role' => 'affiliate',
                        'title' => 'New Support Reply',
                        'message' => 'You received a new reply from support.',
                        'link' => '/affiliate/support'
                    ]);
                    require_once BASE_PATH . '/core/FirebaseMessaging.php';
                    FirebaseMessaging::sendToUser((int)$user['user_id'], 'New Support Reply', 'You received a new reply from support.', ['type' => 'support']);
                }
            } catch (\Throwable $e) {}
        }

        echo json_encode(['success' => true, 'data' => ['message_id' => $msgId]]);
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

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
