<?php
/**
 * Cron: Rebuild daily stats from raw clicks/conversions
 * Run at midnight: 0 0 * * * php /path/to/cron/stats_cache.php
 */
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/FraudIQ.php';
Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

$date = $_SERVER['argv'][1] ?? date('Y-m-d', strtotime('-1 day'));

echo "Rebuilding stats for $date...\n";

// Get click stats
$clickStats = Database::fetchAll(
    "SELECT offer_id, affiliate_id, COUNT(*) as clicks, SUM(is_unique) as unique_clicks, SUM(CASE WHEN is_fraud=1 THEN 1 ELSE 0 END) as fraud FROM clicks WHERE DATE(clicked_at)=? AND status NOT IN ('blocked') GROUP BY offer_id, affiliate_id",
    [$date]
);

// Get conversion stats
$convStats = Database::fetchAll(
    "SELECT offer_id, affiliate_id, COUNT(*) as conversions, SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status='approved' THEN payout ELSE 0 END) as payout, SUM(CASE WHEN status='approved' THEN revenue ELSE 0 END) as revenue FROM conversions WHERE DATE(converted_at)=? GROUP BY offer_id, affiliate_id",
    [$date]
);

// Index conversion stats
$convIdx = [];
foreach ($convStats as $cs) {
    $convIdx[$cs['offer_id'] . '_' . $cs['affiliate_id']] = $cs;
}

foreach ($clickStats as $cs) {
    $key  = $cs['offer_id'] . '_' . $cs['affiliate_id'];
    $conv = $convIdx[$key] ?? [];

    Database::query(
        "INSERT INTO stats_daily (stat_date, affiliate_id, offer_id, clicks, unique_clicks, conversions, approved, rejected, payout, revenue, fraud_clicks)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE clicks=VALUES(clicks), unique_clicks=VALUES(unique_clicks), conversions=VALUES(conversions), approved=VALUES(approved), rejected=VALUES(rejected), payout=VALUES(payout), revenue=VALUES(revenue), fraud_clicks=VALUES(fraud_clicks)",
        [
            $date,
            $cs['affiliate_id'],
            $cs['offer_id'],
            $cs['clicks'],
            $cs['unique_clicks'],
            $conv['conversions'] ?? 0,
            $conv['approved'] ?? 0,
            $conv['rejected'] ?? 0,
            $conv['payout'] ?? 0,
            $conv['revenue'] ?? 0,
            $cs['fraud'],
        ]
    );
}

echo "Done. Processed " . count($clickStats) . " affiliate-offer combinations.\n";
