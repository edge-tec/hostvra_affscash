<?php
Auth::check('affiliate_manager');
$pageTitle = 'My Referral Link';
$userId    = Auth::id();

Referral::migrate();

// Get or generate this manager's referral code
$refCode = Referral::getOrCreateCode($userId, 'affiliate_manager');
$refLink = Referral::getLink($userId, 'affiliate_manager');
$stats   = Referral::getStats($userId);

// Affiliates referred by this manager
$referred = Database::fetchAll(
    "SELECT rs.*,
            CONCAT(u.first_name,' ',u.last_name) as name,
            u.email, u.status, u.created_at as joined_at,
            a.affiliate_code,
            a.balance,
            (SELECT COUNT(*) FROM clicks cl WHERE cl.affiliate_id=a.id AND cl.status='valid') as click_count,
            (SELECT COUNT(*) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved') as conv_count,
            (SELECT COALESCE(SUM(payout),0) FROM conversions cv WHERE cv.affiliate_id=a.id AND cv.status='approved') as total_earned
     FROM referral_signups rs
     JOIN users u ON u.id=rs.referred_user_id
     JOIN affiliates a ON a.id=rs.referred_aff_id
     WHERE rs.referrer_user_id=? AND rs.referrer_role='affiliate_manager'
     ORDER BY rs.created_at DESC",
    [$userId]
);

require BASE_PATH . '/views/affiliate_manager/referral.php';
