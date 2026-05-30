<?php
Auth::check('admin');

// Auto-create table with pending status support
try {
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
        `status`      ENUM('pending','active','inactive') DEFAULT 'active',
        `source`      ENUM('admin','public') DEFAULT 'admin',
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_sort`   (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Idempotent migration: add email + source columns to existing tables
    try { Database::query("ALTER TABLE landing_reviews ADD COLUMN `email`  VARCHAR(255) DEFAULT NULL AFTER `name`"); }  catch(\Throwable $e) {}
    try { Database::query("ALTER TABLE landing_reviews ADD COLUMN `source` ENUM('admin','public') DEFAULT 'admin' AFTER `is_featured`"); } catch(\Throwable $e) {}
    try { Database::query("ALTER TABLE landing_reviews MODIFY COLUMN `status` ENUM('pending','active','inactive') DEFAULT 'active'"); } catch(\Throwable $e) {}
} catch (\Throwable $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Approve (pending → active) ────────────────────────────────────────────
if ($action === 'approve') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)($_GET['id'] ?? 0);
        Database::update('landing_reviews', ['status' => 'active'], 'id=?', [$id]);
        Helpers::flash('success', 'Review approved and is now visible on the landing page.');
    }
    Helpers::redirect('/admin/landing/reviews');

// ── Reject (pending → inactive) ───────────────────────────────────────────
} elseif ($action === 'reject') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)($_GET['id'] ?? 0);
        Database::update('landing_reviews', ['status' => 'inactive'], 'id=?', [$id]);
        Helpers::flash('success', 'Review rejected.');
    }
    Helpers::redirect('/admin/landing/reviews');

// ── Toggle status (active ↔ inactive) ────────────────────────────────────
} elseif ($action === 'toggle') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id  = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT status FROM landing_reviews WHERE id=?", [$id]);
        if ($row) {
            $new = $row['status'] === 'active' ? 'inactive' : 'active';
            Database::update('landing_reviews', ['status' => $new], 'id=?', [$id]);
            Helpers::flash('success', 'Review status updated to ' . $new . '.');
        }
    }
    Helpers::redirect('/admin/landing/reviews');

// ── Create / Edit ──────────────────────────────────────────────────────────
} elseif ($action === 'create' || ($action === 'edit' && isset($_GET['id']))) {
    $reviewId  = $action === 'edit' ? (int)$_GET['id'] : 0;
    $review    = $reviewId ? Database::fetchOne("SELECT * FROM landing_reviews WHERE id=?", [$reviewId]) : null;
    if ($reviewId && !$review) Helpers::redirect('/admin/landing/reviews');

    $errors    = [];
    $pageTitle = $reviewId ? 'Edit Review' : 'Add Review';

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name       = trim(Helpers::postRaw('name'));
        $email      = trim(Helpers::postRaw('email'));
        $roleTitle  = trim(Helpers::postRaw('role_title'));
        $reviewText = trim(Helpers::postRaw('review_text'));
        $country    = trim(Helpers::postRaw('country'));
        $rating     = max(1, min(5, (int)Helpers::post('rating')));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $sortOrder  = (int)Helpers::post('sort_order');
        $status     = in_array(Helpers::post('status'), ['pending','active','inactive']) ? Helpers::post('status') : 'active';

        if (!$name)       $errors[] = 'Name is required.';
        if (!$reviewText) $errors[] = 'Review text is required.';

        // Avatar upload
        $avatar = $review['avatar'] ?? null;
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Avatar must be JPEG, PNG, GIF or WEBP.';
            } else {
                $dir = BASE_PATH . '/assets/uploads/landing/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext   = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fname = 'avatar_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fname)) {
                    $avatar = '/assets/uploads/landing/' . $fname;
                }
            }
        }

        if (!$errors) {
            $data = [
                'name'        => $name,
                'email'       => $email ?: null,
                'role_title'  => $roleTitle ?: null,
                'avatar'      => $avatar,
                'rating'      => $rating,
                'review_text' => $reviewText,
                'country'     => $country ?: null,
                'is_featured' => $isFeatured,
                'sort_order'  => $sortOrder,
                'status'      => $status,
                'source'      => 'admin',
            ];
            if ($reviewId) {
                Database::update('landing_reviews', $data, 'id=?', [$reviewId]);
                Helpers::flash('success', 'Review updated.');
            } else {
                Database::insert('landing_reviews', $data);
                Helpers::flash('success', 'Review added.');
            }
            Helpers::redirect('/admin/landing/reviews');
        }
    }

    require BASE_PATH . '/views/admin/landing/review_form.php';

// ── Delete ─────────────────────────────────────────────────────────────────
} elseif ($action === 'delete' && isset($_GET['id'])) {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Database::query("DELETE FROM landing_reviews WHERE id=?", [(int)$_GET['id']]);
        Helpers::flash('success', 'Review deleted.');
    }
    Helpers::redirect('/admin/landing/reviews');

// ── Index ──────────────────────────────────────────────────────────────────
} else {
    $pageTitle    = 'Landing Reviews';
    $q            = trim(Helpers::get('q') ?? '');
    $statusFilter = Helpers::get('status_filter') ?? '';
    $ratingFilter = (int)(Helpers::get('rating') ?? 0);

    $params = [];
    $where  = '1=1';
    if ($q) {
        $where   .= ' AND (name LIKE ? OR review_text LIKE ? OR country LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if (in_array($statusFilter, ['pending','active','inactive'])) {
        $where   .= ' AND status=?';
        $params[] = $statusFilter;
    }
    if ($ratingFilter >= 1 && $ratingFilter <= 5) {
        $where   .= ' AND rating=?';
        $params[] = $ratingFilter;
    }

    $reviews = Database::fetchAll(
        "SELECT * FROM landing_reviews WHERE $where ORDER BY FIELD(status,'pending','active','inactive'), sort_order ASC, id DESC",
        $params
    );

    // Counts per status for tabs
    $pendingCount  = 0;
    $activeCount   = 0;
    $inactiveCount = 0;
    try {
        $counts = Database::fetchAll("SELECT status, COUNT(*) as c FROM landing_reviews GROUP BY status");
        foreach ($counts as $c) {
            if ($c['status'] === 'pending')  $pendingCount  = (int)$c['c'];
            if ($c['status'] === 'active')   $activeCount   = (int)$c['c'];
            if ($c['status'] === 'inactive') $inactiveCount = (int)$c['c'];
        }
    } catch (\Throwable $e) {}

    require BASE_PATH . '/views/admin/landing/review_index.php';
}
