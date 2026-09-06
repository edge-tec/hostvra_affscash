<?php
require 'core/Config.php';
require 'core/Database.php';
$cols = Database::fetchAll("SHOW COLUMNS FROM clicks");
print_r(array_column($cols, 'Field'));
