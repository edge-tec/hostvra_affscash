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
      <li><a href="/#offers">Offers</a></li>
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
    <a href="/#offers">Offers</a>
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
