<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128202;</div>
        <div>
            <div class="fds-page-title">Conversion Fraud Scanner</div>
            <div class="fds-page-sub">Fast conversions &bull; Duplicate IPs &bull; Stale pending</div>
        </div>
    </div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/conversion-scanner';
$showConvId     = true;
$exportParams   = ['tab' => $tab];
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Tabs -->
<div class="fds-tabs mb-3">
    <a href="?tab=fast&affiliate_id=<?= $affId ?>&offer_id=<?= $offerId ?>" class="fds-tab <?= $tab==='fast' ? 'active':'' ?>">
        &#9889; Fast (&lt;30s) <span class="fds-badge fds-badge-<?= $stats['fast']>0?'critical':'low' ?>"><?= number_format($stats['fast']) ?></span>
    </a>
    <a href="?tab=duplicate&affiliate_id=<?= $affId ?>&offer_id=<?= $offerId ?>" class="fds-tab <?= $tab==='duplicate' ? 'active':'' ?>">
        &#128260; Duplicates <span class="fds-badge fds-badge-<?= $stats['duplicate']>0?'high':'low' ?>"><?= number_format($stats['duplicate']) ?></span>
    </a>
    <a href="?tab=pending_old&affiliate_id=<?= $affId ?>&offer_id=<?= $offerId ?>" class="fds-tab <?= $tab==='pending_old' ? 'active':'' ?>">
        &#9201; Stale Pending <span class="fds-badge fds-badge-<?= $stats['pending_old']>0?'medium':'low' ?>"><?= number_format($stats['pending_old']) ?></span>
    </a>
</div>

<?php if ($tab === 'fast'): ?>
<!-- Fast conversions -->
<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Fast Conversions — click-to-convert under 30 seconds</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($stats['fast']) ?> total</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>#</th><th>Offer</th><th>Affiliate</th><th>IP</th><th>Time Gap</th><th>Payout</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($fastConversions as $cv): $gap = (int)$cv['time_diff']; $riskCls = $gap < 5 ? 'critical' : ($gap < 15 ? 'high' : 'medium'); ?>
            <tr>
                <td class="fds-text-muted fds-text-sm"><?= $cv['id'] ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cv['affiliate_code'] ?? '—') ?></td>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cv['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($cv['ip_address'] ?? '—') ?></a></td>
                <td><?= fraud_severity_badge($riskCls) ?> <strong><?= $gap ?>s</strong></td>
                <td>$<?= number_format($cv['payout'], 2) ?></td>
                <td><span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cv['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($fastConversions)): ?><tr><td colspan="8" class="fds-empty">No fast conversions detected</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($pagFast['pages']??1) > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)"><?= fds_pagination($pagFast, '/admin/fraud-center/conversion-scanner', ['tab'=>'fast']) ?></div>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'duplicate'): ?>
<!-- Duplicate conversions -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Duplicate Conversions — same IP + offer (7 days)</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>IP</th><th>Offer</th><th>Affiliate</th><th>Dup Count</th><th>Total Payout</th><th>First</th><th>Last</th></tr></thead>
            <tbody>
            <?php foreach ($duplicateConversions as $row): ?>
            <tr>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($row['ip_address']) ?>" class="fds-link"><?= Helpers::e($row['ip_address']) ?></a></td>
                <td class="fds-text-sm"><?= Helpers::e($row['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($row['affiliate_code'] ?? '—') ?></td>
                <td><?= fraud_score_badge(min(100, $row['dup_count'] * 20)) ?> <strong><?= $row['dup_count'] ?>x</strong></td>
                <td>$<?= number_format($row['total_payout'], 2) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($row['first_conv'])) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($row['last_conv'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($duplicateConversions)): ?><tr><td colspan="7" class="fds-empty">No duplicate conversions found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'pending_old'): ?>
<!-- Stale pending -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Stale Pending Conversions — pending for over 7 days</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>#</th><th>Offer</th><th>Affiliate</th><th>IP</th><th>Payout</th><th>Days Old</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($pendingOld as $row): ?>
            <tr>
                <td class="fds-text-muted fds-text-sm"><?= $row['id'] ?></td>
                <td class="fds-text-sm"><?= Helpers::e($row['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($row['affiliate_code'] ?? '—') ?></td>
                <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($row['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($row['ip_address']??'—') ?></a></td>
                <td>$<?= number_format($row['payout'], 2) ?></td>
                <td><?= fraud_severity_badge($row['days_old'] > 30 ? 'critical' : ($row['days_old'] > 14 ? 'high' : 'medium')) ?> <?= $row['days_old'] ?>d</td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, Y', strtotime($row['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pendingOld)): ?><tr><td colspan="7" class="fds-empty">No stale pending conversions</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($pagOld['pages']??1) > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)"><?= fds_pagination($pagOld, '/admin/fraud-center/conversion-scanner', ['tab'=>'pending_old']) ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
