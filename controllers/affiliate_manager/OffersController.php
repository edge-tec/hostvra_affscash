<?php
Auth::check('affiliate_manager');
$pageTitle = 'Offers';

$affIds = Auth::managerAffiliateIds();

// Filters
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
    "SELECT o.*, COALESCE(CONCAT(u.first_name,' ',u.last_name), 'In-House') as adv_name,
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

$categories  = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE category IS NOT NULL AND category != '' ORDER BY category");
$offerTypes  = Database::fetchAll("SELECT DISTINCT offer_type FROM offers WHERE offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type");
$appUrl      = rtrim(Config::get('config', 'app.url') ?? '', '/');

// Managed affiliates for link generator
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

require BASE_PATH . '/views/affiliate_manager/offers.php';
