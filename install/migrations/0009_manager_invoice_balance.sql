-- ============================================================
-- Migration 0009: Manager Invoice Balance
--
-- Adds a running `balance` column to affiliate_managers so the
-- invoice system can perform instant, atomic balance deductions
-- when admin generates a manager commission invoice.
--
-- Rules enforced:
--   - balance is incremented when a commission is recorded
--   - balance is decremented when a commission is reversed
--   - invoice creation deducts balance atomically (WHERE balance >= total)
--   - invoice deletion refunds balance (when not yet paid)
--   - balance can never go negative (GREATEST(0, balance - x))
-- ============================================================

-- 1. Add balance column to affiliate_managers
ALTER TABLE affiliate_managers
    ADD COLUMN IF NOT EXISTS balance DECIMAL(10,4) NOT NULL DEFAULT 0.0000
    COMMENT 'Running total of earned commissions available for invoicing';

-- 2. Backfill balance from existing pending + approved commissions
--    (reversed and paid rows are excluded — they have already been
--     deducted or invoiced)
UPDATE affiliate_managers am
SET am.balance = COALESCE(
    (SELECT SUM(mc.commission_amount)
     FROM manager_commissions mc
     WHERE mc.manager_id = am.id
       AND mc.status IN ('pending', 'approved')
    ), 0.0000
);
