<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>Invoice #<?= Helpers::e($invoice['invoice_number'] ?? $invoice['id']) ?></h1>
        <p>For <?= Helpers::e($invoice['aff_name']) ?> (<?= Helpers::e($invoice['affiliate_code']) ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/affiliate_manager/invoices" class="btn btn-secondary">← Back</a>

    </div>
</div>

<div class="grid-2">
    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Invoice Details</span></div>
            <div class="card-body">
                <table style="width:100%;font-size:14px;border-collapse:collapse">
                    <tr><td style="padding:8px 0;color:var(--text-muted);width:40%">Invoice Number</td><td class="fw-bold"><?= Helpers::e($invoice['invoice_number'] ?? '#'.$invoice['id']) ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Status</td>
                        <td>
                            <?php $sc=['pending'=>'warning','paid'=>'success','cancelled'=>'muted','overdue'=>'danger']; ?>
                            <span class="badge badge-<?= $sc[$invoice['status']] ?? 'muted' ?>"><?= ucfirst($invoice['status']) ?></span>
                        </td>
                    </tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Amount</td><td class="fw-bold" style="font-size:18px;color:var(--secondary)">$<?= number_format($invoice['total'] ?? $invoice['amount'] ?? $invoice['subtotal'] ?? 0, 2) ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Period</td><td><?= Helpers::e($invoice['period_start'] ?? '—') ?> – <?= Helpers::e($invoice['period_end'] ?? '—') ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Due Date</td><td><?= $invoice['due_date'] ? date('M j, Y', strtotime($invoice['due_date'])) : '—' ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Created</td><td><?= date('M j, Y H:i', strtotime($invoice['created_at'])) ?></td></tr>
                    <?php if ($invoice['paid_at']): ?>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Paid At</td><td style="color:var(--secondary)"><?= date('M j, Y H:i', strtotime($invoice['paid_at'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Affiliate Info</span></div>
            <div class="card-body">
                <table style="width:100%;font-size:14px;border-collapse:collapse">
                    <tr><td style="padding:8px 0;color:var(--text-muted);width:40%">Name</td><td class="fw-bold"><?= Helpers::e($invoice['aff_name']) ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Code</td><td><code><?= Helpers::e($invoice['affiliate_code']) ?></code></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Email</td><td><?= Helpers::e($invoice['aff_email']) ?></td></tr>
                </table>
            </div>
        </div>
        <?php if (!empty($invoice['notes'])): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Notes</span></div>
            <div class="card-body"><p style="font-size:13px;color:var(--text-muted)"><?= Helpers::e($invoice['notes']) ?></p></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
