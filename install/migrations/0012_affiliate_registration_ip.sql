-- Migration: 0012_affiliate_registration_ip.sql
-- Description: Capture and store the registration IP address for affiliates
--   - Adds registration_ip column to affiliates table
--   - Allows admins to monitor and track IPs used during signup

ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `registration_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP address captured at registration time';

ALTER TABLE `affiliates`
    ADD INDEX IF NOT EXISTS `idx_registration_ip` (`registration_ip`);
