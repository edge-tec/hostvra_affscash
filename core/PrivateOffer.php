<?php
/**
 * PrivateOffer — manages the per-offer / per-affiliate access list for offers
 * marked with visibility='private'. Real-time enforcement: every check is a
 * fresh DB query, no caching. Once admin grants/revokes, the next request
 * already sees the change.
 *
 * Tables created (idempotent):
 *   private_offer_access      — (offer_id, affiliate_id) grant rows
 *   private_offer_access_log  — append-only audit trail of grants/revokes
 *                                and click-time access attempts
 */
class PrivateOffer {

    private static bool $tablesReady = false;

    /** Idempotent schema bootstrap. Safe to call repeatedly. */
    public static function ensureTables(): void {
        if (self::$tablesReady) return;
        self::$tablesReady = true;
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `private_offer_access` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `offer_id`     INT UNSIGNED NOT NULL,
                `affiliate_id` INT UNSIGNED NOT NULL,
                `granted_by`   INT UNSIGNED DEFAULT NULL,
                `granted_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `notes`        VARCHAR(500) DEFAULT NULL,
                UNIQUE KEY `uq_offer_aff` (`offer_id`, `affiliate_id`),
                INDEX `idx_offer` (`offer_id`),
                INDEX `idx_aff`   (`affiliate_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `private_offer_access_log` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `offer_id`     INT UNSIGNED NOT NULL,
                `affiliate_id` INT UNSIGNED DEFAULT NULL,
                `action`       ENUM('grant','revoke','allow','deny','enable','disable') NOT NULL,
                `actor_id`     INT UNSIGNED DEFAULT NULL,
                `source`       VARCHAR(20) NOT NULL DEFAULT 'admin',
                `details`      VARCHAR(500) DEFAULT NULL,
                `ip_address`   VARCHAR(45) DEFAULT NULL,
                `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_offer`   (`offer_id`),
                INDEX `idx_aff`     (`affiliate_id`),
                INDEX `idx_action`  (`action`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $_) {}

        // Ensure the offers.visibility column exists (mirrors the migration in
        // controllers/admin/OfferController.php — we may run before that file
        // is loaded on a non-admin request).
        try {
            Database::query("ALTER TABLE offers ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'public'");
        } catch (\Throwable $_) {}
    }

    /** True when the offer row has visibility='private'. */
    public static function isPrivate(array $offer): bool {
        $v = strtolower((string)($offer['visibility'] ?? 'public'));
        return $v === 'private';
    }

    /** True if a grant row exists for (offer_id, affiliate_id). */
    public static function hasAccess(int $offerId, int $affiliateId): bool {
        if ($offerId <= 0 || $affiliateId <= 0) return false;
        self::ensureTables();
        try {
            $row = Database::fetchOne(
                "SELECT id FROM private_offer_access WHERE offer_id=? AND affiliate_id=? LIMIT 1",
                [$offerId, $affiliateId]
            );
            return (bool)$row;
        } catch (\Throwable $_) {
            return false;
        }
    }

    /**
     * Grant an affiliate access to a private offer. Idempotent — returns true
     * for both new grants and existing grants (with notes updated).
     */
    public static function grant(int $offerId, int $affiliateId, ?int $adminId, ?string $notes = null): bool {
        if ($offerId <= 0 || $affiliateId <= 0) return false;
        self::ensureTables();
        try {
            Database::query(
                "INSERT INTO private_offer_access (offer_id, affiliate_id, granted_by, notes)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE granted_by=VALUES(granted_by), notes=VALUES(notes)",
                [$offerId, $affiliateId, $adminId, $notes]
            );
            self::log($offerId, $affiliateId, 'grant', $adminId, 'admin', $notes);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Revoke an affiliate's access to a private offer. */
    public static function revoke(int $offerId, int $affiliateId, ?int $adminId): bool {
        if ($offerId <= 0 || $affiliateId <= 0) return false;
        self::ensureTables();
        try {
            Database::query(
                "DELETE FROM private_offer_access WHERE offer_id=? AND affiliate_id=?",
                [$offerId, $affiliateId]
            );
            self::log($offerId, $affiliateId, 'revoke', $adminId, 'admin');
            return true;
        } catch (\Throwable $_) {
            return false;
        }
    }

    /** Switch an offer between private and public visibility. */
    public static function setPrivate(int $offerId, bool $private, ?int $adminId): bool {
        if ($offerId <= 0) return false;
        self::ensureTables();
        try {
            $newVis = $private ? 'private' : 'public';
            Database::query("UPDATE offers SET visibility=? WHERE id=?", [$newVis, $offerId]);
            self::log($offerId, null, $private ? 'enable' : 'disable', $adminId, 'admin', "visibility -> $newVis");
            return true;
        } catch (\Throwable $_) {
            return false;
        }
    }

    /**
     * Append an audit-log row. Failures here are non-fatal — logging must
     * never block an actual grant/revoke/tracking decision.
     */
    public static function log(int $offerId, ?int $affiliateId, string $action, ?int $actorId = null, string $source = 'admin', ?string $details = null, ?string $ip = null): void {
        self::ensureTables();
        try {
            Database::insert('private_offer_access_log', [
                'offer_id'     => $offerId,
                'affiliate_id' => $affiliateId,
                'action'       => $action,
                'actor_id'     => $actorId,
                'source'       => substr($source, 0, 20),
                'details'      => $details !== null ? substr($details, 0, 500) : null,
                'ip_address'   => $ip ?: (class_exists('Helpers') ? Helpers::getIp() : null),
            ]);
        } catch (\Throwable $_) {}
    }

    /** All offers currently marked private, with grant counts. */
    public static function listPrivateOffers(): array {
        self::ensureTables();
        try {
            return Database::fetchAll(
                "SELECT o.id, o.name, o.payout_type, o.payout_amount, o.payout_amount as payout, o.status,
                        (SELECT COUNT(*) FROM private_offer_access poa WHERE poa.offer_id = o.id) AS access_count
                 FROM offers o
                 WHERE COALESCE(o.visibility,'public') = 'private'
                 ORDER BY o.created_at DESC"
            ) ?: [];
        } catch (\Throwable $_) {
            return [];
        }
    }

    /** Affiliates currently granted access to a given private offer. */
    public static function listAccess(int $offerId): array {
        if ($offerId <= 0) return [];
        self::ensureTables();
        try {
            return Database::fetchAll(
                "SELECT poa.id AS grant_id, poa.granted_at, poa.granted_by, poa.notes,
                        af.id AS affiliate_id, af.affiliate_code,
                        u.email, u.first_name, u.last_name, u.status AS user_status
                 FROM private_offer_access poa
                 JOIN affiliates af ON af.id = poa.affiliate_id
                 JOIN users      u  ON u.id = af.user_id
                 WHERE poa.offer_id = ?
                 ORDER BY poa.granted_at DESC",
                [$offerId]
            ) ?: [];
        } catch (\Throwable $_) {
            return [];
        }
    }

    /**
     * Resolve a free-form identifier ("123", "user@x.com", "AFF1234") to an
     * affiliates row. Returns null if no match. Match priority:
     *   1. numeric → affiliates.id
     *   2. contains @  → users.email
     *   3. AFF prefix or alphanumeric → affiliates.affiliate_code
     *   4. fallback → first+last name LIKE
     */
    public static function resolveAffiliate(string $needle): ?array {
        $needle = trim($needle);
        if ($needle === '') return null;
        try {
            if (ctype_digit($needle)) {
                $r = Database::fetchOne(
                    "SELECT af.id, af.affiliate_code, u.email, u.first_name, u.last_name
                     FROM affiliates af JOIN users u ON u.id = af.user_id
                     WHERE af.id = ? LIMIT 1",
                    [(int)$needle]
                );
                if ($r) return $r;
            }
            if (strpos($needle, '@') !== false) {
                $r = Database::fetchOne(
                    "SELECT af.id, af.affiliate_code, u.email, u.first_name, u.last_name
                     FROM affiliates af JOIN users u ON u.id = af.user_id
                     WHERE LOWER(u.email) = LOWER(?) LIMIT 1",
                    [$needle]
                );
                if ($r) return $r;
            }
            $r = Database::fetchOne(
                "SELECT af.id, af.affiliate_code, u.email, u.first_name, u.last_name
                 FROM affiliates af JOIN users u ON u.id = af.user_id
                 WHERE af.affiliate_code = ? LIMIT 1",
                [$needle]
            );
            if ($r) return $r;
            // Last-resort fallback: name match (returns exactly one or none)
            $r = Database::fetchOne(
                "SELECT af.id, af.affiliate_code, u.email, u.first_name, u.last_name
                 FROM affiliates af JOIN users u ON u.id = af.user_id
                 WHERE CONCAT(u.first_name,' ',u.last_name) LIKE ? LIMIT 2",
                ['%' . $needle . '%']
            );
            return $r ?: null;
        } catch (\Throwable $_) {
            return null;
        }
    }

    /**
     * Tracking-time access check. Returns true when the request is allowed to
     * proceed; logs every denial (and every allow, when $logAllow=true).
     */
    public static function checkClickAccess(array $offer, int $affiliateId, bool $logAllow = false): bool {
        if (!self::isPrivate($offer)) return true;
        $offerId = (int)($offer['id'] ?? 0);
        $allowed = self::hasAccess($offerId, $affiliateId);
        if ($allowed) {
            if ($logAllow) self::log($offerId, $affiliateId, 'allow', null, 'click');
            return true;
        }
        self::log($offerId, $affiliateId, 'deny', null, 'click', 'No grant for affiliate on private offer');
        return false;
    }
}
