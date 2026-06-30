<?php
/**
 * Public-page SEO <head> partial.
 *
 * Renders canonical URL, meta tags, robots directives, verification meta,
 * and analytics/tag-manager snippets from the seo_settings table populated
 * by /admin/search-console. Safe to include before any other head links —
 * outputs nothing if a setting is empty.
 *
 * Caller-overridable variables (set before include):
 *   $seoTitle       — overrides default <title>
 *   $seoDescription — overrides admin meta_description
 *   $seoCanonical   — overrides auto-computed canonical URL
 *   $seoNoindex     — bool, force noindex on this page
 */

// ── One-time seo_settings reader (per request) ─────────────────────────────
if (!function_exists('seoHeadGet')) {
    function seoHeadGet(string $key, string $default = ''): string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                $rows = Database::fetchAll("SELECT setting_key, setting_value FROM seo_settings");
                foreach ($rows as $r) $cache[$r['setting_key']] = (string)($r['setting_value'] ?? '');
            } catch (\Throwable $_e) { /* table may not exist on fresh installs */ }
        }
        return $cache[$key] ?? $default;
    }
}

$_seoSiteUrl     = rtrim(seoHeadGet('site_url', Config::get('config', 'app.url') ?? ''), '/');
$_seoDesc        = $seoDescription ?? seoHeadGet('meta_description');
$_seoKeywords    = seoHeadGet('meta_keywords');
$_seoVerifyMeta  = seoHeadGet('verification_meta');
$_seoGaId        = seoHeadGet('google_analytics_id');
$_seoGtmId       = seoHeadGet('google_tag_manager_id');
$_seoIndex       = seoHeadGet('robots_index',  '1') !== '0';
$_seoFollow      = seoHeadGet('robots_follow', '1') !== '0';
if (!empty($seoNoindex)) $_seoIndex = false;

// Auto-canonical: strip query string by default, but allow callers to override.
$_seoCanonical = $seoCanonical ?? null;
if ($_seoCanonical === null && $_seoSiteUrl !== '') {
    $_path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $_seoCanonical = $_seoSiteUrl . ($_path === false ? '/' : $_path);
}
?>
<?php if (!empty($seoTitle)): ?>
<title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php endif; ?>
<?php if ($_seoDesc !== ''): ?>
<meta name="description" content="<?= htmlspecialchars($_seoDesc, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if ($_seoKeywords !== ''): ?>
<meta name="keywords" content="<?= htmlspecialchars($_seoKeywords, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<meta name="robots" content="<?= ($_seoIndex ? 'index' : 'noindex') ?>, <?= ($_seoFollow ? 'follow' : 'nofollow') ?>">
<?php if ($_seoCanonical): ?>
<link rel="canonical" href="<?= htmlspecialchars($_seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if ($_seoVerifyMeta !== ''): ?>
<?php
// Accept either the full <meta ...> tag pasted from Search Console,
// or just the content="..." value, and emit a clean <meta> in both cases.
$_seoVerifyContent = $_seoVerifyMeta;
if (preg_match('/content\s*=\s*"([^"]+)"/i', $_seoVerifyMeta, $_m)) $_seoVerifyContent = $_m[1];
?>
<meta name="google-site-verification" content="<?= htmlspecialchars($_seoVerifyContent, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if ($_seoGtmId !== ''): /* GTM head snippet */ ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= htmlspecialchars($_seoGtmId, ENT_QUOTES, 'UTF-8') ?>');</script>
<?php endif; ?>
<?php if ($_seoGaId !== ''): /* GA4 tag */ ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($_seoGaId, ENT_QUOTES, 'UTF-8') ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($_seoGaId, ENT_QUOTES, 'UTF-8') ?>');</script>
<?php endif; ?>
<?php 
$fav = (class_exists('Config')) ? Config::get('config','app.favicon') : null;
if (!$fav) { $fav = '/x-icon.png'; }
?>
<link rel="icon" href="<?= Helpers::e($fav) ?>" type="image/png">
