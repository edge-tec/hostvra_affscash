<?php
/**
 * Public-page Enterprise AI SEO <head> partial.
 *
 * Renders canonical URL, meta tags, Open Graph, Twitter Cards, AI Summaries,
 * Performance Resource Hints, and validated Schema.org JSON-LD graph.
 */

// Load AI SEO core engines
require_once BASE_PATH . '/core/AiSeoEngine.php';
require_once BASE_PATH . '/core/AiSchemaGenerator.php';
require_once BASE_PATH . '/core/GeoOptimizer.php';
require_once BASE_PATH . '/core/PerformanceOptimizer.php';

AiSeoEngine::initSchema();

$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$aiMeta = AiSeoEngine::getPageMetadata($reqUri);

$_seoSiteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
$_seoTitle = $seoTitle ?? $aiMeta['title'] ?? 'Affscash — Best High Paying CPA Affiliate Network';
$_seoDesc  = $seoDescription ?? $aiMeta['meta_description'] ?? 'Affscash is a global CPA Affiliate Network providing exclusive high-paying offers, AI Smartlink technology, and 24/7 support.';
$_seoKeywords = $seoKeywords ?? $aiMeta['meta_keywords'] ?? 'CPA network, affiliate marketing, smartlink, high paying offers';
$_seoCanonical = $seoCanonical ?? $aiMeta['canonical_url'] ?? ($_seoSiteUrl . strtok($reqUri, '?'));
$_seoRobots = !empty($seoNoindex) ? 'noindex, nofollow' : ($aiMeta['robots_meta'] ?? 'index, follow');

$_ogTitle = $aiMeta['og_title'] ?? $_seoTitle;
$_ogDesc  = $aiMeta['og_description'] ?? $_seoDesc;
$_ogImg   = $aiMeta['og_image'] ?? ($_seoSiteUrl . '/logoo.png');

$_twTitle = $aiMeta['twitter_title'] ?? $_seoTitle;
$_twDesc  = $aiMeta['twitter_description'] ?? $_seoDesc;
$_twImg   = $aiMeta['twitter_image'] ?? $_ogImg;

$_aiSummary = $aiMeta['ai_summary'] ?? $_seoDesc;
$_primaryEntity = $aiMeta['primary_entity'] ?? 'Affiliate Marketing';

// Build Schema Graph
$schemas = [];
$schemas[] = AiSchemaGenerator::organization();
$schemas[] = AiSchemaGenerator::website();
$schemas[] = AiSchemaGenerator::webpage($_seoCanonical, $_seoTitle, $_seoDesc);

// Fetch FAQs for page if exist
try {
    $faqs = Database::fetchAll("SELECT question, answer FROM ai_seo_faqs WHERE target_url = ? AND is_published=1", [$aiMeta['page_url']]);
    if (!empty($faqs)) {
        $faqSchema = AiSchemaGenerator::faqPage($faqs);
        if ($faqSchema) $schemas[] = $faqSchema;
    }
} catch (\Throwable $_e) {}

// If custom schema provided by caller
if (!empty($seoSchema) && is_array($seoSchema)) {
    $schemas[] = $seoSchema;
}

$schemaGraphJson = AiSchemaGenerator::buildGraph($schemas);
?>
<!-- Core Title & Meta Tags -->
<title><?= htmlspecialchars($_seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($_seoDesc, ENT_QUOTES, 'UTF-8') ?>">
<meta name="keywords" content="<?= htmlspecialchars($_seoKeywords, ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="<?= htmlspecialchars($_seoRobots, ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars($_seoCanonical, ENT_QUOTES, 'UTF-8') ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($_seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($_ogTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($_ogDesc, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars($_ogImg, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="Affscash">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= htmlspecialchars($_seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($_twTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($_twDesc, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($_twImg, ENT_QUOTES, 'UTF-8') ?>">

<!-- AI SEO & GEO Discoverability Meta -->
<meta name="ai-summary" content="<?= htmlspecialchars($_aiSummary, ENT_QUOTES, 'UTF-8') ?>">
<meta name="primary-entity" content="<?= htmlspecialchars($_primaryEntity, ENT_QUOTES, 'UTF-8') ?>">
<meta name="reading-time" content="<?= (int)($aiMeta['reading_time'] ?? 2) ?> min">

<!-- Performance Resource Hints -->
<?= PerformanceOptimizer::renderResourceHints() ?>

<?php 
$fav = (class_exists('Config')) ? Config::get('config','app.favicon') : null;
if (!$fav) { $fav = '/x-icon.png'; }
?>
<link rel="icon" href="<?= Helpers::e($fav) ?>" type="image/png">

<!-- JSON-LD Schema Graph -->
<script type="application/ld+json">
<?= $schemaGraphJson ?>
</script>
