<?php
/**
 * Multi-Provider IP Score Proxy Controller
 * Route: GET /admin/ip-score-check?ip=X.X.X.X&provider=ipqualityscore
 *
 * Keeps all API keys server-side. Returns a normalised JSON response:
 *   { provider, score, risk_level, proxy_type, vpn, proxy, tor, bot, isp, org, country, details, success }
 */

if (!Auth::isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$ip       = trim(Helpers::get('ip') ?? '');
$provider = strtolower(trim(Helpers::get('provider') ?? ''));

// Validate IP (v4 and v6)
if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['error' => 'Invalid IP address', 'success' => false]);
    exit;
}

// Load fraud config for API keys
$fraudCfg = [];
try {
    $row = Database::fetchOne("SELECT value FROM settings WHERE `key`='fraud_config' LIMIT 1");
    if ($row) $fraudCfg = json_decode($row['value'], true) ?: [];
} catch (\Throwable $e) {}

// ──────────────────────────────────────────────────────────────────────────────
// Helper: cURL GET with timeout
// ──────────────────────────────────────────────────────────────────────────────
function _ipScoreCurl(string $url, array $headers = [], int $timeout = 8): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'AffsCash-FraudCheck/1.0',
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return ($raw !== false) ? $raw : null;
}

// ──────────────────────────────────────────────────────────────────────────────
// Normalised result builder
// ──────────────────────────────────────────────────────────────────────────────
function _buildResult(string $provider, int $score, string $riskLevel, array $extra = []): array {
    return array_merge([
        'provider'   => $provider,
        'score'      => $score,
        'risk_level' => $riskLevel,
        'proxy_type' => 'None',
        'vpn'        => false,
        'proxy'      => false,
        'tor'        => false,
        'bot'        => false,
        'isp'        => '—',
        'org'        => '—',
        'country'    => '—',
        'city'       => '—',
        'details'    => [],
        'success'    => true,
    ], $extra);
}

function _riskLevel(int $score): string {
    if ($score >= 75) return 'High Risk';
    if ($score >= 40) return 'Medium Risk';
    if ($score >= 15) return 'Low Risk';
    return 'Clean';
}

// ──────────────────────────────────────────────────────────────────────────────
// Provider implementations
// ──────────────────────────────────────────────────────────────────────────────

switch ($provider) {

    // ═══ 1. IPQualityScore ═══════════════════════════════════════════════════
    case 'ipqualityscore':
        $apiKey = trim($fraudCfg['ipqs_api_key'] ?? '');
        if (!$apiKey) {
            $s = Database::fetchOne("SELECT value FROM settings WHERE `key`='ipqs_api_key' LIMIT 1");
            $apiKey = trim($s['value'] ?? '');
        }
        if (!$apiKey) { echo json_encode(['error'=>'IPQS API key not configured. Go to Admin → Fraud Settings.','success'=>false]); exit; }

        $url = "https://www.ipqualityscore.com/api/json/ip/{$apiKey}/{$ip}?strictness=1&allow_public_access_points=true&fast=false&mobile=true";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach IPQualityScore API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d) || !($d['success'] ?? false)) { echo json_encode(['error'=>$d['message'] ?? 'Invalid IPQS response','success'=>false]); exit; }

        $score = (int)($d['fraud_score'] ?? 0);
        $proxyType = 'None';
        if ($d['tor'] ?? false) $proxyType = 'TOR';
        elseif ($d['vpn'] ?? false) $proxyType = 'VPN';
        elseif ($d['proxy'] ?? false) $proxyType = 'Proxy';
        elseif ($d['active_vpn'] ?? false) $proxyType = 'Active VPN';

        echo json_encode(_buildResult('IPQualityScore', $score, _riskLevel($score), [
            'proxy_type' => $proxyType,
            'vpn'        => (bool)($d['vpn'] ?? false),
            'proxy'      => (bool)($d['proxy'] ?? false),
            'tor'        => (bool)($d['tor'] ?? false),
            'bot'        => (bool)($d['bot_status'] ?? false),
            'isp'        => $d['ISP'] ?? '—',
            'org'        => $d['organization'] ?? '—',
            'country'    => $d['country_code'] ?? '—',
            'city'       => $d['city'] ?? '—',
            'details'    => [
                'recent_abuse'     => (bool)($d['recent_abuse'] ?? false),
                'mobile'           => (bool)($d['mobile'] ?? false),
                'connection_type'  => $d['connection_type'] ?? '—',
                'abuse_velocity'   => $d['abuse_velocity'] ?? '—',
                'host'             => $d['host'] ?? '—',
            ],
        ]));
        break;

    // ═══ 2. IPQuery.io (Free – no key required) ═════════════════════════════
    case 'ipquery':
        $url = "https://api.ipquery.io/{$ip}?format=json";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach IPQuery.io API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d)) { echo json_encode(['error'=>'Invalid IPQuery response','success'=>false]); exit; }

        $risk  = $d['risk'] ?? [];
        $isVpn = (bool)($risk['is_vpn'] ?? false);
        $isProxy = (bool)($risk['is_proxy'] ?? false);
        $isTor = (bool)($risk['is_tor'] ?? false);
        $isDatacenter = (bool)($risk['is_datacenter'] ?? false);
        $riskScore = (int)($risk['risk_score'] ?? 0);

        $proxyType = 'None';
        if ($isTor) $proxyType = 'TOR';
        elseif ($isVpn) $proxyType = 'VPN';
        elseif ($isProxy) $proxyType = 'Proxy';
        elseif ($isDatacenter) $proxyType = 'Datacenter';

        $loc = $d['location'] ?? [];
        $isp = $d['isp'] ?? [];

        echo json_encode(_buildResult('IPQuery.io', $riskScore, _riskLevel($riskScore), [
            'proxy_type' => $proxyType,
            'vpn'        => $isVpn,
            'proxy'      => $isProxy,
            'tor'        => $isTor,
            'bot'        => false,
            'isp'        => $isp['isp'] ?? '—',
            'org'        => $isp['org'] ?? '—',
            'country'    => $loc['country_code'] ?? ($loc['country'] ?? '—'),
            'city'       => $loc['city'] ?? '—',
            'details'    => [
                'datacenter' => $isDatacenter,
                'latitude'   => $loc['latitude'] ?? null,
                'longitude'  => $loc['longitude'] ?? null,
                'asn'        => $isp['asn'] ?? '—',
            ],
        ]));
        break;

    // ═══ 3. Scamalytics ═════════════════════════════════════════════════════
    case 'scamalytics':
        $apiKey = trim($fraudCfg['scamalytics_api_key'] ?? '');
        if (!$apiKey) { echo json_encode(['error'=>'Scamalytics API key not configured. Go to Admin → Fraud Settings.','success'=>false]); exit; }

        $url = "https://api11.scamalytics.com/affscash/?key={$apiKey}&ip={$ip}";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach Scamalytics API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d)) { echo json_encode(['error'=>'Invalid Scamalytics response','success'=>false]); exit; }

        $score = (int)($d['score'] ?? 0);
        $risk  = $d['risk'] ?? 'low';
        $proxyType = 'None';
        $pType = strtolower($d['proxy_type'] ?? '');
        if (str_contains($pType, 'tor'))  $proxyType = 'TOR';
        elseif (str_contains($pType, 'vpn')) $proxyType = 'VPN';
        elseif ($pType && $pType !== 'none') $proxyType = ucfirst($pType);

        echo json_encode(_buildResult('Scamalytics', $score, ucfirst($risk), [
            'proxy_type' => $proxyType,
            'vpn'        => str_contains($pType, 'vpn'),
            'proxy'      => ($pType && $pType !== 'none'),
            'tor'        => str_contains($pType, 'tor'),
            'bot'        => false,
            'isp'        => $d['isp_name'] ?? '—',
            'org'        => $d['organization_name'] ?? '—',
            'country'    => $d['ip_country_code'] ?? '—',
            'city'       => $d['ip_city'] ?? '—',
            'details'    => [
                'datacenter' => $d['operating_system'] ?? '—',
                'risk_word'  => $risk,
            ],
        ]));
        break;

    // ═══ 4. ProxyCheck.io ════════════════════════════════════════════════════
    case 'proxycheck':
        $apiKey = trim($fraudCfg['proxycheck_api_key'] ?? '');
        $keyPart = $apiKey ? "?key={$apiKey}&" : '?';
        $url = "https://proxycheck.io/v2/{$ip}{$keyPart}vpn=1&asn=1&risk=1&port=1&seen=1&days=7&tag=affscash";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach ProxyCheck.io API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d) || ($d['status'] ?? '') !== 'ok') { echo json_encode(['error'=>$d['message'] ?? 'Invalid ProxyCheck response','success'=>false]); exit; }

        $ipData = $d[$ip] ?? [];
        $score  = (int)($ipData['risk'] ?? 0);
        $isProxy = strtolower($ipData['proxy'] ?? 'no') === 'yes';
        $pType   = strtolower($ipData['type'] ?? '');
        $proxyType = 'None';
        if (str_contains($pType, 'tor')) $proxyType = 'TOR';
        elseif (str_contains($pType, 'vpn')) $proxyType = 'VPN';
        elseif ($isProxy) $proxyType = ucfirst($pType ?: 'Proxy');

        echo json_encode(_buildResult('ProxyCheck.io', $score, _riskLevel($score), [
            'proxy_type' => $proxyType,
            'vpn'        => str_contains($pType, 'vpn'),
            'proxy'      => $isProxy,
            'tor'        => str_contains($pType, 'tor'),
            'bot'        => false,
            'isp'        => $ipData['provider'] ?? '—',
            'org'        => $ipData['organisation'] ?? '—',
            'country'    => $ipData['country'] ?? '—',
            'city'       => $ipData['city'] ?? '—',
            'details'    => [
                'port'   => $ipData['port'] ?? '—',
                'seen'   => $ipData['last seen human'] ?? '—',
                'asn'    => $ipData['asn'] ?? '—',
            ],
        ]));
        break;

    // ═══ 5. BotScout (Free – checks bot DB) ═════════════════════════════════
    case 'botscout':
        $apiKey = trim($fraudCfg['botscout_api_key'] ?? '');
        $keyPart = $apiKey ? "&key={$apiKey}" : '';
        $url = "https://botscout.com/test/?ip={$ip}{$keyPart}&return=json";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach BotScout API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d)) {
            // BotScout sometimes returns pipe-delimited: Y|IP|5
            $parts = explode('|', trim($raw));
            if (count($parts) >= 3) {
                $matched = strtoupper($parts[0]) === 'Y';
                $count   = (int)$parts[2];
                $score   = min(100, $count * 10);
                echo json_encode(_buildResult('BotScout', $score, $matched ? 'High Risk' : 'Clean', [
                    'bot'     => $matched,
                    'details' => ['matched' => $matched, 'count' => $count],
                ]));
                exit;
            }
            echo json_encode(['error'=>'Invalid BotScout response','success'=>false]);
            exit;
        }

        $matched = strtoupper($d['matched'] ?? '') === 'Y';
        $count   = (int)($d['count'] ?? 0);
        $score   = min(100, $count * 10);
        echo json_encode(_buildResult('BotScout', $score, $matched ? 'High Risk' : 'Clean', [
            'bot'     => $matched,
            'details' => ['matched' => $matched, 'count' => $count, 'test' => $d['test'] ?? '—'],
        ]));
        break;

    // ═══ 6. FraudDefense.io (Free basic tier) ═══════════════════════════════
    case 'frauddefense':
        $url = "https://api.frauddefense.io/v1/ip/{$ip}";
        $apiKey = trim($fraudCfg['frauddefense_api_key'] ?? '');
        $headers = $apiKey ? ["Authorization: Bearer {$apiKey}"] : [];
        $raw = _ipScoreCurl($url, $headers);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach FraudDefense.io API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d)) { echo json_encode(['error'=>'Invalid FraudDefense response','success'=>false]); exit; }

        $score = (int)($d['risk_score'] ?? $d['fraud_score'] ?? 0);
        $isVpn = (bool)($d['is_vpn'] ?? false);
        $isProxy = (bool)($d['is_proxy'] ?? false);
        $isTor = (bool)($d['is_tor'] ?? false);
        $proxyType = 'None';
        if ($isTor) $proxyType = 'TOR';
        elseif ($isVpn) $proxyType = 'VPN';
        elseif ($isProxy) $proxyType = 'Proxy';

        echo json_encode(_buildResult('FraudDefense.io', $score, _riskLevel($score), [
            'proxy_type' => $proxyType,
            'vpn'        => $isVpn,
            'proxy'      => $isProxy,
            'tor'        => $isTor,
            'bot'        => (bool)($d['is_bot'] ?? false),
            'isp'        => $d['isp'] ?? '—',
            'org'        => $d['organization'] ?? '—',
            'country'    => $d['country_code'] ?? '—',
            'city'       => $d['city'] ?? '—',
            'details'    => $d,
        ]));
        break;

    // ═══ 7. FraudLabs Pro ═══════════════════════════════════════════════════
    case 'fraudlabspro':
        $apiKey = trim($fraudCfg['fraudlabspro_api_key'] ?? '');
        if (!$apiKey) { echo json_encode(['error'=>'FraudLabs Pro API key not configured. Go to Admin → Fraud Settings.','success'=>false]); exit; }

        $url = "https://api.fraudlabspro.com/v2/ip-address/lookup?key={$apiKey}&ip={$ip}&format=json";
        $raw = _ipScoreCurl($url);
        if (!$raw) { echo json_encode(['error'=>'Failed to reach FraudLabs Pro API','success'=>false]); exit; }

        $d = json_decode($raw, true);
        if (!is_array($d)) { echo json_encode(['error'=>'Invalid FraudLabs Pro response','success'=>false]); exit; }

        $isProxy = strtolower($d['is_proxy'] ?? '') === 'yes' || strtolower($d['is_proxy'] ?? '') === 'true' || $d['is_proxy'] === true;
        $usage = strtolower($d['usage_type'] ?? '');
        $proxyType = 'None';
        if (str_contains($usage, 'tor')) $proxyType = 'TOR';
        elseif (str_contains($usage, 'vpn') || str_contains($usage, 'hosting')) $proxyType = 'VPN / Hosting';
        elseif ($isProxy) $proxyType = 'Proxy';

        $score = $isProxy ? 75 : 0;

        echo json_encode(_buildResult('FraudLabs Pro', $score, $isProxy ? 'High Risk' : 'Clean', [
            'proxy_type' => $proxyType,
            'vpn'        => str_contains($usage, 'vpn'),
            'proxy'      => $isProxy,
            'tor'        => str_contains($usage, 'tor'),
            'bot'        => false,
            'isp'        => $d['isp'] ?? '—',
            'org'        => $d['domain'] ?? '—',
            'country'    => $d['country_code'] ?? '—',
            'city'       => $d['city'] ?? '—',
            'details'    => [
                'usage_type'  => $d['usage_type'] ?? '—',
                'domain'      => $d['domain'] ?? '—',
                'net_speed'   => $d['net_speed'] ?? '—',
                'elevation'   => $d['elevation'] ?? '—',
            ],
        ]));
        break;

    default:
        echo json_encode(['error' => "Unknown provider: {$provider}", 'success' => false]);
        break;
}
