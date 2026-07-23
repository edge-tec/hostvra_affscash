<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>📰 News Management</h1>
        <p>Publish news, offer updates, and blog posts for affiliates</p>
    </div>
    <a href="/admin/news/create" class="btn btn-primary">+ Create News</a>
</div>

<?php if (empty($newsList)): ?>
<div class="card">
    <div style="text-align:center;padding:60px 24px;color:#94A3B8">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 14px;display:block;opacity:.3">
            <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/>
            <path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/>
        </svg>
        <h3 style="font-size:16px;color:#94A3B8;margin-bottom:6px">No news yet</h3>
        <p style="font-size:13px">Click "Create News" to publish your first article.</p>
        <a href="/admin/news/create" class="btn btn-primary" style="margin-top:16px">+ Create First News</a>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%, 300px),1fr));gap:20px">
<?php foreach ($newsList as $n): ?>
<div class="card" style="overflow:hidden;display:flex;flex-direction:column">
    <?php if ($n['image']): ?>
    <div style="height:160px;overflow:hidden;position:relative">
        <img src="<?= Helpers::e($n['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
        <?php if ($n['is_hot']): ?>
        <span style="position:absolute;top:10px;left:10px;background:#EF4444;color:#fff;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700">🔥 HOT</span>
        <?php endif; ?>
        <span style="position:absolute;top:10px;right:10px;background:<?= $n['status']==='published'?'rgba(16,185,129,.9)':'rgba(100,116,139,.85)' ?>;color:#fff;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700">
            <?= $n['status'] === 'published' ? '● Published' : '○ Draft' ?>
        </span>
    </div>
    <?php else: ?>
    <div style="height:80px;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;position:relative">
        <span style="font-size:28px">📰</span>
        <?php if ($n['is_hot']): ?>
        <span style="position:absolute;top:10px;left:10px;background:#EF4444;color:#fff;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700">🔥 HOT</span>
        <?php endif; ?>
        <span style="position:absolute;top:10px;right:10px;background:<?= $n['status']==='published'?'rgba(16,185,129,.9)':'rgba(100,116,139,.85)' ?>;color:#fff;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700">
            <?= $n['status'] === 'published' ? '● Published' : '○ Draft' ?>
        </span>
    </div>
    <?php endif; ?>

    <div style="padding:16px 18px;flex:1;display:flex;flex-direction:column">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:6px;line-height:1.4"><?= Helpers::e($n['title']) ?></h3>
        <?php if ($n['summary']): ?>
        <p style="font-size:12px;color:#64748B;margin-bottom:10px;flex:1"><?= Helpers::e(substr($n['summary'],0,100)) ?><?= strlen($n['summary'])>100?'…':'' ?></p>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-top:auto;padding-top:10px;border-top:1px solid #F1F5F9">
            <div style="font-size:11px;color:#94A3B8">
                <?= $n['published_at'] ? date('M j, Y', strtotime($n['published_at'])) : 'Draft' ?>
                · <?= (int)$n['read_count'] ?> reads
                <?php if ($n['email_sent']): ?> · ✉ Sent<?php endif; ?>
            </div>
            <div style="display:flex;gap:6px">
                <a href="/admin/news/<?= $n['id'] ?>/edit" class="btn btn-secondary btn-sm">✏ Edit</a>
                <form method="POST" action="/admin/news/<?= $n['id'] ?>/delete" style="display:inline"
                      onsubmit="return confirm('Delete this news item?')">
                    <?= Helpers::csrf() ?>
                    <button type="submit" class="btn btn-danger btn-sm">✕</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
