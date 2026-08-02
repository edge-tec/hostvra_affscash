<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">&#128737;</div>
        <div>
            <div class="fds-page-title">Live Threat Monitor</div>
            <div class="fds-page-sub">Real-time click and conversion stream &mdash; last 60 minutes</div>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <button onclick="location.reload()" class="fds-btn fds-btn-sm fds-btn-outline">&#8635; Refresh</button>
    </div>
</div>

<!-- Filters -->
<?php
$fraudFilterUrl = '/admin/fraud-center/live-monitor';
$exportParams   = ['type' => 'clicks'];
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Export conversions button -->
<div style="margin-bottom:14px">
    <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','type'=>'conversions'])) ?>" class="fds-btn fds-btn-outline" style="font-size:13px">&#11123; Export Conversions CSV</a>
</div>

<!-- ── Fraud System Enable / Disable Banner ─────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:16px 20px;border-radius:12px;margin-bottom:20px;
     background:<?= $fraudEnabled ? 'linear-gradient(135deg,rgba(16,185,129,.12),rgba(5,150,105,.08))' : 'linear-gradient(135deg,rgba(239,68,68,.1),rgba(185,28,28,.07))' ?>;
     border:1.5px solid <?= $fraudEnabled ? 'rgba(16,185,129,.35)' : 'rgba(239,68,68,.35)' ?>">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:40px;height:40px;border-radius:10px;background:<?= $fraudEnabled ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.2)' ?>;display:flex;align-items:center;justify-content:center;font-size:20px">
            <?= $fraudEnabled ? '&#128737;' : '&#128683;' ?>
        </div>
        <div>
            <div style="font-weight:700;font-size:15px;color:<?= $fraudEnabled ? '#065F46' : '#991B1B' ?>">
                Fraud Detection System &mdash;
                <span style="font-size:13px;padding:2px 10px;border-radius:20px;background:<?= $fraudEnabled ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $fraudEnabled ? '#166534' : '#991B1B' ?>">
                    <?= $fraudEnabled ? '&#10003; ENABLED' : '&#10007; DISABLED' ?>
                </span>
            </div>
            <div style="font-size:12px;color:#6B7280;margin-top:2px">
                <?= $fraudEnabled
                    ? 'Fraud rules are active. Auto-blocking, flagging, and case creation are running.'
                    : 'All fraud detection is paused. No rules are being evaluated. Clicks and conversions pass through unchecked.' ?>
            </div>
        </div>
    </div>
    <form method="post" style="display:flex;align-items:center;gap:10px">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="action" value="toggle_fraud">
        <input type="hidden" name="enabled" value="<?= $fraudEnabled ? 0 : 1 ?>">
        <button type="submit" style="padding:9px 22px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;border:none;
                background:<?= $fraudEnabled ? '#EF4444' : '#10B981' ?>;color:#fff;
                box-shadow:0 2px 8px <?= $fraudEnabled ? 'rgba(239,68,68,.35)' : 'rgba(16,185,129,.35)' ?>">
            <?= $fraudEnabled ? '&#9724; Disable Fraud Detection' : '&#9654; Enable Fraud Detection' ?>
        </button>
    </form>
</div>

<!-- ── Auto-Block Settings Card ────────────────────────────────────────── -->
<div class="fds-card mb-3" id="auto-block" style="border:2px solid <?= $autoBlockOn ? 'rgba(239,68,68,.35)' : 'rgba(209,213,219,.6)' ?>">
    <div class="fds-card-header" style="background:<?= $autoBlockOn ? 'rgba(239,68,68,.06)' : '#FAFAFA' ?>">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:18px"><?= $autoBlockOn ? '&#128683;' : '&#9728;' ?></span>
            <div>
                <span class="fds-card-title">Auto-Block Clicks by Fraud Score</span>
                <span class="fds-text-sm fds-text-muted" style="margin-left:8px">
                    — Any click with fraud score &ge; threshold is automatically blocked at tracking time
                </span>
            </div>
        </div>
        <span class="fds-badge <?= $autoBlockOn ? 'fds-badge-critical' : 'fds-badge-muted' ?>">
            <?= $autoBlockOn ? '&#128683; ACTIVE' : 'INACTIVE' ?>
        </span>
    </div>
    <div class="fds-card-body">
        <form method="post" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:20px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_auto_block">

            <!-- Toggle -->
            <div>
                <div class="fds-section-label" style="margin-bottom:8px">Auto-Block Status</div>
                <div style="display:flex;gap:8px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 16px;border-radius:8px;border:2px solid <?= !$autoBlockOn ? '#4F46E5' : '#E5E7EB' ?>;background:<?= !$autoBlockOn ? '#EEF2FF' : '#fff' ?>">
                        <input type="radio" name="auto_block_enabled" value="0" <?= !$autoBlockOn ? 'checked' : '' ?> style="accent-color:#4F46E5">
                        <span style="font-weight:700;font-size:13px;color:<?= !$autoBlockOn ? '#4F46E5' : '#6B7280' ?>">&#9654; Disabled</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 16px;border-radius:8px;border:2px solid <?= $autoBlockOn ? '#EF4444' : '#E5E7EB' ?>;background:<?= $autoBlockOn ? '#FFF1F1' : '#fff' ?>">
                        <input type="radio" name="auto_block_enabled" value="1" <?= $autoBlockOn ? 'checked' : '' ?> style="accent-color:#EF4444">
                        <span style="font-weight:700;font-size:13px;color:<?= $autoBlockOn ? '#DC2626' : '#6B7280' ?>">&#128683; Enabled</span>
                    </label>
                </div>
            </div>

            <!-- Threshold slider -->
            <div style="flex:1;min-width:260px">
                <div class="fds-section-label" style="margin-bottom:8px">
                    Fraud Score Threshold &mdash; <span id="ab-thr-val" style="color:#EF4444;font-size:14px;font-weight:800"><?= $autoBlockThr ?></span> / 100
                </div>
                <input type="range" name="auto_block_threshold" id="ab-thr-range"
                       min="1" max="100" value="<?= $autoBlockThr ?>"
                       style="width:100%;accent-color:#EF4444;height:6px"
                       oninput="document.getElementById('ab-thr-val').textContent=this.value;document.getElementById('ab-thr-num').value=this.value">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:#9CA3AF;margin-top:3px">
                    <span>1 (block everything)</span>
                    <span>100 (never block)</span>
                </div>
                <input type="number" id="ab-thr-num" min="1" max="100" value="<?= $autoBlockThr ?>"
                       style="margin-top:8px;width:80px;padding:5px 8px;border:1px solid #D1D5DB;border-radius:6px;font-size:13px"
                       oninput="document.getElementById('ab-thr-range').value=this.value;document.getElementById('ab-thr-val').textContent=this.value"
                       onchange="document.getElementById('ab-thr-range').value=this.value;document.getElementById('ab-thr-val').textContent=this.value">
                <div class="form-hint" style="margin-top:4px">
                    Recommended: <strong>70</strong> &mdash; clicks scoring &ge; <?= $autoBlockThr ?> are blocked before reaching the offer. Lower = stricter.
                </div>
            </div>

            <div style="padding-bottom:2px">
                <button type="submit" class="fds-btn fds-btn-primary">Save Auto-Block Settings</button>
            </div>
        </form>

        <?php if ($autoBlockOn): ?>
        <div style="margin-top:14px;padding:10px 14px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:8px;font-size:13px;color:#7F1D1D">
            &#128683; <strong>Auto-Block is ACTIVE.</strong>
            Any incoming click with a fraud score &ge; <strong><?= $autoBlockThr ?></strong> will be instantly blocked at the tracker level &mdash; the affiliate's browser is redirected to the blocked page and the click is recorded as <code>blocked</code>.
            Conversions from blocked clicks are impossible.
        </div>
        <?php else: ?>
        <div style="margin-top:14px;padding:10px 14px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;color:#6B7280">
            &#9728; Auto-Block is disabled. All clicks pass through regardless of fraud score. Enable it to automatically reject high-risk clicks in real time.
        </div>
        <?php endif; ?>

        <!-- Quick stats -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px">
            <?php
            $blockedToday  = Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE status='blocked' AND DATE(clicked_at)=CURDATE()")['c'] ?? 0;
            $blocked7d     = Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE status='blocked' AND clicked_at>=NOW()-INTERVAL 7 DAY")['c'] ?? 0;
            $highScore     = Database::fetchOne("SELECT COUNT(*) AS c FROM clicks WHERE fraud_score>=? AND status='valid' AND clicked_at>=NOW()-INTERVAL 24 HOUR", [$autoBlockThr])['c'] ?? 0;
            ?>
            <div style="background:#FFF7F0;border:1px solid #FED7AA;border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:22px;font-weight:800;color:#C2410C"><?= number_format($blockedToday) ?></div>
                <div style="font-size:11px;color:#9A3412;font-weight:600;text-transform:uppercase">Blocked Today</div>
            </div>
            <div style="background:#FFF1F2;border:1px solid #FECDD3;border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:22px;font-weight:800;color:#BE123C"><?= number_format($blocked7d) ?></div>
                <div style="font-size:11px;color:#9F1239;font-weight:600;text-transform:uppercase">Blocked (7d)</div>
            </div>
            <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px;text-align:center">
                <div style="font-size:22px;font-weight:800;color:#B45309"><?= number_format($highScore) ?></div>
                <div style="font-size:11px;color:#92400E;font-weight:600;text-transform:uppercase">High-Score (24h, valid)</div>
            </div>
        </div>
    </div>
</div>

<!-- KPI strip -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(4,1fr)">
    <div class="fds-kpi"><div class="fds-kpi-label">Clicks (1h)</div><div class="fds-kpi-val" id="lm-clicks"><?= number_format($stats['clicks_1h']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">Conversions (1h)</div><div class="fds-kpi-val" id="lm-conv"><?= number_format($stats['conv_1h']) ?></div></div>
    <div class="fds-kpi fds-kpi-danger"><div class="fds-kpi-label">Burst IPs</div><div class="fds-kpi-val"><?= number_format($stats['burst_ips']) ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">Open Cases</div><div class="fds-kpi-val"><?= number_format($stats['open_cases']) ?></div></div>
</div>

<!-- Hourly chart -->
<div class="fds-card mb-3">
    <div class="fds-card-header"><span class="fds-card-title">Hourly Click Volume (24h)</span></div>
    <div class="fds-card-body"><div class="chart-container"><canvas id="lmChart"></canvas></div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Top IPs -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">&#128308; Top IPs (1h)</span></div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>IP</th><th>Clicks</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($topIps as $row): $isBurst = $row['cnt'] >= 50; ?>
                <tr>
                    <td><a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($row['ip_address']) ?>" class="fds-link"><?= Helpers::e($row['ip_address']) ?></a></td>
                    <td><strong><?= number_format($row['cnt']) ?></strong></td>
                    <td><?= $isBurst ? '<span class="fds-badge fds-badge-critical">BURST</span>' : '<span class="fds-badge fds-badge-low">Normal</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topIps)): ?><tr><td colspan="3" class="fds-empty">No data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent conversions -->
    <div class="fds-card">
        <div class="fds-card-header"><span class="fds-card-title">&#9989; Recent Conversions (1h)</span></div>
        <div class="fds-table-wrap">
            <table class="fds-table">
                <thead><tr><th>Offer</th><th>Affiliate</th><th>Payout</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($recentConversions, 0, 15) as $cv): ?>
                <tr>
                    <td class="fds-text-sm"><?= Helpers::e($cv['offer_name'] ?? '—') ?></td>
                    <td class="fds-text-sm"><?= Helpers::e($cv['affiliate_code'] ?? '—') ?></td>
                    <td>$<?= number_format($cv['payout'], 2) ?></td>
                    <td><span class="badge badge-<?= ['approved'=>'success','pending'=>'warning','rejected'=>'danger'][$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentConversions)): ?><tr><td colspan="4" class="fds-empty">No conversions yet</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Live click stream -->
<div class="fds-card">
    <div class="fds-card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="fds-card-title">&#9889; Click Stream (last 60 min)</span>
            <span class="fds-badge fds-badge-muted" id="cs-row-count"><?= count($recentClicks) ?> rows shown</span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <input type="text" id="cs-search" placeholder="🔍 Search IP, Offer, Affiliate, UA..."
                   style="padding:6px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:12px;width:240px"
                   onkeyup="filterClickStream()">
        </div>
    </div>
    <div class="fds-table-wrap" style="overflow-x:auto;-webkit-overflow-scrolling:touch">
        <table class="fds-table" style="min-width:980px">
            <thead>
                <tr>
                    <th style="width:85px">Time</th>
                    <th style="width:190px">IP Address</th>
                    <th style="min-width:180px">Offer</th>
                    <th style="width:130px">Affiliate</th>
                    <th style="min-width:320px">User Agent / Details</th>
                    <th style="width:80px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentClicks as $cl): ?>
            <?php
                $uaFull = trim($cl['user_agent'] ?? '');
                $ip = $cl['ip_address'] ?? '—';
                $isIpv6 = strpos($ip, ':') !== false;
                $searchIndex = strtolower($ip . ' ' . ($cl['offer_name'] ?? '') . ' ' . ($cl['affiliate_code'] ?? '') . ' ' . $uaFull);
            ?>
            <tr class="cs-row" data-search="<?= Helpers::e($searchIndex) ?>">
                <td class="fds-text-muted fds-text-sm" style="white-space:nowrap;font-weight:600"><?= date('H:i:s', strtotime($cl['clicked_at'])) ?></td>
                <td style="white-space:nowrap">
                    <a href="/admin/fraud-center/ip-intelligence?ip=<?= urlencode($ip) ?>" class="fds-link" style="font-family:monospace;font-size:12px;<?= $isIpv6 ? 'font-weight:700;color:#6366F1' : '' ?>" title="<?= Helpers::e($ip) ?>">
                        <?= Helpers::e($ip) ?>
                    </a>
                </td>
                <td class="fds-text-sm" style="font-weight:600;color:#1E293B"><?= Helpers::e($cl['offer_name'] ?? '—') ?></td>
                <td class="fds-text-sm" style="white-space:nowrap"><span class="fds-badge fds-badge-muted" style="font-family:monospace"><?= Helpers::e($cl['affiliate_code'] ?? '—') ?></span></td>
                <td>
                    <div style="font-size:12px;color:#475569;word-break:break-all;line-height:1.4;max-height:4.2em;overflow:hidden;position:relative" title="<?= Helpers::e($uaFull) ?>">
                        <?= Helpers::e($uaFull !== '' ? $uaFull : '—') ?>
                    </div>
                </td>
                <td style="text-align:right;white-space:nowrap">
                    <button type="button" class="fds-btn fds-btn-sm fds-btn-outline" style="padding:3px 8px;font-size:11px" onclick='openClickDetailModal(<?= json_encode([
                        "time" => date("Y-m-d H:i:s", strtotime($cl["clicked_at"])),
                        "ip" => $ip,
                        "offer" => $cl["offer_name"] ?? "—",
                        "affiliate" => $cl["affiliate_code"] ?? "—",
                        "ua" => $uaFull ?: "Not provided",
                        "country" => $cl["country"] ?? "",
                        "device" => $cl["device_type"] ?? "",
                        "os" => $cl["os"] ?? "",
                        "browser" => $cl["browser"] ?? "",
                        "status" => $cl["status"] ?? "valid",
                        "fraud_score" => (int)($cl["fraud_score"] ?? 0)
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                        👁 View
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentClicks)): ?><tr><td colspan="6" class="fds-empty">No clicks in the last 60 minutes</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Full Click Detail Modal -->
<div id="csDetailModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);z-index:99999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:14px;max-width:650px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px -5px rgba(0,0,0,.3);border:1px solid #E2E8F0">
        <div style="padding:18px 24px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;background:#F8FAFC;border-top-left-radius:14px;border-top-right-radius:14px">
            <h3 style="margin:0;font-size:16px;font-weight:800;color:#0F172A;display:flex;align-items:center;gap:8px">
                <span>&#9889; Click Stream Event Details</span>
            </h3>
            <button onclick="closeClickDetailModal()" style="border:none;background:none;font-size:22px;cursor:pointer;color:#64748B;line-height:1">&times;</button>
        </div>
        <div style="padding:24px" id="csDetailBody">
            <!-- Modal content injected dynamically -->
        </div>
        <div style="padding:14px 24px;background:#F8FAFC;border-top:1px solid #E2E8F0;display:flex;justify-content:flex-end;border-bottom-left-radius:14px;border-bottom-right-radius:14px">
            <button onclick="closeClickDetailModal()" class="fds-btn fds-btn-secondary">Close</button>
        </div>
    </div>
</div>

<script>
function openClickDetailModal(data) {
    var modal = document.getElementById('csDetailModal');
    var body = document.getElementById('csDetailBody');
    if (!modal || !body) return;
    
    var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">';
    html += '<div><div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Timestamp</div><div style="font-weight:700;color:#0F172A;font-size:13px">' + escapeHtml(data.time) + '</div></div>';
    html += '<div><div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">IP Address</div><div style="font-weight:700;color:#4F46E5;font-family:monospace;font-size:13px"><a href="/admin/fraud-center/ip-intelligence?ip=' + encodeURIComponent(data.ip) + '" target="_blank">' + escapeHtml(data.ip) + '</a></div></div>';
    html += '<div><div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Offer</div><div style="font-weight:700;color:#0F172A;font-size:13px">' + escapeHtml(data.offer) + '</div></div>';
    html += '<div><div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Affiliate Code</div><div style="font-weight:700;color:#0F172A;font-size:13px">' + escapeHtml(data.affiliate) + '</div></div>';
    html += '</div>';

    if (data.device || data.os || data.browser || data.country || data.fraud_score > 0) {
        html += '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:12px;background:#F1F5F9;border-radius:8px;margin-bottom:18px;font-size:12px">';
        html += '<div><span style="color:#64748B">Country:</span> <strong>' + escapeHtml(data.country || 'N/A') + '</strong></div>';
        html += '<div><span style="color:#64748B">Device:</span> <strong>' + escapeHtml(data.device || 'N/A') + '</strong></div>';
        html += '<div><span style="color:#64748B">OS:</span> <strong>' + escapeHtml(data.os || 'N/A') + '</strong></div>';
        html += '<div><span style="color:#64748B">Fraud Score:</span> <strong>' + escapeHtml(String(data.fraud_score)) + '</strong></div>';
        html += '</div>';
    }

    html += '<div style="margin-top:12px"><div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;margin-bottom:6px">Full User Agent String</div>';
    html += '<div style="padding:12px;background:#0F172A;color:#38BDF8;font-family:monospace;font-size:12px;border-radius:8px;word-break:break-all;white-space:pre-wrap;user-select:all;line-height:1.5">' + escapeHtml(data.ua) + '</div></div>';

    body.innerHTML = html;
    modal.style.display = 'flex';
}

function closeClickDetailModal() {
    var modal = document.getElementById('csDetailModal');
    if (modal) modal.style.display = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function filterClickStream() {
    var input = document.getElementById('cs-search');
    var filter = input ? input.value.toLowerCase().trim() : '';
    var rows = document.querySelectorAll('.cs-row');
    var visible = 0;
    rows.forEach(function(row) {
        var text = row.getAttribute('data-search') || '';
        if (!filter || text.indexOf(filter) !== -1) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });
    var cnt = document.getElementById('cs-row-count');
    if (cnt) cnt.textContent = visible + ' rows shown';
}

(function(){
    var ctx = document.getElementById('lmChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?= json_encode(array_column($hourlyClicks,'hr')) ?>;
    var data   = <?= json_encode(array_column($hourlyClicks,'cnt')) ?>;
    new Chart(ctx,{type:'bar',data:{labels:labels,datasets:[{label:'Clicks',data:data,backgroundColor:'rgba(139,92,246,.65)',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    // Auto-refresh KPIs every 15s
    setInterval(function(){
        fetch('/api/stats').then(function(r){return r.json();}).then(function(d){
            var el = document.getElementById('lm-clicks'); if(el) el.textContent = Number(d.clicks_today||0).toLocaleString();
            var el2 = document.getElementById('lm-conv'); if(el2) el2.textContent = Number(d.conv_today||0).toLocaleString();
        }).catch(function(){});
    }, 15000);
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
