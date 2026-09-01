<?php
/**
 * Tab 5: Invoice & Billing Audit Logs
 */
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;">
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:700;">Invoice Audit &amp; Activity Logs</h3>
            <p style="margin:2px 0 0 0;font-size:12.5px;color:var(--text-muted,#64748b);">
                Every invoice generation, rule update, automated cron execution, email dispatch, and status transition is recorded here.
            </p>
        </div>
    </div>

    <!-- Filter toolbar -->
    <div style="background:#f8fafc;padding:14px 24px;border-bottom:1px solid #e2e8f0;">
        <form method="GET" action="/admin/auto-invoices" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="tab" value="logs">

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">Action:</label>
                <select name="action_filter" class="form-control" style="font-size:12.5px;padding:4px 10px;height:auto;" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    <option value="auto_generated" <?= ($_GET['action_filter']??'')==='auto_generated'?'selected':'' ?>>Auto Generated (Cron)</option>
                    <option value="manual_generated" <?= ($_GET['action_filter']??'')==='manual_generated'?'selected':'' ?>>Manual Generated</option>
                    <option value="cron_run" <?= ($_GET['action_filter']??'')==='cron_run'?'selected':'' ?>>Cron Job Run</option>
                    <option value="email_sent" <?= ($_GET['action_filter']??'')==='email_sent'?'selected':'' ?>>Email Sent</option>
                    <option value="status_change" <?= ($_GET['action_filter']??'')==='status_change'?'selected':'' ?>>Status Change</option>
                    <option value="invoice_cancelled" <?= ($_GET['action_filter']??'')==='invoice_cancelled'?'selected':'' ?>>Invoice Cancelled</option>
                    <option value="save_affiliate_rule" <?= ($_GET['action_filter']??'')==='save_affiliate_rule'?'selected':'' ?>>Affiliate Rule Updated</option>
                    <option value="save_offer_rule" <?= ($_GET['action_filter']??'')==='save_offer_rule'?'selected':'' ?>>Offer Rule Updated</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">Source:</label>
                <select name="auto_filter" class="form-control" style="font-size:12.5px;padding:4px 10px;height:auto;" onchange="this.form.submit()">
                    <option value="">All Sources</option>
                    <option value="1" <?= ($_GET['auto_filter']??'')==='1'?'selected':'' ?>>Automated System</option>
                    <option value="0" <?= ($_GET['auto_filter']??'')==='0'?'selected':'' ?>>Admin Action</option>
                </select>
            </div>

            <?php if (!empty($_GET['action_filter']) || isset($_GET['auto_filter']) && $_GET['auto_filter'] !== ''): ?>
            <a href="/admin/auto-invoices?tab=logs" class="btn btn-secondary btn-sm" style="font-size:12px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table id="tbl-invoice-logs" class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Invoice / Target</th>
                    <th>Affiliate</th>
                    <th>Amount</th>
                    <th>Triggered By</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:40px;">
                        No audit log entries recorded yet.
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="font-size:12px;color:#64748b;white-space:nowrap;">
                        <?= date('M j, Y H:i:s', strtotime($l['created_at'])) ?>
                    </td>
                    <td>
                        <?php
                        $actionColors = [
                            'auto_generated'       => 'success',
                            'manual_generated'     => 'primary',
                            'cron_run'             => 'info',
                            'email_sent'           => 'secondary',
                            'status_change'        => 'warning',
                            'invoice_cancelled'    => 'danger',
                            'save_affiliate_rule'  => 'info',
                            'save_offer_rule'      => 'info',
                        ];
                        $badgeType = $actionColors[$l['action']] ?? 'muted';
                        ?>
                        <span class="badge badge-<?= $badgeType ?>" style="font-size:11px;text-transform:uppercase;">
                            <?= str_replace('_', ' ', $l['action']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($l['invoice_id'])): ?>
                            <a href="/admin/invoices/<?= $l['invoice_id'] ?>" style="font-family:monospace;font-weight:700;font-size:12px;color:#4f46e5;">
                                <?= htmlspecialchars($l['invoice_number'] ?: ('#' . $l['invoice_id'])) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($l['affiliate_id'])): ?>
                            <div style="font-weight:600;font-size:12.5px;color:#1e293b;">
                                <?= htmlspecialchars(trim($l['first_name'] . ' ' . $l['last_name']) ?: ('Aff #' . $l['affiliate_id'])) ?>
                            </div>
                            <div style="font-size:11px;color:#64748b;">
                                <?= htmlspecialchars($l['affiliate_email'] ?? '') ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Global / System</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((float)$l['amount'] > 0): ?>
                            <span style="font-weight:700;color:#10b981;font-size:13px;">
                                $<?= number_format((float)$l['amount'], 2) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($l['is_auto'])): ?>
                            <span class="badge badge-info" style="font-size:10px;">SYSTEM CRON</span>
                        <?php elseif (!empty($l['admin_first'])): ?>
                            <span style="font-size:12px;color:#334155;font-weight:600;">
                                <?= htmlspecialchars($l['admin_first'] . ' ' . $l['admin_last']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#475569;max-width:320px;">
                        <?= htmlspecialchars($l['details'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function() {
    $('#tbl-invoice-logs').DataTable({
        destroy: true,
        pageLength: 25,
        order: [[0, 'desc']],
        language: { search: 'Search logs:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>
