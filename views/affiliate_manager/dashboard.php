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
.dash-btn-apply:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 10px 26px rgba(124,58,237,.45);}
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

<?php $cardStyle = Config::get('config', 'app.dashboard_card_style') ?? 'aurora'; ?>

<?php if ($cardStyle === 'gradient_glow'): ?>
.kpi-card{background:var(--card-bg);border:none;border-radius:16px;padding:22px 22px 22px 26px;position:relative;overflow:hidden;transition:box-shadow .3s,transform .3s;border-left:4px solid transparent;}
.kpi-card::before{content:'';position:absolute;top:0;left:0;bottom:0;width:4px;border-radius:16px 0 0 16px;}
.kpi-card::after{content:'';position:absolute;top:-50%;right:-30%;width:120px;height:200%;border-radius:50%;opacity:.06;filter:blur(40px);transition:opacity .3s;pointer-events:none;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,.08);}
.kpi-card:hover::after{opacity:.12;}
.kpi-card.blue::before{background:linear-gradient(180deg,#3B82F6,#1D4ED8);}.kpi-card.blue::after{background:#3B82F6;}
.kpi-card.green::before{background:linear-gradient(180deg,#10B981,#059669);}.kpi-card.green::after{background:#10B981;}
.kpi-card.purple::before{background:linear-gradient(180deg,#7C3AED,#6D28D9);}.kpi-card.purple::after{background:#7C3AED;}
.kpi-card.orange::before{background:linear-gradient(180deg,#F59E0B,#D97706);}.kpi-card.orange::after{background:#F59E0B;}
.kpi-card.teal::before{background:linear-gradient(180deg,#06B6D4,#0891B2);}.kpi-card.teal::after{background:#06B6D4;}
.kpi-card.violet::before{background:linear-gradient(180deg,#8B5CF6,#6D28D9);}.kpi-card.violet::after{background:#8B5CF6;}
html[data-theme="dark"] .kpi-card{background:rgba(30,41,59,.85);border:1px solid rgba(148,163,184,.12);border-left:4px solid transparent;}

<?php elseif ($cardStyle === 'neon_glass'): ?>
@keyframes neon-pulse{0%,100%{opacity:.7}50%{opacity:1}}
.kpi-card{background:rgba(255,255,255,.65);-webkit-backdrop-filter:blur(20px) saturate(180%);backdrop-filter:blur(20px) saturate(180%);border:1px solid rgba(255,255,255,.5);border-radius:18px;padding:22px;position:relative;overflow:hidden;transition:transform .3s cubic-bezier(.34,1.56,.64,1),box-shadow .3s;box-shadow:0 4px 24px rgba(0,0,0,.04);}
.kpi-card::before{content:'';position:absolute;bottom:0;left:10%;right:10%;height:3px;border-radius:0 0 18px 18px;filter:blur(1px);animation:neon-pulse 3s ease-in-out infinite;}
.kpi-card::after{content:'';position:absolute;top:12px;right:14px;width:38px;height:38px;border-radius:12px;opacity:.10;}
.kpi-card:hover{transform:translateY(-6px) scale(1.01);box-shadow:0 24px 48px rgba(0,0,0,.10);}
.kpi-card.blue::before{background:linear-gradient(90deg,transparent,#3B82F6,transparent);}.kpi-card.blue::after{background:#3B82F6;}
.kpi-card.green::before{background:linear-gradient(90deg,transparent,#10B981,transparent);}.kpi-card.green::after{background:#10B981;}
.kpi-card.purple::before{background:linear-gradient(90deg,transparent,#7C3AED,transparent);}.kpi-card.purple::after{background:#7C3AED;}
.kpi-card.orange::before{background:linear-gradient(90deg,transparent,#F59E0B,transparent);}.kpi-card.orange::after{background:#F59E0B;}
.kpi-card.teal::before{background:linear-gradient(90deg,transparent,#06B6D4,transparent);}.kpi-card.teal::after{background:#06B6D4;}
.kpi-card.violet::before{background:linear-gradient(90deg,transparent,#8B5CF6,transparent);}.kpi-card.violet::after{background:#8B5CF6;}
html[data-theme="dark"] .kpi-card{background:rgba(15,23,42,.65);border-color:rgba(148,163,184,.15);box-shadow:0 4px 24px rgba(0,0,0,.2);}

<?php elseif ($cardStyle === 'aurora'): ?>
.kpi-card{border:none;border-radius:18px;padding:24px;position:relative;overflow:hidden;transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s;color:#fff;}
.kpi-card::before{content:'';position:absolute;inset:0;border-radius:18px;opacity:.08;background:linear-gradient(135deg,#fff 0%,transparent 50%);pointer-events:none;}
.kpi-card::after{content:'';position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;opacity:.15;filter:blur(20px);}
.kpi-card:hover{transform:translateY(-5px) scale(1.015);box-shadow:0 30px 60px rgba(0,0,0,.2);}
.kpi-card.blue{background:linear-gradient(135deg,#1E3A5F,#2563EB);box-shadow:0 8px 24px rgba(37,99,235,.2);}.kpi-card.blue::after{background:#60A5FA;}
.kpi-card.green{background:linear-gradient(135deg,#064E3B,#059669);box-shadow:0 8px 24px rgba(5,150,105,.2);}.kpi-card.green::after{background:#34D399;}
.kpi-card.purple{background:linear-gradient(135deg,#2E1065,#7C3AED);box-shadow:0 8px 24px rgba(124,58,237,.2);}.kpi-card.purple::after{background:#A78BFA;}
.kpi-card.orange{background:linear-gradient(135deg,#78350F,#D97706);box-shadow:0 8px 24px rgba(217,119,6,.2);}.kpi-card.orange::after{background:#FCD34D;}
.kpi-card.teal{background:linear-gradient(135deg,#134E4A,#0891B2);box-shadow:0 8px 24px rgba(8,145,178,.2);}.kpi-card.teal::after{background:#22D3EE;}
.kpi-card.violet{background:linear-gradient(135deg,#2E1065,#7C3AED);box-shadow:0 8px 24px rgba(124,58,237,.2);}.kpi-card.violet::after{background:#A78BFA;}
.kpi-card .kpi-label{color:rgba(255,255,255,.7) !important;}
.kpi-card .kpi-value{color:#fff !important;}
.kpi-card .kpi-sub{color:rgba(255,255,255,.55) !important;}
.kpi-card .kpi-sub a{color:rgba(255,255,255,.6) !important;}
.kpi-card .kpi-trend.up{background:rgba(255,255,255,.18) !important;color:#6EE7B7 !important;}
.kpi-card .kpi-trend.down{background:rgba(255,255,255,.18) !important;color:#FCA5A5 !important;}
.kpi-card .kpi-trend.flat{background:rgba(255,255,255,.12) !important;color:rgba(255,255,255,.7) !important;}

<?php else: ?>
.kpi-card{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:20px;position:relative;overflow:hidden;transition:box-shadow .2s,transform .2s,background-color .2s,border-color .2s;}
.kpi-card:hover{box-shadow:var(--shadow-md);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:12px 12px 0 0;}
.kpi-card.blue::before{background:#3B82F6;}
.kpi-card.green::before{background:#10B981;}
.kpi-card.purple::before{background:#7C3AED;}
.kpi-card.orange::before{background:#F59E0B;}
.kpi-card.teal::before{background:#06B6D4;}
.kpi-card.violet::before{background:#8B5CF6;}
<?php endif; ?>

.kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px;}
.kpi-value{font-size:26px;font-weight:800;color:var(--text);line-height:1;}
.kpi-sub{font-size:12px;color:var(--text-light);margin-top:4px;}
.kpi-trend{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;margin-top:6px;}
.kpi-trend.up{background:rgba(16,185,129,.16);color:#059669;}
.kpi-trend.down{background:rgba(239,68,68,.16);color:#DC2626;}
.kpi-trend.flat{background:var(--bg);color:var(--text-muted);}
.charts-row{display:grid;gap:16px;margin-bottom:20px;}
.charts-2{grid-template-columns:1fr 1fr;}
.charts-3{grid-template-columns:2fr 1fr 1fr;}
.chart-card{
    background:rgba(255,255,255,.55);
    -webkit-backdrop-filter:blur(22px) saturate(180%);
            backdrop-filter:blur(22px) saturate(180%);
    border:1px solid rgba(255,255,255,.65);
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 8px 32px rgba(31,38,135,.15), inset 0 1px 0 rgba(255,255,255,.65);
    transition:background-color .2s,border-color .2s,box-shadow .25s;
}
.chart-card .card-header{
    padding:16px 20px;
    border-bottom:1px solid rgba(255,255,255,.40);
    background:transparent;
    display:flex;align-items:center;justify-content:space-between;
}
.chart-card .card-title{font-size:14px;font-weight:700;color:var(--text);}
.chart-card .card-body{padding:16px;background:transparent;}
html[data-theme="dark"] .chart-card{
    background:rgba(20,24,48,.55);
    border-color:rgba(148,163,184,.22);
    box-shadow:0 8px 32px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
}
html[data-theme="dark"] .chart-card .card-header{border-bottom-color:rgba(148,163,184,.15);}
.chart-wrap{position:relative;height:240px;}
.chart-wrap.tall{height:300px;}
.toggle-btns{display:flex;gap:6px 4px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:6px;flex-wrap:wrap;align-items:center;}
.toggle-btns button{background:none;border:none;padding:6px 14px;font-size:12px;font-weight:600;color:#64748B;cursor:pointer;border-radius:6px;transition:all .2s ease;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;line-height:1;}
.toggle-btns button:hover{background:#F1F5F9;color:#0F172A;}
.toggle-btns button.active{background:#7C3AED;color:#fff !important;box-shadow:0 2px 4px rgba(124,58,237,.25);}
.analytics-table{width:100%;border-collapse:collapse;}
.analytics-table th{padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:2px solid var(--border);background:var(--bg);white-space:nowrap;}
.analytics-table td{padding:10px 14px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);}
.analytics-table tr:hover td{background:var(--bg);}
.analytics-table .num{text-align:right;font-variant-numeric:tabular-nums;}
.loading-overlay{position:absolute;inset:0;background:rgba(15,23,42,.04);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:8px;}
html[data-theme="dark"] .loading-overlay{background:rgba(15,23,42,.55);}
.spinner{width:24px;height:24px;border:3px solid var(--border);border-top-color:#7C3AED;border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes live-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
/* "Live · just now" floating pill hidden — overlapped the live chat widget launcher. */
.live-badge-mgr{display:none !important;align-items:center;gap:7px;position:fixed;bottom:20px;right:20px;background:linear-gradient(135deg,#1E1B4B,#7C3AED);color:#fff;padding:8px 18px;border-radius:24px;font-size:12px;font-weight:700;box-shadow:0 4px 20px rgba(124,58,237,.45);z-index:9999;}
.live-badge-mgr .lp{width:8px;height:8px;border-radius:50%;background:#10B981;animation:live-pulse 1.4s infinite;flex-shrink:0;}
/* Responsive ladder — KPI grid + chart header buttons across every device. */
@media(max-width:1280px){
    .kpi-grid{grid-template-columns:repeat(3,1fr);}
    .charts-3{grid-template-columns:1fr 1fr;}
}
@media(max-width:1100px){
    .kpi-grid{grid-template-columns:repeat(3,1fr);}
    .chart-card .card-header{flex-wrap:wrap;gap:10px;}
    .chart-card .card-header > div{flex-wrap:wrap;}
}
@media(max-width:900px){
    .kpi-grid{grid-template-columns:repeat(2,1fr);gap:14px;}
    .kpi-card{padding:16px;}
    .charts-2,.charts-3{grid-template-columns:1fr;}
    .chart-card .card-body{padding:12px;}
    .chart-wrap{height:220px;}
}
@media(max-width:768px){
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
        <div class="kpi-label">Managed Affiliates</div>
        <div class="kpi-value"><?= $totalAffiliates ?></div>
        <div class="kpi-sub">Under your management</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">Total Clicks</div>
        <div class="kpi-value" id="k-clicks">—</div>
        <div class="kpi-sub">Unique: <span id="k-unique">—</span></div>
        <div id="k-clicks-trend" class="kpi-trend flat">—</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Conversions</div>
        <div class="kpi-value" id="k-conv">—</div>
        <div class="kpi-sub">CR: <span id="k-cr">—</span>%</div>
        <div id="k-conv-trend" class="kpi-trend flat">—</div>
    </div>
    <div class="kpi-card teal">
        <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value" id="k-cr-card">—</div>
        <div class="kpi-sub">Overall CR%</div>
    </div>
    <!-- Fraud Conversion % across all managed affiliates. Filters from the
         dashboard (date / offer / country / device) flow through automatically. -->
    <div class="kpi-card red" title="Fraud conversions are conversions with fraud score between 60–100.">
        <div class="kpi-label">Fraud Conversion %</div>
        <div class="kpi-value"><span id="k-fraud-conv-pct">—</span>%</div>
        <div class="kpi-sub"><span id="k-fraud-conv">—</span> Fraud Conversions</div>
        <div id="k-fraud-conv-pct-trend" class="kpi-trend flat">—</div>
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
    <div class="kpi-card" style="--top-rail:<?= $_fsColor ?>;background:<?= $_fsBg ?>"
         title="Real-time IPQS Fraud Score — average IPQualityScore fraud_score across all managed affiliate conversions in the last 30 days.">
        <style>.kpi-card[style*="--top-rail"]::before{background:var(--top-rail) !important}</style>
        <div class="kpi-label" style="display:flex;align-items:center;gap:6px">
            IPQS Fraud Score
        </div>
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
</div>

<!-- ── Row 1: Performance Trend (full width) ─────────────────────────── -->
<div class="chart-card mb-3">
    <div class="card-header">
        <span class="card-title">Performance Trend</span>
        <div style="display:flex;gap:8px;align-items:center">
            <div class="toggle-btns" id="trend-metric-btns" title="Click to toggle each metric on/off">
                <button class="active" data-metric="clicks">Clicks</button>
                <button class="active" data-metric="conv">Conversions</button>
                <button class="active" data-metric="fraud" style="color:#DC2626" title="Fraud conversions are conversions with fraud score between 60–100.">Fraud</button>
            </div>
            <div class="toggle-btns" id="trend-type-btns">
                <button class="active" data-type="line">Line</button>
                <button data-type="bar">Bar</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-wrap tall" style="position:relative">
            <canvas id="trendChart"></canvas>
            <div class="loading-overlay" id="trend-loading"><div class="spinner"></div></div>
        </div>
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
    const metricMap={
        clicks: {key:'clicks', label:'Clicks',                  data:d.clicks_data,  color:'#7C3AED', axis:'y'},
        conv:   {key:'conv',   label:'Conversions',             data:d.conv_data,    color:'#10B981', axis:'y'},
        payout: {key:'payout', label:'Payout ($)',              data:d.payout_data,  color:'#F59E0B', axis:'y1', isCur:true},
        fraud:  {key:'fraud',  label:'Fraud Conversions (≥60)', data:d.fraud_data,   color:'#DC2626', axis:'y'},
    };
    const order  = ['clicks','conv','payout','fraud'];
    const active = order.filter(k => trendMetrics.has(k));
    const isLine = trendType==='line';
    const bgAlpha = isLine ? '20' : 'CC';
    const showFill = isLine && active.length === 1;
    const datasets = active.map(k => {
        const m = metricMap[k];

        let tension = 0.45;
        let stepped = false;
        let fillOpacity1 = '66';
        let fillOpacity2 = '15';
        let fillOpacity3 = '00';
        let bWidth = 3.5;
        let pointRad = d.labels.length <= 45 ? 0 : 0;
        
        if (typeof trendChartStyle !== 'undefined') {
            if (trendChartStyle === 'straight') {
                tension = 0;
                fillOpacity1 = '33'; fillOpacity2 = '05';
            } else if (trendChartStyle === 'stepped') {
                tension = 0;
                stepped = true;
                fillOpacity1 = '00'; fillOpacity2 = '00'; fillOpacity3 = '00';
                bWidth = 2.5;
            } else if (trendChartStyle === 'high_tech') {
                bWidth = 4.5;
                fillOpacity1 = 'AA'; fillOpacity2 = '44'; fillOpacity3 = '05';
                pointRad = d.labels.length <= 45 ? 0 : 0;
            } else if (trendChartStyle === 'gradient_fill') {
                bWidth = 3;
                tension = 0.5;
                fillOpacity1 = 'CC'; fillOpacity2 = '55'; fillOpacity3 = '08';
            } else if (trendChartStyle === 'neon_glow') {
                bWidth = 3;
                tension = 0.4;
                fillOpacity1 = '88'; fillOpacity2 = '22'; fillOpacity3 = '00';
            } else if (trendChartStyle === 'minimal_dots') {
                bWidth = 2;
                tension = 0.3;
                fillOpacity1 = '00'; fillOpacity2 = '00'; fillOpacity3 = '00';
                pointRad = 4;
            } else if (trendChartStyle === 'area_stacked') {
                bWidth = 2.5;
                tension = 0.4;
                fillOpacity1 = '99'; fillOpacity2 = '44'; fillOpacity3 = '11';
            } else if (trendChartStyle === 'thin_sharp') {
                bWidth = 1.5;
                tension = 0;
                fillOpacity1 = '15'; fillOpacity2 = '05'; fillOpacity3 = '00';
            } else if (trendChartStyle === 'bold_rounded') {
                bWidth = 5;
                tension = 0.5;
                fillOpacity1 = '55'; fillOpacity2 = '22'; fillOpacity3 = '00';
            }
        }

        return {
            label: m.label,
            data: m.data || [],
            borderColor: m.color,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea || !isLine) return m.color + (isLine ? '1A' : 'CC');
                if (stepped && isLine) return 'transparent';
                let gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, m.color + fillOpacity1);
                gradient.addColorStop(0.6, m.color + fillOpacity2);
                gradient.addColorStop(1, m.color + fillOpacity3);
                return gradient;
            },
            fill: isLine,
            tension: isLine ? tension : 0,
            stepped: isLine ? stepped : false,
            borderWidth: isLine ? bWidth : 0,
            borderRadius: isLine ? 0 : 4,
            pointRadius: isLine ? pointRad : 0,
            pointBackgroundColor: m.color,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointHoverRadius: 7,
            pointHoverBackgroundColor: m.color,
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3,
            yAxisID: m.axis,
            isCur: !!m.isCur
        };
    });
    const hasCur   = active.some(k => metricMap[k].isCur);
    const hasCount = active.some(k => !metricMap[k].isCur);
    makeChart('trendChart',{
        type: trendType,
        data: { labels: d.labels, datasets },
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{
                legend:{ display:true, position:'bottom', labels:{ boxWidth:12, usePointStyle:true, padding:20, font:{size:13, family:'"Inter", sans-serif', weight:'600'}, color:'#475569' } },
                tooltip:{ 
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleFont: { size: 14, family: '"Inter", sans-serif', weight:'700' },
                    bodyFont: { size: 13, family: '"Inter", sans-serif', weight:'500' },
                    padding: 14,
                    cornerRadius: 10,
                    displayColors: true,
                    boxPadding: 8,
                    callbacks:{ label: ctx => {
                        const dsCur = ctx.dataset.isCur;
                        return ' ' + ctx.dataset.label + ': ' + (dsCur ? '$'+fmt(ctx.parsed.y,2) : fmt(ctx.parsed.y));
                    }}
                }
            },
            scales:{
                x: {
                    grid:{display:false},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B'}
                },
                y: {
                    display:hasCount, beginAtZero:true, position:'left',
                    grid:{display:false},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B', callback:v=>fmt(v)},
                    title:{display:hasCount, text:'Count', font:{size:11, family:'"Inter", sans-serif', weight:'500'}, color:'#94A3B8'}
                },
                y1:{
                    display:hasCur, beginAtZero:true, position:'right',
                    grid:{display:false},
                    ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B', callback:v=>'$'+fmt(v)},
                    title:{display:hasCur, text:'Amount ($)', font:{size:11, family:'"Inter", sans-serif', weight:'500'}, color:'#94A3B8'}
                }
            }
        }
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
                options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8}}}}});
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
    ]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{font:{size:11}}}},
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
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8}}}}});
        }).catch(()=>{}).finally(()=>hideLoad('device-loading'));
}

// Browsers
function loadBrowsers(){
    showLoad('browser-loading');
    fetch(API+'?action=browsers&'+qs(getFilters()))
        .then(r=>r.json()).then(d=>{
            makeChart('browserChart',{type:'doughnut',data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:COLORS.slice(2),borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8}}}}});
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
