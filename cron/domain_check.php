<?php
/**
 * Cron: Background tracking domain DNS + server status checker
 * Run every 15 minutes: * /15 * * * * php /path/to/cron/domain_check.php
 *
 * Checks every tracking domain for:
 *  1. DNS A record pointing to this server
 *  2. HTTP/HTTPS reachability (server configured)
 *  3. SSL certificate status
 *
 * Updates tracking_domains table with results so the admin panel
 * shows live status without manual checks.
 */
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/DomainManager.php';
Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

DomainManager::ensureColumns();

$domains = Database::fetchAll(
    "SELECT id, domain, dns_status, server_status, last_check_at FROM tracking_domains WHERE is_active=1",
    []
);

$checked = 0;
foreach ($domains as $row) {
    // Skip if checked within the last 10 minutes and already fully active
    if ($row['server_status'] === 'configured' && $row['dns_status'] === 'pointing' && $row['last_check_at']) {
        $age = time() - strtotime($row['last_check_at']);
        if ($age < 600) continue; // skip if checked < 10 min ago and everything is fine
    }

    $result = DomainManager::checkDomain($row['domain']);
    $checked++;

    echo "[{$row['domain']}] DNS={$result['dns_status']} Server={$result['server_status']} SSL={$result['ssl_status']}"
       . ($result['message'] ? " — {$result['message']}" : '') . "\n";

    // Rate limit DNS lookups
    usleep(500000); // 500ms between domains
}

echo "Checked {$checked} of " . count($domains) . " domains.\n";
