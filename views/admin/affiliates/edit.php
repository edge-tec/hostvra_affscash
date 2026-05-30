<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Edit Affiliate</h1>
        <p><?= Helpers::e($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?> &bull; <?= Helpers::e($affiliate['affiliate_code']) ?></p>
    </div>
    <a href="/admin/affiliates/<?= $affiliate['id'] ?>" class="btn btn-secondary">← Back to Profile</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>&#9888; <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="/admin/affiliates/<?= $affiliate['id'] ?>?action=edit">
    <?= Helpers::csrf() ?>

    <div class="grid-2 mb-3">
        <!-- Personal Info -->
        <div class="card">
            <div class="card-header"><span class="card-title">Personal Information</span></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= Helpers::e($affiliate['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= Helpers::e($affiliate['last_name']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= Helpers::e($affiliate['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($affiliate['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($affiliate['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Country (2-letter code)</label>
                    <input type="text" name="country" class="form-control" maxlength="2" style="text-transform:uppercase" value="<?= Helpers::e($affiliate['country'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="card">
            <div class="card-header"><span class="card-title">Account Settings</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach(['active','pending','suspended','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($affiliate['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Affiliate Manager</label>
                    <select name="manager_id" class="form-control">
                        <option value="">— No Manager —</option>
                        <?php foreach ($managers as $mgr): ?>
                        <option value="<?= $mgr['id'] ?>" <?= ($affiliate['manager_id'] ?? '') == $mgr['id'] ? 'selected' : '' ?>><?= Helpers::e($mgr['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <input type="text" name="payment_method" class="form-control" value="<?= Helpers::e($affiliate['payment_method'] ?? '') ?>" placeholder="e.g. PayPal, Bank Transfer">
                </div>
                <div class="form-group">
                    <label>Payment Threshold ($)</label>
                    <input type="number" step="0.01" min="0" name="payment_threshold" class="form-control" value="<?= Helpers::e($affiliate['payment_threshold'] ?? '50') ?>">
                </div>
                <div class="form-group">
                    <label>Payment Terms</label>
                    <select name="payment_terms" class="form-control">
                        <?php foreach(['weekly'=>'Weekly','net15'=>'Net-15','net30'=>'Net-30','monthly'=>'Monthly'] as $val=>$lbl): ?>
                        <option value="<?= $val ?>" <?= ($affiliate['payment_terms'] ?? 'monthly') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check mb-2" style="padding:10px 14px;background:#f8f9fa;border:1px solid #E2E8F0;border-radius:6px">
                    <input type="checkbox" name="allow_email_change" id="allowEmailChange" <?= !empty($affiliate['allow_email_change']) ? 'checked' : '' ?>>
                    <label for="allowEmailChange"><strong>Allow affiliate to change their own email address</strong></label>
                </div>
                <div class="form-group">
                    <label>New Password <span class="text-muted" style="font-weight:400">(leave blank to keep current)</span></label>
                    <input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="8" placeholder="Min 8 characters">
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Details -->
    <div class="card mb-3">
        <div class="card-header"><span class="card-title">&#128172; Contact Details</span></div>
        <div class="card-body">
            <div class="form-row cols-2" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group mb-0">
                    <label style="display:flex;align-items:center;gap:6px">
                        <span style="background:#2AABEE;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">TG</span> Telegram ID
                    </label>
                    <input type="text" name="telegram" class="form-control" placeholder="@username"
                           value="<?= Helpers::e($affiliate['telegram'] ?? '') ?>">
                </div>
                <div class="form-group mb-0">
                    <label style="display:flex;align-items:center;gap:6px">
                        <span style="background:#00AFF0;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">SK</span> Skype ID
                    </label>
                    <input type="text" name="skype" class="form-control" placeholder="live:username"
                           value="<?= Helpers::e($affiliate['skype'] ?? '') ?>">
                </div>
                <div class="form-group mb-0">
                    <label style="display:flex;align-items:center;gap:6px">
                        <span style="background:#5865F2;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">DC</span> Discord ID
                    </label>
                    <input type="text" name="discord" class="form-control" placeholder="username"
                           value="<?= Helpers::e($affiliate['discord'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Details -->
    <div class="card mb-3">
        <div class="card-header"><span class="card-title">Payment Details</span></div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label>Payment Account Details</label>
                <textarea name="payment_details" class="form-control" rows="4" placeholder="PayPal email, bank account info, crypto address, etc."><?= Helpers::e($affiliate['payment_details'] ?? '') ?></textarea>
                <div class="form-hint">Visible only to admins — used to process affiliate payouts.</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/admin/affiliates/<?= $affiliate['id'] ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
