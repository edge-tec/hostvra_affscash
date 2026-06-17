<?php
/**
 * Admin App API — Comprehensive Reports
 */
Auth::check('admin');

try {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'filters') {
        // Return dropdown filter options
        $offers = Database::fetchAll("SELECT id, name FROM offers ORDER BY name");
        $affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name");
        $countries = Database::fetchAll("SELECT DISTINCT country FROM clicks WHERE country != '' ORDER BY country");
        
        echo json_encode([
            'status' => 'success',
            'offers' => $offers,
            'affiliates' => $affiliates,
            'countries' => array_column($countries, 'country')
        ]);
        exit;
    }

    $tab     = $_GET['tab'] ?? 'performance';
    $from    = $_GET['from'] ?? date('Y-m-01');
    $to      = $_GET['to']   ?? date('Y-m-d');
    $offerId = (int)($_GET['offer_id'] ?? 0);
    $affId   = (int)($_GET['affiliate_id'] ?? 0);
    $country = trim($_GET['country'] ?? '');
    $sub1    = trim($_GET['sub1'] ?? '');
    $limit   = min((int)($_GET['limit'] ?? 1000), 5000); // Mobile default
    $slId    = (int)($_GET['sl_id'] ?? 0);
    $groupBy = $_GET['group_by'] ?? 'date';

    $dateFrom = date('Y-m-d 00:00:00', strtotime($from));
    $dateTo   = date('Y-m-d 23:59:59', strtotime($to));

    // Common WHERE parts
    $baseWhere = [];
    $baseParams = [];
    if ($offerId > 0) { $baseWhere[] = 'offer_id = ?'; $baseParams[] = $offerId; }
    if ($affId > 0) { $baseWhere[] = 'affiliate_id = ?'; $baseParams[] = $affId; }

    if ($tab === 'performance') {
        $validGroups = ['date','offer','affiliate','country','sub'];
        if (!in_array($groupBy, $validGroups)) $groupBy = 'date';

        if (in_array($groupBy, ['date','offer','affiliate'])) {
            $selectMap = [
                'date'      => 'sd.stat_date as label',
                'offer'     => 'o.name as label',
                'affiliate' => "CONCAT(u.first_name,' ',u.last_name) as label",
            ];
            $groupMap = [
                'date'      => 'sd.stat_date',
                'offer'     => 'sd.offer_id',
                'affiliate' => 'sd.affiliate_id',
            ];
            $joinMap = [
                'date'      => '',
                'offer'     => 'JOIN offers o ON o.id=sd.offer_id',
                'affiliate' => 'JOIN affiliates af ON af.id=sd.affiliate_id JOIN users u ON u.id=af.user_id',
            ];
            $sdWhere = array_merge(['sd.stat_date BETWEEN ? AND ?'], str_replace('offer_id', 'sd.offer_id', str_replace('affiliate_id', 'sd.affiliate_id', $baseWhere)));
            $sdParams = array_merge([$from, $to], $baseParams);
            $whereStr = implode(' AND ', $sdWhere);

            $rows = Database::fetchAll(
                "SELECT {$selectMap[$groupBy]},
                        SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                        SUM(sd.conversions) as conversions, SUM(sd.approved) as approved,
                        SUM(sd.rejected) as rejected, SUM(sd.payout) as payout,
                        SUM(sd.revenue) as revenue, SUM(sd.fraud_clicks) as fraud_clicks,
                        SUM(sd.impressions) as impressions
                 FROM stats_daily sd {$joinMap[$groupBy]}
                 WHERE $whereStr
                 GROUP BY {$groupMap[$groupBy]} ORDER BY payout DESC",
                $sdParams
            );
        } else {
            $selectGrp = $groupBy === 'country' ? 'c.country as label' : 'c.sub1 as label';
            $groupGrp  = $groupBy === 'country' ? 'c.country'          : 'c.sub1';

            $clkWhere = array_merge(['c.clicked_at BETWEEN ? AND ?'], str_replace('offer_id', 'c.offer_id', str_replace('affiliate_id', 'c.affiliate_id', $baseWhere)));
            $clkParams = array_merge([$dateFrom, $dateTo], $baseParams);
            if ($country !== '' && $groupBy !== 'country') { $clkWhere[] = 'c.country = ?'; $clkParams[] = strtoupper($country); }
            if ($sub1    !== '' && $groupBy !== 'sub')     { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
            $whereStr = implode(' AND ', $clkWhere);

            $rows = Database::fetchAll(
                "SELECT $selectGrp,
                        COUNT(*) as clicks, SUM(c.is_unique) as uclicks,
                        SUM(c.is_fraud) as fraud_clicks,
                        SUM(c.payout) as payout, SUM(c.revenue) as revenue,
                        0 as conversions, 0 as approved, 0 as rejected, 0 as impressions
                 FROM clicks c
                 WHERE $whereStr
                 GROUP BY $groupGrp ORDER BY clicks DESC LIMIT $limit",
                $clkParams
            );
        }

        $totals = [
            'clicks'      => array_sum(array_column($rows,'clicks')),
            'uclicks'     => array_sum(array_column($rows,'uclicks')),
            'conversions' => array_sum(array_column($rows,'conversions')),
            'approved'    => array_sum(array_column($rows,'approved')),
            'rejected'    => array_sum(array_column($rows,'rejected')),
            'fraud_clicks'=> array_sum(array_column($rows,'fraud_clicks')),
            'payout'      => array_sum(array_column($rows,'payout')),
            'revenue'     => array_sum(array_column($rows,'revenue')),
        ];
        $totals['profit'] = $totals['revenue'] - $totals['payout'];

        echo json_encode(['status' => 'success', 'totals' => $totals, 'rows' => $rows]);
        exit;

    } elseif ($tab === 'offer_report') {
        $orWhere = array_merge(['sd.stat_date BETWEEN ? AND ?'], str_replace('offer_id', 'sd.offer_id', str_replace('affiliate_id', 'sd.affiliate_id', $baseWhere)));
        $orParams = array_merge([$from, $to], $baseParams);
        $whereStr = implode(' AND ', $orWhere);

        $rows = Database::fetchAll(
            "SELECT o.id as offer_id, o.name as offer_name, o.status as offer_status,
                    SUM(sd.clicks) as clicks, SUM(sd.unique_clicks) as uclicks,
                    SUM(sd.conversions) as conversions, SUM(sd.approved) as approved,
                    SUM(sd.rejected) as rejected, SUM(sd.fraud_clicks) as fraud_clicks,
                    SUM(sd.payout) as payout, SUM(sd.revenue) as revenue
             FROM stats_daily sd
             JOIN offers o ON o.id = sd.offer_id
             WHERE $whereStr
             GROUP BY o.id ORDER BY payout DESC",
            $orParams
        );

        $totals = [
            'clicks'      => array_sum(array_column($rows,'clicks')),
            'uclicks'     => array_sum(array_column($rows,'uclicks')),
            'conversions' => array_sum(array_column($rows,'conversions')),
            'approved'    => array_sum(array_column($rows,'approved')),
            'rejected'    => array_sum(array_column($rows,'rejected')),
            'fraud_clicks'=> array_sum(array_column($rows,'fraud_clicks')),
            'payout'      => array_sum(array_column($rows,'payout')),
            'revenue'     => array_sum(array_column($rows,'revenue')),
        ];
        $totals['profit'] = $totals['revenue'] - $totals['payout'];

        echo json_encode(['status' => 'success', 'totals' => $totals, 'rows' => $rows]);
        exit;

    } elseif ($tab === 'clicks') {
        $clkWhere = array_merge(['c.clicked_at BETWEEN ? AND ?'], str_replace('offer_id', 'c.offer_id', str_replace('affiliate_id', 'c.affiliate_id', $baseWhere)));
        $clkParams = array_merge([$dateFrom, $dateTo], $baseParams);
        if ($country !== '') { $clkWhere[] = 'c.country = ?'; $clkParams[] = strtoupper($country); }
        if ($sub1 !== '') { $clkWhere[] = 'c.sub1 LIKE ?'; $clkParams[] = '%'.$sub1.'%'; }
        $whereStr = implode(' AND ', $clkWhere);

        $rows = Database::fetchAll(
            "SELECT c.click_id, c.sub1, c.sub2, c.os, c.browser, c.device_type, c.ip_address, c.country,
                    c.is_fraud, c.clicked_at, o.name as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    COALESCE(cv.revenue, 0) as revenue, COALESCE(cv.payout, 0) as payout,
                    cv.status as conv_status
             FROM clicks c
             JOIN offers o ON o.id=c.offer_id
             JOIN affiliates af ON af.id=c.affiliate_id
             JOIN users u ON u.id=af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status IN ('approved','pending') AND cv.is_hidden = 0
             WHERE $whereStr
             ORDER BY c.clicked_at DESC LIMIT $limit",
            $clkParams
        );
        echo json_encode(['status' => 'success', 'rows' => $rows]);
        exit;

    } elseif (in_array($tab, ['conversions','rejected','pending','autohide'])) {
        $cvWhere = array_merge(['cv.converted_at BETWEEN ? AND ?'], str_replace('offer_id', 'cv.offer_id', str_replace('affiliate_id', 'cv.affiliate_id', $baseWhere)));
        $cvParams = array_merge([$dateFrom, $dateTo], $baseParams);

        if ($tab === 'autohide') {
            $cvWhere[] = 'cv.is_hidden = 1';
        } else {
            if ($tab === 'conversions') $cvWhere[] = "cv.status = 'approved'";
            if ($tab === 'rejected') $cvWhere[] = "cv.status = 'rejected'";
            if ($tab === 'pending') $cvWhere[] = "cv.status = 'pending'";
            $cvWhere[] = 'COALESCE(cv.is_hidden, 0) = 0';
        }
        
        if ($country !== '') { $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.country=?)'; $cvParams[] = strtoupper($country); }
        if ($sub1 !== '') { $cvWhere[] = 'EXISTS(SELECT 1 FROM clicks ck WHERE ck.click_id=cv.click_id AND ck.sub1 LIKE ?)'; $cvParams[] = '%'.$sub1.'%'; }
        $whereStr = implode(' AND ', $cvWhere);

        $rows = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
                    cv.is_fraud, cv.fraud_score, cv.converted_at, cv.ip_address,
                    COALESCE(cv.rejection_reason, '') as rejection_reason,
                    o.name as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                    ck.country, ck.city
             FROM conversions cv
             LEFT JOIN offers o ON o.id=cv.offer_id
             JOIN affiliates af ON af.id=cv.affiliate_id
             JOIN users u ON u.id=af.user_id
             LEFT JOIN clicks ck ON ck.click_id=cv.click_id
             WHERE $whereStr
             ORDER BY cv.converted_at DESC LIMIT $limit",
            $cvParams
        );
        echo json_encode(['status' => 'success', 'rows' => $rows]);
        exit;

    } elseif ($tab === 'postback') {
        $pbWhere = ['pl.fired_at BETWEEN ? AND ?'];
        $pbParams = [$dateFrom, $dateTo];
        if ($offerId > 0) { $pbWhere[] = '(pb.offer_id = ? OR (pl.postback_id = 0 AND cv_filter.offer_id = ?))'; $pbParams[] = $offerId; $pbParams[] = $offerId; }
        if ($affId > 0) { $pbWhere[] = '(pb.affiliate_id = ? OR (pl.postback_id = 0 AND cv_filter.affiliate_id = ?))'; $pbParams[] = $affId; $pbParams[] = $affId; }
        $whereStr = implode(' AND ', $pbWhere);

        $rows = Database::fetchAll(
            "SELECT pl.id, pl.conversion_id, pl.http_status, pl.is_success, pl.fired_at, pl.fired_url,
                    COALESCE(pb.method,'GET') as method,
                    COALESCE(CONCAT(u.first_name,' ',u.last_name), CONCAT(u2.first_name,' ',u2.last_name)) as aff_name,
                    COALESCE(af.affiliate_code, af2.affiliate_code) as affiliate_code,
                    COALESCE(o.name, o2.name, 'All Offers') as offer_name
             FROM postback_logs pl
             LEFT JOIN postbacks pb ON pb.id = pl.postback_id AND pl.postback_id > 0
             LEFT JOIN affiliates af ON af.id = pb.affiliate_id
             LEFT JOIN users u ON u.id = af.user_id
             LEFT JOIN offers o ON o.id = pb.offer_id
             LEFT JOIN conversions cv ON cv.conversion_id = pl.conversion_id
             LEFT JOIN conversions cv_filter ON cv_filter.conversion_id = pl.conversion_id
             LEFT JOIN affiliates af2 ON af2.id = cv.affiliate_id AND pl.postback_id = 0
             LEFT JOIN users u2 ON u2.id = af2.user_id AND pl.postback_id = 0
             LEFT JOIN offers o2 ON o2.id = cv.offer_id AND pl.postback_id = 0
             WHERE $whereStr
             GROUP BY pl.id
             ORDER BY pl.fired_at DESC LIMIT $limit",
            $pbParams
        );
        echo json_encode(['status' => 'success', 'rows' => $rows]);
        exit;

    } elseif (strpos($tab, 'sl_') === 0) {
        $clkWhere = ['c.smartlink_id IS NOT NULL'];
        $clkParams = [];
        
        if ($tab === 'sl_conversions') {
            $clkWhere[] = 'cv.converted_at BETWEEN ? AND ?';
            $clkParams = [$dateFrom, $dateTo];
        } else {
            $clkWhere[] = 'c.clicked_at BETWEEN ? AND ?';
            $clkParams = [$dateFrom, $dateTo];
        }

        if ($slId > 0) { $clkWhere[] = 'c.smartlink_id = ?'; $clkParams[] = $slId; }
        if ($affId > 0) { $clkWhere[] = 'c.affiliate_id = ?'; $clkParams[] = $affId; }
        if ($country !== '') { $clkWhere[] = 'c.country = ?'; $clkParams[] = strtoupper($country); }
        $whereStr = implode(' AND ', $clkWhere);

        if ($tab === 'sl_clicks') {
            $rows = Database::fetchAll(
                "SELECT c.click_id, c.sub1, c.country, c.clicked_at,
                        COALESCE(sl.name, 'Unknown') as smartlink_name,
                        COALESCE(o.name, 'Custom') as offer_name,
                        CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                        COALESCE(cv.revenue, 0) as revenue, COALESCE(cv.payout, 0) as payout, cv.status as conv_status
                 FROM clicks c
                 LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
                 LEFT JOIN offers o ON o.id = c.offer_id
                 JOIN affiliates af ON af.id = c.affiliate_id
                 JOIN users u ON u.id = af.user_id
                 LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status IN ('approved','pending') AND cv.is_hidden = 0
                 WHERE $whereStr
                 ORDER BY c.clicked_at DESC LIMIT $limit",
                $clkParams
            );
        } elseif ($tab === 'sl_conversions') {
            $rows = Database::fetchAll(
                "SELECT cv.conversion_id, cv.status, cv.payout, cv.revenue, cv.converted_at,
                        COALESCE(sl.name, 'Unknown') as smartlink_name,
                        COALESCE(o.name, 'Custom') as offer_name,
                        CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                        ck.country
                 FROM conversions cv
                 JOIN clicks ck ON ck.click_id = cv.click_id
                 LEFT JOIN smartlinks sl ON sl.id = ck.smartlink_id
                 LEFT JOIN offers o ON o.id = cv.offer_id
                 JOIN affiliates af ON af.id = cv.affiliate_id
                 JOIN users u ON u.id = af.user_id
                 WHERE $whereStr
                 ORDER BY cv.converted_at DESC LIMIT $limit",
                $clkParams
            );
        } elseif ($tab === 'sl_affiliates') {
            $rows = Database::fetchAll(
                "SELECT CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                        COALESCE(sl.name, 'Unknown') as smartlink_name,
                        COUNT(c.click_id) as clicks, SUM(c.is_unique) as uclicks,
                        COUNT(cv.id) as conversions,
                        COALESCE(SUM(CASE WHEN cv.status='approved' THEN cv.payout ELSE 0 END), 0) as payout,
                        COALESCE(SUM(CASE WHEN cv.status='approved' THEN cv.revenue ELSE 0 END), 0) as revenue
                 FROM clicks c
                 JOIN affiliates af ON af.id = c.affiliate_id
                 JOIN users u ON u.id = af.user_id
                 LEFT JOIN smartlinks sl ON sl.id = c.smartlink_id
                 LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
                 WHERE $whereStr
                 GROUP BY af.id, c.smartlink_id
                 ORDER BY clicks DESC LIMIT $limit",
                $clkParams
            );
        }
        echo json_encode(['status' => 'success', 'rows' => $rows]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid tab']);
} catch (\Throwable $e) {
    error_log("Admin Report API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
