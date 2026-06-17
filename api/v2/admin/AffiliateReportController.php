<?php
/**
 * Admin App API — Affiliate Report
 */
Auth::check('admin');

try {
    $action = $_GET['action'] ?? 'list';

    // ─── Filters ──────────────────────────────────────────────────────────────
    if ($action === 'filters') {
        $offers = Database::fetchAll("SELECT id, name FROM offers ORDER BY name");
        $affiliates = Database::fetchAll(
            "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
             FROM affiliates af JOIN users u ON u.id = af.user_id ORDER BY name"
        );
        $countries = Database::fetchAll("SELECT DISTINCT country FROM clicks WHERE country != '' ORDER BY country");
        
        echo json_encode([
            'status' => 'success',
            'offers' => $offers,
            'affiliates' => $affiliates,
            'countries' => array_column($countries, 'country')
        ]);
        exit;
    }

    if ($action === 'list') {
        $from    = $_GET['from'] ?? date('Y-m-01');
        $to      = $_GET['to']   ?? date('Y-m-d');
        $affId   = (int)($_GET['affiliate_id'] ?? 0);
        $affCode = trim($_GET['affiliate_code'] ?? '');
        $affName = trim($_GET['affiliate_name'] ?? '');
        if ($affCode !== '' && $affId === 0) {
            $_r = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code = ?", [$affCode]);
            if ($_r) $affId = (int)$_r['id'];
        }
        $offerId    = (int)($_GET['offer_id'] ?? 0);
        $country    = strtoupper(trim($_GET['country'] ?? ''));
        $convStatus = trim($_GET['conv_status'] ?? ''); // approved|pending|rejected|''
        $device     = trim($_GET['device'] ?? '');      // desktop|mobile|tablet|bot|unknown|''
        $trafficSt  = trim($_GET['traffic_status'] ?? ''); // clean|fraud|blocked|''
        $ipFilter   = trim($_GET['ip'] ?? '');
        $limit      = min((int)($_GET['limit'] ?? 1000), 5000);

        $dateFrom = date('Y-m-d 00:00:00', strtotime($from));
        $dateTo   = date('Y-m-d 23:59:59', strtotime($to));

        $w  = ['c.clicked_at BETWEEN ? AND ?'];
        $p  = [$dateFrom, $dateTo];
        if ($offerId  > 0)   { $w[] = 'c.offer_id = ?';     $p[] = $offerId; }
        if ($affId    > 0)   { $w[] = 'c.affiliate_id = ?'; $p[] = $affId;   }
        if ($country !== '') { $w[] = 'c.country = ?';      $p[] = $country; }
        if ($device  !== '') { $w[] = 'c.device_type = ?';  $p[] = $device;  }
        if ($ipFilter !== ''){ $w[] = 'c.ip_address LIKE ?';$p[] = '%'.$ipFilter.'%'; }
        if ($trafficSt === 'clean')   { $w[] = 'c.is_fraud = 0 AND c.status = "valid"'; }
        if ($trafficSt === 'fraud')   { $w[] = 'c.is_fraud = 1'; }
        if ($trafficSt === 'blocked') { $w[] = "c.status = 'blocked'"; }
        if ($affName !== '') {
            $w[] = "EXISTS(SELECT 1 FROM users uu JOIN affiliates aaff ON aaff.user_id=uu.id
                           WHERE aaff.id = c.affiliate_id
                             AND CONCAT(uu.first_name,' ',uu.last_name) LIKE ?)";
            $p[] = '%'.$affName.'%';
        }
        $wClk = implode(' AND ', $w);
        $pClk = $p;

        $convSubSelect = '
            SELECT cv.affiliate_id,
                   SUM(CASE WHEN cv.status="approved" THEN 1 ELSE 0 END) AS conv_approved,
                   SUM(CASE WHEN cv.status="pending"  THEN 1 ELSE 0 END) AS conv_pending,
                   SUM(CASE WHEN cv.status="rejected" THEN 1 ELSE 0 END) AS conv_rejected,
                   SUM(CASE WHEN cv.status IN ("approved","pending","rejected") THEN 1 ELSE 0 END) AS conv_total,
                   SUM(CASE WHEN cv.status="approved" THEN cv.payout  ELSE 0 END) AS payout,
                   SUM(CASE WHEN cv.status="approved" THEN cv.revenue ELSE 0 END) AS revenue,
                   MAX(cv.converted_at) AS last_conv_at
              FROM conversions cv
             WHERE cv.converted_at BETWEEN ? AND ?
               AND COALESCE(cv.is_hidden,0) = 0
               ' . ($offerId > 0 ? ' AND cv.offer_id = ' . (int)$offerId : '') . '
               ' . ($affId   > 0 ? ' AND cv.affiliate_id = ' . (int)$affId : '') . '
             GROUP BY cv.affiliate_id';
        $convParams = [$dateFrom, $dateTo];

        $havingConv = '';
        if (in_array($convStatus, ['approved','pending','rejected'], true)) {
            $havingConv = ' HAVING conv_' . $convStatus . ' > 0';
        }

        $sqlAgg = "
            SELECT af.id AS affiliate_id, af.affiliate_code, af.created_at AS registered_at,
                   u.email, u.status AS user_status, u.last_login,
                   CONCAT(u.first_name,' ',u.last_name) AS aff_name,
                   SUM(c.is_unique)                                AS unique_clicks,
                   COUNT(*)                                        AS total_clicks,
                   SUM(c.is_fraud)                                 AS fraud_clicks,
                   SUM(CASE WHEN c.status='blocked' THEN 1 ELSE 0 END) AS blocked_clicks,
                   MAX(c.clicked_at)                               AS last_click_at,
                   COALESCE(cvg.conv_total, 0)                     AS conv_total,
                   COALESCE(cvg.conv_approved, 0)                  AS conv_approved,
                   COALESCE(cvg.conv_pending, 0)                   AS conv_pending,
                   COALESCE(cvg.conv_rejected, 0)                  AS conv_rejected,
                   COALESCE(cvg.payout, 0)                         AS payout,
                   COALESCE(cvg.revenue, 0)                        AS revenue,
                   cvg.last_conv_at                                AS last_conv_at
              FROM clicks c
              JOIN affiliates af ON af.id = c.affiliate_id
              JOIN users      u  ON u.id  = af.user_id
              LEFT JOIN ($convSubSelect) cvg ON cvg.affiliate_id = af.id
             WHERE $wClk
             GROUP BY af.id $havingConv
             ORDER BY revenue DESC, total_clicks DESC
             LIMIT $limit";
        $aggRows = Database::fetchAll($sqlAgg, array_merge($convParams, $pClk)) ?: [];

        if ($affId === 0 && $affName === '' && empty($havingConv)) {
            $extraConv = Database::fetchAll("
                SELECT af.id AS affiliate_id, af.affiliate_code, af.created_at AS registered_at,
                       u.email, u.status AS user_status, u.last_login,
                       CONCAT(u.first_name,' ',u.last_name) AS aff_name,
                       0                                               AS unique_clicks,
                       0                                               AS total_clicks,
                       0                                               AS fraud_clicks,
                       0                                               AS blocked_clicks,
                       NULL                                            AS last_click_at,
                       COALESCE(cvg.conv_total, 0)                     AS conv_total,
                       COALESCE(cvg.conv_approved, 0)                  AS conv_approved,
                       COALESCE(cvg.conv_pending, 0)                   AS conv_pending,
                       COALESCE(cvg.conv_rejected, 0)                  AS conv_rejected,
                       COALESCE(cvg.payout, 0)                         AS payout,
                       COALESCE(cvg.revenue, 0)                        AS revenue,
                       cvg.last_conv_at                                AS last_conv_at
                  FROM ($convSubSelect) cvg
                  JOIN affiliates af ON af.id = cvg.affiliate_id
                  JOIN users      u  ON u.id  = af.user_id
                 WHERE NOT EXISTS (SELECT 1 FROM clicks cx WHERE cx.affiliate_id = af.id AND cx.clicked_at BETWEEN ? AND ?)
            ", array_merge($convParams, [$dateFrom, $dateTo])) ?: [];
            foreach ($extraConv as $r) $aggRows[] = $r;
        }

        // IPQS Fraud stats
        $ipqsStatsByAffiliate = [];
        if (!empty($aggRows)) {
            $affIds = array_unique(array_column($aggRows, 'affiliate_id'));
            if (!empty($affIds)) {
                $placeholders = implode(',', array_fill(0, count($affIds), '?'));
                $ipqsRows = Database::fetchAll("
                    SELECT cv.affiliate_id,
                           COUNT(*)                                                        AS total_checked,
                           ROUND(AVG(CASE WHEN cv.fraud_score IS NOT NULL THEN cv.fraud_score END), 1) AS avg_score,
                           SUM(CASE WHEN cv.fraud_score >= 75 THEN 1 ELSE 0 END)          AS high_risk,
                           SUM(CASE WHEN cv.fraud_score >= 40 AND cv.fraud_score < 75 THEN 1 ELSE 0 END) AS medium_risk,
                           SUM(CASE WHEN cv.fraud_score IS NOT NULL AND cv.fraud_score < 40 THEN 1 ELSE 0 END) AS low_risk
                      FROM conversions cv
                     WHERE cv.affiliate_id IN ($placeholders)
                       AND cv.converted_at BETWEEN ? AND ?
                       AND COALESCE(cv.is_hidden, 0) = 0
                     GROUP BY cv.affiliate_id
                ", array_merge($affIds, [$dateFrom, $dateTo])) ?: [];
                foreach ($ipqsRows as $row) {
                    $ipqsStatsByAffiliate[(int)$row['affiliate_id']] = $row;
                }
            }
        }

        // Traffic detail
        $trafficRows = [];
        if ($affId > 0) {
            $convFilterJoin = '';
            if (in_array($convStatus, ['approved','pending','rejected'], true)) {
                $convFilterJoin = " AND cv.status = '" . $convStatus . "'";
            }
            $detLimit = min($limit, 2000);
            $trafficRows = Database::fetchAll(
                "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.sub5,
                        c.ip_address, c.country, c.city, c.region,
                        c.device_type, c.os, c.browser, c.user_agent,
                        c.is_fraud, c.fraud_score, c.status AS click_status, c.clicked_at,
                        o.name AS offer_name,
                        cv.status AS conv_status, cv.payout AS conv_payout, cv.revenue AS conv_revenue
                   FROM clicks c
                   JOIN offers o ON o.id = c.offer_id
                   LEFT JOIN conversions cv ON cv.click_id = c.click_id AND COALESCE(cv.is_hidden,0)=0
                  WHERE $wClk $convFilterJoin
                  ORDER BY c.clicked_at DESC
                  LIMIT $detLimit",
                $pClk
            ) ?: [];
        }

        echo json_encode([
            'status' => 'success',
            'rows' => $aggRows,
            'ipqs_stats' => $ipqsStatsByAffiliate,
            'traffic_detail' => $trafficRows
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (\Throwable $e) {
    error_log("Admin AffiliateReport API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
