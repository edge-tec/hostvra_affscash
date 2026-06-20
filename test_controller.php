<?php
require __DIR__ . '/core/Config.php';
require __DIR__ . '/core/Database.php';
require __DIR__ . '/core/Helpers.php';
Config::init(__DIR__ . '/config');

// Override DB host for local CLI testing
$db = Config::get('config', 'database');
$db['host'] = '127.0.0.1';
Config::set('config', 'database', $db);

class Auth {
    public static function id() { return 1; }
    public static function affiliateId() { return 1; }
    public static function role() { return 'affiliate'; }
}

$_GET['action'] = 'trend';
$_GET['from'] = '2026-06-20';
$_GET['to'] = '2026-06-20';
$_SERVER['REQUEST_URI'] = '/api/affiliate-analytics';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    ob_start();
    require __DIR__ . '/controllers/api/AffiliateAnalyticsController.php';
    $out = ob_get_clean();
    echo "OUTPUT:\n" . $out . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
