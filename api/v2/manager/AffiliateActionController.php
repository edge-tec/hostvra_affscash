<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    $affId = (int)($input['aff_id'] ?? 0); // Need affiliate ID to verify ownership

    if (!$affId) {
        throw new Exception("Missing aff_id");
    }

    $mgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [Auth::id()]);
    $mgrId  = $mgrRow ? (int)$mgrRow['id'] : 0;

    $aff = Database::fetchOne(
        "SELECT af.id, af.user_id, u.status
         FROM affiliates af
         JOIN users u ON u.id = af.user_id
         WHERE af.id = ? AND af.manager_id = ?",
        [$affId, $mgrId]
    );

    if (!$aff) {
        throw new Exception("Affiliate not found or not assigned to your account.");
    }

    if ($action === 'approve') {
        Database::query("UPDATE users SET status = 'active' WHERE id = ?", [$aff['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'impersonate') {
        if ($aff['status'] !== 'active') {
            throw new Exception("Cannot impersonate non-active affiliate.");
        }
        if (Auth::impersonate((int)$aff['user_id'])) {
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
