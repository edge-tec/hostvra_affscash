<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>
            <a href="/advertiser/reports/offers" style="text-decoration:none;color:inherit">Offer Report</a>
            <span style="color:var(--text-muted);font-weight:400">›</span>
            <?= Helpers::e($offer['name']) ?>
        </h1>
        <p>
            OFF-<?= str_pad((int)$offer['id'], 4, '0', STR_PAD_LEFT) ?>
            &middot; <?= Helpers::e($offer['payout_type']) ?> $<?= number_format((float)$offer['payout_amount'], 2) ?>
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <input type="hidden" name="id" value="<?= (int)$offer['id'] ?>">
            <div class="form-group mb-0"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group mb-0"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div style="align-self:flex-end"><button class="btn btn-primary">Run</button></div>
        </form>
    </div>
</div>

<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-label">Total Traffic</div><div class="stat-value"><?= number_format((int)$totals['traffic']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format((int)$totals['clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format((int)$totals['conv']) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR</div><div class="stat-value"><?= $totals['cr'] ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:#10B981"><?= number_format((int)$totals['approved']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:#F59E0B"><?= number_format((int)$totals['pending']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format((float)$totals['revenue'], 2) ?></div></div>
</div>

<div class="card mb-3">
    <div class="card-header" style="font-weight:700;font-size:14px">Top Performing Affiliates</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Affiliate</th><th>Code</th><th>Clicks</th><th>Conv.</th><th>CR</th><th>Payout</th></tr></thead>
            <tbody>
            <?php if (empty($topAff)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No affiliate traffic in this window</td></tr>
            <?php else: foreach ($topAff as $r):
                $cr = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
            ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($r['name']) ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= Helpers::e($r['affiliate_code']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= number_format((float)$r['payout'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header" style="font-weight:700;font-size:14px">Traffic Sources</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Source</th><th>Clicks</th><th>Conv.</th><th>CR</th></tr></thead>
            <tbody>
            <?php if (empty($bySource)): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No source data captured</td></tr>
            <?php else: foreach ($bySource as $r):
                $cr = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
            ?>
                <tr>
                    <td><?= Helpers::e($r['source']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= $cr ?>%</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
    <div class="card">
        <div class="card-header" style="font-weight:700;font-size:14px">Device Breakdown</div>
        <div class="table-wrap" style="overflow:auto">
            <table>
                <thead><tr><th>Device</th><th>Clicks</th><th>Conv.</th></tr></thead>
                <tbody>
                <?php if (empty($byDevice)): ?>
                    <tr><td colspan="3" class="text-center text-muted" style="padding:16px">No data</td></tr>
                <?php else: foreach ($byDevice as $r): ?>
                    <tr><td><?= Helpers::e($r['k']) ?></td><td><?= number_format((int)$r['clicks']) ?></td><td><?= number_format((int)$r['conv']) ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="font-weight:700;font-size:14px">Browser Distribution</div>
        <div class="table-wrap" style="overflow:auto">
            <table>
                <thead><tr><th>Browser</th><th>Clicks</th><th>Conv.</th></tr></thead>
                <tbody>
                <?php if (empty($byBrowser)): ?>
                    <tr><td colspan="3" class="text-center text-muted" style="padding:16px">No data</td></tr>
                <?php else: foreach ($byBrowser as $r): ?>
                    <tr><td><?= Helpers::e($r['k']) ?></td><td><?= number_format((int)$r['clicks']) ?></td><td><?= number_format((int)$r['conv']) ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="font-weight:700;font-size:14px">OS Distribution</div>
        <div class="table-wrap" style="overflow:auto">
            <table>
                <thead><tr><th>OS</th><th>Clicks</th><th>Conv.</th></tr></thead>
                <tbody>
                <?php if (empty($byOs)): ?>
                    <tr><td colspan="3" class="text-center text-muted" style="padding:16px">No data</td></tr>
                <?php else: foreach ($byOs as $r): ?>
                    <tr><td><?= Helpers::e($r['k']) ?></td><td><?= number_format((int)$r['clicks']) ?></td><td><?= number_format((int)$r['conv']) ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
