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

$geoStr = $offer['geo_targeting'] ?? '';
if ($geoStr === '' || $geoStr === 'Global') {
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
    background: linear-gradient(135deg, #0f0826 0%, #05020c 100%);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.breadcrumb {
    font-size: 14px;
    color: rgba(255,255,255,0.5);
    margin-bottom: 24px;
}
.breadcrumb a {
    color: #7c3aed;
    text-decoration: none;
    font-weight: 500;
}
.breadcrumb a:hover {
    color: #0ea5e9;
}
.breadcrumb span {
    margin: 0 8px;
    color: rgba(255,255,255,0.3);
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
    background: rgba(15,10,36,0.6);
    border: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}
.offer-icon img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.offer-icon img[src*="logoo.png"],
.offer-icon img[src*="logo"] {
    max-width: 80%;
    max-height: 80%;
    filter: brightness(0) invert(1);
    opacity: 0.95;
}
.offer-header-info {
    flex: 1;
}
.offer-cat {
    display: inline-block;
    font-size: 13px;
    font-weight: 600;
    color: #7c3aed;
    background: rgba(124, 58, 237, 0.15);
    padding: 4px 12px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}
.offer-header-info h1 {
    font-size: 32px;
    font-weight: 800;
    color: #fff;
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
    color: rgba(255,255,255,0.6);
}
.stat-item strong {
    color: #fff;
}
.offer-main-content {
    padding: 64px 0;
    background: linear-gradient(135deg, #0a0518 0%, #05020c 100%);
}
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 48px;
}
.offer-description {
    font-size: 16px;
    line-height: 1.7;
    color: rgba(255,255,255,0.8);
}
.offer-description h2, .offer-description h3 {
    margin: 32px 0 16px;
    color: #fff;
}
.offer-description p {
    margin-bottom: 16px;
}
.sidebar-box {
    background: rgba(15, 10, 36, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 24px;
    position: sticky;
    top: 100px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transform: perspective(1000px) rotateX(1deg);
    transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.sidebar-box:hover {
    
    border-color: rgba(124, 58, 237, 0.3);
}
.sidebar-box h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #fff;
}
.sidebar-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
}
.sidebar-list li {
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: space-between;
    font-size: 15px;
    color: rgba(255,255,255,0.8);
}
.sidebar-list li:last-child {
    border-bottom: none;
}
.sidebar-list li span:first-child {
    color: rgba(255, 255, 255, 0.5);
}
.sidebar-list li span:last-child {
    font-weight: 600;
    color: #fff;
    text-align: right;
}
.cta-button {
    display: block;
    width: 100%;
    text-align: center;
    background: linear-gradient(135deg, #7c3aed 0%, #e8197a 100%);
    color: #fff;
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}
.cta-button:hover {
    
    box-shadow: 0 5px 15px rgba(232, 25, 122, 0.4);
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
                    <p style="text-align:center; font-size:13px; color:rgba(255,255,255,0.5); margin-top:16px;">Already have an account? <a href="/login" style="color:#7c3aed">Log in</a></p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
