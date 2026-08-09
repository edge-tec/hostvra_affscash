<?php
/**
 * Public REST API endpoint for fetching Offer Categories and Offer Types.
 */
header('Content-Type: application/json');

try {
    Auth::check(); // Ensures user is authenticated

    $action     = Helpers::get('action') ?: 'categories';
    $categoryId = (int)Helpers::get('category_id');

    if ($action === 'types' || $categoryId > 0) {
        $where = ["status = 'active'"];
        $params = [];
        if ($categoryId > 0) {
            $where[] = "category_id = ?";
            $params[] = $categoryId;
        }
        $whereStr = implode(' AND ', $where);
        $types = Database::fetchAll(
            "SELECT id, category_id, name, slug, description, sort_order FROM offer_types WHERE $whereStr ORDER BY sort_order, name",
            $params
        );
        echo json_encode([
            'success' => true,
            'count'   => count($types),
            'data'    => $types,
        ]);
        exit;
    }

    // Default: list all active categories with their types
    $categories = Database::fetchAll(
        "SELECT id, name, slug, description, sort_order FROM offer_categories WHERE status = 'active' ORDER BY sort_order, name"
    );

    $includeTypes = filter_var(Helpers::get('include_types') ?? true, FILTER_VALIDATE_BOOLEAN);

    if ($includeTypes && !empty($categories)) {
        $catIds = array_column($categories, 'id');
        $ph = implode(',', array_fill(0, count($catIds), '?'));
        $allTypes = Database::fetchAll(
            "SELECT id, category_id, name, slug, description, sort_order FROM offer_types WHERE status = 'active' AND category_id IN ($ph) ORDER BY sort_order, name",
            $catIds
        );
        $typesByCat = [];
        foreach ($allTypes as $t) {
            $typesByCat[$t['category_id']][] = $t;
        }
        foreach ($categories as &$c) {
            $c['types'] = $typesByCat[$c['id']] ?? [];
        }
        unset($c);
    }

    echo json_encode([
        'success' => true,
        'count'   => count($categories),
        'data'    => $categories,
    ]);
    exit;

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
    exit;
}
