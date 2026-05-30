-- Migration: 0002_fraud_detection.sql
-- Description: FraudIQ integration — fraud score & check timestamp on conversions

ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `fraud_score`      TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `fraud_checked_at` DATETIME         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `conv_ip`          VARCHAR(45)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `user_agent`       VARCHAR(512)     DEFAULT NULL;

ALTER TABLE `conversions`
    ADD INDEX IF NOT EXISTS `idx_fraud_checked` (`fraud_checked_at`);
