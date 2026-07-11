<?php
/**
 * Public Offers Controller
 * Handles the display, pagination, and filtering of public offers.
 */

if (!defined('BASE_PATH')) exit;

$seoTitle = Config::get('config', 'app.name') . ' - Explore Top CPA Offers';
$seoDescription = 'Discover high-converting CPA, CPL, and CPI offers across all verticals. Start earning today with our top exclusive deals and fast payouts.';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 24; // Offers per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$geo = isset($_GET['geo']) ? trim($_GET['geo']) : '';

$where = "status='active' AND visibility='public'";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($category !== '') {
    $where .= " AND category = ?";
    $params[] = $category;
}

if ($geo !== '') {
    // geo_targeting can be "Global" or JSON array like '["US","UK"]' or comma-separated "US,UK"
    // We'll do a simple LIKE for the geo filter
    $where .= " AND (geo_targeting = 'Global' OR geo_targeting LIKE ?)";
    $params[] = "%{$geo}%";
}

$totalOffersRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE $where", $params);
$totalOffers = (int)($totalOffersRow['cnt'] ?? 0);
$totalPages = max(1, ceil($totalOffers / $limit));

$sql = "SELECT id, name, description, category, geo_targeting, payout_type, payout_amount, currency, thumbnail
        FROM offers
        WHERE $where
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset";
$offers = Database::fetchAll($sql, $params);

// Fetch categories for the filter dropdown
$categoriesResult = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE status='active' AND visibility='public' AND category != '' ORDER BY category ASC");
$categories = array_column($categoriesResult, 'category');

// Fetch unique GEOs for filter dropdown (simplistic extraction)
$geoResult = Database::fetchAll("SELECT geo_targeting FROM offers WHERE status='active' AND visibility='public' AND geo_targeting != '' AND geo_targeting != 'Global'");
$allGeos = [];
foreach ($geoResult as $r) {
    $gStr = $r['geo_targeting'];
    if (strpos($gStr, '[') === 0) {
        $arr = json_decode($gStr, true);
        if (is_array($arr)) {
            foreach ($arr as $a) $allGeos[$a] = 1;
        }
    } else {
        $arr = explode(',', $gStr);
        foreach ($arr as $a) {
            $a = trim($a);
            if ($a !== '') $allGeos[$a] = 1;
        }
    }
}
$availableGeos = array_keys($allGeos);
sort($availableGeos);

// Generate Schema Markup (ItemList)
$schemaItems = [];
$position = 1;
$siteUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

foreach ($offers as $o) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
    $slug = rtrim($slug, '-');
    $offerUrl = $siteUrl . '/offers/' . $o['id'] . '-' . $slug;
    
    $schemaItems[] = [
        "@type" => "ListItem",
        "position" => $position++,
        "item" => [
            "@type" => "Offer",
            "url" => $offerUrl,
            "name" => $o['name'],
            "priceCurrency" => $o['currency'] ?: 'USD',
            "price" => number_format($o['payout_amount'], 2, '.', '')
        ]
    ];
}

if (!empty($schemaItems)) {
    $seoSchemaArray = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "itemListElement" => $schemaItems
    ];
    $seoSchema = json_encode($seoSchemaArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

require BASE_PATH . '/views/public/offers.php';
