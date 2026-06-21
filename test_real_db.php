<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';

try {
    $db = Database::getInstance();
    echo "Connected to DB\n";

    $from = '2023-01-01';
    $to = '2023-01-30';
    $offerId = 0;
    $affId = 1; // Try with an affiliate ID!
    $managerAffIds = [1, 2, 3]; // Mock manager aff ids

    // Mock adminStatsWhere
    $w = ['sd.stat_date BETWEEN ? AND ?'];
    $p = [$from, $to];
    if ($offerId) { $w[] = 'sd.offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'sd.affiliate_id = ?'; $p[] = $affId; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "sd.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    $statsW = implode(' AND ', $w);
    $statsP = $p;

    $sql1 = "SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv FROM stats_daily sd WHERE $statsW";
    echo "Running Q1: $sql1\n";
    print_r(Database::fetchOne($sql1, $statsP));

    // Mock adminConvWhere
    $w2 = ['c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
    $p2 = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $w2[] = 'c.offer_id = ?'; $p2[] = $offerId; }
    if ($affId)   { $w2[] = 'c.affiliate_id = ?'; $p2[] = $affId; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w2[] = "c.affiliate_id IN ($in)";
        $p2   = array_merge($p2, $managerAffIds);
    }
    $convW = implode(' AND ', $w2);
    $convP = $p2;

    $sql2 = "SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW";
    echo "Running Q2: $sql2\n";
    print_r(Database::fetchOne($sql2, $convP));

    $sql3 = "SELECT SUM(c.payout * 0.05) as comm FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW AND c.status = 'approved'";
    echo "Running Q3: $sql3\n";
    print_r(Database::fetchOne($sql3, $convP));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
