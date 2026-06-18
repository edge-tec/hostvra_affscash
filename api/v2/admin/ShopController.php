<?php
header('Content-Type: application/json');

try {
    Auth::check('admin');
    $action = $_GET['action'] ?? 'list';

    require_once BASE_PATH . '/core/ShopService.php';

    if ($action === 'list') {
        $products = ShopService::listProducts('all');
        $orders = ShopService::listOrders(null, null, 10);

        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products,
                'recent_orders' => $orders
            ]
        ]);
        exit;
    }

    if ($action === 'orders') {
        $filterStatus = $_GET['status'] ?? null;
        if (empty($filterStatus)) $filterStatus = null;
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $limit = 50; // Let's return up to 50 for mobile

        $orders = ShopService::listOrders(null, $filterStatus, $limit);

        echo json_encode([
            'success' => true,
            'data' => [
                'orders' => $orders
            ]
        ]);
        exit;
    }

    if ($action === 'save_product') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];

        $id = (int)($data['id'] ?? 0);
        $prodData = [
            'name'         => trim((string)($data['name'] ?? '')),
            'description'  => trim((string)($data['description'] ?? '')),
            'price_points' => (int)($data['price_points'] ?? 0),
            'stock'        => $data['stock'] ?? '',
            'status'       => $data['status'] ?? 'active',
        ];

        if (!empty($data['image_base64'])) {
            // Mobile app sends base64 image
            $base64 = $data['image_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, webp
                if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $base64 = str_replace(' ', '+', $base64);
                    $decoded = base64_decode($base64);
                    if ($decoded !== false) {
                        $dir = BASE_PATH . '/assets/uploads/shop/';
                        if (!is_dir($dir)) @mkdir($dir, 0775, true);
                        $safe = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $type;
                        if (file_put_contents($dir . $safe, $decoded)) {
                            $prodData['image_path'] = '/assets/uploads/shop/' . $safe;
                        }
                    }
                }
            }
        } else if (!empty($data['image_path'])) {
            $prodData['image_path'] = $data['image_path'];
        }

        if ($prodData['name'] === '') {
            echo json_encode(['success' => false, 'error' => 'Product name is required.']);
            exit;
        }

        $newId = ShopService::saveProduct($prodData, $id ?: null);

        if (!$id && $newId > 0) {
            try {
                require_once BASE_PATH . '/core/AffiliateBroadcastNotifier.php';
                $product = ShopService::getProduct($newId);
                if ($product) {
                    AffiliateBroadcastNotifier::notifyShopProductAdded($product);
                }
            } catch (\Throwable $_e) {
                error_log('[ShopAdminController API] notify failed: ' . $_e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'message' => $id ? 'Product updated' : 'Product created']);
        exit;
    }

    if ($action === 'delete_product') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];
        $id = (int)($data['id'] ?? 0);
        
        if ($id > 0) {
            ShopService::deleteProduct($id);
            echo json_encode(['success' => true, 'message' => 'Product deleted.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
        exit;
    }

    if ($action === 'update_order') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];
        
        $oid    = (int)($data['order_id'] ?? 0);
        $stat   = (string)($data['status'] ?? '');
        $track  = trim((string)($data['tracking_code'] ?? ''));
        $note   = trim((string)($data['admin_note'] ?? ''));

        if ($oid > 0) {
            ShopService::updateOrderStatus($oid, $stat, $track ?: null, $note ?: null);
            echo json_encode(['success' => true, 'message' => 'Order updated.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
