<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="card mb-3" style="background:linear-gradient(135deg,#0F766E,#0891B2);border:none;color:#fff">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
        <div>
            <div style="font-size:13px;opacity:.85">Welcome back</div>
            <div style="font-size:22px;font-weight:700"><?= Helpers::e(Auth::currentUser()['company'] ?: Auth::currentUser()['first_name'].' '.Auth::currentUser()['last_name']) ?></div>
            <div style="font-size:13px;opacity:.85">Code: <?= Helpers::e($adv['advertiser_code']) ?></div>
        </div>
        <a href="/advertiser/offers/create" class="btn" style="background:#fff;color:#0F766E;font-weight:700">+ Create Offer</a>
    </div>
</div>

<style>
@keyframes adv-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.45;transform:scale(.65)}}
/* "Live · just now" floating pill hidden — overlapped the live chat widget launcher. */
#adv-live-badge{display:none !important;align-items:center;gap:7px;position:fixed;bottom:20px;right:20px;background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;padding:8px 18px;border-radius:24px;font-size:12px;font-weight:700;box-shadow:0 4px 20px rgba(8,145,178,.45);z-index:9999;}
#adv-live-badge .lp{width:8px;height:8px;border-radius:50%;background:#10B981;animation:adv-pulse 1.4s infinite;flex-shrink:0;}
</style>
<div id="adv-live-badge"><span class="lp"></span><span id="adv-live-txt">Live</span></div>

<div class="stats-grid mb-3">
    <div class="stat-card"><div class="stat-icon blue">&#128432;</div><div class="stat-label">Clicks Today</div><div class="stat-value" id="adv-k-clicks"><?= number_format($todayStats['clicks']??0) ?></div></div>
    <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-label">Conversions Today</div><div class="stat-value" id="adv-k-conv"><?= number_format($todayStats['conv']??0) ?></div></div>
    <div class="stat-card"><div class="stat-icon purple">&#128176;</div><div class="stat-label">Revenue Today</div><div class="stat-value" id="adv-k-revenue">$<?= number_format($todayStats['revenue']??0,2) ?></div></div>
    <div class="stat-card"><div class="stat-icon teal">&#127991;</div><div class="stat-label">Active Offers</div><div class="stat-value" id="adv-k-offers"><?= $totalOffers ?></div></div>
</div>

<!-- ─── Performance Analytics ────────────────────────────────────────── -->
<!-- ─── Performance Analytics (PREMIUM SAAS UI) ────────────────────────────────────────── -->
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
            <span class="saas-section-title">Performance Analytics <span style="font-size:12px;color:var(--text-muted);font-weight:600;margin-left:8px" id="ap-range">—</span></span>
            <div class="saas-time-filters ap-pills">
                <button class="saas-chip ap-pill" data-preset="today">Today</button>
                <button class="saas-chip ap-pill" data-preset="yesterday">Yesterday</button>
                <button class="saas-chip ap-pill" data-preset="7d">7 Days</button>
                <button class="saas-chip ap-pill active" data-preset="30d">30 Days</button>
                <button class="saas-chip ap-pill" data-preset="mtd">This Month</button>
            </div>
        </div>

        <div class="saas-kpi-row">
            <div class="saas-kpi-card" style="--kpi-color: #2563EB;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.05 5A5 5 0 0 1 19 8.95M15.05 1A9 9 0 0 1 23 8.94m-1 7.98v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Clicks</div>
                    <div class="saas-kpi-value" id="ap-clicks">0</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #10B981;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Conversions</div>
                    <div class="saas-kpi-value" id="ap-conv">0</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #8B5CF6;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Spend</div>
                    <div class="saas-kpi-value" id="ap-revenue">$0.00</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
            <div class="saas-kpi-card" style="--kpi-color: #F59E0B;">
                <div class="saas-kpi-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <div class="saas-kpi-content">
                    <div class="saas-kpi-label">Payout</div>
                    <div class="saas-kpi-value" id="ap-payout">$0.00</div>
                </div>
                <div class="saas-kpi-sparkline"></div>
            </div>
        </div>

        <div class="saas-legend-container" id="ap-metrics">
            <button class="saas-legend-item active" style="--leg-color: #3B82F6;" data-m="clicks">
                <span class="saas-leg-dot"></span> Clicks
            </button>
            <button class="saas-legend-item active" style="--leg-color: #10B981;" data-m="conv">
                <span class="saas-leg-dot"></span> Conversions
            </button>
            <button class="saas-legend-item active" style="--leg-color: #8B5CF6;" data-m="revenue">
                <span class="saas-leg-dot"></span> Spend
            </button>
            <button class="saas-legend-item active" style="--leg-color: #F59E0B;" data-m="payout">
                <span class="saas-leg-dot"></span> Payout
            </button>
        </div>
    </div>

    <div class="saas-trend-body">
        <canvas id="ap-chart"></canvas>
        <div id="saas-custom-tooltip" class="saas-custom-tooltip"></div>
        <div class="ap-loading" id="ap-loading" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,.5);z-index:5;align-items:center;justify-content:center"><div style="width:24px;height:24px;border:3px solid var(--text-muted);border-top-color:#3B82F6;border-radius:50%;animation:ap-spin 1s linear infinite"></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Recent Conversions</span><a href="/advertiser/reports" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Offer</th><th>Payout</th><th>Revenue</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if(empty($recentConversions)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No conversions yet</td></tr>
            <?php else: ?>
            <?php foreach($recentConversions as $cv): ?>
            <tr>
                <td><?= Helpers::e($cv['offer_name']) ?></td>
                <td>$<?= number_format($cv['payout'],2) ?></td>
                <td>$<?= number_format($cv['revenue'],2) ?></td>
                <td><span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']]??'muted' ?>"><?= $cv['status'] ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, H:i',strtotime($cv['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><span class="card-title">Your Postback URL</span></div>
    <div class="card-body">
        <p class="text-sm text-muted mb-2">Use this URL to send conversions. Fire it from your server when a user converts.</p>
        <?php $pbUrl = Helpers::trackingUrl().'/postback?click_id={click_id}&payout={payout}&goal={goal}&txn_id={txn_id}'; ?>
        <div class="copy-group">
            <input type="text" id="advPbUrl" class="form-control" value="<?= Helpers::e($pbUrl) ?>" readonly>
            <button class="btn btn-secondary btn-sm" data-copy="advPbUrl">Copy</button>
        </div>
        <div class="form-hint mt-1">
            Fire this URL from your server when a conversion is confirmed.<br>
            <code>{click_id}</code> and <code>{payout}</code> are <strong>required</strong>. <code>{goal}</code> (e.g. Registration, Deposit) and <code>{txn_id}</code> (order/transaction ID) are <strong>optional</strong> — shown in reports when provided.
        </div>
    </div>
</div>

<script>
// ── Performance Analytics — date filters · line/bar · 4 metrics · realtime ──
(function(){
    'use strict';
    var API = '/api/advertiser-analytics';
    var REFRESH = 60000;

    var preset       = '30d';
    var chartType    = 'line';
    var active       = new Set(['clicks','conv','revenue','payout']);
    var lastTrend    = null;
    var chart        = null;
    var refreshTimer = null;
    var abortCtrl    = null;

    var META = {
        clicks:  { label:'Clicks',      color:'#3B82F6', axis:'y',  isCur:false },
        conv:    { label:'Conversions', color:'#10B981', axis:'y',  isCur:false },
        revenue: { label:'Revenue',     color:'#8B5CF6', axis:'y1', isCur:true  },
        payout:  { label:'Payout',      color:'#F59E0B', axis:'y1', isCur:true  }
    };

    function fmtDate(d){
        var y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), dd=String(d.getDate()).padStart(2,'0');
        return y+'-'+m+'-'+dd;
    }
    function presetRange(p){
        var t = new Date();
        var today = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        var mtd = new Date(today.getFullYear(), today.getMonth(), 1);
        var lmEnd = new Date(today.getFullYear(), today.getMonth(), 0);
        var lmStart = new Date(lmEnd.getFullYear(), lmEnd.getMonth(), 1);
        var y = new Date(today); y.setDate(y.getDate()-1);
        function add(d, n){ var x = new Date(d); x.setDate(x.getDate()+n); return x; }
        switch(p){
            case 'today':     return [today, today];
            case 'yesterday': return [y, y];
            case '7d':        return [add(today,-6),  today];
            case '15d':       return [add(today,-14), today];
            case '30d':       return [add(today,-29), today];
            case '90d':       return [add(today,-89), today];
            case 'mtd':       return [mtd, today];
            case 'lastmonth': return [lmStart, lmEnd];
            default:          return [add(today,-29), today];
        }
    }
    function readable(a, b){
        var o = { month:'short', day:'numeric', year:'numeric' };
        var sa = a.toLocaleDateString(undefined, o), sb = b.toLocaleDateString(undefined, o);
        return sa === sb ? sa : (sa + ' → ' + sb);
    }
    var nfInt = new Intl.NumberFormat();
    var nfMon = new Intl.NumberFormat(undefined, { minimumFractionDigits:2, maximumFractionDigits:2 });
    function fmtInt(n){ return nfInt.format(Number(n)||0); }
    function fmtCur(n){ return '$' + nfMon.format(Number(n)||0); }

    function setDelta(id, pct){
        var el = document.getElementById(id); if(!el) return;
        if (pct === null || pct === undefined || isNaN(pct)) { el.className='ap-kpi-delta flat'; el.textContent='—'; return; }
        var cls = pct>0?'up':pct<0?'down':'flat';
        el.className = 'ap-kpi-delta '+cls;
        el.textContent = (pct>0?'▲':pct<0?'▼':'—') + ' ' + Math.abs(pct) + '% vs prev';
    }

    function showLoad(on){ var el = document.getElementById('ap-loading'); if(el) el.classList.toggle('show', !!on); }

    function buildQS(action){
        var r = presetRange(preset);
        var q = new URLSearchParams({ action:action, from:fmtDate(r[0]), to:fmtDate(r[1]) });
        return q.toString();
    }

    function fetchAll(){
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        var sig = abortCtrl.signal;

        showLoad(true);
        var r = presetRange(preset);
        var rangeEl = document.getElementById('ap-range');
        if (rangeEl) rangeEl.textContent = readable(r[0], r[1]);

        Promise.all([
            fetch(API+'?'+buildQS('stats'), {signal: sig}).then(function(r){return r.json();}),
            fetch(API+'?'+buildQS('trend'), {signal: sig}).then(function(r){return r.json();})
        ]).then(function(out){
            var s = out[0] || {}, t = out[1] || {};
            document.getElementById('ap-clicks').textContent  = fmtInt(s.clicks);
            document.getElementById('ap-conv').textContent    = fmtInt(s.conv);
            document.getElementById('ap-revenue').textContent = fmtCur(s.revenue);
            document.getElementById('ap-payout').textContent  = fmtCur(s.payout);
            setDelta('ap-clicks-delta',  (s.trend && s.trend.clicks));
            setDelta('ap-conv-delta',    (s.trend && s.trend.conv));
            setDelta('ap-revenue-delta', (s.trend && s.trend.revenue));
            setDelta('ap-payout-delta',  (s.trend && s.trend.payout));
            lastTrend = t;
            render();
        }).catch(function(err){
            if (err.name !== 'AbortError') console.warn('Performance Analytics fetch failed', err);
        }).finally(function(){ showLoad(false); });
    }

    function hexA(hex, a){
        var h = hex.replace('#','');
        if (h.length === 3) h = h.split('').map(function(c){return c+c;}).join('');
        var r = parseInt(h.slice(0,2),16), g = parseInt(h.slice(2,4),16), b = parseInt(h.slice(4,6),16);
        return 'rgba('+r+','+g+','+b+','+a+')';
    }
    function isDark(){ return document.documentElement.getAttribute('data-theme') === 'dark'; }
    function gridCol(){ return isDark() ? 'rgba(148,163,184,.10)' : '#F1F5F9'; }
    function tickCol(){ return isDark() ? '#9CA3AF' : '#475569'; }

    function render(){
        if (!lastTrend || !lastTrend.labels) return;
        var order = ['clicks','conv','revenue','payout'];
        var keys  = order.filter(function(k){ return active.has(k); });
        var isLine = typeof chartType !== 'undefined' ? chartType === 'line' : true;
        var dense = lastTrend.labels.length > 30;
        
        // Update SaaS AI Insights
        var aiPanel = document.getElementById('saas-ai-panel');
        var aiList = document.getElementById('saas-ai-list');
        if (aiPanel && aiList && lastTrend.labels.length > 0) {
            aiPanel.style.opacity = '1';
            var insights = [];
            if (lastTrend.revenue_data && lastTrend.revenue_data.length > 0) {
                var maxRev = Math.max.apply(null, lastTrend.revenue_data);
                var maxIdx = lastTrend.revenue_data.indexOf(maxRev);
                if (maxRev > 0) insights.push("Peak spend of $" + fmtInt(maxRev) + " on " + lastTrend.labels[maxIdx] + ".");
            }
            if (lastTrend.conv_data && lastTrend.conv_data.length > 0 && lastTrend.clicks_data && lastTrend.clicks_data.length > 0) {
                var sumConv = lastTrend.conv_data.reduce((a,b)=>a+b,0);
                var sumClicks = lastTrend.clicks_data.reduce((a,b)=>a+b,0);
                var cr = sumClicks > 0 ? (sumConv/sumClicks*100).toFixed(1) : 0;
                if (cr > 0) insights.push("Average Conversion Rate: " + cr + "%.");
            }
            if (insights.length === 0) insights.push("No significant insights found.");
            aiList.innerHTML = insights.map(i => '<li>' + i + '</li>').join('');
        }

        const datasets = keys.map(k => {
            const m = META[k];
            const data = lastTrend[(k === 'conv' ? 'conv' : k) + '_data'] || [];
            return {
                label: m.label + (m.isCur ? ' ($)' : ''),
                data: data,
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
                fill: isLine,
                tension: isLine ? 0.45 : 0,
                borderWidth: 3,
                pointRadius: 0, 
                pointHoverRadius: 8,
                pointBackgroundColor: m.color,
                pointBorderColor: '#ffffff',
                pointHoverBorderWidth: 3,
                yAxisID: m.axis,
                isCur: m.isCur
            };
        });
    
        // Custom Glow Plugin
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
                    let displayVal = ds.isCur ? fmtCur(val) : fmtInt(val);
                    
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

        var cfg = {
            type: chartType,
            data: { labels: lastTrend.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 500, easing: 'easeOutQuart' },
                interaction: { mode:'index', intersect:false },
                plugins: {
                    legend: { display:false },
                    tooltip: { enabled: false, external: externalTooltipHandler }
                },
                scales: {
                    y:  { display: hasCount, beginAtZero:true, position:'left',
                          grid:{ color: gridCol(), drawBorder:false },
                          ticks:{ color: tickCol(), font:{size:11}, callback:function(v){return fmtInt(v);} } },
                    y1: { display: hasCur,   beginAtZero:true, position:'right',
                          grid:{ display:false }, ticks:{ color: tickCol(), font:{size:11}, callback:function(v){return '$'+fmtInt(v);} } },
                    x:  { grid:{ color: gridCol(), drawBorder:false },
                          ticks:{ color: tickCol(), font:{size:11}, maxRotation:0, autoSkipPadding:12 } }
                }
            },
            plugins: isLine ? [glowPlugin] : []
        };
        if (chart) chart.destroy();
        var ctx = document.getElementById('ap-chart');
        if (ctx && typeof Chart !== 'undefined') chart = new Chart(ctx, cfg);
    }

    // Pills
    document.querySelectorAll('.ap-pill').forEach(function(p){
        p.addEventListener('click', function(){
            preset = p.dataset.preset;
            document.querySelectorAll('.ap-pill').forEach(function(x){ x.classList.toggle('active', x===p); });
            fetchAll();
        });
    });
    // Metric toggles
    document.querySelectorAll('#ap-metrics button').forEach(function(b){
        b.addEventListener('click', function(){
            var m = b.dataset.m;
            if (active.has(m)) {
                if (active.size === 1) return;
                active.delete(m); b.classList.remove('active');
            } else { active.add(m); b.classList.add('active'); }
            render();
        });
    });
    // Type toggle
    document.querySelectorAll('#ap-types button').forEach(function(b){
        b.addEventListener('click', function(){
            chartType = b.dataset.t;
            document.querySelectorAll('#ap-types button').forEach(function(x){ x.classList.toggle('active', x===b); });
            render();
        });
    });
    // Re-render on theme switch
    new MutationObserver(function(){ if (chart) render(); })
        .observe(document.documentElement, { attributes:true, attributeFilter:['data-theme'] });

    // Auto-refresh while tab is visible
    function tick(){ if (document.visibilityState === 'visible') fetchAll(); }
    refreshTimer = setInterval(tick, REFRESH);
    document.addEventListener('visibilitychange', function(){ if (document.visibilityState === 'visible') fetchAll(); });

    // Chart.js is loaded with `defer` in the layout — wait for it on first paint.
    function boot(){
        if (typeof Chart === 'undefined') { setTimeout(boot, 60); return; }
        fetchAll();
    }
    boot();
})();

// ── Live stats: KPIs every 5s, ticker every 1s ───────────────────────────────
(function(){
    function fmtNum(n){ return Number(n).toLocaleString(); }
    function fmtMoney(n){ return '$'+Number(n).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }

    var _kpiTs = Date.now();

    function refreshStats(){
        fetch('/api/stats')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var c=document.getElementById('adv-k-clicks');
                var cv=document.getElementById('adv-k-conv');
                var rv=document.getElementById('adv-k-revenue');
                var of=document.getElementById('adv-k-offers');
                if(c)  c.textContent  = fmtNum(d.clicks_today  ?? 0);
                if(cv) cv.textContent = fmtNum(d.conv_today    ?? 0);
                if(rv) rv.textContent = fmtMoney(d.revenue_today ?? 0);
                if(of) of.textContent = fmtNum(d.active_offers  ?? 0);
                _kpiTs = Date.now();
            })
            .catch(function(){});
    }

    setInterval(refreshStats, 5000);
    setInterval(function(){
        var t = document.getElementById('adv-live-txt');
        if(!t) return;
        var s = Math.round((Date.now() - _kpiTs) / 1000);
        t.textContent = s <= 1 ? 'Live · just now' : 'Live · ' + s + 's ago';
    }, 1000);
})();
</script>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
