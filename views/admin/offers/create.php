<?php
$isEdit = isset($offer);
$pageTitle = $isEdit ? 'Edit Offer' : 'Create Offer';
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div><h1><?= $isEdit ? 'Edit Offer' : 'Create New Offer' ?></h1><p><?= $isEdit ? 'Modify offer settings' : 'Set up a new affiliate offer' ?></p></div>
    <a href="/admin/offers" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? '/admin/offers/'.$offer['id'] : '/admin/offers/create' ?>" enctype="multipart/form-data">
    <?= Helpers::csrf() ?>
    <?php if ($isEdit): ?><input type="hidden" name="action" value="update"><?php endif; ?>

    <div class="grid-2">
        <div>
            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Basic Information</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Offer Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= Helpers::e($offer['name'] ?? $_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Advertiser *</label>
                        <select name="advertiser_id" class="form-control" required>
                            <option value="">Select advertiser...</option>
                            <?php foreach($advertisers as $adv): ?>
                            <option value="<?= $adv['id'] ?>" <?= (($offer['advertiser_id'] ?? '') == $adv['id']) ? 'selected' : '' ?>><?= Helpers::e($adv['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <?php
                        // Fetch active categories from database
                        $dbCategories = [];
                        try { $dbCategories = Database::fetchAll("SELECT id, name FROM offer_categories WHERE status='active' ORDER BY sort_order, name"); } catch (\Throwable $_e) {}
                        ?>
                        <select name="category_id" id="offerCategorySelect" class="form-control" onchange="loadOfferTypes(this.value)">
                            <option value="">— Select Category —</option>
                            <?php foreach($dbCategories as $dbCat): ?>
                            <option value="<?= $dbCat['id'] ?>" <?= (($offer['category_id'] ?? '') == $dbCat['id']) ? 'selected' : '' ?>><?= Helpers::e($dbCat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Preserve old category string field for backward compat -->
                        <input type="hidden" name="category" value="<?= Helpers::e($offer['category'] ?? '') ?>" id="offerCategoryLegacy">
                    </div>
                    <div class="form-group">
                        <label>Offer Type</label>
                        <select name="offer_type_id" id="offerTypeSelect" class="form-control">
                            <option value="">— Select Category First —</option>
                        </select>
                        <div class="form-hint">Offer types are loaded dynamically based on the selected category.</div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= Helpers::e($offer['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Terms &amp; Conditions</label>
                        <textarea name="terms" class="form-control" rows="3"><?= Helpers::e($offer['terms'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Offer Image</label>
                        <?php if (!empty($offer['offer_image'])): ?>
                        <div style="margin-bottom:8px">
                            <img src="<?= Helpers::e($offer['offer_image']) ?>" alt="Current offer image" style="max-width:120px;max-height:80px;border-radius:6px;border:1px solid #E2E8F0;object-fit:cover">
                            <div class="form-hint">Current image — upload a new one to replace it.</div>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="offer_image" class="form-control" accept="image/*">
                        <div class="form-hint">Optional. Displayed on offer cards. Max 2MB.</div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="require_approval" value="1" <?= !empty($offer['require_approval']) ? 'checked' : (!empty($_POST['require_approval']) ? 'checked' : '') ?>>
                            <span>Require Approval — affiliates must request access</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Targeting</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>GEO Targeting</label>
                        <?php
                        $geos = $offer['geo_targeting'] ?? null;
                        $selectedGeos = $geos ? json_decode($geos, true) : [];
                        $countries = ['AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AD'=>'Andorra','AO'=>'Angola','AG'=>'Antigua and Barbuda','AR'=>'Argentina','AM'=>'Armenia','AU'=>'Australia','AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BT'=>'Bhutan','BO'=>'Bolivia','BA'=>'Bosnia and Herzegovina','BW'=>'Botswana','BR'=>'Brazil','BN'=>'Brunei','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CV'=>'Cape Verde','CF'=>'Central African Republic','TD'=>'Chad','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','KM'=>'Comoros','CG'=>'Congo','CD'=>'DR Congo','CR'=>'Costa Rica','HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','SZ'=>'Eswatini','ET'=>'Ethiopia','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','GD'=>'Grenada','GT'=>'Guatemala','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HN'=>'Honduras','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KP'=>'North Korea','KR'=>'South Korea','KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Laos','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libya','LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands','MR'=>'Mauritania','MU'=>'Mauritius','MX'=>'Mexico','FM'=>'Micronesia','MD'=>'Moldova','MC'=>'Monaco','MN'=>'Mongolia','ME'=>'Montenegro','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru','NP'=>'Nepal','NL'=>'Netherlands','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','MK'=>'North Macedonia','NO'=>'Norway','OM'=>'Oman','PK'=>'Pakistan','PW'=>'Palau','PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania','RU'=>'Russia','RW'=>'Rwanda','KN'=>'Saint Kitts and Nevis','LC'=>'Saint Lucia','VC'=>'Saint Vincent and the Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'Sao Tome and Principe','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia','SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa','SS'=>'South Sudan','ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TO'=>'Tonga','TT'=>'Trinidad and Tobago','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan','TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VA'=>'Vatican City','VE'=>'Venezuela','VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe'];
                        ?>

                        <!-- Hidden select for actual form submission — untouched by UI changes -->
                        <select name="geo_targeting[]" id="geo-select-hidden" multiple style="display:none">
                            <option value="GLOBAL" <?= ($geos === null || in_array('GLOBAL', (array)$selectedGeos)) ? 'selected' : '' ?>>Global</option>
                            <?php foreach($countries as $code => $name): ?>
                            <option value="<?= $code ?>" <?= in_array($code,$selectedGeos)?'selected':'' ?>><?= Helpers::flag($code) ?> <?= $code ?> — <?= $name ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Custom multi-select widget -->
                        <div class="geo-ms" id="geo-ms">
                            <div class="geo-ms-trigger" id="geo-ms-trigger">
                                <div class="geo-ms-tags" id="geo-ms-tags"></div>
                                <input type="text" class="geo-ms-search" id="geo-ms-search" placeholder="Search countries..." autocomplete="off" spellcheck="false">
                                <span class="geo-ms-arrow" id="geo-ms-arrow">&#9660;</span>
                            </div>
                            <div class="geo-ms-dropdown" id="geo-ms-dropdown">
                                <div class="geo-ms-list">
                                    <label class="geo-ms-opt geo-ms-global" id="geo-ms-global-opt">
                                        <input type="checkbox" id="geo-ms-global"> <span class="geo-ms-global-icon">🌐</span> <span class="geo-ms-label"><strong>Global</strong> — All Countries</span>
                                    </label>
                                    <div class="geo-ms-divider"></div>
                                    <label class="geo-ms-opt geo-ms-select-all">
                                        <input type="checkbox" id="geo-ms-all"> <span>Select all visible</span>
                                    </label>
                                    <div class="geo-ms-divider"></div>
                                    <?php foreach($countries as $code => $name): ?>
                                    <label class="geo-ms-opt" data-code="<?= $code ?>" data-name="<?= strtolower($name) ?>">
                                        <input type="checkbox" value="<?= $code ?>" <?= in_array($code,$selectedGeos)?'checked':'' ?>>
                                        <span class="geo-ms-code"><?= Helpers::flag($code) ?> <?= $code ?></span>
                                        <span class="geo-ms-label"><?= $code ?> — <?= $name ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-hint">Select <strong>Global</strong> to allow all countries, or choose specific countries to restrict access.</div>
                    </div>
                    <div class="form-group">
                        <label>Device Targeting</label>
                        <?php $selDevices = !empty($offer['device_targeting']) ? json_decode($offer['device_targeting'],true) : []; ?>
                        <?php foreach(['desktop','mobile','tablet'] as $d): ?>
                        <div class="form-check">
                            <input type="checkbox" name="device_targeting[]" value="<?= $d ?>" <?= in_array($d,(array)$selDevices)?'checked':'' ?>>
                            <label><?= ucfirst($d) ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Tracking URLs</span></div>
                <div class="card-body">

                    <!-- Domain selector for tracking link -->
                    <div class="form-group">
                        <label>Tracking Domain</label>
                        <?php
                        $domains = Database::fetchAll("SELECT * FROM tracking_domains WHERE is_active=1 ORDER BY is_default DESC, domain ASC");
                        $defaultDomain = '';
                        foreach ($domains as $d) { if ($d['is_default']) { $defaultDomain = $d['domain']; break; } }
                        if (!$defaultDomain) $defaultDomain = parse_url(Config::get('config','app.url') ?? '', PHP_URL_HOST) ?: 'yourdomain.com';
                        ?>
                        <select name="tracking_domain" class="form-control" id="trackingDomainSelect" onchange="updateTrackingPreview()">
                            <option value="">— Use Default (<?= Helpers::e($defaultDomain) ?>) —</option>
                            <?php foreach ($domains as $d): ?>
                            <option value="<?= Helpers::e($d['domain']) ?>" <?= ($offer['tracking_domain'] ?? '') === $d['domain'] ? 'selected' : '' ?>>
                                <?= Helpers::e($d['domain']) ?><?= $d['is_default'] ? ' (Default)' : '' ?>
                                <?php if ($d['label']): ?> — <?= Helpers::e($d['label']) ?><?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tracking link preview -->
                    <div style="background:#F1F5F9;border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:12px" id="trackingPreviewBox">
                        <div style="font-weight:600;margin-bottom:6px;color:#475569">Tracking link format:</div>
                        <code id="trackingPreviewCode" style="word-break:break-all;color:#4F46E5">
                            https://<?= Helpers::e($defaultDomain) ?>/click/<?= $isEdit ? $offer['id'] : '{offer_id}' ?>?aff_id={aff_id}&amp;click_id={click_id}
                        </code>
                    </div>

                    <!-- Macro inserter -->
                    <div class="form-group">
                        <label>Available Macros <span style="font-weight:400;color:var(--text-muted);font-size:12px">— click to insert into focused URL field</span></label>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
                            <?php foreach(['{click_id}','{aff_id}','{aff_sub1}','{aff_sub2}','{aff_sub3}','{aff_sub4}','{offer_id}','{country}'] as $macro): ?>
                            <button type="button" onclick="insertUrlMacro('<?= $macro ?>')" style="background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;padding:3px 10px;border-radius:4px;font-size:12px;font-family:monospace;cursor:pointer"><?= $macro ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint">Click a URL field first, then click a macro to insert it at cursor position</div>
                    </div>

                    <!-- Multiple landing page URLs (each with optional friendly Name) -->
                    <div class="form-group">
                        <label>Landing Pages * <span style="font-weight:400;color:var(--text-muted);font-size:12px">— each LP has a URL and an optional Name shown in click/conversion reports</span></label>
                        <div id="landing-pages-container">
                        <?php
                        // Build existing URL list
                        $existingUrls = [];
                        if (!empty($offer['landing_pages'])) {
                            $existingUrls = json_decode($offer['landing_pages'], true) ?: [];
                        }
                        if (empty($existingUrls) && !empty($offer['offer_url'])) {
                            $existingUrls = [$offer['offer_url']];
                        }
                        if (empty($existingUrls)) {
                            $existingUrls = [''];
                        }
                        // Pair each URL with its existing name (if any), aligned by index.
                        $existingNames = [];
                        if (!empty($offer['landing_page_names'])) {
                            $existingNames = json_decode($offer['landing_page_names'], true) ?: [];
                        }
                        foreach ($existingUrls as $i => $url):
                            $lpName = $existingNames[$i] ?? '';
                        ?>
                        <div class="landing-url-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
                            <input type="text" name="landing_page_names[]" class="form-control landing-name-input"
                                   placeholder="LP<?= $i+1 ?> name (e.g. Hero v2)"
                                   value="<?= Helpers::e($lpName) ?>"
                                   style="max-width:200px;flex:0 0 200px">
                            <input type="url" name="landing_pages[]" class="form-control landing-url-input"
                                   placeholder="https://advertiser.com/lp<?= $i+1 ?>?cid={click_id}"
                                   value="<?= Helpers::e($url) ?>"
                                   style="flex:1;min-width:240px"
                                   <?= $i === 0 ? 'required' : '' ?>>
                            <?php if ($i > 0): ?>
                            <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">&#10005; Remove</button>
                            <?php else: ?>
                            <button type="button" disabled style="visibility:hidden" class="btn btn-sm">x</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addLandingPage()" class="btn btn-secondary btn-sm">+ Add Landing Page</button>
                    </div>

                    <div class="form-group">
                        <label>Preview URL</label>
                        <input type="url" name="preview_url" class="form-control landing-url-input" value="<?= Helpers::e($offer['preview_url'] ?? '') ?>" placeholder="https://advertiser.com/preview">
                        <div class="form-hint">Shown to affiliates as a preview (not tracked)</div>
                    </div>
                </div>
            </div>

            <!-- ── Device Links & Payouts ──────────────────────────────────── -->
            <div class="card mb-2">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <span class="card-title">Device Links &amp; Payouts</span>
                        <span class="badge badge-info" style="margin-left:6px;font-size:10px">Optional</span>
                    </div>
                    <button type="button" onclick="addOfferLink()" class="btn btn-secondary btn-sm">+ Add Link</button>
                </div>
                <div class="card-body" style="padding-bottom:12px">
                    <div class="form-hint" style="margin-bottom:12px">
                        Assign a <strong>different URL and payout rate per device + GEO combination</strong>. The tracker auto-detects the visitor's device and country, then routes to the best matching link.
                        Priority: <em>Device+GEO match → Device-only match → Any Device+GEO → Any Device fallback</em>.
                        If no link matches, the Landing Page URLs above are used.
                    </div>

                    <div id="offer-links-container">
                    <?php foreach (($offerLinks ?? []) as $li => $lnk): ?>
                        <div class="ol-row" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px;margin-bottom:10px;position:relative">
                            <button type="button" onclick="removeOfferLinkRow(this)" class="btn btn-danger btn-sm"
                                style="position:absolute;top:10px;right:10px;padding:2px 8px;line-height:1.2">&#10005;</button>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px">
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">Label <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                                    <input type="text" name="ol_label[]" class="form-control" placeholder="e.g. Mobile Tier 1" value="<?= Helpers::e($lnk['label']) ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">Device *</label>
                                    <select name="ol_device[]" class="form-control">
                                        <?php foreach(['all'=>'All Devices','desktop'=>'Desktop','mobile'=>'Mobile','tablet'=>'Tablet'] as $dv=>$dvLbl): ?>
                                        <option value="<?= $dv ?>" <?= ($lnk['device_type'] ?? 'all') === $dv ? 'selected' : '' ?>><?= $dvLbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">GEO</label>
                                    <select name="ol_geo[]" class="form-control">
                                        <option value="">— Any GEO —</option>
                                        <?php foreach($countries as $code => $name): ?>
                                        <option value="<?= $code ?>" <?= ($lnk['geo_country'] ?? '') === $code ? 'selected' : '' ?>><?= Helpers::flag($code) ?> <?= $code ?> — <?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label style="font-size:11px;font-weight:600">Offer URL *</label>
                                <input type="url" name="ol_url[]" class="form-control landing-url-input" required
                                       placeholder="https://advertiser.com/lp?cid={click_id}"
                                       value="<?= Helpers::e($lnk['offer_url']) ?>">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">Payout ($)</label>
                                    <input type="number" name="ol_payout[]" class="form-control" step="0.01" min="0" placeholder="0.00"
                                           value="<?= number_format((float)$lnk['payout_rate'], 2, '.', '') ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">Revenue ($)</label>
                                    <input type="number" name="ol_revenue[]" class="form-control" step="0.01" min="0" placeholder="0.00"
                                           value="<?= number_format((float)$lnk['revenue_rate'], 2, '.', '') ?>">
                                </div>
                                <div class="form-group mb-0">
                                    <label style="font-size:11px;font-weight:600">Status</label>
                                    <select name="ol_status[]" class="form-control">
                                        <option value="active" <?= ($lnk['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($lnk['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <div id="ol-empty" style="<?= !empty($offerLinks) ? 'display:none' : '' ?>text-align:center;padding:20px 0 8px;color:var(--text-muted);font-size:13px">
                        <div style="border:2px dashed #E2E8F0;border-radius:8px;padding:20px">
                            No device links yet — click <strong>+ Add Link</strong> above to add one.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Payout Settings</span></div>
                <div class="card-body">
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Offer Type</label>
                            <select name="offer_type" class="form-control">
                                <option value="">— Select —</option>
                                <?php foreach(['SOI','DOI','CPA','CPI','CPS','COD','FINANCE','CPM','CPL','CPC','RevShare','Trial','Other'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($offer['offer_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payout Type</label>
                            <select name="payout_type" id="payoutTypeSelect" class="form-control">
                                <?php foreach(['CPA','CPC','CPL','RevShare','CPM','CPI','CPS','COD','Trial'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($offer['payout_type'] ?? 'CPA') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label id="payoutAmountLabel">Affiliate Payout ($)</label>
                            <input type="number" step="0.01" min="0" id="payoutAmountInput" name="payout_amount" class="form-control" value="<?= $offer['payout_amount'] ?? '0.00' ?>">
                            <div id="payoutAmountHint" style="display:none;margin-top:4px;font-size:12px;color:#6366F1">
                                Enter percentage (0–100). Affiliate earns this % of advertiser revenue.
                                e.g. 30 = 30% of revenue per conversion.
                            </div>
                        </div>
                        <div class="form-group">
                            <label id="revenueAmountLabel">Advertiser Revenue ($)</label>
                            <input type="number" step="0.01" min="0" id="revenueAmountInput" name="revenue_amount" class="form-control" value="<?= $offer['revenue_amount'] ?? '0.00' ?>">
                            <div id="revenue-warning" style="display:none;margin-top:6px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:6px;padding:6px 10px;font-size:12px;color:#92400E">
                                &#9888; <strong>Manager commission requires Revenue &gt; Payout.</strong>
                                Net Profit = Revenue &minus; Payout. If Revenue &le; Payout, no manager commission will be generated for this offer.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Advanced Payout</span></div>
                <div class="card-body">
                    <?php
                    $existingCountryPayouts = !empty($offer['country_payouts']) ? json_decode($offer['country_payouts'], true) : [];
                    $existingDevicePayouts  = !empty($offer['device_payouts'])  ? json_decode($offer['device_payouts'],  true) : [];
                    ?>

                    <!-- Country-Specific Payouts -->
                    <div class="form-group">
                        <label>Country-Specific Payouts</label>
                        <div class="form-hint" style="margin-bottom:8px">Overrides default payout for matching countries. Leave empty to use default for all.</div>
                        <div id="country-payout-container">
                        <?php if (!empty($existingCountryPayouts)): ?>
                            <?php foreach ($existingCountryPayouts as $cpRow): ?>
                            <div class="form-row cols-2" style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                                <input type="text" name="country_payout_country[]" class="form-control" placeholder="US" maxlength="2" style="text-transform:uppercase;max-width:80px" value="<?= Helpers::e($cpRow['country'] ?? '') ?>">
                                <input type="number" name="country_payout_amount[]" class="form-control" step="0.01" min="0" placeholder="5.00" value="<?= Helpers::e($cpRow['payout'] ?? '') ?>">
                                <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">&#10005; Remove</button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                        <button type="button" onclick="addCountryPayoutRow()" class="btn btn-secondary btn-sm">+ Add Country Rule</button>
                    </div>

                    <!-- Device-Specific Payouts -->
                    <div class="form-group">
                        <label>Device-Specific Payouts</label>
                        <div class="form-hint" style="margin-bottom:8px">Leave 0 to use default offer payout.</div>
                        <div class="form-row cols-2" style="display:flex;gap:12px">
                            <div class="form-group" style="margin-bottom:0">
                                <label style="font-size:12px">Desktop ($)</label>
                                <input type="number" step="0.01" min="0" name="device_payout_desktop" class="form-control" placeholder="0.00" value="<?= Helpers::e($existingDevicePayouts['desktop'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label style="font-size:12px">Mobile ($)</label>
                                <input type="number" step="0.01" min="0" name="device_payout_mobile" class="form-control" placeholder="0.00" value="<?= Helpers::e($existingDevicePayouts['mobile'] ?? '') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label style="font-size:12px">Tablet ($)</label>
                                <input type="number" step="0.01" min="0" name="device_payout_tablet" class="form-control" placeholder="0.00" value="<?= Helpers::e($existingDevicePayouts['tablet'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Optimize -->
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-check">
                            <input type="checkbox" name="conversion_optimize" value="1" <?= !empty($offer['conversion_optimize']) ? 'checked' : '' ?>>
                            <label>Enable conversion-rate-optimized URL rotation</label>
                        </div>
                        <div class="form-hint">When enabled, landing pages with higher conversion rates automatically receive more traffic. Requires at least 10 clicks per page to activate weighting.</div>
                    </div>

                    <!-- Auto-Pause on Low CR -->
                    <hr style="margin:16px 0;border:none;border-top:1px solid #E2E8F0">
                    <div class="form-group" style="margin-bottom:0">
                        <label>Auto-Pause on Low Conversion Rate</label>
                        <div class="form-hint" style="margin-bottom:8px">Automatically pauses this offer when CR drops below the threshold. Set to 0 to disable.</div>
                        <div style="display:flex;gap:12px;align-items:flex-end">
                            <div class="form-group" style="margin-bottom:0">
                                <label style="font-size:12px">Min. CR Threshold (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="auto_pause_cr" class="form-control" style="max-width:120px"
                                       value="<?= Helpers::e($offer['auto_pause_cr'] ?? '0') ?>"
                                       placeholder="e.g. 1.5">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label style="font-size:12px">Minimum Clicks Before Check</label>
                                <input type="number" min="10" name="auto_pause_min_clicks" class="form-control" style="max-width:140px"
                                       value="<?= Helpers::e($offer['auto_pause_min_clicks'] ?? '100') ?>"
                                       placeholder="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Creative Assets</span></div>
                <div class="card-body">
                    <?php
                    $existingBanners = !empty($offer['banner_urls']) ? json_decode($offer['banner_urls'], true) : [];
                    $bannerSizes = [
                        '728x90'  => 'Leaderboard (728×90)',
                        '300x250' => 'Medium Rectangle (300×250)',
                        '160x600' => 'Wide Skyscraper (160×600)',
                        '320x50'  => 'Mobile Banner (320×50)',
                        '300x600' => 'Half Page (300×600)',
                        '970x90'  => 'Billboard (970×90)',
                    ];
                    ?>
                    <!-- Banner URLs -->
                    <div class="form-group">
                        <label>Banner Image URLs</label>
                        <div class="form-hint" style="margin-bottom:10px">Paste the direct URL to each banner image. Leave empty to skip a size.</div>
                        <?php foreach ($bannerSizes as $sz => $label): ?>
                        <div style="margin-bottom:8px">
                            <label style="font-size:12px;font-weight:600;color:var(--text-muted)"><?= $label ?></label>
                            <input type="url" name="banner_<?= str_replace('x','_',$sz) ?>" class="form-control"
                                   placeholder="https://cdn.example.com/banner-<?= $sz ?>.jpg"
                                   value="<?= Helpers::e($existingBanners[$sz] ?? '') ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr style="margin:16px 0;border:none;border-top:1px solid #E2E8F0">

                    <!-- Iframe -->
                    <div class="form-group">
                        <label>Iframe URL</label>
                        <input type="url" name="iframe_url" class="form-control"
                               placeholder="https://advertiser.com/iframe?id=..."
                               value="<?= Helpers::e($offer['iframe_url'] ?? '') ?>">
                        <div class="form-hint">Embed an iframe creative for this offer. Leave empty if not applicable.</div>
                    </div>
                    <div class="form-row cols-2" style="display:flex;gap:12px">
                        <div class="form-group" style="margin-bottom:0">
                            <label>Iframe Width (px)</label>
                            <input type="number" name="iframe_width" class="form-control" min="1" max="9999"
                                   value="<?= (int)($offer['iframe_width'] ?? 728) ?>" placeholder="728">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Iframe Height (px)</label>
                            <input type="number" name="iframe_height" class="form-control" min="1" max="9999"
                                   value="<?= (int)($offer['iframe_height'] ?? 90) ?>" placeholder="90">
                        </div>
                    </div>
                    <?php if (!empty($offer['iframe_url'])): ?>
                    <div style="margin-top:12px;padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px">
                        <div style="font-size:12px;font-weight:600;margin-bottom:8px;color:#475569">Iframe Preview:</div>
                        <iframe src="<?= Helpers::e($offer['iframe_url']) ?>"
                                width="<?= (int)($offer['iframe_width'] ?? 728) ?>"
                                height="<?= (int)($offer['iframe_height'] ?? 90) ?>"
                                style="border:1px solid #CBD5E1;max-width:100%;display:block"
                                sandbox="allow-scripts allow-same-origin"></iframe>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Caps &amp; Limits</span></div>
                <div class="card-body">
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Daily Conv. Cap</label>
                            <input type="number" name="daily_cap" class="form-control" value="<?= $offer['daily_cap'] ?? '0' ?>" min="0">
                            <div class="form-hint">0 = unlimited</div>
                        </div>
                        <div class="form-group">
                            <label>Total Conv. Cap</label>
                            <input type="number" name="total_cap" class="form-control" value="<?= $offer['total_cap'] ?? '0' ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><span class="card-title">Visibility &amp; Status</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <?php foreach(['active','paused','pending','expired'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($offer['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Visibility</label>
                        <select name="visibility" class="form-control">
                            <option value="public" <?= ($offer['visibility'] ?? '') === 'public' ? 'selected' : '' ?>>Public (all affiliates)</option>
                            <option value="require_approval" <?= ($offer['visibility'] ?? '') === 'require_approval' ? 'selected' : '' ?>>Require Approval</option>
                            <option value="private" <?= ($offer['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Private (invite only)</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="require_approval" <?= ($offer['require_approval'] ?? 0) ? 'checked' : '' ?>>
                        <label>Require affiliate approval to access</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">
                <?= $isEdit ? 'Update Offer' : 'Create Offer' ?>
            </button>
        </div>
    </div>
</form>

<style>
/* ── GEO Multi-Select Widget ───────────────────────────────────────── */
.geo-ms {
    position: relative;
}
.geo-ms-trigger {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    padding: 5px 36px 5px 8px;
    min-height: 42px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    background: #fff;
    cursor: text;
    position: relative;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.geo-ms-trigger:hover { border-color: #CBD5E1; }
.geo-ms-trigger.open {
    border-color: #6366F1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    border-radius: 6px 6px 0 0;
}
.geo-ms-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 10px;
    pointer-events: none;
    transition: transform 0.18s;
}
.geo-ms-trigger.open .geo-ms-arrow { transform: translateY(-50%) rotate(180deg); }
.geo-ms-tags {
    display: contents;
}
.geo-ms-tag {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: #EEF2FF;
    color: #4338CA;
    border: 1px solid #C7D2FE;
    border-radius: 4px;
    padding: 2px 5px 2px 8px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1.4;
}
.geo-ms-tag-rm {
    background: none;
    border: none;
    color: #818CF8;
    cursor: pointer;
    padding: 0 2px;
    font-size: 14px;
    line-height: 1;
    border-radius: 2px;
}
.geo-ms-tag-rm:hover { color: #DC2626; background: #FEE2E2; }
.geo-ms-search {
    border: none;
    outline: none;
    background: transparent;
    font-size: 13px;
    color: #1E293B;
    flex: 1;
    min-width: 100px;
    padding: 2px 2px;
}
.geo-ms-search::placeholder { color: #94A3B8; }
.geo-ms-dropdown {
    display: none;
    position: absolute;
    top: calc(100% - 1px);
    left: 0;
    right: 0;
    z-index: 9999;
    background: #fff;
    border: 1px solid #6366F1;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.10);
}
.geo-ms-dropdown.open { display: block; }
.geo-ms-list {
    max-height: 240px;
    overflow-y: auto;
    overscroll-behavior: contain;
}
.geo-ms-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #334155;
    user-select: none;
    transition: background 0.08s;
}
.geo-ms-opt:hover { background: #F8FAFC; }
.geo-ms-opt.checked { background: #EEF2FF; color: #3730A3; }
.geo-ms-opt input[type=checkbox] {
    width: 15px;
    height: 15px;
    accent-color: #4F46E5;
    cursor: pointer;
    flex-shrink: 0;
    margin: 0;
}
.geo-ms-code {
    font-size: 11px;
    font-weight: 700;
    color: #64748B;
    background: #F1F5F9;
    border-radius: 3px;
    padding: 1px 5px;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}
.geo-ms-opt.checked .geo-ms-code { background: #DDD6FE; color: #4338CA; }
.geo-ms-label { flex: 1; }
.geo-ms-opt.geo-ms-hidden { display: none; }
.geo-ms-select-all { border-bottom: 1px solid #F1F5F9; font-weight: 600; color: #4F46E5; }
.geo-ms-select-all:hover { background: #EEF2FF; }
.geo-ms-divider { height: 1px; background: #F1F5F9; }
.geo-ms-global { font-weight: 600; color: #0369A1; border-bottom: 1px solid #F1F5F9; }
.geo-ms-global:hover { background: #F0F9FF; }
.geo-ms-global.checked { background: #E0F2FE; color: #075985; }
.geo-ms-global .geo-ms-label strong { color: inherit; }
.geo-ms-global-icon { font-size: 14px; flex-shrink: 0; }
.geo-ms-tag.geo-ms-tag-global { background: #E0F2FE; color: #0369A1; border-color: #BAE6FD; }
.geo-ms-tag.geo-ms-tag-global .geo-ms-tag-rm { color: #38BDF8; }
.geo-ms-tag.geo-ms-tag-global .geo-ms-tag-rm:hover { color: #DC2626; background: #FEE2E2; }
.geo-ms-empty {
    padding: 16px;
    text-align: center;
    color: #94A3B8;
    font-size: 13px;
    display: none;
}
</style>
<script>
var defaultTrackingDomain = '<?= Helpers::e($defaultDomain) ?>';
function updateTrackingPreview() {
    var sel = document.getElementById('trackingDomainSelect');
    var domain = sel && sel.value ? sel.value : defaultTrackingDomain;
    var offerId = '<?= $isEdit ? $offer['id'] : '{offer_id}' ?>';
    document.getElementById('trackingPreviewCode').textContent =
        'https://' + domain + '/click/' + offerId + '?aff_id={aff_id}&click_id={click_id}';
}

// Track the last focused landing page URL input
var activeUrlField = null;
document.addEventListener('focus', function(e) {
    if (e.target.classList.contains('landing-url-input')) {
        activeUrlField = e.target;
    }
}, true);

function insertUrlMacro(macro) {
    if (!activeUrlField) { alert('Click inside a URL field first, then click a macro.'); return; }
    var pos = activeUrlField.selectionStart || activeUrlField.value.length;
    var val = activeUrlField.value;
    activeUrlField.value = val.slice(0, pos) + macro + val.slice(pos);
    activeUrlField.focus();
    activeUrlField.setSelectionRange(pos + macro.length, pos + macro.length);
}

// ── RevShare payout label / input switcher ────────────────────────────────
function updatePayoutTypeUI() {
    var sel      = document.getElementById('payoutTypeSelect');
    var label    = document.getElementById('payoutAmountLabel');
    var input    = document.getElementById('payoutAmountInput');
    var hint     = document.getElementById('payoutAmountHint');
    var revLabel = document.getElementById('revenueAmountLabel');
    var revInput = document.getElementById('revenueAmountInput');
    if (!sel || !label || !input) return;

    if (sel.value === 'RevShare') {
        label.textContent = 'Affiliate Payout (%)';
        input.max = '100';
        input.placeholder = 'e.g. 30 for 30%';
        if (hint) hint.style.display = 'block';
        if (revLabel) revLabel.textContent = 'Advertiser Revenue (%)';
        if (revInput) { revInput.max = '100'; revInput.placeholder = 'e.g. 100 for 100%'; }
    } else {
        label.textContent = 'Affiliate Payout ($)';
        input.removeAttribute('max');
        input.placeholder = '0.00';
        if (hint) hint.style.display = 'none';
        if (revLabel) revLabel.textContent = 'Advertiser Revenue ($)';
        if (revInput) { revInput.removeAttribute('max'); revInput.placeholder = '0.00'; }
    }
}
function checkRevenueWarning() {
    var warn = document.getElementById('revenue-warning');
    var rev  = parseFloat(document.getElementById('revenueAmountInput').value) || 0;
    var pay  = parseFloat(document.getElementById('payoutAmountInput').value)  || 0;
    var type = (document.getElementById('payoutTypeSelect') || {}).value;
    if (warn && type !== 'RevShare') {
        warn.style.display = (rev > 0 && rev <= pay) ? 'block' : 'none';
    } else if (warn) {
        warn.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('payoutTypeSelect');
    if (sel) {
        sel.addEventListener('change', updatePayoutTypeUI);
        sel.addEventListener('change', checkRevenueWarning);
        updatePayoutTypeUI(); // run immediately for edit form
    }
    var rev = document.getElementById('revenueAmountInput');
    var pay = document.getElementById('payoutAmountInput');
    if (rev) rev.addEventListener('input', checkRevenueWarning);
    if (pay) pay.addEventListener('input', checkRevenueWarning);
    checkRevenueWarning();
});

function addCountryPayoutRow() {
    var container = document.getElementById('country-payout-container');
    var div = document.createElement('div');
    div.className = 'form-row cols-2';
    div.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:8px';
    div.innerHTML = '<input type="text" name="country_payout_country[]" class="form-control" placeholder="US" maxlength="2" style="text-transform:uppercase;max-width:80px">'
        + '<input type="number" name="country_payout_amount[]" class="form-control" step="0.01" min="0" placeholder="5.00">'
        + '<button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">&#10005; Remove</button>';
    container.appendChild(div);
    div.querySelector('input').focus();
}

var urlCount = <?= count($existingUrls ?? [1]) ?>;
function addLandingPage() {
    urlCount++;
    var container = document.getElementById('landing-pages-container');
    var div = document.createElement('div');
    div.className = 'landing-url-row';
    div.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap';
    div.innerHTML =
          '<input type="text" name="landing_page_names[]" class="form-control landing-name-input" placeholder="LP' + urlCount + ' name (e.g. Hero v2)" style="max-width:200px;flex:0 0 200px">'
        + '<input type="url" name="landing_pages[]" class="form-control landing-url-input" placeholder="https://advertiser.com/lp' + urlCount + '?cid={click_id}" style="flex:1;min-width:240px">'
        + '<button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm" style="white-space:nowrap">&#10005; Remove</button>';
    container.appendChild(div);
    var firstInput = div.querySelector('input[name="landing_page_names[]"]');
    if (firstInput) firstInput.focus();
}

// ── Device Links (offer_links) ───────────────────────────────────────────
<?php
$_olGeoHtml = '<option value="">— Any GEO —</option>';
foreach ($countries as $_c => $_n) {
    $_olGeoHtml .= '<option value="' . htmlspecialchars($_c, ENT_QUOTES) . '">' . Helpers::flag($_c) . ' ' . htmlspecialchars($_c . ' — ' . $_n, ENT_QUOTES) . '</option>';
}
?>
var olGeoOptions = <?= json_encode($_olGeoHtml) ?>;

function addOfferLink() {
    var container = document.getElementById('offer-links-container');
    var row = document.createElement('div');
    row.className = 'ol-row';
    row.style.cssText = 'background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px;margin-bottom:10px;position:relative';
    row.innerHTML = [
        '<button type="button" onclick="removeOfferLinkRow(this)" class="btn btn-danger btn-sm"',
        '  style="position:absolute;top:10px;right:10px;padding:2px 8px;line-height:1.2">&#10005;</button>',
        '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px">',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">Label <span style="font-weight:400;color:#94A3B8">(optional)</span></label>',
        '    <input type="text" name="ol_label[]" class="form-control" placeholder="e.g. Mobile Tier 1">',
        '  </div>',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">Device *</label>',
        '    <select name="ol_device[]" class="form-control">',
        '      <option value="all">All Devices</option>',
        '      <option value="desktop">Desktop</option>',
        '      <option value="mobile">Mobile</option>',
        '      <option value="tablet">Tablet</option>',
        '    </select>',
        '  </div>',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">GEO</label>',
        '    <select name="ol_geo[]" class="form-control">' + olGeoOptions + '</select>',
        '  </div>',
        '</div>',
        '<div class="form-group mb-2">',
        '  <label style="font-size:11px;font-weight:600">Offer URL *</label>',
        '  <input type="url" name="ol_url[]" class="form-control landing-url-input" required',
        '         placeholder="https://advertiser.com/lp?cid={click_id}">',
        '</div>',
        '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">Payout ($)</label>',
        '    <input type="number" name="ol_payout[]" class="form-control" step="0.01" min="0" placeholder="0.00">',
        '  </div>',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">Revenue ($)</label>',
        '    <input type="number" name="ol_revenue[]" class="form-control" step="0.01" min="0" placeholder="0.00">',
        '  </div>',
        '  <div class="form-group mb-0">',
        '    <label style="font-size:11px;font-weight:600">Status</label>',
        '    <select name="ol_status[]" class="form-control">',
        '      <option value="active">Active</option>',
        '      <option value="inactive">Inactive</option>',
        '    </select>',
        '  </div>',
        '</div>',
    ].join('');
    container.appendChild(row);
    document.getElementById('ol-empty').style.display = 'none';
    row.querySelector('input[name="ol_url[]"]').focus();
}

function removeOfferLinkRow(btn) {
    var row = btn.closest('.ol-row');
    if (row) row.remove();
    var container = document.getElementById('offer-links-container');
    if (container && container.querySelectorAll('.ol-row').length === 0) {
        document.getElementById('ol-empty').style.display = '';
    }
}

// ── GEO Multi-Select Widget ───────────────────────────────────────────────
function geoFlag(code) {
    var o = 0x1F1A5;
    return String.fromCodePoint(o + code.charCodeAt(0)) + String.fromCodePoint(o + code.charCodeAt(1));
}
(function () {
    var hiddenSel  = document.getElementById('geo-select-hidden');
    var trigger    = document.getElementById('geo-ms-trigger');
    var tagsBox    = document.getElementById('geo-ms-tags');
    var searchEl   = document.getElementById('geo-ms-search');
    var dropdown   = document.getElementById('geo-ms-dropdown');
    var selectAll  = document.getElementById('geo-ms-all');
    var globalCb   = document.getElementById('geo-ms-global');
    var globalOpt  = document.getElementById('geo-ms-global-opt');
    var ms         = document.getElementById('geo-ms');
    var opts       = Array.from(dropdown.querySelectorAll('.geo-ms-opt[data-code]'));

    // ── helpers ──────────────────────────────────────────────────────────
    function isGlobal() {
        var o = hiddenSel.querySelector('option[value="GLOBAL"]');
        return o && o.selected;
    }

    function setGlobal(checked) {
        var o = hiddenSel.querySelector('option[value="GLOBAL"]');
        if (o) o.selected = checked;
        globalCb.checked = checked;
        globalOpt.classList.toggle('checked', checked);
        if (checked) {
            // Deselect all individual countries
            opts.forEach(function(lbl) {
                lbl.querySelector('input').checked = false;
                lbl.classList.remove('checked');
                var opt = hiddenSel.querySelector('option[value="' + lbl.dataset.code + '"]');
                if (opt) opt.selected = false;
            });
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        renderTags();
    }

    function selectedCodes() {
        return Array.from(hiddenSel.selectedOptions)
            .map(function(o){ return o.value; })
            .filter(function(v){ return v !== 'GLOBAL'; });
    }

    function syncHidden(code, checked) {
        var o = hiddenSel.querySelector('option[value="' + code + '"]');
        if (o) o.selected = checked;
    }

    function renderTags() {
        tagsBox.querySelectorAll('.geo-ms-tag').forEach(function(t){ t.remove(); });

        if (isGlobal()) {
            var tag = document.createElement('span');
            tag.className = 'geo-ms-tag geo-ms-tag-global';
            tag.dataset.code = 'GLOBAL';
            tag.innerHTML = '🌐 Global<button type="button" class="geo-ms-tag-rm" aria-label="Remove Global">&#10005;</button>';
            tag.querySelector('.geo-ms-tag-rm').addEventListener('click', function(e){
                e.stopPropagation();
                setGlobal(false);
            });
            tagsBox.appendChild(tag);
            searchEl.placeholder = '';
            return;
        }

        var sel = selectedCodes();
        sel.forEach(function(code) {
            var tag = document.createElement('span');
            tag.className = 'geo-ms-tag';
            tag.dataset.code = code;
            tag.innerHTML = geoFlag(code) + ' ' + code + '<button type="button" class="geo-ms-tag-rm" aria-label="Remove ' + code + '">&#10005;</button>';
            tag.querySelector('.geo-ms-tag-rm').addEventListener('click', function(e){
                e.stopPropagation();
                setChecked(code, false);
            });
            tagsBox.appendChild(tag);
        });
        searchEl.placeholder = sel.length ? '' : 'Search countries...';
    }

    function setChecked(code, checked) {
        if (checked && isGlobal()) setGlobal(false);
        syncHidden(code, checked);
        var lbl = dropdown.querySelector('.geo-ms-opt[data-code="' + code + '"]');
        if (lbl) {
            lbl.querySelector('input').checked = checked;
            lbl.classList.toggle('checked', checked);
        }
        var tag = tagsBox.querySelector('.geo-ms-tag[data-code="' + code + '"]');
        if (checked && !tag) renderTags();
        else if (!checked && tag) { tag.remove(); searchEl.placeholder = selectedCodes().length ? '' : 'Search countries...'; }
        updateSelectAll();
    }

    function updateSelectAll() {
        var visible = opts.filter(function(o){ return !o.classList.contains('geo-ms-hidden'); });
        var checkedVisible = visible.filter(function(o){ return o.querySelector('input').checked; });
        selectAll.checked = visible.length > 0 && checkedVisible.length === visible.length;
        selectAll.indeterminate = checkedVisible.length > 0 && checkedVisible.length < visible.length;
    }

    // ── open / close ─────────────────────────────────────────────────────
    function openDropdown() {
        dropdown.classList.add('open');
        trigger.classList.add('open');
        searchEl.focus();
    }

    function closeDropdown() {
        dropdown.classList.remove('open');
        trigger.classList.remove('open');
        searchEl.value = '';
        filterOptions('');
    }

    function filterOptions(q) {
        q = q.trim().toLowerCase();
        opts.forEach(function(lbl){
            var code = lbl.dataset.code.toLowerCase();
            var name = lbl.dataset.name;
            var show = !q || code.startsWith(q) || name.includes(q);
            lbl.classList.toggle('geo-ms-hidden', !show);
        });
        updateSelectAll();
    }

    // ── events ───────────────────────────────────────────────────────────
    trigger.addEventListener('click', function(e){
        if (e.target.classList.contains('geo-ms-tag-rm')) return;
        dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
    });

    searchEl.addEventListener('input', function(){
        filterOptions(this.value);
        if (!dropdown.classList.contains('open')) openDropdown();
    });

    dropdown.addEventListener('mousedown', function(e){ e.preventDefault(); });

    dropdown.addEventListener('change', function(e){
        var cb = e.target;
        if (cb.type !== 'checkbox') return;
        if (cb.id === 'geo-ms-global') { setGlobal(cb.checked); return; }
        if (cb.id === 'geo-ms-all') return;
        setChecked(cb.value, cb.checked);
    });

    selectAll.addEventListener('change', function(){
        if (selectAll.checked && isGlobal()) setGlobal(false);
        var visible = opts.filter(function(o){ return !o.classList.contains('geo-ms-hidden'); });
        visible.forEach(function(lbl){
            var code = lbl.dataset.code;
            var cb   = lbl.querySelector('input');
            cb.checked = selectAll.checked;
            lbl.classList.toggle('checked', selectAll.checked);
            syncHidden(code, selectAll.checked);
        });
        renderTags();
    });

    document.addEventListener('click', function(e){
        if (!ms.contains(e.target) && dropdown.classList.contains('open')) closeDropdown();
    });

    // ── init ─────────────────────────────────────────────────────────────
    opts.forEach(function(lbl){
        if (lbl.querySelector('input').checked) lbl.classList.add('checked');
    });
    // Initialise global checkbox state
    if (isGlobal()) {
        globalCb.checked = true;
        globalOpt.classList.add('checked');
    }
    renderTags();
    updateSelectAll();
</script>

<script>
// ── Dynamic Offer Type loading based on Category selection ──────────────────
function loadOfferTypes(categoryId) {
    const typeSelect = document.getElementById('offerTypeSelect');
    const legacyInput = document.getElementById('offerCategoryLegacy');

    // Update legacy category name from the selected option text
    const catSelect = document.getElementById('offerCategorySelect');
    if (legacyInput && catSelect) {
        const selectedOpt = catSelect.options[catSelect.selectedIndex];
        legacyInput.value = selectedOpt ? selectedOpt.textContent.trim() : '';
    }

    if (!categoryId) {
        typeSelect.innerHTML = '<option value="">— Select Category First —</option>';
        return;
    }

    typeSelect.innerHTML = '<option value="">Loading...</option>';
    typeSelect.disabled = true;

    fetch('/admin/offer-categories?action=types&category_id=' + categoryId)
        .then(r => r.json())
        .then(data => {
            typeSelect.disabled = false;
            if (data.success && data.types.length > 0) {
                let html = '<option value="">— Select Offer Type —</option>';
                const currentTypeId = '<?= (int)($offer['offer_type_id'] ?? 0) ?>';
                data.types.forEach(t => {
                    const sel = (t.id == currentTypeId) ? ' selected' : '';
                    html += '<option value="' + t.id + '"' + sel + '>' + t.name + '</option>';
                });
                typeSelect.innerHTML = html;
            } else {
                typeSelect.innerHTML = '<option value="">— No types for this category —</option>';
            }
        })
        .catch(() => {
            typeSelect.disabled = false;
            typeSelect.innerHTML = '<option value="">— Error loading types —</option>';
        });
}

// On page load, if category is already selected (edit mode), load its types
(function() {
    const catSelect = document.getElementById('offerCategorySelect');
    if (catSelect && catSelect.value) {
        loadOfferTypes(catSelect.value);
    }
})();
</script>
<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>

