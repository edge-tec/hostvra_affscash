<?php
Auth::check('admin');
$pageTitle = 'Settings';

require_once BASE_PATH . '/core/DomainManager.php';
DomainManager::ensureColumns();

// ── AJAX: Check DNS for a single domain ──────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'check_domain_dns') {
    header('Content-Type: application/json');
    $domain = strtolower(trim(Helpers::postRaw('domain')));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');
    if (!$domain) { echo json_encode(['ok' => false, 'message' => 'No domain provided']); exit; }
    $result = DomainManager::checkDomain($domain);
    echo json_encode($result);
    exit;
}

// ── AJAX: Check DNS for all domains ──────────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'check_all_domains') {
    header('Content-Type: application/json');
    $domains = Database::fetchAll("SELECT domain FROM tracking_domains", []);
    $results = [];
    foreach ($domains as $row) {
        $results[] = DomainManager::checkDomain($row['domain']);
    }
    echo json_encode(['ok' => true, 'results' => $results]);
    exit;
}

// ── AJAX: Auto-configure VirtualHost for a domain ────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'auto_configure_domain') {
    header('Content-Type: application/json');
    $domain = strtolower(trim(Helpers::postRaw('domain')));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');
    if (!$domain) { echo json_encode(['ok' => false, 'message' => 'No domain provided']); exit; }
    $result = DomainManager::autoConfigure($domain);
    // After configure, re-check DNS+HTTP
    $check = DomainManager::checkDomain($domain);
    echo json_encode(array_merge($result, ['check' => $check]));
    exit;
}

// ── AJAX: Request SSL for a domain ───────────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'request_ssl_domain') {
    header('Content-Type: application/json');
    $domain = strtolower(trim(Helpers::postRaw('domain')));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');
    if (!$domain) { echo json_encode(['ok' => false, 'message' => 'No domain provided']); exit; }
    $result = DomainManager::requestSsl($domain);
    echo json_encode($result);
    exit;
}

// ── AJAX: Get server IP ───────────────────────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'get_server_ip') {
    header('Content-Type: application/json');
    echo json_encode(['ip' => DomainManager::getServerIp(), 'env' => DomainManager::detectEnvironment()]);
    exit;
}

// ── AJAX: Test SMTP Connection ───────────────────────────────────────────
if (Helpers::isPost() && Helpers::post('action') === 'test_smtp') {
    header('Content-Type: application/json');
    $email = trim(Helpers::postRaw('email'));
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid email address provided']);
        exit;
    }
    try {
        require_once BASE_PATH . '/core/Mailer.php';
        Mailer::sendRaw(
            $email,
            'Admin',
            'Test Email from Affscash',
            '<div style="font-family:sans-serif;padding:20px;max-width:600px;margin:0 auto;border:1px solid #eee;border-radius:8px">
                <h2 style="color:#4F46E5">SMTP Connection Successful!</h2>
                <p>This is a test email to verify your SMTP settings. If you received this, your email configuration is working perfectly.</p>
                <p style="color:#6B7280;font-size:12px;margin-top:20px">Sent from your Affscash Admin Panel.</p>
            </div>',
            'system_test'
        );
        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$errors  = [];
$success = false;

// Helper: handle image upload, return stored path or null. Pushes a
// human-readable reason to the caller-supplied $err array on failure so the
// admin actually finds out why an upload didn't go through.
function handleUpload(string $field, string $prefix, array &$err = []): ?string {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['tmp_name'])) return null;
    $file = $_FILES[$field];

    // PHP-level upload errors (size > post_max_size, partial upload, etc.)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $phpErrMap = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP tmp directory is missing',
            UPLOAD_ERR_CANT_WRITE => 'Could not write file to disk',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload',
        ];
        $err[] = "$field: " . ($phpErrMap[$file['error']] ?? ('Upload error code ' . $file['error']));
        return null;
    }

    // Resolve MIME with finfo first (most reliable), fall back to
    // mime_content_type, finally to extension-based detection so hosts
    // without the fileinfo extension still work.
    $allowed = ['image/png','image/jpeg','image/gif','image/svg+xml','image/x-icon','image/vnd.microsoft.icon','image/webp'];
    $mime = '';
    if (class_exists('finfo')) {
        try {
            $f = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$f->file($file['tmp_name']);
        } catch (\Throwable $_e) {}
    }
    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string)@mime_content_type($file['tmp_name']);
    }
    if ($mime === '') {
        // Extension fallback — only when both detectors are unavailable.
        $extMap = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon', 'webp' => 'image/webp',
        ];
        $extLower = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = $extMap[$extLower] ?? '';
    }
    if (!in_array($mime, $allowed, true)) {
        $err[] = "$field: Unsupported file type" . ($mime !== '' ? " ($mime)" : '') . '. Use PNG, JPG, GIF, SVG, ICO or WEBP.';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $err[] = "$field: File is larger than 2 MB";
        return null;
    }

    $uploadsDir = BASE_PATH . '/assets/uploads/';
    if (!is_dir($uploadsDir)) {
        if (!@mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            $err[] = "$field: Could not create assets/uploads/ — check filesystem permissions";
            return null;
        }
    }
    if (!is_writable($uploadsDir)) {
        $err[] = "$field: assets/uploads/ is not writable by the web server";
        return null;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
    if (!preg_match('/^[a-z0-9]{1,5}$/', $ext)) $ext = 'png';
    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest = $uploadsDir . $name;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/assets/uploads/' . $name;
    }
    $err[] = "$field: move_uploaded_file failed — likely a permission issue on assets/uploads/";
    return null;
}

// Helper: background-video upload. MP4 / WebM only with a strict 15 MB cap
// to keep the auth-page payload sane. Larger clips harm Time To First Byte
// and waste mobile bandwidth — the recommended length is 5–15 s, looping.
function handleBgVideoUpload(string $field, string $prefix): ?string {
    if (empty($_FILES[$field]['tmp_name'])) return null;
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowedMime = ['video/mp4','video/webm','video/quicktime'];
    $allowedExt  = ['mp4','webm','mov'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) return null;
    if ($file['size'] > 15 * 1024 * 1024) return null;

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp4');
    if (!in_array($ext, $allowedExt, true)) $ext = 'mp4';
    $name = $prefix . '_' . time() . '.' . $ext;
    $dest = BASE_PATH . '/assets/uploads/' . $name;

    if (!is_dir(BASE_PATH . '/assets/uploads/')) {
        @mkdir(BASE_PATH . '/assets/uploads/', 0775, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/assets/uploads/' . $name;
    }
    return null;
}

// Helper: background-image upload with a higher 5 MB cap. Hero backgrounds
// are routinely 1500-2500 px wide and exceed the 2 MB limit used for logos.
// JPEG / PNG / WebP only — SVG is rejected here because remote-resource SVGs
// can carry script payloads, and tiny bitmaps don't make good backgrounds.
function handleBgUpload(string $field, string $prefix): ?string {
    if (empty($_FILES[$field]['tmp_name'])) return null;
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/png','image/jpeg','image/webp','image/gif'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5 MB cap

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
    if (!in_array($ext, ['png','jpg','jpeg','webp','gif'], true)) $ext = 'jpg';
    $name = $prefix . '_' . time() . '.' . $ext;
    $dest = BASE_PATH . '/assets/uploads/' . $name;

    if (!is_dir(BASE_PATH . '/assets/uploads/')) {
        @mkdir(BASE_PATH . '/assets/uploads/', 0775, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/assets/uploads/' . $name;
    }
    return null;
}

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $tab = Helpers::post('tab') ?: 'general';

    if ($tab === 'general') {
        $appName   = trim(Helpers::post('app_name'))  ?: 'AffiliateTracker';
        $appUrl    = rtrim(trim(Helpers::post('app_url')), '/');
        $timezone  = Helpers::post('timezone') ?: 'UTC';
        $copyright = trim(Helpers::post('footer_copyright'));

        // Validate timezone to prevent saving an invalid value
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $timezone = 'UTC';
        }

        // Read the full config once, update all general fields, then write
        // in a single atomic operation so no field (including timezone) is lost.
        $fullConfig = Config::get('config') ?? [];
        if (!isset($fullConfig['app']) || !is_array($fullConfig['app'])) {
            $fullConfig['app'] = [];
        }
        $fullConfig['app']['name']             = $appName;
        $fullConfig['app']['url']              = $appUrl;
        $fullConfig['app']['timezone']         = $timezone;
        $fullConfig['app']['address']          = trim(Helpers::postRaw('app_address') ?? '');
        $fullConfig['app']['phone']            = trim(Helpers::postRaw('app_phone')   ?? '');
        $fullConfig['app']['footer_copyright'] = $copyright;
        $fullConfig['app']['enable_gtranslate'] = Helpers::post('enable_gtranslate') === '0' ? 0 : 1;
        // Landing page contact details
        $fullConfig['app']['contact_email']    = trim(Helpers::postRaw('app_contact_email')  ?? '');
        $fullConfig['app']['manager_name']     = trim(Helpers::post('app_manager_name')       ?? '');
        $fullConfig['app']['support_email']    = trim(Helpers::postRaw('app_support_email')   ?? '');
        $fullConfig['app']['telegram_handle']  = ltrim(trim(Helpers::post('app_telegram_handle') ?? ''), '@');
        $fullConfig['app']['teams_skype_url']  = trim(Helpers::postRaw('app_teams_skype_url') ?? '');
        $fullConfig['app']['tracking_url']     = rtrim(trim(Helpers::postRaw('app_tracking_url') ?? ''), '/');

        // Platform-wide default theme — applied to new users and to users who
        // have not chosen their own preference yet. Per-user choices override.
        $defaultTheme    = strtolower(trim((string)Helpers::post('default_theme')));
        if (!in_array($defaultTheme, ['light','dark'], true)) $defaultTheme = 'light';
        $prevDefault     = $fullConfig['app']['default_theme'] ?? 'light';
        $themeChanged    = ($prevDefault !== $defaultTheme);
        $fullConfig['app']['default_theme'] = $defaultTheme;

        if (!Config::write('config', $fullConfig)) {
            $errors[] = 'Failed to save settings. Please ensure the config/ directory is writable by the web server.';
        } else {
            // Apply the new timezone immediately for the current request
            date_default_timezone_set($timezone);

            // When the platform default theme actually changes, sync the admin's
            // own user preference so the new look applies immediately on the
            // next page paint. Without this the admin's previously-toggled
            // preference would still override the platform default for their
            // own session, making the setting look like it "didn't apply".
            // Other users keep their explicit preferences — this only nudges
            // the admin who just made the change.
            if ($themeChanged) {
                try { Theme::setUserPreference((int)Auth::id(), $defaultTheme); } catch (\Throwable $_e) {}
                // Also tell theme.js (on the next paint) to flush any stale
                // localStorage value so the FOUC guard cannot revive the old
                // theme on subsequent loads.
                Helpers::flash('success', '✓ Default Platform Theme updated to ' . ucfirst($defaultTheme) . '. Your view has been synced.');
                $_SESSION['_theme_force'] = $defaultTheme;
            }

            $success = true;
        }
    }

    elseif ($tab === 'branding') {
        $uploadErrors = [];
        $logoPath      = handleUpload('logo',       'logo',       $uploadErrors);
        $faviconPath   = handleUpload('favicon',    'favicon',    $uploadErrors);
        $loginLogoPath = handleUpload('login_logo', 'login_logo', $uploadErrors);

        if ($logoPath)      Config::set('config', 'app.logo',       $logoPath);
        if ($faviconPath)   Config::set('config', 'app.favicon',    $faviconPath);
        if ($loginLogoPath) Config::set('config', 'app.login_logo', $loginLogoPath);

        foreach ($uploadErrors as $msg) { $errors[] = $msg; }

        if (Helpers::post('clear_logo') === '1')       Config::set('config', 'app.logo',       '');
        if (Helpers::post('clear_favicon') === '1')    Config::set('config', 'app.favicon',    '');
        if (Helpers::post('clear_login_logo') === '1') Config::set('config', 'app.login_logo', '');

        // White logo toggle — checkbox: present = 1, absent = 0
        Config::set('config', 'app.login_logo_white', Helpers::post('login_logo_white') === '1' ? '1' : '');
        Config::set('config', 'app.dark_logo_white',  Helpers::post('dark_logo_white')  === '1' ? '1' : '0');
        
        $bannerStyle = Helpers::post('dashboard_banner_style');
        if (in_array($bannerStyle, ['default', 'transparent', 'glass', 'glass_purple'])) {
            Config::set('config', 'app.dashboard_banner_style', $bannerStyle);
            // Backward compatibility
            Config::set('config', 'app.transparent_dashboard', $bannerStyle === 'transparent' ? '1' : '0');
        }

        $cardStyle = Helpers::post('dashboard_card_style');
        if (in_array($cardStyle, ['default', 'gradient_glow', 'neon_glass', 'aurora'])) {
            Config::set('config', 'app.dashboard_card_style', $cardStyle);
        }

        $trendStyle = Helpers::post('trend_chart_style');
        if (in_array($trendStyle, ['default', 'straight', 'stepped', 'high_tech', 'gradient_fill', 'neon_glow', 'minimal_dots', 'area_stacked', 'thin_sharp', 'bold_rounded'])) {
            Config::set('config', 'app.trend_chart_style', $trendStyle);
        }

        // ── Auth-page backgrounds (login / affiliate register / advertiser register)
        // Each page has: uploaded image path + an enable toggle. Existing config
        // is preserved when no new file is uploaded so admins can flip the toggle
        // on / off without re-uploading.
        $bgFields = [
            'login'  => ['file' => 'auth_bg_login',  'enable' => 'auth_bg_login_enabled',  'clear' => 'clear_auth_bg_login',  'prefix' => 'bg_login'],
            'affreg' => ['file' => 'auth_bg_affreg', 'enable' => 'auth_bg_affreg_enabled', 'clear' => 'clear_auth_bg_affreg', 'prefix' => 'bg_affreg'],
            'advreg' => ['file' => 'auth_bg_advreg', 'enable' => 'auth_bg_advreg_enabled', 'clear' => 'clear_auth_bg_advreg', 'prefix' => 'bg_advreg'],
        ];
        foreach ($bgFields as $bg) {
            // Upload new file (if any).
            $newPath = handleBgUpload($bg['file'], $bg['prefix']);
            if ($newPath) {
                Config::set('config', 'app.' . $bg['file'], $newPath);
            }
            // Explicit clear flag wins over upload — admin can wipe the path.
            if (Helpers::post($bg['clear']) === '1') {
                Config::set('config', 'app.' . $bg['file'], '');
            }
            // Enable toggle.
            Config::set('config', 'app.' . $bg['enable'], Helpers::post($bg['enable']) === '1' ? '1' : '0');
        }

        $success = true;
    }


    elseif ($tab === 'inactivity_rotate') {
        // Generate a fresh hex token and invalidate the previous URL. This
        // is its own tab key (rather than a sub-action of the inactivity
        // form) so the existing form doesn't overwrite anything else.
        try {
            $newToken = bin2hex(random_bytes(16));
            Config::set('config', 'app.inactivity_cron_token', $newToken);
            Helpers::flash('success', '✓ Cron token rotated. Update any cron jobs that use the old URL.');
        } catch (\Throwable $_) {
            Helpers::flash('error', 'Could not rotate token — please try again.');
        }
        Helpers::redirect('/admin/settings?tab=inactivity');
    }

    elseif ($tab === 'inactivity') {
        // Affiliate Inactivity Control — all values validated server-side so a
        // crafted POST cannot push an out-of-range period or a non-numeric value.
        $inacOnIn        = isset($_POST['inactivity_enabled']) ? '1' : '0';
        $inacDaysIn      = (int)Helpers::postRaw('inactivity_days');
        $inacWarnIn      = (int)Helpers::postRaw('inactivity_warn_days');
        $allowedPeriods  = [7,15,30,60,90,120,180,365];
        $allowedWarnings = [0,1,2,3,5,7,14];
        if (!in_array($inacDaysIn, $allowedPeriods, true)) $inacDaysIn = 30;
        if (!in_array($inacWarnIn, $allowedWarnings, true)) $inacWarnIn = 3;
        // Warning must fire before deactivation, never on or after the cut-off.
        if ($inacWarnIn >= $inacDaysIn) $inacWarnIn = max(0, $inacDaysIn - 1);

        $inacSubj = trim(Helpers::postRaw('inactivity_warn_subject') ?? '');
        $inacBody = trim(Helpers::postRaw('inactivity_warn_body') ?? '');

        Config::set('config', 'app.inactivity_enabled',      $inacOnIn);
        Config::set('config', 'app.inactivity_days',         (string)$inacDaysIn);
        Config::set('config', 'app.inactivity_warn_days',    (string)$inacWarnIn);
        if ($inacSubj !== '') Config::set('config', 'app.inactivity_warn_subject', $inacSubj);
        if ($inacBody !== '') Config::set('config', 'app.inactivity_warn_body',    $inacBody);

        $success = true;
    }

    elseif ($tab === 'traffic') {
        Config::set('config', 'app.traffic_back_url', trim(Helpers::postRaw('traffic_back_url')));
        $success = true;
    }

    elseif ($tab === 'shortener') {
        $apiKey = trim(Helpers::postRaw('shortener_api_key'));
        if ($apiKey !== '') Config::set('config', 'shortener.api_key', $apiKey);
        Config::set('config', 'shortener.enabled', isset($_POST['shortener_enabled']) ? '1' : '0');
        $success = true;
    }

    elseif ($tab === 'security') {
        Config::set('config', 'app.2fa_enabled',          isset($_POST['2fa_enabled'])       ? '1' : '0');
        Config::set('config', 'app.email_verification',   isset($_POST['email_verification']) ? '1' : '0');
        $regMsg = trim(Helpers::postRaw('registration_message'));
        if ($regMsg !== '') Config::set('config', 'app.registration_message', $regMsg);
        // Cloudflare Turnstile
        $tsEnabled    = isset($_POST['turnstile_enabled']) ? '1' : '0';
        $tsSiteKey    = trim(Helpers::postRaw('turnstile_site_key')   ?? '');
        $tsSecretKey  = trim(Helpers::postRaw('turnstile_secret_key') ?? '');
        Config::set('config', 'turnstile.enabled',    $tsEnabled);
        if ($tsSiteKey   !== '') Config::set('config', 'turnstile.site_key',   $tsSiteKey);
        if ($tsSecretKey !== '') Config::set('config', 'turnstile.secret_key', $tsSecretKey);

        // ── Advertiser registration master switch + closed-page message.
        // The path-gate inside RegisterAdvertiserController reads these on
        // every request so the change is effective immediately, no cache.
        Config::set('config', 'app.advertiser_registration_enabled',
            isset($_POST['advertiser_registration_enabled']) ? '1' : '0');
        $advClosedMsg = trim(Helpers::postRaw('advertiser_registration_closed_message') ?? '');
        if ($advClosedMsg !== '') {
            Config::set('config', 'app.advertiser_registration_closed_message', $advClosedMsg);
        }

        $success = true;
    }


    elseif ($tab === 'vpn_detection') {
        Config::set('config', 'vpn_detection.enabled', isset($_POST['vpn_detection_enabled']) ? '1' : '0');
        $success = true;
    }

    elseif ($tab === 'conversions') {
        $mode = Helpers::post('conversion_approval_mode') === 'manual' ? 'manual' : 'auto';
        Config::set('config', 'conversion.approval_mode', $mode);
        Config::set('config', 'conversion.hide_fraud_rejected_reports',
            isset($_POST['hide_fraud_rejected_reports']) ? '1' : '0');

        // IP Conversion Protection Settings
        Config::set('config', 'conversion.one_per_ip_enabled', isset($_POST['one_per_ip_enabled']) ? '1' : '0');
        
        $redirectMode = Helpers::post('one_per_ip_redirect_mode');
        if (!in_array($redirectMode, ['traffic_back', 'next_available'])) $redirectMode = 'traffic_back';
        Config::set('config', 'conversion.one_per_ip_redirect_mode', $redirectMode);
        
        $durationMode = Helpers::post('one_per_ip_duration_mode');
        if (!in_array($durationMode, ['permanent', 'custom'])) $durationMode = 'permanent';
        Config::set('config', 'conversion.one_per_ip_duration_mode', $durationMode);
        
        $durationDays = (int)Helpers::post('one_per_ip_duration_days');
        Config::set('config', 'conversion.one_per_ip_duration_days', (string)max(1, $durationDays));
        
        $whitelist = trim(Helpers::post('one_per_ip_whitelist'));
        Config::set('config', 'conversion.one_per_ip_whitelist', $whitelist);

        $success = true;
    }

    elseif ($tab === 'budget_system') {
        // Master switch — when ON, advertisers must enter a budget when
        // creating an offer and conversion deductions kick in automatically.
        Config::set('config', 'app.budget_required', isset($_POST['budget_required']) ? '1' : '0');

        // Per-advertiser exemptions — IDs submitted as exempt_adv[] checkboxes.
        // We re-sync the entire advertisers table: set exempt=1 for ticked, 0 for all others.
        AdvBudget::ensureSchema();
        $exemptIds = array_map('intval', (array)($_POST['exempt_adv'] ?? []));
        // Set everyone to 0 first, then flip the selected ones.
        Database::query("UPDATE advertisers SET budget_exempt = 0", []);
        if ($exemptIds) {
            $placeholders = implode(',', array_fill(0, count($exemptIds), '?'));
            Database::query("UPDATE advertisers SET budget_exempt = 1 WHERE id IN ($placeholders)", $exemptIds);
        }

        // Wallet addresses / payment instructions for each top-up method.
        $cryptoCoins = ['usdt','btc','ltc','eth','bnb','trx'];
        $cryptoWallets = [];
        foreach ($cryptoCoins as $coin) {
            $cryptoWallets[$coin] = trim(Helpers::postRaw('pm_crypto_' . $coin) ?? '');
        }
        $methods = [
            'bank'          => trim(Helpers::postRaw('pm_bank')       ?? ''),
            'crypto_wallets'=> $cryptoWallets,
            'capitalist'    => trim(Helpers::postRaw('pm_capitalist') ?? ''),
        ];
        Config::set('config', 'app.payment_methods', $methods);
        Config::set('config', 'app.stripe_enabled', isset($_POST['stripe_enabled']) ? '1' : '0');
        Config::set('config', 'app.stripe_pk', trim(Helpers::postRaw('stripe_pk') ?? ''));
        Config::set('config', 'app.stripe_sk', trim(Helpers::postRaw('stripe_sk') ?? ''));
        $success = true;
    }

    elseif ($tab === 'mobile_app') {
        // Mobile-app install promo: footer button + affiliate login popup.
        $rawUrl  = trim(Helpers::postRaw('mobile_app_url') ?? '');
        $rawName = trim(Helpers::postRaw('mobile_app_name') ?? '');
        // Soft validation — accept http(s) URLs only. Empty is allowed (turns the button off).
        if ($rawUrl !== '' && !preg_match('#^https?://#i', $rawUrl)) {
            $rawUrl = 'https://' . $rawUrl;
        }
        Config::set('config', 'app.mobile_app_url',           $rawUrl);
        Config::set('config', 'app.mobile_app_name',          $rawName !== '' ? $rawName : 'AffsCash');
        Config::set('config', 'app.mobile_app_popup_enabled', isset($_POST['mobile_app_popup_enabled']) ? '1' : '0');
        $success = true;
    }

    elseif ($tab === 'commission') {
        $refRate = max(0, min(100, (float)Helpers::postRaw('refer_commission_rate')));
        $refType = Helpers::post('refer_commission_type') === 'fixed' ? 'fixed' : 'percent';
        Config::set('config', 'app.refer_commission_rate', $refRate);
        Config::set('config', 'app.refer_commission_type', $refType);
        $success = true;
    }

    elseif ($tab === 'notifications') {
        Config::set('config', 'app.new_offer_notify',    isset($_POST['new_offer_notify'])    ? '1' : '0');
        Config::set('config', 'app.offer_status_notify', isset($_POST['offer_status_notify']) ? '1' : '0');
        Config::set('config', 'app.offer_link_notify',   isset($_POST['offer_link_notify'])   ? '1' : '0');
        $success = true;
    }

    elseif ($tab === 'email') {
        Config::set('config', 'smtp.host',       trim(Helpers::post('smtp_host')));
        Config::set('config', 'smtp.port',       (int)(Helpers::post('smtp_port') ?: 587));
        Config::set('config', 'smtp.username',   trim(Helpers::post('smtp_username')));
        $pass = Helpers::postRaw('smtp_password');
        if ($pass !== '') Config::set('config', 'smtp.password', $pass);
        Config::set('config', 'smtp.encryption', Helpers::post('smtp_encryption') ?: 'tls');
        Config::set('config', 'smtp.from_email', trim(Helpers::post('smtp_from_email')));
        Config::set('config', 'smtp.from_name',  trim(Helpers::post('smtp_from_name')));
        $success = true;
    }

    elseif ($tab === 'domains') {
        $domainAction = Helpers::post('domain_action');

        if ($domainAction === 'add') {
            $domain = strtolower(trim(Helpers::postRaw('new_domain')));
            $label  = trim(Helpers::post('new_domain_label'));
            // Strip protocol if provided
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
            if ($domain) {
                // If first domain, make it default
                $count = Database::count('tracking_domains', '1');
                Database::query(
                    "INSERT IGNORE INTO tracking_domains (domain, label, is_default, is_active) VALUES (?,?,?,1)",
                    [$domain, $label, $count === 0 ? 1 : 0]
                );
                // Kick off immediate DNS check so status shows right away
                try { DomainManager::checkDomain($domain); } catch (\Throwable $_e) {}
            }
        } elseif ($domainAction === 'edit') {
            $dId    = (int)Helpers::postRaw('domain_id');
            $domain = strtolower(trim(Helpers::postRaw('edit_domain')));
            $label  = trim(Helpers::post('edit_domain_label'));
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');
            if ($domain && $dId) {
                Database::query(
                    "UPDATE tracking_domains SET domain=?, label=? WHERE id=?",
                    [$domain, $label, $dId]
                );
            }
        } elseif ($domainAction === 'set_landing') {
            $dId = (int)Helpers::postRaw('domain_id');
            $row = Database::fetchOne("SELECT domain FROM tracking_domains WHERE id=?", [$dId]);
            if ($row) {
                $fc = Config::get('config') ?? [];
                $fc['app']['landing_domain'] = $row['domain'];
                Config::write('config', $fc);
            }
        } elseif ($domainAction === 'clear_landing') {
            $fc = Config::get('config') ?? [];
            $fc['app']['landing_domain'] = '';
            Config::write('config', $fc);
        } elseif ($domainAction === 'set_default') {
            $dId = (int)Helpers::postRaw('domain_id');
            Database::query("UPDATE tracking_domains SET is_default=0", []);
            Database::query("UPDATE tracking_domains SET is_default=1 WHERE id=?", [$dId]);
        } elseif ($domainAction === 'toggle') {
            $dId = (int)Helpers::postRaw('domain_id');
            Database::query("UPDATE tracking_domains SET is_active = 1-is_active WHERE id=?", [$dId]);
        } elseif ($domainAction === 'delete') {
            $dId = (int)Helpers::postRaw('domain_id');
            Database::query("DELETE FROM tracking_domains WHERE id=?", [$dId]);
        }

        Config::clearCache();
        Helpers::flash('success', 'Tracking domains updated.');
        Helpers::redirect('/admin/settings?tab=domains');
    }

    if (!empty($errors)) {
        Helpers::flash('error', 'Some changes were not saved: ' . implode(' | ', $errors));
    }
    if ($success) {
        Config::clearCache();
        if (empty($errors)) {
            Helpers::flash('success', 'Settings saved successfully.');
        }
        Helpers::redirect('/admin/settings?tab=' . ($tab ?? 'general'));
    }
}

$activeTab = Helpers::get('tab') ?: 'general';
$cfg = Config::get('config') ?? [];

$trackingDomains = Database::fetchAll("SELECT * FROM tracking_domains ORDER BY is_default DESC, created_at ASC");
$serverIp        = DomainManager::getServerIp();
$serverEnv       = DomainManager::detectEnvironment();
$canExec         = DomainManager::canExec();

// Load advertiser list for budget exemption tab
AdvBudget::ensureSchema();
$allAdvertisers = Database::fetchAll(
    "SELECT adv.id, adv.budget_exempt, u.first_name, u.last_name, u.email, u.company, u.status
     FROM advertisers adv JOIN users u ON u.id = adv.user_id
     WHERE u.role='advertiser' ORDER BY u.first_name, u.last_name"
);

require BASE_PATH . '/views/admin/settings/index.php';
