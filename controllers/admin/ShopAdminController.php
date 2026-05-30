<?php
/**
 * Admin → Shop module: product CRUD + order viewer.
 */
Auth::check('admin');
$pageTitle = 'Affiliate Shop';

$action = Helpers::get('action') ?: 'index';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $sub = Helpers::post('submit_type');

    if ($sub === 'save_product') {
        $id = (int)Helpers::postRaw('id');
        // Image upload
        $imgPath = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $allowed = ['image/png','image/jpeg','image/gif','image/webp'];
            $mime = @mime_content_type($_FILES['image']['tmp_name']);
            if (in_array($mime, $allowed, true) && (int)$_FILES['image']['size'] <= 4 * 1024 * 1024) {
                $dir = BASE_PATH . '/assets/uploads/shop/';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg');
                $safe = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-z0-9]/','',$ext);
                if (@move_uploaded_file($_FILES['image']['tmp_name'], $dir . $safe)) {
                    $imgPath = '/assets/uploads/shop/' . $safe;
                }
            }
        }
        $data = [
            'name'         => trim((string)Helpers::postRaw('name')),
            'description'  => trim((string)Helpers::postRaw('description')),
            'price_points' => (int)Helpers::postRaw('price_points'),
            'stock'        => Helpers::postRaw('stock'),
            'status'       => Helpers::post('status') ?: 'active',
        ];
        if ($imgPath) $data['image_path'] = $imgPath;
        if ($data['name'] === '') {
            Helpers::flash('error', 'Product name is required.');
        } else {
            $newId = ShopService::saveProduct($data, $id ?: null);
            // Email all active affiliates when this is a brand-new product
            // (an update should not re-blast everyone). Failures are logged
            // per-recipient and never break this form submission.
            if (!$id && $newId > 0) {
                try {
                    require_once BASE_PATH . '/core/AffiliateBroadcastNotifier.php';
                    $product = ShopService::getProduct($newId);
                    if ($product) {
                        AffiliateBroadcastNotifier::notifyShopProductAdded($product);
                    }
                } catch (\Throwable $_e) {
                    error_log('[ShopAdminController] notify failed: ' . $_e->getMessage());
                }
            }
            Helpers::flash('success', $id ? 'Product updated.' : 'Product created.');
        }
        Helpers::redirect('/admin/shop');
    }

    if ($sub === 'delete_product') {
        $id = (int)Helpers::postRaw('id');
        if ($id > 0) {
            ShopService::deleteProduct($id);
            Helpers::flash('success', 'Product deleted.');
        }
        Helpers::redirect('/admin/shop');
    }

    if ($sub === 'update_order') {
        $oid    = (int)Helpers::postRaw('order_id');
        $stat   = (string)Helpers::post('status');
        $track  = trim((string)Helpers::postRaw('tracking_code'));
        $note   = trim((string)Helpers::postRaw('admin_note'));
        if ($oid > 0) {
            ShopService::updateOrderStatus($oid, $stat, $track ?: null, $note ?: null);
            Helpers::flash('success', 'Order updated.');
        }
        Helpers::redirect('/admin/shop?action=orders');
    }
}

if ($action === 'orders') {
    $filterStatus = Helpers::get('status') ?: null;
    $orders = ShopService::listOrders(null, $filterStatus, 500);
    require BASE_PATH . '/views/admin/shop/orders.php';
    return;
}

if ($action === 'edit' && isset($_GET['id'])) {
    $product = ShopService::getProduct((int)$_GET['id']);
    if (!$product) { Helpers::redirect('/admin/shop'); }
    require BASE_PATH . '/views/admin/shop/edit.php';
    return;
}

if ($action === 'create') {
    $product = null;
    require BASE_PATH . '/views/admin/shop/edit.php';
    return;
}

// Index — list all products + recent orders summary
$products = ShopService::listProducts('all');
$recentOrders = ShopService::listOrders(null, null, 10);
require BASE_PATH . '/views/admin/shop/index.php';
