<?php
/**
 * Web-accessible cron endpoint for the Fraud Reports system.
 *
 * GET /cron/fraud-reports?token=<secret>
 *
 * Security:
 *  - Token-gated via `fraud_reports.cron_token` in config.
 *  - Auto-provisions token on first request.
 *  - hash_equals() constant-time comparison.
 *  - X-Robots-Tag: noindex,nofollow.
 *
 * Copy the full URL from Admin → Settings → Fraud Reports
 * and paste it into aaPanel → Cron Jobs (URL type).
 */

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// ── Token gate ────────────────────────────────────────────────────────────────
$storedToken = trim((string)Config::get('config', 'fraud_reports.cron_token'));
if ($storedToken === '') {
    try {
        $storedToken = bin2hex(random_bytes(20));
        Config::set('config', 'fraud_reports.cron_token', $storedToken);
    } catch (\Throwable $_) {}
}

$givenToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if ($storedToken === '' || $givenToken === '' || !hash_equals($storedToken, $givenToken)) {
    http_response_code(403);
    echo "403 Forbidden\n";
    echo "A valid token is required. Copy the Cron URL from Admin → Settings → Fraud Reports.\n";
    return;
}

// ── Run limits ────────────────────────────────────────────────────────────────
@set_time_limit(300);
@ini_set('memory_limit', '256M');
ignore_user_abort(true);

echo "=== Fraud Reports Cron triggered via URL ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ── Delegate to the CLI cron script ──────────────────────────────────────────
// BASE_PATH is already defined by the router (index.php).
require BASE_PATH . '/cron/fraud_reports.php';
