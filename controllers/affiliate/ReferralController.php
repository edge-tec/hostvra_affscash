<?php
Auth::check('affiliate');
$pageTitle = 'My Referral Program';
$affId     = Auth::affiliateId();
$userId    = Auth::id();

Referral::migrate();

// Get or generate this affiliate's referral code
$refCode  = Referral::getOrCreateCode($userId, 'affiliate');
$refLink  = Referral::getLink($userId, 'affiliate');
$stats    = Referral::getStats($userId);

// List of affiliates this affiliate referred
$referred = Database::fetchAll(
    "SELECT rs.*,
            CONCAT(u.first_name,' ',u.last_name) as name,
            u.email, u.status,
            a.affiliate_code,
            a.balance,
            (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved') as conv_count
     FROM referral_signups rs
     JOIN users u ON u.id=rs.referred_user_id
     JOIN affiliates a ON a.id=rs.referred_aff_id
     WHERE rs.referrer_user_id=?
     ORDER BY rs.created_at DESC",
    [$userId]
);

// Commissions earned from referrals
$commissions = Database::fetchAll(
    "SELECT rc.*,
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
);

$commRate = Config::get('config', 'app.refer_commission_rate') ?? '5';
$commType = Config::get('config', 'app.refer_commission_type') ?? 'percent';

require BASE_PATH . '/views/affiliate/referral.php';
