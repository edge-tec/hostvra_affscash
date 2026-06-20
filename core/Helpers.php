<?php
/**
 * Helpers - Utility functions
 */
class Helpers {

    public static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function generateCode(string $prefix = '', int $length = 8): string {
        return strtoupper($prefix . bin2hex(random_bytes($length)));
    }

    public static function sanitize(string $value): string {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function getIp(): string {
        // Public-IP flags — skip private/loopback/reserved ranges in proxy headers.
        // These are only applied to headers that can be spoofed (XFF, Client-IP).
        // REMOTE_ADDR is trusted directly from the TCP connection.
        $pubFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        // Priority order (first match wins):
        //  1. CF-Connecting-IP  — Cloudflare replaces XFF with the real visitor IP
        //  2. X-Real-IP         — nginx proxy_set_header X-Real-IP $remote_addr
        //  3. X-Forwarded-For   — comma-separated chain; scan for first public IP
        //  4. HTTP_CLIENT_IP    — some load balancers
        //  5. REMOTE_ADDR       — direct TCP connection (always the last hop)

        // Headers that carry a single IP value (not a comma list):
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'] as $key) {
            $raw = trim($_SERVER[$key] ?? '');
            if ($raw === '') continue;
            if (filter_var($raw, FILTER_VALIDATE_IP, $pubFlags) !== false) {
                return $raw;
            }
        }

        // X-Forwarded-For can be "clientIP, proxy1, proxy2" — take first public IP.
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            foreach (explode(',', $xff) as $part) {
                $ip = trim($part);
                if ($ip === '') continue;
                if (filter_var($ip, FILTER_VALIDATE_IP, $pubFlags) !== false) {
                    return $ip;
                }
            }
        }

        // REMOTE_ADDR — direct TCP connection IP (could be a proxy or load balancer).
        // Accept any valid IP here including private ranges (on some setups this IS the
        // real visitor IP when behind an internal load balancer without proper headers).
        $ra = trim($_SERVER['REMOTE_ADDR'] ?? '');
        if ($ra !== '' && filter_var($ra, FILTER_VALIDATE_IP) !== false) {
            return $ra;
        }

        return '0.0.0.0';
    }

    public static function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function getReferer(): string {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    public static function parseDevice(string $ua): array {
        // Backwards-compatible: returns device/os/browser (no versions). Internally
        // delegates to parseDeviceDetailed() so we have one source of truth.
        $d = self::parseDeviceDetailed($ua);
        return [
            'device'  => $d['device'],
            'os'      => $d['os'],
            'browser' => $d['browser'],
        ];
    }

    /**
     * Extended UA parsing for the Advertiser Reporting System. Returns the
     * same fields as parseDevice() plus os_version, browser_version,
     * device_brand and device_model. Pure regex — no external libraries.
     */
    public static function parseDeviceDetailed(string $uaRaw): array {
        $ua  = strtolower($uaRaw);
        $out = [
            'device'          => 'desktop',
            'os'              => 'Unknown',
            'os_version'      => '',
            'browser'         => 'Unknown',
            'browser_version' => '',
            'device_brand'    => '',
            'device_model'    => '',
        ];

        // Device class
        if (preg_match('/bot|crawler|spider|slurp|bingbot|googlebot/i', $ua)) {
            $out['device'] = 'bot';
        } elseif (preg_match('/tablet|ipad/i', $ua)) {
            $out['device'] = 'tablet';
        } elseif (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) {
            $out['device'] = 'mobile';
        }

        // OS + OS version
        if (preg_match('/windows nt ([\d.]+)/i', $ua, $m)) {
            $out['os'] = 'Windows';
            $map = ['10.0'=>'10','6.3'=>'8.1','6.2'=>'8','6.1'=>'7','6.0'=>'Vista','5.1'=>'XP'];
            $out['os_version'] = $map[$m[1]] ?? $m[1];
        } elseif (preg_match('/(iphone|ipad|ipod).+?os ([\d_]+)/i', $ua, $m)) {
            $out['os'] = 'iOS';
            $out['os_version'] = str_replace('_', '.', $m[2]);
        } elseif (preg_match('/android ([\d.]+)/i', $ua, $m)) {
            $out['os'] = 'Android';
            $out['os_version'] = $m[1];
        } elseif (preg_match('/mac os x ([\d_]+)/i', $ua, $m)) {
            $out['os'] = 'MacOS';
            $out['os_version'] = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/macintosh|mac os/i', $ua)) {
            $out['os'] = 'MacOS';
        } elseif (preg_match('/cros [\w]+ ([\d.]+)/i', $ua, $m)) {
            $out['os'] = 'ChromeOS';
            $out['os_version'] = $m[1];
        } elseif (preg_match('/linux/i', $ua)) {
            $out['os'] = 'Linux';
        }

        // Browser + browser version (order matters — most specific UAs first)
        if (preg_match('/edg(?:e|a|ios)?\/([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'Edge';        $out['browser_version'] = $m[1];
        } elseif (preg_match('/opr\/([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'Opera';       $out['browser_version'] = $m[1];
        } elseif (preg_match('/samsungbrowser\/([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'Samsung Internet'; $out['browser_version'] = $m[1];
        } elseif (preg_match('/ucbrowser\/([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'UC Browser';  $out['browser_version'] = $m[1];
        } elseif (preg_match('/firefox\/([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'Firefox';     $out['browser_version'] = $m[1];
        } elseif (preg_match('/chrome\/([\d.]+)/i', $ua, $m) && !preg_match('/edge|opr/i', $ua)) {
            $out['browser'] = 'Chrome';      $out['browser_version'] = $m[1];
        } elseif (preg_match('/version\/([\d.]+).*safari/i', $ua, $m)) {
            $out['browser'] = 'Safari';      $out['browser_version'] = $m[1];
        } elseif (preg_match('/safari\/([\d.]+)/i', $ua, $m) && !preg_match('/chrome/i', $ua)) {
            $out['browser'] = 'Safari';      $out['browser_version'] = $m[1];
        } elseif (preg_match('/msie ([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'IE';          $out['browser_version'] = $m[1];
        } elseif (preg_match('/trident.*rv:([\d.]+)/i', $ua, $m)) {
            $out['browser'] = 'IE';          $out['browser_version'] = $m[1];
        }

        // Device brand + model (best-effort — only common patterns)
        if (preg_match('/iphone/i', $ua))      { $out['device_brand'] = 'Apple';   $out['device_model'] = 'iPhone'; }
        elseif (preg_match('/ipad/i', $ua))    { $out['device_brand'] = 'Apple';   $out['device_model'] = 'iPad'; }
        elseif (preg_match('/ipod/i', $ua))    { $out['device_brand'] = 'Apple';   $out['device_model'] = 'iPod'; }
        elseif (preg_match('/macintosh|mac os/i', $ua)) { $out['device_brand'] = 'Apple'; $out['device_model'] = 'Mac'; }
        elseif (preg_match('/; ([\w\- ]+) build\//i', $uaRaw, $m)) {
            // Android: e.g. "(Linux; Android 12; Pixel 6 Build/SD1A.210817.036)"
            $model = trim($m[1]);
            $out['device_model'] = $model;
            $first = strtolower(strtok($model, ' '));
            $brandMap = ['samsung'=>'Samsung','sm-'=>'Samsung','gt-'=>'Samsung','pixel'=>'Google',
                         'mi'=>'Xiaomi','redmi'=>'Xiaomi','poco'=>'Xiaomi','huawei'=>'Huawei',
                         'honor'=>'Honor','oneplus'=>'OnePlus','realme'=>'Realme','oppo'=>'Oppo',
                         'vivo'=>'Vivo','nokia'=>'Nokia','sony'=>'Sony','lenovo'=>'Lenovo','lg-'=>'LG','motorola'=>'Motorola'];
            foreach ($brandMap as $needle => $brand) {
                if (strpos(strtolower($model), $needle) !== false) { $out['device_brand'] = $brand; break; }
            }
            if ($out['device_brand'] === '') $out['device_brand'] = ucfirst($first ?: 'Unknown');
        } elseif ($out['device'] === 'desktop') {
            // Best-effort desktop brand label
            if ($out['os'] === 'Windows')  $out['device_brand'] = 'PC';
            elseif ($out['os'] === 'Linux') $out['device_brand'] = 'Linux';
        }

        return $out;
    }

    /**
     * One-time schema migration flag — prevents ALTER TABLE on every request.
     */
    private static bool $geoSchemaReady = false;

    /**
     * In-process cache — prevents duplicate API calls within the same PHP request
     * (e.g. click.php calls getGeoInfo and then Activity::geoLookup for the same IP).
     */
    private static array $geoMemCache = [];

    /**
     * Rate-limit tracker file path for ip-api.com (45 req/min free tier).
     */
    private static function geoRateLimitFile(): string {
        return sys_get_temp_dir() . '/affscash_ipapi_ratelimit.json';
    }

    /**
     * Check if ip-api.com rate limit allows another request.
     * Returns true if we can proceed, false if we should skip to fallback.
     */
    private static function ipApiCanRequest(): bool {
        $file = self::geoRateLimitFile();
        $now = time();
        $windowStart = $now - 60; // 60-second sliding window
        $maxRequests = 40; // stay under the 45/min hard limit

        try {
            $timestamps = [];
            if (file_exists($file)) {
                $raw = @file_get_contents($file);
                if ($raw !== false) {
                    $timestamps = json_decode($raw, true) ?: [];
                }
            }
            // Prune timestamps older than 60 seconds
            $timestamps = array_values(array_filter($timestamps, fn($t) => $t > $windowStart));
            return count($timestamps) < $maxRequests;
        } catch (\Throwable $e) {
            return true; // on error, allow the request
        }
    }

    /**
     * Record that we made one ip-api.com request.
     */
    private static function ipApiRecordRequest(): void {
        $file = self::geoRateLimitFile();
        $now = time();
        $windowStart = $now - 60;

        try {
            $timestamps = [];
            if (file_exists($file)) {
                $raw = @file_get_contents($file);
                if ($raw !== false) {
                    $timestamps = json_decode($raw, true) ?: [];
                }
            }
            $timestamps = array_values(array_filter($timestamps, fn($t) => $t > $windowStart));
            $timestamps[] = $now;
            @file_put_contents($file, json_encode($timestamps), LOCK_EX);
        } catch (\Throwable $e) {}
    }

    /**
     * Robust HTTP fetcher using cURL with proper timeout, redirect handling,
     * and HTTP status code extraction. Falls back to file_get_contents.
     *
     * @return array{body: string|false, http_code: int}
     */
    private static function geoHttpGet(string $url, int $timeout = 4, array $headers = []): array {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 2,
                CURLOPT_USERAGENT      => 'AffsCash/2.0',
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            ]);
            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $httpCode === 0) {
                error_log("GeoIP cURL error for $url: $err (HTTP $httpCode)");
                return ['body' => false, 'http_code' => $httpCode];
            }
            return ['body' => $body, 'http_code' => $httpCode];
        }

        // Fallback to file_get_contents
        $ctx = stream_context_create(['http' => [
            'timeout'       => $timeout,
            'ignore_errors' => true,
            'header'        => implode("\r\n", array_merge(["User-Agent: AffsCash/2.0"], $headers)) . "\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $h) {
                if (preg_match('/^HTTP\/[\d.]+ (\d{3})/', $h, $m)) {
                    $httpCode = (int)$m[1];
                }
            }
        }
        return ['body' => $body, 'http_code' => $httpCode];
    }

    /**
     * Ensure ip_geo_cache schema has all required columns.
     * Runs ALTER TABLE only once per PHP process.
     */
    private static function ensureGeoCacheSchema(): void {
        if (self::$geoSchemaReady) return;
        self::$geoSchemaReady = true;

        // Create table if missing
        try {
            Database::query("CREATE TABLE IF NOT EXISTS ip_geo_cache (
                ip_address   VARCHAR(45) PRIMARY KEY,
                country_code VARCHAR(5)   DEFAULT NULL,
                country      VARCHAR(100) DEFAULT NULL,
                region       VARCHAR(100) DEFAULT NULL,
                city         VARCHAR(100) DEFAULT NULL,
                isp          VARCHAR(255) DEFAULT NULL,
                proxy        TINYINT(1)   DEFAULT 0,
                hosting      TINYINT(1)   DEFAULT 0,
                lookup_ok    TINYINT(1)   DEFAULT 1,
                cached_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_gc_cached (cached_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        // Idempotent column additions for existing tables
        $cols = [
            'isp'       => "ADD COLUMN `isp` VARCHAR(255) DEFAULT NULL",
            'proxy'     => "ADD COLUMN `proxy` TINYINT(1) DEFAULT 0",
            'hosting'   => "ADD COLUMN `hosting` TINYINT(1) DEFAULT 0",
            'lookup_ok' => "ADD COLUMN `lookup_ok` TINYINT(1) DEFAULT 1",
            'region'    => "ADD COLUMN `region` VARCHAR(100) DEFAULT NULL",
        ];
        foreach ($cols as $col => $ddl) {
            try { Database::query("ALTER TABLE `ip_geo_cache` $ddl"); } catch (\Throwable $_) {}
        }

        // One-time purge: delete legacy poison entries where country_code is blank
        // but lookup_ok defaulted to 1 (success). These were cached with 7-day TTL
        // before the fix, preventing any re-lookup attempt.
        try {
            Database::query(
                "DELETE FROM ip_geo_cache WHERE (country_code IS NULL OR country_code = '') AND cached_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)"
            );
        } catch (\Throwable $_) {}
    }

    public static function getGeoInfo(string $ip): array {
        $default = ['country' => '', 'region' => '', 'city' => '', 'isp' => '', 'proxy' => false, 'hosting' => false];

        // ── 1. Input Validation ─────────────────────────────────────────────────
        // Trim whitespace and null bytes that could silently break lookups
        $ip = trim($ip, " \t\n\r\0\x0B");

        // Skip local/loopback/private/invalid IPs
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || $ip === '0.0.0.0') return $default;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            error_log("GeoIP INVALID IP rejected (pre-filter): [$ip]");
            return $default;
        }
        // Reject private/reserved ranges — they'll never resolve externally
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $default;
        }

        // ── 2. IPv6 Normalisation ───────────────────────────────────────────────
        $isIpv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        if ($isIpv6) {
            // Convert IPv4-mapped IPv6 (::ffff:x.x.x.x) to plain IPv4
            if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $m)) {
                $ip = $m[1];
                $isIpv6 = false;
            } else {
                // Expand compressed IPv6 to full canonical form
                $expanded = inet_ntop(inet_pton($ip));
                if ($expanded !== false) {
                    $ip = $expanded;
                }
            }
        }

        // ── 3. In-Process Memory Cache ──────────────────────────────────────────
        if (isset(self::$geoMemCache[$ip])) {
            return self::$geoMemCache[$ip];
        }

        // ── 4. Schema Migration (once per process) ──────────────────────────────
        self::ensureGeoCacheSchema();

        // ── 5. DB Cache Lookup ──────────────────────────────────────────────────
        //   - Successful lookups (lookup_ok=1 AND country_code non-empty): 7-day TTL
        //   - Failed lookups (lookup_ok=0 OR blank country_code): 2-minute TTL
        //   - Legacy poison entries (lookup_ok=1 but country_code blank): treated as failed
        try {
            $cached = Database::fetchOne(
                "SELECT country_code AS country, COALESCE(region,'') AS region,
                        COALESCE(city,'') AS city, COALESCE(isp,'') AS isp,
                        COALESCE(proxy,0) AS proxy, COALESCE(hosting,0) AS hosting,
                        COALESCE(lookup_ok,1) AS lookup_ok
                 FROM ip_geo_cache
                 WHERE ip_address = ?
                   AND (
                       (COALESCE(lookup_ok,1) = 1 AND COALESCE(country_code,'') != '' AND cached_at > DATE_SUB(NOW(), INTERVAL 7 DAY))
                       OR
                       (cached_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE))
                   )",
                [$ip]
            );
            if ($cached) {
                // If it's a cached result with empty country, treat as miss (expired)
                if (empty($cached['country'])) {
                    // Don't return this — fall through to API lookup below
                } else {
                    $out = [
                        'country' => $cached['country'] ?? '',
                        'region'  => $cached['region'] ?? '',
                        'city'    => $cached['city'] ?? '',
                        'isp'     => $cached['isp'] ?? '',
                        'proxy'   => (bool)($cached['proxy'] ?? false),
                        'hosting' => (bool)($cached['hosting'] ?? false),
                    ];
                    self::$geoMemCache[$ip] = $out;
                    return $out;
                }
            }
        } catch (\Throwable $e) {
            error_log("GeoIP cache read error for IP $ip: " . $e->getMessage());
        }

        // ── 6. API Lookups with Rate Limiting ───────────────────────────────────
        $result  = $default;
        $success = false;

        // --- Provider 1: ip-api.com (free tier: 45 req/min) ---
        if (!$success && self::ipApiCanRequest()) {
            try {
                self::ipApiRecordRequest();
                $url = "http://ip-api.com/json/" . urlencode($ip)
                     . "?fields=status,message,country,countryCode,regionName,city,isp,proxy,hosting";
                $resp = self::geoHttpGet($url, 4);

                if ($resp['http_code'] === 429) {
                    // Rate limited — skip to fallback, do NOT cache this failure
                    error_log("GeoIP [ip-api.com] RATE LIMITED (HTTP 429) for IP $ip — skipping to fallback");
                } elseif ($resp['body'] !== false && $resp['http_code'] === 200) {
                    $data = json_decode($resp['body'], true);
                    if ($data && ($data['status'] ?? '') === 'success') {
                        $result = [
                            'country' => $data['countryCode']  ?? '',
                            'region'  => $data['regionName']   ?? '',
                            'city'    => $data['city']         ?? '',
                            'isp'     => $data['isp']          ?? '',
                            'proxy'   => (bool)($data['proxy']   ?? false),
                            'hosting' => (bool)($data['hosting'] ?? false),
                        ];
                        $success = true;
                    } else {
                        error_log("GeoIP [ip-api.com] API error for IP $ip: " . ($data['message'] ?? json_encode($data)));
                    }
                } else {
                    error_log("GeoIP [ip-api.com] HTTP {$resp['http_code']} for IP $ip");
                }
            } catch (\Throwable $e) {
                error_log("GeoIP [ip-api.com] exception for IP $ip: " . $e->getMessage());
            }
        }

        // --- Provider 2: ipwho.is (no hard rate limit, supports IPv4 + IPv6) ---
        if (!$success) {
            try {
                $resp2 = self::geoHttpGet("https://ipwho.is/" . urlencode($ip), 5);

                if ($resp2['http_code'] === 429) {
                    error_log("GeoIP [ipwho.is] RATE LIMITED (HTTP 429) for IP $ip");
                } elseif ($resp2['body'] !== false && $resp2['http_code'] === 200) {
                    $data2 = json_decode($resp2['body'], true);
                    if ($data2 && ($data2['success'] ?? false) === true) {
                        $result = [
                            'country' => $data2['country_code'] ?? '',
                            'region'  => $data2['region']       ?? '',
                            'city'    => $data2['city']         ?? '',
                            'isp'     => $data2['connection']['isp'] ?? '',
                            'proxy'   => false,
                            'hosting' => false,
                        ];
                        $success = true;
                    } else {
                        error_log("GeoIP [ipwho.is] API error for IP $ip: " . json_encode($data2));
                    }
                } else {
                    error_log("GeoIP [ipwho.is] HTTP {$resp2['http_code']} for IP $ip");
                }
            } catch (\Throwable $e) {
                error_log("GeoIP [ipwho.is] exception for IP $ip: " . $e->getMessage());
            }
        }

        // --- Provider 3: ipapi.co (1000/day free tier) ---
        if (!$success) {
            try {
                $resp3 = self::geoHttpGet(
                    "https://ipapi.co/" . urlencode($ip) . "/json/",
                    5
                );

                if ($resp3['http_code'] === 429) {
                    error_log("GeoIP [ipapi.co] RATE LIMITED (HTTP 429) for IP $ip");
                } elseif ($resp3['body'] !== false && $resp3['http_code'] === 200) {
                    $data3 = json_decode($resp3['body'], true);
                    if ($data3 && !isset($data3['error'])) {
                        $result = [
                            'country' => $data3['country_code'] ?? '',
                            'region'  => $data3['region']       ?? '',
                            'city'    => $data3['city']         ?? '',
                            'isp'     => $data3['org']          ?? '',
                            'proxy'   => false,
                            'hosting' => false,
                        ];
                        $success = true;
                    } else {
                        error_log("GeoIP [ipapi.co] API error for IP $ip: " . json_encode($data3));
                    }
                } else {
                    error_log("GeoIP [ipapi.co] HTTP {$resp3['http_code']} for IP $ip");
                }
            } catch (\Throwable $e) {
                error_log("GeoIP [ipapi.co] exception for IP $ip: " . $e->getMessage());
            }
        }

        // ── 7. Persist to DB Cache ──────────────────────────────────────────────
        //   - Successful lookups → lookup_ok=1, cached for 7 days
        //   - Failed lookups    → lookup_ok=0, cached for 2 minutes only (auto-retry)
        try {
            Database::query(
                "INSERT INTO ip_geo_cache
                    (ip_address, country_code, region, city, isp, proxy, hosting, lookup_ok, cached_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                    country_code = VALUES(country_code),
                    region       = VALUES(region),
                    city         = VALUES(city),
                    isp          = VALUES(isp),
                    proxy        = VALUES(proxy),
                    hosting      = VALUES(hosting),
                    lookup_ok    = VALUES(lookup_ok),
                    cached_at    = NOW()",
                [
                    $ip,
                    $result['country'],
                    $result['region'],
                    $result['city'],
                    $result['isp'],
                    (int)$result['proxy'],
                    (int)$result['hosting'],
                    $success ? 1 : 0,
                ]
            );
        } catch (\Throwable $e) {
            error_log("GeoIP cache write failed for IP $ip: " . $e->getMessage());
        }

        if (!$success) {
            error_log("GeoIP ALL PROVIDERS FAILED for IP $ip — will retry in 2 minutes");
        }

        // Store in memory for this request cycle
        self::$geoMemCache[$ip] = $result;

        return $result;
    }

    public static function firePostback(string $url, string $method = 'GET'): array {
        if (empty(trim($url))) {
            return ['status' => 0, 'body' => '', 'success' => false, 'error' => 'Empty postback URL'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 15,           // 15 s total
            CURLOPT_CONNECTTIMEOUT  => 8,            // 8 s to connect
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_USERAGENT       => 'AffiliateTracker/2.0 Postback',
            CURLOPT_HTTPHEADER      => ['Accept: */*', 'Connection: close'],
            CURLOPT_ENCODING        => '',           // accept compressed responses
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }

        $body      = curl_exec($ch);
        $status    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        // curl_exec returns false on failure; cast to empty string for DB safety
        if ($body === false) {
            $body = '';
        }

        $success = ($status >= 200 && $status < 300);
        $error   = ($curlErrno !== 0) ? "cURL #{$curlErrno}: {$curlError}" : '';

        return [
            'status'  => $status,
            'body'    => (string)$body,
            'success' => $success,
            'error'   => $error,
        ];
    }

    public static function buildPostbackUrl(string $template, array $vars): string {
        foreach ($vars as $k => $v) {
            // Use rawurlencode (RFC 3986) so values are encoded exactly once.
            // urlencode() encodes spaces as "+" which breaks tracker click IDs;
            // rawurlencode() encodes them as "%20" which is universally safe.
            $encoded = rawurlencode((string)$v);
            $template = str_replace(
                ['{' . $k . '}', '%7B' . $k . '%7D'],
                $encoded,
                $template
            );
        }
        return $template;
    }

    public static function redirect(string $url, int $code = 302): void {
        header("Location: $url", true, $code);
        exit;
    }

    public static function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function flash(string $type, string $message): void {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): array {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }

    public static function formatMoney(float $amount, string $currency = 'USD'): string {
        return $currency . ' ' . number_format($amount, 2);
    }

    public static function timeAgo(\DateTimeInterface $dt): string {
        $diff = (new \DateTime())->getTimestamp() - $dt->getTimestamp();
        if ($diff < 60) return "{$diff}s ago";
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        return floor($diff / 86400) . 'd ago';
    }

    public static function post(string $key, $default = ''): string {
        return self::sanitize((string)($_POST[$key] ?? $default));
    }

    public static function get(string $key, $default = ''): string {
        return self::sanitize((string)($_GET[$key] ?? $default));
    }

    public static function postRaw(string $key, $default = ''): string {
        return (string)($_POST[$key] ?? $default);
    }

    public static function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function csrf(): string {
        return '<input type="hidden" name="_token" value="' . Auth::generateCsrf() . '">';
    }

    /**
     * Convert a 2-letter ISO country code to its Unicode flag emoji.
     * Returns empty string for invalid / non-2-letter codes.
     */
    public static function flag(string $code): string {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return '';
        // Regional Indicator Symbol Letters: U+1F1E6 (A) … U+1F1FF (Z)
        $offset = 0x1F1A5; // 0x1F1E6 - ord('A')
        return mb_chr($offset + ord($code[0]), 'UTF-8')
             . mb_chr($offset + ord($code[1]), 'UTF-8');
    }

    /**
     * Render a list of ISO country codes as "🇺🇸 US, 🇬🇧 GB, …"
     * Optionally truncate to $max items, appending $suffix when truncated.
     */
    public static function geoList(array $codes, int $max = 0, string $suffix = '…'): string {
        if (empty($codes)) return 'Global';
        $show = $max > 0 ? array_slice($codes, 0, $max) : $codes;
        $parts = array_map(fn($c) => self::flag($c) . ' ' . strtoupper(htmlspecialchars($c, ENT_QUOTES|ENT_HTML5, 'UTF-8')), $show);
        $out = implode(', ', $parts);
        if ($max > 0 && count($codes) > $max) $out .= ' ' . $suffix;
        return $out;
    }

    /**
     * Returns the base URL used for affiliate tracking links (/click/...).
     * Priority: app.tracking_url config → default tracking domain (DB) → app.url
     */
    public static function trackingUrl(): string {
        $trackingUrl = rtrim(Config::get('config', 'app.tracking_url') ?? '', '/');
        if ($trackingUrl !== '') return $trackingUrl;

        try {
            $defaultDomain = \Database::fetchOne(
                "SELECT domain FROM tracking_domains WHERE is_default=1 AND is_active=1 LIMIT 1"
            );
            if ($defaultDomain && !empty($defaultDomain['domain'])) {
                return 'https://' . rtrim($defaultDomain['domain'], '/');
            }
        } catch (\Throwable $_e) {}

        return rtrim(Config::get('config', 'app.url') ?? '', '/');
    }
}
