<?php
/**
 * Admin → Affiliate Report
 *
 * Standalone analytics + traffic-detail report grouped by affiliate. Pulls
 * live from clicks / conversions / affiliates / users / offers — no caches,
 * no schema changes. Existing /admin/reports tabs are untouched.
 */
Auth::check('admin');
$pageTitle = 'Affiliate Report';

// ─── Filters ──────────────────────────────────────────────────────────────
$from    = Helpers::get('from') ?: date('Y-m-01');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$affId   = (int)(Helpers::get('affiliate_id') ?: 0);
$affCode = trim((string)(Helpers::get('affiliate_code') ?? ''));
$affName = trim((string)(Helpers::get('affiliate_name') ?? ''));
if ($affCode !== '' && $affId === 0) {
    $_r = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code = ?", [$affCode]);
    if ($_r) $affId = (int)$_r['id'];
}
$offerId    = (int)(Helpers::get('offer_id') ?: 0);
$country    = strtoupper(trim((string)(Helpers::get('country') ?? '')));
$convStatus = trim((string)(Helpers::get('conv_status') ?? '')); // approved|pending|rejected|''
$device     = trim((string)(Helpers::get('device') ?? ''));      // desktop|mobile|tablet|bot|unknown|''
$trafficSt  = trim((string)(Helpers::get('traffic_status') ?? '')); // clean|fraud|blocked|''
$ipFilter   = trim((string)(Helpers::get('ip') ?? ''));
$exportFmt  = trim((string)(Helpers::get('export') ?? ''));
$isExport   = in_array($exportFmt, ['csv','xls'], true);
$limit      = min((int)(Helpers::get('limit') ?: 1000), 10000);

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

// ─── Option lists (for filter dropdowns) ──────────────────────────────────
$offerList   = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];
$affList     = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
       FROM affiliates af JOIN users u ON u.id = af.user_id
      ORDER BY name"
) ?: [];
$countryList = Database::fetchAll("SELECT DISTINCT country FROM clicks WHERE country != '' ORDER BY country") ?: [];

// ─── Click WHERE builder (shared with per-affiliate aggregation + detail) ──
// Defined as a closure so it captures controller-scope filter values directly
// — Router dispatches controllers inside an anonymous closure, so these locals
// are NOT in the global scope and `global` would not see them.
$arWhereClicks = function() use ($dateFrom, $dateTo, $offerId, $affId, $country,
                                 $device, $trafficSt, $ipFilter, $affName) {
    $w  = ['c.clicked_at BETWEEN ? AND ?'];
    $p  = [$dateFrom, $dateTo];
    if ($offerId  > 0)   { $w[] = 'c.offer_id = ?';     $p[] = $offerId; }
    if ($affId    > 0)   { $w[] = 'c.affiliate_id = ?'; $p[] = $affId;   }
    if ($country !== '') { $w[] = 'c.country = ?';      $p[] = $country; }
    if ($device  !== '') { $w[] = 'c.device_type = ?';  $p[] = $device;  }
    if ($ipFilter !== ''){ $w[] = 'c.ip_address LIKE ?';$p[] = '%'.$ipFilter.'%'; }
    if ($trafficSt === 'clean')   { $w[] = 'c.is_fraud = 0 AND c.status = "valid"'; }
    if ($trafficSt === 'fraud')   { $w[] = 'c.is_fraud = 1'; }
    if ($trafficSt === 'blocked') { $w[] = "c.status = 'blocked'"; }
    if ($affName !== '') {
        $w[] = "EXISTS(SELECT 1 FROM users uu JOIN affiliates aaff ON aaff.user_id=uu.id
                       WHERE aaff.id = c.affiliate_id
                         AND CONCAT(uu.first_name,' ',uu.last_name) LIKE ?)";
        $p[] = '%'.$affName.'%';
    }
    return [implode(' AND ', $w), $p];
};

// ─── Per-affiliate aggregation ────────────────────────────────────────────
[$wClk, $pClk] = $arWhereClicks();

$convSubSelect = '
    SELECT cv.affiliate_id,
           SUM(CASE WHEN cv.status="approved" THEN 1 ELSE 0 END) AS conv_approved,
           SUM(CASE WHEN cv.status="pending"  THEN 1 ELSE 0 END) AS conv_pending,
           SUM(CASE WHEN cv.status="rejected" THEN 1 ELSE 0 END) AS conv_rejected,
           SUM(CASE WHEN cv.status IN ("approved","pending","rejected") THEN 1 ELSE 0 END) AS conv_total,
           SUM(CASE WHEN cv.status="approved" THEN cv.payout  ELSE 0 END) AS payout,
           SUM(CASE WHEN cv.status="approved" THEN cv.revenue ELSE 0 END) AS revenue,
           MAX(cv.converted_at) AS last_conv_at
      FROM conversions cv
     WHERE cv.converted_at BETWEEN ? AND ?
       AND COALESCE(cv.is_hidden,0) = 0
       ' . ($offerId > 0 ? ' AND cv.offer_id = ' . (int)$offerId : '') . '
       ' . ($affId   > 0 ? ' AND cv.affiliate_id = ' . (int)$affId : '') . '
     GROUP BY cv.affiliate_id';
$convParams = [$dateFrom, $dateTo];

// Optional conversion-status filter applied to the parent join — keeps the
// per-affiliate row but lets admin focus on a status bucket.
$havingConv = '';
if (in_array($convStatus, ['approved','pending','rejected'], true)) {
    $havingConv = ' HAVING conv_' . $convStatus . ' > 0';
}

$sqlAgg = "
    SELECT af.id AS affiliate_id, af.affiliate_code, af.created_at AS registered_at,
           u.email, u.status AS user_status, u.last_login,
           CONCAT(u.first_name,' ',u.last_name) AS aff_name,
           SUM(c.is_unique)                                AS unique_clicks,
           COUNT(*)                                        AS total_clicks,
           SUM(c.is_fraud)                                 AS fraud_clicks,
           SUM(CASE WHEN c.status='blocked' THEN 1 ELSE 0 END) AS blocked_clicks,
           MAX(c.clicked_at)                               AS last_click_at,
           COALESCE(cvg.conv_total, 0)                     AS conv_total,
           COALESCE(cvg.conv_approved, 0)                  AS conv_approved,
           COALESCE(cvg.conv_pending, 0)                   AS conv_pending,
           COALESCE(cvg.conv_rejected, 0)                  AS conv_rejected,
           COALESCE(cvg.payout, 0)                         AS payout,
           COALESCE(cvg.revenue, 0)                        AS revenue,
           cvg.last_conv_at                                AS last_conv_at
      FROM clicks c
      JOIN affiliates af ON af.id = c.affiliate_id
      JOIN users      u  ON u.id  = af.user_id
      LEFT JOIN ($convSubSelect) cvg ON cvg.affiliate_id = af.id
     WHERE $wClk
     GROUP BY af.id $havingConv
     ORDER BY revenue DESC, total_clicks DESC
     LIMIT $limit";
$aggRows = Database::fetchAll($sqlAgg, array_merge($convParams, $pClk)) ?: [];

// Affiliates with conversions but zero clicks in the window — surface them too.
// (Skipped when admin is filtering by an affiliate or affiliate name.)
if ($affId === 0 && $affName === '' && empty($havingConv)) {
    $extraConv = Database::fetchAll("
        SELECT af.id AS affiliate_id, af.affiliate_code, af.created_at AS registered_at,
               u.email, u.status AS user_status, u.last_login,
               CONCAT(u.first_name,' ',u.last_name) AS aff_name,
               0                                               AS unique_clicks,
               0                                               AS total_clicks,
               0                                               AS fraud_clicks,
               0                                               AS blocked_clicks,
               NULL                                            AS last_click_at,
               COALESCE(cvg.conv_total, 0)                     AS conv_total,
               COALESCE(cvg.conv_approved, 0)                  AS conv_approved,
               COALESCE(cvg.conv_pending, 0)                   AS conv_pending,
               COALESCE(cvg.conv_rejected, 0)                  AS conv_rejected,
               COALESCE(cvg.payout, 0)                         AS payout,
               COALESCE(cvg.revenue, 0)                        AS revenue,
               cvg.last_conv_at                                AS last_conv_at
          FROM ($convSubSelect) cvg
          JOIN affiliates af ON af.id = cvg.affiliate_id
          JOIN users      u  ON u.id  = af.user_id
         WHERE NOT EXISTS (SELECT 1 FROM clicks cx WHERE cx.affiliate_id = af.id AND cx.clicked_at BETWEEN ? AND ?)
    ", array_merge($convParams, [$dateFrom, $dateTo])) ?: [];
    foreach ($extraConv as $r) $aggRows[] = $r;
}

// ─── IPQS Fraud stats per affiliate (for inline fraud report panel) ───────
$ipqsStatsByAffiliate = [];
if (!empty($aggRows)) {
    $affIds = array_unique(array_column($aggRows, 'affiliate_id'));
    if (!empty($affIds)) {
        $placeholders = implode(',', array_fill(0, count($affIds), '?'));
        $ipqsRows = Database::fetchAll("
            SELECT cv.affiliate_id,
                   COUNT(*)                                                        AS total_checked,
                   ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END), 1) AS avg_score,
                   MAX(cv.fraud_score)                                             AS max_score,
                   SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)          AS high_risk,
                   SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END) AS medium_risk,
                   SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk,
                   SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END)   AS pending_check,
                   SUM(CASE WHEN cv.ipquery_vpn = 1 THEN 1 ELSE 0 END)            AS vpn_count,
                   SUM(CASE WHEN cv.ipquery_proxy = 1 THEN 1 ELSE 0 END)          AS proxy_count,
                   SUM(CASE WHEN cv.ipquery_tor = 1 THEN 1 ELSE 0 END)            AS tor_count,
                   SUM(CASE WHEN cv.ipquery_datacenter = 1 THEN 1 ELSE 0 END)     AS datacenter_count
              FROM conversions cv
             WHERE cv.affiliate_id IN ($placeholders)
               AND cv.converted_at BETWEEN ? AND ?
               AND COALESCE(cv.is_hidden, 0) = 0
             GROUP BY cv.affiliate_id
        ", array_merge($affIds, [$dateFrom, $dateTo])) ?: [];
        foreach ($ipqsRows as $row) {
            $ipqsStatsByAffiliate[(int)$row['affiliate_id']] = $row;
        }
    }
}

// ─── Traffic detail (only when single affiliate is filtered, capped) ──────
$trafficRows = [];
if ($affId > 0) {
    [$wDet, $pDet] = $arWhereClicks();
    $convFilterJoin = '';
    if (in_array($convStatus, ['approved','pending','rejected'], true)) {
        // $convStatus is already restricted to a 3-value whitelist above,
        // so inlining it as a string literal is safe (no user input reaches SQL).
        $convFilterJoin = " AND cv.status = '" . $convStatus . "'";
    }
    $detLimit = min($limit, 2000);
    $trafficRows = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.sub5,
                c.ip_address, c.country, c.city, c.region,
                c.device_type, c.os, c.browser, c.user_agent,
                c.is_fraud, c.fraud_score, c.status AS click_status, c.clicked_at,
                o.name AS offer_name,
                cv.status AS conv_status, cv.payout AS conv_payout, cv.revenue AS conv_revenue
           FROM clicks c
           JOIN offers o ON o.id = c.offer_id
           LEFT JOIN conversions cv ON cv.click_id = c.click_id AND COALESCE(cv.is_hidden,0)=0
          WHERE $wDet $convFilterJoin
          ORDER BY c.clicked_at DESC
          LIMIT $detLimit",
        $pDet
    ) ?: [];
}

// ─── Export ───────────────────────────────────────────────────────────────
if ($isExport) {
    $ts = date('Y-m-d');
    if ($exportFmt === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="affiliate-report-'.$ts.'.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['Affiliate ID','Affiliate Code','Name','Email','Status',
            'Clicks','Unique','Fraud','Blocked','Conversions','Approved','Pending','Rejected',
            'CR %','EPC','Payout','Revenue','Profit','Traffic Quality','Last Activity','Registered']);
        foreach ($aggRows as $r) {
            $cr  = $r['total_clicks'] > 0 ? round($r['conv_total']  / $r['total_clicks'] * 100, 2) : 0;
            $epc = $r['total_clicks'] > 0 ? round($r['payout']      / $r['total_clicks'], 4) : 0;
            $tq  = $r['total_clicks'] > 0 ? (($r['fraud_clicks']+$r['blocked_clicks'])/$r['total_clicks'] >= 0.20 ? 'Low'
                                            : (($r['fraud_clicks']+$r['blocked_clicks'])/$r['total_clicks'] >= 0.05 ? 'Medium' : 'High')) : '—';
            $lastAct = $r['last_click_at'] ?: $r['last_conv_at'] ?: $r['last_login'];
            fputcsv($f, [
                $r['affiliate_id'], $r['affiliate_code'], $r['aff_name'], $r['email'], $r['user_status'],
                $r['total_clicks'], $r['unique_clicks'], $r['fraud_clicks'], $r['blocked_clicks'],
                $r['conv_total'], $r['conv_approved'], $r['conv_pending'], $r['conv_rejected'],
                $cr, $epc, number_format($r['payout'],4), number_format($r['revenue'],4),
                number_format($r['revenue'] - $r['payout'],4), $tq, $lastAct ?? '', $r['registered_at'] ?? '',
            ]);
        }
        // Per-click detail block when an affiliate is selected
        if ($affId > 0 && !empty($trafficRows)) {
            fputcsv($f, []);
            fputcsv($f, ['── TRAFFIC DETAIL ──']);
            fputcsv($f, ['Offer','Click ID','Aff Click ID (sub1)','sub2','sub3','sub4','sub5',
                'IP','Country','City','Region','Device','OS','Browser','User Agent',
                'Click Status','Fraud Score','Conv Status','Conv Payout','Conv Revenue','Clicked At']);
            foreach ($trafficRows as $t) {
                fputcsv($f, [
                    $t['offer_name'], $t['click_id'], $t['sub1'], $t['sub2'], $t['sub3'], $t['sub4'], $t['sub5'],
                    $t['ip_address'], $t['country'], $t['city'], $t['region'],
                    $t['device_type'], $t['os'], $t['browser'], $t['user_agent'],
                    $t['click_status'], (int)$t['fraud_score'], $t['conv_status'] ?? '',
                    $t['conv_payout'] ?? '', $t['conv_revenue'] ?? '', $t['clicked_at'],
                ]);
            }
        }
        fclose($f); exit;
    }
    // xls = HTML table with .xls extension — Excel opens it natively
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="affiliate-report-'.$ts.'.xls"');
    echo "<html><head><meta charset=\"UTF-8\"></head><body><table border=\"1\">";
    echo '<tr>'.implode('', array_map(fn($h)=>"<th>$h</th>", [
        'Affiliate ID','Affiliate Code','Name','Email','Status','Clicks','Unique','Fraud','Blocked',
        'Conversions','Approved','Pending','Rejected','CR %','EPC','Payout','Revenue','Profit',
        'Traffic Quality','Last Activity','Registered'])).'</tr>';
    foreach ($aggRows as $r) {
        $cr  = $r['total_clicks'] > 0 ? round($r['conv_total']  / $r['total_clicks'] * 100, 2) : 0;
        $epc = $r['total_clicks'] > 0 ? round($r['payout']      / $r['total_clicks'], 4) : 0;
        $tq  = $r['total_clicks'] > 0 ? (($r['fraud_clicks']+$r['blocked_clicks'])/$r['total_clicks'] >= 0.20 ? 'Low'
                                        : (($r['fraud_clicks']+$r['blocked_clicks'])/$r['total_clicks'] >= 0.05 ? 'Medium' : 'High')) : '—';
        $lastAct = $r['last_click_at'] ?: $r['last_conv_at'] ?: $r['last_login'];
        echo '<tr>'.implode('', array_map(fn($v)=>'<td>'.htmlspecialchars((string)$v).'</td>', [
            $r['affiliate_id'], $r['affiliate_code'], $r['aff_name'], $r['email'], $r['user_status'],
            $r['total_clicks'], $r['unique_clicks'], $r['fraud_clicks'], $r['blocked_clicks'],
            $r['conv_total'], $r['conv_approved'], $r['conv_pending'], $r['conv_rejected'],
            $cr.'%', '$'.$epc, '$'.number_format($r['payout'],2), '$'.number_format($r['revenue'],2),
            '$'.number_format($r['revenue']-$r['payout'],2), $tq, $lastAct ?? '', $r['registered_at'] ?? '',
        ])).'</tr>';
    }
    echo '</table></body></html>'; exit;
}

require BASE_PATH . '/views/admin/reports/affiliate_report.php';
