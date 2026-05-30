<?php
/**
 * Web shell for the affiliate inactivity cron.
 *
 * Lets admins schedule the inactivity scan from cPanel "Cron Jobs" (URL
 * mode) or trigger it manually from a browser, instead of needing SSH
 * access to crontab. Behaviour:
 *
 *   GET /cron/affiliate-inactivity?token=<secret>
 *       → runs the same scan as cron/affiliate_inactivity.php and
 *         returns its plain-text log as the response body.
 *
 * Security:
 *   - Requires a hex token stored at `app.inactivity_cron_token`. Without
 *     it, the endpoint returns 403 with no further information leakage.
 *   - Uses hash_equals() for constant-time comparison so the token cannot
 *     be guessed via timing.
 *   - Cron is gated by the admin enable switch — even with a valid token,
 *     a disabled feature produces a no-op.
 *   - No request payload is read; this is read-only against config + a
 *     small, indexed write window on users.status.
 */

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// ── Schema migration is in the cron file; here we just validate the token.
$storedToken = trim((string)Config::get('config', 'app.inactivity_cron_token'));
$givenToken  = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if ($storedToken === '' || $givenToken === '' || !hash_equals($storedToken, $givenToken)) {
    http_response_code(403);
    echo "Forbidden.\n";
    echo "Open Admin → Settings → Affiliate Inactivity to copy the correct trigger URL.\n";
    return;
}

// Long-running scans on big datasets can blow past the 30 s php-fpm default.
@set_time_limit(120);
@ini_set('memory_limit', '256M');
ignore_user_abort(true);

// Hand off to the existing CLI cron — it's been refactored to be safe
// for both contexts (defined() guards, require_once, no STDERR writes
// when STDERR isn't available, `return` instead of `exit`).
require BASE_PATH . '/cron/affiliate_inactivity.php';
