<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>🖼 Landing Sliders</h1>
        <p>Manage hero slider items displayed on the landing page</p>
    </div>
    <a href="/admin/landing/sliders/create" class="btn btn-primary">+ Add Slider</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="/admin/landing/sliders" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Title or subtitle…"
                       value="<?= Helpers::e($q ?? '') ?>" style="height:38px">
            </div>
            <div style="min-width:150px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Status</label>
                <select name="status_filter" class="form-control" style="height:38px">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= ($statusFilter ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($statusFilter ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary" style="height:38px">Filter</button>
                <a href="/admin/landing/sliders" class="btn btn-secondary" style="height:38px">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($sliders)): ?>
<div class="card">
    <div style="text-align:center;padding:60px 24px;color:#94A3B8">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 14px;display:block;opacity:.3">
            <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
        </svg>
        <h3 style="font-size:16px;color:#94A3B8;margin-bottom:6px">No slider items yet</h3>
        <p style="font-size:13px">Click "Add Slider" to create your first landing page slide.</p>
        <a href="/admin/landing/sliders/create" class="btn btn-primary" style="margin-top:16px">+ Add First Slider</a>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:60px">Order</th>
                    <th style="width:80px">Image</th>
                    <th>Title / Subtitle</th>
                    <th>Link</th>
                    <th>Badges</th>
                    <th style="width:100px">Status</th>
                    <th style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sliders as $s): ?>
            <tr>
                <td style="color:#94A3B8;font-size:13px;text-align:center"><?= (int)$s['sort_order'] ?></td>
                <td>
                    <?php if ($s['image']): ?>
                    <img src="<?= Helpers::e($s['image']) ?>" alt=""
                         style="width:64px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0">
                    <?php else: ?>
                    <div style="width:64px;height:40px;background:linear-gradient(135deg,#4F46E5,#7C3AED);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:18px">🖼</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:600;font-size:14px"><?= Helpers::e($s['title']) ?></div>
                    <?php if ($s['subtitle']): ?>
                    <div style="font-size:12px;color:#64748B;margin-top:2px"><?= Helpers::e(substr($s['subtitle'],0,60)) ?><?= strlen($s['subtitle'])>60?'…':'' ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748B;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= Helpers::e($s['link']) ?>
                </td>
                <td>
                    <?php if ($s['badge1']): ?>
                    <span style="background:#EF4444;color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;margin-right:4px"><?= Helpers::e($s['badge1']) ?></span>
                    <?php endif; ?>
                    <?php if ($s['badge2']): ?>
                    <span style="background:#7C3AED;color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700"><?= Helpers::e($s['badge2']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/admin/landing/sliders/<?= $s['id'] ?>/toggle" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <button type="submit" class="btn btn-sm"
                                style="background:<?= $s['status']==='active'?'#D1FAE5':'#F1F5F9' ?>;color:<?= $s['status']==='active'?'#065F46':'#64748B' ?>;border:1px solid <?= $s['status']==='active'?'#A7F3D0':'#E2E8F0' ?>;padding:4px 10px;font-size:12px;font-weight:600">
                            <?= $s['status'] === 'active' ? '● Active' : '○ Inactive' ?>
                        </button>
                    </form>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="/admin/landing/sliders/<?= $s['id'] ?>/edit" class="btn btn-secondary btn-sm">✏ Edit</a>
                        <form method="POST" action="/admin/landing/sliders/<?= $s['id'] ?>/delete" style="display:inline"
                              onsubmit="return confirm('Delete this slider?')">
                            <?= Helpers::csrf() ?>
                            <button type="submit" class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:10px 20px;font-size:12px;color:#94A3B8;border-top:1px solid #F1F5F9">
        <?= count($sliders) ?> slider<?= count($sliders)!==1?'s':'' ?> found
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
