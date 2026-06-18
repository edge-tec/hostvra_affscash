<?php
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';

try {
    Config::init(CONFIG_PATH);
    Database::init();
    $rows = Database::fetchAll(
        "SELECT af.id AS affiliate_id,
                u.status
         FROM affiliates af
         JOIN users u ON u.id = af.user_id"
    );
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
