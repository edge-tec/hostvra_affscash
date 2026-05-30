<?php
/**
 * Marketing - Main SaaS Landing Page Controller
 */
$pageTitle = Config::get('config', 'app.name') . ' - High Performance Affiliate Tracking Platform';

// Fetch pricing plans
$plans = Database::fetchAll("SELECT * FROM `subscription_plans` WHERE status = 'active' ORDER BY price ASC");

// Fetch testimonials
$testimonials = [
    ['name' => 'Michael K.', 'role' => 'Founder, ScaleCPA', 'text' => 'Deploying our tracking portal took less than 2 minutes. The built-in fraud controls save us thousands every single month!', 'rating' => 5],
    ['name' => 'Sophia L.', 'role' => 'Media Buyer', 'text' => 'The Smartlink delivery engine rotates our international mobile traffic perfectly. Payout conversions are extremely reliable.', 'rating' => 5]
];

require BASE_PATH . '/views/marketing/saas_landing.php';
