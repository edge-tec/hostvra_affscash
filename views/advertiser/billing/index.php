<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>Billing</h1>
        <p>Manage your account balance and top-up requests</p>
    </div>
    <a href="/advertiser/billing/top-up" class="btn btn-primary">+ Top Up Balance</a>
</div>

<!-- Balance card -->
<div class="card mb-3" style="background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;border:none">
    <div class="card-body" style="padding:22px 24px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px">
            <div>
                <div style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;opacity:.8">Available Balance</div>
                <div style="font-size:32px;font-weight:800;margin-top:4px;letter-spacing:-.5px">$<?= number_format((float)($adv['balance'] ?? 0), 2) ?></div>
            </div>
            <a href="/advertiser/billing/top-up" class="btn" style="background:#fff;color:#0F766E;font-weight:700">Top Up Now &rarr;</a>
        </div>
    </div>
</div>

<!-- Top-up history -->
<div class="card">
    <div class="card-header"><span class="card-title">Top-Up Requests</span></div>
    <div class="table-wrap">
        <table style="margin:0">
            <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Transaction ID</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr><td colspan="6"><div class="empty-state"><div class="icon">&#128181;</div><h3>No top-up requests yet</h3><p>Submit a top-up to add funds to your balance.</p></div></td></tr>
                <?php else: foreach ($requests as $r):
                    $badge = $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'muted' : 'warning');
                ?>
                <tr>
                    <td class="text-sm"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(Helpers::e($r['method'])) ?></span><?php if (!empty($r['crypto_type'])): ?> <span class="badge" style="background:#EEF2FF;color:#4F46E5;font-size:10px"><?= strtoupper(Helpers::e($r['crypto_type'])) ?></span><?php endif; ?></td>
                    <td class="fw-bold">$<?= number_format((float)$r['amount'], 2) ?></td>
                    <td class="text-sm" style="font-family:monospace"><?= Helpers::e($r['txn_id']) ?></td>
                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst(Helpers::e($r['status'])) ?></span></td>
                    <td class="text-sm text-muted"><?= Helpers::e($r['admin_note'] ?? '') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
