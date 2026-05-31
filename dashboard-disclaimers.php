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
<title>Affiliate Dashboard Disclaimers — Affscash</title>
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
  --p:#7c3aed;--pl:#ec4899;--pd:#5b21b6;
  --cyan:#06b6d4;--green:#10b981;--red:#ef4444;
  --bg:#f9fafb; /* Very light gray background */
  --card:#ffffff;
  --border:#f3f4f6;
  --border2:#e5e7eb;
  --text:#111827;
  --muted:#4b5563;
  --muted2:#6b7280;
  --grad-primary: linear-gradient(135deg, #ec4899, #8b5cf6);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden}
a{text-decoration:none}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:999;height:68px;
    display:flex;align-items:center;padding:0 6%;gap:32px;
    background:rgba(255,255,255,.9);backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);transition:.3s}
nav.scrolled{box-shadow:0 4px 20px rgba(0,0,0,.05)}
.nav-logo{font-size:21px;font-weight:900;letter-spacing:-.5px;
  background:var(--grad-primary);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;flex-shrink:0}
.nav-spacer{flex:1}
.nav-actions{display:flex;gap:8px;flex-shrink:0;align-items:center}
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:10px;
     font-size:13.5px;font-weight:600;border:none;cursor:pointer;transition:all .2s;white-space:nowrap}
.btn-ghost{background:transparent;border:1px solid var(--border2);color:var(--muted)}
.btn-ghost:hover{border-color:var(--p);color:var(--p);background:#fff}
.btn-primary{background:var(--grad-primary);color:#fff;
  box-shadow:0 4px 20px rgba(124,58,237,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(124,58,237,.35)}
.landing-logo-img{display:block}

/* ── HERO / PAGE HEADER ── */
.page-hero{padding:120px 6% 40px;position:relative;overflow:hidden;background:#fff;border-bottom:1px solid var(--border2)}
.page-hero-inner{max-width:860px;margin:0 auto;position:relative;z-index:1;text-align:center}
.page-hero-eyebrow{display:inline-flex;align-items:center;gap:8px;
  background:rgba(236,72,153,.1);border:1px solid rgba(236,72,153,.2);
  border-radius:24px;padding:5px 14px 5px 10px;font-size:12px;color:#db2777;
  margin-bottom:24px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
.page-hero-eyebrow::before{content:'';width:7px;height:7px;background:#db2777;border-radius:50%}
.page-hero h1{font-size:clamp(28px,4vw,44px);font-weight:800;line-height:1.2;
  letter-spacing:-1px;margin-bottom:16px;color:#111827}
.page-hero-meta{display:flex;flex-wrap:wrap;justify-content:center;gap:24px;margin-top:24px;padding-top:24px;
  border-top:1px solid var(--border2)}
.page-hero-meta-item{font-size:13px;color:var(--muted2);text-align:left}
.page-hero-meta-item strong{color:var(--text);display:block;font-weight:600;margin-bottom:2px}

/* ── LAYOUT ── */
.tos-layout{max-width:1120px;margin:0 auto;padding:40px 6% 80px;
  display:grid;grid-template-columns:260px 1fr;gap:40px;align-items:start}

/* ── SIDEBAR TOC ── */
.toc-wrap{position:sticky;top:88px;align-self:start;max-height:calc(100vh - 108px);display:flex;flex-direction:column}
.toc{
  background:var(--card);
  border:1px solid var(--border2);
  border-radius:12px;padding:24px;
  box-shadow:0 4px 12px rgba(0,0,0,.02);
  display:flex;flex-direction:column;min-height:0;overflow:hidden}
.toc-title{font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;
  letter-spacing:.1em;margin:0 0 16px;padding-bottom:16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;cursor:default;flex-shrink:0}
.toc-title-icon{width:16px;height:16px;color:var(--pl)} /* Pink icon */
.toc-progress{display:none} /* Hidden in this design */
.toc-toggle{display:none}
.toc-scroll{overflow-y:auto;overflow-x:hidden;min-height:0;margin:0 -6px;padding:0 6px;
  scrollbar-width:none;}
.toc-scroll::-webkit-scrollbar{display:none}
.toc-list{list-style:none;display:flex;flex-direction:column;gap:8px;margin:0;padding:0}
.toc-list li{margin:0;padding:0}
.toc-list a{font-size:13px;color:var(--text);display:flex;align-items:center;gap:12px;
  padding:6px;border-radius:8px;transition:all .2s ease;font-weight:500;}
.toc-list a:hover{background:rgba(243,244,246,1)}
.toc-list a.active{font-weight:700;}
.toc-list a.active .toc-num{background:#f3f4f6;}
.toc-num{font-size:10px;color:var(--muted);font-weight:700;font-variant-numeric:tabular-nums;
  background:#f3f4f6;border-radius:12px;padding:4px 8px;flex-shrink:0;min-width:28px;
  text-align:center;}
.toc-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.4}
.toc-list a.active .toc-text{white-space:normal}
.toc-footer{display:none}

/* Sidebar Promo Box */
.sidebar-promo{background:var(--grad-primary);border-radius:12px;padding:24px 20px;text-align:center;margin-top:24px;box-shadow:0 8px 24px rgba(139,92,246,.25);position:relative;overflow:hidden}
.sidebar-promo p{color:#fff;font-size:13px;font-weight:600;line-height:1.5;margin:0 0 16px}
.sidebar-promo-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:8px 16px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;transition:all .2s;backdrop-filter:blur(5px)}
.sidebar-promo-btn:hover{background:#fff;color:var(--p)}
.sidebar-promo-btn svg{width:12px;height:12px}

/* ── CONTENT ── */
.tos-content{min-width:0;display:flex;flex-direction:column;gap:16px}

.notice-block{background:#fdfaff;border:1px solid #f3e8ff;
  border-radius:12px;padding:24px 32px;margin-bottom:16px;font-size:14px;color:var(--muted);
  line-height:1.8;position:relative;}
.notice-block strong{color:var(--text);font-weight:600;}

/* Accordion Section */
.tos-section{background:var(--card);border:1px solid var(--border2);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.02);transition:all .3s ease;opacity:0;transform:translateY(20px)}
.tos-section.visible{opacity:1;transform:translateY(0)}
.tos-section.is-open{border-color:rgba(139,92,246,.3);box-shadow:0 8px 24px rgba(139,92,246,.08)}

.accordion-header{display:flex;align-items:center;padding:20px 24px;cursor:pointer;user-select:none;gap:16px;transition:background .2s}
.accordion-header:hover{background:#fbfbfe}

.section-number{font-size:12px;font-weight:800;color:#fff;background:var(--grad-primary);border-radius:8px;padding:5px 12px;flex-shrink:0;letter-spacing:1px}
.tos-section h2{font-size:15px;font-weight:700;color:var(--text);margin:0;flex:1;letter-spacing:0}

.accordion-icon{width:18px;height:18px;color:var(--muted2);transition:transform .3s ease}
.tos-section.is-open .accordion-icon{transform:rotate(180deg);color:var(--p)}

.accordion-content{display:none;padding:0 24px 24px 72px;background:#fff;}
.tos-section.is-open .accordion-content{display:block}

.tos-section h3{font-size:13px;font-weight:700;color:var(--text);margin:24px 0 12px;text-transform:uppercase;letter-spacing:.06em}
.tos-section h3:first-child{margin-top:0}
.tos-section p{font-size:14px;color:var(--muted);line-height:1.75;margin-bottom:14px}
.tos-section p:last-child{margin-bottom:0}

.tos-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0}
.tos-list li{font-size:14px;color:var(--muted);line-height:1.65;padding-left:20px;position:relative}
.tos-list li::before{content:'•';position:absolute;left:0;color:var(--pl);font-weight:700;font-size:16px;line-height:1.5}
.tos-list li strong{color:var(--text)}

/* ── FOOTER ── */
footer{padding:40px 6% 28px;border-top:1px solid var(--border2);background:#fff}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;max-width:1120px;margin:0 auto}
.footer-bottom p{font-size:13px;color:var(--muted2)}
.footer-links-row{display:flex;gap:20px;list-style:none;flex-wrap:wrap;justify-content:center}
.footer-links-row a{font-size:13px;color:var(--muted2);transition:.2s}
.footer-links-row a:hover{color:var(--p)}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .tos-layout{grid-template-columns:1fr;gap:24px;padding:40px 5% 60px}
  .toc-wrap{position:static;margin-bottom:0;max-height:none;display:block}
  .toc{padding:16px 20px;}
  .toc-title{cursor:pointer;margin-bottom:0;padding-bottom:0;border-bottom:none}
  .toc-toggle{display:flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;background:rgba(124,58,237,.1)}
  .toc[data-open="true"] .toc-title{margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border2)}
  .toc[data-open="false"] .toc-scroll{display:none}
  .toc-list{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .sidebar-promo{display:none}
  .accordion-content{padding:0 20px 20px 20px}
}
@media(max-width:640px){
  .page-hero{padding:100px 5% 32px}
  .page-hero h1{font-size:28px}
  .page-hero-meta{flex-direction:column;gap:12px}
  .toc-list{grid-template-columns:1fr!important}
  .accordion-header{padding:16px 20px;gap:12px}
  .section-number{padding:4px 10px}
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
    <h1>Affiliate Dashboard Disclaimers</h1>
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
          <li><a href="#section-1"><span class="toc-num">01</span><span class="toc-text">What Are Cookies</span></a></li>
          <li><a href="#section-2"><span class="toc-num">02</span><span class="toc-text">How We Use Cookies</span></a></li>
          <li><a href="#section-3"><span class="toc-num">03</span><span class="toc-text">Types Of Cookies</span></a></li>
          <li><a href="#section-4"><span class="toc-num">04</span><span class="toc-text">Third-Party Cookies</span></a></li>
          <li><a href="#section-5"><span class="toc-num">05</span><span class="toc-text">User Consent</span></a></li>
          <li><a href="#section-6"><span class="toc-num">06</span><span class="toc-text">Managing Cookies</span></a></li>
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

      This Cookie Policy ("Policy") explains how <strong><?= htmlspecialchars($siteName) ?> Limited</strong> ("<?= htmlspecialchars($siteName) ?>," "Company," "we," "our," or "us") uses cookies and similar tracking technologies on <?= htmlspecialchars($siteName) ?>.net and related services.<br><br>
      This Policy should be read together with our <a href="/privacy-policy" style="color:var(--cyan)">Privacy Policy</a> and <a href="/gdpr-compliance-policy" style="color:var(--cyan)">GDPR Compliance Policy</a>.<br><br>
      By accessing or using our website, you consent to the use of cookies as described in this Policy, unless you disable them through your browser settings or cookie preferences.
    
</div>

<section class="tos-section is-open" id="section-1">
      <div class="accordion-header">
        <span class="section-number">01</span>
        <h2>What Are Cookies</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Cookies are small files stored on a user’s device to track activity and improve performance.</p>
      </div>
    </section>

<section class="tos-section" id="section-2">
      <div class="accordion-header">
        <span class="section-number">02</span>
        <h2>How We Use Cookies</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>We use cookies for:</p>
        <ul class="tos-list">
          <li>Tracking affiliate referrals</li>
          <li>Measuring conversions</li>
          <li>Preventing fraud</li>
          <li>Improving platform performance</li>
        </ul>
      </div>
    </section>

<section class="tos-section" id="section-3">
      <div class="accordion-header">
        <span class="section-number">03</span>
        <h2>Types Of Cookies</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <ul class="tos-list">
          <li>Essential cookies (site functionality)</li>
          <li>Tracking cookies (affiliate attribution)</li>
          <li>Analytics cookies (performance optimization)</li>
        </ul>
      </div>
    </section>

<section class="tos-section" id="section-4">
      <div class="accordion-header">
        <span class="section-number">04</span>
        <h2>Third-Party Cookies</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Advertisers may place cookies to track conversions and user behavior.</p>
      </div>
    </section>

<section class="tos-section" id="section-5">
      <div class="accordion-header">
        <span class="section-number">05</span>
        <h2>User Consent</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>By using AffsCash services, users agree to cookie usage as described in this policy.</p>
      </div>
    </section>

<section class="tos-section" id="section-6">
      <div class="accordion-header">
        <span class="section-number">06</span>
        <h2>Managing Cookies</h2>
        <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="accordion-content">
        <p>Users can disable cookies in browser settings, but this may affect tracking accuracy.</p>
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
      <li><a href="/affiliate-agreement">Affiliate Agreement</a></li>
      <li><a href="/anti-fraud-policy">Anti-Fraud Policy</a></li>
      <li><a href="/gdpr-compliance-policy">GDPR Compliance</a></li>
      <li><a href="/refund-payment-policy">Refund Policy</a></li>
      <li><a href="/cookie-policy" >Cookie Policy</a></li>
      <li><a href="/login">Login</a></li>
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
