<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128200; Commission Report</h1>
        <p><?= Helpers::e($manager['first_name'] . ' ' . $manager['last_name']) ?> &mdash; <?= Helpers::e($manager['email']) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <a href="/admin/affiliate-managers/<?= (int)$manager['mgr_id'] ?>" class="btn btn-secondary">← Back to Manager</a>
        <a href="/admin/affiliate-managers?action=commission_report" class="btn btn-secondary">All Managers</a>
        <?php if ((float)$mgrBalance['balance'] > 0): ?>
        <a href="/admin/affiliate-managers?action=generate_invoice&id=<?= (int)$manager['mgr_id'] ?>" class="btn btn-primary">
            &#128196; Generate Invoice ($<?= number_format((float)$mgrBalance['balance'], 2) ?>)
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Summary KPI Cards ─────────────────────────────────────────────────── -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">

    <div class="stat-card">
        <div class="stat-icon" style="background:#EFF6FF;color:#2563EB">&#128181;</div>
        <div class="stat-label">Advertiser Revenue</div>
        <div class="stat-value">$<?= number_format((float)$commissionSummary['total_revenue'], 2) ?></div>
        <div class="stat-sub">Total received from advertisers</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#FEF3C7;color:#D97706">&#128176;</div>
        <div class="stat-label">Affiliate Payout</div>
        <div class="stat-value">$<?= number_format((float)$commissionSummary['total_payout'], 2) ?></div>
        <div class="stat-sub">Paid out to affiliates</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#059669">&#9989;</div>
        <div class="stat-label">Net Profit</div>
        <div class="stat-value" style="color:#059669">$<?= number_format((float)$commissionSummary['total_profit'], 2) ?></div>
        <div class="stat-sub">Revenue &minus; Payout</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#F5F3FF;color:#7C3AED">&#127381;</div>
        <div class="stat-label">Commission Rate</div>
        <div class="stat-value"><?= number_format((float)$manager['commission_rate'], 2) ?>%</div>
        <div class="stat-sub">Of net profit (hidden from manager)</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#ECFDF5;color:#10B981">&#128176;</div>
        <div class="stat-label">Total Commission</div>
        <div class="stat-value" style="color:#059669">$<?= number_format((float)$commissionSummary['total_commission'], 2) ?></div>
        <div class="stat-sub">Earned (non-reversed)</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#FEF9C3;color:#CA8A04">&#9889;</div>
        <div class="stat-label">Pending Commission</div>
        <div class="stat-value" style="color:#CA8A04">$<?= number_format((float)$commissionSummary['commission_pending'], 2) ?></div>
        <div class="stat-sub">Awaiting settlement</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#15803D">&#10003;</div>
        <div class="stat-label">Paid Commission</div>
        <div class="stat-value" style="color:#15803D">$<?= number_format((float)$commissionSummary['commission_paid'], 2) ?></div>
        <div class="stat-sub">Settled to manager</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#EEF2FF;color:#4F46E5">&#128200;</div>
        <div class="stat-label">Available Balance</div>
        <div class="stat-value" style="color:#4F46E5">$<?= number_format((float)$mgrBalance['balance'], 2) ?></div>
        <div class="stat-sub">Ready to invoice</div>
    </div>

</div>

<!-- ── Commission Formula Note ───────────────────────────────────────────── -->
<div class="card mb-3" style="border-left:4px solid #7C3AED">
    <div class="card-body" style="padding:12px 18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <span style="font-size:18px">&#128274;</span>
        <div>
            <div style="font-weight:700;font-size:13px;color:#1E293B">Commission Formula (Admin-Only)</div>
            <div style="font-size:13px;color:#64748B;margin-top:2px">
                <code style="background:#F1F5F9;padding:2px 8px;border-radius:4px;font-size:12px">
                    Net Profit = Advertiser Revenue &minus; Affiliate Payout
                </code>
                &nbsp;&rarr;&nbsp;
                <code style="background:#F1F5F9;padding:2px 8px;border-radius:4px;font-size:12px">
                    Commission = Net Profit &times; <?= number_format((float)$manager['commission_rate'], 2) ?>%
                </code>
                &nbsp;&mdash;&nbsp;
                <strong>Never calculated from affiliate payout</strong>
            </div>
        </div>
    </div>
</div>

<!-- ── Date Filter ───────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="action" value="commission_report">
            <input type="hidden" name="id" value="<?= (int)$manager['mgr_id'] ?>">
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a href="?action=commission_report&id=<?= (int)$manager['mgr_id'] ?>" class="btn btn-secondary">Reset</a>
            <a href="?action=commission_report&id=<?= (int)$manager['mgr_id'] ?>&from=<?= Helpers::e($from) ?>&to=<?= Helpers::e($to) ?>&export=csv"
               class="btn btn-secondary" style="margin-left:auto">&#8659; CSV</a>
        </form>
    </div>
</div>

<!-- ── Per-Conversion Table ──────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Commission Ledger &mdash; <?= count($commissionRecords) ?> records (<?= Helpers::e($from) ?> to <?= Helpers::e($to) ?>)</span>
    </div>
    <div class="table-wrap">
        <table class="table" id="commission-tbl">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Conversion</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Type</th>
                    <th class="text-right">Adv. Revenue</th>
                    <th class="text-right">Aff. Payout</th>
                    <th class="text-right">Net Profit</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Commission</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($commissionRecords)): ?>
                <tr><td colspan="12" style="text-align:center;padding:32px;color:#9CA3AF">No commission records for this period.</td></tr>
            <?php else: ?>
            <?php foreach ($commissionRecords as $r): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px;color:#6B7280"><?= date('M j, Y', strtotime($r['converted_at'])) ?></td>
                    <td style="font-family:monospace;font-size:11px;color:#6B7280"><?= Helpers::e(substr($r['conversion_uuid'], 0, 12)) ?>…</td>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= Helpers::e($r['affiliate_name']) ?></div>
                        <div style="font-size:11px;color:#9CA3AF"><?= Helpers::e($r['affiliate_code']) ?></div>
                    </td>
                    <td style="font-size:13px"><?= Helpers::e($r['offer_name']) ?></td>
                    <td>
                        <?php if ($r['is_smartlink']): ?>
                            <span style="background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">Smartlink</span>
                        <?php else: ?>
                            <span style="background:#F1F5F9;color:#64748B;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">Standard</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right" style="font-size:13px;color:#2563EB;font-weight:600">$<?= number_format((float)$r['advertiser_revenue'], 4) ?></td>
                    <td class="text-right" style="font-size:13px;color:#D97706">$<?= number_format((float)$r['affiliate_payout'], 4) ?></td>
                    <td class="text-right" style="font-size:13px;font-weight:700;color:#059669">$<?= number_format((float)$r['net_profit'], 4) ?></td>
                    <td class="text-right" style="font-size:13px;color:#7C3AED"><?= number_format((float)$r['commission_rate'], 2) ?>%</td>
                    <td class="text-right" style="font-size:13px;font-weight:700;color:#059669">$<?= number_format((float)$r['commission_amount'], 4) ?></td>
                    <td>
                        <?php
                        $stColors = ['pending'=>['#FEF9C3','#854D0E'],'approved'=>['#DCFCE7','#166534'],'paid'=>['#D1FAE5','#059669'],'reversed'=>['#FEE2E2','#991B1B']];
                        $stc = $stColors[$r['status']] ?? ['#F1F5F9','#64748B'];
                        ?>
                        <span style="background:<?= $stc[0] ?>;color:<?= $stc[1] ?>;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;text-transform:uppercase"><?= Helpers::e($r['status']) ?></span>
                    </td>
                    <td style="white-space:nowrap">
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="cr_action" value="approve">
                            <input type="hidden" name="mc_id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="from" value="<?= Helpers::e($from) ?>">
                            <input type="hidden" name="to" value="<?= Helpers::e($to) ?>">
                            <button type="submit" class="btn btn-success btn-sm" style="font-size:11px;padding:2px 8px"
                                    onclick="return confirm('Approve this $<?= number_format((float)$r['commission_amount'],2) ?> commission?')">
                                &#10003; Approve
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if (in_array($r['status'], ['pending','approved'])): ?>
                        <form method="POST" style="display:inline;margin-left:2px">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="cr_action" value="delete">
                            <input type="hidden" name="mc_id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="from" value="<?= Helpers::e($from) ?>">
                            <input type="hidden" name="to" value="<?= Helpers::e($to) ?>">
                            <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:2px 8px"
                                    onclick="return confirm('Delete this commission record?\nThis will deduct $<?= number_format((float)$r['commission_amount'],2) ?> from the manager balance.')">
                                &#128465; Delete
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'paid'): ?>
                        <span style="font-size:11px;color:#9CA3AF">Paid</span>
                        <?php endif; ?>
                        <?php if ($r['status'] === 'reversed'): ?>
                        <span style="font-size:11px;color:#9CA3AF">Reversed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($commissionRecords)): ?>
            <tfoot>
                <tr style="background:#F8FAFC;font-weight:700">
                    <td colspan="5" style="padding:10px 14px;font-size:12px;color:#64748B">Totals (<?= count($commissionRecords) ?> records)</td>

                    <td class="text-right" style="padding:10px 14px;color:#2563EB">$<?= number_format(array_sum(array_column($commissionRecords, 'advertiser_revenue')), 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#D97706">$<?= number_format(array_sum(array_column($commissionRecords, 'affiliate_payout')), 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#059669">$<?= number_format(array_sum(array_column($commissionRecords, 'net_profit')), 2) ?></td>
                    <td class="text-right" style="padding:10px 14px;color:#7C3AED">&mdash;</td>
                    <td class="text-right" style="padding:10px 14px;color:#059669">$<?= number_format(array_sum(array_column($commissionRecords, 'commission_amount')), 2) ?></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php
// CSV export
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="commission_' . $manager['mgr_id'] . '_' . $from . '_' . $to . '.csv"');
    header('Pragma: no-cache');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Date','Conversion ID','Affiliate','Aff Code','Offer','Type','Adv Revenue','Aff Payout','Net Profit','Commission Rate %','Commission Amount','Status']);
    foreach ($commissionRecords as $r) {
        fputcsv($f, [
            date('Y-m-d', strtotime($r['converted_at'])),
            $r['conversion_uuid'],
            $r['affiliate_name'],
            $r['affiliate_code'],
            $r['offer_name'],
            $r['is_smartlink'] ? 'Smartlink' : 'Standard',
            $r['advertiser_revenue'],
            $r['affiliate_payout'],
            $r['net_profit'],
            $r['commission_rate'],
            $r['commission_amount'],
            $r['status'],
        ]);
    }
    fclose($f);
    exit;
}
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
