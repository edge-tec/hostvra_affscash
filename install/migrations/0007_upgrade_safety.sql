-- Migration 0007: Upgrade-safety schema hardening
-- Ensures all columns required by the upgrade-safe architecture exist.
-- All statements use IF NOT EXISTS / safe ALTER so re-running is harmless.

-- Ensure schema_migrations has the full column set used by Migrator.php
ALTER TABLE `schema_migrations`
    ADD COLUMN IF NOT EXISTS `message` TEXT NULL AFTER `error_message`;

-- Ensure stats_daily has fraud_clicks column (required by cron/stats_cache.php)
ALTER TABLE `stats_daily`
    ADD COLUMN IF NOT EXISTS `fraud_clicks` INT UNSIGNED NOT NULL DEFAULT 0
    AFTER `revenue`;

-- Ensure clicks table has is_fraud and is_unique columns
ALTER TABLE `clicks`
    ADD COLUMN IF NOT EXISTS `is_fraud` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `clicks`
    ADD COLUMN IF NOT EXISTS `is_unique` TINYINT(1) NOT NULL DEFAULT 0;

-- Ensure conversions table has is_hidden and hide_reason
ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `is_hidden` TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `hide_reason` VARCHAR(255) DEFAULT NULL;

-- Ensure affiliates table has balance column
ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00;

-- Index for fast pre-migration backup table listing
ALTER TABLE `schema_migrations`
    ADD INDEX IF NOT EXISTS `idx_migration` (`migration`);
