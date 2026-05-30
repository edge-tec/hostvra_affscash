-- Migration: 0016_super_admin_subscription_system.sql
-- Created:   2026-05-25 19:34:00
-- Description: Super Admin + Admin Subscription System

-- Create tenants table
CREATE TABLE IF NOT EXISTS `tenants` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_name`  VARCHAR(255) NOT NULL,
    `status`        ENUM('trial', 'active', 'expired', 'suspended', 'cancelled') DEFAULT 'trial',
    `custom_domain` VARCHAR(255) DEFAULT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create super_admins table
CREATE TABLE IF NOT EXISTS `super_admins` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `name`          VARCHAR(100) NOT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscription_plans table
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`             VARCHAR(100) NOT NULL,
    `price`            DECIMAL(10,2) NOT NULL,
    `currency`         CHAR(3) DEFAULT 'USD',
    `duration`         INT UNSIGNED NOT NULL COMMENT 'Duration in days',
    `max_users`        INT UNSIGNED DEFAULT 0 COMMENT '0 for unlimited',
    `max_offers`       INT UNSIGNED DEFAULT 0,
    `max_domains`      INT UNSIGNED DEFAULT 0,
    `max_click_limits` INT UNSIGNED DEFAULT 0,
    `max_storage`      INT UNSIGNED DEFAULT 0 COMMENT 'in MB',
    `status`           ENUM('active', 'disabled') DEFAULT 'active',
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subscriptions table
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `plan_id`     INT UNSIGNED NOT NULL,
    `price`       DECIMAL(10,2) NOT NULL,
    `duration`    INT UNSIGNED NOT NULL,
    `status`      ENUM('trial', 'active', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    `starts_at`   DATETIME NOT NULL,
    `ends_at`     DATETIME NOT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tenant_users table
CREATE TABLE IF NOT EXISTS `tenant_users` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`             INT UNSIGNED NOT NULL,
    `user_id`               INT UNSIGNED NOT NULL,
    `role`                  VARCHAR(50) DEFAULT 'ADMIN_OWNER',
    `force_password_change` TINYINT(1) DEFAULT 1,
    `created_at`            DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tenant_user` (`tenant_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create billing_logs table
CREATE TABLE IF NOT EXISTS `billing_logs` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `action`     VARCHAR(255) NOT NULL,
    `amount`     DECIMAL(10,2) DEFAULT 0.00,
    `details`    TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tenant_payments table
CREATE TABLE IF NOT EXISTS `tenant_payments` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `amount`     DECIMAL(12,2) NOT NULL,
    `currency`   CHAR(3) DEFAULT 'USD',
    `method`     VARCHAR(64) DEFAULT '',
    `reference`  VARCHAR(255) DEFAULT '',
    `status`     ENUM('pending', 'processing', 'paid', 'failed') DEFAULT 'pending',
    `notes`      TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tenant_invoices table
CREATE TABLE IF NOT EXISTS `tenant_invoices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(32) NOT NULL UNIQUE,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(12,2) NOT NULL,
    `tax_amount`     DECIMAL(12,2) DEFAULT 0.00,
    `total`          DECIMAL(12,2) NOT NULL,
    `status`         ENUM('draft', 'sent', 'paid', 'void') DEFAULT 'draft',
    `due_date`       DATE NULL,
    `paid_at`        DATETIME NULL,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Convert existing ENUM columns in users to VARCHAR for compatibility with new roles
ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(50) NOT NULL;

-- Safe extension of existing tables with tenant_id, admin_id, and created_by columns
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `admin_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `affiliate_managers` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `advertisers` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `affiliate_offers` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `smartlinks` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `smartlink_offers` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `postbacks` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `postback_logs` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `global_postbacks` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `registration_questions` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `activity_log` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `stats_daily` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `payments` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `email_templates` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `email_logs` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `news_reads` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `referral_codes` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `referral_signups` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `referral_commissions` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `offer_links` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `vpn_blocked_log` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `aff_daily_caps` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `aff_custom_payouts` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `aff_country_payouts` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `aff_country_device_payouts` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `conversion_optimize_rules` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `postback_test_logs` ADD COLUMN IF NOT EXISTS `tenant_id` INT UNSIGNED DEFAULT NULL;

-- Seed default plans if not exists
INSERT IGNORE INTO `subscription_plans` (`id`, `name`, `price`, `currency`, `duration`, `max_users`, `max_offers`, `max_domains`, `max_click_limits`, `max_storage`, `status`) VALUES
(1, 'Monthly Starter', 99.00, 'USD', 30, 5, 100, 3, 100000, 500, 'active'),
(2, '6 Month Professional', 499.00, 'USD', 180, 15, 500, 10, 1000000, 2048, 'active'),
(3, 'Annual Enterprise', 899.00, 'USD', 365, 99, 9999, 99, 99999999, 10240, 'active');

-- Create a default Tenant 1 for backward compatibility
INSERT IGNORE INTO `tenants` (`id`, `company_name`, `status`) VALUES (1, 'Default Affiliate Network', 'active');

-- Create default active subscription for Tenant 1 (Annual Plan, expiring in 1 year)
INSERT IGNORE INTO `subscriptions` (`id`, `tenant_id`, `plan_id`, `price`, `duration`, `status`, `starts_at`, `ends_at`) VALUES
(1, 1, 3, 899.00, 365, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 365 DAY));

-- Set existing records to default Tenant 1
UPDATE `users` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `affiliates` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `affiliate_managers` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `advertisers` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `offers` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `affiliate_offers` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `smartlinks` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `smartlink_offers` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `clicks` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `conversions` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `postbacks` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `postback_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `global_postbacks` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `registration_questions` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `notifications` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `activity_log` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `stats_daily` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `payments` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `invoices` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `tracking_domains` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `email_templates` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `email_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `news` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `news_reads` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `referral_codes` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `referral_signups` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `referral_commissions` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `offer_links` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `vpn_blocked_log` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `aff_daily_caps` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `aff_custom_payouts` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `aff_country_payouts` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `aff_country_device_payouts` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `conversion_optimize_rules` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;
UPDATE `postback_test_logs` SET `tenant_id` = 1 WHERE `tenant_id` IS NULL;

-- Seed default Super Admin credentials (password is 'superadmin123' - hashed with BCRYPT)
INSERT IGNORE INTO `super_admins` (`id`, `email`, `password_hash`, `name`) VALUES
(1, 'superadmin@affscash.net', '$2y$10$wE96c.6b52q2yvNnUuA0iOCJ6iK3C.x6q0P5rT0.B6o3L2mN1uA2m', 'Super Admin');

-- Set role super_admin also inside users table to support unified logging
INSERT IGNORE INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `first_name`, `last_name`, `company`, `created_at`) VALUES
(99999, 'superadmin@affscash.net', '$2y$10$wE96c.6b52q2yvNnUuA0iOCJ6iK3C.x6q0P5rT0.B6o3L2mN1uA2m', 'super_admin', 'active', 'Super', 'Admin', 'AffsCash SaaS', NOW());

-- Map existing Admin user to Tenant User
INSERT IGNORE INTO `tenant_users` (`tenant_id`, `user_id`, `role`, `force_password_change`)
SELECT 1, id, 'ADMIN_OWNER', 0 FROM `users` WHERE `role` = 'admin';
