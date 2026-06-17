<?php
header('Content-Type: application/json');
Auth::check('affiliate_manager');
ManagerPermissions::ensureSchema();

$action = Helpers::get('action') ?: 'stats';
$mgrUserId = Auth::id();
$managerAffIds = Auth::managerAffiliateIds();

if (empty($managerAffIds)) {
    $mgrUserId = Auth::id();
    $mgrRow = Database::fetchOne("SELECT am.id, am.balance FROM affiliate_managers am WHERE am.user_id=?", [$mgrUserId]);
    $commBalance = (float)($mgrRow['balance'] ?? 0);
    $mgrId = $mgrRow['id'] ?? 0;
    $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$mgrUserId])['c'] ?? 0);
    $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$mgrId])['c'] ?? 0);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_affiliates' => 0,
            'commission_balance' => $commBalance,
            'header_counts' => [
                'unread_news' => 0,
                'unread_notifs' => $unreadNotifs,
                'unread_alerts' => 0,
                'unread_chats' => $unreadChats
            ],
            'fraud_score_average' => 0,
            'fraud_score_counts' => ['high' => 0, 'medium' => 0, 'low' => 0],
            'stats' => null,
            'trend' => null,
            'filters' => null
        ]
    ]);
    exit;
}

$from = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
$to = Helpers::get('to') ?: date('Y-m-d');
$offerId = (int)(Helpers::get('offer_id') ?? 0);
$affId = (int)(Helpers::get('affiliate_id') ?? 0);
$country = trim(Helpers::get('country') ?? '');
$device = trim(Helpers::get('device') ?? '');

function adminStatsWhere($from, $to, $offerId, $affId, $managerAffIds) {
    $w = ['sd.stat_date BETWEEN ? AND ?'];
    $p = [$from, $to];
    if ($offerId) { $w[] = 'sd.offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'sd.affiliate_id = ?'; $p[] = $affId; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "sd.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

function adminConvWhere($from, $to, $offerId, $affId, $country, $managerAffIds) {
    $w = ['c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
    $p = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $w[] = 'c.offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'c.affiliate_id = ?'; $p[] = $affId; }
    if ($country) { $w[] = 'c.country = ?'; $p[] = $country; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "c.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

[$statsW, $statsP] = adminStatsWhere($from, $to, $offerId, $affId, $managerAffIds);
[$convW, $convP] = adminConvWhere($from, $to, $offerId, $affId, $country, $managerAffIds);

$days = max(1, (int)((strtotime($to) - strtotime($from)) / 86400) + 1);
$prevFrom = date('Y-m-d', strtotime($from) - $days * 86400);
$prevTo = date('Y-m-d', strtotime($from) - 86400);
[$prevStatsW, $prevStatsP] = adminStatsWhere($prevFrom, $prevTo, $offerId, $affId, $managerAffIds);

if ($action === 'stats') {
    $cur = Database::fetchOne("SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv FROM stats_daily sd WHERE $statsW", $statsP) ?? [];
    $prev = Database::fetchOne("SELECT SUM(clicks) as c, SUM(conversions) as cv FROM stats_daily sd WHERE $prevStatsW", $prevStatsP) ?? [];

    $clicks = (int)($cur['c'] ?? 0);
    $unique = (int)($cur['u'] ?? 0);
    $conv = (int)($cur['cv'] ?? 0);
    $cr = $clicks > 0 ? round($conv / $clicks * 100, 2) : 0;

    $pClicks = (int)($prev['c'] ?? 0);
    $pConv = (int)($prev['cv'] ?? 0);

    // Fraud Conversion
    $fcCur = Database::fetchOne(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
         FROM conversions c WHERE $convW", $convP
    );
    $fraudConvCur = (int)($fcCur['fraud'] ?? 0);
    $totalConvForPct = (int)($fcCur['total'] ?? 0);

    [$prevConvW, $prevConvP] = adminConvWhere($prevFrom, $prevTo, $offerId, $affId, $country, $managerAffIds);
    $fcPrev = Database::fetchOne(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
         FROM conversions c WHERE $prevConvW", $prevConvP
    );
    $fraudConvPrev = (int)($fcPrev['fraud'] ?? 0);
    $totalConvPrevForPct = (int)($fcPrev['total'] ?? 0);

    $fraudConvPct = $totalConvForPct > 0 ? round($fraudConvCur / $totalConvForPct * 100, 2) : 0;
    $fraudConvPctPrev = $totalConvPrevForPct > 0 ? round($fraudConvPrev / $totalConvPrevForPct * 100, 2) : 0;

    // Fraud Score Average (IPQS real-time from conversions + fraud_logs)
    $fraudScoreAgg = 0;
    try {
        $in30 = implode(',', array_fill(0, count($managerAffIds), '?'));
        $since30 = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $ipqsRows = Database::fetchAll(
            "SELECT fl.fraud_score FROM conversions cv JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in30) AND cv.converted_at >= ? AND cv.is_hidden = 0 AND fl.fraud_score IS NOT NULL",
            array_merge($managerAffIds, [$since30])
        );
        if (!empty($ipqsRows)) {
            $ipqsSum = 0;
            foreach ($ipqsRows as $_r) $ipqsSum += (int)$_r['fraud_score'];
            $fraudScoreAgg = (int)round($ipqsSum / count($ipqsRows));
        } else {
            $sum = 0;
            foreach ($managerAffIds as $_aid) $sum += FraudScore::forAffiliate((int)$_aid);
            $fraudScoreAgg = (int)round($sum / max(1, count($managerAffIds)));
        }
    } catch (\Throwable $e) {
        $fraudScoreAgg = 0;
    }

    $mgrUserId = Auth::id();
    $mgrRow = Database::fetchOne("SELECT am.id, am.balance FROM affiliate_managers am WHERE am.user_id=?", [$mgrUserId]);
    $commBalance = (float)($mgrRow['balance'] ?? 0);
    $mgrId = $mgrRow['id'] ?? 0;

    $unreadNews = 0;
    $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$mgrUserId])['c'] ?? 0);
    $unreadAlerts = 0;
    if (!empty($managerAffIds)) {
        try {
            $inAff = implode(',', array_fill(0, count($managerAffIds), '?'));
            $unreadAlerts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM fraud_alerts WHERE affiliate_id IN ($inAff) AND is_read=0 AND resolved_at IS NULL", $managerAffIds)['c'] ?? 0);
        } catch (\Throwable $e) {
            $unreadAlerts = 0;
        }
    }
    $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$mgrId])['c'] ?? 0);

    $header_counts = [
        'unread_news' => $unreadNews,
        'unread_notifs' => $unreadNotifs,
        'unread_alerts' => $unreadAlerts,
        'unread_chats' => $unreadChats
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'total_affiliates' => count($managerAffIds),
            'commission_balance' => $commBalance,
            'header_counts' => $header_counts,
            'clicks' => $clicks,
            'unique' => $unique,
            'conv' => $conv,
            'cr' => $cr,
            'fraud_conv' => $fraudConvCur,
            'fraud_conv_pct' => $fraudConvPct,
            'fraud_score_average' => $fraudScoreAgg,
            'trend' => [
                'clicks' => $pClicks == 0 ? ($clicks > 0 ? 100 : 0) : round(($clicks - $pClicks) / $pClicks * 100, 1),
                'conv' => $pConv == 0 ? ($conv > 0 ? 100 : 0) : round(($conv - $pConv) / $pConv * 100, 1),
                'fraud_conv_pct' => round($fraudConvPct - $fraudConvPctPrev, 1)
            ]
        ]
    ]);
    exit;
}

if ($action === 'trend') {
    $rows = Database::fetchAll(
        "SELECT sd.stat_date as d, SUM(sd.clicks) as c, SUM(sd.unique_clicks) as u, SUM(sd.conversions) as cv
         FROM stats_daily sd WHERE $statsW GROUP BY sd.stat_date ORDER BY sd.stat_date",
        $statsP
    );
    $map = [];
    foreach ($rows as $r) $map[$r['d']] = $r;

    $fraudByDay = [];
    $fraudRows = Database::fetchAll(
        "SELECT DATE(c.converted_at) as d, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
         FROM conversions c WHERE $convW GROUP BY DATE(c.converted_at)",
        $convP
    );
    foreach ($fraudRows as $fr) $fraudByDay[$fr['d']] = (int)($fr['fraud_cv'] ?? 0);

    $labels = $clicks_data = $conv_data = $fraud_data = [];
    $cur = strtotime($from);
    $end = strtotime($to);
    while ($cur <= $end) {
        $d = date('Y-m-d', $cur);
        $r = $map[$d] ?? [];
        $labels[] = date('M j', $cur);
        $clicks_data[] = (int)($r['c'] ?? 0);
        $conv_data[] = (int)($r['cv'] ?? 0);
        $fraud_data[] = (int)($fraudByDay[$d] ?? 0);
        $cur += 86400;
    }
    echo json_encode([
        'success' => true,
        'data' => compact('labels', 'clicks_data', 'conv_data', 'fraud_data')
    ]);
    exit;
}

if ($action === 'filters') {
    $offersQ = Database::fetchAll(
        "SELECT DISTINCT o.id, o.name FROM stats_daily sd JOIN offers o ON o.id=sd.offer_id
         WHERE sd.affiliate_id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ") ORDER BY o.name",
        $managerAffIds
    );
    $affsQ = Database::fetchAll(
        "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label
         FROM affiliates af JOIN users u ON u.id=af.user_id
         WHERE af.id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ") ORDER BY u.first_name",
        $managerAffIds
    );
    $countriesQ = Database::fetchAll(
        "SELECT DISTINCT country FROM clicks WHERE affiliate_id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ") AND country != '' ORDER BY country",
        $managerAffIds
    );
    echo json_encode([
        'success' => true,
        'data' => [
            'offers' => $offersQ,
            'affiliates' => $affsQ,
            'countries' => array_column($countriesQ, 'country'),
            'devices' => ['Desktop', 'Mobile', 'Tablet']
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
