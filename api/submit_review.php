<?php
/**
 * api/submit_review.php
 * Public review submission endpoint — real file, bypasses router.
 * Returns JSON. Inserts review with status='pending' for admin approval.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

if (!file_exists(CONFIG_PATH . '/config.json')) {
    echo json_encode(['success' => false, 'error' => 'Not configured.']);
    exit;
}

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Helpers.php';

Config::init(CONFIG_PATH);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required.']);
    exit;
}

// ── Input validation ────────────────────────────────────────────────────────
$name       = trim(strip_tags($_POST['name']        ?? ''));
$email      = trim($_POST['email']                  ?? '');
$roleTitle  = trim(strip_tags($_POST['role_title']  ?? ''));
$reviewText = trim(strip_tags($_POST['review_text'] ?? ''));
$country    = trim(strip_tags($_POST['country']     ?? ''));
$rating     = max(1, min(5, (int)($_POST['rating']  ?? 5)));

// Spam honeypot — silent drop
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true]);
    exit;
}

$errors = [];
if (!$name || strlen($name) < 2)                                  $errors[] = 'Name is required (min 2 characters).';
if (strlen($name) > 100)                                          $errors[] = 'Name is too long.';
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email format.';
if (!$reviewText || strlen($reviewText) < 10)                     $errors[] = 'Review text must be at least 10 characters.';
if (strlen($reviewText) > 1000)                                   $errors[] = 'Review text is too long (max 1000 characters).';

if ($errors) {
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

try {
    // Ensure table exists
    Database::query("CREATE TABLE IF NOT EXISTS `landing_reviews` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(200) NOT NULL,
        `email`       VARCHAR(255) DEFAULT NULL,
        `role_title`  VARCHAR(200) DEFAULT NULL,
        `avatar`      VARCHAR(512) DEFAULT NULL,
        `rating`      TINYINT UNSIGNED DEFAULT 5,
        `review_text` TEXT NOT NULL,
        `country`     VARCHAR(100) DEFAULT NULL,
        `is_featured` TINYINT(1) DEFAULT 0,
        `sort_order`  INT DEFAULT 0,
        `status`      ENUM('pending','active','inactive') DEFAULT 'pending',
        `source`      ENUM('admin','public') DEFAULT 'public',
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Rate limit: max 3 submissions per email/IP per hour
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipHash = substr(md5($ip), 0, 16);
    $key    = $email ?: $ipHash;
    $recent = Database::fetchOne(
        "SELECT COUNT(*) as c FROM landing_reviews
         WHERE (email = ? OR email = ?) AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$key, $ipHash]
    );
    if ($recent && (int)$recent['c'] >= 3) {
        echo json_encode(['success' => false, 'error' => 'Too many submissions. Please try again later.']);
        exit;
    }

    Database::insert('landing_reviews', [
        'name'        => $name,
        'email'       => $email ?: null,
        'role_title'  => $roleTitle ?: null,
        'rating'      => $rating,
        'review_text' => $reviewText,
        'country'     => $country ?: null,
        'status'      => 'pending',
        'source'      => 'public',
        'is_featured' => 0,
        'sort_order'  => 0,
    ]);

    echo json_encode(['success' => true, 'message' => 'Thank you! Your review has been submitted and is pending approval.']);

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Could not save review. Please try again.']);
}
