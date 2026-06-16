<?php
header('Content-Type: application/json');

if (!Auth::check('affiliate_manager', false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $affIds = Auth::managerAffiliateIds();
    
    if (empty($affIds)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $inPlaceholders = implode(',', array_fill(0, count($affIds), '?'));

    $affiliates = Database::fetchAll(
        "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at,
                af.affiliate_code, af.balance, af.id as aff_id,
                ROUND(COALESCE(AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0
                                         THEN cv.fraud_score END), 0)) as fraud_score,
                SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as fraud_checked_count
         FROM users u
         JOIN affiliates af ON af.user_id=u.id
         LEFT JOIN conversions cv ON cv.affiliate_id = af.id AND COALESCE(cv.is_hidden,0)=0
         WHERE af.id IN ($inPlaceholders) AND u.status!='deleted'
         GROUP BY u.id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at,
                  af.affiliate_code, af.balance, af.id
         ORDER BY u.created_at DESC",
        $affIds
    );

    foreach ($affiliates as &$aff) {
        $aff['balance'] = (float)$aff['balance'];
        $aff['fraud_score'] = (int)$aff['fraud_score'];
        $aff['fraud_checked_count'] = (int)$aff['fraud_checked_count'];
    }

    echo json_encode([
        'success' => true,
        'data' => $affiliates
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
