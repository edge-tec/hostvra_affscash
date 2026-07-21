<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<?php
// Status badge colours
$statusStyle = [
    'accepted'      => ['bg'=>'#ECFDF5','color'=>'#065F46','label'=>'Accepted'],
    'duplicate'     => ['bg'=>'#FEF3C7','color'=>'#92400E','label'=>'Duplicate'],
    'invalid_click' => ['bg'=>'#FEF2F2','color'=>'#991B1B','label'=>'Invalid Click'],
    'blocked'       => ['bg'=>'#FEF2F2','color'=>'#991B1B','label'=>'Blocked'],
    'capped'        => ['bg'=>'#FFF7ED','color'=>'#9A3412','label'=>'Capped'],
    'hidden'        => ['bg'=>'#F3F4F6','color'=>'#374151','label'=>'Hidden'],
    'fraud_blocked' => ['bg'=>'#FEF2F2','color'=>'#7F1D1D','label'=>'Fraud Blocked'],
    'error'         => ['bg'=>'#FEF2F2','color'=>'#991B1B','label'=>'Error'],
];
?>

<div class="page-header">
    <div>
        <h1>Postback Log</h1>
        <p>Log of every postback you fired to this tracker</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary btn-sm">&#8681; Export CSV</a>
    </div>
</div>

<!-- Stats -->
<div class="stat-grid-5">
    <div class="card" style="margin:0"><div class="card-body" style="padding:16px 20px">
        <div class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Total Postbacks</div>
        <div style="font-size:26px;font-weight:700;color:#1E293B;margin-top:4px"><?= number_format($stats['total'] ?? 0) ?></div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:16px 20px">
        <div class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Accepted</div>
        <div style="font-size:26px;font-weight:700;color:#059669;margin-top:4px"><?= number_format($stats['accepted'] ?? 0) ?></div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:16px 20px">
        <div class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Duplicates</div>
        <div style="font-size:26px;font-weight:700;color:#D97706;margin-top:4px"><?= number_format($stats['duplicate'] ?? 0) ?></div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:16px 20px">
        <div class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Rejected</div>
        <div style="font-size:26px;font-weight:700;color:#DC2626;margin-top:4px"><?= number_format($stats['rejected'] ?? 0) ?></div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:16px 20px">
        <div class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Total Payout</div>
        <div style="font-size:26px;font-weight:700;color:#1E293B;margin-top:4px">$<?= number_format($stats['total_payout'] ?? 0, 2) ?></div>
    </div></div>
</div>

<!-- Filters -->
<div class="card mb-3 filter-card filter-open" id="adv-apl-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Parameters</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group mb-0">
                <label style="font-size:12px">From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px">To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="min-width:160px">
                <label style="font-size:12px">Offer</label>
                <select name="offer_id" class="form-control" style="font-size:13px">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $filterOffer===$o['id']?'selected':'' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0" style="min-width:160px">
                <label style="font-size:12px">Status</label>
                <select name="status" class="form-control" style="font-size:13px">
                    <option value="all">All Statuses</option>
                    <?php foreach (array_keys($statusStyle) as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $statusStyle[$s]['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="/advertiser/postback-logs" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">Postbacks (<?= number_format(count($logs)) ?> shown)</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date / Time</th>
                    <th>Offer</th>
                    <th>Click ID</th>
                    <th>Conversion ID</th>
                    <th>Payout</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:40px">No postback records found for this period</td></tr>
            <?php else: ?>
            <?php foreach ($logs as $log):
                $st = $statusStyle[$log['status']] ?? ['bg'=>'#F3F4F6','color'=>'#374151','label'=>ucfirst($log['status'])];
                $isAccepted = $log['status'] === 'accepted';
            ?>
            <tr style="<?= !$isAccepted && $log['status'] !== 'duplicate' ? 'background:#FFFBEB' : '' ?>">
                <td class="text-muted" style="font-size:11px"><?= $log['id'] ?></td>
                <td class="text-muted" style="white-space:nowrap;font-size:12px"><?= Helpers::e(date('M j, Y H:i:s', strtotime($log['created_at']))) ?></td>
                <td class="text-sm"><?= Helpers::e($log['offer_name'] ?? '—') ?></td>
                <td>
                    <?php if ($log['click_id']): ?>
                    <code style="font-size:10px;background:#F1F5F9;padding:2px 5px;border-radius:3px"><?= Helpers::e($log['click_id']) ?></code>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($log['conversion_id']): ?>
                    <code style="font-size:10px;background:#ECFDF5;padding:2px 5px;border-radius:3px;color:#065F46"><?= Helpers::e(substr($log['conversion_id'],0,16)) ?>…</code>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="fw-bold">
                    <?= $log['payout'] > 0 ? '$'.number_format((float)$log['payout'],4) : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <span style="display:inline-block;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>">
                        <?= $st['label'] ?>
                    </span>
                </td>
                <td class="text-muted text-sm"><?= Helpers::e($log['reject_reason'] ?? '') ?></td>
                <td class="text-muted" style="font-family:monospace;font-size:11px"><?= Helpers::e($log['request_ip'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
