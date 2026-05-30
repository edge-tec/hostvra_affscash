<?php
$isEdit = isset($offer) && $offer;
$pageTitle = $isEdit ? 'Edit In-House Offer' : 'Create In-House Offer';
require BASE_PATH . '/views/layouts/admin.php';

// Helper to read value from $offer or $_POST fallback
$v = function(string $key, $default = '') use ($offer) {
    return $_POST[$key] ?? $offer[$key] ?? $default;
};
$geos    = $offer ? (json_decode($offer['geo_targeting'] ?? 'null', true) ?: []) : [];
$devices = $offer ? (json_decode($offer['device_targeting'] ?? 'null', true) ?: []) : [];
$appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
?>

<div class="page-header">
    <div>
        <h1><?= $isEdit ? '✎ Edit In-House Offer' : '+ Create In-House Offer' ?></h1>
        <p><?= $isEdit ? 'Update offer settings, targeting, and status' : 'Set up a new in-house offer — no external advertiser needed' ?></p>
    </div>
    <a href="/admin/inhouse-offers" class="btn btn-secondary">← Back to In-House Offers</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? '/admin/inhouse-offers/'.$offer['id'].'/edit' : '/admin/inhouse-offers/create' ?>">
    <?= Helpers::csrf() ?>

    <div class="grid-2" style="gap:20px;align-items:start">

        <!-- LEFT COLUMN -->
        <div>

            <!-- Basic Info -->
            <div class="card mb-3">
                <div class="card-header" style="background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:8px 8px 0 0">
                    <span class="card-title" style="color:#fff">🏠 Basic Information</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Offer Name <span style="color:#EF4444">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= Helpers::e($v('name')) ?>" placeholder="e.g. Summer Sale CPA">
                    </div>
                    <div class="form-group">
                        <label>Landing Page URLs <span style="color:#EF4444">*</span> <span style="font-weight:400;color:#94A3B8;font-size:12px">— multiple URLs rotate randomly per click</span></label>
                        <div id="ih-landing-pages-container">
                        <?php
                            $ihExistingUrls = [];
                            if (!empty($offer['landing_pages'])) {
                                $ihExistingUrls = json_decode($offer['landing_pages'], true) ?: [];
                            }
                            if (empty($ihExistingUrls) && !empty($offer['offer_url'])) {
                                $ihExistingUrls = [$offer['offer_url']];
                            }
                            if (empty($ihExistingUrls)) {
                                $ihExistingUrls = [''];
                            }
                            foreach ($ihExistingUrls as $ihI => $ihUrl):
                        ?>
                        <div class="ih-lp-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                            <input type="url" name="landing_pages[]" class="form-control"
                                   placeholder="https://advertiser.com/lp<?= $ihI+1 ?>?cid={click_id}"
                                   value="<?= Helpers::e($ihUrl) ?>"
                                   <?= $ihI === 0 ? 'required' : '' ?>>
                            <?php if ($ihI > 0): ?>
                            <button type="button" onclick="this.closest('.ih-lp-row').remove()"
                                    style="background:#FEE2E2;color:#DC2626;border:1px solid #FECACA;border-radius:6px;padding:0 10px;height:36px;font-size:13px;cursor:pointer;white-space:nowrap;flex-shrink:0">&#10005; Remove</button>
                            <?php else: ?>
                            <div style="width:84px;flex-shrink:0"></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="ihAddLandingPage()" class="btn btn-secondary btn-sm">+ Add URL</button>
                        <div class="form-hint">First URL is primary. Additional URLs rotate randomly on each click.</div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Describe the offer for affiliates…"><?= Helpers::e($v('description')) ?></textarea>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Offer Type</label>
                            <select name="offer_type" class="form-control">
                                <option value="">— Select Type —</option>
                                <?php foreach (['DOI','SOI','CPL','CPS','CPI','CPA','COD','FINANCE','CPM','CPC','REVSHARE','TRIAL'] as $_ot): ?>
                                <option value="<?= $_ot ?>" <?= $v('offer_type') === $_ot ? 'selected' : '' ?>><?= $_ot ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control"
                                   value="<?= Helpers::e($v('category')) ?>" placeholder="e.g. Finance, Dating">
                        </div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Visibility</label>
                            <select name="visibility" class="form-control">
                                <?php foreach (['public'=>'Public (all affiliates)','private'=>'Private (approved only)','require_approval'=>'Require Approval'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $v('visibility','public') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div></div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="require_approval" value="1" <?= $v('require_approval') ? 'checked' : '' ?>>
                            Require affiliate approval to access this offer
                        </label>
                    </div>
                </div>
            </div>

            <!-- Payout -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">💰 Payout Settings</span></div>
                <div class="card-body">
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Payout Type</label>
                            <select name="payout_type" class="form-control">
                                <?php foreach (['CPA'=>'CPA — Cost per Action','CPC'=>'CPC — Cost per Click','CPL'=>'CPL — Cost per Lead','RevShare'=>'RevShare — Revenue Share'] as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $v('payout_type','CPA') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Affiliate Payout ($)</label>
                            <input type="number" name="payout_amount" class="form-control"
                                   step="0.0001" min="0" value="<?= Helpers::e($v('payout_amount', '0')) ?>">
                            <div class="form-hint">Amount paid to affiliate per conversion</div>
                        </div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Revenue / Cost ($)</label>
                            <input type="number" name="revenue_amount" class="form-control"
                                   step="0.0001" min="0" value="<?= Helpers::e($v('revenue_amount', '0')) ?>">
                            <div class="form-hint">Your actual revenue per conversion</div>
                        </div>
                        <div></div>
                    </div>
                </div>
            </div>

            <!-- Caps -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">🔢 Conversion Caps</span></div>
                <div class="card-body">
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Daily Cap</label>
                            <input type="number" name="daily_cap" class="form-control" min="0"
                                   value="<?= Helpers::e($v('daily_cap', '0')) ?>" placeholder="0 = unlimited">
                        </div>
                        <div class="form-group">
                            <label>Total Cap</label>
                            <input type="number" name="total_cap" class="form-control" min="0"
                                   value="<?= Helpers::e($v('total_cap', '0')) ?>" placeholder="0 = unlimited">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div>

            <!-- Status -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">⚙️ Status & Control</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Offer Status</label>
                        <select name="status" class="form-control">
                            <option value="active"  <?= $v('status','active') === 'active'  ? 'selected' : '' ?>>✅ Active</option>
                            <option value="paused"  <?= $v('status','active') === 'paused'  ? 'selected' : '' ?>>⏸ Paused</option>
                            <option value="pending" <?= $v('status','active') === 'pending' ? 'selected' : '' ?>>🕐 Pending</option>
                        </select>
                    </div>

                    <?php if ($isEdit): ?>
                    <!-- Tracking Link Preview -->
                    <div style="background:#F0F4FF;border:1px solid #C7D2FE;border-radius:8px;padding:12px 14px;margin-top:4px">
                        <div style="font-size:11px;font-weight:700;color:#4338CA;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px">Tracking Link Format</div>
                        <div style="font-family:monospace;font-size:11px;color:#334155;word-break:break-all;background:#fff;border-radius:4px;padding:6px 8px;border:1px solid #C7D2FE">
                            <?= Helpers::e(Helpers::trackingUrl() . '/click/' . $offer['id'] . '?aff={AFFILIATE_CODE}') ?>
                        </div>
                        <div style="font-size:11px;color:#64748B;margin-top:6px">Replace <code>{AFFILIATE_CODE}</code> with the affiliate's code</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Geo Targeting -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">🌍 Geo Targeting</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-size:12px;color:#64748B">Select countries (leave empty for global)</label>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px" id="geoTagsWrap">
                            <?php foreach ($geos as $geo): ?>
                            <span class="geo-tag" style="display:inline-flex;align-items:center;gap:4px;background:#EDE9FE;color:#5B21B6;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600">
                                <?= Helpers::flag($geo) ?> <?= Helpers::e(strtoupper($geo)) ?>
                                <button type="button" onclick="removeGeoTag(this)" style="background:none;border:none;color:#7C3AED;cursor:pointer;padding:0;font-size:13px;line-height:1">&times;</button>
                                <input type="hidden" name="geo_targeting[]" value="<?= Helpers::e($geo) ?>">
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;gap:6px">
                            <input type="text" id="geoInput" class="form-control" placeholder="Add country code (e.g. US, GB, BD)"
                                   maxlength="2" style="text-transform:uppercase;max-width:160px" oninput="this.value=this.value.toUpperCase()">
                            <button type="button" onclick="addGeoTag()" class="btn btn-secondary btn-sm">+ Add</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Targeting -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">📱 Device Targeting</span></div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <?php foreach (['desktop'=>'🖥 Desktop','mobile'=>'📱 Mobile','tablet'=>'💻 Tablet'] as $dv => $dlabel): ?>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                            <input type="checkbox" name="device_targeting[]" value="<?= $dv ?>"
                                   <?= in_array($dv, $devices) ? 'checked' : '' ?>>
                            <?= $dlabel ?>
                        </label>
                        <?php endforeach; ?>
                        <div style="font-size:11px;color:#94A3B8;margin-top:4px">Leave all unchecked to allow all devices</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Submit -->
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px;padding-bottom:20px">
        <a href="/admin/inhouse-offers" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" style="min-width:160px;font-size:15px">
            <?= $isEdit ? '💾 Save Changes' : '🚀 Create Offer' ?>
        </button>
    </div>
</form>

<script>
function ihAddLandingPage() {
    var container = document.getElementById('ih-landing-pages-container');
    var count = container.querySelectorAll('.ih-lp-row').length + 1;
    var div = document.createElement('div');
    div.className = 'ih-lp-row';
    div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';
    div.innerHTML =
        '<input type="url" name="landing_pages[]" class="form-control"' +
        ' placeholder="https://advertiser.com/lp' + count + '?cid={click_id}">' +
        '<button type="button" onclick="this.closest(\'.ih-lp-row\').remove()"' +
        ' style="background:#FEE2E2;color:#DC2626;border:1px solid #FECACA;border-radius:6px;padding:0 10px;height:36px;font-size:13px;cursor:pointer;white-space:nowrap;flex-shrink:0">&#10005; Remove</button>';
    container.appendChild(div);
    div.querySelector('input').focus();
}

function geoFlag(code) {
    var o = 0x1F1A5;
    return String.fromCodePoint(o + code.charCodeAt(0)) + String.fromCodePoint(o + code.charCodeAt(1));
}
function addGeoTag() {
    var input = document.getElementById('geoInput');
    var val   = input.value.trim().toUpperCase();
    if (!val || val.length !== 2) { alert('Enter a valid 2-letter country code (e.g. US, GB)'); return; }

    // Check duplicate
    var existing = document.querySelectorAll('#geoTagsWrap input[type=hidden]');
    for (var i = 0; i < existing.length; i++) {
        if (existing[i].value === val) { input.value = ''; return; }
    }

    var wrap = document.getElementById('geoTagsWrap');
    var span = document.createElement('span');
    span.className = 'geo-tag';
    span.style.cssText = 'display:inline-flex;align-items:center;gap:4px;background:#EDE9FE;color:#5B21B6;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600';
    span.innerHTML = geoFlag(val) + ' ' + val +
        '<button type="button" onclick="removeGeoTag(this)" style="background:none;border:none;color:#7C3AED;cursor:pointer;padding:0;font-size:13px;line-height:1">&times;</button>' +
        '<input type="hidden" name="geo_targeting[]" value="' + val + '">';
    wrap.appendChild(span);
    input.value = '';
    input.focus();
}
function removeGeoTag(btn) {
    btn.closest('.geo-tag').remove();
}
document.getElementById('geoInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addGeoTag(); }
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
