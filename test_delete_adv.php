<?php
session_start();
require 'core/Config.php';
require 'core/Database.php';
require 'core/Helpers.php';
require 'core/Auth.php';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['_token'] = 'fake_token';
$_SESSION['_csrf'] = 'fake_token';
$_POST['adv_id'] = '1'; // Make sure there is an advertiser with ID 1, or change to one that exists

$action = 'delete';
$_GET['action'] = 'delete';
try {
    require 'controllers/admin/AdvertiserController.php';
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
