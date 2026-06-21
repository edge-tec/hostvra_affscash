<?php
$_SERVER['REQUEST_URI'] = '/api/v2/admin/dashboard';
require 'core/Database.php';
require 'core/Config.php';

// Mock Auth
class Auth {
    public static function check($role) { return true; }
    public static function id() { return 1; }
}

try {
    ob_start();
    require 'api/v2/admin/DashboardController.php';
    $output = ob_get_clean();
    echo "Output:\n" . $output . "\n";
} catch (\Throwable $e) {
    echo "FATAL ERROR:\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
