<?php
$_SERVER['REQUEST_URI'] = '/api/v2/ReportController.php';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer dummy';
require 'core/Auth.php'; // wait, auth will fail if I just include it.
