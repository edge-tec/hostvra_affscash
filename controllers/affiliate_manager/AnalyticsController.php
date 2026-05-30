<?php
Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('view_offer_performance');
$pageTitle = 'Traffic Analytics';

$from    = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)Helpers::get('offer_id');
$selAffId= (int)Helpers::get('affiliate_id');

// ─── Managed affiliate IDs ────────────────────────────────────────────────────
$affIds = Auth::managerAffiliateIds();
if ($selAffId && in_array($selAffId, $affIds)) {
    $scopedAffIds = [$selAffId];
} else {
    $scopedAffIds = $affIds;
}

if (empty($scopedAffIds)) {
    $affWhere = '1=0';
    $affParams = [];
} else {
    $affWhere  = 'affiliate_id IN (' . implode(',', array_fill(0, count($scopedAffIds), '?')) . ')';
    $affParams = $scopedAffIds;
}

// ─── Click WHERE clause ───────────────────────────────────────────────────────
$clickBase = "clicked_at BETWEEN ? AND ?";
$clickBaseParams = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($offerId) { $clickBase .= " AND offer_id=?"; $clickBaseParams[] = $offerId; }

if (!empty($affParams)) {
    $where  = $clickBase . " AND $affWhere";
    $params = array_merge($clickBaseParams, $affParams);
} else {
    $where  = '1=0';
    $params = [];
}

// ─── Conversion WHERE clause ──────────────────────────────────────────────────
$convBase = "converted_at BETWEEN ? AND ? AND is_hidden=0";
$convBaseParams = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($offerId) { $convBase .= " AND offer_id=?"; $convBaseParams[] = $offerId; }

if (!empty($affParams)) {
    $convWhere  = $convBase . " AND $affWhere";
    $convParams = array_merge($convBaseParams, $affParams);
} else {
    $convWhere  = '1=0';
    $convParams = [];
}

// ─── Stats_daily WHERE clause ─────────────────────────────────────────────────
$statsBase = "stat_date BETWEEN ? AND ?";
$statsBaseParams = [$from, $to];
if ($offerId) { $statsBase .= " AND offer_id=?"; $statsBaseParams[] = $offerId; }

if (!empty($affParams)) {
    $statsWhere  = $statsBase . " AND $affWhere";
    $statsParams = array_merge($statsBaseParams, $affParams);
} else {
    $statsWhere  = '1=0';
    $statsParams = [];
}

// ═════════════════════════════════════════════════════════════════════════════
// CLICK-BASED QUERIES
// ═════════════════════════════════════════════════════════════════════════════

$summary = empty($affParams) ? [] : (Database::fetchOne(
    "SELECT
        COUNT(*) as total_clicks,
        SUM(is_unique) as unique_clicks,
        SUM(CASE WHEN status='valid' THEN 1 ELSE 0 END) as valid_clicks,
        SUM(CASE WHEN status='duplicate' THEN 1 ELSE 0 END) as duplicate_clicks,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked_clicks,
        SUM(is_fraud) as fraud_clicks,
        COUNT(DISTINCT ip_address) as unique_ips,
        COUNT(DISTINCT affiliate_id) as active_affiliates
     FROM clicks WHERE $where", $params
) ?? []);

$trend = empty($affParams) ? [] : Database::fetchAll(
    "SELECT DATE(clicked_at) as day,
        COUNT(*) as total,
        SUM(is_unique) as unique_c,
        SUM(is_fraud) as fraud,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked
     FROM clicks WHERE $where
     GROUP BY DATE(clicked_at) ORDER BY day ASC", $params
);

$byCountry = empty($affParams) ? [] : Database::fetchAll(
    "SELECT country, COUNT(*) as clicks, SUM(is_unique) as unique_c, SUM(is_fraud) as fraud
     FROM clicks WHERE $where AND country != ''
     GROUP BY country ORDER BY clicks DESC LIMIT 20", $params
);

$byDevice = empty($affParams) ? [] : Database::fetchAll(
    "SELECT device_type, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE $where
     GROUP BY device_type ORDER BY clicks DESC", $params
);

$byOs = empty($affParams) ? [] : Database::fetchAll(
    "SELECT os, COUNT(*) as clicks
     FROM clicks WHERE $where AND os != ''
     GROUP BY os ORDER BY clicks DESC LIMIT 15", $params
);

$byBrowser = empty($affParams) ? [] : Database::fetchAll(
    "SELECT browser, COUNT(*) as clicks
     FROM clicks WHERE $where AND browser != ''
     GROUP BY browser ORDER BY clicks DESC LIMIT 15", $params
);

// Hourly distribution
$hourlyDate = ($from === $to) ? $from : date('Y-m-d');
$hourlyAffWhere = empty($affParams) ? '1=0' : $affWhere;
$hourlyParams   = array_merge([$hourlyDate], $affParams);
$hourlyOfferFilter = '';
if ($offerId) { $hourlyOfferFilter .= " AND offer_id=?"; $hourlyParams[] = $offerId; }

$hourly = empty($affParams) ? [] : Database::fetchAll(
    "SELECT HOUR(clicked_at) as hour, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE DATE(clicked_at)=? AND $hourlyAffWhere$hourlyOfferFilter
     GROUP BY HOUR(clicked_at) ORDER BY hour", $hourlyParams
);

$hourlyFull       = array_fill(0, 24, 0);
$hourlyUniqueFull = array_fill(0, 24, 0);
foreach ($hourly as $h) {
    $hourlyFull[(int)$h['hour']]       = (int)$h['clicks'];
    $hourlyUniqueFull[(int)$h['hour']] = (int)$h['unique_c'];
}

$byOffer = empty($affParams) ? [] : Database::fetchAll(
    "SELECT o.name, c.offer_id, COUNT(*) as clicks, SUM(c.is_unique) as unique_c, SUM(c.is_fraud) as fraud
     FROM clicks c JOIN offers o ON o.id=c.offer_id WHERE $where
     GROUP BY c.offer_id ORDER BY clicks DESC LIMIT 10", $params
);

$byAffiliate = empty($affParams) ? [] : Database::fetchAll(
    "SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code,
            COUNT(*) as clicks, SUM(c.is_unique) as unique_c, SUM(c.is_fraud) as fraud
     FROM clicks c
     JOIN affiliates af ON af.id=c.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE $where
     GROUP BY c.affiliate_id ORDER BY clicks DESC LIMIT 10", $params
);

$byReferer = empty($affParams) ? [] : Database::fetchAll(
    "SELECT
        CASE
            WHEN referer = '' OR referer IS NULL THEN 'Direct'
            ELSE SUBSTRING_INDEX(REPLACE(REPLACE(referer,'https://',''),'http://',''),'/',1)
        END as source,
        COUNT(*) as clicks,
        SUM(is_unique) as unique_c
     FROM clicks WHERE $where
     GROUP BY source ORDER BY clicks DESC LIMIT 10", $params
);

// ═════════════════════════════════════════════════════════════════════════════
// CONVERSION-BASED QUERIES
// ═════════════════════════════════════════════════════════════════════════════

$convSummary = empty($affParams) ? [] : (Database::fetchOne(
    "SELECT
        COUNT(*) as total_conv,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(payout) as total_payout
     FROM conversions WHERE $convWhere", $convParams
) ?? []);

$dailyConv = empty($affParams) ? [] : Database::fetchAll(
    "SELECT
        DATE(converted_at) as day,
        COUNT(*) as total,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(payout) as payout
     FROM conversions WHERE $convWhere
     GROUP BY day ORDER BY day ASC", $convParams
);

$convByStatus = empty($affParams) ? [] : Database::fetchAll(
    "SELECT status, COUNT(*) as cnt, SUM(payout) as payout
     FROM conversions WHERE $convWhere
     GROUP BY status", $convParams
);

// ═════════════════════════════════════════════════════════════════════════════
// WEEKLY / MONTHLY / OFFER QUERIES — real-time direct from clicks+conversions
// ═════════════════════════════════════════════════════════════════════════════

// --- Weekly (last 12 weeks) ---
$weeklyClicksRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT YEARWEEK(clicked_at,1) as yw, MIN(DATE(clicked_at)) as week_start,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) AND $affWhere
     GROUP BY yw ORDER BY yw ASC", $affParams
);
$weeklyConvRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT YEARWEEK(converted_at,1) as yw,
            COUNT(*) as conversions, SUM(payout) as payout
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) AND is_hidden=0 AND $affWhere
     GROUP BY yw ORDER BY yw ASC", $affParams
);
$wConvByYw   = [];
foreach ($weeklyConvRaw as $r) $wConvByYw[$r['yw']] = $r;
$wClicksByYw = [];
foreach ($weeklyClicksRaw as $r) $wClicksByYw[$r['yw']] = $r;

// Union of weeks from BOTH clicks and conversions so the chart always
// shows data even when one source has no records for a given week.
$allYws = array_unique(array_merge(
    array_column($weeklyClicksRaw, 'yw'),
    array_column($weeklyConvRaw,   'yw')
));
sort($allYws);

$weeklyData = [];
foreach ($allYws as $yw) {
    $cl = $wClicksByYw[$yw] ?? null;
    $cv = $wConvByYw[$yw]   ?? [];

    // Derive the Monday date from the YEARWEEK number when no click record exists
    if ($cl) {
        $weekStart = $cl['week_start'];
    } else {
        $yr        = (int)substr((string)$yw, 0, 4);
        $wk        = (int)substr((string)$yw, 4);
        $weekStart = date('Y-m-d', strtotime($yr . 'W' . sprintf('%02d', $wk)));
    }

    $clicks      = (int)($cl['clicks']       ?? 0);
    $conversions = (int)($cv['conversions']  ?? 0);
    $weeklyData[] = [
        'yw'          => $yw,
        'week_start'  => $weekStart,
        'clicks'      => $clicks,
        'uclicks'     => (int)($cl['uclicks'] ?? 0),
        'conversions' => $conversions,
        'payout'      => (float)($cv['payout'] ?? 0),
        'cr'          => $clicks > 0 ? round($conversions / $clicks * 100, 2) : 0,
    ];
}

// --- Monthly (last 6 months) ---
$monthlyClicksRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT DATE_FORMAT(clicked_at,'%Y-%m') as month,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND $affWhere
     GROUP BY month ORDER BY month ASC", $affParams
);
$monthlyConvRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT DATE_FORMAT(converted_at,'%Y-%m') as month,
            COUNT(*) as conversions, SUM(payout) as payout
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND is_hidden=0 AND $affWhere
     GROUP BY month ORDER BY month ASC", $affParams
);
$mConvByMonth = [];
foreach ($monthlyConvRaw as $r) $mConvByMonth[$r['month']] = $r;
$monthlyData = [];
foreach ($monthlyClicksRaw as $r) {
    $cv = $mConvByMonth[$r['month']] ?? [];
    $monthlyData[] = [
        'month'       => $r['month'],
        'clicks'      => (int)$r['clicks'],
        'uclicks'     => (int)$r['uclicks'],
        'conversions' => (int)($cv['conversions'] ?? 0),
        'payout'      => (float)($cv['payout'] ?? 0),
    ];
}

// --- Top offers ---
$toClicksRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT c.offer_id, o.name, COUNT(*) as clicks, SUM(c.is_unique) as uclicks
     FROM clicks c JOIN offers o ON o.id=c.offer_id
     WHERE $where GROUP BY c.offer_id ORDER BY clicks DESC LIMIT 10", $params
);
$toConvRaw = empty($affParams) ? [] : Database::fetchAll(
    "SELECT offer_id, COUNT(*) as conversions,
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
            SUM(payout) as payout
     FROM conversions WHERE $convWhere GROUP BY offer_id", $convParams
);
$toCvByOffer = [];
foreach ($toConvRaw as $r) $toCvByOffer[(int)$r['offer_id']] = $r;
$topOffers = [];
foreach ($toClicksRaw as $r) {
    $cv  = $toCvByOffer[(int)$r['offer_id']] ?? [];
    $cl  = (int)$r['clicks'];
    $cn  = (int)($cv['conversions'] ?? 0);
    $topOffers[] = [
        'id'          => $r['offer_id'],
        'name'        => $r['name'],
        'clicks'      => $cl,
        'uclicks'     => (int)$r['uclicks'],
        'conversions' => $cn,
        'approved'    => (int)($cv['approved'] ?? 0),
        'payout'      => (float)($cv['payout'] ?? 0),
        'cr'          => $cl > 0 ? round($cn / $cl * 100, 2) : 0,
    ];
}

// ─── Filter dropdowns ─────────────────────────────────────────────────────────
$offersList = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
$affiliatesList = empty($affIds) ? [] : Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' (',af.affiliate_code,')') as label
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE af.id IN (" . implode(',', array_fill(0, count($affIds), '?')) . ")
     ORDER BY u.first_name", $affIds
);

require BASE_PATH . '/views/affiliate_manager/analytics.php';
