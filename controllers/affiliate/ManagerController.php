<?php
Auth::check('affiliate');
$pageTitle = 'Your Affiliate Manager';

$affId = Auth::affiliateId();
$mgr   = null;

try {
    if ($affId) {
        $mgr = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email, u.phone, u.profile_pic,
                    am.skype, am.telegram, am.discord
             FROM affiliates af
             JOIN affiliate_managers am ON am.id = af.manager_id
             JOIN users u ON u.id = am.user_id
             WHERE af.id = ? LIMIT 1",
            [$affId]
        ) ?: null;
    }
} catch (\Exception $e) {}

require BASE_PATH . '/views/affiliate/manager.php';
