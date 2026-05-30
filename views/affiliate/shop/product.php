<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.pd-hero{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}
.pd-img-wrap{border-radius:14px;overflow:hidden;border:1px solid #E5E7EB;background:#F8FAFC;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;font-size:64px}
.pd-img-wrap img{width:100%;height:100%;object-fit:cover}
.pd-badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:4px 10px;border-radius:20px;margin-bottom:14px}
.pd-badge.instock{background:#DCFCE7;color:#166534}
.pd-badge.outofstock{background:#FEE2E2;color:#991B1B}
.pd-badge.lowstock{background:#FEF9C3;color:#854D0E}
.pd-name{font-size:24px;font-weight:800;color:#0F172A;line-height:1.25;margin-bottom:10px}
.pd-price-block{display:flex;align-items:baseline;gap:8px;margin-bottom:18px}
.pd-price{font-size:32px;font-weight:900;color:#4F46E5}
.pd-price-label{font-size:14px;color:#64748B;font-weight:500}
.pd-desc-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94A3B8;margin-bottom:8px}
.pd-desc{font-size:14px;color:#334155;line-height:1.75;white-space:pre-wrap;word-break:break-word}
.pd-balance-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:10px 0;border-bottom:1px solid #F1F5F9}
.pd-balance-row:last-child{border-bottom:none}
@media(max-width:680px){
    .pd-hero{grid-template-columns:1fr}
}
</style>

<div class="page-header">
    <div>
        <h1><?= Helpers::e($product['name']) ?></h1>
        <p>Product details &amp; redemption</p>
    </div>
    <a href="/affiliate/shop" class="btn btn-secondary">← Back to Shop</a>
</div>

<div class="pd-hero">
    <!-- Left: Product image -->
    <div class="pd-img-wrap">
        <?php if (!empty($product['image_path'])): ?>
        <img src="<?= Helpers::e($product['image_path']) ?>" alt="<?= Helpers::e($product['name']) ?>">
        <?php else: ?>🎁<?php endif; ?>
    </div>

    <!-- Right: Details + actions -->
    <div>
        <!-- Stock badge -->
        <?php
            $stock     = (int)$product['stock'];
            $outOfStock = $stock === 0;
            $lowStock   = $stock > 0 && $stock <= 5;
            $unlimited  = $stock === -1;
        ?>
        <?php if ($outOfStock): ?>
            <span class="pd-badge outofstock">✕ Out of Stock</span>
        <?php elseif ($unlimited): ?>
            <span class="pd-badge instock">✓ In Stock</span>
        <?php elseif ($lowStock): ?>
            <span class="pd-badge lowstock">⚠ Only <?= $stock ?> left</span>
        <?php else: ?>
            <span class="pd-badge instock">✓ In Stock (<?= $stock ?> available)</span>
        <?php endif; ?>

        <div class="pd-name"><?= Helpers::e($product['name']) ?></div>

        <div class="pd-price-block">
            <span class="pd-price"><?= number_format((int)$product['price_points']) ?></span>
            <span class="pd-price-label">points</span>
        </div>

        <!-- Points breakdown card -->
        <div class="card" style="margin-bottom:18px">
            <div class="card-body" style="padding:14px 18px">
                <div class="pd-balance-row">
                    <span class="text-muted">Your balance</span>
                    <strong><?= number_format((int)$balance['balance']) ?> pts</strong>
                </div>
                <div class="pd-balance-row">
                    <span class="text-muted">Cost</span>
                    <strong style="color:#DC2626">− <?= number_format((int)$product['price_points']) ?> pts</strong>
                </div>
                <?php $after = (int)$balance['balance'] - (int)$product['price_points']; ?>
                <div class="pd-balance-row" style="font-weight:700;font-size:14px">
                    <span>After redemption</span>
                    <span style="color:<?= $after >= 0 ? '#059669' : '#DC2626' ?>"><?= number_format($after) ?> pts</span>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <?php $canAfford = (int)$balance['balance'] >= (int)$product['price_points']; ?>
        <?php if ($outOfStock): ?>
            <button class="btn btn-secondary" style="width:100%" disabled>Out of Stock</button>
        <?php elseif (!$canAfford): ?>
            <button class="btn btn-secondary" style="width:100%" disabled>
                Need <?= number_format((int)$product['price_points'] - (int)$balance['balance']) ?> more pts to redeem
            </button>
            <div class="form-hint text-center" style="margin-top:8px">
                Keep earning points to unlock this reward.
            </div>
        <?php else: ?>
            <a href="/affiliate/shop?action=checkout&product_id=<?= (int)$product['id'] ?>"
               class="btn btn-primary" style="width:100%;text-align:center;font-size:15px">
                Redeem Now →
            </a>
            <div class="form-hint text-center" style="margin-top:8px">
                You'll confirm your shipping details on the next step.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Full description -->
<?php if (!empty(trim((string)($product['description'] ?? '')))): ?>
<div class="card" style="margin-top:24px">
    <div class="card-header"><span class="card-title">Product Description</span></div>
    <div class="card-body">
        <div class="pd-desc"><?= preg_replace('/\s(on\w+|href\s*=\s*["\']?\s*javascript:)[^\s>]*/i', '', strip_tags((string)$product['description'], '<p><br><strong><em><b><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><blockquote><pre><code><img>')) ?></div>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
