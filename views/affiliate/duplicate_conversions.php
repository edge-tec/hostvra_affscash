<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>Duplicate Conversions</h1>
        <p>Your conversions on the same offer from the same IP address</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    <div class="stat-card">
        <div class="stat-label">Duplicate Clusters</div>
        <div class="stat-value"><?= number_format($totalGroups) ?></div>
        <div class="stat-sub">Unique (offer + IP) groups</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Duplicate Conversions</div>
        <div class="stat-value" style="color:#DC2626"><?= number_format($totalRows) ?></div>
        <div class="stat-sub">Total flagged rows</div>
    </div>
</div>

<?php if (empty($groups)): ?>
<div class="card">
    <div class="card-body text-center text-muted" style="padding:48px 16px">
        No duplicate conversions found in the selected period.
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Duplicates by (Offer · IP)</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="min-width:1000px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>IP ADDRESS</th>
                    <th>CONVERSION ID</th>
                    <th>STATUS</th>
                    <th>PAYOUT</th>
                    <th>TXN ID</th>
                    <th>GOAL</th>
                    <th>CONVERTED AT</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $statusBadgeMap = [
                'approved'      => 'success',
                'pending'       => 'warning',
                'rejected'      => 'danger',
                'chargebacked'  => 'muted',
            ];
            foreach ($groups as $groupKey => $clusterRows):
                $first = $clusterRows[0];
                $clusterSize = (int)$first['dup_count'];
            ?>
                <tr style="background:#FEF2F2;font-weight:600">
                    <td colspan="8" style="padding:8px 12px;color:#991B1B;border-top:2px solid #FECACA">
                        <span style="display:inline-block;background:#DC2626;color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;margin-right:8px">
                            <?= $clusterSize ?> DUPLICATES
                        </span>
                        Offer <strong><?= Helpers::e($first['offer_name'] ?: '#'.(int)$first['offer_id']) ?></strong>
                        · IP <code style="background:#fff;padding:1px 6px;border-radius:3px;font-size:11px;color:#991B1B"><?= Helpers::e($first['ip_address']) ?></code>
                    </td>
                </tr>
                <?php foreach ($clusterRows as $r): ?>
                <tr>
                    <td style="white-space:nowrap"><?= Helpers::e($r['offer_name'] ?: '—') ?></td>
                    <td><code style="font-size:11px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['ip_address']) ?></code></td>
                    <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                    <td><span class="badge badge-<?= $statusBadgeMap[$r['status']] ?? 'muted' ?>"><?= Helpers::e(ucfirst($r['status'])) ?></span></td>
                    <td class="fw-bold">$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td><?= Helpers::e($r['transaction_id'] ?: '—') ?></td>
                    <td><?= Helpers::e($r['goal_name'] ?: '—') ?></td>
                    <td class="text-muted" style="white-space:nowrap"><?= Helpers::e(date('M j, Y H:i', strtotime($r['converted_at']))) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
