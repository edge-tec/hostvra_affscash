<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>Performance Report</h1>
        <p>Clicks, conversions, CR, revenue — broken down by offer, affiliate, and geo.</p>
    </div>
</div>

<!-- Filters --------------------------------------------------------------- -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;align-items:end">
            <div class="form-group mb-0"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group mb-0"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div class="form-group mb-0"><label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All offers</option>
                    <?php foreach ($myOffers as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $offerId === (int)$o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">All affiliates</option>
                    <?php foreach ($myAffiliates as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $affId === (int)$a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Country</label>
                <select name="country" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= Helpers::e($c['country']) ?>" <?= $country===$c['country'] ? 'selected' : '' ?>><?= Helpers::e($c['country']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Device</label>
                <select name="device" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['desktop','mobile','tablet','bot','unknown'] as $d): ?>
                    <option value="<?= $d ?>" <?= $device===$d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><button class="btn btn-primary" style="width:100%">Run</button></div>
        </form>
    </div>
</div>

<!-- KPI cards ------------------------------------------------------------- -->
<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format((int)$totals['clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format((int)$totals['conv']) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR</div><div class="stat-value"><?= $totals['cr'] ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:#10B981"><?= number_format((int)$totals['approved']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:#F59E0B"><?= number_format((int)$totals['pending']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format((float)$totals['revenue'], 2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format((float)$totals['payout'], 2) ?></div></div>
</div>

<!-- By offer ------------------------------------------------------------- -->
<div class="card mb-3">
    <div class="card-header" style="font-weight:700;font-size:14px">By Offer</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Offer</th><th>Clicks</th><th>Conv.</th><th>Approved</th><th>CR</th><th>Revenue</th><th>Payout</th><th>Margin</th></tr></thead>
            <tbody>
            <?php if (empty($byOffer)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:24px">No data</td></tr>
            <?php else: foreach ($byOffer as $r):
                $cr  = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
                $mrg = (float)$r['revenue'] - (float)$r['payout'];
            ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($r['name']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= number_format((int)$r['approved']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= number_format((float)$r['revenue'], 2) ?></td>
                    <td>$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td class="fw-bold">$<?= number_format($mrg, 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- By affiliate --------------------------------------------------------- -->
<div class="card mb-3">
    <div class="card-header" style="font-weight:700;font-size:14px">By Affiliate</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Affiliate</th><th>Code</th><th>Clicks</th><th>Conv.</th><th>Approved</th><th>CR</th><th>Payout</th></tr></thead>
            <tbody>
            <?php if (empty($byAff)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:24px">No data</td></tr>
            <?php else: foreach ($byAff as $r):
                $cr = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
            ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($r['name']) ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= Helpers::e($r['affiliate_code']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= number_format((int)$r['approved']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= number_format((float)$r['payout'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Geo: countries ------------------------------------------------------- -->
<div class="card mb-3">
    <div class="card-header" style="font-weight:700;font-size:14px">By Country</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Country</th><th>Clicks</th><th>Conv.</th><th>CR</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php if (empty($byCountry)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No data</td></tr>
            <?php else: foreach ($byCountry as $r):
                $cr = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
            ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($r['country']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= number_format((float)$r['revenue'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Geo: city + region --------------------------------------------------- -->
<div class="card">
    <div class="card-header" style="font-weight:700;font-size:14px">By Region / City</div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr><th>Country</th><th>Region</th><th>City</th><th>Clicks</th><th>Conv.</th><th>CR</th></tr></thead>
            <tbody>
            <?php if (empty($byRegion)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No data</td></tr>
            <?php else: foreach ($byRegion as $r):
                $cr = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
            ?>
                <tr>
                    <td><?= Helpers::e($r['country']) ?></td>
                    <td><?= Helpers::e($r['region']) ?></td>
                    <td><?= Helpers::e($r['city']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= $cr ?>%</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
