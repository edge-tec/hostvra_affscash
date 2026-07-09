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
        Database::query(
            "UPDATE users 
             SET status = 'active', last_login = NOW(), 
                 inactivity_warned_at = NULL, inactivity_deactivated_at = NULL 
             WHERE id = ? AND role = 'affiliate'", 
            [$userId]
        );
        
        require_once BASE_PATH . '/core/NotificationHelper.php';
        NotificationHelper::notifyUser(
            $userId, 
            "Account Approved", 
            "Congratulations! Your affiliate account has been approved. You can now start earning.", 
            "success", 
            "/affiliate/dashboard",
            ['type' => 'account_approved']
        );

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'edit') {
        $affId = (int)($input['id'] ?? 0);
        $affiliate = Database::fetchOne("SELECT u.* FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$affId]);
        
        if (!$affiliate) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found.']);
            exit;
        }

        $fname   = $input['first_name'] ?? $affiliate['first_name'];
        $lname   = $input['last_name'] ?? $affiliate['last_name'];
        $company = $input['company'] ?? $affiliate['company'];
        $phone   = $input['phone'] ?? $affiliate['phone'];
        $country = strtoupper(substr($input['country'] ?: 'US', 0, 2));

        if (!$fname || !$lname) {
            echo json_encode(['success' => false, 'error' => 'First and last name are required.']);
            exit;
        }

        Database::update('users', [
            'first_name' => $fname,
            'last_name'  => $lname,
            'company'    => $company,
            'phone'      => $phone,
            'country'    => $country,
        ], 'id=?', [$affiliate['id']]);

        echo json_encode(['success' => true, 'message' => 'Affiliate profile updated.']);
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
