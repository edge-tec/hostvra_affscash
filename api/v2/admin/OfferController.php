<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    $qFilter         = Helpers::get('q');
    $catFilter       = Helpers::get('category');
    $typeFilter      = Helpers::get('payout_type');
    $statusFilter    = Helpers::get('status');
    $offerTypeFilter = Helpers::get('offer_type');
    $countryFilter   = Helpers::get('country');
    $deviceFilter    = Helpers::get('device');
    $idFilter        = (int)Helpers::get('offer_id');
    $accessFilter    = Helpers::get('access');
    $isInHouse       = Helpers::get('in_house');

    $whereFilters = ['1=1'];
    $filterParams = [];
    if ($idFilter > 0)    { $whereFilters[] = "o.id = ?";                                    $filterParams[] = $idFilter; }
    if ($qFilter)         { $whereFilters[] = "o.name LIKE ?";                               $filterParams[] = '%' . $qFilter . '%'; }
    if ($catFilter)       { $whereFilters[] = "o.category = ?";                              $filterParams[] = $catFilter; }
    if ($typeFilter)      { $whereFilters[] = "o.payout_type = ?";                           $filterParams[] = $typeFilter; }
    if ($statusFilter)    { $whereFilters[] = "o.status = ?";                                $filterParams[] = $statusFilter; }
    if ($offerTypeFilter) { $whereFilters[] = "o.offer_type = ?";                            $filterParams[] = $offerTypeFilter; }
    if ($countryFilter)   { $whereFilters[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $filterParams[] = $countryFilter; }
    if ($deviceFilter)    { $whereFilters[] = "JSON_CONTAINS(o.device_targeting, JSON_QUOTE(?))"; $filterParams[] = $deviceFilter; }
    switch ($accessFilter) {
        case 'active':     $whereFilters[] = "o.status = 'active'"; break;
        case 'inactive':   $whereFilters[] = "o.status != 'active'"; break;
        case 'request':    $whereFilters[] = "o.require_approval = 1"; break;
        case 'all_access': $whereFilters[] = "o.require_approval = 0 AND COALESCE(o.visibility,'public') != 'private'"; break;
    }

    if ($isInHouse == '1' || $isInHouse === 'true') {
        $whereFilters[] = "o.is_inhouse = 1";
    } else {
        $whereFilters[] = "(o.is_inhouse IS NULL OR o.is_inhouse = 0)";
    }
    
    $filterWhere = implode(' AND ', $whereFilters);

    $offers = Database::fetchAll(
        "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount as payout, o.revenue_amount as revenue,
                o.status, o.category, o.geo_targeting, o.device_targeting, o.created_at, o.daily_cap, o.total_cap,
                o.require_approval, o.visibility, o.offer_type, o.offer_url, o.advertiser_id,
                COALESCE(CONCAT(u.first_name,' ',u.last_name), 'In-House') as adv_name,
                (SELECT GROUP_CONCAT(category) FROM offers WHERE category IS NOT NULL) as all_categories,
                (SELECT GROUP_CONCAT(offer_type) FROM offers WHERE offer_type IS NOT NULL) as all_offer_types
         FROM offers o
         LEFT JOIN advertisers adv ON adv.id=o.advertiser_id
         LEFT JOIN users u ON u.id=adv.user_id
         WHERE $filterWhere ORDER BY o.created_at DESC",
        $filterParams
    );

    $categories = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $offerTypes = Database::fetchAll("SELECT DISTINCT offer_type FROM offers WHERE offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type");
    $advertisers = Database::fetchAll("SELECT adv.id, CONCAT(u.first_name,' ',u.last_name) as label FROM advertisers adv JOIN users u ON u.id=adv.user_id");

    foreach ($offers as &$offer) {
        $offer['payout'] = (float)$offer['payout'];
        $offer['revenue'] = (float)$offer['revenue'];
        $offer['daily_cap'] = (int)$offer['daily_cap'];
        $offer['total_cap'] = (int)$offer['total_cap'];
        $offer['geo_targeting'] = $offer['geo_targeting'] ? json_decode($offer['geo_targeting'], true) : [];
        $offer['device_targeting'] = $offer['device_targeting'] ? json_decode($offer['device_targeting'], true) : [];
        $offer['require_approval'] = (bool)$offer['require_approval'];
        unset($offer['all_categories'], $offer['all_offer_types']);
    }

    echo json_encode([
        'success' => true,
        'data' => $offers,
        'meta' => [
            'categories' => array_column($categories, 'category'),
            'offerTypes' => array_column($offerTypes, 'offer_type'),
            'advertisers' => $advertisers
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
