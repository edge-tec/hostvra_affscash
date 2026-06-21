-- Migration: Chat Edit and Delete Support
-- Adds `is_edited`, `updated_at`, and standardizes `is_deleted` for messages

ALTER TABLE `support_messages`
ADD COLUMN IF NOT EXISTS `is_edited` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL DEFAULT NULL AFTER `is_edited`;

-- If `is_deleted` does not exist, add it
ALTER TABLE `support_messages`
ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `updated_at`;
