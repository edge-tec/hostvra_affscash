<?php
$_SERVER['REQUEST_URI'] = '/api/v2/admin/fraud-score-report?action=report';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'report';
// mock auth
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['user_role'] = 'admin';
require 'index.php';
