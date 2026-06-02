<?php

Auth::check('admin');

$from = Helpers::get('from') ?: date('Y-m-d');
$to   = Helpers::get('to')   ?: date('Y-m-d');
$affId = (int)Helpers::get('affiliate_id');
$offerId = (int)Helpers::get('offer_id');
$limit = min((int)(Helpers::get('limit') ?: 500), 5000);

// Auto-heal table if migrator failed to run
try {
    Database::execute("
        CREATE TABLE IF NOT EXISTS `traffic_back_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `click_id` VARCHAR(255) NOT NULL DEFAULT '',
            `affiliate_id` INT UNSIGNED NULL,
            `offer_id` INT UNSIGNED NULL,
            `reason` VARCHAR(255) DEFAULT '',
            `redirect_url` TEXT,
            `ip_address` VARCHAR(45) NOT NULL,
            `country` CHAR(2) DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_affiliate_id` (`affiliate_id`),
            INDEX `idx_offer_id` (`offer_id`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_click_id` (`click_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // Ensure click_id is large enough (in case it was created as CHAR(36) previously)
    Database::execute("ALTER TABLE `traffic_back_logs` MODIFY COLUMN `click_id` VARCHAR(255) NOT NULL DEFAULT ''");
} catch (\Throwable $e) {}

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
