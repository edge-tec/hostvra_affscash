<?php
// ════════════════════════════════════════════════════════════════
// api/get_reviews.php — Public: fetch approved reviews
// GET params: filter=all|proof  page=1  per_page=9
// ════════════════════════════════════════════════════════════════
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../db.php';

$db      = getDB();
$filter  = $_GET['filter']  ?? 'all';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(50, (int)($_GET['per_page'] ?? 9)));
$offset  = ($page - 1) * $perPage;

// Build WHERE
$where  = "status = 'approved'";
$params = [];

if ($filter === 'proof') {
    $where .= " AND proof_image IS NOT NULL AND proof_image != ''";
}

// Total count
$total = (int)$db->query("SELECT COUNT(*) FROM afc_reviews WHERE $where")->fetchColumn();

// Fetch page
$stmt = $db->prepare(
    "SELECT id, name, country, website, rating,
            offers, payout, tracking, support,
            review_text, proof_image, created_at
     FROM afc_reviews
     WHERE $where
     ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// Format date label
foreach ($rows as &$r) {
    $ts = strtotime($r['created_at']);
    $r['date_label'] = date('M j, Y', $ts);
    // prepend upload URL if path is relative
    if ($r['proof_image'] && !str_starts_with($r['proof_image'], 'http')) {
        $r['proof_image'] = UPLOAD_URL . basename($r['proof_image']);
    }
    unset($r['created_at']);
}
unset($r);

// Rating distribution
$dist = [];
$distStmt = $db->query(
    "SELECT rating, COUNT(*) AS cnt
     FROM afc_reviews WHERE status='approved'
     GROUP BY rating ORDER BY rating DESC"
);
foreach ($distStmt->fetchAll() as $d) {
    $dist[(int)$d['rating']] = (int)$d['cnt'];
}

// Average scores
$avg = $db->query(
    "SELECT
       ROUND(AVG(rating),2)   AS avg_overall,
       ROUND(AVG(offers),2)   AS avg_offers,
       ROUND(AVG(payout),2)   AS avg_payout,
       ROUND(AVG(tracking),2) AS avg_tracking,
       ROUND(AVG(support),2)  AS avg_support,
       COUNT(*)               AS total_approved
     FROM afc_reviews WHERE status='approved'"
)->fetch();

resp(true, [
    'reviews'  => $rows,
    'total'    => $total,
    'has_more' => ($offset + $perPage) < $total,
    'page'     => $page,
    'dist'     => $dist,
    'scores'   => $avg,
]);
