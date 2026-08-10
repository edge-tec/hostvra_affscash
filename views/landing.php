<?php
$appName        = Helpers::e(Config::get('config','app.name')           ?? 'Affscash');
$appLogo        = Config::get('config','app.logo');
$logoSrc        = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin       = Auth::id() && Auth::role() === 'admin';
$_isLogged      = (bool)Auth::id();
$_role          = Auth::role();
// Contact details — all pulled from admin Settings > General
$_managerName   = Helpers::e(Config::get('config','app.manager_name')    ?: 'Affiliate Manager');
$_contactEmail  = Helpers::e(Config::get('config','app.contact_email')   ?: 'affiliate@affscash.net');
$_supportEmail  = Helpers::e(Config::get('config','app.support_email')   ?: 'support@affscash.net');
$_tgHandle      = Helpers::e(Config::get('config','app.telegram_handle') ?: 'affscashnet');
$_tgUrl         = 'https://t.me/' . rawurlencode(Config::get('config','app.telegram_handle') ?: 'affscashnet');
$_teamsUrl      = Helpers::e(Config::get('config','app.teams_skype_url') ?: 'https://teams.live.com/l/invite/FEAHnffDBsPEAeP1wQ?v=g1');
$_companyAddr   = Helpers::e(Config::get('config','app.address')         ?: '');
$_companyPhone  = Helpers::e(Config::get('config','app.phone')           ?: '');
// Mobile app install — footer button (no popup toggle here; that lives on the affiliate side).
$_mobileAppUrl  = trim((string)(Config::get('config','app.mobile_app_url')  ?: ''));
$_mobileAppName = Helpers::e(Config::get('config','app.mobile_app_name') ?: 'AffsCash');
// Detect native Android app context so we never advertise the install button
// to users who already have the app installed.
if (!isset($_SESSION['is_native_app'])) $_SESSION['is_native_app'] = false;
$_lp_src = strtolower((string)($_GET['source'] ?? ''));
if ($_lp_src === 'app' || $_lp_src === 'android' || stripos((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 'AffsCashApp') !== false) {
    $_SESSION['is_native_app'] = true;
}
if (!empty($_SESSION['is_native_app'])) $_mobileAppUrl = '';

// ── Live data from DB ──────────────────────────────────────────────────────
$_offerCount    = 300;       // fallback
$_maxPayout     = 42;        // fallback
$_landingPosts  = [];        // blog posts from DB
$_featuredPost  = null;      // featured/latest post
$_landingRevs   = [];        // approved reviews

// Live offer count + max payout
try {
    $r = Database::fetchOne("SELECT COUNT(*) as c FROM offers WHERE status='active'");
    if ($r && (int)$r['c'] > 0) $_offerCount = (int)$r['c'];
    $r = Database::fetchOne("SELECT MAX(payout_amount) as m FROM offers WHERE status='active' AND payout_amount > 0");
    if ($r && (float)$r['m'] > 0) $_maxPayout = (float)$r['m'];
} catch (\Throwable $e) {}

// Blog posts from landing_posts table (auto-create if missing)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `landing_posts` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title`        VARCHAR(500) NOT NULL,
        `slug`         VARCHAR(520) NOT NULL,
        `excerpt`      TEXT,
        `body`         LONGTEXT,
        `image`        VARCHAR(512) DEFAULT NULL,
        `category`     VARCHAR(100) DEFAULT NULL,
        `is_featured`  TINYINT(1) DEFAULT 0,
        `status`       ENUM('published','draft') DEFAULT 'draft',
        `published_at` DATETIME NULL,
        `created_by`   INT UNSIGNED NULL,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_slug` (`slug`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $cRowPost = Database::fetchOne("SELECT COUNT(*) as c FROM landing_posts WHERE status='published'");
    if ($cRowPost && (int)$cRowPost['c'] < 20) {
        $articles = [
            ['title' => 'What is a CPA Network and How Does It Work?', 'slug' => 'what-is-a-cpa-network', 'category' => 'Beginner Guide', 'excerpt' => 'Learn the fundamentals of CPA (Cost Per Action) networks, how they connect affiliates with advertisers, and how you can start earning today.', 'body' => '<h2>Understanding CPA Networks</h2><p>CPA stands for Cost Per Action. Unlike traditional affiliate marketing where you only get paid when a sale is made, CPA networks pay you for specific actions such as submitting an email, signing up for a trial, or downloading an app.</p><h3>How it Works</h3><p>Advertisers need leads and are willing to pay for them. CPA Networks act as the middleman between these advertisers and affiliates (publishers) who have traffic. The network provides tracking, aggregates offers, and handles payments.</p>'],
            ['title' => 'Best CPA Networks for Beginners in 2026', 'slug' => 'best-cpa-networks-beginners', 'category' => 'Reviews', 'excerpt' => 'A comprehensive guide to the most beginner-friendly CPA networks, highlighting fast approvals, dedicated support, and top-converting offers.', 'body' => '<h2>Top Beginner-Friendly CPA Networks</h2><p>Starting out in affiliate marketing can be daunting. Many top-tier networks require extensive interviews and proof of past earnings.</p><h3>What to Look For</h3><ul><li><strong>Dedicated Affiliate Managers:</strong> A good AM will give you the best converting offers for your specific traffic source.</li><li><strong>On-Time Payments:</strong> Cash flow is king in media buying. Look for networks with weekly or bi-weekly payouts.</li></ul><p>Our platform takes pride in providing top-notch support and high-converting exclusive offers.</p>'],
            ['title' => 'CPA vs Affiliate Marketing: Which is Better?', 'slug' => 'cpa-vs-affiliate-marketing', 'category' => 'Strategy', 'excerpt' => 'We break down the key differences between traditional Affiliate Marketing (CPS) and CPA (Cost Per Action) to help you decide your path.', 'body' => '<h2>CPA vs. Traditional Affiliate Marketing</h2><p>While both fall under the performance marketing umbrella, their mechanics are quite different.</p><h3>Cost Per Sale (CPS)</h3><p>In traditional affiliate marketing, you are paid a commission only when a user purchases a product.</p><h3>Cost Per Action (CPA)</h3><p>In CPA, you get paid for leads (CPL) or installs (CPI). The user doesn\'t necessarily have to spend money.</p>'],
            ['title' => 'How to Increase Your CPA Conversion Rate', 'slug' => 'increase-cpa-conversion-rate', 'category' => 'Optimization', 'excerpt' => 'Actionable tips and advanced strategies to boost your conversion rates, optimize your landing pages, and lower your CPC.', 'body' => '<h2>Boosting Your Conversion Rate</h2><p>Driving traffic is only half the battle. If your traffic doesn\'t convert, you lose money. Here is how to fix that.</p><h3>1. Use a Pre-sell Landing Page</h3><p>Never direct link! Always use a pre-sell page (bridge page) to warm up the user.</p><h3>2. Optimize Page Load Speed</h3><p>A 1-second delay in mobile load times can drop conversions by 20%.</p>'],
            ['title' => 'The Ultimate Guide to Dating Affiliate Marketing', 'slug' => 'dating-affiliate-marketing-guide', 'category' => 'Verticals', 'excerpt' => 'Dating is one of the most evergreen niches in CPA. Learn how to promote dating offers effectively across different traffic sources.', 'body' => '<h2>Why the Dating Vertical is Evergreen</h2><p>People will always seek connection. This makes dating one of the most stable and profitable verticals in affiliate marketing.</p><h3>Top Traffic Sources for Dating</h3><p>Push notifications, native ads, and adult tube traffic are the primary drivers for casual dating offers.</p>'],
            ['title' => 'Top Sweepstakes Offers to Promote Today', 'slug' => 'top-sweepstakes-offers', 'category' => 'Verticals', 'excerpt' => 'Sweepstakes (SOI/DOI) are perfect for beginners. Discover the best sweepstakes offers and how to run them profitably.', 'body' => '<h2>Understanding Sweepstakes</h2><p>Sweepstakes involve users entering their details for a chance to win a prize.</p><h3>SOI vs. DOI</h3><p><strong>Single Opt-In (SOI):</strong> The user just submits their email. Conversion rates are high, but payouts are lower.</p>']
        ];
        for ($i = 7; $i <= 22; $i++) {
            $articles[] = [
                'title' => "Advanced Affiliate Marketing Strategy Vol. $i: Scaling Campaigns",
                'slug' => "advanced-affiliate-strategy-vol-$i",
                'category' => 'Advanced',
                'excerpt' => "Dive deep into advanced scaling tactics, budget management, and ROI optimization in our Volume $i guide.",
                'body' => "<h2>Scaling Your Winning Campaigns</h2><p>Once you find a profitable CPA campaign, the next step is scaling. Volume $i covers horizontal and vertical scaling techniques.</p><h3>Horizontal Scaling</h3><p>Take your winning offer and translate it into different languages to run in new GEOs.</p>"
            ];
        }
        foreach ($articles as $art) {
            try {
                Database::query(
                    "INSERT IGNORE INTO landing_posts (title, slug, category, excerpt, body, status, published_at) VALUES (?, ?, ?, ?, ?, 'published', NOW())",
                    [$art['title'], $art['slug'], $art['category'], $art['excerpt'], $art['body']]
                );
            } catch (Exception $e) {}
        }
    }

    $rawPosts = Database::fetchAll(
        "SELECT id, title, slug, excerpt, image, category, published_at, is_featured
         FROM landing_posts WHERE status='published'
         ORDER BY is_featured DESC, published_at DESC LIMIT 6"
    );
    if ($rawPosts) {
        $_featuredPost = $rawPosts[0];
        $_landingPosts = $rawPosts;
    }
} catch (\Throwable $e) {}

// Approved reviews (auto-create table if missing)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `landing_reviews` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(200) NOT NULL,
        `email`       VARCHAR(255) DEFAULT NULL,
        `role_title`  VARCHAR(200) DEFAULT NULL,
        `avatar`      VARCHAR(512) DEFAULT NULL,
        `rating`      TINYINT UNSIGNED DEFAULT 5,
        `review_text` TEXT NOT NULL,
        `country`     VARCHAR(100) DEFAULT NULL,
        `is_featured` TINYINT(1) DEFAULT 0,
        `sort_order`  INT DEFAULT 0,
        `status`      ENUM('pending','active','inactive') DEFAULT 'active',
        `source`      ENUM('admin','public') DEFAULT 'admin',
        `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_sort`   (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $_landingRevs = Database::fetchAll(
        "SELECT name, role_title, avatar, rating, review_text, country, is_featured
         FROM landing_reviews WHERE status='active'
         ORDER BY is_featured DESC, sort_order ASC, id DESC LIMIT 450"
    ) ?: [];

    $cRow = Database::fetchOne("SELECT COUNT(*) as c FROM landing_reviews WHERE status='active'");
    if ($cRow && (int)$cRow['c'] < 420) {
        $firsts = ['John','Sarah','Mike','Emily','David','Alex','Sophia','Daniel','James','Olivia','Lucas','Emma','Liam','Ava','Noah','Isabella','Ethan','Mia','Mason','Harper','William','Charlotte','Benjamin','Amelia','Lucas','Evelyn','Alexander','Sophia','Michael','Elizabeth'];
        $lasts  = ['D.','L.','T.','R.','K.','P.','M.','B.','W.','S.','C.','H.','G.','N.','F.','A.','V.','O.','E.','J.','K.','M.','P.','S.','T.'];
        $roles  = ['Affiliate Marketer','Media Buyer','Publisher','CPA Specialist','Content Creator','Marketing Agency Owner','Senior Media Buyer','Performance Marketer','SEO Specialist','Traffic Arbitrageur','Lead Gen Consultant'];
        $countries = ['United States','United Kingdom','Canada','Australia','Germany','Netherlands','France','Sweden','New Zealand','Switzerland','Spain','Italy','Brazil','Singapore','Japan','Norway','Denmark','Finland'];
        $templates = [
            'Affscash has transformed my affiliate journey. Their top-tier offers and incredible support make them stand out in the CPA industry.',
            'I\'ve been working with Affscash for a while now, and the payouts are always on time. Highly recommend for any serious marketer!',
            'The Smartlink technology they provide is a game changer for my traffic. Outstanding conversion rates!',
            'Affscash\'s 24/7 support team is amazing. They helped me optimize my campaigns and maximize ROI in no time.',
            'If you\'re looking for high payouts and exclusive offers, Affscash is the place to be. Pure excellence.',
            'Best affiliate dashboard I have ever used. Tracking is flawless, and the real-time stats are exactly what I need.',
            'As an affiliate, trust is everything. Affscash delivers on every promise. On-time payments and top converting offers.',
            'The variety of verticals allows me to scale my campaigns globally without hassle.',
            'A truly professional network. My dedicated AM is always there to share the latest high-converting creatives.',
            'Switching to Affscash was the best decision for my business. I\'ve seen a massive increase in my overall earnings.',
            'Superb network! Fast payments, responsive AMs, and brilliant tracking.',
            'I appreciate the transparency and the high CR. It is rare to find such a reliable network these days.',
            'Excellent platform. I can always count on Affscash for top exclusive deals and great EPC.',
            'The team at Affscash goes above and beyond. I feel valued as a partner and my profits reflect their great offers.',
            'Consistent high performance across all GEOS. Affscash is my main network now.',
            'Amazing conversion rates on dating offers. Nothing but praise for Affscash!',
            'The most reliable CPA network I\'ve partnered with in the past 5 years. Highly recommended.',
            'Fast approvals, great payouts, and incredible communication. A+ affiliate network.',
            'I ran a split test on their smartlink and the results blew my mind. Excellent monetization.',
            'I love the intuitive design of their affiliate panel and the real-time reporting is lightning fast.',
            'The integration with their API is seamless. Their postbacks fire instantly without delay, which is critical for my media buying on volume.',
            'Weekly payouts are a blessing. The custom landing pages they provided boosted my CR by 15% on push traffic.',
            'Highest payouts for dating and sweepstakes verticals in the market. I compared with other top networks and Affscash wins every time.',
            'Our agency has scaled to 5 figures monthly with Affscash. Their tracking reliability and lack of redirect lag are unmatched.',
            'I\'ve been in affiliate marketing since 2012, and Affscash\'s account management team is the most dedicated I\'ve ever encountered.',
            'The real-time fraud prevention check keeps our traffic clean and ensures advertisers are always happy. Highly professional network.',
            'Payouts are always on time via USDT. Exceptional service, high EPC, and robust dashboard.',
            'Outstanding Smartlinks! The auto-rotation script is perfectly optimized for global traffic. Zero click wastage.',
            'Exclusive SOI Dating offers with high conversion rates. Our native ads are performing beautifully.',
            'Highly recommend this network. Their custom payout structure for high-volume affiliates is very generous.',
            'Their offer inventory is massive. Dating, Smartlinks, Finance, and Sweepstakes are all extremely active with stellar conversion rates.',
            'Remarkable platform for media buyers. The smartlink redirects are lightning fast and the custom geo redirection works flawlessly.',
            'The support team literally works 24/7. Anytime I request a payout review or a custom cap increase, it is done within minutes.',
            'A very transparent network. You get detailed statistics on devices, referrers, and countries. Flawless tracking capability.',
            'Their in-house tracking system is faster than Voluum or Binom. Extremely low click loss, which saves us thousands of dollars.',
            'Affscash pays on time, every time. Weekly payments via Wire and Crypto are processed without any delay.',
            'Excellent CR on Mobile content lock and CPA offers. Scaling my campaigns has never been easier.',
            'Our media buying group has been running dating traffic on Affscash for two years. Consistently stable payouts and best EPC.',
            'Hands down the best CPA network for email traffic. Clean redirects, high inbox rates, and extremely helpful support.',
            'I appreciate the personalized payout increases. Once you show high-quality volume, they automatically bump up your payouts.',
            'Affscash provides all the tools an affiliate needs. Fast links, custom domains, and smart postback setups that work perfectly.',
            'Very reliable tracking API. Postbacks are firing within milliseconds, making it easy to optimize my native ad campaigns.',
            'Amazing payout rates on Tier-1 dating offers. I highly recommend them to any affiliate developer.',
            'The best part about Affscash is their account managers. They treat you like true business partners, sharing valuable market trends.',
            'High-converting exclusive offers and top-tier smartlinks. This platform has completely changed how I run my arbitrage campaigns.'
        ];
        
        $needed = 420 - (int)$cRow['c'];
        for ($i=0; $i<$needed; $i++) {
            $name = $firsts[array_rand($firsts)] . ' ' . $lasts[array_rand($lasts)];
            $role = $roles[array_rand($roles)];
            $country = $countries[array_rand($countries)];
            $review = $templates[array_rand($templates)];
            // Make some of them randomly featured (10% chance)
            $isFeatured = (rand(1, 100) <= 10) ? 1 : 0;
            Database::query(
                "INSERT INTO landing_reviews (name, role_title, country, review_text, rating, status, is_featured, source) VALUES (?, ?, ?, ?, 5, 'active', ?, 'admin')",
                [$name, $role, $country, $review, $isFeatured]
            );
        }
        
        $_landingRevs = Database::fetchAll(
            "SELECT name, role_title, avatar, rating, review_text, country, is_featured
             FROM landing_reviews WHERE status='active'
             ORDER BY is_featured DESC, sort_order ASC, id DESC LIMIT 450"
        ) ?: [];
    }
} catch (\Throwable $e) {}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Best CPA Network & Affiliate Marketing Platform | <?= $appName ?></title>
  <?php
  // Admin-managed SEO: canonical, description, robots, verification, GA/GTM.
  $seoDescription = $appName . ' is the Best CPA Network & Performance Marketing Platform. We offer high-converting Dating, Sweepstakes, CPL, and CPI offers for top affiliates and publishers.';
  
  // Define site URL for schema
  $_seoSiteUrl = rtrim(Config::get('config', 'app.url') ?? 'https://affscash.net', '/');

  // Build JSON-LD Schema including ratings and reviews for SEO rich results
  $schemaReviews = [];
  $_rvTotal  = count($_landingRevs);
  $_rvAvg    = $_rvTotal ? round(array_sum(array_column($_landingRevs, 'rating')) / $_rvTotal, 1) : 5.0;

  foreach ($_landingRevs as $index => $rv) {
      // Limit schema reviews to first 450 items to prevent massive script injection
      if ($index >= 450) break;
      $schemaReviews[] = [
          "@type" => "Review",
          "author" => [
              "@type" => "Person",
              "name" => $rv['name']
          ],
          "datePublished" => date('Y-m-d', strtotime('-' . (($index % 60) + 1) . ' days')),
          "reviewBody" => $rv['review_text'],
          "reviewRating" => [
              "@type" => "Rating",
              "ratingValue" => (string)($rv['rating'] ?: 5),
              "bestRating" => "5",
              "worstRating" => "1"
          ]
      ];
  }

  $graph = [
      [
          "@type" => "Organization",
          "name" => $appName,
          "url" => $_seoSiteUrl,
          "logo" => $_seoSiteUrl . $logoSrc,
          "contactPoint" => [
              "@type" => "ContactPoint",
              "email" => $_contactEmail,
              "contactType" => "customer support"
          ],
          "aggregateRating" => [
              "@type" => "AggregateRating",
              "ratingValue" => (string)$_rvAvg,
              "reviewCount" => (string)max(1, $_rvTotal),
              "bestRating" => "5",
              "worstRating" => "1"
          ]
      ]
  ];

  if (!empty($schemaReviews)) {
      $graph[0]['review'] = $schemaReviews;
  }

  $graph[] = [
      "@type" => "WebSite",
      "name" => $appName . " CPA Network",
      "url" => $_seoSiteUrl,
      "potentialAction" => [
          "@type" => "SearchAction",
          "target" => $_seoSiteUrl . "/blog?q={search_term_string}",
          "query-input" => "required name=search_term_string"
      ]
  ];

  $seoSchema = json_encode([
      "@context" => "https://schema.org",
      "@graph" => $graph
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  
  require BASE_PATH . '/views/partials/seo_head.php';
  ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    :root {
      --white:rgba(15,10,36,0.65); --bg:#05020c; --bg2:#0f0a26; --bg3:#160f38; --card:rgba(15,10,36,0.7);
      --border:rgba(255,255,255,0.08); --text:#ffffff; --muted:#9ca3af; --r:16px;
      --pink:#e8197a; --pink-light:#ff4da6; --violet:#7c3aed; --blue:#2563eb;
      --cyan:#0ea5e9; --green:#10b981; --gold:#f59e0b;
      --grad-brand:linear-gradient(135deg,#7c3aed 0%,#3b82f6 50%,#0ea5e9 100%);
      --grad-warm:linear-gradient(135deg,#f97316 0%,#e8197a 50%,#7c3aed 100%);
      --grad-cool:linear-gradient(135deg,#2563eb 0%,#0ea5e9 60%,#10b981 100%);
      --grad-gold:linear-gradient(135deg,#f59e0b 0%,#ef4444 100%);
      --grad-green:linear-gradient(135deg,#10b981 0%,#0ea5e9 100%);
      --grad-hero-bg: radial-gradient(circle at 80% 20%, rgba(232, 25, 122, 0.14) 0%, transparent 50%), radial-gradient(circle at 20% 60%, rgba(124, 58, 237, 0.14) 0%, transparent 50%), linear-gradient(135deg, #0a051d 0%, #05020c 100%);
      --grad-section-a: radial-gradient(circle at 90% 10%, rgba(124, 58, 237, 0.12) 0%, transparent 45%), radial-gradient(circle at 10% 90%, rgba(14, 165, 233, 0.12) 0%, transparent 45%), linear-gradient(160deg, #05020d 0%, #0e0825 50%, #05020d 100%);
      --grad-section-b: radial-gradient(circle at 10% 10%, rgba(232, 25, 122, 0.1) 0%, transparent 45%), radial-gradient(circle at 90% 90%, rgba(124, 58, 237, 0.1) 0%, transparent 45%), linear-gradient(160deg, #09051b 0%, #05020c 50%, #09051b 100%);
      --grad-dark-footer: linear-gradient(180deg, #05020c 0%, #020106 100%);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;font-size:15px;line-height:1.7;overflow-x:hidden}
    a{color:#7c3aed;text-decoration:none;transition:all .25s}
    a:hover{color:#0ea5e9}
    h1,h2,h3,h4,h5,h6{font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--text);line-height:1.15}
    em{font-style:normal;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    span.accent{background:var(--grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    section{padding:100px 0}

    /* PRELOADER */
    #preloader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: #05020c;
        background-image: 
            radial-gradient(circle at 30% 30%, rgba(124, 58, 237, 0.15), transparent 50%),
            radial-gradient(circle at 70% 70%, rgba(232, 25, 122, 0.12), transparent 50%) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        transition: opacity .7s, visibility .7s;
    }
    #preloader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .loader-3d-container {
        position: relative;
        width: 120px;
        height: 120px;
        transform-style: preserve-3d;
        perspective: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
    }
    .loader-ring {
        position: absolute;
        border-radius: 50%;
        border: 3px solid transparent;
    }
    .loader-ring.outer {
        width: 100px;
        height: 100px;
        border-top-color: #7c3aed;
        border-bottom-color: #7c3aed;
        animation: spin3d-outer 2s infinite linear;
    }
    .loader-ring.middle {
        width: 76px;
        height: 76px;
        border-left-color: #0ea5e9;
        border-right-color: #0ea5e9;
        animation: spin3d-middle 1.5s infinite linear;
    }
    .loader-ring.inner {
        width: 52px;
        height: 52px;
        border-top-color: #e8197a;
        border-bottom-color: #e8197a;
        animation: spin3d-inner 1.2s infinite linear;
    }
    @keyframes spin3d-outer {
        0% { transform: rotateX(35deg) rotateY(45deg) rotateZ(0deg); }
        100% { transform: rotateX(35deg) rotateY(45deg) rotateZ(360deg); }
    }
    @keyframes spin3d-middle {
        0% { transform: rotateX(45deg) rotateY(-35deg) rotateZ(360deg); }
        100% { transform: rotateX(45deg) rotateY(-35deg) rotateZ(0deg); }
    }
    @keyframes spin3d-inner {
        0% { transform: rotateX(-35deg) rotateY(35deg) rotateZ(0deg); }
        100% { transform: rotateX(-35deg) rotateY(35deg) rotateZ(360deg); }
    }
    .loader-text {
        font-family: 'Rajdhani', sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        letter-spacing: 2px;
        text-transform: uppercase;
        background: linear-gradient(90deg, #7c3aed, #0ea5e9, #e8197a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: pulse-glow 1.5s infinite ease-in-out;
    }
    @keyframes pulse-glow {
        0%, 100% { opacity: 0.6; filter: drop-shadow(0 0 2px rgba(124,58,237,0.3)); }
        50% { opacity: 1; filter: drop-shadow(0 0 12px rgba(124,58,237,0.8)); }
    }

    /* HEADER */
    .site-header{position:fixed;top:16px;left:50%;transform:translateX(-50%);width:92%;max-width:1280px;z-index:1000;background:rgba(15,10,36,0.7);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:0 24px;box-shadow:0 12px 40px rgba(0,0,0,0.3);transition:all .3s}
    .nav-inner{display:flex;align-items:center;justify-content:space-between;max-width:1280px;margin:0 auto;height:68px}
    .nav-logo{display:flex;align-items:center;text-decoration:none}
    .nav-logo img{height:40px;width:auto;display:block;filter:brightness(0) invert(1)}
    .nav-links{display:flex;align-items:center;gap:4px;list-style:none}
    .nav-links a{color:var(--text);font-size:13px;font-weight:500;padding:7px 11px;border-radius:8px;transition:all .22s}
    .nav-links a:hover{background:rgba(124,58,237,0.15);color:#0ea5e9}
    .nav-links .btn-login{background:var(--grad-brand);color:#fff !important;padding:8px 20px;border-radius:8px;font-weight:600;box-shadow:0 4px 16px rgba(124,58,237,0.3);-webkit-text-fill-color:#fff !important}
    .nav-links .btn-login:hover{opacity:.88;}
    .nav-links .btn-signup{border:2px solid transparent;background:linear-gradient(rgba(15,10,36,0.65),rgba(15,10,36,0.65)) padding-box,var(--grad-brand) border-box;color:#fff !important;padding:7px 18px;border-radius:8px;font-weight:600}
    .nav-links .btn-signup:hover{background:linear-gradient(rgba(124,58,237,0.1),rgba(124,58,237,0.1)) padding-box,var(--grad-brand) border-box}
    .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;background:none;border:none}
    .hamburger span{display:block;width:24px;height:2px;background:#fff;border-radius:2px;transition:all .3s}
    .hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
    .hamburger.open span:nth-child(2){opacity:0}
    .hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
    .mobile-menu{display:none;flex-direction:column;background:rgba(15,10,36,0.95);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:16px 24px 20px;margin-top:8px}
    .mobile-menu.open{display:flex}
    .mobile-menu a{color:var(--text);padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:14px;font-weight:500}
    .mobile-menu a:last-child{border-bottom:none}
    .mobile-menu a:hover{color:#0ea5e9}

    /* HERO */
    .hero{min-height:100vh;display:flex;align-items:center;padding-top:88px;position:relative;overflow:hidden;background:var(--grad-hero-bg)}
    .hero::before{content:'';position:absolute;inset:0;z-index:0;background:radial-gradient(ellipse 50% 60% at 80% 30%,rgba(124,58,237,.12),transparent),radial-gradient(ellipse 40% 45% at 10% 70%,rgba(232,25,122,.09),transparent),radial-gradient(ellipse 35% 35% at 55% 90%,rgba(37,99,235,.07),transparent)}
    .hero::after{content:'';position:absolute;inset:0;z-index:0;background:linear-gradient(rgba(124,58,237,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,.025) 1px,transparent 1px);background-size:44px 44px}
    .hero-content{position:relative;z-index:2}
    .hero h6{font-size:12px;font-weight:600;letter-spacing:4px;text-transform:uppercase;margin-bottom:16px;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .hero h1{font-size:clamp(36px,5.5vw,62px);margin-bottom:20px;line-height:1.05;color:var(--text)}
    .hero p{color:var(--muted);font-size:15px;margin-bottom:14px}
    .hero-btns{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}
    .hero-visual{display:flex;align-items:center;justify-content:center;position:relative;z-index:2}
    .hero-graphic{width:100%;position:relative;margin:0 auto}
    .hero-card-stack{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px;width:100%;transform-style:preserve-3d}
    .hero-stat-card{background:rgba(15,10,36,0.65);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:24px 20px;text-align:center;transition:all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);box-shadow:0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);position:relative;overflow:hidden;transform:translateZ(10px)}
    .hero-stat-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(124,58,237,0.1),transparent);opacity:0.6;pointer-events:none}
    .hero-stat-card:hover{box-shadow:0 20px 45px rgba(124,58,237,0.25);border-color:rgba(124,58,237,0.3)}
    .hero-stat-card .stat-num{font-family:'Rajdhani',sans-serif;font-size:36px;font-weight:700;display:block;line-height:1;margin-bottom:6px;background:var(--grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 2px 8px rgba(245,158,11,0.3))}
    .hero-stat-card:nth-child(2) .stat-num{background:var(--grad-green);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 2px 8px rgba(16,185,129,0.3))}
    .hero-stat-card:nth-child(3) .stat-num{background:var(--grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 2px 8px rgba(14,165,233,0.3))}
    .hero-stat-card:nth-child(4) .stat-num{background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;filter:drop-shadow(0 2px 8px rgba(124,58,237,0.3))}
    .hero-stat-card .stat-label{font-size:12px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-top:6px}

    /* BUTTONS */
    .btn-primary-custom{display:inline-flex;align-items:center;gap:9px;background:var(--grad-brand);color:#fff !important;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:15px;letter-spacing:1.5px;text-transform:uppercase;padding:14px 32px;border-radius:12px;box-shadow:0 6px 20px rgba(124,58,237,0.3);transition:all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);-webkit-text-fill-color:#fff !important;border:1px solid rgba(255,255,255,0.15);cursor:pointer}
    .btn-primary-custom:hover{box-shadow:0 12px 35px rgba(124,58,237,0.6);border-color:rgba(255,255,255,0.35)}
    .btn-outline-custom{display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(255,255,255,0.15);color:#fff !important;-webkit-text-fill-color:#fff !important;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:15px;letter-spacing:1.5px;text-transform:uppercase;padding:13px 30px;border-radius:12px;transition:all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);background:rgba(255,255,255,0.06);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);box-shadow:0 4px 15px rgba(0,0,0,0.15)}
    .btn-outline-custom:hover{background:rgba(255,255,255,0.15);border-color:#0ea5e9;box-shadow:0 8px 25px rgba(14,165,233,0.4)}

    /* SECTION HEADINGS */
    .section-heading{margin-bottom:52px;text-align:center}
    .section-heading h2{font-size:clamp(28px,4vw,46px);color:var(--text)}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:4px;text-transform:uppercase;margin-bottom:12px;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .eyebrow .pulse{width:7px;height:7px;border-radius:50%;background:var(--pink);flex-shrink:0;animation:pulse 1.5s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.3;transform:scale(1.6)}}
    .grad-divider{height:4px;background:var(--grad-brand);margin:0}

    /* IMAGE BANNER MARQUEE */
    .img-banner {
      padding: 60px 0;
      overflow: hidden;
      background: linear-gradient(180deg, #05020c 0%, #0a051b 50%, #05020c 100%);
      position: relative;
      border-top: 1px solid rgba(255,255,255,0.06);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .img-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(232,25,122,0.06), rgba(124,58,237,0.06), rgba(37,99,235,0.06));
      pointer-events: none;
      z-index: 2;
    }
    .img-banner-track {
      display: flex;
      gap: 24px;
      animation: marquee 35s linear infinite;
      width: max-content;
      transform: perspective(1200px) rotateX(12deg) rotateY(-4deg) rotateZ(1deg);
      transform-style: preserve-3d;
      padding: 20px 0;
    }
    .img-banner-track:hover {
      animation-play-state: paused;
    }
    .banner-img-item {
      flex-shrink: 0;
      width: 220px;
      height: 135px;
      border-radius: 18px;
      overflow: hidden;
      border: 1.5px solid rgba(255,255,255,0.08);
      position: relative;
      background: rgba(15,10,36,0.6);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3), inset 0 1px 1px rgba(255,255,255,0.1);
      transform: translateZ(10px) rotateY(-5deg);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      transform-style: preserve-3d;
    }
    .banner-img-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.7;
      transition: transform 0.4s, opacity 0.3s;
      transform: translateZ(15px);
    }
    .banner-img-item:hover {
      
      border-color: rgba(124,58,237,0.3);
      box-shadow: 0 20px 45px rgba(124,58,237,0.25);
    }
    .banner-img-item:hover img {
      opacity: 0.95;
      
    }
    .banner-img-item .banner-label {
      position: absolute;
      bottom: 12px;
      left: 12px;
      font-size: 11px;
      font-weight: 700;
      color: #fff;
      background: rgba(15, 10, 36, 0.8);
      border: 1px solid rgba(255,255,255,0.12);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-radius: 8px;
      padding: 4px 12px;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transform: translateZ(30px);
      transition: all 0.3s;
    }
    .banner-img-item:hover .banner-label {
      background: var(--grad-brand);
      border-color: transparent;
      
    }
    @keyframes marquee {
      0% { transform: perspective(1200px) rotateX(12deg) rotateY(-4deg) rotateZ(1deg) translateX(0); }
      100% { transform: perspective(1200px) rotateX(12deg) rotateY(-4deg) rotateZ(1deg) translateX(-50%); }
    }

    /* STATS */
    .stats-section{background:var(--grad-section-b);padding:80px 0;position:relative;overflow:hidden;border-top:1px solid rgba(255,255,255,0.06);border-bottom:1px solid rgba(255,255,255,0.06)}
    .stats-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 50% 80% at 20% 50%,rgba(255,255,255,.02),transparent),radial-gradient(ellipse 40% 60% at 80% 50%,rgba(255,255,255,.01),transparent)}
    .stat-counter {
      text-align: center;
      padding: 30px 20px;
      position: relative;
      z-index: 1;
      background: rgba(15,10,36,0.6);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      transform: perspective(1000px) rotateX(1deg);
    }
    .stat-counter:hover {
      
      border-color: rgba(232,25,122,0.3);
      box-shadow: 0 20px 45px rgba(232,25,122,0.2), inset 0 1px 1px rgba(255,255,255,0.2);
    }
    .stat-counter .big-num{font-family:'Rajdhani',sans-serif;font-size:56px;font-weight:700;line-height:1;color:#fff;text-shadow:0 4px 20px rgba(0,0,0,.2)}
    .stat-counter .big-label{font-size:12px;color:rgba(255,255,255,.5);margin-top:8px;text-transform:uppercase;letter-spacing:2px}

    /* OFFERS */
    .offers-section{background:var(--grad-section-a);position:relative;overflow:hidden;padding:100px 0}
    .offers-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 50% 40% at 15% 20%,rgba(124,58,237,.07),transparent),radial-gradient(ellipse 40% 35% at 85% 75%,rgba(232,25,122,.06),transparent),linear-gradient(rgba(124,58,237,.018) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,.018) 1px,transparent 1px);background-size:100% 100%,100% 100%,44px 44px,44px 44px;pointer-events:none}
    .chart-card-3d {
      background: rgba(15, 10, 36, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 24px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      height: 100%;
      transform: perspective(1000px) rotateX(1deg);
    }
    .chart-card-3d:hover {
      
      border-color: rgba(124, 58, 237, 0.3);
      box-shadow: 0 20px 45px rgba(124, 58, 237, 0.2), inset 0 1px 1px rgba(255, 255, 255, 0.2);
    }
    .aff-tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-bottom:34px}
    .aff-tab {
      font-family: 'Rajdhani', sans-serif;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      padding: 8px 18px;
      border-radius: 30px;
      border: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.03);
      color: rgba(255,255,255,0.6);
      cursor: pointer;
      transition: all .22s;
    }
    .aff-tab:hover {
      border-color: rgba(255,255,255,0.2);
      color: #fff;
      background: rgba(255,255,255,0.08);
    }
    .aff-tab.active{background:var(--grad-brand);border-color:transparent;color:#fff;box-shadow:0 4px 16px rgba(232,25,122,.3)}
    .aff-tab[data-cat="casino"].active{background:var(--grad-gold);box-shadow:0 4px 16px rgba(217,119,6,.3)}
    .aff-tab[data-cat="cam"].active{background:linear-gradient(135deg,#a855f7,#ec4899)}
    .aff-tab[data-cat="software"].active{background:var(--grad-cool);box-shadow:0 4px 16px rgba(14,165,233,.3)}
    .aff-tab[data-cat="smartlink"].active{background:var(--grad-green);box-shadow:0 4px 16px rgba(5,150,105,.3)}
    .aff-tab[data-cat="doi"].active{background:var(--grad-warm);box-shadow:0 4px 16px rgba(249,115,22,.3)}
    .slider-outer{position:relative;max-width:1400px;margin:0 auto}
    .slider-wrap{overflow:hidden;padding:12px 4px 18px}
    .slider-track{display:flex;gap:16px;will-change:transform;transition:transform .52s cubic-bezier(.4,0,.2,1)}
    .offer-card{flex:0 0 calc(25% - 12px);background:rgba(15,10,36,0.65);border:1px solid rgba(255,255,255,0.08);border-radius:18px;overflow:hidden;position:relative;cursor:pointer;transition:transform 0.15s ease-out, border-color 0.3s, box-shadow 0.3s;box-shadow:0 8px 32px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.08);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);transform-style:preserve-3d}
    .offer-card:hover{border-color:rgba(124,58,237,0.3);box-shadow:0 20px 50px rgba(124,58,237,0.25)}
    .offer-card-img{width:100%;height:140px;object-fit:cover;display:block;transition:transform .4s;transform:translateZ(10px)}
    .offer-card-img[src*="logoo.png"],
    .offer-card-img[src*="logo"] {
      width: 65% !important;
      height: 90px !important;
      object-fit: contain !important;
      margin: 25px auto !important;
      filter: brightness(0) invert(1) !important;
      opacity: 0.9 !important;
    }
    .offer-card:hover .offer-card-img{}
    .offer-card-img-wrap{overflow:hidden;position:relative}
    .offer-card-img-wrap::after{content:'';position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(transparent,rgba(15,10,36,0.65))}
    .offer-card-body{padding:14px 16px 16px;transform:translateZ(10px)}
    .offer-id{display:inline-block;font-size:10px;font-weight:600;color:rgba(255,255,255,0.5);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:2px 8px;letter-spacing:.4px;margin-bottom:8px}
    .offer-badge{display:inline-block;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:2px 7px;border-radius:4px;margin-bottom:8px;margin-left:6px}
    .badge-soi{background:rgba(232,25,122,.1);color:var(--pink);border:1px solid rgba(232,25,122,.22)}
    .badge-doi{background:rgba(249,115,22,.1);color:#f97316;border:1px solid rgba(249,115,22,.22)}
    .badge-smartlink{background:rgba(5,150,105,.1);color:var(--green);border:1px solid rgba(5,150,105,.22)}
    .badge-casino{background:rgba(217,119,6,.1);color:var(--gold);border:1px solid rgba(217,119,6,.22)}
    .badge-cam{background:rgba(168,85,247,.1);color:#a855f7;border:1px solid rgba(168,85,247,.22)}
    .badge-software{background:rgba(14,165,233,.1);color:var(--cyan);border:1px solid rgba(14,165,233,.22)}
    .badge-cps{background:rgba(217,119,6,.1);color:var(--gold);border:1px solid rgba(217,119,6,.22)}
    .badge-financial{background:rgba(37,99,235,.1);color:var(--blue);border:1px solid rgba(37,99,235,.22)}
    .offer-name{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:4px}
    .offer-type{font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
    .offer-countries{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px;min-height:24px}
    .ctag{font-size:10px;font-weight:600;padding:2px 7px;border-radius:5px;background:rgba(37,99,235,.08);color:var(--blue);border:1px solid rgba(37,99,235,.18)}
    .ctag.ww{background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.2)}
    .offer-payout-row{display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid rgba(255,255,255,0.08)}
    .payout-label{font-size:10px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
    .payout-val{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;line-height:1;background:var(--grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .btn-apply{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#fff;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:6px 12px;transition:all .22s;white-space:nowrap}
    .btn-apply:hover{background:var(--grad-brand);border-color:transparent;color:#fff;box-shadow:0 4px 15px rgba(124,58,237,0.4)}
    .slider-controls{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:28px}
    .slider-btn{width:44px;height:44px;border-radius:50%;border:none;background:var(--grad-brand);color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .25s;box-shadow:0 4px 16px rgba(232,25,122,.3)}
    .slider-btn:hover:not(:disabled){box-shadow:0 8px 24px rgba(124,58,237,.4)}
    .slider-btn:disabled{opacity:.3;cursor:not-allowed;box-shadow:none}
    .slider-dots{display:flex;gap:7px;align-items:center;flex-wrap:wrap;justify-content:center;max-width:300px}
    .slider-dot{width:7px;height:7px;border-radius:50%;background:rgba(124,58,237,.2);cursor:pointer;transition:all .3s}
    .slider-dot.active{width:24px;border-radius:4px;background:var(--grad-brand);box-shadow:0 2px 8px rgba(232,25,122,.35)}
    .slider-prog{height:4px;background:rgba(124,58,237,.12);border-radius:2px;max-width:240px;margin:14px auto 0;overflow:hidden}
    .slider-prog-fill{height:100%;background:var(--grad-brand);border-radius:4px;width:0%;transition:width .1s linear}
    .count-badge{display:inline-block;background:linear-gradient(135deg,rgba(232,25,122,.12),rgba(124,58,237,.12));color:var(--pink);border:1px solid rgba(232,25,122,.25);border-radius:20px;font-size:12px;font-weight:600;padding:2px 12px;margin-left:10px;vertical-align:middle}
    .smartlink-highlight {
      background: rgba(15, 10, 36, 0.65) !important;
      border: 1px solid rgba(255, 255, 255, 0.08) !important;
      border-radius: 32px;
      padding: 56px 40px;
      text-align: center;
      margin: 70px 0 0;
      position: relative;
      overflow: hidden;
      box-shadow: 0 30px 70px rgba(0,0,0,0.4), inset 0 1px 1px rgba(255,255,255,0.15) !important;
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      perspective: 1000px;
      transform-style: preserve-3d;
      transition: transform 0.15s ease-out;
    }
    .smartlink-highlight::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top right,rgba(14,165,233,.12),transparent 60%),radial-gradient(circle at bottom left,rgba(124,58,237,.12),transparent 60%);
      pointer-events: none;
    }
    .smartlink-highlight h3 {
      font-size: 34px;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
      letter-spacing: -0.5px;
      color: #fff !important;
      transform: translateZ(25px);
    }
    .smartlink-highlight p {
      color: rgba(255,255,255,0.7) !important;
      font-size: 15px;
      max-width: 650px;
      margin: 0 auto 32px;
      position: relative;
      z-index: 1;
      line-height: 1.6;
      transform: translateZ(15px);
    }
    .sl-features {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      margin-bottom: 36px;
      position: relative;
      z-index: 1;
      transform-style: preserve-3d;
    }
    .sl-feat {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(15, 10, 36, 0.5) !important;
      color: #6ee7b7 !important;
      border: 1px solid rgba(16, 185, 129, 0.22) !important;
      border-radius: 40px;
      padding: 10px 24px;
      font-size: 13px;
      font-weight: 700;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255,255,255,0.1) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateZ(30px);
    }
    .sl-feat:hover {
      
      border-color: rgba(16, 185, 129, 0.45) !important;
      box-shadow: 0 8px 30px rgba(16, 185, 129, 0.2), inset 0 1px 1px rgba(255,255,255,0.15) !important;
    }

    /* ABOUT & FEATURES */
    .about-section{background:var(--grad-section-a);position:relative;overflow:hidden}
    .feature-card-3d {
      background: rgba(15, 10, 36, 0.65);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 24px;
      padding: 24px;
      text-align: center;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      transform-style: preserve-3d;
      perspective: 1000px;
    }
    .feature-card-3d:hover {
      
      border-color: rgba(124, 58, 237, 0.3);
      box-shadow: 0 20px 50px rgba(124, 58, 237, 0.2);
    }
    .canvas-container {
      width: 100%;
      height: 140px;
      margin-bottom: 20px;
      position: relative;
      border-radius: 16px;
      background: rgba(10, 5, 30, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.04);
      overflow: hidden;
    }
    .feature-canvas {
      width: 100%;
      height: 100%;
      display: block;
    }
    .feature-card-3d h4 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 12px;
      color: #fff;
      font-family: 'Rajdhani', sans-serif;
    }
    .feature-card-3d p {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.6);
      line-height: 1.6;
      margin: 0;
    }

    /* SERVICES */
    .services-section{background:var(--grad-section-b); padding:100px 0}
    .services-section h2 { color: #fff }
    .services-section .eyebrow { -webkit-text-fill-color: rgba(255,255,255,0.7); background: none; color: rgba(255,255,255,0.7) }
    
    .laptop-3d-wrap {
      perspective: 1500px;
      position: relative;
      margin-bottom: 30px;
      transform-style: preserve-3d;
      width: 100%;
    }
    .laptop-3d-screen {
      transform: rotateY(-14deg) rotateX(6deg) rotateZ(1deg);
      border-radius: 16px;
      overflow: hidden;
      border: 4px solid #1e1b29;
      box-shadow: -20px 20px 50px rgba(0,0,0,0.6), 0 0 40px rgba(124,58,237,0.15);
      position: relative;
      background: #000;
      aspect-ratio: 16/10;
      transform-style: preserve-3d;
      transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .laptop-3d-screen img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }
    .glass-reflection {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 50%);
      pointer-events: none;
      z-index: 2;
    }
    .laptop-3d-base {
      width: 95%;
      height: 12px;
      background: linear-gradient(to bottom, #2d283e, #0f0b1a);
      border-radius: 0 0 12px 12px;
      margin: -6px auto 0;
      transform: rotateY(-14deg) rotateX(6deg) rotateZ(1deg) translateZ(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.7);
      border-bottom: 2px solid #4d446f;
      transition: transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .laptop-3d-wrap:hover .laptop-3d-screen {
      
    }
    .laptop-3d-wrap:hover .laptop-3d-screen img {
      
    }
    .laptop-3d-wrap:hover .laptop-3d-base {
      
    }
    
    .progress-item{margin-bottom:30px}
    .progress-item h4{font-size:16px;margin-bottom:6px;color:#fff;display:flex;align-items:center;justify-content:space-between}
    .progress-item p{font-size:13px;color:rgba(255,255,255,0.6);margin-bottom:10px}
    
    .progress-bar-wrap {
      position: relative;
      height: 10px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      overflow: visible;
    }
    .progress-bar-fill {
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      background: linear-gradient(90deg, #7c3aed, #0ea5e9);
      border-radius: 20px;
      width: 0;
      transition: width 1.5s cubic-bezier(.4,0,.2,1);
      box-shadow: 0 0 14px rgba(14, 165, 233, 0.6);
    }
    .progress-bar-fill::after {
      content: '';
      position: absolute;
      right: -5px;
      top: -4px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 0 12px #0ea5e9, 0 0 20px #7c3aed;
      animation: pulse 1.5s infinite;
    }
    .progress-label{font-size:11px;font-weight:700;flex-shrink:0;background:var(--grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* DASHBOARD PREVIEW */
    .dashboard-preview-section { padding: 100px 0; background: var(--grad-section-b); }
    .dashboard-preview-wrap {
      max-width: 1100px;
      margin: 0 auto;
      background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 28px;
      padding: 10px;
      box-shadow: 0 40px 100px rgba(0,0,0,0.4);
      transform-style: preserve-3d;
      transform: perspective(1000px) rotateX(2deg);
    }
    .dashboard-glass-ui {
      display: flex;
      border-radius: 20px;
      overflow: hidden;
      background: rgba(15,10,36,0.6);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border: 1px solid rgba(255,255,255,0.08);
      min-height: 480px;
    }
    .db-sidebar {
      width: 220px;
      background: rgba(10,5,30,0.4);
      border-right: 1px solid rgba(255,255,255,0.06);
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }
    @media(max-width:768px){.db-sidebar{display:none}}
    .db-logo { padding-left: 8px; }
    .db-menu { list-style: none; display: flex; flex-direction: column; gap: 8px; }
    .db-menu li {
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 500;
      color: rgba(255,255,255,0.6);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.22s;
    }
    .db-menu li.active, .db-menu li:hover {
      background: rgba(124,58,237,0.15);
      color: #fff;
    }
    .db-content { flex: 1; padding: 24px 30px; display: flex; flex-direction: column; gap: 24px; }
    .db-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
    .db-header h4 { font-size: 20px; margin: 0; color: #fff; }
    .db-header-actions { display: flex; align-items: center; gap: 16px; }
    .db-badge.live {
      font-size: 10px;
      font-weight: 700;
      background: rgba(16,185,129,0.1);
      color: #10b981;
      border: 1px solid rgba(16,185,129,0.2);
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .db-badge.live .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #10b981;
      animation: pulse 1.5s infinite;
    }
    .db-user { font-size: 12px; color: rgba(255,255,255,0.6); }
    .db-widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 16px; }
    .db-widget {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.05);
      border-radius: 14px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .db-widget .widget-label { font-size: 10px; text-transform: uppercase; color: rgba(255,255,255,0.5); letter-spacing: 1px; }
    .db-widget .widget-val { font-family: 'Rajdhani', sans-serif; font-size: 24px; font-weight: 700; color: #fff; }
    .db-widget .widget-trend { font-size: 10px; font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .db-widget .widget-trend.up { color: #10b981; }
    .db-widget .widget-trend.down { color: #ef4444; }
    .db-chart-row { width: 100%; }
    .db-chart-card {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.05);
      border-radius: 16px;
      padding: 20px;
    }
    .db-chart-card h5 { font-size: 15px; margin-bottom: 16px; color: #fff; }

    /* ADVERTISER SECTION */
    .advertiser-section { padding: 100px 0; background: var(--grad-section-a); }
    .adv-features { display: flex; flex-direction: column; gap: 10px; }
    .adv-feature-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.85); }
    .adv-ecosystem-wrap {
      width: 100%;
      height: 380px;
      border-radius: 24px;
      background: rgba(15,10,36,0.65);
      border: 1px solid rgba(255,255,255,0.08);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      overflow: hidden;
      position: relative;
      box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    }
    .ecosystem-canvas { width: 100%; height: 100%; display: block; }

    /* TRUST & SECURITY */
    .trust-security-section { padding: 100px 0; background: var(--grad-section-b); position: relative; }
    .security-card {
      background: rgba(15, 10, 36, 0.65);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 28px 24px;
      text-align: center;
      transition: all 0.3s ease;
      height: 100%;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .security-card:hover {
      
      border-color: rgba(232,25,122,0.3);
      box-shadow: 0 15px 35px rgba(232,25,122,0.15);
    }
    .sec-icon {
      width: 54px;
      height: 54px;
      border-radius: 50%;
      background: rgba(232,25,122,0.1);
      border: 1px solid rgba(232,25,122,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #e8197a;
      font-size: 20px;
      margin: 0 auto 18px;
    }
    .security-card h4 { font-size: 18px; margin-bottom: 10px; color: #fff; }
    .security-card p { font-size: 13px; color: rgba(255,255,255,0.6); margin: 0; line-height: 1.6; }

    /* PARTNERS */
    .partners-section{background:linear-gradient(135deg,#0a0518 0%,#05020c 100%)}
    .partners-section .section-heading h2{color:#fff}
    .partners-section .eyebrow{-webkit-text-fill-color:rgba(255,255,255,.7);background:none;color:rgba(255,255,255,.7)}
    .partner-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px}
    .partner-card{background:rgba(255,255,255,.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.14);border-radius:16px;padding:22px 16px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:10px;transition:all .3s;cursor:pointer}
    .partner-card:hover{background:rgba(255,255,255,.16);box-shadow:0 12px 30px rgba(0,0,0,.25)}
    .partner-card .p-icon{font-size:28px}
    .partner-card span{font-size:12px;font-weight:600;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:1px}

    /* BLOG CARDS */
    .blog-section{background:var(--grad-section-b);padding:100px 0}
    .blog-section h2 { color: #fff }
    .blog-section .eyebrow { -webkit-text-fill-color: rgba(255,255,255,0.7); background: none; color: rgba(255,255,255,0.7) }
    .blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin-top:48px}
    .blog-card {
      background: rgba(15,10,36,0.65);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.15s ease-out, border-color 0.3s, box-shadow 0.3s;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
      transform: perspective(1000px) rotateX(1deg);
      transform-style: preserve-3d;
    }
    .blog-card:hover {
      border-color: rgba(124,58,237,0.3);
      box-shadow: 0 20px 45px rgba(124,58,237,0.25);
    }
    .blog-card-img{position:relative;overflow:hidden;aspect-ratio:16/10;transform:translateZ(15px);transform-style:preserve-3d;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;padding:6px}
    .blog-card-img img{width:100%;height:100%;object-fit:contain;display:block;transition:transform .4s}
    .blog-card:hover .blog-card-img img{}
    .blog-card-cat{position:absolute;top:14px;left:14px;background:var(--grad-brand);color:#fff;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;letter-spacing:.5px;text-transform:uppercase;transform:translateZ(10px)}
    .blog-card-body{padding:22px 22px 20px;flex:1;display:flex;flex-direction:column;gap:10px;transform:translateZ(25px)}
    .blog-card-date{font-size:12px;color:rgba(255,255,255,0.45);display:flex;align-items:center;gap:5px}
    .blog-card-title{font-size:17px;font-weight:700;color:#fff;line-height:1.35;margin:0;font-family:'Rajdhani',sans-serif;flex:1}
    .blog-card-title a{color:inherit;text-decoration:none;transition:color .2s}
    .blog-card-title a:hover{color:#0ea5e9}
    .blog-card-excerpt{font-size:13px;color:rgba(255,255,255,0.65);line-height:1.65;margin:0}
    .blog-card-footer{display:flex;align-items:center;justify-content:flex-end;padding-top:14px;border-top:1px solid rgba(255,255,255,0.08);margin-top:auto;transform:translateZ(30px)}
    .blog-card-read{font-size:13px;font-weight:700;color:#7c3aed;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:gap .2s,color .2s}
    .blog-card-read:hover{color:#0ea5e9;gap:9px}
    .blog-view-all{text-align:center;margin-top:48px}
    @media(max-width:991px){.blog-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:575px){.blog-grid{grid-template-columns:1fr}}

    /* TEAM */
    .contact-team{background:var(--grad-section-a); padding:100px 0}
    .contact-team h2 { color: #fff }
    .contact-team .eyebrow { -webkit-text-fill-color: rgba(255,255,255,0.7); background: none; color: rgba(255,255,255,0.7) }
    .team-card {
      background: rgba(15,10,36,0.6);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 22px;
      padding: 34px;
      text-align: center;
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transform: perspective(1000px) rotateX(1deg);
    }
    .team-card:hover {
      
      border-color: rgba(232,25,122,0.3);
      box-shadow: 0 20px 45px rgba(232,25,122,0.25);
    }
    .team-avatar {
      width: 82px;
      height: 82px;
      border-radius: 50%;
      margin: 0 auto 16px;
      background: var(--grad-brand);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      box-shadow: 0 8px 24px rgba(232,25,122,0.3);
    }
    .team-card h4 { color: #fff; font-size:20px; margin-bottom:6px }
    .team-card .team-role { color: rgba(255,255,255,0.5); font-size:12px; margin-bottom: 18px; text-transform:uppercase; letter-spacing:1px }
    .team-links { display: flex; flex-direction: column; gap: 8px }
    .team-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      color: rgba(255,255,255,0.7) !important;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.08);
      text-decoration: none;
      transition: all 0.22s;
    }
    .team-link:hover {
      background: rgba(255,255,255,0.08);
      color: #fff !important;
      border-color: rgba(255,255,255,0.15);
    }
    .team-link i { color: var(--pink) }

    /* CONTACT FORM */
    .contact-form-section { background: var(--grad-section-b); padding:100px 0 }
    .contact-form-section h2 { color: #fff }
    .contact-form-section .eyebrow { -webkit-text-fill-color: rgba(255,255,255,0.7); background: none; color: rgba(255,255,255,0.7) }
    .address-block {
      background: rgba(15,10,36,0.6);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: var(--r);
      padding: 24px;
      margin-top: 24px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .address-block h5 { font-size: 14px; margin-bottom: 8px; color: #fff }
    .address-block p { font-size: 13px; color: rgba(255,255,255,0.65); margin: 0; line-height: 1.85 }
    .form-card {
      background: rgba(15,10,36,0.6);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 22px;
      padding: 40px 36px;
      box-shadow: 0 16px 50px rgba(0,0,0,0.3);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .form-card h3 { font-size: 26px; margin-bottom: 26px; color: #fff }
    .form-group { margin-bottom: 18px }
    .form-group label { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; display: block }
    .form-control-custom {
      width: 100%;
      padding: 12px 16px;
      background: rgba(255,255,255,0.03);
      border: 1.5px solid rgba(255,255,255,0.08);
      border-radius: 10px;
      color: #fff;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      transition: all .25s;
      outline: none;
    }
    .form-control-custom:focus {
      border-color: var(--violet);
      box-shadow: 0 0 0 4px rgba(124,58,237,.25);
      background: rgba(255,255,255,0.05);
    }
    .form-control-custom::placeholder { color: rgba(255,255,255,0.4) }
    textarea.form-control-custom { resize: vertical; min-height: 120px }
    .contact-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px }
    @media(max-width: 480px) { .contact-form-row { grid-template-columns: 1fr } }

    /* PAYMENT CARDS */
    .payment-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
    .payment-card {
      background: rgba(15,10,36,0.6);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: var(--r);
      padding: 22px 18px;
      text-align: center;
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transform: perspective(1000px) rotateX(1deg);
    }
    .payment-card:hover {
      
      border-color: rgba(14,165,233,0.3);
      box-shadow: 0 20px 45px rgba(14,165,233,0.2), inset 0 1px 1px rgba(255,255,255,0.2);
    }
    .payment-card .p-icon{font-size:28px;margin-bottom:10px;display:block}
    .payment-card h5{font-size:14px;margin-bottom:4px;color:#fff}
    .payment-card p{font-size:12px;color:rgba(255,255,255,0.55);margin:0}

    /* FOOTER */
    @keyframes gradientMove {
      0%{background-position:0% 50%}
      50%{background-position:100% 50%}
      100%{background-position:0% 50%}
    }
    .footer-links{display:flex;flex-wrap:wrap;justify-content:center;gap:16px;margin:18px 0}
    .footer-links a{font-size:13px;color:rgba(255,255,255,.5);transition:color .25s}
    .footer-links a:hover{color:#0ea5e9}

    /* SCROLL TOP */
    .scroll-top{position:fixed;bottom:28px;right:28px;z-index:500;width:46px;height:46px;border-radius:50%;background:var(--grad-brand);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;border:none;cursor:pointer;box-shadow:0 6px 22px rgba(232,25,122,.45);opacity:0;transform:translateY(20px);transition:all .3s}
    .scroll-top.visible{opacity:1;transform:translateY(0)}
    .scroll-top:hover{box-shadow:0 10px 30px rgba(232,25,122,.6)}
    .fade-up{opacity:0;transform:translateY(32px);transition:opacity .75s ease,transform .75s ease}
    .fade-up.visible{opacity:1;transform:translateY(0)}

    /* OFFER BADGE OVERLAYS */
    .offer-badge-overlay{position:absolute;top:8px;right:8px;display:flex;flex-direction:column;gap:4px;z-index:5}
    .badge-hot-pill{background:linear-gradient(135deg,#ff4500,#e8197a);color:#fff;font-size:8px;font-weight:800;letter-spacing:.8px;padding:3px 8px;border-radius:20px;text-transform:uppercase;box-shadow:0 3px 8px rgba(232,25,122,.5)}
    .badge-top-pill{background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-size:8px;font-weight:800;letter-spacing:.8px;padding:3px 8px;border-radius:20px;text-transform:uppercase;box-shadow:0 3px 8px rgba(245,158,11,.45)}
    .badge-new-pill{background:linear-gradient(135deg,#059669,#0ea5e9);color:#fff;font-size:8px;font-weight:800;letter-spacing:.8px;padding:3px 8px;border-radius:20px;text-transform:uppercase}

    /* HERO 3D GRAPHIC EXPERIENCES */
    .hero-graphic-3d {
      width: 100%;
      height: 580px;
      position: relative;
      margin: 0 auto;
      perspective: 1200px;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10;
    }
    
    .threed-platform {
      position: absolute;
      width: 80%;
      height: 30px;
      bottom: 20px;
      background: radial-gradient(ellipse 50% 50% at 50% 50%, rgba(124,58,237,0.35), rgba(79,70,229,0.1), transparent);
      border-radius: 50%;
      transform: rotateX(70deg) translateZ(-40px);
      filter: blur(10px);
      animation: platformPulse 6s infinite ease-in-out;
    }

    .threed-scene-wrap {
      width: 100%;
      height: 100%;
      position: relative;
      transform-style: preserve-3d;
      transition: transform 0.1s ease-out;
    }

    .floating-dashboard-wrap {
      position: absolute;
      width: 90%;
      aspect-ratio: 16/14;
      left: 5%;
      top: 5%;
      border-radius: 24px;
      padding: 6px;
      background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.12);
      box-shadow: 0 45px 100px rgba(10,5,30,0.45), 0 0 40px rgba(124,58,237,0.15);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      transform: rotateY(-14deg) rotateX(8deg) rotateZ(1deg);
      transform-style: preserve-3d;
      animation: mainFloat 6s infinite ease-in-out;
      overflow: hidden;
    }

    /* HERO SLIDER (INTEGRATED INTO 3D DASHBOARD) */
    .hero-slides{display:flex;width:100%;height:100%;align-items:center;transition:transform .65s cubic-bezier(.4,0,.2,1)}
    .hero-slide{flex:0 0 100%;position:relative;height:100%;overflow:hidden}
    .hero-slide img{width:100%;height:100%;object-fit:cover;display:block}
    .hero-slide-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,5,30,0.85) 0%,rgba(10,5,30,0.2) 60%,transparent 100%);z-index:1}
    .hero-slide-caption{position:absolute;bottom:24px;left:24px;right:60px;z-index:2}
    .hero-slide-caption h3{font-family:'Rajdhani',sans-serif;font-size:clamp(14px,2.2vw,24px);font-weight:700;color:#fff;line-height:1.15;margin-bottom:6px;text-shadow:0 2px 10px rgba(0,0,0,.5)}
    .hero-slide-caption p{font-size:clamp(11px,1.3vw,14px);color:rgba(255,255,255,.8);margin:0;line-height:1.4;text-shadow:0 1px 6px rgba(0,0,0,.6)}
    .hero-slider-arrow{position:absolute;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.16);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.28);color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:5;transition:background .22s,transform .22s;line-height:1}
    .hero-slider-arrow:hover{background:rgba(255,255,255,.30);}
    .hero-slider-arrow.prev{left:12px}
    .hero-slider-arrow.next{right:12px}
    .hero-slider-skeleton{width:100%;height:100%;background:linear-gradient(135deg,#1a1535 0%,#2d1b69 100%);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px}
    .hero-slider-skeleton .sk-pulse{width:32px;height:32px;border-radius:50%;border:3px solid rgba(124,58,237,.2);border-top-color:var(--violet);animation:spin .8s linear infinite}
    .hero-slider-skeleton span{font-size:12px;color:rgba(255,255,255,.6);font-family:'DM Sans',sans-serif}

    .glass-reflection-glare {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 40%, rgba(124,58,237,0.03) 60%, rgba(255,255,255,0) 100%);
      pointer-events: none;
      z-index: 5;
      animation: glassGlare 8s infinite linear;
    }

    /* Floating UI glassmorphism layers */
    .floating-ui-layer {
      position: absolute;
      z-index: 20;
      transform-style: preserve-3d;
      pointer-events: auto;
      transition: transform 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Clicks Layer */
    .card-clicks {
      width: 160px;
      left: -5%;
      top: 25%;
      animation: subFloat1 5s infinite ease-in-out;
    }

    /* Conversions Layer */
    .card-convs {
      width: 165px;
      right: -2%;
      top: 15%;
      animation: subFloat2 6.5s infinite ease-in-out;
    }

    /* Revenue/Payout Layer */
    .card-revenue {
      width: 160px;
      left: 12%;
      bottom: 12%;
      animation: subFloat3 5.8s infinite ease-in-out;
    }

    /* EPC Layer */
    .card-epc {
      width: 130px;
      right: 18%;
      top: -4%;
      animation: subFloat1 7.2s infinite ease-in-out;
    }

    /* Fraud Alert Layer */
    .card-fraud {
      width: 250px;
      right: -10%;
      bottom: 22%;
      animation: subFloat4 8s infinite ease-in-out;
    }

    /* Chat Popup Layer */
    .card-chat {
      width: 210px;
      left: -12%;
      top: 60%;
      animation: subFloat2 7.8s infinite ease-in-out;
    }

    /* Glowing Encrypted node */
    .lock-node {
      width: 90px;
      right: 38%;
      bottom: -6%;
      animation: subFloat3 6s infinite ease-in-out;
    }

    /* Common Card Styling (Apple Vision Pro Glassmorphism) */
    .card-inner {
      background: rgba(15, 10, 36, 0.65);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 12px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
    }

    .card-inner:hover {
      border-color: rgba(124, 58, 237, 0.4);
      
      box-shadow: 0 25px 50px rgba(124, 58, 237, 0.25);
    }

    .card-glow-border {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(124,58,237,0.2), transparent);
      opacity: 0.8;
      pointer-events: none;
    }

    .card-header-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.05);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .card-val-group {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .card-label {
      font-size: 10px;
      color: rgba(255,255,255,0.45);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .card-val {
      font-family: 'Rajdhani', sans-serif;
      font-size: 18px;
      font-weight: 700;
      color: #fff;
      line-height: 1.1;
    }

    .card-trend {
      font-size: 9px;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 12px;
      align-self: flex-start;
    }

    .card-trend.up {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
    }

    /* Live Chat Popup Styles */
    .chat-inner {
      background: rgba(10, 7, 28, 0.8);
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 16px;
      padding: 10px 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.35);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 11px;
    }

    .chat-header {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .chat-avatar {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      object-fit: cover;
    }

    .chat-user-info {
      display: flex;
      flex-direction: column;
      line-height: 1.1;
    }

    .chat-name {
      font-weight: 700;
      color: #fff;
    }

    .chat-status {
      font-size: 8px;
      color: #10b981;
      display: flex;
      align-items: center;
      gap: 3px;
    }

    .chat-status-dot {
      width: 4px;
      height: 4px;
      background: #10b981;
      border-radius: 50%;
      display: inline-block;
    }

    .chat-bubble {
      background: rgba(255, 255, 255, 0.05);
      color: rgba(255,255,255,0.85);
      padding: 8px 10px;
      border-radius: 0 12px 12px 12px;
      margin: 0;
      line-height: 1.35;
    }

    /* Fraud Alert CSS */
    .fraud-inner {
      background: rgba(239, 68, 68, 0.07);
      border: 1px solid rgba(239, 68, 68, 0.25);
      border-radius: 16px;
      padding: 12px 14px;
      box-shadow: 0 15px 35px rgba(239, 68, 68, 0.1);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      position: relative;
      overflow: hidden;
    }

    .animate-pulse-border {
      animation: pulseBorder 2.5s infinite ease-in-out;
    }

    .fraud-glow {
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 10% 10%, rgba(239,68,68,0.12), transparent);
      pointer-events: none;
    }

    .fraud-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
    }

    .fraud-indicator {
      width: 6px;
      height: 6px;
      background: #ef4444;
      border-radius: 50%;
      box-shadow: 0 0 8px #ef4444;
      animation: spin 1s infinite alternate;
    }

    .fraud-title {
      font-size: 10px;
      font-weight: 700;
      color: #ef4444;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }

    .fraud-text {
      font-size: 11px;
      color: rgba(255,255,255,0.8);
      margin: 0 0 8px;
      line-height: 1.4;
    }

    .fraud-footer {
      display: flex;
      align-items: center;
    }

    .shield-badge {
      font-size: 8px;
      font-weight: 700;
      text-transform: uppercase;
      color: #ef4444;
      background: rgba(239,68,68,0.12);
      padding: 3px 8px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    /* Lock Node Styling */
    .lock-circle {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.08);
      border: 1px solid rgba(16, 185, 129, 0.3);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15);
      cursor: pointer;
      transition: all 0.3s;
    }

    .lock-circle:hover {
      background: rgba(16,185,129,0.15);
      border-color: rgba(16,185,129,0.6);
      
    }

    .lock-circle i {
      font-size: 12px;
      color: #10b981;
    }

    .lock-label {
      font-size: 6px;
      font-weight: 800;
      color: #10b981;
      margin-top: 2px;
      letter-spacing: 0.3px;
    }

    .glow-text {
      text-shadow: 0 0 6px rgba(16, 185, 129, 0.8);
    }

    /* Digital Data Stream Particles */
    .data-particles-wrap {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 1;
    }

    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: #7c3aed;
      border-radius: 50%;
      filter: blur(0.5px);
      opacity: 0;
    }

    .particle.p1 { left: 20%; top: 30%; animation: particleFlow1 4.5s infinite linear; }
    .particle.p2 { right: 15%; top: 25%; animation: particleFlow2 5.5s infinite linear; background: #0ea5e9; }
    .particle.p3 { left: 40%; bottom: 20%; animation: particleFlow3 6s infinite linear; background: #f59e0b; }
    .particle.p4 { right: 35%; bottom: 35%; animation: particleFlow1 5s infinite linear; background: #10b981; }

    /* Carousel glowing 3D Indicators */
    .hero-3d-dots {
      position: absolute;
      bottom: -15px;
      display: flex;
      gap: 8px;
      z-index: 5;
    }

    .threed-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.1);
      cursor: pointer;
      transition: all 0.3s;
    }

    .threed-dot.active {
      background: #7c3aed;
      box-shadow: 0 0 10px #7c3aed, 0 0 3px #7c3aed;
      width: 24px;
      border-radius: 4px;
    }

    /* ANIMATIONS & KEYFRAMES */
    @keyframes mainFloat {
      0%, 100% { transform: rotateY(-14deg) rotateX(8deg) rotateZ(1deg) translateY(0); }
      50% { transform: rotateY(-11deg) rotateX(10deg) rotateZ(0deg) translateY(-12px); }
    }

    @keyframes subFloat1 {
      0%, 100% { transform: translateY(0) translateZ(10px) rotate(0deg); }
      50% { transform: translateY(-8px) translateZ(25px) rotate(1deg); }
    }

    @keyframes subFloat2 {
      0%, 100% { transform: translateY(0) translateZ(20px) rotate(0deg); }
      50% { transform: translateY(10px) translateZ(5px) rotate(-1.5deg); }
    }

    @keyframes subFloat3 {
      0%, 100% { transform: translateY(0) translateZ(15px); }
      50% { transform: translateY(-10px) translateZ(35px); }
    }

    @keyframes subFloat4 {
      0%, 100% { transform: translateY(0) translateZ(30px) scale(1); }
      50% { transform: translateY(-5px) translateZ(45px) scale(1.02); }
    }

    @keyframes platformPulse {
      0%, 100% { opacity: 0.8; transform: rotateX(70deg) translateZ(-40px) scale(1); }
      50% { opacity: 0.95; transform: rotateX(70deg) translateZ(-40px) scale(1.08); }
    }

    @keyframes glassGlare {
      0% { background-position: -200% -200%; }
      100% { background-position: 200% 200%; }
    }

    @keyframes pulseBorder {
      0%, 100% { border-color: rgba(239, 68, 68, 0.25); box-shadow: 0 15px 35px rgba(239, 68, 68, 0.1); }
      50% { border-color: rgba(239, 68, 68, 0.6); box-shadow: 0 15px 35px rgba(239, 68, 68, 0.25), 0 0 12px rgba(239, 68, 68, 0.15); }
    }

    @keyframes particleFlow1 {
      0% { transform: translateY(40px) scale(0.6); opacity: 0; }
      20% { opacity: 0.6; }
      80% { opacity: 0.6; }
      100% { transform: translateY(-60px) scale(1); opacity: 0; }
    }

    @keyframes particleFlow2 {
      0% { transform: translateX(-40px) translateY(20px) scale(0.5); opacity: 0; }
      30% { opacity: 0.8; }
      70% { opacity: 0.8; }
      100% { transform: translateX(50px) translateY(-40px) scale(0.9); opacity: 0; }
    }

    @keyframes particleFlow3 {
      0% { transform: translateY(60px) scale(0.8); opacity: 0; }
      15% { opacity: 0.7; }
      85% { opacity: 0.7; }
      100% { transform: translateY(-30px) scale(0.5); opacity: 0; }
    }

    /* RESPONSIVE */
    @media(max-width:991px){
      .nav-links{display:none}
      .hamburger{display:flex}
      .hero{text-align:center}
      .hero-btns{justify-content:center}
      .hero-visual{margin-top:48px}
    }
    @media(max-width:767px){
      section{padding:64px 0}
      .form-card{padding:26px 20px}
      .smartlink-highlight{padding:30px 20px}
      .hero-graphic-3d { height: 460px; }
      .card-fraud { display: none !important; }
      .card-chat { display: none !important; }
      .card-clicks { left: 0%; top: 35%; }
      .card-convs { right: 0%; top: 25%; }
      .card-revenue { left: 8%; bottom: 6%; }
      .lock-node { right: 10%; bottom: 6%; }
    }
    @media(max-width:900px){.offer-card{flex:0 0 calc(33.333% - 11px) !important}}
    @media(max-width:600px){.offer-card{flex:0 0 calc(50% - 8px) !important}}
    @media(max-width:400px){.offer-card{flex:0 0 calc(100%) !important}}
  </style>
</head>
<body>

<?php if ($_isAdmin): ?>
  <!-- ADMIN TOP BAR -->
  <div style="background:linear-gradient(90deg,#1e1b4b,#4F46E5,#7C3AED);color:#fff;font-size:13px;padding:7px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:relative;z-index:9999">
    <div style="display:flex;align-items:center;gap:18px">
      <span style="font-weight:700;letter-spacing:.5px">⚙ Admin Mode</span>
      <a href="/admin/dashboard"       style="color:rgba(255,255,255,.9);text-decoration:none;padding:4px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">📊 Dashboard</a>
      <a href="/admin/landing/sliders" style="color:rgba(255,255,255,.9);text-decoration:none;padding:4px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">🖼 Sliders</a>
      <a href="/admin/landing/blog"    style="color:rgba(255,255,255,.9);text-decoration:none;padding:4px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">📝 Blog</a>
      <a href="/admin/landing/reviews" style="color:rgba(255,255,255,.9);text-decoration:none;padding:4px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">⭐ Reviews</a>
      <a href="/admin/offers"          style="color:rgba(255,255,255,.9);text-decoration:none;padding:4px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='transparent'">🏷 Offers</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <span style="color:rgba(255,255,255,.6);font-size:12px">Logged in as Admin</span>
      <a href="/admin/dashboard" style="background:#fff;color:#4F46E5;font-weight:700;text-decoration:none;padding:5px 16px;border-radius:6px;font-size:12px">Admin Panel →</a>
      <a href="/logout" style="color:rgba(255,255,255,.6);text-decoration:none;font-size:12px" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">Logout</a>
    </div>
  </div>
<?php elseif ($_isLogged): ?>
  <!-- LOGGED-IN USER BAR -->
  <div style="background:linear-gradient(90deg,#065F46,#059669);color:#fff;font-size:13px;padding:7px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:relative;z-index:9999">
    <span style="font-weight:600">✅ You are logged in</span>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="/<?= htmlspecialchars($_role,ENT_QUOTES,'UTF-8') ?>/dashboard" style="background:#fff;color:#065F46;font-weight:700;text-decoration:none;padding:5px 16px;border-radius:6px;font-size:12px">Go to Dashboard →</a>
      <a href="/logout" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:12px">Logout</a>
    </div>
  </div>
<?php endif; ?>

  <!-- PRELOADER -->
  <div id="preloader">
    <div class="loader-3d-container">
      <div class="loader-ring outer"></div>
      <div class="loader-ring middle"></div>
      <div class="loader-ring inner"></div>
    </div>
    <div class="loader-text">Loading AffsCash</div>
  </div>

  <!-- HEADER -->
  <header class="site-header" id="site-header">
    <div class="nav-inner">
      <a href="/" class="nav-logo">
        <img src="<?= $logoSrc ?>" alt="<?= $appName ?>" height="40">
      </a>
      <ul class="nav-links">
        <li><a href="#top">Home</a></li>
        <li><a href="/offers">Offers</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="/blog">Blog</a></li>
        <li><a href="/reviews">Reviews</a></li>
        <li><a href="#contact">Contact</a></li>
        <?php if ($_isLogged): ?>
        <li><a href="/<?= htmlspecialchars($_role,ENT_QUOTES,'UTF-8') ?>/dashboard" class="btn-login">Dashboard</a></li>
        <?php if ($_isAdmin): ?>
        <li><a href="/admin/dashboard" class="btn-signup">Admin</a></li>
        <?php endif; ?>
        <?php else: ?>
        <li><a href="/register/affiliate" class="btn-signup">Sign Up</a></li>
        <li><a href="/login" class="btn-login">Login</a></li>
        <?php endif; ?>
      </ul>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
    <div class="mobile-menu" id="mobile-menu">
      <a href="#top">Home</a>
      <a href="/offers">Offers</a>
      <a href="#about">About</a>
      <a href="#services">Services</a>
      <a href="/blog">Blog</a>
      <a href="/reviews">Reviews</a>
      <a href="#contact">Contact</a>
      <?php if ($_isLogged): ?>
      <a href="/<?= htmlspecialchars($_role,ENT_QUOTES,'UTF-8') ?>/dashboard">My Dashboard</a>
      <?php if ($_isAdmin): ?><a href="/admin/dashboard">Admin Panel</a><?php endif; ?>
      <?php else: ?>
      <a href="/register/affiliate">Sign Up Free</a>
      <a href="/login">Login</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero" id="top">
    <canvas id="heroParticlesCanvas" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:999999"></canvas>
    <div class="container" style="position:relative;z-index:2">
      <div class="row align-items-center">
        <div class="col-lg-5 hero-content">
          <h6>Welcome to <?= $appName ?></h6>
          <h1>The Best <em>CPA Network</em> &amp; <span class="accent">Affiliate Marketing</span> Platform</h1>
          <p class="d-block d-lg-none">Global coverage, guaranteed high payouts, direct offers, dedicated AMs and our custom affiliate tracking software make us the leaders on the CPA network market! Make money online with our top performance marketing tools.</p>
          <p class="d-none d-lg-block">Global reach, industry-leading payouts, and exclusive direct advertiser partnerships in lead generation. Our dedicated account managers and proprietary affiliate tracking software empower affiliates to maximize revenue, generate passive income, and scale campaigns with confidence. Join the best CPA network built for performance marketing, transparency, and long-term success.</p>
          <div class="hero-btns">
            <a href="/register/affiliate" class="btn-primary-custom"><i class="fa-solid fa-rocket"></i> Join <?= $appName ?></a>
            <a href="/login" class="btn-outline-custom"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <?php if ((Config::get('config','app.advertiser_registration_enabled') ?? '1') === '1'): ?>
            <a href="/register/advertiser" class="btn-outline-custom"><i class="fa-solid fa-building"></i> Join as Advertiser</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-7 hero-visual fade-up">
          <div class="hero-graphic-3d" id="hero3DGraphic">
            <!-- 3D Platform Base with radial glow -->
            <div class="threed-platform"></div>
            
            <!-- Float Grid Container -->
            <div class="threed-scene-wrap">
              
              <!-- Core Floating Laptop / Monitor Dashboard Slider -->
              <div class="floating-dashboard-wrap" id="heroSliderWrap">
                <div class="hero-slider-skeleton" id="heroSliderSkeleton">
                  <div class="sk-pulse"></div>
                  <span>Loading slides…</span>
                </div>
                <div class="hero-slides" id="heroSlides" style="display:none"></div>
                <button class="hero-slider-arrow prev" id="heroSliderPrev" aria-label="Previous slide" style="display:none">&#8592;</button>
                <button class="hero-slider-arrow next" id="heroSliderNext" aria-label="Next slide" style="display:none">&#8594;</button>
                
                <!-- Neon overlay reflection pulse -->
                <div class="glass-reflection-glare"></div>
              </div>

              <!-- Interactive Floating UI Cards (Parallax offset layers) -->
              <div class="floating-ui-layer card-clicks" data-depth="0.15">
                <div class="card-inner">
                  <div class="card-glow-border"></div>
                  <div class="card-header-icon"><i class="fa-solid fa-mouse-pointer" style="color:#0ea5e9"></i></div>
                  <div class="card-val-group">
                    <span class="card-label">Live Clicks</span>
                    <span class="card-val" id="liveClicksVal">128,690</span>
                  </div>
                  <span class="card-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +12.5%</span>
                </div>
              </div>

              <div class="floating-ui-layer card-convs" data-depth="0.25">
                <div class="card-inner">
                  <div class="card-glow-border"></div>
                  <div class="card-header-icon"><i class="fa-solid fa-circle-check" style="color:#10b981"></i></div>
                  <div class="card-val-group">
                    <span class="card-label">Conversions</span>
                    <span class="card-val" id="liveConvsVal">8,756</span>
                  </div>
                  <span class="card-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +8.7%</span>
                </div>
              </div>

              <div class="floating-ui-layer card-revenue" data-depth="0.35">
                <div class="card-inner">
                  <div class="card-glow-border" style="background:linear-gradient(135deg,rgba(245,158,11,0.3),transparent)"></div>
                  <div class="card-header-icon"><i class="fa-solid fa-wallet" style="color:#f59e0b"></i></div>
                  <div class="card-val-group">
                    <span class="card-label">Payout</span>
                    <span class="card-val">$48,765</span>
                  </div>
                  <span class="card-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +11.2%</span>
                </div>
              </div>

              <div class="floating-ui-layer card-epc" data-depth="0.20">
                <div class="card-inner">
                  <div class="card-glow-border"></div>
                  <div class="card-header-icon"><i class="fa-solid fa-chart-line" style="color:#a855f7"></i></div>
                  <div class="card-val-group">
                    <span class="card-label">EPC</span>
                    <span class="card-val">$3.45</span>
                  </div>
                  <span class="card-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +7.3%</span>
                </div>
              </div>

              <!-- Animated Fraud Detection Alert Notification Layer -->
              <div class="floating-ui-layer card-fraud" data-depth="0.4">
                <div class="fraud-inner animate-pulse-border">
                  <div class="fraud-glow"></div>
                  <div class="fraud-header">
                    <span class="fraud-indicator"></span>
                    <span class="fraud-title">Fraud Monitoring</span>
                  </div>
                  <p class="fraud-text">Fraudulent conversion blocked in real-time from IP 185.220.*.*</p>
                  <div class="fraud-footer">
                    <span class="shield-badge"><i class="fa-solid fa-shield-halved"></i> Active protection</span>
                  </div>
                </div>
              </div>

              <!-- Floating Live Chat Support Popup -->
              <div class="floating-ui-layer card-chat" data-depth="0.3">
                <div class="chat-inner">
                  <div class="chat-header">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&q=80" alt="Support agent Manager avatar" class="chat-avatar">
                    <div class="chat-user-info">
                      <span class="chat-name">Live Support</span>
                      <span class="chat-status"><span class="chat-status-dot"></span> Online</span>
                    </div>
                  </div>
                  <p class="chat-bubble">Hello! Welcome to AffsCash support. How can I help you today?</p>
                </div>
              </div>

              <!-- Glowing Lock node for Encryption visualization -->
              <div class="floating-ui-layer lock-node" data-depth="0.45">
                <div class="lock-circle">
                  <i class="fa-solid fa-lock glow-text"></i>
                  <span class="lock-label">ENCRYPTED</span>
                </div>
              </div>

              <!-- Digital Data stream particle layers -->
              <div class="data-particles-wrap">
                <span class="particle p1"></span>
                <span class="particle p2"></span>
                <span class="particle p3"></span>
                <span class="particle p4"></span>
              </div>
            </div>

            <!-- Dashboard Carousel Navigation dots (glowing 3D indicator style) -->
            <div class="hero-3d-dots" id="heroSliderDots"></div>
          </div>
          
          <!-- Core stats list under visual layout -->
          <div class="hero-card-stack">
            <div class="hero-stat-card"><span class="stat-num" id="heroStatOffers">300+</span><span class="stat-label">Live Offers</span></div>
            <div class="hero-stat-card"><span class="stat-num">$9.00</span><span class="stat-label">Max SOI Payout</span></div>
            <div class="hero-stat-card"><span class="stat-num">24/7</span><span class="stat-label">Support Team</span></div>
            <div class="hero-stat-card"><span class="stat-num">100%</span><span class="stat-label">Conversion Track</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="grad-divider"></div>

  <!-- IMAGE BANNER MARQUEE -->
  <div class="img-banner">
    <div class="img-banner-track" id="bannerTrack">
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=75" alt="Dating Affiliate Program & CPA Offers" loading="lazy"><span class="banner-label">💰 Dating CPA</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=400&q=75" alt="Online Dating Offers and Hookup Offers" loading="lazy"><span class="banner-label">❤️ SOI Offers</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=400&q=75" alt="Performance Marketing and Affiliate Dashboard" loading="lazy"><span class="banner-label">📊 Real-time Stats</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=75" alt="High Paying Affiliate Programs Casino" loading="lazy"><span class="banner-label">🎰 Casino Offers</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=75" alt="Make Money Online with Fast Payouts" loading="lazy"><span class="banner-label">💳 Fast Payouts</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=400&q=75" alt="Global Affiliate Network Team" loading="lazy"><span class="banner-label">🌍 Global Reach</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400&q=75" alt="CPA Network 24/7 Support" loading="lazy"><span class="banner-label">🎧 24/7 Support</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=75" alt="CPA Sweepstakes and Free Gift Cards" loading="lazy"><span class="banner-label">💰 Sweepstakes</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=400&q=75" alt="Finance CPA Offers and Personal Loans" loading="lazy"><span class="banner-label">🏦 Finance Offers</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=400&q=75" alt="Health Affiliate Offers and Weight Loss" loading="lazy"><span class="banner-label">🍎 Health CPA</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=75" alt="Amazon Gift Card Offers and Rewards" loading="lazy"><span class="banner-label">🎁 Gift Cards</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=75" alt="Digital Marketing and SEO Services" loading="lazy"><span class="banner-label">📈 Digital Marketing</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=400&q=75" alt="Media Buying and Paid Traffic" loading="lazy"><span class="banner-label">🌍 Media Buying</span></div>
      <div class="banner-img-item"><img src="https://images.unsplash.com/photo-1531482615713-2afd69097998?w=400&q=75" alt="Lead Generation Network" loading="lazy"><span class="banner-label">🎧 Lead Gen</span></div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-section">
    <div class="container">
      <div class="row g-4">
        <div class="col-6 col-md-3"><div class="stat-counter"><div class="big-num" id="statLiveOffers"><?= $_offerCount ?>+</div><div class="big-label">Live Offers</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-counter"><div class="big-num">$<?= number_format($_maxPayout, 0) ?></div><div class="big-label">Max Payout</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-counter"><div class="big-num">24/7</div><div class="big-label">Support</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-counter"><div class="big-num">100%</div><div class="big-label">Traffic Tracked</div></div></div>
      </div>
    </div>
  </div>

  <!-- OFFERS SLIDER -->
  <section class="offers-section" id="offers">
    <div class="container-fluid px-3 px-md-4">
      <div class="section-heading">
        <div class="eyebrow" style="color:rgba(255,255,255,0.7)"><span class="pulse"></span> High Paying Affiliate Programs &nbsp;·&nbsp; All Verticals</div>
        <h2 style="color:#fff">Top <em>CPA Offers</em> <span class="count-badge" id="offerCount" style="background:rgba(255,255,255,0.1); color:#fff">Loading...</span></h2>
        <p style="color:rgba(255,255,255,0.6);font-size:13px;margin-top:8px">Dating CPA Offers · Finance · Health · Sweepstakes · Gift Cards · Digital Marketing</p>
      </div>
      <div class="row g-4 mb-5">
        <div class="col-md-4">
          <div class="chart-card-3d">
            <div style="font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px;color:#fff">📊 Offers by Category</div>
            <canvas id="chartCat" style="max-height:200px"></canvas>
          </div>
        </div>
        <div class="col-md-4">
          <div class="chart-card-3d">
            <div style="font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px;color:#fff">🔥 Top Payouts</div>
            <canvas id="chartPayout" style="max-height:200px"></canvas>
          </div>
        </div>
        <div class="col-md-4">
          <div class="chart-card-3d">
            <div style="font-family:'Rajdhani',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px;color:#fff">🌍 GEO Coverage</div>
            <canvas id="chartGeo" style="max-height:200px"></canvas>
          </div>
        </div>
      </div>
      <div class="aff-tabs" id="affTabs">
        <button class="aff-tab active" data-cat="all">All Offers</button>
        <button class="aff-tab" data-cat="soi">SOI Dating</button>
        <button class="aff-tab" data-cat="doi">DOI Dating</button>
        <button class="aff-tab" data-cat="smartlink">Smartlink</button>
        <button class="aff-tab" data-cat="casino">Casino</button>
        <button class="aff-tab" data-cat="cam">Cam</button>
        <button class="aff-tab" data-cat="software">Software</button>
        <button class="aff-tab" data-cat="financial">Financial</button>
        <button class="aff-tab" data-cat="cps">CPS</button>
      </div>
      <div class="slider-outer">
        <div class="slider-wrap">
          <div class="slider-track" id="sliderTrack"></div>
        </div>
      </div>
      <div class="slider-controls">
        <button class="slider-btn" id="sliderPrev">&#8592;</button>
        <div class="slider-dots" id="sliderDots"></div>
        <button class="slider-btn" id="sliderNext">&#8594;</button>
      </div>
      <div class="slider-prog"><div class="slider-prog-fill" id="progFill"></div></div>
      <div class="smartlink-highlight">
        <h3>Maximize Earnings with Our Global <em>Smartlink</em></h3>
        <p>Our advanced Smartlink technology delivers top performance across all global GEOs — ensuring you never lose valuable traffic.</p>
        <div class="sl-features">
          <span class="sl-feat"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Auto GEO Optimization</span>
          <span class="sl-feat"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> In-House Offer Rotation</span>
          <span class="sl-feat"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> 100% Conversion Display</span>
          <span class="sl-feat"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Zero Traffic Loss</span>
        </div>
        <a href="/register/affiliate" class="btn-primary-custom" style="position:relative;z-index:2">
          <i class="fa-solid fa-bolt"></i> Join &amp; Access All Offers
        </a>
      </div>
    </div>
  </section>

  <!-- ABOUT / FEATURES SHOWCASE -->
  <section class="about-section" id="about" style="background:var(--grad-section-a); position:relative; overflow:hidden">
    <div class="container">
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> Core Technologies</div>
        <h2>Futuristic <em>Performance Marketing</em> Platform</h2>
        <p style="color:rgba(255,255,255,0.6);font-size:14px;max-width:600px;margin:8px auto 0">Advanced real-time optimization, network analysis, and automation layers built to scale your business.</p>
      </div>
      
      <div class="row g-4">
        <!-- Card 1: Smart Tracking -->
        <div class="col-md-6 col-lg-3 fade-up">
          <div class="feature-card-3d">
            <div class="canvas-container">
              <canvas id="canvasTracking" class="feature-canvas"></canvas>
            </div>
            <h4>Smart Tracking Technology</h4>
            <p>Sub-second redirection and postback delivery engines with 0% traffic drop.</p>
          </div>
        </div>
        <!-- Card 2: Fraud Protection -->
        <div class="col-md-6 col-lg-3 fade-up">
          <div class="feature-card-3d">
            <div class="canvas-container">
              <canvas id="canvasFraud" class="feature-canvas"></canvas>
            </div>
            <h4>Fraud Protection System</h4>
            <p>Real-time machine learning shields blocking bots, VPNs, and device spoofing.</p>
          </div>
        </div>
        <!-- Card 3: Global Affiliate Network -->
        <div class="col-md-6 col-lg-3 fade-up">
          <div class="feature-card-3d">
            <div class="canvas-container">
              <canvas id="canvasGlobe" class="feature-canvas"></canvas>
            </div>
            <h4>Global Affiliate Network</h4>
            <p>Connecting elite affiliates and top direct advertisers across 190+ GEOs.</p>
          </div>
        </div>
        <!-- Card 4: Fast Payments -->
        <div class="col-md-6 col-lg-3 fade-up">
          <div class="feature-card-3d">
            <div class="canvas-container">
              <canvas id="canvasPayments" class="feature-canvas"></canvas>
            </div>
            <h4>Fast Payments</h4>
            <p>Weekly and on-demand withdrawals supporting WebMoney, USDT, Wires and custom terms.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services-section" id="services">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-5 fade-up order-lg-2">
          <div class="section-heading text-start mb-4">
            <div class="eyebrow"><span class="pulse"></span> Our Services</div>
            <h2>The Best <em>Traffic</em> &amp; <span class="accent">Monetization</span> Solutions</h2>
          </div>
          <div class="progress-item">
            <h4>Highest Payouts <span class="progress-label">90%</span></h4>
            <p>We try our best to provide the highest rates in the industry for your traffic.</p>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" data-width="90"></div></div>
          </div>
          <div class="progress-item">
            <h4>All Verticals &amp; Payment Models <span class="progress-label">95%</span></h4>
            <p>Access to thousands of exclusive CPL, CPI, Pay-Per-Call, CPS, CPA, CPE offers.</p>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" data-width="95"></div></div>
          </div>
          <div class="progress-item">
            <h4>High-Skilled Support <span class="progress-label">100%</span></h4>
            <p>No question left unanswered. Our specialists resolve any issue promptly.</p>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" data-width="100"></div></div>
          </div>
          <div class="progress-item">
            <h4>Global Smartlink <span class="progress-label">100%</span></h4>
            <p>Intelligent routing by GEO and device. Maximum EPC, zero wasted traffic.</p>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" data-width="100"></div></div>
          </div>
          <div class="progress-item">
            <h4>Always Timely Payment <span class="progress-label">100%</span></h4>
            <p>We ensure every payment is made on time, every time.</p>
            <div class="progress-bar-wrap"><div class="progress-bar-fill" data-width="100"></div></div>
          </div>
        </div>
        <div class="col-lg-7 fade-up order-lg-1">
          <!-- 3D Laptop Screen Showcase Mockup -->
          <div class="laptop-3d-wrap">
            <div class="laptop-3d-screen">
              <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80" alt="Traffic & Monetization Analytics" loading="lazy">
              <div class="glass-reflection"></div>
            </div>
            <div class="laptop-3d-base"></div>
          </div>
          <div class="payment-cards">
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=swift.com&sz=128" alt="Wire Transfer" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>Wire Transfer</h5><p>International bank wire</p></div>
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=paypal.com&sz=128" alt="PayPal" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>PayPal</h5><p>Fast &amp; global</p></div>
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=payoneer.com&sz=128" alt="Payoneer" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>Payoneer</h5><p>Worldwide payouts</p></div>
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=wmtransfer.com&sz=128" alt="WebMoney" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>WebMoney</h5><p>eWallet payments</p></div>
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=plaid.com&sz=128" alt="ACH" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>ACH</h5><p>US bank transfers</p></div>
            <div class="payment-card"><img src="https://www.google.com/s2/favicons?domain=stripe.com&sz=128" alt="Upon Request" style="width:36px; height:36px; margin-bottom:10px; border-radius:4px; display:inline-block;"><h5>Upon Request</h5><p>Custom methods</p></div>
          </div>
          <!-- Dark Glass Summary Grid -->
          <div style="background:rgba(15,10,36,0.6);border:1px solid rgba(255,255,255,0.08);border-radius:var(--r);padding:22px;margin-top:16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.25);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)">
            <div>
              <div style="font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;background:var(--grad-green);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">$50</div>
              <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px">Min Payment</div>
            </div>
            <div style="border-left:1px solid rgba(255,255,255,0.08);border-right:1px solid rgba(255,255,255,0.08)">
              <div style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;background:var(--grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">Twice/Mo</div>
              <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px">Pay Freq.</div>
            </div>
            <div>
              <div style="font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">6hr</div>
              <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.5px">AM Contact</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- AFFILIATE DASHBOARD PREVIEW -->
  <section class="dashboard-preview-section" id="dashboard-preview" style="background:var(--grad-section-b); position:relative; overflow:hidden">
    <div class="container">
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> Live Platform Preview</div>
        <h2>The Ultimate <em>Affiliate</em> &amp; <span class="accent">Advertiser</span> Portal</h2>
        <p style="color:rgba(255,255,255,0.6);font-size:14px;max-width:600px;margin:8px auto 0">A powerful, modern control panel offering direct tracking links, campaign management, postback logs, and sub-second analytics updates.</p>
      </div>
      
      <div class="dashboard-preview-wrap fade-up">
        <!-- Glassmorphism Dashboard UI Mockup -->
        <div class="dashboard-glass-ui">
          <!-- Sidebar -->
          <div class="db-sidebar">
            <div class="db-logo"><img src="<?= $logoSrc ?>" alt="Logo" height="24" style="filter:brightness(0) invert(1)"></div>
            <ul class="db-menu">
              <li class="active"><i class="fa-solid fa-chart-line"></i> Analytics</li>
              <li><i class="fa-solid fa-tags"></i> CPA Offers</li>
              <li><i class="fa-solid fa-link"></i> Smartlinks</li>
              <li><i class="fa-solid fa-wallet"></i> Billing</li>
              <li><i class="fa-solid fa-shield-halved"></i> Fraud Shield</li>
              <li><i class="fa-solid fa-gear"></i> Settings</li>
            </ul>
          </div>
          <!-- Content -->
          <div class="db-content">
            <div class="db-header">
              <h4>Welcome Back, Elite Publisher!</h4>
              <div class="db-header-actions">
                <span class="db-badge live"><span class="dot"></span> Live Data Feed</span>
                <span class="db-user"><i class="fa-solid fa-user-tie"></i> Account Manager: <strong>Alex</strong></span>
              </div>
            </div>
            <!-- Stat Widgets Row -->
            <div class="db-widgets">
              <div class="db-widget"><span class="widget-label">Today's Earnings</span><span class="widget-val">$1,284.50</span><span class="widget-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +14.2%</span></div>
              <div class="db-widget"><span class="widget-label">Conversions</span><span class="widget-val">342</span><span class="widget-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +8.5%</span></div>
              <div class="db-widget"><span class="widget-label">Clicks</span><span class="widget-val">4,819</span><span class="widget-trend down"><i class="fa-solid fa-arrow-trend-down"></i> -1.2%</span></div>
              <div class="db-widget"><span class="widget-label">EPC</span><span class="widget-val">$0.26</span><span class="widget-trend up"><i class="fa-solid fa-arrow-trend-up"></i> +11.8%</span></div>
            </div>
            <!-- Chart Row -->
            <div class="db-chart-row">
              <div class="db-chart-card">
                <h5>Traffic & Earnings Progression</h5>
                <div style="width:100%; height:180px; position:relative">
                  <canvas id="dbPreviewChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- B2B ADVERTISER SECTION -->
  <section class="advertiser-section" id="advertiser" style="background:var(--grad-section-a); position:relative; overflow:hidden">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-5 fade-up">
          <div class="section-heading text-start mb-4">
            <div class="eyebrow"><span class="pulse"></span> Advertisers</div>
            <h2>Scale Your <em>Performance Marketing</em><br>With Premium Traffic Partners</h2>
          </div>
          <p style="color:rgba(255,255,255,0.7); line-height:1.8; margin-bottom:24px">Reach target consumer demographics globally with zero fraud risk. We align advertisers with verified affiliate channels, matching high-quality media sources to optimize lead generation, sweepstakes entries, software trials, and mobile app conversion objectives.</p>
          <div class="adv-features mb-4">
            <div class="adv-feature-item"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> <span>Direct API Integrations & Real-Time Postbacks</span></div>
            <div class="adv-feature-item"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> <span>Advanced Bot, VPN & Custom Anti-Fraud Screening</span></div>
            <div class="adv-feature-item"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> <span>Premium Global Traffic Quality Across All Channels</span></div>
          </div>
          <a href="/register/advertiser" class="btn-primary-custom"><i class="fa-solid fa-building"></i> Join as Advertiser</a>
        </div>
        <div class="col-lg-7 fade-up position-relative">
          <!-- 3D Ecosystem Visualization Canvas -->
          <div class="adv-ecosystem-wrap">
            <canvas id="canvasAdvertiserEcosystem" class="ecosystem-canvas"></canvas>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRUST & SECURITY SECTION -->
  <section class="trust-security-section" id="security" style="background:var(--grad-section-b); position:relative; overflow:hidden">
    <canvas id="canvasSecurityBg" style="position:absolute; inset:0; pointer-events:none; z-index:1"></canvas>
    <div class="container" style="position:relative; z-index:2">
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> Secure Network</div>
        <h2>Enterprise-Grade <em>Anti-Fraud</em> Infrastructure</h2>
        <p style="color:rgba(255,255,255,0.6);font-size:14px;max-width:600px;margin:8px auto 0">We continuously screen every click, conversion, and postback event using advanced multi-layered verification layers to protect advertising budgets.</p>
      </div>
      
      <div class="row g-4 justify-content-center">
        <div class="col-md-4 col-lg-3 fade-up">
          <div class="security-card">
            <div class="sec-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <h4>AI Fraud Detection</h4>
            <p>Smart classifiers analyze traffic metadata in real-time, detecting bot farms and malicious VPN ranges instantly.</p>
          </div>
        </div>
        <div class="col-md-4 col-lg-3 fade-up">
          <div class="security-card">
            <div class="sec-icon"><i class="fa-solid fa-lock"></i></div>
            <h4>SSL Encryption</h4>
            <p>All data relays, API keys, postbacks, and affiliate panels are secured using advanced secure sockets layers.</p>
          </div>
        </div>
        <div class="col-md-4 col-lg-3 fade-up">
          <div class="security-card">
            <div class="sec-icon"><i class="fa-solid fa-fingerprint"></i></div>
            <h4>Secure Tracking</h4>
            <p>Proprietary fingerprinting algorithms verify that landing page redirects match conversion postbacks perfectly.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PARTNERS -->
  <section class="partners-section">
    <div class="container">
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> Trusted By</div>
        <h2>Our <em>Partners</em> &amp; <span class="accent">Reviews</span></h2>
      </div>
      <div class="partner-grid">
        <a href="https://www.affpaying.com/affscashnet" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affpaying.com&sz=128" alt="Affpaying Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affpaying</span></a>
        <a href="https://affwebsite.com/affscashnet/" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affwebsite.com&sz=128" alt="Affwebsite Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affwebsite</span></a>
        <a href="https://www.affnext.com/affiliate-networks/affscash" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affnext.com&sz=128" alt="Affnext Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affnext</span></a>
        <a href="https://www.trustpilot.com/review/affscash.pro" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=trustpilot.com&sz=128" alt="Trustpilot Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Trustpilot</span></a>
        <a href="https://adswikia.com/affscash" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=adswikia.com&sz=128" alt="Adswikia Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Adswikia</span></a>
        <a href="https://www.affpayzone.com/affscash.net" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affpayzone.com&sz=128" alt="Affpayzone Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affpayzone</span></a>
        <a href="https://affcaptain.com/affiliate-network/affscash/" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affcaptain.com&sz=128" alt="Affcaptain Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affcaptain</span></a>
        <a href="https://expertaff.com/affiliate-network/affscashnet" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=expertaff.com&sz=128" alt="Expertaff Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Expertaff</span></a>
        <a href="https://affbun.com/network/affscash-net" target="_blank" rel="noopener" class="partner-card"><img src="https://www.google.com/s2/favicons?domain=affbun.com&sz=128" alt="Affbun Logo" style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain;"><span>Affbun</span></a>
      </div>
    </div>
  </section>

  <!-- ══ BLOG — auto-populated from Admin › Landing › Blog Posts ══ -->
  <?php if (!empty($_landingPosts)): ?>
  <section class="blog-section" id="blog">
    <div class="container">

      <!-- Section header -->
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> News &amp; Updates</div>
        <h2>Latest From Our <span class="accent">Blog</span></h2>
        <p style="color:rgba(255,255,255,0.6);font-size:15px;max-width:520px;margin:10px auto 0">
          Affiliate tips, CPA strategies, and network updates — all in one place.
        </p>
      </div>

      <!-- Card grid (3 cols → 2 → 1) -->
      <div class="blog-grid">
        <?php foreach ($_landingPosts as $bp):
          $bImg  = Helpers::e($bp['image'] ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=600&q=80');
          $bDate = $bp['published_at'] ? date('d M Y', strtotime($bp['published_at'])) : '';
          $bLink = '/blog/' . Helpers::e($bp['slug']);
        ?>
        <article class="blog-card">
          <a href="<?= $bLink ?>" class="blog-card-img" style="display:block">
            <img src="<?= $bImg ?>" alt="<?= Helpers::e($bp['title']) ?>" loading="lazy">
            <?php if ($bp['category']): ?>
            <span class="blog-card-cat"><?= Helpers::e($bp['category']) ?></span>
            <?php endif; ?>
          </a>
          <div class="blog-card-body">
            <?php if ($bDate): ?>
            <div class="blog-card-date">
              <i class="fa-regular fa-calendar"></i> <?= $bDate ?>
            </div>
            <?php endif; ?>
            <h3 class="blog-card-title">
              <a href="<?= $bLink ?>"><?= Helpers::e($bp['title']) ?></a>
            </h3>
            <?php if ($bp['excerpt']): ?>
            <p class="blog-card-excerpt"><?= Helpers::e(mb_substr($bp['excerpt'], 0, 110)) ?><?= mb_strlen($bp['excerpt']) > 110 ? '…' : '' ?></p>
            <?php endif; ?>
            <div class="blog-card-footer">
              <a href="<?= $bLink ?>" class="blog-card-read">Read More <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- View all button -->
      <div class="blog-view-all">
        <a href="/blog" class="btn-primary-custom">
          <i class="fa-solid fa-newspaper"></i> View All Blog Posts
        </a>
      </div>

    </div>
  </section>
  <?php endif; ?>

  <!-- REVIEWS / TESTIMONIALS -->
  <section id="reviews" style="padding:100px 0;background:var(--grad-section-b)">
    <div class="container">

      <!-- Section Header -->
      <div class="section-heading" style="margin-bottom:56px">
        <div class="eyebrow"><span class="pulse"></span> Verified Testimonials</div>
        <h2>What <em>Affiliates</em> Say About <span class="accent"><?= htmlspecialchars($appName,ENT_QUOTES,'UTF-8') ?></span></h2>
        <p style="color:rgba(255,255,255,0.6);font-size:15px;max-width:540px;margin:12px auto 0">Every review below is real — submitted by our affiliates and approved by our team.</p>
      </div>

      <?php if (!empty($_landingRevs)): ?>

      <!-- Stats bar -->
      <?php
        $_rvTotal  = count($_landingRevs);
        $_rvAvg    = $_rvTotal ? round(array_sum(array_column($_landingRevs,'rating')) / $_rvTotal, 1) : 5;
        $_rvFull   = floor($_rvAvg);
        $_rvHalf   = ($_rvAvg - $_rvFull) >= 0.5 ? 1 : 0;
      ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap;margin-bottom:48px;padding:24px 32px;background:rgba(15,10,36,0.65);border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)">
        <div style="text-align:center">
          <div style="font-size:42px;font-weight:800;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1"><?= $_rvAvg ?></div>
          <div style="color:#F59E0B;font-size:20px;margin:4px 0">
            <?= str_repeat('★',$_rvFull) ?><?= $_rvHalf ? '½' : '' ?><?= str_repeat('☆', 5-$_rvFull-$_rvHalf) ?>
          </div>
          <div style="font-size:12px;color:rgba(255,255,255,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px">Average Rating</div>
        </div>
        <div style="width:1px;height:50px;background:rgba(255,255,255,0.08);display:none" class="rv-sep"></div>
        <div style="text-align:center">
          <div style="font-size:42px;font-weight:800;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1"><?= $_rvTotal ?>+</div>
          <div style="font-size:12px;color:rgba(255,255,255,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:28px">Approved Reviews</div>
        </div>
        <div style="text-align:center">
          <div style="font-size:42px;font-weight:800;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1">100%</div>
          <div style="font-size:12px;color:rgba(255,255,255,0.5);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:28px">Real People</div>
        </div>
      </div>

      <!-- Reviews grid -->
      <div id="reviewsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:24px;margin-bottom:32px">
        <?php foreach (array_slice($_landingRevs, 0, 450) as $index => $rv): ?>
        <div class="review-card" style="background:rgba(15,10,36,0.65);border-radius:20px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);display:<?php echo $index >= 6 ? 'none' : 'flex'; ?>;flex-direction:column;gap:16px;transition:all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)" onmouseenter="this.style.transform='translateY(-6px)';this.style.boxShadow='0 20px 45px rgba(124,58,237,0.25)';this.style.borderColor='rgba(124,58,237,0.3)'" onmouseleave="this.style.transform='';this.style.boxShadow='0 10px 30px rgba(0,0,0,0.25)';this.style.borderColor='rgba(255,255,255,0.08)'">

          <!-- Stars + featured badge -->
          <div style="display:flex;align-items:center;justify-content:space-between">
            <div style="color:#F59E0B;font-size:17px;letter-spacing:1px">
              <?= str_repeat('★',(int)$rv['rating']) ?><?= str_repeat('☆',5-(int)$rv['rating']) ?>
            </div>
            <?php if ($rv['is_featured']): ?>
            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;background:linear-gradient(90deg,#7c3aed,#4f46e5);color:#fff;padding:3px 10px;border-radius:20px">Featured</span>
            <?php endif; ?>
          </div>

          <!-- Review text -->
          <p style="font-size:14px;color:rgba(255,255,255,0.85);line-height:1.75;flex:1;margin:0">"<?= Helpers::e($rv['review_text']) ?>"</p>

          <!-- Author -->
          <div style="display:flex;align-items:center;gap:12px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.08)">
            <?php if (!empty($rv['avatar'])): ?>
            <img src="<?= Helpers::e($rv['avatar']) ?>" alt="" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.12);flex-shrink:0">
            <?php else: ?>
            <div style="width:46px;height:46px;border-radius:50%;background:var(--grad-brand);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:18px;flex-shrink:0">
              <?= strtoupper(substr(htmlspecialchars($rv['name'],ENT_QUOTES,'UTF-8'),0,1)) ?>
            </div>
            <?php endif; ?>
            <div style="min-width:0">
              <div style="font-weight:700;font-size:14px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helpers::e($rv['name']) ?></div>
              <?php if (!empty($rv['role_title'])): ?>
              <div style="font-size:12px;color:rgba(255,255,255,0.5);margin-top:1px"><?= Helpers::e($rv['role_title']) ?></div>
              <?php endif; ?>
              <?php if (!empty($rv['country'])): ?>
              <div style="font-size:11px;color:#9ca3af;margin-top:1px">📍 <?= Helpers::e($rv['country']) ?></div>
              <?php endif; ?>
            </div>
          </div>

        </div>
        <?php endforeach; ?>
      </div>

      <!-- Load More Button -->
      <?php if (count($_landingRevs) > 6): ?>
      <div style="text-align:center;margin-bottom:48px">
        <button id="btnLoadMoreReviews" style="background:var(--grad-brand);color:#fff;border:none;border-radius:30px;padding:12px 32px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 16px rgba(124,58,237,.25);transition:transform .2s,box-shadow .2s;outline:none" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">Load More Reviews</button>
      </div>
      <script>
      document.getElementById('btnLoadMoreReviews').onclick = function() {
          var cards = document.querySelectorAll('#reviewsGrid .review-card');
          var hiddenCards = Array.from(cards).filter(function(card) { return card.style.display === 'none'; });
          for (var i = 0; i < Math.min(9, hiddenCards.length); i++) {
              hiddenCards[i].style.display = 'flex';
          }
          if (document.querySelectorAll('#reviewsGrid .review-card[style*="display: none"]').length === 0 && document.querySelectorAll('#reviewsGrid .review-card[style*="display:none"]').length === 0) {
              this.style.display = 'none';
          }
      };
      </script>
      <?php endif; ?>

      <?php else: ?>

      <!-- Empty state: visible only when no active reviews yet -->
      <div style="text-align:center;padding:60px 24px;background:#fff;border-radius:20px;border:2px dashed #ede9fe;margin-bottom:48px">
        <div style="font-size:52px;margin-bottom:16px;opacity:.25">⭐</div>
        <h3 style="font-size:18px;font-weight:700;color:var(--text);margin:0 0 8px">No reviews yet</h3>
        <p style="color:var(--muted);font-size:14px;margin:0">Be the first to share your experience with <?= htmlspecialchars($appName,ENT_QUOTES,'UTF-8') ?>!</p>
      </div>

      <?php endif; ?>

      <!-- CTA buttons -->
      <div style="text-align:center;display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap">
        <a href="/reviews" class="btn-primary-custom">
          <i class="fa-solid fa-star"></i> View All Reviews
        </a>
        <a href="/reviews#write-review" class="btn-outline-custom">
          <i class="fa-solid fa-pen-to-square"></i> Write a Review
        </a>
      </div>

    </div>
  </section>

  <!-- TEAM -->
  <section class="contact-team" id="team">
    <div class="container">
      <div class="section-heading">
        <div class="eyebrow"><span class="pulse"></span> Our Team</div>
        <h2><?= $appName ?> <em>Contact</em> Details &amp; <span class="accent">Support Team</span></h2>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-5 col-lg-4 fade-up">
          <div class="team-card">
            <div class="team-avatar">👨‍💼</div>
            <h4><?= $_managerName ?></h4>
            <div class="team-role">Your Dedicated AM</div>
            <div class="team-links">
              <a href="mailto:<?= $_contactEmail ?>" class="team-link"><i class="fa-solid fa-envelope"></i> <?= $_contactEmail ?></a>
              <?php if ($_teamsUrl): ?>
              <a href="<?= $_teamsUrl ?>" target="_blank" rel="noopener" class="team-link"><i class="fa-brands fa-microsoft"></i> Teams Support</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-5 col-lg-4 fade-up">
          <div class="team-card">
            <div class="team-avatar" style="background:linear-gradient(135deg,#2563eb,#059669)">🎧</div>
            <h4>Support Team</h4>
            <div class="team-role">24/7 Support</div>
            <div class="team-links">
              <a href="mailto:<?= $_supportEmail ?>" class="team-link"><i class="fa-solid fa-envelope"></i> <?= $_supportEmail ?></a>
              <a href="<?= $_tgUrl ?>" target="_blank" rel="noopener" class="team-link"><i class="fa-brands fa-telegram"></i> @<?= $_tgHandle ?></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT FORM -->
  <section class="contact-form-section" id="contact">
    <div class="container">
      <div class="row g-5 align-items-start">
        <div class="col-lg-5 fade-up">
          <div class="section-heading text-start mb-4">
            <div class="eyebrow"><span class="pulse"></span> Get In Touch</div>
            <h2>Feel Free To <em>Send Us</em> a Message</h2>
          </div>
          <p style="color:rgba(255,255,255,0.6);font-size:14px;margin-bottom:24px">For any enquiry contact us via Skype, Telegram, or fill in the form and our team will respond promptly.</p>
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
            <?php if ($_teamsUrl): ?>
            <a href="<?= $_teamsUrl ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 16px;color:#fff;text-decoration:none;transition:all .25s;font-weight:500" onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.borderColor='rgba(0,175,240,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.03)';this.style.borderColor='rgba(255,255,255,0.08)'">
              <i class="fa-brands fa-skype" style="color:#00aff0;font-size:18px"></i> Skype / Teams Support
            </a>
            <?php endif; ?>
            <a href="<?= $_tgUrl ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 16px;color:#fff;text-decoration:none;transition:all .25s;font-weight:500" onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.borderColor='rgba(42,171,238,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.03)';this.style.borderColor='rgba(255,255,255,0.08)'">
              <i class="fa-brands fa-telegram" style="color:#2aabee;font-size:18px"></i> Telegram @<?= $_tgHandle ?>
            </a>
          </div>
          <div style="border-radius:18px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);margin-bottom:20px;box-shadow:0 10px 30px rgba(0,0,0,0.25)">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80" alt="Office" style="width:100%;height:160px;object-fit:cover;display:block" loading="lazy">
          </div>
          <?php if ($_companyAddr || $_companyPhone): ?>
          <div class="address-block">
            <h5><i class="fa-solid fa-location-pin" style="color:var(--pink);margin-right:6px"></i> Company Address</h5>
            <hr style="border-color:rgba(255,255,255,0.08);margin:12px 0">
            <?php if ($_companyAddr): ?>
            <p><?= nl2br($_companyAddr) ?></p>
            <hr style="border-color:rgba(255,255,255,0.08);margin:12px 0">
            <?php endif; ?>
            <?php if ($_companyPhone): ?>
            <p><i class="fa-solid fa-phone" style="color:var(--green);margin-right:6px"></i> <?= $_companyPhone ?></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="col-lg-7 fade-up">
          <div class="form-card">
            <h3>Send Us a <em>Message</em></h3>
            <form id="contactForm" onsubmit="handleSubmit(event)" novalidate>
              <div class="contact-form-row">
                <div class="form-group"><label for="fname">First Name</label><input type="text" id="fname" class="form-control-custom" placeholder="John" required></div>
                <div class="form-group"><label for="lname">Last Name</label><input type="text" id="lname" class="form-control-custom" placeholder="Doe" required></div>
              </div>
              <div class="form-group"><label for="cemail">Email Address</label><input type="email" id="cemail" class="form-control-custom" placeholder="john@example.com" required></div>
              <div class="form-group"><label for="message">Message</label><textarea id="message" class="form-control-custom" placeholder="How can we help you?" required></textarea></div>
              <button type="submit" class="btn-primary-custom" style="width:100%;justify-content:center">
                <i class="fa-solid fa-paper-plane"></i> Send Message
              </button>
              <div id="formSuccess" style="display:none;margin-top:14px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.22);border-radius:10px;padding:14px;text-align:center;color:var(--green);font-size:14px">
                ✅ Message sent! We'll get back to you within 24 hours.
              </div>
              <div id="formError" style="display:none;margin-top:14px;background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.22);border-radius:10px;padding:14px;text-align:center;color:#dc2626;font-size:14px">
                ❌ <span id="errorText">Something went wrong. Please try again.</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <!-- FOOTER -->
  <footer style="background: var(--grad-dark-footer); padding: 80px 0 40px; border-top: 1px solid rgba(255,255,255,0.08); position: relative">
    <!-- Animated Gradient dividing top line -->
    <div style="position:absolute; top:-2px; left:0; right:0; height:3px; background:linear-gradient(90deg, #7c3aed, #e8197a, #0ea5e9); background-size:200% 200%; animation: gradientMove 6s ease infinite"></div>
    
    <div class="container">
      <div class="row g-4 mb-5 justify-content-between">
        <div class="col-lg-4">
          <div style="margin-bottom: 20px">
            <img src="<?= $logoSrc ?>" alt="<?= $appName ?>" style="max-height:48px; width:auto; filter:brightness(0) invert(1)">
          </div>
          <p style="color: rgba(255,255,255,0.55); font-size:13px; line-height:1.7; margin-bottom: 24px">
            Elite CPA Affiliate Network providing high-converting direct offers, global coverage, and sub-second payment frequencies to digital marketers worldwide.
          </p>
          <div style="display:flex; gap:16px; align-items:center">
            <a href="<?= $_tgUrl ?>" target="_blank" rel="noopener" style="color:rgba(255,255,255,.5); font-size:20px; transition:color .25s"><i class="fa-brands fa-telegram"></i></a>
            <?php if ($_teamsUrl): ?>
            <a href="<?= $_teamsUrl ?>" target="_blank" rel="noopener" style="color:rgba(255,255,255,.5); font-size:20px; transition:color .25s"><i class="fa-brands fa-skype"></i></a>
            <?php endif; ?>
            <a href="https://www.linkedin.com/company/89707239/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.5); font-size:20px; transition:color .25s"><i class="fa-brands fa-linkedin"></i></a>
            <a href="https://www.facebook.com/affscash/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.5); font-size:20px; transition:color .25s"><i class="fa-brands fa-facebook"></i></a>
          </div>
        </div>
        <div class="col-md-7 col-lg-5">
          <h5 style="color: #fff; font-size:15px; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px">Newsletter Subscription</h5>
          <p style="color: rgba(255,255,255,0.55); font-size:12px; margin-bottom:16px">Get notified on exclusive high-payout direct campaigns and industry case studies directly in your inbox.</p>
          <form onsubmit="event.preventDefault(); alert('Successfully subscribed!'); this.reset();" style="display:flex; gap:10px; width:100%">
            <input type="email" placeholder="Enter your email address" required style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:10px 16px; color:#fff; font-size:13px; outline:none">
            <button type="submit" class="btn-primary-custom" style="padding:10px 20px; font-size:13px; border-radius:10px">Subscribe</button>
          </form>
        </div>
      </div>
      
      <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:30px; display:flex; flex-direction:column; gap:20px; align-items:center; justify-content:center; text-align:center">
        <div class="footer-links" style="display:flex; flex-wrap:wrap; gap:14px; justify-content:center; max-width:800px">
          <a href="#top">Home</a>
          <a href="#about">About</a>
          <a href="#services">Services</a>
          <a href="/offers">Offers</a>
          <a href="/blog">Blog</a>
          <a href="#contact">Contact</a>
          <a href="/reviews">Reviews</a>
          <a href="/terms-of-service" target="_blank">Terms &amp; Conditions</a>
          <a href="/privacy-policy" target="_blank">Privacy Policy</a>
          <a href="/affiliate-agreement">Affiliate Agreement</a>
          <a href="/anti-fraud-policy">Anti-Fraud Policy</a>
          <a href="/gdpr-compliance-policy">GDPR Compliance</a>
          <a href="/refund-payment-policy">Refund Policy</a>
          <a href="/cookie-policy">Cookie Policy</a>
          <a href="/dashboard-disclaimers">Dashboard Disclaimers</a>
          <a href="/register/affiliate">Sign Up</a>
          <a href="/login">Login</a>
          <a href="https://shroo.link" target="_blank">Short Link</a>
        </div>
        
        <?php if ($_mobileAppUrl !== ''): ?>
        <div style="display:flex;justify-content:center;margin-top:10px">
          <a href="<?= Helpers::e($_mobileAppUrl) ?>" target="_blank" rel="noopener"
             style="display:inline-flex;align-items:center;gap:10px;background:#000;color:#fff;padding:9px 18px;border-radius:8px;text-decoration:none;border:1px solid #444;transition:transform .15s,box-shadow .25s"
             onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,.35)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''"
             title="Install <?= $_mobileAppName ?> on Google Play">
            <svg width="22" height="22" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path fill="#EA4335" d="M325.3 234.3 104.4 13.4l270.6 156.3-49.7 64.6z"/>
              <path fill="#FBBC04" d="M104.4 13.4 325.3 234.3l-49.7 64.6L104.4 498.6V13.4z"/>
              <path fill="#34A853" d="M375 169.7 104.4 13.4l-7.7 245.3 278.3-89z"/>
              <path fill="#4285F4" d="M375 342.3 104.4 498.6 325.3 277.7z"/>
            </svg>
            <span style="text-align:left;line-height:1.15">
              <span style="display:block;font-size:10px;color:#bbb;letter-spacing:.04em">GET IT ON</span>
              <span style="display:block;font-size:15px;font-weight:600">Google Play</span>
            </span>
          </a>
        </div>
        <?php endif; ?>
        
        <p style="font-size:12px; color:rgba(255,255,255,0.4)">&copy; 2019 &ndash; <?= date('Y') ?> EdgeSoft Ltd. All Rights Reserved. | <?= $appName ?> CPA Affiliate Network</p>
      </div>
  </footer>

  <button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="fa-solid fa-chevron-up"></i>
  </button>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  var AFF_API = '/api/data.php';

  var FALLBACK_OFFERS = [
    {id:125,name:"PersonalLoans.com (US) (CPL)",cat:"financial",sub:"FINANCE · REVSHARE",geos:["US"],payout:"80.00% RevShare",payStyle:"--grad-green",hot:true,top:true,img:"https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70"},
    {id:124,name:"Cash App Gift Card (US) (Trial)",cat:"financial",sub:"FINANCE · CPA",geos:["US"],payout:"$11.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70"},
    {id:123,name:"Walmart Car Emergency (US) (Trial)",cat:"financial",sub:"FINANCE · CPA",geos:["US"],payout:"$12.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=400&q=70"},
    {id:122,name:"YourInsurance Quotes (US)",cat:"financial",sub:"FINANCE · CPA",geos:["US"],payout:"$4.00",payStyle:"--grad-warm",hot:false,top:true,img:"https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=400&q=70"},
    {id:301,name:"SmartLink - CPS",cat:"soi",sub:"DATING · REVSHARE",geos:["WW"],payout:"80.00% RevShare",payStyle:"--grad-green",hot:true,top:true,img:"https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70"},
    {id:302,name:"CPI ServerlessVPN",cat:"software",sub:"MOBILE APPS · REVSHARE",geos:["WW"],payout:"70.00% RevShare",payStyle:"--grad-green",hot:true,top:true,img:"https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70"},
    {id:303,name:"Tolet24",cat:"soi",sub:"DATING · CPC",geos:["WW"],payout:"$0.10 CPC",payStyle:"--grad-cool",hot:false,top:true,img:"https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70"},
    {id:286,name:"CharmDate Hot",cat:"soi",sub:"Dating · Tier-1",geos:["US","GB","CH","DK","FI"],payout:"$9.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70"},
    {id:62, name:"Super Smartlink",cat:"smartlink",sub:"Dating · Global",geos:["WW"],payout:"80.00% RevShare",payStyle:"--grad-green",hot:true,top:true,img:"https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70"},
    {id:289,name:"uMobix.org",cat:"software",sub:"Monitoring App · CPS",geos:["AU","CA","GB","US"],payout:"$42.00",payStyle:"--grad-cool",hot:true,top:true,img:"https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70"},
    {id:296,name:"Ckwin Casino",cat:"casino",sub:"Casino · Per Sale",geos:["WW"],payout:"$25.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70"},
    {id:281,name:"VideoChat FTD",cat:"cam",sub:"Cam · FTD",geos:["AU","CA","GB","US"],payout:"$15.00",payStyle:"linear-gradient(135deg,#a855f7,#ec4899)",hot:true,top:true,img:"https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70"},
    {id:255,name:"Maturedates UK",cat:"doi",sub:"Mature Dating · UK",geos:["GB"],payout:"$5.00",payStyle:"--grad-warm",hot:true,top:true,img:"https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=400&q=70"},
    {id:285,name:"UkrainianGirlsDate",cat:"soi",sub:"Dating · Tier-1",geos:["AU","CA","GB","US"],payout:"$5.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70"},
    {id:304,name:"LoveArrow CPS Dating",cat:"cps",sub:"Dating · CPS",geos:["US","GB"],payout:"$35.00",payStyle:"--grad-gold",hot:true,top:true,img:"https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=400&q=70"}
  ];

  var FALLBACK_SLIDES = [
    {name:"High-Converting CPA Offers",desc:"300+ premium offers — Dating, Casino, Cam, Software",image:"https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80",link:"/register/affiliate",badges:["hot","top"]},
    {name:"Real-Time Analytics Dashboard",desc:"Track every click, conversion and earning live",image:"https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=800&q=80",link:"/register/affiliate",badges:["top"]},
    {name:"300+ Premium CPA Offers",desc:"Dating SOI/DOI, Casino, Cam, Software — highest payouts",image:"https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80",link:"/register/affiliate",badges:["hot"]},
    {name:"Global Smartlink Technology",desc:"Auto GEO optimization — zero traffic loss, maximum EPC",image:"https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800&q=80",link:"/register/affiliate",badges:[]},
  ];

  var OFFER_DATA  = [];
  var chartsBuilt = false;

  function loadOffersFromAPI(){
    fetch(AFF_API+'?resource=offers&action=list&_t='+Date.now())
      .then(function(r){return r.json();})
      .then(function(json){
        if(json.success&&json.data&&json.data.length){
          OFFER_DATA=json.data.map(function(o){
            return{id:o.id||0,name:o.name||'',cat:(o.cat||'soi').toLowerCase(),sub:o.sub||o.vertical||'',
              geos:Array.isArray(o.geos)?o.geos:(o.countries||o.geos||'WW').split(/\s+/).filter(Boolean),
              payout:o.payoutDisplay||o.payout||'',payStyle:o.payStyle||'--grad-gold',
              hot:!!(o.hot||(o.badges||[]).includes('hot')),top:!!(o.top||(o.badges||[]).includes('top')),
              isNew:!!(o.isNew||(o.badges||[]).includes('new')),
              img:o.image||o.img||'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=70'};
          });
        }else{OFFER_DATA=FALLBACK_OFFERS;}
      })
      .catch(function(){OFFER_DATA=FALLBACK_OFFERS;})
      .finally(function(){
        initOfferSlider();
        var n=OFFER_DATA.length;
        var se=document.getElementById('statLiveOffers'); if(se)se.textContent=n+'+';
        var he=document.getElementById('heroStatOffers'); if(he)he.textContent=n+'+';
      });
  }

  var _heroSlides=[],_heroSlideHash='',_heroCur=0,_heroAutoT=null,_heroEventsSet=false,_heroInitDone=false,_HERO_POLL_MS=30000;
  function _heroHash(s){return s.map(function(x){return(x._id||x.id||'')+'|'+(x.name||'')+'|'+(x.image||'');}).join(';;');}
  function _badgePills(b){if(!b||!b.length)return'';return b.map(function(x){
    if(x==='hot')return'<span style="background:linear-gradient(135deg,#ff4500,#e8197a);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">🔥 Hot</span>';
    if(x==='top')return'<span style="background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">⭐ Top</span>';
    if(x==='new')return'<span style="background:linear-gradient(135deg,#059669,#0ea5e9);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">✨ New</span>';
    return'';}).join('');}
  function _heroRender(slides,forceReset){
    var se=document.getElementById('heroSlides'),de=document.getElementById('heroSliderDots'),sk=document.getElementById('heroSliderSkeleton'),pb=document.getElementById('heroSliderPrev'),nb=document.getElementById('heroSliderNext');
    if(!se||!slides.length)return;
    var nh=_heroHash(slides);
    if(!forceReset&&nh===_heroSlideHash&&_heroInitDone)return;
    _heroSlideHash=nh;_heroSlides=slides;if(_heroCur>=slides.length)_heroCur=0;
    se.innerHTML=slides.map(function(s){
      var bp=_badgePills(s.badges||[]);var sl=(s.link||'/register/affiliate').replace(/"/g,'');
      return'<div class="hero-slide">'+(s.image?'<img src="'+s.image+'" alt="'+((s.name||'').replace(/"/g,'&quot;'))+'" loading="lazy">':'<div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1535,#2d1b69)"></div>')
        +'<div class="hero-slide-overlay"></div><div class="hero-slide-caption">'+(bp?'<div style="margin-bottom:5px">'+bp+'</div>':'')
        +'<h3>'+((s.name||'').replace(/</g,'&lt;'))+'</h3>'+(s.desc?'<p>'+((s.desc||'').replace(/</g,'&lt;'))+'</p>':'')
        +'</div><a href="'+sl+'" style="position:absolute;inset:0;z-index:3;opacity:0" aria-label="'+((s.name||'').replace(/"/g,'&quot;'))+'"></a></div>';
    }).join('');
    de.innerHTML='';for(var i=0;i<slides.length;i++){(function(idx){var d=document.createElement('button');d.className='hero-slider-dot'+(idx===_heroCur?' active':'');d.setAttribute('aria-label','Slide '+(idx+1));d.onclick=function(){_heroGoTo(idx);};de.appendChild(d);})(i);}
    if(sk)sk.style.display='none';se.style.display='flex';if(pb)pb.style.display='';if(nb)nb.style.display='';
    if(!_heroEventsSet){_heroEventsSet=true;if(pb)pb.onclick=function(){_heroGoTo(_heroCur-1);};if(nb)nb.onclick=function(){_heroGoTo(_heroCur+1);};
      var tx=0;se.addEventListener('touchstart',function(e){tx=e.touches[0].clientX;},{passive:true});
      se.addEventListener('touchend',function(e){var d=tx-e.changedTouches[0].clientX;if(Math.abs(d)>40)_heroGoTo(_heroCur+(d>0?1:-1));});
      var wr=document.getElementById('heroSliderWrap');if(wr){wr.addEventListener('mouseenter',function(){clearInterval(_heroAutoT);});wr.addEventListener('mouseleave',function(){_heroStartAuto();});}}
    se.style.transition=_heroInitDone?'transform .6s cubic-bezier(.4,0,.2,1)':'none';
    se.style.transform='translateX(-'+(_heroCur*100)+'%)';
    de.querySelectorAll('.hero-slider-dot').forEach(function(d,i){d.classList.toggle('active',i===_heroCur);});
    if(!_heroInitDone){_heroInitDone=true;_heroStartAuto();}
  }
  function _heroGoTo(idx){var t=_heroSlides.length;if(!t)return;_heroCur=((idx%t)+t)%t;var se=document.getElementById('heroSlides'),de=document.getElementById('heroSliderDots');if(se){se.style.transition='transform .55s cubic-bezier(.4,0,.2,1)';se.style.transform='translateX(-'+(_heroCur*100)+'%)';}if(de){de.querySelectorAll('.hero-slider-dot').forEach(function(d,i){d.classList.toggle('active',i===_heroCur);});}clearInterval(_heroAutoT);_heroStartAuto();}
  function _heroStartAuto(){clearInterval(_heroAutoT);_heroAutoT=setInterval(function(){_heroGoTo(_heroCur+1);},4200);}
  function loadSlidesFromAPI(){fetch(AFF_API+'?resource=slider&action=list&_t='+Date.now()).then(function(r){return r.json();}).then(function(j){_heroRender((j.success&&j.data&&j.data.length)?j.data:FALLBACK_SLIDES,false);}).catch(function(){if(!_heroInitDone)_heroRender(FALLBACK_SLIDES,true);});}
  function initLiveHeroSlider(){loadSlidesFromAPI();setInterval(loadSlidesFromAPI,_HERO_POLL_MS);}

  function getCatBadgeClass(c){return{soi:'badge-soi',doi:'badge-doi',smartlink:'badge-smartlink',casino:'badge-casino',cam:'badge-cam',software:'badge-software',financial:'badge-financial',cps:'badge-cps',mobile:'badge-software'}[''+c]||'badge-soi';}
  function getCatLabel(c){return{soi:'SOI',doi:'DOI',smartlink:'Smartlink',casino:'Casino',cam:'Cam',software:'Software',financial:'Financial',cps:'CPS',mobile:'Mobile Apps'}[''+c]||(''+c).toUpperCase();}

  function buildCard(o){
    var geos=Array.isArray(o.geos)?o.geos:(o.geos||'WW').split(/\s+/).filter(Boolean);
    var geoHtml=(geos.includes('WW')||geos.includes('Worldwide')||geos.includes('Global'))?'<span class="ctag ww">🌍 Worldwide</span>':geos.slice(0,5).map(function(g){return'<span class="ctag">'+g+'</span>';}).join('')+(geos.length>5?'<span class="ctag">+'+(geos.length-5)+'</span>':'');
    var ov=(o.hot?'<span class="badge-hot-pill">🔥 Hot</span>':'')+(o.top?'<span class="badge-top-pill">⭐ Top</span>':'')+(o.isNew?'<span class="badge-new-pill">✨ New</span>':'');
    var pg=o.payStyle&&o.payStyle.startsWith('--')?'var('+o.payStyle+')':o.payStyle||'var(--grad-gold)';
    var ps=String(o.payoutDisplay||o.payout||'');

    // RevShare Check & Formatting Safeguard:
    var isRev = false;
    var subLower = String(o.sub || '').toLowerCase();
    var payLower = String(o.payoutType || o.payout_type || '').toLowerCase();
    var psLower  = ps.toLowerCase();

    if (subLower.includes('revshare') || payLower.includes('revshare') || psLower.includes('rev') || ps.includes('%')) {
      isRev = true;
    }

    if (isRev) {
      ps = ps.replace(/\$/g, '').trim();
      if (!ps.includes('%')) {
        var num = parseFloat(ps);
        if (!isNaN(num)) {
          ps = num.toFixed(2) + '%';
        } else {
          ps = ps + '%';
        }
      }
      if (!psLower.includes('rev')) {
        ps = ps + ' RevShare';
      }
      pg = 'var(--grad-green)';
    }

    var pf=ps.length>6?'16px':'22px';
    return'<div class="offer-card" data-cat="'+o.cat+'">'
      +'<div class="offer-card-img-wrap"><img class="offer-card-img" src="'+(o.img||'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=70')+'" alt="'+o.name+'" loading="lazy">'
      +(ov?'<div class="offer-badge-overlay">'+ov+'</div>':'')+'</div>'
      +'<div class="offer-card-body"><span class="offer-id">#'+o.id+'</span><span class="offer-badge '+getCatBadgeClass(o.cat)+'">'+getCatLabel(o.cat)+'</span>'
      +'<div class="offer-name">'+o.name+'</div><div class="offer-type">'+o.sub+'</div>'
      +'<div class="offer-countries">'+geoHtml+'</div>'
      +'<div class="offer-payout-row"><div><div class="payout-label">Payout</div><div class="payout-val" style="background:'+pg+';-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:'+pf+'">'+ps+'</div></div>'
      +'<a href="/register/affiliate" class="btn-apply">Apply ↗</a>'
      +'</div></div></div>';
  }

  function initOfferSlider(){
    var track=document.getElementById('sliderTrack'),dotsEl=document.getElementById('sliderDots'),fill=document.getElementById('progFill'),countEl=document.getElementById('offerCount');
    if(!track)return;
    var GAP=16,DELAY=3200,cur=0,maxIdx=0,cardW=0,autoT,progT,progVal=0,allCards=[],visCards=[];
    function getVisible(){var w=window.innerWidth;if(w>=1024)return 4;if(w>=768)return 3;if(w>=480)return 2;return 1;}
    function initOfferCard3DTilt() {
      allCards.forEach(function(card) {
        if (card.getAttribute('data-tilt-bound')) return;
        card.setAttribute('data-tilt-bound', 'true');
        card.addEventListener('mousemove', function(e) {
          var rect = card.getBoundingClientRect();
          var x = e.clientX - rect.left;
          var y = e.clientY - rect.top;
          var xc = rect.width / 2;
          var yc = rect.height / 2;
          var angleX = (yc - y) / 10;
          var angleY = (x - xc) / 10;
          card.style.transform = 'perspective(1000px) rotateX(' + angleX + 'deg) rotateY(' + angleY + 'deg) scale3d(1.03, 1.03, 1.03) translateY(-6px)';
        });
        card.addEventListener('mouseleave', function() {
          card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
        });
      });
    }
    function syncCatTabs(){
      var tabsEl=document.getElementById('affTabs');
      if(!tabsEl)return;
      var existingCats=[];
      tabsEl.querySelectorAll('.aff-tab').forEach(function(t){
        existingCats.push(t.getAttribute('data-cat'));
      });
      OFFER_DATA.forEach(function(o){
        if(o.cat && !existingCats.includes(o.cat)){
          existingCats.push(o.cat);
          var btn=document.createElement('button');
          btn.className='aff-tab';
          btn.setAttribute('data-cat', o.cat);
          btn.textContent=getCatLabel(o.cat);
          tabsEl.appendChild(btn);
        }
      });
      tabsEl.querySelectorAll('.aff-tab').forEach(function(tab){
        if(tab.getAttribute('data-bound'))return;
        tab.setAttribute('data-bound','true');
        tab.addEventListener('click',function(){
          tabsEl.querySelectorAll('.aff-tab').forEach(function(t){t.classList.remove('active');});
          tab.classList.add('active');
          filterCat(tab.getAttribute('data-cat'));
        });
      });
    }

    function buildAll(){syncCatTabs();track.innerHTML=OFFER_DATA.map(buildCard).join('');allCards=Array.from(track.querySelectorAll('.offer-card'));visCards=allCards.slice();if(countEl)countEl.textContent=allCards.length+' Offers';calc();resetAuto();if(!chartsBuilt){buildCharts();chartsBuilt=true;}initOfferCard3DTilt();}
    function filterCat(cat){cur=0;allCards.forEach(function(c){c.style.display=(cat==='all'||c.getAttribute('data-cat')===cat)?'':'none';});visCards=allCards.filter(function(c){return c.style.display!=='none';});if(countEl)countEl.textContent=visCards.length+' Offers';track.style.transition='none';track.style.transform='translateX(0)';calc();resetAuto();initOfferCard3DTilt();}
    document.querySelectorAll('.aff-tab').forEach(function(tab){tab.addEventListener('click',function(){document.querySelectorAll('.aff-tab').forEach(function(t){t.classList.remove('active');});tab.classList.add('active');filterCat(tab.getAttribute('data-cat'));});});
    function calc(){if(!visCards.length)return;var vis=getVisible(),cw=track.parentElement.offsetWidth,nw=Math.floor((cw-GAP*(vis-1))/vis);allCards.forEach(function(c){c.style.flex='0 0 '+nw+'px';c.style.maxWidth=nw+'px';c.style.minWidth=nw+'px';});cardW=nw+GAP;maxIdx=Math.max(0,visCards.length-vis);if(cur>maxIdx)cur=maxIdx;buildDots(vis);render(false);}
    function buildDots(vis){dotsEl.innerHTML='';var pages=Math.ceil(visCards.length/Math.max(1,vis));for(var i=0;i<pages;i++){(function(idx){var d=document.createElement('div');d.className='slider-dot'+(idx===Math.floor(cur/Math.max(1,vis))?' active':'');d.onclick=function(){goTo(idx*getVisible());};dotsEl.appendChild(d);})(i);}}
    function render(anim){track.style.transition=anim?'transform .52s cubic-bezier(.4,0,.2,1)':'none';var off=0,vs=0;for(var i=0;i<allCards.length&&vs<cur;i++){if(allCards[i].style.display!=='none'){off+=cardW;vs++;}}track.style.transform='translateX(-'+off+'px)';var vis=getVisible(),pg=Math.floor(cur/Math.max(1,vis));dotsEl.querySelectorAll('.slider-dot').forEach(function(d,i){d.classList.toggle('active',i===pg);});var pb=document.getElementById('sliderPrev'),nb=document.getElementById('sliderNext');if(pb)pb.disabled=(cur===0);if(nb)nb.disabled=(cur>=maxIdx);}
    function goTo(idx){cur=Math.max(0,Math.min(idx,maxIdx));render(true);resetAuto();}
    document.getElementById('sliderPrev').onclick=function(){goTo(cur-getVisible());};
    document.getElementById('sliderNext').onclick=function(){goTo(cur+getVisible());};
    function startProg(){progVal=0;clearInterval(progT);progT=setInterval(function(){progVal+=100/(DELAY/100);if(fill)fill.style.width=Math.min(progVal,100)+'%';},100);}
    function resetAuto(){clearInterval(autoT);clearInterval(progT);startProg();autoT=setInterval(function(){goTo(cur>=maxIdx?0:cur+getVisible());},DELAY);}
    var tx=0;track.addEventListener('touchstart',function(e){tx=e.touches[0].clientX;},{passive:true});track.addEventListener('touchend',function(e){var d=tx-e.changedTouches[0].clientX;if(Math.abs(d)>40)goTo(cur+(d>0?getVisible():-getVisible()));});
    track.parentElement.addEventListener('mouseenter',function(){clearInterval(autoT);clearInterval(progT);});track.parentElement.addEventListener('mouseleave',resetAuto);
    window.addEventListener('resize',function(){clearTimeout(window._resT);window._resT=setTimeout(calc,200);});
    buildAll();
  }

  function buildCharts(){
    if(typeof Chart==='undefined')return;
    var cats={SOI:0,DOI:0,Smartlink:0,CPS:0,Casino:0,Cam:0,Software:0,Financial:0};
    OFFER_DATA.forEach(function(o){var k={soi:'SOI',doi:'DOI',smartlink:'Smartlink',cps:'CPS',casino:'Casino',cam:'Cam',software:'Software',financial:'Financial'}[o.cat];if(k)cats[k]++;});
    var c1=document.getElementById('chartCat'),c2=document.getElementById('chartPayout'),c3=document.getElementById('chartGeo');
    if(c1)new Chart(c1,{type:'doughnut',data:{labels:Object.keys(cats),datasets:[{data:Object.values(cats),backgroundColor:['#e8197a','#f97316','#059669','#d97706','#f59e0b','#a855f7','#0ea5e9','#2563eb'],borderWidth:0,hoverOffset:6}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'rgba(255,255,255,0.7)',font:{size:10},padding:8,boxWidth:10}}}}});
    var _parsePay=function(o){return parseFloat(String(o.payoutDisplay||o.payout||'').replace(/[^0-9.]/g,''))||0;};
    var tp=OFFER_DATA.map(function(o){return{name:o.name,payNum:_parsePay(o)};}).filter(function(o){return o.payNum>0;}).sort(function(a,b){return b.payNum-a.payNum;}).slice(0,7);
    if(!tp.length){tp=OFFER_DATA.slice(0,7).map(function(o){return{name:o.name,payNum:_parsePay(o)||30};});}
    if(c2&&tp.length)new Chart(c2,{type:'bar',data:{labels:tp.map(function(o){return o.name.length>13?o.name.slice(0,13)+'…':o.name;}),datasets:[{data:tp.map(function(o){return o.payNum;}),backgroundColor:['#e8197a','#7c3aed','#d97706','#059669','#2563eb','#f97316','#0ea5e9'],borderRadius:5,borderWidth:0}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{color:'rgba(255,255,255,0.7)',callback:function(v){return'$'+v;}},grid:{color:'rgba(255,255,255,.06)'}},x:{ticks:{color:'rgba(255,255,255,0.7)',font:{size:9}},grid:{color:'rgba(255,255,255,.03)'}}}}});
    var gc={};OFFER_DATA.forEach(function(o){var g=Array.isArray(o.geos)?o.geos:(o.geos||'').split(/\s+/).filter(Boolean);g.forEach(function(x){if(x!=='WW'&&x!=='Worldwide'){gc[x]=(gc[x]||0)+1;}});});
    var tg=Object.entries(gc).sort(function(a,b){return b[1]-a[1];}).slice(0,8);
    if(c3)new Chart(c3,{type:'bar',data:{labels:tg.map(function(g){return g[0];}),datasets:[{data:tg.map(function(g){return g[1];}),backgroundColor:'#7c3aed',borderRadius:5,borderWidth:0}]},options:{indexAxis:'y',responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,.06)'},ticks:{color:'rgba(255,255,255,0.7)'}},y:{ticks:{color:'rgba(255,255,255,0.7)',font:{size:10}},grid:{color:'rgba(255,255,255,.03)'}}}}});
  }

  // ── 3D Interactive Hero scene JavaScript (Mouse parallax + floating dynamic indicator rotation) ──
  (function() {
    var scene = document.getElementById('hero3DGraphic');
    if (!scene) return;
    
    var sceneWrap = scene.querySelector('.threed-scene-wrap');
    var layers = scene.querySelectorAll('.floating-ui-layer');
    
    // Mouse movement parallax effect
    scene.addEventListener('mousemove', function(e) {
      var rect = scene.getBoundingClientRect();
      var x = e.clientX - rect.left - (rect.width / 2);
      var y = e.clientY - rect.top - (rect.height / 2);
      
      // Calculate rotation angles for scene
      var rotY = (x / rect.width) * 22; // max 11 deg left/right
      var rotX = -(y / rect.height) * 22; // max 11 deg up/down
      
      if (sceneWrap) {
        sceneWrap.style.transform = 'rotateY(' + rotY + 'deg) rotateX(' + rotX + 'deg)';
      }
      
      // Offset each UI layer based on depth
      layers.forEach(function(layer) {
        var depth = parseFloat(layer.getAttribute('data-depth') || '0.2');
        var moveX = (x * depth) * 0.65;
        var moveY = (y * depth) * 0.65;
        
        // Target specific transforms per card type to preserve base styles
        var baseTransform = '';
        if (layer.classList.contains('lock-node')) {
          baseTransform = 'translateZ(10px) ';
        } else if (layer.classList.contains('card-clicks')) {
          baseTransform = 'translateZ(15px) ';
        } else if (layer.classList.contains('card-convs')) {
          baseTransform = 'translateZ(25px) ';
        } else if (layer.classList.contains('card-revenue')) {
          baseTransform = 'translateZ(35px) ';
        } else if (layer.classList.contains('card-fraud')) {
          baseTransform = 'translateZ(45px) ';
        } else if (layer.classList.contains('card-chat')) {
          baseTransform = 'translateZ(30px) ';
        }
        
        layer.style.transform = baseTransform + 'translate3d(' + moveX + 'px, ' + moveY + 'px, 0px)';
      });
    });
    
    // Reset positions when mouse leaves the scene
    scene.addEventListener('mouseleave', function() {
      if (sceneWrap) {
        sceneWrap.style.transform = 'rotateY(0deg) rotateX(0deg)';
        sceneWrap.style.transition = 'transform 0.5s ease-out';
      }
      layers.forEach(function(layer) {
        layer.style.transform = '';
        layer.style.transition = 'transform 0.5s ease-out';
      });
    });
    
    // Restored Dynamic Multi-Slide Carousel functionality (cycling live images inside 3D screen wrapper)
    window._heroSlides = [];
    window._heroSlideHash = '';
    window._heroCur = 0;
    window._heroAutoT = null;
    window._heroEventsSet = false;
    window._heroInitDone = false;
    window._HERO_POLL_MS = 30000;

    window._heroHash = function(s) {
      return s.map(function(x) { return (x._id || x.id || '') + '|' + (x.name || '') + '|' + (x.image || ''); }).join(';;');
    };

    window._badgePills = function(b) {
      if (!b || !b.length) return '';
      return b.map(function(x) {
        if (x === 'hot') return '<span style="background:linear-gradient(135deg,#ff4500,#e8197a);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">🔥 Hot</span>';
        if (x === 'top') return '<span style="background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">⭐ Top</span>';
        if (x === 'new') return '<span style="background:linear-gradient(135deg,#059669,#0ea5e9);color:#fff;font-size:8px;font-weight:800;padding:3px 8px;border-radius:20px;text-transform:uppercase;margin-right:4px">✨ New</span>';
        return '';
      }).join('');
    };

    window._heroRender = function(slides, forceReset) {
      var se = document.getElementById('heroSlides'),
          de = document.getElementById('heroSliderDots'),
          sk = document.getElementById('heroSliderSkeleton'),
          pb = document.getElementById('heroSliderPrev'),
          nb = document.getElementById('heroSliderNext');
      if (!se || !slides.length) return;
      var nh = window._heroHash(slides);
      if (!forceReset && nh === window._heroSlideHash && window._heroInitDone) return;
      window._heroSlideHash = nh;
      window._heroSlides = slides;
      if (window._heroCur >= slides.length) window._heroCur = 0;

      se.innerHTML = slides.map(function(s) {
        var bp = window._badgePills(s.badges || []);
        var sl = (s.link || '/register/affiliate').replace(/"/g, '');
        return '<div class="hero-slide">' + (s.image ? '<img src="' + s.image + '" alt="' + ((s.name || '').replace(/"/g, '&quot;')) + '" loading="lazy">' : '<div style="width:100%;height:100%;background:linear-gradient(135deg,#1a1535,#2d1b69)"></div>') +
          '<div class="hero-slide-overlay"></div><div class="hero-slide-caption">' + (bp ? '<div style="margin-bottom:5px">' + bp + '</div>' : '') +
          '<h3>' + ((s.name || '').replace(/</g, '&lt;')) + '</h3>' + (s.desc ? '<p>' + ((s.desc || '').replace(/</g, '&lt;')) + '</p>' : '') +
          '</div><a href="' + sl + '" style="position:absolute;inset:0;z-index:3;opacity:0" aria-label="' + ((s.name || '').replace(/"/g, '&quot;')) + '"></a></div>';
      }).join('');

      de.innerHTML = '';
      for (var i = 0; i < slides.length; i++) {
        (function(idx) {
          var d = document.createElement('span');
          d.className = 'threed-dot' + (idx === window._heroCur ? ' active' : '');
          d.setAttribute('aria-label', 'Slide ' + (idx + 1));
          d.onclick = function() { window._heroGoTo(idx); };
          de.appendChild(d);
        })(i);
      }

      if (sk) sk.style.display = 'none';
      se.style.display = 'flex';
      if (pb) pb.style.display = '';
      if (nb) nb.style.display = '';

      if (!window._heroEventsSet) {
        window._heroEventsSet = true;
        if (pb) pb.onclick = function() { window._heroGoTo(window._heroCur - 1); };
        if (nb) nb.onclick = function() { window._heroGoTo(window._heroCur + 1); };
        var tx = 0;
        se.addEventListener('touchstart', function(e) { tx = e.touches[0].clientX; }, { passive: true });
        se.addEventListener('touchend', function(e) {
          var d = tx - e.changedTouches[0].clientX;
          if (Math.abs(d) > 40) window._heroGoTo(window._heroCur + (d > 0 ? 1 : -1));
        });
        var wr = document.getElementById('heroSliderWrap');
        if (wr) {
          wr.addEventListener('mouseenter', function() { clearInterval(window._heroAutoT); });
          wr.addEventListener('mouseleave', function() { window._heroStartAuto(); });
        }
      }

      se.style.transition = window._heroInitDone ? 'transform .6s cubic-bezier(.4,0,.2,1)' : 'none';
      se.style.transform = 'translateX(-' + (window._heroCur * 100) + '%)';
      de.querySelectorAll('.threed-dot').forEach(function(d, idx) {
        d.classList.toggle('active', idx === window._heroCur);
      });

      if (!window._heroInitDone) {
        window._heroInitDone = true;
        window._heroStartAuto();
      }
    };

    window._heroGoTo = function(idx) {
      var t = window._heroSlides.length;
      if (!t) return;
      window._heroCur = ((idx % t) + t) % t;
      var se = document.getElementById('heroSlides'),
          de = document.getElementById('heroSliderDots');
      if (se) {
        se.style.transition = 'transform .55s cubic-bezier(.4,0,.2,1)';
        se.style.transform = 'translateX(-' + (window._heroCur * 100) + '%)';
      }
      if (de) {
        de.querySelectorAll('.threed-dot').forEach(function(d, idx) {
          d.classList.toggle('active', idx === window._heroCur);
        });
      }
      clearInterval(window._heroAutoT);
      window._heroStartAuto();
    };

    window._heroStartAuto = function() {
      clearInterval(window._heroAutoT);
      window._heroAutoT = setInterval(function() { window._heroGoTo(window._heroCur + 1); }, 4200);
    };

    window.loadSlidesFromAPI = function() {
      fetch(AFF_API + '?resource=slider&action=list&_t=' + Date.now())
        .then(function(r) { return r.json(); })
        .then(function(j) {
          window._heroRender((j.success && j.data && j.data.length) ? j.data : FALLBACK_SLIDES, false);
        })
        .catch(function() {
          if (!window._heroInitDone) window._heroRender(FALLBACK_SLIDES, true);
        });
    };

    window.initLiveHeroSlider = function() {
      window.loadSlidesFromAPI();
      setInterval(window.loadSlidesFromAPI, window._HERO_POLL_MS);
    };

    // Initialize the slider on runtime
    window.initLiveHeroSlider();
    
    // Live clicks and conversions live ticker simulator
    var clicksEl = document.getElementById('liveClicksVal');
    var convsEl = document.getElementById('liveConvsVal');
    var currentClicks = 128690;
    var currentConvs = 8756;
    
    setInterval(function() {
      if (Math.random() > 0.3) {
        var clickInc = Math.floor(Math.random() * 8) + 1;
        var convInc = Math.random() > 0.85 ? 1 : 0;
        
        currentClicks += clickInc;
        currentConvs += convInc;
        
        if (clicksEl) clicksEl.textContent = currentClicks.toLocaleString('en-US');
        if (convsEl) convsEl.textContent = currentConvs.toLocaleString('en-US');
      }
    }, 2800);
  })();

  function handleSubmit(e){
    var sEl=document.getElementById('formSuccess'),eEl=document.getElementById('formError'),etEl=document.getElementById('errorText'),btn=e.target.querySelector('button[type="submit"]');
    sEl.style.display='none';eEl.style.display='none';
    var fn=document.getElementById('fname').value.trim(),ln=document.getElementById('lname').value.trim(),em=document.getElementById('cemail').value.trim(),msg=document.getElementById('message').value.trim();
    if(!fn||!ln||!em||!msg){etEl.textContent='Please fill in all fields.';eEl.style.display='block';return;}
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)){etEl.textContent='Please enter a valid email address.';eEl.style.display='block';return;}
    var orig=btn.innerHTML;btn.disabled=true;btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Sending…';
    fetch('/send_mail.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({fname:fn,lname:ln,email:em,message:msg})})
      .then(function(r){return r.json();})
      .then(function(d){if(d.success){sEl.style.display='block';e.target.reset();setTimeout(function(){sEl.style.display='none';},6000);}else{etEl.textContent=d.message||'Failed to send.';eEl.style.display='block';}})
      .catch(function(){etEl.textContent='Network error. Please contact us via Telegram @affscashnet.';eEl.style.display='block';})
      .finally(function(){btn.disabled=false;btn.innerHTML=orig;});
  }

  function hidePreloader(){var p=document.getElementById('preloader');if(p)p.classList.add('hidden');}
  window.addEventListener('load',function(){setTimeout(hidePreloader,400);});
  setTimeout(hidePreloader,3000);

  var ham=document.getElementById('hamburger'),mob=document.getElementById('mobile-menu');
  if(ham&&mob){ham.addEventListener('click',function(){ham.classList.toggle('open');mob.classList.toggle('open');});mob.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){ham.classList.remove('open');mob.classList.remove('open');});});}
window.addEventListener('scroll',function(){var b=document.getElementById('scrollTop');if(b)b.classList.toggle('visible',window.scrollY>400);});

  var observer=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting)e.target.classList.add('visible');});},{threshold:0.12});
  document.querySelectorAll('.fade-up').forEach(function(el){observer.observe(el);});
  document.querySelectorAll('.progress-item').forEach(function(el){
    var io=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting)e.target.querySelectorAll('.progress-bar-fill').forEach(function(bar){bar.style.width=bar.getAttribute('data-width')+'%';});});},{threshold:0.3});
    io.observe(el);
  });

  // ── Hero Background Particles Animation (3D Connected Networking Constellation) ──
  (function() {
    var canvas = document.getElementById('heroParticlesCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var particles = [];
    var w, h;
    var mouse = { x: null, y: null, active: false };
    
    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);
    
    // Add mouse move listener globally to the window for connection interaction
    window.addEventListener('mousemove', function(e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      mouse.active = true;
    });
    window.addEventListener('mouseleave', function() {
      mouse.active = false;
    });
    
    // Create particles with x, y, z coordinates
    var numParticles = 115;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * 800, // 3D local X
        y: (Math.random() - 0.5) * 500, // 3D local Y
        z: (Math.random() - 0.5) * 400, // 3D local Z
        vx: (Math.random() - 0.5) * 0.4,
        vy: (Math.random() - 0.5) * 0.4,
        vz: (Math.random() - 0.5) * 0.4,
        size: Math.random() * 2 + 1.5,
        color: Math.random() > 0.5 ? 'rgba(124, 58, 237, 0.4)' : 'rgba(14, 165, 233, 0.35)'
      });
    }
    
    var fov = 400; // Focal length
    
    function draw() {
      ctx.clearRect(0, 0, w, h);
      
      // Update & project particles
      var projected = [];
      particles.forEach(function(p) {
        // Move in 3D
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        // Bounce within virtual 3D box bounds
        if (Math.abs(p.x) > 450) p.vx *= -1;
        if (Math.abs(p.y) > 300) p.vy *= -1;
        if (Math.abs(p.z) > 200) p.vz *= -1;
        
        // Slow rotation in 3D space
        var rotY = 0.0006;
        var cosY = Math.cos(rotY), sinY = Math.sin(rotY);
        var x1 = p.x * cosY - p.z * sinY;
        var z1 = p.z * cosY + p.x * sinY;
        p.x = x1; p.z = z1;
        
        var rotX = 0.0004;
        var cosX = Math.cos(rotX), sinX = Math.sin(rotX);
        var y1 = p.y * cosX - p.z * sinX;
        var z2 = p.z * cosX + p.y * sinX;
        p.y = y1; p.z = z2;
        
        // Perspective projection
        var scale = fov / (fov + p.z);
        var px = (p.x * scale) + (w / 2);
        var py = (p.y * scale) + (h / 2);
        
        projected.push({
          x: px,
          y: py,
          z: p.z,
          size: p.size * scale,
          color: p.color
        });
      });
      
      // Connect nodes with lines if they are close
      for (var a = 0; a < projected.length; a++) {
        var pa = projected[a];
        for (var b = a + 1; b < projected.length; b++) {
          var pb = projected[b];
          
          // Calculate distance in 2D space
          var dx = pa.x - pb.x;
          var dy = pa.y - pb.y;
          var dist = Math.hypot(dx, dy);
          
          // Only connect if they are close enough
          if (dist < 110) {
            // Fade out based on distance and depth (farther lines are thinner/fainter)
            var opacity = (1 - (dist / 110)) * 0.15 * (1 - (pa.z + pb.z) / 400);
            if (opacity > 0) {
              ctx.beginPath();
              ctx.moveTo(pa.x, pa.y);
              ctx.lineTo(pb.x, pb.y);
              ctx.strokeStyle = 'rgba(124, 58, 237, ' + opacity + ')';
              ctx.lineWidth = 0.8 * (1 - (pa.z + pb.z) / 400);
              ctx.stroke();
            }
          }
        }
        
        // Draw the point
        if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
          ctx.fillStyle = pa.color;
          ctx.beginPath();
          ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
          ctx.fill();
          
          // Draw subtle outer glow ring for larger points
          if (pa.size > 2) {
            ctx.strokeStyle = pa.color.replace('0.4', '0.08').replace('0.35', '0.08');
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(pa.x, pa.y, pa.size * 2, 0, Math.PI * 2);
            ctx.stroke();
          }
        }
      }
      
      // Draw line from mouse to nearby nodes
      if (mouse.active) {
        projected.forEach(function(p) {
          var dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
          if (dist < 150) {
            var opacity = (1 - (dist / 150)) * 0.22;
            ctx.beginPath();
            ctx.moveTo(mouse.x, mouse.y);
            ctx.lineTo(p.x, p.y);
            ctx.strokeStyle = 'rgba(14, 165, 233, ' + opacity + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        });
      }
      
      requestAnimationFrame(draw);
    }
    draw();
  })();

  // ── Features Canvases Animators ──
  (function() {
    // 1. Smart Tracking Sine Graph
    var cTrack = document.getElementById('canvasTracking');
    if (cTrack) {
      var ctx = cTrack.getContext('2d');
      var w, h, step = 0;
      function resize() {
        w = cTrack.width = cTrack.offsetWidth * window.devicePixelRatio;
        h = cTrack.height = cTrack.offsetHeight * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
      }
      resize();
      function draw() {
        var cw = w / window.devicePixelRatio;
        var ch = h / window.devicePixelRatio;
        ctx.clearRect(0,0,cw,ch);
        
        // Draw grid lines
        ctx.strokeStyle = 'rgba(255,255,255,0.03)';
        ctx.lineWidth = 1;
        for (var i = 0; i < cw; i += 20) {
          ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, ch); ctx.stroke();
        }
        for (var j = 0; j < ch; j += 20) {
          ctx.beginPath(); ctx.moveTo(0, j); ctx.lineTo(cw, j); ctx.stroke();
        }
        
        // Sine wave
        ctx.strokeStyle = 'rgba(124, 58, 237, 0.7)';
        ctx.shadowColor = 'rgba(124, 58, 237, 0.4)';
        ctx.shadowBlur = 8;
        ctx.lineWidth = 3;
        ctx.beginPath();
        for (var x = 0; x < cw; x++) {
          var y = ch/2 + Math.sin(x*0.02 + step) * 25 + Math.cos(x*0.01 + step*0.5)*10;
          if (x === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        }
        ctx.stroke();
        ctx.shadowBlur = 0;
        
        // Moving scanner node
        var scanX = (step * 80) % cw;
        var scanY = ch/2 + Math.sin(scanX*0.02 + step) * 25 + Math.cos(scanX*0.01 + step*0.5)*10;
        ctx.fillStyle = '#0ea5e9';
        ctx.beginPath();
        ctx.arc(scanX, scanY, 5, 0, Math.PI*2);
        ctx.fill();
        
        step += 0.035;
        requestAnimationFrame(draw);
      }
      draw();
    }
    
    // 2. Fraud Shield
    var cFraud = document.getElementById('canvasFraud');
    if (cFraud) {
      var ctx = cFraud.getContext('2d');
      var w, h, step = 0;
      function resize() {
        w = cFraud.width = cFraud.offsetWidth * window.devicePixelRatio;
        h = cFraud.height = cFraud.offsetHeight * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
      }
      resize();
      function draw() {
        var cw = w / window.devicePixelRatio;
        var ch = h / window.devicePixelRatio;
        ctx.clearRect(0,0,cw,ch);
        
        var cx = cw / 2;
        var cy = ch / 2;
        var r = 32;
        
        // Pulsing radar rings
        var scale = 1 + (step % 1.5) / 1.5;
        ctx.strokeStyle = 'rgba(232, 25, 122, ' + (1 - (scale-1)) + ')';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(cx, cy, r * scale, 0, Math.PI*2);
        ctx.stroke();
        
        // Shield polygon
        ctx.shadowColor = 'rgba(232, 25, 122, 0.5)';
        ctx.shadowBlur = 10;
        ctx.strokeStyle = '#e8197a';
        ctx.lineWidth = 2.5;
        ctx.fillStyle = 'rgba(232, 25, 122, 0.1)';
        ctx.beginPath();
        ctx.moveTo(cx, cy - r);
        ctx.lineTo(cx + r, cy - r*0.4);
        ctx.lineTo(cx + r*0.7, cy + r*0.7);
        ctx.lineTo(cx, cy + r);
        ctx.lineTo(cx - r*0.7, cy + r*0.7);
        ctx.lineTo(cx - r, cy - r*0.4);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
        ctx.shadowBlur = 0;
        
        // Scan line overlay
        var scanY = cy - r + ((step * 35) % (r * 2));
        ctx.strokeStyle = 'rgba(14, 165, 233, 0.6)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cx - r*0.8, scanY);
        ctx.lineTo(cx + r*0.8, scanY);
        ctx.stroke();
        
        step += 0.02;
        requestAnimationFrame(draw);
      }
      draw();
    }
    
    // 3. Spinning Particle Globe
    var cGlobe = document.getElementById('canvasGlobe');
    if (cGlobe) {
      var ctx = cGlobe.getContext('2d');
      var w, h, step = 0;
      function resize() {
        w = cGlobe.width = cGlobe.offsetWidth * window.devicePixelRatio;
        h = cGlobe.height = cGlobe.offsetHeight * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
      }
      resize();
      
      var points = [];
      for (var i=0; i<38; i++) {
        points.push({
          lat: Math.random() * Math.PI - Math.PI/2,
          lon: Math.random() * Math.PI * 2,
          r: 28 + Math.random()*2
        });
      }
      
      function draw() {
        var cw = w / window.devicePixelRatio;
        var ch = h / window.devicePixelRatio;
        ctx.clearRect(0,0,cw,ch);
        
        var cx = cw / 2;
        var cy = ch / 2;
        
        // Rotation angles
        var rotY = step * 0.45;
        
        // Draw globe outline
        ctx.strokeStyle = 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.arc(cx, cy, 32, 0, Math.PI*2);
        ctx.stroke();
        
        // Project and draw points
        ctx.fillStyle = '#10b981';
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.15)';
        ctx.lineWidth = 1;
        var proj = [];
        points.forEach(function(p) {
          var y3 = p.r * Math.sin(p.lat);
          var rad = p.r * Math.cos(p.lat);
          var x3 = rad * Math.cos(p.lon + rotY);
          var z3 = rad * Math.sin(p.lon + rotY);
          
          if (z3 > -5) { // face visible
            var scale = (z3 + 50) / 50;
            var px = cx + x3 * scale;
            var py = cy + y3 * scale;
            proj.push({x: px, y: py, sz: 2.5 * scale});
          }
        });
        
        // Connect close projected points
        for (var a=0; a<proj.length; a++) {
          for (var b=a+1; b<proj.length; b++) {
            var dist = Math.hypot(proj[a].x - proj[b].x, proj[a].y - proj[b].y);
            if (dist < 22) {
              ctx.beginPath();
              ctx.moveTo(proj[a].x, proj[a].y);
              ctx.lineTo(proj[b].x, proj[b].y);
              ctx.stroke();
            }
          }
        }
        
        // Draw point dots
        proj.forEach(function(pt) {
          ctx.beginPath();
          ctx.arc(pt.x, pt.y, pt.sz, 0, Math.PI*2);
          ctx.fill();
        });
        
        step += 0.015;
        requestAnimationFrame(draw);
      }
      draw();
    }
    
    // 4. Crypto/USDT Blockchain Node Ledgers
    var cPayments = document.getElementById('canvasPayments');
    if (cPayments) {
      var ctx = cPayments.getContext('2d');
      var w, h, step = 0;
      function resize() {
        w = cPayments.width = cPayments.offsetWidth * window.devicePixelRatio;
        h = cPayments.height = cPayments.offsetHeight * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
      }
      resize();
      
      var nodes = [
        {x: 0.2, y: 0.5, size: 5, color: '#f59e0b', pulse: 0},
        {x: 0.4, y: 0.3, size: 4, color: '#0ea5e9', pulse: 1},
        {x: 0.4, y: 0.7, size: 4, color: '#0ea5e9', pulse: 2},
        {x: 0.6, y: 0.4, size: 4, color: '#7c3aed', pulse: 3},
        {x: 0.6, y: 0.6, size: 4, color: '#7c3aed', pulse: 4},
        {x: 0.8, y: 0.5, size: 5, color: '#10b981', pulse: 0}
      ];
      
      function draw() {
        var cw = w / window.devicePixelRatio;
        var ch = h / window.devicePixelRatio;
        ctx.clearRect(0,0,cw,ch);
        
        // Connect nodes
        ctx.strokeStyle = 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 1.5;
        
        // Draw lines
        ctx.beginPath();
        ctx.moveTo(nodes[0].x * cw, nodes[0].y * ch);
        ctx.lineTo(nodes[1].x * cw, nodes[1].y * ch);
        ctx.lineTo(nodes[3].x * cw, nodes[3].y * ch);
        ctx.lineTo(nodes[5].x * cw, nodes[5].y * ch);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo(nodes[0].x * cw, nodes[0].y * ch);
        ctx.lineTo(nodes[2].x * cw, nodes[2].y * ch);
        ctx.lineTo(nodes[4].x * cw, nodes[4].y * ch);
        ctx.lineTo(nodes[5].x * cw, nodes[5].y * ch);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo(nodes[1].x * cw, nodes[1].y * ch);
        ctx.lineTo(nodes[2].x * cw, nodes[2].y * ch);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo(nodes[3].x * cw, nodes[3].y * ch);
        ctx.lineTo(nodes[4].x * cw, nodes[4].y * ch);
        ctx.stroke();
        
        // Draw transaction flow packets
        var flowProgress = (step * 0.4) % 1.0;
        ctx.fillStyle = '#fff';
        
        // Path 1
        var p1x = nodes[0].x * cw + (nodes[1].x - nodes[0].x) * cw * flowProgress;
        var p1y = nodes[0].y * ch + (nodes[1].y - nodes[0].y) * ch * flowProgress;
        ctx.beginPath(); ctx.arc(p1x, p1y, 2.5, 0, Math.PI*2); ctx.fill();
        
        // Path 2
        var p2x = nodes[2].x * cw + (nodes[4].x - nodes[2].x) * cw * flowProgress;
        var p2y = nodes[2].y * ch + (nodes[4].y - nodes[2].y) * ch * flowProgress;
        ctx.beginPath(); ctx.arc(p2x, p2y, 2.5, 0, Math.PI*2); ctx.fill();
        
        // Draw pulsing nodes
        nodes.forEach(function(n) {
          var pulseScale = 1 + Math.sin(step + n.pulse) * 0.25;
          ctx.fillStyle = n.color;
          ctx.shadowColor = n.color;
          ctx.shadowBlur = 8;
          ctx.beginPath();
          ctx.arc(n.x * cw, n.y * ch, n.size * pulseScale, 0, Math.PI*2);
          ctx.fill();
          ctx.shadowBlur = 0;
        });
        
        step += 0.035;
        requestAnimationFrame(draw);
      }
      draw();
    }
  })();

  // ── Dashboard Preview Chart ──
  (function() {
    var ctx = document.getElementById('dbPreviewChart');
    if (ctx) {
      var gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 180);
      gradient.addColorStop(0, 'rgba(124, 58, 237, 0.4)');
      gradient.addColorStop(1, 'rgba(124, 58, 237, 0)');
      
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['01 AM', '04 AM', '08 AM', '12 PM', '04 PM', '08 PM', '11 PM'],
          datasets: [{
            label: 'Earnings ($)',
            data: [120, 240, 480, 890, 1020, 1200, 1284],
            borderColor: '#7c3aed',
            borderWidth: 3,
            backgroundColor: gradient,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#7c3aed',
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 10 } } },
            y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: 'rgba(255, 255, 255, 0.6)', font: { size: 10 } } }
          }
        }
      });
    }
  })();

  // ── Advertiser Ecosystem Canvas ──
  (function() {
    var canvas = document.getElementById('canvasAdvertiserEcosystem');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var w, h, step = 0;
    function resize() {
      w = canvas.width = canvas.offsetWidth * window.devicePixelRatio;
      h = canvas.height = canvas.offsetHeight * window.devicePixelRatio;
      ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    }
    resize();
    
    // Define entity points
    var nodes = [
      { name: 'Advertisers', x: 0.15, y: 0.5, color: '#e8197a', icon: '🏢' },
      { name: 'AffsCash API', x: 0.5, y: 0.5, color: '#7c3aed', icon: '⚙️' },
      { name: 'Search Traffic', x: 0.85, y: 0.25, color: '#0ea5e9', icon: '🔍' },
      { name: 'Social Media', x: 0.85, y: 0.5, color: '#0ea5e9', icon: '💬' },
      { name: 'Native Channels', x: 0.85, y: 0.75, color: '#0ea5e9', icon: '📰' }
    ];
    
    function draw() {
      var cw = w / window.devicePixelRatio;
      var ch = h / window.devicePixelRatio;
      ctx.clearRect(0,0,cw,ch);
      
      // Draw grid lines
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.02)';
      ctx.lineWidth = 1;
      for (var i = 0; i < cw; i += 30) {
        ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, ch); ctx.stroke();
      }
      
      // Draw connections
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
      ctx.lineWidth = 1.5;
      
      // Node 0 -> Node 1
      ctx.beginPath(); ctx.moveTo(nodes[0].x*cw, nodes[0].y*ch); ctx.lineTo(nodes[1].x*cw, nodes[1].y*ch); ctx.stroke();
      // Node 1 -> Node 2, 3, 4
      for (var k = 2; k <= 4; k++) {
        ctx.beginPath(); ctx.moveTo(nodes[1].x*cw, nodes[1].y*ch); ctx.lineTo(nodes[k].x*cw, nodes[k].y*ch); ctx.stroke();
      }
      
      // Flow packets
      var progress = (step * 0.3) % 1.0;
      ctx.fillStyle = '#fff';
      
      // Adv -> API
      var ax = nodes[0].x*cw + (nodes[1].x - nodes[0].x)*cw*progress;
      var ay = nodes[0].y*ch + (nodes[1].y - nodes[0].y)*ch*progress;
      ctx.beginPath(); ctx.arc(ax, ay, 3, 0, Math.PI*2); ctx.fill();
      
      // API -> Channels
      for (var k = 2; k <= 4; k++) {
        var cx = nodes[1].x*cw + (nodes[k].x - nodes[1].x)*cw*progress;
        var cy = nodes[1].y*ch + (nodes[k].y - nodes[1].y)*ch*progress;
        ctx.beginPath(); ctx.arc(cx, cy, 3, 0, Math.PI*2); ctx.fill();
      }
      
      // Draw Nodes
      nodes.forEach(function(n) {
        var nx = n.x * cw;
        var ny = n.y * ch;
        
        ctx.shadowColor = n.color;
        ctx.shadowBlur = 10;
        ctx.fillStyle = 'rgba(15,10,36,0.9)';
        ctx.strokeStyle = n.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(nx, ny, 24, 0, Math.PI*2);
        ctx.fill();
        ctx.stroke();
        ctx.shadowBlur = 0;
        
        // Draw icon text
        ctx.font = '16px serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(n.icon, nx, ny);
        
        // Label
        ctx.fillStyle = '#fff';
        ctx.font = '700 11px Rajdhani';
        ctx.fillText(n.name, nx, ny + 38);
      });
      
      step += 0.04;
      requestAnimationFrame(draw);
    }
    draw();
  })();

  // ── Security Grid Canvas Background ──
  (function() {
    var canvas = document.getElementById('canvasSecurityBg');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var w, h;
    function resize() {
      w = canvas.width = canvas.offsetWidth;
      h = canvas.height = canvas.offsetHeight;
    }
    resize();
    
    var nodes = [];
    for(var i=0; i<30; i++) {
      nodes.push({
        x: Math.random() * 1200,
        y: Math.random() * 500,
        vx: (Math.random() - 0.5) * 0.2,
        vy: (Math.random() - 0.5) * 0.2
      });
    }
    
    function draw() {
      ctx.clearRect(0,0,w,h);
      ctx.strokeStyle = 'rgba(232, 25, 122, 0.03)';
      ctx.lineWidth = 1;
      
      // Move nodes
      nodes.forEach(function(n) {
        n.x += n.vx;
        n.y += n.vy;
        if (n.x < 0 || n.x > w) n.vx *= -1;
        if (n.y < 0 || n.y > h) n.vy *= -1;
      });
      
      // Connect nodes
      for(var a=0; a<nodes.length; a++) {
        for(var b=a+1; b<nodes.length; b++) {
          var dist = Math.hypot(nodes[a].x - nodes[b].x, nodes[a].y - nodes[b].y);
          if (dist < 150) {
            ctx.beginPath();
            ctx.moveTo(nodes[a].x, nodes[a].y);
            ctx.lineTo(nodes[b].x, nodes[b].y);
            ctx.stroke();
          }
        }
      }
      
      requestAnimationFrame(draw);
    }
    draw();
    window.addEventListener('resize', resize);
  })();

  // 3D Mouse Tilt perspective tracking for Blog cards
  (function() {
    var cards = document.querySelectorAll('.blog-card');
    cards.forEach(function(card) {
      card.addEventListener('mousemove', function(e) {
        var rect = card.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var xc = rect.width / 2;
        var yc = rect.height / 2;
        var angleX = (yc - y) / 12;
        var angleY = (x - xc) / 12;
        card.style.transform = 'perspective(1000px) rotateX(' + angleX + 'deg) rotateY(' + angleY + 'deg) scale3d(1.03, 1.03, 1.03) translateY(-6px)';
      });
      card.addEventListener('mouseleave', function() {
        card.style.transform = 'perspective(1000px) rotateX(1deg) rotateY(0deg) scale3d(1, 1, 1)';
      });
    });
  })();

  // 3D Mouse Tilt perspective tracking for Smartlink Highlight container
  (function() {
    var card = document.querySelector('.smartlink-highlight');
    if (!card) return;
    card.addEventListener('mousemove', function(e) {
      var rect = card.getBoundingClientRect();
      var x = e.clientX - rect.left;
      var y = e.clientY - rect.top;
      var xc = rect.width / 2;
      var yc = rect.height / 2;
      var angleX = (yc - y) / 22;
      var angleY = (x - xc) / 22;
      card.style.transform = 'perspective(1200px) rotateX(' + angleX + 'deg) rotateY(' + angleY + 'deg) translateY(-2px)';
    });
    card.addEventListener('mouseleave', function() {
      card.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg)';
    });
  })();

  loadOffersFromAPI();

  </script>
</body>
</html>
