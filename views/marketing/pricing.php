<?php require BASE_PATH . '/views/layouts/marketing.php'; ?>

<?php if (Tenant::getTenantId() === null): ?>
<!-- SaaS Main Pricing Plans Page -->
<div style="text-align: center; margin-bottom: 50px;">
    <span style="background: rgba(139, 92, 246, 0.15); color: #C7D2FE; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 20px; border: 1px solid rgba(139, 92, 246, 0.3); text-transform: uppercase; letter-spacing: 0.1em; display: inline-block; margin-bottom: 16px;">Flexible Subscriptions</span>
    <h1 style="font-size: 48px; font-weight: 800; line-height: 1.2; margin: 0 0 16px; font-family: 'Rajdhani', sans-serif; background: linear-gradient(to right, #FFFFFF, #A5B4FC); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        SaaS Subscription Plans &amp; Tiers
    </h1>
    <p style="font-size: 16px; color: #94A3B8; max-width: 600px; margin: 0 auto; line-height: 1.6;">
        Choose the plan that fits your business scale. Deploy your performance tracking network with advanced bot protection, real-time conversion triggers, and custom domain matching.
    </p>
</div>

<!-- Plan Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 80px;">
    <?php if (empty($plans)): ?>
        <div class="glass-card" style="grid-column: 1/-1; text-align: center; padding: 50px; color: var(--text-muted);">
            <span style="font-size: 40px; display: block; margin-bottom: 16px;">💎</span>
            <h3>No Subscription Plans Active</h3>
            <p>Please contact the system administrator to configure platform pricing tiers.</p>
        </div>
    <?php else: ?>
        <?php foreach ($plans as $p): ?>
            <div style="background: rgba(30, 27, 75, 0.45); backdrop-filter: blur(12px); border: 1px solid var(--border-glass); border-radius: 16px; padding: 40px 30px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.3); transition: all 0.3s; position: relative; overflow: hidden;" onmouseover="this.style.borderColor='var(--primary-color)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='var(--border-glass)'; this.style.transform='translateY(0)';" class="pricing-card">
                <?php if ($p['price'] > 99): ?>
                    <span style="position: absolute; top: 15px; right: -30px; background: var(--primary-grad); color: #FFFFFF; font-size: 10px; font-weight: 800; padding: 4px 30px; transform: rotate(45deg); text-transform: uppercase; letter-spacing: 0.1em; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">Popular</span>
                <?php endif; ?>
                
                <h3 style="margin: 0 0 10px; font-size: 24px; text-align: center; color: #FFFFFF; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.05em;"><?= Helpers::e($p['name']) ?></h3>
                
                <div style="text-align: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(139, 92, 246, 0.15);">
                    <span style="font-size: 46px; font-weight: 800; color: #FFFFFF;">$<?= number_format($p['price'], 2) ?></span>
                    <span style="color: #94A3B8; font-size: 14px; display: block; margin-top: 4px;">Billing cycle: <?= $p['duration'] ?> Days</span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 35px; font-size: 14px; line-height: 2.2; flex-grow: 1;">
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 6px 0;">
                        <span style="color: #94A3B8;">👥 Active User Seats</span>
                        <strong><?= $p['max_users'] > 0 ? number_format($p['max_users']) : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 6px 0;">
                        <span style="color: #94A3B8;">🏷️ Active Campaigns</span>
                        <strong><?= $p['max_offers'] > 0 ? number_format($p['max_offers']) : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 6px 0;">
                        <span style="color: #94A3B8;">🌐 Custom Domain Mapping</span>
                        <strong><?= $p['max_domains'] > 0 ? number_format($p['max_domains']) : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 6px 0;">
                        <span style="color: #94A3B8;">⚡ Monthly Clicks Volume</span>
                        <strong><?= $p['max_click_limits'] > 0 ? number_format($p['max_click_limits']) : 'Unlimited' ?></strong>
                    </li>
                </ul>

                <a href="/register" class="nav-cta" style="display: block; text-align: center; text-decoration: none; padding: 14px 20px; font-size: 14.5px; border-radius: 8px; transition: transform 0.2s;">Get Started Instantly</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Resolved Admin Tenant Custom Pricing / Commission Page -->
<div style="text-align: center; margin-bottom: 50px;">
    <span style="background: rgba(99, 102, 241, 0.15); color: #C7D2FE; font-size: 12px; font-weight: 700; padding: 6px 16px; border-radius: 20px; border: 1px solid rgba(99, 102, 241, 0.3); text-transform: uppercase; letter-spacing: 0.1em; display: inline-block; margin-bottom: 16px;">Payout Speeds &amp; Terms</span>
    <h1 style="font-size: 48px; font-weight: 800; line-height: 1.2; margin: 0 0 16px; font-family: 'Rajdhani', sans-serif; background: linear-gradient(to right, #FFFFFF, #C7D2FE); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Payout Tiers &amp; Commission Rates
    </h1>
    <p style="font-size: 16px; color: #94A3B8; max-width: 600px; margin: 0 auto; line-height: 1.6;">
        We offer the highest-converting network rates and absolute transparency. Check our flexible payout tiers and rapid payment solutions.
    </p>
</div>

<!-- Custom Payout Card Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 80px;">
    
    <!-- Tier 1 -->
    <div class="glass-card" style="transition: transform 0.3s; display: flex; flex-direction: column;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <span style="font-size: 32px; background: rgba(99, 102, 241, 0.15); padding: 10px; border-radius: 12px;">💸</span>
            <div>
                <h3 style="font-size: 20px; margin: 0; font-family: 'Rajdhani', sans-serif;">Standard Publisher</h3>
                <span style="color: var(--text-muted); font-size: 12px;">Default New Affiliates</span>
            </div>
        </div>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
            Perfect for new publishers. Get access to active public offers immediately with bi-weekly payout distributions.
        </p>
        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px; font-size: 13.5px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94A3B8;">Billing Speed</span>
                <strong>Net-15 (Bi-Weekly)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94A3B8;">Minimum Payout</span>
                <strong>$100.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #94A3B8;">Supported Methods</span>
                <strong>USDT / Wire / PayPal</strong>
            </div>
        </div>
    </div>

    <!-- Tier 2 -->
    <div class="glass-card" style="transition: transform 0.3s; border-color: var(--primary-color); display: flex; flex-direction: column; position: relative;" onmouseover="this.style.transform='translateY(-5px)';" onmouseout="this.style.transform='translateY(0)';" >
        <span style="position: absolute; top: 12px; right: 12px; background: rgba(99, 102, 241, 0.2); color: #C7D2FE; font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">Top Tier</span>
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <span style="font-size: 32px; background: rgba(139, 92, 246, 0.15); padding: 10px; border-radius: 12px;">🚀</span>
            <div>
                <h3 style="font-size: 20px; margin: 0; font-family: 'Rajdhani', sans-serif;">VIP Performance</h3>
                <span style="color: var(--text-muted); font-size: 12px;">High Volume Traffic Only</span>
            </div>
        </div>
        <p style="color: #94A3B8; font-size: 14px; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
            Designed for scaling affiliates. Receive dedicated support, custom conversion rotators, higher caps, and weekly invoices.
        </p>
        <div style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px; font-size: 13.5px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94A3B8;">Billing Speed</span>
                <strong>Net-7 (Weekly Payments)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94A3B8;">Minimum Payout</span>
                <strong>$500.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #94A3B8;">Supported Methods</span>
                <strong>Stripe / USDT / Wise</strong>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="glass-card" style="background: rgba(15, 12, 38, 0.6); padding: 40px; border-radius: 16px; text-align: center; margin-bottom: 60px; border: 1px dashed rgba(139, 92, 246, 0.3);">
    <h3 style="font-size: 26px; font-family: 'Rajdhani', sans-serif; font-weight: 700; color: #FFFFFF; margin-bottom: 12px;">Have custom volume requirements?</h3>
    <p style="color: #94A3B8; font-size: 15px; max-width: 600px; margin: 0 auto 24px; line-height: 1.6;">
        We offer customized enterprise models, smartlink APIs, and anti-fraud system white-labeling for high-performance networks. Let's discuss your scaling targets.
    </p>
    <a href="/register" class="nav-cta" style="text-decoration: none; padding: 12px 30px; border-radius: 6px; display: inline-block;">Contact Sales Desk</a>
</div>

<?php require BASE_PATH . '/views/layouts/marketing_footer.php'; ?>
