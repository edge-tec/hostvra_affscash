<?php
Auth::start();
if (!Auth::isImpersonating()) {
    Helpers::redirect('/affiliate_manager/dashboard');
}

$returnUrl = $_SESSION['impersonate_return_url'] ?? '/affiliate_manager/affiliates';
Auth::stopImpersonating();
Helpers::redirect($returnUrl);
