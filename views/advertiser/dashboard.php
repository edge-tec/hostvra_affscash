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
<style>
.ap-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:18px;box-shadow:0 8px 28px -18px rgba(15,23,42,.18);}
.ap-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#F8FAFC,#EEF2FF);}
.ap-title{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:800;color:var(--text);}
.ap-title .ap-dot{width:8px;height:8px;border-radius:50%;background:#10B981;box-shadow:0 0 0 0 rgba(16,185,129,.55);animation:ap-pulse 1.6s infinite;}
@keyframes ap-pulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.55)}70%{box-shadow:0 0 0 8px rgba(16,185,129,0)}100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}}
.ap-range{font-size:11.5px;color:var(--text-muted);background:#fff;border:1px solid var(--border);border-radius:999px;padding:4px 12px;}
.ap-pills{display:flex;flex-wrap:wrap;gap:5px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:3px;}
.ap-pill{border:none;background:transparent;color:var(--text-muted);font-size:12px;font-weight:600;padding:6px 12px;border-radius:7px;cursor:pointer;white-space:nowrap;transition:background .15s,color .15s;}
.ap-pill:hover{background:#F1F5F9;color:var(--text);}
.ap-pill.active{background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;}
.ap-body{padding:18px 20px;}
.ap-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;}
.ap-kpi{position:relative;overflow:hidden;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px 14px;transition:transform .2s,box-shadow .2s;}
.ap-kpi:hover{transform:translateY(-2px);box-shadow:0 8px 20px -12px rgba(0,0,0,.15);}
.ap-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--ap-tint,#0891B2);}
.ap-kpi-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
.ap-kpi-lbl{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);}
.ap-kpi-ico{width:24px;height:24px;border-radius:6px;background:var(--ap-tint,#0891B2);color:#fff;display:flex;align-items:center;justify-content:center;}
.ap-kpi-ico svg{width:13px;height:13px;}
.ap-kpi-val{font-size:20px;font-weight:800;color:var(--text);line-height:1;font-variant-numeric:tabular-nums;}
.ap-kpi-delta{display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:2px 7px;border-radius:999px;margin-top:6px;}
.ap-kpi-delta.up{background:rgba(16,185,129,.13);color:#059669;}
.ap-kpi-delta.down{background:rgba(239,68,68,.13);color:#DC2626;}
.ap-kpi-delta.flat{background:var(--bg);color:var(--text-muted);}
.ap-kpi.clicks {--ap-tint:#3B82F6;}
.ap-kpi.conv   {--ap-tint:#10B981;}
.ap-kpi.revenue{--ap-tint:#8B5CF6;}
.ap-kpi.payout {--ap-tint:#F59E0B;}
.ap-controls{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
.ap-toggles{display:flex;flex-wrap:wrap;gap:5px;}
.ap-toggles button{border:1px solid var(--border);background:var(--bg);color:var(--text-muted);padding:5px 11px;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.ap-toggles button .dot{width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;}
.ap-toggles button.active{color:#fff;border-color:transparent;}
.ap-toggles button.active[data-m="clicks"] {background:#3B82F6;}
.ap-toggles button.active[data-m="conv"]   {background:#10B981;}
.ap-toggles button.active[data-m="revenue"]{background:#8B5CF6;}
.ap-toggles button.active[data-m="payout"] {background:#F59E0B;}
.ap-type{display:inline-flex;border:1px solid var(--border);border-radius:7px;overflow:hidden;background:var(--bg);}
.ap-type button{border:none;background:transparent;color:var(--text-muted);padding:6px 12px;font-size:11.5px;font-weight:600;cursor:pointer;}
.ap-type button.active{background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;}
.ap-chart-wrap{position:relative;height:300px;}
.ap-loading{position:absolute;inset:0;background:rgba(255,255,255,.55);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);display:none;align-items:center;justify-content:center;z-index:5;border-radius:8px;}
html[data-theme="dark"] .ap-loading{background:rgba(15,23,42,.55);}
.ap-loading.show{display:flex;}
.ap-spinner{width:24px;height:24px;border:3px solid var(--border);border-top-color:#0891B2;border-radius:50%;animation:ap-spin .65s linear infinite;}
@keyframes ap-spin{to{transform:rotate(360deg)}}
@media(max-width:900px){ .ap-kpis{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){
    .ap-head{padding:14px 14px;}
    .ap-body{padding:14px;}
    .ap-pills{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap;}
    .ap-pill{flex-shrink:0;}
    .ap-chart-wrap{height:260px;}
    .ap-kpi-val{font-size:18px;}
}
</style>

<div class="ap-card">
    <div class="ap-head">
        <div>
            <div class="ap-title"><span class="ap-dot"></span>Performance Analytics</div>
            <div style="margin-top:6px"><span class="ap-range" id="ap-range">—</span></div>
        </div>
        <div class="ap-pills" role="tablist" aria-label="Date range">
            <button class="ap-pill" data-preset="today">Today</button>
            <button class="ap-pill" data-preset="yesterday">Yesterday</button>
            <button class="ap-pill" data-preset="7d">7D</button>
            <button class="ap-pill" data-preset="15d">Last 15D</button>
            <button class="ap-pill active" data-preset="30d">30D</button>
            <button class="ap-pill" data-preset="90d">90D</button>
            <button class="ap-pill" data-preset="mtd">This Month</button>
            <button class="ap-pill" data-preset="lastmonth">Last Month</button>
        </div>
    </div>

    <div class="ap-body">
        <!-- KPIs -->
        <div class="ap-kpis">
            <div class="ap-kpi clicks">
                <div class="ap-kpi-row">
                    <span class="ap-kpi-lbl">Clicks</span>
                    <span class="ap-kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                </div>
                <div class="ap-kpi-val" id="ap-clicks">—</div>
                <div class="ap-kpi-delta flat" id="ap-clicks-delta">—</div>
            </div>
            <div class="ap-kpi conv">
                <div class="ap-kpi-row">
                    <span class="ap-kpi-lbl">Conversions</span>
                    <span class="ap-kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </div>
                <div class="ap-kpi-val" id="ap-conv">—</div>
                <div class="ap-kpi-delta flat" id="ap-conv-delta">—</div>
            </div>
            <div class="ap-kpi revenue">
                <div class="ap-kpi-row">
                    <span class="ap-kpi-lbl">Revenue</span>
                    <span class="ap-kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                </div>
                <div class="ap-kpi-val" id="ap-revenue">—</div>
                <div class="ap-kpi-delta flat" id="ap-revenue-delta">—</div>
            </div>
            <div class="ap-kpi payout">
                <div class="ap-kpi-row">
                    <span class="ap-kpi-lbl">Payout</span>
                    <span class="ap-kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></span>
                </div>
                <div class="ap-kpi-val" id="ap-payout">—</div>
                <div class="ap-kpi-delta flat" id="ap-payout-delta">—</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="ap-controls">
            <div class="ap-toggles" id="ap-metrics" title="Click to toggle each series">
                <button class="active" data-m="clicks"  style="color:#3B82F6"><span class="dot"></span>Clicks</button>
                <button class="active" data-m="conv"    style="color:#10B981"><span class="dot"></span>Conversions</button>
                <button class="active" data-m="revenue" style="color:#8B5CF6"><span class="dot"></span>Revenue</button>
                <button class="active" data-m="payout"  style="color:#F59E0B"><span class="dot"></span>Payout</button>
            </div>
            <div class="ap-type" id="ap-types">
                <button class="active" data-t="line">Line</button>
                <button data-t="bar">Bar</button>
            </div>
        </div>

        <!-- Chart -->
        <div class="ap-chart-wrap">
            <canvas id="ap-chart"></canvas>
            <div class="ap-loading" id="ap-loading"><div class="ap-spinner"></div></div>
        </div>
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
        var isLine = chartType === 'line';
        var dense = lastTrend.labels.length > 30;
        var single = keys.length === 1;

        var datasets = keys.map(function(k){
            var m = META[k];
            var data = lastTrend[(k === 'conv' ? 'conv' : k) + '_data'] || [];
            return {
                label: m.label + (m.isCur ? ' ($)' : ''),
                data: data,
                yAxisID: m.axis,
                borderColor: m.color,
                backgroundColor: isLine
                    ? (single ? hexA(m.color, .18) : hexA(m.color, .9))
                    : hexA(m.color, .80),
                borderWidth: isLine ? 2.5 : 1,
                pointRadius: dense ? 0 : 3,
                pointHoverRadius: 5,
                fill: isLine && single,
                tension: isLine ? .38 : 0,
                borderRadius: isLine ? 0 : 4,
                isCur: m.isCur
            };
        });

        var hasCount = keys.some(function(k){ return !META[k].isCur; });
        var hasCur   = keys.some(function(k){ return  META[k].isCur; });

        var cfg = {
            type: chartType,
            data: { labels: lastTrend.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 500, easing: 'easeOutQuart' },
                interaction: { mode:'index', intersect:false },
                plugins: {
                    legend: { display:true, position:'bottom', labels:{ boxWidth:10, padding:12, color:tickCol(), font:{size:11.5,weight:'600'} } },
                    tooltip: {
                        backgroundColor:'rgba(15,23,42,.92)', titleColor:'#fff', bodyColor:'#fff',
                        borderColor:'rgba(255,255,255,.08)', borderWidth:1, padding:10, cornerRadius:8,
                        callbacks: { label: function(ctx){
                            return ctx.dataset.label + ': ' + (ctx.dataset.isCur ? fmtCur(ctx.parsed.y) : fmtInt(ctx.parsed.y));
                        }}
                    }
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
            }
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
