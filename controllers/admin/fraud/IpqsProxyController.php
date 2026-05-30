<?php
/**
 * IPQualityScore Proxy Controller
 * Route: GET /admin/fraud/ipqs-proxy?ip=X.X.X.X
 *        GET /affiliate_manager/fraud/ipqs-proxy?ip=X.X.X.X
 *
 * Keeps the IPQS API key server-side and avoids CORS issues.
 * Returns JSON with the IPQS fraud score for the given IP.
 */

// Auth: allow both admin and affiliate_manager roles
if (!Auth::isAdmin() && !Auth::isAffiliateManager()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$ip = trim(Helpers::get('ip') ?? '');

// Validate IP
if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    echo json_encode(['error' => 'Invalid IP address', 'fraud_score' => 0]);
    exit;
}

// Get API key from settings
$apiKey = '';
try {
    $setting = Database::fetchOne("SELECT value FROM settings WHERE `key`='ipqs_api_key' LIMIT 1");
    $apiKey  = trim($setting['value'] ?? '');
} catch (\Throwable $e) {}

// Also check defined constant
if (empty($apiKey) && defined('IPQS_API_KEY')) {
    $apiKey = IPQS_API_KEY;
}

if (empty($apiKey)) {
    // Return demo/simulated result so UI still functions
    $seed  = array_sum(array_map('intval', explode('.', $ip)));
    $score = ($seed * 37 + 13) % 101;
    echo json_encode([
        'fraud_score'  => $score,
        'vpn'          => $score > 70,
        'proxy'        => $score > 65,
        'tor'          => $score > 90,
        'bot_status'   => $score > 80,
        'country_code' => ['US','DE','RU','CN','BR','IN','FR','GB'][$seed % 8],
        'ISP'          => ['Cloudflare','Amazon AWS','DigitalOcean','Google LLC','Comcast'][$seed % 5],
        'organization' => 'Demo ISP',
        'message'      => 'Demo mode — configure IPQS API key in Settings → Fraud → IPQS',
        'success'      => true,
        '_demo'        => true,
    ]);
    exit;
}

// Simple rate-limit: max 120 IPQS calls per minute (per server)
$cacheKey = 'ipqs_rate_' . date('YmdHi');
$callCount = (int)(apcu_fetch($cacheKey) ?: 0);
if ($callCount > 120) {
    echo json_encode(['error' => 'Rate limit reached (120/min). Try again shortly.', 'fraud_score' => 0]);
    exit;
}
apcu_store($cacheKey, $callCount + 1, 65);

// Cache individual IP results for 60 minutes
$resultCacheKey = 'ipqs_ip_' . md5($ip . $apiKey);
if ($cached = apcu_fetch($resultCacheKey)) {
    echo $cached;
    exit;
}

// Build IPQS request
$url = sprintf(
    'https://www.ipqualityscore.com/api/json/ip/%s/%s?strictness=1&allow_public_access_points=true&fast=false&lighter_penalties=false&mobile=true',
    urlencode($apiKey),
    urlencode($ip)
);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 6,
        'header'  => "User-Agent: PHP/IPQS-Proxy\r\n",
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$raw = @file_get_contents($url, false, $ctx);
if ($raw === false) {
    // Try cURL fallback
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'PHP/IPQS-Proxy',
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    }
}

if (!$raw) {
    echo json_encode(['error' => 'Failed to reach IPQualityScore API', 'fraud_score' => 0]);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid response from IPQS', 'fraud_score' => 0]);
    exit;
}

// Cache for 60 min
apcu_store($resultCacheKey, json_encode($data), 3600);

echo json_encode($data);
