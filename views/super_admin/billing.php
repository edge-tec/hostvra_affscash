<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin-bottom: 30px;">💳 Financial Operations &amp; Invoices</h2>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; padding: 14px 20px; border-radius: 8px; margin-bottom: 25px;">
    🎉 <?= Helpers::e($_GET['success']) ?>
</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 14px 20px; border-radius: 8px; margin-bottom: 25px;">
    ⚠️ <?= Helpers::e($_GET['error']) ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 30px;">

    <!-- Cryptocurrency Verification Queue -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title" style="display: flex; align-items: center; gap: 8px;">
                <span>🪙</span> Crypto Payment Verification Queue
            </h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="cryptoPaymentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant Network</th>
                        <th>Amount Due</th>
                        <th>Crypto Coin</th>
                        <th>Transaction Hash / TXID</th>
                        <th>Wallet Address</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cryptoPayments as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td><strong style="color: #FFFFFF; font-size: 14px;"><?= Helpers::e($p['company_name']) ?></strong></td>
                            <td><strong style="color: #A5B4FC;">$<?= number_format($p['amount'], 2) ?></strong></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: rgba(6, 182, 212, 0.15); color: #22D3EE;">
                                    <?= Helpers::e($p['currency']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <code style="background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 4px; color: #34D399; font-size: 11.5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= Helpers::e($p['tx_hash']) ?>">
                                        <?= Helpers::e($p['tx_hash']) ?>
                                    </code>
                                    <button class="btn btn-sm" onclick="navigator.clipboard.writeText('<?= Helpers::e($p['tx_hash']) ?>'); alert('TXID copied!');" style="padding: 2px 6px; font-size: 10px; background: rgba(255,255,255,0.05); color: #C7D2FE; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px;">Copy</button>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 11.5px; font-family: monospace; color: #94A3B8;" title="<?= Helpers::e($p['address']) ?>">
                                    <?= substr($p['address'], 0, 8) ?>...<?= substr($p['address'], -8) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'confirmed'): ?>
                                    <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: rgba(16, 185, 129, 0.15); color: #34D399;">CONFIRMED</span>
                                <?php elseif ($p['status'] === 'rejected'): ?>
                                    <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: rgba(239, 68, 68, 0.15); color: #FCA5A5;">REJECTED</span>
                                <?php else: ?>
                                    <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: rgba(245, 158, 11, 0.15); color: #FBBF24; animation: pulse 2s infinite;">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                            <td>
                                <?php if ($p['status'] === 'pending'): ?>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="/super_admin/billing?action=confirm_crypto&id=<?= $p['id'] ?>" class="btn btn-sm btn-success" style="font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-decoration: none;" onclick="return confirm('Confirm ledger verification & activate workspace subscription?');">Approve</a>
                                        <a href="/super_admin/billing?action=reject_crypto&id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" style="font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 4px; text-decoration: none;" onclick="return confirm('Are you sure you want to reject this transaction hash?');">Reject</a>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #64748B;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Billing Transaction Logs -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">SaaS Billing Transactions</h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="billingLogsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant Network</th>
                        <th>Billing Action</th>
                        <th>Total Paid</th>
                        <th>Transaction Details</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingLogs as $b): ?>
                        <tr>
                            <td><strong>#<?= $b['id'] ?></strong></td>
                            <td><strong style="color: #FFFFFF; font-size: 14px;"><?= Helpers::e($b['company_name']) ?></strong></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: rgba(99, 102, 241, 0.15); color: #818CF8;">
                                    <?= strtoupper(str_replace('_', ' ', $b['action'])) ?>
                                </span>
                            </td>
                            <td><strong style="color: #34D399;">$<?= number_format($b['amount'], 2) ?></strong></td>
                            <td><span style="font-size: 12.5px; color: var(--text-muted);"><?= Helpers::e($b['details']) ?></span></td>
                            <td><?= date('M d, Y H:i:s', strtotime($b['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tenant System Invoices -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">SaaS System Invoices</h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="invoicesTable">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Tenant Network</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Total Due</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong><?= Helpers::e($inv['invoice_number']) ?></strong></td>
                            <td><strong><?= Helpers::e($inv['company_name']) ?></strong></td>
                            <td>$<?= number_format($inv['amount'], 2) ?></td>
                            <td>$<?= number_format($inv['tax_amount'], 2) ?></td>
                            <td><strong style="color: #A78BFA;">$<?= number_format($inv['total'], 2) ?></strong></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: <?= $inv['status'] === 'paid' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $inv['status'] === 'paid' ? '#34D399' : '#FCA5A5' ?>;">
                                    <?= strtoupper($inv['status']) ?>
                                </span>
                            </td>
                            <td><?= $inv['due_date'] ? date('M d, Y', strtotime($inv['due_date'])) : 'N/A' ?></td>
                            <td><?= date('M d, Y', strtotime($inv['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#cryptoPaymentsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 5,
        "language": {
            "search": "Filter Crypto:"
        }
    });

    $('#billingLogsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Logs:"
        }
    });

    $('#invoicesTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Invoices:"
        }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>

