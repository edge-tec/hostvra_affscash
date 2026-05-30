<?php
/**
 * Cron: auto_migrate.php
 *
 * Applies any pending database migrations and syncs config defaults.
 * Run via cron every 5 minutes (or after any code deployment):
 *
 *   *\/5 * * * *  php /path/to/cron/auto_migrate.php >> /path/to/logs/migrations.log 2>&1
 *
 * Safe to run frequently — the lock-file cache prevents unnecessary DB queries.
 */
define('BASE_PATH',   dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

if (!file_exists(CONFIG_PATH . '/config.json')) {
    echo "[" . date('Y-m-d H:i:s') . "] Not installed — skipping migration check.\n";
    exit(0);
}

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Migrator.php';

Config::init(CONFIG_PATH);

$tz = Config::get('config', 'app.timezone') ?? 'UTC';
date_default_timezone_set($tz);

echo "[" . date('Y-m-d H:i:s') . "] Starting migration check...\n";

// Init tracking table
Migrator::init();

// Sync config defaults (add missing keys)
$configChanged = Migrator::syncConfig();
if ($configChanged) {
    echo "[" . date('Y-m-d H:i:s') . "] Config defaults merged.\n";
}

// Run pending migrations
$pending = Migrator::getPending();
if (empty($pending)) {
    echo "[" . date('Y-m-d H:i:s') . "] No pending migrations — DB is up to date (schema v" . Migrator::getCurrentVersion() . ").\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] " . count($pending) . " pending migration(s) found: " . implode(', ', $pending) . "\n";

$results = Migrator::runAll();
foreach ($results as $r) {
    $line = "[" . date('Y-m-d H:i:s') . "] "
          . strtoupper($r['status']) . "  "
          . $r['file'] . "  "
          . "(" . ($r['ms'] ?? 0) . "ms)  "
          . ($r['message'] ?? '');
    echo $line . "\n";
}

$ok  = count(array_filter($results, fn($r) => $r['status'] === 'applied'));
$bad = count(array_filter($results, fn($r) => $r['status'] === 'error'));

echo "[" . date('Y-m-d H:i:s') . "] Done. Applied: {$ok}, Failed: {$bad}. Schema v" . Migrator::getCurrentVersion() . ".\n";

exit($bad > 0 ? 1 : 0);
