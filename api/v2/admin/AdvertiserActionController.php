<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';
    
    if ($action === 'create') {
        $fname   = $input['first_name'] ?? '';
        $lname   = $input['last_name'] ?? '';
        $email   = strtolower(trim($input['email'] ?? ''));
        $pass    = $input['password'] ?? '';
        $company = $input['company'] ?? '';
        $phone   = $input['phone'] ?? '';
        $country = $input['country'] ?? 'US';
        $status  = in_array($input['status'] ?? '', ['active','pending','rejected','deleted']) ? $input['status'] : 'active';
        $budgetExempt = (int)($input['budget_exempt'] ?? 0);

        if (!$fname || !$lname || !$email || !$pass) {
            throw new Exception("Name, email and password are required.");
        }
        if (Database::fetchOne("SELECT id FROM users WHERE email=?", [$email])) {
            throw new Exception("Email already registered.");
        }

        Database::begin();
        try {
            $userId = Database::insert('users', [
                'email'         => $email,
                'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                'role'          => 'advertiser',
                'status'        => $status,
                'first_name'    => $fname,
                'last_name'     => $lname,
                'company'       => $company,
                'phone'         => $phone,
                'country'       => strtoupper(substr($country, 0, 2)),
            ]);
            $advCode = 'ADV' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            Database::insert('advertisers', [
                'user_id'         => $userId,
                'advertiser_code' => $advCode,
                'budget_exempt'   => $budgetExempt
            ]);
            Database::commit();
            echo json_encode(['success' => true, 'message' => "Advertiser created successfully."]);
        } catch (\Exception $e) {
            Database::rollback();
            throw new Exception("Failed to create account.");
        }
        exit;
    }

    if ($action === 'edit') {
        $advId = (int)($input['id'] ?? 0);
        $adv = Database::fetchOne("SELECT u.id as user_id FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE adv.id=?", [$advId]);
        if (!$adv) throw new Exception("Advertiser not found.");

        $fname   = $input['first_name'] ?? '';
        $lname   = $input['last_name'] ?? '';
        $company = $input['company'] ?? '';
        $phone   = $input['phone'] ?? '';
        $country = $input['country'] ?? 'US';
        $budgetExempt = isset($input['budget_exempt']) ? (int)$input['budget_exempt'] : null;

        if (!$fname || !$lname) throw new Exception("First and last name are required.");

        Database::update('users', [
            'first_name' => $fname,
            'last_name'  => $lname,
            'company'    => $company,
            'phone'      => $phone,
            'country'    => strtoupper(substr($country, 0, 2)),
        ], 'id=?', [$adv['user_id']]);

        if ($budgetExempt !== null) {
            Database::update('advertisers', ['budget_exempt' => $budgetExempt], 'id=?', [$advId]);
        }

        echo json_encode(['success' => true, 'message' => 'Advertiser profile updated.']);
        exit;
    }

    if ($action === 'update_status') {
        $advId = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        if (!in_array($status, ['active','pending','suspended','rejected','deleted'])) {
            throw new Exception("Invalid status");
        }

        $adv = Database::fetchOne("SELECT user_id FROM advertisers WHERE id=?", [$advId]);
        if ($adv) {
            Database::update('users', ['status' => $status], 'id=?', [$adv['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Status updated.']);
            exit;
        }
        throw new Exception("Advertiser not found.");
    }

    if ($action === 'delete') {
        $advId = (int)($input['id'] ?? 0);
        $adv = Database::fetchOne("SELECT user_id FROM advertisers WHERE id=?", [$advId]);
        if ($adv) {
            Database::update('users', ['status' => 'deleted'], 'id=?', [$adv['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Advertiser deleted.']);
            exit;
        }
        throw new Exception("Advertiser not found.");
    }

    if ($action === 'impersonate') {
        $advId = (int)($input['id'] ?? 0);
        $adv = Database::fetchOne("SELECT user_id FROM advertisers WHERE id=?", [$advId]);
        if ($adv) {
            if (Auth::impersonate((int)$adv['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'role' => 'advertiser',
                    'user' => Auth::currentUser()
                ]);
                exit;
            }
        }
        throw new Exception("Failed to impersonate");
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
