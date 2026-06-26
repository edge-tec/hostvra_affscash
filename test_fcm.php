<?php
require_once __DIR__ . '/core/Config.php';
Config::init(__DIR__ . '/config');
require_once __DIR__ . '/core/FirebaseMessaging.php';
require_once __DIR__ . '/core/Database.php';
// We need a valid token. Let's get the latest token from the DB.
$rows = Database::fetchAll("SELECT user_id, device_token FROM user_devices ORDER BY last_active DESC LIMIT 1");
if (empty($rows)) {
    echo "No device tokens found in DB.\n";
    exit;
}
$token = $rows[0]['device_token'];
echo "Sending test push to User ID: " . $rows[0]['user_id'] . "\n";
echo "Token: " . substr($token, 0, 20) . "...\n";

$result = FirebaseMessaging::send($token, "Urgent Test", "This is a direct test when app is closed.", ['type' => 'info', 'deep_link_route' => 'notifications']);
if ($result) {
    echo "FCM Success: " . json_encode($result) . "\n";
} else {
    echo "FCM Failed.\n";
}
