<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<?php
$_serverTz = Config::get('config','app.timezone') ?? 'UTC';
$_tzOptions = [
    'UTC/GMT'            => ['UTC'=>'UTC (UTC+0)','GMT'=>'GMT (UTC+0)'],
    'Americas'           => [
        'America/New_York'    =>'New York (ET, UTC-5/4)',
        'America/Chicago'     =>'Chicago (CT, UTC-6/5)',
        'America/Denver'      =>'Denver (MT, UTC-7/6)',
        'America/Los_Angeles' =>'Los Angeles (PT, UTC-8/7)',
        'America/Anchorage'   =>'Anchorage (AKT)',
        'Pacific/Honolulu'    =>'Honolulu (HST, UTC-10)',
        'America/Toronto'     =>'Toronto (ET)',
        'America/Vancouver'   =>'Vancouver (PT)',
        'America/Sao_Paulo'   =>'São Paulo (BRT, UTC-3)',
        'America/Buenos_Aires'=>'Buenos Aires (ART, UTC-3)',
        'America/Mexico_City' =>'Mexico City (CST, UTC-6)',
        'America/Bogota'      =>'Bogotá (COT, UTC-5)',
        'America/Lima'        =>'Lima (PET, UTC-5)',
        'America/Santiago'    =>'Santiago (CLT)',
        'America/Caracas'     =>'Caracas (VET, UTC-4)',
    ],
    'Europe'             => [
        'Europe/London'    =>'London (GMT/BST)',
        'Europe/Dublin'    =>'Dublin (GMT/IST)',
        'Europe/Lisbon'    =>'Lisbon (WET)',
        'Europe/Paris'     =>'Paris (CET, UTC+1/2)',
        'Europe/Berlin'    =>'Berlin (CET)',
        'Europe/Amsterdam' =>'Amsterdam (CET)',
        'Europe/Madrid'    =>'Madrid (CET)',
        'Europe/Rome'      =>'Rome (CET)',
        'Europe/Stockholm' =>'Stockholm (CET)',
        'Europe/Warsaw'    =>'Warsaw (CET)',
        'Europe/Vienna'    =>'Vienna (CET)',
        'Europe/Brussels'  =>'Brussels (CET)',
        'Europe/Zurich'    =>'Zurich (CET)',
        'Europe/Athens'    =>'Athens (EET, UTC+2/3)',
        'Europe/Helsinki'  =>'Helsinki (EET)',
        'Europe/Bucharest' =>'Bucharest (EET)',
        'Europe/Kiev'      =>'Kyiv (EET)',
        'Europe/Istanbul'  =>'Istanbul (TRT, UTC+3)',
        'Europe/Moscow'    =>'Moscow (MSK, UTC+3)',
        'Europe/Minsk'     =>'Minsk (FET, UTC+3)',
    ],
    'Middle East & Africa'=> [
        'Asia/Dubai'          =>'Dubai (GST, UTC+4)',
        'Asia/Riyadh'         =>'Riyadh (AST, UTC+3)',
        'Asia/Jerusalem'      =>'Jerusalem (IST)',
        'Asia/Beirut'         =>'Beirut (EET)',
        'Africa/Cairo'        =>'Cairo (EET, UTC+2)',
        'Africa/Johannesburg' =>'Johannesburg (SAST, UTC+2)',
        'Africa/Lagos'        =>'Lagos (WAT, UTC+1)',
        'Africa/Nairobi'      =>'Nairobi (EAT, UTC+3)',
        'Africa/Casablanca'   =>'Casablanca',
    ],
    'Asia'               => [
        'Asia/Tehran'       =>'Tehran (IRST, UTC+3:30)',
        'Asia/Kabul'        =>'Kabul (AFT, UTC+4:30)',
        'Asia/Karachi'      =>'Karachi (PKT, UTC+5)',
        'Asia/Kolkata'      =>'India (IST, UTC+5:30)',
        'Asia/Dhaka'        =>'Dhaka (BST, UTC+6)',
        'Asia/Yangon'       =>'Yangon (MMT, UTC+6:30)',
        'Asia/Bangkok'      =>'Bangkok (ICT, UTC+7)',
        'Asia/Ho_Chi_Minh'  =>'Ho Chi Minh (ICT, UTC+7)',
        'Asia/Jakarta'      =>'Jakarta (WIB, UTC+7)',
        'Asia/Singapore'    =>'Singapore (SGT, UTC+8)',
        'Asia/Kuala_Lumpur' =>'Kuala Lumpur (MYT, UTC+8)',
        'Asia/Manila'       =>'Manila (PHT, UTC+8)',
        'Asia/Hong_Kong'    =>'Hong Kong (HKT, UTC+8)',
        'Asia/Shanghai'     =>'Shanghai / Beijing (CST, UTC+8)',
        'Asia/Taipei'       =>'Taipei (CST, UTC+8)',
        'Asia/Seoul'        =>'Seoul (KST, UTC+9)',
        'Asia/Tokyo'        =>'Tokyo (JST, UTC+9)',
    ],
    'Pacific'            => [
        'Australia/Perth'     =>'Perth (AWST, UTC+8)',
        'Australia/Darwin'    =>'Darwin (ACST, UTC+9:30)',
        'Australia/Adelaide'  =>'Adelaide (ACST)',
        'Australia/Brisbane'  =>'Brisbane (AEST, UTC+10)',
        'Australia/Sydney'    =>'Sydney (AEST)',
        'Australia/Melbourne' =>'Melbourne (AEST)',
        'Pacific/Auckland'    =>'Auckland (NZST, UTC+12)',
        'Pacific/Fiji'        =>'Fiji (FJT, UTC+12)',
        'Pacific/Guam'        =>'Guam (ChST, UTC+10)',
    ],
];
?>

<style>
<?php $bannerStyle = Config::get('config', 'app.dashboard_banner_style') ?? (Config::get('config', 'app.transparent_dashboard') === '1' ? 'transparent' : 'glass_purple'); ?>
<?php $cardStyle = Config::get('config', 'app.dashboard_card_style') ?? 'aurora'; ?>
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
    background:linear-gradient(135deg,#1E1B4B 0%,#312E81 50%,#4C1D95 100%);
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
    display:flex;
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
.toggle-btns{display:flex;gap:6px 4px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:6px;flex-wrap:wrap;align-items:center;}
.toggle-btns button{background:none;border:none;padding:6px 14px;font-size:12px;font-weight:600;color:#64748B;cursor:pointer;border-radius:6px;transition:all .2s ease;white-space:nowrap;display:inline-flex;align-items:center;justify-content:center;line-height:1;}
.toggle-btns button:hover{background:#F1F5F9;color:#0F172A;}
.toggle-btns button.active{background:#4F46E5;color:#fff !important;box-shadow:0 2px 4px rgba(79,70,229,.25);}

/* Inputs / selects / date pickers — fully transparent glass.
   appearance:none kills the opaque browser-native chrome so the
   purple gradient reads straight through; the chevron is drawn
   via a chevron-arrow background-image on selects only. */
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
    color-scheme:dark;  /* dark calendar/spinner icons for date inputs */
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
.dash-f-input option{background:#1E1B4B;color:#fff;}
.dash-btn-apply{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:opacity .15s,transform .15s,box-shadow .15s;white-space:nowrap;box-shadow:0 6px 18px rgba(124,58,237,.35);}
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
/* ── Responsive: admin dashboard ────────────────────────────── */
@media(max-width:1200px){
    .dash-header{flex-direction:column;align-items:flex-start;}
    .dash-filters{width:100%;}
}
@media(max-width:900px){
    .dash-filters{gap:6px;}
    .dash-period-tabs{width:100%;order:-1;}
    .dash-f-input{min-width:0 !important;flex:1 1 calc(50% - 5px);}
    .dash-period-tab{padding:5px 9px;font-size:11px;}
    .dash-btn-apply,.dash-btn-reset{flex:1 1 calc(50% - 4px);justify-content:center;}
}
@media(max-width:640px){
    /* Hide the — date separator so dates sit side-by-side */
    .dash-filters > span{display:none !important;}
    /* Dates: two per row */
    #f-from,#f-to{flex:1 1 calc(50% - 4px) !important;min-width:0 !important;}
    /* All selects: two per row */
    .dash-f-input{flex:1 1 calc(50% - 4px);min-width:0 !important;}
    /* Timezone: full row — it's wider */
    #f-tz{flex:1 1 100% !important;min-width:0 !important;}
    /* Buttons: side-by-side */
    .dash-btn-apply,.dash-btn-reset{flex:1 1 calc(50% - 4px);}
    /* KPI cards: 2 cols */
    .kpi-grid{grid-template-columns:repeat(2,1fr) !important;gap:10px;}
    .kpi-value{font-size:20px;}
    /* Charts: single col */
    .charts-2,.charts-3{grid-template-columns:1fr !important;}
}
@media(max-width:480px){
    /* Period tabs: horizontal scroll instead of wrap */
    .dash-period-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap;}
    .dash-period-tab{flex-shrink:0;}
    /* All inputs full width on very small phones */
    .dash-f-input{flex:1 1 100% !important;}
    /* Dates stay 50/50 */
    #f-from,#f-to{flex:1 1 calc(50% - 4px) !important;}
    .dash-hdr-title{font-size:20px;}
}
@media(max-width:360px){
    /* Dates also go full width on tiny screens */
    #f-from,#f-to{flex:1 1 100% !important;}
    .kpi-value{font-size:18px;}
}
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}

/* ═══════════════════════════════════════════════════════════════════════
   KPI CARD STYLES — 3D Glassmorphism Premium
   ═══════════════════════════════════════════════════════════════════════ */
.kpi-card, .an-kpi-card {
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
html[data-theme="dark"] .kpi-card, html[data-theme="dark"] .an-kpi-card {
    background: rgba(20, 24, 48, 0.5);
    border-color: rgba(255,255,255,0.08);
    box-shadow: 0 12px 24px rgba(0,0,0,0.2);
}
.kpi-card:hover, .an-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
html[data-theme="dark"] .kpi-card:hover, html[data-theme="dark"] .an-kpi-card:hover {
    box-shadow: 0 16px 32px rgba(0,0,0,0.4);
}
.kpi-icon, .an-kpi-icon {
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
    font-size: 20px;
}
.kpi-content, .an-kpi-content {
    flex-grow: 1;
}
.kpi-sparkline, .an-kpi-sparkline {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50%;
    height: 60%;
    opacity: 0.25;
    background: linear-gradient(180deg, transparent 0%, var(--kpi-color) 100%);
    clip-path: polygon(0 100%, 10% 80%, 30% 90%, 50% 60%, 70% 80%, 100% 40%, 100% 100%);
}

.kpi-card.blue, .an-kpi-card.blue { --kpi-color: #3B82F6; }
.kpi-card.green, .an-kpi-card.green { --kpi-color: #10B981; }
.kpi-card.purple, .an-kpi-card.purple { --kpi-color: #8B5CF6; }
.kpi-card.emerald, .an-kpi-card.emerald { --kpi-color: #059669; }
.kpi-card.teal, .an-kpi-card.teal, .an-kpi-card.cyan { --kpi-color: #06B6D4; }
.kpi-card.orange, .an-kpi-card.orange { --kpi-color: #F59E0B; }
.kpi-card.indigo, .an-kpi-card.indigo { --kpi-color: #4F46E5; }
.kpi-card.red, .an-kpi-card.red { --kpi-color: #EF4444; }

/* Aurora text overrides removal - just ensuring default colors */
.kpi-label, .an-kpi-label { color: var(--text-muted); }
.kpi-value, .an-kpi-value { color: var(--text); }
.kpi-sub, .an-kpi-sub { color: var(--text-light); }


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
/* Dark-mode adaptive glass */
html[data-theme="dark"] .chart-card{
    background:rgba(20,24,48,.55);
    border-color:rgba(148,163,184,.22);
    box-shadow:0 8px 32px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
}
html[data-theme="dark"] .chart-card .card-header{border-bottom-color:rgba(148,163,184,.15);}
.chart-wrap{position:relative;height:240px;}
.chart-wrap.tall{height:300px;}
.chart-wrap.short{height:180px;}
.analytics-table{width:100%;border-collapse:collapse;}
.analytics-table th{padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:2px solid var(--border);background:var(--bg);white-space:nowrap;}
.analytics-table td{padding:10px 14px;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);}
.analytics-table tr:hover td{background:var(--bg);}
.analytics-table .num{text-align:right;font-variant-numeric:tabular-nums;}
.analytics-table .green{color:#059669;font-weight:700;}
.analytics-table .red{color:#DC2626;font-weight:700;}
.cr-bar{display:flex;align-items:center;gap:6px;}
.cr-bar-inner{flex:1;background:var(--border);border-radius:3px;height:5px;min-width:40px;}
.cr-bar-fill{height:100%;border-radius:3px;background:var(--primary);}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px;}
.status-dot.approved{background:#10B981;}
.status-dot.pending{background:#F59E0B;}
.status-dot.rejected{background:#EF4444;}
.loading-overlay{position:absolute;inset:0;background:rgba(15,23,42,.04);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:8px;}
html[data-theme="dark"] .loading-overlay{background:rgba(15,23,42,.55);}
.spinner{width:24px;height:24px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes live-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
/* "Live · just now" floating pill hidden — overlapped the live chat widget launcher. */
.live-badge{display:none !important;align-items:center;gap:7px;position:fixed;bottom:20px;right:20px;background:linear-gradient(135deg,#1E1B4B,#4F46E5);color:#fff;padding:8px 18px;border-radius:24px;font-size:12px;font-weight:700;box-shadow:0 4px 20px rgba(79,70,229,.45);z-index:9999;}
.live-badge .lp{width:8px;height:8px;border-radius:50%;background:#10B981;animation:live-pulse 1.4s infinite;flex-shrink:0;}
/* ── Responsive overhaul: dashboard works on every device ────
   Breakpoints intentionally laddered so KPI cards never look too
   wide or too cramped. Performance Trend buttons wrap with proper
   spacing on touch sizes. */

/* Base kpi-grid declaration lives at the top of the stylesheet
   (4 cols, gap 16). The cascade below ladders it down per device. */

/* 1100-1280px (small laptop): 3 cols so cards don't compress */
@media(max-width:1280px){
    .kpi-grid{grid-template-columns:repeat(3,1fr);}
    .charts-3{grid-template-columns:1fr 1fr;}
}

/* 900-1100px (tablet landscape) */
@media(max-width:1100px){
    .kpi-grid{grid-template-columns:repeat(3,1fr);}
    .charts-3{grid-template-columns:1fr 1fr;}
    /* Performance Trend buttons wrap as the chart header narrows */
    .chart-card .card-header{flex-wrap:wrap;gap:10px;}
    .chart-card .card-header > div{flex-wrap:wrap;}
}

/* 768-900px (tablet portrait) */
@media(max-width:900px){
    .kpi-grid{grid-template-columns:repeat(2,1fr);gap:14px;}
    .kpi-card{padding:16px;}
    .charts-2,.charts-3{grid-template-columns:1fr;}
    .chart-card .card-body{padding:12px;}
    .chart-wrap{height:220px;}
    .chart-wrap.tall{height:340px;}
}

/* 640-768px (large phone) */
@media(max-width:768px){
    .dash-header{padding:18px 18px;border-radius:12px;}
    .dash-hdr-title{font-size:22px;}
    .dash-hdr-label{font-size:10px;}
    .kpi-grid{grid-template-columns:repeat(2,1fr) !important;gap:10px;}
    .kpi-card{padding:14px;}
    .kpi-value{font-size:22px;}
    .kpi-label{font-size:11px;}
    .kpi-sub{font-size:11px;}
    .toggle-btns{flex-wrap:wrap;gap:6px;padding:6px;}
    .toggle-btns button{padding:5px 10px;font-size:11px;min-height:32px;}
}

/* 480-640px (phone) */
@media(max-width:640px){
    .dash-header{padding:14px 12px;}
    .dash-hdr-title{font-size:18px;}
    .dash-hdr-label{font-size:9px;}
    .kpi-grid{grid-template-columns:repeat(2,1fr) !important;gap:10px;}
    .kpi-value{font-size:18px;}
    .kpi-label{font-size:10px;}
    .charts-2,.charts-3{grid-template-columns:1fr !important;}
    .chart-card .card-header{padding:10px 12px;}
    .chart-card .card-title{font-size:12px;}
    .chart-wrap{height:200px;}
    .chart-wrap.tall{height:320px;}
    .dash-f-input{font-size: 11px; padding: 6px 10px;}
    .dash-btn-apply,.dash-btn-reset{font-size: 11px; padding: 6px 10px;}
}

/* 360-480px (small phone) */
@media(max-width:480px){
    .dash-header{padding:12px 10px;}
    .dash-hdr-title{font-size:16px;}
    .kpi-card{padding:10px;}
    .kpi-value{font-size:16px;}
    .toggle-btns button{padding:5px 8px;font-size:10px;}
    .chart-card .card-header > div{width:100%;}
    .chart-card .card-header .toggle-btns{justify-content:flex-start;}
}

/* <360px (very small phone) */
@media(max-width:360px){
    .kpi-grid{grid-template-columns:1fr !important;}
    .kpi-card{padding:10px;}
    .kpi-value{font-size:15px;}
    .dash-hdr-title{font-size:14px;}
    .chart-wrap{height:180px;}
}
</style>

<div id="live-badge" class="live-badge"><span class="lp"></span><span id="live-badge-txt">Live</span></div>

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
        <select class="dash-f-input" id="f-tz" style="min-width:160px" title="Timezone — affects date ranges and hourly chart">
            <?php foreach ($_tzOptions as $_tzGroup => $_tzList): ?>
            <optgroup label="<?= htmlspecialchars($_tzGroup) ?>">
                <?php foreach ($_tzList as $_tzId => $_tzLabel): ?>
                <option value="<?= htmlspecialchars($_tzId) ?>"><?= htmlspecialchars($_tzLabel) ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select>
        <button class="dash-btn-apply" onclick="applyFilters()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
        </button>
        <button class="dash-btn-reset" onclick="resetFilters()">Reset</button>
    </div>
</div>

<!-- ── KPI Cards ──────────────────────────────────────────────────────── -->
<div class="kpi-grid" id="kpi-grid">
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
    <?php if (Auth::role() === "admin"): ?>
    <div class="kpi-card purple">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Revenue</div>
        <div class="kpi-value">$<span id="k-revenue">—</span></div>
        <div class="kpi-sub">Payout: $<span id="k-payout">—</span></div>
        <div id="k-revenue-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <?php endif; ?>
    <?php if (Auth::role() === "admin"): ?>
    <div class="kpi-card emerald">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Net Profit</div>
        <div class="kpi-value" id="k-profit">—</div>
        <div class="kpi-sub">Revenue minus payout</div>
        <div id="k-payout-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <?php endif; ?>
    <div class="kpi-card teal">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Active Affiliates</div>
        <div class="kpi-value" id="k-affiliates"><?= number_format($totalAffiliates) ?></div>
        <div class="kpi-sub"><span id="k-pending"><?= $pendingAffiliates ?></span> pending approval</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card orange">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Active Offers</div>
        <div class="kpi-value" id="k-offers"><?= number_format($totalOffers) ?></div>
        <div class="kpi-sub"><?= $totalAdvertisers ?> advertisers</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card indigo">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value"><span id="k-cr2">—</span>%</div>
        <div class="kpi-sub">Clicks to conversions</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <div class="kpi-card red">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Fraud Clicks</div>
        <div class="kpi-value" id="k-fraud">—</div>
        <div class="kpi-sub"><a href="/admin/fraud" style="color:#94A3B8">View fraud log</a></div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
    <!-- Fraud Conversion % — fraud_score >= 60 ÷ total conversions × 100. Filters
         (date / offer / affiliate / country / device) flow through automatically
         because the API uses the same WHERE builders as the rest of the cards. -->
    <div class="kpi-card red" title="Fraud conversions are conversions with fraud score between 60–100.">
        <div class="kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-label">Fraud Conversion %</div>
        <div class="kpi-value"><span id="k-fraud-conv-pct">—</span>%</div>
        <div class="kpi-sub"><span id="k-fraud-conv">—</span> Fraud Conversions &middot; <a href="/admin/fraud-score-report" style="color:#94A3B8">View fraud report</a></div>
        <div id="k-fraud-conv-pct-trend" class="kpi-trend flat">—</div>
        </div>
        <div class="kpi-sparkline"></div>
</div>
</div>

<?php if ($pendingAffiliates > 0): ?>
<div class="alert alert-warning mb-3">
    &#9888; <strong><?= $pendingAffiliates ?> affiliate(s)</strong> pending approval.
    <a href="/admin/affiliates?status=pending">Review now &rarr;</a>
</div>
<?php endif; ?>

<!-- ── Row 1: Performance Trend (PREMIUM SAAS UI) ─────────────────────────── -->
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
.saas-tooltip-extra {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dotted rgba(148,163,184,0.3);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.saas-tooltip-ex-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.saas-tooltip-ex-label { font-size: 11px; color: var(--text-light, #94a3b8); font-weight: 600; text-transform: uppercase; }
.saas-tooltip-ex-val { font-size: 13px; font-weight: 700; color: var(--text, #111827); }

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
            <div class="saas-time-filters">
                <!-- Tie to existing global date ranges if needed, or trigger admin reload -->
                <button class="saas-chip active" onclick="document.getElementById('dt-btn-today').click(); updateSaaSChips(this)">Today</button>
                <button class="saas-chip" onclick="document.getElementById('dt-btn-yesterday').click(); updateSaaSChips(this)">Yesterday</button>
                <button class="saas-chip" onclick="document.getElementById('dt-btn-7d').click(); updateSaaSChips(this)">7 Days</button>
                <button class="saas-chip" onclick="document.getElementById('dt-btn-30d').click(); updateSaaSChips(this)">30 Days</button>
                <button class="saas-chip" onclick="document.getElementById('dt-btn-tm').click(); updateSaaSChips(this)">This Month</button>
            </div>
        </div>

        <div class="saas-kpi-row">
            <div class="saas-kpi-card" style="--kpi-color: #2563EB;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Clicks</div>
                    <div class="saas-kpi-value" id="saas-kpi-clicks">0</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #8B5CF6;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Conversions</div>
                    <div class="saas-kpi-value" id="saas-kpi-conv">0</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #10B981;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Revenue</div>
                    <div class="saas-kpi-value" id="saas-kpi-rev">$0.00</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #F59E0B;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Profit</div>
                    <div class="saas-kpi-value" id="saas-kpi-profit">$0.00</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #DC2626;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Fraud Rate</div>
                    <div class="saas-kpi-value" id="saas-kpi-fraud">0%</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
        </div>

        <div class="saas-legend-container" id="saas-trend-legend">
            <label class="saas-legend-item active" style="--leg-color: #3B82F6;" onclick="toggleSaaSLegend(this, 'tog-clicks')">
                <input type="checkbox" checked id="tog-clicks" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Clicks
            </label>
            <label class="saas-legend-item active" style="--leg-color: #8B5CF6;" onclick="toggleSaaSLegend(this, 'tog-conv')">
                <input type="checkbox" checked id="tog-conv" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Conversions
            </label>
            <?php if (Auth::role() === "admin"): ?>
            <label class="saas-legend-item active" style="--leg-color: #10B981;" onclick="toggleSaaSLegend(this, 'tog-rev')">
                <input type="checkbox" checked id="tog-rev" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Revenue
            </label>
            <?php endif; ?>
            <label class="saas-legend-item active" style="--leg-color: #06B6D4;" onclick="toggleSaaSLegend(this, 'tog-payout')">
                <input type="checkbox" checked id="tog-payout" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Payout
            </label>
            <?php if (Auth::role() === "admin"): ?>
            <label class="saas-legend-item active" style="--leg-color: #F59E0B;" onclick="toggleSaaSLegend(this, 'tog-profit')">
                <input type="checkbox" checked id="tog-profit" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Profit
            </label>
            <?php endif; ?>
            <label class="saas-legend-item active" style="--leg-color: #DC2626;" onclick="toggleSaaSLegend(this, 'tog-fraud')">
                <input type="checkbox" checked id="tog-fraud" onchange="renderTrendChart()" style="display:none">
                <span class="saas-leg-dot"></span> Fraud
            </label>
        </div>
    </div>

    <div class="saas-trend-body">
        <canvas id="trendChart"></canvas>
        <div id="saas-custom-tooltip" class="saas-custom-tooltip"></div>
        <div class="loading-overlay" id="trend-loading"><div class="spinner"></div></div>
    </div>
</div>

<script>
function updateSaaSChips(activeBtn) {
    document.querySelectorAll('.saas-chip').forEach(b => b.classList.remove('active'));
    activeBtn.classList.add('active');
}
function toggleSaaSLegend(labelEl, inputId) {
    setTimeout(() => {
        const chk = document.getElementById(inputId);
        if (chk.checked) {
            labelEl.classList.add('active');
        } else {
            labelEl.classList.remove('active');
        }
    }, 10);
}
</script>

<!-- ── Row 2: Hourly Today + Conversion Status ───────────────────────── -->
<div class="charts-row charts-2 mb-3">
    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Hourly Traffic — Today</span>
            <span style="font-size:11px;color:#94A3B8">Clicks by hour</span>
        </div>
        <div class="card-body">
            <div class="chart-wrap" style="position:relative">
                <canvas id="hourlyChart"></canvas>
                <div class="loading-overlay" id="hourly-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header">
            <span class="card-title">Conversion Status</span>
        </div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center">
            <div style="position:relative;height:240px;width:100%;max-width:280px;margin:0 auto;position:relative">
                <canvas id="convPieChart"></canvas>
                <div class="loading-overlay" id="pie-loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Row 3: Country Traffic + Device + Browser ─────────────────────── -->
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
            <div id="country-chart-view" style="position:relative">
                <div class="chart-wrap" style="position:relative">
                    <canvas id="countryChart"></canvas>
                    <div class="loading-overlay" id="country-loading"><div class="spinner"></div></div>
                </div>
            </div>
            <div id="country-table-view" style="display:none;overflow-y:auto;max-height:240px">
                <table class="analytics-table" id="country-table">
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
                        <thead><tr><th>Offer</th><th class="num">Clicks</th><th class="num">Conv</th><th class="num">CR%</th><th class="num">Payout</th><?php if (Auth::role() === "admin"): ?><th class="num">Revenue</th><?php endif; ?><th class="num" title="Fraud conversions are conversions with fraud score between 60–100." style="color:#DC2626">Fraud</th></tr></thead>
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

<!-- ── Profit Overview ────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header" style="background:linear-gradient(135deg,#065F46,#059669);color:#fff">
        <span class="card-title" style="color:#fff">Profit Overview — Last 30 Days</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin:0;gap:0;border-radius:0">
            <?php if (Auth::role() === "admin"): ?>
            <div class="stat-card" style="border-radius:0;border-right:1px solid #E2E8F0">
                <div class="stat-icon green">&#128200;</div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">$<?= number_format($profitSummary['total_revenue'] ?? 0, 2) ?></div>
                <div class="stat-sub">Approved conversions</div>
            </div>
            <?php endif; ?>
            <div class="stat-card" style="border-radius:0;border-right:1px solid #E2E8F0">
                <div class="stat-icon purple">&#128176;</div>
                <div class="stat-label">Total Payout</div>
                <div class="stat-value">$<?= number_format($profitSummary['total_payout'] ?? 0, 2) ?></div>
                <div class="stat-sub">Paid to affiliates</div>
            </div>
            <?php if (Auth::role() === "admin"): ?>
            <div class="stat-card" style="border-radius:0">
                <div class="stat-icon teal">&#9733;</div>
                <div class="stat-label">Net Profit</div>
                <?php $netProfit = (float)($profitSummary['total_profit'] ?? 0); ?>
                <div class="stat-value" style="color:<?= $netProfit >= 0 ? '#059669' : 'var(--danger)' ?>">$<?= number_format($netProfit, 2) ?></div>
                <div class="stat-sub">Revenue minus payout</div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($topProfitOffers)): ?>
        <div>
            <div style="padding:12px 20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-top:1px solid var(--border);background:var(--bg)">Top Profitable Offers</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Offer</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Profit</th><?php endif; ?><th>Conv.</th><th>Margin</th></tr></thead>
                    <tbody>
                    <?php foreach ($topProfitOffers as $po):
                        $margin   = (float)$po['revenue'] > 0 ? round(((float)$po['profit'] / (float)$po['revenue']) * 100, 1) : 0;
                        $profitVal = (float)$po['profit'];
                    ?>
                    <tr>
                        <td class="fw-bold"><a href="/admin/offers/<?= (int)$po['id'] ?>/overview"><?= Helpers::e($po['name']) ?></a></td>
                        <td>$<?= number_format((float)$po['revenue'], 2) ?></td>
                        <td>$<?= number_format((float)$po['payout'], 2) ?></td>
                        <td style="color:<?= $profitVal >= 0 ? '#059669' : 'var(--danger)' ?>;font-weight:700">$<?= number_format($profitVal, 2) ?></td>
                        <td><?= number_format((int)$po['conversions']) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="flex:1;background:#E2E8F0;border-radius:4px;height:6px;min-width:60px">
                                    <div style="background:<?= $margin >= 0 ? '#059669' : '#EF4444' ?>;width:<?= min(abs($margin),100) ?>%;height:100%;border-radius:4px"></div>
                                </div>
                                <span style="font-size:12px;font-weight:600;color:<?= $margin >= 0 ? '#059669' : 'var(--danger)' ?>"><?= $margin ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($trafficSources)): ?>
<!-- ── Traffic Sources (last 30 days) ─────────────────────────────────── -->
<div class="chart-card mb-3">
    <div class="card-header">
        <span class="card-title">Conversions by Traffic Source (Last 30 Days)</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:20px">
        <div class="table-wrap">
            <table class="analytics-table">
                <thead>
                    <tr><th>Source</th><th>Conversions</th><th>Revenue</th><th>Payout</th><th>Profit</th></tr>
                </thead>
                <tbody>
                <?php foreach ($trafficSources as $ts): 
                    $tsLabel = $ts['source'] ?? 'Unknown';
                    $tsColor = TrafficSourceDetector::color($tsLabel);
                    $tsDark  = in_array($tsLabel, ['Threads','TikTok','Facebook Ads','Direct','Unknown','X']);
                ?>
                <tr>
                    <td>
                        <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:<?= $tsColor ?>;color:#fff;white-space:nowrap">
                            <?= Helpers::e($tsLabel) ?>
                        </span>
                    </td>
                    <td><?= number_format((int)$ts['conversions']) ?></td>
                    <td>$<?= number_format((float)$ts['revenue'], 2) ?></td>
                    <td>$<?= number_format((float)$ts['payout'], 2) ?></td>
                    <td style="color:<?= (float)$ts['profit'] >= 0 ? '#059669' : 'var(--danger)' ?>;font-weight:700">
                        $<?= number_format((float)$ts['profit'], 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div>
            <canvas id="tsPieChart" style="max-height:250px;width:100%"></canvas>
            <div style="margin-top:20px">
                <canvas id="tsBarChart" style="max-height:200px;width:100%"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Recent Conversions ─────────────────────────────────────────────── -->
<div class="chart-card mb-3">
    <div class="card-header">
        <span class="card-title">Recent Conversions</span>
        <a href="/admin/conversions" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div style="position:relative;min-height:80px">
        <div class="loading-overlay" id="conv-loading"><div class="spinner"></div></div>
        <div class="table-wrap" style="overflow-x:auto">
            <table class="analytics-table">
                <thead><tr><th>Affiliate</th><th>Offer</th><th class="num">Payout</th><?php if (Auth::role() === "admin"): ?><th class="num">Revenue</th><?php endif; ?><th>Status</th><th>Country</th><th>Device</th><th>Date</th></tr></thead>
                <tbody id="conv-tbody">
                    <tr><td colspan="8" class="text-center text-muted" style="padding:24px">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
const API = '/api/admin-analytics';
const COLORS = ['#4F46E5','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#06B6D4','#F97316','#EC4899','#14B8A6'];
const charts = {};
const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const legendColor = isDark ? '#E2E8F0' : '#475569';
let trendMetrics = new Set(['clicks','conv','revenue','payout','profit','fraud']);
let trendType    = 'line';
let trendData   = {};
const SERVER_TZ = '<?= addslashes($_serverTz) ?>';
const trendChartStyle = '<?= Config::get('config', 'app.trend_chart_style') ?? 'neon_glow' ?>';

function fmt(n, dec){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:dec||0,maximumFractionDigits:dec||0}); }
function fmtCur(n){ return '$'+fmt(n,2); }

// ── Timezone helpers ──────────────────────────────────────────────────
function getTz(){ return document.getElementById('f-tz')?.value || SERVER_TZ; }

// Return YYYY-MM-DD for a timestamp offset from now, in the given timezone
function tzDateStr(tz, msOffset){
    return new Date(Date.now() + (msOffset||0)).toLocaleDateString('en-CA', { timeZone: tz || SERVER_TZ });
}
function tzToday(tz){ return tzDateStr(tz, 0); }
function tzDaysAgo(tz, n){ return tzDateStr(tz, -n * 86400000); }
function tzMonthStart(tz){
    var parts = tzToday(tz).split('-');
    return parts[0] + '-' + parts[1] + '-01';
}
// First day of last month in the given tz, e.g. "2026-03-01"
function tzLastMonthStart(tz){
    var p = tzToday(tz).split('-'); // [yyyy, mm, dd]
    var y = parseInt(p[0],10), m = parseInt(p[1],10) - 1; // 0-based: this month
    var lm = m - 1, ly = y;
    if (lm < 0) { lm = 11; ly -= 1; }
    return ly + '-' + String(lm + 1).padStart(2,'0') + '-01';
}
// Last day of last month in the given tz, e.g. "2026-03-31"
function tzLastMonthEnd(tz){
    var p = tzToday(tz).split('-');
    var y = parseInt(p[0],10), m = parseInt(p[1],10) - 1;
    // Day 0 of this month = last day of previous month, in UTC math
    var d = new Date(Date.UTC(y, m, 0));
    return d.getUTCFullYear() + '-' + String(d.getUTCMonth() + 1).padStart(2,'0') + '-' + String(d.getUTCDate()).padStart(2,'0');
}

function getFilters(){
    return {
        from: document.getElementById('f-from').value,
        to:   document.getElementById('f-to').value,
        offer_id:     document.getElementById('f-offer').value,
        affiliate_id: document.getElementById('f-affiliate').value,
        country:      document.getElementById('f-country').value,
        device:       document.getElementById('f-device').value,
        tz:           getTz(),
    };
}

function qs(params){ return Object.entries(params).filter(([,v])=>v).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&'); }

function showLoad(id){ const el=document.getElementById(id); if(el)el.style.display='flex'; }
function hideLoad(id){ const el=document.getElementById(id); if(el)el.style.display='none'; }

// ── Trend percent badge ──────────────────────────────────────────────
function trendBadge(id, pct){
    const el = document.getElementById(id);
    if(!el) return;
    if(pct===null||pct===undefined){ el.className='kpi-trend flat'; el.textContent='—'; return; }
    const cls = pct>0?'up':pct<0?'down':'flat';
    el.className = 'kpi-trend '+cls;
    el.textContent = (pct>0?'▲':pct<0?'▼':'—')+' '+Math.abs(pct)+'% vs prev';
}

// ── Destroy chart helper ─────────────────────────────────────────────
function destroyChart(id){ if(charts[id]){ charts[id].destroy(); delete charts[id]; } }

function makeChart(id, cfg){
    destroyChart(id);
    const ctx = document.getElementById(id);
    if(!ctx) return null;
    charts[id] = new Chart(ctx, cfg);
    return charts[id];
}

// ── KPI Stats ────────────────────────────────────────────────────────
function loadStats(){
    const f = getFilters();
    fetch(API+'?action=stats&'+qs(f))
        .then(r=>r.json()).then(d=>{
            document.getElementById('k-clicks').textContent  = fmt(d.clicks);
            document.getElementById('k-unique').textContent  = fmt(d.unique);
            document.getElementById('k-conv').textContent    = fmt(d.conv);
            document.getElementById('k-cr').textContent      = fmt(d.cr,2);
            document.getElementById('k-cr2').textContent     = fmt(d.cr,2);
            document.getElementById('k-revenue').textContent = fmt(d.revenue,2);
            document.getElementById('k-payout').textContent  = fmt(d.payout,2);
            document.getElementById('k-fraud').textContent   = fmt(d.fraud);
            const profit = d.profit||0;
            document.getElementById('k-profit').textContent  = (profit<0?'-':'')+fmtCur(Math.abs(profit));
            document.getElementById('k-profit').style.color  = profit>=0?'#059669':'#DC2626';
            // Fraud Conversion % card
            var elPct = document.getElementById('k-fraud-conv-pct');
            var elCnt = document.getElementById('k-fraud-conv');
            if (elPct) elPct.textContent = fmt(d.fraud_conv_pct ?? 0, 2);
            if (elCnt) elCnt.textContent = fmt(d.fraud_conv ?? 0);
            trendBadge('k-clicks-trend',         d.trend?.clicks);
            trendBadge('k-conv-trend',           d.trend?.conv);
            trendBadge('k-revenue-trend',        d.trend?.revenue);
            trendBadge('k-payout-trend',         d.trend?.payout);
            trendBadge('k-fraud-conv-pct-trend', d.trend?.fraud_conv_pct);
        }).catch(()=>{});
}

// ── Performance Trend ────────────────────────────────────────────────
function loadTrend(){
    showLoad('trend-loading');
    const f = getFilters();
    fetch(API+'?action=trend&'+qs(f))
        .then(r=>r.json()).then(d=>{
            trendData = d;
            renderTrendChart();
        }).catch(()=>{}).finally(()=>hideLoad('trend-loading'));
}

function renderTrendChart(){
    const d = typeof trendData !== 'undefined' ? trendData : null;
    if(!d||!d.labels) return;

    let hasAnyData = false;
    if (d.clicks_data && d.clicks_data.some(v => v !== 0)) hasAnyData = true;
    if (d.conv_data && d.conv_data.some(v => v !== 0)) hasAnyData = true;
    if (d.revenue_data && d.revenue_data.some(v => v !== 0)) hasAnyData = true;
    
    // Update SaaS KPIs
    if (d.clicks_data && d.conv_data) {
        var totClicks = d.clicks_data.reduce((a,b)=>a+b,0);
        var totConv = d.conv_data.reduce((a,b)=>a+b,0);
        var totRev = d.revenue_data ? d.revenue_data.reduce((a,b)=>a+b,0) : 0;
        var totProfit = d.profit_data ? d.profit_data.reduce((a,b)=>a+b,0) : 0;
        var totFraud = d.fraud_data ? d.fraud_data.reduce((a,b)=>a+b,0) : 0;
        
        var elClicks = document.getElementById('saas-kpi-clicks');
        if(elClicks) elClicks.textContent = fmt(totClicks);
        var elConv = document.getElementById('saas-kpi-conv');
        if(elConv) elConv.textContent = fmt(totConv);
        var elRev = document.getElementById('saas-kpi-rev');
        if(elRev) elRev.textContent = '$' + fmt(totRev, 2);
        var elProfit = document.getElementById('saas-kpi-profit');
        if(elProfit) elProfit.textContent = '$' + fmt(totProfit, 2);
        
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
            
            // Peak day analysis
            if (d.revenue_data) {
                var maxRev = Math.max(...d.revenue_data);
                var maxRevIdx = d.revenue_data.indexOf(maxRev);
                if (maxRev > 0) {
                    insights.push("Peak revenue of $" + fmt(maxRev) + " on " + d.labels[maxRevIdx] + ".");
                }
                
                // Trend analysis
                if (d.revenue_data.length >= 4) {
                    var mid = Math.floor(d.revenue_data.length / 2);
                    var sum1 = d.revenue_data.slice(0, mid).reduce((a,b)=>a+b,0);
                    var sum2 = d.revenue_data.slice(mid).reduce((a,b)=>a+b,0);
                    if (sum2 > sum1 && sum1 > 0) {
                        var pct = Math.round(((sum2 - sum1) / sum1) * 100);
                        insights.push("Revenue trending UP by " + pct + "% in recent days.");
                    } else if (sum1 > sum2 && sum2 > 0) {
                        insights.push("Revenue momentum has slowed down recently.");
                    }
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

    const wrap = document.getElementById('trendChart').parentElement;
    let emptyMsg = document.getElementById('trend-empty-msg');
    if (!hasAnyData) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('div');
            emptyMsg.id = 'trend-empty-msg';
            emptyMsg.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-weight:500;z-index:5;background:transparent;backdrop-filter:blur(2px);';
            emptyMsg.innerHTML = '<div style="text-align:center"><div style="font-size:24px;margin-bottom:8px">📊</div>No data available for the selected period.</div>';
            wrap.appendChild(emptyMsg);
        }
        emptyMsg.style.display = 'flex';
    } else {
        if (emptyMsg) emptyMsg.style.display = 'none';
    }

    const metricMap = {
        clicks:  { key:'clicks',  label:'Clicks',                  data: d.clicks_data,  color:'#4F46E5', axis:'y'  },
        conv:    { key:'conv',    label:'Conversions',             data: d.conv_data,    color:'#10B981', axis:'y'  },
        revenue: { key:'revenue', label:'Revenue ($)',             data: d.revenue_data, color:'#8B5CF6', axis:'y1', isCur:true },
        payout:  { key:'payout',  label:'Payout ($)',              data: d.payout_data,  color:'#06B6D4', axis:'y1', isCur:true },
        profit:  { key:'profit',  label:'Profit ($)',              data: d.profit_data,  color:'#F59E0B', axis:'y1', isCur:true },
        fraud:   { key:'fraud',   label:'Fraud',                   data: d.fraud_data,   color:'#DC2626', axis:'y'  },
    };
    
    // Instead of using the old toggle buttons array, use the new checkboxes:
    const active = [];
    if (document.getElementById('tog-clicks') && document.getElementById('tog-clicks').checked) active.push('clicks');
    if (document.getElementById('tog-conv') && document.getElementById('tog-conv').checked) active.push('conv');
    if (document.getElementById('tog-rev') && document.getElementById('tog-rev').checked) active.push('revenue');
    if (document.getElementById('tog-payout') && document.getElementById('tog-payout').checked) active.push('payout');
    if (document.getElementById('tog-profit') && document.getElementById('tog-profit').checked) active.push('profit');
    if (document.getElementById('tog-fraud') && document.getElementById('tog-fraud').checked) active.push('fraud');

    const isLine = typeof trendType !== 'undefined' ? (trendType === 'line') : true;
    const hasCur   = active.some(k => metricMap[k] && metricMap[k].isCur);
    const hasCount = active.some(k => metricMap[k] && !metricMap[k].isCur);
    
    const datasets = active.map(k => {
        const m = metricMap[k];
        return {
            label: m.label,
            data: m.data||[],
            borderColor: m.color,
            shadowColor: m.color,
            backgroundColor: function(context) {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea || !isLine) return m.color + (isLine ? '1A' : 'CC');
                let gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, m.color + '80');
                gradient.addColorStop(0.5, m.color + '20');
                gradient.addColorStop(1, m.color + '00');
                return gradient;
            },
            fill: isLine,
            tension: isLine ? 0.45 : 0,
            borderWidth: isLine ? 3 : 0,
            borderRadius: isLine ? 0 : 4,
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

    const cfg = {
        type: isLine ? 'line' : 'bar',
        data:{ labels: d.labels, datasets },
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
    };
    
    // We recreate Chart instead of makeChart to bypass default plugins if necessary, but makeChart works if we pass custom options
    if (window.trendChartInstance) window.trendChartInstance.destroy();
    const ctx = document.getElementById('trendChart');
    if(ctx) window.trendChartInstance = new Chart(ctx, cfg);
}

// ── Hourly Today ──────────────────────────────────────────────────────
function loadHourly(){
    showLoad('hourly-loading');
    const f = getFilters();
    fetch(API+'?action=hourly&'+qs(f))
        .then(r=>r.json()).then(d=>{
            makeChart('hourlyChart',{
                type:'bar',
                data:{
                    labels:d.labels,
                    datasets:[{label:'Clicks',data:d.data,backgroundColor:'rgba(79,70,229,.7)',borderColor:'#4F46E5',borderWidth:1,borderRadius:3}]
                },
                options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
                    scales:{y:{beginAtZero:true,grid:{display:false}},x:{grid:{display:false},ticks:{maxRotation:0,font:{size:10}}}}}
            });
        }).catch(()=>{}).finally(()=>hideLoad('hourly-loading'));
}

// ── Conversion Pie ────────────────────────────────────────────────────
function loadConvPie(){
    showLoad('pie-loading');
    const f = getFilters();
    fetch(API+'?action=conv_status&'+qs(f))
        .then(r=>r.json()).then(d=>{
            const labels = d.labels||[];
            const data   = d.data||[];
            const colors = d.colors||[];
            if(!labels.length){
                hideLoad('pie-loading');
                const cv = document.getElementById('convPieChart');
                if(cv){ const ctx=cv.getContext('2d'); ctx.clearRect(0,0,cv.width,cv.height); }
                return;
            }
            makeChart('convPieChart',{
                type:'doughnut',
                data:{labels,datasets:[{data,backgroundColor:colors,borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}
            });
        }).catch(()=>{}).finally(()=>hideLoad('pie-loading'));
}

// ── Countries ─────────────────────────────────────────────────────────
let countryRows = [];
function loadCountries(){
    showLoad('country-loading');
    const f = getFilters();
    fetch(API+'?action=countries&'+qs(f))
        .then(r=>r.json()).then(d=>{
            countryRows = d.rows||[];
            renderCountryChart();
            renderCountryTable();
        }).catch(()=>{}).finally(()=>hideLoad('country-loading'));
}

function renderCountryChart(){
    const rows = countryRows.slice(0,10);
    makeChart('countryChart',{
        type:'bar',
        data:{
            labels:rows.map(r=>r.country),
            datasets:[
                {label:'Clicks',data:rows.map(r=>r.clicks),backgroundColor:'rgba(79,70,229,.7)',borderColor:'#4F46E5',borderWidth:1,borderRadius:3},
                {label:'Conv',  data:rows.map(r=>r.conv),  backgroundColor:'rgba(16,185,129,.7)',borderColor:'#10B981',borderWidth:1,borderRadius:3}
            ]
        },
        options:{
            indexAxis:'y',responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'top',labels:{font:{size:11},color:legendColor}}},
            scales:{x:{beginAtZero:true,grid:{display:false}},y:{grid:{display:false},ticks:{font:{size:11}}}}
        }
    });
}

function renderCountryTable(){
    const tbody = document.getElementById('country-tbody');
    if(!tbody) return;
    tbody.innerHTML = countryRows.map(r=>{
        const cr = r.clicks>0 ? (r.conv/r.clicks*100).toFixed(1) : '0.0';
        return `<tr><td>${r.country}</td><td class="num">${fmt(r.clicks)}</td><td class="num">${fmt(r.conv)}</td><td class="num">${cr}%</td></tr>`;
    }).join('') || '<tr><td colspan="4" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// ── Devices ───────────────────────────────────────────────────────────
function loadDevices(){
    showLoad('device-loading');
    const f = getFilters();
    fetch(API+'?action=devices&'+qs(f))
        .then(r=>r.json()).then(d=>{
            makeChart('deviceChart',{
                type:'doughnut',
                data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:COLORS,borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}
            });
        }).catch(()=>{}).finally(()=>hideLoad('device-loading'));
}

// ── Browsers ──────────────────────────────────────────────────────────
function loadBrowsers(){
    showLoad('browser-loading');
    const f = getFilters();
    fetch(API+'?action=browsers&'+qs(f))
        .then(r=>r.json()).then(d=>{
            makeChart('browserChart',{
                type:'doughnut',
                data:{labels:d.labels,datasets:[{data:d.data,backgroundColor:COLORS.slice(2),borderColor:'#fff',borderWidth:2}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,color:legendColor}}}}
            });
        }).catch(()=>{}).finally(()=>hideLoad('browser-loading'));
}

// ── Top Offers ────────────────────────────────────────────────────────
let offersRows = [];
function loadOffers(){
    showLoad('offers-loading');
    const f = getFilters();
    fetch(API+'?action=offers&'+qs(f))
        .then(r=>r.json()).then(d=>{
            offersRows = d.rows||[];
            renderOffersChart();
            renderOffersTable();
        }).catch(()=>{}).finally(()=>hideLoad('offers-loading'));
}

function renderOffersChart(){
    const rows = offersRows.slice(0,8);
    makeChart('offersChart',{
        type:'bar',
        data:{
            labels:rows.map(r=>r.name.length>18?r.name.slice(0,18)+'…':r.name),
            datasets:[
                {label:'Payout ($)',          data:rows.map(r=>r.payout),         backgroundColor:COLORS.map(c=>c+'CC'),borderColor:COLORS,borderWidth:1.5,borderRadius:4,yAxisID:'y'},
                {label:'Fraud Conversions',    data:rows.map(r=>r.fraud_conv||0), backgroundColor:'#DC2626CC',borderColor:'#DC2626',borderWidth:1.5,borderRadius:4,yAxisID:'y1'}
            ]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true,position:'bottom',labels:{boxWidth:10,font:{size:11},color:legendColor}}},
            scales:{
                y: {beginAtZero:true,position:'left', grid:{display:false},ticks:{callback:v=>'$'+v}},
                y1:{beginAtZero:true,position:'right',grid:{display:false},  ticks:{callback:v=>v}, title:{display:true,text:'Fraud Conv',font:{size:10},color:'#DC2626'}},
                x: {grid:{display:false},ticks:{font:{size:11}}}
            }}
    });
}

function renderOffersTable(){
    const tbody = document.getElementById('offers-tbody');
    if(!tbody) return;
    tbody.innerHTML = offersRows.map(r=>`
        <tr>
            <td><a href="/admin/offers/${r.id}/overview" style="font-weight:600;color:#4F46E5">${r.name}</a></td>
            <td class="num">${fmt(r.clicks)}</td>
            <td class="num">${fmt(r.conv)}</td>
            <td class="num">${fmt(r.cr,2)}%</td>
            <td class="num">$${fmt(r.payout,2)}</td>
            <td class="num">$${fmt(r.revenue,2)}</td>
            <td class="num" style="color:${(r.fraud_conv||0)>0?'#DC2626':'#94A3B8'};font-weight:600" title="Fraud conversions are conversions with fraud score between 60–100.">${fmt(r.fraud_conv||0)} <span style="font-size:11px;font-weight:400">(${fmt(r.fraud_conv_pct||0,2)}%)</span></td>
        </tr>`).join('') || '<tr><td colspan="7" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// ── Top Affiliates ────────────────────────────────────────────────────
let affsRows = [];
function loadAffiliates(){
    showLoad('affs-loading');
    const f = getFilters();
    fetch(API+'?action=affiliates&'+qs(f))
        .then(r=>r.json()).then(d=>{
            affsRows = d.rows||[];
            renderAffsChart();
            renderAffsTable();
        }).catch(()=>{}).finally(()=>hideLoad('affs-loading'));
}

function renderAffsChart(){
    const rows = affsRows.slice(0,8);
    makeChart('affsChart',{
        type:'bar',
        data:{
            labels:rows.map(r=>r.code||r.name),
            datasets:[{label:'Payout ($)',data:rows.map(r=>r.payout),backgroundColor:COLORS.slice(1).map(c=>c+'CC'),borderColor:COLORS.slice(1),borderWidth:1.5,borderRadius:4}]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
            scales:{y:{beginAtZero:true,grid:{display:false},ticks:{callback:v=>'$'+v}},x:{grid:{display:false},ticks:{font:{size:11}}}}}
    });
}

function renderAffsTable(){
    const tbody = document.getElementById('affs-tbody');
    if(!tbody) return;
    tbody.innerHTML = affsRows.map(r=>`
        <tr>
            <td><div style="font-weight:600">${r.name}</div><div style="font-size:11px;color:#94A3B8">${r.code}</div></td>
            <td class="num">${fmt(r.clicks)}</td>
            <td class="num">${fmt(r.conv)}</td>
            <td class="num">${fmt(r.cr,2)}%</td>
            <td class="num" style="font-weight:700;color:#4F46E5">$${fmt(r.payout,2)}</td>
        </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted" style="padding:16px">No data</td></tr>';
}

// ── Recent Conversions ────────────────────────────────────────────────
function loadConversions(){
    showLoad('conv-loading');
    const f = getFilters();
    const statusBadge = {'approved':'success','pending':'warning','rejected':'danger','chargebacked':'muted'};
    fetch(API+'?action=conversions&'+qs(f)+'&limit=15')
        .then(r=>r.json()).then(d=>{
            const tbody = document.getElementById('conv-tbody');
            tbody.innerHTML = (d.rows||[]).map(r=>`
                <tr>
                    <td style="font-weight:600">${r.aff_name||'—'}</td>
                    <td style="font-size:12px">${r.offer_name}</td>
                    <td class="num">$${fmt(r.payout,2)}</td>
                    <td class="num">$${fmt(r.revenue,2)}</td>
                    <td><span class="badge badge-${statusBadge[r.status]||'muted'}">${r.status}</span></td>
                    <td style="font-size:12px">${r.country||'—'}</td>
                    <td style="font-size:12px">${r.device_type||'—'}</td>
                    <td style="font-size:12px;color:#94A3B8">${r.converted_at?r.converted_at.slice(0,16):'—'}</td>
                </tr>`).join('') || '<tr><td colspan="8" class="text-center text-muted" style="padding:24px">No conversions in this period</td></tr>';
        }).catch(()=>{}).finally(()=>hideLoad('conv-loading'));
}

// ── Load filter dropdowns ─────────────────────────────────────────────
function loadFilterOptions(){
    fetch(API+'?action=filters')
        .then(r=>r.json()).then(d=>{
            const offerSel = document.getElementById('f-offer');
            (d.offers||[]).forEach(o=>{ const opt=document.createElement('option'); opt.value=o.id; opt.textContent=o.name; offerSel.appendChild(opt); });

            const affSel = document.getElementById('f-affiliate');
            (d.affiliates||[]).forEach(a=>{ const opt=document.createElement('option'); opt.value=a.id; opt.textContent=a.label; affSel.appendChild(opt); });

            const cntrSel = document.getElementById('f-country');
            (d.countries||[]).forEach(c=>{ const opt=document.createElement('option'); opt.value=c; opt.textContent=c; cntrSel.appendChild(opt); });
        }).catch(()=>{});
}

// ── Apply/Reset/Period tabs ───────────────────────────────────────────
window.applyFilters = function(){
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    var tz = getTz();
    var tzShort = tz.split('/').pop().replace(/_/g,' ');
    var u = document.getElementById('dash-updated');
    if(u) u.textContent = 'Updated ' + new Date().toLocaleTimeString(undefined, { timeZone: tz }) + ' (' + tzShort + ')';
    loadAll();
};

window.resetFilters = function(){
    var tz = getTz();
    document.getElementById('f-from').value     = tzDaysAgo(tz, 29);
    document.getElementById('f-to').value       = tzToday(tz);
    document.getElementById('f-offer').value    = '';
    document.getElementById('f-affiliate').value= '';
    document.getElementById('f-country').value  = '';
    document.getElementById('f-device').value   = '';
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    // Reset selects the "30D" preset — find it by its onclick handler so future
    // tab additions don't shift this off by index.
    var _resetTab = Array.prototype.find.call(
        document.querySelectorAll('.dash-period-tab'),
        function(b){ return /'30d'/.test(b.getAttribute('onclick') || ''); }
    );
    if (_resetTab) _resetTab.classList.add('active');
    loadAll();
};

window.setPeriod = function(btn, val){
    document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    var tz   = getTz();
    var today = tzToday(tz);
    var from = today, to = today;
    if     (val==='today')    { from=today; to=today; }
    else if(val==='yesterday'){ from=tzDaysAgo(tz,1);  to=tzDaysAgo(tz,1); }
    else if(val==='7d')       { from=tzDaysAgo(tz,6);  to=today; }
    else if(val==='15d')      { from=tzDaysAgo(tz,14); to=today; }
    else if(val==='30d')      { from=tzDaysAgo(tz,29); to=today; }
    else if(val==='90d')      { from=tzDaysAgo(tz,89); to=today; }
    else if(val==='mtd')      { from=tzMonthStart(tz); to=today; }
    else if(val==='lastmonth'){ from=tzLastMonthStart(tz); to=tzLastMonthEnd(tz); }
    else return;
    document.getElementById('f-from').value = from;
    document.getElementById('f-to').value   = to;
    loadAll();
};

// ── Trend metric/type toggles ─────────────────────────────────────────
document.querySelectorAll('#trend-metric-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        const key = this.dataset.metric;
        if (trendMetrics.has(key)) {
            // Don't allow turning off the last active metric
            if (trendMetrics.size <= 1) return;
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
        this.classList.add('active');
        renderTrendChart();
    });
});

// ── Country chart/table toggle ────────────────────────────────────────
document.querySelectorAll('#country-view-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        const view=this.dataset.view;
        document.querySelectorAll('#country-view-btns button').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('country-chart-view').style.display = view==='chart'?'':'none';
        document.getElementById('country-table-view').style.display = view==='table'?'':'none';
    });
});

// ── Offers chart/table toggle ─────────────────────────────────────────
document.querySelectorAll('#offers-view-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        const view=this.dataset.view;
        document.querySelectorAll('#offers-view-btns button').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('offers-chart-view').style.display = view==='chart'?'':'none';
        document.getElementById('offers-table-view').style.display = view==='table'?'':'none';
    });
});

// ── Affiliates chart/table toggle ─────────────────────────────────────
document.querySelectorAll('#affs-view-btns button').forEach(btn=>{
    btn.addEventListener('click',function(){
        const view=this.dataset.view;
        document.querySelectorAll('#affs-view-btns button').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('affs-chart-view').style.display = view==='chart'?'':'none';
        document.getElementById('affs-table-view').style.display = view==='table'?'':'none';
    });
});

// ── Load everything ───────────────────────────────────────────────────
function loadAll(){
    loadStats();
    loadTrend();
    loadHourly();
    loadConvPie();
    loadCountries();
    loadDevices();
    loadBrowsers();
    loadOffers();
    loadAffiliates();
    loadConversions();
    var tz = getTz();
    var tzShort = tz.split('/').pop().replace(/_/g,' ');
    var u = document.getElementById('dash-updated');
    if(u) u.textContent = 'Updated ' + new Date().toLocaleTimeString(undefined, { timeZone: tz }) + ' (' + tzShort + ')';
}

// ── Live poll (every 30s) ─────────────────────────────────────────────
function showLiveBadge(){}  // kept for compat

// ── Live refresh ──────────────────────────────────────────────────────
var _kpiTs = Date.now();

// KPIs every 5 s
setInterval(function(){ loadStats(); _kpiTs = Date.now(); }, 1000);

// Full data (charts + tables) every 60 s
setInterval(function(){ loadAll(); _kpiTs = Date.now(); }, 60000);

// Ticker every 1 s — always-visible badge
setInterval(function(){
    var t = document.getElementById('live-badge-txt');
    if(!t) return;
    var s = Math.round((Date.now() - _kpiTs) / 1000);
    t.textContent = s <= 1 ? 'Live · just now' : 'Live · ' + s + 's ago';
}, 1000);

// ── Timezone selector: init from localStorage + live change handler ───
(function initTz(){
    var saved = localStorage.getItem('dashTz') || SERVER_TZ;
    var sel   = document.getElementById('f-tz');
    if (!sel) return;
    // Try to select the saved value; fall back to server TZ
    var found = Array.from(sel.options).some(function(o){ return o.value === saved; });
    sel.value = found ? saved : SERVER_TZ;
    if (!found) saved = SERVER_TZ;
    // Update date inputs to match restored timezone
    document.getElementById('f-from').value = tzDaysAgo(saved, 29);
    document.getElementById('f-to').value   = tzToday(saved);
    // Live change: save, refresh dates, reload data
    sel.addEventListener('change', function(){
        var tz = this.value;
        localStorage.setItem('dashTz', tz);
        document.getElementById('f-from').value = tzDaysAgo(tz, 29);
        document.getElementById('f-to').value   = tzToday(tz);
        document.querySelectorAll('.dash-period-tab').forEach(p=>p.classList.remove('active'));
        document.querySelector('.dash-period-tab:nth-child(3)')?.classList.add('active');
        loadAll();
    });
})();

// ── Init ───────────────────────────────────────────────────────────────
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

// ── Traffic Source Charts (Static PHP Data) ───────────────────────────
(function() {
    var tsData = <?= json_encode($trafficSources ?? []) ?>;
    if (!tsData || tsData.length === 0) return;

    // Load Chart.js if not already loaded by dynamic charts
    if (typeof Chart === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = renderTsCharts;
        document.head.appendChild(script);
    } else {
        renderTsCharts();
    }

    function renderTsCharts() {
        var labels = tsData.map(d => d.source || 'Unknown');
        var convs  = tsData.map(d => parseInt(d.conversions));
        var revs   = tsData.map(d => parseFloat(d.revenue));
        var profs  = tsData.map(d => parseFloat(d.profit));
        
        // Use PHP detector colors injected via JS
        var colorMap = <?= json_encode(TrafficSourceDetector::SOURCE_COLORS) ?>;
        var colors = labels.map(l => colorMap[l] || '#94A3B8');

        // Pie Chart
        var ctxPie = document.getElementById('tsPieChart');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: convs,
                        backgroundColor: colors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 12 } },
                        title: { display: true, text: 'Conversion Distribution' }
                    },
                    cutout: '65%'
                }
            });
        }

        // Bar Chart (Revenue vs Profit)
        var ctxBar = document.getElementById('tsBarChart');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Revenue', data: revs, backgroundColor: '#3b82f6', borderRadius: 2 },
                        { label: 'Profit', data: profs, backgroundColor: '#10b981', borderRadius: 2 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true, position: 'top' } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { borderDash: [2,2], color: '#e2e8f0' } }
                    }
                }
            });
        }
    }
})();

})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
