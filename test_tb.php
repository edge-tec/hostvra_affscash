<?php
require 'core/Init.php';
try {
    Database::execute("INSERT INTO traffic_back_logs (click_id, ip_address) VALUES ('', '127.0.0.1')");
    echo "Success!";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
