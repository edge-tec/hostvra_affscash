<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Shop Orders</h1><p>Manage redemption orders. Cancelling a paid order automatically refunds the points.</p></div>
    <a href="/admin/shop" class="btn btn-secondary">← Products</a>
</div>

<div class="card mb-3">
    <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <span class="text-muted text-sm">Filter:</span>
        <?php $cur = Helpers::get('status') ?: ''; foreach (['', 'pending','approved','shipped','delivered','cancelled'] as $s): ?>
        <a href="/admin/shop?action=orders<?= $s ? '&status=' . $s : '' ?>"
           class="btn btn-sm <?= $cur === $s ? 'btn-primary' : 'btn-secondary' ?>"><?= $s === '' ? 'All' : ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Product</th><th>Affiliate</th><th class="text-right">Points</th>
                    <th>Status</th><th>Address</th><th>Tracking</th><th>Date</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= Helpers::e($o['product_name']) ?></td>
                <td>
                    <?= Helpers::e($o['aff_name'] ?: ('#' . $o['affiliate_id'])) ?>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e((string)$o['aff_email']) ?></div>
                </td>
                <td class="text-right"><?= number_format((int)$o['price_points']) ?></td>
                <td>
                    <?php $b = ['pending'=>'warning','approved'=>'info','shipped'=>'info','delivered'=>'success','cancelled'=>'danger'][$o['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($o['status']) ?></span>
                </td>
                <td style="max-width:280px">
                    <div style="font-weight:600;font-size:12.5px"><?= Helpers::e($o['recipient_name'] ?: '—') ?></div>
                    <div class="text-muted" style="font-size:11px;white-space:normal"><?= nl2br(Helpers::e($o['address'] ?: '')) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($o['phone'] ?: '') ?> · <?= Helpers::e($o['email'] ?: '') ?></div>
                </td>
                <td class="text-muted" style="font-size:12px"><?= Helpers::e($o['tracking_code'] ?: '—') ?></td>
                <td class="text-muted text-sm"><?= Helpers::e(date('M j H:i', strtotime((string)$o['created_at']))) ?></td>
                <td>
                    <details>
                        <summary class="btn btn-secondary btn-sm" style="cursor:pointer">Update</summary>
                        <form method="POST" style="margin-top:8px;background:#F8FAFC;padding:10px;border-radius:6px;min-width:240px">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="update_order">
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <select name="status" class="form-control" style="margin-bottom:6px">
                                <?php foreach (['pending','approved','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="tracking_code" class="form-control" placeholder="Tracking code (optional)" value="<?= Helpers::e($o['tracking_code'] ?? '') ?>" style="margin-bottom:6px">
                            <textarea name="admin_note" class="form-control" rows="2" placeholder="Admin note (optional)" style="margin-bottom:6px"><?= Helpers::e($o['admin_note'] ?? '') ?></textarea>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </form>
                    </details>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:32px">No orders match this filter.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
