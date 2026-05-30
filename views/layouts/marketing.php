<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= Helpers::e($seoDesc ?? 'Scale your affiliate networks instantly.') ?>">
<title><?= Helpers::e($pageTitle ?? 'EliteAli Platform') ?></title>
<?php if ($fav = Config::get('config','app.favicon')): ?><link rel="icon" href="<?= Helpers::e($fav) ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg-main: #06050F;
    --bg-card: rgba(20, 16, 45, 0.45);
    --border-glass: rgba(139, 92, 246, 0.18);
    --primary-color: <?= Helpers::e($lp['primary_color'] ?? '#6366F1') ?>;
    --secondary-color: <?= Helpers::e($lp['secondary_color'] ?? '#8B5CF6') ?>;
    --primary-grad: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    --text-muted: #94A3B8;
}

body {
    background-color: var(--bg-main);
    background-image: radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.1) 0%, transparent 40%),
                      radial-gradient(circle at 85% 85%, rgba(139, 92, 246, 0.1) 0%, transparent 40%);
    color: #F8FAFC;
    font-family: 'DM Sans', sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* Glassmorphic Navbar */
.nav-bar {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    background: rgba(11, 9, 26, 0.7);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border-glass);
    padding: 16px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 24px;
    letter-spacing: 0.05em;
    color: #FFFFFF;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}

.nav-links {
    display: flex;
    gap: 30px;
    align-items: center;
}

.nav-item {
    color: #94A3B8;
    text-decoration: none;
    font-size: 14.5px;
    font-weight: 500;
    transition: color 0.3s;
}

.nav-item:hover, .nav-item.active {
    color: #FFFFFF;
}

.nav-cta {
    background: var(--primary-grad);
    color: #FFFFFF !important;
    font-weight: 700;
    border-radius: 8px;
    padding: 8px 20px;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    transition: all 0.3s;
}

.nav-cta:hover {
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5);
    transform: translateY(-1px);
}

.section-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 120px 20px 80px;
}

/* Layout cards */
.glass-card {
    background: var(--bg-card);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border-glass);
    border-radius: 16px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    padding: 30px;
}

/* Headlines */
h1, h2, h3 {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    color: #FFFFFF;
}
</style>
</head>
<body>

<!-- Header Navigation -->
<nav class="nav-bar">
    <a href="/" class="logo">
        <?php if (!empty($lp['logo_path']) && file_exists(BASE_PATH . $lp['logo_path'])): ?>
            <img src="<?= Helpers::e($lp['logo_path']) ?>" alt="Logo" style="max-height:30px;">
        <?php else: ?>
            🌐 <?= Helpers::e(Config::get('config','app.name') ?? 'EliteAli') ?>
        <?php endif; ?>
    </a>

    <div class="nav-links">
        <a href="/" class="nav-item <?= $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">Home</a>
        <a href="/features" class="nav-item <?= $_SERVER['REQUEST_URI'] === '/features' ? 'active' : '' ?>">Features</a>
        <a href="/pricing" class="nav-item <?= $_SERVER['REQUEST_URI'] === '/pricing' ? 'active' : '' ?>">Pricing Plans</a>
        <a href="/docs" class="nav-item <?= $_SERVER['REQUEST_URI'] === '/docs' ? 'active' : '' ?>">Developer APIs</a>
        <a href="/login" class="nav-item" style="margin-left: 20px;">Sign In</a>
        <a href="/register" class="nav-item nav-cta">Start Free Trial</a>
    </div>
</nav>

<div class="section-container">
