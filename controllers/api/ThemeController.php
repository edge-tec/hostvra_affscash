<?php
/**
 * POST /api/theme — save the current user's theme preference.
 *
 * Body (urlencoded):
 *   theme  = light | dark | system
 *   _token = CSRF token
 *
 * Response: { ok: bool, theme: string, error?: string }
 *
 * Requires an authenticated session. The preference is stored on the user
 * row and overrides the admin's app.default_theme for that account from
 * the very next request — no logout/login needed.
 */
header('Content-Type: application/json; charset=utf-8');

$uid = Auth::id();
if (!$uid) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$token = Helpers::postRaw('_token');
if ($token === '' || !Auth::verifyCsrf($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$theme = strtolower(trim((string)Helpers::postRaw('theme')));
if (!in_array($theme, Theme::VALID, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid theme value']);
    exit;
}

$ok = Theme::setUserPreference((int)$uid, $theme);
echo json_encode(['ok' => $ok, 'theme' => $theme]);
