<?php
Auth::start();
// Must be an admin who is currently impersonating — prevent privilege escalation
if (empty($_SESSION['admin_user_role']) || $_SESSION['admin_user_role'] !== 'admin') {
    Helpers::redirect('/login');
}
if (!Auth::isImpersonating()) {
    Helpers::redirect('/admin/dashboard');
}
Auth::stopImpersonating();
Helpers::redirect('/admin/dashboard');
