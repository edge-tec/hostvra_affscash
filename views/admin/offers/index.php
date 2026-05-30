<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Offers</h1><p>Manage CPA offers and campaigns</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/offers?export=csv<?= $qFilter ? '&q='.urlencode($qFilter) : '' ?><?= $catFilter ? '&category='.urlencode($catFilter) : '' ?><?= $typeFilter ? '&payout_type='.urlencode($typeFilter) : '' ?><?= $statusFilter ? '&status_filter='.urlencode($statusFilter) : '' ?>" class="btn btn-secondary">&#8595; Export CSV</a>
        <a href="/admin/offers/create" class="btn btn-primary">+ Create Offer</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-2">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="/admin/offers" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Offer name..." value="<?= Helpers::e($qFilter ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:140px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= Helpers::e($cat['category']) ?>" <?= ($catFilter ?? '') === $cat['category'] ? 'selected' : '' ?>><?= Helpers::e($cat['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:130px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Payout Type</label>
                <select name="payout_type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach (['CPA','CPL','CPS','CPM','CPC','RevShare','Trial','Other'] as $pt): ?>
                    <option value="<?= $pt ?>" <?= ($typeFilter ?? '') === $pt ? 'selected' : '' ?>><?= $pt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:120px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Status</label>
                <select name="status_filter" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach (['active','paused','pending'] as $sf): ?>
                    <option value="<?= $sf ?>" <?= ($statusFilter ?? '') === $sf ? 'selected' : '' ?>><?= ucfirst($sf) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:130px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Offer Type</label>
                <select name="offer_type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($offerTypes ?? [] as $ot): ?>
                    <option value="<?= Helpers::e($ot['offer_type']) ?>" <?= ($offerTypeFilter ?? '') === $ot['offer_type'] ? 'selected' : '' ?>><?= Helpers::e($ot['offer_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:110px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Country</label>
                <input type="text" name="country" class="form-control" placeholder="e.g. US" value="<?= Helpers::e($countryFilter ?? '') ?>" maxlength="2" style="text-transform:uppercase">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:110px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Device</label>
                <select name="device" class="form-control">
                    <option value="">All Devices</option>
                    <?php foreach (['desktop','mobile','tablet'] as $dv): ?>
                    <option value="<?= $dv ?>" <?= ($deviceFilter ?? '') === $dv ? 'selected' : '' ?>><?= ucfirst($dv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:110px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Offer ID</label>
                <input type="number" name="offer_id" class="form-control" placeholder="ID..." value="<?= ($idFilter ?? 0) > 0 ? (int)$idFilter : '' ?>" min="1">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:150px">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Access</label>
                <select name="access_filter" class="form-control">
                    <option value="">All Offers</option>
                    <option value="active" <?= ($accessFilter ?? '') === 'active' ? 'selected' : '' ?>>Active Offers</option>
                    <option value="inactive" <?= ($accessFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Offers</option>
                    <option value="request" <?= ($accessFilter ?? '') === 'request' ? 'selected' : '' ?>>Request / Need Approval</option>
                    <option value="all_access" <?= ($accessFilter ?? '') === 'all_access' ? 'selected' : '' ?>>Access for All</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($qFilter || $catFilter || $typeFilter || $statusFilter || ($offerTypeFilter ?? '') || ($countryFilter ?? '') || ($deviceFilter ?? '') || ($idFilter ?? 0) > 0 || ($accessFilter ?? '')): ?>
                <a href="/admin/offers" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
            <!-- View Toggle -->
            <div style="display:flex;gap:4px;align-items:center;margin-left:auto">
                <button type="button" id="btn-list-view" class="btn btn-secondary btn-sm" onclick="setView('list')" title="List view" style="padding:6px 10px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    List
                </button>
                <button type="button" id="btn-grid-view" class="btn btn-secondary btn-sm" onclick="setView('grid')" title="Grid view" style="padding:6px 10px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Grid
                </button>
            </div>
        </form>
    </div>
</div>

<!-- LIST VIEW -->
<style>
/* Offers table needs a generous minimum width so all ten columns
   (ID, Offer, Advertiser, Type, Payout, GEO, Cap, Status, Tracking,
   Actions) lay out cleanly without squashing the Tracking-Link or
   Actions cells. Below this minimum, .table-wrap's overflow-x:auto
   takes over and the table scrolls horizontally inside the card —
   the page itself never scrolls sideways. */
#tbl-offers                 { min-width: 1400px; }
#tbl-offers th,
#tbl-offers td              { vertical-align: top; }
#tbl-offers .col-id         { width: 80px;  white-space: nowrap; }
#tbl-offers .col-type       { width: 80px;  white-space: nowrap; }
#tbl-offers .col-payout     { width: 110px; white-space: nowrap; }
#tbl-offers .col-geo        { width: 90px; }
#tbl-offers .col-cap        { width: 100px; white-space: nowrap; }
#tbl-offers .col-status     { width: 90px;  white-space: nowrap; }
#tbl-offers .col-track      { width: 280px; min-width: 240px; }
#tbl-offers .col-actions    { width: 1%;    white-space: nowrap; text-align: right; }

/* Offer thumbnail — uniform 36×36 with a graceful fallback when the
   src 404s. The `object-fit: cover` keeps thumbnails square even
   when the source image is non-square. */
#tbl-offers .offer-thumb {
    width: 36px; height: 36px;
    border-radius: 6px;
    object-fit: cover;
    border: 1px solid #E2E8F0;
    flex-shrink: 0;
    background: #F1F5F9;
}

/* Action buttons in the last cell — let them wrap to a second row
   on narrow screens instead of overflowing horizontally. */
#tbl-offers .actions-cell {
    display: inline-flex;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* DataTables shell tidy-up — controls align cleanly on all widths. */
#tbl-offers_wrapper .dataTables_length,
#tbl-offers_wrapper .dataTables_filter {
    padding: 12px 16px;
}
#tbl-offers_wrapper .dataTables_info,
#tbl-offers_wrapper .dataTables_paginate {
    padding: 12px 16px;
}

/* Mobile + tablet — pagination + length controls stack vertically
   so they don't overflow the card on narrow viewports. The table
   itself still scrolls horizontally inside .table-wrap. */
@media (max-width: 768px) {
    #tbl-offers_wrapper .dataTables_length,
    #tbl-offers_wrapper .dataTables_filter,
    #tbl-offers_wrapper .dataTables_info,
    #tbl-offers_wrapper .dataTables_paginate {
        float: none !important;
        text-align: left !important;
        padding: 8px 12px;
    }
    #tbl-offers_wrapper .dataTables_filter input { width: 100%; box-sizing: border-box; }
}
</style>
<div id="view-list">
    <div class="card">
        <div class="table-wrap">
            <table id="tbl-offers">
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Offer</th>
                        <th>Advertiser</th>
                        <th class="col-type">Type</th>
                        <th class="col-payout">Payout</th>
                        <th class="col-geo">GEO</th>
                        <th class="col-cap">Cap</th>
                        <th class="col-status">Status</th>
                        <th class="col-track">Tracking Link</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($offers as $o): ?>
                <tr>
                    <td style="white-space:nowrap">
                        <span style="display:inline-block;background:#EFF6FF;color:#2563EB;border-radius:4px;padding:2px 7px;font-size:11px;font-weight:700;font-family:monospace">
                            OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;min-width:0">
                            <?php if (!empty($o['offer_image'])): ?>
                            <img src="<?= Helpers::e($o['offer_image']) ?>" alt="" class="offer-thumb"
                                 loading="lazy"
                                 onerror="this.style.display='none'">
                            <?php else: ?>
                            <div class="offer-thumb" style="display:flex;align-items:center;justify-content:center;color:#94A3B8;font-weight:700;font-size:12px;background:linear-gradient(135deg,#EEF2FF,#F5F3FF);border-color:#E0E7FF">
                                <?= Helpers::e(strtoupper(substr((string)$o['name'], 0, 2))) ?>
                            </div>
                            <?php endif; ?>
                            <div style="min-width:0">
                                <div class="fw-bold"><?= Helpers::e($o['name']) ?></div>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
                                    <span class="text-sm text-muted"><?= Helpers::e($o['category'] ?: '—') ?></span>
                                    <?php
                                    // Visibility badge — shown for every offer so admins can tell
                                    // public / private / require-approval at a glance.
                                    $_vis = strtolower((string)($o['visibility'] ?? 'public'));
                                    $_visBadge = match ($_vis) {
                                        'private'          => ['danger',  '&#128274; Private'],
                                        'require_approval' => ['warning', 'Approval Required'],
                                        default            => ['success', 'Public'],
                                    };
                                    ?>
                                    <span class="badge badge-<?= $_visBadge[0] ?>" style="font-size:10px;padding:1px 6px"><?= $_visBadge[1] ?></span>
                                    <?php if (!empty($o['require_approval']) && $_vis !== 'require_approval'): ?>
                                    <span class="badge badge-warning" style="font-size:10px;padding:1px 6px">Approval Required</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><?= Helpers::e($o['adv_name']) ?></td>
                    <td><span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span></td>
                    <td>
                        <?php if ($o['payout_type'] === 'RevShare'): ?>
                        <div class="fw-bold"><?= number_format($o['payout_amount'],2) ?>%</div>
                        <?php else: ?>
                        <div class="fw-bold">$<?= number_format($o['payout_amount'],2) ?></div>
                        <?php endif; ?>
                        <div class="text-sm text-muted">Rev: $<?= number_format($o['revenue_amount'],2) ?></div>
                    </td>
                    <td class="text-sm">
                        <?php
                        $geos = $o['geo_targeting'] ? json_decode($o['geo_targeting'], true) : [];
                        echo Helpers::geoList($geos, 3);
                        ?>
                    </td>
                    <td class="text-sm">
                        Daily: <?= $o['daily_cap'] ?: '&infin;' ?><br>
                        Total: <?= $o['total_cap'] ?: '&infin;' ?>
                    </td>
                    <td>
                        <?php $sm=['active'=>'success','paused'=>'warning','expired'=>'muted','pending'=>'info']; ?>
                        <span class="badge badge-<?= $sm[$o['status']] ?? 'muted' ?>"><?= $o['status'] ?></span>
                    </td>
                    <td class="col-track">
                        <div style="display:flex;flex-direction:column;gap:4px">
                            <select onchange="buildAdminLink(<?= $o['id'] ?>,this.value)" class="form-control" style="font-size:11px;padding:4px 6px;height:auto">
                                <option value="">— Select Affiliate —</option>
                                <?php foreach ($allAffiliates as $af): ?>
                                <option value="<?= Helpers::e($af['affiliate_code']) ?>"><?= Helpers::e($af['name'].' ('.$af['affiliate_code'].')') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div style="display:flex;gap:4px;min-width:0">
                                <input type="text" id="admin-tl-<?= $o['id'] ?>" class="form-control"
                                       value="<?= Helpers::e(Helpers::trackingUrl().'/click/'.$o['id'].'?aff=') ?>"
                                       readonly style="font-size:10px;flex:1;min-width:0">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="copyAdminLink(<?= $o['id'] ?>)" style="white-space:nowrap;flex-shrink:0">Copy</button>
                            </div>
                        </div>
                    </td>
                    <td class="col-actions">
                        <div class="actions-cell">
                            <a href="/admin/offers/<?= $o['id'] ?>/overview" class="btn btn-secondary btn-sm">Overview</a>
                            <a href="/admin/offers/<?= $o['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" action="/admin/offers/<?= $o['id'] ?>" style="display:inline">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="status" value="<?= $o['status']==='active' ? 'paused' : 'active' ?>">
                                <button type="submit" class="btn btn-sm <?= $o['status']==='active' ? 'btn-warning' : 'btn-success' ?>">
                                    <?= $o['status']==='active' ? 'Pause' : 'Activate' ?>
                                </button>
                            </form>
                            <form method="POST" action="/admin/offers?action=delete" style="display:inline"
                                  onsubmit="return confirm('Delete offer &quot;<?= Helpers::e(addslashes($o['name'])) ?>&quot;?\n\nThis will remove all affiliate access and tracking links. Conversion history is preserved.')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- GRID VIEW -->
<div id="view-grid" style="display:none;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;align-items:start;width:100%;box-sizing:border-box">
    <?php if (empty($offers)): ?>
    <div style="grid-column:1/-1"><div class="empty-state"><h3>No offers found</h3><p>Adjust your filters or create a new offer.</p></div></div>
    <?php else: foreach ($offers as $o):
        $sm = ['active'=>'success','paused'=>'warning','expired'=>'muted','pending'=>'info'];
    ?>
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden">
        <?php if (!empty($o['offer_image'])): ?>
        <div style="height:120px;overflow:hidden;background:#F1F5F9;flex-shrink:0">
            <img src="<?= Helpers::e($o['offer_image']) ?>" alt="<?= Helpers::e($o['name']) ?>" style="width:100%;height:100%;object-fit:cover">
        </div>
        <?php else: ?>
        <div style="height:60px;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="color:#fff;font-size:22px;font-weight:800;opacity:.4"><?= strtoupper(substr($o['name'],0,2)) ?></span>
        </div>
        <?php endif; ?>
        <div style="padding:14px;flex:1;display:flex;flex-direction:column">
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px;align-items:center">
                <span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span>
                <span class="badge badge-<?= $sm[$o['status']] ?? 'muted' ?>"><?= $o['status'] ?></span>
                <?php
                $_visG = strtolower((string)($o['visibility'] ?? 'public'));
                $_visGBadge = match ($_visG) {
                    'private'          => ['danger',  '&#128274; Private'],
                    'require_approval' => ['warning', 'Approval Required'],
                    default            => ['success', 'Public'],
                };
                ?>
                <span class="badge badge-<?= $_visGBadge[0] ?>" style="font-size:10px"><?= $_visGBadge[1] ?></span>
                <?php if (!empty($o['require_approval']) && $_visG !== 'require_approval'): ?>
                <span class="badge badge-warning" style="font-size:10px">Approval</span>
                <?php endif; ?>
            </div>
            <div class="fw-bold" style="font-size:14px;margin-bottom:4px;line-height:1.3"><?= Helpers::e($o['name']) ?></div>
            <div class="text-sm text-muted" style="margin-bottom:8px"><?= Helpers::e($o['adv_name']) ?></div>
            <div style="font-size:18px;font-weight:800;color:var(--secondary);margin-bottom:4px">
                <?php if ($o['payout_type'] === 'RevShare'): ?><?= number_format($o['payout_amount'],2) ?>%<?php else: ?>$<?= number_format($o['payout_amount'],2) ?><?php endif; ?>
            </div>
            <?php if ($o['category']): ?>
            <div class="text-sm text-muted" style="margin-bottom:10px"><?= Helpers::e($o['category']) ?></div>
            <?php endif; ?>
            <div style="display:flex;gap:6px;margin-top:auto">
                <a href="/admin/offers/<?= $o['id'] ?>/overview" class="btn btn-secondary btn-sm" style="flex:1;text-align:center">Overview</a>
                <a href="/admin/offers/<?= $o['id'] ?>" class="btn btn-secondary btn-sm" style="flex:1;text-align:center">Edit</a>
                <form method="POST" action="/admin/offers?action=delete" style="display:inline"
                      onsubmit="return confirm('Delete offer &quot;<?= Helpers::e(addslashes($o['name'])) ?>&quot;?')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">🗑</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<script>
function setView(mode) {
    var listEl = document.getElementById('view-list');
    var gridEl = document.getElementById('view-grid');
    var btnList = document.getElementById('btn-list-view');
    var btnGrid = document.getElementById('btn-grid-view');

    if (mode === 'grid') {
        listEl.style.display = 'none';
        gridEl.style.display = 'grid';
        btnGrid.style.background = 'var(--primary)';
        btnGrid.style.color = '#fff';
        btnGrid.style.borderColor = 'var(--primary)';
        btnList.style.background = '';
        btnList.style.color = '';
        btnList.style.borderColor = '';
    } else {
        listEl.style.display = '';
        gridEl.style.display = 'none';
        btnList.style.background = 'var(--primary)';
        btnList.style.color = '#fff';
        btnList.style.borderColor = 'var(--primary)';
        btnGrid.style.background = '';
        btnGrid.style.color = '';
        btnGrid.style.borderColor = '';
    }
    try { localStorage.setItem('offers_view_mode', mode); } catch(e) {}
}

// Restore saved view preference
(function() {
    var saved = '';
    try { saved = localStorage.getItem('offers_view_mode') || 'list'; } catch(e) { saved = 'list'; }
    setView(saved);
})();

var _baseAppUrl = <?= json_encode($appUrl) ?>;
function buildAdminLink(offerId, affCode) {
    var el = document.getElementById('admin-tl-' + offerId);
    if (!el) return;
    el.value = affCode ? (_baseAppUrl + '/click/' + offerId + '?aff=' + affCode) : (_baseAppUrl + '/click/' + offerId + '?aff=');
}
function copyAdminLink(offerId) {
    var el = document.getElementById('admin-tl-' + offerId);
    if (!el || !el.value || el.value.endsWith('?aff=')) { alert('Please select an affiliate first.'); return; }
    navigator.clipboard.writeText(el.value).then(function() {
        var btn = el.nextElementSibling;
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = 'var(--secondary)';
        btn.style.color = '#fff';
        setTimeout(function(){ btn.textContent = orig; btn.style.background=''; btn.style.color=''; }, 1500);
    }).catch(function(){ el.select(); document.execCommand('copy'); });
}

$(function() {
    $('#tbl-offers').DataTable({
        destroy: true,
        pageLength: 25,
        order: [],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
