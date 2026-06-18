<?php
define('BASE_PATH', dirname(__DIR__, 3));
define('CONFIG_PATH', BASE_PATH . '/config');
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';

try {
    Config::init(CONFIG_PATH);
    $rows = Database::fetchAll("SELECT af.id, u.status, u.role FROM affiliates af JOIN users u ON u.id = af.user_id");
    echo json_encode($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
