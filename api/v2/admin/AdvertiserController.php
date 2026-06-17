<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';

        $whereFilters = ["u.role='advertiser'"];
        $params = [];

        if ($status !== 'all') {
            $whereFilters[] = "u.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $whereFilters[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR adv.advertiser_code LIKE ?)";
            $searchLike = "%$search%";
            $params = array_merge($params, [$searchLike, $searchLike, $searchLike, $searchLike]);
        }

        $whereClause = implode(' AND ', $whereFilters);

        $advertisers = Database::fetchAll(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.company, u.phone, u.country, u.status, u.created_at,
                    adv.advertiser_code, adv.balance, adv.budget_exempt, adv.id as adv_id
             FROM users u
             JOIN advertisers adv ON adv.user_id=u.id
             WHERE $whereClause
             ORDER BY u.created_at DESC",
            $params
        );

        foreach ($advertisers as &$adv) {
            $adv['balance'] = (float)$adv['balance'];
            $adv['budget_exempt'] = (int)$adv['budget_exempt'];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'advertisers' => $advertisers
            ]
        ]);
        exit;
    }

    if ($action === 'view') {
        $advId = (int)($_GET['id'] ?? 0);
        
        $advertiser = Database::fetchOne(
            "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.company, u.phone, u.country, u.status, u.created_at,
                    adv.advertiser_code, adv.balance, adv.budget_exempt, adv.id as adv_id
             FROM users u
             JOIN advertisers adv ON adv.user_id=u.id
             WHERE adv.id=?",
            [$advId]
        );

        if (!$advertiser) {
            echo json_encode(['success' => false, 'error' => 'Advertiser not found.']);
            exit;
        }

        echo json_encode([
            'success' => true, 
            'data' => [
                'advertiser' => $advertiser
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
