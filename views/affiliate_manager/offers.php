<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>&#127991; Offers</h1><p>Browse all available offers and access tracking links</p></div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <a href="/affiliate_manager/offers?<?= http_build_query(array_merge($_GET, ['tab'=>'regular'])) ?>"
       style="padding:10px 20px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;color:<?= ($tab??'regular')==='regular'?'#7C3AED':'#64748B' ?>;border-bottom:<?= ($tab??'regular')==='regular'?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        &#127991; Regular Offers
    </a>
    <a href="/affiliate_manager/offers?<?= http_build_query(array_merge($_GET, ['tab'=>'inhouse'])) ?>"
       style="padding:10px 20px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;color:<?= ($tab??'')==='inhouse'?'#7C3AED':'#64748B' ?>;border-bottom:<?= ($tab??'')==='inhouse'?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        &#127968; In-House Offers
    </a>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/affiliate_manager/offers" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <input type="hidden" name="tab" value="<?= Helpers::e($tab ?? 'regular') ?>">
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Offer name..." value="<?= Helpers::e($qFilter ?? '') ?>" style="min-width:140px"></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= Helpers::e($cat['category']) ?>" <?= ($catFilter ?? '') === $cat['category'] ? 'selected' : '' ?>><?= Helpers::e($cat['category']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Offer Type</label>
                <select name="offer_type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($offerTypes as $ot): ?>
                    <option value="<?= Helpers::e($ot['offer_type']) ?>" <?= ($offerTypeFilter ?? '') === $ot['offer_type'] ? 'selected' : '' ?>><?= Helpers::e($ot['offer_type']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Payout Type</label>
                <select name="payout_type" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['CPA','CPL','CPS','CPM','CPC','RevShare','Trial','Other'] as $pt): ?>
                    <option value="<?= $pt ?>" <?= ($typeFilter ?? '') === $pt ? 'selected' : '' ?>><?= $pt ?></option>
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
            <div><label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Device</label>
                <select name="device" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['desktop','mobile','tablet'] as $dv): ?>
                    <option value="<?= $dv ?>" <?= ($deviceFilter ?? '') === $dv ? 'selected' : '' ?>><?= ucfirst($dv) ?></option>
                    <?php endforeach; ?>
                </select></div>
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
            <a href="/affiliate_manager/offers" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= ($tab??'regular')==='inhouse' ? '&#127968; In-House Offers' : '&#127991; Regular Offers' ?></span>
        <span class="text-muted text-sm"><?= number_format(count($offers)) ?> offers</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-mgr-offers" style="font-size:13px">
            <thead><tr>
                <th>#ID</th><th>OFFER</th><th>TYPE</th><th>PAYOUT</th><th>GEO</th><th>AFFILIATES</th><th>FRAUD SCORE</th><th>STATUS</th><th>AFFILIATE LINK GENERATOR</th>
            </tr></thead>
            <tbody>
            <?php if (empty($offers)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:32px">No offers match your filters.</td></tr>
            <?php else: ?>
            <?php foreach ($offers as $o):
                $geos     = $o['geo_targeting'] ? json_decode($o['geo_targeting'], true) : [];
                $baseUrl  = Helpers::trackingUrl() . '/click/' . $o['id'] . '?aff=';
                $_oScore  = FraudScore::forOffer((int)$o['id']);
                $_oLevel  = FraudScore::level($_oScore);
            ?>
            <tr data-fs-level="<?= $_oLevel ?>">
                <td class="text-muted" style="font-size:11px;white-space:nowrap">#<?= $o['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($o['name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($o['category'] ?: '—') ?>
                        <?php if ($o['offer_type'] ?? ''): ?>· <span class="badge badge-success" style="font-size:10px"><?= Helpers::e($o['offer_type']) ?></span><?php endif; ?>
                        <?php if (!empty($o['is_inhouse'])): ?>· <span class="badge" style="font-size:10px;background:#7C3AED;color:#fff">In-House</span><?php endif; ?>
                    </div>
                </td>
                <td><span class="badge badge-info"><?= Helpers::e($o['payout_type']) ?></span></td>
                <td class="fw-bold">
                    <?php if ($o['payout_type'] === 'RevShare'): ?>
                    <?= number_format($o['payout_amount'],2) ?>%
                    <?php else: ?>
                    $<?= number_format($o['payout_amount'],2) ?>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= Helpers::geoList($geos, 3) ?></td>
                <td class="text-sm"><?= number_format($o['aff_count']) ?></td>
                <td data-order="<?= $_oScore ?>"><?= FraudScore::badge($_oScore) ?></td>
                <td>
                    <?php $sc = ['active'=>'success','paused'=>'warning','pending'=>'info']; ?>
                    <span class="badge badge-<?= $sc[$o['status']] ?? 'muted' ?>"><?= $o['status'] ?></span>
                </td>
                <td style="min-width:260px">
                    <?php if (empty($managedAffiliates)): ?>
                    <span class="text-muted text-sm">No managed affiliates</span>
                    <?php else: ?>
                    <select class="form-control mgr-aff-sel" data-offer="<?= $o['id'] ?>" data-base="<?= Helpers::e($baseUrl) ?>" onchange="mgrBuildLink(this)" style="font-size:11px;margin-bottom:4px">
                        <option value="">— Select affiliate —</option>
                        <?php foreach ($managedAffiliates as $ma): ?>
                        <option value="<?= Helpers::e($ma['affiliate_code']) ?>"><?= Helpers::e($ma['name']) ?> (<?= Helpers::e($ma['affiliate_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="copy-group">
                        <input type="text" id="mgr-tl-<?= $o['id'] ?>" class="form-control" placeholder="Select affiliate above..." readonly style="font-size:10px">
                        <button class="btn btn-secondary btn-sm" onclick="mgrCopyLink(<?= $o['id'] ?>)">Copy</button>
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
function mgrBuildLink(sel) {
    var offerId = sel.getAttribute('data-offer');
    var base    = sel.getAttribute('data-base');
    var out     = document.getElementById('mgr-tl-' + offerId);
    out.value   = sel.value ? base + sel.value + '&sub1=' : '';
}
function mgrCopyLink(offerId) {
    var out = document.getElementById('mgr-tl-' + offerId);
    if (!out.value) return;
    navigator.clipboard ? navigator.clipboard.writeText(out.value) : (out.select(), document.execCommand('copy'));
    event.target.textContent = 'Copied!';
    setTimeout(function(){ event.target.textContent = 'Copy'; }, 1500);
}
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    var $t = $('#tbl-mgr-offers');
    if ($t.find('tbody tr td:first-child').length === 0) return;
    var dt = $t.DataTable({
        destroy:    true,
        pageLength: 25,
        order:      [[0,'asc']],
        columnDefs: [{ targets: [6], type: 'num' }],
        language:   { search:'Search:', emptyTable:'No offers' }
    });

    // Optional fraud-score filter — appears above the search field
    var $bar = $('<div style="display:flex;gap:10px;align-items:center;margin-bottom:10px"></div>')
        .append('<span style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Fraud Score</span>')
        .append('<select id="mgr-offer-fs-filter" class="form-control" style="width:auto;min-width:140px">' +
                '<option value="">All</option>' +
                '<option value="high">High</option>' +
                '<option value="medium">Medium</option>' +
                '<option value="low">Low</option></select>');
    $('#tbl-mgr-offers_wrapper').prepend($bar);
    $.fn.dataTable.ext.search.push(function (settings, _data, _index, _row, dataIndex) {
        if (settings.nTable.id !== 'tbl-mgr-offers') return true;
        var picked = $('#mgr-offer-fs-filter').val();
        if (!picked) return true;
        var tr = settings.aoData[dataIndex].nTr;
        return tr && tr.getAttribute('data-fs-level') === picked;
    });
    $bar.on('change', '#mgr-offer-fs-filter', function () { dt.draw(); });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
