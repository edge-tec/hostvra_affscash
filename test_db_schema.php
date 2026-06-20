<?php
require 'core/Database.php';

$conversions = Database::fetchAll("SELECT * FROM conversions ORDER BY conversion_id DESC LIMIT 5");
$clicks = Database::fetchAll("SELECT * FROM clicks ORDER BY id DESC LIMIT 5");

echo "Conversions:\n";
print_r($conversions);

echo "\nClicks:\n";
print_r($clicks);
