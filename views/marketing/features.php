<?php require BASE_PATH . '/views/layouts/marketing.php'; ?>

<?php if (Tenant::getTenantId() === null): ?>
<!-- SaaS platform Features page -->
<div style="text-align: center; margin-bottom: 50px;">
    <span style="background: rgba(139, 92, 246, 0.15); color: #C7D2FE; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 20px; border: 1px solid rgba(139, 92, 246, 0.3); text-transform: uppercase; letter-spacing: 0.1em; display: inline-block; margin-bottom: 16px;">Platform Highlights</span>
    <h1 style="font-size: 48px; font-weight: 800; line-height: 1.2; margin: 0 0 16px; font-family: 'Rajdhani', sans-serif; background: linear-gradient(to right, #FFFFFF, #A5B4FC); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Advanced Tracking Architecture
    </h1>
    <p style="font-size: 16px; color: #94A3B8; max-width: 600px; margin: 0 auto; line-height: 1.6;">
        EliteAli delivers enterprise-grade CPA tracking mechanics, multi-tenant scalability, anti-fraud risk engines, and real-time smartlinks.
    </p>
</div>

<!-- Dynamic Grid of platform Features -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 80px;">
    
    <!-- Feature 1 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">⚡</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_1_title'] ?? 'Real-Time Click Tracking') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Track and optimize millions of click pathways instantly with advanced redirect rotation mechanics. Scale your CPA networks with absolute accuracy.
        </p>
    </div>

    <!-- Feature 2 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(139, 92, 246, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">🛡️</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_2_title'] ?? 'Anti-Fraud Risk Engine') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Identify and filter high-risk traffic, proxy IPs, and bot patterns using real-time security algorithms. Protect your margins dynamically.
        </p>
    </div>

    <!-- Feature 3 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">🌐</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_3_title'] ?? 'Smartlink Rotators') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Dynamically route visitors to the highest converting offers using evolutionary device and geo-targeting. Maximize CTR and publisher payout values.
        </p>
    </div>

    <!-- Feature 4 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(139, 92, 246, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">📊</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;">SaaS Tenant Analytics Dashboard</h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Super admins get centralized stats on total active subscription revenue, monthly growth models, pending admin requests, and conversion performance indexes.
        </p>
    </div>

    <!-- Feature 5 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">🖇️</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;">Postback Delivery engine</h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Idempotent tracking triggers execute sub-affiliate postback scripts cleanly with zero loss. Complete history lookup desks are built-in.
        </p>
    </div>

    <!-- Feature 6 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(139, 92, 246, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">👑</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;">Audit Security &amp; IP Bans</h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            Log super admin "login-as-tenant" overrides completely. Block attackers instantly with automated rate limiters and permanent IP firewall bans.
        </p>
    </div>
</div>

<?php else: ?>
<!-- Resolved Tenant Features page -->
<div style="text-align: center; margin-bottom: 50px;">
    <span style="background: rgba(99, 102, 241, 0.15); color: #C7D2FE; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 20px; border: 1px solid rgba(99, 102, 241, 0.3); text-transform: uppercase; letter-spacing: 0.1em; display: inline-block; margin-bottom: 16px;">Publisher Perks</span>
    <h1 style="font-size: 48px; font-weight: 800; line-height: 1.2; margin: 0 0 16px; font-family: 'Rajdhani', sans-serif; background: linear-gradient(to right, #FFFFFF, #C7D2FE); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        High-Converting Campaign Delivery
    </h1>
    <p style="font-size: 16px; color: #94A3B8; max-width: 600px; margin: 0 auto; line-height: 1.6;">
        Join our elite CPA performance network. Access optimized conversion routes, smartlink arrays, and faster payment solutions.
    </p>
</div>

<!-- Dynamic Grid of Tenant features -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 80px;">
    
    <!-- Feature 1 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">🔥</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_1_title'] ?? 'Exclusive Campaigns') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            <?= Helpers::e($content['item_1_desc'] ?? 'Access hand-picked in-house conversion offers with optimized payouts and stable revenue flows.') ?>
        </p>
    </div>

    <!-- Feature 2 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(139, 92, 246, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">🚀</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_2_title'] ?? 'Smartlink Delivery') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            <?= Helpers::e($content['item_2_desc'] ?? 'Direct your generic traffic to optimized smartlink nodes to maximize CTR and CPM performance.') ?>
        </p>
    </div>

    <!-- Feature 3 -->
    <div class="glass-card" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">💸</span>
        <h3 style="font-size: 20px; margin: 0 0 10px; font-family: 'Rajdhani', sans-serif;"><?= Helpers::e($content['item_3_title'] ?? 'Weekly Faster Payments') ?></h3>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin: 0;">
            <?= Helpers::e($content['item_3_desc'] ?? 'Request invoice releases automatically with zero delay. Support Stripe, PayPal, Bank Transfers, and USDT payment logs.') ?>
        </p>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/marketing_footer.php'; ?>
