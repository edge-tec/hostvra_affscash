<?php
require_once 'core/Database.php';
try {
    $row = Database::fetchOne("SHOW COLUMNS FROM conversions LIKE 'status'");
    print_r($row);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
