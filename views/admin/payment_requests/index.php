<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>💳 Payment Requests <?php if ($pendingCount > 0): ?><span style="background:#FEF3C7;color:#92400E;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:700;margin-left:8px"><?= $pendingCount ?> pending</span><?php endif; ?></h1>
        <p>Manual top-up payments from advertisers awaiting verification.</p>
    </div>
</div>

<!-- Status filter tabs -->
<div class="card mb-3">
    <div class="card-body" style="padding:10px 16px">
        <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $k => $label): ?>
        <a href="/admin/payment-requests?status=<?= $k ?>" style="display:inline-block;padding:6px 14px;border-radius:6px;margin-right:6px;font-size:13px;font-weight:600;text-decoration:none;<?= $status === $k ? 'background:#4F46E5;color:#fff' : 'color:#475569' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table style="margin:0">
            <thead>
                <tr>
                    <th>Date</th><th>Advertiser</th><th>Method</th><th>Amount</th>
                    <th>Transaction ID</th><th>Proof</th><th>Status</th><th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--text-muted)">No payment requests match this filter.</td></tr>
                <?php else: foreach ($requests as $r):
                    $badge = $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'muted' : 'warning');
                ?>
                <tr>
                    <td class="text-sm"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
                    <td>
                        <div class="fw-bold"><?= Helpers::e(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ($r['company'] ?? '—')) ?></div>
                        <div class="text-sm text-muted"><?= Helpers::e($r['email']) ?></div>
                        <div style="font-family:monospace;font-size:10px;color:#94A3B8"><?= Helpers::e($r['advertiser_code']) ?> &middot; Balance: $<?= number_format((float)$r['adv_balance'], 2) ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= ucfirst(Helpers::e($r['method'])) ?></span><?php if (!empty($r['crypto_type'])): ?> <span class="badge" style="background:#EEF2FF;color:#4F46E5;font-size:10px"><?= strtoupper(Helpers::e($r['crypto_type'])) ?></span><?php endif; ?></td>
                    <td class="fw-bold">$<?= number_format((float)$r['amount'], 2) ?></td>
                    <td class="text-sm" style="font-family:monospace;word-break:break-all;max-width:200px"><?= Helpers::e($r['txn_id']) ?></td>
                    <td>
                        <?php if (!empty($r['screenshot_path'])): ?>
                        <a href="/admin/payment-requests?action=download&id=<?= (int)$r['id'] ?>" target="_blank" style="font-size:12px;color:#4F46E5;font-weight:600;text-decoration:none">📎 View</a>
                        <?php else: ?>
                        <span style="color:#94A3B8;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst(Helpers::e($r['status'])) ?></span></td>
                    <td style="text-align:right">
                        <?php if ($r['status'] === 'pending'): ?>
                        <button class="btn btn-success btn-sm" onclick="pmAct(<?= (int)$r['id'] ?>, 'approve')">✓ Approve</button>
                        <button class="btn btn-danger btn-sm"  onclick="pmAct(<?= (int)$r['id'] ?>, 'reject')">✕ Reject</button>
                        <?php else: ?>
                        <span class="text-sm text-muted">
                            <?= $r['reviewed_at'] ? date('M j, g:i A', strtotime($r['reviewed_at'])) : '—' ?>
                        </span>
                        <?php if (!empty($r['admin_note'])): ?>
                        <div style="font-size:11px;color:#64748B;margin-top:2px;text-align:right;max-width:200px"><?= Helpers::e($r['admin_note']) ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hidden form posted by the approve/reject buttons. -->
<form method="POST" id="pm-form" style="display:none">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="action" id="pm-action">
    <input type="hidden" name="id"     id="pm-id">
    <input type="hidden" name="admin_note" id="pm-note">
</form>

<script>
function pmAct(id, verb){
    var prompt1 = verb === 'approve'
        ? 'Approve this top-up and credit the advertiser? You can add an optional note:'
        : 'Reject this top-up? Please provide a reason (shown to the advertiser):';
    var note = window.prompt(prompt1, '');
    if (note === null) return; // cancelled
    if (verb === 'reject' && note.trim() === '') {
        if (!confirm('Reject without a note?')) return;
    }
    document.getElementById('pm-id').value     = id;
    document.getElementById('pm-action').value = verb;
    document.getElementById('pm-note').value   = note;
    document.getElementById('pm-form').submit();
}
</script>
