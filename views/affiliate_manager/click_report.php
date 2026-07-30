<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>Click Report</h1><p>Click-level data for your managed affiliates</p></div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#11123; Export CSV</a>
</div>

<style>
/* 3D Glassmorphism Click Report Control Panel */
#cr-filter-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.9) 100%) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 18px !important;
    box-shadow: 0 16px 40px -10px rgba(99, 102, 241, 0.12), 0 4px 16px rgba(0, 0, 0, 0.04) !important;
    margin-bottom: 24px !important;
    overflow: hidden !important;
}

html[data-theme="dark"] #cr-filter-card {
    background: linear-gradient(180deg, rgba(24, 18, 55, 0.95) 0%, rgba(18, 12, 42, 0.9) 100%) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4) !important;
}

.cr-filter-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) !important;
    gap: 16px 20px !important;
    align-items: flex-end !important;
    width: 100% !important;
    margin-top: 14px !important;
}

.cr-filter-grid .form-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
}

.cr-filter-grid label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    margin-bottom: 0 !important;
}

html[data-theme="dark"] .cr-filter-grid label {
    color: rgba(255, 255, 255, 0.7) !important;
}

.cr-filter-grid .form-control {
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

html[data-theme="dark"] .cr-filter-grid .form-control {
    background: rgba(30, 24, 60, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.cr-filter-grid .form-control:hover {
    border-color: #A5B4FC !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.08) !important;
}

.cr-filter-grid .form-control:focus {
    border-color: #6366F1 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18) !important;
}

.cr-btn-apply {
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

.cr-btn-apply:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.45) !important;
    color: #ffffff !important;
}

.cr-btn-reset {
    background: #F1F5F9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    padding: 9.5px 18px !important;
    border-radius: 12px !important;
    border: 1px solid #CBD5E1 !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    display: inline-block !important;
    text-align: center !important;
    width: 100% !important;
}

.cr-btn-reset:hover {
    background: #E2E8F0 !important;
    color: #1E293B !important;
}

/* 3D KPI Stats Grid */
.cr-stats-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.cr-stat-card {
    background: #ffffff !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 16px !important;
    padding: 18px 22px !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

html[data-theme="dark"] .cr-stat-card {
    background: rgba(20, 14, 45, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.cr-stat-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 24px rgba(99, 102, 241, 0.15) !important;
    border-color: rgba(99, 102, 241, 0.3) !important;
}

.cr-stat-card.stat-clicks { border-top: 3px solid #6366F1 !important; }
.cr-stat-card.stat-fraud { border-top: 3px solid #EF4444 !important; }
.cr-stat-card.stat-payout { border-top: 3px solid #059669 !important; }

.cr-stat-label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #64748B !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    margin-bottom: 4px !important;
}

html[data-theme="dark"] .cr-stat-label {
    color: rgba(255, 255, 255, 0.65) !important;
}

.cr-stat-value {
    font-size: 28px !important;
    font-weight: 800 !important;
    color: #0F172A !important;
    line-height: 1.1 !important;
}

html[data-theme="dark"] .cr-stat-value {
    color: #ffffff !important;
}

.cr-stat-sub {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #94A3B8 !important;
    margin-top: 4px !important;
}
</style>

<!-- Filters -->
<div class="card mb-3 filter-card filter-open" id="cr-filter-card">
    <div class="card-body" style="padding: 22px 24px">
        <form method="GET" id="mgr-cr-form">
            <?php $drpFromId='mgrcr-from'; $drpToId='mgrcr-to'; $drpFormId='mgr-cr-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            
            <div class="cr-filter-grid">
                <div class="form-group">
                    <label>From</label>
                    <input type="date" id="mgrcr-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" id="mgrcr-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
                </div>
                <div class="form-group">
                    <label>Offer</label>
                    <select name="offer_id" class="form-control">
                        <option value="">All Offers</option>
                        <?php foreach ($offerList as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $offerId == $o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Affiliate</label>
                    <select name="affiliate_id" class="form-control">
                        <option value="">All Affiliates</option>
                        <?php foreach ($affList as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $selAffId == $a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Limit</label>
                    <select name="limit" class="form-control">
                        <?php foreach ([200,500,1000,2000,5000] as $l): ?>
                        <option value="<?= $l ?>" <?= $limit==$l ? 'selected':'' ?>><?= number_format($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Click Type</label>
                    <select name="click_filter" class="form-control">
                        <option value="all"       <?= ($clickFilter ?? 'all') === 'all'       ? 'selected' : '' ?>>All Clicks</option>
                        <option value="converted" <?= ($clickFilter ?? 'all') === 'converted' ? 'selected' : '' ?>>Converted Clicks</option>
                        <option value="approved"  <?= ($clickFilter ?? 'all') === 'approved'  ? 'selected' : '' ?>>Approved Conversions</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="cr-btn-apply">Apply Filters</button>
                </div>
                <div class="form-group">
                    <a href="/affiliate_manager/click_report" class="cr-btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary cards -->
<div class="cr-stats-grid">
    <div class="cr-stat-card stat-clicks">
        <div class="cr-stat-label">Total Clicks</div>
        <div class="cr-stat-value"><?= number_format($totalClicks) ?></div>
    </div>
    <div class="cr-stat-card stat-fraud">
        <div class="cr-stat-label">Fraud Clicks</div>
        <div class="cr-stat-value" style="color:#DC2626"><?= number_format($fraudCount) ?></div>
        <div class="cr-stat-sub" style="color:#DC2626"><?= $totalClicks > 0 ? round($fraudCount/$totalClicks*100,1) : 0 ?>% fraud rate</div>
    </div>
    <div class="cr-stat-card stat-payout">
        <div class="cr-stat-label">Total Payout</div>
        <div class="cr-stat-value" style="color:#059669">$<?= number_format($totalPayout, 2) ?></div>
        <div class="cr-stat-sub">converted only</div>
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
        stateSave: true,
        pageLength: 50,
        order: [[12, 'desc']],
        scrollX: true,
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
