-- Migration: 0004_advanced_payout_management.sql
-- Description: Advanced payout management tables and offer-level payout fields

-- Affiliate-specific flat custom payout per offer (Advanced Payout Management)
CREATE TABLE IF NOT EXISTS `aff_custom_payouts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `offer_id`     INT UNSIGNED NOT NULL,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_offer` (`affiliate_id`, `offer_id`),
    INDEX `idx_acp_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-affiliate daily conversion cap (offer-specific or global when offer_id IS NULL)
CREATE TABLE IF NOT EXISTS `aff_daily_caps` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `offer_id`     INT UNSIGNED NULL,
    `daily_cap`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_offer_cap` (`affiliate_id`, `offer_id`),
    INDEX `idx_adc_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Country-specific payout rules (per affiliate+offer or global)
CREATE TABLE IF NOT EXISTS `payout_country_rules` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NULL,
    `offer_id`     INT UNSIGNED NULL,
    `country`      CHAR(2) NOT NULL,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pcr` (`affiliate_id`, `offer_id`, `country`),
    INDEX `idx_pcr_offer` (`offer_id`),
    INDEX `idx_pcr_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Device+country payout rules (per affiliate+offer or global)
CREATE TABLE IF NOT EXISTS `payout_device_rules` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NULL,
    `offer_id`     INT UNSIGNED NULL,
    `device`       ENUM('desktop','mobile','tablet') NOT NULL,
    `country`      CHAR(2) DEFAULT NULL,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pdr` (`affiliate_id`, `offer_id`, `device`, `country`),
    INDEX `idx_pdr_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offer-level device and country payout JSON columns
ALTER TABLE `offers`
    ADD COLUMN IF NOT EXISTS `device_payouts`  TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `country_payouts` TEXT DEFAULT NULL;

-- Offer-level conversion optimisation and auto-pause settings
ALTER TABLE `offers`
    ADD COLUMN IF NOT EXISTS `conversion_optimize`      TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `auto_pause_cr`            DECIMAL(5,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS `auto_pause_min_clicks`    INT UNSIGNED DEFAULT 100;

-- affiliate_offers: store per-affiliate device/country payout JSON overrides
ALTER TABLE `affiliate_offers`
    ADD COLUMN IF NOT EXISTS `device_payouts`  TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `country_payouts` TEXT DEFAULT NULL;
