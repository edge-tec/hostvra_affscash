<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128202;</div>
        <div>
            <div class="fds-page-title">Fraud Reports &amp; Analytics</div>
            <div class="fds-page-sub">Trends, affiliate risk rankings, blocklist effectiveness</div>
        </div>
    </div>
</div>

<!-- Filters (includes date-range chips + From/To) -->
<?php
$fraudFilterUrl = '/admin/fraud-center/analytics';
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- KPI strip -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:16px">
    <div class="fds-kpi"><div class="fds-kpi-label">Total Clicks</div><div class="fds-kpi-val"><?= number_format($kpis['total_clicks']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Total Conversions</div><div class="fds-kpi-val"><?= number_format($kpis['total_convs']) ?></div></div>
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Fast Convs (&lt;30s)</div><div class="fds-kpi-val"><?= number_format($kpis['fast_convs']) ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">Open Cases</div><div class="fds-kpi-val"><?= number_format($kpis['open_cases']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Blocklist Active</div><div class="fds-kpi-val"><?= number_format($kpis['blocklist_total']) ?></div></div>
</div>

<!-- Daily trend chart -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title">Daily Click &amp; Conversion Trend (<?= $range ?>d)</span></div>
    <div class="fds-card-body"><div class="chart-container" style="height:220px"><canvas id="fdsAnalyticsChart"></canvas></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Case types -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">Cases by Type (<?= $range ?>d)</span></div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>Type</th><th>Cases</th></tr></thead>
                <tbody>
                <?php foreach ($caseTypes as $row): ?>
                <tr>
                    <td><?= str_replace('_',' ', ucfirst($row['type'])) ?></td>
                    <td><strong><?= number_format($row['cnt']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($caseTypes)): ?><tr><td colspan="2" class="fds-empty">No cases in period</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Blocklist hits -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">Blocklist Hit Summary</span></div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>Type</th><th>Entries</th><th>Total Hits</th></tr></thead>
                <tbody>
                <?php foreach ($blocklistHits as $row): ?>
                <tr>
                    <td><span class="fds-badge fds-badge-muted"><?= $row['type'] ?></span></td>
                    <td><?= $row['entries'] ?></td>
                    <td><strong><?= number_format($row['total_hits']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($blocklistHits)): ?><tr><td colspan="3" class="fds-empty">No active blocklist entries</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Affiliate risk table -->
<div class="fds-card">
    <div class="fds-card-header"><span class="fds-card-title">Affiliate Risk Ranking (<?= $range ?>d)</span></div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead><tr><th>Affiliate</th><th>Clicks</th><th>Convs</th><th>CVR</th><th>Fast Convs</th><th>Open Cases</th><th>Risk</th></tr></thead>
            <tbody>
            <?php foreach ($affRisk as $row):
                $risk = 'low';
                if ($row['open_cases'] >= 3 || $row['fast_convs'] >= 10) $risk = 'critical';
                elseif ($row['open_cases'] >= 1 || $row['fast_convs'] >= 3) $risk = 'high';
                elseif ($row['fast_convs'] >= 1 || (float)$row['cvr'] < 0.1) $risk = 'medium';
            ?>
            <tr>
                <td>
                    <strong><?= Helpers::e($row['affiliate_code']) ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e(($row['first_name']??'').' '.($row['last_name']??'')) ?></span>
                </td>
                <td><?= number_format($row['total_clicks']) ?></td>
                <td><?= number_format($row['total_convs']) ?></td>
                <td><?= $row['cvr'] !== null ? $row['cvr'].'%' : '—' ?></td>
                <td><?= $row['fast_convs'] > 0 ? '<strong style="color:#EF4444">'.$row['fast_convs'].'</strong>' : '0' ?></td>
                <td><?= $row['open_cases'] > 0 ? '<strong style="color:#EF4444">'.$row['open_cases'].'</strong>' : '0' ?></td>
                <td><?= fraud_severity_badge($risk) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($affRisk)): ?><tr><td colspan="7" class="fds-empty">No affiliate data</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    var ctx = document.getElementById('fdsAnalyticsChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?= json_encode(array_column($dailyTrend, 'day')) ?>;
    var clicks  = <?= json_encode(array_column($dailyTrend, 'clicks')) ?>;
    var convs   = <?= json_encode(array_column($dailyTrend, 'convs')) ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {label:'Clicks', data:clicks, borderColor:'rgba(139,92,246,.9)', backgroundColor:'rgba(139,92,246,.12)', tension:.3, fill:true},
                {label:'Conversions', data:convs, borderColor:'rgba(16,185,129,.9)', backgroundColor:'rgba(16,185,129,.12)', tension:.3, fill:true}
            ]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
