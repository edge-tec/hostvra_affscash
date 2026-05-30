<?php
/**
 * Advertiser — Performance Report.
 * Aggregates clicks + conversions across the advertiser's offers and groups
 * the data by offer, affiliate, and geo (country / city / region).
 */
Auth::check('advertiser');
$pageTitle = 'Performance Report';
$advId     = Auth::advertiserId();

$from    = Helpers::get('from') ?: date('Y-m-01');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)Helpers::get('offer_id');
$affId   = (int)Helpers::get('affiliate_id');
$country = strtoupper(substr(trim(Helpers::get('country') ?? ''), 0, 2));
$device  = Helpers::get('device');

// Shared WHERE: advertiser-scoped + filters. Applied to every aggregation.
$clkWhere  = ['o.advertiser_id = ?', 'c.clicked_at BETWEEN ? AND ?'];
$clkParams = [$advId, $from . ' 00:00:00', $to . ' 23:59:59'];
if ($offerId) { $clkWhere[] = 'c.offer_id = ?';     $clkParams[] = $offerId; }
if ($affId)   { $clkWhere[] = 'c.affiliate_id = ?'; $clkParams[] = $affId; }
if ($country) { $clkWhere[] = 'c.country = ?';      $clkParams[] = $country; }
if (in_array($device, ['desktop','mobile','tablet','bot','unknown'], true)) {
    $clkWhere[] = 'c.device_type = ?'; $clkParams[] = $device;
}
$clkWhereStr = implode(' AND ', $clkWhere);

// Top-line totals. Rejected conversions are excluded — advertisers should
// not see quality / fraud rejections in their account. The conversions
// LEFT JOIN gates on cv.status <> 'rejected' so neither the count nor the
// revenue / payout totals include them.
$totals = ['clicks'=>0, 'conv'=>0, 'approved'=>0, 'pending'=>0, 'revenue'=>0.0, 'payout'=>0.0];
try {
    $r = Database::fetchOne(
        "SELECT
            COUNT(*) AS clicks,
            SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
            SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN cv.status='pending'  THEN 1 ELSE 0 END) AS pending,
            COALESCE(SUM(cv.revenue), 0) AS revenue,
            COALESCE(SUM(cv.payout),  0) AS payout
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $clkWhereStr",
        $clkParams
    );
    if ($r) $totals = array_merge($totals, $r);
} catch (\Throwable $_) {}

$totals['cr'] = $totals['clicks'] > 0
    ? round(((int)$totals['conv'] / (int)$totals['clicks']) * 100, 2)
    : 0;

// Offer breakdown
$byOffer = [];
try {
    $byOffer = Database::fetchAll(
        "SELECT o.id, o.name,
                COUNT(*) AS clicks,
                SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                COALESCE(SUM(cv.revenue),0) AS revenue,
                COALESCE(SUM(cv.payout),0)  AS payout
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $clkWhereStr
         GROUP BY o.id, o.name
         ORDER BY clicks DESC
         LIMIT 100",
        $clkParams
    ) ?: [];
} catch (\Throwable $_) {}

// Affiliate breakdown
$byAff = [];
try {
    $byAff = Database::fetchAll(
        "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name,
                COUNT(*) AS clicks,
                SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                COALESCE(SUM(cv.payout),0)  AS payout
         FROM clicks c
         JOIN offers o      ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u       ON u.id = af.user_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $clkWhereStr
         GROUP BY af.id, af.affiliate_code, name
         ORDER BY clicks DESC
         LIMIT 100",
        $clkParams
    ) ?: [];
} catch (\Throwable $_) {}

// Geo breakdown (country)
$byCountry = [];
try {
    $byCountry = Database::fetchAll(
        "SELECT c.country,
                COUNT(*) AS clicks,
                SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                COALESCE(SUM(cv.revenue),0) AS revenue
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $clkWhereStr AND c.country != ''
         GROUP BY c.country
         ORDER BY clicks DESC
         LIMIT 100",
        $clkParams
    ) ?: [];
} catch (\Throwable $_) {}

// Geo breakdown (region/city)
$byRegion = [];
try {
    $byRegion = Database::fetchAll(
        "SELECT c.country, c.region, c.city,
                COUNT(*) AS clicks,
                SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $clkWhereStr AND c.country != ''
         GROUP BY c.country, c.region, c.city
         ORDER BY clicks DESC
         LIMIT 100",
        $clkParams
    ) ?: [];
} catch (\Throwable $_) {}

// Filter dropdown data
try {
    $myOffers = Database::fetchAll("SELECT id, name FROM offers WHERE advertiser_id=? ORDER BY name", [$advId]) ?: [];
} catch (\Throwable $_) { $myOffers = []; }
try {
    $myAffiliates = Database::fetchAll(
        "SELECT DISTINCT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
         FROM clicks c JOIN offers o ON o.id=c.offer_id
         JOIN affiliates af ON af.id=c.affiliate_id
         JOIN users u ON u.id=af.user_id
         WHERE o.advertiser_id=? ORDER BY name LIMIT 500",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $myAffiliates = []; }
try {
    $countries = Database::fetchAll(
        "SELECT DISTINCT c.country FROM clicks c JOIN offers o ON o.id=c.offer_id
         WHERE o.advertiser_id=? AND c.country!='' ORDER BY c.country",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $countries = []; }

require BASE_PATH . '/views/advertiser/reports/performance.php';
