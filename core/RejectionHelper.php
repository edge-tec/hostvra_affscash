<?php
/**
 * RejectionHelper — schema + reason management for the admin
 * Conversion Approval & Rejection System.
 *
 * - Adds idempotent migrations for conversions.rejection_reason / rejected_at /
 *   action_admin_id and creates the rejection_reasons lookup table.
 * - Seeds the five default reasons on first run.
 * - Provides a single payload builder so every reject path stores the same fields.
 */
final class RejectionHelper
{
    private static bool $schemaEnsured = false;

    /** Run once per request; idempotent. */
    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try { Database::query("ALTER TABLE `conversions` ADD COLUMN `rejection_reason` VARCHAR(500) DEFAULT NULL"); } catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE `conversions` ADD COLUMN `rejected_at`      DATETIME DEFAULT NULL"); }    catch (\Throwable $e) {}
        try { Database::query("ALTER TABLE `conversions` ADD COLUMN `action_admin_id`  INT UNSIGNED DEFAULT NULL"); } catch (\Throwable $e) {}

        try { Database::query("CREATE TABLE IF NOT EXISTS `rejection_reasons` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `label`      VARCHAR(255) NOT NULL,
            `is_default` TINYINT(1)   NOT NULL DEFAULT 0,
            `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
            `sort_order` INT          NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP    NULL DEFAULT NULL,
            UNIQUE KEY `uniq_label` (`label`)
        )"); } catch (\Throwable $e) {}

        // Seed the five default reasons once.
        try {
            $cnt = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM rejection_reasons")['c'] ?? 0);
            if ($cnt === 0) {
                $defaults = [
                    'Fraud Conversion',
                    'Proxy/VPN Traffic',
                    'Duplicate Lead',
                    'Invalid Traffic',
                    'Fake Information',
                ];
                foreach ($defaults as $i => $label) {
                    try {
                        Database::insert('rejection_reasons', [
                            'label'      => $label,
                            'is_default' => 1,
                            'is_active'  => 1,
                            'sort_order' => $i + 1,
                        ]);
                    } catch (\Throwable $e) {}
                }
            }
        } catch (\Throwable $e) {}
    }

    /** All active reasons, sorted for dropdowns. */
    public static function getActiveReasons(): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT id, label, is_default FROM rejection_reasons
                 WHERE is_active=1 ORDER BY sort_order ASC, id ASC"
            ) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** All reasons (active + inactive) for the management page. */
    public static function getAllReasons(): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT id, label, is_default, is_active, sort_order, created_at, updated_at
                 FROM rejection_reasons ORDER BY sort_order ASC, id ASC"
            ) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Build the column-update payload that every reject/approve handler should
     * apply to the conversions row. Centralised so every code path stores the
     * same audit fields (rejection_reason + rejected_at) and clears them on
     * re-approve.
     */
    public static function buildUpdatePayload(string $newStatus, ?string $reason = null, ?int $adminId = null): array
    {
        self::ensureSchema();
        $now     = date('Y-m-d H:i:s');
        $payload = ['status' => $newStatus];

        if ($newStatus === 'approved') {
            $payload['approved_at']      = $now;
            // Re-approving clears prior rejection bookkeeping so reports show fresh data.
            $payload['rejection_reason'] = null;
            $payload['rejected_at']      = null;
        } elseif ($newStatus === 'rejected' || $newStatus === 'chargebacked') {
            $payload['rejection_reason'] = $reason !== null && $reason !== ''
                ? mb_substr(trim($reason), 0, 500)
                : null;
            $payload['rejected_at']      = $now;
        } else {
            // pending or other — clear approved_at so it doesn't lie about the state.
            $payload['approved_at'] = null;
        }
        if ($adminId !== null && $adminId > 0) {
            $payload['action_admin_id'] = $adminId;
        }
        return $payload;
    }
}
