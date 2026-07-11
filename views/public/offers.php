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
    max-width: 600px;
    margin: 0 auto;
}
.filter-bar {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin: -32px auto 48px;
    max-width: 1000px;
    position: relative;
    z-index: 10;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: center;
}
.filter-bar input, .filter-bar select {
    flex: 1;
    min-width: 200px;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 15px;
    color: var(--text);
    background: var(--bg-body);
    transition: all 0.2s;
}
.filter-bar input:focus, .filter-bar select:focus {
    border-color: var(--violet);
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
}
.filter-bar button {
    background: var(--violet);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.filter-bar button:hover {
    background: var(--violet-dark, #6d28d9);
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
</style>

<div class="offers-banner">
    <div class="container">
        <h1>Explore Our Latest CPA Offers</h1>
        <p>Discover high-converting offers across top verticals. Join our network today to access exclusive payouts and dedicated support.</p>
    </div>
</div>

<section style="background: #fafafa; min-height: 500px; padding-bottom: 64px;">
    <div class="container">
        <form method="GET" action="/offers" class="filter-bar">
            <input type="text" name="q" placeholder="Search offers..." value="<?= Helpers::e($search) ?>">
            
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= Helpers::e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= Helpers::e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="geo">
                <option value="">All Countries</option>
                <option value="Global" <?= $geo === 'Global' ? 'selected' : '' ?>>Global</option>
                <?php foreach ($availableGeos as $g): ?>
                <option value="<?= Helpers::e($g) ?>" <?= $geo === $g ? 'selected' : '' ?>><?= Helpers::e($g) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Filter Offers</button>
        </form>

        <?php if (empty($offers)): ?>
        <div style="text-align:center; padding: 64px 0;">
            <svg style="width:64px;height:64px;color:#d1d5db;margin:0 auto 16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
            <h3 style="font-size:20px;color:var(--text);margin-bottom:8px">No offers found</h3>
            <p style="color:var(--text-light)">Try adjusting your search or filters to find what you're looking for.</p>
            <a href="/offers" style="display:inline-block;margin-top:16px;color:var(--violet);font-weight:600;text-decoration:none">Clear all filters</a>
        </div>
        <?php else: ?>
        <div class="offers-grid">
            <?php foreach ($offers as $o): 
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
                $slug = rtrim($slug, '-');
                $url = '/offers/' . $o['id'] . '-' . $slug;
                
                $img = $o['thumbnail'] ? $o['thumbnail'] : '/logoo.png';
                
                // Format payout
                $sym = ($o['currency'] === 'EUR') ? '€' : (($o['currency'] === 'GBP') ? '£' : '$');
                if ($o['payout_type'] === 'RevShare') {
                    $payoutStr = number_format($o['payout_amount'], 2) . '% RevShare';
                } else {
                    $payoutStr = $sym . number_format($o['payout_amount'], 2) . ' ' . $o['payout_type'];
                }

                // Format GEO
                $geoStr = $o['geo_targeting'];
                if ($geoStr === 'Global') {
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
                    <img src="<?= Helpers::e($img) ?>" alt="<?= Helpers::e($o['name']) ?>" loading="lazy">
                </div>
                <div class="offer-card-body">
                    <?php if ($o['category']): ?>
                    <div class="offer-cat"><?= Helpers::e($o['category']) ?></div>
                    <?php endif; ?>
                    
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
            <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&geo=<?= urlencode($geo) ?>">&laquo;</a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): 
            ?>
                <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&geo=<?= urlencode($geo) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&geo=<?= urlencode($geo) ?>">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . '/views/layouts/public_bottom.php'; ?>
