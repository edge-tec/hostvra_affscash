<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>Invoice <?= Helpers::e($invoice['invoice_number'] ?? '#'.$invoice['id']) ?></h1>
        <p>Your commission statement issued by admin</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/affiliate_manager/invoices?tab=my_invoices" class="btn btn-secondary">← Back</a>
        <a href="/affiliate_manager/invoices?action=my_invoice_pdf&id=<?= $invoice['id'] ?>" class="btn btn-secondary" target="_blank">&#128196; Download PDF</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Invoice Details</span></div>
            <div class="card-body">
                <table style="width:100%;font-size:14px;border-collapse:collapse">
                    <tr><td style="padding:8px 0;color:var(--text-muted);width:40%">Invoice #</td>
                        <td class="fw-bold"><code style="background:#F1F5F9;padding:2px 8px;border-radius:4px"><?= Helpers::e($invoice['invoice_number'] ?? '#'.$invoice['id']) ?></code></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Status</td>
                        <td>
                            <?php $sc=['sent'=>'warning','paid'=>'success','draft'=>'info','void'=>'muted']; ?>
                            <span class="badge badge-<?= $sc[$invoice['status']] ?? 'muted' ?>"><?= ucfirst($invoice['status']) ?></span>
                        </td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Total Amount</td>
                        <td class="fw-bold" style="font-size:20px;color:var(--secondary)">$<?= number_format($invoice['total'] ?? 0, 2) ?></td></tr>
                    <?php if ((float)($invoice['tax_amount'] ?? 0) > 0): ?>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Subtotal</td><td>$<?= number_format($invoice['subtotal'] ?? 0, 2) ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Tax (<?= number_format($invoice['tax_rate'] ?? 0, 1) ?>%)</td><td>$<?= number_format($invoice['tax_amount'] ?? 0, 2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Period</td>
                        <td><?= $invoice['period_start'] ? date('M j, Y', strtotime($invoice['period_start'])) : '—' ?>
                            <?= $invoice['period_end'] ? ' – '.date('M j, Y', strtotime($invoice['period_end'])) : '' ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Due Date</td>
                        <td><?= $invoice['due_date'] ? date('M j, Y', strtotime($invoice['due_date'])) : '—' ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Issued</td>
                        <td><?= date('M j, Y H:i', strtotime($invoice['created_at'])) ?></td></tr>
                    <?php if ($invoice['paid_at']): ?>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Paid At</td>
                        <td style="color:var(--secondary);font-weight:600"><?= date('M j, Y H:i', strtotime($invoice['paid_at'])) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Notes</span></div>
            <div class="card-body"><p style="font-size:13px;color:var(--text-muted);white-space:pre-wrap"><?= Helpers::e($invoice['notes']) ?></p></div>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Billed To</span></div>
            <div class="card-body">
                <table style="width:100%;font-size:14px;border-collapse:collapse">
                    <tr><td style="padding:8px 0;color:var(--text-muted);width:40%">Name</td>
                        <td class="fw-bold"><?= Helpers::e($entityName) ?></td></tr>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Email</td>
                        <td><?= Helpers::e($entityEmail) ?></td></tr>
                    <?php if ($entityCompany): ?>
                    <tr><td style="padding:8px 0;color:var(--text-muted)">Company</td>
                        <td><?= Helpers::e($entityCompany) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if (!empty($items)): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Line Items</span></div>
            <div class="table-wrap">
                <table style="font-size:13px">
                    <thead><tr><th>Description</th><th style="text-align:center">Qty</th><th style="text-align:right">Rate</th><th style="text-align:right">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $li): ?>
                    <tr>
                        <td><?= Helpers::e($li['description'] ?? '') ?></td>
                        <td style="text-align:center"><?= number_format((float)($li['qty'] ?? 1), 2) ?></td>
                        <td style="text-align:right">$<?= number_format((float)($li['rate'] ?? 0), 2) ?></td>
                        <td style="text-align:right;font-weight:600">$<?= number_format((float)($li['amount'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #E2E8F0">
                            <td colspan="3" style="text-align:right;padding:10px 14px;font-weight:700">Total</td>
                            <td style="text-align:right;padding:10px 14px;font-weight:800;font-size:16px;color:var(--secondary)">$<?= number_format($invoice['total'] ?? 0, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
