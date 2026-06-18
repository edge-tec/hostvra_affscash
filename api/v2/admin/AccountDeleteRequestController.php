<?php
header('Content-Type: application/json');

Auth::check('admin');

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

try {
    $action = Helpers::get('action') ?: 'list';

    if ($action === 'list') {
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
        ) ?: [];

        $counts = Database::fetchOne(
            "SELECT
                SUM(status='pending')  AS pending,
                SUM(status='approved') AS approved,
                SUM(status='rejected') AS rejected,
                COUNT(*) AS total
             FROM account_delete_requests"
        );

        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'stats' => [
                'pending'  => (int)($counts['pending'] ?? 0),
                'approved' => (int)($counts['approved'] ?? 0),
                'rejected' => (int)($counts['rejected'] ?? 0),
                'total'    => (int)($counts['total'] ?? 0)
            ]
        ]);
        exit;
    }

    if ($action === 'update_status') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method");
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $reqId     = (int)($input['request_id'] ?? 0);
        $decision  = $input['decision'] ?? ''; // 'approve' or 'reject'
        $adminNote = trim($input['admin_note'] ?? '');

        if (!$reqId || !in_array($decision, ['approve', 'reject'])) {
            throw new Exception("Invalid request ID or decision.");
        }

        $req = Database::fetchOne(
            "SELECT r.*, u.email, u.first_name, u.last_name
             FROM account_delete_requests r
             JOIN users u ON u.id = r.user_id
             WHERE r.id = ? AND r.status = 'pending'",
            [$reqId]
        );

        if (!$req) {
            throw new Exception("Request not found or already reviewed.");
        }

        if ($decision === 'approve') {
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

            try {
                Mailer::sendEvent($req['email'], $req['first_name'] . ' ' . $req['last_name'], 'account_deleted', [
                    'name'      => $req['first_name'],
                    'site_name' => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                    'note'      => $adminNote,
                ]);
            } catch (\Throwable $e) {}

            echo json_encode(['success' => true, 'message' => 'Affiliate account has been deleted.']);
        } elseif ($decision === 'reject') {
            Database::update('account_delete_requests', [
                'status'      => 'rejected',
                'admin_note'  => $adminNote ?: 'Request rejected by admin.',
                'reviewed_at' => date('Y-m-d H:i:s'),
                'reviewed_by' => Auth::id(),
            ], 'id=?', [$reqId]);

            echo json_encode(['success' => true, 'message' => 'Deletion request has been rejected.']);
        }
        exit;
    }

    throw new Exception("Invalid action");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
