<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

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
                    <th>Tracking Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $o): ?>
            <?php
                $geos = $o['geo_targeting'] ? json_decode($o['geo_targeting'], true) : [];
                $devs = $o['device_targeting'] ? json_decode($o['device_targeting'], true) : [];
                $trackLink = Helpers::trackingUrl() . '/click/' . $o['id'] . '?aff={AFFILIATE_CODE}';
                $statusColors = ['active'=>'success','paused'=>'warning','pending'=>'muted'];
                $sBadge = $statusColors[$o['status']] ?? 'muted';
            ?>
            <tr>
                <td>
                    <div style="font-weight:700;font-size:14px"><?= Helpers::e($o['name']) ?></div>
                    <div style="font-size:11px;color:#94A3B8">#<?= $o['id'] ?> &middot; <?= Helpers::e($o['payout_type']) ?>
                        <?php if ($o['category']): ?>&middot; <?= Helpers::e($o['category']) ?><?php endif; ?>
                    </div>
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
                <td style="max-width:200px">
                    <div style="font-family:monospace;font-size:10px;background:#F1F5F9;border-radius:4px;padding:4px 6px;word-break:break-all;color:#475569">
                        <?= Helpers::e(Helpers::trackingUrl() . '/click/' . $o['id'] . '?aff={CODE}') ?>
                    </div>
                </td>
                <td style="white-space:nowrap">
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

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
