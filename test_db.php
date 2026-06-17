<?php
require 'includes/config.php';
require 'includes/Database.php';
try {
    $rows = Database::fetchAll("SELECT * FROM manager_permissions");
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
