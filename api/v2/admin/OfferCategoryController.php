<?php
/**
 * Native App API v2 — Admin Offer Categories Controller
 */
header('Content-Type: application/json');

try {
    Auth::check('admin');

    $method = $_SERVER['REQUEST_METHOD'];
    $action = Helpers::get('action') ?: 'list';

    // GET /api/v2/admin/offer-categories
    if ($method === 'GET') {
        $id = (int)Helpers::get('id');
        if ($id > 0) {
            $cat = Database::fetchOne("SELECT * FROM offer_categories WHERE id = ?", [$id]);
            if (!$cat) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Category not found']);
                exit;
            }
            $cat['types'] = Database::fetchAll("SELECT * FROM offer_types WHERE category_id = ? ORDER BY sort_order, name", [$id]);
            echo json_encode(['success' => true, 'data' => $cat]);
            exit;
        }

        $categories = Database::fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM offers o WHERE o.category_id = c.id) as offer_count,
                    (SELECT COUNT(*) FROM offer_types ot WHERE ot.category_id = c.id) as type_count
             FROM offer_categories c
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        echo json_encode(['success' => true, 'count' => count($categories), 'data' => $categories]);
        exit;
    }

    // POST /api/v2/admin/offer-categories (Create / Update / Delete)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $act   = $input['action'] ?? $action;

        if ($act === 'create') {
            $name        = trim($input['name'] ?? '');
            $slug        = trim($input['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $description = $input['description'] ?? null;
            $status      = $input['status'] ?? 'active';
            $sortOrder   = (int)($input['sort_order'] ?? 0);

            if (!$name) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Category name is required']);
                exit;
            }

            if (Database::fetchOne("SELECT id FROM offer_categories WHERE name = ?", [$name])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Category name already exists']);
                exit;
            }

            $id = Database::insert('offer_categories', [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ]);

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Category created']);
            exit;
        }

        if ($act === 'update') {
            $id          = (int)($input['id'] ?? 0);
            $name        = trim($input['name'] ?? '');
            $slug        = trim($input['slug'] ?? '');
            $description = $input['description'] ?? null;
            $status      = $input['status'] ?? 'active';
            $sortOrder   = (int)($input['sort_order'] ?? 0);

            if ($id <= 0 || !$name) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid ID or name']);
                exit;
            }

            Database::update('offer_categories', [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => 'Category updated']);
            exit;
        }

        if ($act === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
                exit;
            }
            $offerCount = Database::count('offers', 'category_id = ?', [$id]);
            if ($offerCount > 0) {
                Database::update('offer_categories', ['status' => 'inactive'], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Category deactivated due to associated offers']);
            } else {
                Database::delete('offer_categories', 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Category deleted']);
            }
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
