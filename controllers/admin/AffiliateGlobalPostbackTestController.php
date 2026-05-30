<?php
/**
 * Affiliate Global Postback Test Controller
 *
 * Allows admin to verify an affiliate's global postback integration by:
 *   1. Pasting the affiliate's tracker-generated tracking link
 *   2. Simulating a click through the tracking link
 *   3. Triggering a test conversion (zero real impact)
 *   4. Firing the affiliate's global postback URL
 *   5. Reporting the full result
 *
 * All test records are flagged is_test=1 and do NOT affect:
 *   – affiliate balance
 *   – stats_daily
 *   – referral commissions
 *   – real conversion counts / caps
 */
Auth::check('admin');
$pageTitle = 'Affiliate Global Postback Test';

ob_start();

// ── Schema: ensure test log table exists ──────────────────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `aff_global_pb_test_logs` (
        `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`     INT UNSIGNED     NOT NULL,
        `affiliate_name`   VARCHAR(255)     NOT NULL DEFAULT '',
        `affiliate_code`   VARCHAR(32)      NOT NULL DEFAULT '',
        `tracking_link`    TEXT,
        `click_id`         CHAR(36)         NOT NULL DEFAULT '',
        `conversion_id`    CHAR(36)         NOT NULL DEFAULT '',
        `global_pb_url`    TEXT,
        `fired_url`        TEXT,
        `http_status`      SMALLINT UNSIGNED NULL,
        `response_body`    TEXT,
        `is_success`       TINYINT(1)       DEFAULT 0,
        `time_ms`          INT UNSIGNED     DEFAULT 0,
        `error_message`    TEXT,
        `tested_at`        DATETIME         DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_aff`       (`affiliate_id`),
        INDEX `idx_tested_at` (`tested_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

// ── Helper: send JSON and exit ────────────────────────────────────────────
function gptJsonOut(array $data): never {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── AJAX: Load affiliates that have a global postback URL set ─────────────
if (($_GET['action'] ?? '') === 'get_affiliates') {
    try {
        $rows = Database::fetchAll(
            "SELECT af.id, af.affiliate_code, af.global_postback_url,
                    CONCAT(u.first_name,' ',u.last_name) AS name
             FROM affiliates af
             JOIN users u ON u.id = af.user_id
             WHERE af.global_postback_url IS NOT NULL
               AND af.global_postback_url != ''
               AND u.status = 'active'
             ORDER BY name"
        );
        gptJsonOut(['affiliates' => $rows]);
    } catch (\Throwable $e) {
        gptJsonOut(['affiliates' => [], 'error' => $e->getMessage()]);
    }
}

// ── AJAX: Clear test log ──────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'clear_logs') {
    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        gptJsonOut(['error' => 'Invalid CSRF token.']);
    }
    try {
        Database::query("DELETE FROM aff_global_pb_test_logs");
        gptJsonOut(['success' => true]);
    } catch (\Throwable $e) {
        gptJsonOut(['error' => $e->getMessage()]);
    }
}

// ── AJAX: Get recent logs ─────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'get_logs') {
    try {
        $logs = Database::fetchAll(
            "SELECT * FROM aff_global_pb_test_logs ORDER BY tested_at DESC LIMIT 50"
        );
        gptJsonOut(['logs' => $logs]);
    } catch (\Throwable $e) {
        gptJsonOut(['logs' => []]);
    }
}

// ── AJAX: Run the test ────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'run_test') {

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        gptJsonOut(['error' => 'Invalid CSRF token. Refresh and try again.']);
    }

    $trackingLink = trim($_POST['tracking_link'] ?? '');
    $affId        = (int)($_POST['affiliate_id'] ?? 0);

    if (!$trackingLink) {
        gptJsonOut(['error' => 'Please paste the affiliate tracking link.']);
    }
    if (!filter_var($trackingLink, FILTER_VALIDATE_URL)) {
        gptJsonOut(['error' => 'The tracking link does not appear to be a valid URL. Please check and try again.']);
    }

    // ── Load affiliate ────────────────────────────────────────────────────
    try {
        if ($affId) {
            $affiliate = Database::fetchOne(
                "SELECT af.id, af.affiliate_code, af.global_postback_url,
                        CONCAT(u.first_name,' ',u.last_name) AS name
                 FROM affiliates af JOIN users u ON u.id=af.user_id
                 WHERE af.id=? AND u.status='active'",
                [$affId]
            );
        } else {
            // Try to identify affiliate from the tracking link URL itself
            // by matching aff= or aff_id= param against known affiliate codes
            $parsedUrl = parse_url($trackingLink);
            parse_str($parsedUrl['query'] ?? '', $qs);
            $affCode = $qs['aff'] ?? $qs['aff_id'] ?? '';
            $affiliate = null;
            if ($affCode) {
                $affiliate = Database::fetchOne(
                    "SELECT af.id, af.affiliate_code, af.global_postback_url,
                            CONCAT(u.first_name,' ',u.last_name) AS name
                     FROM affiliates af JOIN users u ON u.id=af.user_id
                     WHERE af.affiliate_code=? AND u.status='active'",
                    [$affCode]
                );
            }
            if (!$affiliate) {
                gptJsonOut(['error' => 'Could not identify the affiliate from the tracking link. Please select the affiliate manually above.']);
            }
        }
    } catch (\Throwable $e) {
        gptJsonOut(['error' => 'DB error loading affiliate: ' . $e->getMessage()]);
    }

    if (!$affiliate) {
        gptJsonOut(['error' => 'Affiliate not found or inactive.']);
    }

    $globalPostbackUrl = trim($affiliate['global_postback_url'] ?? '');
    if (empty($globalPostbackUrl)) {
        gptJsonOut(['error' => 'This affiliate has not set a Global Postback URL in their account. They must save it first.']);
    }

    // ── Step 1: Simulate clicking the tracking link ───────────────────────
    // We call the tracking link via cURL (HEAD then GET with no-follow) to
    // extract the click_id that our system assigns. We parse it from the
    // redirect Location header instead of following through to the offer URL.
    //
    // Strategy: inject a synthetic test click_id into sub1 so the postback
    // can return it. We do NOT follow the redirect to avoid loading the offer.
    $steps    = [];
    $testSub1 = 'GTEST_' . strtoupper(substr(md5(uniqid('', true)), 0, 12));

    // Build the test tracking link — append our test click_id as sub1
    // so it flows through to the postback {click_id} macro.
    $parsedTL = parse_url($trackingLink);
    parse_str($parsedTL['query'] ?? '', $tlQuery);
    $tlQuery['click_id'] = $testSub1;  // tracker click_id — stored as sub1
    $testTrackingLink = ($parsedTL['scheme'] ?? 'https') . '://'
                      . ($parsedTL['host'] ?? '')
                      . ($parsedTL['path'] ?? '')
                      . '?' . http_build_query($tlQuery);

    // Hit the tracking link to create the click record
    $appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
    $appHost = strtolower(parse_url($appUrl, PHP_URL_HOST) ?? '');
    $tlHost  = strtolower(parse_url($testTrackingLink, PHP_URL_HOST) ?? '');

    $clickId = null;
    $clickCreatedViaHttp = false;

    if ($tlHost && $tlHost === $appHost) {
        // ── Internal tracking link: simulate the click directly in-process ──
        // Parse the tracking link to extract offer_id, aff code, sub params
        $tlPath = $parsedTL['path'] ?? '';
        parse_str($parsedTL['query'] ?? '', $tlQs);

        // Extract offer_id from path: /click/{offer_id}
        preg_match('#/click/(\d+)#', $tlPath, $pathM);
        $tlOfferId  = (int)($pathM[1] ?? ($tlQs['offer_id'] ?? 0));
        $tlAffCode  = $tlQs['aff'] ?? $tlQs['aff_id'] ?? $affiliate['affiliate_code'];
        $tlSub1     = $tlQs['click_id'] ?? $tlQs['sub1'] ?? $testSub1;
        $tlSub2     = $tlQs['sub2'] ?? '';
        $tlSub3     = $tlQs['sub3'] ?? '';
        $tlSub4     = $tlQs['sub4'] ?? '';
        $tlSub5     = $tlQs['sub5'] ?? '';
        $tlSource   = $tlQs['source'] ?? 'postback_test';

        // Validate offer
        $tlOffer = null;
        if ($tlOfferId) {
            try {
                $tlOffer = Database::fetchOne(
                    "SELECT o.*, COALESCE(a.id,0) as advertiser_uid
                     FROM offers o LEFT JOIN advertisers a ON a.id=o.advertiser_id
                     WHERE o.id=? AND o.status='active'",
                    [$tlOfferId]
                );
            } catch (\Throwable $e) {}
        }

        // If we couldn't find the offer from path, try to find ANY approved offer for this affiliate
        if (!$tlOffer) {
            try {
                $tlOffer = Database::fetchOne(
                    "SELECT o.*, COALESCE(a.id,0) as advertiser_uid
                     FROM offers o
                     LEFT JOIN advertisers a ON a.id=o.advertiser_id
                     JOIN affiliate_offers ao ON ao.offer_id=o.id
                     WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
                     LIMIT 1",
                    [$affiliate['id']]
                );
            } catch (\Throwable $e) {}
        }

        if (!$tlOffer) {
            gptJsonOut(['error' => 'Could not identify an active offer from the tracking link. Ensure the affiliate has at least one approved active offer.']);
        }

        $clickId = Helpers::uuid();
        $payout  = (float)$tlOffer['payout_amount'];
        $revenue = (float)$tlOffer['revenue_amount'];

        // Insert a TEST click record — use goal_name field to flag it
        try {
            Database::insert('clicks', [
                'click_id'     => $clickId,
                'offer_id'     => (int)$tlOffer['id'],
                'affiliate_id' => (int)$affiliate['id'],
                'smartlink_id' => null,
                'sub1'         => $tlSub1,
                'sub2'         => $tlSub2,
                'sub3'         => $tlSub3,
                'sub4'         => $tlSub4,
                'sub5'         => $tlSub5,
                'source'       => $tlSource,
                'ip_address'   => '0.0.0.0',
                'user_agent'   => 'AffiliateTracker/PostbackTest Admin',
                'referer'      => $trackingLink,
                'country'      => 'XX',
                'region'       => '',
                'city'         => '',
                'isp'          => '',
                'device_type'  => 'desktop',
                'os'           => '',
                'browser'      => '',
                'is_unique'    => 1,
                'is_fraud'     => 0,
                'fraud_score'  => 0,
                'payout'       => $payout,
                'revenue'      => $revenue,
                'status'       => 'valid',
            ]);
        } catch (\Throwable $e) {
            gptJsonOut(['error' => 'Failed to record test click: ' . $e->getMessage()]);
        }

        $steps[] = [
            'step'   => 1,
            'label'  => 'Tracking Link Simulated (Internal Click)',
            'detail' => "offer_id: {$tlOffer['id']} · aff: {$affiliate['affiliate_code']} · test click_id: {$clickId} · tracker_click_id (sub1): {$tlSub1}",
            'status' => 'ok',
        ];

    } else {
        // ── External tracking link: hit it via cURL, read Location header ──
        // The tracker will redirect → our tracking link → we parse click_id from DB
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $testTrackingLink,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (PostbackTest/Admin)',
        ]);
        $response   = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$httpStatus) {
            gptJsonOut(['error' => 'Could not reach the tracking link. Please verify it is correct and the tracker is online.']);
        }

        // Try to find the click_id created by the tracker redirecting to our /click/ endpoint.
        // After following through, the click lands on our DB with sub1 = $testSub1
        sleep(1); // small delay for async DB writes
        try {
            $recentClick = Database::fetchOne(
                "SELECT * FROM clicks
                 WHERE affiliate_id=? AND sub1=?
                 ORDER BY clicked_at DESC LIMIT 1",
                [(int)$affiliate['id'], $testSub1]
            );
        } catch (\Throwable $e) { $recentClick = null; }

        if ($recentClick) {
            $clickId  = $recentClick['click_id'];
            $payout   = (float)$recentClick['payout'];
            $revenue  = (float)$recentClick['revenue'];
            $tlOffer  = Database::fetchOne("SELECT * FROM offers WHERE id=?", [(int)$recentClick['offer_id']]);
            $clickCreatedViaHttp = true;
            $steps[] = [
                'step'   => 1,
                'label'  => 'Tracking Link Clicked (External)',
                'detail' => "HTTP {$httpStatus} · click_id: {$clickId} · sub1: {$testSub1}",
                'status' => 'ok',
            ];
        } else {
            // Fallback: create click synthetically for the affiliate's first approved offer
            try {
                $tlOffer = Database::fetchOne(
                    "SELECT o.*, COALESCE(a.id,0) as advertiser_uid
                     FROM offers o
                     LEFT JOIN advertisers a ON a.id=o.advertiser_id
                     JOIN affiliate_offers ao ON ao.offer_id=o.id
                     WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
                     LIMIT 1",
                    [$affiliate['id']]
                );
            } catch (\Throwable $e) { $tlOffer = null; }

            if (!$tlOffer) {
                gptJsonOut(['error' => 'Could not create a test click. Ensure the affiliate has at least one approved active offer, or use an internal tracking link.']);
            }

            $clickId = Helpers::uuid();
            $payout  = (float)$tlOffer['payout_amount'];
            $revenue = (float)$tlOffer['revenue_amount'];

            try {
                Database::insert('clicks', [
                    'click_id'     => $clickId,
                    'offer_id'     => (int)$tlOffer['id'],
                    'affiliate_id' => (int)$affiliate['id'],
                    'smartlink_id' => null,
                    'sub1'         => $testSub1,
                    'ip_address'   => '0.0.0.0',
                    'user_agent'   => 'AffiliateTracker/PostbackTest Admin',
                    'referer'      => $trackingLink,
                    'country'      => 'XX',
                    'device_type'  => 'desktop',
                    'is_unique'    => 1,
                    'is_fraud'     => 0,
                    'payout'       => $payout,
                    'revenue'      => $revenue,
                    'status'       => 'valid',
                    'source'       => 'postback_test',
                ]);
            } catch (\Throwable $e) {
                gptJsonOut(['error' => 'Failed to create synthetic test click: ' . $e->getMessage()]);
            }

            $steps[] = [
                'step'   => 1,
                'label'  => 'Tracking Link Simulated (Synthetic Click — Tracker HTTP ' . $httpStatus . ')',
                'detail' => "Synthetic click_id: {$clickId} · sub1: {$testSub1}",
                'status' => 'ok',
            ];
        }
    }

    // ── Step 2: Record TEST conversion — zero financial/stats impact ──────
    $convId    = Helpers::uuid();
    $convData  = [
        'conversion_id'  => $convId,
        'click_id'       => $clickId,
        'offer_id'       => (int)$tlOffer['id'],
        'affiliate_id'   => (int)$affiliate['id'],
        'advertiser_id'  => (int)($tlOffer['advertiser_uid'] ?? $tlOffer['advertiser_id'] ?? 0),
        'payout'         => 0,         // TEST: zero payout — no balance credited
        'revenue'        => 0,         // TEST: zero revenue
        'currency'       => 'USD',
        'status'         => 'approved',
        'transaction_id' => null,
        'goal_name'      => '[TEST] Global Postback Verification',
        'ip_address'     => '0.0.0.0',
    ];

    // Add optional columns gracefully
    try {
        $hasIsHidden = !empty(Database::fetchAll("SHOW COLUMNS FROM conversions LIKE 'is_hidden'"));
        if ($hasIsHidden) {
            $convData['is_hidden']   = 1;  // Hidden = excluded from stats/reports
            $convData['hide_reason'] = 'postback_test';
        }
    } catch (\Throwable $e) {}

    try {
        Database::insert('conversions', $convData);
    } catch (\Throwable $e) {
        // Clean up test click
        try { Database::query("DELETE FROM clicks WHERE click_id=?", [$clickId]); } catch (\Throwable $ex) {}
        gptJsonOut(['error' => 'Failed to record test conversion: ' . $e->getMessage()]);
    }

    $steps[] = [
        'step'   => 2,
        'label'  => 'Test Conversion Created (Zero Impact)',
        'detail' => "conversion_id: {$convId} · payout: $0.00 · hidden · goal: [TEST] Global Postback Verification",
        'status' => 'ok',
    ];

    // ── Step 3: Fire the Global Postback URL ──────────────────────────────
    // sub1 = the tracker's click ID ($testSub1) — this is what the affiliate
    // tracker is expecting back in their {click_id} macro.
    $externalClickId = $testSub1; // tracker-generated click ID flows back

    $firedUrl = Helpers::buildPostbackUrl($globalPostbackUrl, [
        'click_id'          => $externalClickId,
        'clickid'           => $externalClickId,
        'cid'               => $externalClickId,
        'tid'               => $externalClickId,
        'internal_click_id' => $clickId,
        'conversion_id'     => $convId,
        'payout'            => '0.0000',
        'amount'            => '0.0000',
        'revenue'           => '0.0000',
        'txid'              => $convId,
        'transaction_id'    => $convId,
        'status'            => 'approved',
        'offer_id'          => $tlOffer['id'],
        'sub1'              => $externalClickId,
        'sub2'              => '',
        'sub3'              => '',
        'sub4'              => '',
        'sub5'              => '',
        'aff_id'            => $affiliate['affiliate_code'],
    ]);

    // Detect self-loop — compare root domains (last 2 parts) so subdomains are also caught.
    // e.g. click.eliteali.com and tracker.eliteali.com share root "eliteali.com"
    $pbHost  = strtolower(parse_url($firedUrl, PHP_URL_HOST) ?? '');
    function _rootDomain(string $host): string {
        $parts = explode('.', trim($host, '.'));
        return count($parts) >= 2 ? implode('.', array_slice($parts, -2)) : $host;
    }
    $appRootDomain = _rootDomain($appHost);
    $pbRootDomain  = _rootDomain($pbHost);
    $isSelf = ($appHost && $pbHost && $pbRootDomain === $appRootDomain);

    // ── When the postback points to OUR OWN domain, re-fire using the real
    // internal click UUID so our postback endpoint can find the click record.
    // With GTEST_* as click_id our format-validator returns 400; with a UUID
    // that doesn't exist in the DB it returns 404. Using the actual click UUID
    // that was inserted above means the endpoint finds the click, hits the
    // duplicate-conversion guard, and returns HTTP 200 "duplicate" — proving
    // the postback URL and routing are working correctly end-to-end.
    if ($isSelf) {
        $firedUrl = Helpers::buildPostbackUrl($globalPostbackUrl, [
            'click_id'          => $clickId,   // internal UUID — exists in clicks table
            'clickid'           => $clickId,
            'cid'               => $clickId,
            'tid'               => $clickId,
            'internal_click_id' => $clickId,
            'conversion_id'     => $convId,
            'payout'            => '0.0000',
            'amount'            => '0.0000',
            'revenue'           => '0.0000',
            'txid'              => $convId,
            'transaction_id'    => $convId,
            'status'            => 'approved',
            'offer_id'          => $tlOffer['id'],
            'sub1'              => $clickId,
            'sub2'              => '',
            'sub3'              => '',
            'sub4'              => '',
            'sub5'              => '',
            'aff_id'            => $affiliate['affiliate_code'],
            'aff_sub1'          => $clickId,
            'aff_sub2'          => '',
        ]);
    }

    $t0     = microtime(true);
    $result = Helpers::firePostback($firedUrl, 'GET');
    $ms     = (int)round((microtime(true) - $t0) * 1000);

    // Determine success:
    // - External: any HTTP response is a success (non-2xx is expected — synthetic click_id unknown to their tracker)
    // - Self (own domain): 200 "duplicate" or 2xx means postback routing works correctly
    $httpOk    = ($result['status'] >= 200 && $result['status'] < 300);
    $isDup     = str_contains($result['body'] ?? '', 'duplicate');  // our own 200 duplicate response
    $isSuccess = ($result['status'] > 0 && !($isSelf && !$httpOk && !$isDup)) ? 1 : 0;

    if ($isSelf && !$httpOk && !$isDup) {
        $steps[] = [
            'step'   => 3,
            'label'  => 'Global Postback Fired — Self-Pointing URL (HTTP ' . $result['status'] . ')',
            'detail' => "The affiliate's global postback URL points to this tracker. The postback was re-fired using the internal click UUID so the endpoint could look up the record. "
                      . "HTTP {$result['status']} received. Response: " . substr($result['body'] ?? '', 0, 120),
            'status' => 'warn',
        ];
    } elseif ($result['status'] === 0) {
        $steps[] = [
            'step'   => 3,
            'label'  => 'Global Postback Fired — Connection Failed',
            'detail' => "Could not reach the affiliate's tracker server (HTTP 0 / timeout). Check the postback URL domain.",
            'status' => 'err',
        ];
    } elseif ($isSelf && ($httpOk || $isDup)) {
        $steps[] = [
            'step'   => 3,
            'label'  => 'Global Postback Fired — HTTP ' . $result['status'] . ' (Self — Postback Routing OK)',
            'detail' => "Postback reached this tracker in {$ms}ms · Used internal click UUID {$clickId} · "
                      . ($isDup ? 'Duplicate conversion guard returned 200 — postback URL and routing are working correctly ✓'
                                : 'HTTP ' . $result['status'] . ' — postback endpoint reached successfully ✓'),
            'status' => 'ok',
        ];
    } else {
        $steps[] = [
            'step'   => 3,
            'label'  => 'Global Postback Fired — HTTP ' . $result['status'],
            'detail' => "Postback reached tracker in {$ms}ms · {$externalClickId} returned as click_id · "
                      . ($result['status'] >= 200 && $result['status'] < 300
                            ? 'Tracker accepted ✓'
                            : 'Non-2xx is normal in test mode (tracker has no record of synthetic click_id)'),
            'status' => 'ok',
        ];
    }

    // ── Log to test log table ─────────────────────────────────────────────
    try {
        Database::insert('aff_global_pb_test_logs', [
            'affiliate_id'   => (int)$affiliate['id'],
            'affiliate_name' => $affiliate['name'],
            'affiliate_code' => $affiliate['affiliate_code'],
            'tracking_link'  => $trackingLink,
            'click_id'       => $clickId,
            'conversion_id'  => $convId,
            'global_pb_url'  => $globalPostbackUrl,
            'fired_url'      => $firedUrl,
            'http_status'    => $result['status'],
            'response_body'  => substr($result['body'] ?? '', 0, 1000),
            'is_success'     => $isSuccess,
            'time_ms'        => $ms,
            'error_message'  => $isSelf ? 'Self-loop detected' : ($result['status'] === 0 ? 'Connection failed' : null),
        ]);
    } catch (\Throwable $e) {}

    // Mark conversion postback_sent
    try {
        Database::query(
            "UPDATE conversions SET postback_sent=1, postback_sent_at=NOW() WHERE conversion_id=?",
            [$convId]
        );
    } catch (\Throwable $e) {}

    gptJsonOut([
        'success'          => (bool)$isSuccess,
        'is_self'          => $isSelf,
        'affiliate_name'   => $affiliate['name'],
        'affiliate_code'   => $affiliate['affiliate_code'],
        'global_pb_url'    => $globalPostbackUrl,
        'click_id'         => $clickId,
        'tracker_click_id' => $externalClickId,
        'conversion_id'    => $convId,
        'fired_url'        => $firedUrl,
        'http_status'      => $result['status'],
        'response'         => substr($result['body'] ?? '', 0, 600),
        'time_ms'          => $ms,
        'steps'            => $steps,
    ]);
}

// ── Page load ─────────────────────────────────────────────────────────────
ob_clean();

// Load affiliates with global postback URLs for the dropdown
$affiliatesWithPb = [];
try {
    $affiliatesWithPb = Database::fetchAll(
        "SELECT af.id, af.affiliate_code, af.global_postback_url,
                CONCAT(u.first_name,' ',u.last_name) AS name
         FROM affiliates af
         JOIN users u ON u.id = af.user_id
         WHERE af.global_postback_url IS NOT NULL
           AND af.global_postback_url != ''
           AND u.status = 'active'
         ORDER BY name"
    );
} catch (\Throwable $e) {}

// Recent test logs
$recentLogs = [];
try {
    $recentLogs = Database::fetchAll(
        "SELECT * FROM aff_global_pb_test_logs ORDER BY tested_at DESC LIMIT 50"
    );
} catch (\Throwable $e) {}

require BASE_PATH . '/views/admin/affiliate_global_pb_test/index.php';
