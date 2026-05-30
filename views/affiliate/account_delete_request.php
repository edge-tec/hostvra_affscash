<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128465; Request Account Deletion</h1>
        <p style="margin-top:4px;color:var(--text-muted)">Submit a request to permanently close your affiliate account</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:20px">
    <?php foreach ($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px">
    &#10003; Your account deletion request has been submitted successfully. Our team will review it and get back to you.
</div>
<?php endif; ?>

<!-- Warning Banner -->
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:16px 20px;margin-bottom:24px;display:flex;gap:14px;align-items:flex-start">
    <div style="font-size:24px;line-height:1">&#9888;</div>
    <div>
        <div style="font-weight:700;color:#B91C1C;margin-bottom:6px">This action is irreversible</div>
        <ul style="margin:0;padding-left:18px;color:#7F1D1D;font-size:14px;line-height:1.7">
            <li>Your account and all associated data will be permanently deleted</li>
            <li>Any pending balances must be settled before deletion is approved</li>
            <li>Active offers and postbacks will be deactivated immediately upon approval</li>
            <li>You will no longer be able to log in or recover your account</li>
        </ul>
    </div>
</div>

<?php if ($existing && $existing['status'] === 'pending'): ?>
<!-- Pending request notice -->
<div class="card" style="max-width:640px;border:2px solid #FCD34D">
    <div class="card-header" style="background:#FFFBEB">
        <span class="card-title" style="color:#92400E">&#128336; Pending Deletion Request</span>
    </div>
    <div class="card-body">
        <p style="margin-bottom:12px;color:#78350F">You already have a pending account deletion request. An admin will review it soon.</p>
        <table style="width:100%;font-size:13px">
            <tr>
                <td style="color:var(--text-muted);padding:6px 0;width:140px">Submitted</td>
                <td><?= Helpers::e(date('M j, Y g:i A', strtotime($existing['requested_at']))) ?></td>
            </tr>
            <tr>
                <td style="color:var(--text-muted);padding:6px 0">Status</td>
                <td><span class="badge badge-warning">Pending Review</span></td>
            </tr>
            <tr>
                <td style="color:var(--text-muted);padding:6px 0;vertical-align:top">Your Reason</td>
                <td style="white-space:pre-wrap"><?= Helpers::e($existing['reason']) ?></td>
            </tr>
        </table>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #E2E8F0">
            <p style="font-size:13px;color:var(--text-muted)">If you wish to cancel this request, please contact your manager or support.</p>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Submission Form -->
<div class="card" style="max-width:640px">
    <div class="card-header">
        <span class="card-title">Account Deletion Request</span>
    </div>
    <div class="card-body">
        <form method="POST" onsubmit="return confirmDelete()">
            <?= Helpers::csrf() ?>

            <div class="form-group">
                <label for="reason">Reason for Deletion <span style="color:#EF4444">*</span></label>
                <textarea name="reason" id="reason" class="form-control" rows="5" required minlength="10"
                    placeholder="Please explain why you want to delete your account. This helps us improve our service."
                    style="resize:vertical"><?= Helpers::e(Helpers::postRaw('reason') ?? '') ?></textarea>
                <div class="form-hint">Minimum 10 characters. Be as specific as possible.</div>
            </div>

            <div class="form-group" style="margin-top:8px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
                    <input type="checkbox" id="confirmCheck" required style="margin-top:3px;flex-shrink:0">
                    <span style="font-size:14px;color:#374151">
                        I understand that deleting my account is <strong>permanent and irreversible</strong>.
                        All my data, earnings history, and access will be permanently removed upon approval.
                    </span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px">
                <button type="submit" class="btn btn-danger" id="submitBtn">
                    &#128465; Submit Deletion Request
                </button>
                <a href="/affiliate/profile" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Request History -->
<?php if (!empty($history)): ?>
<div class="card" style="max-width:640px;margin-top:24px">
    <div class="card-header"><span class="card-title">Request History</span></div>
    <div class="card-body" style="padding:0">
        <table class="table" style="margin:0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Admin Note</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $r): ?>
            <tr>
                <td style="font-size:13px;white-space:nowrap"><?= Helpers::e(date('M j, Y', strtotime($r['requested_at']))) ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php elseif ($r['status'] === 'approved'): ?>
                        <span class="badge badge-success">Approved</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Rejected</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:13px;color:var(--text-muted)">
                    <?= $r['admin_note'] ? Helpers::e($r['admin_note']) : '—' ?>
                    <?php if ($r['reviewed_at']): ?>
                        <div style="font-size:11px;color:#94A3B8;margin-top:2px">
                            Reviewed <?= Helpers::e(date('M j, Y', strtotime($r['reviewed_at']))) ?>
                            <?php if ($r['first_name']): ?>by <?= Helpers::e($r['first_name'] . ' ' . $r['last_name']) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDelete() {
    var check = document.getElementById('confirmCheck');
    if (!check.checked) {
        alert('Please confirm that you understand the deletion is permanent.');
        return false;
    }
    return confirm('Are you absolutely sure you want to request account deletion? This cannot be undone once approved.');
}
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
