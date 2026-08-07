<?php
/**
 * Affiliate Fraud Reports Cron — v2
 *
 * Generates TWO separate reports per affiliate:
 *   1. Fraud Click Report
 *   2. Fraud Conversion Report
 *
 * Each report is:
 *   - Stored in the `fraud_report_logs` table
 *   - Sent as a separate email to the affiliate
 *
 * Usage (CLI):
 *   php /path/to/cron/fraud_reports.php [--force]
 *
 * Usage (Web, via /cron/fraud-reports?token=xxx):
 *   Called from FraudReportsCronController.php
 *
 * Schedule check is enforced: interval_hours (1/6/12/24).
 * Duplicate prevention: per-affiliate timestamp lock in DB.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    require BASE_PATH . '/core/Database.php';
    require BASE_PATH . '/core/Config.php';
    require BASE_PATH . '/core/Mailer.php';
    require BASE_PATH . '/core/Helpers.php';
}

@set_time_limit(300);
@ini_set('memory_limit', '256M');
ignore_user_abort(true);

$cfg     = Config::get('config') ?? [];
$enabled = ($cfg['fraud_reports']['enabled'] ?? '0') === '1';
$isForce = (PHP_SAPI === 'cli' && in_array('--force', $argv ?? []));
$isCli   = (PHP_SAPI === 'cli');

if (!$enabled && !$isForce) {
    _fr_log("Fraud reports are disabled in admin settings. Use --force to override.");
    return;
}

// ── Single-instance lock ──────────────────────────────────────────────────────
$lockFile = BASE_PATH . '/logs/fraud_reports_cron.lock';
if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0755, true);
$fp = @fopen($lockFile, 'c');
if ($fp && !flock($fp, LOCK_EX | LOCK_NB)) {
    _fr_log("Another instance is already running.");
    return;
}

// ── Ensure DB table exists ────────────────────────────────────────────────────
_fr_ensure_table();

// ── Schedule check ────────────────────────────────────────────────────────────
$intervalHours = (int)($cfg['fraud_reports']['interval_hours'] ?? 24);
if (!in_array($intervalHours, [1, 6, 12, 24])) $intervalHours = 24;

$lastRunFile = BASE_PATH . '/logs/fraud_reports_last_run.txt';
$lastRun     = file_exists($lastRunFile) ? (int)@file_get_contents($lastRunFile) : 0;
$now         = time();
$elapsed     = $now - $lastRun;
$minElapsed  = ($intervalHours * 3600) - 300; // 5-min buffer

if (!$isForce && $elapsed < $minElapsed) {
    $nextRun = date('Y-m-d H:i:s', $lastRun + $intervalHours * 3600);
    _fr_log("Too early. Last run: " . date('Y-m-d H:i:s', $lastRun) . ". Next run: {$nextRun}");
    if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
    return;
}

// ── Main processing ───────────────────────────────────────────────────────────
$periodFrom  = date('Y-m-d H:i:s', $now - ($intervalHours * 3600));
$periodTo    = date('Y-m-d H:i:s', $now);
$sendEmail   = ($cfg['fraud_reports']['send_email'] ?? '1') === '1';
$appName     = $cfg['app']['name'] ?? 'Affiliate Network';
$appUrl      = rtrim($cfg['app']['url'] ?? '', '/');
$appLogo     = $cfg['app']['logo'] ?? '';

_fr_log("=== Fraud Reports Cron Started ===");
_fr_log("Period: {$periodFrom} → {$periodTo}");
_fr_log("Interval: {$intervalHours}h | Email: " . ($sendEmail ? 'yes' : 'no'));

// Fetch all active affiliates
$affiliates = Database::fetchAll(
    "SELECT a.id, a.affiliate_code, u.first_name, u.last_name, u.email
     FROM affiliates a
     JOIN users u ON u.id = a.user_id
     WHERE u.status = 'active' AND u.role = 'affiliate'"
) ?: [];

if (empty($affiliates)) {
    _fr_log("No active affiliates found. Exiting.");
    _fr_finish($fp, $lastRunFile, $now);
    return;
}

_fr_log("Processing " . count($affiliates) . " affiliates...");

$sentClicks  = 0;
$sentConvs   = 0;
$savedClick  = 0;
$savedConv   = 0;

foreach ($affiliates as $aff) {
    $affId    = (int)$aff['id'];
    $affCode  = $aff['affiliate_code'];
    $email    = $aff['email'];
    $name     = trim($aff['first_name'] . ' ' . $aff['last_name']);

    // ── CLICK REPORT ──────────────────────────────────────────────────────────
    $clickData = _fr_build_click_report($affId, $periodFrom, $periodTo);
    $clickSaved = _fr_save_report($affId, 'click', $periodFrom, $periodTo, $clickData);

    if ($clickSaved) {
        $savedClick++;
        _fr_log("[{$affCode}] Click report saved (fraud_clicks={$clickData['fraud_clicks']})");

        if ($sendEmail && ($clickData['total_clicks'] > 0 || $clickData['fraud_clicks'] > 0)) {
            $subject = "[{$appName}] Fraud Click Report — " . date('M d, Y', $now);
            $html    = _fr_click_email($aff, $clickData, $periodFrom, $periodTo, $intervalHours, $appName, $appUrl, $appLogo);
            try {
                if (Mailer::sendRaw($email, $name, $subject, $html, 'fraud_report_click')) {
                    $sentClicks++;
                    _fr_log("[{$affCode}] Click report email sent → {$email}");
                }
            } catch (\Throwable $e) {
                _fr_log("[{$affCode}] Click email failed: " . $e->getMessage());
            }
        }
    }

    // ── CONVERSION REPORT ─────────────────────────────────────────────────────
    $convData = _fr_build_conversion_report($affId, $periodFrom, $periodTo);
    $convSaved = _fr_save_report($affId, 'conversion', $periodFrom, $periodTo, $convData);

    if ($convSaved) {
        $savedConv++;
        _fr_log("[{$affCode}] Conversion report saved (fraud_conversions={$convData['fraud_conversions']})");

        if ($sendEmail && ($convData['total_conversions'] > 0 || $convData['fraud_conversions'] > 0)) {
            $subject = "[{$appName}] Fraud Conversion Report — " . date('M d, Y', $now);
            $html    = _fr_conv_email($aff, $convData, $periodFrom, $periodTo, $intervalHours, $appName, $appUrl, $appLogo);
            try {
                if (Mailer::sendRaw($email, $name, $subject, $html, 'fraud_report_conv')) {
                    $sentConvs++;
                    _fr_log("[{$affCode}] Conversion report email sent → {$email}");
                }
            } catch (\Throwable $e) {
                _fr_log("[{$affCode}] Conversion email failed: " . $e->getMessage());
            }
        }
    }
}

_fr_log("=== Done. Saved: {$savedClick} click / {$savedConv} conv reports. Emails: {$sentClicks} click / {$sentConvs} conv. ===");
_fr_finish($fp, $lastRunFile, $now);


// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

function _fr_ensure_table(): void {
    try {
        Database::query("CREATE TABLE IF NOT EXISTS `fraud_report_logs` (
            `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `affiliate_id`  INT UNSIGNED NOT NULL,
            `report_type`   ENUM('click','conversion') NOT NULL,
            `period_from`   DATETIME NOT NULL,
            `period_to`     DATETIME NOT NULL,
            `data_json`     JSON NOT NULL,
            `email_sent`    TINYINT(1) NOT NULL DEFAULT 0,
            `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_aff`  (`affiliate_id`),
            INDEX `idx_type` (`report_type`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (\Throwable $e) {
        _fr_log("Table creation warning: " . $e->getMessage());
    }
}

function _fr_build_click_report(int $affId, string $from, string $to): array {
    $base = "FROM clicks c WHERE c.affiliate_id = ? AND c.clicked_at BETWEEN ? AND ?";
    $p    = [$affId, $from, $to];

    $total         = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$base}", $p)['c'] ?? 0);
    $fraudClicks   = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$base} AND (c.is_fraud=1 OR c.fraud_score>=50)", $p)['c'] ?? 0);
    $blockedClicks = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$base} AND c.status='blocked'", $p)['c'] ?? 0);

    // Bot detection via user_agent (same patterns as BotDetectionController)
    $botPatterns = ['bot','crawler','spider','scraper','curl','wget','python','libwww','scrapy',
                    'Go-http','Java/','okhttp','HeadlessChrome','PhantomJS','Selenium','WebDriver'];
    $uaLikes  = implode(' OR ', array_fill(0, count($botPatterns), 'c.user_agent LIKE ?'));
    $uaParams = array_map(fn($pat) => "%{$pat}%", $botPatterns);

    $botClicks = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c {$base} AND ({$uaLikes})",
        array_merge($p, $uaParams)
    )['c'] ?? 0);

    // High fraud-score clicks as proxy/VPN/datacenter proxy measure
    // (The actual is_vpn / is_proxy columns do not exist in this schema — use fraud_score tiers)
    $highFraud  = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$base} AND c.fraud_score >= 75", $p)['c'] ?? 0);
    $medFraud   = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$base} AND c.fraud_score >= 50 AND c.fraud_score < 75", $p)['c'] ?? 0);

    $quality = $total > 0 ? round((($total - $fraudClicks) / $total) * 100, 1) : 100.0;

    return [
        'total_clicks'      => $total,
        'fraud_clicks'      => $fraudClicks,
        'blocked_clicks'    => $blockedClicks,
        'bot_clicks'        => $botClicks,
        'high_risk_clicks'  => $highFraud,
        'medium_risk_clicks'=> $medFraud,
        'click_quality'     => $quality,
    ];
}

function _fr_build_conversion_report(int $affId, string $from, string $to): array {
    $baseConv = "FROM conversions cv WHERE cv.affiliate_id = ? AND cv.converted_at BETWEEN ? AND ? AND cv.is_hidden = 0 AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = cv.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)";
    $p        = [$affId, $from, $to];

    $total        = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$baseConv}", $p)['c'] ?? 0);
    $fraudConvs   = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$baseConv} AND (cv.is_fraud=1 OR cv.fraud_score>=50)", $p)['c'] ?? 0);
    $invalidLeads = (int)(Database::fetchOne("SELECT COUNT(*) AS c {$baseConv} AND cv.status='rejected' AND cv.hide_reason IS NOT NULL", $p)['c'] ?? 0);
    // Suspicious: click-to-conv under 30 seconds
    $suspicious   = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         WHERE cv.affiliate_id = ? AND cv.converted_at BETWEEN ? AND ?
           AND cv.is_hidden = 0 AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%')
           AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)
           AND ck.id IS NOT NULL AND TIMESTAMPDIFF(SECOND, ck.clicked_at, cv.converted_at) < 30
           AND TIMESTAMPDIFF(SECOND, ck.clicked_at, cv.converted_at) >= 0",
        $p
    )['c'] ?? 0);

    $fraudRate = $total > 0 ? round(($fraudConvs / $total) * 100, 2) : 0.0;
    $quality   = $total > 0 ? round((($total - $fraudConvs) / $total) * 100, 1) : 100.0;

    return [
        'total_conversions'  => $total,
        'fraud_conversions'  => $fraudConvs,
        'fraud_rate'         => $fraudRate,
        'invalid_leads'      => $invalidLeads,
        'suspicious_activity'=> $suspicious,
        'conversion_quality' => $quality,
    ];
}

function _fr_save_report(int $affId, string $type, string $from, string $to, array $data): bool {
    try {
        Database::query(
            "INSERT INTO fraud_report_logs (affiliate_id, report_type, period_from, period_to, data_json, email_sent, created_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW())",
            [$affId, $type, $from, $to, json_encode($data, JSON_UNESCAPED_UNICODE)]
        );
        return true;
    } catch (\Throwable $e) {
        _fr_log("Save error ({$type}/{$affId}): " . $e->getMessage());
        return false;
    }
}

function _fr_click_email(array $aff, array $d, string $from, string $to, int $hours, string $appName, string $appUrl, string $appLogo): string {
    $name    = htmlspecialchars(trim($aff['first_name'] . ' ' . $aff['last_name']), ENT_QUOTES);
    $code    = htmlspecialchars($aff['affiliate_code'], ENT_QUOTES);
    $period  = date('M d, H:i', strtotime($from)) . ' – ' . date('M d, Y H:i', strtotime($to));
    $quality = $d['click_quality'];
    $qColor  = $quality >= 80 ? '#10B981' : ($quality >= 50 ? '#F59E0B' : '#EF4444');

    $rows = [
        ['Total Clicks',          number_format($d['total_clicks']),        '#1E293B'],
        ['🚫 Fraud Clicks',        number_format($d['fraud_clicks']),        '#EF4444'],
        ['🔒 Blocked Clicks',      number_format($d['blocked_clicks']),      '#EF4444'],
        ['🤖 Bot Traffic',         number_format($d['bot_clicks']),          '#DC2626'],
        ['⚠️ High Risk Clicks',   number_format($d['high_risk_clicks']),    '#F59E0B'],
        ['🟡 Medium Risk Clicks',  number_format($d['medium_risk_clicks']),  '#F59E0B'],
    ];

    $tableRows = '';
    foreach ($rows as [$label, $val, $color]) {
        $tableRows .= "<tr><td style=\"padding:12px 16px;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600\">{$label}</td><td style=\"padding:12px 16px;border-bottom:1px solid #F1F5F9;text-align:right;font-weight:700;color:{$color}\">{$val}</td></tr>";
    }

    return _fr_email_wrap($appUrl, "Fraud Click Report", <<<HTML
<p style="color:#475569;font-size:15px">Hello <b>{$name}</b>,</p>
<p style="color:#475569;font-size:14px;line-height:1.6">This is your automated <b>Fraud Click Report</b> for affiliate account <b>{$code}</b>.<br>Period covered: <b>{$period}</b> ({$hours}h window).</p>
<div style="background:#fff;border:1px solid #E2E8F0;border-radius:8px;overflow:hidden;margin:20px 0">
  <table style="width:100%;border-collapse:collapse;text-align:left">{$tableRows}</table>
</div>
<div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 18px;margin:16px 0">
  <div style="font-size:13px;color:#166534;font-weight:700;margin-bottom:4px">Click Quality Score</div>
  <div style="font-size:28px;font-weight:800;color:{$qColor}">{$quality}%</div>
  <div style="font-size:12px;color:#64748B;margin-top:4px">Higher is better. Score below 70% requires attention.</div>
</div>
<p style="color:#475569;font-size:13px;line-height:1.6">Please ensure your traffic sources comply with our network guidelines. If you believe this report is incorrect, contact your affiliate manager.</p>
<a href="{$appUrl}/affiliate/fraud-reports?tab=clicks" style="display:inline-block;background:#4F46E5;color:#fff;text-decoration:none;padding:10px 22px;border-radius:6px;font-weight:600;font-size:14px;margin-top:8px">View Full Click Report →</a>
HTML, $appName, $appLogo);
}

function _fr_conv_email(array $aff, array $d, string $from, string $to, int $hours, string $appName, string $appUrl, string $appLogo): string {
    $name    = htmlspecialchars(trim($aff['first_name'] . ' ' . $aff['last_name']), ENT_QUOTES);
    $code    = htmlspecialchars($aff['affiliate_code'], ENT_QUOTES);
    $period  = date('M d, H:i', strtotime($from)) . ' – ' . date('M d, Y H:i', strtotime($to));
    $quality = $d['conversion_quality'];
    $qColor  = $quality >= 80 ? '#10B981' : ($quality >= 50 ? '#F59E0B' : '#EF4444');

    $rows = [
        ['Total Conversions',         number_format($d['total_conversions']),  '#1E293B'],
        ['🚫 Fraud Conversions',       number_format($d['fraud_conversions']),  '#EF4444'],
        ['📊 Fraud Rate',              $d['fraud_rate'] . '%',                 '#EF4444'],
        ['❌ Invalid Leads',           number_format($d['invalid_leads']),      '#F59E0B'],
        ['⚡ Suspicious Activity',    number_format($d['suspicious_activity']), '#DC2626'],
    ];

    $tableRows = '';
    foreach ($rows as [$label, $val, $color]) {
        $tableRows .= "<tr><td style=\"padding:12px 16px;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600\">{$label}</td><td style=\"padding:12px 16px;border-bottom:1px solid #F1F5F9;text-align:right;font-weight:700;color:{$color}\">{$val}</td></tr>";
    }

    return _fr_email_wrap($appUrl, "Fraud Conversion Report", <<<HTML
<p style="color:#475569;font-size:15px">Hello <b>{$name}</b>,</p>
<p style="color:#475569;font-size:14px;line-height:1.6">This is your automated <b>Fraud Conversion Report</b> for affiliate account <b>{$code}</b>.<br>Period covered: <b>{$period}</b> ({$hours}h window).</p>
<div style="background:#fff;border:1px solid #E2E8F0;border-radius:8px;overflow:hidden;margin:20px 0">
  <table style="width:100%;border-collapse:collapse;text-align:left">{$tableRows}</table>
</div>
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:14px 18px;margin:16px 0">
  <div style="font-size:13px;color:#92400E;font-weight:700;margin-bottom:4px">Conversion Quality Score</div>
  <div style="font-size:28px;font-weight:800;color:{$qColor}">{$quality}%</div>
  <div style="font-size:12px;color:#64748B;margin-top:4px">Higher is better. Score below 70% may result in account review.</div>
</div>
<p style="color:#475569;font-size:13px;line-height:1.6">Continued fraudulent conversion activity may result in payout deductions or account suspension. Contact your affiliate manager if you have questions.</p>
<a href="{$appUrl}/affiliate/fraud-reports?tab=conversions" style="display:inline-block;background:#DC2626;color:#fff;text-decoration:none;padding:10px 22px;border-radius:6px;font-weight:600;font-size:14px;margin-top:8px">View Full Conversion Report →</a>
HTML, $appName, $appLogo);
}

function _fr_email_wrap(string $appUrl, string $title, string $body, string $appName, string $appLogo): string {
    $appEsc = htmlspecialchars($appName, ENT_QUOTES);
    
    $headerBranding = '';
    if (!empty($appLogo)) {
        $logoUrl = filter_var($appLogo, FILTER_VALIDATE_URL) ? $appLogo : rtrim($appUrl, '/') . '/' . ltrim($appLogo, '/');
        $logoEsc = htmlspecialchars($logoUrl, ENT_QUOTES);
        $headerBranding = "<img src=\"{$logoEsc}\" alt=\"{$appEsc}\" style=\"max-height:40px;max-width:200px;object-fit:contain\">";
    } else {
        $headerBranding = "<div style=\"font-size:20px;font-weight:800;color:#fff;letter-spacing:.02em\">{$appEsc}</div>";
    }

        return <<<HTML
<div style="background:#F8FAFC;padding:40px 20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(148,163,184,0.15);">
    <div style="padding:32px;text-align:center;background:linear-gradient(135deg, rgba(167,139,250,0.15) 0%, rgba(124,58,237,0.15) 100%);border-bottom:1px solid rgba(124,58,237,0.2);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
      {$headerBranding}
      <div style="font-size:14px;color:#4F46E5;margin-top:8px;font-weight:600;letter-spacing:0.5px">{$title}</div>
    </div>
    <div style="padding:32px;color:#334155;font-size:15px;line-height:1.7;">
      {$body}
    </div>
    <div style="padding:20px 32px;background:#F8FAFC;text-align:center;border-top:1px solid #E2E8F0;">
      <p style="color:#64748B;font-size:12px;margin:0">{$appEsc} Fraud Detection System &bull; This is an automated report.</p>
    </div>
  </div>
</div>
HTML;
}

function _fr_log(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if (PHP_SAPI === 'cli') echo $line . "\n";
    else                    echo $line . "<br>\n";

    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents($logDir . '/fraud_reports_cron.log', $line . "\n", FILE_APPEND | LOCK_EX);
}

function _fr_finish($fp, string $lastRunFile, int $now): void {
    @file_put_contents($lastRunFile, $now);
    if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
}
