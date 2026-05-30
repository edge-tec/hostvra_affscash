<?php
Auth::check('admin');

// Schema migrations
try { Database::query("ALTER TABLE affiliate_offers ADD COLUMN notes TEXT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE affiliate_offers ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch(Exception $e) {}

$offerId = (int)($_GET['id'] ?? 0);
$offer   = Database::fetchOne(
    "SELECT o.*, CONCAT(u.first_name,' ',u.last_name) as adv_name, u.company as adv_company
     FROM offers o
     JOIN advertisers adv ON adv.id=o.advertiser_id
     JOIN users u ON u.id=adv.user_id
     WHERE o.id=?",
    [$offerId]
);
if (!$offer) Helpers::redirect('/admin/offers');
$pageTitle = 'Offer Overview — ' . $offer['name'];

$today     = date('Y-m-d');
$monthStart = date('Y-m-01');

// ── Today stats ───────────────────────────────────────────────────────────
$todayStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
            SUM(conversions) as conversions, SUM(approved) as approved,
            SUM(rejected) as rejected, SUM(payout) as payout, SUM(revenue) as revenue,
            SUM(fraud_clicks) as fraud_clicks
     FROM stats_daily WHERE offer_id=? AND stat_date=?",
    [$offerId, $today]
) ?? [];

// ── 30-day stats ──────────────────────────────────────────────────────────
$monthStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
            SUM(conversions) as conversions, SUM(approved) as approved,
            SUM(rejected) as rejected, SUM(payout) as payout, SUM(revenue) as revenue,
            SUM(fraud_clicks) as fraud_clicks
     FROM stats_daily WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
    [$offerId]
) ?? [];

// ── All-time stats ────────────────────────────────────────────────────────
$allStats = Database::fetchOne(
    "SELECT SUM(clicks) as clicks, SUM(conversions) as conversions,
            SUM(approved) as approved, SUM(payout) as payout, SUM(revenue) as revenue
     FROM stats_daily WHERE offer_id=?",
    [$offerId]
) ?? [];

// ── 30-day chart data ─────────────────────────────────────────────────────
$chartData = Database::fetchAll(
    "SELECT stat_date, SUM(clicks) as clicks, SUM(conversions) as conversions,
            SUM(payout) as payout, SUM(revenue) as revenue
     FROM stats_daily WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY stat_date ORDER BY stat_date",
    [$offerId]
);

// ── Conversion status breakdown ───────────────────────────────────────────
$convStatus = Database::fetchAll(
    "SELECT status, COUNT(*) as cnt FROM conversions WHERE offer_id=? GROUP BY status",
    [$offerId]
);

// ── Top affiliates (30 days) ──────────────────────────────────────────────
$topAffiliates = Database::fetchAll(
    "SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code,
            SUM(sd.clicks) as clicks, SUM(sd.conversions) as conversions,
            SUM(sd.approved) as approved, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue
     FROM stats_daily sd
     JOIN affiliates af ON af.id=sd.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE sd.offer_id=? AND sd.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY sd.affiliate_id ORDER BY payout DESC LIMIT 10",
    [$offerId]
);

// ── Top countries (30 days) ───────────────────────────────────────────────
$topCountries = Database::fetchAll(
    "SELECT country, COUNT(*) as clicks, SUM(is_unique) as uclicks,
            SUM(payout) as payout, SUM(revenue) as revenue
     FROM clicks WHERE offer_id=? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY country ORDER BY clicks DESC LIMIT 15",
    [$offerId]
);

// ── Approved affiliates count ─────────────────────────────────────────────
$approvedAffCount = Database::count('affiliate_offers', "offer_id=? AND status='approved'", [$offerId]);
$pendingAffCount  = Database::count('affiliate_offers', "offer_id=? AND status='pending'",  [$offerId]);

// ── Pending affiliate applications (with promotion description) ────────────
$pendingApplications = Database::fetchAll(
    "SELECT ao.id, ao.notes, ao.created_at as applied_at,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, u.email as aff_email,
            af.affiliate_code
     FROM affiliate_offers ao
     JOIN affiliates af ON af.id=ao.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE ao.offer_id=? AND ao.status='pending'
     ORDER BY ao.created_at DESC",
    [$offerId]
);

// ── Cap usage ─────────────────────────────────────────────────────────────
// Daily cap tracks CONVERSIONS (matching click.php enforcement which uses CURDATE() on conversions).
// This resets automatically every day — no cron needed.
$todayCapUsed = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM conversions
     WHERE offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
    [$offerId]
)['c'] ?? 0);
// Total cap also tracks all-time conversions (not clicks)
$totalCapUsed = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM conversions WHERE offer_id=? AND status IN ('pending','approved')",
    [$offerId]
)['c'] ?? 0);
// Keep click counts for other stats sections
$todayClicks = (int)($todayStats['clicks'] ?? 0);
$allClicks   = (int)($allStats['clicks']   ?? 0);

// ── Recent conversions ────────────────────────────────────────────────────
$recentConversions = Database::fetchAll(
    "SELECT cv.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM conversions cv
     JOIN affiliates af ON af.id=cv.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE cv.offer_id=? ORDER BY cv.converted_at DESC LIMIT 20",
    [$offerId]
);

// ── 7-day revenue chart ───────────────────────────────────────────────────
$weekData = Database::fetchAll(
    "SELECT stat_date, SUM(clicks) as clicks, SUM(payout) as payout, SUM(revenue) as revenue
     FROM stats_daily WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY stat_date ORDER BY stat_date",
    [$offerId]
);

// ── All affiliates for link generator ────────────────────────────────────
$allAffiliates = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
     FROM affiliates af
     JOIN users u ON u.id=af.user_id
     WHERE u.status='active'
     ORDER BY name"
);

$appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

require BASE_PATH . '/views/admin/offers/overview.php';
