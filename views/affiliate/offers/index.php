<?php
$shortenerEnabled = (Config::get('config', 'shortener.enabled') ?? '1') === '1';
// Build domain list JSON for JS
$_domainListJson = json_encode(array_values(array_map(function($d){
    return ['id'=>(int)$d['id'],'domain'=>$d['domain'],'label'=>$d['label'],'is_default'=>(int)$d['is_default']];
}, $trackingDomains ?? [])));
?>
<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>
<script>
var _csrfToken   = '<?= Auth::generateCsrf() ?>';
var _trackDomains = <?= $_domainListJson ?>;
</script>

<div class="page-header">
    <div><h1>Available Offers</h1><p>Browse and apply for CPA offers</p></div>
    <div style="display:flex;gap:8px;align-items:center">
        <span class="text-muted text-sm"><?= number_format(count($offers)) ?> offers</span>
        <button id="btn-grid" onclick="setView('grid')" class="btn btn-secondary btn-sm" title="Grid View">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button id="btn-list" onclick="setView('list')" class="btn btn-secondary btn-sm" title="List View">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
    </div>
</div>

<div class="card mb-3 filter-card filter-open" id="offers-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Offers</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body">
        <form method="GET" action="/affiliate/offers" id="offers-filter-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Offer name..." value="<?= Helpers::e($qFilter ?? '') ?>" style="min-width:140px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php if (!empty($dbCategories)): ?>
                        <?php foreach ($dbCategories as $dbc): ?>
                        <option value="<?= $dbc['id'] ?>" <?= (($catIdFilter ?? 0) == $dbc['id']) ? 'selected' : '' ?>><?= Helpers::e($dbc['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Offer Type</label>
                <select name="offer_type_id" class="form-control">
                    <option value="">All Types</option>
                    <?php if (!empty($dbOfferTypes)): ?>
                        <?php foreach ($dbOfferTypes as $ot): ?>
                        <option value="<?= $ot['id'] ?>" <?= (($typeIdFilter ?? 0) == $ot['id']) ? 'selected' : '' ?>><?= Helpers::e($ot['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Payout Type</label>
                <select name="payout_type" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['CPA','CPL','CPS','CPM','CPC','RevShare','Trial'] as $pt): ?>
                    <option value="<?= $pt ?>" <?= ($typeFilter ?? '') === $pt ? 'selected' : '' ?>><?= $pt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Country</label>
                <input type="text" name="country" class="form-control" placeholder="US" value="<?= Helpers::e($countryFilter ?? '') ?>" maxlength="2" style="width:70px;text-transform:uppercase">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Device</label>
                <select name="device" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['desktop','mobile','tablet'] as $dv): ?>
                    <option value="<?= $dv ?>" <?= ($deviceFilter ?? '') === $dv ? 'selected' : '' ?>><?= ucfirst($dv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Offer ID</label>
                <input type="number" name="offer_id" class="form-control" placeholder="ID..." value="<?= ($idFilter ?? 0) > 0 ? (int)$idFilter : '' ?>" min="1" style="width:80px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Access</label>
                <select name="access_filter" class="form-control">
                    <option value="">All Offers</option>
                    <option value="active" <?= ($accessFilter ?? '') === 'active' ? 'selected' : '' ?>>Active Offers</option>
                    <option value="request" <?= ($accessFilter ?? '') === 'request' ? 'selected' : '' ?>>Request / Need Approval</option>
                    <option value="all_access" <?= ($accessFilter ?? '') === 'all_access' ? 'selected' : '' ?>>Access for All</option>
                </select>
            </div>
            <div style="align-self:flex-end;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($qFilter || $catFilter || $typeFilter || $offerTypeFilter || $countryFilter || $deviceFilter || ($idFilter ?? 0) > 0 || ($accessFilter ?? '')): ?>
                <a href="/affiliate/offers" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </div>
    </div>
</div>

<?php
// Pre-process offers
// Determine default tracking domain base
$_defaultDomain = null;
foreach (($trackingDomains ?? []) as $_td) {
    if ($_td['is_default']) { $_defaultDomain = $_td['domain']; break; }
}
if (!$_defaultDomain && !empty($trackingDomains)) {
    $_defaultDomain = $trackingDomains[0]['domain'];
}
// Build the base URL using the tracking domain (falls back to default domain then app URL)
$_trackBase = Helpers::trackingUrl();

$_offerData = [];
foreach ($offers as $o) {
    $isRevShare = ($o['payout_type'] ?? '') === 'RevShare';
    $rules      = $affPayoutRules[$o['id']] ?? [];
    // Base custom payout: adv_custom_payouts > affiliate_offers.custom_payout > offer default
    $basePayout  = $o['adv_custom_payout'] !== null ? (float)$o['adv_custom_payout']
                 : ($o['custom_payout']     !== null ? (float)$o['custom_payout']
                 : (float)$o['payout_amount']);
    // Offer-level device/country payouts (set in offer Advanced Payout section)
    $offerDevPayouts = !empty($o['device_payouts'])  ? (json_decode($o['device_payouts'],  true) ?: []) : [];
    $offerCntPayouts = !empty($o['country_payouts']) ? (json_decode($o['country_payouts'], true) ?: []) : [];
    $isCustom    = ($o['adv_custom_payout'] !== null || $o['custom_payout'] !== null
                 || !empty($rules['countries']) || !empty($rules['devices'])
                 || !empty($offerDevPayouts) || !empty($offerCntPayouts));
    // Effective daily cap: affiliate-specific overrides offer default
    $effCap = isset($rules['cap']) && $rules['cap'] > 0 ? $rules['cap']
            : ($affGlobalCap > 0 ? $affGlobalCap : (int)$o['daily_cap']);
    $_offerData[$o['id']] = [
        'payout'          => $basePayout,
        'isRevShare'      => $isRevShare,
        'isCustom'        => $isCustom,
        'effCap'          => $effCap,
        'affCap'          => $rules['cap'] ?? 0,
        'countries'       => $rules['countries'] ?? [],
        'devices'         => $rules['devices']   ?? [],
        'offerDevPayouts' => $offerDevPayouts,
        'offerCntPayouts' => $offerCntPayouts,
        'trackUrl'    => $_trackBase.'/click/'.$o['id'].'?aff='.($aff['affiliate_code'] ?? ''),
        'geos'        => $o['geo_targeting']    ? json_decode($o['geo_targeting'],    true) : [],
        'devTargeting'=> $o['device_targeting'] ? json_decode($o['device_targeting'], true) : [],
        'hasAccess'   => $o['access_status'] === 'approved',
        'isPending'   => $o['access_status'] === 'pending',
        'isBlocked'   => $o['access_status'] === 'blocked',
        'lpList'      => !empty($o['landing_pages']) ? json_decode($o['landing_pages'],true) : [],
        'banners'     => !empty($o['banner_urls']) ? json_decode($o['banner_urls'],true) : [],
        'offerLinks'  => $offerLinksMap[$o['id']] ?? [],
    ];
}
?>

<!-- ── GRID VIEW ─────────────────────────────────────────────────────────── -->
<div id="view-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;align-items:stretch">
<?php if(empty($offers)): ?>
<div style="grid-column:1/-1"><div class="empty-state"><div class="icon">&#127991;</div><h3>No offers available</h3><p>Check back later for new offers.</p></div></div>
<?php else: ?>
<?php foreach($offers as $o):
    $d = $_offerData[$o['id']];
    extract($d, EXTR_PREFIX_ALL, 'g');
?>
<div class="card offer-3d-card" style="display:flex;flex-direction:column;min-width:0;border-radius:18px;border:1px solid rgba(226,232,240,0.85);background:linear-gradient(145deg,#ffffff 0%,#f8fafc 100%);box-shadow:0 10px 25px -5px rgba(0,0,0,0.05),0 8px 10px -6px rgba(0,0,0,0.02);transition:all 0.28s ease;overflow:hidden">
    <?php if (!empty(trim($o['offer_image'] ?? ''))): ?>
    <div style="height:140px;background:linear-gradient(135deg,#4F46E5,#7C3AED);position:relative;overflow:hidden">
        <img src="<?= Helpers::e($o['offer_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;opacity:.92" onerror="this.parentElement.style.display='none'">
        <div style="position:absolute;bottom:8px;left:8px">
            <span style="font-size:11px;background:rgba(15,23,42,0.75);color:#fff;backdrop-filter:blur(8px);border-radius:6px;padding:2px 8px;font-weight:700;font-family:monospace">OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div style="padding:22px;flex:1;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px">
            <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
                <?php if($o['offer_type'] ?? ''): ?>
                <span class="badge badge-success" style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#DCFCE7,#F0FDF4);color:#15803D;border:1px solid #BBF7D0"><?= Helpers::e($o['offer_type']) ?></span>
                <?php endif; ?>
                <span class="badge badge-info" style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#DBEAFE,#EFF6FF);color:#1D4ED8;border:1px solid #BFDBFE"><?= Helpers::e($o['payout_type']) ?></span>
                <?php if(!empty($o['require_approval'])): ?>
                <span class="badge badge-warning" style="border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;background:linear-gradient(135deg,#FEF9C3,#FFFBE0);color:#92400E;border:1px solid #FDE68A">Approval Required</span>
                <?php endif; ?>
            </div>
            <?php if($o['category']): ?>
            <span class="badge badge-muted" style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0"><?= Helpers::e($o['category']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
            <h3 style="font-size:16.5px;font-weight:800;color:var(--text);margin:0;line-height:1.35"><?= Helpers::e($o['name']) ?></h3>
            <?php if (empty(trim($o['offer_image'] ?? ''))): ?>
            <span style="font-size:11px;background:linear-gradient(135deg,#EFF6FF,#DBEAFE);color:#1D4ED8;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-weight:700;font-family:monospace;white-space:nowrap;flex-shrink:0">OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?></span>
            <?php endif; ?>
        </div>

        <?php if($o['description']): ?>
        <div style="font-size:13px;color:var(--text-muted);line-height:1.5">
            <span><?= Helpers::e(mb_substr($o['description'], 0, 120)) ?><?= mb_strlen($o['description']) > 120 ? '…' : '' ?></span>
            <button type="button" onclick="showOfferDetailsModal(<?= $o['id'] ?>)" style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:8px;color:#4F46E5;background:#EEF2FF;border:1px solid #C7D2FE;cursor:pointer;margin-left:4px" title="View offer details">📄 Details</button>
        </div>
        <?php endif; ?>

        <?php if($o['preview_url'] ?? ''): ?>
        <div>
            <a href="<?= Helpers::e($o['preview_url']) ?>" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#0EA5E9;text-decoration:none;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px;padding:4px 10px">
                👁 Preview Offer
            </a>
        </div>
        <?php endif; ?>

        <!-- 3D Payout & Target Specs -->
        <div style="display:flex;gap:14px;background:linear-gradient(135deg,#F0FDF4 0%,#ECFDF5 100%);border:1px solid #A7F3D0;border-radius:14px;padding:12px 16px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.7);margin-top:auto">
            <?php if(!$g_isRevShare): ?>
            <div>
                <div class="stat-label" style="font-size:10px;font-weight:800;color:#047857;letter-spacing:.05em;text-transform:uppercase">PAYOUT</div>
                <div style="font-size:22px;font-weight:800;color:#059669">$<?= number_format($g_payout,2) ?></div>
                <?php if($g_isCustom): ?><span style="font-size:10px;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:8px;font-weight:700">✓ Custom</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div style="flex:1;min-width:0">
                <div class="stat-label" style="font-size:10px;font-weight:800;color:#64748B;letter-spacing:.05em;text-transform:uppercase">GEO &amp; DEVICES</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px">
                    <?php if(empty($g_geos)): ?>
                    <span style="display:inline-flex;align-items:center;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;color:var(--text-muted)">Global</span>
                    <?php else: ?>
                        <?php 
                        $visibleGeos = array_slice($g_geos, 0, 4);
                        $hiddenGeos = array_slice($g_geos, 4);
                        foreach($visibleGeos as $_gc): 
                        ?>
                        <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap;color:#1E40AF"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span>
                        <?php endforeach; ?>
                        
                        <?php if(count($hiddenGeos) > 0): ?>
                        <button type="button" id="btn-more-geo-<?= $o['id'] ?>" onclick="document.getElementById('more-geos-<?= $o['id'] ?>').style.display='contents'; this.style.display='none'" style="display:inline-flex;align-items:center;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;cursor:pointer;color:var(--text-muted)">+<?= count($hiddenGeos) ?> more</button>
                        <span id="more-geos-<?= $o['id'] ?>" style="display:none;">
                            <?php foreach($hiddenGeos as $_gc): ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap;color:#1E40AF"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(!empty($g_devTargeting)): ?>
                        <span style="color:#CBD5E1;margin:0 2px">|</span>
                        <?php
                        $_dtIcons=['mobile'=>'📱','tablet'=>'📲','desktop'=>'🖥️'];
                        foreach($g_devTargeting as $_dv):
                            $_dvIcon = $_dtIcons[$_dv] ?? '📡';
                        ?>
                        <span style="display:inline-flex;align-items:center;gap:3px;background:#F8FAFC;border:1px solid #E2E8F0;color:#475569;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap">
                            <?= $_dvIcon ?> <?= ucfirst($_dv) ?>
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($g_effCap > 0): ?>
        <div class="text-sm text-muted">
            Daily Cap: <strong><?= number_format($g_effCap) ?></strong>
            <?php if($g_affCap > 0): ?><span style="font-size:10px;background:#EDE9FE;color:#5B21B6;padding:1px 6px;border-radius:6px;margin-left:4px;font-weight:700">Your Cap</span><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if($o['terms'] ?? ''): ?>
        <details>
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748B;list-style:none;display:flex;align-items:center;gap:4px">
                <span>📝</span> Terms &amp; Conditions
            </summary>
            <div style="margin-top:6px;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:12px;color:#78350F;line-height:1.6;white-space:pre-line;max-height:200px;overflow-y:auto">
                <?= Helpers::e($o['terms']) ?>
            </div>
        </details>
        <?php endif; ?>
    </div>

    <!-- Card Action Footer -->
    <div style="padding:16px 22px;border-top:1px solid var(--border,#e2e8f0);background:rgba(248,250,252,0.7)">
        <?php if($g_hasAccess): ?>
            <div class="text-sm text-muted" style="margin-bottom:8px;font-size:12.5px;color:#065F46;font-weight:700;display:flex;align-items:center;gap:4px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Approved — <?= number_format($o['today_clicks']) ?> clicks today</span>
            </div>
            <div class="copy-group" style="gap:6px">
                <input type="text" id="ol-<?= $o['id'] ?>" class="form-control" value="<?= Helpers::e($g_trackUrl) ?>" readonly style="font-size:12px;border-radius:8px;background:#fff">
                <button class="btn btn-secondary btn-sm" data-copy="ol-<?= $o['id'] ?>" style="border-radius:8px;font-weight:700">Copy</button>
                <button class="btn btn-sm" onclick="openGenModal(<?= $o['id'] ?>, '<?= Helpers::e($g_trackUrl) ?>', <?= (int)count($g_lpList) ?>)" style="white-space:nowrap;background:#0EA5E9;border:none;color:#fff;border-radius:8px;font-weight:700;padding:0 12px" title="Build link with tracking parameters">🔗 Build</button>
                <?php if($shortenerEnabled): ?>
                <button class="btn btn-sm" onclick="shortenLink(<?= $o['id'] ?>, '<?= Helpers::e($g_trackUrl) ?>', 'grid')" id="shorten-btn-grid-<?= $o['id'] ?>" style="white-space:nowrap;background:#7C3AED;border:none;color:#fff;border-radius:8px;font-weight:700;padding:0 12px">✂ Short</button>
                <?php endif; ?>
            </div>
        <?php elseif($g_isPending): ?>
            <div style="background:linear-gradient(135deg,#FEF9C3,#FEF08A);border:1px solid #FDE047;border-radius:10px;padding:10px 14px;font-size:12.5px;color:#713F12;font-weight:700">
                ⏳ Pending Approval — waiting for admin review
            </div>
        <?php elseif($g_isBlocked): ?>
            <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:8px">
                <span style="font-size:16px">🚫</span>
                <span style="font-size:13px;color:#B91C1C;font-weight:700">Access Blocked</span>
            </div>
        <?php else: ?>
            <?php if(!empty($o['require_approval'])): ?>
            <button class="btn btn-primary" style="width:100%;border-radius:10px;padding:11px 18px;font-size:14px;font-weight:800;background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 100%);box-shadow:0 4px 14px rgba(124,58,237,0.35);justify-content:center" onclick="requestApproval(<?= $o['id'] ?>)">Request Access</button>
            <?php else: ?>
            <form method="POST"><?= Helpers::csrf() ?><input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                <button class="btn btn-primary" style="width:100%;border-radius:10px;padding:11px 18px;font-size:14px;font-weight:800;background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 100%);box-shadow:0 4px 14px rgba(124,58,237,0.35);justify-content:center">⚡ Get Tracking Link</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ── LIST VIEW ─────────────────────────────────────────────────────────── -->
<div id="view-list" style="display:none">
<?php if(empty($offers)): ?>
<div class="empty-state"><div class="icon">&#127991;</div><h3>No offers available</h3></div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table style="font-size:13px">
            <thead><tr>
                <th style="width:70px">ID</th><th>OFFER</th><th>TYPE</th><th>PAYOUT</th><th>GEO</th><th>DEVICES</th><th>STATUS</th><th>TRACKING LINK</th>
            </tr></thead>
            <tbody>
            <?php foreach($offers as $o):
                $d = $_offerData[$o['id']];
                extract($d, EXTR_PREFIX_ALL, 'l');
            ?>
            <tr>
                <td style="white-space:nowrap">
                    <span style="display:inline-block;background:#EFF6FF;color:#2563EB;border-radius:4px;padding:2px 7px;font-size:11px;font-weight:700;font-family:monospace">
                        OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?>
                    </span>
                </td>
                <td style="max-width:280px">
                    <div class="fw-bold"><?= Helpers::e($o['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px"><?= Helpers::e($o['category']?:'') ?>
                        <?php if($o['offer_type']??''): ?>· <span class="badge badge-success" style="font-size:10px"><?= Helpers::e($o['offer_type']) ?></span><?php endif; ?>
                    </div>
                    <?php if($o['description'] ?? ''): ?>
                    <div style="font-size:11px;color:#64748B;line-height:1.4;margin-bottom:4px">
                        <?= Helpers::e(mb_substr($o['description'], 0, 100)) ?><?= mb_strlen($o['description']) > 100 ? '…' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;margin-top:3px">
                        <button type="button" onclick="showOfferDetailsModal(<?= $o['id'] ?>)" style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:#4F46E5;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:4px;padding:2px 7px;cursor:pointer">&#128196; Details</button>
                        <?php if($o['preview_url'] ?? ''): ?>
                        <a href="<?= Helpers::e($o['preview_url']) ?>" target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:#0EA5E9;text-decoration:none;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:4px;padding:2px 7px">
                            &#128065; Preview
                        </a>
                        <?php endif; ?>
                        <?php if($o['terms'] ?? ''): ?>
                        <button type="button" onclick="toggleListTerms(<?= $o['id'] ?>)"
                            style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:#92400E;background:#FFFBEB;border:1px solid #FDE68A;border-radius:4px;padding:2px 7px;cursor:pointer">
                            &#128221; T&amp;C
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php if($o['terms'] ?? ''): ?>
                    <div id="lterms-<?= $o['id'] ?>" style="display:none;margin-top:6px;padding:8px 10px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;font-size:11px;color:#78350F;line-height:1.6;white-space:pre-line;max-height:140px;overflow-y:auto">
                        <?= Helpers::e($o['terms']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span></td>
                <td>
                    <?php if(!$l_isRevShare): ?><div style="font-size:16px;font-weight:700;color:var(--secondary)">$<?= number_format($l_payout,2) ?></div><?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
                    <?php if($l_isCustom): ?><div style="font-size:10px"><span style="background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:8px;font-weight:700">&#10003; Custom</span></div><?php endif; ?>
                    <?php if(!empty($l_countries)||!empty($l_devices)): ?>
                    <div style="font-size:11px;color:#6B7280;margin-top:2px">
                        <?php foreach(array_slice($l_countries,0,2) as $cr): ?>
                        <span><?= Helpers::flag($cr['country']) ?><?= Helpers::e($cr['country']) ?>:$<?= number_format((float)$cr['payout'],2) ?></span>
                        <?php endforeach; ?>
                        <?php
                        $devIconsList=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
                        foreach(array_slice($l_devices,0,2) as $dr):
                        ?>
                        <span><?= $devIconsList[$dr['device']]??'&#128225;' ?><?= ucfirst($dr['device']) ?>:$<?= number_format((float)$dr['payout'],2) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if($l_effCap > 0): ?>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                        Cap: <strong><?= number_format($l_effCap) ?></strong>
                        <?php if($l_affCap > 0): ?><span style="font-size:10px;background:#EDE9FE;color:#5B21B6;padding:1px 5px;border-radius:6px;margin-left:3px">Yours</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($l_offerCntPayouts)): ?>
                    <div style="margin-top:3px;display:flex;flex-wrap:wrap;gap:3px">
                        <?php foreach($l_offerCntPayouts as $ocr): ?>
                        <span style="background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap">
                            <?= Helpers::flag($ocr['country']) ?><?= Helpers::e($ocr['country']) ?>:$<?= number_format((float)$ocr['payout'],2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($l_offerDevPayouts)): ?>
                    <div style="margin-top:3px;display:flex;flex-wrap:wrap;gap:3px">
                        <?php
                        $odIconsList=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
                        foreach($l_offerDevPayouts as $odev => $opay):
                        ?>
                        <span style="background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap">
                            <?= $odIconsList[$odev]??'&#128225;' ?><?= ucfirst($odev) ?>:$<?= number_format((float)$opay,2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($l_offerLinks)): ?>
                    <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:3px">
                        <?php
                        $olIcons2 = ['mobile'=>'📱','tablet'=>'📲','desktop'=>'🖥️','all'=>'🌐'];
                        foreach($l_offerLinks as $ol):
                            $olIcon2 = $olIcons2[$ol['device_type']] ?? '📡';
                            $olGeo2  = !empty($ol['geo_country']) ? '/' . Helpers::flag($ol['geo_country']) . strtoupper($ol['geo_country']) : '';
                        ?>
                        <span style="background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap">
                            <?= $olIcon2 ?>&nbsp;<?= ucfirst($ol['device_type'] === 'all' ? 'All' : $ol['device_type']) . $olGeo2 ?>&nbsp;$<?= number_format((float)$ol['payout_rate'],2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><?php if(empty($l_geos)): ?><span class="text-muted" style="font-size:11px">Global</span><?php else: ?><div style="display:flex;flex-wrap:wrap;gap:2px;max-width:200px"><?php foreach($l_geos as $_gc): ?><span style="display:inline-flex;align-items:center;gap:2px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:4px;padding:1px 5px;font-size:10px;font-weight:600;white-space:nowrap"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span><?php endforeach; ?></div><?php endif; ?></td>
                <td>
                    <?php if(!empty($l_devTargeting)):
                        $_ldtIcons=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
                        foreach($l_devTargeting as $_ldv):
                    ?>
                    <div style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:5px;padding:2px 7px;font-size:11px;font-weight:600;margin-bottom:2px;white-space:nowrap">
                        <?= $_ldtIcons[$_ldv] ?? '&#128225;' ?> <?= ucfirst($_ldv) ?>
                    </div>
                    <?php endforeach; else: ?>
                    <span class="text-muted" style="font-size:11px">All</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($l_hasAccess): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php elseif($l_isPending): ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php elseif($l_isBlocked): ?>
                        <span class="badge badge-danger">Blocked</span>
                    <?php else: ?>
                        <span class="badge badge-muted">Not Applied</span>
                    <?php endif; ?>
                </td>
                <td style="min-width:260px">
                    <?php if($l_hasAccess): ?>
                    <div class="copy-group" style="flex-wrap:wrap;gap:4px">
                        <input type="text" id="ll-<?= $o['id'] ?>" class="form-control" value="<?= Helpers::e($l_trackUrl) ?>" readonly style="font-size:11px">
                        <button class="btn btn-secondary btn-sm" data-copy="ll-<?= $o['id'] ?>">Copy</button>
                        <button class="btn btn-sm" onclick="openGenModal(<?= $o['id'] ?>, '<?= Helpers::e($l_trackUrl) ?>', <?= (int)count($l_lpList) ?>)" style="white-space:nowrap;background:#0EA5E9;border-color:#0EA5E9;color:#fff;font-size:11px">&#128279; Build</button>
                        <?php if($shortenerEnabled): ?>
                        <button class="btn btn-sm" onclick="shortenLink(<?= $o['id'] ?>, '<?= Helpers::e($l_trackUrl) ?>', 'list')" id="shorten-btn-list-<?= $o['id'] ?>" style="white-space:nowrap;background:#7C3AED;border-color:#7C3AED;color:#fff;font-size:11px">&#9986; Short</button>
                        <?php endif; ?>
                    </div>
                    <?php if($shortenerEnabled): ?>
                    <div id="short-result-list-<?= $o['id'] ?>" style="display:none;margin-top:4px">
                        <div style="display:flex;align-items:center;gap:6px">
                            <span style="font-size:11px;font-weight:600;color:#7C3AED;white-space:nowrap">&#128279; Short:</span>
                            <input type="text" id="short-url-list-<?= $o['id'] ?>" class="form-control" readonly style="font-size:11px;border-color:#7C3AED;background:#F5F3FF">
                            <button class="btn btn-sm" data-copy="short-url-list-<?= $o['id'] ?>" style="background:#7C3AED;color:#fff;border:none;white-space:nowrap;font-size:11px">Copy</button>
                            <a id="short-open-list-<?= $o['id'] ?>" href="#" target="_blank" style="background:#5B21B6;color:#fff;padding:4px 8px;border-radius:4px;font-size:11px;text-decoration:none">&#8599;</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if(count($l_lpList)>1): ?>
                    <div style="margin-top:6px">
                        <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Landing Page</label>
                        <select id="lp-lsel-<?= $o['id'] ?>" class="form-control" style="font-size:12px" onchange="updateListTrackUrl(<?= $o['id'] ?>)">
                            <option value="">Auto (Rotation)</option>
                            <?php foreach($l_lpList as $lpI=>$lpUrl): ?>
                            <option value="<?= $lpI ?>">Landing Page <?= $lpI+1 ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($l_banners)): ?>
                    <details style="margin-top:6px">
                        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:var(--primary)">&#128444; Banners (<?= count($l_banners) ?>)</summary>
                        <div style="margin-top:6px;display:flex;flex-direction:column;gap:6px">
                            <?php foreach($l_banners as $sz=>$url): ?>
                            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 8px">
                                <div style="font-size:11px;font-weight:600;color:var(--text-muted);margin-bottom:3px"><?= Helpers::e($sz) ?></div>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                                    <input type="text" value="<?= Helpers::e($url) ?>" readonly class="form-control" id="lbn-<?= $o['id'] ?>-<?= str_replace('x','_',$sz) ?>" style="font-size:11px;flex:1;min-width:150px">
                                    <button class="btn btn-secondary btn-sm" data-copy="lbn-<?= $o['id'] ?>-<?= str_replace('x','_',$sz) ?>">Copy</button>
                                    <a href="<?= Helpers::e($url) ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>
                    <?php if(!empty($o['iframe_url'])): ?>
                    <details style="margin-top:6px">
                        <summary style="cursor:pointer;font-size:12px;font-weight:600;color:var(--primary)">&#9707; Iframe Creative</summary>
                        <div style="margin-top:6px">
                            <div style="display:flex;gap:6px;align-items:center;margin-bottom:6px">
                                <input type="text" value="<?= Helpers::e($o['iframe_url']) ?>" readonly class="form-control" id="lif-<?= $o['id'] ?>" style="font-size:11px">
                                <button class="btn btn-secondary btn-sm" data-copy="lif-<?= $o['id'] ?>">Copy</button>
                            </div>
                            <div style="width:100%;overflow:hidden"><iframe src="<?= Helpers::e($o['iframe_url']) ?>" width="<?= (int)($o['iframe_width']??728) ?>" height="<?= (int)($o['iframe_height']??90) ?>" style="border:1px solid #CBD5E1;display:block;max-width:100%;width:100%" sandbox="allow-scripts allow-same-origin"></iframe></div>
                        </div>
                    </details>
                    <?php endif; ?>
                    <?php elseif($l_isPending): ?>
                    <span class="text-muted text-sm">Awaiting approval</span>
                    <?php elseif($l_isBlocked): ?>
                    <div style="display:inline-flex;align-items:center;gap:6px;background:#FEF2F2;border:1px solid #FECACA;border-radius:6px;padding:6px 10px">
                        <span style="font-size:13px">&#128683;</span>
                        <span style="font-size:12px;color:#B91C1C;font-weight:600">Access Blocked</span>
                    </div>
                    <?php else: ?>
                        <?php if(!empty($o['require_approval'])): ?>
                        <button class="btn btn-primary btn-sm" onclick="requestApproval(<?= $o['id'] ?>)">Request Access</button>
                        <?php else: ?>
                        <form method="POST" style="display:inline"><?= Helpers::csrf() ?><input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                            <button class="btn btn-primary btn-sm">Get Link</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>

<script>
function setView(v) {
    document.getElementById('view-grid').style.display = v==='grid' ? 'grid' : 'none';
    document.getElementById('view-list').style.display = v==='list' ? 'block' : 'none';
    document.getElementById('btn-grid').style.opacity  = v==='grid' ? '1' : '.45';
    document.getElementById('btn-list').style.opacity  = v==='list' ? '1' : '.45';
    localStorage.setItem('aff_offers_view', v);
}
function toggleGDesc(id) {
    var s = document.getElementById('gdesc-short-' + id);
    var f = document.getElementById('gdesc-full-' + id);
    if (!s || !f) return;
    var collapsed = f.style.display === 'none';
    s.style.display = collapsed ? 'none' : '';
    f.style.display = collapsed ? '' : 'none';
}
function toggleListTerms(id) {
    var el = document.getElementById('lterms-' + id);
    if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
}
(function(){
    var saved = localStorage.getItem('aff_offers_view') || 'grid';
    setView(saved);
})();

function updateTrackUrl(offerId) {
    const sel = document.getElementById('lp-sel-' + offerId);
    const input = document.getElementById('ol-' + offerId);
    if (!sel || !input) return;
    const base = input.value.replace(/&lp=\d+/, '');
    const lpVal = sel.value;
    input.value = lpVal !== '' ? base + '&lp=' + lpVal : base;
}
function updateListTrackUrl(offerId) {
    const sel = document.getElementById('lp-lsel-' + offerId);
    const input = document.getElementById('ll-' + offerId);
    if (!sel || !input) return;
    const base = input.value.replace(/&lp=\d+/, '');
    const lpVal = sel.value;
    input.value = lpVal !== '' ? base + '&lp=' + lpVal : base;
}

function shortenLink(offerId, url, viewType = 'grid') {
    var btn = document.getElementById('shorten-btn-' + viewType + '-' + offerId);
    var resultBox = document.getElementById('short-result-' + viewType + '-' + offerId);
    var shortInput = document.getElementById('short-url-' + viewType + '-' + offerId);
    var shortOpen  = document.getElementById('short-open-' + viewType + '-' + offerId);

    // Always use the current value of the live input field
    var input = document.getElementById(viewType === 'grid' ? 'ol-' + offerId : 'll-' + offerId);
    var liveUrl = input ? input.value : url;

    // If already shortened for the same URL, just toggle display
    if (shortInput.value && shortInput.dataset.srcUrl === liveUrl) {
        resultBox.style.display = resultBox.style.display === 'none' ? 'block' : 'none';
        return;
    }

    var origText = btn.innerHTML;
    btn.innerHTML = '&#9203; ...';
    btn.disabled  = true;

    fetch('/affiliate/shorten', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_csrfToken) + '&url=' + encodeURIComponent(liveUrl)
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        btn.innerHTML = origText;
        btn.disabled  = false;
        if (data.error) {
            alert('Shortener error: ' + data.error);
            return;
        }
        shortInput.value    = data.short_url;
        shortInput.dataset.srcUrl = liveUrl;
        shortOpen.href      = data.short_url;
        resultBox.style.display = '';
        btn.innerHTML = '&#128279; Short';
        btn.style.background = '#7C3AED';
        btn.style.color = '#fff';
        btn.style.borderColor = '#7C3AED';
    })
    .catch(function(e) {
        btn.innerHTML = origText;
        btn.disabled  = false;
        alert('Failed to shorten link. Please try again.');
    });
}

function requestApproval(offerId) {
    document.getElementById('modal-offer-id').value = offerId;
    document.getElementById('approvalModal').style.display = 'flex';
}
</script>

<!-- Request Access Modal -->
<div id="approvalModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <h3 style="margin-bottom:16px">Request Access</h3>
        <p style="font-size:13px;color:#64748B;margin-bottom:16px">This offer requires approval. Briefly describe your promotion method:</p>
        <form method="POST" action="/affiliate/offers">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="apply_offer_id" id="modal-offer-id">
            <textarea name="promotion_description" class="form-control" rows="4" placeholder="e.g. I run a review blog with 10k monthly visitors targeting..." required style="margin-bottom:12px"></textarea>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('approvalModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ── GENERATE / BUILD TRACKING LINK MODAL ────────────────────────────── -->
<div id="genLinkModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:14px;padding:28px 28px 24px;max-width:580px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.25);max-height:90vh;overflow-y:auto">

        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div>
                <h3 style="margin:0;font-size:17px;font-weight:700">&#128279; Build Tracking Link</h3>
                <p style="margin:4px 0 0;font-size:12px;color:#64748B">Add click_id, source &amp; sub parameters so conversions reach your tracker</p>
            </div>
            <button onclick="closeGenModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94A3B8;line-height:1">&times;</button>
        </div>

        <!-- Domain Selection -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">TRACKING DOMAIN</label>
            <select id="gen-domain" class="form-control" onchange="rebuildGenUrl()" style="font-size:12px">
                <?php foreach ($trackingDomains as $td): ?>
                <option value="<?= Helpers::e($td['domain']) ?>" <?= $td['is_default'] ? 'selected' : '' ?>>
                    <?= Helpers::e($td['domain']) ?><?= $td['label'] ? ' — '.Helpers::e($td['label']) : '' ?><?= $td['is_default'] ? ' (Default)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:10px;color:#94A3B8;margin-top:3px">Select which tracking domain to use for this link</div>
        </div>

        <!-- Base URL (read-only) -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">BASE TRACKING URL</label>
            <input type="text" id="gen-base-url" class="form-control" readonly style="font-size:11px;background:#F8FAFC;color:#475569">
        </div>

        <!-- Landing Page selection — populated dynamically per-offer -->
        <div id="gen-lp-wrap" style="margin-bottom:16px;display:none">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#127757; LANDING PAGE</label>
            <select id="gen-lp" class="form-control" onchange="rebuildGenUrl()" style="font-size:12px">
                <option value="">Auto (Rotation)</option>
            </select>
            <div style="font-size:10px;color:#94A3B8;margin-top:3px">Pick a specific landing page or leave on Auto for rotation. Appended as <code>&amp;lp=N</code>.</div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px">
            <!-- Click ID — REQUIRED -->
            <div style="border:1px solid #C7D2FE;border-radius:8px;padding:11px;background:#F5F3FF">
                <label style="font-size:11px;font-weight:700;color:#4338CA;letter-spacing:.5px;display:block;margin-bottom:4px">
                    CLICK ID <span style="background:#4F46E5;color:#fff;border-radius:3px;padding:1px 5px;font-size:9px;font-weight:700;margin-left:3px">REQUIRED</span>
                </label>
                <input type="text" id="gen-clickid" class="form-control" placeholder="e.g. {clickid} or ##CLICKID##" oninput="rebuildGenUrl()" style="font-size:12px;border-color:#A5B4FC">
                <div style="font-size:10px;color:#6366F1;margin-top:4px">Your tracker's click ID token → appended as <code>click_id=</code> → returned as <code>{click_id}</code> in your global postback</div>
            </div>
            <!-- Source -->
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">
                    SOURCE <span style="color:#94A3B8;font-weight:400">(traffic source)</span>
                </label>
                <input type="text" id="gen-source" class="form-control" placeholder="e.g. facebook, push, native" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;source=</code></div>
            </div>
        </div>

        <!-- Sub params — sub_id_1→sub2, sub_id_2→sub3, …, sub_id_5→sub6 -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:12px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_1 <span style="color:#94A3B8;font-weight:400">→ stored in sub2</span></label>
                <input type="text" id="gen-sub1" class="form-control" placeholder="e.g. {affid} or {campaign}" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_1=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_2 <span style="color:#94A3B8;font-weight:400">→ stored in sub3</span></label>
                <input type="text" id="gen-sub2" class="form-control" placeholder="e.g. {creative}" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_2=</code></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:12px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_3 <span style="color:#94A3B8;font-weight:400">→ stored in sub4</span></label>
                <input type="text" id="gen-sub3" class="form-control" placeholder="e.g. {keyword}" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_3=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_4 <span style="color:#94A3B8;font-weight:400">→ stored in sub5</span></label>
                <input type="text" id="gen-sub4" class="form-control" placeholder="e.g. {placement}" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_4=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_5 <span style="color:#94A3B8;font-weight:400">→ stored in sub6</span></label>
                <input type="text" id="gen-sub5" class="form-control" placeholder="e.g. {zone}" oninput="rebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_5=</code></div>
            </div>
        </div>

        <!-- Postback info callout -->
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#1E40AF">
            <div style="font-weight:700;margin-bottom:5px">&#128161; How conversions reach your tracker</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;font-size:11px">
                <span style="background:#DBEAFE;border-radius:4px;padding:2px 7px">Advertiser fires conversion</span>
                <span style="color:#93C5FD">→</span>
                <span style="background:#DBEAFE;border-radius:4px;padding:2px 7px">Our tracker records it</span>
                <span style="color:#93C5FD">→</span>
                <span style="background:#DBEAFE;border-radius:4px;padding:2px 7px">Your global postback URL fires with <code>{click_id}</code> &amp; <code>{payout}</code></span>
                <span style="color:#93C5FD">→</span>
                <span style="background:#D1FAE5;border-radius:4px;padding:2px 7px;color:#065F46;font-weight:600">✅ Conversion in your tracker</span>
            </div>
            <div style="margin-top:7px;color:#3B82F6">Set up your global postback URL on the <a href="/affiliate/postbacks" style="color:#2563EB;font-weight:600">Postback Setup</a> page.</div>
        </div>

        <!-- Generated URL output -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9989; GENERATED TRACKING LINK</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input type="text" id="gen-output" class="form-control" readonly style="font-size:11px;background:#F0FDF4;border-color:#86EFAC;font-family:monospace">
                <button class="btn btn-secondary btn-sm" onclick="copyGenOutput()" id="gen-copy-btn" style="white-space:nowrap">Copy</button>
            </div>
        </div>

        <!-- Short link from modal -->
        <?php if($shortenerEnabled): ?>
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9986; SHORT LINK</label>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                <input type="text" id="gen-short-output" class="form-control" readonly placeholder="Click 'Generate Short Link' to shorten..." style="font-size:11px;background:#F5F3FF;border-color:#C4B5FD;font-family:monospace">
                <button class="btn btn-sm" id="gen-shorten-btn" onclick="genModalShorten()" style="white-space:nowrap;background:#7C3AED;border-color:#7C3AED;color:#fff">&#9986; Shorten</button>
                <a id="gen-short-open" href="#" target="_blank" style="display:none;background:#5B21B6;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;text-decoration:none">&#8599;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick presets -->
        <div style="margin-bottom:20px">
            <div style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;margin-bottom:8px">QUICK PRESETS — click to fill click ID field with your tracker's macro</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <button class="btn btn-sm" onclick="applyPreset('{clickid}')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{clickid}</button>
                <button class="btn btn-sm" onclick="applyPreset('{click_id}')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{click_id}</button>
                <button class="btn btn-sm" onclick="applyPreset('[clickid]')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">[clickid]</button>
                <button class="btn btn-sm" onclick="applyPreset('##CLICKID##')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">##CLICKID##</button>
                <button class="btn btn-sm" onclick="applyPreset('%7Bclickid%7D')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">%7Bclickid%7D</button>
            </div>
        </div>

        <!-- Footer buttons -->
        <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #E2E8F0;padding-top:16px">
            <button class="btn btn-secondary" onclick="resetGenModal()">&#8635; Reset</button>
            <button class="btn btn-primary" onclick="applyGenToOffer()" style="background:#0EA5E9;border-color:#0EA5E9">Apply to Offer Link</button>
            <button class="btn btn-secondary" onclick="closeGenModal()">Close</button>
        </div>
    </div>
</div>

<script>
var _genOfferId   = null;
var _genBaseUrl   = '';
var _genOfferId_raw = null; // offer id used for offer_id param

function _getDomainBase() {
    var sel = document.getElementById('gen-domain');
    if (!sel) return '';
    var domain = sel.value || '';
    return 'https://' + domain.replace(/^https?:\/\//, '').replace(/\/$/, '');
}

function _rebuildBaseFromDomain() {
    // Reconstruct the base tracking URL using the selected domain
    // The path portion is everything after the original domain
    if (!_genBaseUrl) return '';
    // Extract path+query from the current base
    var tmp = _genBaseUrl.replace(/^https?:\/\/[^\/]+/, '');
    return _getDomainBase() + tmp;
}

function openGenModal(offerId, baseUrl, lpCount) {
    _genOfferId     = offerId;
    _genOfferId_raw = offerId;
    // Strip any previously appended params (source, sub_id_1-5, click_id) so base is always clean
    var clean = baseUrl
        .replace(/[&?]source=[^&]*/g, '')
        .replace(/[&?]sub_id_[1-9]=[^&]*/g, '')
        .replace(/[&?]sub[1-6]=[^&]*/g, '')
        .replace(/[&?]click_id=[^&]*/g, '')
        .replace(/[&?]lp=\d+/g, '')
        .replace(/&&/g, '&')
        .replace(/\?&/, '?');
    // Strip domain from clean URL — store only the path+query
    _genBaseUrl = clean.replace(/^https?:\/\/[^\/]+/, '');

    // Populate landing-page selector if this offer has multiple LPs
    var lpWrap = document.getElementById('gen-lp-wrap');
    var lpSel  = document.getElementById('gen-lp');
    if (lpWrap && lpSel) {
        lpSel.innerHTML = '<option value="">Auto (Rotation)</option>';
        var n = parseInt(lpCount || 0, 10);
        if (n > 1) {
            for (var i = 0; i < n; i++) {
                var opt = document.createElement('option');
                opt.value = i;
                opt.textContent = 'Landing Page ' + (i + 1);
                lpSel.appendChild(opt);
            }
            lpWrap.style.display = '';
        } else {
            lpWrap.style.display = 'none';
        }
    }

    resetGenFields();
    // Set domain selector to match the domain of the incoming URL if present
    var incomingDomain = clean.match(/^https?:\/\/([^\/]+)/);
    if (incomingDomain) {
        var sel = document.getElementById('gen-domain');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === incomingDomain[1]) {
                sel.selectedIndex = i; break;
            }
        }
    }
    rebuildGenUrl();
    document.getElementById('genLinkModal').style.display = 'flex';
}

function closeGenModal() {
    document.getElementById('genLinkModal').style.display = 'none';
}

function resetGenFields() {
    ['gen-source','gen-clickid','gen-sub1','gen-sub2','gen-sub3','gen-sub4','gen-sub5'].forEach(function(id){
        document.getElementById(id).value = '';
    });
    var lp = document.getElementById('gen-lp');
    if (lp) lp.value = '';
    var so = document.getElementById('gen-short-output');
    if (so) so.value = '';
    var gso = document.getElementById('gen-short-open');
    if (gso) { gso.href = '#'; gso.style.display = 'none'; }
}

function resetGenModal() {
    resetGenFields();
    rebuildGenUrl();
}

function applyPreset(macro) {
    document.getElementById('gen-clickid').value = macro;
    rebuildGenUrl();
}

function rebuildGenUrl() {
    var domainBase = _getDomainBase();
    var pathPart   = _genBaseUrl || '';
    var base       = domainBase + pathPart;
    // Ensure offer_id and aff_id params are included (they're already in pathPart from original URL)
    var sep    = base.indexOf('?') === -1 ? '?' : '&';
    var parts  = [];
    var cid    = document.getElementById('gen-clickid').value.trim();
    var src    = document.getElementById('gen-source').value.trim();
    var sub1   = document.getElementById('gen-sub1').value.trim();
    var sub2   = document.getElementById('gen-sub2').value.trim();
    var sub3   = document.getElementById('gen-sub3').value.trim();
    var sub4   = document.getElementById('gen-sub4').value.trim();
    var sub5   = document.getElementById('gen-sub5').value.trim();

    if (cid)  parts.push('click_id=' + cid);       // raw — macros like {clickid} must not be encoded
    if (src)  parts.push('source='   + encodeURIComponent(src));
    if (sub1) parts.push('sub_id_1=' + encodeURIComponent(sub1));  // → stored in sub2
    if (sub2) parts.push('sub_id_2=' + encodeURIComponent(sub2));  // → stored in sub3
    if (sub3) parts.push('sub_id_3=' + encodeURIComponent(sub3));  // → stored in sub4
    if (sub4) parts.push('sub_id_4=' + encodeURIComponent(sub4));  // → stored in sub5
    if (sub5) parts.push('sub_id_5=' + encodeURIComponent(sub5));  // → stored in sub6

    var lpEl = document.getElementById('gen-lp');
    if (lpEl && lpEl.value !== '') parts.push('lp=' + encodeURIComponent(lpEl.value));

    var finalUrl = parts.length > 0 ? base + sep + parts.join('&') : base;
    document.getElementById('gen-base-url').value = base;
    document.getElementById('gen-output').value   = finalUrl;
    // Reset short link when URL changes
    var so = document.getElementById('gen-short-output');
    if (so) so.value = '';
    var gso = document.getElementById('gen-short-open');
    if (gso) { gso.href = '#'; gso.style.display = 'none'; }
}

function copyGenOutput() {
    var el  = document.getElementById('gen-output');
    var btn = document.getElementById('gen-copy-btn');
    el.select(); el.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch(e) { navigator.clipboard.writeText(el.value); }
    btn.textContent = '✓ Copied!';
    btn.style.background = '#22C55E'; btn.style.color = '#fff'; btn.style.borderColor = '#22C55E';
    setTimeout(function(){ btn.textContent = 'Copy'; btn.style.background=''; btn.style.color=''; btn.style.borderColor=''; }, 2000);
}

function applyGenToOffer() {
    var url = document.getElementById('gen-output').value;
    if (!url || !_genOfferId) return;
    // Update both grid input (ol-) and list input (ll-)
    var gridIn = document.getElementById('ol-' + _genOfferId);
    var listIn = document.getElementById('ll-' + _genOfferId);
    if (gridIn) gridIn.value = url;
    if (listIn) listIn.value = url;
    closeGenModal();
}

function genModalShorten() {
    var url = document.getElementById('gen-output').value;
    if (!url) { alert('Please build a tracking link first.'); return; }
    var btn = document.getElementById('gen-shorten-btn');
    var si  = document.getElementById('gen-short-output');
    var gso = document.getElementById('gen-short-open');
    // If already shortened for same URL, just show
    if (si && si.dataset.srcUrl === url && si.value) {
        si.style.display = '';
        return;
    }
    btn.textContent = '...';
    btn.disabled = true;
    var fd = new FormData();
    fd.append('url', url);
    fd.append('_token', _csrfToken);
    fetch('/affiliate/shorten', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.innerHTML = '&#9986; Shorten';
            btn.disabled = false;
            if (d.error) { alert('Shortener error: ' + d.error); return; }
            si.value = d.short_url;
            si.dataset.srcUrl = url;
            if (gso) { gso.href = d.short_url; gso.style.display = 'inline'; }
        })
        .catch(function(){
            btn.innerHTML = '&#9986; Shorten';
            btn.disabled = false;
            alert('Failed to shorten link. Please try again.');
        });
}

// Close modal on backdrop click
document.getElementById('genLinkModal').addEventListener('click', function(e) {
    if (e.target === this) closeGenModal();
});
</script>

<?php
$jsOfferDetails = [];
foreach($offers as $o) {
    $jsOfferDetails[$o['id']] = [
        'name' => $o['name'],
        'desc' => $o['description'] ?? '',
        'terms' => $o['terms'] ?? ''
    ];
}
?>
<script>
var _offerDetails = <?= json_encode($jsOfferDetails) ?>;
function showOfferDetailsModal(id) {
    var data = _offerDetails[id];
    if(!data) return;
    document.getElementById('od-modal-title').textContent = data.name;
    document.getElementById('od-modal-desc').innerHTML = data.desc.replace(/\n/g, '<br>');
    var tEl = document.getElementById('od-modal-terms-wrap');
    if(data.terms) {
        document.getElementById('od-modal-terms').innerHTML = data.terms;
        tEl.style.display = 'block';
    } else {
        tEl.style.display = 'none';
    }
    document.getElementById('offer-details-modal').style.display = 'flex';
}
</script>

<!-- Offer Details Modal -->
<div id="offer-details-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.65);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);z-index:9999;align-items:center;justify-content:center;padding:20px;animation:modalFadeIn 0.25s ease">
    <div class="modal-3d-box" style="background:linear-gradient(145deg,#ffffff 0%,#f8fafc 100%);border-radius:20px;border:1px solid rgba(226,232,240,0.85);padding:26px;max-width:620px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25),0 12px 24px -8px rgba(124,58,237,0.2),inset 0 1px 0 rgba(255,255,255,0.95);animation:modalPop3D 0.28s cubic-bezier(0.34,1.56,0.64,1)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border,#e2e8f0)">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 12px rgba(124,58,237,0.3)">📄</div>
                <h3 id="od-modal-title" style="margin:0;font-size:18.5px;font-weight:800;color:#0F172A;line-height:1.3"></h3>
            </div>
            <button type="button" onclick="document.getElementById('offer-details-modal').style.display='none'" style="width:32px;height:32px;border-radius:50%;background:#F1F5F9;border:1px solid #E2E8F0;font-size:18px;line-height:1;cursor:pointer;color:#64748B;display:flex;align-items:center;justify-content:center;transition:all 0.15s ease" onmouseover="this.style.background='#EF4444';this.style.color='#fff';this.style.borderColor='#EF4444'" onmouseout="this.style.background='#F1F5F9';this.style.color='#64748B';this.style.borderColor='#E2E8F0'">&times;</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding-right:4px">
            <div id="od-modal-desc" style="font-size:14px;color:#334155;line-height:1.65;margin-bottom:16px;white-space:pre-wrap"></div>
            <div id="od-modal-terms-wrap" style="display:none;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:1px solid #FDE68A;border-radius:14px;padding:16px;margin-top:14px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.7)">
                <div style="font-weight:800;color:#92400E;font-size:12px;margin-bottom:8px;text-transform:uppercase;letter-spacing:.07em;display:flex;align-items:center;gap:5px"><span>📝</span> Terms &amp; Conditions</div>
                <div id="od-modal-terms" style="font-size:13px;color:#78350F;line-height:1.65;white-space:pre-wrap"></div>
            </div>
        </div>
        <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--border,#e2e8f0);text-align:right">
            <button type="button" onclick="document.getElementById('offer-details-modal').style.display='none'" class="btn btn-secondary" style="border-radius:10px;padding:9px 22px;font-weight:700">Close</button>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
