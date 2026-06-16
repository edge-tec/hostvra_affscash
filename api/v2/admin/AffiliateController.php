<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    $affiliates = Database::fetchAll(
        "SELECT u.id as user_id, u.email, u.first_name, u.last_name, u.company, u.phone, u.country, u.status, u.created_at,
                u.last_login, u.inactivity_deactivated_at,
                CASE WHEN u.last_login IS NULL THEN NULL ELSE DATEDIFF(NOW(), u.last_login) END AS days_inactive,
                af.affiliate_code, af.balance, af.payment_method, af.payment_threshold, af.id as aff_id,
                ROUND(COALESCE(AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0
                                         THEN cv.fraud_score END), 0)) as fraud_score,
                SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as fraud_checked_count
         FROM users u
         JOIN affiliates af ON af.user_id=u.id
         LEFT JOIN conversions cv ON cv.affiliate_id = af.id AND COALESCE(cv.is_hidden,0)=0
         WHERE u.role='affiliate' AND u.status!='deleted'
         GROUP BY u.id, u.email, u.first_name, u.last_name, u.company, u.phone, u.country, u.status, u.created_at,
                  u.last_login, u.inactivity_deactivated_at,
                  af.affiliate_code, af.balance, af.payment_method, af.payment_threshold, af.id
         ORDER BY u.created_at DESC"
    );

    foreach ($affiliates as &$aff) {
        $aff['balance'] = (float)$aff['balance'];
        $aff['payment_threshold'] = (float)$aff['payment_threshold'];
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
