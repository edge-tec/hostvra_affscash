<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <div style="margin-bottom:4px">
            <a href="/affiliate_manager/dashboard" style="color:var(--text-muted);text-decoration:none;font-size:13px">&#8592; Dashboard</a>
        </div>
        <h1><?= Helpers::e($offer['name']) ?></h1>
        <p><?= Helpers::e($offer['category'] ?: 'No category') ?> &bull; <?= ucfirst($offer['payout_type']) ?> &bull; $<?= number_format($offer['payout_amount'],2) ?> payout</p>
    </div>
    <?php $statusColors = ['active'=>'var(--secondary)','paused'=>'#F59E0B','expired'=>'#94A3B8','pending'=>'var(--primary)']; ?>
    <span class="badge" style="background:<?= $statusColors[$offer['status']] ?? '#94A3B8' ?>;color:#fff;padding:8px 16px;font-size:13px;border-radius:6px"><?= strtoupper($offer['status']) ?></span>
</div>

<!-- Offer info + cap -->
<div class="grid-2 mb-3" style="grid-template-columns:2fr 1fr">
    <div class="card">
        <div class="card-header"><span class="card-title">Offer Details</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                <div>
                    <div class="text-muted text-sm mb-1">Payout Type</div>
                    <div class="fw-bold"><span class="badge badge-info"><?= Helpers::e($offer['payout_type']) ?></span></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Affiliate Payout</div>
                    <div class="fw-bold" style="font-size:20px;color:var(--secondary)">$<?= number_format($offer['payout_amount'],2) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Require Approval</div>
                    <div class="fw-bold"><?= $offer['require_approval'] ? '&#9989; Yes' : '&#10060; No' ?></div>
                </div>
                <?php
                $geos    = $offer['geo_targeting']    ? json_decode($offer['geo_targeting'],    true) : [];
                $devices = $offer['device_targeting'] ? json_decode($offer['device_targeting'], true) : [];
                ?>
                <div>
                    <div class="text-muted text-sm mb-1">GEO Targeting</div>
                    <div class="fw-bold text-sm"><?= Helpers::geoList($geos) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Device Targeting</div>
                    <div class="fw-bold text-sm"><?= $devices ? implode(', ', $devices) : 'All Devices' ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Visibility</div>
                    <div class="fw-bold"><?= ucfirst($offer['visibility'] ?? 'public') ?></div>
                </div>
            </div>
            <?php if ($offer['description']): ?>
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid #E2E8F0">
                <div class="text-muted text-sm mb-1">Description</div>
                <div style="font-size:14px"><?= nl2br(Helpers::e($offer['description'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <div class="card-header"><span class="card-title">Cap Usage</span></div>
            <div class="card-body">
                <?php
                $dailyCap = (int)$offer['daily_cap'];
                $totalCap = (int)$offer['total_cap'];
                $dailyPct = $dailyCap > 0 ? min(100, round($todayCapUsed / $dailyCap * 100)) : 0;
                $totalPct = $totalCap > 0 ? min(100, round($totalCapUsed / $totalCap  * 100)) : 0;
                ?>
                <div style="margin-bottom:16px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                        <span class="text-sm fw-bold">Daily Cap <span style="font-weight:400;color:#9CA3AF;font-size:10px">(resets midnight)</span></span>
                        <span class="text-sm text-muted"><?= number_format($todayCapUsed) ?> / <?= $dailyCap ? number_format($dailyCap) : '&infin;' ?></span>
                    </div>
                    <?php if ($dailyCap > 0): ?>
                    <div style="background:#E2E8F0;border-radius:4px;height:8px">
                        <div style="background:<?= $dailyPct>=100?'var(--danger)':($dailyPct>=80?'#F59E0B':'var(--secondary)') ?>;width:<?= $dailyPct ?>%;height:8px;border-radius:4px"></div>
                    </div>
                    <div class="text-sm" style="margin-top:2px;color:<?= $dailyPct>=100?'var(--danger)':'var(--text-muted)' ?>"><?= $dailyPct ?>% used today<?= $dailyPct>=100?' &mdash; <strong>Cap reached</strong>':'' ?></div>
                    <?php else: ?><div class="text-sm text-muted">No daily cap</div><?php endif; ?>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                        <span class="text-sm fw-bold">Total Cap</span>
                        <span class="text-sm text-muted"><?= number_format($totalCapUsed) ?> / <?= $totalCap ? number_format($totalCap) : '&infin;' ?></span>
                    </div>
                    <?php if ($totalCap > 0): ?>
                    <div style="background:#E2E8F0;border-radius:4px;height:8px">
                        <div style="background:<?= $totalPct>=100?'var(--danger)':($totalPct>=80?'#F59E0B':'var(--secondary)') ?>;width:<?= $totalPct ?>%;height:8px;border-radius:4px"></div>
                    </div>
                    <div class="text-sm text-muted" style="margin-top:2px"><?= $totalPct ?>% used all-time</div>
                    <?php else: ?><div class="text-sm text-muted">No total cap</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">Today</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($todayStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($todayStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format($todayStats['payout'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">CR%</div><div class="fw-bold"><?= ($todayStats['clicks'] ?? 0) > 0 ? round(($todayStats['conversions'] ?? 0) / $todayStats['clicks'] * 100, 2) : 0 ?>%</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">Last 30 Days</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($monthStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($monthStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Approved</div><div class="fw-bold" style="color:var(--secondary)"><?= number_format($monthStats['approved'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold">$<?= number_format($monthStats['payout'] ?? 0, 2) ?></div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">All Time</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($allStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($allStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format($allStats['payout'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">EPC</div><div class="fw-bold"><?= ($allStats['clicks'] ?? 0) > 0 ? '$'.round(($allStats['payout'] ?? 0) / $allStats['clicks'], 4) : '$0' ?></div></div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header">
            <span class="card-title">30-Day Performance</span>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary btn-sm chart-type-btn active" data-chart="mgrOfferChart" data-type="line">Line</button>
                <button class="btn btn-secondary btn-sm chart-type-btn" data-chart="mgrOfferChart" data-type="bar">Bar</button>
            </div>
        </div>
        <div class="card-body"><div class="chart-container"><canvas id="mgrOfferChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Conversion Status</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center">
            <div style="position:relative;height:260px;width:100%;max-width:320px;margin:0 auto">
                <canvas id="mgrOfferPie"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top affiliates + Top countries -->
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Top Affiliates (30 days)</span></div>
        <div class="table-wrap">
            <table id="tbl-top-aff">
                <thead><tr><th>Affiliate</th><th>Clicks</th><th>Conv.</th><th>Approved</th><th>CR%</th><th>Payout</th></tr></thead>
                <tbody>
                <?php if (empty($topAffiliates)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No data yet</td></tr>
                <?php else: foreach ($topAffiliates as $a):
                    $cr = $a['clicks'] > 0 ? round($a['conversions'] / $a['clicks'] * 100, 2) : 0;
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= Helpers::e($a['name']) ?></div>
                        <div class="text-sm text-muted"><?= Helpers::e($a['affiliate_code']) ?></div>
                    </td>
                    <td><?= number_format($a['clicks']) ?></td>
                    <td><?= number_format($a['conversions']) ?></td>
                    <td><?= number_format($a['approved']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td class="fw-bold" style="color:var(--secondary)">$<?= number_format($a['payout'], 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Top Countries (30 days)</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Country</th><th>Clicks</th><th>Unique</th><th>Payout</th></tr></thead>
                <tbody>
                <?php if (empty($topCountries)): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No data yet</td></tr>
                <?php else: foreach ($topCountries as $c): ?>
                <tr>
                    <td>
                        <?php if (!empty($c['country'])): ?>
                        <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:5px">
                        <?php endif; ?>
                        <span class="fw-bold"><?= Helpers::e($c['country'] ?: '—') ?></span>
                    </td>
                    <td><?= number_format($c['clicks']) ?></td>
                    <td><?= number_format($c['uclicks']) ?></td>
                    <td>$<?= number_format($c['payout'], 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent conversions -->
<div class="card mb-3">
    <div class="card-header"><span class="card-title">Recent Conversions</span></div>
    <div class="table-wrap">
        <table id="tbl-conv">
            <thead><tr><th>Affiliate</th><th>Payout</th><th>Status</th><th>Goal</th><th>Time</th></tr></thead>
            <tbody>
            <?php if (empty($recentConversions)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No conversions yet</td></tr>
            <?php else: foreach ($recentConversions as $cv):
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($cv['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($cv['affiliate_code']) ?></div>
                </td>
                <td class="fw-bold" style="color:var(--secondary)">$<?= number_format($cv['payout'], 2) ?></td>
                <td><span class="badge badge-<?= $bm[$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span></td>
                <td class="text-sm text-muted"><?= Helpers::e($cv['goal_name'] ?: '—') ?></td>
                <td class="text-sm text-muted"><?= date('M j, H:i', strtotime($cv['converted_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const COLORS = ['#4F46E5','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6'];
    const alpha  = (hex, a) => hex + Math.round(a*255).toString(16).padStart(2,'0');
    const registry = {};

    function makeChart(id, cfg) {
        const ctx = document.getElementById(id);
        if (!ctx || typeof Chart === 'undefined') return null;
        const ch = new Chart(ctx, cfg);
        registry[id] = { chart: ch };
        return ch;
    }

    function switchType(chartId, newType) {
        if (!registry[chartId]) return;
        const { chart } = registry[chartId];
        const labels = chart.data.labels;
        const datasets = chart.data.datasets.map((ds, i) => ({
            ...ds, type: undefined,
            backgroundColor: newType === 'pie' ? COLORS.slice(0, labels.length) : COLORS.slice(i, i+1).map(c => alpha(c, '30')),
            borderColor: newType === 'pie' ? '#fff' : COLORS[i % COLORS.length],
            fill: newType === 'line', tension: newType === 'line' ? .3 : 0,
        }));
        chart.config.type = newType;
        chart.data.datasets = datasets;
        chart.options.scales = newType === 'pie' ? {} : { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { color: '#F1F5F9' } } };
        chart.update();
    }

    document.querySelectorAll('.chart-type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchType(this.dataset.chart, this.dataset.type);
            this.closest('.card-header').querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    makeChart('mgrOfferChart', {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chartData,'stat_date')) ?>,
            datasets: [
                { label:'Clicks',      data:<?= json_encode(array_map('intval',   array_column($chartData,'clicks'))) ?>,      borderColor:'#4F46E5', backgroundColor:'rgba(79,70,229,.09)',  tension:.3, fill:true },
                { label:'Conversions', data:<?= json_encode(array_map('intval',   array_column($chartData,'conversions'))) ?>, borderColor:'#10B981', backgroundColor:'rgba(16,185,129,.09)', tension:.3, fill:true },
            ]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'}},x:{grid:{color:'#F1F5F9'}}} }
    });

    <?php
    $pieL = array_column($convStatus,'status');
    $pieV = array_map('intval', array_column($convStatus,'cnt'));
    $pieC = ['#10B981','#F59E0B','#EF4444','#94A3B8','#3B82F6'];
    ?>
    makeChart('mgrOfferPie', {
        type: 'pie',
        data: {
            labels: <?= json_encode($pieL ?: ['No data']) ?>,
            datasets: [{ data: <?= json_encode($pieV ?: [1]) ?>, backgroundColor: <?= json_encode(array_slice($pieC, 0, max(1, count($pieL)))) ?>, borderColor:'#fff', borderWidth:2 }]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });

    $.fn.dataTable.ext.errMode = 'none';
    function dtInit(id, opts) { var $t=$('#'+id); if(!$t.length)return; if(opts.scrollX)opts.scrollX=$t.find('tbody tr').length>0; $t.DataTable(opts); }
    $(function() {
        dtInit('tbl-top-aff', { destroy:true, pageLength:10, order:[[5,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data yet'} });
        dtInit('tbl-conv',    { destroy:true, pageLength:10, order:[[4,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No conversions yet'} });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
