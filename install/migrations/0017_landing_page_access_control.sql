-- Migration: 0017_landing_page_access_control.sql
-- Created:   2026-05-25 19:59:00
-- Description: Super Admin Landing Page + Per-Admin Landing Page Access Control

-- Create landing_pages table
CREATE TABLE IF NOT EXISTS `landing_pages` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`       INT UNSIGNED NULL COMMENT 'NULL for Super Admin SaaS main page',
    `logo_path`       VARCHAR(512) DEFAULT NULL,
    `hero_headline`   VARCHAR(500) DEFAULT NULL,
    `hero_sub`        TEXT DEFAULT NULL,
    `primary_color`   VARCHAR(10) DEFAULT '#6366F1',
    `secondary_color` VARCHAR(10) DEFAULT '#8B5CF6',
    `dark_mode`       TINYINT(1) DEFAULT 1,
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create landing_page_settings table
CREATE TABLE IF NOT EXISTS `landing_page_settings` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NULL,
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    UNIQUE KEY `uq_tenant_key` (`tenant_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create page_sections table
CREATE TABLE IF NOT EXISTS `page_sections` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NULL,
    `section_key`   VARCHAR(50) NOT NULL,
    `section_title` VARCHAR(255) NOT NULL,
    `sort_order`    INT DEFAULT 0,
    `is_visible`    TINYINT(1) DEFAULT 1,
    UNIQUE KEY `uq_tenant_section` (`tenant_id`, `section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create page_content table
CREATE TABLE IF NOT EXISTS `page_content` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id`     INT UNSIGNED NULL,
    `section_key`   VARCHAR(50) NOT NULL,
    `content_key`   VARCHAR(100) NOT NULL,
    `content_value` LONGTEXT,
    UNIQUE KEY `uq_tenant_sec_content` (`tenant_id`, `section_key`, `content_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add landing_enabled columns safely using standard SQL extensions
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `landing_enabled` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `landing_enabled` TINYINT(1) NOT NULL DEFAULT 1;

-- Seed default sections for Super Admin SaaS (tenant_id = NULL)
INSERT IGNORE INTO `page_sections` (`tenant_id`, `section_key`, `section_title`, `sort_order`, `is_visible`) VALUES
(NULL, 'hero', 'Hero Welcome Section', 1, 1),
(NULL, 'features', 'Product Features', 2, 1),
(NULL, 'plans', 'SaaS Pricing Tiers', 3, 1),
(NULL, 'screenshots', 'Platform Preview Screenshots', 4, 1),
(NULL, 'testimonials', 'Success Testimonials', 5, 1),
(NULL, 'faq', 'Frequently Asked Questions', 6, 1),
(NULL, 'footer', 'Platform Footer Info', 7, 1);

-- Seed default sections for Tenant 1 (tenant_id = 1)
INSERT IGNORE INTO `page_sections` (`tenant_id`, `section_key`, `section_title`, `sort_order`, `is_visible`) VALUES
(1, 'hero', 'Hero Welcome Section', 1, 1),
(1, 'features', 'Tracker Features', 2, 1),
(1, 'plans', 'Pricing Plans', 3, 1),
(1, 'screenshots', 'Reports Preview', 4, 1),
(1, 'testimonials', 'Affiliate Feedback', 5, 1),
(1, 'faq', 'General FAQ', 6, 1),
(1, 'footer', 'Footer Info', 7, 1);

-- Seed default page contents for Super Admin SaaS (tenant_id = NULL)
INSERT IGNORE INTO `page_content` (`tenant_id`, `section_key`, `content_key`, `content_value`) VALUES
(NULL, 'hero', 'headline', 'Deploy Your Own CPA & Affiliate Tracking Network in Seconds'),
(NULL, 'hero', 'description', 'AffsCash is the ultimate high-performance affiliate tracking software. Scale your clicks, protect your payout margins with built-in fraud blockers, and manage sub-affiliates easily.'),
(NULL, 'features', 'item_1_title', 'Real-Time Click Tracking'),
(NULL, 'features', 'item_1_desc', 'Track and optimize millions of click pathways instantly with advanced redirect rotation mechanics.'),
(NULL, 'features', 'item_2_title', 'Anti-Fraud Risk Engine'),
(NULL, 'features', 'item_2_desc', 'Identify and filter high-risk traffic, proxy ips, and bot patterns using real-time security algorithms.'),
(NULL, 'features', 'item_3_title', 'Smartlink Rotators'),
(NULL, 'features', 'item_3_desc', 'Dynamically route visitors to the highest converting offers using evolutionary device and geo-targeting.'),
(NULL, 'faq', 'q_1', 'What is AffsCash?'),
(NULL, 'faq', 'a_1', 'AffsCash is a multi-tenant enterprise software helping affiliate networks, performance marketers, and SaaS builders tracking conversion activities securely.');

-- Seed default page contents for Tenant 1 (tenant_id = 1)
INSERT IGNORE INTO `page_content` (`tenant_id`, `section_key`, `content_key`, `content_value`) VALUES
(1, 'hero', 'headline', 'The Premier Affiliate Network for CPA & Smartlink Offers'),
(1, 'hero', 'description', 'Join our network today. Access exclusive offers, high-paying performance caps, fast weekly billing support, and reliable tracking endpoints.'),
(1, 'features', 'item_1_title', 'Exclusive CPA Deals'),
(1, 'features', 'item_1_desc', 'Access hand-picked in-house conversion offers with optimized payouts and stable revenue flows.'),
(1, 'features', 'item_2_title', 'Smartlink Delivery'),
(1, 'features', 'item_2_desc', 'Direct your generic traffic to optimized smartlink nodes to maximize CTR and CPM performance.'),
(1, 'features', 'item_3_title', 'Weekly Faster Payments'),
(1, 'features', 'item_3_desc', 'Request invoices with zero delay. Support Stripe, PayPal, Bank Transfers, and USDT payment logs.');
