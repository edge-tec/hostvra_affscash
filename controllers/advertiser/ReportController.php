<?php
Auth::check('advertiser');
$pageTitle = 'Reports';
$advId = Auth::advertiserId();

$from = Helpers::get('from') ?: date('Y-m-01');
$to   = Helpers::get('to') ?: date('Y-m-d');

$myOfferIds = array_column(Database::fetchAll("SELECT id FROM offers WHERE advertiser_id=?", [$advId]), 'id');
$in = $myOfferIds ? implode(',', $myOfferIds) : '0';

$rows = Database::fetchAll(
    "SELECT sd.stat_date as label, o.name as offer_name, SUM(sd.clicks) as clicks, SUM(sd.conversions) as conv, SUM(sd.approved) as approved, SUM(sd.revenue) as revenue, SUM(sd.payout) as payout
     FROM stats_daily sd JOIN offers o ON o.id=sd.offer_id
     WHERE sd.offer_id IN ($in) AND sd.stat_date BETWEEN ? AND ?
     GROUP BY sd.stat_date, sd.offer_id ORDER BY sd.stat_date DESC",
    [$from, $to]
);

$totals = ['clicks'=>array_sum(array_column($rows,'clicks')),'conv'=>array_sum(array_column($rows,'conv')),'revenue'=>array_sum(array_column($rows,'revenue'))];

require BASE_PATH . '/views/advertiser/reports/index.php';
