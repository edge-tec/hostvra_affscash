<?php
/**
 * Public Category Controller
 * Handles SEO-optimized category pages (e.g., /category/dating-offers)
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

// Mapping SEO slugs to DB categories
$categoryMap = [
    'dating-offers' => 'Dating',
    'finance-offers' => 'Finance',
    'health-offers' => 'Health',
    'sweepstakes-offers' => 'Sweepstakes',
    'gift-card-offers' => 'Gift Card',
    'digital-marketing' => 'Digital Marketing',
];

if (!isset($categoryMap[$slug])) {
    http_response_code(404);
    require BASE_PATH . '/views/public/404.php';
    exit;
}

$dbCategory = $categoryMap[$slug];
$siteUrl = rtrim(Config::get('config', 'app.url') ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
$currentUrl = $siteUrl . '/category/' . $slug;

// Define specific SEO metadata per category
$seoData = [
    'dating-offers' => [
        'h1' => 'Best High Paying Dating CPA Offers & Affiliate Programs',
        'title' => 'Top Dating CPA Offers | High Paying Dating Affiliate Programs',
        'desc' => 'Promote the highest paying dating CPA offers, hookup offers, and online dating affiliate programs. Earn passive income with top tier dating networks.',
        'text' => 'The dating vertical is one of the most profitable niches in performance marketing. Whether you have traffic for mature dating offers, casual hookup offers, or senior dating sites, our CPA network provides exclusive high-converting campaigns. We partner directly with advertisers to bring you the best dating affiliate programs with guaranteed high payouts and global coverage.',
        'faqs' => [
            "What are dating CPA offers?" => "Dating CPA offers are performance marketing campaigns where affiliates earn a commission when a user completes an action, such as signing up for an online dating site or app.",
            "Why promote dating affiliate programs?" => "The online dating industry is massive. With high conversion rates and high payouts, dating offers are perfect for media buyers, email marketers, and social media traffic."
        ]
    ],
    'finance-offers' => [
        'h1' => 'Top Finance CPA Offers & Affiliate Programs',
        'title' => 'Best Finance Affiliate Offers | Personal Loans & Credit Card CPA',
        'desc' => 'Discover the best finance affiliate offers including personal loans, credit cards, insurance leads, and debt relief. Maximize your earnings with high EPC finance offers.',
        'text' => 'Finance affiliate offers consistently deliver some of the highest payouts in the CPA industry. Our network features top-tier financial lead generation campaigns, including personal loans, credit card offers, banking offers, and investment affiliate programs. Whether you generate traffic via SEO, media buying, or email marketing, these offers provide incredible EPCs.',
        'faqs' => [
            "What are the best finance CPA offers?" => "The best finance offers typically include personal loans, debt relief, insurance leads, and credit card signups.",
            "How do I promote finance affiliate programs?" => "Finance offers convert best with highly targeted SEO traffic, specialized email lists, and targeted media buying on search engines."
        ]
    ],
    'health-offers' => [
        'h1' => 'High Converting Health & Wellness CPA Offers',
        'title' => 'Health Affiliate Offers | Weight Loss & Supplement CPA Programs',
        'desc' => 'Promote high paying health affiliate offers, weight loss programs, and supplement CPA offers. Start earning with top wellness and skincare lead generation.',
        'text' => 'Health and wellness is an evergreen vertical that generates massive revenue year-round. Our CPA network provides exclusive access to top-converting weight loss offers, skincare trials, vitamin supplements, and fitness affiliate programs. Capitalize on high consumer demand and earn substantial commissions with our premium health offers.',
        'faqs' => [
            "What types of health offers convert best?" => "Nutra, weight loss supplements, male enhancement, and skincare trial offers historically have very high conversion rates.",
            "Are health CPA offers suitable for social media traffic?" => "Yes, health and wellness offers perform exceptionally well on native ads, social media platforms, and influencer marketing campaigns."
        ]
    ],
    'sweepstakes-offers' => [
        'h1' => 'Best Sweepstakes CPA Offers & Free Giveaways',
        'title' => 'Sweepstakes Offers | CPA Giveaways & Win Prizes Affiliate Programs',
        'desc' => 'Earn massive commissions promoting sweepstakes CPA offers, gift card giveaways, and free prize offers. The best CPL sweepstakes network for high EPCs.',
        'text' => 'Sweepstakes offers are perfect for affiliates looking for high-volume conversions. These campaigns typically require users to submit their email or complete a short survey for a chance to win cash prizes, free gift cards, or an iPhone giveaway. Due to the low barrier to entry, sweepstakes CPA offers generate incredible conversion rates across almost all traffic sources.',
        'faqs' => [
            "What is a sweepstakes CPA offer?" => "A sweepstakes offer pays affiliates a commission when a user enters their information for a chance to win a prize or giveaway.",
            "Which GEOs work best for sweepstakes?" => "While Tier 1 countries (US, UK, CA, AU) offer the highest payouts, Tier 2 and Tier 3 GEOs often provide massive volume and excellent ROI."
        ]
    ],
    'gift-card-offers' => [
        'h1' => 'Top Gift Card Offers & Online Rewards Affiliate Programs',
        'title' => 'Free Gift Card Offers | Amazon, Walmart & Apple Giveaways',
        'desc' => 'Promote high converting gift card offers including Amazon, Walmart, Target, and Google Play. Best CPA programs for reward points and surveys.',
        'text' => 'Gift card giveaways and survey offers are some of the easiest campaigns to convert. Users are highly motivated by the promise of free Amazon gift cards, Walmart gift cards, or Apple and Google Play rewards. These CPL (Cost Per Lead) offers are ideal for social media traffic, email lists, and reward-based platforms.',
        'faqs' => [
            "How do gift card CPA offers work?" => "Users complete a short survey or submit their email to qualify for a free gift card, and the affiliate earns a set commission per lead.",
            "Are gift card offers allowed on social media?" => "Yes, they are highly popular on social media, but always ensure your promotional methods comply with the advertiser's guidelines."
        ]
    ],
    'digital-marketing' => [
        'h1' => 'High Paying Digital Marketing & SaaS Affiliate Programs',
        'title' => 'Digital Marketing Offers | SEO, SaaS & Software Affiliate Programs',
        'desc' => 'Earn recurring commissions with digital marketing offers, SEO services, hosting, and SaaS affiliate programs. The best network for B2B marketers.',
        'text' => 'B2B and software affiliate marketing offer some of the most lucrative and often recurring commissions in the industry. Promote top digital marketing tools, marketing automation software, email marketing platforms, and website builders. If you have an audience of entrepreneurs or businesses, our SaaS affiliate programs will help you maximize your passive income.',
        'faqs' => [
            "What are SaaS affiliate programs?" => "SaaS (Software as a Service) affiliate programs pay you a commission for referring new users to their software platforms.",
            "Do digital marketing offers pay recurring commissions?" => "Many SaaS and hosting affiliate programs offer recurring commissions, meaning you get paid every month the user remains a customer."
        ]
    ],
];

$meta = $seoData[$slug];
$seoTitle = $meta['title'];
$seoDescription = $meta['desc'];

// Fetch the actual offers for this category
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 24;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, name, description, category, geo_targeting, payout_type, payout_amount, currency, thumbnail
        FROM offers
        WHERE status='active' AND visibility='public' AND category = ?
        ORDER BY id DESC LIMIT $limit OFFSET $offset";
$offers = Database::fetchAll($sql, [$dbCategory]);

$totalRow = Database::fetchOne("SELECT COUNT(*) as cnt FROM offers WHERE status='active' AND visibility='public' AND category = ?", [$dbCategory]);
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
            "name" => "Categories",
            "item" => $siteUrl . '/offers'
        ],
        [
            "@type" => "ListItem",
            "position" => 3,
            "name" => $dbCategory . " Offers",
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

require BASE_PATH . '/views/public/category.php';
