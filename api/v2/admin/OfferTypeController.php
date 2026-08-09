<?php
/**
 * Native App API v2 — Admin Offer Types Controller
 */
header('Content-Type: application/json');

try {
    Auth::check('admin');

    $method = $_SERVER['REQUEST_METHOD'];
    $action = Helpers::get('action') ?: 'list';

    // GET /api/v2/admin/offer-types
    if ($method === 'GET') {
        $catId = (int)Helpers::get('category_id');
        $where = ['1=1'];
        $params = [];

        if ($catId > 0) {
            $where[] = "t.category_id = ?";
            $params[] = $catId;
        }

        $whereStr = implode(' AND ', $where);

        $types = Database::fetchAll(
            "SELECT t.*, c.name as category_name,
                    (SELECT COUNT(*) FROM offers o WHERE o.offer_type_id = t.id) as offer_count
             FROM offer_types t
             LEFT JOIN offer_categories c ON c.id = t.category_id
             WHERE $whereStr
             ORDER BY c.sort_order ASC, t.sort_order ASC, t.name ASC",
            $params
        );
        echo json_encode(['success' => true, 'count' => count($types), 'data' => $types]);
        exit;
    }

    // POST /api/v2/admin/offer-types (Create / Update / Delete)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $act   = $input['action'] ?? $action;

        if ($act === 'create') {
            $name        = trim($input['name'] ?? '');
            $slug        = trim($input['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $categoryId  = (int)($input['category_id'] ?? 0);
            $description = $input['description'] ?? null;
            $status      = $input['status'] ?? 'active';
            $sortOrder   = (int)($input['sort_order'] ?? 0);

            if (!$name || $categoryId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Name and Category ID are required']);
                exit;
            }

            $id = Database::insert('offer_types', [
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ]);

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Offer type created']);
            exit;
        }

        if ($act === 'update') {
            $id          = (int)($input['id'] ?? 0);
            $name        = trim($input['name'] ?? '');
            $slug        = trim($input['slug'] ?? '');
            $categoryId  = (int)($input['category_id'] ?? 0);
            $description = $input['description'] ?? null;
            $status      = $input['status'] ?? 'active';
            $sortOrder   = (int)($input['sort_order'] ?? 0);

            if ($id <= 0 || !$name || $categoryId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid ID, name, or category ID']);
                exit;
            }

            Database::update('offer_types', [
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'status'      => $status,
                'sort_order'  => $sortOrder,
            ], 'id = ?', [$id]);

            echo json_encode(['success' => true, 'message' => 'Offer type updated']);
            exit;
        }

        if ($act === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
                exit;
            }
            $offerCount = Database::count('offers', 'offer_type_id = ?', [$id]);
            if ($offerCount > 0) {
                Database::update('offer_types', ['status' => 'inactive'], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Offer type deactivated due to associated offers']);
            } else {
                Database::delete('offer_types', 'id = ?', [$id]);
                echo json_encode(['success' => true, 'message' => 'Offer type deleted']);
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
