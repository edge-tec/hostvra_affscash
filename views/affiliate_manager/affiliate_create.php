<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
        <h1>Create Affiliate</h1>
        <p style="color:var(--text-muted);font-size:13px">New affiliate will be automatically assigned to your account</p>
    </div>
    <a href="/affiliate_manager/affiliates" class="btn btn-secondary btn-sm">← Back to Affiliates</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <ul style="margin:0;padding-left:18px"><?php foreach($errors as $e): ?><li><?= Helpers::e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px">
    <div class="card-body" style="padding:28px">
        <form method="POST">
            <?= Helpers::csrf() ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">First Name <span style="color:#EF4444">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= Helpers::e($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name <span style="color:#EF4444">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= Helpers::e($_POST['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Email Address <span style="color:#EF4444">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= Helpers::e($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Password <span style="color:#EF4444">*</span></label>
                <input type="password" name="password" class="form-control" minlength="8" required placeholder="Minimum 8 characters">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($_POST['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" maxlength="2" placeholder="e.g. US" value="<?= Helpers::e($_POST['country'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </div>
            </div>

            <div style="background:#F3EEFF;border:1px solid #DDD6FE;border-radius:10px;padding:12px 16px;margin-bottom:24px;display:flex;align-items:center;gap:10px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span style="font-size:13px;color:#5B21B6">This affiliate will be <strong>automatically assigned to you</strong> as their manager.</span>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Create Affiliate</button>
                <a href="/affiliate_manager/affiliates" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
