-- CPA Affiliate Marketing Tracking Software
-- Database Schema v1.0
-- No FOREIGN KEY constraints for maximum hosting compatibility
-- Application-level referential integrity is enforced in PHP

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Users (all roles)
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','affiliate','advertiser','affiliate_manager') NOT NULL,
    `status`        ENUM('pending','active','suspended','rejected') DEFAULT 'pending',
    `first_name`    VARCHAR(100) DEFAULT '',
    `last_name`     VARCHAR(100) DEFAULT '',
    `company`       VARCHAR(255) DEFAULT '',
    `phone`         VARCHAR(50) DEFAULT '',
    `country`       CHAR(2) DEFAULT '',
    `timezone`      VARCHAR(64) DEFAULT 'UTC',
    `last_login`    DATETIME NULL,
    `profile_pic`   VARCHAR(255) DEFAULT '',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Devices (For Push Notifications)
CREATE TABLE IF NOT EXISTS `user_devices` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `device_token`  VARCHAR(500) NOT NULL,
    `platform`      VARCHAR(50) DEFAULT 'android',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_token` (`device_token`),
    INDEX `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliates extended profile
CREATE TABLE IF NOT EXISTS `affiliates` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`               INT UNSIGNED NOT NULL UNIQUE,
    `affiliate_code`        VARCHAR(32) NOT NULL UNIQUE,
    `manager_id`            INT UNSIGNED NULL,
    `traffic_sources`       TEXT,
    `monthly_volume`        VARCHAR(50) DEFAULT '',
    `payment_method`        VARCHAR(100) NOT NULL DEFAULT '',
    `payment_details`       TEXT,
    `payment_threshold`     DECIMAL(10,2) DEFAULT 100.00,
    `payment_frequency`     ENUM('weekly','biweekly','monthly') DEFAULT 'monthly',
    `balance`               DECIMAL(12,4) DEFAULT 0.0000,
    `referred_by`           INT UNSIGNED NULL,
    `registration_answers`  TEXT,
    `fraud_score`           TINYINT UNSIGNED DEFAULT 0,
    `notes`                 TEXT,
    `global_postback_url`   VARCHAR(2000) DEFAULT NULL,
    `allow_email_change`    TINYINT(1) DEFAULT 0,
    `payment_terms`         ENUM('weekly','net15','net30','monthly') DEFAULT 'monthly',
    `manager_assigned_at`   DATETIME DEFAULT NULL,
    `created_at`            DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_affiliate_code` (`affiliate_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate Managers extended profile
CREATE TABLE IF NOT EXISTS `affiliate_managers` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT UNSIGNED NOT NULL UNIQUE,
    `permissions`     TEXT NULL,
    `notes`           TEXT NULL,
    `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `balance`         DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertisers extended profile
CREATE TABLE IF NOT EXISTS `advertisers` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT UNSIGNED NOT NULL UNIQUE,
    `advertiser_code`   VARCHAR(32) NOT NULL UNIQUE,
    `billing_email`     VARCHAR(255) DEFAULT '',
    `address`           TEXT,
    `vat_number`        VARCHAR(64) DEFAULT '',
    `credit_limit`      DECIMAL(12,2) DEFAULT 0.00,
    `balance`           DECIMAL(12,4) DEFAULT 0.0000,
    `notes`             TEXT,
    `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers
CREATE TABLE IF NOT EXISTS `offers` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `advertiser_id`     INT UNSIGNED NOT NULL,
    `name`              VARCHAR(255) NOT NULL,
    `description`       TEXT,
    `offer_url`         TEXT NOT NULL,
    `landing_pages`     TEXT NULL,
    `preview_url`       TEXT,
    `category`          VARCHAR(100) DEFAULT '',
    `geo_targeting`     TEXT,
    `device_targeting`  TEXT,
    `os_targeting`      TEXT,
    `payout_type`       ENUM('CPA','CPC','CPL','RevShare') DEFAULT 'CPA',
    `payout_amount`     DECIMAL(10,4) DEFAULT 0.0000,
    `revenue_amount`    DECIMAL(10,4) DEFAULT 0.0000,
    `country_payouts`   TEXT NULL,
    `device_payouts`    TEXT NULL,
    `conversion_optimize` TINYINT(1) DEFAULT 0,
    `auto_pause_cr`         DECIMAL(5,2) DEFAULT 0.00,
    `auto_pause_min_clicks` INT UNSIGNED DEFAULT 100,
    `currency`          CHAR(3) DEFAULT 'USD',
    `daily_cap`         INT UNSIGNED DEFAULT 0,
    `monthly_cap`       INT UNSIGNED DEFAULT 0,
    `total_cap`         INT UNSIGNED DEFAULT 0,
    `daily_click_cap`   INT UNSIGNED DEFAULT 0,
    `status`            ENUM('active','paused','expired','pending','deleted') DEFAULT 'pending',
    `visibility`        ENUM('public','private','require_approval') DEFAULT 'public',
    `tracking_domain`   VARCHAR(255) DEFAULT '',
    `expiry_date`       DATE NULL,
    `require_approval`  TINYINT(1) DEFAULT 0,
    `thumbnail`         VARCHAR(255) DEFAULT '',
    `terms`             TEXT,
    `created_by`        INT UNSIGNED NULL,
    `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_advertiser_id` (`advertiser_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate offer access
CREATE TABLE IF NOT EXISTS `affiliate_offers` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`  INT UNSIGNED NOT NULL,
    `offer_id`      INT UNSIGNED NOT NULL,
    `status`        ENUM('pending','approved','rejected','blocked') DEFAULT 'pending',
    `custom_payout` DECIMAL(10,4) NULL,
    `campaign_name` VARCHAR(100) DEFAULT '',
    `country_payouts` TEXT NULL,
    `device_payouts`  TEXT NULL,
    `approved_at`   DATETIME NULL,
    `approved_by`   INT UNSIGNED NULL,
    `notes`         TEXT NULL,
    UNIQUE KEY `uq_aff_offer` (`affiliate_id`,`offer_id`),
    INDEX `idx_affiliate_id` (`affiliate_id`),
    INDEX `idx_offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smartlinks
CREATE TABLE IF NOT EXISTS `smartlinks` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255) NOT NULL,
    `slug`          VARCHAR(64) NOT NULL UNIQUE,
    `created_by`    INT UNSIGNED NOT NULL,
    `description`   TEXT,
    `status`        ENUM('active','paused') DEFAULT 'active',
    `rotation_type` ENUM('weight','round_robin','geo','device') DEFAULT 'weight',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers inside a smartlink
CREATE TABLE IF NOT EXISTS `smartlink_offers` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `smartlink_id`  INT UNSIGNED NOT NULL,
    `offer_id`      INT UNSIGNED NOT NULL,
    `weight`        TINYINT UNSIGNED DEFAULT 10,
    `geo_rules`     TEXT NULL,
    `device_rules`  TEXT NULL,
    INDEX `idx_smartlink_id` (`smartlink_id`),
    INDEX `idx_offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clicks
CREATE TABLE IF NOT EXISTS `clicks` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `click_id`      CHAR(36) NOT NULL UNIQUE,
    `offer_id`      INT UNSIGNED NOT NULL,
    `affiliate_id`  INT UNSIGNED NOT NULL,
    `smartlink_id`  INT UNSIGNED NULL,
    `sub1`          VARCHAR(255) DEFAULT '',
    `sub2`          VARCHAR(255) DEFAULT '',
    `sub3`          VARCHAR(255) DEFAULT '',
    `sub4`          VARCHAR(255) DEFAULT '',
    `sub5`          VARCHAR(255) DEFAULT '',
    `sub6`          VARCHAR(500) DEFAULT NULL,
    `source`        VARCHAR(255) DEFAULT '',
    `original_source` VARCHAR(255) DEFAULT NULL,
    `source_override_applied` TINYINT(1) DEFAULT 0,
    `traffic_source`      VARCHAR(50)  DEFAULT 'Unknown',
    `traffic_source_type` VARCHAR(50)  DEFAULT 'Unknown',
    `override_source`     VARCHAR(50)  DEFAULT NULL,
    `override_rule_id`    INT UNSIGNED DEFAULT NULL,
    `detected_by`         VARCHAR(100) DEFAULT NULL,
    `utm_source`          VARCHAR(255) DEFAULT NULL,
    `utm_medium`          VARCHAR(255) DEFAULT NULL,
    `utm_campaign`        VARCHAR(255) DEFAULT NULL,
    `utm_content`         VARCHAR(255) DEFAULT NULL,
    `utm_term`            VARCHAR(255) DEFAULT NULL,
    `landing_page_idx` TINYINT UNSIGNED NULL,
    `ip_address`    VARCHAR(45) NOT NULL,
    `user_agent`    TEXT,
    `referer`       TEXT,
    `country`       CHAR(2) DEFAULT '',
    `region`        VARCHAR(100) DEFAULT '',
    `city`          VARCHAR(100) DEFAULT '',
    `isp`           VARCHAR(255) DEFAULT '',
    `device_type`   ENUM('desktop','mobile','tablet','bot','unknown') DEFAULT 'unknown',
    `os`            VARCHAR(64) DEFAULT '',
    `browser`       VARCHAR(64) DEFAULT '',
    `is_unique`     TINYINT(1) DEFAULT 1,
    `is_fraud`      TINYINT(1) DEFAULT 0,
    `fraud_score`   TINYINT UNSIGNED DEFAULT 0,
    `fraud_reasons` TEXT NULL,
    `payout`        DECIMAL(10,4) DEFAULT 0.0000,
    `revenue`       DECIMAL(10,4) DEFAULT 0.0000,
    `status`        ENUM('valid','fraud','duplicate','blocked') DEFAULT 'valid',
    `clicked_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_click_id` (`click_id`),
    INDEX `idx_offer_affiliate` (`offer_id`,`affiliate_id`),
    INDEX `idx_clicked_at` (`clicked_at`),
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_affiliate_id` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversions
CREATE TABLE IF NOT EXISTS `conversions` (
    `id`                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversion_id`     CHAR(36) NOT NULL UNIQUE,
    `click_id`          CHAR(36) NOT NULL,
    `offer_id`          INT UNSIGNED NOT NULL,
    `affiliate_id`      INT UNSIGNED NOT NULL,
    `smartlink_id`      INT UNSIGNED NULL DEFAULT NULL,
    `advertiser_id`     INT UNSIGNED NOT NULL,
    `payout`            DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `revenue`           DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `currency`          CHAR(3) DEFAULT 'USD',
    `status`            ENUM('pending','approved','rejected','chargebacked') DEFAULT 'pending',
    `conversion_data`   TEXT NULL,
    `transaction_id`    VARCHAR(255) NULL,
    `goal_name`         VARCHAR(100) NULL,
    `is_fraud`          TINYINT(1) DEFAULT 0,
    `postback_sent`     TINYINT(1) DEFAULT 0,
    `postback_sent_at`  DATETIME NULL,
    `ip_address`        VARCHAR(45) DEFAULT '',
    `converted_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    `approved_at`       DATETIME NULL,
    INDEX `idx_click_id` (`click_id`),
    INDEX `idx_affiliate_offer` (`affiliate_id`,`offer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_converted_at` (`converted_at`),
    INDEX `idx_offer_id` (`offer_id`),
    INDEX `idx_smartlink_id` (`smartlink_id`),
    INDEX `idx_aff_converted` (`affiliate_id`,`converted_at`),
    INDEX `idx_offer_converted` (`offer_id`,`converted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manager commission ledger (one row per approved conversion)
CREATE TABLE IF NOT EXISTS `manager_commissions` (
    `id`                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `manager_id`         INT UNSIGNED     NOT NULL,
    `affiliate_id`       INT UNSIGNED     NOT NULL,
    `conversion_id`      BIGINT UNSIGNED  NOT NULL,
    `offer_id`           INT UNSIGNED     NOT NULL,
    `is_smartlink`       TINYINT(1)       NOT NULL DEFAULT 0,
    `advertiser_revenue` DECIMAL(10,4)    NOT NULL DEFAULT 0.0000,
    `affiliate_payout`   DECIMAL(10,4)    NOT NULL DEFAULT 0.0000,
    `net_profit`         DECIMAL(10,4)    NOT NULL DEFAULT 0.0000,
    `commission_rate`    DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    `commission_amount`  DECIMAL(10,4)    NOT NULL DEFAULT 0.0000,
    `status`             ENUM('pending','approved','paid','reversed') NOT NULL DEFAULT 'pending',
    `calculated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mgr_comm_conversion` (`conversion_id`),
    KEY `idx_mgr_comm_manager`   (`manager_id`),
    KEY `idx_mgr_comm_affiliate` (`affiliate_id`),
    KEY `idx_mgr_comm_status`    (`status`),
    KEY `idx_mgr_comm_date`      (`calculated_at`),
    KEY `idx_mgr_comm_offer`     (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate postbacks
CREATE TABLE IF NOT EXISTS `postbacks` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`   INT UNSIGNED NOT NULL,
    `offer_id`       INT UNSIGNED NULL,
    `event`          ENUM('conversion','click','rejection','chargeback') DEFAULT 'conversion',
    `method`         ENUM('GET','POST') DEFAULT 'GET',
    `url`            TEXT NOT NULL,
    `status`         ENUM('active','inactive') DEFAULT 'active',
    `fire_on_status` VARCHAR(64) DEFAULT 'approved',
    `admin_status`   ENUM('pending','approved','rejected') DEFAULT 'approved',
    `admin_note`     VARCHAR(255) DEFAULT NULL,
    `last_fired_at`  DATETIME NULL,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_affiliate_id` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix existing installs: add admin_status and admin_note if missing
-- DEFAULT 'approved' ensures new postbacks are active immediately (admin can reject if needed)
ALTER TABLE `postbacks` ADD COLUMN IF NOT EXISTS `admin_status` ENUM('pending','approved','rejected') DEFAULT 'approved';
ALTER TABLE `postbacks` ADD COLUMN IF NOT EXISTS `admin_note` VARCHAR(255) DEFAULT NULL;
-- Approve any existing active postbacks that pre-date the admin_status column
UPDATE `postbacks` SET `admin_status`='approved' WHERE `status`='active' AND (`admin_status` IS NULL OR `admin_status`='' OR `admin_status`='pending');

-- Postback fire logs
CREATE TABLE IF NOT EXISTS `postback_logs` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `postback_id`   INT UNSIGNED NOT NULL,
    `conversion_id` CHAR(36) NOT NULL,
    `fired_url`     TEXT NOT NULL,
    `http_status`   SMALLINT UNSIGNED NULL,
    `response_body` TEXT,
    `is_success`    TINYINT(1) DEFAULT 0,
    `attempt_count` TINYINT UNSIGNED DEFAULT 1,
    `fired_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_postback_id` (`postback_id`),
    INDEX `idx_conversion_id` (`conversion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global postbacks (admin-defined)
CREATE TABLE IF NOT EXISTS `global_postbacks` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(255) DEFAULT '',
    `event`      ENUM('conversion','click','rejection') DEFAULT 'conversion',
    `method`     ENUM('GET','POST') DEFAULT 'GET',
    `url`        TEXT NOT NULL,
    `status`     ENUM('active','inactive','pending','rejected') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix existing installs: add pending/rejected to global_postbacks status ENUM
ALTER TABLE `global_postbacks` MODIFY COLUMN `status` ENUM('active','inactive','pending','rejected') DEFAULT 'pending';

-- Fraud detection logs
CREATE TABLE IF NOT EXISTS `fraud_logs` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `click_id`      CHAR(36) NOT NULL,
    `ip_address`    VARCHAR(45) NOT NULL,
    `fraud_score`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_vpn`        TINYINT(1) DEFAULT 0,
    `is_proxy`      TINYINT(1) DEFAULT 0,
    `is_tor`        TINYINT(1) DEFAULT 0,
    `is_bot`        TINYINT(1) DEFAULT 0,
    `is_datacenter` TINYINT(1) DEFAULT 0,
    `isp`           VARCHAR(255) DEFAULT '',
    `country`       CHAR(2) DEFAULT '',
    `api_response`  TEXT,
    `action_taken`  ENUM('allow','flag','block') DEFAULT 'allow',
    `checked_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_click_id` (`click_id`),
    INDEX `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registration questions
CREATE TABLE IF NOT EXISTS `registration_questions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `target_role`   ENUM('affiliate','advertiser','both') DEFAULT 'affiliate',
    `question_text` VARCHAR(500) NOT NULL,
    `field_type`    ENUM('text','textarea','select','radio','checkbox','url') DEFAULT 'text',
    `options`       TEXT NULL,
    `is_required`   TINYINT(1) DEFAULT 1,
    `sort_order`    TINYINT UNSIGNED DEFAULT 0,
    `is_active`     TINYINT(1) DEFAULT 1,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rq_text_role` (`question_text`(100), `target_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NULL,
    `target_role`   VARCHAR(20) NULL,
    `type`          ENUM('info','success','warning','danger') DEFAULT 'info',
    `title`         VARCHAR(255) NOT NULL,
    `message`       TEXT NOT NULL,
    `link`          VARCHAR(500) NULL,
    `is_read`       TINYINT(1) DEFAULT 0,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_read` (`user_id`,`is_read`),
    INDEX `idx_target_role` (`target_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity / audit log
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NULL,
    `action`        VARCHAR(255) NOT NULL,
    `entity_type`   VARCHAR(64) DEFAULT '',
    `entity_id`     INT UNSIGNED NULL,
    `old_data`      TEXT NULL,
    `new_data`      TEXT NULL,
    `ip_address`    VARCHAR(45) DEFAULT '',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily stats cache
CREATE TABLE IF NOT EXISTS `stats_daily` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date`     DATE NOT NULL,
    `affiliate_id`  INT UNSIGNED NOT NULL,
    `offer_id`      INT UNSIGNED NOT NULL,
    `clicks`        INT UNSIGNED DEFAULT 0,
    `unique_clicks` INT UNSIGNED DEFAULT 0,
    `conversions`   INT UNSIGNED DEFAULT 0,
    `approved`      INT UNSIGNED DEFAULT 0,
    `rejected`      INT UNSIGNED DEFAULT 0,
    `payout`        DECIMAL(12,4) DEFAULT 0.0000,
    `revenue`       DECIMAL(12,4) DEFAULT 0.0000,
    `fraud_clicks`  INT UNSIGNED DEFAULT 0,
    `impressions`   INT UNSIGNED DEFAULT 0,
    UNIQUE KEY `uq_daily` (`stat_date`,`affiliate_id`,`offer_id`),
    INDEX `idx_stat_date` (`stat_date`),
    INDEX `idx_affiliate` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`  INT UNSIGNED NOT NULL,
    `amount`        DECIMAL(12,4) NOT NULL,
    `currency`      CHAR(3) DEFAULT 'USD',
    `method`        VARCHAR(64) DEFAULT '',
    `reference`     VARCHAR(255) DEFAULT '',
    `status`        ENUM('pending','processing','paid','failed') DEFAULT 'pending',
    `period_start`  DATE NULL,
    `period_end`    DATE NULL,
    `notes`         TEXT,
    `paid_at`       DATETIME NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_affiliate_id` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(32) NOT NULL UNIQUE,
    `type`           VARCHAR(50) NOT NULL DEFAULT 'affiliate_payout',
    `affiliate_id`   INT UNSIGNED NULL,
    `advertiser_id`  INT UNSIGNED NULL,
    `manager_id`     INT UNSIGNED NULL,
    `balance_before` DECIMAL(10,4) DEFAULT NULL,
    `balance_after`  DECIMAL(10,4) DEFAULT NULL,
    `period_start`   DATE NULL,
    `period_end`     DATE NULL,
    `items`          TEXT NULL,
    `subtotal`       DECIMAL(12,4) DEFAULT 0.0000,
    `tax_rate`       DECIMAL(5,2)  DEFAULT 0.00,
    `tax_amount`     DECIMAL(12,4) DEFAULT 0.0000,
    `total`          DECIMAL(12,4) DEFAULT 0.0000,
    `status`         ENUM('draft','sent','paid','void') DEFAULT 'draft',
    `notes`          TEXT NULL,
    `due_date`       DATE NULL,
    `paid_at`        DATETIME NULL,
    `created_by`     INT UNSIGNED NULL,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_affiliate_id` (`affiliate_id`),
    INDEX `idx_advertiser_id` (`advertiser_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_methods` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(100) NOT NULL,
    `description`  TEXT,
    `instructions` TEXT,
    `fields`       TEXT NULL,
    `is_active`    TINYINT(1) DEFAULT 1,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tracking Domains
CREATE TABLE IF NOT EXISTS `tracking_domains` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain`     VARCHAR(255) NOT NULL UNIQUE,
    `label`      VARCHAR(100) DEFAULT '',
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active`  TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Templates
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type`  VARCHAR(64) NOT NULL UNIQUE,
    `label`       VARCHAR(150) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `html_body`   MEDIUMTEXT NOT NULL,
    `is_active`   TINYINT(1) DEFAULT 1,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Logs
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `to_email`    VARCHAR(255) NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `event_type`  VARCHAR(64) DEFAULT '',
    `status`      ENUM('sent','failed') DEFAULT 'sent',
    `error`       TEXT NULL,
    `sent_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default email templates
INSERT IGNORE INTO `email_templates` (`event_type`, `label`, `subject`, `html_body`, `is_active`) VALUES
('affiliate_created',  'Account Created',       'Welcome to {{site_name}} — Your account is pending review',
'<h2>Welcome, {{name}}!</h2><p>Thank you for registering with <strong>{{site_name}}</strong>.</p><p>Your affiliate account has been created and is currently <strong>pending review</strong>. Our team will review your application and get back to you shortly.</p><p>Your affiliate code: <strong>{{affiliate_code}}</strong></p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('affiliate_approved', 'Account Approved',      'Great news — Your {{site_name}} affiliate account is approved!',
'<h2>You are approved, {{name}}!</h2><p>Congratulations! Your affiliate account on <strong>{{site_name}}</strong> has been approved.</p><p>You can now log in and start promoting offers.</p><p><a href="{{app_url}}/login" style="background:#4F46E5;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">Log In Now</a></p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('affiliate_rejected', 'Account Rejected',      'Your {{site_name}} affiliate application was not approved',
'<h2>Hello {{name}},</h2><p>We regret to inform you that your affiliate application on <strong>{{site_name}}</strong> has not been approved at this time.</p><p>If you believe this is a mistake or would like more information, please contact our support team.</p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('affiliate_suspended','Account Suspended',     'Your {{site_name}} affiliate account has been suspended',
'<h2>Hello {{name}},</h2><p>Your affiliate account on <strong>{{site_name}}</strong> has been <strong>suspended</strong>.</p><p>Please contact support if you have any questions.</p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('invoice_created',    'New Invoice',           'Invoice {{invoice_number}} from {{site_name}}',
'<h2>Hello {{name}},</h2><p>A new invoice has been generated for you on <strong>{{site_name}}</strong>.</p><table style="border-collapse:collapse;width:100%;max-width:480px"><tr><td style="padding:8px;color:#64748B">Invoice #</td><td style="padding:8px"><strong>{{invoice_number}}</strong></td></tr><tr><td style="padding:8px;color:#64748B">Amount</td><td style="padding:8px"><strong>{{total}}</strong></td></tr><tr><td style="padding:8px;color:#64748B">Due Date</td><td style="padding:8px">{{due_date}}</td></tr></table><p><a href="{{app_url}}/affiliate/invoices" style="background:#4F46E5;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">View Invoice</a></p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('invoice_paid',       'Invoice Paid',          'Payment confirmed for Invoice {{invoice_number}}',
'<h2>Hello {{name}},</h2><p>We are pleased to confirm that your invoice <strong>{{invoice_number}}</strong> of <strong>{{total}}</strong> on <strong>{{site_name}}</strong> has been marked as <strong>paid</strong>.</p><p><a href="{{app_url}}/affiliate/invoices" style="background:#10B981;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">View Invoice</a></p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('offer_approval_requested', 'Offer Access Requested', 'Affiliate {{name}} has requested access to offer: {{offer_name}}',
'<h2>Offer Access Request</h2><p>Affiliate <strong>{{name}}</strong> ({{email}}) has requested access to the offer: <strong>{{offer_name}}</strong>.</p><p><a href="{{app_url}}/admin/affiliates/{{affiliate_id}}" style="background:#4F46E5;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">Review Request</a></p>', 1),

('offer_approved',     'Offer Access Approved', 'You have been approved for: {{offer_name}}',
'<h2>Hello {{name}},</h2><p>Great news! Your request to promote the offer <strong>{{offer_name}}</strong> on <strong>{{site_name}}</strong> has been <strong>approved</strong>.</p><p><a href="{{app_url}}/affiliate/offers" style="background:#10B981;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">View Offers</a></p><p>Thanks,<br>{{site_name}} Team</p>', 1),

('news_published', 'News Published', '{{title}} — {{site_name}}',
'<div style="font-family:sans-serif;max-width:600px;margin:0 auto">
<div style="background:linear-gradient(135deg,#7C3AED,#6D28D9);padding:28px 32px;border-radius:12px 12px 0 0">
  {{logo_html}}
  <h1 style="color:#fff;margin:0;font-size:22px;font-weight:800">{{site_name}}</h1>
</div>
<div style="background:#fff;padding:28px 32px;border:1px solid #E2E8F0;border-top:none">
  {{#if_hot}}<div style="display:inline-block;background:#FEE2E2;color:#B91C1C;border-radius:20px;padding:4px 16px;font-size:12px;font-weight:700;margin-bottom:16px">🔥 HOT NEWS</div>{{/if_hot}}
  {{#if_image}}<img src="{{image}}" alt="" style="width:100%;border-radius:8px;margin-bottom:20px;max-height:300px;object-fit:cover">{{/if_image}}
  <h2 style="font-size:20px;font-weight:700;color:#0F172A;margin:0 0 12px">{{title}}</h2>
  <p style="font-size:14px;color:#475569;line-height:1.7;margin:0 0 20px">{{summary}}</p>
  <a href="{{link}}" style="display:inline-block;background:#4F46E5;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">Read Full Article →</a>
</div>
<div style="background:#F8FAFC;padding:16px 32px;border-radius:0 0 12px 12px;border:1px solid #E2E8F0;border-top:none;text-align:center;font-size:12px;color:#94A3B8">
  You received this because you are an affiliate on {{site_name}}.<br>
  <a href="{{app_url}}/affiliate/news" style="color:#4F46E5">View all news</a>
</div>
</div>', 1);

-- ALTER TABLE for existing installs
ALTER TABLE `offers`
    ADD COLUMN IF NOT EXISTS `country_payouts` TEXT NULL AFTER `revenue_amount`,
    ADD COLUMN IF NOT EXISTS `device_payouts` TEXT NULL AFTER `country_payouts`,
    ADD COLUMN IF NOT EXISTS `conversion_optimize` TINYINT(1) DEFAULT 0 AFTER `device_payouts`;

ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `auto_pause_cr` DECIMAL(5,2) DEFAULT 0.00;
ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `auto_pause_min_clicks` INT UNSIGNED DEFAULT 100;

ALTER TABLE `affiliate_offers`
    ADD COLUMN IF NOT EXISTS `campaign_name` VARCHAR(100) DEFAULT '' AFTER `custom_payout`,
    ADD COLUMN IF NOT EXISTS `country_payouts` TEXT NULL AFTER `campaign_name`,
    ADD COLUMN IF NOT EXISTS `device_payouts` TEXT NULL AFTER `country_payouts`,
    ADD COLUMN IF NOT EXISTS `notes` TEXT NULL AFTER `device_payouts`;

ALTER TABLE `affiliate_offers` MODIFY COLUMN `status` ENUM('pending','approved','rejected','blocked') DEFAULT 'pending';

ALTER TABLE `clicks`
    ADD COLUMN IF NOT EXISTS `landing_page_idx` TINYINT UNSIGNED NULL AFTER `sub5`,
    ADD COLUMN IF NOT EXISTS `source` VARCHAR(255) DEFAULT '' AFTER `sub5`;

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_pic` VARCHAR(255) DEFAULT '';
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `allow_email_change` TINYINT(1) DEFAULT 0;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `payment_terms` ENUM('weekly','net15','net30','monthly') DEFAULT 'monthly';
ALTER TABLE `affiliate_managers` ADD COLUMN IF NOT EXISTS `commission_rate` DECIMAL(5,2) DEFAULT 0.00;

-- email_templates / email_logs are CREATE IF NOT EXISTS — no ALTER needed for fresh
-- For existing installs that had older schema, ensure columns exist:
ALTER TABLE `email_templates` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1;
ALTER TABLE `email_logs` ADD COLUMN IF NOT EXISTS `error` TEXT NULL;

-- Insert news email template for existing installs
INSERT IGNORE INTO `email_templates` (`event_type`, `label`, `subject`, `html_body`, `is_active`) VALUES
('news_published', 'News Published', '{{title}} — {{site_name}}',
'<div style="font-family:sans-serif;max-width:600px;margin:0 auto"><div style="background:linear-gradient(135deg,#7C3AED,#6D28D9);padding:28px 32px;border-radius:12px 12px 0 0">{{logo_html}}<h1 style="color:#fff;margin:0;font-size:22px;font-weight:800">{{site_name}}</h1></div><div style="background:#fff;padding:28px 32px;border:1px solid #E2E8F0;border-top:none"><h2 style="font-size:20px;font-weight:700;color:#0F172A;margin:0 0 12px">{{title}}</h2><p style="font-size:14px;color:#475569;line-height:1.7;margin:0 0 20px">{{summary}}</p><a href="{{link}}" style="display:inline-block;background:#4F46E5;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px">Read Full Article →</a></div><div style="background:#F8FAFC;padding:16px 32px;border-radius:0 0 12px 12px;border:1px solid #E2E8F0;border-top:none;text-align:center;font-size:12px;color:#94A3B8">You received this because you are an affiliate on {{site_name}}.</div></div>',
1);

-- Missing columns on conversions (required by auto-hide feature)
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `is_hidden`   TINYINT(1)   NOT NULL DEFAULT 0;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `hide_reason` VARCHAR(255) NOT NULL DEFAULT '';

-- Global postback logs (for debugging / success tracking)
CREATE TABLE IF NOT EXISTS `global_postback_logs` (
    `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `global_postback_id` INT UNSIGNED NOT NULL,
    `conversion_id`      CHAR(36) NOT NULL,
    `fired_url`          TEXT NOT NULL,
    `http_status`        SMALLINT UNSIGNED NULL,
    `response_body`      TEXT,
    `is_success`         TINYINT(1) DEFAULT 0,
    `attempt_count`      TINYINT UNSIGNED DEFAULT 1,
    `fired_at`           DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_global_pb_id`   (`global_postback_id`),
    INDEX `idx_global_conv_id` (`conversion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- conversion_autohide_rules (used in postback.php to check auto-hide rules at conversion time)
CREATE TABLE IF NOT EXISTS `conversion_autohide_rules` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`         VARCHAR(255) NOT NULL,
    `type`         ENUM('global','offer','affiliate') NOT NULL DEFAULT 'global',
    `offer_id`     INT UNSIGNED NULL,
    `affiliate_id` INT UNSIGNED NULL,
    `hide_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `reason`       VARCHAR(500) DEFAULT '',
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type`      (`type`),
    INDEX `idx_offer`     (`offer_id`),
    INDEX `idx_affiliate` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- offer_links (geo/device-specific landing page URLs per offer)
CREATE TABLE IF NOT EXISTS `offer_links` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `offer_id`     INT UNSIGNED NOT NULL,
    `device_type`  ENUM('desktop','mobile','tablet','all') NOT NULL DEFAULT 'all',
    `geo_country`  VARCHAR(2) NOT NULL DEFAULT '',
    `label`        VARCHAR(100) DEFAULT '',
    `offer_url`    TEXT NOT NULL,
    `payout_rate`  DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `revenue_rate` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `status`       ENUM('active','inactive') DEFAULT 'active',
    `sort_order`   TINYINT UNSIGNED DEFAULT 0,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_offer_id`     (`offer_id`),
    INDEX `idx_offer_device` (`offer_id`,`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- vpn_blocked_log (logs VPN/proxy blocked click attempts)
CREATE TABLE IF NOT EXISTS `vpn_blocked_log` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`   INT UNSIGNED NULL,
    `offer_id`       INT UNSIGNED NULL,
    `offer_name`     VARCHAR(255) NULL,
    `smartlink_id`   INT UNSIGNED NULL,
    `smartlink_name` VARCHAR(255) NULL,
    `ip_address`     VARCHAR(45) NOT NULL,
    `detection_type` VARCHAR(50) NOT NULL DEFAULT 'VPN',
    `user_agent`     VARCHAR(1000) NULL,
    `country`        VARCHAR(4) NOT NULL DEFAULT '',
    `blocked_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_blocked_at` (`blocked_at`),
    INDEX `idx_aff`        (`affiliate_id`),
    INDEX `idx_smartlink`  (`smartlink_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default registration questions
INSERT IGNORE INTO `registration_questions` (`target_role`,`question_text`,`field_type`,`options`,`is_required`,`sort_order`) VALUES
('affiliate','What traffic sources do you use?','checkbox','["SEO","Social Media","Email Marketing","PPC","Native Ads","Push Notifications","Other"]',1,1),
('affiliate','What is your approximate monthly traffic volume?','select','["Less than 10,000","10,000 - 100,000","100,000 - 1,000,000","Over 1,000,000"]',1,2),
('affiliate','Please provide your main website or portfolio URL','url',NULL,0,3),
('affiliate','Describe your marketing experience','textarea',NULL,1,4),
('advertiser','What is your primary industry/vertical?','select','["Finance","Health & Beauty","eCommerce","Gaming","Dating","Software","Travel","Other"]',1,1),
('advertiser','What is your monthly advertising budget?','select','["Under $1,000","$1,000 - $10,000","$10,000 - $50,000","Over $50,000"]',1,2);

-- ── Advanced Payout Management Tables ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS `payout_country_rules` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `country`    CHAR(2) NOT NULL,
    `revenue`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `payout`     DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payout_device_rules` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `country`    CHAR(2) NOT NULL DEFAULT '',
    `device`     ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
    `revenue`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `payout`     DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_country_device` (`country`,`device`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aff_daily_caps` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `daily_cap`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff` (`affiliate_id`),
    INDEX `idx_aff` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aff_custom_payouts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `offer_id`     INT UNSIGNED NOT NULL,
    `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_offer` (`affiliate_id`,`offer_id`),
    INDEX `idx_aff` (`affiliate_id`),
    INDEX `idx_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aff_country_payouts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `country`      CHAR(2) NOT NULL,
    `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_country` (`affiliate_id`,`country`),
    INDEX `idx_aff` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aff_country_device_payouts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `country`      CHAR(2) NOT NULL,
    `device`       ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
    `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_aff_country_device` (`affiliate_id`,`country`,`device`),
    INDEX `idx_aff` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversion_optimize_rules` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `offer_id`       INT UNSIGNED NOT NULL,
    `optimize_value` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_offer` (`offer_id`),
    INDEX `idx_offer` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Postback Test Logs
CREATE TABLE IF NOT EXISTS `postback_test_logs` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`   INT UNSIGNED NOT NULL,
    `affiliate_code` VARCHAR(32)  NOT NULL,
    `offer_id`       INT UNSIGNED NOT NULL,
    `postback_id`    INT UNSIGNED NULL,
    `click_id`       CHAR(36)     NOT NULL,
    `payout`         DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `tracking_link`  TEXT         NOT NULL,
    `postback_url`   TEXT         NOT NULL,
    `fired_url`      TEXT         NOT NULL,
    `http_status`    SMALLINT UNSIGNED NULL,
    `response_body`  TEXT,
    `is_success`     TINYINT(1)   DEFAULT 0,
    `time_ms`        INT UNSIGNED DEFAULT 0,
    `tested_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_affiliate` (`affiliate_id`),
    INDEX `idx_tested_at` (`tested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News System
CREATE TABLE IF NOT EXISTS `news` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`        VARCHAR(500) NOT NULL,
    `slug`         VARCHAR(520) NOT NULL,
    `summary`      TEXT,
    `body`         LONGTEXT,
    `image`        VARCHAR(512) DEFAULT NULL,
    `is_hot`       TINYINT(1) DEFAULT 0,
    `status`       ENUM('published','draft') DEFAULT 'draft',
    `email_sent`   TINYINT(1) DEFAULT 0,
    `created_by`   INT UNSIGNED NULL,
    `published_at` DATETIME NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news_reads` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `news_id`      INT UNSIGNED NOT NULL,
    `user_id`      INT UNSIGNED NOT NULL,
    `read_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_news_user` (`news_id`,`user_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_news` (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referral System
CREATE TABLE IF NOT EXISTS `referral_codes` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `role`        ENUM('affiliate','affiliate_manager') NOT NULL,
    `code`        VARCHAR(32) NOT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user` (`user_id`),
    UNIQUE KEY `uq_code` (`code`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_signups` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `referrer_user_id` INT UNSIGNED NOT NULL,
    `referrer_role`   ENUM('affiliate','affiliate_manager') NOT NULL,
    `referred_aff_id` INT UNSIGNED NOT NULL,
    `referred_user_id` INT UNSIGNED NOT NULL,
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_referred` (`referred_aff_id`),
    INDEX `idx_referrer` (`referrer_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_commissions` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `referrer_aff_id`  INT UNSIGNED NOT NULL,
    `referred_aff_id`  INT UNSIGNED NOT NULL,
    `conversion_id`    CHAR(36) NOT NULL,
    `base_payout`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `commission_rate`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `commission_type`  ENUM('percent','fixed') DEFAULT 'percent',
    `commission_amount` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `status`           ENUM('pending','approved','rejected') DEFAULT 'pending',
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_referrer` (`referrer_aff_id`),
    INDEX `idx_referred` (`referred_aff_id`),
    INDEX `idx_conversion` (`conversion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fix: payment_method column was incorrectly defined as ENUM, preventing custom payment
-- method names from being saved. Changed to VARCHAR(100) to support any method name.
ALTER TABLE `affiliates` MODIFY COLUMN `payment_method` VARCHAR(100) NOT NULL DEFAULT '';

-- Invoice system improvements: duplicate prevention + balance deduction tracking
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `period_hash` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `balance_deducted` TINYINT(1) DEFAULT 0;

-- In-House Offers: allow NULL advertiser_id and add is_inhouse flag
ALTER TABLE `offers` MODIFY COLUMN `advertiser_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `is_inhouse` TINYINT(1) NOT NULL DEFAULT 0;

-- In-House Short Links table
CREATE TABLE IF NOT EXISTS `inhouse_short_links` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`             VARCHAR(12) NOT NULL UNIQUE,
    `affiliate_id`     INT UNSIGNED NOT NULL,
    `offer_id`         INT UNSIGNED NULL,
    `destination_url`  TEXT NOT NULL,
    `click_count`      INT UNSIGNED DEFAULT 0,
    `status`           ENUM('active','inactive') DEFAULT 'active',
    `last_clicked_at`  DATETIME NULL,
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_code` (`code`),
    INDEX `idx_affiliate` (`affiliate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global Postback Test Logs
CREATE TABLE IF NOT EXISTS `global_postback_test_logs` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`   INT UNSIGNED NOT NULL,
    `affiliate_code` VARCHAR(32)  NOT NULL DEFAULT '',
    `click_id`       CHAR(36)     NOT NULL DEFAULT '',
    `test_link`      TEXT,
    `postback_url`   TEXT,
    `fired_url`      TEXT,
    `http_status`    SMALLINT UNSIGNED NULL,
    `response_body`  TEXT,
    `is_success`     TINYINT(1)   DEFAULT 0,
    `time_ms`        INT UNSIGNED DEFAULT 0,
    `tested_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_affiliate` (`affiliate_id`),
    INDEX `idx_tested_at` (`tested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Affiliate Global Postback Test Logs ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `aff_global_pb_test_logs` (
    `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`     INT UNSIGNED     NOT NULL,
    `affiliate_name`   VARCHAR(255)     NOT NULL DEFAULT '',
    `affiliate_code`   VARCHAR(32)      NOT NULL DEFAULT '',
    `tracking_link`    TEXT,
    `click_id`         CHAR(36)         NOT NULL DEFAULT '',
    `conversion_id`    CHAR(36)         NOT NULL DEFAULT '',
    `global_pb_url`    TEXT,
    `fired_url`        TEXT,
    `http_status`      SMALLINT UNSIGNED NULL,
    `response_body`    TEXT,
    `is_success`       TINYINT(1)       DEFAULT 0,
    `time_ms`          INT UNSIGNED     DEFAULT 0,
    `error_message`    TEXT,
    `tested_at`        DATETIME         DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gpt_aff`       (`affiliate_id`),
    INDEX `idx_gpt_tested_at` (`tested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Global Postback URL — Admin Approval Columns ─────────────────────────
-- These columns give admin full control over each affiliate's global postback URL.
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_admin_status` ENUM('pending','approved','rejected') DEFAULT NULL;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_admin_note`   VARCHAR(500) DEFAULT NULL;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_submitted_at` DATETIME DEFAULT NULL;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_reviewed_at`  DATETIME DEFAULT NULL;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_reviewed_by`  INT UNSIGNED DEFAULT NULL;
ALTER TABLE `affiliates` ADD COLUMN IF NOT EXISTS `global_pb_active`       TINYINT(1) DEFAULT 1;
-- Approve any existing global postback URLs that pre-date this column
UPDATE `affiliates` SET `global_pb_admin_status`='approved', `global_pb_active`=1 WHERE `global_postback_url` IS NOT NULL AND `global_postback_url` != '' AND `global_pb_admin_status` IS NULL;

-- ── Global Postbacks: type + affiliate_id columns ─────────────────────────
-- type='advertiser' fires for ALL conversions (original behaviour)
-- type='affiliate'  fires for a single affiliate's conversions (synced to affiliates.global_postback_url)
ALTER TABLE `global_postbacks` ADD COLUMN IF NOT EXISTS `type`         ENUM('advertiser','affiliate') NOT NULL DEFAULT 'advertiser';
ALTER TABLE `global_postbacks` ADD COLUMN IF NOT EXISTS `affiliate_id` INT UNSIGNED DEFAULT NULL;

-- ── Performance indexes for affiliate global postback log JOIN query ──────
-- postback_logs: composite index for filtering by postback_id=0 + joining on conversion_id
ALTER TABLE `postback_logs` ADD INDEX IF NOT EXISTS `idx_pb_conv` (`postback_id`, `conversion_id`);
-- conversions: index for filtering by affiliate_id quickly
ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_conv_affiliate` (`affiliate_id`);

-- ── Tracking domain status columns ──────────────────────────────────────
-- dns_status: pending=A record not yet verified, pointing=A record OK, error=wrong IP
-- server_status: pending=vhost not configured, configured=vhost exists, error=failed
-- ssl_status: none=no SSL, active=HTTPS working, error=SSL failed
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `dns_status`     ENUM('pending','pointing','error')        NOT NULL DEFAULT 'pending';
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `server_status`  ENUM('pending','configured','error')      NOT NULL DEFAULT 'pending';
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `ssl_status`     ENUM('none','active','error')             NOT NULL DEFAULT 'none';
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `dns_ip`         VARCHAR(45)                               DEFAULT NULL;
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `last_check_at`  DATETIME                                  DEFAULT NULL;
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `check_message`  VARCHAR(500)                              DEFAULT NULL;

-- ── Fraud score columns on conversions ────────────────────────────────────
-- fraud_score: NULL = not yet checked, 0-100 = IPQS score after check
-- fraud_checked_at: NULL = pending API check, non-NULL = API called (even if score=0)
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `fraud_score`       TINYINT UNSIGNED DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `fraud_checked_at`  DATETIME         DEFAULT NULL;
-- conv_ip stores the IP address recorded on conversion (may differ from click IP via proxy)
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `conv_ip`           VARCHAR(45)      DEFAULT NULL;
-- user_agent on conversions (for report display)
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `user_agent`        VARCHAR(512)     DEFAULT NULL;
-- Index for fraud report queries (pending check filter + date sort)
ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_fraud_checked` (`fraud_checked_at`);

-- ── Migration tracking table (auto-migration system) ───────────────────────
-- Records which install/migrations/*.sql files have been applied.
-- On fresh install, all migration files are pre-marked as applied so they
-- are not re-run on the first boot (schema.sql already contains those changes).
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

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration`     VARCHAR(255)  NOT NULL UNIQUE,
    `batch`         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `applied_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `checksum`      VARCHAR(64)   NULL,
    `execution_ms`  INT UNSIGNED  NULL,
    `status`        ENUM('applied','failed') NOT NULL DEFAULT 'applied',
    `error_message` TEXT NULL,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-mark all bundled migrations as applied on fresh install
-- (their SQL is already included above in this schema file)
INSERT IGNORE INTO `schema_migrations` (migration, batch, status) VALUES
    ('0001_postback_reliability.sql',      1, 'applied'),
    ('0002_fraud_detection.sql',           1, 'applied'),
    ('0003_tracking_domains.sql',          1, 'applied'),
    ('0004_advanced_payout_management.sql',1, 'applied'),
    ('0005_smartlink_payouts.sql',         1, 'applied'),
    ('0006_autohide_index.sql',                1, 'applied'),
    ('0007_upgrade_safety.sql',               1, 'applied'),
    ('0008_manager_commission_from_profit.sql',1, 'applied'),
    ('0009_manager_invoice_balance.sql',      1, 'applied'),
    ('0010_manager_commission_fix.sql',       1, 'applied'),
    ('0024_offer_categories_types.sql',       1, 'applied');

-- ── Traffic Source Tracking on conversions ────────────────────────────────
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `traffic_source`      VARCHAR(50)   DEFAULT 'Unknown';
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `traffic_source_type` VARCHAR(30)   DEFAULT 'Unknown';
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `referrer_url`        VARCHAR(2000) DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_source`          VARCHAR(255)  DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_medium`          VARCHAR(255)  DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_campaign`        VARCHAR(255)  DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_content`         VARCHAR(255)  DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_term`            VARCHAR(255)  DEFAULT NULL;
ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_traffic_source`      (`traffic_source`);
ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_traffic_source_type` (`traffic_source_type`);

-- ── Admin Traffic Source Override System ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `traffic_source_overrides` (
    `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`                    VARCHAR(255) NOT NULL,
    `enabled`                 TINYINT(1) NOT NULL DEFAULT 1,
    `priority`                INT NOT NULL DEFAULT 0,
    `conditions`              JSON NULL COMMENT 'Stores JSON arrays for Affiliates, Offers, Advertisers, etc.',
    `target_original_sources` JSON NULL COMMENT 'Stores JSON array of original sources to override',
    `override_source`         VARCHAR(255) NOT NULL,
    `created_at`              DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enabled`       (`enabled`),
    INDEX `idx_priority`      (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL;

-- ── Offer Categories & Offer Types (Two-level taxonomy) ──────────────────────
CREATE TABLE IF NOT EXISTS `offer_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cat_name` (`name`),
    UNIQUE KEY `uq_cat_slug` (`slug`),
    INDEX `idx_cat_status` (`status`),
    INDEX `idx_cat_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offer_types` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_type_cat_name` (`category_id`, `name`),
    UNIQUE KEY `uq_type_slug` (`slug`),
    INDEX `idx_type_category` (`category_id`),
    INDEX `idx_type_status` (`status`),
    INDEX `idx_type_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `category_id`   INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `offer_type_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `offers` ADD INDEX IF NOT EXISTS `idx_offer_category_id` (`category_id`);
ALTER TABLE `offers` ADD INDEX IF NOT EXISTS `idx_offer_type_id`     (`offer_type_id`);

-- Seed initial offer categories
INSERT IGNORE INTO `offer_categories` (`name`, `slug`, `status`, `sort_order`) VALUES
    ('Sweepstakes',       'sweepstakes',     'active', 1),
    ('Finance',           'finance',         'active', 2),
    ('Free Trials',       'free-trials',     'active', 3),
    ('Subscriptions',     'subscriptions',   'active', 4),
    ('Mobile Apps',       'mobile-apps',     'active', 5),
    ('Health & Wellness', 'health-wellness', 'active', 6),
    ('Dating',            'dating',          'active', 7);
