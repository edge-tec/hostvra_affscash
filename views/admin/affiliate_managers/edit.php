<?php
$pageTitle = 'Edit Affiliate Manager';
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div><h1>Edit Affiliate Manager</h1><p><?= Helpers::e($manager['first_name'].' '.$manager['last_name']) ?></p></div>
    <a href="/admin/affiliate-managers/<?= $manager['mgr_id'] ?>" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="POST" action="/admin/affiliate-managers?action=edit&id=<?= $manager['mgr_id'] ?>">
    <?= Helpers::csrf() ?>
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><span class="card-title">Account Details</span></div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($_POST['first_name'] ?? $manager['first_name']) ?>"></div>
                    <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($_POST['last_name'] ?? $manager['last_name']) ?>"></div>
                </div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required value="<?= Helpers::e($_POST['email'] ?? $manager['email']) ?>"></div>
                <div class="form-group"><label>New Password <span class="text-muted text-sm">(leave blank to keep current)</span></label><input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters"></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($manager['status']==='active')?'selected':'' ?>>Active</option>
                        <option value="suspended" <?= ($manager['status']==='suspended')?'selected':'' ?>>Suspended</option>
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3"><?= Helpers::e($_POST['notes'] ?? $manager['notes'] ?? '') ?></textarea></div>
                <div class="form-group" style="background:#FAFAF9;border:1px solid #E5E7EB;border-radius:8px;padding:14px">
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                        <span style="background:#EEF2FF;color:#4338CA;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.04em">ADMIN ONLY</span>
                        Commission Rate (%) — calculated from Net Profit only
                    </label>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <input type="number" name="commission_rate" class="form-control" min="0" max="100" step="0.01"
                               value="<?= Helpers::e($_POST['commission_rate'] ?? $manager['commission_rate'] ?? '0') ?>"
                               style="max-width:140px">
                        <span style="font-size:12px;color:#6B7280">% of (Advertiser Revenue &minus; Affiliate Payout). <strong>Never shown</strong> to the manager.</span>
                    </div>
                    <div style="margin-top:8px">
                        <a href="/admin/affiliate-managers?action=commission_report&id=<?= (int)$manager['mgr_id'] ?>"
                           style="font-size:12px;color:#4F46E5">&#128200; View Commission Report &rarr;</a>
                    </div>
                </div>
                <hr style="margin:16px 0">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group"><label style="display:flex;align-items:center;gap:5px"><span style="color:#00AFF0">&#128222;</span> Skype</label><input type="text" name="skype" class="form-control" placeholder="live:username" value="<?= Helpers::e($_POST['skype'] ?? $manager['skype'] ?? '') ?>"></div>
                    <div class="form-group"><label style="display:flex;align-items:center;gap:5px"><span style="color:#2AABEE">&#128232;</span> Telegram</label><input type="text" name="telegram" class="form-control" placeholder="@username" value="<?= Helpers::e($_POST['telegram'] ?? $manager['telegram'] ?? '') ?>"></div>
                    <div class="form-group"><label style="display:flex;align-items:center;gap:5px"><span style="color:#5865F2">&#127918;</span> Discord</label><input type="text" name="discord" class="form-control" placeholder="username" value="<?= Helpers::e($_POST['discord'] ?? $manager['discord'] ?? '') ?>"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Permissions</span></div>
            <div class="card-body">
                <p class="text-muted text-sm" style="margin-bottom:16px">Select what this manager is allowed to do.</p>
                <?php
                $permLabels = [
                    'view_affiliates'     => ['View Affiliates','See the list of their assigned affiliates'],
                    'approve_affiliates'  => ['Approve Affiliates','Approve or reject affiliate applications'],
                    'view_conversions'    => ['View Conversions','Access conversion records'],
                    'view_reports'        => ['View Reports','Access performance reports'],
                    'manage_offers'       => ['Manage Offers','Create and edit offers'],
                    'manage_payments'     => ['Manage Payments','Create and update payment records'],
                    'create_invoices'     => ['Create Invoices','Generate and send invoices to affiliates'],
                ];
                $currentPerms = json_decode($manager['permissions'] ?? '[]', true) ?: [];
                $selPerms = $_POST['permissions'] ?? $currentPerms;
                ?>
                <?php foreach ($permLabels as $key => [$label, $desc]): ?>
                <div class="form-check" style="margin-bottom:12px;align-items:flex-start">
                    <input type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_<?= $key ?>" <?= in_array($key,$selPerms)?'checked':'' ?> style="margin-top:2px">
                    <label for="perm_<?= $key ?>" style="cursor:pointer">
                        <div class="fw-bold" style="font-size:13px"><?= $label ?></div>
                        <div class="text-muted text-sm"><?= $desc ?></div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:10px">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/admin/affiliate-managers/<?= $manager['mgr_id'] ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
