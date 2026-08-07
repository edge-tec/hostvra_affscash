<?php
/**
 * Manager App API — Fraud Report
 */
Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('view_fraud_reports');

$affIds = Auth::managerAffiliateIds();
$hasAffiliates = !empty($affIds);

$riskSql = FraudAutoNotify::highRiskWhereSql('cv');

$conversions = [];
$totals = [
    'total' => 0,
    'approved' => 0,
    'pending' => 0,
    'blocked' => 0,
    'fraud_flagged' => 0,
    'payout' => 0.0
];

if ($hasAffiliates) {
    $in = implode(',', array_fill(0, count($affIds), '?'));
    
    try {
        $conversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout,
                    cv.converted_at, cv.ip_address, COALESCE(NULLIF(cv.country,''), NULLIF(ck.country,''), NULLIF(cv.ipquery_country_code,'')) as country, cv.device_type, cv.os_version, cv.user_agent, cv.goal_name,
                    COALESCE(cv.rejection_reason, '') AS rejection_reason,
                    cv.rejected_at,
                    af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name, af.id AS affiliate_id,
                    o.name AS offer_name,
                    COALESCE(NULLIF(ck.city,''), NULLIF(cv.ipquery_city,'')) as city,
                    COALESCE(NULLIF(ck.region,''), NULLIF(cv.ipquery_state,'')) as region,
                    fl.fraud_score   AS ipqs_score,
                    fl.is_vpn        AS ipqs_is_vpn,
                    fl.is_proxy      AS ipqs_is_proxy,
                    fl.is_tor        AS ipqs_is_tor,
                    fl.is_bot        AS ipqs_is_bot,
                    fl.is_datacenter AS ipqs_is_datacenter,
                    fl.isp           AS ipqs_isp,
                    fl.action_taken  AS ipqs_action,
                    fl.checked_at    AS ipqs_checked_at
             FROM conversions cv
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in)
               AND COALESCE(cv.is_hidden, 0) = 0
               AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')
               AND (ck.source IS NULL OR ck.source != 'traffic_back')
               AND NOT EXISTS (SELECT 1 FROM traffic_back_logs tbl WHERE tbl.click_id = cv.click_id)
               AND $riskSql
             ORDER BY cv.converted_at DESC LIMIT 1000",
            $affIds
        );
        
        foreach ($conversions as $cv) {
            $totals['total']++;
            if ($cv['status'] === 'approved') $totals['approved']++;
            if ($cv['status'] === 'pending') $totals['pending']++;
            if ($cv['status'] === 'rejected') $totals['blocked']++;
            $totals['fraud_flagged']++;
            if ($cv['status'] === 'approved') {
                $totals['payout'] += (float)$cv['payout'];
            }
        }
    } catch (\Throwable $e) {}
}

echo json_encode([
    'success' => true,
    'totals' => $totals,
    'conversions' => $conversions
]);
