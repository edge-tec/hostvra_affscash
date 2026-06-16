<?php
header('Content-Type: application/json');

Auth::check('affiliate');

$affId = Auth::affiliateId();
$tab = $_GET['tab'] ?? 'day';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$rows = [];
$totals = [];

if (in_array($tab, ['day', 'offer'])) {
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

    $totals = [
        'clicks'   => array_sum(array_column($rows, 'clicks')),
        'uclicks'  => array_sum(array_column($rows, 'uclicks')),
        'conv'     => array_sum(array_column($rows, 'conv')),
        'approved' => array_sum(array_column($rows, 'approved')),
        'rejected' => array_sum(array_column($rows, 'rejected')),
        'fraud'    => array_sum(array_column($rows, 'fraud')),
        'payout'   => round(array_sum(array_column($rows, 'payout')), 2),
    ];
}

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
