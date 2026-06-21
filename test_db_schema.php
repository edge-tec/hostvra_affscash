<?php
require 'core/Config.php';
require 'core/Database.php';
Config::init(__DIR__ . '/config');
$rows = Database::fetchAll("SHOW CREATE TABLE support_messages");
print_r($rows[0]['Create Table']);
