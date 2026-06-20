<?php
/**
 * FraudAutoNotify — instant in-app notification for affiliates when one of their
 * conversions is flagged as High Risk (fraud_score >= 60).
 *
 * Call FraudAutoNotify::afterCheck($conversionId) IMMEDIATELY after the existing
 * FraudIQ::checkConversion() finishes — that function already writes
 * `fraud_score` + `fraud_checked_at` to the conversions row, so we just re-read
 * the row, compare to the threshold, and insert a notification.
 *
 * The threshold (60) and the wording are kept in one place so the affiliate
 * Fraud Report query, manager Fraud Report query, dashboard widget query and
 * the notification text all stay in sync.
 */
final class FraudAutoNotify
{
    /** Score >= this is considered High Risk. Mirrored everywhere fraud is filtered for affiliates/managers. */
    public const HIGH_RISK_THRESHOLD = 35;

    /** SQL fragment used by every "high risk fraud" query — single source of truth. */
    public static function highRiskWhereSql(string $convAlias = 'cv'): string
    {
        $th = self::HIGH_RISK_THRESHOLD;
        return "(COALESCE({$convAlias}.fraud_score, 0) >= {$th} "
             . "OR COALESCE({$convAlias}.ipquery_risk_score, 0) >= {$th} "
             . "OR COALESCE({$convAlias}.fraudlabspro_score, 0) >= {$th} "
             . "OR COALESCE({$convAlias}.proxycheck_score, 0) >= {$th} "
             . "OR COALESCE({$convAlias}.scamalytics_score, 0) >= {$th} "
             . "OR COALESCE({$convAlias}.frauddefense_score, 0) >= {$th})";
    }

    /** Idempotent extension of the notifications table for the Fraud Alert system. */
    public static function ensureSchema(): void
    {
        try { Database::query("ALTER TABLE notifications ADD COLUMN category    VARCHAR(40) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE notifications ADD COLUMN meta        TEXT        NULL"); }       catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE notifications ADD COLUMN resolved_at DATETIME    NULL"); }       catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE notifications ADD COLUMN resolved_by INT UNSIGNED NULL"); }      catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE notifications ADD INDEX idx_category (category)"); }            catch (\Throwable $e) {}
    }

    /** Risk-level label used in the affiliate-facing UI ("High" / "Medium"). */
    public static function riskLevel(int $score): string
    {
        if ($score >= 80) return 'high';
        if ($score >= self::HIGH_RISK_THRESHOLD) return 'medium';
        return 'low';
    }

    /**
     * Fires once per conversion. Safe to call multiple times — the duplicate
     * check at the bottom prevents spamming the notifications bell.
     */
    public static function afterCheck(string $conversionId): void
    {
        if ($conversionId === '') return;
        self::ensureSchema();

        try {
            $row = Database::fetchOne(
                "SELECT cv.conversion_id, cv.fraud_score, cv.ipquery_risk_score, cv.fraudlabspro_score, cv.proxycheck_score, cv.scamalytics_score, cv.frauddefense_score, cv.affiliate_id, cv.payout,
                        cv.fraud_checked_at,
                        af.user_id, af.affiliate_code,
                        o.name AS offer_name
                 FROM conversions cv
                 LEFT JOIN affiliates af ON af.id = cv.affiliate_id
                 LEFT JOIN offers     o  ON o.id  = cv.offer_id
                 WHERE cv.conversion_id = ? LIMIT 1",
                [$conversionId]
            );
        } catch (\Throwable $e) { return; }

        if (!$row || !$row['user_id']) return;
        $score = max(
            (int)($row['fraud_score'] ?? 0),
            (int)($row['ipquery_risk_score'] ?? 0),
            (int)($row['fraudlabspro_score'] ?? 0),
            (int)($row['proxycheck_score'] ?? 0),
            (int)($row['scamalytics_score'] ?? 0),
            (int)($row['frauddefense_score'] ?? 0)
        );
        if ($score < self::HIGH_RISK_THRESHOLD) return;

        // De-dupe: a conversion gets at most ONE fraud alert, even if the fraud
        // check is re-run later (admin clicking "Re-check Pending").
        try {
            $existing = Database::fetchOne(
                "SELECT id FROM notifications
                 WHERE user_id = ?
                   AND category = 'fraud'
                   AND link    LIKE ? LIMIT 1",
                [(int)$row['user_id'], '%cid=' . $conversionId]
            );
            if ($existing) return;
        } catch (\Throwable $e) {}

        // Build the affiliate-facing alert message exactly per spec — no fraud
        // score percentage is leaked, only a Risk Level label.
        $shortId = substr((string)$conversionId, 0, 8);
        $offer   = trim((string)($row['offer_name'] ?? ''));
        $risk    = self::riskLevel($score);
        $riskTxt = $risk === 'high' ? 'High Risk' : 'Medium Risk';
        $msg     = "\u{26A0}\u{FE0F} Your conversion has been detected as fraudulent.\n"
                 . 'Conversion ID: #' . $shortId
                 . ($offer !== '' ? "\nOffer Name: " . $offer : '')
                 . "\nRisk Level: " . $riskTxt . "\n\n"
                 . 'Please review your traffic sources and improve traffic quality, otherwise your future conversions may be rejected or your account may face risk actions.';

        // Compact JSON sidecar consumed by the Fraud Alert dropdown.
        $meta = json_encode([
            'conversion_id' => (string)$conversionId,
            'short_id'      => $shortId,
            'offer_id'      => (int)($row['offer_id'] ?? 0),
            'offer_name'    => $offer,
            'fraud_score'   => $score,           // not shown to affiliate — used for risk banding only
            'risk_level'    => $risk,
            'detected_at'   => (string)($row['fraud_checked_at'] ?? date('Y-m-d H:i:s')),
        ], JSON_UNESCAPED_UNICODE);

        try {
            require_once BASE_PATH . '/core/NotificationHelper.php';
            
            // Notify Affiliate
            NotificationHelper::notifyUser(
                (int)$row['user_id'],
                'Fraud Alert',
                $msg,
                $risk === 'high' ? 'danger' : 'warning',
                '/affiliate/fraud-report?cid=' . urlencode($conversionId),
                ['type' => 'fraud_alert', 'conversion_id' => $conversionId]
            );

            // Notify Admin
            $adminMsg = "Fraud Alert: Conversion #{$shortId} from Affiliate #{$row['affiliate_id']} marked as {$riskTxt}.";
            NotificationHelper::notifyRole('admin', 'Fraud Alert', $adminMsg, 'danger', '/admin/fraud-report');

            // Notify Manager (if assigned)
            $managerRow = Database::fetchOne("SELECT manager_id FROM affiliates WHERE user_id=?", [(int)$row['user_id']]);
            if ($managerRow && !empty($managerRow['manager_id'])) {
                NotificationHelper::notifyUser((int)$managerRow['manager_id'], 'Fraud Alert', $adminMsg, 'danger', '/manager/fraud-report');
            }

            // Still need to update the category and meta, but notifyUser creates the row.
            // A quick fix is to update the latest notification for this user:
            Database::query("UPDATE notifications SET category='fraud', meta=? WHERE user_id=? AND link LIKE ? ORDER BY id DESC LIMIT 1",
                [$meta, (int)$row['user_id'], '%cid=' . urlencode($conversionId)]
            );
        } catch (\Throwable $e) {}
    }

    /**
     * Backfill missing fraud alerts for every historical high-risk conversion
     * that doesn't yet have a notification row. Safe to call repeatedly — the
     * LEFT JOIN below excludes anything that already has a fraud alert linked
     * by conversion_id, so duplicates can never be created.
     *
     * Returns the number of rows inserted on this run. Use `$userId` to scope
     * the backfill to one affiliate's owner (called by the API on first load),
     * or pass 0 to process every user (admin maintenance / cron).
     *
     * `$limit` keeps a single call bounded so we never time out — repeat the
     * call until the return value is < $limit and the backlog is exhausted.
     */
    public static function backfillRecent(int $userId = 0, int $limit = 500): int
    {
        self::ensureSchema();
        $limit = max(1, min(5000, $limit));

        $where  = "(" . self::highRiskWhereSql('cv') . ")"
                . " AND af.user_id IS NOT NULL"
                . " AND n.id IS NULL";
        $params = [];
        if ($userId > 0) {
            $where .= " AND af.user_id = ?";
            $params[] = $userId;
        }

        try {
            // The LEFT JOIN to `notifications` filters out conversions that
            // already have a fraud alert (matched on the cid in the link URL).
            $rows = Database::fetchAll(
                "SELECT cv.conversion_id, cv.fraud_score, cv.ipquery_risk_score, cv.fraudlabspro_score, cv.proxycheck_score, cv.scamalytics_score, cv.frauddefense_score, cv.affiliate_id, cv.offer_id,
                        cv.fraud_checked_at, cv.converted_at,
                        af.user_id, af.affiliate_code,
                        o.name AS offer_name
                 FROM conversions cv
                 INNER JOIN affiliates af ON af.id = cv.affiliate_id
                 LEFT  JOIN offers     o  ON o.id  = cv.offer_id
                 LEFT  JOIN notifications n
                   ON n.category = 'fraud'
                  AND n.user_id  = af.user_id
                  AND n.link LIKE CONCAT('%cid=', cv.conversion_id)
                 WHERE $where
                 ORDER BY cv.converted_at DESC
                 LIMIT $limit",
                $params
            );
        } catch (\Throwable $e) { return 0; }

        if (!$rows) return 0;

        $inserted = 0;
        foreach ($rows as $row) {
            $score = max(
                (int)($row['fraud_score'] ?? 0),
                (int)($row['ipquery_risk_score'] ?? 0),
                (int)($row['fraudlabspro_score'] ?? 0),
                (int)($row['proxycheck_score'] ?? 0),
                (int)($row['scamalytics_score'] ?? 0),
                (int)($row['frauddefense_score'] ?? 0)
            );
            $convId  = (string)$row['conversion_id'];
            if ($convId === '' || $score < self::HIGH_RISK_THRESHOLD) continue;

            $shortId = substr($convId, 0, 8);
            $offer   = trim((string)($row['offer_name'] ?? ''));
            $risk    = self::riskLevel($score);
            $riskTxt = $risk === 'high' ? 'High Risk' : 'Medium Risk';
            $msg     = "\u{26A0}\u{FE0F} Your conversion has been detected as fraudulent.\n"
                     . 'Conversion ID: #' . $shortId
                     . ($offer !== '' ? "\nOffer Name: " . $offer : '')
                     . "\nRisk Level: " . $riskTxt . "\n\n"
                     . 'Please review your traffic sources and improve traffic quality, otherwise your future conversions may be rejected or your account may face risk actions.';

            $meta = json_encode([
                'conversion_id' => $convId,
                'short_id'      => $shortId,
                'offer_id'      => (int)($row['offer_id'] ?? 0),
                'offer_name'    => $offer,
                'fraud_score'   => $score,
                'risk_level'    => $risk,
                'detected_at'   => (string)($row['fraud_checked_at'] ?? $row['converted_at'] ?? date('Y-m-d H:i:s')),
            ], JSON_UNESCAPED_UNICODE);

            try {
                Database::insert('notifications', [
                    'user_id'     => (int)$row['user_id'],
                    'target_role' => null,
                    'type'        => $risk === 'high' ? 'danger' : 'warning',
                    'title'       => 'Fraud Alert',
                    'message'     => $msg,
                    'link'        => '/affiliate/fraud-report?cid=' . urlencode($convId),
                    'is_read'     => 0,
                    'category'    => 'fraud',
                    'meta'        => $meta,
                    // Backdate the notification so the bell shows the detection
                    // time, not the moment we ran the backfill.
                    'created_at'  => (string)($row['fraud_checked_at'] ?? $row['converted_at'] ?? date('Y-m-d H:i:s')),
                ]);
                $inserted++;
            } catch (\Throwable $e) {}
        }
        return $inserted;
    }
}
