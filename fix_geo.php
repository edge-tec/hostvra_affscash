<?php
/**
 * Backfill missing geolocation data for ALL conversions.
 *
 * Usage: Log into admin panel, then visit https://yoursite.com/fix_geo.php
 *
 * This script bootstraps the application the same way index.php does,
 * verifies admin auth, then iterates over every conversion row that lacks
 * country/city/state data and resolves it via the IPQuery API.
 *
 * Safe to run multiple times — only processes rows with missing data.
 * Delete this file once all conversions have been backfilled.
 */

define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

// Redirect to installer if not installed
if (!file_exists(CONFIG_PATH . '/config.json')) {
    die('Application not installed.');
}

// Bootstrap the same way index.php does
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/FraudIQ.php';

// Start session for auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: require admin login
if (!Auth::isLoggedIn() || Auth::role() !== 'admin') {
    die('<h2 style="color:red">Access Denied</h2><p>You must be logged in as admin. <a href="/login">Login here</a></p>');
}

// Ensure all required columns exist
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country`      VARCHAR(60)  DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country_code` CHAR(2)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_city`         VARCHAR(100) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_state`        VARCHAR(100) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_isp`          VARCHAR(200) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_org`          VARCHAR(200) DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_asn`          VARCHAR(30)  DEFAULT NULL"); } catch (\Throwable $_e) {}

// Find all conversions missing geo data
$conversions = Database::fetchAll(
    "SELECT id, conversion_id, ip_address
     FROM conversions
     WHERE ip_address IS NOT NULL
       AND ip_address != ''
       AND ip_address != '0.0.0.0'
       AND (ipquery_country_code IS NULL OR ipquery_country_code = ''
            OR ipquery_city IS NULL OR ipquery_city = ''
            OR ipquery_state IS NULL OR ipquery_state = '')
     ORDER BY id DESC"
);

$total = count($conversions);

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Geo Backfill</title>
<style>body{font-family:monospace;background:#0F172A;color:#E2E8F0;padding:24px}
.ok{color:#10B981}.skip{color:#F59E0B}.err{color:#EF4444}
h1{color:#818CF8}pre{white-space:pre-wrap}</style></head><body>";
echo "<h1>🌍 Geo Data Backfill</h1>";
echo "<p>Found <strong>{$total}</strong> conversions missing geolocation data.</p><pre>";
flush();

$updated = 0;
$failed  = 0;

foreach ($conversions as $cv) {
    $ip = trim($cv['ip_address']);
    // Strip CIDR block
    if (strpos($ip, '/') !== false) {
        $ip = explode('/', $ip)[0];
    }

    try {
        $geo = FraudIQ::checkIPQuery($ip);
        $cc    = $geo['country_code'] ?? '';
        $city  = $geo['city']         ?? '';
        $state = $geo['state']        ?? '';

        if ($cc === '' && $city === '' && $state === '') {
            echo "<span class='skip'>[SKIP]</span> ID {$cv['id']} — IP {$ip} — API returned empty geo\n";
            $failed++;
        } else {
            Database::query(
                "UPDATE conversions SET
                    ipquery_country      = ?,
                    ipquery_country_code = ?,
                    ipquery_city         = ?,
                    ipquery_state        = ?,
                    ipquery_isp          = ?,
                    ipquery_org          = ?,
                    ipquery_asn          = ?
                 WHERE id = ?",
                [
                    $geo['country']      ?? '',
                    $cc,
                    $city,
                    $state,
                    $geo['isp'] ?? '',
                    $geo['org'] ?? '',
                    $geo['asn'] ?? '',
                    $cv['id']
                ]
            );
            echo "<span class='ok'>[  OK]</span> ID {$cv['id']} — {$ip} → {$cc} / {$city} / {$state}\n";
            $updated++;
        }
    } catch (\Throwable $e) {
        echo "<span class='err'>[ ERR]</span> ID {$cv['id']} — {$ip} — " . $e->getMessage() . "\n";
        $failed++;
    }

    flush();
    usleep(350000); // 350ms delay to respect API rate limits
}

echo "</pre><hr>";
echo "<h2>✅ Done!</h2>";
echo "<p><strong>{$updated}</strong> conversions updated, <strong>{$failed}</strong> skipped/failed out of <strong>{$total}</strong> total.</p>";
echo "<p><a href='/admin/reports?tab=conversions' style='color:#818CF8'>← Back to Reports</a></p>";
echo "</body></html>";
