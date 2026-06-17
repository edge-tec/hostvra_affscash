<?php
$config = json_decode(file_get_contents('config/config.json'), true);
$db = new PDO("mysql:host=127.0.0.1;port=3306;dbname=" . $config['db_name'], $config['db_user'], $config['db_pass']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $db->query("UPDATE manager_permissions SET granted=1 WHERE permission_key='view_fraud_reports'");
echo "Updated rows: " . $stmt->rowCount() . "\n";
