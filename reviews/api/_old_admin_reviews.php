<?php
// ════════════════════════════════════════════════════════════════
// api/admin_reviews.php — Admin: manage reviews
// Requires X-Admin-Auth header or admin_pwd param
//
// Actions (POST JSON body):
//   action=list             → all reviews with filter/page
//   action=approve  id=X   → approve a pending review
//   action=reject   id=X   → reject (set status=rejected)
//   action=delete   id=X   → permanently delete review + image
//   action=stats            → dashboard counts & averages
// ════════════════════════════════════════════════════════════════
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Auth');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
requireAdmin();

$db     = getDB();
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

// ── LIST ─────────────────────────────────────────────────────────
if ($action === 'list') {
    $status  = $body['status'] ?? 'pending';
    $page    = max(1, (int)($body['page'] ?? 1));
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;

    $allowed = ['pending','approved','rejected','all'];
    if (!in_array($status, $allowed)) $status = 'pending';

    $where = $status === 'all' ? '1=1' : "status = '$status'";

    $total = (int)$db->query("SELECT COUNT(*) FROM afc_reviews WHERE $where")->fetchColumn();

    $stmt = $db->prepare(
        "SELECT id, name, email, country, website, rating,
                offers, payout, tracking, support,
                review_text, proof_image, status,
                submitter_ip, created_at
         FROM afc_reviews
         WHERE $where
         ORDER BY created_at DESC
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['date_label'] = date('M j, Y · H:i', strtotime($r['created_at']));
        if ($r['proof_image'] && !str_starts_with($r['proof_image'], 'http')) {
            $r['proof_image'] = UPLOAD_URL . basename($r['proof_image']);
        }
    }
    unset($r);

    resp(true, [
        'reviews'  => $rows,
        'total'    => $total,
        'has_more' => ($offset + $perPage) < $total,
    ]);
}

// ── APPROVE ──────────────────────────────────────────────────────
if ($action === 'approve') {
    $id = $body['id'] ?? '';
    if (!$id) resp(false, [], 'Missing id');
    $db->prepare("UPDATE afc_reviews SET status='approved' WHERE id=?")->execute([$id]);
    resp(true, ['message' => 'Review approved.']);
}

// ── REJECT ───────────────────────────────────────────────────────
if ($action === 'reject') {
    $id = $body['id'] ?? '';
    if (!$id) resp(false, [], 'Missing id');
    $db->prepare("UPDATE afc_reviews SET status='rejected' WHERE id=?")->execute([$id]);
    resp(true, ['message' => 'Review rejected.']);
}

// ── DELETE ───────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = $body['id'] ?? '';
    if (!$id) resp(false, [], 'Missing id');

    // Delete image file if exists
    $row = $db->prepare("SELECT proof_image FROM afc_reviews WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch();
    if ($r && $r['proof_image']) {
        $file = UPLOAD_DIR . basename($r['proof_image']);
        if (file_exists($file)) @unlink($file);
    }

    $db->prepare("DELETE FROM afc_reviews WHERE id=?")->execute([$id]);
    resp(true, ['message' => 'Review deleted.']);
}

// ── STATS ────────────────────────────────────────────────────────
if ($action === 'stats') {
    $counts = $db->query(
        "SELECT status, COUNT(*) AS cnt FROM afc_reviews GROUP BY status"
    )->fetchAll();
    $statusMap = ['pending'=>0,'approved'=>0,'rejected'=>0];
    foreach ($counts as $c) $statusMap[$c['status']] = (int)$c['cnt'];

    $avgs = $db->query(
        "SELECT ROUND(AVG(rating),2) AS avg_rating,
                ROUND(AVG(offers),2) AS avg_offers,
                ROUND(AVG(payout),2) AS avg_payout,
                ROUND(AVG(tracking),2) AS avg_tracking,
                ROUND(AVG(support),2) AS avg_support
         FROM afc_reviews WHERE status='approved'"
    )->fetch();

    $recent = $db->query(
        "SELECT id, name, rating, LEFT(review_text,80) AS snippet,
                status, created_at
         FROM afc_reviews ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    resp(true, [
        'counts' => $statusMap,
        'avgs'   => $avgs,
        'recent' => $recent,
    ]);
}

resp(false, [], 'Unknown action: ' . $action);
