<?php
require_once __DIR__ . '/core/Database.php';

class Auth {
    public static function check($role) { return true; }
    public static function user() { return ['id' => 1, 'role' => 'affiliate_manager']; }
    public static function isImpersonating() { return true; }
    public static function getImpersonateId() { return 1; }
    public static function id() { return 1; }
    public static function role() { return 'affiliate'; } // The app sets role=affiliate when impersonating?
    public static function affiliateId() { return 1; }
}

class Config {
    public static function get($file, $key) { return 'UTC'; }
}

class Helpers {
    public static function get($k) { return $_GET[$k] ?? null; }
}

$from = date('Y-m-d', strtotime('-30 days'));
$to = date('Y-m-d');

echo "--- Testing AffiliateAnalyticsController (stats) ---\n";
$_GET = ['action' => 'stats', 'from' => $from, 'to' => $to];
try {
    ob_start();
    require __DIR__ . '/controllers/api/AffiliateAnalyticsController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

echo "--- Testing AffiliateAnalyticsController (trend) ---\n";
$_GET = ['action' => 'trend', 'from' => $from, 'to' => $to];
try {
    ob_start();
    require __DIR__ . '/controllers/api/AffiliateAnalyticsController.php';
    $out = ob_get_clean();
    echo substr($out, 0, 500) . "\n\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}
