<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128683;</div>
        <div>
            <div class="fds-page-title">Auto Block Report</div>
            <div class="fds-page-sub">Real-time report of clicks and conversions blocked automatically</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <button onclick="location.reload()" class="fds-btn fds-btn-sm fds-btn-outline">&#8635; Refresh</button>
    </div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/auto-block-report';
$exportParams   = ['type' => 'clicks'];
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Export conversions button -->
<div style="margin-bottom:14px">
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','type'=>'conversions'])) ?>" class="fds-btn fds-btn-outline" style="font-size:13px">&#11123; Export Conversions CSV</a>
</div>

<!-- KPI strip -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(2,1fr)">
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Blocked Clicks (Selected Range)</div><div class="fds-kpi-val"><?= number_format($stats['blocked_clicks']) ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">Blocked Conversions (Selected Range)</div><div class="fds-kpi-val"><?= number_format($stats['blocked_convs']) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Blocked clicks -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">&#128683; Auto Blocked Clicks</span>
            <span class="fds-text-muted fds-text-sm"><?= count($recentBlockedClicks) ?> rows shown</span>
        </div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>Time</th><th>IP</th><th>Offer</th><th>Affiliate</th><th>Score</th></tr></thead>
                <tbody>
                <?php foreach ($recentBlockedClicks as $cl): ?>
                <tr>
                    <td class="fds-text-muted fds-text-sm"><?= date('m-d H:i', strtotime($cl['clicked_at'])) ?></td>
                    <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address'] ?? '') ?>" class="fds-link"><?= Helpers::e($cl['ip_address'] ?? '—') ?></a></td>
                    <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                    <td class="fds-text-sm"><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></td>
                    <td class="fds-text-sm"><span class="fds-badge fds-badge-critical"><?= $cl['fraud_score'] ?: 'N/A' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentBlockedClicks)): ?><tr><td colspan="5" class="fds-empty">No blocked clicks found</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Blocked conversions -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">&#9989; Auto Blocked Conversions</span>
            <span class="fds-text-muted fds-text-sm"><?= count($recentBlockedConversions) ?> rows shown</span>
        </div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>Offer</th><th>Affiliate</th><th>Payout</th><th>Reason</th></tr></thead>
                <tbody>
                <?php foreach ($recentBlockedConversions as $cv): ?>
                <tr>
                    <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                    <td class="fds-text-sm"><?= Helpers::e($cv['affiliate_code'] ?? '—') ?></td>
                    <td>$<?= number_format($cv['payout'], 2) ?></td>
                    <td><span class="fds-badge fds-badge-critical" title="<?= Helpers::e($cv['hide_reason']) ?>"><?= Helpers::e(str_replace('_blocked', '', $cv['hide_reason'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentBlockedConversions)): ?><tr><td colspan="4" class="fds-empty">No blocked conversions found</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
