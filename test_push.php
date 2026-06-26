<?php
require_once __DIR__ . '/core/Config.php';
Config::init();
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/FirebaseMessaging.php';

// Get the most recent device token
$row = Database::fetchOne("SELECT device_token FROM user_devices ORDER BY updated_at DESC LIMIT 1");
if (!$row || empty($row['device_token'])) {
    die("No device token found in database.\n");
}

$token = $row['device_token'];
echo "Testing push to token: " . substr($token, 0, 30) . "...\n";

$res = FirebaseMessaging::send($token, 'Test Background Push', 'This is a test message to see if it shows in the background', [
    'type' => 'info'
]);

if ($res) {
    echo "Push sent successfully!\n";
    print_r($res);
} else {
    echo "Push failed.\n";
}
