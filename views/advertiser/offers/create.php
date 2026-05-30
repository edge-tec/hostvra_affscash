<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div><h1>Create Offer</h1><p>Submit a new CPA offer for review</p></div>
    <a href="/advertiser/offers" class="btn btn-secondary">← Back</a>
</div>

<?php if(!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/advertiser/offers/create">
            <?= Helpers::csrf() ?>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Offer Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= Helpers::e($_POST['name']??'') ?>">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <option value="">— None —</option>
                        <?php foreach(['Finance','Health & Beauty','eCommerce','Gaming','Dating','Software','Travel','Education','Other'] as $c): ?>
                        <option value="<?= $c ?>"><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Describe your offer..."></textarea>
            </div>
            <div class="form-group">
                <label>Offer URL *</label>
                <input type="url" name="offer_url" class="form-control" required value="<?= Helpers::e($_POST['offer_url']??'') ?>" placeholder="https://yourlander.com/lp?cid={click_id}">
                <div class="form-hint">Include <code>{click_id}</code> so we can track conversions via postback</div>
            </div>
            <div class="form-group">
                <label>Preview URL</label>
                <input type="url" name="preview_url" class="form-control" placeholder="https://yoursite.com">
            </div>
            <div class="form-row cols-3">
                <div class="form-group">
                    <label>Payout Type</label>
                    <select name="payout_type" id="advPayoutTypeSelect" class="form-control">
                        <?php foreach(['CPA','CPC','CPL','RevShare'] as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label id="advPayoutAmountLabel">Affiliate Payout ($)</label>
                    <input type="number" step="0.01" min="0" id="advPayoutAmountInput" name="payout_amount" class="form-control" value="0.00">
                    <div id="advPayoutAmountHint" style="display:none;margin-top:4px;font-size:12px;color:#6366F1">Enter percentage 0–100. e.g. 30 = 30% of your revenue per conversion.</div>
                </div>
                <div class="form-group">
                    <label id="advRevenueAmountLabel">Your Budget per Conv. ($)</label>
                    <input type="number" step="0.01" min="0" id="advRevenueAmountInput" name="revenue_amount" class="form-control" value="0.00">
                </div>
            </div>
            <div class="form-group">
                <label>Target Countries (hold Ctrl/Cmd for multiple)</label>
                <?php $advGeoCountries = ['AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AD'=>'Andorra','AO'=>'Angola','AG'=>'Antigua and Barbuda','AR'=>'Argentina','AM'=>'Armenia','AU'=>'Australia','AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BT'=>'Bhutan','BO'=>'Bolivia','BA'=>'Bosnia and Herzegovina','BW'=>'Botswana','BR'=>'Brazil','BN'=>'Brunei','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CV'=>'Cape Verde','CF'=>'Central African Republic','TD'=>'Chad','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','KM'=>'Comoros','CG'=>'Congo','CD'=>'DR Congo','CR'=>'Costa Rica','HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','SZ'=>'Eswatini','ET'=>'Ethiopia','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','GD'=>'Grenada','GT'=>'Guatemala','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KP'=>'North Korea','KR'=>'South Korea','KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya','LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands','MR'=>'Mauritania','MU'=>'Mauritius','MX'=>'Mexico','FM'=>'Micronesia','MD'=>'Moldova','MC'=>'Monaco','MN'=>'Mongolia','ME'=>'Montenegro','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru','NP'=>'Nepal','NL'=>'Netherlands','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','MK'=>'North Macedonia','NO'=>'Norway','OM'=>'Oman','PK'=>'Pakistan','PW'=>'Palau','PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania','RU'=>'Russia','RW'=>'Rwanda','KN'=>'Saint Kitts and Nevis','LC'=>'Saint Lucia','VC'=>'Saint Vincent and the Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'Sao Tome and Principe','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia','SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa','SS'=>'South Sudan','ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TO'=>'Tonga','TT'=>'Trinidad and Tobago','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan','TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VA'=>'Vatican City','VE'=>'Venezuela','VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe']; ?>
                <select name="geo_targeting[]" class="form-control" multiple style="height:200px">
                    <option value="GLOBAL" selected>🌐 GLOBAL — All Countries</option>
                    <?php foreach($advGeoCountries as $code => $name): ?>
                    <option value="<?= $code ?>"><?= Helpers::flag($code) ?> <?= $code ?> — <?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Select <strong>Global</strong> for all countries, or pick specific countries (hold Ctrl/Cmd for multiple).</div>
            </div>

            <!-- ─── Device Targeting ──────────────────────────────────────── -->
            <div class="form-group" style="margin-top:18px;padding:14px 16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="font-weight:700">Device Targeting</label>
                <div class="form-hint" style="margin-bottom:8px">Restrict this offer to specific device types. Leave all unchecked to accept any device.</div>
                <div style="display:flex;flex-wrap:wrap;gap:14px">
                    <?php $advSelDevices = (array)($_POST['device_targeting'] ?? []); ?>
                    <?php foreach (['desktop'=>'🖥️ Desktop','mobile'=>'📱 Mobile','tablet'=>'📲 Tablet'] as $advDv => $advDvLbl): ?>
                    <label style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:8px 14px;cursor:pointer;font-size:13px;font-weight:600">
                        <input type="checkbox" name="device_targeting[]" value="<?= $advDv ?>" <?= in_array($advDv, $advSelDevices, true) ? 'checked' : '' ?>>
                        <span><?= $advDvLbl ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ─── Multiple Landing Pages ────────────────────────────────── -->
            <div class="form-group" style="margin-top:14px;padding:14px 16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="font-weight:700">Landing Pages <span style="font-weight:400;color:var(--text-muted);font-size:12px">— add more variants for rotation / A-B testing</span></label>
                <div class="form-hint" style="margin-bottom:8px">Each LP has a URL and an optional Name shown in click/conversion reports. The main <strong>Offer URL</strong> above is always LP #1.</div>
                <div id="adv-lp-container">
                    <?php $advLpUrls = (array)($_POST['landing_pages'] ?? []); $advLpNames = (array)($_POST['landing_page_names'] ?? []); ?>
                    <?php if (!empty($advLpUrls)): foreach ($advLpUrls as $i => $url): ?>
                    <div class="adv-lp-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
                        <input type="text" name="landing_page_names[]" class="form-control" style="flex:0 0 200px;max-width:200px" placeholder="LP<?= $i+1 ?> name" value="<?= Helpers::e($advLpNames[$i] ?? '') ?>">
                        <input type="url"  name="landing_pages[]"      class="form-control" style="flex:1;min-width:240px" placeholder="https://yourlander.com/v<?= $i+1 ?>?cid={click_id}" value="<?= Helpers::e($url) ?>">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()" style="white-space:nowrap">&times; Remove</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="advAddLandingPage()">+ Add Landing Page</button>
            </div>

            <!-- ─── Country-Specific Payouts ──────────────────────────────── -->
            <div class="form-group" style="margin-top:14px;padding:14px 16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="font-weight:700">Country-Specific Payouts</label>
                <div class="form-hint" style="margin-bottom:8px">Override the default affiliate payout for selected countries. Leave empty to use the default payout everywhere.</div>
                <div id="adv-cp-container">
                    <?php $advCpC = (array)($_POST['country_payout_country'] ?? []); $advCpA = (array)($_POST['country_payout_amount'] ?? []); ?>
                    <?php if (!empty($advCpC)): foreach ($advCpC as $i => $cc): ?>
                    <div class="adv-cp-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
                        <input type="text"   name="country_payout_country[]" class="form-control" maxlength="2" style="max-width:90px;text-transform:uppercase" placeholder="US"   value="<?= Helpers::e(strtoupper((string)$cc)) ?>">
                        <input type="number" name="country_payout_amount[]"  class="form-control" step="0.01" min="0" style="max-width:140px" placeholder="5.00" value="<?= Helpers::e((string)($advCpA[$i] ?? '')) ?>">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()" style="white-space:nowrap">&times; Remove</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="advAddCountryPayout()">+ Add Country Rule</button>
            </div>

            <!-- ─── Device-Specific Payouts ───────────────────────────────── -->
            <div class="form-group" style="margin-top:14px;padding:14px 16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="font-weight:700">Device-Specific Payouts</label>
                <div class="form-hint" style="margin-bottom:8px">Override the default affiliate payout per device. Leave 0 to use the default payout for that device.</div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:var(--text-muted)">Desktop ($)</label>
                        <input type="number" step="0.01" min="0" name="device_payout_desktop" class="form-control" placeholder="0.00" value="<?= Helpers::e($_POST['device_payout_desktop'] ?? '') ?>">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:var(--text-muted)">Mobile ($)</label>
                        <input type="number" step="0.01" min="0" name="device_payout_mobile" class="form-control" placeholder="0.00" value="<?= Helpers::e($_POST['device_payout_mobile'] ?? '') ?>">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:var(--text-muted)">Tablet ($)</label>
                        <input type="number" step="0.01" min="0" name="device_payout_tablet" class="form-control" placeholder="0.00" value="<?= Helpers::e($_POST['device_payout_tablet'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- ─── Daily & Total Cap ─────────────────────────────────────── -->
            <div class="form-group" style="margin-top:14px;padding:14px 16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="font-weight:700">Conversion Caps</label>
                <div class="form-hint" style="margin-bottom:8px">Auto-pause the offer once it reaches the cap. Set <strong>0</strong> for unlimited.</div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:var(--text-muted)">Daily Conversion Cap</label>
                        <input type="number" min="0" name="daily_cap" class="form-control" placeholder="0 = unlimited" value="<?= Helpers::e($_POST['daily_cap'] ?? '0') ?>">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:var(--text-muted)">Total Conversion Cap</label>
                        <input type="number" min="0" name="total_cap" class="form-control" placeholder="0 = unlimited" value="<?= Helpers::e($_POST['total_cap'] ?? '0') ?>">
                    </div>
                </div>
            </div>

            <!-- ─── Smart Routing (Device + GEO) ──────────────────────────── -->
            <div class="form-group" style="margin-top:14px;padding:14px 16px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px">
                <label style="font-weight:700;color:#3730A3">Smart Routing — Device &amp; GEO</label>
                <div class="form-hint" style="margin-bottom:8px;color:#4338CA">
                    Route visitors to a different landing URL (and optional payout) based on their device + country. Priority: <em>Device+GEO match → Device-only → GEO-only → fallback Landing Pages above</em>.
                </div>
                <div id="adv-smart-container">
                    <?php $advOlL = (array)($_POST['ol_label'] ?? []); $advOlD = (array)($_POST['ol_device'] ?? []); $advOlG = (array)($_POST['ol_geo'] ?? []); $advOlU = (array)($_POST['ol_url'] ?? []); $advOlP = (array)($_POST['ol_payout'] ?? []); ?>
                    <?php if (!empty($advOlU)): foreach ($advOlU as $i => $url): ?>
                    <div class="adv-smart-row" style="background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:12px;margin-bottom:10px;position:relative">
                        <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm" style="position:absolute;top:8px;right:8px;padding:2px 8px">&times;</button>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:8px">
                            <input type="text"   name="ol_label[]"  class="form-control" placeholder="Label (e.g. Mobile Tier 1)" value="<?= Helpers::e($advOlL[$i] ?? '') ?>">
                            <select              name="ol_device[]" class="form-control">
                                <?php foreach (['all'=>'All Devices','desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $advDvV => $advDvLb): ?>
                                <option value="<?= $advDvV ?>" <?= ($advOlD[$i] ?? 'all') === $advDvV ? 'selected' : '' ?>><?= $advDvLb ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text"   name="ol_geo[]"    class="form-control" maxlength="2" style="text-transform:uppercase" placeholder="GEO (e.g. US)" value="<?= Helpers::e(strtoupper((string)($advOlG[$i] ?? ''))) ?>">
                        </div>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px">
                            <input type="url"    name="ol_url[]"    class="form-control" placeholder="https://target.com/lp?cid={click_id}" value="<?= Helpers::e($url) ?>">
                            <input type="number" name="ol_payout[]" class="form-control" step="0.01" min="0" placeholder="Payout override ($) — optional" value="<?= Helpers::e((string)($advOlP[$i] ?? '')) ?>">
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="advAddSmartRoute()">+ Add Routing Rule</button>
            </div>

            <?php if ($budgetRequired): ?>
            <div class="form-group" style="margin-top:10px;padding:14px 16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px">
                <label style="font-weight:700;color:#92400E">
                    Offer Budget (USD) <span style="color:#DC2626">*</span>
                </label>
                <div style="font-size:13px;color:#78350F;margin-bottom:8px">
                    Your available balance: <strong>$<?= number_format($advBalance, 2) ?></strong>
                </div>
                <input type="number" step="0.01" min="0.01" max="<?= htmlspecialchars((string)$advBalance, ENT_QUOTES, 'UTF-8') ?>" name="budget_total" class="form-control"
                       value="<?= Helpers::e($_POST['budget_total'] ?? '') ?>"
                       placeholder="e.g. 500.00"
                       required style="max-width:240px">
                <div class="form-hint" style="color:#78350F">
                    Set the total USD budget for this offer. Each approved conversion deducts the <strong>Revenue Today</strong> amount from this budget and your account balance. The offer is auto-paused when the budget reaches zero. Budget cannot exceed your available balance.
                </div>
            </div>
            <?php endif; ?>
            <div class="alert alert-info">
                Your offer will be reviewed by our team before going live. We'll notify you once it's approved.
            </div>
            <button type="submit" class="btn btn-primary">Submit Offer for Review</button>
        </form>
    </div>
</div>

<script>
(function() {
    function updateAdvPayoutUI() {
        var sel      = document.getElementById('advPayoutTypeSelect');
        var label    = document.getElementById('advPayoutAmountLabel');
        var input    = document.getElementById('advPayoutAmountInput');
        var hint     = document.getElementById('advPayoutAmountHint');
        var revLabel = document.getElementById('advRevenueAmountLabel');
        var revInput = document.getElementById('advRevenueAmountInput');
        if (!sel || !label || !input) return;
        if (sel.value === 'RevShare') {
            label.textContent = 'Affiliate Payout (%)';
            input.max = '100';
            input.placeholder = 'e.g. 30 for 30%';
            if (hint) hint.style.display = 'block';
            if (revLabel) revLabel.textContent = 'Your Budget per Conv. (%)';
            if (revInput) { revInput.max = '100'; revInput.placeholder = 'e.g. 100 for 100%'; }
        } else {
            label.textContent = 'Affiliate Payout ($)';
            input.removeAttribute('max');
            input.placeholder = '0.00';
            if (hint) hint.style.display = 'none';
            if (revLabel) revLabel.textContent = 'Your Budget per Conv. ($)';
            if (revInput) { revInput.removeAttribute('max'); revInput.placeholder = '0.00'; }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('advPayoutTypeSelect');
        if (sel) { sel.addEventListener('change', updateAdvPayoutUI); updateAdvPayoutUI(); }
    });

    // ── Dynamic-row builders for the advanced sections ────────────────────
    // Exposed on window only so the inline onclick="" handlers above can
    // reach them; the closure still scopes the helpers above.
    function rmRow(btn){ btn.parentElement.remove(); }

    window.advAddLandingPage = function(){
        var c = document.getElementById('adv-lp-container'); if(!c) return;
        var n = c.querySelectorAll('.adv-lp-row').length + 1;
        var row = document.createElement('div');
        row.className = 'adv-lp-row';
        row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap';
        row.innerHTML =
            '<input type="text" name="landing_page_names[]" class="form-control" style="flex:0 0 200px;max-width:200px" placeholder="LP'+n+' name">' +
            '<input type="url"  name="landing_pages[]"      class="form-control" style="flex:1;min-width:240px" placeholder="https://yourlander.com/v'+n+'?cid={click_id}">' +
            '<button type="button" class="btn btn-danger btn-sm" style="white-space:nowrap">&times; Remove</button>';
        row.querySelector('button').addEventListener('click', function(){ rmRow(this); });
        c.appendChild(row);
    };

    window.advAddCountryPayout = function(){
        var c = document.getElementById('adv-cp-container'); if(!c) return;
        var row = document.createElement('div');
        row.className = 'adv-cp-row';
        row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap';
        row.innerHTML =
            '<input type="text"   name="country_payout_country[]" class="form-control" maxlength="2" style="max-width:90px;text-transform:uppercase" placeholder="US">' +
            '<input type="number" name="country_payout_amount[]"  class="form-control" step="0.01" min="0" style="max-width:140px" placeholder="5.00">' +
            '<button type="button" class="btn btn-danger btn-sm" style="white-space:nowrap">&times; Remove</button>';
        row.querySelector('button').addEventListener('click', function(){ rmRow(this); });
        c.appendChild(row);
    };

    window.advAddSmartRoute = function(){
        var c = document.getElementById('adv-smart-container'); if(!c) return;
        var row = document.createElement('div');
        row.className = 'adv-smart-row';
        row.style.cssText = 'background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:12px;margin-bottom:10px;position:relative';
        row.innerHTML =
            '<button type="button" class="btn btn-danger btn-sm" style="position:absolute;top:8px;right:8px;padding:2px 8px">&times;</button>' +
            '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:8px">' +
                '<input type="text" name="ol_label[]" class="form-control" placeholder="Label (e.g. Mobile Tier 1)">' +
                '<select name="ol_device[]" class="form-control">' +
                    '<option value="all">All Devices</option>' +
                    '<option value="desktop">Desktop</option>' +
                    '<option value="mobile">Mobile</option>' +
                    '<option value="tablet">Tablet</option>' +
                '</select>' +
                '<input type="text" name="ol_geo[]" class="form-control" maxlength="2" style="text-transform:uppercase" placeholder="GEO (e.g. US)">' +
            '</div>' +
            '<div style="display:grid;grid-template-columns:2fr 1fr;gap:10px">' +
                '<input type="url"    name="ol_url[]"    class="form-control" placeholder="https://target.com/lp?cid={click_id}">' +
                '<input type="number" name="ol_payout[]" class="form-control" step="0.01" min="0" placeholder="Payout override ($) — optional">' +
            '</div>';
        row.querySelector('button').addEventListener('click', function(){ row.remove(); });
        c.appendChild(row);
    };
})();
</script>
<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
