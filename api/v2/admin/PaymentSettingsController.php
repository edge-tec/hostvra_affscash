<?php
header('Content-Type: application/json');

Auth::check('admin');

try {
    // Ensure manager payment columns exist
    try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS payment_method VARCHAR(100) NOT NULL DEFAULT ''"); } catch(\Throwable $e) {}
    try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS payment_details TEXT DEFAULT NULL"); } catch(\Throwable $e) {}
    try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS payment_details TEXT DEFAULT NULL"); } catch(\Throwable $e) {}

    // Ensure method_type and is_default columns exist on payment_methods
    try { Database::query("ALTER TABLE `payment_methods` ADD COLUMN `method_type` VARCHAR(50) NOT NULL DEFAULT 'custom'"); } catch(\Throwable $e) {}
    try { Database::query("ALTER TABLE `payment_methods` ADD COLUMN `is_default`  TINYINT     NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}

    // Auto-seed default payment methods (only if not already present by name)
    $_existingPmNames = array_column(
        Database::fetchAll("SELECT name FROM payment_methods", []),
        'name'
    );
    $_defaultMethods = [
        [
            'name'         => 'Payoneer',
            'description'  => 'Receive payment via Payoneer',
            'instructions' => 'Please provide your Payoneer Account Holder Name and registered Email / Account ID.',
            'method_type'  => 'payoneer',
            'is_active'    => 1,
            'is_default'   => 1,
        ],
        [
            'name'         => 'PayPal',
            'description'  => 'Receive payment via PayPal',
            'instructions' => 'Please provide your PayPal Account Holder Name and registered Email.',
            'method_type'  => 'paypal',
            'is_active'    => 1,
            'is_default'   => 1,
        ],
        [
            'name'         => 'Wire Transfer',
            'description'  => 'Receive payment via Bank Wire Transfer',
            'instructions' => 'Please provide full bank details: account number, IBAN/SWIFT, routing number, and bank address.',
            'method_type'  => 'wire',
            'is_active'    => 1,
            'is_default'   => 1,
        ],
        [
            'name'         => 'Wise',
            'description'  => 'Receive payment via Wise (TransferWise)',
            'instructions' => 'Please provide your Wise Account Holder Name and registered Email.',
            'method_type'  => 'wise',
            'is_active'    => 1,
            'is_default'   => 1,
        ],
        [
            'name'         => 'Cryptocurrency',
            'description'  => 'Receive payment via Cryptocurrency (USDT, BTC, ETH, etc.)',
            'instructions' => 'Please provide your wallet address, cryptocurrency type, and network.',
            'method_type'  => 'crypto',
            'is_active'    => 1,
            'is_default'   => 1,
        ],
    ];
    foreach ($_defaultMethods as $_dm) {
        if (!in_array($_dm['name'], $_existingPmNames, true)) {
            try { Database::insert('payment_methods', $_dm); } catch(\Throwable $e) {}
        }
    }
    unset($_existingPmNames, $_defaultMethods, $_dm);

    // Ensure offer-specific commission overrides table exists
    try { Database::query("CREATE TABLE IF NOT EXISTS `manager_offer_commissions` (
        `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `manager_id`      INT UNSIGNED NOT NULL,
        `offer_id`        INT UNSIGNED NOT NULL,
        `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_mgr_offer` (`manager_id`,`offer_id`),
        KEY `idx_manager` (`manager_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(\Throwable $e) {}

    $action = $_GET['action'] ?? 'data';

    // Parse JSON body for POST actions
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'data') {
        $paymentMethods = Database::fetchAll("SELECT * FROM payment_methods ORDER BY is_default DESC, name");
        $affiliates = Database::fetchAll(
            "SELECT af.id, af.payment_terms, af.allow_email_change, COALESCE(af.payment_method,'') as payment_method,
                    COALESCE(af.payment_details,'') as payment_details,
                    CONCAT(u.first_name,' ',u.last_name) as name, u.email
             FROM affiliates af JOIN users u ON u.id=af.user_id WHERE u.status='active' ORDER BY u.first_name"
        );
        $managers = Database::fetchAll(
            "SELECT am.id as mgr_id, am.user_id, am.commission_rate,
                    COALESCE(am.payment_method,'') as payment_method,
                    COALESCE(am.payment_details,'') as payment_details,
                    CONCAT(u.first_name,' ',u.last_name) as name, u.email
             FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE u.status='active' ORDER BY u.first_name"
        );
        $offers = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
        $offerCommissions = Database::fetchAll(
            "SELECT moc.id, moc.commission_rate,
                    am.id as mgr_id, CONCAT(u.first_name,' ',u.last_name) as manager_name,
                    o.id as offer_id, o.name as offer_name
             FROM manager_offer_commissions moc
             JOIN affiliate_managers am ON am.id = moc.manager_id
             JOIN users u ON u.id = am.user_id
             JOIN offers o ON o.id = moc.offer_id
             WHERE u.status='active'
             ORDER BY manager_name, o.name"
        );

        echo json_encode([
            'success' => true,
            'data' => [
                'payment_methods' => $paymentMethods,
                'affiliates' => $affiliates,
                'managers' => $managers,
                'offers' => $offers,
                'offer_commissions' => $offerCommissions
            ]
        ]);
        exit;
    }

    if ($action === 'save_method') {
        $pmAction = $input['pm_action'] ?? '';
        if ($pmAction === 'add') {
            Database::insert('payment_methods', [
                'name'         => $input['pm_name'] ?? '',
                'description'  => $input['pm_description'] ?? '',
                'instructions' => $input['pm_instructions'] ?? '',
                'method_type'  => $input['pm_method_type'] ?: 'custom',
                'is_active'    => 1,
                'is_default'   => 0,
            ]);
            echo json_encode(['success' => true, 'message' => 'Payment method added.']);
        } elseif ($pmAction === 'edit') {
            $pmId = (int)($input['pm_id'] ?? 0);
            if ($pmId) {
                Database::update('payment_methods', [
                    'name'         => $input['pm_name'] ?? '',
                    'description'  => $input['pm_description'] ?? '',
                    'instructions' => $input['pm_instructions'] ?? '',
                    'method_type'  => $input['pm_method_type'] ?: 'custom',
                ], 'id=?', [$pmId]);
                echo json_encode(['success' => true, 'message' => 'Payment method updated.']);
            }
        } elseif ($pmAction === 'toggle') {
            $pmId = (int)($input['pm_id'] ?? 0);
            $pm = Database::fetchOne("SELECT * FROM payment_methods WHERE id=?", [$pmId]);
            if ($pm) {
                Database::update('payment_methods', ['is_active' => $pm['is_active'] ? 0 : 1], 'id=?', [$pmId]);
                echo json_encode(['success' => true, 'message' => 'Payment method status toggled.']);
            }
        } elseif ($pmAction === 'delete') {
            $pmId = (int)($input['pm_id'] ?? 0);
            if ($pmId) {
                Database::delete('payment_methods', 'id=?', [$pmId]);
                echo json_encode(['success' => true, 'message' => 'Payment method deleted.']);
            }
        }
        exit;
    }

    if ($action === 'save_terms') {
        $scope = $input['scope'] ?? 'selected';
        $terms = $input['payment_terms'] ?? 'monthly';
        if (!in_array($terms, ['weekly','net15','net30','monthly'])) $terms = 'monthly';

        if ($scope === 'all') {
            Database::query("UPDATE affiliates SET payment_terms=?", [$terms]);
            echo json_encode(['success' => true, 'message' => 'Payment terms updated for all affiliates.']);
        } else {
            $selectedIds = $input['affiliate_ids'] ?? [];
            foreach ($selectedIds as $affId) {
                Database::update('affiliates', ['payment_terms' => $terms], 'id=?', [(int)$affId]);
            }
            echo json_encode(['success' => true, 'message' => count($selectedIds) . ' affiliate(s) updated.']);
        }
        exit;
    }

    if ($action === 'save_commission') {
        $mgId = (int)($input['manager_id'] ?? 0);
        $rate = max(0, min(100, (float)($input['commission_rate'] ?? 0)));

        if ($mgId) {
            Database::update('affiliate_managers', ['commission_rate' => $rate], 'user_id=?', [$mgId]);
            echo json_encode(['success' => true, 'message' => 'Manager commission rate updated.']);
        }
        exit;
    }

    if ($action === 'save_offer_commission') {
        $ocAction = $input['oc_action'] ?? '';
        if ($ocAction === 'save') {
            $mgId   = (int)($input['oc_manager_id'] ?? 0);
            $offId  = (int)($input['oc_offer_id'] ?? 0);
            $rate   = max(0, min(100, (float)($input['oc_rate'] ?? 0)));
            if ($mgId && $offId) {
                Database::query(
                    "INSERT INTO manager_offer_commissions (manager_id, offer_id, commission_rate)
                     VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE commission_rate=VALUES(commission_rate)",
                    [$mgId, $offId, $rate]
                );
                echo json_encode(['success' => true, 'message' => 'Offer commission override saved.']);
            }
        } elseif ($ocAction === 'delete') {
            $ocId = (int)($input['oc_id'] ?? 0);
            if ($ocId) {
                Database::delete('manager_offer_commissions', 'id=?', [$ocId]);
                echo json_encode(['success' => true, 'message' => 'Offer commission override removed.']);
            }
        }
        exit;
    }

    if ($action === 'save_email_perm') {
        $affId   = (int)($input['affiliate_id'] ?? 0);
        $allowed = (int)($input['allow_email_change'] ?? 0);
        if ($affId) {
            Database::update('affiliates', ['allow_email_change' => $allowed], 'id=?', [$affId]);
            echo json_encode(['success' => true, 'message' => 'Email change permission updated.']);
        }
        exit;
    }

    if ($action === 'save_payout_info') {
        $type = $input['payout_type'] ?? '';
        $entityId = (int)($input['entity_id'] ?? 0);
        $method = substr(trim($input['payment_method'] ?? ''), 0, 100);
        $pmType = $input['pd_method_type'] ?? 'custom';
        $pdFields = $input['pd_field'] ?? null;

        if ($pdFields && is_array($pdFields) && $pmType !== 'custom') {
            $details = json_encode(array_map('trim', $pdFields));
        } else {
            $details = trim($input['payment_details'] ?? '');
        }

        if ($type === 'affiliate' && $entityId) {
            try {
                $colType = Database::fetchOne(
                    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME='payment_method'"
                );
                if ($colType && stripos($colType['COLUMN_TYPE'], 'enum') !== false) {
                    Database::query("ALTER TABLE `affiliates` MODIFY `payment_method` VARCHAR(100) NOT NULL DEFAULT ''");
                }
            } catch (\Throwable $e) {}
            Database::update('affiliates', [
                'payment_method'  => $method,
                'payment_details' => $details,
            ], 'id=?', [$entityId]);
            echo json_encode(['success' => true, 'message' => 'Affiliate payment info saved.']);
        } elseif ($type === 'manager' && $entityId) {
            Database::update('affiliate_managers', [
                'payment_method'  => $method,
                'payment_details' => $details,
            ], 'id=?', [$entityId]);
            echo json_encode(['success' => true, 'message' => 'Manager payment info saved.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
