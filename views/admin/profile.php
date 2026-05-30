<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>My Profile</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:2px">Manage your admin account details</p>
    </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E5E7EB">
    <a href="?tab=profile" style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;<?= $activeTab==='profile' ? 'color:var(--primary);border-bottom-color:var(--primary)' : 'color:var(--text-muted)' ?>">
        Profile Info
    </a>
    <a href="?tab=security" style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;<?= $activeTab==='security' ? 'color:var(--primary);border-bottom-color:var(--primary)' : 'color:var(--text-muted)' ?>">
        Change Password
    </a>
    <a href="/admin/2fa" style="padding:10px 20px;font-size:13px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:var(--text-muted)">
        &#128274; Google Authenticator
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:16px">
    <?php foreach($errors as $e): ?><div>&#9679; <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($activeTab === 'profile'): ?>
<!-- ── Profile Info Tab ──────────────────────────────── -->
<div class="card" style="max-width:700px">
    <div class="card-header"><h3 class="card-title">Profile Information</h3></div>
    <form method="POST" enctype="multipart/form-data" style="padding:24px">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="tab" value="profile">

        <!-- Avatar row -->
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #F1F5F9">
            <?php if (!empty($user['profile_pic'])): ?>
            <img src="<?= Helpers::e($user['profile_pic']) ?>" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #E5E7EB">
            <?php else: ?>
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;flex-shrink:0">
                <?= strtoupper(substr($user['first_name']??'A',0,1)) ?>
            </div>
            <?php endif; ?>
            <div>
                <label class="form-label" style="margin-bottom:6px">Profile Picture</label>
                <input type="file" name="profile_pic" accept="image/*" class="form-control" style="font-size:13px">
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">PNG, JPG, GIF or WebP. Max 2 MB.</div>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">First Name <span style="color:#EF4444">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= Helpers::e($user['first_name']??'') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Last Name <span style="color:#EF4444">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= Helpers::e($user['last_name']??'') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email Address <span style="color:#EF4444">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= Helpers::e($user['email']??'') ?>" required>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Changing your email will update your login credentials immediately.</div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Company / Organization</label>
                <input type="text" name="company" class="form-control" value="<?= Helpers::e($user['company']??'') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= Helpers::e($user['phone']??'') ?>">
            </div>
        </div>

        <!-- Read-only info -->
        <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:8px;padding:14px 16px;margin-bottom:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600;margin-bottom:3px">Role</div>
                    <div style="font-size:13px;font-weight:600;color:#111827"><?= ucfirst(Helpers::e($user['role']??'admin')) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600;margin-bottom:3px">Member Since</div>
                    <div style="font-size:13px;font-weight:600;color:#111827"><?= date('M j, Y', strtotime($user['created_at']??'now')) ?></div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php elseif ($activeTab === 'security'): ?>
<!-- ── Change Password Tab ───────────────────────────── -->
<div class="card" style="max-width:500px">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <form method="POST" style="padding:24px">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="tab" value="security">

        <div class="form-group">
            <label class="form-label">Current Password <span style="color:#EF4444">*</span></label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label class="form-label">New Password <span style="color:#EF4444">*</span></label>
            <input type="password" name="new_password" id="np" class="form-control" required autocomplete="new-password" minlength="8" oninput="checkStrength(this.value)">
            <div id="pw-strength" style="margin-top:6px;font-size:12px;color:var(--text-muted)"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Confirm New Password <span style="color:#EF4444">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" minlength="8">
        </div>

        <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:12px;color:#92400E">
            &#9888; Use at least 8 characters including letters and numbers for a strong password.
        </div>

        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</div>
<script>
function checkStrength(v) {
    var el = document.getElementById('pw-strength');
    if (!v) { el.textContent = ''; return; }
    var score = 0;
    if (v.length >= 8) score++;
    if (v.length >= 12) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    var labels = ['','Weak','Fair','Good','Strong','Very Strong'];
    var colors = ['','#EF4444','#F59E0B','#3B82F6','#10B981','#059669'];
    el.textContent = 'Strength: ' + (labels[score]||'');
    el.style.color = colors[score]||'#94A3B8';
}
</script>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
