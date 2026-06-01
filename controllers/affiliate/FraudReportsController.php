<?php
Auth::check('affiliate');
$pageTitle = 'My Fraud Reports';
$affId     = (int)Auth::affiliateId();

// Ensure the table exists (graceful fallback)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `fraud_report_logs` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`  INT UNSIGNED NOT NULL,
        `report_type`   ENUM('click','conversion') NOT NULL,
        `period_from`   DATETIME NOT NULL,
        `period_to`     DATETIME NOT NULL,
        `data_json`     JSON NOT NULL,
        `email_sent`    TINYINT(1) NOT NULL DEFAULT 0,
        `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_aff`     (`affiliate_id`),
        INDEX `idx_type`    (`report_type`),
        INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

require BASE_PATH . '/views/affiliate/fraud_reports.php';
