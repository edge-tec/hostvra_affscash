<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128220;</div>
        <div>
            <div class="fds-page-title">Fraud Cases</div>
            <div class="fds-page-sub">Case management &mdash; open, investigate, resolve</div>
        </div>
    </div>
    <button onclick="document.getElementById('fds-new-case-modal').style.display='flex'" class="fds-btn fds-btn-primary">+ New Case</button>
</div>

<?php if ($message): ?><div class="alert alert-success mb-3"><?= Helpers::e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger mb-3"><?= Helpers::e($error) ?></div><?php endif; ?>

<!-- Summary badges -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Open Cases</div><div class="fds-kpi-val"><?= $counts['open'] ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">Investigating</div><div class="fds-kpi-val"><?= $counts['investigating'] ?></div></div>
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Critical Active</div><div class="fds-kpi-val"><?= $counts['critical'] ?></div></div>
</div>

<?php if ($viewCase): ?>
<!-- Single case detail -->
<div class="fds-card mb-3" style="border:2px solid rgba(139,92,246,.4)">
    <div class="fds-card-header" style="background:rgba(139,92,246,.08)">
        <span class="fds-card-title">Case: <?= Helpers::e($viewCase['case_ref']) ?></span>
        <a href="/admin/fraud-center/cases" class="fds-btn fds-btn-sm fds-btn-outline">Back to List</a>
    </div>
    <div class="fds-card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px">
            <div><div class="fds-section-label">Type</div><div><?= str_replace('_',' ', ucfirst($viewCase['type'])) ?></div></div>
            <div><div class="fds-section-label">Severity</div><?= fraud_severity_badge($viewCase['severity']) ?></div>
            <div><div class="fds-section-label">Status</div><?= fraud_status_badge($viewCase['status']) ?></div>
            <div><div class="fds-section-label">Affiliate</div><div><?= Helpers::e($viewCase['affiliate_code'] ?? '—') ?></div></div>
            <div><div class="fds-section-label">Reference ID</div><div><?= Helpers::e($viewCase['reference_id'] ?? '—') ?></div></div>
            <div><div class="fds-section-label">Created</div><div class="fds-text-sm"><?= date('M j, Y H:i', strtotime($viewCase['created_at'])) ?></div></div>
        </div>
        <?php if ($viewCase['notes']): ?>
        <div class="fds-section-label">Notes</div>
        <div style="background:rgba(0,0,0,.04);border-radius:6px;padding:12px;margin-bottom:16px;white-space:pre-wrap;font-size:13px"><?= Helpers::e($viewCase['notes']) ?></div>
        <?php endif; ?>
        <!-- Update status -->
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach (['open','investigating','resolved','dismissed'] as $s): ?>
            <?php if ($s !== $viewCase['status']): ?>
            <form method="post" style="display:inline">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="case_id" value="<?= $viewCase['id'] ?>">
                <input type="hidden" name="status" value="<?= $s ?>">
                <button type="submit" class="fds-btn fds-btn-sm fds-btn-outline">Mark <?= ucfirst($s) ?></button>
            </form>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <!-- Add note -->
        <form method="post" style="margin-top:16px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="case_id" value="<?= $viewCase['id'] ?>">
            <label class="fds-section-label">Update Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Investigation notes..."><?= Helpers::e($viewCase['notes'] ?? '') ?></textarea>
            <button type="submit" class="fds-btn fds-btn-primary" style="margin-top:8px">Save Notes</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/cases';
$affId          = $filterAffId ?? 0;
$offerId        = 0;
// Build affiliateList from existing $affiliates variable (FraudCasesController passes $affiliates)
$affiliateList  = array_map(fn($a) => [
    'id'             => $a['id'],
    'affiliate_code' => $a['affiliate_code'],
    'name'           => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')),
], $affiliates ?? []);
$offerList      = []; // cases have no offer_id; suppress offer dropdown
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<div class="fds-card mb-3">
    <div class="fds-card-body" style="padding:12px 16px">
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <?php if($filterAffId > 0): ?><input type="hidden" name="affiliate_id" value="<?= $filterAffId ?>"><?php endif; ?>
            <input type="text" name="q" class="form-control" placeholder="Search case ref, reference..." value="<?= Helpers::e($search) ?>" style="max-width:240px">
            <select name="status" class="form-control" style="max-width:160px">
                <option value="">All Statuses</option>
                <?php foreach (['open','investigating','resolved','dismissed'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="severity" class="form-control" style="max-width:160px">
                <option value="">All Severities</option>
                <?php foreach (['critical','high','medium','low'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterSeverity === $s ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="fds-btn fds-btn-primary">Filter</button>
            <a href="/admin/fraud-center/cases" class="fds-btn fds-btn-outline">Clear</a>
        </form>
    </div>
</div>

<!-- Cases table -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Cases</span><span class="fds-text-muted fds-text-sm"><?= number_format($total) ?> total</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Ref</th><th>Type</th><th>Affiliate</th><th>Severity</th><th>Status</th><th>Score</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($cases as $case): ?>
            <tr>
                <td><a href="?view=<?= $case['id'] ?>" class="fds-link"><strong><?= Helpers::e($case['case_ref']) ?></strong></a></td>
                <td class="fds-text-sm"><?= str_replace('_',' ', ucfirst($case['type'])) ?></td>
                <td class="fds-text-sm"><?= Helpers::e($case['affiliate_code'] ?? '—') ?></td>
                <td><?= fraud_severity_badge($case['severity']) ?></td>
                <td><?= fraud_status_badge($case['status']) ?></td>
                <td><?= fraud_score_badge($case['fraud_score']) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= date('M j, H:i', strtotime($case['created_at'])) ?></td>
                <td>
                    <a href="?view=<?= $case['id'] ?>" class="fds-btn fds-btn-sm fds-btn-outline">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cases)): ?><tr><td colspan="8" class="fds-empty">No cases found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)"><?= fds_pagination($pag, '/admin/fraud-center/cases', ['q'=>$search,'status'=>$filterStatus,'severity'=>$filterSeverity]) ?></div>
    <?php endif; ?>
</div>

<!-- New Case Modal -->
<div id="fds-new-case-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--surface,#fff);border-radius:12px;padding:24px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <strong style="font-size:16px">New Fraud Case</strong>
            <button onclick="document.getElementById('fds-new-case-modal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748B">&times;</button>
        </div>
        <form method="post">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="create_case">
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control">
                    <?php foreach (['click_spam','bot_traffic','fake_conversion','device_abuse','ip_abuse','geo_fraud','duplicate_conv'] as $t): ?>
                    <option value="<?= $t ?>"><?= str_replace('_',' ', ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">— None —</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['affiliate_code'].' — '.$a['first_name'].' '.$a['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Reference ID (click_id / conversion_id)</label>
                <input type="text" name="reference_id" class="form-control" placeholder="Optional">
            </div>
            <div class="form-group">
                <label>Severity</label>
                <select name="severity" class="form-control">
                    <?php foreach (['low','medium','high','critical'] as $s): ?>
                    <option value="<?= $s ?>" <?= $s==='medium'?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Investigation notes..."></textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px">
                <button type="submit" class="fds-btn fds-btn-primary">Create Case</button>
                <button type="button" onclick="document.getElementById('fds-new-case-modal').style.display='none'" class="fds-btn fds-btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
