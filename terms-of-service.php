<?php
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/core/functions.php';
    $siteName      = setting('site_name', 'AffsCash');
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
    $siteName = 'AffsCash'; $siteUrl = ''; $logoPath = ''; $loginLogoPath = '';
    $faviconPath = '';
    $footerCopyright = 'All rights reserved. Built for performance marketers worldwide.';
    $footerPoweredBy = 'Powered by EdgeSoft Ltd';
    $landingTheme = 'dark';
}
$navLogo = $loginLogoPath ?: $logoPath;
if (!$navLogo) {
    $navLogo = '/logoo.png';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($landingTheme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terms of Service — Affscash</title>
<link rel="canonical" href="<?= htmlspecialchars(rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?'), '/'), ENT_QUOTES, 'UTF-8') ?>">
<?php if (class_exists('Config') && $fav = Config::get('config','app.favicon')): ?>
<link rel="icon" href="<?= Helpers::e($fav) ?>">
<?php elseif ($faviconPath): ?>
<link rel="icon" href="<?= htmlspecialchars($faviconPath) ?>">
<?php else: ?>
<link rel="icon" href="/x-icon.png" type="image/png">
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
  .toc-wrap{display:none !important;}
  .toc{display:none !important;}
  .sidebar-promo{display:none}
  .accordion-content{padding:0 20px 20px 20px}
}
@media(max-width:640px){
  nav{padding:0 4%;gap:10px;justify-content:space-between}
  .nav-logo img{max-height:36px!important}
  .btn{padding:8px 12px;font-size:12px}
  .nav-spacer{display:none}
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
    <img src="<?= htmlspecialchars($navLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="max-height:38px;width:auto;display:block" class="landing-logo-img" onerror="this.src='/logoo.png'">
    <?php else: ?>
    <?= htmlspecialchars($siteName) ?>
    <?php endif; ?>
  </a>
  <div class="nav-spacer"></div>
  <div class="nav-actions">
    <?php if (class_exists('Auth') && Auth::id()): ?>
        <?php
            $dashUrl = '/login';
            $userRole = Auth::role() ?? '';
            if ($userRole === 'admin') $dashUrl = '/admin/dashboard';
            elseif ($userRole === 'manager') $dashUrl = '/manager/dashboard';
            elseif ($userRole === 'affiliate') $dashUrl = '/affiliate/dashboard';
            elseif ($userRole === 'advertiser') $dashUrl = '/advertiser/dashboard';
        ?>
        <a href="<?= $dashUrl ?>" class="btn btn-primary">My Dashboard</a>
    <?php else: ?>
        <a href="/login" class="btn btn-ghost">Log In</a>
        <a href="/register/affiliate" class="btn btn-primary">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── PAGE HERO ── -->
<div class="page-hero">
  <div class="page-hero-inner">
    <div class="page-hero-eyebrow">Legal</div>
    <h1>Terms of Service</h1>
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
          <li><a href="#section-1"><span class="toc-num">01</span> <span class="toc-text">Sign Up as an Affiliate</span></a></li>
          <li><a href="#section-2"><span class="toc-num">02</span> <span class="toc-text">Cancellation and Termination</span></a></li>
          <li><a href="#section-3"><span class="toc-num">03</span> <span class="toc-text">Consensus of Confidentiality</span></a></li>
          <li><a href="#section-4"><span class="toc-num">04</span> <span class="toc-text">Limited License &amp; Intellectual Property</span></a></li>
          <li><a href="#section-5"><span class="toc-num">05</span> <span class="toc-text">Termination</span></a></li>
          <li><a href="#section-6"><span class="toc-num">06</span> <span class="toc-text">Amendments</span></a></li>
          <li><a href="#section-7"><span class="toc-num">07</span> <span class="toc-text">Counter-Spam Policy</span></a></li>
          <li><a href="#section-8"><span class="toc-num">08</span> <span class="toc-text">About Fraud</span></a></li>
          <li><a href="#section-9"><span class="toc-num">09</span> <span class="toc-text">Representations and Warranties</span></a></li>
          <li><a href="#section-10"><span class="toc-num">10</span> <span class="toc-text">Modifications</span></a></li>
          <li><a href="#section-11"><span class="toc-num">11</span> <span class="toc-text">Independent Investigation</span></a></li>
          <li><a href="#section-12"><span class="toc-num">12</span> <span class="toc-text">Mutual Indemnification</span></a></li>
          <li><a href="#section-13"><span class="toc-num">13</span> <span class="toc-text">Disclaimers</span></a></li>
          <li><a href="#section-14"><span class="toc-num">14</span> <span class="toc-text">Limitation of Liability</span></a></li>
          <li><a href="#section-15"><span class="toc-num">15</span> <span class="toc-text">Governing Law &amp; Miscellaneous</span></a></li>
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
      <p>Ready to start earning with Affscash?</p>
      <a href="/register/affiliate" class="sidebar-promo-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg>
        Join Now
      </a>
    </div>
  </aside>

  <!-- Terms Content -->
  







<main class="tos-content">

<div class="notice-block">

  <p><p>This Affiliate Program Operating Agreement (the <strong>"Agreement"</strong>) is made and entered into by and between <strong>affscash</strong> ("affscash" or "we"), and you ("you" or <strong>"Affiliate"</strong>) — the party submitting an application to become an Affscash affiliate. The terms and conditions contained in this Agreement apply to your participation with <a href="https://app.affscash.net" target="_blank">app.affscash.net</a> ("Affiliate Program"). Each Affiliate Program offer (an "Offer") may be for any offering by affscash or a third party (each such third party a "Client") and may link to a specific web site for that particular Offer ("Program Web Site"). By submitting an application or participating in an Offer, you expressly consent to all the terms and conditions of this Agreement.</p></p>

</div>

<section class="tos-section is-open" id="section-1">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">01</span>
    <h2>Sign Up as an Affiliate</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>You must submit an Affiliate Program application from our website. You must accurately complete the application to become an affiliate (and provide us with future updates) and not use any aliases or other means to mask your true identity or contact information.</p>
          <p>After we review your application, we will notify you of your acceptance or rejection to the Affiliate Program, generally within two (2) business days. We may accept or reject your application at our sole discretion for any reason.</p>
  </div>
</section>

<section class="tos-section" id="section-2">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">02</span>
    <h2>Cancellation and Termination</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>Subject to our acceptance of you as an affiliate and your continued compliance with the terms and conditions of this Agreement, affscash agrees as follows:</p>
          <ul class="tos-list">
            <li>We will make available to you via the Affiliate Program graphic and textual links to the Program Web Site and/or other creative materials (collectively, the "Links") which you may display on web sites owned or controlled by you, in emails sent by you and clearly identified as coming from you and in online advertisements (collectively, "Media").</li>
            <li>We will pay Affiliate for each Qualified Action (the "Commission"). A "Qualified Action" means an individual person who (i) accesses the Program Web Site via the Link, (ii) is not a computer generated user, (iii) is not using pre-populated fields, (iv) completes all required information within the allowed time period, and (v) is not later determined to be fraudulent, incomplete, unqualified or a duplicate.</li>
            <li>We will pay you any Commissions earned monthly, provided that your account balance is greater than $100. Accounts with a balance less than $100 will roll over monthly until $100 is reached.</li>
          </ul>

          <div class="payment-highlight">
            <div class="pay-item">
              <span class="pay-icon">💰</span>
              <h5>Min Payment</h5>
              <p>$50 – $200</p>
            </div>
            <div class="pay-item">
              <span class="pay-icon">📅</span>
              <h5>Payment Frequency</h5>
              <p>Net-30, Net-15, Weekly, Upon Request</p>
            </div>
            <div class="pay-item">
              <span class="pay-icon">💳</span>
              <h5>Payment Methods</h5>
              <p>Wire, PayPal, WebMoney, Payoneer, ACH</p>
            </div>
          </div>

          <ul class="tos-list">
            <li>Payment for Commissions is dependent upon Clients providing such funds to affscash. You hereby release affscash from any claim for Commissions if affscash has not received such funds from the Clients.</li>
            <li>affscash shall automatically generate an invoice on behalf of Affiliate for all Commissions payable under this Agreement. All tracking of Links and determinations of Qualified Actions and Commissions shall be made by affscash in its sole discretion.</li>
            <li>If Affiliate has an outstanding balance due to affscash under this Agreement or any other agreement, affscash may offset any such amounts from amounts payable to Affiliate under this Agreement.</li>
          </ul>

          <div class="notice-box" style="background: rgba(236, 72, 153, 0.05); border: 1px solid rgba(236, 72, 153, 0.2); border-radius: 12px; padding: 14px 18px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start;">
            <svg style="color: var(--pl); width: 16px; height: 16px; margin-top: 3px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <p style="margin: 0; font-size: 13px; color: var(--text); line-height: 1.5;"><strong>Affiliate also agrees to:</strong> Have sole responsibility for the development, operation, and maintenance of, and all content on or linked to, your Media. Ensure all materials posted are not illegal, do not infringe upon intellectual property or personal rights of any third party, and do not contain Objectionable Content.</p>
          </div>

          <p>Email Campaigns: For all email campaigns, Affiliate must download the "Suppression List" from the Offers section of affscash. Affiliate shall filter its email list by removing any entries appearing on the Suppression List and will only send emails to the remaining addresses on its email list.</p>
          <p>Advertising Campaigns: No Links can appear to be associated with or be positioned on chat rooms or bulletin boards unless otherwise agreed by affscash in writing.</p>
          <p>Affiliate Network Campaigns: For all Affiliates that maintain their own affiliate networks, Affiliate agrees to place the Links in its affiliate network for access and use by those affiliates. Affiliate agrees that it will expressly forbid any Third Party Affiliate to modify the Links in any way.</p>
  </div>
</section>

<section class="tos-section" id="section-3">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">03</span>
    <h2>Consensus of Confidentiality</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>Except as otherwise provided in this Agreement or with the consent of affscash, you agree that all information, including, without limitation, the terms of this Agreement, business and financial information, customer and vendor lists, and pricing and sales information, concerning us or any of our affiliates provided by or on behalf of any of them shall remain strictly confidential and secret.</p>
          <p>Affiliate shall not use any information obtained from the Affiliate Program to develop, enhance or operate a service that competes with the Affiliate Program, or assist another party to do the same.</p>
  </div>
</section>

<section class="tos-section" id="section-4">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">04</span>
    <h2>Limited License &amp; Intellectual Property</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>We grant you a nonexclusive, nontransferable, revocable right to use the Links and to access our web site through the Links solely in accordance with the terms of this Agreement, for the sole purpose of identifying your Media as a participant in the Affiliate Program and assisting in increasing sales through the Program Web Site.</p>
          <p>You may not alter, modify, manipulate or create derivative works of the Links or any affscash graphics, creative, copy or other materials owned by, or licensed to, affscash in any way. We may revoke your license anytime by giving you written notice. All rights not expressly granted in this Agreement are reserved by affscash.</p>
  </div>
</section>

<section class="tos-section" id="section-5">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">05</span>
    <h2>Termination</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>This Agreement shall commence on the date of our approval of your Affiliate Program application and shall continue thereafter until terminated as provided herein. You may terminate your participation in the Affiliate Program at any time by removing all Links from your Media and deleting all copies of the Links.</p>
          <p>We may terminate your participation in one or more Offers or this Agreement at any time and for any reason which we deem appropriate with or without prior notice to you. Upon termination, you will immediately cease all use of and delete all Links, plus all affscash or Client intellectual property.</p>
  </div>
</section>

<section class="tos-section" id="section-6">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">06</span>
    <h2>Amendments</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>In addition to any other rights and remedies available to us under this Agreement, affscash reserves the right to delete any actions submitted through your Links and withhold and freeze any unpaid Commissions or charge back paid Commissions to your account if (i) affscash determines that you have violated this Agreement, (ii) affscash receives any complaints about your participation which affscash reasonably believes to violate this Agreement, or (iii) any Qualified Action is later determined to have not met the requirements set forth in this Agreement.</p>
          <div class="notice-box" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 14px 18px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start;">
            <svg style="color: var(--red); width: 16px; height: 16px; margin-top: 3px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <p style="margin: 0; font-size: 13px; color: var(--text); line-height: 1.5;"><p>In the event of a material breach of this Agreement, affscash reserves the right to disclose your identity and contact information to appropriate law enforcement or regulatory authorities or any third party that has been directly damaged by your actions.</p></p>
          </div>
  </div>
</section>

<section class="tos-section" id="section-7">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">07</span>
    <h2>Counter-Spam Policy</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>You must strictly comply with the federal CAN-SPAM Act of 2003 (the "Act"). All emails sent in connection with the Affiliate Program must include the appropriate party's opt-out link.</p>
          <p>From time to time, we may request — prior to your sending emails containing or referencing the Affiliate Program — that you submit the final version of your email to affscash for approval. It is solely your obligation to ensure that the email complies with the Act.</p>
  </div>
</section>

<section class="tos-section" id="section-8">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">08</span>
    <h2>About Fraud</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>You are expressly prohibited from using any persons, means, devices or arrangements to commit fraud, violate any applicable law, interfere with other affiliates or falsify information in connection with referrals through the Links or the generation of Commissions or exceed your permitted access to the Affiliate Program.</p>
          <div class="notice-box" style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 14px 18px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start;">
            <svg style="color: var(--red); width: 16px; height: 16px; margin-top: 3px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <p style="margin: 0; font-size: 13px; color: var(--text); line-height: 1.5;">Such acts include, but are not limited to: using automated means to increase clicks through the Links, using spyware, using stealware, cookie-stuffing and other deceptive acts or click-fraud. affscash shall make all determinations about fraudulent activity in its sole discretion.</p>
          </div>
  </div>
</section>

<section class="tos-section" id="section-9">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">09</span>
    <h2>Representations and Warranties</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>You hereby represent and warrant that this Agreement constitutes your legal, valid, and binding obligation, enforceable against you in accordance with its terms and that you have the authority to enter into this Agreement.</p>
          <p>Subject to the other terms and conditions of this Agreement, affscash represents and warrants that it shall not knowingly violate any law, rule or regulation which is applicable to affscash's own business operations or affscash's proprietary products or services.</p>
  </div>
</section>

<section class="tos-section" id="section-10">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">10</span>
    <h2>Modifications</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>We may modify any of the terms and conditions of this Agreement at any time by providing you with a notification by email. The changes will become effective ten (10) business days after such notice. If the modifications are unacceptable to you, you may terminate this Agreement without penalty within such ten (10) business day period.</p>
          <p>Your continued participation in this Affiliate Program ten (10) business days after a change notice has been posted will constitute your acceptance of such change. In addition, affscash may change, suspend or discontinue any aspect of an Offer or Link or remove, alter, or modify any tags, text, graphic or banner ad in connection with a Link.</p>
  </div>
</section>

<section class="tos-section" id="section-11">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">11</span>
    <h2>Independent Investigation</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>You acknowledge that you have read this Agreement and agree to all its terms and conditions. You have independently evaluated the desirability of participating in the Affiliate Program and each Offer and are not relying on any representation, guarantee or statement other than as set forth in this Agreement or on the Affiliate Program.</p>
  </div>
</section>

<section class="tos-section" id="section-12">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">12</span>
    <h2>Mutual Indemnification</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>Affiliate hereby agrees to indemnify, defend and hold harmless affscash and Clients and their respective subsidiaries, affiliates, partners and licensors, directors, officers, employees, owners and agents against any and all claims, actions, demands, liabilities, losses, damages, judgments, settlements, costs, and expenses (including reasonable attorneys' fees and costs) based on (i) any failure or breach of this Agreement, (ii) any misuse by Affiliate of the Links, Offers or affscash intellectual property, or (iii) any claim related to your Media.</p>
          <p>affscash hereby agrees to indemnify, defend and hold harmless Affiliate and its subsidiaries, affiliates, partners, and their respective directors, officers, employees, owners and agents against any and all claims, actions, demands, liabilities, losses, damages, judgments, settlements, costs, and expenses based on a claim that affscash is not authorized to provide you with the Links.</p>
  </div>
</section>

<section class="tos-section" id="section-13">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">13</span>
    <h2>Disclaimers</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>THE AFFILIATE PROGRAM AND LINKS, AND THE PRODUCTS AND SERVICES PROVIDED IN CONNECTION THEREWITH, ARE PROVIDED TO AFFILIATE "AS IS". EXCEPT AS EXPRESSLY SET FORTH HEREIN, AFFSCASH EXPRESSLY DISCLAIMS ALL WARRANTIES, EXPRESS, IMPLIED OR STATUTORY, INCLUDING BUT NOT LIMITED TO THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NONINFRINGEMENT.</p>
          <p>AFFSCASH DOES NOT WARRANT THAT THE AFFILIATE PROGRAM OR LINKS WILL MEET AFFILIATE'S SPECIFIC REQUIREMENTS OR THAT THE OPERATION OF THE AFFILIATE PROGRAM OR LINKS WILL BE COMPLETELY ERROR-FREE OR UNINTERRUPTED. AFFSCASH DOES NOT GUARANTEE THAT AFFILIATE WILL EARN ANY SPECIFIC AMOUNT OF COMMISSIONS.</p>
  </div>
</section>

<section class="tos-section" id="section-14">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">14</span>
    <h2>Limitation of Liability</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>IN NO EVENT SHALL AFFSCASH BE LIABLE FOR ANY UNAVAILABILITY OR INOPERABILITY OF THE LINKS, PROGRAM WEB SITES, TECHNICAL MALFUNCTION, COMPUTER ERROR, CORRUPTION OR LOSS OF INFORMATION, OR OTHER INJURY, DAMAGE OR DISRUPTION OF ANY KIND BEYOND THE REASONABLE CONTROL OF AFFSCASH.</p>
          <p>AFFSCASH'S CUMULATIVE LIABILITY TO AFFILIATE, FROM ALL CAUSES OF ACTION AND ALL THEORIES OF LIABILITY, WILL BE LIMITED TO AND WILL NOT EXCEED THE AMOUNTS PAID TO AFFILIATE BY AFFSCASH IN COMMISSIONS DURING THE SIX (6) MONTHS IMMEDIATELY PRIOR TO SUCH CLAIM.</p>
  </div>
</section>

<section class="tos-section" id="section-15">
  <div class="accordion-header" onclick="toggleSection(this)">
    <span class="section-number">15</span>
    <h2>Governing Law &amp; Miscellaneous</h2>
    <svg class="accordion-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </div>
  <div class="accordion-content">
    <p>Affiliate shall be responsible for the payment of all attorneys' fees and expenses incurred by affscash to enforce the terms of this Agreement. This Agreement contains the entire agreement between affscash and Affiliate with respect to the subject matter hereof, and supersedes all prior and/or contemporaneous agreements or understandings, written or oral.</p>
          <p>Affiliate may not assign all or any part of this Agreement without affscash's prior written consent. affscash may assign this Agreement at any time with notice to Affiliate. This Agreement will be binding on and will inure to the benefit of the legal representatives, successors and valid assigns of the parties hereto.</p>
          <p>Each party to this Agreement is an independent contractor in relation to the other party with respect to all matters arising under this Agreement. Nothing herein shall be deemed to establish a partnership, joint venture, association or employment relationship between the parties.</p>
          <div class="notice-box" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 14px 18px; margin: 14px 0; display: flex; gap: 10px; align-items: flex-start;">
            <svg style="color: var(--green); width: 16px; height: 16px; margin-top: 3px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <p style="margin: 0; font-size: 13px; color: var(--text); line-height: 1.5;">By submitting an application to the Affiliate Program, you affirm and acknowledge that you have read this Agreement in its entirety and agree to be bound by all of its terms and conditions. <strong>This Agreement was last revised on 2023.</strong></p>
          </div>
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
      <li><a href="/terms-of-service" class="active-link">Terms of Service</a></li>
      <li><a href="/privacy-policy">Privacy Policy</a></li>
      <li><a href="/affiliate-agreement">Affiliate Agreement</a></li>
      <li><a href="/anti-fraud-policy">Anti-Fraud Policy</a></li>
      <li><a href="/gdpr-compliance-policy">GDPR Compliance</a></li>
      <li><a href="/refund-payment-policy">Refund Policy</a></li>
      <li><a href="/cookie-policy"        >Cookie Policy</a></li>
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
<?php if(defined('BASE_PATH')) { require BASE_PATH . '/views/partials/policy_theme.php'; } else { require __DIR__ . '/views/partials/policy_theme.php'; } ?>
</body>
</html>
