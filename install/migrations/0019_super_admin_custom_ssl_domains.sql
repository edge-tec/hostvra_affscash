-- Migration: 0019_super_admin_custom_ssl_domains.sql
-- Created:   2026-05-26 11:35:00
-- Description: Super Admin Domain & SSL Assignment System

ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `ssl_cert` TEXT NULL;
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `ssl_key` TEXT NULL;
ALTER TABLE `tracking_domains` ADD COLUMN IF NOT EXISTS `ssl_ca` TEXT NULL;

-- Ensure ssl_status can hold 'custom' if needed (currently it's ENUM('none','active','error')). Let's alter it to support 'custom' or just keep it 'active' when custom.
-- We will just use 'active' when custom SSL is active, and rely on `ssl_cert IS NOT NULL` to know it's a custom cert.

-- Tracking specific metrics
ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `tracking_domain` VARCHAR(255) DEFAULT '';
ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `tracking_domain` VARCHAR(255) DEFAULT '';
