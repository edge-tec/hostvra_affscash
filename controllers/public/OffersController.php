<?php
/**
 * Public Offers Controller
 * Handles the display, pagination, and filtering of public offers.
 */

if (!defined('BASE_PATH')) exit;

$appName  = Helpers::e(Config::get('config','app.name') ?? 'Affscash');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'offers';

$seoTitle = Config::get('config', 'app.name') . ' - Explore Top CPA Offers';
$seoDescription = 'Discover high-converting CPA, CPL, and CPI offers across all verticals. Start earning today with our top exclusive deals and fast payouts.';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 24; // Offers per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$geo = isset($_GET['geo']) ? trim($_GET['geo']) : '';

$where = "status='active'";
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
    $where .= " AND (geo_targeting = 'Global' OR geo_targeting LIKE ?)";
    $params[] = "%{$geo}%";
}

try {
    $totalOffersRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE $where", $params);
    $totalOffers = (int)($totalOffersRow['cnt'] ?? 0);
} catch (\Throwable $e) {
    $totalOffers = 0;
}
$totalPages = max(1, ceil($totalOffers / $limit));

$dbOffers = [];
try {
    $sql = "SELECT id, name, description, category, geo_targeting, payout_type, payout_amount, currency, thumbnail
            FROM offers
            WHERE $where
            ORDER BY id DESC
            LIMIT $limit OFFSET $offset";
    $dbOffers = Database::fetchAll($sql, $params) ?: [];
} catch (\Throwable $e) {}

// Standard network fallback offers to ensure all categories and offers are always represented
$fallbackOffers = [
    ['id' => 125, 'name' => 'PersonalLoans.com (US) (CPL)', 'category' => 'Financial', 'geo_targeting' => 'US', 'payout_type' => 'RevShare', 'payout_amount' => 80.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70', 'description' => 'Top performing personal loans CPL offer with high revshare.'],
    ['id' => 124, 'name' => 'Cash App Gift Card (US) (Trial)', 'category' => 'Financial', 'geo_targeting' => 'US', 'payout_type' => 'CPA', 'payout_amount' => 11.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70', 'description' => 'Fast converting Cash App trial offer for US traffic.'],
    ['id' => 123, 'name' => 'Walmart Car Emergency (US) (Trial)', 'category' => 'Financial', 'geo_targeting' => 'US', 'payout_type' => 'CPA', 'payout_amount' => 12.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=400&q=70', 'description' => 'High payout automotive trial offer.'],
    ['id' => 122, 'name' => 'YourInsurance Quotes (US)', 'category' => 'Financial', 'geo_targeting' => 'US', 'payout_type' => 'CPA', 'payout_amount' => 4.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&q=70', 'description' => 'Evergreen insurance quotes CPA offer.'],
    ['id' => 301, 'name' => 'SmartLink - CPS', 'category' => 'Dating', 'geo_targeting' => 'Global', 'payout_type' => 'RevShare', 'payout_amount' => 80.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70', 'description' => 'High-converting dating smartlink.'],
    ['id' => 302, 'name' => 'CPI ServerlessVPN', 'category' => 'Mobile Apps', 'geo_targeting' => 'Global', 'payout_type' => 'RevShare', 'payout_amount' => 70.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70', 'description' => 'Worldwide mobile VPN application install offer.'],
    ['id' => 303, 'name' => 'Tolet24', 'category' => 'Dating', 'geo_targeting' => 'Global', 'payout_type' => 'CPC', 'payout_amount' => 0.10, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70', 'description' => 'Global CPC dating traffic offer.'],
    ['id' => 304, 'name' => 'Friends-With-Benefits.com - (DOI) - Responsive [UK]', 'category' => 'Dating', 'geo_targeting' => 'GB', 'payout_type' => 'CPL', 'payout_amount' => 5.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70', 'description' => 'Premium DOI casual dating offer for UK.'],
    ['id' => 286, 'name' => 'CharmDate Hot SOI', 'category' => 'SOI Dating', 'geo_targeting' => 'US,GB,CA', 'payout_type' => 'CPA', 'payout_amount' => 9.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70', 'description' => 'Hot SOI dating lead generation.'],
    ['id' => 62,  'name' => 'Super Smartlink', 'category' => 'Smartlink', 'geo_targeting' => 'Global', 'payout_type' => 'RevShare', 'payout_amount' => 80.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70', 'description' => 'Auto-optimizing global smartlink.'],
    ['id' => 289, 'name' => 'uMobix.org Software', 'category' => 'Software', 'geo_targeting' => 'US,GB,AU', 'payout_type' => 'CPS', 'payout_amount' => 42.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70', 'description' => 'High ticket software monitoring offer.'],
    ['id' => 296, 'name' => 'Ckwin Casino', 'category' => 'Casino', 'geo_targeting' => 'Global', 'payout_type' => 'CPA', 'payout_amount' => 25.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70', 'description' => 'Global casino deposit offer.'],
    ['id' => 281, 'name' => 'VideoChat FTD', 'category' => 'Cam', 'geo_targeting' => 'US,GB,CA', 'payout_type' => 'FTD', 'payout_amount' => 15.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70', 'description' => 'First time deposit video chat offer.'],
    ['id' => 255, 'name' => 'Maturedates UK DOI', 'category' => 'DOI Dating', 'geo_targeting' => 'GB', 'payout_type' => 'CPA', 'payout_amount' => 5.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=400&q=70', 'description' => 'Targeted mature dating offer.'],
    ['id' => 305, 'name' => 'LoveArrow Membership CPS', 'category' => 'CPS', 'geo_targeting' => 'US,GB', 'payout_type' => 'CPS', 'payout_amount' => 35.00, 'currency' => 'USD', 'thumbnail' => 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=400&q=70', 'description' => 'High payout membership sale offer.']
];

// Merge DB offers first, then fallback offers (avoiding duplicates)
$seenIds = [];
$offers = [];
foreach ($dbOffers as $o) {
    $seenIds[$o['id']] = true;
    $offers[] = $o;
}
foreach ($fallbackOffers as $o) {
    if (isset($seenIds[$o['id']])) continue;
    // Apply search filter to fallback offers if set
    if ($search !== '' && stripos($o['name'], $search) === false && stripos($o['category'], $search) === false && stripos($o['description'], $search) === false) continue;
    // Apply category filter to fallback offers if set
    if ($category !== '' && strcasecmp($o['category'], $category) !== 0) continue;
    // Apply geo filter to fallback offers if set
    if ($geo !== '' && $o['geo_targeting'] !== 'Global' && stripos($o['geo_targeting'], $geo) === false) continue;
    
    $offers[] = $o;
}

if ($totalOffers === 0) {
    $totalOffers = count($offers);
    $totalPages = max(1, ceil($totalOffers / $limit));
}

// Default categories + ALL distinct categories added by Admin in the database
$defaultCategories = [
    'Dating',
    'Mobile Apps',
    'Financial',
    'Casino',
    'Software',
    'SOI Dating',
    'DOI Dating',
    'Cam',
    'CPS',
    'Health',
    'Sweepstakes',
    'Gift Cards',
    'Smartlink'
];

$dbCats = [];
try {
    $categoriesResult = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $dbCats = array_column($categoriesResult, 'category');
} catch (\Throwable $e) {}

$categories = array_values(array_unique(array_merge($defaultCategories, $dbCats)));
sort($categories);

// Fetch unique GEOs for filter dropdown
$allGeos = ['US' => 1, 'GB' => 1, 'CA' => 1, 'AU' => 1, 'DE' => 1, 'FR' => 1, 'IT' => 1, 'ES' => 1, 'CH' => 1, 'DK' => 1, 'FI' => 1];
try {
    $geoResult = Database::fetchAll("SELECT geo_targeting FROM offers WHERE geo_targeting != '' AND geo_targeting != 'Global'");
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
} catch (\Throwable $e) {}

$availableGeos = array_keys($allGeos);
sort($availableGeos);

// Generate Schema Markup (ItemList & Breadcrumb)
$schemaItems = [];
$position = 1;
$siteUrl = rtrim(Config::get('config', 'app.url') ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
$currentUrl = $siteUrl . '/offers';

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

$seoSchemaArray = [
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                [
                    "@type" => "ListItem",
                    "position" => 1,
                    "name" => "Home",
                    "item" => $siteUrl
                ],
                [
                    "@type" => "ListItem",
                    "position" => 2,
                    "name" => "Offers Marketplace",
                    "item" => $currentUrl
                ]
            ]
        ]
    ]
];

if (!empty($schemaItems)) {
    $seoSchemaArray['@graph'][] = [
        "@type" => "ItemList",
        "itemListElement" => $schemaItems
    ];
}

$seoSchema = json_encode($seoSchemaArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Custom meta tags for Open Graph and Twitter
ob_start();
?>
<meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES) ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES) ?>">
<meta property="og:url" content="<?= htmlspecialchars($currentUrl, ENT_QUOTES) ?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES) ?>">
<link rel="canonical" href="<?= htmlspecialchars($currentUrl, ENT_QUOTES) ?>" />
<?php
$seoCustomHead = ob_get_clean();

require BASE_PATH . '/views/public/offers.php';
