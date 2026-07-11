<?php
if (!defined('BASE_PATH')) exit;
require BASE_PATH . '/views/layouts/public_top.php';
?>

<style>
.offers-banner {
    padding: 80px 0 60px;
    background: linear-gradient(135deg, var(--bg-card) 0%, #ffffff 100%);
    text-align: center;
    border-bottom: 1px solid var(--border);
}
.offers-banner h1 {
    font-size: 42px;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 16px;
    letter-spacing: -0.5px;
}
.offers-banner p {
    font-size: 18px;
    color: var(--text-light);
    max-width: 800px;
    margin: 0 auto;
}
.offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 48px;
}
.offer-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.offer-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.08);
    border-color: var(--violet);
}
.offer-card-img {
    height: 160px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid var(--border);
}
.offer-card-img img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
.offer-card-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.offer-cat {
    font-size: 12px;
    font-weight: 600;
    color: var(--violet);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.offer-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.offer-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
.offer-payout {
    font-size: 16px;
    font-weight: 700;
    color: #10b981;
    background: #d1fae5;
    padding: 4px 10px;
    border-radius: 6px;
}
.offer-geo {
    font-size: 13px;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: 4px;
}
.offer-action {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px dashed var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.offer-action a {
    color: var(--violet);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}
.offer-action a:hover {
    text-decoration: underline;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 64px;
}
.pagination a, .pagination span {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
}
.pagination a {
    background: #fff;
    border: 1px solid var(--border);
    color: var(--text);
    transition: all 0.2s;
}
.pagination a:hover {
    border-color: var(--violet);
    color: var(--violet);
}
.pagination span.active {
    background: var(--violet);
    color: #fff;
    border: 1px solid var(--violet);
}
.seo-text-block {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    margin: 64px auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    border: 1px solid var(--border);
}
.seo-text-block h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 24px;
    color: var(--text);
}
.seo-text-block p {
    font-size: 16px;
    line-height: 1.8;
    color: var(--text-light);
    margin-bottom: 24px;
}
.faq-block {
    margin-top: 48px;
}
.faq-item {
    margin-bottom: 24px;
}
.faq-item h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 12px;
}
.faq-item p {
    font-size: 15px;
    line-height: 1.7;
    color: var(--text-light);
    margin: 0;
}
</style>

<div class="offers-banner">
    <div class="container">
        <h1><?= Helpers::e($meta['h1']) ?></h1>
        <p><?= Helpers::e($meta['desc']) ?></p>
    </div>
</div>

<section style="background: #fafafa; min-height: 500px; padding: 64px 0;">
    <div class="container">

        <?php if (empty($offers)): ?>
        <div style="text-align:center; padding: 64px 0;">
            <svg style="width:64px;height:64px;color:#d1d5db;margin:0 auto 16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
            <h3 style="font-size:20px;color:var(--text);margin-bottom:8px">No offers found</h3>
            <p style="color:var(--text-light)">We currently do not have any active public offers in this category.</p>
        </div>
        <?php else: ?>
        <div class="offers-grid">
            <?php foreach ($offers as $o): 
                $oslug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
                $oslug = rtrim($oslug, '-');
                $url = '/offers/' . $o['id'] . '-' . $oslug;
                
                $img = $o['thumbnail'] ? $o['thumbnail'] : '/logoo.png';
                
                $sym = ($o['currency'] === 'EUR') ? '€' : (($o['currency'] === 'GBP') ? '£' : '$');
                if ($o['payout_type'] === 'RevShare') {
                    $payoutStr = number_format($o['payout_amount'], 2) . '% RevShare';
                } else {
                    $payoutStr = $sym . number_format($o['payout_amount'], 2) . ' ' . $o['payout_type'];
                }

                $geoStr = $o['geo_targeting'] ?? '';
                if ($geoStr === '' || $geoStr === 'Global') {
                    $geoDisplay = 'Global';
                } elseif (strpos($geoStr, '[') === 0) {
                    $arr = json_decode($geoStr, true);
                    $geoDisplay = is_array($arr) ? implode(', ', array_slice($arr, 0, 3)) . (count($arr)>3?' +'.(count($arr)-3):'') : 'Various';
                } else {
                    $arr = explode(',', $geoStr);
                    $geoDisplay = implode(', ', array_slice($arr, 0, 3)) . (count($arr)>3?' +'.(count($arr)-3):'');
                }
            ?>
            <div class="offer-card">
                <div class="offer-card-img">
                    <img src="<?= Helpers::e($img) ?>" alt="<?= Helpers::e($o['name']) ?> - Affiliate Offer" loading="lazy">
                </div>
                <div class="offer-card-body">
                    <div class="offer-cat"><?= Helpers::e($o['category']) ?></div>
                    <h3 class="offer-title"><?= Helpers::e($o['name']) ?></h3>
                    
                    <div class="offer-meta">
                        <div class="offer-payout"><?= Helpers::e($payoutStr) ?></div>
                        <div class="offer-geo">
                            <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"/></svg>
                            <?= Helpers::e($geoDisplay) ?>
                        </div>
                    </div>
                    
                    <div class="offer-action">
                        <a href="<?= $url ?>">View Details <svg style="width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo;</a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): 
            ?>
                <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="seo-text-block">
            <h2>About <?= Helpers::e($dbCategory) ?> Affiliate Marketing</h2>
            <p><?= Helpers::e($meta['text']) ?></p>
            
            <div class="faq-block">
                <h2>Frequently Asked Questions</h2>
                <?php foreach ($meta['faqs'] as $q => $a): ?>
                <div class="faq-item">
                    <h3><?= Helpers::e($q) ?></h3>
                    <p><?= Helpers::e($a) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>
</section>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
