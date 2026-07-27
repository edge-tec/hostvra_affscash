<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<style>
<?php $bannerStyle = Config::get('config', 'app.dashboard_banner_style') ?? (Config::get('config', 'app.transparent_dashboard') === '1' ? 'transparent' : 'glass_purple'); ?>
/* ── Dashboard Header + Filter ───────────────────────────────────────── */
.dash-header{
    <?php if ($bannerStyle === 'transparent'): ?>
    background:transparent;
    padding:10px 0;
    <?php elseif ($bannerStyle === 'glass'): ?>
    background:rgba(255,255,255,.55);
    -webkit-backdrop-filter:blur(16px) saturate(180%);
            backdrop-filter:blur(16px) saturate(180%);
    border:1px solid rgba(255,255,255,.65);
    border-radius:14px;padding:22px 26px;
    box-shadow:0 8px 32px rgba(31,38,135,.15);
    <?php elseif ($bannerStyle === 'glass_purple'): ?>
    background:linear-gradient(135deg, rgba(30,27,75,0.85) 0%, rgba(49,46,129,0.85) 50%, rgba(76,29,149,0.85) 100%);
    -webkit-backdrop-filter:blur(20px) saturate(180%);
            backdrop-filter:blur(20px) saturate(180%);
    border:1px solid rgba(255,255,255,0.15);
    border-radius:14px;padding:22px 26px;
    box-shadow:0 8px 32px rgba(31,38,135,0.25);
    <?php else: ?>
    background:linear-gradient(135deg,#1E1B4B 0%,#2D1B69 50%,#4C1D95 100%);
    border-radius:14px;padding:22px 26px;
    <?php endif; ?>
    margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden;
}
html[data-theme="dark"] .dash-header {
    <?php if ($bannerStyle === 'glass'): ?>
    background:rgba(20,24,48,.55);
    border-color:rgba(148,163,184,.22);
    box-shadow:0 8px 32px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
    <?php endif; ?>
}
<?php if ($bannerStyle === 'transparent' || $bannerStyle === 'glass'): ?>
.dash-header::before, .dash-header::after { display: none !important; }
.dash-hdr-title { color: var(--text) !important; }
.dash-hdr-label { color: var(--text-muted) !important; }
.dash-hdr-updated { color: var(--text-light) !important; }
.dash-f-input { background: var(--card-bg) !important; color: var(--text) !important; border: 1px solid var(--border) !important; }
.dash-f-input option { background: var(--card-bg) !important; color: var(--text) !important; }
.dash-period-tabs { background: var(--card-bg) !important; border: 1px solid var(--border) !important; }
.dash-period-tab { color: var(--text-muted) !important; }
.dash-period-tab.active { background: var(--bg) !important; color: var(--text) !important; }
.dash-btn-apply { box-shadow: none !important; }
.dash-btn-reset { border-color: var(--border) !important; color: var(--text) !important; }
<?php elseif ($bannerStyle === 'glass_purple'): ?>
.dash-header::before{content:'';position:absolute;top:-60px;right:-40px;width:300px;height:300px;background:radial-gradient(circle, rgba(168,85,247,0.4) 0%, rgba(168,85,247,0) 70%);border-radius:50%;filter:blur(15px);z-index:0;}
.dash-header::after{content:'';position:absolute;bottom:-80px;left:20%;width:400px;height:400px;background:radial-gradient(circle, rgba(56,189,248,0.3) 0%, rgba(56,189,248,0) 70%);border-radius:50%;filter:blur(20px);z-index:0;}
<?php else: ?>
.dash-header::before{content:'';position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%;}
.dash-header::after{content:'';position:absolute;bottom:-60px;left:30%;width:280px;height:280px;background:rgba(255,255,255,.03);border-radius:50%;}
<?php endif; ?>
.dash-hdr-left{position:relative;z-index:1;}
.dash-hdr-label{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.55);margin-bottom:4px;}
.dash-hdr-title{font-size:24px;font-weight:800;color:#fff;line-height:1.1;}
.dash-hdr-updated{font-size:12px;color:rgba(255,255,255,.5);margin-top:4px;}
.dash-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;position:relative;z-index:1;}
.dash-period-tabs{
    display:flex;flex-wrap:wrap;
    background:rgba(255,255,255,.04);
    -webkit-backdrop-filter:blur(14px) saturate(170%);
            backdrop-filter:blur(14px) saturate(170%);
    border:1px solid rgba(255,255,255,.10);
    border-radius:10px;padding:3px;gap:2px;
}
.dash-period-tab{padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;color:rgba(255,255,255,.70);cursor:pointer;transition:background .15s,color .15s;border:none;background:transparent;}
.dash-period-tab:hover{background:rgba(255,255,255,.08);color:#fff;}
.dash-period-tab.active{
    background:rgba(255,255,255,.14);
    -webkit-backdrop-filter:blur(8px);
            backdrop-filter:blur(8px);
    color:#fff;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.20);
}
.dash-f-input{
    background:rgba(255,255,255,.06);
    -webkit-appearance:none;-moz-appearance:none;appearance:none;
    border:1px solid rgba(255,255,255,.18);
    border-radius:10px;
    padding:8px 12px;
    color:#fff;font-size:13px;font-weight:500;
    outline:none;
    -webkit-backdrop-filter:blur(16px) saturate(180%);
            backdrop-filter:blur(16px) saturate(180%);
    transition:background .15s,border-color .15s;
    min-width:110px;
    color-scheme:dark;
}
.dash-f-input:hover{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.28);}
.dash-f-input:focus{
    background:rgba(255,255,255,.14);
    border-color:rgba(168,85,247,.55);
    box-shadow:0 0 0 3px rgba(168,85,247,.18);
}
select.dash-f-input{
    padding-right:30px;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path fill='none' stroke='%23ffffff' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round' d='M1 1l4 4 4-4'/></svg>");
    background-repeat:no-repeat;
    background-position:right 12px center;
}
.dash-f-input option{background:#2D1B69;color:#fff;}
.dash-btn-apply{background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:opacity .15s,transform .15s,box-shadow .15s;white-space:nowrap;box-shadow:0 6px 18px rgba(124,58,237,.35);}
.dash-btn-apply:hover{opacity:.92;box-shadow:0 10px 26px rgba(124,58,237,.45);}
.dash-btn-reset{
    background:rgba(255,255,255,.05);
    -webkit-backdrop-filter:blur(14px);
            backdrop-filter:blur(14px);
    color:#fff;
    border:1px solid rgba(255,255,255,.20);
    border-radius:10px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;
    transition:background .15s,border-color .15s;white-space:nowrap;
}
.dash-btn-reset:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.35);}
@media(max-width:1100px){.dash-header{flex-direction:column;align-items:flex-start;}}
@media(max-width:640px){.dash-filters{gap:6px;}.dash-f-input{min-width:0;flex:1 1 calc(50% - 4px);}.dash-period-tab{padding:5px 8px;font-size:11px;}}
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}

/* ═══════════════════════════════════════════════════════════════════════
   KPI CARD STYLES — 3D Glassmorphism Premium (Responsive Grid)
   ═══════════════════════════════════════════════════════════════════════ */
.kpi-grid, .an-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.kpi-card, .an-kpi-card {
    background: rgba(255, 255, 255, 0.75) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 20px !important;
    padding: 18px 20px !important;
    position: relative !important;
    overflow: hidden !important;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s !important;
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important;
    min-width: 0 !important;
}

html[data-theme="dark"] .kpi-card, html[data-theme="dark"] .an-kpi-card {
    background: rgba(15, 23, 42, 0.65) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35) !important;
}

.kpi-card:hover, .an-kpi-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.12) !important;
}

html[data-theme="dark"] .kpi-card:hover, html[data-theme="dark"] .an-kpi-card:hover {
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.5) !important;
}

.kpi-icon, .an-kpi-icon {
    width: 48px !important;
    height: 48px !important;
    border-radius: 14px !important;
    background: var(--kpi-color, #3B82F6) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #ffffff !important;
    box-shadow: 0 8px 20px var(--kpi-shadow, rgba(59, 130, 246, 0.35)) !important;
    flex-shrink: 0 !important;
    font-size: 20px !important;
}

.kpi-content, .an-kpi-content {
    flex-grow: 1 !important;
    min-width: 0 !important;
    overflow: hidden !important;
}

.kpi-sparkline, .an-kpi-sparkline {
    position: absolute !important;
    bottom: 0 !important;
    right: 0 !important;
    width: 55% !important;
    height: 65% !important;
    opacity: 0.22 !important;
    background: linear-gradient(180deg, transparent 0%, var(--kpi-color) 100%) !important;
    clip-path: polygon(0 100%, 15% 75%, 35% 85%, 55% 55%, 75% 70%, 100% 30%, 100% 100%) !important;
    pointer-events: none !important;
}

.kpi-card.blue, .an-kpi-card.blue { --kpi-color: #3B82F6; --kpi-shadow: rgba(59, 130, 246, 0.35); }
.kpi-card.green, .an-kpi-card.green { --kpi-color: #10B981; --kpi-shadow: rgba(16, 185, 129, 0.35); }
.kpi-card.purple, .an-kpi-card.purple, .kpi-card.violet, .an-kpi-card.violet { --kpi-color: #8B5CF6; --kpi-shadow: rgba(139, 92, 246, 0.35); }
.kpi-card.emerald, .an-kpi-card.emerald { --kpi-color: #059669; --kpi-shadow: rgba(5, 150, 105, 0.35); }
.kpi-card.teal, .an-kpi-card.teal, .kpi-card.cyan, .an-kpi-card.cyan { --kpi-color: #06B6D4; --kpi-shadow: rgba(6, 182, 212, 0.35); }
.kpi-card.orange, .an-kpi-card.orange { --kpi-color: #F59E0B; --kpi-shadow: rgba(245, 158, 11, 0.35); }
.kpi-card.indigo, .an-kpi-card.indigo { --kpi-color: #4F46E5; --kpi-shadow: rgba(79, 70, 229, 0.35); }
.kpi-card.red, .an-kpi-card.red { --kpi-color: #EF4444; --kpi-shadow: rgba(239, 68, 68, 0.35); }

.kpi-label, .an-kpi-label { font-size: 11px !important; font-weight: 700 !important; text-transform: uppercase !important; letter-spacing: .05em !important; color: var(--text-muted, #64748B) !important; margin-bottom: 4px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
.kpi-value, .an-kpi-value { font-size: 24px !important; font-weight: 800 !important; color: var(--text, #0F172A) !important; line-height: 1.1 !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
html[data-theme="dark"] .kpi-value, html[data-theme="dark"] .an-kpi-value { color: #F8FAFC !important; }
.kpi-sub, .an-kpi-sub { font-size: 12px !important; color: var(--text-light, #94A3B8) !important; margin-top: 4px !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }
.kpi-trend, .an-kpi-trend { display: inline-flex !important; align-items: center !important; gap: 3px !important; font-size: 11px !important; font-weight: 700 !important; padding: 2px 8px !important; border-radius: 20px !important; margin-top: 6px !important; white-space: nowrap !important; }
.kpi-trend.up, .an-kpi-trend.up { background: rgba(16, 185, 129, 0.12) !important; color: #10B981 !important; }
.kpi-trend.down, .an-kpi-trend.down { background: rgba(239, 68, 68, 0.12) !important; color: #EF4444 !important; }
.kpi-trend.flat, .an-kpi-trend.flat { background: rgba(148, 163, 184, 0.12) !important; color: #64748B !important; }
.dash-header{padding:18px 18px;border-radius:12px;}
    .dash-hdr-title{font-size:22px;}
    .kpi-grid{grid-template-columns:repeat(2,1fr);gap:10px;}
    .kpi-card{padding:14px;}
    .kpi-value{font-size:22px;}
    .toggle-btns{flex-wrap:wrap;gap:6px;padding:6px;}
    .toggle-btns button{padding:5px 10px;font-size:11px;min-height:32px;}
}
@media(max-width:640px){
    .dash-header{padding:16px 14px;}
    .dash-header{padding:14px 12px;}
    .dash-hdr-title{font-size:18px;}
    .dash-hdr-label{font-size:9px;}
    .kpi-grid{grid-template-columns:repeat(2,1fr);gap:10px;}
    .kpi-value{font-size:18px;}
    .kpi-label{font-size:10px;}
    .charts-2{grid-template-columns:1fr;}
    .chart-card .card-header{padding:10px 12px;}
    .chart-card .card-title{font-size:12px;}
    .chart-wrap{height:200px;}
    .dash-f-input{font-size: 11px; padding: 6px 10px;}
    .dash-btn-apply,.dash-btn-reset{font-size: 11px; padding: 6px 10px;}
    .dash-filters{gap:6px;}
    .dash-f-input{min-width:0;flex:1 1 calc(50% - 4px);}
    .dash-period-tab{padding:5px 8px;font-size:11px;}
}

/* 360-480px (small phone) */
@media(max-width:480px){
    .dash-period-tab{flex:1 1 calc(50% - 4px);font-size:11px;padding:6px 4px;text-align:center}
    .dash-filters{flex-direction:column;align-items:stretch}
    .dash-f-input{width:100%;min-width:0}
}
</style>

<div id="live-badge-mgr" class="live-badge-mgr"><span class="lp"></span><span id="live-badge-mgr-txt">Live</span></div>

<!-- ── Header + Filter Bar ─────────────────────────────────────────── -->
<div class="dash-header" style="position:relative; overflow:hidden;">
    <canvas class="an-header-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
    <div class="dash-hdr-left">
        <div class="dash-hdr-label">Affiliate Network</div>
        <div class="dash-hdr-title">Analytics Dashboard</div>
        <div class="dash-hdr-updated" id="dash-updated">Loading data…</div>
    </div>
    <button type="button" class="dash-filter-toggle-btn" onclick="document.querySelector('.dash-filters').classList.toggle('open'); this.classList.toggle('open')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filter Options</span>
        <span class="dash-toggle-chevron">▼</span>
    </button>
    <div class="dash-filters">
        <div class="dash-period-tabs">
            <button class="dash-period-tab" onclick="setPeriod(this,'today')">Today</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'yesterday')">Yesterday</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'7d')">7D</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'15d')">Last 15D</button>
            <button class="dash-period-tab active" onclick="setPeriod(this,'30d')">30D</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'90d')">90D</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'mtd')">This Month</button>
            <button class="dash-period-tab" onclick="setPeriod(this,'lastmonth')">Last Month</button>
        </div>
        <input type="date" class="dash-f-input" id="f-from" value="<?= date('Y-m-d', strtotime('-29 days')) ?>">
        <span style="color:rgba(255,255,255,.4);font-size:13px">—</span>
        <input type="date" class="dash-f-input" id="f-to" value="<?= date('Y-m-d') ?>">
        <select class="dash-f-input" id="f-offer" style="min-width:120px"><option value="">All Offers</option></select>
        <select class="dash-f-input" id="f-affiliate" style="min-width:120px"><option value="">All Affiliates</option></select>
        <select class="dash-f-input" id="f-country" style="min-width:110px"><option value="">All Countries</option></select>
        <select class="dash-f-input" id="f-device" style="min-width:110px">
            <option value="">All Devices</option>
            <option value="Desktop">Desktop</option>
            <option value="Mobile">Mobile</option>
            <option value="Tablet">Tablet</option>
        </select>
        <button class="dash-btn-apply" onclick="applyFilters()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
        </button>
        <button class="dash-btn-reset" onclick="resetFilters()">Reset</button>
    </div>
</div>

<!-- ── KPI Cards ──────────────────────────────────────────────────────── -->
<div class="kpi-grid">
    <div class="kpi-card blue">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Managed Affiliates</div>
        <div class="kpi-value"><?= $totalAffiliates ?></div>
        <div class="kpi-sub">Under your management</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card blue">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Total Clicks</div>
        <div class="kpi-value" id="k-clicks">—</div>
        <div class="kpi-sub">Unique: <span id="k-unique">—</span></div>
        <div id="k-clicks-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card green">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Conversions</div>
        <div class="kpi-value" id="k-conv">—</div>
        <div class="kpi-sub">CR: <span id="k-cr">—</span>%</div>
        <div id="k-conv-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card teal">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value" id="k-cr-card">—</div>
        <div class="kpi-sub">Overall CR%</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <!-- Fraud Conversion % across all managed affiliates. Filters from the
         dashboard (date / offer / country / device) flow through automatically. -->
    <div class="kpi-card red" title="Fraud conversions are conversions with fraud score between 60–100.">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Fraud Conversion %</div>
        <div class="kpi-value"><span id="k-fraud-conv-pct">—</span>%</div>
        <div class="kpi-sub"><span id="k-fraud-conv">—</span> Fraud Conversions</div>
        <div id="k-fraud-conv-pct-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <!-- Real-time IPQS Fraud Score — avg of IPQualityScore fraud_score values
         from fraud_logs, joined through conversions — last 30 days. -->
    <?php
    $_fsScore = (int)$fraudScoreAgg;
    if ($_fsScore >= 75)     { $_fsLevel = 'high';   $_fsColor = '#EF4444'; }
    elseif ($_fsScore >= 40) { $_fsLevel = 'medium'; $_fsColor = '#F59E0B'; }
    else                     { $_fsLevel = 'low';    $_fsColor = '#10B981'; }
    $_fsBg    = ['high'=>'rgba(239,68,68,.08)','medium'=>'rgba(245,158,11,.08)','low'=>'rgba(16,185,129,.08)'][$_fsLevel];
    $_fsTotal = (int)$fraudScoreCounts['high'] + (int)$fraudScoreCounts['medium'] + (int)$fraudScoreCounts['low'];
    ?>
    <div class="kpi-card green" title="Real-time IPQS Fraud Score — average IPQualityScore fraud_score across all managed affiliate conversions in the last 30 days.">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">IPQS Fraud Score</div>
            <div class="kpi-value" style="color:<?= $_fsColor ?>;display:flex;align-items:baseline;gap:6px">
                <?= $_fsScore ?>
                <span style="font-size:14px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">/100 · <?= ucfirst($_fsLevel) ?></span>
            </div>
            <div class="kpi-sub" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:5px">
                <span style="display:inline-flex;align-items:center;gap:3px">
                    <span style="width:6px;height:6px;border-radius:50%;background:#EF4444;display:inline-block"></span>
                    <?= (int)$fraudScoreCounts['high'] ?> high
                </span>
                <span style="display:inline-flex;align-items:center;gap:3px">
                    <span style="width:6px;height:6px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
                    <?= (int)$fraudScoreCounts['medium'] ?> med
                </span>
                <span style="display:inline-flex;align-items:center;gap:3px">
                    <span style="width:6px;height:6px;border-radius:50%;background:#10B981;display:inline-block"></span>
                    <?= (int)$fraudScoreCounts['low'] ?> low
                </span>
            </div>
            <?php if ($ipqsChecked > 0): ?>
            <div style="margin-top:6px;font-size:10px;color:var(--text-muted)">
                Based on <?= number_format($ipqsChecked) ?> checked conversion<?= $ipqsChecked === 1 ? '' : 's' ?> · last 30 days
            </div>
            <?php elseif ($_fsTotal === 0): ?>
            <div style="margin-top:6px;font-size:10px;color:var(--text-muted)">No conversions checked yet</div>
            <?php endif; ?>
        </div>
        <div class="kpi-sparkline"></div>
    </div>
</div>

<!-- ── Row 1: Performance Trend (full width - PREMIUM SAAS UI) ─────────────────────────── -->
<style>
/* SaaS Glassmorphism Chart Container */
.saas-trend-container {
    background: var(--card-bg, #ffffff);
    border-radius: 24px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0,0,0,0.02);
    border: 1px solid rgba(148, 163, 184, 0.15);
    margin-bottom: 24px;
    position: relative;
    overflow: visible;
    display: flex;
    flex-direction: column;
    font-family: 'Inter', system-ui, sans-serif;
    transition: all 0.3s ease;
}
html[data-theme="dark"] .saas-trend-container {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(24px) saturate(150%);
    -webkit-backdrop-filter: blur(24px) saturate(150%);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255,255,255,0.05);
}

.saas-trend-header {
    padding: 24px 28px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.saas-trend-title-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.saas-section-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text, #111827);
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Intelligent Filter Chips */
.saas-time-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    background: rgba(148, 163, 184, 0.08);
    padding: 4px;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.1);
}
.saas-chip {
    background: transparent;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted, #64748b);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.saas-chip:hover {
    color: var(--text, #111827);
}
.saas-chip.active {
    background: var(--card-bg, #ffffff);
    color: #111827;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
html[data-theme="dark"] .saas-chip.active {
    background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Floating KPI Cards Above Chart */
.saas-kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.saas-kpi-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 16px;
    padding: 16px 20px;
    position: relative;
    overflow: hidden;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
html[data-theme="dark"] .saas-kpi-card {
    background: rgba(20, 24, 48, 0.5);
    border-color: rgba(255,255,255,0.08);
    box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
.saas-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
html[data-theme="dark"] .saas-kpi-card:hover {
    box-shadow: 0 16px 32px rgba(0,0,0,0.4);
}
.saas-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--kpi-color, #3B82F6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 0 20px var(--kpi-color);
    flex-shrink: 0;
}
.saas-kpi-content {
    flex-grow: 1;
}
.saas-kpi-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-muted, #64748B);
    margin-bottom: 4px;
}
.saas-kpi-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text, #111827);
    line-height: 1.1;
    letter-spacing: -0.02em;
}
.saas-kpi-sparkline {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50%;
    height: 60%;
    opacity: 0.25;
    background: linear-gradient(180deg, transparent 0%, var(--kpi-color) 100%);
    clip-path: polygon(0 100%, 10% 80%, 30% 90%, 50% 60%, 70% 80%, 100% 40%, 100% 100%);
}

/* Intelligent Legend */
.saas-legend-container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 4px;
}
.saas-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: rgba(148, 163, 184, 0.05);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted, #64748b);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}
html[data-theme="dark"] .saas-legend-item {
    background: transparent;
    border-color: transparent;
}
.saas-leg-dot {
    width: 12px; height: 12px;
    border-radius: 3px;
    background: var(--leg-color);
    box-shadow: 0 0 10px var(--leg-color);
    transition: all 0.3s;
    opacity: 0.5;
}
.saas-legend-item.active {
    color: var(--text, #111827);
}
html[data-theme="dark"] .saas-legend-item.active {
    color: #ffffff;
}
.saas-legend-item.active .saas-leg-dot {
    opacity: 1;
}

/* Main Chart Body */
.saas-trend-body {
    position: relative;
    padding: 0 20px 24px;
    height: 380px;
}

/* AI Insights Panel */
.saas-ai-insights {
    display: none !important;
    position: absolute;
    top: 24px;
    right: 24px;
    width: 280px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 16px 32px rgba(31, 38, 135, 0.08);
    z-index: 10;
    pointer-events: none;
    transition: opacity 0.3s;
}
html[data-theme="dark"] .saas-ai-insights {
    background: rgba(15, 23, 42, 0.75);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.4);
}
.saas-ai-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text, #111827);
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: linear-gradient(135deg, #4F46E5, #EC4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.saas-ai-list {
    list-style: none;
    padding: 0; margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.saas-ai-list li {
    font-size: 12.5px;
    font-weight: 500;
    color: var(--text-muted, #475569);
    display: flex;
    align-items: flex-start;
    gap: 8px;
    line-height: 1.4;
}
.saas-ai-list li::before {
    content: '✧';
    color: #8B5CF6;
    font-size: 14px;
}

/* Custom Interactive Tooltip */
.saas-custom-tooltip {
    position: absolute;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px) saturate(200%);
    -webkit-backdrop-filter: blur(20px) saturate(200%);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0,0,0,0.05);
    pointer-events: none;
    transform: translate(-50%, 15px);
    transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    z-index: 100;
    min-width: 220px;
}
html[data-theme="dark"] .saas-custom-tooltip {
    background: rgba(15, 23, 42, 0.85);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    color: #fff;
}
.saas-tooltip-date {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
    border-bottom: 1px solid rgba(148,163,184,0.2);
    padding-bottom: 8px;
}
.saas-tooltip-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
}
.saas-tooltip-row:last-child { margin-bottom: 0; }
.saas-tooltip-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-muted, #475569);
}
.saas-tooltip-val {
    color: var(--text, #111827);
    font-weight: 800;
}

@media (max-width: 1024px) {
    .saas-ai-insights { display: none; }
}
@media (max-width: 768px) {
    .saas-trend-body { height: 280px; }
}
</style>

<div class="saas-trend-container mb-3">
    <div class="saas-ai-insights" id="saas-ai-panel" style="opacity:0">
        <div class="saas-ai-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="url(#ai-grad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <defs>
                    <linearGradient id="ai-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4F46E5" />
                        <stop offset="100%" stop-color="#EC4899" />
                    </linearGradient>
                </defs>
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
            AI Insights
        </div>
        <ul class="saas-ai-list" id="saas-ai-list">
            <li>Analyzing trend data...</li>
        </ul>
    </div>

    <div class="saas-trend-header">
        <div class="saas-trend-title-area">
            <span class="saas-section-title">Performance Trend</span>
            <div class="saas-time-filters" id="trend-type-btns">
                <button class="saas-chip active" data-type="line">Line</button>
                <button class="saas-chip" data-type="bar">Bar</button>
            </div>
        </div>

        <div class="saas-legend-container" id="trend-metric-btns">
            <!-- For compatibility with existing JS, we keep the checkboxes visually hidden or just style the buttons -->
            <button class="saas-legend-item active" style="--leg-color: #4F46E5;" data-metric="clicks">
                <span class="saas-leg-dot"></span> Clicks
            </button>
            <button class="saas-legend-item active" style="--leg-color: #10B981;" data-metric="conv">
                <span class="saas-leg-dot"></span> Conversions
            </button>
            <button class="saas-legend-item active" style="--leg-color: #DC2626;" data-metric="fraud">
                <span class="saas-leg-dot"></span> Fraud
            </button>
        </div>
    </div>

    <div class="saas-trend-body">
        <canvas id="trendChart"></canvas>
        <div id="saas-custom-tooltip" class="saas-custom-tooltip"></div>
        <div class="loading-overlay" id="trend-loading" style="display:none"><div class="spinner"></div></div>
    </div>
</div>

<!-- ── Row 2: Hourly + Conversion Pie ────────────────────────────────── -->
<div class="charts-row charts-2 mb-3">
    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Hourly Traffic — Today</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="position:relative">
                <canvas id="hourlyChart"></canvas>
                <div class="loading-overlay" id="hourly-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
    <div class="chart-card">
        <div class="card-header"><span class="card-title">Conversion Status</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center">
            <div style="position:relative;height:240px;width:100%;max-width:280px;margin:0 auto">
                <canvas id="convPieChart"></canvas>
                <div class="loading-overlay" id="pie-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Row 3: Country + Device + Browser ─────────────────────────────── -->
<div class="charts-row charts-3 mb-3">
    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Country Traffic</span>
            <div class="toggle-btns" id="country-view-btns">
                <button class="active" data-view="chart">Chart</button>
                <button data-view="table">Table</button>
            </div>
        </div>
        <div class="card-body" style="position:relative">
            <div id="country-chart-view">
                <div class="chart-wrap" style="position:relative">
                    <canvas id="countryChart"></canvas>
                    <div class="loading-overlay" id="country-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div id="country-table-view" style="display:none;overflow-y:auto;max-height:240px">
                <table class="analytics-table">
                    <thead><tr><th>Country</th><th class="num">Clicks</th><th class="num">Conv</th><th class="num">CR%</th></tr></thead>
                    <tbody id="country-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="chart-card">
        <div class="card-header"><span class="card-title">Devices</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center;position:relative">
            <div style="position:relative;height:220px;width:100%;max-width:220px">
                <canvas id="deviceChart"></canvas>
                <div class="loading-overlay" id="device-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
    <div class="chart-card">
        <div class="card-header"><span class="card-title">Browsers</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center;position:relative">
            <div style="position:relative;height:220px;width:100%;max-width:220px">
                <canvas id="browserChart"></canvas>
                <div class="loading-overlay" id="browser-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Row 4: Top Offers + Top Affiliates ────────────────────────────── -->
<div class="charts-row charts-2 mb-3">
    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Top Offers</span>
            <div class="toggle-btns" id="offers-view-btns">
                <button class="active" data-view="chart">Chart</button>
                <button data-view="table">Table</button>
            </div>
        </div>
        <div class="card-body" style="position:relative">
            <div id="offers-chart-view">
                <div class="chart-wrap" style="position:relative">
                    <canvas id="offersChart"></canvas>
                    <div class="loading-overlay" id="offers-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div id="offers-table-view" style="display:none">
                <div style="overflow-y:auto;max-height:240px">
                    <table class="analytics-table">
                        <thead><tr><th>Offer</th><th class="num">Clicks</th><th class="num">Conv</th><th class="num">CR%</th><th class="num">Payout</th></tr></thead>
                        <tbody id="offers-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Top Affiliates</span>
            <div class="toggle-btns" id="affs-view-btns">
                <button class="active" data-view="chart">Chart</button>
                <button data-view="table">Table</button>
            </div>
        </div>
        <div class="card-body" style="position:relative">
            <div id="affs-chart-view">
                <div class="chart-wrap" style="position:relative">
                    <canvas id="affsChart"></canvas>
                    <div class="loading-overlay" id="affs-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div id="affs-table-view" style="display:none">
                <div style="overflow-y:auto;max-height:240px">
                    <table class="analytics-table">
                        <thead><tr><th>Affiliate</th><th class="num">Clicks</th><th class="num">Conv</th><th class="num">CR%</th><th class="num">Payout</th></tr></thead>
                        <tbody id="affs-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── High Risk Fraud Conversions widget ──────────────────────────────── -->
<?php
$_dashFraudRiskSql = FraudAutoNotify::highRiskWhereSql('cv');
$_dashAffIds       = Auth::managerAffiliateIds();
$_dashFraud        = [];
$_dashFraudCount   = 0;
if (!empty($_dashAffIds)) {
    $_in = implode(',', array_fill(0, count($_dashAffIds), '?'));
    try {
        $_dashFraud = Database::fetchAll(
            "SELECT cv.conversion_id, cv.payout, cv.converted_at, cv.country, cv.ip_address,
                    af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS aff_name,
                    o.name AS offer_name
             FROM conversions cv
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             LEFT JOIN offers o ON o.id = cv.offer_id
             WHERE cv.affiliate_id IN ($_in) AND $_dashFraudRiskSql
             ORDER BY cv.converted_at DESC LIMIT 5",
            $_dashAffIds
        ) ?: [];
        $_dashFraudCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) AS c FROM conversions cv WHERE cv.affiliate_id IN ($_in) AND $_dashFraudRiskSql",
            $_dashAffIds
        )['c'] ?? 0);
    } catch (\Throwable $_e) {}
}
?>
<?php if (!empty($_dashFraud)): ?>
<div class="chart-card mb-3" style="border:1px solid #FCA5A5">
    <div class="card-header" style="background:linear-gradient(135deg,#FEF2F2,#FFE4E6)">
        <span class="card-title" style="color:#991B1B">&#9888; High Risk Fraud Conversions
            <span style="background:#fee2e2;color:#991B1B;border:1px solid #fca5a5;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700;margin-left:6px"><?= number_format($_dashFraudCount) ?> total</span>
        </span>
        <a href="/affiliate_manager/fraud-report" class="btn btn-secondary btn-sm">View Fraud Report</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Risk</th>
                    <th>Affiliate</th>
                    <th>Conversion ID</th>
                    <th>Offer</th>
                    <th>Payout</th>
                    <th>Country</th>
                    <th>Detected At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_dashFraud as $_f): ?>
                <tr>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;white-space:nowrap">
                            &#9888; High Risk
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold" style="font-size:13px"><?= Helpers::e($_f['aff_name']) ?></div>
                        <div class="text-muted" style="font-size:11px;font-family:monospace"><?= Helpers::e($_f['affiliate_code']) ?></div>
                    </td>
                    <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($_f['conversion_id']),0,12) ?>…</td>
                    <td><?= Helpers::e($_f['offer_name'] ?: '—') ?></td>
                    <td>$<?= number_format((float)$_f['payout'], 2) ?></td>
                    <td><?= !empty($_f['country']) ? Helpers::flag($_f['country']) . ' ' . Helpers::e($_f['country']) : '—' ?></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($_f['converted_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Recent Conversions ─────────────────────────────────────────────── -->
<div class="chart-card mb-3">
    <div class="card-header">
        <span class="card-title">Recent Conversions</span>
        <?php if (Auth::hasPermission('view_conversions')): ?>
        <a href="/affiliate_manager/conversions" class="btn btn-secondary btn-sm">View All</a>
        <?php endif; ?>
    </div>
    <div style="position:relative;min-height:80px">
        <div class="loading-overlay" id="conv-loading"><div class="spinner"></div></div>
        <div class="table-wrap" style="overflow-x:auto">
            <table class="analytics-table">
                <thead><tr><th>Affiliate</th><th>Offer</th><th class="num">Payout</th><th>Status</th><th>Country</th><th>Device</th><th>Date</th></tr></thead>
                <tbody id="conv-tbody">
                    <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
const API    = '/api/admin-analytics';
const COLORS = ['#7C3AED','#10B981','#F59E0B','#EF4444','#3B82F6','#4F46E5','#06B6D4','#F97316','#EC4899','#14B8A6'];
const charts = {};
const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const legendColor = isDark ? '#E2E8F0' : '#475569';
let trendMetrics = new Set(['clicks','conv','fraud']);
let trendType    = 'line';
let trendData   = {};
const trendChartStyle = '<?= Config::get('config', 'app.trend_chart_style') ?? 'neon_glow' ?>';

function fmt(n, dec){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:dec||0,maximumFractionDigits:dec||0}); }

function getFilters(){
    return {
        from: document.getElementById('f-from').value,
        to:   document.getElementById('f-to').value,
        offer_id:     document.getElementById('f-offer').value,
        affiliate_id: document.getElementById('f-affiliate').value,
        country:      document.getElementById('f-country').value,
        device:       document.getElementById('f-device').value,
    };
}
function qs(p){ return Object.entries(p).filter(([,v])=>v).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&'); }

function showLoad(id){ const el=document.getElementById(id); if(el)el.style.display='flex'; }
function hideLoad(id){ const el=document.getElementById(id); if(el)el.style.display='none'; }

function trendBadge(id, pct){
    const el=document.getElementById(id);
    if(!el) return;
    if(pct===null||pct===undefined){ el.className='kpi-trend flat'; el.textContent='—'; return; }
    const cls=pct>0?'up':pct<0?'down':'flat';
    el.className='kpi-trend '+cls;
    el.textContent=(pct>0?'▲':pct<0?'▼':'—')+' '+Math.abs(pct)+'% vs prev';
}

function destroyChart(id){ if(charts[id]){ charts[id].destroy(); delete charts[id]; } }
function makeChart(id, cfg){
    destroyChart(id);
    const ctx=document.getElementById(id);
    if(!ctx) return null;
    charts[id]=new Chart(ctx,cfg);
    return charts[id];
}

// KPI Stats
function loadStats(){
    const f=getFilters();
    fetch(API+'?action=stats&'+qs(f))
        .then(r=>r.json()).then(d=>{
            document.getElementById('k-clicks').textContent = fmt(d.clicks);
            document.getElementById('k-unique').textContent = fmt(d.unique);
            document.getElementById('k-conv').textContent    = fmt(d.conv);
            document.getElementById('k-cr').textContent      = fmt(d.cr,2);
            document.getElementById('k-cr-card').textContent = fmt(d.cr,2) + '%';
            // Fraud Conversion % card
            var fp = document.getElementById('k-fraud-conv-pct');
            var fc = document.getElementById('k-fraud-conv');
            if (fp) fp.textContent = fmt(d.fraud_conv_pct ?? 0, 2);
            if (fc) fc.textContent = fmt(d.fraud_conv ?? 0);
            trendBadge('k-clicks-trend',         d.trend?.clicks);
            trendBadge('k-conv-trend',           d.trend?.conv);
            trendBadge('k-fraud-conv-pct-trend', d.trend?.fraud_conv_pct);
        }).catch(()=>{});
}

// Trend
function loadTrend(){
    showLoad('trend-loading');
    fetch(API+'?action=trend&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{ trendData=d; renderTrendChart(); })
        .catch(()=>{}).finally(()=>hideLoad('trend-loading'));
}

function renderTrendChart(){
    const d=trendData;
    if(!d||!d.labels) return;
    
    // Update SaaS KPIs
    if (d.clicks_data && d.conv_data) {
        var totClicks = d.clicks_data.reduce((a,b)=>a+b,0);
        var totConv = d.conv_data.reduce((a,b)=>a+b,0);
        var totFraud = d.fraud_data ? d.fraud_data.reduce((a,b)=>a+b,0) : 0;
        
        var elClicks = document.getElementById('saas-kpi-clicks');
        if(elClicks) elClicks.textContent = fmt(totClicks);
        var elConv = document.getElementById('saas-kpi-conv');
        if(elConv) elConv.textContent = fmt(totConv);
        
        var elFraud = document.getElementById('saas-kpi-fraud');
        var fRate = 0;
        if(elFraud) {
            fRate = totConv > 0 ? (totFraud/totConv*100).toFixed(1) : 0;
            elFraud.textContent = fRate + '%';
        }
        
        // Generate AI Insights
        var aiPanel = document.getElementById('saas-ai-panel');
        var aiList = document.getElementById('saas-ai-list');
        if (aiPanel && aiList && d.labels.length > 0) {
            aiPanel.style.opacity = '1';
            var insights = [];
            
            // Trend analysis
            if (d.conv_data.length >= 4) {
                var mid = Math.floor(d.conv_data.length / 2);
                var sum1 = d.conv_data.slice(0, mid).reduce((a,b)=>a+b,0);
                var sum2 = d.conv_data.slice(mid).reduce((a,b)=>a+b,0);
                if (sum2 > sum1 && sum1 > 0) {
                    var pct = Math.round(((sum2 - sum1) / sum1) * 100);
                    insights.push("Conversions trending UP by " + pct + "% recently.");
                } else if (sum1 > sum2 && sum2 > 0) {
                    insights.push("Conversion momentum has slowed down recently.");
                }
            } else if (d.clicks_data) {
                var maxClicks = Math.max(...d.clicks_data);
                var maxCIdx = d.clicks_data.indexOf(maxClicks);
                if (maxClicks > 0) insights.push("Peak traffic on " + d.labels[maxCIdx] + ".");
            }
            
            // CR insight
            var overallCR = totClicks > 0 ? (totConv/totClicks*100).toFixed(1) : 0;
            if (overallCR > 0) {
                insights.push("Average Conversion Rate is " + overallCR + "%.");
            }
            
            // Fraud insight
            if (fRate > 10) {
                insights.push("Warning: Fraud rate is elevated at " + fRate + "%.");
            } else {
                insights.push("Fraud activity remains within safe limits.");
            }
            
            aiList.innerHTML = insights.map(i => '<li>' + i + '</li>').join('');
        }
    }

    const metricMap={
        clicks: {key:'clicks', label:'Clicks',                  data:d.clicks_data,  color:'#4F46E5', axis:'y'},
        conv:   {key:'conv',   label:'Conversions',             data:d.conv_data,    color:'#10B981', axis:'y'},
        payout: {key:'payout', label:'Payout ($)',              data:d.payout_data,  color:'#F59E0B', axis:'y1', isCur:true},
        fraud:  {key:'fraud',  label:'Fraud',                   data:d.fraud_data,   color:'#DC2626', axis:'y'},
    };
    const order  = ['clicks','conv','payout','fraud'];
    const active = order.filter(k => trendMetrics.has(k));
    const isLine = typeof trendType !== 'undefined' ? (trendType === 'line') : true;

    const datasets = active.map(k => {
        const m = metricMap[k];
        return {
            label: m.label,
            data: m.data || [],
            borderColor: m.color,
            shadowColor: m.color,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea || !isLine) return m.color + '1A';
                let gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, m.color + '80');
                gradient.addColorStop(0.5, m.color + '20');
                gradient.addColorStop(1, m.color + '00');
                return gradient;
            },
            fill: true,
            tension: 0.45,
            borderWidth: 3,
            pointRadius: 0, 
            pointHoverRadius: 8,
            pointBackgroundColor: m.color,
            pointBorderColor: '#ffffff',
            pointHoverBorderWidth: 3,
            yAxisID: m.axis,
            isCur: !!m.isCur
        };
    });
    
    // Custom Plugin for Line Glow
    var glowPlugin = {
        id: 'glowPlugin',
        beforeDatasetDraw: function(chart, args, options) {
            const ctx = chart.ctx;
            ctx.save();
            ctx.shadowColor = args.meta.dataset.shadowColor || 'transparent';
            ctx.shadowBlur = 15;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 4;
        },
        afterDatasetDraw: function(chart, args, options) {
            chart.ctx.restore();
        }
    };

    const getOrCreateTooltip = (chart) => {
        let tooltipEl = document.getElementById('saas-custom-tooltip');
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.id = 'saas-custom-tooltip';
            tooltipEl.classList.add('saas-custom-tooltip');
            chart.canvas.parentNode.appendChild(tooltipEl);
        }
        return tooltipEl;
    };

    const externalTooltipHandler = (context) => {
        const {chart, tooltip} = context;
        const tooltipEl = getOrCreateTooltip(chart);

        if (tooltip.opacity === 0) {
            tooltipEl.style.opacity = 0;
            return;
        }

        if (tooltip.body) {
            const titleLines = tooltip.title || [];
            let innerHtml = '<div class="saas-tooltip-date">' + titleLines[0] + '</div>';
            
            tooltip.dataPoints.forEach((dp, i) => {
                const ds = chart.data.datasets[dp.datasetIndex];
                const color = ds.borderColor;
                const label = ds.label;
                const val = dp.parsed.y;
                let displayVal = ds.isCur ? '$'+fmt(val,2) : fmt(val);
                
                innerHtml += `
                    <div class="saas-tooltip-row">
                        <div class="saas-tooltip-label">
                            <span style="width:10px;height:10px;border-radius:50%;background:${color};box-shadow:0 0 6px ${color}"></span>
                            ${label}
                        </div>
                        <div class="saas-tooltip-val">${displayVal}</div>
                    </div>
                `;
            });
            tooltipEl.innerHTML = innerHtml;
        }

        const position = context.chart.canvas.getBoundingClientRect();
        let left = tooltip.caretX;
        let top = tooltip.caretY - 15;
        if (left < 100) left = 100;
        if (left > position.width - 100) left = position.width - 100;

        tooltipEl.style.opacity = 1;
        tooltipEl.style.left = left + 'px';
        tooltipEl.style.top = top + 'px';
    };

    const hasCur   = active.some(k => metricMap[k].isCur);
    const hasCount = active.some(k => !metricMap[k].isCur);
    
    makeChart('trendChart',{
        type: isLine ? 'line' : 'bar',
        data: { labels: d.labels, datasets },
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{
                legend:{ display:false },
                tooltip:{ 
                    enabled: false,
                    external: externalTooltipHandler
                }
            },
            scales:{
                x: {
                    grid:{display:true, color:'rgba(148,163,184,0.05)', tickLength:0},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B'}
                },
                y: {
                    display:hasCount, beginAtZero:true, position:'left', border:{display:false},
                    grid:{display:true, color:'rgba(148,163,184,0.05)', tickLength:0},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B', callback:v=>fmt(v)},
                },
                y1:{
                    display:hasCur, beginAtZero:true, position:'right', border:{display:false},
                    grid:{display:false},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B', callback:v=>'$'+fmt(v)},
                }
            }
        },
        plugins: isLine ? [glowPlugin] : []
    });
}

// Hourly
function loadHourly(){
    showLoad('hourly-loading');
    fetch(API+'?action=hourly&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            makeChart('hourlyChart',{type:'bar',data:{labels:d.labels,datasets:[{label:'Clicks',data:d.data,backgroundColor:'rgba(124,58,237,.7)',borderColor:'#7C3AED',borderWidth:1,borderRadius:3}]},
                options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{display:false}},x:{grid:{display:false},ticks:{maxRotation:0,font:{size:10}}}}}});
        }).catch(()=>{}).finally(()=>hideLoad('hourly-loading'));
}

// Conversion Pie
function loadConvPie(){
    showLoad('pie-loading');
    const pieColors={'approved':'#10B981','pending':'#F59E0B','rejected':'#EF4444','chargebacked':'#94A3B8'};
    fetch(API+'?action=conversions&'+qs(getFilters())+'&limit=200')
        .then(r=>r.json()).then(d=>{
            const counts={};
            (d.rows||[]).forEach(r=>{ counts[r.status]=(counts[r.status]||0)+1; });
            const labels=Object.keys(counts),data=labels.map(l=>counts[l]),colors=labels.map(l=>pieColors[l]||'#64748B');
            makeChart('convPieChart',{type:'doughnut',data:{labels,datasets:[{data,backgroundColor:colors,borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}});
        }).catch(()=>{}).finally(()=>hideLoad('pie-loading'));
}

// Countries
let countryRows=[];
function loadCountries(){
    showLoad('country-loading');
    fetch(API+'?action=countries&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            countryRows=d.rows||[];
            renderCountryChart(); renderCountryTable();
        }).catch(()=>{}).finally(()=>hideLoad('country-loading'));
}
function renderCountryChart(){
    const rows=countryRows.slice(0,10);
    makeChart('countryChart',{type:'bar',data:{labels:rows.map(r=>r.country),datasets:[
        {label:'Clicks',data:rows.map(r=>r.clicks),backgroundColor:'rgba(124,58,237,.7)',borderColor:'#7C3AED',borderWidth:1,borderRadius:3},
        {label:'Conv',data:rows.map(r=>r.conv),backgroundColor:'rgba(16,185,129,.7)',borderColor:'#10B981',borderWidth:1,borderRadius:3}
    ]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{font:{size:11},color:legendColor}}},
        scales:{x:{beginAtZero:true,grid:{display:false}},y:{grid:{display:false},ticks:{font:{size:11}}}}}});
}
function renderCountryTable(){
    const tbody=document.getElementById('country-tbody');
    if(!tbody) return;
    tbody.innerHTML=countryRows.map(r=>{
        const cr=r.clicks>0?(r.conv/r.clicks*100).toFixed(1):'0.0';
        return `<tr><td>${r.country}</td><td class="num">${fmt(r.clicks)}</td><td class="num">${fmt(r.conv)}</td><td class="num">${cr}%</td></tr>`;
    }).join('')||'<tr><td colspan="4" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// Devices
function loadDevices(){
    showLoad('device-loading');
    fetch(API+'?action=devices&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            makeChart('deviceChart',{type:'doughnut',data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:COLORS,borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}});
        }).catch(()=>{}).finally(()=>hideLoad('device-loading'));
}

// Browsers
function loadBrowsers(){
    showLoad('browser-loading');
    fetch(API+'?action=browsers&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            makeChart('browserChart',{type:'doughnut',data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:COLORS.slice(2),borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}});
        }).catch(()=>{}).finally(()=>hideLoad('browser-loading'));
}

// Offers
let offersRows=[];
function loadOffers(){
    showLoad('offers-loading');
    fetch(API+'?action=offers&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            offersRows=d.rows||[];
            renderOffersChart(); renderOffersTable();
        }).catch(()=>{}).finally(()=>hideLoad('offers-loading'));
}
function renderOffersChart(){
    const rows=offersRows.slice(0,8);
    makeChart('offersChart',{type:'bar',data:{labels:rows.map(r=>r.name.length>18?r.name.slice(0,18)+'…':r.name),datasets:[{label:'Payout ($)',data:rows.map(r=>r.payout),backgroundColor:COLORS.map(c=>c+'CC'),borderColor:COLORS,borderWidth:1.5,borderRadius:4}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{display:false},ticks:{callback:v=>'$'+v}},x:{grid:{display:false},ticks:{font:{size:11}}}}}});
}
function renderOffersTable(){
    const tbody=document.getElementById('offers-tbody');
    if(!tbody) return;
    tbody.innerHTML=offersRows.map(r=>`<tr><td style="font-weight:600">${r.name}</td><td class="num">${fmt(r.clicks)}</td><td class="num">${fmt(r.conv)}</td><td class="num">${fmt(r.cr,2)}%</td><td class="num" style="color:#7C3AED;font-weight:700">$${fmt(r.payout,2)}</td></tr>`).join('')
        ||'<tr><td colspan="5" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// Affiliates
let affsRows=[];
function loadAffiliates(){
    showLoad('affs-loading');
    fetch(API+'?action=affiliates&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            affsRows=d.rows||[];
            renderAffsChart(); renderAffsTable();
        }).catch(()=>{}).finally(()=>hideLoad('affs-loading'));
}
function renderAffsChart(){
    const rows=affsRows.slice(0,8);
    makeChart('affsChart',{type:'bar',data:{labels:rows.map(r=>r.code||r.name),datasets:[{label:'Payout ($)',data:rows.map(r=>r.payout),backgroundColor:COLORS.slice(1).map(c=>c+'CC'),borderColor:COLORS.slice(1),borderWidth:1.5,borderRadius:4}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{display:false},ticks:{callback:v=>'$'+v}},x:{grid:{display:false},ticks:{font:{size:11}}}}}});
}
function renderAffsTable(){
    const tbody=document.getElementById('affs-tbody');
    if(!tbody) return;
    tbody.innerHTML=affsRows.map(r=>`<tr><td><div style="font-weight:600">${r.name}</div><div style="font-size:11px;color:#94A3B8">${r.code}</div></td><td class="num">${fmt(r.clicks)}</td><td class="num">${fmt(r.conv)}</td><td class="num">${fmt(r.cr,2)}%</td><td class="num" style="color:#7C3AED;font-weight:700">$${fmt(r.payout,2)}</td></tr>`).join('')
        ||'<tr><td colspan="5" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// Recent Conversions
function loadConversions(){
    showLoad('conv-loading');
    const statusBadge={'approved':'success','pending':'warning','rejected':'danger','chargebacked':'muted'};
    fetch(API+'?action=conversions&'+qs(getFilters())+'&limit=15')
        .then(r=>r.json()).then(d=>{
            const tbody=document.getElementById('conv-tbody');
            tbody.innerHTML=(d.rows||[]).map(r=>`<tr>
                <td style="font-weight:600">${r.aff_name||'—'}</td>
                <td style="font-size:12px">${r.offer_name}</td>
                <td class="num">$${fmt(r.payout,2)}</td>
                <td><span class="badge badge-${statusBadge[r.status]||'muted'}">${r.status}</span></td>
                <td style="font-size:12px">${r.country||'—'}</td>
                <td style="font-size:12px">${r.device_type||'—'}</td>
                <td style="font-size:12px;color:#94A3B8">${r.converted_at?r.converted_at.slice(0,16):'—'}</td>
            </tr>`).join('')||'<tr><td colspan="7" class="text-center text-muted" style="padding:24px">No conversions in this period</td></tr>';
        }).catch(()=>{}).finally(()=>hideLoad('conv-loading'));
}

// Filter options
function loadFilterOptions(){
    fetch(API+'?action=filters')
        .then(r=>r.json()).then(d=>{
            const offerSel=document.getElementById('f-offer');
            (d.offers||[]).forEach(o=>{ const opt=document.createElement('option'); opt.value=o.id; opt.textContent=o.name; offerSel.appendChild(opt); });
            const affSel=document.getElementById('f-affiliate');
            (d.affiliates||[]).forEach(a=>{ const opt=document.createElement('option'); opt.value=a.id; opt.textContent=a.label; affSel.appendChild(opt); });
            const cntrSel=document.getElementById('f-country');
            (d.countries||[]).forEach(c=>{ const opt=document.createElement('option'); opt.value=c; opt.textContent=c; cntrSel.appendChild(opt); });
        }).catch(()=>{});
}

window.applyFilters=function(){
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    var u=document.getElementById('dash-updated');
    if(u) u.textContent='Updated '+new Date().toLocaleTimeString();
    loadAll();
};
window.resetFilters=function(){
    document.getElementById('f-from').value='<?= date('Y-m-d', strtotime('-29 days')) ?>';
    document.getElementById('f-to').value='<?= date('Y-m-d') ?>';
    document.getElementById('f-offer').value='';
    document.getElementById('f-affiliate').value='';
    document.getElementById('f-country').value='';
    document.getElementById('f-device').value='';
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    document.querySelector('.dash-period-tab:nth-child(3)')?.classList.add('active');
    loadAll();
};
window.setPeriod=function(btn,val){
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    const now=new Date(),today=now.toISOString().slice(0,10);
    let from=today,to=today;
    // Last-month helpers — first/last day of the previous calendar month, UTC math.
    const lastMonthStart=function(){var y=now.getUTCFullYear(),m=now.getUTCMonth()-1; if(m<0){m=11;y-=1;} return y+'-'+String(m+1).padStart(2,'0')+'-01';};
    const lastMonthEnd  =function(){var d=new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), 0)); return d.getUTCFullYear()+'-'+String(d.getUTCMonth()+1).padStart(2,'0')+'-'+String(d.getUTCDate()).padStart(2,'0');};

    if      (val==='today')    { from=today; to=today; }
    else if (val==='yesterday'){ var y=new Date(now-86400000).toISOString().slice(0,10); from=y; to=y; }
    else if (val==='7d')       { from=new Date(now-6*86400000).toISOString().slice(0,10);  to=today; }
    else if (val==='15d')      { from=new Date(now-14*86400000).toISOString().slice(0,10); to=today; }
    else if (val==='30d')      { from=new Date(now-29*86400000).toISOString().slice(0,10); to=today; }
    else if (val==='90d')      { from=new Date(now-89*86400000).toISOString().slice(0,10); to=today; }
    else if (val==='mtd')      { from=today.slice(0,8)+'01'; to=today; }
    else if (val==='lastmonth'){ from=lastMonthStart(); to=lastMonthEnd(); }
    else return;
    document.getElementById('f-from').value=from;
    document.getElementById('f-to').value=to;
    loadAll();
};

// Trend toggles
document.querySelectorAll('#trend-metric-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        const key = this.dataset.metric;
        if (trendMetrics.has(key)) {
            if (trendMetrics.size <= 1) return; // keep at least one active
            trendMetrics.delete(key);
            this.classList.remove('active');
        } else {
            trendMetrics.add(key);
            this.classList.add('active');
        }
        renderTrendChart();
    });
});
document.querySelectorAll('#trend-type-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        trendType=this.dataset.type;
        document.querySelectorAll('#trend-type-btns button').forEach(b=>b.classList.remove('active'));
        this.classList.add('active'); renderTrendChart();
    });
});

// View toggles
[['country-view-btns','country-chart-view','country-table-view'],
 ['offers-view-btns','offers-chart-view','offers-table-view'],
 ['affs-view-btns','affs-chart-view','affs-table-view']].forEach(([btns,chart,table])=>{
    document.querySelectorAll('#'+btns+' button').forEach(btn=>{
        btn.addEventListener('click',function(){
            const view=this.dataset.view;
            document.querySelectorAll('#'+btns+' button').forEach(b=>b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(chart).style.display=view==='chart'?'':'none';
            document.getElementById(table).style.display=view==='table'?'':'none';
        });
    });
});

function loadAll(){
    loadStats(); loadTrend(); loadHourly(); loadConvPie();
    loadCountries(); loadDevices(); loadBrowsers();
    loadOffers(); loadAffiliates(); loadConversions();
    var u=document.getElementById('dash-updated');
    if(u) u.textContent='Updated '+new Date().toLocaleTimeString();
}

function showLiveBadge(){}  // kept for compat

// ── Live refresh ──────────────────────────────────────────────────────
var _kpiTs = Date.now();

setInterval(function(){ loadStats(); _kpiTs = Date.now(); }, 1000);
setInterval(function(){ loadAll();   _kpiTs = Date.now(); }, 60000);

setInterval(function(){
    var t = document.getElementById('live-badge-mgr-txt');
    if(!t) return;
    var s = Math.round((Date.now() - _kpiTs) / 1000);
    t.textContent = s <= 1 ? 'Live · just now' : 'Live · ' + s + 's ago';
}, 1000);

loadFilterOptions();
loadAll();

// 3D Particles Constellation Network Animation inside the Header
(function() {
    var canvases = document.querySelectorAll('.an-header-canvas');
    canvases.forEach(function(canvas) {
        var ctx = canvas.getContext('2d');
        var w, h;
        function resize() {
            w = canvas.width = canvas.offsetWidth;
            h = canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);
        
        var nodes = [];
        var density = Math.min(25, Math.floor(w / 40));
        for (var i = 0; i < density; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                r: Math.random() * 2 + 1
            });
        }
        
        function animate() {
            if (!canvas.offsetParent) {
                requestAnimationFrame(animate);
                return;
            }
            ctx.clearRect(0, 0, w, h);
            
            // Draw nodes
            ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
            nodes.forEach(function(n) {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > w) n.vx *= -1;
                if (n.y < 0 || n.y > h) n.vy *= -1;
                
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
            });
            
            // Draw lines
            ctx.lineWidth = 0.8;
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    var dist = Math.hypot(dx, dy);
                    if (dist < 100) {
                        var alpha = (1 - dist / 100) * 0.15;
                        ctx.strokeStyle = 'rgba(255, 255, 255, ' + alpha + ')';
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }
        animate();
    });
})();

})();
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
