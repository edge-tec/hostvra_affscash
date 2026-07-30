<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>📝 Landing Blog Posts</h1>
        <p>Manage blog posts displayed on the landing page</p>
    </div>
    <a href="/admin/landing/blog/create" class="btn btn-primary">+ Create Post</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="/admin/landing/blog" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Title or excerpt…"
                       value="<?= Helpers::e($q ?? '') ?>" style="height:38px">
            </div>
            <div style="min-width:150px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Status</label>
                <select name="status_filter" class="form-control" style="height:38px">
                    <option value="">All Statuses</option>
                    <option value="published" <?= ($statusFilter ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft"     <?= ($statusFilter ?? '') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <?php if (!empty($categories)): ?>
            <div style="min-width:150px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Category</label>
                <select name="cat" class="form-control" style="height:38px">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= Helpers::e($c['category']) ?>" <?= ($catFilter ?? '') === $c['category'] ? 'selected' : '' ?>><?= Helpers::e($c['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary" style="height:38px">Filter</button>
                <a href="/admin/landing/blog" class="btn btn-secondary" style="height:38px">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($posts)): ?>
<div class="card">
    <div style="text-align:center;padding:60px 24px;color:#94A3B8">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 14px;display:block;opacity:.3">
            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
            <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>
        </svg>
        <h3 style="font-size:16px;color:#94A3B8;margin-bottom:6px">No posts yet</h3>
        <p style="font-size:13px">Click "Create Post" to write your first blog article.</p>
        <a href="/admin/landing/blog/create" class="btn btn-primary" style="margin-top:16px">+ Create First Post</a>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px">
<?php foreach ($posts as $p): ?>
<div class="card" style="overflow:hidden;display:flex;flex-direction:column">
    <?php if ($p['image']): ?>
    <div style="height:180px;overflow:hidden;position:relative;background:linear-gradient(135deg,#0f0826,#1e1b4b);display:flex;align-items:center;justify-content:center;padding:8px">
        <img src="<?= Helpers::e($p['image']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;display:block">
        <?php if ($p['is_featured']): ?>
        <span style="position:absolute;top:10px;left:10px;background:#F59E0B;color:#fff;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700">⭐ Featured</span>
        <?php endif; ?>
        <span style="position:absolute;top:10px;right:10px;background:<?= $p['status']==='published'?'rgba(16,185,129,.9)':'rgba(100,116,139,.85)' ?>;color:#fff;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700">
            <?= $p['status'] === 'published' ? '● Published' : '○ Draft' ?>
        </span>
    </div>
    <?php else: ?>
    <div style="height:70px;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;position:relative">
        <span style="font-size:26px">📝</span>
        <?php if ($p['is_featured']): ?>
        <span style="position:absolute;top:10px;left:10px;background:#F59E0B;color:#fff;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700">⭐ Featured</span>
        <?php endif; ?>
        <span style="position:absolute;top:10px;right:10px;background:<?= $p['status']==='published'?'rgba(16,185,129,.9)':'rgba(100,116,139,.85)' ?>;color:#fff;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700">
            <?= $p['status'] === 'published' ? '● Published' : '○ Draft' ?>
        </span>
    </div>
    <?php endif; ?>

    <div style="padding:16px 18px;flex:1;display:flex;flex-direction:column">
        <?php if ($p['category']): ?>
        <div style="margin-bottom:6px">
            <span style="background:#EFF6FF;color:#2563EB;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600"><?= Helpers::e($p['category']) ?></span>
        </div>
        <?php endif; ?>
        <h3 style="font-size:15px;font-weight:700;margin-bottom:6px;line-height:1.4"><?= Helpers::e($p['title']) ?></h3>
        <?php if ($p['excerpt']): ?>
        <p style="font-size:12px;color:#64748B;margin-bottom:10px;flex:1"><?= Helpers::e(substr($p['excerpt'],0,100)) ?><?= strlen($p['excerpt'])>100?'…':'' ?></p>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-top:auto;padding-top:10px;border-top:1px solid #F1F5F9">
            <div style="font-size:11px;color:#94A3B8">
                <?= $p['published_at'] ? date('M j, Y', strtotime($p['published_at'])) : 'Draft' ?>
                <?php if ($p['author_name']): ?> · <?= Helpers::e($p['author_name']) ?><?php endif; ?>
            </div>
            <div style="display:flex;gap:6px">
                <form method="POST" action="/admin/landing/blog/<?= $p['id'] ?>/toggle" style="display:inline" title="Toggle status">
                    <?= Helpers::csrf() ?>
                    <button type="submit" class="btn btn-secondary btn-sm" title="Toggle <?= $p['status']==='published'?'to Draft':'to Published' ?>">
                        <?= $p['status'] === 'published' ? '↓ Draft' : '↑ Publish' ?>
                    </button>
                </form>
                <a href="/admin/landing/blog/<?= $p['id'] ?>/edit" class="btn btn-secondary btn-sm">✏ Edit</a>
                <form method="POST" action="/admin/landing/blog/<?= $p['id'] ?>/delete" style="display:inline"
                      onsubmit="return confirm('Delete this post?')">
                    <?= Helpers::csrf() ?>
                    <button type="submit" class="btn btn-danger btn-sm">✕</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div style="margin-top:12px;font-size:12px;color:#94A3B8">
    <?= count($posts) ?> post<?= count($posts)!==1?'s':'' ?> found
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
