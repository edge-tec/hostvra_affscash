<?php
/**
 * Affiliate Manager — Duplicate Conversions (read-only)
 *
 * Same detection logic as the admin page, but scoped to conversions belonging
 * to affiliates managed by the current manager.
 */
Auth::check('affiliate_manager');
if (!Auth::hasPermission('view_reports')) {
    Helpers::flash('error', 'You do not have permission to view reports.');
    Helpers::redirect('/affiliate_manager/dashboard');
}
$pageTitle = 'Duplicate Conversions';

$affIds = Auth::managerAffiliateIds();
$hasAffiliates = !empty($affIds);

$range = Helpers::get('range') ?: '';
if (!empty($range)) {
    $today = date('Y-m-d');
    switch (strtolower($range)) {
        case 'today':
            $from = $today;
            $to   = $today;
            break;
        case 'yesterday':
            $from = date('Y-m-d', strtotime('-1 day'));
            $to   = $from;
            break;
        case 'last_7_days':
        case '7days':
            $from = date('Y-m-d', strtotime('-6 days'));
            $to   = $today;
            break;
        case 'last_15_days':
        case '15days':
            $from = date('Y-m-d', strtotime('-14 days'));
            $to   = $today;
            break;
        case 'this_month':
            $from = date('Y-m-01');
            $to   = $today;
            break;
        case 'last_month':
            $from = date('Y-m-01', strtotime('first day of last month'));
            $to   = date('Y-m-t', strtotime('last month'));
            break;
        case 'last_90_days':
        case '90days':
            $from = date('Y-m-d', strtotime('-89 days'));
            $to   = $today;
            break;
        default:
            $from = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
            $to   = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
            break;
    }
} else {
    $from = Helpers::get('start_date') ?: (Helpers::get('from') ?: date('Y-m-01'));
    $to   = Helpers::get('end_date')   ?: (Helpers::get('to')   ?: date('Y-m-d'));
}
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = [];
if ($hasAffiliates) {
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
            SELECT offer_id, ip_address, COUNT(*) AS dup_count
            FROM conversions
            WHERE affiliate_id IN ($inSql)
              AND offer_id IS NOT NULL AND offer_id > 0
              AND ip_address IS NOT NULL AND ip_address <> ''
              AND COALESCE(is_hidden, 0) = 0
            GROUP BY offer_id, ip_address
            HAVING dup_count > 1
         ) dup ON dup.offer_id = cv.offer_id AND dup.ip_address = cv.ip_address
         LEFT JOIN offers o      ON o.id  = cv.offer_id
         LEFT JOIN affiliates af ON af.id = cv.affiliate_id
         LEFT JOIN users u       ON u.id  = af.user_id
         WHERE cv.affiliate_id IN ($inSql)
           AND COALESCE(cv.is_hidden, 0) = 0
           AND (
               cv.converted_at BETWEEN ? AND ?
               OR cv.rejected_at BETWEEN ? AND ?
               OR cv.created_at BETWEEN ? AND ?
           )
         ORDER BY cv.offer_id, cv.ip_address, cv.converted_at DESC",
        array_merge($affIds, $affIds, [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo])
    ) ?: [];
}

$groups = [];
foreach ($rows as $r) {
    $groups[$r['offer_id'] . '|' . $r['ip_address']][] = $r;
}
$totalGroups = count($groups);
$totalRows   = count($rows);

require BASE_PATH . '/views/affiliate_manager/duplicate_conversions.php';
