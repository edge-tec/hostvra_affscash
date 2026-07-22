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
            $where[] = "v.affiliate_id IN ($placeholders)";
            $params = array_merge($params, $affIds);
        }

        if ($qIp)     { $where[] = 'v.ip_address LIKE ?';     $params[] = '%' . $qIp . '%'; }
        if ($qType)   { $where[] = 'v.detection_type = ?';    $params[] = $qType; }
        if ($qAff)    { $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CAST(v.affiliate_id AS CHAR) LIKE ? OR af.affiliate_code LIKE ?)";
                        $params = array_merge($params, ['%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%']); }
        if ($dateFrom){ $where[] = 'DATE(v.blocked_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)  { $where[] = 'DATE(v.blocked_at) <= ?'; $params[] = $dateTo; }

        $whereStr = implode(' AND ', $where);

        $logs = Database::fetchAll(
            "SELECT v.*,
                    CONCAT(u.first_name, ' ', u.last_name) as aff_name,
                    af.affiliate_code
             FROM vpn_blocked_log v
             LEFT JOIN affiliates af ON af.id = v.affiliate_id
             LEFT JOIN users u ON u.id = af.user_id
             WHERE $whereStr
             ORDER BY v.blocked_at DESC
             LIMIT 1000",
            $params
        );

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
