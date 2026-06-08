<?php
$pageTitle = 'Edit Invoice ' . $invoice['invoice_number'];
require BASE_PATH . '/views/layouts/admin.php';
$statusColors = ['draft'=>'#94A3B8','sent'=>'#3B82F6','paid'=>'#10B981','void'=>'#EF4444'];
?>

<div class="page-header">
    <div>
        <h1>Edit Invoice</h1>
        <p><code><?= Helpers::e($invoice['invoice_number']) ?></code></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/invoices/<?= $invoice['id'] ?>" class="btn btn-secondary">← Back to Invoice</a>
        <a href="/admin/invoices" class="btn btn-secondary">All Invoices</a>
    </div>
</div>

<form method="POST" action="/admin/invoices?action=edit&id=<?= $invoice['id'] ?>">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        <!-- Left: Details -->
        <div class="card">
            <div class="card-header"><span class="card-title">Invoice Details</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Invoice #</label>
                    <input type="text" class="form-control" value="<?= Helpers::e($invoice['invoice_number']) ?>" disabled style="background:#F8FAFC;color:#94A3B8">
                </div>
                <div class="form-group">
                    <label>Recipient</label>
                    <input type="text" class="form-control" value="<?= Helpers::e($invoice['entity_name'] ?? '—') ?>" disabled style="background:#F8FAFC;color:#94A3B8">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['draft','sent','paid','void'] as $s): ?>
                        <option value="<?= $s ?>" <?= $invoice['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?= Helpers::e($invoice['due_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Tax Rate (%)</label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01" max="100" value="<?= (float)$invoice['tax_rate'] ?>" id="taxRate" oninput="recalc()">
                </div>
                <div class="form-group">
                    <label>Total Override <span class="text-muted" style="font-size:11px">(leave blank to use calculated total)</span></label>
                    <input type="number" name="total_override" class="form-control" step="0.0001" min="0" placeholder="e.g. 9.00" value="">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= Helpers::e($invoice['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right: Line Items -->
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span class="card-title">Line Items</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">+ Add Row</button>
            </div>
            <div class="card-body" style="padding:0">
                <table style="width:100%;font-size:13px;border-collapse:collapse" id="itemsTable">
                    <thead style="background:#F8FAFC">
                        <tr>
                            <th style="padding:8px 12px;text-align:left;font-weight:600;border-bottom:1px solid #E2E8F0">Description</th>
                            <th style="padding:8px 8px;text-align:center;font-weight:600;border-bottom:1px solid #E2E8F0;width:60px">Qty</th>
                            <th style="padding:8px 8px;text-align:right;font-weight:600;border-bottom:1px solid #E2E8F0;width:80px">Rate</th>
                            <th style="padding:8px 8px;text-align:right;font-weight:600;border-bottom:1px solid #E2E8F0;width:80px">Amount</th>
                            <th style="padding:8px 8px;border-bottom:1px solid #E2E8F0;width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                    <?php foreach ($items as $item): ?>
                    <tr class="item-row">
                        <td style="padding:6px 12px"><input type="text" name="item_desc[]" class="form-control" style="font-size:12px" value="<?= Helpers::e($item['description']) ?>" required></td>
                        <td style="padding:6px 8px"><input type="number" name="item_qty[]" class="form-control item-qty" style="font-size:12px;text-align:center" step="0.01" min="0" value="<?= (float)$item['qty'] ?>" oninput="recalc()"></td>
                        <td style="padding:6px 8px"><input type="number" name="item_rate[]" class="form-control item-rate" style="font-size:12px;text-align:right" step="0.0001" min="0" value="<?= (float)$item['rate'] ?>" oninput="recalc()"></td>
                        <td style="padding:6px 8px;text-align:right" class="item-amount">$<?= number_format((float)$item['amount'],2) ?></td>
                        <td style="padding:6px 8px;text-align:center"><button type="button" onclick="this.closest('tr').remove();recalc()" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:16px">×</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body" style="border-top:1px solid #E2E8F0;background:#F8FAFC">
                <div style="display:flex;justify-content:flex-end;gap:24px;font-size:13px">
                    <div>Subtotal: <strong id="dispSubtotal">$<?= number_format((float)$invoice['subtotal'],2) ?></strong></div>
                    <div>Tax: <strong id="dispTax">$<?= number_format((float)$invoice['tax_amount'],2) ?></strong></div>
                    <div style="font-size:15px">Total: <strong id="dispTotal" style="color:var(--primary)">$<?= number_format((float)$invoice['total'],2) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="/admin/invoices/<?= $invoice['id'] ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

<script>
function recalc() {
    var subtotal = 0;
    document.querySelectorAll('.item-row').forEach(function(row) {
        var qty  = parseFloat(row.querySelector('.item-qty').value)  || 0;
        var rate = parseFloat(row.querySelector('.item-rate').value) || 0;
        var amt  = qty * rate;
        row.querySelector('.item-amount').textContent = '$' + amt.toFixed(2);
        subtotal += amt;
    });
    var taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
    var tax     = subtotal * taxRate / 100;
    var total   = subtotal + tax;
    document.getElementById('dispSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('dispTax').textContent      = '$' + tax.toFixed(2);
    document.getElementById('dispTotal').textContent    = '$' + total.toFixed(2);
}

function addRow() {
    var tbody = document.getElementById('itemsBody');
    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = '<td style="padding:6px 12px"><input type="text" name="item_desc[]" class="form-control" style="font-size:12px" placeholder="Description" required></td>'
        + '<td style="padding:6px 8px"><input type="number" name="item_qty[]" class="form-control item-qty" style="font-size:12px;text-align:center" step="0.01" min="0" value="1" oninput="recalc()"></td>'
        + '<td style="padding:6px 8px"><input type="number" name="item_rate[]" class="form-control item-rate" style="font-size:12px;text-align:right" step="0.0001" min="0" value="0" oninput="recalc()"></td>'
        + '<td style="padding:6px 8px;text-align:right" class="item-amount">$0.00</td>'
        + '<td style="padding:6px 8px;text-align:center"><button type="button" onclick="this.closest(\'tr\').remove();recalc()" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:16px">×</button></td>';
    tbody.appendChild(tr);
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
