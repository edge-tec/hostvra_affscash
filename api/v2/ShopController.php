<?php
header('Content-Type: application/json');

try {
    Auth::check('affiliate');
    $affId = (int)Auth::affiliateId();
    $action = $_GET['action'] ?? 'list';

    require_once BASE_PATH . '/core/ShopService.php';
    require_once BASE_PATH . '/core/PointsService.php';

    if ($action === 'list') {
        $balance = PointsService::balance($affId);
        $products = ShopService::listProducts('active');
        $orders = ShopService::listOrders($affId, null, 200);

        echo json_encode([
            'success' => true,
            'balance' => $balance,
            'products' => $products,
            'orders' => $orders
        ]);
        exit;
    }

    if ($action === 'place_order') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?: [];

        $productId = (int)($data['product_id'] ?? 0);
        $idempotencyKey = trim((string)($data['idempotency_key'] ?? '')) ?: bin2hex(random_bytes(16));
        $address = [
            'name'    => $data['recipient_name'] ?? '',
            'phone'   => $data['phone'] ?? '',
            'email'   => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'notes'   => $data['notes'] ?? '',
        ];

        $r = ShopService::placeOrder($affId, $productId, $address, $idempotencyKey);
        
        if (!empty($r['ok'])) {
            $msg = !empty($r['duplicate']) ? 'Order already submitted.' : 'Order submitted! Points have been deducted.';
            echo json_encode([
                'success' => true,
                'message' => $msg,
                'order_id' => $r['order_id'] ?? 0
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $r['reason'] ?? 'Order failed.'
            ]);
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
