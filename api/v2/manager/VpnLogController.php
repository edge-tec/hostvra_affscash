<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    $action = $_GET['action'] ?? 'list';
    $affIds = Auth::managerAffiliateIds();

    if ($action === 'list') {
        $qIp      = trim($_GET['q_ip'] ?? '');
        $qAff     = trim($_GET['q_aff'] ?? '');
        $qType    = trim($_GET['q_type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if (empty($affIds)) {
            $where[] = '1=0';
        } else {
            $placeholders = implode(',', array_fill(0, count($affIds), '?'));
            $where[] = "COALESCE(v.affiliate_id, mapped_c.affiliate_id) IN ($placeholders)";
            $params = array_merge($params, $affIds);
        }

        if ($qIp)     { $where[] = 'v.ip_address LIKE ?';     $params[] = '%' . $qIp . '%'; }
        if ($qType)   { $where[] = 'v.detection_type = ?';    $params[] = $qType; }
        if ($qAff)    { $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CAST(COALESCE(v.affiliate_id, mapped_c.affiliate_id) AS CHAR) LIKE ? OR af.affiliate_code LIKE ?)";
                        $params = array_merge($params, ['%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%']); }
        if ($dateFrom){ $where[] = 'DATE(v.blocked_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)  { $where[] = 'DATE(v.blocked_at) <= ?'; $params[] = $dateTo; }

        $whereStr = implode(' AND ', $where);

        $logs = Database::fetchAll(
            "SELECT v.*,
                    COALESCE(v.affiliate_id, mapped_c.affiliate_id) AS effective_affiliate_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS aff_name,
                    af.affiliate_code,
                    COALESCE(v.smartlink_id, mapped_c.smartlink_id, sl_off.smartlink_id) AS effective_smartlink_id,
                    COALESCE(v.smartlink_name, sl.name) AS effective_smartlink_name,
                    o.name AS db_offer_name
             FROM vpn_blocked_log v
             LEFT JOIN clicks mapped_c ON v.affiliate_id IS NULL
                 AND mapped_c.id = (
                     SELECT c_sub.id FROM clicks c_sub
                     WHERE c_sub.ip_address = v.ip_address
                     ORDER BY c_sub.id DESC LIMIT 1
                 )
             LEFT JOIN smartlink_offers sl_off ON sl_off.offer_id = v.offer_id
             LEFT JOIN smartlinks sl ON sl.id = COALESCE(v.smartlink_id, mapped_c.smartlink_id, sl_off.smartlink_id)
             LEFT JOIN affiliates af ON af.id = COALESCE(v.affiliate_id, mapped_c.affiliate_id)
             LEFT JOIN users u ON u.id = af.user_id
             LEFT JOIN offers o ON o.id = v.offer_id
             WHERE $whereStr
             ORDER BY v.blocked_at DESC
             LIMIT 1000",
            $params
        );

        // Normalize affiliate_id, smartlink_id, smartlink_name for JSON consumer
        foreach ($logs as &$logItem) {
            $logItem['affiliate_id']   = !empty($logItem['effective_affiliate_id']) ? (int)$logItem['effective_affiliate_id'] : ($logItem['affiliate_id'] ? (int)$logItem['affiliate_id'] : null);
            $logItem['smartlink_id']   = !empty($logItem['effective_smartlink_id']) ? (int)$logItem['effective_smartlink_id'] : ($logItem['smartlink_id'] ? (int)$logItem['smartlink_id'] : null);
            $logItem['smartlink_name'] = !empty($logItem['effective_smartlink_name']) ? $logItem['effective_smartlink_name'] : ($logItem['smartlink_name'] ?? null);
        }
        unset($logItem);

        echo json_encode(['success' => true, 'data' => $logs]);
        exit;
    }

    if ($action === 'stats') {
        if (empty($affIds)) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_last_30_days' => 0,
                    'today_blocked' => 0,
                    'vpn_hosting_count' => 0,
                    'proxy_count' => 0,
                    'types' => []
                ]
            ]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($affIds), '?'));

        $totalBlocked = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND blocked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $affIds
        )['cnt'] ?? 0;

        $todayBlocked = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND DATE(blocked_at) = CURDATE()",
            $affIds
        )['cnt'] ?? 0;

        $typeCounts = Database::fetchAll(
            "SELECT detection_type, COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) GROUP BY detection_type ORDER BY cnt DESC",
            $affIds
        );

        $vpnHostingCount = 0;
        $proxyCount = 0;
        foreach ($typeCounts as $tc) {
            if (stripos($tc['detection_type'], 'vpn') !== false || stripos($tc['detection_type'], 'hosting') !== false) {
                $vpnHostingCount += (int)$tc['cnt'];
            }
            if (stripos($tc['detection_type'], 'proxy') !== false) {
                $proxyCount += (int)$tc['cnt'];
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'total_last_30_days' => (int)$totalBlocked,
                'today_blocked' => (int)$todayBlocked,
                'vpn_hosting_count' => $vpnHostingCount,
                'proxy_count' => $proxyCount,
                'types' => $typeCounts
            ]
        ]);
        exit;
    }

    if ($action === 'clear') {
        if (!empty($affIds)) {
            $placeholders = implode(',', array_fill(0, count($affIds), '?'));
            Database::query("DELETE FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND blocked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)", $affIds);
        }
        echo json_encode(['success' => true, 'message' => 'Log entries older than 30 days cleared for your assigned affiliates.']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
