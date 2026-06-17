<?php
require 'config/Database.php';
$_GET['action'] = 'details';
$_GET['id'] = 1;
class Auth {
    static function check() {}
    static function affiliateId() { return 1; }
    static function currentUser() { return ['id'=>1]; }
}
class Config {
    static function get() { return 'http://localhost'; }
}
class PrivateOffer {
    static function ensureTables() {}
}
ob_start();
require 'api/v2/OfferController.php';
$output = ob_get_clean();
echo $output;
