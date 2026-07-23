<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1><?= $sliderId ? '✏ Edit Slider' : '+ Add Slider' ?></h1>
        <p><?= $sliderId ? 'Update this landing page slider item' : 'Add a new hero slider slide to the landing page' ?></p>
    </div>
    <a href="/admin/landing/sliders" class="btn btn-secondary">← Back to Sliders</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.admin-form-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
}
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 991px) {
    .admin-form-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 576px) {
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<form method="POST" enctype="multipart/form-data" id="slider-form">
    <?= Helpers::csrf() ?>

    <div class="admin-form-grid">

        <!-- Main content -->
        <div>
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Slide Content</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-weight:700">Title <span style="color:#EF4444">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= Helpers::e($slider['title'] ?? '') ?>"
                               placeholder="e.g. High-Converting CPA Offers" required
                               style="font-size:17px;font-weight:600;height:48px">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700">Subtitle</label>
                        <textarea name="subtitle" class="form-control" rows="2"
                                  placeholder="Brief description shown on the slide…"
                                  style="resize:vertical"><?= Helpers::e($slider['subtitle'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700">Link URL</label>
                        <input type="text" name="link" class="form-control"
                               value="<?= Helpers::e($slider['link'] ?? '/tracker/register.php') ?>"
                               placeholder="/tracker/register.php">
                        <div style="font-size:11px;color:#94A3B8;margin-top:3px">Where the slide links to when clicked. Default: /tracker/register.php</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Badges</span></div>
                <div class="card-body">
                    <div class="form-row-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label style="font-weight:700">Badge 1</label>
                            <input type="text" name="badge1" class="form-control"
                                   value="<?= Helpers::e($slider['badge1'] ?? '') ?>"
                                   placeholder="e.g. hot" maxlength="50">
                            <div style="font-size:11px;color:#94A3B8;margin-top:3px">Shown in red. Common: hot, new, top</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label style="font-weight:700">Badge 2</label>
                            <input type="text" name="badge2" class="form-control"
                                   value="<?= Helpers::e($slider['badge2'] ?? '') ?>"
                                   placeholder="e.g. top" maxlength="50">
                            <div style="font-size:11px;color:#94A3B8;margin-top:3px">Shown in violet</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Publish settings -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Settings</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-weight:700">Status</label>
                        <select name="status" class="form-control">
                            <option value="active"   <?= ($slider['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>● Active — shown on landing page</option>
                            <option value="inactive" <?= ($slider['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>○ Inactive — hidden</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= (int)($slider['sort_order'] ?? 0) ?>">
                        <div style="font-size:11px;color:#94A3B8;margin-top:3px">Lower numbers appear first</div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        <?= $sliderId ? '💾 Save Changes' : '🚀 Create Slider' ?>
                    </button>
                    <?php if ($sliderId): ?>
                    <a href="/admin/landing/sliders" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:8px">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Image -->
            <div class="card">
                <div class="card-header"><span class="card-title">Slide Image</span></div>
                <div class="card-body">
                    <?php if (!empty($slider['image'])): ?>
                    <div style="margin-bottom:12px;border-radius:8px;overflow:hidden">
                        <img src="<?= Helpers::e($slider['image']) ?>" alt="" style="width:100%;height:120px;object-fit:cover">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp" id="img-input">
                    <div id="img-preview" style="display:none;margin-top:10px;border-radius:8px;overflow:hidden">
                        <img id="img-preview-img" src="" alt="" style="width:100%;height:120px;object-fit:cover">
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:6px">Recommended: 1280×720px JPEG/PNG</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('img-input').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('img-preview-img').src = e.target.result;
        document.getElementById('img-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
