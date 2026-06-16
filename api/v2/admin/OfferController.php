<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    // Basic filter for now
    $whereFilters = ['1=1'];
    $whereFilters[] = "(o.is_inhouse IS NULL OR o.is_inhouse = 0)";
    $filterWhere = implode(' AND ', $whereFilters);

    $offers = Database::fetchAll(
        "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount as payout, 
                o.status, o.category, o.geo_targeting, o.device_targeting, o.created_at,
                COALESCE(CONCAT(u.first_name,' ',u.last_name), 'In-House') as adv_name
         FROM offers o
         LEFT JOIN advertisers adv ON adv.id=o.advertiser_id
         LEFT JOIN users u ON u.id=adv.user_id
         WHERE $filterWhere ORDER BY o.created_at DESC"
    );

    // Parse JSON fields
    foreach ($offers as &$offer) {
        $offer['payout'] = (float)$offer['payout'];
        $offer['geo_targeting'] = $offer['geo_targeting'] ? json_decode($offer['geo_targeting'], true) : [];
        $offer['device_targeting'] = $offer['device_targeting'] ? json_decode($offer['device_targeting'], true) : [];
    }

    echo json_encode([
        'success' => true,
        'data' => $offers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
