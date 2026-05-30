<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128269;</div>
        <div>
            <div class="fds-page-title">Click Intelligence</div>
            <div class="fds-page-sub">IP burst analysis &bull; CVR anomalies &bull; Click patterns</div>
        </div>
    </div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/click-intelligence';
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Search bar -->
<div class="fds-card mb-3">
    <div class="fds-card-body" style="padding:12px 16px">
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if($affId>0): ?><input type="hidden" name="affiliate_id" value="<?= $affId ?>"><?php endif; ?>
            <?php if($offerId>0): ?><input type="hidden" name="offer_id" value="<?= $offerId ?>"><?php endif; ?>
            <input type="text" name="q" class="form-control" placeholder="Search IP, affiliate code, offer..." value="<?= Helpers::e($search) ?>" style="max-width:360px">
            <button type="submit" class="fds-btn fds-btn-primary">Search</button>
            <?php if($search): ?><a href="/admin/fraud-center/click-intelligence<?= ($affId||$offerId)?'?'.http_build_query(array_filter(['affiliate_id'=>$affId,'offer_id'=>$offerId])):'' ?>" class="fds-btn fds-btn-outline">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- IP Burst Analysis -->
<div class="fds-card mb-3">
    <div class="fds-card-header">
        <span class="fds-card-title">&#128308; IP Burst Alerts (24h &mdash; 20+ clicks)</span>
        <span class="fds-badge fds-badge-<?= count($ipBursts) > 0 ? 'high' : 'low' ?>"><?= count($ipBursts) ?> IPs</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>IP Address</th><th>Clicks (24h)</th><th>Offers</th><th>Affiliates</th><th>First Seen</th><th>Last Seen</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($ipBursts as $row): $score = min(100, (int)($row['total_clicks'] / 5)); ?>
            <tr>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($row['ip_address']) ?>" class="fds-link"><?= Helpers::e($row['ip_address']) ?></a></td>
                <td><?= fraud_score_badge($score) ?> <strong><?= number_format($row['total_clicks']) ?></strong></td>
                <td><?= $row['unique_offers'] ?></td>
                <td><?= $row['unique_affs'] ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j H:i', strtotime($row['first_seen'])) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j H:i', strtotime($row['last_seen'])) ?></td>
                <td>
                    <a href="/admin/fraud-center/blocklist?add_ip=<?= urlencode($row['ip_address']) ?>" class="fds-btn fds-btn-sm fds-btn-danger">Block IP</a>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($row['ip_address']) ?>" class="fds-btn fds-btn-sm fds-btn-outline">Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ipBursts)): ?><tr><td colspan="7" class="fds-empty">No IP bursts detected in the last 24 hours</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Affiliate CVR Anomalies -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title">&#9888; Low CVR Affiliates (7 days &mdash; min 50 clicks)</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Affiliate</th><th>Clicks</th><th>Conversions</th><th>CVR</th><th>Risk</th></tr></thead>
            <tbody>
            <?php foreach ($aff_cvr as $row): $cvr = (float)$row['cvr']; $risk = $cvr < 0.1 ? 'critical' : ($cvr < 0.5 ? 'high' : ($cvr < 1.0 ? 'medium' : 'low')); ?>
            <tr>
                <td><strong><?= Helpers::e($row['affiliate_code']) ?></strong><br><span class="fds-text-sm fds-text-muted"><?= Helpers::e($row['first_name'].' '.$row['last_name']) ?></span></td>
                <td><?= number_format($row['clicks']) ?></td>
                <td><?= number_format($row['convs']) ?></td>
                <td><strong><?= $cvr ?>%</strong></td>
                <td><?= fraud_severity_badge($risk) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($aff_cvr)): ?><tr><td colspan="5" class="fds-empty">No CVR anomalies found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Raw click log -->
<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Click Log</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($total) ?> total</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>#</th><th>IP</th><th>Offer</th><th>Affiliate</th><th>Country</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($clicks as $cl): ?>
            <tr>
                <td class="fds-text-muted fds-text-sm"><?= $cl['id'] ?></td>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address'] ?? '') ?>" class="fds-link"><?= Helpers::e($cl['ip_address'] ?? '—') ?></a></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['country'] ?? '—') ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cl['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($clicks)): ?><tr><td colspan="6" class="fds-empty">No clicks found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)">
        <?= fds_pagination($pag, '/admin/fraud-center/click-intelligence', ['q'=>$search]) ?>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
