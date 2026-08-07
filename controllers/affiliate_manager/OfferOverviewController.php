<?php
Auth::check('affiliate_manager');
$pageTitle = 'Offer Overview';

$affIds  = Auth::managerAffiliateIds();
$offerId = (int)($_GET['id'] ?? 0);

// Verify this offer is relevant to the manager's affiliates
$offer = Database::fetchOne("SELECT * FROM offers WHERE id=? AND status='active'", [$offerId]);
if (!$offer) Helpers::redirect('/affiliate_manager/dashboard');

$inSql    = $affIds ? implode(',', array_fill(0, count($affIds), '?')) : '0';
$inParams = $affIds ?: [0];

$pageTitle = 'Offer — ' . $offer['name'];

$today = date('Y-m-d');

// ── Today stats (scoped to managed affiliates) ────────────────────────────
$todayStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
            SUM(conversions) as conversions, SUM(approved) as approved,
            SUM(rejected) as rejected, SUM(payout) as payout, SUM(fraud_clicks) as fraud_clicks
     FROM stats_daily WHERE offer_id=? AND stat_date=? AND affiliate_id IN ($inSql)",
    array_merge([$offerId, $today], $inParams)
) ?? [];

// ── 30-day stats ──────────────────────────────────────────────────────────
$monthStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
            SUM(conversions) as conversions, SUM(approved) as approved,
            SUM(rejected) as rejected, SUM(payout) as payout, SUM(fraud_clicks) as fraud_clicks
     FROM stats_daily WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND affiliate_id IN ($inSql)",
    array_merge([$offerId], $inParams)
) ?? [];

// ── All-time stats ────────────────────────────────────────────────────────
$allStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(conversions) as conversions,
            SUM(approved) as approved, SUM(payout) as payout
     FROM stats_daily WHERE offer_id=? AND affiliate_id IN ($inSql)",
    array_merge([$offerId], $inParams)
) ?? [];

// ── 30-day chart data ─────────────────────────────────────────────────────
$chartData = Database::fetchAll(
    "SELECT stat_date, SUM(clicks) as clicks, SUM(conversions) as conversions, SUM(payout) as payout
     FROM stats_daily WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND affiliate_id IN ($inSql)
     GROUP BY stat_date ORDER BY stat_date",
    array_merge([$offerId], $inParams)
);

// ── Conversion status pie ─────────────────────────────────────────────────
$convStatus = Database::fetchAll(
    "SELECT status, COUNT(*) as cnt FROM conversions WHERE offer_id=? AND affiliate_id IN ($inSql) AND is_hidden=0 AND (hide_reason IS NULL OR hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = conversions.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = conversions.click_id) GROUP BY status",
    array_merge([$offerId], $inParams)
);

// ── Top affiliates ────────────────────────────────────────────────────────
$topAffiliates = Database::fetchAll(
    "SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code,
            SUM(sd.clicks) as clicks, SUM(sd.conversions) as conversions,
            SUM(sd.approved) as approved, SUM(sd.payout) as payout
     FROM stats_daily sd
     JOIN affiliates af ON af.id=sd.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE sd.offer_id=? AND sd.affiliate_id IN ($inSql)
           AND sd.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY sd.affiliate_id ORDER BY payout DESC LIMIT 10",
    array_merge([$offerId], $inParams)
);

// ── Top countries ─────────────────────────────────────────────────────────
$topCountries = Database::fetchAll(
    "SELECT country, COUNT(*) as clicks, SUM(is_unique) as uclicks, SUM(payout) as payout
     FROM clicks WHERE offer_id=? AND affiliate_id IN ($inSql) AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY country ORDER BY clicks DESC LIMIT 15",
    array_merge([$offerId], $inParams)
);

// ── 7-day payout bar ──────────────────────────────────────────────────────
$weekData = Database::fetchAll(
    "SELECT stat_date, SUM(clicks) as clicks, SUM(payout) as payout
     FROM stats_daily WHERE offer_id=? AND affiliate_id IN ($inSql)
           AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY stat_date ORDER BY stat_date",
    array_merge([$offerId], $inParams)
);

// ── Recent conversions ────────────────────────────────────────────────────
$recentConversions = Database::fetchAll(
    "SELECT cv.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM conversions cv
     JOIN affiliates af ON af.id=cv.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE cv.offer_id=? AND cv.affiliate_id IN ($inSql) AND cv.is_hidden=0 AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = cv.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)
     ORDER BY cv.converted_at DESC LIMIT 20",
    array_merge([$offerId], $inParams)
);

// ── Cap usage (conversions, matching click.php enforcement with CURDATE()) ─
$todayCapUsed = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM conversions
     WHERE offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved') AND is_hidden=0 AND (hide_reason IS NULL OR hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = conversions.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = conversions.click_id)",
    [$offerId]
)['c'] ?? 0);
$totalCapUsed = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM conversions WHERE offer_id=? AND status IN ('pending','approved') AND is_hidden=0 AND (hide_reason IS NULL OR hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = conversions.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = conversions.click_id)",
    [$offerId]
)['c'] ?? 0);
$todayClicks = (int)($todayStats['clicks'] ?? 0);
$allClicks   = (int)($allStats['clicks']   ?? 0);

require BASE_PATH . '/views/affiliate_manager/offer_overview.php';
