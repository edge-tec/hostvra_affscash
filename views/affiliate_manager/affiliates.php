<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div><h1>My Affiliates</h1><p>Affiliates assigned to your account</p></div>
    <a href="/affiliate_manager/affiliates?action=create" class="btn btn-primary btn-sm">+ Create Affiliate</a>
</div>

<!-- Fraud Conversion Score filter (client-side, applied to the DataTable) -->
<div class="card mb-3">
    <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Fraud Conv. Score Filter</span>
        <select id="mgr-fs-filter" class="form-control" style="width:auto;min-width:160px">
            <option value="">All scores</option>
            <option value="high">High risk (70–100)</option>
            <option value="medium">Medium risk (40–69)</option>
            <option value="low">Low risk (0–39)</option>
        </select>
        <span class="text-muted text-sm" style="margin-left:auto">Live blend over the last 30 days · clicks + conversions</span>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-mgr-affiliates">
            <thead><tr><th>Affiliate</th><th>Code</th><th>Company</th><th>Balance</th><th>Fraud Conv. Score</th><th>Legacy Flag</th><th>Status</th><th>Joined</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($affiliates as $aff):
                $_aid    = (int)$aff['aff_id'];
                $_fScore = FraudScore::forAffiliate($_aid);
                $_fLevel = FraudScore::level($_fScore);
            ?>
            <tr data-fs-level="<?= $_fLevel ?>">
                <td><div class="fw-bold"><?= Helpers::e($aff['first_name'].' '.$aff['last_name']) ?></div><div class="text-sm text-muted"><?= Helpers::e($aff['email']) ?></div></td>
                <td><code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($aff['affiliate_code']) ?></code></td>
                <td><?= Helpers::e($aff['company'] ?: '—') ?></td>
                <td>$<?= number_format($aff['balance'],2) ?></td>
                <td data-order="<?= $_fScore ?>"><?= FraudScore::badge($_fScore) ?></td>
                <td><span class="badge <?= $aff['fraud_score']>=75?'badge-danger':($aff['fraud_score']>=40?'badge-warning':'badge-success') ?>"><?= $aff['fraud_score'] ?></span></td>
                <td><span class="badge badge-<?= ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'muted'][$aff['status']]??'muted' ?>"><?= $aff['status'] ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, Y',strtotime($aff['created_at'])) ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate_manager/affiliates?action=view&id=<?= $aff['aff_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="/affiliate_manager/affiliates?action=edit&id=<?= $aff['aff_id'] ?>" class="btn btn-secondary btn-sm">✎ Edit</a>
                    <?php if ($aff['status'] === 'active'): ?>
                    <form method="POST" action="/affiliate_manager/login-as-affiliate" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="aff_id" value="<?= $aff['aff_id'] ?>">
                        <button class="btn btn-sm" style="background:#4F46E5;color:#fff" title="Log in as this affiliate">🔑 Login</button>
                    </form>
                    <?php endif; ?>
                    <?php if (Auth::hasPermission('approve_affiliates')): ?>
                    <?php if ($aff['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="aff_id" value="<?= $aff['aff_id'] ?>">
                        <input type="hidden" name="status" value="active">
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="aff_id" value="<?= $aff['aff_id'] ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <?php elseif ($aff['status'] === 'active'): ?>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="aff_id" value="<?= $aff['aff_id'] ?>">
                        <input type="hidden" name="status" value="suspended">
                        <button class="btn btn-warning btn-sm">Suspend</button>
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

<script>
$(function() {
    var dt = $('#tbl-mgr-affiliates').DataTable({
        destroy: true,
        pageLength: 25,
        order: [],
        columnDefs: [{ targets: [4], type: 'num' }],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });

    // Fraud-score level filter — uses a custom DataTables search plugin
    // bound to data-fs-level on each row.
    var $sel = $('#mgr-fs-filter');
    $.fn.dataTable.ext.search.push(function (settings, _data, _index, _row, dataIndex) {
        if (settings.nTable.id !== 'tbl-mgr-affiliates') return true;
        var picked = $sel.val();
        if (!picked) return true;
        var tr = settings.aoData[dataIndex].nTr;
        return tr && tr.getAttribute('data-fs-level') === picked;
    });
    $sel.on('change', function () { dt.draw(); });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
