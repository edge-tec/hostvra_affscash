<?php
/**
 * Global Postback Test Click Endpoint
 *
 * URL: /test-postback-click?aff={affiliate_code}&click_id={tracker_click_id}
 *
 * When an affiliate clicks this link from their tracker:
 *  1. A real click record is created so the flow is identical to a live offer
 *  2. A conversion is immediately recorded (simulated advertiser postback)
 *  3. The affiliate's global postback URL is fired with {click_id} = tracker click ID
 *  4. The test result is logged to global_postback_test_logs
 *  5. The browser is shown a confirmation page (or redirected)
 *
 * Requires: global_postback_test.enabled = '1' in config
 */
require_once dirname(__DIR__) . '/core/TrackingBootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// ── Guard: feature must be enabled ───────────────────────────────────────
$testEnabled = (Config::get('config', 'global_postback_test.enabled') ?? '0') === '1';
if (!$testEnabled) {
    http_response_code(403);
    echo '<h2>Postback test is currently disabled.</h2>';
    exit;
}

// ── Schema: ensure test log table exists ─────────────────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `global_postback_test_logs` (
        `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`   INT UNSIGNED NOT NULL,
        `affiliate_code` VARCHAR(32)  NOT NULL DEFAULT '',
        `click_id`       CHAR(36)     NOT NULL DEFAULT '',
        `test_link`      TEXT,
        `postback_url`   TEXT,
        `fired_url`      TEXT,
        `http_status`    SMALLINT UNSIGNED NULL,
        `response_body`  TEXT,
        `is_success`     TINYINT(1)   DEFAULT 0,
        `time_ms`        INT UNSIGNED DEFAULT 0,
        `tested_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_affiliate` (`affiliate_id`),
        INDEX `idx_tested_at` (`tested_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

$affCode          = trim(Helpers::get('aff') ?: Helpers::get('aff_id'));
$externalClickId  = trim(Helpers::get('click_id') ?: Helpers::get('sub1') ?: '');
$appUrl           = rtrim(Config::get('config', 'app.url') ?? '', '/');
$testLink         = $appUrl . '/test-postback-click?aff=' . urlencode($affCode) . '&click_id=' . urlencode($externalClickId);

// ── Validate affiliate ────────────────────────────────────────────────────
if (!$affCode) {
    http_response_code(400);
    echo '<h3>Missing affiliate code.</h3>';
    exit;
}

$affiliate = Database::fetchOne(
    "SELECT af.*, u.status as user_status
     FROM affiliates af
     JOIN users u ON u.id = af.user_id
     WHERE af.affiliate_code = ? AND u.status = 'active'",
    [$affCode]
);

if (!$affiliate) {
    http_response_code(404);
    echo '<h3>Affiliate not found or inactive.</h3>';
    exit;
}

$affId         = (int)$affiliate['id'];
$globalPbUrl   = trim($affiliate['global_postback_url'] ?? '');

if (empty($globalPbUrl)) {
    http_response_code(422);
    renderResult('no_url', 'No Global Postback URL', 'This affiliate has no global postback URL configured. Please set one in your postback settings.', '', 0, 0, '');
    exit;
}

// ── Build IDs ─────────────────────────────────────────────────────────────
$internalClickId = Helpers::uuid();
$convId          = Helpers::uuid();
$payout          = 1.00; // fixed test payout

// If no external click ID was passed, use a placeholder so the affiliate's
// tracker can still see something came through — they should pass their
// tracker's click ID macro in the test link to get a real match.
if (empty($externalClickId)) {
    $externalClickId = 'TEST_' . strtoupper(substr(md5($internalClickId), 0, 14));
}

// ── Record a test click ───────────────────────────────────────────────────
try {
    Database::insert('clicks', [
        'click_id'     => $internalClickId,
        'offer_id'     => 0,          // no real offer for test
        'affiliate_id' => $affId,
        'ip_address'   => Helpers::getIp(),
        'user_agent'   => substr(Helpers::getUserAgent(), 0, 1000),
        'country'      => 'XX',
        'device_type'  => 'desktop',
        'sub1'         => $externalClickId,  // store external click ID as sub1
        'payout'       => $payout,
        'revenue'      => $payout,
        'status'       => 'valid',
        'is_unique'    => 1,
        'is_fraud'     => 0,
        'source'       => 'postback_test',
        'traffic_source'      => 'Unknown',
        'traffic_source_type' => 'Unknown',
        'detected_by'         => 'Test Postback',
    ]);
} catch (\Throwable $e) {
    // clicks table may require offer_id NOT NULL on older installs — try with offer_id=1
    try {
        $firstOffer = Database::fetchOne("SELECT id FROM offers WHERE status='active' ORDER BY id LIMIT 1");
        Database::insert('clicks', [
            'click_id'     => $internalClickId,
            'offer_id'     => $firstOffer['id'] ?? 1,
            'affiliate_id' => $affId,
            'ip_address'   => Helpers::getIp(),
            'user_agent'   => substr(Helpers::getUserAgent(), 0, 1000),
            'country'      => 'XX',
            'device_type'  => 'desktop',
            'sub1'         => $externalClickId,
            'payout'       => $payout,
            'revenue'      => $payout,
            'status'       => 'valid',
            'is_unique'    => 1,
            'is_fraud'     => 0,
            'source'       => 'postback_test',
            'traffic_source'      => 'Unknown',
            'traffic_source_type' => 'Unknown',
            'detected_by'         => 'Test Postback',
        ]);
    } catch (\Throwable $e2) {}
}

// ── Build the postback URL with all macros ────────────────────────────────
$firedUrl = Helpers::buildPostbackUrl($globalPbUrl, [
    'click_id'          => $externalClickId,
    'clickid'           => $externalClickId,
    'cid'               => $externalClickId,
    'tid'               => $externalClickId,
    'internal_click_id' => $internalClickId,
    'conversion_id'     => $convId,
    'payout'            => number_format($payout, 4, '.', ''),
    'amount'            => number_format($payout, 4, '.', ''),
    'revenue'           => number_format($payout, 4, '.', ''),
    'txid'              => $convId,
    'transaction_id'    => $convId,
    'status'            => 'approved',
    'offer_id'          => '0',
    'sub1'              => $externalClickId,
    'sub2'              => '',
    'sub3'              => '',
    'sub4'              => '',
    'sub5'              => '',
    'aff_id'            => $affId,
]);

// ── Fire the global postback ──────────────────────────────────────────────
$t0     = microtime(true);
$result = Helpers::firePostback($firedUrl, 'GET');
$ms     = (int)round((microtime(true) - $t0) * 1000);

// Self-loop detection
$appHost = strtolower(parse_url($appUrl, PHP_URL_HOST) ?? '');
$pbHost  = strtolower(parse_url($firedUrl, PHP_URL_HOST) ?? '');
$isSelf  = ($appHost && $pbHost && $pbHost === $appHost);

$isSuccess = $result['status'] > 0 && !$isSelf;

// ── Log the test ──────────────────────────────────────────────────────────
try {
    Database::insert('global_postback_test_logs', [
        'affiliate_id'   => $affId,
        'affiliate_code' => $affCode,
        'click_id'       => $externalClickId,
        'test_link'      => $testLink,
        'postback_url'   => $globalPbUrl,
        'fired_url'      => $firedUrl,
        'http_status'    => $result['status'],
        'response_body'  => substr($result['body'] ?? '', 0, 1000),
        'is_success'     => $isSuccess ? 1 : 0,
        'time_ms'        => $ms,
    ]);
} catch (\Throwable $e) {}

// ── Render result page ────────────────────────────────────────────────────
$statusLabel = $isSelf ? 'Self-loop detected' : ($isSuccess ? 'Success' : 'Failed');
$statusType  = $isSelf ? 'warn' : ($isSuccess ? 'ok' : 'err');

renderResult($statusType, $statusLabel, '', $firedUrl, $result['status'], $ms, $externalClickId, $result['body'] ?? '', $globalPbUrl);
exit;


// ── Helper: render the result HTML page ──────────────────────────────────
function renderResult(string $type, string $title, string $msg, string $firedUrl, int $httpStatus, int $ms, string $clickId, string $responseBody = '', string $templateUrl = ''): void {
    $colors = [
        'ok'   => ['bg' => '#ECFDF5', 'border' => '#A7F3D0', 'icon' => '✓', 'iconColor' => '#065F46', 'titleColor' => '#065F46'],
        'err'  => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'icon' => '✗', 'iconColor' => '#991B1B', 'titleColor' => '#991B1B'],
        'warn' => ['bg' => '#FFF7ED', 'border' => '#FED7AA', 'icon' => '⚠', 'iconColor' => '#92400E', 'titleColor' => '#92400E'],
        'no_url' => ['bg' => '#F8FAFC', 'border' => '#E2E8F0', 'icon' => '○', 'iconColor' => '#64748B', 'titleColor' => '#64748B'],
    ];
    $c = $colors[$type] ?? $colors['err'];
    $appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Postback Test Result</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:#F1F5F9; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .card { background:#fff; border-radius:14px; box-shadow:0 4px 24px rgba(0,0,0,.1); max-width:620px; width:100%; overflow:hidden; }
        .card-top { padding:28px 28px 20px; border-bottom:2px solid #F1F5F9; }
        .badge { display:inline-flex; align-items:center; gap:8px; border-radius:10px; padding:12px 18px; margin-bottom:16px; border:1px solid ' . htmlspecialchars($c['border']) . '; background:' . htmlspecialchars($c['bg']) . '; }
        .badge-icon { font-size:24px; color:' . htmlspecialchars($c['iconColor']) . '; line-height:1; }
        .badge-title { font-size:17px; font-weight:800; color:' . htmlspecialchars($c['titleColor']) . '; }
        .badge-sub { font-size:13px; color:#64748B; margin-top:3px; }
        .row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #F1F5F9; font-size:13px; }
        .row:last-child { border-bottom:none; }
        .row-label { font-weight:700; color:#64748B; min-width:110px; font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
        .row-val { font-family:monospace; font-size:12px; color:#1E293B; word-break:break-all; flex:1; }
        .url-box { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:7px; padding:10px 14px; font-family:monospace; font-size:11px; color:#334155; word-break:break-all; line-height:1.6; margin:12px 0; }
        .resp-box { background:#0F172A; border-radius:7px; padding:10px 14px; font-family:monospace; font-size:11px; color:#94A3B8; max-height:100px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; margin:8px 0 0; }
        .card-body { padding:20px 28px; }
        .card-footer { padding:16px 28px; background:#F8FAFC; border-top:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; border:none; cursor:pointer; }
        .btn-primary { background:#4F46E5; color:#fff; }
        .btn-secondary { background:#F1F5F9; color:#475569; }
        .tip { background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; padding:11px 14px; font-size:12px; color:#1E40AF; margin-top:14px; line-height:1.6; }
    </style>
    </head><body>
    <div class="card">
        <div class="card-top">
            <div class="badge">
                <span class="badge-icon">' . $c['icon'] . '</span>
                <div>
                    <div class="badge-title">' . htmlspecialchars($title) . '</div>';
    if ($msg) echo '<div class="badge-sub">' . htmlspecialchars($msg) . '</div>';
    if ($type === 'ok') echo '<div class="badge-sub">Your tracker received the postback ✓</div>';
    if ($type === 'err') echo '<div class="badge-sub">Could not reach your tracker. Check the postback URL domain is correct.</div>';
    if ($type === 'warn') echo '<div class="badge-sub">Your postback URL points back to this tracker — enter your <strong>external</strong> tracker URL.</div>';
    echo '          </div>
            </div>';

    if ($clickId) echo '<div class="row"><span class="row-label">Click ID sent</span><span class="row-val">' . htmlspecialchars($clickId) . '</span></div>';
    if ($httpStatus) echo '<div class="row"><span class="row-label">HTTP Status</span><span class="row-val">HTTP ' . $httpStatus . ($ms ? ' &nbsp;·&nbsp; ' . $ms . 'ms' : '') . '</span></div>';

    echo '  </div>
        <div class="card-body">';

    if ($templateUrl) {
        echo '<div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px">Postback Template</div>
              <div class="url-box">' . htmlspecialchars($templateUrl) . '</div>';
    }
    if ($firedUrl) {
        echo '<div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px;margin-top:10px">Fired URL</div>
              <div class="url-box">' . htmlspecialchars($firedUrl) . '</div>';
    }
    if ($responseBody) {
        echo '<div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin:10px 0 5px">Response Body</div>
              <div class="resp-box">' . htmlspecialchars(substr($responseBody, 0, 500)) . '</div>';
    }

    if ($type === 'ok' && $httpStatus >= 400) {
        echo '<div class="tip">💡 <strong>Non-2xx HTTP is normal</strong> — your tracker returned HTTP ' . $httpStatus . ' because it has no prior record of this test click ID. What matters is that the URL was reachable. In a real conversion, the click ID will match and the tracker will return 200.</div>';
    }
    if ($type === 'ok') {
        echo '<div class="tip" style="background:#F0FDF4;border-color:#86EFAC;color:#166534;margin-top:10px">✓ <strong>Postback integration confirmed.</strong> Your tracker\'s endpoint is reachable. On real conversions, the postback will fire with the correct click ID and be recorded as a successful conversion.</div>';
    }

    echo '  </div>
        <div class="card-footer">
            <span style="font-size:12px;color:#94A3B8">Postback Test · ' . date('M j, Y H:i') . '</span>
            <div style="display:flex;gap:8px">
                <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
                <a href="' . htmlspecialchars($appUrl) . '/affiliate/postbacks" class="btn btn-primary">Postback Settings</a>
            </div>
        </div>
    </div>
    </body></html>';
}
