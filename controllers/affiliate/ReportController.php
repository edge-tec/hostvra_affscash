<?php
Auth::check('affiliate');
$pageTitle = 'My Reports';
$affId = Auth::affiliateId();

// Schema migrations
try { Database::query("ALTER TABLE conversions ADD COLUMN IF NOT EXISTS is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE clicks ADD COLUMN IF NOT EXISTS source VARCHAR(255) DEFAULT '' AFTER sub5"); } catch(Exception $e) {}

$tab         = Helpers::get('tab') ?: 'day';
$from        = Helpers::get('from') ?: date('Y-m-01');
$to          = Helpers::get('to')   ?: date('Y-m-d');
$offerId     = (int)(Helpers::get('offer_id') ?: 0);
$country     = trim(Helpers::get('country') ?: '');
$sub1        = trim(Helpers::get('sub1') ?: '');
$clickId     = trim(Helpers::get('click_id') ?: '');
$limit       = min((int)(Helpers::get('limit') ?: 500), 5000);
$clickFilter = Helpers::get('click_filter') ?: 'all';
if (!in_array($clickFilter, ['all','converted','approved'])) $clickFilter = 'all';

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

// Affiliate's approved offers for filter dropdown
$myOffers = Database::fetchAll(
    "SELECT o.id, o.name FROM offers o
     JOIN affiliate_offers ao ON ao.offer_id=o.id
     WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
     ORDER BY o.name",
    [$affId]
);

// Countries from this affiliate's clicks
$countryList = Database::fetchAll(
    "SELECT DISTINCT country FROM clicks WHERE affiliate_id=? AND country != '' ORDER BY country",
    [$affId]
);

$slId     = (int)(Helpers::get('sl_id') ?: 0);
$isExport = Helpers::get('export') === 'csv';

// SmartLinks this affiliate has traffic through
$mySlList = [];
try {
    $mySlList = Database::fetchAll(
        "SELECT DISTINCT sl.id, sl.name FROM smartlinks sl
         JOIN clicks c ON c.smartlink_id = sl.id
         WHERE c.affiliate_id = ? ORDER BY sl.name",
        [$affId]
    );
} catch (\Throwable $_e) {}

// ─── Performance tabs: day / offer / country / sub ────────────────────────
// RULE: Payout only counts when the conversion status = 'approved'.
// All tabs use a subquery against the conversions table for payout —
// never clicks.payout — so the number exactly matches what was earned.

$rows = $totals = null;
$perfTabs = ['day','offer','country','sub'];

if (in_array($tab, $perfTabs)) {

    if ($tab === 'day' || $tab === 'offer') {
        // From stats_daily + live approved payout from conversions
        $selectMap = [
            'day'   => 'sd.stat_date as label',
            'offer' => 'IF(so.smartlink_id IS NOT NULL, COALESCE(sl.name, "SmartLink"), o.name) as label',
        ];
        $groupMap = [
            'day'   => 'sd.stat_date',
            'offer' => 'sd.offer_id, IF(so.smartlink_id IS NOT NULL, COALESCE(sl.name, "SmartLink"), o.name)',
        ];
        $orderMap = [
            'day'   => 'sd.stat_date DESC, payout DESC',
            'offer' => 'payout DESC, clicks DESC',
        ];
        $joinMap = [
            'day'   => '',
            'offer' => 'JOIN offers o ON o.id=sd.offer_id LEFT JOIN smartlink_offers so ON so.offer_id=o.id LEFT JOIN smartlinks sl ON sl.id=so.smartlink_id',
        ];

        $sdWhere  = ['sd.affiliate_id=?', 'sd.stat_date BETWEEN ? AND ?'];
        $sdParams = [$affId, $from, $to];
        if ($offerId > 0) { $sdWhere[] = 'sd.offer_id=?'; $sdParams[] = $offerId; }
        $whereStr = implode(' AND ', $sdWhere);

        // Subquery: approved payout only, grouped to match the outer GROUP BY
        // (Plus a second subquery for IPQS fraud-conversion counts — additive
        //  visibility layer; never replaces any existing column.)
        if ($tab === 'day') {
            $cvJoin = "LEFT JOIN (
                SELECT DATE(converted_at) as cv_date, SUM(payout) as approved_payout
                FROM conversions
                WHERE affiliate_id=? AND status='approved' AND is_hidden=0
                GROUP BY DATE(converted_at)
            ) cv_pay ON cv_pay.cv_date = sd.stat_date
            LEFT JOIN (
                SELECT DATE(converted_at) as cv_date,
                       SUM(CASE WHEN COALESCE(fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_count
                FROM conversions
                WHERE affiliate_id=? AND is_hidden=0
                GROUP BY DATE(converted_at)
            ) cv_fraud ON cv_fraud.cv_date = sd.stat_date";
        } else {
            $cvJoin = "LEFT JOIN (
                SELECT offer_id, SUM(payout) as approved_payout
                FROM conversions
                WHERE affiliate_id=? AND status='approved' AND is_hidden=0
                GROUP BY offer_id
            ) cv_pay ON cv_pay.offer_id = sd.offer_id
            LEFT JOIN (
                SELECT offer_id,
                       SUM(CASE WHEN COALESCE(fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_count
                FROM conversions
                WHERE affiliate_id=? AND is_hidden=0
                GROUP BY offer_id
            ) cv_fraud ON cv_fraud.offer_id = sd.offer_id";
        }

        // affiliate_id for the TWO subqueries comes first (payout, then fraud).
        $sdParamsFull = array_merge([$affId, $affId], $sdParams);

        $rows = Database::fetchAll(
            "SELECT {$selectMap[$tab]},
                    SUM(sd.clicks)        as clicks,
                    SUM(sd.unique_clicks) as uclicks,
                    SUM(sd.conversions)   as conv,
                    SUM(sd.approved)      as approved,
                    SUM(sd.rejected)      as rejected,
                    COALESCE(MAX(cv_pay.approved_payout), 0) as payout,
                    COALESCE(MAX(cv_fraud.fraud_count), 0)   as fraud
             FROM stats_daily sd
             {$joinMap[$tab]}
             $cvJoin
             WHERE $whereStr
             GROUP BY {$groupMap[$tab]}
             ORDER BY {$orderMap[$tab]}",
            $sdParamsFull
        );

    } elseif ($tab === 'country' || $tab === 'sub') {
        // Group by country or sub1.
        // Click counts come from clicks table;
        // approved payout comes from conversions joined via click_id.
        $grpCol = $tab === 'country' ? 'c.country' : 'c.sub1';

        $clkWhere  = ['c.affiliate_id=?', 'c.clicked_at BETWEEN ? AND ?', 'c.status = \'valid\''];
        $clkParams = [$affId, $dateFrom, $dateTo];
        if ($offerId > 0) { $clkWhere[] = 'c.offer_id=?'; $clkParams[] = $offerId; }
        if ($country !== '' && $tab !== 'country') { $clkWhere[] = 'c.country=?'; $clkParams[] = strtoupper($country); }
        if ($sub1    !== '' && $tab !== 'sub')     { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
        $whereStr = implode(' AND ', $clkWhere);

        $rows = Database::fetchAll(
            "SELECT $grpCol as label,
                    COUNT(*)            as clicks,
                    SUM(c.is_unique)    as uclicks,
                    COUNT(cv.id)        as conv,
                    SUM(CASE WHEN cv.status='approved'  THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN cv.status='rejected'  THEN 1 ELSE 0 END) as rejected,
                    /* IPQS fraud overlay — counts conversions whose fraud_score >= 60.
                       Additive: never affects clicks / conv / approved / rejected. */
                    SUM(CASE WHEN cv.id IS NOT NULL AND COALESCE(cv.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud,
                    COALESCE(SUM(CASE WHEN cv.status='approved' AND cv.is_hidden=0 THEN cv.payout ELSE 0 END), 0) as payout
             FROM clicks c
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $whereStr
             GROUP BY $grpCol
             ORDER BY clicks DESC
             LIMIT $limit",
            $clkParams
        );
    }

    $totals = [
        'clicks'   => array_sum(array_column($rows, 'clicks')),
        'uclicks'  => array_sum(array_column($rows, 'uclicks')),
        'conv'     => array_sum(array_column($rows, 'conv')),
        'approved' => array_sum(array_column($rows, 'approved')),
        'rejected' => array_sum(array_column($rows, 'rejected')),
        // IPQS fraud-conversion total — visibility overlay only.
        'fraud'    => array_sum(array_column($rows, 'fraud')),
        'payout'   => array_sum(array_column($rows, 'payout')),  // approved payout only
    ];

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my-report-'.$tab.'-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f, [ucfirst($tab),'Clicks','Unique','Conversions','Approved','Rejected','CR%','EPC','Approved Payout']);
        foreach ($rows as $r) {
            $cr  = $r['clicks'] > 0 ? round($r['conv']  / $r['clicks'] * 100, 2) : 0;
            $epc = $r['clicks'] > 0 ? round($r['payout'] / $r['clicks'], 4)       : 0;
            fputcsv($f, [
                $r['label'], $r['clicks'], $r['uclicks'],
                $r['conv'],  $r['approved'], $r['rejected'],
                $cr, $epc, number_format($r['payout'], 2),
            ]);
        }
        fclose($f); exit;
    }
}

// ─── Click Log tab ────────────────────────────────────────────────────────
// Shows each click with its conversion status + approved payout.
// The "Total Payout" stat card sums only approved conversion payouts.

$clicks = null;
if ($tab === 'click') {
    $clkWhere  = ['c.affiliate_id=?', 'c.clicked_at BETWEEN ? AND ?'];
    $clkParams = [$affId, $dateFrom, $dateTo];
    if ($offerId > 0)    { $clkWhere[] = 'c.offer_id=?';  $clkParams[] = $offerId; }
    if ($country !== '') { $clkWhere[] = 'c.country=?';   $clkParams[] = strtoupper($country); }
    if ($sub1 !== '')    { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
    if ($clickId !== '') { $clkWhere[] = 'c.click_id=?';  $clkParams[] = $clickId; }
    if ($clickFilter === 'converted') {
        $clkWhere[] = 'EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.is_hidden = 0)';
    } elseif ($clickFilter === 'approved') {
        $clkWhere[] = "EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.status = 'approved' AND _cv.is_hidden = 0)";
    }
    $whereStr = implode(' AND ', $clkWhere);

    $clicks = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.source,
                c.referer, c.os, c.browser, c.user_agent, c.device_type,
                c.ip_address, c.country, c.city, c.region, c.clicked_at,
                COALESCE(cv.rejection_reason, '') AS rejection_reason,
                cv.rejected_at,
                IF(c.smartlink_id IS NOT NULL AND c.smartlink_id > 0, COALESCE(sl.name, 'SmartLink'), IF(so.smartlink_id IS NOT NULL, COALESCE(sl2.name, 'SmartLink'), o.name)) as offer_name,
                IF(c.smartlink_id IS NOT NULL OR so.smartlink_id IS NOT NULL, NULL, o.id) as offer_id,
                cv.status       as conv_status,
                cv.payout       as conv_payout,
                cv.converted_at as conv_time
         FROM clicks c
         LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
         LEFT JOIN smartlink_offers so ON so.offer_id = c.offer_id
         LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
         LEFT JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
         WHERE $whereStr
         GROUP BY c.click_id
         ORDER BY c.clicked_at DESC
         LIMIT $limit",
        $clkParams
    );

    // Total payout = sum of approved conversion payouts only
    $clickTotalPayout = 0;
    foreach ($clicks as $cl) {
        if (($cl['conv_status'] ?? '') === 'approved') {
            $clickTotalPayout += (float)$cl['conv_payout'];
        }
    }

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my-clicks-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f, [
            'OFFER','CLICK ID','SUB1','SUB2','SUB3','SOURCE',
            'OS','BROWSER','DEVICE','IP','COUNTRY','CITY','REGION',
            'CONV STATUS','PAYOUT','CLICK TIME','CONV TIME',
        ]);
        foreach ($clicks as $r) {
            fputcsv($f, [
                $r['offer_name'] ?: '— Custom URL —', $r['click_id'],
                $r['sub1'], $r['sub2'], $r['sub3'], $r['source'],
                $r['os'], $r['browser'], $r['device_type'],
                $r['ip_address'], $r['country'], $r['city'], $r['region'],
                $r['conv_status'] ?: 'no conversion',
                $r['conv_status'] === 'approved' ? number_format((float)$r['conv_payout'], 4) : '0.0000',
                $r['clicked_at'],
                $r['conv_time'] ?: '',
            ]);
        }
        fclose($f); exit;
    }
}

// ─── Conversions tab ──────────────────────────────────────────────────────
$convRows = null;
if ($tab === 'conversion') {
    $cvWhere  = ['cv.affiliate_id=?', 'cv.converted_at BETWEEN ? AND ?', 'cv.is_hidden=0'];
    $cvParams = [$affId, $dateFrom, $dateTo];
    if ($offerId > 0)    { $cvWhere[] = 'cv.offer_id=?'; $cvParams[] = $offerId; }
    if ($country !== '') {
        $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.country=?)';
        $cvParams[] = strtoupper($country);
    }
    if ($sub1 !== '') {
        $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.sub1 LIKE ?)';
        $cvParams[] = '%'.$sub1.'%';
    }
    if ($clickId !== '') {
        $cvWhere[] = 'cv.click_id=?';
        $cvParams[] = $clickId;
    }
    $whereStr = implode(' AND ', $cvWhere);

    $convRows = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                cv.goal_name, cv.transaction_id,
                /* Surface rejection metadata so the affiliate can see exactly
                   why the conversion was rejected and when. */
                 COALESCE(cv.rejection_reason, '') AS rejection_reason,
                 cv.rejected_at,
                 IF(COALESCE(cv.smartlink_id, ck.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id) > 0, COALESCE(sl.name, 'SmartLink'), IF(so.smartlink_id IS NOT NULL, COALESCE(sl2.name, 'SmartLink'), o.name)) as offer_name,
                 IF(COALESCE(cv.smartlink_id, ck.smartlink_id) IS NOT NULL OR so.smartlink_id IS NOT NULL, NULL, o.id) as offer_id,
                 ck.sub1, ck.sub2, ck.sub3, ck.source,
                 ck.os, ck.browser, ck.user_agent, ck.device_type,
                 ck.ip_address, ck.country, ck.city, ck.region, ck.referer
          FROM conversions cv
          LEFT JOIN clicks ck ON ck.click_id = cv.click_id
          LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
          LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
          LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
          LEFT JOIN offers o ON o.id = cv.offer_id
         WHERE $whereStr
         GROUP BY cv.conversion_id
         ORDER BY cv.converted_at DESC
         LIMIT $limit",
        $cvParams
    );

    if ($isExport) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my-conversions-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f, [
            'OFFER','CLICK ID','SUB1','SUB2','SUB3','SOURCE',
            'OS','BROWSER','DEVICE','IP','COUNTRY','CITY','REGION',
            'PAYOUT','STATUS','REJECTION REASON','REJECTED AT','GOAL','TXN ID','CONVERT TIME',
        ]);
        foreach ($convRows as $r) {
            fputcsv($f, [
                $r['offer_name'], $r['click_id'],
                $r['sub1'], $r['sub2'], $r['sub3'], $r['source'],
                $r['os'], $r['browser'], $r['device_type'],
                $r['ip_address'], $r['country'], $r['city'], $r['region'],
                number_format((float)$r['payout'], 4),
                $r['status'],
                $r['rejection_reason'] ?? '', $r['rejected_at'] ?? '',
                $r['goal_name'], $r['transaction_id'],
                $r['converted_at'],
            ]);
        }
        fclose($f); exit;
    }
}

// ─── SmartLink Report tab (affiliate's own smartlink traffic) ─────────────
$slClicks = $slConversions = null;
if ($tab === 'sl_report') {
    // ── Clicks ──
    $slClkWhere  = ['c.affiliate_id=?', 'c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $slClkParams = [$affId, $dateFrom, $dateTo];
    if ($slId    > 0)    { $slClkWhere[] = 'c.smartlink_id=?'; $slClkParams[] = $slId; }
    if ($country !== '') { $slClkWhere[] = 'c.country=?';       $slClkParams[] = strtoupper($country); }
    $slClkWhere = implode(' AND ', $slClkWhere);

    try {
        $slClicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.source,
                    c.ip_address, c.country, c.city, c.os, c.browser, c.device_type,
                    c.clicked_at, c.is_fraud,
                    COALESCE(sl.name,'— Unknown —') as smartlink_name,
                    COALESCE(sl.name, 'SmartLink') as offer_name,
                    COALESCE(cv.status,'') as conv_status,
                    COALESCE(cv.payout, 0) as conv_payout,
                    (cv.conversion_id IS NOT NULL) as has_conversion
             FROM clicks c
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN offers o ON o.id = c.offer_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $slClkWhere
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $slClkParams
        );
    } catch (\Throwable $_e) { $slClicks = []; }

    // ── Conversions ──
    $slCvWhere  = ['cv.affiliate_id=?', 'ck.smartlink_id IS NOT NULL', 'cv.converted_at BETWEEN ? AND ?', 'cv.is_hidden=0'];
    $slCvParams = [$affId, $dateFrom, $dateTo];
    if ($slId    > 0)    { $slCvWhere[] = 'ck.smartlink_id=?'; $slCvParams[] = $slId; }
    if ($country !== '') { $slCvWhere[] = 'ck.country=?';       $slCvParams[] = strtoupper($country); }
    $slCvWhere = implode(' AND ', $slCvWhere);

    try {
        $slConversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                    cv.goal_name, cv.transaction_id,
                    COALESCE(sl.name,'— Unknown —') as smartlink_name,
                    COALESCE(sl.name,'SmartLink') as offer_name,
                    ck.sub1, ck.sub2, ck.country, ck.os, ck.browser, ck.device_type
             FROM conversions cv
             JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN smartlinks sl ON sl.id = ck.smartlink_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             WHERE $slCvWhere
             ORDER BY cv.converted_at DESC LIMIT $limit",
            $slCvParams
        );
    } catch (\Throwable $_e) { $slConversions = []; }

    if ($isExport) {
        $exportType = Helpers::get('export_type') ?: 'clicks';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sl-'.Helpers::e($exportType).'-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");
        if ($exportType === 'conversions') {
            fputcsv($f, ['SMARTLINK','OFFER','CLICK ID','CONV ID','SUB1','SUB2','STATUS','PAYOUT','GOAL','TXN ID','COUNTRY','OS','BROWSER','DEVICE','CONVERTED AT']);
            foreach ($slConversions as $r) {
                fputcsv($f, [$r['smartlink_name'],$r['offer_name'],$r['click_id'],$r['conversion_id'],$r['sub1'],$r['sub2'],$r['status'],number_format((float)$r['payout'],4),$r['goal_name'],$r['transaction_id'],$r['country'],$r['os'],$r['browser'],$r['device_type'],$r['converted_at']]);
            }
        } else {
            fputcsv($f, ['SMARTLINK','OFFER','CLICK ID','SUB1','SUB2','SOURCE','FRAUD','OS','BROWSER','DEVICE','IP','COUNTRY','CITY','CONVERTED','CONV STATUS','PAYOUT','CLICK TIME']);
            foreach ($slClicks as $r) {
                fputcsv($f, [$r['smartlink_name'],$r['offer_name'],$r['click_id'],$r['sub1'],$r['sub2'],$r['source'],$r['is_fraud']?'Fraud':'Clean',$r['os'],$r['browser'],$r['device_type'],$r['ip_address'],$r['country'],$r['city'],$r['has_conversion']?'Yes':'No',$r['conv_status']?:'—',number_format((float)$r['conv_payout'],4),$r['clicked_at']]);
            }
        }
        fclose($f); exit;
    }
}

require BASE_PATH . '/views/affiliate/reports/index.php';
