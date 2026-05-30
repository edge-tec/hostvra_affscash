<?php
/**
 * GoogleSearchConsole
 *
 * Service-Account-backed wrapper for:
 *   - Google Search Console v3 API   (sites, sitemaps, searchAnalytics, urlInspection)
 *   - Google Indexing API v3         (URL_UPDATED / URL_DELETED notifications)
 *
 * No external dependencies — pure PHP + cURL + openssl.
 *
 * Reliability features added in the 2026 audit:
 *   - JWT/access-token caching (~3500s) via `seo_settings` to avoid re-running
 *     RS256 + a Google round-trip on every page load
 *   - Last-error capture (`lastError()`) so failures surface a real diagnostic
 *     instead of silent empty strings
 *   - HTTP-code-based success detection (not just absence of an `error` key)
 *   - Per-call activity write to `seo_api_log` (when the table exists)
 *   - searchAnalytics.query()        ← previously missing — clicks / impressions / CTR / position
 *   - inspectUrl() (URL Inspection)  ← previously missing — coverage state, last-crawl
 *
 * The legacy method signatures (`getAccessToken`, `listSites`, `submitSitemap`,
 * `requestIndexing`, …) are preserved so existing call sites keep working.
 */
class GoogleSearchConsole
{
    const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    const GSC_BASE     = 'https://www.googleapis.com/webmasters/v3';
    const SC_BASE      = 'https://searchconsole.googleapis.com/v1';        // for urlInspection.index.inspect
    const INDEXING_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    const SCOPE_GSC      = 'https://www.googleapis.com/auth/webmasters';
    const SCOPE_INDEXING = 'https://www.googleapis.com/auth/indexing';

    /** Last error encountered by any API call. Inspectable via lastError(). */
    private static string $lastError = '';

    /** In-process memo for the current request, keyed by sha256(saEmail + scope). */
    private static array $tokenMemo = [];

    public static function lastError(): string { return self::$lastError; }
    private static function setError(string $msg): void { self::$lastError = $msg; }
    private static function clearError(): void  { self::$lastError = ''; }

    // ─────────────────────────────────────────────────────────────────────────
    // JWT + access-token (with cache)
    // ─────────────────────────────────────────────────────────────────────────

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Exchange service-account credentials for a short-lived access token.
     * Cached for up to ~55 minutes (Google issues 60-minute tokens; we
     * proactively refresh 5 minutes early). Cache lives in seo_settings so
     * it survives across requests.
     */
    public static function getAccessToken(array $sa, string $scope): string
    {
        self::clearError();
        if (empty($sa['private_key']) || empty($sa['client_email'])) {
            self::setError('Service account is missing client_email or private_key.');
            return '';
        }

        $cacheKey = 'gsc_token_' . substr(hash('sha256', ($sa['client_email'] ?? '') . '|' . $scope), 0, 24);

        // 1) In-process memo (fastest)
        if (isset(self::$tokenMemo[$cacheKey]) && self::$tokenMemo[$cacheKey]['exp'] > time()) {
            return self::$tokenMemo[$cacheKey]['token'];
        }

        // 2) Persistent cache (seo_settings table)
        try {
            $row = Database::fetchOne("SELECT setting_value FROM seo_settings WHERE setting_key=?", [$cacheKey]);
            if ($row && !empty($row['setting_value'])) {
                $cached = json_decode((string)$row['setting_value'], true);
                if (is_array($cached) && !empty($cached['token']) && ($cached['exp'] ?? 0) > time()) {
                    self::$tokenMemo[$cacheKey] = $cached;
                    return $cached['token'];
                }
            }
        } catch (\Throwable $_) { /* cache table may not exist yet — fall through */ }

        // 3) Mint a new token
        $now = time();
        $header  = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::b64url(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => $scope,
            'aud'   => self::TOKEN_URL,
            'exp'   => $now + 3600,
            'iat'   => $now,
        ]));
        $input = $header . '.' . $payload;
        if (!openssl_sign($input, $sig, $sa['private_key'], 'SHA256')) {
            self::setError('openssl_sign failed — the private_key is malformed or unreadable.');
            self::log('token', 'POST', self::TOKEN_URL, 0, 'openssl_sign failed');
            return '';
        }
        $jwt = $input . '.' . self::b64url($sig);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw      = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $curlErr !== '') {
            self::setError('cURL error contacting Google OAuth: ' . $curlErr);
            self::log('token', 'POST', self::TOKEN_URL, $httpCode, $curlErr);
            return '';
        }
        $resp = json_decode((string)$raw, true);
        if (!is_array($resp) || empty($resp['access_token'])) {
            $msg = isset($resp['error_description'])
                ? ($resp['error'] ?? 'oauth_error') . ': ' . $resp['error_description']
                : ('Unexpected OAuth response (HTTP ' . $httpCode . ')');
            self::setError($msg);
            self::log('token', 'POST', self::TOKEN_URL, $httpCode, $msg);
            return '';
        }

        $token  = (string)$resp['access_token'];
        $expIn  = (int)($resp['expires_in'] ?? 3600);
        $expAt  = $now + max(60, $expIn - 300);   // refresh 5 minutes early
        $payload = json_encode(['token' => $token, 'exp' => $expAt]);

        // Persist (best-effort) + in-process memo
        try {
            Database::query(
                "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?,?)
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()",
                [$cacheKey, $payload]
            );
        } catch (\Throwable $_) {}
        self::$tokenMemo[$cacheKey] = ['token' => $token, 'exp' => $expAt];
        return $token;
    }

    /** Force-invalidate the cached token (e.g. when admin uploads new SA JSON). */
    public static function flushTokenCache(): void
    {
        self::$tokenMemo = [];
        try { Database::query("DELETE FROM seo_settings WHERE setting_key LIKE 'gsc_token_%'"); } catch (\Throwable $_) {}
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sites
    // ─────────────────────────────────────────────────────────────────────────

    public static function listSites(string $token): array
    {
        $raw = self::request(self::GSC_BASE . '/sites', 'GET', null, $token);
        return $raw['siteEntry'] ?? [];
    }

    /** Returns ['success'=>bool, 'http'=>int, 'response'=>array]. */
    public static function addSite(string $siteUrl, string $token): array
    {
        $result = self::request(self::GSC_BASE . '/sites/' . urlencode($siteUrl), 'PUT', null, $token);
        return self::wrap($result);
    }

    public static function getSite(string $siteUrl, string $token): array
    {
        $result = self::request(self::GSC_BASE . '/sites/' . urlencode($siteUrl), 'GET', null, $token);
        return is_array($result) ? $result : [];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sitemaps
    // ─────────────────────────────────────────────────────────────────────────

    public static function submitSitemap(string $siteUrl, string $sitemapUrl, string $token): array
    {
        $result = self::request(
            self::GSC_BASE . '/sites/' . urlencode($siteUrl) . '/sitemaps/' . urlencode($sitemapUrl),
            'PUT', null, $token
        );
        return self::wrap($result);
    }

    public static function listSitemaps(string $siteUrl, string $token): array
    {
        $result = self::request(self::GSC_BASE . '/sites/' . urlencode($siteUrl) . '/sitemaps', 'GET', null, $token);
        return $result['sitemap'] ?? [];
    }

    public static function deleteSitemap(string $siteUrl, string $sitemapUrl, string $token): bool
    {
        $result = self::request(
            self::GSC_BASE . '/sites/' . urlencode($siteUrl) . '/sitemaps/' . urlencode($sitemapUrl),
            'DELETE', null, $token
        );
        $http = (int)($result['_http_code'] ?? 0);
        return $http >= 200 && $http < 300;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Search Analytics  (clicks / impressions / CTR / position by query|page|country|device|date)
    // Endpoint: POST /sites/{siteUrl}/searchAnalytics/query
    // See https://developers.google.com/webmaster-tools/v1/searchanalytics/query
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Query search analytics.
     *
     * @param string $siteUrl    Property URL exactly as it appears in Search Console.
     * @param string $token      OAuth access token (webmasters scope).
     * @param array  $opts       startDate, endDate, dimensions, rowLimit, startRow, searchType
     * @return array             ['success'=>bool, 'http'=>int, 'rows'=>[…], 'error'=>?string]
     */
    public static function searchAnalytics(string $siteUrl, string $token, array $opts = []): array
    {
        $body = [
            'startDate'  => $opts['startDate']  ?? date('Y-m-d', strtotime('-29 days')),
            'endDate'    => $opts['endDate']    ?? date('Y-m-d', strtotime('-1 day')),
            'dimensions' => $opts['dimensions'] ?? ['query'],
            'rowLimit'   => min(25000, (int)($opts['rowLimit'] ?? 100)),
            'startRow'   => max(0, (int)($opts['startRow'] ?? 0)),
        ];
        if (!empty($opts['searchType'])) $body['type'] = $opts['searchType'];   // 'web' | 'image' | 'video' | 'news' | 'discover' | 'googleNews'
        if (!empty($opts['filters']))    $body['dimensionFilterGroups'] = $opts['filters'];

        $url    = self::GSC_BASE . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query';
        $result = self::request($url, 'POST', $body, $token);

        $http = (int)($result['_http_code'] ?? 0);
        if ($http >= 200 && $http < 300 && isset($result['rows'])) {
            return ['success' => true, 'http' => $http, 'rows' => $result['rows'], 'response' => $result];
        }
        if ($http >= 200 && $http < 300) {
            return ['success' => true, 'http' => $http, 'rows' => [], 'response' => $result];
        }
        return [
            'success'  => false,
            'http'     => $http,
            'rows'     => [],
            'error'    => $result['error']['message'] ?? 'Unknown error (HTTP ' . $http . ')',
            'response' => $result,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // URL Inspection — indexing status, last crawl, coverage state
    // Endpoint: POST /urlInspection/index:inspect  (searchconsole.googleapis.com/v1)
    // ─────────────────────────────────────────────────────────────────────────

    public static function inspectUrl(string $siteUrl, string $targetUrl, string $token, string $languageCode = 'en-US'): array
    {
        $url    = self::SC_BASE . '/urlInspection/index:inspect';
        $body   = ['inspectionUrl' => $targetUrl, 'siteUrl' => $siteUrl, 'languageCode' => $languageCode];
        $result = self::request($url, 'POST', $body, $token);
        $http   = (int)($result['_http_code'] ?? 0);
        if ($http >= 200 && $http < 300 && isset($result['inspectionResult'])) {
            return ['success' => true, 'http' => $http, 'result' => $result['inspectionResult']];
        }
        return [
            'success' => false,
            'http'    => $http,
            'error'   => $result['error']['message'] ?? 'Inspection failed (HTTP ' . $http . ')',
            'response' => $result,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Google Indexing API
    // ─────────────────────────────────────────────────────────────────────────

    public static function requestIndexing(string $url, string $token, string $type = 'URL_UPDATED'): array
    {
        $result = self::request(
            self::INDEXING_URL, 'POST',
            ['url' => $url, 'type' => $type],
            $token, 'application/json'
        );
        $http = (int)($result['_http_code'] ?? 0);
        $ok   = $http >= 200 && $http < 300 && isset($result['urlNotificationMetadata']);
        return [
            'success'  => $ok,
            'http'     => $http,
            'error'    => $ok ? null : ($result['error']['message'] ?? ('HTTP ' . $http)),
            'response' => $result,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sitemap XML helpers
    // ─────────────────────────────────────────────────────────────────────────

    public static function buildSitemapXml(array $urls): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $entry) {
            $loc        = htmlspecialchars($entry['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod    = $entry['lastmod']    ?? date('Y-m-d');
            $changefreq = $entry['changefreq'] ?? 'weekly';
            $priority   = $entry['priority']   ?? '0.5';
            $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>{$changefreq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    public static function buildRobotsTxt(array $cfg): string
    {
        $sitemapUrl = rtrim($cfg['site_url'] ?? '', '/') . '/sitemap.xml';
        $lines = ['User-agent: *'];
        if (!empty($cfg['robots_disallow'])) {
            foreach (array_filter(array_map('trim', explode("\n", $cfg['robots_disallow']))) as $p) {
                $lines[] = 'Disallow: ' . $p;
            }
        } else {
            $lines[] = !($cfg['robots_index'] ?? true) ? 'Disallow: /' : 'Disallow:';
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . $sitemapUrl;
        return implode("\n", $lines);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal: HTTP transport with error capture
    // ─────────────────────────────────────────────────────────────────────────

    private static function request(
        string  $url,
        string  $method,
        ?array  $body,
        string  $token,
        string  $contentType = 'application/json'
    ): array {
        self::clearError();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: ' . $contentType,
            'Accept: application/json',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                $contentType === 'application/json' ? json_encode($body) : http_build_query($body)
            );
        }
        $raw      = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $http     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $curlErr !== '') {
            self::setError('cURL error: ' . $curlErr);
            self::log('request', $method, $url, $http, $curlErr);
            return ['_http_code' => $http, '_curl_error' => $curlErr];
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            $decoded = ['_raw' => (string)$raw];
        }
        $decoded['_http_code'] = $http;

        // Surface error message as lastError so callers don't have to dig.
        if (isset($decoded['error']['message'])) {
            self::setError('Google API error (HTTP ' . $http . '): ' . $decoded['error']['message']);
            self::log('request', $method, $url, $http, $decoded['error']['message']);
        } elseif ($http >= 400) {
            self::setError('HTTP ' . $http . ' from Google');
            self::log('request', $method, $url, $http, 'HTTP ' . $http);
        } else {
            self::log('request', $method, $url, $http, null);
        }
        return $decoded;
    }

    /** Standard return shape for write-style endpoints (addSite/submitSitemap). */
    private static function wrap(array $result): array
    {
        $http = (int)($result['_http_code'] ?? 0);
        return [
            'success'  => $http >= 200 && $http < 300 && !isset($result['error']),
            'http'     => $http,
            'error'    => $result['error']['message'] ?? null,
            'response' => $result,
        ];
    }

    /**
     * Best-effort write to seo_api_log. Never throws — the log table may not
     * exist on older installs. If the row commits, admins can see API
     * history in the Insights tab.
     */
    private static function log(string $op, string $method, string $url, int $http, ?string $err): void
    {
        try {
            Database::query(
                "INSERT INTO seo_api_log (op, method, url, http_code, error_message)
                 VALUES (?,?,?,?,?)",
                [$op, $method, $url, $http, $err]
            );
        } catch (\Throwable $_) { /* table may not exist yet — silently skip */ }
    }
}
