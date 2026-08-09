<?php
/**
 * OfferTypeController — Admin CRUD for Offer Types
 */
Auth::check('admin');
$pageTitle = 'Offer Types';

// Ensure tables exist (idempotent)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `offer_types` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT UNSIGNED NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(120) NOT NULL,
        `description` TEXT NULL,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_type_cat_name` (`category_id`, `name`),
        UNIQUE KEY `uq_type_slug` (`slug`),
        INDEX `idx_type_category` (`category_id`),
        INDEX `idx_type_status` (`status`),
        INDEX `idx_type_sort` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Helper: generate slug ────────────────────────────────────────────────────
function _typeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ── AJAX: Toggle status ──────────────────────────────────────────────────────
if (Helpers::isPost() && Helpers::postRaw('ajax_action') === 'toggle_status') {
    header('Content-Type: application/json');
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    $id = (int)Helpers::postRaw('id');
    $type = Database::fetchOne("SELECT id, status FROM offer_types WHERE id = ?", [$id]);
    if (!$type) {
        echo json_encode(['success' => false, 'error' => 'Offer type not found']);
        exit;
    }
    $newStatus = $type['status'] === 'active' ? 'inactive' : 'active';
    Database::update('offer_types', ['status' => $newStatus], 'id = ?', [$id]);
    Activity::log(Auth::id(), 'offer_type.toggle_status', 'offer_type', $id, ['old_status' => $type['status']], ['new_status' => $newStatus]);
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
    exit;
}

// ── INDEX ────────────────────────────────────────────────────────────────────
if ($action === 'index') {
    $qFilter      = Helpers::get('q');
    $statusFilter = Helpers::get('status_filter');
    $catIdFilter  = (int)Helpers::get('category_id');

    $where = ['1=1'];
    $params = [];

    if ($qFilter) {
        $where[] = "(t.name LIKE ? OR t.slug LIKE ?)";
        $params[] = '%' . $qFilter . '%';
        $params[] = '%' . $qFilter . '%';
    }
    if ($statusFilter) {
        $where[] = "t.status = ?";
        $params[] = $statusFilter;
    }
    if ($catIdFilter > 0) {
        $where[] = "t.category_id = ?";
        $params[] = $catIdFilter;
    }

    $whereStr = implode(' AND ', $where);

    $offerTypes = Database::fetchAll(
        "SELECT t.*, c.name as category_name,
                (SELECT COUNT(*) FROM offers o WHERE o.offer_type_id = t.id) as offer_count
         FROM offer_types t
         LEFT JOIN offer_categories c ON c.id = t.category_id
         WHERE $whereStr
         ORDER BY c.sort_order ASC, c.name ASC, t.sort_order ASC, t.name ASC",
        $params
    );

    // All categories for filter dropdown and create form
    $allCategories = Database::fetchAll(
        "SELECT id, name, status FROM offer_categories ORDER BY sort_order, name"
    );

    require BASE_PATH . '/views/admin/offer_types/index.php';
}

// ── CREATE ───────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $errors = [];
    $allCategories = Database::fetchAll(
        "SELECT id, name, status FROM offer_categories ORDER BY sort_order, name"
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name        = trim(Helpers::post('name'));
        $slug        = trim(Helpers::post('slug')) ?: _typeSlug($name);
        $categoryId  = (int)Helpers::postRaw('category_id');
        $description = Helpers::postRaw('description');
        $status      = Helpers::post('status') ?: 'active';
        $sortOrder   = (int)Helpers::postRaw('sort_order');

        if (!$name) $errors[] = 'Offer type name is required.';
        if (!$categoryId) $errors[] = 'Category is required.';

        // Validate category exists
        if ($categoryId && !Database::fetchOne("SELECT id FROM offer_categories WHERE id = ?", [$categoryId])) {
            $errors[] = 'Selected category does not exist.';
        }

        // Check uniqueness within category
        if ($name && $categoryId) {
            $dup = Database::fetchOne(
                "SELECT id FROM offer_types WHERE category_id = ? AND name = ?",
                [$categoryId, $name]
            );
            if ($dup) $errors[] = 'An offer type with this name already exists in the selected category.';
        }

        // Unique slug
        if ($slug && Database::fetchOne("SELECT id FROM offer_types WHERE slug = ?", [$slug])) {
            $slug = $slug . '-' . time();
        }

        if (empty($errors)) {
            $id = Database::insert('offer_types', [
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ]);
            Activity::log(Auth::id(), 'offer_type.create', 'offer_type', $id, null, ['name' => $name, 'category_id' => $categoryId]);
            Helpers::flash('success', "Offer type \"$name\" created successfully.");
            Helpers::redirect('/admin/offer-types');
        }
    }

    require BASE_PATH . '/views/admin/offer_types/index.php';
}

// ── EDIT ─────────────────────────────────────────────────────────────────────
elseif ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $offerType = Database::fetchOne("SELECT * FROM offer_types WHERE id = ?", [$id]);
    if (!$offerType) { Helpers::flash('error', 'Offer type not found.'); Helpers::redirect('/admin/offer-types'); }

    $allCategories = Database::fetchAll(
        "SELECT id, name, status FROM offer_categories ORDER BY sort_order, name"
    );
    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name        = trim(Helpers::post('name'));
        $slug        = trim(Helpers::post('slug')) ?: _typeSlug($name);
        $categoryId  = (int)Helpers::postRaw('category_id');
        $description = Helpers::postRaw('description');
        $status      = Helpers::post('status') ?: 'active';
        $sortOrder   = (int)Helpers::postRaw('sort_order');

        if (!$name) $errors[] = 'Offer type name is required.';
        if (!$categoryId) $errors[] = 'Category is required.';

        if ($categoryId && !Database::fetchOne("SELECT id FROM offer_categories WHERE id = ?", [$categoryId])) {
            $errors[] = 'Selected category does not exist.';
        }

        // Check uniqueness within category (exclude self)
        if ($name && $categoryId) {
            $dup = Database::fetchOne(
                "SELECT id FROM offer_types WHERE category_id = ? AND name = ? AND id != ?",
                [$categoryId, $name, $id]
            );
            if ($dup) $errors[] = 'An offer type with this name already exists in the selected category.';
        }

        // Unique slug (exclude self)
        $dupSlug = Database::fetchOne("SELECT id FROM offer_types WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($dupSlug) $slug = $slug . '-' . time();

        if (empty($errors)) {
            $oldData = $offerType;
            Database::update('offer_types', [
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ], 'id = ?', [$id]);
            Activity::log(Auth::id(), 'offer_type.update', 'offer_type', $id, $oldData, ['name' => $name]);
            Helpers::flash('success', "Offer type \"$name\" updated successfully.");
            Helpers::redirect('/admin/offer-types');
        }
    }

    require BASE_PATH . '/views/admin/offer_types/index.php';
}

// ── DELETE ───────────────────────────────────────────────────────────────────
elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $offerType = Database::fetchOne("SELECT * FROM offer_types WHERE id = ?", [$id]);
    if (!$offerType) { Helpers::flash('error', 'Offer type not found.'); Helpers::redirect('/admin/offer-types'); }

    $offerCount = Database::count('offers', 'offer_type_id = ?', [$id]);

    if ($offerCount > 0) {
        Database::update('offer_types', ['status' => 'inactive'], 'id = ?', [$id]);
        Activity::log(Auth::id(), 'offer_type.deactivate', 'offer_type', $id, null, ['reason' => "Has $offerCount offers"]);
        Helpers::flash('warning', "Offer type \"{$offerType['name']}\" has $offerCount offers assigned. Deactivated instead of deleted.");
    } else {
        Database::delete('offer_types', 'id = ?', [$id]);
        Activity::log(Auth::id(), 'offer_type.delete', 'offer_type', $id, $offerType, null);
        Helpers::flash('success', "Offer type \"{$offerType['name']}\" deleted successfully.");
    }
    Helpers::redirect('/admin/offer-types');
}
