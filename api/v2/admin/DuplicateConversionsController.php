<?php
/**
 * Admin App API — Duplicate Conversions
 */
Auth::check('admin');

$from = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
$to   = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = [];
try {
    $rows = Database::fetchAll(
        "SELECT cv.id, cv.conversion_id, cv.offer_id, cv.affiliate_id,
                cv.payout, cv.revenue, cv.status, cv.ip_address, cv.converted_at,
                cv.transaction_id, cv.goal_name,
                cv.rejection_reason, cv.rejected_at,
                o.name AS offer_name,
                CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                af.affiliate_code,
                dup.dup_count
         FROM conversions cv
         JOIN (
            SELECT offer_id, ip_address, COUNT(*) AS dup_count
            FROM conversions
            WHERE converted_at BETWEEN ? AND ?
              AND offer_id IS NOT NULL AND offer_id > 0
              AND ip_address IS NOT NULL AND ip_address <> ''
              AND COALESCE(is_hidden, 0) = 0
            GROUP BY offer_id, ip_address
            HAVING dup_count > 1
         ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
         LEFT JOIN offers o      ON o.id  = cv.offer_id
         LEFT JOIN affiliates af ON af.id = cv.affiliate_id
         LEFT JOIN users u       ON u.id  = af.user_id
         WHERE cv.converted_at BETWEEN ? AND ?
           AND COALESCE(cv.is_hidden, 0) = 0
         ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
        [$dateFrom, $dateTo, $dateFrom, $dateTo]
    ) ?: [];
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

$groups = [];
foreach ($rows as $r) {
    $key = $r['offer_id'] . '|' . $r['ip_address'];
    
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'offer_id' => $r['offer_id'],
            'offer_name' => $r['offer_name'],
            'ip_address' => $r['ip_address'],
            'dup_count' => (int)$r['dup_count'],
            'conversions' => []
        ];
    }
    
    $groups[$key]['conversions'][] = [
        'id' => (int)$r['id'],
        'conversion_id' => $r['conversion_id'],
        'affiliate_id' => (int)$r['affiliate_id'],
        'affiliate_name' => $r['affiliate_name'],
        'affiliate_code' => $r['affiliate_code'],
        'payout' => (float)$r['payout'],
        'revenue' => (float)$r['revenue'],
        'status' => $r['status'],
        'transaction_id' => $r['transaction_id'],
        'goal_name' => $r['goal_name'],
        'rejection_reason' => $r['rejection_reason'],
        'converted_at' => $r['converted_at']
    ];
}

echo json_encode([
    'success' => true,
    'total_groups' => count($groups),
    'total_rows' => count($rows),
    'groups' => array_values($groups)
]);
