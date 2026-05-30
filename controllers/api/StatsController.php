<?php
header('Content-Type: application/json');
if (!Auth::id()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$role  = Auth::role();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

if ($role === 'affiliate') {
    $affId = Auth::affiliateId();
    $td = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks, SUM(conversions) as conv,
                SUM(approved) as approved, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id=? AND stat_date=?",
        [$affId, $today]
    ) ?? [];
    $mo = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id=? AND stat_date>=?",
        [$affId, $monthStart]
    ) ?? [];
    $bal          = Database::fetchOne("SELECT balance FROM affiliates WHERE id=?", [$affId]);
    $totApproved  = Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='approved'", [$affId]);
    $totPending   = Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='pending'",  [$affId]);
    $moApproved   = Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='approved' AND converted_at>=?", [$affId, $monthStart]);
    echo json_encode([
        'clicks_today'   => (int)($td['clicks']   ?? 0),
        'uclicks_today'  => (int)($td['uclicks']  ?? 0),
        'conv_today'     => (int)($td['conv']      ?? 0),
        'approved_today' => (int)($td['approved']  ?? 0),
        'payout_today'   => round((float)($td['payout']  ?? 0), 2),
        'clicks_month'   => (int)($mo['clicks']   ?? 0),
        'conv_month'     => (int)($mo['conv']      ?? 0),
        'payout_month'   => round((float)($mo['payout']  ?? 0), 2),
        'balance'        => round((float)($bal['balance']     ?? 0), 2),
        'approved_total' => round((float)($totApproved['t']  ?? 0), 2),
        'pending_total'  => round((float)($totPending['t']   ?? 0), 2),
        'month_approved' => round((float)($moApproved['t']   ?? 0), 2),
    ]);
    exit;
}

if ($role === 'affiliate_manager') {
    $affIds = Auth::managerAffiliateIds();
    $mgrRow = Database::fetchOne("SELECT am.id, am.balance FROM affiliate_managers am WHERE am.user_id=?", [Auth::id()]);
    // Commission data read from manager_commissions + balance column (authoritative)
    $commBalance = (float)($mgrRow['balance'] ?? 0);
    $commEarned  = 0.0;
    $commPaid    = 0.0;
    if ($mgrRow) {
        $commAgg = Database::fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN status!='reversed' THEN commission_amount ELSE 0 END),0) AS earned,
                COALESCE(SUM(CASE WHEN status='paid'      THEN commission_amount ELSE 0 END),0) AS paid
             FROM manager_commissions WHERE manager_id=?",
            [$mgrRow['id']]
        );
        if ($commAgg) {
            $commEarned = round((float)$commAgg['earned'], 2);
            $commPaid   = round((float)$commAgg['paid'],   2);
        }
    }
    if (empty($affIds)) {
        echo json_encode(['clicks_today'=>0,'conv_today'=>0,'payout_today'=>0,'clicks_month'=>0,'conv_month'=>0,'payout_month'=>0,
            'commission_earned'=>$commEarned,'commission_paid'=>$commPaid,'commission_balance'=>round($commBalance,2)]);
        exit;
    }
    $in = implode(',', array_fill(0, count($affIds), '?'));
    $td = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
                SUM(conversions) as conv, SUM(approved) as approved, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id IN ($in) AND stat_date=?",
        array_merge($affIds, [$today])
    ) ?? [];
    $mo = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id IN ($in) AND stat_date>=?",
        array_merge($affIds, [$monthStart])
    ) ?? [];
    echo json_encode([
        'clicks_today'       => (int)($td['clicks']  ?? 0),
        'uclicks_today'      => (int)($td['uclicks'] ?? 0),
        'conv_today'         => (int)($td['conv']     ?? 0),
        'approved_today'     => (int)($td['approved'] ?? 0),
        'payout_today'       => round((float)($td['payout']  ?? 0), 2),
        'clicks_month'       => (int)($mo['clicks']  ?? 0),
        'conv_month'         => (int)($mo['conv']     ?? 0),
        'payout_month'       => round((float)($mo['payout']  ?? 0), 2),
        'commission_earned'  => $commEarned,
        'commission_paid'    => $commPaid,
        'commission_balance' => round($commBalance, 2),
    ]);
    exit;
}

if ($role === 'admin') {
    $td = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(unique_clicks) as uclicks,
                SUM(conversions) as conv, SUM(approved) as approved,
                SUM(revenue) as revenue, SUM(payout) as payout
         FROM stats_daily WHERE stat_date=?", [$today]
    ) ?? [];
    $fraud   = Database::fetchOne("SELECT COUNT(*) as cnt FROM clicks WHERE DATE(clicked_at)=? AND is_fraud=1", [$today]);
    $totAff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='active'");
    $pendAff = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='pending'");
    $totOff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE status='active'");
    echo json_encode([
        'clicks_today'      => (int)($td['clicks']    ?? 0),
        'uclicks_today'     => (int)($td['uclicks']   ?? 0),
        'conv_today'        => (int)($td['conv']       ?? 0),
        'approved_today'    => (int)($td['approved']   ?? 0),
        'revenue_today'     => round((float)($td['revenue']   ?? 0), 2),
        'payout_today'      => round((float)($td['payout']    ?? 0), 2),
        'fraud_today'       => (int)($fraud['cnt']    ?? 0),
        'total_affiliates'  => (int)($totAff['cnt']   ?? 0),
        'pending_affiliates'=> (int)($pendAff['cnt']  ?? 0),
        'total_offers'      => (int)($totOff['cnt']   ?? 0),
    ]);
    exit;
}

if ($role === 'advertiser') {
    $advId = Database::fetchOne("SELECT id FROM advertisers WHERE user_id=?", [Auth::id()]);
    $advId = $advId ? (int)$advId['id'] : 0;
    $td = Database::fetchOne(
        "SELECT SUM(c.clicks) as clicks, SUM(c.conversions) as conv, SUM(c.revenue) as revenue
         FROM stats_daily c
         JOIN offers o ON o.id=c.offer_id
         WHERE o.advertiser_id=? AND c.stat_date=?",
        [$advId, $today]
    ) ?? [];
    $activeOffers = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE advertiser_id=? AND status='active'", [$advId]);
    echo json_encode([
        'clicks_today'  => (int)($td['clicks']  ?? 0),
        'conv_today'    => (int)($td['conv']     ?? 0),
        'revenue_today' => round((float)($td['revenue'] ?? 0), 2),
        'active_offers' => (int)($activeOffers['cnt'] ?? 0),
    ]);
    exit;
}

echo json_encode(['error'=>'Unknown role']);
