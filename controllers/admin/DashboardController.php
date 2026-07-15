<?php
Auth::check('admin');
$pageTitle = 'Dashboard';

// Schema migrations
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN is_test TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

// Total counts (static header cards)
$totalAffiliates   = Database::count('users', 'role=? AND status=?', ['affiliate', 'active']);
$pendingAffiliates = Database::count('users', 'role=? AND status=?', ['affiliate', 'pending']);
$totalAdvertisers  = Database::count('users', 'role=? AND status=?', ['advertiser', 'active']);
$totalOffers       = Database::count('offers', 'status=?', ['active']);

// Profit summary (last 30 days) — static section below charts
$profitSummary = Database::fetchOne(
    "SELECT SUM(revenue) as total_revenue, SUM(payout) as total_payout, SUM(revenue-payout) as total_profit
     FROM conversions WHERE status='approved' AND is_hidden=0 AND converted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
) ?? [];

// Top profitable offers (last 30 days) — static section below charts
$topProfitOffers = Database::fetchAll(
    "SELECT o.name, o.id,
            SUM(c.revenue) as revenue, SUM(c.payout) as payout,
            SUM(c.revenue - c.payout) as profit,
            COUNT(*) as conversions
     FROM conversions c JOIN offers o ON o.id=c.offer_id
     WHERE c.status='approved' AND c.is_hidden=0 AND c.converted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY o.id ORDER BY profit DESC LIMIT 10"
);

// Conversions by Traffic Source (last 30 days)
$trafficSources = Database::fetchAll(
    "SELECT COALESCE(NULLIF(traffic_source,''),'Unknown') as source,
            COUNT(*) as conversions,
            SUM(revenue) as revenue, SUM(payout) as payout,
            SUM(revenue-payout) as profit
     FROM conversions
     WHERE status='approved' AND is_hidden=0
       AND converted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY source ORDER BY conversions DESC LIMIT 15"
);
require_once BASE_PATH . '/core/TrafficSourceDetector.php';

require BASE_PATH . '/views/admin/dashboard.php';
