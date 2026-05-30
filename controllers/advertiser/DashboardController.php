<?php
Auth::check('advertiser');
$pageTitle = 'Advertiser Dashboard';

$advId = Auth::advertiserId();
$adv   = Database::fetchOne("SELECT * FROM advertisers WHERE id=?", [$advId]);

$today = date('Y-m-d');
$myOffers = Database::fetchAll("SELECT id FROM offers WHERE advertiser_id=?", [$advId]);
$offerIds = array_column($myOffers, 'id') ?: [0];
$in = implode(',', $offerIds);

$todayStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(approved) as approved, SUM(revenue) as revenue
     FROM stats_daily WHERE offer_id IN ($in) AND stat_date=?",
    [$today]
);
$totalOffers = count($offerIds);
$totalConversions = Database::count('conversions', "offer_id IN ($in)");

// Rejected conversions are hidden from the advertiser account everywhere,
// including the dashboard's recent conversions panel.
$recentConversions = Database::fetchAll(
    "SELECT c.*,o.name as offer_name
     FROM conversions c
     JOIN offers o ON o.id=c.offer_id
     WHERE o.advertiser_id=? AND c.status <> 'rejected'
     ORDER BY c.converted_at DESC LIMIT 10",
    [$advId]
);

// Performance trend is now loaded async via /api/advertiser-analytics so the
// dashboard can support multiple date ranges without re-querying server-side.

$appUrl = Config::get('config','app.url') ?? '';

require BASE_PATH . '/views/advertiser/dashboard.php';
