-- Migration: 0018_super_admin_billing_and_domains.sql
-- Created:   2026-05-25 20:47:00
-- Description: Complete Super Admin Subscription, Payment Gateways (Stripe + Crypto) & Domain Assignment System

-- Add custom domain columns safely using standard SQL extensions
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `admin_main_domain` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `admin_tracking_domain` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `domain_verified` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `ssl_active` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `domain_health` VARCHAR(50) NOT NULL DEFAULT 'unknown';
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `trial_ends_at` DATETIME DEFAULT NULL;

-- Enforce uniqueness on the domain fields safely via try-catch or single unique index
CREATE UNIQUE INDEX IF NOT EXISTS `idx_main_domain` ON `tenants` (`admin_main_domain`);
CREATE UNIQUE INDEX IF NOT EXISTS `idx_track_domain` ON `tenants` (`admin_tracking_domain`);

-- Create gateway_settings table
CREATE TABLE IF NOT EXISTS `gateway_settings` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gateway_name`   VARCHAR(50) NOT NULL COMMENT 'stripe, crypto, trial',
    `setting_key`    VARCHAR(100) NOT NULL,
    `setting_value`  TEXT NULL,
    UNIQUE KEY `uq_gw_key` (`gateway_name`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create crypto_payments table for verification
CREATE TABLE IF NOT EXISTS `crypto_payments` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(16,8) NOT NULL,
    `currency`       VARCHAR(10) NOT NULL,
    `address`        VARCHAR(255) NOT NULL,
    `tx_hash`        VARCHAR(255) DEFAULT NULL,
    `status`         VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at`   DATETIME DEFAULT NULL,
    INDEX `idx_tenant_crypto` (`tenant_id`),
    INDEX `idx_status_crypto` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create email_verifications table for OTP/wizard flows
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`          VARCHAR(255) NOT NULL,
    `code`           VARCHAR(10) NOT NULL,
    `attempts`       TINYINT NOT NULL DEFAULT 0,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    `verified_at`    DATETIME DEFAULT NULL,
    INDEX `idx_email_verify` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add subscription auto-renew and stripe provider columns safely
ALTER TABLE `subscriptions` ADD COLUMN IF NOT EXISTS `stripe_subscription_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `subscriptions` ADD COLUMN IF NOT EXISTS `auto_renew` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `subscriptions` ADD COLUMN IF NOT EXISTS `billing_provider` VARCHAR(50) NOT NULL DEFAULT 'manual';

-- Seed default gateway setting values
INSERT IGNORE INTO `gateway_settings` (`gateway_name`, `setting_key`, `setting_value`) VALUES
('stripe', 'enabled', '0'),
('stripe', 'sandbox_mode', '1'),
('stripe', 'secret_key', 'sk_test_mock_keys_12345'),
('stripe', 'webhook_secret', 'whsec_mock_signatures_abcde'),
('crypto', 'enabled', '0'),
('crypto', 'btc_address', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'),
('crypto', 'eth_address', '0x71C7656EC7ab88b098defB751B7401B5f6d8976F'),
('crypto', 'usdt_trc20', 'TR7NHqJdjGdNF2DzFauk3FGLqmiejGLARi'),
('crypto', 'usdt_erc20', '0x71C7656EC7ab88b098defB751B7401B5f6d8976F'),
('crypto', 'bnb_address', '0x71C7656EC7ab88b098defB751B7401B5f6d8976F'),
('crypto', 'ltc_address', 'LXPp1nZf2N8yR3q7yS2G1pG5rW9F7nK9yF'),
('crypto', 'doge_address', 'D6t2Dq3r2yN8S3q7yS2G1pG5rW9F7nK9yF'),
('trial', 'duration_days', '14'),
('trial', 'enabled', '1');
