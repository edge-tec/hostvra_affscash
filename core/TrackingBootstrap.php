<?php
/**
 * TrackingBootstrap — High-Performance, Self-Contained Dependency Bootstrap
 * 
 * Specially engineered for high-concurrency tracking endpoints:
 *   /click/{id}, /offer/{id}, /smartlink/{slug}, /s/{code}, /postback, /pixel, /impression
 * 
 * Responsibilities:
 * 1. Guarantees base directory constants (BASE_PATH, CONFIG_PATH).
 * 2. Registers a Universal Core Autoloader so no class is ever missing.
 * 3. Enforces production-safe error suppression (no leaked filesystem paths/stack traces).
 * 4. Pre-loads essential tracking dependencies in memory.
 * 5. Strictly avoids heavy admin controllers, user sessions, and UI templates.
 */

if (defined('TRACKING_BOOTSTRAPPED')) {
    return;
}
define('TRACKING_BOOTSTRAPPED', true);

// ── 1. Base Environment Constants ───────────────────────────────────────────
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
}

// ── 2. Production Defensive Error Handling ──────────────────────────────────
// Prevent raw PHP fatal errors or sensitive filesystem paths from leaking to visitors.
$isDebug = false;
if (file_exists(CONFIG_PATH . '/config.json')) {
    $cfgRaw = @file_get_contents(CONFIG_PATH . '/config.json');
    if ($cfgRaw) {
        $cfgDecoded = @json_decode($cfgRaw, true);
        $isDebug = !empty($cfgDecoded['app']['debug']);
    }
}

if (!$isDebug) {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
    @ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

@ini_set('log_errors', '1');
$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
@ini_set('error_log', $logDir . '/tracking_php_errors.log');

// Fatal shutdown catcher for tracking
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $msg = sprintf(
            "[%s] FATAL TRACKING ERROR: %s in %s on line %d | URI: %s\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line'],
            $_SERVER['REQUEST_URI'] ?? 'CLI'
        );
        @file_put_contents(BASE_PATH . '/storage/logs/tracking_fatal.log', $msg, FILE_APPEND | LOCK_EX);

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            echo "Service temporarily unavailable.";
        }
    }
});

// ── 3. Universal Class Autoloader ───────────────────────────────────────────
// Automatically resolves ANY class located in core/ to guarantee that no
// "Class 'X' not found" fatal error can ever take down a tracking hit.
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', '/', $class);
    $coreFile  = BASE_PATH . '/core/' . $classPath . '.php';
    if (is_file($coreFile)) {
        require_once $coreFile;
    }
});

// ── 4. Core Infrastructure Essentials ───────────────────────────────────────
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Cache.php';

// Initialize Config if not already done
Config::init(CONFIG_PATH);

// Timezone setup
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// ── 5. High-Throughput Tracking Dependencies Pre-load ───────────────────────
// Pre-load the mission-critical classes so opcode caches and tracking run at peak velocity.
require_once BASE_PATH . '/core/Blocklist.php';
require_once BASE_PATH . '/core/FraudIQ.php';
require_once BASE_PATH . '/core/RiskEngine.php';
require_once BASE_PATH . '/core/VpnSkipList.php';
require_once BASE_PATH . '/core/TrafficSourceDetector.php';
require_once BASE_PATH . '/core/TrafficSourceOverride.php';
require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';
require_once BASE_PATH . '/core/PrivateOffer.php';
