<?php
/**
 * Advertiser analytics API — feeds the Performance Analytics chart on the
 * advertiser dashboard. Exposes two read-only actions:
 *
 *   /api/advertiser-analytics?action=stats&from=…&to=…
 *       → { clicks, conv, revenue, payout, trend:{ clicks, conv, revenue, payout } }
 *
 *   /api/advertiser-analytics?action=trend&from=…&to=…
 *       → { labels[], clicks_data[], conv_data[], revenue_data[], payout_data[] }
 *
 * Security:
 *   - advertiser role required (Auth::check)
 *   - every query joins on offers.advertiser_id = current advertiser → an
 *     account can never see another advertiser's data even if it forges the
 *     querystring
 *   - rejected conversions are excluded everywhere (matches the rest of the
 *     advertiser reports)
 */
header('Content-Type: application/json');

Auth::check('advertiser');
$advId = (int)Auth::advertiserId();

$action = $_GET['action'] ?? 'stats';
if (!in_array($action, ['stats','trend'], true)) {
    echo json_encode(['error' => 'invalid_action']); exit;
}

// ── Date range parsing (defensive — bad input falls back to a 30-day window).
$from = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$to   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-29 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }

// Previous-period window for the % deltas (same length, ending the day
// before $from). Used only by the stats action.
$days     = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
$prevTo   = date('Y-m-d', strtotime($from) - 86400);
$prevFrom = date('Y-m-d', strtotime($from) - $days * 86400);

function pct_change_adv($curr, $prev) {
    if ($prev <= 0) return $curr > 0 ? 100.0 : 0.0;
    return round(($curr - $prev) / $prev * 100, 1);
}

// Shared filter: scope every query to the caller's offers AND exclude
// rejected conversions (matches advertiser reports policy).
$advertiserScope    = 'o.advertiser_id = ?';
$conversionScope    = "c.status <> 'rejected'";

// ── ACTION: stats ─────────────────────────────────────────────────────────
if ($action === 'stats') {
    // Clicks come from the clicks table (all valid/duplicate rows in the window).
    $clicksRow = Database::fetchOne(
        "SELECT COUNT(*) AS c
         FROM clicks cl JOIN offers o ON o.id = cl.offer_id
         WHERE $advertiserScope AND DATE(cl.clicked_at) BETWEEN ? AND ?",
        [$advId, $from, $to]
    );
    $clicks = (int)($clicksRow['c'] ?? 0);

    $convRow = Database::fetchOne(
        "SELECT COUNT(*) AS cv,
                COALESCE(SUM(c.revenue), 0) AS revenue,
                COALESCE(SUM(c.payout),  0) AS payout
         FROM conversions c JOIN offers o ON o.id = c.offer_id
         WHERE $advertiserScope AND $conversionScope
           AND DATE(c.converted_at) BETWEEN ? AND ?",
        [$advId, $from, $to]
    );
    $conv    = (int)($convRow['cv']      ?? 0);
    $revenue = round((float)($convRow['revenue'] ?? 0), 2);
    $payout  = round((float)($convRow['payout']  ?? 0), 2);

    // Previous period for deltas.
    $prevClicksRow = Database::fetchOne(
        "SELECT COUNT(*) AS c
         FROM clicks cl JOIN offers o ON o.id = cl.offer_id
         WHERE $advertiserScope AND DATE(cl.clicked_at) BETWEEN ? AND ?",
        [$advId, $prevFrom, $prevTo]
    );
    $prevConvRow = Database::fetchOne(
        "SELECT COUNT(*) AS cv,
                COALESCE(SUM(c.revenue), 0) AS revenue,
                COALESCE(SUM(c.payout),  0) AS payout
         FROM conversions c JOIN offers o ON o.id = c.offer_id
         WHERE $advertiserScope AND $conversionScope
           AND DATE(c.converted_at) BETWEEN ? AND ?",
        [$advId, $prevFrom, $prevTo]
    );
    $pClicks  = (int)($prevClicksRow['c']        ?? 0);
    $pConv    = (int)($prevConvRow['cv']         ?? 0);
    $pRevenue = (float)($prevConvRow['revenue']  ?? 0);
    $pPayout  = (float)($prevConvRow['payout']   ?? 0);

    echo json_encode([
        'clicks'  => $clicks,
        'conv'    => $conv,
        'revenue' => $revenue,
        'payout'  => $payout,
        'trend'   => [
            'clicks'  => pct_change_adv($clicks,  $pClicks),
            'conv'    => pct_change_adv($conv,    $pConv),
            'revenue' => pct_change_adv($revenue, $pRevenue),
            'payout'  => pct_change_adv($payout,  $pPayout),
        ],
    ]);
    exit;
}

// ── ACTION: trend ─────────────────────────────────────────────────────────
// When from === to we return an hourly breakdown so single-day picks
// actually show intraday detail; otherwise daily.
if ($from === $to) {
    $clickRows = Database::fetchAll(
        "SELECT HOUR(cl.clicked_at) AS h, COUNT(*) AS c
         FROM clicks cl JOIN offers o ON o.id = cl.offer_id
         WHERE $advertiserScope AND DATE(cl.clicked_at) = ?
         GROUP BY HOUR(cl.clicked_at)",
        [$advId, $from]
    ) ?: [];
    $convRows = Database::fetchAll(
        "SELECT HOUR(c.converted_at) AS h,
                COUNT(*) AS cv,
                COALESCE(SUM(c.revenue),0) AS revenue,
                COALESCE(SUM(c.payout), 0) AS payout
         FROM conversions c JOIN offers o ON o.id = c.offer_id
         WHERE $advertiserScope AND $conversionScope AND DATE(c.converted_at) = ?
         GROUP BY HOUR(c.converted_at)",
        [$advId, $from]
    ) ?: [];

    $cMap = [];  foreach ($clickRows as $r) $cMap[(int)$r['h']] = (int)$r['c'];
    $vMap = [];  foreach ($convRows  as $r) $vMap[(int)$r['h']] = $r;

    $labels = $clicks_data = $conv_data = $revenue_data = $payout_data = [];
    for ($h = 0; $h < 24; $h++) {
        $labels[]       = sprintf('%02d:00', $h);
        $clicks_data[]  = (int)($cMap[$h] ?? 0);
        $vr             = $vMap[$h] ?? [];
        $conv_data[]    = (int)($vr['cv']      ?? 0);
        $revenue_data[] = round((float)($vr['revenue'] ?? 0), 2);
        $payout_data[]  = round((float)($vr['payout']  ?? 0), 2);
    }
    echo json_encode(compact('labels','clicks_data','conv_data','revenue_data','payout_data'));
    exit;
}

// Daily aggregation across the window.
$clickRows = Database::fetchAll(
    "SELECT DATE(cl.clicked_at) AS d, COUNT(*) AS c
     FROM clicks cl JOIN offers o ON o.id = cl.offer_id
     WHERE $advertiserScope AND DATE(cl.clicked_at) BETWEEN ? AND ?
     GROUP BY DATE(cl.clicked_at)",
    [$advId, $from, $to]
) ?: [];
$convRows = Database::fetchAll(
    "SELECT DATE(c.converted_at) AS d,
            COUNT(*) AS cv,
            COALESCE(SUM(c.revenue),0) AS revenue,
            COALESCE(SUM(c.payout), 0) AS payout
     FROM conversions c JOIN offers o ON o.id = c.offer_id
     WHERE $advertiserScope AND $conversionScope
       AND DATE(c.converted_at) BETWEEN ? AND ?
     GROUP BY DATE(c.converted_at)",
    [$advId, $from, $to]
) ?: [];

$cMap = [];  foreach ($clickRows as $r) $cMap[$r['d']] = (int)$r['c'];
$vMap = [];  foreach ($convRows  as $r) $vMap[$r['d']] = $r;

$labels = $clicks_data = $conv_data = $revenue_data = $payout_data = [];
$cur = strtotime($from);
$end = strtotime($to);
while ($cur <= $end) {
    $d = date('Y-m-d', $cur);
    $labels[]       = date('M j', $cur);
    $clicks_data[]  = (int)($cMap[$d] ?? 0);
    $vr             = $vMap[$d] ?? [];
    $conv_data[]    = (int)($vr['cv']      ?? 0);
    $revenue_data[] = round((float)($vr['revenue'] ?? 0), 2);
    $payout_data[]  = round((float)($vr['payout']  ?? 0), 2);
    $cur += 86400;
}

echo json_encode(compact('labels','clicks_data','conv_data','revenue_data','payout_data'));
