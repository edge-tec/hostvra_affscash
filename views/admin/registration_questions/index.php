<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Registration Questions</h1><p>Customize questions shown during affiliate and advertiser registration</p></div>
</div>

<div class="grid-2 mb-3">
    <!-- Add Question Form -->
    <div class="card">
        <div class="card-header"><span class="card-title">Add New Question</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Question Text *</label>
                    <input type="text" name="question_text" class="form-control" required placeholder="What is your traffic source?">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Show To</label>
                        <select name="target_role" class="form-control">
                            <option value="affiliate">Affiliates only</option>
                            <option value="advertiser">Advertisers only</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field Type</label>
                        <select name="field_type" class="form-control" id="fieldTypeSelect" onchange="toggleOptions(this.value)">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Dropdown</option>
                            <option value="radio">Radio Buttons</option>
                            <option value="checkbox">Checkboxes</option>
                            <option value="url">URL</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="optionsGroup" style="display:none">
                    <label>Options (one per line)</label>
                    <textarea name="options" class="form-control" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:1px">
                        <div class="form-check">
                            <input type="checkbox" name="is_required" id="isRequired" checked>
                            <label for="isRequired">Required</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Question</button>
            </form>
        </div>
    </div>

    <!-- Preview -->
    <div class="card">
        <div class="card-header"><span class="card-title">Questions Preview</span></div>
        <div class="card-body">
            <div class="alert alert-info text-sm">These questions appear on the registration forms for affiliates and advertisers.</div>
            <div style="font-size:13px;color:var(--text-muted)">
                <strong>Affiliate questions:</strong> <?= Database::count('registration_questions','target_role IN (\'affiliate\',\'both\') AND is_active=1') ?><br>
                <strong>Advertiser questions:</strong> <?= Database::count('registration_questions','target_role IN (\'advertiser\',\'both\') AND is_active=1') ?>
            </div>
        </div>
    </div>
</div>

<!-- Questions List -->
<div class="card">
    <div class="card-header"><span class="card-title">All Questions</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Question</th><th>Shown To</th><th>Type</th><th>Required</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($questions)): ?>
            <tr><td colspan="7"><div class="empty-state"><div class="icon">&#10067;</div><h3>No questions yet</h3></div></td></tr>
            <?php else: ?>
            <?php foreach($questions as $i => $q): ?>
            <tr>
                <td class="text-muted"><?= $q['sort_order'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($q['question_text']) ?></div>
                    <?php if ($q['options']): ?>
                    <?php $opts = json_decode($q['options'], true); ?>
                    <div class="text-sm text-muted"><?= Helpers::e(implode(', ', array_slice($opts,0,3))) ?><?= count($opts)>3 ? '...' : '' ?></div>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-info"><?= $q['target_role'] ?></span></td>
                <td><?= $q['field_type'] ?></td>
                <td><?= $q['is_required'] ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-muted">No</span>' ?></td>
                <td><span class="badge badge-<?= $q['is_active']?'success':'muted' ?>"><?= $q['is_active']?'Active':'Inactive' ?></span></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button class="btn btn-secondary btn-sm"><?= $q['is_active']?'Disable':'Enable' ?></button>
                    </form>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button class="btn btn-danger btn-sm" data-confirm="Delete this question?">Del</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleOptions(type) {
    const show = ['select','radio','checkbox'].includes(type);
    document.getElementById('optionsGroup').style.display = show ? 'block' : 'none';
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
