<?php
header('Content-Type: application/json');
if (!Auth::id() || Auth::role() !== 'affiliate') {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

// ── Schema guard: ensure columns used in this controller exist ─────────────
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN country VARCHAR(10) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN device_type VARCHAR(20) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN invoice_id INT UNSIGNED DEFAULT NULL"); } catch(Exception $e) {}

$affId  = Auth::affiliateId();
$action = Helpers::get('action') ?: '';

// ── Timezone: apply before any date() calls ────────────────────────────────
$_reqTz = Helpers::get('tz') ?: (Config::get('config','app.timezone') ?? 'UTC');
if (!in_array($_reqTz, timezone_identifiers_list())) {
    $_reqTz = Config::get('config','app.timezone') ?? 'UTC';
}
date_default_timezone_set($_reqTz);
// Sync MySQL session so DATE(), HOUR(), NOW() match PHP timezone
try {
    Database::getInstance()->exec("SET time_zone = '" . addslashes($_reqTz) . "'");
} catch (\Throwable $_tzEx) {
    try { Database::getInstance()->exec("SET time_zone = '" . date('P') . "'"); } catch (\Throwable $_) {}
}

// ── Filters ────────────────────────────────────────────────────────────────
$from    = Helpers::get('from')     ?: date('Y-m-d', strtotime('-29 days'));
$to      = Helpers::get('to')       ?: date('Y-m-d');
$offerId = (int)(Helpers::get('offer_id') ?? 0);
$country = trim(Helpers::get('country') ?? '');
$device  = trim(Helpers::get('device')  ?? '');

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

// ── Dynamic WHERE helpers ──────────────────────────────────────────────────
$clickP = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
$clickW = "affiliate_id=? AND clicked_at BETWEEN ? AND ?";
$convP  = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
$convW  = "affiliate_id=? AND converted_at BETWEEN ? AND ? AND COALESCE(is_hidden,0)=0";
$statsP = [$affId, $from, $to];
$statsW = "affiliate_id=? AND stat_date BETWEEN ? AND ?";

if ($offerId) {
    $clickW .= " AND offer_id=?"; $clickP[] = $offerId;
    $convW  .= " AND offer_id=?"; $convP[]  = $offerId;
    $statsW .= " AND offer_id=?"; $statsP[] = $offerId;
}
if ($country) {
    $clickW .= " AND country=?"; $clickP[] = $country;
    $convW  .= " AND country=?"; $convP[]  = $country;
}
if ($device) {
    $clickW .= " AND device_type=?"; $clickP[] = $device;
}

// ── Previous period (for % change) ────────────────────────────────────────
$days     = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
$prevFrom = date('Y-m-d', strtotime($from) - $days * 86400);
$prevTo   = date('Y-m-d', strtotime($from) - 86400);
$prevStatsP = [$affId, $prevFrom, $prevTo];
$prevStatsW = "affiliate_id=? AND stat_date BETWEEN ? AND ?";
if ($offerId) { $prevStatsW .= " AND offer_id=?"; $prevStatsP[] = $offerId; }

function safe_pct($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? 100 : 0;
    return round(($curr - $prev) / $prev * 100, 1);
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: stats — KPI totals + trend comparison
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'stats') {
    $cur  = Database::fetchOne("SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv, SUM(payout) as p FROM stats_daily WHERE $statsW", $statsP) ?? [];
    $prev = Database::fetchOne("SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv, SUM(payout) as p FROM stats_daily WHERE $prevStatsW", $prevStatsP) ?? [];
    $bal  = Database::fetchOne("SELECT balance FROM affiliates WHERE id=?", [$affId]);

    $clicks  = (int)($cur['c']  ?? 0);
    $unique  = (int)($cur['u']  ?? 0);
    $conv    = (int)($cur['cv'] ?? 0);
    $payout  = round((float)($cur['p'] ?? 0), 2);
    $cr      = $clicks > 0 ? round($conv / $clicks * 100, 2) : 0;
    $balance = round((float)($bal['balance'] ?? 0), 2);

    $pClicks = (int)($prev['c']  ?? 0);
    $pConv   = (int)($prev['cv'] ?? 0);
    $pPayout = (float)($prev['p'] ?? 0);

    // Impressions from clicks table (all clicks including non-unique)
    $imp = Database::fetchOne("SELECT COUNT(*) as cnt FROM clicks WHERE $clickW", $clickP);
    $impressions = (int)($imp['cnt'] ?? 0);

    // ── Fraud Conversion % — scoped to the logged-in affiliate ──────────────
    // Reads from conversions table (stats_daily lacks fraud_score). Same
    // date / offer / country filters as the rest of the dashboard so the
    // percentage tracks every other KPI's filter state.
    $fraudConvCur = 0; $totalConvForPct = 0; $fraudConvPrev = 0; $totalConvPrev = 0;
    try {
        $fcWhere  = ['c.affiliate_id=?', 'c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
        $fcParams = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
        if ($offerId) { $fcWhere[] = 'c.offer_id=?'; $fcParams[] = $offerId; }
        if ($country) { $fcWhere[] = 'c.country=?';  $fcParams[] = $country; }
        $fcW = implode(' AND ', $fcWhere);

        $fcCur = Database::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
             FROM conversions c WHERE $fcW",
            $fcParams
        );
        $fraudConvCur     = (int)($fcCur['fraud'] ?? 0);
        $totalConvForPct  = (int)($fcCur['total'] ?? 0);

        $fcPrevWhere  = ['c.affiliate_id=?', 'c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
        $fcPrevParams = [$affId, $prevFrom . ' 00:00:00', $prevTo . ' 23:59:59'];
        if ($offerId) { $fcPrevWhere[] = 'c.offer_id=?'; $fcPrevParams[] = $offerId; }
        if ($country) { $fcPrevWhere[] = 'c.country=?';  $fcPrevParams[] = $country; }
        $fcPrevW = implode(' AND ', $fcPrevWhere);
        $fcPrev = Database::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
             FROM conversions c WHERE $fcPrevW",
            $fcPrevParams
        );
        $fraudConvPrev = (int)($fcPrev['fraud'] ?? 0);
        $totalConvPrev = (int)($fcPrev['total'] ?? 0);
    } catch (\Throwable $e) {}

    $fraudConvPct      = $totalConvForPct > 0 ? round($fraudConvCur  / $totalConvForPct * 100, 2) : 0;
    $fraudConvPctPrev  = $totalConvPrev   > 0 ? round($fraudConvPrev / $totalConvPrev   * 100, 2) : 0;
    $fraudConvPctTrend = round($fraudConvPct - $fraudConvPctPrev, 1); // delta in pp

    echo json_encode([
        'clicks'         => $clicks,
        'unique'         => $unique,
        'conversions'    => $conv,
        'revenue'        => $payout,
        'cr'             => $cr,
        'balance'        => $balance,
        'impressions'    => $impressions,
        'fraud_conv'     => $fraudConvCur,
        'fraud_conv_pct' => $fraudConvPct,
        'trend' => [
            'clicks'         => safe_pct($clicks,  $pClicks),
            'conv'           => safe_pct($conv,    $pConv),
            'revenue'        => safe_pct($payout,  $pPayout),
            'fraud_conv_pct' => $fraudConvPctTrend,
        ],
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: trend — daily series for main line chart (hourly when from===to)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'trend') {
    // When a single day is selected, return hourly breakdown for intraday detail
    if ($from === $to) {
        $hW = ['affiliate_id=?', 'DATE(clicked_at)=?'];
        $hP = [$affId, $from];
        if ($offerId) { $hW[] = 'offer_id=?';  $hP[] = $offerId; }
        if ($country) { $hW[] = 'country=?';   $hP[] = $country; }
        if ($device)  { $hW[] = 'device_type=?'; $hP[] = $device; }
        $hWhere = implode(' AND ', $hW);

        $cvW = ['affiliate_id=?', 'DATE(converted_at)=?', 'COALESCE(is_hidden,0)=0'];
        $cvP = [$affId, $from];
        if ($offerId) { $cvW[] = 'offer_id=?'; $cvP[] = $offerId; }
        $cvWhere = implode(' AND ', $cvW);

        $clRows = Database::fetchAll(
            "SELECT HOUR(clicked_at) as h, COUNT(*) as c, SUM(is_unique) as u
             FROM clicks WHERE $hWhere GROUP BY HOUR(clicked_at)",
            $hP
        );
        $cvRows = Database::fetchAll(
            "SELECT HOUR(converted_at) as h, COUNT(*) as cv, SUM(payout) as p,
                    SUM(CASE WHEN COALESCE(fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
             FROM conversions WHERE $cvWhere GROUP BY HOUR(converted_at)",
            $cvP
        );

        $cMap = []; foreach ($clRows as $r) $cMap[(int)$r['h']] = $r;
        $cvMap = []; foreach ($cvRows as $r) $cvMap[(int)$r['h']] = $r;

        $labels = $clicks_data = $unique_data = $conv_data = $revenue_data = $fraud_data = [];
        for ($h = 0; $h < 24; $h++) {
            $cr  = $cMap[$h]  ?? [];
            $cvr = $cvMap[$h] ?? [];
            $labels[]       = sprintf('%02d:00', $h);
            $clicks_data[]  = (int)($cr['c']   ?? 0);
            $unique_data[]  = (int)($cr['u']   ?? 0);
            $conv_data[]    = (int)($cvr['cv'] ?? 0);
            $revenue_data[] = round((float)($cvr['p'] ?? 0), 2);
            $fraud_data[]   = (int)($cvr['fraud_cv'] ?? 0);
        }
        echo json_encode(compact('labels','clicks_data','unique_data','conv_data','revenue_data','fraud_data'));
        exit;
    }

    $rows = Database::fetchAll(
        "SELECT stat_date as d, SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv, SUM(payout) as p
         FROM stats_daily WHERE $statsW GROUP BY stat_date ORDER BY stat_date",
        $statsP
    );
    // Fill missing dates with zeros
    $map = [];
    foreach ($rows as $r) $map[$r['d']] = $r;

    // Per-day fraud counts overlaid from conversions table — same affiliate scope.
    $fraudByDay = [];
    try {
        $fcWhere  = ['c.affiliate_id=?', 'c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
        $fcParams = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
        if ($offerId) { $fcWhere[] = 'c.offer_id=?'; $fcParams[] = $offerId; }
        if ($country) { $fcWhere[] = 'c.country=?';  $fcParams[] = $country; }
        $fcW = implode(' AND ', $fcWhere);
        $fraudRows = Database::fetchAll(
            "SELECT DATE(c.converted_at) as d,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
             FROM conversions c WHERE $fcW GROUP BY DATE(c.converted_at)",
            $fcParams
        );
        foreach ($fraudRows as $fr) $fraudByDay[$fr['d']] = (int)($fr['fraud_cv'] ?? 0);
    } catch (\Throwable $_e) {}

    $labels = $clicks_data = $unique_data = $conv_data = $revenue_data = $fraud_data = [];
    $cur = strtotime($from);
    $end = strtotime($to);
    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $r = $map[$d] ?? [];
        $labels[]       = date('M j', $cur);
        $clicks_data[]  = (int)($r['c']  ?? 0);
        $unique_data[]  = (int)($r['u']  ?? 0);
        $conv_data[]    = (int)($r['cv'] ?? 0);
        $revenue_data[] = round((float)($r['p'] ?? 0), 2);
        $fraud_data[]   = (int)($fraudByDay[$d] ?? 0);
        $cur += 86400;
    }
    echo json_encode(compact('labels','clicks_data','unique_data','conv_data','revenue_data','fraud_data'));
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: hourly — today's clicks by hour
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'hourly') {
    $today   = date('Y-m-d');
    $hW      = "affiliate_id=? AND DATE(clicked_at)=?";
    $hP      = [$affId, $today];
    if ($offerId) { $hW .= " AND offer_id=?"; $hP[] = $offerId; }
    if ($country) { $hW .= " AND country=?";  $hP[] = $country; }
    if ($device)  { $hW .= " AND device_type=?"; $hP[] = $device; }
    $rows = Database::fetchAll(
        "SELECT HOUR(clicked_at) as h, COUNT(*) as cnt FROM clicks WHERE $hW GROUP BY HOUR(clicked_at)",
        $hP
    );
    $hourly = array_fill(0, 24, 0);
    foreach ($rows as $r) $hourly[(int)$r['h']] = (int)$r['cnt'];
    $labels = array_map(function($h){ return sprintf('%02d:00', $h); }, range(0,23));
    echo json_encode(['labels' => $labels, 'data' => array_values($hourly)]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: devices — device type breakdown
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'devices') {
    $rows = Database::fetchAll(
        "SELECT COALESCE(NULLIF(device_type,''),'Unknown') as label, COUNT(*) as cnt
         FROM clicks WHERE $clickW GROUP BY device_type ORDER BY cnt DESC",
        $clickP
    );
    echo json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: countries — top countries (clicks + conversions)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'countries') {
    $clRows = Database::fetchAll(
        "SELECT country, COUNT(*) as clicks, SUM(is_unique) as unique_c
         FROM clicks WHERE $clickW AND country!='' GROUP BY country ORDER BY clicks DESC LIMIT 12",
        $clickP
    );
    // Build an alias-qualified WHERE to avoid column ambiguity after the JOIN
    // (clicks also has affiliate_id, offer_id, country — must prefix with cv.)
    $cvJoinW = "cv.affiliate_id=? AND cv.converted_at BETWEEN ? AND ? AND COALESCE(cv.is_hidden,0)=0";
    $cvJoinP = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $cvJoinW .= " AND cv.offer_id=?"; $cvJoinP[] = $offerId; }
    if ($country) { $cvJoinW .= " AND COALESCE(NULLIF(cv.country,''), ck.country)=?"; $cvJoinP[] = $country; }
    $cvRows = Database::fetchAll(
        "SELECT COALESCE(NULLIF(cv.country,''), ck.country) as country, COUNT(*) as conv
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         WHERE $cvJoinW AND COALESCE(NULLIF(cv.country,''), ck.country) != ''
         GROUP BY COALESCE(NULLIF(cv.country,''), ck.country)",
        $cvJoinP
    );
    $cvMap = [];
    foreach ($cvRows as $r) $cvMap[$r['country']] = (int)$r['conv'];

    $out = [];
    foreach ($clRows as $r) {
        $out[] = [
            'country'  => $r['country'],
            'clicks'   => (int)$r['clicks'],
            'unique'   => (int)$r['unique_c'],
            'conv'     => $cvMap[$r['country']] ?? 0,
        ];
    }
    echo json_encode(['rows' => $out]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: offers — offer performance ranking
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'offers') {
    $rows = Database::fetchAll(
        "SELECT o.id, o.name, SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                SUM(sd.conversions) as conv, SUM(sd.payout) as payout
         FROM stats_daily sd JOIN offers o ON o.id=sd.offer_id
         WHERE $statsW GROUP BY sd.offer_id ORDER BY payout DESC LIMIT 10",
        $statsP
    );

    // Per-offer fraud counts overlaid from conversions table — same affiliate scope.
    $fraudByOffer = [];
    try {
        $fcWhere  = ['c.affiliate_id=?', 'c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
        $fcParams = [$affId, $from . ' 00:00:00', $to . ' 23:59:59'];
        if ($offerId) { $fcWhere[] = 'c.offer_id=?'; $fcParams[] = $offerId; }
        if ($country) { $fcWhere[] = 'c.country=?';  $fcParams[] = $country; }
        $fcW = implode(' AND ', $fcWhere);
        $fraudRows = Database::fetchAll(
            "SELECT c.offer_id,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv,
                    COUNT(*) AS total_cv
             FROM conversions c WHERE $fcW GROUP BY c.offer_id",
            $fcParams
        );
        foreach ($fraudRows as $fr) {
            $fraudByOffer[(int)$fr['offer_id']] = [
                'fraud' => (int)($fr['fraud_cv'] ?? 0),
                'total' => (int)($fr['total_cv'] ?? 0),
            ];
        }
    } catch (\Throwable $_e) {}

    $out = [];
    foreach ($rows as $r) {
        $cl = (int)$r['clicks'];
        $cv = (int)$r['conv'];
        $oid = (int)($r['id'] ?? 0);
        $fbo = $fraudByOffer[$oid] ?? ['fraud' => 0, 'total' => 0];
        $fraudPct = $fbo['total'] > 0 ? round($fbo['fraud'] / $fbo['total'] * 100, 2) : 0;
        $out[] = [
            'id'             => $oid,
            'name'           => $r['name'],
            'clicks'         => $cl,
            'conv'           => $cv,
            'payout'         => round((float)$r['payout'], 2),
            'cr'             => $cl > 0 ? round($cv / $cl * 100, 2) : 0,
            'fraud_conv'     => $fbo['fraud'],
            'fraud_conv_pct' => $fraudPct,
        ];
    }
    echo json_encode(['rows' => $out]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: browsers — browser breakdown
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'browsers') {
    $rows = Database::fetchAll(
        "SELECT COALESCE(NULLIF(browser,''),'Unknown') as label, COUNT(*) as cnt
         FROM clicks WHERE $clickW GROUP BY browser ORDER BY cnt DESC LIMIT 8",
        $clickP
    );
    echo json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: sources — traffic sources / referrers
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'sources') {
    $rows = Database::fetchAll(
        "SELECT CASE WHEN referer='' OR referer IS NULL THEN 'Direct'
                     ELSE SUBSTRING_INDEX(REPLACE(REPLACE(referer,'https://',''),'http://',''),'/',1) END as label,
                COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY label ORDER BY cnt DESC LIMIT 8",
        $clickP
    );
    echo json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: conversions — recent conversions list
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'conversions') {
    $limit = min(25, max(5, (int)(Helpers::get('limit') ?? 15)));
    try {
        $rows = Database::fetchAll(
            "SELECT c.id, c.conversion_id, c.status, c.payout, c.converted_at,
                    /* Surface rejection metadata so the dashboard widget can show
                       admins' rejection reason next to the badge. */
                    COALESCE(c.rejection_reason, '') AS rejection_reason,
                    c.rejected_at,
                    /* IPQualityScore (IPQS) overlay — visibility-only fields.
                       fraud_score is the canonical 0–100 IPQS verdict; values
                       >= 60 are flagged as high-risk in the dashboard widget. */
                    COALESCE(c.fraud_score, 0)        AS fraud_score,
                    CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END AS fraud_flag,
                    COALESCE(NULLIF(c.country,''), ck.country) as country,
                    COALESCE(NULLIF(c.device_type,''), ck.device_type) as device_type,
                    o.id as offer_id, o.name as offer_name
             FROM conversions c
             LEFT JOIN clicks ck ON ck.click_id = c.click_id
             LEFT JOIN offers o ON o.id = c.offer_id
             WHERE c.affiliate_id = ?
               AND c.converted_at BETWEEN ? AND ?
               AND COALESCE(c.is_hidden, 0) = 0
             ORDER BY c.converted_at DESC
             LIMIT $limit",
            [$affId, $from . ' 00:00:00', $to . ' 23:59:59']
        );
    } catch (Exception $e) {
        $rows = [];
    }
    echo json_encode(['rows' => $rows ?: []]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: filters — available filter options
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'filters') {
    // Hide private offers the caller hasn't been granted access to.
    PrivateOffer::ensureTables();
    $offers = Database::fetchAll(
        "SELECT o.id, o.name
         FROM offers o
         JOIN affiliate_offers ao         ON ao.offer_id  = o.id
         LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
         WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
           AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)
         ORDER BY o.name",
        [$affId, $affId]
    );
    $countries = Database::fetchAll(
        "SELECT DISTINCT country FROM clicks WHERE affiliate_id=? AND country!='' ORDER BY country",
        [$affId]
    );
    echo json_encode([
        'offers'    => $offers,
        'countries' => array_column($countries, 'country'),
        'devices'   => ['Desktop','Mobile','Tablet'],
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
