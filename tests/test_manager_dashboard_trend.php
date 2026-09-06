<?php
require_once __DIR__ . '/core/Database.php';

// Mock Auth
class Auth {
    public static function check($role) { return true; }
    public static function user() { return ['id' => 1, 'role' => 'affiliate_manager']; }
    public static function isImpersonating() { return true; }
    public static function getImpersonateId() { return 1; }
    public static function id() { return 1; }
    public static function role() { return 'affiliate_manager'; }
    public static function affiliateId() { return 1; }
    public static function managerAffiliateIds() { return [1]; }
}

class Config {
    public static function get($file, $key) { return 'UTC'; }
}

class Helpers {
    public static function get($k) { return $_GET[$k] ?? null; }
}

class ManagerPermissions {
    public static function ensureSchema() {}
    public static function getAllowedAffiliates($id) { return [1]; }
}

$from = date('Y-m-d', strtotime('-30 days'));
$to = date('Y-m-d');

echo "--- Testing Manager DashboardController (action=trend) ---\n";
$_GET = ['action' => 'trend', 'from' => $from, 'to' => $to, 'affiliate_id' => 1];
try {
    ob_start();
    require __DIR__ . '/api/v2/manager/DashboardController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n\n";
}
