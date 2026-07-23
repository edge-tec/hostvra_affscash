<?php
Auth::check('affiliate');                      // Affiliate-only — admins/managers have their own pages
$pageTitle = 'Fraud Report';

$affId   = (int)Auth::affiliateId();
$riskSql = FraudAutoNotify::highRiskWhereSql('cv');   // single source of truth: fraud_score >= 60

// Optional CSV export — same column set the affiliate sees on screen, never the score number.
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fraud-report-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Conversion ID','Click ID','Offer','Status','Risk','Country','IP','Payout','Converted At']);
    try {
        $rows = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.converted_at,
                    cv.ip_address, ck.country,
                    IF(COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id), 4, '0'), '] ', COALESCE(sl.name, sl2.name)), 'SmartLink'), o.name) AS offer_name
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
             LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
             LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             WHERE cv.affiliate_id = ?
               AND $riskSql
             ORDER BY cv.converted_at DESC LIMIT 5000",
            [$affId]
        );
    } catch (\Throwable $e) { $rows = []; }
    foreach ($rows as $r) {
        fputcsv($f, [
            $r['conversion_id'], $r['click_id'], $r['offer_name'] ?? '',
            $r['status'], 'High Risk Fraud Conversion',
            $r['country'] ?? '', $r['ip_address'] ?? '',
            $r['payout'], $r['converted_at'],
        ]);
    }
    fclose($f); exit;
}

// On-screen rows — NEVER select fraud_score so the view literally cannot leak the percentage.
try {
    $conversions = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
                cv.converted_at, cv.ip_address, ck.country,
                COALESCE(cv.rejection_reason, '') AS rejection_reason,
                cv.rejected_at,
                IF(COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id), 4, '0'), '] ', COALESCE(sl.name, sl2.name)), 'SmartLink'), o.name) AS offer_name
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
         LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
         LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
         LEFT JOIN offers o ON o.id = cv.offer_id
         WHERE cv.affiliate_id = ?
           AND $riskSql
         ORDER BY cv.converted_at DESC LIMIT 1000",
        [$affId]
    );
} catch (\Throwable $e) { $conversions = []; }

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

// Optional drill-down: highlight the conversion the notification linked to
$highlightCid = trim((string)Helpers::get('cid'));

require BASE_PATH . '/views/affiliate/fraud_report.php';
