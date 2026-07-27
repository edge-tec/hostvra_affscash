<?php
// ── Print mode must be detected BEFORE the layout is included ─────────────
// The layout outputs the full sidebar/header HTML. Any header() call after
// that would trigger "headers already sent" warnings.
if (isset($_GET['print'])):
    header('Content-Type: text/html; charset=UTF-8');
    $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title><?= Helpers::e($invoice['invoice_number']) ?></title>
<style>body{font-family:sans-serif;color:#1e293b;margin:0;padding:40px} table{width:100%;border-collapse:collapse} th,td{padding:8px 12px;border:1px solid #e2e8f0;font-size:13px} th{background:#f8fafc;font-weight:600} .total-row td{font-weight:700;background:#f1f5f9}</style></head><body>
<h2><?= Helpers::e($appName) ?> — Invoice</h2>
<p><strong>Invoice #:</strong> <?= Helpers::e($invoice['invoice_number']) ?><br>
<strong>To:</strong> <?= Helpers::e(trim($aff['first_name'].' '.$aff['last_name'])) ?> &lt;<?= Helpers::e($aff['email']) ?>&gt;<br>
<strong>Period:</strong> <?= $invoice['period_start'] ? date('M j, Y', strtotime($invoice['period_start'])) . ' – ' . date('M j, Y', strtotime($invoice['period_end'])) : '—' ?><br>
<strong>Status:</strong> <?= ucfirst($invoice['status']) ?><br>
<strong>Due:</strong> <?= $invoice['due_date'] ? date('M j, Y', strtotime($invoice['due_date'])) : '—' ?></p>
<table><thead><tr><th>Offer / Description</th><th>Offer ID</th><th>Conversions</th><th>Rate</th><th>Amount</th></tr></thead><tbody>
<?php foreach ($items as $item):
    $isSl     = !empty($item['smartlink_id']) || (isset($item['description']) && stripos($item['description'], 'smartlink') !== false);
    $pOffCode = (!$isSl && !empty($item['offer_id'])) ? 'OFF-' . str_pad((int)$item['offer_id'], 4, '0', STR_PAD_LEFT) : '—';
    $pConvIds = !empty($item['conversion_ids']) && is_array($item['conversion_ids']) ? implode(', ', array_slice($item['conversion_ids'], 0, 3)) . (count($item['conversion_ids']) > 3 ? ' +' . (count($item['conversion_ids']) - 3) . ' more' : '') : '';
?>
<tr>
  <td><?= Helpers::e($item['description']) ?><?= $pConvIds ? '<br><small style="color:#666">IDs: '.Helpers::e($pConvIds).'</small>' : '' ?></td>
  <td><?= Helpers::e($pOffCode) ?></td>
  <td><?= (int)($item['qty'] ?? 1) ?></td>
  <td>$<?= number_format((float)($item['rate'] ?? 0),2) ?></td>
  <td>$<?= number_format((float)($item['amount'] ?? 0),2) ?></td>
</tr>
<?php endforeach; ?>
<tr class="total-row"><td colspan="3" style="text-align:right">Subtotal</td><td>$<?= number_format($invoice['subtotal'],2) ?></td></tr>
<?php if ($invoice['tax_rate'] > 0): ?><tr><td colspan="3" style="text-align:right">Tax (<?= $invoice['tax_rate'] ?>%)</td><td>$<?= number_format($invoice['tax_amount'],2) ?></td></tr><?php endif; ?>
<tr class="total-row"><td colspan="3" style="text-align:right">Total</td><td>$<?= number_format($invoice['total'],2) ?></td></tr>
</tbody></table>
<?php if ($invoice['notes']): ?><p><?= Helpers::e($invoice['notes']) ?></p><?php endif; ?>
<script>window.print();</script></body></html>
<?php exit; endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1><?= Helpers::e($invoice['invoice_number']) ?></h1>
        <p>Issued <?= date('F j, Y', strtotime($invoice['created_at'])) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/affiliate/billing-history" class="btn btn-secondary">← Back</a>
        <a href="/affiliate/billing-history?action=download_pdf&id=<?= $invoice['id'] ?>" class="btn btn-primary" download>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
        <a href="/affiliate/billing-history/<?= $invoice['id'] ?>?print=1" class="btn btn-secondary" target="_blank">&#128424; Print</a>
    </div>
</div>

<div class="card" style="max-width:760px">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:16px">
            <div>
                <div style="font-size:22px;font-weight:800;color:#1E293B"><?= Helpers::e($invoice['invoice_number']) ?></div>
                <div class="text-sm text-muted">Issued <?= date('F j, Y', strtotime($invoice['created_at'])) ?></div>
            </div>
            <div style="text-align:right">
                <?php $badgeMap = ['draft'=>'muted','sent'=>'info','paid'=>'success','void'=>'danger']; ?>
                <span class="badge badge-<?= $badgeMap[$invoice['status']] ?? 'muted' ?>" style="font-size:14px;padding:6px 14px"><?= ucfirst($invoice['status']) ?></span>
                <?php if ($invoice['due_date']): ?>
                <div class="text-sm text-muted" style="margin-top:4px">Due: <?= date('M j, Y', strtotime($invoice['due_date'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="background:#F8FAFC;border-radius:6px;padding:12px 16px;margin-bottom:20px">
            <div style="font-size:13px"><strong>Bill To:</strong> <?= Helpers::e(trim($aff['first_name'].' '.$aff['last_name'])) ?></div>
            <div style="font-size:13px;color:var(--text-muted)"><?= Helpers::e($aff['email']) ?><?= $aff['company'] ? ' — '.Helpers::e($aff['company']) : '' ?></div>
            <?php if ($invoice['period_start']): ?>
            <div style="font-size:13px;color:var(--text-muted);margin-top:4px">Period: <?= date('M j, Y', strtotime($invoice['period_start'])) ?> – <?= date('M j, Y', strtotime($invoice['period_end'])) ?></div>
            <?php endif; ?>
        </div>

        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;min-width:420px">
            <thead>
                <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                    <th style="padding:10px 12px;text-align:left;font-size:13px">Offer / Description</th>
                    <th style="padding:10px 12px;text-align:right;font-size:13px;width:90px">Conversions</th>
                    <th style="padding:10px 12px;text-align:right;font-size:13px;width:100px">Rate/Conv.</th>
                    <th style="padding:10px 12px;text-align:right;font-size:13px;width:110px">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $isSl       = !empty($item['smartlink_id']) || (isset($item['description']) && stripos($item['description'], 'smartlink') !== false);
                $hasDetail  = !$isSl && !empty($item['offer_id']);
                $convIds    = $item['conversion_ids'] ?? [];
                $convCount  = (int)($item['conversion_count'] ?? ($item['qty'] ?? 1));
                $offCode    = $hasDetail ? 'OFF-' . str_pad((int)$item['offer_id'], 4, '0', STR_PAD_LEFT) : null;
            ?>
            <tr style="border-bottom:1px solid #F1F5F9">
                <td style="padding:10px 12px;font-size:14px">
                    <?= Helpers::e($item['description']) ?>
                    <?php if ($hasDetail): ?>
                    <div style="margin-top:5px;display:flex;flex-wrap:wrap;gap:6px">
                        <span style="font-size:11px;background:#EFF6FF;color:#2563EB;border-radius:4px;padding:2px 8px;font-weight:600">Offer ID: <?= Helpers::e($offCode) ?></span>
                        <?php if ($convCount): ?>
                        <span style="font-size:11px;background:#F0FDF4;color:#16A34A;border-radius:4px;padding:2px 8px;font-weight:600"><?= $convCount ?> conversion<?= $convCount !== 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($convIds) && is_array($convIds)): ?>
                    <div style="margin-top:5px;font-size:10px;color:#9CA3AF;word-break:break-all;line-height:1.7">
                        <span style="font-weight:600;color:#6B7280">Conversion IDs:</span>
                        <?= Helpers::e(implode(', ', array_slice($convIds, 0, 5))) ?><?php if (count($convIds) > 5) echo ' <span style="color:#6B7280">+' . (count($convIds) - 5) . ' more</span>'; ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="padding:10px 12px;text-align:right;font-size:14px"><?= (int)($item['qty'] ?? 1) ?></td>
                <td style="padding:10px 12px;text-align:right;font-size:14px">$<?= number_format((float)($item['rate'] ?? 0), 2) ?></td>
                <td style="padding:10px 12px;text-align:right;font-size:14px;font-weight:600">$<?= number_format((float)($item['amount'] ?? 0), 2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:var(--text-muted)">Subtotal</td><td style="padding:8px 12px;text-align:right">$<?= number_format($invoice['subtotal'],2) ?></td></tr>
                <?php if ($invoice['tax_rate'] > 0): ?>
                <tr><td colspan="3" style="padding:8px 12px;text-align:right;font-size:13px;color:var(--text-muted)">Tax (<?= $invoice['tax_rate'] ?>%)</td><td style="padding:8px 12px;text-align:right">$<?= number_format($invoice['tax_amount'],2) ?></td></tr>
                <?php endif; ?>
                <tr style="background:#F8FAFC;font-weight:700;font-size:16px"><td colspan="3" style="padding:10px 12px;text-align:right">Total</td><td style="padding:10px 12px;text-align:right;color:#10B981">$<?= number_format($invoice['total'],2) ?></td></tr>
            </tfoot>
        </table>
        </div>

        <?php if ($invoice['notes']): ?>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;padding:12px 16px;font-size:13px"><?= Helpers::e($invoice['notes']) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
