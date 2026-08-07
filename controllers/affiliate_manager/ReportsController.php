<?php
Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('view_affiliate_reports');

// Ensure is_hidden column exists (migration)
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

if (!Auth::hasPermission('view_reports')) {
    Helpers::flash('error', 'You do not have permission to view reports.');
    Helpers::redirect('/affiliate_manager/dashboard');
}
$pageTitle = 'Reports';

$affIds  = Auth::managerAffiliateIds();
$tab     = Helpers::get('tab') ?: 'day';
$from    = Helpers::get('from') ?: date('Y-m-01');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)(Helpers::get('offer_id') ?: 0);
$affId   = (int)(Helpers::get('affiliate_id') ?: 0);
$country = trim(Helpers::get('country') ?: '');
$sub1    = trim(Helpers::get('sub1') ?: '');
$limit   = min((int)(Helpers::get('limit') ?: 500), 5000);

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

// If manager has no affiliates, show empty everything
$hasAffiliates = !empty($affIds);

// IN clause helpers
$inSql    = $hasAffiliates ? implode(',', array_fill(0, count($affIds), '?')) : '0';
$inParams = $hasAffiliates ? $affIds : [];

// Respect selected specific affiliate (must be in managed list)
$activeAffIds = ($affId > 0 && in_array($affId, $affIds)) ? [$affId] : $affIds;
$activeInSql  = implode(',', array_fill(0, count($activeAffIds) ?: 1, '?'));
$activeInParams = $activeAffIds ?: [0];

// Filter dropdowns — offers used by managed affiliates
$offerList = $hasAffiliates ? Database::fetchAll(
    "SELECT DISTINCT o.id, o.name FROM offers o
     JOIN stats_daily sd ON sd.offer_id=o.id
     WHERE sd.affiliate_id IN ($inSql)
     ORDER BY o.name",
    $inParams
) : [];

// Managed affiliates list
$affList = $hasAffiliates ? Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE af.id IN ($inSql) ORDER BY name",
    $inParams
) : [];

// Countries from managed affiliates' clicks
$countryList = $hasAffiliates ? Database::fetchAll(
    "SELECT DISTINCT country FROM clicks WHERE affiliate_id IN ($inSql) AND country != '' ORDER BY country",
    $inParams
) : [];

$slId     = (int)(Helpers::get('sl_id') ?: 0);
$isExport = Helpers::get('export') === 'csv';

// SmartLinks with traffic across managed affiliates
$mgrSlList = [];
if ($hasAffiliates) {
    try {
        $mgrSlList = Database::fetchAll(
            "SELECT DISTINCT sl.id, sl.name FROM smartlinks sl
             JOIN clicks c ON c.smartlink_id = sl.id
             WHERE c.affiliate_id IN ($inSql) ORDER BY sl.name",
            $inParams
        );
    } catch (\Throwable $_e) {}
}

// ─── build stat_daily WHERE ───────────────────────────────────────────────
function mgrSdWhere(array $activeAffIds, string $activeInSql, int $offerId, string $from, string $to): array {
    $where  = ["sd.stat_date BETWEEN ? AND ?", "sd.affiliate_id IN ($activeInSql)"];
    $params = array_merge([$from, $to], $activeAffIds);
    if ($offerId > 0) { $where[] = 'sd.offer_id=?'; $params[] = $offerId; }
    return [$where, $params];
}

// ─── build click WHERE ────────────────────────────────────────────────────
function mgrClkWhere(array $activeAffIds, string $activeInSql, int $offerId, string $country, string $sub1, string $dateFrom, string $dateTo): array {
    $where  = ["c.clicked_at BETWEEN ? AND ?", "c.affiliate_id IN ($activeInSql)", "(c.source IS NULL OR c.source != 'traffic_back')"];
    $params = array_merge([$dateFrom, $dateTo], $activeAffIds);
    if ($offerId > 0)   { $where[] = 'c.offer_id=?';    $params[] = $offerId; }
    if ($country !== '') { $where[] = 'c.country=?';     $params[] = strtoupper($country); }
    if ($sub1 !== '')   { $where[] = 'c.sub1 LIKE ?';   $params[] = '%'.$sub1.'%'; }
    return [$where, $params];
}

// ─── build conversion WHERE ───────────────────────────────────────────────
function mgrCvWhere(array $activeAffIds, string $activeInSql, int $offerId, string $country, string $sub1, string $dateFrom, string $dateTo): array {
    $where  = [
        "cv.converted_at BETWEEN ? AND ?",
        "cv.affiliate_id IN ($activeInSql)",
        "cv.is_hidden = 0",
        "(cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')",
        "NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = cv.click_id AND _ck_tb.source = 'traffic_back')",
        "NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)"
    ];
    $params = array_merge([$dateFrom, $dateTo], $activeAffIds);
    if ($offerId > 0)   { $where[] = 'cv.offer_id=?';   $params[] = $offerId; }
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

// ─── Performance tabs: day / offer / country / sub / affiliate ─────────────
$rows = $totals = null;
$perfTabs = ['day','offer','country','sub','affiliate'];

if (in_array($tab, $perfTabs) && $hasAffiliates) {

    if (in_array($tab, ['day','offer','affiliate'])) {
        [$sdWhere, $sdParams] = mgrSdWhere($activeAffIds, $activeInSql, $offerId, $from, $to);
        $whereStr = implode(' AND ', $sdWhere);

        $selectMap = [
            'day'       => 'sd.stat_date as label, NULL as row_id',
            'offer'     => 'o.name as label, o.id as row_id',
            'affiliate' => "CONCAT(u.first_name,' ',u.last_name) as label, af.id as row_id",
        ];
        $joinMap = [
            'day'       => '',
            'offer'     => 'JOIN offers o ON o.id=sd.offer_id',
            'affiliate' => 'JOIN affiliates af ON af.id=sd.affiliate_id JOIN users u ON u.id=af.user_id',
        ];
        $groupMap = [
            'day'       => 'sd.stat_date',
            'offer'     => 'sd.offer_id',
            'affiliate' => 'sd.affiliate_id',
        ];

        $rows = Database::fetchAll(
            "SELECT {$selectMap[$tab]},
                    SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                    SUM(sd.conversions) as conversions, SUM(sd.approved) as approved,
                    SUM(sd.rejected) as rejected, SUM(sd.payout) as payout,
                    SUM(sd.fraud_clicks) as fraud_clicks
             FROM stats_daily sd {$joinMap[$tab]}
             WHERE $whereStr
             GROUP BY {$groupMap[$tab]} ORDER BY {$groupMap[$tab]} DESC",
            $sdParams
        );

    } else {
        // country or sub — from clicks
        $grpCol = $tab === 'country' ? 'c.country' : 'c.sub1';
        [$clkWhere, $clkParams] = mgrClkWhere($activeAffIds, $activeInSql, $offerId, $country, $sub1, $dateFrom, $dateTo);
        // remove country/sub filter if we're grouping by it
        if ($tab === 'country') {
            $clkWhere = array_filter($clkWhere, fn($w) => !str_contains($w, 'c.country'));
            $clkParams = array_values($clkParams);
        }
        if ($tab === 'sub') {
            $clkWhere = array_filter($clkWhere, fn($w) => !str_contains($w, 'c.sub1'));
            $clkParams = array_values($clkParams);
        }
        $whereStr = implode(' AND ', $clkWhere);

        $rows = Database::fetchAll(
            "SELECT $grpCol as label,
                    COUNT(*) as clicks, SUM(c.is_unique) as uclicks,
                    SUM(c.is_fraud) as fraud_clicks,
                    COUNT(cv.id) as conversions,
                    SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN cv.status='rejected' THEN 1 ELSE 0 END) as rejected,
                    COALESCE(SUM(CASE WHEN cv.status='approved' AND cv.is_hidden=0 THEN cv.payout ELSE 0 END), 0) as payout
             FROM clicks c
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $whereStr
             GROUP BY $grpCol ORDER BY clicks DESC LIMIT $limit",
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
        'payout'      => array_sum(array_column($rows,'payout'))
    ];

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mgr-report-'.$tab.'-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f, [ucfirst($tab),'Clicks','Unique','Conversions','Approved','Rejected','Fraud','CR%','EPC','Payout']);
        foreach ($rows as $r) {
            $cr  = $r['clicks'] > 0 ? round($r['conversions']/$r['clicks']*100,2) : 0;
            $epc = $r['clicks'] > 0 ? round($r['payout']/$r['clicks'],4) : 0;
            fputcsv($f, [$r['label'],$r['clicks'],$r['uclicks'],$r['conversions'],$r['approved'],$r['rejected'],$r['fraud_clicks'],$cr,$epc,number_format($r['payout'],2)]);
        }
        fclose($f); exit;
    }

    // ── IPQS fraud/rejected conv stats per row label ─────────────────────
    $perfIpqsStats = [];
    if (!empty($rows)) {
        if (in_array($tab, ['day','offer','affiliate'])) {
            $groupColMap = [
                'day'       => 'DATE(cv.converted_at)',
                'offer'     => 'o.name',
                'affiliate' => "CONCAT(u.first_name,' ',u.last_name)",
            ];
            $joinColMap = [
                'day'       => '',
                'offer'     => 'JOIN offers o ON o.id = cv.offer_id',
                'affiliate' => 'JOIN affiliates af ON af.id = cv.affiliate_id JOIN users u ON u.id = af.user_id',
            ];
            $groupCol  = $groupColMap[$tab];
            $joinExtra = $joinColMap[$tab];
            $affInSql  = implode(',', array_fill(0, count($activeAffIds), '?'));

            $ipqsRaw = Database::fetchAll(
                "SELECT {$groupCol} AS label,
                        COUNT(*)                                                                           AS total_checked,
                        ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END), 1)       AS avg_score,
                        SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)                             AS high_risk,
                        SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END)     AS medium_risk,
                        SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk,
                        SUM(CASE WHEN cv.status = 'rejected' THEN 1 ELSE 0 END)                           AS rejected_conv,
                        SUM(CASE WHEN COALESCE(cv.fraud_score, 0) >= 60 THEN 1 ELSE 0 END)               AS fraud_conv
                 FROM conversions cv
                 {$joinExtra}
                 WHERE cv.affiliate_id IN ($affInSql)
                   AND cv.converted_at BETWEEN ? AND ?
                   AND cv.fraud_score IS NOT NULL
                   AND cv.is_hidden = 0
                 GROUP BY {$groupCol}",
                array_merge($activeAffIds, [$dateFrom, $dateTo])
            ) ?: [];
            foreach ($ipqsRaw as $r2) {
                $perfIpqsStats[$r2['label']] = $r2;
            }
        }
    }

} elseif (in_array($tab, $perfTabs) && !$hasAffiliates) {
    $rows   = [];
    $totals = ['clicks'=>0,'uclicks'=>0,'conversions'=>0,'approved'=>0,'rejected'=>0,'fraud_clicks'=>0,'payout'=>0];
    $perfIpqsStats = [];
}

// ─── Click tab ────────────────────────────────────────────────────────────
$clicks = null;
if ($tab === 'click') {
    if ($hasAffiliates) {
        [$clkWhere, $clkParams] = mgrClkWhere($activeAffIds, $activeInSql, $offerId, $country, $sub1, $dateFrom, $dateTo);
        $whereStr = implode(' AND ', $clkWhere);

        $clicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.source, c.referer,
                    c.is_fraud, c.fraud_score, c.fraud_reasons,
                    c.os, c.browser, c.user_agent, c.device_type,
                    c.ip_address, c.country, c.city, c.region,
                    c.clicked_at, c.status,
                    IF(COALESCE(c.smartlink_id, cv.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(c.smartlink_id, cv.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, c.smartlink_id, cv.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink'), o.name) as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    cv.status       as conv_status,
                    cv.payout       as conv_payout,
                    cv.converted_at as conv_time
             FROM clicks c
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN smartlink_offers so ON so.offer_id = c.offer_id
             LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
             LEFT JOIN offers o ON o.id = c.offer_id
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $whereStr
             GROUP BY c.click_id
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $clkParams
        );
    } else {
        $clicks = [];
    }

    if ($isExport && $clicks !== null) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mgr-clicks-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['OFFER','AFFILIATE','AFF CODE','CLICK ID','SUB1','SUB2','SUB3','SOURCE','FRAUD','OS','BROWSER','DEVICE','IP','COUNTRY','CITY','CONV STATUS','PAYOUT','CLICK TIME','CONV TIME']);
        foreach ($clicks as $r) {
            $convPayout = ($r['conv_status'] ?? '') === 'approved' ? number_format((float)$r['conv_payout'], 4) : '0.0000';
            fputcsv($f,[$r['offer_name'],$r['aff_name'],$r['affiliate_code'],$r['click_id'],$r['sub1'],$r['sub2'],$r['sub3'],$r['source'],$r['is_fraud']?'Fraud':'Clean',$r['os'],$r['browser'],$r['device_type'],$r['ip_address'],$r['country'],$r['city'],$r['conv_status']?:'no conversion',$convPayout,$r['clicked_at'],$r['conv_time']??'']);
        }
        fclose($f); exit;
    }
}

// ─── Conversion tab ───────────────────────────────────────────────────────
$convRows = null;
if ($tab === 'conversion') {
    if ($hasAffiliates) {
        [$cvWhere, $cvParams] = mgrCvWhere($activeAffIds, $activeInSql, $offerId, $country, $sub1, $dateFrom, $dateTo);
        $whereStr = implode(' AND ', $cvWhere);

        $convRows = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout,
                    cv.is_fraud, cv.transaction_id, cv.goal_name, cv.converted_at,
                    IF(COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, cv.smartlink_id, ck.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink'), o.name) as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.referer,
                    ck.os, ck.browser, ck.user_agent, ck.device_type,
                    ck.ip_address, ck.country, ck.city, ck.region
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id=cv.click_id
             LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
             LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
             LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
             LEFT JOIN offers o ON o.id=cv.offer_id
             JOIN affiliates af ON af.id=cv.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE $whereStr AND cv.is_hidden=0
             GROUP BY cv.conversion_id
             ORDER BY cv.converted_at DESC LIMIT $limit",
            $cvParams
        );
    } else {
        $convRows = [];
    }

    if ($isExport && $convRows !== null) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mgr-conversions-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['OFFER','AFFILIATE','AFF CODE','CLICK ID','AFF CLICK ID','AFF SUB 1','AFF SUB 2','AFF SUB 3','SOURCE','OS','BROWSER','DEVICE TYPE','IP','COUNTRY','CITY','REGION','PAYOUT','STATUS','GOAL','TXN ID','CONVERT TIME']);
        foreach ($convRows as $r) {
            fputcsv($f,[$r['offer_name'],$r['aff_name'],$r['affiliate_code'],$r['click_id'],$r['sub1'],$r['sub2'],$r['sub3'],$r['sub4'],$r['referer'],$r['os'],$r['browser'],$r['device_type'],$r['ip_address'],$r['country'],$r['city'],$r['region'],number_format($r['payout'],4),$r['status'],$r['goal_name'],$r['transaction_id'],$r['converted_at']]);
        }
        fclose($f); exit;
    }
}

// ─── SmartLink Report tab (manager view across managed affiliates) ────────
$slClicks = $slConversions = $slAffSum = null;
if ($tab === 'sl_report' && $hasAffiliates) {
    // ── Per-affiliate SmartLink summary ──
    $slSumWhere  = ["c.affiliate_id IN ($activeInSql)", 'c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $slSumParams = array_merge($activeAffIds, [$dateFrom, $dateTo]);
    if ($slId > 0) { $slSumWhere[] = 'c.smartlink_id=?'; $slSumParams[] = $slId; }
    $slSumWhere = implode(' AND ', $slSumWhere);

    try {
        $slAffSum = Database::fetchAll(
            "SELECT CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    COALESCE(sl.name,'— Unknown —') as smartlink_name,
                    COUNT(c.click_id)    as clicks,
                    SUM(c.is_unique)     as uclicks,
                    SUM(c.is_fraud)      as fraud_clicks,
                    COUNT(cv.id)         as conversions,
                    SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) as approved,
                    COALESCE(SUM(CASE WHEN cv.status='approved' THEN cv.payout   ELSE 0 END),0) as payout
             FROM clicks c
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $slSumWhere
             GROUP BY af.id, c.smartlink_id
             ORDER BY clicks DESC LIMIT $limit",
            $slSumParams
        );
    } catch (\Throwable $_e) { $slAffSum = []; }

    // ── Click log ──
    $slClkWhere  = ["c.affiliate_id IN ($activeInSql)", 'c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $slClkParams = array_merge($activeAffIds, [$dateFrom, $dateTo]);
    if ($slId    > 0)    { $slClkWhere[] = 'c.smartlink_id=?'; $slClkParams[] = $slId; }
    if ($country !== '') { $slClkWhere[] = 'c.country=?';       $slClkParams[] = strtoupper($country); }
    $slClkWhere = implode(' AND ', $slClkWhere);

    try {
        $slClicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.source,
                    c.ip_address, c.country, c.city, c.os, c.browser, c.device_type,
                    c.clicked_at, c.is_fraud,
                    COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, c.smartlink_id, cv.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink') as smartlink_name,
                    COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, c.smartlink_id, cv.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink') as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    COALESCE(cv.status,'') as conv_status,
                    (cv.conversion_id IS NOT NULL) as has_conversion
             FROM clicks c
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN smartlink_offers so ON so.offer_id = c.offer_id
             LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
             LEFT JOIN offers o ON o.id = c.offer_id
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $slClkWhere
             GROUP BY c.click_id
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $slClkParams
        );
    } catch (\Throwable $_e) { $slClicks = []; }

    // ── Conversion log ──
    $slCvWhere  = ["cv.affiliate_id IN ($activeInSql)", '(cv.smartlink_id IS NOT NULL OR ck.smartlink_id IS NOT NULL OR so.smartlink_id IS NOT NULL)', 'cv.converted_at BETWEEN ? AND ?', 'cv.is_hidden=0'];
    $slCvParams = array_merge($activeAffIds, [$dateFrom, $dateTo]);
    if ($slId    > 0)    { $slCvWhere[] = 'COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id)=?'; $slCvParams[] = $slId; }
    if ($country !== '') { $slCvWhere[] = 'ck.country=?';       $slCvParams[] = strtoupper($country); }
    $slCvWhere = implode(' AND ', $slCvWhere);

    try {
        $slConversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                    cv.goal_name, cv.transaction_id,
                    COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, cv.smartlink_id, ck.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink') as smartlink_name,
                    COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id, cv.smartlink_id, ck.smartlink_id, so.smartlink_id), 4, '0'), '] ', COALESCE(sl.name, sl2.name, 'SmartLink')), 'SmartLink') as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    ck.sub1, ck.country, ck.os, ck.browser, ck.device_type
             FROM conversions cv
             JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
             LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
             LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE $slCvWhere
             GROUP BY cv.conversion_id
             ORDER BY cv.converted_at DESC LIMIT $limit",
            $slCvParams
        );
    } catch (\Throwable $_e) { $slConversions = []; }

    if ($isExport) {
        $exportType = Helpers::get('export_type') ?: 'summary';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mgr-sl-'.Helpers::e($exportType).'-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");
        if ($exportType === 'clicks') {
            fputcsv($f, ['SMARTLINK','OFFER','AFFILIATE','AFF CODE','CLICK ID','SUB1','SUB2','SOURCE','FRAUD','OS','BROWSER','DEVICE','IP','COUNTRY','CITY','CONVERTED','CONV STATUS','CLICK TIME']);
            foreach ($slClicks as $r) {
                fputcsv($f, [$r['smartlink_name'],$r['offer_name'],$r['aff_name'],$r['affiliate_code'],$r['click_id'],$r['sub1'],$r['sub2'],$r['source'],$r['is_fraud']?'Fraud':'Clean',$r['os'],$r['browser'],$r['device_type'],$r['ip_address'],$r['country'],$r['city'],$r['has_conversion']?'Yes':'No',$r['conv_status']?:'—',$r['clicked_at']]);
            }
        } elseif ($exportType === 'conversions') {
            fputcsv($f, ['SMARTLINK','OFFER','AFFILIATE','AFF CODE','CLICK ID','CONV ID','SUB1','STATUS','PAYOUT','GOAL','TXN ID','COUNTRY','OS','DEVICE','CONVERTED AT']);
            foreach ($slConversions as $r) {
                fputcsv($f, [$r['smartlink_name'],$r['offer_name'],$r['aff_name'],$r['affiliate_code'],$r['click_id'],$r['conversion_id'],$r['sub1'],$r['status'],number_format((float)$r['payout'],4),$r['goal_name'],$r['transaction_id'],$r['country'],$r['os'],$r['device_type'],$r['converted_at']]);
            }
        } else {
            fputcsv($f, ['AFFILIATE','AFF CODE','SMARTLINK','CLICKS','UNIQUE','FRAUD','CONVERSIONS','APPROVED','PAYOUT','CR%','EPC']);
            foreach ($slAffSum as $r) {
                $cr = $r['clicks']>0 ? round($r['conversions']/$r['clicks']*100,2) : 0;
                $epc= $r['clicks']>0 ? round($r['payout']/$r['clicks'],4)           : 0;
                fputcsv($f, [$r['aff_name'],$r['affiliate_code'],$r['smartlink_name'],$r['clicks'],$r['uclicks'],$r['fraud_clicks'],$r['conversions'],$r['approved'],number_format((float)$r['payout'],2),$cr,$epc]);
            }
        }
        fclose($f); exit;
    }
} elseif ($tab === 'sl_report') {
    $slAffSum = $slClicks = $slConversions = [];
}

require BASE_PATH . '/views/affiliate_manager/reports.php';
