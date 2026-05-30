<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Create Smartlink</h1><p>Set up a rotating link across multiple offers</p></div>
    <a href="/admin/smartlinks" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php
// Rebuild rows from POST on validation error, or start with one empty row
$_slRows = [];
if (!empty($_POST['offer_ids'])) {
    foreach ($_POST['offer_ids'] as $i => $oid) {
        $directUrlRaw = trim($_POST['so_direct_url'][$i] ?? '');
        if (!(int)$oid && !$directUrlRaw) continue;
        $devRaw = trim($_POST['so_devices'][$i] ?? '');
        $devArr = $devRaw ? array_values(array_filter(array_map('trim', explode(',', $devRaw)))) : [];
        $_slRows[] = [
            'offer_id'   => (int)$oid,
            'is_custom'  => !(int)$oid,
            'weight'     => max(1, (int)($_POST['weights'][$i] ?? 10)),
            'geo_raw'    => trim($_POST['so_geos'][$i] ?? ''),
            'dev_arr'    => $devArr,
            'dev_raw'    => $devRaw,
            'pay_type'   => $_POST['so_payout_type'][$i] ?? 'default',
            'pay_val'    => $_POST['so_payout_value'][$i] ?? '',
            'direct_url' => $directUrlRaw,
        ];
    }
}
if (empty($_slRows)) {
    $_slRows = [['offer_id'=>0,'is_custom'=>false,'weight'=>10,'geo_raw'=>'','dev_arr'=>[],'dev_raw'=>'','pay_type'=>'default','pay_val'=>'','direct_url'=>'']];
}
$_validPay = ['default', 'fixed', 'percent', 'skip'];
?>

<form method="POST">
    <?= Helpers::csrf() ?>
    <div class="grid-2">

        <!-- Left: Smartlink settings -->
        <div>
            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Smartlink Settings</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Smartlink Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= Helpers::e($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>URL Slug *</label>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="color:var(--text-muted);font-size:13px;white-space:nowrap"><?= Helpers::e($appUrl) ?>/smartlink/</span>
                            <input type="text" name="slug" class="form-control" required value="<?= Helpers::e($_POST['slug'] ?? '') ?>" placeholder="my-smartlink">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Rotation Type</label>
                        <?php $rt = $_POST['rotation_type'] ?? 'weight'; ?>
                        <select name="rotation_type" class="form-control">
                            <option value="weight"<?= $rt==='weight'?' selected':'' ?>>Weighted Random</option>
                            <option value="round_robin"<?= $rt==='round_robin'?' selected':'' ?>>Round Robin</option>
                            <option value="geo"<?= $rt==='geo'?' selected':'' ?>>Geo-Based</option>
                            <option value="device"<?= $rt==='device'?' selected':'' ?>>Device-Based</option>
                        </select>
                        <div class="form-hint">When per-offer GEO/Device targeting is configured below, the tracker always routes to the best-matching offer first, regardless of rotation type.</div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= Helpers::e($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal">
                            <input type="checkbox" name="require_approval" value="1" <?= isset($_POST['require_approval']) ? 'checked' : '' ?> style="width:16px;height:16px">
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
                    <?php foreach ($_slRows as $row):
                        $payType     = in_array($row['pay_type'] ?? 'default', $_validPay) ? $row['pay_type'] : 'default';
                        $payNeedsVal = in_array($payType, ['fixed', 'percent']);
                        $isCustom    = !empty($row['is_custom']);
                        $hasSettings = $row['geo_raw'] || !empty($row['dev_arr']) || $payType !== 'default' || (!$isCustom && !empty($row['direct_url']));
                        $payLabels   = ['default'=>'Default offer payout','fixed'=>'Fixed amount ($)','percent'=>'Percent of offer payout (%)','skip'=>'Skip payout (no commission)'];
                    ?>
                    <div class="sl-row<?= $isCustom ? ' sl-row-custom' : '' ?>">
                        <div style="display:grid;grid-template-columns:1fr 80px auto 32px;gap:8px;align-items:center;padding:10px 12px">
                            <?php if ($isCustom): ?>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="background:#7C3AED;color:#fff;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0">CUSTOM URL</span>
                                <input type="hidden" name="offer_ids[]" value="0">
                                <input type="url" name="so_direct_url[]" class="form-control" value="<?= Helpers::e($row['direct_url'] ?? '') ?>" placeholder="https://advertiser.com/lp?click_id={click_id}" required style="flex:1">
                            </div>
                            <?php else: ?>
                            <select name="offer_ids[]" class="form-control">
                                <option value="">— Select offer —</option>
                                <?php foreach($activeOffers as $o): ?>
                                <option value="<?= $o['id'] ?>" <?= $o['id'] == $row['offer_id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?> ($<?= number_format($o['payout_amount'],2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <input type="number" name="weights[]" class="form-control" value="<?= (int)$row['weight'] ?>" min="1" max="1000" title="Traffic weight — higher = more traffic">
                            <button type="button" onclick="toggleSlSettings(this)" class="btn btn-sm btn-secondary sl-cfg-btn" style="white-space:nowrap;font-size:11px;padding:4px 9px">
                                <?= $hasSettings ? '&#9650; Hide' : '&#9881; Config' ?>
                            </button>
                            <button type="button" onclick="this.closest('.sl-row').remove()" class="btn btn-danger btn-sm btn-icon" title="Remove">&#10005;</button>
                        </div>
                        <div class="sl-settings" style="<?= $hasSettings ? '' : 'display:none;' ?>border-top:1px solid #E2E8F0;padding:12px;background:#fff">
                            <?php if (!$isCustom): ?>
                            <!-- Direct Offer URL (for regular offer rows only) -->
                            <div class="form-group" style="margin-bottom:12px">
                                <label style="font-size:11px;font-weight:600;color:#374151">Direct Offer Page URL <span style="font-weight:400;color:#94A3B8">(optional)</span></label>
                                <input type="url" name="so_direct_url[]" class="form-control landing-url-input"
                                       value="<?= Helpers::e($row['direct_url'] ?? '') ?>"
                                       placeholder="https://advertiser.com/lp?click_id={click_id}">
                                <div class="form-hint" style="margin-top:3px">Overrides the offer's default URL for traffic through this smartlink. Leave empty to use the offer's URL.</div>
                            </div>
                            <?php endif; ?>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                                <!-- GEO -->
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600;color:#374151">GEO Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>
                                    <input type="text" name="so_geos[]" class="form-control" value="<?= Helpers::e($row['geo_raw']) ?>" placeholder="US, GB, CA, AU, IN..." style="font-size:13px">
                                    <div class="form-hint" style="margin-top:3px">Comma-separated 2-letter country codes</div>
                                </div>
                                <!-- Device -->
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600;color:#374151">Device Targeting <span style="font-weight:400;color:#94A3B8">(empty&nbsp;=&nbsp;all)</span></label>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                                        <?php foreach(['desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $dv=>$dvLbl): ?>
                                        <label class="sl-pill<?= in_array($dv, $row['dev_arr']) ? ' sl-pill-active' : '' ?>">
                                            <input type="checkbox" class="sl-dev-cb" value="<?= $dv ?>" onchange="syncSlDev(this)" <?= in_array($dv, $row['dev_arr']) ? 'checked' : '' ?>>
                                            <?= $dvLbl ?>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="so_devices[]" class="sl-dev-val" value="<?= Helpers::e($row['dev_raw']) ?>">
                                </div>
                            </div>
                            <!-- Payout -->
                            <div class="form-group mb-0">
                                <label style="font-size:11px;font-weight:600;color:#374151">Payout Override</label>
                                <div style="display:flex;gap:8px;align-items:center;margin-top:4px;flex-wrap:wrap">
                                    <select name="so_payout_type[]" class="form-control" style="max-width:240px" onchange="toggleSlPayVal(this)">
                                        <?php foreach($payLabels as $pt=>$ptLbl): ?>
                                        <option value="<?= $pt ?>" <?= $payType===$pt?'selected':'' ?>><?= $ptLbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="so_payout_value[]" class="form-control sl-pay-val" step="0.01" min="0" placeholder="0.00"
                                           style="max-width:90px;<?= $payNeedsVal ? '' : 'display:none' ?>"
                                           value="<?= $payNeedsVal && $row['pay_val'] !== '' ? Helpers::e($row['pay_val']) : '' ?>">
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
            <button type="submit" class="btn btn-primary" style="width:100%">Create Smartlink</button>
        </div>
    </div>
</form>

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
.sl-row-custom {
    border-color: #7C3AED;
    background: #FAFAFF;
}
</style>

<script>
// PHP-generated offer options for dynamically added rows
const slOfferOptions = `<option value="">— Select offer —</option><?php foreach($activeOffers as $o): ?><option value="<?= $o['id'] ?>"><?= addslashes(htmlspecialchars($o['name'],ENT_QUOTES)) ?> ($<?= number_format($o['payout_amount'],2) ?>)</option><?php endforeach; ?>`;

const slSettingsPanelHtml =
    '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">' +
        '<div class="form-group mb-0">' +
            '<label style="font-size:11px;font-weight:600;color:#374151">GEO Targeting <span style="font-weight:400;color:#94A3B8">(empty = all)</span></label>' +
            '<input type="text" name="so_geos[]" class="form-control" placeholder="US, GB, CA, AU, IN..." style="font-size:13px">' +
        '</div>' +
        '<div class="form-group mb-0">' +
            '<label style="font-size:11px;font-weight:600;color:#374151">Device Targeting <span style="font-weight:400;color:#94A3B8">(empty = all)</span></label>' +
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
    '</div>';

function addCustomUrlRow() {
    var c = document.getElementById('slOfferRows');
    var d = document.createElement('div');
    d.className = 'sl-row sl-row-custom';
    d.innerHTML =
        '<div style="display:grid;grid-template-columns:1fr 80px auto 32px;gap:8px;align-items:center;padding:10px 12px">' +
            '<div style="display:flex;align-items:center;gap:8px">' +
                '<span style="background:#7C3AED;color:#fff;padding:2px 8px;border-radius:4px;font-size:10px;font-weight:700;white-space:nowrap;flex-shrink:0">CUSTOM URL</span>' +
                '<input type="hidden" name="offer_ids[]" value="0">' +
                '<input type="url" name="so_direct_url[]" class="form-control" placeholder="https://advertiser.com/lp?click_id={click_id}" required style="flex:1">' +
            '</div>' +
            '<input type="number" name="weights[]" class="form-control" value="10" min="1" max="1000" title="Traffic weight">' +
            '<button type="button" onclick="toggleSlSettings(this)" class="btn btn-sm btn-secondary sl-cfg-btn" style="white-space:nowrap;font-size:11px;padding:4px 9px">&#9881; Config</button>' +
            '<button type="button" onclick="this.closest(\'.sl-row\').remove()" class="btn btn-danger btn-sm btn-icon" title="Remove">&#10005;</button>' +
        '</div>' +
        '<div class="sl-settings" style="display:none;border-top:1px solid #E2E8F0;padding:12px;background:#FAFAFF">' +
            slSettingsPanelHtml +
        '</div>';
    c.appendChild(d);
    d.querySelector('input[name="so_direct_url[]"]').focus();
}

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

// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('input', function() {
    var slugInput = document.querySelector('input[name="slug"]');
    if (!slugInput._touched) {
        slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.querySelector('input[name="slug"]').addEventListener('input', function() {
    this._touched = true;
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
