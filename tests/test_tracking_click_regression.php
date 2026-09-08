<?php
/**
 * Automated Regression Test Suite: Tracking Flow & PrivateOffer Authorization
 * 
 * Run: php tests/test_tracking_click_regression.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/TrackingBootstrap.php';

echo "═══════════════════════════════════════════════════════════════════════\n";
echo " AFFSCASH ENTERPRISE TRACKING & PRIVATE OFFER REGRESSION TEST SUITE\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] " . $name . "\n";
    } else {
        $failed++;
        echo " [FAIL] " . $name . ($details ? " -> " . $details : "") . "\n";
    }
}

// ── Database Setup (Test Environment Mock) ──────────────────────────────────
// Check if live MySQL is reachable; if not, initialize high-fidelity in-memory SQLite
$dbReady = false;
try {
    Database::getInstance();
    $dbReady = true;
} catch (\Throwable $e) {
    // Inject in-memory SQLite PDO into Database singleton for unit testing
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `private_offer_access` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `offer_id` INTEGER NOT NULL,
        `affiliate_id` INTEGER NOT NULL,
        `granted_by` INTEGER,
        `granted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `notes` TEXT,
        UNIQUE(`offer_id`, `affiliate_id`)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `private_offer_access_log` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `offer_id` INTEGER NOT NULL,
        `affiliate_id` INTEGER,
        `action` TEXT NOT NULL,
        `actor_id` INTEGER,
        `source` TEXT DEFAULT 'admin',
        `details` TEXT,
        `ip_address` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `offers` (
        `id` INTEGER PRIMARY KEY,
        `name` TEXT,
        `status` TEXT DEFAULT 'active',
        `visibility` TEXT DEFAULT 'public'
    )");

    $ref = new ReflectionProperty('Database', 'instance');
    $ref->setAccessible(true);
    $ref->setValue(null, $pdo);
    $dbReady = true;
}

// ── Test 1: TrackingBootstrap & Autoloader Verification ──────────────────────
assertTest(
    "TrackingBootstrap initializes constants and autoloader",
    defined('TRACKING_BOOTSTRAPPED') && defined('BASE_PATH') && defined('CONFIG_PATH')
);

// Autoloader test: Try instantiating/calling a class not explicitly required in this file
$autoloaderWorking = class_exists('FraudAutoNotify');
assertTest(
    "Universal Autoloader dynamically loads un-required core class (FraudAutoNotify)",
    $autoloaderWorking
);

// ── Test 2: Public Offer Authorization ───────────────────────────────────────
$publicOffer = [
    'id' => 999901,
    'visibility' => 'public',
    'status' => 'active'
];
$canAccessPublic = PrivateOffer::checkClickAccess($publicOffer, 12345);
assertTest(
    "Public offer: checkClickAccess allows access to any affiliate",
    $canAccessPublic === true
);

// ── Test 3: Unauthorized Private Offer ──────────────────────────────────────
$privateOffer = [
    'id' => 999902,
    'visibility' => 'private',
    'status' => 'active'
];
// Ensure affiliate 88888 has NO access
PrivateOffer::revoke(999902, 88888, null);

$canAccessUnauthorized = PrivateOffer::checkClickAccess($privateOffer, 88888);
assertTest(
    "Private offer: checkClickAccess blocks affiliate WITHOUT grant",
    $canAccessUnauthorized === false
);

// ── Test 4: Authorized Private Offer ────────────────────────────────────────
// Grant access to affiliate 77777
$pdo = Database::getInstance();
$pdo->prepare("INSERT OR REPLACE INTO `private_offer_access` (offer_id, affiliate_id, granted_by, notes) VALUES (?, ?, ?, ?)")
    ->execute([999902, 77777, 1, 'Automated test grant']);

$canAccessAuthorized = PrivateOffer::checkClickAccess($privateOffer, 77777);
assertTest(
    "Private offer: checkClickAccess allows affiliate WITH grant",
    $canAccessAuthorized === true
);

// Cleanup test grant & verify instant revocation
$pdo->prepare("DELETE FROM `private_offer_access` WHERE offer_id=? AND affiliate_id=?")
    ->execute([999902, 77777]);
$revokedCheck = PrivateOffer::checkClickAccess($privateOffer, 77777);
assertTest(
    "Private offer: Revoking access immediately blocks affiliate",
    $revokedCheck === false
);

// ── Test 5: Invalid Offer ID & Malformed Input Safety ───────────────────────
$malformedOffer1 = ['id' => 0, 'visibility' => 'private'];
$malformedOffer2 = ['id' => -5, 'visibility' => 'private'];
$malformedOffer3 = ['visibility' => 'private'];

$safe1 = PrivateOffer::checkClickAccess($malformedOffer1, 123);
$safe2 = PrivateOffer::checkClickAccess($malformedOffer2, 123);
$safe3 = PrivateOffer::checkClickAccess($malformedOffer3, 123);

assertTest(
    "Malformed/Invalid offer IDs fail safely without crashing (returns false)",
    $safe1 === false && $safe2 === false && $safe3 === false
);

$safeAff1 = PrivateOffer::checkClickAccess($privateOffer, 0);
$safeAff2 = PrivateOffer::checkClickAccess($privateOffer, -1);
assertTest(
    "Missing / non-positive affiliate IDs fail safely (returns false)",
    $safeAff1 === false && $safeAff2 === false
);

// ── Test 6: Fast-Path URL Regex Matches ─────────────────────────────────────
$fastRegex = '#^/click/(\d+)#';
$testUrls = [
    '/click/123' => true,
    '/click/999999' => true,
    '/click/abc' => false,
    '/dashboard' => false,
];
$regexAllPassed = true;
foreach ($testUrls as $url => $expected) {
    $matched = (bool)preg_match($fastRegex, $url);
    if ($matched !== $expected) {
        $regexAllPassed = false;
    }
}
assertTest("Fast-Path /click/(\d+) regex correctly identifies tracking hits", $regexAllPassed);

// ── Test 7: Direct click.php Execution Dependency Check ─────────────────────
// Run click.php via CLI in a clean sub-process with simulated parameters
$cmd = sprintf(
    'php -r %s',
    escapeshellarg('
        $_GET["offer_id"] = 99999999;
        $_GET["aff_id"] = "TEST_NON_EXISTENT";
        $_SERVER["REQUEST_URI"] = "/click/99999999?aff_id=TEST_NON_EXISTENT";
        ob_start();
        try {
            require "' . BASE_PATH . '/tracking/click.php";
        } catch (\Throwable $e) {
            echo "EXCEPTION: " . $e->getMessage();
        }
        $out = ob_get_clean();
        if (strpos($out, "Class") !== false && strpos($out, "not found") !== false) {
            echo "FATAL_CLASS_NOT_FOUND";
        } else {
            echo "SUCCESS_HANDLED";
        }
    ')
);
$cliOutput = shell_exec($cmd);
assertTest(
    "Direct click.php execution has all dependencies (NO 'Class not found' errors)",
    strpos($cliOutput, 'SUCCESS_HANDLED') !== false && strpos($cliOutput, 'FATAL_CLASS_NOT_FOUND') === false,
    "Output: " . trim((string)$cliOutput)
);

// ── Test 8: Performance Benchmark (TrackingBootstrap Overhead) ──────────────
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    require BASE_PATH . '/core/TrackingBootstrap.php';
}
$duration = (microtime(true) - $start) * 1000; // in milliseconds
$perHit = $duration / 1000; // ms per call
$mem = memory_get_usage(true) / 1024 / 1024; // in MB

assertTest(
    sprintf("TrackingBootstrap is ultra-fast: %.4f ms/hit (Memory: %.2f MB)", $perHit, $mem),
    $perHit < 0.1 && $mem < 5.0,
    sprintf("Took %.4f ms per hit, memory %.2f MB", $perHit, $mem)
);

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo sprintf(" RESULT: %d PASSED, %d FAILED\n", $passed, $failed);
echo "═══════════════════════════════════════════════════════════════════════\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
