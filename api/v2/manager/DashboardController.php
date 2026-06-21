<?php

try {
    Database::query("ALTER TABLE stats_daily ADD COLUMN revenue DECIMAL(12,4) DEFAULT 0.0000");
    Database::query("ALTER TABLE stats_daily ADD COLUMN payout DECIMAL(12,4) DEFAULT 0.0000");
} catch(\Throwable $e) {}
try {
    Database::query("ALTER TABLE conversions ADD COLUMN fraud_score INT DEFAULT 0");
} catch(\Throwable $e) {}

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
    $unreadAdminChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$mgrId])['c'] ?? 0);
    $unreadChats = $unreadAdminChats;

    echo json_encode([
        'success' => true,
        'data' => [
            'total_affiliates' => 0,
            'commission_balance' => $commBalance,
            'header_counts' => [
                'unread_news' => 0,
                'unread_notifs' => $unreadNotifs,
                'unread_alerts' => 0,
                'unread_chats' => $unreadChats,
                'pending_approvals' => 0
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
    if ($country) { $w[] = 'ck.country = ?'; $p[] = $country; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "c.affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

function adminClicksWhere($from, $to, $offerId, $affId, $country, $device, $managerAffIds) {
    $w = ['clicked_at BETWEEN ? AND ?'];
    $p = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $w[] = 'offer_id = ?'; $p[] = $offerId; }
    if ($affId)   { $w[] = 'affiliate_id = ?'; $p[] = $affId; }
    if ($country) { $w[] = 'country = ?'; $p[] = $country; }
    if ($device)  { $w[] = 'device_type = ?'; $p[] = $device; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $w[] = "affiliate_id IN ($in)";
        $p   = array_merge($p, $managerAffIds);
    }
    return [implode(' AND ', $w), $p];
}

[$statsW, $statsP] = adminStatsWhere($from, $to, $offerId, $affId, $managerAffIds);
[$clickW, $clickP] = adminClicksWhere($from, $to, $offerId, $affId, $country, $device, $managerAffIds);
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
         FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW", $convP
    );
    $fraudConvCur = (int)($fcCur['fraud'] ?? 0);
    $totalConvForPct = (int)($fcCur['total'] ?? 0);

    [$prevConvW, $prevConvP] = adminConvWhere($prevFrom, $prevTo, $offerId, $affId, $country, $managerAffIds);
    $fcPrev = Database::fetchOne(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
         FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $prevConvW", $prevConvP
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
    $unreadAdminChats = 0;
    try {
        $unreadAdminChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$mgrId])['c'] ?? 0);
    } catch (\Throwable $e) {}

    $unreadAffiliateChats = 0;
    $pendingApprovals = 0;
    if (!empty($managerAffIds)) {
        $inAff = implode(',', array_fill(0, count($managerAffIds), '?'));
        try {
            $unreadAffiliateChats = (int)(Database::fetchOne(
                "SELECT COUNT(*) c FROM support_messages sm JOIN support_conversations sc ON sc.id = sm.conversation_id WHERE sc.affiliate_id IN ($inAff) AND sm.sender_role='affiliate' AND sm.is_read=0",
                $managerAffIds
            )['c'] ?? 0);
        } catch (\Throwable $e) {}

        try {
            $pendingApprovals = (int)(Database::fetchOne(
                "SELECT COUNT(*) c FROM affiliate_offers WHERE affiliate_id IN ($inAff) AND status='pending'",
                $managerAffIds
            )['c'] ?? 0);
        } catch (\Throwable $e) {}
    }
    $unreadChats = $unreadAdminChats + $unreadAffiliateChats;

    $header_counts = [
        'unread_news' => $unreadNews,
        'unread_notifs' => $unreadNotifs,
        'unread_alerts' => $unreadAlerts,
        'unread_chats' => $unreadChats,
        'pending_approvals' => $pendingApprovals
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
    $labels = $clicks_data = $conv_data = $fraud_data = [];

    if ($from === $to) {
        $appTz = Config::get('config', 'app.timezone') ?? 'UTC';
        $tzApp = new DateTimeZone($appTz);
        $_reqTz = 'UTC';
        $tzReq = new DateTimeZone($_reqTz);

        $startDt = new DateTime("$from 00:00:00", $tzReq);
        $startDt->setTimezone($tzApp);
        $appStart = $startDt->format('Y-m-d H:i:s');

        $endDt = new DateTime("$from 23:59:59", $tzReq);
        $endDt->setTimezone($tzApp);
        $appEnd = $endDt->format('Y-m-d H:i:s');

        $dtNow = new DateTime("now", $tzReq);
        $offsetSeconds = $tzReq->getOffset($dtNow) - $tzApp->getOffset($dtNow);

        $clRows = [];
        try {
            $clRows = Database::fetchAll(
                "SELECT HOUR(DATE_ADD(clicked_at, INTERVAL ? SECOND)) as h, COUNT(*) as c
                 FROM clicks WHERE $clickW GROUP BY h",
                array_merge([$offsetSeconds], $clickP)
            );
        } catch (\Throwable $_e) {}

        $cvRows = [];
        try {
            $cvRows = Database::fetchAll(
                "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv
                 FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY h",
                array_merge([$offsetSeconds], $convP)
            );
        } catch (\Throwable $_e) {}

        $fraudByHour = [];
        try {
            $fraudRows = Database::fetchAll(
                "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h,
                        SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv
                 FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY h",
                array_merge([$offsetSeconds], $convP)
            );
            foreach ($fraudRows as $fr) $fraudByHour[(int)$fr['h']] = (int)$fr['fraud_cv'];
        } catch (\Throwable $_e) {}

        $cMap = []; foreach ($clRows as $r) $cMap[(int)$r['h']] = $r;
        $cvMap = []; foreach ($cvRows as $r) $cvMap[(int)$r['h']] = $r;

        for ($h = 0; $h < 24; $h++) {
            $cr  = $cMap[$h]  ?? [];
            $cvr = $cvMap[$h] ?? [];
            $labels[]       = sprintf('%02d:00', $h);
            $clicks_data[]  = (int)($cr['c']   ?? 0);
            $conv_data[]    = (int)($cvr['cv'] ?? 0);
            $fraud_data[]   = $fraudByHour[$h] ?? 0;
        }
    } else {
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
             FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY DATE(c.converted_at)",
            $convP
        );
        foreach ($fraudRows as $fr) $fraudByDay[$fr['d']] = (int)($fr['fraud_cv'] ?? 0);

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

if ($action === 'extra') {
    $extra = [];

    // Hourly
    $today = date('Y-m-d');
    $hW    = 'DATE(clicked_at) = ?';
    $hP    = [$today];
    if ($offerId) { $hW .= ' AND offer_id = ?'; $hP[] = $offerId; }
    if ($affId)   { $hW .= ' AND affiliate_id = ?'; $hP[] = $affId; }
    if ($country) { $hW .= ' AND country = ?'; $hP[] = $country; }
    if ($device)  { $hW .= ' AND device_type = ?'; $hP[] = $device; }
    if (!empty($managerAffIds)) {
        $in = implode(',', array_fill(0, count($managerAffIds), '?'));
        $hW .= " AND affiliate_id IN ($in)";
        $hP  = array_merge($hP, $managerAffIds);
    }
    $rows   = Database::fetchAll("SELECT HOUR(clicked_at) as h, COUNT(*) as cnt FROM clicks WHERE $hW GROUP BY HOUR(clicked_at)", $hP);
    $hourly = array_fill(0, 24, 0);
    foreach ($rows as $r) $hourly[(int)$r['h']] = (int)$r['cnt'];
    $extra['hourly'] = [
        'labels' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
        'data' => array_values($hourly)
    ];

    // Conv Status
    $rows = Database::fetchAll("SELECT c.status, COUNT(*) as cnt FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY c.status ORDER BY cnt DESC", $convP);
    $extra['conv_status'] = [
        'labels' => [], 'data' => [], 'colors' => []
    ];
    $pieColors = ['approved' => '#10B981', 'pending' => '#F59E0B', 'rejected' => '#EF4444', 'chargebacked' => '#94A3B8'];
    foreach ($rows as $r) {
        if ((int)$r['cnt'] === 0) continue;
        $extra['conv_status']['labels'][] = $r['status'];
        $extra['conv_status']['data'][]   = (int)$r['cnt'];
        $extra['conv_status']['colors'][] = $pieColors[$r['status']] ?? '#64748B';
    }

    // Countries
    $clRows = Database::fetchAll("SELECT country, COUNT(*) as clicks, SUM(is_unique) as uniq FROM clicks WHERE $clickW AND country != '' AND country IS NOT NULL GROUP BY country ORDER BY clicks DESC LIMIT 15", $clickP);
    $cvRows = Database::fetchAll("SELECT ck.country as country, COUNT(*) as conv FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW AND ck.country != '' GROUP BY ck.country", $convP);
    $cvMap = [];
    foreach ($cvRows as $r) $cvMap[$r['country']] = (int)$r['conv'];
    $out = [];
    foreach ($clRows as $r) {
        $out[] = ['country' => $r['country'], 'clicks' => (int)$r['clicks'], 'unique' => (int)$r['uniq'], 'conv' => $cvMap[$r['country']] ?? 0];
    }
    $extra['countries'] = $out;

    // Devices
    $rows = Database::fetchAll("SELECT COALESCE(NULLIF(device_type,''),'Unknown') as label, COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY device_type ORDER BY cnt DESC", $clickP);
    $extra['devices'] = ['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'cnt'))];

    // Browsers
    $rows = Database::fetchAll("SELECT COALESCE(NULLIF(browser,''),'Unknown') as label, COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY browser ORDER BY cnt DESC LIMIT 8", $clickP);
    $extra['browsers'] = ['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'cnt'))];

    // OS
    $rows = Database::fetchAll("SELECT COALESCE(NULLIF(os,''),'Unknown') as label, COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY os ORDER BY cnt DESC LIMIT 8", $clickP);
    $extra['os'] = ['labels' => array_column($rows, 'label'), 'data' => array_map('intval', array_column($rows, 'cnt'))];

    // Offers
    $rows = Database::fetchAll("SELECT o.name, o.id, SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks, SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue FROM stats_daily sd JOIN offers o ON o.id = sd.offer_id WHERE $statsW GROUP BY sd.offer_id ORDER BY payout DESC LIMIT 10", $statsP);
    $out = [];
    foreach ($rows as $r) {
        $cl = (int)$r['clicks'];
        $cv = (int)$r['conv'];
        $out[] = [
            'id' => (int)$r['id'], 'name' => $r['name'], 'clicks' => $cl, 'uclicks' => (int)$r['uclicks'],
            'conv' => $cv, 'payout' => round((float)$r['payout'], 2), 'cr' => $cl > 0 ? round($cv / $cl * 100, 2) : 0
        ];
    }
    $extra['offers'] = $out;

    // Affiliates
    $rows = Database::fetchAll("SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code, af.id as aff_id, SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks, SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue FROM stats_daily sd JOIN affiliates af ON af.id = sd.affiliate_id JOIN users u ON u.id = af.user_id WHERE $statsW GROUP BY sd.affiliate_id ORDER BY payout DESC LIMIT 10", $statsP);
    $out = [];
    foreach ($rows as $r) {
        $cl = (int)$r['clicks'];
        $cv = (int)$r['conv'];
        $out[] = [
            'id' => (int)$r['aff_id'], 'name' => $r['name'], 'code' => $r['affiliate_code'], 'clicks' => $cl,
            'uclicks' => (int)$r['uclicks'], 'conv' => $cv, 'payout' => round((float)$r['payout'], 2), 'cr' => $cl > 0 ? round($cv / $cl * 100, 2) : 0
        ];
    }
    $extra['affiliates'] = $out;

    // Recent Conversions
    $cW = ['c.converted_at BETWEEN ? AND ?', 'COALESCE(c.is_hidden,0)=0'];
    $cP = [$from . ' 00:00:00', $to . ' 23:59:59'];
    if ($offerId) { $cW[] = 'c.offer_id = ?'; $cP[] = $offerId; }
    if ($affId)   { $cW[] = 'c.affiliate_id = ?'; $cP[] = $affId; }
    if ($country) { $cW[] = 'ck.country = ?'; $cP[] = $country; }
    if (!empty($managerAffIds)) {
        $in  = implode(',', array_fill(0, count($managerAffIds), '?'));
        $cW[] = "c.affiliate_id IN ($in)";
        $cP   = array_merge($cP, $managerAffIds);
    }
    $whereStr = implode(' AND ', $cW);
    try {
        $extra['recent_convs'] = Database::fetchAll("SELECT c.id, c.status, c.payout, c.revenue, c.converted_at, ck.country as country, ck.device_type as device_type, o.name as offer_name, CONCAT(u.first_name,' ',u.last_name) as aff_name FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id LEFT JOIN offers o ON o.id = c.offer_id LEFT JOIN affiliates af ON af.id = c.affiliate_id LEFT JOIN users u ON u.id = af.user_id WHERE $whereStr ORDER BY c.converted_at DESC LIMIT 15", $cP) ?: [];
    } catch (Exception $e) { $extra['recent_convs'] = []; }

    // High Risk Fraud Conversions
    $_dashFraudRiskSql = FraudAutoNotify::highRiskWhereSql('cv');
    try {
        $extra['fraud_convs'] = Database::fetchAll("SELECT cv.id as conversion_id, cv.payout, cv.converted_at, ck.country, ck.ip_address, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name, o.name AS offer_name FROM conversions cv LEFT JOIN clicks ck ON ck.click_id = cv.click_id JOIN affiliates af ON af.id = cv.affiliate_id JOIN users u ON u.id = af.user_id LEFT JOIN offers o ON o.id = cv.offer_id WHERE cv.affiliate_id IN (" . implode(',', array_fill(0, count($managerAffIds), '?')) . ") AND $_dashFraudRiskSql ORDER BY cv.converted_at DESC LIMIT 5", $managerAffIds) ?: [];
    } catch (\Throwable $e) { $extra['fraud_convs'] = []; }

    echo json_encode(['success' => true, 'data' => $extra]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
