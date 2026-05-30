-- ============================================================
-- Migration 0008: Manager Commission From Net Profit
--
-- Changes:
--   1. Ensures commission_rate column exists on affiliate_managers
--      (column already exists in base schema but may be missing
--       on older installations — safe to re-add with IF NOT EXISTS).
--
--   2. Creates manager_commissions table.
--      One row per approved conversion, recording:
--        - advertiser_revenue  (what the advertiser pays)
--        - affiliate_payout    (what the affiliate earns)
--        - net_profit          = revenue − payout
--        - commission_rate     (snapshot of the manager's rate at time of calculation)
--        - commission_amount   = net_profit × (commission_rate / 100)
--
--   Commission is NEVER calculated from affiliate_payout.
--   It is calculated ONLY from net_profit.
-- ============================================================

-- 1. Ensure commission_rate column exists
ALTER TABLE affiliate_managers
    ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00
    COMMENT 'Manager commission % applied strictly to net profit (advertiser_revenue - affiliate_payout)';

-- 2. Manager commissions ledger
CREATE TABLE IF NOT EXISTS manager_commissions (
    id                 BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    manager_id         INT UNSIGNED     NOT NULL  COMMENT 'affiliate_managers.id',
    affiliate_id       INT UNSIGNED     NOT NULL  COMMENT 'affiliates.id',
    conversion_id      BIGINT UNSIGNED  NOT NULL  COMMENT 'conversions.id (integer PK)',
    offer_id           INT UNSIGNED     NOT NULL,
    is_smartlink       TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '1 if traffic originated from a smartlink',
    advertiser_revenue DECIMAL(10,4)    NOT NULL DEFAULT 0.0000 COMMENT 'Revenue received from advertiser',
    affiliate_payout   DECIMAL(10,4)    NOT NULL DEFAULT 0.0000 COMMENT 'Amount paid to affiliate',
    net_profit         DECIMAL(10,4)    NOT NULL DEFAULT 0.0000 COMMENT 'advertiser_revenue - affiliate_payout',
    commission_rate    DECIMAL(5,2)     NOT NULL DEFAULT 0.00   COMMENT 'Snapshot of rate at calculation time',
    commission_amount  DECIMAL(10,4)    NOT NULL DEFAULT 0.0000 COMMENT 'net_profit * (commission_rate / 100)',
    status             ENUM('pending','approved','paid','reversed') NOT NULL DEFAULT 'pending',
    calculated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mgr_comm_conversion (conversion_id),
    KEY idx_mgr_comm_manager  (manager_id),
    KEY idx_mgr_comm_affiliate (affiliate_id),
    KEY idx_mgr_comm_status   (status),
    KEY idx_mgr_comm_date     (calculated_at),
    KEY idx_mgr_comm_offer    (offer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
