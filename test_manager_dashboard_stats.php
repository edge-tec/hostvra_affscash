<?php
class Auth {
    public static function check($r) { return null; }
    public static function managerAffiliateIds() { return [1, 2]; }
}
class Helpers {
    public static function get($k) { 
        if ($k === 'action') return 'stats'; 
        if ($k === 'from') return '2023-01-01';
        if ($k === 'to') return '2023-01-30';
        return null;
    }
}
class Database {
    public static function fetchOne($sql) { return ['c'=>1, 'u'=>1, 'cv'=>1, 'payout'=>1, 'revenue'=>1]; }
    public static function fetchAll($sql) { return []; }
    public static function query($sql) { return true; }
}
class FraudScore {
    public static function forAffiliate($id) { return 0; }
}
class ManagerPermissions {
    public static function has($x) { return true; }
    public static function ensureSchema() { return true; }
}

include 'api/v2/manager/DashboardController.php';
