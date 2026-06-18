<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate_manager');
    $userId = Auth::id();

    require_once BASE_PATH . '/core/Referral.php';
    Referral::migrate();

    $refCode = Referral::getOrCreateCode($userId, 'affiliate_manager');
    $refLink = Referral::getLink($userId, 'affiliate_manager');
    $stats   = Referral::getStats($userId);

    // Affiliates referred by this manager
    $referred = Database::fetchAll(
        "SELECT rs.created_at as joined_at,
                CONCAT(u.first_name,' ',u.last_name) as name,
                u.email, u.status,
                a.id as affiliate_id,
                a.affiliate_code,
                a.balance,
                (SELECT COUNT(*) FROM clicks cl WHERE cl.affiliate_id=a.id AND cl.status='valid') as click_count,
                (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved' AND cv.is_hidden=0) as conv_count,
                (SELECT COALESCE(SUM(payout),0) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved' AND cv.is_hidden=0) as total_earned
         FROM referral_signups rs
         JOIN users u ON u.id=rs.referred_user_id
         JOIN affiliates a ON a.id=rs.referred_aff_id
         WHERE rs.referrer_user_id=? AND rs.referrer_role='affiliate_manager'
         ORDER BY rs.created_at DESC",
        [$userId]
    ) ?: [];

    $activeCount = 0;
    foreach ($referred as $r) {
        if ($r['status'] === 'active') {
            $activeCount++;
        }
    }

    $totalEarnedAll = array_reduce($referred, function($carry, $item) {
        return $carry + (float)$item['total_earned'];
    }, 0.0);

    echo json_encode([
        'success' => true,
        'data' => [
            'referral_code' => $refCode,
            'referral_link' => $refLink,
            'stats' => [
                'total_referrals' => count($referred),
                'active_referrals' => $activeCount,
                'total_earned' => $totalEarnedAll
            ],
            'referred_affiliates' => $referred
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
