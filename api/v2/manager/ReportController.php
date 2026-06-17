<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('view_affiliate_reports');

try {
    $affIds = Auth::managerAffiliateIds();
    $action = $_GET['action'] ?? 'list';

    $hasAffiliates = !empty($affIds);
    $inSql    = $hasAffiliates ? implode(',', array_fill(0, count($affIds), '?')) : '0';
    $inParams = $hasAffiliates ? $affIds : [];

    if ($action === 'filters') {
        if (!$hasAffiliates) {
            echo json_encode([
                'success' => true,
                'offers' => [],
                'countries' => [],
                'affiliates' => []
            ]);
            exit;
        }

        // Offers used by managed affiliates
        $offers = Database::fetchAll(
            "SELECT DISTINCT o.id, o.name FROM offers o
             JOIN stats_daily sd ON sd.offer_id=o.id
             WHERE sd.affiliate_id IN ($inSql)
             ORDER BY o.name",
            $inParams
        );

        // Countries from managed affiliates' clicks
        $countryList = Database::fetchAll(
            "SELECT DISTINCT country FROM clicks WHERE affiliate_id IN ($inSql) AND country != '' ORDER BY country",
            $inParams
        );

        // Managed affiliates
        $affiliates = Database::fetchAll(
            "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code
             FROM affiliates af JOIN users u ON u.id=af.user_id
             WHERE af.id IN ($inSql) ORDER BY name",
            $inParams
        );

        echo json_encode([
            'success' => true,
            'offers' => $offers,
            'countries' => array_column($countryList, 'country'),
            'affiliates' => $affiliates
        ]);
        exit;
    }

    $tab         = $_GET['tab'] ?? 'day';
    $from        = $_GET['from'] ?? date('Y-m-01');
    $to          = $_GET['to']   ?? date('Y-m-d');
    $offerId     = (int)($_GET['offer_id'] ?? 0);
    $affId       = (int)($_GET['affiliate_id'] ?? 0);
    $country     = trim($_GET['country'] ?? '');
    $sub1        = trim($_GET['sub1'] ?? '');
    $limit       = 1000;
    
    $slId        = (int)($_GET['sl_id'] ?? 0);

    $dateFrom = date('Y-m-d 00:00:00', strtotime($from));
    $dateTo   = date('Y-m-d 23:59:59', strtotime($to));

    $activeAffIds = ($affId > 0 && in_array($affId, $affIds)) ? [$affId] : $affIds;
    if (empty($activeAffIds)) {
        // If they requested a specific affiliate they don't manage, or they manage no affiliates
        echo json_encode(['success' => true, 'tab' => $tab, 'rows' => [], 'totals' => [
            'clicks' => 0, 'uclicks' => 0, 'conv' => 0, 'approved' => 0, 'rejected' => 0, 'fraud' => 0, 'payout' => 0
        ]]);
        exit;
    }

    $activeInSql  = implode(',', array_fill(0, count($activeAffIds), '?'));
    
    $rows = [];
    $totals = null;

    $perfTabs = ['day','offer','country','sub','affiliate'];

    if (in_array($tab, $perfTabs)) {
        if (in_array($tab, ['day','offer','affiliate'])) {
            $where  = ["sd.stat_date BETWEEN ? AND ?", "sd.affiliate_id IN ($activeInSql)"];
            $params = array_merge([$from, $to], $activeAffIds);
            if ($offerId > 0) { $where[] = 'sd.offer_id=?'; $params[] = $offerId; }
            $whereStr = implode(' AND ', $where);

            $selectMap = [
                'day'       => 'sd.stat_date as label',
                'offer'     => 'o.name as label',
                'affiliate' => "CONCAT(u.first_name,' ',u.last_name) as label",
            ];
            $joinMap = [
                'day'       => '',
                'offer'     => 'JOIN offers o ON o.id=sd.offer_id',
                'affiliate' => 'JOIN affiliates af ON af.id=sd.affiliate_id JOIN users u ON u.id=af.user_id',
            ];
            $groupMap = [
                'day'       => 'sd.stat_date',
                'offer'     => 'sd.offer_id',
                'affiliate' => 'sd.affiliate_id',
            ];

            $rows = Database::fetchAll(
                "SELECT {$selectMap[$tab]},
                        SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                        SUM(sd.conversions) as conv, SUM(sd.approved) as approved,
                        SUM(sd.rejected) as rejected, SUM(sd.payout) as payout,
                        SUM(sd.fraud_clicks) as fraud
                 FROM stats_daily sd {$joinMap[$tab]}
                 WHERE $whereStr
                 GROUP BY {$groupMap[$tab]} ORDER BY {$groupMap[$tab]} DESC",
                $params
            );
        } else {
            $grpCol = $tab === 'country' ? 'c.country' : 'c.sub1';
            
            $clkWhere  = ["c.clicked_at BETWEEN ? AND ?", "c.affiliate_id IN ($activeInSql)", "c.status = 'valid'"];
            $clkParams = array_merge([$dateFrom, $dateTo], $activeAffIds);
            if ($offerId > 0) { $clkWhere[] = 'c.offer_id=?'; $clkParams[] = $offerId; }
            if ($country !== '' && $tab !== 'country') { $clkWhere[] = 'c.country=?'; $clkParams[] = strtoupper($country); }
            if ($sub1    !== '' && $tab !== 'sub')     { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
            $whereStr = implode(' AND ', $clkWhere);

            $rows = Database::fetchAll(
                "SELECT $grpCol as label,
                        COUNT(*) as clicks, SUM(c.is_unique) as uclicks,
                        SUM(CASE WHEN cv.id IS NOT NULL AND COALESCE(cv.fraud_score,0) >= 60 THEN 1 ELSE 0 END) as fraud,
                        COUNT(cv.id) as conv,
                        SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN cv.status='rejected' THEN 1 ELSE 0 END) as rejected,
                        COALESCE(SUM(CASE WHEN cv.status='approved' AND cv.is_hidden=0 THEN cv.payout ELSE 0 END), 0) as payout
                 FROM clicks c
                 LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
                 WHERE $whereStr
                 GROUP BY $grpCol ORDER BY clicks DESC LIMIT $limit",
                $clkParams
            );
            
            foreach ($rows as &$r) {
                if (empty($r['label'])) $r['label'] = 'Unknown';
            }
        }

        $totals = [
            'clicks'   => array_sum(array_column($rows, 'clicks')),
            'uclicks'  => array_sum(array_column($rows, 'uclicks')),
            'conv'     => array_sum(array_column($rows, 'conv')),
            'approved' => array_sum(array_column($rows, 'approved')),
            'rejected' => array_sum(array_column($rows, 'rejected')),
            'fraud'    => array_sum(array_column($rows, 'fraud')),
            'payout'   => array_sum(array_column($rows, 'payout')),
        ];

        echo json_encode([
            'success' => true,
            'tab' => $tab,
            'from' => $from,
            'to' => $to,
            'rows' => array_map(function($r) {
                $r['payout'] = round((float)$r['payout'], 2);
                return $r;
            }, $rows),
            'totals' => $totals
        ]);
        exit;

    } elseif ($tab === 'click') {
        $clkWhere  = ["c.clicked_at BETWEEN ? AND ?", "c.affiliate_id IN ($activeInSql)"];
        $clkParams = array_merge([$dateFrom, $dateTo], $activeAffIds);
        if ($offerId > 0)    { $clkWhere[] = 'c.offer_id=?';  $clkParams[] = $offerId; }
        if ($country !== '') { $clkWhere[] = 'c.country=?';   $clkParams[] = strtoupper($country); }
        if ($sub1 !== '')    { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
        $whereStr = implode(' AND ', $clkWhere);

        $clicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.source,
                    c.os, c.browser, c.device_type, c.ip_address, c.country, c.city, c.clicked_at,
                    o.name as offer_name,
                    cv.status       as conv_status,
                    cv.payout       as conv_payout,
                    cv.converted_at as conv_time
             FROM clicks c
             LEFT JOIN offers o ON o.id = c.offer_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $whereStr
             ORDER BY c.clicked_at DESC
             LIMIT $limit",
            $clkParams
        );

        echo json_encode([
            'success' => true,
            'tab' => $tab,
            'clicks' => $clicks
        ]);
        exit;

    } elseif ($tab === 'conversion') {
        $cvWhere  = ["cv.converted_at BETWEEN ? AND ?", "cv.affiliate_id IN ($activeInSql)", "cv.is_hidden=0"];
        $cvParams = array_merge([$dateFrom, $dateTo], $activeAffIds);
        if ($offerId > 0)    { $cvWhere[] = 'cv.offer_id=?'; $cvParams[] = $offerId; }
        if ($country !== '') {
            $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.country=?)';
            $cvParams[] = strtoupper($country);
        }
        if ($sub1 !== '') {
            $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.sub1 LIKE ?)';
            $cvParams[] = '%'.$sub1.'%';
        }
        $whereStr = implode(' AND ', $cvWhere);

        $convRows = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                    o.name as offer_name,
                    ck.sub1, ck.os, ck.browser, ck.device_type, ck.ip_address, ck.country, ck.city
             FROM conversions cv
             JOIN offers o ON o.id = cv.offer_id
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE $whereStr
             ORDER BY cv.converted_at DESC
             LIMIT $limit",
            $cvParams
        );

        echo json_encode([
            'success' => true,
            'tab' => $tab,
            'conversions' => $convRows
        ]);
        exit;

    } elseif ($tab === 'sl_report') {
        $slClkWhere  = ["c.affiliate_id IN ($activeInSql)", "c.smartlink_id IS NOT NULL", "c.clicked_at BETWEEN ? AND ?"];
        $slClkParams = array_merge($activeAffIds, [$dateFrom, $dateTo]);
        if ($country !== '') { $slClkWhere[] = 'c.country=?';       $slClkParams[] = strtoupper($country); }
        $slClkWhere = implode(' AND ', $slClkWhere);

        $slClicks = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.source, c.ip_address, c.country, c.city, c.os, c.browser, c.device_type, c.clicked_at,
                    COALESCE(sl.name,'— Unknown —') as smartlink_name,
                    COALESCE(o.name, '— Custom URL —') as offer_name,
                    COALESCE(cv.status,'') as conv_status,
                    COALESCE(cv.payout, 0) as conv_payout
             FROM clicks c
             LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
             LEFT JOIN offers o ON o.id = c.offer_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
             WHERE $slClkWhere
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $slClkParams
        );

        echo json_encode([
            'success' => true,
            'tab' => $tab,
            'sl_clicks' => $slClicks
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid tab']);
} catch (\Throwable $e) {
    error_log("Manager Report API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while generating the report.']);
}
