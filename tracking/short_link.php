<?php
/**
 * In-House Short Link Redirect
 * URL: /s/{code}
 *
 * Resolves a short code to its full tracking URL and redirects.
 * All query parameters appended to /s/{code}?key=val are merged
 * into the destination URL before redirecting, preserving click_id,
 * sub_id, etc.
 */
require_once dirname(__DIR__) . '/core/TrackingBootstrap.php';

$code = trim($_GET['code'] ?? '');
if (!$code) {
    http_response_code(404);
    echo 'Invalid short link.';
    exit;
}

try {
    $link = Database::fetchOne(
        "SELECT * FROM inhouse_short_links WHERE code=? AND status='active' LIMIT 1",
        [$code]
    );
} catch (\Throwable $e) {
    // Table may not exist yet — redirect to home
    header('Location: /');
    exit;
}

if (!$link) {
    http_response_code(404);
    echo 'Short link not found or expired.';
    exit;
}

// Track the click on the short link itself
try {
    Database::query(
        "UPDATE inhouse_short_links SET click_count = click_count + 1, last_clicked_at = NOW() WHERE id = ?",
        [$link['id']]
    );
} catch (\Throwable $e) {}

// Build destination URL — merge any extra params from this request
$destination = $link['destination_url'];

// Collect extra GET params (excluding 'code' which is routing)
$extra = $_GET;
unset($extra['code']);

if (!empty($extra)) {
    $separator = str_contains($destination, '?') ? '&' : '?';
    $destination .= $separator . http_build_query($extra);
}

header('Location: ' . $destination, true, 302);
exit;
