<?php
Auth::check('affiliate_manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::redirect('/affiliate_manager/affiliates');
}

if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
    Helpers::flash('error', 'Invalid CSRF token.');
    Helpers::redirect('/affiliate_manager/affiliates');
}

$affId  = (int)($_POST['aff_id'] ?? 0);
// affiliates.manager_id stores affiliate_managers.id (not users.id)
$mgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [Auth::id()]);
$mgrId  = $mgrRow ? (int)$mgrRow['id'] : 0;

if (!$affId) {
    Helpers::flash('error', 'Invalid affiliate.');
    Helpers::redirect('/affiliate_manager/affiliates');
}

// Verify this affiliate belongs to this manager
$aff = Database::fetchOne(
    "SELECT af.id, af.user_id, u.first_name, u.last_name
     FROM affiliates af
     JOIN users u ON u.id = af.user_id
     WHERE af.id = ? AND af.manager_id = ? AND u.status = 'active'",
    [$affId, $mgrId]
);

if (!$aff) {
    Helpers::flash('error', 'Affiliate not found or not assigned to your account.');
    Helpers::redirect('/affiliate_manager/affiliates');
}

// Store manager return path before impersonating
$_SESSION['impersonate_return_url'] = '/affiliate_manager/affiliates';

if (!Auth::impersonate((int)$aff['user_id'])) {
    Helpers::flash('error', 'Could not log in as this affiliate.');
    Helpers::redirect('/affiliate_manager/affiliates');
}

Helpers::redirect('/affiliate/dashboard');
