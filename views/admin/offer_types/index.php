<?php
$pageTitle = 'Offer Types';
require BASE_PATH . '/views/layouts/admin.php';
$editType = $offerType ?? null;
$isCreate = ($action ?? '') === 'create';
$showModal = $isCreate || $editType;
?>

<div class="page-header">
    <div><h1>Offer Types</h1><p>Manage offer types within categories</p></div>
    <a href="/admin/offer-types?action=create" class="btn btn-primary">+ Add Offer Type</a>
</div>

<?php if ($flash = Helpers::getFlash()): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= Helpers::e($flash['message']) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-2">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/admin/offer-types" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <input type="text" name="q" class="form-control" placeholder="Search types..." value="<?= Helpers::e($qFilter ?? '') ?>" style="max-width:220px">
            <select name="category_id" class="form-control" style="max-width:200px">
                <option value="">All Categories</option>
                <?php foreach($allCategories ?? [] as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($catIdFilter ?? 0) == $c['id'] ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status_filter" class="form-control" style="max-width:140px">
                <option value="">All Status</option>
                <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
            <?php if (!empty($qFilter) || !empty($statusFilter) || !empty($catIdFilter)): ?>
            <a href="/admin/offer-types" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Offer Types (<?= count($offerTypes ?? []) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Slug</th>
                    <th>Offers</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offerTypes)): ?>
                <tr><td colspan="10" style="text-align:center;padding:40px;color:#94A3B8">No offer types found. Create categories first, then add types.</td></tr>
                <?php else: ?>
                <?php foreach($offerTypes as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><strong><?= Helpers::e($t['name']) ?></strong></td>
                    <td>
                        <span class="badge" style="background:#F0F9FF;color:#0369A1;font-size:11px">
                            <?= Helpers::e($t['category_name'] ?? 'Unknown') ?>
                        </span>
                    </td>
                    <td><code style="font-size:12px;color:#64748B"><?= Helpers::e($t['slug']) ?></code></td>
                    <td><span class="badge" style="background:#DBEAFE;color:#1E40AF"><?= (int)$t['offer_count'] ?></span></td>
                    <td>
                        <button class="badge toggle-type-btn" data-id="<?= $t['id'] ?>"
                                style="background:<?= $t['status']==='active' ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $t['status']==='active' ? '#166534' : '#991B1B' ?>;cursor:pointer;border:none;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600">
                            <?= ucfirst($t['status']) ?>
                        </button>
                    </td>
                    <td><?= (int)$t['sort_order'] ?></td>
                    <td style="font-size:12px;color:#64748B"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                    <td style="font-size:12px;color:#64748B"><?= date('M j, Y', strtotime($t['updated_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="/admin/offer-types/<?= $t['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="/admin/offer-types/<?= $t['id'] ?>?action=delete"
                               class="btn btn-sm" style="background:#FEE2E2;color:#DC2626"
                               onclick="return confirm('<?= (int)$t['offer_count'] > 0 ? 'This type has '.$t['offer_count'].' offers. It will be deactivated instead of deleted.' : 'Delete this offer type?' ?>')">
                                Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<?php if ($showModal): ?>
<div class="modal-overlay" id="typeModal" style="display:flex">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <h3><?= $editType ? 'Edit Offer Type' : 'Add Offer Type' ?></h3>
            <a href="/admin/offer-types" class="modal-close">&times;</a>
        </div>
        <form method="POST" action="<?= $editType ? '/admin/offer-types/'.$editType['id'] : '/admin/offer-types/create' ?>">
            <?= Helpers::csrf() ?>
            <?php if ($editType): ?><input type="hidden" name="action" value="update"><?php endif; ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select category...</option>
                        <?php foreach($allCategories ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editType['category_id'] ?? $_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($c['name']) ?>
                            <?= $c['status'] === 'inactive' ? ' (Inactive)' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Offer Type Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= Helpers::e($editType['name'] ?? $_POST['name'] ?? '') ?>"
                           placeholder="e.g. Credit Cards, Loans, App Installs">
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control"
                           value="<?= Helpers::e($editType['slug'] ?? $_POST['slug'] ?? '') ?>"
                           placeholder="Auto-generated from name">
                    <div class="form-hint">Leave blank to auto-generate from name.</div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Optional description"><?= Helpers::e($editType['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= ($editType['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editType['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= (int)($editType['sort_order'] ?? $_POST['sort_order'] ?? 0) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/admin/offer-types" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><?= $editType ? 'Update' : 'Create' ?> Offer Type</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle status via AJAX
document.querySelectorAll('.toggle-type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const fd = new FormData();
        fd.append('ajax_action', 'toggle_status');
        fd.append('id', id);
        fd.append('_token', '<?= $_SESSION['_csrf'] ?? '' ?>');
        fetch('/admin/offer-types', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                this.textContent = d.new_status.charAt(0).toUpperCase() + d.new_status.slice(1);
                this.style.background = d.new_status === 'active' ? '#DCFCE7' : '#FEE2E2';
                this.style.color = d.new_status === 'active' ? '#166534' : '#991B1B';
            } else {
                alert(d.error || 'Failed');
            }
        });
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
