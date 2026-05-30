<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Reward Pop-up</h1><p>Optional post-login modal shown to affiliates. Editing the text auto-bumps the version so every affiliate sees the new message once.</p></div>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Pop-up Settings</span></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="save_popup">

                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:600">
                            <input type="checkbox" name="enabled" value="1" <?= (int)($cfg['enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                            Show pop-up to affiliates
                        </label>
                        <div class="form-hint">Pop-up only renders for users with role <code>affiliate</code> AND when this flag is on.</div>
                    </div>

                    <div class="form-group"><label>Title *</label><input type="text" name="title" class="form-control" required maxlength="255" value="<?= Helpers::e($cfg['title'] ?? '') ?>"></div>

                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control" rows="6"><?= Helpers::e($cfg['body'] ?? '') ?></textarea>
                        <div class="form-hint">Plain text or basic HTML. Saved versions reset all dismissals so every affiliate sees the new message once.</div>
                    </div>

                    <div class="form-row" style="display:grid;grid-template-columns:1fr 2fr;gap:12px">
                        <div class="form-group"><label>Button label</label><input type="text" name="cta_label" class="form-control" placeholder="e.g. Learn more" value="<?= Helpers::e($cfg['cta_label'] ?? '') ?>"></div>
                        <div class="form-group"><label>Button URL</label><input type="url" name="cta_url" class="form-control" placeholder="https://…" value="<?= Helpers::e($cfg['cta_url'] ?? '') ?>"></div>
                    </div>

                    <div class="form-group">
                        <label>Image</label>
                        <?php if (!empty($cfg['image_path'])): ?>
                        <div style="margin-bottom:8px">
                            <img src="<?= Helpers::e($cfg['image_path']) ?>" alt="" style="max-height:96px;border-radius:6px;border:1px solid #E2E8F0">
                            <label style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-size:12px;color:#DC2626">
                                <input type="checkbox" name="clear_image" value="1"> Remove
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                    </div>

                    <button class="btn btn-primary" type="submit">Save Pop-up</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Preview</span></div>
            <div class="card-body">
                <?php if (empty($cfg['title'])): ?>
                <div class="text-muted text-center" style="padding:40px">Add a title to see the preview.</div>
                <?php else: ?>
                <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border-radius:14px;padding:28px 24px;text-align:center">
                    <?php if (!empty($cfg['image_path'])): ?>
                    <img src="<?= Helpers::e($cfg['image_path']) ?>" alt="" style="max-width:160px;max-height:120px;border-radius:10px;margin-bottom:14px">
                    <?php endif; ?>
                    <h3 style="margin:0 0 10px;font-size:20px;font-weight:800"><?= Helpers::e($cfg['title']) ?></h3>
                    <div style="font-size:13.5px;line-height:1.55;opacity:.92;max-width:380px;margin:0 auto"><?= nl2br(Helpers::e($cfg['body'] ?? '')) ?></div>
                    <?php if (!empty($cfg['cta_label']) && !empty($cfg['cta_url'])): ?>
                    <div style="margin-top:18px">
                        <a href="<?= Helpers::e($cfg['cta_url']) ?>" target="_blank" style="display:inline-block;background:#fff;color:#4F46E5;padding:10px 22px;border-radius:8px;font-weight:700;text-decoration:none"><?= Helpers::e($cfg['cta_label']) ?></a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-muted text-sm" style="margin-top:10px">Version: <strong>v<?= (int)($cfg['version'] ?? 1) ?></strong> · Last updated: <?= !empty($cfg['updated_at']) ? Helpers::e(date('M j, Y H:i', strtotime((string)$cfg['updated_at']))) : '—' ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
