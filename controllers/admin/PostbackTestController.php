<?php
Auth::check('admin');
$pageTitle = 'Postback Test';

// ── Start output buffer immediately so any PHP errors don't corrupt JSON ──
ob_start();

// ── Schema: create test log table once, silently ─────────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `postback_test_logs` (
        `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`   INT UNSIGNED NOT NULL,
        `affiliate_code` VARCHAR(32)  NOT NULL DEFAULT '',
        `offer_id`       INT UNSIGNED NOT NULL DEFAULT 0,
        `postback_id`    INT UNSIGNED NULL,
        `click_id`       CHAR(36)     NOT NULL DEFAULT '',
        `payout`         DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `tracking_link`  TEXT,
        `postback_url`   TEXT,
        `fired_url`      TEXT,
        `http_status`    SMALLINT UNSIGNED NULL,
        `response_body`  TEXT,
        `is_success`     TINYINT(1)   DEFAULT 0,
        `is_real`        TINYINT(1)   DEFAULT 0,
        `time_ms`        INT UNSIGNED DEFAULT 0,
        `tested_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_affiliate` (`affiliate_id`),
        INDEX `idx_tested_at` (`tested_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

// ── Helper: send JSON and exit cleanly ────────────────────────────────────
function jsonOut(array $data): never {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── AJAX: Get offers for a selected affiliate ─────────────────────────────
if (($_GET['action'] ?? '') === 'get_offers') {
    $affId = (int)($_GET['affiliate_id'] ?? 0);
    if (!$affId) { jsonOut(['offers' => []]); }
    try {
        $offers = Database::fetchAll(
            "SELECT o.id, o.name, o.payout_amount, o.revenue_amount, o.payout_type
             FROM offers o
             JOIN affiliate_offers ao ON ao.offer_id=o.id
             WHERE ao.affiliate_id=? AND ao.status='approved' AND o.status='active'
             ORDER BY o.name",
            [$affId]
        );
        jsonOut(['offers' => $offers]);
    } catch (\Throwable $e) {
        jsonOut(['error' => $e->getMessage()]);
    }
}

// ── AJAX: Get postback URLs for affiliate+offer ───────────────────────────
if (($_GET['action'] ?? '') === 'get_postbacks') {
    $affId   = (int)($_GET['affiliate_id'] ?? 0);
    $offerId = (int)($_GET['offer_id'] ?? 0);
    if (!$affId) { jsonOut(['postbacks' => []]); }
    try {
        $pbs = Database::fetchAll(
            "SELECT id, url, method, event, status, offer_id
             FROM postbacks
             WHERE affiliate_id=? AND status='active' AND event='conversion'
               AND (offer_id IS NULL OR offer_id=?)
             ORDER BY id",
            [$affId, $offerId]
        );
        jsonOut(['postbacks' => $pbs]);
    } catch (\Throwable $e) {
        jsonOut(['postbacks' => []]);
    }
}

// ── AJAX: Recent test logs ────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'get_logs') {
    try {
        $logs = Database::fetchAll("SELECT * FROM postback_test_logs ORDER BY tested_at DESC LIMIT 10");
    } catch (\Throwable $e) { $logs = []; }
    jsonOut(['logs' => $logs]);
}

// ── AJAX: Run the full conversion test ───────────────────────────────────
if (($_GET['action'] ?? '') === 'run_test') {

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        jsonOut(['error' => 'Invalid CSRF token. Please refresh the page and try again.']);
    }

    $affId       = (int)($_POST['affiliate_id']  ?? 0);
    $offerId     = (int)($_POST['offer_id']       ?? 0);
    $trackingUrl = trim($_POST['tracking_link']   ?? '');
    $payout      = (float)($_POST['payout']       ?? 0);
    $sub1        = trim($_POST['sub1']            ?? '');
    $sub2        = trim($_POST['sub2']            ?? '');

    if (!$affId)       { jsonOut(['error' => 'Please select an affiliate.']); }
    if (!$offerId)     { jsonOut(['error' => 'Please select an offer.']); }
    if (!$trackingUrl) { jsonOut(['error' => 'Please paste the affiliate tracking link.']); }

    // Load affiliate
    try {
        $affiliate = Database::fetchOne(
            "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
             FROM affiliates af JOIN users u ON u.id=af.user_id
             WHERE af.id=? AND u.status='active'",
            [$affId]
        );
    } catch (\Throwable $e) { jsonOut(['error' => 'DB error loading affiliate: ' . $e->getMessage()]); }
    if (!$affiliate) { jsonOut(['error' => 'Affiliate not found or inactive.']); }

    // Load offer
    try {
        $offer = Database::fetchOne(
            "SELECT id, name, payout_amount, revenue_amount, payout_type, advertiser_id
             FROM offers WHERE id=? AND status='active'",
            [$offerId]
        );
    } catch (\Throwable $e) { jsonOut(['error' => 'DB error loading offer: ' . $e->getMessage()]); }
    if (!$offer) { jsonOut(['error' => 'Offer not found or inactive.']); }

    // Verify access
    try {
        $access = Database::fetchOne(
            "SELECT id FROM affiliate_offers WHERE affiliate_id=? AND offer_id=? AND status='approved'",
            [$affId, $offerId]
        );
    } catch (\Throwable $e) { $access = null; }
    if (!$access) { jsonOut(['error' => 'This affiliate does not have approved access to this offer.']); }

    // Resolve payout
    if ($payout <= 0) $payout = (float)$offer['payout_amount'];
    $revenue  = (float)$offer['revenue_amount'];
    $clickId  = Helpers::uuid();
    $convId   = Helpers::uuid();
    $steps    = [];

    // STEP 1 — record tracking link
    $steps[] = [
        'step'   => 1,
        'label'  => 'Affiliate Tracking Link Recorded',
        'detail' => $trackingUrl,
        'status' => 'ok',
    ];

    // STEP 2 — create click record
    try {
        Database::insert('clicks', [
            'click_id'     => $clickId,
            'offer_id'     => $offerId,
            'affiliate_id' => $affId,
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent'   => 'PostbackTest/Admin',
            'country'      => 'XX',
            'device_type'  => 'desktop',
            'sub1'         => $sub1 ?: 'TEST',
            'sub2'         => $sub2 ?: '',
            'payout'       => $payout,
            'revenue'      => $revenue,
            'status'       => 'valid',
            'is_unique'    => 1,
            'is_fraud'     => 0,
            'referer'      => $trackingUrl,
        ]);
        $steps[] = ['step' => 2, 'label' => 'Click ID Generated', 'detail' => "click_id: {$clickId}", 'status' => 'ok'];
    } catch (\Throwable $e) {
        jsonOut(['error' => 'Failed to create click record: ' . $e->getMessage()]);
    }

    // STEP 3 — record conversion
    // Build conversion data using only columns guaranteed to exist in schema
    $convData = [
        'conversion_id'  => $convId,
        'click_id'       => $clickId,
        'offer_id'       => $offerId,
        'affiliate_id'   => $affId,
        'advertiser_id'  => $offer['advertiser_id'],
        'payout'         => $payout,
        'revenue'        => $revenue,
        'currency'       => 'USD',
        'status'         => 'approved',
        'transaction_id' => null,
        'goal_name'      => 'Postback Test',
        'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'approved_at'    => date('Y-m-d H:i:s'),
    ];

    // Optionally add is_hidden / hide_reason / postback_sent if columns exist
    try {
        $colCheck = Database::fetchOne(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='conversions' AND COLUMN_NAME='is_hidden'"
        );
        if ($colCheck) {
            $convData['is_hidden']   = 0;
            $convData['hide_reason'] = '';
        }
    } catch (\Throwable $e) {}
    $convData['postback_sent'] = 0;

    // Insert conversion (no transaction wrapper — avoids "no active transaction" errors)
    try {
        Database::insert('conversions', $convData);
    } catch (\Throwable $e) {
        // Clean up the click we just created
        try { Database::query("DELETE FROM clicks WHERE click_id=?", [$clickId]); } catch (\Throwable $e2) {}
        jsonOut(['error' => 'Failed to record conversion: ' . $e->getMessage()]);
    }

    // Credit affiliate balance
    try {
        Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?", [$payout, $affId]);
    } catch (\Throwable $e) {}

    // Update daily stats
    try {
        Database::upsertStats(date('Y-m-d'), $affId, $offerId, ['conversions'=>1,'approved'=>1,'payout'=>$payout,'revenue'=>$revenue]);
    } catch (\Throwable $e) {}

    // Referral commission
    try { Referral::creditCommission($convId, $affId, $payout); } catch (\Throwable $e) {}

    $steps[] = [
        'step'   => 3,
        'label'  => 'Conversion Recorded & Balance Credited',
        'detail' => "conversion_id: {$convId} · \$" . number_format($payout, 2) . " credited to affiliate balance",
        'status' => 'ok',
    ];

    // STEP 4 — fire ALL postbacks via PostbackFirer
    // (fires affiliate-level postbacks + global postback URL + admin global postbacks
    //  and marks postback_sent=1 — identical to the real conversion flow)
    require_once BASE_PATH . '/core/PostbackFirer.php';
    $convForPostback = [
        'conversion_id' => $convId,
        'click_id'      => $clickId,
        'offer_id'      => $offerId,
        'affiliate_id'  => $affId,
        'payout'        => $payout,
        'revenue'       => $revenue,
        'status'        => 'approved',
        'source'        => '',
        'sub1'          => $sub1 ?: 'TEST',
        'sub2'          => $sub2 ?: '',
        'sub3'          => '',
        'sub4'          => '',
        'sub5'          => '',
    ];
    try {
        PostbackFirer::fireAll($convForPostback, 'approved', true);
    } catch (\Throwable $pbEx) {
        PostbackFirer::log('[PostbackTest] fireAll error: ' . $pbEx->getMessage());
    }

    // Read back postback_logs to show fired URLs + results in the UI
    $postbackResults = [];
    try {
        $firedLogs = Database::fetchAll(
            "SELECT pl.*, pb.url as template_url, pb.method
             FROM postback_logs pl
             LEFT JOIN postbacks pb ON pb.id = pl.postback_id
             WHERE pl.conversion_id = ?
             ORDER BY pl.id",
            [$convId]
        );
        $appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?? '';
        foreach ($firedLogs as $log) {
            $pbHost = parse_url($log['fired_url'], PHP_URL_HOST) ?? '';
            $isSelf = ($appHost && $pbHost && strtolower($pbHost) === strtolower($appHost));
            $postbackResults[] = [
                'postback_id'  => $log['postback_id'],
                'method'       => $log['method'] ?? 'GET',
                'template_url' => $log['template_url'] ?? ($log['postback_id'] == 0 ? '[Global Postback URL]' : ''),
                'fired_url'    => $log['fired_url'],
                'http_status'  => $log['http_status'],
                'response'     => substr($log['response_body'] ?? '', 0, 500),
                'success'      => ((int)$log['http_status'] > 0 && !$isSelf),
                'real_http_ok' => (bool)$log['is_success'],
                'time_ms'      => 0,
                'is_self'      => $isSelf,
            ];
        }
    } catch (\Throwable $e) {}

    $pbCount = count($postbackResults);
    $steps[] = [
        'step'   => 4,
        'label'  => 'Postbacks Fired to Affiliate Tracker',
        'detail' => $pbCount > 0
            ? "{$pbCount} postback(s) fired (affiliate + global + admin-global). Non-2xx HTTP is normal in test mode."
            : 'No postbacks configured for this affiliate. Add one under Manage Postbacks.',
        'status' => $pbCount > 0 ? 'ok' : 'warn',
    ];

    // STEP 5 — Fraud score
    require_once BASE_PATH . '/core/FraudIQ.php';
    $fraudScore = 0;
    try {
        FraudIQ::checkConversion($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $clickId, $convId, $payout, $affId);
        $firedClick = Database::fetchOne("SELECT fraud_score FROM clicks WHERE click_id=?", [$clickId]);
        $fraudScore = (int)($firedClick['fraud_score'] ?? 0);
    } catch (\Throwable $e) {}
    $steps[] = [
        'step'   => 5,
        'label'  => 'Fraud Score Calculated',
        'detail' => "Fraud score: {$fraudScore}/100" . ($fraudScore >= 75 ? ' ⚠ HIGH RISK' : ($fraudScore >= 40 ? ' ⚠ Medium risk' : ' ✓ Clean')),
        'status' => $fraudScore >= 75 ? 'warn' : 'ok',
    ];

    // Admin notification
    try {
        Database::insert('notifications', [
            'user_id'     => null,
            'target_role' => 'admin',
            'type'        => 'info',
            'title'       => 'Postback Test Conversion',
            'message'     => "Test conversion for {$affiliate['name']} — {$offer['name']} — \${$payout}",
            'link'        => '/admin/conversions',
        ]);
    } catch (\Throwable $e) {}

    jsonOut([
        'success'          => true,
        'affiliate'        => $affiliate['name'],
        'affiliate_code'   => $affiliate['affiliate_code'],
        'offer'            => $offer['name'],
        'offer_id'         => $offerId,
        'click_id'         => $clickId,
        'conv_id'          => $convId,
        'payout'           => $payout,
        'sub1'             => $sub1,
        'is_real'          => true,
        'steps'            => $steps,
        'postback_results' => $postbackResults,
        'tracking_link'    => $trackingUrl,
        'no_postbacks'     => empty($postbackResults),
    ]);
}

// ── Page load ──────────────────────────────────────────────────────────────
ob_clean(); // clear buffer before outputting page HTML

try {
    $recentLogs = Database::fetchAll("SELECT * FROM postback_test_logs ORDER BY tested_at DESC LIMIT 8");
} catch (\Throwable $e) { $recentLogs = []; }

$affiliateList = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE u.status='active'
     ORDER BY name"
);

require BASE_PATH . '/views/admin/postbacks/test.php';
