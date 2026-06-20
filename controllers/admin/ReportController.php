<?php
Auth::check('admin');
$pageTitle = 'Reports';

// Ensure required geo columns exist on conversions table
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country`      VARCHAR(60)  DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country_code` CHAR(2)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_city`         VARCHAR(100) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_state`        VARCHAR(100) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_isp`          VARCHAR(200) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_org`          VARCHAR(200) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_asn`          VARCHAR(30)  DEFAULT NULL"); } catch (\Throwable $_e) {}

/**
 * Lazy geo-backfill helper.
 * Called after fetching conversion rows. For any row where country/city/region
 * are ALL empty but a valid conv_ip exists, call IPQuery to resolve the geo
 * and write it back to the conversions table. Limited to $batchMax per page
 * load to avoid timeouts. On subsequent loads the data is already cached.
 */
function _backfillMissingGeo(array &$rows, int $batchMax = 25): void {
    $backfilled = 0;
    foreach ($rows as &$r) {
        if ($backfilled >= $batchMax) break;
        // Skip if geo data already present
        if (!empty($r['country']) || !empty($r['city']) || !empty($r['region'])) continue;
        // Skip if no IP to look up
        $ip = trim($r['conv_ip'] ?? '');
        if ($ip === '' || $ip === '0.0.0.0') continue;

        // Strip CIDR notation
        if (strpos($ip, '/') !== false) $ip = explode('/', $ip)[0];

        try {
            $geo = FraudIQ::checkIPQuery($ip);
            $cc    = $geo['country_code'] ?? '';
            $city  = $geo['city']         ?? '';
            $state = $geo['state']        ?? '';

            if ($cc !== '' || $city !== '' || $state !== '') {
                // Write back to DB so next page load is instant
                Database::query(
                    "UPDATE `conversions` SET
                        `ipquery_country`      = COALESCE(`ipquery_country`, ?),
                        `ipquery_country_code` = COALESCE(`ipquery_country_code`, ?),
                        `ipquery_city`         = COALESCE(`ipquery_city`, ?),
                        `ipquery_state`        = COALESCE(`ipquery_state`, ?),
                        `ipquery_isp`          = COALESCE(`ipquery_isp`, ?),
                        `ipquery_org`          = COALESCE(`ipquery_org`, ?),
                        `ipquery_asn`          = COALESCE(`ipquery_asn`, ?)
                     WHERE `conversion_id` = ?",
                    [
                        $geo['country']      ?? '',
                        $cc,
                        $city,
                        $state,
                        $geo['isp'] ?? '',
                        $geo['org'] ?? '',
                        $geo['asn'] ?? '',
                        $r['conversion_id'],
                    ]
                );
                // Patch the in-memory row so it renders immediately
                $r['country'] = $cc;
                $r['city']    = $city;
                $r['region']  = $state;
                $backfilled++;
            }
        } catch (\Throwable $_e) {
            // Silently skip — next page load will retry
        }
    }
    unset($r);
}

$tab     = Helpers::get('tab') ?: 'performance';
$from    = Helpers::get('from') ?: date('Y-m-01');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)(Helpers::get('offer_id') ?: 0);
$affId   = (int)(Helpers::get('affiliate_id') ?: 0);
$affCode = trim(Helpers::get('affiliate_code') ?? '');
if ($affCode !== '' && $affId === 0) {
    $_affRow = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code = ?", [$affCode]);
    if ($_affRow) $affId = (int)$_affRow['id'];
}
$country = trim(Helpers::get('country') ?: '');
$sub1    = trim(Helpers::get('sub1') ?: '');
$limit   = min((int)(Helpers::get('limit') ?: 1000), 10000);

$slId    = (int)(Helpers::get('sl_id') ?: 0);

$offerList = Database::fetchAll("SELECT id, name FROM offers ORDER BY name");
$affList   = Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name"
);
$countryList = Database::fetchAll(
    "SELECT DISTINCT country FROM clicks WHERE country != '' ORDER BY country"
);
$slList = [];
try {
    $slList = Database::fetchAll("SELECT id, name, slug FROM smartlinks WHERE status='active' ORDER BY name");
} catch (\Throwable $_e) {}

// ─── helpers ──────────────────────────────────────────────────────────────
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

// Build click WHERE parts
function buildClickWhere(int $offerId, int $affId, string $country, string $sub1): array {
    $where  = ['c.clicked_at BETWEEN ? AND ?'];
    $params = [];  // date params added later
    if ($offerId > 0) { $where[] = 'c.offer_id = ?';      $params[] = $offerId; }
    if ($affId   > 0) { $where[] = 'c.affiliate_id = ?';  $params[] = $affId; }
    if ($country !== '') { $where[] = 'c.country = ?';    $params[] = strtoupper($country); }
    if ($sub1    !== '') { $where[] = 'c.sub1 LIKE ?';    $params[] = '%'.$sub1.'%'; }
    return [$where, $params];
}

// Build conversion WHERE parts
function buildConvWhere(int $offerId, int $affId, string $country, string $sub1): array {
    $where  = ['cv.converted_at BETWEEN ? AND ?'];
    $params = [];
    if ($offerId > 0) { $where[] = 'cv.offer_id = ?';     $params[] = $offerId; }
    if ($affId   > 0) { $where[] = 'cv.affiliate_id = ?'; $params[] = $affId; }
    if ($country !== '') {
        $where[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.country=?)';
        $params[] = strtoupper($country);
    }
    if ($sub1 !== '') {
        $where[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.sub1 LIKE ?)';
        $params[] = '%'.$sub1.'%';
    }
    return [$where, $params];
}

// ─── CSV export helper ────────────────────────────────────────────────────
$_exportFormat = Helpers::get('export');                       // '', 'csv', or 'xls'
$isExport      = ($_exportFormat === 'csv' || $_exportFormat === 'xls');

// ─── Performance tab (aggregated) ────────────────────────────────────────
$rows = $totals = $groupBy = null;
if ($tab === 'performance') {
    $groupBy = Helpers::get('group_by') ?: 'date';
    $validGroups = ['date','offer','affiliate','country','sub'];
    if (!in_array($groupBy, $validGroups)) $groupBy = 'date';

    if (in_array($groupBy, ['date','offer','affiliate'])) {
        // Use stats_daily (fast)
        $selectMap = [
            'date'      => 'sd.stat_date as label',
            'offer'     => 'o.name as label',
            'affiliate' => "CONCAT(u.first_name,' ',u.last_name) as label",
        ];
        $groupMap = [
            'date'      => 'sd.stat_date',
            'offer'     => 'sd.offer_id',
            'affiliate' => 'sd.affiliate_id',
        ];
        $joinMap = [
            'date'      => '',
            'offer'     => 'JOIN offers o ON o.id=sd.offer_id',
            'affiliate' => 'JOIN affiliates af ON af.id=sd.affiliate_id JOIN users u ON u.id=af.user_id',
        ];
        $sdWhere  = ['sd.stat_date BETWEEN ? AND ?'];
        $sdParams = [$from, $to];
        if ($offerId > 0) { $sdWhere[] = 'sd.offer_id = ?';     $sdParams[] = $offerId; }
        if ($affId   > 0) { $sdWhere[] = 'sd.affiliate_id = ?'; $sdParams[] = $affId; }
        $whereStr = implode(' AND ', $sdWhere);

        $rows = Database::fetchAll(
            "SELECT {$selectMap[$groupBy]},
                    SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                    SUM(sd.conversions) as conversions, SUM(sd.approved) as approved,
                    SUM(sd.rejected) as rejected, SUM(sd.payout) as payout,
                    SUM(sd.revenue) as revenue, SUM(sd.fraud_clicks) as fraud_clicks,
                    SUM(sd.impressions) as impressions
             FROM stats_daily sd {$joinMap[$groupBy]}
             WHERE $whereStr
             GROUP BY {$groupMap[$groupBy]} ORDER BY payout DESC",
            $sdParams
        );
    } else {
        // Group by country or sub1 — query from clicks table
        $selectGrp = $groupBy === 'country' ? 'c.country as label' : 'c.sub1 as label';
        $groupGrp  = $groupBy === 'country' ? 'c.country'          : 'c.sub1';

        $clkWhere  = ['c.clicked_at BETWEEN ? AND ?'];
        $clkParams = [$dateFrom, $dateTo];
        if ($offerId > 0) { $clkWhere[] = 'c.offer_id = ?';     $clkParams[] = $offerId; }
        if ($affId   > 0) { $clkWhere[] = 'c.affiliate_id = ?'; $clkParams[] = $affId; }
        if ($country !== '' && $groupBy !== 'country') { $clkWhere[] = 'c.country = ?'; $clkParams[] = strtoupper($country); }
        if ($sub1    !== '' && $groupBy !== 'sub')     { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
        $whereStr = implode(' AND ', $clkWhere);

        $rows = Database::fetchAll(
            "SELECT $selectGrp,
                    COUNT(*) as clicks, SUM(c.is_unique) as uclicks,
                    SUM(c.is_fraud) as fraud_clicks,
                    SUM(c.payout) as payout, SUM(c.revenue) as revenue,
                    0 as conversions, 0 as approved, 0 as rejected, 0 as impressions
             FROM clicks c
             WHERE $whereStr
             GROUP BY $groupGrp ORDER BY clicks DESC
             LIMIT $limit",
            $clkParams
        );
    }

    $totals = [
        'clicks'      => array_sum(array_column($rows,'clicks')),
        'uclicks'     => array_sum(array_column($rows,'uclicks')),
        'conversions' => array_sum(array_column($rows,'conversions')),
        'approved'    => array_sum(array_column($rows,'approved')),
        'rejected'    => array_sum(array_column($rows,'rejected')),
        'fraud_clicks'=> array_sum(array_column($rows,'fraud_clicks')),
        'payout'      => array_sum(array_column($rows,'payout')),
        'revenue'     => array_sum(array_column($rows,'revenue')),
    ];
    $totals['profit'] = $totals['revenue'] - $totals['payout'];

    // ── IPQS fraud stats per label for the expandable fraud panel ──────────
    // Only for date/offer/affiliate groupings (stats_daily based rows).
    // For country/sub we skip — those rows don't have stable IDs to join on.
    $perfIpqsStats = [];
    if (in_array($groupBy, ['date','offer','affiliate'], true) && !empty($rows)) {
        if ($groupBy === 'date') {
            $ipqsLabels = array_column($rows, 'label');
            if (!empty($ipqsLabels)) {
                $ph = implode(',', array_fill(0, count($ipqsLabels), '?'));
                $ipqsRaw = Database::fetchAll("
                    SELECT DATE(cv.converted_at) AS label,
                           COUNT(*)                                                        AS total_checked,
                           ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END),1) AS avg_score,
                           MAX(cv.fraud_score)                                             AS max_score,
                           SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)          AS high_risk,
                           SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END) AS medium_risk,
                           SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk,
                           SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END)   AS pending_check,
                           SUM(CASE WHEN cv.ipquery_vpn = 1 THEN 1 ELSE 0 END)            AS vpn_count,
                           SUM(CASE WHEN cv.ipquery_proxy = 1 THEN 1 ELSE 0 END)          AS proxy_count,
                           SUM(CASE WHEN cv.ipquery_tor = 1 THEN 1 ELSE 0 END)            AS tor_count,
                           SUM(CASE WHEN cv.ipquery_datacenter = 1 THEN 1 ELSE 0 END)     AS datacenter_count,
                           SUM(CASE WHEN cv.status = 'rejected' THEN 1 ELSE 0 END)        AS rejected_conv
                      FROM conversions cv
                     WHERE DATE(cv.converted_at) IN ($ph)
                       AND cv.converted_at BETWEEN ? AND ?
                    " . ($offerId > 0 ? ' AND cv.offer_id = ' . (int)$offerId : '')
                      . ($affId   > 0 ? ' AND cv.affiliate_id = ' . (int)$affId : '') . "
                     GROUP BY DATE(cv.converted_at)
                ", array_merge($ipqsLabels, [$dateFrom, $dateTo])) ?: [];
                foreach ($ipqsRaw as $r2) $perfIpqsStats[$r2['label']] = $r2;
            }
        } elseif ($groupBy === 'offer') {
            $offerIds = array_filter(array_map(fn($r) => (int)($r['offer_id'] ?? 0), $rows));
            // When grouping by offer the label is the offer name — also pull offer_id
            // by re-running a small query.
            $ipqsRaw = Database::fetchAll("
                SELECT cv.offer_id AS key_id,
                       o.name AS label,
                       COUNT(*)                                                        AS total_checked,
                       ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END),1) AS avg_score,
                       MAX(cv.fraud_score)                                             AS max_score,
                       SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)          AS high_risk,
                       SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END) AS medium_risk,
                       SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk,
                       SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END)   AS pending_check,
                       SUM(CASE WHEN cv.ipquery_vpn = 1 THEN 1 ELSE 0 END)            AS vpn_count,
                       SUM(CASE WHEN cv.ipquery_proxy = 1 THEN 1 ELSE 0 END)          AS proxy_count,
                       SUM(CASE WHEN cv.ipquery_tor = 1 THEN 1 ELSE 0 END)            AS tor_count,
                       SUM(CASE WHEN cv.ipquery_datacenter = 1 THEN 1 ELSE 0 END)     AS datacenter_count,
                       SUM(CASE WHEN cv.status = 'rejected' THEN 1 ELSE 0 END)        AS rejected_conv
                  FROM conversions cv
                  JOIN offers o ON o.id = cv.offer_id
                 WHERE cv.converted_at BETWEEN ? AND ?
                " . ($affId > 0 ? ' AND cv.affiliate_id = ' . (int)$affId : '') . "
                 GROUP BY cv.offer_id
            ", [$dateFrom, $dateTo]) ?: [];
            foreach ($ipqsRaw as $r2) $perfIpqsStats[$r2['label']] = $r2;
        } elseif ($groupBy === 'affiliate') {
            $ipqsRaw = Database::fetchAll("
                SELECT CONCAT(u.first_name,' ',u.last_name) AS label,
                       cv.affiliate_id AS key_id,
                       COUNT(*)                                                        AS total_checked,
                       ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END),1) AS avg_score,
                       MAX(cv.fraud_score)                                             AS max_score,
                       SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)          AS high_risk,
                       SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END) AS medium_risk,
                       SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk,
                       SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END)   AS pending_check,
                       SUM(CASE WHEN cv.ipquery_vpn = 1 THEN 1 ELSE 0 END)            AS vpn_count,
                       SUM(CASE WHEN cv.ipquery_proxy = 1 THEN 1 ELSE 0 END)          AS proxy_count,
                       SUM(CASE WHEN cv.ipquery_tor = 1 THEN 1 ELSE 0 END)            AS tor_count,
                       SUM(CASE WHEN cv.ipquery_datacenter = 1 THEN 1 ELSE 0 END)     AS datacenter_count,
                       SUM(CASE WHEN cv.status = 'rejected' THEN 1 ELSE 0 END)        AS rejected_conv
                  FROM conversions cv
                  JOIN affiliates af ON af.id = cv.affiliate_id
                  JOIN users u ON u.id = af.user_id
                 WHERE cv.converted_at BETWEEN ? AND ?
                " . ($offerId > 0 ? ' AND cv.offer_id = ' . (int)$offerId : '') . "
                 GROUP BY cv.affiliate_id
            ", [$dateFrom, $dateTo]) ?: [];
            foreach ($ipqsRaw as $r2) $perfIpqsStats[$r2['label']] = $r2;
        }
    }

    // ── Real-time rejected conversions endpoint ───────────────────────────
    // Called by JS polling: ?tab=performance&action=rt_rejected&group_by=date&from=…&to=…
    if (Helpers::get('action') === 'rt_rejected' && in_array($groupBy, ['date','offer','affiliate'], true)) {
        header('Content-Type: application/json');
        $rtData = [];
        foreach ($perfIpqsStats as $label => $s) {
            $rtData[$label] = ['rejected' => (int)$s['rejected_conv']];
        }
        echo json_encode(['ok' => true, 'data' => $rtData]);
        exit;
    }

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report-performance-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f, [ucfirst($groupBy),'Clicks','Unique','Conversions','Approved','Rejected','Fraud','CR%','EPC','Payout','Revenue','Profit']);
        foreach ($rows as $r) {
            $cr  = $r['clicks'] > 0 ? round($r['conversions']/$r['clicks']*100,2) : 0;
            $epc = $r['clicks'] > 0 ? round($r['payout']/$r['clicks'],4) : 0;
            fputcsv($f, [$r['label'],$r['clicks'],$r['uclicks'],$r['conversions'],$r['approved'],$r['rejected'],$r['fraud_clicks'],$cr,$epc,number_format($r['payout'],2),number_format($r['revenue'],2),number_format($r['revenue']-$r['payout'],2)]);
        }
        fclose($f); exit;
    }
}

// ─── Offer Report tab ────────────────────────────────────────────────────
$offerReportRows = $offerReportTotals = null;
if ($tab === 'offer_report') {
    $orWhere  = ['sd.stat_date BETWEEN ? AND ?'];
    $orParams = [$from, $to];
    if ($offerId > 0) { $orWhere[] = 'sd.offer_id = ?'; $orParams[] = $offerId; }
    if ($affId   > 0) { $orWhere[] = 'sd.affiliate_id = ?'; $orParams[] = $affId; }
    $whereStr = implode(' AND ', $orWhere);

    // Additive IPQS fraud-conversion subquery: counts conversions with
    // fraud_score >= 60 per offer in the same date window. Joined onto the
    // existing aggregation as a new column — never modifies clicks /
    // conversions / approved / rejected calculations above.
    $orParamsFull = $orParams;
    $fraudJoinSql = "LEFT JOIN (
        SELECT cv.offer_id,
               SUM(CASE WHEN COALESCE(cv.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_conv
        FROM conversions cv
        WHERE DATE(cv.converted_at) BETWEEN ? AND ? AND COALESCE(cv.is_hidden,0) = 0
        " . ($affId > 0 ? " AND cv.affiliate_id = " . (int)$affId : "") . "
        GROUP BY cv.offer_id
    ) cvf ON cvf.offer_id = sd.offer_id";
    $orParamsFull = array_merge([$from, $to], $orParams);

    $offerReportRows = Database::fetchAll(
        "SELECT o.id as offer_id, o.name as offer_name, o.status as offer_status,
                o.category, o.payout_type, o.payout_amount, o.revenue_amount,
                o.daily_cap, o.total_cap, o.visibility,
                SUM(sd.clicks)        as clicks,
                SUM(sd.unique_clicks) as uclicks,
                SUM(sd.impressions)   as impressions,
                SUM(sd.conversions)   as conversions,
                SUM(sd.approved)      as approved,
                SUM(sd.rejected)      as rejected,
                SUM(sd.fraud_clicks)  as fraud_clicks,
                COALESCE(MAX(cvf.fraud_conv), 0) as fraud_conv,
                SUM(sd.payout)        as payout,
                SUM(sd.revenue)       as revenue,
                COUNT(DISTINCT sd.affiliate_id) as aff_count
         FROM stats_daily sd
         JOIN offers o ON o.id = sd.offer_id
         $fraudJoinSql
         WHERE $whereStr
         GROUP BY o.id
         ORDER BY payout DESC",
        $orParamsFull
    );

    $offerReportTotals = [
        'clicks'      => array_sum(array_column($offerReportRows, 'clicks')),
        'uclicks'     => array_sum(array_column($offerReportRows, 'uclicks')),
        'impressions' => array_sum(array_column($offerReportRows, 'impressions')),
        'conversions' => array_sum(array_column($offerReportRows, 'conversions')),
        'approved'    => array_sum(array_column($offerReportRows, 'approved')),
        'rejected'    => array_sum(array_column($offerReportRows, 'rejected')),
        'fraud_clicks'=> array_sum(array_column($offerReportRows, 'fraud_clicks')),
        // IPQS fraud-conversion total — visibility overlay only.
        'fraud_conv'  => array_sum(array_column($offerReportRows, 'fraud_conv')),
        'payout'      => array_sum(array_column($offerReportRows, 'payout')),
        'revenue'     => array_sum(array_column($offerReportRows, 'revenue')),
    ];
    $offerReportTotals['profit'] = $offerReportTotals['revenue'] - $offerReportTotals['payout'];

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report-offers-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['OFFER','STATUS','CATEGORY','PAYOUT TYPE','PAYOUT RATE','REVENUE RATE','DAILY CAP','TOTAL CAP','AFFILIATES','IMPRESSIONS','CLICKS','UNIQUE','CONVERSIONS','APPROVED','REJECTED','FRAUD','CR%','EPC','PAYOUT','REVENUE','PROFIT']);
        foreach ($offerReportRows as $r) {
            $cr     = $r['clicks'] > 0 ? round($r['conversions'] / $r['clicks'] * 100, 2) : 0;
            $epc    = $r['clicks'] > 0 ? round($r['payout'] / $r['clicks'], 4) : 0;
            $profit = (float)$r['revenue'] - (float)$r['payout'];
            fputcsv($f, [$r['offer_name'], $r['offer_status'], $r['category'] ?? '', $r['payout_type'], number_format($r['payout_amount'], 4), number_format($r['revenue_amount'], 4), $r['daily_cap'] ?: '∞', $r['total_cap'] ?: '∞', $r['aff_count'], $r['impressions'], $r['clicks'], $r['uclicks'], $r['conversions'], $r['approved'], $r['rejected'], $r['fraud_clicks'], $cr, $epc, number_format($r['payout'], 2), number_format($r['revenue'], 2), number_format($profit, 2)]);
        }
        fclose($f); exit;
    }
}

// ─── Clicks tab ───────────────────────────────────────────────────────────
$clicks = null;
if ($tab === 'clicks') {
    [$clkWhere, $clkExtra] = buildClickWhere($offerId, $affId, $country, $sub1);
    $clkParams = array_merge([$dateFrom, $dateTo], $clkExtra);
    $whereStr  = implode(' AND ', $clkWhere);

    // Revenue and payout are shown only for clicks that resulted in a conversion.
    // Clicks without a matching approved/pending conversion display $0.00.
    // COALESCE(cv.revenue,0) achieves this via LEFT JOIN on conversions.
    $clicks = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.referer,
                c.is_fraud, c.fraud_score, c.fraud_reasons, c.os, c.browser, c.user_agent,
                c.device_type, c.ip_address, c.country, c.city, c.region,
                COALESCE(cv.revenue, 0) as revenue,
                COALESCE(cv.payout,  0) as payout,
                (COALESCE(cv.revenue, 0) - COALESCE(cv.payout, 0)) as profit,
                (cv.conversion_id IS NOT NULL) as has_conversion,
                cv.status as conv_status,
                c.clicked_at, c.status,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
         FROM clicks c
         JOIN offers o ON o.id=c.offer_id
         JOIN affiliates af ON af.id=c.affiliate_id
         JOIN users u ON u.id=af.user_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id
               AND cv.status IN ('approved','pending')
               AND cv.is_hidden = 0
         WHERE $whereStr
         ORDER BY c.clicked_at DESC LIMIT $limit",
        $clkParams
    );

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report-clicks-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['OFFER','AFFILIATE','AFF CODE','CLICK ID','AFF CLICK ID','AFF SUB 1','AFF SUB 2','AFF SUB 3','SOURCE','FRAUD','OS','BROWSER','USER AGENT','DEVICE TYPE','IP','COUNTRY','CITY','REGION','CONVERTED','CONV STATUS','REVENUE','PAYOUT','PROFIT','CLICK TIME','STATUS']);
        foreach ($clicks as $r) {
            fputcsv($f,[$r['offer_name'],$r['aff_name'],$r['affiliate_code'],$r['click_id'],$r['sub1'],$r['sub2'],$r['sub3'],$r['sub4'],$r['referer'],$r['is_fraud']?'Fraud':'Clean',$r['os'],$r['browser'],$r['user_agent'],$r['device_type'],$r['ip_address'],$r['country'],$r['city'],$r['region'],$r['has_conversion']?'Yes':'No',$r['conv_status']??'—',number_format($r['revenue'],4),number_format($r['payout'],4),number_format($r['profit'],4),$r['clicked_at'],$r['status']]);
        }
        fclose($f); exit;
    }
}

// ─── Conversions / Rejected / Pending / Autohide tabs ────────────────────
$convRows = null;
$convStatusFilter = match($tab) {
    'conversions' => 'approved',
    'rejected'    => 'rejected',
    'pending'     => 'pending',
    'autohide'    => null,   // fraud
    default       => null,
};

if (in_array($tab, ['conversions','rejected','pending','autohide'])) {
    [$cvWhere, $cvExtra] = buildConvWhere($offerId, $affId, $country, $sub1);
    $cvParams = array_merge([$dateFrom, $dateTo], $cvExtra);

    if ($tab === 'autohide') {
        // Show all hidden conversions (auto-hide rules + FraudIQ blocked).
        // Previously filtered cv.is_fraud=1 but FraudIQ sets is_hidden=1 (not
        // is_fraud on the conversion), so hidden conversions never appeared here.
        $cvWhere[] = 'cv.is_hidden = 1';
    } else {
        if ($convStatusFilter) {
            $cvWhere[] = 'cv.status = ?';
            $cvParams[] = $convStatusFilter;
        }
        // Exclude hidden conversions from all non-autohide tabs.
        // Hidden conversions have postback_sent=0 (we exit before PostbackFirer
        // fires for them) and should only appear in the Autohide tab.
        $cvWhere[] = 'COALESCE(cv.is_hidden, 0) = 0';
    }
    $whereStr = implode(' AND ', $cvWhere);

    $convRows = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.offer_id, cv.status, cv.payout, cv.revenue,
                (cv.revenue-cv.payout) as profit,
                cv.is_fraud, cv.transaction_id, cv.goal_name, cv.converted_at,
                cv.postback_sent, cv.ip_address as conv_ip,
                cv.fraud_score, cv.fraud_checked_at,
                COALESCE(cv.rejection_reason, '') as rejection_reason,
                cv.rejected_at,
                o.name as offer_name, o.category as offer_category,
                o.offer_url as offer_page, o.landing_pages as offer_landing_pages,
                o.landing_page_names as offer_landing_page_names,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                ck.sub1, ck.sub2, ck.sub3, ck.os, ck.browser, ck.user_agent,
                ck.landing_page_idx, ck.smartlink_id as flow_id,
                /* Country fallback chain — older rows may have no click record so
                   ck.country is NULL; fall back to the IPQuery geo code captured
                   when fraud-checking the conversion itself, then to empty. */
                COALESCE(NULLIF(ck.country, ''), NULLIF(cv.ipquery_country_code, ''), '') as country,
                COALESCE(NULLIF(ck.city, ''), NULLIF(cv.ipquery_city, ''), '') as city,
                COALESCE(NULLIF(ck.region, ''), NULLIF(cv.ipquery_state, ''), '') as region
         FROM conversions cv
         LEFT JOIN offers o ON o.id=cv.offer_id
         JOIN affiliates af ON af.id=cv.affiliate_id
         JOIN users u ON u.id=af.user_id
         LEFT JOIN clicks ck ON ck.click_id=cv.click_id
         WHERE $whereStr
         ORDER BY cv.converted_at DESC LIMIT $limit",
        $cvParams
    );

    // Auto-backfill missing geo data (country/city/state) from IP addresses.
    // Processes up to 25 rows per page load; results are cached in the DB.
    if (!empty($convRows)) {
        _backfillMissingGeo($convRows, 25);
    }

    // Pre-compute CR / CTR stats per offer for the selected date range
    $offerStatMap = [];
    if (!empty($convRows)) {
        $convOfferIds = array_values(array_unique(array_column($convRows, 'offer_id')));
        if (!empty($convOfferIds)) {
            $ph = implode(',', array_fill(0, count($convOfferIds), '?'));
            $osRows = Database::fetchAll(
                "SELECT offer_id,
                        SUM(clicks) as total_clicks,
                        SUM(unique_clicks) as total_unique,
                        SUM(conversions) as total_conv,
                        COALESCE(SUM(impressions),0) as total_impr
                 FROM stats_daily
                 WHERE offer_id IN ($ph)
                   AND stat_date BETWEEN ? AND ?
                 GROUP BY offer_id",
                array_merge($convOfferIds, [$dateFrom, $dateTo])
            );
            foreach ($osRows as $os) {
                $offerStatMap[(int)$os['offer_id']] = $os;
            }
        }
    }

    if ($isExport) {
        $headerRow = ['CONVERSION ID','CLICK ID','OFFER','AFFILIATE','AFF CODE','SUB1','SUB2','STATUS','REJECTION REASON','REJECTED AT','PAYOUT','REVENUE','PROFIT','TRANSACTION ID','GOAL','COUNTRY','CITY','STATE','OS','BROWSER','CONV IP','USER AGENT','FRAUD SCORE','POSTBACK SENT','CONVERTED AT','DEVICE BRAND','DEVICE MODEL','CATEGORY','PRELAND','LANDING PAGE NAME','OFFER PAGE','FLOW ID','CR (VISIT)','CR (CLICK)','CR (UNIQUE)','CTR'];
        if ($_exportFormat === 'xls') {
            ExportHelper::beginXls('report-'.$tab);
            ExportHelper::xlsHeaderRow($headerRow);
        } else {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report-'.$tab.'-'.date('Y-m-d').'.csv"');
            $f = fopen('php://output','w');
            fputcsv($f, $headerRow);
        }
        foreach ($convRows as $r) {
            $ua = $r['user_agent'] ?? '';
            $dBrand = ''; $dModel = '';
            $knownBrands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
            if ($ua !== '') {
                if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $ua, $m)) {
                    $raw = trim($m[1]); $brand = '';
                    foreach ($knownBrands as $b) { if (stripos($raw,$b)===0){$brand=$b;break;} }
                    if ($brand){$dBrand=$brand;$dModel=trim(substr($raw,strlen($brand)))?:$raw;}
                    else{$dBrand='Android';$dModel=$raw;}
                } elseif (stripos($ua,'iPhone')!==false){$dBrand='Apple';$dModel='iPhone';}
                elseif (stripos($ua,'iPad')!==false){$dBrand='Apple';$dModel='iPad';}
                elseif (stripos($ua,'Windows')!==false){$dBrand='PC';$dModel='Windows';}
            }
            $lpArr  = !empty($r['offer_landing_pages'])      ? json_decode($r['offer_landing_pages'],true)      : null;
            $lpNArr = !empty($r['offer_landing_page_names']) ? json_decode($r['offer_landing_page_names'],true) : null;
            $preland = (is_array($lpArr)  && $r['landing_page_idx'] !== null && isset($lpArr[(int)$r['landing_page_idx']]))  ? $lpArr[(int)$r['landing_page_idx']]  : '';
            $lpName  = (is_array($lpNArr) && $r['landing_page_idx'] !== null && isset($lpNArr[(int)$r['landing_page_idx']])) ? $lpNArr[(int)$r['landing_page_idx']] : '';
            $oid = (int)$r['offer_id'];
            $os = $offerStatMap[$oid] ?? [];
            $crVisit  = (!empty($os['total_impr'])  && $os['total_impr']  > 0) ? round($os['total_conv']/$os['total_impr']*100,2).'%' : '—';
            $crClick  = (!empty($os['total_clicks'])&& $os['total_clicks']> 0) ? round($os['total_conv']/$os['total_clicks']*100,2).'%' : '—';
            $crUnique = (!empty($os['total_unique'])&& $os['total_unique']> 0) ? round($os['total_conv']/$os['total_unique']*100,2).'%' : '—';
            $ctr      = (!empty($os['total_impr'])  && $os['total_impr']  > 0) ? round($os['total_clicks']/$os['total_impr']*100,2).'%' : '—';
            $rowCells = [$r['conversion_id'],$r['click_id'],$r['offer_name']??'—',$r['aff_name'],$r['affiliate_code'],$r['sub1'],$r['sub2'],$r['status'],$r['rejection_reason']??'',$r['rejected_at']??'',number_format($r['payout'],4),number_format($r['revenue'],4),number_format($r['profit'],4),$r['transaction_id'],$r['goal_name'],$r['country'],$r['city'],$r['region'],$r['os'],$r['browser'],$r['conv_ip']??'',$ua,!empty($r['fraud_checked_at'])?(int)$r['fraud_score']:'pending',$r['postback_sent']?'Yes':'No',$r['converted_at'],$dBrand,$dModel,$r['offer_category']??'',$preland,$lpName,$r['offer_page']??'',$r['flow_id']??'',$crVisit,$crClick,$crUnique,$ctr];
            if ($_exportFormat === 'xls') ExportHelper::xlsRow($rowCells);
            else                          fputcsv($f, $rowCells);
        }
        if ($_exportFormat === 'xls') ExportHelper::endXls();
        else                          fclose($f);
        exit;
    }
}

// ─── Postback log tab ─────────────────────────────────────────────────────
$postbackRows = null;
if ($tab === 'postback') {
    $pbWhere  = ['pl.fired_at BETWEEN ? AND ?'];
    $pbParams = [$dateFrom, $dateTo];

    // For affiliate-level postbacks (postback_id > 0) we can filter by offer/aff
    // For affiliate GLOBAL postbacks (postback_id = 0) the join is via conversions
    if ($offerId > 0) {
        $pbWhere[] = '(pb.offer_id = ? OR (pl.postback_id = 0 AND cv_filter.offer_id = ?))';
        $pbParams[] = $offerId;
        $pbParams[] = $offerId;
    }
    if ($affId > 0) {
        $pbWhere[] = '(pb.affiliate_id = ? OR (pl.postback_id = 0 AND cv_filter.affiliate_id = ?))';
        $pbParams[] = $affId;
        $pbParams[] = $affId;
    }
    $whereStr = implode(' AND ', $pbWhere);

    // LEFT JOIN postbacks so postback_id=0 rows (affiliate global) are included.
    // A second LEFT JOIN on conversions provides affiliate/offer context for global rows.
    $postbackRows = Database::fetchAll(
        "SELECT pl.id, pl.conversion_id, pl.fired_url, pl.http_status, pl.response_body,
                pl.is_success,
                COALESCE(pl.attempt_count, 1) as attempt_count,
                pl.fired_at,
                pl.postback_id,
                COALESCE(pb.url,  '[Affiliate Global Postback]') as template_url,
                COALESCE(pb.event,'conversion')                  as event,
                COALESCE(pb.method,'GET')                        as method,
                COALESCE(
                    CONCAT(u.first_name,' ',u.last_name),
                    CONCAT(u2.first_name,' ',u2.last_name)
                ) as aff_name,
                COALESCE(af.affiliate_code, af2.affiliate_code)  as affiliate_code,
                COALESCE(o.name, o2.name, 'All Offers')          as offer_name,
                cv.status as conv_status, cv.payout
         FROM postback_logs pl
         LEFT JOIN postbacks pb  ON pb.id  = pl.postback_id AND pl.postback_id > 0
         LEFT JOIN affiliates af ON af.id  = pb.affiliate_id
         LEFT JOIN users u       ON u.id   = af.user_id
         LEFT JOIN offers o      ON o.id   = pb.offer_id
         LEFT JOIN conversions cv         ON cv.conversion_id  = pl.conversion_id
         LEFT JOIN conversions cv_filter  ON cv_filter.conversion_id = pl.conversion_id
         LEFT JOIN affiliates af2         ON af2.id = cv.affiliate_id AND pl.postback_id = 0
         LEFT JOIN users u2               ON u2.id  = af2.user_id     AND pl.postback_id = 0
         LEFT JOIN offers o2              ON o2.id  = cv.offer_id     AND pl.postback_id = 0
         WHERE $whereStr
         GROUP BY pl.id
         ORDER BY pl.fired_at DESC LIMIT $limit",
        $pbParams
    );

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report-postbacklog-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['ID','TYPE','CONVERSION ID','AFFILIATE','AFF CODE','OFFER','EVENT','METHOD',
                    'HTTP STATUS','SUCCESS','ATTEMPTS','FIRED URL','RESPONSE','FIRED AT']);
        foreach ($postbackRows as $r) {
            $type = ($r['postback_id'] == 0) ? 'Global (Affiliate)' : 'Affiliate Postback';
            fputcsv($f,[
                $r['id'], $type, $r['conversion_id'],
                $r['aff_name'], $r['affiliate_code'],
                $r['offer_name'], $r['event'], $r['method'],
                $r['http_status'], $r['is_success'] ? 'Yes' : 'No',
                $r['attempt_count'],
                substr($r['fired_url'], 0, 300),
                $r['response_body'],
                $r['fired_at'],
            ]);
        }
        fclose($f); exit;
    }
}

// ─── SmartLink Click Report tab ───────────────────────────────────────────
$slClicks = null;
if ($tab === 'sl_clicks') {
    $clkWhere  = ['c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $clkParams = [$dateFrom, $dateTo];
    if ($slId    > 0)    { $clkWhere[] = 'c.smartlink_id = ?'; $clkParams[] = $slId; }
    if ($affId   > 0)    { $clkWhere[] = 'c.affiliate_id = ?'; $clkParams[] = $affId; }
    if ($country !== '') { $clkWhere[] = 'c.country = ?';       $clkParams[] = strtoupper($country); }
    if ($sub1    !== '') { $clkWhere[] = 'c.sub1 LIKE ?';       $clkParams[] = '%'.$sub1.'%'; }
    $whereStr = implode(' AND ', $clkWhere);

    try {
        $slClicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.source,
                    c.ip_address, c.country, c.city, c.region, c.os, c.browser, c.device_type,
                    c.clicked_at, c.status, c.is_fraud, c.fraud_score,
                    COALESCE(sl.name, '— Unknown —') as smartlink_name,
                    COALESCE(sl.slug, '') as smartlink_slug,
                    COALESCE(o.name, '— Custom URL —') as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    COALESCE(cv.revenue, 0) as revenue,
                    COALESCE(cv.payout,  0) as payout,
                    (cv.conversion_id IS NOT NULL) as has_conversion,
                    cv.status as conv_status
             FROM clicks c
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN offers o ON o.id = c.offer_id
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id
                   AND cv.status IN ('approved','pending') AND cv.is_hidden = 0
             WHERE $whereStr
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $clkParams
        );
    } catch (\Throwable $_e) { $slClicks = []; }

    if ($isExport) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sl-click-report-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, ['SMARTLINK','OFFER','AFFILIATE','AFF CODE','CLICK ID','SUB1','SUB2','SUB3','SOURCE','FRAUD','OS','BROWSER','DEVICE','IP','COUNTRY','CITY','REGION','CONVERTED','CONV STATUS','REVENUE','PAYOUT','CLICK TIME']);
        foreach ($slClicks as $r) {
            fputcsv($f, [
                $r['smartlink_name'], $r['offer_name'], $r['aff_name'], $r['affiliate_code'],
                $r['click_id'], $r['sub1'], $r['sub2'], $r['sub3'], $r['source'],
                $r['is_fraud'] ? 'Fraud' : 'Clean', $r['os'], $r['browser'], $r['device_type'],
                $r['ip_address'], $r['country'], $r['city'], $r['region'],
                $r['has_conversion'] ? 'Yes' : 'No', $r['conv_status'] ?? '—',
                number_format((float)$r['revenue'], 4), number_format((float)$r['payout'], 4),
                $r['clicked_at'],
            ]);
        }
        fclose($f); exit;
    }
}

// ─── SmartLink Conversion Report tab ─────────────────────────────────────
$slConversions = null;
if ($tab === 'sl_conversions') {
    $cvWhere  = ['ck.smartlink_id IS NOT NULL', 'cv.converted_at BETWEEN ? AND ?', 'COALESCE(cv.is_hidden,0) = 0'];
    $cvParams = [$dateFrom, $dateTo];
    if ($slId    > 0)    { $cvWhere[] = 'ck.smartlink_id = ?'; $cvParams[] = $slId; }
    if ($affId   > 0)    { $cvWhere[] = 'cv.affiliate_id = ?'; $cvParams[] = $affId; }
    if ($country !== '') { $cvWhere[] = 'ck.country = ?';       $cvParams[] = strtoupper($country); }
    if ($sub1    !== '') { $cvWhere[] = 'ck.sub1 LIKE ?';       $cvParams[] = '%'.$sub1.'%'; }
    $whereStr = implode(' AND ', $cvWhere);

    try {
        $slConversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
                    (cv.revenue - cv.payout) as profit,
                    cv.converted_at, cv.goal_name, cv.transaction_id, cv.postback_sent,
                    COALESCE(sl.name, '— Unknown —') as smartlink_name,
                    COALESCE(o.name,  '— Custom URL —') as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    ck.sub1, ck.sub2,
                    COALESCE(NULLIF(ck.country,''), NULLIF(cv.ipquery_country_code,'')) as country,
                    COALESCE(NULLIF(ck.city,''), NULLIF(cv.ipquery_city,'')) as city,
                    COALESCE(NULLIF(ck.region,''), NULLIF(cv.ipquery_state,'')) as region,
                    ck.os, ck.browser, ck.device_type
             FROM conversions cv
             JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN smartlinks sl ON sl.id = ck.smartlink_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE $whereStr
             ORDER BY cv.converted_at DESC LIMIT $limit",
            $cvParams
        );
    } catch (\Throwable $_e) { $slConversions = []; }

    if ($isExport) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sl-conversion-report-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, ['SMARTLINK','OFFER','AFFILIATE','AFF CODE','CLICK ID','CONVERSION ID','SUB1','SUB2','STATUS','PAYOUT','REVENUE','PROFIT','GOAL','TXN ID','COUNTRY','CITY','STATE','OS','BROWSER','DEVICE','POSTBACK SENT','CONVERTED AT']);
        foreach ($slConversions as $r) {
            fputcsv($f, [
                $r['smartlink_name'], $r['offer_name'], $r['aff_name'], $r['affiliate_code'],
                $r['click_id'], $r['conversion_id'], $r['sub1'], $r['sub2'], $r['status'],
                number_format((float)$r['payout'], 4), number_format((float)$r['revenue'], 4),
                number_format((float)$r['profit'], 4),
                $r['goal_name'] ?? '—', $r['transaction_id'] ?? '—',
                $r['country'], $r['city'] ?? '', $r['region'] ?? '',
                $r['os'], $r['browser'], $r['device_type'],
                $r['postback_sent'] ? 'Yes' : 'No', $r['converted_at'],
            ]);
        }
        fclose($f); exit;
    }
}

// ─── SmartLink Affiliate Report tab ──────────────────────────────────────
$slAffiliates = null;
if ($tab === 'sl_affiliates') {
    $clkWhere  = ['c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $clkParams = [$dateFrom, $dateTo];
    if ($slId    > 0)    { $clkWhere[] = 'c.smartlink_id = ?'; $clkParams[] = $slId; }
    if ($affId   > 0)    { $clkWhere[] = 'c.affiliate_id = ?'; $clkParams[] = $affId; }
    if ($country !== '') { $clkWhere[] = 'c.country = ?';       $clkParams[] = strtoupper($country); }
    $whereStr = implode(' AND ', $clkWhere);

    try {
        $slAffiliates = Database::fetchAll(
            "SELECT CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    af.id as aff_id,
                    COALESCE(sl.name, '— Unknown —') as smartlink_name,
                    COALESCE(sl.slug, '') as smartlink_slug,
                    c.smartlink_id,
                    COUNT(c.click_id)    as clicks,
                    SUM(c.is_unique)     as uclicks,
                    SUM(c.is_fraud)      as fraud_clicks,
                    COUNT(cv.id)         as conversions,
                    SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN cv.status='pending'  THEN 1 ELSE 0 END) as pending_conv,
                    /* Additive overlay columns — visibility only, never affect payout/revenue.
                       fraud_conv counts conversions whose IPQS fraud_score is >= 60. */
                    SUM(CASE WHEN cv.status='rejected' THEN 1 ELSE 0 END) as rejected_conv,
                    SUM(CASE WHEN cv.id IS NOT NULL AND COALESCE(cv.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_conv,
                    COALESCE(SUM(CASE WHEN cv.status='approved' THEN cv.payout   ELSE 0 END), 0) as payout,
                    COALESCE(SUM(CASE WHEN cv.status='approved' THEN cv.revenue  ELSE 0 END), 0) as revenue
             FROM clicks c
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $whereStr
             GROUP BY af.id, c.smartlink_id
             ORDER BY clicks DESC
             LIMIT $limit",
            $clkParams
        );
    } catch (\Throwable $_e) { $slAffiliates = []; }

    if ($isExport) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sl-affiliate-report-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, ['AFFILIATE','AFF CODE','SMARTLINK','CLICKS','UNIQUE','FRAUD','CONVERSIONS','APPROVED','PENDING','CR%','EPC','PAYOUT','REVENUE','PROFIT']);
        foreach ($slAffiliates as $r) {
            $cr     = $r['clicks'] > 0 ? round($r['conversions'] / $r['clicks'] * 100, 2) : 0;
            $epc    = $r['clicks'] > 0 ? round($r['payout']      / $r['clicks'], 4)        : 0;
            $profit = (float)$r['revenue'] - (float)$r['payout'];
            fputcsv($f, [
                $r['aff_name'], $r['affiliate_code'], $r['smartlink_name'],
                $r['clicks'], $r['uclicks'], $r['fraud_clicks'],
                $r['conversions'], $r['approved'], $r['pending_conv'],
                $cr, $epc,
                number_format((float)$r['payout'], 2), number_format((float)$r['revenue'], 2),
                number_format($profit, 2),
            ]);
        }
        fclose($f); exit;
    }
}

require BASE_PATH . '/views/admin/reports/index.php';
