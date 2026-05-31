<?php
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/core/functions.php';
    $siteName      = setting('site_name', 'EdgeCash');
    $siteUrl       = defined('SITE_URL') ? SITE_URL : '';
    $logoPath      = setting('logo_path', '');
    $loginLogoPath = setting('login_logo_path', '');
    $faviconPath   = setting('favicon_path', '');
    $footerCopyright   = setting('footer_copyright', 'All rights reserved. Built for performance marketers worldwide.');
    $footerPoweredBy   = setting('footer_powered_by', 'Powered by EdgeSoft Ltd');
    $landingCookieTheme = in_array($_COOKIE['pref_theme'] ?? '', ['dark','light']) ? $_COOKIE['pref_theme'] : null;
    $landingGlobalDefault = in_array(setting('default_theme','dark'), ['dark','light']) ? setting('default_theme','dark') : 'dark';
    $landingTheme = $landingCookieTheme ?? $landingGlobalDefault;
} else {
    $siteName = 'EdgeCash'; $siteUrl = ''; $logoPath = ''; $loginLogoPath = '';
    $faviconPath = '';
    $footerCopyright = 'All rights reserved. Built for performance marketers worldwide.';
    $footerPoweredBy = 'Powered by EdgeSoft Ltd';
    $landingTheme = 'dark';
}
$navLogo = $loginLogoPath ?: $logoPath;
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($landingTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Affiliate Agreement — Affscash</title>
<?php if ($faviconPath): ?>
<link rel="icon" href="<?= htmlspecialchars($faviconPath) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect fill='%237c3aed' width='32' height='32' rx='6'/><text x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-size='18' font-weight='800'>E</text></svg>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --p:#7c3aed;--pl:#a78bfa;--pd:#5b21b6;
  --cyan:#06b6d4;--green:#10b981;--orange:#f97316;--yellow:#f59e0b;--red:#ef4444;
  --bg:#06080f;--bg2:#0b0f1e;--bg3:#0f1428;
  --card:rgba(255,255,255,.03);--card2:rgba(255,255,255,.06);
  --border:rgba(255,255,255,.07);--border2:rgba(124,58,237,.25);
  --text:#f1f5f9;--muted:#64748b;--muted2:#94a3b8;
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden}
a{text-decoration:none}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:999;height:68px;
    display:flex;align-items:center;padding:0 6%;gap:32px;
    background:rgba(6,8,15,.8);backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);transition:.3s}
nav.scrolled{box-shadow:0 8px 40px rgba(0,0,0,.5)}
.nav-logo{font-size:21px;font-weight:900;letter-spacing:-.5px;
  background:linear-gradient(135deg,#c4b5fd,#7c3aed 50%,#06b6d4);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex-shrink:0}
.nav-spacer{flex:1}
.nav-actions{display:flex;gap:8px;flex-shrink:0;align-items:center}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:10px;
     font-size:13.5px;font-weight:600;border:none;cursor:pointer;transition:all .2s;white-space:nowrap}
.btn-ghost{background:transparent;border:1px solid var(--border);color:var(--muted2)}
.btn-ghost:hover{border-color:var(--pl);color:var(--pl)}
.btn-primary{background:linear-gradient(135deg,var(--p),#6d28d9);color:#fff;
  box-shadow:0 4px 20px rgba(124,58,237,.35)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(124,58,237,.5)}
.landing-logo-img{display:block}

/* Dark theme: force logos to white */
html:not([data-theme="light"]) .landing-logo-img { filter: brightness(0) invert(1); }

/* ── HERO / PAGE HEADER ── */
.page-hero{padding:120px 6% 64px;position:relative;overflow:hidden}
.page-hero::before{content:'';position:fixed;top:-100px;left:-100px;width:600px;height:600px;
  background:rgba(124,58,237,.12);border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0}
.page-hero::after{content:'';position:fixed;bottom:-100px;right:-50px;width:500px;height:500px;
  background:rgba(6,182,212,.07);border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0}
.page-hero-inner{max-width:860px;margin:0 auto;position:relative;z-index:1}
.page-hero-eyebrow{display:inline-flex;align-items:center;gap:8px;
  background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.3);
  border-radius:24px;padding:5px 14px 5px 10px;font-size:12px;color:var(--pl);
  margin-bottom:24px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
.page-hero-eyebrow::before{content:'';width:7px;height:7px;background:var(--pl);border-radius:50%}
.page-hero h1{font-size:clamp(28px,4vw,52px);font-weight:900;line-height:1.1;
  letter-spacing:-1.5px;margin-bottom:16px}
.page-hero h1 .grad{background:linear-gradient(135deg,#c4b5fd 0%,#7c3aed 40%,#06b6d4 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.page-hero-meta{display:flex;flex-wrap:wrap;gap:24px;margin-top:24px;padding-top:24px;
  border-top:1px solid var(--border)}
.page-hero-meta-item{font-size:13px;color:var(--muted2)}
.page-hero-meta-item strong{color:var(--text);display:block;font-weight:600;margin-bottom:2px}

/* ── LAYOUT ── */
.tos-layout{max-width:1120px;margin:0 auto;padding:0 6% 80px;
  display:grid;grid-template-columns:240px 1fr;gap:56px;align-items:start}

/* ── SIDEBAR TOC ── */
.toc-wrap{position:sticky;top:88px;align-self:start;max-height:calc(100vh - 108px);display:flex;flex-direction:column}
.toc{
  background:linear-gradient(180deg,rgba(124,58,237,.08),rgba(6,182,212,.04)),var(--card2);
  border:1px solid var(--border2);
  border-radius:16px;padding:18px;
  box-shadow:0 8px 32px rgba(124,58,237,.12),0 2px 8px rgba(0,0,0,.2);
  backdrop-filter:blur(10px);
  display:flex;flex-direction:column;min-height:0;overflow:hidden}
.toc-title{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;
  letter-spacing:.1em;margin:0 0 12px;padding-bottom:10px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;cursor:default;flex-shrink:0}
.toc-title-label{display:flex;align-items:center;gap:8px}
.toc-title-icon{width:14px;height:14px;color:var(--pl)}
.toc-progress{height:3px;background:rgba(124,58,237,.1);border-radius:2px;overflow:hidden;margin-bottom:14px;flex-shrink:0}
.toc-progress-bar{height:100%;background:linear-gradient(90deg,var(--pl),var(--cyan));
  width:0%;transition:width .25s ease-out;border-radius:2px;
  box-shadow:0 0 8px rgba(124,58,237,.5)}
.toc-toggle{display:none;background:none;border:none;color:var(--pl);font-size:18px;cursor:pointer;padding:0;line-height:1;transition:transform .25s}
.toc[data-open="false"] .toc-toggle{transform:rotate(-90deg)}
.toc-scroll{overflow-y:auto;overflow-x:hidden;min-height:0;margin:0 -6px;padding:0 6px;
  scrollbar-width:thin;scrollbar-color:rgba(124,58,237,.3) transparent}
.toc-scroll::-webkit-scrollbar{width:5px}
.toc-scroll::-webkit-scrollbar-track{background:transparent}
.toc-scroll::-webkit-scrollbar-thumb{background:rgba(124,58,237,.25);border-radius:3px}
.toc-scroll::-webkit-scrollbar-thumb:hover{background:rgba(124,58,237,.45)}
.toc-list{list-style:none;display:flex;flex-direction:column;gap:1px;margin:0;padding:0}
.toc-list li{margin:0;padding:0}
.toc-list a{font-size:12.5px;color:var(--muted2);display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:8px;transition:all .2s ease;line-height:1.35;font-weight:500;
  position:relative;border-left:2px solid transparent}
.toc-list a::before{content:'';position:absolute;left:-1px;top:50%;width:3px;height:0;
  background:linear-gradient(180deg,var(--pl),var(--cyan));border-radius:2px;
  transform:translateY(-50%);transition:height .25s ease}
.toc-list a:hover{color:var(--pl);background:rgba(124,58,237,.08);transform:translateX(2px)}
.toc-list a:hover .toc-num{background:rgba(124,58,237,.18);color:var(--pl)}
.toc-list a.active{color:var(--pl);background:rgba(124,58,237,.12);font-weight:600;
  box-shadow:inset 0 0 0 1px rgba(124,58,237,.18)}
.toc-list a.active::before{height:60%}
.toc-list a.active .toc-num{color:#fff;background:linear-gradient(135deg,var(--pl),var(--p));
  box-shadow:0 2px 8px rgba(124,58,237,.4)}
.toc-num{font-size:10px;color:var(--muted);font-weight:700;font-variant-numeric:tabular-nums;
  background:rgba(124,58,237,.08);border-radius:5px;padding:2px 6px;flex-shrink:0;min-width:26px;
  text-align:center;transition:all .2s ease}
.toc-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.toc-list a.active .toc-text{white-space:normal}
.toc-footer{margin-top:14px;padding-top:12px;border-top:1px solid var(--border);flex-shrink:0}
.toc-back-top{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;
  padding:8px;border-radius:8px;background:rgba(124,58,237,.06);border:1px solid var(--border);
  color:var(--muted2);font-size:11.5px;font-weight:600;cursor:pointer;transition:all .2s;
  text-transform:uppercase;letter-spacing:.05em}
.toc-back-top:hover{background:rgba(124,58,237,.12);color:var(--pl);border-color:rgba(124,58,237,.3)}
.toc-back-top svg{width:12px;height:12px}

/* ── CONTENT ── */
.tos-content{min-width:0;display:flex;flex-direction:column;gap:16px}

.notice-block{background:var(--card);border:1px solid var(--border);
  border-radius:16px;padding:24px 28px;margin-bottom:32px;font-size:14.5px;color:var(--text);
  line-height:1.7;box-shadow:0 8px 32px rgba(0,0,0,.1);
  position:relative;overflow:hidden}
.notice-block::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--pl)}
.notice-block strong{color:var(--pl);font-weight:700;letter-spacing:.02em}

/* Accordion Section */
.tos-section{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05);transition:all .3s ease;opacity:0;transform:translateY(20px)}
.tos-section.visible{opacity:1;transform:translateY(0)}
.tos-section.is-open{border-color:var(--pl);box-shadow:0 8px 24px rgba(124,58,237,.15)}

.accordion-header{display:flex;align-items:center;padding:20px 24px;cursor:pointer;user-select:none;gap:16px;transition:background .2s}
.accordion-header:hover{background:var(--card2)}

.section-number{font-size:12px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--pl),var(--p));border-radius:8px;padding:4px 10px;flex-shrink:0;box-shadow:0 2px 8px rgba(124,58,237,.4)}
.tos-section h2{font-size:16px;font-weight:700;color:var(--text);margin:0;flex:1;letter-spacing:-.2px}

.accordion-icon{width:20px;height:20px;color:var(--muted);transition:transform .3s ease}
.tos-section.is-open .accordion-icon{transform:rotate(180deg);color:var(--pl)}

.accordion-content{display:none;padding:24px;background:rgba(255,255,255,.01);border-top:1px solid var(--border)}
.tos-section.is-open .accordion-content{display:block}

.tos-section h3{font-size:14px;font-weight:700;color:var(--text);margin:24px 0 12px;text-transform:uppercase;letter-spacing:.06em}
.tos-section h3:first-child{margin-top:0}
.tos-section p{font-size:14.5px;color:var(--muted2);line-height:1.85;margin-bottom:14px}
.tos-section p:last-child{margin-bottom:0}

.tos-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0}
.tos-list li{font-size:14px;color:var(--muted2);line-height:1.75;padding-left:20px;position:relative}
.tos-list li::before{content:'›';position:absolute;left:0;color:var(--pl);font-weight:700;font-size:16px;line-height:1.5}
.tos-list li strong{color:var(--text)}

.def-list{list-style:none;display:flex;flex-direction:column;gap:0;
  border:1px solid var(--border);border-radius:12px;overflow:hidden;margin:16px 0}
.def-list li{display:grid;grid-template-columns:180px 1fr;gap:16px;
  padding:14px 18px;border-bottom:1px solid var(--border);font-size:13.5px}
.def-list li:last-child{border-bottom:none}
.def-list li strong{color:var(--muted2);font-weight:600;font-size:12.5px;text-transform:uppercase;
  letter-spacing:.04em;padding-top:1px}

.term{background:rgba(124,58,237,.12);color:var(--pl);padding:1px 7px;border-radius:5px;font-weight:600;font-size:.95em}

.info-block{background:linear-gradient(135deg,rgba(6,182,212,.1),rgba(6,182,212,.03));border:1px solid rgba(6,182,212,.3);
  border-radius:14px;padding:20px 24px;margin:24px 0;font-size:14px;color:var(--text);line-height:1.7;
  box-shadow:0 4px 24px rgba(6,182,212,.1);position:relative;overflow:hidden}
.info-block::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--cyan)}
.info-block strong{color:var(--cyan);display:block;margin-bottom:8px;font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:800}

.warn-block{background:linear-gradient(135deg,rgba(239,68,68,.1),rgba(239,68,68,.03));border:1px solid rgba(239,68,68,.3);
  border-radius:14px;padding:20px 24px;margin:24px 0;font-size:14px;color:var(--text);line-height:1.7;
  box-shadow:0 4px 24px rgba(239,68,68,.1);position:relative;overflow:hidden}
.warn-block::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--red)}
.warn-block strong{color:var(--red);display:block;margin-bottom:8px;font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:800}



/* ── FOOTER ── */
footer{padding:40px 6% 28px;border-top:1px solid var(--border)}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.footer-bottom p{font-size:13px;color:var(--muted)}
.footer-links-row{display:flex;gap:20px;list-style:none}
.footer-links-row a{font-size:13px;color:var(--muted);transition:.2s}
.footer-links-row a:hover{color:var(--pl)}
.footer-links-row a.active-link{color:var(--pl)}


.sidebar-promo{background:linear-gradient(135deg,var(--p),#3b82f6);border-radius:16px;padding:24px 20px;text-align:center;margin-top:24px;box-shadow:0 12px 32px rgba(124,58,237,.25);position:relative;overflow:hidden}
.sidebar-promo::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at top right,rgba(255,255,255,.2),transparent);pointer-events:none}
.sidebar-promo p{color:#fff;font-size:13.5px;font-weight:600;line-height:1.5;margin:0 0 16px;text-shadow:0 2px 4px rgba(0,0,0,.1)}
.sidebar-promo-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 20px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .2s;backdrop-filter:blur(5px)}
.sidebar-promo-btn:hover{background:#fff;color:var(--p)}
.sidebar-promo-btn svg{width:14px;height:14px}

/* ── LIGHT THEME ── */
[data-theme="light"] {
  --p:#7c3aed;--pl:#6d28d9;--pd:#5b21b6;
  --cyan:#0891b2;--green:#059669;
  --bg:#f0f2f8;--bg2:#e8eaf2;--bg3:#f8fafc;
  --card:#ffffff;--card2:#f8fafc;
  --border:rgba(0,0,0,.08);--border2:rgba(124,58,237,.2);
  --text:#0f172a;--muted:#475569;--muted2:#334155;
}
html[data-theme="light"],
[data-theme="light"] body{background:#f0f2f8 !important;color:#0f172a !important}
[data-theme="light"] nav{background:rgba(240,242,248,.92)!important;border-bottom-color:rgba(0,0,0,.1)!important;box-shadow:0 1px 12px rgba(0,0,0,.06)}
[data-theme="light"] .nav-logo{background:linear-gradient(135deg,#6d28d9,#7c3aed 50%,#0891b2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
[data-theme="light"] .btn-ghost{border-color:rgba(0,0,0,.2)!important;color:#334155!important;background:rgba(255,255,255,.7)}
[data-theme="light"] .btn-ghost:hover{border-color:#7c3aed!important;color:#7c3aed!important;background:#fff}
[data-theme="light"] .toc{background:linear-gradient(180deg,#ffffff,#faf8ff);border-color:rgba(124,58,237,.25);box-shadow:0 8px 32px rgba(124,58,237,.15),0 2px 8px rgba(15,23,42,.06)}
[data-theme="light"] .toc-list a{color:#334155}
[data-theme="light"] .toc-list a:hover{background:rgba(124,58,237,.06)}
[data-theme="light"] .toc-list a.active{background:rgba(124,58,237,.08);box-shadow:inset 0 0 0 1px rgba(124,58,237,.2)}
[data-theme="light"] .toc-progress{background:rgba(124,58,237,.12)}
[data-theme="light"] .toc-back-top{background:rgba(124,58,237,.05);border-color:rgba(0,0,0,.08);color:#475569}
[data-theme="light"] .toc-back-top:hover{background:rgba(124,58,237,.1);color:var(--pl);border-color:rgba(124,58,237,.3)}
[data-theme="light"] .toc-scroll::-webkit-scrollbar-thumb{background:rgba(124,58,237,.2)}
[data-theme="light"] .toc-num{background:rgba(124,58,237,.08);color:#64748b}
[data-theme="light"] .tos-section h2{background:linear-gradient(135deg,#0f172a,#334155);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
[data-theme="light"] .tos-section h2{color:#0f172a;background:none;-webkit-text-fill-color:initial}
[data-theme="light"] .tos-section h3{color:#1e293b}
[data-theme="light"] .tos-section p,[data-theme="light"] .tos-list li,[data-theme="light"] .def-list li{color:#475569}
[data-theme="light"] .notice-block{background:#fdfcff;border-color:rgba(124,58,237,.2);color:#334155}
[data-theme="light"] .accordion-content{background:#f8fafc}
[data-theme="light"] .accordion-header:hover{background:#f8fafc}
[data-theme="light"] .def-list{border-color:rgba(0,0,0,.1)}
[data-theme="light"] .def-list li{border-bottom-color:rgba(0,0,0,.08)}
[data-theme="light"] .def-list li strong{color:#475569}
[data-theme="light"] .info-block{background:rgba(8,145,178,.06);border-color:rgba(8,145,178,.2);color:#334155}
[data-theme="light"] .warn-block{background:rgba(220,38,38,.05);border-color:rgba(220,38,38,.2);color:#334155}
[data-theme="light"] footer{border-top-color:rgba(0,0,0,.1)}
[data-theme="light"] .footer-bottom p,[data-theme="light"] .footer-links-row a{color:#64748b}
[data-theme="light"] .landing-logo-img{filter:none!important}
[data-theme="light"] .page-hero h1{color:#0f172a}
[data-theme="light"] .page-hero h1 .grad{background:linear-gradient(135deg,#5b21b6,#7c3aed 40%,#0891b2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

/* ── RESPONSIVE ── */
@media(max-width:1100px){
  .tos-layout{grid-template-columns:220px 1fr;gap:36px}
}
@media(max-width:900px){
  .tos-layout{grid-template-columns:1fr;gap:0;padding:0 5% 60px}
  .toc-wrap{position:static;margin-bottom:28px;max-height:none;display:block}
  .toc{padding:16px 18px;max-height:none;display:block}
  .toc-title{cursor:pointer;margin-bottom:0;padding-bottom:0;border-bottom:none}
  .toc-toggle{display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;background:rgba(124,58,237,.1)}
  .toc[data-open="true"] .toc-title{margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border)}
  .toc[data-open="false"] .toc-progress,
  .toc[data-open="false"] .toc-scroll,
  .toc[data-open="false"] .toc-footer{display:none}
  .toc-scroll{overflow:visible;max-height:none;margin:0;padding:0}
  .toc-list{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px}
  .toc-list a{padding:8px 10px;font-size:12.5px;border-left:none}
  .toc-list a::before{display:none}
  .toc-list a.active .toc-text{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .page-hero{padding:100px 5% 48px}
  .page-hero h1{font-size:clamp(24px,5vw,40px);letter-spacing:-1px}
  .page-hero-meta{gap:16px}
  nav{padding:0 5%;height:60px}
  .btn{padding:8px 16px;font-size:13px}
}
@media(max-width:640px){
  .page-hero{padding:84px 4% 36px}
  .page-hero-meta{flex-direction:column;gap:10px}
  .tos-layout{padding:0 4% 48px}
  .tos-section{padding:32px 0}
  .tos-section h2{font-size:20px}
  .def-list li{grid-template-columns:1fr;gap:4px}
  .def-list li strong{font-size:11px}
  .footer-bottom{flex-direction:column;text-align:center;gap:16px}
  .footer-links-row{flex-wrap:wrap;justify-content:center;gap:12px}
  .notice-block{padding:16px}
  .info-block,.warn-block{padding:14px 16px}
  .toc-list{grid-template-columns:1fr!important;gap:2px}
  .toc-list a{font-size:12.5px;padding:9px 12px}
  nav{padding:0 4%;height:58px}
  .nav-logo{font-size:18px}
}
@media(max-width:400px){
  .page-hero{padding:76px 4% 28px}
  .tos-layout{padding:0 4% 40px}
  .btn-ghost{display:none}
}
</style>
</head>
<body>

<!-- ── NAV ── -->
<nav id="mainNav">
  <a href="/" class="nav-logo">
    <?php if ($navLogo): ?>
    <img src="<?= htmlspecialchars($navLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="max-height:38px;width:auto;display:block" class="landing-logo-img">
    <?php else: ?>
    <?= htmlspecialchars($siteName) ?>
    <?php endif; ?>
  </a>
  <div class="nav-spacer"></div>
  <div class="nav-actions">
    <a href="/login" class="btn btn-ghost">Log In</a>
    <a href="/register" class="btn btn-primary">Get Started</a>
  </div>
</nav>

<!-- ── PAGE HERO ── -->
<div class="page-hero">
  <div class="page-hero-inner">
    <div class="page-hero-eyebrow">Legal</div>
    <h1>Affscash <span class="grad">Affiliate Agreement</span></h1>
    <div class="page-hero-meta">
      <div class="page-hero-meta-item"><strong>Effective Date</strong>May 31, 2026</div>
      <div class="page-hero-meta-item"><strong>Last Updated</strong>May 31, 2026</div>
      <div class="page-hero-meta-item"><strong>Platform</strong><?= htmlspecialchars($siteName) ?> — Global Performance Affiliate Network</div>
    </div>
  </div>
</div>

<!-- ── MAIN LAYOUT ── -->
<div class="tos-layout">

  <!-- Sidebar TOC -->
  <aside class="toc-wrap">
    <nav class="toc" id="toc" data-open="true" aria-label="Table of contents">
      <p class="toc-title" id="tocTitle">
        <span class="toc-title-label">
          <svg class="toc-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          <span>Contents</span>
        </span>
        <button class="toc-toggle" type="button" aria-label="Toggle contents" aria-expanded="true">&#9662;</button>
      </p>
      <div class="toc-progress" aria-hidden="true">
        <div class="toc-progress-bar" id="tocProgress"></div>
      </div>
      <div class="toc-scroll">
        <ul class="toc-list">
          <li><a href="#section-1"><span class="toc-num">01</span><span class="toc-text">Parties</span></a></li>
          <li><a href="#section-2"><span class="toc-num">02</span><span class="toc-text">Enrollment</span></a></li>
          <li><a href="#section-3"><span class="toc-num">03</span><span class="toc-text">Promotion Rights</span></a></li>
          <li><a href="#section-4"><span class="toc-num">04</span><span class="toc-text">Commissions</span></a></li>
          <li><a href="#section-5"><span class="toc-num">05</span><span class="toc-text">Payment Terms</span></a></li>
          <li><a href="#section-6"><span class="toc-num">06</span><span class="toc-text">Prohibited Activities</span></a></li>
          <li><a href="#section-7"><span class="toc-num">07</span><span class="toc-text">Termination</span></a></li>
          <li><a href="#section-8"><span class="toc-num">08</span><span class="toc-text">Liability</span></a></li>
        </ul>
      </div>
      <div class="toc-footer">
        <button type="button" class="toc-back-top" id="tocBackTop" aria-label="Back to top">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"/></svg>
          <span>Back to top</span>
        </button>
      </div>
    </nav>
    <div class="sidebar-promo">
      <p>Ready to start earning with Edgecash?</p>
      <a href="/register" class="sidebar-promo-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg>
        Join Now
      </a>
    </div>
  </aside>

  <!-- Terms Content -->
  <main class="tos-content">

    <div class="notice-block">
      This Affiliate Agreement ("Agreement") is entered into between <strong><?= htmlspecialchars($siteName) ?> Limited</strong> ("Company," "<?= htmlspecialchars($siteName) ?>," "we," "our," or "us") and the individual or entity applying to participate in the <?= htmlspecialchars($siteName) ?> Affiliate Network ("Affiliate," "you," or "your").<br><br>
      By registering for, accessing, or participating in the <?= htmlspecialchars($siteName) ?> Affiliate Network, you agree to be bound by the terms of this Agreement.
    </div>

        <!-- Section 1 -->
    <section class="tos-section is-open" id="section-1">
      <div class="accordion-header">
        <span class="section-number">01</span>
        <h2>Parties</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>This Affiliate Agreement (“Agreement”) is entered into between AffsCash (“Company,” “we,” “us”) and the individual or entity (“Affiliate,” “you”) applying to participate in the AffsCash Affiliate Program.</p>
      </div>
    </section>

    <!-- Section 2 -->
    <section class="tos-section" id="section-2">
      <div class="accordion-header">
        <span class="section-number">02</span>
        <h2>Enrollment</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Affiliates must submit accurate registration details. We reserve the right to approve or reject any application at our sole discretion.</p>
      </div>
    </section>

    <!-- Section 3 -->
    <section class="tos-section" id="section-3">
      <div class="accordion-header">
        <span class="section-number">03</span>
        <h2>Promotion Rights</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Upon approval, Affiliates are granted a non-exclusive, revocable right to promote AffsCash offers using provided tracking links.</p>
      </div>
    </section>

    <!-- Section 4 -->
    <section class="tos-section" id="section-4">
      <div class="accordion-header">
        <span class="section-number">04</span>
        <h2>Commissions</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <ul class="tos-list">
          <li>Affiliates earn commissions for valid, non-fraudulent, verified conversions</li>
          <li>Rates vary by offer, GEO, and advertiser requirements</li>
          <li>Minimum payout threshold applies (e.g., $50–$100 depending on payment method)</li>
        </ul>
      </div>
    </section>

    <!-- Section 5 -->
    <section class="tos-section" id="section-5">
      <div class="accordion-header">
        <span class="section-number">05</span>
        <h2>Payment Terms</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <ul class="tos-list">
          <li>Payments are issued on Net-15, Net-30, Weekly, or Upon Request basis</li>
          <li>Payments are made via supported methods (wire, PayPal, Payoneer, etc.)</li>
          <li>Company reserves the right to withhold payments for fraud or policy violations</li>
        </ul>
      </div>
    </section>

    <!-- Section 6 -->
    <section class="tos-section" id="section-6">
      <div class="accordion-header">
        <span class="section-number">06</span>
        <h2>Prohibited Activities</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Affiliates are strictly prohibited from:</p>
        <ul class="tos-list">
          <li>Generating fake traffic or conversions</li>
          <li>Using bots, VPN fraud, click farms, or incentivized abuse (unless approved)</li>
          <li>Misleading advertising or brand impersonation</li>
        </ul>
      </div>
    </section>

    <!-- Section 7 -->
    <section class="tos-section" id="section-7">
      <div class="accordion-header">
        <span class="section-number">07</span>
        <h2>Termination</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>We may suspend or terminate accounts at any time for fraud, abuse, or violation of this Agreement.</p>
      </div>
    </section>

    <!-- Section 8 -->
    <section class="tos-section" id="section-8">
      <div class="accordion-header">
        <span class="section-number">08</span>
        <h2>Liability</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>AffsCash is not responsible for lost revenue due to tracking errors, downtime, or third-party advertiser issues.</p>
      </div>
    </section>

</main>
</div>

<!-- ── FOOTER ── -->
<footer>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. <?= htmlspecialchars($footerCopyright) ?> <?= htmlspecialchars($footerPoweredBy) ?></p>
    <ul class="footer-links-row">
      <li><a href="/">Home</a></li>
      <li><a href="/terms-of-service">Terms of Service</a></li>
      <li><a href="/privacy-policy">Privacy Policy</a></li>
      <li><a href="/affiliate-agreement" class="active-link">Affiliate Agreement</a></li>
      <li><a href="/anti-fraud-policy">Anti-Fraud Policy</a></li>
      <li><a href="/gdpr-compliance-policy">GDPR Compliance</a></li>
      <li><a href="/refund-payment-policy">Refund Policy</a></li>
      <li><a href="/cookie-policy">Cookie Policy</a></li>
      <li><a href="/login">Login</a></li>
      <li><a href="/register">Sign Up</a></li>
    </ul>
  </div>
</footer>

<script>
// TOC Toggle for Mobile
const toc = document.getElementById('toc');
const tocTitle = document.getElementById('tocTitle');
if(tocTitle) {
  tocTitle.addEventListener('click', () => {
    if(window.innerWidth <= 900) {
      const isOpen = toc.getAttribute('data-open') === 'true';
      toc.setAttribute('data-open', !isOpen);
      toc.querySelector('.toc-toggle').setAttribute('aria-expanded', !isOpen);
    }
  });
}

// Reveal animations on scroll
const ro = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if(e.isIntersecting) {
      e.target.classList.add('visible');
      ro.unobserve(e.target);
    }
  });
}, { threshold: 0.05, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.tos-section, .notice-block').forEach(el => ro.observe(el));

// Accordion Logic
document.querySelectorAll('.accordion-header').forEach(header => {
  header.addEventListener('click', () => {
    const section = header.parentElement;
    const isOpen = section.classList.contains('is-open');
    
    // Optional: close others (uncomment if you want only one open at a time)
    // document.querySelectorAll('.tos-section').forEach(s => s.classList.remove('is-open'));
    
    if (!isOpen) {
      section.classList.add('is-open');
    } else {
      section.classList.remove('is-open');
    }
  });
});

// TOC Scroll Spy and Progress
const sections = document.querySelectorAll('.tos-section');
const tocLinks = document.querySelectorAll('.toc-list a');
const tocProgress = document.getElementById('tocProgress');

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const id = entry.target.getAttribute('id');
      tocLinks.forEach(link => {
        if (link.getAttribute('href') === `#${id}`) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      });
    }
  });
}, { rootMargin: '-10% 0px -80% 0px' });

sections.forEach(section => observer.observe(section));

// Smooth Scroll for TOC
tocLinks.forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const targetId = link.getAttribute('href').substring(1);
    const targetSection = document.getElementById(targetId);
    if (targetSection) {
      // If accordion is closed, open it when clicked from TOC
      if(!targetSection.classList.contains('is-open')){
        targetSection.classList.add('is-open');
      }
      
      // Wait a tiny bit for the DOM to update the height before scrolling
      setTimeout(() => {
        const offset = 80;
        const elementPosition = targetSection.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
      }, 50);
      
      // On mobile, close TOC after clicking
      if(window.innerWidth <= 900) {
        toc.setAttribute('data-open', 'false');
      }
    }
  });
});

// Update progress bar
window.addEventListener('scroll', () => {
  const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
  const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  const scrolled = (winScroll / height) * 100;
  if(tocProgress) tocProgress.style.width = scrolled + '%';
});

// Back to top
const backTopBtn = document.getElementById('tocBackTop');
if(backTopBtn) {
  backTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}
</script>
</body>
</html>
