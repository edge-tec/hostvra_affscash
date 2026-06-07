<?php
require 'core/Init.php';
try {
    Database::query(
        "INSERT INTO traffic_back_logs (click_id, affiliate_id, offer_id, reason, redirect_url, ip_address, country) VALUES (?, ?, ?, ?, ?, ?, ?)",
        ['test-uuid', null, null, 'Test Reason', 'https://example.com', '127.0.0.1', 'US']
    );
    echo "Success!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
