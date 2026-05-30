-- ============================================================
-- Schema Migration: Postback System Reliability Fixes
-- Run once against your database, or let the app auto-apply
-- via the runtime ALTER TABLE IF NOT EXISTS calls.
-- ============================================================

-- 1. postback_logs: add attempt_count if missing
ALTER TABLE `postback_logs`
    ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 1;

-- 2. global_postback_logs: add attempt_count if missing
ALTER TABLE `global_postback_logs`
    ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 1;

-- 3. postback_logs: composite index for postback_id=0 (affiliate global) queries
ALTER TABLE `postback_logs`
    ADD INDEX IF NOT EXISTS `idx_pb0_fired_at` (`postback_id`, `fired_at`);
ALTER TABLE `postback_logs`
    ADD INDEX IF NOT EXISTS `idx_pb_success_fired` (`is_success`, `attempt_count`, `fired_at`);

-- 4. global_postback_logs: index for retry cron
ALTER TABLE `global_postback_logs`
    ADD INDEX IF NOT EXISTS `idx_gpbl_success_fired` (`is_success`, `attempt_count`, `fired_at`);

-- 5. affiliates: global postback approval columns (idempotent)
ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `global_pb_admin_status` ENUM('pending','approved','rejected') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_admin_note`   VARCHAR(500) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_active`       TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `global_pb_submitted_at` DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_reviewed_at`  DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `global_pb_reviewed_by`  INT UNSIGNED DEFAULT NULL;

-- 6. Auto-approve any existing global postback URLs that pre-date the approval system
UPDATE `affiliates`
SET  `global_pb_admin_status` = 'approved',
     `global_pb_active`       = 1
WHERE `global_postback_url` IS NOT NULL
  AND `global_postback_url` != ''
  AND (`global_pb_admin_status` IS NULL);

-- 7. global_postbacks: type and affiliate_id columns (idempotent)
ALTER TABLE `global_postbacks`
    ADD COLUMN IF NOT EXISTS `type`         ENUM('advertiser','affiliate') NOT NULL DEFAULT 'advertiser',
    ADD COLUMN IF NOT EXISTS `affiliate_id` INT UNSIGNED DEFAULT NULL;

-- 8. conversions: is_hidden / hide_reason (idempotent - these may already exist)
ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `is_hidden`   TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `hide_reason` VARCHAR(255) NOT NULL DEFAULT '';

-- 9. conversions: postback tracking columns (CRITICAL — missing columns cause silent
--    postback_sent UPDATE failure, leaving postback status showing "NO" in the UI)
ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `postback_sent`    TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `postback_sent_at` DATETIME   DEFAULT NULL;

-- 9. Ensure postback_logs.postback_id allows 0 (sentinel for affiliate global)
--    Change the column from UNSIGNED (which cannot store 0 as a FK reference issue)
--    to plain INT so postback_id=0 is always valid.
ALTER TABLE `postback_logs`
    MODIFY COLUMN `postback_id` INT UNSIGNED NOT NULL DEFAULT 0;

-- 10. postbacks: ensure admin_status defaults to 'approved' and approve all active postbacks
--     (default was 'pending' which silently blocked postbacks from firing on new installs)
ALTER TABLE `postbacks` MODIFY COLUMN `admin_status` ENUM('pending','approved','rejected') DEFAULT 'approved';
UPDATE `postbacks` SET `admin_status`='approved' WHERE `status`='active' AND (`admin_status` IS NULL OR `admin_status`='pending');

-- 11. postback_logs: add first_fired_at to anchor retry window to original fire time
ALTER TABLE `postback_logs`
    ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL;
-- Backfill existing rows so RETRY_WINDOW works correctly for in-flight retries
UPDATE `postback_logs` SET `first_fired_at`=`fired_at` WHERE `first_fired_at` IS NULL;

-- 12. global_postback_logs: same first_fired_at column
ALTER TABLE `global_postback_logs`
    ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL;
UPDATE `global_postback_logs` SET `first_fired_at`=`fired_at` WHERE `first_fired_at` IS NULL;

-- 13. conversions: ensure is_fraud column exists for FraudIQ blocked conversions
--     FraudIQ sets is_fraud=1 on the conversion (not status='fraud' which is not
--     a valid ENUM value).  Without this column the UPDATE throws silently,
--     leaving the conversion visible in the Approved tab with postback=NO.
ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `is_fraud` TINYINT(1) NOT NULL DEFAULT 0;

-- Done
SELECT 'Migration applied successfully' AS result;

-- ============================================================
-- Bug-fix migration: global_pb_active NULL causes postback skip
-- (Added in v4 postback reliability patch)
-- ============================================================

-- MySQL leaves newly-ALTERed columns as NULL on pre-existing rows even when
-- DEFAULT 1 is specified. PHP evaluates (int)NULL as 0 which is != 1,
-- so the PostbackFirer canFire check silently blocked global postbacks
-- for any affiliate whose URL was set before this column was added.
-- This one-time UPDATE fixes all affected rows.

UPDATE `affiliates`
SET  `global_pb_active` = 1
WHERE `global_postback_url` IS NOT NULL
  AND `global_postback_url` != ''
  AND `global_pb_admin_status` = 'approved'
  AND `global_pb_active` IS NULL;

-- Also ensure postback_sent column exists before conversions are recorded
ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `postback_sent`    TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `postback_sent_at` DATETIME   DEFAULT NULL;

SELECT 'Bug-fix migration (global_pb_active NULL + postback_sent) applied' AS result;
