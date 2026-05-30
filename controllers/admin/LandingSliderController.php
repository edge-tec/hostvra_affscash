<?php
Auth::check('admin');

// Auto-create table
try {
    Database::query("CREATE TABLE IF NOT EXISTS `landing_sliders` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title`      VARCHAR(255) NOT NULL,
        `subtitle`   TEXT,
        `image`      VARCHAR(512) DEFAULT NULL,
        `link`       VARCHAR(512) DEFAULT '/tracker/register.php',
        `badge1`     VARCHAR(50) DEFAULT NULL,
        `badge2`     VARCHAR(50) DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `status`     ENUM('active','inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_sort`   (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Toggle status ──────────────────────────────────────────────────────────
if ($action === 'toggle') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id  = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT status FROM landing_sliders WHERE id=?", [$id]);
        if ($row) {
            $new = $row['status'] === 'active' ? 'inactive' : 'active';
            Database::update('landing_sliders', ['status' => $new], 'id=?', [$id]);
            Helpers::flash('success', 'Slider status updated to ' . $new . '.');
        }
    }
    Helpers::redirect('/admin/landing/sliders');

// ── Create / Edit ──────────────────────────────────────────────────────────
} elseif ($action === 'create' || ($action === 'edit' && isset($_GET['id']))) {
    $sliderId  = $action === 'edit' ? (int)$_GET['id'] : 0;
    $slider    = $sliderId ? Database::fetchOne("SELECT * FROM landing_sliders WHERE id=?", [$sliderId]) : null;
    if ($sliderId && !$slider) Helpers::redirect('/admin/landing/sliders');

    $errors    = [];
    $pageTitle = $sliderId ? 'Edit Slider' : 'Add Slider';

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $title     = trim(Helpers::postRaw('title'));
        $subtitle  = trim(Helpers::postRaw('subtitle'));
        $link      = trim(Helpers::postRaw('link')) ?: '/tracker/register.php';
        $badge1    = trim(Helpers::postRaw('badge1'));
        $badge2    = trim(Helpers::postRaw('badge2'));
        $sortOrder = (int)Helpers::post('sort_order');
        $status    = in_array(Helpers::post('status'), ['active','inactive']) ? Helpers::post('status') : 'active';

        if (!$title) $errors[] = 'Title is required.';

        // Image upload
        $image = $slider['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Image must be JPEG, PNG, GIF or WEBP.';
            } else {
                $dir = BASE_PATH . '/assets/uploads/landing/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext   = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = 'slide_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                    $image = '/assets/uploads/landing/' . $fname;
                }
            }
        }

        if (!$errors) {
            $data = [
                'title'      => $title,
                'subtitle'   => $subtitle,
                'image'      => $image,
                'link'       => $link,
                'badge1'     => $badge1 ?: null,
                'badge2'     => $badge2 ?: null,
                'sort_order' => $sortOrder,
                'status'     => $status,
            ];
            if ($sliderId) {
                Database::update('landing_sliders', $data, 'id=?', [$sliderId]);
                Helpers::flash('success', 'Slider updated.');
            } else {
                Database::insert('landing_sliders', $data);
                Helpers::flash('success', 'Slider created.');
            }
            Helpers::redirect('/admin/landing/sliders');
        }
    }

    require BASE_PATH . '/views/admin/landing/slider_form.php';

// ── Delete ─────────────────────────────────────────────────────────────────
} elseif ($action === 'delete' && isset($_GET['id'])) {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Database::query("DELETE FROM landing_sliders WHERE id=?", [(int)$_GET['id']]);
        Helpers::flash('success', 'Slider deleted.');
    }
    Helpers::redirect('/admin/landing/sliders');

// ── Index ──────────────────────────────────────────────────────────────────
} else {
    $pageTitle    = 'Landing Sliders';
    $q            = trim(Helpers::get('q') ?? '');
    $statusFilter = Helpers::get('status_filter') ?? '';

    $params = [];
    $where  = '1=1';
    if ($q) {
        $where   .= ' AND (title LIKE ? OR subtitle LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if (in_array($statusFilter, ['active','inactive'])) {
        $where   .= ' AND status=?';
        $params[] = $statusFilter;
    }

    $sliders = Database::fetchAll(
        "SELECT * FROM landing_sliders WHERE $where ORDER BY sort_order ASC, id DESC",
        $params
    );
    require BASE_PATH . '/views/admin/landing/slider_index.php';
}
