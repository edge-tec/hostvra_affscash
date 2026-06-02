<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>Conversions</h1><p>Conversion records for your affiliates</p></div>
    <div class="d-flex gap-2">
        <?php foreach(['all','pending','approved','rejected'] as $s): ?>
        <a href="/affiliate_manager/conversions?status=<?= $s ?>&click_id=<?= urlencode($clickId ?? '') ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-secondary' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
            <input type="hidden" name="status" value="<?= Helpers::e($status) ?>">
            <div class="form-group mb-0">
                <label style="font-size:12px">Click ID</label>
                <input type="text" name="click_id" class="form-control" placeholder="Search by Click ID..." value="<?= Helpers::e($clickId ?? '') ?>" style="font-size:13px; min-width:180px;">
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="/affiliate_manager/conversions?status=<?= Helpers::e($status) ?>" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-mgr-conv">
            <thead><tr><th>Affiliate</th><th>Offer</th><th>Payout</th><th>Transaction</th><th>IPQS Score</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($conversions as $c): ?>
            <tr>
                <td><div class="fw-bold"><?= Helpers::e($c['aff_name']) ?></div><div class="text-sm text-muted"><?= Helpers::e($c['affiliate_code']) ?></div></td>
                <td class="text-sm"><?= Helpers::e($c['offer_name']) ?></td>
                <td class="fw-bold">$<?= number_format($c['payout'],2) ?></td>
                <td class="text-sm" style="font-family:monospace"><?= Helpers::e($c['transaction_id'] ?: '—') ?></td>
                <td data-order="<?= (int)($c['ipqs_score'] ?? -1) ?>" style="min-width:120px">
                <?php
                $_ipqs = isset($c['ipqs_score']) && $c['ipqs_score'] !== null ? (int)$c['ipqs_score'] : null;
                if ($_ipqs !== null):
                    // Colour band: 0-39 green, 40-74 amber, 75+ red
                    if ($_ipqs >= 75)      { $_iqBg='rgba(239,68,68,.15)'; $_iqFg='#991B1B'; $_iqLabel='High'; }
                    elseif ($_ipqs >= 40)  { $_iqBg='rgba(245,158,11,.15)'; $_iqFg='#92400E'; $_iqLabel='Med'; }
                    else                   { $_iqBg='rgba(16,185,129,.15)'; $_iqFg='#047857'; $_iqLabel='Low'; }
                    $_iqAction = $c['ipqs_action'] ?? 'allow';
                    $_iqActionColor = $_iqAction==='block' ? '#dc2626' : ($_iqAction==='flag' ? '#d97706' : '#16a34a');
                ?>
                <div style="display:inline-flex;flex-direction:column;gap:3px">
                    <span style="display:inline-flex;align-items:center;gap:5px;background:<?= $_iqBg ?>;color:<?= $_iqFg ?>;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:700;white-space:nowrap"
                          title="IPQS Fraud Score from IPQualityScore.com<?= $c['ipqs_checked_at'] ? ' · checked '.date('M j H:i', strtotime($c['ipqs_checked_at'])) : '' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:<?= $_iqFg ?>"></span>
                        <?= $_ipqs ?> · <?= $_iqLabel ?>
                    </span>
                    <?php
                    // Threat flags row
                    $_flags = [];
                    if (!empty($c['ipqs_is_vpn']))        $_flags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#ede9fe;color:#6d28d9">VPN</span>';
                    if (!empty($c['ipqs_is_proxy']))       $_flags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fef3c7;color:#b45309">PX</span>';
                    if (!empty($c['ipqs_is_tor']))         $_flags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fee2e2;color:#dc2626">TOR</span>';
                    if (!empty($c['ipqs_is_bot']))         $_flags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fee2e2;color:#dc2626">BOT</span>';
                    if (!empty($c['ipqs_is_datacenter']))  $_flags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#f0f9ff;color:#0369a1">DC</span>';
                    if (!empty($_flags)):
                    ?>
                    <div style="display:flex;gap:3px;flex-wrap:wrap"><?= implode('', $_flags) ?></div>
                    <?php endif; ?>
                    <span style="font-size:9px;color:<?= $_iqActionColor ?>;font-weight:600;text-transform:uppercase;letter-spacing:.04em"><?= htmlspecialchars($_iqAction, ENT_QUOTES) ?></span>
                </div>
                <?php elseif (!empty($c['click_ip']) || !empty($c['ip_address'])): ?>
                <span style="font-size:10px;color:#92400e;background:#fef3c7;border-radius:10px;padding:2px 8px">&#9203; Not checked</span>
                <?php else: ?>
                <span class="text-muted" style="font-size:11px">—</span>
                <?php endif; ?>
                </td>
                <td>
                    <?php $bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted']; ?>
                    <span class="badge badge-<?= $bm[$c['status']]??'muted' ?>"><?= $c['status'] ?></span>
                    <?php if($c['is_fraud']): ?><span class="badge badge-danger" style="margin-left:4px">FRAUD</span><?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= date('M j, H:i',strtotime($c['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function() {
    $('#tbl-mgr-conv').DataTable({
        destroy: true,
        pageLength: 25,
        order: [],
        columnDefs: [{ targets: [4], type: 'num' }],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
