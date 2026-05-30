<?php
Auth::check('affiliate');

// Only accept POST + JSON response
header('Content-Type: application/json');

if (!Helpers::isPost()) {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$url = trim(Helpers::postRaw('url'));
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL']); exit;
}

// Read API key from config (admin-configurable), fallback to default
$apiKey = Config::get('config', 'shortener.api_key') ?: 'nLxSNqjvdIsejHwodGpCDDirzbCeBJdu';

// Call shroo.link API
$ch = curl_init('https://shroo.link/api/url/add');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode(['url' => $url, 'type' => 'direct']),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['error' => 'Connection failed: ' . $curlError]); exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo json_encode(['error' => 'Invalid response from shortener']); exit;
}

if (!empty($data['error'])) {
    $msg = is_string($data['error']) ? $data['error'] : ($data['message'] ?? 'Shortener error');
    echo json_encode(['error' => $msg]); exit;
}

if (empty($data['shorturl'])) {
    echo json_encode(['error' => 'No short URL returned']); exit;
}

echo json_encode(['short_url' => $data['shorturl'], 'id' => $data['id'] ?? null]);
