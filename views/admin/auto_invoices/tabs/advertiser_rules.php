<?php
/**
 * Tab 4: Advertiser Billing Rules Management
 */
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700;">Advertiser Billing &amp; Payment Terms Rules</h3>
            <p style="margin:2px 0 0 0;font-size:12.5px;color:var(--text-muted,#64748b);">
                Set custom payment terms and minimum payout thresholds per Advertiser. Invoices for an advertiser's offers will only be generated once their terms and minimum payout criteria are reached.
            </p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAdvRuleModal()" style="display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Add / Batch Advertiser Rule
        </button>
    </div>

    <div class="table-wrap">
        <table id="tbl-adv-rules" class="table">
            <thead>
                <tr>
                    <th>Advertiser</th>
                    <th>Total Offers</th>
                    <th>Payment Terms</th>
                    <th>Min Payout Threshold</th>
                    <th>Min Conversions</th>
                    <th>Frequency</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($advRules as $r): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:#1e293b;">
                            <?= htmlspecialchars(!empty($r['adv_company']) ? $r['adv_company'] : trim($r['first_name'] . ' ' . $r['last_name'])) ?>
                        </div>
                        <div style="font-size:11.5px;color:#64748b;">
                            ID: #<?= (int)$r['advertiser_id'] ?> &bull; <?= htmlspecialchars($r['email'] ?? '') ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary" style="font-size:11.5px;font-weight:700;">
                            <?= (int)($r['total_offers'] ?? 0) ?> Offers
                        </span>
                    </td>
                    <td>
                        <code style="font-size:12px;font-weight:700;color:#4f46e5;background:#eef2ff;padding:2px 6px;border-radius:4px;">
                            <?= strtoupper(htmlspecialchars($r['payment_terms'])) ?>
                        </code>
                    </td>
                    <td style="font-weight:700;color:#059669;">
                        $<?= number_format((float)$r['minimum_payout'], 2) ?> <?= htmlspecialchars($r['currency'] ?? 'USD') ?>
                    </td>
                    <td style="font-size:12.5px;">
                        <?= (int)$r['minimum_conversions'] > 0 ? (int)$r['minimum_conversions'] . ' convs' : '<span class="text-muted">None</span>' ?>
                    </td>
                    <td>
                        <span class="badge badge-info" style="font-size:11px;text-transform:capitalize;">
                            <?= str_replace('_', ' ', $r['frequency']) ?>
                        </span>
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
                        <button type="button" class="btn btn-secondary btn-sm" onclick='editAdvRule(<?= json_encode($r) ?>)'>Edit</button>
                        <form method="POST" action="/admin/auto-invoices?tab=advertiser_rules" style="display:inline" onsubmit="return confirm('Delete this advertiser rule?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="delete_advertiser_rule">
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
<div id="modalAdvRule" class="aig-modal-overlay">
    <div class="aig-modal">
        <div class="aig-modal-header">
            <h3 id="modalAdvTitle" style="margin:0;font-size:16px;font-weight:700;">Configure Advertiser Billing Rule</h3>
            <button type="button" onclick="closeAdvRuleModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="/admin/auto-invoices?tab=advertiser_rules" id="formAdvRule">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_advertiser_rule">
            <input type="hidden" name="id" id="advRuleId" value="">

            <div class="aig-modal-body">
                <div class="form-group mb-3">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Select Advertiser(s) <span style="color:#ef4444">*</span></label>
                    <select name="advertiser_ids[]" id="advRuleSelect" multiple placeholder="Select one or more advertisers...">
                        <?php foreach ($advertisers as $adv): ?>
                        <option value="<?= $adv['id'] ?>">
                            #<?= $adv['id'] ?> &ndash; <?= htmlspecialchars(!empty($adv['company']) ? $adv['company'] : $adv['name']) ?> (<?= htmlspecialchars($adv['email'] ?? '') ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Invoices for all offers owned by this advertiser will respect these payment terms and minimum threshold.</small>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Payment Terms</label>
                        <select name="payment_terms" id="advPaymentTerms" class="form-control">
                            <option value="net14">Every 14 Days / Net 14 (+14 Days)</option>
                            <option value="net15">Net 15 (+15 Days)</option>
                            <option value="net30" selected>Net 30 (+30 Days)</option>
                            <option value="net7">Net 7 (+7 Days)</option>
                            <option value="net45">Net 45 (+45 Days)</option>
                            <option value="net60">Net 60 (+60 Days)</option>
                            <option value="weekly">Weekly (+3 Days)</option>
                            <option value="biweekly">Bi-weekly (+7 Days)</option>
                            <option value="immediate">Immediate (Upon Generation)</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Minimum Payout Threshold ($)</label>
                        <input type="number" step="0.01" name="minimum_payout" id="advMinPayout" class="form-control" value="50.00" min="0">
                        <small class="text-muted">Affiliate earnings for this advertiser's offers must reach this minimum before generating invoice.</small>
                    </div>
                </div>

                <div class="grid-2" style="gap:16px;">
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Min Conversions (Optional)</label>
                        <input type="number" name="minimum_conversions" id="advMinConvs" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Billing Frequency</label>
                        <select name="frequency" id="advFrequency" class="form-control">
                            <option value="monthly">Monthly</option>
                            <option value="every_x_days">Every X Days</option>
                            <option value="weekly">Weekly</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Rule Active Status</label>
                    <label class="aig-switch">
                        <input type="checkbox" name="enabled" id="advEnabled" value="1" checked>
                        <span class="aig-slider"></span>
                    </label>
                </div>
            </div>

            <div class="aig-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAdvRuleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Advertiser Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
var advSelectInstance = null;

$(function() {
    advSelectInstance = new TomSelect('#advRuleSelect', {
        plugins: ['remove_button'],
        maxItems: 50,
        persist: false,
        create: false
    });

    $('#tbl-adv-rules').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[7, 'desc']],
        language: { 
            search: 'Search advertisers:', 
            lengthMenu: 'Show _MENU_ entries',
            emptyTable: 'No advertiser-specific billing rules configured yet. All advertisers follow standard schedules.'
        }
    });
});

function openAdvRuleModal() {
    document.getElementById('modalAdvTitle').innerText = 'Add Advertiser Billing Rule';
    document.getElementById('formAdvRule').reset();
    document.getElementById('advRuleId').value = '';
    if (advSelectInstance) advSelectInstance.clear();
    document.getElementById('modalAdvRule').style.display = 'flex';
}

function closeAdvRuleModal() {
    document.getElementById('modalAdvRule').style.display = 'none';
}

function editAdvRule(rule) {
    document.getElementById('modalAdvTitle').innerText = 'Edit Advertiser Billing Rule (Advertiser #' + rule.advertiser_id + ')';
    document.getElementById('advRuleId').value = rule.id;
    if (advSelectInstance) {
        advSelectInstance.clear();
        advSelectInstance.addItem(rule.advertiser_id);
    }
    document.getElementById('advPaymentTerms').value = rule.payment_terms || 'net30';
    document.getElementById('advMinPayout').value = rule.minimum_payout || '50.00';
    document.getElementById('advMinConvs').value = rule.minimum_conversions || 0;
    document.getElementById('advFrequency').value = rule.frequency || 'monthly';
    document.getElementById('advEnabled').checked = (rule.enabled == 1);
    document.getElementById('modalAdvRule').style.display = 'flex';
}
</script>
