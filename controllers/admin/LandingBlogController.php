<?php
Auth::check('admin');

// Auto-create table
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
} catch (\Throwable $e) {}

// One-time additive column for blog broadcast idempotency. Without this,
// re-saving a published post would re-email everyone. MySQL 5.7 safe —
// any "duplicate column" error is silently ignored.
try { Database::query("ALTER TABLE `landing_posts` ADD COLUMN `email_sent` TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $_) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Toggle status ──────────────────────────────────────────────────────────
if ($action === 'toggle') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id  = (int)($_GET['id'] ?? 0);
        $row = Database::fetchOne("SELECT status FROM landing_posts WHERE id=?", [$id]);
        if ($row) {
            $new = $row['status'] === 'published' ? 'draft' : 'published';
            $extra = $new === 'published' ? ['published_at' => date('Y-m-d H:i:s')] : [];
            Database::update('landing_posts', array_merge(['status' => $new], $extra), 'id=?', [$id]);
            Helpers::flash('success', 'Post status updated to ' . $new . '.');
            try {
                require_once BASE_PATH . '/core/SeoSitemap.php';
                SeoSitemap::writeFile();
            } catch (\Throwable $_e) {}

            // ── Email blast: only when transitioning into 'published' and the
            // post hasn't been emailed before. email_sent flag prevents repeat
            // sends if admin toggles draft↔published multiple times.
            if ($new === 'published') {
                try {
                    $fresh = Database::fetchOne(
                        "SELECT id, title, excerpt, slug, image, COALESCE(email_sent,0) AS email_sent
                         FROM landing_posts WHERE id=?",
                        [$id]
                    );
                    if ($fresh && !(int)$fresh['email_sent']) {
                        _sendBlogPostEmail((int)$fresh['id'], (string)$fresh['title'], (string)($fresh['excerpt'] ?? ''), (string)($fresh['slug'] ?? ''), $fresh['image'] ?? null);
                        Database::update('landing_posts', ['email_sent' => 1], 'id=?', [$id]);
                    }
                } catch (\Throwable $_) {}
            }
        }
    }
    Helpers::redirect('/admin/landing/blog');

// ── Create / Edit ──────────────────────────────────────────────────────────
} elseif ($action === 'create' || ($action === 'edit' && isset($_GET['id']))) {
    $postId    = $action === 'edit' ? (int)$_GET['id'] : 0;
    $post      = $postId ? Database::fetchOne("SELECT * FROM landing_posts WHERE id=?", [$postId]) : null;
    if ($postId && !$post) Helpers::redirect('/admin/landing/blog');

    $errors    = [];
    $pageTitle = $postId ? 'Edit Blog Post' : 'Create Blog Post';

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $title      = trim(Helpers::postRaw('title'));
        $excerpt    = trim(Helpers::postRaw('excerpt'));
        $body       = Helpers::postRaw('body');
        $category   = trim(Helpers::postRaw('category'));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $status     = in_array(Helpers::post('status'), ['published','draft']) ? Helpers::post('status') : 'draft';
        $slug       = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug       = trim($slug, '-') ?: 'post-' . time();

        if (!$title) $errors[] = 'Title is required.';

        // Image upload
        $image = $post['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Image must be JPEG, PNG, GIF or WEBP.';
            } else {
                $dir = BASE_PATH . '/assets/uploads/landing/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext   = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = 'post_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                    $image = '/assets/uploads/landing/' . $fname;
                }
            }
        }

        if (!$errors) {
            // Make slug unique
            $slugCheck = Database::fetchOne(
                "SELECT id FROM landing_posts WHERE slug=?" . ($postId ? " AND id!=?" : ''),
                $postId ? [$slug, $postId] : [$slug]
            );
            if ($slugCheck) $slug .= '-' . time();

            $publishedAt = ($status === 'published')
                ? ($post['published_at'] ?? date('Y-m-d H:i:s'))
                : null;

            $data = [
                'title'        => $title,
                'slug'         => $slug,
                'excerpt'      => $excerpt,
                'body'         => $body,
                'image'        => $image,
                'category'     => $category ?: null,
                'is_featured'  => $isFeatured,
                'status'       => $status,
                'published_at' => $publishedAt,
            ];

            $emailedAffiliates = 0;
            if ($postId) {
                $wasPublished = ($post['status'] ?? '') === 'published';
                Database::update('landing_posts', $data, 'id=?', [$postId]);

                // Send the blast only when this save transitions the post into
                // 'published' for the first time AND the broadcast hasn't run
                // before (email_sent flag is the idempotency guard).
                if (!$wasPublished && $status === 'published' && !(int)($post['email_sent'] ?? 0)) {
                    try {
                        $emailedAffiliates = _sendBlogPostEmail((int)$postId, $title, $excerpt, $slug, $image);
                        Database::update('landing_posts', ['email_sent' => 1], 'id=?', [$postId]);
                    } catch (\Throwable $_) {}
                }
                Helpers::flash('success', 'Blog post updated.' . ($emailedAffiliates > 0 ? ' Email sent to ' . $emailedAffiliates . ' affiliate(s).' : ''));
            } else {
                $data['created_by'] = Auth::id();
                $newId = Database::insert('landing_posts', $data);
                if ($status === 'published' && $newId) {
                    try {
                        $emailedAffiliates = _sendBlogPostEmail((int)$newId, $title, $excerpt, $slug, $image);
                        Database::update('landing_posts', ['email_sent' => 1], 'id=?', [(int)$newId]);
                    } catch (\Throwable $_) {}
                }
                Helpers::flash('success', 'Blog post ' . ($status === 'published' ? 'published.' : 'saved as draft.') . ($emailedAffiliates > 0 ? ' Email sent to ' . $emailedAffiliates . ' affiliate(s).' : ''));
            }
            // Refresh sitemap.xml so the new/updated slug is picked up immediately.
            try {
                require_once BASE_PATH . '/core/SeoSitemap.php';
                SeoSitemap::writeFile();
            } catch (\Throwable $_e) { /* best-effort — fallback router still generates on demand */ }
            Helpers::redirect('/admin/landing/blog');
        }
    }

    require BASE_PATH . '/views/admin/landing/blog_form.php';

// ── Delete ─────────────────────────────────────────────────────────────────
} elseif ($action === 'delete' && isset($_GET['id'])) {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Database::query("DELETE FROM landing_posts WHERE id=?", [(int)$_GET['id']]);
        Helpers::flash('success', 'Blog post deleted.');
        try {
            require_once BASE_PATH . '/core/SeoSitemap.php';
            SeoSitemap::writeFile();
        } catch (\Throwable $_e) {}
    }
    Helpers::redirect('/admin/landing/blog');

// ── Index ──────────────────────────────────────────────────────────────────
} else {
    $pageTitle    = 'Landing Blog Posts';
    $q            = trim(Helpers::get('q') ?? '');
    $statusFilter = Helpers::get('status_filter') ?? '';
    $catFilter    = trim(Helpers::get('cat') ?? '');

    $params = [];
    $where  = '1=1';
    if ($q) {
        $where   .= ' AND (title LIKE ? OR excerpt LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if (in_array($statusFilter, ['published','draft'])) {
        $where   .= ' AND status=?';
        $params[] = $statusFilter;
    }
    if ($catFilter) {
        $where   .= ' AND category=?';
        $params[] = $catFilter;
    }

    $posts = Database::fetchAll(
        "SELECT p.*, CONCAT(u.first_name,' ',u.last_name) as author_name
         FROM landing_posts p
         LEFT JOIN users u ON u.id=p.created_by
         WHERE $where
         ORDER BY p.created_at DESC",
        $params
    );

    // Distinct categories for filter
    $categories = Database::fetchAll(
        "SELECT DISTINCT category FROM landing_posts WHERE category IS NOT NULL AND category!='' ORDER BY category"
    );

    require BASE_PATH . '/views/admin/landing/blog_index.php';
}

/**
 * Email every active affiliate the moment a new blog post is published.
 *
 * Mirrors _sendNewsEmail() and _broadcastNewOfferEmail() so the three
 * broadcast surfaces (offers / news / blog) share a consistent pattern.
 * Returns the number of mails attempted. Individual send failures are
 * swallowed so a single bad address can't block the rest of the blast.
 */
function _sendBlogPostEmail(int $postId, string $title, string $excerpt, string $slug, ?string $image = null): int
{
    if ($postId <= 0 || $title === '') return 0;

    $appUrl   = rtrim((string)(Config::get('config','app.url') ?: ''), '/');
    $siteName = Config::get('config','app.name') ?? 'AffiliateTracker';
    $link     = $appUrl . '/blog/' . ($slug !== '' ? $slug : (string)$postId);
    $imgUrl   = $image ? ($appUrl . $image) : '';

    $titleH   = htmlspecialchars($title,    ENT_QUOTES, 'UTF-8');
    $excerptH = trim($excerpt) !== ''
        ? nl2br(htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'))
        : '<em style="color:#94A3B8">New post published. Click below to read.</em>';

    $subject = '📝 New Blog Post: ' . $title . ' — ' . $siteName;

    $appLogo = Config::get('config','app.logo') ?? '';
    $logoHtml = '';
    if (!empty($appLogo)) {
        $logoUrl = filter_var($appLogo, FILTER_VALIDATE_URL) ? $appLogo : $appUrl . '/' . ltrim($appLogo, '/');
        $logoHtml = '<div style="margin-bottom:12px;text-align:center"><img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" style="max-height:40px;max-width:200px;object-fit:contain"></div>';
    }

    $bodyTpl = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,Segoe UI,Roboto,sans-serif;color:#0F172A">
    <div style="max-width:600px;margin:0 auto;padding:24px">
        <div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #E2E8F0">
            <div style="background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;padding:24px 28px">
                ' . $logoHtml . '
                <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.8">New Blog Post</div>
                <h1 style="margin:6px 0 0;font-size:22px;line-height:1.3">' . $titleH . '</h1>
            </div>'
            . ($imgUrl !== '' ? '<img src="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" alt="" style="display:block;width:100%;max-height:360px;object-fit:contain;background:#0f0826">' : '') .
            '<div style="padding:22px 28px">
                <p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {NAME},</p>
                <p style="margin:0 0 14px;font-size:14px;color:#334155">A new post just went live on the ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' blog.</p>

                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 16px;margin:0 0 22px">
                    <div style="font-size:11px;color:#64748B;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Summary</div>
                    <div style="font-size:13.5px;color:#334155">' . $excerptH . '</div>
                </div>

                <div style="text-align:center;margin:24px 0 4px">
                    <a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#0F766E;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">Read Full Post &rarr;</a>
                </div>
            </div>
            <div style="padding:16px 28px;background:#F8FAFC;border-top:1px solid #E2E8F0;text-align:center;font-size:11px;color:#94A3B8">
                You\'re receiving this because you\'re an active affiliate on ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '.<br>
                <a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '/privacy-policy" style="color:#94A3B8">Privacy Policy</a>
            </div>
        </div>
    </div>
    </body></html>';

    // Pull every active affiliate. GROUP BY u.id de-dupes any accidental
    // duplicate join rows so a single affiliate can never be emailed twice.
    try {
        $recipients = Database::fetchAll(
            "SELECT u.email, u.first_name, u.last_name
             FROM users u
             INNER JOIN affiliates af ON af.user_id = u.id
             WHERE u.role = 'affiliate' AND u.status = 'active'
               AND u.email IS NOT NULL AND u.email <> ''
             GROUP BY u.id"
        );
    } catch (\Throwable $e) { return 0; }

    $sent = 0;
    foreach ($recipients as $r) {
        $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
        if ($name === '') $name = 'Affiliate';
        $personal = str_replace('{NAME}', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $bodyTpl);
        try {
            if (Mailer::sendRaw($r['email'], $name, $subject, $personal, 'blog_new')) {
                $sent++;
            }
        } catch (\Throwable $_e) {
            // skip — keep going
        }
    }
    return $sent;
}
