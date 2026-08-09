<?php
/**
 * OfferCategoryController — Admin CRUD for Offer Categories
 */
Auth::check('admin');
$pageTitle = 'Offer Categories';

// Ensure tables exist (idempotent)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `offer_categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(120) NOT NULL,
        `description` TEXT NULL,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_cat_name` (`name`),
        UNIQUE KEY `uq_cat_slug` (`slug`),
        INDEX `idx_cat_status` (`status`),
        INDEX `idx_cat_sort` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Helper: generate slug ────────────────────────────────────────────────────
function _catSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── AJAX: Get offer types for a category (used by Offer Create/Edit) ─────────
if (Helpers::get('action') === 'types') {
    header('Content-Type: application/json');
    $catId = (int)Helpers::get('category_id');
    if ($catId > 0) {
        $types = Database::fetchAll(
            "SELECT id, name FROM offer_types WHERE category_id = ? AND status = 'active' ORDER BY sort_order, name",
            [$catId]
        );
    } else {
        $types = [];
    }
    echo json_encode(['success' => true, 'types' => $types]);
    exit;
}

// ── AJAX: Toggle status ──────────────────────────────────────────────────────
if (Helpers::isPost() && Helpers::postRaw('ajax_action') === 'toggle_status') {
    header('Content-Type: application/json');
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    $id = (int)Helpers::postRaw('id');
    $cat = Database::fetchOne("SELECT id, status FROM offer_categories WHERE id = ?", [$id]);
    if (!$cat) {
        echo json_encode(['success' => false, 'error' => 'Category not found']);
        exit;
    }
    $newStatus = $cat['status'] === 'active' ? 'inactive' : 'active';
    Database::update('offer_categories', ['status' => $newStatus], 'id = ?', [$id]);
    Activity::log(Auth::id(), 'offer_category.toggle_status', 'offer_category', $id, ['old_status' => $cat['status']], ['new_status' => $newStatus]);
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
    exit;
}

// ── AJAX: Run Seeder ─────────────────────────────────────────────────────────
if (Helpers::isPost() && Helpers::postRaw('ajax_action') === 'run_seeder') {
    header('Content-Type: application/json');
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    require_once BASE_PATH . '/install/seeders/offer_categories_seeder.php';
    $result = OfferCategorySeeder::run();
    echo json_encode(['success' => true, 'result' => $result]);
    exit;
}

// ── INDEX ────────────────────────────────────────────────────────────────────
if ($action === 'index') {
    $qFilter      = Helpers::get('q');
    $statusFilter = Helpers::get('status_filter');

    $where = ['1=1'];
    $params = [];

    if ($qFilter) {
        $where[] = "(c.name LIKE ? OR c.slug LIKE ?)";
        $params[] = '%' . $qFilter . '%';
        $params[] = '%' . $qFilter . '%';
    }
    if ($statusFilter) {
        $where[] = "c.status = ?";
        $params[] = $statusFilter;
    }

    $whereStr = implode(' AND ', $where);

    $categories = Database::fetchAll(
        "SELECT c.*,
                (SELECT COUNT(*) FROM offers o WHERE o.category_id = c.id) as offer_count,
                (SELECT COUNT(*) FROM offer_types ot WHERE ot.category_id = c.id) as type_count
         FROM offer_categories c
         WHERE $whereStr
         ORDER BY c.sort_order ASC, c.name ASC",
        $params
    );

    require BASE_PATH . '/views/admin/offer_categories/index.php';
}

// ── CREATE ───────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name        = trim(Helpers::post('name'));
        $slug        = trim(Helpers::post('slug')) ?: _catSlug($name);
        $description = Helpers::postRaw('description');
        $status      = Helpers::post('status') ?: 'active';
        $sortOrder   = (int)Helpers::postRaw('sort_order');

        if (!$name) $errors[] = 'Category name is required.';
        if ($name && Database::fetchOne("SELECT id FROM offer_categories WHERE name = ?", [$name])) {
            $errors[] = 'A category with this name already exists.';
        }
        if ($slug && Database::fetchOne("SELECT id FROM offer_categories WHERE slug = ?", [$slug])) {
            $slug = $slug . '-' . time(); // Auto-fix slug collision
        }

        if (empty($errors)) {
            $id = Database::insert('offer_categories', [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ]);
            Activity::log(Auth::id(), 'offer_category.create', 'offer_category', $id, null, ['name' => $name]);
            Helpers::flash('success', "Category \"$name\" created successfully.");
            Helpers::redirect('/admin/offer-categories');
        }
    }

    require BASE_PATH . '/views/admin/offer_categories/index.php';
}

// ── EDIT ─────────────────────────────────────────────────────────────────────
elseif ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $category = Database::fetchOne("SELECT * FROM offer_categories WHERE id = ?", [$id]);
    if (!$category) { Helpers::flash('error', 'Category not found.'); Helpers::redirect('/admin/offer-categories'); }
    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name        = trim(Helpers::post('name'));
        $slug        = trim(Helpers::post('slug')) ?: _catSlug($name);
        $description = Helpers::postRaw('description');
        $status      = Helpers::post('status') ?: 'active';
        $sortOrder   = (int)Helpers::postRaw('sort_order');

        if (!$name) $errors[] = 'Category name is required.';
        $dup = Database::fetchOne("SELECT id FROM offer_categories WHERE name = ? AND id != ?", [$name, $id]);
        if ($dup) $errors[] = 'A category with this name already exists.';

        $dupSlug = Database::fetchOne("SELECT id FROM offer_categories WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($dupSlug) $slug = $slug . '-' . time();

        if (empty($errors)) {
            $oldData = $category;
            Database::update('offer_categories', [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ], 'id = ?', [$id]);
            Activity::log(Auth::id(), 'offer_category.update', 'offer_category', $id, $oldData, ['name' => $name, 'status' => $status]);
            Helpers::flash('success', "Category \"$name\" updated successfully.");
            Helpers::redirect('/admin/offer-categories');
        }
    }

    require BASE_PATH . '/views/admin/offer_categories/index.php';
}

// ── DELETE ───────────────────────────────────────────────────────────────────
elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $category = Database::fetchOne("SELECT * FROM offer_categories WHERE id = ?", [$id]);
    if (!$category) { Helpers::flash('error', 'Category not found.'); Helpers::redirect('/admin/offer-categories'); }

    // Check if category is in use
    $offerCount = Database::count('offers', 'category_id = ?', [$id]);
    $typeCount  = Database::count('offer_types', 'category_id = ?', [$id]);

    if ($offerCount > 0) {
        // Deactivate instead of delete
        Database::update('offer_categories', ['status' => 'inactive'], 'id = ?', [$id]);
        Activity::log(Auth::id(), 'offer_category.deactivate', 'offer_category', $id, null, ['reason' => "Has $offerCount offers assigned"]);
        Helpers::flash('warning', "Category \"{$category['name']}\" has $offerCount offers assigned. Deactivated instead of deleted.");
    } else {
        // Also delete orphan types under this category
        if ($typeCount > 0) {
            Database::delete('offer_types', 'category_id = ?', [$id]);
        }
        Database::delete('offer_categories', 'id = ?', [$id]);
        Activity::log(Auth::id(), 'offer_category.delete', 'offer_category', $id, $category, null);
        Helpers::flash('success', "Category \"{$category['name']}\" deleted successfully.");
    }
    Helpers::redirect('/admin/offer-categories');
}
