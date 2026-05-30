<?php
Auth::check('admin');
$pageTitle = 'Account Deletion Requests';

// Ensure table exists (idempotent)
try {
    Database::query("CREATE TABLE IF NOT EXISTS account_delete_requests (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        affiliate_id    INT NOT NULL,
        user_id         INT NOT NULL,
        reason          TEXT NOT NULL,
        status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        admin_note      TEXT DEFAULT NULL,
        requested_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at     DATETIME DEFAULT NULL,
        reviewed_by     INT DEFAULT NULL,
        INDEX idx_aff   (affiliate_id),
        INDEX idx_status (status)
    )");
} catch (\Throwable $e) {}

$action = Helpers::get('action') ?: 'index';

// ── Approve / Reject ────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $reqId     = (int)Helpers::postRaw('request_id');
    $decision  = Helpers::post('decision'); // 'approve' or 'reject'
    $adminNote = trim(Helpers::postRaw('admin_note') ?? '');

    $req = Database::fetchOne(
        "SELECT r.*, u.email, u.first_name, u.last_name
         FROM account_delete_requests r
         JOIN users u ON u.id = r.user_id
         WHERE r.id = ? AND r.status = 'pending'",
        [$reqId]
    );

    if (!$req) {
        Helpers::flash('error', 'Request not found or already reviewed.');
        Helpers::redirect('/admin/account-delete-requests');
    }

    if ($decision === 'approve') {
        // Soft-delete the affiliate
        Database::update('users', ['status' => 'deleted'], 'id=?', [$req['user_id']]);
        Database::query(
            "UPDATE affiliate_offers SET status='blocked' WHERE affiliate_id=?",
            [$req['affiliate_id']]
        );
        Database::update('account_delete_requests', [
            'status'      => 'approved',
            'admin_note'  => $adminNote ?: 'Account approved for deletion.',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => Auth::id(),
        ], 'id=?', [$reqId]);

        // Notify affiliate
        try {
            Mailer::sendEvent($req['email'], $req['first_name'] . ' ' . $req['last_name'], 'account_deleted', [
                'name'      => $req['first_name'],
                'site_name' => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                'note'      => $adminNote,
            ]);
        } catch (\Throwable $e) {}

        Helpers::flash('success', 'Affiliate account has been deleted.');
    } elseif ($decision === 'reject') {
        Database::update('account_delete_requests', [
            'status'      => 'rejected',
            'admin_note'  => $adminNote ?: 'Request rejected by admin.',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by' => Auth::id(),
        ], 'id=?', [$reqId]);

        Helpers::flash('success', 'Deletion request has been rejected.');
    } else {
        Helpers::flash('error', 'Invalid decision.');
    }

    Helpers::redirect('/admin/account-delete-requests');
}

// ── Index ───────────────────────────────────────────────────────────────────
$filterStatus = Helpers::get('status') ?: 'pending';
$where  = $filterStatus !== 'all' ? 'r.status=?' : '1';
$params = $filterStatus !== 'all' ? [$filterStatus] : [];

$requests = Database::fetchAll(
    "SELECT r.*,
            u.email, u.first_name, u.last_name, u.country,
            af.affiliate_code, af.balance,
            CONCAT(ru.first_name,' ',ru.last_name) AS reviewed_by_name
     FROM account_delete_requests r
     JOIN users u ON u.id = r.user_id
     JOIN affiliates af ON af.id = r.affiliate_id
     LEFT JOIN users ru ON ru.id = r.reviewed_by
     WHERE $where
     ORDER BY r.requested_at DESC",
    $params
);

$counts = Database::fetchOne(
    "SELECT
        SUM(status='pending')  AS pending,
        SUM(status='approved') AS approved,
        SUM(status='rejected') AS rejected,
        COUNT(*) AS total
     FROM account_delete_requests"
);

require BASE_PATH . '/views/admin/account_delete_requests/index.php';
