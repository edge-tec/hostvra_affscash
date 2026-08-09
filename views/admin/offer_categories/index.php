<?php
$pageTitle = 'Offer Categories';
require BASE_PATH . '/views/layouts/admin.php';
$editCat = $category ?? null;
$isCreate = ($action ?? '') === 'create';
$showModal = $isCreate || $editCat;
?>

<div class="page-header">
    <div><h1>Offer Categories</h1><p>Manage offer categories for your taxonomy</p></div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary" onclick="runSeeder()" id="seederBtn">🌱 Seed Defaults</button>
        <a href="/admin/offer-categories?action=create" class="btn btn-primary">+ Add Category</a>
    </div>
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
        <form method="GET" action="/admin/offer-categories" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <input type="text" name="q" class="form-control" placeholder="Search categories..." value="<?= Helpers::e($qFilter ?? '') ?>" style="max-width:250px">
            <select name="status_filter" class="form-control" style="max-width:150px">
                <option value="">All Status</option>
                <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
            <?php if (!empty($qFilter) || !empty($statusFilter)): ?>
            <a href="/admin/offer-categories" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Categories (<?= count($categories ?? []) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Offer Types</th>
                    <th>Offers</th>
                    <th>Status</th>
                    <th>Sort</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr><td colspan="10" style="text-align:center;padding:40px;color:#94A3B8">No categories found. Click "Seed Defaults" to add initial categories.</td></tr>
                <?php else: ?>
                <?php foreach($categories as $cat): ?>
                <tr id="cat-row-<?= $cat['id'] ?>">
                    <td><?= $cat['id'] ?></td>
                    <td><strong><?= Helpers::e($cat['name']) ?></strong></td>
                    <td><code style="font-size:12px;color:#64748B"><?= Helpers::e($cat['slug']) ?></code></td>
                    <td><span class="badge" style="background:#E0E7FF;color:#3730A3"><?= (int)$cat['type_count'] ?></span></td>
                    <td><span class="badge" style="background:#DBEAFE;color:#1E40AF"><?= (int)$cat['offer_count'] ?></span></td>
                    <td>
                        <button class="badge toggle-status-btn" data-id="<?= $cat['id'] ?>"
                                style="background:<?= $cat['status']==='active' ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $cat['status']==='active' ? '#166534' : '#991B1B' ?>;cursor:pointer;border:none;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600">
                            <?= ucfirst($cat['status']) ?>
                        </button>
                    </td>
                    <td><?= (int)$cat['sort_order'] ?></td>
                    <td style="font-size:12px;color:#64748B"><?= date('M j, Y', strtotime($cat['created_at'])) ?></td>
                    <td style="font-size:12px;color:#64748B"><?= date('M j, Y', strtotime($cat['updated_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="/admin/offer-categories/<?= $cat['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="/admin/offer-categories/<?= $cat['id'] ?>?action=delete"
                               class="btn btn-sm" style="background:#FEE2E2;color:#DC2626"
                               onclick="return confirm('<?= (int)$cat['offer_count'] > 0 ? 'This category has '.$cat['offer_count'].' offers. It will be deactivated instead of deleted.' : 'Delete this category?' ?>')">
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
<div class="modal-overlay" id="catModal" style="display:flex">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <h3><?= $editCat ? 'Edit Category' : 'Add Category' ?></h3>
            <a href="/admin/offer-categories" class="modal-close">&times;</a>
        </div>
        <form method="POST" action="<?= $editCat ? '/admin/offer-categories/'.$editCat['id'] : '/admin/offer-categories/create' ?>">
            <?= Helpers::csrf() ?>
            <?php if ($editCat): ?><input type="hidden" name="action" value="update"><?php endif; ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= Helpers::e($editCat['name'] ?? $_POST['name'] ?? '') ?>"
                           placeholder="e.g. Finance, Health & Wellness">
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control"
                           value="<?= Helpers::e($editCat['slug'] ?? $_POST['slug'] ?? '') ?>"
                           placeholder="Auto-generated from name">
                    <div class="form-hint">Leave blank to auto-generate from name.</div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Optional description"><?= Helpers::e($editCat['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= ($editCat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editCat['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= (int)($editCat['sort_order'] ?? $_POST['sort_order'] ?? 0) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/admin/offer-categories" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><?= $editCat ? 'Update' : 'Create' ?> Category</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle status via AJAX
document.querySelectorAll('.toggle-status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const fd = new FormData();
        fd.append('ajax_action', 'toggle_status');
        fd.append('id', id);
        fd.append('_token', '<?= $_SESSION['_csrf'] ?? '' ?>');
        fetch('/admin/offer-categories', { method: 'POST', body: fd })
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

// Run seeder
function runSeeder() {
    if (!confirm('Seed default categories & types? Existing entries will not be duplicated.')) return;
    const btn = document.getElementById('seederBtn');
    btn.disabled = true; btn.textContent = 'Seeding...';
    const fd = new FormData();
    fd.append('ajax_action', 'run_seeder');
    fd.append('_token', '<?= $_SESSION['_csrf'] ?? '' ?>');
    fetch('/admin/offer-categories', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(`Seeded! ${d.result.categories} categories, ${d.result.types} types created. ${d.result.skipped} skipped.`);
            location.reload();
        } else {
            alert(d.error || 'Failed');
            btn.disabled = false; btn.textContent = '🌱 Seed Defaults';
        }
    });
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
