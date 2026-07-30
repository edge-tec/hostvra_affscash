<?php
Auth::check('affiliate_manager');               // Manager-only — admins use the existing Fraud Score Report
ManagerPermissions::requirePermission('view_fraud_reports');
$pageTitle = 'Fraud Report';

$affIds = Auth::managerAffiliateIds();

// ── Reject action ────────────────────────────────────────────────────────────
// Manager rejects a fraud conversion with a required reason. Server-side
// checks: CSRF, role, permission (already gated above), and that the
// conversion belongs to one of the manager's own affiliates. The actual
// rejection re-uses the existing RejectionHelper so the user-facing
// behaviour (notification, balance reversal) stays identical to the admin
// reject flow. The manager-fraud-rejections audit row is the new layer.
if (Helpers::isPost() && Helpers::post('action') === 'reject_fraud' && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $convId = trim((string)Helpers::postRaw('conversion_id'));
    $reason = trim((string)Helpers::postRaw('reason'));
    if ($convId !== '' && $reason !== '' && !empty($affIds)) {
        $in = implode(',', array_fill(0, count($affIds), '?'));
        if (!Auth::hasPermission('reject_fraud_conv')) {
            Helpers::jsonResponse(['success' => false, 'message' => 'Permission denied'], 403);
        }
        // Scope check — must belong to one of the manager's affiliates AND
        // not already be rejected. The IN-clause is the security gate.
        $conv = Database::fetchOne(
            "SELECT id, conversion_id, affiliate_id, offer_id, status, payout
             FROM conversions
             WHERE conversion_id = ? AND affiliate_id IN ($in) AND status <> 'rejected'
             LIMIT 1",
            array_merge([$convId], $affIds)
        );
        if ($conv) {
            // Re-use existing rejection mechanics. Manager's user id is used as
            // the rejector — admin's fraud_rejections page shows the manager
            // by joining on affiliate_managers.user_id.
            $payload = RejectionHelper::buildUpdatePayload('rejected', $reason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            // Reverse affiliate balance + manager commission if previously approved.
            if (($conv['status'] ?? '') === 'approved') {
                try { Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]); } catch (\Throwable $_) {}
                try {
                    require_once BASE_PATH . '/core/ManagerCommissionService.php';
                    ManagerCommissionService::reverseForConversion((int)$conv['id']);
                } catch (\Throwable $_) {}
            }
            try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_) {}

            // Audit trail — admin-visible via /admin/affiliate-managers/fraud-rejections.
            $myMgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [(int)Auth::id()]);
            $myMgrId  = (int)($myMgrRow['id'] ?? 0);
            ManagerPermissions::logFraudRejection(
                $myMgrId,
                $convId,
                (int)($conv['affiliate_id'] ?? 0) ?: null,
                (int)($conv['offer_id'] ?? 0)     ?: null,
                $reason
            );
            Helpers::flash('success', 'Fraud conversion rejected.');
        } else {
            Helpers::flash('error', 'Conversion not found or already rejected.');
        }
    } else {
        Helpers::flash('error', 'A rejection reason is required.');
    }
    Helpers::redirect('/affiliate_manager/fraud-report');
}
$riskSql = FraudAutoNotify::highRiskWhereSql('cv');

$conversions = [];
$count30     = 0;

if (!empty($affIds)) {
    $in = implode(',', array_fill(0, count($affIds), '?'));

    // CSV export — manager view, score never included.
    if (Helpers::get('export') === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="manager-fraud-report-'.date('Y-m-d').'.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['Conversion ID','Click ID','Affiliate','Aff Code','Offer','Status','Risk','Country','IP','Payout','Converted At']);
        try {
            $rows = Database::fetchAll(
                "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                        cv.ip_address, ck.country,
                        af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name,
                        o.name AS offer_name
                 FROM conversions cv
                 JOIN affiliates af ON af.id = cv.affiliate_id
                 JOIN users u ON u.id = af.user_id
                 LEFT JOIN clicks ck ON ck.click_id = cv.click_id
                 LEFT JOIN offers o ON o.id = cv.offer_id
                 WHERE cv.affiliate_id IN ($in)
                   AND cv.is_hidden = 0
                   AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')
                   AND (ck.source IS NULL OR ck.source != 'traffic_back')
                   AND $riskSql
                 ORDER BY cv.converted_at DESC LIMIT 5000",
                $affIds
            );
        } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) {
            fputcsv($f, [
                $r['conversion_id'], $r['click_id'], $r['aff_name'], $r['affiliate_code'],
                $r['offer_name'] ?? '',
                $r['status'], 'High Risk Fraud Conversion',
                $r['country'] ?? '', $r['ip_address'] ?? '',
                $r['payout'], $r['converted_at'],
            ]);
        }
        fclose($f); exit;
    }

    try {
        $conversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout,
                    cv.converted_at, cv.ip_address, ck.country,
                    COALESCE(cv.rejection_reason, '') AS rejection_reason,
                    cv.rejected_at,
                    af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name,
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
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             LEFT JOIN fraud_logs fl ON fl.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in)
               AND cv.is_hidden = 0
               AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')
               AND (ck.source IS NULL OR ck.source != 'traffic_back')
               AND $riskSql
             ORDER BY cv.converted_at DESC LIMIT 1000",
            $affIds
        );

        $count30 = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE cv.affiliate_id IN ($in)
               AND cv.is_hidden = 0
               AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')
               AND (ck.source IS NULL OR ck.source != 'traffic_back')
               AND $riskSql
               AND cv.converted_at >= NOW() - INTERVAL 30 DAY",
            $affIds
        )['c'] ?? 0);
    } catch (\Throwable $e) {}
}

$highlightCid = trim((string)Helpers::get('cid'));

require BASE_PATH . '/views/affiliate_manager/fraud_report.php';
