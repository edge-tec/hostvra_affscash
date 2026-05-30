<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Invoices</h1><p>Affiliate payouts and advertiser billing</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/invoices?export=csv" class="btn btn-secondary">&#8595; Export CSV</a>
        <a href="/admin/invoices/auto-generate" class="btn btn-secondary">&#9881; Auto-Generate</a>
        <a href="/admin/invoices/create" class="btn btn-primary">+ Create Invoice</a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-invoices">
            <thead><tr><th>Invoice #</th><th>Type</th><th>Recipient</th><th>Period</th><th>Total</th><th>Status</th><th>Due</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><code style="font-size:12px"><?= Helpers::e($inv['invoice_number']) ?></code></td>
                <td><span class="badge badge-info" style="font-size:11px"><?= $inv['type']==='affiliate_payout'?'Aff Payout':'Adv Billing' ?></span></td>
                <td><?= Helpers::e($inv['entity_name'] ?? '—') ?></td>
                <td class="text-sm text-muted">
                    <?= $inv['period_start'] ? date('M j',strtotime($inv['period_start'])).' – '.date('M j, Y',strtotime($inv['period_end'])) : '—' ?>
                </td>
                <td class="fw-bold">$<?= number_format($inv['total'],2) ?></td>
                <td>
                    <?php $sm=['draft'=>'muted','sent'=>'info','paid'=>'success','void'=>'danger']; ?>
                    <span class="badge badge-<?= $sm[$inv['status']]??'muted' ?>"><?= $inv['status'] ?></span>
                </td>
                <td class="text-sm text-muted"><?= $inv['due_date'] ? date('M j, Y',strtotime($inv['due_date'])) : '—' ?></td>
                <td style="white-space:nowrap">
                    <a href="/admin/invoices/<?= $inv['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <a href="/admin/invoices/<?= $inv['id'] ?>?print=1" class="btn btn-secondary btn-sm" target="_blank">Print</a>
                    <a href="/admin/invoices?action=edit&id=<?= $inv['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="/admin/invoices?action=delete" style="display:inline" onsubmit="return confirm('Delete invoice <?= Helpers::e($inv['invoice_number']) ?>? This cannot be undone.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function() {
    $('#tbl-invoices').DataTable({ destroy:true, pageLength:25, order:[[6,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries'} });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
