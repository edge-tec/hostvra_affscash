<?php
if (!defined('BASE_PATH')) exit;
require BASE_PATH . '/views/layouts/public_top.php';

$img = $offer['thumbnail'] ? $offer['thumbnail'] : '/logoo.png';
$sym = ($offer['currency'] === 'EUR') ? '€' : (($offer['currency'] === 'GBP') ? '£' : '$');
if ($offer['payout_type'] === 'RevShare') {
    $payoutStr = number_format($offer['payout_amount'], 2) . '% RevShare';
} else {
    $payoutStr = $sym . number_format($offer['payout_amount'], 2) . ' ' . $offer['payout_type'];
}

$geoStr = $offer['geo_targeting'];
if ($geoStr === 'Global') {
    $geoDisplay = 'Global (All Countries)';
} elseif (strpos($geoStr, '[') === 0) {
    $arr = json_decode($geoStr, true);
    $geoDisplay = is_array($arr) ? implode(', ', $arr) : 'Various';
} else {
    $arr = explode(',', $geoStr);
    $geoDisplay = implode(', ', $arr);
}
?>

<style>
.offer-detail-banner {
    padding: 60px 0 40px;
    background: #f8fafc;
    border-bottom: 1px solid var(--border);
}
.breadcrumb {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 24px;
}
.breadcrumb a {
    color: var(--violet);
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb a:hover {
    text-decoration: underline;
}
.breadcrumb span {
    margin: 0 8px;
    color: #9ca3af;
}
.offer-header {
    display: flex;
    gap: 32px;
    align-items: flex-start;
}
.offer-icon {
    width: 120px;
    height: 120px;
    border-radius: 20px;
    background: #fff;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.offer-icon img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.offer-header-info {
    flex: 1;
}
.offer-cat {
    display: inline-block;
    font-size: 13px;
    font-weight: 600;
    color: var(--violet);
    background: rgba(124, 58, 237, 0.1);
    padding: 4px 12px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}
.offer-header-info h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 16px;
    line-height: 1.2;
}
.offer-quick-stats {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}
.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    color: var(--text-light);
}
.stat-item strong {
    color: var(--text);
}
.offer-main-content {
    padding: 64px 0;
    background: #fff;
}
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 48px;
}
.offer-description {
    font-size: 16px;
    line-height: 1.7;
    color: var(--text);
}
.offer-description h2, .offer-description h3 {
    margin: 32px 0 16px;
    color: var(--text);
}
.offer-description p {
    margin-bottom: 16px;
}
.sidebar-box {
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    position: sticky;
    top: 100px;
}
.sidebar-box h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    color: var(--text);
}
.sidebar-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
}
.sidebar-list li {
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    font-size: 15px;
}
.sidebar-list li:last-child {
    border-bottom: none;
}
.sidebar-list li span:first-child {
    color: var(--text-light);
}
.sidebar-list li span:last-child {
    font-weight: 600;
    color: var(--text);
    text-align: right;
}
.cta-button {
    display: block;
    width: 100%;
    text-align: center;
    background: var(--violet);
    color: #fff;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
}
.cta-button:hover {
    background: var(--violet-dark, #6d28d9);
}
@media (max-width: 768px) {
    .offer-header {
        flex-direction: column;
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="offer-detail-banner">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span>/</span> <a href="/offers">Offers</a> <span>/</span> <?= Helpers::e($offer['name']) ?>
        </div>
        
        <div class="offer-header">
            <div class="offer-icon">
                <img src="<?= Helpers::e($img) ?>" alt="<?= Helpers::e($offer['name']) ?>">
            </div>
            <div class="offer-header-info">
                <?php if ($offer['category']): ?>
                <div class="offer-cat"><?= Helpers::e($offer['category']) ?></div>
                <?php endif; ?>
                <h1><?= Helpers::e($offer['name']) ?></h1>
                
                <div class="offer-quick-stats">
                    <div class="stat-item">
                        <svg style="width:18px;height:18px;color:#10b981" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01"/><path d="M17 12h.01"/><path d="M7 12h.01"/></svg>
                        <strong>Payout:</strong> <span style="color:#10b981;font-weight:700"><?= Helpers::e($payoutStr) ?></span>
                    </div>
                    <div class="stat-item">
                        <svg style="width:18px;height:18px;color:var(--violet)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"/></svg>
                        <strong>GEO:</strong> <?= Helpers::e($geoDisplay) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="offer-main-content">
    <div class="container">
        <div class="content-grid">
            
            <div class="offer-description">
                <h2>About This Offer</h2>
                <?php if ($offer['description']): ?>
                    <?= nl2br(Helpers::e($offer['description'])) ?>
                <?php else: ?>
                    <p>No detailed description provided for this offer yet.</p>
                <?php endif; ?>
                
                <h3 style="margin-top:40px">Why Promote This?</h3>
                <ul style="padding-left:20px; line-height: 1.8;">
                    <li>High converting landing pages optimized for maximum ROI.</li>
                    <li>Exclusive payouts for our top affiliates.</li>
                    <li>Fast and reliable tracking.</li>
                    <li>On-time payments.</li>
                </ul>
            </div>
            
            <div>
                <div class="sidebar-box">
                    <h3>Offer Summary</h3>
                    <ul class="sidebar-list">
                        <li>
                            <span>Offer ID</span>
                            <span>#<?= $offer['id'] ?></span>
                        </li>
                        <li>
                            <span>Payout Type</span>
                            <span><?= Helpers::e($offer['payout_type']) ?></span>
                        </li>
                        <li>
                            <span>Payout Amount</span>
                            <span style="color:#10b981"><?= Helpers::e($payoutStr) ?></span>
                        </li>
                        <li>
                            <span>Category</span>
                            <span><?= Helpers::e($offer['category'] ?: 'N/A') ?></span>
                        </li>
                        <li>
                            <span>Supported Devices</span>
                            <span><?= Helpers::e($offer['device_targeting'] ?: 'All Devices') ?></span>
                        </li>
                        <li>
                            <span>Supported OS</span>
                            <span><?= Helpers::e($offer['os_targeting'] ?: 'All OS') ?></span>
                        </li>
                    </ul>
                    
                    <a href="/register" class="cta-button">Sign up to promote this</a>
                    <p style="text-align:center; font-size:13px; color:var(--text-light); margin-top:16px;">Already have an account? <a href="/login" style="color:var(--violet)">Log in</a></p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
