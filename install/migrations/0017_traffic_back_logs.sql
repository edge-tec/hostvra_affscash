CREATE TABLE IF NOT EXISTS `traffic_back_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `click_id` CHAR(36) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
