<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$tab     = $_GET['tab'] ?? 'fast'; // fast | duplicate | pending_old | geo_mismatch
$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);
$convId  = trim($_GET['conversion_id'] ?? '');

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

$dr = fraud_date_range();

// ── Fast conversions: click-to-conversion < 30 seconds ──────────────────────
$fastConversions = [];
$duplicateConversions = [];
$pendingOld = [];
$geoMismatch = [];

if ($tab === 'fast' || $tab === 'all') {
    $fastWhere  = "c.id IS NOT NULL AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)<30 AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)>=0 AND cv.converted_at BETWEEN ? AND ?";
    $fastParams = [$dr['date_from'], $dr['date_to']];
    if ($affId > 0)   { $fastWhere .= " AND cv.affiliate_id=?"; $fastParams[] = $affId; }
    if ($offerId > 0) { $fastWhere .= " AND cv.offer_id=?";     $fastParams[] = $offerId; }
    if ($convId !== ''){ $fastWhere .= " AND cv.conversion_id LIKE ?"; $fastParams[] = "%$convId%"; }

    $totalFast = Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions cv LEFT JOIN clicks c ON c.id = cv.click_id WHERE $fastWhere", $fastParams
    )['c'] ?? 0;
    $pagFast = fraud_paginate($totalFast, $perPage, $page);

    $fastConversions = Database::fetchAll(
        "SELECT cv.id, cv.conversion_id, cv.ip_address, cv.payout, cv.revenue, cv.status,
                cv.converted_at, cv.goal_name, cv.transaction_id,
                o.name AS offer_name, a.affiliate_code,
                TIMESTAMPDIFF(SECOND, c.clicked_at, cv.converted_at) AS time_diff,
                c.user_agent
         FROM conversions cv
         LEFT JOIN clicks c ON c.id = cv.click_id
         LEFT JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN affiliates a ON a.id = cv.affiliate_id
         WHERE $fastWhere
         ORDER BY time_diff ASC LIMIT $perPage OFFSET {$pagFast['offset']}", $fastParams
    ) ?: [];
}

if ($tab === 'duplicate') {
    $dupWhere  = "cv.converted_at BETWEEN ? AND ? AND cv.ip_address IS NOT NULL";
    $dupParams = [$dr['date_from'], $dr['date_to']];
    if ($affId > 0)   { $dupWhere .= " AND cv.affiliate_id=?"; $dupParams[] = $affId; }
    if ($offerId > 0) { $dupWhere .= " AND cv.offer_id=?";     $dupParams[] = $offerId; }

    $totalDup = Database::fetchOne(
        "SELECT COUNT(*) AS c FROM (SELECT ip_address, offer_id FROM conversions cv WHERE $dupWhere GROUP BY ip_address, offer_id HAVING COUNT(*)>1) t", $dupParams
    )['c'] ?? 0;
    $pagDup = fraud_paginate($totalDup, $perPage, $page);

    $duplicateConversions = Database::fetchAll(
        "SELECT cv.ip_address, cv.offer_id, COUNT(*) AS dup_count,
                SUM(cv.payout) AS total_payout, o.name AS offer_name,
                MIN(cv.converted_at) AS first_conv, MAX(cv.converted_at) AS last_conv,
                a.affiliate_code
         FROM conversions cv
         LEFT JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN affiliates a ON a.id = cv.affiliate_id
         WHERE $dupWhere GROUP BY cv.ip_address, cv.offer_id HAVING dup_count > 1
         ORDER BY dup_count DESC LIMIT $perPage", $dupParams
    ) ?: [];
}

if ($tab === 'pending_old') {
    $oldWhere  = "cv.status='pending' AND cv.converted_at BETWEEN ? AND ?";
    $oldParams = [$dr['date_from'], $dr['date_to']];
    if ($affId > 0)   { $oldWhere .= " AND cv.affiliate_id=?"; $oldParams[] = $affId; }
    if ($offerId > 0) { $oldWhere .= " AND cv.offer_id=?";     $oldParams[] = $offerId; }
    if ($convId !== ''){ $oldWhere .= " AND cv.conversion_id LIKE ?"; $oldParams[] = "%$convId%"; }

    $totalOld = Database::fetchOne("SELECT COUNT(*) AS c FROM conversions cv WHERE $oldWhere", $oldParams)['c'] ?? 0;
    $pagOld = fraud_paginate($totalOld, $perPage, $page);

    $pendingOld = Database::fetchAll(
        "SELECT cv.id, cv.conversion_id, cv.payout, cv.revenue, cv.converted_at, cv.ip_address,
                o.name AS offer_name, a.affiliate_code,
                DATEDIFF(NOW(), cv.converted_at) AS days_old
         FROM conversions cv
         LEFT JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN affiliates a ON a.id = cv.affiliate_id
         WHERE $oldWhere ORDER BY cv.converted_at ASC LIMIT $perPage OFFSET {$pagOld['offset']}", $oldParams
    ) ?: [];
}

// Summary counts for tab badges
$stats = [
    'fast'        => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions cv LEFT JOIN clicks c ON c.id=cv.click_id WHERE c.id IS NOT NULL AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)<30 AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)>=0")['c'] ?? 0,
    'duplicate'   => Database::fetchOne("SELECT COUNT(*) AS c FROM (SELECT ip_address,offer_id FROM conversions WHERE converted_at>=NOW()-INTERVAL 7 DAY AND ip_address IS NOT NULL GROUP BY ip_address,offer_id HAVING COUNT(*)>1) t")['c'] ?? 0,
    'pending_old' => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions WHERE status='pending' AND converted_at < NOW() - INTERVAL 7 DAY")['c'] ?? 0,
];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="conversion-scanner-'.$tab.'-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    if ($tab === 'fast') {
        fputcsv($f, ['Conversion ID','IP','Offer','Affiliate','Payout','Status','Time Diff (s)','Converted At']);
        foreach ($fastConversions as $r) fputcsv($f, [$r['conversion_id']??$r['id'],$r['ip_address'],$r['offer_name'],$r['affiliate_code'],number_format($r['payout'],4),$r['status'],$r['time_diff'],$r['converted_at']]);
    } elseif ($tab === 'duplicate') {
        fputcsv($f, ['IP','Offer','Dup Count','Total Payout','Affiliate','First Conv','Last Conv']);
        foreach ($duplicateConversions as $r) fputcsv($f, [$r['ip_address'],$r['offer_name'],$r['dup_count'],number_format($r['total_payout'],4),$r['affiliate_code'],$r['first_conv'],$r['last_conv']]);
    } elseif ($tab === 'pending_old') {
        fputcsv($f, ['Conversion ID','IP','Offer','Affiliate','Payout','Days Old','Converted At']);
        foreach ($pendingOld as $r) fputcsv($f, [$r['conversion_id']??$r['id'],$r['ip_address'],$r['offer_name'],$r['affiliate_code'],number_format($r['payout'],4),$r['days_old'],$r['converted_at']]);
    }
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/conversion_scanner.php';
