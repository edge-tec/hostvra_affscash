<?php
/**
 * ManagerCommissionService
 *
 * Commission formula:
 *   Revenue source: conversions.revenue (actual) → offers.revenue_amount (fallback) → payout
 *
 *   When revenue > affiliate_payout:
 *     net_profit = revenue - payout
 *     commission = net_profit × (rate / 100)
 *
 *   When revenue = 0 or revenue ≤ payout (no margin configured):
 *     commission = payout × (rate / 100)   ← payout-based fallback
 *
 * Key design decisions:
 *   - All DB inserts use a single SQL INSERT IGNORE … SELECT (no PHP loops that fail silently)
 *   - calc_mode column is NEVER required in INSERT — uses DEFAULT, added separately
 *   - Balance is always rebuilt from manager_commissions SUM, not incremented piecemeal
 *   - ensureSchema() uses compatible ALTER TABLE syntax (no IF NOT EXISTS on ADD COLUMN)
 *   - No retroactive date guard on recordForConversion() — new conversions always post-date assignment
 */
class ManagerCommissionService
{
    private static bool $_schemaEnsured = false;

    // ─────────────────────────────────────────────────────────────────────────
    // Ensure all tables / columns exist. MySQL 5.7 compatible.
    // ─────────────────────────────────────────────────────────────────────────
    private static function ensureSchema(): void
    {
        if (self::$_schemaEnsured) return;
        self::$_schemaEnsured = true;

        // ── manager_commissions ──────────────────────────────────────────────
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_commissions` (
                `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`         INT UNSIGNED NOT NULL,
                `affiliate_id`       INT UNSIGNED NOT NULL,
                `conversion_id`      INT UNSIGNED NOT NULL,
                `offer_id`           INT UNSIGNED NOT NULL,
                `is_smartlink`       TINYINT(1) NOT NULL DEFAULT 0,
                `advertiser_revenue` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `affiliate_payout`   DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `net_profit`         DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `commission_rate`    DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
                `commission_amount`  DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `calc_mode`          VARCHAR(20)   NOT NULL DEFAULT 'payout_based',
                `status`             ENUM('pending','approved','paid','reversed') NOT NULL DEFAULT 'pending',
                `calculated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_conversion` (`conversion_id`),
                KEY `idx_manager_id`   (`manager_id`),
                KEY `idx_affiliate_id` (`affiliate_id`),
                KEY `idx_status`       (`status`),
                KEY `idx_calculated`   (`calculated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            error_log('[MCS] create manager_commissions: ' . $e->getMessage());
        }

        // Add calc_mode if table existed without it (MySQL 5.7 compatible — no IF NOT EXISTS)
        try {
            $has = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='manager_commissions' AND COLUMN_NAME='calc_mode'"
            );
            if (!$has) {
                Database::query("ALTER TABLE manager_commissions ADD COLUMN calc_mode VARCHAR(20) NOT NULL DEFAULT 'payout_based' AFTER commission_amount");
            }
        } catch (\Throwable $e) {}

        // ── commission_logs ──────────────────────────────────────────────────
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `commission_logs` (
                `id`                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`         INT UNSIGNED NOT NULL DEFAULT 0,
                `affiliate_id`       INT UNSIGNED NOT NULL DEFAULT 0,
                `conversion_id`      BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `offer_id`           INT UNSIGNED NOT NULL DEFAULT 0,
                `advertiser_revenue` DECIMAL(10,4) NOT NULL DEFAULT 0,
                `affiliate_payout`   DECIMAL(10,4) NOT NULL DEFAULT 0,
                `net_profit`         DECIMAL(10,4) NOT NULL DEFAULT 0,
                `commission_rate`    DECIMAL(5,2)  NOT NULL DEFAULT 0,
                `commission_amount`  DECIMAL(10,4) NOT NULL DEFAULT 0,
                `calc_mode`          VARCHAR(20)   NOT NULL DEFAULT 'payout_based',
                `result`             ENUM('credited','skipped','duplicate') NOT NULL DEFAULT 'skipped',
                `skip_reason`        VARCHAR(500) DEFAULT NULL,
                `logged_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_manager`    (`manager_id`),
                KEY `idx_conversion` (`conversion_id`),
                KEY `idx_logged`     (`logged_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}

        // ── affiliate_managers.commission_rate + balance ─────────────────────
        try {
            $hasCR = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliate_managers' AND COLUMN_NAME='commission_rate'"
            );
            if (!$hasCR) {
                Database::query("ALTER TABLE affiliate_managers ADD COLUMN commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00");
            }
        } catch (\Throwable $e) {}
        try {
            $hasBalance = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliate_managers' AND COLUMN_NAME='balance'"
            );
            if (!$hasBalance) {
                Database::query("ALTER TABLE affiliate_managers ADD COLUMN balance DECIMAL(10,4) NOT NULL DEFAULT 0.0000");
            }
        } catch (\Throwable $e) {}

        // ── affiliate_managers.commission_mode (per-manager: 'all' or 'selected_only') ─
        try {
            $hasCM = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliate_managers' AND COLUMN_NAME='commission_mode'"
            );
            if (!$hasCM) {
                Database::query("ALTER TABLE affiliate_managers ADD COLUMN commission_mode VARCHAR(20) NOT NULL DEFAULT 'all'");
            }
        } catch (\Throwable $e) {}

        // ── affiliates.manager_assigned_at ───────────────────────────────────
        try {
            $hasAssigned = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME='manager_assigned_at'"
            );
            if (!$hasAssigned) {
                Database::query("ALTER TABLE affiliates ADD COLUMN manager_assigned_at DATETIME DEFAULT NULL");
            }
        } catch (\Throwable $e) {}
        // One-time fix: reset manager_assigned_at timestamps that were erroneously set to NOW().
        // Guard with a flag row so this only runs once, not on every request.
        try {
            $fixDone = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME='manager_assigned_at'"
            );
            if ($fixDone) {
                // Only do the reset once — check if any row is still in the future (i.e. was set to NOW())
                $futureRow = Database::fetchOne(
                    "SELECT 1 FROM affiliates WHERE manager_id IS NOT NULL AND manager_assigned_at > '2010-01-01 00:00:00' LIMIT 1"
                );
                if ($futureRow) {
                    Database::query(
                        "UPDATE affiliates SET manager_assigned_at = '2000-01-01 00:00:00' WHERE manager_id IS NOT NULL AND manager_assigned_at > '2010-01-01 00:00:00'"
                    );
                }
            }
        } catch (\Throwable $e) {}

        // ── conversions: ensure revenue column exists (used in offer join fallback) ─
        try {
            $hasRev = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='conversions' AND COLUMN_NAME='revenue'"
            );
            if (!$hasRev) {
                Database::query("ALTER TABLE conversions ADD COLUMN revenue DECIMAL(10,4) NOT NULL DEFAULT 0.0000");
            }
        } catch (\Throwable $e) {}

        // ── invoices.type ENUM → VARCHAR ─────────────────────────────────────
        try {
            $typeCol = Database::fetchOne(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='invoices' AND COLUMN_NAME='type'"
            );
            if ($typeCol && stripos((string)($typeCol['COLUMN_TYPE'] ?? ''), 'enum') !== false) {
                Database::query("ALTER TABLE invoices MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'affiliate_payout'");
            }
        } catch (\Throwable $e) {}

        // ── manager_offer_commissions (per-offer commission overrides) ───────
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_offer_commissions` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`      INT UNSIGNED NOT NULL,
                `offer_id`        INT UNSIGNED NOT NULL,
                `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_mgr_offer` (`manager_id`,`offer_id`),
                KEY `idx_manager` (`manager_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}

        // ── offers.manager_commission_enabled (per-offer eligibility flag) ───
        // Used together with the `commission.manager_offer_mode` config to
        // decide whether a conversion on a given offer should generate a
        // manager commission. Defaults to 1 so existing offers keep working
        // exactly as before the switch was introduced (back-compat).
        try {
            $hasMgrEligible = Database::fetchOne(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='offers' AND COLUMN_NAME='manager_commission_enabled'"
            );
            if (!$hasMgrEligible) {
                Database::query("ALTER TABLE offers ADD COLUMN manager_commission_enabled TINYINT(1) NOT NULL DEFAULT 1");
            }
        } catch (\Throwable $e) {}

        // ── offer_id index helper for the per-offer override lookup ──────────
        try {
            $idx = Database::fetchOne(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='manager_offer_commissions' AND INDEX_NAME='idx_offer'"
            );
            if (!$idx) {
                Database::query("ALTER TABLE manager_offer_commissions ADD INDEX idx_offer (offer_id)");
            }
        } catch (\Throwable $e) {}

        // ── invoices extra columns ───────────────────────────────────────────
        foreach (['manager_id INT UNSIGNED DEFAULT NULL', 'balance_before DECIMAL(10,4) DEFAULT NULL', 'balance_after DECIMAL(10,4) DEFAULT NULL'] as $colDef) {
            $colName = explode(' ', $colDef)[0];
            try {
                $exists = Database::fetchOne(
                    "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='invoices' AND COLUMN_NAME=?",
                    [$colName]
                );
                if (!$exists) {
                    Database::query("ALTER TABLE invoices ADD COLUMN {$colDef}");
                }
            } catch (\Throwable $e) {}
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin-configurable defaults read from Config (commission.* keys).
    //   manager_default_rate : applied when neither the manager nor the offer
    //                          has an explicit rate. Spec default = 10%.
    //   manager_offer_mode   : 'all'           → every offer is eligible (back-compat)
    //                          'selected_only' → only offers with
    //                          offers.manager_commission_enabled=1 generate commission
    // ─────────────────────────────────────────────────────────────────────────
    public static function defaultRate(): float
    {
        $val = Config::get('config', 'commission.manager_default_rate');
        $rate = $val === null || $val === '' ? 10.0 : (float)$val;
        if ($rate < 0)   $rate = 0.0;
        if ($rate > 100) $rate = 100.0;
        return $rate;
    }

    /**
     * Get the commission offer mode for a specific manager.
     * Per-manager value ('all' or 'selected_only') stored in affiliate_managers.commission_mode.
     * Falls back to global config if not set on the manager record.
     */
    public static function offerMode(int $managerId = 0): string
    {
        // Per-manager mode (takes priority)
        if ($managerId > 0) {
            try {
                $row = Database::fetchOne(
                    "SELECT commission_mode FROM affiliate_managers WHERE id = ? LIMIT 1",
                    [$managerId]
                );
                if ($row && in_array($row['commission_mode'], ['all', 'selected_only'], true)) {
                    return $row['commission_mode'];
                }
            } catch (\Throwable $e) {}
        }
        // Fallback: global config
        $m = (string)(Config::get('config', 'commission.manager_offer_mode') ?? 'all');
        return $m === 'selected_only' ? 'selected_only' : 'all';
    }

    // Resolve eligibility for a single offer + manager pair.
    // SmartLink conversions (offer_id=0) are always eligible.
    //
    // When mode='selected_only' eligibility is determined per-manager:
    //   the offer must exist in manager_offer_commissions for that manager.
    //   The global offers.manager_commission_enabled flag is no longer used
    //   for this check — it was a global gate that incorrectly blocked the
    //   offer for ALL managers whenever one manager hadn't selected it.
    //
    // When mode='all' every offer is eligible (back-compat).
    public static function isOfferEligible(int $offerId, int $managerId = 0): bool
    {
        if ($offerId <= 0) return true;                       // SmartLink / custom URL
        if (self::offerMode($managerId) === 'all') return true;

        // selected_only: check per-manager table, not global offer flag
        if ($managerId > 0) {
            try {
                $row = Database::fetchOne(
                    "SELECT id FROM manager_offer_commissions
                     WHERE manager_id = ? AND offer_id = ? LIMIT 1",
                    [$managerId, $offerId]
                );
                return (bool)$row;   // true = offer is in the manager's selected list
            } catch (\Throwable $e) {
                return true;   // fail-open — never silently drop commissions on a DB blip
            }
        }

        // Fallback (no manager context): check global flag for backfill path
        try {
            $row = Database::fetchOne(
                "SELECT COALESCE(manager_commission_enabled,1) AS en FROM offers WHERE id=? LIMIT 1",
                [$offerId]
            );
            return (int)($row['en'] ?? 1) === 1;
        } catch (\Throwable $e) {
            return true;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rebuild balance for a manager from the actual commission_amount SUM.
    // Call this after any backfill / adjustment to ensure balance is accurate.
    // ─────────────────────────────────────────────────────────────────────────
    public static function rebuildBalance(int $managerId): float
    {
        if ($managerId <= 0) return 0.0;
        try {
            Database::query(
                "UPDATE affiliate_managers
                 SET balance = (
                     SELECT COALESCE(SUM(commission_amount), 0)
                     FROM manager_commissions
                     WHERE manager_id = ? AND status IN ('pending','approved')
                 )
                 WHERE id = ?",
                [$managerId, $managerId]
            );
            $row = Database::fetchOne("SELECT balance FROM affiliate_managers WHERE id = ?", [$managerId]);
            return (float)($row['balance'] ?? 0);
        } catch (\Throwable $e) {
            error_log('[MCS] rebuildBalance: ' . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Record commission for a single conversion (called from postback + ConversionController).
    // Uses same SQL INSERT IGNORE approach — no calc_mode dependency.
    // ─────────────────────────────────────────────────────────────────────────
    public static function recordForConversion(int $conversionDbId): void
    {
        if ($conversionDbId <= 0) return;
        self::ensureSchema();

        // Load conversion + revenue (LEFT JOIN so SmartLink conversions with NULL offer_id are included)
        // Use actual conversion revenue (c.revenue) first; fall back to current offer rate.
        $conv = Database::fetchOne(
            "SELECT c.id, c.offer_id, c.affiliate_id,
                    c.payout                                                        AS payout,
                    COALESCE(NULLIF(c.revenue, 0), NULLIF(o.revenue_amount, 0), 0)  AS offer_revenue,
                    c.status,
                    c.is_hidden,
                    c.converted_at
             FROM conversions c
             LEFT JOIN offers o ON o.id = c.offer_id
             WHERE c.id = ?
             LIMIT 1",
            [$conversionDbId]
        );
        if (!$conv) return;

        // Only commission approved, non-hidden conversions
        if (($conv['status'] ?? '') !== 'approved' || !empty($conv['is_hidden'])) return;

        // Load manager
        $mgr = Database::fetchOne(
            "SELECT am.id AS manager_id, am.commission_rate
             FROM affiliates af
             JOIN affiliate_managers am ON am.id = af.manager_id
             WHERE af.id = ?
             LIMIT 1",
            [(int)$conv['affiliate_id']]
        );
        if (!$mgr) return;

        $managerId      = (int)$mgr['manager_id'];
        $commissionRate = (float)($mgr['commission_rate'] ?? 0);

        // ── Per-offer commission override takes top priority. ───────────────
        try {
            $offerOverride = Database::fetchOne(
                "SELECT commission_rate FROM manager_offer_commissions
                 WHERE manager_id=? AND offer_id=? LIMIT 1",
                [$managerId, (int)$conv['offer_id']]
            );
            if ($offerOverride) {
                $commissionRate = (float)$offerOverride['commission_rate'];
            }
        } catch (\Throwable $e) {}

        // ── Fall back to the admin-set global default if neither the manager
        //    nor any per-offer override has a non-zero rate. Default = 10%.
        if ($commissionRate <= 0) {
            $commissionRate = self::defaultRate();
        }

        if ($commissionRate <= 0) return;

        // ── Offer-level eligibility gate. When the admin has set
        //    commission.manager_offer_mode = 'selected_only', the offer must
        //    be in manager_offer_commissions for this specific manager.
        if (!self::isOfferEligible((int)($conv['offer_id'] ?? 0), $managerId)) {
            try {
                self::writeLog($managerId, (int)$conv['affiliate_id'], (int)$conv['id'],
                    (int)($conv['offer_id'] ?? 0), (float)$conv['offer_revenue'],
                    (float)$conv['payout'], 0, $commissionRate, 0, 'payout_based',
                    'skipped', 'offer_not_eligible_for_manager_commission');
            } catch (\Throwable $_) {}
            return;
        }

        $payout       = (float)$conv['payout'];
        $offerRevenue = (float)$conv['offer_revenue'];

        if ($payout <= 0) return;

        // Commission base: net profit if offer revenue > payout, else payout itself
        if ($offerRevenue > $payout) {
            $base     = round($offerRevenue - $payout, 4);
            $revenue  = $offerRevenue;
            $calcMode = 'net_profit';
        } else {
            $base     = $payout;
            $revenue  = $payout;
            $calcMode = 'payout_based';
        }

        $commissionAmount = round($base * $commissionRate / 100, 4);

        $isSmartlink = empty($conv['offer_id']) ? 1 : 0;

        try {
            $stmt = Database::query(
                "INSERT IGNORE INTO manager_commissions
                     (manager_id, affiliate_id, conversion_id, offer_id,
                      is_smartlink,
                      advertiser_revenue, affiliate_payout,
                      net_profit, commission_rate, commission_amount,
                      status, calculated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [
                    $managerId, (int)$conv['affiliate_id'],
                    (int)$conv['id'], (int)($conv['offer_id'] ?? 0),
                    $isSmartlink,
                    $revenue, $payout,
                    $base, $commissionRate, $commissionAmount,
                ]
            );

            if ($stmt->rowCount() > 0) {
                // Credit balance atomically
                Database::query(
                    "UPDATE affiliate_managers SET balance = balance + ? WHERE id = ?",
                    [$commissionAmount, $managerId]
                );
                // Log success
                self::writeLog($managerId, (int)$conv['affiliate_id'], (int)$conv['id'],
                    (int)$conv['offer_id'], $revenue, $payout, $base,
                    $commissionRate, $commissionAmount, $calcMode, 'credited', '');
            }
        } catch (\Throwable $e) {
            error_log('[MCS] recordForConversion(' . $conversionDbId . '): ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Backfill all approved conversions for a manager using a single SQL
    // INSERT IGNORE … SELECT.  No PHP loop.  No calc_mode dependency.
    // After inserting, rebuilds the balance from the actual SUM.
    // ─────────────────────────────────────────────────────────────────────────
    public static function backfillForManager(
        int    $managerId,
        string $fromDate = '',
        string $toDate   = ''
    ): array {
        if ($managerId <= 0) return ['processed'=>0,'credited'=>0,'skipped'=>0,'total_added'=>0.0];
        self::ensureSchema();

        $result = ['processed'=>0, 'credited'=>0, 'skipped'=>0, 'total_added'=>0.0, 'error'=>''];

        try {
            // Manager rate (may be 0; the bulk SELECT below falls back to the
            // global default when it is). We no longer early-exit on a zero
            // manager rate — the admin-configured default kicks in.
            $mgr = Database::fetchOne(
                "SELECT commission_rate FROM affiliate_managers WHERE id = ?",
                [$managerId]
            );
            if (!$mgr) return $result;
            $globalDefault = self::defaultRate();
            $effectiveRate = (float)($mgr['commission_rate'] ?? 0);
            if ($effectiveRate <= 0) $effectiveRate = $globalDefault;
            if ($effectiveRate <= 0) return $result;

            // Offer eligibility gate — when this manager is in 'selected_only' mode,
            // only include offers the manager has explicitly selected
            // (i.e. rows exist in manager_offer_commissions for this manager).
            // SmartLink conversions (NULL offer_id) stay eligible.
            $eligibleClause = '';
            if (self::offerMode($managerId) === 'selected_only') {
                $eligibleClause = " AND (c.offer_id IS NULL OR EXISTS (
                    SELECT 1 FROM manager_offer_commissions moc2
                    WHERE moc2.manager_id = af.manager_id AND moc2.offer_id = c.offer_id
                ))";
            }

            // Count eligible conversions before insert
            // LEFT JOIN offers so SmartLink conversions (NULL offer_id) are included
            $dateWhere  = '';
            $cntParams  = [$managerId];
            if ($fromDate) { $dateWhere .= " AND DATE(c.converted_at) >= ?"; $cntParams[] = $fromDate; }
            if ($toDate)   { $dateWhere .= " AND DATE(c.converted_at) <= ?"; $cntParams[] = $toDate;   }

            $cntRow = Database::fetchOne(
                "SELECT COUNT(*) AS cnt
                 FROM conversions c
                 JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
                 LEFT JOIN offers o ON o.id = c.offer_id
                 WHERE c.status = 'approved' AND COALESCE(c.is_hidden,0)=0 AND c.payout > 0
                   {$dateWhere}{$eligibleClause}",
                $cntParams
            );
            $result['processed'] = (int)($cntRow['cnt'] ?? 0);

            if ($result['processed'] === 0) return $result;

            // Single INSERT IGNORE … SELECT — atomic, no PHP loop.
            // Uses actual conversion revenue (c.revenue) first, falls back to offer rate.
            $insParams = [$managerId];
            if ($fromDate) $insParams[] = $fromDate;
            if ($toDate)   $insParams[] = $toDate;

            // ── Attempt 1: single bulk INSERT IGNORE … SELECT ────────────────
            // Per-offer override (mo.commission_rate) wins; otherwise manager
            // rate; otherwise the admin-set global default. Eligibility filter
            // is appended via $eligibleClause.
            $bulkOk = false;
            // Prepend the global-default bind so it can be referenced in the
            // SELECT clause as a literal-via-?. Order matters: ? for default,
            // ? for manager_id, then optional date params.
            $bulkParams = array_merge([$globalDefault], $insParams);
            try {
                $stmt = Database::query(
                    "INSERT IGNORE INTO manager_commissions
                         (manager_id, affiliate_id, conversion_id, offer_id, is_smartlink,
                          advertiser_revenue, affiliate_payout, net_profit,
                          commission_rate, commission_amount,
                          status, calculated_at)
                     SELECT
                         af.manager_id,
                         c.affiliate_id,
                         c.id,
                         COALESCE(c.offer_id, 0),
                         IF(c.offer_id IS NULL, 1, 0),
                         COALESCE(NULLIF(c.revenue, 0), NULLIF(o.revenue_amount, 0), c.payout),
                         c.payout,
                         CASE
                             WHEN COALESCE(NULLIF(c.revenue,0), NULLIF(o.revenue_amount,0), 0) > c.payout
                             THEN ROUND(COALESCE(NULLIF(c.revenue,0), NULLIF(o.revenue_amount,0), 0) - c.payout, 4)
                             ELSE ROUND(c.payout, 4)
                         END,
                         /* Resolved rate: per-offer override → manager rate → global default. */
                         COALESCE(NULLIF(mo.commission_rate,0), NULLIF(am.commission_rate,0), ?) AS resolved_rate,
                         CASE
                             WHEN COALESCE(NULLIF(c.revenue,0), NULLIF(o.revenue_amount,0), 0) > c.payout
                             THEN ROUND((COALESCE(NULLIF(c.revenue,0), NULLIF(o.revenue_amount,0), 0) - c.payout)
                                        * COALESCE(NULLIF(mo.commission_rate,0), NULLIF(am.commission_rate,0), {$globalDefault}) / 100, 4)
                             ELSE ROUND(c.payout
                                        * COALESCE(NULLIF(mo.commission_rate,0), NULLIF(am.commission_rate,0), {$globalDefault}) / 100, 4)
                         END,
                         'pending',
                         COALESCE(c.converted_at, NOW())
                     FROM conversions c
                     JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
                     JOIN affiliate_managers am ON am.id = af.manager_id
                     LEFT JOIN offers o  ON o.id  = c.offer_id
                     LEFT JOIN manager_offer_commissions mo
                            ON mo.manager_id = af.manager_id AND mo.offer_id = c.offer_id
                     WHERE c.status = 'approved'
                       AND COALESCE(c.is_hidden,0) = 0
                       AND c.payout > 0
                       {$dateWhere}{$eligibleClause}",
                    $bulkParams
                );
                $result['credited'] = (int)$stmt->rowCount();
                $bulkOk = true;
            } catch (\Throwable $bulkErr) {
                error_log('[MCS] bulk INSERT failed, trying row-by-row fallback: ' . $bulkErr->getMessage());
            }

            // ── Attempt 2: row-by-row fallback if bulk failed ────────────────
            if (!$bulkOk) {
                $rows = Database::fetchAll(
                    "SELECT c.id, c.affiliate_id, c.offer_id, c.payout,
                            COALESCE(NULLIF(c.revenue,0), NULLIF(o.revenue_amount,0), c.payout) AS actual_revenue,
                            IF(c.offer_id IS NULL, 1, 0) AS is_smartlink,
                            COALESCE(c.converted_at, NOW()) AS calc_at,
                            am.id AS manager_id, am.commission_rate,
                            mo.commission_rate AS offer_override_rate
                     FROM conversions c
                     JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
                     JOIN affiliate_managers am ON am.id = af.manager_id
                     LEFT JOIN offers o ON o.id = c.offer_id
                     LEFT JOIN manager_offer_commissions mo
                            ON mo.manager_id = af.manager_id AND mo.offer_id = c.offer_id
                     WHERE c.status = 'approved' AND COALESCE(c.is_hidden,0)=0 AND c.payout > 0
                     {$dateWhere}{$eligibleClause}",
                    $insParams
                );
                foreach ($rows as $row) {
                    try {
                        $payout      = (float)$row['payout'];
                        $revenue     = (float)$row['actual_revenue'];
                        $isSmartlink = (int)$row['is_smartlink'];
                        $net         = $revenue > $payout ? round($revenue - $payout, 4) : round($payout, 4);
                        // Same priority chain as recordForConversion(): per-offer
                        // override → manager rate → admin global default.
                        $rowRate     = (float)($row['offer_override_rate'] ?? 0);
                        if ($rowRate <= 0) $rowRate = (float)($row['commission_rate'] ?? 0);
                        if ($rowRate <= 0) $rowRate = $globalDefault;
                        if ($rowRate <= 0) continue;   // nothing to credit
                        $comm        = round($net * $rowRate / 100, 4);
                        $ins         = Database::query(
                            "INSERT IGNORE INTO manager_commissions
                                 (manager_id, affiliate_id, conversion_id, offer_id, is_smartlink,
                                  advertiser_revenue, affiliate_payout, net_profit,
                                  commission_rate, commission_amount, status, calculated_at)
                             VALUES (?,?,?,?,?,?,?,?,?,?,'pending',?)",
                            [
                                (int)$row['manager_id'], (int)$row['affiliate_id'],
                                (int)$row['id'],         (int)($row['offer_id'] ?? 0), $isSmartlink,
                                $revenue, $payout, $net,
                                $rowRate, $comm,
                                $row['calc_at'],
                            ]
                        );
                        $result['credited'] += (int)$ins->rowCount();
                    } catch (\Throwable $rowErr) {
                        error_log('[MCS] row-by-row insert failed conv ' . $row['id'] . ': ' . $rowErr->getMessage());
                        $result['error'] = $rowErr->getMessage();
                    }
                }
            }

            $result['skipped'] = $result['processed'] - $result['credited'];

            // Always rebuild balance from actual DB data — never rely on incremental updates
            $result['total_added'] = self::rebuildBalance($managerId);

        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            error_log('[MCS] backfillForManager(' . $managerId . '): ' . $e->getMessage());
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reverse commission when conversion is rejected / chargebacked.
    // ─────────────────────────────────────────────────────────────────────────
    public static function reverseForConversion(int $conversionDbId): void
    {
        if ($conversionDbId <= 0) return;
        try {
            $existing = Database::fetchOne(
                "SELECT id, manager_id, commission_amount FROM manager_commissions
                 WHERE conversion_id = ? AND status IN ('pending','approved') LIMIT 1",
                [$conversionDbId]
            );
            Database::query(
                "UPDATE manager_commissions SET status='reversed'
                 WHERE conversion_id = ? AND status IN ('pending','approved')",
                [$conversionDbId]
            );
            if ($existing) {
                Database::query(
                    "UPDATE affiliate_managers SET balance = GREATEST(0, balance - ?) WHERE id = ?",
                    [(float)$existing['commission_amount'], (int)$existing['manager_id']]
                );
            }
        } catch (\Throwable $e) {
            error_log('[MCS] reverseForConversion: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Approve all pending commissions for a manager.
    // ─────────────────────────────────────────────────────────────────────────
    public static function approveAllPending(int $managerId): int
    {
        if ($managerId <= 0) return 0;
        try {
            $stmt = Database::query(
                "UPDATE manager_commissions SET status='approved' WHERE manager_id=? AND status='pending'",
                [$managerId]
            );
            return (int)$stmt->rowCount();
        } catch (\Throwable $e) { return 0; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mark commissions as paid within a period.
    // ─────────────────────────────────────────────────────────────────────────
    public static function markPaidForPeriod(int $managerId, string $from, string $to): int
    {
        if ($managerId <= 0) return 0;
        try {
            $stmt = Database::query(
                "UPDATE manager_commissions SET status='paid'
                 WHERE manager_id=? AND status IN ('pending','approved')
                   AND DATE(calculated_at) BETWEEN ? AND ?",
                [$managerId, $from, $to]
            );
            return (int)$stmt->rowCount();
        } catch (\Throwable $e) { return 0; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Balance breakdown — used by admin view and topbar.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getManagerBalance(int $managerId): array
    {
        try {
            $bal = Database::fetchOne("SELECT balance FROM affiliate_managers WHERE id=?", [$managerId]);
            $agg = Database::fetchOne(
                "SELECT
                     COALESCE(SUM(CASE WHEN status='pending'   THEN commission_amount ELSE 0 END),0) AS pending,
                     COALESCE(SUM(CASE WHEN status='approved'  THEN commission_amount ELSE 0 END),0) AS approved,
                     COALESCE(SUM(CASE WHEN status='paid'      THEN commission_amount ELSE 0 END),0) AS paid,
                     COALESCE(SUM(CASE WHEN status!='reversed' THEN commission_amount ELSE 0 END),0) AS total_earned
                 FROM manager_commissions WHERE manager_id=?",
                [$managerId]
            );
            return [
                'balance'      => (float)($bal['balance']      ?? 0),
                'pending'      => (float)($agg['pending']      ?? 0),
                'approved'     => (float)($agg['approved']     ?? 0),
                'paid'         => (float)($agg['paid']         ?? 0),
                'total_earned' => (float)($agg['total_earned'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['balance'=>0.0,'pending'=>0.0,'approved'=>0.0,'paid'=>0.0,'total_earned'=>0.0];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Commission activity log for admin view.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getCommissionLogs(int $managerId, int $limit = 50): array
    {
        try {
            // Return commission records with join — commission_logs may be empty
            return Database::fetchAll(
                "SELECT mc.id, mc.calculated_at AS logged_at,
                        mc.manager_id, mc.affiliate_id, mc.conversion_id, mc.offer_id,
                        mc.advertiser_revenue, mc.affiliate_payout, mc.net_profit,
                        mc.commission_rate, mc.commission_amount,
                        COALESCE(mc.calc_mode,'payout_based') AS calc_mode,
                        mc.status AS result,
                        NULL AS skip_reason,
                        o.name AS offer_name,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        af.affiliate_code
                 FROM manager_commissions mc
                 LEFT JOIN offers o ON o.id = mc.offer_id
                 LEFT JOIN affiliates af ON af.id = mc.affiliate_id
                 LEFT JOIN users u ON u.id = af.user_id
                 WHERE mc.manager_id = ?
                 ORDER BY mc.calculated_at DESC
                 LIMIT ?",
                [$managerId, $limit]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Earnings history for manager-facing view.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getEarningsHistory(int $managerId, string $from='', string $to='', int $limit=500): array
    {
        $params = [$managerId];
        $where  = "mc.manager_id=? AND mc.status!='reversed'";
        if ($from) { $where .= ' AND DATE(mc.calculated_at)>=?'; $params[] = $from; }
        if ($to)   { $where .= ' AND DATE(mc.calculated_at)<=?'; $params[] = $to;   }
        try {
            return Database::fetchAll(
                "SELECT mc.id, mc.status, mc.calculated_at, mc.commission_amount,
                        COALESCE(mc.calc_mode,'payout_based') AS calc_mode,
                        COALESCE(mc.is_smartlink,0) AS is_smartlink,
                        COALESCE(o.name,'— SmartLink —') AS offer_name,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        af.affiliate_code, cv.converted_at
                 FROM manager_commissions mc
                 LEFT JOIN offers o ON o.id=mc.offer_id AND mc.offer_id > 0
                 JOIN affiliates af ON af.id=mc.affiliate_id
                 JOIN users u ON u.id=af.user_id
                 JOIN conversions cv ON cv.id=mc.conversion_id
                 WHERE {$where}
                 ORDER BY mc.calculated_at DESC
                 LIMIT " . max(1, min(1000, $limit)),
                $params
            );
        } catch (\Throwable $e) { return []; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Summary aggregate for admin view.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getSummaryForManager(int $managerId): array
    {
        try {
            $row = Database::fetchOne(
                "SELECT
                     COALESCE(SUM(advertiser_revenue),0) AS total_revenue,
                     COALESCE(SUM(affiliate_payout),0)   AS total_payout,
                     COALESCE(SUM(net_profit),0)          AS total_profit,
                     COALESCE(SUM(CASE WHEN status!='reversed' THEN commission_amount ELSE 0 END),0) AS total_commission,
                     COALESCE(SUM(CASE WHEN status='paid'      THEN commission_amount ELSE 0 END),0) AS commission_paid,
                     COALESCE(SUM(CASE WHEN status='pending'   THEN commission_amount ELSE 0 END),0) AS commission_pending,
                     COALESCE(SUM(CASE WHEN status='approved'  THEN commission_amount ELSE 0 END),0) AS commission_approved
                 FROM manager_commissions WHERE manager_id=?",
                [$managerId]
            );
            return $row ?: ['total_revenue'=>0,'total_payout'=>0,'total_profit'=>0,'total_commission'=>0,'commission_paid'=>0,'commission_pending'=>0,'commission_approved'=>0];
        } catch (\Throwable $e) { return ['total_revenue'=>0,'total_payout'=>0,'total_profit'=>0,'total_commission'=>0,'commission_paid'=>0,'commission_pending'=>0,'commission_approved'=>0]; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Per-conversion records for admin commission report.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getRecordsForManager(int $managerId, string $from='', string $to='', int $limit=500): array
    {
        $params = [$managerId];
        $where  = 'mc.manager_id=?';
        if ($from) { $where .= ' AND DATE(mc.calculated_at)>=?'; $params[] = $from; }
        if ($to)   { $where .= ' AND DATE(mc.calculated_at)<=?'; $params[] = $to;   }
        try {
            return Database::fetchAll(
                "SELECT mc.*, COALESCE(mc.calc_mode,'payout_based') AS calc_mode,
                        COALESCE(mc.is_smartlink,0) AS is_smartlink,
                        COALESCE(o.name,'— SmartLink —') AS offer_name,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        af.affiliate_code, cv.conversion_id AS conversion_uuid, cv.converted_at
                 FROM manager_commissions mc
                 LEFT JOIN offers o ON o.id=mc.offer_id AND mc.offer_id > 0
                 JOIN affiliates af ON af.id=mc.affiliate_id
                 JOIN users u ON u.id=af.user_id
                 JOIN conversions cv ON cv.id=mc.conversion_id
                 WHERE {$where}
                 ORDER BY mc.calculated_at DESC
                 LIMIT " . max(1, min(5000, $limit)),
                $params
            );
        } catch (\Throwable $e) { return []; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // All-managers summary for admin overview.
    // ─────────────────────────────────────────────────────────────────────────
    public static function getAllManagersSummary(): array
    {
        try {
            return Database::fetchAll(
                "SELECT am.id AS manager_id,
                        CONCAT(u.first_name,' ',u.last_name) AS manager_name,
                        u.email AS manager_email,
                        am.commission_rate, am.balance,
                        COALESCE(SUM(mc.advertiser_revenue),0) AS total_revenue,
                        COALESCE(SUM(mc.affiliate_payout),0)   AS total_payout,
                        COALESCE(SUM(mc.net_profit),0)          AS total_profit,
                        COALESCE(SUM(CASE WHEN mc.status!='reversed' THEN mc.commission_amount ELSE 0 END),0) AS total_commission,
                        COALESCE(SUM(CASE WHEN mc.status='paid'      THEN mc.commission_amount ELSE 0 END),0) AS commission_paid,
                        COALESCE(SUM(CASE WHEN mc.status='pending'   THEN mc.commission_amount ELSE 0 END),0) AS commission_pending,
                        COALESCE(SUM(CASE WHEN mc.status='approved'  THEN mc.commission_amount ELSE 0 END),0) AS commission_approved,
                        COUNT(CASE WHEN mc.status!='reversed' THEN 1 END) AS conversion_count
                 FROM affiliate_managers am
                 JOIN users u ON u.id=am.user_id
                 LEFT JOIN manager_commissions mc ON mc.manager_id=am.id
                 WHERE u.status!='deleted'
                 GROUP BY am.id, u.first_name, u.last_name, u.email, am.commission_rate, am.balance
                 ORDER BY total_commission DESC"
            );
        } catch (\Throwable $e) { return []; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal: write to commission_logs (best-effort, never throws).
    // ─────────────────────────────────────────────────────────────────────────
    private static function writeLog(
        int $managerId, int $affiliateId, int $conversionId, int $offerId,
        float $revenue, float $payout, float $netProfit,
        float $rate, float $amount, string $calcMode,
        string $result, string $skipReason
    ): void {
        try {
            Database::query(
                "INSERT INTO commission_logs
                     (manager_id, affiliate_id, conversion_id, offer_id,
                      advertiser_revenue, affiliate_payout, net_profit,
                      commission_rate, commission_amount, calc_mode, result, skip_reason)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$managerId, $affiliateId, $conversionId, $offerId,
                 $revenue, $payout, $netProfit, $rate, $amount, $calcMode,
                 $result, $skipReason ?: null]
            );
        } catch (\Throwable $e) {}
    }
}
