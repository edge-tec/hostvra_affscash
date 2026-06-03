<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>Click Report</h1><p>Click-level data for your managed affiliates</p></div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#11123; Export CSV</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="mgr-cr-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <?php $drpFromId='mgrcr-from'; $drpToId='mgrcr-to'; $drpFormId='mgr-cr-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="mgrcr-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="mgrcr-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $offerId == $o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $selAffId == $a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit==$l ? 'selected':'' ?>><?= number_format($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Click Type</label>
                <select name="click_filter" class="form-control">
                    <option value="all"       <?= ($clickFilter ?? 'all') === 'all'       ? 'selected' : '' ?>>All Clicks</option>
                    <option value="converted" <?= ($clickFilter ?? 'all') === 'converted' ? 'selected' : '' ?>>Converted Clicks</option>
                    <option value="approved"  <?= ($clickFilter ?? 'all') === 'approved'  ? 'selected' : '' ?>>Approved Conversions</option>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply Filters</button>
                <a href="/affiliate_manager/click_report" class="btn btn-secondary" style="margin-left:4px">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary cards -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-label">Total Clicks</div>
        <div class="stat-value"><?= number_format($totalClicks) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fraud Clicks</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($fraudCount) ?></div>
        <div class="stat-sub"><?= $totalClicks > 0 ? round($fraudCount/$totalClicks*100,1) : 0 ?>% fraud rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Payout</div>
        <div class="stat-value">$<?= number_format($totalPayout, 2) ?></div>
        <div class="stat-sub" style="font-size:10px">converted only</div>
    </div>
</div>

<!-- Click table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Click Log</span>
        <span class="text-muted text-sm">Showing <?= number_format($totalClicks) ?> clicks &middot; <?= Helpers::e($from) ?> to <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-mgr-clicks" style="min-width:1800px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>AFFILIATE</th>
                    <th>CLICK ID</th>
                    <th>SUB1</th>
                    <th>FRAUD</th>
                    <th>OS</th>
                    <th>BROWSER</th>
                    <th>DEVICE</th>
                    <th>IP ADDRESS</th>
                    <th>COUNTRY</th>
                    <th>CITY</th>
                    <th>PAYOUT</th>
                    <th>CLICK TIME</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clicks)): ?>
            <tr><td colspan="13" class="text-center text-muted" style="padding:32px">No clicks found for the selected filters</td></tr>
            <?php else: ?>
            <?php foreach ($clicks as $c):
                $isFraud = (bool)$c['is_fraud'];
            ?>
            <tr style="<?= $isFraud ? 'background:#FFF5F5' : '' ?>">
                <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($c['offer_name'] ?: '— Custom URL —') ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1'] ?: '—') ?></td>
                <td>
                    <?php if ($isFraud): ?>
                    <span class="badge badge-danger" title="Score: <?= (int)$c['fraud_score'] ?>">Fraud&nbsp;<?= (int)$c['fraud_score'] ?></span>
                    <?php else: ?>
                    <span class="badge badge-success">Clean</span>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($c['os'] ?: '—') ?></td>
                <td><?= Helpers::e($c['browser'] ?: '—') ?></td>
                <td><?= Helpers::e($c['device_type'] ?: '—') ?></td>
                <td><?= Helpers::e($c['ip_address'] ?: '—') ?></td>
                <td>
                    <?php if (!empty($c['country'])): ?>
                    <span title="<?= Helpers::e($c['country']) ?>">
                        <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png"
                             onerror="this.style.display='none'"
                             style="vertical-align:middle;margin-right:3px">
                        <?= Helpers::e($c['country']) ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($c['city'] ?: '—') ?></td>
                <td><?= $c['has_conversion'] ? '$'.number_format((float)$c['payout'],4)  : '<span style="color:#CBD5E1">—</span>' ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($c['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function() {
    $('#tbl-mgr-clicks').DataTable({
        destroy: true,
        pageLength: 50,
        order: [[12, 'desc']],
        scrollX: true,
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
