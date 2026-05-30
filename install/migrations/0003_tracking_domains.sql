-- Migration: 0003_tracking_domains.sql
-- Description: Custom tracking domain status columns (DNS / server / SSL)

ALTER TABLE `tracking_domains`
    ADD COLUMN IF NOT EXISTS `dns_status`    ENUM('pending','pointing','error') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `server_status` ENUM('pending','configured','error') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `ssl_status`    ENUM('none','active','error') NOT NULL DEFAULT 'none',
    ADD COLUMN IF NOT EXISTS `dns_ip`        VARCHAR(45)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `last_check_at` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `check_message` VARCHAR(500) DEFAULT NULL;
