<?php
/**
 * Admin Subscription - Payment Success Controller
 */
Auth::check('admin');

$pageTitle = 'Subscription Successful!';
require BASE_PATH . '/views/admin/subscription/success.php';
