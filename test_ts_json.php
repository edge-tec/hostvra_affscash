<?php
define('BASE_PATH', __DIR__);
require_once 'core/Database.php';
// just connect to DB to run the queries
$config = require 'config/database.php';
try {
    Database::init($config);
    $rawAffiliates = Database::fetchAll("SELECT af.id, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), u.username, u.email, CONCAT('Affiliate #', af.id)) as name, COALESCE(af.affiliate_code, CONCAT('AFF', af.id)) as affiliate_code FROM affiliates af JOIN users u ON u.id = af.user_id ORDER BY name");
    if (!is_array($rawAffiliates)) $rawAffiliates = [];
    $affiliates = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'name' => (string)($row['name'] ?? ''),
            'affiliate_code' => (string)($row['affiliate_code'] ?? '')
        ];
    }, $rawAffiliates);
    echo "Affiliates count: " . count($affiliates) . "\n";
    echo json_encode(array_values($affiliates), JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
