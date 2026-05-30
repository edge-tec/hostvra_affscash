<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Affiliate Shop</h1><p>Products affiliates can redeem with points. Orders use a dedicated table — no impact on existing invoices.</p></div>
    <div style="display:flex;gap:8px">
        <a href="/admin/shop?action=orders" class="btn btn-secondary">View Orders</a>
        <a href="/admin/shop?action=create" class="btn btn-primary">+ New Product</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><span class="card-title">Products</span></div>
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th></th><th>Name</th><th class="text-right">Price (pts)</th><th class="text-right">Stock</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td style="width:64px">
                    <?php if (!empty($p['image_path'])): ?>
                        <img src="<?= Helpers::e($p['image_path']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #E2E8F0">
                    <?php else: ?>
                        <div style="width:48px;height:48px;border-radius:6px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:18px">🎁</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:600"><?= Helpers::e($p['name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e(mb_strimwidth((string)($p['description'] ?? ''), 0, 70, '…')) ?></div>
                </td>
                <td class="text-right" style="font-weight:700;color:#4F46E5"><?= number_format((int)$p['price_points']) ?></td>
                <td class="text-right"><?= (int)$p['stock'] === -1 ? '∞' : (int)$p['stock'] ?></td>
                <td>
                    <?php $b = ['active'=>'success','draft'=>'warning','archived'=>'muted'][$p['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($p['status']) ?></span>
                </td>
                <td style="white-space:nowrap">
                    <a href="/admin/shop?action=edit&id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product? Existing orders are unaffected.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="submit_type" value="delete_product">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No products yet. Create your first one to launch the shop.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">Recent Orders</span>
        <a href="/admin/shop?action=orders" class="btn btn-secondary btn-sm">View all →</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th>#</th><th>Product</th><th>Affiliate</th><th class="text-right">Points</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= Helpers::e($o['product_name']) ?></td>
                <td><?= Helpers::e($o['aff_name'] ?: ('#' . $o['affiliate_id'])) ?></td>
                <td class="text-right"><?= number_format((int)$o['price_points']) ?></td>
                <td>
                    <?php $b = ['pending'=>'warning','approved'=>'info','shipped'=>'info','delivered'=>'success','cancelled'=>'danger'][$o['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($o['status']) ?></span>
                </td>
                <td class="text-muted text-sm"><?= Helpers::e(date('M j H:i', strtotime((string)$o['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No orders yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
