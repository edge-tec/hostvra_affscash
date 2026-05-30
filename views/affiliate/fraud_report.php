<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; Fraud Report</h1>
        <p>High-risk conversions flagged by our fraud detection system.</p>
    </div>
    <a href="?export=csv" class="btn btn-secondary btn-sm">&#11123; Export CSV</a>
</div>

<!-- Summary banner -->
<div style="background:linear-gradient(135deg,#FEF2F2,#FFE4E6);border:1px solid #FECACA;border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="font-size:28px">&#9888;</div>
    <div style="flex:1;min-width:200px">
        <div style="font-weight:700;color:#991B1B;font-size:14px">Only High Risk Fraud Conversions are listed here</div>
        <div style="font-size:12px;color:#7F1D1D;margin-top:2px">
            <strong><?= number_format($count30) ?></strong> high-risk conversion<?= $count30 === 1 ? '' : 's' ?> detected in the last 30 days.
            All conversions in this list have been flagged as potentially fraudulent — please review the source/quality of this traffic.
        </div>
    </div>
</div>

<?php if (empty($conversions)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px 24px;color:#64748B">
        <div style="font-size:42px;margin-bottom:12px">&#9989;</div>
        <div style="font-size:15px;font-weight:600;color:#1E293B">No high-risk fraud conversions</div>
        <div style="font-size:13px;margin-top:6px">Your traffic is clean — none of your conversions have been flagged.</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">High Risk Fraud Conversions</span>
        <span class="text-muted" style="font-size:12px"><?= number_format(count($conversions)) ?> total</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Risk</th>
                    <th>Conversion ID</th>
                    <th>Offer</th>
                    <th>Status</th>
                    <th>Payout</th>
                    <th>Country</th>
                    <th>IP</th>
                    <th>Detected At</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($conversions as $c):
                $isHl = $highlightCid !== '' && $c['conversion_id'] === $highlightCid;
                $bm   = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr<?= $isHl ? ' style="background:#FEF2F2"' : '' ?>>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;white-space:nowrap"
                          title="This conversion has been detected as a High Risk Fraud Conversion.">
                        &#9888; High Risk Fraud Conversion
                    </span>
                </td>
                <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($c['conversion_id']),0,12) ?>…</td>
                <td><?= Helpers::e($c['offer_name'] ?: '—') ?></td>
                <td>
                    <span class="badge badge-<?= $bm[$c['status']] ?? 'muted' ?>"><?= Helpers::e($c['status']) ?></span>
                    <?php if ($c['status'] === 'rejected' && !empty($c['rejection_reason'])):
                        $_frFull  = (string)$c['rejection_reason'];
                        $_frShort = mb_strlen($_frFull) > 60 ? mb_substr($_frFull, 0, 60) . '…' : $_frFull;
                    ?>
                    <div style="margin-top:3px;font-size:11px;color:#b91c1c;line-height:1.4;max-width:180px;white-space:normal"
                         title="<?= Helpers::e($_frFull) ?>">
                        <strong>Reason:</strong> <?= Helpers::e($_frShort) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($c['rejected_at'])): ?>
                    <div style="font-size:10px;color:#94A3B8;margin-top:2px">Rejected <?= Helpers::e(date('M j, H:i', strtotime($c['rejected_at']))) ?></div>
                    <?php endif; ?>
                </td>
                <td>$<?= number_format((float)$c['payout'], 2) ?></td>
                <td><?= !empty($c['country']) ? Helpers::flag($c['country']) . ' ' . Helpers::e($c['country']) : '—' ?></td>
                <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address'] ?: '—') ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($c['converted_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
