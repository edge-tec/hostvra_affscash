<?php
$_SERVER['REQUEST_URI'] = '/api/affiliate-analytics?action=stats&from=2024-05-01&to=2024-05-31&affiliate_id=1';
$_GET['action'] = 'stats';
$_GET['from'] = '2024-05-01';
$_GET['to'] = '2024-05-31';
$_GET['affiliate_id'] = '1';

// Mock Auth
class Auth {
    public static function check($role) { return true; }
    public static function role() { return 'manager'; }
    public static function id() { return 1; }
    public static function affiliateId() { return 1; }
    public static function managerAffiliateIds() { return [1]; }
}

require_once 'core/Database.php';
require_once 'core/Helpers.php';
require_once 'core/Config.php';

// Catch errors
try {
    require_once 'controllers/api/AffiliateAnalyticsController.php';
} catch (\Throwable $e) {
    echo "ERROR CAUGHT: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
