<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header"><div><h1>Reports</h1><p>Your campaign performance</p></div></div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-center">
            <div class="form-group mb-0"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group mb-0"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div style="align-self:flex-end"><button class="btn btn-primary">Run Report</button></div>
        </form>
    </div>
</div>

<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format($totals['clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totals['conv']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($totals['revenue'],2) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR</div><div class="stat-value"><?= $totals['clicks']>0?round($totals['conv']/$totals['clicks']*100,2):0 ?>%</div></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Offer</th><th>Clicks</th><th>Conv.</th><th>Approved</th><th>Revenue</th><th>Payout</th><th>Margin</th></tr></thead>
            <tbody>
            <?php if(empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:32px">No data</td></tr>
            <?php else: ?>
            <?php foreach($rows as $r):
                $margin = $r['revenue'] - $r['payout'];
            ?>
            <tr>
                <td><?= Helpers::e($r['label']) ?></td>
                <td><?= Helpers::e($r['offer_name']) ?></td>
                <td><?= number_format($r['clicks']) ?></td>
                <td><?= number_format($r['conv']) ?></td>
                <td><?= number_format($r['approved']) ?></td>
                <td class="fw-bold">$<?= number_format($r['revenue'],2) ?></td>
                <td>$<?= number_format($r['payout'],2) ?></td>
                <td style="color:<?= $margin>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($margin,2) ?></td>
            </tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
