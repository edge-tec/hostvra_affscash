<?php
Auth::check('affiliate_manager');
$pageTitle = 'In-House Offers';

$affIds = Auth::managerAffiliateIds();

// Filters
$qFilter         = Helpers::get('q');
$statusFilter    = Helpers::get('status_filter') ?: 'active';
$offerTypeFilter = Helpers::get('offer_type_filter');
$countryFilter   = Helpers::get('country');
$idFilter        = (int)Helpers::get('offer_id');
$accessFilter    = Helpers::get('access_filter') ?: '';

$where  = ['o.is_inhouse = 1'];
$params = [];

if ($idFilter > 0)    { $where[] = "o.id = ?";            $params[] = $idFilter; }
if ($statusFilter)    { $where[] = "o.status = ?";        $params[] = $statusFilter; }
if ($qFilter)         { $where[] = "o.name LIKE ?";       $params[] = '%' . $qFilter . '%'; }
if ($offerTypeFilter) { $where[] = "o.offer_type = ?";    $params[] = $offerTypeFilter; }
if ($countryFilter)   { $where[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $params[] = $countryFilter; }
switch ($accessFilter) {
    case 'active':     $where[] = "o.status = 'active'"; break;
    case 'inactive':   $where[] = "o.status != 'active'"; break;
    case 'request':    $where[] = "o.require_approval = 1"; break;
    case 'all_access': $where[] = "o.require_approval = 0"; break;
}

$whereStr = implode(' AND ', $where);

// All in-house offers — no affiliate scope restriction (read-only browsing)
$offers = Database::fetchAll(
    "SELECT o.*,
            (SELECT COUNT(*) FROM affiliate_offers ao WHERE ao.offer_id=o.id AND ao.status='approved') as aff_count,
            (SELECT COUNT(*) FROM clicks c WHERE c.offer_id=o.id AND DATE(c.clicked_at)=CURDATE()) as today_clicks,
            (SELECT COUNT(*) FROM conversions cv WHERE cv.offer_id=o.id AND DATE(cv.converted_at)=CURDATE()) as today_convs
     FROM offers o
     WHERE $whereStr
     ORDER BY o.created_at DESC",
    $params
);

$offerTypes = Database::fetchAll(
    "SELECT DISTINCT offer_type FROM offers WHERE is_inhouse=1 AND offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type"
);

$appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

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

require BASE_PATH . '/views/affiliate_manager/inhouse_offers.php';
