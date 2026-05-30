<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
        <h1>Edit Affiliate</h1>
        <p style="color:var(--text-muted);font-size:13px"><?= Helpers::e($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?> &bull; <code><?= Helpers::e($affiliate['affiliate_code']) ?></code></p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/affiliate_manager/affiliates?action=view&id=<?= $affiliate['aff_id'] ?>" class="btn btn-secondary btn-sm">← Back to Profile</a>
        <a href="/affiliate_manager/affiliates" class="btn btn-secondary btn-sm">← All Affiliates</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:8px;padding:12px 18px;color:#B91C1C;font-size:13px;margin-bottom:16px">
    <?php foreach ($errors as $e): ?><div>⚠ <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px">
    <div class="card-body" style="padding:28px">
        <form method="POST">
            <?= Helpers::csrf() ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">First Name <span style="color:#EF4444">*</span></label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= Helpers::e($_POST['first_name'] ?? $affiliate['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name <span style="color:#EF4444">*</span></label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= Helpers::e($_POST['last_name'] ?? $affiliate['last_name']) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" value="<?= Helpers::e($affiliate['email']) ?>" disabled
                       style="background:#F8FAFC;color:var(--text-muted);cursor:not-allowed">
                <small style="color:var(--text-muted);font-size:11px">Email cannot be changed here. Contact admin to update.</small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control"
                           value="<?= Helpers::e($_POST['company'] ?? $affiliate['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= Helpers::e($_POST['phone'] ?? $affiliate['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px">
                <label class="form-label">Country (2-letter code)</label>
                <input type="text" name="country" class="form-control" maxlength="2" placeholder="e.g. US"
                       style="text-transform:uppercase;max-width:100px"
                       value="<?= Helpers::e($_POST['country'] ?? $affiliate['country'] ?? '') ?>">
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="/affiliate_manager/affiliates?action=view&id=<?= $affiliate['aff_id'] ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
