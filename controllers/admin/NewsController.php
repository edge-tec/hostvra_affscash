<?php
Auth::check('admin');

// Auto-create tables
try {
    Database::query("CREATE TABLE IF NOT EXISTS `news` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title`        VARCHAR(500) NOT NULL,
        `slug`         VARCHAR(520) NOT NULL,
        `summary`      TEXT,
        `body`         LONGTEXT,
        `image`        VARCHAR(512) DEFAULT NULL,
        `is_hot`       TINYINT(1) DEFAULT 0,
        `status`       ENUM('published','draft') DEFAULT 'draft',
        `email_sent`   TINYINT(1) DEFAULT 0,
        `created_by`   INT UNSIGNED NULL,
        `published_at` DATETIME NULL,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_slug` (`slug`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    Database::query("CREATE TABLE IF NOT EXISTS `news_reads` (
        `id`      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `news_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `read_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_news_user` (`news_id`,`user_id`),
        INDEX `idx_user` (`user_id`),
        INDEX `idx_news` (`news_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Ensure news email template exists for existing installs
    Database::query("INSERT IGNORE INTO `email_templates` (`event_type`,`label`,`subject`,`html_body`,`is_active`) VALUES ('news_published','News Published','{{title}} — {{site_name}}','<div style=\"font-family:sans-serif;max-width:600px;margin:0 auto\"><div style=\"background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:28px 32px;border-radius:12px 12px 0 0\"><h1 style=\"color:#fff;margin:0;font-size:22px;font-weight:800\">{{site_name}}</h1></div><div style=\"background:#fff;padding:28px 32px;border:1px solid #E2E8F0;border-top:none\"><h2 style=\"font-size:20px;font-weight:700;color:#0F172A;margin:0 0 12px\">{{title}}</h2><p style=\"font-size:14px;color:#475569;line-height:1.7;margin:0 0 20px\">{{summary}}</p><a href=\"{{link}}\" style=\"display:inline-block;background:#4F46E5;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px\">Read Full Article →</a></div><div style=\"background:#F8FAFC;padding:16px 32px;border-radius:0 0 12px 12px;border:1px solid #E2E8F0;border-top:none;text-align:center;font-size:12px;color:#94A3B8\">You received this because you are an affiliate on {{site_name}}.</div></div>',1)");
} catch (\Throwable $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// ── Create / Edit ─────────────────────────────────────────────────────────
if ($action === 'create' || ($action === 'edit' && isset($_GET['id']))) {
    $newsId   = $action === 'edit' ? (int)$_GET['id'] : 0;
    $newsItem = $newsId ? Database::fetchOne("SELECT * FROM news WHERE id=?", [$newsId]) : null;
    if ($newsId && !$newsItem) Helpers::redirect('/admin/news');

    $errors = [];
    $pageTitle = $newsId ? 'Edit News' : 'Create News';

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $title   = trim(Helpers::postRaw('title'));
        $summary = trim(Helpers::postRaw('summary'));
        $body    = Helpers::postRaw('body');
        $isHot   = isset($_POST['is_hot']) ? 1 : 0;
        $status  = in_array(Helpers::post('status'), ['published','draft']) ? Helpers::post('status') : 'draft';
        $slug    = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug    = trim($slug, '-') ?: 'news-' . time();

        if (!$title) $errors[] = 'Title is required.';

        // Image upload
        $image = $newsItem['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Image must be JPEG, PNG, GIF or WEBP.';
            } else {
                $dir = BASE_PATH . '/assets/uploads/news/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = 'news_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                    $image = '/assets/uploads/news/' . $fname;
                }
            }
        }

        if (!$errors) {
            $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

            // Make slug unique
            $slugCheck = Database::fetchOne(
                "SELECT id FROM news WHERE slug=?" . ($newsId ? " AND id!=?" : ''),
                $newsId ? [$slug, $newsId] : [$slug]
            );
            if ($slugCheck) $slug .= '-' . time();

            if ($newsId) {
                $wasPublished = $newsItem['status'] === 'published';
                Database::update('news', [
                    'title'        => $title,
                    'slug'         => $slug,
                    'summary'      => $summary,
                    'body'         => $body,
                    'image'        => $image,
                    'is_hot'       => $isHot,
                    'status'       => $status,
                    'published_at' => $publishedAt ?? $newsItem['published_at'],
                ], 'id=?', [$newsId]);

                // Send emails if newly published
                if (!$wasPublished && $status === 'published' && !$newsItem['email_sent']) {
                    _sendNewsEmail($newsId, $title, $summary, $image, $isHot);
                    Database::update('news', ['email_sent' => 1], 'id=?', [$newsId]);
                }
                Helpers::flash('success', 'News updated.');
            } else {
                $id = Database::insert('news', [
                    'title'        => $title,
                    'slug'         => $slug,
                    'summary'      => $summary,
                    'body'         => $body,
                    'image'        => $image,
                    'is_hot'       => $isHot,
                    'status'       => $status,
                    'email_sent'   => 0,
                    'created_by'   => Auth::id(),
                    'published_at' => $publishedAt,
                ]);
                if ($status === 'published') {
                    _sendNewsEmail($id, $title, $summary, $image, $isHot);
                    Database::update('news', ['email_sent' => 1], 'id=?', [$id]);
                    // Add in-app notification for all affiliates
                    $affiliateUsers = Database::fetchAll(
                        "SELECT u.id FROM users u WHERE u.role='affiliate' AND u.status='active'"
                    );
                    foreach ($affiliateUsers as $au) {
                        Database::insert('notifications', [
                            'user_id'     => $au['id'],
                            'target_role' => null,
                            'type'        => 'info',
                            'title'       => ($isHot ? '🔥 Hot News: ' : '📰 News: ') . $title,
                            'message'     => $summary ?: 'New post published. Click to read.',
                            'link'        => '/affiliate/news/' . $id,
                        ]);
                    }
                }
                Helpers::flash('success', 'News published!' . ($status === 'published' ? ' Email sent to all affiliates.' : ''));
            }
            Helpers::redirect('/admin/news');
        }
    }

    require BASE_PATH . '/views/admin/news/form.php';

// ── Delete ────────────────────────────────────────────────────────────────
} elseif ($action === 'delete' && isset($_GET['id'])) {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)$_GET['id'];
        Database::query("DELETE FROM news_reads WHERE news_id=?", [$id]);
        Database::query("DELETE FROM news WHERE id=?", [$id]);
        Helpers::flash('success', 'News deleted.');
    }
    Helpers::redirect('/admin/news');

// ── Index ─────────────────────────────────────────────────────────────────
} else {
    $pageTitle = 'News Management';
    $newsList  = Database::fetchAll(
        "SELECT n.*, CONCAT(u.first_name,' ',u.last_name) as author_name,
                (SELECT COUNT(*) FROM news_reads nr WHERE nr.news_id=n.id) as read_count
         FROM news n
         LEFT JOIN users u ON u.id=n.created_by
         ORDER BY n.created_at DESC"
    );
    require BASE_PATH . '/views/admin/news/index.php';
}

// ── Helper: send email blast ──────────────────────────────────────────────
function _sendNewsEmail(int $newsId, string $title, string $summary, ?string $image, int $isHot): void {
    $appUrl   = rtrim(Config::get('config','app.url') ?? '', '/');
    $siteName = Config::get('config','app.name') ?? 'AffiliateTracker';
    $link     = $appUrl . '/affiliate/news/' . $newsId;

    $affiliates = Database::fetchAll(
        "SELECT u.email, u.first_name, u.last_name
         FROM users u
         WHERE u.role='affiliate' AND u.status='active'
         LIMIT 2000"
    );

    foreach ($affiliates as $aff) {
        try {
            Mailer::sendEvent($aff['email'], $aff['first_name'].' '.$aff['last_name'], 'news_published', [
                'name'      => $aff['first_name'] . ' ' . $aff['last_name'],
                'title'     => $title,
                'summary'   => $summary ?: '',
                'image'     => $image ? $appUrl . $image : '',
                'link'      => $link,
                'is_hot'    => $isHot ? 'true' : 'false',
                'site_name' => $siteName,
                'app_url'   => $appUrl,
            ]);
        } catch (\Throwable $e) {}
    }
}
