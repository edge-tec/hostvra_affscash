<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>Create Invoice</h1>
        <p>Load offer conversions by period, adjust rates, generate PDF &amp; send to affiliate</p>
    </div>
    <a href="/affiliate_manager/invoices" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Step 1: Setup ────────────────────────────────────────────────────────── -->
<div class="card mb-3" id="stepSetup">
    <div class="card-header" style="background:linear-gradient(135deg,#5B21B6,#7C3AED);border-radius:8px 8px 0 0">
        <span class="card-title" style="color:#fff">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Step 1 — Select Affiliate &amp; Period
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3" style="align-items:flex-end">
            <div class="form-group" style="margin-bottom:0">
                <label>Affiliate <span style="color:#EF4444">*</span></label>
                <select id="affSelect" class="form-control">
                    <option value="">Select affiliate...</option>
                    <?php foreach ($managedAffiliates as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Period Start <span style="color:#EF4444">*</span></label>
                <input type="date" id="periodFrom" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Period End <span style="color:#EF4444">*</span></label>
                <input type="date" id="periodTo" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div style="margin-top:16px">
            <button type="button" class="btn btn-primary" style="min-width:160px" onclick="loadOffers()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                Load Offers
            </button>
        </div>

        <div id="loadMsg" style="margin-top:12px;display:none"></div>
    </div>
</div>

<!-- ── Step 2: Offers + invoice form (hidden until offers load) ─────────────── -->
<form method="POST" action="/affiliate_manager/invoices?action=create" id="invoiceForm" style="display:none">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="entity_id"    id="hidEntityId" value="">
    <input type="hidden" name="period_start" id="hidFrom"     value="">
    <input type="hidden" name="period_end"   id="hidTo"       value="">

    <!-- Offer rows table -->
    <div class="card mb-3">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Step 2 — Offers for Selected Period
            </span>
            <span id="periodLabel" style="font-size:13px;color:#64748B"></span>
        </div>
        <div class="card-body" style="padding:0">
            <table style="width:100%;border-collapse:collapse" id="offersTable">
                <thead>
                    <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                        <th style="padding:10px 14px;text-align:left;font-size:12px;color:#64748B;text-transform:uppercase">Offer</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:110px">Conversions</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:140px">Rate / Conv ($)</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:130px">Total Payout</th>
                        <th style="padding:10px 14px;width:44px"></th>
                    </tr>
                </thead>
                <tbody id="offerRows"></tbody>
                <tfoot>
                    <tr style="background:#F1F5F9;border-top:2px solid #E2E8F0">
                        <td style="padding:10px 14px;font-weight:700;font-size:13px">Totals</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:700" id="totalConversions">0</td>
                        <td></td>
                        <td style="padding:10px 14px;text-align:right;font-weight:700;color:#7C3AED;font-size:15px" id="totalPayout">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div id="hiddenItems"></div>
        </div>
    </div>

    <!-- Invoice settings + summary -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Invoice Settings</span></div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" id="fldDueDate">
                    </div>
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" id="fldTaxRate" class="form-control" value="0" max="100" oninput="recalcTotals()">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Payment terms, bank details, etc."></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3" style="background:linear-gradient(135deg,#F5F3FF,#EDE9FE)">
            <div class="card-header" style="background:transparent;border-bottom:1px solid #DDD6FE">
                <span class="card-title" style="color:#7C3AED">Invoice Summary</span>
            </div>
            <div class="card-body">
                <table style="width:100%;font-size:14px">
                    <tr>
                        <td style="color:#64748B;padding:6px 0">Total Conversions:</td>
                        <td style="text-align:right;font-weight:600" id="summConv">0</td>
                    </tr>
                    <tr>
                        <td style="color:#64748B;padding:6px 0">Subtotal:</td>
                        <td style="text-align:right;font-weight:600" id="summSubtotal">$0.00</td>
                    </tr>
                    <tr id="summTaxRow" style="display:none">
                        <td style="color:#64748B;padding:6px 0" id="summTaxLabel">Tax (0%):</td>
                        <td style="text-align:right;font-weight:600" id="summTax">$0.00</td>
                    </tr>
                    <tr style="border-top:2px solid #DDD6FE">
                        <td style="padding:10px 0 4px;font-weight:700;font-size:17px;color:#7C3AED">Total:</td>
                        <td style="text-align:right;font-weight:800;font-size:20px;color:#7C3AED;padding:10px 0 4px" id="summTotal">$0.00</td>
                    </tr>
                </table>

                <div style="margin-top:16px;padding:10px 14px;background:rgba(124,58,237,0.08);border-radius:6px;font-size:12px;color:#7C3AED">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    PDF will be generated &amp; emailed to the affiliate automatically.
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;height:44px;font-size:15px;background:#7C3AED;border-color:#7C3AED" onclick="return prepareSubmit()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Generate Invoice &amp; Send PDF
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.offer-row td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; }
.rate-input   { text-align: right; width: 100%; border: 1px solid #E2E8F0; border-radius: 5px; padding: 5px 8px; font-size: 13px; font-family: inherit; }
.rate-input:focus { outline: none; border-color: #7C3AED; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
.remove-offer { background: none; border: none; color: #CBD5E1; cursor: pointer; padding: 4px; border-radius: 4px; transition: color .15s; }
.remove-offer:hover { color: #EF4444; }
</style>

<script>
var offerData = [];

function loadOffers() {
    var entityId = document.getElementById('affSelect').value;
    var from     = document.getElementById('periodFrom').value;
    var to       = document.getElementById('periodTo').value;

    if (!entityId) { showMsg('error', 'Please select an affiliate.'); return; }
    if (!from || !to) { showMsg('error', 'Please select a period.'); return; }
    if (from > to)  { showMsg('error', 'Period start must be before period end.'); return; }

    showMsg('info', 'Loading offers…');

    fetch('/affiliate_manager/invoices?action=load_offers&affiliate_id=' + entityId + '&from=' + from + '&to=' + to)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) { showMsg('error', d.error); return; }
            if (!d.offers || d.offers.length === 0) {
                showMsg('warning', 'No approved conversions found for this affiliate in the selected period.');
                document.getElementById('invoiceForm').style.display = 'none';
                return;
            }
            offerData = d.offers.map(function(o) {
                return {
                    offer_id    : o.offer_id,
                    offer_name  : o.offer_name,
                    conversions : parseInt(o.conversions) || 0,
                    avg_rate    : parseFloat(o.avg_rate) || 0,
                    total_payout: parseFloat(o.total_payout) || 0,
                };
            });
            renderOffers(entityId, from, to);
            document.getElementById('loadMsg').style.display = 'none';
        })
        .catch(function() { showMsg('error', 'Failed to load offers. Please try again.'); });
}

function renderOffers(entityId, from, to) {
    document.getElementById('hidEntityId').value = entityId;
    document.getElementById('hidFrom').value     = from;
    document.getElementById('hidTo').value       = to;

    var fl = new Date(from + 'T00:00:00'), tl = new Date(to + 'T00:00:00');
    var fmt = function(d) { return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }); };
    document.getElementById('periodLabel').textContent = fmt(fl) + ' – ' + fmt(tl);

    buildOfferRows();
    recalcTotals();
    document.getElementById('invoiceForm').style.display = '';
    document.getElementById('invoiceForm').scrollIntoView({ behavior:'smooth', block:'start' });
}

function buildOfferRows() {
    var tbody = document.getElementById('offerRows');
    tbody.innerHTML = '';
    offerData.forEach(function(o, idx) {
        var tr = document.createElement('tr');
        tr.className = 'offer-row';
        tr.dataset.idx = idx;
        tr.innerHTML =
            '<td style="font-weight:600;font-size:14px">' + escHtml(o.offer_name) +
                '<div style="font-size:11px;color:#94A3B8;margin-top:2px">Offer ID #' + o.offer_id + '</div></td>' +
            '<td style="text-align:right;font-size:14px">' + o.conversions.toLocaleString() + '</td>' +
            '<td style="text-align:right"><input type="number" class="rate-input" value="' + o.avg_rate.toFixed(4) + '" min="0" step="0.0001" data-idx="' + idx + '" oninput="onRateChange(this)"></td>' +
            '<td style="text-align:right;font-weight:700;color:#1E293B;font-size:14px" id="rowTotal_' + idx + '">$' + o.total_payout.toFixed(2) + '</td>' +
            '<td><button type="button" class="remove-offer" onclick="removeOffer(' + idx + ')" title="Remove offer">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button></td>';
        tbody.appendChild(tr);
    });
}

function onRateChange(input) {
    var idx  = parseInt(input.dataset.idx);
    var rate = parseFloat(input.value) || 0;
    offerData[idx].avg_rate     = rate;
    offerData[idx].total_payout = rate * offerData[idx].conversions;
    document.getElementById('rowTotal_' + idx).textContent = '$' + offerData[idx].total_payout.toFixed(2);
    recalcTotals();
}

function removeOffer(idx) {
    offerData.splice(idx, 1);
    buildOfferRows();
    recalcTotals();
    if (offerData.length === 0) {
        showMsg('warning', 'All offers removed. Load offers again to start over.');
        document.getElementById('invoiceForm').style.display = 'none';
    }
}

function recalcTotals() {
    var totalConv = 0, subtotal = 0;
    offerData.forEach(function(o) { totalConv += o.conversions; subtotal += o.total_payout; });
    var taxRate = parseFloat(document.getElementById('fldTaxRate').value) || 0;
    var tax     = subtotal * taxRate / 100;
    var total   = subtotal + tax;

    document.getElementById('totalConversions').textContent = totalConv.toLocaleString();
    document.getElementById('totalPayout').textContent      = '$' + subtotal.toFixed(2);
    document.getElementById('summConv').textContent         = totalConv.toLocaleString();
    document.getElementById('summSubtotal').textContent     = '$' + subtotal.toFixed(2);
    document.getElementById('summTotal').textContent        = '$' + total.toFixed(2);

    if (taxRate > 0) {
        document.getElementById('summTaxRow').style.display = '';
        document.getElementById('summTaxLabel').textContent = 'Tax (' + taxRate + '%):';
        document.getElementById('summTax').textContent      = '$' + tax.toFixed(2);
    } else {
        document.getElementById('summTaxRow').style.display = 'none';
    }
}

function prepareSubmit() {
    if (offerData.length === 0) {
        alert('No offers to invoice. Please load offers first.');
        return false;
    }
    var container = document.getElementById('hiddenItems');
    container.innerHTML = '';
    offerData.forEach(function(o) {
        var desc = o.offer_name + ' (' + o.conversions + ' conv. @ $' + o.avg_rate.toFixed(4) + ')';
        addHidden(container, 'item_desc[]',  desc);
        addHidden(container, 'item_qty[]',   o.conversions);
        addHidden(container, 'item_rate[]',  o.avg_rate.toFixed(4));
    });
    return true;
}

function addHidden(parent, name, value) {
    var i = document.createElement('input');
    i.type  = 'hidden'; i.name = name; i.value = value;
    parent.appendChild(i);
}

function showMsg(type, msg) {
    var colors = {
        error:   { bg:'#FEF2F2', border:'#FCA5A5', color:'#B91C1C' },
        warning: { bg:'#FFFBEB', border:'#FCD34D', color:'#92400E' },
        info:    { bg:'#F5F3FF', border:'#C4B5FD', color:'#5B21B6' },
    };
    var c = colors[type] || colors.info;
    var el = document.getElementById('loadMsg');
    el.style.cssText = 'padding:10px 14px;border-radius:6px;font-size:13px;background:' + c.bg + ';border:1px solid ' + c.border + ';color:' + c.color;
    el.textContent = msg;
    el.style.display = '';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
