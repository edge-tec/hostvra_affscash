<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Stat 1: Total Tenants -->
    <div class="stat-card">
        <div class="stat-label">Total Tenant Accounts</div>
        <div class="stat-value"><?= number_format($totalTenants) ?></div>
        <div style="font-size: 11.5px; color: #818CF8; margin-top: 6px;">All networks on platform</div>
    </div>
    <!-- Stat 2: Active Subscriptions -->
    <div class="stat-card">
        <div class="stat-label">Active Subscriptions</div>
        <div class="stat-value"><?= number_format($activeSubs) ?></div>
        <div style="font-size: 11.5px; color: #34D399; margin-top: 6px;">Paying recurring clients</div>
    </div>
    <!-- Stat 3: Expired accounts -->
    <div class="stat-card">
        <div class="stat-label">Expired / Suspended</div>
        <div class="stat-value"><?= number_format($expiredSubs) ?></div>
        <div style="font-size: 11.5px; color: #F87171; margin-top: 6px;">Need renewal attention</div>
    </div>
    <!-- Stat 4: 30 Days Revenue -->
    <div class="stat-card">
        <div class="stat-label">30-Day SaaS Revenue</div>
        <div class="stat-value">$<?= number_format($revenue30d, 2) ?></div>
        <div style="font-size: 11.5px; color: #A78BFA; margin-top: 6px;">Last 30 days SaaS income</div>
    </div>
</div>

<div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Stat 5: 6 Months Revenue -->
    <div class="stat-card">
        <div class="stat-label">6-Month SaaS Revenue</div>
        <div class="stat-value">$<?= number_format($revenue6m, 2) ?></div>
        <div style="font-size: 11.5px; color: #F472B6; margin-top: 6px;">SaaS sales over 6 months</div>
    </div>
    <!-- Stat 6: Annual Revenue -->
    <div class="stat-card">
        <div class="stat-label">Annual SaaS Revenue</div>
        <div class="stat-value">$<?= number_format($revenue1y, 2) ?></div>
        <div style="font-size: 11.5px; color: #60A5FA; margin-top: 6px;">Sales in last 365 days</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Recent Tenant Accounts -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">🆕 Recently Registered Tenants</h3>
        </div>
        <div style="padding: 20px; overflow-x: auto;">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTenants)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted);">No tenants registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTenants as $t): ?>
                            <tr>
                                <td>
                                    <strong><?= Helpers::e($t['company_name']) ?></strong>
                                    <?php if ($t['custom_domain']): ?>
                                        <br><span style="font-size: 11px; color: #818CF8;"><?= Helpers::e($t['custom_domain']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= $t['status'] === 'active' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $t['status'] === 'active' ? '#34D399' : '#FCA5A5' ?>;">
                                        <?= strtoupper($t['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #C084FC; font-weight: 600;"><?= Helpers::e($t['plan_name'] ?? 'No Active Plan') ?></span>
                                </td>
                                <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent SaaS Payments -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">💳 Recent SaaS Billing Logs</h3>
        </div>
        <div style="padding: 20px; overflow-x: auto;">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Action</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBilling)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted);">No billing logs registered yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBilling as $b): ?>
                            <tr>
                                <td><strong><?= Helpers::e($b['company_name']) ?></strong></td>
                                <td><span style="font-size: 12px; color: #E2E8F0;"><?= Helpers::e($b['action']) ?></span></td>
                                <td><span style="color: #34D399; font-weight: 700;">$<?= number_format($b['amount'], 2) ?></span></td>
                                <td><?= date('M d, H:i', strtotime($b['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
