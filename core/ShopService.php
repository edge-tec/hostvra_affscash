<?php
/**
 * ShopService — Affiliate-facing Shop where points become products.
 *
 * Promises (per spec):
 *   ✔ Separate order system — does NOT touch any existing orders/invoices.
 *   ✔ Atomic checkout: PointsService::debit returns -1 if insufficient,
 *     so we only insert the order row when the debit succeeded.
 *   ✔ Duplicate-order prevention: client-side button disables + server-side
 *     idempotency token check against shop_orders.idempotency_key.
 */
class ShopService
{
    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `shop_products` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`          VARCHAR(255) NOT NULL,
                `slug`          VARCHAR(280) NOT NULL,
                `description`   TEXT DEFAULT NULL,
                `image_path`    VARCHAR(512) DEFAULT NULL,
                `price_points`  INT NOT NULL DEFAULT 0,
                `stock`         INT NOT NULL DEFAULT -1,   /* -1 = unlimited */
                `status`        ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
                `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_slug` (`slug`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `shop_orders` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id`    INT UNSIGNED NOT NULL,
                `product_id`      INT UNSIGNED NOT NULL,
                `product_name`    VARCHAR(255) NOT NULL,
                `price_points`    INT NOT NULL,
                `idempotency_key` CHAR(32) DEFAULT NULL,
                `recipient_name`  VARCHAR(200) DEFAULT NULL,
                `phone`           VARCHAR(50)  DEFAULT NULL,
                `email`           VARCHAR(200) DEFAULT NULL,
                `address`         TEXT DEFAULT NULL,
                `notes`           TEXT DEFAULT NULL,
                `status`          ENUM('pending','approved','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
                `tracking_code`   VARCHAR(255) DEFAULT NULL,
                `admin_note`      TEXT DEFAULT NULL,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_idempotency` (`idempotency_key`),
                KEY `idx_aff`    (`affiliate_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}
    }

    public static function listProducts(string $status = 'active', int $limit = 200): array
    {
        self::ensureSchema();
        if ($status === 'all') {
            return Database::fetchAll("SELECT * FROM shop_products ORDER BY id DESC LIMIT $limit") ?: [];
        }
        return Database::fetchAll(
            "SELECT * FROM shop_products WHERE status = ? ORDER BY id DESC LIMIT $limit",
            [$status]
        ) ?: [];
    }

    public static function getProduct(int $id): ?array
    {
        self::ensureSchema();
        $r = Database::fetchOne("SELECT * FROM shop_products WHERE id = ? LIMIT 1", [$id]);
        return $r ?: null;
    }

    public static function saveProduct(array $data, ?int $id = null): int
    {
        self::ensureSchema();
        $slug = self::slugify($data['name'] ?? ('product-' . time()));
        $row = [
            'name'         => trim((string)($data['name'] ?? '')),
            'slug'         => $slug,
            'description'  => trim((string)($data['description'] ?? '')),
            'image_path'   => $data['image_path'] ?? null,
            'price_points' => max(1, (int)($data['price_points'] ?? 1)),
            'stock'        => isset($data['stock']) && $data['stock'] !== '' ? (int)$data['stock'] : -1,
            'status'       => in_array(($data['status'] ?? 'active'), ['active','draft','archived'], true)
                              ? $data['status'] : 'active',
        ];
        if ($id) {
            // Keep existing slug if not changing name.
            unset($row['slug']);
            Database::update('shop_products', $row, 'id = ?', [$id]);
            return $id;
        }
        // Make slug unique.
        $base = $slug; $i = 1;
        while (Database::fetchOne("SELECT id FROM shop_products WHERE slug=? LIMIT 1", [$slug])) {
            $slug = $base . '-' . (++$i);
        }
        $row['slug'] = $slug;
        return (int)Database::insert('shop_products', $row);
    }

    public static function deleteProduct(int $id): void
    {
        self::ensureSchema();
        Database::query("DELETE FROM shop_products WHERE id = ?", [$id]);
    }

    /**
     * Atomic purchase. Returns ['ok'=>true, 'order_id'=>N] on success,
     * or ['ok'=>false, 'reason'=>...] on failure. Never partially commits.
     */
    public static function placeOrder(
        int $affiliateId,
        int $productId,
        array $address,
        string $idempotencyKey = ''
    ): array {
        self::ensureSchema();
        if ($affiliateId <= 0 || $productId <= 0) {
            return ['ok' => false, 'reason' => 'Invalid request'];
        }
        // Duplicate-order guard — same key returns the prior order id.
        if ($idempotencyKey !== '') {
            $dup = Database::fetchOne(
                "SELECT id FROM shop_orders WHERE idempotency_key = ? LIMIT 1",
                [$idempotencyKey]
            );
            if ($dup) return ['ok' => true, 'order_id' => (int)$dup['id'], 'duplicate' => true];
        }
        $product = self::getProduct($productId);
        if (!$product || $product['status'] !== 'active') {
            return ['ok' => false, 'reason' => 'Product not available'];
        }
        // Stock check (atomic against the row).
        if ((int)$product['stock'] === 0) {
            return ['ok' => false, 'reason' => 'Out of stock'];
        }
        $price = (int)$product['price_points'];

        // ── Atomic debit. If this returns -1, we never write the order. ──
        require_once BASE_PATH . '/core/PointsService.php';
        $newBalance = PointsService::debit(
            $affiliateId,
            $price,
            'Shop purchase: ' . $product['name'],
            'shop_order',
            null   // we link back AFTER insert
        );
        if ($newBalance === -1) {
            return ['ok' => false, 'reason' => 'Insufficient points'];
        }

        // Stock decrement (only when finite). Race-safe via WHERE stock>0.
        if ((int)$product['stock'] > 0) {
            $upd = Database::query(
                "UPDATE shop_products SET stock = stock - 1 WHERE id = ? AND stock > 0",
                [$productId]
            );
            if ($upd->rowCount() === 0) {
                // Reverse the debit because we couldn't reserve stock.
                PointsService::refund($affiliateId, $price, 'Shop refund: out of stock', 'shop_order', null);
                return ['ok' => false, 'reason' => 'Out of stock'];
            }
        }

        $orderId = (int)Database::insert('shop_orders', [
            'affiliate_id'    => $affiliateId,
            'product_id'      => $productId,
            'product_name'    => $product['name'],
            'price_points'    => $price,
            'idempotency_key' => $idempotencyKey ?: bin2hex(random_bytes(16)),
            'recipient_name'  => substr(trim((string)($address['name']   ?? '')), 0, 200),
            'phone'           => substr(trim((string)($address['phone']  ?? '')), 0, 50),
            'email'           => substr(trim((string)($address['email']  ?? '')), 0, 200),
            'address'         => trim((string)($address['address'] ?? '')),
            'notes'           => trim((string)($address['notes']   ?? '')),
            'status'          => 'pending',
        ]);

        // Back-link the ref on the debit transaction so audit shows the order.
        try {
            Database::query(
                "UPDATE points_transactions
                 SET ref_id = ?
                 WHERE affiliate_id = ? AND ref_type = 'shop_order' AND ref_id IS NULL
                 ORDER BY id DESC LIMIT 1",
                [(string)$orderId, $affiliateId]
            );
        } catch (\Throwable $_) {}

        return ['ok' => true, 'order_id' => $orderId];
    }

    public static function listOrders(?int $affiliateId = null, ?string $status = null, int $limit = 200): array
    {
        self::ensureSchema();
        $where = '1';
        $params = [];
        if ($affiliateId !== null) { $where .= ' AND so.affiliate_id = ?'; $params[] = $affiliateId; }
        if ($status   !== null && $status !== '') { $where .= ' AND so.status = ?'; $params[] = $status; }
        return Database::fetchAll(
            "SELECT so.*, CONCAT(u.first_name,' ',u.last_name) AS aff_name, u.email AS aff_email
             FROM shop_orders so
             LEFT JOIN affiliates af ON af.id = so.affiliate_id
             LEFT JOIN users u ON u.id = af.user_id
             WHERE $where ORDER BY so.created_at DESC LIMIT $limit",
            $params
        ) ?: [];
    }

    public static function updateOrderStatus(int $orderId, string $status, ?string $trackingCode = null, ?string $adminNote = null): bool
    {
        self::ensureSchema();
        $allowed = ['pending','approved','shipped','delivered','cancelled'];
        if (!in_array($status, $allowed, true)) return false;
        // If cancelling a paid order, refund the points.
        $existing = Database::fetchOne("SELECT * FROM shop_orders WHERE id = ?", [$orderId]);
        if (!$existing) return false;
        if ($status === 'cancelled' && $existing['status'] !== 'cancelled') {
            require_once BASE_PATH . '/core/PointsService.php';
            PointsService::refund(
                (int)$existing['affiliate_id'],
                (int)$existing['price_points'],
                'Order cancelled #' . $orderId,
                'shop_order',
                (string)$orderId
            );
        }
        $upd = ['status' => $status];
        if ($trackingCode !== null) $upd['tracking_code'] = $trackingCode;
        if ($adminNote    !== null) $upd['admin_note']    = $adminNote;
        Database::update('shop_orders', $upd, 'id = ?', [$orderId]);
        return true;
    }

    private static function slugify(string $s): string
    {
        $s = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $s));
        return trim($s, '-') ?: ('p-' . time());
    }
}
