<?php
header('Content-Type: application/json');

try {
    Auth::check('admin');
    $action = $_GET['action'] ?? 'dashboard';

    require_once BASE_PATH . '/core/Referral.php';
    Referral::migrate();

    if ($action === 'dashboard') {
        $totalSignups = Database::fetchOne("SELECT COUNT(*) as c FROM referral_signups")['c'] ?? 0;
        $totalCodes   = Database::fetchOne("SELECT COUNT(*) as c FROM referral_codes")['c'] ?? 0;
        $totalComm    = Database::fetchOne("SELECT COALESCE(SUM(commission_amount),0) as a FROM referral_commissions WHERE status='approved'")['a'] ?? 0;
        $pendingComm  = Database::fetchOne("SELECT COUNT(*) as c FROM referral_commissions WHERE status='pending'")['c'] ?? 0;
        $commRate     = Config::get('config','app.refer_commission_rate') ?? '5';
        $commType     = Config::get('config','app.refer_commission_type') ?? 'percent';

        echo json_encode([
            'success' => true,
            'data' => [
                'total_signups' => $totalSignups,
                'total_codes' => $totalCodes,
                'total_commission_paid' => $totalComm,
                'pending_commissions' => $pendingComm,
                'commission_rate' => $commRate,
                'commission_type' => $commType
            ]
        ]);
        exit;
    }

    if ($action === 'signups') {
        $signups = Database::fetchAll(
            "SELECT rs.*,
                    CONCAT(ru.first_name,' ',ru.last_name) as referrer_name,
                    ru.email as referrer_email,
                    rc_ref.code as referrer_code,
                    CONCAT(nu.first_name,' ',nu.last_name) as referred_name,
                    nu.email as referred_email,
                    nu.status as referred_status,
                    na.affiliate_code as referred_aff_code,
                    na.balance as referred_balance,
                    ru.role as referrer_role
             FROM referral_signups rs
             JOIN users ru ON ru.id=rs.referrer_user_id
             LEFT JOIN referral_codes rc_ref ON rc_ref.user_id=rs.referrer_user_id
             JOIN users nu ON nu.id=rs.referred_user_id
             JOIN affiliates na ON na.id=rs.referred_aff_id
             ORDER BY rs.created_at DESC"
        );
        echo json_encode(['success' => true, 'data' => ['signups' => $signups]]);
        exit;
    }

    if ($action === 'commissions') {
        $commissions = Database::fetchAll(
            "SELECT rc.*,
                    CONCAT(ru.first_name,' ',ru.last_name) as referrer_name,
                    ru.email as referrer_email,
                    rfa.affiliate_code as referrer_code,
                    CONCAT(nu.first_name,' ',nu.last_name) as referred_name,
                    nu.email as referred_email,
                    nfa.affiliate_code as referred_aff_code
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

        echo json_encode([
            'success' => true,
            'data' => [
                'commissions' => $commissions,
                'totals' => $commTotals
            ]
        ]);
        exit;
    }

    if ($action === 'codes') {
        $codes = Database::fetchAll(
            "SELECT rc.*, CONCAT(u.first_name,' ',u.last_name) as user_name,
                    u.email, u.role as role,
                    (SELECT COUNT(*) FROM referral_signups rs WHERE rs.referrer_user_id=rc.user_id) as signup_count,
                    (SELECT COALESCE(SUM(commission_amount),0) FROM referral_commissions rcomm
                     JOIN affiliates af ON af.id=rcomm.referrer_aff_id
                     WHERE af.user_id=rc.user_id AND rcomm.status='approved') as total_earned
             FROM referral_codes rc
             JOIN users u ON u.id=rc.user_id
             ORDER BY rc.role, signup_count DESC"
        );
        echo json_encode(['success' => true, 'data' => ['codes' => $codes]]);
        exit;
    }

    if ($action === 'approve_commission') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];
        $id = (int)($data['commission_id'] ?? 0);

        if ($id > 0) {
            $comm = Database::fetchOne("SELECT * FROM referral_commissions WHERE id=? AND status='pending'", [$id]);
            if ($comm) {
                Database::update('referral_commissions', ['status'=>'approved'], 'id=?', [$id]);
                Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?",
                    [$comm['commission_amount'], $comm['referrer_aff_id']]);
                echo json_encode(['success' => true, 'message' => 'Commission approved and credited.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Pending commission not found.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid commission ID.']);
        }
        exit;
    }

    if ($action === 'reject_commission') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];
        $id = (int)($data['commission_id'] ?? 0);

        if ($id > 0) {
            Database::update('referral_commissions', ['status'=>'rejected'], 'id=?', [$id]);
            echo json_encode(['success' => true, 'message' => 'Commission rejected.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid commission ID.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
