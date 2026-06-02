<?php
require 'core/Init.php';
$_GET['click_id'] = 'test_click_id_123';
$_GET['aff_id'] = '1';
$_GET['offer_id'] = '1';
$_SERVER['REQUEST_URI'] = '/click/1';
require 'tracking/click.php';
