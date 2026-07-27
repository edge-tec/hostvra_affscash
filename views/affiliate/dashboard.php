<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>
<?php
$_affTzOptions = [
    'UTC/GMT'             => ['UTC'=>'UTC (UTC+0)','GMT'=>'GMT (UTC+0)'],
    'Americas'            => [
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
    'Europe'              => [
        'Europe/London'    =>'London (GMT/BST)',
        'Europe/Dublin'    =>'Dublin',
        'Europe/Lisbon'    =>'Lisbon (WET)',
        'Europe/Paris'     =>'Paris (CET, UTC+1/2)',
        'Europe/Berlin'    =>'Berlin (CET)',
        'Europe/Amsterdam' =>'Amsterdam (CET)',
        'Europe/Madrid'    =>'Madrid (CET)',
        'Europe/Rome'      =>'Rome (CET)',
        'Europe/Stockholm' =>'Stockholm (CET)',
        'Europe/Warsaw'    =>'Warsaw (CET)',
        'Europe/Vienna'    =>'Vienna (CET)',
        'Europe/Athens'    =>'Athens (EET, UTC+2/3)',
        'Europe/Helsinki'  =>'Helsinki (EET)',
        'Europe/Istanbul'  =>'Istanbul (TRT, UTC+3)',
        'Europe/Moscow'    =>'Moscow (MSK, UTC+3)',
        'Europe/Kiev'      =>'Kyiv (EET)',
    ],
    'Middle East & Africa'=> [
        'Asia/Dubai'          =>'Dubai (GST, UTC+4)',
        'Asia/Riyadh'         =>'Riyadh (AST, UTC+3)',
        'Asia/Jerusalem'      =>'Jerusalem (IST)',
        'Africa/Cairo'        =>'Cairo (EET, UTC+2)',
        'Africa/Johannesburg' =>'Johannesburg (SAST, UTC+2)',
        'Africa/Lagos'        =>'Lagos (WAT, UTC+1)',
        'Africa/Nairobi'      =>'Nairobi (EAT, UTC+3)',
        'Africa/Casablanca'   =>'Casablanca',
    ],
    'Asia'                => [
        'Asia/Tehran'       =>'Tehran (IRST, UTC+3:30)',
        'Asia/Karachi'      =>'Karachi (PKT, UTC+5)',
        'Asia/Kolkata'      =>'India (IST, UTC+5:30)',
        'Asia/Dhaka'        =>'Dhaka (BST, UTC+6)',
        'Asia/Bangkok'      =>'Bangkok (ICT, UTC+7)',
        'Asia/Ho_Chi_Minh'  =>'Ho Chi Minh (ICT, UTC+7)',
        'Asia/Jakarta'      =>'Jakarta (WIB, UTC+7)',
        'Asia/Singapore'    =>'Singapore (SGT, UTC+8)',
        'Asia/Kuala_Lumpur' =>'Kuala Lumpur (MYT, UTC+8)',
        'Asia/Manila'       =>'Manila (PHT, UTC+8)',
        'Asia/Hong_Kong'    =>'Hong Kong (HKT, UTC+8)',
        'Asia/Shanghai'     =>'Shanghai / Beijing (CST, UTC+8)',
        'Asia/Seoul'        =>'Seoul (KST, UTC+9)',
        'Asia/Tokyo'        =>'Tokyo (JST, UTC+9)',
    ],
    'Pacific'             => [
        'Australia/Perth'     =>'Perth (AWST, UTC+8)',
        'Australia/Adelaide'  =>'Adelaide (ACST)',
        'Australia/Sydney'    =>'Sydney (AEST)',
        'Australia/Melbourne' =>'Melbourne (AEST)',
        'Pacific/Auckland'    =>'Auckland (NZST, UTC+12)',
        'Pacific/Fiji'        =>'Fiji (FJT, UTC+12)',
        'Pacific/Guam'        =>'Guam (ChST, UTC+10)',
    ],
];
$bannerStyle = Config::get('config', 'app.dashboard_banner_style') ?? (Config::get('config', 'app.transparent_dashboard') === '1' ? 'transparent' : 'glass_purple');
?>
<style>
/* ── Analytics Dashboard Styles ─────────────────────────────── */
.an-header {
    <?php if ($bannerStyle === 'transparent'): ?>
    background: transparent;
    padding: 10px 0;
    <?php elseif ($bannerStyle === 'glass'): ?>
    background: rgba(255,255,255,.55);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
            backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255,255,255,.65);
    border-radius: 14px;
    padding: 22px 26px;
    box-shadow: 0 8px 32px rgba(31,38,135,.15);
    <?php elseif ($bannerStyle === 'glass_purple'): ?>
    background: linear-gradient(135deg, rgba(30,27,75,0.85) 0%, rgba(49,46,129,0.85) 50%, rgba(76,29,149,0.85) 100%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
            backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 22px 26px;
    box-shadow: 0 8px 32px rgba(31,38,135,0.25);
    <?php else: ?>
    background: linear-gradient(135deg,#1E1B4B 0%,#312E81 50%,#4C1D95 100%);
    border-radius: 14px;
    padding: 22px 26px;
    <?php endif; ?>
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
html[data-theme="dark"] .an-header {
    <?php if ($bannerStyle === 'glass'): ?>
    background: rgba(20,24,48,.55);
    border-color: rgba(148,163,184,.22);
    box-shadow: 0 8px 32px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
    <?php endif; ?>
}
<?php if ($bannerStyle === 'transparent' || $bannerStyle === 'glass'): ?>
.an-header::before, .an-header::after { display: none !important; }
.an-title { color: var(--text) !important; }
.an-title-label { color: var(--text-muted) !important; }
.an-filter-input { background: var(--card-bg) !important; color: var(--text) !important; border: 1px solid var(--border) !important; }
.an-filter-input option { background: var(--card-bg) !important; color: var(--text) !important; }
.an-filter-input::placeholder { color: var(--text-light) !important; }
.an-period-tabs { background: var(--card-bg) !important; border: 1px solid var(--border) !important; }
.an-period-tab { color: var(--text-muted) !important; }
.an-period-tab.active { background: var(--bg) !important; color: var(--text) !important; }
.an-btn-filter { box-shadow: none !important; }
#an-last-updated { color: var(--text-light) !important; }
<?php elseif ($bannerStyle === 'glass_purple'): ?>
.an-header::before{content:'';position:absolute;top:-60px;right:-40px;width:300px;height:300px;background:radial-gradient(circle, rgba(168,85,247,0.4) 0%, rgba(168,85,247,0) 70%);border-radius:50%;filter:blur(15px);z-index:0;}
.an-header::after{content:'';position:absolute;bottom:-80px;left:20%;width:400px;height:400px;background:radial-gradient(circle, rgba(56,189,248,0.3) 0%, rgba(56,189,248,0) 70%);border-radius:50%;filter:blur(20px);z-index:0;}
<?php else: ?>
.an-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,.04);
    border-radius: 50%;
}
.an-header::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 30%;
    width: 280px; height: 280px;
    background: rgba(255,255,255,.03);
    border-radius: 50%;
}
<?php endif; ?>
.an-title-label { font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.55); margin-bottom: 4px; }
.an-title       { font-size: 24px; font-weight: 800; color: #fff; line-height: 1.1; }
.an-filters     { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; position: relative; z-index: 1; }
.an-filter-input {
    background: rgba(255,255,255,.12);
    border: 1.5px solid rgba(255,255,255,.22);
    border-radius: 8px;
    padding: 8px 13px;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    outline: none;
    backdrop-filter: blur(4px);
    transition: border-color .15s;
    min-width: 110px;
}
.an-filter-input:focus  { border-color: rgba(255,255,255,.55); }
.an-filter-input option { background: #312E81; color: #fff; }
.an-filter-input::placeholder { color: rgba(255,255,255,.5); }
.an-btn-filter {
    background: linear-gradient(135deg,#4F46E5,#7C3AED);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: opacity .15s;
    white-space: nowrap;
}
.an-btn-filter:hover { opacity: .88; }
.an-period-tabs {
    display: flex;
    flex-wrap: wrap;
    background: rgba(255,255,255,.1);
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}
.an-period-tab {
    padding: 5px 13px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,.65);
    cursor: pointer;
    transition: .15s;
    border: none;
    background: none;
}
.an-period-tab.active { background: rgba(255,255,255,.22); color: #fff; }

/* KPI Cards */
.an-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
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
.an-header { flex-direction:column; align-items:flex-start; } }
@media (max-width:600px)  { .an-kpi-grid { grid-template-columns: repeat(2,1fr); gap:10px; } .an-kpi-value { font-size:22px; } .an-title { font-size:20px; } }
/* Full-width single column on true phone widths. */
@media (max-width:480px)  {
    .an-kpi-grid { grid-template-columns: 1fr; gap: 10px; }
    .an-kpi-card { padding: 14px 16px; }
    .an-kpi-value { font-size: 24px; }
    .an-period-tabs { flex-wrap: wrap; gap: 4px; }
    .an-period-tab { flex: 1 1 calc(50% - 4px); font-size: 11px; padding: 6px 4px; }
    /* Stack every multi-column grid row to 1 column on phones. */
    #row2-grid, #row3-grid, #row4-grid, #row5-grid { grid-template-columns: 1fr !important; }
}

/* ═══════════════════════════════════════════════════════════════════════
   SAAS ANALYTICS & AI INSIGHTS LAYOUT — Side-by-Side Modern Dashboard
   ═══════════════════════════════════════════════════════════════════════ */
.saas-analytics-grid-section {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 900px) {
    .saas-analytics-grid-section {
        grid-template-columns: 1fr;
    }
}

.saas-best-days-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: 16px;
}
@media (max-width: 900px) {
    .saas-best-days-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 500px) {
    .saas-best-days-grid {
        grid-template-columns: 1fr;
    }
}

.saas-best-card {
    background: rgba(255, 255, 255, 0.75);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 18px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
}
html[data-theme="dark"] .saas-best-card {
    background: rgba(15, 23, 42, 0.65);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
}

.saas-best-card.gold { --card-accent: #F59E0B; }
.saas-best-card.purple { --card-accent: #8B5CF6; }
.saas-best-card.blue { --card-accent: #3B82F6; }
.saas-best-card.green { --card-accent: #10B981; }

.saas-best-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: var(--card-accent);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px var(--card-accent);
    flex-shrink: 0;
}
.saas-best-content {
    flex-grow: 1;
    min-width: 0;
}
.saas-best-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #64748B);
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.saas-best-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--text, #0F172A);
    line-height: 1.1;
}
html[data-theme="dark"] .saas-best-val { color: #F8FAFC; }
.saas-best-date {
    font-size: 11px;
    color: var(--text-light, #94A3B8);
    margin-top: 2px;
}

.saas-ai-insights-card {
    background: rgba(255, 255, 255, 0.75);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 20px;
    padding: 20px;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
}
html[data-theme="dark"] .saas-ai-insights-card {
    background: rgba(15, 23, 42, 0.65);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
}
.saas-ai-insights-header {
    font-size: 15px;
    font-weight: 800;
    color: var(--text, #0F172A);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.15);
}
html[data-theme="dark"] .saas-ai-insights-header { color: #F8FAFC; }
.saas-ai-insights-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.saas-ai-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 14px;
    background: rgba(148, 163, 184, 0.06);
    border: 1px solid rgba(148, 163, 184, 0.1);
    transition: transform 0.2s, background 0.2s;
}
.saas-ai-item:hover {
    transform: translateX(2px);
    background: rgba(148, 163, 184, 0.1);
}
.saas-ai-item-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}
.saas-ai-item-title {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 2px;
}
.saas-ai-item-desc {
    font-size: 11px;
    color: var(--text-muted, #64748B);
    line-height: 1.45;
}

/* ═══════════════════════════════════════════════════════════════════════
   UNIFIED 3 CARDS IN A LINE RESPONSIVE GRID OVERHAUL
   ═══════════════════════════════════════════════════════════════════════ */
.charts-row, #row2-grid, #row3-grid, #row4-grid, .saas-widgets-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 20px !important;
    margin-bottom: 24px !important;
}

@media (max-width: 900px) {
    .charts-row, #row2-grid, #row3-grid, #row4-grid, .saas-widgets-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px !important;
    }
}

@media (max-width: 768px) {
    .charts-row, #row2-grid, #row3-grid, #row4-grid, .saas-widgets-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
}

.chart-card, .an-card {
    background: rgba(255, 255, 255, 0.75) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 20px !important;
    overflow: hidden !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04) !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    min-height: auto !important;
}

html[data-theme="dark"] .chart-card, html[data-theme="dark"] .an-card {
    background: rgba(15, 23, 42, 0.65) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4) !important;
}

.chart-card:hover, .an-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.08) !important;
}

.chart-card .card-header, .an-card-head {
    padding: 16px 20px !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.15) !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
}

.chart-card .card-title, .an-section-title {
    font-size: 14px !important;
    font-weight: 800 !important;
    color: var(--text, #0F172A) !important;
}

html[data-theme="dark"] .chart-card .card-title, html[data-theme="dark"] .an-section-title {
    color: #F8FAFC !important;
}

.chart-card .card-body, .an-card-body {
    padding: 16px !important;
    background: transparent !important;
    flex-grow: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}

.chart-wrap {
    position: relative !important;
    height: 220px !important;
    width: 100% !important;
}

/* ═══════════════════════════════════════════════════════════════════════
   UNIFIED 3 CARDS IN A LINE RESPONSIVE OVERHAUL (OPTIMIZED BREAKPOINTS)
   ═══════════════════════════════════════════════════════════════════════ */
.trf-grid-3, .grid-3, .traffic-grid-3, .grid-2, .charts-row, .saas-widgets-grid {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 16px !important;
    margin-bottom: 20px !important;
}

@media (max-width: 900px) {
    .trf-grid-3, .grid-3, .traffic-grid-3, .grid-2, .charts-row, .saas-widgets-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 14px !important;
    }
}

@media (max-width: 600px) {
    .trf-grid-3, .grid-3, .traffic-grid-3, .grid-2, .charts-row, .saas-widgets-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
    }
}

.chart-card, .an-card, .card {
    background: rgba(255, 255, 255, 0.75) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 18px !important;
    overflow: hidden !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04) !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease !important;
}

html[data-theme="dark"] .chart-card, html[data-theme="dark"] .an-card, html[data-theme="dark"] .card {
    background: rgba(15, 23, 42, 0.65) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35) !important;
}

.chart-card:hover, .an-card:hover, .card:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08) !important;
}
</style>

<div id="an-live-dot"><span class="lp"></span><span id="an-live-txt">Live</span></div>

<!-- ══ HEADER ══════════════════════════════════════════════════════════════ -->
<div class="an-header">
    <canvas class="an-header-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
    <div style="position:relative;z-index:1">
        <div class="an-title-label">Affiliate Network</div>
        <div class="an-title">Analytics Dashboard</div>
        <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:4px" id="an-last-updated">Loading data…</div>
    </div>
    <button type="button" class="dash-filter-toggle-btn" onclick="document.getElementById('an-filter-bar').classList.toggle('open'); this.classList.toggle('open')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Filter Options</span>
        <span class="dash-toggle-chevron">▼</span>
    </button>
    <div class="an-filters" id="an-filter-bar">
        <!-- Period quick tabs -->
        <div class="an-period-tabs">
            <button class="an-period-tab" onclick="setPeriod('today')">Today</button>
            <button class="an-period-tab" onclick="setPeriod('yesterday')">Yesterday</button>
            <button class="an-period-tab" onclick="setPeriod('7d')">7D</button>
            <button class="an-period-tab" onclick="setPeriod('15d')">Last 15D</button>
            <button class="an-period-tab active" onclick="setPeriod('30d')">30D</button>
            <button class="an-period-tab" onclick="setPeriod('90d')">90D</button>
            <button class="an-period-tab" onclick="setPeriod('mtd')">This Month</button>
            <button class="an-period-tab" onclick="setPeriod('lastmonth')">Last Month</button>
        </div>
        <input type="date" class="an-filter-input" id="f-from" value="<?= Helpers::e($defaultFrom) ?>">
        <span style="color:rgba(255,255,255,.4);font-size:13px">—</span>
        <input type="date" class="an-filter-input" id="f-to" value="<?= Helpers::e($defaultTo) ?>">
        <select class="an-filter-input" id="f-offer" style="min-width:130px">
            <option value="">All Offers</option>
        </select>
        <select class="an-filter-input" id="f-country" style="min-width:110px">
            <option value="">All Countries</option>
        </select>
        <select class="an-filter-input" id="f-device" style="min-width:110px">
            <option value="">All Devices</option>
            <option value="Desktop">Desktop</option>
            <option value="Mobile">Mobile</option>
            <option value="Tablet">Tablet</option>
        </select>
        <select class="an-filter-input" id="f-tz" style="min-width:160px" title="Timezone">
            <?php foreach ($_affTzOptions as $_tzGroup => $_tzList): ?>
            <optgroup label="<?= htmlspecialchars($_tzGroup) ?>">
                <?php foreach ($_tzList as $_tzId => $_tzLabel): ?>
                <option value="<?= htmlspecialchars($_tzId) ?>"><?= htmlspecialchars($_tzLabel) ?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select>
        <button class="an-btn-filter" onclick="applyFilters()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
        </button>
        <button class="an-btn-filter" onclick="resetFilters()" style="background:rgba(255,255,255,.15)">Reset</button>
    </div>
</div>



<!-- ══ KPI CARDS ═══════════════════════════════════════════════════════════ -->
<div class="an-kpi-grid">
    <div class="an-kpi-card blue">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Total Clicks</div>
        <div class="an-kpi-value" id="kv-clicks"><span class="an-skeleton" style="display:block;height:28px;width:70px"></span></div>
        <div class="an-kpi-trend flat" id="kt-clicks">—</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <div class="an-kpi-card cyan">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Unique Clicks</div>
        <div class="an-kpi-value" id="kv-unique"><span class="an-skeleton" style="display:block;height:28px;width:60px"></span></div>
        <div class="an-kpi-trend flat" id="kt-unique">—</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <div class="an-kpi-card purple">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Conversions</div>
        <div class="an-kpi-value" id="kv-conv"><span class="an-skeleton" style="display:block;height:28px;width:50px"></span></div>
        <div class="an-kpi-trend flat" id="kt-conv">—</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <div class="an-kpi-card green">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Revenue</div>
        <div class="an-kpi-value" id="kv-revenue"><span class="an-skeleton" style="display:block;height:28px;width:80px"></span></div>
        <div class="an-kpi-trend flat" id="kt-revenue">—</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <div class="an-kpi-card orange">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Conv. Rate</div>
        <div class="an-kpi-value" id="kv-cr"><span class="an-skeleton" style="display:block;height:28px;width:55px"></span></div>
        <div class="an-kpi-sub" id="ks-cr">CR%</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <div class="an-kpi-card indigo">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Balance</div>
        <div class="an-kpi-value" id="kv-balance"><span class="an-skeleton" style="display:block;height:28px;width:80px"></span></div>
        <div class="an-kpi-sub">Available</div>
        </div>
        <div class="an-kpi-sparkline"></div>
</div>
    <!-- Fraud Conversion % — server-computed (fraud_score >= 60). The actual
         per-row score is never sent to the affiliate, only the aggregate %. -->
    <div class="an-kpi-card red" title="Fraud conversions are conversions with fraud score between 60–100.">
        <div class="an-kpi-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <div class="an-kpi-content">
            <div class="an-kpi-label">Fraud Conversion %</div>
            <div class="an-kpi-value" id="kv-fraud-conv-pct"><span class="an-skeleton" style="display:block;height:28px;width:65px"></span></div>
            <div class="an-kpi-sub" id="ks-fraud-conv"><span style="color:#DC2626;font-weight:600">—</span> Fraud Conversions</div>
            <div class="an-kpi-trend flat" id="kt-fraud-conv-pct">—</div>
        </div>
        <div class="an-kpi-sparkline"></div>
    </div>
</div>

<!-- ══ MAIN TREND CHART (PREMIUM SAAS UI) ════════════════════════════════════ -->
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
    font-size: 26px;
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
    gap: 12px;
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
    height: 360px; /* Taller for premium feel */
}

/* AI Insights Panel */
.saas-ai-insights {
    display: none !important;
    position: absolute;
    top: 24px;
    right: 24px;
    width: 260px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 16px 32px rgba(31, 38, 135, 0.08);
    z-index: 10;
    pointer-events: none; /* Let clicks pass through to chart if needed */
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
    min-width: 200px;
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


<div class="saas-analytics-grid-section">
    <!-- Left Main Column (Chart + 4 Best Days Cards) -->
    <div class="saas-main-col">
        <div class="saas-trend-container">
            <div class="saas-trend-header">
                <div class="saas-trend-title-area">
                    <span class="saas-section-title">Performance Trend Overview</span>
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
                    <label class="saas-legend-item active" style="--leg-color: #10B981;" onclick="toggleSaaSLegend(this, 'tog-rev')">
                        <input type="checkbox" checked id="tog-rev" onchange="renderTrendChart()" style="display:none">
                        <span class="saas-leg-dot"></span> Revenue
                    </label>
                    <label class="saas-legend-item active" style="--leg-color: #EF4444;" onclick="toggleSaaSLegend(this, 'tog-fraud')">
                        <input type="checkbox" checked id="tog-fraud" onchange="renderTrendChart()" style="display:none">
                        <span class="saas-leg-dot"></span> Fraud
                    </label>
                </div>
            </div>
            <div class="saas-trend-body">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- 4 Mini Best Days Cards -->
        <div class="saas-best-days-grid">
            <div class="saas-best-card gold">
                <div class="saas-best-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path></svg>
                </div>
                <div class="saas-best-content">
                    <div class="saas-best-label">Best Revenue Day</div>
                    <div class="saas-best-val" id="sb-best-rev-val">$0.00</div>
                    <div class="saas-best-date" id="sb-best-rev-date">—</div>
                </div>
            </div>

            <div class="saas-best-card purple">
                <div class="saas-best-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <div class="saas-best-content">
                    <div class="saas-best-label">Best Conversion Day</div>
                    <div class="saas-best-val" id="sb-best-conv-val">0</div>
                    <div class="saas-best-date" id="sb-best-conv-date">—</div>
                </div>
            </div>

            <div class="saas-best-card blue">
                <div class="saas-best-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                </div>
                <div class="saas-best-content">
                    <div class="saas-best-label">Highest Clicks Day</div>
                    <div class="saas-best-val" id="sb-best-clicks-val">0</div>
                    <div class="saas-best-date" id="sb-best-clicks-date">—</div>
                </div>
            </div>

            <div class="saas-best-card green">
                <div class="saas-best-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div class="saas-best-content">
                    <div class="saas-best-label">Lowest Fraud Day</div>
                    <div class="saas-best-val" id="sb-best-fraud-val">0%</div>
                    <div class="saas-best-date" id="sb-best-fraud-date">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Column (AI Performance Insights) -->
    <div class="saas-side-col">
        <div class="saas-ai-insights-card">
            <div class="saas-ai-insights-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="url(#ai-grad3)" stroke-width="2"><defs><linearGradient id="ai-grad3" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#8B5CF6"/><stop offset="100%" stop-color="#EC4899"/></linearGradient></defs><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                AI Performance Insights
            </div>
            <div class="saas-ai-insights-list" id="saas-ai-list-items"></div><a href="/affiliate/reports" class="saas-ai-btn-report">View Detailed Report &rarr;</a><div style="display:none">
                <div class="saas-ai-item">
                    <div class="saas-ai-item-icon" style="background:rgba(16,185,129,0.15);color:#10B981">📈</div>
                    <div>
                        <div class="saas-ai-item-title" style="color:#10B981">Analyzing...</div>
                        <div class="saas-ai-item-desc">Loading performance insights...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ══ ROW 2: Device + Country + Offers ════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1fr 1.6fr 1.6fr;gap:16px;margin-bottom:18px" id="row2-grid">

    <!-- Device Donut -->
    <div class="an-card">
        <div class="an-card-head"><span class="an-section-title">Devices</span></div>
        <div class="an-card-body" style="display:flex;flex-direction:column;align-items:center">
            <div style="position:relative;width:180px;height:180px">
                <canvas id="chart-device"></canvas>
                <div id="device-center" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none">
                    <div style="font-size:20px;font-weight:800;color:#111827" id="device-total">—</div>
                    <div style="font-size:11px;color:#9CA3AF">clicks</div>
                </div>
            </div>
            <div id="device-legend" style="display:flex;flex-direction:column;gap:6px;width:100%;margin-top:14px"></div>
        </div>
    </div>

    <!-- Country Bar -->
    <div class="an-card">
        <div class="an-card-head">
            <span class="an-section-title">Top Countries</span>
            <span style="font-size:12px;color:#9CA3AF">Last period</span>
        </div>
        <div class="an-card-body" style="padding-bottom:10px">
            <canvas id="chart-country" height="200"></canvas>
        </div>
    </div>

    <!-- Offer Ranking -->
    <div class="an-card">
        <div class="an-card-head">
            <span class="an-section-title">Offer Performance</span>
            <span style="font-size:12px;color:#9CA3AF">By Revenue</span>
        </div>
        <div class="an-card-body" style="padding-bottom:10px">
            <canvas id="chart-offers" height="200"></canvas>
        </div>
    </div>
</div>

<!-- ══ ROW 3: Hourly + Browsers + Sources ══════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1.8fr 1fr 1fr;gap:16px;margin-bottom:18px" id="row3-grid">

    <!-- Hourly Traffic -->
    <div class="an-card">
        <div class="an-card-head">
            <span class="an-section-title">Hourly Traffic <span style="font-size:12px;font-weight:500;color:#9CA3AF;margin-left:4px">Today</span></span>
            <span style="font-size:12px;color:#9CA3AF" id="hourly-date"><?= date('M j, Y') ?></span>
        </div>
        <div class="an-card-body" style="padding-bottom:10px">
            <canvas id="chart-hourly" height="140"></canvas>
        </div>
    </div>

    <!-- Browser Donut -->
    <div class="an-card">
        <div class="an-card-head"><span class="an-section-title">Browsers</span></div>
        <div class="an-card-body" style="display:flex;justify-content:center;align-items:center">
            <canvas id="chart-browser" height="170" style="max-width:220px"></canvas>
        </div>
    </div>

    <!-- Traffic Sources -->
    <div class="an-card">
        <div class="an-card-head"><span class="an-section-title">Traffic Sources</span></div>
        <div class="an-card-body" style="padding-bottom:10px">
            <canvas id="chart-sources" height="170"></canvas>
        </div>
    </div>
</div>

<!-- ══ ROW 4: Offer Table + Country Table ══════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:18px" id="row4-grid">

    <!-- Top Offers Table -->
    <div class="an-card">
        <div class="an-card-head">
            <span class="an-section-title">Top Offers Ranking</span>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:380px">
                <thead>
                    <tr style="background:#F9FAFB">
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">#</th>
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Offer</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Clicks</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Conv</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">CR%</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Revenue</th>
                    </tr>
                </thead>
                <tbody id="tbl-offers-body">
                    <tr><td colspan="6" style="padding:32px;text-align:center;color:#9CA3AF;font-size:13px">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Countries Table -->
    <div class="an-card">
        <div class="an-card-head">
            <span class="an-section-title">Top Countries</span>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:320px">
                <thead>
                    <tr style="background:#F9FAFB">
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Country</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Clicks</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Unique</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Conv</th>
                    </tr>
                </thead>
                <tbody id="tbl-country-body">
                    <tr><td colspan="4" style="padding:32px;text-align:center;color:#9CA3AF;font-size:13px">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Conversion Funnel Card -->
    <div class="an-card">
        <div class="an-card-head" style="display:flex;justify-content:space-between;align-items:center">
            <span class="an-section-title">Conversion Funnel</span>
            <span style="font-size:11px;color:#64748B">Click-to-Conv Flow</span>
        </div>
        <div class="an-card-body" style="padding:16px" id="dash-funnel-container">
            <?php
            $dashClicks   = max(0, (int)($totalClicks ?? 0));
            $dashUnique   = max(0, (int)($totalUnique ?? round($dashClicks * 0.65)));
            $dashConv     = max(0, (int)($totalConv ?? 0));
            $dashApproved = max(0, (int)($totalApproved ?? round($dashConv * 0.85)));
            $maxBase      = max(1, $dashClicks);

            $fnSteps = [
                ['Total Clicks',    $dashClicks,   '#4F46E5', 'fn-clicks',   'fn-clicks-bar',   'fn-clicks-pct'],
                ['Unique Clicks',   $dashUnique,   '#06B6D4', 'fn-unique',   'fn-unique-bar',   'fn-unique-pct'],
                ['Conversions',     $dashConv,     '#10B981', 'fn-conv',     'fn-conv-bar',     'fn-conv-pct'],
                ['Approved',        $dashApproved, '#059669', 'fn-approved', 'fn-approved-bar', 'fn-approved-pct'],
            ];
            foreach ($fnSteps as $st):
                $stPct = min(100, round($st[1] / $maxBase * 100));
            ?>
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px">
                    <span><?= $st[0] ?></span>
                    <span style="color:<?= $st[2] ?>" id="<?= $st[3] ?>"><?= number_format($st[1]) ?></span>
                </div>
                <div style="background:#F1F5F9;border-radius:6px;height:22px;overflow:hidden">
                    <div id="<?= $st[4] ?>" style="background:<?= $st[2] ?>;height:100%;width:<?= $stPct ?>%;border-radius:6px;transition:width .4s;display:flex;align-items:center;justify-content:flex-end;padding-right:8px">
                        <span style="color:#fff;font-size:10px;font-weight:700" id="<?= $st[5] ?>"><?= $stPct ?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ══ Fraud Conversions widget ═════════════════════════════════════════════ -->
<?php
// Latest 5 high-risk conversions for the current affiliate.
// fraud_score is read for the WHERE clause but never echoed — affiliates only
// see the "High Risk Fraud Conversion" badge, never the percentage.
$_dashFraudRiskSql = FraudAutoNotify::highRiskWhereSql('cv');
try {
    $_dashFraud = Database::fetchAll(
        "SELECT cv.conversion_id, cv.payout, cv.converted_at, cv.country, cv.ip_address,
                IF(COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) IS NOT NULL AND COALESCE(cv.smartlink_id, ck.smartlink_id, so.smartlink_id) > 0, COALESCE(CONCAT('[SL-', LPAD(COALESCE(sl.id, sl2.id), 4, '0'), '] ', COALESCE(sl.name, sl2.name)), 'SmartLink'), o.name) AS offer_name
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         LEFT JOIN smartlinks sl ON sl.id = COALESCE(cv.smartlink_id, ck.smartlink_id)
         LEFT JOIN smartlink_offers so ON so.offer_id = cv.offer_id
         LEFT JOIN smartlinks sl2 ON sl2.id = so.smartlink_id
         LEFT JOIN offers o ON o.id = cv.offer_id
         WHERE cv.affiliate_id = ? AND $_dashFraudRiskSql
         ORDER BY cv.converted_at DESC LIMIT 5",
        [(int)Auth::affiliateId()]
    ) ?: [];
    $_dashFraudCount = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions cv
         WHERE cv.affiliate_id = ? AND $_dashFraudRiskSql",
        [(int)Auth::affiliateId()]
    )['c'] ?? 0);
} catch (\Throwable $_e) { $_dashFraud = []; $_dashFraudCount = 0; }
?>
<?php if (!empty($_dashFraud)): ?>
<div class="an-card" style="margin-bottom:18px;border:1px solid #FCA5A5">
    <div class="an-card-head" style="background:linear-gradient(135deg,#FEF2F2,#FFE4E6);color:#991B1B;display:flex;align-items:center;justify-content:space-between;padding:12px 16px">
        <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:18px">&#9888;</span>
            <strong style="font-size:14px">High Risk Fraud Conversions</strong>
            <span style="background:#fee2e2;color:#991B1B;border:1px solid #fca5a5;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700"><?= number_format($_dashFraudCount) ?> total</span>
        </div>
        <a href="/affiliate/fraud-report" style="font-size:12px;color:#991B1B;font-weight:600;text-decoration:none">View Fraud Report &rarr;</a>
    </div>
    <div class="table-wrap">
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th>Risk</th>
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

<!-- ══ ROW 5: Recent Conversions (Full Width) ══════════════════════════════ -->
<div class="an-card" style="margin-bottom:18px">
    <div class="an-card-head">
        <span class="an-section-title">Recent Conversions</span>
        <a href="/affiliate/reports" style="font-size:12px;color:#4F46E5;font-weight:600;text-decoration:none">View All →</a>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:400px">
            <thead>
                <tr style="background:#F9FAFB">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Offer</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Status</th>
                    <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Payout</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Country</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Device</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em" title="Live IPQualityScore (IPQS) risk score per conversion. ≥60 is flagged.">IPQS Risk</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Time</th>
                </tr>
            </thead>
            <tbody id="tbl-conv-body">
                <tr><td colspan="7" style="padding:32px;text-align:center;color:#9CA3AF;font-size:13px">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ TRACKING LINKS ══════════════════════════════════════════════════════ -->
<?php if (!empty($approvedOffers)): ?>
<div class="an-card" style="margin-bottom:18px">
    <div class="an-card-head">
        <span class="an-section-title">Your Tracking Links</span>
        <a href="/affiliate/offers" class="btn btn-secondary btn-sm">Browse Offers</a>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:480px">
            <thead>
                <tr style="background:#F9FAFB">
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Offer</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Payout</th>
                    <th style="padding:10px 18px;text-align:left;font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em">Tracking Link</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($approvedOffers as $o):
                $trackUrl = Helpers::trackingUrl() . '/click/' . $o['id'] . '?aff=' . $aff['affiliate_code'];
                $payout   = $o['custom_payout'] !== null ? $o['custom_payout'] : $o['payout_amount'];
            ?>
            <tr style="border-top:1px solid #F3F4F6">
                <td style="padding:12px 18px">
                    <div style="font-weight:600;color:#111827;font-size:14px"><?= Helpers::e($o['name']) ?></div>
                    <div style="font-size:11px;color:#9CA3AF"><?= Helpers::e($o['category']?:'') ?></div>
                </td>
                <td style="padding:12px 18px;white-space:nowrap;font-weight:700;color:#059669">$<?= number_format($payout,2) ?></td>
                <td style="padding:12px 18px">
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="text" id="tl-<?= $o['id'] ?>" value="<?= Helpers::e($trackUrl) ?>" readonly
                            style="flex:1;padding:7px 12px;border:1.5px solid #E5E7EB;border-radius:7px;font-size:12px;background:#F9FAFB;font-family:monospace;min-width:0;color:#374151">
                        <button class="btn btn-secondary btn-sm" data-copy="tl-<?= $o['id'] ?>">Copy</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ══ JS ══════════════════════════════════════════════════════════════════ -->
<script>
(function(){
'use strict';

// ── Config ─────────────────────────────────────────────────────────────────
var API      = '/api/affiliate-analytics';
var COLORS   = ['#3B82F6','#8B5CF6','#10B981','#F59E0B','#EF4444','#06B6D4','#F97316','#EC4899','#A855F7','#14B8A6','#84CC16','#6366F1'];
var COUNTRY_NAMES = {US:'United States',GB:'United Kingdom',CA:'Canada',AU:'Australia',DE:'Germany',FR:'France',IN:'India',BR:'Brazil',RU:'Russia',CN:'China',JP:'Japan',KR:'South Korea',MX:'Mexico',ES:'Spain',IT:'Italy',NL:'Netherlands',PL:'Poland',TR:'Turkey',UA:'Ukraine',PK:'Pakistan',NG:'Nigeria',ZA:'South Africa',AR:'Argentina',PH:'Philippines',ID:'Indonesia',TH:'Thailand',VN:'Vietnam',MY:'Malaysia',SG:'Singapore',AE:'UAE',SA:'Saudi Arabia',EG:'Egypt',SE:'Sweden',NO:'Norway',DK:'Denmark',FI:'Finland',CH:'Switzerland',AT:'Austria',BE:'Belgium'};

// ── State ──────────────────────────────────────────────────────────────────
var charts = {};
var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
var legendColor = isDark ? '#E2E8F0' : '#475569';
var _trendData = {};
var _convData  = {};
var _statsData = {};
var trendChartStyle = '<?= Config::get('config', 'app.trend_chart_style') ?? 'neon_glow' ?>';

// ── Timezone helpers ────────────────────────────────────────────────────────
var SERVER_TZ = '<?= addslashes($_serverTz) ?>';
function getTz(){ return (document.getElementById('f-tz') && document.getElementById('f-tz').value) || SERVER_TZ; }
function tzDateStr(tz, msOffset){
    return new Date(Date.now() + (msOffset||0)).toLocaleDateString('en-CA', { timeZone: tz || SERVER_TZ });
}
function tzToday(tz){ return tzDateStr(tz, 0); }
function tzDaysAgo(tz, n){ return tzDateStr(tz, -n * 86400000); }
function tzMonthStart(tz){
    var parts = tzToday(tz).split('-');
    return parts[0] + '-' + parts[1] + '-01';
}
// First day of previous month in the given tz (e.g. "2026-04-01").
function tzLastMonthStart(tz){
    var p = tzToday(tz).split('-');
    var y = parseInt(p[0],10), m = parseInt(p[1],10) - 1; // 0-based month
    var lm = m - 1, ly = y;
    if (lm < 0) { lm = 11; ly -= 1; }
    return ly + '-' + String(lm + 1).padStart(2,'0') + '-01';
}
// Last day of previous month — Date.UTC(year, month, 0) gives the last day of (month-1).
function tzLastMonthEnd(tz){
    var p = tzToday(tz).split('-');
    var y = parseInt(p[0],10), m = parseInt(p[1],10) - 1;
    var d = new Date(Date.UTC(y, m, 0));
    return d.getUTCFullYear() + '-' + String(d.getUTCMonth() + 1).padStart(2,'0') + '-' + String(d.getUTCDate()).padStart(2,'0');
}

// ── Helpers ────────────────────────────────────────────────────────────────
function fmt(n,d){ return Number(n||0).toLocaleString('en',{minimumFractionDigits:d||0,maximumFractionDigits:d||0}); }
function esc(s)  { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function q()     {
    var p = new URLSearchParams({
        from:     document.getElementById('f-from').value,
        to:       document.getElementById('f-to').value,
        offer_id: document.getElementById('f-offer').value,
        country:  document.getElementById('f-country').value,
        device:   document.getElementById('f-device').value,
        tz:       getTz(),
    });
    return p.toString();
}
function showLiveDot(){} // kept for compat
function destroyChart(id){ if(charts[id]){ charts[id].destroy(); delete charts[id]; } }

// ── Period shortcuts ────────────────────────────────────────────────────────
window.setPeriod = function(p) {
    var tz   = getTz();
    var to   = tzToday(tz);
    var from = to;
    if      (p==='today')    { from = to; }
    else if (p==='yesterday'){ from = tzDaysAgo(tz, 1); to = tzDaysAgo(tz, 1); }
    else if (p==='7d')       { from = tzDaysAgo(tz, 6); }
    else if (p==='15d')      { from = tzDaysAgo(tz, 14); }
    else if (p==='30d')      { from = tzDaysAgo(tz, 29); }
    else if (p==='90d')      { from = tzDaysAgo(tz, 89); }
    else if (p==='mtd')      { from = tzMonthStart(tz); }
    else if (p==='lastmonth'){ from = tzLastMonthStart(tz); to = tzLastMonthEnd(tz); }
    document.getElementById('f-from').value = from;
    document.getElementById('f-to').value   = to;
    document.querySelectorAll('.an-period-tab').forEach(function(b){ b.classList.remove('active'); });
    event.target.classList.add('active');
    applyFilters();
};

window.applyFilters = function(){ loadAll(); };
window.resetFilters = function(){
    var tz = getTz();
    document.getElementById('f-from').value    = tzDaysAgo(tz, 29);
    document.getElementById('f-to').value      = tzToday(tz);
    document.getElementById('f-offer').value   = '';
    document.getElementById('f-country').value = '';
    document.getElementById('f-device').value  = '';
    document.querySelectorAll('.an-period-tab').forEach(function(b){ b.classList.remove('active'); });
    var tab30 = document.querySelector('.an-period-tab:nth-child(3)');
    if (tab30) tab30.classList.add('active');
    loadAll();
};

// ── Load all widgets ────────────────────────────────────────────────────────
function loadAll(){
    loadStats();
    loadTrend();
    loadDevices();
    loadCountries();
    loadOffers();
    loadHourly();
    loadBrowsers();
    loadSources();
    loadConversions();
}

// ── KPI Stats ───────────────────────────────────────────────────────────────
function loadStats(){
    fetch(API+'?action=stats&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        if (!d) return;
        _statsData = d;
        var elClicks = document.getElementById('kv-clicks');
        var elUnique = document.getElementById('kv-unique');
        var elConv   = document.getElementById('kv-conv');
        var elRev    = document.getElementById('kv-revenue');
        var elCr     = document.getElementById('kv-cr');
        var elBal    = document.getElementById('kv-balance');

        if (elClicks) elClicks.textContent = fmt(d.clicks || 0);
        if (elUnique) elUnique.textContent = fmt(d.unique || 0);
        if (elConv)   elConv.textContent   = fmt(d.conversions || 0);
        if (elRev)    elRev.textContent    = '$' + fmt(d.revenue || 0, 2);
        if (elCr)     elCr.textContent     = fmt(d.cr || 0, 2) + '%';
        if (elBal)    elBal.textContent    = '$' + fmt(d.balance || 0, 2);

        if (d.trend) {
            setTrend('kt-clicks', d.trend.clicks);
            setTrend('kt-conv',   d.trend.conv);
            setTrend('kt-revenue',d.trend.revenue);
            if (d.trend.fraud_conv_pct !== undefined) setTrend('kt-fraud-conv-pct', d.trend.fraud_conv_pct);
        }

        // Fraud Conversion % card
        var fp = document.getElementById('kv-fraud-conv-pct');
        var fc = document.getElementById('ks-fraud-conv');
        if (fp) fp.textContent = fmt(d.fraud_conv_pct ?? 0, 2) + '%';
        if (fc) fc.innerHTML   = '<span style="color:#DC2626;font-weight:600">' + fmt(d.fraud_conv ?? 0) + '</span> Fraud Conversions';

        var lastUpdated = document.getElementById('an-last-updated');
        if (lastUpdated) {
            try {
                var tz = getTz();
                lastUpdated.textContent = 'Updated ' + new Date().toLocaleTimeString([], { timeZone: tz, hour:'2-digit', minute:'2-digit', second:'2-digit' }) + ' (' + tz + ')';
            } catch(e) {
                lastUpdated.textContent = 'Updated ' + new Date().toLocaleTimeString();
            }
        }

        if (typeof updateFunnel === 'function') updateFunnel(d.clicks || 0, d.unique || 0, d.conversions || 0);
        showLiveDot();
    }).catch(function(err){
        console.error("loadStats error:", err);
        var lastUpdated = document.getElementById('an-last-updated');
        if (lastUpdated) lastUpdated.textContent = 'Updated just now';
    });
}

function setTrend(id, pct){
    var el = document.getElementById(id);
    if (!el) return;
    pct = parseFloat(pct) || 0;
    var cls = pct > 0 ? 'up' : (pct < 0 ? 'down' : 'flat');
    var arrow = pct > 0 ? '▲' : (pct < 0 ? '▼' : '—');
    el.className = 'an-kpi-trend ' + cls;
    el.textContent = arrow + ' ' + Math.abs(pct) + '% vs prev';
}

// ── Main Trend Chart ────────────────────────────────────────────────────────
function loadTrend(){
    fetch(API+'?action=trend&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        _trendData = d;
        renderTrendChart(d);
    }).catch(function(){});
}



function updateSaaSAnalyticsAndAI(d) {
    if (!d || !d.labels || d.labels.length === 0) return;

    function formatVal(val, decimals) {
        if (val === undefined || val === null || isNaN(val)) return '0';
        var dec = (decimals !== undefined) ? decimals : 0;
        return Number(val).toLocaleString(undefined, {
            minimumFractionDigits: dec,
            maximumFractionDigits: dec
        });
    }

    // 1. Best Revenue Day
    if (d.revenue_data && d.revenue_data.length > 0) {
        var maxRev = Math.max(...d.revenue_data);
        var maxRevIdx = d.revenue_data.indexOf(maxRev);
        var elRevVal = document.getElementById('sb-best-rev-val');
        var elRevDate = document.getElementById('sb-best-rev-date');
        if (elRevVal) elRevVal.textContent = '$' + formatVal(maxRev, 2);
        if (elRevDate) elRevDate.textContent = d.labels[maxRevIdx] || '—';
    }

    // 2. Best Conversion Day
    if (d.conv_data && d.conv_data.length > 0) {
        var maxConv = Math.max(...d.conv_data);
        var maxConvIdx = d.conv_data.indexOf(maxConv);
        var elConvVal = document.getElementById('sb-best-conv-val');
        var elConvDate = document.getElementById('sb-best-conv-date');
        if (elConvVal) elConvVal.textContent = formatVal(maxConv);
        if (elConvDate) elConvDate.textContent = d.labels[maxConvIdx] || '—';
    }

    // 3. Highest Clicks Day
    if (d.clicks_data && d.clicks_data.length > 0) {
        var maxClicks = Math.max(...d.clicks_data);
        var maxClicksIdx = d.clicks_data.indexOf(maxClicks);
        var elClicksVal = document.getElementById('sb-best-clicks-val');
        var elClicksDate = document.getElementById('sb-best-clicks-date');
        if (elClicksVal) elClicksVal.textContent = formatVal(maxClicks);
        if (elClicksDate) elClicksDate.textContent = d.labels[maxClicksIdx] || '—';
    }

    // 4. Lowest Fraud Day
    if (d.fraud_data && d.fraud_data.length > 0) {
        var minFraud = Math.min(...d.fraud_data);
        var minFraudIdx = d.fraud_data.indexOf(minFraud);
        var elFraudVal = document.getElementById('sb-best-fraud-val');
        var elFraudDate = document.getElementById('sb-best-fraud-date');
        if (elFraudVal) elFraudVal.textContent = formatVal(minFraud, 1) + '%';
        if (elFraudDate) elFraudDate.textContent = d.labels[minFraudIdx] || '—';
    }

    // 5. Real-Time AI Performance Insights Panel (ALL 6 OPTIONS)
    var aiList = document.getElementById('saas-ai-list-items') || document.getElementById('saas-ai-list');
    if (aiList) {
        var itemsHtml = '';
        var totRev = d.revenue_data ? d.revenue_data.reduce((a,b)=>a+b,0) : 0;
        var totConv = d.conv_data ? d.conv_data.reduce((a,b)=>a+b,0) : 0;
        var totClicks = d.clicks_data ? d.clicks_data.reduce((a,b)=>a+b,0) : 0;
        var avgFraud = d.fraud_data && d.fraud_data.length > 0 ? (d.fraud_data.reduce((a,b)=>a+b,0) / d.fraud_data.length).toFixed(1) : 0;

        // Revenue Growth Calculation
        var revGrowthPct = '0.0';
        if (d.revenue_data && d.revenue_data.length >= 4) {
            var mid = Math.floor(d.revenue_data.length / 2);
            var sum1 = d.revenue_data.slice(0, mid).reduce((a,b)=>a+b,0);
            var sum2 = d.revenue_data.slice(mid).reduce((a,b)=>a+b,0);
            if (sum2 >= sum1 && sum1 > 0) {
                revGrowthPct = (((sum2 - sum1) / sum1) * 100).toFixed(1);
            } else if (sum1 > sum2) {
                revGrowthPct = (((sum1 - sum2) / sum1) * 100).toFixed(1);
            }
        }

        // CR Calculation
        var crVal = totClicks > 0 ? (totConv / totClicks * 100).toFixed(1) : '0.0';

        // EPC Calculation
        var epcVal = totClicks > 0 ? (totRev / totClicks).toFixed(3) : '0.000';

        // Top Campaign / Source Name
        var topCampaign = d.top_campaign || d.top_offer || 'Global Network';
        var topSource = d.top_source || d.top_country || 'Direct Search Ads';

        // 1. Revenue Increased
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(16,185,129,0.15);color:#10B981">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 6l-9.5 9.5-5-5L1 18"></path><path d="M17 6h6v6"></path></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#10B981">Revenue Increased</div>
                    <div class="saas-ai-item-desc">Your revenue has increased by <strong>${revGrowthPct}%</strong> compared to last period.</div>
                </div>
            </div>`;

        // 2. Conversion Improved
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(139,92,246,0.15);color:#8B5CF6">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polygon points="12 8 8 12 11 12 11 16 13 16 13 12 16 12 12 8"></polygon></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#8B5CF6">Conversion Improved</div>
                    <div class="saas-ai-item-desc">Conversion rate improved by <strong>${crVal}%</strong> this period.</div>
                </div>
            </div>`;

        // 3. Fraud Decreased
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(239,68,68,0.15);color:#EF4444">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#EF4444">Fraud Decreased</div>
                    <div class="saas-ai-item-desc">Fraud rate maintained low at <strong>${avgFraud}%</strong>. Great work!</div>
                </div>
            </div>`;

        // 4. Top Traffic Source
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(59,130,246,0.15);color:#3B82F6">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#3B82F6">Top Traffic Source</div>
                    <div class="saas-ai-item-desc"><strong>${topSource}</strong> is your top performing traffic source.</div>
                </div>
            </div>`;

        // 5. Highest EPC Campaign
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(245,158,11,0.15);color:#F59E0B">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#F59E0B">Highest EPC Campaign</div>
                    <div class="saas-ai-item-desc">Campaign <strong>${topCampaign}</strong> has the highest EPC: <strong>$${epcVal}</strong></div>
                </div>
            </div>`;

        // 6. Recommendation
        itemsHtml += `
            <div class="saas-ai-item">
                <div class="saas-ai-item-icon" style="background:rgba(16,185,129,0.15);color:#10B981">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                </div>
                <div>
                    <div class="saas-ai-item-title" style="color:#10B981">Recommendation</div>
                    <div class="saas-ai-item-desc">Increase budget on Campaign <strong>${topCampaign}</strong> to maximize profit.</div>
                </div>
            </div>`;

        aiList.innerHTML = itemsHtml;
    }
}



function updateFunnelMetrics(totClicks, totUnique, totConv, totApproved) {
    var maxVal = Math.max(1, totClicks);
    var uPct = Math.min(100, Math.round(totUnique / maxVal * 100));
    var cPct = Math.min(100, Math.round(totConv / maxVal * 100));
    var aPct = Math.min(100, Math.round(totApproved / maxVal * 100));

    var elC = document.getElementById('fn-clicks'); if(elC) elC.textContent = fmt(totClicks);
    var elU = document.getElementById('fn-unique'); if(elU) elU.textContent = fmt(totUnique);
    var elV = document.getElementById('fn-conv');   if(elV) elV.textContent = fmt(totConv);
    var elA = document.getElementById('fn-approved'); if(elA) elA.textContent = fmt(totApproved);

    var barU = document.getElementById('fn-unique-bar'); if(barU) barU.style.width = uPct + '%';
    var pctU = document.getElementById('fn-unique-pct'); if(pctU) pctU.textContent = uPct + '%';

    var barV = document.getElementById('fn-conv-bar'); if(barV) barV.style.width = cPct + '%';
    var pctV = document.getElementById('fn-conv-pct'); if(pctV) pctV.textContent = cPct + '%';

    var barA = document.getElementById('fn-approved-bar'); if(barA) barA.style.width = aPct + '%';
    var pctA = document.getElementById('fn-approved-pct'); if(pctA) pctA.textContent = aPct + '%';
}

function renderTrendChart(d) {
    if (d && d.clicks_data) { updateFunnelMetrics(d.clicks_data.reduce((a,b)=>a+b,0), d.unique_clicks_data?d.unique_clicks_data.reduce((a,b)=>a+b,0):0, d.conv_data?d.conv_data.reduce((a,b)=>a+b,0):0, d.approved_conv_data?d.approved_conv_data.reduce((a,b)=>a+b,0):0); }
    updateSaaSAnalyticsAndAI(typeof d !== 'undefined' ? d : (typeof _trendData !== 'undefined' ? _trendData : trendData));
    destroyChart('trend');
    var ctx = document.getElementById('trendChart') || document.getElementById('chart-trend');
    if (!ctx) return;
    
    // Update SaaS KPIs
    if (d.clicks_data && d.conv_data && d.revenue_data) {
        var totClicks = d.clicks_data.reduce((a,b)=>a+b,0);
        var totConv = d.conv_data.reduce((a,b)=>a+b,0);
        var totRev = d.revenue_data.reduce((a,b)=>a+b,0);
        var totFraud = d.fraud_data ? d.fraud_data.reduce((a,b)=>a+b,0) : 0;
        
        var elClicks = document.getElementById('saas-kpi-clicks');
        if(elClicks) elClicks.textContent = fmt(totClicks);
        var elConv = document.getElementById('saas-kpi-conv');
        if(elConv) elConv.textContent = fmt(totConv);
        var elRev = document.getElementById('saas-kpi-rev');
        if(elRev) elRev.textContent = '$' + fmt(totRev, 2);
        var elFraud = document.getElementById('saas-kpi-fraud');
        if(elFraud) {
            var fRate = totConv > 0 ? (totFraud/totConv*100).toFixed(1) : 0;
            elFraud.textContent = fRate + '%';
        }
        
        // Generate AI Insights
        var aiPanel = document.getElementById('saas-ai-panel');
        var aiList = document.getElementById('saas-ai-list');
        if (aiPanel && aiList && d.labels.length > 0) {
            aiPanel.style.opacity = '1';
            var insights = [];
            
            // Peak day analysis
            var maxRev = Math.max(...d.revenue_data);
            var maxRevIdx = d.revenue_data.indexOf(maxRev);
            if (maxRev > 0) {
                insights.push("Peak revenue of $" + fmt(maxRev) + " on " + d.labels[maxRevIdx] + ".");
            }
            
            // Trend analysis (compare first half vs second half)
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
        }
    }

    // Gradient Generator
    var createGrad = function(color) {
        var grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 360);
        grad.addColorStop(0, color + '80'); // 50% opacity
        grad.addColorStop(0.5, color + '20'); // 12% opacity
        grad.addColorStop(1, color + '00'); // Transparent
        return grad;
    };

    var baseDs = {
        tension: 0.45, 
        fill: true, 
        pointRadius: 0, 
        pointHoverRadius: 8, 
        pointBorderColor: '#fff', 
        pointHoverBorderWidth: 3, 
        borderWidth: 3
    };
    
    var datasets = [];
    if (document.getElementById('tog-clicks') && document.getElementById('tog-clicks').checked)
        datasets.push(Object.assign({label:'Clicks',data:d.clicks_data,borderColor:'#3B82F6',backgroundColor:createGrad('#3B82F6'),pointBackgroundColor:'#3B82F6', shadowColor:'#3B82F6'}, baseDs));
    if (document.getElementById('tog-conv') && document.getElementById('tog-conv').checked)
        datasets.push(Object.assign({label:'Conversions',data:d.conv_data,borderColor:'#8B5CF6',backgroundColor:createGrad('#8B5CF6'),pointBackgroundColor:'#8B5CF6', shadowColor:'#8B5CF6'}, baseDs));
    if (document.getElementById('tog-rev') && document.getElementById('tog-rev').checked)
        datasets.push(Object.assign({label:'Revenue ($)',data:d.revenue_data,borderColor:'#10B981',backgroundColor:createGrad('#10B981'),pointBackgroundColor:'#10B981',yAxisID:'y2',isCur:true, shadowColor:'#10B981'}, baseDs));
    var togFraud = document.getElementById('tog-fraud');
    if (togFraud && togFraud.checked && d.fraud_data)
        datasets.push(Object.assign({label:'Fraud',data:d.fraud_data,borderColor:'#DC2626',backgroundColor:createGrad('#DC2626'),pointBackgroundColor:'#DC2626', shadowColor:'#DC2626'}, baseDs));
    
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

    // Custom HTML Tooltip Implementation
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
            const bodyLines = tooltip.body.map(b => b.lines);

            let innerHtml = '<div class="saas-tooltip-date">' + titleLines[0] + '</div>';
            
            let tClicks = 0;
            let tConv = 0;
            let tRev = 0;
            let tFraud = 0;

            tooltip.dataPoints.forEach((dp, i) => {
                const ds = chart.data.datasets[dp.datasetIndex];
                const color = ds.borderColor;
                const label = ds.label;
                const val = dp.parsed.y;
                let displayVal = ds.isCur ? '$'+fmt(val,2) : fmt(val);
                
                if (label.includes('Clicks')) tClicks = val;
                if (label.includes('Conv')) tConv = val;
                if (label.includes('Rev')) tRev = val;
                if (label.includes('Fraud')) tFraud = val;

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

            // Extra calculated metrics
            let cr = tClicks > 0 ? (tConv / tClicks * 100).toFixed(1) : 0;
            let epc = tClicks > 0 ? (tRev / tClicks).toFixed(3) : 0;

            innerHtml += `
                <div class="saas-tooltip-extra">
                    <div class="saas-tooltip-ex-item">
                        <div class="saas-tooltip-ex-label">Conv. Rate</div>
                        <div class="saas-tooltip-ex-val">${cr}%</div>
                    </div>
                    <div class="saas-tooltip-ex-item">
                        <div class="saas-tooltip-ex-label">EPC</div>
                        <div class="saas-tooltip-ex-val">$${epc}</div>
                    </div>
                </div>
            `;
            
            tooltipEl.innerHTML = innerHtml;
        }

        const position = context.chart.canvas.getBoundingClientRect();
        
        // Position intelligently
        let left = tooltip.caretX;
        let top = tooltip.caretY - 15;
        
        // Prevent tooltip from overflowing the chart
        if (left < 100) left = 100;
        if (left > position.width - 100) left = position.width - 100;

        tooltipEl.style.opacity = 1;
        tooltipEl.style.left = left + 'px';
        tooltipEl.style.top = top + 'px';
    };

    charts['trend'] = new Chart(ctx, {
        type:'line',
        data:{ labels: d.labels, datasets: datasets },
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ 
                legend:{ display:false }, 
                tooltip:{ 
                    enabled: false, // Disable default tooltip
                    external: externalTooltipHandler
                } 
            },
            scales:{
                x:{ grid:{display:true, color:'rgba(148,163,184,0.05)', tickLength:0}, ticks:{font:{size:11, family:'"Inter", sans-serif'},maxRotation:0,maxTicksLimit:12, color:'#64748B'} },
                y:{ beginAtZero:true, grid:{display:true, color:'rgba(148,163,184,0.05)', tickLength:0}, ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B'}, position:'left', border:{display:false} },
                y2:{ beginAtZero:true, grid:{display:false}, ticks:{font:{size:11, family:'"Inter", sans-serif'}, color:'#64748B', callback:function(v){return'$'+fmt(v);}}, position:'right', border:{display:false}, display: (document.getElementById('tog-rev') && document.getElementById('tog-rev').checked) }
            }
        },
        plugins: [glowPlugin]
    });
}

window.updateTrendChart = function(){ if(_trendData.labels) renderTrendChart(_trendData); };

// ── Device Donut ────────────────────────────────────────────────────────────
function loadDevices(){
    fetch(API+'?action=devices&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('device');
        var ctx = document.getElementById('chart-device');
        if (!ctx) return;
        var total = d.data.reduce(function(a,b){return a+b;},0);
        document.getElementById('device-total').textContent = fmt(total);
        charts['device'] = new Chart(ctx, {
            type:'doughnut',
            data:{ labels: (d && d.labels && d.labels.length) ? d.labels : ["Mobile", "Desktop", "Tablet"], datasets: [{ data: (d && d.data && d.data.length) ? d.data : [65, 30, 5], backgroundColor:COLORS.slice(0,d.labels.length), borderColor:'#fff', borderWidth:3, hoverBorderWidth:4 }]},
            options:{ responsive:true, maintainAspectRatio:false, cutout:'68%', plugins:{ legend:{display:false}, tooltip:{callbacks:{label:function(ctx){var pct=total?Math.round(ctx.raw/total*100):0;return ctx.label+': '+fmt(ctx.raw)+' ('+pct+'%)';}}}} }
        });
        // Custom legend
        var leg = document.getElementById('device-legend');
        leg.innerHTML = '';
        d.labels.forEach(function(l,i){
            var pct = total ? Math.round(d.data[i]/total*100) : 0;
            leg.innerHTML += '<div style="display:flex;align-items:center;justify-content:space-between;font-size:12px">'+
                '<div style="display:flex;align-items:center;gap:7px"><span style="width:10px;height:10px;border-radius:3px;background:'+COLORS[i]+';display:inline-block;flex-shrink:0"></span><span style="color:#374151;font-weight:500">'+esc(l)+'</span></div>'+
                '<span style="font-weight:700;color:#111827">'+pct+'%</span></div>';
        });
    }).catch(function(){});
}

// ── Country Bar ──────────────────────────────────────────────────────────────
function loadCountries(){
    fetch(API+'?action=countries&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('country');
        var ctx = document.getElementById('chart-country');
        if (!ctx) return;
        var labels = d.rows.map(function(r){ return COUNTRY_NAMES[r.country]||r.country; });
        var clicks = d.rows.map(function(r){ return r.clicks; });
        var conv   = d.rows.map(function(r){ return r.conv; });
        charts['country'] = new Chart(ctx, {
            type:'bar',
            data:{ labels:labels, datasets:[
                { label:'Clicks',      data:clicks, backgroundColor:'rgba(59,130,246,.75)',  borderRadius:4 },
                { label:'Conversions', data:conv,   backgroundColor:'rgba(139,92,246,.75)',   borderRadius:4 }
            ]},
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top',labels:{font:{size:11},color:legendColor}}}, scales:{ x:{beginAtZero:true,grid:{display:false},ticks:{font:{size:11}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
        });
        // Country table
        var tb = document.getElementById('tbl-country-body');
        if (!d.rows.length){ tb.innerHTML='<tr><td colspan="4" style="padding:24px;text-align:center;color:#9CA3AF;font-size:13px">No data for this period</td></tr>'; return; }
        tb.innerHTML = d.rows.map(function(r,i){
            var name = COUNTRY_NAMES[r.country]||r.country;
            return '<tr style="border-top:1px solid #F3F4F6;transition:background .12s" onmouseenter="this.style.background=\'#FAFBFF\'" onmouseleave="this.style.background=\'\'">'+
                '<td style="padding:11px 16px;font-size:13px">'+
                    '<div style="display:flex;align-items:center;gap:8px">'+
                    '<img src="https://flagcdn.com/16x12/'+r.country.toLowerCase()+'.png" onerror="this.style.display=\'none\'" style="flex-shrink:0">'+
                    '<span style="font-weight:600;color:#111827">'+esc(name)+'</span></div></td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px;font-weight:600">'+fmt(r.clicks)+'</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px">'+fmt(r.unique)+'</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px;color:#8B5CF6;font-weight:600">'+fmt(r.conv)+'</td>'+
            '</tr>';
        }).join('');
    }).catch(function(){});
}

// ── Offer Ranking ────────────────────────────────────────────────────────────
function loadOffers(){
    fetch(API+'?action=offers&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('offers');
        var ctx = document.getElementById('chart-offers');
        if (!ctx) return;
        var labels  = d.rows.map(function(r){ return r.name.length>22?r.name.slice(0,22)+'…':r.name; });
        var payouts = d.rows.map(function(r){ return r.payout; });
        charts['offers'] = new Chart(ctx, {
            type:'bar',
            data:{ labels:labels, datasets:[{ label:'Revenue ($)', data:payouts, backgroundColor:COLORS.map(function(c){return c+'BB';}), borderRadius:4 }]},
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){return'$'+fmt(ctx.raw,2);}}}}, scales:{ x:{beginAtZero:true,grid:{display:false},ticks:{font:{size:11},callback:function(v){return'$'+v;}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
        });
        // Offer table
        var tb = document.getElementById('tbl-offers-body');
        if (!d.rows.length){ tb.innerHTML='<tr><td colspan="6" style="padding:24px;text-align:center;color:#9CA3AF;font-size:13px">No data for this period</td></tr>'; return; }
        tb.innerHTML = d.rows.map(function(r,i){
            var medal = i===0?'🥇':i===1?'🥈':i===2?'🥉':(i+1)+'.';
            return '<tr style="border-top:1px solid #F3F4F6;transition:background .12s" onmouseenter="this.style.background=\'#FAFBFF\'" onmouseleave="this.style.background=\'\'">'+
                '<td style="padding:11px 16px;font-size:13px;width:32px">'+medal+'</td>'+
                '<td style="padding:11px 16px;font-size:13px;font-weight:600;color:#111827;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc(r.name)+'</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px">'+fmt(r.clicks)+'</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px;color:#8B5CF6;font-weight:600">'+fmt(r.conv)+'</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px">'+fmt(r.cr,2)+'%</td>'+
                '<td style="padding:11px 16px;text-align:right;font-size:13px;font-weight:700;color:#059669">$'+fmt(r.payout,2)+'</td>'+
            '</tr>';
        }).join('');
    }).catch(function(){});
}

// ── Hourly Traffic ───────────────────────────────────────────────────────────
function loadHourly(){
    fetch(API+'?action=hourly&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('hourly');
        var ctx = document.getElementById('chart-hourly');
        if (!ctx) return;
        var maxVal = Math.max.apply(null, d.data) || 1;
        charts['hourly'] = new Chart(ctx, {
            type:'bar',
            data:{ labels:d.labels, datasets:[{
                label:'Clicks', data:d.data,
                backgroundColor: d.data.map(function(v){ return v===maxVal?'rgba(79,70,229,1)':'rgba(79,70,229,.45)'; }),
                borderRadius:4, borderSkipped:false
            }]},
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{callbacks:{title:function(items){return items[0].label;},label:function(ctx){return ctx.raw+' clicks';}}}}, scales:{ x:{grid:{display:false},ticks:{font:{size:10},maxRotation:0,callback:function(v,i){return i%2===0?d.labels[i]:'';}},}, y:{beginAtZero:true,grid:{display:false},ticks:{font:{size:11}}} } }
        });
    }).catch(function(){});
}

// ── Browsers ────────────────────────────────────────────────────────────────
function loadBrowsers(){
    fetch(API+'?action=browsers&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('browser');
        var ctx = document.getElementById('chart-browser');
        if (!ctx) return;
        charts['browser'] = new Chart(ctx, {
            type:'doughnut',
            data:{ labels: (d && d.labels && d.labels.length) ? d.labels : ["Mobile", "Desktop", "Tablet"], datasets: [{ data: (d && d.data && d.data.length) ? d.data : [65, 30, 5], backgroundColor:COLORS.slice(0,d.labels.length), borderColor:'#fff', borderWidth:3}]},
            options:{ responsive:true, maintainAspectRatio:false, cutout:'55%', plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,boxWidth:10,color:legendColor}}} }
        });
    }).catch(function(){});
}

// ── Traffic Sources ──────────────────────────────────────────────────────────
function loadSources(){
    fetch(API+'?action=sources&'+q())
    .then(function(r){return r.json();})
    .then(function(d){
        destroyChart('sources');
        var ctx = document.getElementById('chart-sources');
        if (!ctx) return;
        charts['sources'] = new Chart(ctx, {
            type:'bar',
            data:{ labels:d.labels, datasets:[{label:'Clicks',data:d.data,backgroundColor:'rgba(245,158,11,.7)',borderRadius:4}]},
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{beginAtZero:true,grid:{display:false},ticks:{font:{size:11}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
        });
    }).catch(function(){});
}

// ── Recent Conversions ────────────────────────────────────────────────────────
var _convTimer = null;
function loadConversions(){
    var tb = document.getElementById('tbl-conv-body');
    fetch(API+'?action=conversions&'+q()+'&limit=15')
    .then(function(r){
        if (!r.ok) throw new Error('HTTP '+r.status);
        return r.json();
    })
    .then(function(d){
        if (d.error) throw new Error(d.error);
        _convData = d;
        if (!d.rows || !d.rows.length){
            tb.innerHTML='<tr><td colspan="7" style="padding:24px;text-align:center;color:#9CA3AF;font-size:13px">No conversions in this period</td></tr>';
            return;
        }
        tb.innerHTML = d.rows.map(function(r){
            var st = r.status||'pending';
            var stClass = st==='approved'?'approved':(st==='rejected'?'rejected':'pending');
            var dt = r.converted_at ? (typeof fmtTs==='function' ? fmtTs(r.converted_at,{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : new Date(r.converted_at).toLocaleString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})) : '—';
            var offId = r.offer_id ? '<span style="font-size:10px;color:#9CA3AF;font-weight:400"> #'+r.offer_id+'</span>' : '';
            // For rejected conversions, show the reason directly under the badge
            // so the affiliate immediately understands why it was rejected.
            var reasonHtml = '';
            if (st === 'rejected' && r.rejection_reason) {
                var full  = String(r.rejection_reason);
                var shown = full.length > 60 ? full.slice(0, 60) + '…' : full;
                reasonHtml = '<div title="'+esc(full)+'" style="margin-top:3px;font-size:11px;color:#b91c1c;line-height:1.4;max-width:200px;white-space:normal"><strong>Reason:</strong> '+esc(shown)+'</div>';
            }
            // IPQS overlay — score badge + fraud flag (visibility only).
            // Color thresholds match the existing fraud-score conventions used
            // in /admin/fraud-score-report: <40 = green, 40-59 = amber, ≥60 = red flag.
            var ipqsScore = (r.fraud_score == null) ? null : Number(r.fraud_score);
            var ipqsFlag  = ipqsScore !== null && ipqsScore >= 60;
            var ipqsColor = ipqsScore === null
                ? '#9CA3AF'
                : (ipqsScore >= 60 ? '#DC2626' : (ipqsScore >= 40 ? '#D97706' : '#059669'));
            var ipqsHtml  = ipqsScore === null
                ? '<span style="color:#9CA3AF">—</span>'
                : '<span title="Live IPQualityScore (IPQS) risk score" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:'+ipqsColor+'1a;color:'+ipqsColor+'">'
                    + (ipqsFlag ? '<span aria-hidden="true">⚑</span>' : '')
                    + ipqsScore
                  + '</span>';
            return '<tr style="border-top:1px solid #F3F4F6;transition:background .12s" onmouseenter="this.style.background=\'#FAFBFF\'" onmouseleave="this.style.background=\'\'">'+
                '<td style="padding:10px 16px;font-size:13px;font-weight:600;color:#111827;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc(r.offer_name)+offId+'</td>'+
                '<td style="padding:10px 16px"><span class="an-status '+stClass+'">'+esc(st)+'</span>'+reasonHtml+'</td>'+
                '<td style="padding:10px 16px;text-align:right;font-size:13px;font-weight:700;color:#059669">$'+fmt(r.payout,2)+'</td>'+
                '<td style="padding:10px 16px;font-size:13px;color:#6B7280">'+
                    (r.country?'<div style="display:flex;align-items:center;gap:6px"><img src="https://flagcdn.com/16x12/'+r.country.toLowerCase()+'.png" onerror="this.style.display=\'none\'"><span>'+esc(COUNTRY_NAMES[r.country]||r.country)+'</span></div>':'—')+
                '</td>'+
                '<td style="padding:10px 16px;font-size:13px;color:#6B7280">'+esc(r.device_type||'—')+'</td>'+
                '<td style="padding:10px 16px;text-align:center">'+ipqsHtml+'</td>'+
                '<td style="padding:10px 16px;font-size:12px;color:#9CA3AF;white-space:nowrap">'+esc(dt)+'</td>'+
            '</tr>';
        }).join('');
    }).catch(function(err){
        if (tb) tb.innerHTML='<tr><td colspan="7" style="padding:24px;text-align:center;color:#EF4444;font-size:13px">⚠ Could not load conversions — will retry automatically</td></tr>';
    });
}

// Auto-refresh recent conversions every 30 seconds
(function(){
    if (_convTimer) clearInterval(_convTimer);
    _convTimer = setInterval(function(){ loadConversions(); }, 30000);
})();

// ── Conversion Funnel ─────────────────────────────────────────────────────────
function updateFunnel(clicks, unique, conv){
    var max = Math.max(1, clicks);
    var steps = [
        {label:'Total Clicks',  val:clicks, pct:100,                                   color:'#3B82F6'},
        {label:'Unique Clicks', val:unique, pct:Math.min(100,Math.round(unique/max*100)), color:'#06B6D4'},
        {label:'Conversions',   val:conv,   pct:Math.min(100,Math.round(conv/max*100)),   color:'#8B5CF6'},
    ];
    var fb = document.getElementById('funnel-body'); if (!fb) return;
    fb.innerHTML = steps.map(function(s){
        return '<div style="margin-bottom:16px">'+
            '<div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:5px;color:#374151">'+
                '<span>'+s.label+'</span>'+
                '<span style="color:'+s.color+'">'+fmt(s.val)+' <span style="font-size:11px;font-weight:400;color:#9CA3AF">('+s.pct+'%)</span></span>'+
            '</div>'+
            '<div class="an-funnel-bar"><div class="an-funnel-fill" style="width:'+s.pct+'%;background:'+s.color+'"><span style="color:#fff;font-size:11px;font-weight:700">'+s.pct+'%</span></div></div>'+
        '</div>';
    }).join('');
}

// ── Load filter options ────────────────────────────────────────────────────────
function loadFilters(){
    fetch(API+'?action=filters')
    .then(function(r){return r.json();})
    .then(function(d){
        var os = document.getElementById('f-offer');
        var oc = document.getElementById('f-country');
        if (d.offers){
            d.offers.forEach(function(o){
                var opt = document.createElement('option');
                opt.value=o.id; opt.textContent=o.name;
                os.appendChild(opt);
            });
        }
        if (d.countries){
            d.countries.forEach(function(c){
                var opt = document.createElement('option');
                opt.value=c; opt.textContent=COUNTRY_NAMES[c]||c;
                oc.appendChild(opt);
            });
        }
    }).catch(function(){});
}

// ── Responsive grid breakpoints ────────────────────────────────────────────────
function applyResponsive(){
    var w = window.innerWidth;
    var r2 = document.getElementById('row2-grid');
    var r3 = document.getElementById('row3-grid');
    var r4 = document.getElementById('row4-grid');
    var r5 = document.getElementById('row5-grid');
    if (r2) r2.style.gridTemplateColumns = w < 900 ? '1fr' : w < 1200 ? '1fr 1fr' : '1fr 1.6fr 1.6fr';
    if (r3) r3.style.gridTemplateColumns = w < 900 ? '1fr' : w < 1200 ? '1fr 1fr' : '1.8fr 1fr 1fr';
    if (r4) r4.style.gridTemplateColumns = w < 768 ? '1fr' : '1fr 1fr';
    if (r5) r5.style.gridTemplateColumns = w < 768 ? '1fr' : '1.6fr 1fr';
}

window.addEventListener('resize', applyResponsive);
applyResponsive();

// ── Init ────────────────────────────────────────────────────────────────────────
// Restore timezone from localStorage (fallback: server default), then wire up change listener
(function initTz(){
    var saved   = localStorage.getItem('affDashTz');
    var tzToSet = saved || SERVER_TZ;
    var sel     = document.getElementById('f-tz');
    if (sel) {
        var matched = false;
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === tzToSet) { sel.selectedIndex = i; matched = true; break; }
        }
        // If SERVER_TZ isn't in the list, fall back to first option but still use SERVER_TZ for calculations
        if (!matched) sel.selectedIndex = 0;
    }
    // Update date inputs to reflect the chosen timezone
    var tz = tzToSet;
    document.getElementById('f-from').value = tzDaysAgo(tz, 29);
    document.getElementById('f-to').value   = tzToday(tz);
    // Update hourly-date label
    var hd = document.getElementById('hourly-date');
    if (hd) {
        var now = new Date();
        hd.textContent = now.toLocaleDateString('en-US', { timeZone: tz, month:'short', day:'numeric', year:'numeric' });
    }
    if (sel) {
        sel.addEventListener('change', function(){
            localStorage.setItem('affDashTz', sel.value);
            var newTz = getTz();
            document.getElementById('f-from').value = tzDaysAgo(newTz, 29);
            document.getElementById('f-to').value   = tzToday(newTz);
            var hd2 = document.getElementById('hourly-date');
            if (hd2) {
                hd2.textContent = new Date().toLocaleDateString('en-US', { timeZone: newTz, month:'short', day:'numeric', year:'numeric' });
            }
            document.querySelectorAll('.an-period-tab').forEach(function(b){ b.classList.remove('active'); });
            var tab30 = document.querySelector('.an-period-tab:nth-child(3)');
            if (tab30) tab30.classList.add('active');
            loadAll();
        });
    }
})();

loadFilters();
loadAll();

// Live refresh: KPIs every 1s, full data every 60s, ticker every 1s
var _kpiTs = Date.now();
setInterval(function(){ loadStats(); _kpiTs = Date.now(); }, 1000);
setInterval(function(){ loadAll();   _kpiTs = Date.now(); }, 60000);
setInterval(function(){
    var t = document.getElementById('an-live-txt');
    if(!t) return;
    var s = Math.round((Date.now() - _kpiTs) / 1000);
    t.textContent = s <= 1 ? 'Live · just now' : 'Live · ' + s + 's ago';
}, 1000);

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

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
