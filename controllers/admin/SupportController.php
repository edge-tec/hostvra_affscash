<?php
Auth::check('admin');
$pageTitle  = 'Live Support';
$selAffId   = (int)(Helpers::get('aff') ?? 0);

require BASE_PATH . '/views/admin/support.php';
