<?php
header('Content-Type: application/json');

Auth::check('affiliate');

$affId = Auth::affiliateId();
$action = $_GET['action'] ?? 'list';

if ($action === 'filters') {
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

    echo json_encode([
        'success' => true,
        'offers' => $myOffers,
        'countries' => array_column($countryList, 'country')
    ]);
    exit;
}

$tab         = $_GET['tab'] ?? 'day';
$from        = $_GET['from'] ?? date('Y-m-01');
$to          = $_GET['to']   ?? date('Y-m-d');
$offerId     = (int)($_GET['offer_id'] ?? 0);
$country     = trim($_GET['country'] ?? '');
$sub1        = trim($_GET['sub1'] ?? '');
$limit       = 1000;

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = [];
$totals = null;

$perfTabs = ['day','offer','country','sub'];

if (in_array($tab, $perfTabs)) {

    if ($tab === 'day' || $tab === 'offer') {
        $selectMap = [
            'day'   => 'sd.stat_date as label',
            'offer' => 'o.name as label',
        ];
        $groupMap = [
            'day'   => 'sd.stat_date',
            'offer' => 'sd.offer_id, o.name',
        ];
        $orderMap = [
            'day'   => 'sd.stat_date DESC, payout DESC',
            'offer' => 'payout DESC, clicks DESC',
        ];
        $joinMap = [
            'day'   => '',
            'offer' => 'JOIN offers o ON o.id=sd.offer_id',
        ];

        $sdWhere  = ['sd.affiliate_id=?', 'sd.stat_date BETWEEN ? AND ?'];
        $sdParams = [$affId, $from, $to];
        if ($offerId > 0) { $sdWhere[] = 'sd.offer_id=?'; $sdParams[] = $offerId; }
        $whereStr = implode(' AND ', $sdWhere);

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
        // Clean empty labels
        foreach ($rows as &$r) {
            if (empty($r['label'])) $r['label'] = 'Unknown';
        }
    }

    $totals = [
        'clicks'   => array_sum(array_column($rows, 'clicks')),
        'uclicks'  => array_sum(array_column($rows, 'uclicks')),
        'conv'     => array_sum(array_column($rows, 'conv')),
        'approved' => array_sum(array_column($rows, 'approved')),
        'rejected' => array_sum(array_column($rows, 'rejected')),
        'fraud'    => array_sum(array_column($rows, 'fraud')),
        'payout'   => array_sum(array_column($rows, 'payout')),
    ];

    echo json_encode([
        'success' => true,
        'tab' => $tab,
        'from' => $from,
        'to' => $to,
        'rows' => array_map(function($r) {
            $r['payout'] = round((float)$r['payout'], 2);
            return $r;
        }, $rows),
        'totals' => $totals
    ]);
    exit;

} elseif ($tab === 'click') {
    $clkWhere  = ['c.affiliate_id=?', 'c.clicked_at BETWEEN ? AND ?'];
    $clkParams = [$affId, $dateFrom, $dateTo];
    if ($offerId > 0)    { $clkWhere[] = 'c.offer_id=?';  $clkParams[] = $offerId; }
    if ($country !== '') { $clkWhere[] = 'c.country=?';   $clkParams[] = strtoupper($country); }
    if ($sub1 !== '')    { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
    
    $whereStr = implode(' AND ', $clkWhere);

    $clicks = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.source,
                c.os, c.browser, c.device_type, c.ip_address, c.country, c.city, c.clicked_at,
                o.name as offer_name,
                cv.status       as conv_status,
                cv.payout       as conv_payout,
                cv.converted_at as conv_time
         FROM clicks c
         LEFT JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
         WHERE $whereStr
         ORDER BY c.clicked_at DESC
         LIMIT $limit",
        $clkParams
    );

    echo json_encode([
        'success' => true,
        'tab' => $tab,
        'clicks' => $clicks
    ]);
    exit;

} elseif ($tab === 'conversion') {
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
    $whereStr = implode(' AND ', $cvWhere);

    $convRows = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                o.name as offer_name,
                ck.sub1, ck.os, ck.browser, ck.device_type, ck.ip_address, ck.country, ck.city
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         WHERE $whereStr
         ORDER BY cv.converted_at DESC
         LIMIT $limit",
        $cvParams
    );

    echo json_encode([
        'success' => true,
        'tab' => $tab,
        'conversions' => $convRows
    ]);
    exit;

} elseif ($tab === 'sl_report') {
    $slClkWhere  = ['c.affiliate_id=?', 'c.smartlink_id IS NOT NULL', 'c.clicked_at BETWEEN ? AND ?'];
    $slClkParams = [$affId, $dateFrom, $dateTo];
    if ($country !== '') { $slClkWhere[] = 'c.country=?';       $slClkParams[] = strtoupper($country); }
    $slClkWhere = implode(' AND ', $slClkWhere);

    $slClicks = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.source, c.ip_address, c.country, c.city, c.os, c.browser, c.device_type, c.clicked_at,
                COALESCE(sl.name,'— Unknown —') as smartlink_name,
                COALESCE(o.name, '— Custom URL —') as offer_name,
                COALESCE(cv.status,'') as conv_status,
                COALESCE(cv.payout, 0) as conv_payout
         FROM clicks c
         LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
         LEFT JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
         WHERE $slClkWhere
         ORDER BY c.clicked_at DESC LIMIT $limit",
        $slClkParams
    );

    echo json_encode([
        'success' => true,
        'tab' => $tab,
        'sl_clicks' => $slClicks
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid tab']);
