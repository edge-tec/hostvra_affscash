<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Settings</h1><p>Configure your platform</p></div>
</div>

<!-- Tab navigation — responsive, scrollable on mobile -->
<style>
.settings-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 22px;
    border-bottom: 2px solid #E2E8F0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    flex-wrap: nowrap;
}
.settings-tabs::-webkit-scrollbar { display: none; }
.settings-tab {
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    color: #64748B;
    white-space: nowrap;
    flex-shrink: 0;
    transition: color .15s;
}
.settings-tab:hover { color: #4F46E5; text-decoration: none; }
.settings-tab.active { color: #4F46E5; border-bottom-color: #4F46E5; }

/* Mobile: show a select dropdown instead of tabs */
.settings-tab-select {
    display: none;
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #CBD5E1;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #1E293B;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 12px center;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    margin-bottom: 18px;
}
.settings-tab-select:focus { outline: none; border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }

@media (max-width: 640px) {
    .settings-tabs { display: none; }
    .settings-tab-select { display: block; }
}
@media (max-width: 900px) {
    .settings-tab { padding: 9px 12px; font-size: 12px; }
}
</style>

<!-- Desktop: scrollable tab bar -->
<div class="settings-tabs">
    <?php
    $tabs = ['general'=>'General','branding'=>'Branding','security'=>'Security','inactivity'=>'Affiliate Inactivity','traffic'=>'Traffic','vpn_detection'=>'VPN & Proxy','commission'=>'Commission','conversions'=>'Conversions','budget_system'=>'Budget System','domains'=>'Tracking Domains','email'=>'Email / SMTP','shortener'=>'Link Shortener','mobile_app'=>'Mobile App','notifications'=>'Notifications','fraud_reports'=>'Fraud Reports', 'space_engine'=>'3D Background'];
    foreach ($tabs as $key => $label):
    ?>
    <a href="/admin/settings?tab=<?= $key ?>"
       class="settings-tab <?= $activeTab === $key ? 'active' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Mobile: native select dropdown -->
<select class="settings-tab-select" onchange="location.href='/admin/settings?tab='+this.value">
    <?php foreach ($tabs as $key => $label): ?>
    <option value="<?= $key ?>" <?= $activeTab === $key ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
</select>

<!-- ─── GENERAL ─────────────────────────────────────── -->
<?php if ($activeTab === 'general'): ?>
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">General Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="general">
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="app_name" class="form-control" value="<?= Helpers::e($cfg['app']['name'] ?? 'AffiliateTracker') ?>" required>
                <div class="form-hint">Shown in browser title and sidebar logo</div>
            </div>
            <div class="form-group">
                <label>Site URL <span style="color:#94A3B8;font-weight:400">(Main Domain)</span></label>
                <input type="url" name="app_url" class="form-control" value="<?= Helpers::e($cfg['app']['url'] ?? '') ?>" placeholder="https://yourdomain.com">
                <div class="form-hint">Used for dashboard, landing pages, and all non-tracking URLs (no trailing slash)</div>
            </div>
            <div class="form-group">
                <label>Tracking Domain <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <input type="url" name="app_tracking_url" class="form-control" value="<?= Helpers::e($cfg['app']['tracking_url'] ?? '') ?>" placeholder="https://click.yourdomain.com">
                <div class="form-hint">Used exclusively for affiliate tracking links (/click/...). Leave blank to use Site URL.</div>
            </div>
            <div class="form-group">
                <label>Company Address <span style="color:#94A3B8;font-weight:400">(shown on invoices)</span></label>
                <input type="text" name="app_address" class="form-control" value="<?= Helpers::e($cfg['app']['address'] ?? '') ?>" placeholder="e.g. 123 Main St, New York, NY 10001, USA">
                <div class="form-hint">Printed on PDF invoices under the company name</div>
            </div>
            <div class="form-group">
                <label>Company Phone / Mobile <span style="color:#94A3B8;font-weight:400">(shown on invoices)</span></label>
                <input type="text" name="app_phone" class="form-control" value="<?= Helpers::e($cfg['app']['phone'] ?? '') ?>" placeholder="e.g. +1 (555) 123-4567">
                <div class="form-hint">Printed on PDF invoices under the company address</div>
            </div>
            <div class="form-group">
                <label>Timezone</label>
                <select name="timezone" class="form-control">
                    <?php
                    $timezones = DateTimeZone::listIdentifiers();
                    $current   = $cfg['app']['timezone'] ?? 'UTC';
                    foreach ($timezones as $tz):
                    ?>
                    <option value="<?= $tz ?>" <?= $tz === $current ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Default Platform Theme</label>
                <?php
                $rawTheme     = $cfg['app']['default_theme'] ?? 'light';
                $currentTheme = in_array($rawTheme, ['light','dark'], true) ? $rawTheme : 'light';
                ?>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:500;cursor:pointer">
                        <input type="radio" name="default_theme" value="light" <?= $currentTheme === 'light' ? 'checked' : '' ?>>
                        Light Mode
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:500;cursor:pointer">
                        <input type="radio" name="default_theme" value="dark"  <?= $currentTheme === 'dark'  ? 'checked' : '' ?>>
                        Dark Mode
                    </label>
                </div>
                <div class="form-hint">Applied to new accounts and to users who have not picked a theme yet. Individual users can override this from their topbar picker at any time.</div>
            </div>
            <div class="form-group">
                <label>Footer Copyright Text</label>
                <input type="text" name="footer_copyright" class="form-control"
                       value="<?= Helpers::e($cfg['app']['footer_copyright'] ?? '') ?>"
                       placeholder="© <?= date('Y') ?> YourCompany. All rights reserved.">
                <div class="form-hint">Shown at the bottom of all pages</div>
            </div>
            <div class="form-group">
                <label>Global Translate Widget</label>
                <select name="enable_gtranslate" class="form-control">
                    <option value="1" <?= ($cfg['app']['enable_gtranslate'] ?? 1) == 1 ? 'selected' : '' ?>>Enabled</option>
                    <option value="0" <?= ($cfg['app']['enable_gtranslate'] ?? 1) == 0 ? 'selected' : '' ?>>Disabled</option>
                </select>
                <div class="form-hint">Shows a floating translation widget (GTranslate) at the bottom left of all pages.</div>
            </div>

            <hr style="margin:24px 0;border-color:#E2E8F0">
            <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:16px;text-transform:uppercase;letter-spacing:.05em">
                Landing Page Contact Details
            </div>

            <div class="form-group">
                <label>Affiliate Manager Name</label>
                <input type="text" name="app_manager_name" class="form-control"
                       value="<?= Helpers::e($cfg['app']['manager_name'] ?? '') ?>"
                       placeholder="e.g. John Smith">
                <div class="form-hint">Displayed on the landing page team / contact section</div>
            </div>
            <div class="form-group">
                <label>Affiliate Manager Email</label>
                <input type="email" name="app_contact_email" class="form-control"
                       value="<?= Helpers::e($cfg['app']['contact_email'] ?? '') ?>"
                       placeholder="affiliate@yourdomain.com">
                <div class="form-hint">Shown as the Affiliate Manager email on the landing page</div>
            </div>
            <div class="form-group">
                <label>Support Email</label>
                <input type="email" name="app_support_email" class="form-control"
                       value="<?= Helpers::e($cfg['app']['support_email'] ?? '') ?>"
                       placeholder="support@yourdomain.com">
                <div class="form-hint">Shown as the Support Team email on the landing page</div>
            </div>
            <div class="form-group">
                <label>Telegram Handle</label>
                <div style="display:flex;align-items:center;gap:0">
                    <span style="background:#F1F5F9;border:1.5px solid #CBD5E1;border-right:none;border-radius:6px 0 0 6px;padding:8px 10px;font-size:13px;color:#64748B">@</span>
                    <input type="text" name="app_telegram_handle" class="form-control" style="border-radius:0 6px 6px 0;border-left:none"
                           value="<?= Helpers::e($cfg['app']['telegram_handle'] ?? '') ?>"
                           placeholder="yourtelegramhandle">
                </div>
                <div class="form-hint">Telegram username without the @ sign — used on landing page &amp; footer</div>
            </div>
            <div class="form-group">
                <label>Teams / Skype Invite URL</label>
                <input type="url" name="app_teams_skype_url" class="form-control"
                       value="<?= Helpers::e($cfg['app']['teams_skype_url'] ?? '') ?>"
                       placeholder="https://teams.live.com/l/invite/...">
                <div class="form-hint">Full Teams or Skype invite URL — shown in the contact section and footer</div>
            </div>

            <button type="submit" class="btn btn-primary">Save General Settings</button>
        </form>
    </div>
</div>

<!-- ─── BRANDING ─────────────────────────────────────── -->
<?php elseif ($activeTab === 'branding'): ?>
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Branding</span></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="branding">

            <!-- Logo -->
            <div class="form-group">
                <label>Site Logo</label>
                <?php $logo = $cfg['app']['logo'] ?? ''; ?>
                <?php if ($logo): ?>
                <div style="margin-bottom:10px;padding:12px;background:#F8FAFC;border-radius:6px;display:inline-flex;align-items:center;gap:12px">
                    <img src="<?= Helpers::e($logo) ?>" alt="Logo" style="max-height:48px;max-width:200px;object-fit:contain">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;color:#EF4444">
                        <input type="checkbox" name="clear_logo" value="1"> Remove logo
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/gif,image/svg+xml">
                <div class="form-hint">PNG, JPG, SVG or GIF · Max 2 MB · Recommended: 180×40 px</div>
            </div>

            <!-- Favicon -->
            <div class="form-group">
                <label>Favicon</label>
                <?php $favicon = $cfg['app']['favicon'] ?? ''; ?>
                <?php if ($favicon): ?>
                <div style="margin-bottom:10px;padding:12px;background:#F8FAFC;border-radius:6px;display:inline-flex;align-items:center;gap:12px">
                    <img src="<?= Helpers::e($favicon) ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;color:#EF4444">
                        <input type="checkbox" name="clear_favicon" value="1"> Remove favicon
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="favicon" class="form-control" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/gif">
                <div class="form-hint">ICO, PNG or GIF · Max 2 MB · Recommended: 32×32 px</div>
            </div>

            <!-- Login Page Logo -->
            <div class="form-group">
                <label>Login Page Logo</label>
                <div class="form-hint" style="margin-bottom:8px">Displayed on the login, register, and 2FA pages. If not set, the Site Logo above is used as fallback.</div>
                <?php $loginLogo = $cfg['app']['login_logo'] ?? ''; ?>
                <?php if ($loginLogo): ?>
                <div style="margin-bottom:10px;padding:12px;background:#F8FAFC;border-radius:6px;display:inline-flex;align-items:center;gap:12px">
                    <img src="<?= Helpers::e($loginLogo) ?>" alt="Login Logo" style="max-height:48px;max-width:200px;object-fit:contain">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;color:#EF4444">
                        <input type="checkbox" name="clear_login_logo" value="1"> Remove logo
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" name="login_logo" class="form-control" accept="image/png,image/jpeg,image/gif,image/svg+xml">
                <div class="form-hint">PNG, JPG, SVG or GIF · Max 2 MB · Recommended: 240×60 px</div>
            </div>

            <!-- White Logo Toggle -->
            <?php $loginLogoWhite = !empty($cfg['app']['login_logo_white']); ?>
            <div class="form-group">
                <label>Login Page Logo Color</label>
                <div class="form-hint" style="margin-bottom:10px">Enable if your logo is dark/colored and looks invisible on the login page's dark purple header. Converts the logo to white automatically.</div>
                <label style="display:inline-flex;align-items:center;gap:10px;cursor:pointer;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px 16px">
                    <input type="checkbox" name="login_logo_white" value="1" <?= $loginLogoWhite ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:#4F46E5">
                    <span style="font-size:13px;font-weight:600;color:var(--text)">Convert logo to white on login page</span>
                </label>
                <?php
                $_previewSrc = $cfg['app']['login_logo'] ?? ($cfg['app']['logo'] ?? '');
                if ($_previewSrc): ?>
                <div style="margin-top:10px;padding:14px 20px;background:linear-gradient(135deg,#4F46E5,#7C3AED);border-radius:8px;display:inline-flex;align-items:center;gap:16px">
                    <img src="<?= Helpers::e($_previewSrc) ?>" alt="Preview"
                         style="max-height:44px;max-width:180px;object-fit:contain;<?= $loginLogoWhite ? 'filter:brightness(0) invert(1)' : '' ?>">
                    <span style="font-size:11px;color:rgba(255,255,255,.65)">Preview on login background</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Dark Mode Logo Toggle -->
            <?php $darkLogoWhite = !isset($cfg['app']['dark_logo_white']) || $cfg['app']['dark_logo_white'] === '1'; ?>
            <div class="form-group">
                <label>Dark Mode Logo Color</label>
                <div class="form-hint" style="margin-bottom:10px">Enable if your logo is dark/colored and looks invisible when users switch to dark mode. Converts the site logo to white automatically — no separate dark-mode logo asset needed.</div>
                <label style="display:inline-flex;align-items:center;gap:10px;cursor:pointer;background:#F8FAFC;border:1px solid var(--border);border-radius:8px;padding:12px 16px">
                    <input type="checkbox" name="dark_logo_white" value="1" <?= $darkLogoWhite ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:#4F46E5">
                    <span style="font-size:13px;font-weight:600;color:var(--text)">Convert logo to white in dark mode</span>
                </label>
                <?php
                $_darkPreviewSrc = $cfg['app']['logo'] ?? '';
                if ($_darkPreviewSrc): ?>
                <div style="margin-top:10px;padding:14px 20px;background:#0E1526;border:1px solid rgba(148,163,184,.18);border-radius:8px;display:inline-flex;align-items:center;gap:16px">
                    <img src="<?= Helpers::e($_darkPreviewSrc) ?>" alt="Preview"
                         style="max-height:44px;max-width:180px;object-fit:contain;<?= $darkLogoWhite ? 'filter:brightness(0) invert(1)' : '' ?>">
                    <span style="font-size:11px;color:rgba(255,255,255,.65)">Preview on dark sidebar</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Dashboard Banner Style -->
            <?php $bannerStyle = $cfg['app']['dashboard_banner_style'] ?? (empty($cfg['app']['transparent_dashboard']) ? 'glass_purple' : 'transparent'); ?>
            <div class="form-group">
                <label>Dashboard Banner Style</label>
                <div class="form-hint" style="margin-bottom:10px">Choose the style for the top banner on all dashboards (Admin, Manager, Affiliate).</div>
                <select name="dashboard_banner_style" class="form-control" style="max-width:300px">
                    <option value="default" <?= $bannerStyle === 'default' ? 'selected' : '' ?>>Default (Solid Purple)</option>
                    <option value="transparent" <?= $bannerStyle === 'transparent' ? 'selected' : '' ?>>Fully Transparent</option>
                    <option value="glass" <?= $bannerStyle === 'glass' ? 'selected' : '' ?>>Transparent Glass</option>
                    <option value="glass_purple" <?= $bannerStyle === 'glass_purple' ? 'selected' : '' ?>>Purple Glass</option>
                </select>
            </div>

            <!-- Dashboard Card Style -->
            <?php $cardStyle = $cfg['app']['dashboard_card_style'] ?? 'aurora'; ?>
            <div class="form-group">
                <label>Dashboard Card Design</label>
                <div class="form-hint" style="margin-bottom:10px">Choose the KPI stat card design for all dashboards. Changes apply to Admin, Manager, and Affiliate dashboards.</div>
                <select name="dashboard_card_style" class="form-control" id="cardStyleSelect" style="max-width:300px" onchange="updateCardPreview(this.value)">
                    <option value="default" <?= $cardStyle === 'default' ? 'selected' : '' ?>>Default (Clean Border)</option>
                    <option value="gradient_glow" <?= $cardStyle === 'gradient_glow' ? 'selected' : '' ?>>Gradient Glow</option>
                    <option value="neon_glass" <?= $cardStyle === 'neon_glass' ? 'selected' : '' ?>>Neon Glass</option>
                    <option value="aurora" <?= $cardStyle === 'aurora' ? 'selected' : '' ?>>Aurora Premium</option>
                </select>
                <div id="cardStylePreview" style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;max-width:700px">
                </div>
            </div>
            <?php $trendStyle = $cfg['app']['trend_chart_style'] ?? 'neon_glow'; ?>
            <div class="form-group" style="margin-top:24px">
                <label>Performance Trend Style</label>
                <div class="form-hint" style="margin-bottom:10px">Choose the design for the main Performance Trend chart.</div>
                <select name="trend_chart_style" class="form-control" style="max-width:300px">
                    <option value="default" <?= $trendStyle === 'default' ? 'selected' : '' ?>>Default (Smooth Curve)</option>
                    <option value="straight" <?= $trendStyle === 'straight' ? 'selected' : '' ?>>Straight Lines (Sharp)</option>
                    <option value="stepped" <?= $trendStyle === 'stepped' ? 'selected' : '' ?>>Stepped Lines</option>
                    <option value="high_tech" <?= $trendStyle === 'high_tech' ? 'selected' : '' ?>>High-Tech (Heavy Glow)</option>
                    <option value="gradient_fill" <?= $trendStyle === 'gradient_fill' ? 'selected' : '' ?>>Gradient Fill (Deep)</option>
                    <option value="neon_glow" <?= $trendStyle === 'neon_glow' ? 'selected' : '' ?>>Neon Glow (Dark)</option>
                    <option value="minimal_dots" <?= $trendStyle === 'minimal_dots' ? 'selected' : '' ?>>Minimal Dots</option>
                    <option value="area_stacked" <?= $trendStyle === 'area_stacked' ? 'selected' : '' ?>>Stacked Area</option>
                    <option value="thin_sharp" <?= $trendStyle === 'thin_sharp' ? 'selected' : '' ?>>Thin Sharp</option>
                    <option value="bold_rounded" <?= $trendStyle === 'bold_rounded' ? 'selected' : '' ?>>Bold Rounded</option>
                </select>
            </div>
            
            <script>
            function updateCardPreview(style) {
                var wrap = document.getElementById('cardStylePreview');
                var cards = [
                    {label:'CLICKS', value:'1,234', color:'blue', accent:'#3B82F6'},
                    {label:'CONVERSIONS', value:'89', color:'green', accent:'#10B981'},
                    {label:'REVENUE', value:'$2,566', color:'purple', accent:'#8B5CF6'},
                    {label:'PROFIT', value:'$359', color:'emerald', accent:'#059669'},
                ];
                var html = '';
                cards.forEach(function(c) {
                    if (style === 'gradient_glow') {
                        html += '<div style="background:#fff;border:none;border-radius:16px;padding:16px 16px 16px 20px;position:relative;overflow:hidden;border-left:4px solid transparent;box-shadow:0 4px 16px rgba(0,0,0,.04)">' +
                            '<div style="position:absolute;top:0;left:0;bottom:0;width:4px;border-radius:16px 0 0 16px;background:linear-gradient(180deg,' + c.accent + ',' + c.accent + '99)"></div>' +
                            '<div style="position:absolute;top:-30%;right:-20%;width:80px;height:160%;border-radius:50%;opacity:.06;filter:blur(30px);background:' + c.accent + '"></div>' +
                            '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94A3B8;margin-bottom:4px">' + c.label + '</div>' +
                            '<div style="font-size:20px;font-weight:800;color:#0F172A">' + c.value + '</div>' +
                            '<div style="margin-top:5px;display:inline-flex;align-items:center;gap:2px;font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">▲ 12.5%</div>' +
                            '</div>';
                    } else if (style === 'neon_glass') {
                        html += '<div style="background:rgba(255,255,255,.7);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.4);border-radius:18px;padding:16px;position:relative;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.04)">' +
                            '<div style="position:absolute;bottom:0;left:15%;right:15%;height:3px;border-radius:0 0 18px 18px;filter:blur(1px);background:linear-gradient(90deg,transparent,' + c.accent + ',transparent)"></div>' +
                            '<div style="position:absolute;top:10px;right:12px;width:30px;height:30px;border-radius:10px;opacity:.10;background:' + c.accent + '"></div>' +
                            '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94A3B8;margin-bottom:4px">' + c.label + '</div>' +
                            '<div style="font-size:20px;font-weight:800;color:#0F172A">' + c.value + '</div>' +
                            '<div style="margin-top:5px;display:inline-flex;align-items:center;gap:2px;font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">▲ 12.5%</div>' +
                            '</div>';
                    } else if (style === 'aurora') {
                        var gradients = {blue:'linear-gradient(135deg,#1E3A5F,#2563EB)',green:'linear-gradient(135deg,#064E3B,#059669)',purple:'linear-gradient(135deg,#2E1065,#7C3AED)',emerald:'linear-gradient(135deg,#064E3B,#047857)'};
                        html += '<div style="background:' + gradients[c.color] + ';border:none;border-radius:18px;padding:16px;position:relative;overflow:hidden;box-shadow:0 8px 20px rgba(0,0,0,.15)">' +
                            '<div style="position:absolute;inset:0;border-radius:18px;opacity:.08;background:linear-gradient(135deg,#fff 0%,transparent 50%);pointer-events:none"></div>' +
                            '<div style="position:absolute;top:-15px;right:-15px;width:60px;height:60px;border-radius:50%;opacity:.15;filter:blur(15px);background:' + c.accent + '"></div>' +
                            '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.7);margin-bottom:4px">' + c.label + '</div>' +
                            '<div style="font-size:20px;font-weight:800;color:#fff">' + c.value + '</div>' +
                            '<div style="margin-top:5px;display:inline-flex;align-items:center;gap:2px;font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px;background:rgba(255,255,255,.18);color:#6EE7B7">▲ 12.5%</div>' +
                            '</div>';
                    } else {
                        html += '<div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px;position:relative;overflow:hidden">' +
                            '<div style="position:absolute;top:0;left:0;right:0;height:3px;border-radius:12px 12px 0 0;background:' + c.accent + '"></div>' +
                            '<div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94A3B8;margin-bottom:4px">' + c.label + '</div>' +
                            '<div style="font-size:20px;font-weight:800;color:#0F172A">' + c.value + '</div>' +
                            '<div style="margin-top:5px;display:inline-flex;align-items:center;gap:2px;font-size:9px;font-weight:700;padding:2px 6px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">▲ 12.5%</div>' +
                            '</div>';
                    }
                });
                wrap.innerHTML = html;
            }
            updateCardPreview(document.getElementById('cardStyleSelect').value);
            </script>

            <!-- ─── Auth-page Backgrounds ──────────────────────────────── -->
            <div style="margin-top:24px;padding:18px;background:linear-gradient(135deg,#F8FAFC,#EEF2FF);border:1px solid #E2E8F0;border-radius:10px">
                <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Auth Page Backgrounds</div>
                <div style="font-size:12.5px;color:#64748B;margin-bottom:14px;line-height:1.5">
                    Upload custom backgrounds for the Login, Affiliate Registration and Advertiser Registration pages. Each one has its own enable toggle — turn off to use the default gradient. Backgrounds use <code>cover</code> sizing so they scale cleanly on desktop, tablet and mobile.
                </div>
                <?php
                $authBgs = [
                    'auth_bg_login'  => ['title' => 'Login Page Background',                  'enable' => 'auth_bg_login_enabled',  'clear' => 'clear_auth_bg_login'],
                    'auth_bg_affreg' => ['title' => 'Affiliate Registration Background',      'enable' => 'auth_bg_affreg_enabled', 'clear' => 'clear_auth_bg_affreg'],
                    'auth_bg_advreg' => ['title' => 'Advertiser Registration Background',     'enable' => 'auth_bg_advreg_enabled', 'clear' => 'clear_auth_bg_advreg'],
                ];
                foreach ($authBgs as $field => $meta):
                    $path = $cfg['app'][$field] ?? '';
                    $on   = ($cfg['app'][$meta['enable']] ?? '0') === '1';
                ?>
                <div style="background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:14px 16px;margin-bottom:12px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;gap:14px;flex-wrap:wrap">
                        <div style="font-weight:700;font-size:13px;color:#0F172A"><?= Helpers::e($meta['title']) ?></div>
                        <!-- Enable toggle -->
                        <label style="position:relative;display:inline-block;width:46px;height:26px">
                            <input type="checkbox" name="<?= $meta['enable'] ?>" value="1" <?= $on ? 'checked' : '' ?> style="opacity:0;width:0;height:0" class="bg-tgl">
                            <span class="bg-tgl-track" data-on="<?= $on ? '1' : '0' ?>" style="position:absolute;cursor:pointer;inset:0;background:<?= $on ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                                <span style="position:absolute;height:20px;width:20px;left:<?= $on ? '24px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
                            </span>
                        </label>
                    </div>

                    <?php if ($path): ?>
                    <div style="position:relative;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E2E8F0;aspect-ratio:16 / 6;background:#F1F5F9 url('<?= Helpers::e($path) ?>') center/cover no-repeat" data-bg-preview>
                        <label style="position:absolute;top:8px;right:8px;display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.92);padding:5px 10px;border-radius:20px;font-size:11.5px;cursor:pointer;color:#DC2626;font-weight:600;border:1px solid rgba(220,38,38,.2)">
                            <input type="checkbox" name="<?= $meta['clear'] ?>" value="1" style="margin:0"> Remove
                        </label>
                    </div>
                    <?php else: ?>
                    <div style="margin-bottom:10px;border-radius:8px;border:1px dashed #CBD5E1;aspect-ratio:16 / 6;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:12px;background:#F8FAFC" data-bg-preview-empty>
                        No background uploaded — gradient default will be used
                    </div>
                    <?php endif; ?>

                    <input type="file" name="<?= $field ?>" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control bg-file" data-target="<?= $field ?>">
                    <div class="form-hint" style="margin-top:6px">JPG, PNG, WebP or GIF · Max 5 MB · Recommended: 1920×1080 or larger landscape</div>
                </div>
                <?php endforeach; ?>
            </div>

            <script>
            (function(){
                // Toggle visual sync (mirror the green/grey + knob position)
                document.querySelectorAll('.bg-tgl').forEach(function(cb){
                    var track = cb.nextElementSibling;
                    var knob  = track && track.firstElementChild;
                    if (!track || !knob) return;
                    cb.addEventListener('change', function(){
                        track.style.background = cb.checked ? '#10B981' : '#CBD5E1';
                        knob.style.left        = cb.checked ? '24px'    : '3px';
                    });
                });

                // Live preview before saving — read the chosen file via
                // FileReader and paint it into the same preview panel.
                document.querySelectorAll('.bg-file').forEach(function(input){
                    input.addEventListener('change', function(e){
                        var f = e.target.files && e.target.files[0];
                        if (!f) return;
                        if (f.size > 5 * 1024 * 1024) {
                            alert('That file is over 5 MB — please pick a smaller image.');
                            input.value = '';
                            return;
                        }
                        var card = input.closest('div[style*="background:#fff"]') || input.parentElement;
                        if (!card) return;
                        var preview = card.querySelector('[data-bg-preview]') || card.querySelector('[data-bg-preview-empty]');
                        if (!preview) return;
                        var reader = new FileReader();
                        reader.onload = function(ev){
                            // If it was the empty placeholder, swap to a real preview block.
                            if (preview.hasAttribute('data-bg-preview-empty')) {
                                var div = document.createElement('div');
                                div.setAttribute('data-bg-preview','');
                                div.style.cssText = 'position:relative;margin-bottom:10px;border-radius:8px;overflow:hidden;border:1px solid #E2E8F0;aspect-ratio:16 / 6;background-position:center;background-size:cover;background-repeat:no-repeat';
                                preview.replaceWith(div);
                                preview = div;
                            }
                            preview.style.backgroundImage = 'url("' + ev.target.result + '")';
                        };
                        reader.readAsDataURL(f);
                    });
                });
            })();
            </script>

            <button type="submit" class="btn btn-primary">Save Branding</button>
        </form>
    </div>
</div>
<!-- ─── SECURITY ─────────────────────────────────────── -->
<?php elseif ($activeTab === 'security'): ?>
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Security Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="security">

            <!-- 2FA Toggle -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Two-Step Verification (2FA)</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">When enabled, users must enter a 6-digit code sent to their email after entering their password. Applies to all roles.</div>
                    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? '#15803D' : '#64748B' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                        <?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? 'Active' : 'Inactive' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="2fa_enabled" value="1" <?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity:0;width:0;height:0" onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;background:<?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span style="position:absolute;content:'';height:22px;width:22px;left:<?= ($cfg['app']['2fa_enabled'] ?? '0') === '1' ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
            </div>

            <!-- Email Verification Toggle -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Email Verification on Registration</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">When enabled, affiliates and advertisers must verify their email address before they can log in. A verification link is sent to their inbox on registration.</div>
                    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= ($cfg['app']['email_verification'] ?? '0') === '1' ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= ($cfg['app']['email_verification'] ?? '0') === '1' ? '#15803D' : '#64748B' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                        <?= ($cfg['app']['email_verification'] ?? '0') === '1' ? 'Active' : 'Inactive' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="email_verification" value="1" <?= ($cfg['app']['email_verification'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity:0;width:0;height:0" onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;background:<?= ($cfg['app']['email_verification'] ?? '0') === '1' ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span style="position:absolute;content:'';height:22px;width:22px;left:<?= ($cfg['app']['email_verification'] ?? '0') === '1' ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
            </div>

            <!-- Registration Message -->
            <div style="padding:18px 0">
                <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Registration Notification Message</div>
                <div style="font-size:13px;color:#64748B;margin-bottom:12px;line-height:1.5">Displayed on the login page after a user registers. Shown as a success notice. Supports line breaks.</div>
                <textarea name="registration_message" class="form-control" rows="5"><?= Helpers::e($cfg['app']['registration_message'] ?? "Registration successful.\nYour account is currently inactive. Please contact support for activation.\nTelegram: @affscashnet") ?></textarea>
            </div>

            <!-- Advertiser Registration master switch -->
            <?php $advRegOn = ($cfg['app']['advertiser_registration_enabled'] ?? '1') === '1'; ?>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 0;border-top:1px solid #F1F5F9;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Advertiser Registration</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">When disabled, the public advertiser registration page is closed (browser hits get a 403 status). Affiliate registration and login are unaffected. Existing advertisers can still sign in.</div>
                    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $advRegOn ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $advRegOn ? '#15803D' : '#991B1B' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                        <?= $advRegOn ? 'Open' : 'Closed' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="advertiser_registration_enabled" value="1" <?= $advRegOn ? 'checked' : '' ?> style="opacity:0;width:0;height:0" id="advRegToggle">
                    <span id="advRegTrack" style="position:absolute;cursor:pointer;inset:0;background:<?= $advRegOn ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span id="advRegKnob" style="position:absolute;height:22px;width:22px;left:<?= $advRegOn ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
                <script>
                (function(){
                    var cb = document.getElementById('advRegToggle'); if(!cb) return;
                    var t  = document.getElementById('advRegTrack');
                    var k  = document.getElementById('advRegKnob');
                    cb.addEventListener('change', function(){
                        t.style.background = cb.checked ? '#10B981' : '#CBD5E1';
                        k.style.left       = cb.checked ? '27px'    : '3px';
                    });
                })();
                </script>
            </div>

            <!-- Advertiser Closed Message -->
            <div style="padding:18px 0">
                <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Advertiser Registration Closed Message</div>
                <div style="font-size:13px;color:#64748B;margin-bottom:12px;line-height:1.5">Shown to anyone who visits the advertiser registration URL while registration is disabled.</div>
                <textarea name="advertiser_registration_closed_message" class="form-control" rows="3" placeholder="Advertiser registration is currently closed."><?= Helpers::e($cfg['app']['advertiser_registration_closed_message'] ?? 'Advertiser registration is currently closed. Please contact our team for partnership opportunities.') ?></textarea>
            </div>

            <!-- Cloudflare Turnstile -->
            <?php
            $tsEnabled   = ($cfg['turnstile']['enabled']    ?? '0') === '1';
            $tsSiteKey   =  $cfg['turnstile']['site_key']   ?? '';
            $tsSecretKey =  $cfg['turnstile']['secret_key'] ?? '';
            ?>
            <div style="border-top:1px solid #F1F5F9;padding-top:22px;margin-top:4px">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px">
                    <div style="flex:1">
                        <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px;display:flex;align-items:center;gap:8px">
                            <img src="https://challenges.cloudflare.com/turnstile/v0/api.js" onerror="this.style.display='none'" style="display:none">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F6821F" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                            Cloudflare Turnstile CAPTCHA
                        </div>
                        <div style="font-size:13px;color:#64748B;line-height:1.5">Protect login and registration forms from bots. Get your keys from the <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" style="color:#4F46E5;font-weight:600">Cloudflare Dashboard → Turnstile</a>.</div>
                        <div style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $tsEnabled ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= $tsEnabled ? '#15803D' : '#64748B' ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                            <?= $tsEnabled ? 'Active' : 'Inactive' ?>
                        </div>
                    </div>
                    <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                        <input type="checkbox" name="turnstile_enabled" value="1" <?= $tsEnabled ? 'checked' : '' ?> style="opacity:0;width:0;height:0">
                        <span style="position:absolute;cursor:pointer;inset:0;background:<?= $tsEnabled ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                            <span style="position:absolute;height:22px;width:22px;left:<?= $tsEnabled ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                        </span>
                    </label>
                    <script>
                    (function(){
                        var cb = document.querySelector('input[name="turnstile_enabled"]');
                        if (!cb) return;
                        var track = cb.nextElementSibling;
                        var knob  = track.firstElementChild;
                        cb.addEventListener('change', function(){
                            track.style.background = cb.checked ? '#10B981' : '#CBD5E1';
                            knob.style.left        = cb.checked ? '27px'    : '3px';
                        });
                    })();
                    </script>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label style="font-weight:600;font-size:13px">Site Key <span style="color:#94A3B8;font-weight:400">(public)</span></label>
                        <input type="text" name="turnstile_site_key" class="form-control"
                               value="<?= Helpers::e($tsSiteKey) ?>"
                               placeholder="0x4AAAAAAA...">
                        <div class="form-hint">Rendered in the browser widget</div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label style="font-weight:600;font-size:13px">Secret Key <span style="color:#94A3B8;font-weight:400">(server-side)</span></label>
                        <input type="password" name="turnstile_secret_key" class="form-control"
                               value="<?= Helpers::e($tsSecretKey) ?>"
                               placeholder="Leave blank to keep existing"
                               autocomplete="new-password">
                        <div class="form-hint">Used to verify tokens server-side</div>
                    </div>
                </div>
                <?php if ($tsSiteKey): ?>
                <div style="margin-top:12px;padding:10px 14px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:6px;font-size:12px;color:#92400E">
                    <strong>Live preview:</strong> &nbsp;
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <div class="cf-turnstile" data-sitekey="<?= Helpers::e($tsSiteKey) ?>" data-size="compact" style="display:inline-block;margin-top:6px"></div>
                </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Save Security Settings</button>
        </form>
    </div>
</div>

<!-- ─── AFFILIATE INACTIVITY CONTROL ─────────────────── -->
<?php elseif ($activeTab === 'inactivity'): ?>
<?php
$inacOn        = ($cfg['app']['inactivity_enabled']   ?? '0') === '1';
$inacDays      = (int)($cfg['app']['inactivity_days'] ?? 30);
$inacWarnDays  = (int)($cfg['app']['inactivity_warn_days'] ?? 3);
$inacSubject   = $cfg['app']['inactivity_warn_subject'] ?? 'Your account will be deactivated soon';
$inacBody      = $cfg['app']['inactivity_warn_body'] ?? "Hi {name},\n\nWe noticed you haven't signed in to your affiliate account in {days_inactive} days. Your account will be automatically deactivated in {days_until_deactivation} days unless you log in.\n\nSign in here: {login_url}\n\nThanks,\n{site_name}";

// Live counts to give the admin a sense of impact before flipping the switch.
$inacStats = ['active' => 0, 'at_risk' => 0, 'deactivated' => 0];
try {
    $inacStats['active'] = (int)(Database::fetchOne(
        "SELECT COUNT(*) c FROM users WHERE role='affiliate' AND status='active'"
    )['c'] ?? 0);
    if ($inacDays > 0) {
        $inacStats['at_risk'] = (int)(Database::fetchOne(
            "SELECT COUNT(*) c FROM users WHERE role='affiliate' AND status='active'
             AND last_login IS NOT NULL AND last_login < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [max(0, $inacDays - max(1, $inacWarnDays))]
        )['c'] ?? 0);
    }
    $inacStats['deactivated'] = (int)(Database::fetchOne(
        "SELECT COUNT(*) c FROM users u
         WHERE u.role='affiliate' AND u.status='suspended'
           AND EXISTS (SELECT 1 FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='inactivity_deactivated_at')
           AND u.id IN (SELECT id FROM users WHERE inactivity_deactivated_at IS NOT NULL)"
    )['c'] ?? 0);
} catch (\Throwable $_e) { /* counters are nice-to-have only */ }
?>
<div class="card" style="max-width:760px">
    <div class="card-header"><span class="card-title">Affiliate Inactivity Control</span></div>
    <div class="card-body">

        <!-- Snapshot row -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:22px">
            <div style="padding:14px 16px;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:10px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#065F46">Active affiliates</div>
                <div style="font-size:24px;font-weight:800;color:#064E3B;margin-top:4px"><?= number_format($inacStats['active']) ?></div>
            </div>
            <div style="padding:14px 16px;background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:1px solid #FDE68A;border-radius:10px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#92400E">At risk now</div>
                <div style="font-size:24px;font-weight:800;color:#78350F;margin-top:4px"><?= number_format($inacStats['at_risk']) ?></div>
                <div style="font-size:11px;color:#92400E;margin-top:2px">Would be warned today</div>
            </div>
            <div style="padding:14px 16px;background:linear-gradient(135deg,#FEF2F2,#FEE2E2);border:1px solid #FECACA;border-radius:10px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#991B1B">Auto-deactivated</div>
                <div style="font-size:24px;font-weight:800;color:#7F1D1D;margin-top:4px"><?= number_format($inacStats['deactivated']) ?></div>
            </div>
        </div>

        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="inactivity">

            <!-- Enable / disable -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:16px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Automatic Inactivity Deactivation</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">When enabled, affiliate accounts are automatically deactivated after the inactivity period below. Login, conversion tracking, payouts and existing reports are not affected — only sign-in for the deactivated user is blocked.</div>
                    <div style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $inacOn ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= $inacOn ? '#15803D' : '#64748B' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                        <?= $inacOn ? 'Active' : 'Inactive' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="inactivity_enabled" value="1" <?= $inacOn ? 'checked' : '' ?> id="inacToggle" style="opacity:0;width:0;height:0">
                    <span id="inacTrack" style="position:absolute;cursor:pointer;inset:0;background:<?= $inacOn ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span id="inacKnob" style="position:absolute;height:22px;width:22px;left:<?= $inacOn ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
                <script>
                (function(){
                    var cb=document.getElementById('inacToggle'); if(!cb) return;
                    var t=document.getElementById('inacTrack'), k=document.getElementById('inacKnob');
                    cb.addEventListener('change',function(){
                        t.style.background=cb.checked?'#10B981':'#CBD5E1';
                        k.style.left=cb.checked?'27px':'3px';
                    });
                })();
                </script>
            </div>

            <!-- Inactivity period + warning -->
            <div style="padding:18px 0;border-bottom:1px solid #F1F5F9">
                <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label style="font-weight:600;font-size:13px">Inactivity Period</label>
                        <select name="inactivity_days" class="form-control">
                            <?php foreach ([7,15,30,60,90,120,180,365] as $d): ?>
                            <option value="<?= $d ?>" <?= $inacDays === $d ? 'selected' : '' ?>><?= $d ?> days</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Accounts inactive longer than this are deactivated</div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label style="font-weight:600;font-size:13px">Warn Before Deactivation</label>
                        <select name="inactivity_warn_days" class="form-control">
                            <option value="0" <?= $inacWarnDays === 0 ? 'selected' : '' ?>>Disabled — no warning email</option>
                            <?php foreach ([1,2,3,5,7,14] as $d): ?>
                            <option value="<?= $d ?>" <?= $inacWarnDays === $d ? 'selected' : '' ?>><?= $d ?> day<?= $d===1?'':'s' ?> before</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Optional warning email a few days before the deadline</div>
                    </div>
                </div>
            </div>

            <!-- Warning email template -->
            <div style="padding:18px 0">
                <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:6px">Warning Email</div>
                <div style="font-size:12.5px;color:#64748B;margin-bottom:10px;line-height:1.5">
                    Placeholders you can use: <code>{name}</code>, <code>{email}</code>, <code>{days_inactive}</code>, <code>{days_until_deactivation}</code>, <code>{deactivation_date}</code>, <code>{login_url}</code>, <code>{site_name}</code>
                </div>
                <div class="form-group">
                    <label style="font-weight:600;font-size:13px">Subject</label>
                    <input type="text" name="inactivity_warn_subject" class="form-control" value="<?= Helpers::e($inacSubject) ?>" placeholder="Your account will be deactivated soon">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label style="font-weight:600;font-size:13px">Body</label>
                    <textarea name="inactivity_warn_body" class="form-control" rows="7" style="font-family:inherit"><?= Helpers::e($inacBody) ?></textarea>
                </div>
            </div>

            <!-- ─── AUTOMATIC BACKGROUND SCAN ─────────────────────────── -->
            <div style="margin-top:16px;padding:14px 16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;font-size:12.5px;color:#166534;line-height:1.55">
                <div style="font-weight:800;font-size:13px;color:#14532D;margin-bottom:4px">Automatic Background Scan</div>
                <div style="margin-bottom:0">The system automatically checks and deactivates inactive affiliates in the background without needing any manual cron job setup.</div>
            </div>

            <div style="margin-top:18px">
                <button type="submit" class="btn btn-primary">Save Inactivity Settings</button>
            </div>
        </form>

        <form method="POST" style="margin-top:12px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="inactivity_manual_run">
            <button type="submit" class="btn" style="background:#F1F5F9;color:#475569;border:1px solid #CBD5E1;font-weight:600">Run Inactivity Check Now (Manual Run)</button>
            <div class="form-hint" style="margin-top:6px; margin-left:2px;">Manually trigger the cron sweep to warn or deactivate affiliates immediately based on the periods set above.</div>
        </form>
    </div>
</div>

<!-- ─── TRAFFIC ─────────────────────────────────────── -->
<?php elseif ($activeTab === 'traffic'): ?>
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Traffic Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="traffic">
            <div class="form-group">
                <label>Traffic Back URL</label>
                <input type="url" name="traffic_back_url" class="form-control"
                       value="<?= Helpers::e($cfg['app']['traffic_back_url'] ?? '') ?>"
                       placeholder="https://yoursite.com/traffic-back?click_id={click_id}&aff_id={aff_id}">
                <div class="form-hint">
                    Where to redirect clicks that are blocked, capped, or from inactive offers.
                    Leave empty to show a plain error page.
                </div>
            </div>
            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#3730A3;line-height:1.6">
                <strong>Available placeholders</strong> — drop these anywhere in the URL and the tracker
                substitutes them at redirect time. If you omit them, the same params are auto-appended as
                a query string instead.
                <div style="margin-top:8px;font-family:ui-monospace,Consolas,monospace;font-size:12px;color:#1E1B4B">
                    <code>{click_id}</code> · <code>{aff_id}</code> · <code>{offer_id}</code> · <code>{reason}</code>
                </div>
                <div style="margin-top:8px;font-size:12px;color:#4338CA">
                    Example: <code>https://yoursite.com/tb?cid={click_id}&amp;aff={aff_id}</code>
                </div>
            </div>
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534;line-height:1.55">
                <strong>Crediting traffic-back conversions:</strong> If the visitor converts on the
                traffic-back destination, fire a postback to
                <code>{app_url}/postback?click_id={click_id}</code> with the original click_id received here.
                The conversion will then show up under <a href="/admin/conversions" style="color:#166534;text-decoration:underline">Conversions</a>
                with status <em>approved</em> (or pending if manual approval is on).
            </div>
            <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400E">
                <strong>Current behaviour:</strong> Blocked/fraud clicks → 403. Capped offers → error message.
                Setting this URL will redirect all such traffic instead, with <code>click_id</code> and
                <code>aff_id</code> attached so the destination can identify the original click.
            </div>
            <button type="submit" class="btn btn-primary">Save Traffic Settings</button>
        </form>
    </div>
</div>

<!-- ─── COMMISSION ─────────────────────────────────────── -->
<?php elseif ($activeTab === 'commission'): ?>

<!-- Manager commission is configured per-manager (rate + selected offers) on
     the affiliate manager profile page. The engine runs in real time on every
     approved conversion via ManagerCommissionService::recordForConversion(). -->

<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Referral Commission</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="commission">
            <div class="form-group">
                <label>Commission Type</label>
                <div style="display:flex;gap:16px;margin-top:6px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                        <input type="radio" name="refer_commission_type" value="percent"
                               <?= ($cfg['app']['refer_commission_type'] ?? 'percent') === 'percent' ? 'checked' : '' ?>>
                        Percentage of referred affiliate's earnings
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                        <input type="radio" name="refer_commission_type" value="fixed"
                               <?= ($cfg['app']['refer_commission_type'] ?? '') === 'fixed' ? 'checked' : '' ?>>
                        Fixed amount per conversion
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Commission Rate / Amount</label>
                <div style="display:flex;align-items:center;gap:8px">
                    <input type="number" name="refer_commission_rate" class="form-control" style="max-width:140px"
                           step="0.01" min="0" max="100"
                           value="<?= Helpers::e($cfg['app']['refer_commission_rate'] ?? '5') ?>">
                    <span id="rateLabel" style="font-size:14px;color:#64748B">
                        <?= ($cfg['app']['refer_commission_type'] ?? 'percent') === 'percent' ? '%' : 'USD' ?>
                    </span>
                </div>
                <div class="form-hint" id="rateHint">
                    <?= ($cfg['app']['refer_commission_type'] ?? 'percent') === 'percent'
                        ? 'e.g. 5 means the referrer earns 5% of each payout generated by their referred affiliate.'
                        : 'e.g. 1.00 means the referrer earns $1.00 for each conversion made by their referred affiliate.' ?>
                </div>
            </div>
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1E40AF">
                <strong>How it works:</strong> When an affiliate is referred by another affiliate (via their referral link), the referring affiliate earns a commission based on the referred affiliate's activity.
                The <code>referred_by</code> field on each affiliate record tracks the referring affiliate's ID.
            </div>
            <button type="submit" class="btn btn-primary">Save Commission Settings</button>
        </form>
    </div>
</div>
<!-- ─── DOMAINS ─────────────────────────────────────── -->
<?php elseif ($activeTab === 'domains'): ?>

<style>
.dns-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600 }
.dns-pending    { background:#FEF3C7;color:#92400E }
.dns-pointing   { background:#D1FAE5;color:#065F46 }
.dns-error      { background:#FEE2E2;color:#991B1B }
.dns-configured { background:#DBEAFE;color:#1E40AF }
.dns-active     { background:#D1FAE5;color:#065F46 }
.dns-none       { background:#F1F5F9;color:#64748B }
.domain-log { font-size:11px;color:#64748B;margin-top:4px;font-family:monospace;max-width:320px;word-break:break-all }
@keyframes spin { to { transform:rotate(360deg); } }
.spin { display:inline-block;animation:spin .8s linear infinite }
</style>

<!-- Server Info Banner -->
<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div>
        <span style="font-size:12px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.5px">This Server's IP</span><br>
        <code id="srv-ip" style="font-size:16px;font-weight:700;color:#1E40AF"><?= Helpers::e($serverIp ?: 'Detecting…') ?></code>
    </div>
    <div>
        <span style="font-size:12px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Server Type</span><br>
        <code style="font-size:14px;font-weight:700;color:#1E40AF"><?= strtoupper(Helpers::e($serverEnv)) ?><?= $canExec ? ' (auto-config available)' : '' ?></code>
    </div>
    <div style="margin-left:auto">
        <button onclick="checkAllDomains()" class="btn btn-secondary btn-sm">&#8635; Check All DNS</button>
    </div>
</div>

<!-- DNS Setup Instructions -->
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:#92400E">
    <strong>Step 1 — DNS:</strong> Point your tracking domain's <strong>A record</strong> to <code id="dns-ip-hint"><?= Helpers::e($serverIp ?: 'your server IP') ?></code><br>
    <strong>Step 2 — Server:</strong>
    <?php if ($serverEnv === 'cpanel'): ?>
        In cPanel → <strong>Addon Domains</strong>, add the domain pointing to the tracker folder. OR enter cPanel credentials in General Settings to enable one-click setup.
    <?php elseif ($canExec): ?>
        Click <strong>Auto Configure</strong> next to each domain — this creates the VirtualHost and reloads <?= $serverEnv === 'nginx' ? 'Nginx' : 'Apache' ?> automatically.
    <?php else: ?>
        Create a VirtualHost for each domain pointing to <code><?= Helpers::e(defined('BASE_PATH') ? BASE_PATH . '/public' : '/path/to/tracker/public') ?></code>
    <?php endif; ?>
    <br><strong>Step 3:</strong> Click <strong>Check DNS</strong> to verify — status changes to <span class="dns-badge dns-pointing">DNS OK</span> then <span class="dns-badge dns-active">Active</span> once the server is configured.
</div>

<div class="grid-2 mb-3">
    <!-- Add Domain -->
    <div class="card">
        <div class="card-header"><span class="card-title">Add Tracking Domain</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="domains">
                <input type="hidden" name="domain_action" value="add">
                <div class="form-group">
                    <label>Domain *</label>
                    <input type="text" name="new_domain" id="newDomainInput" class="form-control" required placeholder="track.yourdomain.com">
                    <div class="form-hint">No https:// needed. Set an A record pointing to <strong><?= Helpers::e($serverIp ?: 'this server IP') ?></strong></div>
                </div>
                <div class="form-group">
                    <label>Label (optional)</label>
                    <input type="text" name="new_domain_label" class="form-control" placeholder="e.g. Brand Tracker">
                </div>
                <button type="submit" class="btn btn-primary">Add Domain</button>
            </form>
        </div>
    </div>

    <!-- Domain List -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Tracking Domains</span>
            <span style="font-size:12px;color:#64748B"><?= count($trackingDomains) ?> domain<?= count($trackingDomains) !== 1 ? 's' : '' ?></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>Domain</th>
                    <th>DNS</th>
                    <th>Server</th>
                    <th>SSL</th>
                    <th style="min-width:180px">Actions</th>
                </tr></thead>
                <tbody id="domainsTableBody">
                <?php if (empty($trackingDomains)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No tracking domains yet. Add one above or the main site URL will be used.</td></tr>
                <?php else: ?>
                <?php foreach ($trackingDomains as $td):
                    $dnsClass = match($td['dns_status'] ?? 'pending') {
                        'pointing' => 'dns-pointing',
                        'error'    => 'dns-error',
                        default    => 'dns-pending',
                    };
                    $dnsLabel = match($td['dns_status'] ?? 'pending') {
                        'pointing' => '&#10003; DNS OK',
                        'error'    => '&#9888; Wrong IP',
                        default    => '&#8987; Pending',
                    };
                    $srvClass = match($td['server_status'] ?? 'pending') {
                        'configured' => 'dns-configured',
                        'error'      => 'dns-error',
                        default      => 'dns-pending',
                    };
                    $srvLabel = match($td['server_status'] ?? 'pending') {
                        'configured' => '&#10003; Configured',
                        'error'      => '&#9888; Error',
                        default      => '&#8987; Pending',
                    };
                    $sslClass = match($td['ssl_status'] ?? 'none') {
                        'active' => 'dns-active',
                        'error'  => 'dns-error',
                        default  => 'dns-none',
                    };
                    $sslLabel = match($td['ssl_status'] ?? 'none') {
                        'active' => '&#128274; Active',
                        'error'  => '&#9888; Error',
                        default  => 'No SSL',
                    };
                ?>
                <tr id="domain-row-<?= $td['id'] ?>">
                    <!-- View mode -->
                    <td id="domain-view-<?= $td['id'] ?>">
                        <div>
                            <code style="font-size:12px"><?= Helpers::e($td['domain']) ?></code>
                            <?php if ($td['is_default']): ?><span class="badge badge-success" style="margin-left:4px;font-size:10px">Default</span><?php endif; ?>
                            <?php if (!$td['is_active']): ?><span class="badge badge-muted" style="margin-left:4px;font-size:10px">Off</span><?php endif; ?>
                            <?php if (($cfg['app']['landing_domain'] ?? '') === $td['domain']): ?><span class="badge" style="margin-left:4px;font-size:10px;background:#D1FAE5;color:#065F46;padding:2px 6px;border-radius:4px">&#127968; Landing</span><?php endif; ?>
                        </div>
                        <?php if ($td['label']): ?><div style="font-size:11px;color:#94A3B8"><?= Helpers::e($td['label']) ?></div><?php endif; ?>
                        <?php if ($td['check_message'] ?? ''): ?>
                        <div class="domain-log" id="msg-<?= $td['id'] ?>"><?= Helpers::e($td['check_message']) ?></div>
                        <?php else: ?>
                        <div class="domain-log" id="msg-<?= $td['id'] ?>"></div>
                        <?php endif; ?>
                        <?php if ($td['dns_ip'] ?? ''): ?>
                        <div style="font-size:10px;color:#94A3B8">Resolves to: <?= Helpers::e($td['dns_ip']) ?></div>
                        <?php endif; ?>
                        <?php if ($td['last_check_at'] ?? ''): ?>
                        <div style="font-size:10px;color:#CBD5E1">Checked <?= Helpers::e(date('M j H:i', strtotime($td['last_check_at']))) ?></div>
                        <?php endif; ?>
                    </td>
                    <!-- Edit mode -->
                    <td id="domain-edit-<?= $td['id'] ?>" style="display:none" colspan="4">
                        <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="edit">
                            <input type="hidden" name="domain_id" value="<?= $td['id'] ?>">
                            <input type="text" name="edit_domain" class="form-control" value="<?= Helpers::e($td['domain']) ?>" placeholder="track.example.com" style="width:170px;font-size:12px" required>
                            <input type="text" name="edit_domain_label" class="form-control" value="<?= Helpers::e($td['label']) ?>" placeholder="Label" style="width:100px;font-size:12px">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="cancelDomainEdit(<?= $td['id'] ?>)">Cancel</button>
                        </form>
                    </td>
                    <td id="domain-dns-<?= $td['id'] ?>">
                        <span class="dns-badge <?= $dnsClass ?>"><?= $dnsLabel ?></span>
                    </td>
                    <td id="domain-srv-<?= $td['id'] ?>">
                        <span class="dns-badge <?= $srvClass ?>"><?= $srvLabel ?></span>
                    </td>
                    <td id="domain-ssl-<?= $td['id'] ?>">
                        <span class="dns-badge <?= $sslClass ?>"><?= $sslLabel ?></span>
                    </td>
                    <td id="domain-actions-<?= $td['id'] ?>" style="white-space:nowrap">
                        <!-- Check DNS -->
                        <button class="btn btn-secondary btn-sm" onclick="checkDomain('<?= Helpers::e($td['domain']) ?>', <?= $td['id'] ?>)" title="Check DNS propagation">
                            &#8635; Check
                        </button>
                        <?php if ($canExec || $serverEnv === 'cpanel'): ?>
                        <!-- Auto Configure VHost -->
                        <button class="btn btn-sm" style="background:#6366F1;color:#fff" onclick="autoConfig('<?= Helpers::e($td['domain']) ?>', <?= $td['id'] ?>)" title="Auto-create VirtualHost">
                            &#9881; Configure
                        </button>
                        <?php endif; ?>
                        <!-- Edit -->
                        <button class="btn btn-secondary btn-sm" onclick="startDomainEdit(<?= $td['id'] ?>)">Edit</button>
                        <?php if (!$td['is_default']): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="set_default">
                            <input type="hidden" name="domain_id" value="<?= $td['id'] ?>">
                            <button class="btn btn-secondary btn-sm">Default</button>
                        </form>
                        <?php endif; ?>
                        <?php if (($cfg['app']['landing_domain'] ?? '') === $td['domain']): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="clear_landing">
                            <button class="btn btn-sm" style="background:#F59E0B;color:#fff" title="Remove this domain as landing page domain">&#127968; Landing ✕</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="set_landing">
                            <input type="hidden" name="domain_id" value="<?= $td['id'] ?>">
                            <button class="btn btn-sm" style="background:#10B981;color:#fff" title="Serve landing page when this domain is visited">&#127968; Landing</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="toggle">
                            <input type="hidden" name="domain_id" value="<?= $td['id'] ?>">
                            <button class="btn btn-secondary btn-sm"><?= $td['is_active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <?php if (!$td['is_default']): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="domains">
                            <input type="hidden" name="domain_action" value="delete">
                            <input type="hidden" name="domain_id" value="<?= $td['id'] ?>">
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this domain?')">&#215;</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Setup Instructions Modal -->
<div id="setupModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:28px;max-width:640px;width:95%;max-height:80vh;overflow-y:auto;position:relative">
        <button onclick="document.getElementById('setupModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#64748B">&#215;</button>
        <h3 style="margin:0 0 16px;font-size:16px">Manual Setup Instructions</h3>
        <div id="setupInstructions" style="font-size:13px;line-height:1.8;color:#334155"></div>
        <div style="margin-top:16px;padding:12px;background:#F8FAFC;border-radius:8px;font-size:12px;color:#64748B">
            After completing setup, click <strong>&#8635; Check</strong> next to the domain to verify it's working.
        </div>
    </div>
</div>

<script>
var _domainSettingsUrl = '/admin/settings';

function domainPost(data) {
    var fd = new FormData();
    for (var k in data) fd.append(k, data[k]);
    return fetch(_domainSettingsUrl, { method:'POST', body: fd }).then(function(r){ return r.json(); });
}

function setBadge(cellId, cls, label) {
    var el = document.getElementById(cellId);
    if (!el) return;
    el.innerHTML = '<span class="dns-badge ' + cls + '">' + label + '</span>';
}

function setMsg(id, text) {
    var el = document.getElementById('msg-' + id);
    if (el) el.textContent = text || '';
}

function checkDomain(domain, id) {
    setBadge('domain-dns-' + id, 'dns-pending', '<span class="spin">&#8635;</span> Checking…');
    setMsg(id, 'Querying DNS…');
    domainPost({ action: 'check_domain_dns', domain: domain })
    .then(function(d) {
        // DNS badge
        if (d.dns_status === 'pointing') {
            setBadge('domain-dns-' + id, 'dns-pointing', '&#10003; DNS OK');
        } else if (d.dns_status === 'error') {
            setBadge('domain-dns-' + id, 'dns-error', '&#9888; Wrong IP');
        } else {
            setBadge('domain-dns-' + id, 'dns-pending', '&#8987; Pending');
        }
        // Server badge
        if (d.server_status === 'configured') {
            setBadge('domain-srv-' + id, 'dns-configured', '&#10003; Configured');
        } else if (d.server_status === 'error') {
            setBadge('domain-srv-' + id, 'dns-error', '&#9888; Error');
        } else {
            setBadge('domain-srv-' + id, 'dns-pending', '&#8987; Pending');
        }
        // SSL badge
        if (d.ssl_status === 'active') {
            setBadge('domain-ssl-' + id, 'dns-active', '&#128274; Active');
        } else if (d.ssl_status === 'error') {
            setBadge('domain-ssl-' + id, 'dns-error', '&#9888; Error');
        } else {
            setBadge('domain-ssl-' + id, 'dns-none', 'No SSL');
        }
        // Message
        var msg = d.message || '';
        if (d.dns_ip) msg = 'Resolves to: ' + d.dns_ip + (msg ? ' — ' + msg : '');
        setMsg(id, msg);

        // If DNS is OK but server not configured, show auto-configure prompt
        if (d.dns_status === 'pointing' && d.server_status !== 'configured') {
            setMsg(id, (msg ? msg + ' | ' : '') + 'DNS ready — click ⚙ Configure to set up the VirtualHost');
        }
    })
    .catch(function() { setMsg(id, 'Check failed — please try again'); });
}

function checkAllDomains() {
    var rows = document.querySelectorAll('[id^="domain-row-"]');
    rows.forEach(function(row) {
        var id    = row.id.replace('domain-row-', '');
        var view  = document.getElementById('domain-view-' + id);
        var code  = view ? view.querySelector('code') : null;
        if (code) checkDomain(code.textContent.trim(), id);
    });
}

function autoConfig(domain, id) {
    setBadge('domain-srv-' + id, 'dns-pending', '<span class="spin">&#9881;</span> Configuring…');
    setMsg(id, 'Attempting auto-configuration…');
    domainPost({ action: 'auto_configure_domain', domain: domain })
    .then(function(d) {
        setMsg(id, d.message || '');
        if (d.ok) {
            setBadge('domain-srv-' + id, 'dns-configured', '&#10003; Configured');
            // Re-check everything after configure
            setTimeout(function() { checkDomain(domain, id); }, 1500);
        } else {
            setBadge('domain-srv-' + id, 'dns-error', '&#9888; Error');
            // Show manual instructions
            document.getElementById('setupInstructions').innerHTML = d.message || 'Auto-configure failed. Please configure manually.';
            document.getElementById('setupModal').style.display = 'flex';
        }
    })
    .catch(function() { setMsg(id, 'Configure request failed'); });
}

function startDomainEdit(id) {
    document.getElementById('domain-view-'   + id).style.display = 'none';
    document.getElementById('domain-dns-'    + id).style.display = 'none';
    document.getElementById('domain-srv-'    + id).style.display = 'none';
    document.getElementById('domain-ssl-'    + id).style.display = 'none';
    document.getElementById('domain-actions-'+ id).style.display = 'none';
    document.getElementById('domain-edit-'   + id).style.display = '';
}
function cancelDomainEdit(id) {
    document.getElementById('domain-view-'   + id).style.display = '';
    document.getElementById('domain-dns-'    + id).style.display = '';
    document.getElementById('domain-srv-'    + id).style.display = '';
    document.getElementById('domain-ssl-'    + id).style.display = '';
    document.getElementById('domain-actions-'+ id).style.display = '';
    document.getElementById('domain-edit-'   + id).style.display = 'none';
}

// Auto-check domains that are still pending on page load
document.addEventListener('DOMContentLoaded', function() {
    var rows = document.querySelectorAll('[id^="domain-row-"]');
    rows.forEach(function(row) {
        var id = row.id.replace('domain-row-', '');
        var dnsBadge = document.getElementById('domain-dns-' + id);
        if (dnsBadge && dnsBadge.textContent.indexOf('Pending') !== -1) {
            var code = document.querySelector('#domain-view-' + id + ' code');
            if (code) {
                // Stagger checks so we don't slam DNS simultaneously
                setTimeout(function() { checkDomain(code.textContent.trim(), id); }, 300 * parseInt(id));
            }
        }
    });
});
</script>

<!-- ─── EMAIL / SMTP ────────────────────────────────── -->
<?php elseif ($activeTab === 'email'): ?>
<div class="card" style="max-width:700px">
    <div class="card-header"><span class="card-title">SMTP / Email Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="email">

            <div class="form-group">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control"
                       value="<?= Helpers::e($cfg['smtp']['host'] ?? '') ?>"
                       placeholder="smtp.gmail.com or mail.yourdomain.com">
                <div class="form-hint">Leave blank to use PHP mail() (not recommended for production)</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control"
                           value="<?= Helpers::e($cfg['smtp']['port'] ?? 587) ?>" placeholder="587">
                </div>
                <div class="form-group">
                    <label>Encryption</label>
                    <select name="smtp_encryption" class="form-control">
                        <?php foreach (['tls'=>'STARTTLS (port 587)','ssl'=>'SSL (port 465)','none'=>'None (port 25)'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($cfg['smtp']['encryption'] ?? 'tls') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>SMTP Username</label>
                <input type="text" name="smtp_username" class="form-control" autocomplete="off"
                       value="<?= Helpers::e($cfg['smtp']['username'] ?? '') ?>"
                       placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label>SMTP Password</label>
                <input type="password" name="smtp_password" class="form-control" autocomplete="new-password"
                       placeholder="Leave blank to keep existing password">
                <div class="form-hint">Password is stored encrypted in config. Leave blank to keep existing.</div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>From Email</label>
                    <input type="email" name="smtp_from_email" class="form-control"
                           value="<?= Helpers::e($cfg['smtp']['from_email'] ?? '') ?>"
                           placeholder="noreply@yourdomain.com">
                </div>
                <div class="form-group">
                    <label>From Name</label>
                    <input type="text" name="smtp_from_name" class="form-control"
                           value="<?= Helpers::e($cfg['smtp']['from_name'] ?? ($cfg['app']['name'] ?? '')) ?>"
                           placeholder="AffiliateTracker">
                </div>
            </div>

            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:6px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#166534">
                <strong>Quick Setup:</strong> For Gmail, use <code>smtp.gmail.com:587 STARTTLS</code> with an App Password (2FA required). For other providers check their SMTP docs.
            </div>

            <button type="submit" class="btn btn-primary">Save Email Settings</button>
            <button type="button" class="btn btn-secondary" style="margin-left:8px" onclick="testSmtp(event)">Test Connection</button>
            <a href="/admin/email?action=templates" class="btn btn-secondary" style="margin-left:8px">Manage Templates</a>
            
            <script>
            function testSmtp(e) {
                let email = prompt("Enter an email address to send a test email to (Make sure you save your settings first!):");
                if (!email) return;
                
                let btn = e.target;
                let oldText = btn.textContent;
                btn.disabled = true;
                btn.textContent = "Testing...";

                let token = document.querySelector('input[name="csrf_token"]')?.value || '';
                
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=test_smtp&email=' + encodeURIComponent(email) + '&csrf_token=' + encodeURIComponent(token)
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = oldText;
                    if (data.ok) {
                        alert('✅ Success! Test email sent to ' + email);
                    } else {
                        alert('❌ Failed to send test email:\n\n' + data.error);
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = oldText;
                    alert('Network error or invalid response.');
                });
            }
            </script>
        </form>
    </div>
</div>
<?php elseif ($activeTab === 'vpn_detection'): ?>
<?php $vpnEnabled = (Config::get('config', 'vpn_detection.enabled') ?? '0') === '1'; ?>
<div class="card" style="max-width:680px">
    <div class="card-header" style="background:linear-gradient(135deg,#1E1B4B,#4F46E5);border-radius:8px 8px 0 0">
        <span class="card-title" style="color:#fff">&#128737; VPN &amp; Proxy Detection</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="vpn_detection">

            <!-- Enable / Disable toggle -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Enable VPN &amp; Proxy Detection</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.6">
                        When <strong>enabled</strong>, any click arriving through a VPN, proxy, Tor exit node, or datacenter IP is automatically blocked before it reaches the offer. A warning page is shown and every blocked attempt is logged below.<br>
                        When <strong>disabled</strong>, all traffic (including VPN/proxy) passes through normally — no logging or blocking occurs.
                    </div>
                    <div style="margin-top:10px;display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $vpnEnabled ? '#DCFCE7' : '#F1F5F9' ?>;color:<?= $vpnEnabled ? '#15803D' : '#64748B' ?>">
                        <span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block"></span>
                        <?= $vpnEnabled ? 'Active — VPN/Proxy traffic is being blocked' : 'Inactive — All traffic is allowed through' ?>
                    </div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="vpn_detection_enabled" value="1"
                           <?= $vpnEnabled ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0"
                           onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;background:<?= $vpnEnabled ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span style="position:absolute;height:22px;width:22px;left:<?= $vpnEnabled ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
            </div>

            <!-- What gets detected -->
            <div style="padding:18px 0;border-bottom:1px solid #F1F5F9">
                <div style="font-weight:700;font-size:13px;color:#1E293B;margin-bottom:10px">Detection Signals</div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <?php foreach ([
                        ['&#127760;', 'Proxy IP', 'IP address flagged as a known proxy by ip-api.com geolocation.'],
                        ['&#127968;', 'Hosting / Datacenter', 'IP originates from a hosting provider or datacenter (common for VPNs).'],
                        ['&#128737;', 'VPN Flag (FraudIQ)', 'IP flagged as VPN by the FraudIQ fraud detection engine (when active).'],
                    ] as [$icon, $title, $desc]): ?>
                    <div style="display:flex;gap:10px;padding:10px 12px;background:#F8FAFC;border-radius:7px;border:1px solid #E2E8F0">
                        <span style="font-size:18px;flex-shrink:0"><?= $icon ?></span>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1E293B"><?= $title ?></div>
                            <div style="font-size:12px;color:#64748B;margin-top:1px"><?= $desc ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Warning message shown to blocked users -->
            <div style="padding:18px 0;border-bottom:1px solid #F1F5F9">
                <div style="font-weight:700;font-size:13px;color:#1E293B;margin-bottom:6px">Visitor Warning Message</div>
                <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:7px;padding:12px 16px;font-size:13px;color:#991B1B;font-weight:600">
                    &#128683; VPN or Proxy traffic detected. Access denied.
                </div>
                <div style="font-size:11px;color:#94A3B8;margin-top:6px">This message is shown to any visitor whose traffic is identified as VPN or proxy when detection is enabled.</div>
            </div>

            <!-- Log link -->
            <div style="padding:18px 0;border-bottom:1px solid #F1F5F9">
                <div style="font-weight:700;font-size:13px;color:#1E293B;margin-bottom:6px">Blocked Traffic Log</div>
                <div style="font-size:13px;color:#64748B;margin-bottom:12px">Every blocked VPN/proxy click is recorded with affiliate ID, offer, IP address, and detection type.</div>
                <a href="/admin/vpn-log" class="btn btn-secondary">&#128203; View VPN Blocked Log</a>
            </div>

            <!-- Per-affiliate skip list -->
            <div style="padding:18px 0">
                <div style="font-weight:700;font-size:13px;color:#1E293B;margin-bottom:6px">Excluded Affiliates &mdash; VPN/Proxy Skip List</div>
                <div style="font-size:13px;color:#64748B;margin-bottom:12px">Trusted affiliates can bypass VPN/Proxy detection entirely. Their tracking links, smartlinks, click and conversion tracking are not blocked even when an IP is flagged.</div>
                <a href="/admin/vpn-proxy-skip" class="btn btn-secondary">&#128274; Manage Excluded Affiliates</a>
            </div>

            <button type="submit" class="btn btn-primary">Save VPN &amp; Proxy Settings</button>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'shortener'): ?>
<!-- ─── LINK SHORTENER ─────────────────────────────────────── -->
<div class="card" style="max-width:680px">
    <div class="card-header" style="background:linear-gradient(135deg,#4C1D95,#7C3AED);color:#fff">
        <span class="card-title" style="color:#fff">&#128279; Link Shortener — shroo.link</span>
    </div>
    <div class="card-body">
        <!-- Info banner -->
        <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:20px">&#9986;</span>
            <div style="font-size:13px;color:#4C1D95">
                <strong>shroo.link</strong> — Affiliates can shorten their tracking links with one click directly from the Offers page.
                Short links redirect through <strong>shroo.link</strong> and still track all clicks normally via your platform.
            </div>
        </div>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="shortener">
            <div class="form-group">
                <label>Enable Link Shortener for Affiliates</label>
                <div class="form-check">
                    <input type="checkbox" name="shortener_enabled" value="1"
                           <?= ($cfg['shortener']['enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <label>Show "Short" button on affiliate offer cards</label>
                </div>
            </div>
            <div class="form-group">
                <label>shroo.link API Key <span style="color:var(--danger)">*</span></label>
                <input type="text" name="shortener_api_key" class="form-control"
                       placeholder="Enter your shroo.link API key"
                       value="<?= Helpers::e($cfg['shortener']['api_key'] ?? 'nLxSNqjvdIsejHwodGpCDDirzbCeBJdu') ?>"
                       autocomplete="off">
                <div class="form-hint">
                    Get your API key from <strong>shroo.link/developers</strong>.
                    Leave unchanged to use the current key.
                </div>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:12px;margin-bottom:16px;font-size:12px;color:#475569">
                <strong>API endpoint:</strong> <code>https://shroo.link/api/url/add</code><br>
                <strong>Rate limit:</strong> 30 requests / minute<br>
                <strong>Short links:</strong> Permanent, direct redirect (no splash page)
            </div>
            <button type="submit" class="btn btn-primary">Save Shortener Settings</button>
        </form>

        <!-- Internal Shortlink API -->
        <div style="margin-top:28px;border-top:1px solid #E2E8F0;padding-top:20px">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;color:#1E1B4B">Internal Shortlink API</div>
            <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:#4C1D95">
                <strong>API Key (same as shroo.link key above):</strong><br>
                <code style="display:inline-block;margin-top:6px;padding:6px 10px;background:#EDE9FE;border-radius:4px;font-size:12px;user-select:all;word-break:break-all"><?= Helpers::e($cfg['shortener']['api_key'] ?? 'nLxSNqjvdIsejHwodGpCDDirzbCeBJdu') ?></code>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:14px 16px;font-size:13px;color:#334155">
                <strong>API Usage:</strong>
                <div style="margin-top:10px">
                    <code style="display:block;background:#1E293B;color:#7DD3FC;padding:12px;border-radius:6px;font-size:12px;line-height:1.7">
                        POST /api/shorten<br>
                        Header: X-API-Key: <?= Helpers::e($cfg['shortener']['api_key'] ?? 'nLxSNqjvdIsejHwodGpCDDirzbCeBJdu') ?><br>
                        Body (JSON): {"url": "https://example.com/your-long-url"}<br><br>
                        Response: {"success": true, "short_url": "https://shroo.link/xxxxx"}
                    </code>
                </div>
                <div style="margin-top:10px;font-size:12px;color:#64748B">
                    Alternatively, pass the key as an <code>Authorization: Bearer {key}</code> header.
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($activeTab === 'conversions'): ?>
<!-- ─── CONVERSIONS ─────────────────────────────────────── -->
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Conversion Approval</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="conversions">
            <div class="form-group">
                <label style="font-weight:600;font-size:14px">Approval Mode</label>
                <div style="margin-top:10px;display:flex;flex-direction:column;gap:14px">
                    <!-- Auto approve option -->
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:2px solid <?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'auto' ? '#4F46E5' : '#E2E8F0' ?>;border-radius:10px;cursor:pointer;background:<?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'auto' ? '#F5F3FF' : '#fff' ?>" id="lbl_auto">
                        <input type="radio" name="conversion_approval_mode" value="auto"
                               <?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'auto' ? 'checked' : '' ?>
                               onchange="hlApproval(this)"
                               style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                        <div>
                            <div style="font-weight:600;font-size:14px;color:#1E293B">Auto Approve</div>
                            <div style="font-size:13px;color:#64748B;margin-top:3px">Conversions are approved instantly when the advertiser fires the postback. Affiliate balance is credited immediately and downstream postbacks fire right away.</div>
                        </div>
                    </label>
                    <!-- Manual approve option -->
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:2px solid <?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'manual' ? '#4F46E5' : '#E2E8F0' ?>;border-radius:10px;cursor:pointer;background:<?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'manual' ? '#F5F3FF' : '#fff' ?>" id="lbl_manual">
                        <input type="radio" name="conversion_approval_mode" value="manual"
                               <?= ($cfg['conversion']['approval_mode'] ?? 'auto') === 'manual' ? 'checked' : '' ?>
                               onchange="hlApproval(this)"
                               style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                        <div>
                            <div style="font-weight:600;font-size:14px;color:#1E293B">Manual Approve</div>
                            <div style="font-size:13px;color:#64748B;margin-top:3px">New conversions are stored as <strong>Pending</strong>. Affiliate balance is only credited after an admin approves each conversion. Downstream postbacks fire only on approval.</div>
                        </div>
                    </label>
                </div>
                <div style="margin-top:12px;padding:10px 14px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;font-size:12px;color:#92400E">
                    <strong>Note:</strong> Changing this setting only affects <em>new</em> conversions. Conversions already recorded keep their current status.
                </div>
            </div>

            <div class="form-group" style="margin-top:22px;padding-top:18px;border-top:1px solid #E2E8F0">
                <label style="font-weight:600;font-size:14px">Report Visibility</label>
                <label style="display:flex;align-items:flex-start;gap:10px;margin-top:10px;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer">
                    <input type="checkbox" name="hide_fraud_rejected_reports" value="1"
                           <?= !empty($cfg['conversion']['hide_fraud_rejected_reports']) ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#1E293B">Hide fraud &amp; rejected conversion sections</div>
                        <div style="font-size:13px;color:#64748B;margin-top:3px">When enabled, the <strong>Fraud</strong> and <strong>Rejected</strong> stat cards and report columns are hidden from the affiliate report page and the affiliate manager report page. Internal admin reports are unaffected.</div>
                    </div>
                </label>
            </div>

            <div class="form-group" style="margin-top:22px;padding-top:18px;border-top:1px solid #E2E8F0">
                <label style="font-weight:600;font-size:14px">Offer Conversion Protection (One per IP)</label>
                <div style="font-size:13px;color:#64748B;margin-top:4px;margin-bottom:12px">
                    Prevent the same IP address from converting the same offer more than once. When blocked, the user is redirected instead of seeing the offer.
                </div>
                
                <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;cursor:pointer;margin-bottom:16px">
                    <input type="checkbox" name="one_per_ip_enabled" value="1" id="one_per_ip_toggle"
                           <?= !empty($cfg['conversion']['one_per_ip_enabled']) ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0" onchange="document.getElementById('one_per_ip_settings').style.display=this.checked?'block':'none'">
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#1E293B">Enable IP Conversion Protection</div>
                        <div style="font-size:13px;color:#64748B;margin-top:3px">If a user with an IP already has an approved conversion for an offer, they will be blocked from accessing that offer again.</div>
                    </div>
                </label>
                
                <div id="one_per_ip_settings" style="display:<?= !empty($cfg['conversion']['one_per_ip_enabled']) ? 'block' : 'none' ?>;padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                    <div class="form-group">
                        <label>Redirect Mode</label>
                        <select name="one_per_ip_redirect_mode" class="form-control">
                            <option value="traffic_back" <?= ($cfg['conversion']['one_per_ip_redirect_mode'] ?? 'traffic_back') === 'traffic_back' ? 'selected' : '' ?>>Traffic Back URL</option>
                            <option value="next_available" <?= ($cfg['conversion']['one_per_ip_redirect_mode'] ?? 'traffic_back') === 'next_available' ? 'selected' : '' ?>>Next Available Offer (Smart Rotation)</option>
                        </select>
                        <div class="form-hint">Where to redirect the user if they are blocked from accessing the offer. Next Available Offer will try to find another active offer for the affiliate.</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Conversion Lock Duration</label>
                        <div style="display:flex;gap:12px;margin-bottom:8px">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                <input type="radio" name="one_per_ip_duration_mode" value="permanent" <?= ($cfg['conversion']['one_per_ip_duration_mode'] ?? 'permanent') === 'permanent' ? 'checked' : '' ?> onchange="document.getElementById('duration_days_wrap').style.display='none'"> Permanent
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                <input type="radio" name="one_per_ip_duration_mode" value="custom" <?= ($cfg['conversion']['one_per_ip_duration_mode'] ?? 'permanent') === 'custom' ? 'checked' : '' ?> onchange="document.getElementById('duration_days_wrap').style.display='block'"> Custom Days
                            </label>
                        </div>
                        <div id="duration_days_wrap" style="display:<?= ($cfg['conversion']['one_per_ip_duration_mode'] ?? 'permanent') === 'custom' ? 'block' : 'none' ?>">
                            <div style="display:flex;align-items:center;gap:8px">
                                <input type="number" name="one_per_ip_duration_days" class="form-control" style="width:100px" min="1" value="<?= htmlspecialchars($cfg['conversion']['one_per_ip_duration_days'] ?? '30') ?>"> <span style="font-size:13px;color:#64748B">Days</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:0">
                        <label>Whitelist IPs <span style="color:#94A3B8;font-weight:400">(Optional)</span></label>
                        <textarea name="one_per_ip_whitelist" class="form-control" rows="3" placeholder="Enter one IP address per line..."><?= htmlspecialchars($cfg['conversion']['one_per_ip_whitelist'] ?? '') ?></textarea>
                        <div class="form-hint">These IPs will never be blocked, even if they have already converted. One IP per line.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Conversion Settings</button>
        </form>
    </div>
</div>
<script>
function hlApproval(radio) {
    ['auto','manual'].forEach(function(v) {
        var lbl = document.getElementById('lbl_'+v);
        var active = radio.value === v;
        lbl.style.borderColor = active ? '#4F46E5' : '#E2E8F0';
        lbl.style.background  = active ? '#F5F3FF' : '#fff';
    });
}
</script>

<?php elseif ($activeTab === 'budget_system'): ?>
<!-- ─── BUDGET SYSTEM ────────────────────────────────── -->
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Advertiser Offer Budget</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="budget_system">

            <div class="form-group" style="padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
                    <input type="checkbox" name="budget_required" value="1"
                           <?= (string)($cfg['app']['budget_required'] ?? '0') === '1' ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#1E293B">Require budget on every new offer</div>
                        <div style="font-size:12px;color:#64748B;margin-top:3px">
                            When enabled, advertisers must set a USD budget on every new offer. Each conversion deducts the affiliate payout from the offer's remaining budget and the advertiser's balance. Offers auto-pause when their budget hits zero. When disabled, the budget field is hidden and no deductions occur.
                        </div>
                    </div>
                </label>
            </div>

            <hr style="margin:22px 0;border-color:#E2E8F0">
            <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em">
                Budget-Exempt Advertisers
            </div>
            <p style="font-size:12px;color:#64748B;margin:0 0 14px">
                Advertisers selected below are <strong>exempt</strong> from the budget requirement. They can create and add offers without adding any balance. All other advertisers must have sufficient balance before creating offers when the requirement above is enabled.
            </p>

            <?php if (empty($allAdvertisers)): ?>
                <div style="font-size:13px;color:#94A3B8;padding:12px;background:#F8FAFC;border-radius:8px;border:1px solid #E2E8F0">
                    No advertisers registered yet.
                </div>
            <?php else: ?>
                <div style="margin-bottom:10px;display:flex;gap:8px">
                    <button type="button" onclick="toggleAllExempt(true)" class="btn btn-sm" style="background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;font-size:12px;padding:4px 12px">Select All</button>
                    <button type="button" onclick="toggleAllExempt(false)" class="btn btn-sm" style="background:#F8FAFC;color:#64748B;border:1px solid #E2E8F0;font-size:12px;padding:4px 12px">Deselect All</button>
                    <input type="text" id="exemptSearch" placeholder="Search advertisers…" oninput="filterExempt(this.value)"
                           style="margin-left:auto;padding:4px 10px;border:1px solid #E2E8F0;border-radius:6px;font-size:12px;min-width:180px">
                </div>
                <div id="exemptList" style="max-height:320px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:8px;background:#fff">
                    <?php foreach ($allAdvertisers as $adv):
                        $name    = trim(($adv['first_name'] ?? '') . ' ' . ($adv['last_name'] ?? ''));
                        $company = $adv['company'] ? ' — ' . htmlspecialchars($adv['company'], ENT_QUOTES, 'UTF-8') : '';
                        $exempt  = (int)($adv['budget_exempt'] ?? 0) === 1;
                    ?>
                    <label class="exempt-row" data-search="<?= strtolower(htmlspecialchars($name . ' ' . $adv['email'] . ' ' . ($adv['company'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>"
                           style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #F1F5F9;cursor:pointer;transition:background .15s">
                        <input type="checkbox" name="exempt_adv[]" value="<?= (int)$adv['id'] ?>"
                               <?= $exempt ? 'checked' : '' ?>
                               style="accent-color:#4F46E5;width:15px;height:15px;flex-shrink:0">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600;color:#1E293B"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?><?= $company ?></div>
                            <div style="font-size:11px;color:#94A3B8"><?= htmlspecialchars($adv['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if ($exempt): ?>
                        <span class="exempt-badge" style="font-size:10px;font-weight:700;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;padding:2px 8px;border-radius:20px">EXEMPT</span>
                        <?php else: ?>
                        <span class="exempt-badge" style="font-size:10px;font-weight:700;background:#F1F5F9;color:#94A3B8;border:1px solid #E2E8F0;padding:2px 8px;border-radius:20px">REQUIRED</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <script>
                function toggleAllExempt(check) {
                    document.querySelectorAll('#exemptList input[type=checkbox]').forEach(function(cb) { cb.checked = check; });
                    updateBadges();
                }
                function filterExempt(q) {
                    q = q.toLowerCase();
                    document.querySelectorAll('.exempt-row').forEach(function(row) {
                        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
                    });
                }
                document.querySelectorAll('#exemptList input[type=checkbox]').forEach(function(cb) {
                    cb.addEventListener('change', function() { updateBadge(this); });
                });
                function updateBadge(cb) {
                    var badge = cb.closest('.exempt-row').querySelector('.exempt-badge');
                    if (!badge) return;
                    if (cb.checked) {
                        badge.textContent = 'EXEMPT';
                        badge.style.background = '#EEF2FF'; badge.style.color = '#4F46E5'; badge.style.borderColor = '#C7D2FE';
                    } else {
                        badge.textContent = 'REQUIRED';
                        badge.style.background = '#F1F5F9'; badge.style.color = '#94A3B8'; badge.style.borderColor = '#E2E8F0';
                    }
                }
                function updateBadges() {
                    document.querySelectorAll('#exemptList input[type=checkbox]').forEach(updateBadge);
                }
                </script>
            <?php endif; ?>

            <hr style="margin:22px 0;border-color:#E2E8F0">
            <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em">
                Payment Top-Up Methods
            </div>
            <p style="font-size:12px;color:#64748B;margin:0 0 14px">Wallet addresses / bank details shown to advertisers on the Top-Up page. Leave a field blank to hide that method.</p>

            <?php $pm = $cfg['app']['payment_methods'] ?? []; ?>
            <div class="form-group">
                <label>Bank Payment Instructions</label>
                <textarea name="pm_bank" class="form-control" rows="3" placeholder="Bank: ACME Bank&#10;Account: 1234567890&#10;Swift: ACMEUS33"><?= Helpers::e($pm['bank'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Crypto Wallet Addresses <span style="font-size:11px;color:#94A3B8;font-weight:400">— leave blank to hide that coin from advertisers</span></label>
                <?php
                $cryptoWallets = $pm['crypto_wallets'] ?? [];
                // Backward-compat: if old plain string exists, pre-fill USDT
                if (!is_array($cryptoWallets) && !empty($pm['crypto'])) {
                    $cryptoWallets = ['usdt' => $pm['crypto']];
                }
                $cryptoCoins = [
                    'usdt' => ['USDT (TRC20)',  'TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',   '🔵'],
                    'btc'  => ['BTC (Bitcoin)',  'bc1qxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', '🟠'],
                    'ltc'  => ['LTC (Litecoin)', 'Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',  '⚪'],
                    'eth'  => ['ETH (Ethereum)', '0xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '🔷'],
                    'bnb'  => ['BNB (BEP20)',    '0xXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '🟡'],
                    'trx'  => ['TRX (Tron)',     'TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',   '🔴'],
                ];
                ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px;margin-top:8px">
                    <?php foreach ($cryptoCoins as $coin => [$label, $placeholder, $icon]): ?>
                    <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:10px 12px">
                        <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:6px"><?= $icon ?> <?= $label ?></label>
                        <input type="text" name="pm_crypto_<?= $coin ?>" class="form-control" style="font-size:12px;font-family:monospace"
                               value="<?= Helpers::e($cryptoWallets[$coin] ?? '') ?>"
                               placeholder="<?= Helpers::e($placeholder) ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Capitalist Wallet</label>
                <textarea name="pm_capitalist" class="form-control" rows="2" placeholder="U1234567890 or capitalist.net/u/yourwallet"><?= Helpers::e($pm['capitalist'] ?? '') ?></textarea>
            </div>

            <hr style="margin:22px 0;border-color:#E2E8F0">
            <div style="font-size:13px;font-weight:700;color:#4F46E5;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em">
                Stripe Payment Gateway
            </div>
            <p style="font-size:12px;color:#64748B;margin:0 0 14px">Allow advertisers to top-up their balance automatically using Stripe (Credit Card).</p>

            <div class="form-group" style="padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
                    <input type="checkbox" name="stripe_enabled" value="1"
                           <?= (($cfg['app']['stripe_enabled'] ?? '') === '1') ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                    <div style="font-weight:600;font-size:14px;color:#1E293B">Enable Stripe Top-Ups</div>
                </label>
            </div>
            
            <div class="form-row cols-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label>Stripe Publishable Key</label>
                    <input type="text" name="stripe_pk" class="form-control" value="<?= Helpers::e($cfg['app']['stripe_pk'] ?? '') ?>" placeholder="pk_live_...">
                </div>
                <div class="form-group">
                    <label>Stripe Secret Key</label>
                    <input type="text" name="stripe_sk" class="form-control" value="<?= Helpers::e($cfg['app']['stripe_sk'] ?? '') ?>" placeholder="sk_live_...">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:10px">Save Budget Settings</button>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'mobile_app'): ?>
<!-- ─── MOBILE APP ─────────────────────────────────────── -->
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">Mobile App Install</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="mobile_app">

            <div class="form-group">
                <label>App Name / Title</label>
                <input type="text" name="mobile_app_name" class="form-control"
                       value="<?= Helpers::e($cfg['app']['mobile_app_name'] ?? 'AffsCash') ?>"
                       placeholder="AffsCash" maxlength="80">
                <div class="form-hint">Shown inside the affiliate login popup — e.g. "Install our app <strong>AffsCash</strong> for better experience".</div>
            </div>

            <div class="form-group">
                <label>Google Play Store URL</label>
                <input type="url" name="mobile_app_url" class="form-control"
                       value="<?= Helpers::e($cfg['app']['mobile_app_url'] ?? '') ?>"
                       placeholder="https://play.google.com/store/apps/details?id=com.affscash.app">
                <div class="form-hint">When set, a Google Play install button appears in the landing page footer. Leave blank to hide the footer button and disable the popup.</div>
            </div>

            <div class="form-group" style="padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
                    <input type="checkbox" name="mobile_app_popup_enabled" value="1"
                           <?= (($cfg['app']['mobile_app_popup_enabled'] ?? '') === '1') ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                    <div>
                        <div style="font-weight:600;font-size:14px;color:#1E293B">Show install popup to affiliates after login</div>
                        <div style="font-size:12px;color:#64748B;margin-top:3px">When enabled, affiliates see a one-time-per-session popup with an install button. The footer button on the landing page is independent of this toggle.</div>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Save Mobile App Settings</button>
        </form>
    </div>
</div>

<?php elseif ($activeTab === 'notifications'): ?>
<!-- ─── NOTIFICATIONS ──────────────────────────────────── -->
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">📧 Email Notifications</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="notifications">

            <!-- New Offer Notification -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:16px 0;border-bottom:1px solid #F1F5F9">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#0F172A;margin-bottom:4px">
                        🎯 New Offer Notification
                    </div>
                    <div style="font-size:13px;color:#64748B;line-height:1.6">
                        When enabled, all active affiliates automatically receive a styled email the first time
                        an offer becomes <strong>active</strong> — whether the offer is created as active or
                        flipped active later via edit / quick-toggle. Drafts and paused offers stay quiet,
                        and each offer only broadcasts once. Notifications are logged in
                        <a href="/admin/email" style="color:#4F46E5">Email Logs</a>.
                    </div>
                    <div style="margin-top:8px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                            background:<?= ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? '#DCFCE7' : '#F1F5F9' ?>;
                            color:<?=    ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? '#15803D' : '#64748B' ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                            <?= ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? 'Enabled' : 'Disabled' ?>
                        </span>
                    </div>
                </div>
                <!-- Toggle switch -->
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px" title="Toggle new-offer email notification">
                    <input type="checkbox" name="new_offer_notify" value="1"
                        <?= ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? 'checked' : '' ?>
                        style="opacity:0;width:0;height:0"
                        onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;
                        background:<?= ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? '#10B981' : '#CBD5E1' ?>;
                        border-radius:34px;transition:.3s">
                        <span style="position:absolute;height:22px;width:22px;
                            left:<?= ($cfg['app']['new_offer_notify'] ?? '1') === '1' ? '27px' : '3px' ?>;
                            bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)">
                        </span>
                    </span>
                </label>
            </div>

            <!-- Offer Status Change Notification -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:16px 0;border-bottom:1px solid #F1F5F9">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#0F172A;margin-bottom:4px">
                        🔄 Offer Status Change Notification
                    </div>
                    <div style="font-size:13px;color:#64748B;line-height:1.6">
                        When enabled, all active affiliates automatically receive a styled email when an
                        offer's status changes (e.g. from Active to Paused). Notifications are logged in
                        <a href="/admin/email" style="color:#4F46E5">Email Logs</a>.
                    </div>
                    <div style="margin-top:8px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                            background:<?= ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? '#DCFCE7' : '#F1F5F9' ?>;
                            color:<?=    ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? '#15803D' : '#64748B' ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                            <?= ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? 'Enabled' : 'Disabled' ?>
                        </span>
                    </div>
                </div>
                <!-- Toggle switch -->
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px" title="Toggle offer-status-change email notification">
                    <input type="checkbox" name="offer_status_notify" value="1"
                        <?= ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? 'checked' : '' ?>
                        style="opacity:0;width:0;height:0"
                        onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;
                        background:<?= ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? '#10B981' : '#CBD5E1' ?>;
                        border-radius:34px;transition:.3s">
                        <span style="position:absolute;height:22px;width:22px;
                            left:<?= ($cfg['app']['offer_status_notify'] ?? '0') === '1' ? '27px' : '3px' ?>;
                            bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)">
                        </span>
                    </span>
                </label>
            </div>

            <!-- Tracking Link Change Notification -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:16px 0;border-bottom:1px solid #F1F5F9">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#0F172A;margin-bottom:4px">
                        🔗 Tracking Link Change Notification
                    </div>
                    <div style="font-size:13px;color:#64748B;line-height:1.6">
                        When enabled, all active affiliates automatically receive a styled email when an
                        offer's tracking URL/destination is updated. Notifications are logged in
                        <a href="/admin/email" style="color:#4F46E5">Email Logs</a>.
                    </div>
                    <div style="margin-top:8px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                            background:<?= ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? '#DCFCE7' : '#F1F5F9' ?>;
                            color:<?=    ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? '#15803D' : '#64748B' ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block"></span>
                            <?= ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? 'Enabled' : 'Disabled' ?>
                        </span>
                    </div>
                </div>
                <!-- Toggle switch -->
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px" title="Toggle tracking-link-change email notification">
                    <input type="checkbox" name="offer_link_notify" value="1"
                        <?= ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? 'checked' : '' ?>
                        style="opacity:0;width:0;height:0"
                        onchange="this.closest('form').submit()">
                    <span style="position:absolute;cursor:pointer;inset:0;
                        background:<?= ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? '#10B981' : '#CBD5E1' ?>;
                        border-radius:34px;transition:.3s">
                        <span style="position:absolute;height:22px;width:22px;
                            left:<?= ($cfg['app']['offer_link_notify'] ?? '0') === '1' ? '27px' : '3px' ?>;
                            bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)">
                        </span>
                    </span>
                </label>
            </div>

            <p style="margin:18px 0 0;font-size:12px;color:#94A3B8">
                More notification types (affiliate approvals, conversion alerts, etc.) can be added here in future updates.
            </p>
        </form>
    </div>
</div>

<!-- ─── FRAUD REPORTS ──────────────────────────────────── -->
<?php elseif ($activeTab === 'fraud_reports'):
    $fraudRepEnabled  = ($cfg['fraud_reports']['enabled']        ?? '0') === '1';
    $fraudRepEmail    = ($cfg['fraud_reports']['send_email']     ?? '1') === '1';
    $fraudRepInterval = (int)($cfg['fraud_reports']['interval_hours'] ?? 24);
    if (!in_array($fraudRepInterval, [1,6,12,24])) $fraudRepInterval = 24;

    // Auto-provision token if missing
    $cronToken = trim((string)($cfg['fraud_reports']['cron_token'] ?? ''));
    if ($cronToken === '') {
        try { $cronToken = bin2hex(random_bytes(20)); } catch (\Throwable $_) { $cronToken = md5(uniqid('fr',true)); }
        Config::set('config', 'fraud_reports.cron_token', $cronToken);
    }
    $appUrlBase = rtrim($cfg['app']['url'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com')), '/');
    $cronUrl    = $appUrlBase . '/cron/fraud-reports?token=' . urlencode($cronToken);
?>
<div class="card" style="max-width:760px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">⚡ Automated Affiliate Fraud Reports</span>
        <a href="/admin/fraud-center/report-logs" style="font-size:12px;color:#4F46E5;font-weight:600;text-decoration:none">View Report Logs →</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="fraud_reports">

            <!-- Enable / Disable -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:16px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Enable Automated Reports</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">Generates a <strong>separate</strong> Fraud Click Report and Fraud Conversion Report for every affiliate on the configured schedule. Reports are stored in the database and optionally emailed.</div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="fraud_reports_enabled" value="1" <?= $fraudRepEnabled ? 'checked' : '' ?> style="opacity:0;width:0;height:0" id="fraudRepToggle">
                    <span id="fraudRepTrack" style="position:absolute;cursor:pointer;inset:0;background:<?= $fraudRepEnabled ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span id="fraudRepKnob" style="position:absolute;height:22px;width:22px;left:<?= $fraudRepEnabled ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
            </div>

            <!-- Send Email Toggle -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:16px 0;border-bottom:1px solid #F1F5F9;gap:20px">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px;color:#1E293B;margin-bottom:4px">Send Email to Affiliates</div>
                    <div style="font-size:13px;color:#64748B;line-height:1.5">When enabled, each affiliate receives their own Fraud Click Report and Fraud Conversion Report as two separate emails. Reports are always saved to the database regardless of this setting.</div>
                </div>
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;margin-top:4px">
                    <input type="checkbox" name="fraud_reports_send_email" value="1" <?= $fraudRepEmail ? 'checked' : '' ?> style="opacity:0;width:0;height:0" id="fraudEmailToggle">
                    <span id="fraudEmailTrack" style="position:absolute;cursor:pointer;inset:0;background:<?= $fraudRepEmail ? '#10B981' : '#CBD5E1' ?>;border-radius:34px;transition:.3s">
                        <span id="fraudEmailKnob" style="position:absolute;height:22px;width:22px;left:<?= $fraudRepEmail ? '27px' : '3px' ?>;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)"></span>
                    </span>
                </label>
            </div>

            <!-- Interval Hours -->
            <div class="form-group" style="margin-top:18px">
                <label>Execution Frequency</label>
                <select name="fraud_reports_interval_hours" class="form-control">
                    <option value="1"  <?= $fraudRepInterval === 1  ? 'selected' : '' ?>>Every 1 Hour</option>
                    <option value="6"  <?= $fraudRepInterval === 6  ? 'selected' : '' ?>>Every 6 Hours</option>
                    <option value="12" <?= $fraudRepInterval === 12 ? 'selected' : '' ?>>Every 12 Hours</option>
                    <option value="24" <?= $fraudRepInterval === 24 ? 'selected' : '' ?>>Every 24 Hours (Daily)</option>
                </select>
                <div class="form-hint">How often the cron runs. Your cron job must be set to run at least as frequently as this setting (e.g. every hour). Reports for affiliates with zero fraud activity are still saved but emails are skipped.</div>
            </div>

            <!-- Cron Token -->
            <div class="form-group">
                <label>Cron Security Token</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" value="<?= Helpers::e($cronToken) ?>" id="fraudCronToken" class="form-control" readonly style="font-family:monospace;font-size:12px;background:#F8FAFC;color:#334155">
                    <button type="submit" name="fraud_reports_reset_token" value="1" class="btn btn-sm" style="background:#FEF2F2;color:#EF4444;border:1px solid #FECACA;white-space:nowrap" onclick="return confirm('Regenerate token? The old Cron URL will stop working immediately.')">
                        🔄 New Token
                    </button>
                </div>
                <div class="form-hint">This token secures your cron URL. Keep it private.</div>
            </div>

            <!-- Cron URL -->
            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;padding:16px;margin-bottom:20px">
                <div style="font-size:13px;color:#3730A3;font-weight:700;margin-bottom:8px">🔗 Cron URL (copy this into aaPanel → Cron Jobs)</div>
                <div style="display:flex;gap:8px;align-items:center">
                    <code id="fraudCronUrl" style="display:block;flex:1;background:#fff;padding:10px 14px;border:1px solid #C7D2FE;border-radius:6px;font-size:11px;color:#1E293B;word-break:break-all;line-height:1.6"><?= Helpers::e($cronUrl) ?></code>
                    <button type="button" onclick="frCopyUrl()" class="btn btn-sm" style="background:#4F46E5;color:#fff;border:none;white-space:nowrap;flex-shrink:0">📋 Copy URL</button>
                </div>
                <div style="margin-top:10px;font-size:12px;color:#6366F1;line-height:1.5">
                    <b>aaPanel setup:</b> Go to <strong>Cron Jobs</strong> → Add Cron Job → Type: <strong>URL</strong> → paste URL above → Frequency: every hour (or match your frequency setting above).
                </div>
            </div>

            <!-- Report Sections Info -->
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;margin-bottom:20px">
                <div style="font-size:13px;color:#166534;font-weight:700;margin-bottom:8px">📊 Two Separate Reports Per Affiliate</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px;color:#15803D">
                    <div>
                        <div style="font-weight:700;margin-bottom:4px">🖱 Fraud Click Report includes:</div>
                        <ul style="margin:0;padding-left:16px;line-height:1.8">
                            <li>Fraud Clicks &amp; Blocked Clicks</li>
                            <li>Proxy Traffic Detection</li>
                            <li>VPN Traffic Detection</li>
                            <li>Bot Traffic Detection</li>
                            <li>Datacenter Traffic Detection</li>
                            <li>Click Quality Score</li>
                        </ul>
                    </div>
                    <div>
                        <div style="font-weight:700;margin-bottom:4px">💰 Fraud Conversion Report includes:</div>
                        <ul style="margin:0;padding-left:16px;line-height:1.8">
                            <li>Fraud Conversions &amp; Fraud Rate</li>
                            <li>Invalid Leads</li>
                            <li>Suspicious Conversion Activity (&lt;30s)</li>
                            <li>Conversion Quality Score</li>
                        </ul>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Fraud Report Settings</button>
        </form>
    </div>
</div>
<script>
(function(){
    function mkToggle(cbId, trackId, knobId) {
        var cb = document.getElementById(cbId);
        var t  = document.getElementById(trackId);
        var k  = document.getElementById(knobId);
        if (!cb) return;
        cb.addEventListener('change', function(){
            t.style.background = cb.checked ? '#10B981' : '#CBD5E1';
            k.style.left       = cb.checked ? '27px'    : '3px';
        });
    }
    mkToggle('fraudRepToggle',   'fraudRepTrack',   'fraudRepKnob');
    mkToggle('fraudEmailToggle', 'fraudEmailTrack', 'fraudEmailKnob');
})();
function frCopyUrl() {
    var el = document.getElementById('fraudCronUrl');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim()).then(function() {
        var btn = event.target; var orig = btn.textContent;
        btn.textContent = '✅ Copied!'; btn.style.background = '#10B981';
        setTimeout(function(){ btn.textContent = orig; btn.style.background = '#4F46E5'; }, 2000);
    });
}
</script>

<?php elseif ($activeTab === 'space_engine'): ?>
<div class="card" style="max-width:680px">
    <div class="card-header"><span class="card-title">3D Background Settings</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="space_engine">
            
            <div class="form-group">
                <label class="form-check" style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px;color:var(--text);margin-bottom:6px">
                    <input type="checkbox" name="space_engine_enabled" value="1" <?= (Config::get('config', 'space_engine.enabled') !== '0') ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer">
                    <span>Enable 3D Galaxy background effect (Light Mode)</span>
                </label>
                <div class="form-hint">Enables/disables the floating stars, cosmic dust, and rotating galaxy animation in Light Mode.</div>
            </div>

            <div class="form-group">
                <label>Rotation Speed</label>
                <input type="range" name="space_engine_speed" min="0" max="2" step="0.1" class="form-control" style="height:auto;accent-color:var(--primary);cursor:pointer" value="<?= htmlspecialchars(Config::get('config', 'space_engine.speed') ?: '1.0') ?>">
                <div class="form-hint">Controls how fast the galaxy arms rotate. Default: 1.0</div>
            </div>

            <div class="form-group">
                <label>Star Density</label>
                <input type="range" name="space_engine_density" min="0.2" max="2" step="0.1" class="form-control" style="height:auto;accent-color:var(--primary);cursor:pointer" value="<?= htmlspecialchars(Config::get('config', 'space_engine.density') ?: '1.0') ?>">
                <div class="form-hint">Controls the density/number of star particles. Default: 1.0</div>
            </div>

            <div class="form-group">
                <label>Motion Parallax</label>
                <input type="range" name="space_engine_motion" min="0" max="2" step="0.1" class="form-control" style="height:auto;accent-color:var(--primary);cursor:pointer" value="<?= htmlspecialchars(Config::get('config', 'space_engine.motion') ?: '1.0') ?>">
                <div class="form-hint">Controls camera reaction to mouse movement and page scroll. Default: 1.0</div>
            </div>

            <div class="form-group">
                <label>Global Market Status Glow</label>
                <select name="space_engine_market_status" class="form-control">
                    <option value="neutral" <?= Config::get('config', 'space_engine.market_status') === 'neutral' ? 'selected' : '' ?>>Neutral (Soft Blue/Purple)</option>
                    <option value="positive" <?= Config::get('config', 'space_engine.market_status') === 'positive' ? 'selected' : '' ?>>Positive Market (Soft Emerald Green)</option>
                    <option value="negative" <?= Config::get('config', 'space_engine.market_status') === 'negative' ? 'selected' : '' ?>>Negative Market (Soft Ruby Red)</option>
                </select>
                <div class="form-hint">Simulate a global market sentiment layout lighting glow color.</div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
document.querySelectorAll('[name=refer_commission_type]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('rateLabel').textContent = this.value === 'percent' ? '%' : 'USD';
        document.getElementById('rateHint').textContent = this.value === 'percent'
            ? 'e.g. 5 means the referrer earns 5% of each payout generated by their referred affiliate.'
            : 'e.g. 1.00 means the referrer earns $1.00 for each conversion made by their referred affiliate.';
    });
});
</script>

<style>@keyframes adtSpin { to { transform:rotate(360deg); } }</style>
<script>
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
<script>
// Scroll active settings tab into view on mobile
(function() {
    var activeTab = document.querySelector('.settings-tab.active');
    if (activeTab) {
        activeTab.scrollIntoView({ behavior: 'instant', block: 'nearest', inline: 'center' });
    }
})();
</script>
