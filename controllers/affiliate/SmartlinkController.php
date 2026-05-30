<?php
Auth::check('affiliate');
$pageTitle = 'Smartlinks';

$affId  = Auth::affiliateId();
$aff    = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$affId]);
$appUrl = Config::get('config', 'app.url') ?? '';

// Schema migration (idempotent)
try {
    Database::query("CREATE TABLE IF NOT EXISTS smartlink_requests (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        smartlink_id  INT NOT NULL,
        affiliate_id  INT NOT NULL,
        status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        affiliate_note TEXT DEFAULT NULL,
        admin_note    TEXT DEFAULT NULL,
        reviewed_by   INT DEFAULT NULL,
        reviewed_at   DATETIME DEFAULT NULL,
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_sl_aff (smartlink_id, affiliate_id),
        INDEX idx_status (status),
        INDEX idx_aff (affiliate_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$action = Helpers::get('action') ?: 'index';

// ── Submit Access Request ──────────────────────────────────────────────────
if ($action === 'request_access') {
    header('Content-Type: application/json');
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['error' => 'Invalid token']); exit;
    }
    $slId = (int)Helpers::postRaw('smartlink_id');
    $note = trim(Helpers::postRaw('note') ?? '');

    $sl = Database::fetchOne("SELECT id, name, require_approval FROM smartlinks WHERE id=? AND status='active'", [$slId]);
    if (!$sl) { echo json_encode(['error' => 'Smartlink not found']); exit; }

    $autoApprove = empty($sl['require_approval']);

    // Check if request already exists
    $existing = Database::fetchOne("SELECT id, status FROM smartlink_requests WHERE smartlink_id=? AND affiliate_id=?", [$slId, $affId]);
    if ($existing) {
        if ($existing['status'] === 'approved') {
            echo json_encode(['error' => 'You already have access to this smartlink.']); exit;
        }
        if ($existing['status'] === 'pending' && !$autoApprove) {
            echo json_encode(['error' => 'Your request is already pending review.']); exit;
        }
        // Auto-approve or re-request after rejection
        Database::update('smartlink_requests', [
            'status'         => $autoApprove ? 'approved' : 'pending',
            'affiliate_note' => $note,
            'admin_note'     => $autoApprove ? 'Auto-approved' : null,
            'reviewed_by'    => null,
            'reviewed_at'    => $autoApprove ? date('Y-m-d H:i:s') : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ], 'id=?', [$existing['id']]);
    } else {
        Database::insert('smartlink_requests', [
            'smartlink_id'   => $slId,
            'affiliate_id'   => $affId,
            'status'         => $autoApprove ? 'approved' : 'pending',
            'affiliate_note' => $note,
            'admin_note'     => $autoApprove ? 'Auto-approved' : null,
            'reviewed_at'    => $autoApprove ? date('Y-m-d H:i:s') : null,
        ]);
    }

    if ($autoApprove) {
        echo json_encode(['ok' => true, 'auto_approved' => true]); exit;
    }

    // Notify admin/manager (only when manual approval needed)
    try {
        $me = Auth::currentUser();
        Database::insert('notifications', [
            'user_id'     => null,
            'target_role' => 'admin',
            'type'        => 'info',
            'title'       => 'New Smartlink Request',
            'message'     => ($me['first_name'] ?? 'Affiliate') . ' requested access to smartlink "' . $sl['name'] . '"',
            'link'        => '/admin/smartlinks?action=requests',
        ]);
    } catch (Exception $e) {}

    echo json_encode(['ok' => true]); exit;
}

// ── Default: Index ─────────────────────────────────────────────────────────
$shortenerEnabled = (Config::get('config', 'shortener.enabled') ?? '1') === '1';

$smartlinks = Database::fetchAll(
    "SELECT sl.*, COUNT(so.id) as offer_count FROM smartlinks sl LEFT JOIN smartlink_offers so ON so.smartlink_id=sl.id WHERE sl.status='active' GROUP BY sl.id ORDER BY sl.name ASC"
);

// Load this affiliate's request statuses
$myRequests = Database::fetchAll(
    "SELECT smartlink_id, status, admin_note FROM smartlink_requests WHERE affiliate_id=?", [$affId]
);
$requestMap = [];
foreach ($myRequests as $r) $requestMap[$r['smartlink_id']] = $r;

// Load custom payouts from Advanced Payout Management
$slPayoutRows = [];
try {
    $slPayoutRows = Database::fetchAll(
        "SELECT smartlink_id, revenue, payout FROM aff_smartlink_payouts WHERE affiliate_id=?", [$affId]
    );
} catch (Exception $e) {}
$slPayoutMap = [];
foreach ($slPayoutRows as $row) $slPayoutMap[(int)$row['smartlink_id']] = $row;

require BASE_PATH . '/views/affiliate/smartlinks/index.php';
