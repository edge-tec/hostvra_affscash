<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>
<script>
var _csrfToken    = '<?= Auth::generateCsrf() ?>';
<?php
$_ihDomainListJson = json_encode(array_values(array_map(function($d){
    return ['id'=>(int)$d['id'],'domain'=>$d['domain'],'label'=>$d['label'],'is_default'=>(int)$d['is_default']];
}, $trackingDomains ?? [])));
?>
var _ihTrackDomains = <?= $_ihDomainListJson ?>;
</script>

<div class="page-header">
    <div>
        <h1>&#127968; In-House Offers</h1>
        <p>Exclusive offers managed directly by the network</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span class="text-muted text-sm"><?= number_format(count($offers)) ?> offers</span>
        <button id="btn-grid" onclick="ihSetView('grid')" class="btn btn-secondary btn-sm" title="Grid View">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button id="btn-list" onclick="ihSetView('list')" class="btn btn-secondary btn-sm" title="List View">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 filter-card filter-open" id="inhouse-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter In-House Offers</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Search in-house offers..." value="<?= Helpers::e($qFilter ?? '') ?>" style="min-width:200px">
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
                <?php if ($qFilter || ($idFilter ?? 0) > 0 || ($accessFilter ?? '')): ?>
                <a href="/affiliate/inhouse-offers" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php
// Determine default tracking domain base for inhouse offers
$_ihDefaultDomain = null;
foreach (($trackingDomains ?? []) as $_td) {
    if ($_td['is_default']) { $_ihDefaultDomain = $_td['domain']; break; }
}
if (!$_ihDefaultDomain && !empty($trackingDomains)) {
    $_ihDefaultDomain = $trackingDomains[0]['domain'];
}
$_ihTrackBase = $_ihDefaultDomain ? 'https://' . rtrim($_ihDefaultDomain, '/') : rtrim($appUrl, '/');

$_ihData = [];
foreach ($offers as $o) {
    $affCode = $aff['affiliate_code'] ?? '';
    $geos    = $o['geo_targeting']    ? json_decode($o['geo_targeting'], true) : [];
    $devs    = $o['device_targeting'] ? json_decode($o['device_targeting'], true) : [];
    $ihRules = $affPayoutRules[$o['id']] ?? [];
    // Base custom payout: adv_custom_payouts > affiliate_offers.custom_payout > offer default
    $ihBasePayout = $o['adv_custom_payout'] !== null ? (float)$o['adv_custom_payout']
                  : ($o['custom_payout']     !== null ? (float)$o['custom_payout']
                  : (float)$o['payout_amount']);
    // Offer-level device/country payouts (set in offer Advanced Payout section)
    $ihOfferDevPayouts = !empty($o['device_payouts'])  ? (json_decode($o['device_payouts'],  true) ?: []) : [];
    $ihOfferCntPayouts = !empty($o['country_payouts']) ? (json_decode($o['country_payouts'], true) ?: []) : [];
    $ihIsCustom   = ($o['adv_custom_payout'] !== null || $o['custom_payout'] !== null
                  || !empty($ihRules['countries']) || !empty($ihRules['devices'])
                  || !empty($ihOfferDevPayouts) || !empty($ihOfferCntPayouts));
    // Effective daily cap: affiliate-specific overrides offer default
    $ihEffCap = isset($ihRules['cap']) && $ihRules['cap'] > 0 ? $ihRules['cap']
              : ($affGlobalCap > 0 ? $affGlobalCap : (int)$o['daily_cap']);
    $_ihData[$o['id']] = [
        'geos'            => $geos,
        'devs'            => $devs,
        'payout'          => $ihBasePayout,
        'isCustom'        => $ihIsCustom,
        'effCap'          => $ihEffCap,
        'affCap'          => $ihRules['cap'] ?? 0,
        'countries'       => $ihRules['countries'] ?? [],
        'devices'         => $ihRules['devices']   ?? [],
        'offerDevPayouts' => $ihOfferDevPayouts,
        'offerCntPayouts' => $ihOfferCntPayouts,
        'baseUrl'   => $_ihTrackBase . '/offer/' . $o['id'] . '?aff_id=' . urlencode($affCode),
        'hasAccess' => ($o['access_status'] ?? null) === 'approved',
        'isPending' => ($o['access_status'] ?? null) === 'pending',
    ];
}
?>

<!-- ═══════════════════════════════════════
     GRID VIEW
═══════════════════════════════════════ -->
<div id="ih-view-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;align-items:start">
<?php if (empty($offers)): ?>
<div style="grid-column:1/-1">
    <div class="empty-state"><div class="icon">&#127968;</div><h3>No in-house offers available</h3><p>Check back later for new in-house offers.</p></div>
</div>
<?php else: ?>
<?php foreach ($offers as $o): $d = $_ihData[$o['id']]; ?>
<div class="card offer-3d-card" style="display:flex;flex-direction:column;min-width:0;border-radius:18px;border:1px solid rgba(226,232,240,0.85);background:linear-gradient(145deg,#ffffff 0%,#f8fafc 100%);box-shadow:0 10px 25px -5px rgba(0,0,0,0.05),0 8px 10px -6px rgba(0,0,0,0.02);transition:all 0.28s ease;overflow:hidden">
    <div style="padding:22px;flex:1;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:6px">
            <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
                <span style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#5B21B6;border:1px solid #C4B5FD;font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:20px">🏠 IN-HOUSE</span>
                <span class="badge badge-info" style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#DBEAFE,#EFF6FF);color:#1D4ED8;border:1px solid #BFDBFE"><?= Helpers::e($o['payout_type']) ?></span>
                <?php if (!empty($o['offer_type'])): ?>
                <span style="background:linear-gradient(135deg,#DCFCE7,#F0FDF4);color:#15803D;border:1px solid #BBF7D0;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px"><?= Helpers::e($o['offer_type']) ?></span>
                <?php endif; ?>
                <?php if (!empty($o['require_approval'])): ?>
                <span class="badge badge-warning" style="border-radius:20px;padding:3px 9px;font-size:10px;font-weight:700;background:linear-gradient(135deg,#FEF9C3,#FFFBE0);color:#92400E;border:1px solid #FDE68A">Approval Required</span>
                <?php endif; ?>
            </div>
            <?php if ($o['category']): ?>
            <span class="badge badge-muted" style="border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700;background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0"><?= Helpers::e($o['category']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
            <h3 style="font-size:16.5px;font-weight:800;color:var(--text);margin:0;line-height:1.35"><?= Helpers::e($o['name']) ?></h3>
            <span style="font-size:11px;background:linear-gradient(135deg,#EFF6FF,#DBEAFE);color:#1D4ED8;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-weight:700;font-family:monospace;white-space:nowrap;flex-shrink:0">#<?= $o['id'] ?></span>
        </div>

        <?php if ($o['description']): ?>
        <div style="font-size:13px;color:var(--text-muted);line-height:1.5">
            <span id="ih-gdesc-short-<?= $o['id'] ?>"><?= Helpers::e(mb_substr($o['description'], 0, 120)) ?><?= mb_strlen($o['description']) > 120 ? '...' : '' ?></span>
            <button type="button" onclick="showOfferDetailsModal(<?= $o['id'] ?>)" style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:8px;color:#4F46E5;background:#EEF2FF;border:1px solid #C7D2FE;cursor:pointer;margin-left:4px" title="View details">📄 Details</button>
        </div>
        <?php endif; ?>

        <?php if (!empty($o['preview_url'])): ?>
        <div>
            <a href="<?= Helpers::e($o['preview_url']) ?>" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#0EA5E9;text-decoration:none;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px;padding:4px 10px">
                👁 Preview Offer
            </a>
        </div>
        <?php endif; ?>

        <!-- 3D Payout Box -->
        <div style="display:flex;gap:14px;background:linear-gradient(135deg,#F0FDF4 0%,#ECFDF5 100%);border:1px solid #A7F3D0;border-radius:14px;padding:12px 16px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.7);margin-top:auto">
            <div>
                <div class="stat-label" style="font-size:10px;font-weight:800;color:#047857;letter-spacing:.05em;text-transform:uppercase">PAYOUT</div>
                <div style="font-size:22px;font-weight:800;color:#059669">$<?= number_format($d['payout'], 2) ?></div>
                <?php if($d['isCustom']): ?><span style="font-size:10px;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:8px;font-weight:700">✓ Custom</span><?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="stat-label" style="font-size:10px;font-weight:800;color:#64748B;letter-spacing:.05em;text-transform:uppercase">GEO &amp; DEVICES</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px">
                    <?php if(empty($d['geos'])): ?>
                    <span style="display:inline-flex;align-items:center;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;color:var(--text-muted)">Global</span>
                    <?php else: ?>
                        <?php 
                        $visibleIhGeos = array_slice($d['geos'], 0, 4);
                        $hiddenIhGeos = array_slice($d['geos'], 4);
                        foreach($visibleIhGeos as $_gc): 
                        ?>
                        <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap;color:#1E40AF"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span>
                        <?php endforeach; ?>
                        
                        <?php if(count($hiddenIhGeos) > 0): ?>
                        <button type="button" onclick="this.nextElementSibling.style.display='contents'; this.style.display='none'" style="display:inline-flex;align-items:center;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;cursor:pointer;color:var(--text-muted)">+<?= count($hiddenIhGeos) ?> more</button>
                        <span style="display:none;">
                            <?php foreach($hiddenIhGeos as $_gc): ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap;color:#1E40AF"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($d['devs'])): ?>
                        <span style="color:#CBD5E1;margin:0 2px">|</span>
                        <?php
                        $_ihDevIcons=['mobile'=>'📱','tablet'=>'📲','desktop'=>'🖥️'];
                        foreach($d['devs'] as $_dv):
                            $_dvIcon = $_ihDevIcons[$_dv] ?? '📡';
                        ?>
                        <span style="display:inline-flex;align-items:center;gap:3px;background:#F8FAFC;border:1px solid #E2E8F0;color:#475569;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;white-space:nowrap">
                            <?= $_dvIcon ?> <?= ucfirst($_dv) ?>
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($d['effCap'] > 0): ?>
        <div class="text-sm text-muted">
            Daily Cap: <strong><?= number_format($d['effCap']) ?></strong>
            <?php if($d['affCap'] > 0): ?><span style="font-size:10px;background:#EDE9FE;color:#5B21B6;padding:1px 6px;border-radius:6px;margin-left:4px;font-weight:700">Your Cap</span><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($o['terms_conditions'])): ?>
        <details>
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748B;list-style:none;display:flex;align-items:center;gap:4px">
                <span>📝</span> Terms &amp; Conditions
            </summary>
            <div style="margin-top:6px;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:12px;color:#78350F;line-height:1.6;white-space:pre-line;max-height:200px;overflow-y:auto">
                <?= Helpers::e($o['terms_conditions']) ?>
            </div>
        </details>
        <?php endif ?>
    </div>

    <!-- Footer Action -->
    <div style="padding:16px 22px;border-top:1px solid var(--border,#e2e8f0);background:rgba(248,250,252,0.7)">
        <?php if ($d['hasAccess']): ?>
            <div class="text-sm text-muted" style="margin-bottom:8px;font-size:12.5px;color:#065F46;font-weight:700;display:flex;align-items:center;gap:4px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Approved — <?= (int)$o['today_clicks'] ?> clicks today</span>
            </div>
            <div class="copy-group" style="gap:6px">
                <input type="text" id="ih-ol-<?= $o['id'] ?>" class="form-control" value="<?= Helpers::e($d['baseUrl']) ?>" readonly style="font-size:12px;border-radius:8px;background:#fff">
                <button class="btn btn-secondary btn-sm" data-copy="ih-ol-<?= $o['id'] ?>" style="border-radius:8px;font-weight:700">Copy</button>
                <button class="btn btn-sm" onclick="ihOpenBuildModal(<?= $o['id'] ?>, '<?= Helpers::e($d['baseUrl']) ?>')" style="white-space:nowrap;background:#0EA5E9;border:none;color:#fff;border-radius:8px;font-weight:700;padding:0 12px" title="Build link with tracking parameters">🔗 Build</button>
                <button class="btn btn-sm" onclick="ihGenerateShort(<?= $o['id'] ?>)" id="ih-shorten-btn-<?= $o['id'] ?>" style="white-space:nowrap;background:#7C3AED;border:none;color:#fff;border-radius:8px;font-weight:700;padding:0 12px">✂ Short</button>
            </div>
        <?php elseif ($d['isPending']): ?>
            <div style="background:linear-gradient(135deg,#FEF9C3,#FEF08A);border:1px solid #FDE047;border-radius:10px;padding:10px 14px;font-size:12.5px;color:#713F12;font-weight:700;text-align:center">
                ⏳ Pending Approval — waiting for admin review
            </div>
        <?php else: ?>
            <form method="POST" action="/affiliate/inhouse-offers">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                <button type="submit" class="btn btn-primary" style="width:100%;border-radius:10px;padding:11px 18px;font-size:14px;font-weight:800;background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 100%);box-shadow:0 4px 14px rgba(124,58,237,0.35);justify-content:center">
                    <?= $o['require_approval'] ? 'Apply for Access' : '⚡ Get Tracking Link' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ═══════════════════════════════════════
     LIST VIEW
═══════════════════════════════════════ -->
<div id="ih-view-list" style="display:none">
<?php if (empty($offers)): ?>
<div class="empty-state"><div class="icon">&#127968;</div><h3>No in-house offers available</h3><p>Check back later for new in-house offers.</p></div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table style="font-size:13px">
            <thead><tr>
                <th>OFFER</th><th>TYPE</th><th>PAYOUT</th><th>GEO</th><th>DEVICES</th><th>STATUS</th><th>TRACKING LINK</th>
            </tr></thead>
            <tbody>
            <?php foreach ($offers as $o): $d = $_ihData[$o['id']]; ?>
            <tr>
                <td>
                    <div style="font-size:10px;color:#94A3B8;font-family:monospace;font-weight:600;margin-bottom:2px">#<?= $o['id'] ?></div>
                    <div class="fw-bold"><?= Helpers::e($o['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);display:flex;gap:4px;flex-wrap:wrap;margin-top:2px">
                        <span style="background:#EDE9FE;color:#5B21B6;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px">IN-HOUSE</span>
                        <?php if ($o['category']): ?><span class="badge badge-muted" style="font-size:10px"><?= Helpers::e($o['category']) ?></span><?php endif; ?>
                    </div>
                    <?php if ($o['description']): ?>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
                        <?= Helpers::e(substr($o['description'], 0, 100)) ?><?= mb_strlen($o['description']) > 100 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;align-items:center">
                        <button type="button" onclick="showOfferDetailsModal(<?= $o['id'] ?>)" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;color:#4F46E5;background:#EEF2FF;border:1px solid #C7D2FE;cursor:pointer">&#128196; Details</button>
                        <?php if (!empty($o['preview_url'])): ?>
                        <a href="<?= Helpers::e($o['preview_url']) ?>" target="_blank" rel="noopener"
                           style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;background:#0EA5E9;color:#fff;text-decoration:none">&#128065; Preview</a>
                        <?php endif; ?>
                        <?php if (!empty($o['terms_conditions'])): ?>
                        <button onclick="toggleIhListTerms(<?= $o['id'] ?>)"
                                style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;background:#FEF3C7;border:1px solid #FCD34D;color:#78350F;cursor:pointer">&#128220; T&amp;C</button>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($o['terms_conditions'])): ?>
                    <div id="ih-lterms-<?= $o['id'] ?>" style="display:none;margin-top:6px;padding:8px 10px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:6px;font-size:11px;color:#78350F;white-space:pre-wrap;max-width:360px"><?= Helpers::e($o['terms_conditions']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span>
                    <?php if (!empty($o['offer_type'])): ?>
                    <div style="margin-top:4px"><span style="background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px"><?= Helpers::e($o['offer_type']) ?></span></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-size:16px;font-weight:700;color:var(--secondary)">$<?= number_format($d['payout'], 2) ?></div>
                    <?php if($d['isCustom']): ?><div style="font-size:10px"><span style="background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:8px;font-weight:700">&#10003; Custom</span></div><?php endif; ?>
                    <?php if(!empty($d['countries'])||!empty($d['devices'])): ?>
                    <div style="font-size:11px;color:#6B7280;margin-top:2px">
                        <?php foreach(array_slice($d['countries'],0,2) as $cr): ?>
                        <span>&#127757;<?= Helpers::e($cr['country']) ?>:$<?= number_format((float)$cr['payout'],2) ?></span>
                        <?php endforeach; ?>
                        <?php
                        $ihDevIconsList=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
                        foreach(array_slice($d['devices'],0,2) as $dr):
                        ?>
                        <span><?= $ihDevIconsList[$dr['device']]??'&#128225;' ?><?= ucfirst($dr['device']) ?>:$<?= number_format((float)$dr['payout'],2) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($d['offerCntPayouts'])): ?>
                    <div style="margin-top:3px;display:flex;flex-wrap:wrap;gap:3px">
                        <?php foreach($d['offerCntPayouts'] as $ocr): ?>
                        <span style="background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap">
                            &#127757;<?= Helpers::e($ocr['country']) ?>:$<?= number_format((float)$ocr['payout'],2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($d['offerDevPayouts'])): ?>
                    <div style="margin-top:3px;display:flex;flex-wrap:wrap;gap:3px">
                        <?php
                        $ihOdIconsList=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
                        foreach($d['offerDevPayouts'] as $odev => $opay):
                        ?>
                        <span style="background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap">
                            <?= $ihOdIconsList[$odev]??'&#128225;' ?><?= ucfirst($odev) ?>:$<?= number_format((float)$opay,2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if($d['effCap'] > 0): ?>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                        Cap: <strong><?= number_format($d['effCap']) ?></strong>
                        <?php if($d['affCap'] > 0): ?><span style="font-size:10px;background:#EDE9FE;color:#5B21B6;padding:1px 5px;border-radius:6px;margin-left:3px">Yours</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><?php if(empty($d['geos'])): ?><span class="text-muted" style="font-size:11px">Global</span><?php else: ?><div style="display:flex;flex-wrap:wrap;gap:2px;max-width:200px"><?php foreach($d['geos'] as $_gc): ?><span style="display:inline-flex;align-items:center;gap:2px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:4px;padding:1px 5px;font-size:10px;font-weight:600;white-space:nowrap"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span><?php endforeach; ?></div><?php endif; ?></td>
                <td class="text-sm"><?= !empty($d['devs']) ? implode(', ', array_map('ucfirst', $d['devs'])) : 'All' ?></td>
                <td>
                    <?php if ($d['hasAccess']): ?><span class="badge badge-success">Approved</span>
                    <?php elseif ($d['isPending']): ?><span class="badge badge-warning">Pending</span>
                    <?php else: ?><span class="badge badge-muted">Not Applied</span><?php endif; ?>
                </td>
                <td style="min-width:240px">
                    <?php if ($d['hasAccess']): ?>
                    <div class="copy-group" style="flex-wrap:wrap;gap:4px">
                        <input type="text" id="ih-ll-<?= $o['id'] ?>" class="form-control"
                               value="<?= Helpers::e($d['baseUrl']) ?>" readonly style="font-size:11px">
                        <button class="btn btn-secondary btn-sm" data-copy="ih-ll-<?= $o['id'] ?>">Copy</button>
                        <button class="btn btn-sm"
                                onclick="ihOpenBuildModal(<?= $o['id'] ?>, '<?= Helpers::e($d['baseUrl']) ?>')"
                                style="white-space:nowrap;background:#0EA5E9;border-color:#0EA5E9;color:#fff;font-size:11px">&#128279; Build</button>
                    </div>
                    <?php elseif ($d['isPending']): ?>
                    <span class="text-muted text-sm">Awaiting approval</span>
                    <?php else: ?>
                    <form method="POST" action="/affiliate/inhouse-offers" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $o['require_approval'] ? 'Apply' : 'Get Link' ?>
                        </button>
                    </form>
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

<!-- ═══════════════════════════════════════
     BUILD TRACKING LINK MODAL  (in-house)
═══════════════════════════════════════ -->
<div id="ihGenLinkModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:14px;padding:28px 28px 24px;max-width:580px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.25);max-height:90vh;overflow-y:auto">

        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div>
                <h3 style="margin:0;font-size:17px;font-weight:700">&#128279; Build Tracking Link</h3>
                <p style="margin:4px 0 0;font-size:12px;color:#64748B">Add your source &amp; sub-parameters to the tracking URL</p>
            </div>
            <button onclick="ihCloseGenModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94A3B8;line-height:1">&times;</button>
        </div>

        <!-- Domain Selection -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">TRACKING DOMAIN</label>
            <select id="ih-gen-domain" class="form-control" onchange="ihRebuildGenUrl()" style="font-size:12px">
                <?php foreach ($trackingDomains as $td): ?>
                <option value="<?= Helpers::e($td['domain']) ?>" <?= $td['is_default'] ? 'selected' : '' ?>>
                    <?= Helpers::e($td['domain']) ?><?= $td['label'] ? ' — '.Helpers::e($td['label']) : '' ?><?= $td['is_default'] ? ' (Default)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:10px;color:#94A3B8;margin-top:3px">Select which tracking domain to use for this link</div>
        </div>

        <!-- Base URL -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">BASE TRACKING URL</label>
            <input type="text" id="ih-gen-base-url" class="form-control" readonly style="font-size:11px;background:#F8FAFC;color:#475569">
        </div>

        <!-- SOURCE + CLICK ID -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SOURCE <span style="color:#94A3B8;font-weight:400">(traffic source name)</span></label>
                <input type="text" id="ih-gen-source" class="form-control" placeholder="e.g. facebook, push, native" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;source=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">CLICK ID MACRO <span style="color:#94A3B8;font-weight:400">(your tracker token)</span></label>
                <input type="text" id="ih-gen-clickid" class="form-control" placeholder="e.g. {clickid} or ##CLICKID##" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;click_id=</code></div>
            </div>
        </div>

        <!-- SUB2 + SUB3 -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB ID 1 (sub_id_1)</label>
                <input type="text" id="ih-gen-sub2" class="form-control" placeholder="e.g. {campaign} or adgroup" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_1=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB ID 2 (sub_id_2)</label>
                <input type="text" id="ih-gen-sub3" class="form-control" placeholder="e.g. {creative} or ad_id" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_2=</code></div>
            </div>
        </div>

        <!-- SUB4 + SUB5 -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB ID 3 (sub_id_3)</label>
                <input type="text" id="ih-gen-sub4" class="form-control" placeholder="e.g. {keyword}" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_3=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB ID 4 (sub_id_4)</label>
                <input type="text" id="ih-gen-sub5" class="form-control" placeholder="e.g. {placement}" oninput="ihRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;sub_id_4=</code></div>
            </div>
        </div>

        <!-- Postback Tip -->
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#1E40AF">
            <div style="font-weight:700;margin-bottom:4px">&#128161; Postback Tip</div>
            Your postback will automatically receive <code style="background:#DBEAFE;padding:1px 5px;border-radius:3px">{click_id}</code> as the value of whatever you pass in <strong>click_id</strong>.
            Use your tracker's click macro in the CLICK ID field above so conversions fire back correctly.
        </div>

        <!-- Generated URL -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9989; GENERATED TRACKING LINK</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input type="text" id="ih-gen-output" class="form-control" readonly
                       style="font-size:11px;background:#F0FDF4;border-color:#86EFAC;font-family:monospace">
                <button class="btn btn-secondary btn-sm" onclick="ihCopyGenOutput()" id="ih-gen-copy-btn" style="white-space:nowrap">Copy</button>
            </div>
        </div>

        <!-- Short link from modal -->
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9986; SHORT LINK</label>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                <input type="text" id="ih-gen-short-output" class="form-control" readonly placeholder="Click 'Shorten' to generate a short link..." style="font-size:11px;background:#F5F3FF;border-color:#C4B5FD;font-family:monospace">
                <button class="btn btn-sm" id="ih-gen-shorten-btn" onclick="ihModalShorten()" style="white-space:nowrap;background:#7C3AED;border-color:#7C3AED;color:#fff">&#9986; Shorten</button>
                <a id="ih-gen-short-open" href="#" target="_blank" style="display:none;background:#5B21B6;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;text-decoration:none">&#8599;</a>
            </div>
        </div>

        <!-- Quick Presets -->
        <div style="margin-bottom:20px">
            <div style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;margin-bottom:8px">QUICK PRESETS — click to fill click ID macro</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <button class="btn btn-sm" onclick="ihApplyPreset('{clickid}')"    style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{clickid}</button>
                <button class="btn btn-sm" onclick="ihApplyPreset('{click_id}')"   style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{click_id}</button>
                <button class="btn btn-sm" onclick="ihApplyPreset('[clickid]')"    style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">[clickid]</button>
                <button class="btn btn-sm" onclick="ihApplyPreset('##CLICKID##')"  style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">##CLICKID##</button>
                <button class="btn btn-sm" onclick="ihApplyPreset('%7Bclickid%7D')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">%7Bclickid%7D</button>
            </div>
        </div>

        <!-- Footer -->
        <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #E2E8F0;padding-top:16px">
            <button class="btn btn-secondary" onclick="ihResetGenModal()">&#8635; Reset</button>
            <button class="btn btn-primary" onclick="ihApplyGenToOffer()" style="background:#0EA5E9;border-color:#0EA5E9">Apply to Offer Link</button>
            <button class="btn btn-secondary" onclick="ihCloseGenModal()">Close</button>
        </div>
    </div>
</div>

<script>
// ── Description expand/collapse ────────────────────────────────────────────
function toggleIhDesc(id) {
    var s = document.getElementById('ih-gdesc-short-' + id);
    var f = document.getElementById('ih-gdesc-full-'  + id);
    var t = document.getElementById('ih-gdesc-toggle-' + id);
    if (!f) return;
    var expanded = f.style.display !== 'none';
    f.style.display = expanded ? 'none' : 'inline';
    if (s) s.style.display = expanded ? 'inline' : 'none';
    if (t) t.textContent = expanded ? 'More' : 'Less';
}
function toggleIhListTerms(id) {
    var el = document.getElementById('ih-lterms-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

// ── View toggle ────────────────────────────────────────────────────────────
function ihSetView(v) {
    document.getElementById('ih-view-grid').style.display = v === 'grid' ? 'grid' : 'none';
    document.getElementById('ih-view-list').style.display = v === 'list' ? 'block' : 'none';
    document.getElementById('btn-grid').style.opacity = v === 'grid' ? '1' : '.45';
    document.getElementById('btn-list').style.opacity = v === 'list' ? '1' : '.45';
    localStorage.setItem('ih_offers_view', v);
}
(function () {
    ihSetView(localStorage.getItem('ih_offers_view') || 'grid');
})();

// ── Copy via data-copy attr ────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy]');
    if (!btn) return;
    var inp = document.getElementById(btn.getAttribute('data-copy'));
    if (!inp) return;
    navigator.clipboard.writeText(inp.value).then(function () {
        var orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(function () { btn.textContent = orig; }, 2000);
    }).catch(function () { inp.select(); try { document.execCommand('copy'); } catch (e) {} });
});

// ── Build Tracking Link modal ──────────────────────────────────────────────
var _ihGenOfferId = null;
var _ihGenBasePath = ''; // path+query only, no domain

function _ihGetDomainBase() {
    var sel = document.getElementById('ih-gen-domain');
    if (!sel) return '';
    return 'https://' + sel.value.replace(/^https?:\/\//, '').replace(/\/$/, '');
}

function ihOpenBuildModal(offerId, baseUrl) {
    _ihGenOfferId  = offerId;
    var clean = baseUrl
        .replace(/[&?]source=[^&]*/g, '')
        .replace(/[&?]sub[1-5]=[^&]*/g, '')
        .replace(/[&?]sub_id_[1-9]=[^&]*/g, '')
        .replace(/[&?]click_id=[^&]*/g, '')
        .replace(/&&/g, '&')
        .replace(/\?&/, '?');
    // Strip domain — keep only path+query
    _ihGenBasePath = clean.replace(/^https?:\/\/[^\/]+/, '');
    // Try to match domain in dropdown
    var incomingDomain = clean.match(/^https?:\/\/([^\/]+)/);
    if (incomingDomain) {
        var sel = document.getElementById('ih-gen-domain');
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === incomingDomain[1]) { sel.selectedIndex = i; break; }
        }
    }
    ihResetGenFields();
    ihRebuildGenUrl();
    document.getElementById('ihGenLinkModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function ihCloseGenModal() {
    document.getElementById('ihGenLinkModal').style.display = 'none';
    document.body.style.overflow = '';
}

function ihResetGenFields() {
    ['ih-gen-source','ih-gen-clickid','ih-gen-sub2','ih-gen-sub3','ih-gen-sub4','ih-gen-sub5'].forEach(function (id) {
        document.getElementById(id).value = '';
    });
    var so = document.getElementById('ih-gen-short-output');
    if (so) so.value = '';
    var gso = document.getElementById('ih-gen-short-open');
    if (gso) { gso.href = '#'; gso.style.display = 'none'; }
}

function ihResetGenModal() {
    ihResetGenFields();
    ihRebuildGenUrl();
}

function ihApplyPreset(macro) {
    document.getElementById('ih-gen-clickid').value = macro;
    ihRebuildGenUrl();
}

function ihRebuildGenUrl() {
    var domainBase = _ihGetDomainBase();
    var base  = domainBase + _ihGenBasePath;
    var sep   = base.indexOf('?') === -1 ? '?' : '&';
    var parts = [];
    var src   = document.getElementById('ih-gen-source').value.trim();
    var cid   = document.getElementById('ih-gen-clickid').value.trim();
    var sub2  = document.getElementById('ih-gen-sub2').value.trim();
    var sub3  = document.getElementById('ih-gen-sub3').value.trim();
    var sub4  = document.getElementById('ih-gen-sub4').value.trim();
    var sub5  = document.getElementById('ih-gen-sub5').value.trim();
    if (src)  parts.push('source='   + encodeURIComponent(src));
    if (cid)  parts.push('click_id=' + cid);
    if (sub2) parts.push('sub_id_1=' + encodeURIComponent(sub2));
    if (sub3) parts.push('sub_id_2=' + encodeURIComponent(sub3));
    if (sub4) parts.push('sub_id_3=' + encodeURIComponent(sub4));
    if (sub5) parts.push('sub_id_4=' + encodeURIComponent(sub5));
    var finalUrl = parts.length ? base + sep + parts.join('&') : base;
    document.getElementById('ih-gen-base-url').value = base;
    document.getElementById('ih-gen-output').value   = finalUrl;
    // Reset short when URL changes
    var so = document.getElementById('ih-gen-short-output');
    if (so) so.value = '';
    var gso = document.getElementById('ih-gen-short-open');
    if (gso) { gso.href = '#'; gso.style.display = 'none'; }
}

function ihCopyGenOutput() {
    var el  = document.getElementById('ih-gen-output');
    var btn = document.getElementById('ih-gen-copy-btn');
    el.select(); el.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch (e) { navigator.clipboard.writeText(el.value); }
    btn.textContent = '✓ Copied!';
    btn.style.background = '#22C55E'; btn.style.color = '#fff'; btn.style.borderColor = '#22C55E';
    setTimeout(function () { btn.textContent = 'Copy'; btn.style.background = ''; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
}

function ihApplyGenToOffer() {
    var url = document.getElementById('ih-gen-output').value;
    if (!url || !_ihGenOfferId) return;
    var gridIn = document.getElementById('ih-ol-' + _ihGenOfferId);
    var listIn = document.getElementById('ih-ll-' + _ihGenOfferId);
    if (gridIn) gridIn.value = url;
    if (listIn) listIn.value = url;
    ihCloseGenModal();
}

function ihModalShorten() {
    var url = document.getElementById('ih-gen-output').value;
    if (!url) { alert('Please build a tracking link first.'); return; }
    var btn = document.getElementById('ih-gen-shorten-btn');
    var si  = document.getElementById('ih-gen-short-output');
    var gso = document.getElementById('ih-gen-short-open');
    if (si && si.dataset.srcUrl === url && si.value) {
        if (gso) { gso.href = si.value; gso.style.display = 'inline'; }
        return;
    }
    btn.textContent = '...'; btn.disabled = true;
    var fd = new FormData();
    fd.append('url', url);
    fd.append('_token', _csrfToken);
    fetch('/affiliate/inhouse-shorten', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            btn.innerHTML = '&#9986; Shorten'; btn.disabled = false;
            if (d.error) { alert('Shortener error: ' + d.error); return; }
            si.value = d.short_url;
            si.dataset.srcUrl = url;
            if (gso) { gso.href = d.short_url; gso.style.display = 'inline'; }
        })
        .catch(function () {
            btn.innerHTML = '&#9986; Shorten'; btn.disabled = false;
            alert('Failed to shorten link. Please try again.');
        });
}

// Close on backdrop click or Escape
document.getElementById('ihGenLinkModal').addEventListener('click', function (e) {
    if (e.target === this) ihCloseGenModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') ihCloseGenModal();
});

// ── Inline short link (grid/list Short button) ─────────────────────────────
function ihGenerateShort(id) {
    var btn    = document.getElementById('ih-shorten-btn-' + id);
    var gridIn = document.getElementById('ih-ol-' + id);
    var url    = (gridIn && gridIn.value) ? gridIn.value : '';
    if (!url) {
        // Rebuild URL using default domain + offer path
        var defDomain = (_ihTrackDomains && _ihTrackDomains.length)
            ? _ihTrackDomains.find(function(d){return d.is_default;}) || _ihTrackDomains[0]
            : null;
        if (defDomain) {
            url = 'https://' + defDomain.domain + (window['_ih_path_' + id] || '');
        } else {
            url = window['_ih_base_' + id] || '';
        }
    }
    if (!url) return;

    var si = document.getElementById('ih-short-url-' + id);
    var sr = document.getElementById('ih-short-result-' + id);
    var so = document.getElementById('ih-short-open-' + id);

    // If already shortened for this exact URL, just toggle
    if (si && si.dataset.srcUrl === url && si.value) {
        sr.style.display = sr.style.display === 'none' ? 'block' : 'none';
        return;
    }

    btn.disabled = true; btn.innerHTML = 'Working...';
    var fd = new FormData();
    fd.append('url', url);
    fd.append('_token', _csrfToken);
    fetch('/affiliate/inhouse-shorten', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            btn.disabled = false; btn.innerHTML = '&#9986; Short';
            if (d.error) { alert('Error: ' + d.error); return; }
            if (si) { si.value = d.short_url; si.dataset.srcUrl = url; }
            if (so) so.href  = d.short_url;
            if (sr) sr.style.display = 'block';
        })
        .catch(function (err) {
            btn.disabled = false; btn.innerHTML = '&#9986; Short';
            alert('Network error: ' + err);
        });
}
</script>

<!-- Path-only per offer (for domain-swapping in ihGenerateShort) -->
<?php foreach ($offers as $o):
    $affCode3  = $aff['affiliate_code'] ?? '';
    $path3     = '/offer/' . $o['id'] . '?aff_id=' . urlencode($affCode3);
    $base3     = $_ihTrackBase . $path3;
?>
<script>
window['_ih_path_<?= $o['id'] ?>'] = <?= json_encode($path3) ?>;
window['_ih_base_<?= $o['id'] ?>'] = <?= json_encode($base3) ?>;
</script>
<?php endforeach; ?>

<?php
$jsOfferDetails = [];
foreach($offers as $o) {
    $jsOfferDetails[$o['id']] = [
        'name' => $o['name'],
        'desc' => $o['description'] ?? '',
        'terms' => $o['terms_conditions'] ?? ''
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
<div id="offer-details-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;padding:24px;max-width:600px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 10px 25px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 id="od-modal-title" style="margin:0;font-size:18px;font-weight:700"></h3>
            <button type="button" onclick="document.getElementById('offer-details-modal').style.display='none'" style="background:none;border:none;font-size:24px;line-height:1;cursor:pointer;color:#94A3B8">&times;</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding-right:8px;font-size:13px;color:#334155;line-height:1.6">
            <div id="od-modal-desc" style="margin-bottom:20px;white-space:pre-wrap"></div>
            <div id="od-modal-terms-wrap" style="display:none;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;padding:12px">
                <div style="font-weight:700;color:#92400E;margin-bottom:8px">&#128221; Terms &amp; Conditions</div>
                <div id="od-modal-terms" style="color:#78350F;white-space:pre-line"></div>
            </div>
        </div>
        <div style="margin-top:20px;text-align:right">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('offer-details-modal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
