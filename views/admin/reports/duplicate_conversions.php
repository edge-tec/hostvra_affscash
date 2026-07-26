<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Duplicate Conversions</h1>
        <p>Conversions sharing the same offer &amp; IP address — flagged for review</p>
    </div>
</div>

<!-- ── Filter bar ─────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="dupFilterForm" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <div class="form-group mb-0">
                <label>Date Preset</label>
                <select id="quickPresetSelect" class="form-control" onchange="applyDatePreset(this.value)">
                    <option value="custom">Custom Range</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last_7_days">Last 7 Days</option>
                    <option value="last_15_days">Last 15 Days</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_90_days">Last 90 Days</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="inputFromDate" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="inputToDate" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['approved','pending','rejected','chargebacked'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2" style="flex-wrap:wrap;align-items:center">
            <span class="text-muted text-sm fw-bold">Quick Ranges:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('today')">Today</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('yesterday')">Yesterday</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('last_7_days')">Last 7 Days</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('last_15_days')">Last 15 Days</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('this_month')">This Month</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('last_month')">Last Month</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDatePreset('last_90_days')">Last 90 Days</button>
        </div>
    </div>
</div>

<script>
function formatDate(d) {
    let month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function applyDatePreset(preset) {
    if (!preset || preset === 'custom') return;
    const now = new Date();
    let fromDate = new Date();
    let toDate = new Date();

    if (preset === 'today') {
        fromDate = now;
        toDate = now;
    } else if (preset === 'yesterday') {
        let y = new Date();
        y.setDate(now.getDate() - 1);
        fromDate = y;
        toDate = y;
    } else if (preset === 'last_7_days') {
        let d = new Date();
        d.setDate(now.getDate() - 6);
        fromDate = d;
        toDate = now;
    } else if (preset === 'last_15_days') {
        let d = new Date();
        d.setDate(now.getDate() - 14);
        fromDate = d;
        toDate = now;
    } else if (preset === 'this_month') {
        fromDate = new Date(now.getFullYear(), now.getMonth(), 1);
        toDate = now;
    } else if (preset === 'last_month') {
        fromDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        toDate = new Date(now.getFullYear(), now.getMonth(), 0);
    } else if (preset === 'last_90_days') {
        let d = new Date();
        d.setDate(now.getDate() - 89);
        fromDate = d;
        toDate = now;
    }

    document.getElementById('inputFromDate').value = formatDate(fromDate);
    document.getElementById('inputToDate').value = formatDate(toDate);
    document.getElementById('dupFilterForm').submit();
}
</script>

<!-- ── Summary cards ──────────────────────────────────────────────────── -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    <div class="stat-card">
        <div class="stat-label">Duplicate Clusters</div>
        <div class="stat-value"><?= number_format($totalGroups) ?></div>
        <div class="stat-sub">Unique (offer + IP) groups</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Duplicate Conversions</div>
        <div class="stat-value" style="color:#DC2626"><?= number_format($totalRows) ?></div>
        <div class="stat-sub">Total rows across clusters</div>
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
        <table style="min-width:1300px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>IP ADDRESS</th>
                    <th>CONVERSION ID</th>
                    <th>AFFILIATE</th>
                    <th>STATUS</th>
                    <th>PAYOUT</th>
                    <th>TXN ID</th>
                    <th>GOAL</th>
                    <th>CONVERTED AT</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $groupKey => $clusterRows):
                $first = $clusterRows[0];
                $clusterSize = count($clusterRows);
                $statusBadgeMap = [
                    'approved'      => 'success',
                    'pending'       => 'warning',
                    'rejected'      => 'danger',
                    'chargebacked'  => 'muted',
                ];
            ?>
                <tr style="background:#FEF2F2;font-weight:600">
                    <td colspan="10" style="padding:8px 12px;color:#991B1B;border-top:2px solid #FECACA">
                        <span style="display:inline-block;background:#DC2626;color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;margin-right:8px">
                            <?= (int)$clusterSize ?> DUPLICATES
                        </span>
                        Offer <strong><?= Helpers::e($first['offer_name'] ?: '#'.(int)$first['offer_id']) ?></strong>
                        · IP <code style="background:#fff;padding:1px 6px;border-radius:3px;font-size:11px;color:#991B1B"><?= Helpers::e($first['ip_address']) ?></code>
                    </td>
                </tr>
                <?php foreach ($clusterRows as $r):
                    $st = $r['status'];
                    $canReject = !in_array($st, ['rejected','chargebacked'], true);
                ?>
                <tr>
                    <td style="white-space:nowrap"><?= Helpers::e($r['offer_name'] ?: '—') ?></td>
                    <td><code style="font-size:11px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['ip_address']) ?></code></td>
                    <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                    <td style="white-space:nowrap">
                        <div><?= Helpers::e($r['affiliate_name'] ?: '—') ?></div>
                        <div class="text-muted" style="font-size:10px"><?= Helpers::e($r['affiliate_code'] ?? '') ?></div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $statusBadgeMap[$st] ?? 'muted' ?>"><?= Helpers::e(ucfirst($st)) ?></span>
                        <?php if ($st === 'rejected' && !empty($r['rejection_reason'])): ?>
                            <div title="<?= Helpers::e($r['rejection_reason']) ?>" style="margin-top:4px;font-size:10px;color:#DC2626;line-height:1.3;max-width:180px;white-space:normal">
                                <strong>Reason:</strong> <?= Helpers::e(strlen($r['rejection_reason'])>40 ? substr($r['rejection_reason'],0,40).'…' : $r['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold">$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td><?= Helpers::e($r['transaction_id'] ?: '—') ?></td>
                    <td><?= Helpers::e($r['goal_name'] ?: '—') ?></td>
                    <td class="text-muted" style="white-space:nowrap"><?= Helpers::e(date('M j, Y H:i', strtotime($r['converted_at']))) ?></td>
                    <td>
                        <?php if ($canReject): ?>
                        <form method="POST" action="/admin/conversions" style="display:inline"
                              onsubmit="return confirm('Reject this duplicate conversion? Affiliate balance and manager commission will be reversed if it was approved.');">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="conversion_id"   value="<?= Helpers::e($r['conversion_id']) ?>">
                            <input type="hidden" name="status"          value="rejected">
                            <input type="hidden" name="rejection_reason" value="Removed for duplicate conversion">
                            <input type="hidden" name="redirect_back"   value="/admin/reports/duplicate-conversions?<?= Helpers::e(http_build_query(['from'=>$from,'to'=>$to,'status'=>$status])) ?>">
                            <button class="btn btn-danger btn-sm" type="submit" style="font-size:11px;padding:4px 10px">Reject</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:11px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
