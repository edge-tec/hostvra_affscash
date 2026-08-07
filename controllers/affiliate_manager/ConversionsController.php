<?php
Auth::check('affiliate_manager');
if (!Auth::hasPermission('view_conversions')) {
    Helpers::flash('error', 'You do not have permission to view conversions.');
    Helpers::redirect('/affiliate_manager/dashboard');
}
$pageTitle = 'Conversions';

$affIds = Auth::managerAffiliateIds();
$status = Helpers::get('status') ?: 'all';
$clickId = Helpers::get('click_id');

if (empty($affIds)) {
    $conversions = [];
} else {
    $inSql = implode(',', array_fill(0, count($affIds), '?'));
    $params = $affIds;
    $statusSql = '';
    if ($status !== 'all') {
        $statusSql .= " AND c.status=?";
        $params[] = $status;
    }
    if (!empty($clickId)) {
        $statusSql .= " AND c.click_id=?";
        $params[] = trim($clickId);
    }
    // Join the click row AND the latest fraud_log row (IPQS) so we can
    // derive a per-conversion Fraud Score AND display the real-time IPQS
    // score with VPN/Proxy/Tor/Bot flags. Pure read; no tracking changes.
    $conversions = Database::fetchAll(
        "SELECT c.*, o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code,
                ck.fraud_score     AS click_fraud_score,
                ck.is_fraud        AS click_is_fraud,
                ck.status          AS click_status,
                ck.ip_address      AS click_ip,
                fl.fraud_score     AS ipqs_score,
                fl.is_vpn          AS ipqs_is_vpn,
                fl.is_proxy        AS ipqs_is_proxy,
                fl.is_tor          AS ipqs_is_tor,
                fl.is_bot          AS ipqs_is_bot,
                fl.is_datacenter   AS ipqs_is_datacenter,
                fl.isp             AS ipqs_isp,
                fl.action_taken    AS ipqs_action,
                fl.checked_at      AS ipqs_checked_at
         FROM conversions c
         JOIN offers o ON o.id=c.offer_id
         JOIN affiliates af ON af.id=c.affiliate_id
         JOIN users u ON u.id=af.user_id
         LEFT JOIN clicks ck ON ck.click_id = c.click_id
         LEFT JOIN fraud_logs fl ON fl.click_id = c.click_id
         WHERE c.affiliate_id IN ($inSql) AND c.is_hidden=0 AND (c.hide_reason IS NULL OR c.hide_reason NOT LIKE '%traffic_back%') AND (ck.source IS NULL OR ck.source != 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs tbl WHERE tbl.click_id = c.click_id) $statusSql
         ORDER BY c.converted_at DESC LIMIT 500",
        $params
    );
}

require BASE_PATH . '/views/affiliate_manager/conversions.php';
