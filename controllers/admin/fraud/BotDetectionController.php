<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

// Known bot/crawler UA fragments
$botPatterns = [
    'Googlebot','bingbot','Slurp','DuckDuckBot','Baiduspider','YandexBot',
    'Sogou','Exabot','facebot','ia_archiver','curl','wget','python-requests',
    'libwww','scrapy','Go-http','Java/','Apache-HttpClient','okhttp',
    'HeadlessChrome','PhantomJS','SlimerJS','Selenium','WebDriver',
    'bot','crawler','spider','scraper','scan','check','monitor','probe',
];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$search  = trim($_GET['q'] ?? '');
$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

$dr = fraud_date_range();

// Build LIKE conditions for bot UA detection
$uaConditions = implode(' OR ', array_fill(0, count($botPatterns), 'c.user_agent LIKE ?'));
$uaParams     = array_map(fn($p) => "%$p%", $botPatterns);

$extraWhere  = ' AND c.clicked_at BETWEEN ? AND ?';
$extraParams = [$dr['date_from'], $dr['date_to']];
if ($search !== '') {
    $extraWhere  .= " AND (c.ip_address LIKE ? OR c.user_agent LIKE ?)";
    $extraParams  = array_merge($extraParams, ["%$search%", "%$search%"]);
}
if ($affId > 0)   { $extraWhere .= " AND c.affiliate_id = ?"; $extraParams[] = $affId; }
if ($offerId > 0) { $extraWhere .= " AND c.offer_id = ?";     $extraParams[] = $offerId; }

$totalRow = Database::fetchOne(
    "SELECT COUNT(*) AS c FROM clicks c WHERE ($uaConditions)$extraWhere",
    array_merge($uaParams, $extraParams)
);
$total = $totalRow['c'] ?? 0;
$pag   = fraud_paginate($total, $perPage, $page);

$botClicks = Database::fetchAll(
    "SELECT c.id, c.ip_address, c.user_agent, c.country, c.clicked_at,
            c.offer_id, o.name AS offer_name, a.affiliate_code
     FROM clicks c
     LEFT JOIN offers o ON o.id = c.offer_id
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     WHERE ($uaConditions)$extraWhere
     ORDER BY c.clicked_at DESC LIMIT $perPage OFFSET {$pag['offset']}",
    array_merge($uaParams, $extraParams)
) ?: [];

// Top bot UAs
$topBotUas = Database::fetchAll(
    "SELECT c.user_agent, COUNT(*) AS cnt FROM clicks c
     WHERE ($uaConditions)
       AND c.clicked_at >= NOW() - INTERVAL 7 DAY
       AND c.user_agent IS NOT NULL
     GROUP BY c.user_agent ORDER BY cnt DESC LIMIT 20",
    $uaParams
) ?: [];

// Affiliate summary: how many bot clicks per affiliate
$affBotSummary = Database::fetchAll(
    "SELECT a.affiliate_code, u.first_name, u.last_name,
            COUNT(c.id) AS bot_clicks,
            ROUND(COUNT(c.id)*100.0/NULLIF((SELECT COUNT(*) FROM clicks c2 WHERE c2.affiliate_id=a.id AND c2.clicked_at>=NOW()-INTERVAL 7 DAY),0),1) AS bot_pct
     FROM clicks c
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     LEFT JOIN users u ON u.id = a.user_id
     WHERE ($uaConditions) AND c.clicked_at >= NOW() - INTERVAL 7 DAY
     GROUP BY a.id HAVING bot_clicks >= 5
     ORDER BY bot_clicks DESC LIMIT 20",
    $uaParams
) ?: [];

$stats = [
    'total_bot'    => $total,
    'bot_24h'      => Database::fetchOne("SELECT COUNT(*) AS c FROM clicks c WHERE ($uaConditions) AND c.clicked_at >= NOW() - INTERVAL 24 HOUR", $uaParams)['c'] ?? 0,
    'unique_ips'   => Database::fetchOne("SELECT COUNT(DISTINCT ip_address) AS c FROM clicks c WHERE ($uaConditions) AND c.clicked_at >= NOW() - INTERVAL 7 DAY", $uaParams)['c'] ?? 0,
    'unique_affs'  => Database::fetchOne("SELECT COUNT(DISTINCT affiliate_id) AS c FROM clicks c WHERE ($uaConditions) AND c.clicked_at >= NOW() - INTERVAL 7 DAY", $uaParams)['c'] ?? 0,
];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bot-detection-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['ID','IP Address','User Agent','Country','Clicked At','Offer','Affiliate Code']);
    $exp = Database::fetchAll(
        "SELECT c.id, c.ip_address, c.user_agent, c.country, c.clicked_at, o.name AS offer_name, a.affiliate_code
         FROM clicks c LEFT JOIN offers o ON o.id=c.offer_id LEFT JOIN affiliates a ON a.id=c.affiliate_id
         WHERE ($uaConditions)$extraWhere ORDER BY c.clicked_at DESC LIMIT 10000",
        array_merge($uaParams, $extraParams)) ?: [];
    foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['user_agent'],$r['country'],$r['clicked_at'],$r['offer_name'],$r['affiliate_code']]);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/bot_detection.php';
