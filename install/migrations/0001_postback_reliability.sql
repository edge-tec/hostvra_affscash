-- Migration: 0001_postback_reliability.sql
-- Description: Postback system reliability improvements
--   - attempt_count on postback_logs / global_postback_logs
--   - Performance indexes for retry cron
--   - Global postback approval columns on affiliates
--   - postback_sent tracking on conversions
--   - Retry-window anchor: first_fired_at
--   - is_hidden / hide_reason on conversions
--   - is_fraud column on conversions
--   - Fix postback_id column type
--   - Fix admin_status default on postbacks
--   - Auto-approve legacy global postback URLs

ALTER TABLE `postback_logs`
    ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `global_postback_logs`
    ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `postback_logs`
    ADD INDEX IF NOT EXISTS `idx_pb0_fired_at` (`postback_id`, `fired_at`);

ALTER TABLE `postback_logs`
    ADD INDEX IF NOT EXISTS `idx_pb_success_fired` (`is_success`, `attempt_count`, `fired_at`);

ALTER TABLE `global_postback_logs`
    ADD INDEX IF NOT EXISTS `idx_gpbl_success_fired` (`is_success`, `attempt_count`, `fired_at`);

ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `global_pb_admin_status` ENUM('pending','approved','rejected') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_admin_note`   VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_active`       TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `global_pb_submitted_at` DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_reviewed_at`  DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_reviewed_by`  INT UNSIGNED DEFAULT NULL;

UPDATE `affiliates`
SET  `global_pb_admin_status` = 'approved',
     `global_pb_active`       = 1
WHERE `global_postback_url` IS NOT NULL
  AND `global_postback_url` != ''
  AND (`global_pb_admin_status` IS NULL OR `global_pb_admin_status` = 'pending');

ALTER TABLE `global_postbacks`
    ADD COLUMN IF NOT EXISTS `type`         ENUM('advertiser','affiliate') NOT NULL DEFAULT 'advertiser',
    ADD COLUMN IF NOT EXISTS `affiliate_id` INT UNSIGNED DEFAULT NULL;

ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `is_hidden`        TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `hide_reason`      VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS `postback_sent`    TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `postback_sent_at` DATETIME     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `is_fraud`         TINYINT(1)   NOT NULL DEFAULT 0;

ALTER TABLE `postback_logs`
    MODIFY COLUMN `postback_id` INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `postbacks`
    MODIFY COLUMN `admin_status` ENUM('pending','approved','rejected') DEFAULT 'approved';

UPDATE `postbacks`
SET `admin_status` = 'approved'
WHERE `status` = 'active'
  AND (`admin_status` IS NULL OR `admin_status` = 'pending');

ALTER TABLE `postback_logs`
    ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL;

UPDATE `postback_logs`
SET `first_fired_at` = `fired_at`
WHERE `first_fired_at` IS NULL;

ALTER TABLE `global_postback_logs`
    ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL;

UPDATE `global_postback_logs`
SET `first_fired_at` = `fired_at`
WHERE `first_fired_at` IS NULL;

ALTER TABLE `affiliates`
    ADD INDEX IF NOT EXISTS `idx_aff_gpb_status` (`global_pb_admin_status`);

ALTER TABLE `postback_logs`
    ADD INDEX IF NOT EXISTS `idx_pb_conv` (`postback_id`, `conversion_id`);

ALTER TABLE `conversions`
    ADD INDEX IF NOT EXISTS `idx_conv_affiliate` (`affiliate_id`);
