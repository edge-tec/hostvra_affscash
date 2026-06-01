<?php
/**
 * Affiliate Fraud Report Cron
 * Generates and sends an automated fraud report to active affiliates
 * based on the schedule configured in Admin Settings.
 *
 * To run automatically, add this to your server's crontab:
 * 0 * * * * php /path/to/cron/affiliate_fraud_report.php >/dev/null 2>&1
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Mailer.php';
require BASE_PATH . '/core/Helpers.php';

// Single instance locking
$lockFile = BASE_PATH . '/logs/affiliate_fraud_report.lock';
$fp = fopen($lockFile, 'c');
if (!flock($fp, LOCK_EX | LOCK_NB)) {
    die("Another instance is already running.\n");
}

$cfg = Config::get('config');
$enabled = ($cfg['fraud_reports']['enabled'] ?? '0') === '1';

// Allow manual override via command line arg `--force`
$isForce = (isset($argv[1]) && $argv[1] === '--force');

if (!$enabled && !$isForce) {
    die("Fraud reports are disabled in settings.\n");
}

$intervalDays = (int)($cfg['fraud_reports']['interval_days'] ?? 7);
if ($intervalDays < 1) $intervalDays = 7;

$targetHour = (int)($cfg['fraud_reports']['run_hour'] ?? 8);

$currentHour = (int)date('H');
if ($currentHour < $targetHour && !$isForce) {
    die("Not time to run yet (Target: {$targetHour}:00, Current: {$currentHour}:00).\n");
}

$lastRunFile = BASE_PATH . '/logs/fraud_report_last_run.txt';
$lastRun = file_exists($lastRunFile) ? (int)file_get_contents($lastRunFile) : 0;
$now = time();

// Only run if enough time has passed (give a 1 hour buffer for cron timing variations)
if ($now - $lastRun < ($intervalDays * 86400 - 3600) && !$isForce) {
    die("Already ran within the interval period.\n");
}

echo "Generating Affiliate Fraud Reports...\n";

// Proceed to generate reports
$dateFrom = date('Y-m-d H:i:s', $now - ($intervalDays * 86400));
$dateTo = date('Y-m-d H:i:s', $now);

// Fetch active affiliates with their user emails
$affiliates = Database::fetchAll("SELECT a.id, a.affiliate_code, u.first_name, u.email FROM affiliates a JOIN users u ON a.user_id = u.id WHERE u.status = 'active'");

if (!$affiliates) {
    die("No active affiliates found.\n");
}

$sentCount = 0;
$appName = $cfg['app']['name'] ?? 'Affiliate Network';

foreach ($affiliates as $aff) {
    $affId = $aff['id'];
    
    // Check blocked clicks
    $clickCount = Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE affiliate_id = ? AND status = 'blocked' AND clicked_at BETWEEN ? AND ?", [$affId, $dateFrom, $dateTo])['c'] ?? 0;
    
    // Check blocked/fraud conversions
    $convStats = Database::fetchOne("SELECT COUNT(*) AS c, SUM(payout) as total_payout FROM conversions WHERE affiliate_id = ? AND is_fraud = 1 AND hide_reason LIKE '%_blocked%' AND converted_at BETWEEN ? AND ?", [$affId, $dateFrom, $dateTo]);
    $convCount = $convStats['c'] ?? 0;
    $totalPayout = $convStats['total_payout'] ?? 0.00;
    
    // If no fraud occurred for this affiliate in this period, don't spam them
    if ($clickCount == 0 && $convCount == 0) {
        continue;
    }
    
    // Generate email
    $subject = "Automated Fraud Report - " . date('M d, Y');
    
    $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #F8FAFC; padding: 24px; border-radius: 12px;">';
    $html .= '<h2 style="color: #1E293B; text-align: center; margin-top: 0;">Fraud Activity Report</h2>';
    $html .= '<p style="color: #475569; font-size: 15px;">Hello <b>' . Helpers::e($aff['first_name']) . '</b>,</p>';
    $html .= '<p style="color: #475569; font-size: 15px; line-height: 1.6;">This is an automated summary of fraudulent activity detected on your affiliate account (<b>' . $aff['affiliate_code'] . '</b>) during the past ' . $intervalDays . ' days (' . date('M d', strtotime($dateFrom)) . ' - ' . date('M d', strtotime($dateTo)) . ').</p>';
    
    $html .= '<div style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 8px; padding: 0; margin: 24px 0; overflow: hidden;">';
    $html .= '<table style="width: 100%; border-collapse: collapse; text-align: left;">';
    $html .= '<tr><td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; color: #64748B; font-weight: 600;">Blocked Fraudulent Clicks</td><td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; text-align: right; color: #EF4444; font-weight: 700;">' . number_format($clickCount) . '</td></tr>';
    $html .= '<tr><td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; color: #64748B; font-weight: 600;">Blocked / Reversed Conversions</td><td style="padding: 14px 16px; border-bottom: 1px solid #F1F5F9; text-align: right; color: #EF4444; font-weight: 700;">' . number_format($convCount) . '</td></tr>';
    $html .= '<tr><td style="padding: 14px 16px; color: #64748B; font-weight: 600;">Reversed Payout Amount</td><td style="padding: 14px 16px; text-align: right; color: #EF4444; font-weight: 700;">$' . number_format($totalPayout, 2) . '</td></tr>';
    $html .= '</table>';
    $html .= '</div>';
    
    $html .= '<p style="color: #475569; font-size: 14px; line-height: 1.6;">Please ensure that your traffic sources comply with our network guidelines. Continued fraudulent activity may result in account suspension. If you believe this is an error, please contact your affiliate manager.</p>';
    $html .= '<p style="color: #94A3B8; font-size: 12px; text-align: center; margin-top: 30px; margin-bottom: 0;">' . Helpers::e($appName) . ' Fraud Detection System</p>';
    $html .= '</div>';
    
    try {
        if (Mailer::sendRaw($aff['email'], $aff['first_name'], $subject, $html, 'fraud_report')) {
            $sentCount++;
            echo "Sent report to {$aff['email']}\n";
        }
    } catch (\Throwable $e) {
        echo "Failed to send to {$aff['email']}: " . $e->getMessage() . "\n";
    }
}

file_put_contents($lastRunFile, $now);
echo "Fraud report cron completed successfully. Total emails sent: {$sentCount}.\n";

flock($fp, LOCK_UN);
fclose($fp);
