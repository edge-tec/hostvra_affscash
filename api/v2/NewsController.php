<?php
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden.']));
}
Auth::check('affiliate');
$userId = Auth::id();

$action = Helpers::get('action', 'list');

if ($action === 'list') {
    $newsList = Database::fetchAll(
        "SELECT n.*,
                (SELECT 1 FROM news_reads nr WHERE nr.news_id=n.id AND nr.user_id=?) as is_read
         FROM news n
         WHERE n.status='published'
         ORDER BY n.is_hot DESC, n.published_at DESC",
        [$userId]
    );

    // Format output
    $formatted = [];
    foreach ($newsList as $n) {
        $formatted[] = [
            'id' => (int)$n['id'],
            'title' => $n['title'],
            'summary' => $n['summary'],
            'body' => $n['body'],
            'image' => $n['image'],
            'is_hot' => (bool)$n['is_hot'],
            'is_read' => (bool)$n['is_read'],
            'published_at' => $n['published_at']
        ];
    }

    echo json_encode(['success' => true, 'news' => $formatted]);
    exit;
}

if ($action === 'mark_read') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $newsId = (int)($data['news_id'] ?? 0);
    if ($newsId > 0) {
        try {
            Database::insert('news_reads', ['news_id' => $newsId, 'user_id' => $userId]);
        } catch (\Throwable $e) {} // Ignore unique constraint violations
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid news ID']);
    }
    exit;
}

if ($action === 'mark_all_read') {
    $items = Database::fetchAll("SELECT id FROM news WHERE status='published'");
    foreach ($items as $n) {
        try {
            Database::insert('news_reads', ['news_id' => $n['id'], 'user_id' => $userId]);
        } catch (\Throwable $e) {}
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
