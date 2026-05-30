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
  ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --white:#fff; --bg:#fff; --bg2:#f7f5ff; --card:#fff;
      --border:#ede8fc; --text:#1a1535; --muted:#7c7a9e; --r:14px;
      --pink:#e8197a; --pink-light:#ff4da6; --violet:#7c3aed; --blue:#2563eb;
      --green:#059669; --gold:#d97706;
      --grad-brand:linear-gradient(135deg,#e8197a 0%,#7c3aed 50%,#2563eb 100%);
      --grad-cool:linear-gradient(135deg,#2563eb 0%,#0ea5e9 60%,#059669 100%);
      --grad-section-a:linear-gradient(160deg,#fdf8ff 0%,#f5f0ff 100%);
      --grad-dark-footer:linear-gradient(135deg,#1a1535 0%,#2d1b69 50%,#1a0030 100%);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{background:#fff;color:var(--text);font-family:'DM Sans',sans-serif;font-size:15px;line-height:1.7;overflow-x:hidden}
    a{color:var(--pink);text-decoration:none;transition:color .25s}
    a:hover{color:var(--pink-light)}
    h1,h2,h3,h4,h5,h6{font-family:'Rajdhani',sans-serif;font-weight:700;color:var(--text);line-height:1.15}
    em{font-style:normal;background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* HEADER */
    .site-header{position:fixed;top:0;left:0;right:0;z-index:1000;background:rgba(255,255,255,.95);backdrop-filter:blur(18px);border-bottom:1px solid var(--border);padding:0 24px;box-shadow:0 2px 24px rgba(124,58,237,.08)}
    .nav-inner{display:flex;align-items:center;justify-content:space-between;max-width:1280px;margin:0 auto;height:68px}
    .nav-logo img{height:40px;width:auto;display:block}
    .nav-links{display:flex;align-items:center;gap:4px;list-style:none}
    .nav-links a{color:var(--text);font-size:13px;font-weight:500;padding:7px 11px;border-radius:8px;transition:all .22s}
    .nav-links a:hover,.nav-links a.active{background:rgba(232,25,122,.07);color:var(--pink)}
    .nav-links .btn-login{background:var(--grad-brand);color:#fff !important;padding:8px 20px;border-radius:8px;font-weight:600;box-shadow:0 4px 16px rgba(232,25,122,.3);-webkit-text-fill-color:#fff !important}
    .nav-links .btn-login:hover{opacity:.88;transform:translateY(-1px)}
    .nav-links .btn-signup{border:2px solid transparent;background:linear-gradient(white,white) padding-box,var(--grad-brand) border-box;color:var(--pink) !important;padding:7px 18px;border-radius:8px;font-weight:600}
    .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;background:none;border:none}
    .hamburger span{display:block;width:24px;height:2px;background:var(--text);border-radius:2px;transition:all .3s}
    .mobile-menu{display:none;flex-direction:column;background:#fff;border-top:1px solid var(--border);padding:16px 24px 20px}
    .mobile-menu.open{display:flex}
    .mobile-menu a{color:var(--text);padding:12px 0;border-bottom:1px solid var(--border);font-size:14px;font-weight:500}
    .mobile-menu a:last-child{border-bottom:none}
    .mobile-menu a:hover{color:var(--pink)}

    /* PAGE HERO BANNER */
    .page-banner{background:var(--grad-brand);color:#fff;padding:110px 0 64px;text-align:center;position:relative;overflow:hidden}
    .page-banner::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
    .page-banner h1{font-size:clamp(32px,5vw,54px);color:#fff;margin-bottom:12px}
    .page-banner p{font-size:16px;color:rgba(255,255,255,.8);max-width:560px;margin:0 auto}
    .breadcrumb-bar{display:flex;align-items:center;justify-content:center;gap:8px;font-size:13px;color:rgba(255,255,255,.6);margin-bottom:16px}
    .breadcrumb-bar a{color:rgba(255,255,255,.7);text-decoration:none}
    .breadcrumb-bar a:hover{color:#fff}

    /* FORM CONTROLS */
    .form-control-custom{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);background:#fff;outline:none;transition:border .2s,box-shadow .2s;font-family:'DM Sans',sans-serif}
    .form-control-custom:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(124,58,237,.12)}
    .btn-primary-custom{display:inline-flex;align-items:center;gap:8px;background:var(--grad-brand);color:#fff;font-weight:700;padding:13px 28px;border-radius:10px;border:none;cursor:pointer;font-size:14px;font-family:'DM Sans',sans-serif;transition:all .22s;text-decoration:none;-webkit-text-fill-color:#fff}
    .btn-primary-custom:hover{opacity:.88;transform:translateY(-1px);box-shadow:0 6px 24px rgba(232,25,122,.3);color:#fff}
    .btn-outline-custom{display:inline-flex;align-items:center;gap:8px;border:2px solid var(--violet);color:var(--violet);font-weight:600;padding:11px 24px;border-radius:10px;background:transparent;cursor:pointer;font-size:14px;font-family:'DM Sans',sans-serif;transition:all .22s;text-decoration:none}
    .btn-outline-custom:hover{background:var(--violet);color:#fff;-webkit-text-fill-color:#fff}
    .eyebrow{font-size:13px;font-weight:700;color:var(--pink);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .pulse{width:7px;height:7px;border-radius:50%;background:var(--pink);display:inline-block;animation:pulse-anim 1.4s ease-in-out infinite}
    @keyframes pulse-anim{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.5);opacity:.5}}

    /* FOOTER */
    footer{background:var(--grad-dark-footer);color:rgba(255,255,255,.7);text-align:center;padding:50px 24px 30px}
    .footer-links{display:flex;flex-wrap:wrap;justify-content:center;gap:6px 18px;margin-bottom:24px}
    .footer-links a{color:rgba(255,255,255,.55);font-size:13px;transition:color .2s}
    .footer-links a:hover{color:#fff}

    @media(max-width:991px){.nav-links{display:none}.hamburger{display:flex}}
    @media(max-width:767px){.page-banner{padding:90px 0 48px}}
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
