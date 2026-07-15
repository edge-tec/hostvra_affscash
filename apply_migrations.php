<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/Database.php';

echo "<pre>\n";

$queries = [
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `traffic_source` VARCHAR(50) DEFAULT 'Unknown'",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `traffic_source_type` VARCHAR(30) DEFAULT 'Unknown'",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `referrer_url` VARCHAR(2000) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_source` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_medium` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_campaign` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_content` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `utm_term` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_traffic_source` (`traffic_source`)",
    "ALTER TABLE `conversions` ADD INDEX IF NOT EXISTS `idx_traffic_source_type` (`traffic_source_type`)",

    "CREATE TABLE IF NOT EXISTS `traffic_source_overrides` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `priority` INT NOT NULL DEFAULT 0,
        `conditions` JSON NULL COMMENT 'Stores JSON arrays for Affiliates, Offers, Advertisers, etc.',
        `target_original_sources` JSON NULL COMMENT 'Stores JSON array of original sources to override',
        `override_source` VARCHAR(255) NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_enabled` (`enabled`),
        INDEX `idx_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL",
    
    // Also we need to make sure traffic_source exists on clicks table!
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `traffic_source` VARCHAR(50) DEFAULT 'Unknown'",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `traffic_source_type` VARCHAR(30) DEFAULT 'Unknown'",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `detected_by` VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `utm_source` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `utm_medium` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `utm_campaign` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `utm_content` VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `utm_term` VARCHAR(255) DEFAULT NULL",
];

foreach ($queries as $sql) {
    try {
        Database::query($sql);
        echo "Executed: " . substr(str_replace("\n", " ", $sql), 0, 80) . "...\n";
    } catch (Exception $e) {
        // Ignore duplicate column errors or syntax errors like IF NOT EXISTS on old MariaDB versions
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Skipped (Already exists): " . substr($sql, 0, 80) . "...\n";
        } else {
            // For MariaDB older versions, ADD COLUMN IF NOT EXISTS is not supported. We have to catch the syntax error
            // and assume it failed. Let's write a fallback for older mysql versions.
            echo "Error on: " . substr($sql, 0, 80) . "... -> " . $e->getMessage() . "\n";
        }
    }
}
echo "Done.\n";
