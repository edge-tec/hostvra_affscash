<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div style="max-width: 1100px; margin: 0 auto; padding: 20px 0;">
    <!-- Expiry Alert Notice if applicable -->
    <?php if (!$activeSub || strtotime($activeSub['ends_at']) <= time()): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; padding: 20px; color: #FCA5A5; display: flex; align-items: center; gap: 16px; margin-bottom: 40px; box-shadow: 0 4px 20px rgba(239, 68, 68, 0.1);">
            <div style="font-size: 32px;">⚠️</div>
            <div>
                <h4 style="margin: 0 0 4px; font-weight: 700; color: #FFFFFF;">Subscription Expired or Suspended</h4>
                <p style="margin: 0; font-size: 13.5px; color: #F3F4F6;">Your administrative access and click tracking systems are currently paused. Please select one of the subscription tiers below to renew and instantly restore full platform services.</p>
            </div>
        </div>
    <?php else: ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 12px; padding: 20px; color: #34D399; display: flex; align-items: center; gap: 16px; margin-bottom: 40px;">
            <div style="font-size: 32px;">✅</div>
            <div>
                <h4 style="margin: 0 0 4px; font-weight: 700; color: #FFFFFF;">Subscription Active</h4>
                <p style="margin: 0; font-size: 13.5px; color: #E5E7EB;">Your workspace is fully operational. Current Tier: <strong><?= Helpers::e($activeSub['plan_name']) ?></strong> (Expires: <strong><?= date('M d, Y', strtotime($activeSub['ends_at'])) ?></strong>).</p>
            </div>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-size: 30px; font-weight: 800; margin: 0 0 10px; color: #FFFFFF;">Choose Your Subscription Tier</h2>
        <p style="margin: 0; color: #94A3B8; font-size: 15px;">Scale your affiliate tracking platform with precise limits tailored to your performance goals.</p>
    </div>

    <!-- Pricing Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
        <?php foreach ($plans as $p): ?>
            <?php $isCurrent = ($activeSub && $activeSub['plan_id'] == $p['id']); ?>
            <div style="background: rgba(30, 27, 75, 0.45); backdrop-filter: blur(12px); border: 2px solid <?= $isCurrent ? '#8B5CF6' : 'rgba(139, 92, 246, 0.18)' ?>; border-radius: 16px; padding: 30px; display: flex; flex-direction: column; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.3); transition: all 0.3s;" onmouseover="this.style.borderColor='#8B5CF6'" onmouseout="this.style.borderColor='<?= $isCurrent ? '#8B5CF6' : 'rgba(139, 92, 246, 0.18)' ?>'">
                
                <?php if ($isCurrent): ?>
                    <span style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #8B5CF6; color: #FFFFFF; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 14px; border-radius: 20px; letter-spacing: 0.05em; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);">Your Current Tier</span>
                <?php endif; ?>

                <h3 style="margin: 0 0 10px; font-size: 20px; font-weight: 700; color: #FFFFFF; text-align: center;"><?= Helpers::e($p['name']) ?></h3>
                
                <div style="text-align: center; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(139, 92, 246, 0.15);">
                    <span style="font-size: 40px; font-weight: 800; color: #FFFFFF;">$<?= number_format($p['price'], 2) ?></span>
                    <span style="color: #94A3B8; font-size: 14.5px;"> / <?= $p['duration'] ?> Days</span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 30px; font-size: 14px; line-height: 2.3; flex-grow: 1;">
                    <li style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 4px 0;">
                        <span style="color: #8B5CF6; font-weight: bold;">✓</span>
                        <span style="color: #E2E8F0;"><strong><?= $p['max_users'] > 0 ? number_format($p['max_users']) : 'Unlimited' ?></strong> Team Members</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 4px 0;">
                        <span style="color: #8B5CF6; font-weight: bold;">✓</span>
                        <span style="color: #E2E8F0;"><strong><?= $p['max_offers'] > 0 ? number_format($p['max_offers']) : 'Unlimited' ?></strong> Active Offers</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 4px 0;">
                        <span style="color: #8B5CF6; font-weight: bold;">✓</span>
                        <span style="color: #E2E8F0;"><strong><?= $p['max_domains'] > 0 ? number_format($p['max_domains']) : 'Unlimited' ?></strong> Custom Tracking Domains</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.04); padding: 4px 0;">
                        <span style="color: #8B5CF6; font-weight: bold;">✓</span>
                        <span style="color: #E2E8F0;"><strong><?= $p['max_click_limits'] > 0 ? number_format($p['max_click_limits']) : 'Unlimited' ?></strong> Clicks / Period</span>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; padding: 4px 0;">
                        <span style="color: #8B5CF6; font-weight: bold;">✓</span>
                        <span style="color: #E2E8F0;"><strong><?= $p['max_storage'] > 0 ? number_format($p['max_storage']) . ' MB' : 'Unlimited' ?></strong> File Storage</span>
                    </li>
                </ul>

                <a href="/admin/subscription/checkout?plan_id=<?= $p['id'] ?>" style="display: block; text-align: center; text-decoration: none; background: <?= $isCurrent ? 'rgba(255,255,255,0.1)' : 'linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%)' ?>; color: #FFFFFF; font-weight: 700; font-size: 14px; padding: 12px 20px; border-radius: 8px; box-shadow: <?= $isCurrent ? 'none' : '0 4px 15px rgba(139, 92, 246, 0.3)' ?>; transition: all 0.3s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <?= $isCurrent ? 'Extend Current Tier' : 'Select Plan Tier' ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
