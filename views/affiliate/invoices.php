<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div><h1>My Invoices</h1><p>Payout invoices issued to your account</p></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-aff-invoices">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Period</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Issued</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($inv['invoice_number']) ?></code></td>
                <td class="text-sm text-muted">
                    <?= $inv['period_start'] ? date('M j', strtotime($inv['period_start'])) . ' – ' . date('M j, Y', strtotime($inv['period_end'])) : '—' ?>
                </td>
                <td>$<?= number_format($inv['subtotal'], 2) ?></td>
                <td><?= $inv['tax_rate'] > 0 ? number_format($inv['tax_rate'], 1) . '%' : '—' ?></td>
                <td class="fw-bold">$<?= number_format($inv['total'], 2) ?></td>
                <td>
                    <?php $badgeMap = ['draft'=>'muted','sent'=>'info','paid'=>'success','void'=>'danger']; ?>
                    <span class="badge badge-<?= $badgeMap[$inv['status']] ?? 'muted' ?>"><?= ucfirst($inv['status']) ?></span>
                </td>
                <td class="text-sm text-muted"><?= $inv['due_date'] ? date('M j, Y', strtotime($inv['due_date'])) : '—' ?></td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
                <td style="white-space:nowrap">
                    <a href="/affiliate/invoices/<?= $inv['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="/affiliate/invoices?action=download_pdf&id=<?= $inv['id'] ?>" class="btn btn-primary btn-sm" download title="Download PDF">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        PDF
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($invoices)): ?>
        <div class="text-center text-muted" style="padding:32px">No invoices yet</div>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    $('#tbl-aff-invoices').DataTable({ destroy:true, pageLength:25, order:[[7,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries'} });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
