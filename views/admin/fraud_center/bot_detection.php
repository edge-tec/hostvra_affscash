<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#129302;</div>
        <div>
            <div class="fds-page-title">Bot Detection Center</div>
            <div class="fds-page-sub">Automated bot &amp; crawler traffic identified by User-Agent patterns</div>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Total Bot Clicks</div><div class="fds-kpi-val"><?= number_format($stats['total_bot']) ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">Bot Clicks (24h)</div><div class="fds-kpi-val"><?= number_format($stats['bot_24h']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Unique IPs</div><div class="fds-kpi-val"><?= number_format($stats['unique_ips']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Affected Affiliates</div><div class="fds-kpi-val"><?= number_format($stats['unique_affs']) ?></div></div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/bot-detection';
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Search -->
<div class="fds-card mb-3">
    <div class="fds-card-body" style="padding:12px 16px">
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if($affId>0): ?><input type="hidden" name="affiliate_id" value="<?= $affId ?>"><?php endif; ?>
            <?php if($offerId>0): ?><input type="hidden" name="offer_id" value="<?= $offerId ?>"><?php endif; ?>
            <input type="text" name="q" class="form-control" placeholder="Filter by IP or User-Agent..." value="<?= Helpers::e($search) ?>" style="max-width:400px">
            <button type="submit" class="fds-btn fds-btn-primary">Filter</button>
            <?php if($search): ?><a href="/admin/fraud-center/bot-detection<?= ($affId||$offerId)?'?'.http_build_query(array_filter(['affiliate_id'=>$affId,'offer_id'=>$offerId])):'' ?>" class="fds-btn fds-btn-outline">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- Top bot UAs -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title">Top Bot User-Agents (7 days)</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>User-Agent</th><th>Click Count</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($topBotUas as $row): ?>
            <tr>
                <td class="fds-text-sm" style="max-width:500px;word-break:break-all"><?= Helpers::e($row['user_agent'] ?? '—') ?></td>
                <td><strong><?= number_format($row['cnt']) ?></strong></td>
                <td>
                    <form method="post" action="/admin/fraud-center/blocklist" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="type" value="user_agent">
                        <input type="hidden" name="value" value="<?= Helpers::e($row['user_agent'] ?? '') ?>">
                        <input type="hidden" name="reason" value="Bot UA detected automatically">
                        <input type="hidden" name="bl_action" value="block">
                        <input type="hidden" name="scope" value="clicks">
                        <button type="submit" class="fds-btn fds-btn-sm fds-btn-danger">Block UA</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topBotUas)): ?><tr><td colspan="3" class="fds-empty">No bot UAs found in the last 7 days</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Affiliate bot summary -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title">Affiliate Bot Traffic Summary (7 days)</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Affiliate</th><th>Bot Clicks</th><th>Bot %</th><th>Risk</th></tr></thead>
            <tbody>
            <?php foreach ($affBotSummary as $row): $pct = (float)$row['bot_pct']; $risk = $pct > 50 ? 'critical' : ($pct > 20 ? 'high' : ($pct > 5 ? 'medium' : 'low')); ?>
            <tr>
                <td><strong><?= Helpers::e($row['affiliate_code'] ?? '—') ?></strong><br><span class="fds-text-sm fds-text-muted"><?= Helpers::e(($row['first_name']??'').' '.($row['last_name']??'')) ?></span></td>
                <td><?= number_format($row['bot_clicks']) ?></td>
                <td><strong><?= $pct ?>%</strong></td>
                <td><?= fraud_severity_badge($risk) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($affBotSummary)): ?><tr><td colspan="4" class="fds-empty">No significant bot traffic per affiliate</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bot click log -->
<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Bot Click Log</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($total) ?> total</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>#</th><th>IP</th><th>Offer</th><th>Affiliate</th><th>User-Agent</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($botClicks as $cl): ?>
            <tr>
                <td class="fds-text-muted fds-text-sm"><?= $cl['id'] ?></td>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address'] ?? '') ?>" class="fds-link"><?= Helpers::e($cl['ip_address'] ?? '—') ?></a></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></td>
                <td class="fds-text-sm" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($cl['user_agent'] ?? '') ?>"><?= Helpers::e(substr($cl['user_agent'] ?? '—', 0, 60)) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cl['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($botClicks)): ?><tr><td colspan="6" class="fds-empty">No bot clicks found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)">
        <?= fds_pagination($pag, '/admin/fraud-center/bot-detection', ['q'=>$search]) ?>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
