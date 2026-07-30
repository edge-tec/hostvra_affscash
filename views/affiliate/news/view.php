<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
.news-article { max-width:780px; margin:0 auto; }
.news-article-hero { border-radius:16px; overflow:hidden; margin-bottom:28px; background:linear-gradient(135deg,#0f0826 0%,#1e1b4b 100%); border:1px solid #E2E8F0; box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:10px; display:flex; align-items:center; justify-content:center; }
.news-article-hero img { width:100%; height:auto; max-height:650px; object-fit:contain; display:block; border-radius:12px; margin:0 auto; }
.news-article-meta { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:20px; font-size:13px; color:#64748B; }
.news-article-title { font-size:28px; font-weight:800; line-height:1.3; color:#0F172A; margin-bottom:16px; }
.news-article-summary { font-size:15px; color:#475569; line-height:1.7; margin-bottom:24px; padding-bottom:24px; border-bottom:1px solid #E2E8F0; }
.ql-snow .ql-editor { padding:0; font-size:15px; line-height:1.8; color:#334155; }
.ql-snow .ql-editor h1,.ql-snow .ql-editor h2,.ql-snow .ql-editor h3 { color:#0F172A; font-weight:700; margin:20px 0 10px; }
.ql-snow .ql-editor p { margin-bottom:14px; }
.ql-snow .ql-editor img { max-width:100% !important; height:auto !important; object-fit:contain !important; border-radius:8px; margin:16px auto; display:block; }
.ql-snow .ql-editor a { color:#4F46E5; }
.ql-snow .ql-editor blockquote { border-left:4px solid #C7D2FE; padding:10px 16px; background:#EEF2FF; color:#3730A3; border-radius:0 6px 6px 0; margin:16px 0; }
.ql-toolbar { display:none !important; }
.ql-container.ql-snow { border:none !important; }
</style>

<div class="news-article">
    <div style="margin-bottom:20px">
        <a href="/affiliate/news" style="color:#64748B;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            ← Back to News
        </a>
    </div>

    <?php if ($newsItem['image']): ?>
    <div class="news-article-hero">
        <img src="<?= Helpers::e($newsItem['image']) ?>" alt="<?= Helpers::e($newsItem['title']) ?>">
    </div>
    <?php endif; ?>

    <div class="news-article-meta">
        <?php if ($newsItem['is_hot']): ?>
        <span style="background:#FEE2E2;color:#B91C1C;border-radius:20px;padding:3px 14px;font-weight:700;font-size:12px">🔥 HOT NEWS</span>
        <?php endif; ?>
        <span><?= date('F j, Y', strtotime($newsItem['published_at'] ?? $newsItem['created_at'])) ?></span>
        <?php if ($newsItem['author_name']): ?>
        <span>· By <?= Helpers::e($newsItem['author_name']) ?></span>
        <?php endif; ?>
    </div>

    <h1 class="news-article-title"><?= Helpers::e($newsItem['title']) ?></h1>

    <?php if ($newsItem['summary']): ?>
    <div class="news-article-summary"><?= Helpers::e($newsItem['summary']) ?></div>
    <?php endif; ?>

    <?php if ($newsItem['body']): ?>
    <div id="news-body" class="ql-container ql-snow">
        <div class="ql-editor"><?= $newsItem['body'] ?></div>
    </div>
    <?php endif; ?>

    <div style="margin-top:40px;padding-top:20px;border-top:1px solid #E2E8F0">
        <a href="/affiliate/news" class="btn btn-secondary">← Back to News</a>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
