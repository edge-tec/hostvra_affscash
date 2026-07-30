<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>Duplicate Conversions</h1>
        <p>Conversions on the same offer from the same IP — for your managed affiliates</p>
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

<style>
/* 3D Glassmorphism Duplicate Conversions Control Panel */
#dup-filter-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.9) 100%) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 18px !important;
    box-shadow: 0 16px 40px -10px rgba(99, 102, 241, 0.12), 0 4px 16px rgba(0, 0, 0, 0.04) !important;
    margin-bottom: 24px !important;
    overflow: hidden !important;
}

html[data-theme="dark"] #dup-filter-card {
    background: linear-gradient(180deg, rgba(24, 18, 55, 0.95) 0%, rgba(18, 12, 42, 0.9) 100%) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4) !important;
}

.dup-filter-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important;
    gap: 16px 20px !important;
    align-items: flex-end !important;
    width: 100% !important;
}

.dup-filter-grid .form-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
}

.dup-filter-grid label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    margin-bottom: 0 !important;
}

html[data-theme="dark"] .dup-filter-grid label {
    color: rgba(255, 255, 255, 0.7) !important;
}

.dup-filter-grid .form-control {
    font-size: 13px !important;
    font-weight: 600 !important;
    padding: 9px 13px !important;
    border: 1.5px solid #E2E8F0 !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    color: #1E293B !important;
    outline: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    width: 100% !important;
    box-sizing: border-box !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02) !important;
}

html[data-theme="dark"] .dup-filter-grid .form-control {
    background: rgba(30, 24, 60, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.dup-filter-grid .form-control:hover {
    border-color: #A5B4FC !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.08) !important;
}

.dup-filter-grid .form-control:focus {
    border-color: #6366F1 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18) !important;
}

.dup-btn-apply {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13.5px !important;
    padding: 10px 24px !important;
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    width: 100% !important;
}

.dup-btn-apply:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.45) !important;
    color: #ffffff !important;
}

/* Quick Ranges 3D Pills */
.dup-quick-bar {
    display: flex !important;
    gap: 8px !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    margin-top: 16px !important;
    padding-top: 14px !important;
    border-top: 1px dashed #E2E8F0 !important;
}

html[data-theme="dark"] .dup-quick-bar {
    border-top-color: rgba(255, 255, 255, 0.1) !important;
}

.dup-preset-btn {
    padding: 5px 13px !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
    border-radius: 20px !important;
    background: #ffffff !important;
    color: #475569 !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    white-space: nowrap !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03) !important;
}

.dup-preset-btn:hover {
    background: rgba(99, 102, 241, 0.1) !important;
    color: #4F46E5 !important;
    border-color: rgba(99, 102, 241, 0.3) !important;
    transform: translateY(-1px) !important;
}

.dup-preset-btn.active {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #ffffff !important;
    border-color: transparent !important;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.35) !important;
}

/* 3D KPI Stats Grid */
.dup-stats-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.dup-stat-card {
    background: #ffffff !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 16px !important;
    padding: 18px 22px !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

html[data-theme="dark"] .dup-stat-card {
    background: rgba(20, 14, 45, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.dup-stat-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 24px rgba(99, 102, 241, 0.15) !important;
    border-color: rgba(99, 102, 241, 0.3) !important;
}

.dup-stat-card.stat-clusters { border-top: 3px solid #6366F1 !important; }
.dup-stat-card.stat-conversions { border-top: 3px solid #EF4444 !important; }

.dup-stat-label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #64748B !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    margin-bottom: 4px !important;
}

html[data-theme="dark"] .dup-stat-label {
    color: rgba(255, 255, 255, 0.65) !important;
}

.dup-stat-value {
    font-size: 28px !important;
    font-weight: 800 !important;
    color: #0F172A !important;
    line-height: 1.1 !important;
}

html[data-theme="dark"] .dup-stat-value {
    color: #ffffff !important;
}

.dup-stat-sub {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #94A3B8 !important;
    margin-top: 4px !important;
}

/* Status Badges */
.ac-badge-approved {
    background: #10b981 !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 3px 9px !important;
    border-radius: 20px !important;
}
.ac-badge-rejected {
    background: #ef4444 !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 3px 9px !important;
    border-radius: 20px !important;
}
.ac-badge-pending {
    background: #f59e0b !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 3px 9px !important;
    border-radius: 20px !important;
}
</style>

<div class="card mb-3 filter-card filter-open" id="dup-filter-card">
    <div class="card-body" style="padding: 22px 24px">
        <form method="GET" id="dupFilterForm">
            <input type="hidden" name="range" id="inputRange" value="<?= Helpers::e($currentRange) ?>">
            <div class="dup-filter-grid">
                <div class="form-group">
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
                <div class="form-group">
                    <label>From</label>
                    <input type="date" id="inputFromDate" name="from" class="form-control" value="<?= Helpers::e($from) ?>" onchange="onManualDateChange()">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" id="inputToDate" name="to" class="form-control" value="<?= Helpers::e($to) ?>" onchange="onManualDateChange()">
                </div>
                <div class="form-group">
                    <button type="submit" class="dup-btn-apply">Apply</button>
                </div>
            </div>
        </form>
        <div class="dup-quick-bar">
            <span class="text-muted text-sm fw-bold" style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:#64748B">Quick Ranges:</span>
            <button type="button" class="dup-preset-btn <?= $currentRange==='today'?'active':'' ?>" onclick="applyDatePreset('today')">Today</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='yesterday'?'active':'' ?>" onclick="applyDatePreset('yesterday')">Yesterday</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='last_7_days'?'active':'' ?>" onclick="applyDatePreset('last_7_days')">Last 7 Days</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='last_15_days'?'active':'' ?>" onclick="applyDatePreset('last_15_days')">Last 15 Days</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='this_month'?'active':'' ?>" onclick="applyDatePreset('this_month')">This Month</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='last_month'?'active':'' ?>" onclick="applyDatePreset('last_month')">Last Month</button>
            <button type="button" class="dup-preset-btn <?= $currentRange==='last_90_days'?'active':'' ?>" onclick="applyDatePreset('last_90_days')">Last 90 Days</button>
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

<div class="dup-stats-grid">
    <div class="dup-stat-card stat-clusters">
        <div class="dup-stat-label">Duplicate Clusters</div>
        <div class="dup-stat-value"><?= number_format($totalGroups) ?></div>
        <div class="dup-stat-sub">Unique (offer + IP) groups</div>
    </div>
    <div class="dup-stat-card stat-conversions">
        <div class="dup-stat-label">Duplicate Conversions</div>
        <div class="dup-stat-value" style="color:#DC2626"><?= number_format($totalRows) ?></div>
        <div class="dup-stat-sub">Total flagged rows</div>
    </div>
</div>

<?php if (!$hasAffiliates): ?>
<div class="alert alert-warning">No affiliates are assigned to your account yet.</div>
<?php elseif (empty($groups)): ?>
<div class="card">
    <div class="card-body text-center text-muted" style="padding:48px 16px">
        No duplicate conversions found in the selected period.
    </div>
</div>
<?php else: ?>
<div class="card" style="border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.03);overflow:hidden">
    <div class="card-header" style="background:#f8fafc;padding:16px 20px;border-bottom:1px solid #e2e8f0">
        <span class="card-title" style="font-weight:800;font-size:14px;color:#1e293b">Duplicates by (Offer · IP)</span>
        <span class="text-muted text-sm" style="font-size:12px"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="min-width:1200px;font-size:12px">
            <thead>
                <tr style="background:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;letter-spacing:0.05em">
                    <th>OFFER</th>
                    <th>IP ADDRESS</th>
                    <th>CONVERSION ID</th>
                    <th>AFFILIATE</th>
                    <th>STATUS</th>
                    <th>PAYOUT</th>
                    <th>TXN ID</th>
                    <th>GOAL</th>
                    <th>CONVERTED AT</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($groups as $groupKey => $clusterRows):
                $first = $clusterRows[0];
                $clusterSize = count($clusterRows);
            ?>
                <tr style="background:rgba(239, 68, 68, 0.04);font-weight:600">
                    <td colspan="9" style="padding:10px 16px;color:#991B1B;border-top:2px solid #fca5a5;border-bottom:1px solid #fecaca">
                        <span style="display:inline-flex;align-items:center;background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%);color:#fff;border-radius:6px;padding:3px 9px;font-size:11px;font-weight:800;margin-right:10px;box-shadow:0 2px 6px rgba(239,68,68,0.25)">
                            <?= $clusterSize ?> DUPLICATES
                        </span>
                        Offer <strong style="color:#7f1d1d"><?= Helpers::e($first['offer_name'] ?: '#'.(int)$first['offer_id']) ?></strong>
                        <span style="margin:0 6px;opacity:0.5">•</span> IP <code style="background:#ffffff;padding:2px 8px;border-radius:6px;font-size:11px;color:#991b1b;border:1px solid #fca5a5;font-weight:700"><?= Helpers::e($first['ip_address']) ?></code>
                    </td>
                </tr>
                <?php foreach ($clusterRows as $r):
                    $st = strtolower($r['status'] ?? 'pending');
                    $stClass = $st === 'approved' ? 'ac-badge-approved' : ($st === 'rejected' ? 'ac-badge-rejected' : 'ac-badge-pending');
                ?>
                <tr>
                    <td style="white-space:nowrap;font-weight:600;color:#1e293b"><?= Helpers::e($r['offer_name'] ?: '—') ?></td>
                    <td><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:5px;color:#475569;font-weight:600"><?= Helpers::e($r['ip_address']) ?></code></td>
                    <td><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:5px;color:#6366f1;font-weight:700"><?= Helpers::e($r['conversion_id']) ?></code></td>
                    <td style="white-space:nowrap">
                        <div style="font-weight:700;font-size:12.5px;color:#0f172a"><?= Helpers::e($r['affiliate_name'] ?: '—') ?></div>
                        <div class="text-muted" style="font-size:10.5px;font-family:monospace"><?= Helpers::e($r['affiliate_code'] ?? '') ?></div>
                    </td>
                    <td><span class="<?= $stClass ?>"><?= Helpers::e(strtoupper($r['status'])) ?></span></td>
                    <td class="fw-bold" style="color:#059669;font-size:13px">$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td><?= Helpers::e($r['transaction_id'] ?: '—') ?></td>
                    <td><?= Helpers::e($r['goal_name'] ?: '—') ?></td>
                    <td class="text-muted" style="white-space:nowrap;font-size:11.5px"><?= Helpers::e(date('M j, Y H:i', strtotime($r['converted_at']))) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
