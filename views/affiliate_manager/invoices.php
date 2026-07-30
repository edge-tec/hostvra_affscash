<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<style>
/* 3D Glassmorphism Invoices Navigation & Control System */
.inv-tab-nav {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin-bottom: 24px !important;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.85) 100%) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    padding: 6px 10px !important;
    border-radius: 18px !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    box-shadow: 0 8px 24px -4px rgba(99, 102, 241, 0.1), 0 3px 10px rgba(0, 0, 0, 0.03) !important;
    width: fit-content !important;
}

html[data-theme="dark"] .inv-tab-nav {
    background: linear-gradient(180deg, rgba(24, 18, 55, 0.95) 0%, rgba(18, 12, 42, 0.9) 100%) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
}

.inv-tab-btn {
    padding: 10px 22px !important;
    font-size: 13.5px !important;
    font-weight: 700 !important;
    border-radius: 13px !important;
    text-decoration: none !important;
    color: #475569 !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    white-space: nowrap !important;
}

html[data-theme="dark"] .inv-tab-btn {
    color: rgba(255, 255, 255, 0.7) !important;
}

.inv-tab-btn:hover {
    color: #6366F1 !important;
    background: rgba(99, 102, 241, 0.08) !important;
}

.inv-tab-btn.active {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
}

/* 3D KPI Stats Grid */
.inv-stats-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
    gap: 18px !important;
    margin-bottom: 24px !important;
}

.inv-stat-card {
    background: #ffffff !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    display: flex !important;
    flex-direction: column !important;
}

html[data-theme="dark"] .inv-stat-card {
    background: rgba(20, 14, 45, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.inv-stat-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 12px 28px rgba(99, 102, 241, 0.16) !important;
    border-color: rgba(99, 102, 241, 0.35) !important;
}

.inv-stat-card.stat-invoices { border-top: 3.5px solid #6366F1 !important; }
.inv-stat-card.stat-pending { border-top: 3.5px solid #F59E0B !important; }
.inv-stat-card.stat-paid { border-top: 3.5px solid #10B981 !important; }

.inv-stat-label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #64748B !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    margin-bottom: 6px !important;
}

html[data-theme="dark"] .inv-stat-label {
    color: rgba(255, 255, 255, 0.65) !important;
}

.inv-stat-value {
    font-size: 32px !important;
    font-weight: 800 !important;
    color: #0F172A !important;
    line-height: 1.1 !important;
}

html[data-theme="dark"] .inv-stat-value {
    color: #ffffff !important;
}

.inv-btn-primary {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    padding: 9px 20px !important;
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    cursor: pointer !important;
}

.inv-btn-primary:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.45) !important;
    color: #ffffff !important;
}

.inv-btn-action {
    background: #F1F5F9 !important;
    color: #334155 !important;
    font-weight: 700 !important;
    font-size: 11.5px !important;
    padding: 5px 14px !important;
    border-radius: 10px !important;
    border: 1px solid #CBD5E1 !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    transition: all 0.15s ease !important;
}

.inv-btn-action:hover {
    background: #E2E8F0 !important;
    color: #0F172A !important;
    border-color: #94A3B8 !important;
    transform: translateY(-1px) !important;
}

/* Badges */
.ac-badge-approved {
    background: #10b981 !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    display: inline-block !important;
}
.ac-badge-pending {
    background: #f59e0b !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    display: inline-block !important;
}
.ac-badge-rejected {
    background: #ef4444 !important;
    color: #ffffff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    padding: 4px 10px !important;
    border-radius: 20px !important;
    display: inline-block !important;
}
</style>

<div class="page-header">
    <div><h1>&#128196; Invoices &amp; Earnings</h1><p>Manage invoices and track your commission earnings</p></div>
    <?php if ($tab === 'affiliates'): ?>
    <a href="/affiliate_manager/invoices?action=create" class="inv-btn-primary">+ Create Invoice</a>
    <?php endif; ?>
</div>

<!-- Tab Nav -->
<div class="inv-tab-nav">
    <a href="/affiliate_manager/invoices?tab=affiliates" class="inv-tab-btn <?= $tab==='affiliates'?'active':'' ?>">
        &#128101; Affiliate Invoices
    </a>
    <a href="/affiliate_manager/invoices?tab=my_invoices" class="inv-tab-btn <?= $tab==='my_invoices'?'active':'' ?>">
        &#128179; My Invoices
        <?php if (($myPending ?? 0) > 0): ?>
        <span style="background:#F59E0B;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px">$<?= number_format($myPending ?? 0, 0) ?></span>
        <?php endif; ?>
    </a>
    <?php if (!($mgrHideEarnings ?? 0)): ?>
    <a href="/affiliate_manager/invoices?action=earnings" class="inv-tab-btn <?= $tab==='earnings'?'active':'' ?>">
        &#128200; My Earnings
    </a>
    <?php endif; ?>
</div>

<?php if ($tab === 'affiliates'): ?>
<!-- ── AFFILIATE INVOICES TAB ─────────────────────────────────────────────────── -->

<div class="inv-stats-grid">
    <div class="inv-stat-card stat-invoices">
        <div class="inv-stat-label">Total Invoices</div>
        <div class="inv-stat-value"><?= number_format(count($invoices ?? [])) ?></div>
    </div>
    <div class="inv-stat-card stat-pending">
        <div class="inv-stat-label">Pending Amount</div>
        <div class="inv-stat-value" style="color:#F59E0B">$<?= number_format($totalPending ?? 0, 2) ?></div>
    </div>
    <div class="inv-stat-card stat-paid">
        <div class="inv-stat-label">Total Paid</div>
        <div class="inv-stat-value" style="color:#10B981">$<?= number_format($totalPaid ?? 0, 2) ?></div>
    </div>
</div>

<?php if (empty($affIds)): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted)">
    <p>No affiliates assigned to your account.</p>
</div></div>
<?php else: ?>
<div class="card" style="border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.03);overflow:hidden">
    <div class="card-header" style="background:#f8fafc;padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center">
        <span class="card-title" style="font-weight:800;font-size:14px;color:#1e293b">Affiliate Invoice List</span>
        <a href="/affiliate_manager/invoices?action=create" class="inv-btn-primary">+ New Invoice</a>
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
                    <?php 
                    $st = strtolower($i['status'] ?? 'draft');
                    $stClass = $st === 'paid' ? 'ac-badge-approved' : ($st === 'sent' || $st === 'pending' ? 'ac-badge-pending' : 'ac-badge-rejected');
                    ?>
                    <span class="<?= $stClass ?>"><?= ucfirst($i['status']) ?></span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                <td class="text-sm text-muted"><?= $i['paid_at'] ? date('M j, Y', strtotime($i['paid_at'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate_manager/invoices?action=view&id=<?= $i['id'] ?>" class="btn btn-secondary btn-sm" style="border-radius:10px;padding:4px 12px">View</a>
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

<div class="inv-stats-grid">
    <div class="inv-stat-card stat-invoices">
        <div class="inv-stat-label">My Total Invoices</div>
        <div class="inv-stat-value"><?= number_format(count($myInvoices)) ?></div>
    </div>
    <div class="inv-stat-card stat-pending">
        <div class="inv-stat-label">Pending Amount</div>
        <div class="inv-stat-value" style="color:#F59E0B">$<?= number_format($myPending, 2) ?></div>
    </div>
    <div class="inv-stat-card stat-paid">
        <div class="inv-stat-label">Total Paid</div>
        <div class="inv-stat-value" style="color:#10B981">$<?= number_format($myPaid, 2) ?></div>
    </div>
</div>

<div class="card" style="border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.03);overflow:hidden">
    <div class="card-header" style="background:#f8fafc;padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center">
        <span class="card-title" style="font-weight:800;font-size:14px;color:#1e293b">My Earnings Statements</span>
        <div style="display:flex;align-items:center;gap:10px">
            <span class="text-muted text-sm">Invoices generated by admin for your commissions</span>
            <a href="/affiliate_manager/invoices?action=request_invoice" class="inv-btn-primary">
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
                $st = strtolower($i['status'] ?? 'draft');
                $stClass = $st === 'paid' ? 'ac-badge-approved' : ($st === 'sent' || $st === 'pending' ? 'ac-badge-pending' : 'ac-badge-rejected');
            ?>
            <tr>
                <td><code style="font-size:12px;background:#F1F5F9;padding:3px 8px;border-radius:6px;font-weight:700;color:#6366F1"><?= Helpers::e($i['invoice_number'] ?? '#'.$i['id']) ?></code></td>
                <td class="fw-bold" style="font-size:13.5px;color:<?= $st==='paid'?'#059669':'#0F172A' ?>">$<?= number_format($i['total'] ?? 0, 2) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap">
                    <?= $i['period_start'] ? date('M j, Y', strtotime($i['period_start'])) : '—' ?>
                    <?= $i['period_end'] ? ' – '.date('M j, Y', strtotime($i['period_end'])) : '' ?>
                </td>
                <td><span class="<?= $stClass ?>"><?= ucfirst($i['status']) ?></span></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= date('M j, Y', strtotime($i['created_at'])) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= $i['due_date'] ? date('M j, Y', strtotime($i['due_date'])) : '—' ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= $i['paid_at'] ? date('M j, Y', strtotime($i['paid_at'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate_manager/invoices?action=my_invoice_view&id=<?= $i['id'] ?>" class="inv-btn-action">View</a>
                    <a href="/affiliate_manager/invoices?action=my_invoice_pdf&id=<?= $i['id'] ?>" class="inv-btn-action" target="_blank">PDF</a>
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
