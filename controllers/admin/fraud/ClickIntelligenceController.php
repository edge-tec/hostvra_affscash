<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$search  = trim($_GET['q'] ?? '');
$filter  = $_GET['filter'] ?? 'all'; // all | burst | low_cvr | suspicious
$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

// Dropdown data for filters
$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

$dr = fraud_date_range();

// Build WHERE clause for click analysis
$where = "c.clicked_at BETWEEN ? AND ?";
$params = [$dr['date_from'], $dr['date_to']];

if ($search !== '') {
    $where .= " AND (c.ip_address LIKE ? OR a.affiliate_code LIKE ? OR o.name LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}
if ($affId > 0)   { $where .= " AND c.affiliate_id = ?"; $params[] = $affId; }
if ($offerId > 0) { $where .= " AND c.offer_id = ?";     $params[] = $offerId; }

$totalRow = Database::fetchOne(
    "SELECT COUNT(*) AS c FROM clicks c
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     LEFT JOIN offers o ON o.id = c.offer_id
     WHERE $where", $params
);
$total = $totalRow['c'] ?? 0;
$pag   = fraud_paginate($total, $perPage, $page);

$clicks = Database::fetchAll(
    "SELECT c.id, c.ip_address, c.user_agent, c.referer, c.country, c.clicked_at,
            c.offer_id, o.name AS offer_name,
            c.affiliate_id, a.affiliate_code
     FROM clicks c
     LEFT JOIN offers o ON o.id = c.offer_id
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     WHERE $where
     ORDER BY c.clicked_at DESC LIMIT $perPage OFFSET {$pag['offset']}",
    $params
) ?: [];

// IP burst analysis: top IPs last 24h
$ipBursts = Database::fetchAll(
    "SELECT ip_address, COUNT(*) AS total_clicks,
            COUNT(DISTINCT offer_id) AS unique_offers,
            COUNT(DISTINCT affiliate_id) AS unique_affs,
            MIN(clicked_at) AS first_seen, MAX(clicked_at) AS last_seen
     FROM clicks WHERE clicked_at >= NOW() - INTERVAL 24 HOUR
       AND ip_address IS NOT NULL
     GROUP BY ip_address HAVING total_clicks >= 20
     ORDER BY total_clicks DESC LIMIT 50"
) ?: [];

// Affiliate CVR analysis (last 7 days, min 50 clicks)
$aff_cvr = Database::fetchAll(
    "SELECT a.id, a.affiliate_code, u.first_name, u.last_name,
            COUNT(DISTINCT c.id) AS clicks,
            COUNT(DISTINCT cv.id) AS convs,
            ROUND(COUNT(DISTINCT cv.id) / NULLIF(COUNT(DISTINCT c.id),0) * 100, 4) AS cvr
     FROM affiliates a
     LEFT JOIN users u ON u.id = a.user_id
     LEFT JOIN clicks c ON c.affiliate_id = a.id AND c.clicked_at >= NOW() - INTERVAL 7 DAY
     LEFT JOIN conversions cv ON cv.affiliate_id = a.id AND cv.converted_at >= NOW() - INTERVAL 7 DAY
     GROUP BY a.id HAVING clicks >= 50
     ORDER BY cvr ASC LIMIT 30"
) ?: [];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="click-intelligence-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['ID','IP Address','User Agent','Country','Clicked At','Offer','Affiliate Code']);
    $exp = Database::fetchAll(
        "SELECT c.id, c.ip_address, c.user_agent, c.country, c.clicked_at, o.name AS offer_name, a.affiliate_code
         FROM clicks c LEFT JOIN offers o ON o.id=c.offer_id LEFT JOIN affiliates a ON a.id=c.affiliate_id
         WHERE $where ORDER BY c.clicked_at DESC LIMIT 10000", $params) ?: [];
    foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['user_agent'],$r['country'],$r['clicked_at'],$r['offer_name'],$r['affiliate_code']]);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/click_intelligence.php';
