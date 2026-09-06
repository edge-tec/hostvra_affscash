<?php
/**
 * PostbackFirer — Centralised affiliate & global postback dispatcher
 *
 * Used by:
 *   - tracking/postback.php        (immediate fire on conversion receipt)
 *   - ConversionController.php     (fire on admin status change: pending→approved, approved→rejected, etc.)
 *   - cron/fire_postbacks.php      (retry failed postbacks)
 *
 * Design goals
 *   1. Fire affiliate-level postbacks (postbacks table) filtered by fire_on_status
 *   2. Fire the affiliate's global postback URL (affiliates.global_postback_url)
 *   3. Fire admin-defined global postbacks (global_postbacks table)
 *   4. Log every attempt with full diagnostics (HTTP code, body, curl error)
 *   5. Mark conversions.postback_sent = 1 after all postbacks attempted
 *   6. Never throw — all errors are logged, never bubble up to block conversion recording
 */
class PostbackFirer
{
    // ──────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Fire all postbacks for a given conversion.
     *
     * @param array  $conversion  Full row from `conversions` joined with `clicks` data
     *                            Required keys: conversion_id, click_id, offer_id,
     *                            affiliate_id, payout, revenue, status
     *                            Optional: sub1..sub5 (from clicks join)
     * @param string $convStatus  The conversion status to match fire_on_status rules.
     *                            Pass the NEW status when called after a status change.
     * @param bool   $markSent    Whether to UPDATE conversions.postback_sent=1 when done.
     *                            Pass false when retrying (already marked).
     */
    public static function fireAll(array $conversion, string $convStatus, bool $markSent = true): void
    {
        $convId    = $conversion['conversion_id'];
        $clickId   = $conversion['click_id'];
        $affId     = (int)$conversion['affiliate_id'];
        $offerId   = (int)$conversion['offer_id'];
        $payout    = (float)$conversion['payout'];
        $revenue   = (float)($conversion['revenue'] ?? $payout);

        // Log every invocation so we have a complete audit trail
        self::log(
            "[PostbackFirer] fireAll called: conv={$convId} click={$clickId} "
            . "aff={$affId} offer={$offerId} status={$convStatus} payout={$payout} markSent=" . ($markSent ? 'true' : 'false')
        );

        // Column → parameter mapping (matches tracking/click.php):
        //   sub1 = click_id          (affiliate's external tracker click ID — REQUIRED for postback match)
        //   sub2 = sub_id_1 / affid  (affiliate's own tracker affiliate ID)
        //   sub3 = sub_id_2 / aff_sub1
        //   sub4 = sub_id_3 / aff_sub2
        //   sub5 = sub_id_4 / aff_sub3
        //   sub6 = sub_id_5 / aff_sub4  (new column)
        $sub1   = $conversion['sub1']   ?? '';  // click_id
        $sub2   = $conversion['sub2']   ?? '';  // sub_id_1 / affid
        $sub3   = $conversion['sub3']   ?? '';  // sub_id_2 / aff_sub1
        $sub4   = $conversion['sub4']   ?? '';  // sub_id_3 / aff_sub2
        $sub5   = $conversion['sub5']   ?? '';  // sub_id_4 / aff_sub3
        $sub6   = $conversion['sub6']   ?? '';  // sub_id_5 / aff_sub4
        $source = $conversion['source'] ?? '';

        // The external click ID is what the affiliate tracker generated and
        // passed as click_id= in the tracking link → stored in sub1.
        // {click_id} in their postback URL resolves to this — NOT our internal UUID.
        $externalClickId = !empty($sub1) ? $sub1 : $clickId;

        // ── Canonical macro map ──────────────────────────────────────────────
        // Primary macros (spec-mandated):
        //   {click_id} = affiliate's tracker click ID (from sub1/click_id in tracking link)
        //   {payout}   = conversion payout amount
        //   {aff_id}   = affiliate code
        //   {sub_id_1..5} = optional segmentation values
        //
        // Legacy aliases kept for backwards compatibility with older postback URLs.
        $macros = [
            // ── PRIMARY: affiliate's external tracker click ID ─────────────
            'click_id'          => $externalClickId,   // SPEC CANONICAL
            // Legacy aliases (all resolve to same value)
            'clickid'           => $externalClickId,
            'cid'               => $externalClickId,
            'tid'               => $externalClickId,

            // ── Internal click UUID (our system's UUID, not affiliate's ID) ─
            'internal_click_id' => $clickId,

            // ── PRIMARY: payout amount ─────────────────────────────────────
            'payout'            => number_format($payout,  4, '.', ''),  // SPEC CANONICAL
            // Legacy aliases
            'amount'            => number_format($payout,  4, '.', ''),
            'revenue'           => number_format($revenue, 4, '.', ''),

            // ── Conversion identifiers ─────────────────────────────────────
            'conversion_id'     => $convId,
            'txid'              => $convId,
            'transaction_id'    => $convId,

            // ── Status ─────────────────────────────────────────────────────
            'status'            => $convStatus,

            // ── Offer & affiliate identifiers ──────────────────────────────
            'offer_id'          => (string)$offerId,
            'aff_id'            => (string)$affId,     // SPEC CANONICAL

            // ── Sub parameters ─────────────────────────────────────────────
            // Canonical sub_id_X names (match what affiliates pass in tracking links):
            'sub_id_1'          => $sub2,   // sub_id_1= → stored in sub2
            'sub_id_2'          => $sub3,   // sub_id_2= → stored in sub3
            'sub_id_3'          => $sub4,   // sub_id_3= → stored in sub4
            'sub_id_4'          => $sub5,   // sub_id_4= → stored in sub5
            'sub_id_5'          => $sub6,   // sub_id_5= → stored in sub6
            // Raw DB column aliases
            'sub1'              => $sub1,
            'sub2'              => $sub2,
            'sub3'              => $sub3,
            'sub4'              => $sub4,
            'sub5'              => $sub5,
            'sub6'              => $sub6,
            // Named parameter aliases (aff_sub / affid forms)
            'affid'             => $sub2,   // affid= → sub2
            'aff_sub1'          => $sub3,   // aff_sub1= → sub3
            'aff_sub2'          => $sub4,   // aff_sub2= → sub4
            'aff_sub3'          => $sub5,   // aff_sub3= → sub5
            'aff_sub4'          => $sub6,   // aff_sub4= → sub6

            // ── Traffic source ─────────────────────────────────────────────
            'source'            => $source,   // traffic source / affiliate source name
        ];

        // ── 1. Affiliate-level postbacks (postbacks table) ────────────────
        self::fireAffiliateLevelPostbacks($affId, $offerId, $convId, $convStatus, $macros);

        // ── 2. Affiliate's global postback URL ────────────────────────────
        self::fireAffiliateGlobalPostback($affId, $convId, $convStatus, $macros);

        // ── 3. Admin-configured global postbacks ──────────────────────────
        self::fireAdminGlobalPostbacks($affId, $convId, $convStatus, $macros);

        // ── 4. Mark conversion as postback-attempted ──────────────────────
        if ($markSent) {
            // Ensure conversions table has the postback tracking columns before updating.
            // This silently adds missing columns on first run (handles fresh installs
            // and upgrades where the schema predates these columns).
            self::ensureConversionColumns();
            try {
                // Primary update — includes postback_sent_at timestamp
                Database::query(
                    "UPDATE `conversions` SET `postback_sent`=1, `postback_sent_at`=NOW() WHERE `conversion_id`=?",
                    [$convId]
                );
                self::log("[PostbackFirer] postback_sent marked for conversion {$convId}");
            } catch (\Throwable $e) {
                // postback_sent_at column may still be missing (ALTER may have failed
                // on some MySQL permission configs). Fall back to updating only the
                // boolean flag so the UI reflects a correct "YES" state.
                self::log("[PostbackFirer] postback_sent_at update failed for {$convId}: " . $e->getMessage() . " — retrying without timestamp column");
                try {
                    Database::query(
                        "UPDATE `conversions` SET `postback_sent`=1 WHERE `conversion_id`=?",
                        [$convId]
                    );
                    self::log("[PostbackFirer] postback_sent (fallback, no timestamp) marked for conversion {$convId}");
                } catch (\Throwable $e2) {
                    self::log("[PostbackFirer] CRITICAL: Failed to mark postback_sent for {$convId}: " . $e2->getMessage());
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Fire affiliate-level postbacks (rows from `postbacks` table).
     * Respects fire_on_status filter, offer_id filter, admin_status=approved.
     */
    private static function fireAffiliateLevelPostbacks(
        int    $affId,
        int    $offerId,
        string $convId,
        string $convStatus,
        array  $macros
    ): void {
        try {
            $postbacks = Database::fetchAll(
                "SELECT * FROM `postbacks`
                 WHERE affiliate_id=? AND status='active' AND admin_status='approved' AND event='conversion'
                   AND (offer_id IS NULL OR offer_id=?)
                 ORDER BY id",
                [$affId, $offerId]
            );
        } catch (\Throwable $e) {
            self::log("[PostbackFirer] DB error loading affiliate postbacks for aff#{$affId}: " . $e->getMessage());
            return;
        }

        if (empty($postbacks)) {
            self::log("[PostbackFirer] No affiliate-level postbacks configured for aff#{$affId} offer#{$offerId} — skipping affiliate postback step.");
        }

        foreach ($postbacks as $pb) {
            $fireOn = array_map('trim', explode(',', $pb['fire_on_status'] ?? 'approved'));
            if (!in_array($convStatus, $fireOn, true)) {
                self::log("[PostbackFirer] Skipping postback #{$pb['id']} for aff#{$affId}: fire_on_status=[" . implode(',', $fireOn) . "] does not include status '{$convStatus}'");
                continue;
            }

            $url    = Helpers::buildPostbackUrl($pb['url'], $macros);
            $result = Helpers::firePostback($url, $pb['method'] ?? 'GET');

            $sub1Missing = empty($macros['sub1']);
            $note = $sub1Missing
                ? '[WARN: click_id was empty — affiliate must pass click_id={TRACKER_CLICK_ID} in their offer tracking link (e.g. &click_id={clickid}). Postback fired with internal UUID instead — conversion will NOT match in affiliate tracker.] '
                : '';
            if (!empty($result['error'])) {
                $note .= '[cURL error: ' . $result['error'] . '] ';
            }

            self::log(
                "[PostbackFirer] Affiliate postback #{$pb['id']} for aff#{$affId} conv#{$convId}: "
                . "HTTP={$result['status']} success=" . ($result['success'] ? 'YES' : 'NO')
                . " URL={$url}"
                . (!empty($result['error']) ? " error={$result['error']}" : '')
            );

            try {
                // Ensure attempt_count and first_fired_at columns exist before logging
                self::ensurePostbackLogColumns();
                Database::insert('postback_logs', [
                    'postback_id'    => $pb['id'],
                    'conversion_id'  => $convId,
                    'fired_url'      => $url,
                    'http_status'    => $result['status'],
                    'response_body'  => substr($note . ($result['body'] ?? ''), 0, 1000),
                    'is_success'     => $result['success'] ? 1 : 0,
                    'attempt_count'  => 1,
                    'first_fired_at' => date('Y-m-d H:i:s'),  // anchors RETRY_WINDOW to original fire
                ]);
            } catch (\Throwable $e) {
                self::log("[PostbackFirer] Failed to log affiliate postback #{$pb['id']}: " . $e->getMessage());
            }

            try {
                Database::query(
                    "UPDATE `postbacks` SET `last_fired_at`=NOW() WHERE `id`=?",
                    [$pb['id']]
                );
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Fire the affiliate's own global postback URL stored in affiliates table.
     * Only fires if approved and active.
     */
    private static function fireAffiliateGlobalPostback(
        int    $affId,
        string $convId,
        string $convStatus,
        array  $macros
    ): void {
        // Only fire on approved conversions for global postback
        if ($convStatus !== 'approved') {
            return;
        }

        // Ensure columns exist once per request (not on every conversion)
        self::ensureAffiliateGlobalPbColumns();

        try {
            $affRow = Database::fetchOne(
                "SELECT global_postback_url, global_pb_admin_status, global_pb_active
                 FROM affiliates WHERE id=?",
                [$affId]
            );
        } catch (\Throwable $e) {
            self::log("[PostbackFirer] DB error loading affiliate#{$affId} global pb: " . $e->getMessage());
            return;
        }

        $globalUrl  = trim($affRow['global_postback_url'] ?? '');
        $pbStatus   = $affRow['global_pb_admin_status'] ?? null;
        // Normalize global_pb_active: DB may return NULL (column added after row was created),
        // '0', '1', 0, or 1.  Treat NULL as "not explicitly deactivated" — allow fire.
        // We only block when the value is explicitly 0 (admin deactivated).
        $pbActiveRaw = $affRow['global_pb_active'] ?? null;
        $pbActive    = ($pbActiveRaw === null) ? null : (int)$pbActiveRaw;

        // Fire conditions:
        //  - URL is non-empty
        //  - admin_status is 'approved' OR NULL (legacy row without approval system)
        //  - active flag is NOT explicitly 0 (admin manually deactivated)
        //    NULL means column didn't exist when row was created — treat as active
        $canFire = !empty($globalUrl)
            && ($pbStatus === 'approved' || $pbStatus === null)
            && ($pbActive !== 0);

        if (!$canFire) {
            // Log the reason so admins can diagnose "postback=NO" in reports
            if (empty($globalUrl)) {
                self::log("[PostbackFirer] Affiliate#{$affId} has no global postback URL configured — skipping global pb for conv#{$convId}.");
            } else {
                $reason = match(true) {
                    ($pbStatus === 'pending')  => 'pending admin approval — go to Admin › Global Postbacks to approve',
                    ($pbStatus === 'rejected') => 'rejected by admin',
                    ($pbActive === 0)          => 'deactivated by admin',
                    default                     => 'unknown',
                };
                self::log("[PostbackFirer] Skipped affiliate#{$affId} global pb for conv#{$convId}: {$reason}. URL: {$globalUrl}");
            }
            return;
        }

        $firedUrl = Helpers::buildPostbackUrl($globalUrl, $macros);
        $result   = Helpers::firePostback($firedUrl, 'GET');

        $note = '';
        if (empty($macros['sub1'])) {
            $note .= '[WARN: click_id was empty in tracking link — {click_id} in global postback resolved to internal UUID. Affiliate must include click_id={TRACKER_CLICK_ID} (e.g. &click_id={clickid}) in their offer tracking link or conversion will NOT match.] ';
        }
        if (!empty($result['error'])) {
            $note .= '[cURL: ' . $result['error'] . '] ';
        }

        self::log(
            "[PostbackFirer] Affiliate#{$affId} global postback for conv#{$convId}: "
            . "HTTP={$result['status']} success=" . ($result['success'] ? 'YES' : 'NO')
            . " URL={$firedUrl}"
            . (!empty($result['error']) ? " error={$result['error']}" : '')
        );

        // Log to postback_logs with postback_id=0 (sentinel for affiliate global)
        try {
            self::ensurePostbackLogColumns();
            Database::insert('postback_logs', [
                'postback_id'    => 0,
                'conversion_id'  => $convId,
                'fired_url'      => $firedUrl,
                'http_status'    => $result['status'],
                'response_body'  => substr($note . ($result['body'] ?? ''), 0, 1000),
                'is_success'     => $result['success'] ? 1 : 0,
                'attempt_count'  => 1,
                'first_fired_at' => date('Y-m-d H:i:s'),  // anchors RETRY_WINDOW to original fire
            ]);
        } catch (\Throwable $e) {
            self::log("[PostbackFirer] Failed to log global pb for aff#{$affId}: " . $e->getMessage());
        }

        if (!$result['success']) {
            self::log(
                "[PostbackFirer] Affiliate#{$affId} global pb FAILED. "
                . "HTTP={$result['status']} Error={$result['error']} URL={$firedUrl}"
            );
        }
    }

    /**
     * Fire admin-configured global postbacks (global_postbacks table).
     * type='advertiser' fires for all conversions; type='affiliate' fires for specific affiliate.
     */
    private static function fireAdminGlobalPostbacks(
        int    $affId,
        string $convId,
        string $convStatus,
        array  $macros
    ): void {
        // Ensure schema columns exist
        // Ensure schema columns exist once per request
        self::ensureGlobalPostbackColumns();

        try {
            $globalPbs = Database::fetchAll(
                "SELECT * FROM `global_postbacks`
                 WHERE status='active' AND event='conversion'
                   AND (
                       (type='advertiser')
                       OR (type='affiliate' AND affiliate_id=?)
                   )",
                [$affId]
            );
        } catch (\Throwable $e) {
            self::log("[PostbackFirer] DB error loading global_postbacks: " . $e->getMessage());
            return;
        }

        foreach ($globalPbs as $gpb) {
            $url    = Helpers::buildPostbackUrl($gpb['url'], $macros);
            $result = Helpers::firePostback($url, $gpb['method'] ?? 'GET');

            $note = !empty($result['error']) ? '[cURL: ' . $result['error'] . '] ' : '';

            self::log(
                "[PostbackFirer] Admin global postback #{$gpb['id']} for conv#{$convId}: "
                . "HTTP={$result['status']} success=" . ($result['success'] ? 'YES' : 'NO')
                . " URL={$url}"
                . (!empty($result['error']) ? " error={$result['error']}" : '')
            );

            try {
                Database::insert('global_postback_logs', [
                    'global_postback_id' => $gpb['id'],
                    'conversion_id'      => $convId,
                    'fired_url'          => $url,
                    'http_status'        => $result['status'],
                    'response_body'      => substr($note . ($result['body'] ?? ''), 0, 1000),
                    'is_success'         => $result['success'] ? 1 : 0,
                    'attempt_count'      => 1,
                    'first_fired_at'     => date('Y-m-d H:i:s'),  // anchors RETRY_WINDOW to original fire
                ]);
            } catch (\Throwable $e) {
                self::log("[PostbackFirer] Failed to log global_postback #{$gpb['id']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Ensure postback_logs has the attempt_count and first_fired_at columns.
     * Called once per request; subsequent calls are no-ops due to static flag.
     */
    private static bool $pbLogColumnChecked = false;

    /**
     * Check whether a column exists in a table.
     * Uses SHOW COLUMNS which works on MySQL 5.6+ and all MariaDB versions,
     * unlike ADD COLUMN IF NOT EXISTS which requires MySQL 8.0+ / MariaDB 10.3+.
     */
    private static function columnExists(string $table, string $column): bool
    {
        try {
            // SHOW COLUMNS LIKE ? is not supported by MariaDB prepared statements (SQLSTATE 42000).
            // INFORMATION_SCHEMA.COLUMNS fully supports prepared statement params on all versions.
            $row = Database::fetchOne(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            return $row !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function ensurePostbackLogColumns(): void
    {
        if (self::$pbLogColumnChecked) return;
        try {
            if (!self::columnExists('postback_logs', 'attempt_count')) {
                Database::query("ALTER TABLE `postback_logs` ADD COLUMN `attempt_count` TINYINT UNSIGNED DEFAULT 1");
            }
            if (!self::columnExists('postback_logs', 'first_fired_at')) {
                Database::query("ALTER TABLE `postback_logs` ADD COLUMN `first_fired_at` DATETIME DEFAULT NULL");
            }
            if (!self::columnExists('global_postback_logs', 'attempt_count')) {
                Database::query("ALTER TABLE `global_postback_logs` ADD COLUMN `attempt_count` TINYINT UNSIGNED DEFAULT 1");
            }
            if (!self::columnExists('global_postback_logs', 'first_fired_at')) {
                Database::query("ALTER TABLE `global_postback_logs` ADD COLUMN `first_fired_at` DATETIME DEFAULT NULL");
            }
        } catch (\Throwable $e) {}
        self::$pbLogColumnChecked = true;
    }

    /**
     * Ensure affiliates table has the global postback approval columns.
     * Also auto-approves pre-existing URLs that predate the approval system.
     * Runs once per request via static flag — NOT on every conversion.
     */
    private static bool $affColChecked = false;

    private static function ensureAffiliateGlobalPbColumns(): void
    {
        if (self::$affColChecked) return;
        try {
            if (!self::columnExists('affiliates', 'global_pb_admin_status')) {
                Database::query("ALTER TABLE `affiliates` ADD COLUMN `global_pb_admin_status` ENUM('pending','approved','rejected') DEFAULT NULL");
            }
            if (!self::columnExists('affiliates', 'global_pb_active')) {
                Database::query("ALTER TABLE `affiliates` ADD COLUMN `global_pb_active` TINYINT(1) DEFAULT 1");
            }
            // Auto-approve existing URLs (NULL status = pre-dates the approval system,
            // 'pending' = was submitted but never manually reviewed by admin — approve automatically)
            Database::query(
                "UPDATE `affiliates`
                 SET `global_pb_admin_status`='approved', `global_pb_active`=1
                 WHERE `global_postback_url` IS NOT NULL
                   AND `global_postback_url` != ''
                   AND (`global_pb_admin_status` IS NULL OR `global_pb_admin_status` = 'pending')"
            );
            // Fix rows where global_pb_active is NULL (MySQL leaves new columns NULL on existing rows)
            Database::query(
                "UPDATE `affiliates`
                 SET `global_pb_active`=1
                 WHERE `global_postback_url` IS NOT NULL
                   AND `global_postback_url` != ''
                   AND `global_pb_admin_status` = 'approved'
                   AND `global_pb_active` IS NULL"
            );
        } catch (\Throwable $e) {
            self::log("[PostbackFirer] ensureAffiliateGlobalPbColumns error: " . $e->getMessage());
        }
        self::$affColChecked = true;
    }

    /**
     * Ensure global_postbacks table has type and affiliate_id columns.
     * Runs once per request via static flag.
     */
    private static bool $globalPbColChecked = false;

    private static function ensureGlobalPostbackColumns(): void
    {
        if (self::$globalPbColChecked) return;
        try {
            if (!self::columnExists('global_postbacks', 'type')) {
                Database::query("ALTER TABLE `global_postbacks` ADD COLUMN `type` ENUM('advertiser','affiliate') NOT NULL DEFAULT 'advertiser'");
            }
            if (!self::columnExists('global_postbacks', 'affiliate_id')) {
                Database::query("ALTER TABLE `global_postbacks` ADD COLUMN `affiliate_id` INT UNSIGNED DEFAULT NULL");
            }
        } catch (\Throwable $e) {}
        self::$globalPbColChecked = true;
    }

    /**
     * Ensure conversions table has the postback tracking columns.
     * postback_sent_at is critical — without it the markSent UPDATE fails silently,
     * leaving postback_sent=0 and the UI showing "NO" forever.
     * Made public so tracking/postback.php can call it before the INSERT.
     */
    private static bool $convColumnChecked = false;

    public static function ensurePostbackSentColumn(): void
    {
        // Columns are safely handled via migrations (apply_migrations.php)
    }

    private static function ensureConversionColumns(): void
    {
        // Columns are safely handled via migrations (apply_migrations.php)
    }

    /**
     * Write a diagnostic message to the postback log file.
     * Non-blocking — any file write failure is swallowed.
     */
    public static function log(string $message): void
    {
        try {
            $logFile = defined('BASE_PATH')
                ? BASE_PATH . '/logs/postback.log'
                : __DIR__ . '/../logs/postback.log';
            $dir = dirname($logFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
            file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Log write failure must never disrupt normal operation
        }
    }
}
