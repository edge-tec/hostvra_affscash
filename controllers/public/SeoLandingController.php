<?php
/**
 * Public SEO Landing Controller
 * Handles highly targeted keyword landing pages (e.g., /best-cpa-offers)
 */

if (!defined('BASE_PATH')) exit;

$appName  = Helpers::e(Config::get('config','app.name') ?? 'Affscash');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'offers';

// We map $route passed from index.php
$route = $_GET['route'] ?? '';

// Mapping SEO routes to content
$seoLandingMap = [
    'best-cpa-offers' => [
        'h1' => 'Best CPA Offers & High Paying Affiliate Programs',
        'title' => 'Best CPA Offers | Top Affiliate Marketing Network',
        'desc' => 'Discover the best CPA offers and high paying affiliate programs in the industry. Join our CPA network to access exclusive performance marketing deals.',
        'text' => 'Finding the right CPA network is critical for your success in affiliate marketing. Our platform connects you with the best CPA offers across all major verticals, including Dating, Sweepstakes, Finance, Health, and Digital Marketing. We pride ourselves on offering some of the highest payouts, dedicated account managers, and reliable tracking software to ensure your media buying and email marketing campaigns are highly profitable.',
        'faqs' => [
            "What makes a good CPA offer?" => "A good CPA offer combines high conversion rates with high payouts. We test all our campaigns internally to ensure our affiliates only promote the best CPA offers available.",
            "How do I start promoting CPA offers?" => "Simply sign up for our affiliate network, browse the Offers Marketplace, and grab your tracking link. You can drive traffic via SEO, social media, paid traffic, or email marketing."
        ],
        // SQL modifier to fetch top offers
        'sql_modifier' => 'ORDER BY payout_amount DESC'
    ],
    'high-paying-affiliate-offers' => [
        'h1' => 'High Paying Affiliate Offers & Premium CPA Programs',
        'title' => 'High Paying Affiliate Programs & Top CPA Offers',
        'desc' => 'Maximize your passive income with high paying affiliate offers. Our CPA network provides top payouts, fast payments, and exclusive lead generation deals.',
        'text' => 'If you are looking to maximize your ROI, you need access to high paying affiliate offers. Our network specializes in securing premium payouts for our affiliates by working directly with advertisers. From lucrative finance CPA offers to high EPC SaaS affiliate programs, we have the campaigns you need to generate serious passive income.',
        'faqs' => [
            "Which affiliate programs pay the highest?" => "Finance, Health (Nutra), and Digital Marketing typically have the highest absolute payouts, though Sweepstakes and Dating can have much higher conversion rates.",
            "Are high paying offers harder to promote?" => "Sometimes high paying offers require more targeted traffic, but the increased commission often makes up for a slightly lower conversion rate."
        ],
        'sql_modifier' => 'ORDER BY payout_amount DESC'
    ],
    'best-dating-cpa-offers' => [
        'h1' => 'Best Dating CPA Offers & Affiliate Programs',
        'title' => 'Top Dating CPA Offers | High Converting Hookup Offers',
        'desc' => 'Promote the best dating affiliate programs and mature dating offers. Gain access to premium relationship and dating CPA offers with highest EPCs.',
        'text' => 'The dating vertical is one of the most evergreen niches in affiliate marketing. We provide a vast selection of the best dating CPA offers, including mainstream dating app affiliate programs, hookup offers, and senior dating offers. Whether you are buying traffic or using social media, our dating offers convert.',
        'faqs' => [
            "What is a dating CPA offer?" => "A dating CPA offer pays you a commission when a user you refer signs up for a free dating site or purchases a premium membership.",
            "Why are dating offers so popular?" => "Dating offers generally have a very broad appeal, meaning you can market them to a wide demographic with excellent conversion rates."
        ],
        'sql_modifier' => "WHERE category LIKE '%Dating%'"
    ],
    'best-finance-cpa-offers' => [
        'h1' => 'Best Finance CPA Offers & Lead Generation',
        'title' => 'Top Finance Affiliate Offers | Personal Loan & Credit Card CPA',
        'desc' => 'Discover high-paying finance CPA offers. Promote personal loan offers, credit card leads, and investment affiliate programs for massive payouts.',
        'text' => 'Finance is known for having some of the highest payouts in the performance marketing industry. Our network features the best finance CPA offers covering personal loan offers, credit card offers, insurance leads, and debt relief programs. Perfect for affiliates skilled in financial lead generation.',
        'faqs' => [
            "How do finance CPA offers work?" => "Advertisers pay top dollar for qualified financial leads. You earn a commission when a user submits their information for a loan, credit card, or insurance quote.",
            "Are finance offers difficult to convert?" => "They require more targeted and high-quality traffic compared to sweepstakes, but the payouts are substantially higher."
        ],
        'sql_modifier' => "WHERE category LIKE '%Finance%'"
    ],
    'best-sweepstakes-offers' => [
        'h1' => 'Best Sweepstakes Offers & CPA Giveaways',
        'title' => 'Top Sweepstakes CPA Offers | Win Prizes & Gift Cards',
        'desc' => 'Promote high-converting sweepstakes offers, iPhone giveaways, and cash prize CPA offers. Best for paid traffic and email marketing.',
        'text' => 'Sweepstakes offers are perfect for affiliates looking for quick, high-volume conversions. We host the best sweepstakes CPA offers, including free giveaway offers, iPhone giveaways, and cash prize surveys. These campaigns convert easily since users just need to enter their email or zip code to participate.',
        'faqs' => [
            "What is a Sweepstakes CPA offer?" => "Users enter to win prizes or gift cards, and you get paid when they submit their basic information (like an email address or ZIP code).",
            "What traffic works best for Sweepstakes?" => "Email marketing, push notifications, and pop traffic are highly effective for sweepstakes offers."
        ],
        'sql_modifier' => "WHERE category LIKE '%Sweepstakes%'"
    ],
    'best-gift-card-offers' => [
        'h1' => 'Best Gift Card Offers & Survey CPA Programs',
        'title' => 'Top Gift Card Offers | Amazon, Walmart & Apple Gift Cards',
        'desc' => 'Market the best gift card offers and survey rewards. Earn CPA commissions by promoting Amazon gift card offers, Google Play, and Target giveaways.',
        'text' => 'Everyone loves free rewards. Our network provides access to the best gift card offers, including Amazon gift card offers, Walmart, Target, Apple, and Google Play gift cards. These offers typically involve reward points or survey sweepstakes and are highly appealing to a broad audience looking to make money online.',
        'faqs' => [
            "Are gift card offers easy to promote?" => "Yes, because the incentive of a free gift card is very strong. They typically require a simple email submit to convert.",
            "Which gift card brands perform best?" => "Amazon, Walmart, and Apple gift card offers consistently show the highest conversion rates."
        ],
        'sql_modifier' => "WHERE category LIKE '%Gift%Card%' OR name LIKE '%Gift%Card%'"
    ],
    'best-health-affiliate-offers' => [
        'h1' => 'Best Health Affiliate Offers & Nutra CPA',
        'title' => 'Top Health CPA Offers | Weight Loss & Supplement Affiliates',
        'desc' => 'Join the best health affiliate programs. Promote weight loss offers, skincare, vitamins, and wellness CPA offers with high conversion rates.',
        'text' => 'The Health and Wellness (Nutra) vertical is highly lucrative. Promote the best health CPA offers ranging from weight loss supplements and skincare offers to vitamins and general wellness products. These health lead generation campaigns are optimized for high EPCs and long-term profitability.',
        'faqs' => [
            "What does Nutra mean in CPA marketing?" => "Nutra is short for nutraceuticals, which refers to health and wellness products like dietary supplements, weight loss pills, and skincare creams.",
            "Why promote health offers?" => "Health offers solve a pressing consumer need, leading to strong emotional buying decisions and high commission payouts."
        ],
        'sql_modifier' => "WHERE category LIKE '%Health%' OR category LIKE '%Nutra%'"
    ]
];

// Fallback if not mapped
if (!isset($seoLandingMap[$route])) {
    http_response_code(404);
    require BASE_PATH . '/views/public/404.php';
    exit;
}

$meta = $seoLandingMap[$route];
$siteUrl = rtrim(Config::get('config', 'app.url') ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
$currentUrl = $siteUrl . '/' . $route;

$seoTitle = $meta['title'];
$seoDescription = $meta['desc'];

// Fetch the actual offers
$sqlMod = $meta['sql_modifier'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, name, description, category, geo_targeting, payout_type, payout_amount, currency, thumbnail
        FROM offers
        WHERE status='active' AND visibility='public'
        $sqlMod LIMIT $limit OFFSET $offset";
$offers = Database::fetchAll($sql);

$totalRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE status='active' AND visibility='public'");
$totalPages = max(1, ceil($totalRow['cnt'] / $limit));

// Build Schemas (ItemList, Breadcrumb, FAQ)
$schemaGraph = [];

$schemaGraph[] = [
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
            "name" => $meta['h1'],
            "item" => $currentUrl
        ]
    ]
];

$schemaItems = [];
$pos = 1;
foreach ($offers as $o) {
    $oslug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
    $oslug = rtrim($oslug, '-');
    $schemaItems[] = [
        "@type" => "ListItem",
        "position" => $pos++,
        "item" => [
            "@type" => "Offer",
            "url" => $siteUrl . '/offers/' . $o['id'] . '-' . $oslug,
            "name" => $o['name'],
            "priceCurrency" => $o['currency'] ?: 'USD',
            "price" => number_format($o['payout_amount'], 2, '.', '')
        ]
    ];
}
if (!empty($schemaItems)) {
    $schemaGraph[] = [
        "@type" => "ItemList",
        "itemListElement" => $schemaItems
    ];
}

$faqItems = [];
foreach ($meta['faqs'] as $q => $a) {
    $faqItems[] = [
        "@type" => "Question",
        "name" => $q,
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => $a
        ]
    ];
}
if (!empty($faqItems)) {
    $schemaGraph[] = [
        "@type" => "FAQPage",
        "mainEntity" => $faqItems
    ];
}

$seoSchema = json_encode([
    "@context" => "https://schema.org",
    "@graph" => $schemaGraph
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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

// We can reuse the category view since the layout is identical
require BASE_PATH . '/views/public/category.php';
