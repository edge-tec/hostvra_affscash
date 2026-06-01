<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

$_lmHasRange = (Helpers::get('from') !== null && Helpers::get('from') !== '')
            || (Helpers::get('to')   !== null && Helpers::get('to')   !== '');

if ($_lmHasRange) {
    $dr = fraud_date_range();
    $clickWhere = "c.clicked_at BETWEEN ? AND ? AND c.status = 'blocked'";
    $convWhere  = "cv.converted_at BETWEEN ? AND ? AND cv.is_fraud = 1 AND cv.hide_reason LIKE '%_blocked%'";
    $clickParams = $convParams = [$dr['date_from'], $dr['date_to']];
} else {
    $dr = ['from' => date('Y-m-d'), 'to' => date('Y-m-d')];
    $clickWhere  = "c.clicked_at >= NOW() - INTERVAL 24 HOUR AND c.status = 'blocked'";
    $convWhere   = "cv.converted_at >= NOW() - INTERVAL 24 HOUR AND cv.is_fraud = 1 AND cv.hide_reason LIKE '%_blocked%'";
    $clickParams = $convParams = [];
}
if ($affId > 0)   { $clickWhere .= " AND c.affiliate_id=?"; $clickParams[] = $affId; }
if ($offerId > 0) { $clickWhere .= " AND c.offer_id=?";     $clickParams[] = $offerId; }
if ($affId > 0)   { $convWhere  .= " AND cv.affiliate_id=?"; $convParams[]  = $affId; }
if ($offerId > 0) { $convWhere  .= " AND cv.offer_id=?";     $convParams[]  = $offerId; }

if (($_GET['export'] ?? '') === 'csv') {
    $type = $_GET['type'] ?? 'clicks';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="auto-block-report-'.$type.'-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    if ($type === 'conversions') {
        fputcsv($f, ['ID','IP','Offer','Affiliate','Payout','Revenue','Status','Block Reason','Converted At']);
        $exp = Database::fetchAll("SELECT cv.id, cv.ip_address, o.name AS offer_name, a.affiliate_code, cv.payout, cv.revenue, cv.status, cv.hide_reason, cv.converted_at FROM conversions cv LEFT JOIN offers o ON o.id=cv.offer_id LEFT JOIN affiliates a ON a.id=cv.affiliate_id WHERE $convWhere ORDER BY cv.converted_at DESC LIMIT 5000", $convParams) ?: [];
        foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['offer_name'],$r['affiliate_code'],number_format($r['payout'],4),number_format($r['revenue'],4),$r['status'],$r['hide_reason'],$r['converted_at']]);
    } else {
        fputcsv($f, ['ID','IP','User Agent','Offer','Affiliate','Score','Clicked At']);
        $exp = Database::fetchAll("SELECT c.id, c.ip_address, c.user_agent, o.name AS offer_name, a.affiliate_code, c.fraud_score, c.clicked_at FROM clicks c LEFT JOIN offers o ON o.id=c.offer_id LEFT JOIN affiliates a ON a.id=c.affiliate_id WHERE $clickWhere ORDER BY c.clicked_at DESC LIMIT 5000", $clickParams) ?: [];
        foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['user_agent'],$r['offer_name'],$r['affiliate_code'],$r['fraud_score'],$r['clicked_at']]);
    }
    fclose($f); exit;
}

$recentBlockedClicks = Database::fetchAll(
    "SELECT c.id, c.ip_address, c.user_agent, c.clicked_at, c.offer_id,
            o.name AS offer_name, a.affiliate_code, c.fraud_score
     FROM clicks c
     LEFT JOIN offers o ON o.id = c.offer_id
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     WHERE $clickWhere
     ORDER BY c.clicked_at DESC LIMIT 200", $clickParams
) ?: [];

$recentBlockedConversions = Database::fetchAll(
    "SELECT cv.id, cv.ip_address, cv.payout, cv.revenue, cv.status,
            cv.converted_at, o.name AS offer_name, a.affiliate_code, cv.hide_reason, cv.fraud_score
     FROM conversions cv
     LEFT JOIN offers o ON o.id = cv.offer_id
     LEFT JOIN affiliates a ON a.id = cv.affiliate_id
     WHERE $convWhere
     ORDER BY cv.converted_at DESC LIMIT 100", $convParams
) ?: [];

$stats = [
    'blocked_clicks' => Database::fetchOne("SELECT COUNT(*) AS c FROM clicks c WHERE $clickWhere", $clickParams)['c'] ?? 0,
    'blocked_convs'  => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions cv WHERE $convWhere", $convParams)['c'] ?? 0,
];

require BASE_PATH . '/views/admin/fraud_center/auto_block_report.php';
