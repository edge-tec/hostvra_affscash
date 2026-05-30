<?php
Auth::check('admin');                  // Admin-only page; affiliates and managers cannot reach this
$pageTitle = 'VPN/Proxy Detection Excluded Affiliates';

VpnSkipList::ensureSchema();

if (Helpers::isPost()) {
    Auth::verifyCsrf(Helpers::postRaw('_token'));
    $action = Helpers::post('action');

    if ($action === 'add') {
        $affId  = (int)Helpers::post('affiliate_id');
        $note   = trim((string)Helpers::postRaw('note'));
        if ($affId <= 0) {
            Helpers::flash('error', 'Please pick an affiliate.');
        } else {
            // Reject non-existent / inactive affiliates so the list never points at deleted users.
            $exists = Database::fetchOne("SELECT id FROM affiliates WHERE id=?", [$affId]);
            if (!$exists) {
                Helpers::flash('error', 'Affiliate not found.');
            } else {
                $dup = Database::fetchOne("SELECT id FROM vpn_proxy_skip_affiliates WHERE affiliate_id=?", [$affId]);
                if ($dup) {
                    Helpers::flash('error', 'This affiliate is already on the skip list.');
                } else {
                    $newId = VpnSkipList::add($affId, (int)(Auth::id() ?? 0), $note ?: null);
                    if ($newId) Helpers::flash('success', 'Affiliate added to the VPN/Proxy skip list.');
                    else        Helpers::flash('error',   'Failed to add affiliate to the skip list.');
                }
            }
        }
    } elseif ($action === 'remove') {
        $rowId = (int)Helpers::post('id');
        if (VpnSkipList::remove($rowId)) {
            Helpers::flash('success', 'Affiliate removed from the VPN/Proxy skip list.');
        } else {
            Helpers::flash('error', 'Failed to remove the entry.');
        }
    }
    Helpers::redirect('/admin/vpn-proxy-skip');
}

$entries = VpnSkipList::getAll();

// Affiliate dropdown source — only active accounts not already on the list
try {
    $affiliates = Database::fetchAll(
        "SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name, u.email
           FROM affiliates a
      LEFT JOIN users u ON u.id = a.user_id
      LEFT JOIN vpn_proxy_skip_affiliates s ON s.affiliate_id = a.id
          WHERE u.status = 'active' AND s.id IS NULL
       ORDER BY a.affiliate_code ASC"
    ) ?: [];
} catch (\Throwable $e) { $affiliates = []; }

require BASE_PATH . '/views/admin/vpn_proxy_skip/index.php';
