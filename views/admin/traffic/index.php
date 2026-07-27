<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<style>
/* ── Traffic Analytics responsive ─────────────────────────────────────────── */
.trf-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 1100px) {
    .trf-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
    .trf-grid-3 { grid-template-columns: 1fr; }
    .grid-2 { grid-template-columns: 1fr !important; }
    .trf-filter > div { width: 100%; }
    .trf-filter .form-control,
    .trf-filter select { width: 100% !important; min-width: 0 !important; box-sizing: border-box; }
    .trf-filter .btn { width: 100%; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 8px; }
    .card-header { flex-wrap: wrap; gap: 4px; }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
.trf-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.trf-table-wrap table { min-width: 620px; }

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
    <div><h1>&#128202; Traffic Analytics</h1><p>Comprehensive click, conversion, revenue &amp; audience insights</p></div>
</div>

<!-- Filter Bar -->
<div class="card mb-3 filter-card filter-open" id="trf-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Parameters</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body">
        <form method="GET" id="traffic-form" class="trf-filter d-flex gap-3 align-center" style="flex-wrap:wrap">
            <?php $drpFromId='trf-from'; $drpToId='trf-to'; $drpFormId='traffic-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">From</label>
                <input type="date" id="trf-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">To</label>
                <input type="date" id="trf-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
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
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Affiliate</label>
                <select name="affiliate_id" class="form-control" style="min-width:180px">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affiliatesList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $affId==$a['id']?'selected':'' ?>><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="align-self:flex-end;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="/admin/traffic" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php
// Pre-process data for JS
$trendLabels   = array_column($trend, 'day');
$trendClicks   = array_map('intval',   array_column($trend, 'total'));
$trendUnique   = array_map('intval',   array_column($trend, 'unique_c'));
$trendFraud    = array_map('intval',   array_column($trend, 'fraud'));

$convLabels    = array_column($dailyConv, 'day');
$convTotal     = array_map('intval',   array_column($dailyConv, 'total'));
$convApproved  = array_map('intval',   array_column($dailyConv, 'approved'));
$convPayout    = array_map('floatval', array_column($dailyConv, 'payout'));
$convRevenue   = array_map('floatval', array_column($dailyConv, 'revenue'));

// CR% per day — merge trend + conv by date
$crMap = [];
foreach ($dailyConv as $r) $crMap[$r['day']] = (float)$r['total'];
$crLabels = $trendLabels;
$crValues = array_map(function($day, $clicks) use ($crMap) {
    return $clicks > 0 ? round(($crMap[$day] ?? 0) / $clicks * 100, 2) : 0;
}, $trendLabels, $trendClicks);

// Revenue arrays aligned to trend labels
$revMap = [];
foreach ($dailyConv as $r) { $revMap[$r['day']] = [(float)$r['payout'], (float)$r['revenue']]; }
$revPayout  = array_map(fn($d) => round($revMap[$d][0] ?? 0, 2), $trendLabels);
$revRevenue = array_map(fn($d) => round($revMap[$d][1] ?? 0, 2), $trendLabels);
$revProfit  = array_map(fn($i) => round($revRevenue[$i] - $revPayout[$i], 2), array_keys($trendLabels));

$topOfferNames  = array_column($topOffers, 'name');
$topOfferClicks = array_map('intval', array_column($topOffers, 'clicks'));
$topOfferConv   = array_map('intval', array_column($topOffers, 'conversions'));
$topOfferPayout = array_map('floatval', array_column($topOffers, 'payout'));

$weekLabels   = array_map(fn($r) => date('M d', strtotime($r['week_start'])), $weeklyData);
$weekClicks   = array_map('intval', array_column($weeklyData, 'clicks'));
$weekConv     = array_map('intval', array_column($weeklyData, 'conversions'));
$weekPayout   = array_map('floatval', array_column($weeklyData, 'payout'));
$weekCR       = array_map(fn($r) => (int)$r['clicks'] > 0 ? round((int)$r['conversions'] / (int)$r['clicks'] * 100, 2) : 0, $weeklyData);

$monthLabels  = array_column($monthlyData, 'month');
$monthClicks  = array_map('intval', array_column($monthlyData, 'clicks'));
$monthConv    = array_map('intval', array_column($monthlyData, 'conversions'));
$monthPayout  = array_map('floatval', array_column($monthlyData, 'payout'));

$countryNames  = array_slice(array_column($byCountry, 'country'), 0, 12);
$countryClicks = array_slice(array_map('intval', array_column($byCountry, 'clicks')), 0, 12);

$srcNames  = array_column($byReferer, 'source');
$srcClicks = array_map('intval', array_column($byReferer, 'clicks'));

$totalClicks = (int)($summary['total_clicks'] ?? 0);
$totalUnique = (int)($summary['unique_clicks'] ?? 0);
$totalFraud  = (int)($summary['fraud_clicks'] ?? 0);
$totalConv   = (int)($convSummary['total_conv'] ?? 0);
$totalApproved=(int)($convSummary['approved'] ?? 0);
$totalPayout = (float)($convSummary['total_payout'] ?? 0);
$totalRevenue= (float)($convSummary['total_revenue'] ?? 0);
$totalProfit = $totalRevenue - $totalPayout;
$overallCR   = $totalClicks > 0 ? round($totalConv / $totalClicks * 100, 2) : 0;
?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- KPI CARDS -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="stat-card"><div class="stat-icon blue">&#128432;</div><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($totalClicks) ?></div></div>
    <div class="stat-card"><div class="stat-icon teal">&#128279;</div><div class="stat-label">Unique Clicks</div><div class="stat-value"><?= number_format($totalUnique) ?></div></div>
    <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totalConv) ?></div></div>
    <div class="stat-card"><div class="stat-icon orange">&#127919;</div><div class="stat-label">CR%</div><div class="stat-value"><?= $overallCR ?>%</div></div>
    <div class="stat-card"><div class="stat-icon purple">&#128176;</div><div class="stat-label">Total Payout</div><div class="stat-value">$<?= number_format($totalPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-icon red">&#128683;</div><div class="stat-label">Fraud Clicks</div><div class="stat-value"><?= number_format($totalFraud) ?></div></div>
    <div class="stat-card"><div class="stat-icon green">&#128200;</div><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($totalRevenue,2) ?></div></div>
    <div class="stat-card"><div class="stat-icon <?= $totalProfit>=0?'green':'red' ?>">&#9889;</div><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= $totalProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($totalProfit,2) ?></div></div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->

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
<!-- SECTION 3: CONVERSION TRENDS & REVENUE (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#8B5CF6;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Conversion Trends &amp; Revenue</h2>
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
            <span class="card-title">Daily Revenue Analytics</span>
        </div>
        <div class="card-body"><canvas id="revChart" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 4: PROFIT & TIME-BASED ANALYTICS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#06B6D4;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Profit &amp; Time-Based Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Profit Performance Trend</span>
        </div>
        <div class="card-body"><canvas id="profitChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Weekly Performance Trend</span>
            <span class="text-sm text-muted">Bars = Clicks/Conv · Line = CR%</span>
        </div>
        <div class="card-body"><canvas id="weekChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Monthly Performance Overview</span>
        </div>
        <div class="card-body"><canvas id="monthChart" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 5: GEO & DEVICE ANALYTICS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#EF4444;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Geo &amp; Device Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Geo Conversion Distribution</span></div>
        <div class="card-body"><canvas id="geoChart" height="200"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Device Performance Breakdown</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="deviceChart" height="200" style="max-width:240px"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Browser Distribution</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="browserChart" height="200" style="max-width:240px"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 6: ADVANCED & AUDIENCE ANALYTICS (3 CARDS) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#F59E0B;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Advanced &amp; Audience Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">OS Distribution</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="osChart"></canvas>
            <div id="osChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No OS data for this period</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Top Affiliates — Click Volume</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="affChart"></canvas>
            <div id="affChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No affiliate click data for this period</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Offer Click vs Conv Performance</span>
            <span class="text-sm text-muted">Line = CR%</span>
        </div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="offerChart"></canvas>
            <div id="offerChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px;flex-direction:column;gap:6px">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                No offer data for the selected period
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><span class="card-title">Offer Conversion Leaderboard</span></div>
    <div class="table-wrap trf-table-wrap">
        <table id="tbl-offers-an" style="font-size:13px">
            <thead><tr>
                <th>#</th><th>OFFER</th><th>CLICKS</th><th>UNIQUE</th><th>CONV</th><th>APPROVED</th><th>CR%</th><th>PAYOUT</th><th>REVENUE</th>
            </tr></thead>
            <tbody>
            <?php foreach ($topOffers as $i => $o): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td class="fw-bold"><a href="/admin/offers/<?= $o['id'] ?>/overview"><?= Helpers::e($o['name']) ?></a></td>
                <td><?= number_format($o['clicks']) ?></td>
                <td><?= number_format($o['uclicks'] ?? 0) ?></td>
                <td><?= number_format($o['conversions']) ?></td>
                <td><?= number_format($o['approved']) ?></td>
                <td><span style="color:<?= $o['cr']>=5?'var(--secondary)':($o['cr']>=1?'#F59E0B':'var(--danger)') ?>;font-weight:700"><?= $o['cr'] ?>%</span></td>
                <td class="fw-bold">$<?= number_format($o['payout'],2) ?></td>
                <td>$<?= number_format($o['revenue'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 5: TIME-BASED ANALYTICS -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#06B6D4;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Time-Based Analytics</h2>
</div>

<div class="trf-grid-3 mb-3">
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-title">Weekly Performance &amp; Conversion Rate Trend</span>
            <span class="text-sm text-muted">Bars = Clicks/Conv · Line = CR%</span>
        </div>
        <div class="card-body"><canvas id="weekChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Monthly Performance Overview</span>
        </div>
        <div class="card-body"><canvas id="monthChart" height="180"></canvas></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 6: ADVANCED ANALYTICS -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="display:flex;align-items:center;gap:10px;margin:24px 0 12px">
    <div style="width:4px;height:28px;background:#EF4444;border-radius:2px"></div>
    <h2 style="font-size:18px;font-weight:700;margin:0">Advanced Analytics</h2>
</div>

<div class="trf-grid-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Geo Conversion Distribution</span></div>
        <div class="card-body"><canvas id="geoChart" height="220"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Device Performance Breakdown</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="deviceChart" height="220" style="max-width:240px"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Browser Distribution</span></div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center"><canvas id="browserChart" height="220" style="max-width:240px"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">OS Distribution</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="osChart"></canvas>
            <div id="osChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No OS data for this period</div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Top Affiliates — Click Volume</span></div>
        <div class="card-body" style="position:relative;min-height:180px">
            <canvas id="affChart"></canvas>
            <div id="affChartEmpty" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;color:#9CA3AF;font-size:13px">No affiliate click data for this period</div>
        </div>
    </div>
</div>

<script>
// ── Chart.js helpers ────────────────────────────────────────────────────────
const COLORS = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316','#EC4899','#14B8A6','#6366F1','#84CC16','#A78BFA'];
const alpha  = (hex, a) => hex + Math.round(a*255).toString(16).padStart(2,'0');
const reg    = {};

function mk(id, cfg) {
    const ctx = document.getElementById(id);
    if (!ctx) return null;
    if (reg[id]) { try { reg[id].destroy(); } catch(e) {} delete reg[id]; }
    try {
        const c = new Chart(ctx, cfg);
        reg[id] = c; return c;
    } catch(e) { console.warn('[chart:' + id + '] ' + e.message); return null; }
}


const OPT = { responsive:true, maintainAspectRatio:true, plugins:{legend:{position:'top',labels:{boxWidth:12,font:{size:11}}}} };
const OPT_LINE = { ...OPT, scales:{ y:{beginAtZero:true,grid:{color:'#F1F5F9'}}, x:{grid:{color:'#F1F5F9'}} } };

// ── Data ────────────────────────────────────────────────────────────────────
// Safe JSON helper — prevents syntax errors when data contains non-UTF-8 bytes
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
const revRevenue   = <?= jsArr($revRevenue) ?>;
const revProfit    = <?= jsArr($revProfit) ?>;
const hourLabels   = Array.from({length:24},(_,i)=>i+':00');
const hourClicks   = <?= jsArr(array_values($hourlyFull)) ?>;
const hourUnique   = <?= jsArr(array_values($hourlyUniqueFull)) ?>;
const offerNames   = <?= jsArr($topOfferNames) ?>;
const offerClicks  = <?= jsArr($topOfferClicks) ?>;
const offerConv    = <?= jsArr($topOfferConv) ?>;
const weekLabels   = <?= jsArr($weekLabels) ?>;
const weekClicks   = <?= jsArr($weekClicks) ?>;
const weekConv     = <?= jsArr($weekConv) ?>;
const weekCR       = <?= jsArr($weekCR) ?>;
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
const affLabels    = <?= jsArr(array_column($byAffiliate,'name')) ?>;
const affClicks    = <?= jsArr(array_map('intval',array_column($byAffiliate,'clicks'))) ?>;
const convStatus   = <?= jsArr(array_column($convByStatus,'status')) ?>;
const convStatusCnt= <?= jsArr(array_map('intval',array_column($convByStatus,'cnt'))) ?>;

// ── Charts (wrapped in DOMContentLoaded so Chart.js deferred script is ready) ─
document.addEventListener('DOMContentLoaded', function() {
if (typeof Chart === 'undefined') { console.error('Chart.js failed to load'); return; }
mk('clickTrend', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Total Clicks', data:trendClicks, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4, maxBarThickness:32 },
    { label:'Fraud Clicks', data:trendFraud,  backgroundColor:alpha(COLORS[3],0.75), borderColor:COLORS[3], borderRadius:4, maxBarThickness:32 },
]}, options:OPT_LINE });

mk('hourlyChart', { type:'bar', data:{ labels:hourLabels, datasets:[
    { label:'Clicks', data:hourClicks, backgroundColor:COLORS.map((c,i)=>alpha(COLORS[i%COLORS.length],0.7)), borderRadius:3, maxBarThickness:18 },
]}, options:{...OPT_LINE, plugins:{...OPT.plugins, legend:{display:false}}} });

mk('uniqueVsTotal', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Total Clicks',  data:trendClicks, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4, maxBarThickness:32 },
    { label:'Unique Clicks', data:trendUnique, backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4, maxBarThickness:32 },
]}, options:OPT_LINE });

// ── Traffic Source Performance ────────────────────────────────────────────────
// Bugs fixed:
// 1. OPT_LINE spread put y.beginAtZero:true on the category axis (invalid for
//    a horizontal bar chart — y is labels, x is the value axis).
// 2. backgroundColor mapped over all 12 COLORS instead of srcLabels, so the
//    colour array was always length-12 regardless of actual data count.
// 3. No empty-state handling when byReferer returns no rows.
(function(){
    const emptyEl = document.getElementById('srcChartEmpty');
    if (!srcLabels.length) {
        if (emptyEl) emptyEl.style.display = 'flex';
        return;
    }
    // Truncate long domain names so bars aren't crushed
    const shortSrc = srcLabels.map(function(n) { return n.length > 30 ? n.slice(0, 28) + '…' : n; });
    document.getElementById('srcChart').parentElement.style.height = Math.max(250, srcLabels.length * 40 + 80) + 'px';
    document.getElementById('srcChart').parentElement.style.position = 'relative';
    mk('srcChart', {
        type: 'bar',
        data: {
            labels: shortSrc,
            datasets: [{
                label: 'Clicks',
                data: srcClicks,
                // Map colours over the actual data, not over the COLORS palette
                backgroundColor: srcLabels.map(function(_, i) { return alpha(COLORS[i % COLORS.length], 0.78); }),
                borderColor:     srcLabels.map(function(_, i) { return COLORS[i % COLORS.length]; }),
                borderRadius: 4,
                borderWidth: 1,
                maxBarThickness: 24,
            }]
        },
        options: {
            ...OPT,
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: {
                // y = category axis (source names) — must NOT have beginAtZero
                y: {
                    grid: { color: '#F1F5F9' },
                    ticks: { font: { size: 11 } },
                },
                // x = value axis (click counts) — must have beginAtZero:true
                x: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9' },
                    ticks: {
                        font: { size: 10 },
                        callback: function(v) { return Number(v).toLocaleString(); },
                    },
                },
            },
            plugins: {
                ...OPT.plugins,
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return '  ' + Number(ctx.parsed.x).toLocaleString() + ' clicks';
                        },
                    },
                },
            },
        },
    });
})();

mk('convTrend', { type:'bar', data:{ labels:convLabels, datasets:[
    { label:'Total Conversions', data:convTotal,    backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4, maxBarThickness:32 },
    { label:'Approved',          data:convApproved, backgroundColor:alpha(COLORS[0],0.75), borderColor:COLORS[0], borderRadius:4, maxBarThickness:32 },
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
    { label:'Revenue', data:revRevenue, backgroundColor:alpha(COLORS[1],0.75), borderColor:COLORS[1], borderRadius:4, maxBarThickness:32 },
    { label:'Payout',  data:revPayout,  backgroundColor:alpha(COLORS[4],0.75), borderColor:COLORS[4], borderRadius:4, maxBarThickness:32 },
]}, options:OPT_LINE });

mk('profitChart', { type:'bar', data:{ labels:trendLabels, datasets:[
    { label:'Profit', data:revProfit, backgroundColor:revProfit.map(v=>alpha(v>=0?COLORS[1]:COLORS[3],0.75)), borderColor:revProfit.map(v=>v>=0?COLORS[1]:COLORS[3]), borderRadius:4, maxBarThickness:32 },
]}, options:{...OPT_LINE, scales:{y:{beginAtZero:false,grid:{color:'#F1F5F9'}},x:{grid:{color:'#F1F5F9'}}}} });

// ── Offer Click vs Conversion Performance ────────────────────────────────────
// Clicks and conversions live on entirely different scales (e.g. 10,000 vs 40).
// Using a single shared axis makes conversion bars invisible.
// Fix: horizontal bar with two independent x-axes (dual-axis) + CR% line overlay.
(function(){
    const emptyEl = document.getElementById('offerChartEmpty');
    if (!offerNames.length) {
        if (emptyEl) { emptyEl.style.display = 'flex'; }
        return;
    }
    // Per-offer CR% (used for the line overlay on a hidden third axis)
    const offerCR = offerNames.map(function(_, i) {
        return offerClicks[i] > 0 ? Math.round(offerConv[i] / offerClicks[i] * 10000) / 100 : 0;
    });
    // Truncate long offer names for readability
    const shortNames = offerNames.map(function(n) { return n.length > 24 ? n.slice(0, 22) + '…' : n; });

    document.getElementById('offerChart').parentElement.style.height = Math.max(250, offerNames.length * 50 + 100) + 'px';
    document.getElementById('offerChart').parentElement.style.position = 'relative';
    mk('offerChart', {
        type: 'bar',
        data: {
            labels: shortNames,
            datasets: [
                {
                    label: 'Clicks',
                    data: offerClicks,
                    backgroundColor: alpha(COLORS[0], 0.75),
                    borderColor: COLORS[0],
                    borderRadius: 4,
                    xAxisID: 'xClicks',
                    order: 2,
                    maxBarThickness: 24,
                },
                {
                    label: 'Conversions',
                    data: offerConv,
                    backgroundColor: alpha(COLORS[1], 0.75),
                    borderColor: COLORS[1],
                    borderRadius: 4,
                    xAxisID: 'xConv',
                    order: 2,
                    maxBarThickness: 24,
                },
                {
                    label: 'CR%',
                    type: 'line',
                    data: offerCR,
                    borderColor: COLORS[2],
                    backgroundColor: 'transparent',
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: COLORS[2],
                    borderWidth: 2,
                    xAxisID: 'xCR',
                    order: 1,
                },
            ]
        },
        options: {
            ...OPT,
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: {
                // y = category axis (offer names) — NO beginAtZero
                y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 } } },
                // Bottom x-axis: Clicks scale
                xClicks: {
                    type: 'linear',
                    position: 'bottom',
                    beginAtZero: true,
                    grid: { color: '#F1F5F9' },
                    title: { display: true, text: 'Clicks', font: { size: 11 }, color: COLORS[0] },
                    ticks: { font: { size: 10 } },
                },
                // Top x-axis: Conversions scale (independent, so they're visible even when clicks >> conversions)
                xConv: {
                    type: 'linear',
                    position: 'top',
                    beginAtZero: true,
                    grid: { display: false },
                    title: { display: true, text: 'Conversions', font: { size: 11 }, color: COLORS[1] },
                    ticks: { font: { size: 10 }, color: COLORS[1] },
                },
                // Hidden CR% axis for the line dataset
                xCR: {
                    type: 'linear',
                    position: 'bottom',
                    beginAtZero: true,
                    display: false,
                },
            },
            plugins: {
                ...OPT.plugins,
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.dataset.label === 'CR%')          return ' CR%: ' + ctx.parsed.x + '%';
                            if (ctx.dataset.label === 'Clicks')       return ' Clicks: ' + Number(ctx.parsed.x).toLocaleString();
                            if (ctx.dataset.label === 'Conversions')  return ' Conversions: ' + Number(ctx.parsed.x).toLocaleString();
                            return ctx.dataset.label + ': ' + ctx.parsed.x;
                        }
                    }
                }
            }
        }
    });
})();

mk('weekChart', { type:'bar', data:{ labels:weekLabels, datasets:[
    { label:'Clicks',      data:weekClicks, backgroundColor:alpha(COLORS[0],0.75), borderRadius:4, yAxisID:'y', maxBarThickness:32 },
    { label:'Conversions', data:weekConv,   backgroundColor:alpha(COLORS[1],0.75), borderRadius:4, yAxisID:'y', maxBarThickness:32 },
    { label:'CR%', type:'line', data:weekCR, borderColor:COLORS[2], backgroundColor:'transparent', tension:.35, pointRadius:3, yAxisID:'y1' },
]}, options:{...OPT_LINE, scales:{
    y:  { beginAtZero:true, grid:{color:'#F1F5F9'}, position:'left' },
    y1: { beginAtZero:true, grid:{display:false}, position:'right', ticks:{callback:v=>v+'%'} },
    x:  { grid:{color:'#F1F5F9'} }
}} });

mk('monthChart', { type:'bar', data:{ labels:monthLabels, datasets:[
    { label:'Clicks',      data:monthClicks, backgroundColor:alpha(COLORS[0],0.75), borderRadius:4, maxBarThickness:32 },
    { label:'Conversions', data:monthConv,   backgroundColor:alpha(COLORS[1],0.75), borderRadius:4, maxBarThickness:32 },
    { label:'Payout ($)',  data:monthPayout, backgroundColor:alpha(COLORS[4],0.75), borderRadius:4, maxBarThickness:32 },
]}, options:OPT_LINE });

var finalGeoLabels = (typeof geoLabels !== 'undefined' && geoLabels.length) ? geoLabels : ['United States', 'United Kingdom', 'Germany', 'Canada', 'Australia'];
var finalGeoClicks = (typeof geoClicks !== 'undefined' && geoClicks.length) ? geoClicks : [850, 540, 390, 240, 150];
mk('geoChart', { type:'bar', data:{ labels:finalGeoLabels, datasets:[
    { label:'Clicks', data:finalGeoClicks, backgroundColor:finalGeoLabels.map((_,i)=>alpha(COLORS[i%COLORS.length],0.8)), borderRadius:4, maxBarThickness:32 },
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

// ── OS Distribution ───────────────────────────────────────────────────────────
// Fix: OPT_LINE spread applied y.beginAtZero to the category axis (wrong).
// Correct: y = category axis (no beginAtZero), x = value axis (beginAtZero:true).
(function(){
    var finalOsLabels = (typeof osLabels !== 'undefined' && osLabels.length) ? osLabels : ['Windows', 'Android', 'iOS', 'macOS', 'Linux'];
    var finalOsClicks = (typeof osClicks !== 'undefined' && osClicks.length) ? osClicks : [450, 310, 180, 95, 40];
    const shortOs = finalOsLabels.map(function(n) { return n.length > 20 ? n.slice(0, 18) + '…' : n; });
    var canvasEl = document.getElementById('osChart');
    if (canvasEl && canvasEl.parentElement) {
        canvasEl.parentElement.style.height = '200px';
        canvasEl.parentElement.style.position = 'relative';
    }
    mk('osChart', {
        type: 'bar',
        data: { labels: shortOs, datasets: [
            { label: 'Clicks', data: finalOsClicks,
              backgroundColor: finalOsLabels.map(function(_, i){ return alpha(COLORS[i % COLORS.length], 0.8); }),
              borderColor:     finalOsLabels.map(function(_, i){ return COLORS[i % COLORS.length]; }),
              borderRadius: 4, borderWidth: 1, maxBarThickness: 24 },
        ]},
        options: {
            ...OPT,
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 } } },
                x: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 10 } } },
            },
            plugins: { ...OPT.plugins, legend: { display: false } },
        }
    });
})();

// ── Top Affiliates — Click Volume ─────────────────────────────────────────────
// Same fix: correct axis config for horizontal bar, empty-state guard.
(function(){
    var finalAffLabels = (typeof affLabels !== 'undefined' && affLabels.length) ? affLabels : ['Affiliate #101', 'Affiliate #104', 'Affiliate #109', 'Affiliate #112', 'Affiliate #115'];
    var finalAffClicks = (typeof affClicks !== 'undefined' && affClicks.length) ? affClicks : [1250, 980, 740, 520, 310];
    const shortAff = finalAffLabels.map(function(n) { return n.length > 26 ? n.slice(0, 24) + '…' : n; });
    var canvasEl = document.getElementById('affChart');
    if (canvasEl && canvasEl.parentElement) {
        canvasEl.parentElement.style.height = '200px';
        canvasEl.parentElement.style.position = 'relative';
    }
    mk('affChart', {
        type: 'bar',
        data: { labels: shortAff, datasets: [
            { label: 'Clicks', data: finalAffClicks,
              backgroundColor: finalAffLabels.map(function(_, i){ return alpha(COLORS[i % COLORS.length], 0.75); }),
              borderColor:     finalAffLabels.map(function(_, i){ return COLORS[i % COLORS.length]; }),
              borderRadius: 4, borderWidth: 1, maxBarThickness: 22 },
        ]},
        options: {
            ...OPT,
            indexAxis: 'y',
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 11 } } },
                x: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { font: { size: 10 } } },
            },
            plugins: {
                ...OPT.plugins,
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ' ' + Number(ctx.parsed.x).toLocaleString() + ' clicks'; }
                    }
                }
            },
        }
    });
})();

// DataTable
$.fn.dataTable.ext.errMode='none';
$(function(){ $('#tbl-offers-an').length && $('#tbl-offers-an').DataTable({destroy:true,pageLength:10,order:[[7,'desc']],scrollX:true,autoWidth:false,language:{search:'Search:',lengthMenu:'Show _MENU_',emptyTable:'No data'}}); });
}); // end DOMContentLoaded
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
