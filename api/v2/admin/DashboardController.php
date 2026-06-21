<?php

try {
    Database::query("ALTER TABLE stats_daily ADD COLUMN revenue DECIMAL(12,4) DEFAULT 0.0000");
    Database::query("ALTER TABLE stats_daily ADD COLUMN payout DECIMAL(12,4) DEFAULT 0.0000");
} catch(\Throwable $e) {}
try {
    Database::query("ALTER TABLE conversions ADD COLUMN fraud_score INT DEFAULT 0");
} catch(\Throwable $e) {}

header('Content-Type: application/json');

// Ensure only admins can access this endpoint
Auth::check('admin');

try {
    // We will hardcode 30 days for the mobile app for now to keep things simple
    $from = date('Y-m-d', strtotime('-29 days'));
    $to = date('Y-m-d');
    
    // We can also allow them to be passed as params if the mobile app ever wants to add date pickers
    if (isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) {
        $from = $_GET['from'];
    }
    if (isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])) {
        $to = $_GET['to'];
    }

    $statsW = 'sd.stat_date BETWEEN ? AND ?';
    $statsP = [$from, $to];

    $clickW = 'clicked_at BETWEEN ? AND ?';
    $clickP = [$from . ' 00:00:00', $to . ' 23:59:59'];

    $convW = 'c.converted_at BETWEEN ? AND ? AND COALESCE(c.is_hidden,0)=0';
    $convP = [$from . ' 00:00:00', $to . ' 23:59:59'];

    // 1. KPI Totals
    $cur = Database::fetchOne("SELECT SUM(clicks) as c, SUM(unique_clicks) as u, SUM(conversions) as cv, SUM(payout) as p, SUM(revenue) as r FROM stats_daily sd WHERE $statsW", $statsP) ?? [];
    
    $clicks  = (int)($cur['c']  ?? 0);
    $unique  = (int)($cur['u']  ?? 0);
    $conv    = (int)($cur['cv'] ?? 0);
    $payout  = round((float)($cur['p'] ?? 0), 2);
    $revenue = round((float)($cur['r'] ?? 0), 2);
    $profit  = round($revenue - $payout, 2);
    $cr      = $clicks > 0 ? round($conv / $clicks * 100, 2) : 0;

    $fraud = Database::fetchOne("SELECT COUNT(*) as cnt FROM clicks WHERE DATE(clicked_at) BETWEEN ? AND ? AND is_fraud=1", [$from, $to]);
    $fraudClicks = (int)($fraud['cnt'] ?? 0);

    $fcCur = Database::fetchOne(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) AS fraud
         FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW", $convP
    );
    $fraudConvCur     = (int)($fcCur['fraud'] ?? 0);
    $totalConvForPct  = (int)($fcCur['total'] ?? 0);
    $fraudConvPct      = $totalConvForPct > 0 ? round($fraudConvCur / $totalConvForPct * 100, 2) : 0;

    $kpis = [
        'clicks' => $clicks,
        'unique_clicks' => $unique,
        'conversions' => $conv,
        'payout' => $payout,
        'revenue' => $revenue,
        'profit' => $profit,
        'cr' => $cr,
        'fraud_clicks' => $fraudClicks,
        'fraud_conv' => $fraudConvCur,
        'fraud_conv_pct' => $fraudConvPct,
    ];

    // 2. Trend Data
    $labels = []; $clicks_data = []; $conv_data = []; $revenue_data = []; $payout_data = []; $fraud_data = [];

    if ($from === $to) {
        // Hourly breakdown for single day
        $appTz = Config::get('config', 'app.timezone') ?? 'UTC';
        $tzApp = new DateTimeZone($appTz);
        $_reqTz = 'UTC'; // Default or from request if needed
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
                "SELECT HOUR(DATE_ADD(c.converted_at, INTERVAL ? SECOND)) as h, COUNT(*) as cv, SUM(c.payout) as p, SUM(c.revenue) as r
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
            $revenue_data[] = round((float)($cvr['r'] ?? 0), 2);
            $payout_data[]  = round((float)($cvr['p'] ?? 0), 2);
            $fraud_data[]   = $fraudByHour[$h] ?? 0;
        }
    } else {
        $trendRows = Database::fetchAll(
            "SELECT sd.stat_date as d, SUM(sd.clicks) as c, SUM(sd.unique_clicks) as u,
                    SUM(sd.conversions) as cv, SUM(sd.payout) as p, SUM(sd.revenue) as r
             FROM stats_daily sd WHERE $statsW GROUP BY sd.stat_date ORDER BY sd.stat_date",
            $statsP
        );
        $map = []; foreach ($trendRows as $r) $map[$r['d']] = $r;
        
        $fraudByDay = [];
        $fraudRows = Database::fetchAll("SELECT DATE(c.converted_at) as d, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY DATE(c.converted_at)", $convP);
        foreach ($fraudRows as $fr) $fraudByDay[$fr['d']] = (int)($fr['fraud_cv'] ?? 0);

        $curDate = strtotime($from);
        $endDate = strtotime($to);
        while ($curDate <= $endDate) {
            $d = date('Y-m-d', $curDate);
            $r = $map[$d] ?? [];
            $labels[]       = date('M j', $curDate);
            $clicks_data[]  = (int)($r['c']  ?? 0);
            $conv_data[]    = (int)($r['cv'] ?? 0);
            $revenue_data[] = round((float)($r['r'] ?? 0), 2);
            $payout_data[]  = round((float)($r['p'] ?? 0), 2);
            $fraud_data[]   = (int)($fraudByDay[$d] ?? 0);
            $curDate += 86400;
        }
    }
    
    $trend = [
        'labels' => $labels,
        'clicks' => $clicks_data,
        'conversions' => $conv_data,
        'revenue' => $revenue_data,
        'payout' => $payout_data,
        'fraud' => $fraud_data
    ];

    // 3. Conversion Status
    $statusRows = Database::fetchAll("SELECT c.status, COUNT(*) as cnt FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY c.status ORDER BY cnt DESC", $convP);
    $status_data = [];
    foreach($statusRows as $r) {
        $status_data[] = ['status' => $r['status'], 'count' => (int)$r['cnt']];
    }

    // 4. Countries
    $countryRows = Database::fetchAll("SELECT country, COUNT(*) as clicks, SUM(is_unique) as uniq FROM clicks WHERE $clickW AND country != '' AND country IS NOT NULL GROUP BY country ORDER BY clicks DESC LIMIT 15", $clickP);
    $cvCountryRows = Database::fetchAll("SELECT ck.country as country, COUNT(*) as conv FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW AND ck.country != '' GROUP BY ck.country", $convP);
    $cvMap = []; foreach ($cvCountryRows as $r) $cvMap[$r['country']] = (int)$r['conv'];
    $countries = [];
    foreach ($countryRows as $r) {
        $c = $r['country'];
        $cvCount = $cvMap[$c] ?? 0;
        $countries[] = ['country' => $c, 'clicks' => (int)$r['clicks'], 'conversions' => $cvCount, 'cr' => (int)$r['clicks'] > 0 ? round($cvCount / (int)$r['clicks'] * 100, 2) : 0];
    }

    // 5. Devices
    $deviceRows = Database::fetchAll("SELECT COALESCE(NULLIF(device_type,''),'Unknown') as label, COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY device_type ORDER BY cnt DESC", $clickP);
    $devices = []; foreach($deviceRows as $r) $devices[] = ['device' => $r['label'], 'clicks' => (int)$r['cnt']];

    // 6. Browsers
    $browserRows = Database::fetchAll("SELECT COALESCE(NULLIF(browser,''),'Unknown') as label, COUNT(*) as cnt FROM clicks WHERE $clickW GROUP BY browser ORDER BY cnt DESC LIMIT 8", $clickP);
    $browsers = []; foreach($browserRows as $r) $browsers[] = ['browser' => $r['label'], 'clicks' => (int)$r['cnt']];

    // 7. Top Offers
    $offerRows = Database::fetchAll("SELECT o.name, o.id, SUM(sd.clicks) as clicks, SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue FROM stats_daily sd JOIN offers o ON o.id = sd.offer_id WHERE $statsW GROUP BY sd.offer_id ORDER BY payout DESC LIMIT 10", $statsP);
    $fraudOfferRows = Database::fetchAll("SELECT c.offer_id, SUM(CASE WHEN COALESCE(c.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud_cv, COUNT(*) AS total_cv FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id WHERE $convW GROUP BY c.offer_id", $convP);
    $fraudByOffer = []; foreach ($fraudOfferRows as $fr) $fraudByOffer[(int)$fr['offer_id']] = ['fraud' => (int)($fr['fraud_cv'] ?? 0), 'total' => (int)($fr['total_cv'] ?? 0)];
    $top_offers = [];
    foreach ($offerRows as $r) {
        $cl = (int)$r['clicks']; $cv = (int)$r['conv']; $oid = (int)$r['id'];
        $fbo = $fraudByOffer[$oid] ?? ['fraud' => 0, 'total' => 0];
        $fraudPct = $fbo['total'] > 0 ? round($fbo['fraud'] / $fbo['total'] * 100, 2) : 0;
        $top_offers[] = [
            'id' => $oid,
            'name' => $r['name'],
            'clicks' => $cl,
            'conversions' => $cv,
            'payout' => round((float)$r['payout'], 2),
            'revenue' => round((float)$r['revenue'], 2),
            'profit' => round((float)$r['revenue'] - (float)$r['payout'], 2),
            'cr' => $cl > 0 ? round($cv / $cl * 100, 2) : 0,
            'fraud_conv' => $fbo['fraud'],
            'fraud_conv_pct' => $fraudPct
        ];
    }

    // 8. Top Affiliates
    $affRows = Database::fetchAll("SELECT CONCAT(u.first_name,' ',u.last_name) as name, af.id as aff_id, SUM(sd.clicks) as clicks, SUM(sd.conversions) as conv, SUM(sd.payout) as payout, SUM(sd.revenue) as revenue FROM stats_daily sd JOIN affiliates af ON af.id = sd.affiliate_id JOIN users u ON u.id = af.user_id WHERE $statsW GROUP BY sd.affiliate_id ORDER BY payout DESC LIMIT 10", $statsP);
    $top_affiliates = [];
    foreach ($affRows as $r) {
        $cl = (int)$r['clicks']; $cv = (int)$r['conv'];
        $top_affiliates[] = [
            'id' => (int)$r['aff_id'],
            'name' => $r['name'],
            'clicks' => $cl,
            'conversions' => $cv,
            'payout' => round((float)$r['payout'], 2),
            'revenue' => round((float)$r['revenue'], 2),
            'profit' => round((float)$r['revenue'] - (float)$r['payout'], 2),
            'cr' => $cl > 0 ? round($cv / $cl * 100, 2) : 0
        ];
    }

    // 9. Recent Conversions
    $recentConversions = [];
    try {
        $recentRows = Database::fetchAll("SELECT c.id, c.status, c.payout, c.revenue, c.converted_at, ck.country as country, ck.device_type as device_type, o.name as offer_name, CONCAT(u.first_name,' ',u.last_name) as aff_name FROM conversions c LEFT JOIN clicks ck ON ck.click_id = c.click_id LEFT JOIN offers o ON o.id = c.offer_id LEFT JOIN affiliates af ON af.id = c.affiliate_id LEFT JOIN users u ON u.id = af.user_id WHERE $convW ORDER BY c.converted_at DESC LIMIT 15", $convP);
        foreach ($recentRows as $r) {
            $recentConversions[] = [
                'id' => $r['id'],
                'status' => $r['status'],
                'payout' => round((float)$r['payout'], 2),
                'revenue' => round((float)$r['revenue'], 2),
                'converted_at' => $r['converted_at'],
                'country' => $r['country'] ?: 'Unknown',
                'device_type' => $r['device_type'] ?: 'Unknown',
                'offer_name' => $r['offer_name'],
                'affiliate_name' => $r['aff_name']
            ];
        }
    } catch (Exception $e) {}

    // 10. Summary Cards (Active totals)
    $totAff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='active'");
    $pendAff = Database::fetchOne("SELECT COUNT(*) as cnt FROM affiliates a JOIN users u ON u.id=a.user_id WHERE u.status='pending'");
    $totOff  = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE status='active'");
    $totAdv  = Database::fetchOne("SELECT COUNT(*) as cnt FROM advertisers a JOIN users u ON u.id=a.user_id WHERE u.status='active'");

    $summary = [
        'total_affiliates' => (int)($totAff['cnt'] ?? 0),
        'pending_affiliates' => (int)($pendAff['cnt'] ?? 0),
        'total_offers' => (int)($totOff['cnt'] ?? 0),
        'total_advertisers' => (int)($totAdv['cnt'] ?? 0)
    ];

    $header_counts = [
        'unread_news' => 0,
        'unread_notifs' => 0,
        'unread_alerts' => 0,
        'unread_chats' => 0,
        'pending_approvals' => 0
    ];

    try {
        $adminUserId = Auth::id();
        $unreadNotifs = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0", [$adminUserId])['c'] ?? 0);
        $unreadBroadcast = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM notifications n WHERE n.user_id IS NULL AND n.target_role IN ('admin', 'all') AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?)", [$adminUserId])['c'] ?? 0);
        $unreadAlerts = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM fraud_alerts WHERE is_read=0 AND resolved_at IS NULL")['c'] ?? 0);
        
        try { Database::query("ALTER TABLE support_messages ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}
        try { Database::query("ALTER TABLE support_messages ADD COLUMN owner_type VARCHAR(20) NOT NULL DEFAULT 'affiliate'"); } catch(\Throwable $e) {}
        try { Database::query("ALTER TABLE support_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}
        
        $unreadChats = (int)(Database::fetchOne("SELECT COUNT(*) c FROM support_messages WHERE owner_type IN ('affiliate', 'advertiser') AND sender_role != 'admin' AND is_read=0 AND is_deleted=0")['c'] ?? 0);
        $pendingApprovals = (int)(Database::fetchOne("SELECT COUNT(*) c FROM offer_approvals WHERE status='pending'")['c'] ?? 0);

        $header_counts['unread_notifs'] = $unreadNotifs + $unreadBroadcast;
        $header_counts['unread_alerts'] = $unreadAlerts;
        $header_counts['unread_chats'] = $unreadChats;
        $header_counts['pending_approvals'] = $pendingApprovals;
    } catch (\Throwable $e) {
        error_log("Dashboard Header Counts Error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'header_counts' => $header_counts,
            'summary' => $summary,
            'kpis' => $kpis,
            'trend' => $trend,
            'conversion_status' => $status_data,
            'countries' => $countries,
            'devices' => $devices,
            'browsers' => $browsers,
            'top_offers' => $top_offers,
            'top_affiliates' => $top_affiliates,
            'recent_conversions' => $recentConversions
        ]
    ]);

} catch (Exception $e) {
    error_log("Admin Dashboard 500 Error: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
