<?php
Auth::check('affiliate');
$pageTitle = 'Traffic Analytics';

// ── Schema guard: ensure is_hidden column exists ───────────────────────────
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

$affId   = Auth::affiliateId();
$from    = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)Helpers::get('offer_id');

// ─── WHERE clauses ────────────────────────────────────────────────────────────
$where  = "clicked_at BETWEEN ? AND ? AND affiliate_id=?";
$params = [$from . ' 00:00:00', $to . ' 23:59:59', $affId];
if ($offerId) { $where .= " AND offer_id=?"; $params[] = $offerId; }

$convWhere  = "converted_at BETWEEN ? AND ? AND COALESCE(is_hidden,0)=0 AND affiliate_id=?";
$convParams = [$from . ' 00:00:00', $to . ' 23:59:59', $affId];
if ($offerId) { $convWhere .= " AND offer_id=?"; $convParams[] = $offerId; }

$statsWhere  = "stat_date BETWEEN ? AND ? AND affiliate_id=?";
$statsParams = [$from, $to, $affId];
if ($offerId) { $statsWhere .= " AND offer_id=?"; $statsParams[] = $offerId; }

// ═════════════════════════════════════════════════════════════════════════════
// CLICK-BASED QUERIES
// ═════════════════════════════════════════════════════════════════════════════

$summary = Database::fetchOne(
    "SELECT
        COUNT(*) as total_clicks,
        SUM(is_unique) as unique_clicks,
        SUM(CASE WHEN status='valid' THEN 1 ELSE 0 END) as valid_clicks,
        SUM(CASE WHEN status='duplicate' THEN 1 ELSE 0 END) as duplicate_clicks,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked_clicks,
        SUM(is_fraud) as fraud_clicks,
        COUNT(DISTINCT ip_address) as unique_ips
     FROM clicks WHERE $where", $params
) ?? [];

$trend = Database::fetchAll(
    "SELECT DATE(clicked_at) as day,
        COUNT(*) as total,
        SUM(is_unique) as unique_c,
        SUM(is_fraud) as fraud,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked
     FROM clicks WHERE $where
     GROUP BY DATE(clicked_at) ORDER BY day ASC", $params
);

$byCountry = Database::fetchAll(
    "SELECT country, COUNT(*) as clicks, SUM(is_unique) as unique_c, SUM(is_fraud) as fraud
     FROM clicks WHERE $where AND country != ''
     GROUP BY country ORDER BY clicks DESC LIMIT 20", $params
);

$byDevice = Database::fetchAll(
    "SELECT device_type, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE $where
     GROUP BY device_type ORDER BY clicks DESC", $params
);

$byOs = Database::fetchAll(
    "SELECT os, COUNT(*) as clicks
     FROM clicks WHERE $where AND os != ''
     GROUP BY os ORDER BY clicks DESC LIMIT 15", $params
);

$byBrowser = Database::fetchAll(
    "SELECT browser, COUNT(*) as clicks
     FROM clicks WHERE $where AND browser != ''
     GROUP BY browser ORDER BY clicks DESC LIMIT 15", $params
);

// Hourly distribution
$hourlyDate = ($from === $to) ? $from : date('Y-m-d');
$hourlyParams = [$hourlyDate, $affId];
$hourlyOfferFilter = '';
if ($offerId) { $hourlyOfferFilter .= " AND offer_id=?"; $hourlyParams[] = $offerId; }

$hourly = Database::fetchAll(
    "SELECT HOUR(clicked_at) as hour, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE DATE(clicked_at)=? AND affiliate_id=?$hourlyOfferFilter
     GROUP BY HOUR(clicked_at) ORDER BY hour", $hourlyParams
);

$hourlyFull       = array_fill(0, 24, 0);
$hourlyUniqueFull = array_fill(0, 24, 0);
foreach ($hourly as $h) {
    $hourlyFull[(int)$h['hour']]       = (int)$h['clicks'];
    $hourlyUniqueFull[(int)$h['hour']] = (int)$h['unique_c'];
}

// Hourly CR% for single-day view ("Today")
$hourlyCrLabels = [];
$hourlyCrValues = [];
if ($from === $to) {
    $hcP = [$hourlyDate, $affId];
    $hcOf = '';
    if ($offerId) { $hcOf = ' AND offer_id=?'; $hcP[] = $offerId; }
    $hourlyConvRaw = Database::fetchAll(
        "SELECT HOUR(converted_at) as hour,
                SUM(CASE WHEN status IN ('approved','pending') THEN 1 ELSE 0 END) as total
         FROM conversions
         WHERE DATE(converted_at)=? AND COALESCE(is_hidden,0)=0 AND affiliate_id=?$hcOf
         GROUP BY HOUR(converted_at)", $hcP
    );
    $hourlyConvFull = array_fill(0, 24, 0);
    foreach ($hourlyConvRaw as $r) $hourlyConvFull[(int)$r['hour']] = (int)$r['total'];
    for ($h = 0; $h < 24; $h++) {
        $hourlyCrLabels[] = sprintf('%02d:00', $h);
        $hourlyCrValues[] = $hourlyFull[$h] > 0
            ? round($hourlyConvFull[$h] / $hourlyFull[$h] * 100, 2)
            : 0;
    }
}

$byOffer = Database::fetchAll(
    "SELECT o.name, c.offer_id, COUNT(*) as clicks, SUM(c.is_unique) as unique_c, SUM(c.is_fraud) as fraud
     FROM clicks c JOIN offers o ON o.id=c.offer_id WHERE $where
     GROUP BY c.offer_id ORDER BY clicks DESC LIMIT 10", $params
);

$byReferer = Database::fetchAll(
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

$convSummary = Database::fetchOne(
    "SELECT
        SUM(CASE WHEN status IN ('approved','pending') THEN 1 ELSE 0 END) as total_conv,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('approved','pending') THEN payout ELSE 0 END) as total_payout
     FROM conversions WHERE $convWhere", $convParams
) ?? [];

$dailyConv = Database::fetchAll(
    "SELECT
        DATE(converted_at) as day,
        SUM(CASE WHEN status IN ('approved','pending') THEN 1 ELSE 0 END) as total,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('approved','pending') THEN payout ELSE 0 END) as payout
     FROM conversions WHERE $convWhere
     GROUP BY day ORDER BY day ASC", $convParams
);

$convByStatus = Database::fetchAll(
    "SELECT status, COUNT(*) as cnt, SUM(payout) as payout
     FROM conversions WHERE $convWhere
     GROUP BY status", $convParams
);

// ═════════════════════════════════════════════════════════════════════════════
// WEEKLY / MONTHLY / OFFER QUERIES — real-time direct from clicks+conversions
// ═════════════════════════════════════════════════════════════════════════════

// --- Weekly (last 12 weeks) ---
$weeklyClicksRaw = Database::fetchAll(
    "SELECT YEARWEEK(clicked_at,1) as yw, MIN(DATE(clicked_at)) as week_start,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) AND affiliate_id=?
     GROUP BY yw ORDER BY yw ASC", [$affId]
);
$weeklyConvRaw = Database::fetchAll(
    "SELECT YEARWEEK(converted_at,1) as yw,
            COUNT(*) as conversions, SUM(payout) as payout
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) AND COALESCE(is_hidden,0)=0 AND affiliate_id=?
     GROUP BY yw ORDER BY yw ASC", [$affId]
);
$wConvByYw = [];
foreach ($weeklyConvRaw as $r) $wConvByYw[$r['yw']] = $r;
$weeklyData = [];
foreach ($weeklyClicksRaw as $r) {
    $cv = $wConvByYw[$r['yw']] ?? [];
    $weeklyData[] = [
        'yw'          => $r['yw'],
        'week_start'  => $r['week_start'],
        'clicks'      => (int)$r['clicks'],
        'uclicks'     => (int)$r['uclicks'],
        'conversions' => (int)($cv['conversions'] ?? 0),
        'payout'      => (float)($cv['payout'] ?? 0),
    ];
}

// --- Monthly (last 6 months) ---
$monthlyClicksRaw = Database::fetchAll(
    "SELECT DATE_FORMAT(clicked_at,'%Y-%m') as month,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND affiliate_id=?
     GROUP BY month ORDER BY month ASC", [$affId]
);
$monthlyConvRaw = Database::fetchAll(
    "SELECT DATE_FORMAT(converted_at,'%Y-%m') as month,
            COUNT(*) as conversions, SUM(payout) as payout
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND COALESCE(is_hidden,0)=0 AND affiliate_id=?
     GROUP BY month ORDER BY month ASC", [$affId]
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
$toClicksRaw = Database::fetchAll(
    "SELECT c.offer_id, o.name, COUNT(*) as clicks, SUM(c.is_unique) as uclicks
     FROM clicks c JOIN offers o ON o.id=c.offer_id
     WHERE $where GROUP BY c.offer_id ORDER BY clicks DESC LIMIT 10", $params
);
$toConvRaw = Database::fetchAll(
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

// ─── Filter dropdown ─────────────────────────────────────────────────────────
$offersList = Database::fetchAll(
    "SELECT o.id, o.name FROM offers o
     JOIN affiliate_offers ao ON ao.offer_id=o.id
     WHERE ao.affiliate_id=? AND ao.status='approved'
     ORDER BY o.name", [$affId]
);

require BASE_PATH . '/views/affiliate/analytics/index.php';
