<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';

// Mock Auth
class Auth {
    public static function check($role) { return true; }
    public static function id() { return 1; }
    public static function hasPermission($p) { return true; }
}

// Override headers and exit so we don't actually exit
ob_start();
$_GET['action'] = 'stats';
$_GET['from'] = '2023-01-01';
$_GET['to'] = '2023-01-30';

try {
    require BASE_PATH . '/api/v2/admin/DashboardController.php';
} catch (\Throwable $e) {
    echo "CAUGHT FATAL ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$output = ob_get_clean();
echo "OUTPUT:\n" . $output;
