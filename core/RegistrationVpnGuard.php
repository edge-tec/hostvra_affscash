<?php
/**
 * RegistrationVpnGuard — VPN/Proxy/TOR/Datacenter IP blocking for registration pages.
 *
 * This class is ONLY used by the Affiliate and Advertiser registration controllers.
 * It does NOT affect any other part of the system (login, tracking, admin, etc.).
 *
 * Detection layers (all free, no API keys required):
 *  1. ip-api.com   — proxy + hosting flags  (same API already used by Helpers::getGeoInfo)
 *  2. IPQuery.io   — vpn, proxy, tor, datacenter flags  (same API used by FraudIQ::checkIPQuery)
 *  3. Header heuristics — Via, Proxy-Connection, forwarded-chain analysis
 *
 * Results are cached in $_SESSION for 10 minutes to avoid redundant API calls
 * on page reloads / form validation failures.
 *
 * Fail-open: if all API calls fail, users are allowed through so legitimate
 * registrations are never blocked by an API outage.
 */
class RegistrationVpnGuard
{
    /** Cache duration in seconds (10 minutes). */
    private const CACHE_TTL = 600;

    /** Session key for cached results. */
    private const CACHE_KEY = '_vpn_guard_cache';

    /**
     * Check whether an IP address is using VPN/Proxy/TOR/Datacenter.
     *
     * @param  string $ip  The visitor's IP address (use Helpers::getIp())
     * @return array{blocked: bool, reason: string, details: array}
     *   - blocked:  true if the IP should be denied registration access
     *   - reason:   human-readable reason string (empty if not blocked)
     *   - details:  raw detection flags for logging/debugging
     */
    public static function checkIp(string $ip): array
    {
        // Localhost / private IPs — never block (dev environments)
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0' || $ip === '') {
            return ['blocked' => false, 'reason' => '', 'details' => ['source' => 'localhost_skip']];
        }

        // Check session cache first
        $cached = self::getCache($ip);
        if ($cached !== null) {
            return $cached;
        }

        // Run detection layers
        $flags = [
            'is_vpn'        => false,
            'is_proxy'      => false,
            'is_tor'        => false,
            'is_datacenter' => false,
            'is_hosting'    => false,
            'sources'       => [],
        ];

        // ── Layer 1: ip-api.com ──────────────────────────────────────────────
        try {
            $ipApiResult = self::checkIpApi($ip);
            if ($ipApiResult !== null) {
                $flags['sources'][] = 'ip-api';
                if ($ipApiResult['proxy'])   { $flags['is_proxy'] = true; }
                if ($ipApiResult['hosting']) { $flags['is_hosting'] = true; $flags['is_datacenter'] = true; }
            }
        } catch (\Throwable $e) {
            // Fail-open: silently continue
        }

        // ── Layer 2: IPQuery.io ──────────────────────────────────────────────
        try {
            $ipQueryResult = self::checkIpQuery($ip);
            if ($ipQueryResult !== null) {
                $flags['sources'][] = 'ipquery';
                if ($ipQueryResult['is_vpn'])        { $flags['is_vpn'] = true; }
                if ($ipQueryResult['is_proxy'])       { $flags['is_proxy'] = true; }
                if ($ipQueryResult['is_tor'])         { $flags['is_tor'] = true; }
                if ($ipQueryResult['is_datacenter'])  { $flags['is_datacenter'] = true; }
            }
        } catch (\Throwable $e) {
            // Fail-open: silently continue
        }

        // ── Layer 3: Header heuristics ───────────────────────────────────────
        try {
            $headerFlags = self::checkHeaders();
            if ($headerFlags['suspicious']) {
                $flags['sources'][] = 'headers';
                if ($headerFlags['is_proxy']) { $flags['is_proxy'] = true; }
                if ($headerFlags['is_tor'])   { $flags['is_tor'] = true; }
            }
        } catch (\Throwable $e) {
            // Fail-open: silently continue
        }

        // ── Decision ─────────────────────────────────────────────────────────
        $blocked = false;
        $reason  = '';

        if ($flags['is_tor']) {
            $blocked = true;
            $reason  = 'TOR network detected';
        } elseif ($flags['is_vpn']) {
            $blocked = true;
            $reason  = 'VPN detected';
        } elseif ($flags['is_proxy']) {
            $blocked = true;
            $reason  = 'Proxy detected';
        } elseif ($flags['is_datacenter'] || $flags['is_hosting']) {
            $blocked = true;
            $reason  = 'Datacenter/hosting IP detected';
        }

        $result = [
            'blocked' => $blocked,
            'reason'  => $reason,
            'details' => $flags,
        ];

        // Cache the result
        self::setCache($ip, $result);

        return $result;
    }

    /**
     * Convenience: returns true if the IP is blocked.
     */
    public static function isBlocked(string $ip): bool
    {
        return self::checkIp($ip)['blocked'];
    }

    // ── Detection Layer 1: ip-api.com ────────────────────────────────────────

    /**
     * Query ip-api.com for proxy/hosting flags.
     * Returns null on failure (fail-open).
     */
    private static function checkIpApi(string $ip): ?array
    {
        $url = "http://ip-api.com/json/{$ip}?fields=status,proxy,hosting";
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
        $json = @file_get_contents($url, false, $ctx);

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);
        if (!$data || ($data['status'] ?? '') !== 'success') {
            return null;
        }

        return [
            'proxy'   => (bool)($data['proxy']   ?? false),
            'hosting' => (bool)($data['hosting'] ?? false),
        ];
    }

    // ── Detection Layer 2: IPQuery.io ────────────────────────────────────────

    /**
     * Query IPQuery.io for VPN/proxy/TOR/datacenter flags.
     * Returns null on failure (fail-open).
     */
    private static function checkIpQuery(string $ip): ?array
    {
        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') {
            return null;
        }

        $url = 'https://api.ipquery.io/' . $safeIp;

        // Use cURL for HTTPS (stream wrappers may not have SSL on all hosts)
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        // IPQuery.io nests risk flags under a "risk" key
        $risk = (isset($data['risk']) && is_array($data['risk'])) ? $data['risk'] : $data;

        return [
            'is_vpn'        => (bool)($risk['is_vpn']        ?? $risk['vpn']        ?? false),
            'is_proxy'      => (bool)($risk['is_proxy']      ?? $risk['proxy']      ?? false),
            'is_tor'        => (bool)($risk['is_tor']        ?? $risk['tor']        ?? false),
            'is_datacenter' => (bool)($risk['is_datacenter'] ?? $risk['datacenter'] ?? false),
        ];
    }

    // ── Detection Layer 3: Header heuristics ─────────────────────────────────

    /**
     * Inspect HTTP headers for signs of proxy/TOR usage.
     * Pure server-side — no external calls.
     */
    private static function checkHeaders(): array
    {
        $result = ['suspicious' => false, 'is_proxy' => false, 'is_tor' => false];

        // Known proxy-related headers
        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_PROXY_CONNECTION',
            'HTTP_X_PROXY_ID',
            'HTTP_FORWARDED',
            'HTTP_X_FORWARDED_HOST',
        ];

        foreach ($proxyHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $result['suspicious'] = true;
                $result['is_proxy']   = true;
                break;
            }
        }

        // X-Forwarded-For with multiple IPs suggests proxy chain
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $hops = array_filter(array_map('trim', explode(',', $xff)));
            if (count($hops) >= 3) {
                $result['suspicious'] = true;
                $result['is_proxy']   = true;
            }
        }

        return $result;
    }

    // ── Session caching ──────────────────────────────────────────────────────

    /**
     * Retrieve cached result for the given IP, or null if expired/missing.
     */
    private static function getCache(string $ip): ?array
    {
        if (!isset($_SESSION)) {
            @session_start();
        }

        $cache = $_SESSION[self::CACHE_KEY] ?? null;
        if (!is_array($cache)) {
            return null;
        }

        // Check IP match and TTL
        if (($cache['ip'] ?? '') === $ip && ($cache['expires'] ?? 0) > time()) {
            return $cache['result'];
        }

        // Expired or different IP — clear
        unset($_SESSION[self::CACHE_KEY]);
        return null;
    }

    /**
     * Store a check result in the session cache.
     */
    private static function setCache(string $ip, array $result): void
    {
        if (!isset($_SESSION)) {
            @session_start();
        }

        $_SESSION[self::CACHE_KEY] = [
            'ip'      => $ip,
            'result'  => $result,
            'expires' => time() + self::CACHE_TTL,
        ];
    }
}
