<?php
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
require 'core/Config.php';
require 'core/Database.php';
Config::init(CONFIG_PATH);
// Try connecting
try {
    $db = Database::getInstance();
    echo "Connected successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
