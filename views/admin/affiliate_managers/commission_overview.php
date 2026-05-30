<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128200; Commission Overview — All Managers</h1>
        <p>Net profit &rarr; manager commission breakdown across all affiliate managers.</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/admin/affiliate-managers" class="btn btn-secondary">← Back to Managers</a>
        <a href="?action=commission_report&export=csv" class="btn btn-secondary">&#8659; CSV</a>
    </div>
</div>

<!-- ── Network totals ─────────────────────────────────────────────────────── -->
<?php
$netTotalRevenue    = array_sum(array_column($allManagersSummary, 'total_revenue'));
$netTotalPayout     = array_sum(array_column($allManagersSummary, 'total_payout'));
$netTotalProfit     = array_sum(array_column($allManagersSummary, 'total_profit'));
$netTotalCommission = array_sum(array_column($allManagersSummary, 'total_commission'));
$netPending         = array_sum(array_column($allManagersSummary, 'commission_pending'));
$netPaid            = array_sum(array_column($allManagersSummary, 'commission_paid'));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="stat-card"><div class="stat-icon blue">&#128181;</div><div class="stat-label">Total Revenue</div><div class="stat-value">$<?= number_format($netTotalRevenue, 2) ?></div><div class="stat-sub">All advertisers</div></div>
    <div class="stat-card"><div class="stat-icon orange">&#128176;</div><div class="stat-label">Total Payout</div><div class="stat-value">$<?= number_format($netTotalPayout, 2) ?></div><div class="stat-sub">All affiliates</div></div>
    <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-label">Net Profit</div><div class="stat-value" style="color:#059669">$<?= number_format($netTotalProfit, 2) ?></div><div class="stat-sub">Revenue &minus; Payout</div></div>
    <div class="stat-card"><div class="stat-icon purple">&#127381;</div><div class="stat-label">Total Commission</div><div class="stat-value" style="color:#7C3AED">$<?= number_format($netTotalCommission, 2) ?></div><div class="stat-sub">Earned by managers</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#FEF9C3;color:#CA8A04">&#9889;</div><div class="stat-label">Pending</div><div class="stat-value" style="color:#CA8A04">$<?= number_format($netPending, 2) ?></div><div class="stat-sub">Awaiting payment</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#DCFCE7;color:#15803D">&#10003;</div><div class="stat-label">Paid</div><div class="stat-value" style="color:#15803D">$<?= number_format($netPaid, 2) ?></div><div class="stat-sub">Settled</div></div>
</div>

<!-- ── Formula reminder ───────────────────────────────────────────────────── -->
<div class="card mb-3" style="border-left:4px solid #7C3AED">
    <div class="card-body" style="padding:12px 18px;font-size:13px;color:#64748B">
        <span style="font-size:16px">&#128274;</span>
        <strong style="color:#1E293B"> Commission formula:</strong>
        <code style="background:#F1F5F9;padding:2px 8px;border-radius:4px">Net Profit = Advertiser Revenue &minus; Affiliate Payout</code> &rarr;
        <code style="background:#F1F5F9;padding:2px 8px;border-radius:4px">Commission = Net Profit &times; Rate%</code>
        &mdash; Commission percentages are <strong>hidden from managers</strong>.
    </div>
</div>

<!-- ── Per-manager table ──────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><span class="card-title">Per-Manager Summary</span></div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Manager</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Adv. Revenue</th>
                    <th class="text-right">Aff. Payout</th>
                    <th class="text-right">Net Profit</th>
                    <th class="text-right">Commission</th>
                    <th class="text-right">Pending</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Conversions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($allManagersSummary)): ?>
                <tr><td colspan="10" style="text-align:center;padding:32px;color:#9CA3AF">No commission data yet.</td></tr>
            <?php else: ?>
            <?php foreach ($allManagersSummary as $m): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= Helpers::e($m['manager_name']) ?></div>
                        <div style="font-size:11px;color:#9CA3AF"><?= Helpers::e($m['manager_email']) ?></div>
                    </td>
                    <td class="text-right" style="font-weight:700;color:#7C3AED"><?= number_format((float)$m['commission_rate'], 2) ?>%</td>
                    <td class="text-right" style="color:#2563EB">$<?= number_format((float)$m['total_revenue'], 2) ?></td>
                    <td class="text-right" style="color:#D97706">$<?= number_format((float)$m['total_payout'], 2) ?></td>
                    <td class="text-right" style="font-weight:700;color:#059669">$<?= number_format((float)$m['total_profit'], 2) ?></td>
                    <td class="text-right" style="font-weight:700;color:#059669">$<?= number_format((float)$m['total_commission'], 2) ?></td>
                    <td class="text-right" style="color:#CA8A04">$<?= number_format((float)$m['commission_pending'], 2) ?></td>
                    <td class="text-right" style="color:#15803D">$<?= number_format((float)$m['commission_paid'], 2) ?></td>
                    <td class="text-right"><?= (int)$m['conversion_count'] ?></td>
                    <td>
                        <a href="?action=commission_report&id=<?= (int)$m['manager_id'] ?>" class="btn btn-secondary btn-sm">Detail →</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($allManagersSummary)): ?>
            <tfoot>
                <tr style="background:#F8FAFC;font-weight:700">
                    <td style="padding:10px 14px;font-size:12px;color:#64748B">Network Total</td>
                    <td class="text-right" style="padding:10px 14px">&mdash;</td>
                    <td class="text-right" style="padding:10px 14px;color:#2563EB">$<?= number_format($netTotalRevenue, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#D97706">$<?= number_format($netTotalPayout, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#059669">$<?= number_format($netTotalProfit, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#059669">$<?= number_format($netTotalCommission, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#CA8A04">$<?= number_format($netPending, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#15803D">$<?= number_format($netPaid, 2) ?></td>
                    <td class="text-right" style="padding:10px 14px"><?= array_sum(array_column($allManagersSummary, 'conversion_count')) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="commission_overview_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Manager','Email','Rate %','Adv Revenue','Aff Payout','Net Profit','Total Commission','Pending','Paid','Conversions']);
    foreach ($allManagersSummary as $m) {
        fputcsv($f, [
            $m['manager_name'], $m['manager_email'], $m['commission_rate'],
            $m['total_revenue'], $m['total_payout'], $m['total_profit'],
            $m['total_commission'], $m['commission_pending'], $m['commission_paid'],
            $m['conversion_count'],
        ]);
    }
    fclose($f);
    exit;
}
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
