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
?>
<style>
/* ── Analytics Dashboard Styles ─────────────────────────────── */
.an-header {
    background: linear-gradient(135deg,#1E1B4B 0%,#312E81 50%,#4C1D95 100%);
    border-radius: 14px;
    padding: 22px 26px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
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
.an-kpi-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 16px 14px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .2s, transform .2s, background-color .2s, border-color .2s;
}
.an-kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.an-kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 12px 12px 0 0;
}
.an-kpi-card.blue::before   { background: linear-gradient(90deg,#3B82F6,#60A5FA); }
.an-kpi-card.cyan::before   { background: linear-gradient(90deg,#06B6D4,#22D3EE); }
.an-kpi-card.purple::before { background: linear-gradient(90deg,#8B5CF6,#A78BFA); }
.an-kpi-card.green::before  { background: linear-gradient(90deg,#10B981,#34D399); }
.an-kpi-card.orange::before { background: linear-gradient(90deg,#F59E0B,#FCD34D); }
.an-kpi-card.indigo::before { background: linear-gradient(90deg,#4F46E5,#818CF8); }
.an-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    margin-bottom: 12px;
}
.an-kpi-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 4px; }
.an-kpi-value { font-size: 26px; font-weight: 800; color: var(--text); line-height: 1.1; }
.an-kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    margin-top: 6px;
}
.an-kpi-trend.up   { background: rgba(16,185,129,.16); color: #16A34A; }
.an-kpi-trend.down { background: rgba(239,68,68,.16);  color: #DC2626; }
.an-kpi-trend.flat { background: var(--bg);            color: var(--text-muted); }
.an-kpi-sub { font-size: 11px; color: var(--text-light); margin-top: 4px; }

/* Chart sections */
.an-section-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}
.an-section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 18px;
    background: linear-gradient(180deg,#4F46E5,#7C3AED);
    border-radius: 2px;
    flex-shrink: 0;
}
.an-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: background-color .2s, border-color .2s;
}
.an-card-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.an-card-body { padding: 20px; }

/* Performance Trend legend — dim the label when its line is toggled off so
   the on/off state is visible at a glance (the checkbox itself is hidden). */
.an-card-head label:has(input[type="checkbox"]:not(:checked)){
    opacity: .35;
    text-decoration: line-through;
}

/* Loading skeleton */
.an-skeleton {
    background: linear-gradient(90deg, var(--bg) 25%, var(--border) 50%, var(--bg) 75%);
    background-size: 200% 100%;
    animation: an-shimmer 1.4s infinite;
    border-radius: 6px;
}
@keyframes an-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* Status badge */
.an-status { display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.an-status.approved { background:#DCFCE7;color:#16A34A; }
.an-status.pending  { background:#FEF9C3;color:#854D0E; }
.an-status.rejected { background:#FEE2E2;color:#DC2626; }

/* Funnel bar */
.an-funnel-bar { height: 28px; border-radius: 6px; background: var(--bg); overflow: hidden; margin-top: 6px; }
.an-funnel-fill { height: 100%; border-radius: 6px; display:flex;align-items:center;justify-content:flex-end;padding-right:10px;transition:width .8s ease; }

/* Manager banner */
.an-mgr-banner {
    background: linear-gradient(135deg,#4F46E5,#7C3AED);
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

/* Live pulse */
@keyframes live-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
/* "Live · just now" floating pill is hidden — it overlapped the chat widget launcher.
   The data is still being polled (the JS still updates #an-live-txt), it's just not shown. */
#an-live-dot { display: none !important; }
#an-live-dot .lp{width:8px;height:8px;border-radius:50%;background:#fff;opacity:.9;animation:live-pulse 1.4s infinite;flex-shrink:0;}

/* Responsive */
@media (max-width:1200px) { .an-kpi-grid { grid-template-columns: repeat(3,1fr); } }
@media (max-width:900px)  { .an-kpi-grid { grid-template-columns: repeat(2,1fr); } .an-header { flex-direction:column; align-items:flex-start; } }
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
</style>

<div id="an-live-dot"><span class="lp"></span><span id="an-live-txt">Live</span></div>

<!-- ══ HEADER ══════════════════════════════════════════════════════════════ -->
<div class="an-header">
    <div style="position:relative;z-index:1">
        <div class="an-title-label">Affiliate Network</div>
        <div class="an-title">Analytics Dashboard</div>
        <div style="font-size:12px;color:rgba(255,255,255,.5);margin-top:4px" id="an-last-updated">Loading data…</div>
    </div>
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
        <div class="an-kpi-icon" style="background:#EFF6FF;color:#2563EB">📊</div>
        <div class="an-kpi-label">Total Clicks</div>
        <div class="an-kpi-value" id="kv-clicks"><span class="an-skeleton" style="display:block;height:28px;width:70px"></span></div>
        <div class="an-kpi-trend flat" id="kt-clicks">—</div>
    </div>
    <div class="an-kpi-card cyan">
        <div class="an-kpi-icon" style="background:#ECFEFF;color:#06B6D4">🔗</div>
        <div class="an-kpi-label">Unique Clicks</div>
        <div class="an-kpi-value" id="kv-unique"><span class="an-skeleton" style="display:block;height:28px;width:60px"></span></div>
        <div class="an-kpi-trend flat" id="kt-unique">—</div>
    </div>
    <div class="an-kpi-card purple">
        <div class="an-kpi-icon" style="background:#F5F3FF;color:#7C3AED">✅</div>
        <div class="an-kpi-label">Conversions</div>
        <div class="an-kpi-value" id="kv-conv"><span class="an-skeleton" style="display:block;height:28px;width:50px"></span></div>
        <div class="an-kpi-trend flat" id="kt-conv">—</div>
    </div>
    <div class="an-kpi-card green">
        <div class="an-kpi-icon" style="background:#F0FDF4;color:#10B981">💰</div>
        <div class="an-kpi-label">Revenue</div>
        <div class="an-kpi-value" id="kv-revenue"><span class="an-skeleton" style="display:block;height:28px;width:80px"></span></div>
        <div class="an-kpi-trend flat" id="kt-revenue">—</div>
    </div>
    <div class="an-kpi-card orange">
        <div class="an-kpi-icon" style="background:#FFFBEB;color:#F59E0B">🎯</div>
        <div class="an-kpi-label">Conv. Rate</div>
        <div class="an-kpi-value" id="kv-cr"><span class="an-skeleton" style="display:block;height:28px;width:55px"></span></div>
        <div class="an-kpi-sub" id="ks-cr">CR%</div>
    </div>
    <div class="an-kpi-card indigo">
        <div class="an-kpi-icon" style="background:#EEF2FF;color:#4F46E5">💳</div>
        <div class="an-kpi-label">Balance</div>
        <div class="an-kpi-value" id="kv-balance"><span class="an-skeleton" style="display:block;height:28px;width:80px"></span></div>
        <div class="an-kpi-sub">Available</div>
    </div>
    <!-- Fraud Conversion % — server-computed (fraud_score >= 60). The actual
         per-row score is never sent to the affiliate, only the aggregate %. -->
    <div class="an-kpi-card" style="--an-kpi-accent:#DC2626" title="Fraud conversions are conversions with fraud score between 60–100.">
        <div class="an-kpi-icon" style="background:#FEF2F2;color:#DC2626">⚠</div>
        <div class="an-kpi-label">Fraud Conversion %</div>
        <div class="an-kpi-value" id="kv-fraud-conv-pct"><span class="an-skeleton" style="display:block;height:28px;width:65px"></span></div>
        <div class="an-kpi-sub" id="ks-fraud-conv"><span style="color:#DC2626;font-weight:600">—</span> Fraud Conversions</div>
        <div class="an-kpi-trend flat" id="kt-fraud-conv-pct">—</div>
    </div>
</div>

<!-- ══ MAIN TREND CHART ════════════════════════════════════════════════════ -->
<div class="an-card" style="margin-bottom:18px">
    <div class="an-card-head">
        <span class="an-section-title">Performance Trend</span>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <!-- Legend toggles -->
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#6B7280;cursor:pointer">
                <span style="width:12px;height:3px;background:#3B82F6;border-radius:2px;display:inline-block"></span> Clicks
                <input type="checkbox" checked id="tog-clicks" onchange="updateTrendChart()" style="display:none">
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#6B7280;cursor:pointer">
                <span style="width:12px;height:3px;background:#8B5CF6;border-radius:2px;display:inline-block"></span> Conversions
                <input type="checkbox" checked id="tog-conv" onchange="updateTrendChart()" style="display:none">
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#6B7280;cursor:pointer">
                <span style="width:12px;height:3px;background:#10B981;border-radius:2px;display:inline-block"></span> Revenue
                <input type="checkbox" checked id="tog-rev" onchange="updateTrendChart()" style="display:none">
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#DC2626;cursor:pointer"
                   title="Fraud conversions are conversions with fraud score between 60–100.">
                <span style="width:12px;height:3px;background:#DC2626;border-radius:2px;display:inline-block"></span> Fraud
                <input type="checkbox" checked id="tog-fraud" onchange="updateTrendChart()" style="display:none">
            </label>
        </div>
    </div>
    <div class="an-card-body" style="padding-bottom:16px">
        <canvas id="chart-trend" height="90"></canvas>
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
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px" id="row4-grid">

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
                o.name AS offer_name
         FROM conversions cv
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

<!-- ══ ROW 5: Recent Conversions + Funnel ══════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:18px" id="row5-grid">

    <!-- Recent Conversions -->
    <div class="an-card">
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

    <!-- Conversion Funnel -->
    <div class="an-card">
        <div class="an-card-head"><span class="an-section-title">Conversion Funnel</span></div>
        <div class="an-card-body" id="funnel-body">
            <div class="an-skeleton" style="height:22px;margin-bottom:14px"></div>
            <div class="an-skeleton" style="height:22px;margin-bottom:14px"></div>
            <div class="an-skeleton" style="height:22px;margin-bottom:14px"></div>
            <div class="an-skeleton" style="height:22px"></div>
        </div>
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
var _trendData = {};
var _convData  = {};
var _statsData = {};

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
        _statsData = d;
        document.getElementById('kv-clicks').textContent  = fmt(d.clicks);
        document.getElementById('kv-unique').textContent  = fmt(d.unique);
        document.getElementById('kv-conv').textContent    = fmt(d.conversions);
        document.getElementById('kv-revenue').textContent = '$'+fmt(d.revenue,2);
        document.getElementById('kv-cr').textContent      = fmt(d.cr,2)+'%';
        document.getElementById('kv-balance').textContent = '$'+fmt(d.balance,2);
        setTrend('kt-clicks', d.trend.clicks);
        setTrend('kt-conv',   d.trend.conv);
        setTrend('kt-revenue',d.trend.revenue);
        // Fraud Conversion % card
        var fp = document.getElementById('kv-fraud-conv-pct');
        var fc = document.getElementById('ks-fraud-conv');
        if (fp) fp.textContent = fmt(d.fraud_conv_pct ?? 0, 2) + '%';
        if (fc) fc.innerHTML   = '<span style="color:#DC2626;font-weight:600">' + fmt(d.fraud_conv ?? 0) + '</span> Fraud Conversions';
        if (d.trend && d.trend.fraud_conv_pct !== undefined) setTrend('kt-fraud-conv-pct', d.trend.fraud_conv_pct);
        document.getElementById('an-last-updated').textContent = 'Updated ' + new Date().toLocaleTimeString([], { timeZone: getTz(), hour:'2-digit', minute:'2-digit', second:'2-digit' }) + ' (' + getTz() + ')';
        updateFunnel(d.clicks, d.unique, d.conversions);
        showLiveDot();
    }).catch(function(){});
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

function renderTrendChart(d){
    destroyChart('trend');
    var ctx = document.getElementById('chart-trend');
    if (!ctx) return;
    var datasets = [];
    if (document.getElementById('tog-clicks').checked)
        datasets.push({label:'Clicks',data:d.clicks_data,borderColor:'#3B82F6',backgroundColor:'rgba(59,130,246,.08)',tension:.35,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2.5});
    if (document.getElementById('tog-conv').checked)
        datasets.push({label:'Conversions',data:d.conv_data,borderColor:'#8B5CF6',backgroundColor:'rgba(139,92,246,.07)',tension:.35,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2.5});
    if (document.getElementById('tog-rev').checked)
        datasets.push({label:'Revenue ($)',data:d.revenue_data,borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.07)',tension:.35,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2.5,yAxisID:'y2'});
    var togFraud = document.getElementById('tog-fraud');
    if (togFraud && togFraud.checked && d.fraud_data)
        datasets.push({label:'Fraud Conversions',data:d.fraud_data,borderColor:'#DC2626',backgroundColor:'rgba(220,38,38,.08)',tension:.35,fill:true,pointRadius:3,pointHoverRadius:6,borderWidth:2.5,borderDash:[5,3]});
    charts['trend'] = new Chart(ctx, {
        type:'line',
        data:{ labels: d.labels, datasets: datasets },
        options:{
            responsive:true, maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ display:false }, tooltip:{ backgroundColor:'rgba(17,24,39,.9)', titleFont:{size:12}, bodyFont:{size:12}, padding:10, cornerRadius:8 } },
            scales:{
                x:{ grid:{color:'#F3F4F6'}, ticks:{font:{size:11},maxRotation:0,maxTicksLimit:12} },
                y:{ beginAtZero:true, grid:{color:'#F3F4F6'}, ticks:{font:{size:11}}, position:'left' },
                y2:{ beginAtZero:true, grid:{display:false}, ticks:{font:{size:11}, callback:function(v){return'$'+v;}}, position:'right', display: document.getElementById('tog-rev').checked }
            }
        }
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
            data:{ labels:d.labels, datasets:[{data:d.data, backgroundColor:COLORS.slice(0,d.labels.length), borderColor:'#fff', borderWidth:3, hoverBorderWidth:4 }]},
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
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top',labels:{font:{size:11}}}}, scales:{ x:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
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
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){return'$'+fmt(ctx.raw,2);}}}}, scales:{ x:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11},callback:function(v){return'$'+v;}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
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
            options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false},tooltip:{callbacks:{title:function(items){return items[0].label;},label:function(ctx){return ctx.raw+' clicks';}}}}, scales:{ x:{grid:{display:false},ticks:{font:{size:10},maxRotation:0,callback:function(v,i){return i%2===0?d.labels[i]:'';}},}, y:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11}}} } }
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
            data:{ labels:d.labels, datasets:[{data:d.data, backgroundColor:COLORS.slice(0,d.labels.length), borderColor:'#fff', borderWidth:3}]},
            options:{ responsive:true, maintainAspectRatio:false, cutout:'55%', plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,boxWidth:10}}} }
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
            options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ x:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11}}}, y:{grid:{display:false},ticks:{font:{size:11}}} } }
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
    var fb = document.getElementById('funnel-body');
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

})();
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
