<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div><h1>Checkout</h1><p>Confirm your shipping details to complete the redemption.</p></div>
    <a href="/affiliate/shop" class="btn btn-secondary">← Back to Shop</a>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Order Summary</span></div>
            <div class="card-body">
                <div style="display:flex;gap:14px;align-items:flex-start">
                    <?php if (!empty($product['image_path'])): ?>
                    <img src="<?= Helpers::e($product['image_path']) ?>" alt="" style="width:96px;height:96px;border-radius:8px;border:1px solid #E2E8F0;object-fit:cover">
                    <?php else: ?>
                    <div style="width:96px;height:96px;border-radius:8px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:30px">🎁</div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:700;font-size:16px"><?= Helpers::e($product['name']) ?></div>
                        <div class="text-muted text-sm" style="margin-top:4px"><?= Helpers::e(mb_strimwidth(trim(strip_tags((string)($product['description'] ?? ''))), 0, 180, '…')) ?></div>
                        <div style="margin-top:10px;font-weight:800;color:#4F46E5;font-size:18px"><?= number_format((int)$product['price_points']) ?> pts</div>
                    </div>
                </div>
                <hr style="margin:18px 0;border:none;border-top:1px solid #E2E8F0">
                <div style="display:flex;justify-content:space-between;font-size:13px">
                    <span class="text-muted">Your balance</span>
                    <strong><?= number_format((int)$balance['balance']) ?> pts</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:6px">
                    <span class="text-muted">Price</span>
                    <strong style="color:#DC2626">− <?= number_format((int)$product['price_points']) ?> pts</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;margin-top:6px;padding-top:8px;border-top:1px dashed #E2E8F0">
                    <span>After redemption</span>
                    <span style="color:#059669"><?= number_format((int)$balance['balance'] - (int)$product['price_points']) ?> pts</span>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">Shipping Details</span></div>
            <div class="card-body">
                <form method="POST" id="checkoutForm">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type"    value="place_order">
                    <input type="hidden" name="product_id"     value="<?= (int)$product['id'] ?>">
                    <input type="hidden" name="idempotency_key" value="<?= Helpers::e($idempotencyKey) ?>">

                    <div class="form-group">
                        <label>Recipient name *</label>
                        <input type="text" name="recipient_name" class="form-control" required maxlength="200">
                    </div>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" maxlength="50"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" maxlength="200"></div>
                    </div>
                    <div class="form-group">
                        <label>Address *</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. preferred delivery time"></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit" id="placeBtn" style="width:100%">Confirm &amp; Redeem</button>
                    <div class="form-hint text-center" style="margin-top:8px">Once confirmed, points are deducted immediately. The order cannot be modified.</div>
                </form>
                <script>
                document.getElementById('checkoutForm').addEventListener('submit', function(){
                    var b = document.getElementById('placeBtn');
                    b.disabled = true; b.textContent = 'Submitting…';
                });
                </script>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
