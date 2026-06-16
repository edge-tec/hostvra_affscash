<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $userId = (int)($input['user_id'] ?? 0);

    if (!$userId) {
        throw new Exception("Missing user_id");
    }

    if ($action === 'approve') {
        Database::query("UPDATE users SET status = 'active' WHERE id = ? AND role = 'affiliate'", [$userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'impersonate') {
        if (Auth::impersonate($userId)) {
            echo json_encode([
                'success' => true,
                'role' => 'affiliate',
                'user' => Auth::currentUser()
            ]);
        } else {
            throw new Exception("Failed to impersonate");
        }
        exit;
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
