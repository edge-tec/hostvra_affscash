<?php
/**
 * RewardsService — Earning-milestone rewards.
 *
 * Admin defines rules ("$500 lifetime → $50 cash bonus", "$1000 → $100 gift").
 * On every conversion approval, checkAndGrant() reads the affiliate's
 * lifetime approved earnings (READ-ONLY against conversions) and stamps
 * any newly-crossed milestones into reward_grants. Each rule × affiliate
 * combo is guarded by a UNIQUE index so re-runs are idempotent.
 *
 * Reward kinds:
 *   - cash          (USD, displayed; payout still goes through normal flow)
 *   - bonus_credit  (USD, mirrored into affiliates.balance as a credit)
 *   - voucher       (text code shown to affiliate)
 *   - product       (free-text label; admin fulfils manually)
 */
class RewardsService
{
    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `reward_rules` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `threshold_usd`   DECIMAL(12,2) NOT NULL,
                `kind`            ENUM('cash','bonus_credit','voucher','product') NOT NULL DEFAULT 'cash',
                `value_amount`    DECIMAL(12,2) DEFAULT NULL,
                `value_text`      VARCHAR(255)  DEFAULT NULL,
                `title`           VARCHAR(255) NOT NULL,
                `description`     TEXT DEFAULT NULL,
                `active`          TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_threshold` (`threshold_usd`),
                KEY `idx_active`    (`active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        // ── Additive columns added in 2026 Rewards Module v2 — each in its own
        //    try/catch so re-runs are idempotent. Never modifies existing rows.
        $addCols = [
            'image_path'   => "VARCHAR(512) NULL DEFAULT NULL",
            'embed_code'   => "MEDIUMTEXT  NULL DEFAULT NULL",
            'badge_label'  => "VARCHAR(60) NULL DEFAULT NULL",
            'badge_color'  => "VARCHAR(20) NULL DEFAULT NULL",
            'visibility'   => "VARCHAR(20) NOT NULL DEFAULT 'public'",   // public|affiliate|vip|private
            'publish_at'   => "DATETIME    NULL DEFAULT NULL",
            'expires_at'   => "DATETIME    NULL DEFAULT NULL",
            'sort_order'   => "INT         NOT NULL DEFAULT 0",
        ];
        foreach ($addCols as $col => $type) {
            try {
                $exists = Database::fetchOne(
                    "SELECT 1 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reward_rules' AND COLUMN_NAME = ?",
                    [$col]
                );
                if (!$exists) {
                    Database::query("ALTER TABLE reward_rules ADD COLUMN `$col` $type");
                }
            } catch (\Throwable $_) {}
        }
        try { Database::query("ALTER TABLE reward_rules ADD INDEX idx_sort_order (sort_order)"); } catch (\Throwable $_) {}
        try { Database::query("ALTER TABLE reward_rules ADD INDEX idx_visibility (visibility)"); } catch (\Throwable $_) {}

        // ── Custom badge presets table — admins can build their own labels
        //    in addition to the canonical Hot / New / Trending / Featured /
        //    Popular / Exclusive set. Stored separately so a rule referring
        //    to a deleted preset still keeps its label snapshot.
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `reward_badge_presets` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `label`      VARCHAR(60) NOT NULL,
                `color`      VARCHAR(20) NOT NULL DEFAULT '#4F46E5',
                `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_label` (`label`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // Seed the canonical presets once.
            foreach (self::canonicalBadges() as $label => $color) {
                Database::query(
                    "INSERT IGNORE INTO reward_badge_presets (label, color) VALUES (?, ?)",
                    [$label, $color]
                );
            }
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `reward_grants` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `rule_id`         INT UNSIGNED NOT NULL,
                `affiliate_id`    INT UNSIGNED NOT NULL,
                `lifetime_at_grant` DECIMAL(12,2) NOT NULL,
                `kind`            ENUM('cash','bonus_credit','voucher','product') NOT NULL,
                `value_amount`    DECIMAL(12,2) DEFAULT NULL,
                `value_text`      VARCHAR(255)  DEFAULT NULL,
                `title_snapshot`  VARCHAR(255) NOT NULL,
                `status`          ENUM('granted','claimed','paid','cancelled') NOT NULL DEFAULT 'granted',
                `granted_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `admin_note`      TEXT DEFAULT NULL,
                UNIQUE KEY `uq_rule_aff` (`rule_id`,`affiliate_id`),
                KEY `idx_aff`    (`affiliate_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}
    }

    // ── Admin: rule CRUD ────────────────────────────────────────────────────

    public static function rules(bool $activeOnly = false, array $filters = []): array
    {
        self::ensureSchema();
        $where = [];
        $params = [];
        if ($activeOnly) $where[] = 'active = 1';
        if (!empty($filters['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ? OR badge_label LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['kind']))       { $where[] = 'kind = ?';       $params[] = $filters['kind']; }
        if (!empty($filters['visibility'])) { $where[] = 'visibility = ?'; $params[] = $filters['visibility']; }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = 'active = ?';
            $params[] = (int)!empty($filters['active']);
        }
        $sql = "SELECT * FROM reward_rules";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        // sort_order takes priority; threshold as a stable tiebreaker.
        $sql .= " ORDER BY sort_order ASC, threshold_usd ASC";
        return Database::fetchAll($sql, $params) ?: [];
    }

    public static function saveRule(array $data, ?int $id = null): int
    {
        self::ensureSchema();
        $allowedKinds = ['cash','bonus_credit','voucher','product'];
        $allowedVis   = ['public','affiliate','vip','private'];
        $kind  = in_array($data['kind'] ?? '', $allowedKinds, true) ? $data['kind'] : 'cash';
        $vis   = in_array($data['visibility'] ?? '', $allowedVis, true) ? $data['visibility'] : 'public';
        $color = trim((string)($data['badge_color'] ?? ''));
        if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) $color = '';

        $row = [
            'threshold_usd' => max(0.01, (float)($data['threshold_usd'] ?? 0)),
            'kind'          => $kind,
            'value_amount'  => isset($data['value_amount']) && $data['value_amount'] !== ''
                              ? (float)$data['value_amount'] : null,
            'value_text'    => trim((string)($data['value_text'] ?? '')) ?: null,
            'title'         => trim((string)($data['title'] ?? '')),
            'description'   => trim((string)($data['description'] ?? '')) ?: null,
            'active'        => empty($data['active']) ? 0 : 1,
            // New v2 fields — every one nullable / safe-defaulted, so existing
            // rules upgraded by ensureSchema() keep working unchanged.
            'image_path'    => isset($data['image_path']) ? ($data['image_path'] ?: null) : null,
            'embed_code'    => isset($data['embed_code']) ? (trim((string)$data['embed_code']) ?: null) : null,
            'badge_label'   => trim((string)($data['badge_label'] ?? '')) ?: null,
            'badge_color'   => $color ?: null,
            'visibility'    => $vis,
            'publish_at'    => self::normaliseDate($data['publish_at'] ?? null),
            'expires_at'    => self::normaliseDate($data['expires_at'] ?? null),
            'sort_order'    => isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
        ];
        if ($row['title'] === '') $row['title'] = 'Milestone $' . number_format($row['threshold_usd'], 0);

        // For UPDATE we may need to keep an existing image_path when no new
        // image is being uploaded — caller passes null intentionally to clear.
        if ($id) {
            // If image_path wasn't in $data at all, don't touch the column.
            if (!array_key_exists('image_path', $data)) unset($row['image_path']);
            Database::update('reward_rules', $row, 'id = ?', [$id]);
            return $id;
        }
        return (int)Database::insert('reward_rules', $row);
    }

    // ── New helpers (v2) ────────────────────────────────────────────────────

    /** Toggle the active flag without affecting any other column. */
    public static function toggleActive(int $id): bool
    {
        self::ensureSchema();
        if ($id <= 0) return false;
        Database::query("UPDATE reward_rules SET active = 1 - active WHERE id = ?", [$id]);
        return true;
    }

    /**
     * Clone a rule (everything except the id). The duplicate is inactive by
     * default so admins don't accidentally double-trigger milestones.
     */
    public static function duplicateRule(int $id): ?int
    {
        self::ensureSchema();
        $src = Database::fetchOne("SELECT * FROM reward_rules WHERE id = ?", [$id]);
        if (!$src) return null;
        unset($src['id'], $src['created_at']);
        $src['title']  = (string)$src['title'] . ' (copy)';
        $src['active'] = 0;
        return (int)Database::insert('reward_rules', $src);
    }

    /** Persist the new sort_order for a list of rule IDs in order. */
    public static function reorder(array $orderedIds): void
    {
        self::ensureSchema();
        $pos = 0;
        foreach ($orderedIds as $rid) {
            $rid = (int)$rid;
            if ($rid <= 0) continue;
            try { Database::query("UPDATE reward_rules SET sort_order = ? WHERE id = ?", [$pos, $rid]); }
            catch (\Throwable $_) {}
            $pos++;
        }
    }

    /** Canonical badge presets that ship with the system. */
    public static function canonicalBadges(): array
    {
        return [
            'Hot'       => '#EF4444',
            'New'       => '#10B981',
            'Trending'  => '#F59E0B',
            'Featured'  => '#8B5CF6',
            'Popular'   => '#3B82F6',
            'Exclusive' => '#0F172A',
        ];
    }

    /** All badge presets (canonical + admin-added). */
    public static function badges(): array
    {
        self::ensureSchema();
        return Database::fetchAll("SELECT label, color FROM reward_badge_presets ORDER BY id ASC") ?: [];
    }

    public static function addBadge(string $label, string $color): bool
    {
        self::ensureSchema();
        $label = trim($label);
        if ($label === '' || strlen($label) > 60) return false;
        if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) $color = '#4F46E5';
        try {
            Database::query(
                "INSERT IGNORE INTO reward_badge_presets (label, color) VALUES (?, ?)",
                [$label, $color]
            );
            return true;
        } catch (\Throwable $_) { return false; }
    }

    public static function deleteBadge(string $label): void
    {
        self::ensureSchema();
        try { Database::query("DELETE FROM reward_badge_presets WHERE label = ?", [$label]); }
        catch (\Throwable $_) {}
    }

    /**
     * Rules visible to an affiliate now — applies publish_at, expires_at and
     * visibility filtering. Used by the affiliate-facing list to honour
     * scheduled publishing.
     *
     * @param string $audience  'public' | 'affiliate' | 'vip'
     */
    public static function visibleRules(string $audience = 'affiliate'): array
    {
        self::ensureSchema();
        $vis = ['public'];
        if ($audience === 'affiliate' || $audience === 'vip') $vis[] = 'affiliate';
        if ($audience === 'vip')                              $vis[] = 'vip';
        $ph  = implode(',', array_fill(0, count($vis), '?'));
        try {
            return Database::fetchAll(
                "SELECT * FROM reward_rules
                 WHERE active = 1
                   AND visibility IN ($ph)
                   AND (publish_at IS NULL OR publish_at <= NOW())
                   AND (expires_at IS NULL OR expires_at >  NOW())
                 ORDER BY sort_order ASC, threshold_usd ASC",
                $vis
            ) ?: [];
        } catch (\Throwable $_) { return []; }
    }

    /** Convert a 'YYYY-MM-DDTHH:MM' or empty into a DATETIME / null. */
    private static function normaliseDate($val): ?string
    {
        if ($val === null || $val === '' || $val === '0000-00-00 00:00:00') return null;
        $ts = strtotime((string)$val);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    public static function deleteRule(int $id): void
    {
        self::ensureSchema();
        Database::query("DELETE FROM reward_rules WHERE id = ?", [$id]);
    }

    // ── Granting (called from conversion-approval hook) ─────────────────────

    /**
     * For a given affiliate, find all active rules whose threshold is now
     * met and grant any that haven't been granted yet. Idempotent thanks to
     * the UNIQUE (rule_id, affiliate_id) index — duplicate INSERTs are no-ops.
     */
    public static function checkAndGrant(int $affiliateId): array
    {
        if ($affiliateId <= 0) return [];
        self::ensureSchema();

        // Per-rule earnings: only payouts from conversions that converted
        // INSIDE the reward's window count toward unlocking it.
        //   window start = publish_at (falls back to created_at when null)
        //   window end   = expires_at (no end when null)
        // Earnings before the window started, after the window ended, or made
        // toward another (already-expired) reward never carry across — each
        // reward's counter starts fresh from its own publish_at and freezes at
        // its own expires_at.
        try {
            $rows = Database::fetchAll(
                "SELECT r.*, COALESCE(r.publish_at, r.created_at) AS start_date,
                        COALESCE((
                            SELECT SUM(c.payout)
                            FROM conversions c
                            WHERE c.affiliate_id = ?
                              AND c.status = 'approved'
                              AND COALESCE(c.is_hidden, 0) = 0
                              AND (c.hide_reason IS NULL OR c.hide_reason NOT LIKE '%traffic_back%')
                              AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = c.click_id AND _ck_tb.source = 'traffic_back')
                              AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = c.click_id)
                              AND c.converted_at >= COALESCE(r.publish_at, r.created_at)
                              AND (r.expires_at IS NULL OR c.converted_at <= r.expires_at)
                        ), 0) AS earned_in_window
                 FROM reward_rules r
                 WHERE r.active = 1
                   AND (r.publish_at IS NULL OR r.publish_at <= NOW())
                 ORDER BY r.threshold_usd ASC",
                [$affiliateId]
            ) ?: [];
        } catch (\Throwable $_) { return []; }

        $granted = [];
        foreach ($rows as $rule) {
            $earnedSince = (float)$rule['earned_in_window'];
            if ($earnedSince < (float)$rule['threshold_usd']) continue;

            $rid = (int)$rule['id'];
            try {
                // INSERT IGNORE protects against double-grants under race.
                // lifetime_at_grant stores the per-rule earnings at grant time
                // (the metric that actually triggered the unlock).
                $stmt = Database::query(
                    "INSERT IGNORE INTO reward_grants
                        (rule_id, affiliate_id, lifetime_at_grant, kind, value_amount, value_text, title_snapshot)
                     VALUES (?,?,?,?,?,?,?)",
                    [
                        $rid, $affiliateId, $earnedSince, $rule['kind'],
                        $rule['value_amount'], $rule['value_text'], $rule['title'],
                    ]
                );
                if ($stmt->rowCount() > 0) {
                    $granted[] = $rule;
                    if ($rule['kind'] === 'bonus_credit' && $rule['value_amount'] > 0) {
                        try {
                            Database::query(
                                "UPDATE affiliates SET balance = balance + ? WHERE id = ?",
                                [(float)$rule['value_amount'], $affiliateId]
                            );
                        } catch (\Throwable $_) {}
                    }
                }
            } catch (\Throwable $e) {
                error_log('[RewardsService::checkAndGrant] ' . $e->getMessage());
            }
        }
        return $granted;
    }

    // Earnings counted toward a single reward — approved, non-hidden conversion
    // payouts that fell INSIDE the reward's publish_at → expires_at window.
    // After expires_at the counter freezes: earnings made later don't count
    // (and earnings before publish_at never did).
    public static function earningsSinceRuleStart(int $affiliateId, int $ruleId): float
    {
        if ($affiliateId <= 0 || $ruleId <= 0) return 0.0;
        try {
            $row = Database::fetchOne(
                "SELECT COALESCE((
                    SELECT SUM(c.payout)
                    FROM conversions c
                    WHERE c.affiliate_id = ?
                      AND c.status = 'approved'
                      AND COALESCE(c.is_hidden, 0) = 0
                      AND (c.hide_reason IS NULL OR c.hide_reason NOT LIKE '%traffic_back%')
                      AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = c.click_id AND _ck_tb.source = 'traffic_back')
                      AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = c.click_id)
                      AND c.converted_at >= COALESCE(r.publish_at, r.created_at)
                      AND (r.expires_at IS NULL OR c.converted_at <= r.expires_at)
                ), 0) AS earned_in_window
                FROM reward_rules r
                WHERE r.id = ? LIMIT 1",
                [$affiliateId, $ruleId]
            );
            return (float)($row['earned_in_window'] ?? 0);
        } catch (\Throwable $_) { return 0.0; }
    }

    public static function grantsForAffiliate(int $affiliateId, int $limit = 100): array
    {
        self::ensureSchema();
        return Database::fetchAll(
            "SELECT g.*, r.threshold_usd
             FROM reward_grants g
             LEFT JOIN reward_rules r ON r.id = g.rule_id
             WHERE g.affiliate_id = ?
             ORDER BY g.granted_at DESC LIMIT $limit",
            [$affiliateId]
        ) ?: [];
    }

    public static function grants(int $limit = 200, ?string $status = null): array
    {
        self::ensureSchema();
        $sql = "SELECT g.*, r.threshold_usd,
                       CONCAT(u.first_name,' ',u.last_name) AS aff_name, u.email AS aff_email
                FROM reward_grants g
                LEFT JOIN reward_rules r ON r.id = g.rule_id
                LEFT JOIN affiliates af ON af.id = g.affiliate_id
                LEFT JOIN users u ON u.id = af.user_id";
        $params = [];
        if ($status) { $sql .= " WHERE g.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY g.granted_at DESC LIMIT $limit";
        return Database::fetchAll($sql, $params) ?: [];
    }

    public static function updateGrantStatus(int $grantId, string $status, ?string $note = null): bool
    {
        self::ensureSchema();
        $allowed = ['granted','claimed','paid','cancelled'];
        if (!in_array($status, $allowed, true)) return false;
        $upd = ['status' => $status];
        if ($note !== null) $upd['admin_note'] = $note;
        Database::update('reward_grants', $upd, 'id = ?', [$grantId]);
        return true;
    }
}
