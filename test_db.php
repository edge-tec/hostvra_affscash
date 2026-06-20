<?php
require_once __DIR__ . '/api/v2/config/Database.php';

$clicks = Database::fetchAll("SELECT country, city, region, ip_address FROM clicks ORDER BY id DESC LIMIT 5");
print_r($clicks);
