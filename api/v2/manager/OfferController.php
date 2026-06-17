<?php
header('Content-Type: application/json');
Auth::check('affiliate_manager');

try {
    $action = Helpers::get('action') ?: 'list';
    $affIds = Auth::managerAffiliateIds();

    if ($action === 'list') {
        $qFilter         = Helpers::get('q');
        $catFilter       = Helpers::get('category');
        $typeFilter      = Helpers::get('payout_type');
        $offerTypeFilter = Helpers::get('offer_type');
        $statusFilter    = Helpers::get('status_filter') ?: 'active';
        $countryFilter   = Helpers::get('country');
        $deviceFilter    = Helpers::get('device');
        $idFilter        = (int)Helpers::get('offer_id');
        $accessFilter    = Helpers::get('access_filter') ?: '';

        $tab = Helpers::get('tab') === 'inhouse' ? 'inhouse' : 'regular';

        if ($tab === 'inhouse') {
            $whereFilters = ['1=1', 'o.is_inhouse = 1'];
        } else {
            $whereFilters = ['1=1', '(o.is_inhouse IS NULL OR o.is_inhouse = 0)'];
        }

        $filterParams = [];
        if ($idFilter > 0)    { $whereFilters[] = "o.id = ?";           $filterParams[] = $idFilter; }
        if ($qFilter)         { $whereFilters[] = "o.name LIKE ?";      $filterParams[] = '%'.$qFilter.'%'; }
        if ($catFilter)       { $whereFilters[] = "o.category = ?";     $filterParams[] = $catFilter; }
        if ($typeFilter)      { $whereFilters[] = "o.payout_type = ?";  $filterParams[] = $typeFilter; }
        if ($offerTypeFilter) { $whereFilters[] = "o.offer_type = ?";   $filterParams[] = $offerTypeFilter; }
        if ($statusFilter)    { $whereFilters[] = "o.status = ?";       $filterParams[] = $statusFilter; }
        if ($countryFilter)   { $whereFilters[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $filterParams[] = $countryFilter; }
        if ($deviceFilter)    { $whereFilters[] = "JSON_CONTAINS(o.device_targeting, JSON_QUOTE(?))"; $filterParams[] = $deviceFilter; }
        switch ($accessFilter) {
            case 'active':     $whereFilters[] = "o.status = 'active'"; break;
            case 'inactive':   $whereFilters[] = "o.status != 'active'"; break;
            case 'request':    $whereFilters[] = "o.require_approval = 1"; break;
            case 'all_access': $whereFilters[] = "o.require_approval = 0 AND COALESCE(o.visibility,'public') != 'private'"; break;
        }

        $filterWhere = implode(' AND ', $whereFilters);

        $offers = Database::fetchAll(
            "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount as payout, 
                    o.status, o.category, o.geo_targeting, o.device_targeting, o.created_at, o.is_inhouse, o.offer_type,
                    COALESCE(CONCAT(u.first_name,' ',u.last_name), 'In-House') as adv_name,
                    COUNT(DISTINCT ao.affiliate_id) as aff_count
             FROM offers o
             LEFT JOIN advertisers adv ON adv.id=o.advertiser_id
             LEFT JOIN users u ON u.id=adv.user_id
             LEFT JOIN affiliate_offers ao ON ao.offer_id=o.id AND ao.status='approved'
             WHERE $filterWhere
             GROUP BY o.id
             ORDER BY o.created_at DESC",
            $filterParams
        );

        foreach ($offers as &$offer) {
            $offer['payout'] = (float)$offer['payout'];
            $offer['geo_targeting'] = $offer['geo_targeting'] ? json_decode($offer['geo_targeting'], true) : [];
            $offer['device_targeting'] = $offer['device_targeting'] ? json_decode($offer['device_targeting'], true) : [];
            $offer['aff_count'] = (int)$offer['aff_count'];
            $offer['is_inhouse'] = !empty($offer['is_inhouse']);
            
            // Fraud Score
            $oScore = FraudScore::forOffer((int)$offer['id']);
            $offer['fraud_score'] = $oScore;
            $offer['fraud_level'] = FraudScore::level($oScore); // low, medium, high
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'offers' => $offers,
                'tracking_url_base' => Helpers::trackingUrl() . '/click/'
            ]
        ]);
        exit;
    }

    if ($action === 'filters') {
        $categories  = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $offerTypes  = Database::fetchAll("SELECT DISTINCT offer_type FROM offers WHERE offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type");
        
        $managedAffiliates = [];
        if (!empty($affIds)) {
            $inSql = implode(',', array_fill(0, count($affIds), '?'));
            $managedAffiliates = Database::fetchAll(
                "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
                 FROM affiliates af JOIN users u ON u.id=af.user_id
                 WHERE af.id IN ($inSql) AND u.status='active'
                 ORDER BY name",
                $affIds
            );
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'categories' => array_column($categories, 'category'),
                'offer_types' => array_column($offerTypes, 'offer_type'),
                'managed_affiliates' => $managedAffiliates,
                'payout_types' => ['CPA','CPL','CPS','CPM','CPC','RevShare','Trial','Other'],
                'statuses' => ['active','paused','pending'],
                'devices' => ['desktop','mobile','tablet']
            ]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
