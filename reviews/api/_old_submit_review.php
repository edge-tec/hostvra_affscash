<?php
// ════════════════════════════════════════════════════════════════
// api/submit_review.php — Public: submit a new review (pending)
// POST multipart/form-data
// Fields: name, email, country, website, rating, offers, payout,
//         tracking, support, review, proof (file, optional)
// ════════════════════════════════════════════════════════════════
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(false, [], 'POST only');
}

// ── Validate required fields ──────────────────────────────────────
$name   = trim($_POST['name']   ?? '');
$email  = trim($_POST['email']  ?? '');
$review = trim($_POST['review'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);

if (!$name || !$email || !$review || $rating < 1 || $rating > 5) {
    resp(false, [], 'Please fill in all required fields and select a star rating.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    resp(false, [], 'Please enter a valid email address.');
}
if (strlen($review) < 20) {
    resp(false, [], 'Review must be at least 20 characters.');
}
if (strlen($review) > 3000) {
    resp(false, [], 'Review is too long (max 3000 characters).');
}

// ── Rate limiting: max 3 submissions per IP per 24h ──────────────
// FIX: single clean prepared statement — no SQL injection, no duplicate query
$db = getDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$ipStmt = $db->prepare(
    "SELECT COUNT(*) FROM afc_reviews
     WHERE submitter_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);
$ipStmt->execute([$ip]);
$recentCount = (int)$ipStmt->fetchColumn();

if ($recentCount >= 3) {
    resp(false, [], 'Too many submissions. Please try again tomorrow.');
}

// ── Optional category scores ─────────────────────────────────────
$offers   = ($v = (int)($_POST['offers']   ?? 0)) >= 1 && $v <= 5 ? $v : null;
$payout   = ($v = (int)($_POST['payout']   ?? 0)) >= 1 && $v <= 5 ? $v : null;
$tracking = ($v = (int)($_POST['tracking'] ?? 0)) >= 1 && $v <= 5 ? $v : null;
$support  = ($v = (int)($_POST['support']  ?? 0)) >= 1 && $v <= 5 ? $v : null;

$country = substr(trim($_POST['country'] ?? ''), 0, 100);
$website = substr(trim($_POST['website'] ?? ''), 0, 300);

// ── Handle proof image upload ─────────────────────────────────────
$proofPath = null;
if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['proof'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['size'] > $maxSize) {
        resp(false, [], 'Image too large. Max 5MB.');
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed)) {
        resp(false, [], 'Invalid file type. Allowed: JPG, PNG, WEBP, GIF.');
    }

    // Create upload directory if it does not exist
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        file_put_contents(
            UPLOAD_DIR . '.htaccess',
            "Options -Indexes\n<FilesMatch '\\.php$'>\nDeny from all\n</FilesMatch>\n"
        );
    }

    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext      = $extMap[$mime];
    $filename = genId('proof') . '.' . $ext;
    $destPath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        resp(false, [], 'Upload failed. Please try again.');
    }
    $proofPath = $filename;
}

// ── Insert review ─────────────────────────────────────────────────
$id   = genId('rv');
$stmt = $db->prepare(
    "INSERT INTO afc_reviews
       (id, name, email, country, website, rating,
        offers, payout, tracking, support,
        review_text, proof_image, submitter_ip, status, created_at)
     VALUES
       (:id, :name, :email, :country, :website, :rating,
        :offers, :payout, :tracking, :support,
        :review_text, :proof_image, :ip, 'pending', NOW())"
);
$stmt->execute([
    ':id'          => $id,
    ':name'        => substr($name, 0, 200),
    ':email'       => substr($email, 0, 300),
    ':country'     => $country,
    ':website'     => $website,
    ':rating'      => $rating,
    ':offers'      => $offers,
    ':payout'      => $payout,
    ':tracking'    => $tracking,
    ':support'     => $support,
    ':review_text' => substr($review, 0, 3000),
    ':proof_image' => $proofPath,
    ':ip'          => substr($ip, 0, 45),
]);

resp(true, ['message' => 'Review submitted! It will appear after admin approval.']);
