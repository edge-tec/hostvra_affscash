<?php
Auth::start();
if (!Auth::isImpersonating()) {
    Helpers::redirect('/admin/dashboard');
}
Auth::stopImpersonating();
Helpers::redirect('/admin/dashboard');
