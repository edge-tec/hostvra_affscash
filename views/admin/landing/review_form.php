<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1><?= $reviewId ? '✏ Edit Review' : '+ Add Review' ?></h1>
        <p><?= $reviewId ? 'Update this testimonial' : 'Add a new testimonial to the landing page' ?></p>
    </div>
    <a href="/admin/landing/reviews" class="btn btn-secondary">← Back to Reviews</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.admin-form-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
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

<form method="POST" enctype="multipart/form-data" id="review-form">
    <?= Helpers::csrf() ?>

    <div class="admin-form-grid">

        <!-- Main content -->
        <div>
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Reviewer Details</span></div>
                <div class="card-body">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label style="font-weight:700">Name <span style="color:#EF4444">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= Helpers::e($review['name'] ?? '') ?>"
                                   placeholder="e.g. John Smith" required>
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700">Role / Title</label>
                            <input type="text" name="role_title" class="form-control"
                                   value="<?= Helpers::e($review['role_title'] ?? '') ?>"
                                   placeholder="e.g. Affiliate Manager">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label style="font-weight:700">Country</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= Helpers::e($review['country'] ?? '') ?>"
                                   placeholder="e.g. United States">
                        </div>
                        <div class="form-group">
                            <label style="font-weight:700">Rating <span style="color:#EF4444">*</span></label>
                            <select name="rating" class="form-control" id="rating-select" onchange="updateStars()">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= (int)($review['rating'] ?? 5) === $i ? 'selected' : '' ?>>
                                    <?= $i ?> Star<?= $i!==1?'s':'' ?> — <?= str_repeat('★', $i) ?><?= str_repeat('☆', 5-$i) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div id="star-preview" style="font-size:22px;color:#F59E0B;margin-bottom:12px;letter-spacing:2px">
                        <?= str_repeat('★', (int)($review['rating'] ?? 5)) ?><?= str_repeat('☆', 5-(int)($review['rating'] ?? 5)) ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="card-title">Review Text</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label style="font-weight:700">Review <span style="color:#EF4444">*</span></label>
                        <textarea name="review_text" class="form-control" rows="5" required
                                  placeholder="Write the testimonial text here…"
                                  style="resize:vertical"><?= Helpers::e($review['review_text'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Settings card -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Settings</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-weight:700">Status</label>
                        <select name="status" class="form-control">
                            <option value="active"   <?= ($review['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>● Active — shown on landing page</option>
                            <option value="inactive" <?= ($review['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>○ Inactive — hidden</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= (int)($review['sort_order'] ?? 0) ?>">
                        <div style="font-size:11px;color:#94A3B8;margin-top:3px">Lower numbers appear first</div>
                    </div>

                    <!-- Featured flag -->
                    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;color:#92400E">
                            <input type="checkbox" name="is_featured" value="1"
                                   <?= ($review['is_featured'] ?? 0) ? 'checked' : '' ?>
                                   style="width:18px;height:18px;accent-color:#F59E0B">
                            <div>
                                <div style="font-size:14px">⭐ Featured Review</div>
                                <div style="font-size:11px;font-weight:400;color:#B45309;margin-top:2px">Highlighted in reviews section</div>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        <?= $reviewId ? '💾 Save Changes' : '🚀 Add Review' ?>
                    </button>
                    <?php if ($reviewId): ?>
                    <a href="/admin/landing/reviews" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:8px">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Avatar -->
            <div class="card">
                <div class="card-header"><span class="card-title">Avatar Photo</span></div>
                <div class="card-body" style="text-align:center">
                    <?php if (!empty($review['avatar'])): ?>
                    <div style="margin-bottom:12px">
                        <img src="<?= Helpers::e($review['avatar']) ?>" alt=""
                             style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:3px solid #E2E8F0">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="avatar" class="form-control"
                           accept="image/jpeg,image/png,image/gif,image/webp" id="avatar-input">
                    <div id="avatar-preview" style="display:none;margin-top:12px;justify-content:center">
                        <img id="avatar-preview-img" src="" alt=""
                             style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:3px solid #E2E8F0">
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:6px">Square image recommended, min 200×200px</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function updateStars() {
    var val = parseInt(document.getElementById('rating-select').value) || 5;
    var prev = document.getElementById('star-preview');
    if (prev) prev.textContent = '★'.repeat(val) + '☆'.repeat(5-val);
}

document.getElementById('avatar-input').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatar-preview-img').src = e.target.result;
        document.getElementById('avatar-preview').style.display = 'flex';
    };
    reader.readAsDataURL(file);
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
