<?php
header('Content-Type: application/json');
Auth::check('affiliate_manager');

try {
    $action = Helpers::get('action') ?: 'list';
    
    // Check permission
    if (!Auth::hasPermission('view_affiliates')) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to view affiliates.']);
        exit;
    }

    $mgrUserId = Auth::id();
    $mgrRow    = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [$mgrUserId]);
    $mgrAffMgrId = $mgrRow ? (int)$mgrRow['id'] : null;
    $affIds = Auth::managerAffiliateIds();

    // ── Create Affiliate ───────────────────────────────────────────────────────
    if ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $fname   = $data['first_name'] ?? '';
        $lname   = $data['last_name'] ?? '';
        $email   = strtolower(trim($data['email'] ?? ''));
        $pass    = $data['password'] ?? '';
        $company = $data['company'] ?? '';
        $phone   = $data['phone'] ?? '';
        $country = $data['country'] ?? 'US';
        $status  = in_array($data['status'] ?? '', ['active','pending']) ? $data['status'] : 'active';

        $errors = [];
        if (!$fname || !$lname) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!$errors && Database::fetchOne("SELECT id FROM users WHERE email=?", [$email])) {
            $errors[] = 'Email already registered.';
        }

        if ($errors) {
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            exit;
        }

        Database::begin();
        try {
            $userId = Database::insert('users', [
                'email'         => $email,
                'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                'role'          => 'affiliate',
                'status'        => $status,
                'first_name'    => $fname,
                'last_name'     => $lname,
                'company'       => $company,
                'phone'         => $phone,
                'country'       => strtoupper(substr($country, 0, 2)),
            ]);
            $affCode = 'AFF' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            Database::insert('affiliates', [
                'user_id'              => $userId,
                'affiliate_code'       => $affCode,
                'registration_answers' => '{}',
                'manager_id'           => $mgrAffMgrId,
            ]);
            Database::commit();

            Mailer::sendEvent($email, "$fname $lname", $status === 'active' ? 'affiliate_approved' : 'affiliate_created', [
                'name'           => "$fname $lname",
                'email'          => $email,
                'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
                'affiliate_code' => $affCode,
            ]);

            echo json_encode(['success' => true, 'message' => "Affiliate account created successfully."]);
        } catch (\Exception $e) {
            Database::rollback();
            echo json_encode(['success' => false, 'error' => 'Failed to create account.']);
        }
        exit;
    }

    // ── Edit Affiliate ─────────────────────────────────────────────────────────
    if ($action === 'edit') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $affId = (int)($data['id'] ?? 0);
        
        if (!in_array($affId, $affIds)) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found or not assigned to you.']);
            exit;
        }

        $affiliate = Database::fetchOne("SELECT u.* FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$affId]);
        if (!$affiliate) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found.']);
            exit;
        }

        $fname   = $data['first_name'] ?? $affiliate['first_name'];
        $lname   = $data['last_name'] ?? $affiliate['last_name'];
        $company = $data['company'] ?? $affiliate['company'];
        $phone   = $data['phone'] ?? $affiliate['phone'];
        $country = strtoupper(substr($data['country'] ?: 'US', 0, 2));

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

    // ── View Affiliate Details ──────────────────────────────────────────────────
    if ($action === 'view') {
        $affId = (int)($_GET['id'] ?? 0);
        if (!in_array($affId, $affIds)) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found or not assigned to you.']);
            exit;
        }

        $affiliate = Database::fetchOne(
            "SELECT u.*, af.id as aff_id, af.affiliate_code, af.balance, af.fraud_score, af.payment_method, af.payment_threshold
             FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?",
            [$affId]
        );
        
        if (!$affiliate) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found.']);
            exit;
        }

        $stats = Database::fetchOne(
            "SELECT COALESCE(SUM(clicks),0) as clicks, COALESCE(SUM(conversions),0) as conv, COALESCE(SUM(approved),0) as approved, COALESCE(SUM(payout),0) as payout
             FROM stats_daily WHERE affiliate_id=?",
            [$affId]
        );

        echo json_encode([
            'success' => true, 
            'data' => [
                'affiliate' => $affiliate,
                'stats' => $stats
            ]
        ]);
        exit;
    }

    // ── Update Status ──────────────────────────────────────────────────────────
    if ($action === 'update_status') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        if (!Auth::hasPermission('approve_affiliates')) {
            echo json_encode(['success' => false, 'error' => 'You do not have permission to change status.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $targetId  = (int)($data['aff_id'] ?? 0);
        $newStatus = $data['status'] ?? '';

        if (in_array($targetId, $affIds) && in_array($newStatus, ['active','suspended','rejected','pending'])) {
            $aff = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$targetId]);
            if ($aff) {
                Database::update('users', ['status' => $newStatus], 'id=?', [$aff['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Affiliate status updated.']);
                exit;
            }
        }
        
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        exit;
    }

    // ── Impersonate Affiliate ──────────────────────────────────────────────────
    if ($action === 'impersonate') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $affId = (int)($data['aff_id'] ?? 0);

        if (!in_array($affId, $affIds)) {
            echo json_encode(['success' => false, 'error' => 'Affiliate not found or not assigned to you.']);
            exit;
        }

        $aff = Database::fetchOne(
            "SELECT af.id, af.user_id, u.first_name, u.last_name
             FROM affiliates af
             JOIN users u ON u.id = af.user_id
             WHERE af.id = ? AND u.status = 'active'",
            [$affId]
        );

        if (!$aff) {
            echo json_encode(['success' => false, 'error' => 'Affiliate is not active or not found.']);
            exit;
        }

        if (!Auth::impersonate((int)$aff['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Could not impersonate affiliate.']);
            exit;
        }

        echo json_encode([
            'success' => true, 
            'role' => 'affiliate',
            'user' => Auth::currentUser(),
            'message' => 'Impersonating affiliate successfully'
        ]);
        exit;
    }

    // ── List All Affiliates ────────────────────────────────────────────────────
    if ($action === 'list') {
        $q = Helpers::get('q');
        $fraudScoreFilter = Helpers::get('fraud_score_filter') ?: 'all';
        $statusFilter = Helpers::get('status') ?: 'all';

        $whereClause = ["af.id IN (" . (empty($affIds) ? '0' : implode(',', array_fill(0, count($affIds), '?'))) . ")"];
        $params = $affIds;

        if ($statusFilter !== 'all') {
            $whereClause[] = "u.status = ?";
            $params[] = $statusFilter;
        }

        if ($q) {
            $whereClause[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR af.affiliate_code LIKE ?)";
            $params = array_merge($params, ["%$q%", "%$q%", "%$q%", "%$q%"]);
        }

        $whereSql = implode(' AND ', $whereClause);

        $affiliates = empty($affIds) ? [] : Database::fetchAll(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at, u.last_login,
                    CASE WHEN u.last_login IS NULL THEN NULL ELSE DATEDIFF(NOW(), u.last_login) END AS days_inactive,
                    af.affiliate_code, af.balance, af.id as aff_id,
                    ROUND(COALESCE(AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0
                                             THEN cv.fraud_score END), 0)) as fraud_score,
                    SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as fraud_checked_count
             FROM users u
             JOIN affiliates af ON af.user_id=u.id
             LEFT JOIN conversions cv ON cv.affiliate_id = af.id AND COALESCE(cv.is_hidden,0)=0
             WHERE $whereSql
             GROUP BY u.id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at, u.last_login,
                      af.affiliate_code, af.balance, af.id
             ORDER BY u.created_at DESC",
            $params
        );

        // Apply fraud score filter in memory if needed
        if ($fraudScoreFilter !== 'all') {
            $filtered = [];
            foreach ($affiliates as $aff) {
                $score = (int)$aff['fraud_score'];
                if ($fraudScoreFilter === 'low' && $score < 30) $filtered[] = $aff;
                elseif ($fraudScoreFilter === 'medium' && $score >= 30 && $score <= 70) $filtered[] = $aff;
                elseif ($fraudScoreFilter === 'high' && $score > 70) $filtered[] = $aff;
            }
            $affiliates = $filtered;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'affiliates' => $affiliates,
                'can_approve' => Auth::hasPermission('approve_affiliates')
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
