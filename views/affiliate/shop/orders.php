<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div><h1>My Orders</h1><p>Status of every reward you've redeemed.</p></div>
    <a href="/affiliate/shop" class="btn btn-secondary">← Back to Shop</a>
</div>

<div class="card">
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th>#</th><th>Product</th><th class="text-right">Points</th><th>Status</th><th>Tracking</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= Helpers::e($o['product_name']) ?></td>
                <td class="text-right" style="font-weight:700;color:#4F46E5"><?= number_format((int)$o['price_points']) ?></td>
                <td>
                    <?php $b = ['pending'=>'warning','approved'=>'info','shipped'=>'info','delivered'=>'success','cancelled'=>'danger'][$o['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($o['status']) ?></span>
                </td>
                <td class="text-muted text-sm"><?= Helpers::e($o['tracking_code'] ?: '—') ?></td>
                <td class="text-muted text-sm"><?= Helpers::e(date('M j, Y H:i', strtotime((string)$o['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:40px">No orders yet. <a href="/affiliate/shop">Browse the shop →</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
