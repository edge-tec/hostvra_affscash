<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128279; Smart Links</h1>
        <p>Browse all available smart links and manage access requests from your affiliates</p>
    </div>
    <div>
        <?php if ($pendingRequestsCount > 0): ?>
        <a href="/affiliate_manager/smartlinks?action=requests" class="btn btn-warning btn-sm">
            &#9888; <?= $pendingRequestsCount ?> Pending Request<?= $pendingRequestsCount > 1 ? 's' : '' ?>
        </a>
        <?php endif; ?>
        <a href="/affiliate_manager/smartlinks?action=requests" class="btn btn-secondary btn-sm">View All Requests</a>
    </div>
</div>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/affiliate_manager/smartlinks" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="action" value="list">
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Smartlink name..." value="<?= Helpers::e($qSmartlink ?? '') ?>" style="min-width:200px">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <a href="/affiliate_manager/smartlinks" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Available Smart Links</span>
        <span class="text-muted text-sm"><?= number_format(count($smartlinks)) ?> smartlinks</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-mgr-smartlinks" style="font-size:13px">
            <thead><tr>
                <th>#ID</th>
                <th>SMART LINK</th>
                <th>NICHE / CATEGORY</th>
                <th>MY AFFILIATES (Approved)</th>
                <th>PENDING REQUESTS</th>
                <th>TOTAL AFFILIATES</th>
                <th>TRACKING LINK</th>
                <th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php if (empty($smartlinks)): ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:32px">No active smartlinks found.</td></tr>
            <?php else: ?>
            <?php foreach ($smartlinks as $sl):
                $slUrl = $appUrl . '/smartlink/' . $sl['slug'] . '?aff=';
            ?>
            <tr>
                <td class="text-muted" style="font-size:11px;white-space:nowrap;font-family:monospace">#<?= $sl['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($sl['name']) ?></div>
                    <?php if (!empty($sl['description'])): ?>
                    <div class="text-sm text-muted" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helpers::e($sl['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= Helpers::e($sl['niche'] ?? $sl['category'] ?? '—') ?></td>
                <td>
                    <?php if ($sl['my_approved'] > 0): ?>
                    <span class="badge badge-success"><?= (int)$sl['my_approved'] ?> affiliate<?= $sl['my_approved'] != 1 ? 's' : '' ?></span>
                    <?php else: ?>
                    <span class="text-muted text-sm">None</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($sl['my_pending'] > 0): ?>
                    <a href="/affiliate_manager/smartlinks?action=requests&status=pending" class="badge badge-warning" style="text-decoration:none"><?= (int)$sl['my_pending'] ?> pending</a>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= number_format((int)$sl['total_approved']) ?></td>
                <td style="min-width:260px">
                    <?php if (empty($managedAffiliates)): ?>
                    <span class="text-muted text-sm">No managed affiliates</span>
                    <?php else: ?>
                    <select class="form-control sl-aff-sel" data-sl="<?= $sl['id'] ?>" data-base="<?= Helpers::e($slUrl) ?>"
                            onchange="slBuildLink(this)" style="font-size:11px;margin-bottom:4px">
                        <option value="">— Select affiliate —</option>
                        <?php foreach ($managedAffiliates as $ma): ?>
                        <option value="<?= Helpers::e($ma['affiliate_code']) ?>"><?= Helpers::e($ma['name']) ?> (<?= Helpers::e($ma['affiliate_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="copy-group">
                        <input type="text" id="sl-tl-<?= $sl['id'] ?>" class="form-control" placeholder="Select affiliate above..." readonly style="font-size:10px">
                        <button class="btn btn-secondary btn-sm" onclick="slCopyLink(<?= $sl['id'] ?>)">Copy</button>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/affiliate_manager/smartlinks?action=requests&status=all" class="btn btn-secondary btn-sm" title="View requests for this smartlink">
                        &#128279; Requests
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function slBuildLink(sel) {
    var slId = sel.getAttribute('data-sl');
    var base = sel.getAttribute('data-base');
    var out  = document.getElementById('sl-tl-' + slId);
    out.value = sel.value ? base + sel.value : '';
}
function slCopyLink(slId) {
    var out = document.getElementById('sl-tl-' + slId);
    if (!out.value) return;
    navigator.clipboard ? navigator.clipboard.writeText(out.value) : (out.select(), document.execCommand('copy'));
    event.target.textContent = 'Copied!';
    setTimeout(function(){ event.target.textContent = 'Copy'; }, 1500);
}
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    var $t = $('#tbl-mgr-smartlinks');
    if ($t.find('tbody tr td:first-child').length > 0) {
        $t.DataTable({destroy:true,pageLength:25,order:[[0,'asc']],language:{search:'Search:',emptyTable:'No smartlinks'}});
    }
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
