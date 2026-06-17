<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    $affId = Auth::affiliateId();
    $riskSql = FraudAutoNotify::highRiskWhereSql('cv');

    // Clear unread fraud alerts since they are viewing the report
    try {
        Database::query("UPDATE fraud_alerts SET is_read = 1 WHERE affiliate_id = ? AND is_read = 0", [$affId]);
    } catch (\Throwable $e) {}

    // 30-day count for the summary card
    try {
        $count30 = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM conversions cv
             WHERE cv.affiliate_id = ?
               AND $riskSql
               AND cv.converted_at >= NOW() - INTERVAL 30 DAY",
            [$affId]
        )['c'] ?? 0);
    } catch (\Throwable $e) { $count30 = 0; }

    // On-screen rows — NEVER select fraud_score so the view literally cannot leak the percentage.
    try {
        $conversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
                    cv.converted_at, cv.ip_address, cv.country,
                    COALESCE(cv.rejection_reason, '') AS rejection_reason,
                    cv.rejected_at,
                    o.name AS offer_name
             FROM conversions cv
             LEFT JOIN offers o ON o.id = cv.offer_id
             WHERE cv.affiliate_id = ?
               AND $riskSql
             ORDER BY cv.converted_at DESC LIMIT 1000",
            [$affId]
        );
    } catch (\Throwable $e) { $conversions = []; }

    echo json_encode([
        'success' => true,
        'count_30_days' => $count30,
        'conversions' => $conversions
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
