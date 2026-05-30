<?php
header('Content-Type: application/json');

// API key auth
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = str_replace('Bearer ', '', $apiKey);
$configKey = Config::get('config', 'shortener.api_key') ?: 'nLxSNqjvdIsejHwodGpCDDirzbCeBJdu';

if (!$apiKey || $apiKey !== $configKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// POST /api/shorten - shorten a URL
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $url = trim($body['url'] ?? $_POST['url'] ?? '');

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }

    $ch = curl_init('https://shroo.link/api/url/add');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $configKey, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['url' => $url, 'type' => 'direct']),
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($data && isset($data['shorturl'])) {
        echo json_encode(['success' => true, 'short_url' => $data['shorturl']]);
    } else {
        http_response_code(502);
        echo json_encode(['error' => 'Shortener service unavailable']);
    }
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
