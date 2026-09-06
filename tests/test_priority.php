<?php
require_once __DIR__ . '/core/FirebaseMessaging.php';
$res = FirebaseMessaging::send('dummytokendummytokendummytokendummytokendummy', 'Title', 'Body');
print_r($res);
