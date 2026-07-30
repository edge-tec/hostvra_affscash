<?php
/**
 * AffiliateTracker - Front Controller
 */
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

// Redirect to installer if not installed
if (!file_exists(CONFIG_PATH . '/config.json')) {
    header('Location: /install/');
    exit;
}

// ── Install-lock auto-protection ─────────────────────────────────────────────
// If config.json exists the app is installed. Ensure install/install.lock also
// exists so that if someone deletes all source files and uploads new ones, the
// first request after the upload automatically recreates the lock — preventing
// the installer wizard from destroying the existing database.
$_installLock = BASE_PATH . '/install/install.lock';
if (!file_exists($_installLock) && is_dir(BASE_PATH . '/install')) {
    @file_put_contents(
        $_installLock,
        'Installed: ' . date('Y-m-d H:i:s') . "\nAuto-protected: " . date('Y-m-d H:i:s') . "\n",
        LOCK_EX
    );
}
unset($_installLock);

// Load core classes
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Activity.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Router.php';
require BASE_PATH . '/core/FraudIQ.php';
require BASE_PATH . '/core/Mailer.php';
require BASE_PATH . '/core/InvoicePDF.php';
require BASE_PATH . '/core/Referral.php';
require BASE_PATH . '/core/Turnstile.php';
require BASE_PATH . '/core/Migrator.php';
require BASE_PATH . '/core/RejectionHelper.php';
require BASE_PATH . '/core/ExportHelper.php';
require BASE_PATH . '/core/VpnSkipList.php';
require BASE_PATH . '/core/FraudAutoNotify.php';
require BASE_PATH . '/core/RejectionNotifier.php';
require BASE_PATH . '/core/AdvBudget.php';
require BASE_PATH . '/core/Totp.php';
require BASE_PATH . '/core/Blocklist.php';
require BASE_PATH . '/core/Theme.php';
require BASE_PATH . '/core/FraudScore.php';
require BASE_PATH . '/core/PrivateOffer.php';
require BASE_PATH . '/core/ManagerPermissions.php';
require BASE_PATH . '/core/PointsService.php';
require BASE_PATH . '/core/ShopService.php';
require BASE_PATH . '/core/RewardsService.php';
require BASE_PATH . '/core/PopupService.php';
require BASE_PATH . '/core/RegistrationSecurity.php';
require BASE_PATH . '/core/RegistrationVpnGuard.php';
require BASE_PATH . '/core/RiskEngine.php';
require BASE_PATH . '/core/TrafficSourceOverride.php';
require BASE_PATH . '/core/TrafficSourceDetector.php';
require BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';
require BASE_PATH . '/core/AiSeoEngine.php';
require BASE_PATH . '/core/AiSchemaGenerator.php';
require BASE_PATH . '/core/GeoOptimizer.php';
require BASE_PATH . '/core/AiCrawlerManager.php';
require BASE_PATH . '/core/LlmsTxtGenerator.php';
require BASE_PATH . '/core/XmlSitemapGenerator.php';
require BASE_PATH . '/core/PerformanceOptimizer.php';

// Initialize config
Config::init(CONFIG_PATH);

// ── Auto-migrate ─────────────────────────────────────────────────────────────
// Checks a lock file first (O(1) — no DB query when up to date).
// When new migration files are deployed, the lock hash changes, pending
// migrations are applied automatically on the very next request.
if (Config::get('config', 'app.auto_migrate') !== false) {
    if (!Migrator::isUpToDate()) {
        Migrator::init();
        Migrator::backupBeforeMigrate(); // snapshot DB before any schema changes
        Migrator::runAll();
        Migrator::syncConfig();
    }
}

// Ensure required directories exist
foreach (['/uploads/invoices', '/uploads/support', '/storage/backups/code', '/storage/update_staging'] as $_d) {
    if (!is_dir(__DIR__ . $_d)) @mkdir(__DIR__ . $_d, 0755, true);
}
// Drop an empty index.html into /uploads/support so even a misconfigured web
// server can't list the directory contents directly.
if (is_dir(__DIR__ . '/uploads/support') && !is_file(__DIR__ . '/uploads/support/index.html')) {
    @file_put_contents(__DIR__ . '/uploads/support/index.html', '');
}
unset($_d);

// Set timezone
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// Start auth session
Auth::start();

// ── Global Blocklist Guard ───────────────────────────────────────────────────
Blocklist::guard();

// ── AI SEO Redirect Guard ───────────────────────────────────────────────────
AiSeoEngine::checkRedirect($_SERVER['REQUEST_URI'] ?? '/');

// ─── Routes ────────────────────────────────────────────────────────────────
$r = new Router();

// ── SEO: serve sitemap.xml, robots.txt, llms.txt ─────────────────────────
Router::get('/sitemap.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildSitemapIndex();
    exit;
});

Router::get('/sitemap/pages.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildPagesSitemap();
    exit;
});
Router::get('/sitemap/offers.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildOffersSitemap();
    exit;
});
Router::get('/sitemap/categories.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildCategoriesSitemap();
    exit;
});
Router::get('/sitemap/blogs.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildBlogsSitemap();
    exit;
});
Router::get('/sitemap/images.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildImagesSitemap();
    exit;
});
Router::get('/sitemap-images.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildImagesSitemap();
    exit;
});
Router::get('/sitemap/videos.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildVideosSitemap();
    exit;
});
Router::get('/sitemap-videos.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildVideosSitemap();
    exit;
});
Router::get('/sitemap-news.xml', function() {
    header('Content-Type: application/xml; charset=utf-8');
    echo XmlSitemapGenerator::buildBlogsSitemap();
    exit;
});

Router::get('/robots.txt', function() {
    header('Content-Type: text/plain; charset=utf-8');
    echo AiCrawlerManager::buildRobotsTxt();
    exit;
});

Router::get('/llms.txt', function() {
    header('Content-Type: text/markdown; charset=utf-8');
    echo LlmsTxtGenerator::buildLlmsTxt();
    exit;
});

Router::get('/llms-full.txt', function() {
    header('Content-Type: text/markdown; charset=utf-8');
    echo LlmsTxtGenerator::buildLlmsFullTxt();
    exit;
});

// Google HTML verification file (e.g. /googleXXXXXXXX.html)
Router::get('/google{code}.html', function($code) {
    $fname = 'google' . preg_replace('/[^a-z0-9]/', '', strtolower($code)) . '.html';
    $file  = BASE_PATH . '/' . $fname;
    if (file_exists($file)) {
        header('Content-Type: text/html');
        readfile($file);
    } else {
        http_response_code(404); echo '404';
    }
    exit;
});

// Root — Landing Page
Router::get('/', function() {
    // If this request comes from the configured landing domain, always show landing page
    $landingDomain = Config::get('config', 'app.landing_domain');
    if ($landingDomain) {
        $currentHost = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
        if ($currentHost === $landingDomain) {
            require BASE_PATH . '/views/landing.php';
            return;
        }
    }
    if (Auth::id()) {
        $role = Auth::role();
        Helpers::redirect("/$role/dashboard");
    }
    require BASE_PATH . '/views/landing.php';
});

// Public pages — Offers, Reviews & Blog
Router::get('/offers', function() {
    require BASE_PATH . '/controllers/public/OffersController.php';
});
Router::get('/offers/best-cpa-offers', function() {
    $_GET['route'] = 'best-cpa-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/high-paying-affiliate-offers', function() {
    $_GET['route'] = 'high-paying-affiliate-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/best-dating-cpa-offers', function() {
    $_GET['route'] = 'best-dating-cpa-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/best-finance-cpa-offers', function() {
    $_GET['route'] = 'best-finance-cpa-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/best-sweepstakes-offers', function() {
    $_GET['route'] = 'best-sweepstakes-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/best-gift-card-offers', function() {
    $_GET['route'] = 'best-gift-card-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/best-health-affiliate-offers', function() {
    $_GET['route'] = 'best-health-affiliate-offers';
    require BASE_PATH . '/controllers/public/SeoLandingController.php';
});
Router::get('/offers/{slug}', function($slug) {
    $_GET['slug'] = $slug;
    require BASE_PATH . '/controllers/public/OfferDetailController.php';
});
Router::get('/reviews', function() {
    require BASE_PATH . '/views/public/reviews.php';
});
Router::get('/blog', function() {
    require BASE_PATH . '/views/public/blog.php';
});
Router::get('/blog/{slug}', function($slug) {
    $_GET['slug'] = $slug;
    require BASE_PATH . '/views/public/blog_post.php';
});

// Category and specific SEO landing pages
Router::get('/category/{slug}', function($slug) {
    $_GET['slug'] = $slug;
    require BASE_PATH . '/controllers/public/CategoryController.php';
});

// Privacy Policy — public, no auth required.
Router::get('/privacy-policy', function() {
    require BASE_PATH . '/views/public/privacy_policy.php';
});
// Convenience aliases.
Router::get('/privacy', function() { Helpers::redirect('/privacy-policy'); });

Router::get('/terms-of-service', function() {
    require BASE_PATH . '/terms-of-service.php';
});
Router::get('/affiliate-agreement', function() {
    require BASE_PATH . '/affiliate-agreement.php';
});
Router::get('/anti-fraud-policy', function() {
    require BASE_PATH . '/anti-fraud-policy.php';
});
Router::get('/gdpr-compliance-policy', function() {
    require BASE_PATH . '/gdpr-compliance-policy.php';
});
Router::get('/refund-payment-policy', function() {
    require BASE_PATH . '/refund-payment-policy.php';
});
Router::get('/cookie-policy', function() {
    require BASE_PATH . '/cookie-policy.php';
});
Router::get('/dashboard-disclaimers', function() {
    require BASE_PATH . '/dashboard-disclaimers.php';
});

// Auth
Router::any('/login', function() {
    require BASE_PATH . '/controllers/auth/LoginController.php';
});
Router::any('/logout', function() {
    Auth::logout();
});
Router::get('/register', function() {
    Helpers::redirect('/register/affiliate');
});
Router::any('/register/affiliate', function() {
    require BASE_PATH . '/controllers/auth/RegisterAffiliateController.php';
});
Router::any('/register/advertiser', function() {
    require BASE_PATH . '/controllers/auth/RegisterAdvertiserController.php';
});
Router::any('/login/2fa', function() {
    require BASE_PATH . '/controllers/auth/TwoFactorController.php';
});
Router::get('/verify-email', function() {
    require BASE_PATH . '/controllers/auth/VerifyEmailController.php';
});
Router::any('/forgot-password', function() {
    require BASE_PATH . '/controllers/auth/ForgotPasswordController.php';
});
Router::any('/reset-password', function() {
    require BASE_PATH . '/controllers/auth/ResetPasswordController.php';
});

// /tracker/ aliases — fallback in case server routes these through index.php
// (normally Apache serves /tracker/login.php and /tracker/register.php directly)
Router::any('/tracker/login.php', function() {
    require BASE_PATH . '/tracker/login.php';
});
Router::any('/tracker/register.php', function() {
    require BASE_PATH . '/tracker/register.php';
});

// Admin shortcut — /admin redirects to /admin/dashboard
Router::any('/admin', function() { Helpers::redirect('/admin/dashboard'); });

// Admin routes
Router::any('/admin/dashboard', function() { require BASE_PATH . '/controllers/admin/DashboardController.php'; });
Router::any('/admin/affiliates', function() { require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/reactivate', function() { $_GET['action']='reactivate'; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/approve-offer', function() { $_GET['action']='approve_offer'; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/reject-offer',  function() { $_GET['action']='reject_offer';  require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/{id}/view', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/{id}/approve-offer', function($id) { $_GET['action']='approve_offer'; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/{id}/reject-offer',  function($id) { $_GET['action']='reject_offer';  require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/affiliates/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/AffiliateController.php'; });
Router::any('/admin/advertisers', function() { require BASE_PATH . '/controllers/admin/AdvertiserController.php'; });
Router::any('/admin/advertisers/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/AdvertiserController.php'; });
Router::any('/admin/advertisers/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/AdvertiserController.php'; });
Router::any('/admin/stop-impersonate', function() { require BASE_PATH . '/controllers/admin/ImpersonateController.php'; });
Router::any('/admin/offers', function() { require BASE_PATH . '/controllers/admin/OfferController.php'; });
Router::any('/admin/offers/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/OfferController.php'; });
Router::any('/admin/offers/{id}/overview', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/OfferOverviewController.php'; });
Router::any('/admin/offers/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/OfferController.php'; });

// Private Offers — restricted-visibility offers with per-affiliate access list
Router::any('/admin/private-offers', function() { require BASE_PATH . '/controllers/admin/PrivateOfferController.php'; });

// In-House Offers
Router::any('/admin/inhouse-offers', function() { require BASE_PATH . '/controllers/admin/InHouseOfferController.php'; });
Router::any('/admin/inhouse-offers/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/InHouseOfferController.php'; });
Router::any('/admin/inhouse-offers/toggle-status', function() { $_GET['action']='toggle_status'; require BASE_PATH . '/controllers/admin/InHouseOfferController.php'; });
Router::any('/admin/inhouse-offers/delete', function() { $_GET['action']='delete'; require BASE_PATH . '/controllers/admin/InHouseOfferController.php'; });
Router::any('/admin/inhouse-offers/{id}/edit', function($id) { $_GET['id']=$id; $_GET['action']='edit'; require BASE_PATH . '/controllers/admin/InHouseOfferController.php'; });
Router::any('/admin/smartlinks', function() { require BASE_PATH . '/controllers/admin/SmartlinkController.php'; });
Router::any('/admin/smartlinks/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/SmartlinkController.php'; });

// Landing Page Management
Router::any('/admin/landing/sliders', function() { require BASE_PATH . '/controllers/admin/LandingSliderController.php'; });
Router::any('/admin/landing/sliders/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/LandingSliderController.php'; });
Router::any('/admin/landing/sliders/{id}/edit', function($id) { $_GET['id']=$id; $_GET['action']='edit'; require BASE_PATH . '/controllers/admin/LandingSliderController.php'; });
Router::any('/admin/landing/sliders/{id}/delete', function($id) { $_GET['id']=$id; $_GET['action']='delete'; require BASE_PATH . '/controllers/admin/LandingSliderController.php'; });
Router::any('/admin/landing/sliders/{id}/toggle', function($id) { $_GET['id']=$id; $_GET['action']='toggle'; require BASE_PATH . '/controllers/admin/LandingSliderController.php'; });
Router::any('/admin/landing/blog', function() { require BASE_PATH . '/controllers/admin/LandingBlogController.php'; });
Router::any('/admin/landing/blog/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/LandingBlogController.php'; });
Router::any('/admin/landing/blog/{id}/edit', function($id) { $_GET['id']=$id; $_GET['action']='edit'; require BASE_PATH . '/controllers/admin/LandingBlogController.php'; });
Router::any('/admin/landing/blog/{id}/delete', function($id) { $_GET['id']=$id; $_GET['action']='delete'; require BASE_PATH . '/controllers/admin/LandingBlogController.php'; });
Router::any('/admin/landing/blog/{id}/toggle', function($id) { $_GET['id']=$id; $_GET['action']='toggle'; require BASE_PATH . '/controllers/admin/LandingBlogController.php'; });
Router::any('/admin/landing/reviews', function() { require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/{id}/edit', function($id) { $_GET['id']=$id; $_GET['action']='edit'; require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/{id}/delete', function($id) { $_GET['id']=$id; $_GET['action']='delete'; require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/{id}/toggle',  function($id) { $_GET['id']=$id; $_GET['action']='toggle';  require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/{id}/approve', function($id) { $_GET['id']=$id; $_GET['action']='approve'; require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/landing/reviews/{id}/reject',  function($id) { $_GET['id']=$id; $_GET['action']='reject';  require BASE_PATH . '/controllers/admin/LandingReviewController.php'; });
Router::any('/admin/postbacks', function() { require BASE_PATH . '/controllers/admin/PostbackController.php'; });
Router::any('/admin/postback-test', function() { require BASE_PATH . '/controllers/admin/PostbackTestController.php'; });
Router::any('/admin/affiliate-global-postbacks', function() { require BASE_PATH . '/controllers/admin/AffiliateGlobalPostbackMgmtController.php'; });
Router::any('/admin/postback-logs', function() {
    Auth::checkAny(['admin','affiliate_manager']);
    $appUrl = rtrim(Config::get('config','app.url') ?? '', '/');
    require BASE_PATH . '/views/admin/postbacks/logs.php';
});
Router::any('/admin/affiliate-global-pb-test', function() { require BASE_PATH . '/controllers/admin/AffiliateGlobalPostbackTestController.php'; });
Router::any('/admin/advertiser-postback-logs', function() { require BASE_PATH . '/controllers/admin/AdvertiserPostbackLogController.php'; });
Router::any('/admin/payment-requests', function() { require BASE_PATH . '/controllers/admin/PaymentRequestController.php'; });
Router::any('/admin/account-delete-requests', function() { require BASE_PATH . '/controllers/admin/AccountDeleteRequestController.php'; });
Router::any('/admin/offer-approvals', function() { require BASE_PATH . '/controllers/admin/OfferApprovalController.php'; });
Router::any('/admin/news', function() { require BASE_PATH . '/controllers/admin/NewsController.php'; });
Router::any('/admin/referrals', function() { require BASE_PATH . '/controllers/admin/ReferralController.php'; });
Router::any('/admin/news/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/NewsController.php'; });
Router::any('/admin/news/{id}/edit', function($id) { $_GET['action']='edit'; $_GET['id']=$id; require BASE_PATH . '/controllers/admin/NewsController.php'; });
Router::any('/admin/news/{id}/delete', function($id) { $_GET['action']='delete'; $_GET['id']=$id; require BASE_PATH . '/controllers/admin/NewsController.php'; });
Router::any('/admin/fraud', function() { require BASE_PATH . '/controllers/admin/FraudController.php'; });
Router::any('/admin/fraud-score-report', function() { require BASE_PATH . '/controllers/admin/FraudScoreReportController.php'; });
Router::any('/admin/fraud-alerts',       function() { require BASE_PATH . '/controllers/admin/FraudAlertsController.php'; });
Router::any('/admin/vpn-log', function() { require BASE_PATH . '/controllers/admin/VpnLogController.php'; });
Router::any('/admin/notifications', function() { require BASE_PATH . '/controllers/admin/NotificationController.php'; });
Router::any('/admin/registration-questions', function() { require BASE_PATH . '/controllers/admin/RegistrationQuestionsController.php'; });
Router::any('/admin/ai-seo', function() { require BASE_PATH . '/controllers/admin/AiSeoController.php'; });

// ── Enterprise REST APIs for AI SEO & LLM Discovery ─────────────────────────────
Router::get('/api/seo/metadata', function() {
    header('Content-Type: application/json; charset=utf-8');
    $url = Helpers::get('url') ?: '/';
    echo json_encode(['success' => true, 'data' => AiSeoEngine::getPageMetadata($url)]);
    exit;
});

Router::get('/api/seo/schema', function() {
    header('Content-Type: application/json; charset=utf-8');
    $url = Helpers::get('url') ?: '/';
    $aiMeta = AiSeoEngine::getPageMetadata($url);
    $schemas = [
        AiSchemaGenerator::organization(),
        AiSchemaGenerator::website(),
        AiSchemaGenerator::webpage($aiMeta['canonical_url'], $aiMeta['title'], $aiMeta['meta_description'])
    ];
    echo AiSchemaGenerator::buildGraph($schemas);
    exit;
});

Router::get('/api/seo/sitemap', function() {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'urls' => XmlSitemapGenerator::buildPagesSitemap()]);
    exit;
});

Router::get('/api/seo/faq', function() {
    header('Content-Type: application/json; charset=utf-8');
    $url = Helpers::get('url') ?: '/';
    $faqs = Database::fetchAll("SELECT question, answer FROM ai_seo_faqs WHERE target_url = ? AND is_published=1", [$url]);
    echo json_encode(['success' => true, 'faqs' => $faqs]);
    exit;
});

Router::get('/api/seo/ai-summary', function() {
    header('Content-Type: application/json; charset=utf-8');
    $url = Helpers::get('url') ?: '/';
    $meta = AiSeoEngine::getPageMetadata($url);
    echo json_encode([
        'success' => true,
        'url' => $url,
        'summary' => $meta['ai_summary'] ?? $meta['meta_description'],
        'primary_entity' => $meta['primary_entity'] ?? 'Affiliate Marketing'
    ]);
    exit;
});

Router::get('/api/seo/breadcrumb', function() {
    header('Content-Type: application/json; charset=utf-8');
    $url = Helpers::get('url') ?: '/';
    $crumbs = [
        ['name' => 'Home', 'url' => '/']
    ];
    if ($url !== '/') {
        $parts = array_filter(explode('/', $url));
        $path = '';
        foreach ($parts as $p) {
            $path .= '/' . $p;
            $crumbs[] = ['name' => ucfirst($p), 'url' => $path];
        }
    }
    echo json_encode(['success' => true, 'breadcrumbs' => $crumbs]);
    exit;
});

Router::get('/api/seo/llms-txt', function() {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'content' => LlmsTxtGenerator::buildLlmsTxt()]);
    exit;
});

Router::get('/api/seo/reports', function() {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'scores' => AiSeoEngine::getScores()]);
    exit;
});

Router::any('/admin/reports/affiliates', function() { require BASE_PATH . '/controllers/admin/AffiliateReportController.php'; });
Router::any('/admin/reports/duplicate-conversions', function() { require BASE_PATH . '/controllers/admin/DuplicateConversionsController.php'; });
Router::any('/admin/reports/traffic-source-override', function() { require BASE_PATH . '/controllers/admin/TrafficSourceOverrideReportController.php'; });
Router::any('/admin/conversions', function() { require BASE_PATH . '/controllers/admin/ConversionController.php'; });
Router::any('/admin/rejection-reasons', function() { require BASE_PATH . '/controllers/admin/RejectionReasonsController.php'; });
Router::any('/admin/vpn-proxy-skip', function() { require BASE_PATH . '/controllers/admin/VpnProxySkipController.php'; });
Router::any('/admin/settings', function() { require BASE_PATH . '/controllers/admin/SettingsController.php'; });
Router::any('/admin/system-update', function() { require BASE_PATH . '/controllers/admin/UpdateController.php'; });

// ── Points / Shop / Rewards / Popup admin (non-invasive add-on modules) ──
Router::any('/admin/points',          function() { require BASE_PATH . '/controllers/admin/PointsController.php'; });
Router::any('/admin/shop',            function() { require BASE_PATH . '/controllers/admin/ShopAdminController.php'; });
Router::any('/admin/shop/create',     function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/ShopAdminController.php'; });
Router::any('/admin/shop/edit/{id}',  function($id) { $_GET['action']='edit'; $_GET['id']=$id; require BASE_PATH . '/controllers/admin/ShopAdminController.php'; });
Router::any('/admin/shop/orders',     function() { $_GET['action']='orders'; require BASE_PATH . '/controllers/admin/ShopAdminController.php'; });
Router::any('/admin/rewards',         function() { require BASE_PATH . '/controllers/admin/RewardsAdminController.php'; });
Router::any('/admin/popup',           function() { require BASE_PATH . '/controllers/admin/PopupAdminController.php'; });
Router::any('/admin/search-console', function() { require BASE_PATH . '/controllers/admin/SearchConsoleController.php'; });
Router::any('/admin/payment-settings', function() { require BASE_PATH . '/controllers/admin/PaymentSettingsController.php'; });
Router::any('/admin/payout-management', function() { require BASE_PATH . '/controllers/admin/PayoutManagementController.php'; });
Router::any('/admin/traffic', function() { require BASE_PATH . '/controllers/admin/TrafficController.php'; });
Router::any('/admin/traffic-source-override', function() { require BASE_PATH . '/controllers/admin/TrafficSourceOverrideController.php'; });
Router::any('/admin/email', function() { require BASE_PATH . '/controllers/admin/EmailController.php'; });
Router::any('/admin/autohide', function() { require BASE_PATH . '/controllers/admin/AutoHideController.php'; });
Router::any('/admin/database', function() { require BASE_PATH . '/controllers/admin/DatabaseController.php'; });
Router::any('/admin/invoices/auto-generate', function() { require BASE_PATH . '/controllers/admin/AutoInvoiceController.php'; });
Router::any('/admin/profile', function() { require BASE_PATH . '/controllers/admin/ProfileController.php'; });
Router::any('/admin/2fa', function() { Auth::check('admin'); require BASE_PATH . '/controllers/shared/TwoFactorSettingsController.php'; });
Router::post('/admin/users/2fa-reset', function() { require BASE_PATH . '/controllers/admin/Reset2FAController.php'; });
Router::any('/admin/support', function() { require BASE_PATH . '/controllers/admin/SupportController.php'; });
Router::any('/admin/login-logs', function() { require BASE_PATH . '/controllers/admin/LoginLogsController.php'; });
Router::any('/admin/ip-bans', function() { require BASE_PATH . '/controllers/admin/IpBanController.php'; });

// ── In-House Fraud Detection System (read-only analysis module) ──────────────
Router::any('/admin/fraud-center/live-monitor',       function() { require BASE_PATH . '/controllers/admin/fraud/LiveMonitorController.php'; });
Router::any('/admin/fraud-center/auto-block-report',  function() { require BASE_PATH . '/controllers/admin/fraud/AutoBlockReportController.php'; });
Router::any('/admin/fraud-center/click-intelligence', function() { require BASE_PATH . '/controllers/admin/fraud/ClickIntelligenceController.php'; });
Router::any('/admin/fraud-center/bot-detection',      function() { require BASE_PATH . '/controllers/admin/fraud/BotDetectionController.php'; });
Router::any('/admin/fraud-center/conversion-scanner', function() { require BASE_PATH . '/controllers/admin/fraud/ConversionScannerController.php'; });
Router::any('/admin/fraud-center/risk-engine',        function() { require BASE_PATH . '/controllers/admin/fraud/RiskEngineController.php'; });
Router::any('/admin/fraud-center/ip-intelligence',    function() { require BASE_PATH . '/controllers/admin/fraud/IpIntelligenceController.php'; });
Router::any('/admin/fraud-center/cases',              function() { require BASE_PATH . '/controllers/admin/fraud/FraudCasesController.php'; });
Router::any('/admin/fraud-center/auto-rules',         function() { require BASE_PATH . '/controllers/admin/fraud/AutoRulesController.php'; });
Router::any('/admin/fraud-center/blocklist',          function() { require BASE_PATH . '/controllers/admin/fraud/BlocklistController.php'; });
Router::any('/admin/fraud-center/analytics',          function() { require BASE_PATH . '/controllers/admin/fraud/FraudAnalyticsController.php'; });
Router::any('/admin/fraud-center/fraud-reports',      function() { require BASE_PATH . '/controllers/admin/fraud/FraudReportsController.php'; });
Router::any('/admin/fraud-center/report-logs',        function() { require BASE_PATH . '/controllers/admin/fraud/FraudReportLogsController.php'; });
Router::any('/admin/ip-score-check',                   function() { require BASE_PATH . '/controllers/admin/fraud/IpScoreProxyController.php'; });

// Admin — Affiliate Managers
Router::any('/admin/affiliate-managers', function() { require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });
Router::any('/admin/affiliate-managers/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });
Router::any('/admin/affiliate-managers/permissions', function() { $_GET['action']='permissions'; require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });
Router::any('/admin/affiliate-managers/fraud-rejections', function() { $_GET['action']='fraud_rejections'; require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });
Router::any('/admin/affiliate-managers/messages', function() { require BASE_PATH . '/controllers/admin/AdminManagerMessagesController.php'; });
Router::any('/admin/affiliate-managers/invoice-requests', function() { $_GET['action']='invoice_requests'; require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });
Router::any('/admin/affiliate-managers/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/AffiliateManagerController.php'; });

// Admin — Invoices
Router::any('/admin/invoices', function() { require BASE_PATH . '/controllers/admin/InvoiceController.php'; });
Router::any('/admin/invoices/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/admin/InvoiceController.php'; });
Router::any('/admin/invoices/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/admin/InvoiceController.php'; });

// Affiliate Manager routes
Router::any('/affiliate_manager/dashboard', function() { require BASE_PATH . '/controllers/affiliate_manager/DashboardController.php'; });
Router::any('/affiliate_manager/affiliates', function() { require BASE_PATH . '/controllers/affiliate_manager/AffiliatesController.php'; });
Router::any('/affiliate_manager/conversions', function() { require BASE_PATH . '/controllers/affiliate_manager/ConversionsController.php'; });
Router::any('/affiliate_manager/reports', function() { require BASE_PATH . '/controllers/affiliate_manager/ReportsController.php'; });
Router::any('/affiliate-manager/reports', function() { require BASE_PATH . '/controllers/affiliate_manager/ReportsController.php'; });
Router::any('/affiliate_manager/duplicate-conversions', function() { require BASE_PATH . '/controllers/affiliate_manager/DuplicateConversionsController.php'; });
Router::any('/affiliate_manager/fraud-report', function() { require BASE_PATH . '/controllers/admin/fraud/FraudReportsController.php'; });
Router::any('/affiliate_manager/vpn-log', function() { require BASE_PATH . '/controllers/affiliate_manager/VpnLogController.php'; });
Router::any('/affiliate_manager/offers/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/affiliate_manager/OfferOverviewController.php'; });
Router::any('/affiliate_manager/analytics', function() { require BASE_PATH . '/controllers/affiliate_manager/AnalyticsController.php'; });
Router::any('/affiliate_manager/click_report', function() { require BASE_PATH . '/controllers/affiliate_manager/ClickReportController.php'; });
Router::any('/affiliate_manager/offers', function() { require BASE_PATH . '/controllers/affiliate_manager/OffersController.php'; });
Router::any('/affiliate_manager/inhouse-offers', function() { require BASE_PATH . '/controllers/affiliate_manager/InhouseOffersController.php'; });
Router::any('/affiliate_manager/invoices', function() { require BASE_PATH . '/controllers/affiliate_manager/InvoiceController.php'; });
Router::any('/affiliate_manager/smartlinks', function() { require BASE_PATH . '/controllers/affiliate_manager/SmartlinkController.php'; });
Router::any('/affiliate_manager/support', function() { require BASE_PATH . '/controllers/affiliate_manager/SupportController.php'; });
Router::any('/affiliate_manager/admin-messages', function() { require BASE_PATH . '/controllers/affiliate_manager/AdminMessagesController.php'; });
Router::any('/affiliate_manager/offer-approvals', function() { require BASE_PATH . '/controllers/affiliate_manager/OfferApprovalsController.php'; });
Router::any('/affiliate_manager/referral', function() { require BASE_PATH . '/controllers/affiliate_manager/ReferralController.php'; });
Router::any('/affiliate_manager/login-as-affiliate', function() { require BASE_PATH . '/controllers/affiliate_manager/LoginAsAffiliateController.php'; });
Router::any('/affiliate_manager/stop-impersonate', function() { require BASE_PATH . '/controllers/affiliate_manager/StopImpersonateController.php'; });
Router::any('/affiliate_manager/profile', function() { require BASE_PATH . '/controllers/affiliate_manager/ProfileController.php'; });
Router::any('/affiliate_manager/2fa', function() { Auth::check('affiliate_manager'); require BASE_PATH . '/controllers/shared/TwoFactorSettingsController.php'; });

// Affiliate routes
Router::any('/affiliate/dashboard', function() { require BASE_PATH . '/controllers/affiliate/DashboardController.php'; });
Router::any('/affiliate/offers', function() { require BASE_PATH . '/controllers/affiliate/OfferController.php'; });
Router::any('/affiliate/inhouse-offers', function() { require BASE_PATH . '/controllers/affiliate/InHouseOfferController.php'; });
Router::any('/affiliate/inhouse-shorten', function() { require BASE_PATH . '/controllers/affiliate/InHouseShortenController.php'; });
Router::any('/affiliate/smartlinks', function() { require BASE_PATH . '/controllers/affiliate/SmartlinkController.php'; });
Router::any('/affiliate/postbacks', function() { require BASE_PATH . '/controllers/affiliate/PostbackController.php'; });
Router::any('/affiliate/reports', function() { require BASE_PATH . '/controllers/affiliate/ReportController.php'; });
Router::any('/affiliate/duplicate-conversions', function() { require BASE_PATH . '/controllers/affiliate/DuplicateConversionsController.php'; });
Router::any('/affiliate/fraud-report',  function() { require BASE_PATH . '/controllers/affiliate/FraudReportController.php'; });
Router::any('/affiliate/fraud-reports', function() { require BASE_PATH . '/controllers/affiliate/FraudReportsController.php'; });
Router::any('/affiliate/balance', function() { require BASE_PATH . '/controllers/affiliate/BalanceController.php'; });
Router::any('/affiliate/billing-history', function() { require BASE_PATH . '/controllers/affiliate/InvoiceController.php'; });
Router::any('/affiliate/billing-history/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/affiliate/InvoiceController.php'; });
Router::any('/affiliate/invoices', function() { require BASE_PATH . '/controllers/affiliate/InvoiceController.php'; });
Router::any('/affiliate/invoices/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/affiliate/InvoiceController.php'; });
Router::any('/affiliate/profile', function() { require BASE_PATH . '/controllers/affiliate/ProfileController.php'; });

// ── Affiliate add-ons: Shop + Rewards + Popup dismiss ──
Router::any('/affiliate/shop',            function() { require BASE_PATH . '/controllers/affiliate/ShopController.php'; });
Router::any('/affiliate/rewards',         function() { require BASE_PATH . '/controllers/affiliate/RewardsController.php'; });
Router::any('/affiliate/popup/dismiss',   function() { require BASE_PATH . '/controllers/affiliate/PopupDismissController.php'; });
Router::any('/affiliate/2fa', function() { Auth::check('affiliate'); require BASE_PATH . '/controllers/shared/TwoFactorSettingsController.php'; });
Router::any('/affiliate/referral', function() { require BASE_PATH . '/controllers/affiliate/ReferralController.php'; });
Router::any('/affiliate/news', function() { require BASE_PATH . '/controllers/affiliate/NewsController.php'; });
Router::any('/affiliate/news/{id}', function($id) { $_GET['id']=$id; require BASE_PATH . '/controllers/affiliate/NewsController.php'; });
Router::any('/affiliate/shorten', function() { require BASE_PATH . '/controllers/affiliate/ShortenController.php'; });
Router::any('/affiliate/analytics', function() { require BASE_PATH . '/controllers/affiliate/AnalyticsController.php'; });
Router::any('/affiliate/support', function() { require BASE_PATH . '/controllers/affiliate/SupportController.php'; });
Router::any('/affiliate/manager', function() { require BASE_PATH . '/controllers/affiliate/ManagerController.php'; });
Router::any('/affiliate/delete-account', function() { require BASE_PATH . '/controllers/affiliate/AccountDeleteRequestController.php'; });

// Advertiser routes
Router::any('/advertiser/dashboard', function() { require BASE_PATH . '/controllers/advertiser/DashboardController.php'; });
Router::any('/advertiser/offers', function() { require BASE_PATH . '/controllers/advertiser/OfferController.php'; });
Router::any('/advertiser/offers/create', function() { $_GET['action']='create'; require BASE_PATH . '/controllers/advertiser/OfferController.php'; });
Router::any('/advertiser/reports', function() { require BASE_PATH . '/controllers/advertiser/ReportController.php'; });
// Advertiser Reporting System — click, performance, and offer reports.
Router::any('/advertiser/reports/clicks',      function() { require BASE_PATH . '/controllers/advertiser/ClickReportController.php'; });
Router::any('/advertiser/reports/conversions', function() { require BASE_PATH . '/controllers/advertiser/ConversionReportController.php'; });
Router::any('/advertiser/reports/performance', function() { require BASE_PATH . '/controllers/advertiser/PerformanceReportController.php'; });
Router::any('/advertiser/reports/offers',      function() { require BASE_PATH . '/controllers/advertiser/OfferReportController.php'; });
Router::any('/advertiser/postback-logs', function() { require BASE_PATH . '/controllers/advertiser/PostbackLogController.php'; });
Router::any('/advertiser/billing', function() { require BASE_PATH . '/controllers/advertiser/BillingController.php'; });
Router::any('/advertiser/billing/top-up', function() { $_GET['action']='top_up'; require BASE_PATH . '/controllers/advertiser/BillingController.php'; });
Router::any('/advertiser/api/balance', function() { require BASE_PATH . '/controllers/advertiser/BalanceApiController.php'; });

// API routes
Router::any('/api/shorten', function() { require BASE_PATH . '/controllers/api/ShortlinkController.php'; });
Router::any('/api/chat', function() { require BASE_PATH . '/controllers/api/ChatController.php'; });
Router::any('/api/notifications', function() { require BASE_PATH . '/controllers/api/NotificationsController.php'; });
Router::any('/api/fraud-alerts', function() { require BASE_PATH . '/controllers/api/FraudAlertsController.php'; });
Router::any('/api/stats', function() { require BASE_PATH . '/controllers/api/StatsController.php'; });
Router::any('/api/advertiser-analytics', function() { require BASE_PATH . '/controllers/api/AdvertiserAnalyticsController.php'; });

// Native App API Routes (v2)
Router::any('/api/v2/auth', function() { require BASE_PATH . '/api/v2/AuthController.php'; });
Router::any('/api/v2/dashboard', function() { require BASE_PATH . '/api/v2/DashboardController.php'; });
Router::any('/api/v2/offers', function() { require BASE_PATH . '/api/v2/OfferController.php'; });
Router::any('/api/v2/smartlinks', function() { require BASE_PATH . '/api/v2/SmartlinkController.php'; });
Router::any('/api/v2/reports', function() { require BASE_PATH . '/api/v2/ReportController.php'; });
Router::any('/api/v2/news', function() { require BASE_PATH . '/api/v2/NewsController.php'; });
Router::any('/api/v2/fraud-report', function() { require BASE_PATH . '/api/v2/FraudReportController.php'; });
Router::any('/api/v2/duplicate-conversions', function() { require BASE_PATH . '/api/v2/DuplicateConversionsController.php'; });
Router::any('/api/v2/referral', function() { require BASE_PATH . '/api/v2/ReferralController.php'; });
Router::any('/api/v2/rewards', function() { require BASE_PATH . '/api/v2/RewardsController.php'; });
Router::any('/api/v2/shop', function() { require BASE_PATH . '/api/v2/ShopController.php'; });
Router::any('/api/v2/invoices', function() { require BASE_PATH . '/api/v2/InvoiceController.php'; });
Router::any('/api/v2/settings', function() { require BASE_PATH . '/api/v2/SettingsController.php'; });
Router::any('/api/v2/chat', function() { require BASE_PATH . '/api/v2/ChatController.php'; });
Router::any('/api/v2/notifications', function() { require BASE_PATH . '/api/v2/NotificationsController.php'; });

// Native App API Routes (v2) - Admin
Router::any('/api/v2/admin/dashboard', function() { require BASE_PATH . '/api/v2/admin/DashboardController.php'; });
Router::any('/api/v2/admin/offers', function() { require BASE_PATH . '/api/v2/admin/OfferController.php'; });
Router::post('/api/v2/admin/offers/action', function() { require BASE_PATH . '/api/v2/admin/OfferActionController.php'; });

Router::any('/api/v2/admin/smartlinks', function() { require BASE_PATH . '/api/v2/admin/SmartlinkController.php'; });

Router::get('/api/v2/admin/private-offers/dashboard', function() { require BASE_PATH . '/api/v2/admin/PrivateOfferDashboardController.php'; });
Router::get('/api/v2/admin/private-offers/detail', function() { require BASE_PATH . '/api/v2/admin/PrivateOfferDetailController.php'; });
Router::post('/api/v2/admin/private-offers/action', function() { require BASE_PATH . '/api/v2/admin/PrivateOfferActionController.php'; });
Router::any('/api/v2/admin/offer-approvals', function() { require BASE_PATH . '/api/v2/admin/OfferApprovalController.php'; });
Router::any('/api/v2/admin/affiliates', function() { require BASE_PATH . '/api/v2/admin/AffiliateController.php'; });
Router::any('/api/v2/admin/affiliate-managers', function() { require BASE_PATH . '/api/v2/admin/AffiliateManagerController.php'; });
Router::any('/api/v2/admin/affiliate-actions', function() { require BASE_PATH . '/api/v2/admin/AffiliateActionController.php'; });
Router::any('/api/v2/admin/advertisers', function() { require BASE_PATH . '/api/v2/admin/AdvertiserController.php'; });
Router::any('/api/v2/admin/advertiser-actions', function() { require BASE_PATH . '/api/v2/admin/AdvertiserActionController.php'; });
Router::any('/api/v2/admin/conversions', function() { require BASE_PATH . '/api/v2/admin/ConversionController.php'; });
Router::any('/api/v2/admin/reports', function() { require BASE_PATH . '/api/v2/admin/ReportController.php'; });
Router::any('/api/v2/admin/autohide', function() { require BASE_PATH . '/api/v2/admin/AutoHideController.php'; });
Router::any('/api/v2/admin/affiliate-report', function() { require BASE_PATH . '/api/v2/admin/AffiliateReportController.php'; });
Router::any('/api/v2/admin/invoices', function() { require BASE_PATH . '/api/v2/admin/InvoiceController.php'; });
Router::any('/api/v2/admin/fraud-score-report', function() { require BASE_PATH . '/api/v2/admin/FraudScoreReportController.php'; });
Router::any('/api/v2/admin/account-delete-requests', function() { require BASE_PATH . '/api/v2/admin/AccountDeleteRequestController.php'; });
Router::any('/api/v2/admin/settings', function() { require BASE_PATH . '/api/v2/admin/SettingsController.php'; });
Router::any('/api/v2/admin/chat', function() { require BASE_PATH . '/api/v2/admin/ChatController.php'; });
Router::any('/api/v2/admin/shop', function() { require BASE_PATH . '/api/v2/admin/ShopController.php'; });
Router::any('/api/v2/admin/referral', function() { require BASE_PATH . '/api/v2/admin/ReferralController.php'; });
Router::any('/api/v2/admin/points', function() { require BASE_PATH . '/api/v2/admin/PointsController.php'; });
Router::any('/api/v2/admin/payment-settings', function() { require BASE_PATH . '/api/v2/admin/PaymentSettingsController.php'; });
Router::any('/api/v2/admin/vpn-logs', function() { require BASE_PATH . '/api/v2/admin/VpnLogController.php'; });
Router::any('/api/v2/admin/duplicate_conversions', function() { require BASE_PATH . '/api/v2/admin/DuplicateConversionsController.php'; });
Router::any('/api/v2/admin/traffic-source-override', function() { require BASE_PATH . '/api/v2/admin/TrafficSourceOverrideController.php'; });
Router::any('/api/v2/admin/traffic-source-override-logs', function() { require BASE_PATH . '/api/v2/admin/TrafficSourceOverrideLogsController.php'; });
Router::any('/api/v2/admin/login-activity', function() { require BASE_PATH . '/api/v2/admin/LoginActivityController.php'; });
Router::any('/api/v2/admin/vpn-proxy-skip', function() { require BASE_PATH . '/api/v2/admin/VpnProxySkipController.php'; });

// Native App API Routes (v2) - Manager
Router::any('/api/v2/manager/dashboard', function() { require BASE_PATH . '/api/v2/manager/DashboardController.php'; });
Router::any('/api/v2/manager/offers', function() { require BASE_PATH . '/api/v2/manager/OfferController.php'; });
Router::any('/api/v2/manager/affiliates', function() { require BASE_PATH . '/api/v2/manager/AffiliateController.php'; });
Router::any('/api/v2/manager/affiliate-actions', function() { require BASE_PATH . '/api/v2/manager/AffiliateActionController.php'; });
Router::any('/api/v2/manager/conversions', function() { require BASE_PATH . '/api/v2/manager/ConversionController.php'; });
Router::any('/api/v2/manager/invoices', function() { require BASE_PATH . '/api/v2/manager/InvoiceController.php'; });
Router::any('/api/v2/manager/profile', function() { require BASE_PATH . '/api/v2/manager/ProfileController.php'; });
Router::any('/api/v2/manager/traffic-source-override', function() { require BASE_PATH . '/api/v2/manager/TrafficSourceOverrideController.php'; });
Router::any('/api/v2/manager/traffic-source-override-logs', function() { require BASE_PATH . '/api/v2/manager/TrafficSourceOverrideLogsController.php'; });
Router::any('/api/v2/manager/reports', function() { require BASE_PATH . '/api/v2/manager/ReportController.php'; });
Router::any('/api/v2/manager/referral', function() { require BASE_PATH . '/api/v2/manager/ReferralController.php'; });
Router::any('/api/v2/manager/vpn-logs', function() { require BASE_PATH . '/api/v2/manager/VpnLogController.php'; });
Router::any('/api/v2/manager/smartlinks', function() { require BASE_PATH . '/api/v2/manager/SmartlinkController.php'; });
Router::any('/api/v2/manager/offer-approvals', function() { require BASE_PATH . '/api/v2/manager/OfferApprovalController.php'; });
Router::any('/api/v2/manager/duplicate_conversions', function() { require BASE_PATH . '/api/v2/manager/DuplicateConversionsController.php'; });
Router::any('/api/v2/manager/fraud-report', function() { require BASE_PATH . '/api/v2/manager/FraudReportController.php'; });
Router::any('/api/v2/manager/chat', function() { require BASE_PATH . '/api/v2/manager/ChatController.php'; });

Router::any('/api/v2/stop-impersonate', function() { require BASE_PATH . '/api/v2/StopImpersonateController.php'; });

// Web shell for the affiliate-inactivity cron — lets admins schedule the
// scan from cPanel "Cron Jobs" (URL mode) or fire it from a browser when
// they don't have SSH crontab access. Token-gated (Settings → Inactivity).
Router::any('/cron/affiliate-inactivity', function() { require BASE_PATH . '/controllers/cron/AffiliateInactivityController.php'; });

// Web shell for the fraud-scan cron (IPQS + multi-provider). Same token-
// gated pattern; admin can paste the URL into cPanel → Cron Jobs.
Router::any('/cron/fraud-scan',    function() { require BASE_PATH . '/controllers/cron/FraudScanController.php'; });
Router::any('/cron/fraud-reports', function() { require BASE_PATH . '/controllers/cron/FraudReportsCronController.php'; });
Router::any('/api/affiliate-analytics', function() { require BASE_PATH . '/controllers/api/AffiliateAnalyticsController.php'; });
Router::any('/api/admin-analytics', function() { require BASE_PATH . '/controllers/api/AdminAnalyticsController.php'; });
Router::any('/api/activity', function() { require BASE_PATH . '/controllers/api/ActivityController.php'; });
Router::any('/api/theme',    function() { require BASE_PATH . '/controllers/api/ThemeController.php'; });
Router::any('/policy/{slug}', function($slug) { $_GET['slug']=$slug; require BASE_PATH . '/controllers/shared/PolicyController.php'; });

// Tracking (public - no auth)
// In-House Offer Tracking: /offer/{id}?aff_id={code}&click_id={ext}&sub_id={sub}
Router::any('/offer/{offerId}', function($offerId) {
    $_GET['offer_id'] = (int)$offerId;
    require BASE_PATH . '/tracking/inhouse_click.php';
});

// In-House Short Links: /s/{code}
Router::any('/s/{code}', function($code) {
    $_GET['code'] = $code;
    require BASE_PATH . '/tracking/short_link.php';
});

Router::any('/click/{offerId}', function($offerId) {
    $_GET['offer_id'] = $offerId;
    require BASE_PATH . '/tracking/click.php';
});
Router::any('/test-postback-click', function() {
    require BASE_PATH . '/tracking/test_postback_click.php';
});
Router::any('/postback', function() {
    require BASE_PATH . '/tracking/postback.php';
});
Router::any('/pixel', function() {
    require BASE_PATH . '/tracking/pixel.php';
});
Router::any('/impression', function() {
    $_GET['type'] = 'imp';
    require BASE_PATH . '/tracking/pixel.php';
});
Router::any('/smartlink/{slug}', function($slug) {
    $_GET['slug'] = $slug;
    require BASE_PATH . '/tracking/smartlink.php';
});

// Dispatch
try {
    $requestUri = $_SERVER['REQUEST_URI'];
    // Alias /manager/* to /affiliate_manager/* for legacy links (e.g., existing notifications)
    if (preg_match('#^/manager/#', $requestUri)) {
        $requestUri = preg_replace('#^/manager/#', '/affiliate_manager/', $requestUri);
    }
    Router::dispatch($requestUri, $_SERVER['REQUEST_METHOD']);
} catch (\Throwable $e) {
    file_put_contents(BASE_PATH . '/api_error.log', date('Y-m-d H:i:s') . ' ' . $_SERVER['REQUEST_URI'] . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Server Error: ' . $e->getMessage()
        ]);
    } else {
        throw $e;
    }
}
