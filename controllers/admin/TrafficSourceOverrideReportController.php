<?php
Auth::check('admin');

$from = Helpers::get('from') ?: date('Y-m-d');
$to   = Helpers::get('to')   ?: date('Y-m-d');
$affId = (int)Helpers::get('affiliate_id');
$offerId = (int)Helpers::get('offer_id');
$limit = min((int)(Helpers::get('limit') ?: 500), 5000);

$params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
$where  = ['c.clicked_at BETWEEN ? AND ?', 'c.override_source IS NOT NULL'];

if ($affId > 0) {
    $where[] = 'c.affiliate_id = ?';
    $params[] = $affId;
}
if ($offerId > 0) {
    $where[] = 'c.offer_id = ?';
    $params[] = $offerId;
}

$whereStr = implode(' AND ', $where);

$logs = Database::fetchAll(
    "SELECT c.id, c.click_id, c.ip_address, c.traffic_source as original_source, c.override_source, c.clicked_at,
            o.name as offer_name, 
            CONCAT(u.first_name, ' ', u.last_name) as aff_name, 
            af.affiliate_code,
            c.affiliate_id,
            c.offer_id
     FROM clicks c 
     LEFT JOIN offers o ON o.id = c.offer_id 
     LEFT JOIN affiliates af ON af.id = c.affiliate_id 
     LEFT JOIN users u ON u.id = af.user_id 
     WHERE $whereStr 
     ORDER BY c.clicked_at DESC 
     LIMIT $limit",
    $params
) ?: [];

$totalLogs = count($logs);

$offerList = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];
$affList   = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name") ?: [];

require BASE_PATH . '/views/admin/reports/traffic_source_override.php';
