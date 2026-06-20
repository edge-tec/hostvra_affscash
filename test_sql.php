<?php
require 'core/Database.php';
$rows = Database::fetchAll("SELECT click_id, ip_address, country, region, city FROM clicks WHERE ip_address = '70.102.128.110'");
print_r($rows);
