<?php
define('BASE_PATH', __DIR__);
require BASE_PATH . '/core/Config.php';
$pg = 'login';
$imgPath = (string)(Config::get('config', 'app.auth_bg_' . $pg) ?? '');
$imgOn   = (string)(Config::get('config', 'app.auth_bg_' . $pg . '_enabled') ?? '0') === '1';
$imgFs   = $imgPath !== '' ? BASE_PATH . $imgPath : '';
$imgOk   = $imgOn && $imgPath !== '' && is_file($imgFs);
echo "imgPath: $imgPath\n";
echo "imgOn: " . ($imgOn ? 'true' : 'false') . "\n";
echo "imgFs: $imgFs\n";
echo "is_file: " . (is_file($imgFs) ? 'true' : 'false') . "\n";
echo "imgOk: " . ($imgOk ? 'true' : 'false') . "\n";
