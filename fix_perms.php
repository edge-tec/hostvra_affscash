<?php
define('BASE_PATH', __DIR__);
require 'core/Config.php';
require 'core/Database.php';

try {
    $rows = Database::fetchAll("SELECT manager_id, permission_key, granted FROM manager_permissions WHERE permission_key = 'view_fraud_reports'");
    print_r($rows);
    
    // Also, force grant it for all managers just in case they were explicitly denied when the default was 0
    Database::query("UPDATE manager_permissions SET granted=1 WHERE permission_key='view_fraud_reports'");
    echo "Updated!\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
