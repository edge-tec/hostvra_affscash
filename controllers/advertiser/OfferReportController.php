<?php
/**
 * Advertiser — Offer Report.
 * Two modes:
 *   /advertiser/reports/offers              → summary across all offers
 *   /advertiser/reports/offers?id={offerId} → drill-down for one offer with
 *                                              top affiliates, traffic sources,
 *                                              device / browser / OS distribution
 */
Auth::check('advertiser');
$pageTitle = 'Offer Report';
$advId     = Auth::advertiserId();

$from    = Helpers::get('from') ?: date('Y-m-01');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$offerId = (int)Helpers::get('id');

$dateRange = [$from . ' 00:00:00', $to . ' 23:59:59'];

// ── Detail view ────────────────────────────────────────────────────────────
if ($offerId > 0) {
    $offer = Database::fetchOne(
        "SELECT * FROM offers WHERE id=? AND advertiser_id=?",
        [$offerId, $advId]
    );
    if (!$offer) {
        Helpers::flash('error', 'Offer not found or not owned by you.');
        Helpers::redirect('/advertiser/reports/offers');
    }

    // Rejected conversions are hidden from advertisers — same approach the
    // Performance / Conversion reports use. The cv.status filter sits on the
    // JOIN so rejected rows never contribute to counts, revenue or payout.
    $base   = "JOIN offers o ON o.id = c.offer_id
               LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
               WHERE c.offer_id = ? AND o.advertiser_id = ? AND c.clicked_at BETWEEN ? AND ?";
    $bArgs  = [$offerId, $advId, $dateRange[0], $dateRange[1]];

    $totals = ['traffic'=>0,'clicks'=>0,'conv'=>0,'approved'=>0,'pending'=>0,'revenue'=>0,'payout'=>0];
    try {
        $r = Database::fetchOne(
            "SELECT COUNT(*) AS traffic,
                    SUM(CASE WHEN c.status IN ('valid','duplicate') THEN 1 ELSE 0 END) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                    SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN cv.status='pending'  THEN 1 ELSE 0 END) AS pending,
                    COALESCE(SUM(cv.revenue),0) AS revenue,
                    COALESCE(SUM(cv.payout),0)  AS payout
             FROM clicks c $base",
            $bArgs
        );
        if ($r) $totals = array_merge($totals, $r);
    } catch (\Throwable $_) {}
    $totals['cr'] = $totals['clicks'] > 0 ? round(($totals['conv'] / $totals['clicks']) * 100, 2) : 0;

    // Top affiliates
    $topAff = [];
    try {
        $topAff = Database::fetchAll(
            "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name,
                    COUNT(*) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                    COALESCE(SUM(cv.payout),0) AS payout
             FROM clicks c
             JOIN offers o      ON o.id = c.offer_id
             JOIN affiliates af ON af.id = c.affiliate_id
             JOIN users u       ON u.id = af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
             WHERE c.offer_id = ? AND o.advertiser_id = ? AND c.clicked_at BETWEEN ? AND ?
             GROUP BY af.id, af.affiliate_code, name
             ORDER BY clicks DESC
             LIMIT 25",
            $bArgs
        ) ?: [];
    } catch (\Throwable $_) {}

    // Traffic sources (clicks.source — populated when set on the tracking URL)
    $bySource = [];
    try {
        $bySource = Database::fetchAll(
            "SELECT COALESCE(NULLIF(c.source,''),'(direct)') AS source,
                    COUNT(*) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv
             FROM clicks c $base
             GROUP BY source
             ORDER BY clicks DESC
             LIMIT 25",
            $bArgs
        ) ?: [];
    } catch (\Throwable $_) {}

    // Device / browser / OS distributions
    $byDevice = [];
    $byBrowser = [];
    $byOs = [];
    try {
        $byDevice = Database::fetchAll(
            "SELECT c.device_type AS k, COUNT(*) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv
             FROM clicks c $base
             GROUP BY c.device_type
             ORDER BY clicks DESC",
            $bArgs
        ) ?: [];
        $byBrowser = Database::fetchAll(
            "SELECT COALESCE(NULLIF(c.browser,''),'Unknown') AS k, COUNT(*) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv
             FROM clicks c $base
             GROUP BY k
             ORDER BY clicks DESC
             LIMIT 15",
            $bArgs
        ) ?: [];
        $byOs = Database::fetchAll(
            "SELECT COALESCE(NULLIF(c.os,''),'Unknown') AS k, COUNT(*) AS clicks,
                    SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv
             FROM clicks c $base
             GROUP BY k
             ORDER BY clicks DESC
             LIMIT 15",
            $bArgs
        ) ?: [];
    } catch (\Throwable $_) {}

    require BASE_PATH . '/views/advertiser/reports/offer_detail.php';
    return;
}

// ── Summary view (all offers) ──────────────────────────────────────────────
$rows = [];
try {
    $rows = Database::fetchAll(
        "SELECT o.id, o.name, o.status, o.payout_type, o.payout_amount,
                COUNT(c.id) AS traffic,
                SUM(CASE WHEN c.status IN ('valid','duplicate') THEN 1 ELSE 0 END) AS clicks,
                SUM(CASE WHEN cv.id IS NOT NULL THEN 1 ELSE 0 END) AS conv,
                SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                COALESCE(SUM(cv.revenue),0) AS revenue,
                COALESCE(SUM(cv.payout),0)  AS payout
         FROM offers o
         LEFT JOIN clicks c        ON c.offer_id = o.id AND c.clicked_at BETWEEN ? AND ?
         LEFT JOIN conversions cv  ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE o.advertiser_id = ?
         GROUP BY o.id, o.name, o.status, o.payout_type, o.payout_amount
         ORDER BY clicks DESC, o.name",
        [$dateRange[0], $dateRange[1], $advId]
    ) ?: [];
} catch (\Throwable $_) {}

require BASE_PATH . '/views/advertiser/reports/offers.php';
