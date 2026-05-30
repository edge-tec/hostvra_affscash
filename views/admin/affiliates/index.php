<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Affiliates</h1>
        <p>Manage affiliate accounts and approvals</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/affiliates?export=csv<?= $status !== 'all' ? '&status='.$status : '' ?>" class="btn btn-secondary">&#8595; Export CSV</a>
        <a href="/admin/affiliates/create" class="btn btn-primary">+ Create Affiliate</a>
        <a href="/admin/payout-management" class="btn btn-secondary" title="Advanced Payout Management">&#9881; Payout Management</a>
        <?php $statuses = ['all','active','pending','suspended','rejected']; ?>
        <?php foreach($statuses as $s): ?>
        <a href="/admin/affiliates<?= $s !== 'all' ? '?status='.$s : '' ?>" class="btn <?= $status === $s ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-affiliates">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Code</th>
                    <th>Company</th>
                    <th>Balance</th>
                    <th>Fraud Score</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Days Inactive</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $_inacCfgDays = $inactivityCfgDays ?? 30;
            foreach($affiliates as $aff):
                $daysInactive    = $aff['days_inactive'];
                $daysInactiveInt = $daysInactive === null ? null : (int)$daysInactive;
                $autoDeactivated = !empty($aff['inactivity_deactivated_at']);
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?></div>
                    <div class="text-muted text-sm"><?= Helpers::e($aff['email']) ?></div>
                </td>
                <td><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($aff['affiliate_code']) ?></code></td>
                <td><?= Helpers::e($aff['company'] ?: '—') ?></td>
                <td>$<?= number_format($aff['balance'],2) ?></td>
                <td>
                    <?php
                    $fs       = (int)$aff['fraud_score'];
                    $fsCount  = (int)($aff['fraud_checked_count'] ?? 0);
                    $fsTitle  = $fsCount > 0
                        ? 'Avg IPQS score across ' . $fsCount . ' checked conversion' . ($fsCount === 1 ? '' : 's')
                        : 'No checked conversions yet';
                    ?>
                    <?php if ($fsCount === 0): ?>
                    <span class="badge badge-muted" title="<?= Helpers::e($fsTitle) ?>">—</span>
                    <?php else: ?>
                    <span class="badge <?= $fs >= 75 ? 'badge-danger' : ($fs >= 40 ? 'badge-warning' : 'badge-success') ?>" title="<?= Helpers::e($fsTitle) ?>"><?= $fs ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $bm = ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'muted']; ?>
                    <span class="badge badge-<?= $bm[$aff['status']] ?? 'muted' ?>" title="<?= $autoDeactivated ? 'Auto-deactivated for inactivity on ' . Helpers::e(date('Y-m-d', strtotime($aff['inactivity_deactivated_at']))) : '' ?>">
                        <?= $aff['status'] === 'suspended' && $autoDeactivated ? 'deactivated' : $aff['status'] ?>
                    </span>
                </td>
                <td class="text-sm text-muted" data-order="<?= $aff['last_login'] ? Helpers::e($aff['last_login']) : '0' ?>">
                    <?php if ($aff['last_login']): ?>
                        <?= Helpers::e(date('M j, Y', strtotime($aff['last_login']))) ?>
                        <div style="font-size:11px;color:#94A3B8"><?= Helpers::e(date('H:i', strtotime($aff['last_login']))) ?></div>
                    <?php else: ?>
                        <span style="color:#94A3B8">Never</span>
                    <?php endif; ?>
                </td>
                <td data-order="<?= $daysInactiveInt === null ? -1 : $daysInactiveInt ?>">
                    <?php if ($daysInactiveInt === null): ?>
                        <span class="badge badge-muted">—</span>
                    <?php else:
                        $cls = 'badge-success';
                        if ($_inacCfgDays > 0) {
                            if ($daysInactiveInt >= $_inacCfgDays)              $cls = 'badge-danger';
                            elseif ($daysInactiveInt >= $_inacCfgDays * 0.7)   $cls = 'badge-warning';
                        }
                    ?>
                        <span class="badge <?= $cls ?>" title="Inactivity cut-off: <?= $_inacCfgDays ?> days"><?= $daysInactiveInt ?> day<?= $daysInactiveInt===1?'':'s' ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($aff['created_at'])) ?></td>
                <td>
                    <a href="/admin/affiliates/<?= $aff['aff_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="/admin/affiliates?action=impersonate&user_id=<?= $aff['user_id'] ?>" class="btn btn-sm" style="background:#6366F1;color:#fff" onclick="return confirm('Login as this affiliate?')">Login As</a>
                    <?php if ($aff['status'] === 'pending'): ?>
                    <form method="POST" action="/admin/affiliates/<?= $aff['aff_id'] ?>" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="status" value="active">
                        <button class="btn btn-success btn-sm" type="submit">Approve</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($aff['status'] === 'suspended'): ?>
                    <form method="POST" action="/admin/affiliates/reactivate" style="display:inline" onsubmit="return confirm('Reactivate this affiliate and reset the inactivity timer?');">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="affiliate_id" value="<?= (int)$aff['aff_id'] ?>">
                        <button class="btn btn-sm" type="submit" style="background:#10B981;color:#fff"<?= $autoDeactivated ? ' title="Auto-deactivated for inactivity"' : '' ?>>Reactivate</button>
                    </form>
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
    $('#tbl-affiliates').DataTable({
        destroy: true,
        pageLength: 25,
        order: [],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
