<?php
/**
 * ManagerPermissions — admin-controlled access control for Affiliate Managers.
 *
 * Adds an overlay permission layer on top of the existing
 * `Auth::check('affiliate_manager')` role guard. Every page or action that
 * needs to be gated calls one of:
 *
 *     ManagerPermissions::can($managerId, 'view_affiliates')
 *     ManagerPermissions::requirePermission('view_affiliates')   // 403s on deny
 *
 * Storage:
 *   - manager_permissions     — one row per (manager_id, permission_key)
 *   - manager_fraud_rejections — audit log written every time a manager rejects
 *                                 a fraud conversion
 *   - manager_messages         — admin ↔ manager threaded inbox
 *   - manager_activity_log     — permission-gated action audit log
 *
 * Defaults intentionally match the most restrictive interpretation of the
 * spec: managers can VIEW their assigned affiliates' reports out of the box,
 * but cannot generate invoices, mark paid, or process payouts unless the
 * admin explicitly grants those permissions.
 *
 * No existing schema is altered — only new tables + idempotent CREATE TABLE
 * IF NOT EXISTS guards.
 */
class ManagerPermissions
{
    /** Canonical permission catalogue (label + default for new managers). */
    public const CATALOGUE = [
        'view_affiliates'        => ['label' => 'View affiliate accounts',          'default' => 1, 'group' => 'access'],
        'view_affiliate_reports' => ['label' => 'View affiliate reports',           'default' => 1, 'group' => 'access'],
        'reject_fraud_conv'      => ['label' => 'Approve/reject fraud conversions', 'default' => 0, 'group' => 'fraud'],
        'view_fraud_reports'     => ['label' => 'View fraud reports',               'default' => 1, 'group' => 'fraud'],
        'create_invoice_request' => ['label' => 'Create invoice requests',          'default' => 1, 'group' => 'invoice'],
        'access_support'         => ['label' => 'Access support / chat',            'default' => 1, 'group' => 'support'],
        'view_payment_history'   => ['label' => 'View payment history',             'default' => 1, 'group' => 'access'],
        'view_offer_performance' => ['label' => 'View offer performance',           'default' => 1, 'group' => 'access'],
        'access_affiliate_tickets' => ['label' => 'Access affiliate tickets',       'default' => 1, 'group' => 'support'],
        'traffic_source_override'  => ['label' => 'Traffic Source Override',        'default' => 1, 'group' => 'access'],
        // Optional "elevated" permissions — admin must opt-in.
        'edit_affiliate_payouts' => ['label' => 'Edit affiliate payouts',           'default' => 0, 'group' => 'access'],
        'generate_invoices'      => ['label' => 'Allow invoice generator access',   'default' => 0, 'group' => 'invoice'],
        'mark_invoice_paid'      => ['label' => 'Allow mark-invoice-paid',          'default' => 0, 'group' => 'invoice'],
    ];

    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_permissions` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`      INT UNSIGNED NOT NULL,
                `permission_key`  VARCHAR(64)  NOT NULL,
                `granted`         TINYINT(1)   NOT NULL DEFAULT 0,
                `updated_by`      INT UNSIGNED NULL,
                `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_mgr_perm` (`manager_id`,`permission_key`),
                KEY `idx_manager` (`manager_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_fraud_rejections` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`      INT UNSIGNED NOT NULL,
                `conversion_id`   VARCHAR(64)  NOT NULL,
                `affiliate_id`    INT UNSIGNED NULL,
                `offer_id`        INT UNSIGNED NULL,
                `reason`          TEXT         NULL,
                `rejected_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_manager`     (`manager_id`),
                KEY `idx_conversion`  (`conversion_id`),
                KEY `idx_rejected_at` (`rejected_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_messages` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`      INT UNSIGNED NOT NULL,
                `sender_role`     ENUM('admin','manager') NOT NULL,
                `sender_user_id`  INT UNSIGNED NOT NULL,
                `body`            MEDIUMTEXT   NOT NULL,
                `attachment_path` VARCHAR(512) NULL,
                `attachment_name` VARCHAR(255) NULL,
                `read_by_admin`   TINYINT(1)   NOT NULL DEFAULT 0,
                `read_by_manager` TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_manager`     (`manager_id`),
                KEY `idx_created_at`  (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `manager_activity_log` (
                `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `manager_id`      INT UNSIGNED NOT NULL,
                `action`          VARCHAR(64)  NOT NULL,
                `target_type`     VARCHAR(32)  NULL,
                `target_id`       VARCHAR(64)  NULL,
                `note`            TEXT         NULL,
                `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_manager_action` (`manager_id`,`action`),
                KEY `idx_created_at`     (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}
    }

    /**
     * Return the manager_id of the currently-logged-in affiliate manager, or
     * 0 if the caller is admin / not a manager. Used by views to surface the
     * caller's effective permission set without an extra DB lookup.
     */
    public static function currentManagerId(): int
    {
        try {
            if (\Auth::role() !== 'affiliate_manager') return 0;
            $row = Database::fetchOne(
                "SELECT id FROM affiliate_managers WHERE user_id=? LIMIT 1",
                [(int)\Auth::id()]
            );
            return (int)($row['id'] ?? 0);
        } catch (\Throwable $_) { return 0; }
    }

    /**
     * Return the full permission map for a manager, with admin overrides applied
     * over the defaults. Keys = permission_key, values = bool.
     */
    public static function map(int $managerId): array
    {
        self::ensureSchema();
        $out = [];
        foreach (self::CATALOGUE as $k => $meta) {
            $out[$k] = (bool)$meta['default'];
        }
        if ($managerId <= 0) return $out;

        try {
            $rows = Database::fetchAll(
                "SELECT permission_key, granted FROM manager_permissions WHERE manager_id=?",
                [$managerId]
            ) ?: [];
            foreach ($rows as $r) {
                if (array_key_exists($r['permission_key'], $out)) {
                    $out[$r['permission_key']] = (int)$r['granted'] === 1;
                }
            }
        } catch (\Throwable $_) {}
        return $out;
    }

    public static function can(int $managerId, string $key): bool
    {
        if (!array_key_exists($key, self::CATALOGUE)) return false;
        $map = self::map($managerId);
        return !empty($map[$key]);
    }

    /**
     * Hard gate for use at the top of a controller. If the caller is not a
     * manager OR doesn't have the named permission, render a 403 page and
     * exit. Admins always pass. Renamed from `require` because that's a
     * PHP language construct and reads awkwardly at call sites.
     */
    public static function requirePermission(string $key): void
    {
        // Admins bypass.
        if (\Auth::role() === 'admin') return;

        $managerId = self::currentManagerId();
        if ($managerId === 0 || !self::can($managerId, $key)) {
            self::renderForbidden($key);
            exit;
        }
    }

    /** Persist a single permission for a manager (admin-only). */
    public static function set(int $managerId, string $key, bool $granted, int $updatedBy = 0): bool
    {
        if ($managerId <= 0) return false;
        if (!array_key_exists($key, self::CATALOGUE)) return false;
        self::ensureSchema();
        try {
            $exists = Database::fetchOne(
                "SELECT id FROM manager_permissions WHERE manager_id=? AND permission_key=? LIMIT 1",
                [$managerId, $key]
            );
            if ($exists) {
                Database::query(
                    "UPDATE manager_permissions SET granted=?, updated_by=? WHERE id=?",
                    [$granted ? 1 : 0, $updatedBy ?: null, (int)$exists['id']]
                );
            } else {
                Database::query(
                    "INSERT INTO manager_permissions (manager_id, permission_key, granted, updated_by)
                     VALUES (?,?,?,?)",
                    [$managerId, $key, $granted ? 1 : 0, $updatedBy ?: null]
                );
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[ManagerPermissions::set] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk-apply a permission map for a manager. Missing keys are reset to
     * 0 — this lets the admin UI POST exactly what's checked.
     */
    public static function setAll(int $managerId, array $granted, int $updatedBy = 0): void
    {
        if ($managerId <= 0) return;
        foreach (self::CATALOGUE as $k => $_) {
            self::set($managerId, $k, !empty($granted[$k]), $updatedBy);
        }
    }

    /** Activity audit log writer — never throws. */
    public static function logActivity(int $managerId, string $action, ?string $targetType = null, ?string $targetId = null, ?string $note = null): void
    {
        if ($managerId <= 0) return;
        self::ensureSchema();
        try {
            Database::query(
                "INSERT INTO manager_activity_log (manager_id, action, target_type, target_id, note)
                 VALUES (?,?,?,?,?)",
                [$managerId, $action, $targetType, $targetId, $note]
            );
        } catch (\Throwable $_) {}
    }

    /** Fraud-rejection audit writer. */
    public static function logFraudRejection(int $managerId, string $conversionId, ?int $affiliateId, ?int $offerId, string $reason): void
    {
        if ($managerId <= 0 || $conversionId === '') return;
        self::ensureSchema();
        try {
            Database::query(
                "INSERT INTO manager_fraud_rejections (manager_id, conversion_id, affiliate_id, offer_id, reason)
                 VALUES (?,?,?,?,?)",
                [$managerId, $conversionId, $affiliateId, $offerId, $reason]
            );
        } catch (\Throwable $_) {}
        self::logActivity($managerId, 'fraud_reject', 'conversion', $conversionId, $reason);
    }

    /** Render the 403 page used by require(). Inline so we don't depend on a view file. */
    private static function renderForbidden(string $key): void
    {
        $label = self::CATALOGUE[$key]['label'] ?? $key;
        http_response_code(403);
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => "Permission denied. Required: $label"]);
            return;
        }
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access Denied</title>'
           . '<link rel="stylesheet" href="/assets/css/app.min.css"></head><body style="background:#F8FAFC;font-family:-apple-system,Segoe UI,Roboto,sans-serif">'
           . '<div style="max-width:520px;margin:80px auto;background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:36px 32px;text-align:center;box-shadow:0 10px 32px -16px rgba(15,23,42,.2)">'
           . '<div style="width:64px;height:64px;border-radius:50%;background:#FEE2E2;color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:30px">⊘</div>'
           . '<h1 style="margin:0 0 6px;font-size:22px;color:#0F172A">Access Denied</h1>'
           . '<p style="margin:0 0 6px;color:#64748B;font-size:14px">You don\'t have permission to access this section.</p>'
           . '<p style="margin:0 0 22px;color:#94A3B8;font-size:12.5px">Required permission: <code>' . htmlspecialchars($label, ENT_QUOTES) . '</code></p>'
           . '<a href="/affiliate_manager/dashboard" style="display:inline-block;background:#4F46E5;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13.5px">Back to Dashboard</a>'
           . '</div></body></html>';
    }
}
