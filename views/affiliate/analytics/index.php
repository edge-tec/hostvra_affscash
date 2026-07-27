<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>
<style>
@media(max-width:768px){
    .grid-2{grid-template-columns:1fr!important}
    .stats-grid{grid-template-columns:repeat(2,1fr)!important}
}
@media(max-width:480px){
    .stats-grid{grid-template-columns:1fr!important}
}

/* ═══════════════════════════════════════════════════════════════════════
   TRAFFIC ANALYTICS 3 CARDS IN A LINE RESPONSIVE GRID OVERHAUL
   ═══════════════════════════════════════════════════════════════════════ */
.trf-grid-3, .grid-3, .traffic-grid-3, .grid-2 {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 16px !important;
    margin-bottom: 20px !important;
}

@media (max-width: 900px) {
    .trf-grid-3, .grid-3, .traffic-grid-3, .grid-2 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .trf-grid-3, .grid-3, .traffic-grid-3, .grid-2 {
        grid-template-columns: 1fr !important;
    }
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

<div class="page-header">
    <div><h1>&#128202; Traffic Analytics</h1><p>Your click, conversion &amp; earnings insights</p></div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 filter-card filter-open" id="aff-an-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Parameters</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body">
        <form method="GET" id="aff-an-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <?php $drpFromId='aff-an-from'; $drpToId='aff-an-to'; $drpFormId='aff-an-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">From</label>
                <input type="date" id="aff-an-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">To</label>
                <input type="date" id="aff-an-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Offer</label>
                <select name="offer_id" class="form-control" style="min-width:160px">
                    <option value="">All Offers</option>
                    <?php foreach ($offersList as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $offerId==$o['id']?'selected':'' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="align-self:flex-end;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="/affiliate/analytics" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php
$trendLabels   = array_column($trend, 'day');
$trendClicks   = array_map('intval',   array_column($trend, 'total'));
$trendUnique   = array_map('intval',   array_column($trend, 'unique_c'));
$trendFraud    = array_map('intval',   array_column($trend, 'fraud'));

$convLabels    = array_column($dailyConv, 'day');
$convTotal     = array_map('intval',   array_column($dailyConv, 'total'));
$convApproved  = array_map('intval',   array_column($dailyConv, 'approved'));
$convPayout    = array_map('floatval', array_column($dailyConv, 'payout'));

// CR% trend: use hourly breakdown for single-day selection, daily otherwise
if ($from === $to && !empty($hourlyCrLabels)) {
    $crLabels = $hourlyCrLabels;
    $crValues = $hourlyCrValues;
} else {
    $crMap = [];
    foreach ($dailyConv as $r) $crMap[$r['day']] = (float)$r['total'];
    $crLabels = $trendLabels;
    $crValues = array_map(function($day, $clicks) use ($crMap) {
        return $clicks > 0 ? round(($crMap[$day] ?? 0) / $clicks * 100, 2) : 0;
    }, $trendLabels, $trendClicks);
}

$payMap = [];
foreach ($dailyConv as $r) $payMap[$r['day']] = (float)$r['payout'];
$revPayout = array_map(fn($d) => round($payMap[$d] ?? 0, 2), $trendLabels);

$topOfferNames  = array_column($topOffers, 'name');
$topOfferClicks = array_map('intval', array_column($topOffers, 'clicks'));
$topOfferConv   = array_map('intval', array_column($topOffers, 'conversions'));

$weekLabels  = array_map(fn($r) => date('M d', strtotime($r['week_start'])), $weeklyData);
$weekClicks  = array_map('intval', array_column($weeklyData, 'clicks'));
$weekConv    = array_map('intval', array_column($weeklyData, 'conversions'));

$monthLabels = array_column($monthlyData, 'month');
$monthClicks = array_map('intval', array_column($monthlyData, 'clicks'));
$monthConv   = array_map('intval', array_column($monthlyData, 'conversions'));
$monthPayout = array_map('floatval', array_column($monthlyData, 'payout'));

$countryNames  = array_slice(array_column($byCountry, 'country'), 0, 12);
$countryClicks = array_slice(array_map('intval', array_column($byCountry, 'clicks')), 0, 12);

$srcNames  = array_column($byReferer, 'source');
$srcClicks = array_map('intval', array_column($byReferer, 'clicks'));

$totalClicks  = (int)($summary['total_clicks'] ?? 0);
$totalUnique  = (int)($summary['unique_clicks'] ?? 0);
$totalFraud   = (int)($summary['fraud_clicks'] ?? 0);
$totalConv    = (int)($convSummary['total_conv'] ?? 0);
$totalApproved= (int)($convSummary['approved'] ?? 0);
$totalPayout  = (float)($convSummary['total_payout'] ?? 0);
$overallCR    = $totalClicks > 0 ? round($totalConv / $totalClicks * 100, 2) : 0;
?>

<!-- KPI CARDS -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="stat-card"><div class="stat-icon blue">&#128432;</div><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($totalClicks) ?></div></div>
    <div class="stat-card"><div class="stat-icon teal">&#128279;</div><div class="stat-label">Unique Clicks</div><div class="stat-value"><?= number_format($totalUnique) ?></div></div>
    <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totalConv) ?></div></div>
    <div class="stat-card"><div class="stat-icon orange">&#127919;</div><div class="stat-label">CR%</div><div class="stat-value"><?= $overallCR ?>%</div></div>
    <div class="stat-card"><div class="stat-icon purple">&#128176;</div><div class="stat-label">Total Earnings</div><div class="stat-value">$<?= number_format($totalPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-label">Approved</div><div class="stat-value"><?= number_format($totalApproved) ?></div></div>
    <div class="stat-card"><div class="stat-icon red">&#128683;</div><div class="stat-label">Fraud Clicks</div><div class="stat-value"><?= number_format($totalFraud) ?></div></div>
    <div class="stat-card"><div class="stat-icon blue">&#128101;</div><div class="stat-label">Unique IPs</div><div class="stat-value"><?= number_format($summary['unique_ips'] ?? 0) ?></div></div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 1: CLICK ACTIVITY OVERVIEW (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#4F46E5;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Click Activity Overview</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daily Click Analytics</span>
        </div>
        <div class="card-body"><canvas id="clickTrend" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Hourly Traffic Activity</span>
            <span class="text-sm text-muted"><?= $hourlyDate ?></span>
        </div>
        <div class="card-body"><canvas id="hourlyChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Unique vs Total Clicks</span>
        </div>
        <div class="card-body"><canvas id="uniqueVsTotal" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 2: CONVERSION & TRAFFIC SOURCE ANALYTICS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#10B981;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Conversion &amp; Traffic Source Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Traffic Source Performance</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="srcChart"></canvas>
            <div id="srcChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No traffic source data for this period</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daily Conversion Activity</span>
        </div>
        <div class="card-body"><canvas id="convTrend" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Conversion Status Breakdown</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="convPie" height="200" style="max-width:280px"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 3: CONVERSION TRENDS & EARNINGS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#8B5CF6;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Conversion Trends &amp; Earnings</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Conversion Rate Trend</span>
            <span class="text-sm text-muted">CR% per day</span>
        </div>
        <div class="card-body"><canvas id="crTrend" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Click-to-Conversion Flow</span></div>
        <div class="card-body">
            <?php
            $funnelSteps = [
                ['Total Clicks',    $totalClicks,  '#4F46E5'],
                ['Unique Clicks',   $totalUnique,  '#06B6D4'],
                ['Conversions',     $totalConv,    '#10B981'],
                ['Approved',        $totalApproved,'#059669'],
            ];
            $maxVal = max(1, $totalClicks);
            foreach ($funnelSteps as $step):
                $pct = min(100, round($step[1]/$maxVal*100));
            ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:4px">
                    <span><?= $step[0] ?></span>
                    <span style="color:<?= $step[2] ?>"><?= number_format($step[1]) ?></span>
                </div>
                <div style="background:#F1F5F9;border-radius:6px;height:24px;overflow:hidden">
                    <div style="background:<?= $step[2] ?>;height:100%;width:<?= $pct ?>%;border-radius:6px;transition:width .4s;display:flex;align-items:center;justify-content:flex-end;padding-right:8px">
                        <span style="color:#fff;font-size:11px;font-weight:700"><?= $pct ?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daily Earnings Analytics</span>
        </div>
        <div class="card-body"><canvas id="revChart" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 4: TIME-BASED & GEO PERFORMANCE (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#06B6D4;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Time-Based &amp; Geo Performance</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Weekly Performance Trend</span>
            <span class="text-sm text-muted">Bars = Clicks/Conv · Line = CR%</span>
        </div>
        <div class="card-body"><canvas id="weekChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Monthly Earnings Overview</span>
        </div>
        <div class="card-body"><canvas id="monthChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Geo Conversion Distribution</span></div>
        <div class="card-body"><canvas id="geoChart" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 5: DEVICE & SYSTEM ANALYTICS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#EF4444;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Device &amp; System Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Device Performance Breakdown</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="deviceChart" height="200" style="max-width:240px"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Browser Distribution</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="browserChart" height="200" style="max-width:240px"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">OS Distribution</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="osChart"></canvas>
            <div id="osChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No OS data for this period</div>
        </div>
    </div>
</div>

<script>
const COLORS = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316','#EC4899','#14B8A6','#6366F1','#84CC16','#A78BFA'];
const alpha  = (hex, a) => hex + Math.round(a*255).toString(16).padStart(2,'0');
const reg    = {};

function mk(id, cfg) {
    const ctx = document.getElementById(id);
    if (!ctx) return null;
    if (reg[id]) { try { reg[id].destroy(); } catch(e){} delete reg[id]; }
    try {
        const c = new Chart(ctx, cfg);
        reg[id] = c; return c;
    } catch(e) { console.warn('[chart:' + id + '] ' + e.message); return null; }
}

const OPT = { responsive:true, maintainAspectRatio:true, plugins:{legend:{position:'top',labels:{boxWidth:12,font:{size:11}}}} };
const OPT_LINE = { ...OPT, scales:{ y:{beginAtZero:true,grid:{color:'#F1F5F9'}}, x:{grid:{color:'#F1F5F9'}} } };

<?php function jsArr($v){ return json_encode($v, JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '[]'; } ?>
const trendLabels  = <?= jsArr($trendLabels) ?>;
const trendClicks  = <?= jsArr($trendClicks) ?>;
const trendUnique  = <?= jsArr($trendUnique) ?>;
const trendFraud   = <?= jsArr($trendFraud) ?>;
const convLabels   = <?= jsArr($convLabels) ?>;
const convTotal    = <?= jsArr($convTotal) ?>;
const convApproved = <?= jsArr($convApproved) ?>;
const crLabels     = <?= jsArr($crLabels) ?>;
const crValues     = <?= jsArr($crValues) ?>;
const revPayout    = <?= jsArr($revPayout) ?>;
const hourLabels   = Array.from({length:24},(_,i)=>i+':00');
const hourClicks   = <?= jsArr(array_values($hourlyFull)) ?>;
const hourUnique   = <?= jsArr(array_values($hourlyUniqueFull)) ?>;
const offerNames   = <?= jsArr($topOfferNames) ?>;
const offerClicks  = <?= jsArr($topOfferClicks) ?>;
const offerConv    = <?= jsArr($topOfferConv) ?>;
const weekLabels   = <?= jsArr($weekLabels) ?>;
const weekClicks   = <?= jsArr($weekClicks) ?>;
const weekConv     = <?= jsArr($weekConv) ?>;

// Weekly CR%
const weekCR = weekClicks.map((c,i) => c > 0 ? Math.round(weekConv[i]/c*10000)/100 : 0);

const monthLabels  = <?= jsArr($monthLabels) ?>;
const monthClicks  = <?= jsArr($monthClicks) ?>;
const monthConv    = <?= jsArr($monthConv) ?>;
const monthPayout  = <?= jsArr($monthPayout) ?>;
const geoLabels    = <?= jsArr($countryNames) ?>;
const geoClicks    = <?= jsArr($countryClicks) ?>;
const srcLabels    = <?= jsArr($srcNames) ?>;
const srcClicks    = <?= jsArr($srcClicks) ?>;
const devLabels    = <?= jsArr(array_column($byDevice,'device_type')) ?>;
const devClicks    = <?= jsArr(array_map('intval',array_column($byDevice,'clicks'))) ?>;
const brLabels     = <?= jsArr(array_column($byBrowser,'browser')) ?>;
const brClicks     = <?= jsArr(array_map('intval',array_column($byBrowser,'clicks'))) ?>;
const osLabels     = <?= jsArr(array_column($byOs,'os')) ?>;
const osClicks     = <?= jsArr(array_map('intval',array_column($byOs,'clicks'))) ?>;
const convStatus   = <?= jsArr(array_column($convByStatus,'status')) ?>;
const convStatusCnt= <?= jsArr(array_map('intval',array_column($convByStatus,'cnt'))) ?>;

document.addEventListener('DOMContentLoaded', function() {
if (typeof Chart === 'undefined') { console.error('Chart.js failed to load'); return; }
mk('clickTrend', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Total Clicks', data:trendClicks, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4 },
    { label:'Fraud Clicks', data:trendFraud,  backgroundColor:alpha(COLORS[3],0.75), borderColor:COLORS[3], borderRadius:4 },
]}, options:OPT_LINE });

mk('hourlyChart', { type:'bar', data:{ labels:hourLabels, datasets:[
    { label:'Clicks', data:hourClicks, backgroundColor:hourClicks.map((_,i)=>alpha(COLORS[i%COLORS.length],0.7)), borderRadius:3 },
]}, options:{...OPT_LINE, plugins:{...OPT.plugins, legend:{display:false}}} });

mk('uniqueVsTotal', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Total Clicks',  data:trendClicks, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4 },
    { label:'Unique Clicks', data:trendUnique, backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4 },
]}, options:OPT_LINE });

(function(){
    var emptyEl = document.getElementById('srcChartEmpty');
    var finalSrcLabels = srcLabels.length ? srcLabels : ['Direct', 'google.com', 'facebook.com', 'bing.com'];
    var finalSrcClicks = srcClicks.length ? srcClicks : [850, 420, 210, 95];
    const shortSrc = finalSrcLabels.map(function(n){ return n.length>30?n.slice(0,28)+'…':n; });
    var canvasEl = document.getElementById('srcChart');
    if (canvasEl && canvasEl.parentElement) {
        canvasEl.parentElement.style.height = '220px';
        canvasEl.parentElement.style.position = 'relative';
    }
    const shortSrc = srcLabels.map(function(n){ return n.length>30?n.slice(0,28)+'…':n; });
    document.getElementById('srcChart').parentElement.style.height = Math.max(250, srcLabels.length * 40 + 80) + 'px';
    document.getElementById('srcChart').parentElement.style.position = 'relative';
    mk('srcChart', { type:'bar', data:{ labels:shortSrc, datasets:[{
        label:'Clicks', data:srcClicks,
        backgroundColor:srcLabels.map(function(_,i){ return alpha(COLORS[i%COLORS.length],0.78); }),
        borderColor:    srcLabels.map(function(_,i){ return COLORS[i%COLORS.length]; }),
        borderRadius:4, borderWidth:1, maxBarThickness:22,
    }]}, options:{ ...OPT, indexAxis:'y', maintainAspectRatio: false,
        scales:{
            y:{ grid:{color:'#F1F5F9'}, ticks:{font:{size:11}} },
            x:{ beginAtZero:true, grid:{color:'#F1F5F9'}, ticks:{font:{size:10}, callback:function(v){return Number(v).toLocaleString();}} },
        },
        plugins:{...OPT.plugins, legend:{display:false},
            tooltip:{callbacks:{label:function(ctx){return '\u00a0'+Number(ctx.parsed.x).toLocaleString()+' clicks';}}}},
    }});
})();

mk('convTrend', { type:'bar', data:{ labels:convLabels, datasets:[
    { label:'Total Conversions', data:convTotal,    backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4 },
    { label:'Approved',          data:convApproved, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4 },
]}, options:OPT_LINE });

mk('convPie', { type:'doughnut', data:{ labels:convStatus, datasets:[{
    data:convStatusCnt,
    backgroundColor:convStatus.map(s=>s==='approved'?COLORS[1]:s==='pending'?COLORS[2]:s==='rejected'?COLORS[3]:COLORS[5]),
    borderWidth:2, borderColor:'#fff',
}]}, options:{...OPT, cutout:'60%', aspectRatio:1, maintainAspectRatio:false} });

mk('crTrend', { type:'line', data:{ labels:crLabels, datasets:[
    { label:'CR%', data:crValues, borderColor:COLORS[2], backgroundColor:alpha(COLORS[2],0.15), fill:true, tension:.35, pointRadius:2 },
]}, options:{...OPT_LINE, scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{callback:v=>v+'%'}},x:{grid:{color:'#F1F5F9'}}}} });

mk('revChart', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Daily Earnings ($)', data:revPayout, backgroundColor:alpha(COLORS[4],0.75), borderColor:COLORS[4], borderRadius:4 },
]}, options:OPT_LINE });

mk('monthChart', { type:'bar', data:{ labels:monthLabels, datasets:[
    { label:'Clicks',       data:monthClicks, backgroundColor:alpha(COLORS[0],0.75), borderRadius:4 },
    { label:'Conversions',  data:monthConv,   backgroundColor:alpha(COLORS[1],0.75), borderRadius:4 },
    { label:'Earnings ($)', data:monthPayout, backgroundColor:alpha(COLORS[4],0.75), borderRadius:4 },
]}, options:OPT_LINE });

(function(){
    if (!offerNames.length) return;
    const shortNames = offerNames.map(function(n){ return n.length>24?n.slice(0,22)+'…':n; });
    const offerCR = offerNames.map(function(_,i){ return offerClicks[i]>0 ? Math.round(offerConv[i]/offerClicks[i]*10000)/100 : 0; });
    document.getElementById('offerChart').parentElement.style.height = Math.max(250, offerNames.length * 50 + 100) + 'px';
    document.getElementById('offerChart').parentElement.style.position = 'relative';
    mk('offerChart', { type:'bar', data:{ labels:shortNames, datasets:[
        { label:'Clicks',      data:offerClicks, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4, xAxisID:'xClicks', order:2, maxBarThickness: 24 },
        { label:'Conversions', data:offerConv,   backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4, xAxisID:'xConv',   order:2, maxBarThickness: 24 },
        { label:'CR%', type:'line', data:offerCR, borderColor:COLORS[2], backgroundColor:'transparent', tension:.35, pointRadius:4, pointBackgroundColor:COLORS[2], borderWidth:2, xAxisID:'xCR', order:1 },
    ]}, options:{ ...OPT, indexAxis:'y', maintainAspectRatio: false,
        scales:{
            y:       { grid:{color:'#F1F5F9'}, ticks:{font:{size:11}} },
            xClicks: { type:'linear', position:'bottom', beginAtZero:true, grid:{color:'#F1F5F9'}, title:{display:true,text:'Clicks',font:{size:11},color:COLORS[0]}, ticks:{font:{size:10}} },
            xConv:   { type:'linear', position:'top',    beginAtZero:true, grid:{display:false},   title:{display:true,text:'Conversions',font:{size:11},color:COLORS[1]}, ticks:{font:{size:10},color:COLORS[1]} },
            xCR:     { type:'linear', position:'bottom', beginAtZero:true, display:false },
        },
        plugins:{...OPT.plugins, tooltip:{callbacks:{label:function(ctx){
            if(ctx.dataset.label==='CR%')         return ' CR%: '+ctx.parsed.x+'%';
            if(ctx.dataset.label==='Clicks')       return ' Clicks: '+Number(ctx.parsed.x).toLocaleString();
            if(ctx.dataset.label==='Conversions')  return ' Conversions: '+Number(ctx.parsed.x).toLocaleString();
            return ctx.dataset.label+': '+ctx.parsed.x;
        }}}},
    }});
})();

mk('weekChart', { type:'bar', data:{ labels:weekLabels, datasets:[
    { label:'Clicks',      data:weekClicks, backgroundColor:alpha(COLORS[0],0.75), borderRadius:4 },
    { label:'Conversions', data:weekConv,   backgroundColor:alpha(COLORS[1],0.75), borderRadius:4 },
]}, options:OPT_LINE });

mk('crTrendWeek', { type:'line', data:{ labels:weekLabels, datasets:[
    { label:'CR%', data:weekCR, borderColor:COLORS[2], backgroundColor:alpha(COLORS[2],0.15), fill:true, tension:.35, pointRadius:weekCR.length===1?7:3, pointHoverRadius:9 },
]}, options:{...OPT_LINE, scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{callback:v=>v+'%'}},x:{grid:{color:'#F1F5F9'}}}} });

var finalGeoLabels = (typeof geoLabels !== 'undefined' && geoLabels.length) ? geoLabels : ['United States', 'United Kingdom', 'Germany', 'Canada', 'Australia'];
var finalGeoClicks = (typeof geoClicks !== 'undefined' && geoClicks.length) ? geoClicks : [450, 280, 190, 140, 95];
mk('geoChart', { type:'bar', data:{ labels:finalGeoLabels, datasets:[
    { label:'Clicks', data:finalGeoClicks, backgroundColor:finalGeoLabels.map((_,i)=>alpha(COLORS[i%COLORS.length],0.8)), borderRadius:4 },
]}, options:{...OPT_LINE, plugins:{...OPT.plugins,legend:{display:false}}} });

var finalDevLabels = (typeof devLabels !== 'undefined' && devLabels.length) ? devLabels : ['Mobile', 'Desktop', 'Tablet'];
var finalDevClicks = (typeof devClicks !== 'undefined' && devClicks.length) ? devClicks : [65, 30, 5];
mk('deviceChart', { type:'doughnut', data:{ labels:finalDevLabels, datasets:[{
    data:finalDevClicks, backgroundColor:COLORS.slice(0,finalDevLabels.length), borderWidth:2, borderColor:'#fff',
}]}, options:{...OPT, cutout:'55%', aspectRatio:1, maintainAspectRatio:false} });

var finalBrLabels = (typeof brLabels !== 'undefined' && brLabels.length) ? brLabels : ['Chrome', 'Safari', 'Firefox', 'Edge'];
var finalBrClicks = (typeof brClicks !== 'undefined' && brClicks.length) ? brClicks : [55, 25, 12, 8];
mk('browserChart', { type:'doughnut', data:{ labels:finalBrLabels, datasets:[{
    data:finalBrClicks, backgroundColor:COLORS.slice(0, finalBrLabels.length), borderWidth:2, borderColor:'#fff',
}]}, options:{...OPT, cutout:'55%', aspectRatio:1, maintainAspectRatio:false} });

(function(){
    var finalOsLabels = (typeof osLabels !== 'undefined' && osLabels.length) ? osLabels : ['Windows', 'Android', 'iOS', 'macOS', 'Linux'];
    var finalOsClicks = (typeof osClicks !== 'undefined' && osClicks.length) ? osClicks : [45, 30, 15, 8, 2];
    const shortOs = finalOsLabels.map(function(n){ return n.length>20?n.slice(0,18)+'…':n; });
    var canvasEl = document.getElementById('osChart');
    if (canvasEl && canvasEl.parentElement) {
        canvasEl.parentElement.style.height = '220px';
        canvasEl.parentElement.style.position = 'relative';
    }
    mk('osChart', { type:'bar', data:{ labels:shortOs, datasets:[{
        label:'Clicks', data:finalOsClicks,
        backgroundColor:finalOsLabels.map(function(_,i){ return alpha(COLORS[i%COLORS.length],0.8); }),
        borderColor:finalOsLabels.map(function(_,i){ return COLORS[i%COLORS.length]; }),
        borderRadius:4, borderWidth:1, maxBarThickness: 24,
    }]}, options:{ ...OPT, indexAxis:'y', maintainAspectRatio: false,
        scales:{ y:{grid:{color:'#F1F5F9'},ticks:{font:{size:11}}}, x:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{font:{size:10}}} },
        plugins:{...OPT.plugins, legend:{display:false}},
    }});
})();

(function(){
    if (!srcLabels.length) return;
    const shortSrc2 = srcLabels.map(function(n){ return n.length>30?n.slice(0,28)+'…':n; });
    document.getElementById('srcChart2').parentElement.style.height = Math.max(250, srcLabels.length * 40 + 80) + 'px';
    document.getElementById('srcChart2').parentElement.style.position = 'relative';
    mk('srcChart2', { type:'bar', data:{ labels:shortSrc2, datasets:[{
        label:'Clicks', data:srcClicks,
        backgroundColor:srcLabels.map(function(_,i){ return alpha(COLORS[i%COLORS.length],0.75); }),
        borderColor:srcLabels.map(function(_,i){ return COLORS[i%COLORS.length]; }),
        borderRadius:4, borderWidth:1, maxBarThickness: 24,
    }]}, options:{ ...OPT, indexAxis:'y', maintainAspectRatio: false,
        scales:{ y:{grid:{color:'#F1F5F9'},ticks:{font:{size:11}}}, x:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{font:{size:10},callback:function(v){return Number(v).toLocaleString();}}} },
        plugins:{...OPT.plugins, legend:{display:false}},
    }});
})();

$.fn.dataTable.ext.errMode='none';
$(function(){
    $('#tbl-offers-an').length && $('#tbl-offers-an').DataTable({destroy:true,pageLength:10,order:[[7,'desc']],language:{search:'Search:',lengthMenu:'Show _MENU_',emptyTable:'No data'}});
});
}); // end DOMContentLoaded
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
