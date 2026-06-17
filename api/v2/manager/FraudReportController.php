<?php
/**
 * Manager App API — Fraud Report
 */
Auth::checkAPI('affiliate_manager');
ManagerPermissions::requirePermission('reject_fraud_conv');

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
                    cv.converted_at, cv.ip_address, cv.country, cv.device_type, cv.os_version, cv.user_agent, cv.goal_name,
                    COALESCE(cv.rejection_reason, '') AS rejection_reason,
                    cv.rejected_at,
                    af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name, af.id AS affiliate_id,
                    o.name AS offer_name,
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
             LEFT JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in)
               AND cv.is_hidden = 0
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
