<?php
Auth::check('affiliate_manager');
$pageTitle = 'Click Report';

$affIds = Auth::managerAffiliateIds();

$from        = Helpers::get('from') ?: date('Y-m-d');
$to          = Helpers::get('to')   ?: date('Y-m-d');
$offerId     = (int)(Helpers::get('offer_id') ?: 0);
$selAffId    = (int)(Helpers::get('affiliate_id') ?: 0);
$limit       = min((int)(Helpers::get('limit') ?: 500), 5000);
$clickFilter = Helpers::get('click_filter') ?: 'all';
if (!in_array($clickFilter, ['all','converted','approved'])) $clickFilter = 'all';

if (empty($affIds)) {
    $clicks = []; $totalClicks = $fraudCount = $totalRevenue = $totalPayout = $totalProfit = 0;
    $offerList = $affList = [];
    require BASE_PATH . '/views/affiliate_manager/click_report.php';
    return;
}

$inSql = implode(',', array_fill(0, count($affIds), '?'));
$params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
$where  = ["c.clicked_at BETWEEN ? AND ?", "c.affiliate_id IN ($inSql)"];
$params = array_merge($params, $affIds);

if ($offerId > 0) { $where[] = 'c.offer_id = ?'; $params[] = $offerId; }
if ($selAffId > 0 && in_array($selAffId, $affIds)) { $where[] = 'c.affiliate_id = ?'; $params[] = $selAffId; }
if ($clickFilter === 'converted') {
    $where[] = 'EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.is_hidden = 0)';
} elseif ($clickFilter === 'approved') {
    $where[] = "EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.status = 'approved' AND _cv.is_hidden = 0)";
}

$whereStr = implode(' AND ', $where);

// CSV export
if (Helpers::get('export') === 'csv') {
    $rows = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.referer,
                c.is_fraud, c.fraud_score, c.os, c.browser, c.device_type,
                c.ip_address, c.country, c.city,
                COALESCE(cv.payout,  0) as payout,
                COALESCE(cv.revenue, 0) as revenue,
                (COALESCE(cv.revenue, 0) - COALESCE(cv.payout, 0)) as profit,
                (cv.conversion_id IS NOT NULL) as has_conversion,
                c.clicked_at, c.status,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
         FROM clicks c
         LEFT JOIN offers o ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u ON u.id = af.user_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id
               AND cv.status IN ('approved','pending')
               AND cv.is_hidden = 0
         WHERE $whereStr
         ORDER BY c.clicked_at DESC
         LIMIT $limit",
        $params
    );
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="click-report-' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['OFFER','AFFILIATE','AFF CODE','CLICK ID','SUB1','SUB2','SUB3','SUB4','REFERER','FRAUD','OS','BROWSER','DEVICE','IP ADDRESS','COUNTRY','CITY','REVENUE','PAYOUT','PROFIT','CLICK TIME']);
    foreach ($rows as $r) {
        fputcsv($f, [
            $r['offer_name'], $r['aff_name'], $r['affiliate_code'],
            $r['click_id'], $r['sub1'], $r['sub2'], $r['sub3'], $r['sub4'],
            $r['referer'], $r['is_fraud'] ? 'Yes' : 'No',
            $r['os'], $r['browser'], $r['device_type'],
            $r['ip_address'], $r['country'], $r['city'],
            number_format($r['revenue'], 4), number_format($r['payout'], 4), number_format($r['profit'], 4),
            $r['clicked_at'],
        ]);
    }
    fclose($f);
    exit;
}

$clicks = Database::fetchAll(
    "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.referer,
            c.is_fraud, c.fraud_score, c.os, c.browser, c.device_type,
            c.ip_address, c.country, c.city,
            COALESCE(cv.payout,  0) as payout,
            COALESCE(cv.revenue, 0) as revenue,
            (COALESCE(cv.revenue, 0) - COALESCE(cv.payout, 0)) as profit,
            (cv.conversion_id IS NOT NULL) as has_conversion,
            c.clicked_at, c.status,
            o.name as offer_name,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM clicks c
     JOIN offers o ON o.id = c.offer_id
     JOIN affiliates af ON af.id = c.affiliate_id
     JOIN users u ON u.id = af.user_id
     LEFT JOIN conversions cv ON cv.click_id = c.click_id
           AND cv.status IN ('approved','pending')
           AND cv.is_hidden = 0
     WHERE $whereStr
     ORDER BY c.clicked_at DESC
     LIMIT $limit",
    $params
);

$totalClicks  = count($clicks);
$fraudCount   = count(array_filter($clicks, fn($r) => $r['is_fraud']));
$totalRevenue = array_sum(array_column($clicks, 'revenue'));
$totalPayout  = array_sum(array_column($clicks, 'payout'));
$totalProfit  = $totalRevenue - $totalPayout;

$offerList = Database::fetchAll("SELECT DISTINCT o.id, o.name FROM offers o JOIN clicks c ON c.offer_id=o.id WHERE c.affiliate_id IN ($inSql) ORDER BY o.name", $affIds);
$affList   = empty($affIds) ? [] : Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id IN ($inSql) ORDER BY name",
    $affIds
);

require BASE_PATH . '/views/affiliate_manager/click_report.php';
