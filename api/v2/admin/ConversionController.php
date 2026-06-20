<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    $conversions = Database::fetchAll(
        "SELECT c.conversion_id, c.click_id, c.offer_id, c.affiliate_id, c.payout, c.revenue, c.status, 
                COALESCE(NULLIF(c.country,''), NULLIF(cl.country,''), NULLIF(c.ipquery_country_code,'')) as country, 
                COALESCE(NULLIF(c.ip_address,''), cl.ip_address) as ip_address, 
                c.postback_sent, c.converted_at, c.fraud_score,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code,
                cl.source, cl.device_type, cl.os, 
                COALESCE(NULLIF(cl.city,''), NULLIF(c.ipquery_city,'')) as city, 
                COALESCE(NULLIF(cl.region,''), NULLIF(c.ipquery_state,'')) as region
         FROM conversions c
         LEFT JOIN offers o ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u ON u.id = af.user_id
         LEFT JOIN clicks cl ON cl.click_id = c.click_id
         ORDER BY c.converted_at DESC
         LIMIT 500"
    );

    foreach ($conversions as &$c) {
        $c['payout'] = (float)$c['payout'];
        $c['revenue'] = (float)$c['revenue'];
        $c['postback_sent'] = (int)$c['postback_sent'];
        $c['fraud_score'] = (int)$c['fraud_score'];
    }

    echo json_encode([
        'success' => true,
        'data' => $conversions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
