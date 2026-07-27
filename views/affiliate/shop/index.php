<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.sh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;}
.sh-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;transition:transform .15s,box-shadow .15s;display:flex;flex-direction:column}
.sh-card:hover{box-shadow:0 12px 28px -16px rgba(15,23,42,.18);}
.sh-img{aspect-ratio:4/3;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:32px;color:#94A3B8}
.sh-img img{width:100%;height:100%;object-fit:cover}
.sh-body{padding:14px 16px;display:flex;flex-direction:column;flex:1}
.sh-title{font-weight:700;font-size:14px;color:#0F172A;margin-bottom:4px}
.sh-desc{font-size:12px;color:#64748B;line-height:1.5;margin-bottom:12px;flex:1}
.sh-meta{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #F1F5F9;padding-top:10px;margin-top:auto}
.sh-price{font-weight:800;color:#4F46E5;font-size:16px}
.sh-stock{font-size:11px;color:#94A3B8}
.sh-actions{padding:0 16px 14px}
.sh-balance-card{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border-radius:12px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.sh-balance-amt{font-size:26px;font-weight:800}
.desc-text.line-clamp-3{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.read-more-btn{font-size:11px;color:#4F46E5;background:none;border:none;padding:0;cursor:pointer;font-weight:600;margin-top:4px;}
.read-more-btn:hover{text-decoration:underline;}
</style>

<div class="page-header">
    <div><h1>Rewards Shop</h1><p>Redeem your earned points for prizes, vouchers, and gifts.</p></div>
    <a href="/affiliate/shop?action=orders" class="btn btn-secondary">My Orders</a>
</div>

<div class="sh-balance-card">
    <div>
        <div style="font-size:11px;letter-spacing:.05em;text-transform:uppercase;opacity:.85">Your Points Balance</div>
        <div class="sh-balance-amt"><?= number_format((int)$balance['balance']) ?> <span style="font-size:13px;font-weight:600;opacity:.85">pts</span></div>
    </div>
    <div style="font-size:11.5px;opacity:.85;text-align:right">
        Lifetime earned <strong><?= number_format((int)$balance['lifetime_earned']) ?></strong><br>
        Lifetime spent <strong><?= number_format((int)$balance['lifetime_spent']) ?></strong>
    </div>
</div>

<?php if (empty($products)): ?>
<div class="card"><div class="card-body text-center text-muted" style="padding:48px">No products available right now. Check back soon!</div></div>
<?php else: ?>
<div class="sh-grid">
    <?php foreach ($products as $p):
        $canAfford = (int)$balance['balance'] >= (int)$p['price_points'];
        $outOfStock = (int)$p['stock'] === 0;
    ?>
    <div class="sh-card">
        <a href="/affiliate/shop?action=product&product_id=<?= (int)$p['id'] ?>" class="sh-img" style="text-decoration:none;cursor:pointer" title="View details">
            <?php if (!empty($p['image_path'])): ?>
            <img src="<?= Helpers::e($p['image_path']) ?>" alt="<?= Helpers::e($p['name']) ?>">
            <?php else: ?>🎁<?php endif; ?>
        </a>
        <div class="sh-body">
            <a href="/affiliate/shop?action=product&product_id=<?= (int)$p['id'] ?>" class="sh-title" style="text-decoration:none;color:inherit"><?= Helpers::e($p['name']) ?></a>
            <div class="sh-desc">
                <?php $descText = trim(strip_tags((string)($p['description'] ?? ''))); ?>
                <div class="desc-text <?= strlen($descText) > 100 ? 'line-clamp-3' : '' ?>"><?= Helpers::e($descText) ?></div>
                <?php if (strlen($descText) > 100): ?>
                <button type="button" class="read-more-btn" onclick="toggleDesc(this)">Read More</button>
                <?php endif; ?>
            </div>
            <div class="sh-meta">
                <span class="sh-price"><?= number_format((int)$p['price_points']) ?> pts</span>
                <span class="sh-stock"><?= (int)$p['stock'] === -1 ? 'In stock' : ((int)$p['stock'] . ' left') ?></span>
            </div>
        </div>
        <div class="sh-actions" style="display:flex;gap:8px">
            <a href="/affiliate/shop?action=product&product_id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm" style="flex:0 0 auto">Details</a>
            <?php if ($outOfStock): ?>
            <button class="btn btn-secondary btn-sm" style="flex:1" disabled>Out of stock</button>
            <?php elseif (!$canAfford): ?>
            <button class="btn btn-secondary btn-sm" style="flex:1" disabled>Need <?= number_format((int)$p['price_points'] - (int)$balance['balance']) ?> more pts</button>
            <?php else: ?>
            <a href="/affiliate/shop?action=checkout&product_id=<?= (int)$p['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;text-align:center">Redeem →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>

<script>
function toggleDesc(btn) {
    const textDiv = btn.previousElementSibling;
    if (textDiv.classList.contains('line-clamp-3')) {
        textDiv.classList.remove('line-clamp-3');
        btn.textContent = 'Read Less';
    } else {
        textDiv.classList.add('line-clamp-3');
        btn.textContent = 'Read More';
    }
}
</script>
