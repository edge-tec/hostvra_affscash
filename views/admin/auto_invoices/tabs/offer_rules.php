<?php
/**
 * Tab 3: Offer Billing Rules Management
 */
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700;">Offer Specific Billing Rules</h3>
            <p style="margin:2px 0 0 0;font-size:12.5px;color:var(--text-muted,#64748b);">
                Configure custom invoice frequencies and minimum volume/revenue thresholds for specific offers.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openOfferRuleModal()" style="display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Add / Batch Offer Rule
        </button>
    </div>

    <div class="table-wrap">
        <table id="tbl-offer-rules" class="table">
            <thead>
                <tr>
                    <th>Offer Name</th>
                    <th>Override Global</th>
                    <th>Frequency</th>
                    <th>Min Conversions</th>
                    <th>Min Revenue</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offerRules as $r): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:#1e293b;">
                            <?= htmlspecialchars($r['offer_name']) ?>
                        </div>
                        <div style="font-size:11.5px;color:#64748b;">
                            Offer ID: #<?= (int)$r['offer_id'] ?> &bull; Base Payout: $<?= number_format((float)$r['payout'], 2) ?> (<?= htmlspecialchars($r['payout_type']) ?>)
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
                        <div style="font-size:11.5px;color:#64748b;margin-top:2px;">
                            <?php if ($r['frequency'] === 'monthly'): ?>
                                <?= $r['monthly_day'] ?><?= ($r['monthly_day']==1?'st':($r['monthly_day']==2?'nd':($r['monthly_day']==3?'rd':'th'))) ?> of month
                            <?php elseif ($r['frequency'] === 'every_x_days'): ?>
                                Every <?= (int)$r['interval_days'] ?> Days
                            <?php else: ?>
                                Weekly
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-weight:600;">
                        <?= (int)$r['minimum_conversions'] ?> conv(s)
                    </td>
                    <td style="font-weight:700;color:#059669;">
                        $<?= number_format((float)$r['minimum_revenue'], 2) ?>
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
                        <button type="button" class="btn btn-secondary btn-sm" onclick='editOfferRule(<?= json_encode($r) ?>)'>Edit</button>
                        <form method="POST" action="/admin/auto-invoices?tab=offer_rules" style="display:inline" onsubmit="return confirm('Delete this offer billing rule?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="delete_offer_rule">
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
<div id="modalOfferRule" class="aig-modal-overlay">
    <div class="aig-modal">
        <div class="aig-modal-header">
            <h3 id="modalOfferTitle" style="margin:0;font-size:16px;font-weight:700;">Configure Offer Billing Rule</h3>
            <button type="button" onclick="closeOfferRuleModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="/admin/auto-invoices?tab=offer_rules" id="formOfferRule">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_offer_rule">
            <input type="hidden" name="id" id="offerRuleId" value="">

            <div class="aig-modal-body">
                <!-- Offer Selector -->
                <div class="form-group mb-3" id="offerSelectGroup">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Select Offer(s) <span style="color:#ef4444">*</span></label>
                    <select name="offer_ids[]" id="offerRuleSelect" multiple placeholder="Select one or more offers...">
                        <?php foreach ($offers as $o): ?>
                        <option value="<?= $o['id'] ?>">
                            #<?= $o['id'] ?> &ndash; <?= htmlspecialchars($o['name']) ?> ($<?= number_format((float)($o['payout_amount'] ?? $o['payout'] ?? 0), 2) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">You can select multiple offers to configure identical thresholds in batch.</small>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:13.5px;color:#1e293b;">Enable Offer Rule Override</div>
                            <div style="font-size:12px;color:#64748b;">Conversions for this offer will follow this specific schedule unless an affiliate rule overrides it.</div>
                        </div>
                        <label class="aig-switch">
                            <input type="checkbox" name="override_global" id="offerOverrideGlobal" value="1" checked>
                            <span class="aig-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Billing Frequency</label>
                        <select name="frequency" id="offerFrequency" class="form-control" onchange="toggleOfferFreq(this.value)">
                            <option value="monthly">Monthly</option>
                            <option value="every_x_days">Every X Days</option>
                            <option value="weekly">Weekly (Every Monday)</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="form-group mb-3" id="wrapOfferMonthlyDay">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Monthly Day</label>
                        <select name="monthly_day" id="offerMonthlyDay" class="form-control">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?><?= ($d==1?'st':($d==2?'nd':($d==3?'rd':'th'))) ?> of month</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3" id="wrapOfferIntervalDays" style="display:none;">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Interval Days</label>
                        <select name="interval_days" id="offerIntervalDays" class="form-control">
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
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Min Conversion Count</label>
                        <input type="number" name="minimum_conversions" id="offerMinConvs" class="form-control" value="1" min="1">
                        <small class="text-muted">Requires at least this many conversions to invoice.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Min Revenue Threshold ($)</label>
                        <input type="number" step="0.01" name="minimum_revenue" id="offerMinRevenue" class="form-control" value="0.00" min="0">
                        <small class="text-muted">Minimum total payout for this offer to be included.</small>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Rule Active Status</label>
                    <label class="aig-switch">
                        <input type="checkbox" name="enabled" id="offerEnabled" value="1" checked>
                        <span class="aig-slider"></span>
                    </label>
                </div>
            </div>

            <div class="aig-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeOfferRuleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Offer Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
var offerSelectInstance = null;

$(function() {
    offerSelectInstance = new TomSelect('#offerRuleSelect', {
        plugins: ['remove_button'],
        maxItems: 50,
        persist: false,
        create: false
    });

    $('#tbl-offer-rules').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[6, 'desc']],
        language: { 
            search: 'Search offers:', 
            lengthMenu: 'Show _MENU_ entries',
            emptyTable: 'No offer-specific billing rules defined yet. All offers follow Global or Affiliate schedules.'
        }
    });
});

function openOfferRuleModal() {
    document.getElementById('modalOfferTitle').innerText = 'Add Offer Billing Rule';
    document.getElementById('formOfferRule').reset();
    document.getElementById('offerRuleId').value = '';
    if (offerSelectInstance) offerSelectInstance.clear();
    toggleOfferFreq('monthly');
    document.getElementById('modalOfferRule').style.display = 'flex';
}

function closeOfferRuleModal() {
    document.getElementById('modalOfferRule').style.display = 'none';
}

function toggleOfferFreq(val) {
    document.getElementById('wrapOfferMonthlyDay').style.display = (val === 'monthly') ? 'block' : 'none';
    document.getElementById('wrapOfferIntervalDays').style.display = (val === 'every_x_days') ? 'block' : 'none';
}

function editOfferRule(rule) {
    document.getElementById('modalOfferTitle').innerText = 'Edit Offer Billing Rule (#' + rule.offer_id + ')';
    document.getElementById('offerRuleId').value = rule.id;
    if (offerSelectInstance) {
        offerSelectInstance.clear();
        offerSelectInstance.addItem(rule.offer_id);
    }
    document.getElementById('offerOverrideGlobal').checked = (rule.override_global == 1);
    document.getElementById('offerFrequency').value = rule.frequency;
    document.getElementById('offerMonthlyDay').value = rule.monthly_day;
    document.getElementById('offerIntervalDays').value = rule.interval_days;
    document.getElementById('offerMinConvs').value = rule.minimum_conversions;
    document.getElementById('offerMinRevenue').value = rule.minimum_revenue;
    document.getElementById('offerEnabled').checked = (rule.enabled == 1);

    toggleOfferFreq(rule.frequency);
    document.getElementById('modalOfferRule').style.display = 'flex';
}
</script>
