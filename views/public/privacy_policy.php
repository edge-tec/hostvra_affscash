<?php
$appName      = Helpers::e(Config::get('config','app.name')         ?? 'AffsCash');
$appUrl       = Helpers::e(rtrim((string)(Config::get('config','app.url') ?: 'https://affscash.net'), '/'));
$contactEmail = Helpers::e(Config::get('config','app.contact_email') ?: 'affiliate@affscash.net');
$supportEmail = Helpers::e(Config::get('config','app.support_email') ?: 'support@affscash.net');
$effectiveDate = '2026';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Privacy Policy &mdash; <?= $appName ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php 
$fav = (class_exists('Config')) ? Config::get('config','app.favicon') : null;
if (!$fav) { $fav = '/x-icon.png'; }
?>
<link rel="icon" href="<?= Helpers::e($fav) ?>" type="image/png">
<meta name="description" content="Privacy Policy for <?= $appName ?> — how we collect, use and protect your personal data as an affiliate or advertiser on our network.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── Reset & Base ──────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --ink:        #0A0F1E;
    --ink-soft:   #2D3554;
    --ink-muted:  #6B7280;
    --teal:       #0F766E;
    --teal-light: #CCFBF1;
    --teal-mid:   #14B8A6;
    --blue:       #0EA5E9;
    --border:     #E5E9F2;
    --bg:         #F6F8FD;
    --white:      #FFFFFF;
    --accent:     #0F766E;
    --accent2:    #0891B2;
    --radius:     14px;
    --font:       'Sora', sans-serif;
    --mono:       'DM Mono', monospace;
}
html { scroll-behavior: smooth; }
body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--ink);
    line-height: 1.7;
    -webkit-font-smoothing: antialiased;
}

/* ── Top Nav ───────────────────────────────────────────────────────────── */
.topnav {
    position: sticky; top: 0; z-index: 100;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex; align-items: center; justify-content: space-between;
    height: 60px;
}
.topnav-brand {
    font-weight: 700; font-size: 17px; color: var(--teal);
    text-decoration: none; letter-spacing: -.3px;
    display: flex; align-items: center; gap: 8px;
}
.topnav-brand svg { flex-shrink: 0; }
.topnav-links { display: flex; gap: 6px; }
.topnav-links a {
    font-size: 12.5px; font-weight: 500; color: var(--ink-muted);
    text-decoration: none; padding: 6px 12px; border-radius: 8px;
    transition: background .15s, color .15s;
}
.topnav-links a:hover { background: #F1F5F9; color: var(--ink); }
.topnav-links a.cta {
    background: var(--teal); color: #fff;
}
.topnav-links a.cta:hover { background: #0D6B63; }

/* ── Hero ──────────────────────────────────────────────────────────────── */
.hero {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, #0F766E 0%, #0891B2 60%, #0EA5E9 100%);
    padding: 80px 32px 90px;
    text-align: center; color: #fff;
}
.hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-eyebrow {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 999px; padding: 5px 14px;
    font-size: 11px; font-weight: 600; letter-spacing: .12em;
    text-transform: uppercase; margin-bottom: 22px;
}
.hero h1 {
    font-size: clamp(30px, 5vw, 48px);
    font-weight: 700; line-height: 1.15;
    letter-spacing: -.5px; margin-bottom: 14px;
}
.hero-sub {
    font-size: 15px; opacity: .85; max-width: 500px;
    margin: 0 auto 30px; font-weight: 300;
}
.hero-meta {
    display: inline-flex; align-items: center; gap: 20px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px; padding: 12px 24px;
    font-size: 12.5px;
}
.hero-meta-item { display: flex; align-items: center; gap: 6px; }
.hero-meta-item svg { opacity: .75; }
.hero-meta-sep { opacity: .3; }

/* ── TOC sidebar + content grid ────────────────────────────────────────── */
.outer {
    max-width: 1100px; margin: 0 auto;
    padding: 52px 24px 80px;
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 36px;
    align-items: start;
}
@media (max-width: 860px) {
    .outer { grid-template-columns: 1fr; }
    .toc { display: none; }
}

/* ── TOC ───────────────────────────────────────────────────────────────── */
.toc {
    position: sticky; top: 76px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 0;
    box-shadow: 0 2px 12px rgba(10,15,30,.05);
    overflow: hidden;
}
.toc-title {
    font-size: 10px; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--ink-muted);
    padding: 0 20px 12px; border-bottom: 1px solid var(--border);
    margin-bottom: 8px;
}
.toc a {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 20px; font-size: 12.5px; font-weight: 500;
    color: var(--ink-muted); text-decoration: none;
    border-left: 3px solid transparent;
    transition: all .15s;
}
.toc a:hover { color: var(--teal); background: #F0FDF9; border-left-color: var(--teal-mid); }
.toc a .num {
    font-family: var(--mono); font-size: 10px;
    background: var(--bg); border-radius: 4px;
    padding: 1px 5px; flex-shrink: 0; color: var(--ink-muted);
}

/* ── Main content ──────────────────────────────────────────────────────── */
.content { display: flex; flex-direction: column; gap: 0; }

.section {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 36px 40px;
    margin-bottom: 16px;
    box-shadow: 0 1px 6px rgba(10,15,30,.04);
    scroll-margin-top: 80px;
    animation: fadeUp .5s ease both;
}
.section:nth-child(2)  { animation-delay: .05s; }
.section:nth-child(3)  { animation-delay: .10s; }
.section:nth-child(4)  { animation-delay: .15s; }
.section:nth-child(5)  { animation-delay: .20s; }
.section:nth-child(6)  { animation-delay: .25s; }
.section:nth-child(7)  { animation-delay: .28s; }
.section:nth-child(8)  { animation-delay: .30s; }
.section:nth-child(9)  { animation-delay: .32s; }
.section:nth-child(10) { animation-delay: .34s; }
.section:nth-child(11) { animation-delay: .36s; }
.section:nth-child(12) { animation-delay: .38s; }
.section:nth-child(13) { animation-delay: .40s; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

.section-head {
    display: flex; align-items: flex-start; gap: 14px;
    margin-bottom: 20px;
}
.section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 18px;
}
.section-num {
    font-family: var(--mono); font-size: 10px; font-weight: 500;
    color: var(--teal); letter-spacing: .06em;
    text-transform: uppercase; margin-bottom: 3px;
}
.section h2 {
    font-size: 18px; font-weight: 700; color: var(--ink);
    letter-spacing: -.2px; line-height: 1.25;
}

.section p {
    font-size: 14px; color: var(--ink-soft); margin-bottom: 12px;
    line-height: 1.75;
}
.section p:last-child { margin-bottom: 0; }

.section h3 {
    font-size: 13px; font-weight: 700; color: var(--ink);
    text-transform: uppercase; letter-spacing: .06em;
    margin: 22px 0 8px;
}

/* ── Lists ─────────────────────────────────────────────────────────────── */
.check-list { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.check-list li {
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 14px; color: var(--ink-soft); line-height: 1.65;
}
.check-list li::before {
    content: '';
    width: 18px; height: 18px; flex-shrink: 0;
    border-radius: 50%;
    background: var(--teal-light);
    background-image: url("data:image/svg+xml,%3Csvg width='10' height='8' viewBox='0 0 10 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 4L3.5 6.5L9 1' stroke='%230F766E' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    margin-top: 3px;
}

/* ── Data Grid (what we collect cards) ─────────────────────────────────── */
.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 12px; margin-top: 16px;
}
.data-card {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
}
.data-card-icon { font-size: 22px; margin-bottom: 8px; }
.data-card-title { font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
.data-card ul { list-style: none; padding: 0; }
.data-card ul li { font-size: 12.5px; color: var(--ink-muted); padding: 2px 0; border-bottom: 1px solid var(--border); }
.data-card ul li:last-child { border-bottom: none; }

/* ── Highlight / callout boxes ─────────────────────────────────────────── */
.callout {
    display: flex; gap: 12px; align-items: flex-start;
    border-radius: 10px; padding: 14px 18px;
    margin: 16px 0; font-size: 13.5px; line-height: 1.65;
}
.callout.info    { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; }
.callout.warning { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; }
.callout.success { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
.callout.teal    { background: #F0FDFA; border: 1px solid #99F6E4; color: #0F766E; }
.callout-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

/* ── Inline code / badge ────────────────────────────────────────────────── */
.badge {
    display: inline-block; font-family: var(--mono);
    font-size: 11px; background: #EEF2FF; color: #4338CA;
    border-radius: 5px; padding: 2px 7px; font-weight: 500;
}

/* ── Two-col rights grid ────────────────────────────────────────────────── */
.rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px; margin-top: 14px;
}
.right-card {
    border: 1px solid var(--border);
    border-radius: 10px; padding: 16px;
    background: var(--bg);
    transition: border-color .2s, box-shadow .2s;
}
.right-card:hover { border-color: var(--teal-mid); box-shadow: 0 0 0 3px var(--teal-light); }
.right-card-title { font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.right-card p { font-size: 12.5px; color: var(--ink-muted); margin: 0; line-height: 1.5; }

/* ── Contact card ──────────────────────────────────────────────────────── */
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px; margin-top: 18px;
}
.contact-card {
    border: 1px solid var(--border); border-radius: 12px;
    padding: 18px 20px;
    background: linear-gradient(135deg, #F8FFFD, #F0FDFA);
    border-color: #99F6E4;
    text-decoration: none; color: inherit;
    transition: transform .2s, box-shadow .2s;
    display: block;
}
.contact-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,118,110,.12); }
.contact-card-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--teal); margin-bottom: 6px; }
.contact-card-value { font-size: 14px; font-weight: 600; color: var(--ink); word-break: break-all; }

/* ── Divider ───────────────────────────────────────────────────────────── */
.divider {
    height: 1px; background: var(--border);
    margin: 20px 0;
}

/* ── Footer ────────────────────────────────────────────────────────────── */
.footer {
    background: var(--ink);
    color: rgba(255,255,255,.5);
    text-align: center;
    padding: 28px 24px;
    font-size: 12.5px;
}
.footer a { color: var(--teal-mid); text-decoration: none; }
.footer a:hover { color: #fff; }
.footer-links { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 10px; }

@media (max-width: 600px) {
    .section { padding: 24px 20px; }
    .topnav-links a:not(.cta) { display: none; }
    .hero { padding: 52px 20px 64px; }
    .hero-meta { flex-direction: column; gap: 10px; }
    .hero-meta-sep { display: none; }
}
</style>
</head>
<body>

<!-- ── Top Nav ─────────────────────────────────────────────────────────── -->
<nav class="topnav">
    <a class="topnav-brand" href="/">
        <?php 
          $logoPath = Config::get('config', 'app.logo');
          if (!$logoPath) {
              $logoPath = '/logoo.png';
          }
        ?>
        <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($appName) ?> Logo" style="height:40px; width:auto; object-fit:contain;">
    </a>
    <div class="topnav-links">
        <a href="/">Home</a>
        <a href="/terms-of-service">Terms &amp; Conditions</a>
        <?php if (class_exists('Auth') && Auth::id()): ?>
            <?php
                $dashUrl = '/login';
                $userRole = Auth::role() ?? '';
                if ($userRole === 'admin') $dashUrl = '/admin/dashboard';
                elseif ($userRole === 'manager') $dashUrl = '/manager/dashboard';
                elseif ($userRole === 'affiliate') $dashUrl = '/affiliate/dashboard';
                elseif ($userRole === 'advertiser') $dashUrl = '/advertiser/dashboard';
            ?>
            <a href="<?= $dashUrl ?>" class="cta">My Dashboard</a>
        <?php else: ?>
            <a href="/login">Login</a>
            <a href="/register/affiliate" class="cta">Join Free</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────────────────────── -->
<div class="hero">
    <div class="hero-eyebrow">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Legal Document
    </div>
    <h1>Privacy Policy</h1>
    <p class="hero-sub">How <?= $appName ?> collects, uses, and safeguards your personal information across our affiliate network.</p>
    <div class="hero-meta">
        <div class="hero-meta-item">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Effective: 2026
        </div>
        <span class="hero-meta-sep">|</span>
        <div class="hero-meta-item">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            Last Updated: <?= date('F j, Y') ?>
        </div>
        <span class="hero-meta-sep">|</span>
        <div class="hero-meta-item">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Company: Affscash Affiliate Network
        </div>
    </div>
</div>

<!-- ── Main Layout ─────────────────────────────────────────────────────── -->
<div class="outer">

    <!-- Table of Contents -->
    <aside class="toc">
        <div class="toc-title">Contents</div>
        <a href="#s1"><span class="num">01</span> Introduction</a>
        <a href="#s2"><span class="num">02</span> Information We Collect</a>
        <a href="#s3"><span class="num">03</span> How We Use Data</a>
        <a href="#s4"><span class="num">04</span> Cookies &amp; Tracking</a>
        <a href="#s5"><span class="num">05</span> Data Sharing</a>
        <a href="#s6"><span class="num">06</span> Data Retention</a>
        <a href="#s7"><span class="num">07</span> Fraud Prevention</a>
        <a href="#s8"><span class="num">08</span> Data Security</a>
        <a href="#s9"><span class="num">09</span> Your Rights</a>
        <a href="#s10"><span class="num">10</span> Third-Party Links</a>
        <a href="#s11"><span class="num">11</span> Children's Privacy</a>
        <a href="#s12"><span class="num">12</span> Policy Changes</a>
        <a href="#s13"><span class="num">13</span> Contact Us</a>
    </aside>

    <!-- Sections -->
    <main class="content">

        <!-- 1. Introduction -->
        <section class="section" id="s1">
            <div class="section-head">
                <div class="section-icon" style="background:#F0FDF4">🔒</div>
                <div>
                    <div class="section-num">Section 01</div>
                    <h2>Introduction</h2>
                </div>
            </div>
            <p>Welcome to <strong><?= $appName ?></strong>. We respect your privacy and are committed to protecting any personal data you share with us. This Privacy Policy explains how we collect, use, store, and protect your information when you use our website, affiliate platform, or services.</p>
            <div class="callout teal">
                <span class="callout-icon">ℹ️</span>
                <span>By using our platform, you agree to the practices described in this policy.</span>
            </div>
        </section>

        <!-- 2. Information We Collect -->
        <section class="section" id="s2">
            <div class="section-head">
                <div class="section-icon" style="background:#EFF6FF">📋</div>
                <div>
                    <div class="section-num">Section 02</div>
                    <h2>Information We Collect</h2>
                </div>
            </div>
            <p>We collect the following types of information:</p>

            <div class="data-grid">
                <div class="data-card">
                    <div class="data-card-icon">👤</div>
                    <div class="data-card-title">Personal Information</div>
                    <ul>
                        <li>Full name</li>
                        <li>Email address</li>
                        <li>Phone number / messaging ID (Skype, Telegram, etc.)</li>
                        <li>Payment details (PayPal, bank info, Payoneer, etc.)</li>
                        <li>Company or business information (if applicable)</li>
                    </ul>
                </div>
                <div class="data-card">
                    <div class="data-card-icon">🌐</div>
                    <div class="data-card-title">Tracking &amp; Technical Data</div>
                    <ul>
                        <li>IP address</li>
                        <li>Device type and browser</li>
                        <li>Operating system</li>
                        <li>Location (country/region level)</li>
                        <li>Referral URLs</li>
                        <li>Cookies and tracking identifiers</li>
                        <li>Clicks, conversions, and campaign activity</li>
                    </ul>
                </div>
                <div class="data-card">
                    <div class="data-card-icon">📈</div>
                    <div class="data-card-title">Affiliate Activity Data</div>
                    <ul>
                        <li>Clicks on affiliate links</li>
                        <li>Leads and conversions</li>
                        <li>Traffic source and GEO</li>
                        <li>Campaign performance statistics</li>
                    </ul>
                </div>
            </div>

            <div class="divider"></div>


        </section>

        <!-- 3. How We Use Data -->
        <section class="section" id="s3">
            <div class="section-head">
                <div class="section-icon" style="background:#FFF7ED">⚙️</div>
                <div>
                    <div class="section-num">Section 03</div>
                    <h2>How We Use Your Information</h2>
                </div>
            </div>
            <p>We use collected data to:</p>
            <ul class="check-list">
                <li>Operate and maintain our affiliate network</li>
                <li>Track traffic, clicks, and conversions</li>
                <li>Prevent fraud, abuse, and invalid traffic</li>
                <li>Process payments and commissions</li>
                <li>Improve campaign performance and optimization</li>
                <li>Provide customer support</li>
                <li>Comply with legal obligations</li>
            </ul>
        </section>

        <!-- 4. Cookies -->
        <section class="section" id="s4">
            <div class="section-head">
                <div class="section-icon" style="background:#FDF4FF">🍪</div>
                <div>
                    <div class="section-num">Section 04</div>
                    <h2>Cookies &amp; Tracking Technologies</h2>
                </div>
            </div>
            <p>We use cookies, pixels, and tracking links to:</p>
            <ul class="check-list">
                <li>Identify affiliate referrals</li>
                <li>Store session information</li>
                <li>Prevent duplicate or fraudulent conversions</li>
                <li>Improve ad targeting and performance tracking</li>
            </ul>

            <div class="callout warning">
                <span class="callout-icon">⚠️</span>
                <span>Users can disable cookies in their browser settings, but some features may not function properly.</span>
            </div>
        </section>

        <!-- 5. Data Sharing -->
        <section class="section" id="s5">
            <div class="section-head">
                <div class="section-icon" style="background:#F0FDF4">🤝</div>
                <div>
                    <div class="section-num">Section 05</div>
                    <h2>Data Sharing &amp; Disclosure</h2>
                </div>
            </div>
            <p>We may share limited data with:</p>
            <ul class="check-list">
                <li>Advertisers (for conversion validation)</li>
                <li>Payment processors (for payouts)</li>
                <li>Fraud prevention and analytics providers</li>
                <li>Legal authorities if required by law</li>
            </ul>
            <div class="callout success">
                <span class="callout-icon">✅</span>
                <span>We do not sell personal data to third parties.</span>
            </div>
        </section>

        <!-- 6. Data Retention -->
        <section class="section" id="s6">
            <div class="section-head">
                <div class="section-icon" style="background:#FFF7ED">🗂️</div>
                <div>
                    <div class="section-num">Section 06</div>
                    <h2>Data Retention</h2>
                </div>
            </div>
            <p>We retain user data for as long as:</p>
            <ul class="check-list">
                <li>The account remains active</li>
                <li>It is necessary for business or legal purposes</li>
                <li>Required for fraud prevention and accounting</li>
            </ul>
            <p>After account deletion, some data may be retained for compliance purposes.</p>
        </section>

        <!-- 7. Fraud Prevention -->
        <section class="section" id="s7">
            <div class="section-head">
                <div class="section-icon" style="background:#FEF2F2">🛡️</div>
                <div>
                    <div class="section-num">Section 07</div>
                    <h2>Fraud Prevention &amp; Network Integrity</h2>
                </div>
            </div>
            <p>We use automated and manual systems to detect:</p>
            <ul class="check-list">
                <li>Fake conversions</li>
                <li>Bot traffic</li>
                <li>Incentivized or invalid clicks</li>
                <li>IP duplication or suspicious activity</li>
            </ul>
            <div class="callout warning">
                <span class="callout-icon">⚠️</span>
                <span>Accounts found engaging in fraudulent activity may be suspended or terminated.</span>
            </div>
        </section>

        <!-- 8. Security -->
        <section class="section" id="s8">
            <div class="section-head">
                <div class="section-icon" style="background:#F0FDF4">🔐</div>
                <div>
                    <div class="section-num">Section 08</div>
                    <h2>Data Security</h2>
                </div>
            </div>
            <p>We implement industry-standard security measures including:</p>
            <ul class="check-list">
                <li>Encrypted data transmission (SSL)</li>
                <li>Secure servers and restricted access</li>
                <li>Monitoring systems to prevent unauthorized access</li>
            </ul>
            <div class="callout warning">
                <span class="callout-icon">⚠️</span>
                <span>However, no system is 100% secure, and we cannot guarantee absolute security.</span>
            </div>
        </section>

        <!-- 9. Your Rights -->
        <section class="section" id="s9">
            <div class="section-head">
                <div class="section-icon" style="background:#EFF6FF">⚖️</div>
                <div>
                    <div class="section-num">Section 09</div>
                    <h2>Your Privacy Rights</h2>
                </div>
            </div>
            <p>Depending on your region, you may have the right to:</p>
            <ul class="check-list">
                <li>Access your personal data</li>
                <li>Request correction of incorrect data</li>
                <li>Request deletion of your account</li>
                <li>Withdraw consent for marketing communications</li>
            </ul>
            <p>To exercise these rights, contact our support team.</p>
        </section>

        <!-- 10. Third-Party Links -->
        <section class="section" id="s10">
            <div class="section-head">
                <div class="section-icon" style="background:#FFF7ED">🔗</div>
                <div>
                    <div class="section-num">Section 10</div>
                    <h2>Third-Party Links &amp; Offers</h2>
                </div>
            </div>
            <p>Our platform may contain links to third-party websites or offers. We are not responsible for the privacy practices of those external websites.</p>
        </section>

        <!-- 11. Children's Privacy -->
        <section class="section" id="s11">
            <div class="section-head">
                <div class="section-icon" style="background:#FDF4FF">🔞</div>
                <div>
                    <div class="section-num">Section 11</div>
                    <h2>Children's Privacy</h2>
                </div>
            </div>
            <p>Our services are intended for users aged 18+. We do not knowingly collect data from minors. If we become aware of such data, it will be deleted.</p>
        </section>

        <!-- 12. Policy Changes -->
        <section class="section" id="s12">
            <div class="section-head">
                <div class="section-icon" style="background:#F0FDF4">📝</div>
                <div>
                    <div class="section-num">Section 12</div>
                    <h2>Changes to This Policy</h2>
                </div>
            </div>
            <p>We may update this Privacy Policy at any time. Updates will be posted on this page with a revised effective date.</p>
        </section>

        <!-- 13. Contact -->
        <section class="section" id="s13">
            <div class="section-head">
                <div class="section-icon" style="background:#F0FDFA">📬</div>
                <div>
                    <div class="section-num">Section 13</div>
                    <h2>Contact Us</h2>
                </div>
            </div>
            <p>If you have questions about this Privacy Policy, contact us:</p>
            <div class="contact-grid">
                <a class="contact-card" href="mailto:support@affscash.net">
                    <div class="contact-card-label">📧 Email</div>
                    <div class="contact-card-value">support@affscash.net</div>
                </a>
                <div class="contact-card">
                    <div class="contact-card-label">💬 Telegram / Skype</div>
                    <div class="contact-card-value">Available via dashboard support</div>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- ── Footer ─────────────────────────────────────────────────────────── -->
<footer class="footer">
    <div>&copy; <?= date('Y') ?> <?= $appName ?>. All rights reserved.</div>
    <div class="footer-links">
        <a href="/">Home</a>
        <a href="/privacy-policy">Privacy Policy</a>
        <a href="/terms-of-service">Terms &amp; Conditions</a>
        <a href="/login">Login</a>
        <a href="/register/affiliate">Join as Affiliate</a>
        <a href="/register/advertiser">Join as Advertiser</a>
    </div>
</footer>

</body>
</html>
