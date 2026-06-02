<?php

Auth::check('admin');

$from = Helpers::get('from') ?: date('Y-m-d');
$to   = Helpers::get('to')   ?: date('Y-m-d');
$affId = (int)Helpers::get('affiliate_id');
$offerId = (int)Helpers::get('offer_id');
$limit = min((int)(Helpers::get('limit') ?: 500), 5000);

$params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
$where  = ['t.created_at BETWEEN ? AND ?'];

if ($affId > 0) {
    $where[] = 't.affiliate_id = ?';
    $params[] = $affId;
}
if ($offerId > 0) {
    $where[] = 't.offer_id = ?';
    $params[] = $offerId;
}

$whereStr = implode(' AND ', $where);

$logs = Database::fetchAll(
    "SELECT t.*, 
            o.name as offer_name, 
            CONCAT(u.first_name, ' ', u.last_name) as aff_name, 
            af.affiliate_code 
     FROM traffic_back_logs t 
     LEFT JOIN offers o ON o.id = t.offer_id 
     LEFT JOIN affiliates af ON af.id = t.affiliate_id 
     LEFT JOIN users u ON u.id = af.user_id 
     WHERE $whereStr 
     ORDER BY t.created_at DESC 
     LIMIT $limit",
    $params
);

$totalLogs = count($logs);

$offerList = Database::fetchAll("SELECT id, name FROM offers ORDER BY name");
$affList   = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name");

require BASE_PATH . '/views/admin/reports/traffic_back.php';
