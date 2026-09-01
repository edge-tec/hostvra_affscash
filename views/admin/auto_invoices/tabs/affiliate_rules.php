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
                <?php if (empty($affRules)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding:40px;">
                        No custom affiliate billing rules configured yet. All affiliates currently follow the Global Scheduler.
                    </td>
                </tr>
                <?php endif; ?>

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
                        <code style="font-size:12px;"><?= strtoupper(htmlspecialchars($r['payment_terms'])) ?></code>
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
                            <option value="7">Every 7 Days</option>
                            <option value="15" selected>Every 15 Days</option>
                            <option value="30">Every 30 Days</option>
                            <option value="45">Every 45 Days</option>
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
                            <option value="net15">Net 15</option>
                            <option value="net30">Net 30</option>
                            <option value="net7">Net 7</option>
                            <option value="weekly">Weekly</option>
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

$(function() {
    affSelectInstance = new TomSelect('#affRuleSelect', {
        plugins: ['remove_button'],
        maxItems: 50,
        persist: false,
        create: false
    });

    $('#tbl-aff-rules').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[7, 'desc']],
        language: { search: 'Search rules:', lengthMenu: 'Show _MENU_ entries' }
    });
});

function openAffRuleModal() {
    document.getElementById('modalAffTitle').innerText = 'Add Affiliate Billing Rule';
    document.getElementById('formAffRule').reset();
    document.getElementById('affRuleId').value = '';
    if (affSelectInstance) affSelectInstance.clear();
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
    document.getElementById('modalAffTitle').innerText = 'Edit Affiliate Billing Rule (#' + rule.affiliate_id + ')';
    document.getElementById('affRuleId').value = rule.id;
    if (affSelectInstance) {
        affSelectInstance.clear();
        affSelectInstance.addItem(rule.affiliate_id);
    }
    document.getElementById('affOverrideGlobal').checked = (rule.override_global == 1);
    document.getElementById('affFrequency').value = rule.frequency;
    document.getElementById('affMonthlyDay').value = rule.monthly_day;
    document.getElementById('affIntervalDays').value = rule.interval_days;
    document.getElementById('affMinAmount').value = rule.minimum_amount;
    document.getElementById('affPaymentTerms').value = rule.payment_terms;
    document.getElementById('affCurrency').value = rule.currency || 'USD';
    document.getElementById('affPaymentMethod').value = rule.payment_method || '';
    document.getElementById('affEnabled').checked = (rule.enabled == 1);

    toggleAffFreq(rule.frequency);
    document.getElementById('modalAffRule').style.display = 'flex';
}
</script>
