<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <h1>&#128465; Account Deletion Requests</h1>
        <p style="color:var(--text-muted);margin-top:4px">Review and action affiliate account deletion requests</p>
    </div>
</div>

<!-- Stat cards -->
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <?php
    $stats = [
        ['label'=>'Pending',  'val'=> (int)($counts['pending']  ?? 0), 'color'=>'#F59E0B','bg'=>'#FFFBEB'],
        ['label'=>'Approved', 'val'=> (int)($counts['approved'] ?? 0), 'color'=>'#10B981','bg'=>'#ECFDF5'],
        ['label'=>'Rejected', 'val'=> (int)($counts['rejected'] ?? 0), 'color'=>'#EF4444','bg'=>'#FEF2F2'],
        ['label'=>'Total',    'val'=> (int)($counts['total']    ?? 0), 'color'=>'#6366F1','bg'=>'#EEF2FF'],
    ];
    foreach ($stats as $s): ?>
    <div style="background:<?= $s['bg'] ?>;border:1px solid <?= $s['color'] ?>33;border-radius:10px;padding:16px 24px;min-width:130px;flex:1">
        <div style="font-size:26px;font-weight:700;color:<?= $s['color'] ?>"><?= $s['val'] ?></div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:2px"><?= $s['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0;flex-wrap:wrap">
    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $k=>$lbl): ?>
    <a href="/admin/account-delete-requests?status=<?= $k ?>"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;
              border-bottom:2px solid <?= $filterStatus===$k?'#10B981':'transparent' ?>;
              margin-bottom:-2px;
              color:<?= $filterStatus===$k?'#10B981':'#64748B' ?>">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px 24px">
        <div style="font-size:48px;margin-bottom:12px">&#128465;</div>
        <div style="font-weight:600;font-size:16px;margin-bottom:6px">No <?= $filterStatus !== 'all' ? $filterStatus : '' ?> requests</div>
        <div style="color:var(--text-muted);font-size:14px">There are no account deletion requests to show here.</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body" style="padding:0;overflow-x:auto">
        <table class="table" style="margin:0;min-width:700px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Affiliate</th>
                    <th>Reason</th>
                    <th>Balance</th>
                    <th>Requested</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td style="font-size:13px;color:var(--text-muted)"><?= (int)$r['id'] ?></td>
                <td>
                    <div style="font-weight:600;font-size:14px"><?= Helpers::e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($r['email']) ?></div>
                    <div style="font-size:12px;color:#6366F1"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td style="max-width:260px">
                    <div style="font-size:13px;line-height:1.5;white-space:pre-wrap;max-height:80px;overflow:hidden;text-overflow:ellipsis"
                         title="<?= Helpers::e($r['reason']) ?>">
                        <?= Helpers::e(mb_substr($r['reason'], 0, 160)) ?><?= mb_strlen($r['reason']) > 160 ? '…' : '' ?>
                    </div>
                </td>
                <td style="font-size:14px;font-weight:600;<?= ($r['balance'] > 0 ? 'color:#EF4444' : 'color:#6B7280') ?>">
                    $<?= number_format((float)$r['balance'], 2) ?>
                    <?php if ($r['balance'] > 0): ?>
                    <div style="font-size:11px;color:#EF4444;font-weight:400">Unsettled balance</div>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;white-space:nowrap"><?= Helpers::e(date('M j, Y', strtotime($r['requested_at']))) ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php elseif ($r['status'] === 'approved'): ?>
                        <span class="badge badge-success">Approved</span>
                        <?php if ($r['reviewed_by_name']): ?><div style="font-size:11px;color:#94A3B8;margin-top:2px">by <?= Helpers::e($r['reviewed_by_name']) ?></div><?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-danger">Rejected</span>
                        <?php if ($r['reviewed_by_name']): ?><div style="font-size:11px;color:#94A3B8;margin-top:2px">by <?= Helpers::e($r['reviewed_by_name']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <div style="display:flex;gap:8px;align-items:center">
                        <button type="button" class="btn btn-success" style="padding:6px 12px;font-size:12px"
                            onclick="openReviewModal(<?= (int)$r['id'] ?>, '<?= Helpers::e(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?>', 'approve')">
                            &#10003; Approve
                        </button>
                        <button type="button" class="btn btn-danger" style="padding:6px 12px;font-size:12px"
                            onclick="openReviewModal(<?= (int)$r['id'] ?>, '<?= Helpers::e(addslashes($r['first_name'] . ' ' . $r['last_name'])) ?>', 'reject')">
                            &#10007; Reject
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="font-size:13px;color:var(--text-muted)">
                        <?= $r['admin_note'] ? Helpers::e(mb_substr($r['admin_note'], 0, 80)) : '—' ?>
                        <?php if ($r['reviewed_at']): ?>
                        <div style="font-size:11px;color:#94A3B8"><?= Helpers::e(date('M j, Y', strtotime($r['reviewed_at']))) ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="/admin/affiliates/<?= (int)$r['affiliate_id'] ?>" style="font-size:12px;color:#6366F1">View Affiliate &#8594;</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Review Modal -->
<div id="review-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:28px;max-width:480px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <h3 id="modal-title" style="margin-bottom:16px;font-size:18px"></h3>
        <form method="POST" id="review-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="request_id" id="modal-request-id">
            <input type="hidden" name="decision"   id="modal-decision">

            <div class="form-group" style="margin-bottom:20px">
                <label for="admin_note">Admin Note <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
                <textarea name="admin_note" id="admin_note" class="form-control" rows="3"
                    placeholder="Add a note for the affiliate (e.g. reason for rejection or confirmation message)"></textarea>
            </div>

            <div id="approve-warning" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#B91C1C">
                &#9888; <strong>Warning:</strong> This will permanently soft-delete the affiliate account and block all their offers. This cannot be undone.
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">Cancel</button>
                <button type="submit" class="btn" id="modal-submit-btn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(id, name, decision) {
    document.getElementById('modal-request-id').value = id;
    document.getElementById('modal-decision').value   = decision;
    document.getElementById('admin_note').value       = '';

    var isApprove = decision === 'approve';
    document.getElementById('modal-title').textContent =
        isApprove ? '✓ Approve Deletion: ' + name : '✗ Reject Request: ' + name;

    var btn = document.getElementById('modal-submit-btn');
    btn.textContent  = isApprove ? 'Yes, Delete Account' : 'Reject Request';
    btn.className    = 'btn ' + (isApprove ? 'btn-danger' : 'btn-primary');

    document.getElementById('approve-warning').style.display = isApprove ? 'block' : 'none';
    document.getElementById('review-modal').style.display    = 'flex';
}

function closeReviewModal() {
    document.getElementById('review-modal').style.display = 'none';
}

document.getElementById('review-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
