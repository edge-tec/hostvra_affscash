<?php
require_once 'init.php';
require_once 'core/FirebaseMessaging.php';
$res = FirebaseMessaging::sendToUser(2, "Test Push Title", "This is a test body from script", ["notification_type" => "info"]);
echo "Push result: " . json_encode($res) . "\n";
