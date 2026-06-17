<?php
require 'vendor/autoload.php';
require 'config/Config.php';
require 'config/Database.php';

// Set up minimal env
class Auth {
    static function check() { return true; }
    static function affiliateId() { return 1; }
    static function currentUser() { return ['id'=>1, 'role'=>'affiliate']; }
}
class PrivateOffer {
    static function ensureTables() {}
}

$_GET['action'] = 'details';
$_GET['id'] = 1;

ob_start();
require 'api/v2/OfferController.php';
$out = ob_get_clean();
echo $out;
