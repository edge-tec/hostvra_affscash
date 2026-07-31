<?php
/**
 * SeoKeywordAjaxController — AJAX API Handler for SEO Keyword System
 *
 * Supports fast loading, CSRF validation, prepared statements, and
 * asynchronous tag saving & autocomplete suggestions.
 */
Auth::check('admin');

header('Content-Type: application/json; charset=utf-8');

$action = Helpers::get('action') ?? Helpers::post('action') ?? '';

if ($action === 'autocomplete') {
    $query = Helpers::get('q') ?? Helpers::post('q') ?? '';
    $suggestions = SeoKeywordModule::getSuggestions($query);
    echo json_encode(['success' => true, 'suggestions' => $suggestions]);
    exit;
}

// All mutating actions require CSRF token validation
if (!Auth::verifyCsrf(Helpers::postRaw('_token') ?? Helpers::get('_token') ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired security token (CSRF).']);
    exit;
}

if ($action === 'save_keywords') {
    $postId = (int)Helpers::post('post_id');
    $keywords = $_POST['keywords'] ?? [];

    if (!is_array($keywords)) {
        echo json_encode(['success' => false, 'error' => 'Invalid keywords payload structure.']);
        exit;
    }

    $res = SeoKeywordModule::saveKeywords($postId, $keywords);
    echo json_encode($res);
    exit;
}

if ($action === 'preview_seo') {
    $postId = (int)Helpers::post('post_id');
    $keywords = $_POST['keywords'] ?? [];
    
    $post = [];
    if ($postId > 0) {
        $post = Database::fetchOne("SELECT * FROM landing_posts WHERE id=?", [$postId]) ?: [];
    }
    if (empty($post['title'])) {
        $post['title']   = trim(Helpers::postRaw('title') ?? 'Sample Post Title');
        $post['excerpt'] = trim(Helpers::postRaw('excerpt') ?? '');
        $post['slug']    = trim(Helpers::postRaw('slug') ?? 'post-sample');
    }

    $cleanedByTypes = [];
    foreach (array_keys(SeoKeywordModule::getSupportedTypes()) as $type) {
        $cleanedByTypes[$type] = SeoKeywordModule::cleanKeywords($keywords[$type] ?? []);
    }

    $preview = SeoKeywordModule::autoGenerateSeoData($post, $cleanedByTypes);
    echo json_encode(['success' => true, 'preview' => $preview]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action requested.']);
exit;
