<?php
/**
 * FraudIQ — IPQualityScore (ipqualityscore.com) fraud detection
 *
 * checkConversion() is called after a conversion is recorded and returns
 * true if the conversion is identified as fraudulent and should be blocked.
 *
 * check() is a backwards-compatible alias for checkIPQS().
 */
class FraudIQ {

    // ── IPQualityScore IP check ───────────────────────────────────────────────

    public static function checkIPQS(string $ip, string $clickId): array {
        $cfg = Config::get('fraud') ?? [];

        if (empty($cfg['ipqs_api_key'])) {
            return ['score' => 0, 'action' => 'allow', 'provider' => 'ipqs_disabled'];
        }

        // ipqs_enabled flag: if explicitly set to false, skip
        if (array_key_exists('ipqs_enabled', $cfg) && !$cfg['ipqs_enabled']) {
            return ['score' => 0, 'action' => 'allow', 'provider' => 'ipqs_disabled'];
        }

        $apiKey         = $cfg['ipqs_api_key'];
        $timeoutSeconds = max(10, (int)($cfg['timeout_seconds'] ?? 10));

        // IMPORTANT: Do NOT urlencode the IP address.
        // IPv6 addresses contain colons (:) — urlencode() converts these to %3A
        // making the IPQS URL path invalid → API returns {success:false} → score 0.
        // IPv4 and IPv6 only contain [0-9a-fA-F:.] so no encoding is needed.
        // We strip any other characters defensively.
        // Strip CIDR block from IPv6 addresses if present
        if (strpos($ip, '/') !== false) {
            $ip = explode('/', $ip)[0];
        }
        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);

        if ($safeIp === '') {
            error_log('[IPQS] Invalid IP address — ip=' . $ip);
            return ['score' => 0, 'action' => 'allow', 'provider' => 'ipqs'];
        }

        $url = 'https://www.ipqualityscore.com/api/json/ip/'
             . urlencode($apiKey) . '/' . $safeIp
             . '?strictness=1&allow_public_access_points=1&fast=1&lighter_penalties=0';

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:126.0) Gecko/20100101 Firefox/126.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];
        $randomAgent = $userAgents[array_rand($userAgents)];

        $ch = curl_init($url);
        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => $randomAgent,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Cache-Control: no-cache',
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
        ];
        if (!empty($cfg['ipqs_proxy'])) {
            $curlOpts[CURLOPT_PROXY] = trim((string)$cfg['ipqs_proxy']);
        }
        curl_setopt_array($ch, $curlOpts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[IPQS] API call failed — ip=' . $ip . ' http=' . $httpCode . ' curlErr=' . $curlErr);
            if ($cfg['fail_open'] ?? true) {
                return ['score' => 0, 'action' => 'allow', 'provider' => 'ipqs'];
            }
            return ['score' => 50, 'action' => 'flag', 'provider' => 'ipqs'];
        }

        $data = json_decode($response, true);
        if (!$data || !($data['success'] ?? false)) {
            error_log('[IPQS] API returned failure — ip=' . $ip . ' body=' . substr((string)$response, 0, 400));
            return ['score' => 0, 'action' => 'allow', 'provider' => 'ipqs'];
        }

        $score   = (int)($data['fraud_score']  ?? 0);
        $isVpn   = (int)(bool)($data['vpn']        ?? false);
        $isProxy = (int)(bool)($data['proxy']       ?? false);
        $isTor   = (int)(bool)($data['tor']         ?? false);
        $isBot   = (int)(bool)($data['bot_status']  ?? false);
        $isDC    = (int)(bool)($data['active_vpn']  ?? false);

        $blockThreshold = (int)($cfg['block_threshold'] ?? 90);
        $flagThreshold  = (int)($cfg['score_threshold']  ?? 75);

        $action = 'allow';
        if ($score >= $blockThreshold)      $action = 'block';
        elseif ($score >= $flagThreshold)   $action = 'flag';

        $checks = $cfg['checks'] ?? [];
        if (($checks['vpn']        ?? false) && $isVpn)   $action = ($action === 'allow') ? 'flag' : $action;
        if (($checks['proxy']      ?? false) && $isProxy)  $action = ($action === 'allow') ? 'flag' : $action;
        if (($checks['tor']        ?? false) && $isTor)    $action = 'block';
        if (($checks['bot']        ?? false) && $isBot)    $action = 'block';
        if (($checks['datacenter'] ?? false) && $isDC)     $action = ($action === 'allow') ? 'flag' : $action;

        // Log to fraud_logs
        try {
            Database::insert('fraud_logs', [
                'click_id'      => $clickId,
                'ip_address'    => $ip,
                'fraud_score'   => $score,
                'is_vpn'        => $isVpn,
                'is_proxy'      => $isProxy,
                'is_tor'        => $isTor,
                'is_bot'        => $isBot,
                'is_datacenter' => $isDC,
                'isp'           => $data['ISP'] ?? ($data['organization'] ?? ''),
                'country'       => $data['country_code'] ?? '',
                'api_response'  => json_encode($data),
                'action_taken'  => $action,
            ]);
        } catch (\Exception $e) {}

        return [
            'score'         => $score,
            'action'        => $action,
            'is_vpn'        => $isVpn,
            'is_proxy'      => $isProxy,
            'is_tor'        => $isTor,
            'is_bot'        => $isBot,
            'is_datacenter' => $isDC,
            'provider'      => 'ipqs',
            'raw'           => $data,
        ];
    }

    // ── Backwards-compatible alias ────────────────────────────────────────────

    public static function check(string $ip, string $clickId): array {
        return self::checkIPQS($ip, $clickId);
    }

    // ── Post-conversion fraud check ───────────────────────────────────────────
    /**
     * Called after a conversion is recorded.
     * Returns true if the conversion is BLOCKED (fraudulent).
     *
     * ALWAYS writes fraud_checked_at so conversions never stay "Pending"
     * regardless of API outcome or provider configuration.
     */
    public static function checkConversion(string $ip, string $clickId, string $conversionId, float $payout, int $affiliateId): bool {
        $cfg = Config::get('fraud') ?? [];

        // Ensure required columns exist (safe repeated execution)
        try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
        try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at` DATETIME         DEFAULT NULL"); } catch (\Throwable $_e) {}

        $mode       = $cfg['mode'] ?? 'score_only';
        $hasApiKey  = !empty($cfg['ipqs_api_key']);
        $isEnabled  = array_key_exists('ipqs_enabled', $cfg) ? (bool)$cfg['ipqs_enabled'] : $hasApiKey;
        $hasProvider = $hasApiKey && $isEnabled;

        // Pre-stamp fraud_checked_at immediately — prevents "⏳ Pending" on API timeout
        try {
            Database::query(
                "UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL",
                [$conversionId]
            );
        } catch (\Throwable $_e) {}

        // No API configured — pre-stamp done, nothing else to do
        if (!$hasProvider) {
            return false;
        }

        // Call IPQS
        $result = ['score' => 0, 'action' => 'allow'];
        try {
            $result = self::checkIPQS($ip, $clickId);
        } catch (\Throwable $_apiEx) {
            error_log('[IPQS] checkConversion exception — conv=' . $conversionId . ': ' . $_apiEx->getMessage());
        }

        $score  = (int)($result['score']  ?? 0);
        $action = (string)($result['action'] ?? 'allow');

        // Persist actual score (overwrites NULL pre-stamp with real score)
        try {
            Database::query(
                "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                [$score, $conversionId]
            );
        } catch (\Throwable $_e) {
            error_log('[IPQS] Failed to save fraud_score — conv=' . $conversionId . ' score=' . $score . ': ' . $_e->getMessage());
        }

        // Mirror highest score to the click row
        if ($score > 0) {
            try {
                Database::query(
                    "UPDATE `clicks` SET `fraud_score`=? WHERE `click_id`=? AND (COALESCE(`fraud_score`,0) < ?)",
                    [$score, $clickId, $score]
                );
            } catch (\Exception $e) {}
        }

        // Score-Only Mode — record but never block
        if ($mode === 'score_only') {
            if ($action !== 'allow' && $score > 0) {
                try { Database::update('clicks', ['is_fraud' => 1], 'click_id=? AND is_fraud=0', [$clickId]); } catch (\Exception $e) {}
            }
            return false;
        }

        // Block Mode — fraud conversions are blocked automatically.
        // (The legacy `check_on_conversion` checkbox in the UI is no longer a
        // hard gate; when admin picks Block Mode, blocking IS the mode. The
        // checkbox can still be unchecked to opt out per-installation, but
        // when unset we treat Block Mode as enabled by default — matching
        // the user-facing UI promise of "block fraudulent conversions".)
        $blockingEnabled = $cfg['check_on_conversion'] ?? true;
        if (!$blockingEnabled || $action !== 'block') {
            if ($action === 'flag' && $score > 0) {
                try { Database::update('clicks', ['is_fraud' => 1], 'click_id=? AND is_fraud=0', [$clickId]); } catch (\Exception $e) {}
            }
            return false;
        }

        // Fraud confirmed — block the conversion
        try {
            Database::update('conversions', [
                'is_fraud'    => 1,
                'is_hidden'   => 1,
                'hide_reason' => 'ipqs_blocked',
            ], 'conversion_id=?', [$conversionId]);

            Database::query(
                "UPDATE `affiliates` SET `balance` = `balance` - ? WHERE `id` = ? AND `balance` >= ?",
                [$payout, $affiliateId, $payout]
            );

            Database::update('clicks', [
                'is_fraud'    => 1,
                'fraud_score' => $score,
                'status'      => 'blocked',
            ], 'click_id=?', [$clickId]);

            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'warning',
                'title'       => 'IPQS: Conversion Blocked',
                'message'     => 'Conversion ' . substr($conversionId, 0, 8) . '... blocked. IP: ' . $ip . ' | Score: ' . $score,
                'link'        => '/admin/fraud',
            ]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── Scamalytics fraud detection ───────────────────────────────────────────
    /**
     * checkFraud() — primary Scamalytics entry point called on every conversion.
     *
     * Calls https://api{server}.scamalytics.com/v3/{user}/?key={apiKey}&ip={ip} and returns:
     *   score   int     0–100  (raw API score)
     *   status  string  'allowed' | 'flagged' | 'blocked'
     *   mode    string  'score_only' | 'auto_block'  (active mode from config)
     *   raw     array   full decoded API response
     *
     * Mode behaviour (read from Config::get('fraud','scamalytics_mode')):
     *   score_only  — status is always 'allowed'; score is recorded but nothing
     *                 is blocked. Safe for monitoring without disruption.
     *   auto_block  — score >= 80  → 'blocked'
     *                 score 50–79  → 'flagged'
     *                 score < 50   → 'allowed'
     *
     * On any API failure the method returns score=0 / status='allowed' so
     * conversions are never incorrectly blocked due to an outage.
     *
     * Config keys (all under config/fraud.json):
     *   scamalytics_api_key       string  required
     *   scamalytics_enabled       bool    default true when key present
     *   scamalytics_mode          string  'score_only' | 'auto_block'  default 'score_only'
     *   scamalytics_block_threshold int   default 80
     *   scamalytics_flag_threshold  int   default 50
     */
    public static function checkFraud(string $ip): array {
        $default = ['score' => 0, 'status' => 'allowed', 'mode' => 'score_only', 'raw' => []];

        $cfg    = Config::get('fraud') ?? [];
        $apiKey = trim($cfg['scamalytics_api_key'] ?? '');
        $user   = trim($cfg['scamalytics_user']    ?? '');
        $server = trim($cfg['scamalytics_server']  ?? '');

        // All three credentials are required
        if ($apiKey === '' || $user === '' || $server === '') {
            return $default;
        }

        // Respect explicit scamalytics_enabled = false
        if (array_key_exists('scamalytics_enabled', $cfg) && !$cfg['scamalytics_enabled']) {
            return $default;
        }

        $mode           = $cfg['scamalytics_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['scamalytics_block_threshold'] ?? 80);
        $flagThreshold  = (int)($cfg['scamalytics_flag_threshold']  ?? 50);

        // Sanitise IP — strip CIDR and everything not valid in an IPv4/IPv6 address
        if (strpos($ip, '/') !== false) {
            $ip = explode('/', $ip)[0];
        }
        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') {
            error_log('[Scamalytics] Invalid IP address — ip=' . $ip);
            return $default;
        }

        // Correct v3 URL format:
        // https://api{server}.scamalytics.com/v3/{user}/?key={apiKey}&ip={ip}
        $url = 'https://api' . preg_replace('/[^0-9]/', '', $server)
             . '.scamalytics.com/v3/' . urlencode($user)
             . '/?key=' . urlencode($apiKey)
             . '&ip=' . $safeIp;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
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
            error_log('[Scamalytics] API call failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[Scamalytics] Invalid JSON — ip=' . $ip . ' body=' . substr((string)$response, 0, 200));
            return $default;
        }

        // Scamalytics v3 wraps all data under a "scamalytics" key at the top level.
        // Unwrap it so field lookups work regardless of nesting.
        if (isset($data['scamalytics']) && is_array($data['scamalytics'])) {
            $data = $data['scamalytics'];
        }

        // Score field is "scamalytics_score" in the nested object; fall back to "score".
        $score = (int)($data['scamalytics_score'] ?? $data['score'] ?? 0);

        // Determine status according to the active mode
        if ($mode === 'auto_block') {
            if ($score >= $blockThreshold) {
                $status = 'blocked';
            } elseif ($score >= $flagThreshold) {
                $status = 'flagged';
            } else {
                $status = 'allowed';
            }
        } else {
            // score_only — record everything, block nothing
            $status = 'allowed';
        }

        return [
            'score'  => $score,
            'status' => $status,
            'mode'   => $mode,
            'raw'    => $data,
        ];
    }

    /**
     * checkScamalyticsConversion() — called inside postback.php for every
     * auto-approved conversion after scoring has been saved.
     * Returns true if the conversion was blocked (auto_block mode only).
     * Never blocks hidden or pending conversions — caller decides context.
     */
    public static function checkScamalyticsConversion(
        string $ip,
        string $conversionId,
        float  $payout,
        int    $affiliateId,
        string $clickId
    ): bool {
        $result = self::checkFraud($ip);

        // Persist score + status + mode on the conversion row
        try {
            Database::query(
                "UPDATE `conversions`
                    SET `scamalytics_score`  = ?,
                        `scamalytics_status` = ?,
                        `scamalytics_mode`   = ?
                  WHERE `conversion_id` = ?",
                [$result['score'], $result['status'], $result['mode'], $conversionId]
            );
        } catch (\Throwable $_e) {
            error_log('[Scamalytics] Failed to save score — conv=' . $conversionId . ': ' . $_e->getMessage());
        }

        if ($result['status'] !== 'blocked') {
            return false;
        }

        // Block the conversion: mark fraud, reverse balance, notify admin
        try {
            Database::update('conversions', [
                'is_fraud'    => 1,
                'is_hidden'   => 1,
                'hide_reason' => 'scamalytics_blocked',
            ], 'conversion_id=?', [$conversionId]);

            Database::query(
                "UPDATE `affiliates` SET `balance` = `balance` - ? WHERE `id` = ? AND `balance` >= ?",
                [$payout, $affiliateId, $payout]
            );

            Database::update('clicks', [
                'is_fraud' => 1,
                'status'   => 'blocked',
            ], 'click_id=?', [$clickId]);

            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'warning',
                'title'       => 'Scamalytics: Conversion Blocked',
                'message'     => 'Conversion ' . substr($conversionId, 0, 8)
                               . '... blocked. IP: ' . $ip
                               . ' | Score: ' . $result['score'],
                'link'        => '/admin/fraud',
            ]);
        } catch (\Exception $e) {
            error_log('[Scamalytics] Block actions failed — conv=' . $conversionId . ': ' . $e->getMessage());
        }

        return true;
    }

    // ── IPQuery.io fraud detection ────────────────────────────────────────────
    /**
     * Calls https://api.ipquery.io/{ip} and returns:
     *   risk_score    int   0–100
     *   risk_level    string  'low' | 'medium' | 'high'
     *   is_vpn        int   0|1
     *   is_proxy      int   0|1
     *   is_tor        int   0|1
     *   is_datacenter int   0|1
     *
     * Risk thresholds: >=80 high, 50–79 medium, <50 low.
     * On any failure the method returns score=0 / low risk so conversions are
     * never blocked due to an API error.
     */
    /**
     * checkIPQuery() — calls https://api.ipquery.io/{ip} and returns:
     *   risk_score    int    0–100
     *   risk_level    string 'low' | 'medium' | 'high'
     *   status        string 'allowed' | 'flagged' | 'blocked'
     *   mode          string 'score_only' | 'auto_block'
     *   is_vpn        int    0|1
     *   is_proxy      int    0|1
     *   is_tor        int    0|1
     *   is_datacenter int    0|1
     *
     * Config keys (config/fraud.json):
     *   ipquery_enabled          bool   default false
     *   ipquery_mode             string 'score_only' | 'auto_block'  default 'score_only'
     *   ipquery_block_threshold  int    default 80
     *   ipquery_flag_threshold   int    default 50
     *
     * In score_only mode: status is always 'allowed' — nothing is blocked.
     * In auto_block mode: score >= block_threshold → 'blocked',
     *                     score >= flag_threshold  → 'flagged',
     *                     else                     → 'allowed'.
     * On any API failure returns score=0 / status='allowed' (fail open).
     */
    public static function checkIPQuery(string $ip): array {
        $cfg            = Config::get('fraud') ?? [];
        $mode           = $cfg['ipquery_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['ipquery_block_threshold'] ?? 80);
        $flagThreshold  = (int)($cfg['ipquery_flag_threshold']  ?? 50);

        // IPQuery.io is FREE — no API key required. It is always active unless
        // explicitly disabled via ipquery_enabled = false in config.
        // Default: enabled (treat absent key as enabled).
        if (array_key_exists('ipquery_enabled', $cfg) && !(bool)$cfg['ipquery_enabled']) {
            return [
                'risk_score' => 0, 'risk_level' => 'low', 'status' => 'allowed', 'mode' => $mode,
                'is_vpn' => 0, 'is_proxy' => 0, 'is_tor' => 0, 'is_datacenter' => 0, 'is_mobile' => 0,
                'country' => '', 'country_code' => '', 'city' => '', 'state' => '', 'timezone' => '',
                'isp' => '', 'org' => '', 'asn' => '',
            ];
        }

        $default = [
            'risk_score'    => 0,
            'risk_level'    => 'low',
            'status'        => 'allowed',
            'mode'          => $mode,
            'is_vpn'        => 0,
            'is_proxy'      => 0,
            'is_tor'        => 0,
            'is_datacenter' => 0,
            'is_mobile'     => 0,
            'country'       => '',
            'country_code'  => '',
            'city'          => '',
            'state'         => '',
            'timezone'      => '',
            'isp'           => '',
            'org'           => '',
            'asn'           => '',
        ];

        // Strip CIDR and any characters that are not valid in an IP address
        if (strpos($ip, '/') !== false) {
            $ip = explode('/', $ip)[0];
        }
        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') {
            error_log('[IPQuery] Invalid IP address — ip=' . $ip);
            return $default;
        }

        $url = 'https://api.ipquery.io/' . $safeIp;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
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
            error_log('[IPQuery] API call failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[IPQuery] Invalid JSON response — ip=' . $ip . ' body=' . substr((string)$response, 0, 200));
            return $default;
        }

        // IPQuery.io response structure:
        // { "ip":"...", "isp":{"asn":"AS15169","org":"Google LLC","isp":"Google LLC"},
        //   "location":{"country":"United States","country_code":"US","city":"Mountain View","state":"California",...},
        //   "risk":{"is_mobile":false,"is_vpn":false,"is_tor":false,"is_proxy":false,"is_datacenter":false,"risk_score":0} }
        $risk     = isset($data['risk'])     && is_array($data['risk'])     ? $data['risk']     : $data;
        $location = isset($data['location']) && is_array($data['location']) ? $data['location'] : [];
        $ispData  = isset($data['isp'])      && is_array($data['isp'])      ? $data['isp']      : [];

        $riskScore    = (int)($risk['risk_score']    ?? 0);
        $isVpn        = (int)(bool)($risk['is_vpn']        ?? ($risk['vpn']        ?? false));
        $isProxy      = (int)(bool)($risk['is_proxy']      ?? ($risk['proxy']      ?? false));
        $isTor        = (int)(bool)($risk['is_tor']        ?? ($risk['tor']        ?? false));
        $isDatacenter = (int)(bool)($risk['is_datacenter'] ?? ($risk['datacenter'] ?? false));
        $isMobile     = (int)(bool)($risk['is_mobile']     ?? false);

        // Location fields
        $country     = $location['country']      ?? ($data['country']      ?? '');
        $countryCode = $location['country_code'] ?? ($data['country_code'] ?? '');
        $city        = $location['city']         ?? ($data['city']         ?? '');
        $state       = $location['state']        ?? ($data['state']        ?? '');
        $timezone    = $location['timezone']     ?? '';

        // ISP fields
        $isp    = $ispData['isp']  ?? ($ispData['org'] ?? ($data['isp'] ?? ''));
        $org    = $ispData['org']  ?? '';
        $asn    = $ispData['asn']  ?? '';

        // Fraud scoring thresholds:
        //  > 70 → High Risk ❌  (VPN/Proxy/TOR/DC also bump this)
        // 30–70 → Medium Risk ⚠️
        //  < 30 → Low Risk ✅
        if ($riskScore > 70 || $isTor || ($isVpn && $isProxy)) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30 || $isVpn || $isProxy || $isDatacenter) {
            $riskLevel = 'medium';
        } else {
            $riskLevel = 'low';
        }

        // Determine status based on active mode
        if ($mode === 'auto_block') {
            if ($riskScore >= $blockThreshold || $isTor) {
                $status = 'blocked';
            } elseif ($riskScore >= $flagThreshold || $isVpn || $isProxy || $isDatacenter) {
                $status = 'flagged';
            } else {
                $status = 'allowed';
            }
        } else {
            // score_only — record everything, block nothing
            $status = 'allowed';
        }

        return [
            'risk_score'    => $riskScore,
            'risk_level'    => $riskLevel,
            'status'        => $status,
            'mode'          => $mode,
            'is_vpn'        => $isVpn,
            'is_proxy'      => $isProxy,
            'is_tor'        => $isTor,
            'is_datacenter' => $isDatacenter,
            'is_mobile'     => $isMobile,
            'country'       => $country,
            'country_code'  => $countryCode,
            'city'          => $city,
            'state'         => $state,
            'timezone'      => $timezone,
            'isp'           => $isp,
            'org'           => $org,
            'asn'           => $asn,
        ];
    }

    /**
     * checkIPQueryBatch() — bulk IP intelligence lookup using IPQuery.io batch API.
     * Endpoint: POST https://api.ipquery.io/?bulk
     * Body: JSON array of IP strings, e.g. ["1.2.3.4","5.6.7.8"]
     * Returns: associative array keyed by IP address, each value = same shape as checkIPQuery().
     * On any failure returns an empty array (fail-open).
     * Rate: free tier supports up to 100 IPs per batch request.
     */
    public static function checkIPQueryBatch(array $ips): array {
        // Sanitise + deduplicate IPs
        $safeIps = [];
        foreach ($ips as $ip) {
            if (strpos((string)$ip, '/') !== false) {
                $ip = explode('/', (string)$ip)[0];
            }
            $s = preg_replace('/[^0-9a-fA-F:.]/', '', (string)$ip);
            if ($s !== '' && filter_var($s, FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                $safeIps[$s] = $s;
            }
        }
        $safeIps = array_values($safeIps);

        if (empty($safeIps)) return [];

        // IPQuery.io batch: POST JSON array to https://api.ipquery.io/?bulk
        $payload = json_encode($safeIps);
        $ch = curl_init('https://api.ipquery.io/?bulk');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[IPQuery Batch] API failed — http=' . $httpCode . ' err=' . $curlErr);
            return [];
        }

        $results = json_decode($response, true);
        if (!is_array($results)) {
            error_log('[IPQuery Batch] Invalid JSON — body=' . substr((string)$response, 0, 300));
            return [];
        }

        $cfg            = Config::get('fraud') ?? [];
        $mode           = $cfg['ipquery_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['ipquery_block_threshold'] ?? 80);
        $flagThreshold  = (int)($cfg['ipquery_flag_threshold']  ?? 50);

        $out = [];
        foreach ($results as $item) {
            if (!is_array($item)) continue;
            $ip = $item['ip'] ?? '';
            if ($ip === '') continue;

            $risk     = isset($item['risk'])     && is_array($item['risk'])     ? $item['risk']     : $item;
            $location = isset($item['location']) && is_array($item['location']) ? $item['location'] : [];
            $ispData  = isset($item['isp'])      && is_array($item['isp'])      ? $item['isp']      : [];

            $riskScore    = (int)($risk['risk_score']    ?? 0);
            $isVpn        = (int)(bool)($risk['is_vpn']        ?? false);
            $isProxy      = (int)(bool)($risk['is_proxy']      ?? false);
            $isTor        = (int)(bool)($risk['is_tor']        ?? false);
            $isDatacenter = (int)(bool)($risk['is_datacenter'] ?? false);
            $isMobile     = (int)(bool)($risk['is_mobile']     ?? false);

            if ($riskScore > 70 || $isTor || ($isVpn && $isProxy)) {
                $riskLevel = 'high';
            } elseif ($riskScore >= 30 || $isVpn || $isProxy || $isDatacenter) {
                $riskLevel = 'medium';
            } else {
                $riskLevel = 'low';
            }

            if ($mode === 'auto_block') {
                if ($riskScore >= $blockThreshold || $isTor) {
                    $status = 'blocked';
                } elseif ($riskScore >= $flagThreshold || $isVpn || $isProxy || $isDatacenter) {
                    $status = 'flagged';
                } else {
                    $status = 'allowed';
                }
            } else {
                $status = 'allowed';
            }

            $out[$ip] = [
                'ip'            => $ip,
                'risk_score'    => $riskScore,
                'risk_level'    => $riskLevel,
                'status'        => $status,
                'mode'          => $mode,
                'is_vpn'        => $isVpn,
                'is_proxy'      => $isProxy,
                'is_tor'        => $isTor,
                'is_datacenter' => $isDatacenter,
                'is_mobile'     => $isMobile,
                'country'       => $location['country']      ?? '',
                'country_code'  => $location['country_code'] ?? '',
                'city'          => $location['city']         ?? '',
                'state'         => $location['state']        ?? '',
                'isp'           => $ispData['isp'] ?? ($ispData['org'] ?? ''),
                'org'           => $ispData['org'] ?? '',
                'asn'           => $ispData['asn'] ?? '',
            ];
        }

        return $out;
    }

    /**
     * checkIPQueryConversion() — called in postback.php for auto-approved
     * conversions when IPQuery auto_block mode is active and status='blocked'.
     * Executes the full block action and returns true if the conversion was blocked.
     */
    public static function checkIPQueryConversion(
        string $ip,
        string $conversionId,
        float  $payout,
        int    $affiliateId,
        string $clickId
    ): bool {
        $result = self::checkIPQuery($ip);

        // Persist the latest score/status/mode on the conversion row
        try {
            Database::query(
                "UPDATE `conversions`
                    SET `ipquery_risk_score` = ?,
                        `ipquery_risk_level` = ?,
                        `ipquery_datacenter` = ?
                  WHERE `conversion_id` = ?",
                [$result['risk_score'], $result['risk_level'], $result['is_datacenter'], $conversionId]
            );
        } catch (\Throwable $_e) {
            error_log('[IPQuery] Failed to save score on block — conv=' . $conversionId . ': ' . $_e->getMessage());
        }

        if ($result['status'] !== 'blocked') {
            return false;
        }

        // Block the conversion: mark fraud, reverse balance, notify admin
        try {
            Database::update('conversions', [
                'is_fraud'    => 1,
                'is_hidden'   => 1,
                'hide_reason' => 'ipquery_blocked',
            ], 'conversion_id=?', [$conversionId]);

            Database::query(
                "UPDATE `affiliates` SET `balance` = `balance` - ? WHERE `id` = ? AND `balance` >= ?",
                [$payout, $affiliateId, $payout]
            );

            Database::update('clicks', [
                'is_fraud' => 1,
                'status'   => 'blocked',
            ], 'click_id=?', [$clickId]);

            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'warning',
                'title'       => 'IPQuery: Conversion Blocked',
                'message'     => 'Conversion ' . substr($conversionId, 0, 8)
                               . '... blocked. IP: ' . $ip
                               . ' | Score: ' . $result['risk_score'],
                'link'        => '/admin/fraud',
            ]);
        } catch (\Exception $e) {
            error_log('[IPQuery] Block actions failed — conv=' . $conversionId . ': ' . $e->getMessage());
        }

        return true;
    }

    // ── ProxyCheck.io fraud detection ────────────────────────────────────────
    /**
     * checkProxyCheck() — calls https://proxycheck.io/v2/{IP} and returns:
     *   score    int    0–100  (risk score)
     *   is_proxy int    0|1
     *   is_vpn   int    0|1
     *   status   string 'allowed' | 'flagged' | 'blocked'
     *   mode     string 'score_only' | 'auto_block'
     *
     * Block threshold: risk > 50 (configurable via proxycheck_block_threshold)
     * On any failure returns score=0/status='allowed' (fail-open).
     *
     * Config keys (config/fraud.json):
     *   proxycheck_api_key        string  required
     *   proxycheck_enabled        bool    default true when key present
     *   proxycheck_mode           string  'score_only' | 'auto_block'  default 'score_only'
     *   proxycheck_block_threshold int    default 50
     *   proxycheck_flag_threshold  int    default 30
     */
    public static function checkProxyCheck(string $ip): array {
        $cfg     = Config::get('fraud') ?? [];
        $apiKey  = trim($cfg['proxycheck_api_key'] ?? '');
        $default = ['score' => 0, 'is_proxy' => 0, 'is_vpn' => 0, 'status' => 'allowed', 'mode' => 'score_only'];

        if ($apiKey === '') return $default;
        if (array_key_exists('proxycheck_enabled', $cfg) && !$cfg['proxycheck_enabled']) return $default;

        $mode           = $cfg['proxycheck_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['proxycheck_block_threshold'] ?? 50);
        $flagThreshold  = (int)($cfg['proxycheck_flag_threshold']  ?? 30);

        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') return $default;

        $url = 'https://proxycheck.io/v2/' . $safeIp
             . '?key=' . urlencode($apiKey)
             . '&vpn=1&asn=1&risk=1&port=1';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[ProxyCheck] API failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'ok') return $default;

        // Data for the specific IP is keyed by the IP address itself
        $ipData  = $data[$safeIp] ?? $data[$ip] ?? [];
        $score   = (int)($ipData['risk'] ?? 0);
        $isProxy = (int)(strtolower($ipData['proxy'] ?? 'no') === 'yes');
        $isVpn   = (int)(strtolower($ipData['vpn']   ?? 'no') === 'yes');

        if ($mode === 'auto_block') {
            if ($score >= $blockThreshold || $isProxy || $isVpn) {
                $status = 'blocked';
            } elseif ($score >= $flagThreshold) {
                $status = 'flagged';
            } else {
                $status = 'allowed';
            }
        } else {
            $status = 'allowed';
        }

        return ['score' => $score, 'is_proxy' => $isProxy, 'is_vpn' => $isVpn, 'status' => $status, 'mode' => $mode];
    }

    public static function checkProxyCheckConversion(
        string $ip, string $conversionId, float $payout, int $affiliateId, string $clickId
    ): bool {
        $result = self::checkProxyCheck($ip);

        try {
            Database::query(
                "UPDATE `conversions` SET `proxycheck_score`=?,`proxycheck_is_proxy`=?,`proxycheck_is_vpn`=?,`proxycheck_status`=? WHERE `conversion_id`=?",
                [$result['score'], $result['is_proxy'], $result['is_vpn'], $result['status'], $conversionId]
            );
        } catch (\Throwable $_e) {}

        if ($result['status'] !== 'blocked') return false;

        try {
            Database::update('conversions', ['is_fraud'=>1,'is_hidden'=>1,'hide_reason'=>'proxycheck_blocked'], 'conversion_id=?', [$conversionId]);
            Database::query("UPDATE `affiliates` SET `balance`=`balance`-? WHERE `id`=? AND `balance`>=?", [$payout, $affiliateId, $payout]);
            Database::update('clicks', ['is_fraud'=>1,'status'=>'blocked'], 'click_id=?', [$clickId]);
            Database::insert('notifications', [
                'user_id'=>null,'target_role'=>'admin','type'=>'warning',
                'title'=>'ProxyCheck: Conversion Blocked',
                'message'=>'Conversion '.substr($conversionId,0,8).'... blocked. IP: '.$ip.' | Score: '.$result['score'],
                'link'=>'/admin/fraud',
            ]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── BotScout fraud detection ──────────────────────────────────────────────
    /**
     * checkBotScout() — calls https://botscout.com/test/?ip={IP}&key={KEY}
     * BotScout returns plain text: Y|TYPE|COUNT (bot) or N|TYPE|COUNT (clean).
     *
     * Returns:
     *   is_bot  int    0|1
     *   count   int    number of prior bot hits
     *   status  string 'allowed' | 'blocked'
     *   mode    string 'score_only' | 'auto_block'
     *
     * In auto_block mode: any is_bot=1 → 'blocked'.
     * On any failure returns is_bot=0/status='allowed' (fail-open).
     *
     * Config keys: botscout_api_key, botscout_enabled, botscout_mode
     */
    public static function checkBotScout(string $ip): array {
        $cfg    = Config::get('fraud') ?? [];
        $apiKey = trim($cfg['botscout_api_key'] ?? '');
        $default = ['is_bot' => 0, 'count' => 0, 'status' => 'allowed', 'mode' => 'score_only'];

        if ($apiKey === '') return $default;
        if (array_key_exists('botscout_enabled', $cfg) && !$cfg['botscout_enabled']) return $default;

        $mode   = $cfg['botscout_mode'] ?? 'score_only';

        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') return $default;

        $url = 'https://botscout.com/test/?ip=' . urlencode($safeIp) . '&key=' . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[BotScout] API failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        // Response format: Y|IP|3  or  N|IP|0
        $response = trim($response);
        $parts    = explode('|', $response);
        $isBot    = (isset($parts[0]) && strtoupper($parts[0]) === 'Y') ? 1 : 0;
        $count    = isset($parts[2]) ? (int)$parts[2] : 0;

        $status = ($mode === 'auto_block' && $isBot) ? 'blocked' : 'allowed';

        return ['is_bot' => $isBot, 'count' => $count, 'status' => $status, 'mode' => $mode];
    }

    public static function checkBotScoutConversion(
        string $ip, string $conversionId, float $payout, int $affiliateId, string $clickId
    ): bool {
        $result = self::checkBotScout($ip);

        try {
            Database::query(
                "UPDATE `conversions` SET `botscout_is_bot`=?,`botscout_count`=?,`botscout_status`=? WHERE `conversion_id`=?",
                [$result['is_bot'], $result['count'], $result['status'], $conversionId]
            );
        } catch (\Throwable $_e) {}

        if ($result['status'] !== 'blocked') return false;

        try {
            Database::update('conversions', ['is_fraud'=>1,'is_hidden'=>1,'hide_reason'=>'botscout_blocked'], 'conversion_id=?', [$conversionId]);
            Database::query("UPDATE `affiliates` SET `balance`=`balance`-? WHERE `id`=? AND `balance`>=?", [$payout, $affiliateId, $payout]);
            Database::update('clicks', ['is_fraud'=>1,'status'=>'blocked'], 'click_id=?', [$clickId]);
            Database::insert('notifications', [
                'user_id'=>null,'target_role'=>'admin','type'=>'warning',
                'title'=>'BotScout: Conversion Blocked',
                'message'=>'Conversion '.substr($conversionId,0,8).'... blocked (bot detected). IP: '.$ip,
                'link'=>'/admin/fraud',
            ]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── FraudDefense.io fraud detection ──────────────────────────────────────
    /**
     * checkFraudDefense() — calls the FraudDefense.io API.
     * URL: https://api.frauddefense.io/v1/{IP}?apikey={KEY}
     *
     * Returns:
     *   score   int    0–100
     *   status  string 'allowed' | 'flagged' | 'blocked'
     *   mode    string 'score_only' | 'auto_block'
     *   raw     array
     *
     * Block threshold: fraud_score > 50 (configurable).
     * On any failure returns score=0/status='allowed' (fail-open).
     *
     * Config keys: frauddefense_api_key, frauddefense_enabled,
     *              frauddefense_mode, frauddefense_block_threshold,
     *              frauddefense_flag_threshold
     */
    public static function checkFraudDefense(string $ip): array {
        $cfg    = Config::get('fraud') ?? [];
        $apiKey = trim($cfg['frauddefense_api_key'] ?? '');
        $default = ['score' => 0, 'status' => 'allowed', 'mode' => 'score_only', 'raw' => []];

        if ($apiKey === '') return $default;
        if (array_key_exists('frauddefense_enabled', $cfg) && !$cfg['frauddefense_enabled']) return $default;

        $mode           = $cfg['frauddefense_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['frauddefense_block_threshold'] ?? 50);
        $flagThreshold  = (int)($cfg['frauddefense_flag_threshold']  ?? 30);

        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') return $default;

        // FraudDefense.io: API key passed as ?key= query parameter
        // Response: {"status":"ok","data":{"detections":{"risk":0,...},...}}
        $url = 'https://api.frauddefense.io/v1/' . $safeIp . '?key=' . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'AffiliateTracker/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[FraudDefense] API failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) return $default;

        // Check for API-level error response
        if (($data['status'] ?? '') === 'error') {
            error_log('[FraudDefense] API error — ip=' . $ip . ' msg=' . ($data['message'] ?? ''));
            return $default;
        }

        // Unwrap 'data' wrapper
        if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];

        // Score is at data.detections.risk (per API docs)
        $detections = $data['detections'] ?? [];
        $score = (int)($detections['risk'] ?? $data['fraud_score'] ?? $data['risk_score'] ?? $data['score'] ?? 0);

        if ($mode === 'auto_block') {
            if ($score >= $blockThreshold) {
                $status = 'blocked';
            } elseif ($score >= $flagThreshold) {
                $status = 'flagged';
            } else {
                $status = 'allowed';
            }
        } else {
            $status = 'allowed';
        }

        return ['score' => $score, 'status' => $status, 'mode' => $mode, 'raw' => $data];
    }

    public static function checkFraudDefenseConversion(
        string $ip, string $conversionId, float $payout, int $affiliateId, string $clickId
    ): bool {
        $result = self::checkFraudDefense($ip);

        try {
            Database::query(
                "UPDATE `conversions` SET `frauddefense_score`=?,`frauddefense_status`=? WHERE `conversion_id`=?",
                [$result['score'], $result['status'], $conversionId]
            );
        } catch (\Throwable $_e) {}

        if ($result['status'] !== 'blocked') return false;

        try {
            Database::update('conversions', ['is_fraud'=>1,'is_hidden'=>1,'hide_reason'=>'frauddefense_blocked'], 'conversion_id=?', [$conversionId]);
            Database::query("UPDATE `affiliates` SET `balance`=`balance`-? WHERE `id`=? AND `balance`>=?", [$payout, $affiliateId, $payout]);
            Database::update('clicks', ['is_fraud'=>1,'status'=>'blocked'], 'click_id=?', [$clickId]);
            Database::insert('notifications', [
                'user_id'=>null,'target_role'=>'admin','type'=>'warning',
                'title'=>'FraudDefense: Conversion Blocked',
                'message'=>'Conversion '.substr($conversionId,0,8).'... blocked. IP: '.$ip.' | Score: '.$result['score'],
                'link'=>'/admin/fraud',
            ]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── FraudLabs Pro fraud detection ─────────────────────────────────────────
    /**
     * checkFraudLabsPro() — calls https://api.fraudlabspro.com/v2/order/screen
     *
     * Returns:
     *   score        int    0–100
     *   status       string 'allowed' | 'flagged' | 'blocked'
     *   mode         string 'score_only' | 'auto_block'
     *   flp_status   string raw FLP status: APPROVE | REVIEW | REJECT
     *   is_proxy     int    0|1
     *   is_vpn       int    0|1
     *   is_tor       int    0|1
     *   country_code string
     *   city         string
     *   isp          string
     *   raw          array  full decoded API response
     *
     * Config keys: fraudlabspro_api_key, fraudlabspro_enabled,
     *              fraudlabspro_mode, fraudlabspro_block_threshold,
     *              fraudlabspro_flag_threshold
     */
    public static function checkFraudLabsPro(string $ip): array {
        $cfg    = Config::get('fraud') ?? [];
        $apiKey = trim($cfg['fraudlabspro_api_key'] ?? '');
        $default = [
            'score' => 0, 'status' => 'allowed', 'mode' => 'score_only',
            'flp_status' => '', 'is_proxy' => 0, 'is_vpn' => 0, 'is_tor' => 0,
            'country_code' => '', 'city' => '', 'isp' => '', 'raw' => [],
        ];

        if ($apiKey === '') return $default;
        if (array_key_exists('fraudlabspro_enabled', $cfg) && !(bool)$cfg['fraudlabspro_enabled']) return $default;

        $mode           = $cfg['fraudlabspro_mode']            ?? 'score_only';
        $blockThreshold = (int)($cfg['fraudlabspro_block_threshold'] ?? 75);
        $flagThreshold  = (int)($cfg['fraudlabspro_flag_threshold']  ?? 40);

        $safeIp = preg_replace('/[^0-9a-fA-F:.]/', '', $ip);
        if ($safeIp === '') return $default;

        // FraudLabs Pro v2 Order Screening API
        // Docs: https://www.fraudlabspro.com/developer/api/screen-order
        // v2 requires a POST request with form-encoded body
        $postFields = http_build_query([
            'key'    => $apiKey,
            'ip'     => $safeIp,
            'format' => 'json',
        ]);

        $ch = curl_init('https://api.fraudlabspro.com/v2/order/screen');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/2.0',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$response || $httpCode !== 200) {
            error_log('[FraudLabsPro] API failed — ip=' . $ip . ' http=' . $httpCode . ' err=' . $curlErr);
            return $default;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            error_log('[FraudLabsPro] Non-JSON response — ip=' . $ip . ' body=' . substr((string)$response, 0, 200));
            return $default;
        }

        // FLP returns error_code on failure (e.g. invalid API key)
        if (!empty($data['error_code'])) {
            error_log('[FraudLabsPro] API error — ip=' . $ip . ' code=' . $data['error_code'] . ' msg=' . ($data['message'] ?? ''));
            return $default;
        }

        // Score: fraudlabspro_score field (0–100)
        $score    = (int)($data['fraudlabspro_score'] ?? 0);
        $flpRaw   = strtoupper($data['fraudlabspro_status'] ?? ''); // APPROVE | REVIEW | REJECT
        $isProxy  = (int)(bool)($data['is_proxy_ip_address']  ?? false);
        $isVpn    = (int)(bool)($data['is_vpn']               ?? false);
        $isTor    = (int)(bool)($data['is_tor']               ?? false);
        $country  = $data['ip_country_code'] ?? ($data['ip_country'] ?? '');
        $city     = $data['ip_city']         ?? '';
        $isp      = $data['ip_isp']          ?? ($data['ip_org'] ?? '');

        // Map thresholds to internal status
        if ($mode === 'auto_block') {
            if ($score >= $blockThreshold || $flpRaw === 'REJECT') {
                $status = 'blocked';
            } elseif ($score >= $flagThreshold || $flpRaw === 'REVIEW') {
                $status = 'flagged';
            } else {
                $status = 'allowed';
            }
        } else {
            $status = 'allowed'; // score_only — never block
        }

        return [
            'score'        => $score,
            'status'       => $status,
            'mode'         => $mode,
            'flp_status'   => $flpRaw,
            'is_proxy'     => $isProxy,
            'is_vpn'       => $isVpn,
            'is_tor'       => $isTor,
            'country_code' => $country,
            'city'         => $city,
            'isp'          => $isp,
            'raw'          => $data,
        ];
    }

    /**
     * checkFraudLabsProConversion() — called in postback.php for auto-approved
     * conversions when FraudLabsPro auto_block mode is active and status='blocked'.
     */
    public static function checkFraudLabsProConversion(
        string $ip, string $conversionId, float $payout, int $affiliateId, string $clickId
    ): bool {
        $result = self::checkFraudLabsPro($ip);

        try {
            Database::query(
                "UPDATE `conversions` SET `fraudlabspro_score`=?,`fraudlabspro_status`=?,`fraudlabspro_flp_status`=? WHERE `conversion_id`=?",
                [$result['score'], $result['status'], $result['flp_status'], $conversionId]
            );
        } catch (\Throwable $_e) {}

        if ($result['status'] !== 'blocked') return false;

        try {
            Database::update('conversions', [
                'is_fraud'    => 1,
                'is_hidden'   => 1,
                'hide_reason' => 'fraudlabspro_blocked',
            ], 'conversion_id=?', [$conversionId]);
            Database::query(
                "UPDATE `affiliates` SET `balance`=`balance`-? WHERE `id`=? AND `balance`>=?",
                [$payout, $affiliateId, $payout]
            );
            Database::update('clicks', ['is_fraud' => 1, 'status' => 'blocked'], 'click_id=?', [$clickId]);
            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'warning',
                'title'       => 'FraudLabs Pro: Conversion Blocked',
                'message'     => 'Conversion ' . substr($conversionId, 0, 8) . '... blocked. IP: ' . $ip . ' | Score: ' . $result['score'],
                'link'        => '/admin/fraud',
            ]);
        } catch (\Exception $e) {}

        return true;
    }

    // ── API connection tests ──────────────────────────────────────────────────

    /** Test IPQualityScore API with a known clean IP (Google DNS 8.8.8.8). */
    public static function testConnection(string $apiKey): array {
        if (empty($apiKey)) {
            return ['ok' => false, 'message' => 'API key is empty.'];
        }

        // Test with a known clean IP (Google's DNS)
        $testIp  = '8.8.8.8';
        $url     = 'https://www.ipqualityscore.com/api/json/ip/'
                 . urlencode($apiKey) . '/' . $testIp
                 . '?strictness=0&fast=1';

        $cfg = Config::get('fraud') ?? [];
        $ch  = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];
        if (!empty($cfg['ipqs_proxy'])) {
            $opts[CURLOPT_PROXY] = trim((string)$cfg['ipqs_proxy']);
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from IPQS API.'];
        }

        $data = json_decode($response, true);
        if (!$data) {
            return ['ok' => false, 'message' => 'Invalid JSON response from IPQS.'];
        }
        if (!($data['success'] ?? false)) {
            $msg = $data['message'] ?? 'Unknown error from IPQS.';
            return ['ok' => false, 'message' => 'IPQS error: ' . $msg];
        }

        return [
            'ok'      => true,
            'message' => 'Connection successful. Test IP (8.8.8.8) score: ' . ($data['fraud_score'] ?? 'n/a'),
        ];
    }

    /** Test IPQuery.io API with Google DNS (8.8.8.8). No API key required. */
    public static function testIPQuery(): array {
        $url = 'https://api.ipquery.io/8.8.8.8';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from IPQuery.io API.'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid JSON response from IPQuery.io.'];
        }

        $risk = isset($data['risk']) && is_array($data['risk']) ? $data['risk'] : $data;
        $score = $risk['risk_score'] ?? 'n/a';

        return [
            'ok'      => true,
            'message' => 'Connection successful. Test IP (8.8.8.8) risk score: ' . $score . '. No API key required.',
        ];
    }

    /**
     * Test Scamalytics API with Google DNS (8.8.8.8).
     * Requires the API User, Server number, and API Key from the Scamalytics
     * credentials page (e.g. user=69d275dd058b8, server=12, key=784521…).
     * The tested URL matches the example shown on the credentials page:
     *   https://api{server}.scamalytics.com/v3/{user}/?key={apiKey}&ip=8.8.8.8
     */
    /**
     * Produce a short, human-friendly summary when an outbound vendor
     * API call returns something that isn't JSON — usually an HTML
     * page injected by a hosting-level WAF or captive portal
     * (TrafficGuardian, Cloudflare, mod_security, ...). Dumping the raw
     * HTML into the UI makes the issue look catastrophic when in fact
     * it's a network policy that the host or WAF needs to relax.
     *
     * The hint identifies the most common WAFs by name and tells the
     * admin who to talk to. The caller still sees a short snippet of
     * the original body for forensics.
     */
    private static function summarizeNonJson(string $vendor, $response): string {
        $body = (string)$response;
        if ($body === '') {
            return $vendor . ' returned an empty body. Likely an outbound firewall on your server is blocking the request — check with your host.';
        }
        $sniff = strtolower(substr($body, 0, 2000));
        $looksLikeHtml = (strpos($sniff, '<!doctype') !== false
                      || strpos($sniff, '<html')    !== false
                      || strpos($sniff, '<head')    !== false);
        $blockedBy = '';
        if      (strpos($sniff, 'trafficguardian')   !== false) $blockedBy = 'TrafficGuardian';
        elseif  (strpos($sniff, 'cloudflare')        !== false) $blockedBy = 'Cloudflare';
        elseif  (strpos($sniff, 'mod_security')      !== false
              || strpos($sniff, 'modsecurity')       !== false) $blockedBy = 'ModSecurity';
        elseif  (strpos($sniff, 'imunify')           !== false) $blockedBy = 'Imunify360';
        elseif  (strpos($sniff, 'sucuri')            !== false) $blockedBy = 'Sucuri WAF';
        elseif  (strpos($sniff, 'access denied')     !== false
              || strpos($sniff, 'forbidden')         !== false
              || strpos($sniff, 'blocked')           !== false) $blockedBy = 'a security filter';
        if ($looksLikeHtml) {
            $base = 'The request to ' . $vendor . ' was intercepted by ' . ($blockedBy ?: 'a network filter on your server')
                  . ' and an HTML page was returned instead of JSON.';
            $action = ' Ask your hosting provider to whitelist outbound HTTPS to this vendor (or disable the filter for outbound API calls).';
            return $base . $action;
        }
        $snippet = preg_replace('/\s+/', ' ', trim($body));
        return 'Non-JSON response from ' . $vendor . ': ' . substr($snippet, 0, 220);
    }

    public static function testScamalytics(string $user, string $server, string $apiKey): array {
        $user   = trim($user);
        $server = preg_replace('/[^0-9]/', '', $server); // keep digits only
        $apiKey = trim($apiKey);

        if ($user   === '') return ['ok' => false, 'message' => 'API User is empty.'];
        if ($server === '') return ['ok' => false, 'message' => 'API Server number is empty.'];
        if ($apiKey === '') return ['ok' => false, 'message' => 'API Key is empty.'];

        $url = 'https://api' . $server . '.scamalytics.com/v3/'
             . urlencode($user) . '/?key=' . urlencode($apiKey) . '&ip=8.8.8.8';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from Scamalytics API.'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => self::summarizeNonJson('Scamalytics', $response)];
        }

        // Scamalytics v3 wraps all data under a "scamalytics" key at the top level.
        if (isset($data['scamalytics']) && is_array($data['scamalytics'])) {
            $data = $data['scamalytics'];
        }

        // v3 error responses carry "ok":0 or a non-zero "code" field
        $ok = $data['ok'] ?? $data['status'] ?? 'ok';
        if ((string)$ok === '0' || (string)$ok === 'error') {
            $msg = $data['error'] ?? $data['message'] ?? json_encode($data);
            return ['ok' => false, 'message' => 'Scamalytics error: ' . $msg];
        }

        // Score field is "scamalytics_score" in the nested object; fall back to "score".
        $score = $data['scamalytics_score'] ?? $data['score'] ?? $data['fraud_score'] ?? null;

        if ($score === null) {
            $keys = implode(', ', array_keys($data));
            $raw  = substr(json_encode($data), 0, 400);
            return ['ok' => false, 'message' => 'API reached but score field not found. Keys: [' . $keys . '] Raw: ' . $raw];
        }

        $risk = $data['scamalytics_risk'] ?? $data['risk'] ?? $data['risk_level'] ?? 'n/a';

        return [
            'ok'      => true,
            'message' => 'Connection successful. Test IP (8.8.8.8) score: ' . $score . ' | Risk: ' . $risk,
        ];
    }

    /** Test ProxyCheck.io API with Google DNS (8.8.8.8). */
    public static function testProxyCheck(string $apiKey): array {
        if (empty($apiKey)) return ['ok' => false, 'message' => 'API key is empty.'];

        $url = 'https://proxycheck.io/v2/8.8.8.8?key=' . urlencode($apiKey) . '&vpn=1&risk=1';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $elapsed  = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        if ($curlErr) return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        if ($httpCode !== 200) return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from ProxyCheck.io.'];

        $data = json_decode($response, true);
        if (!is_array($data)) return ['ok' => false, 'message' => 'Invalid JSON from ProxyCheck.io: ' . substr((string)$response, 0, 200)];

        $status = $data['status'] ?? 'unknown';
        if ($status === 'error') {
            return ['ok' => false, 'message' => 'ProxyCheck error: ' . ($data['message'] ?? json_encode($data))];
        }

        $ipData = $data['8.8.8.8'] ?? null;
        $risk   = $ipData['risk'] ?? 'n/a';
        $proxy  = $ipData['proxy'] ?? 'n/a';

        return [
            'ok'      => true,
            'message' => 'Connection successful (' . round($elapsed * 1000) . ' ms). Test IP (8.8.8.8) risk: ' . $risk . ' | proxy: ' . $proxy,
        ];
    }

    /** Test BotScout API with Google DNS (8.8.8.8). */
    public static function testBotScout(string $apiKey): array {
        if (empty($apiKey)) return ['ok' => false, 'message' => 'API key is empty.'];

        $url = 'https://botscout.com/test/?ip=8.8.8.8&key=' . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'AffiliateTracker/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $elapsed  = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        if ($curlErr) return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        if ($httpCode !== 200) return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from BotScout.'];

        $response = trim((string)$response);
        // Expected: Y|IP|N or N|IP|0  (text response)
        if (substr($response, 0, 2) === '! ') {
            return ['ok' => false, 'message' => 'BotScout error: ' . $response];
        }
        $parts  = explode('|', $response);
        $result = strtoupper($parts[0] ?? '');
        $count  = (int)($parts[2] ?? 0);
        $isBot  = $result === 'Y' ? 'Yes (bot detected)' : 'No (clean)';

        return [
            'ok'      => true,
            'message' => 'Connection successful (' . round($elapsed * 1000) . ' ms). Test IP (8.8.8.8): bot=' . $isBot . ' hits=' . $count,
        ];
    }

    /** Test FraudDefense.io API with Google DNS (8.8.8.8).
     *  Endpoint: GET https://api.frauddefense.io/v1/{IP}?key={API_KEY}
     *  Score field: data.detections.risk */
    public static function testFraudDefense(string $apiKey): array {
        if (empty($apiKey)) return ['ok' => false, 'message' => 'API key is empty.'];

        $url = 'https://api.frauddefense.io/v1/8.8.8.8?key=' . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,    CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/1.0',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $elapsed  = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        if ($curlErr)       return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];
        if ($httpCode !== 200) return ['ok' => false, 'message' => 'HTTP ' . $httpCode . ' from FraudDefense.io.'];

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => self::summarizeNonJson('FraudDefense.io', $response)];
        }

        // API-level error (invalid key etc.) — HTTP 200 with status=error
        if (($data['status'] ?? '') === 'error') {
            return ['ok' => false, 'message' => 'FraudDefense.io: ' . ($data['message'] ?? json_encode($data))];
        }

        // Unwrap data wrapper, then read detections.risk
        $inner      = $data['data'] ?? $data;
        $detections = $inner['detections'] ?? [];
        $risk       = $detections['risk'] ?? null;

        if ($risk === null) {
            $raw = substr(json_encode($data), 0, 400);
            return ['ok' => false, 'message' => 'API reached but detections.risk not found. Raw: ' . $raw];
        }

        $isProxy = ($detections['proxy']  ?? false) ? ' proxy=Y' : '';
        $isVpn   = ($detections['vpn']    ?? false) ? ' vpn=Y'   : '';
        $isTor   = ($detections['tor']    ?? false) ? ' tor=Y'   : '';

        return [
            'ok'      => true,
            'message' => 'Connection successful (' . round($elapsed * 1000) . ' ms). Test IP (8.8.8.8) risk: ' . $risk . $isProxy . $isVpn . $isTor . ' | Credits left: ' . ($inner['lookup_left'] ?? 'n/a'),
        ];
    }

    /** Test FraudLabs Pro API key validity using the v1 GET endpoint.
     *  The v1 endpoint accepts only key + ip and returns a score — perfect
     *  for a lightweight connection test without needing full order fields.
     *  The v2 POST endpoint (used for real screening) requires additional
     *  order data and returns HTTP 400 when only key+ip are sent.
     *  Endpoint: GET https://api.fraudlabspro.com/v1/order/screen */
    public static function testFraudLabsPro(string $apiKey): array {
        if (empty($apiKey)) return ['ok' => false, 'message' => 'API key is empty.'];

        $url = 'https://api.fraudlabspro.com/v1/order/screen?' . http_build_query([
            'key'    => $apiKey,
            'ip'     => '8.8.8.8',
            'format' => 'json',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'AffiliateTracker/2.0',
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $elapsed  = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        if ($curlErr) return ['ok' => false, 'message' => 'Connection error: ' . $curlErr];

        if ($httpCode !== 200) {
            // Try to surface the actual error message from the response body
            $errBody = is_string($response) ? trim(strip_tags($response)) : '';
            $errData = json_decode((string)$response, true);
            $errMsg  = $errData['message'] ?? ($errBody ?: 'HTTP ' . $httpCode);
            return ['ok' => false, 'message' => 'FraudLabs Pro error: ' . $errMsg];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) return ['ok' => false, 'message' => self::summarizeNonJson('FraudLabs Pro', $response)];

        if (!empty($data['error_code'])) {
            return ['ok' => false, 'message' => 'FraudLabs Pro error: ' . ($data['message'] ?? $data['error_code'])];
        }

        $score   = $data['fraudlabspro_score']  ?? 'n/a';
        $status  = $data['fraudlabspro_status'] ?? 'n/a';
        $credits = $data['credits_remaining']   ?? ($data['fraudlabspro_credits'] ?? 'n/a');

        return [
            'ok'      => true,
            'message' => 'Connection successful (' . round($elapsed * 1000) . ' ms). Test IP (8.8.8.8) score: ' . $score . ' | FLP status: ' . $status . ' | Credits left: ' . $credits,
        ];
    }
}
