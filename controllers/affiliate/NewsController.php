<?php
Auth::check('affiliate');
$userId = Auth::id();

// Auto-create tables (graceful)
try {
    Database::query("CREATE TABLE IF NOT EXISTS `news` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(500) NOT NULL, `slug` VARCHAR(520) NOT NULL,
        `summary` TEXT, `body` LONGTEXT, `image` VARCHAR(512) DEFAULT NULL,
        `is_hot` TINYINT(1) DEFAULT 0, `status` ENUM('published','draft') DEFAULT 'draft',
        `email_sent` TINYINT(1) DEFAULT 0, `created_by` INT UNSIGNED NULL,
        `published_at` DATETIME NULL, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_slug` (`slug`), INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    Database::query("CREATE TABLE IF NOT EXISTS `news_reads` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `news_id` INT UNSIGNED NOT NULL, `user_id` INT UNSIGNED NOT NULL,
        `read_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_news_user` (`news_id`,`user_id`),
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

// ── Mark all as read (AJAX) ───────────────────────────────────────────────
if (Helpers::get('action') === 'mark_all_read') {
    header('Content-Type: application/json');
    $items = Database::fetchAll("SELECT id FROM news WHERE status='published'");
    foreach ($items as $n) {
        try {
            Database::insert('news_reads', ['news_id' => $n['id'], 'user_id' => $userId]);
        } catch (\Throwable $e) {}
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── Unread count (AJAX) ───────────────────────────────────────────────────
if (Helpers::get('action') === 'unread_count') {
    header('Content-Type: application/json');
    $count = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM news n
         WHERE n.status='published'
           AND NOT EXISTS (SELECT 1 FROM news_reads nr WHERE nr.news_id=n.id AND nr.user_id=?)",
        [$userId]
    )['cnt'] ?? 0;
    echo json_encode(['unread' => (int)$count]);
    exit;
}

// ── Single article view ───────────────────────────────────────────────────
if (isset($_GET['id'])) {
    $newsId   = (int)$_GET['id'];
    $newsItem = Database::fetchOne(
        "SELECT n.*, CONCAT(u.first_name,' ',u.last_name) as author_name
         FROM news n LEFT JOIN users u ON u.id=n.created_by
         WHERE n.id=? AND n.status='published'",
        [$newsId]
    );
    if (!$newsItem) Helpers::redirect('/affiliate/news');

    // Mark as read
    try {
        Database::insert('news_reads', ['news_id' => $newsId, 'user_id' => $userId]);
    } catch (\Throwable $e) {}

    $pageTitle = Helpers::e($newsItem['title']);
    require BASE_PATH . '/views/affiliate/news/view.php';
    exit;
}

// ── News list ─────────────────────────────────────────────────────────────
$pageTitle = 'News';
$newsList  = Database::fetchAll(
    "SELECT n.*,
            (SELECT 1 FROM news_reads nr WHERE nr.news_id=n.id AND nr.user_id=?) as is_read
     FROM news n
     WHERE n.status='published'
     ORDER BY n.is_hot DESC, n.published_at DESC",
    [$userId]
);

require BASE_PATH . '/views/affiliate/news/index.php';
