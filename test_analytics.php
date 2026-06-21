<?php
define('BASE_PATH', __DIR__);
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';

// Mock Auth to act as affiliate 1
class MockAuth {
    public static function id() { return 1; }
    public static function role() { return 'affiliate'; }
    public static function affiliateId() { return 1; }
}
// Replace Auth calls if possible, or just let it fail at Auth if Auth needs DB.
// Actually, Auth::check() usually uses session. We can just set $_SESSION.
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'affiliate';
$_SESSION['affiliate_id'] = 1;

$_GET['action'] = 'stats';

try {
    require BASE_PATH . '/controllers/api/AffiliateAnalyticsController.php';
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
