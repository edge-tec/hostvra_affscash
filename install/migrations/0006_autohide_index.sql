-- Migration 0006: Add index to speed up exact-ratio autohide COUNT queries
-- The deterministic autohide checks COUNT(*) per scope (affiliate_id, offer_id)
-- filtered by converted_at. These indexes prevent full table scans on high traffic.

ALTER TABLE `conversions`
    ADD INDEX IF NOT EXISTS `idx_aff_converted` (`affiliate_id`, `converted_at`);

ALTER TABLE `conversions`
    ADD INDEX IF NOT EXISTS `idx_offer_converted` (`offer_id`, `converted_at`);

ALTER TABLE `conversions`
    ADD INDEX IF NOT EXISTS `idx_converted_at` (`converted_at`);
