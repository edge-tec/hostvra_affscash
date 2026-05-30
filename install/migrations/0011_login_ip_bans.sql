-- Migration: 0011_login_ip_bans.sql
-- Description: Affiliate Login IP Ban feature
--   - Creates login_ip_bans table to store banned IPs
--   - Supports individual IPs and CIDR ranges
--   - Tracks who added the ban and why

CREATE TABLE IF NOT EXISTS `login_ip_bans` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address`  VARCHAR(50) NOT NULL,
    `reason`      VARCHAR(255) DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`  INT UNSIGNED DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_ip` (`ip_address`),
    INDEX `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add is_active to existing installations
ALTER TABLE `login_ip_bans` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;
