<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>&#127968; In-House Offers</h1><p>Browse all in-house offers and generate affiliate tracking links</p></div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/affiliate_manager/inhouse-offers" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Offer name..." value="<?= Helpers::e($qFilter ?? '') ?>" style="min-width:140px"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Offer Type</label>
                <select name="offer_type_filter" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($offerTypes as $ot): ?>
                    <option value="<?= Helpers::e($ot['offer_type']) ?>" <?= ($offerTypeFilter ?? '') === $ot['offer_type'] ? 'selected' : '' ?>><?= Helpers::e($ot['offer_type']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Status</label>
                <select name="status_filter" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['active','paused','pending'] as $sf): ?>
                    <option value="<?= $sf ?>" <?= ($statusFilter ?? '') === $sf ? 'selected' : '' ?>><?= ucfirst($sf) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Country</label>
                <input type="text" name="country" class="form-control" placeholder="US" value="<?= Helpers::e($countryFilter ?? '') ?>" maxlength="2" style="width:70px;text-transform:uppercase"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Offer ID</label>
                <input type="number" name="offer_id" class="form-control" placeholder="ID..." value="<?= ($idFilter ?? 0) > 0 ? (int)$idFilter : '' ?>" min="1" style="width:80px"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Access</label>
                <select name="access_filter" class="form-control">
                    <option value="">All Offers</option>
                    <option value="active" <?= ($accessFilter ?? '') === 'active' ? 'selected' : '' ?>>Active Offers</option>
                    <option value="inactive" <?= ($accessFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Offers</option>
                    <option value="request" <?= ($accessFilter ?? '') === 'request' ? 'selected' : '' ?>>Request / Need Approval</option>
                    <option value="all_access" <?= ($accessFilter ?? '') === 'all_access' ? 'selected' : '' ?>>Access for All</option>
                </select></div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="/affiliate_manager/inhouse-offers" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">&#127968; In-House Offers</span>
        <span class="text-muted text-sm"><?= number_format(count($offers)) ?> offers</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-mgr-inhouse" style="font-size:13px">
            <thead><tr>
                <th>#ID</th><th>OFFER</th><th>TYPE</th><th>PAYOUT</th><th>GEO</th><th>AFFILIATES</th><th>TODAY</th><th>STATUS</th><th>AFFILIATE LINK GENERATOR</th>
            </tr></thead>
            <tbody>
            <?php if (empty($offers)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:32px">No in-house offers found.</td></tr>
            <?php else: ?>
            <?php foreach ($offers as $o):
                $geos    = $o['geo_targeting'] ? json_decode($o['geo_targeting'], true) : [];
                $baseUrl = $appUrl . '/offer/' . $o['id'] . '?aff_id=';
            ?>
            <tr>
                <td class="text-muted" style="font-size:11px;white-space:nowrap">#<?= $o['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($o['name']) ?></div>
                    <div class="text-sm text-muted">
                        <?php if ($o['category'] ?? ''): ?><?= Helpers::e($o['category']) ?><?php endif; ?>
                        <?php if ($o['offer_type'] ?? ''): ?>· <span class="badge badge-success" style="font-size:10px"><?= Helpers::e($o['offer_type']) ?></span><?php endif; ?>
                        <span class="badge" style="font-size:10px;background:#7C3AED;color:#fff">In-House</span>
                    </div>
                </td>
                <td><span class="badge badge-info"><?= Helpers::e($o['payout_type'] ?? 'CPA') ?></span></td>
                <td class="fw-bold">
                    <?php if (($o['payout_type'] ?? '') === 'RevShare'): ?>
                    <?= number_format((float)$o['payout_amount'], 2) ?>%
                    <?php else: ?>
                    $<?= number_format((float)$o['payout_amount'], 2) ?>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= Helpers::geoList($geos, 3) ?></td>
                <td class="text-sm text-center"><?= number_format((int)$o['aff_count']) ?></td>
                <td class="text-sm" style="white-space:nowrap">
                    <span title="Today's clicks" style="color:#4F46E5">&#9654; <?= (int)$o['today_clicks'] ?></span>
                    &nbsp;<span title="Today's conversions" style="color:#059669">&#10003; <?= (int)$o['today_convs'] ?></span>
                </td>
                <td>
                    <?php $sc = ['active'=>'success','paused'=>'warning','pending'=>'info']; ?>
                    <span class="badge badge-<?= $sc[$o['status']] ?? 'muted' ?>"><?= $o['status'] ?></span>
                </td>
                <td style="min-width:260px">
                    <?php if (empty($managedAffiliates)): ?>
                    <span class="text-muted text-sm">No managed affiliates</span>
                    <?php else: ?>
                    <select class="form-control ih-aff-sel" data-offer="<?= $o['id'] ?>" data-base="<?= Helpers::e($baseUrl) ?>" onchange="ihBuildLink(this)" style="font-size:11px;margin-bottom:4px">
                        <option value="">— Select affiliate —</option>
                        <?php foreach ($managedAffiliates as $ma): ?>
                        <option value="<?= Helpers::e($ma['affiliate_code']) ?>"><?= Helpers::e($ma['name']) ?> (<?= Helpers::e($ma['affiliate_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="copy-group">
                        <input type="text" id="ih-tl-<?= $o['id'] ?>" class="form-control" placeholder="Select affiliate above..." readonly style="font-size:10px">
                        <button class="btn btn-secondary btn-sm" onclick="ihCopyLink(<?= $o['id'] ?>, event)">Copy</button>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function ihBuildLink(sel) {
    var offerId = sel.getAttribute('data-offer');
    var base    = sel.getAttribute('data-base');
    var out     = document.getElementById('ih-tl-' + offerId);
    out.value   = sel.value ? base + sel.value : '';
}
function ihCopyLink(offerId, e) {
    var out = document.getElementById('ih-tl-' + offerId);
    if (!out.value) return;
    navigator.clipboard ? navigator.clipboard.writeText(out.value) : (out.select(), document.execCommand('copy'));
    var btn = e.target;
    btn.textContent = 'Copied!';
    setTimeout(function(){ btn.textContent = 'Copy'; }, 1500);
}
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    var $t = $('#tbl-mgr-inhouse');
    if ($t.find('tbody tr td:first-child').length > 0) {
        $t.DataTable({destroy:true,pageLength:25,order:[[0,'desc']],language:{search:'Search:',emptyTable:'No offers'}});
    }
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
