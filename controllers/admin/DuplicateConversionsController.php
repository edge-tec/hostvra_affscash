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

$from   = Helpers::get('from') ?: date('Y-m-01');
$to     = Helpers::get('to')   ?: date('Y-m-d');
$status = Helpers::get('status') ?: '';   // optional filter

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$statusClause = '';
$params = [$dateFrom, $dateTo];
if (in_array($status, ['approved','pending','rejected','chargebacked'], true)) {
    $statusClause = ' AND cv.status = ?';
    $params[] = $status;
}

// All conversions that share (offer_id, ip_address) with at least one other
// conversion in the date range. SmartLink / custom-URL conversions (offer_id
// null or 0) are excluded — there is no meaningful "same offer" grouping for
// them.
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
        WHERE offer_id IS NOT NULL AND offer_id > 0
          AND ip_address IS NOT NULL AND ip_address <> ''
          AND converted_at BETWEEN ? AND ?
        GROUP BY offer_id, ip_address
        HAVING dup_count > 1
     ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
     LEFT JOIN offers o      ON o.id  = cv.offer_id
     LEFT JOIN affiliates af ON af.id = cv.affiliate_id
     LEFT JOIN users u       ON u.id  = af.user_id
     WHERE cv.converted_at BETWEEN ? AND ?
           $statusClause
     ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
    array_merge([$dateFrom, $dateTo], $params)
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
