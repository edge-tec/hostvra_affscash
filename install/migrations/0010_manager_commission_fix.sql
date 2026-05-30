-- ============================================================
-- Migration 0010: Manager Commission System — Complete Fix
--
-- Ensures all columns, tables, and ENUM values required by
-- the manager commission system exist, regardless of the
-- install path or prior migration state.
-- All statements are idempotent (IF NOT EXISTS / safe ALTER).
-- ============================================================

-- 1. Add manager_assigned_at to affiliates (tracks when each affiliate
--    was assigned so no retroactive commissions are issued)
ALTER TABLE `affiliates`
    ADD COLUMN IF NOT EXISTS `manager_assigned_at` DATETIME DEFAULT NULL
    COMMENT 'Timestamp when affiliate was assigned to current manager — commission only counts from this date';

-- 2. Backfill assigned_at for affiliates already under a manager
--    (we use NOW() so no historical conversions trigger retroactive commissions)
UPDATE `affiliates`
SET `manager_assigned_at` = NOW()
WHERE `manager_id` IS NOT NULL
  AND `manager_assigned_at` IS NULL;

-- 3. Ensure commission_rate column exists on affiliate_managers
ALTER TABLE `affiliate_managers`
    ADD COLUMN IF NOT EXISTS `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00
    COMMENT 'Commission % applied strictly to net profit (revenue - payout)';

-- 4. Ensure balance column exists on affiliate_managers
ALTER TABLE `affiliate_managers`
    ADD COLUMN IF NOT EXISTS `balance` DECIMAL(10,4) NOT NULL DEFAULT 0.0000
    COMMENT 'Running available commission balance ready for invoicing';

-- 5. Create manager_commissions table if it does not exist
CREATE TABLE IF NOT EXISTS `manager_commissions` (
    `id`                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `manager_id`         INT UNSIGNED     NOT NULL  COMMENT 'affiliate_managers.id',
    `affiliate_id`       INT UNSIGNED     NOT NULL  COMMENT 'affiliates.id',
    `conversion_id`      BIGINT UNSIGNED  NOT NULL  COMMENT 'conversions.id (integer PK)',
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

-- 6. Fix invoices.type column: the base schema had it as a 2-value ENUM
--    which cannot store 'manager_fee'. Widen it to VARCHAR(50) so all
--    invoice types (affiliate_payout, advertiser_billing, manager_fee) work.
ALTER TABLE `invoices`
    MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'affiliate_payout';

-- 7. Add manager_id column to invoices (for manager fee invoices)
ALTER TABLE `invoices`
    ADD COLUMN IF NOT EXISTS `manager_id` INT UNSIGNED DEFAULT NULL
    COMMENT 'Set for manager_fee invoice type';

-- 8. Add balance_before / balance_after to invoices (for manager fee audit)
ALTER TABLE `invoices`
    ADD COLUMN IF NOT EXISTS `balance_before` DECIMAL(10,4) DEFAULT NULL;

ALTER TABLE `invoices`
    ADD COLUMN IF NOT EXISTS `balance_after` DECIMAL(10,4) DEFAULT NULL;

-- 9. Recalculate affiliate_managers.balance from existing commission records
--    (safe to run repeatedly — sets balance to sum of pending+approved commissions)
UPDATE `affiliate_managers` am
SET am.`balance` = COALESCE(
    (SELECT SUM(mc.`commission_amount`)
     FROM `manager_commissions` mc
     WHERE mc.`manager_id` = am.`id`
       AND mc.`status` IN ('pending', 'approved')
    ), 0.0000
);
