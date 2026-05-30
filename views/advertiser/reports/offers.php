<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>Offer Report</h1>
        <p>Per-offer traffic, conversions, and revenue across the selected window.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <div class="form-group mb-0"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group mb-0"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div style="align-self:flex-end"><button class="btn btn-primary">Run</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead><tr>
                <th>Offer</th>
                <th>Status</th>
                <th>Payout</th>
                <th>Traffic</th>
                <th>Clicks</th>
                <th>Conv.</th>
                <th>Approved</th>
                <th>CR</th>
                <th>Revenue</th>
                <th>Payout Spent</th>
                <th>Margin</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="12" class="text-center text-muted" style="padding:32px">No data</td></tr>
            <?php else: foreach ($rows as $r):
                $cr  = $r['clicks'] > 0 ? round(($r['conv']/$r['clicks'])*100,2) : 0;
                $mrg = (float)$r['revenue'] - (float)$r['payout'];
            ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($r['name']) ?></td>
                    <td><span class="badge badge-<?= $r['status']==='active'?'success':'muted' ?>"><?= Helpers::e($r['status']) ?></span></td>
                    <td><?= Helpers::e($r['payout_type']) ?> $<?= number_format((float)$r['payout_amount'], 2) ?></td>
                    <td><?= number_format((int)$r['traffic']) ?></td>
                    <td><?= number_format((int)$r['clicks']) ?></td>
                    <td><?= number_format((int)$r['conv']) ?></td>
                    <td><?= number_format((int)$r['approved']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= number_format((float)$r['revenue'], 2) ?></td>
                    <td>$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td class="fw-bold">$<?= number_format($mrg, 2) ?></td>
                    <td>
                        <a href="/advertiser/reports/offers?id=<?= (int)$r['id'] ?>&from=<?= Helpers::e($from) ?>&to=<?= Helpers::e($to) ?>" class="btn btn-secondary btn-sm">Details</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
