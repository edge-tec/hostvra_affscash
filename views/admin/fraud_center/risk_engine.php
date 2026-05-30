<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#9881;</div>
        <div>
            <div class="fds-page-title">Risk Engine</div>
            <div class="fds-page-sub">Configure detection rules &mdash; thresholds, actions, conditions</div>
        </div>
    </div>
    <a href="?new=1" class="fds-btn fds-btn-primary">+ New Rule</a>
</div>

<?php if ($message): ?><div class="alert alert-success mb-3"><?= Helpers::e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger mb-3"><?= Helpers::e($error) ?></div><?php endif; ?>

<?php $fdfFrom = $dr['from']; $fdfTo = $dr['to']; include BASE_PATH . '/views/admin/fraud_center/_date_filter.php'; ?>

<?php if ($editRule || isset($_GET['new'])): ?>
<!-- Edit / Create form -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title"><?= $editRule ? 'Edit Rule: '.Helpers::e($editRule['name']) : 'New Rule' ?></span></div>
    <div class="fds-card-body">
        <form method="post">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_rule">
            <input type="hidden" name="rule_id" value="<?= (int)($editRule['id'] ?? 0) ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control" maxlength="100" required value="<?= Helpers::e($editRule['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Rule Type <span style="color:red">*</span></label>
                    <select name="rule_type" class="form-control" required>
                        <?php foreach ($validTypes as $t): ?>
                        <option value="<?= $t ?>" <?= ($editRule['rule_type']??'') === $t ? 'selected':'' ?>><?= str_replace('_',' ', ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" maxlength="500" value="<?= Helpers::e($editRule['description'] ?? '') ?>">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Condition JSON <span style="color:red">*</span></label>
                    <textarea name="condition_json" class="form-control" rows="3" style="font-family:monospace"><?= Helpers::e($editRule['condition_json'] ?? '{}') ?></textarea>
                    <div class="form-hint">e.g. {"clicks_per_minute":20,"window_seconds":60}</div>
                </div>
                <div class="form-group">
                    <label>Action</label>
                    <select name="rule_action" class="form-control">
                        <?php foreach ($validActions as $a): ?>
                        <option value="<?= $a ?>" <?= ($editRule['action']??'flag') === $a ? 'selected':'' ?>><?= ucfirst($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($editRule['is_active']??1) ? 'selected':'' ?>>Active</option>
                        <option value="0" <?= !($editRule['is_active']??1) ? 'selected':'' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px">
                <button type="submit" class="fds-btn fds-btn-primary">Save Rule</button>
                <a href="/admin/fraud-center/risk-engine" class="fds-btn fds-btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Rules list -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Detection Rules</span><span class="fds-text-muted fds-text-sm"><?= count($rules) ?> rules</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Name</th><th>Type</th><th>Action</th><th>Triggers</th><th>Last Triggered</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rules as $rule): ?>
            <tr>
                <td><strong><?= Helpers::e($rule['name']) ?></strong><br><span class="fds-text-sm fds-text-muted"><?= Helpers::e($rule['description'] ?? '') ?></span></td>
                <td><span class="fds-badge fds-badge-muted"><?= str_replace('_',' ', $rule['rule_type']) ?></span></td>
                <td>
                    <?php $actMap = ['flag'=>'medium','block'=>'critical','alert'=>'high','auto_case'=>'high']; ?>
                    <span class="fds-badge fds-badge-<?= $actMap[$rule['action']] ?? 'muted' ?>"><?= $rule['action'] ?></span>
                </td>
                <td><?= number_format($rule['trigger_count']) ?></td>
                <td class="fds-text-sm fds-text-muted"><?= $rule['last_triggered'] ? date('M j H:i', strtotime($rule['last_triggered'])) : '—' ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="toggle_rule">
                        <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                        <input type="hidden" name="active" value="<?= $rule['is_active'] ? 0 : 1 ?>">
                        <button type="submit" class="fds-toggle <?= $rule['is_active'] ? 'fds-toggle-on':'' ?>" title="<?= $rule['is_active'] ? 'Disable':'Enable' ?>"></button>
                    </form>
                </td>
                <td style="white-space:nowrap">
                    <a href="?edit=<?= $rule['id'] ?>" class="fds-btn fds-btn-sm fds-btn-outline">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this rule?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="delete_rule">
                        <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                        <button type="submit" class="fds-btn fds-btn-sm fds-btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rules)): ?><tr><td colspan="7" class="fds-empty">No rules configured</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
