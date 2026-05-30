<?php
$appName  = Helpers::e(Config::get('config','app.name') ?? 'EliteAli');
$appLogo  = Config::get('config','app.logo');
$logoSrc  = $appLogo ? Helpers::e($appLogo) : '/logoo.png';
$_isAdmin = Auth::id() && Auth::role() === 'admin';
$_isLogged= (bool)Auth::id();
$_role    = Auth::role();
$_currentPage = 'blog';

// Filters
$categoryFilter = trim(strip_tags($_GET['cat'] ?? ''));
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 9;

$posts      = [];
$categories = [];
$totalCount = 0;
$totalPages = 1;


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

    // Distinct categories
    $catRows = Database::fetchAll("SELECT DISTINCT category FROM landing_posts WHERE status='published' AND category IS NOT NULL AND category != '' ORDER BY category");
    foreach ($catRows as $cr) $categories[] = $cr['category'];

    $where  = "status='published'";
    $params = [];
    if ($categoryFilter) {
        $where .= " AND category=?";
        $params[] = $categoryFilter;
    }
    $countRow   = Database::fetchOne("SELECT COUNT(*) as c FROM landing_posts WHERE $where", $params);
    $totalCount = (int)($countRow['c'] ?? 0);
    $totalPages = max(1, (int)ceil($totalCount / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    if ($totalCount > 0) {
        $posts = Database::fetchAll(
            "SELECT id, title, slug, excerpt, image, category, published_at, is_featured
             FROM landing_posts WHERE $where
             ORDER BY is_featured DESC, published_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );
    }
} catch (\Throwable $e) {}

$useStatic = false;

$pageTitle = "Blog | $appName";
require BASE_PATH . '/views/layouts/public_top.php';
?>

<!-- PAGE BANNER -->
<div class="page-banner">
  <div class="container">
    <div class="breadcrumb-bar">
      <a href="/">Home</a> <span>/</span> <span>Blog</span>
    </div>
    <h1>📝 <em style="-webkit-text-fill-color:#fff;background:none;color:#fff">Blog</em> &amp; Updates</h1>
    <p>News, guides and insights from the <?= $appName ?> affiliate network team.</p>
  </div>
</div>

<!-- CONTENT -->
<section style="padding:64px 0;background:#fff">
  <div class="container">

    <!-- Category filter (only if DB has categories) -->
    <?php if (!$useStatic && !empty($categories)): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:36px;justify-content:center">
      <a href="/blog" style="padding:8px 18px;border-radius:50px;font-size:13px;font-weight:600;border:2px solid <?= $categoryFilter===''?'var(--violet)':'var(--border)' ?>;background:<?= $categoryFilter===''?'var(--violet)':'#fff' ?>;color:<?= $categoryFilter===''?'#fff':'var(--text)' ?>;text-decoration:none">All Posts</a>
      <?php foreach ($categories as $cat): ?>
      <a href="/blog?cat=<?= urlencode($cat) ?>" style="padding:8px 18px;border-radius:50px;font-size:13px;font-weight:600;border:2px solid <?= $categoryFilter===$cat?'var(--violet)':'var(--border)' ?>;background:<?= $categoryFilter===$cat?'var(--violet)':'#fff' ?>;color:<?= $categoryFilter===$cat?'#fff':'var(--text)' ?>;text-decoration:none"><?= Helpers::e($cat) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Featured post (first item) -->
    <?php
    $featuredPost = $posts[0] ?? null;
    $sidePosts    = array_slice($posts, 1);
    if ($featuredPost):
        $fImg  = $featuredPost['image'] ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80';
        $fDate = $featuredPost['published_at'] ? date('d M Y', strtotime($featuredPost['published_at'])) : '';
        $fLink = $featuredPost['slug'] ? '/blog/' . Helpers::e($featuredPost['slug']) : '/blog';
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:36px;margin-bottom:52px;align-items:center" class="featured-post-grid">
      <div style="border-radius:20px;overflow:hidden;aspect-ratio:16/10;box-shadow:0 8px 40px rgba(124,58,237,.12)">
        <img src="<?= Helpers::e($fImg) ?>" alt="<?= Helpers::e($featuredPost['title']) ?>"
             style="width:100%;height:100%;object-fit:cover;display:block" loading="lazy">
      </div>
      <div>
        <?php if ($featuredPost['is_featured']): ?>
        <div style="display:inline-flex;align-items:center;gap:6px;background:#FEF3C7;color:#92400E;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;margin-bottom:14px">⭐ Featured Post</div>
        <?php endif; ?>
        <?php if ($featuredPost['category']): ?>
        <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(124,58,237,.1);color:var(--violet);border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700;margin-bottom:12px;margin-left:6px"><?= Helpers::e($featuredPost['category']) ?></div>
        <?php endif; ?>
        <h2 style="font-size:clamp(22px,3vw,32px);margin-bottom:12px;line-height:1.2">
          <a href="<?= $fLink ?>" style="color:var(--text);text-decoration:none" onmouseover="this.style.color='var(--pink)'" onmouseout="this.style.color='var(--text)'"><?= Helpers::e($featuredPost['title']) ?></a>
        </h2>
        <?php if ($fDate): ?><div style="font-size:13px;color:var(--muted);margin-bottom:14px"><i class="fa-regular fa-calendar"></i> <?= $fDate ?></div><?php endif; ?>
        <?php if ($featuredPost['excerpt']): ?>
        <p style="color:var(--muted);font-size:14px;line-height:1.75;margin-bottom:20px"><?= Helpers::e(substr($featuredPost['excerpt'],0,200)) ?><?= strlen($featuredPost['excerpt'])>200?'…':'' ?></p>
        <?php endif; ?>
        <a href="<?= $fLink ?>" class="btn-primary-custom" style="font-size:13px;padding:11px 24px">Read Article →</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Post grid -->
    <?php if (!empty($sidePosts)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;margin-bottom:48px">
      <?php foreach ($sidePosts as $bp):
        $bImg  = $bp['image'] ?: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?w=600&q=80';
        $bDate = $bp['published_at'] ? date('d M Y', strtotime($bp['published_at'])) : '';
        $bLink = $bp['slug'] ? '/blog/' . Helpers::e($bp['slug']) : '/blog';
      ?>
      <div style="background:#fff;border-radius:16px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 16px rgba(124,58,237,.06);transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 32px rgba(124,58,237,.12)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 16px rgba(124,58,237,.06)'">
        <div style="aspect-ratio:16/9;overflow:hidden">
          <img src="<?= Helpers::e($bImg) ?>" alt="<?= Helpers::e($bp['title']) ?>"
               style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s" loading="lazy">
        </div>
        <div style="padding:20px 22px;flex:1;display:flex;flex-direction:column;gap:10px">
          <?php if ($bp['category']): ?>
          <span style="display:inline-block;background:var(--bg2);color:var(--violet);border-radius:20px;padding:2px 12px;font-size:11px;font-weight:700;width:fit-content"><?= Helpers::e($bp['category']) ?></span>
          <?php endif; ?>
          <h3 style="font-size:17px;line-height:1.3;flex:1">
            <a href="<?= $bLink ?>" style="color:var(--text);text-decoration:none" onmouseover="this.style.color='var(--pink)'" onmouseout="this.style.color='var(--text)'"><?= Helpers::e($bp['title']) ?></a>
          </h3>
          <?php if ($bp['excerpt']): ?><p style="font-size:13px;color:var(--muted);line-height:1.6"><?= Helpers::e(substr($bp['excerpt'],0,120)) ?><?= strlen($bp['excerpt'])>120?'…':'' ?></p><?php endif; ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid var(--border)">
            <?php if ($bDate): ?><span style="font-size:12px;color:var(--muted)"><i class="fa-regular fa-calendar"></i> <?= $bDate ?></span><?php else: ?><span></span><?php endif; ?>
            <a href="<?= $bLink ?>" style="font-size:12px;color:var(--violet);font-weight:600;text-decoration:none">Read More →</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$featuredPost): ?>
    <!-- Full empty state -->
    <div style="text-align:center;padding:64px 24px;color:var(--muted)">
      <div style="font-size:48px;margin-bottom:16px;opacity:.35">📝</div>
      <h3 style="font-size:20px;color:var(--muted);margin-bottom:8px">No posts yet</h3>
      <p style="font-size:14px;margin-bottom:24px">Check back soon for news, guides and updates from <?= $appName ?>.</p>
      <a href="/" class="btn-primary-custom">← Back to Home</a>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if (!$useStatic && $totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <?php if ($page > 1): ?>
      <a href="/blog?<?= $categoryFilter?'cat='.urlencode($categoryFilter).'&':'' ?>page=<?= $page-1 ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid var(--border);color:var(--text);font-size:13px;font-weight:600;text-decoration:none">← Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
      <a href="/blog?<?= $categoryFilter?'cat='.urlencode($categoryFilter).'&':'' ?>page=<?= $i ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid <?= $i===$page?'var(--violet)':'var(--border)' ?>;background:<?= $i===$page?'var(--violet)':'#fff' ?>;color:<?= $i===$page?'#fff':'var(--text)' ?>;font-size:13px;font-weight:600;text-decoration:none"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="/blog?<?= $categoryFilter?'cat='.urlencode($categoryFilter).'&':'' ?>page=<?= $page+1 ?>" style="padding:8px 16px;border-radius:8px;border:1.5px solid var(--border);color:var(--text);font-size:13px;font-weight:600;text-decoration:none">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div style="background:var(--grad-brand);border-radius:20px;padding:48px 40px;text-align:center;margin-top:32px">
      <h2 style="color:#fff;font-size:32px;margin-bottom:12px">Ready to <em style="background:none;-webkit-text-fill-color:#fff;color:#fff">Start Earning</em>?</h2>
      <p style="color:rgba(255,255,255,.8);font-size:15px;margin-bottom:24px">Join <?= $appName ?> and get access to 300+ premium CPA offers today.</p>
      <a href="/register/affiliate" class="btn-primary-custom" style="background:#fff;color:var(--violet);-webkit-text-fill-color:var(--violet);font-size:15px;padding:14px 32px">
        <i class="fa-solid fa-rocket"></i> Join <?= $appName ?> Free
      </a>
    </div>

  </div>
</section>

<style>
@media(max-width:767px){
  .featured-post-grid{grid-template-columns:1fr !important}
}
</style>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
