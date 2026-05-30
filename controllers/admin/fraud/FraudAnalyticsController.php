<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

// Date range — defaults to last 30 days so the analytics dashboard works out of the box.
$dr = fraud_date_range(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
$_df = $dr['date_from']; $_dt = $dr['date_to'];

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

// Daily click vs conversion trend
$dailyTrend = Database::fetchAll(
    "SELECT DATE(c.clicked_at) AS day,
            COUNT(*) AS clicks,
            COALESCE((SELECT COUNT(*) FROM conversions cv WHERE DATE(cv.converted_at)=DATE(c.clicked_at)),0) AS convs
     FROM clicks c
     WHERE c.clicked_at BETWEEN ? AND ?
     GROUP BY day ORDER BY day ASC",
    [$_df, $_dt]
) ?: [];

// Affiliate risk ranking (suspicious score composite)
$affRiskWhere  = "1=1";
$affRiskParams = [];
if ($affId > 0)   { $affRiskWhere .= " AND a.id=?";  $affRiskParams[] = $affId; }

$_offerJoin = $offerId > 0 ? " AND c.offer_id=" . (int)$offerId : "";
$_offerJoinCv = $offerId > 0 ? " AND cv.offer_id=" . (int)$offerId : "";
$affRisk = Database::fetchAll(
    "SELECT a.id, a.affiliate_code, u.first_name, u.last_name,
            COUNT(DISTINCT c.id) AS total_clicks,
            COUNT(DISTINCT cv.id) AS total_convs,
            ROUND(COUNT(DISTINCT cv.id)/NULLIF(COUNT(DISTINCT c.id),0)*100,2) AS cvr,
            COUNT(DISTINCT CASE WHEN cv.id IS NOT NULL AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)<30 THEN cv.id END) AS fast_convs,
            COUNT(DISTINCT fc.id) AS open_cases
     FROM affiliates a
     LEFT JOIN users u ON u.id = a.user_id
     LEFT JOIN clicks c ON c.affiliate_id=a.id AND c.clicked_at BETWEEN ? AND ?{$_offerJoin}
     LEFT JOIN conversions cv ON cv.affiliate_id=a.id AND cv.converted_at BETWEEN ? AND ?{$_offerJoinCv}
     LEFT JOIN fraud_cases fc ON fc.affiliate_id=a.id AND fc.status NOT IN ('resolved','dismissed')
     WHERE $affRiskWhere
     GROUP BY a.id
     HAVING total_clicks > 0
     ORDER BY open_cases DESC, fast_convs DESC, total_clicks DESC LIMIT 30",
    array_merge([$_df, $_dt, $_df, $_dt], $affRiskParams)
) ?: [];

// Case trend by severity
$caseTrend = Database::fetchAll(
    "SELECT DATE(created_at) AS day, severity, COUNT(*) AS cnt
     FROM fraud_cases WHERE created_at BETWEEN ? AND ?
     GROUP BY day, severity ORDER BY day ASC",
    [$_df, $_dt]
) ?: [];

// Blocklist hit summary
$blocklistHits = Database::fetchAll(
    "SELECT type, SUM(hit_count) AS total_hits, COUNT(*) AS entries
     FROM fraud_blocklist WHERE is_active=1 GROUP BY type ORDER BY total_hits DESC"
) ?: [];

// Top fraud types
$caseTypes = Database::fetchAll(
    "SELECT type, COUNT(*) AS cnt FROM fraud_cases
     WHERE created_at BETWEEN ? AND ?
     GROUP BY type ORDER BY cnt DESC",
    [$_df, $_dt]
) ?: [];

// Summary KPIs
$kpis = [
    'total_clicks'    => Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE clicked_at BETWEEN ? AND ?", [$_df, $_dt])['c'] ?? 0,
    'total_convs'     => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions WHERE converted_at BETWEEN ? AND ?", [$_df, $_dt])['c'] ?? 0,
    'open_cases'      => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases WHERE status='open'")['c'] ?? 0,
    'blocklist_total' => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_blocklist WHERE is_active=1")['c'] ?? 0,
    'fast_convs'      => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions cv LEFT JOIN clicks c ON c.id=cv.click_id WHERE c.id IS NOT NULL AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)<30 AND TIMESTAMPDIFF(SECOND,c.clicked_at,cv.converted_at)>=0 AND cv.converted_at BETWEEN ? AND ?", [$_df, $_dt])['c'] ?? 0,
];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fraud-analytics-affiliate-risk-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Affiliate Code','Name','Total Clicks','Total Convs','CVR%','Fast Convs','Open Cases']);
    foreach ($affRisk as $r) fputcsv($f, [$r['affiliate_code'],trim($r['first_name'].' '.$r['last_name']),$r['total_clicks'],$r['total_convs'],$r['cvr'],$r['fast_convs'],$r['open_cases']]);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/analytics.php';
