<?php
/**
 * Admin App API — Traffic Source Override Logs Report
 */
Auth::check('admin');

try {
    $from = $_GET['from'] ?? ($_GET['start_date'] ?? date('Y-m-d'));
    $to   = $_GET['to']   ?? ($_GET['end_date']   ?? date('Y-m-d'));
    $affId = (int)($_GET['affiliate_id'] ?? 0);
    $offerId = (int)($_GET['offer_id'] ?? 0);
    $limit = min((int)($_GET['limit'] ?? 500), 1000);

    $params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
    $where  = ['c.clicked_at BETWEEN ? AND ?', 'c.override_source IS NOT NULL AND c.override_source != ""'];

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

    $formattedLogs = [];
    foreach ($logs as $log) {
        $formattedLogs[] = [
            'id' => (int)$log['id'],
            'click_id' => $log['click_id'] ?? '',
            'ip_address' => $log['ip_address'] ?? '',
            'original_source' => $log['original_source'] ?? 'Unknown',
            'override_source' => $log['override_source'] ?? '',
            'offer_name' => $log['offer_name'] ?? ('Offer #' . ($log['offer_id'] ?? 0)),
            'affiliate_name' => $log['aff_name'] ?? ('Affiliate #' . ($log['affiliate_id'] ?? 0)),
            'affiliate_code' => $log['affiliate_code'] ?? '',
            'clicked_at' => date('M d, Y H:i:s', strtotime($log['clicked_at']))
        ];
    }

    Helpers::json([
        'status' => 'success',
        'data' => [
            'total_logs' => count($formattedLogs),
            'logs' => $formattedLogs
        ]
    ]);

} catch (\Throwable $e) {
    Helpers::json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
