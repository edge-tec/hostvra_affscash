<?php
/**
 * TrafficSourceOverride — Chat traffic source detection & reclassification.
 *
 * When enabled by admin, detects incoming traffic from chat platforms
 * (Telegram, WhatsApp, Messenger, Discord, Signal, Viber) and overrides
 * the recorded traffic source to a different category per admin-defined rules.
 *
 * Architecture follows the VpnSkipList pattern:
 *   - idempotent schema creation
 *   - single DB query per request then in-memory lookup
 *   - fail-open on any error (never break click tracking)
 *   - admin-only management with full audit trail
 */
final class TrafficSourceOverride
{
    // ── Schema guard ─────────────────────────────────────────────────────────
    private static bool $schemaEnsured = false;

    // ── In-memory rule cache (loaded once per request) ───────────────────────
    // Structure: [ affiliate_id => [ original_source => override_source ] ]
    // affiliate_id 0 = global (applies to all)
    private static array $rulesCache = [];
    private static bool  $cacheLoaded = false;

    // ── Valid source & destination enums ──────────────────────────────────────
    const CHAT_SOURCES = [
        'telegram', 'whatsapp', 'messenger', 'discord', 'signal', 'viber',
    ];

    const OVERRIDE_DESTINATIONS = [
        'organic'    => 'SEO / Organic',
        'paid_ads'   => 'Paid Ads',
        'display'    => 'Display',
        'email'      => 'Email',
        'social'     => 'Social',
        'native_ads' => 'Native Ads',
        'push'       => 'Push',
        'other'      => 'Other',
    ];

    // ── Chat source detection patterns ───────────────────────────────────────
    private static array $refererPatterns = [
        'telegram'  => ['t.me', 'telegram.org', 'web.telegram.org', 'telegram.me'],
        'whatsapp'  => ['wa.me', 'whatsapp.com', 'api.whatsapp.com', 'web.whatsapp.com'],
        'messenger' => ['m.me', 'messenger.com', 'l.messenger.com'],
        'discord'   => ['discord.com', 'discordapp.com', 'discord.gg', 'cdn.discordapp.com'],
        'signal'    => ['signal.org', 'signal.link', 'signal.me'],
        'viber'     => ['viber.com', 'chats.viber.com'],
    ];

    private static array $uaPatterns = [
        'telegram'  => ['TelegramBot', 'Telegram'],
        'whatsapp'  => ['WhatsApp'],
        'messenger' => ['FBAN/Messenger', 'FB_IAB/Messenger', 'Messenger'],
        'discord'   => ['Discordbot', 'Discord'],
        'signal'    => [], // Signal doesn't inject UA markers
        'viber'     => ['Viber'],
    ];

    // ========================================================================
    // Schema
    // ========================================================================

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `traffic_source_override_rules` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id`     INT UNSIGNED NULL COMMENT 'NULL = applies to ALL affiliates',
                `enabled`          TINYINT(1) NOT NULL DEFAULT 1,
                `original_source`  VARCHAR(50) NOT NULL,
                `override_source`  VARCHAR(50) NOT NULL,
                `created_by`       INT UNSIGNED NULL,
                `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_aff_source` (`affiliate_id`, `original_source`),
                INDEX `idx_affiliate`  (`affiliate_id`),
                INDEX `idx_enabled`    (`enabled`),
                INDEX `idx_original`   (`original_source`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `traffic_source_override_logs` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `rule_id`          INT UNSIGNED NULL,
                `affiliate_id`     INT UNSIGNED NULL,
                `action`           VARCHAR(30) NOT NULL,
                `original_source`  VARCHAR(50) NULL,
                `override_source`  VARCHAR(50) NULL,
                `old_value`        VARCHAR(255) NULL,
                `new_value`        VARCHAR(255) NULL,
                `admin_id`         INT UNSIGNED NULL,
                `admin_email`      VARCHAR(255) NULL,
                `ip_address`       VARCHAR(45) NULL,
                `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_rule`       (`rule_id`),
                INDEX `idx_affiliate`  (`affiliate_id`),
                INDEX `idx_action`     (`action`),
                INDEX `idx_created`    (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}
    }

    // ========================================================================
    // Global toggle
    // ========================================================================

    /** Check if traffic source override is globally enabled. */
    public static function isGlobalEnabled(): bool
    {
        try {
            return (Config::get('config', 'traffic_source_override.enabled') ?? '0') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Toggle global setting on/off. */
    public static function setGlobalEnabled(bool $enabled): bool
    {
        return Config::set('config', 'traffic_source_override.enabled', $enabled ? '1' : '0');
    }

    // ========================================================================
    // Chat source detection (hot path — no DB)
    // ========================================================================

    /**
     * Detect if the visitor's traffic originates from a chat platform.
     *
     * Checks (in priority order):
     *   1. UTM source / explicit source parameter
     *   2. HTTP Referer domain
     *   3. User-Agent keywords
     *
     * @return string|null  Lowercase chat source key, or null if not chat traffic.
     */
    public static function detectChatSource(string $referer, string $ua, string $source): ?string
    {
        // 1. Explicit source parameter / UTM
        $srcLower = strtolower(trim($source));
        if ($srcLower !== '') {
            foreach (self::CHAT_SOURCES as $chat) {
                if ($srcLower === $chat || str_contains($srcLower, $chat)) {
                    return $chat;
                }
            }
        }

        // 2. Referer domain matching
        if ($referer !== '') {
            $refLower = strtolower($referer);
            foreach (self::$refererPatterns as $chat => $domains) {
                foreach ($domains as $domain) {
                    if (str_contains($refLower, $domain)) {
                        return $chat;
                    }
                }
            }
            // Facebook referer can also be Messenger (l.facebook.com with messenger context)
            if (str_contains($refLower, 'l.facebook.com') || str_contains($refLower, 'lm.facebook.com')) {
                // Check UA for Messenger confirmation
                $uaLower = strtolower($ua);
                if (str_contains($uaLower, 'messenger') || str_contains($uaLower, 'fban/messenger')) {
                    return 'messenger';
                }
            }
        }

        // 3. User-Agent keyword matching
        if ($ua !== '') {
            $uaLower = strtolower($ua);
            foreach (self::$uaPatterns as $chat => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($uaLower, strtolower($keyword))) {
                        return $chat;
                    }
                }
            }
        }

        return null;
    }

    // ========================================================================
    // Rule resolution (hot path — cached)
    // ========================================================================

    /**
     * Check if the affiliate has an active override rule for the detected source.
     *
     * Resolution order:
     *   1. Affiliate-specific rule (exact affiliate_id match)
     *   2. Global rule (affiliate_id IS NULL — applies to all)
     *
     * @return string|null  The override source key, or null if no matching rule.
     */
    public static function resolveOverride(int $affiliateId, string $detectedSource): ?string
    {
        self::loadRulesCache();

        $src = strtolower($detectedSource);

        // Priority 1: affiliate-specific rule
        if (isset(self::$rulesCache[$affiliateId][$src])) {
            return self::$rulesCache[$affiliateId][$src];
        }

        // Priority 2: global rule (affiliate_id = 0 means NULL/all)
        if (isset(self::$rulesCache[0][$src])) {
            return self::$rulesCache[0][$src];
        }

        return null;
    }

    /** Load all enabled rules into static cache (once per request). */
    private static function loadRulesCache(): void
    {
        if (self::$cacheLoaded) return;
        self::$cacheLoaded = true;

        try {
            self::ensureSchema();
            $rows = Database::fetchAll(
                "SELECT affiliate_id, original_source, override_source
                   FROM traffic_source_override_rules
                  WHERE enabled = 1"
            ) ?: [];

            foreach ($rows as $r) {
                $affKey = $r['affiliate_id'] !== null ? (int)$r['affiliate_id'] : 0;
                self::$rulesCache[$affKey][strtolower($r['original_source'])] = strtolower($r['override_source']);
            }
        } catch (\Throwable $e) {
            // Fail-open: empty cache means no overrides
        }
    }

    // ========================================================================
    // CRUD — Admin management
    // ========================================================================

    /** Get all rules with affiliate info for the admin management page. */
    public static function getRules(): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT r.*,
                        af.affiliate_code,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        u.email AS affiliate_email,
                        adm.first_name AS admin_first, adm.last_name AS admin_last
                   FROM traffic_source_override_rules r
              LEFT JOIN affiliates af  ON af.id = r.affiliate_id
              LEFT JOIN users      u   ON u.id  = af.user_id
              LEFT JOIN users      adm ON adm.id = r.created_by
               ORDER BY r.created_at DESC"
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Add a new override rule. Returns the new row ID or 0 on failure. */
    public static function addRule(?int $affiliateId, string $originalSource, string $overrideSource, ?int $adminId = null): int
    {
        self::ensureSchema();
        try {
            $id = Database::insert('traffic_source_override_rules', [
                'affiliate_id'    => $affiliateId,
                'enabled'         => 1,
                'original_source' => strtolower($originalSource),
                'override_source' => strtolower($overrideSource),
                'created_by'      => $adminId,
            ]);
            self::writeLog($id, $affiliateId, 'created', $originalSource, $overrideSource, null, null, $adminId);
            self::$cacheLoaded = false; // invalidate
            return $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Update an existing rule's override destination. */
    public static function updateRule(int $ruleId, string $newOverrideSource, ?int $adminId = null): bool
    {
        self::ensureSchema();
        try {
            $existing = Database::fetchOne("SELECT * FROM traffic_source_override_rules WHERE id=?", [$ruleId]);
            if (!$existing) return false;

            Database::update('traffic_source_override_rules', [
                'override_source' => strtolower($newOverrideSource),
            ], 'id=?', [$ruleId]);

            self::writeLog(
                $ruleId, $existing['affiliate_id'], 'updated',
                $existing['original_source'], $newOverrideSource,
                $existing['override_source'], $newOverrideSource,
                $adminId
            );
            self::$cacheLoaded = false;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Delete a rule. */
    public static function deleteRule(int $ruleId, ?int $adminId = null): bool
    {
        self::ensureSchema();
        try {
            $existing = Database::fetchOne("SELECT * FROM traffic_source_override_rules WHERE id=?", [$ruleId]);
            if (!$existing) return false;

            Database::delete('traffic_source_override_rules', 'id=?', [$ruleId]);

            self::writeLog(
                $ruleId, $existing['affiliate_id'], 'deleted',
                $existing['original_source'], $existing['override_source'],
                null, null, $adminId
            );
            self::$cacheLoaded = false;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Toggle a rule's enabled state. */
    public static function toggleRule(int $ruleId, ?int $adminId = null): bool
    {
        self::ensureSchema();
        try {
            $existing = Database::fetchOne("SELECT * FROM traffic_source_override_rules WHERE id=?", [$ruleId]);
            if (!$existing) return false;

            $newState = $existing['enabled'] ? 0 : 1;
            Database::update('traffic_source_override_rules', [
                'enabled' => $newState,
            ], 'id=?', [$ruleId]);

            self::writeLog(
                $ruleId, $existing['affiliate_id'], 'toggled',
                $existing['original_source'], $existing['override_source'],
                $existing['enabled'] ? 'enabled' : 'disabled',
                $newState ? 'enabled' : 'disabled',
                $adminId
            );
            self::$cacheLoaded = false;
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ========================================================================
    // Audit log
    // ========================================================================

    /** Write an audit log entry. */
    private static function writeLog(
        ?int $ruleId, ?int $affiliateId, string $action,
        ?string $originalSource, ?string $overrideSource,
        ?string $oldValue, ?string $newValue,
        ?int $adminId
    ): void {
        try {
            $adminEmail = null;
            if ($adminId) {
                $adm = Database::fetchOne("SELECT email FROM users WHERE id=?", [$adminId]);
                $adminEmail = $adm['email'] ?? null;
            }
            Database::insert('traffic_source_override_logs', [
                'rule_id'         => $ruleId,
                'affiliate_id'    => $affiliateId,
                'action'          => $action,
                'original_source' => $originalSource,
                'override_source' => $overrideSource,
                'old_value'       => $oldValue,
                'new_value'       => $newValue,
                'admin_id'        => $adminId,
                'admin_email'     => $adminEmail,
                'ip_address'      => substr(Helpers::getIp(), 0, 45),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never break anything
        }
    }

    /** Get paginated audit log entries. */
    public static function getAuditLog(int $limit = 50, int $offset = 0): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT * FROM traffic_source_override_logs ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [$limit, $offset]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Count total audit log entries. */
    public static function countAuditLog(): int
    {
        try {
            return Database::count('traffic_source_override_logs');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /** Human-readable label for a chat source key. */
    public static function sourceLabel(string $key): string
    {
        $labels = [
            'telegram'  => 'Telegram',
            'whatsapp'  => 'WhatsApp',
            'messenger' => 'Facebook Messenger',
            'discord'   => 'Discord',
            'signal'    => 'Signal',
            'viber'     => 'Viber',
        ];
        return $labels[strtolower($key)] ?? ucfirst($key);
    }

    /** Human-readable label for an override destination key. */
    public static function destinationLabel(string $key): string
    {
        return self::OVERRIDE_DESTINATIONS[strtolower($key)] ?? ucfirst($key);
    }
}
