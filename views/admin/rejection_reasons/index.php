<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Rejection Reasons</h1>
        <p>Manage the list of reasons admins can pick when rejecting a conversion.</p>
    </div>
</div>

<?php foreach (Helpers::getFlash() as $_f): ?>
<div class="alert alert-<?= $_f['type'] === 'error' ? 'danger' : 'success' ?> mb-3"><?= Helpers::e($_f['message']) ?></div>
<?php endforeach; ?>

<div class="grid-2" style="grid-template-columns:1fr 1.4fr;gap:16px;align-items:flex-start">

    <!-- ── Add form ──────────────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><span class="card-title">+ Add Reason</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Reason label</label>
                    <input type="text" name="label" class="form-control" maxlength="255"
                           placeholder="e.g. Repeat User, Bot Activity..." required>
                    <div class="form-hint">Shown in the reject modal when admins block a conversion.</div>
                </div>
                <button class="btn btn-primary">Add Reason</button>
            </form>
        </div>
    </div>

    <!-- ── List of all reasons ───────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><span class="card-title">All Reasons (<?= count($reasons) ?>)</span></div>
        <div class="card-body" style="padding:0">
            <?php if (empty($reasons)): ?>
            <div style="padding:24px;color:var(--text-muted);text-align:center">No reasons yet — add one above.</div>
            <?php else: ?>
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:32px">#</th>
                        <th>Label</th>
                        <th style="width:80px">Type</th>
                        <th style="width:80px">Status</th>
                        <th style="width:240px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reasons as $r): ?>
                    <tr>
                        <td><?= (int)$r['sort_order'] ?: (int)$r['id'] ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="text" name="label" class="form-control"
                                       value="<?= Helpers::e($r['label']) ?>"
                                       maxlength="255" style="font-size:13px;flex:1;min-width:180px" required>
                                <label style="font-size:11px;display:flex;align-items:center;gap:4px;white-space:nowrap;color:var(--text-muted)">
                                    <input type="checkbox" name="is_active" value="1" <?= (int)$r['is_active'] === 1 ? 'checked' : '' ?>>
                                    Active
                                </label>
                                <button class="btn btn-secondary btn-sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td>
                            <?php if ((int)$r['is_default'] === 1): ?>
                            <span class="badge badge-info" style="font-size:10px">Default</span>
                            <?php else: ?>
                            <span class="badge badge-muted" style="font-size:10px">Custom</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$r['is_active'] === 1): ?>
                            <span class="badge badge-success" style="font-size:10px">Active</span>
                            <?php else: ?>
                            <span class="badge badge-warning" style="font-size:10px">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline-block;margin:0">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-secondary btn-sm" type="submit"
                                        title="<?= (int)$r['is_active'] === 1 ? 'Disable' : 'Enable' ?>">
                                    <?= (int)$r['is_active'] === 1 ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline-block;margin:0"
                                  onsubmit="return confirm('Delete reason &quot;<?= Helpers::e(addslashes($r['label'])) ?>&quot;?<?= (int)$r['is_default'] === 1 ? '\nDefault reasons are deactivated rather than deleted.' : '' ?>');">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body" style="font-size:13px;color:var(--text-muted)">
        <strong>How this list is used:</strong> Active reasons populate the dropdown on every Reject button across the admin panel
        (Reports, Conversions, Click Report, Fraud Score Report, Fraud Center). Admins can also type a custom one-off reason
        on each reject without adding it to this list permanently.
    </div>
</div>
