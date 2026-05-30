<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128229; Manager Invoice Requests</h1>
        <p>Payout invoice requests submitted by affiliate managers</p>
    </div>
    <!-- Status filter -->
    <div style="display:flex;gap:6px">
        <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $s=>$lbl): ?>
        <a href="?status=<?= $s ?>"
           class="btn btn-sm <?= ($status === $s) ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $lbl ?>
            <?php if ($s === 'pending' && $pendingCount > 0): ?>
            <span style="background:#fff;color:#7C3AED;border-radius:10px;padding:0 6px;font-size:11px;margin-left:4px;font-weight:700"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:60px 24px;color:var(--text-muted)">
        <div style="font-size:48px;margin-bottom:14px">&#128196;</div>
        <div style="font-size:16px;font-weight:600;color:#1E293B;margin-bottom:6px">No invoice requests</div>
        <div style="font-size:13px">No <?= $status !== 'all' ? $status : '' ?> invoice requests from managers at this time.</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table id="tbl-inv-req">
            <thead>
                <tr>
                    <th>#</th>
                    <th>MANAGER</th>
                    <th>AFFILIATE ACCOUNT</th>
                    <th>AMOUNT</th>
                    <th>PERIOD</th>
                    <th>BALANCE</th>
                    <th>NOTES</th>
                    <th>SUBMITTED</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
            <?php
                $stMap = [
                    'pending'  => ['badge-warning', '&#9203; Pending'],
                    'approved' => ['badge-success', '&#10003; Approved'],
                    'rejected' => ['badge-danger',  '&#10005; Rejected'],
                ];
                [$badgeCls, $badgeTxt] = $stMap[$r['status']] ?? ['badge-muted', ucfirst($r['status'])];
            ?>
            <tr>
                <td class="text-muted text-sm"><?= $r['id'] ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e(trim($r['first_name'].' '.$r['last_name'])) ?></div>
                    <div class="text-muted text-sm"><?= Helpers::e($r['email']) ?></div>
                </td>
                <td>
                    <?php if ($r['affiliate_id'] && $r['aff_name']): ?>
                    <div class="fw-bold text-sm"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></div>
                    <?php if ($r['aff_email']): ?>
                    <div style="font-size:11px;color:#94A3B8"><?= Helpers::e($r['aff_email']) ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td class="fw-bold" style="font-size:15px;color:#7C3AED">$<?= number_format((float)$r['amount'], 2) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap">
                    <?= date('M j, Y', strtotime($r['period_start'])) ?><br>
                    <span style="color:#CBD5E1">→</span> <?= date('M j, Y', strtotime($r['period_end'])) ?>
                </td>
                <td class="text-sm">
                    <?php if ($r['affiliate_id']): ?>
                    <span style="color:#7C3AED;font-weight:600">$<?= number_format((float)$r['aff_balance'], 2) ?></span>
                    <div style="font-size:11px;color:#94A3B8">affiliate balance</div>
                    <?php else: ?>
                    <span style="color:#059669;font-weight:600">$<?= number_format((float)$r['mgr_balance'], 2) ?></span>
                    <div style="font-size:11px;color:#94A3B8">manager balance</div>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px;font-size:12px;color:#64748B">
                    <?= $r['notes'] ? Helpers::e(mb_strimwidth($r['notes'], 0, 80, '…')) : '<span style="color:#CBD5E1">—</span>' ?>
                </td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
                <td>
                    <span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span>
                    <?php if ($r['status'] !== 'pending' && $r['admin_note']): ?>
                    <div style="font-size:11px;color:#64748B;margin-top:3px;max-width:160px"><?= Helpers::e(mb_strimwidth($r['admin_note'], 0, 60, '…')) ?></div>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                    <?php if ($r['status'] === 'approved' && $r['invoice_id']): ?>
                    <a href="/admin/invoices/<?= (int)$r['invoice_id'] ?>" class="btn btn-secondary btn-sm">View Invoice</a>
                    <?php elseif ($r['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-primary btn-sm"
                            onclick="openApprove(
                                <?= $r['id'] ?>,
                                <?= Helpers::e(json_encode(trim($r['first_name'].' '.$r['last_name']))) ?>,
                                '<?= number_format((float)$r['amount'], 2) ?>',
                                '<?= Helpers::e($r['period_start']) ?>',
                                '<?= Helpers::e($r['period_end']) ?>',
                                '<?= number_format((float)$r['mgr_balance'], 2) ?>',
                                <?= Helpers::e(json_encode($r['affiliate_id'] ? trim($r['aff_name'] ?? '') . ' (' . ($r['affiliate_code'] ?? '') . ')' : '')) ?>,
                                '<?= $r['affiliate_id'] ? number_format((float)$r['aff_balance'], 2) : '' ?>'
                            )">
                        &#10003; Approve
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" style="margin-left:4px"
                            onclick="openReject(<?= $r['id'] ?>, <?= Helpers::e(json_encode(trim($r['first_name'].' '.$r['last_name']))) ?>)">
                        &#10005; Reject
                    </button>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Approve Modal ──────────────────────────────────────────────────────────── -->
<div id="approveModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:18px;backdrop-filter:blur(2px)">
    <div role="dialog" style="background:#fff;border-radius:14px;max-width:500px;width:100%;box-shadow:0 20px 60px -12px rgba(15,23,42,.4);overflow:hidden">
        <div style="padding:18px 22px;border-bottom:1px solid #E2E8F0;background:linear-gradient(135deg,#F0FDF4,#DCFCE7);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:16px;font-weight:800;color:#166534;display:flex;align-items:center;gap:8px">
                <span style="font-size:20px">&#10003;</span> Approve Invoice Request
            </h3>
            <button onclick="closeModal('approveModal')" style="background:none;border:none;font-size:22px;color:#94A3B8;cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="/admin/affiliate-managers/invoice-requests" style="padding:20px 22px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" id="approveId">

            <div id="approveSummary" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px"></div>

            <!-- Invoice type notice (shown when affiliate is set) -->
            <div id="approveAffiliateNotice" style="display:none;padding:10px 14px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;margin-bottom:16px;font-size:13px">
                <div style="color:#4338CA;font-weight:700;margin-bottom:4px">&#128100; Affiliate Payout Invoice</div>
                <div style="color:#1E293B">Affiliate: <strong id="approveAffiliateLabel"></strong></div>
                <div style="color:#4338CA;margin-top:4px;font-size:12px">&#9432; Approving will create an <strong>affiliate_payout</strong> invoice and deduct the amount from the <strong>affiliate's balance</strong>. No change to manager balance.</div>
            </div>

            <div class="form-group" style="margin-bottom:14px">
                <label style="font-weight:600;font-size:13px">Invoice Amount ($) <span style="color:#EF4444">*</span></label>
                <input type="number" name="amount" id="approveAmount" class="form-control" step="0.01" min="0.01" required>
                <div class="form-hint" id="approveBalanceHint"></div>
            </div>

            <div class="form-row cols-2" style="margin-bottom:14px">
                <div class="form-group" style="margin-bottom:0">
                    <label style="font-weight:600;font-size:13px">Period Start</label>
                    <input type="date" name="period_start" id="approvePeriodStart" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label style="font-weight:600;font-size:13px">Period End</label>
                    <input type="date" name="period_end" id="approvePeriodEnd" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label style="font-weight:600;font-size:13px">Due Date <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <input type="date" name="due_date" class="form-control">
            </div>

            <div class="form-group" style="margin-bottom:20px">
                <label style="font-weight:600;font-size:13px">Admin Note <span style="color:#94A3B8;font-weight:400">(optional — sent to manager)</span></label>
                <textarea name="admin_note" class="form-control" rows="3" placeholder="e.g. Approved — payment will be processed within 3 business days."></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#16A34A;border-color:#16A34A">&#10003; Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Reject Modal ───────────────────────────────────────────────────────────── -->
<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:18px;backdrop-filter:blur(2px)">
    <div role="dialog" style="background:#fff;border-radius:14px;max-width:440px;width:100%;box-shadow:0 20px 60px -12px rgba(15,23,42,.4);overflow:hidden">
        <div style="padding:18px 22px;border-bottom:1px solid #E2E8F0;background:linear-gradient(135deg,#FEF2F2,#FFE4E6);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;font-size:16px;font-weight:800;color:#991B1B;display:flex;align-items:center;gap:8px">
                <span style="font-size:20px">&#10005;</span> Reject Invoice Request
            </h3>
            <button onclick="closeModal('rejectModal')" style="background:none;border:none;font-size:22px;color:#94A3B8;cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="/admin/affiliate-managers/invoice-requests" style="padding:20px 22px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" id="rejectId">
            <p style="font-size:13px;color:#64748B;margin:0 0 14px">
                Rejecting request from <strong id="rejectName"></strong>. The manager will see your note.
            </p>
            <div class="form-group" style="margin-bottom:20px">
                <label style="font-weight:600;font-size:13px">Reason <span style="color:#DC2626">*</span></label>
                <textarea name="admin_note" class="form-control" rows="4" required
                          placeholder="e.g. Balance not yet confirmed. Please re-submit after month end."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">&#10005; Reject Request</button>
            </div>
        </form>
    </div>
</div>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function(){
    var $t = $('#tbl-inv-req');
    if ($t.find('tbody tr').length > 1) {
        $t.DataTable({destroy:true, pageLength:25, order:[[0,'desc']], language:{search:'Search:'}});
    }
});

function openApprove(id, name, amount, periodStart, periodEnd, balance, affiliateLabel, affBalance) {
    document.getElementById('approveId').value          = id;
    document.getElementById('approveAmount').value      = amount;
    document.getElementById('approvePeriodStart').value = periodStart;
    document.getElementById('approvePeriodEnd').value   = periodEnd;
    document.getElementById('approveSummary').innerHTML =
        '<div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="color:#64748B">Manager</span><strong>' + escHtml(name) + '</strong></div>' +
        '<div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="color:#64748B">Requested</span><strong style="color:#7C3AED">$' + amount + '</strong></div>' +
        '<div style="display:flex;justify-content:space-between"><span style="color:#64748B">Period</span><span>' + periodStart + ' – ' + periodEnd + '</span></div>';

    // Show affiliate invoice notice when affiliate is linked
    var noticeEl  = document.getElementById('approveAffiliateNotice');
    var labelEl   = document.getElementById('approveAffiliateLabel');
    var hintEl    = document.getElementById('approveBalanceHint');
    if (affiliateLabel && affBalance !== '') {
        labelEl.textContent    = affiliateLabel;
        noticeEl.style.display = 'block';
        hintEl.textContent     = 'Affiliate available balance: $' + affBalance;
    } else {
        noticeEl.style.display = 'none';
        hintEl.textContent     = 'Manager available balance: $' + balance;
    }

    document.getElementById('approveModal').style.display = 'flex';
}
function openReject(id, name) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeModal('approveModal'); closeModal('rejectModal'); } });
[document.getElementById('approveModal'), document.getElementById('rejectModal')].forEach(function(m){
    m.addEventListener('click', function(e){ if (e.target === m) closeModal(m.id); });
});
function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
