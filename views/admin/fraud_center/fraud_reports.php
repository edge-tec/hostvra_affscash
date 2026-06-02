<?php 
$layoutStr = Auth::role() === 'affiliate_manager' ? 'affiliate_manager' : 'admin';
require BASE_PATH . "/views/layouts/{$layoutStr}.php"; 
?>

<style>
.fr-filter-bar { background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end; }
.fr-filter-bar label { font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px; }
.fr-filter-bar select, .fr-filter-bar input[type=text], .fr-filter-bar input[type=date] { font-size:13px;padding:7px 10px;border:1px solid #D1D5DB;border-radius:7px;background:#fff;color:#111827;outline:none; }
.fr-filter-bar select:focus, .fr-filter-bar input:focus { border-color:#6366F1; }
.fr-summary-bar { display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:16px; }
.fr-sum-box { background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:12px 16px;text-align:center; }
.fr-sum-box .val { font-size:22px;font-weight:800;color:#111827;line-height:1.1; }
.fr-sum-box .lbl { font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.04em;margin-top:3px; }
.fr-sum-box.danger .val { color:#DC2626; }
.fr-sum-box.warn .val   { color:#D97706; }
.fr-sum-box.ok .val     { color:#059669; }
.fr-bulk-bar { display:none;align-items:center;gap:8px;padding:10px 16px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;margin-bottom:12px;font-size:13px;font-weight:600;color:#3730A3; }
.fr-bulk-bar.visible { display:flex; }
.fraud-row { background:#FFF7F7; }
.fraud-row td:first-child { border-left:3px solid #EF4444; }
</style>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128196;</div>
        <div>
            <div class="fds-page-title">Fraud Reports</div>
            <div class="fds-page-sub">Affiliate &amp; Offer based conversion/click report &mdash; review, block and approve</div>
        </div>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success mb-3"><?= Helpers::e($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-danger mb-3"><?= Helpers::e($error) ?></div><?php endif; ?>

<!-- Tabs -->
<div class="fds-tabs mb-0" style="margin-bottom:0">
    <a href="?tab=conversions&aff_id=<?= $affId ?>&offer_id=<?= $offerId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&status=<?= urlencode($statusFilter) ?>&fraud_only=<?= $fraudOnly ?>"
       class="fds-tab <?= $tab==='conversions'?'active':'' ?>">&#9989; Conversions</a>
    <a href="?tab=clicks&aff_id=<?= $affId ?>&offer_id=<?= $offerId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"
       class="fds-tab <?= $tab==='clicks'?'active':'' ?>">&#128432; Clicks</a>
</div>

<!-- ── FILTER BAR ─────────────────────────────────────────────────────────── -->
<form method="get" id="frFilterForm">
    <input type="hidden" name="tab" value="<?= Helpers::e($tab) ?>">
    <div style="margin-bottom:8px">
        <?php $drpFromId='fr-from'; $drpToId='fr-to'; $drpFormId='frFilterForm'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
    </div>
    <div class="fr-filter-bar">
        <div>
            <label>Affiliate</label>
            <select name="aff_id" style="min-width:180px" onchange="this.form.submit()">
                <option value="0">All Affiliates</option>
                <?php foreach ($affiliateList as $aff): ?>
                <option value="<?= $aff['id'] ?>" <?= $affId==$aff['id']?'selected':'' ?>>
                    <?= Helpers::e($aff['affiliate_code'].' — '.($aff['first_name']??'').(' '.($aff['last_name']??''))) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Offer</label>
            <select name="offer_id" style="min-width:160px" onchange="this.form.submit()">
                <option value="0">All Offers</option>
                <?php foreach ($offerList as $off): ?>
                <option value="<?= $off['id'] ?>" <?= $offerId==$off['id']?'selected':'' ?>><?= Helpers::e($off['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Date From</label>
            <input type="date" id="fr-from" name="date_from" value="<?= Helpers::e($dateFrom) ?>">
        </div>
        <div>
            <label>Date To</label>
            <input type="date" id="fr-to" name="date_to" value="<?= Helpers::e($dateTo) ?>">
        </div>
        <?php if ($tab === 'conversions'): ?>
        <div>
            <label>Status</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="pending"  <?= $statusFilter==='pending'  ?'selected':'' ?>>Pending</option>
                <option value="approved" <?= $statusFilter==='approved' ?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= $statusFilter==='rejected' ?'selected':'' ?>>Rejected / Blocked</option>
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:6px;padding-bottom:2px">
            <input type="checkbox" name="fraud_only" value="1" id="fraudOnly" <?= $fraudOnly?'checked':'' ?> onchange="this.form.submit()" style="width:16px;height:16px;accent-color:#6366F1">
            <label for="fraudOnly" style="font-size:13px;font-weight:600;color:#4F46E5;cursor:pointer;text-transform:none;letter-spacing:0">Fraud flagged only</label>
        </div>
        <?php endif; ?>
        <div>
            <label>Search</label>
            <input type="text" name="q" value="<?= Helpers::e($search) ?>" placeholder="Conv ID, IP, affiliate..." style="min-width:200px">
        </div>
        <div style="padding-bottom:1px;display:flex;gap:6px">
            <button type="submit" class="fds-btn fds-btn-primary">Filter</button>
            <a href="?tab=<?= $tab ?>" class="fds-btn fds-btn-outline">Clear</a>
        </div>
        <div style="padding-bottom:1px;margin-left:auto">
            <?php require BASE_PATH . '/views/partials/export_buttons.php'; ?>
        </div>
    </div>
</form>

<?php if ($tab === 'conversions'): ?>
<!-- ════════════════════════════════════════════════════════════════════════════
     CONVERSION REPORT
     ════════════════════════════════════════════════════════════════════════════ -->

<!-- Summary bar -->
<?php if (!empty($convSummary)): ?>
<div class="fr-summary-bar">
    <div class="fr-sum-box"><div class="val"><?= number_format($convSummary['total']??0) ?></div><div class="lbl">Total</div></div>
    <div class="fr-sum-box ok"><div class="val"><?= number_format($convSummary['approved']??0) ?></div><div class="lbl">Approved</div></div>
    <div class="fr-sum-box warn"><div class="val"><?= number_format($convSummary['pending']??0) ?></div><div class="lbl">Pending</div></div>
    <div class="fr-sum-box danger"><div class="val"><?= number_format($convSummary['rejected']??0) ?></div><div class="lbl">Blocked</div></div>
    <div class="fr-sum-box danger"><div class="val"><?= number_format($convSummary['flagged']??0) ?></div><div class="lbl">Fraud Flagged</div></div>
    <div class="fr-sum-box"><div class="val">$<?= number_format($convSummary['total_payout']??0,2) ?></div><div class="lbl">Payout</div></div>
    <div class="fr-sum-box"><div class="val">$<?= number_format($convSummary['total_revenue']??0,2) ?></div><div class="lbl">Revenue</div></div>
</div>
<?php endif; ?>

<!-- Bulk action bar -->
<div class="fr-bulk-bar" id="frBulkBar">
    <span id="frBulkCount">0</span> conversions selected &mdash;
    <form method="post" id="frBulkForm" style="display:inline;display:flex;gap:6px;align-items:center">
        <?= Helpers::csrf() ?>
        <div id="frBulkInputs"></div>
        <input type="hidden" name="rejection_reason" id="frBulkReason" value="">
        <button type="submit" name="action" value="approve" class="fds-btn fds-btn-sm" style="background:#10B981;color:#fff">&#10003; Approve Selected</button>
        <button type="button" onclick="frBulkBlockClick()" class="fds-btn fds-btn-sm fds-btn-danger">&#128683; Block Selected</button>
    </form>
    <button onclick="clearSelection()" class="fds-btn fds-btn-sm fds-btn-outline">Clear</button>
</div>

<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Conversions</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($convPag['total']) ?> total &mdash; page <?= $convPag['page'] ?> of <?= $convPag['pages'] ?></span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table" id="frConvTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="frSelectAll" style="accent-color:#6366F1" title="Select all"></th>
                    <th>Conv ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>IP</th>
                    <th>Payout</th>
                    <th>Goal</th>
                    <th>Speed</th>
                    <th>Fraud</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($conversions as $cv):
                $isFraud = ($cv['is_fraud'] ?? 0) || ($cv['fraud_score'] ?? 0) >= 50;
                $speedSecs = (int)($cv['click_to_conv_secs'] ?? -1);
            ?>
            <tr class="<?= $isFraud ? 'fraud-row' : '' ?>">
                <td><input type="checkbox" class="fr-conv-cb" value="<?= Helpers::e($cv['conversion_id']) ?>" style="accent-color:#6366F1"></td>
                <td class="fds-text-sm" style="font-family:monospace"><?= Helpers::e(substr($cv['conversion_id'],0,12)) ?>…</td>
                <td>
                    <strong><?= Helpers::e($cv['affiliate_code'] ?? '—') ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e(($cv['first_name']??'').' '.($cv['last_name']??'')) ?></span>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cv['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($cv['ip_address']??'—') ?></a>
                </td>
                <td><strong>$<?= number_format($cv['payout'],2) ?></strong></td>
                <td class="fds-text-sm fds-text-muted"><?= Helpers::e($cv['goal_name']??'—') ?></td>
                <td>
                    <?php if ($speedSecs >= 0): ?>
                        <?php $sc = $speedSecs < 10 ? 'critical' : ($speedSecs < 30 ? 'high' : ($speedSecs < 120 ? 'medium' : 'low')); ?>
                        <?= fraud_severity_badge($sc) ?> <span class="fds-text-sm"><?= $speedSecs ?>s</span>
                    <?php else: ?>
                        <span class="fds-text-muted fds-text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td><?= fraud_score_badge($cv['fraud_score'] ?? 0) ?><?= $isFraud ? ' <span class="fds-badge fds-badge-critical" style="font-size:10px">&#9888;</span>' : '' ?></td>
                <td>
                    <span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']]??'muted' ?>">
                        <?= $cv['status'] ?>
                    </span>
                </td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cv['converted_at'])) ?></td>
                <td style="white-space:nowrap">
                    <?php if ($cv['status'] !== 'approved'): ?>
                    <form method="post" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($cv['conversion_id']) ?>">
                        <button type="submit" class="fds-btn fds-btn-sm" style="background:#10B981;color:#fff;padding:4px 10px" title="Approve">&#10003;</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($cv['status'] !== 'rejected'): ?>
                    <button type="button" class="fds-btn fds-btn-sm fds-btn-danger" style="padding:4px 10px" title="Block / Reject"
                            onclick="openRejectModal('<?= Helpers::e($cv['conversion_id']) ?>')">&#128683;</button>
                    <?php endif; ?>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cv['ip_address']??'') ?>" class="fds-btn fds-btn-sm fds-btn-outline" style="padding:4px 8px" title="IP Info">&#127760;</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($conversions)): ?>
            <tr><td colspan="12" class="fds-empty">No conversions match the current filters</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($convPag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)">
        <?= fds_pagination($convPag, '/admin/fraud-center/fraud-reports', [
            'tab'        => 'conversions',
            'aff_id'     => $affId,
            'offer_id'   => $offerId,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'status'     => $statusFilter,
            'fraud_only' => $fraudOnly,
            'q'          => $search,
        ]) ?>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════════════════════
     CLICK REPORT
     ════════════════════════════════════════════════════════════════════════════ -->

<!-- Summary bar -->
<?php if (!empty($clickSummary)): ?>
<div class="fr-summary-bar">
    <div class="fr-sum-box"><div class="val"><?= number_format($clickSummary['total']??0) ?></div><div class="lbl">Total Clicks</div></div>
    <div class="fr-sum-box danger"><div class="val"><?= number_format($clickSummary['flagged']??0) ?></div><div class="lbl">Fraud Flagged</div></div>
    <div class="fr-sum-box"><div class="val"><?= number_format($clickSummary['unique_ips']??0) ?></div><div class="lbl">Unique IPs</div></div>
    <div class="fr-sum-box"><div class="val"><?= number_format($clickSummary['unique_affs']??0) ?></div><div class="lbl">Affiliates</div></div>
</div>
<?php endif; ?>

<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Click Log</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($clickPag['total']) ?> total &mdash; page <?= $clickPag['page'] ?> of <?= $clickPag['pages'] ?></span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>IP</th>
                    <th>Country</th>
                    <th>Device</th>
                    <th>Fraud Score</th>
                    <th>Converted</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clicks as $cl):
                $isFraud = ($cl['is_fraud'] ?? 0) || ($cl['fraud_score'] ?? 0) >= 50;
            ?>
            <tr class="<?= $isFraud ? 'fraud-row' : '' ?>">
                <td class="fds-text-sm fds-text-muted"><?= $cl['id'] ?></td>
                <td>
                    <strong><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e(($cl['first_name']??'').' '.($cl['last_name']??'')) ?></span>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($cl['ip_address']??'—') ?></a>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cl['country']??'—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['device_type']??'—') ?></td>
                <td><?= fraud_score_badge($cl['fraud_score'] ?? 0) ?></td>
                <td>
                    <?php if ($cl['has_conv'] > 0): ?>
                    <span class="fds-badge fds-badge-low">&#10003; Yes</span>
                    <?php else: ?>
                    <span class="fds-text-muted fds-text-sm">No</span>
                    <?php endif; ?>
                </td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cl['clicked_at'])) ?></td>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address']??'') ?>" class="fds-btn fds-btn-sm fds-btn-outline">IP Info</a>
                    <a href="/admin/fraud-center/fraud-reports?tab=conversions&q=<?= urlencode($cl['click_id']??'') ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="fds-btn fds-btn-sm fds-btn-outline">Conv</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($clicks)): ?>
            <tr><td colspan="10" class="fds-empty">No clicks match the current filters</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($clickPag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)">
        <?= fds_pagination($clickPag, '/admin/fraud-center/fraud-reports', [
            'tab'       => 'clicks',
            'aff_id'    => $affId,
            'offer_id'  => $offerId,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'q'         => $search,
        ]) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// ── Bulk selection ───────────────────────────────────────────────────────────
var selectedIds = [];

function updateBulkBar() {
    var bar = document.getElementById('frBulkBar');
    var cnt = document.getElementById('frBulkCount');
    if (!bar) return;
    selectedIds = Array.from(document.querySelectorAll('.fr-conv-cb:checked')).map(function(el){return el.value;});
    if (selectedIds.length > 0) {
        bar.classList.add('visible');
        cnt.textContent = selectedIds.length;
        var inp = document.getElementById('frBulkInputs');
        inp.innerHTML = selectedIds.map(function(id){
            return '<input type="hidden" name="bulk_ids[]" value="'+id.replace(/"/g,'&quot;')+'">';
        }).join('');
    } else {
        bar.classList.remove('visible');
    }
}

function clearSelection() {
    document.querySelectorAll('.fr-conv-cb').forEach(function(cb){ cb.checked=false; });
    var sa = document.getElementById('frSelectAll');
    if (sa) sa.checked = false;
    updateBulkBar();
}

document.addEventListener('DOMContentLoaded', function(){
    var sa = document.getElementById('frSelectAll');
    if (sa) {
        sa.addEventListener('change', function(){
            document.querySelectorAll('.fr-conv-cb').forEach(function(cb){ cb.checked = sa.checked; });
            updateBulkBar();
        });
    }
    document.querySelectorAll('.fr-conv-cb').forEach(function(cb){
        cb.addEventListener('change', updateBulkBar);
    });
});

// Bulk-block flow: prompt for a reason, then submit the bulk form with action=block.
function frBulkBlockClick(){
    if (typeof selectedIds === 'undefined' || !selectedIds.length) {
        alert('Select at least one conversion first.');
        return;
    }
    // Reuse the shared reject modal — but we steer it to submit the BULK form
    // instead of its own form. Open it and override the submit handler.
    if (!document.getElementById('rejectReasonModal')) {
        alert('Reject modal not loaded on this page.');
        return;
    }
    openRejectModal('BULK');
    // Replace submit handler for this one bulk operation
    var modalForm = document.querySelector('#rejectReasonModal form');
    if (!modalForm) return;
    modalForm.onsubmit = function(e){
        if (!rejectModalSubmit(e)) return false;
        e.preventDefault();
        var reason = document.getElementById('rrm-reason-final').value;
        document.getElementById('frBulkReason').value = reason;
        closeRejectModal();
        // Submit bulk form with action=block by injecting a hidden action input
        var bulk = document.getElementById('frBulkForm');
        var existing = bulk.querySelector('input[name="action"]');
        if (existing) existing.remove();
        var actInput = document.createElement('input');
        actInput.type = 'hidden'; actInput.name = 'action'; actInput.value = 'block';
        bulk.appendChild(actInput);
        bulk.submit();
        // Restore default modal handler so per-row rejects still work later
        modalForm.onsubmit = null;
        return false;
    };
}
</script>

<?php
// Reject-with-reason modal — used by both per-row rejects and the bulk Block flow.
$rejectFormAction  = '/admin/fraud-center/fraud-reports';
$rejectStatusField = 'action';
$rejectStatusValue = 'block';
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php 
$footerStr = Auth::role() === 'affiliate_manager' ? 'affiliate_manager_footer' : 'admin_footer';
require BASE_PATH . "/views/layouts/{$footerStr}.php"; 
?>
