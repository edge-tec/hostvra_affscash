<?php
/**
 * Admin — Duplicate Conversions
 *
 * Detects conversions where more than one conversion exists for the same
 * (offer_id, ip_address) pair. Admin can reject any duplicate inline; the
 * reject path reuses /admin/conversions so all the existing side-effects
 * (balance reversal, manager commission reversal, postback fire, affiliate
 * notification) run unchanged.
 */
Auth::check('admin');
$pageTitle = 'Duplicate Conversions';

$from   = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
$to     = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
$status = Helpers::get('status') ?: '';   // optional filter

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$statusClause = '';
$params = [$dateFrom, $dateTo];
if (in_array($status, ['approved','pending','rejected','chargebacked'], true)) {
    $statusClause = ' AND cv.status = ?';
    $params[] = $status;
}

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
        SELECT c.offer_id, c.ip_address, COUNT(*) AS dup_count
        FROM conversions c
        JOIN (
            SELECT DISTINCT offer_id, ip_address
            FROM conversions
            WHERE converted_at BETWEEN ? AND ?
              AND offer_id IS NOT NULL AND offer_id > 0
              AND ip_address IS NOT NULL AND ip_address <> ''
              AND COALESCE(is_hidden, 0) = 0
        ) active ON active.offer_id = c.offer_id AND active.ip_address = c.ip_address
        WHERE c.offer_id IS NOT NULL AND c.offer_id > 0
          AND c.ip_address IS NOT NULL AND c.ip_address <> ''
          AND COALESCE(c.is_hidden, 0) = 0
        GROUP BY c.offer_id, c.ip_address
        HAVING dup_count > 1
     ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
     LEFT JOIN offers o      ON o.id  = cv.offer_id
     LEFT JOIN affiliates af ON af.id = cv.affiliate_id
     LEFT JOIN users u       ON u.id  = af.user_id
     WHERE COALESCE(cv.is_hidden, 0) = 0
           $statusClause
     ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
    $params
) ?: [];

// Group rows by (offer_id, ip_address) so the view can render one block per
// duplicate cluster. The DB ORDER BY already returns rows in cluster order.
$groups = [];
foreach ($rows as $r) {
    $key = $r['offer_id'] . '|' . $r['ip_address'];
    $groups[$key][] = $r;
}

$totalGroups = count($groups);
$totalRows   = count($rows);

require BASE_PATH . '/views/admin/reports/duplicate_conversions.php';
