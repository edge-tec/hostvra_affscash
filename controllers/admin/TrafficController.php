<?php
Auth::check('admin');
$pageTitle = 'Traffic Statistics';

// Ensure is_hidden column exists (migration)
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

$from    = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)Helpers::get('offer_id');
$affId   = (int)Helpers::get('affiliate_id');

// ─── Click-based WHERE clause ────────────────────────────────────────────────
$where  = "clicked_at BETWEEN ? AND ?";
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($offerId) { $where .= " AND offer_id=?";     $params[] = $offerId; }
if ($affId)   { $where .= " AND affiliate_id=?"; $params[] = $affId; }

// ─── Conversion-based WHERE clause ───────────────────────────────────────────
$convWhere  = "converted_at BETWEEN ? AND ? AND COALESCE(is_hidden,0)=0";
$convParams = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($offerId) { $convWhere .= " AND offer_id=?";     $convParams[] = $offerId; }
if ($affId)   { $convWhere .= " AND affiliate_id=?"; $convParams[] = $affId; }

// ─── stats_daily WHERE clause ─────────────────────────────────────────────────
$statsWhere  = "stat_date BETWEEN ? AND ?";
$statsParams = [$from, $to];
if ($offerId) { $statsWhere .= " AND offer_id=?";     $statsParams[] = $offerId; }
if ($affId)   { $statsWhere .= " AND affiliate_id=?"; $statsParams[] = $affId; }

// ═════════════════════════════════════════════════════════════════════════════
// CLICK-BASED QUERIES
// ═════════════════════════════════════════════════════════════════════════════

// Summary stats
$summary = Database::fetchOne(
    "SELECT
        COUNT(*) as total_clicks,
        SUM(is_unique) as unique_clicks,
        SUM(CASE WHEN status='valid' THEN 1 ELSE 0 END) as valid_clicks,
        SUM(CASE WHEN status='duplicate' THEN 1 ELSE 0 END) as duplicate_clicks,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked_clicks,
        SUM(is_fraud) as fraud_clicks,
        COUNT(DISTINCT ip_address) as unique_ips,
        COUNT(DISTINCT affiliate_id) as unique_affiliates
     FROM clicks WHERE $where", $params
) ?? [];

// Daily click trend
$trend = Database::fetchAll(
    "SELECT DATE(clicked_at) as day,
        COUNT(*) as total,
        SUM(is_unique) as unique_c,
        SUM(is_fraud) as fraud,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked
     FROM clicks WHERE $where
     GROUP BY DATE(clicked_at) ORDER BY day ASC", $params
);

// By country (top 20)
$byCountry = Database::fetchAll(
    "SELECT country, COUNT(*) as clicks, SUM(is_unique) as unique_c, SUM(is_fraud) as fraud
     FROM clicks WHERE $where AND country != ''
     GROUP BY country ORDER BY clicks DESC LIMIT 20", $params
);

// By device
$byDevice = Database::fetchAll(
    "SELECT device_type, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE $where
     GROUP BY device_type ORDER BY clicks DESC", $params
);

// By OS
$byOs = Database::fetchAll(
    "SELECT os, COUNT(*) as clicks
     FROM clicks WHERE $where AND os != ''
     GROUP BY os ORDER BY clicks DESC LIMIT 15", $params
);

// By browser
$byBrowser = Database::fetchAll(
    "SELECT browser, COUNT(*) as clicks
     FROM clicks WHERE $where AND browser != ''
     GROUP BY browser ORDER BY clicks DESC LIMIT 15", $params
);

// Hourly distribution — includes unique clicks, filtered to today or a single selected day
$hourlyDate = ($from === $to) ? $from : date('Y-m-d');
$hourlyParams = [$hourlyDate];
$hourlyOfferFilter = '';
if ($offerId) { $hourlyOfferFilter .= " AND offer_id=?"; $hourlyParams[] = $offerId; }
if ($affId)   { $hourlyOfferFilter .= " AND affiliate_id=?"; $hourlyParams[] = $affId; }

$hourly = Database::fetchAll(
    "SELECT HOUR(clicked_at) as hour, COUNT(*) as clicks, SUM(is_unique) as unique_c
     FROM clicks WHERE DATE(clicked_at)=?$hourlyOfferFilter
     GROUP BY HOUR(clicked_at) ORDER BY hour", $hourlyParams
);

// Fill missing hours with 0
$hourlyFull       = array_fill(0, 24, 0);
$hourlyUniqueFull = array_fill(0, 24, 0);
foreach ($hourly as $h) {
    $hourlyFull[(int)$h['hour']]       = (int)$h['clicks'];
    $hourlyUniqueFull[(int)$h['hour']] = (int)$h['unique_c'];
}

// By offer (top 10)
$byOffer = Database::fetchAll(
    "SELECT o.name, c.offer_id, COUNT(*) as clicks, SUM(c.is_unique) as unique_c, SUM(c.is_fraud) as fraud
     FROM clicks c JOIN offers o ON o.id=c.offer_id WHERE $where
     GROUP BY c.offer_id ORDER BY clicks DESC LIMIT 10", $params
);

// By affiliate (top 10)
$byAffiliate = Database::fetchAll(
    "SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code,
            COUNT(*) as clicks, SUM(c.is_unique) as unique_c, SUM(c.is_fraud) as fraud
     FROM clicks c
     JOIN affiliates af ON af.id=c.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE $where
     GROUP BY c.affiliate_id, af.affiliate_code, u.first_name, u.last_name ORDER BY clicks DESC LIMIT 10", $params
);

// Top ISPs
$byIsp = Database::fetchAll(
    "SELECT isp, COUNT(*) as clicks, SUM(is_fraud) as fraud
     FROM clicks WHERE $where AND isp != ''
     GROUP BY isp ORDER BY clicks DESC LIMIT 10", $params
);

// Traffic by referer / source (top 10)
$byReferer = Database::fetchAll(
    "SELECT
        CASE
            WHEN referer = '' OR referer IS NULL THEN 'Direct'
            ELSE SUBSTRING_INDEX(REPLACE(REPLACE(referer,'https://',''),'http://',''),'/',1)
        END as source,
        COUNT(*) as clicks,
        SUM(is_unique) as unique_c
     FROM clicks WHERE $where
     GROUP BY CASE WHEN referer = '' OR referer IS NULL THEN 'Direct' ELSE SUBSTRING_INDEX(REPLACE(REPLACE(referer,'https://',''),'http://',''),'/',1) END
     ORDER BY clicks DESC LIMIT 10", $params
);

// ═════════════════════════════════════════════════════════════════════════════
// CONVERSION-BASED QUERIES
// ═════════════════════════════════════════════════════════════════════════════

// Overall conversion summary
$convSummary = Database::fetchOne(
    "SELECT
        COUNT(*) as total_conv,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(payout)  as total_payout,
        SUM(revenue) as total_revenue
     FROM conversions WHERE $convWhere", $convParams
) ?? [];

// Daily conversion trend (last 30 days or date range)
$dailyConv = Database::fetchAll(
    "SELECT
        DATE(converted_at) as day,
        COUNT(*) as total,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) as pending,
        SUM(payout)  as payout,
        SUM(revenue) as revenue
     FROM conversions WHERE $convWhere
     GROUP BY day ORDER BY day ASC", $convParams
);

// Conversion status breakdown
$convByStatus = Database::fetchAll(
    "SELECT status, COUNT(*) as cnt, SUM(payout) as payout
     FROM conversions WHERE $convWhere
     GROUP BY status", $convParams
);

// ═════════════════════════════════════════════════════════════════════════════
// WEEKLY / MONTHLY / OFFER QUERIES — real-time direct from clicks+conversions
// (replaces stats_daily cache so data is always current, no cron required)
// ═════════════════════════════════════════════════════════════════════════════

// --- Weekly (last 12 weeks) ---
$weeklyClicksRaw = Database::fetchAll(
    "SELECT YEARWEEK(clicked_at,1) as yw, MIN(DATE(clicked_at)) as week_start,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY)
     GROUP BY YEARWEEK(clicked_at,1) ORDER BY yw ASC"
);
$weeklyConvRaw = Database::fetchAll(
    "SELECT YEARWEEK(converted_at,1) as yw,
            COUNT(*) as conversions, SUM(payout) as payout, SUM(revenue) as revenue
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) AND COALESCE(is_hidden,0)=0
     GROUP BY YEARWEEK(converted_at,1) ORDER BY yw ASC"
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
        'revenue'     => (float)($cv['revenue'] ?? 0),
    ];
}

// --- Monthly (last 6 months) ---
$monthlyClicksRaw = Database::fetchAll(
    "SELECT DATE_FORMAT(clicked_at,'%Y-%m') as month,
            COUNT(*) as clicks, SUM(is_unique) as uclicks
     FROM clicks WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
     GROUP BY DATE_FORMAT(clicked_at,'%Y-%m') ORDER BY month ASC"
);
$monthlyConvRaw = Database::fetchAll(
    "SELECT DATE_FORMAT(converted_at,'%Y-%m') as month,
            COUNT(*) as conversions, SUM(payout) as payout, SUM(revenue) as revenue
     FROM conversions
     WHERE converted_at >= DATE_SUB(CURDATE(), INTERVAL 180 DAY) AND COALESCE(is_hidden,0)=0
     GROUP BY DATE_FORMAT(converted_at,'%Y-%m') ORDER BY month ASC"
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
        'revenue'     => (float)($cv['revenue'] ?? 0),
    ];
}

// --- Top offers — filter-aware, real-time ---
$toClicksRaw = Database::fetchAll(
    "SELECT c.offer_id, o.name, COUNT(*) as clicks, SUM(c.is_unique) as uclicks
     FROM clicks c JOIN offers o ON o.id=c.offer_id
     WHERE $where GROUP BY c.offer_id, o.name ORDER BY clicks DESC LIMIT 10", $params
);
$toConvRaw = Database::fetchAll(
    "SELECT offer_id, COUNT(*) as conversions,
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
            SUM(payout) as payout, SUM(revenue) as revenue
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
        'revenue'     => (float)($cv['revenue'] ?? 0),
        'cr'          => $cl > 0 ? round($cn / $cl * 100, 2) : 0,
    ];
}

// ═════════════════════════════════════════════════════════════════════════════
// FILTER DROPDOWNS
// ═════════════════════════════════════════════════════════════════════════════

$offersList     = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
$affiliatesList = Database::fetchAll(
    "SELECT af.id,
            CONCAT(u.first_name,' ',u.last_name,' (',af.affiliate_code,')') as label
     FROM affiliates af
     JOIN users u ON u.id=af.user_id
     WHERE u.status='active'
     ORDER BY u.first_name"
);

require BASE_PATH . '/views/admin/traffic/index.php';
