<?php
require BASE_PATH . '/views/layouts/admin.php';
$_pendingReqs = Database::fetchOne("SELECT COUNT(*) as cnt FROM smartlink_requests WHERE status='pending'");
$_pendingCount = (int)($_pendingReqs['cnt'] ?? 0);
$appUrl = rtrim(Config::get('config','app.url') ?? '', '/');
$affiliates = $affiliates ?? [];
$slOffersMap = $slOffersMap ?? [];

// Prepare data for JS modal
$smartlinksDataMap = [];
foreach ($smartlinks as $sl) {
    $smartlinksDataMap[$sl['id']] = [
        'id'               => (int)$sl['id'],
        'name'             => $sl['name'],
        'slug'             => $sl['slug'],
        'description'      => $sl['description'] ?: '',
        'rotation_type'    => $sl['rotation_type'],
        'status'           => $sl['status'],
        'require_approval' => (int)($sl['require_approval'] ?? 0),
        'offer_count'      => (int)$sl['offer_count'],
        'total_clicks'     => (int)$sl['total_clicks'],
        'total_convs'      => (int)$sl['total_convs'],
        'offers'           => $slOffersMap[$sl['id']] ?? []
    ];
}
?>

<div class="page-header">
    <div><h1>Smartlinks</h1><p>Rotating offer links with intelligent traffic distribution</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/smartlinks?action=requests" class="btn btn-secondary">
            &#128338; Requests<?= $_pendingCount > 0 ? ' <span style="background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700">'.$_pendingCount.'</span>' : '' ?>
        </a>
        <a href="/admin/smartlinks/create" class="btn btn-primary">+ Create Smartlink</a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding:14px 20px;border-bottom:1px solid #E2E8F0;background:#F8FAFC">
        <div class="fw-bold" style="font-size:14px;color:#1E293B">&#128279; Smartlink List</div>
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:12px;font-weight:600;color:#64748B;white-space:nowrap">&#128100; Select Affiliate for All Links:</label>
            <select class="form-select form-select-sm" id="global-aff-select" style="font-size:12px;min-width:240px;border-radius:6px;border:1px solid #CBD5E1" onchange="applyGlobalAffiliateToSmartlinks(this.value)">
                <option value="{AFF_CODE}">-- Generic Placeholder ({AFF_CODE}) --</option>
                <?php foreach ($affiliates as $aff): ?>
                <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                    [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?> (<?= Helpers::e($aff['email']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Name & Description</th>
                    <th>Slug</th>
                    <th>Rotation</th>
                    <th>Offers</th>
                    <th>Clicks</th>
                    <th>Convs</th>
                    <th>Status</th>
                    <th>Access</th>
                    <th style="min-width:280px">Affiliate Tracking Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($smartlinks)): ?>
            <tr><td colspan="11"><div class="empty-state"><div class="icon">&#128279;</div><h3>No smartlinks yet</h3><p>Create your first smartlink to start rotating offers.</p></div></td></tr>
            <?php else: ?>
            <?php foreach($smartlinks as $sl): ?>
            <tr>
                <td class="text-muted" style="font-size:11px;white-space:nowrap;font-family:monospace">#<?= $sl['id'] ?></td>
                <td style="max-width:320px">
                    <div class="fw-bold" style="font-size:14px;color:#0F172A"><?= Helpers::e($sl['name']) ?></div>
                    <?php if (!empty($sl['description'])): ?>
                        <?php 
                            $fullDesc  = $sl['description'];
                            $shortDesc = mb_strimwidth($fullDesc, 0, 75, '...');
                        ?>
                        <div class="text-muted text-sm mt-1" style="font-size:12px;line-height:1.4;color:#64748B">
                            <?= Helpers::e($shortDesc) ?>
                            <button type="button" class="btn-link p-0 text-primary fw-semibold ms-1" style="font-size:11px;text-decoration:underline;border:none;background:none;cursor:pointer;color:#4F46E5" onclick="openSlDetailsModal(<?= $sl['id'] ?>)">See Details &raquo;</button>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn-link p-0 text-primary fw-semibold" style="font-size:11px;text-decoration:underline;border:none;background:none;cursor:pointer;color:#4F46E5" onclick="openSlDetailsModal(<?= $sl['id'] ?>)">See Details &raquo;</button>
                    <?php endif; ?>
                </td>
                <td><code style="background:#F1F5F9;padding:2px 8px;border-radius:4px;font-size:12px;color:#334155"><?= Helpers::e($sl['slug']) ?></code></td>
                <td><span class="badge badge-info"><?= Helpers::e($sl['rotation_type']) ?></span></td>
                <td><span class="badge" style="background:#EEF2FF;color:#4F46E5;font-weight:700"><?= $sl['offer_count'] ?> offers</span></td>
                <td><?= number_format((int)$sl['total_clicks']) ?></td>
                <td><?= number_format((int)$sl['total_convs']) ?></td>
                <td><span class="badge badge-<?= $sl['status']==='active'?'success':'muted' ?>"><?= $sl['status'] ?></span></td>
                <td>
                    <?php if (!empty($sl['require_approval'])): ?>
                    <span class="badge badge-warning" style="font-size:11px">Manual</span>
                    <?php else: ?>
                    <span class="badge badge-success" style="font-size:11px">Auto</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="max-width:300px">
                        <div style="margin-bottom:5px">
                            <select class="form-select form-select-sm sl-aff-select" id="sl-aff-sel-<?= $sl['id'] ?>" style="font-size:11px;padding:3px 8px;border-radius:6px;border:1px solid #CBD5E1;width:100%" onchange="updateSlAffLink(<?= $sl['id'] ?>, '<?= Helpers::e($appUrl.'/smartlink/'.$sl['slug']) ?>')">
                                <option value="{AFF_CODE}">-- Select Affiliate ({AFF_CODE}) --</option>
                                <?php foreach ($affiliates as $aff): ?>
                                <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                                    [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="copy-group">
                            <input type="text" class="form-control" id="sl-<?= $sl['id'] ?>" value="<?= Helpers::e($appUrl.'/smartlink/'.$sl['slug'].'?aff={AFF_CODE}') ?>" readonly style="font-size:11px">
                            <button class="btn btn-secondary btn-sm" data-copy="sl-<?= $sl['id'] ?>">Copy</button>
                            <button class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;cursor:pointer" id="sl-short-btn-<?= $sl['id'] ?>" onclick="adminShortenSl(<?= $sl['id'] ?>, document.getElementById('sl-<?= $sl['id'] ?>').value)">&#9986; Short</button>
                        </div>
                        <div id="sl-short-result-<?= $sl['id'] ?>" style="display:none;margin-top:6px">
                            <div class="copy-group">
                                <input type="text" id="sl-short-url-<?= $sl['id'] ?>" class="form-control" readonly style="font-size:11px">
                                <button class="btn btn-secondary btn-sm" data-copy="sl-short-url-<?= $sl['id'] ?>">Copy</button>
                                <a id="sl-short-open-<?= $sl['id'] ?>" href="#" target="_blank" class="btn btn-sm btn-secondary">Open</a>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm btn-info text-white me-1" style="background:#0EA5E9;border:none;font-weight:600" onclick="openSlDetailsModal(<?= $sl['id'] ?>)">Details</button>
                    <a href="/admin/smartlinks?action=edit&id=<?= $sl['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="/admin/smartlinks" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                        <input type="hidden" name="toggle_status" value="1">
                        <button class="btn btn-sm btn-secondary"><?= $sl['status']==='active'?'Pause':'Activate' ?></button>
                    </form>
                    <form method="POST" action="/admin/smartlinks?action=delete" style="display:inline" onsubmit="return confirm('Delete this smartlink? This cannot be undone.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── SMARTLINK DETAILS MODAL ───────────────────────────────────────────── -->
<style>
.details-modal-dialog {
    background:#fff;border-radius:16px;padding:24px;max-width:760px;width:95vw;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;box-sizing:border-box;
}
.modal-link-grid {
    display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:12px;align-items:end;
}
.modal-copy-group {
    display:flex;width:100%;gap:4px;align-items:center;
}
.modal-copy-group input {
    flex:1;min-width:0;
}
@media (max-width: 640px) {
    .details-modal-dialog { padding: 16px; border-radius: 12px; width: 98vw; }
    .modal-link-grid { grid-template-columns: 1fr; }
}
</style>

<div id="slDetailsModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.65);z-index:10000;align-items:center;justify-content:center;padding:12px;backdrop-filter:blur(3px)">
    <div class="details-modal-dialog">
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:1px solid #E2E8F0;margin-bottom:20px;gap:10px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <h3 id="modal-sl-title" style="margin:0;font-size:18px;font-weight:700;color:#0F172A;word-break:break-word">Smartlink Details</h3>
                    <span id="modal-sl-id-badge" style="background:#F1F5F9;color:#475569;font-family:monospace;font-size:12px;padding:2px 8px;border-radius:6px;font-weight:700">#0</span>
                </div>
                <div style="font-size:12px;color:#64748B;margin-top:2px">Comprehensive configuration, traffic rules & affiliate tracking link</div>
            </div>
            <button type="button" onclick="closeSlDetailsModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#94A3B8;line-height:1;padding:0">&times;</button>
        </div>

        <!-- Overview Badges -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:20px">
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Slug</div>
                <div id="modal-sl-slug" style="font-family:monospace;font-size:12px;font-weight:700;color:#334155;margin-top:2px;word-break:break-all">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Rotation</div>
                <div id="modal-sl-rotation" style="font-size:12px;font-weight:700;color:#4F46E5;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Access Control</div>
                <div id="modal-sl-access" style="font-size:12px;font-weight:700;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Status</div>
                <div id="modal-sl-status" style="font-size:12px;font-weight:700;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Clicks / Convs</div>
                <div id="modal-sl-stats" style="font-size:12px;font-weight:700;color:#0F172A;margin-top:2px">0 / 0</div>
            </div>
        </div>

        <!-- Description Box -->
        <div style="margin-bottom:20px">
            <label style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:6px">&#128220; Description & Traffic Restrictions</label>
            <div id="modal-sl-description" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;font-size:13px;line-height:1.6;color:#334155;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-word">No description provided.</div>
        </div>

        <!-- Separate Affiliate Tracking Link Box inside Modal -->
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:16px;margin-bottom:20px">
            <div style="font-size:13px;font-weight:700;color:#1E40AF;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <span>&#128279; Generate Affiliate Smartlink</span>
            </div>
            <div class="modal-link-grid">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Select Affiliate:</label>
                    <select class="form-select form-select-sm" id="modal-sl-aff-select" style="font-size:12px;border-radius:6px;border:1px solid #93C5FD;width:100%" onchange="updateModalSlAffLink()">
                        <option value="{AFF_CODE}">-- Generic Placeholder ({AFF_CODE}) --</option>
                        <?php foreach ($affiliates as $aff): ?>
                        <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                            [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Smartlink URL:</label>
                    <div class="modal-copy-group">
                        <input type="text" id="modal-sl-url-input" class="form-control" readonly style="font-size:11px;background:#fff">
                        <button type="button" class="btn btn-secondary btn-sm" data-copy="modal-sl-url-input" style="white-space:nowrap">Copy</button>
                        <button type="button" class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;cursor:pointer;white-space:nowrap" id="modal-sl-short-btn" onclick="adminShortenSlModal()">&#9986; Short</button>
                    </div>
                </div>
            </div>
            <div id="modal-sl-short-result" style="display:none;margin-top:8px">
                <div class="modal-copy-group">
                    <input type="text" id="modal-sl-short-url" class="form-control" readonly style="font-size:11px">
                    <button type="button" class="btn btn-secondary btn-sm" data-copy="modal-sl-short-url" style="white-space:nowrap">Copy</button>
                    <a id="modal-sl-short-open" href="#" target="_blank" class="btn btn-sm btn-secondary" style="white-space:nowrap">Open</a>
                </div>
            </div>
        </div>

        <!-- Offers List Table -->
        <div style="margin-bottom:20px">
            <label style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:6px">&#127919; Smartlink Offers & Targeting Rules</label>
            <div style="border:1px solid #E2E8F0;border-radius:10px;overflow-x:auto;-webkit-overflow-scrolling:touch">
                <table style="width:100%;min-width:540px;font-size:12px;border-collapse:collapse">
                    <thead style="background:#F8FAFC;border-bottom:1px solid #E2E8F0">
                        <tr>
                            <th style="padding:8px 12px;text-align:left">Offer / Target URL</th>
                            <th style="padding:8px 12px;text-align:center">Weight</th>
                            <th style="padding:8px 12px;text-align:left">Geo Rules</th>
                            <th style="padding:8px 12px;text-align:left">Device Rules</th>
                            <th style="padding:8px 12px;text-align:left">Payout Override</th>
                        </tr>
                    </thead>
                    <tbody id="modal-sl-offers-tbody">
                        <tr><td colspan="5" style="padding:16px;text-align:center;color:#94A3B8">No offers attached</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;border-top:1px solid #E2E8F0">
            <a id="modal-sl-edit-btn" href="#" class="btn btn-secondary">Edit Smartlink</a>
            <button type="button" class="btn btn-primary" onclick="closeSlDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
var _adminCsrf = '<?= Auth::generateCsrf() ?>';
var _appUrl = '<?= Helpers::e($appUrl) ?>';
var _slDataMap = <?= json_encode($smartlinksDataMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

// Global copy button handler
document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var inp = document.getElementById(this.dataset.copy);
        if (!inp) return;
        inp.select(); document.execCommand('copy');
        var orig = this.textContent;
        this.textContent = '✓ Copied';
        setTimeout(() => this.textContent = orig, 1500);
    });
});

function updateSlAffLink(id, baseUrl) {
    var affSel = document.getElementById('sl-aff-sel-' + id);
    var affCode = affSel ? affSel.value : '{AFF_CODE}';
    var input = document.getElementById('sl-' + id);
    if (input) {
        input.value = baseUrl + '?aff=' + encodeURIComponent(affCode);
    }
    var shortRes = document.getElementById('sl-short-result-' + id);
    if (shortRes) shortRes.style.display = 'none';
}

function applyGlobalAffiliateToSmartlinks(affCode) {
    document.querySelectorAll('.sl-aff-select').forEach(function(sel) {
        sel.value = affCode;
        sel.dispatchEvent(new Event('change'));
    });
}

function adminShortenSl(id, customUrl) {
    var btn = document.getElementById('sl-short-btn-' + id);
    var url = customUrl || (document.getElementById('sl-' + id) ? document.getElementById('sl-' + id).value : '');
    if (!url) return;

    btn.disabled = true; btn.textContent = '...';
    fetch('/affiliate/shorten', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_adminCsrf) + '&url=' + encodeURIComponent(url)
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; btn.innerHTML = '&#9986; Short';
        if (d.short_url) {
            document.getElementById('sl-short-url-' + id).value = d.short_url;
            document.getElementById('sl-short-result-' + id).style.display = 'block';
            document.getElementById('sl-short-open-' + id).href = d.short_url;
        } else { alert(d.error || 'Failed to shorten'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '&#9986; Short'; });
}

// Modal functions
var _currentModalSlId = null;

function openSlDetailsModal(slId) {
    var sl = _slDataMap[slId];
    if (!sl) return;

    _currentModalSlId = slId;

    document.getElementById('modal-sl-title').textContent = sl.name;
    document.getElementById('modal-sl-id-badge').textContent = '#' + sl.id;
    document.getElementById('modal-sl-slug').textContent = sl.slug;
    document.getElementById('modal-sl-rotation').textContent = sl.rotation_type;
    document.getElementById('modal-sl-access').innerHTML = sl.require_approval ? '<span class="badge badge-warning" style="font-size:10.5px;padding:3px 7px;white-space:normal;line-height:1.2;display:inline-block">Manual Approval</span>' : '<span class="badge badge-success" style="font-size:10.5px;padding:3px 7px;white-space:normal;line-height:1.2;display:inline-block">Auto Access</span>';
    document.getElementById('modal-sl-status').innerHTML = '<span class="badge badge-' + (sl.status === 'active' ? 'success' : 'muted') + '" style="font-size:10.5px;padding:3px 7px;white-space:normal;line-height:1.2;display:inline-block">' + sl.status + '</span>';
    document.getElementById('modal-sl-stats').textContent = Number(sl.total_clicks).toLocaleString() + ' / ' + Number(sl.total_convs).toLocaleString();
    document.getElementById('modal-sl-description').textContent = sl.description || 'No description provided for this smartlink.';
    document.getElementById('modal-sl-edit-btn').href = '/admin/smartlinks?action=edit&id=' + sl.id;

    // Reset modal affiliate selector to match table row selector if selected
    var rowAffSel = document.getElementById('sl-aff-sel-' + slId);
    var modalAffSel = document.getElementById('modal-sl-aff-select');
    if (rowAffSel && modalAffSel) {
        modalAffSel.value = rowAffSel.value;
    }
    updateModalSlAffLink();

    // Render offers table inside modal
    var tbody = document.getElementById('modal-sl-offers-tbody');
    tbody.innerHTML = '';

    if (sl.offers && sl.offers.length > 0) {
        sl.offers.forEach(function(o) {
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #F1F5F9';

            var nameTd = o.offer_name ? ('<b>' + escapeHtml(o.offer_name) + '</b> <span style="color:#94A3B8">(#' + o.offer_id + ')</span>') : ('<code>' + escapeHtml(o.direct_url || 'Direct URL') + '</code>');
            var weightTd = '<span class="badge" style="background:#F1F5F9;color:#334155;font-weight:700">' + o.weight + '</span>';

            // Parse Geos
            var geos = [];
            try { if (o.geo_rules) geos = JSON.parse(o.geo_rules); } catch(e){}
            var geoTd = geos.length > 0 ? geos.map(g => '<span class="badge badge-info" style="font-size:10px;margin-right:2px">' + escapeHtml(g) + '</span>').join('') : '<span style="color:#94A3B8">All Geos</span>';

            // Parse Devices
            var devs = [];
            try { if (o.device_rules) devs = JSON.parse(o.device_rules); } catch(e){}
            var devTd = devs.length > 0 ? devs.map(d => '<span class="badge" style="background:#E0E7FF;color:#3730A3;font-size:10px;margin-right:2px">' + escapeHtml(d) + '</span>').join('') : '<span style="color:#94A3B8">All Devices</span>';

            // Payout override
            var payTd = '<span style="color:#64748B">Default</span>';
            if (o.payout_type === 'fixed') payTd = '<b>$' + Number(o.payout_value).toFixed(2) + '</b> (Fixed)';
            else if (o.payout_type === 'percent') payTd = '<b>' + Number(o.payout_value).toFixed(1) + '%</b> (Percent)';
            else if (o.payout_type === 'skip') payTd = '<span style="color:#EF4444;font-weight:600">Skip Payout</span>';

            tr.innerHTML = '<td style="padding:10px 12px">' + nameTd + '</td>'
                         + '<td style="padding:10px 12px;text-align:center">' + weightTd + '</td>'
                         + '<td style="padding:10px 12px">' + geoTd + '</td>'
                         + '<td style="padding:10px 12px">' + devTd + '</td>'
                         + '<td style="padding:10px 12px">' + payTd + '</td>';
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="5" style="padding:16px;text-align:center;color:#94A3B8">No offers attached to this smartlink yet</td></tr>';
    }

    document.getElementById('slDetailsModal').style.display = 'flex';
}

function closeSlDetailsModal() {
    document.getElementById('slDetailsModal').style.display = 'none';
}

function updateModalSlAffLink() {
    if (!_currentModalSlId) return;
    var sl = _slDataMap[_currentModalSlId];
    if (!sl) return;

    var modalAffSel = document.getElementById('modal-sl-aff-select');
    var affCode = modalAffSel ? modalAffSel.value : '{AFF_CODE}';
    var modalUrlInput = document.getElementById('modal-sl-url-input');

    if (modalUrlInput) {
        modalUrlInput.value = _appUrl + '/smartlink/' + sl.slug + '?aff=' + encodeURIComponent(affCode);
    }
    document.getElementById('modal-sl-short-result').style.display = 'none';
}

function adminShortenSlModal() {
    var modalUrlInput = document.getElementById('modal-sl-url-input');
    if (!modalUrlInput || !modalUrlInput.value) return;

    var btn = document.getElementById('modal-sl-short-btn');
    btn.disabled = true; btn.textContent = '...';

    fetch('/affiliate/shorten', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_adminCsrf) + '&url=' + encodeURIComponent(modalUrlInput.value)
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; btn.innerHTML = '&#9986; Short';
        if (d.short_url) {
            document.getElementById('modal-sl-short-url').value = d.short_url;
            document.getElementById('modal-sl-short-result').style.display = 'block';
            document.getElementById('modal-sl-short-open').href = d.short_url;
        } else { alert(d.error || 'Failed to shorten'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '&#9986; Short'; });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Close modal on Escape or backdrop click
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSlDetailsModal();
});
document.getElementById('slDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeSlDetailsModal();
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
