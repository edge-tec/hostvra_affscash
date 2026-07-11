<?php
/**
 * Public Offer Detail Controller
 * Handles the display of a single public offer.
 */

if (!defined('BASE_PATH')) exit;

$appName  = Helpers::e(Config::get('config','app.name') ?? 'Affscash');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'offers';

$slug = $_GET['slug'] ?? '';
if (!$slug || !preg_match('/^(\d+)-/', $slug, $matches)) {
    http_response_code(404);
    require BASE_PATH . '/views/public/404.php';
    exit;
}

$offerId = (int)$matches[1];

$offer = Database::fetchOne("SELECT * FROM offers WHERE id = ? AND status = 'active' AND visibility = 'public'", [$offerId]);

if (!$offer) {
    http_response_code(404);
    require BASE_PATH . '/views/public/404.php';
    exit;
}

$siteUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');
$offerUrl = $siteUrl . '/offers/' . $slug;

// SEO Variables
$seoTitle = $offer['name'] . ' - CPA Offer | ' . Config::get('config', 'app.name');
$seoDescription = mb_substr(strip_tags($offer['description']), 0, 155) . '...';
$seoCanonical = $offerUrl;

// Open Graph Tags
$ogImage = $offer['thumbnail'] ? $offer['thumbnail'] : '/logoo.png';
if (strpos($ogImage, 'http') !== 0) {
    $ogImage = $siteUrl . $ogImage;
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
                    "name" => "Offers",
                    "item" => $siteUrl . '/offers'
                ],
                [
                    "@type" => "ListItem",
                    "position" => 3,
                    "name" => $offer['name'],
                    "item" => $offerUrl
                ]
            ]
        ],
        [
            "@type" => "Product",
            "name" => $offer['name'],
            "image" => $ogImage,
            "description" => strip_tags($offer['description']),
            "offers" => [
                "@type" => "Offer",
                "url" => $offerUrl,
                "priceCurrency" => $offer['currency'] ?: 'USD',
                "price" => number_format($offer['payout_amount'], 2, '.', ''),
                "itemCondition" => "https://schema.org/NewCondition",
                "availability" => "https://schema.org/InStock",
                "seller" => [
                    "@type" => "Organization",
                    "name" => Config::get('config', 'app.name')
                ]
            ]
        ]
    ]
];
$seoSchema = json_encode($seoSchemaArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

ob_start();
?>
<meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES) ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES) ?>">
<meta property="og:url" content="<?= htmlspecialchars($offerUrl, ENT_QUOTES) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES) ?>">
<meta property="og:type" content="product">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES) ?>">
<?php
$seoCustomHead = ob_get_clean();

require BASE_PATH . '/views/public/offer_detail.php';
