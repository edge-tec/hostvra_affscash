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
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Search in-house offers..."
                       value="<?= Helpers::e($qFilter ?? '') ?>" style="min-width:200px;height:36px"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Offer ID</label>
                <input type="number" name="offer_id" class="form-control" placeholder="ID..." value="<?= ($idFilter ?? 0) > 0 ? (int)$idFilter : '' ?>" min="1" style="width:80px;height:36px"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Access</label>
                <select name="access_filter" class="form-control" style="height:36px">
                    <option value="">All Offers</option>
                    <option value="active" <?= ($accessFilter ?? '') === 'active' ? 'selected' : '' ?>>Active Offers</option>
                    <option value="request" <?= ($accessFilter ?? '') === 'request' ? 'selected' : '' ?>>Request / Need Approval</option>
                    <option value="all_access" <?= ($accessFilter ?? '') === 'all_access' ? 'selected' : '' ?>>Access for All</option>
                </select></div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">Filter</button>
            <?php if ($qFilter || ($idFilter ?? 0) > 0 || ($accessFilter ?? '')): ?>
            <a href="/affiliate/inhouse-offers" class="btn btn-secondary btn-sm" style="height:36px;line-height:24px">Clear</a>
            <?php endif; ?>
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
<div class="card" style="display:flex;flex-direction:column;min-width:0;border:1px solid <?= $d['hasAccess'] ? '#A7F3D0' : 'var(--border)' ?>">
    <div style="padding:20px;flex:1">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:4px;margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2px">
            <span style="font-size:10px;color:#94A3B8;font-family:monospace;font-weight:600">#<?= $o['id'] ?></span>
        </div>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
                <span style="background:#EDE9FE;color:#5B21B6;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px">&#127968; IN-HOUSE</span>
                <span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span>
                <?php if (!empty($o['offer_type'])): ?>
                <span style="background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px"><?= Helpers::e($o['offer_type']) ?></span>
                <?php endif; ?>
                <?php if (!empty($o['require_approval'])): ?>
                <span class="badge badge-warning" style="font-size:10px">Approval Required</span>
                <?php endif; ?>
            </div>
            <?php if ($o['category']): ?><span class="badge badge-muted"><?= Helpers::e($o['category']) ?></span><?php endif; ?>
        </div>
        <h3 style="font-size:16px;font-weight:700;margin-bottom:6px"><?= Helpers::e($o['name']) ?></h3>
        <?php if ($o['description']): ?>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:10px">
            <span id="ih-gdesc-short-<?= $o['id'] ?>"><?= Helpers::e(substr($o['description'], 0, 120)) ?><?= mb_strlen($o['description']) > 120 ? '...' : '' ?></span>
            <?php if (mb_strlen($o['description']) > 120): ?>
            <span id="ih-gdesc-full-<?= $o['id'] ?>" style="display:none"><?= Helpers::e($o['description']) ?></span>
            <a href="#" onclick="toggleIhDesc(<?= $o['id'] ?>);return false" id="ih-gdesc-toggle-<?= $o['id'] ?>" style="font-size:12px;margin-left:4px">More</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($o['preview_url'])): ?>
        <div style="margin-bottom:8px">
            <a href="<?= Helpers::e($o['preview_url']) ?>" target="_blank" rel="noopener"
               style="display:inline-block;font-size:11px;font-weight:700;padding:2px 10px;border-radius:10px;background:#0EA5E9;color:#fff;text-decoration:none">&#128065; Preview Offer</a>
        </div>
        <?php endif; ?>
        <?php if (!empty($o['terms_conditions'])): ?>
        <details style="margin-bottom:10px;font-size:12px">
            <summary style="cursor:pointer;font-weight:600;color:var(--text-muted)">&#128220; Terms &amp; Conditions</summary>
            <div style="margin-top:6px;padding:8px 10px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:6px;color:#78350F;white-space:pre-wrap"><?= Helpers::e($o['terms_conditions']) ?></div>
        </details>
        <?php endif ?>
        <div style="display:flex;gap:16px;margin-bottom:10px">
            <div>
                <div class="stat-label">Payout</div>
                <div style="font-size:20px;font-weight:700;color:var(--secondary)">$<?= number_format($d['payout'], 2) ?></div>
                <?php if($d['isCustom']): ?><span style="font-size:10px;background:#D1FAE5;color:#065F46;padding:1px 6px;border-radius:8px;font-weight:700">&#10003; Custom Rate</span><?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="stat-label">GEO</div>
                <?php if(empty($d['geos'])): ?>
                <span style="font-size:13px;color:var(--text-muted)">Global</span>
                <?php else: ?>
                <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px">
                    <?php foreach($d['geos'] as $_gc): ?><span style="display:inline-flex;align-items:center;gap:2px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:600;white-space:nowrap"><?= Helpers::flag($_gc) ?> <?= strtoupper(htmlspecialchars($_gc,ENT_QUOTES,'UTF-8')) ?></span><?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($d['devs'])): ?>
            <div><div class="stat-label">Devices</div><div style="font-size:13px;font-weight:600"><?= implode(', ', array_map('ucfirst', $d['devs'])) ?></div></div>
            <?php endif; ?>
        </div>
        <?php if($d['effCap'] > 0): ?>
        <div class="text-sm text-muted mb-1">
            Daily Cap: <strong><?= number_format($d['effCap']) ?></strong>
            <?php if($d['affCap'] > 0): ?><span style="font-size:10px;background:#EDE9FE;color:#5B21B6;padding:1px 6px;border-radius:8px;margin-left:4px">Your Cap</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if(!empty($d['countries'])): ?>
        <div style="margin-bottom:8px">
            <div class="stat-label" style="margin-bottom:4px">Your Country Rates</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php foreach($d['countries'] as $cr): ?>
            <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:600">
                &#127757; <?= Helpers::e($cr['country']) ?>: $<?= number_format((float)$cr['payout'],2) ?>
            </span>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!empty($d['devices'])): ?>
        <div style="margin-bottom:8px">
            <div class="stat-label" style="margin-bottom:4px">Your Device Rates</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php
            $ihDevIcons=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
            foreach($d['devices'] as $dr):
                $ihDIcon = $ihDevIcons[$dr['device']] ?? '&#128225;';
                $ihDGeo  = !empty($dr['country']) ? '/'.$dr['country'] : '';
            ?>
            <span style="display:inline-flex;align-items:center;gap:3px;background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:600">
                <?= $ihDIcon ?> <?= ucfirst($dr['device']) . $ihDGeo ?>: $<?= number_format((float)$dr['payout'],2) ?>
            </span>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!empty($d['offerCntPayouts'])): ?>
        <div style="margin-bottom:8px">
            <div class="stat-label" style="margin-bottom:4px">Country Rates</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php foreach($d['offerCntPayouts'] as $ocr): ?>
            <span style="display:inline-flex;align-items:center;gap:3px;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:600">
                &#127757; <?= Helpers::e($ocr['country']) ?>: $<?= number_format((float)$ocr['payout'],2) ?>
            </span>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if(!empty($d['offerDevPayouts'])): ?>
        <div style="margin-bottom:8px">
            <div class="stat-label" style="margin-bottom:4px">Device Rates</div>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
            <?php
            $ihOdIcons=['mobile'=>'&#128241;','tablet'=>'&#128242;','desktop'=>'&#128421;'];
            foreach($d['offerDevPayouts'] as $odev => $opay):
                $ihOdIcon = $ihOdIcons[$odev] ?? '&#128225;';
            ?>
            <span style="display:inline-flex;align-items:center;gap:3px;background:#F0FDF4;border:1px solid #BBF7D0;color:#15803D;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:600">
                <?= $ihOdIcon ?> <?= ucfirst($odev) ?>: $<?= number_format((float)$opay,2) ?>
            </span>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($o['today_clicks'] > 0): ?>
        <div class="text-sm text-muted mb-1">Today: <strong><?= (int)$o['today_clicks'] ?></strong> clicks</div>
        <?php endif; ?>
    </div>
    <div style="padding:16px;border-top:1px solid var(--border);background:var(--bg)">
        <?php if ($d['hasAccess']): ?>
            <!-- Tracking link input row -->
            <div class="copy-group" style="margin-bottom:8px">
                <input type="text" id="ih-ol-<?= $o['id'] ?>" class="form-control"
                       value="<?= Helpers::e($d['baseUrl']) ?>" readonly style="font-size:11px">
                <button class="btn btn-secondary btn-sm" data-copy="ih-ol-<?= $o['id'] ?>">Copy</button>
                <button class="btn btn-sm"
                        onclick="ihOpenBuildModal(<?= $o['id'] ?>, '<?= Helpers::e($d['baseUrl']) ?>')"
                        style="white-space:nowrap;background:#0EA5E9;border-color:#0EA5E9;color:#fff"
                        title="Build link with tracking parameters">&#128279; Build</button>
                <button class="btn btn-primary btn-sm"
                        onclick="ihGenerateShort(<?= $o['id'] ?>)"
                        id="ih-shorten-btn-<?= $o['id'] ?>"
                        style="white-space:nowrap;background:#7C3AED;border-color:#7C3AED">&#9986; Short</button>
            </div>
            <div id="ih-short-result-<?= $o['id'] ?>" style="display:none;margin-bottom:6px">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <span style="font-size:11px;font-weight:600;color:#7C3AED;white-space:nowrap">&#128279; Short:</span>
                    <input type="text" id="ih-short-url-<?= $o['id'] ?>" class="form-control" readonly
                           style="font-size:11px;border-color:#7C3AED;background:#F5F3FF;min-width:120px;flex:1">
                    <button class="btn btn-sm" data-copy="ih-short-url-<?= $o['id'] ?>"
                            style="background:#7C3AED;color:#fff;border:none;white-space:nowrap;flex-shrink:0">Copy</button>
                    <a id="ih-short-open-<?= $o['id'] ?>" href="#" target="_blank"
                       style="background:#5B21B6;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;text-decoration:none;flex-shrink:0">&#8599;</a>
                </div>
            </div>
            <div class="text-sm text-muted">&#9989; Approved — <?= (int)$o['today_clicks'] ?> clicks today</div>
        <?php elseif ($d['isPending']): ?>
            <div class="alert alert-warning mb-0" style="padding:10px;text-align:center">&#9203; Pending Approval — waiting for admin review</div>
        <?php else: ?>
            <form method="POST" action="/affiliate/inhouse-offers">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                <button type="submit" class="btn btn-primary" style="width:100%">
                    <?= $o['require_approval'] ? 'Apply for Access' : 'Get Tracking Link' ?>
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

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
