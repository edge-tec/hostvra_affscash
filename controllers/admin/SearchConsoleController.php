<?php
Auth::check('admin');
require_once BASE_PATH . '/core/GoogleSearchConsole.php';

$pageTitle = 'Google Search Console';

// ── Schema: SEO settings table ───────────────────────────────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `seo_settings` (
        `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `setting_key`      VARCHAR(120) NOT NULL,
        `setting_value`    MEDIUMTEXT DEFAULT NULL,
        `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $_e) {}

try {
    Database::query("CREATE TABLE IF NOT EXISTS `seo_indexing_log` (
        `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `url`        VARCHAR(1000) NOT NULL,
        `type`       VARCHAR(30) NOT NULL DEFAULT 'URL_UPDATED',
        `status`     VARCHAR(30) NOT NULL DEFAULT 'pending',
        `response`   TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_url`     (`url`(255)),
        KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $_e) {}

// Generic API call log — every cURL trip (token + sites + sitemaps + analytics
// + url-inspect + indexing) is written here by GoogleSearchConsole::log().
// Lets admins debug Google failures without server SSH access.
try {
    Database::query("CREATE TABLE IF NOT EXISTS `seo_api_log` (
        `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `op`            VARCHAR(40)   NOT NULL DEFAULT '',
        `method`        VARCHAR(10)   NOT NULL DEFAULT 'GET',
        `url`           VARCHAR(500)  NOT NULL DEFAULT '',
        `http_code`     SMALLINT      NOT NULL DEFAULT 0,
        `error_message` TEXT          DEFAULT NULL,
        `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_created` (`created_at`),
        KEY `idx_op`      (`op`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $_e) {}

// ── Helpers ───────────────────────────────────────────────────────────────────
function seoGet(string $key, string $default = ''): string
{
    $row = Database::fetchOne("SELECT setting_value FROM seo_settings WHERE setting_key = ?", [$key]);
    return $row ? (string)($row['setting_value'] ?? $default) : $default;
}

function seoSet(string $key, string $value): void
{
    Database::query(
        "INSERT INTO seo_settings (setting_key, setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()",
        [$key, $value]
    );
}

function getSA(): array
{
    $json = seoGet('service_account_json');
    if (!$json) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function getGscToken(): string
{
    $sa = getSA();
    if (!$sa) return '';
    return GoogleSearchConsole::getAccessToken($sa, GoogleSearchConsole::SCOPE_GSC);
}

function getIndexToken(): string
{
    $sa = getSA();
    if (!$sa) return '';
    return GoogleSearchConsole::getAccessToken($sa, GoogleSearchConsole::SCOPE_INDEXING);
}

// ── Sitemap URL builder ───────────────────────────────────────────────────────
// Delegates to the shared SeoSitemap class so the admin panel, the
// /sitemap.xml router fallback, and the cron job all emit the same URLs.
require_once BASE_PATH . '/core/SeoSitemap.php';
function buildSitemapUrls(): array { return SeoSitemap::buildUrls(); }

$action = Helpers::get('action') ?: 'index';

// ══════════════════════════════════════════════════════════════════════════════
// POST handlers
// ══════════════════════════════════════════════════════════════════════════════
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $sub = Helpers::post('submit_type');

    // ── Save GSC credentials + site URL ──────────────────────────────────────
    if ($sub === 'save_credentials') {
        $siteUrl = rtrim(trim(Helpers::post('site_url')), '/');
        seoSet('site_url', $siteUrl);

        // Service account JSON upload or paste
        $saJson = '';
        if (!empty($_FILES['sa_json_file']['tmp_name'])) {
            $saJson = trim(file_get_contents($_FILES['sa_json_file']['tmp_name']));
        } elseif (Helpers::postRaw('sa_json_paste')) {
            $saJson = trim(Helpers::postRaw('sa_json_paste'));
        }
        if ($saJson) {
            $decoded = json_decode($saJson, true);
            if (!$decoded || !isset($decoded['client_email'], $decoded['private_key'])) {
                Helpers::flash('error', 'Invalid Service Account JSON — must contain client_email and private_key.');
            } else {
                seoSet('service_account_json', $saJson);
                // Drop any access token minted with the previous credentials.
                GoogleSearchConsole::flushTokenCache();
                Helpers::flash('success', 'Service account credentials saved. Token cache cleared.');
            }
        } else {
            Helpers::flash('success', 'Site URL saved.');
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Save verification meta tag ────────────────────────────────────────────
    if ($sub === 'save_verification') {
        seoSet('verification_meta',    trim(Helpers::post('verification_meta')));
        seoSet('google_analytics_id',  trim(Helpers::post('google_analytics_id')));
        seoSet('google_tag_manager_id',trim(Helpers::post('google_tag_manager_id')));
        Helpers::flash('success', 'Verification & tracking settings saved. Add the meta tag to your site\'s &lt;head&gt;.');
        Helpers::redirect('/admin/search-console');
    }

    // ── Upload HTML verification file ─────────────────────────────────────────
    if ($sub === 'upload_verification_file') {
        if (!empty($_FILES['verification_file']['tmp_name'])) {
            $fname = preg_replace('/[^a-z0-9\-_.]/', '', strtolower(basename($_FILES['verification_file']['name'])));
            if (!preg_match('/^google[a-z0-9]+\.html$/', $fname)) {
                Helpers::flash('error', 'Filename must match googleXXXXXXXXXXXXXXXX.html format.');
            } else {
                $dest = BASE_PATH . '/' . $fname;
                if (move_uploaded_file($_FILES['verification_file']['tmp_name'], $dest)) {
                    seoSet('verification_file', $fname);
                    Helpers::flash('success', "Verification file uploaded: /{$fname}");
                } else {
                    Helpers::flash('error', 'File upload failed — check directory permissions.');
                }
            }
        } else {
            Helpers::flash('error', 'No file selected.');
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Save SEO settings ─────────────────────────────────────────────────────
    if ($sub === 'save_seo') {
        seoSet('meta_title',       trim(Helpers::post('meta_title')));
        seoSet('meta_description', trim(Helpers::post('meta_description')));
        seoSet('meta_keywords',    trim(Helpers::post('meta_keywords')));
        seoSet('robots_index',     Helpers::post('robots_index') === '1' ? '1' : '0');
        seoSet('robots_follow',    Helpers::post('robots_follow') === '1' ? '1' : '0');
        seoSet('robots_disallow',  trim(Helpers::postRaw('robots_disallow')));
        seoSet('sitemap_custom_urls', trim(Helpers::postRaw('sitemap_custom_urls')));

        // Write robots.txt
        $robotsTxt = GoogleSearchConsole::buildRobotsTxt([
            'site_url'         => seoGet('site_url', Config::get('config', 'app.url') ?? ''),
            'robots_index'     => seoGet('robots_index') !== '0',
            'robots_disallow'  => seoGet('robots_disallow'),
        ]);
        @file_put_contents(BASE_PATH . '/robots.txt', $robotsTxt);

        Helpers::flash('success', 'SEO settings saved and robots.txt updated.');
        Helpers::redirect('/admin/search-console');
    }

    // ── Generate / regenerate sitemap ─────────────────────────────────────────
    if ($sub === 'generate_sitemap') {
        $urls = buildSitemapUrls();
        $xml  = GoogleSearchConsole::buildSitemapXml($urls);
        if (file_put_contents(BASE_PATH . '/sitemap.xml', $xml) !== false) {
            seoSet('sitemap_generated_at', date('Y-m-d H:i:s'));
            seoSet('sitemap_url_count', (string)count($urls));
            Helpers::flash('success', sprintf('sitemap.xml generated with %d URLs.', count($urls)));
        } else {
            Helpers::flash('error', 'Could not write sitemap.xml — check write permissions on root directory.');
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Submit sitemap to Google ──────────────────────────────────────────────
    if ($sub === 'submit_sitemap') {
        $siteUrl    = seoGet('site_url', Config::get('config', 'app.url') ?? '');
        $sitemapUrl = rtrim($siteUrl, '/') . '/sitemap.xml';
        $token      = getGscToken();
        if (!$token) {
            Helpers::flash('error', 'Could not get access token — ' . (GoogleSearchConsole::lastError() ?: 'check Service Account JSON credentials.'));
        } else {
            $r = GoogleSearchConsole::submitSitemap($siteUrl, $sitemapUrl, $token);
            if ($r['success']) {
                seoSet('sitemap_submitted_at', date('Y-m-d H:i:s'));
                seoSet('sitemap_submitted_url', $sitemapUrl);
                Helpers::flash('success', "Sitemap submitted to Google: {$sitemapUrl}");
            } else {
                Helpers::flash('error', 'Sitemap submission failed (HTTP ' . $r['http'] . '): ' . ($r['error'] ?: 'unknown error'));
            }
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Add site to GSC ───────────────────────────────────────────────────────
    if ($sub === 'add_site') {
        $siteUrl = seoGet('site_url', Config::get('config', 'app.url') ?? '');
        $token   = getGscToken();
        if (!$token) {
            Helpers::flash('error', 'Could not authenticate — ' . (GoogleSearchConsole::lastError() ?: 'check Service Account JSON.'));
        } else {
            $r = GoogleSearchConsole::addSite($siteUrl, $token);
            if ($r['success']) {
                Helpers::flash('success', 'Site add request sent to Google Search Console. Verify ownership to activate.');
            } else {
                Helpers::flash('error', 'Could not add site (HTTP ' . $r['http'] . '): ' . ($r['error'] ?: 'unknown error') . '. Make sure the service account email is added as an Owner of the property.');
            }
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Request URL indexing ──────────────────────────────────────────────────
    if ($sub === 'request_indexing') {
        $rawUrls = Helpers::postRaw('index_urls');
        $type    = Helpers::post('index_type') === 'URL_DELETED' ? 'URL_DELETED' : 'URL_UPDATED';
        $token   = getIndexToken();
        if (!$token) {
            Helpers::flash('error', 'Could not authenticate for Indexing API — ' . (GoogleSearchConsole::lastError() ?: 'ensure Service Account has Indexing API access.'));
        } else {
            // Indexing API quota is 200 URLs/day, 60/min by default. Cap the
            // per-submission batch and pace by 100 ms so we never burst the
            // per-minute ceiling.
            $lines = array_filter(array_map('trim', explode("\n", $rawUrls)));
            if (count($lines) > 200) { $lines = array_slice($lines, 0, 200); }
            $success = 0; $failed = 0; $lastErr = '';
            foreach ($lines as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) { $failed++; $lastErr = 'Invalid URL: ' . $url; continue; }
                $r = GoogleSearchConsole::requestIndexing($url, $token, $type);
                Database::insert('seo_indexing_log', [
                    'url'      => $url,
                    'type'     => $type,
                    'status'   => $r['success'] ? 'submitted' : 'failed',
                    'response' => json_encode([
                        'http'  => $r['http']  ?? 0,
                        'error' => $r['error'] ?? null,
                        'body'  => $r['response'] ?? null,
                    ]),
                ]);
                if ($r['success']) {
                    $success++;
                } else {
                    $failed++;
                    if (!$lastErr) $lastErr = ($r['error'] ?? 'HTTP ' . ($r['http'] ?? '?'));
                }
                usleep(100000); // 100 ms — keep under 60/min hard limit
            }
            $level = $failed === 0 ? 'success' : ($success > 0 ? 'warning' : 'error');
            $msg   = sprintf('Indexing requests: %d submitted, %d failed.', $success, $failed);
            if ($lastErr) $msg .= ' Last error: ' . $lastErr;
            Helpers::flash($level, $msg);
        }
        Helpers::redirect('/admin/search-console');
    }

    // ── Delete service account ────────────────────────────────────────────────
    if ($sub === 'remove_credentials') {
        seoSet('service_account_json', '');
        Helpers::flash('success', 'Service account credentials removed.');
        Helpers::redirect('/admin/search-console');
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// GET: AJAX actions
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'test_connection') {
    header('Content-Type: application/json');
    $sa = getSA();
    if (!$sa) { echo json_encode(['ok' => false, 'msg' => 'No service account credentials saved.']); exit; }
    $token = GoogleSearchConsole::getAccessToken($sa, GoogleSearchConsole::SCOPE_GSC);
    if (!$token) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Token request failed: ' . (GoogleSearchConsole::lastError() ?: 'check private key in service account JSON.'),
        ]);
        exit;
    }
    $siteUrl = seoGet('site_url', Config::get('config', 'app.url') ?? '');
    $sites   = GoogleSearchConsole::listSites($token);
    echo json_encode([
        'ok'       => true,
        'msg'      => 'Connected! Found ' . count($sites) . ' verified site(s) in this account.',
        'sites'    => array_column($sites, 'siteUrl'),
        'site_url' => $siteUrl,
        'sa_email' => $sa['client_email'] ?? '',
    ]);
    exit;
}

// ── Search Analytics — clicks / impressions / CTR / position ────────────────
// Accepts: dimension (query|page|country|device|date), days (1-90), rows (1-1000)
if ($action === 'analytics') {
    header('Content-Type: application/json');
    $sa = getSA();
    if (!$sa) { echo json_encode(['ok' => false, 'msg' => 'No service account credentials saved.']); exit; }
    $token = GoogleSearchConsole::getAccessToken($sa, GoogleSearchConsole::SCOPE_GSC);
    if (!$token) {
        echo json_encode(['ok' => false, 'msg' => 'Auth failed: ' . (GoogleSearchConsole::lastError() ?: 'unknown')]);
        exit;
    }
    $siteUrl = seoGet('site_url', Config::get('config', 'app.url') ?? '');
    if (!$siteUrl) {
        echo json_encode(['ok' => false, 'msg' => 'Site URL is not configured.']); exit;
    }
    // Whitelist the dimension to prevent free-form input reaching Google's API.
    $allowedDims = ['query','page','country','device','date'];
    $dim         = in_array(Helpers::get('dimension'), $allowedDims, true) ? Helpers::get('dimension') : 'query';
    $days        = max(1, min(90, (int)(Helpers::get('days') ?: 28)));
    $rowLimit    = max(1, min(1000, (int)(Helpers::get('rows') ?: 50)));

    $r = GoogleSearchConsole::searchAnalytics($siteUrl, $token, [
        'startDate'  => date('Y-m-d', strtotime('-' . $days . ' days')),
        'endDate'    => date('Y-m-d', strtotime('-1 day')),     // Google data lags ~1-3 days
        'dimensions' => [$dim],
        'rowLimit'   => $rowLimit,
    ]);
    if (!$r['success']) {
        echo json_encode(['ok' => false, 'msg' => $r['error'] ?? 'Analytics query failed.', 'http' => $r['http']]);
        exit;
    }
    // Project to a flat array the JS table can render directly.
    $rows = [];
    foreach ($r['rows'] as $row) {
        $rows[] = [
            'key'         => $row['keys'][0] ?? '',
            'clicks'      => (int)($row['clicks'] ?? 0),
            'impressions' => (int)($row['impressions'] ?? 0),
            'ctr'         => round(((float)($row['ctr'] ?? 0)) * 100, 2),
            'position'    => round((float)($row['position'] ?? 0), 1),
        ];
    }
    echo json_encode([
        'ok'        => true,
        'dimension' => $dim,
        'days'      => $days,
        'site_url'  => $siteUrl,
        'rows'      => $rows,
    ]);
    exit;
}

// ── URL Inspection — coverage state, last crawl, indexing status ────────────
if ($action === 'url_inspect') {
    header('Content-Type: application/json');
    $sa = getSA();
    if (!$sa) { echo json_encode(['ok' => false, 'msg' => 'No service account credentials saved.']); exit; }
    $token = GoogleSearchConsole::getAccessToken($sa, GoogleSearchConsole::SCOPE_GSC);
    if (!$token) {
        echo json_encode(['ok' => false, 'msg' => 'Auth failed: ' . (GoogleSearchConsole::lastError() ?: 'unknown')]);
        exit;
    }
    $siteUrl   = seoGet('site_url', Config::get('config', 'app.url') ?? '');
    $targetUrl = trim((string)Helpers::get('url'));
    if (!$siteUrl || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['ok' => false, 'msg' => 'Provide a valid URL to inspect.']);
        exit;
    }
    $r = GoogleSearchConsole::inspectUrl($siteUrl, $targetUrl, $token);
    if (!$r['success']) {
        echo json_encode(['ok' => false, 'msg' => $r['error'] ?? 'Inspection failed.', 'http' => $r['http']]);
        exit;
    }
    echo json_encode(['ok' => true, 'result' => $r['result']]);
    exit;
}

if ($action === 'list_sitemaps') {
    header('Content-Type: application/json');
    $token   = getGscToken();
    $siteUrl = seoGet('site_url', Config::get('config', 'app.url') ?? '');
    if (!$token) { echo json_encode(['ok' => false, 'sitemaps' => []]); exit; }
    $sitemaps = GoogleSearchConsole::listSitemaps($siteUrl, $token);
    echo json_encode(['ok' => true, 'sitemaps' => $sitemaps]);
    exit;
}

if ($action === 'preview_sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    echo GoogleSearchConsole::buildSitemapXml(buildSitemapUrls());
    exit;
}

if ($action === 'delete_sitemap') {
    header('Content-Type: application/json');
    $sitemapUrl = Helpers::get('sitemap_url') ?? '';
    $token      = getGscToken();
    $siteUrl    = seoGet('site_url', Config::get('config', 'app.url') ?? '');
    if (!$token || !$sitemapUrl) { echo json_encode(['ok' => false]); exit; }
    $ok = GoogleSearchConsole::deleteSitemap($siteUrl, $sitemapUrl, $token);
    echo json_encode(['ok' => $ok]);
    exit;
}

// ── Load view data ────────────────────────────────────────────────────────────
$siteUrl            = seoGet('site_url', Config::get('config', 'app.url') ?? '');
$verificationMeta   = seoGet('verification_meta');
$verificationFile   = seoGet('verification_file');
$gaId               = seoGet('google_analytics_id');
$gtmId              = seoGet('google_tag_manager_id');
$metaTitle          = seoGet('meta_title', Config::get('config', 'app.name') ?? '');
$metaDescription    = seoGet('meta_description');
$metaKeywords       = seoGet('meta_keywords');
$robotsIndex        = seoGet('robots_index', '1');
$robotsFollow       = seoGet('robots_follow', '1');
$robotsDisallow     = seoGet('robots_disallow');
$sitemapCustom      = seoGet('sitemap_custom_urls');
$sitemapGeneratedAt = seoGet('sitemap_generated_at');
$sitemapSubmittedAt = seoGet('sitemap_submitted_at');
$sitemapSubmittedUrl= seoGet('sitemap_submitted_url');
$sitemapUrlCount    = seoGet('sitemap_url_count', '0');
$_sa                = getSA();
$hasSA              = !empty($_sa);
$saEmail            = $_sa['client_email'] ?? '';
$saProject          = $_sa['project_id']   ?? '';
$hasVerifFile       = $verificationFile && file_exists(BASE_PATH . '/' . $verificationFile);
$hasSitemapFile     = file_exists(BASE_PATH . '/sitemap.xml');
$hasRobotsFile      = file_exists(BASE_PATH . '/robots.txt');

// Recent indexing log
$indexingLog = Database::fetchAll(
    "SELECT * FROM seo_indexing_log ORDER BY created_at DESC LIMIT 30"
);

// Recent API call log — written by GoogleSearchConsole::log() for every
// outbound request. Helps admins debug auth / quota failures without SSH.
$apiLog = [];
try {
    $apiLog = Database::fetchAll(
        "SELECT op, method, url, http_code, error_message, created_at
         FROM seo_api_log ORDER BY created_at DESC LIMIT 50"
    ) ?: [];
} catch (\Throwable $_) { /* table may not exist on older installs */ }

// Last token-fetch error (if any) — surfaces immediately on the Insights
// tab so a misconfigured service account is obvious without running tests.
$_lastApiError = '';
if ($hasSA) {
    try {
        // Touch the cache only — do NOT mint a new token here; if a cached
        // one exists, lastError stays empty. Real diagnostics come from the
        // explicit "Test connection" button.
        $_lastApiError = (string)GoogleSearchConsole::lastError();
    } catch (\Throwable $_) {}
}

$tab = Helpers::get('tab') ?: 'setup';

require BASE_PATH . '/views/admin/search_console/index.php';
