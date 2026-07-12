<?php
$appName  = Helpers::e(Config::get('config','app.name') ?? 'Affscash');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'blog';

$slug = trim($_GET['slug'] ?? '');
$post = null;
$relatedPosts = [];

if ($slug) {
    try {
        Database::query("CREATE TABLE IF NOT EXISTS `landing_posts` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title`        VARCHAR(500) NOT NULL,
            `slug`         VARCHAR(520) NOT NULL,
            `excerpt`      TEXT,
            `body`         LONGTEXT,
            `image`        VARCHAR(512) DEFAULT NULL,
            `category`     VARCHAR(100) DEFAULT NULL,
            `is_featured`  TINYINT(1) DEFAULT 0,
            `status`       ENUM('published','draft') DEFAULT 'draft',
            `published_at` DATETIME NULL,
            `created_by`   INT UNSIGNED NULL,
            `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_slug` (`slug`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $post = Database::fetchOne(
            "SELECT * FROM landing_posts WHERE slug=? AND status='published'",
            [$slug]
        );

        if ($post && $post['category']) {
            $relatedPosts = Database::fetchAll(
                "SELECT id, title, slug, excerpt, image, category, published_at
                 FROM landing_posts
                 WHERE status='published' AND category=? AND id != ?
                 ORDER BY is_featured DESC, published_at DESC LIMIT 3",
                [$post['category'], $post['id']]
            );
        }
    } catch (\Throwable $e) {}
}

if (!$post) {
    // Post not found — redirect to blog
    Helpers::redirect('/blog');
}

$pageTitle = Helpers::e($post['title']) . ' | ' . $appName;
$seoDescription = htmlspecialchars(strip_tags($post['excerpt'] ?: substr($post['body'], 0, 160)), ENT_QUOTES, 'UTF-8');
require BASE_PATH . '/views/layouts/public_top.php';
?>

<!-- POST BANNER -->
<div class="page-banner" style="<?= $post['image'] ? 'background-image:linear-gradient(135deg,rgba(10,5,24,0.9),rgba(20,10,40,0.85)),url('.Helpers::e($post['image']).');background-size:cover;background-position:center;' : 'background: linear-gradient(135deg, #0f0826 0%, #05020c 100%);' ?> border-bottom: 1px solid rgba(255,255,255,0.08)">
  <div class="container" style="max-width:860px">
    <div class="breadcrumb-bar">
      <a href="/" style="color: rgba(255,255,255,0.6); text-decoration: none">Home</a> <span style="color: rgba(255,255,255,0.3)">/</span> <a href="/blog" style="color: #7c3aed; text-decoration: none">Blog</a> <span style="color: rgba(255,255,255,0.3)">/</span>
      <span style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color: #fff"><?= Helpers::e(substr($post['title'],0,40)) ?>…</span>
    </div>
    <?php if ($post['category']): ?>
    <div style="display:inline-block;background:rgba(255,255,255,.15);color:#fff;border-radius:20px;padding:4px 16px;font-size:13px;font-weight:700;margin-bottom:14px"><?= Helpers::e($post['category']) ?></div>
    <?php endif; ?>
    <h1 style="font-size:clamp(26px,4vw,46px);color:#fff;margin-bottom:16px;line-height:1.15"><?= Helpers::e($post['title']) ?></h1>
    <?php if ($post['published_at']): ?>
    <p style="color:rgba(255,255,255,.7);font-size:14px"><i class="fa-regular fa-calendar"></i> <?= date('d F Y', strtotime($post['published_at'])) ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- ARTICLE CONTENT -->
<section style="padding:80px 0;background:linear-gradient(135deg, #0a0518 0%, #05020c 100%)">
  <div class="container" style="max-width:860px">

    <!-- Back link -->
    <div style="margin-bottom:32px">
      <a href="/blog" style="color:#7c3aed;font-size:14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:color .2s" onmouseover="this.style.color='#0ea5e9'" onmouseout="this.style.color='#7c3aed'">
        <i class="fa-solid fa-arrow-left"></i> Back to Blog
      </a>
    </div>

    <!-- Featured image (if not used as banner background) -->
    <?php if ($post['image']): ?>
    <div style="border-radius:20px;overflow:hidden;margin-bottom:36px;box-shadow:0 10px 40px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.08);aspect-ratio:16/7">
      <img src="<?= Helpers::e($post['image']) ?>" alt="<?= Helpers::e($post['title']) ?>"
           style="width:100%;height:100%;object-fit:cover;display:block">
    </div>
    <?php endif; ?>

    <!-- Article body -->
    <div style="font-size:16px;line-height:1.85;color:rgba(255,255,255,0.8);max-width:720px;margin:0 auto">
      <?php if ($post['excerpt']): ?>
      <p style="font-size:18px;color:#fff;font-weight:500;line-height:1.6;margin-bottom:28px;border-left:4px solid #7c3aed;padding-left:20px;font-style:italic"><?= Helpers::e($post['excerpt']) ?></p>
      <?php endif; ?>
      <?php if ($post['body']): ?>
      <div class="post-body"><?= $post['body'] /* body is admin-entered HTML via Quill.js */ ?></div>
      <?php else: ?>
      <p style="color:rgba(255,255,255,0.55)">Full article content coming soon.</p>
      <?php endif; ?>
    </div>

    <!-- CTA -->
    <div style="background:var(--grad-brand);border-radius:20px;padding:40px;text-align:center;margin-top:52px">
      <h2 style="color:#fff;font-size:28px;margin-bottom:10px">Start Earning With <?= $appName ?></h2>
      <p style="color:rgba(255,255,255,.8);font-size:14px;margin-bottom:20px">Join our affiliate network and access 300+ premium CPA offers with highest payouts.</p>
      <a href="/register/affiliate" class="btn-primary-custom" style="background:#fff;color:var(--violet);-webkit-text-fill-color:var(--violet)">
        <i class="fa-solid fa-rocket"></i> Join <?= $appName ?> Free
      </a>
    </div>

    <!-- Related posts -->
    <?php if (!empty($relatedPosts)): ?>
    <div style="margin-top:56px">
      <h3 style="font-size:24px;margin-bottom:24px;color:#fff">More from <em><?= Helpers::e($post['category']) ?></em></h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px">
        <?php foreach ($relatedPosts as $rp):
          $rpImg  = $rp['image'] ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=400&q=70';
          $rpDate = $rp['published_at'] ? date('d M Y', strtotime($rp['published_at'])) : '';
        ?>
        <a href="/blog/<?= Helpers::e($rp['slug']) ?>" style="background:rgba(15,10,36,0.65);border-radius:14px;border:1px solid rgba(255,255,255,0.08);overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);text-decoration:none;display:flex;flex-direction:column;transition:all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='rgba(124,58,237,0.3)';" onmouseout="this.style.transform='';this.style.borderColor='rgba(255,255,255,0.08)';">
          <div style="aspect-ratio:16/9;overflow:hidden;border-bottom:1px solid rgba(255,255,255,0.08)">
            <img src="<?= Helpers::e($rpImg) ?>" alt="<?= Helpers::e($rp['title']) ?>" style="width:100%;height:100%;object-fit:cover;display:block" loading="lazy">
          </div>
          <div style="padding:16px">
            <h4 style="font-size:15px;color:#fff;margin-bottom:6px;line-height:1.3;margin-top:0"><?= Helpers::e($rp['title']) ?></h4>
            <?php if ($rpDate): ?><span style="font-size:12px;color:rgba(255,255,255,0.55)"><?= $rpDate ?></span><?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<style>
.post-body{color:rgba(255,255,255,0.8);font-size:16px;line-height:1.85}
.post-body h1,.post-body h2,.post-body h3,.post-body h4{font-family:'Rajdhani',sans-serif;margin:28px 0 12px;color:#fff}
.post-body h2{font-size:26px}.post-body h3{font-size:22px}
.post-body p{margin-bottom:18px}
.post-body ul,.post-body ol{padding-left:24px;margin-bottom:18px}
.post-body li{margin-bottom:6px}
.post-body img{max-width:100%;border-radius:12px;margin:16px 0}
.post-body a{color:#7c3aed;text-decoration:underline}
.post-body blockquote{border-left:4px solid #7c3aed;padding:12px 20px;background:rgba(255,255,255,0.03);border-radius:0 10px 10px 0;margin:20px 0;font-style:italic;color:#fff}
.post-body code{background:rgba(255,255,255,0.06);color:#fff;padding:2px 8px;border-radius:4px;font-size:13px}
.post-body pre{background:rgba(0,0,0,0.3);color:#e2e8f0;padding:20px;border-radius:12px;overflow-x:auto;margin:20px 0;border:1px solid rgba(255,255,255,0.08)}
</style>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
