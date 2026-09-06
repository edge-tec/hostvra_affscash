<?php
/**
 * Blocklist - Central enforcement for the admin-managed fraud_blocklist table.
 *
 * Provides a single source of truth for blocking requests, logins, dashboard
 * access, tracking clicks, postbacks, and API calls based on the entries
 * managed at /admin/fraud-center/blocklist.
 *
 * Supported types: ip, cidr, user_agent, asn, affiliate_id, device_fp
 * Supported scopes: all (everywhere), clicks (tracking links), conversions (postbacks)
 * Supported actions: block (hard deny), flag/throttle (recorded but not blocked)
 *
 * The table is shared with the Fraud Center; this class never writes new
 * entries — only reads them and increments hit_count on match.
 */
class Blocklist {
    private static bool $tableReady   = false;
    private static array $cache       = []; // per-request entry cache keyed by scope
    private static bool $guardRan     = false;
    private static array $whitelistIp = [];

    /** Ensure the fraud_blocklist table exists. Idempotent + safe on fresh installs. */
    private static function ensureTable(): void {
        if (self::$tableReady) return;
        self::$tableReady = true;

        try {
            // Fast-path: check if table is already readable without acquiring DDL metadata lock
            Database::fetchOne("SELECT 1 FROM `fraud_blocklist` LIMIT 1");
            return;
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `fraud_blocklist` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `type`       ENUM('ip','cidr','user_agent','affiliate_id','device_fp','asn') NOT NULL,
                `value`      VARCHAR(255) NOT NULL,
                `reason`     VARCHAR(255) DEFAULT NULL,
                `action`     ENUM('flag','block','throttle') NOT NULL DEFAULT 'block',
                `scope`      ENUM('clicks','conversions','all') NOT NULL DEFAULT 'all',
                `source`     ENUM('manual','auto_rule','imported') NOT NULL DEFAULT 'manual',
                `hit_count`  INT UNSIGNED NOT NULL DEFAULT 0,
                `expires_at` DATETIME DEFAULT NULL,
                `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
                `created_by` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_type_value` (`type`, `value`(100)),
                INDEX `idx_type_active` (`type`, `is_active`),
                INDEX `idx_active`      (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $_) {}
    }

    /** Load active, non-expired blocklist entries matching the given scope. */
    private static function entries(string $scope): array {
        if (isset(self::$cache[$scope])) return self::$cache[$scope];
        self::ensureTable();

        $scopeSql = "AND scope='all'";
        if ($scope === 'clicks')      $scopeSql = "AND scope IN ('all','clicks')";
        elseif ($scope === 'conversions') $scopeSql = "AND scope IN ('all','conversions')";

        try {
            $rows = Database::fetchAll(
                "SELECT id, type, value, action, scope
                   FROM fraud_blocklist
                  WHERE is_active = 1
                    $scopeSql
                    AND (expires_at IS NULL OR expires_at > NOW())"
            ) ?: [];
        } catch (\Throwable $_) {
            $rows = [];
        }
        self::$cache[$scope] = $rows;
        return $rows;
    }

    /**
     * Match a request context against blocklist entries for the given scope.
     * Returns the first matching entry (any action), or null if nothing matched.
     *
     * Context keys (all optional): ip, ua, asn, affiliate_id, affiliate_code, device_fp
     */
    public static function match(array $ctx, string $scope = 'all'): ?array {
        $entries = self::entries($scope);
        if (empty($entries)) return null;

        $ip    = trim((string)($ctx['ip']             ?? ''));
        $ua    =       (string)($ctx['ua']             ?? '');
        $asn   =       (string)($ctx['asn']            ?? '');
        $aid   =          (int)($ctx['affiliate_id']   ?? 0);
        $acode =       (string)($ctx['affiliate_code'] ?? '');
        $dfp   =       (string)($ctx['device_fp']      ?? '');

        foreach ($entries as $e) {
            $val = (string)$e['value'];
            $hit = false;
            switch ($e['type']) {
                case 'ip':
                    $hit = ($ip !== '' && $ip === $val);
                    break;
                case 'cidr':
                    $hit = ($ip !== '' && self::ipInCidr($ip, $val));
                    break;
                case 'user_agent':
                    $hit = ($ua !== '' && $val !== '' && stripos($ua, $val) !== false);
                    break;
                case 'asn':
                    $needle = ltrim($val, 'ASas');
                    $hit = ($asn !== '' && $needle !== '' && stripos($asn, $needle) !== false);
                    break;
                case 'affiliate_id':
                    // Match by numeric affiliate ID or by affiliate code (admin can paste either)
                    $hit = (($aid > 0 && (string)$aid === $val)
                         || ($acode !== '' && strcasecmp($acode, $val) === 0));
                    break;
                case 'device_fp':
                    $hit = ($dfp !== '' && $dfp === $val);
                    break;
            }
            if ($hit) return $e;
        }
        return null;
    }

    /** Returns true if the matched entry should hard-block the request. */
    public static function isBlocking(?array $entry): bool {
        return $entry && ($entry['action'] ?? '') === 'block';
    }

    /** Record a blocklist hit (fire-and-forget). */
    public static function recordHit(int $entryId): void {
        if ($entryId <= 0) return;
        try {
            Database::query("UPDATE fraud_blocklist SET hit_count = hit_count + 1 WHERE id = ?", [$entryId]);
        } catch (\Throwable $_) {}
    }

    /** CIDR containment check supporting both IPv4 and IPv6. */
    public static function ipInCidr(string $ip, string $cidr): bool {
        if ($ip === '' || strpos($cidr, '/') === false) return false;
        [$range, $prefix] = explode('/', $cidr, 2);
        $prefix = (int)$prefix;
        $ipBin = @inet_pton($ip);
        $rgBin = @inet_pton($range);
        if ($ipBin === false || $rgBin === false) return false;
        if (strlen($ipBin) !== strlen($rgBin)) return false;
        $bytes = strlen($ipBin);
        $full  = intdiv($prefix, 8);
        $rem   = $prefix % 8;
        for ($i = 0; $i < $full && $i < $bytes; $i++) {
            if ($ipBin[$i] !== $rgBin[$i]) return false;
        }
        if ($rem > 0 && $full < $bytes) {
            $mask = 0xFF & (0xFF << (8 - $rem));
            if ((ord($ipBin[$full]) & $mask) !== (ord($rgBin[$full]) & $mask)) return false;
        }
        return true;
    }

    /**
     * Global request guard — runs once per request, immediately after the
     * session is started. Hard-blocks the request (HTTP 403) if the visitor
     * IP, user-agent, or the affiliate currently signed in matches an active
     * blocklist entry with action=block and scope=all.
     *
     * Skipped for:
     *  - localhost / unknown IPs (would lock everyone out during local dev)
     *  - currently signed-in admin sessions (prevents self-lockout via the UI)
     */
    public static function guard(): void {
        if (self::$guardRan) return;
        self::$guardRan = true;

        $ip = Helpers::getIp();
        if ($ip === '' || $ip === '0.0.0.0' || $ip === '127.0.0.1' || $ip === '::1') return;

        // Self-lockout safety: admins are never blocked by their own list.
        if (($_SESSION['user_role'] ?? '') === 'admin') return;

        $ua    = Helpers::getUserAgent();
        $aid   = (int)($_SESSION['affiliate_id'] ?? 0);
        $acode = '';
        if ($aid > 0) {
            try {
                $r = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$aid]);
                $acode = $r['affiliate_code'] ?? '';
            } catch (\Throwable $_) {}
        }

        $entry = self::match([
            'ip'             => $ip,
            'ua'             => $ua,
            'affiliate_id'   => $aid,
            'affiliate_code' => $acode,
        ], 'all');

        if (self::isBlocking($entry)) {
            self::recordHit((int)$entry['id']);
            // If an authenticated session is being blocked (affiliate now on the
            // list, or their IP is now blocked), kill the session as well — the
            // user must not retain dashboard/API access on the next request.
            if (!empty($_SESSION['user_id'])) {
                @session_destroy();
            }
            self::deny('Access denied.');
        }
    }

    /**
     * Hard-drop the request. Sends HTTP 403 and a minimal body, NEVER a
     * redirect — blocked traffic must not be forwarded to the Traffic Back
     * URL, an offer link, or any fallback destination. Any Location headers
     * queued earlier in the request are cleared before we respond.
     */
    public static function deny(string $message = 'Access denied.'): void {
        if (!headers_sent()) {
            // Strip any Location/refresh headers the caller may have queued —
            // blocked traffic must terminate here, not redirect anywhere.
            @header_remove('Location');
            @header_remove('Refresh');
            http_response_code(403);
        }
        $accept = $_SERVER['HTTP_ACCEPT']           ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $isJson = (stripos($accept, 'application/json') !== false)
               || (strcasecmp($xrw, 'XMLHttpRequest') === 0)
               || (strncmp($_SERVER['REQUEST_URI'] ?? '', '/api/', 5) === 0);
        if ($isJson) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode(['error' => $message, 'code' => 'blocked']);
        } else {
            if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
            echo $message;
        }
        exit;
    }

    /**
     * Check a tracking-click context (scope = clicks). Increments hit_count
     * on a blocking match and HARD-DROPS the request via deny().
     *
     * IMPORTANT: blocked traffic must never be forwarded to the Traffic Back
     * URL, an offer redirect, or any other destination — even if a callback
     * is supplied. The block decision is final at this layer.
     */
    public static function enforceClick(array $ctx): void {
        $entry = self::match($ctx, 'clicks');
        if (!self::isBlocking($entry)) return;
        self::recordHit((int)$entry['id']);
        self::deny('Access denied.');
    }

    /**
     * Check a conversion context (scope = conversions). Increments hit_count
     * on a blocking match. Returns the matched entry so callers can record
     * advertiser-postback rejection metadata before bailing out.
     */
    public static function checkConversion(array $ctx): ?array {
        $entry = self::match($ctx, 'conversions');
        if (!self::isBlocking($entry)) return null;
        self::recordHit((int)$entry['id']);
        return $entry;
    }

    /** Convenience: is the connecting IP blocked at the platform-wide scope? */
    public static function isIpBlocked(string $ip): bool {
        $entry = self::match(['ip' => $ip], 'all');
        return self::isBlocking($entry);
    }
}
