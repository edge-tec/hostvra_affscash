<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    $affIds = Auth::managerAffiliateIds();
    
    if (empty($affIds)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $inPlaceholders = implode(',', array_fill(0, count($affIds), '?'));

    $conversions = Database::fetchAll(
        "SELECT c.conversion_id, c.click_id, c.offer_id, c.affiliate_id, c.payout, c.revenue, c.status, 
                c.country, c.ip_address, c.postback_sent, c.converted_at, c.fraud_score,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code,
                cl.source, cl.device_type, cl.os
         FROM conversions c
         LEFT JOIN offers o ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u ON u.id = af.user_id
         LEFT JOIN clicks cl ON cl.click_id = c.click_id
         WHERE c.affiliate_id IN ($inPlaceholders)
         ORDER BY c.converted_at DESC
         LIMIT 500",
         $affIds
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
