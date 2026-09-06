<?php
define('BASE_PATH', __DIR__);
require 'core/Config.php';
require 'core/Database.php';
require 'core/Auth.php';
require 'core/Helpers.php';
require 'core/FraudScore.php';
require 'core/ManagerPermissions.php';
require 'core/FraudAutoNotify.php';

// Simulate manager login
$mgrId = Database::fetchOne("SELECT user_id FROM affiliate_managers LIMIT 1")['user_id'];
Auth::login($mgrId);

$_GET['from'] = '2026-05-01';
$_GET['to'] = '2026-06-22';
$_GET['action'] = 'stats';

ob_start();
try {
    require 'api/v2/manager/DashboardController.php';
} catch (\Throwable $e) {
    echo "ERROR in stats: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo "STATS OUTPUT: " . substr($out, 0, 200) . "\n";

$_GET['action'] = 'trend';
ob_start();
try {
    require 'api/v2/manager/DashboardController.php';
} catch (\Throwable $e) {
    echo "ERROR in trend: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo "TREND OUTPUT: " . substr($out, 0, 200) . "\n";

$_GET['action'] = 'extra';
ob_start();
try {
    require 'api/v2/manager/DashboardController.php';
} catch (\Throwable $e) {
    echo "ERROR in extra: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo "EXTRA OUTPUT: " . substr($out, 0, 200) . "\n";
