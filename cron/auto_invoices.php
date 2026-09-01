<?php
/**
 * Cron: Automatic Affiliate Invoice Generator & Billing Scheduler
 *
 * Runs hourly to evaluate active affiliate schedules and generate invoices:
 *  0 * * * * php /path/to/cron/auto_invoices.php > /dev/null 2>&1
 *
 * Can also be triggered via web endpoint:
 *  GET /cron/auto-invoices?token=<secret_token>
 */

defined('BASE_PATH')   || define('BASE_PATH',   dirname(__DIR__));
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . '/config');

require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Mailer.php';
require_once BASE_PATH . '/core/InvoicePDF.php';
require_once BASE_PATH . '/core/AutoInvoiceEngine.php';

try { Config::init(CONFIG_PATH); } catch (\Throwable $_) {}
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// Ensure execution finishes even if connection is closed
ignore_user_abort(true);
@set_time_limit(600);
@ini_set('memory_limit', '512M');

// Prevent concurrent runs
$lockFile = BASE_PATH . '/logs/auto_invoices.lock';
if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0777, true);
$lockFp = fopen($lockFile, 'w+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Automatic invoice generator is already running. Exiting.\n";
    exit(0);
}

echo "=== Affscash Automatic Invoice Scheduler ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . " UTC\n";

try {
    $result = AutoInvoiceEngine::runAutoGeneration('cron');

    echo "Status: " . ($result['status'] ?? 'ok') . "\n";
    echo "Generated: " . ($result['generated'] ?? 0) . " invoices\n";
    echo "Skipped: " . ($result['skipped'] ?? 0) . "\n";
    echo "Total Invoiced Amount: $" . number_format((float)($result['total_amt'] ?? 0), 2) . "\n";
    if (!empty($result['next_run'])) {
        echo "Next Scheduled Run: " . $result['next_run'] . " UTC\n";
    }

    if (!empty($result['logs'])) {
        echo "\nDetails:\n";
        foreach ($result['logs'] as $log) {
            echo " - " . $log . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log('[AutoInvoicesCron] Error: ' . $e->getMessage());
}

flock($lockFp, LOCK_UN);
fclose($lockFp);
echo "=== Execution Complete ===\n";
