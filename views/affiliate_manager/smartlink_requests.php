<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>Smartlink Access Requests</h1>
        <p>Approve or reject smartlink access requests from your managed affiliates</p>
    </div>
</div>

<!-- Status Filter Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <?php foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $val=>$label):
        $cnt = ($val === 'pending') ? ' (' . (int)($pendingCount['cnt'] ?? 0) . ')' : '';
    ?>
    <a href="/affiliate_manager/smartlinks?status=<?= $val ?>"
       style="padding:10px 18px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;text-decoration:none;color:<?= $statusFilter===$val?'#7C3AED':'#64748B' ?>;border-bottom:<?= $statusFilter===$val?'2px solid #7C3AED':'2px solid transparent' ?>;margin-bottom:-2px">
        <?= $label . $cnt ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body text-center text-muted" style="padding:48px">
        <div style="font-size:40px;margin-bottom:12px">&#128279;</div>
        <div style="font-weight:600;margin-bottom:6px">No <?= $statusFilter !== 'all' ? $statusFilter : '' ?> requests</div>
        <div style="font-size:13px">Smartlink access requests from your affiliates will appear here.</div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Smartlink</th>
                    <th>Requested</th>
                    <th>Note</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r):
                $badgeMap = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                $badge = $badgeMap[$r['status']] ?? 'muted';
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['smartlink_name']) ?></div>
                    <div class="text-sm text-muted">/smartlink/<?= Helpers::e($r['slug']) ?></div>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
                <td class="text-sm"><?= $r['affiliate_note'] ? Helpers::e($r['affiliate_note']) : '<span class="text-muted">—</span>' ?></td>
                <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="openReview(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['aff_name'])) ?>', '<?= htmlspecialchars(addslashes($r['smartlink_name'])) ?>', 'approved')">&#10003; Approve</button>
                    <button type="button" class="btn btn-danger btn-sm mt-1" onclick="openReview(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['aff_name'])) ?>', '<?= htmlspecialchars(addslashes($r['smartlink_name'])) ?>', 'rejected')">&#10005; Reject</button>
                    <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openReview(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['aff_name'])) ?>', '<?= htmlspecialchars(addslashes($r['smartlink_name'])) ?>', '<?= $r['status']==='approved'?'rejected':'approved' ?>')">
                        <?= $r['status']==='approved' ? '&#128683; Block' : '&#10003; Re-approve' ?>
                    </button>
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
<div id="review-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:460px;width:90%">
        <h3 id="modal-title" style="margin-bottom:8px"></h3>
        <p id="modal-desc" style="color:#64748B;font-size:13px;margin-bottom:20px"></p>
        <form method="POST" action="/affiliate_manager/smartlinks?action=review_request">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="request_id" id="modal-req-id">
            <input type="hidden" name="decision" id="modal-decision">
            <div class="form-group">
                <label>Note to Affiliate <span class="text-muted" style="font-weight:400">(optional)</span></label>
                <textarea name="admin_note" class="form-control" rows="3" placeholder="Reason for your decision…"></textarea>
            </div>
            <div class="d-flex gap-2 mt-2">
                <button type="submit" id="modal-submit-btn" class="btn btn-primary">Confirm</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('review-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReview(reqId, affName, slName, decision) {
    document.getElementById('modal-req-id').value   = reqId;
    document.getElementById('modal-decision').value = decision;
    document.getElementById('modal-title').textContent = (decision==='approved'?'✓ Approve':'✗ Reject') + ' Request';
    document.getElementById('modal-desc').textContent  = affName + ' — "' + slName + '"';
    var btn = document.getElementById('modal-submit-btn');
    btn.className = 'btn ' + (decision==='approved'?'btn-success':'btn-danger');
    btn.textContent = decision==='approved' ? 'Grant Access' : 'Reject';
    document.getElementById('review-modal').style.display = 'flex';
}
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
