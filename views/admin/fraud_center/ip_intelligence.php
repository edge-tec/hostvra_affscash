<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#127760;</div>
        <div>
            <div class="fds-page-title">Device &amp; IP Intelligence</div>
            <div class="fds-page-sub">IP history &bull; Click/conversion fingerprinting &bull; Blocklist lookup</div>
        </div>
    </div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/ip-intelligence';
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- IP search -->
<div class="fds-card mb-3">
    <div class="fds-card-body" style="padding:12px 16px">
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if($affId>0): ?><input type="hidden" name="affiliate_id" value="<?= $affId ?>"><?php endif; ?>
            <?php if($offerId>0): ?><input type="hidden" name="offer_id" value="<?= $offerId ?>"><?php endif; ?>
            <input type="text" name="ip" class="form-control" placeholder="Search IP address..." value="<?= Helpers::e($search) ?>" style="max-width:320px">
            <button type="submit" class="fds-btn fds-btn-primary">&#128269; Look Up</button>
            <?php if($search): ?><a href="/admin/fraud-center/ip-intelligence<?= ($affId||$offerId)?'?'.http_build_query(array_filter(['affiliate_id'=>$affId,'offer_id'=>$offerId])):'' ?>" class="fds-btn fds-btn-outline">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<?php if ($drillIp): ?>
<!-- IP drill-down -->
<div class="fds-card mb-3" style="border:2px solid rgba(139,92,246,.4)">
    <div class="fds-card-header" style="background:rgba(139,92,246,.08)">
        <span class="fds-card-title">&#128269; IP Report: <?= Helpers::e($drillIp) ?></span>
        <form method="post" action="/admin/fraud-center/blocklist" style="display:inline">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="type" value="ip">
            <input type="hidden" name="value" value="<?= Helpers::e($drillIp) ?>">
            <input type="hidden" name="reason" value="Manually blocked via IP Intelligence">
            <input type="hidden" name="bl_action" value="block">
            <input type="hidden" name="scope" value="all">
            <button type="submit" class="fds-btn fds-btn-sm fds-btn-danger">Block IP</button>
        </form>
    </div>
    <div class="fds-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
                <div class="fds-section-label">Click History (last 100)</div>
                <div class="fds-table-wrap" style="max-height:300px;overflow-y:auto">
                    <table class="fds-table">
                        <thead><tr><th>Date</th><th>Offer</th><th>Affiliate</th><th>Country</th></tr></thead>
                        <tbody>
                        <?php foreach ($drillClicks as $cl): ?>
                        <tr>
                            <td class="fds-text-sm fds-text-muted"><?= date('M j H:i', strtotime($cl['clicked_at'])) ?></td>
                            <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                            <td class="fds-text-sm"><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></td>
                            <td class="fds-text-sm"><?= Helpers::e($cl['country'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($drillClicks)): ?><tr><td colspan="4" class="fds-empty">No clicks</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <div class="fds-section-label">Conversion History (last 50)</div>
                <div class="fds-table-wrap" style="max-height:300px;overflow-y:auto">
                    <table class="fds-table">
                        <thead><tr><th>Date</th><th>Offer</th><th>Payout</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($drillConvs as $cv): ?>
                        <tr>
                            <td class="fds-text-sm fds-text-muted"><?= date('M j H:i', strtotime($cv['converted_at'])) ?></td>
                            <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                            <td>$<?= number_format($cv['payout'], 2) ?></td>
                            <td><span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($drillConvs)): ?><tr><td colspan="4" class="fds-empty">No conversions</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top IPs table -->
<div class="fds-card mb-3">
    <div class="fds-card-header">
        <span class="fds-card-title">Top IPs by Click Volume (30 days)</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($total) ?> unique IPs</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>IP</th><th>Total Clicks</th><th>Last 1h</th><th>Offers</th><th>Affiliates</th><th>First Seen</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($ipStats as $row): $risk = $row['clicks_1h'] >= 50 ? 'critical' : ($row['total_clicks'] >= 500 ? 'high' : ($row['total_clicks'] >= 100 ? 'medium' : 'low')); ?>
            <tr>
                <td><a href="?ip=<?= urlencode($row['ip_address']) ?>" class="fds-link"><?= Helpers::e($row['ip_address']) ?></a></td>
                <td><?= fraud_score_badge(min(100, (int)($row['total_clicks'] / 10))) ?> <?= number_format($row['total_clicks']) ?></td>
                <td><?= $row['clicks_1h'] > 0 ? '<strong style="color:'.($row['clicks_1h']>=50?'#EF4444':'inherit').'">'.$row['clicks_1h'].'</strong>' : '0' ?></td>
                <td><?= $row['unique_offers'] ?></td>
                <td><?= $row['unique_affs'] ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j', strtotime($row['first_seen'])) ?></td>
                <td>
                    <a href="?ip=<?= urlencode($row['ip_address']) ?>" class="fds-btn fds-btn-sm fds-btn-outline">Details</a>
                    <form method="post" action="/admin/fraud-center/blocklist" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="type" value="ip">
                        <input type="hidden" name="value" value="<?= Helpers::e($row['ip_address']) ?>">
                        <input type="hidden" name="reason" value="Manually blocked via IP Intelligence">
                        <input type="hidden" name="bl_action" value="block">
                        <input type="hidden" name="scope" value="all">
                        <button type="submit" class="fds-btn fds-btn-sm fds-btn-danger">Block</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ipStats)): ?><tr><td colspan="7" class="fds-empty">No data</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)"><?= fds_pagination($pag, '/admin/fraud-center/ip-intelligence', ['ip'=>$search]) ?></div>
    <?php endif; ?>
</div>

<!-- Currently blocked IPs -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">&#128274; Active IP Blocklist</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>IP</th><th>Reason</th><th>Action</th><th>Hits</th><th>Added</th></tr></thead>
            <tbody>
            <?php foreach ($blockedIps as $entry): ?>
            <tr>
                <td><strong><?= Helpers::e($entry['value']) ?></strong></td>
                <td class="fds-text-sm"><?= Helpers::e($entry['reason'] ?? '—') ?></td>
                <td><span class="fds-badge fds-badge-critical"><?= $entry['action'] ?></span></td>
                <td><?= number_format($entry['hit_count']) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, Y', strtotime($entry['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($blockedIps)): ?><tr><td colspan="5" class="fds-empty">No IPs blocked</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
