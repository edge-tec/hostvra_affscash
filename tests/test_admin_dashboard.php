<?php
require_once __DIR__ . '/core/Database.php';

// Mock Auth
class Auth {
    public static function check($role) { return true; }
    public static function user() { return ['id' => 1, 'role' => 'admin']; }
    public static function isImpersonating() { return false; }
    public static function id() { return 1; }
    public static function role() { return 'admin'; }
}
class Config {
    public static function get($file, $key) { return null; }
}
class Helpers {
    public static function get($k) { return $_GET[$k] ?? null; }
}

$from = date('Y-m-d', strtotime('-30 days'));
$to = date('Y-m-d');

echo "--- Testing Admin DashboardController (default / initial load) ---\n";
$_GET = ['action' => ''];
try {
    ob_start();
    require __DIR__ . '/api/v2/admin/DashboardController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "--- Testing Admin DashboardController (stats) ---\n";
$_GET = ['action' => 'stats', 'from' => $from, 'to' => $to];
try {
    ob_start();
    require __DIR__ . '/api/v2/admin/DashboardController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "--- Testing Admin DashboardController (trend) ---\n";
$_GET = ['action' => 'trend', 'from' => $from, 'to' => $to];
try {
    ob_start();
    require __DIR__ . '/api/v2/admin/DashboardController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}
