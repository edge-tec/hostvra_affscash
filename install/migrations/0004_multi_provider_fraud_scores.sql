-- Migration: 0004_multi_provider_fraud_scores.sql
-- Adds all multi-provider fraud score columns to the conversions table.
-- Safe to run multiple times (uses IF NOT EXISTS / IGNORE errors).

ALTER TABLE `conversions`
    ADD COLUMN IF NOT EXISTS `ipquery_risk_score`   TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_risk_level`   VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_vpn`          TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_proxy`        TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_tor`          TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_datacenter`   TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_mobile`       TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_country`      VARCHAR(60)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_country_code` CHAR(2)          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_city`         VARCHAR(100)     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_isp`          VARCHAR(200)     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_org`          VARCHAR(200)     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ipquery_asn`          VARCHAR(30)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `scamalytics_score`    TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `scamalytics_status`   VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `scamalytics_mode`     VARCHAR(15)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `proxycheck_score`     TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `proxycheck_is_proxy`  TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `proxycheck_is_vpn`    TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `proxycheck_status`    VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `frauddefense_score`   TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `frauddefense_status`  VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `fraudlabspro_score`       TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `fraudlabspro_status`      VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `fraudlabspro_flp_status`  VARCHAR(10)      DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `botscout_is_bot`      TINYINT(1)       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `botscout_count`       SMALLINT         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `botscout_status`      VARCHAR(10)      DEFAULT NULL;
