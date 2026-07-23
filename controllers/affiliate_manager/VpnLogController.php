<?php
Auth::check('affiliate_manager');
$pageTitle = 'VPN & Proxy Blocked Log';

// Ensure table exists
try {
    Database::query("CREATE TABLE IF NOT EXISTS `vpn_blocked_log` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`   INT UNSIGNED NULL,
        `offer_id`       INT UNSIGNED NULL,
        `offer_name`     VARCHAR(255) NULL,
        `ip_address`     VARCHAR(45) NOT NULL,
        `detection_type` VARCHAR(50) NOT NULL DEFAULT 'VPN',
        `user_agent`     VARCHAR(1000) NULL,
        `country`        VARCHAR(4) NOT NULL DEFAULT '',
        `blocked_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_blocked_at` (`blocked_at`),
        INDEX `idx_aff` (`affiliate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

$affIds = Auth::managerAffiliateIds();

// Filters
$qIp      = trim(Helpers::get('q_ip')   ?? '');
$qAff     = trim(Helpers::get('q_aff')  ?? '');
$qType    = trim(Helpers::get('q_type') ?? '');
$dateFrom = trim(Helpers::get('date_from') ?? '');
$dateTo   = trim(Helpers::get('date_to')   ?? '');

$where  = ['1=1'];
$params = [];

if (empty($affIds)) {
    $where[] = '1=0';
} else {
    $placeholders = implode(',', array_fill(0, count($affIds), '?'));
    $where[] = "v.affiliate_id IN ($placeholders)";
    $params = array_merge($params, $affIds);
}

if ($qIp)     { $where[] = 'v.ip_address LIKE ?';     $params[] = '%' . $qIp . '%'; }
if ($qType)   { $where[] = 'v.detection_type = ?';    $params[] = $qType; }
if ($qAff)    { $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CAST(v.affiliate_id AS CHAR) LIKE ? OR af.affiliate_code LIKE ?)";
                $params = array_merge($params, ['%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%','%'.$qAff.'%']); }
if ($dateFrom){ $where[] = 'DATE(v.blocked_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)  { $where[] = 'DATE(v.blocked_at) <= ?'; $params[] = $dateTo; }

$whereStr = implode(' AND ', $where);

$logs = Database::fetchAll(
    "SELECT v.*,
            CONCAT(u.first_name, ' ', u.last_name) as aff_name,
            af.affiliate_code,
            o.name as db_offer_name
     FROM vpn_blocked_log v
     LEFT JOIN affiliates af ON af.id = v.affiliate_id
     LEFT JOIN users u ON u.id = af.user_id
     LEFT JOIN offers o ON o.id = v.offer_id
     WHERE $whereStr
     ORDER BY v.blocked_at DESC
     LIMIT 1000",
    $params
);

if (Helpers::get('export') === '1') {
    $exportLogs = Database::fetchAll(
        "SELECT v.*,
                CONCAT(u.first_name, ' ', u.last_name) as aff_name,
                af.affiliate_code,
                o.name as db_offer_name
         FROM vpn_blocked_log v
         LEFT JOIN affiliates af ON af.id = v.affiliate_id
         LEFT JOIN users u ON u.id = af.user_id
         LEFT JOIN offers o ON o.id = v.offer_id
         WHERE $whereStr
         ORDER BY v.blocked_at DESC
         LIMIT 50000",
        $params
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vpn_blocked_log_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date & Time', 'Affiliate ID', 'Affiliate Name', 'Offer ID', 'Offer Name', 'IP Address', 'Country', 'Detection Type', 'User Agent']);
    foreach ($exportLogs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['blocked_at'],
            $log['affiliate_id'] ?? '',
            $log['aff_name'] ?? '',
            $log['offer_id'] ?? '',
            $log['offer_name'] ?? '',
            $log['ip_address'],
            $log['country'],
            $log['detection_type'],
            $log['user_agent'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Summary stats
if (empty($affIds)) {
    $totalBlocked = ['cnt' => 0];
    $todayBlocked = ['cnt' => 0];
    $typeCounts = [];
} else {
    $placeholders = implode(',', array_fill(0, count($affIds), '?'));
    $totalBlocked = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND blocked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        $affIds
    );
    $todayBlocked = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND DATE(blocked_at) = CURDATE()",
        $affIds
    );
    $typeCounts = Database::fetchAll(
        "SELECT detection_type, COUNT(*) as cnt FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) GROUP BY detection_type ORDER BY cnt DESC",
        $affIds
    );
}

// Handle clear log action
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    if (Helpers::postRaw('action') === 'clear_log' && !empty($affIds)) {
        $placeholders = implode(',', array_fill(0, count($affIds), '?'));
        Database::query("DELETE FROM vpn_blocked_log WHERE affiliate_id IN ($placeholders) AND blocked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)", $affIds);
        Helpers::flash('success', 'Log entries older than 30 days cleared for your assigned affiliates.');
        Helpers::redirect('/affiliate_manager/vpn-log');
    }
}

$vpnEnabled = (Config::get('config', 'vpn_detection.enabled') ?? '0') === '1';

require BASE_PATH . '/views/affiliate_manager/vpn_log/index.php';
