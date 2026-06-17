<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    
    $action = $_GET['action'] ?? 'list';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? $_GET['action'] ?? 'send';
    }

    $affId = Auth::affiliateId();
    $role = 'affiliate';

    // Need access to chat functions. They are currently defined in the old ChatController, 
    // but instead of requiring it (which might run its logic), we will implement what we need directly 
    // or load the helper functions if they are decoupled.
    // Wait, the functions in `controllers/api/ChatController.php` are defined globally, but running that file also executes the routing logic at the bottom.
    // So we should duplicate the essential SQL logic here for safety.

    if ($action === 'messages' || $action === 'list') {
        // Find open conversation
        $openConv = Database::fetchOne("SELECT id FROM support_conversations WHERE affiliate_id=? AND owner_type='affiliate' AND status='open'", [$affId]);
        
        $messages = [];
        if ($openConv) {
            $convId = $openConv['id'];
            $messages = Database::fetchAll(
                "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
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

    if ($action === 'send') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("POST method required for sending messages");
        }
        
        $messageText = trim($input['message'] ?? '');
        if (empty($messageText)) {
            throw new Exception("Message cannot be empty");
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

        $userId = $_SESSION['user_id'] ?? $affId;

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

        $newMessage = Database::fetchOne(
            "SELECT sm.id, sm.sender_id, sm.sender_role, sm.message, sm.created_at, sm.is_read,
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

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
