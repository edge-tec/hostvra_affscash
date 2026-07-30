<?php
/**
 * Admin Traffic Back Conversions Report Controller
 * Only Admin can view this Traffic Back conversions report.
 */
Auth::check('admin');

$pageTitle = 'Traffic Back Conversions';

$from    = Helpers::get('from') ?: date('Y-m-d', strtotime('-30 days'));
$to      = Helpers::get('to')   ?: date('Y-m-d');
$affId   = (int)Helpers::get('affiliate_id');
$offerId = (int)Helpers::get('offer_id');
$status  = trim(Helpers::get('status') ?: 'all');
$limit   = min((int)(Helpers::get('limit') ?: 500), 5000);

$params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
$where  = [
    'cv.converted_at BETWEEN ? AND ?',
    "(cv.hide_reason LIKE '%traffic_back%' OR ck.source = 'traffic_back' OR EXISTS (SELECT 1 FROM traffic_back_logs tbl WHERE tbl.click_id = cv.click_id))"
];

if ($affId > 0) {
    $where[]  = 'cv.affiliate_id = ?';
    $params[] = $affId;
}
if ($offerId > 0) {
    $where[]  = 'cv.offer_id = ?';
    $params[] = $offerId;
}
if ($status !== 'all' && in_array($status, ['approved', 'pending', 'rejected'])) {
    $where[]  = 'cv.status = ?';
    $params[] = $status;
}

$whereStr = implode(' AND ', $where);

// CSV Export
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="traffic-back-conversions-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Conversion ID', 'Click ID', 'Affiliate', 'Aff Code', 'Offer Name', 'Status', 'IP', 'Country', 'Payout', 'Revenue', 'Converted At']);
    
    $rows = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
                cv.converted_at, cv.ip_address, ck.country,
                af.affiliate_code, CONCAT(u.first_name, ' ', u.last_name) as aff_name,
                o.name as offer_name
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         LEFT JOIN affiliates af ON af.id = cv.affiliate_id
         LEFT JOIN users u ON u.id = af.user_id
         LEFT JOIN offers o ON o.id = cv.offer_id
         WHERE $whereStr
         ORDER BY cv.converted_at DESC LIMIT 5000",
        $params
    ) ?: [];
    
    foreach ($rows as $r) {
        fputcsv($f, [
            $r['conversion_id'], $r['click_id'], $r['aff_name'], $r['affiliate_code'],
            $r['offer_name'] ?? 'N/A', $r['status'], $r['ip_address'], $r['country'],
            number_format((float)$r['payout'], 4), number_format((float)$r['revenue'], 4),
            $r['converted_at']
        ]);
    }
    fclose($f); exit;
}

// Stats Summary
$statsSql = "SELECT COUNT(*) as total,
                    SUM(CASE WHEN cv.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN cv.status = 'pending'  THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN cv.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    COALESCE(SUM(CASE WHEN cv.status = 'approved' THEN cv.payout ELSE 0 END), 0) as approved_payout,
                    COALESCE(SUM(CASE WHEN cv.status = 'approved' THEN cv.revenue ELSE 0 END), 0) as approved_revenue
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE $whereStr";
$summary = Database::fetchOne($statsSql, $params) ?: [
    'total' => 0, 'approved_count' => 0, 'pending_count' => 0, 'rejected_count' => 0,
    'approved_payout' => 0, 'approved_revenue' => 0
];

// Fetch Conversions list
$conversions = Database::fetchAll(
    "SELECT cv.conversion_id, cv.click_id, cv.status, cv.payout, cv.revenue,
            cv.converted_at, cv.ip_address, cv.hide_reason, cv.rejection_reason,
            ck.country, ck.source as click_source,
            af.affiliate_code, CONCAT(u.first_name, ' ', u.last_name) as aff_name,
            o.name as offer_name
     FROM conversions cv
     LEFT JOIN clicks ck ON ck.click_id = cv.click_id
     LEFT JOIN affiliates af ON af.id = cv.affiliate_id
     LEFT JOIN users u ON u.id = af.user_id
     LEFT JOIN offers o ON o.id = cv.offer_id
     WHERE $whereStr
     ORDER BY cv.converted_at DESC
     LIMIT $limit",
    $params
) ?: [];

$offerList = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];
$affList   = Database::fetchAll("SELECT af.id, CONCAT(u.first_name, ' ', u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name") ?: [];

require BASE_PATH . '/views/admin/reports/traffic_back_conversions.php';
