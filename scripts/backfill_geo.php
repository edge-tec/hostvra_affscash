<?php
/**
 * Backfill GeoIP data for click records that have blank country/city/region.
 *
 * This script finds all clicks with a valid IP but missing geo data and
 * re-runs the GeoIP lookup for each. It respects rate limits by processing
 * in small batches with pauses between each batch.
 *
 * Usage:
 *   php scripts/backfill_geo.php [--limit=500] [--batch=10] [--delay=2]
 *
 * Options:
 *   --limit  Total number of clicks to process (default: 1000)
 *   --batch  Number of IPs per batch before pausing (default: 10)
 *   --delay  Seconds to pause between batches (default: 2)
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';

Config::init(CONFIG_PATH);

use core\Database;
use core\Helpers;

$opts = getopt('', ['limit::', 'batch::', 'delay::']);
$totalLimit = (int)($opts['limit'] ?? 1000);
$batchSize  = (int)($opts['batch'] ?? 10);
$delaySec   = (int)($opts['delay'] ?? 2);

echo "=== GeoIP Backfill Script ===\n";
echo "Limit: $totalLimit | Batch: $batchSize | Delay: {$delaySec}s\n\n";

// Find clicks with valid IP but blank geo
$rows = Database::fetchAll(
    "SELECT DISTINCT ip_address
     FROM clicks
     WHERE ip_address IS NOT NULL
       AND ip_address != ''
       AND ip_address != '127.0.0.1'
       AND ip_address != '::1'
       AND (country IS NULL OR country = '' OR city IS NULL OR city = '')
     ORDER BY clicked_at DESC
     LIMIT ?",
    [$totalLimit]
);

$total = count($rows);
echo "Found $total unique IPs with missing geo data.\n\n";

if ($total === 0) {
    echo "Nothing to backfill — all clicks have geo data.\n";
    exit(0);
}

// Also purge any failed cache entries so lookups actually hit the API
try {
    $purged = Database::query(
        "DELETE FROM ip_geo_cache WHERE (country_code IS NULL OR country_code = '') AND cached_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
    );
    echo "Purged stale empty cache entries.\n\n";
} catch (\Throwable $e) {
    echo "Warning: Could not purge cache: " . $e->getMessage() . "\n";
}

$resolved = 0;
$failed   = 0;
$skipped  = 0;

foreach ($rows as $i => $row) {
    $ip = trim($row['ip_address']);
    $num = $i + 1;

    echo "[$num/$total] Looking up: $ip ... ";

    $geo = Helpers::getGeoInfo($ip);

    if (!empty($geo['country'])) {
        // Update ALL clicks with this IP that have blank geo
        try {
            Database::query(
                "UPDATE clicks
                 SET country = ?, city = ?, region = ?, isp = ?
                 WHERE ip_address = ?
                   AND (country IS NULL OR country = '' OR city IS NULL OR city = '')",
                [$geo['country'], $geo['city'], $geo['region'], $geo['isp'] ?? '', $ip]
            );
            echo "✓ {$geo['country']} / {$geo['city']} / {$geo['region']}\n";
            $resolved++;
        } catch (\Throwable $e) {
            echo "✗ DB update failed: " . $e->getMessage() . "\n";
            $failed++;
        }
    } else {
        echo "✗ Could not resolve (all providers failed)\n";
        $failed++;
    }

    // Pause between batches to respect rate limits
    if ($num % $batchSize === 0 && $num < $total) {
        echo "--- Pausing {$delaySec}s (batch complete) ---\n";
        sleep($delaySec);
    }
}

echo "\n=== Backfill Complete ===\n";
echo "Resolved: $resolved | Failed: $failed | Total: $total\n";
