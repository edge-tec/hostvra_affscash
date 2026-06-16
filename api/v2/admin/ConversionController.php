<?php
header('Content-Type: application/json');

if (!Auth::check('admin', false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $conversions = Database::fetchAll(
        "SELECT c.conversion_id, c.click_id, c.offer_id, c.affiliate_id, c.payout, c.revenue, c.status, 
                c.country, c.ip_address, c.postback_sent, c.converted_at, c.fraud_score,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code
         FROM conversions c
         LEFT JOIN offers o ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u ON u.id = af.user_id
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
