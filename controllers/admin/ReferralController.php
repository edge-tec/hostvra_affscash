<?php
Auth::check('admin');
$pageTitle = 'Referral System';
Referral::migrate();

// ── Approve / reject commission ───────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::postRaw('action');
    $id     = (int)Helpers::postRaw('commission_id');
    if ($action === 'approve_commission' && $id) {
        $comm = Database::fetchOne("SELECT * FROM referral_commissions WHERE id=? AND status='pending'", [$id]);
        if ($comm) {
            Database::update('referral_commissions', ['status'=>'approved'], 'id=?', [$id]);
            Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?",
                [$comm['commission_amount'], $comm['referrer_aff_id']]);
            Helpers::flash('success', 'Commission approved and credited.');
        }
    } elseif ($action === 'reject_commission' && $id) {
        Database::update('referral_commissions', ['status'=>'rejected'], 'id=?', [$id]);
        Helpers::flash('success', 'Commission rejected.');
    }
    Helpers::redirect('/admin/referrals');
}

$tab = Helpers::get('tab', 'signups');

// ── Signups tab ───────────────────────────────────────────────────────────
$signups = [];
if ($tab === 'signups') {
    Referral::migrate();
    $signups = Database::fetchAll(
        "SELECT rs.*,
                CONCAT(ru.first_name,' ',ru.last_name) as referrer_name,
                ru.email as referrer_email,
                rc_ref.code as referrer_code,
                CONCAT(nu.first_name,' ',nu.last_name) as referred_name,
                nu.email as referred_email,
                nu.status as referred_status,
                na.affiliate_code as referred_aff_code,
                na.balance as referred_balance
         FROM referral_signups rs
         JOIN users ru ON ru.id=rs.referrer_user_id
         LEFT JOIN referral_codes rc_ref ON rc_ref.user_id=rs.referrer_user_id
         JOIN users nu ON nu.id=rs.referred_user_id
         JOIN affiliates na ON na.id=rs.referred_aff_id
         ORDER BY rs.created_at DESC"
    );
}

// ── Commissions tab ────────────────────────────────────────────────────────
$commissions = [];
$commTotals  = ['total'=>0,'approved'=>0,'pending'=>0,'amount'=>0];
if ($tab === 'commissions') {
    $commissions = Database::fetchAll(
        "SELECT rc.*,
                CONCAT(ru.first_name,' ',ru.last_name) as referrer_name,
                ru.email as referrer_email,
                rfa.affiliate_code as referrer_code,
                CONCAT(nu.first_name,' ',nu.last_name) as referred_name,
                nu.email as referred_email,
                nfa.affiliate_code as referred_code
         FROM referral_commissions rc
         JOIN affiliates rfa ON rfa.id=rc.referrer_aff_id
         JOIN users ru ON ru.id=rfa.user_id
         JOIN affiliates nfa ON nfa.id=rc.referred_aff_id
         JOIN users nu ON nu.id=nfa.user_id
         ORDER BY rc.created_at DESC
         LIMIT 1000"
    );
    $commTotals = Database::fetchOne(
        "SELECT
            COUNT(*) as total,
            COALESCE(SUM(status='approved'), 0) as approved,
            COALESCE(SUM(status='pending'),  0) as pending,
            COALESCE(SUM(CASE WHEN status='approved' THEN commission_amount ELSE 0 END), 0) as amount
         FROM referral_commissions"
    ) ?? ['total' => 0, 'approved' => 0, 'pending' => 0, 'amount' => 0];
}

// ── Referral codes tab ─────────────────────────────────────────────────────
$codes = [];
if ($tab === 'codes') {
    $codes = Database::fetchAll(
        "SELECT rc.*, CONCAT(u.first_name,' ',u.last_name) as user_name,
                u.email, u.role as user_role,
                (SELECT COUNT(*) FROM referral_signups rs WHERE rs.referrer_user_id=rc.user_id) as signup_count,
                (SELECT COALESCE(SUM(commission_amount),0) FROM referral_commissions rcomm
                 JOIN affiliates af ON af.id=rcomm.referrer_aff_id
                 WHERE af.user_id=rc.user_id AND rcomm.status='approved') as total_earned
         FROM referral_codes rc
         JOIN users u ON u.id=rc.user_id
         ORDER BY rc.role, signup_count DESC"
    );
}

$appUrl = rtrim(Config::get('config','app.url') ?? '', '/');
require BASE_PATH . '/views/admin/referrals/index.php';
