<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.news-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; }
.news-card {
    background:#fff; border-radius:12px; overflow:hidden;
    border:1px solid #E2E8F0; transition:.2s;
    display:flex; flex-direction:column; cursor:pointer;
    text-decoration:none; color:inherit;
}
.news-card:hover { border-color:#C7D2FE; box-shadow:0 8px 24px rgba(79,70,229,.1);  text-decoration:none; }
.news-card.unread { border-color:#C7D2FE; }
.news-card-img { height:180px; overflow:hidden; position:relative; background:linear-gradient(135deg,#0f0826,#1e1b4b); display:flex; align-items:center; justify-content:center; padding:6px; }
.news-card-img img { width:100%; height:100%; object-fit:contain; display:block; }
.news-card-img .no-img { display:flex; align-items:center; justify-content:center; height:100%; font-size:36px; }
.news-hot-badge { position:absolute; top:10px; left:10px; background:#EF4444; color:#fff; border-radius:20px; padding:3px 12px; font-size:11px; font-weight:700; }
.news-time-badge { position:absolute; top:10px; right:10px; background:rgba(0,0,0,.55); color:#fff; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:500; }
.news-unread-dot { position:absolute; top:10px; right:10px; width:10px; height:10px; background:#4F46E5; border-radius:50%; border:2px solid #fff; }
.news-card-body { padding:16px 18px; flex:1; display:flex; flex-direction:column; }
.news-card-title { font-size:15px; font-weight:700; line-height:1.4; margin-bottom:8px; color:#0F172A; }
.news-card-summary { font-size:12px; color:#64748B; line-height:1.6; flex:1; margin-bottom:12px; }
.news-card-footer { display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#94A3B8; border-top:1px solid #F1F5F9; padding-top:10px; }
.news-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.news-topbar h1 { font-size:22px; font-weight:700; color:#0F172A; }
.news-refresh-btn { background:none; border:none; cursor:pointer; color:#4F46E5; font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; padding:6px 10px; border-radius:6px; }
.news-refresh-btn:hover { background:#EEF2FF; }
.news-mark-all-btn { font-size:13px; color:#10B981; font-weight:600; background:none; border:none; cursor:pointer; padding:6px 10px; border-radius:6px; }
.news-mark-all-btn:hover { background:#ECFDF5; }
</style>

<div class="news-topbar">
    <div style="display:flex;align-items:center;gap:16px">
        <h1>News</h1>
        <button class="news-refresh-btn" onclick="window.location.reload()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Refresh
        </button>
    </div>
    <button class="news-mark-all-btn" onclick="markAllRead()" id="mark-all-btn">
        ✓ Mark all as read
    </button>
</div>

<?php if (empty($newsList)): ?>
<div class="card">
    <div style="text-align:center;padding:60px 24px;color:#94A3B8">
        <div style="font-size:48px;margin-bottom:12px">📰</div>
        <h3 style="font-size:16px;color:#94A3B8;margin-bottom:6px">No news yet</h3>
        <p style="font-size:13px">Check back soon for updates and new offers.</p>
    </div>
</div>
<?php else: ?>

<div class="news-grid">
<?php foreach ($newsList as $n):
    $isUnread = !$n['is_read'];
    $timeAgo  = _newsTimeAgo($n['published_at'] ?? $n['created_at']);
?>
<a href="/affiliate/news/<?= $n['id'] ?>" class="news-card <?= $isUnread ? 'unread' : '' ?>">
    <div class="news-card-img">
        <?php if ($n['image']): ?>
        <img src="<?= Helpers::e($n['image']) ?>" alt="<?= Helpers::e($n['title']) ?>">
        <?php else: ?>
        <div class="no-img">📰</div>
        <?php endif; ?>
        <?php if ($n['is_hot']): ?>
        <span class="news-hot-badge">🔥 HOT</span>
        <?php endif; ?>
        <span class="news-time-badge"><?= Helpers::e($timeAgo) ?></span>
        <?php if ($isUnread && !$n['is_hot']): ?>
        <span class="news-unread-dot" title="Unread"></span>
        <?php endif; ?>
    </div>
    <div class="news-card-body">
        <div class="news-card-title"><?= Helpers::e($n['title']) ?></div>
        <?php if ($n['summary']): ?>
        <div class="news-card-summary"><?= Helpers::e(substr($n['summary'],0,120)) ?><?= strlen($n['summary'])>120?'…':'' ?></div>
        <?php endif; ?>
        <div class="news-card-footer">
            <span><?= date('M j, Y', strtotime($n['published_at'] ?? $n['created_at'])) ?></span>
            <?php if ($isUnread): ?><span style="color:#4F46E5;font-weight:600">● New</span><?php endif; ?>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function markAllRead() {
    fetch('/affiliate/news?action=mark_all_read')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.success) {
                document.querySelectorAll('.news-card.unread').forEach(function(el){ el.classList.remove('unread'); });
                document.querySelectorAll('.news-unread-dot').forEach(function(el){ el.remove(); });
                document.querySelectorAll('.news-card-footer span[style*="color:#4F46E5"]').forEach(function(el){ el.remove(); });
                document.getElementById('mark-all-btn').textContent = '✓ All read';
                // Update topbar badge
                var nb = document.getElementById('news-badge');
                if (nb) nb.style.display = 'none';
            }
        });
}
</script>

<?php
function _newsTimeAgo(?string $dt): string {
    if (!$dt) return 'Recently';
    $diff = time() - strtotime($dt);
    if ($diff < 3600)   return 'Just now';
    if ($diff < 86400)  return round($diff/3600) . ' hours ago';
    if ($diff < 604800) return round($diff/86400) . ' days ago';
    if ($diff < 2592000)return round($diff/604800) . ' weeks ago';
    if ($diff < 31536000)return round($diff/2592000) . ' months ago';
    return round($diff/31536000) . ' years ago';
}
?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
