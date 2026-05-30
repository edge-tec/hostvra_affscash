<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
        <h1><?= Helpers::e($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?></h1>
        <p style="color:var(--text-muted);font-size:13px">Code: <strong><?= Helpers::e($affiliate['affiliate_code']) ?></strong> &bull; <?= Helpers::e($affiliate['email']) ?></p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/affiliate_manager/affiliates?action=edit&id=<?= $affiliate['aff_id'] ?>" class="btn btn-primary btn-sm">✎ Edit Profile</a>
        <a href="/affiliate_manager/affiliates" class="btn btn-secondary btn-sm">← Back</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-label">Total Clicks</div>
        <div class="stat-value"><?= number_format($stats['clicks'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Conversions</div>
        <div class="stat-value"><?= number_format($stats['conv'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Approved</div>
        <div class="stat-value" style="color:var(--secondary)"><?= number_format($stats['approved'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Payout</div>
        <div class="stat-value">$<?= number_format($stats['payout'] ?? 0, 2) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <!-- Profile -->
    <div class="card">
        <div class="card-header"><span class="card-title">Profile Information</span></div>
        <div class="card-body">
            <table style="width:100%">
                <?php $fields = [
                    'email'             => 'Email',
                    'company'           => 'Company',
                    'phone'             => 'Phone',
                    'country'           => 'Country',
                    'payment_method'    => 'Payment Method',
                    'payment_threshold' => 'Payment Threshold',
                    'fraud_score'       => 'Fraud Score',
                ]; ?>
                <?php foreach ($fields as $key => $label): ?>
                <tr>
                    <td style="padding:7px 0;color:var(--text-muted);font-size:13px;width:45%"><?= $label ?></td>
                    <td style="padding:7px 0;font-size:13px"><?= Helpers::e((string)($affiliate[$key] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Account Status -->
    <div class="card">
        <div class="card-header"><span class="card-title">Account Status</span></div>
        <div class="card-body">
            <?php $badgeMap = ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'muted']; ?>
            <div style="margin-bottom:16px">
                <span class="badge badge-<?= $badgeMap[$affiliate['status']] ?? 'muted' ?>" style="font-size:14px;padding:6px 14px">
                    <?= ucfirst($affiliate['status']) ?>
                </span>
            </div>
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-muted)">
                Balance: <strong style="color:var(--text)">$<?= number_format($affiliate['balance'], 2) ?></strong>
            </div>
            <?php if (Auth::hasPermission('approve_affiliates') && in_array($affiliate['status'], ['pending','active','suspended'])): ?>
            <div style="margin-top:16px">
                <?php if ($affiliate['status'] === 'pending'): ?>
                <form method="POST" action="/affiliate_manager/affiliates" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="aff_id" value="<?= $affiliate['aff_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button class="btn btn-success btn-sm">✓ Approve</button>
                </form>
                <form method="POST" action="/affiliate_manager/affiliates" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="aff_id" value="<?= $affiliate['aff_id'] ?>">
                    <input type="hidden" name="status" value="rejected">
                    <button class="btn btn-danger btn-sm">✕ Reject</button>
                </form>
                <?php elseif ($affiliate['status'] === 'active'): ?>
                <form method="POST" action="/affiliate_manager/affiliates" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="aff_id" value="<?= $affiliate['aff_id'] ?>">
                    <input type="hidden" name="status" value="suspended">
                    <button class="btn btn-warning btn-sm">Suspend</button>
                </form>
                <?php elseif ($affiliate['status'] === 'suspended'): ?>
                <form method="POST" action="/affiliate_manager/affiliates" style="display:inline">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="aff_id" value="<?= $affiliate['aff_id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <button class="btn btn-success btn-sm">Re-activate</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
