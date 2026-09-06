<?php
require 'core/Config.php';
require 'core/Database.php';
require 'core/ShopService.php';

$products = ShopService::listProducts('active');
print_r($products);
