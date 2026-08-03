<?php
require BASE_PATH . '/views/layouts/admin.php';

$trackingBaseUrl = rtrim(Helpers::trackingUrl(), '/');
$affiliates = $affiliates ?? [];

// Build data map for JS Details Modal
$offersDataMap = [];
foreach ($offers as $o) {
    $offersDataMap[$o['id']] = [
        'id'               => (int)$o['id'],
        'name'             => $o['name'],
        'offer_type'       => $o['offer_type'] ?: '',
        'payout_type'      => $o['payout_type'],
        'payout_amount'    => (float)$o['payout_amount'],
        'revenue_amount'   => (float)$o['revenue_amount'],
        'status'           => $o['status'],
        'visibility'       => $o['visibility'] ?? 'public',
        'require_approval' => (int)($o['require_approval'] ?? 0),
        'category'         => $o['category'] ?: '',
        'daily_cap'        => (int)($o['daily_cap'] ?? 0),
        'total_cap'        => (int)($o['total_cap'] ?? 0),
        'geos'             => $o['geo_targeting'] ? (json_decode($o['geo_targeting'], true) ?: []) : [],
        'devs'             => $o['device_targeting'] ? (json_decode($o['device_targeting'], true) ?: []) : [],
        'offer_url'        => $o['offer_url'] ?: '',
        'landing_pages'    => $o['landing_pages'] ? (json_decode($o['landing_pages'], true) ?: []) : [],
        'description'      => $o['description'] ?: '',
        'aff_count'        => (int)$o['aff_count'],
        'today_clicks'     => (int)$o['today_clicks'],
        'today_convs'      => (int)$o['today_convs'],
    ];
}
?>

<div class="page-header">
    <div>
        <h1>🏠 In-House Offers</h1>
        <p>Manage your own offers — no external advertiser needed</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?php
            $_exportParams = http_build_query(array_filter([
                'q'                => $qFilter         ?? '',
                'status_filter'    => $statusFilter    ?? '',
                'offer_type_filter'=> $offerTypeFilter ?? '',
                'offer_id'         => ($idFilter ?? 0) > 0 ? $idFilter : '',
                'access_filter'    => $accessFilter    ?? '',
                'export'           => 'csv',
            ], fn($v) => $v !== '' && $v !== null));
        ?>
        <a href="/admin/inhouse-offers?<?= $_exportParams ?>"
           class="btn btn-secondary"
           style="display:flex;align-items:center;gap:6px"
           title="Export current view to CSV">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
        <a href="/admin/inhouse-offers/create" class="btn btn-primary">+ Create In-House Offer</a>
    </div>
</div>

<?php foreach (Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type'] === 'success' ? 'success' : ($f['type'] === 'error' ? 'error' : 'info') ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:180px">
                <label style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Offer name…" value="<?= Helpers::e($qFilter ?? '') ?>" style="height:36px">
            </div>
            <div style="min-width:130px">
                <label style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;display:block;margin-bottom:4px">Status</label>
                <select name="status_filter" class="form-control" style="height:36px">
                    <option value="">All Statuses</option>
                    <?php foreach (['active','paused','pending'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:140px">
                <label style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;display:block;margin-bottom:4px">Offer Type</label>
                <select name="offer_type_filter" class="form-control" style="height:36px">
                    <option value="">All Types</option>
                    <?php foreach (['DOI','SOI','CPL','CPS','CPI','CPA','COD','FINANCE','CPM','CPC','REVSHARE','TRIAL'] as $_ot): ?>
                    <option value="<?= $_ot ?>" <?= ($offerTypeFilter ?? '') === $_ot ? 'selected' : '' ?>><?= $_ot ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="min-width:100px">
                <label style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;display:block;margin-bottom:4px">Offer ID</label>
                <input type="number" name="offer_id" class="form-control" placeholder="ID…" value="<?= ($idFilter ?? 0) > 0 ? (int)$idFilter : '' ?>" min="1" style="height:36px">
            </div>
            <div style="min-width:150px">
                <label style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;display:block;margin-bottom:4px">Access</label>
                <select name="access_filter" class="form-control" style="height:36px">
                    <option value="">All Offers</option>
                    <option value="active" <?= ($accessFilter ?? '') === 'active' ? 'selected' : '' ?>>Active Offers</option>
                    <option value="inactive" <?= ($accessFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Offers</option>
                    <option value="request" <?= ($accessFilter ?? '') === 'request' ? 'selected' : '' ?>>Request / Need Approval</option>
                    <option value="all_access" <?= ($accessFilter ?? '') === 'all_access' ? 'selected' : '' ?>>Access for All</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:36px;align-self:flex-end">Filter</button>
            <a href="/admin/inhouse-offers" class="btn btn-secondary" style="height:36px;line-height:22px;align-self:flex-end">Reset</a>
        </form>
    </div>
</div>

<?php if (!empty($offers)): ?>
<div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:8px;gap:10px">
    <span style="font-size:13px;color:#64748B"><?= count($offers) ?> offer<?= count($offers) !== 1 ? 's' : '' ?> shown</span>
    <a href="/admin/inhouse-offers?<?= $_exportParams ?>"
       style="font-size:13px;color:#4F46E5;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:4px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download CSV (<?= count($offers) ?>)
    </a>
</div>
<?php endif; ?>

<!-- Offers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding:14px 20px;border-bottom:1px solid #E2E8F0;background:#F8FAFC">
        <div class="fw-bold" style="font-size:14px;color:#1E293B">🏠 In-House Offers List</div>
        <div class="d-flex align-items-center gap-2">
            <label style="font-size:12px;font-weight:600;color:#64748B;white-space:nowrap">&#128100; Select Affiliate for All Links:</label>
            <select class="form-select form-select-sm" id="global-aff-select" style="font-size:12px;min-width:240px;border-radius:6px;border:1px solid #CBD5E1" onchange="applyGlobalAffiliateToOffers(this.value)">
                <option value="{CODE}">-- Generic Placeholder ({CODE}) --</option>
                <?php foreach ($affiliates as $aff): ?>
                <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                    [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?> (<?= Helpers::e($aff['email']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card-body" style="padding:0">
        <?php if (empty($offers)): ?>
        <div style="text-align:center;padding:60px 24px;color:#94A3B8">
            <div style="font-size:40px;margin-bottom:12px">🏠</div>
            <div style="font-size:16px;font-weight:600;margin-bottom:6px">No in-house offers yet</div>
            <div style="font-size:13px;margin-bottom:20px">Create your first offer to start tracking affiliate performance without an external advertiser.</div>
            <a href="/admin/inhouse-offers/create" class="btn btn-primary">+ Create First Offer</a>
        </div>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Type</th>
                    <th>Payout</th>
                    <th>Targeting</th>
                    <th>Affiliates</th>
                    <th>Today</th>
                    <th>Status</th>
                    <th style="min-width:280px">Tracking Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $o): ?>
            <?php
                $geos = $o['geo_targeting'] ? json_decode($o['geo_targeting'], true) : [];
                $devs = $o['device_targeting'] ? json_decode($o['device_targeting'], true) : [];
                $statusColors = ['active'=>'success','paused'=>'warning','pending'=>'muted'];
                $sBadge = $statusColors[$o['status']] ?? 'muted';
            ?>
            <tr>
                <td style="max-width:300px">
                    <div style="font-weight:700;font-size:14px;color:#0F172A"><?= Helpers::e($o['name']) ?></div>
                    <div style="font-size:11px;color:#94A3B8">#<?= $o['id'] ?> &middot; <?= Helpers::e($o['payout_type']) ?>
                        <?php if ($o['category']): ?>&middot; <?= Helpers::e($o['category']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($o['description'])): ?>
                        <?php
                            $fullDesc  = strip_tags($o['description']);
                            $shortDesc = mb_strimwidth($fullDesc, 0, 70, '...');
                        ?>
                        <div class="text-muted text-sm mt-1" style="font-size:12px;line-height:1.4;color:#64748B">
                            <?= Helpers::e($shortDesc) ?>
                            <button type="button" class="btn-link p-0 text-primary fw-semibold ms-1" style="font-size:11px;text-decoration:underline;border:none;background:none;cursor:pointer;color:#4F46E5" onclick="openIhOfferDetailsModal(<?= $o['id'] ?>)">See Details &raquo;</button>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn-link p-0 text-primary fw-semibold" style="font-size:11px;text-decoration:underline;border:none;background:none;cursor:pointer;color:#4F46E5" onclick="openIhOfferDetailsModal(<?= $o['id'] ?>)">See Details &raquo;</button>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($o['offer_type'])): ?>
                    <?php
                        $_otColors = [
                            'DOI'     => ['#EFF6FF','#1D4ED8'],
                            'SOI'     => ['#F0FDF4','#15803D'],
                            'CPL'     => ['#FEF9C3','#854D0E'],
                            'CPS'     => ['#DCFCE7','#065F46'],
                            'CPI'     => ['#F3E8FF','#6D28D9'],
                            'CPA'     => ['#ECFDF5','#047857'],
                            'COD'     => ['#FFF7ED','#C2410C'],
                            'FINANCE' => ['#EEF2FF','#4338CA'],
                            'CPM'     => ['#FDF4FF','#9333EA'],
                            'CPC'     => ['#FFFBEB','#B45309'],
                            'REVSHARE'=> ['#E0F2FE','#0369A1'],
                            'TRIAL'   => ['#FFEDD5','#EA580C'],
                        ];
                        $_bg  = $_otColors[$o['offer_type']][0] ?? '#F1F5F9';
                        $_clr = $_otColors[$o['offer_type']][1] ?? '#475569';
                    ?>
                    <span style="display:inline-block;background:<?= $_bg ?>;color:<?= $_clr ?>;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;white-space:nowrap"><?= Helpers::e($o['offer_type']) ?></span>
                    <?php else: ?>
                    <span style="color:#CBD5E1;font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-weight:700;color:#15803D">$<?= number_format($o['payout_amount'], 2) ?></span>
                    <?php if ($o['payout_type'] === 'RevShare'): ?>
                        <span style="font-size:11px;color:#64748B">/ rev</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px">
                    <?= Helpers::geoList($geos, 3) ?>
                    <?php if (!empty($devs)): ?>
                    <div style="color:#64748B"><?= implode(', ', $devs) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-info"><?= (int)$o['aff_count'] ?></span>
                </td>
                <td style="font-size:12px;text-align:center">
                    <div><?= (int)$o['today_clicks'] ?> clicks</div>
                    <div style="color:#15803D"><?= (int)$o['today_convs'] ?> conv</div>
                </td>
                <td>
                    <span class="badge badge-<?= $sBadge ?>"><?= ucfirst($o['status']) ?></span>
                </td>
                <td>
                    <div style="max-width:290px">
                        <div style="margin-bottom:5px">
                            <select class="form-select form-select-sm ih-aff-select" id="ih-aff-sel-<?= $o['id'] ?>" style="font-size:11px;padding:3px 8px;border-radius:6px;border:1px solid #CBD5E1;width:100%" onchange="updateIhAffLink(<?= $o['id'] ?>, '<?= Helpers::e($trackingBaseUrl . '/click/' . $o['id']) ?>')">
                                <option value="{CODE}">-- Select Affiliate ({CODE}) --</option>
                                <?php foreach ($affiliates as $aff): ?>
                                <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                                    [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="copy-group">
                            <input type="text" class="form-control" id="ih-<?= $o['id'] ?>" value="<?= Helpers::e($trackingBaseUrl . '/click/' . $o['id'] . '?aff={CODE}') ?>" readonly style="font-size:11px">
                            <button class="btn btn-secondary btn-sm" data-copy="ih-<?= $o['id'] ?>">Copy</button>
                            <button class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;cursor:pointer" id="ih-short-btn-<?= $o['id'] ?>" onclick="adminShortenIh(<?= $o['id'] ?>, document.getElementById('ih-<?= $o['id'] ?>').value)">&#9986; Short</button>
                        </div>
                        <div id="ih-short-result-<?= $o['id'] ?>" style="display:none;margin-top:6px">
                            <div class="copy-group">
                                <input type="text" id="ih-short-url-<?= $o['id'] ?>" class="form-control" readonly style="font-size:11px">
                                <button class="btn btn-secondary btn-sm" data-copy="ih-short-url-<?= $o['id'] ?>">Copy</button>
                                <a id="ih-short-open-<?= $o['id'] ?>" href="#" target="_blank" class="btn btn-sm btn-secondary">Open</a>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm btn-info text-white me-1" style="background:#0EA5E9;border:none;font-weight:600" onclick="openIhOfferDetailsModal(<?= $o['id'] ?>)">Details</button>
                    <a href="/admin/inhouse-offers/<?= $o['id'] ?>/edit" class="btn btn-secondary btn-sm">✎ Edit</a>
                    <a href="/admin/offers/<?= $o['id'] ?>/overview" class="btn btn-secondary btn-sm">📊</a>
                    <!-- Quick status toggle -->
                    <form method="POST" action="/admin/inhouse-offers/toggle-status" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                        <input type="hidden" name="status" value="<?= $o['status'] === 'active' ? 'paused' : 'active' ?>">
                        <button class="btn btn-sm <?= $o['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                            <?= $o['status'] === 'active' ? '⏸ Pause' : '▶ Activate' ?>
                        </button>
                    </form>
                    <form method="POST" action="/admin/inhouse-offers/delete" style="display:inline" onsubmit="return confirm('Delete this offer? This cannot be undone.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── IN-HOUSE OFFER DETAILS MODAL ────────────────────────────────────── -->
<div id="ihOfferDetailsModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.65);z-index:10000;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px)">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:760px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto">
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:16px;border-bottom:1px solid #E2E8F0;margin-bottom:20px">
            <div>
                <div style="display:flex;align-items:center;gap:8px">
                    <h3 id="modal-ih-title" style="margin:0;font-size:18px;font-weight:700;color:#0F172A">In-House Offer Details</h3>
                    <span id="modal-ih-id-badge" style="background:#F1F5F9;color:#475569;font-family:monospace;font-size:12px;padding:2px 8px;border-radius:6px;font-weight:700">#0</span>
                </div>
                <div style="font-size:12px;color:#64748B;margin-top:2px">Full offer configuration, targeting rules & affiliate tracking link</div>
            </div>
            <button type="button" onclick="closeIhOfferDetailsModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#94A3B8;line-height:1">&times;</button>
        </div>

        <!-- Overview Badges -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Offer Type</div>
                <div id="modal-ih-type" style="font-size:12px;font-weight:700;color:#4F46E5;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Payout / Revenue</div>
                <div id="modal-ih-payout" style="font-size:12px;font-weight:700;color:#15803D;margin-top:2px">$0.00</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Access Control</div>
                <div id="modal-ih-access" style="font-size:12px;font-weight:700;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Status</div>
                <div id="modal-ih-status" style="font-size:12px;font-weight:700;margin-top:2px">-</div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px">
                <div style="font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase">Approved Affiliates</div>
                <div id="modal-ih-affs" style="font-size:12px;font-weight:700;color:#0F172A;margin-top:2px">0</div>
            </div>
        </div>

        <!-- Description Box -->
        <div style="margin-bottom:20px">
            <label style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:6px">&#128220; Description & Traffic Terms</label>
            <div id="modal-ih-description" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;font-size:13px;line-height:1.6;color:#334155;max-height:200px;overflow-y:auto;white-space:pre-wrap">No description provided.</div>
        </div>

        <!-- Separate Affiliate Tracking Link Box inside Modal -->
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:16px;margin-bottom:20px">
            <div style="font-size:13px;font-weight:700;color:#1E40AF;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <span>&#128279; Generate Affiliate Tracking Link</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px;align-items:center">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Select Affiliate:</label>
                    <select class="form-select form-select-sm" id="modal-ih-aff-select" style="font-size:12px;border-radius:6px;border:1px solid #93C5FD" onchange="updateModalIhAffLink()">
                        <option value="{CODE}">-- Generic Placeholder ({CODE}) --</option>
                        <?php foreach ($affiliates as $aff): ?>
                        <option value="<?= Helpers::e($aff['affiliate_code']) ?>">
                            [<?= Helpers::e($aff['affiliate_code']) ?>] <?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#475569;display:block;margin-bottom:4px">Tracking Link URL:</label>
                    <div class="copy-group">
                        <input type="text" id="modal-ih-url-input" class="form-control" readonly style="font-size:11px;background:#fff">
                        <button type="button" class="btn btn-secondary btn-sm" data-copy="modal-ih-url-input">Copy</button>
                        <button type="button" class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;cursor:pointer" id="modal-ih-short-btn" onclick="adminShortenIhModal()">&#9986; Short</button>
                    </div>
                </div>
            </div>
            <div id="modal-ih-short-result" style="display:none;margin-top:8px">
                <div class="copy-group">
                    <input type="text" id="modal-ih-short-url" class="form-control" readonly style="font-size:11px">
                    <button type="button" class="btn btn-secondary btn-sm" data-copy="modal-ih-short-url">Copy</button>
                    <a id="modal-ih-short-open" href="#" target="_blank" class="btn btn-sm btn-secondary">Open</a>
                </div>
            </div>
        </div>

        <!-- Targeting & Caps Summary -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
            <div style="border:1px solid #E2E8F0;border-radius:10px;padding:12px">
                <div style="font-size:12px;font-weight:700;color:#334155;margin-bottom:6px">&#127758; Targeted GEOs</div>
                <div id="modal-ih-geos" style="font-size:12px;color:#475569">All Geos</div>
            </div>
            <div style="border:1px solid #E2E8F0;border-radius:10px;padding:12px">
                <div style="font-size:12px;font-weight:700;color:#334155;margin-bottom:6px">&#128241; Targeted Devices</div>
                <div id="modal-ih-devs" style="font-size:12px;color:#475569">All Devices</div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:16px;border-top:1px solid #E2E8F0">
            <a id="modal-ih-edit-btn" href="#" class="btn btn-secondary">✎ Edit Offer</a>
            <button type="button" class="btn btn-primary" onclick="closeIhOfferDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
var _adminCsrf = '<?= Auth::generateCsrf() ?>';
var _trackingBaseUrl = '<?= Helpers::e($trackingBaseUrl) ?>';
var _offersDataMap = <?= json_encode($offersDataMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

// Global copy handler
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

function updateIhAffLink(id, baseUrl) {
    var affSel = document.getElementById('ih-aff-sel-' + id);
    var affCode = affSel ? affSel.value : '{CODE}';
    var input = document.getElementById('ih-' + id);
    if (input) {
        input.value = baseUrl + '?aff=' + encodeURIComponent(affCode);
    }
    var shortRes = document.getElementById('ih-short-result-' + id);
    if (shortRes) shortRes.style.display = 'none';
}

function applyGlobalAffiliateToOffers(affCode) {
    document.querySelectorAll('.ih-aff-select').forEach(function(sel) {
        sel.value = affCode;
        sel.dispatchEvent(new Event('change'));
    });
}

function adminShortenIh(id, customUrl) {
    var btn = document.getElementById('ih-short-btn-' + id);
    var url = customUrl || (document.getElementById('ih-' + id) ? document.getElementById('ih-' + id).value : '');
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
            document.getElementById('ih-short-url-' + id).value = d.short_url;
            document.getElementById('ih-short-result-' + id).style.display = 'block';
            document.getElementById('ih-short-open-' + id).href = d.short_url;
        } else { alert(d.error || 'Failed to shorten'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '&#9986; Short'; });
}

// Modal functions
var _currentModalOfferId = null;

function openIhOfferDetailsModal(offerId) {
    var o = _offersDataMap[offerId];
    if (!o) return;

    _currentModalOfferId = offerId;

    document.getElementById('modal-ih-title').textContent = o.name;
    document.getElementById('modal-ih-id-badge').textContent = '#' + o.id;
    document.getElementById('modal-ih-type').textContent = o.offer_type || o.payout_type || 'Standard';
    document.getElementById('modal-ih-payout').textContent = '$' + Number(o.payout_amount).toFixed(2) + ' / $' + Number(o.revenue_amount).toFixed(2) + ' rev';
    document.getElementById('modal-ih-access').innerHTML = o.require_approval ? '<span class="badge badge-warning" style="font-size:11px">Manual Approval</span>' : '<span class="badge badge-success" style="font-size:11px">Auto Access</span>';
    document.getElementById('modal-ih-status').innerHTML = '<span class="badge badge-' + (o.status === 'active' ? 'success' : (o.status === 'paused' ? 'warning' : 'muted')) + '">' + o.status + '</span>';
    document.getElementById('modal-ih-affs').textContent = o.aff_count + ' approved affiliates';
    document.getElementById('modal-ih-description').textContent = o.description || 'No description provided for this offer.';
    document.getElementById('modal-ih-edit-btn').href = '/admin/inhouse-offers/' + o.id + '/edit';

    // Target Geos
    var geosContainer = document.getElementById('modal-ih-geos');
    if (o.geos && o.geos.length > 0) {
        geosContainer.innerHTML = o.geos.map(g => '<span class="badge badge-info" style="font-size:10px;margin-right:3px">' + escapeHtml(g) + '</span>').join(' ');
    } else {
        geosContainer.innerHTML = '<span style="color:#94A3B8">All Countries</span>';
    }

    // Target Devices
    var devsContainer = document.getElementById('modal-ih-devs');
    if (o.devs && o.devs.length > 0) {
        devsContainer.innerHTML = o.devs.map(d => '<span class="badge" style="background:#E0E7FF;color:#3730A3;font-size:10px;margin-right:3px">' + escapeHtml(d) + '</span>').join(' ');
    } else {
        devsContainer.innerHTML = '<span style="color:#94A3B8">All Devices</span>';
    }

    // Match row selector to modal selector
    var rowAffSel = document.getElementById('ih-aff-sel-' + offerId);
    var modalAffSel = document.getElementById('modal-ih-aff-select');
    if (rowAffSel && modalAffSel) {
        modalAffSel.value = rowAffSel.value;
    }
    updateModalIhAffLink();

    document.getElementById('ihOfferDetailsModal').style.display = 'flex';
}

function closeIhOfferDetailsModal() {
    document.getElementById('ihOfferDetailsModal').style.display = 'none';
}

function updateModalIhAffLink() {
    if (!_currentModalOfferId) return;
    var o = _offersDataMap[_currentModalOfferId];
    if (!o) return;

    var modalAffSel = document.getElementById('modal-ih-aff-select');
    var affCode = modalAffSel ? modalAffSel.value : '{CODE}';
    var modalUrlInput = document.getElementById('modal-ih-url-input');

    if (modalUrlInput) {
        modalUrlInput.value = _trackingBaseUrl + '/click/' + o.id + '?aff=' + encodeURIComponent(affCode);
    }
    document.getElementById('modal-ih-short-result').style.display = 'none';
}

function adminShortenIhModal() {
    var modalUrlInput = document.getElementById('modal-ih-url-input');
    if (!modalUrlInput || !modalUrlInput.value) return;

    var btn = document.getElementById('modal-ih-short-btn');
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
            document.getElementById('modal-ih-short-url').value = d.short_url;
            document.getElementById('modal-ih-short-result').style.display = 'block';
            document.getElementById('modal-ih-short-open').href = d.short_url;
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
    if (e.key === 'Escape') closeIhOfferDetailsModal();
});
document.getElementById('ihOfferDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeIhOfferDetailsModal();
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
