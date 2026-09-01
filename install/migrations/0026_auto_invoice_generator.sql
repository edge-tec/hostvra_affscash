-- Migration: 0026_auto_invoice_generator.sql
-- Description: Database tables and column modifications for Automatic Invoice Generator & Billing Scheduler

-- 1. Global invoice scheduler configuration
CREATE TABLE IF NOT EXISTS `invoice_schedules` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `schedule_name`        VARCHAR(100) NOT NULL DEFAULT 'Default Global Schedule',
    `enabled`              TINYINT(1) NOT NULL DEFAULT 0,
    `frequency`            ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
    `monthly_day`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `interval_days`        SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `invoice_time`         TIME NOT NULL DEFAULT '00:00:00',
    `timezone`             VARCHAR(64) NOT NULL DEFAULT 'UTC',
    `auto_pdf`             TINYINT(1) NOT NULL DEFAULT 1,
    `auto_email`           TINYINT(1) NOT NULL DEFAULT 1,
    `invoice_prefix`       VARCHAR(32) NOT NULL DEFAULT 'AFFSCASH-INV-',
    `starting_number`      INT UNSIGNED NOT NULL DEFAULT 100001,
    `min_payout_threshold` DECIMAL(12,4) NOT NULL DEFAULT 50.0000,
    `payment_terms`        VARCHAR(50) NOT NULL DEFAULT 'net15',
    `currency`             VARCHAR(10) NOT NULL DEFAULT 'USD',
    `last_run_at`          DATETIME NULL DEFAULT NULL,
    `next_run_at`          DATETIME NULL DEFAULT NULL,
    `created_by`           INT UNSIGNED NULL DEFAULT NULL,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Affiliate specific billing rules
CREATE TABLE IF NOT EXISTS `affiliate_invoice_rules` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`     INT UNSIGNED NOT NULL,
    `override_global`  TINYINT(1) NOT NULL DEFAULT 1,
    `frequency`        ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
    `monthly_day`      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `interval_days`    SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `minimum_amount`   DECIMAL(12,4) NOT NULL DEFAULT 50.0000,
    `currency`         VARCHAR(10) NOT NULL DEFAULT 'USD',
    `payment_method`   VARCHAR(50) NULL DEFAULT NULL,
    `payment_terms`    VARCHAR(50) NOT NULL DEFAULT 'net15',
    `start_date`       DATE NULL DEFAULT NULL,
    `end_date`         DATE NULL DEFAULT NULL,
    `enabled`          TINYINT(1) NOT NULL DEFAULT 1,
    `notes`            TEXT NULL DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_affiliate_id` (`affiliate_id`),
    INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Offer specific billing rules
CREATE TABLE IF NOT EXISTS `offer_invoice_rules` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `offer_id`             INT UNSIGNED NOT NULL,
    `override_global`      TINYINT(1) NOT NULL DEFAULT 1,
    `frequency`            ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
    `monthly_day`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `interval_days`        SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `minimum_conversions`  INT UNSIGNED NOT NULL DEFAULT 1,
    `minimum_revenue`      DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `start_date`           DATE NULL DEFAULT NULL,
    `end_date`             DATE NULL DEFAULT NULL,
    `enabled`              TINYINT(1) NOT NULL DEFAULT 1,
    `notes`                TEXT NULL DEFAULT NULL,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_offer_id` (`offer_id`),
    INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Individual invoice items
CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id`       INT UNSIGNED NOT NULL,
    `conversion_id`    VARCHAR(64) NULL DEFAULT NULL,
    `conversion_db_id` INT UNSIGNED NULL DEFAULT NULL,
    `offer_id`         INT UNSIGNED NULL DEFAULT NULL,
    `offer_name`       VARCHAR(255) NULL DEFAULT NULL,
    `geo`              VARCHAR(10) NULL DEFAULT NULL,
    `payout`           DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `conversion_date`  DATETIME NULL DEFAULT NULL,
    `currency`         VARCHAR(10) NOT NULL DEFAULT 'USD',
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_invoice_id` (`invoice_id`),
    INDEX `idx_conversion_id` (`conversion_id`),
    INDEX `idx_offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Invoice action logs
CREATE TABLE IF NOT EXISTS `invoice_logs` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id`   INT UNSIGNED NULL DEFAULT NULL,
    `affiliate_id` INT UNSIGNED NULL DEFAULT NULL,
    `action`       VARCHAR(64) NOT NULL,
    `offer_count`  INT UNSIGNED NOT NULL DEFAULT 0,
    `amount`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `is_auto`      TINYINT(1) NOT NULL DEFAULT 0,
    `created_by`   INT UNSIGNED NULL DEFAULT NULL,
    `details`      TEXT NULL DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_invoice_id` (`invoice_id`),
    INDEX `idx_affiliate_id` (`affiliate_id`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Add/Ensure columns on `invoices` table
ALTER TABLE `invoices`
    ADD COLUMN IF NOT EXISTS `billing_start` DATE NULL DEFAULT NULL AFTER `period_end`,
    ADD COLUMN IF NOT EXISTS `billing_end` DATE NULL DEFAULT NULL AFTER `billing_start`,
    ADD COLUMN IF NOT EXISTS `adjustment` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `subtotal`,
    ADD COLUMN IF NOT EXISTS `chargeback_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `adjustment`,
    ADD COLUMN IF NOT EXISTS `currency` VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER `total`,
    ADD COLUMN IF NOT EXISTS `generated_at` DATETIME NULL DEFAULT NULL AFTER `paid_at`,
    ADD COLUMN IF NOT EXISTS `pdf_path` VARCHAR(255) NULL DEFAULT NULL AFTER `generated_at`,
    ADD COLUMN IF NOT EXISTS `is_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pdf_path`,
    ADD COLUMN IF NOT EXISTS `viewed_at` DATETIME NULL DEFAULT NULL AFTER `is_auto`,
    ADD COLUMN IF NOT EXISTS `email_sent_at` DATETIME NULL DEFAULT NULL AFTER `viewed_at`;

-- Seed default global schedule if empty
INSERT IGNORE INTO `invoice_schedules` (`id`, `schedule_name`, `enabled`, `frequency`, `monthly_day`, `interval_days`, `invoice_time`, `timezone`, `auto_pdf`, `auto_email`, `invoice_prefix`, `starting_number`, `min_payout_threshold`, `payment_terms`, `currency`)
VALUES (1, 'Default Global Schedule', 1, 'monthly', 1, 15, '00:00:00', 'UTC', 1, 1, 'AFFSCASH-INV-', 100001, 50.0000, 'net15', 'USD');
