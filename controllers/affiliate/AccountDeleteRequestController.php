<?php
Auth::check('affiliate');
$pageTitle = 'Request Account Deletion';

$affId  = Auth::affiliateId();
$userId = Auth::id();

// Ensure the delete-requests table exists
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

$errors  = [];
$success = false;

// Check if a pending request already exists for this affiliate
$existing = Database::fetchOne(
    "SELECT * FROM account_delete_requests WHERE affiliate_id=? AND status='pending' ORDER BY requested_at DESC LIMIT 1",
    [$affId]
);

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    if ($existing) {
        $errors[] = 'You already have a pending account deletion request. Please wait for admin review.';
    } else {
        $reason = trim(Helpers::postRaw('reason'));
        if (strlen($reason) < 10) {
            $errors[] = 'Please provide a reason (at least 10 characters).';
        } else {
            Database::insert('account_delete_requests', [
                'affiliate_id' => $affId,
                'user_id'      => $userId,
                'reason'       => $reason,
                'status'       => 'pending',
            ]);
            $existing = Database::fetchOne(
                "SELECT * FROM account_delete_requests WHERE affiliate_id=? AND status='pending' ORDER BY requested_at DESC LIMIT 1",
                [$affId]
            );
            $success = true;
            Helpers::flash('success', 'Your account deletion request has been submitted. An admin will review it shortly.');
        }
    }
}

// Fetch request history for this affiliate
$history = Database::fetchAll(
    "SELECT r.*, u.first_name, u.last_name
     FROM account_delete_requests r
     LEFT JOIN users u ON u.id = r.reviewed_by
     WHERE r.affiliate_id = ?
     ORDER BY r.requested_at DESC",
    [$affId]
);

require BASE_PATH . '/views/affiliate/account_delete_request.php';
