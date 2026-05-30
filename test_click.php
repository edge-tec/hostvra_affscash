<?php
require_once __DIR__ . '/core/Config.php';
Config::init(__DIR__ . '/config');
var_dump(Config::get('config', 'conversion'));
