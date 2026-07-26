<?php
/**
 * Manager App API — Duplicate Conversions
 */
Auth::check('affiliate_manager');

$affIds = Auth::managerAffiliateIds();
$hasAffiliates = !empty($affIds);

$from = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
$to   = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = [];
if ($hasAffiliates) {
    try {
        $inSql = implode(',', array_fill(0, count($affIds), '?'));
        $rows = Database::fetchAll(
            "SELECT cv.id, cv.conversion_id, cv.offer_id, cv.affiliate_id,
                    cv.payout, cv.status, cv.ip_address, cv.converted_at,
                    cv.transaction_id, cv.goal_name,
                    o.name AS offer_name,
                    CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                    af.affiliate_code,
                    dup.dup_count
             FROM conversions cv
             JOIN (
                SELECT c.offer_id, c.ip_address, COUNT(*) AS dup_count
                FROM conversions c
                JOIN (
                    SELECT DISTINCT offer_id, ip_address
                    FROM conversions
                    WHERE affiliate_id IN ($inSql)
                      AND converted_at BETWEEN ? AND ?
                      AND offer_id IS NOT NULL AND offer_id > 0
                      AND ip_address IS NOT NULL AND ip_address <> ''
                      AND COALESCE(is_hidden, 0) = 0
                ) active ON active.offer_id = c.offer_id AND active.ip_address = c.ip_address
                WHERE c.affiliate_id IN ($inSql)
                  AND c.offer_id IS NOT NULL AND c.offer_id > 0
                  AND c.ip_address IS NOT NULL AND c.ip_address <> ''
                  AND COALESCE(c.is_hidden, 0) = 0
                GROUP BY c.offer_id, c.ip_address
                HAVING dup_count > 1
             ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
             LEFT JOIN offers o      ON o.id  = cv.offer_id
             LEFT JOIN affiliates af ON af.id = cv.affiliate_id
             LEFT JOIN users u       ON u.id  = af.user_id
             WHERE cv.affiliate_id IN ($inSql)
               AND COALESCE(cv.is_hidden, 0) = 0
             ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
            array_merge($affIds, [$dateFrom, $dateTo], $affIds, $affIds)
        ) ?: [];
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$groups = [];
foreach ($rows as $r) {
    $key = $r['offer_id'] . '|' . $r['ip_address'];
    
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'offer_id' => $r['offer_id'],
            'offer_name' => $r['offer_name'],
            'ip_address' => $r['ip_address'],
            'dup_count' => $r['dup_count'],
            'conversions' => []
        ];
    }
    
    $groups[$key]['conversions'][] = [
        'id' => $r['id'],
        'conversion_id' => $r['conversion_id'],
        'affiliate_id' => $r['affiliate_id'],
        'affiliate_name' => $r['affiliate_name'],
        'affiliate_code' => $r['affiliate_code'],
        'payout' => $r['payout'],
        'status' => $r['status'],
        'transaction_id' => $r['transaction_id'],
        'goal_name' => $r['goal_name'],
        'converted_at' => $r['converted_at']
    ];
}

echo json_encode([
    'success' => true,
    'total_groups' => count($groups),
    'total_rows' => count($rows),
    'groups' => array_values($groups)
]);
