<?php
// Mock setup
require 'core/Config.php';
require 'core/Database.php';
require 'core/Helpers.php';
require 'core/Auth.php';
require 'core/FraudScore.php';

// Bypass Auth
class MockAuth {
    public static function id() { return 1; }
    public static function check($role) { return true; }
    public static function managerAffiliateIds() { return [1]; }
}
// Replace Auth calls
$content = file_get_contents('api/v2/manager/DashboardController.php');
$content = str_replace('Auth::check', '//Auth::check', $content);
$content = str_replace('ManagerPermissions::ensureSchema', '//ManagerPermissions::ensureSchema', $content);
$content = str_replace('Auth::id()', 'MockAuth::id()', $content);
$content = str_replace('Auth::managerAffiliateIds()', 'MockAuth::managerAffiliateIds()', $content);
// disable exit
$content = str_replace('exit;', 'return;', $content);

file_put_contents('test_temp_manager.php', $content);

// Test actions
$_GET['action'] = 'stats';
require 'test_temp_manager.php';
echo "\n---\n";
$_GET['action'] = 'trend';
require 'test_temp_manager.php';
echo "\n---\n";
$_GET['action'] = 'extra';
require 'test_temp_manager.php';

