<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.gsc-tabs { display:flex; gap:4px; border-bottom:2px solid #E2E8F0; margin-bottom:24px; flex-wrap:wrap; }
.gsc-tab  { padding:10px 18px; font-size:13px; font-weight:600; color:#64748B; cursor:pointer; border-radius:6px 6px 0 0; border:2px solid transparent; border-bottom:none; text-decoration:none; transition:all .15s; }
.gsc-tab:hover  { color:#1E293B; background:#F8FAFC; }
.gsc-tab.active { color:#4F46E5; background:#EEF2FF; border-color:#C7D2FE #C7D2FE transparent; margin-bottom:-2px; }
.gsc-panel { display:none; } .gsc-panel.active { display:block; }
.stat-card { background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:16px 20px; }
.stat-val  { font-size:22px; font-weight:800; color:#1E293B; }
.stat-lbl  { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94A3B8; margin-bottom:4px; }
.badge-ok  { display:inline-block; background:#D1FAE5; color:#065F46; font-size:11px; font-weight:700; padding:2px 8px; border-radius:12px; }
.badge-warn{ display:inline-block; background:#FEF3C7; color:#92400E; font-size:11px; font-weight:700; padding:2px 8px; border-radius:12px; }
.badge-err { display:inline-block; background:#FEE2E2; color:#991B1B; font-size:11px; font-weight:700; padding:2px 8px; border-radius:12px; }
.code-block{ background:#1E293B; color:#E2E8F0; padding:14px 16px; border-radius:8px; font-family:monospace; font-size:13px; overflow-x:auto; white-space:pre-wrap; word-break:break-all; }
.info-box  { background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; padding:12px 16px; font-size:13px; color:#1E40AF; }
.warn-box  { background:#FFFBEB; border:1px solid #FDE68A; border-radius:8px; padding:12px 16px; font-size:13px; color:#92400E; }
.step-num  { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; background:#4F46E5; color:#fff; border-radius:50%; font-size:12px; font-weight:700; flex-shrink:0; }
</style>

<div class="page-header">
    <div>
        <h1>Google Search Console</h1>
        <p style="color:#64748B;font-size:14px;margin:0">Connect your site to Google, manage sitemaps, SEO settings &amp; indexing.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($hasSA): ?>
        <button class="btn btn-secondary btn-sm" onclick="testConnection()">&#128268; Test Connection</button>
        <?php endif; ?>
        <a href="/admin/search-console?action=preview_sitemap" target="_blank" class="btn btn-secondary btn-sm">&#128196; Preview Sitemap</a>
    </div>
</div>

<!-- Connection status bar -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <div class="stat-card" style="flex:1;min-width:160px;border-left:4px solid <?= $hasSA ? '#059669' : '#F59E0B' ?>">
        <div class="stat-lbl">API Connection</div>
        <div><?= $hasSA ? '<span class="badge-ok">&#10003; Configured</span>' : '<span class="badge-warn">&#9888; Not Set Up</span>' ?></div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px;border-left:4px solid <?= $hasSitemapFile ? '#059669' : '#94A3B8' ?>">
        <div class="stat-lbl">Sitemap File</div>
        <div><?php if ($hasSitemapFile): ?>
            <span class="badge-ok">&#10003; Exists</span>
            <?php if ($sitemapUrlCount): ?><span style="font-size:12px;color:#64748B;margin-left:6px"><?= (int)$sitemapUrlCount ?> URLs</span><?php endif; ?>
        <?php else: ?><span class="badge-warn">Not Generated</span><?php endif; ?></div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px;border-left:4px solid <?= $sitemapSubmittedAt ? '#059669' : '#94A3B8' ?>">
        <div class="stat-lbl">Last Submitted</div>
        <div style="font-size:13px;font-weight:600;color:#1E293B"><?= $sitemapSubmittedAt ? date('M j, Y H:i', strtotime($sitemapSubmittedAt)) : '—' ?></div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px;border-left:4px solid <?= ($verificationMeta || $hasVerifFile) ? '#059669' : '#94A3B8' ?>">
        <div class="stat-lbl">Site Verification</div>
        <div><?= ($verificationMeta || $hasVerifFile) ? '<span class="badge-ok">&#10003; Configured</span>' : '<span class="badge-warn">Not Set</span>' ?></div>
    </div>
    <div class="stat-card" style="flex:1;min-width:160px;border-left:4px solid <?= $hasRobotsFile ? '#059669' : '#94A3B8' ?>">
        <div class="stat-lbl">robots.txt</div>
        <div><?= $hasRobotsFile ? '<span class="badge-ok">&#10003; Generated</span>' : '<span class="badge-warn">Not Created</span>' ?></div>
    </div>
</div>

<!-- Tabs -->
<div class="gsc-tabs">
    <?php
    $tabs = [
        'setup'       => '&#9881; Setup & Credentials',
        'verify'      => '&#10003; Site Verification',
        'sitemap'     => '&#128196; Sitemap',
        'seo'         => '&#128269; SEO Settings',
        'indexing'    => '&#128279; URL Indexing',
        'performance' => '&#128202; Performance',
        'inspect'     => '&#128270; URL Inspection',
        'insights'    => '&#128200; Insights & Log',
    ];
    foreach ($tabs as $t => $label): ?>
    <a href="/admin/search-console?tab=<?= $t ?>" class="gsc-tab <?= $tab === $t ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php /* ───────────────── TAB: SETUP ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'setup' ? 'active' : '' ?>">
<div class="grid-2" style="gap:20px">
<div>
<div class="card">
    <div class="card-header"><span class="card-title">&#128272; Google API Credentials (Service Account)</span></div>
    <div class="card-body">
        <div class="info-box" style="margin-bottom:16px">
            <strong>How to set up:</strong>
            <ol style="margin:8px 0 0 16px;padding:0;font-size:13px">
                <li style="margin-bottom:4px">Go to <strong>console.cloud.google.com</strong> → Create/select a project</li>
                <li style="margin-bottom:4px">Enable <strong>Google Search Console API</strong> &amp; <strong>Google Indexing API</strong></li>
                <li style="margin-bottom:4px">Create a <strong>Service Account</strong> → Download the JSON key file</li>
                <li style="margin-bottom:4px">In Search Console → Settings → Users → Add the service account email as <em>Owner</em></li>
                <li>Paste or upload the JSON file below</li>
            </ol>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_credentials">

            <div class="form-group">
                <label style="font-weight:600">Site URL <span style="color:#EF4444">*</span></label>
                <input type="url" name="site_url" class="form-control"
                       value="<?= Helpers::e($siteUrl ?: (Config::get('config', 'app.url') ?? '')) ?>"
                       placeholder="https://yourdomain.com" required>
                <small style="color:#64748B">Must match exactly what you verified in Google Search Console (with or without www).</small>
            </div>

            <?php if ($hasSA): ?>
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px;margin-bottom:14px">
                <span class="badge-ok">&#10003; Service Account Connected</span>
                <div style="font-size:12px;color:#374151;margin-top:6px">
                    <strong>Email:</strong> <?= Helpers::e($saEmail ?? '') ?><br>
                    <strong>Project:</strong> <?= Helpers::e($saProject ?? 'n/a') ?>
                </div>
                <form method="POST" style="margin-top:8px">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="remove_credentials">
                    <button class="btn btn-secondary btn-sm" style="color:#DC2626;border-color:#FCA5A5"
                            onclick="return confirm('Remove service account credentials?')">&#128465; Remove Credentials</button>
                </form>
            </div>
            <div class="form-group">
                <label style="font-weight:600">Replace Service Account JSON (optional)</label>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label style="font-weight:600">Service Account JSON <span style="color:#EF4444">*</span></label>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label style="font-size:13px;color:#64748B">Upload JSON Key File</label>
                <input type="file" name="sa_json_file" class="form-control" accept=".json">
            </div>
            <div style="text-align:center;color:#94A3B8;font-size:12px;margin:4px 0">— or paste JSON content —</div>
            <div class="form-group">
                <label style="font-size:13px;color:#64748B">Paste JSON Content</label>
                <textarea name="sa_json_paste" class="form-control" rows="5"
                          placeholder='{"type": "service_account", "project_id": "...", "client_email": "...", "private_key": "..."}'
                          style="font-family:monospace;font-size:12px"></textarea>
            </div>
            <button class="btn btn-primary">&#128190; Save Credentials</button>
        </form>
    </div>
</div>
</div>

<div>
<div class="card mb-3">
    <div class="card-header"><span class="card-title">&#128268; Connection Test</span></div>
    <div class="card-body">
        <?php if ($hasSA): ?>
        <p style="font-size:13px;color:#64748B">Click to verify the service account can authenticate with Google APIs.</p>
        <button class="btn btn-secondary" onclick="testConnection()">&#9654; Test Connection</button>
        <div id="conn-result" style="display:none;margin-top:12px"></div>
        <?php else: ?>
        <div class="warn-box">Add Service Account credentials first.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">&#128203; Quick Actions</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
        <?php if ($hasSA): ?>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="add_site">
            <button class="btn btn-secondary" style="width:100%;text-align:left">
                &#10133; Add Site to Search Console
            </button>
        </form>
        <?php endif; ?>
        <a href="/admin/search-console?tab=sitemap" class="btn btn-secondary" style="text-align:left">
            &#128196; Generate &amp; Submit Sitemap
        </a>
        <a href="/admin/search-console?tab=indexing" class="btn btn-secondary" style="text-align:left">
            &#128279; Request URL Indexing
        </a>
    </div>
</div>
</div>
</div>
</div>

<?php /* ───────────────── TAB: VERIFICATION ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'verify' ? 'active' : '' ?>">
<div class="grid-2" style="gap:20px">
<div class="card">
    <div class="card-header"><span class="card-title">&#128214; Meta Tag Verification</span></div>
    <div class="card-body">
        <div class="info-box" style="margin-bottom:14px">
            In <strong>Google Search Console</strong> → Add property → select <em>HTML tag</em> method.
            Copy the meta tag Google gives you and paste the content value below.
            Then add the output tag to your site's <code>&lt;head&gt;</code> section.
        </div>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_verification">

            <div class="form-group">
                <label style="font-weight:600">Google Verification Meta Content</label>
                <input type="text" name="verification_meta" class="form-control"
                       value="<?= Helpers::e($verificationMeta) ?>"
                       placeholder="e.g. abc123xyz789...">
                <small style="color:#64748B">Only paste the <em>content</em> value, not the full tag.</small>
            </div>
            <?php if ($verificationMeta): ?>
            <div style="margin-bottom:14px">
                <label style="font-weight:600;font-size:13px">Tag to add to your &lt;head&gt;:</label>
                <div class="code-block">&lt;meta name="google-site-verification" content="<?= Helpers::e($verificationMeta) ?>"&gt;</div>
            </div>
            <?php endif; ?>

            <hr style="margin:16px 0">
            <div class="form-group">
                <label style="font-weight:600">Google Analytics Measurement ID</label>
                <input type="text" name="google_analytics_id" class="form-control"
                       value="<?= Helpers::e($gaId) ?>" placeholder="G-XXXXXXXXXX">
            </div>
            <div class="form-group">
                <label style="font-weight:600">Google Tag Manager Container ID</label>
                <input type="text" name="google_tag_manager_id" class="form-control"
                       value="<?= Helpers::e($gtmId) ?>" placeholder="GTM-XXXXXXX">
            </div>
            <button class="btn btn-primary">&#128190; Save Verification &amp; Tracking</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">&#128462; HTML File Verification</span></div>
    <div class="card-body">
        <div class="info-box" style="margin-bottom:14px">
            In <strong>Google Search Console</strong> → Add property → select <em>HTML file</em> method.
            Download the verification file Google provides and upload it here.
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="upload_verification_file">
            <div class="form-group">
                <label style="font-weight:600">Upload Google Verification HTML File</label>
                <input type="file" name="verification_file" class="form-control" accept=".html">
                <small style="color:#64748B">Filename must be <code>googleXXXXXXXXXXXXXXXX.html</code></small>
            </div>
            <?php if ($hasVerifFile): ?>
            <div class="badge-ok" style="margin-bottom:10px">&#10003; File uploaded: /<?= Helpers::e($verificationFile) ?></div><br>
            <?php endif; ?>
            <button class="btn btn-primary">&#9650; Upload File</button>
        </form>

        <?php if ($verificationMeta || $gaId || $gtmId): ?>
        <hr style="margin:20px 0">
        <div style="font-size:13px;font-weight:600;margin-bottom:8px">Add these tags to your &lt;head&gt; in the layout:</div>
        <div class="code-block"><?php
            if ($verificationMeta) echo htmlspecialchars('<meta name="google-site-verification" content="' . $verificationMeta . '">') . "\n";
            if ($gaId): ?>
<!-- Google Analytics -->
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=<?= Helpers::e($gaId) ?>"&gt;&lt;/script&gt;
&lt;script&gt;window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= Helpers::e($gaId) ?>');&lt;/script&gt;<?php
            endif;
            if ($gtmId): ?>
<!-- Google Tag Manager -->
&lt;script&gt;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&amp;l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= Helpers::e($gtmId) ?>');&lt;/script&gt;<?php
            endif; ?></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php /* ───────────────── TAB: SITEMAP ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'sitemap' ? 'active' : '' ?>">
<div class="grid-2" style="gap:20px">
<div class="card">
    <div class="card-header"><span class="card-title">&#128196; Sitemap Generator</span></div>
    <div class="card-body">
        <div class="info-box" style="margin-bottom:14px">
            This will generate a <strong>sitemap.xml</strong> in your site's root directory.
            It automatically includes your homepage, blog posts, and any custom URLs below.
        </div>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="generate_sitemap">
            <div class="form-group">
                <label style="font-weight:600">Custom URLs to include</label>
                <textarea name="sitemap_custom_urls" class="form-control" rows="5"
                          placeholder="https://yourdomain.com/page1&#10;https://yourdomain.com/page2"
                          style="font-family:monospace;font-size:12px"><?= Helpers::e($sitemapCustom) ?></textarea>
                <small style="color:#64748B">One URL per line. Automatically added to the auto-detected pages.</small>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <button class="btn btn-primary">&#9881; Generate sitemap.xml</button>
                <a href="/admin/search-console?action=preview_sitemap" target="_blank"
                   class="btn btn-secondary btn-sm">&#128065; Preview XML</a>
            </div>
        </form>

        <?php if ($hasSitemapFile): ?>
        <div style="margin-top:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px">
            <span class="badge-ok">&#10003; sitemap.xml exists</span>
            <?php if ($sitemapGeneratedAt): ?>
            <span style="font-size:12px;color:#374151;margin-left:8px">Generated: <?= date('M j, Y H:i', strtotime($sitemapGeneratedAt)) ?></span>
            <?php endif; ?>
            <?php if ($sitemapUrlCount): ?>
            <span style="font-size:12px;color:#374151;margin-left:8px">(<?= (int)$sitemapUrlCount ?> URLs)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">&#128228; Submit to Google</span></div>
    <div class="card-body">
        <?php if (!$hasSA): ?>
        <div class="warn-box">Set up API credentials in the <a href="/admin/search-console?tab=setup">Setup tab</a> first.</div>
        <?php elseif (!$hasSitemapFile): ?>
        <div class="warn-box">Generate the sitemap first using the panel on the left.</div>
        <?php else: ?>
        <p style="font-size:13px;color:#64748B">
            This will submit your sitemap directly to Google Search Console via the API.
            Google will crawl and index your sitemap automatically after submission.
        </p>
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px">
            <strong>Site URL:</strong> <?= Helpers::e($siteUrl) ?><br>
            <strong>Sitemap URL:</strong> <?= Helpers::e(rtrim($siteUrl, '/') . '/sitemap.xml') ?>
        </div>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="submit_sitemap">
            <button class="btn btn-primary">&#128228; Submit Sitemap to Google</button>
        </form>
        <?php if ($sitemapSubmittedAt): ?>
        <div style="margin-top:12px;font-size:12px;color:#059669">
            &#10003; Last submitted: <?= date('M j, Y H:i', strtotime($sitemapSubmittedAt)) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($hasSA): ?>
        <hr style="margin:16px 0">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px">Sitemaps in Google Search Console</div>
        <button class="btn btn-secondary btn-sm" onclick="loadSitemaps()">&#8635; Load from Google</button>
        <div id="sitemaps-list" style="margin-top:10px"></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php /* ───────────────── TAB: SEO SETTINGS ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'seo' ? 'active' : '' ?>">
<div class="card">
    <div class="card-header"><span class="card-title">&#128269; Global SEO Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_seo">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600">Default Meta Title</label>
                    <input type="text" name="meta_title" class="form-control"
                           value="<?= Helpers::e($metaTitle) ?>"
                           maxlength="70"
                           placeholder="Your Site Name — Short Description">
                    <small style="color:#64748B">Recommended: 50–70 characters. Current: <span id="title-len"><?= strlen($metaTitle) ?></span></small>
                </div>
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600">Default Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-control"
                           value="<?= Helpers::e($metaKeywords) ?>"
                           placeholder="affiliate, tracking, CPA network">
                    <small style="color:#64748B">Comma-separated. Modern SEO: less critical but still useful.</small>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight:600">Default Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="3"
                          maxlength="160"
                          placeholder="A concise description of your site for search engine results pages."><?= Helpers::e($metaDescription) ?></textarea>
                <small style="color:#64748B">Recommended: 120–160 characters. Current: <span id="desc-len"><?= strlen($metaDescription) ?></span></small>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600">Search Engine Indexing</label>
                    <select name="robots_index" class="form-control">
                        <option value="1" <?= $robotsIndex !== '0' ? 'selected' : '' ?>>Allow Indexing (index)</option>
                        <option value="0" <?= $robotsIndex === '0' ? 'selected' : '' ?>>Block Indexing (noindex)</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600">Link Following</label>
                    <select name="robots_follow" class="form-control">
                        <option value="1" <?= $robotsFollow !== '0' ? 'selected' : '' ?>>Follow Links (follow)</option>
                        <option value="0" <?= $robotsFollow === '0' ? 'selected' : '' ?>>No Follow (nofollow)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight:600">Robots.txt Custom Disallow Paths</label>
                <textarea name="robots_disallow" class="form-control" rows="5"
                          style="font-family:monospace;font-size:13px"
                          placeholder="/admin/&#10;/api/&#10;/tracking/"><?= Helpers::e($robotsDisallow) ?></textarea>
                <small style="color:#64748B">One path per line. Leave empty to allow all or block all based on indexing setting above.</small>
            </div>

            <?php if ($hasRobotsFile): ?>
            <div style="margin-bottom:14px">
                <label style="font-weight:600;font-size:13px">Current robots.txt</label>
                <div class="code-block"><?= Helpers::e(file_get_contents(BASE_PATH . '/robots.txt') ?: '') ?></div>
            </div>
            <?php endif; ?>

            <button class="btn btn-primary">&#128190; Save SEO Settings &amp; Update robots.txt</button>
        </form>

        <?php if ($metaTitle || $metaDescription): ?>
        <hr style="margin:20px 0">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px">SERP Preview</div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:16px;max-width:600px;font-family:Arial,sans-serif">
            <div style="font-size:18px;color:#1a0dab;font-weight:400;margin-bottom:2px"><?= Helpers::e($metaTitle ?: 'Page Title') ?></div>
            <div style="font-size:13px;color:#006621;margin-bottom:4px"><?= Helpers::e(rtrim($siteUrl ?: 'https://yoursite.com', '/') . '/') ?></div>
            <div style="font-size:13px;color:#545454"><?= Helpers::e($metaDescription ?: 'Page description will appear here...') ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php /* ───────────────── TAB: INDEXING ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'indexing' ? 'active' : '' ?>">
<div class="grid-2" style="gap:20px">
<div class="card">
    <div class="card-header"><span class="card-title">&#128279; Request URL Indexing</span></div>
    <div class="card-body">
        <?php if (!$hasSA): ?>
        <div class="warn-box" style="margin-bottom:14px">Set up API credentials in the <a href="/admin/search-console?tab=setup">Setup tab</a> first.</div>
        <?php else: ?>
        <div class="info-box" style="margin-bottom:14px">
            Uses the <strong>Google Indexing API</strong> to request immediate crawling of specific URLs.
            Best used for new or updated pages. Google may not honour every request immediately.
            <br><br>
            <strong>Note:</strong> The Indexing API officially supports pages with <code>JobPosting</code> or <code>BroadcastEvent</code>
            structured data. For other pages, this sends a crawl hint that Google may process.
        </div>
        <?php endif; ?>
        <!-- The form (and its textarea) is always rendered so the Quick Index "+ Add" /
             "+ Add All to Form" buttons can populate it regardless of API setup state.
             The submit button is the only piece gated on API credentials. -->
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="request_indexing">
            <div class="form-group">
                <label style="font-weight:600">URLs to Index</label>
                <textarea name="index_urls" class="form-control" rows="8"
                          style="font-family:monospace;font-size:12px"
                          placeholder="https://yourdomain.com/&#10;https://yourdomain.com/blog/post-slug&#10;https://yourdomain.com/page"></textarea>
                <small style="color:#64748B">One URL per line. Max ~200 per day via Indexing API.</small>
            </div>
            <div class="form-group">
                <label style="font-weight:600">Request Type</label>
                <select name="index_type" class="form-control" <?= $hasSA ? '' : 'disabled' ?>>
                    <option value="URL_UPDATED">URL_UPDATED — New or updated page (index it)</option>
                    <option value="URL_DELETED">URL_DELETED — Page removed (de-index it)</option>
                </select>
            </div>
            <?php if ($hasSA): ?>
            <button class="btn btn-primary">&#128228; Submit Indexing Requests</button>
            <?php else: ?>
            <button type="button" class="btn btn-secondary" disabled title="Set up API credentials first">&#128274; Set up API to submit</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">&#128204; Quick Index: Key Pages</span></div>
    <div class="card-body">
        <?php
        $base = rtrim($siteUrl ?: (Config::get('config', 'app.url') ?? ''), '/');
        $keyPages = array_filter([
            $base . '/',
            $base . '/register',
        ]);
        try {
            $posts = Database::fetchAll("SELECT slug FROM blog_posts WHERE status='published' ORDER BY created_at DESC LIMIT 10");
            foreach ($posts as $p) $keyPages[] = $base . '/blog/' . $p['slug'];
        } catch (\Throwable $_e) {}
        ?>
        <p style="font-size:13px;color:#64748B;margin-bottom:12px">
            Click to auto-populate the indexing form with your key pages:
        </p>
        <div style="display:flex;flex-direction:column;gap:6px">
            <?php foreach ($keyPages as $kp): ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:13px">
                <code style="flex:1;color:#4F46E5;word-break:break-all"><?= Helpers::e($kp) ?></code>
                <button type="button" class="btn btn-secondary btn-sm"
                        onclick="addToIndex(<?= json_encode($kp) ?>, this)">+ Add</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-secondary btn-sm" style="margin-top:12px;width:100%"
                onclick="addAllToIndex()">&#43; Add All to Form</button>
    </div>
</div>
</div>
</div>

<?php /* ───────────────── TAB: PERFORMANCE ─────────────────
   Live Google Search Console "Performance" data — clicks, impressions,
   CTR and average position, switchable by dimension (top queries / pages
   / countries / devices). All requests go through /admin/search-console
   ?action=analytics which uses the cached access token. */ ?>
<div class="gsc-panel <?= $tab === 'performance' ? 'active' : '' ?>">
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <span class="card-title">&#128202; Search Performance</span>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;font-size:13px">
            <label>Dimension
                <select id="gscDim" class="form-control" style="height:32px;padding:2px 8px;font-size:12.5px;width:auto;display:inline-block">
                    <option value="query">Top queries</option>
                    <option value="page">Top pages</option>
                    <option value="country">Countries</option>
                    <option value="device">Devices</option>
                    <option value="date">By date</option>
                </select>
            </label>
            <label>Range
                <select id="gscDays" class="form-control" style="height:32px;padding:2px 8px;font-size:12.5px;width:auto;display:inline-block">
                    <option value="7">Last 7d</option>
                    <option value="28" selected>Last 28d</option>
                    <option value="90">Last 90d</option>
                </select>
            </label>
            <button type="button" id="gscPerfReload" class="btn btn-primary btn-sm">↻ Refresh</button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!$hasSA): ?>
        <div class="alert alert-warning">&#9888; Connect a service account on the <strong>Setup</strong> tab before fetching analytics.</div>
        <?php else: ?>
        <div id="gscPerfStatus" style="font-size:13px;color:#64748B;margin-bottom:12px">Loading…</div>
        <div style="overflow-x:auto">
            <table class="table" id="gscPerfTable" style="width:100%;font-size:13px">
                <thead>
                    <tr style="background:#F8FAFC">
                        <th id="gscPerfKeyHead" style="padding:10px 12px;text-align:left">Query</th>
                        <th style="padding:10px 12px;text-align:right">Clicks</th>
                        <th style="padding:10px 12px;text-align:right">Impressions</th>
                        <th style="padding:10px 12px;text-align:right">CTR</th>
                        <th style="padding:10px 12px;text-align:right">Position</th>
                    </tr>
                </thead>
                <tbody id="gscPerfBody">
                    <tr><td colspan="5" style="padding:24px;text-align:center;color:#9CA3AF">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div style="font-size:11px;color:#94A3B8;margin-top:8px">
            Google's data is typically delayed by 1-3 days. End date is set to yesterday automatically.
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php /* ───────────────── TAB: URL INSPECTION ─────────────────
   Calls Google's URL Inspection API (urlInspection.index.inspect) for a
   single URL on the configured property. Returns coverage state, last
   crawl time, indexing verdict, and the canonical URL Google picked. */ ?>
<div class="gsc-panel <?= $tab === 'inspect' ? 'active' : '' ?>">
<div class="card">
    <div class="card-header"><span class="card-title">&#128270; URL Inspection</span></div>
    <div class="card-body">
        <?php if (!$hasSA): ?>
        <div class="alert alert-warning">&#9888; Connect a service account on the <strong>Setup</strong> tab to use URL Inspection.</div>
        <?php else: ?>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
            <input type="url" id="gscInspectUrl" class="form-control" placeholder="https://yourdomain.com/some/page" style="flex:1;min-width:280px">
            <button type="button" id="gscInspectBtn" class="btn btn-primary btn-sm">Inspect →</button>
        </div>
        <div id="gscInspectStatus" style="font-size:13px;color:#64748B;margin-bottom:10px"></div>
        <div id="gscInspectResult" style="display:none;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px 16px;font-size:13px"></div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php /* ───────────────── TAB: INSIGHTS ───────────────── */ ?>
<div class="gsc-panel <?= $tab === 'insights' ? 'active' : '' ?>">
<div class="card mb-3">
    <div class="card-header"><span class="card-title">&#128200; System Status &amp; SEO Health</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:16px">
            <?php
            $checks = [
                ['label' => 'sitemap.xml', 'ok' => $hasSitemapFile, 'hint' => $hasSitemapFile ? 'File exists' : 'Not generated'],
                ['label' => 'robots.txt',  'ok' => $hasRobotsFile,  'hint' => $hasRobotsFile ? 'File exists' : 'Not created'],
                ['label' => 'Meta Tag Set','ok' => (bool)$verificationMeta, 'hint' => $verificationMeta ? 'Set' : 'Not configured'],
                ['label' => 'Verif. File', 'ok' => $hasVerifFile, 'hint' => $hasVerifFile ? 'Uploaded' : 'Not uploaded'],
                ['label' => 'API Creds',   'ok' => $hasSA, 'hint' => $hasSA ? 'Connected' : 'Not configured'],
                ['label' => 'Sitemap Sent','ok' => (bool)$sitemapSubmittedAt, 'hint' => $sitemapSubmittedAt ? date('M j Y', strtotime($sitemapSubmittedAt)) : 'Not submitted'],
                ['label' => 'Google Analytics', 'ok' => (bool)$gaId, 'hint' => $gaId ?: 'Not set'],
                ['label' => 'Meta Title',  'ok' => (bool)$metaTitle, 'hint' => $metaTitle ? mb_substr($metaTitle,0,30).'...' : 'Not set'],
                ['label' => 'Meta Desc',   'ok' => (bool)$metaDescription, 'hint' => $metaDescription ? 'Set ('.strlen($metaDescription).' chars)' : 'Not set'],
                ['label' => 'Indexing Allowed', 'ok' => $robotsIndex !== '0', 'hint' => $robotsIndex !== '0' ? 'Enabled' : 'Blocked (noindex)'],
            ];
            foreach ($checks as $c): ?>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94A3B8;margin-bottom:4px">
                    <?= $c['label'] ?>
                </div>
                <?php if ($c['ok']): ?>
                <span class="badge-ok">&#10003; <?= Helpers::e($c['hint']) ?></span>
                <?php else: ?>
                <span class="badge-warn">&#9888; <?= Helpers::e($c['hint']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php
        $score = count(array_filter($checks, fn($c) => $c['ok']));
        $total = count($checks);
        $pct   = round($score / $total * 100);
        $color = $pct >= 80 ? '#059669' : ($pct >= 50 ? '#D97706' : '#DC2626');
        ?>
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="font-size:32px;font-weight:800;color:<?= $color ?>"><?= $pct ?>%</div>
                <div>
                    <div style="font-weight:700">SEO Setup Score</div>
                    <div style="font-size:13px;color:#64748B"><?= $score ?> of <?= $total ?> checks passed</div>
                </div>
                <div style="flex:1;background:#E2E8F0;border-radius:99px;height:10px;overflow:hidden">
                    <div style="width:<?= $pct ?>%;background:<?= $color ?>;height:100%;border-radius:99px;transition:width .5s"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">&#128204; Indexing Request Log</span>
        <span style="font-size:12px;color:#64748B">Last 30 requests</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($indexingLog)): ?>
        <div style="padding:20px;text-align:center;color:#94A3B8;font-size:13px">No indexing requests yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="table" style="font-size:13px">
            <thead><tr>
                <th>URL</th>
                <th>Type</th>
                <th>Status</th>
                <th>Date</th>
            </tr></thead>
            <tbody>
            <?php foreach ($indexingLog as $log): ?>
            <tr>
                <td style="max-width:360px;word-break:break-all;font-size:12px;font-family:monospace">
                    <?= Helpers::e($log['url']) ?>
                </td>
                <td>
                    <span style="font-size:11px;font-weight:600;color:<?= $log['type']==='URL_UPDATED' ? '#4F46E5' : '#DC2626' ?>">
                        <?= Helpers::e($log['type']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($log['status'] === 'submitted'): ?>
                    <span class="badge-ok">&#10003; Submitted</span>
                    <?php else: ?>
                    <span class="badge-err" title="<?= Helpers::e($log['response'] ?? '') ?>">&#10007; Failed</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;color:#64748B"><?= date('M j, H:i', strtotime($log['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── API Call Log — written by GoogleSearchConsole::log() on every outbound
     request. Surfaces auth failures, HTTP non-200s and cURL errors so admins
     can diagnose without SSH access. ─────────────────────────────────────── -->
<div class="card mt-3">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <span class="card-title">&#128202; Google API Call Log <span style="font-size:11px;color:#94A3B8;font-weight:400;margin-left:6px">last 50 outbound calls</span></span>
        <span style="font-size:11px;color:#94A3B8">Auto-recorded — surfaces auth, quota &amp; network issues</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($apiLog)): ?>
        <div style="padding:24px;text-align:center;color:#9CA3AF;font-size:13px">
            No API calls logged yet. Run <strong>Test connection</strong> or open the <strong>Performance</strong> tab to populate this list.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="table" style="width:100%;font-size:12.5px;border-collapse:collapse">
                <thead>
                    <tr style="background:#F8FAFC">
                        <th style="padding:9px 12px;text-align:left;width:140px">Time</th>
                        <th style="padding:9px 12px;text-align:left;width:80px">Op</th>
                        <th style="padding:9px 12px;text-align:left;width:60px">Method</th>
                        <th style="padding:9px 12px;text-align:left">URL</th>
                        <th style="padding:9px 12px;text-align:center;width:80px">HTTP</th>
                        <th style="padding:9px 12px;text-align:left">Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiLog as $row):
                        $http = (int)($row['http_code'] ?? 0);
                        $err  = trim((string)($row['error_message'] ?? ''));
                        $ok   = $http >= 200 && $http < 300 && $err === '';
                        $httpColor = $ok ? '#059669' : ($http >= 400 ? '#DC2626' : '#D97706');
                        // Strip the long shared prefix so the URL column stays readable.
                        $urlShort = preg_replace('#^https?://[^/]+#', '', (string)$row['url']);
                    ?>
                    <tr style="border-top:1px solid #F1F5F9">
                        <td style="padding:8px 12px;color:#64748B;white-space:nowrap"><?= Helpers::e(date('M j H:i:s', strtotime((string)$row['created_at']))) ?></td>
                        <td style="padding:8px 12px"><span style="display:inline-block;padding:2px 7px;background:#EEF2FF;color:#4F46E5;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase"><?= Helpers::e((string)$row['op']) ?></span></td>
                        <td style="padding:8px 12px;color:#475569;font-family:monospace;font-size:11.5px"><?= Helpers::e((string)$row['method']) ?></td>
                        <td style="padding:8px 12px;color:#0F172A;font-family:monospace;font-size:11.5px;max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e((string)$row['url']) ?>"><?= Helpers::e($urlShort ?: (string)$row['url']) ?></td>
                        <td style="padding:8px 12px;text-align:center;font-weight:700;color:<?= $httpColor ?>"><?= $http ?: '—' ?></td>
                        <td style="padding:8px 12px;color:<?= $err ? '#DC2626' : '#94A3B8' ?>;font-size:11.5px;max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($err) ?>"><?= $err !== '' ? Helpers::e($err) : 'OK' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasSA && $_lastApiError !== ''): ?>
<div class="alert alert-warning mt-3" style="display:flex;align-items:flex-start;gap:10px">
    <span style="font-size:18px;line-height:1">&#9888;&#65039;</span>
    <div>
        <strong>Most recent API error:</strong>
        <div style="margin-top:4px;font-family:monospace;font-size:12px;color:#92400E"><?= Helpers::e($_lastApiError) ?></div>
        <div style="margin-top:6px;font-size:11.5px;color:#78350F">Re-run <strong>Test connection</strong> on the Setup tab to confirm the fix.</div>
    </div>
</div>
<?php endif; ?>

</div>

<script>
// ── Tab quick-links work via URL ?tab= parameter ──────────────────────────

// ── Connection test ───────────────────────────────────────────────────────
function testConnection() {
    const btn = event.currentTarget;
    btn.disabled = true; btn.textContent = 'Testing…';
    const res = document.getElementById('conn-result');
    fetch('/admin/search-console?action=test_connection')
        .then(r => r.json())
        .then(d => {
            res.style.display = 'block';
            res.innerHTML = d.ok
                ? `<div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:8px;padding:12px;font-size:13px;color:#065F46">
                     &#10003; <strong>${d.msg}</strong>
                     ${d.sites && d.sites.length ? '<br>Sites: ' + d.sites.join(', ') : ''}
                   </div>`
                : `<div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:12px;font-size:13px;color:#991B1B">
                     &#10007; ${d.msg}
                   </div>`;
        })
        .catch(() => { res.style.display='block'; res.innerHTML='<div style="color:#DC2626">Request failed.</div>'; })
        .finally(() => { btn.disabled=false; btn.textContent='▶ Test Connection'; });
}

// ── Load sitemaps from Google ─────────────────────────────────────────────
function loadSitemaps() {
    const el = document.getElementById('sitemaps-list');
    el.innerHTML = '<span style="color:#64748B;font-size:13px">Loading…</span>';
    fetch('/admin/search-console?action=list_sitemaps')
        .then(r => r.json())
        .then(d => {
            if (!d.ok || !d.sitemaps || !d.sitemaps.length) {
                el.innerHTML = '<span style="color:#64748B;font-size:13px">No sitemaps found in Google Search Console.</span>';
                return;
            }
            el.innerHTML = d.sitemaps.map(s => `
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:8px 12px;margin-bottom:6px;font-size:12px;display:flex;align-items:center;gap:8px">
                    <span style="flex:1;font-family:monospace;color:#4F46E5;word-break:break-all">${s.path}</span>
                    <span style="color:#64748B">${s.warnings||0} warn, ${s.errors||0} err</span>
                    <button onclick="delSitemap(${JSON.stringify(s.path)})" style="font-size:11px;padding:2px 8px;border:1px solid #FCA5A5;color:#DC2626;background:#FEF2F2;border-radius:4px;cursor:pointer">Delete</button>
                </div>`).join('');
        })
        .catch(() => { el.innerHTML = '<span style="color:#DC2626;font-size:13px">Failed to load.</span>'; });
}

function delSitemap(url) {
    if (!confirm('Delete this sitemap from Google Search Console?\n' + url)) return;
    fetch('/admin/search-console?action=delete_sitemap&sitemap_url=' + encodeURIComponent(url))
        .then(r => r.json())
        .then(d => { alert(d.ok ? 'Deleted.' : 'Failed to delete.'); loadSitemaps(); });
}

// ── Indexing quick-add ────────────────────────────────────────────────────
function addToIndex(url, btn) {
    const ta = document.querySelector('textarea[name="index_urls"]');
    if (!ta) { alert('URL Indexing form not found on this page.'); return; }
    const lines = ta.value.trim() ? ta.value.trim().split('\n') : [];
    if (!lines.includes(url)) { lines.push(url); ta.value = lines.join('\n'); }
    // Visual feedback on the clicked button (if provided)
    if (btn && btn.tagName === 'BUTTON') {
        const orig = btn.innerHTML;
        btn.innerHTML = '&#10003; Added';
        btn.disabled = true;
        setTimeout(function(){ btn.innerHTML = orig; btn.disabled = false; }, 1400);
    }
    // Make sure the user actually sees the textarea (scroll to it)
    ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function addAllToIndex() {
    document.querySelectorAll('button[onclick^="addToIndex("]').forEach(b => {
        // Extract the JSON-encoded URL (1st arg) from the inline handler.
        // Pattern: addToIndex("https://...", this) → capture the quoted string.
        const m = b.getAttribute('onclick').match(/addToIndex\((".*?")\s*(?:,|\))/);
        if (m) {
            try { addToIndex(JSON.parse(m[1]), b); } catch (_e) { b.click(); }
        } else {
            b.click();
        }
    });
}

// ── Character counter for meta fields ─────────────────────────────────────
(function() {
    const t = document.querySelector('input[name="meta_title"]');
    const d = document.querySelector('textarea[name="meta_description"]');
    if (t) t.addEventListener('input', () => { document.getElementById('title-len').textContent = t.value.length; });
    if (d) d.addEventListener('input', () => { document.getElementById('desc-len').textContent = d.value.length; });
})();

// ──────────────────────────────────────────────────────────────────────
// Performance tab — fetches /admin/search-console?action=analytics and
// renders the row set into the table. Errors are surfaced clearly so the
// admin sees Google's actual response, not a generic spinner.
// ──────────────────────────────────────────────────────────────────────
(function(){
    if (!document.getElementById('gscPerfTable')) return;
    var dimSel  = document.getElementById('gscDim');
    var daysSel = document.getElementById('gscDays');
    var btn     = document.getElementById('gscPerfReload');
    var body    = document.getElementById('gscPerfBody');
    var status  = document.getElementById('gscPerfStatus');
    var keyHead = document.getElementById('gscPerfKeyHead');
    var labels  = { query:'Query', page:'Page', country:'Country', device:'Device', date:'Date' };
    var fmtPct  = function(n){ return (Number(n)||0).toFixed(2) + '%'; };
    var fmtNum  = function(n){ return (Number(n)||0).toLocaleString(); };

    function load(){
        var dim = dimSel.value, days = daysSel.value;
        keyHead.textContent = labels[dim] || 'Key';
        status.textContent  = 'Querying Google Search Console…';
        body.innerHTML      = '<tr><td colspan="5" style="padding:24px;text-align:center;color:#9CA3AF">Loading…</td></tr>';
        fetch('/admin/search-console?action=analytics&dimension=' + encodeURIComponent(dim) + '&days=' + encodeURIComponent(days) + '&rows=100')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.ok) {
                    status.innerHTML = '<span style="color:#DC2626">⚠ ' + (d.msg || 'Failed') + (d.http ? ' (HTTP ' + d.http + ')' : '') + '</span>';
                    body.innerHTML = '<tr><td colspan="5" style="padding:24px;text-align:center;color:#9CA3AF">No data.</td></tr>';
                    return;
                }
                status.innerHTML = '<span style="color:#059669">✓</span> ' + d.rows.length + ' rows · property: <code>' + d.site_url + '</code> · last ' + d.days + ' days';
                if (!d.rows.length) {
                    body.innerHTML = '<tr><td colspan="5" style="padding:24px;text-align:center;color:#9CA3AF">No data available for this range.</td></tr>';
                    return;
                }
                body.innerHTML = d.rows.map(function(r){
                    return '<tr style="border-top:1px solid #F1F5F9">' +
                        '<td style="padding:9px 12px;color:#0F172A;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (r.key || '—') + '</td>' +
                        '<td style="padding:9px 12px;text-align:right;font-weight:700">' + fmtNum(r.clicks) + '</td>' +
                        '<td style="padding:9px 12px;text-align:right">' + fmtNum(r.impressions) + '</td>' +
                        '<td style="padding:9px 12px;text-align:right">' + fmtPct(r.ctr) + '</td>' +
                        '<td style="padding:9px 12px;text-align:right">' + (Number(r.position)||0).toFixed(1) + '</td>' +
                    '</tr>';
                }).join('');
            })
            .catch(function(err){
                status.innerHTML = '<span style="color:#DC2626">⚠ Network error: ' + (err && err.message ? err.message : 'unknown') + '</span>';
                body.innerHTML = '<tr><td colspan="5" style="padding:24px;text-align:center;color:#9CA3AF">—</td></tr>';
            });
    }
    if (btn)  btn.addEventListener('click', load);
    if (dimSel)  dimSel.addEventListener('change', load);
    if (daysSel) daysSel.addEventListener('change', load);
    // Auto-fire when the Performance tab is opened.
    if (/\btab=performance\b/.test(location.search)) load();
})();

// ──────────────────────────────────────────────────────────────────────
// URL Inspection tab — calls the URL Inspection API for a single URL.
// ──────────────────────────────────────────────────────────────────────
(function(){
    var btn    = document.getElementById('gscInspectBtn');
    if (!btn) return;
    var input  = document.getElementById('gscInspectUrl');
    var status = document.getElementById('gscInspectStatus');
    var out    = document.getElementById('gscInspectResult');

    btn.addEventListener('click', function(){
        var url = (input.value || '').trim();
        if (!url) { status.innerHTML = '<span style="color:#DC2626">⚠ Enter a URL first.</span>'; out.style.display = 'none'; return; }
        status.textContent = 'Inspecting…';
        out.style.display = 'none';
        out.innerHTML = '';
        fetch('/admin/search-console?action=url_inspect&url=' + encodeURIComponent(url))
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.ok) {
                    status.innerHTML = '<span style="color:#DC2626">⚠ ' + (d.msg || 'Failed') + (d.http ? ' (HTTP ' + d.http + ')' : '') + '</span>';
                    return;
                }
                status.innerHTML = '<span style="color:#059669">✓ Inspection complete</span>';
                var res = d.result || {};
                var idx = res.indexStatusResult || {};
                function row(label, value){
                    if (!value) return '';
                    return '<tr><td style="padding:6px 0;color:#64748B;width:40%">' + label + '</td><td style="padding:6px 0;font-weight:600;color:#0F172A">' + value + '</td></tr>';
                }
                out.innerHTML = '<table style="width:100%;border-collapse:collapse">' +
                    row('Verdict',           idx.verdict) +
                    row('Coverage state',    idx.coverageState) +
                    row('Robots.txt state',  idx.robotsTxtState) +
                    row('Indexing state',    idx.indexingState) +
                    row('Page fetch state',  idx.pageFetchState) +
                    row('Last crawl time',   idx.lastCrawlTime) +
                    row('Google canonical',  idx.googleCanonical) +
                    row('User canonical',    idx.userCanonical) +
                    row('Crawled as',        idx.crawledAs) +
                    '</table>' +
                    '<div style="margin-top:10px;font-size:11.5px;color:#94A3B8">Inspection link: <a href="' + (res.inspectionResultLink || '#') + '" target="_blank">Open in Search Console</a></div>';
                out.style.display = 'block';
            })
            .catch(function(err){
                status.innerHTML = '<span style="color:#DC2626">⚠ Network error: ' + (err && err.message ? err.message : 'unknown') + '</span>';
            });
    });
})();
</script>
