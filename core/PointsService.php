<?php
/**
 * PointsService — Points-as-a-layer over existing affiliate earnings.
 *
 * Design promises (per spec):
 *   ✔ Does NOT modify any existing earnings table or row.
 *   ✔ READS conversion / earnings data; WRITES only to its own tables.
 *   ✔ Atomic credit/debit (single UPDATE … balance = balance + ? guards
 *     against race conditions; debit is gated by a WHERE balance >= ?
 *     so negative balances are impossible).
 *   ✔ Full audit trail in points_transactions.
 *
 * Tables created idempotently:
 *   - points_config           — single-row config (usd_per_point, enabled)
 *   - affiliate_points        — one row per affiliate (balance + totals)
 *   - points_transactions     — ledger of every credit/debit with reason
 */
class PointsService
{
    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `points_config` (
                `id`            INT UNSIGNED NOT NULL DEFAULT 1 PRIMARY KEY,
                `enabled`       TINYINT(1)   NOT NULL DEFAULT 1,
                `usd_per_point` DECIMAL(10,4) NOT NULL DEFAULT 20.0000,
                `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // Seed default row.
            Database::query("INSERT IGNORE INTO points_config (id, enabled, usd_per_point) VALUES (1, 1, 20.0000)");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `affiliate_points` (
                `affiliate_id`  INT UNSIGNED NOT NULL PRIMARY KEY,
                `balance`       INT NOT NULL DEFAULT 0,
                `lifetime_earned` INT NOT NULL DEFAULT 0,
                `lifetime_spent`  INT NOT NULL DEFAULT 0,
                `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `points_transactions` (
                `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id` INT UNSIGNED NOT NULL,
                `delta`        INT NOT NULL,
                `kind`         ENUM('earn','spend','adjust','refund') NOT NULL DEFAULT 'earn',
                `reason`       VARCHAR(255) DEFAULT NULL,
                `ref_type`     VARCHAR(40)  DEFAULT NULL,
                `ref_id`       VARCHAR(64)  DEFAULT NULL,
                `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_aff`     (`affiliate_id`),
                KEY `idx_ref`     (`ref_type`,`ref_id`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}
    }

    // ── Config ──────────────────────────────────────────────────────────────

    public static function config(): array
    {
        self::ensureSchema();
        $row = Database::fetchOne("SELECT enabled, usd_per_point FROM points_config WHERE id=1");
        if (!$row) return ['enabled' => true, 'usd_per_point' => 20.0];
        return [
            'enabled'       => (int)$row['enabled'] === 1,
            'usd_per_point' => max(0.01, (float)$row['usd_per_point']),
        ];
    }

    public static function setConfig(bool $enabled, float $usdPerPoint): void
    {
        self::ensureSchema();
        if ($usdPerPoint < 0.01) $usdPerPoint = 0.01;
        Database::query(
            "INSERT INTO points_config (id, enabled, usd_per_point) VALUES (1, ?, ?)
             ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), usd_per_point=VALUES(usd_per_point)",
            [$enabled ? 1 : 0, $usdPerPoint]
        );
    }

    // ── Balance helpers ─────────────────────────────────────────────────────

    public static function balance(int $affiliateId): array
    {
        self::ensureSchema();
        if ($affiliateId <= 0) return ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0];
        $row = Database::fetchOne(
            "SELECT balance, lifetime_earned, lifetime_spent FROM affiliate_points WHERE affiliate_id=?",
            [$affiliateId]
        );
        return $row ? [
            'balance'         => (int)$row['balance'],
            'lifetime_earned' => (int)$row['lifetime_earned'],
            'lifetime_spent'  => (int)$row['lifetime_spent'],
        ] : ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0];
    }

    /**
     * Atomic credit. Always returns the new balance.
     * Negative or zero deltas are clamped to no-op.
     */
    public static function credit(int $affiliateId, int $points, string $reason = '', ?string $refType = null, ?string $refId = null): int
    {
        self::ensureSchema();
        if ($affiliateId <= 0 || $points <= 0) return self::balance($affiliateId)['balance'];

        // Idempotency: if ref_type+ref_id already credited, skip.
        if ($refType !== null && $refId !== null) {
            $dup = Database::fetchOne(
                "SELECT id FROM points_transactions WHERE affiliate_id=? AND ref_type=? AND ref_id=? AND kind='earn' LIMIT 1",
                [$affiliateId, $refType, $refId]
            );
            if ($dup) return self::balance($affiliateId)['balance'];
        }

        // Atomic upsert via two-statement transaction.
        try {
            Database::query(
                "INSERT INTO affiliate_points (affiliate_id, balance, lifetime_earned)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance),
                                         lifetime_earned = lifetime_earned + VALUES(lifetime_earned)",
                [$affiliateId, $points, $points]
            );
            Database::insert('points_transactions', [
                'affiliate_id' => $affiliateId,
                'delta'        => $points,
                'kind'         => 'earn',
                'reason'       => $reason ?: 'Conversion earnings',
                'ref_type'     => $refType,
                'ref_id'       => $refId,
            ]);
        } catch (\Throwable $e) {
            error_log('[PointsService::credit] ' . $e->getMessage());
        }
        return self::balance($affiliateId)['balance'];
    }

    /**
     * Atomic debit guarded against negative balance. Returns the new balance,
     * or -1 if the debit was rejected (insufficient funds).
     */
    public static function debit(int $affiliateId, int $points, string $reason = '', ?string $refType = null, ?string $refId = null): int
    {
        self::ensureSchema();
        if ($affiliateId <= 0 || $points <= 0) return self::balance($affiliateId)['balance'];

        // Conditional UPDATE — rowcount tells us whether the spend was approved.
        try {
            $stmt = Database::query(
                "UPDATE affiliate_points
                 SET balance = balance - ?, lifetime_spent = lifetime_spent + ?
                 WHERE affiliate_id = ? AND balance >= ?",
                [$points, $points, $affiliateId, $points]
            );
            if ($stmt->rowCount() === 0) {
                return -1;  // insufficient points
            }
            Database::insert('points_transactions', [
                'affiliate_id' => $affiliateId,
                'delta'        => -$points,
                'kind'         => 'spend',
                'reason'       => $reason ?: 'Shop purchase',
                'ref_type'     => $refType,
                'ref_id'       => $refId,
            ]);
        } catch (\Throwable $e) {
            error_log('[PointsService::debit] ' . $e->getMessage());
            return -1;
        }
        return self::balance($affiliateId)['balance'];
    }

    /**
     * Reverse a debit (e.g. canceled order). Credits the points back with
     * kind='refund' so audit reports can distinguish from regular earnings.
     */
    public static function refund(int $affiliateId, int $points, string $reason, ?string $refType = null, ?string $refId = null): int
    {
        if ($affiliateId <= 0 || $points <= 0) return self::balance($affiliateId)['balance'];
        self::ensureSchema();
        try {
            Database::query(
                "INSERT INTO affiliate_points (affiliate_id, balance)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)",
                [$affiliateId, $points]
            );
            Database::insert('points_transactions', [
                'affiliate_id' => $affiliateId,
                'delta'        => $points,
                'kind'         => 'refund',
                'reason'       => $reason,
                'ref_type'     => $refType,
                'ref_id'       => $refId,
            ]);
        } catch (\Throwable $e) { error_log('[PointsService::refund] ' . $e->getMessage()); }
        return self::balance($affiliateId)['balance'];
    }

    /**
     * Hook called from the existing conversion-approval call sites
     * (postback.php + ConversionController approve). Reads the conversion
     * row (does NOT mutate it) and credits points based on the configured
     * rule. Idempotent via the conversion DB id as ref.
     */
    public static function onConversionApproved(int $conversionDbId): void
    {
        if ($conversionDbId <= 0) return;
        $cfg = self::config();
        if (!$cfg['enabled']) return;
        $usdPerPt = max(0.01, (float)$cfg['usd_per_point']);

        try {
            $row = Database::fetchOne(
                "SELECT id, affiliate_id, payout, status, COALESCE(is_hidden,0) AS is_hidden
                 FROM conversions WHERE id = ? LIMIT 1",
                [$conversionDbId]
            );
        } catch (\Throwable $_) { return; }
        if (!$row || $row['status'] !== 'approved' || (int)$row['is_hidden'] === 1) return;

        $payout = (float)$row['payout'];
        if ($payout <= 0) return;
        $points = (int)floor($payout / $usdPerPt);
        if ($points <= 0) return;

        self::credit(
            (int)$row['affiliate_id'],
            $points,
            sprintf('Earnings $%.2f × rule', $payout),
            'conversion',
            (string)$row['id']
        );
    }

    /** Manual admin adjustment — positive or negative. */
    public static function adjust(int $affiliateId, int $delta, string $reason): int
    {
        if ($affiliateId <= 0 || $delta === 0) return self::balance($affiliateId)['balance'];
        self::ensureSchema();
        // Negative adjust is gated by balance.
        if ($delta < 0) {
            $abs = abs($delta);
            $stmt = Database::query(
                "UPDATE affiliate_points
                 SET balance = balance - ?
                 WHERE affiliate_id = ? AND balance >= ?",
                [$abs, $affiliateId, $abs]
            );
            if ($stmt->rowCount() === 0) return -1;
        } else {
            Database::query(
                "INSERT INTO affiliate_points (affiliate_id, balance)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)",
                [$affiliateId, $delta]
            );
        }
        Database::insert('points_transactions', [
            'affiliate_id' => $affiliateId,
            'delta'        => $delta,
            'kind'         => 'adjust',
            'reason'       => $reason ?: 'Admin adjustment',
        ]);
        return self::balance($affiliateId)['balance'];
    }

    public static function transactions(int $affiliateId, int $limit = 50): array
    {
        self::ensureSchema();
        return Database::fetchAll(
            "SELECT * FROM points_transactions WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT $limit",
            [$affiliateId]
        ) ?: [];
    }

    // ── Auto-Sync: backfill all approved conversions missing points ──────────

    /**
     * Scans all approved, non-hidden conversions with payout > 0 and credits
     * points for any that have not yet been credited.
     *
     * Idempotent — safe to call repeatedly (duplicates are skipped via the
     * ref_type='conversion' + ref_id guard in credit()).
     *
     * @param  bool        $dryRun  If true, returns a preview without writing.
     * @param  string|null $since   Optional date string: only process conversions
     *                              approved/updated on or after this date.
     *                              Example: '2024-01-01'
     * @return array{processed:int, credited:int, skipped:int, errors:int, log:list<string>}
     */
    public static function syncAllApproved(bool $dryRun = false, ?string $since = null): array
    {
        self::ensureSchema();

        $cfg      = self::config();
        $usdPerPt = max(0.01, (float)$cfg['usd_per_point']);
        $log      = [];

        if (!$cfg['enabled'] && !$dryRun) {
            $log[] = 'Points module disabled — sync aborted.';
            return ['processed' => 0, 'credited' => 0, 'skipped' => 0, 'errors' => 0, 'log' => $log];
        }

        $processed = 0;
        $credited  = 0;
        $skipped   = 0;
        $errors    = 0;
        $batchSz   = 200;
        $offset    = 0;

        $sinceClause = '';
        $sinceParams = [];
        if ($since) {
            $sinceClause = 'AND c.updated_at >= ?';
            $sinceParams = [$since];
        }

        while (true) {
            try {
                $rows = Database::fetchAll(
                    "SELECT
                        c.id            AS conv_id,
                        c.affiliate_id,
                        c.payout,
                        COALESCE(c.is_hidden, 0) AS is_hidden,
                        pt.id           AS already_credited
                     FROM conversions c
                     LEFT JOIN points_transactions pt
                            ON pt.affiliate_id = c.affiliate_id
                           AND pt.ref_type    = 'conversion'
                           AND pt.ref_id      = CAST(c.id AS CHAR)
                           AND pt.kind        = 'earn'
                     WHERE c.status = 'approved'
                       AND c.payout > 0
                       $sinceClause
                     ORDER BY c.id ASC
                     LIMIT $batchSz OFFSET $offset",
                    $sinceParams
                ) ?: [];
            } catch (\Throwable $e) {
                $log[] = 'DB error: ' . $e->getMessage();
                $errors++;
                break;
            }

            if (empty($rows)) break;

            foreach ($rows as $row) {
                $processed++;
                $convId = (int)$row['conv_id'];
                $affId  = (int)$row['affiliate_id'];
                $payout = (float)$row['payout'];

                if ((int)$row['is_hidden'] === 1) {
                    $skipped++;
                    continue;
                }
                if ($row['already_credited'] !== null) {
                    $skipped++;
                    continue;
                }

                $points = (int)floor($payout / $usdPerPt);
                if ($points <= 0) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $log[] = sprintf('DRY conv#%d aff#%d $%.2f → %d pts', $convId, $affId, $payout, $points);
                    $credited++;
                    continue;
                }

                try {
                    $newBal = self::credit(
                        $affId,
                        $points,
                        sprintf('Auto-sync $%.2f ÷ $%.2f = %d pts', $payout, $usdPerPt, $points),
                        'conversion',
                        (string)$convId
                    );
                    $log[] = sprintf('CREDIT conv#%d aff#%d +%d pts (bal:%d)', $convId, $affId, $points, $newBal);
                    $credited++;
                } catch (\Throwable $e) {
                    $log[] = sprintf('ERROR conv#%d: %s', $convId, $e->getMessage());
                    $errors++;
                }
            }

            $offset += $batchSz;
            if (count($rows) < $batchSz) break;
        }

        return compact('processed', 'credited', 'skipped', 'errors', 'log');
    }
}
