<?php
/**
 * Tab 2: Affiliate Billing Rules Management
 */
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700;">Affiliate Specific Billing Rules</h3>
            <p style="margin:2px 0 0 0;font-size:12.5px;color:var(--text-muted,#64748b);">
                Individual billing rules override the Global Scheduler when "Override Global" is enabled.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAffRuleModal()" style="display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Add / Batch Affiliate Rule
        </button>
    </div>

    <div class="table-wrap">
        <table id="tbl-aff-rules" class="table">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Override Global</th>
                    <th>Offer Scope</th>
                    <th>Frequency</th>
                    <th>Schedule Details</th>
                    <th>Min Amount</th>
                    <th>Payment Terms</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($affRules as $r): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:#1e293b;">
                            <?= htmlspecialchars(trim($r['first_name'] . ' ' . $r['last_name'])) ?>
                        </div>
                        <div style="font-size:11.5px;color:#64748b;">
                            ID: #<?= (int)$r['affiliate_id'] ?> &bull; <?= htmlspecialchars($r['email']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($r['override_global'])): ?>
                            <span class="badge badge-success" style="font-size:11px;">Override ON</span>
                        <?php else: ?>
                            <span class="badge badge-muted" style="font-size:11px;">Uses Global</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($r['offer_scope'] ?? 'all') === 'specific'): ?>
                            <?php 
                            $offIds = !empty($r['specific_offers']) ? json_decode($r['specific_offers'], true) : [];
                            $cnt = is_array($offIds) ? count($offIds) : 0;
                            ?>
                            <span class="badge badge-info" style="font-size:11px;background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;">
                                Specific (<?= $cnt ?> <?= $cnt===1?'Offer':'Offers' ?>)
                            </span>
                        <?php else: ?>
                            <span class="badge badge-muted" style="font-size:11px;">All Offers</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-info" style="font-size:11px;text-transform:capitalize;">
                            <?= str_replace('_', ' ', $r['frequency']) ?>
                        </span>
                    </td>
                    <td style="font-size:12.5px;">
                        <?php if ($r['frequency'] === 'monthly'): ?>
                            <?= $r['monthly_day'] ?><?= ($r['monthly_day']==1?'st':($r['monthly_day']==2?'nd':($r['monthly_day']==3?'rd':'th'))) ?> of month
                        <?php elseif ($r['frequency'] === 'every_x_days'): ?>
                            Every <?= (int)$r['interval_days'] ?> Days
                        <?php elseif ($r['frequency'] === 'weekly'): ?>
                            Every Monday (Weekly)
                        <?php else: ?>
                            Custom Range
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;">
                        $<?= number_format((float)$r['minimum_amount'], 2) ?> <?= htmlspecialchars($r['currency']) ?>
                    </td>
                    <td>
                        <code style="font-size:12px;font-weight:700;color:#4f46e5;background:#eef2ff;padding:2px 6px;border-radius:4px;"><?= strtoupper(htmlspecialchars($r['payment_terms'])) ?></code>
                    </td>
                    <td>
                        <span class="badge badge-<?= !empty($r['enabled']) ? 'success' : 'danger' ?>">
                            <?= !empty($r['enabled']) ? 'Active' : 'Disabled' ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:#64748b;">
                        <?= date('M j, Y', strtotime($r['updated_at'])) ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick='editAffRule(<?= json_encode($r) ?>)'>Edit</button>
                        <form method="POST" action="/admin/auto-invoices?tab=affiliate_rules" style="display:inline" onsubmit="return confirm('Delete this billing rule? The affiliate will revert to the Global Scheduler.')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="delete_affiliate_rule">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="modalAffRule" class="aig-modal-overlay">
    <div class="aig-modal">
        <div class="aig-modal-header">
            <h3 id="modalAffTitle" style="margin:0;font-size:16px;font-weight:700;">Configure Affiliate Billing Rule</h3>
            <button type="button" onclick="closeAffRuleModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="/admin/auto-invoices?tab=affiliate_rules" id="formAffRule">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_affiliate_rule">
            <input type="hidden" name="id" id="affRuleId" value="">

            <div class="aig-modal-body">
                <!-- Affiliate Selector -->
                <div class="form-group mb-3" id="affSelectGroup">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Select Affiliate(s) <span style="color:#ef4444">*</span></label>
                    <select name="affiliate_ids[]" id="affRuleSelect" multiple placeholder="Select one or more affiliates...">
                        <?php foreach ($affiliates as $a): ?>
                        <option value="<?= $a['id'] ?>">
                            #<?= $a['id'] ?> &ndash; <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?> (<?= htmlspecialchars($a['email']) ?>) &ndash; Bal: $<?= number_format((float)$a['balance'],2) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">You can select multiple affiliates to apply the same billing schedule in bulk.</small>
                </div>

                <!-- Offer Scope Selection -->
                <div class="form-group mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;">
                    <label style="font-weight:700;font-size:13.5px;color:#1e293b;margin-bottom:6px;display:block;">Offer Scope (Applicable Offers)</label>
                    <div style="display:flex;gap:20px;margin-bottom:10px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;cursor:pointer;">
                            <input type="radio" name="offer_scope" value="all" id="affScopeAll" checked onchange="toggleAffOfferScope(this.value)">
                            All Offers (General Rule)
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;cursor:pointer;">
                            <input type="radio" name="offer_scope" value="specific" id="affScopeSpecific" onchange="toggleAffOfferScope(this.value)">
                            Specific Offer(s) Only
                        </label>
                    </div>

                    <div id="wrapSpecificOffers" style="display:none;margin-top:10px;">
                        <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Select Specific Offer(s)</label>
                        <select name="specific_offers[]" id="affSpecificOfferSelect" multiple placeholder="Select specific offers...">
                            <?php foreach ($offers as $o): ?>
                            <option value="<?= $o['id'] ?>">
                                #<?= $o['id'] ?> &ndash; <?= htmlspecialchars($o['name']) ?> ($<?= number_format((float)($o['payout_amount'] ?? $o['payout'] ?? 0), 2) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">This billing schedule and payment term will apply exclusively to conversions from these selected offers.</small>
                    </div>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:13.5px;color:#1e293b;">Override Global Scheduler</div>
                            <div style="font-size:12px;color:#64748b;">When enabled, this affiliate uses their personal schedule instead of the network global schedule.</div>
                        </div>
                        <label class="aig-switch">
                            <input type="checkbox" name="override_global" id="affOverrideGlobal" value="1" checked>
                            <span class="aig-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Invoice Frequency</label>
                        <select name="frequency" id="affFrequency" class="form-control" onchange="toggleAffFreq(this.value)">
                            <option value="monthly">Monthly</option>
                            <option value="every_x_days">Every X Days</option>
                            <option value="weekly">Weekly (Every Monday)</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>
                    <div class="form-group mb-3" id="wrapAffMonthlyDay">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Monthly Day</label>
                        <select name="monthly_day" id="affMonthlyDay" class="form-control">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?><?= ($d==1?'st':($d==2?'nd':($d==3?'rd':'th'))) ?> of month</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3" id="wrapAffIntervalDays" style="display:none;">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Interval Days</label>
                        <select name="interval_days" id="affIntervalDays" class="form-control">
                            <option value="7">Every 7 Days (Weekly)</option>
                            <option value="14">Every 14 Days (Bi-weekly)</option>
                            <option value="15" selected>Every 15 Days (Bi-monthly)</option>
                            <option value="30">Every 30 Days (Monthly)</option>
                            <option value="45">Every 45 Days</option>
                            <option value="60">Every 60 Days (2 Months)</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Minimum Payable Amount ($)</label>
                        <input type="number" step="0.01" name="minimum_amount" id="affMinAmount" class="form-control" value="50.00" min="0">
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Payment Terms</label>
                        <select name="payment_terms" id="affPaymentTerms" class="form-control">
                            <option value="net14">Every 14 Days (Due in +2 Days)</option>
                            <option value="net15">Net 15 (+15 Days)</option>
                            <option value="net30">Net 30 (+30 Days)</option>
                            <option value="net7">Net 7 (+7 Days)</option>
                            <option value="net45">Net 45 (+45 Days)</option>
                            <option value="net60">Net 60 (+60 Days)</option>
                            <option value="weekly">Weekly (+2 Days)</option>
                            <option value="biweekly">Bi-weekly (+2 Days)</option>
                            <option value="immediate">Immediate (Upon Generation)</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Currency</label>
                        <input type="text" name="currency" id="affCurrency" class="form-control" value="USD">
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Payment Method (Optional)</label>
                        <input type="text" name="payment_method" id="affPaymentMethod" class="form-control" placeholder="e.g. USDT TRC20, Wire, PayPal">
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Rule Active Status</label>
                    <label class="aig-switch">
                        <input type="checkbox" name="enabled" id="affEnabled" value="1" checked>
                        <span class="aig-slider"></span>
                    </label>
                </div>
            </div>

            <div class="aig-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAffRuleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Billing Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
var affSelectInstance = null;
var affSpecificOfferSelectInstance = null;

$(function() {
    affSelectInstance = new TomSelect('#affRuleSelect', {
        plugins: ['remove_button'],
        maxItems: 50,
        persist: false,
        create: false
    });

    affSpecificOfferSelectInstance = new TomSelect('#affSpecificOfferSelect', {
        plugins: ['remove_button'],
        maxItems: 50,
        persist: false,
        create: false
    });

    $('#tbl-aff-rules').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[8, 'desc']],
        language: { 
            search: 'Search rules:', 
            lengthMenu: 'Show _MENU_ entries',
            emptyTable: 'No custom affiliate billing rules configured yet. All affiliates follow the Global Scheduler.'
        }
    });
});

function toggleAffOfferScope(val) {
    document.getElementById('wrapSpecificOffers').style.display = (val === 'specific') ? 'block' : 'none';
}

function openAffRuleModal() {
    document.getElementById('modalAffTitle').innerText = 'Add Affiliate Billing Rule';
    document.getElementById('formAffRule').reset();
    document.getElementById('affRuleId').value = '';
    if (affSelectInstance) affSelectInstance.clear();
    if (affSpecificOfferSelectInstance) affSpecificOfferSelectInstance.clear();
    document.getElementById('affScopeAll').checked = true;
    toggleAffOfferScope('all');
    toggleAffFreq('monthly');
    document.getElementById('modalAffRule').style.display = 'flex';
}

function closeAffRuleModal() {
    document.getElementById('modalAffRule').style.display = 'none';
}

function toggleAffFreq(val) {
    document.getElementById('wrapAffMonthlyDay').style.display = (val === 'monthly') ? 'block' : 'none';
    document.getElementById('wrapAffIntervalDays').style.display = (val === 'every_x_days') ? 'block' : 'none';
}

function editAffRule(rule) {
    document.getElementById('modalAffTitle').innerText = 'Edit Affiliate Billing Rule (Affiliate #' + rule.affiliate_id + ')';
    document.getElementById('affRuleId').value = rule.id;
    if (affSelectInstance) {
        affSelectInstance.clear();
        affSelectInstance.addItem(rule.affiliate_id);
    }
    
    // Set offer scope
    var scope = rule.offer_scope || 'all';
    if (scope === 'specific') {
        document.getElementById('affScopeSpecific').checked = true;
        toggleAffOfferScope('specific');
        if (affSpecificOfferSelectInstance && rule.specific_offers) {
            affSpecificOfferSelectInstance.clear();
            try {
                var ids = JSON.parse(rule.specific_offers);
                if (Array.isArray(ids)) {
                    ids.forEach(function(oid){ affSpecificOfferSelectInstance.addItem(oid); });
                }
            } catch(e){}
        }
    } else {
        document.getElementById('affScopeAll').checked = true;
        toggleAffOfferScope('all');
        if (affSpecificOfferSelectInstance) affSpecificOfferSelectInstance.clear();
    }

    document.getElementById('affOverrideGlobal').checked = (rule.override_global == 1);
    document.getElementById('affFrequency').value = rule.frequency;
    toggleAffFreq(rule.frequency);
    document.getElementById('affMonthlyDay').value = rule.monthly_day;
    document.getElementById('affIntervalDays').value = rule.interval_days;
    document.getElementById('affMinAmount').value = rule.minimum_amount;
    document.getElementById('affPaymentTerms').value = rule.payment_terms;
    document.getElementById('affCurrency').value = rule.currency || 'USD';
    document.getElementById('affPaymentMethod').value = rule.payment_method || '';
    document.getElementById('affEnabled').checked = (rule.enabled == 1);

    document.getElementById('modalAffRule').style.display = 'flex';
}
</script>
