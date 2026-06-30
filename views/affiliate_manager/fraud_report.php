<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; Fraud Report</h1>
        <p>High-risk conversions across the affiliates you manage.</p>
    </div>
    <a href="?export=csv" class="btn btn-secondary btn-sm">&#11123; Export CSV</a>
</div>

<div style="background:linear-gradient(135deg,#FEF2F2,#FFE4E6);border:1px solid #FECACA;border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="font-size:28px">&#9888;</div>
    <div style="flex:1;min-width:200px">
        <div style="font-weight:700;color:#991B1B;font-size:14px">Only High Risk Fraud Conversions are listed here</div>
        <div style="font-size:12px;color:#7F1D1D;margin-top:2px">
            <strong><?= number_format($count30) ?></strong> high-risk conversion<?= $count30 === 1 ? '' : 's' ?> detected in the last 30 days across your affiliates.
        </div>
    </div>
</div>

<?php if (empty($conversions)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px 24px;color:#64748B">
        <div style="font-size:42px;margin-bottom:12px">&#9989;</div>
        <div style="font-size:15px;font-weight:600;color:#1E293B">No high-risk fraud conversions</div>
        <div style="font-size:13px;margin-top:6px">None of your affiliates have flagged conversions.</div>
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
                    <th>Affiliate</th>
                    <th>Conversion ID</th>
                    <th>Offer</th>
                    <th>Status</th>
                    <th>IPQS Score</th>
                    <th>Payout</th>
                    <th>Country</th>
                    <th>IP</th>
                    <th>Detected At</th>
                    <th>Action</th>
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
                <td>
                    <div class="fw-bold"><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px;font-family:monospace"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($c['conversion_id']),0,12) ?>…</td>
                <td><?= Helpers::e($c['offer_name'] ?: '—') ?></td>
                <td>
                    <span class="badge badge-<?= $bm[$c['status']] ?? 'muted' ?>"><?= Helpers::e($c['status']) ?></span>
                    <?php if ($c['status'] === 'rejected' && !empty($c['rejection_reason'])):
                        $_mrFull  = (string)$c['rejection_reason'];
                        $_mrShort = mb_strlen($_mrFull) > 60 ? mb_substr($_mrFull, 0, 60) . '…' : $_mrFull;
                    ?>
                    <div style="margin-top:3px;font-size:11px;color:#b91c1c;line-height:1.4;max-width:180px;white-space:normal"
                         title="<?= Helpers::e($_mrFull) ?>">
                        <strong>Reason:</strong> <?= Helpers::e($_mrShort) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($c['rejected_at'])): ?>
                    <div style="font-size:10px;color:#94A3B8;margin-top:2px">Rejected <?= Helpers::e(date('M j, H:i', strtotime($c['rejected_at']))) ?></div>
                    <?php endif; ?>
                </td>
                <td style="min-width:130px">
                <?php
                $_frIpqs = isset($c['ipqs_score']) && $c['ipqs_score'] !== null ? (int)$c['ipqs_score'] : null;
                if ($_frIpqs !== null):
                    if ($_frIpqs >= 75)     { $_frBg='rgba(239,68,68,.15)'; $_frFg='#991B1B'; $_frLbl='High'; }
                    elseif ($_frIpqs >= 40) { $_frBg='rgba(245,158,11,.15)'; $_frFg='#92400E'; $_frLbl='Med'; }
                    else                    { $_frBg='rgba(16,185,129,.15)'; $_frFg='#047857'; $_frLbl='Low'; }
                    $_frAction = $c['ipqs_action'] ?? 'allow';
                    $_frActionColor = $_frAction==='block' ? '#dc2626' : ($_frAction==='flag' ? '#d97706' : '#16a34a');
                ?>
                <div style="display:inline-flex;flex-direction:column;gap:3px">
                    <span style="display:inline-flex;align-items:center;gap:5px;background:<?= $_frBg ?>;color:<?= $_frFg ?>;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:700;white-space:nowrap"
                          title="IPQualityScore real-time fraud score<?= $c['ipqs_checked_at'] ? ' · checked '.date('M j H:i',strtotime($c['ipqs_checked_at'])) : '' ?>">
                        <span style="width:6px;height:6px;border-radius:50%;background:<?= $_frFg ?>"></span>
                        <?= $_frIpqs ?> · <?= $_frLbl ?>
                    </span>
                    <?php
                    $_frFlags = [];
                    if (!empty($c['ipqs_is_vpn']))        $_frFlags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#ede9fe;color:#6d28d9">VPN</span>';
                    if (!empty($c['ipqs_is_proxy']))       $_frFlags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fef3c7;color:#b45309">PX</span>';
                    if (!empty($c['ipqs_is_tor']))         $_frFlags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fee2e2;color:#dc2626">TOR</span>';
                    if (!empty($c['ipqs_is_bot']))         $_frFlags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#fee2e2;color:#dc2626">BOT</span>';
                    if (!empty($c['ipqs_is_datacenter']))  $_frFlags[] = '<span style="font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:#f0f9ff;color:#0369a1">DC</span>';
                    if (!empty($_frFlags)):
                    ?>
                    <div style="display:flex;gap:3px;flex-wrap:wrap"><?= implode('', $_frFlags) ?></div>
                    <?php endif; ?>
                    <span style="font-size:9px;color:<?= $_frActionColor ?>;font-weight:600;text-transform:uppercase;letter-spacing:.04em"><?= htmlspecialchars($_frAction, ENT_QUOTES) ?></span>
                </div>
                <?php else: ?>
                <span style="font-size:10px;color:#64748b;background:#f1f5f9;border-radius:10px;padding:2px 8px">&#9203; No data</span>
                <?php endif; ?>
                </td>
                <td>$<?= number_format((float)$c['payout'], 2) ?></td>
                <td><?= !empty($c['country']) ? Helpers::flag($c['country']) . ' ' . Helpers::e($c['country']) : '—' ?></td>
                <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address'] ?: '—') ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($c['converted_at']) ?></td>
                <td style="white-space:nowrap">
                    <?php if ($c['status'] !== 'rejected'): ?>
                    <button type="button"
                            class="btn btn-danger btn-sm fr-reject-btn"
                            data-cid="<?= Helpers::e($c['conversion_id']) ?>"
                            data-aff="<?= Helpers::e($c['aff_name']) ?>"
                            data-offer="<?= Helpers::e($c['offer_name'] ?: '—') ?>"
                            data-payout="<?= number_format((float)$c['payout'], 2) ?>"
                            title="Reject this conversion as fraud">
                        &#10005; Reject as Fraud
                    </button>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:12px">&#10003; Rejected</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Reject fraud modal ─────────────────────────────────────────────────
     Posts to the same controller (FraudReportController) which validates:
       1. CSRF token
       2. role = affiliate_manager
       3. ManagerPermissions::requirePermission('reject_fraud_conv') — admin-gated
       4. Conversion belongs to one of this manager's own affiliates
     After save it writes an audit row to manager_fraud_rejections (admin-visible). -->
<div id="frRejectModal"
     style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center;padding:18px;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)">
    <div role="dialog" aria-modal="true" aria-labelledby="frRejectTitle"
         style="background:#fff;border-radius:14px;max-width:520px;width:100%;box-shadow:0 18px 50px -16px rgba(15,23,42,.45);overflow:hidden">
        <div style="padding:18px 22px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;gap:10px;background:linear-gradient(135deg,#FEF2F2,#FFE4E6)">
            <h3 id="frRejectTitle" style="margin:0;font-size:16px;font-weight:800;color:#991B1B;display:flex;align-items:center;gap:8px">
                <span style="font-size:20px">&#9888;&#65039;</span> Reject Fraud Conversion
            </h3>
            <button type="button" id="frRejectClose"
                    style="background:transparent;border:none;font-size:22px;line-height:1;color:#94A3B8;cursor:pointer;padding:0 4px">&times;</button>
        </div>
        <form method="POST" id="frRejectForm" style="padding:18px 22px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="reject_fraud">
            <input type="hidden" name="conversion_id" id="frRejectCid">

            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:13px">
                <div style="display:flex;justify-content:space-between;gap:10px"><span style="color:#64748B">Conversion</span><span style="font-family:monospace;font-size:12px" id="frRejectCidLabel">—</span></div>
                <div style="display:flex;justify-content:space-between;gap:10px;margin-top:6px"><span style="color:#64748B">Affiliate</span><strong id="frRejectAff">—</strong></div>
                <div style="display:flex;justify-content:space-between;gap:10px;margin-top:6px"><span style="color:#64748B">Offer</span><span id="frRejectOffer">—</span></div>
                <div style="display:flex;justify-content:space-between;gap:10px;margin-top:6px"><span style="color:#64748B">Payout (will be reversed if approved)</span><strong id="frRejectPayout">$0.00</strong></div>
            </div>

            <div class="form-group">
                <label style="font-weight:700;font-size:13px">Rejection reason <span style="color:#DC2626">*</span></label>
                <textarea name="reason" id="frRejectReason" class="form-control" rows="4" required maxlength="1000"
                          placeholder="e.g. Duplicate IP across 12 conversions in 30 min; flagged by IPQS at 92 risk score"></textarea>
                <div class="form-hint" style="margin-top:4px">Required. Stored in the admin-visible audit log along with your name and timestamp.</div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
                <button type="button" id="frRejectCancel" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="frRejectSubmit" class="btn btn-danger">&#10005; Reject as Fraud</button>
            </div>
        </form>
    </div>
</div>

<script>
$(function() {
    // Skip the new action column (index 10) from sort; sort by "Detected At".
    $('table').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [[9, 'desc']],
        columnDefs: [{ orderable: false, targets: 10 }],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});

// ── Reject-fraud modal wiring ────────────────────────────────────────────
(function(){
    var modal  = document.getElementById('frRejectModal');
    var form   = document.getElementById('frRejectForm');
    var cidIn  = document.getElementById('frRejectCid');
    var lblCid = document.getElementById('frRejectCidLabel');
    var lblAff = document.getElementById('frRejectAff');
    var lblOff = document.getElementById('frRejectOffer');
    var lblPay = document.getElementById('frRejectPayout');
    var reason = document.getElementById('frRejectReason');
    var submit = document.getElementById('frRejectSubmit');

    function open(btn){
        cidIn.value   = btn.dataset.cid;
        lblCid.textContent = (btn.dataset.cid || '').slice(0, 16) + '…';
        lblAff.textContent = btn.dataset.aff   || '—';
        lblOff.textContent = btn.dataset.offer || '—';
        lblPay.textContent = '$' + (btn.dataset.payout || '0.00');
        reason.value = '';
        submit.disabled = false; submit.textContent = '✕ Reject as Fraud';
        modal.style.display = 'flex';
        setTimeout(function(){ reason.focus(); }, 50);
    }
    function close(){ modal.style.display = 'none'; }

    document.querySelectorAll('.fr-reject-btn').forEach(function(b){
        b.addEventListener('click', function(){ open(b); });
    });
    document.getElementById('frRejectClose').addEventListener('click', close);
    document.getElementById('frRejectCancel').addEventListener('click', close);
    modal.addEventListener('click', function(e){ if (e.target === modal) close(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });

    // Disable button on submit so a double-click cannot create two
    // rejection rows (the controller is idempotent via status check, but
    // disabling the button also prevents confusing duplicate flashes).
    form.addEventListener('submit', function(){
        submit.disabled = true; submit.textContent = 'Submitting…';
    });
})();
</script>

<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
