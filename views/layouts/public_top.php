<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? $appName ?></title>
  <?php
  // Admin-managed SEO: canonical, description, robots, verification, GA/GTM.
  // Falls back gracefully if seo_settings table or rows are empty.
  $seoDescription = $appName . ' - Global Performance & Affiliate Network.';
  require BASE_PATH . '/views/partials/seo_head.php';
  
  if (isset($seoCustomHead)) {
      echo $seoCustomHead;
  }
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/public_top.min.css?v=<?= filemtime(BASE_PATH . '/assets/css/public_top.min.css') ?>">
  <style>
    :root {
      --white:rgba(15,10,36,0.65); --bg:#05020c; --bg2:#0f0a26; --bg3:#160f38; --card:rgba(15,10,36,0.7);
      --border:rgba(255,255,255,0.08); --text:#ffffff; --muted:#9ca3af; --r:16px;
      --pink:#e8197a; --pink-light:#ff4da6; --violet:#7c3aed; --blue:#2563eb;
      --cyan:#0ea5e9; --green:#10b981; --gold:#f59e0b;
      --grad-brand:linear-gradient(135deg,#7c3aed 0%,#3b82f6 50%,#0ea5e9 100%);
    }
    body {
      background: #05020c !important;
      color: #fff !important;
    }
    /* HEADER MATCHING LANDING PAGE */
    .site-header {
      position: fixed !important;
      top: 16px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      width: 92% !important;
      max-width: 1280px !important;
      z-index: 1000 !important;
      background: rgba(15,10,36,0.7) !important;
      backdrop-filter: blur(20px) !important;
      -webkit-backdrop-filter: blur(20px) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      border-radius: 24px !important;
      padding: 0 24px !important;
      box-shadow: 0 12px 40px rgba(0,0,0,0.3) !important;
      transition: all .3s !important;
    }
    .nav-inner {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      max-width: 1280px !important;
      margin: 0 auto !important;
      height: 68px !important;
    }
    .nav-logo {
      display: flex !important;
      align-items: center !important;
      text-decoration: none !important;
    }
    .nav-logo img {
      height: 40px !important;
      width: auto !important;
      display: block !important;
      filter: brightness(0) invert(1) !important;
    }
    .nav-links {
      display: flex !important;
      align-items: center !important;
      gap: 4px !important;
      list-style: none !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .nav-links li {
      margin: 0 !important;
    }
    .nav-links a {
      color: #fff !important;
      font-size: 13px !important;
      font-weight: 500 !important;
      padding: 7px 11px !important;
      border-radius: 8px !important;
      transition: all .22s !important;
      text-decoration: none !important;
    }
    .nav-links a:hover {
      background: rgba(124,58,237,0.15) !important;
      color: #0ea5e9 !important;
    }
    .nav-links .btn-login {
      background: var(--grad-brand) !important;
      color: #fff !important;
      padding: 8px 20px !important;
      border-radius: 8px !important;
      font-weight: 600 !important;
      box-shadow: 0 4px 16px rgba(124,58,237,0.3) !important;
      border: none !important;
    }
    .nav-links .btn-login:hover {
      opacity: .88 !important;
      transform: translateY(-1px) !important;
    }
    .nav-links .btn-signup {
      border: 2px solid transparent !important;
      background: linear-gradient(rgba(15,10,36,0.65),rgba(15,10,36,0.65)) padding-box,var(--grad-brand) border-box !important;
      color: #fff !important;
      padding: 7px 18px !important;
      border-radius: 8px !important;
      font-weight: 600 !important;
      text-decoration: none !important;
    }
    .nav-links .btn-signup:hover {
      background: linear-gradient(rgba(124,58,237,0.1),rgba(124,58,237,0.1)) padding-box,var(--grad-brand) border-box !important;
    }
    .hamburger {
      display: none !important;
      flex-direction: column !important;
      gap: 5px !important;
      cursor: pointer !important;
      padding: 8px !important;
      background: none !important;
      border: none !important;
    }
    .hamburger span {
      display: block !important;
      width: 24px !important;
      height: 2px !important;
      background: #fff !important;
      border-radius: 2px !important;
      transition: all .3s !important;
    }
    .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg) !important; }
    .hamburger.open span:nth-child(2) { opacity: 0 !important; }
    .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg) !important; }
    
    .mobile-menu {
      display: none !important;
      flex-direction: column !important;
      background: rgba(15,10,36,0.95) !important;
      backdrop-filter: blur(20px) !important;
      -webkit-backdrop-filter: blur(20px) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      border-radius: 20px !important;
      padding: 16px 24px 20px !important;
      margin-top: 8px !important;
      position: absolute !important;
      top: 70px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      width: 92% !important;
      z-index: 999 !important;
    }
    .mobile-menu.open {
      display: flex !important;
    }
    .mobile-menu a {
      color: #fff !important;
      padding: 12px 0 !important;
      border-bottom: 1px solid rgba(255,255,255,0.08) !important;
      font-size: 14px !important;
      font-weight: 500 !important;
      text-decoration: none !important;
    }
    .mobile-menu a:last-child {
      border-bottom: none !important;
    }
    .mobile-menu a:hover {
      color: #0ea5e9 !important;
    }
    
    @media(max-width:991px) {
      .nav-links { display: none !important; }
      .hamburger { display: flex !important; }
    }
    
    /* Adjust page-banner to clear the fixed header */
    .page-banner {
      padding-top: 130px !important;
    }
  </style>
</head>
<body>

<?php if ($_isAdmin): ?>
<div style="background:linear-gradient(90deg,#1e1b4b,#4F46E5,#7C3AED);color:#fff;font-size:12px;padding:6px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:relative;z-index:9999">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-weight:700">⚙ Admin Mode</span>
    <a href="/admin/dashboard"       style="color:rgba(255,255,255,.85);padding:3px 10px;border:1px solid rgba(255,255,255,.3);border-radius:5px">Dashboard</a>
    <a href="/admin/landing/sliders" style="color:rgba(255,255,255,.85);padding:3px 10px;border:1px solid rgba(255,255,255,.3);border-radius:5px">Sliders</a>
    <a href="/admin/landing/blog"    style="color:rgba(255,255,255,.85);padding:3px 10px;border:1px solid rgba(255,255,255,.3);border-radius:5px">Blog</a>
    <a href="/admin/landing/reviews" style="color:rgba(255,255,255,.85);padding:3px 10px;border:1px solid rgba(255,255,255,.3);border-radius:5px">Reviews</a>
  </div>
  <a href="/admin/dashboard" style="background:#fff;color:#4F46E5;font-weight:700;padding:4px 14px;border-radius:5px;font-size:12px;text-decoration:none">Admin Panel →</a>
</div>
<?php elseif ($_isLogged): ?>
<div style="background:linear-gradient(90deg,#065F46,#059669);color:#fff;font-size:12px;padding:6px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:relative;z-index:9999">
  <span>✅ You are logged in</span>
  <a href="/<?= htmlspecialchars($_role,ENT_QUOTES,'UTF-8') ?>/dashboard" style="background:#fff;color:#065F46;font-weight:700;padding:4px 14px;border-radius:5px;font-size:12px;text-decoration:none">Go to Dashboard →</a>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="nav-inner">
    <a href="/" class="nav-logo"><img src="<?= $logoSrc ?>" alt="<?= $appName ?>" height="40"></a>
    <ul class="nav-links">
      <li><a href="/">Home</a></li>
      <li><a href="/offers" <?= (($_currentPage??'')==='offers')?'class="active"':'' ?>>Offers</a></li>
      <li><a href="/#about">About</a></li>
      <li><a href="/#services">Services</a></li>
      <li><a href="/blog" <?= (($_currentPage??'')==='blog')?'class="active"':'' ?>>Blog</a></li>
      <li><a href="/reviews" <?= (($_currentPage??'')==='reviews')?'class="active"':'' ?>>Reviews</a></li>
      <li><a href="/#contact">Contact</a></li>
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
    <a href="/">Home</a>
    <a href="/offers" <?= (($_currentPage??'')==='offers')?'style="color:var(--pink)"':'' ?>>Offers</a>
    <a href="/#about">About</a>
    <a href="/#services">Services</a>
    <a href="/blog" <?= (($_currentPage??'')==='blog')?'style="color:var(--pink)"':'' ?>>Blog</a>
    <a href="/reviews" <?= (($_currentPage??'')==='reviews')?'style="color:var(--pink)"':'' ?>>Reviews</a>
    <a href="/#contact">Contact</a>
    <?php if ($_isLogged): ?>
    <a href="/<?= htmlspecialchars($_role,ENT_QUOTES,'UTF-8') ?>/dashboard">My Dashboard</a>
    <?php if ($_isAdmin): ?><a href="/admin/dashboard">Admin Panel</a><?php endif; ?>
    <?php else: ?>
    <a href="/register/affiliate">Sign Up Free</a>
    <a href="/login">Login</a>
    <?php endif; ?>
  </div>
</header>
