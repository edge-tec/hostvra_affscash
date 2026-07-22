<?php
require 'core/Config.php';
require 'core/Database.php';
$clicks = Database::fetchAll("SELECT click_id, original_source, source, source_override_applied FROM clicks ORDER BY id DESC LIMIT 10");
print_r($clicks);
$count = Database::count("clicks", "source_override_applied = 1");
echo "Total overridden clicks: " . $count . "\n";
