-- ============================================================
-- Migration 0015: Add affiliate_id to manager_invoice_requests
--
-- Allows affiliate managers to specify which affiliate account
-- the invoice request is for when submitting a payout request.
-- The admin sees this when reviewing and approving.
-- ============================================================

ALTER TABLE `manager_invoice_requests`
    ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Optional: which affiliate account this request covers'
        AFTER `manager_id`,
    ADD INDEX `idx_affiliate_id` (`affiliate_id`);
