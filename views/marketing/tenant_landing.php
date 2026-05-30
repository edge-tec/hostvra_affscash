<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= Helpers::e($seoDesc ?? 'Scale your conversions instantly.') ?>">
<title><?= Helpers::e($pageTitle ?? 'CPA Affiliate Network') ?></title>
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

.glass-card {
    background: var(--bg-card);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border-glass);
    border-radius: 16px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    padding: 30px;
}

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
        <?php if (!empty($lp['logo_path']) && $lp['logo_path'] !== '/logoo.png'): ?>
            <img src="<?= Helpers::e($lp['logo_path']) ?>" alt="Logo" style="max-height:30px;">
        <?php else: ?>
            🌐 <?= Helpers::e(Config::get('config','app.name') ?? 'CPA Performance') ?>
        <?php endif; ?>
    </a>

    <div class="nav-links">
        <a href="/" class="nav-item active">Home</a>
        <a href="/features" class="nav-item">Features</a>
        <a href="/pricing" class="nav-item">Pricing</a>
        <a href="/docs" class="nav-item">API Docs</a>
        <a href="/login" class="nav-item" style="margin-left: 20px;">Sign In</a>
        <a href="/register" class="nav-item nav-cta">Join Network</a>
    </div>
</nav>

<div class="section-container">
    <!-- Hero Welcome -->
    <div style="text-align: center; padding: 60px 0 80px;">
        <h1 style="font-size: 50px; font-weight: 800; line-height: 1.2; margin: 0 0 20px; font-family: 'Rajdhani', sans-serif; background: linear-gradient(to right, #FFFFFF, #C7D2FE); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <?= Helpers::e($lp['hero_headline'] ?? 'The Premier Affiliate Network for Exclusive CPA Deals') ?>
        </h1>
        <p style="font-size: 17px; color: #94A3B8; line-height: 1.6; max-width: 750px; margin: 0 auto 40px;">
            <?= Helpers::e($lp['hero_sub'] ?? 'Join Acme CPA Network to access top optimized rotators, hand-picked payout caps, weekly invoices, and reliable click servers.') ?>
        </p>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <a href="/register" class="nav-cta" style="text-decoration: none; padding: 14px 36px; font-size: 15px;">Become an Affiliate</a>
            <a href="/login" style="background: rgba(255, 255, 255, 0.05); color: #FFFFFF; font-weight: 700; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 14px 36px; text-decoration: none; transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">Advertiser Login</a>
        </div>
    </div>

    <!-- Product Features -->
    <div style="margin-bottom: 100px;">
        <h2 style="font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 50px;">⚡ High-Converting Performance Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <div class="glass-card">
                <span style="font-size: 28px;">🔥</span>
                <h3 style="font-size: 19px; margin: 16px 0 10px;"><?= Helpers::e($content['item_1_title'] ?? 'Exclusive Campaigns') ?></h3>
                <p style="color: #94A3B8; font-size: 13.5px; line-height: 1.6; margin: 0;">
                    <?= Helpers::e($content['item_1_desc'] ?? 'Access hand-picked in-house conversion offers with optimized payouts and stable revenue flows.') ?>
                </p>
            </div>
            <div class="glass-card">
                <span style="font-size: 28px;">🚀</span>
                <h3 style="font-size: 19px; margin: 16px 0 10px;"><?= Helpers::e($content['item_2_title'] ?? 'Smartlink Nodes') ?></h3>
                <p style="color: #94A3B8; font-size: 13.5px; line-height: 1.6; margin: 0;">
                    <?= Helpers::e($content['item_2_desc'] ?? 'Direct your generic traffic to optimized smartlink nodes to maximize CTR and CPM performance.') ?>
                </p>
            </div>
            <div class="glass-card">
                <span style="font-size: 28px;">💸</span>
                <h3 style="font-size: 19px; margin: 16px 0 10px;"><?= Helpers::e($content['item_3_title'] ?? 'Weekly Payments') ?></h3>
                <p style="color: #94A3B8; font-size: 13.5px; line-height: 1.6; margin: 0;">
                    <?= Helpers::e($content['item_3_desc'] ?? 'Request invoice releases automatically with zero delay. Support Stripe, PayPal, and USDT.') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Active Offers Listing -->
    <div style="margin-bottom: 100px;">
        <h2 style="font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 50px;">🔥 Top Conversion Campaigns</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <?php if (empty($offers)): ?>
                <div class="glass-card" style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">
                    Special offers list is currently only visible for authenticated affiliates.
                </div>
            <?php else: ?>
                <?php foreach ($offers as $off): ?>
                    <div class="glass-card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                        <h4 style="margin: 0 0 12px; font-size: 16px; color: #FFFFFF; font-weight: 700;"><?= Helpers::e($off['name']) ?></h4>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 12px;">
                            <span style="font-size: 12px; color: var(--text-muted);">Type: <?= strtoupper($off['payout_type']) ?></span>
                            <strong style="color: var(--primary-color); font-size: 15px;">$<?= number_format($off['payout_amount'], 2) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tenant Custom FAQ -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 32px; font-weight: 700; text-align: center; margin-bottom: 50px;">❓ Network FAQ</h2>
        <div class="glass-card">
            <h4 style="margin: 0 0 8px; font-size: 16px; color: #FFFFFF; font-weight: 700;"><?= Helpers::e($content['q_1'] ?? 'How to register?') ?></h4>
            <p style="margin: 0; font-size: 14px; color: #94A3B8; line-height: 1.6;">
                <?= Helpers::e($content['a_1'] ?? 'Click register at the top right, fill out the form, and our manager will review your affiliate details within 24 hours.') ?>
            </p>
        </div>
    </div>
</div>

<footer style="background: rgba(11, 9, 26, 0.7); backdrop-filter: blur(10px); border-top: 1px solid var(--border-glass); padding: 40px 30px; text-align: center; font-size: 13.5px; color: #64748B;">
    <div style="max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            &copy; <?= date('Y') ?> <?= Helpers::e(Config::get('config','app.name') ?? 'CPA Network') ?>. All Rights Reserved.
        </div>
        <div style="display: flex; gap: 24px;">
            <a href="/privacy-policy" style="color: #64748B; text-decoration: none;">Privacy Policy</a>
            <a href="/docs" style="color: #64748B; text-decoration: none;">API Guides</a>
            <a href="/login" style="color: #64748B; text-decoration: none;">Sign In</a>
        </div>
    </div>
</footer>

</body>
</html>
