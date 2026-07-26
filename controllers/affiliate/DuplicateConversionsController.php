<?php
/**
 * Affiliate — Duplicate Conversions (read-only)
 *
 * Affiliate sees only their own conversions flagged as duplicate (same offer
 * + same IP as at least one other conversion of theirs in the date range).
 */
Auth::check('affiliate');
$pageTitle = 'Duplicate Conversions';
$affId = Auth::affiliateId();

$from = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
$to   = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = Database::fetchAll(
    "SELECT cv.id, cv.conversion_id, cv.offer_id,
            cv.payout, cv.status, cv.ip_address, cv.converted_at,
            cv.transaction_id, cv.goal_name,
            IF(COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id), 4, '0'), '] ', COALESCE(sl.name, sl2.name)), 'SmartLink'), o.name) AS offer_name,
            dup.dup_count
     FROM conversions cv
     LEFT JOIN clicks ck ON ck.click_id = cv.click_id
     LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
     LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
     LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
     JOIN (
        SELECT c.offer_id, c.ip_address, COUNT(*) AS dup_count
        FROM conversions c
        JOIN (
            SELECT DISTINCT offer_id, ip_address
            FROM conversions
            WHERE affiliate_id = ?
              AND converted_at BETWEEN ? AND ?
              AND offer_id IS NOT NULL AND offer_id > 0
              AND ip_address IS NOT NULL AND ip_address <> ''
              AND COALESCE(is_hidden, 0) = 0
        ) active ON active.offer_id = c.offer_id AND active.ip_address = c.ip_address
        WHERE c.affiliate_id = ?
          AND c.offer_id IS NOT NULL AND c.offer_id > 0
          AND c.ip_address IS NOT NULL AND c.ip_address <> ''
          AND COALESCE(c.is_hidden, 0) = 0
        GROUP BY c.offer_id, c.ip_address
        HAVING dup_count > 1
     ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
     LEFT JOIN offers o ON o.id = cv.offer_id
     WHERE cv.affiliate_id = ?
       AND COALESCE(cv.is_hidden, 0) = 0
     ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
    [$affId, $dateFrom, $dateTo, $affId, $affId]
) ?: [];

$groups = [];
foreach ($rows as $r) {
    $groups[$r['offer_id'] . '|' . $r['ip_address']][] = $r;
}
$totalGroups = count($groups);
$totalRows   = count($rows);

require BASE_PATH . '/views/affiliate/duplicate_conversions.php';
