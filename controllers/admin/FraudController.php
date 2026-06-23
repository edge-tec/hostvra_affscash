<?php
Auth::check('admin');
$pageTitle = 'Fraud Detector';

// Auto-create fraud.json with safe defaults if it doesn't exist yet.
$_fraudCfgPath = BASE_PATH . '/config/fraud.json';
if (!file_exists($_fraudCfgPath)) {
    $dir = dirname($_fraudCfgPath);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($_fraudCfgPath, json_encode([
        'mode'                    => 'score_only',
        // When admin flips `mode` to 'block', this gate is honoured but
        // defaults to TRUE so Block Mode actually blocks without an
        // additional opt-in step.
        'check_on_conversion'     => true,
        'ipquery_enabled'         => true,
        'ipquery_mode'            => 'score_only',
        'ipquery_block_threshold' => 80,
        'ipquery_flag_threshold'  => 50,
        'ipqs_enabled'            => false,
        'ipqs_api_key'            => '',
        'scamalytics_enabled'     => false,
        'scamalytics_api_key'     => '',
        'proxycheck_enabled'      => false,
        'proxycheck_api_key'      => '',
        'botscout_enabled'        => false,
        'botscout_api_key'        => '',
        'frauddefense_enabled'    => false,
        'frauddefense_api_key'    => '',
        'fraudlabspro_enabled'    => false,
        'fraudlabspro_api_key'    => '',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

$fraudCfg  = Config::get('fraud') ?? [];

// AJAX: Test IPQS API connection
if (Helpers::isPost() && Helpers::post('action') === 'test_ipqs') {
    header('Content-Type: application/json');
    $apiKey = trim(Helpers::postRaw('ipqs_api_key') ?: ($fraudCfg['ipqs_api_key'] ?? ''));
    $result = FraudIQ::testConnection($apiKey);
    echo json_encode($result);
    exit;
}

// AJAX: Test IPQuery.io connection (no API key required)
if (Helpers::isPost() && Helpers::post('action') === 'test_ipquery') {
    header('Content-Type: application/json');
    echo json_encode(FraudIQ::testIPQuery());
    exit;
}

// AJAX: Bulk IP check via IPQuery.io batch API
// POST body: JSON {"ips":["1.2.3.4","5.6.7.8",...]}  (max 100 IPs)
if (Helpers::isPost() && Helpers::post('action') === 'ipquery_bulk_check') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $ips  = is_array($body['ips'] ?? null) ? $body['ips'] : [];
    if (empty($ips)) {
        echo json_encode(['ok' => false, 'message' => 'No IPs provided.']);
        exit;
    }
    if (count($ips) > 100) {
        echo json_encode(['ok' => false, 'message' => 'Maximum 100 IPs per batch request.']);
        exit;
    }
    require_once BASE_PATH . '/core/FraudIQ.php';
    $results = FraudIQ::checkIPQueryBatch($ips);
    echo json_encode(['ok' => true, 'count' => count($results), 'results' => $results]);
    exit;
}

// AJAX: Test Scamalytics connection
if (Helpers::isPost() && Helpers::post('action') === 'test_scamalytics') {
    header('Content-Type: application/json');
    $user   = trim(Helpers::postRaw('scamalytics_user')    ?: ($fraudCfg['scamalytics_user']    ?? ''));
    $server = trim(Helpers::postRaw('scamalytics_server')  ?: ($fraudCfg['scamalytics_server']  ?? ''));
    $apiKey = trim(Helpers::postRaw('scamalytics_api_key') ?: ($fraudCfg['scamalytics_api_key'] ?? ''));
    echo json_encode(FraudIQ::testScamalytics($user, $server, $apiKey));
    exit;
}

// AJAX: Test ProxyCheck.io connection
if (Helpers::isPost() && Helpers::post('action') === 'test_proxycheck') {
    header('Content-Type: application/json');
    $apiKey = trim(Helpers::postRaw('proxycheck_api_key') ?: ($fraudCfg['proxycheck_api_key'] ?? ''));
    echo json_encode(FraudIQ::testProxyCheck($apiKey));
    exit;
}

// AJAX: Test BotScout connection
if (Helpers::isPost() && Helpers::post('action') === 'test_botscout') {
    header('Content-Type: application/json');
    $apiKey = trim(Helpers::postRaw('botscout_api_key') ?: ($fraudCfg['botscout_api_key'] ?? ''));
    echo json_encode(FraudIQ::testBotScout($apiKey));
    exit;
}

// AJAX: Test FraudDefense.io connection
if (Helpers::isPost() && Helpers::post('action') === 'test_frauddefense') {
    header('Content-Type: application/json');
    $apiKey = trim(Helpers::postRaw('frauddefense_api_key') ?: ($fraudCfg['frauddefense_api_key'] ?? ''));
    echo json_encode(FraudIQ::testFraudDefense($apiKey));
    exit;
}

// AJAX: Test FraudLabs Pro connection
if (Helpers::isPost() && Helpers::post('action') === 'test_fraudlabspro') {
    header('Content-Type: application/json');
    $apiKey = trim(Helpers::postRaw('fraudlabspro_api_key') ?: ($fraudCfg['fraudlabspro_api_key'] ?? ''));
    echo json_encode(FraudIQ::testFraudLabsPro($apiKey));
    exit;
}

// Save fraud settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_settings') {
    $existingKey = ($fraudCfg['ipqs_api_key'] ?? '');
    $submittedKey = Helpers::postRaw('ipqs_api_key');
    // Preserve existing key if submitted field is empty (masked in UI)
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    // Merge IPQS fields into existing config — do NOT replace the entire array.
    // Replacing $fraudCfg would wipe IPQuery, FraudDefense, Scamalytics, etc. settings.
    $fraudCfg['mode']                = in_array(Helpers::postRaw('fraud_mode'), ['block','score_only']) ? Helpers::postRaw('fraud_mode') : 'block';
    $fraudCfg['ipqs_enabled']        = isset($_POST['ipqs_enabled']);
    $fraudCfg['ipqs_api_key']        = $apiKey;
    $fraudCfg['check_on_conversion'] = isset($_POST['check_on_conversion']);
    $fraudCfg['timeout_seconds']     = max(10, (int)($_POST['timeout_seconds'] ?? 10));
    $fraudCfg['fail_open']           = isset($_POST['fail_open']);
    $fraudCfg['score_threshold']     = (int)($_POST['score_threshold'] ?? 75);
    $fraudCfg['block_threshold']     = (int)($_POST['block_threshold'] ?? 90);
    $fraudCfg['checks']              = [
        'vpn'        => isset($_POST['check_vpn']),
        'proxy'      => isset($_POST['check_proxy']),
        'tor'        => isset($_POST['check_tor']),
        'bot'        => isset($_POST['check_bot']),
        'datacenter' => isset($_POST['check_datacenter']),
    ];
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'IPQualityScore settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save IPQuery.io settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_ipquery_settings') {
    $fraudCfg['ipquery_enabled']         = isset($_POST['ipquery_enabled']);
    $fraudCfg['ipquery_mode']            = in_array(Helpers::postRaw('ipquery_mode'), ['score_only','auto_block'])
                                               ? Helpers::postRaw('ipquery_mode') : 'score_only';
    $fraudCfg['ipquery_block_threshold'] = min(100, max(1, (int)($_POST['ipquery_block_threshold'] ?? 80)));
    $fraudCfg['ipquery_flag_threshold']  = min(100, max(1, (int)($_POST['ipquery_flag_threshold']  ?? 50)));
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'IPQuery.io settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save ProxyCheck.io settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_proxycheck_settings') {
    $existingKey  = $fraudCfg['proxycheck_api_key'] ?? '';
    $submittedKey = Helpers::postRaw('proxycheck_api_key');
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    $fraudCfg['proxycheck_api_key']         = $apiKey;
    $fraudCfg['proxycheck_enabled']         = isset($_POST['proxycheck_enabled']);
    $fraudCfg['proxycheck_mode']            = in_array(Helpers::postRaw('proxycheck_mode'), ['score_only','auto_block'])
                                                  ? Helpers::postRaw('proxycheck_mode') : 'score_only';
    $fraudCfg['proxycheck_block_threshold'] = min(100, max(1, (int)($_POST['proxycheck_block_threshold'] ?? 50)));
    $fraudCfg['proxycheck_flag_threshold']  = min(100, max(1, (int)($_POST['proxycheck_flag_threshold']  ?? 30)));
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'ProxyCheck.io settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save BotScout settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_botscout_settings') {
    $existingKey  = $fraudCfg['botscout_api_key'] ?? '';
    $submittedKey = Helpers::postRaw('botscout_api_key');
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    $fraudCfg['botscout_api_key'] = $apiKey;
    $fraudCfg['botscout_enabled'] = isset($_POST['botscout_enabled']);
    $fraudCfg['botscout_mode']    = in_array(Helpers::postRaw('botscout_mode'), ['score_only','auto_block'])
                                        ? Helpers::postRaw('botscout_mode') : 'score_only';
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'BotScout settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save FraudDefense.io settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_frauddefense_settings') {
    $existingKey  = $fraudCfg['frauddefense_api_key'] ?? '';
    $submittedKey = Helpers::postRaw('frauddefense_api_key');
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    $fraudCfg['frauddefense_api_key']         = $apiKey;
    $fraudCfg['frauddefense_enabled']         = isset($_POST['frauddefense_enabled']);
    $fraudCfg['frauddefense_mode']            = in_array(Helpers::postRaw('frauddefense_mode'), ['score_only','auto_block'])
                                                    ? Helpers::postRaw('frauddefense_mode') : 'score_only';
    $fraudCfg['frauddefense_block_threshold'] = min(100, max(1, (int)($_POST['frauddefense_block_threshold'] ?? 50)));
    $fraudCfg['frauddefense_flag_threshold']  = min(100, max(1, (int)($_POST['frauddefense_flag_threshold']  ?? 30)));
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'FraudDefense.io settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save FraudLabs Pro settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_fraudlabspro_settings') {
    $existingKey  = $fraudCfg['fraudlabspro_api_key'] ?? '';
    $submittedKey = Helpers::postRaw('fraudlabspro_api_key');
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    $fraudCfg['fraudlabspro_api_key']         = $apiKey;
    $fraudCfg['fraudlabspro_enabled']         = isset($_POST['fraudlabspro_enabled']);
    $fraudCfg['fraudlabspro_mode']            = in_array(Helpers::postRaw('fraudlabspro_mode'), ['score_only','auto_block'])
                                                    ? Helpers::postRaw('fraudlabspro_mode') : 'score_only';
    $fraudCfg['fraudlabspro_block_threshold'] = min(100, max(1, (int)($_POST['fraudlabspro_block_threshold'] ?? 75)));
    $fraudCfg['fraudlabspro_flag_threshold']  = min(100, max(1, (int)($_POST['fraudlabspro_flag_threshold']  ?? 40)));
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'FraudLabs Pro settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Save Scamalytics settings
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'save_scamalytics_settings') {
    $existingKey  = $fraudCfg['scamalytics_api_key'] ?? '';
    $submittedKey = Helpers::postRaw('scamalytics_api_key');
    $apiKey = ($submittedKey !== '' && $submittedKey !== '••••••••') ? $submittedKey : $existingKey;

    $fraudCfg['scamalytics_user']            = trim(Helpers::postRaw('scamalytics_user')   ?? '');
    $fraudCfg['scamalytics_server']          = preg_replace('/[^0-9]/', '', Helpers::postRaw('scamalytics_server') ?? '');
    $fraudCfg['scamalytics_api_key']         = $apiKey;
    $fraudCfg['scamalytics_enabled']         = isset($_POST['scamalytics_enabled']);
    $fraudCfg['scamalytics_mode']            = in_array(Helpers::postRaw('scamalytics_mode'), ['score_only','auto_block'])
                                                   ? Helpers::postRaw('scamalytics_mode') : 'score_only';
    $fraudCfg['scamalytics_block_threshold'] = min(100, max(1, (int)($_POST['scamalytics_block_threshold'] ?? 80)));
    $fraudCfg['scamalytics_flag_threshold']  = min(100, max(1, (int)($_POST['scamalytics_flag_threshold']  ?? 50)));
    $_cfgOk = Config::write('fraud', $fraudCfg);
    Config::clearCache();
    Helpers::flash($_cfgOk ? 'success' : 'error', $_cfgOk ? 'Scamalytics settings saved.' : 'Save failed: ' . Config::$lastError);
    Helpers::redirect('/admin/fraud');
}

// Manual action on a click
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'mark_click') {
    $clickId   = Helpers::postRaw('click_id');
    $newStatus = Helpers::post('click_status');
    if (in_array($newStatus, ['valid','fraud','blocked'])) {
        Database::update('clicks', ['status' => $newStatus, 'is_fraud' => $newStatus !== 'valid' ? 1 : 0], 'click_id=?', [$clickId]);
        Helpers::flash('success', 'Click status updated.');
    }
    Helpers::redirect('/admin/fraud');
}

// Manual approve / reject conversion from Score-Only panel
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'update_conv_status') {
    $convId    = Helpers::postRaw('conversion_id');
    $newStatus = Helpers::post('conv_status');
    if (in_array($newStatus, ['approved', 'rejected'])) {
        $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($conv) {
            $oldStatus    = $conv['status'];
            $rejectReason = trim((string)Helpers::postRaw('rejection_reason'));
            $payload      = RejectionHelper::buildUpdatePayload($newStatus, $rejectReason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            // Notify affiliate when transitioning into a rejected-style state.
            if ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
                try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
            }
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                Database::query("UPDATE affiliates SET balance = balance + ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
            } elseif ($newStatus === 'rejected' && $oldStatus === 'approved') {
                Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
            }
            Helpers::flash('success', 'Conversion ' . $newStatus . ' successfully.');
        }
    }
    Helpers::redirect('/admin/fraud');
}

$fraudMode = $fraudCfg['mode'] ?? 'block';

// Stats
$today = date('Y-m-d');
$todayFraud = Database::fetchOne("SELECT COUNT(*) as cnt, SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked FROM clicks WHERE DATE(clicked_at)=? AND is_fraud=1", [$today]);
$totalFraud = Database::count('clicks', 'is_fraud=1');
$topFraudIPs = Database::fetchAll("SELECT ip_address, COUNT(*) as cnt, MAX(fraud_score) as max_score FROM fraud_logs GROUP BY ip_address ORDER BY cnt DESC LIMIT 10");

$fraudClicks = Database::fetchAll(
    "SELECT c.click_id, c.ip_address, c.country, c.device_type, c.os, c.browser, c.isp, c.fraud_score, c.status, c.clicked_at, o.name as offer_name, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM clicks c JOIN offers o ON o.id=c.offer_id JOIN affiliates af ON af.id=c.affiliate_id JOIN users u ON u.id=af.user_id
     WHERE c.is_fraud=1 ORDER BY c.clicked_at DESC LIMIT 5000"
);

// Score-Only: load all conversions with fraud scores for the monitoring panel
$scoredConversions = [];
if ($fraudMode === 'score_only') {
    try {
        $scoredConversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, cv.offer_id,
                    cv.ip_address, COALESCE(cv.fraud_score, 0) as fraud_score,
                    cv.status, cv.payout, cv.converted_at,
                    o.name as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name,
                    af.affiliate_code
             FROM conversions cv
             JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE COALESCE(cv.is_hidden, 0) = 0
             ORDER BY cv.converted_at DESC
             LIMIT 1000"
        );
    } catch (\Throwable $e) {}
}

// IPQuery report: all conversions that have been scored by IPQuery.io
$ipqueryReport = [];
try {
    $ipqueryReport = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, cv.offer_id,
                cv.ip_address,
                cv.ipquery_risk_score, cv.ipquery_risk_level,
                cv.ipquery_vpn, cv.ipquery_proxy, cv.ipquery_tor,
                cv.ipquery_datacenter, cv.ipquery_mobile,
                cv.ipquery_country, cv.ipquery_country_code, cv.ipquery_city,
                cv.ipquery_isp, cv.ipquery_org, cv.ipquery_asn,
                cv.status, cv.payout, cv.converted_at,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         JOIN affiliates af ON af.id = cv.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE cv.ipquery_risk_score IS NOT NULL
           AND COALESCE(cv.is_hidden, 0) = 0
         ORDER BY cv.ipquery_risk_score DESC, cv.converted_at DESC
         LIMIT 5000"
    );
} catch (\Throwable $e) { $ipqueryReport = []; }

// FraudDefense.io report: conversions that have been scored by FraudDefense
$fraudDefenseReport = [];
try {
    $fraudDefenseReport = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, cv.offer_id,
                cv.ip_address,
                cv.frauddefense_score, cv.frauddefense_status,
                cv.status, cv.payout, cv.converted_at,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         JOIN affiliates af ON af.id = cv.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE cv.frauddefense_score IS NOT NULL
           AND COALESCE(cv.is_hidden, 0) = 0
         ORDER BY cv.frauddefense_score DESC, cv.converted_at DESC
         LIMIT 2000"
    );
} catch (\Throwable $e) { $fraudDefenseReport = []; }

// FraudLabs Pro report: conversions scored by FraudLabs Pro
$fraudLabsProReport = [];
try {
    $fraudLabsProReport = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, cv.offer_id,
                cv.ip_address,
                cv.fraudlabspro_score, cv.fraudlabspro_status, cv.fraudlabspro_flp_status,
                cv.status, cv.payout, cv.converted_at,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                af.affiliate_code
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         JOIN affiliates af ON af.id = cv.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE cv.fraudlabspro_score IS NOT NULL
           AND COALESCE(cv.is_hidden, 0) = 0
         ORDER BY cv.fraudlabspro_score DESC, cv.converted_at DESC
         LIMIT 5000"
    );
} catch (\Throwable $e) { $fraudLabsProReport = []; }

// Fraud clicks CSV export
if (Helpers::get('export') === 'csv') {
    $filename = 'fraud_clicks_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Click ID','IP Address','Country','Device','OS','Browser','ISP','Fraud Score','Status','Offer','Affiliate','Aff Code','Clicked At']);
    foreach ($fraudClicks as $c) {
        fputcsv($f, [$c['click_id'],$c['ip_address'],$c['country'],$c['device_type'],$c['os'],$c['browser'],$c['isp'],$c['fraud_score'],$c['status'],$c['offer_name'],$c['aff_name'],$c['affiliate_code'],$c['clicked_at']]);
    }
    fclose($f);
    exit;
}

// Score-Only conversion scores CSV export
if (Helpers::get('export') === 'conversion_csv') {
    try {
        $allScored = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, af.affiliate_code,
                    cv.offer_id, o.name as offer_name,
                    cv.ip_address, COALESCE(cv.fraud_score, 0) as fraud_score,
                    cv.status, cv.payout, cv.converted_at,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name
             FROM conversions cv
             JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE COALESCE(cv.is_hidden, 0) = 0
             ORDER BY cv.converted_at DESC
             LIMIT 5000"
        );
    } catch (\Throwable $e) { $allScored = []; }
    $filename = 'fraud_score_conversions_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Conversion ID','Click ID','Affiliate ID','Affiliate','Aff Code','Offer ID','Offer','IP Address','Fraud Score','Status','Payout','Converted At']);
    foreach ($allScored as $c) {
        fputcsv($f, [
            $c['conversion_id'], $c['click_id'], $c['affiliate_id'], $c['aff_name'], $c['affiliate_code'],
            $c['offer_id'], $c['offer_name'], $c['ip_address'],
            (int)$c['fraud_score'], $c['status'], $c['payout'], $c['converted_at'],
        ]);
    }
    fclose($f);
    exit;
}

require BASE_PATH . '/views/admin/fraud/index.php';
