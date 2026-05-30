<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Edit Smartlink</h1><p>Update smartlink settings and offer rotation</p></div>
    <a href="/admin/smartlinks" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php $validPayLabels = ['default'=>'Default offer payout','fixed'=>'Fixed amount ($)','percent'=>'Percent of offer payout (%)','skip'=>'Skip payout (no commission)']; ?>

<form method="POST" action="/admin/smartlinks?action=edit&id=<?= (int)$sl['id'] ?>">
    <?= Helpers::csrf() ?>
    <div class="grid-2">

        <!-- Left: Settings -->
        <div>
            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Smartlink Settings</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Smartlink Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= Helpers::e($_POST['name'] ?? $sl['name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>URL Slug *</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="color:var(--text-muted);font-size:13px;white-space:nowrap"><?= Helpers::e($appUrl) ?>/smartlink/</span>
                            <input type="text" name="slug" class="form-control" required value="<?= Helpers::e($_POST['slug'] ?? $sl['slug']) ?>" placeholder="my-smartlink">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Rotation Type</label>
                        <?php $currentRotation = $_POST['rotation_type'] ?? $sl['rotation_type']; ?>
                        <select name="rotation_type" class="form-control">
                            <option value="weight"<?= $currentRotation==='weight'?' selected':'' ?>>Weighted Random</option>
                            <option value="round_robin"<?= $currentRotation==='round_robin'?' selected':'' ?>>Round Robin</option>
                            <option value="geo"<?= $currentRotation==='geo'?' selected':'' ?>>Geo-Based</option>
                            <option value="device"<?= $currentRotation==='device'?' selected':'' ?>>Device-Based</option>
                        </select>
                        <div class="form-hint">When per-offer GEO/Device targeting is configured below, the tracker always routes to the best-matching offer first.</div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <?php $currentStatus = $_POST['status'] ?? $sl['status']; ?>
                        <select name="status" class="form-control">
                            <option value="active"<?= $currentStatus==='active'?' selected':'' ?>>Active</option>
                            <option value="paused"<?= $currentStatus==='paused'?' selected':'' ?>>Paused</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= Helpers::e($_POST['description'] ?? $sl['description']) ?></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal">
                            <?php $reqApproval = isset($_POST['require_approval']) ? (bool)$_POST['require_approval'] : (bool)($sl['require_approval'] ?? 0); ?>
                            <input type="checkbox" name="require_approval" value="1" <?= $reqApproval ? 'checked' : '' ?> style="width:16px;height:16px">
                            <span>Require admin approval for affiliate access</span>
                        </label>
                        <div class="form-hint">If unchecked, affiliates who click "Request Access" will be auto-approved immediately.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Offers -->
        <div>
            <div class="card mb-2">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <span class="card-title">Offers in Rotation</span>
                        <span class="badge badge-info" style="margin-left:6px;font-size:10px">Required</span>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addSlRow()">+ Add Offer</button>
                        <button type="button" class="btn btn-sm" onclick="addCustomUrlRow()" style="background:#7C3AED;color:#fff;border:none">+ Custom URL</button>
                    </div>
                </div>
                <div class="card-body" style="padding-bottom:8px">
                    <div id="slOfferRows">
                    <?php foreach ($smartlinkOffers as $so):
                        // Decode stored rules
                        $geoArr    = !empty($so['geo_rules'])    ? (json_decode($so['geo_rules'],    true) ?? []) : [];
                        $devArr    = !empty($so['device_rules']) ? (json_decode($so['device_rules'], true) ?? []) : [];
                        $geoRaw    = implode(', ', $geoArr);
                        $devRaw    = implode(',', $devArr);
                        $payType   = in_array($so['payout_type'] ?? 'default', array_keys($validPayLabels)) ? $so['payout_type'] : 'default';
                        $payVal    = (isset($so['payout_value']) && $so['payout_value'] !== null) ? number_format((float)$so['payout_value'], 2, '.', '') : '';
                        $payNeedsVal = in_array($payType, ['fixed', 'percent']);
                        $directUrl = trim($so['direct_url'] ?? '');
                        $isCustom  = empty($so['offer_id']);
                        $hasSettings = !$isCustom && ($geoRaw || !empty($devArr) || $payType !== 'default' || $directUrl !== '');
                    ?>
                    <div class="sl-row<?= $isCustom ? ' sl-row-custom' : '' ?>">
                        <div style="display:grid;grid-template-columns:1fr 80px auto 32px;gap:8px;align-items:center;padding:10px 12px">
                            <?php if ($isCustom): ?>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="background:#7C3AED;color:#fff;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0">CUSTOM URL</span>
                                <input type="hidden" name="offer_ids[]" value="0">
                                <input type="url" name="so_direct_url[]" class="form-control" value="<?= Helpers::e($directUrl) ?>" placeholder="https://advertiser.com/lp?click_id={click_id}" required style="flex:1">
                            </div>
                            <?php else: ?>
                            <select name="offer_ids[]" class="form-control">
                                <option value="">— Select offer —</option>
                                <?php foreach($activeOffers as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= $o['id'] == $so['offer_id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?> ($<?= number_format($o['payout_amount'],2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <input type="number" name="weights[]" class="form-control" value="<?= (int)$so['weight'] ?>" min="1" max="1000" title="Traffic weight">
                            <button type="button" onclick="toggleSlSettings(this)" class="btn btn-sm btn-secondary sl-cfg-btn" style="white-space:nowrap;font-size:11px;padding:4px 9px">
                                <?= ($hasSettings || ($isCustom && ($geoRaw || !empty($devArr) || $payType !== 'default'))) ? '&#9650; Hide' : '&#9881; Config' ?>
                            </button>
                            <button type="button" onclick="this.closest('.sl-row').remove()" class="btn btn-danger btn-sm btn-icon" title="Remove">&#10005;</button>
                        </div>
                        <div class="sl-settings" style="<?= ($hasSettings || ($isCustom && ($geoRaw || !empty($devArr) || $payType !== 'default'))) ? '' : 'display:none;' ?>border-top:1px solid #E2E8F0;padding:12px;background:<?= $isCustom ? '#FAFAFF' : '#fff' ?>">
                            <?php if (!$isCustom): ?>
                            <!-- Direct Offer URL (for regular offer rows only) -->
                            <div class="form-group" style="margin-bottom:12px">
                                <label style="font-size:11px;font-weight:600;color:#374151">Direct Offer Page URL <span style="font-weight:400;color:#94A3B8">(optional)</span></label>
                                <input type="url" name="so_direct_url[]" class="form-control landing-url-input"
                                       value="<?= Helpers::e($directUrl) ?>"
                                       placeholder="https://advertiser.com/lp?click_id={click_id}">
                                <div class="form-hint" style="margin-top:3px">Overrides the offer's default URL for traffic through this smartlink.</div>
                            </div>
                            <?php endif; ?>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                                <!-- GEO -->
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600;color:#374151">GEO Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>
                                    <input type="text" name="so_geos[]" class="form-control" value="<?= Helpers::e($geoRaw) ?>" placeholder="US, GB, CA, AU, IN..." style="font-size:13px">
                                    <div class="form-hint" style="margin-top:3px">Comma-separated 2-letter country codes</div>
                                </div>
                                <!-- Device -->
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600;color:#374151">Device Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                                        <?php foreach(['desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $dv=>$dvLbl): ?>
                                        <label class="sl-pill<?= in_array($dv, $devArr) ? ' sl-pill-active' : '' ?>">
                                            <input type="checkbox" class="sl-dev-cb" value="<?= $dv ?>" onchange="syncSlDev(this)" <?= in_array($dv, $devArr) ? 'checked' : '' ?>>
                                            <?= $dvLbl ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="so_devices[]" class="sl-dev-val" value="<?= Helpers::e($devRaw) ?>">
                                </div>
                            </div>
                            <!-- Payout -->
                            <div class="form-group mb-0">
                                <label style="font-size:11px;font-weight:600;color:#374151">Payout Override</label>
                                <div style="display:flex;gap:8px;align-items:center;margin-top:4px;flex-wrap:wrap">
                                    <select name="so_payout_type[]" class="form-control" style="max-width:240px" onchange="toggleSlPayVal(this)">
                                        <?php foreach($validPayLabels as $pt=>$ptLbl): ?>
                                        <option value="<?= $pt ?>" <?= $payType===$pt?'selected':'' ?>><?= $ptLbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="so_payout_value[]" class="form-control sl-pay-val" step="0.01" min="0" placeholder="0.00"
                                           style="max-width:90px;<?= $payNeedsVal ? '' : 'display:none' ?>"
                                           value="<?= $payNeedsVal && $payVal !== '' ? Helpers::e($payVal) : '' ?>">
                                    <span class="sl-pay-unit" style="<?= $payNeedsVal ? '' : 'display:none;' ?>font-size:13px;font-weight:700;color:var(--text-muted)">
                                        <?= $payType==='percent' ? '%' : ($payType==='fixed' ? '$' : '') ?>
                                    </span>
                                </div>
                                <div class="form-hint" style="margin-top:4px">Overrides the offer's default payout for traffic routed through this smartlink.</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <div class="form-hint" style="padding-top:4px">
                        <strong>Weight</strong> controls traffic share (higher = more traffic).
                        <strong>GEO + Device</strong> targeting auto-routes to the best-matching offer.
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Save Changes</button>
        </div>
    </div>
</form>

<!-- Delete — outside main form -->
<div style="margin-top:16px">
    <form method="POST" action="/admin/smartlinks?action=delete" onsubmit="return confirm('Delete this smartlink? This cannot be undone.')">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="id" value="<?= (int)$sl['id'] ?>">
        <button type="submit" class="btn btn-danger">Delete Smartlink</button>
    </form>
</div>

<style>
.sl-row {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
}
.sl-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    user-select: none;
    transition: border-color 0.1s, background 0.1s, color 0.1s;
}
.sl-pill input[type=checkbox] { cursor: pointer; accent-color: #6366F1; }
.sl-pill-active {
    background: #EEF2FF;
    border-color: #6366F1;
    color: #4338CA;
}
</style>

<script>
const slOfferOptions = `<option value="">— Select offer —</option><?php foreach($activeOffers as $o): ?><option value="<?= $o['id'] ?>"><?= addslashes(htmlspecialchars($o['name'],ENT_QUOTES)) ?> ($<?= number_format($o['payout_amount'],2) ?>)</option><?php endforeach; ?>`;

function addSlRow() {
    var c = document.getElementById('slOfferRows');
    var d = document.createElement('div');
    d.className = 'sl-row';
    d.innerHTML =
        '<div style="display:grid;grid-template-columns:1fr 80px auto 32px;gap:8px;align-items:center;padding:10px 12px">' +
            '<select name="offer_ids[]" class="form-control">' + slOfferOptions + '</select>' +
            '<input type="number" name="weights[]" class="form-control" value="10" min="1" max="1000" title="Traffic weight">' +
            '<button type="button" onclick="toggleSlSettings(this)" class="btn btn-sm btn-secondary sl-cfg-btn" style="white-space:nowrap;font-size:11px;padding:4px 9px">&#9881; Config</button>' +
            '<button type="button" onclick="this.closest(\'.sl-row\').remove()" class="btn btn-danger btn-sm btn-icon" title="Remove">&#10005;</button>' +
        '</div>' +
        '<div class="sl-settings" style="display:none;border-top:1px solid #E2E8F0;padding:12px;background:#fff">' +
            '<div class="form-group" style="margin-bottom:12px">' +
                '<label style="font-size:11px;font-weight:600;color:#374151">Direct Offer Page URL <span style="font-weight:400;color:#94A3B8">(optional)</span></label>' +
                '<input type="url" name="so_direct_url[]" class="form-control landing-url-input" placeholder="https://advertiser.com/lp?click_id={click_id}">' +
                '<div class="form-hint" style="margin-top:3px">Overrides the offer\'s default URL for traffic through this smartlink. Leave empty to use the offer\'s URL.</div>' +
            '</div>' +
            '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">' +
                '<div class="form-group mb-0">' +
                    '<label style="font-size:11px;font-weight:600;color:#374151">GEO Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>' +
                    '<input type="text" name="so_geos[]" class="form-control" placeholder="US, GB, CA, AU, IN..." style="font-size:13px">' +
                    '<div class="form-hint" style="margin-top:3px">Comma-separated 2-letter country codes</div>' +
                '</div>' +
                '<div class="form-group mb-0">' +
                    '<label style="font-size:11px;font-weight:600;color:#374151">Device Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>' +
                    '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">' +
                        '<label class="sl-pill"><input type="checkbox" class="sl-dev-cb" value="desktop" onchange="syncSlDev(this)"> Desktop</label>' +
                        '<label class="sl-pill"><input type="checkbox" class="sl-dev-cb" value="mobile" onchange="syncSlDev(this)"> Mobile</label>' +
                        '<label class="sl-pill"><input type="checkbox" class="sl-dev-cb" value="tablet" onchange="syncSlDev(this)"> Tablet</label>' +
                    '</div>' +
                    '<input type="hidden" name="so_devices[]" class="sl-dev-val">' +
                '</div>' +
            '</div>' +
            '<div class="form-group mb-0">' +
                '<label style="font-size:11px;font-weight:600;color:#374151">Payout Override</label>' +
                '<div style="display:flex;gap:8px;align-items:center;margin-top:4px;flex-wrap:wrap">' +
                    '<select name="so_payout_type[]" class="form-control" style="max-width:240px" onchange="toggleSlPayVal(this)">' +
                        '<option value="default">Default offer payout</option>' +
                        '<option value="fixed">Fixed amount ($)</option>' +
                        '<option value="percent">Percent of offer payout (%)</option>' +
                        '<option value="skip">Skip payout (no commission)</option>' +
                    '</select>' +
                    '<input type="number" name="so_payout_value[]" class="form-control sl-pay-val" step="0.01" min="0" placeholder="0.00" style="max-width:90px;display:none">' +
                    '<span class="sl-pay-unit" style="display:none;font-size:13px;font-weight:700;color:var(--text-muted)"></span>' +
                '</div>' +
                '<div class="form-hint" style="margin-top:4px">Overrides the offer\'s default payout for traffic routed through this smartlink.</div>' +
            '</div>' +
        '</div>';
    c.appendChild(d);
    d.querySelector('select[name="offer_ids[]"]').focus();
}

function toggleSlSettings(btn) {
    var panel = btn.closest('.sl-row').querySelector('.sl-settings');
    var open  = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : '';
    btn.innerHTML = open ? '&#9881; Config' : '&#9650; Hide';
}

function syncSlDev(cb) {
    var row  = cb.closest('.sl-row');
    var vals = Array.from(row.querySelectorAll('.sl-dev-cb:checked')).map(c => c.value);
    row.querySelector('.sl-dev-val').value = vals.join(',');
    cb.closest('.sl-pill').classList.toggle('sl-pill-active', cb.checked);
}

function toggleSlPayVal(sel) {
    var row  = sel.closest('.sl-row');
    var inp  = row.querySelector('.sl-pay-val');
    var unit = row.querySelector('.sl-pay-unit');
    var show = sel.value === 'fixed' || sel.value === 'percent';
    inp.style.display  = show ? '' : 'none';
    unit.style.display = show ? '' : 'none';
    unit.textContent   = sel.value === 'percent' ? '%' : (sel.value === 'fixed' ? '$' : '');
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
