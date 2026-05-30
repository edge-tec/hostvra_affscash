<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>&#128196; Invoices &amp; Earnings</h1><p>Manage invoices and track your commission earnings</p></div>
    <?php if ($tab === 'affiliates'): ?>
    <a href="/affiliate_manager/invoices?action=create" class="btn btn-primary btn-sm">+ Create Invoice</a>
    <?php endif; ?>
</div>

<!-- Tab Nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <a href="/affiliate_manager/invoices?tab=affiliates"
       style="padding:10px 20px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;
              color:<?= $tab==='affiliates'?'#7C3AED':'#64748B' ?>;
              border-bottom:<?= $tab==='affiliates'?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        &#128101; Affiliate Invoices
    </a>
    <a href="/affiliate_manager/invoices?tab=my_invoices"
       style="padding:10px 20px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;
              color:<?= $tab==='my_invoices'?'#7C3AED':'#64748B' ?>;
              border-bottom:<?= $tab==='my_invoices'?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        &#128179; My Invoices
        <?php if (($myPending ?? 0) > 0): ?>
        <span style="background:#F59E0B;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px">$<?= number_format($myPending ?? 0, 0) ?></span>
        <?php endif; ?>
    </a>
    <?php if (!($mgrHideEarnings ?? 0)): ?>
    <a href="/affiliate_manager/invoices?action=earnings"
       style="padding:10px 20px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;
              color:<?= $tab==='earnings'?'#7C3AED':'#64748B' ?>;
              border-bottom:<?= $tab==='earnings'?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        &#128200; My Earnings
    </a>
    <?php endif; ?>
</div>

<?php if ($tab === 'affiliates'): ?>
<!-- ── AFFILIATE INVOICES TAB ─────────────────────────────────────────────────── -->

<div class="stats-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-icon blue">&#128196;</div>
        <div class="stat-label">Total Invoices</div>
        <div class="stat-value"><?= number_format(count($invoices ?? [])) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9203;</div>
        <div class="stat-label">Pending Amount</div>
        <div class="stat-value" style="color:var(--warning)">$<?= number_format($totalPending ?? 0, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-label">Total Paid</div>
        <div class="stat-value" style="color:var(--secondary)">$<?= number_format($totalPaid ?? 0, 2) ?></div>
    </div>
</div>

<?php if (empty($affIds)): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted)">
    <p>No affiliates assigned to your account.</p>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Affiliate Invoice List</span>
        <a href="/affiliate_manager/invoices?action=create" class="btn btn-primary btn-sm">+ New Invoice</a>
    </div>
    <div class="table-wrap">
        <table id="tbl-invoices">
            <thead><tr>
                <th>#</th><th>AFFILIATE</th><th>INVOICE #</th><th>AMOUNT</th><th>PERIOD</th><th>STATUS</th><th>CREATED</th><th>PAID AT</th><th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php if (empty($invoices)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:32px">No invoices found for your affiliates.</td></tr>
            <?php else: ?>
            <?php foreach ($invoices as $i): ?>
            <tr>
                <td><?= $i['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($i['aff_name']) ?></div>
                    <div class="text-muted text-sm"><?= Helpers::e($i['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:12px;background:#F1F5F9;padding:2px 6px;border-radius:4px"><?= Helpers::e($i['invoice_number'] ?? '#'.$i['id']) ?></code></td>
                <td class="fw-bold">$<?= number_format($i['total'] ?? $i['amount'] ?? $i['subtotal'] ?? 0, 2) ?></td>
                <td class="text-sm text-muted">
                    <?= Helpers::e($i['period_start'] ?? '—') ?> – <?= Helpers::e($i['period_end'] ?? '—') ?>
                </td>
                <td>
                    <?php $sc = ['sent'=>'warning','paid'=>'success','draft'=>'info','void'=>'muted']; ?>
                    <span class="badge badge-<?= $sc[$i['status']] ?? 'muted' ?>"><?= ucfirst($i['status']) ?></span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                <td class="text-sm text-muted"><?= $i['paid_at'] ? date('M j, Y', strtotime($i['paid_at'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate_manager/invoices?action=view&id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php elseif ($tab === 'my_invoices'): ?>
<!-- ── MY INVOICES TAB (admin-generated for this manager) ─────────────────────── -->

<div class="stats-grid mb-3" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-icon blue">&#128196;</div>
        <div class="stat-label">My Total Invoices</div>
        <div class="stat-value"><?= number_format(count($myInvoices)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9203;</div>
        <div class="stat-label">Pending Amount</div>
        <div class="stat-value" style="color:var(--warning)">$<?= number_format($myPending, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-label">Total Paid</div>
        <div class="stat-value" style="color:var(--secondary)">$<?= number_format($myPaid, 2) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">My Earnings Statements</span>
        <div style="display:flex;align-items:center;gap:10px">
            <span class="text-muted text-sm">Invoices generated by admin for your commissions</span>
            <a href="/affiliate_manager/invoices?action=request_invoice" class="btn btn-primary btn-sm">
                &#128229; Request Payout Invoice
            </a>
        </div>
    </div>
    <div class="table-wrap">
        <table id="tbl-my-invoices">
            <thead><tr>
                <th>INVOICE #</th><th>AMOUNT</th><th>PERIOD</th><th>STATUS</th><th>ISSUED</th><th>DUE DATE</th><th>PAID AT</th><th>ACTIONS</th>
            </tr></thead>
            <tbody>
            <?php if (empty($myInvoices)): ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:48px">
                <div style="font-size:40px;margin-bottom:12px">&#128196;</div>
                <div style="font-weight:600;margin-bottom:6px">No invoices yet</div>
                <div style="font-size:13px">Your admin will generate commission invoices here.</div>
                <div style="margin-top:12px"><a href="/affiliate_manager/invoices?action=earnings" class="btn btn-secondary btn-sm">View My Earnings →</a></div>
            </td></tr>
            <?php else: ?>
            <?php foreach ($myInvoices as $i):
                $sc = ['sent'=>'warning','paid'=>'success','draft'=>'info','void'=>'muted'];
            ?>
            <tr>
                <td><code style="font-size:12px;background:#F1F5F9;padding:2px 6px;border-radius:4px"><?= Helpers::e($i['invoice_number'] ?? '#'.$i['id']) ?></code></td>
                <td class="fw-bold" style="color:<?= $i['status']==='paid'?'var(--secondary)':'inherit' ?>">$<?= number_format($i['total'] ?? 0, 2) ?></td>
                <td class="text-sm text-muted">
                    <?= $i['period_start'] ? date('M j, Y', strtotime($i['period_start'])) : '—' ?>
                    <?= $i['period_end'] ? ' – '.date('M j, Y', strtotime($i['period_end'])) : '' ?>
                </td>
                <td><span class="badge badge-<?= $sc[$i['status']] ?? 'muted' ?>"><?= ucfirst($i['status']) ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                <td class="text-sm text-muted"><?= $i['due_date'] ? date('M j, Y', strtotime($i['due_date'])) : '—' ?></td>
                <td class="text-sm text-muted"><?= $i['paid_at'] ? date('M j, Y', strtotime($i['paid_at'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate_manager/invoices?action=my_invoice_view&id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="/affiliate_manager/invoices?action=my_invoice_pdf&id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">PDF</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'earnings'): ?>
<!-- ── MY EARNINGS TAB ────────────────────────────────────────────────────────── -->

<!-- Balance overview cards -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="stat-card">
        <div class="stat-icon" style="background:#DCFCE7;color:#059669">&#128181;</div>
        <div class="stat-label">Available Balance</div>
        <div class="stat-value" style="color:#059669">$<?= number_format((float)$earningsBalance['balance'], 2) ?></div>
        <div class="stat-sub">Awaiting payment</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#FEF9C3;color:#CA8A04">&#9889;</div>
        <div class="stat-label">Pending</div>
        <div class="stat-value" style="color:#CA8A04">$<?= number_format((float)$earningsBalance['pending'], 2) ?></div>
        <div class="stat-sub">Being reviewed</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#EEF2FF;color:#4F46E5">&#10003;</div>
        <div class="stat-label">Approved</div>
        <div class="stat-value" style="color:#4F46E5">$<?= number_format((float)$earningsBalance['approved'], 2) ?></div>
        <div class="stat-sub">Approved for payment</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#15803D">&#128176;</div>
        <div class="stat-label">Total Paid Out</div>
        <div class="stat-value" style="color:#15803D">$<?= number_format((float)$earningsBalance['paid'], 2) ?></div>
        <div class="stat-sub">Settled to you</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F5F3FF;color:#7C3AED">&#128200;</div>
        <div class="stat-label">All-Time Earned</div>
        <div class="stat-value" style="color:#7C3AED">$<?= number_format((float)$earningsBalance['total_earned'], 2) ?></div>
        <div class="stat-sub">Lifetime total</div>
    </div>
</div>

<!-- Date filter -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/affiliate_manager/invoices" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="action" value="earnings">
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from ?? date('Y-m-d', strtotime('-29 days'))) ?>">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to ?? date('Y-m-d')) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">Apply</button>
            <a href="/affiliate_manager/invoices?action=earnings" class="btn btn-secondary btn-sm" style="align-self:flex-end">Reset</a>
        </form>
    </div>
</div>

<!-- Earnings history table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Earnings History</span>
        <span class="text-muted text-sm"><?= number_format(count($earningsHistory ?? [])) ?> records</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-earnings" style="font-size:13px">
            <thead><tr>
                <th>DATE</th><th>OFFER</th><th>AFFILIATE</th><th>TYPE</th><th class="text-right">EARNINGS</th><th>STATUS</th>
            </tr></thead>
            <tbody>
            <?php if (empty($earningsHistory)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:40px">
                <div style="font-size:32px;margin-bottom:8px">&#128200;</div>
                <div style="font-weight:600;margin-bottom:4px">No earnings in this period</div>
                <div style="font-size:12px">Earnings are calculated from approved conversions of your assigned affiliates.</div>
            </td></tr>
            <?php else: ?>
            <?php
            $totalEarningsInPeriod = 0;
            foreach ($earningsHistory as $r):
                $totalEarningsInPeriod += (float)$r['commission_amount'];
                $stColors = [
                    'pending'  => ['#FEF9C3','#854D0E'],
                    'approved' => ['#DCFCE7','#166534'],
                    'paid'     => ['#D1FAE5','#059669'],
                    'reversed' => ['#FEE2E2','#991B1B'],
                ];
                $stc = $stColors[$r['status']] ?? ['#F1F5F9','#64748B'];
            ?>
            <tr>
                <td style="white-space:nowrap;color:#6B7280;font-size:12px">
                    <?= date('M j, Y', strtotime($r['converted_at'] ?? $r['calculated_at'])) ?>
                </td>
                <td>
                    <div class="fw-bold" style="font-size:13px"><?= Helpers::e($r['offer_name']) ?></div>
                </td>
                <td>
                    <div style="font-size:13px"><?= Helpers::e($r['affiliate_name']) ?></div>
                    <div style="font-size:11px;color:#9CA3AF"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td>
                    <?php if ($r['is_smartlink']): ?>
                    <span style="background:#EEF2FF;color:#4F46E5;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">Smartlink</span>
                    <?php else: ?>
                    <span style="background:#F1F5F9;color:#64748B;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">Standard</span>
                    <?php endif; ?>
                </td>
                <td class="text-right" style="font-weight:700;color:#059669;font-size:14px">
                    $<?= number_format((float)$r['commission_amount'], 2) ?>
                </td>
                <td>
                    <span style="background:<?= $stc[0] ?>;color:<?= $stc[1] ?>;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;text-transform:uppercase">
                        <?= Helpers::e($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($earningsHistory)): ?>
            <tfoot>
                <tr style="background:#F8FAFC;font-weight:700">
                    <td colspan="4" style="padding:10px 14px;font-size:12px;color:#64748B">
                        Period Total (<?= count($earningsHistory) ?> records)
                    </td>
                    <td class="text-right" style="padding:10px 14px;color:#059669;font-size:14px">
                        $<?= number_format($totalEarningsInPeriod, 2) ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    ['#tbl-invoices','#tbl-my-invoices','#tbl-earnings'].forEach(function(id){
        var $t = $(id);
        if ($t.find('tbody tr').length > 1) {
            $t.DataTable({destroy:true,pageLength:25,order:[[0,'desc']],language:{search:'Search:',emptyTable:'No records'}});
        }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
