<?php
/**
 * Advanced Traffic Source Override System
 *
 * Allows admin to configure dynamic rules to map original traffic sources
 * (like WhatsApp or Telegram) to an advertiser-compliant source (like Paid Ads)
 * based on Affiliate, Offer, Advertiser, Geo, Device, and more.
 */

class AdvancedTrafficSourceOverride
{
    private static bool $schemaEnsured = false;
    private static ?array $rulesCache = null;

    // ========================================================================
    // Schema Migration (Auto-execute on click)
    // ========================================================================
    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `traffic_source_overrides` (
                `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`                    VARCHAR(255) NOT NULL,
                `enabled`                 TINYINT(1) NOT NULL DEFAULT 1,
                `priority`                INT NOT NULL DEFAULT 0,
                `conditions`              JSON NULL COMMENT 'Stores JSON arrays for Affiliates, Offers, Advertisers, etc.',
                `target_original_sources` JSON NULL COMMENT 'Stores JSON array of original sources to override',
                `override_source`         VARCHAR(255) NOT NULL,
                `created_at`              DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at`              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_enabled`       (`enabled`),
                INDEX `idx_priority`      (`priority`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Add columns to clicks if they don't exist
            Database::query("ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL");
            Database::query("ALTER TABLE `clicks` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL");

            // Add columns to conversions if they don't exist
            Database::query("ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_source` VARCHAR(50) DEFAULT NULL");
            Database::query("ALTER TABLE `conversions` ADD COLUMN IF NOT EXISTS `override_rule_id` INT UNSIGNED DEFAULT NULL");
        } catch (\Throwable $e) {
            // Suppress schema errors in production to prevent crashing clicks
        }
    }

    // ========================================================================
    // Global Toggle
    // ========================================================================
    public static function isGlobalEnabled(): bool
    {
        try {
            return (Config::get('config', 'advanced_traffic_override.enabled') ?? '0') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function setGlobalEnabled(bool $enabled): bool
    {
        return Config::set('config', 'advanced_traffic_override.enabled', $enabled ? '1' : '0');
    }

    // ========================================================================
    // Rule Evaluation
    // ========================================================================
    
    /**
     * Loads all enabled rules from the database into memory.
     */
    private static function loadRules(): void
    {
        if (self::$rulesCache !== null) return;
        
        try {
            self::$rulesCache = Database::fetchAll("SELECT * FROM `traffic_source_overrides` WHERE `enabled` = 1 ORDER BY `priority` DESC, `id` ASC");
        } catch (\Throwable $e) {
            self::$rulesCache = [];
        }
    }

    /**
     * Evaluates the detected traffic source against the admin rules.
     *
     * @param string $originalSource The TRUE detected source (e.g. "WhatsApp", "Telegram")
     * @param array  $context        The click context (affiliate_id, offer_id, advertiser_id, country, device_type, browser, os, smartlink_id, landing_page_idx, campaign_id)
     *
     * @return array|null Returns ['override_source' => string, 'rule_id' => int] or null if no rule matches.
     */
    public static function evaluate(string $originalSource, array $context): ?array
    {
        if (!self::isGlobalEnabled()) {
            return null;
        }

        self::ensureSchema();
        self::loadRules();

        if (empty(self::$rulesCache)) {
            return null;
        }

        $sourceLower = strtolower(trim($originalSource));

        foreach (self::$rulesCache as $rule) {
            // 1. Check if the rule targets this specific original source
            $targets = json_decode($rule['target_original_sources'] ?? '[]', true);
            if (!empty($targets)) {
                $matchedSource = false;
                foreach ($targets as $target) {
                    if (strtolower(trim($target)) === $sourceLower) {
                        $matchedSource = true;
                        break;
                    }
                }
                if (!$matchedSource) {
                    continue; // Skip rule: source doesn't match
                }
            } else {
                // If target_original_sources is empty, it means this rule applies to ALL sources?
                // Usually we'd want at least one source specified. We'll assume empty array means it applies to all.
            }

            // 2. Check conditions
            $conditions = json_decode($rule['conditions'] ?? '{}', true);
            if (!empty($conditions)) {
                
                // Affiliate Check
                if (!empty($conditions['affiliate_ids']) && !empty($context['affiliate_id'])) {
                    if (!in_array((int)$context['affiliate_id'], $conditions['affiliate_ids'], true)) {
                        continue;
                    }
                }

                // Offer Check
                if (!empty($conditions['offer_ids']) && !empty($context['offer_id'])) {
                    if (!in_array((int)$context['offer_id'], $conditions['offer_ids'], true)) {
                        continue;
                    }
                }

                // Advertiser Check
                if (!empty($conditions['advertiser_ids']) && !empty($context['advertiser_id'])) {
                    if (!in_array((int)$context['advertiser_id'], $conditions['advertiser_ids'], true)) {
                        continue;
                    }
                }

                // Country Check (GEO)
                if (!empty($conditions['countries']) && !empty($context['country'])) {
                    // Match country codes
                    $matchedGeo = false;
                    foreach ($conditions['countries'] as $c) {
                        if (strtolower(trim($c)) === strtolower(trim($context['country']))) {
                            $matchedGeo = true;
                            break;
                        }
                    }
                    if (!$matchedGeo) {
                        continue;
                    }
                }

                // Device Type
                if (!empty($conditions['device_types']) && !empty($context['device_type'])) {
                    $matchedDevice = false;
                    foreach ($conditions['device_types'] as $d) {
                        if (strtolower(trim($d)) === strtolower(trim($context['device_type']))) {
                            $matchedDevice = true;
                            break;
                        }
                    }
                    if (!$matchedDevice) continue;
                }

                // Browser
                if (!empty($conditions['browsers']) && !empty($context['browser'])) {
                    $matchedBrowser = false;
                    foreach ($conditions['browsers'] as $b) {
                        if (strtolower(trim($b)) === strtolower(trim($context['browser']))) {
                            $matchedBrowser = true;
                            break;
                        }
                    }
                    if (!$matchedBrowser) continue;
                }

                // OS
                if (!empty($conditions['operating_systems']) && !empty($context['os'])) {
                    $matchedOs = false;
                    foreach ($conditions['operating_systems'] as $o) {
                        if (strtolower(trim($o)) === strtolower(trim($context['os']))) {
                            $matchedOs = true;
                            break;
                        }
                    }
                    if (!$matchedOs) continue;
                }

                // SmartLink Check
                if (!empty($conditions['smartlink_ids']) && !empty($context['smartlink_id'])) {
                    if (!in_array((int)$context['smartlink_id'], $conditions['smartlink_ids'], true)) {
                        continue;
                    }
                }
            }

            // If we reached here, ALL defined conditions matched!
            return [
                'override_source' => $rule['override_source'],
                'rule_id'         => (int)$rule['id']
            ];
        }

        // No rules matched
        return null;
    }

    // ========================================================================
    // CRUD Operations for Admin
    // ========================================================================

    public static function getAllRules(): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll("SELECT * FROM `traffic_source_overrides` ORDER BY `priority` DESC, `id` DESC") ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getRule(int $id): ?array
    {
        self::ensureSchema();
        try {
            return Database::fetchOne("SELECT * FROM `traffic_source_overrides` WHERE `id` = ?", [$id]) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function addRule(array $data): int
    {
        self::ensureSchema();
        return Database::insert('traffic_source_overrides', [
            'name'                    => substr(trim($data['name'] ?? 'New Rule'), 0, 255),
            'enabled'                 => (int)($data['enabled'] ?? 1),
            'priority'                => (int)($data['priority'] ?? 0),
            'conditions'              => empty($data['conditions']) ? null : json_encode($data['conditions']),
            'target_original_sources' => empty($data['target_original_sources']) ? null : json_encode($data['target_original_sources']),
            'override_source'         => substr(trim($data['override_source']), 0, 255),
        ]);
    }

    public static function updateRule(int $id, array $data): bool
    {
        self::ensureSchema();
        try {
            Database::query(
                "UPDATE `traffic_source_overrides` SET 
                    `name` = ?, 
                    `enabled` = ?, 
                    `priority` = ?, 
                    `conditions` = ?, 
                    `target_original_sources` = ?, 
                    `override_source` = ? 
                WHERE `id` = ?",
                [
                    substr(trim($data['name'] ?? ''), 0, 255),
                    (int)($data['enabled'] ?? 1),
                    (int)($data['priority'] ?? 0),
                    empty($data['conditions']) ? null : json_encode($data['conditions']),
                    empty($data['target_original_sources']) ? null : json_encode($data['target_original_sources']),
                    substr(trim($data['override_source']), 0, 255),
                    $id
                ]
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function toggleRule(int $id): bool
    {
        self::ensureSchema();
        try {
            Database::query("UPDATE `traffic_source_overrides` SET `enabled` = NOT `enabled` WHERE `id` = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function deleteRule(int $id): bool
    {
        self::ensureSchema();
        try {
            Database::query("DELETE FROM `traffic_source_overrides` WHERE `id` = ?", [$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
