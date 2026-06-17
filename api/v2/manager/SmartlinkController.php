<?php
header('Content-Type: application/json');
Auth::check('affiliate_manager');

try {
    $action = Helpers::get('action') ?: 'list';
    $affIds = Auth::managerAffiliateIds();

    // ── Browse All Smartlinks ──────────────────────────────────────────────────
    if ($action === 'list') {
        $qSmartlink = Helpers::get('q');
        $whereSl = ["(sl.status = 'active' OR sl.status IS NULL OR sl.status = '')"];
        $slParams = [];
        if ($qSmartlink) { $whereSl[] = "sl.name LIKE ?"; $slParams[] = '%'.$qSmartlink.'%'; }
        $slWhere = implode(' AND ', $whereSl);

        $affInSql = !empty($affIds) ? implode(',', array_fill(0, count($affIds), '?')) : '0';
        $slAffParams = !empty($affIds) ? $affIds : [];

        $smartlinks = Database::fetchAll(
            "SELECT sl.*,
                    COUNT(DISTINCT CASE WHEN sr.status='approved' AND sr.affiliate_id IN ($affInSql) THEN sr.affiliate_id END) as my_approved,
                    COUNT(DISTINCT CASE WHEN sr.status='pending'  AND sr.affiliate_id IN ($affInSql) THEN sr.affiliate_id END) as my_pending,
                    COUNT(DISTINCT CASE WHEN sr.status='approved' THEN sr.affiliate_id END) as total_approved
             FROM smartlinks sl
             LEFT JOIN smartlink_requests sr ON sr.smartlink_id = sl.id
             WHERE $slWhere
             GROUP BY sl.id
             ORDER BY sl.name ASC",
            array_merge($slParams, $slAffParams, $slAffParams)
        );

        $pendingRequestsCount = 0;
        if (!empty($affIds)) {
            $in = implode(',', array_fill(0, count($affIds), '?'));
            $pendingRequestsCount = Database::fetchOne(
                "SELECT COUNT(*) as c FROM smartlink_requests WHERE status='pending' AND affiliate_id IN ($in)",
                $affIds
            )['c'] ?? 0;
        }

        // Managed affiliates for link generator
        $managedAffiliates = [];
        if (!empty($affIds)) {
            $inSql = implode(',', array_fill(0, count($affIds), '?'));
            $managedAffiliates = Database::fetchAll(
                "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
                 FROM affiliates af JOIN users u ON u.id=af.user_id
                 WHERE af.id IN ($inSql) AND u.status='active'
                 ORDER BY name",
                $affIds
            );
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'smartlinks' => $smartlinks,
                'pending_requests_count' => (int)$pendingRequestsCount,
                'managed_affiliates' => $managedAffiliates,
                'tracking_url_base' => Helpers::trackingUrl() . '/sl/'
            ]
        ]);
        exit;
    }

    // ── List Requests ──────────────────────────────────────────────────────────
    if ($action === 'requests') {
        $statusFilter = Helpers::get('status') ?: 'pending'; // 'pending', 'approved', 'rejected', 'all'
        $whereStatus  = $statusFilter !== 'all' ? "AND sr.status=?" : "";
        $statusParams = $statusFilter !== 'all' ? [$statusFilter] : [];

        if (empty($affIds)) {
            $requests = [];
        } else {
            $in  = implode(',', array_fill(0, count($affIds), '?'));
            $requests = Database::fetchAll(
                "SELECT sr.*, sl.name as smartlink_name, sl.slug,
                        CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, u.email as aff_email
                 FROM smartlink_requests sr
                 JOIN smartlinks sl ON sl.id=sr.smartlink_id
                 JOIN affiliates af ON af.id=sr.affiliate_id
                 JOIN users u ON u.id=af.user_id
                 WHERE sr.affiliate_id IN ($in) $whereStatus
                 ORDER BY sr.created_at DESC",
                array_merge($affIds, $statusParams)
            );
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'requests' => $requests
            ]
        ]);
        exit;
    }

    // ── Review Request ─────────────────────────────────────────────────────────
    if ($action === 'review_request') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reqId    = (int)($data['request_id'] ?? 0);
        $decision = $data['decision'] ?? '';
        $note     = $data['admin_note'] ?? '';

        if (!in_array($decision, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid decision.']);
            exit;
        }

        if (empty($affIds)) {
            echo json_encode(['success' => false, 'error' => 'Access denied.']);
            exit;
        }

        $in  = implode(',', array_fill(0, count($affIds), '?'));
        $req = Database::fetchOne(
            "SELECT sr.*, sl.name as sl_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, u.email as aff_email,
                    u.id as user_id_val
             FROM smartlink_requests sr
             JOIN smartlinks sl ON sl.id=sr.smartlink_id
             JOIN affiliates af ON af.id=sr.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE sr.id=? AND sr.affiliate_id IN ($in)",
            array_merge([$reqId], $affIds)
        );

        if (!$req) {
            echo json_encode(['success' => false, 'error' => 'Request not found or access denied.']);
            exit;
        }

        Database::update('smartlink_requests', [
            'status'      => $decision,
            'admin_note'  => $note,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$reqId]);

        // In-app notification
        Database::insert('notifications', [
            'user_id' => $req['user_id_val'],
            'type'    => $decision === 'approved' ? 'success' : 'warning',
            'title'   => 'Smartlink Request ' . ucfirst($decision),
            'message' => 'Your request for smartlink "' . $req['sl_name'] . '" has been ' . $decision . '.',
            'link'    => '/affiliate/smartlinks',
        ]);

        echo json_encode(['success' => true, 'message' => 'Request ' . $decision . ' successfully.']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
