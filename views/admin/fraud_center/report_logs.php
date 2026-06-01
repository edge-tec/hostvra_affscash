<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="fds-page-header">
    <div class="fds-page-header-left">
        <div class="fds-page-icon">📋</div>
        <div>
            <div class="fds-page-title">Fraud Report Logs</div>
            <div class="fds-page-sub">Automated click &amp; conversion fraud reports sent to affiliates</div>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <a href="/admin/settings?tab=fraud_reports" class="btn btn-sm" style="background:#EEF2FF;color:#4F46E5;border:none;font-weight:600">
            ⚙️ Configure
        </a>
    </div>
</div>

<!-- Filter Bar -->
<?php
$fraudFilterUrl = '/admin/fraud-center/report-logs';
include BASE_PATH . '/views/partials/fraud_filter_bar.php';
?>

<!-- Extra type filter -->
<form method="GET" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
    <input type="hidden" name="from"  value="<?= Helpers::e($dr['from']) ?>">
    <input type="hidden" name="to"    value="<?= Helpers::e($dr['to']) ?>">
    <select name="affiliate_id" class="form-control" style="width:220px">
        <option value="0">All Affiliates</option>
        <?php foreach ($affiliateList as $a): ?>
        <option value="<?= $a['id'] ?>" <?= $affId == $a['id'] ? 'selected' : '' ?>>
            <?= Helpers::e($a['affiliate_code'] . ' — ' . $a['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <select name="report_type" class="form-control" style="width:180px">
        <option value=""            <?= $typeFilter === '' ? 'selected' : '' ?>>All Types</option>
        <option value="click"       <?= $typeFilter === 'click' ? 'selected' : '' ?>>🖱 Click Reports</option>
        <option value="conversion"  <?= $typeFilter === 'conversion' ? 'selected' : '' ?>>💰 Conversion Reports</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

<!-- KPI strip -->
<div class="fds-kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
    <div class="fds-kpi"><div class="fds-kpi-label">Total Reports</div><div class="fds-kpi-val"><?= number_format($kpis['total_reports']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">🖱 Click Reports</div><div class="fds-kpi-val"><?= number_format($kpis['click_reports']) ?></div></div>
    <div class="fds-kpi fds-kpi-warn"><div class="fds-kpi-label">💰 Conversion Reports</div><div class="fds-kpi-val"><?= number_format($kpis['conv_reports']) ?></div></div>
    <div class="fds-kpi"><div class="fds-kpi-label">✉️ Emails Sent</div><div class="fds-kpi-val"><?= number_format($kpis['emails_sent']) ?></div></div>
</div>

<!-- Report Logs Table -->
<div class="fds-card">
    <div class="fds-card-header">
        <span class="fds-card-title">Report Logs (<?= number_format($total) ?> total)</span>
    </div>
    <div class="fds-table-wrap">
        <table class="fds-table">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Type</th>
                    <th>Period</th>
                    <th>Key Metrics</th>
                    <th>Quality</th>
                    <th>Email</th>
                    <th>Generated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
                $data    = json_decode($log['data_json'], true) ?? [];
                $isClick = $log['report_type'] === 'click';
                $quality = $isClick ? ($data['click_quality'] ?? 100) : ($data['conversion_quality'] ?? 100);
                $qClass  = $quality >= 80 ? 'fds-badge-low' : ($quality >= 50 ? 'fds-badge-medium' : 'fds-badge-critical');
            ?>
            <tr>
                <td>
                    <strong><?= Helpers::e($log['affiliate_code'] ?? '—') ?></strong><br>
                    <span class="fds-text-sm fds-text-muted"><?= Helpers::e($log['affiliate_name'] ?? '') ?></span>
                </td>
                <td>
                    <?php if ($isClick): ?>
                    <span class="fds-badge" style="background:#EEF2FF;color:#4F46E5">🖱 Click</span>
                    <?php else: ?>
                    <span class="fds-badge" style="background:#FFF7ED;color:#D97706">💰 Conversion</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748B">
                    <?= date('M d H:i', strtotime($log['period_from'])) ?><br>
                    → <?= date('M d H:i', strtotime($log['period_to'])) ?>
                </td>
                <td style="font-size:12px">
                    <?php if ($isClick): ?>
                        Total: <b><?= number_format($data['total_clicks'] ?? 0) ?></b>
                        &nbsp;|&nbsp; Fraud: <b style="color:#EF4444"><?= number_format($data['fraud_clicks'] ?? 0) ?></b><br>
                        Bot: <?= number_format($data['bot_clicks'] ?? 0) ?>
                        &nbsp;|&nbsp; High Risk: <?= number_format($data['high_risk_clicks'] ?? 0) ?>
                        &nbsp;|&nbsp; Med Risk: <?= number_format($data['medium_risk_clicks'] ?? 0) ?>
                    <?php else: ?>
                        Total: <b><?= number_format($data['total_conversions'] ?? 0) ?></b>
                        &nbsp;|&nbsp; Fraud: <b style="color:#EF4444"><?= number_format($data['fraud_conversions'] ?? 0) ?></b><br>
                        Rate: <b style="color:#EF4444"><?= $data['fraud_rate'] ?? 0 ?>%</b>
                        &nbsp;|&nbsp; Invalid: <?= number_format($data['invalid_leads'] ?? 0) ?>
                        &nbsp;|&nbsp; Suspicious: <?= number_format($data['suspicious_activity'] ?? 0) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="fds-badge <?= $qClass ?>"><?= $quality ?>%</span>
                </td>
                <td>
                    <?php if ($log['email_sent']): ?>
                    <span class="fds-badge fds-badge-low">✓ Sent</span>
                    <?php else: ?>
                    <span class="fds-badge fds-badge-muted">Not sent</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#64748B;white-space:nowrap">
                    <?= date('M d, Y', strtotime($log['created_at'])) ?><br>
                    <?= date('H:i', strtotime($log['created_at'])) ?>
                </td>
                <td>
                    <button onclick="frToggleDetail(<?= $log['id'] ?>)" class="btn btn-sm" style="background:#F1F5F9;color:#64748B;border:none;font-size:12px">
                        JSON ↓
                    </button>
                </td>
            </tr>
            <tr id="fr-detail-<?= $log['id'] ?>" style="display:none">
                <td colspan="8" style="background:#F8FAFC;padding:12px 20px">
                    <pre style="font-size:11px;color:#334155;margin:0;white-space:pre-wrap;background:#fff;padding:10px;border:1px solid #E2E8F0;border-radius:6px"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?></pre>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="8" class="fds-empty">No reports found for this period. Run the cron job or trigger it manually via the Cron URL.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= fds_pagination($pag, '/admin/fraud-center/report-logs', array_filter(['affiliate_id' => $affId ?: null, 'report_type' => $typeFilter ?: null, 'from' => $dr['from'], 'to' => $dr['to']])) ?>
</div>

<script>
function frToggleDetail(id) {
    var row = document.getElementById('fr-detail-' + id);
    if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
