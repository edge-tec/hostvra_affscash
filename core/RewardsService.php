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
                `claimed_at`      DATETIME NULL DEFAULT NULL,
                `email_sent_affiliate` TINYINT(1) NOT NULL DEFAULT 0,
                `email_sent_admin`     TINYINT(1) NOT NULL DEFAULT 0,
                `admin_note`      TEXT DEFAULT NULL,
                UNIQUE KEY `uq_rule_aff` (`rule_id`,`affiliate_id`),
                KEY `idx_aff`    (`affiliate_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        // Additive columns for reward_grants
        $grantCols = [
            'email_sent_affiliate' => "TINYINT(1) NOT NULL DEFAULT 0",
            'email_sent_admin'     => "TINYINT(1) NOT NULL DEFAULT 0",
            'claimed_at'           => "DATETIME NULL DEFAULT NULL",
        ];
        foreach ($grantCols as $col => $type) {
            try {
                $exists = Database::fetchOne(
                    "SELECT 1 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reward_grants' AND COLUMN_NAME = ?",
                    [$col]
                );
                if (!$exists) {
                    Database::query("ALTER TABLE reward_grants ADD COLUMN `$col` $type");
                }
            } catch (\Throwable $_) {}
        }

        // Seed default email templates for reward claims if missing
        try {
            $affTpl = '<div style="font-family:sans-serif;line-height:1.6;color:#0F172A">'
                . '<h2 style="color:#4F46E5;margin-top:0">🎉 Congratulations {{affiliate_name}}!</h2>'
                . '<p>You have successfully unlocked and claimed your reward on <strong>{{site_name}}</strong>.</p>'
                . '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px;margin:16px 0">'
                . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
                . '<tr><td style="padding:6px 0;color:#64748B">Reward Name:</td><td style="padding:6px 0;font-weight:700;color:#0F172A">{{reward_title}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Reward Value:</td><td style="padding:6px 0;font-weight:700;color:#10B981">{{reward_value}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Grant / Claim ID:</td><td style="padding:6px 0;font-weight:600;color:#0F172A">#{{grant_id}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Claim Date & Time:</td><td style="padding:6px 0;color:#0F172A">{{claim_date}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Current Status:</td><td style="padding:6px 0;font-weight:700;color:#2563EB">{{status}}</td></tr>'
                . '</table></div>'
                . '<p style="font-size:13px;color:#475569;background:#F1F5F9;padding:12px;border-radius:8px">'
                . 'ℹ️ <strong>Fulfillment Notice:</strong> Reward processing and physical gift fulfillment may take <strong>up to 30 days</strong>. If you have any questions, please contact our support team.'
                . '</p></div>';

            Database::query(
                "INSERT IGNORE INTO `email_templates` (`event_type`, `label`, `subject`, `html_body`, `is_active`)
                 VALUES ('reward_claimed_affiliate', 'Reward Claimed (Affiliate)', 'Reward Successfully Claimed – {{reward_title}}', ?, 1)",
                [$affTpl]
            );

            $admTpl = '<div style="font-family:sans-serif;line-height:1.6;color:#0F172A">'
                . '<h2 style="color:#0F172A;margin-top:0">🎁 New Reward Claim Alert</h2>'
                . '<p>An affiliate has unlocked and claimed a milestone reward on <strong>{{site_name}}</strong>.</p>'
                . '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px;margin:16px 0">'
                . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
                . '<tr><td style="padding:6px 0;color:#64748B">Affiliate Name:</td><td style="padding:6px 0;font-weight:700;color:#0F172A">{{affiliate_name}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Affiliate ID:</td><td style="padding:6px 0;font-weight:600;color:#0F172A">#{{affiliate_id}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Affiliate Email:</td><td style="padding:6px 0;color:#0F172A">{{affiliate_email}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Reward Name:</td><td style="padding:6px 0;font-weight:700;color:#0F172A">{{reward_title}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Reward Value:</td><td style="padding:6px 0;font-weight:700;color:#10B981">{{reward_value}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Grant / Claim ID:</td><td style="padding:6px 0;font-weight:600;color:#0F172A">#{{grant_id}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Claim Date & Time:</td><td style="padding:6px 0;color:#0F172A">{{claim_date}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Status:</td><td style="padding:6px 0;font-weight:700;color:#2563EB">{{status}}</td></tr>'
                . '<tr><td style="padding:6px 0;color:#64748B">Processing Window:</td><td style="padding:6px 0;color:#475569">Up to 30 days fulfillment target</td></tr>'
                . '</table></div>'
                . '<p style="font-size:13px;color:#475569">'
                . 'You can review and process this reward within the standard 30-day fulfillment window in your Admin Dashboard under <a href="{{admin_link}}" style="color:#4F46E5;font-weight:600">Rewards Management</a>.'
                . '</p></div>';

            Database::query(
                "INSERT IGNORE INTO `email_templates` (`event_type`, `label`, `subject`, `html_body`, `is_active`)
                 VALUES ('reward_claimed_admin', 'Reward Claimed (Admin Alert)', 'New Reward Claim – {{affiliate_name}} (#{{affiliate_id}})', ?, 1)",
                [$admTpl]
            );
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
     * Get the timestamp of the affiliate's last unlocked reward grant.
     * Returns null if the affiliate has not unlocked any rewards yet.
     */
    public static function lastGrantTimestamp(int $affiliateId): ?string
    {
        if ($affiliateId <= 0) return null;
        self::ensureSchema();
        try {
            $row = Database::fetchOne(
                "SELECT MAX(granted_at) AS last_ts FROM reward_grants WHERE affiliate_id = ?",
                [$affiliateId]
            );
            return !empty($row['last_ts']) ? (string)$row['last_ts'] : null;
        } catch (\Throwable $_) { return null; }
    }

    /**
     * Calculate current cycle earnings for an affiliate toward a reward rule.
     *
     * Independent Cycle Logic:
     * - Baseline start date is the LATER of:
     *   1) The reward rule's publish_at / created_at date.
     *   2) The affiliate's last unlocked reward grant timestamp (granted_at).
     * - Conversions before the last unlocked reward are consumed/reset to $0.
     * - Only approved non-hidden conversions after the last grant timestamp count.
     */
    public static function currentCycleEarnings(int $affiliateId, int $ruleId): float
    {
        if ($affiliateId <= 0 || $ruleId <= 0) return 0.0;
        self::ensureSchema();
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
                      AND c.converted_at >= GREATEST(
                          COALESCE(r.publish_at, r.created_at),
                          COALESCE((SELECT MAX(g.granted_at) FROM reward_grants g WHERE g.affiliate_id = c.affiliate_id), '1970-01-01 00:00:00')
                      )
                      AND (r.expires_at IS NULL OR c.converted_at <= r.expires_at)
                ), 0) AS cycle_earnings
                FROM reward_rules r
                WHERE r.id = ? LIMIT 1",
                [$affiliateId, $ruleId]
            );
            return (float)($row['cycle_earnings'] ?? 0);
        } catch (\Throwable $_) { return 0.0; }
    }

    /** Alias for backward compatibility */
    public static function earningsSinceRuleStart(int $affiliateId, int $ruleId): float
    {
        return self::currentCycleEarnings($affiliateId, $ruleId);
    }

    // ── Granting (called from conversion-approval hook) ─────────────────────

    /**
     * For a given affiliate, check active un-granted rules and unlock the next
     * rule whose target cycle threshold is now met.
     *
     * Reset & Cycle Protection:
     * - Unlocking a reward inserts a record into reward_grants with granted_at = NOW().
     * - Instantly resets the qualifying earnings baseline to $0.00 for subsequent rules.
     * - Unlocks only ONE reward per completed target cycle so multiple rewards
     *   are never unlocked simultaneously from the same historical pool.
     */
    public static function checkAndGrant(int $affiliateId): array
    {
        if ($affiliateId <= 0) return [];
        self::ensureSchema();

        try {
            $rules = Database::fetchAll(
                "SELECT r.*, COALESCE(r.publish_at, r.created_at) AS start_date
                 FROM reward_rules r
                 WHERE r.active = 1
                   AND (r.publish_at IS NULL OR r.publish_at <= NOW())
                   AND (r.expires_at IS NULL OR r.expires_at >= NOW())
                   AND r.id NOT IN (SELECT rule_id FROM reward_grants WHERE affiliate_id = ?)
                 ORDER BY r.sort_order ASC, r.threshold_usd ASC",
                [$affiliateId]
            ) ?: [];
        } catch (\Throwable $_) { return []; }

        $granted = [];
        foreach ($rules as $rule) {
            $rid = (int)$rule['id'];
            $cycleEarned = self::currentCycleEarnings($affiliateId, $rid);
            if ($cycleEarned < (float)$rule['threshold_usd']) {
                continue;
            }

            try {
                // INSERT IGNORE protects against double-grants under race conditions.
                $stmt = Database::query(
                    "INSERT IGNORE INTO reward_grants
                        (rule_id, affiliate_id, lifetime_at_grant, kind, value_amount, value_text, title_snapshot, granted_at)
                     VALUES (?,?,?,?,?,?,?, NOW())",
                    [
                        $rid, $affiliateId, $cycleEarned, $rule['kind'],
                        $rule['value_amount'], $rule['value_text'], $rule['title'],
                    ]
                );
                if ($stmt->rowCount() > 0) {
                    $grantId   = (int)Database::lastInsertId();
                    $granted[] = $rule;
                    if ($rule['kind'] === 'bonus_credit' && $rule['value_amount'] > 0) {
                        try {
                            Database::query(
                                "UPDATE affiliates SET balance = balance + ? WHERE id = ?",
                                [(float)$rule['value_amount'], $affiliateId]
                            );
                        } catch (\Throwable $_) {}
                    }
                    // Trigger email notifications automatically for affiliate and admin
                    try {
                        self::sendRewardClaimNotifications($grantId);
                    } catch (\Throwable $_e) {
                        error_log('[RewardsService::checkAndGrant] Email notification error: ' . $_e->getMessage());
                    }
                    // After granting ONE reward, granted_at is set to NOW(), which
                    // resets current cycle progress to $0 for the next reward. Break loop.
                    break;
                }
            } catch (\Throwable $e) {
                error_log('[RewardsService::checkAndGrant] ' . $e->getMessage());
            }
        }
        return $granted;
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
        if ($status === 'claimed') $upd['claimed_at'] = date('Y-m-d H:i:s');
        if ($note !== null) $upd['admin_note'] = $note;
        Database::update('reward_grants', $upd, 'id = ?', [$grantId]);
        return true;
    }

    /**
     * Send email notifications for a reward grant/claim to both Affiliate and Admin.
     * Idempotent & secure with delivery status logging in email_logs and reward_grants.
     *
     * @param int  $grantId     ID from reward_grants table
     * @param bool $forceRetry  If true, forces resending even if previously sent
     * @return array  ['affiliate_sent' => bool, 'admin_sent' => bool, 'errors' => array]
     */
    public static function sendRewardClaimNotifications(int $grantId, bool $forceRetry = false): array
    {
        self::ensureSchema();
        $result = ['affiliate_sent' => false, 'admin_sent' => false, 'errors' => []];

        if ($grantId <= 0) {
            $result['errors'][] = 'Invalid grant ID.';
            return $result;
        }

        try {
            $grant = Database::fetchOne(
                "SELECT g.*,
                        CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS aff_name,
                        u.email AS aff_email,
                        af.id AS aff_id,
                        af.user_id AS aff_user_id
                 FROM reward_grants g
                 JOIN affiliates af ON af.id = g.affiliate_id
                 JOIN users u ON u.id = af.user_id
                 WHERE g.id = ? LIMIT 1",
                [$grantId]
            );
            if (!$grant) {
                $result['errors'][] = "Grant #{$grantId} not found.";
                return $result;
            }

            $affName  = trim((string)$grant['aff_name']) ?: ('Affiliate #' . $grant['affiliate_id']);
            $affEmail = trim((string)$grant['aff_email']);
            $valLabel = ($grant['kind'] === 'cash' || $grant['kind'] === 'bonus_credit')
                ? '$' . number_format((float)$grant['value_amount'], 2)
                : (string)($grant['value_text'] ?: ucfirst($grant['kind']));

            $vars = [
                'affiliate_id'    => (int)$grant['affiliate_id'],
                'affiliate_name'  => $affName,
                'affiliate_email' => $affEmail,
                'reward_title'    => (string)$grant['title_snapshot'],
                'reward_value'    => $valLabel,
                'grant_id'        => (int)$grant['id'],
                'claim_date'      => date('F j, Y, g:i a', strtotime((string)$grant['granted_at'])),
                'status'          => ucfirst((string)$grant['status']),
                'admin_link'      => (Config::get('app.url') ?? '') . '/admin/rewards?tab=grants',
                'processing_days' => 'Up to 30 days',
            ];

            // 1. Send Affiliate Email Notification
            if (empty($grant['email_sent_affiliate']) || $forceRetry) {
                if (!filter_var($affEmail, FILTER_VALIDATE_EMAIL)) {
                    $result['errors'][] = "Invalid affiliate email address: '{$affEmail}'";
                } else {
                    $sent = Mailer::sendEvent($affEmail, $affName, 'reward_claimed_affiliate', $vars);
                    if ($sent) {
                        $result['affiliate_sent'] = true;
                        Database::query("UPDATE reward_grants SET email_sent_affiliate = 1 WHERE id = ?", [$grantId]);
                    } else {
                        $result['errors'][] = "Failed to deliver email to affiliate ({$affEmail}). Check SMTP configuration.";
                    }
                }
            } else {
                $result['affiliate_sent'] = true; // Already sent previously
            }

            // 2. Send Admin Email Notification
            if (empty($grant['email_sent_admin']) || $forceRetry) {
                $cfg = Config::get('config') ?? [];
                $adminEmail = $cfg['smtp']['from_email'] 
                    ?? $cfg['app']['admin_email'] 
                    ?? Database::fetchColumn("SELECT email FROM users WHERE role='admin' AND is_active=1 LIMIT 1") 
                    ?? 'admin@localhost';

                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $result['errors'][] = "Invalid admin email address: '{$adminEmail}'";
                } else {
                    $sentAdm = Mailer::sendEvent($adminEmail, 'Admin', 'reward_claimed_admin', $vars);
                    if ($sentAdm) {
                        $result['admin_sent'] = true;
                        Database::query("UPDATE reward_grants SET email_sent_admin = 1 WHERE id = ?", [$grantId]);
                    } else {
                        $result['errors'][] = "Failed to deliver notification to admin ({$adminEmail}). Check SMTP configuration.";
                    }
                }
            } else {
                $result['admin_sent'] = true; // Already sent previously
            }

            // 3. Instant In-App Notification Bell & FCM Push for Affiliate Dashboard
            try {
                $affUserId = (int)$grant['aff_user_id'];
                if ($affUserId > 0) {
                    $notifTitle = '🎉 Reward Unlocked: ' . $grant['title_snapshot'];
                    $notifMsg   = 'Congratulations! You unlocked the reward "' . $grant['title_snapshot'] . '" (' . $valLabel . '). Check your rewards dashboard to claim.';

                    $existsInApp = Database::fetchOne(
                        "SELECT 1 FROM notifications WHERE user_id = ? AND title = ? LIMIT 1",
                        [$affUserId, $notifTitle]
                    );
                    if (!$existsInApp || $forceRetry) {
                        Database::insert('notifications', [
                            'user_id'     => $affUserId,
                            'target_role' => 'affiliate',
                            'type'        => 'success',
                            'title'       => $notifTitle,
                            'message'     => $notifMsg,
                            'link'        => '/affiliate/rewards',
                            'is_read'     => 0,
                            'created_at'  => date('Y-m-d H:i:s'),
                        ]);

                        // FCM Push Notification to Mobile App (if enabled)
                        if (file_exists(BASE_PATH . '/core/FirebaseMessaging.php')) {
                            require_once BASE_PATH . '/core/FirebaseMessaging.php';
                            @FirebaseMessaging::sendToUser(
                                $affUserId,
                                $notifTitle,
                                "You unlocked the reward \"" . $grant['title_snapshot'] . "\" (" . $valLabel . "). Tap to view.",
                                ['type' => 'reward', 'deep_link_route' => 'rewards']
                            );
                        }
                    }
                }
            } catch (\Throwable $ne) {
                error_log('[RewardsService::sendRewardClaimNotifications] In-app notification error: ' . $ne->getMessage());
            }

            // 4. Instant In-App Notification for Admin Dashboard
            try {
                $admNotifTitle = '🎁 New Reward Claim Alert';
                $admNotifMsg   = $affName . ' (ID #' . $grant['affiliate_id'] . ') claimed reward "' . $grant['title_snapshot'] . '" (' . $valLabel . ').';

                $existsAdmNotif = Database::fetchOne(
                    "SELECT 1 FROM notifications WHERE target_role = 'admin' AND title = ? AND message = ? LIMIT 1",
                    [$admNotifTitle, $admNotifMsg]
                );
                if (!$existsAdmNotif || $forceRetry) {
                    Database::insert('notifications', [
                        'user_id'     => null,
                        'target_role' => 'admin',
                        'type'        => 'info',
                        'title'       => $admNotifTitle,
                        'message'     => $admNotifMsg,
                        'link'        => '/admin/rewards?tab=grants',
                        'is_read'     => 0,
                        'created_at'  => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $ane) {
                error_log('[RewardsService::sendRewardClaimNotifications] Admin in-app notification error: ' . $ane->getMessage());
            }

        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            error_log('[RewardsService::sendRewardClaimNotifications] Error: ' . $e->getMessage());
        }

        return $result;
    }
}
