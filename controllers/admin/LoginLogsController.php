<?php
Auth::check('admin');
$pageTitle = 'Login Activity & Live Users';
Activity::ensureTables();
Activity::cleanExpired(10);

require BASE_PATH . '/views/admin/login_logs.php';
