<?php
/**
 * Web-accessible cron endpoint for Automatic Invoice Generator & Scheduler.
 *
 * GET /cron/auto-invoices?token=<secret>
 *
 * Security:
 *  - Token-gated via `fraud_reports.cron_token` in config (or default).
 *  - hash_equals() constant-time comparison.
 *  - X-Robots-Tag: noindex,nofollow.
 */

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// Token gate
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
    echo "A valid token is required to trigger the automatic invoice generator.\n";
    return;
}

@set_time_limit(600);
@ini_set('memory_limit', '512M');
ignore_user_abort(true);

echo "=== Auto Invoice Cron triggered via Web ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

require BASE_PATH . '/cron/auto_invoices.php';
