-- Migration: 0005_smartlink_payouts.sql
-- Description: Affiliate-specific custom payouts for smartlinks

CREATE TABLE IF NOT EXISTS `aff_smartlink_payouts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `smartlink_id` INT UNSIGNED NOT NULL,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_sl` (`affiliate_id`, `smartlink_id`),
    INDEX `idx_aslp_smartlink` (`smartlink_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smartlinks: add terms_conditions and preview_url if missing
ALTER TABLE `smartlinks`
    ADD COLUMN IF NOT EXISTS `terms_conditions` TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `preview_url`      VARCHAR(2000) DEFAULT NULL;
