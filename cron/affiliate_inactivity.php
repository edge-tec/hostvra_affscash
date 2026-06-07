<?php
/**
 * Cron: Affiliate Inactivity Sweep.
 *
 *   - Sends one warning email per affiliate `inactivity_warn_days` days
 *     before the deactivation deadline (when enabled).
 *   - Auto-deactivates active affiliates whose `last_login` is older
 *     than `inactivity_days` by setting users.status = 'suspended' and
 *     stamping users.inactivity_deactivated_at.
 *
 * Design notes:
 *   - Runs in batches of 250 to keep memory + lock pressure low.
 *   - File lock prevents concurrent runs from double-emailing.
 *   - Idempotent: re-running within the same day re-checks the same
 *     window and is a no-op (warn flag + status guard the writes).
 *   - Touches only `users.status` / inactivity timestamps. Login,
 *     tracking, payout and reporting tables are not modified — a
 *     deactivated account simply cannot sign in until reactivated.
 *
 * Crontab:
 *   0 3 * * * php /path/to/project/cron/affiliate_inactivity.php
 */
// Bootstrap-safe: this file can be triggered three ways —
//   1. `php cron/affiliate_inactivity.php` from a real crontab (CLI)
//   2. `php -q ...` from cPanel "Cron Jobs" (CLI)
//   3. HTTPS GET `/cron/affiliate-inactivity?token=…` from a browser or
//      cPanel "Cron URL" (web). In the web path index.php has already
//      defined BASE_PATH and loaded the core classes, so the bootstrap
//      block below uses defined() / require_once guards to stay safe.
defined('BASE_PATH')   || define('BASE_PATH',   dirname(__DIR__));
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . '/config');

require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Mailer.php';

if (method_exists('Config', 'isInitialised') ? !Config::isInitialised() : !class_exists('Config')) {
    Config::init(CONFIG_PATH);
} else {
    // Idempotent re-init in CLI when called from a fresh process.
    try { Config::init(CONFIG_PATH); } catch (\Throwable $_) {}
}
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// stderr exists in CLI; via php-fpm it's piped to the error log. Wrapping
// the writes prevents "fwrite(): supplied resource is not a valid stream"
// notices on hosts that don't expose STDERR to the web SAPI.
$cronLog = static function(string $msg): void {
    if (defined('STDERR') && is_resource(STDERR)) {
        @fwrite(STDERR, $msg);
    } else {
        echo $msg;   // surfaces inside the web response so admins can see it
    }
};

// ── Single-instance lock (cheap belt-and-braces) ─────────────────────────
$lockDir  = BASE_PATH . '/logs';
if (!is_dir($lockDir)) { @mkdir($lockDir, 0775, true); }
$lockFile = $lockDir . '/affiliate_inactivity.lock';
$fh = @fopen($lockFile, 'c');
if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) {
    $cronLog("[inactivity] another instance is running — exit.\n");
    return;   // `return` instead of `exit` so the web shell can finish cleanly
}

// ── Config gate ──────────────────────────────────────────────────────────
$enabled  = (Config::get('config', 'app.inactivity_enabled') ?? '0') === '1';
if (!$enabled) {
    echo "[inactivity] disabled by admin — exit.\n";
    flock($fh, LOCK_UN); fclose($fh); @unlink($lockFile);
    return;
}

$days     = (int)(Config::get('config', 'app.inactivity_days')      ?? 30);
$warnDays = (int)(Config::get('config', 'app.inactivity_warn_days') ?? 3);
$allowedPeriods  = [7,15,30,60,90,120,180,365];
$allowedWarnings = [0,1,2,3,5,7,14];
if (!in_array($days, $allowedPeriods, true))  $days     = 30;
if (!in_array($warnDays, $allowedWarnings, true)) $warnDays = 3;
if ($warnDays >= $days) $warnDays = max(0, $days - 1);

$warnSubject = Config::get('config', 'app.inactivity_warn_subject')
               ?: 'Your account will be deactivated soon';
$warnBody    = Config::get('config', 'app.inactivity_warn_body')
               ?: "Hi {name},\n\nWe noticed you haven't signed in to your affiliate account in {days_inactive} days. Your account will be automatically deactivated in {days_until_deactivation} days unless you log in.\n\nSign in here: {login_url}\n\nThanks,\n{site_name}";

// ── Schema migrations (idempotent) ───────────────────────────────────────
// Two timestamps on the users row keep state per-account without a new table.
try { Database::query("ALTER TABLE users ADD COLUMN inactivity_warned_at      DATETIME NULL"); } catch (\Throwable $_) {}
try { Database::query("ALTER TABLE users ADD COLUMN inactivity_deactivated_at DATETIME NULL"); } catch (\Throwable $_) {}

$siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
$appUrl   = rtrim(Config::get('config', 'app.url') ?? '', '/');
$loginUrl = ($appUrl !== '' ? $appUrl : '') . '/login';

$warnedCount = 0;
$killedCount = 0;
$startTs     = microtime(true);

// ── Step 1: warning emails ───────────────────────────────────────────────
// Affiliates who are about to hit the cut-off in `warnDays` days, whose
// last warning (if any) is older than their last_login (so a login resets
// the warning state automatically).
if ($warnDays > 0) {
    $warnAfterDays = $days - $warnDays;   // e.g. 30 - 3 = 27d
    $lastId = 0;
    while (true) {
        $batch = Database::fetchAll(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.last_login,
                    DATEDIFF(NOW(), u.last_login) AS days_inactive
             FROM users u
             WHERE u.role='affiliate'
               AND u.status='active'
               AND u.id > ?
               AND u.last_login IS NOT NULL
               AND u.last_login < DATE_SUB(NOW(), INTERVAL ? DAY)
               AND u.last_login >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND (u.inactivity_warned_at IS NULL OR u.inactivity_warned_at < u.last_login)
             ORDER BY u.id ASC
             LIMIT 250",
            [$lastId, $warnAfterDays, $days]
        ) ?: [];

        if (empty($batch)) break;

        foreach ($batch as $r) {
            $daysInactive   = (int)$r['days_inactive'];
            $daysUntilKill  = max(0, $days - $daysInactive);
            $name           = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'there';
            $deactivateDate = date('Y-m-d', strtotime("+{$daysUntilKill} days"));

            $vars = [
                '{name}'                    => $name,
                '{email}'                   => $r['email'],
                '{days_inactive}'           => (string)$daysInactive,
                '{days_until_deactivation}' => (string)$daysUntilKill,
                '{deactivation_date}'       => $deactivateDate,
                '{login_url}'               => $loginUrl,
                '{site_name}'               => $siteName,
            ];
            $subject = strtr($warnSubject, $vars);
            $bodyTxt = strtr($warnBody,    $vars);
            $bodyHtml = '<div style="font-family:Segoe UI,Helvetica,Arial,sans-serif;font-size:14px;color:#1E293B;line-height:1.55">'
                       . nl2br(htmlspecialchars($bodyTxt, ENT_QUOTES, 'UTF-8'))
                       . '</div>';

            try {
                Mailer::sendRaw($r['email'], $name, $subject, $bodyHtml, 'inactivity_warning');
            } catch (\Throwable $_) {}

            try {
                Database::query("UPDATE users SET inactivity_warned_at=NOW() WHERE id=?", [$r['id']]);
            } catch (\Throwable $_) {}
            $warnedCount++;
            $lastId = $r['id'];
        }
        if (count($batch) < 250) break;
    }
}

// ── Step 2: deactivate accounts past the cut-off ─────────────────────────
$lastId = 0;
while (true) {
    $batch = Database::fetchAll(
        "SELECT u.id, u.email, u.first_name, u.last_name
         FROM users u
         WHERE u.role='affiliate'
           AND u.status='active'
           AND u.id > ?
           AND u.last_login IS NOT NULL
           AND u.last_login < DATE_SUB(NOW(), INTERVAL ? DAY)
         ORDER BY u.id ASC
         LIMIT 250",
        [$lastId, $days]
    ) ?: [];

    if (empty($batch)) break;

    foreach ($batch as $r) {
        try {
            Database::query(
                "UPDATE users
                 SET status='suspended', inactivity_deactivated_at=NOW()
                 WHERE id=? AND status='active'",
                [$r['id']]
            );
        } catch (\Throwable $_) { continue; }
        $killedCount++;
        $lastId = $r['id'];
    }
    if (count($batch) < 250) break;
}

$elapsed = round(microtime(true) - $startTs, 2);
echo "[inactivity] done · warned={$warnedCount} · deactivated={$killedCount} · period={$days}d · warn={$warnDays}d · elapsed={$elapsed}s\n";

flock($fh, LOCK_UN);
fclose($fh);
@unlink($lockFile);
