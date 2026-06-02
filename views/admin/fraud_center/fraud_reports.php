<?php 
$layoutStr = Auth::role() === 'affiliate_manager' ? 'affiliate_manager' : 'admin';
$isManager = $layoutStr === 'affiliate_manager';
$canAction = !$isManager || ManagerPermissions::can(ManagerPermissions::currentManagerId(), 'reject_fraud_conv');
require BASE_PATH . "/views/layouts/{$layoutStr}.php"; 
?>

<style>
.fr-header-card { background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); margin-bottom: 24px; overflow: hidden; }
.fr-header-tabs { display: flex; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 0 16px; }
.fr-header-tab { padding: 14px 20px; font-size: 14px; font-weight: 600; color: #64748B; text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
.fr-header-tab:hover { color: #334155; border-bottom-color: #CBD5E1; }
.fr-header-tab.active { color: #4F46E5; border-bottom-color: #4F46E5; }
.fr-filter-section { padding: 20px; }
.fr-filter-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.fr-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px 20px; align-items: start; }
.fr-filter-grid > div { display: flex; flex-direction: column; gap: 0; }
.fr-filter-grid label { display: block; margin-bottom: 8px; font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; }
.fr-filter-grid select, .fr-filter-grid input[type=text], .fr-filter-grid input[type=date] { font-size: 13px; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; background: #F8FAFC; color: #1E293B; outline: none; transition: all 0.2s; width: 100%; box-sizing: border-box; }
.fr-filter-grid select:hover, .fr-filter-grid input:hover { border-color: #94A3B8; }
.fr-filter-grid select:focus, .fr-filter-grid input:focus { background: #fff; border-color: #6366F1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
.fr-filter-actions { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; padding-top: 16px; border-top: 1px dashed #E2E8F0; }
.fr-filter-actions-right { display: flex; gap: 12px; }
.fr-filter-actions-left { display: flex; align-items: center; gap: 12px; }
.fr-export-wrapper { margin-left: auto; }
.risk-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: inline-block; white-space: nowrap; }
.risk-badge.high { background-color: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
.risk-badge.medium { background-color: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
.risk-badge.low { background-color: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
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

<!-- ── HEADER CARD (Tabs + Filters) ─────────────────────────────────────────────────────────── -->
<div class="fr-header-card">
    <div class="fr-header-tabs">
        <a href="?tab=conversions&aff_id=<?= $affId ?>&offer_id=<?= $offerId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&status=<?= urlencode($statusFilter) ?>&fraud_only=<?= $fraudOnly ?>"
           class="fr-header-tab <?= $tab==='conversions'?'active':'' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Conversions
        </a>
        <a href="?tab=clicks&aff_id=<?= $affId ?>&offer_id=<?= $offerId ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>"
           class="fr-header-tab <?= $tab==='clicks'?'active':'' ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Clicks
        </a>
    </div>

    <div class="fr-filter-section">
        <form method="get" id="frFilterForm">
            <input type="hidden" name="tab" value="<?= Helpers::e($tab) ?>">
            
            <div class="fr-filter-top">
                <?php $drpFromId='fr-from'; $drpToId='fr-to'; $drpFormId='frFilterForm'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
                <div class="fr-export-wrapper">
                    <?php require BASE_PATH . '/views/partials/export_buttons.php'; ?>
                </div>
            </div>

            <div class="fr-filter-grid">
                <div>
                    <label>Affiliate</label>
                    <select name="aff_id" onchange="this.form.submit()">
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
                    <select name="offer_id" onchange="this.form.submit()">
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
                        <option value="">All Statuses</option>
                        <option value="pending"  <?= $statusFilter==='pending'  ?'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $statusFilter==='approved' ?'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $statusFilter==='rejected' ?'selected':'' ?>>Rejected / Blocked</option>
                    </select>
                </div>
                <?php endif; ?>
                <div>
                    <label>Risk Level</label>
                    <select name="risk_level" onchange="this.form.submit()">
                        <option value="">All Risk Levels</option>
                        <option value="low" <?= (isset($riskLevel) && $riskLevel==='low')?'selected':'' ?>>Low Risk (&lt;20)</option>
                        <option value="medium" <?= (isset($riskLevel) && $riskLevel==='medium')?'selected':'' ?>>Medium Risk (20-49)</option>
                        <option value="high" <?= (isset($riskLevel) && $riskLevel==='high')?'selected':'' ?>>High Risk (50+)</option>
                    </select>
                </div>
                <div>
                    <label>Click ID</label>
                    <input type="text" name="click_id" value="<?= Helpers::e($clickIdFlt ?? '') ?>" placeholder="Exact Click ID...">
                </div>
                <div>
                    <label>User Agent</label>
                    <input type="text" name="ua" value="<?= Helpers::e($uaFlt ?? '') ?>" placeholder="Search User Agent...">
                </div>
                <div>
                    <label>Aff Sub</label>
                    <input type="text" name="sub" value="<?= Helpers::e($subFlt ?? '') ?>" placeholder="Sub 1-5...">
                </div>
                <div style="grid-column: 1 / -1; max-width: 420px;">
                    <label>Search</label>
                    <input type="text" name="q" value="<?= Helpers::e($search) ?>" placeholder="Search by Conv ID, IP, or affiliate code...">
                </div>
            </div>

            <div class="fr-filter-actions">
                <div class="fr-filter-actions-left">
                    <?php if ($tab === 'conversions'): ?>
                    <label for="fraudOnly" style="display:flex;align-items:center;gap:8px;cursor:pointer;background:#EEF2FF;padding:8px 14px;border-radius:8px;border:1px solid #C7D2FE;transition:all 0.2s">
                        <input type="checkbox" name="fraud_only" value="1" id="fraudOnly" <?= $fraudOnly?'checked':'' ?> onchange="this.form.submit()" style="width:16px;height:16px;accent-color:#4F46E5;margin:0">
                        <span style="font-size:13px;font-weight:700;color:#4F46E5;line-height:1">Show Fraud Flagged Only</span>
                    </label>
                    <?php endif; ?>
                </div>
                <div class="fr-filter-actions-right">
                    <a href="?tab=<?= $tab ?>" class="fds-btn fds-btn-outline" style="padding:10px 20px;font-weight:600">Clear Filters</a>
                    <button type="submit" class="fds-btn fds-btn-primary" style="padding:10px 24px;font-weight:600;background:#4F46E5;border:none">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
    <?php if (Auth::role() === "admin"): ?>
    <div class="fr-sum-box"><div class="val">$<?= number_format($convSummary['total_revenue']??0,2) ?></div><div class="lbl">Revenue</div></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Bulk action bar -->
<?php if ($canAction): ?>
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
<?php endif; ?>

<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Conversions</span>
        <span class="fds-text-muted fds-text-sm"><?= number_format($convPag['total']) ?> total &mdash; page <?= $convPag['page'] ?> of <?= $convPag['pages'] ?></span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table" id="frConvTable">
            <thead>
                <tr>
                    <?php if ($canAction): ?><th><input type="checkbox" id="frSelectAll" style="accent-color:#6366F1" title="Select all"></th><?php endif; ?>
                    <th>Conv ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>IP</th>
                    <th>Click ID & Sub</th>
                    <th>Device Type</th>
                    <th>Brand & Model</th>
                    <th>OS Version</th>
                    <th>User Agent</th>
                    <th>Source</th>
                    <th>Payout</th>
                    <th>Goal</th>
                    <th>Speed</th>
                    <th>Risk Level</th>
                    <th>Status</th>
                    <th>Date</th>
                    <?php if ($canAction): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($conversions as $cv):
                $isFraud = ($cv['is_fraud'] ?? 0) || ($cv['fraud_score'] ?? 0) >= 50;
                $speedSecs = (int)($cv['click_to_conv_secs'] ?? -1);
            ?>
            <tr class="<?= $isFraud ? 'fraud-row' : '' ?>">
                <?php if ($canAction): ?><td><input type="checkbox" class="fr-conv-cb" value="<?= Helpers::e($cv['conversion_id']) ?>" style="accent-color:#6366F1"></td><?php endif; ?>
                <td class="fds-text-sm" style="font-family:monospace; word-break: break-all;"><?= Helpers::e($cv['conversion_id']) ?></td>
                <td>
                    <strong><?= Helpers::e($cv['affiliate_code'] ?? '—') ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e(($cv['first_name']??'').' '.($cv['last_name']??'')) ?></span>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cv['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($cv['ip_address']??'—') ?></a>
                </td>
                <td class="fds-text-sm">
                    <strong style="word-break: break-all;"><?= Helpers::e(!empty($cv['click_id']) ? $cv['click_id'] : '(Unknown)') ?></strong>
                    <?php 
                        $subs = array_filter([$cv['sub2']??'', $cv['sub3']??'', $cv['sub4']??'', $cv['sub5']??'', $cv['sub6']??'']);
                        if (!empty($subs)) echo '<br><span class="fds-text-muted" style="font-size:11px">'.Helpers::e(implode(' / ', $subs)).'</span>';
                    ?>
                </td>
                <td class="fds-text-sm">
                    <strong><?= Helpers::e(!empty($cv['device_type']) ? ucfirst($cv['device_type']) : 'Unknown') ?></strong>
                </td>
                <td class="fds-text-sm">
                    <?= Helpers::e(trim(($cv['device_brand'] ?? '') . ' ' . ($cv['device_model'] ?? '')) ?: '—') ?>
                </td>
                <td class="fds-text-sm">
                    <?= Helpers::e($cv['os_version'] ?: '—') ?>
                </td>
                <td class="fds-text-sm">
                    <div style="word-break: break-word; min-width: 150px;">
                        <?= Helpers::e(!empty($cv['user_agent']) ? $cv['user_agent'] : '(Unknown)') ?>
                    </div>
                </td>
                <td class="fds-text-sm"><?= Helpers::e(!empty($cv['source']) ? $cv['source'] : '—') ?></td>
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
                <td>
                    <?php 
                        $score = $cv['fraud_score'] ?? 0;
                        $riskLabel = $score >= 50 ? 'High' : ($score >= 20 ? 'Medium' : 'Low');
                        $badgeColor = $score >= 50 ? 'high' : ($score >= 20 ? 'medium' : 'low');
                    ?>
                    <span class="risk-badge <?= $badgeColor ?>"><?= $riskLabel ?> (<?= $score ?>)</span>
                    <?= $isFraud ? ' <span class="fds-badge fds-badge-critical" style="font-size:10px">&#9888;</span>' : '' ?>
                </td>
                <td>
                    <span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']]??'muted' ?>">
                        <?= $cv['status'] ?>
                    </span>
                </td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cv['converted_at'])) ?></td>
                <?php if ($canAction): ?>
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
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($conversions)): ?>
            <tr><td colspan="<?= $canAction ? 18 : 16 ?>" class="fds-empty">No conversions match the current filters</td></tr>
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
                    <th>Click ID & Sub</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Source</th>
                    <th>IP</th>
                    <th>User Agent</th>
                    <th>Country</th>
                    <th>Device</th>
                    <th>Risk Level</th>
                    <th>Converted</th>
                    <th>Date</th>
                    <?php if ($canAction): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clicks as $cl):
                $isFraud = ($cl['is_fraud'] ?? 0) || ($cl['fraud_score'] ?? 0) >= 50;
            ?>
            <tr class="<?= $isFraud ? 'fraud-row' : '' ?>">
                <td class="fds-text-sm">
                    <strong style="word-break: break-all;"><?= Helpers::e(!empty($cl['click_id']) ? $cl['click_id'] : '(Unknown)') ?></strong>
                    <?php 
                        $subs = array_filter([$cl['sub2']??'', $cl['sub3']??'', $cl['sub4']??'', $cl['sub5']??'', $cl['sub6']??'']);
                        if (!empty($subs)) echo '<br><span class="fds-text-muted" style="font-size:11px">'.Helpers::e(implode(' / ', $subs)).'</span>';
                    ?>
                </td>
                <td>
                    <strong><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e(($cl['first_name']??'').' '.($cl['last_name']??'')) ?></span>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e(!empty($cl['source']) ? $cl['source'] : '—') ?></td>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address']??'') ?>" class="fds-link"><?= Helpers::e($cl['ip_address']??'—') ?></a>
                </td>
                <td class="fds-text-sm">
                    <div style="word-break: break-word; min-width: 150px;">
                        <?= Helpers::e(!empty($cl['user_agent']) ? $cl['user_agent'] : '(Unknown)') ?>
                    </div>
                </td>
                <td class="fds-text-sm"><?= Helpers::e($cl['country']??'—') ?></td>
                <td class="fds-text-sm"><?= Helpers::e($cl['device_type']??'—') ?></td>
                <td>
                    <?php 
                        $score = $cl['fraud_score'] ?? 0;
                        $riskLabel = $score >= 50 ? 'High' : ($score >= 20 ? 'Medium' : 'Low');
                        $badgeColor = $score >= 50 ? 'high' : ($score >= 20 ? 'medium' : 'low');
                    ?>
                    <span class="risk-badge <?= $badgeColor ?>"><?= $riskLabel ?> (<?= $score ?>)</span>
                </td>
                <td>
                    <?php if ($cl['has_conv'] > 0): ?>
                    <span class="fds-badge fds-badge-low">&#10003; Yes</span>
                    <?php else: ?>
                    <span class="fds-text-muted fds-text-sm">No</span>
                    <?php endif; ?>
                </td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($cl['clicked_at'])) ?></td>
                <?php if ($canAction): ?>
                <td>
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($cl['ip_address']??'') ?>" class="fds-btn fds-btn-sm fds-btn-outline">IP Info</a>
                    <a href="/admin/fraud-center/fraud-reports?tab=conversions&q=<?= urlencode($cl['click_id']??'') ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="fds-btn fds-btn-sm fds-btn-outline">Conv</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($clicks)): ?>
            <tr><td colspan="<?= $canAction ? 12 : 11 ?>" class="fds-empty">No clicks match the current filters</td></tr>
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
