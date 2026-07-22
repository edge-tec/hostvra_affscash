<?php
/**
 * Admin App API — VPN / Proxy Skip List
 */
Auth::check('admin');
require_once BASE_PATH . '/core/VpnSkipList.php';

VpnSkipList::ensureSchema();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action !== 'list') {
        if ($action === 'add') {
            $affId = (int)($input['affiliate_id'] ?? ($_POST['affiliate_id'] ?? 0));
            $note  = trim((string)($input['note'] ?? ($_POST['note'] ?? '')));

            if ($affId <= 0) {
                Helpers::json(['status' => 'error', 'message' => 'Please select a valid affiliate.']);
                exit;
            }

            $exists = Database::fetchOne("SELECT id FROM affiliates WHERE id=?", [$affId]);
            if (!$exists) {
                Helpers::json(['status' => 'error', 'message' => 'Affiliate not found.']);
                exit;
            }

            $dup = Database::fetchOne("SELECT id FROM vpn_proxy_skip_affiliates WHERE affiliate_id=?", [$affId]);
            if ($dup) {
                Helpers::json(['status' => 'error', 'message' => 'This affiliate is already on the skip list.']);
                exit;
            }

            $newId = VpnSkipList::add($affId, (int)(Auth::id() ?? 0), $note ?: null);
            if ($newId) {
                Helpers::json(['status' => 'success', 'message' => 'Affiliate added to VPN/Proxy skip list successfully.']);
            } else {
                Helpers::json(['status' => 'error', 'message' => 'Failed to add affiliate to skip list.']);
            }
            exit;
        }

        if ($action === 'remove') {
            $rowId = (int)($input['id'] ?? ($_POST['id'] ?? 0));
            if (VpnSkipList::remove($rowId)) {
                Helpers::json(['status' => 'success', 'message' => 'Affiliate removed from VPN/Proxy skip list successfully.']);
            } else {
                Helpers::json(['status' => 'error', 'message' => 'Failed to remove entry.']);
            }
            exit;
        }
    }

    // List action
    $rawEntries = VpnSkipList::getAll();
    $entries = [];
    foreach ($rawEntries as $entry) {
        $entries[] = [
            'id' => (int)$entry['id'],
            'affiliate_id' => (int)$entry['affiliate_id'],
            'affiliate_code' => $entry['affiliate_code'] ?? '',
            'affiliate_name' => $entry['affiliate_name'] ?? ('Affiliate #' . $entry['affiliate_id']),
            'email' => $entry['email'] ?? '',
            'note' => $entry['note'] ?? '',
            'added_by_name' => $entry['added_by_name'] ?? 'Admin',
            'created_at' => date('M d, Y H:i', strtotime($entry['created_at']))
        ];
    }

    $availableAffiliates = Database::fetchAll(
        "SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name, u.email
           FROM affiliates a
      LEFT JOIN users u ON u.id = a.user_id
      LEFT JOIN vpn_proxy_skip_affiliates s ON s.affiliate_id = a.id
          WHERE u.status = 'active' AND s.id IS NULL
       ORDER BY a.affiliate_code ASC"
    ) ?: [];

    $formattedAvailable = [];
    foreach ($availableAffiliates as $aff) {
        $formattedAvailable[] = [
            'id' => (int)$aff['id'],
            'affiliate_code' => $aff['affiliate_code'] ?? '',
            'name' => trim($aff['name'] ?? ''),
            'email' => $aff['email'] ?? ''
        ];
    }

    Helpers::json([
        'status' => 'success',
        'data' => [
            'entries' => $entries,
            'available_affiliates' => $formattedAvailable,
            'total' => count($entries)
        ]
    ]);

} catch (\Throwable $e) {
    Helpers::json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
