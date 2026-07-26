<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>Duplicate Conversions</h1>
        <p>Your conversions on the same offer from the same IP address</p>
    </div>
</div>

<?php
$todayDate = date('Y-m-d');
$currentRange = Helpers::get('range') ?: '';
if (empty($currentRange)) {
    if ($from === $todayDate && $to === $todayDate) {
        $currentRange = 'today';
    } elseif ($from === date('Y-m-d', strtotime('-1 day')) && $to === date('Y-m-d', strtotime('-1 day'))) {
        $currentRange = 'yesterday';
    } elseif ($from === date('Y-m-d', strtotime('-6 days')) && $to === $todayDate) {
        $currentRange = 'last_7_days';
    } elseif ($from === date('Y-m-d', strtotime('-14 days')) && $to === $todayDate) {
        $currentRange = 'last_15_days';
    } elseif ($from === date('Y-m-01') && $to === $todayDate) {
        $currentRange = 'this_month';
    } elseif ($from === date('Y-m-01', strtotime('first day of last month')) && $to === date('Y-m-t', strtotime('last month'))) {
        $currentRange = 'last_month';
    } elseif ($from === date('Y-m-d', strtotime('-89 days')) && $to === $todayDate) {
        $currentRange = 'last_90_days';
    } else {
        $currentRange = 'custom';
    }
}
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="dupFilterForm" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <input type="hidden" name="range" id="inputRange" value="<?= Helpers::e($currentRange) ?>">
            <div class="form-group mb-0">
                <label>Date Preset</label>
                <select id="quickPresetSelect" class="form-control" onchange="applyDatePreset(this.value)">
                    <option value="custom" <?= $currentRange==='custom'?'selected':'' ?>>Custom Range</option>
                    <option value="today" <?= $currentRange==='today'?'selected':'' ?>>Today</option>
                    <option value="yesterday" <?= $currentRange==='yesterday'?'selected':'' ?>>Yesterday</option>
                    <option value="last_7_days" <?= $currentRange==='last_7_days'?'selected':'' ?>>Last 7 Days</option>
                    <option value="last_15_days" <?= $currentRange==='last_15_days'?'selected':'' ?>>Last 15 Days</option>
                    <option value="this_month" <?= $currentRange==='this_month'?'selected':'' ?>>This Month</option>
                    <option value="last_month" <?= $currentRange==='last_month'?'selected':'' ?>>Last Month</option>
                    <option value="last_90_days" <?= $currentRange==='last_90_days'?'selected':'' ?>>Last 90 Days</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="inputFromDate" name="from" class="form-control" value="<?= Helpers::e($from) ?>" onchange="onManualDateChange()">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="inputToDate" name="to" class="form-control" value="<?= Helpers::e($to) ?>" onchange="onManualDateChange()">
            </div>
            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2" style="flex-wrap:wrap;align-items:center">
            <span class="text-muted text-sm fw-bold">Quick Ranges:</span>
            <button type="button" class="btn btn-sm <?= $currentRange==='today'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('today')">Today</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='yesterday'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('yesterday')">Yesterday</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='last_7_days'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('last_7_days')">Last 7 Days</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='last_15_days'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('last_15_days')">Last 15 Days</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='this_month'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('this_month')">This Month</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='last_month'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('last_month')">Last Month</button>
            <button type="button" class="btn btn-sm <?= $currentRange==='last_90_days'?'btn-primary':'btn-outline-secondary' ?>" onclick="applyDatePreset('last_90_days')">Last 90 Days</button>
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

function onManualDateChange() {
    document.getElementById('inputRange').value = 'custom';
    document.getElementById('quickPresetSelect').value = 'custom';
}

function applyDatePreset(preset) {
    if (!preset) return;
    document.getElementById('inputRange').value = preset;
    document.getElementById('quickPresetSelect').value = preset;

    if (preset === 'custom') return;

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
                $clusterSize = count($clusterRows);
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
