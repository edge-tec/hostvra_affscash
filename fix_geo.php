<?php
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

require 'core/Helpers.php';
require 'core/Config.php';
require 'core/Database.php';
require 'core/Auth.php';
require 'core/FraudIQ.php';

// Ensure only admins can run this
Auth::check('admin');

$conversions = Database::fetchAll("SELECT id, ip_address FROM conversions WHERE ip_address IS NOT NULL AND ip_address != '' AND (ipquery_country_code IS NULL OR ipquery_city IS NULL OR ipquery_state IS NULL OR ipquery_country_code = '')");

echo "<h1>Updating " . count($conversions) . " conversions...</h1><pre>";

$updated = 0;
foreach ($conversions as $cv) {
    $ip = $cv['ip_address'];
    if (strpos($ip, '/') !== false) {
        $ip = explode('/', $ip)[0];
    }
    
    $geo = FraudIQ::checkIPQuery($ip);
    
    Database::query(
        "UPDATE conversions 
         SET ipquery_country = ?, ipquery_country_code = ?, ipquery_city = ?, ipquery_state = ?, ipquery_isp = ?, ipquery_org = ?, ipquery_asn = ? 
         WHERE id = ?",
        [
            $geo['country'] ?? '',
            $geo['country_code'] ?? '',
            $geo['city'] ?? '',
            $geo['state'] ?? '',
            $geo['isp'] ?? '',
            $geo['org'] ?? '',
            $geo['asn'] ?? '',
            $cv['id']
        ]
    );
    $updated++;
    echo "Updated ID {$cv['id']} for IP {$ip} - Country: {$geo['country_code']}, City: {$geo['city']}, State: {$geo['state']}\n";
    flush();
    usleep(500000); // 500ms
}

echo "\nDone! Updated {$updated} rows.</pre>";
