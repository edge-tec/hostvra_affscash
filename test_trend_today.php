<?php
$_GET['action'] = 'trend';
$_GET['from'] = date('Y-m-d');
$_GET['to'] = date('Y-m-d');
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

define('BASE_PATH', __DIR__);
require 'vendor/autoload.php';
require 'config/config.php';
require 'core/Database.php';
require 'core/Auth.php';

// Bootstrap minimal
Database::init();

// Mock Auth
class AuthMock {
    public static function check($role) { return true; }
    public static function role() { return 'admin'; }
    public static function user() { return ['id' => 1]; }
}
// override Auth class? no, it's already included. Let's just do an HTTP request.
