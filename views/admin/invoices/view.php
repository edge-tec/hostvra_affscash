<?php
$pageTitle = 'Invoice ' . $invoice['invoice_number'];
$isPrint = !empty($_GET['print']);
if ($isPrint) {
    // Print mode — clean layout, no admin shell
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice '.$invoice['invoice_number'].'</title>';
    echo '<link rel="stylesheet" href="/assets/css/app.min.css">';
    echo '<style>@media print{.no-print{display:none!important}}body{background:#fff}@page{margin:20mm}</style>';
    echo '</head><body style="padding:32px;max-width:800px;margin:0 auto">';
} else {
    require BASE_PATH . '/views/layouts/admin.php';
}
$statusColors = ['draft'=>'#94A3B8','sent'=>'#3B82F6','paid'=>'#10B981','void'=>'#EF4444'];
$appName    = Config::get('config','app.name')    ?? 'AffiliateTracker';
$appAddress = Config::get('config','app.address') ?? '';
$appPhone   = Config::get('config','app.phone')   ?? '';
$appLogo    = Config::get('config','app.logo')    ?? '';
$appUrl     = Config::get('config','app.url')     ?? '';
// Affiliate payment details — use override if saved, else from affiliate record
$affPaymentMethod  = $entityExtra['payment_method']          ?? '';
$affPaymentDetails = $invoice['payment_details_override']
                     ?? ($entityExtra['payment_details'] ?? '');
require_once BASE_PATH . '/views/admin/_payment_details_display.php';
$affCode           = $entityExtra['affiliate_code']          ?? '';
$affPhone          = $entityExtra['phone']                   ?? '';
$csrfToken         = Auth::generateCsrf();
?>

<?php if (!$isPrint): ?>
<div class="page-header no-print">
    <div><h1>Invoice <?= Helpers::e($invoice['invoice_number']) ?></h1></div>
    <div class="d-flex gap-2">
        <a href="/admin/invoices?action=download_pdf&id=<?= $invoice['id'] ?>" class="btn btn-primary" download>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
        <a href="/admin/invoices/<?= $invoice['id'] ?>?print=1" class="btn btn-secondary" target="_blank">&#128424; Print</a>
        <a href="/admin/invoices" class="btn btn-secondary">← Back</a>
    </div>
</div>

<!-- Status update form -->
<div class="card mb-3 no-print">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <span>Status: <span class="badge" style="background:<?= $statusColors[$invoice['status']] ?>;color:#fff;font-size:13px"><?= strtoupper($invoice['status']) ?></span></span>
        <form method="POST" style="display:flex;gap:8px;align-items:center">
            <?= Helpers::csrf() ?>
            <select name="status" class="form-control" style="width:auto">
                <option value="draft" <?= $invoice['status']==='draft'?'selected':'' ?>>Draft</option>
                <option value="sent" <?= $invoice['status']==='sent'?'selected':'' ?>>Sent</option>
                <option value="paid" <?= $invoice['status']==='paid'?'selected':'' ?>>Paid</option>
                <option value="void" <?= $invoice['status']==='void'?'selected':'' ?>>Void</option>
            </select>
            <button class="btn btn-primary btn-sm">Update</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Invoice document -->
<style>
.inv-card-body {
    padding: 32px;
}
.inv-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}
.inv-meta-right {
    text-align: right;
}
.inv-meta-right table {
    margin-left: auto;
    font-size: 13px;
}
@media (max-width: 768px) {
    .inv-card-body {
        padding: 16px !important;
    }
    .inv-meta-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .inv-meta-right {
        text-align: left;
    }
    .inv-meta-right table {
        margin-left: 0;
    }
}
</style>
<div class="card" style="<?= $isPrint ? 'box-shadow:none;border:none' : '' ?>">
    <div class="card-body inv-card-body">
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;flex-wrap:wrap;gap:16px">
            <div>
                <?php if ($appLogo): ?>
                <img src="<?= Helpers::e($appLogo) ?>" alt="<?= Helpers::e($appName) ?>" style="max-height:48px;max-width:180px;object-fit:contain;margin-bottom:8px;display:block">
                <?php endif; ?>
                <div style="font-size:22px;font-weight:800;color:#4F46E5"><?= Helpers::e($appName) ?></div>
                <?php if ($appUrl): ?><div style="color:#64748B;font-size:12px;margin-top:2px"><?= Helpers::e($appUrl) ?></div><?php endif; ?>
                <?php if ($appAddress): ?><div style="color:#64748B;font-size:12px;margin-top:2px">📍 <?= Helpers::e($appAddress) ?></div><?php endif; ?>
                <?php if ($appPhone): ?><div style="color:#64748B;font-size:12px;margin-top:2px">📞 <?= Helpers::e($appPhone) ?></div><?php endif; ?>
            </div>
            <div style="text-align:right">
                <div style="font-size:28px;font-weight:800;color:#1E293B">INVOICE</div>
                <div style="color:#4F46E5;font-weight:700;margin-top:4px"><?= Helpers::e($invoice['invoice_number']) ?></div>
                <div style="margin-top:8px;padding:4px 12px;border-radius:20px;display:inline-block;color:#fff;font-size:12px;font-weight:700;background:<?= $statusColors[$invoice['status']] ?>"><?= strtoupper($invoice['status']) ?></div>
            </div>
        </div>

        <!-- Bill To / Details -->
        <div class="inv-meta-grid">
            <div>
                <div style="font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">Bill To</div>
                <div style="font-weight:700;font-size:15px"><?= Helpers::e($entityName) ?></div>
                <?php if ($affCode): ?><div style="color:#94A3B8;font-size:12px;font-family:monospace"><?= Helpers::e($affCode) ?></div><?php endif; ?>
                <div style="color:#64748B;font-size:13px"><?= Helpers::e($entityEmail) ?></div>
                <?php if ($affPhone): ?><div style="color:#64748B;font-size:13px">📞 <?= Helpers::e($affPhone) ?></div><?php endif; ?>
                <div style="color:#64748B;font-size:13px;margin-top:4px"><?= $invoice['type']==='affiliate_payout' ? 'Affiliate Payout' : 'Advertiser Billing' ?></div>
                <?php if ($affPaymentMethod || $affPaymentDetails): ?>
                <div style="margin-top:10px;padding:10px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px">
                    <div style="font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px">
                        Payment Details
                        <?php if (!$isPrint): ?>
                        <button type="button" onclick="openEditPayment()" style="margin-left:8px;background:#EEF2FF;border:none;color:#4338CA;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;cursor:pointer" class="no-print">✎ Edit</button>
                        <?php endif; ?>
                    </div>
                    <?php if ($affPaymentMethod): ?><div style="font-size:13px;font-weight:600;color:#1E293B;margin-bottom:6px"><?= Helpers::e($affPaymentMethod) ?></div><?php endif; ?>
                    <div id="paymentDetailsDisplay"><?= formatPaymentDetailsHtml($affPaymentMethod, $affPaymentDetails) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="inv-meta-right">
                <table style="font-size:13px">
                    <tr><td style="color:#64748B;padding:2px 8px 2px 0">Invoice Date:</td><td style="font-weight:600"><?= date('M j, Y',strtotime($invoice['created_at'])) ?></td></tr>
                    <?php if ($invoice['due_date']): ?>
                    <tr><td style="color:#64748B;padding:2px 8px 2px 0">Due Date:</td><td style="font-weight:600;color:<?= strtotime($invoice['due_date'])<time()&&$invoice['status']!=='paid'?'#EF4444':'inherit' ?>"><?= date('M j, Y',strtotime($invoice['due_date'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($invoice['period_start']): ?>
                    <tr><td style="color:#64748B;padding:2px 8px 2px 0">Period:</td><td><?= date('M j',strtotime($invoice['period_start'])) ?> – <?= date('M j, Y',strtotime($invoice['period_end'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($invoice['paid_at']): ?>
                    <tr><td style="color:#64748B;padding:2px 8px 2px 0">Paid:</td><td style="color:#10B981;font-weight:700"><?= date('M j, Y',strtotime($invoice['paid_at'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Line items -->
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;margin-bottom:24px;min-width:400px">
                <thead>
                    <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                        <th style="padding:10px 12px;text-align:left;font-size:12px;color:#64748B;text-transform:uppercase;letter-spacing:.5px">Description</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748B;width:70px">Qty</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748B;width:100px">Rate</th>
                        <th style="padding:10px 12px;text-align:right;font-size:12px;color:#64748B;width:110px">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr style="border-bottom:1px solid #F1F5F9">
                    <td style="padding:10px 12px;font-size:14px"><?= Helpers::e($item['description']) ?></td>
                    <td style="padding:10px 12px;text-align:right;font-size:14px"><?= $item['qty'] ?></td>
                    <td style="padding:10px 12px;text-align:right;font-size:14px">$<?= number_format($item['rate'],2) ?></td>
                    <td style="padding:10px 12px;text-align:right;font-size:14px;font-weight:600">$<?= number_format($item['amount'],2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div style="display:flex;justify-content:flex-end">
            <table style="font-size:14px;min-width:220px">
                <tr><td style="padding:4px 16px 4px 0;color:#64748B">Subtotal</td><td style="text-align:right;padding:4px 0">$<?= number_format($invoice['subtotal'],2) ?></td></tr>
                <?php if ($invoice['tax_rate'] > 0): ?>
                <tr><td style="padding:4px 16px 4px 0;color:#64748B">Tax (<?= $invoice['tax_rate'] ?>%)</td><td style="text-align:right;padding:4px 0">$<?= number_format($invoice['tax_amount'],2) ?></td></tr>
                <?php endif; ?>
                <tr style="border-top:2px solid #E2E8F0">
                    <td style="padding:10px 16px 4px 0;font-weight:700;font-size:16px">Total</td>
                    <td style="text-align:right;padding:10px 0 4px">
                        <span id="totalDisplay" style="font-weight:800;font-size:18px;color:#4F46E5">$<?= number_format($invoice['total'],2) ?></span>
                        <?php if (!$isPrint): ?>
                        <button type="button" onclick="openEditTotal()" style="margin-left:8px;background:#EEF2FF;border:none;color:#4338CA;font-size:11px;font-weight:700;padding:3px 9px;border-radius:4px;cursor:pointer;vertical-align:middle" class="no-print">✎ Edit</button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        <?php if ($invoice['notes']): ?>
        <div style="border-top:1px solid #E2E8F0;margin-top:24px;padding-top:16px;color:#64748B;font-size:13px">
            <strong>Notes:</strong> <?= Helpers::e($invoice['notes']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isPrint): ?>
<div class="no-print" style="margin-top:24px;text-align:center">
    <button onclick="window.print()" style="background:#4F46E5;color:#fff;padding:10px 32px;border:none;border-radius:6px;font-size:15px;cursor:pointer">&#128424; Print</button>
</div>
</body></html>
<?php else: ?>

<!-- ── Edit Total Modal ────────────────────────────────────────────────────── -->
<div id="editTotalModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <div style="font-size:16px;font-weight:700;color:#1E293B;margin-bottom:16px">✎ Edit Invoice Total</div>
        <div style="font-size:13px;color:#64748B;margin-bottom:12px">Current total: <strong id="currentTotalDisplay"></strong></div>
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px">New Total Amount ($)</label>
        <input type="number" id="newTotalInput" step="0.01" min="0" style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:15px;font-family:inherit;box-sizing:border-box">
        <div id="editTotalError" style="color:#EF4444;font-size:12px;margin-top:6px;display:none"></div>
        <div style="display:flex;gap:10px;margin-top:18px">
            <button onclick="saveEditTotal()" style="flex:1;background:#4338CA;color:#fff;border:none;padding:10px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer">Save</button>
            <button onclick="closeEditTotal()" style="flex:1;background:#F1F5F9;color:#64748B;border:none;padding:10px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer">Cancel</button>
        </div>
    </div>
</div>

<!-- ── Edit Payment Details Modal ─────────────────────────────────────────── -->
<div id="editPaymentModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <div style="font-size:16px;font-weight:700;color:#1E293B;margin-bottom:16px">✎ Edit Payment Details</div>
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px">Payment Details <span style="font-weight:400;color:#94A3B8;font-size:11px">(plain text override)</span></label>
        <textarea id="newPaymentInput" rows="5" style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:13px;font-family:inherit;resize:vertical;box-sizing:border-box"><?php
            // Pre-fill with human-readable text: if JSON, expand to key: value lines
            $_pd = trim($affPaymentDetails);
            if ($_pd !== '' && $_pd[0] === '{') {
                $_dec = json_decode($_pd, true);
                if (is_array($_dec)) {
                    $_labelMap = [
                        'account_holder_name'=>'Account Holder Name','email'=>'Email / Account ID',
                        'bank_name'=>'Bank Name','account_number'=>'Account Number',
                        'iban_swift'=>'IBAN / SWIFT','routing_number'=>'Routing Number',
                        'branch_name'=>'Branch Name','bank_address'=>'Bank Address',
                        'crypto_type'=>'Cryptocurrency','network_type'=>'Network',
                        'wallet_address'=>'Wallet Address',
                    ];
                    $_lines = [];
                    foreach ($_dec as $_k => $_v) {
                        $_v = trim((string)$_v);
                        if ($_v === '') continue;
                        $_lbl = $_labelMap[$_k] ?? ucwords(str_replace('_',' ',$_k));
                        $_lines[] = $_lbl . ': ' . $_v;
                    }
                    echo htmlspecialchars(implode("\n", $_lines));
                } else {
                    echo htmlspecialchars($_pd);
                }
            } else {
                echo htmlspecialchars($_pd);
            }
        ?></textarea>
        <div id="editPaymentError" style="color:#EF4444;font-size:12px;margin-top:6px;display:none"></div>
        <div style="display:flex;gap:10px;margin-top:18px">
            <button onclick="saveEditPayment()" style="flex:1;background:#4338CA;color:#fff;border:none;padding:10px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer">Save</button>
            <button onclick="closeEditPayment()" style="flex:1;background:#F1F5F9;color:#64748B;border:none;padding:10px;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer">Cancel</button>
        </div>
    </div>
</div>

<script>
var INVOICE_ID  = <?= (int)$invoice['id'] ?>;
var CSRF_TOKEN  = '<?= Helpers::e($csrfToken) ?>';
var currentTotal = <?= (float)$invoice['total'] ?>;

// ── Edit Total ──────────────────────────────────────────────────────────────
function openEditTotal() {
    document.getElementById('currentTotalDisplay').textContent = '$' + currentTotal.toFixed(2);
    document.getElementById('newTotalInput').value = currentTotal.toFixed(2);
    document.getElementById('editTotalError').style.display = 'none';
    document.getElementById('editTotalModal').style.display = 'flex';
    setTimeout(function(){ document.getElementById('newTotalInput').focus(); document.getElementById('newTotalInput').select(); }, 50);
}
function closeEditTotal() {
    document.getElementById('editTotalModal').style.display = 'none';
}
function saveEditTotal() {
    var val = parseFloat(document.getElementById('newTotalInput').value);
    var errEl = document.getElementById('editTotalError');
    if (isNaN(val) || val < 0) {
        errEl.textContent = 'Please enter a valid positive amount.';
        errEl.style.display = '';
        return;
    }
    var fd = new FormData();
    fd.append('_token',     CSRF_TOKEN);
    fd.append('invoice_id', INVOICE_ID);
    fd.append('new_total',  val.toFixed(2));
    fetch('/admin/invoices?action=edit_total', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.error) { errEl.textContent = d.error; errEl.style.display = ''; return; }
            currentTotal = val;
            document.getElementById('totalDisplay').textContent = '$' + val.toFixed(2);
            closeEditTotal();
        })
        .catch(function(){ errEl.textContent = 'Network error. Please try again.'; errEl.style.display = ''; });
}

// ── Edit Payment Details ────────────────────────────────────────────────────
function openEditPayment() {
    document.getElementById('editPaymentError').style.display = 'none';
    document.getElementById('editPaymentModal').style.display = 'flex';
    setTimeout(function(){ document.getElementById('newPaymentInput').focus(); }, 50);
}
function closeEditPayment() {
    document.getElementById('editPaymentModal').style.display = 'none';
}
function saveEditPayment() {
    var val   = document.getElementById('newPaymentInput').value;
    var errEl = document.getElementById('editPaymentError');
    var fd = new FormData();
    fd.append('_token',          CSRF_TOKEN);
    fd.append('invoice_id',      INVOICE_ID);
    fd.append('payment_details', val);
    fetch('/admin/invoices?action=edit_payment_details', { method:'POST', body:fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.error) { errEl.textContent = d.error; errEl.style.display = ''; return; }
            // Reload so PHP re-renders the formatted display with the saved override
            window.location.reload();
        })
        .catch(function(){ errEl.textContent = 'Network error. Please try again.'; errEl.style.display = ''; });
}

// Close modals on backdrop click
['editTotalModal','editPaymentModal'].forEach(function(id){
    document.getElementById(id).addEventListener('click', function(e){
        if (e.target === this) this.style.display = 'none';
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
<?php endif; ?>
