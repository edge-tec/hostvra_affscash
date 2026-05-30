<?php
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
Config::init(__DIR__ . '/config');
$db = Database::getInstance();
var_dump($db);
