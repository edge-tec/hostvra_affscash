<?php
/**
 * Tab 4: Generated Invoices List
 */
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700;">Affiliate Invoices &amp; Billing History</h3>
            <p style="margin:2px 0 0 0;font-size:12.5px;color:var(--text-muted,#64748b);">
                Track generated invoices, payment status, PDF downloads, and email logs.
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="recalculateBalances()" style="display:flex;align-items:center;gap:5px;background:#f1f5f9;color:#0f172a;font-weight:600;" title="Restore exact balance from approved conversions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Sync / Restore Balances
            </button>
            <a href="/admin/invoices?export=csv" class="btn btn-secondary btn-sm" style="display:flex;align-items:center;gap:4px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export CSV
            </a>
            <a href="/admin/auto-invoices?tab=manual" class="btn btn-primary btn-sm" style="display:flex;align-items:center;gap:4px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                + New Invoice
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div style="background:#f8fafc;padding:14px 24px;border-bottom:1px solid #e2e8f0;display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
        <form method="GET" action="/admin/auto-invoices" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;width:100%;">
            <input type="hidden" name="tab" value="invoices">
            
            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">Status:</label>
                <select name="status" class="form-control" style="font-size:12.5px;padding:4px 10px;height:auto;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="draft" <?= ($_GET['status']??'')==='draft'?'selected':'' ?>>Draft</option>
                    <option value="sent" <?= ($_GET['status']??'')==='sent'?'selected':'' ?>>Sent (Pending)</option>
                    <option value="viewed" <?= ($_GET['status']??'')==='viewed'?'selected':'' ?>>Viewed</option>
                    <option value="paid" <?= ($_GET['status']??'')==='paid'?'selected':'' ?>>Paid</option>
                    <option value="void" <?= ($_GET['status']??'')==='void'?'selected':'' ?>>Void / Cancelled</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">Generation:</label>
                <select name="is_auto" class="form-control" style="font-size:12.5px;padding:4px 10px;height:auto;" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="1" <?= ($_GET['is_auto']??'')==='1'?'selected':'' ?>>Auto Generated (Cron)</option>
                    <option value="0" <?= ($_GET['is_auto']??'')==='0'?'selected':'' ?>>Manual</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">Affiliate:</label>
                <select name="affiliate_id" class="form-control" style="font-size:12.5px;padding:4px 10px;height:auto;max-width:200px;" onchange="this.form.submit()">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= (isset($_GET['affiliate_id']) && $_GET['affiliate_id'] == $a['id']) ? 'selected' : '' ?>>
                        #<?= $a['id'] ?> - <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($_GET['status']) || isset($_GET['is_auto']) && $_GET['is_auto'] !== '' || !empty($_GET['affiliate_id'])): ?>
            <a href="/admin/auto-invoices?tab=invoices" class="btn btn-secondary btn-sm" style="font-size:12px;">Reset Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table id="tbl-invoices-list" class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Affiliate</th>
                    <th>Type</th>
                    <th>Billing Period</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Generated</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>
                        <div style="font-family:monospace;font-weight:700;font-size:12.5px;color:#4f46e5;">
                            <?= htmlspecialchars($inv['invoice_number']) ?>
                        </div>
                        <?php if (!empty($inv['is_auto'])): ?>
                            <span class="badge badge-info" style="font-size:10px;padding:1px 6px;">AUTO CRON</span>
                        <?php else: ?>
                            <span class="badge badge-muted" style="font-size:10px;padding:1px 6px;">MANUAL</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;color:#1e293b;">
                            <?= htmlspecialchars(trim($inv['first_name'] . ' ' . $inv['last_name']) ?: 'Affiliate #' . $inv['affiliate_id']) ?>
                        </div>
                        <div style="font-size:11.5px;color:#64748b;">
                            <?= htmlspecialchars($inv['affiliate_email'] ?? '') ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-muted" style="font-size:11px;">
                            <?= $inv['type'] === 'affiliate_payout' ? 'Aff Payout' : 'Adv Billing' ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:#475569;">
                        <?= $inv['period_start'] ? date('M j', strtotime($inv['period_start'])) . ' – ' . date('M j, Y', strtotime($inv['period_end'])) : '—' ?>
                    </td>
                    <td style="font-size:12.5px;color:#64748b;">
                        $<?= number_format((float)$inv['subtotal'], 2) ?>
                    </td>
                    <td>
                        <div style="font-weight:800;font-size:14px;color:#10b981;">
                            $<?= number_format((float)$inv['total'], 2) ?>
                        </div>
                        <?php if ((float)($inv['adjustment'] ?? 0) != 0): ?>
                        <div style="font-size:10.5px;color:#64748b;">
                            Adj: <?= ((float)$inv['adjustment'] > 0 ? '+' : '') . number_format((float)$inv['adjustment'], 2) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $st = $inv['status'];
                        $badgeClass = match($st) {
                            'paid' => 'success',
                            'sent' => 'primary',
                            'viewed' => 'warning',
                            'void', 'cancelled' => 'danger',
                            default => 'muted',
                        };
                        ?>
                        <span class="badge badge-<?= $badgeClass ?>" style="font-size:11px;text-transform:capitalize;">
                            <?= htmlspecialchars($st) ?>
                        </span>
                        <?php if (!empty($inv['email_sent_at'])): ?>
                        <div style="font-size:10px;color:#059669;margin-top:2px;" title="Email sent <?= $inv['email_sent_at'] ?>">
                            &#10003; Emailed
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#64748b;">
                        <?= $inv['due_date'] ? date('M j, Y', strtotime($inv['due_date'])) : '—' ?>
                    </td>
                    <td style="font-size:12px;color:#64748b;">
                        <?= date('M j, Y', strtotime($inv['created_at'])) ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a href="/admin/invoices/<?= $inv['id'] ?>" class="btn btn-secondary btn-sm" title="View Full Details">View</a>
                        <a href="/admin/auto-invoices?action=download_pdf&id=<?= $inv['id'] ?>" class="btn btn-primary btn-sm" target="_blank" title="Download PDF">&#128196; PDF</a>
                        
                        <!-- Status Update Quick Dropdown / Actions -->
                        <div style="display:inline-block;position:relative;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleRowMenu(<?= $inv['id'] ?>)">&#8942;</button>
                            <div id="rowMenu_<?= $inv['id'] ?>" style="display:none;position:absolute;right:0;top:100%;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);z-index:100;min-width:160px;padding:6px 0;text-align:left;">
                                <?php if ($inv['status'] !== 'paid'): ?>
                                <button type="button" onclick="updateInvStatus(<?= $inv['id'] ?>, 'paid')" style="display:block;width:100%;padding:6px 14px;border:none;background:none;text-align:left;font-size:12.5px;color:#059669;cursor:pointer;">
                                    &#10003; Mark as Paid
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" onclick="resendInvEmail(<?= $inv['id'] ?>)" style="display:block;width:100%;padding:6px 14px;border:none;background:none;text-align:left;font-size:12.5px;color:#4f46e5;cursor:pointer;">
                                    &#9993; Resend Email
                                </button>

                                <a href="/admin/invoices/<?= $inv['id'] ?>?print=1" target="_blank" style="display:block;padding:6px 14px;text-decoration:none;font-size:12.5px;color:#334155;">
                                    &#128424; Print View
                                </a>

                                <hr style="margin:4px 0;border:0;border-top:1px solid #f1f5f9;">
                                <?php if (!in_array($inv['status'], ['void', 'cancelled'])): ?>
                                <button type="button" onclick="cancelInvoice(<?= $inv['id'] ?>)" style="display:block;width:100%;padding:6px 14px;border:none;background:none;text-align:left;font-size:12.5px;color:#d97706;cursor:pointer;">
                                    &#10005; Void &amp; Refund
                                </button>
                                <?php endif; ?>
                                <button type="button" onclick="deleteInvoice(<?= $inv['id'] ?>)" style="display:block;width:100%;padding:6px 14px;border:none;background:none;text-align:left;font-size:12.5px;color:#dc2626;cursor:pointer;">
                                    &#128465; Delete &amp; Restore
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function() {
    $('#tbl-invoices-list').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[0, 'desc']],
        language: { 
            search: 'Search invoices:', 
            lengthMenu: 'Show _MENU_ entries',
            emptyTable: 'No invoices generated yet.'
        }
    });
});

function toggleRowMenu(id) {
    var el = document.getElementById('rowMenu_' + id);
    var isShown = el.style.display === 'block';
    document.querySelectorAll('[id^="rowMenu_"]').forEach(function(m){ m.style.display = 'none'; });
    if (!isShown) el.style.display = 'block';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="rowMenu_"]') && !e.target.matches('button')) {
        document.querySelectorAll('[id^="rowMenu_"]').forEach(function(m){ m.style.display = 'none'; });
    }
});

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
}

function recalculateBalances() {
    if (!confirm('Recalculate & restore exact balance for all affiliates based on approved conversions? (No extra balance will be added)')) return;
    var fd = new FormData();
    fd.append('_token', getCsrfToken());
    fd.append('action', 'recalculate_balances');

    fetch('/admin/auto-invoices?tab=invoices', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        alert(d && d.message ? d.message : 'Balances synchronized successfully.');
        window.location.reload();
    })
    .catch(function(err){
        console.error(err);
        window.location.reload();
    });
}

function updateInvStatus(id, status) {
    if (!confirm('Mark this invoice as ' + status.toUpperCase() + '?')) return;
    var fd = new FormData();
    fd.append('_token', getCsrfToken());
    fd.append('action', 'update_status');
    fd.append('invoice_id', id);
    fd.append('status', status);

    fetch('/admin/auto-invoices?tab=invoices', { 
        method: 'POST', 
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd 
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d && d.success) {
            window.location.reload();
        } else {
            alert(d && d.error ? d.error : 'Error updating status');
        }
    })
    .catch(function(err){
        console.error(err);
        window.location.reload();
    });
}

function resendInvEmail(id) {
    if (!confirm('Resend email notification with invoice details to affiliate?')) return;
    var fd = new FormData();
    fd.append('_token', getCsrfToken());
    fd.append('action', 'resend_email');
    fd.append('invoice_id', id);

    fetch('/admin/auto-invoices?tab=invoices', { 
        method: 'POST', 
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd 
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        alert(d && d.message ? d.message : 'Email sent.');
        window.location.reload();
    })
    .catch(function(err){
        console.error(err);
        alert('Network or server error sending email.');
    });
}

function cancelInvoice(id) {
    var reason = prompt('Please enter reason for cancelling this invoice (conversions will be unlinked and balance refunded):');
    if (reason === null) return;

    var fd = new FormData();
    fd.append('_token', getCsrfToken());
    fd.append('action', 'cancel_invoice');
    fd.append('invoice_id', id);
    fd.append('reason', reason);

    fetch('/admin/auto-invoices?tab=invoices', { 
        method: 'POST', 
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd 
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d && d.success) {
            alert('Invoice cancelled and balance restored.');
            window.location.reload();
        } else {
            alert(d && d.error ? d.error : 'Error cancelling invoice');
        }
    })
    .catch(function(err){
        console.error(err);
        alert('Network or server error cancelling invoice.');
    });
}

function deleteInvoice(id) {
    if (!confirm('Permanently delete this invoice? The conversions will be unlinked and the affiliate balance will be restored exactly to its pre-invoice state.')) return;
    var fd = new FormData();
    fd.append('_token', getCsrfToken());
    fd.append('action', 'delete_invoice');
    fd.append('invoice_id', id);

    fetch('/admin/auto-invoices?tab=invoices', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d && d.success) {
            alert(d.message || 'Invoice deleted and balance reconciled.');
            window.location.reload();
        } else {
            alert(d && d.error ? d.error : 'Failed to delete invoice.');
        }
    })
    .catch(function(err){
        console.error(err);
        alert('Network or server error deleting invoice.');
    });
}
</script>
