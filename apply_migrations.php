<?php
/**
 * Safe Consolidated Database Migration Runner
 * Ensures all required tables, columns, and indexes exist without any table metadata lock issues.
 */
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';

if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/core/Auth.php';
    require_once __DIR__ . '/core/Session.php';
    Session::start();
    if (!Auth::check() || Auth::role() !== 'admin') {
        http_response_code(403);
        die("Access denied: Admin login or CLI execution required.");
    }
}

Config::init(__DIR__ . '/config');

echo "<pre>\n";
echo "Starting database schema consolidation...\n";

function safeQuery(string $sql, string $label = ''): void {
    try {
        Database::query($sql);
        echo "[OK] " . ($label ?: substr(str_replace("\n", " ", $sql), 0, 70)) . "\n";
    } catch (\Throwable $e) {
        $msg = $e->getMessage();
        if (
            strpos($msg, 'Duplicate column name') !== false ||
            strpos($msg, 'Duplicate key name') !== false ||
            strpos($msg, 'already exists') !== false
        ) {
            echo "[SKIP] " . ($label ?: substr(str_replace("\n", " ", $sql), 0, 70)) . " (Already exists)\n";
        } else {
            echo "[NOTICE] " . ($label ?: substr(str_replace("\n", " ", $sql), 0, 70)) . ": " . $msg . "\n";
        }
    }
}

function columnExists(string $table, string $column): bool {
    try {
        $dbName = Config::get('config', 'database.name');
        $row = Database::fetchOne(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
            [$dbName, $table, $column]
        );
        return !empty($row);
    } catch (\Throwable $e) {
        return false;
    }
}

function addColumnIfMissing(string $table, string $column, string $definition): void {
    if (!columnExists($table, $column)) {
        safeQuery("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}", "Add {$table}.{$column}");
    } else {
        echo "[SKIP] Column {$table}.{$column} already exists\n";
    }
}

// 1. Tables Creation
safeQuery("CREATE TABLE IF NOT EXISTS `traffic_source_overrides` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table traffic_source_overrides");

safeQuery("CREATE TABLE IF NOT EXISTS `vpn_blocked_log` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id`   INT UNSIGNED NULL,
    `offer_id`       INT UNSIGNED NULL,
    `offer_name`     VARCHAR(255) NULL,
    `smartlink_id`   INT UNSIGNED NULL,
    `smartlink_name` VARCHAR(255) NULL,
    `ip_address`     VARCHAR(45) NOT NULL,
    `detection_type` VARCHAR(50) NOT NULL DEFAULT 'VPN',
    `user_agent`     VARCHAR(1000) NULL,
    `country`        VARCHAR(4) NOT NULL DEFAULT '',
    `blocked_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_blocked_at` (`blocked_at`),
    INDEX `idx_aff` (`affiliate_id`),
    INDEX `idx_smartlink` (`smartlink_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Table vpn_blocked_log");

safeQuery("CREATE TABLE IF NOT EXISTS `offer_conversion_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT UNSIGNED NOT NULL,
    `offer_id` INT UNSIGNED NOT NULL,
    `visitor_ip` VARCHAR(45) NOT NULL,
    `conversion_time` DATETIME NOT NULL,
    `conversion_status` VARCHAR(20) DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_aff_off_ip` (`affiliate_id`, `offer_id`, `visitor_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table offer_conversion_history");

safeQuery("CREATE TABLE IF NOT EXISTS `advertiser_postback_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversion_id` VARCHAR(64) DEFAULT NULL,
    `click_id` VARCHAR(64) DEFAULT NULL,
    `advertiser_id` INT UNSIGNED DEFAULT NULL,
    `offer_id` INT UNSIGNED DEFAULT NULL,
    `raw_params` TEXT,
    `headers` TEXT,
    `client_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_adv_pb_created` (`created_at`),
    INDEX `idx_adv_pb_conv` (`conversion_id`),
    INDEX `idx_adv_pb_click` (`click_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table advertiser_postback_logs");

safeQuery("CREATE TABLE IF NOT EXISTS `ip_geo_cache` (
    `ip_address`   VARCHAR(45) PRIMARY KEY,
    `country_code` VARCHAR(5)   DEFAULT NULL,
    `country`      VARCHAR(100) DEFAULT NULL,
    `region`       VARCHAR(100) DEFAULT NULL,
    `city`         VARCHAR(100) DEFAULT NULL,
    `isp`          VARCHAR(255) DEFAULT NULL,
    `proxy`        TINYINT(1)   DEFAULT 0,
    `hosting`      TINYINT(1)   DEFAULT 0,
    `lookup_ok`    TINYINT(1)   DEFAULT 1,
    `cached_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gc_cached` (`cached_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table ip_geo_cache");

safeQuery("CREATE TABLE IF NOT EXISTS `traffic_back_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `click_id` VARCHAR(255) DEFAULT NULL,
    `affiliate_id` INT UNSIGNED DEFAULT NULL,
    `offer_id` INT UNSIGNED DEFAULT NULL,
    `reason` VARCHAR(255) DEFAULT NULL,
    `redirect_url` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `country` VARCHAR(4) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tbl_aff` (`affiliate_id`),
    INDEX `idx_tbl_offer` (`offer_id`),
    INDEX `idx_tbl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table traffic_back_logs");

safeQuery("CREATE TABLE IF NOT EXISTS `fraud_blocklist` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type`       ENUM('ip','cidr','user_agent','affiliate_id','device_fp','asn') NOT NULL,
    `value`      VARCHAR(255) NOT NULL,
    `reason`     VARCHAR(255) DEFAULT NULL,
    `action`     ENUM('flag','block','throttle') NOT NULL DEFAULT 'block',
    `scope`      ENUM('clicks','conversions','all') NOT NULL DEFAULT 'all',
    `source`     ENUM('manual','auto_rule','imported') NOT NULL DEFAULT 'manual',
    `hit_count`  INT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` DATETIME DEFAULT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_type_value` (`type`, `value`(100)),
    INDEX `idx_type_active` (`type`, `is_active`),
    INDEX `idx_active`      (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Table fraud_blocklist");

safeQuery("CREATE TABLE IF NOT EXISTS `login_failures` (
    `ip_address` VARCHAR(45) PRIMARY KEY,
    `attempts` INT NOT NULL DEFAULT 0,
    `last_attempt` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table login_failures");

// 2. Add columns to `clicks`
$clickCols = [
    'sub6'                    => "VARCHAR(500) DEFAULT NULL",
    'smartlink_id'            => "INT UNSIGNED DEFAULT NULL",
    'fraud_reasons'           => "TEXT DEFAULT NULL",
    'source'                  => "VARCHAR(255) DEFAULT ''",
    'original_source'         => "VARCHAR(255) DEFAULT NULL",
    'source_override_applied' => "TINYINT(1) DEFAULT 0",
    'traffic_source'          => "VARCHAR(50) DEFAULT 'Unknown'",
    'traffic_source_type'     => "VARCHAR(50) DEFAULT 'Unknown'",
    'detected_by'             => "VARCHAR(100) DEFAULT NULL",
    'utm_source'              => "VARCHAR(255) DEFAULT NULL",
    'utm_medium'              => "VARCHAR(255) DEFAULT NULL",
    'utm_campaign'            => "VARCHAR(255) DEFAULT NULL",
    'utm_content'             => "VARCHAR(255) DEFAULT NULL",
    'utm_term'                => "VARCHAR(255) DEFAULT NULL",
    'override_source'         => "VARCHAR(50) DEFAULT NULL",
    'override_rule_id'        => "INT UNSIGNED DEFAULT NULL",
    'browser_version'         => "VARCHAR(64) DEFAULT ''",
    'os_version'              => "VARCHAR(64) DEFAULT ''",
    'device_brand'            => "VARCHAR(64) DEFAULT ''",
    'device_model'            => "VARCHAR(128) DEFAULT ''",
];
foreach ($clickCols as $col => $def) {
    addColumnIfMissing('clicks', $col, $def);
}

// 3. Add columns to `conversions`
$convCols = [
    'hide_reason'            => "VARCHAR(500) NOT NULL DEFAULT ''",
    'fraud_score'            => "TINYINT UNSIGNED DEFAULT NULL",
    'fraud_checked_at'       => "DATETIME DEFAULT NULL",
    'fraud_reasons'          => "TEXT DEFAULT NULL",
    'conv_ip'                => "VARCHAR(45) DEFAULT NULL",
    'user_agent'             => "VARCHAR(512) DEFAULT NULL",
    'ipquery_risk_score'     => "TINYINT UNSIGNED DEFAULT NULL",
    'ipquery_risk_level'     => "VARCHAR(10) DEFAULT NULL",
    'ipquery_vpn'            => "TINYINT(1) DEFAULT NULL",
    'ipquery_proxy'          => "TINYINT(1) DEFAULT NULL",
    'ipquery_tor'            => "TINYINT(1) DEFAULT NULL",
    'ipquery_datacenter'     => "TINYINT(1) DEFAULT NULL",
    'ipquery_mobile'         => "TINYINT(1) DEFAULT NULL",
    'ipquery_country'        => "VARCHAR(60) DEFAULT NULL",
    'ipquery_country_code'   => "CHAR(2) DEFAULT NULL",
    'ipquery_city'           => "VARCHAR(100) DEFAULT NULL",
    'ipquery_state'          => "VARCHAR(100) DEFAULT NULL",
    'ipquery_isp'            => "VARCHAR(200) DEFAULT NULL",
    'ipquery_org'            => "VARCHAR(200) DEFAULT NULL",
    'ipquery_asn'            => "VARCHAR(30) DEFAULT NULL",
    'scamalytics_score'      => "TINYINT UNSIGNED DEFAULT NULL",
    'scamalytics_status'     => "VARCHAR(10) DEFAULT NULL",
    'scamalytics_mode'       => "VARCHAR(15) DEFAULT NULL",
    'proxycheck_score'       => "TINYINT UNSIGNED DEFAULT NULL",
    'proxycheck_is_proxy'    => "TINYINT(1) DEFAULT NULL",
    'proxycheck_is_vpn'      => "TINYINT(1) DEFAULT NULL",
    'proxycheck_status'      => "VARCHAR(10) DEFAULT NULL",
    'botscout_is_bot'        => "TINYINT(1) DEFAULT NULL",
    'botscout_count'         => "SMALLINT UNSIGNED DEFAULT NULL",
    'botscout_status'        => "VARCHAR(10) DEFAULT NULL",
    'frauddefense_score'     => "TINYINT UNSIGNED DEFAULT NULL",
    'frauddefense_status'    => "VARCHAR(10) DEFAULT NULL",
    'fraudlabspro_score'     => "TINYINT UNSIGNED DEFAULT NULL",
    'fraudlabspro_status'    => "VARCHAR(10) DEFAULT NULL",
    'fraudlabspro_flp_status'=> "VARCHAR(10) DEFAULT NULL",
    'device_brand'           => "VARCHAR(60) DEFAULT NULL",
    'device_model'           => "VARCHAR(120) DEFAULT NULL",
    'os_version'             => "VARCHAR(40) DEFAULT NULL",
    'landing_page'           => "VARCHAR(2000) DEFAULT NULL",
    'referrer'               => "VARCHAR(2000) DEFAULT NULL",
    'traffic_source'         => "VARCHAR(50) DEFAULT 'Unknown'",
    'traffic_source_type'    => "VARCHAR(30) DEFAULT 'Unknown'",
    'referrer_url'           => "VARCHAR(2000) DEFAULT NULL",
    'utm_source'             => "VARCHAR(255) DEFAULT NULL",
    'utm_medium'             => "VARCHAR(255) DEFAULT NULL",
    'utm_campaign'           => "VARCHAR(255) DEFAULT NULL",
    'utm_content'            => "VARCHAR(255) DEFAULT NULL",
    'utm_term'               => "VARCHAR(255) DEFAULT NULL",
    'override_source'        => "VARCHAR(50) DEFAULT NULL",
    'override_rule_id'       => "INT UNSIGNED DEFAULT NULL",
    'postback_sent'          => "TINYINT(1) NOT NULL DEFAULT 0",
    'postback_sent_at'       => "DATETIME DEFAULT NULL",
    'is_hidden'              => "TINYINT(1) NOT NULL DEFAULT 0",
];
foreach ($convCols as $col => $def) {
    addColumnIfMissing('conversions', $col, $def);
}

// Modify advertiser_id on conversions to allow NULL
safeQuery("ALTER TABLE `conversions` MODIFY COLUMN `advertiser_id` INT UNSIGNED NULL DEFAULT NULL", "Allow NULL conversions.advertiser_id");
safeQuery("ALTER TABLE `clicks` MODIFY COLUMN `offer_id` INT UNSIGNED NULL DEFAULT NULL", "Allow NULL clicks.offer_id");

// 4. Other tables
addColumnIfMissing('vpn_blocked_log', 'smartlink_id', "INT UNSIGNED NULL AFTER `offer_name`");
addColumnIfMissing('vpn_blocked_log', 'smartlink_name', "VARCHAR(255) NULL AFTER `smartlink_id`");
addColumnIfMissing('offer_conversion_history', 'conversion_status', "VARCHAR(20) DEFAULT 'approved'");

// 5. Phase 1 Performance Indexes (High-throughput tracking & cap lookups)
safeQuery("ALTER TABLE `clicks` ADD INDEX `idx_offer_clicked_status` (`offer_id`, `clicked_at`, `status`)", "Index clicks(offer_id, clicked_at, status)");
safeQuery("ALTER TABLE `clicks` ADD INDEX `idx_ip_offer_aff_time` (`ip_address`, `offer_id`, `affiliate_id`, `clicked_at`)", "Index clicks(ip, offer, aff, time)");
safeQuery("ALTER TABLE `clicks` ADD INDEX `idx_aff_offer_time` (`affiliate_id`, `offer_id`, `clicked_at`)", "Index clicks(affiliate_id, offer_id, clicked_at)");

safeQuery("ALTER TABLE `conversions` ADD INDEX `idx_offer_status_date` (`offer_id`, `status`, `converted_at`)", "Index conversions(offer_id, status, converted_at)");
safeQuery("ALTER TABLE `conversions` ADD INDEX `idx_aff_offer_status_date` (`affiliate_id`, `offer_id`, `status`, `converted_at`)", "Index conversions(aff, offer, status, date)");

safeQuery("ALTER TABLE `stats_daily` ADD INDEX `idx_offer_date` (`offer_id`, `stat_date`)", "Index stats_daily(offer_id, stat_date)");

// 6. Phase 2 & 3: CTIT, Postback Security & Budget Protections
safeQuery("CREATE TABLE IF NOT EXISTS `advertiser_payment_requests` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `advertiser_id`   INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `method`          VARCHAR(40) NOT NULL,
    `amount`          DECIMAL(12,2) NOT NULL,
    `txn_id`          VARCHAR(120) NOT NULL,
    `screenshot_path` VARCHAR(300) NULL,
    `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note`      TEXT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`     DATETIME NULL,
    `reviewed_by`     INT UNSIGNED NULL,
    INDEX `idx_adv` (`advertiser_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table advertiser_payment_requests");

addColumnIfMissing('conversions', 'ctit_seconds', "INT UNSIGNED NULL DEFAULT NULL");
addColumnIfMissing('advertisers', 'postback_token', "VARCHAR(64) NULL DEFAULT NULL");
addColumnIfMissing('advertisers', 'postback_ips', "TEXT NULL DEFAULT NULL");
addColumnIfMissing('advertisers', 'budget_exempt', "TINYINT(1) NOT NULL DEFAULT 0");
addColumnIfMissing('offers', 'postback_token', "VARCHAR(64) NULL DEFAULT NULL");
addColumnIfMissing('offers', 'min_ctit_seconds', "INT UNSIGNED NULL DEFAULT NULL");
addColumnIfMissing('offers', 'attribution_window_days', "INT UNSIGNED NULL DEFAULT NULL");
addColumnIfMissing('offers', 'budget_total', "DECIMAL(12,2) DEFAULT NULL");
addColumnIfMissing('offers', 'budget_spent', "DECIMAL(12,2) NOT NULL DEFAULT 0");
addColumnIfMissing('offers', 'budget_paused', "TINYINT(1) NOT NULL DEFAULT 0");

safeQuery("ALTER TABLE `conversions` ADD INDEX `idx_ctit` (`ctit_seconds`)", "Index conversions(ctit_seconds)");

echo "\nConsolidated migration completed successfully!\n";
