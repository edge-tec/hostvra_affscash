<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#9881; Auto-Generate Invoices</h1>
        <p>Generate invoices for all active affiliates who have reached their payment threshold</p>
    </div>
    <a href="/admin/invoices" class="btn btn-secondary">← Back to Invoices</a>
</div>

<!-- Summary cards -->
<?php
$qualify  = array_filter($candidates, fn($c) => $c['qualifies'] && !$c['duplicate']);
$belowThr = array_filter($candidates, fn($c) => !$c['qualifies']);
$dupCount = array_filter($candidates, fn($c) => $c['qualifies'] && $c['duplicate']);
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-icon green">&#9989;</div>
        <div class="stat-label">Will Generate</div>
        <div class="stat-value"><?= count($qualify) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9888;</div>
        <div class="stat-label">Below Threshold</div>
        <div class="stat-value"><?= count($belowThr) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">&#128203;</div>
        <div class="stat-label">Already Invoiced</div>
        <div class="stat-value"><?= count($dupCount) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#128176;</div>
        <div class="stat-label">Total Payout</div>
        <?php $totalPayout = array_sum(array_column(array_values($qualify), 'balance')); ?>
        <div class="stat-value">$<?= number_format($totalPayout, 2) ?></div>
    </div>
</div>

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1E40AF">
    <strong>How it works:</strong> Invoices are generated based on each affiliate's <strong>payment terms</strong> (weekly / net-15 / net-30 / monthly). The period is automatically calculated. Affiliates below their threshold or with a duplicate period invoice are skipped.
</div>

<form method="POST" id="autoInvForm">
    <?= Helpers::csrf() ?>

    <div class="card mb-3">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Affiliate Preview</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="selAll(true)">Select All Eligible</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="selAll(false)">Deselect All</button>
            </div>
        </div>
        <div class="table-wrap">
            <table id="autoTbl">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="masterCb" onchange="document.querySelectorAll('.aff-row-cb:not(:disabled)').forEach(cb=>cb.checked=this.checked)"></th>
                        <th>Affiliate</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Threshold</th>
                        <th>Payment Terms</th>
                        <th>Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($candidates as $c): ?>
                <?php
                    $canGen = $c['qualifies'] && !$c['duplicate'];
                    $rowColor = !$c['qualifies'] ? '#FEF2F2' : ($c['duplicate'] ? '#FFFBEB' : '');
                ?>
                <tr style="background:<?= $rowColor ?>">
                    <td>
                        <input type="checkbox" name="affiliate_ids[]" value="<?= $c['id'] ?>"
                               class="aff-row-cb"
                               <?= $canGen ? 'checked' : 'disabled' ?>
                               <?= !$canGen ? 'style="opacity:.3"' : '' ?>>
                    </td>
                    <td><?= Helpers::e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                    <td style="font-size:12px;color:#64748B"><?= Helpers::e($c['email']) ?></td>
                    <td class="fw-bold">$<?= number_format($c['balance'], 2) ?></td>
                    <td style="color:#64748B">$<?= number_format($c['payment_threshold'], 2) ?></td>
                    <td><span class="badge badge-info"><?= Helpers::e($c['payment_terms'] ?? 'monthly') ?></span></td>
                    <td style="font-size:12px">
                        <?= $c['period_start'] ?> – <?= $c['period_end'] ?>
                    </td>
                    <td>
                        <?php if ($c['duplicate']): ?>
                            <span class="badge badge-warning">Already Invoiced</span>
                        <?php elseif (!$c['qualifies']): ?>
                            <span class="badge badge-danger">Below Threshold</span>
                        <?php else: ?>
                            <span class="badge badge-success">&#10003; Will Generate</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($candidates)): ?>
                <tr class="dt-empty-row"><td colspan="8" class="text-center text-muted" style="padding:32px">No active affiliates with a positive balance.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="max-width:560px">
        <div class="card-header"><span class="card-title">Generation Options</span></div>
        <div class="card-body">
            <div class="form-group">
                <label>Due Date Override <span style="color:#94A3B8;font-size:12px">(optional — overrides per-term due date)</span></label>
                <input type="date" name="due_date_override" class="form-control" min="<?= date('Y-m-d') ?>">
                <div class="form-hint">Leave blank to use each affiliate's payment term default.</div>
            </div>
            <button type="submit" class="btn btn-primary"
                    onclick="return confirmGen()"
                    id="genBtn">&#9889; Generate Invoices</button>
        </div>
    </div>
</form>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    $('#autoTbl .dt-empty-row').remove();
    $('#autoTbl').DataTable({pageLength:25,destroy:true,order:[[7,'asc']],language:{emptyTable:'No active affiliates with a positive balance.'}});
});

function selAll(state) {
    document.querySelectorAll('.aff-row-cb:not(:disabled)').forEach(cb => cb.checked = state);
    document.getElementById('masterCb').checked = state;
}
function confirmGen() {
    var cnt = document.querySelectorAll('.aff-row-cb:checked').length;
    if (cnt === 0) { alert('No affiliates selected.'); return false; }
    return confirm('Generate ' + cnt + ' invoice(s)? This will also send email notifications.');
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
