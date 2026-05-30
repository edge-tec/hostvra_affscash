<?php
Auth::check('admin');
$pageTitle = 'Payment Settings';
$activeTab = Helpers::get('tab') ?: 'methods';

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

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $tab = Helpers::post('tab') ?: 'methods';

    if ($tab === 'methods') {
        $pmAction = Helpers::post('pm_action');
        if ($pmAction === 'add') {
            Database::insert('payment_methods', [
                'name'         => Helpers::post('pm_name'),
                'description'  => Helpers::post('pm_description'),
                'instructions' => Helpers::postRaw('pm_instructions'),
                'method_type'  => Helpers::post('pm_method_type') ?: 'custom',
                'is_active'    => 1,
                'is_default'   => 0,
            ]);
            Helpers::flash('success', 'Payment method added.');
        } elseif ($pmAction === 'edit') {
            $pmId = (int)Helpers::postRaw('pm_id');
            if ($pmId) {
                Database::update('payment_methods', [
                    'name'         => Helpers::post('pm_name'),
                    'description'  => Helpers::post('pm_description'),
                    'instructions' => Helpers::postRaw('pm_instructions'),
                    'method_type'  => Helpers::post('pm_method_type') ?: 'custom',
                ], 'id=?', [$pmId]);
                Helpers::flash('success', 'Payment method updated.');
            }
        } elseif ($pmAction === 'toggle') {
            $pmId = (int)Helpers::postRaw('pm_id');
            $pm = Database::fetchOne("SELECT * FROM payment_methods WHERE id=?", [$pmId]);
            if ($pm) Database::update('payment_methods', ['is_active' => $pm['is_active'] ? 0 : 1], 'id=?', [$pmId]);
            Helpers::flash('success', 'Payment method updated.');
        } elseif ($pmAction === 'delete') {
            $pmId = (int)Helpers::postRaw('pm_id');
            Database::delete('payment_methods', 'id=?', [$pmId]);
            Helpers::flash('success', 'Payment method deleted.');
        }
        Helpers::redirect('/admin/payment-settings?tab=methods');
    }

    elseif ($tab === 'terms') {
        $scope = Helpers::post('scope'); // 'all' or 'selected'
        $terms = Helpers::post('payment_terms');
        if (!in_array($terms, ['weekly','net15','net30','monthly'])) $terms = 'monthly';

        if ($scope === 'all') {
            Database::query("UPDATE affiliates SET payment_terms=?", [$terms]);
            Helpers::flash('success', 'Payment terms updated for all affiliates.');
        } else {
            $selectedIds = $_POST['affiliate_ids'] ?? [];
            foreach ($selectedIds as $affId) {
                Database::update('affiliates', ['payment_terms' => $terms], 'id=?', [(int)$affId]);
            }
            Helpers::flash('success', count($selectedIds) . ' affiliate(s) updated.');
        }
        Helpers::redirect('/admin/payment-settings?tab=terms');
    }

    elseif ($tab === 'commission') {
        $mgId = (int)Helpers::postRaw('manager_id');
        $rate = max(0, min(100, (float)Helpers::postRaw('commission_rate')));

        if ($mgId) {
            Database::update('affiliate_managers', ['commission_rate' => $rate], 'user_id=?', [$mgId]);
            Helpers::flash('success', 'Manager commission rate updated.');
        }
        Helpers::redirect('/admin/payment-settings?tab=commission');
    }

    elseif ($tab === 'offer_commission') {
        $action = Helpers::post('oc_action');
        if ($action === 'save') {
            $mgId   = (int)Helpers::postRaw('oc_manager_id');
            $offId  = (int)Helpers::postRaw('oc_offer_id');
            $rate   = max(0, min(100, (float)Helpers::postRaw('oc_rate')));
            if ($mgId && $offId) {
                Database::query(
                    "INSERT INTO manager_offer_commissions (manager_id, offer_id, commission_rate)
                     VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE commission_rate=VALUES(commission_rate)",
                    [$mgId, $offId, $rate]
                );
                Helpers::flash('success', 'Offer commission override saved.');
            }
        } elseif ($action === 'delete') {
            $ocId = (int)Helpers::postRaw('oc_id');
            if ($ocId) {
                Database::delete('manager_offer_commissions', 'id=?', [$ocId]);
                Helpers::flash('success', 'Offer commission override removed.');
            }
        }
        Helpers::redirect('/admin/payment-settings?tab=commission');
    }

    elseif ($tab === 'email_perm') {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $allowed = isset($_POST['allow_email_change']) ? 1 : 0;
        if ($affId) {
            Database::update('affiliates', ['allow_email_change' => $allowed], 'id=?', [$affId]);
            Helpers::flash('success', 'Email change permission updated.');
        }
        Helpers::redirect('/admin/payment-settings?tab=commission');
    }

    // ── Save affiliate payout info ────────────────────────────────────────
    elseif ($tab === 'payout_info' && Helpers::post('payout_type') === 'affiliate') {
        $affId      = (int)Helpers::postRaw('entity_id');
        $method     = substr(trim(Helpers::postRaw('payment_method')), 0, 100);
        $pmType     = Helpers::post('pd_method_type') ?: 'custom';
        $pdFields   = $_POST['pd_field'] ?? null;

        // Encode structured fields as JSON if the method has a known type
        if ($pdFields && is_array($pdFields) && $pmType !== 'custom') {
            $details = json_encode(array_map('trim', $pdFields));
        } else {
            $details = Helpers::postRaw('payment_details') ?: '';
        }

        if ($affId) {
            // Auto-migrate payment_method column type if still ENUM
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
            ], 'id=?', [$affId]);
            Helpers::flash('success', 'Affiliate payment info saved.');
        }
        Helpers::redirect('/admin/payment-settings?tab=payout_info');
    }

    // ── Save manager payout info ──────────────────────────────────────────
    elseif ($tab === 'payout_info' && Helpers::post('payout_type') === 'manager') {
        $mgrId    = (int)Helpers::postRaw('entity_id');
        $method   = substr(trim(Helpers::postRaw('payment_method')), 0, 100);
        $pmType   = Helpers::post('pd_method_type') ?: 'custom';
        $pdFields = $_POST['pd_field'] ?? null;

        if ($pdFields && is_array($pdFields) && $pmType !== 'custom') {
            $details = json_encode(array_map('trim', $pdFields));
        } else {
            $details = Helpers::postRaw('payment_details') ?: '';
        }

        if ($mgrId) {
            Database::update('affiliate_managers', [
                'payment_method'  => $method,
                'payment_details' => $details,
            ], 'id=?', [$mgrId]);
            Helpers::flash('success', 'Manager payment info saved.');
        }
        Helpers::redirect('/admin/payment-settings?tab=payout_info');
    }
}

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

require BASE_PATH . '/views/admin/payment_settings/index.php';
