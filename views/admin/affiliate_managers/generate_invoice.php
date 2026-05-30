<?php
$pageTitle = 'Generate Invoice — ' . $manager['first_name'] . ' ' . $manager['last_name'];
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div>
        <h1>&#128196; Generate Commission Invoice</h1>
        <p><?= Helpers::e($manager['first_name'] . ' ' . $manager['last_name']) ?> &mdash; <?= Helpers::e($manager['email']) ?></p>
    </div>
    <a href="/admin/affiliate-managers/<?= (int)$manager['mgr_id'] ?>" class="btn btn-secondary">← Back to Manager</a>
</div>

<?php foreach (Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $err): ?><div>&#9888; <?= Helpers::e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Balance summary -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    <div class="stat-card">
        <div class="stat-icon" style="background:#DCFCE7;color:#059669">&#128181;</div>
        <div class="stat-label">Available Balance</div>
        <div class="stat-value" style="color:#059669">$<?= number_format((float)$mgrBalance['balance'], 2) ?></div>
        <div class="stat-sub">Max invoice amount</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#FEF9C3;color:#CA8A04">&#9889;</div>
        <div class="stat-label">Pending</div>
        <div class="stat-value" style="color:#CA8A04">$<?= number_format((float)$mgrBalance['pending'], 2) ?></div>
        <div class="stat-sub">Awaiting approval</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#EEF2FF;color:#4F46E5">&#10003;</div>
        <div class="stat-label">Approved</div>
        <div class="stat-value" style="color:#4F46E5">$<?= number_format((float)$mgrBalance['approved'], 2) ?></div>
        <div class="stat-sub">Ready to pay</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#15803D">&#128176;</div>
        <div class="stat-label">Previously Paid</div>
        <div class="stat-value" style="color:#15803D">$<?= number_format((float)$mgrBalance['paid'], 2) ?></div>
        <div class="stat-sub">Historical payments</div>
    </div>
</div>

<?php if ((float)$mgrBalance['balance'] <= 0): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px;color:#94A3B8">
        <div style="font-size:40px;margin-bottom:12px">&#128683;</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px;color:#475569">No Available Balance</div>
        <div style="font-size:13px">This manager has no commission balance to invoice against.</div>
        <a href="/admin/affiliate-managers/<?= (int)$manager['mgr_id'] ?>" class="btn btn-secondary" style="margin-top:16px">← Back</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header"><span class="card-title">Invoice Details</span></div>
    <div class="card-body" style="max-width:680px">
        <form method="POST" id="inv-form">
            <?= Helpers::csrf() ?>

            <div class="form-group">
                <label style="font-weight:600">Payment Amount <span style="color:#EF4444">*</span></label>
                <div style="position:relative;max-width:200px">
                    <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#64748B;font-weight:600">$</span>
                    <input type="number" name="amount" id="inv-amount" class="form-control" required
                           min="0.01" max="<?= number_format((float)$mgrBalance['balance'], 4, '.', '') ?>"
                           step="0.01"
                           value="<?= number_format((float)$mgrBalance['balance'], 2) ?>"
                           style="padding-left:22px"
                           oninput="updatePreview()">
                </div>
                <div style="font-size:12px;color:#6B7280;margin-top:4px">
                    Max: $<?= number_format((float)$mgrBalance['balance'], 2) ?> (current balance)
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label style="font-weight:600">Period Start <span style="color:#EF4444">*</span></label>
                    <input type="date" name="period_start" class="form-control" required
                           value="<?= date('Y-m-01') ?>">
                </div>
                <div class="form-group">
                    <label style="font-weight:600">Period End <span style="color:#EF4444">*</span></label>
                    <input type="date" name="period_end" class="form-control" required
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label style="font-weight:600">Due Date</label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight:600">Notes</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="Optional internal notes..."></textarea>
            </div>

            <!-- Live preview -->
            <div id="inv-preview" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px;margin:16px 0">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:12px">Payment Preview</div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:13px;color:#475569">Commission Balance Before:</span>
                    <span style="font-size:13px;font-weight:600;color:#1E293B">$<?= number_format((float)$mgrBalance['balance'], 2) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:13px;color:#475569">Invoice Amount (deducted):</span>
                    <span id="prev-amount" style="font-size:13px;font-weight:600;color:#DC2626">&minus; $<?= number_format((float)$mgrBalance['balance'], 2) ?></span>
                </div>
                <div style="border-top:1px solid #E2E8F0;padding-top:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;font-weight:700;color:#475569">Balance After Payment:</span>
                    <span id="prev-after" style="font-size:15px;font-weight:800;color:#059669">$0.00</span>
                </div>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Generate invoice and deduct $' + document.getElementById('inv-amount').value + ' from this manager\'s balance?')">
                    &#128196; Generate &amp; Deduct from Balance
                </button>
                <a href="/admin/affiliate-managers/<?= (int)$manager['mgr_id'] ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var _maxBal = <?= (float)$mgrBalance['balance'] ?>;
function updatePreview() {
    var amt = Math.max(0, Math.min(parseFloat(document.getElementById('inv-amount').value) || 0, _maxBal));
    var after = Math.max(0, _maxBal - amt);
    document.getElementById('prev-amount').textContent = '− $' + amt.toFixed(2);
    document.getElementById('prev-after').textContent  = '$' + after.toFixed(2);
    document.getElementById('prev-after').style.color  = after > 0 ? '#059669' : '#94A3B8';
}
</script>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
