<?php
/**
 * Web shell for the fraud_scan cron.
 *
 * Lets admins schedule the IPQS / multi-provider fraud sweep from cPanel
 * "Cron Jobs" (URL mode) or fire it manually from a browser, when SSH
 * crontab access is unavailable.
 *
 * Behaviour:
 *   GET /cron/fraud-scan?token=<secret>
 *       → runs the same scan as cron/fraud_scan.php and prints the log.
 *
 * Security:
 *   - Token gated against `app.fraud_scan_cron_token` (auto-generated on
 *     first read). Without it: HTTP 403 + minimal response.
 *   - hash_equals() for constant-time comparison.
 *   - X-Robots-Tag: noindex,nofollow.
 *   - Read-only outside its expected DB writes (scores + checked_at).
 */

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$storedToken = trim((string)Config::get('config', 'app.fraud_scan_cron_token'));
if ($storedToken === '') {
    // Auto-provision a token on first request so cPanel admins don't have
    // to dig for one. The Fraud / IPQS settings screen displays it.
    try {
        $storedToken = bin2hex(random_bytes(16));
        Config::set('config', 'app.fraud_scan_cron_token', $storedToken);
    } catch (\Throwable $_) {}
}
$givenToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if ($storedToken === '' || $givenToken === '' || !hash_equals($storedToken, $givenToken)) {
    http_response_code(403);
    echo "Forbidden.\n";
    echo "The fraud-scan token is required. Generate / copy it from\n";
    echo "Admin → Settings → Fraud / IPQS (auto-created on first call).\n";
    return;
}

@set_time_limit(180);
@ini_set('memory_limit', '256M');
ignore_user_abort(true);

// Hand off to the existing CLI cron file. It uses defined() guards so
// running it under a web SAPI does not redefine BASE_PATH / CONFIG_PATH.
require BASE_PATH . '/cron/fraud_scan.php';
