<?php
/**
 * VpnSkipList — per-affiliate VPN/Proxy detection bypass.
 *
 * When an affiliate is on this list, VPN/proxy/hosting/datacenter detection
 * still runs (so dashboards keep their data) but the BLOCK action is skipped:
 *   - tracking/click.php does not set $isVpnBlocked = true
 *   - tracking/smartlink.php does not 403-block before delegation
 *   - vpn_blocked_log does not get a row for this affiliate's traffic
 *
 * Storage: dedicated table `vpn_proxy_skip_affiliates` (audit fields included).
 * Caching: a single SELECT per request, then in-memory lookup.
 *
 * Only admins manage the list; no affiliate-facing surface exists.
 */
final class VpnSkipList
{
    private static bool $schemaEnsured = false;
    private static array $skippedIds   = [];   // affiliate_id => true
    private static bool $cacheLoaded   = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `vpn_proxy_skip_affiliates` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id`      INT UNSIGNED NOT NULL,
                `added_by_admin_id` INT UNSIGNED NULL,
                `note`              VARCHAR(500) NULL,
                `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_affiliate` (`affiliate_id`),
                INDEX `idx_aff` (`affiliate_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}
    }

    /** Hot-path check called from click.php / smartlink.php. */
    public static function isSkipped(?int $affiliateId): bool
    {
        if (!$affiliateId) return false;
        self::ensureSchema();
        if (!self::$cacheLoaded) {
            try {
                $rows = Database::fetchAll("SELECT affiliate_id FROM vpn_proxy_skip_affiliates") ?: [];
                foreach ($rows as $r) self::$skippedIds[(int)$r['affiliate_id']] = true;
            } catch (\Throwable $e) {}
            self::$cacheLoaded = true;
        }
        return isset(self::$skippedIds[$affiliateId]);
    }

    /** Convenience: resolve an affiliate code (e.g. AFFF06092C5) to ID then check. */
    public static function isSkippedByCode(?string $affCode): bool
    {
        if (!$affCode) return false;
        try {
            $row = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code=?", [$affCode]);
            if ($row) return self::isSkipped((int)$row['id']);
        } catch (\Throwable $e) {}
        return false;
    }

    /** Full list with affiliate + admin info for the admin management page. */
    public static function getAll(): array
    {
        self::ensureSchema();
        try {
            return Database::fetchAll(
                "SELECT s.id, s.affiliate_id, s.added_by_admin_id, s.note, s.created_at,
                        af.affiliate_code,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        u.email AS affiliate_email,
                        adm_u.first_name AS admin_first, adm_u.last_name AS admin_last,
                        adm_u.email      AS admin_email
                 FROM vpn_proxy_skip_affiliates s
                 LEFT JOIN affiliates af  ON af.id = s.affiliate_id
                 LEFT JOIN users      u   ON u.id  = af.user_id
                 LEFT JOIN users      adm_u ON adm_u.id = s.added_by_admin_id
                 ORDER BY s.created_at DESC, s.id DESC"
            ) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    /** Add an affiliate to the skip list. Returns the new row id, or 0 on failure. */
    public static function add(int $affiliateId, ?int $adminId = null, ?string $note = null): int
    {
        if ($affiliateId <= 0) return 0;
        self::ensureSchema();
        try {
            $id = Database::insert('vpn_proxy_skip_affiliates', [
                'affiliate_id'      => $affiliateId,
                'added_by_admin_id' => $adminId,
                'note'              => $note ? mb_substr(trim($note), 0, 500) : null,
            ]);
            // Bust in-process cache so the next isSkipped() call sees the new id.
            self::$skippedIds[$affiliateId] = true;
            return (int)$id;
        } catch (\Throwable $e) { return 0; }
    }

    /** Remove a single skip-list entry by row id. */
    public static function remove(int $id): bool
    {
        if ($id <= 0) return false;
        try {
            $row = Database::fetchOne("SELECT affiliate_id FROM vpn_proxy_skip_affiliates WHERE id=?", [$id]);
            Database::query("DELETE FROM vpn_proxy_skip_affiliates WHERE id=?", [$id]);
            if ($row) unset(self::$skippedIds[(int)$row['affiliate_id']]);
            return true;
        } catch (\Throwable $e) { return false; }
    }
}
