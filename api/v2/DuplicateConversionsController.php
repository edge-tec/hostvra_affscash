<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    $affId = Auth::affiliateId();

    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');
    
    $dateFrom = date('Y-m-d 00:00:00', strtotime($from));
    $dateTo   = date('Y-m-d 23:59:59', strtotime($to));

    $rows = Database::fetchAll(
        "SELECT cv.id, cv.conversion_id, cv.offer_id,
                cv.payout, cv.status, cv.ip_address, cv.converted_at,
                cv.transaction_id, cv.goal_name,
                IF(COALESCE(cv.smartlink_id, ck.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id) > 0, COALESCE(CONCAT('SmartLink: ', sl.name), 'SmartLink'), o.name) AS offer_name,
                dup.dup_count
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
         JOIN (
            SELECT offer_id, ip_address, COUNT(*) AS dup_count
            FROM conversions
            WHERE offer_id IS NOT NULL AND offer_id > 0
              AND ip_address IS NOT NULL AND ip_address <> ''
              AND converted_at BETWEEN ? AND ?
            GROUP BY offer_id, ip_address
            HAVING dup_count > 1
         ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
         LEFT JOIN offers o ON o.id = cv.offer_id
         WHERE cv.affiliate_id = ?
           AND cv.converted_at BETWEEN ? AND ?
         ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
        [$dateFrom, $dateTo, $affId, $dateFrom, $dateTo]
    ) ?: [];

    // Grouping for the JSON response
    $groups = [];
    foreach ($rows as $r) {
        $key = $r['offer_id'] . '|' . $r['ip_address'];
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'offer_id' => $r['offer_id'],
                'offer_name' => $r['offer_name'] ?: ('#' . $r['offer_id']),
                'ip_address' => $r['ip_address'],
                'dup_count' => (int)$r['dup_count'],
                'conversions' => []
            ];
        }
        
        $groups[$key]['conversions'][] = [
            'id' => (int)$r['id'],
            'conversion_id' => $r['conversion_id'],
            'status' => $r['status'],
            'payout' => (float)$r['payout'],
            'transaction_id' => $r['transaction_id'],
            'goal_name' => $r['goal_name'],
            'converted_at' => date('M j, Y H:i', strtotime($r['converted_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_clusters' => count($groups),
            'total_conversions' => count($rows),
            'clusters' => array_values($groups)
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
