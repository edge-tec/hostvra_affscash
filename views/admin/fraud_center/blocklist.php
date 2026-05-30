<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128274;</div>
        <div>
            <div class="fds-page-title">Blocklist Manager</div>
            <div class="fds-page-sub">IPs, CIDRs, User-Agents, Affiliates &mdash; fraud-specific blocking</div>
        </div>
    </div>
    <button onclick="document.getElementById('fds-add-block-form').style.display=document.getElementById('fds-add-block-form').style.display==='none'?'block':'none'" class="fds-btn fds-btn-primary">+ Add Entry</button>
</div>

<?php if ($message): ?><div class="alert alert-success mb-3"><?= Helpers::e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger mb-3"><?= Helpers::e($error) ?></div><?php endif; ?>

<?php
$fdfFrom = $dr['from']; $fdfTo = $dr['to'];
$fdfHiddenInputs = ['type'=>$filterType,'q'=>$search];
include BASE_PATH . '/views/admin/fraud_center/_date_filter.php';
?>

<!-- Stats -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
    <div class="fds-kpi"><div class="fds-kpi-label">Total Entries</div><div class="fds-kpi-val"><?= number_format($stats['total']) ?></div></div>
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Active Blocks</div><div class="fds-kpi-val"><?= number_format($stats['active']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Blocked IPs</div><div class="fds-kpi-val"><?= number_format($stats['ips']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Total Hits</div><div class="fds-kpi-val"><?= number_format($stats['hits']) ?></div></div>
</div>

<!-- Add form (initially shown if add_ip param present) -->
<div id="fds-add-block-form" style="display:<?= isset($_GET['add_ip']) ? 'block':'none' ?>">
    <div class="fds-card mb-3">
        <div class="fds-card-header"><span class="fds-card-title">Add Blocklist Entry</span></div>
        <div class="fds-card-body">
            <form method="post">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="add">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                    <div class="form-group">
                        <label>Type <span style="color:red">*</span></label>
                        <select name="type" class="form-control">
                            <?php foreach ($validTypes as $t): ?>
                            <option value="<?= $t ?>"><?= str_replace('_',' ', ucfirst($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label>Value <span style="color:red">*</span></label>
                        <input type="text" name="value" class="form-control" required placeholder="IP, CIDR, UA string, affiliate ID..." value="<?= Helpers::e($_GET['add_ip'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Why is this being blocked?" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select name="bl_action" class="form-control">
                            <?php foreach ($validActions as $a): ?>
                            <option value="<?= $a ?>"><?= ucfirst($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Scope</label>
                        <select name="scope" class="form-control">
                            <?php foreach ($validScopes as $s): ?>
                            <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expires At (optional)</label>
                        <input type="datetime-local" name="expires_at" class="form-control">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px">
                    <button type="submit" class="fds-btn fds-btn-primary">Add to Blocklist</button>
                    <button type="button" onclick="document.getElementById('fds-add-block-form').style.display='none'" class="fds-btn fds-btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="fds-card mb-3">
    <div class="fds-card-body" style="padding:12px 16px">
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
            <input type="text" name="q" class="form-control" placeholder="Search value or reason..." value="<?= Helpers::e($search) ?>" style="max-width:260px">
            <select name="type" class="form-control" style="max-width:160px">
                <option value="">All Types</option>
                <?php foreach ($validTypes as $t): ?>
                <option value="<?= $t ?>" <?= $filterType === $t ? 'selected':'' ?>><?= str_replace('_',' ', ucfirst($t)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="fds-btn fds-btn-primary">Filter</button>
            <a href="/admin/fraud-center/blocklist" class="fds-btn fds-btn-outline">Clear</a>
            <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="fds-btn fds-btn-outline" style="margin-left:auto">&#11123; Export CSV</a>
        </form>
    </div>
</div>

<!-- Blocklist table -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Blocklist Entries</span><span class="fds-text-muted fds-text-sm"><?= number_format($total) ?> entries</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Type</th><th>Value</th><th>Reason</th><th>Action</th><th>Scope</th><th>Hits</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $entry): $expired = $entry['expires_at'] && strtotime($entry['expires_at']) < time(); ?>
            <tr style="<?= $expired ? 'opacity:.5':'' ?>">
                <td><span class="fds-badge fds-badge-muted"><?= $entry['type'] ?></span></td>
                <td style="max-width:200px;word-break:break-all;font-family:monospace;font-size:12px"><?= Helpers::e($entry['value']) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= Helpers::e($entry['reason'] ?? '—') ?></td>
                <td><span class="fds-badge fds-badge-<?= $entry['action']==='block'?'critical':($entry['action']==='throttle'?'high':'medium') ?>"><?= $entry['action'] ?></span></td>
                <td class="fds-text-sm"><?= $entry['scope'] ?></td>
                <td><?= number_format($entry['hit_count']) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= $entry['expires_at'] ? date('M j, Y', strtotime($entry['expires_at'])) : '&#8734;' ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                        <input type="hidden" name="active" value="<?= $entry['is_active'] ? 0 : 1 ?>">
                        <button type="submit" class="fds-toggle <?= $entry['is_active'] ? 'fds-toggle-on':'' ?>"></button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline" onsubmit="return confirm('Remove this entry?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                        <button type="submit" class="fds-btn fds-btn-sm fds-btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($entries)): ?><tr><td colspan="9" class="fds-empty">No blocklist entries</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
    <div class="fds-card-body" style="border-top:1px solid var(--border)"><?= fds_pagination($pag, '/admin/fraud-center/blocklist', ['q'=>$search,'type'=>$filterType]) ?></div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
