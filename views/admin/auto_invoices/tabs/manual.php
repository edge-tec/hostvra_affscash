<?php
/**
 * Tab 6: Manual Invoice Generator & Interactive Preview
 */
?>

<div class="card mb-4">
    <div class="card-header" style="background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:12px 12px 0 0;padding:18px 24px;">
        <h3 style="color:#ffffff;margin:0;font-size:16px;font-weight:700;">Manual Affiliate Invoice Generator</h3>
        <p style="color:#c7d2fe;margin:4px 0 0 0;font-size:12.5px;">
            Filter conversions by affiliate, date range, offer or country, inspect the line items in real time, and generate an on-demand invoice.
        </p>
    </div>

    <div class="card-body" style="padding:24px;">
        <!-- Filters Form -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;align-items:flex-end;">
            <div class="form-group mb-0">
                <label style="font-weight:700;font-size:13px;margin-bottom:6px;display:block;">Affiliate <span style="color:#ef4444">*</span></label>
                <select id="manAffiliateSelect" placeholder="Search & select affiliate...">
                    <option value="">Select affiliate...</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>">
                        #<?= $a['id'] ?> - <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?> (Bal: $<?= number_format((float)$a['balance'],2) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label style="font-weight:700;font-size:13px;margin-bottom:6px;display:block;">Filter by Offer(s) (Optional)</label>
                <select id="manOfferSelect" multiple placeholder="All Offers...">
                    <?php foreach ($offers as $o): ?>
                    <option value="<?= $o['id'] ?>">#<?= $o['id'] ?> - <?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label style="font-weight:700;font-size:13px;margin-bottom:6px;display:block;">Billing Start Date</label>
                <input type="date" id="manStartDate" class="form-control" value="<?= date('Y-m-01', strtotime('last month')) ?>">
            </div>

            <div class="form-group mb-0">
                <label style="font-weight:700;font-size:13px;margin-bottom:6px;display:block;">Billing End Date</label>
                <input type="date" id="manEndDate" class="form-control" value="<?= date('Y-m-t', strtotime('last month')) ?>">
            </div>

            <div class="form-group mb-0">
                <button type="button" class="btn btn-primary" onclick="loadConversionsPreview()" style="width:100%;height:38px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Preview Conversions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview & Calculation Section (Hidden until Preview Clicked) -->
<div id="previewSection" style="display:none;">
    <!-- Duplicate or Threshold Warning Banner -->
    <div id="dupWarning" class="alert alert-warning" style="display:none;margin-bottom:16px;">
        <strong>&#9888; Warning:</strong> An invoice covering this affiliate and date period already exists. Generating another one will create an additional billing record.
    </div>

    <div style="display:grid;grid-template-columns: 2fr 1fr;gap:24px;align-items:flex-start;">
        <!-- Left: Line Items Table -->
        <div class="card">
            <div class="card-header" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;">
                <h4 style="margin:0;font-size:15px;font-weight:700;">Conversion Line Items</h4>
                <span id="previewConvCountBadge" class="badge badge-info" style="font-size:12px;">0 Conversions</span>
            </div>
            <div class="table-wrap">
                <table class="table" id="tbl-preview-items">
                    <thead>
                        <tr>
                            <th>Offer Name</th>
                            <th>Campaign ID</th>
                            <th>GEO</th>
                            <th>Qty</th>
                            <th>Avg Rate</th>
                            <th style="text-align:right">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Summary & Generation Options -->
        <div class="card">
            <div class="card-header" style="padding:16px 20px;">
                <h4 style="margin:0;font-size:15px;font-weight:700;">Invoice Summary &amp; Adjustments</h4>
            </div>
            <div class="card-body" style="padding:20px;">
                <form id="formManualGenerate" method="POST" action="/admin/auto-invoices?tab=manual">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="generate_manual_invoice">
                    <input type="hidden" name="affiliate_id" id="postAffId" value="">
                    <input type="hidden" name="period_start" id="postStartDate" value="">
                    <input type="hidden" name="period_end" id="postEndDate" value="">
                    
                    <div style="margin-bottom:12px;display:flex;justify-content:space-between;font-size:13.5px;">
                        <span style="color:#64748b;">Gross Conversions:</span>
                        <strong id="summaryGrossConvs">0</strong>
                    </div>

                    <div style="margin-bottom:12px;display:flex;justify-content:space-between;font-size:13.5px;">
                        <span style="color:#64748b;">Subtotal:</span>
                        <strong id="summarySubtotal" style="font-size:15px;color:#1e293b;">$0.00</strong>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Tax Rate (%)</label>
                        <input type="number" step="0.1" name="tax_rate" id="manTaxRate" class="form-control" value="0" min="0" oninput="recalcTotals()">
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Adjustment ($)</label>
                        <input type="number" step="0.01" name="adjustment" id="manAdjustment" class="form-control" value="0.00" oninput="recalcTotals()">
                        <small class="text-muted">Bonuses, manual corrections (+ or -)</small>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Chargebacks / Deductions ($)</label>
                        <input type="number" step="0.01" name="chargeback_amount" id="manChargeback" class="form-control" value="0.00" min="0" oninput="recalcTotals()">
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Due Date</label>
                        <input type="date" name="due_date" id="manDueDate" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Notes / Memo (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment notes or instructions..."></textarea>
                    </div>

                    <div style="border-top:2px solid #e2e8f0;padding-top:16px;margin-top:16px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:15px;font-weight:800;color:#1e293b;">Final Total:</span>
                        <span id="summaryGrandTotal" style="font-size:22px;font-weight:800;color:#10b981;">$0.00</span>
                    </div>

                    <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;">
                        <button type="submit" class="btn btn-primary" style="width:100%;font-weight:700;padding:10px;">
                            &#10003; Generate &amp; Save Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
var manAffSelect = null;
var manOfferSelect = null;
var currentRawTotal = 0;

$(function() {
    manAffSelect = new TomSelect('#manAffiliateSelect', {
        create: false,
        placeholder: 'Select affiliate...'
    });

    manOfferSelect = new TomSelect('#manOfferSelect', {
        plugins: ['remove_button'],
        create: false,
        placeholder: 'Filter by specific offers...'
    });
});

function loadConversionsPreview() {
    var affId = document.getElementById('manAffiliateSelect').value;
    var start = document.getElementById('manStartDate').value;
    var end   = document.getElementById('manEndDate').value;
    var offers = manOfferSelect ? manOfferSelect.getValue() : [];

    if (!affId) {
        alert('Please select an affiliate first.');
        return;
    }

    var qs = 'affiliate_id=' + encodeURIComponent(affId) +
             '&start_date=' + encodeURIComponent(start) +
             '&end_date=' + encodeURIComponent(end);
    if (offers && offers.length > 0) {
        qs += '&offer_id=' + encodeURIComponent(offers.join(','));
    }

    fetch('/admin/auto-invoices?action=preview_conversions&' + qs)
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d.success) {
            alert('Error: ' + (d.error || 'Failed to preview conversions'));
            return;
        }

        document.getElementById('postAffId').value = affId;
        document.getElementById('postStartDate').value = start;
        document.getElementById('postEndDate').value = end;

        document.getElementById('dupWarning').style.display = d.is_duplicate ? 'block' : 'none';
        document.getElementById('previewConvCountBadge').innerText = d.conversion_count + ' Conversions';
        document.getElementById('summaryGrossConvs').innerText = d.conversion_count;
        
        currentRawTotal = parseFloat(d.subtotal) || 0;
        document.getElementById('summarySubtotal').innerText = '$' + currentRawTotal.toFixed(2);

        // Populate line items table
        var tbody = document.getElementById('previewTableBody');
        tbody.innerHTML = '';

        if (!d.items || d.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:24px;">No approved conversions found for this period. Current balance will be used if positive.</td></tr>';
        } else {
            d.items.forEach(function(item) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><strong>' + escapeHtml(item.offer_name) + '</strong></td>' +
                               '<td><code style="font-size:11px;">' + escapeHtml(item.campaign_id) + '</code></td>' +
                               '<td style="font-size:12px;color:#64748b;">' + escapeHtml(item.geo || 'ALL') + '</td>' +
                               '<td>' + item.qty + '</td>' +
                               '<td>$' + parseFloat(item.rate).toFixed(2) + '</td>' +
                               '<td style="text-align:right;font-weight:700;color:#1e293b;">$' + parseFloat(item.amount).toFixed(2) + '</td>';
                tbody.appendChild(tr);
            });
        }

        recalcTotals();
        document.getElementById('previewSection').style.display = 'block';
    })
    .catch(function(err){
        alert('Network error loading conversion preview.');
    });
}

function recalcTotals() {
    var taxRate = parseFloat(document.getElementById('manTaxRate').value) || 0;
    var adjustment = parseFloat(document.getElementById('manAdjustment').value) || 0;
    var chargeback = parseFloat(document.getElementById('manChargeback').value) || 0;

    var taxAmt = currentRawTotal * (taxRate / 100);
    var finalTot = Math.max(0, currentRawTotal + taxAmt + adjustment - chargeback);

    document.getElementById('summaryGrandTotal').innerText = '$' + finalTot.toFixed(2);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
