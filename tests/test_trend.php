<?php
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';

Config::init(CONFIG_PATH);

// Override Database to not connect but just mock fetchAll
class MockDb {
    public function exec($s) {}
    public static function fetchAll($sql, $params = []) {
        echo "SQL: $sql\n";
        echo "PARAMS: " . json_encode($params) . "\n";
        if (strpos($sql, 'clicks') !== false) {
            return [['h' => 10, 'c' => 50, 'u' => 45]];
        }
        if (strpos($sql, 'conversions') !== false) {
            return [['h' => 10, 'cv' => 5, 'p' => 10, 'r' => 20]];
        }
        return [];
    }
}
class Auth {
    public static function check() { return true; }
    public static function role() { return 'admin'; }
    public static function user() { return ['id' => 1]; }
    public static function managerAffiliateIds() { return []; }
}
class Helpers {
    public static function get($key) { return $_GET[$key] ?? ''; }
}

// Monkey-patch Database class! Wait, I can't redefine Database class.
// But I can use Reflection or run the real DB!
