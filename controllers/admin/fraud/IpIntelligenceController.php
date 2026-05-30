<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$search  = trim($_GET['ip'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

$dr = fraud_date_range(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

// Top IPs by click volume (selected date range; defaults to last 30 days).
$where  = "ip_address IS NOT NULL AND clicked_at BETWEEN ? AND ?";
$params = [$dr['date_from'], $dr['date_to']];
if ($search !== '') { $where .= " AND ip_address LIKE ?"; $params[] = "%$search%"; }
if ($affId > 0)     { $where .= " AND affiliate_id = ?";  $params[] = $affId; }
if ($offerId > 0)   { $where .= " AND offer_id = ?";      $params[] = $offerId; }

$totalRow = Database::fetchOne("SELECT COUNT(DISTINCT ip_address) AS c FROM clicks WHERE $where", $params);
$total    = $totalRow['c'] ?? 0;
$pag      = fraud_paginate($total, $perPage, $page);

$ipStats = Database::fetchAll(
    "SELECT ip_address,
            COUNT(*) AS total_clicks,
            COUNT(DISTINCT affiliate_id) AS unique_affs,
            COUNT(DISTINCT offer_id) AS unique_offers,
            SUM(CASE WHEN clicked_at >= NOW() - INTERVAL 1 HOUR THEN 1 ELSE 0 END) AS clicks_1h,
            MIN(clicked_at) AS first_seen, MAX(clicked_at) AS last_seen
     FROM clicks WHERE $where
     GROUP BY ip_address
     ORDER BY total_clicks DESC LIMIT $perPage OFFSET {$pag['offset']}",
    $params
) ?: [];

// Conversion IPs (same date range as the clicks query above).
$convIpStats = Database::fetchAll(
    "SELECT ip_address,
            COUNT(*) AS total_convs,
            COUNT(DISTINCT affiliate_id) AS unique_affs,
            SUM(payout) AS total_payout,
            MIN(converted_at) AS first_seen
     FROM conversions
     WHERE ip_address IS NOT NULL AND converted_at BETWEEN ? AND ?
     GROUP BY ip_address
     ORDER BY total_convs DESC LIMIT 20",
    [$dr['date_from'], $dr['date_to']]
) ?: [];

// Blocklisted IPs
$blockedIps = Database::fetchAll(
    "SELECT * FROM fraud_blocklist WHERE type='ip' AND is_active=1 ORDER BY created_at DESC LIMIT 50"
) ?: [];

// Single IP drill-down
$drillIp     = null;
$drillClicks = [];
$drillConvs  = [];
if ($search !== '' && strlen($search) >= 7) {
    $drillIp = $search;
    $drillClicks = Database::fetchAll(
        "SELECT c.id, c.clicked_at, c.user_agent, c.country, c.referer,
                o.name AS offer_name, a.affiliate_code
         FROM clicks c
         LEFT JOIN offers o ON o.id = c.offer_id
         LEFT JOIN affiliates a ON a.id = c.affiliate_id
         WHERE c.ip_address = ? ORDER BY c.clicked_at DESC LIMIT 100",
        [$search]
    ) ?: [];
    $drillConvs = Database::fetchAll(
        "SELECT cv.id, cv.converted_at, cv.payout, cv.status, cv.goal_name,
                o.name AS offer_name, a.affiliate_code
         FROM conversions cv
         LEFT JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN affiliates a ON a.id = cv.affiliate_id
         WHERE cv.ip_address = ? ORDER BY cv.converted_at DESC LIMIT 50",
        [$search]
    ) ?: [];
}

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ip-intelligence-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['IP Address','Total Clicks','Unique Affs','Unique Offers','Clicks (1h)','First Seen','Last Seen']);
    $exp = Database::fetchAll(
        "SELECT ip_address, COUNT(*) AS total_clicks, COUNT(DISTINCT affiliate_id) AS unique_affs,
                COUNT(DISTINCT offer_id) AS unique_offers,
                SUM(CASE WHEN clicked_at>=NOW()-INTERVAL 1 HOUR THEN 1 ELSE 0 END) AS clicks_1h,
                MIN(clicked_at) AS first_seen, MAX(clicked_at) AS last_seen
         FROM clicks WHERE $where GROUP BY ip_address ORDER BY total_clicks DESC LIMIT 10000", $params) ?: [];
    foreach ($exp as $r) fputcsv($f, [$r['ip_address'],$r['total_clicks'],$r['unique_affs'],$r['unique_offers'],$r['clicks_1h'],$r['first_seen'],$r['last_seen']]);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/ip_intelligence.php';
