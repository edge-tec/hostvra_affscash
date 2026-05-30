<?php
Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('access_support');
$pageTitle = 'Support Inbox';
$affIds = Auth::managerAffiliateIds();
$selAffId = (int)Helpers::get('aff');
require BASE_PATH . '/views/affiliate_manager/support.php';
