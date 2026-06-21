<?php
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $fcParams = [1, '2026-05-20 00:00:00', '2026-06-20 23:59:59'];
    $fcW = "c.affiliate_id=? AND c.converted_at BETWEEN ? AND ? AND COALESCE(c.is_hidden,0)=0";
    
    // This is from AffiliateAnalyticsController.php lines 110-112
    $fcCur = Database::fetchOne(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
         FROM conversions c WHERE $fcW",
        $fcParams
    );
    print_r($fcCur);

    // This is from AffiliateAnalyticsController.php lines 257-259
    $fraudRows = Database::fetchAll(
        "SELECT DATE(c.converted_at) as d,
                SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
         FROM conversions c WHERE $fcW GROUP BY DATE(c.converted_at)",
        $fcParams
    );
    print_r($fraudRows);

    // Also test offers endpoint line 498
    $offers = Database::fetchAll(
        "SELECT o.id, o.name
         FROM offers o
         JOIN affiliate_offers ao         ON ao.offer_id  = o.id
         LEFT JOIN private_offer_access poa ON poa.offer_id = o.id AND poa.affiliate_id = ?
         WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
           AND (COALESCE(o.visibility,'public') != 'private' OR poa.id IS NOT NULL)
         ORDER BY o.name",
        [1, 1]
    );
    print_r($offers);

} catch (\Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
