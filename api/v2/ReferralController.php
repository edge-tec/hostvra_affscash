<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    $affId = Auth::affiliateId();
    $userId = Auth::id();

    require_once BASE_PATH . '/core/Referral.php';
    Referral::migrate();

    $refCode  = Referral::getOrCreateCode($userId, 'affiliate');
    $refLink  = Referral::getLink($userId, 'affiliate');
    $stats    = Referral::getStats($userId);

    // List of affiliates this affiliate referred
    $referred = Database::fetchAll(
        "SELECT rs.created_at as joined_at,
                CONCAT(u.first_name,' ',u.last_name) as name,
                u.email, u.status,
                a.id as affiliate_id,
                a.affiliate_code,
                a.balance,
                (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved') as conv_count
         FROM referral_signups rs
         JOIN users u ON u.id=rs.referred_user_id
         JOIN affiliates a ON a.id=rs.referred_aff_id
         WHERE rs.referrer_user_id=?
         ORDER BY rs.created_at DESC",
        [$userId]
    ) ?: [];

    // Commissions earned from referrals
    $commissions = Database::fetchAll(
        "SELECT rc.id, rc.conversion_id, rc.base_payout, rc.commission_rate, rc.commission_type, rc.commission_amount, rc.status, rc.created_at,
                CONCAT(nu.first_name,' ',nu.last_name) as referred_name,
                nfa.affiliate_code as referred_code
         FROM referral_commissions rc
         JOIN affiliates nfa ON nfa.id=rc.referred_aff_id
         JOIN users nu ON nu.id=nfa.user_id
         JOIN affiliates rfa ON rfa.id=rc.referrer_aff_id
         WHERE rfa.id=?
         ORDER BY rc.created_at DESC
         LIMIT 200",
        [$affId]
    ) ?: [];

    $commRate = Config::get('config', 'app.refer_commission_rate') ?? '5';
    $commType = Config::get('config', 'app.refer_commission_type') ?? 'percent';

    echo json_encode([
        'success' => true,
        'data' => [
            'referral_code' => $refCode,
            'referral_link' => $refLink,
            'stats' => [
                'total_referrals' => $stats['signups'],
                'commissions_earned' => $stats['earned'],
                'commission_rate' => $commRate,
                'commission_type' => $commType
            ],
            'referred_affiliates' => $referred,
            'commissions' => $commissions
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
