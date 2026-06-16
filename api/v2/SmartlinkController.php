<?php
Auth::check('affiliate');
header('Content-Type: application/json');

$affId  = Auth::affiliateId();
$action = $_GET['action'] ?? 'list';
$appUrl = Config::get('config', 'app.url') ?? '';

if ($action === 'list') {
    try {
        $smartlinks = Database::fetchAll(
            "SELECT sl.*, COUNT(so.id) as offer_count 
             FROM smartlinks sl 
             LEFT JOIN smartlink_offers so ON so.smartlink_id=sl.id 
             WHERE sl.status='active' 
             GROUP BY sl.id 
             ORDER BY sl.name ASC"
        );

        $myRequests = Database::fetchAll(
            "SELECT smartlink_id, status FROM smartlink_requests WHERE affiliate_id=?", 
            [$affId]
        );
        $requestMap = [];
        foreach ($myRequests as $r) {
            $requestMap[$r['smartlink_id']] = $r['status'];
        }

        $list = [];
        foreach ($smartlinks as $sl) {
            $slId = (int)$sl['id'];
            $status = $requestMap[$slId] ?? null;
            $trackingLink = $status === 'approved' ? $appUrl . '/s/' . $slId . '?aff=' . $affId : null;

            $list[] = [
                'id' => $slId,
                'name' => $sl['name'],
                'description' => $sl['description'],
                'require_approval' => (int)$sl['require_approval'],
                'offer_count' => (int)$sl['offer_count'],
                'distribution_type' => $sl['distribution_type'] ?? 'WEIGHT',
                'access_status' => $status,
                'tracking_link' => $trackingLink
            ];
        }

        echo json_encode(['success' => true, 'smartlinks' => $list]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load smartlinks']);
    }

} elseif ($action === 'apply') {
    $json = json_decode(file_get_contents('php://input'), true);
    $slId = (int)($json['smartlink_id'] ?? 0);
    $note = trim($json['promotion_description'] ?? '');

    $sl = Database::fetchOne("SELECT id, name, require_approval FROM smartlinks WHERE id=? AND status='active'", [$slId]);
    if (!$sl) { 
        echo json_encode(['success' => false, 'error' => 'Smartlink not found']); 
        exit; 
    }

    $autoApprove = empty($sl['require_approval']);
    $existing = Database::fetchOne("SELECT id, status FROM smartlink_requests WHERE smartlink_id=? AND affiliate_id=?", [$slId, $affId]);

    if ($existing) {
        if ($existing['status'] === 'approved') {
            echo json_encode(['success' => false, 'error' => 'You already have access to this smartlink.']); 
            exit;
        }
        if ($existing['status'] === 'pending' && !$autoApprove) {
            echo json_encode(['success' => false, 'error' => 'Your request is already pending review.']); 
            exit;
        }
        
        $newStatus = $autoApprove ? 'approved' : 'pending';
        Database::update('smartlink_requests', [
            'status'         => $newStatus,
            'affiliate_note' => $note,
            'admin_note'     => $autoApprove ? 'Auto-approved' : null,
            'reviewed_by'    => null,
            'reviewed_at'    => $autoApprove ? date('Y-m-d H:i:s') : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ], 'id=?', [$existing['id']]);
    } else {
        $newStatus = $autoApprove ? 'approved' : 'pending';
        Database::insert('smartlink_requests', [
            'smartlink_id'   => $slId,
            'affiliate_id'   => $affId,
            'status'         => $newStatus,
            'affiliate_note' => $note,
            'admin_note'     => $autoApprove ? 'Auto-approved' : null,
            'reviewed_at'    => $autoApprove ? date('Y-m-d H:i:s') : null,
        ]);
    }

    if (!$autoApprove) {
        try {
            $me = Auth::currentUser();
            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'info',
                'title'       => 'New Smartlink Request',
                'message'     => ($me['first_name'] ?? 'Affiliate') . ' requested access to smartlink "' . $sl['name'] . '"',
                'link'        => '/admin/smartlinks?action=requests',
            ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true, 
        'message' => $autoApprove ? 'Smartlink access granted!' : 'Request submitted.',
        'new_status' => $autoApprove ? 'approved' : 'pending'
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action not found']);
}
