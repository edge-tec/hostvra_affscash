<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<style>
/* ── Login Activity Styles ──────────────────────────────────── */
:root { --online:#10B981; --offline:#EF4444; }

/* Tab nav */
.act-tabs { display:flex; gap:0; border-bottom:2px solid #E5E7EB; margin-bottom:22px; }
.act-tab {
    padding:11px 22px; font-size:13px; font-weight:600; cursor:pointer;
    border:none; background:none; color:#6B7280;
    border-bottom:2px solid transparent; margin-bottom:-2px;
    display:flex; align-items:center; gap:7px; transition:.15s;
}
.act-tab:hover { color:#4F46E5; }
.act-tab.active { color:#4F46E5; border-bottom-color:#4F46E5; }
.act-tab-badge {
    background:#EF4444; color:#fff; border-radius:20px;
    padding:1px 7px; font-size:10px; font-weight:700;
}

/* Summary cards */
.act-summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:14px; margin-bottom:20px; }
.act-sum-card {
    background:var(--card-bg); border:1px solid var(--border); border-radius:12px;
    padding:18px 20px; display:flex; align-items:center; gap:16px;
    transition:box-shadow .2s, background-color .2s, border-color .2s;
}
.act-sum-card:hover { box-shadow:var(--shadow-md); }
.act-sum-icon {
    width:46px; height:46px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;
}
.act-sum-val { font-size:26px; font-weight:800; color:var(--text); line-height:1.1; }
.act-sum-label { font-size:12px; color:var(--text-muted); font-weight:500; margin-top:2px; }

/* Live users grid */
.live-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr)); gap:14px; }
.live-card {
    background:var(--card-bg); border:1px solid var(--border); border-radius:12px;
    padding:16px; transition:box-shadow .2s, border-color .2s, background-color .2s;
    position:relative; overflow:hidden;
}
.live-card:hover { box-shadow:var(--shadow-md); border-color:var(--primary); }
.live-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#10B981,#059669);
}
.live-card.idle::before { background:linear-gradient(90deg,#F59E0B,#D97706); }
.live-online-dot {
    width:9px; height:9px; border-radius:50%; background:var(--online);
    display:inline-block; flex-shrink:0;
    box-shadow:0 0 0 3px rgba(16,185,129,.2);
    animation:pulse-green 1.8s infinite;
}
.live-card.idle .live-online-dot { background:#F59E0B; box-shadow:0 0 0 3px rgba(245,158,11,.2); animation:none; }
@keyframes pulse-green { 0%,100%{box-shadow:0 0 0 3px rgba(16,185,129,.2)} 50%{box-shadow:0 0 0 6px rgba(16,185,129,.05)} }

/* Filter bar */
.act-filter-bar {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    background:var(--bg); border:1px solid var(--border); border-radius:10px;
    padding:14px 16px; margin-bottom:16px;
}
.act-filter-bar select,
.act-filter-bar input { padding:8px 12px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; color:var(--text); background:var(--card-bg); outline:none; }
.act-filter-bar select:focus,
.act-filter-bar input:focus { border-color:var(--primary); }

/* History table */
.act-tbl-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.act-tbl { width:100%; border-collapse:collapse; min-width:900px; }
.act-tbl thead th {
    padding:11px 14px; background:var(--bg); font-size:11px; font-weight:700;
    color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;
    border-bottom:1px solid var(--border); white-space:nowrap; text-align:left;
}
.act-tbl tbody td {
    padding:11px 14px; border-bottom:1px solid var(--border); font-size:13px;
    color:var(--text); vertical-align:middle;
}
.act-tbl tbody tr:hover { background:var(--bg); }

/* Pagination */
.act-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:14px 16px; border-top:1px solid var(--border); }
.act-page-btn {
    padding:6px 14px; border:1.5px solid var(--border); border-radius:7px;
    background:var(--card-bg); font-size:12px; font-weight:600; color:var(--text);
    cursor:pointer; transition:.15s;
}
.act-page-btn:hover:not(:disabled) { border-color:var(--primary); color:var(--primary); }
.act-page-btn:disabled { opacity:.45; cursor:default; }
.act-page-btn.active { background:var(--primary); color:#fff; border-color:var(--primary); }

/* Device / browser / role badges */
.act-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.act-badge.desktop { background:#EFF6FF; color:#2563EB; }
.act-badge.mobile  { background:#F0FDF4; color:#16A34A; }
.act-badge.tablet  { background:#FEF9C3; color:#854D0E; }
.act-badge.admin   { background:#FEE2E2; color:#DC2626; }
.act-badge.affiliate { background:#EEF2FF; color:#4F46E5; }
.act-badge.affiliate_manager { background:#F3E8FF; color:#7C3AED; }
.act-badge.active  { background:#DCFCE7; color:#16A34A; }
.act-badge.ended   { background:#F1F5F9; color:#64748B; }

/* Charts row */
.act-charts-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-bottom:18px; }

/* Empty / loading states */
.act-empty { padding:48px 24px; text-align:center; color:#9CA3AF; font-size:14px; }
.act-loading { text-align:center; padding:32px; color:#6B7280; font-size:13px; }
.act-skeleton { background:linear-gradient(90deg,#F1F5F9 25%,#E9EFF6 50%,#F1F5F9 75%); background-size:200% 100%; animation:act-shimmer 1.4s infinite; border-radius:6px; display:block; }
@keyframes act-shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* Responsive */
@media(max-width:1100px){ .act-charts-grid{grid-template-columns:1fr 1fr;} }
@media(max-width:768px){  .act-summary-grid{grid-template-columns:repeat(2,1fr);} .act-charts-grid{grid-template-columns:1fr;} .live-card{padding:12px;} }
@media(max-width:480px){  .act-summary-grid{grid-template-columns:repeat(2,1fr);} .live-grid{grid-template-columns:1fr;} .act-sum-val{font-size:18px;} }
</style>

<div class="page-header" style="margin-bottom:0">
    <div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="width:10px;height:10px;border-radius:50%;background:#10B981;display:inline-block;animation:pulse-green 1.8s infinite"></span>
            Login Activity &amp; Live Users
        </h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:3px">Real-time user monitoring and login history</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <div style="font-size:12px;color:#9CA3AF" id="act-last-refresh">—</div>
        <button onclick="refreshAll()" class="btn btn-secondary btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Refresh
        </button>
    </div>
</div>

<!-- ── Summary Cards ────────────────────────────────────────────────── -->
<div class="act-summary-grid" style="margin-top:20px">
    <div class="act-sum-card">
        <div class="act-sum-icon" style="background:#DCFCE7;font-size:22px">🟢</div>
        <div>
            <div class="act-sum-val" id="sc-online">—</div>
            <div class="act-sum-label">Online Now</div>
        </div>
    </div>
    <div class="act-sum-card">
        <div class="act-sum-icon" style="background:#EFF6FF">📅</div>
        <div>
            <div class="act-sum-val" id="sc-today">—</div>
            <div class="act-sum-label">Logins Today</div>
        </div>
    </div>
    <div class="act-sum-card">
        <div class="act-sum-icon" style="background:#F5F3FF">📊</div>
        <div>
            <div class="act-sum-val" id="sc-total">—</div>
            <div class="act-sum-label">Total Logins</div>
        </div>
    </div>
    <div class="act-sum-card">
        <div class="act-sum-icon" style="background:#FEF9C3">📱</div>
        <div>
            <div class="act-sum-val" id="sc-mobile">—</div>
            <div class="act-sum-label">Mobile Sessions</div>
        </div>
    </div>
</div>

<!-- ── Charts Row ────────────────────────────────────────────────────── -->
<div class="act-charts-grid">
    <div class="card" style="padding:0">
        <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:13px;color:#111827">Logins by Hour — Today</div>
        <div style="padding:16px"><canvas id="chart-hourly" height="130"></canvas></div>
    </div>
    <div class="card" style="padding:0">
        <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:13px;color:#111827">Device Distribution</div>
        <div style="padding:16px;display:flex;justify-content:center;align-items:center"><canvas id="chart-devices" height="130" style="max-width:200px"></canvas></div>
    </div>
    <div class="card" style="padding:0">
        <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:13px;color:#111827">Top Countries (7 days)</div>
        <div style="padding:16px"><canvas id="chart-countries" height="130"></canvas></div>
    </div>
</div>

<!-- ══ TABS ════════════════════════════════════════════════════════════ -->
<div class="act-tabs">
    <button class="act-tab active" onclick="switchTab('live')" id="tab-live">
        <span class="live-online-dot"></span>
        Live Users
        <span class="act-tab-badge" id="live-count-badge">0</span>
    </button>
    <button class="act-tab" onclick="switchTab('history')" id="tab-history">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Login History
    </button>
</div>

<!-- ══ TAB: LIVE USERS ══════════════════════════════════════════════════ -->
<div id="panel-live">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px">
        <div style="font-size:13px;color:#6B7280;display:flex;align-items:center;gap:6px">
            <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block;animation:pulse-green 1.8s infinite"></span>
            <strong>Real-time updates active</strong>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <input type="text" id="live-search" placeholder="Search name, IP, email…" oninput="filterLiveCards(this.value)"
                style="padding:8px 13px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none;width:220px">
            <select id="live-role-filter" onchange="filterLiveCards(document.getElementById('live-search').value)"
                style="padding:8px 13px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:13px;outline:none">
                <option value="">All Roles</option>
                <option value="affiliate">Affiliate</option>
                <option value="admin">Admin</option>
                <option value="affiliate_manager">Manager</option>
                <option value="advertiser">Advertiser</option>
            </select>
        </div>
    </div>
    <div id="live-grid" class="live-grid">
        <div class="act-loading">Loading live users…</div>
    </div>
    <div id="live-empty" style="display:none" class="act-empty">
        <div style="font-size:40px;margin-bottom:10px">👤</div>
        <div style="font-weight:600;color:#374151;font-size:15px">No users online</div>
        <div style="margin-top:4px">Active sessions will appear here in real time</div>
    </div>
</div>

<!-- ══ TAB: LOGIN HISTORY ═══════════════════════════════════════════════ -->
<div id="panel-history" style="display:none">

    <!-- Filter bar -->
    <div class="act-filter-bar">
        <input type="text" id="h-search" placeholder="🔍  Search name, IP, country, email…" style="flex:1;min-width:200px" oninput="debounceLoad()">
        <input type="date" id="h-from" placeholder="From">
        <input type="date" id="h-to"   placeholder="To">
        <select id="h-role">
            <option value="">All Roles</option>
            <option value="affiliate">Affiliate</option>
            <option value="admin">Admin</option>
            <option value="affiliate_manager">Manager</option>
            <option value="advertiser">Advertiser</option>
        </select>
        <select id="h-device">
            <option value="">All Devices</option>
            <option value="Desktop">Desktop</option>
            <option value="Mobile">Mobile</option>
            <option value="Tablet">Tablet</option>
        </select>
        <select id="h-country" style="min-width:120px">
            <option value="">All Countries</option>
        </select>
        <button onclick="loadHistory(1)" class="btn btn-primary btn-sm">Apply</button>
        <button onclick="resetFilters()" class="btn btn-secondary btn-sm">Reset</button>
    </div>

    <div class="card" style="overflow:hidden">
        <div class="act-tbl-wrap">
            <table class="act-tbl">
                <thead>
                    <tr>
                        <th style="width:32px">#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>IP Address</th>
                        <th>Location</th>
                        <th>Device</th>
                        <th>Browser / OS</th>
                        <th>Login Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="history-tbody">
                    <tr><td colspan="10" class="act-loading">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div class="act-pagination">
            <div style="font-size:13px;color:#6B7280" id="h-pagination-info">—</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap" id="h-pagination-btns"></div>
        </div>
    </div>
</div>

<!-- ══ JAVASCRIPT ═══════════════════════════════════════════════════════ -->
<script>
(function(){
'use strict';

var COLORS = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316','#EC4899'];
var charts  = {};
var liveData = [];
var currentPage = 1;
var liveTimer, liveCountdown;
var debounceTimer;

// ── Tab switch ─────────────────────────────────────────────────────────────
window.switchTab = function(tab) {
    document.getElementById('panel-live').style.display    = tab==='live'    ? '' : 'none';
    document.getElementById('panel-history').style.display = tab==='history' ? '' : 'none';
    document.getElementById('tab-live').classList.toggle('active',    tab==='live');
    document.getElementById('tab-history').classList.toggle('active', tab==='history');
    if (tab === 'history') loadHistory(1);
};

// ── Helpers ────────────────────────────────────────────────────────────────
function fmt(n){ return Number(n||0).toLocaleString(); }
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtDur(sec){
    if (!sec || sec < 0) return '—';
    if (sec < 60) return sec+'s';
    if (sec < 3600) return Math.floor(sec/60)+'m '+(sec%60)+'s';
    return Math.floor(sec/3600)+'h '+Math.floor((sec%3600)/60)+'m';
}
function fmtDt(dt){
    if (!dt) return '—';
    return typeof fmtTs==='function'
        ? fmtTs(dt, {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})
        : new Date(dt).toLocaleString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
function deviceIcon(d){
    if (d === 'Mobile (App)' || d === 'Mobile') return '📱';
    if (d === 'Tablet') return '💊';
    return '🖥️';
}
function roleBadge(r){
    var labels={admin:'Admin',affiliate:'Affiliate',affiliate_manager:'Manager',advertiser:'Advertiser'};
    return '<span class="act-badge '+(r||'')+'">'+esc(labels[r]||r)+'</span>';
}
function deviceBadge(d){
    var baseClass = (d || 'desktop').toLowerCase();
    if (baseClass.includes('mobile')) baseClass = 'mobile';
    return '<span class="act-badge '+baseClass+'">'+deviceIcon(d)+' '+esc(d||'Desktop')+'</span>';
}

// ── Load summary stats ─────────────────────────────────────────────────────
function loadStats(){
    fetch('/api/activity?action=log_stats')
    .then(function(r){return r.json();})
    .then(function(d){
        document.getElementById('sc-online').textContent = fmt(d.online_now);
        document.getElementById('sc-today').textContent  = fmt(d.logins_today);
        document.getElementById('sc-total').textContent  = fmt(d.total_logins);
        var mob = (d.devices||[]).find(function(x){return x.label==='Mobile';});
        document.getElementById('sc-mobile').textContent = fmt(mob ? mob.cnt : 0);

        // Hourly chart
        renderHourly(d.hourly_today || []);
        renderDevices(d.devices || []);
        renderCountries(d.top_countries || []);
        document.getElementById('act-last-refresh').textContent = 'Updated '+new Date().toLocaleTimeString();
    }).catch(function(){});
}

function renderHourly(data){
    if (charts.hourly) charts.hourly.destroy();
    var ctx = document.getElementById('chart-hourly');
    if (!ctx) return;
    var max = Math.max.apply(null,data)||1;
    charts.hourly = new Chart(ctx,{
        type:'bar',
        data:{labels:data.map(function(_,i){return i%3===0?String(i).padStart(2,'0')+':00':'';}),
              datasets:[{data:data,backgroundColor:data.map(function(v){return v===max?'rgba(79,70,229,1)':'rgba(79,70,229,.45)';}),borderRadius:3,borderSkipped:false}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11}}}}}
    });
}

function renderDevices(data){
    if (charts.devices) charts.devices.destroy();
    var ctx = document.getElementById('chart-devices');
    if (!ctx || !data.length) return;
    charts.devices = new Chart(ctx,{
        type:'doughnut',
        data:{labels:data.map(function(r){return r.label;}),
              datasets:[{data:data.map(function(r){return r.cnt;}),backgroundColor:COLORS.slice(0,data.length),borderColor:'#fff',borderWidth:3}]},
        options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:8,boxWidth:10}}}}
    });
}

function renderCountries(data){
    if (charts.countries) charts.countries.destroy();
    var ctx = document.getElementById('chart-countries');
    if (!ctx || !data.length) return;
    charts.countries = new Chart(ctx,{
        type:'bar',
        data:{labels:data.map(function(r){return r.country;}),
              datasets:[{data:data.map(function(r){return r.cnt;}),backgroundColor:'rgba(16,185,129,.7)',borderRadius:4}]},
        options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{color:'#F3F4F6'},ticks:{font:{size:11}}},y:{grid:{display:false},ticks:{font:{size:11}}}}}
    });
}

// ── Live Users ─────────────────────────────────────────────────────────────
function loadLive(){
    fetch('/api/activity?action=live_users')
    .then(function(r){return r.json();})
    .then(function(d){
        liveData = d.users || [];
        document.getElementById('live-count-badge').textContent = liveData.length;
        document.getElementById('sc-online').textContent = fmt(liveData.length);
        filterLiveCards(document.getElementById('live-search').value);
    }).catch(function(){});
}

window.filterLiveCards = function(search){
    search = (search||'').toLowerCase();
    var role = document.getElementById('live-role-filter').value;
    var filtered = liveData.filter(function(u){
        var matchSearch = !search || (
            (u.user_name||'').toLowerCase().includes(search) ||
            (u.email||'').toLowerCase().includes(search) ||
            (u.ip_address||'').toLowerCase().includes(search) ||
            (u.affiliate_code||'').toLowerCase().includes(search) ||
            (u.country||'').toLowerCase().includes(search)
        );
        var matchRole = !role || u.role === role;
        return matchSearch && matchRole;
    });
    renderLiveGrid(filtered);
};

function renderLiveGrid(users){
    var grid  = document.getElementById('live-grid');
    var empty = document.getElementById('live-empty');
    if (!users.length) {
        grid.innerHTML = '';
        grid.style.display  = 'none';
        empty.style.display = '';
        return;
    }
    grid.style.display  = '';
    empty.style.display = 'none';
    grid.innerHTML = users.map(function(u){
        var idle = parseInt(u.idle_sec||0);
        var isIdle = idle > 180; // 3min
        var dur   = fmtDur(parseInt(u.session_sec||0));
        var page  = u.current_page || '/';
        var initials = (u.user_name||'?').split(' ').map(function(w){return w.charAt(0).toUpperCase();}).slice(0,2).join('');
        
        var browserDisplay = esc(u.browser||'—');
        if (u.platform_source === 'Android APK' || u.browser === 'Android App') {
            browserDisplay = '<span style="color:#10B981;font-weight:700;display:inline-flex;align-items:center;gap:4px"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993.0004.5511-.4482.9997-.9993.9997zm-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997zm11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1517-.5676.4255.4255 0 00-.5753.1493l-2.029 3.5133A11.7584 11.7584 0 0012 7.7471c-1.8906 0-3.6593.4795-5.1228 1.3093L4.8482 5.5431a.426.426 0 00-.5753-.1493.415.415 0 00-.1517.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396z"/></svg> Android App</span>';
        }

        return '<div class="live-card '+(isIdle?'idle':'')+'">'+
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">'+
                '<div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0">'+esc(initials)+'</div>'+
                '<div style="flex:1;min-width:0">'+
                    '<div style="font-weight:700;font-size:13px;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc(u.user_name||'Unknown')+'</div>'+
                    '<div style="font-size:11px;color:#9CA3AF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+esc(u.email)+'</div>'+
                '</div>'+
                '<div style="display:flex;align-items:center;gap:5px">'+
                    '<span class="live-online-dot'+(isIdle?' idle':'')+'"></span>'+
                    '<span style="font-size:10px;font-weight:600;color:'+(isIdle?'#F59E0B':'#10B981')+'">'+
                        (isIdle?'Idle':'Active')+
                    '</span>'+
                '</div>'+
            '</div>'+
            '<div style="display:grid;grid-template-columns:1fr 1fr;gap:7px;font-size:12px;margin-bottom:12px">'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Role</span><br>'+roleBadge(u.role)+'</div>'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Device</span><br>'+deviceBadge(u.device_type)+'</div>'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">IP</span><br><span style="font-weight:600;font-family:monospace;font-size:12px;color:#374151">'+esc(u.ip_address)+'</span></div>'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Location</span><br>'+
                    (u.country_code?'<img src="https://flagcdn.com/16x12/'+u.country_code.toLowerCase()+'.png" style="margin-right:4px;vertical-align:middle" onerror="this.style.display=\'none\'">':'')+
                    '<span style="font-size:12px;color:#374151">'+esc(u.city&&u.city!=='Unknown'?u.city+', ':'')+''+esc(u.country||'—')+'</span>'+
                '</div>'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Browser</span><br><span style="color:#374151">'+browserDisplay+'</span></div>'+
                '<div><span style="color:#9CA3AF;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Session</span><br><span style="color:#374151">'+esc(dur)+'</span></div>'+
            '</div>'+
            (u.affiliate_code?'<div style="background:#EEF2FF;border-radius:7px;padding:7px 10px;font-size:12px;margin-bottom:10px;display:flex;align-items:center;gap:7px"><span style="color:#6B7280;font-size:10px;font-weight:700;text-transform:uppercase">Aff Code</span><span style="font-weight:700;color:#4F46E5;font-family:monospace">'+esc(u.affiliate_code)+'</span></div>':'')+
            '<div style="background:#F8FAFC;border-radius:7px;padding:7px 10px;font-size:11px;color:#6B7280;display:flex;align-items:center;gap:6px;overflow:hidden">'+
                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'+
                '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">'+esc(page)+'</span>'+
            '</div>'+
            '<div style="margin-top:10px;display:flex;justify-content:space-between;align-items:center">'+
                '<span style="font-size:11px;color:#D1D5DB">Last active: '+esc(fmtDur(idle)+' ago')+'</span>'+
                '<button onclick="forceLogout(\''+esc(u.session_id)+'\','+u.user_id+',this)" '+
                    'style="padding:4px 11px;border:1.5px solid #FCA5A5;border-radius:6px;background:#FEF2F2;color:#DC2626;font-size:11px;font-weight:700;cursor:pointer;transition:.15s" '+
                    'title="Force logout">Force Logout</button>'+
            '</div>'+
        '</div>';
    }).join('');
}

// ── Force logout ────────────────────────────────────────────────────────────
window.forceLogout = function(sessionId, userId, btn) {
    if (!confirm('Force logout this user?')) return;
    btn.disabled = true; btn.textContent = '…';
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetch('/api/activity?action=force_logout',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=force_logout&_token='+encodeURIComponent(token)+'&session_id='+encodeURIComponent(sessionId)+'&user_id='+userId
    }).then(function(r){return r.json();}).then(function(d){
        if (d.ok) loadLive();
        else { btn.disabled=false; btn.textContent='Force Logout'; }
    }).catch(function(){ btn.disabled=false; btn.textContent='Force Logout'; });
};

// ── Login History ──────────────────────────────────────────────────────────
window.loadHistory = function(page){
    currentPage = page || 1;
    var params = new URLSearchParams({
        action:   'login_logs',
        page:     currentPage,
        limit:    25,
        search:   document.getElementById('h-search').value,
        from:     document.getElementById('h-from').value,
        to:       document.getElementById('h-to').value,
        role:     document.getElementById('h-role').value,
        device:   document.getElementById('h-device').value,
        country:  document.getElementById('h-country').value,
    });
    var tbody = document.getElementById('history-tbody');
    tbody.innerHTML = '<tr><td colspan="10" class="act-loading">Loading…</td></tr>';

    fetch('/api/activity?'+params.toString())
    .then(function(r){return r.json();})
    .then(function(d){
        if (!d.rows || !d.rows.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="act-empty">No login records found</td></tr>';
            document.getElementById('h-pagination-info').textContent = '0 results';
            document.getElementById('h-pagination-btns').innerHTML = '';
            return;
        }
        tbody.innerHTML = d.rows.map(function(r,i){
            var num = (currentPage-1)*25 + i + 1;
            return '<tr>'+
                '<td style="color:#9CA3AF;font-size:12px">'+num+'</td>'+
                '<td>'+
                    '<div style="font-weight:600;font-size:13px;color:#111827">'+esc(r.full_name||r.user_name||'—')+'</div>'+
                    '<div style="font-size:11px;color:#9CA3AF">'+esc(r.email||'')+'</div>'+
                    (r.affiliate_code?'<div style="font-size:11px;color:#4F46E5;font-family:monospace">'+esc(r.affiliate_code)+'</div>':'')+
                '</td>'+
                '<td>'+roleBadge(r.role)+'</td>'+
                '<td><code style="font-size:12px;background:#F8FAFC;padding:2px 6px;border-radius:4px;color:#374151">'+esc(r.ip_address)+'</code></td>'+
                '<td>'+
                    (r.country_code?'<img src="https://flagcdn.com/16x12/'+r.country_code.toLowerCase()+'.png" style="margin-right:5px;vertical-align:middle" onerror="this.style.display=\'none\'">':'')+
                    '<span style="font-size:13px">'+esc(r.city&&r.city!=='Unknown'?r.city+', ':'')+''+esc(r.country||'—')+'</span>'+
                '</td>'+
                '<td>'+deviceBadge(r.device_type)+'</td>'+
                '<td>'+
                    '<div style="font-size:13px;color:#374151">'+
                        ((r.platform_source === 'Android APK' || r.browser === 'Android App')
                            ? '<span style="color:#10B981;font-weight:700;display:inline-flex;align-items:center;gap:4px"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993.0004.5511-.4482.9997-.9993.9997zm-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993 0 .5511-.4482.9997-.9993.9997zm11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1517-.5676.4255.4255 0 00-.5753.1493l-2.029 3.5133A11.7584 11.7584 0 0012 7.7471c-1.8906 0-3.6593.4795-5.1228 1.3093L4.8482 5.5431a.426.426 0 00-.5753-.1493.415.415 0 00-.1517.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396z"/></svg> Android App</span>'
                            : esc(r.browser||'—'))+
                    '</div>'+
                    '<div style="font-size:11px;color:#9CA3AF">'+esc(r.os||'')+'</div>'+
                '</td>'+
                '<td style="white-space:nowrap;font-size:13px">'+esc(fmtDt(r.login_time))+'</td>'+
                '<td style="font-size:13px">'+esc(r.duration_sec ? fmtDur(parseInt(r.duration_sec)) : (r.is_active?'Active':'—'))+'</td>'+
                '<td>'+
                    (r.is_active
                        ? '<span class="act-badge active">● Online</span>'
                        : '<span class="act-badge ended">Ended</span>')+
                '</td>'+
            '</tr>';
        }).join('');

        // Pagination
        var start = (currentPage-1)*25+1, end = Math.min(currentPage*25, d.total);
        document.getElementById('h-pagination-info').textContent =
            'Showing '+start+'–'+end+' of '+fmt(d.total)+' records';
        renderPagination(d.total_pages, currentPage);
    }).catch(function(){
        document.getElementById('history-tbody').innerHTML='<tr><td colspan="10" class="act-empty">Error loading data</td></tr>';
    });
};

function renderPagination(totalPages, current){
    var el = document.getElementById('h-pagination-btns');
    var html = '';
    html += '<button class="act-page-btn" onclick="loadHistory('+Math.max(1,current-1)+')" '+(current<=1?'disabled':'')+'>‹ Prev</button>';
    var start = Math.max(1, current-2), end = Math.min(totalPages, current+2);
    if (start>1) html += '<button class="act-page-btn" onclick="loadHistory(1)">1</button>'+(start>2?'<span style="padding:0 4px;color:#D1D5DB">…</span>':'');
    for(var i=start;i<=end;i++){
        html += '<button class="act-page-btn'+(i===current?' active':'')+'" onclick="loadHistory('+i+')">'+i+'</button>';
    }
    if (end<totalPages) html += (end<totalPages-1?'<span style="padding:0 4px;color:#D1D5DB">…</span>':'')+'<button class="act-page-btn" onclick="loadHistory('+totalPages+')">'+totalPages+'</button>';
    html += '<button class="act-page-btn" onclick="loadHistory('+Math.min(totalPages,current+1)+')" '+(current>=totalPages?'disabled':'')+'>Next ›</button>';
    el.innerHTML = html;
}

window.resetFilters = function(){
    document.getElementById('h-search').value  = '';
    document.getElementById('h-from').value    = '';
    document.getElementById('h-to').value      = '';
    document.getElementById('h-role').value    = '';
    document.getElementById('h-device').value  = '';
    document.getElementById('h-country').value = '';
    loadHistory(1);
};

window.debounceLoad = function(){
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function(){ loadHistory(1); }, 500);
};

// Populate country filter
function loadCountryOptions(){
    fetch('/api/activity?action=login_logs&limit=1000&page=1')
    .then(function(r){return r.json();}).then(function(d){
        var seen = {}; var sel = document.getElementById('h-country');
        (d.rows||[]).forEach(function(r){
            if (r.country_code && !seen[r.country_code]) {
                seen[r.country_code]=1;
                var opt = document.createElement('option');
                opt.value=r.country_code; opt.textContent=r.country||r.country_code;
                sel.appendChild(opt);
            }
        });
    }).catch(function(){});
}

// ── Live ticker ──────────────────────────────────────────────────
function startLiveCountdown(){
    clearInterval(liveCountdown);
    liveCountdown = setInterval(function(){
        loadLive();
        loadStats();
    }, 3000);
}

window.refreshAll = function(){
    loadStats();
    loadLive();
    if (document.getElementById('panel-history').style.display!=='none') loadHistory(currentPage);
};

// ── Init ────────────────────────────────────────────────────────────────────
loadStats();
loadLive();
loadCountryOptions();
startLiveCountdown();

// Change select/date triggers
['h-from','h-to','h-role','h-device','h-country'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) el.addEventListener('change', function(){ loadHistory(1); });
});

})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
