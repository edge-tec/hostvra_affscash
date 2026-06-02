<?php

function _safe_json_encode($data) {
    if (\Auth::role() !== 'admin') {
        if (is_array($data)) {
            if (isset($data['revenue'])) $data['revenue'] = 0;
            if (isset($data['profit'])) $data['profit'] = 0;
            if (isset($data['revenue_data'])) $data['revenue_data'] = array_fill(0, count($data['revenue_data']??[]), 0);
            if (isset($data['trend']['revenue'])) $data['trend']['revenue'] = 0;
            
            if (isset($data['rows']) && is_array($data['rows'])) {
                foreach ($data['rows'] as &$r) {
                    if (isset($r['revenue'])) $r['revenue'] = 0;
                    if (isset($r['profit'])) $r['profit'] = 0;
                }
            }
        }
    }
    return json_encode($data);
}

header('Content-Type: application/json');
if (!Auth::id()) { echo _safe_json_encode(['error'=>'Unauthorized']); exit; }

$role = Auth::role();
if (!in_array($role, ['admin', 'affiliate_manager'])) {
    echo _safe_json_encode(['error'=>'Forbidden']); exit;
}

// ── Schema guards: ensure columns used in this controller exist ────────────
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN country VARCHAR(10) DEFAULT NULL"); } catch(Exception $e) {}

$action  = Helpers::get('action') ?: '';

// ── Timezone: apply before any date() calls ────────────────────────────────
$_reqTz = Helpers::get('tz') ?: (Config::get('config','app.timezone') ?? 'UTC');
if (!in_array($_reqTz, timezone_identifiers_list())) {
    $_reqTz = Config::get('config','app.timezone') ?? 'UTC';
}
date_default_timezone_set($_reqTz);
// Sync MySQL session timezone so DATE(), HOUR(), NOW() match PHP
try {
    Database::getInstance()->exec("SET time_zone = '" . addslashes($_reqTz) . "'");
} catch (\Throwable $_tzEx) {
    try { Database::getInstance()->exec("SET time_zone = '" . date('P') . "'"); } catch (\Throwable $_) {}
}

$from    = Helpers::get('from')   ?: date('Y-m-d', strtotime('-29 days'));
$to      = Helpers::get('to')     ?: date('Y-m-d');

// Sanitize dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

$offerId = (int)(Helpers::get('offer_id')    ?? 0);
$affId   = (int)(Helpers::get('affiliate_id') ?? 0);
$country = trim(Helpers::get('country') ?? '');
$device  = trim(Helpers::get('device')  ?? '');

// For affiliate_manager: scope to managed affiliates
$managerAffIds = [];
if ($role === 'affiliate_manager') {
    $managerAffIds = Auth::managerAffiliateIds();
    if (empty($managerAffIds)) {
        echo _safe_json_encode(['rows'=>[], 'labels'=>[], 'data'=>[], 'count'=>0,
                          'clicks'=>0,'conversions'=>0,'revenue'=>0,'payout'=>0,'cr'=>0,
                          'trend'=>['clicks'=>0,'conv'=>0,'revenue'=>0,'payout'=>0]]);
        exit;
    }
}

// ── WHERE builder for stats_daily ─────────────────────────────────────────
function adminStatsWhere($from, $to, $offerId, $affId, $managerAffIds) {
    $w = ['sd.stat_date BETWEEN ? AND ?'];
    $p = [$from, $to];
    if ($offerId) { $w[] = 'sd.offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'sd.affiliate_id = ?'; $p[] = $affId; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "sd.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

// ── WHERE builder for clicks table ────────────────────────────────────────
function adminClicksWhere($from, $to, $offerId, $affId, $country, $device, $managerAffIds) {
    $w = ['clicked_at BETWEEN ? AND ?'];
    $p = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $w[] = 'offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'affiliate_id = ?'; $p[] = $affId; }
    if ($country) { $w[] = 'country = ?'; $p[] = $country; }
    if ($device)  { $w[] = 'device_type = ?'; $p[] = $device; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

// ── WHERE builder for conversions table ───────────────────────────────────
function adminConvWhere($from, $to, $offerId, $affId, $country, $managerAffIds) {
    $w = ['c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
    $p = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $w[] = 'c.offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'c.affiliate_id = ?'; $p[] = $affId; }
    if ($country) { $w[] = 'c.country = ?'; $p[] = $country; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "c.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

function safe_pct_adm($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? 100 : 0;
    return round(($curr - $prev) / $prev * 100, 1);
}

[$statsW, $statsP] = adminStatsWhere($from, $to, $offerId, $affId, $managerAffIds);
[$clickW, $clickP] = adminClicksWhere($from, $to, $offerId, $affId, $country, $device, $managerAffIds);
[$convW,  $convP]  = adminConvWhere($from, $to, $offerId, $affId, $country, $managerAffIds);

// Previous period for trend comparison
$days     = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
$prevFrom = date('Y-m-d', strtotime($from) - $days * 86400);
$prevTo   = date('Y-m-d', strtotime($from) - 86400);
[$prevStatsW, $prevStatsP] = adminStatsWhere($prevFrom, $prevTo, $offerId, $affId, $managerAffIds);

// ══════════════════════════════════════════════════════════════════════════
// ACTION: stats — KPI totals with trend
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'stats') {
    $cur  = Database::fetchOne("SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv, SUM(payout) as p, SUM(revenue) as r FROM stats_daily sd WHERE $statsW", $statsP) ?? [];
    $prev = Database::fetchOne("SELECT SUM(clicks) as c, SUM(conversions) as cv, SUM(payout) as p, SUM(revenue) as r FROM stats_daily sd WHERE $prevStatsW", $prevStatsP) ?? [];

    $clicks  = (int)($cur['c']  ?? 0);
    $unique  = (int)($cur['u']  ?? 0);
    $conv    = (int)($cur['cv'] ?? 0);
    $payout  = round((float)($cur['p'] ?? 0), 2);
    $revenue = round((float)($cur['r'] ?? 0), 2);
    $profit  = round($revenue - $payout, 2);
    $cr      = $clicks > 0 ? round($conv / $clicks * 100, 2) : 0;

    $pClicks  = (int)($prev['c']  ?? 0);
    $pConv    = (int)($prev['cv'] ?? 0);
    $pPayout  = (float)($prev['p'] ?? 0);
    $pRevenue = (float)($prev['r'] ?? 0);

    // Fraud count
    $fraud = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM clicks WHERE DATE(clicked_at) BETWEEN ? AND ? AND is_fraud=1",
        [$from, $to]
    );

    // ── Fraud Conversion % — counts conversions whose fraud_score >= 60 ─────
    // Reads directly from the conversions table (stats_daily has no fraud_score).
    // Re-uses the same WHERE builder so date / offer / affiliate / country /
    // manager scope filters apply identically to the rest of the KPI grid.
    $fraudConvCur = 0; $fraudConvPrev = 0; $totalConvForPct = 0; $totalConvPrevForPct = 0;
    try {
        $fcCur = Database::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
             FROM conversions c WHERE $convW",
            $convP
        );
        $fraudConvCur     = (int)($fcCur['fraud'] ?? 0);
        $totalConvForPct  = (int)($fcCur['total'] ?? 0);
        [$prevConvW, $prevConvP] = adminConvWhere($prevFrom, $prevTo, $offerId, $affId, $country, $managerAffIds);
        $fcPrev = Database::fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
             FROM conversions c WHERE $prevConvW",
            $prevConvP
        );
        $fraudConvPrev       = (int)($fcPrev['fraud'] ?? 0);
        $totalConvPrevForPct = (int)($fcPrev['total'] ?? 0);
    } catch (\Throwable $e) {}

    $fraudConvPct      = $totalConvForPct     > 0 ? round($fraudConvCur  / $totalConvForPct     * 100, 2) : 0;
    $fraudConvPctPrev  = $totalConvPrevForPct > 0 ? round($fraudConvPrev / $totalConvPrevForPct * 100, 2) : 0;
    $fraudConvPctTrend = round($fraudConvPct - $fraudConvPctPrev, 1); // delta in percentage points

    echo _safe_json_encode([
        'clicks'         => $clicks,
        'unique'         => $unique,
        'conv'           => $conv,
        'payout'         => $payout,
        'revenue'        => $revenue,
        'profit'         => $profit,
        'cr'             => $cr,
        'fraud'          => (int)($fraud['cnt'] ?? 0),
        'fraud_conv'     => $fraudConvCur,
        'fraud_conv_pct' => $fraudConvPct,
        'trend'          => [
            'clicks'         => safe_pct_adm($clicks,  $pClicks),
            'conv'           => safe_pct_adm($conv,    $pConv),
            'payout'         => safe_pct_adm($payout,  $pPayout),
            'revenue'        => safe_pct_adm($revenue, $pRevenue),
            'fraud_conv_pct' => $fraudConvPctTrend,
        ],
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: trend — daily series for main chart (hourly when from===to)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'trend') {
    // When a single day is selected, return hourly breakdown for intraday detail
    if ($from === $to) {
        $hW = ['DATE(clicked_at)=?'];
        $hP = [$from];
        if ($offerId) { $hW[] = 'offer_id=?';     $hP[] = $offerId; }
        if ($affId)   { $hW[] = 'affiliate_id=?'; $hP[] = $affId; }
        if ($country) { $hW[] = 'country=?';      $hP[] = $country; }
        if (!empty($managerAffIds)) {
            $in = implode(',', array_fill(0, count($managerAffIds), '?'));
            $hW[] = "affiliate_id IN ($in)";
            $hP   = array_merge($hP, $managerAffIds);
        }
        $hWhere = implode(' AND ', $hW);

        $cvW = ['DATE(converted_at)=?', 'COALESCE(is_hidden,0)=0'];
        $cvP = [$from];
        if ($offerId) { $cvW[] = 'offer_id=?';     $cvP[] = $offerId; }
        if ($affId)   { $cvW[] = 'affiliate_id=?'; $cvP[] = $affId; }
        if (!empty($managerAffIds)) {
            $in = implode(',', array_fill(0, count($managerAffIds), '?'));
            $cvW[] = "affiliate_id IN ($in)";
            $cvP   = array_merge($cvP, $managerAffIds);
        }
        $cvWhere = implode(' AND ', $cvW);

        $clRows = Database::fetchAll(
            "SELECT HOUR(clicked_at) as h, COUNT(*) as c, SUM(is_unique) as u
             FROM clicks WHERE $hWhere GROUP BY HOUR(clicked_at)",
            $hP
        );
        $cvRows = Database::fetchAll(
            "SELECT HOUR(converted_at) as h, COUNT(*) as cv, SUM(payout) as p, SUM(revenue) as r,
                    SUM(CASE WHEN COALESCE(fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
             FROM conversions WHERE $cvWhere GROUP BY HOUR(converted_at)",
            $cvP
        );

        $cMap = []; foreach ($clRows as $r) $cMap[(int)$r['h']] = $r;
        $cvMap = []; foreach ($cvRows as $r) $cvMap[(int)$r['h']] = $r;

        $labels = $clicks_data = $unique_data = $conv_data = $revenue_data = $payout_data = $fraud_data = [];
        for ($h = 0; $h < 24; $h++) {
            $cr  = $cMap[$h]  ?? [];
            $cvr = $cvMap[$h] ?? [];
            $labels[]       = sprintf('%02d:00', $h);
            $clicks_data[]  = (int)($cr['c']   ?? 0);
            $unique_data[]  = (int)($cr['u']   ?? 0);
            $conv_data[]    = (int)($cvr['cv'] ?? 0);
            $revenue_data[] = round((float)($cvr['r'] ?? 0), 2);
            $payout_data[]  = round((float)($cvr['p'] ?? 0), 2);
            $fraud_data[]   = (int)($cvr['fraud_cv'] ?? 0);
        }
        echo _safe_json_encode(compact('labels','clicks_data','unique_data','conv_data','revenue_data','payout_data','fraud_data'));
        exit;
    }

    $rows = Database::fetchAll(
        "SELECT sd.stat_date as d, SUM(sd.clicks) as c, SUM(sd.unique_clicks) as u,
                SUM(sd.conversions) as cv, SUM(sd.payout) as p, SUM(sd.revenue) as r
         FROM stats_daily sd WHERE $statsW GROUP BY sd.stat_date ORDER BY sd.stat_date",
        $statsP
    );
    $map = [];
    foreach ($rows as $r) $map[$r['d']] = $r;

    // Daily fraud-conversion counts overlaid on the trend (stats_daily has no
    // fraud_score column, so we query conversions directly using the same
    // scope filters from adminConvWhere — keeping data consistent across cards).
    $fraudByDay = [];
    try {
        $fraudRows = Database::fetchAll(
            "SELECT DATE(c.converted_at) as d,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
             FROM conversions c WHERE $convW GROUP BY DATE(c.converted_at)",
            $convP
        );
        foreach ($fraudRows as $fr) $fraudByDay[$fr['d']] = (int)($fr['fraud_cv'] ?? 0);
    } catch (\Throwable $_e) {}

    $labels = $clicks_data = $unique_data = $conv_data = $revenue_data = $payout_data = $fraud_data = [];
    $cur = strtotime($from);
    $end = strtotime($to);
    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $r = $map[$d] ?? [];
        $labels[]       = date('M j', $cur);
        $clicks_data[]  = (int)($r['c']  ?? 0);
        $unique_data[]  = (int)($r['u']  ?? 0);
        $conv_data[]    = (int)($r['cv'] ?? 0);
        $revenue_data[] = round((float)($r['r'] ?? 0), 2);
        $payout_data[]  = round((float)($r['p'] ?? 0), 2);
        $fraud_data[]   = (int)($fraudByDay[$d] ?? 0);
        $cur += 86400;
    }
    echo _safe_json_encode(compact('labels','clicks_data','unique_data','conv_data','revenue_data','payout_data','fraud_data'));
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: hourly — today's clicks by hour
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'hourly') {
    $today = date('Y-m-d');
    $hW    = 'DATE(clicked_at) = ?';
    $hP    = [$today];
    if ($offerId) { $hW .= ' AND offer_id = ?'; $hP[] = $offerId; }
    if ($affId)   { $hW .= ' AND affiliate_id = ?'; $hP[] = $affId; }
    if ($country) { $hW .= ' AND country = ?'; $hP[] = $country; }
    if ($device)  { $hW .= ' AND device_type = ?'; $hP[] = $device; }
    if (!empty($managerAffIds)) {
        $in = implode(',', array_fill(0, count($managerAffIds), '?'));
        $hW .= " AND affiliate_id IN ($in)";
        $hP  = array_merge($hP, $managerAffIds);
    }
    $rows   = Database::fetchAll("SELECT HOUR(clicked_at) as h, COUNT(*) as cnt FROM clicks WHERE $hW GROUP BY HOUR(clicked_at)", $hP);
    $hourly = array_fill(0, 24, 0);
    foreach ($rows as $r) $hourly[(int)$r['h']] = (int)$r['cnt'];
    $labels = array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23));
    echo _safe_json_encode(['labels' => $labels, 'data' => array_values($hourly)]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: countries — top countries by clicks
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'countries') {
    $clRows = Database::fetchAll(
        "SELECT country, COUNT(*) as clicks, SUM(is_unique) as uniq
         FROM clicks WHERE $clickW AND country != '' AND country IS NOT NULL
         GROUP BY country ORDER BY clicks DESC LIMIT 15",
        $clickP
    );
    $cvRows = Database::fetchAll(
        "SELECT COALESCE(NULLIF(c.country,''), ck.country) as country, COUNT(*) as conv
         FROM conversions c
         LEFT JOIN clicks ck ON ck.click_id = c.click_id
         WHERE $convW AND COALESCE(NULLIF(c.country,''), ck.country) != ''
         GROUP BY COALESCE(NULLIF(c.country,''), ck.country)",
        $convP
    );
    // Note: $convW from adminConvWhere() already uses the 'c.' table alias,
    // which matches the 'c' alias used above — no ambiguity with 'ck' (clicks).
    $cvMap = [];
    foreach ($cvRows as $r) $cvMap[$r['country']] = (int)$r['conv'];

    $out = [];
    foreach ($clRows as $r) {
        $out[] = [
            'country' => $r['country'],
            'clicks'  => (int)$r['clicks'],
            'unique'  => (int)$r['uniq'],
            'conv'    => $cvMap[$r['country']] ?? 0,
        ];
    }
    echo _safe_json_encode(['rows' => $out]);
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
    echo _safe_json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ]);
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
    echo _safe_json_encode([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: offers — top offers performance
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'offers') {
    $rows = Database::fetchAll(
        "SELECT o.name, o.id,
                SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue
         FROM stats_daily sd JOIN offers o ON o.id = sd.offer_id
         WHERE $statsW GROUP BY sd.offer_id ORDER BY payout DESC LIMIT 10",
        $statsP
    );

    // Per-offer fraud counts overlaid from the conversions table (stats_daily
    // has no fraud_score). One round-trip; keyed by offer_id for fast lookup.
    $fraudByOffer = [];
    try {
        $fraudRows = Database::fetchAll(
            "SELECT c.offer_id,
                    SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv,
                    COUNT(*) AS total_cv
             FROM conversions c WHERE $convW GROUP BY c.offer_id",
            $convP
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
        $oid = (int)$r['id'];
        $fbo = $fraudByOffer[$oid] ?? ['fraud' => 0, 'total' => 0];
        $fraudPct = $fbo['total'] > 0 ? round($fbo['fraud'] / $fbo['total'] * 100, 2) : 0;
        $out[] = [
            'id'             => $oid,
            'name'           => $r['name'],
            'clicks'         => $cl,
            'uclicks'        => (int)$r['uclicks'],
            'conv'           => $cv,
            'payout'         => round((float)$r['payout'],  2),
            'revenue'        => round((float)$r['revenue'], 2),
            'profit'         => round((float)$r['revenue'] - (float)$r['payout'], 2),
            'cr'             => $cl > 0 ? round($cv / $cl * 100, 2) : 0,
            'fraud_conv'     => $fbo['fraud'],
            'fraud_conv_pct' => $fraudPct,
        ];
    }
    echo _safe_json_encode(['rows' => $out]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: affiliates — top affiliates performance
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'affiliates') {
    $rows = Database::fetchAll(
        "SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code, af.id as aff_id,
                SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue
         FROM stats_daily sd
         JOIN affiliates af ON af.id = sd.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE $statsW GROUP BY sd.affiliate_id ORDER BY payout DESC LIMIT 10",
        $statsP
    );
    $out = [];
    foreach ($rows as $r) {
        $cl = (int)$r['clicks'];
        $cv = (int)$r['conv'];
        $out[] = [
            'id'      => (int)$r['aff_id'],
            'name'    => $r['name'],
            'code'    => $r['affiliate_code'],
            'clicks'  => $cl,
            'uclicks' => (int)$r['uclicks'],
            'conv'    => $cv,
            'payout'  => round((float)$r['payout'], 2),
            'cr'      => $cl > 0 ? round($cv / $cl * 100, 2) : 0,
        ];
    }
    echo _safe_json_encode(['rows' => $out]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: conv_status — conversion status counts for pie chart
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'conv_status') {
    $rows = Database::fetchAll(
        "SELECT c.status, COUNT(*) as cnt
         FROM conversions c WHERE $convW
         GROUP BY c.status ORDER BY cnt DESC",
        $convP
    );
    $pieColors = [
        'approved'     => '#10B981',
        'pending'      => '#F59E0B',
        'rejected'     => '#EF4444',
        'chargebacked' => '#94A3B8',
    ];
    $labels = [];
    $data   = [];
    $colors = [];
    foreach ($rows as $r) {
        if ((int)$r['cnt'] === 0) continue;
        $labels[] = $r['status'];
        $data[]   = (int)$r['cnt'];
        $colors[] = $pieColors[$r['status']] ?? '#64748B';
    }
    echo _safe_json_encode(compact('labels', 'data', 'colors'));
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: conversions — recent conversions list
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'conversions') {
    $limit = min(25, max(5, (int)(Helpers::get('limit') ?? 15)));

    // Build a simpler where for this join query
    $cW = ['c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
    $cP = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $cW[] = 'c.offer_id = ?'; $cP[] = $offerId; }
    if ($affId)   { $cW[] = 'c.affiliate_id = ?'; $cP[] = $affId; }
    if ($country) { $cW[] = 'c.country = ?'; $cP[] = $country; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $cW[] = "c.affiliate_id IN ($in)";
        $cP   = array_merge($cP, $managerAffIds);
    }
    $whereStr = implode(' AND ', $cW);

    try {
        $rows = Database::fetchAll(
            "SELECT c.id, c.status, c.payout, c.revenue, c.converted_at,
                    COALESCE(NULLIF(c.country,''), ck.country) as country,
                    COALESCE(NULLIF(c.device_type,''), ck.device_type) as device_type,
                    o.name as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name
             FROM conversions c
             LEFT JOIN clicks ck ON ck.click_id = c.click_id
             LEFT JOIN offers o ON o.id = c.offer_id
             LEFT JOIN affiliates af ON af.id = c.affiliate_id
             LEFT JOIN users u ON u.id = af.user_id
             WHERE $whereStr ORDER BY c.converted_at DESC LIMIT $limit",
            $cP
        );
    } catch (Exception $e) {
        $rows = [];
    }
    echo _safe_json_encode(['rows' => $rows ?: []]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: filters — available filter options for dropdowns
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'filters') {
    if ($role === 'affiliate_manager') {
        $offersQ = empty($managerAffIds) ? [] : Database::fetchAll(
            "SELECT DISTINCT o.id, o.name FROM stats_daily sd JOIN offers o ON o.id=sd.offer_id
             WHERE sd.affiliate_id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ")
             ORDER BY o.name",
            $managerAffIds
        );
        $affsQ = empty($managerAffIds) ? [] : Database::fetchAll(
            "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label
             FROM affiliates af JOIN users u ON u.id=af.user_id
             WHERE af.id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ")
             ORDER BY u.first_name",
            $managerAffIds
        );
        $countriesQ = empty($managerAffIds) ? [] : Database::fetchAll(
            "SELECT DISTINCT country FROM clicks
             WHERE affiliate_id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ")
               AND country != '' ORDER BY country",
            $managerAffIds
        );
    } else {
        $offersQ = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
        $affsQ   = Database::fetchAll(
            "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label
             FROM affiliates af JOIN users u ON u.id=af.user_id WHERE u.status='active' ORDER BY u.first_name"
        );
        $countriesQ = Database::fetchAll("SELECT DISTINCT country FROM clicks WHERE country != '' ORDER BY country");
    }

    echo _safe_json_encode([
        'offers'    => $offersQ,
        'affiliates'=> $affsQ,
        'countries' => array_column($countriesQ, 'country'),
        'devices'   => ['Desktop','Mobile','Tablet'],
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION: summary_cards — static counts (total affiliates, offers etc)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'summary_cards') {
    if ($role === 'admin') {
        $totAff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='active'");
        $pendAff = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='pending'");
        $totOff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE status='active'");
        $totAdv  = Database::fetchOne("SELECT COUNT(*) as cnt FROM advertisers a JOIN users u ON u.id=a.user_id WHERE u.status='active'");
        echo _safe_json_encode([
            'affiliates'         => (int)($totAff['cnt']  ?? 0),
            'pending_affiliates' => (int)($pendAff['cnt'] ?? 0),
            'offers'             => (int)($totOff['cnt']  ?? 0),
            'advertisers'        => (int)($totAdv['cnt']  ?? 0),
        ]);
    } else {
        echo _safe_json_encode([
            'affiliates'         => count($managerAffIds),
            'pending_affiliates' => 0,
            'offers'             => 0,
            'advertisers'        => 0,
        ]);
    }
    exit;
}

echo _safe_json_encode(['error' => 'Unknown action']);
