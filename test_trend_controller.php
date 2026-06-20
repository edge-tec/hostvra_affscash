<?php
define('BASE_PATH', __DIR__);
require 'config/config.php';
require 'core/Database.php';
require 'core/Auth.php';

Database::init();

$action = 'trend';
$from = '2026-06-20';
$to = '2026-06-20';
$offerId = null;
$affId = null;
$country = null;
$managerAffIds = [];

// provide mock _safe_json_encode since it's defined in api_helper or something usually
if (!function_exists('_safe_json_encode')) {
    function _safe_json_encode($data) { return json_encode($data); }
}

require 'controllers/api/AdminAnalyticsController.php';
