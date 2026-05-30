<?php
/**
 * Affiliate → Shop (browse + buy + my orders).
 * Uses ShopService::placeOrder for atomic checkout; never touches existing
 * invoices / orders / payouts.
 */
Auth::check('affiliate');
$pageTitle = 'Rewards Shop';

$affId   = (int)Auth::affiliateId();
$balance = PointsService::balance($affId);

$action = Helpers::get('action') ?: 'index';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $sub = Helpers::post('submit_type');

    if ($sub === 'place_order') {
        $productId      = (int)Helpers::postRaw('product_id');
        $idempotencyKey = trim((string)Helpers::postRaw('idempotency_key')) ?: bin2hex(random_bytes(16));
        $address = [
            'name'    => Helpers::postRaw('recipient_name'),
            'phone'   => Helpers::postRaw('phone'),
            'email'   => Helpers::postRaw('email'),
            'address' => Helpers::postRaw('address'),
            'notes'   => Helpers::postRaw('notes'),
        ];
        $r = ShopService::placeOrder($affId, $productId, $address, $idempotencyKey);
        if (!empty($r['ok'])) {
            $msg = !empty($r['duplicate']) ? 'Order already submitted.' : 'Order submitted! Points have been deducted.';
            Helpers::flash('success', $msg);
            Helpers::redirect('/affiliate/shop?action=orders');
        }
        Helpers::flash('error', $r['reason'] ?? 'Order failed.');
        Helpers::redirect('/affiliate/shop?action=checkout&product_id=' . $productId);
    }
}

if ($action === 'product' && isset($_GET['product_id'])) {
    $product = ShopService::getProduct((int)$_GET['product_id']);
    if (!$product || $product['status'] !== 'active') { Helpers::redirect('/affiliate/shop'); }
    require BASE_PATH . '/views/affiliate/shop/product.php';
    return;
}

if ($action === 'checkout' && isset($_GET['product_id'])) {
    $product = ShopService::getProduct((int)$_GET['product_id']);
    if (!$product || $product['status'] !== 'active') { Helpers::redirect('/affiliate/shop'); }
    if ((int)$product['stock'] === 0)                  { Helpers::redirect('/affiliate/shop'); }
    if ($balance['balance'] < (int)$product['price_points']) {
        Helpers::flash('error', sprintf('Not enough points. You have %d, this product costs %d.', $balance['balance'], (int)$product['price_points']));
        Helpers::redirect('/affiliate/shop');
    }
    $idempotencyKey = bin2hex(random_bytes(16));
    require BASE_PATH . '/views/affiliate/shop/checkout.php';
    return;
}

if ($action === 'orders') {
    $orders = ShopService::listOrders($affId, null, 200);
    require BASE_PATH . '/views/affiliate/shop/orders.php';
    return;
}

$products = ShopService::listProducts('active');
require BASE_PATH . '/views/affiliate/shop/index.php';
