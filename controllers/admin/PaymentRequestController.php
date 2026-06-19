<?php
Auth::check('admin');
AdvBudget::ensureSchema();

$pageTitle = 'Payment Requests';
$action    = Helpers::get('action') ?: 'index';

// ─── ACTION: approve / reject ───────────────────────────────────────────
if (Helpers::isPost() && in_array(Helpers::post('action'), ['approve', 'reject'], true)) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Helpers::flash('error', 'Invalid form submission.');
        Helpers::redirect('/admin/payment-requests');
    }
    $reqId = (int)Helpers::postRaw('id');
    $note  = trim((string)(Helpers::postRaw('admin_note') ?? ''));
    $verb  = Helpers::post('action');

    $req = Database::fetchOne(
        "SELECT pr.*, u.email, u.first_name, u.last_name
         FROM advertiser_payment_requests pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.id = ? AND pr.status = 'pending' LIMIT 1",
        [$reqId]
    );
    if (!$req) {
        Helpers::flash('error', 'Payment request not found or already reviewed.');
        Helpers::redirect('/admin/payment-requests');
    }

    Database::begin();
    try {
        if ($verb === 'approve') {
            // Credit advertiser balance
            Database::query("UPDATE advertisers SET balance = balance + ? WHERE id = ?", [(float)$req['amount'], (int)$req['advertiser_id']]);
            Database::update('advertiser_payment_requests', [
                'status'      => 'approved',
                'admin_note'  => $note,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'reviewed_by' => (int)Auth::id(),
            ], 'id=?', [$reqId]);

            // Notify advertiser instantly
            Database::insert('notifications', [
                'user_id'     => (int)$req['user_id'],
                'target_role' => null,
                'type'        => 'success',
                'title'       => 'Balance Top-Up Approved',
                'message'     => '$' . number_format((float)$req['amount'], 2) . ' has been added to your balance.',
                'link'        => '/advertiser/billing',
                'is_read'     => 0,
            ]);

            try {
                require_once BASE_PATH . '/core/FirebaseMessaging.php';
                FirebaseMessaging::sendToUser((int)$req['user_id'], 'Balance Top-Up Approved', '$' . number_format((float)$req['amount'], 2) . ' has been added to your balance.', ['type' => 'billing']);
            } catch (\Throwable $e) {}

            // Email confirmation (best-effort)
            try {
                $siteName = Config::get('config','app.name') ?? 'AffsCash';
                $name = trim(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? '')) ?: 'Advertiser';
                $body = '<div style="font-family:sans-serif;max-width:560px;margin:0 auto;padding:20px"><h2 style="color:#059669">Top-Up Approved</h2>
                    <p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>Your $<strong>' . number_format((float)$req['amount'], 2) . '</strong> top-up via ' . ucfirst($req['method']) . ' has been approved and added to your ' . htmlspecialchars($siteName) . ' balance.</p>'
                    . ($note !== '' ? '<p style="background:#F8FAFC;border-left:3px solid #4F46E5;padding:10px 12px;margin:14px 0;font-size:13px">Note from admin: ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>' : '')
                    . '</div>';
                Mailer::sendRaw($req['email'], $name, 'Your balance top-up is approved', $body, 'topup_approved');
            } catch (\Throwable $_e) {}

            Helpers::flash('success', 'Payment approved — $' . number_format((float)$req['amount'], 2) . ' credited to advertiser balance.');
        } else {
            Database::update('advertiser_payment_requests', [
                'status'      => 'rejected',
                'admin_note'  => $note,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'reviewed_by' => (int)Auth::id(),
            ], 'id=?', [$reqId]);

            Database::insert('notifications', [
                'user_id'     => (int)$req['user_id'],
                'target_role' => null,
                'type'        => 'warning',
                'title'       => 'Balance Top-Up Rejected',
                'message'     => 'Your $' . number_format((float)$req['amount'], 2) . ' top-up was rejected.' . ($note !== '' ? ' Reason: ' . $note : ''),
                'link'        => '/advertiser/billing',
                'is_read'     => 0,
            ]);

            try {
                require_once BASE_PATH . '/core/FirebaseMessaging.php';
                FirebaseMessaging::sendToUser((int)$req['user_id'], 'Balance Top-Up Rejected', 'Your $' . number_format((float)$req['amount'], 2) . ' top-up was rejected.', ['type' => 'billing']);
            } catch (\Throwable $e) {}

            Helpers::flash('success', 'Payment request marked as rejected.');
        }
        Database::commit();
    } catch (\Throwable $e) {
        try { Database::rollback(); } catch (\Throwable $rb) {}
        Helpers::flash('error', 'Failed to update payment request: ' . $e->getMessage());
    }
    Helpers::redirect('/admin/payment-requests');
}

// ─── ACTION: download — serves the screenshot with admin auth check ─────
if ($action === 'download') {
    $reqId = (int)Helpers::get('id');
    $row = Database::fetchOne("SELECT screenshot_path FROM advertiser_payment_requests WHERE id=?", [$reqId]);
    if (!$row || empty($row['screenshot_path'])) { http_response_code(404); echo 'Not found'; exit; }
    $abs = BASE_PATH . '/uploads/payment_requests/' . $row['screenshot_path'];
    if (!is_file($abs)) { http_response_code(404); echo 'File missing'; exit; }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $abs);
    finfo_close($finfo);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($abs));
    header('Content-Disposition: inline; filename="' . basename($row['screenshot_path']) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($abs);
    exit;
}

// ─── DEFAULT: list ──────────────────────────────────────────────────────
$status = Helpers::get('status') ?: 'pending';
$where  = "1=1";
$params = [];
if (in_array($status, ['pending','approved','rejected'], true)) {
    $where = "pr.status = ?"; $params[] = $status;
} else { $status = 'all'; }

$requests = Database::fetchAll(
    "SELECT pr.*,
            u.email, u.first_name, u.last_name, u.company,
            a.advertiser_code, a.balance AS adv_balance
     FROM advertiser_payment_requests pr
     JOIN users u       ON u.id = pr.user_id
     JOIN advertisers a ON a.id = pr.advertiser_id
     WHERE $where
     ORDER BY pr.id DESC LIMIT 300",
    $params
);
$pendingCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM advertiser_payment_requests WHERE status='pending'")['c'] ?? 0);

require BASE_PATH . '/views/admin/payment_requests/index.php';
