-- Traffic Source Override & Classification Rules
-- Allows admin to reclassify chat-based traffic (Telegram, WhatsApp, etc.)
-- as another traffic type (Email, Paid Ads, Display, etc.)

CREATE TABLE IF NOT EXISTS `traffic_source_override_rules` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`     INT UNSIGNED NULL COMMENT 'NULL = applies to ALL affiliates',
    `enabled`          TINYINT(1) NOT NULL DEFAULT 1,
    `original_source`  VARCHAR(50) NOT NULL COMMENT 'telegram, whatsapp, messenger, discord, signal, viber',
    `override_source`  VARCHAR(50) NOT NULL COMMENT 'organic, paid_ads, display, email, social, native_ads, push, other',
    `created_by`       INT UNSIGNED NULL COMMENT 'Admin user ID who created this rule',
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_aff_source` (`affiliate_id`, `original_source`),
    INDEX `idx_affiliate`  (`affiliate_id`),
    INDEX `idx_enabled`    (`enabled`),
    INDEX `idx_original`   (`original_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `traffic_source_override_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_id`          INT UNSIGNED NULL,
    `affiliate_id`     INT UNSIGNED NULL,
    `action`           VARCHAR(30) NOT NULL COMMENT 'created, updated, deleted, toggled, global_enabled, global_disabled',
    `original_source`  VARCHAR(50) NULL,
    `override_source`  VARCHAR(50) NULL,
    `old_value`        VARCHAR(255) NULL COMMENT 'Previous value for updates/toggles',
    `new_value`        VARCHAR(255) NULL COMMENT 'New value for updates/toggles',
    `admin_id`         INT UNSIGNED NULL,
    `admin_email`      VARCHAR(255) NULL,
    `ip_address`       VARCHAR(45) NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rule`       (`rule_id`),
    INDEX `idx_affiliate`  (`affiliate_id`),
    INDEX `idx_action`     (`action`),
    INDEX `idx_created`    (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
