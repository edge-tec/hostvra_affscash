<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

// ── Enable / Disable toggle + Auto-Block settings ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(Helpers::postRaw('_token'));
    $cfg = Config::get('config') ?? [];

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_fraud') {
        $cfg['fraud']['enabled'] = (int)($_POST['enabled'] ?? 0);
        Config::write('config', $cfg);
        header('Location: /admin/fraud-center/live-monitor');
        exit;
    }

    if ($action === 'save_auto_block') {
        $cfg['fraud']['auto_block']['enabled']   = (int)($_POST['auto_block_enabled'] ?? 0);
        $threshold = max(1, min(100, (int)($_POST['auto_block_threshold'] ?? 70)));
        $cfg['fraud']['auto_block']['threshold'] = $threshold;
        Config::write('config', $cfg);
        header('Location: /admin/fraud-center/live-monitor#auto-block');
        exit;
    }
}

$fraudEnabled  = (bool)(Config::get('config', 'fraud.enabled') ?? 1); // default ON
$autoBlockCfg  = Config::get('config', 'fraud.auto_block') ?? [];
$autoBlockOn   = (bool)($autoBlockCfg['enabled']   ?? 0);
$autoBlockThr  = (int) ($autoBlockCfg['threshold'] ?? 70);

$affId   = (int)($_GET['affiliate_id'] ?? 0);
$offerId = (int)($_GET['offer_id'] ?? 0);

$affiliateList = Database::fetchAll("SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code") ?: [];
$offerList     = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

// Default window is "last 60 minutes" so the page works as a live monitor
// out of the box. If the user supplies from/to, those override the default.
$_lmHasRange = (Helpers::get('from') !== null && Helpers::get('from') !== '')
            || (Helpers::get('to')   !== null && Helpers::get('to')   !== '');
if ($_lmHasRange) {
    $dr = fraud_date_range();
    $clickWhere = "c.clicked_at BETWEEN ? AND ?";
    $convWhere  = "cv.converted_at BETWEEN ? AND ?";
    $clickParams = $convParams = [$dr['date_from'], $dr['date_to']];
} else {
    // For the filter bar UI we still want the date inputs to show today's window.
    $dr = ['from' => date('Y-m-d'), 'to' => date('Y-m-d')];
    $clickWhere  = "c.clicked_at >= NOW() - INTERVAL 60 MINUTE";
    $convWhere   = "cv.converted_at >= NOW() - INTERVAL 60 MINUTE";
    $clickParams = $convParams = [];
}
if ($affId > 0)   { $clickWhere .= " AND c.affiliate_id=?"; $clickParams[] = $affId; }
if ($offerId > 0) { $clickWhere .= " AND c.offer_id=?";     $clickParams[] = $offerId; }
if ($affId > 0)   { $convWhere  .= " AND cv.affiliate_id=?"; $convParams[]  = $affId; }
if ($offerId > 0) { $convWhere  .= " AND cv.offer_id=?";     $convParams[]  = $offerId; }

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    $type = $_GET['type'] ?? 'clicks';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="live-monitor-'.$type.'-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    if ($type === 'conversions') {
        fputcsv($f, ['ID','IP','Offer','Affiliate','Payout','Revenue','Status','Converted At']);
        $exp = Database::fetchAll("SELECT cv.id, cv.ip_address, o.name AS offer_name, a.affiliate_code, cv.payout, cv.revenue, cv.status, cv.converted_at FROM conversions cv LEFT JOIN offers o ON o.id=cv.offer_id LEFT JOIN affiliates a ON a.id=cv.affiliate_id WHERE $convWhere ORDER BY cv.converted_at DESC LIMIT 5000", $convParams) ?: [];
        foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['offer_name'],$r['affiliate_code'],number_format($r['payout'],4),number_format($r['revenue'],4),$r['status'],$r['converted_at']]);
    } else {
        fputcsv($f, ['ID','IP','User Agent','Offer','Affiliate','Clicked At']);
        $exp = Database::fetchAll("SELECT c.id, c.ip_address, c.user_agent, o.name AS offer_name, a.affiliate_code, c.clicked_at FROM clicks c LEFT JOIN offers o ON o.id=c.offer_id LEFT JOIN affiliates a ON a.id=c.affiliate_id WHERE $clickWhere ORDER BY c.clicked_at DESC LIMIT 5000", $clickParams) ?: [];
        foreach ($exp as $r) fputcsv($f, [$r['id'],$r['ip_address'],$r['user_agent'],$r['offer_name'],$r['affiliate_code'],$r['clicked_at']]);
    }
    fclose($f); exit;
}

// Last-60-minutes click stream (sampled, max 200 rows)
$recentClicks = Database::fetchAll(
    "SELECT c.id, c.ip_address, c.user_agent, c.clicked_at, c.offer_id,
            o.name AS offer_name, a.affiliate_code
     FROM clicks c
     LEFT JOIN offers o ON o.id = c.offer_id
     LEFT JOIN affiliates a ON a.id = c.affiliate_id
     WHERE $clickWhere
     ORDER BY c.clicked_at DESC LIMIT 200", $clickParams
) ?: [];

// Last-60-minutes conversions
$recentConversions = Database::fetchAll(
    "SELECT cv.id, cv.ip_address, cv.payout, cv.revenue, cv.status,
            cv.converted_at, o.name AS offer_name, a.affiliate_code
     FROM conversions cv
     LEFT JOIN offers o ON o.id = cv.offer_id
     LEFT JOIN affiliates a ON a.id = cv.affiliate_id
     WHERE $convWhere
     ORDER BY cv.converted_at DESC LIMIT 100", $convParams
) ?: [];

// Top IPs last 60 min
$topIps = Database::fetchAll(
    "SELECT ip_address, COUNT(*) AS cnt
     FROM clicks
     WHERE clicked_at >= NOW() - INTERVAL 60 MINUTE AND ip_address IS NOT NULL
     GROUP BY ip_address ORDER BY cnt DESC LIMIT 20"
) ?: [];

// Top suspicious IPs: same IP >50 clicks in 60 min
$burstIps = array_filter($topIps, fn($r) => $r['cnt'] >= 50);

// Hourly click volume for chart (last 24h)
$hourlyClicks = Database::fetchAll(
    "SELECT DATE_FORMAT(clicked_at,'%H:00') AS hr, COUNT(*) AS cnt
     FROM clicks WHERE clicked_at >= NOW() - INTERVAL 24 HOUR
     GROUP BY hr ORDER BY hr ASC"
) ?: [];

// KPI counters
$stats = [
    'clicks_1h'   => Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE clicked_at >= NOW() - INTERVAL 60 MINUTE")['c'] ?? 0,
    'conv_1h'     => Database::fetchOne("SELECT COUNT(*) AS c FROM conversions WHERE converted_at >= NOW() - INTERVAL 60 MINUTE")['c'] ?? 0,
    'burst_ips'   => count($burstIps),
    'open_cases'  => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases WHERE status='open'")['c'] ?? 0,
];

require BASE_PATH . '/views/admin/fraud_center/live_monitor.php';
