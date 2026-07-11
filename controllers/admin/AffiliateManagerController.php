<?php
Auth::check('admin');
$pageTitle = 'Affiliate Managers';

// ── Schema migrations ─────────────────────────────────────────────────────────
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN skype VARCHAR(200) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE users MODIFY COLUMN status ENUM('active','pending','suspended','rejected','deleted') NOT NULL DEFAULT 'pending'"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN telegram VARCHAR(200) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN discord VARCHAR(200) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS balance DECIMAL(10,4) NOT NULL DEFAULT 0.0000"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS hide_earnings TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS commission_mode VARCHAR(20) NOT NULL DEFAULT 'all'"); } catch(\Throwable $_e) {}
// Track when each affiliate was assigned to a manager.
try { Database::query("ALTER TABLE affiliates ADD COLUMN manager_assigned_at DATETIME DEFAULT NULL"); } catch(\Throwable $_e) {}
// Fix: reset manager_assigned_at to far-past for all assigned affiliates.
// A prior migration bug set this to NOW() which blocked ALL historical conversions.
// We reset to '2000-01-01' so all conversions are eligible for commission.
try { Database::query("UPDATE affiliates SET manager_assigned_at = '2000-01-01 00:00:00' WHERE manager_id IS NOT NULL"); } catch(\Throwable $_e) {}
// invoices table: widen type column from ENUM to VARCHAR so 'manager_fee' is accepted
try {
    $typeCol = Database::fetchOne(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'type'"
    );
    if ($typeCol && strpos((string)($typeCol['COLUMN_TYPE'] ?? ''), 'enum') !== false) {
        Database::query("ALTER TABLE invoices MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'affiliate_payout'");
    }
} catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS manager_id INT UNSIGNED DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS balance_before DECIMAL(10,4) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN IF NOT EXISTS balance_after  DECIMAL(10,4) DEFAULT NULL"); } catch(\Throwable $_e) {}

require_once BASE_PATH . '/core/ManagerCommissionService.php';
ManagerPermissions::ensureSchema();   // additive — creates 4 new audit tables if missing

$allPerms = ['view_affiliates','approve_affiliates','edit_affiliate_payouts','view_conversions','view_reports','manage_offers','manage_payments','create_invoices'];
$action   = Helpers::get('action') ?: (isset($_GET['id']) ? 'view' : 'index');

// ── PERMISSIONS (advanced — admin-controlled per-manager matrix) ─────────────
// New surface dedicated to the granular ManagerPermissions catalogue. Keeps
// the legacy `affiliate_managers.permissions` JSON column intact — both
// layers coexist; the new layer is the one consulted by ManagerPermissions::can().
if ($action === 'permissions') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $matrix = $_POST['perm'] ?? [];   // perm[manager_id][permission_key] = '1' if checked
        if (is_array($matrix)) {
            foreach ($matrix as $mid => $perms) {
                ManagerPermissions::setAll((int)$mid, (array)$perms, (int)(Auth::id() ?? 0));
            }
        }
        Helpers::flash('success', 'Affiliate Manager permissions saved.');
        Helpers::redirect('/admin/affiliate-managers/permissions');
    }

    $managers = Database::fetchAll(
        "SELECT am.id AS mgr_id, u.email, u.status,
                CONCAT(u.first_name,' ',u.last_name) AS name
         FROM affiliate_managers am
         JOIN users u ON u.id = am.user_id
         WHERE u.role = 'affiliate_manager' AND u.status <> 'deleted'
         ORDER BY u.first_name, u.last_name"
    ) ?: [];
    // Pre-load the permission map for every manager so the view renders in one pass.
    $permMatrix = [];
    foreach ($managers as $m) {
        $permMatrix[(int)$m['mgr_id']] = ManagerPermissions::map((int)$m['mgr_id']);
    }
    require BASE_PATH . '/views/admin/affiliate_managers/permissions.php';
    return;
}

// ── FRAUD REJECTIONS HISTORY (admin-visible audit log) ───────────────────────
if ($action === 'fraud_rejections') {
    $rows = Database::fetchAll(
        "SELECT fr.*, CONCAT(u.first_name,' ',u.last_name) AS manager_name, u.email AS manager_email
         FROM manager_fraud_rejections fr
         JOIN affiliate_managers am ON am.id = fr.manager_id
         JOIN users u ON u.id = am.user_id
         ORDER BY fr.rejected_at DESC
         LIMIT 1000"
    ) ?: [];
    require BASE_PATH . '/views/admin/affiliate_managers/fraud_rejections.php';
    return;
}

// ── INDEX ─────────────────────────────────────────────────────────────────────
if ($action === 'index') {
    $managers = Database::fetchAll(
        "SELECT u.id, u.email, u.first_name, u.last_name, u.status, u.created_at,
                am.id as mgr_id, am.permissions, am.balance, am.commission_rate,
                (SELECT COUNT(*) FROM affiliates af WHERE af.manager_id=am.id) as aff_count
         FROM users u
         JOIN affiliate_managers am ON am.user_id=u.id
         WHERE u.role='affiliate_manager'
         ORDER BY u.created_at DESC"
    );
    require BASE_PATH . '/views/admin/affiliate_managers/index.php';
}

// ── CREATE ────────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname          = trim(Helpers::post('first_name'));
        $lname          = trim(Helpers::post('last_name'));
        $email          = strtolower(trim(Helpers::postRaw('email')));
        $pass           = Helpers::postRaw('password');
        $perms          = $_POST['permissions'] ?? [];
        $notes          = Helpers::post('notes');
        $skype          = trim(Helpers::post('skype')     ?? '') ?: null;
        $telegram       = trim(Helpers::post('telegram')  ?? '') ?: null;
        $discord        = trim(Helpers::post('discord')   ?? '') ?: null;
        $commissionRate = max(0.0, min(100.0, (float)Helpers::post('commission_rate')));

        if (!$fname || !$lname) $errors[] = 'First and last name required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!$errors && Database::fetchOne("SELECT id FROM users WHERE email=?", [$email])) {
            $errors[] = 'Email already registered.';
        }

        if (!$errors) {
            Database::begin();
            try {
                $userId = Database::insert('users', [
                    'email'         => $email,
                    'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role'          => 'affiliate_manager',
                    'status'        => 'active',
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                ]);
                Database::insert('affiliate_managers', [
                    'user_id'         => $userId,
                    'permissions'     => json_encode(array_values(array_filter($perms, fn($p) => in_array($p, $allPerms)))),
                    'notes'           => $notes,
                    'skype'           => $skype,
                    'telegram'        => $telegram,
                    'discord'         => $discord,
                    'commission_rate' => $commissionRate,
                    'balance'         => 0.0000,
                ]);
                Database::commit();
                Helpers::flash('success', "Affiliate Manager {$fname} {$lname} created.");
                Helpers::redirect('/admin/affiliate-managers');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to create account: ' . $e->getMessage();
            }
        }
    }
    require BASE_PATH . '/views/admin/affiliate_managers/create.php';
}

// ── VIEW / MANAGE ─────────────────────────────────────────────────────────────
elseif ($action === 'view') {
    $id = (int)$_GET['id'];
    $manager = Database::fetchOne(
        "SELECT u.*, am.id as mgr_id, am.permissions, am.notes, am.skype, am.telegram, am.discord,
                am.commission_rate, am.balance, COALESCE(am.hide_earnings,0) as hide_earnings,
                COALESCE(am.commission_mode,'all') as commission_mode
         FROM users u
         JOIN affiliate_managers am ON am.user_id=u.id
         WHERE am.id=?",
        [$id]
    );
    if (!$manager) Helpers::redirect('/admin/affiliate-managers');

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $sub = Helpers::post('submit_type');

        if ($sub === 'permissions') {
            $perms          = $_POST['permissions'] ?? [];
            $commissionRate = max(0.0, min(100.0, (float)Helpers::post('commission_rate')));
            $hideEarnings   = isset($_POST['hide_earnings']) ? 1 : 0;
            $commissionMode = Helpers::post('commission_mode') === 'selected_only' ? 'selected_only' : 'all';
            Database::update('affiliate_managers', [
                'permissions'     => json_encode(array_values(array_filter($perms, fn($p) => in_array($p, $allPerms)))),
                'notes'           => Helpers::post('notes'),
                'skype'           => trim(Helpers::post('skype')    ?? '') ?: null,
                'telegram'        => trim(Helpers::post('telegram') ?? '') ?: null,
                'discord'         => trim(Helpers::post('discord')  ?? '') ?: null,
                'commission_rate' => $commissionRate,
                'hide_earnings'   => $hideEarnings,
                'commission_mode' => $commissionMode,
            ], 'id=?', [$id]);

            // ── Save per-offer commission selections ──────────────────────────
            // Delete existing offer commissions for this manager, then re-insert
            // the ones submitted. Only offers that are checked AND have a rate > 0
            // (or rate = 0 explicitly set by admin) are stored.
            try {
                Database::query(
                    "DELETE FROM manager_offer_commissions WHERE manager_id = ?",
                    [$id]
                );
                $selectedOfferIds = array_map('intval', (array)($_POST['offer_ids'] ?? []));
                $offerRates       = $_POST['offer_commission_rates'] ?? [];
                foreach ($selectedOfferIds as $offerId) {
                    if ($offerId <= 0) continue;
                    $offerRate = max(0.0, min(100.0, (float)($offerRates[$offerId] ?? 0)));
                    Database::query(
                        "INSERT INTO manager_offer_commissions (manager_id, offer_id, commission_rate)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE commission_rate = VALUES(commission_rate)",
                        [$id, $offerId, $offerRate]
                    );
                }
            } catch (\Throwable $_offerErr) {
                error_log('[AffMgr] save offer commissions: ' . $_offerErr->getMessage());
            }

            // ── AUTO-CALCULATE COMMISSIONS ────────────────────────────────────
            // Automatically run backfill whenever admin saves Commission Rate (%)
            // or Selected Offers Commission. Works for both "all offers" mode
            // (commission_rate > 0) and "selected_only" mode (offer_ids present).
            // INSERT IGNORE inside backfillForManager prevents duplicates — safe
            // to run on every save regardless of existing records.
            {
                $_savedRate         = max(0.0, min(100.0, (float)Helpers::post('commission_rate')));
                $_savedOfferIds     = array_filter(array_map('intval', (array)($_POST['offer_ids'] ?? [])), fn($v) => $v > 0);
                $_savedMode         = Helpers::post('commission_mode') === 'selected_only' ? 'selected_only' : 'all';
                // Auto-calc: trigger if global rate set (all-offers mode) OR selected offers exist (selected-only mode)
                $_shouldAutoCalc    = ($_savedRate > 0 && $_savedMode === 'all')
                                   || ($_savedMode === 'selected_only' && count($_savedOfferIds) > 0)
                                   || ($_savedRate > 0 && $_savedMode === 'selected_only');

                if ($_shouldAutoCalc) {
                    try {
                        $_autoR = ManagerCommissionService::backfillForManager((int)$id, '2000-01-01', date('Y-m-d'));
                        if (!empty($_autoR['error'])) {
                            Helpers::flash('warning', 'Settings saved. Commission auto-calculation error: ' . $_autoR['error'] . ' — check PHP error log.');
                        } elseif ($_autoR['credited'] > 0) {
                            Helpers::flash('success', sprintf(
                                'Settings saved &amp; commissions auto-calculated: %d new conversion(s) credited. Total commission balance: $%s.',
                                $_autoR['credited'],
                                number_format((float)($_autoR['total_added'] ?? 0), 2)
                            ));
                        } else {
                            Helpers::flash('success', sprintf(
                                'Permissions &amp; settings updated. Commissions verified: %d conversion(s) already recorded (balance up to date).',
                                (int)($_autoR['processed'] ?? 0)
                            ));
                        }
                    } catch (\Throwable $_autoErr) {
                        error_log('[AffMgr] auto-backfill after save: ' . $_autoErr->getMessage());
                        Helpers::flash('success', 'Permissions &amp; settings updated.');
                    }
                } else {
                    Helpers::flash('success', 'Permissions &amp; settings updated.');
                }
            }

        } elseif ($sub === 'status') {
            $newStatus = Helpers::post('status');
            if (in_array($newStatus, ['active','suspended'])) {
                Database::update('users', ['status' => $newStatus], 'id=?', [$manager['id']]);
                Helpers::flash('success', 'Status updated.');
            }

        } elseif ($sub === 'assign') {
            $affId = (int)Helpers::post('affiliate_id');
            if ($affId > 0) {
                Database::update('affiliates', [
                    'manager_id'          => $manager['mgr_id'],
                    'manager_assigned_at' => date('Y-m-d H:i:s'),
                ], 'id=?', [$affId]);
                Helpers::flash('success', 'Affiliate assigned. Commission will apply to all conversions (use Recalculate to process existing ones).');
            }

        } elseif ($sub === 'unassign') {
            $affId = (int)Helpers::post('affiliate_id');
            if ($affId > 0) {
                Database::update('affiliates', [
                    'manager_id'          => null,
                    'manager_assigned_at' => null,
                ], 'id=?', [$affId]);
                Helpers::flash('success', 'Affiliate unassigned.');
            }

        } elseif ($sub === 'recalculate_commissions') {
            $recalcFrom = Helpers::post('recalc_from') ?: '';
            $recalcTo   = Helpers::post('recalc_to')   ?: '';
            $r = ManagerCommissionService::backfillForManager((int)$manager['mgr_id'], $recalcFrom, $recalcTo);
            if (!empty($r['error'])) {
                Helpers::flash('error', 'Recalculation SQL error: ' . $r['error'] . ' — Check PHP error_log for details.');
            } elseif ($r['credited'] > 0) {
                Helpers::flash('success', sprintf(
                    'Done: %d conversion(s) processed, %d commission(s) credited (+$%s total). %d already recorded/skipped.',
                    $r['processed'], $r['credited'], number_format($r['total_added'], 2), $r['skipped']
                ));
            } elseif ($r['processed'] === 0) {
                Helpers::flash('warning', 'No approved conversions found in this date range. Check Diagnostics panel — affiliates may not be assigned, or conversions may be Pending (not yet approved).');
            } else {
                Helpers::flash('info', sprintf(
                    '%d conversion(s) checked — all already recorded (no duplicates added). Balance is up to date.',
                    $r['processed']
                ));
            }

        } elseif ($sub === 'approve_commissions') {
            $approved = ManagerCommissionService::approveAllPending((int)$manager['mgr_id']);
            Helpers::flash('success', "Approved {$approved} pending commission record(s).");

        } elseif ($sub === 'adjust_balance') {
            $adjType   = Helpers::post('adj_type');   // add | subtract | set
            $adjAmount = round(abs((float)Helpers::postRaw('adj_amount')), 4);
            $adjNote   = trim(Helpers::post('adj_note') ?? '');

            if ($adjAmount <= 0) {
                Helpers::flash('error', 'Adjustment amount must be greater than zero.');
            } else {
                switch ($adjType) {
                    case 'add':
                        Database::query(
                            "UPDATE affiliate_managers SET balance = balance + ? WHERE id = ?",
                            [$adjAmount, (int)$manager['mgr_id']]
                        );
                        Helpers::flash('success', sprintf('Added $%s to balance. %s', number_format($adjAmount, 2), $adjNote ? "Note: $adjNote" : ''));
                        break;
                    case 'subtract':
                        Database::query(
                            "UPDATE affiliate_managers SET balance = GREATEST(0, balance - ?) WHERE id = ?",
                            [$adjAmount, (int)$manager['mgr_id']]
                        );
                        Helpers::flash('success', sprintf('Subtracted $%s from balance. %s', number_format($adjAmount, 2), $adjNote ? "Note: $adjNote" : ''));
                        break;
                    case 'set':
                        Database::query(
                            "UPDATE affiliate_managers SET balance = ? WHERE id = ?",
                            [$adjAmount, (int)$manager['mgr_id']]
                        );
                        Helpers::flash('success', sprintf('Balance set to $%s. %s', number_format($adjAmount, 2), $adjNote ? "Note: $adjNote" : ''));
                        break;
                    default:
                        Helpers::flash('error', 'Invalid adjustment type.');
                }
            }
        }

        Helpers::redirect('/admin/affiliate-managers/' . $id);
    }

    // Reload after possible POST
    $manager = Database::fetchOne(
        "SELECT u.*, am.id as mgr_id, am.permissions, am.notes, am.skype, am.telegram, am.discord,
                am.commission_rate, am.balance, COALESCE(am.hide_earnings,0) as hide_earnings,
                COALESCE(am.commission_mode,'all') as commission_mode
         FROM users u
         JOIN affiliate_managers am ON am.user_id=u.id
         WHERE am.id=?",
        [$id]
    );

    // ── AUTO-BACKFILL on page load ────────────────────────────────────────────
    // Automatically detect and commission any approved conversions not yet recorded.
    // INSERT IGNORE inside backfillForManager prevents duplicates — safe every load.
    // Covers: first setup, rate change, new conversions, and selected-offer mode.
    {
        $_mgrId           = (int)($manager['mgr_id'] ?? 0);
        $_mgrRate         = (float)($manager['commission_rate'] ?? 0);
        $_hasOfferOverrides = false;
        try {
            $_hasOfferOverrides = (int)(Database::fetchOne(
                "SELECT COUNT(*) AS c FROM manager_offer_commissions WHERE manager_id = ?",
                [$_mgrId]
            )['c'] ?? 0) > 0;
        } catch (\Throwable $_e) {}

        if ($_mgrId > 0 && ($_mgrRate > 0 || $_hasOfferOverrides)) {
            // Count approved conversions not yet assigned a commission record
            $_unconverted = 0;
            try {
                $_unconverted = (int)(Database::fetchOne(
                    "SELECT COUNT(*) AS c
                     FROM conversions c
                     JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
                     LEFT JOIN manager_commissions mc ON mc.conversion_id = c.id AND mc.manager_id = ?
                     WHERE c.status = 'approved'
                       AND COALESCE(c.is_hidden,0) = 0
                       AND c.payout > 0
                       AND mc.id IS NULL",
                    [$_mgrId, $_mgrId]
                )['c'] ?? 0);
            } catch (\Throwable $_e) {}

            if ($_unconverted > 0) {
                $_autoR = ManagerCommissionService::backfillForManager($_mgrId, '2000-01-01', date('Y-m-d'));
                if (!empty($_autoR['error'])) {
                    Helpers::flash('error', 'Commission auto-calculation failed: ' . $_autoR['error']);
                } elseif ($_autoR['credited'] > 0) {
                    Helpers::flash('success', sprintf(
                        'Commissions auto-calculated: %d new conversion(s) credited. Total balance: $%s',
                        $_autoR['credited'], number_format($_autoR['total_added'], 2)
                    ));
                    Helpers::redirect('/admin/affiliate-managers/' . $id);
                }
            }
        }
    }

    $mgrCommissionSummary = ManagerCommissionService::getSummaryForManager((int)$manager['mgr_id']);
    $mgrBalance           = ManagerCommissionService::getManagerBalance((int)$manager['mgr_id']);
    $commissionLogs       = ManagerCommissionService::getCommissionLogs((int)$manager['mgr_id'], 30);

    // Diagnostic: gather data to explain $0 balance if needed
    $commissionDiag = [];
    try {
        $diagAffCount = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c FROM affiliates WHERE manager_id = ?",
            [(int)$manager['mgr_id']]
        )['c'] ?? 0);

        $diagConvApproved = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c
             FROM conversions c
             JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
             WHERE c.status = 'approved'",
            [(int)$manager['mgr_id']]
        )['c'] ?? 0);

        $diagConvWithPayout = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c
             FROM conversions c
             JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
             WHERE c.status = 'approved' AND c.payout > 0",
            [(int)$manager['mgr_id']]
        )['c'] ?? 0);

        $diagConvPending = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c
             FROM conversions c
             JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
             WHERE c.status = 'pending'",
            [(int)$manager['mgr_id']]
        )['c'] ?? 0);

        $diagCommRecords = (int)(Database::fetchOne(
            "SELECT COUNT(*) as c FROM manager_commissions WHERE manager_id = ?",
            [(int)$manager['mgr_id']]
        )['c'] ?? 0);

        $commissionDiag = [
            'assigned_affiliates'    => $diagAffCount,
            'approved_conversions'   => $diagConvApproved,
            'conv_with_payout'       => $diagConvWithPayout,
            'pending_conversions'    => $diagConvPending,
            'commission_records'     => $diagCommRecords,
            'commission_rate'        => (float)($manager['commission_rate'] ?? 0),
        ];
    } catch (\Throwable $_e) {
        $commissionDiag = ['error' => $_e->getMessage()];
    }

    $assignedAffiliates = Database::fetchAll(
        "SELECT af.id, af.affiliate_code, af.manager_assigned_at,
                u.first_name, u.last_name, u.email, u.status
         FROM affiliates af
         JOIN users u ON u.id=af.user_id
         WHERE af.manager_id=?
         ORDER BY u.first_name",
        [$manager['mgr_id']]
    );
    $unassignedAffiliates = Database::fetchAll(
        "SELECT af.id, af.affiliate_code, u.first_name, u.last_name
         FROM affiliates af
         JOIN users u ON u.id=af.user_id
         WHERE af.manager_id IS NULL AND u.status='active'
         ORDER BY u.first_name
         LIMIT 300"
    );

    // ── All offers (for offer-commission selection panel) ─────────────────────
    $allOffers = [];
    try {
        $allOffers = Database::fetchAll(
            "SELECT id, name, status FROM offers WHERE status != 'deleted' ORDER BY name LIMIT 2000"
        );
    } catch (\Throwable $_e) {}

    // ── Existing per-offer commission overrides for this manager ──────────────
    $managerOfferCommissions = [];
    try {
        $moc = Database::fetchAll(
            "SELECT offer_id, commission_rate FROM manager_offer_commissions WHERE manager_id = ?",
            [$manager['mgr_id']]
        );
        foreach ($moc as $row) {
            $managerOfferCommissions[(int)$row['offer_id']] = (float)$row['commission_rate'];
        }
    } catch (\Throwable $_e) {}

    require BASE_PATH . '/views/admin/affiliate_managers/view.php';
}

// ── EDIT ──────────────────────────────────────────────────────────────────────
elseif ($action === 'edit') {
    $id = (int)$_GET['id'];
    $manager = Database::fetchOne(
        "SELECT u.*, am.id as mgr_id, am.permissions, am.notes, am.skype, am.telegram, am.discord, am.commission_rate
         FROM users u
         JOIN affiliate_managers am ON am.user_id=u.id
         WHERE am.id=?",
        [$id]
    );
    if (!$manager) Helpers::redirect('/admin/affiliate-managers');

    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname          = trim(Helpers::post('first_name'));
        $lname          = trim(Helpers::post('last_name'));
        $email          = strtolower(trim(Helpers::postRaw('email')));
        $newPass        = Helpers::postRaw('new_password');
        $status         = in_array(Helpers::post('status'), ['active','suspended']) ? Helpers::post('status') : $manager['status'];
        $perms          = $_POST['permissions'] ?? [];
        $notes          = Helpers::post('notes');
        $skype          = trim(Helpers::post('skype')    ?? '') ?: null;
        $telegram       = trim(Helpers::post('telegram') ?? '') ?: null;
        $discord        = trim(Helpers::post('discord')  ?? '') ?: null;
        $commissionRate = max(0.0, min(100.0, (float)Helpers::post('commission_rate')));

        if (!$fname || !$lname) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if ($newPass && strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
        if (!$errors) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$email, $manager['id']]);
            if ($existing) $errors[] = 'Email already in use by another account.';
        }

        if (!$errors) {
            $userUpdate = [
                'first_name' => $fname,
                'last_name'  => $lname,
                'email'      => $email,
                'status'     => $status,
            ];
            if ($newPass) $userUpdate['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            Database::update('users', $userUpdate, 'id=?', [$manager['id']]);
            Database::update('affiliate_managers', [
                'permissions'     => json_encode(array_values(array_filter($perms, fn($p) => in_array($p, $allPerms)))),
                'notes'           => $notes,
                'skype'           => $skype,
                'telegram'        => $telegram,
                'discord'         => $discord,
                'commission_rate' => $commissionRate,
            ], 'id=?', [$id]);
            Helpers::flash('success', 'Affiliate Manager updated successfully.');
            Helpers::redirect('/admin/affiliate-managers/' . $id);
        }
    }
    require BASE_PATH . '/views/admin/affiliate_managers/edit.php';
}

// ── DELETE ────────────────────────────────────────────────────────────────────
elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id      = (int)Helpers::postRaw('mgr_id');
        $manager = Database::fetchOne(
            "SELECT u.id as user_id, u.first_name, u.last_name
             FROM users u
             JOIN affiliate_managers am ON am.user_id=u.id
             WHERE am.id=?",
            [$id]
        );
        if ($manager) {
            Database::update('users', ['status' => 'deleted'], 'id=?', [$manager['user_id']]);
            // Unassign all affiliates from this manager
            Database::query(
                "UPDATE affiliates SET manager_id=NULL, manager_assigned_at=NULL
                 WHERE manager_id=?",
                [$id]
            );
            Database::query("DELETE FROM user_active_sessions WHERE user_id=?", [$manager['user_id']]);
            Helpers::flash('success', 'Affiliate Manager account deleted.');
        } else {
            Helpers::flash('error', 'Manager not found.');
        }
    }
    Helpers::redirect('/admin/affiliate-managers');
}

// ── IMPERSONATE ───────────────────────────────────────────────────────────────
elseif ($action === 'impersonate') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $user   = Database::fetchOne(
        "SELECT id FROM users WHERE id=? AND role='affiliate_manager'",
        [$userId]
    );
    if ($user) {
        $_SESSION['impersonate_return_url'] = $_SERVER['HTTP_REFERER'] ?? '/admin/affiliate-managers';
        if (Auth::impersonate($userId)) {
            Helpers::redirect('/affiliate_manager/dashboard');
        }
    }
    Helpers::flash('error', 'Could not switch to that account.');
    Helpers::redirect('/admin/affiliate-managers');
}

// ── GENERATE MANAGER INVOICE ──────────────────────────────────────────────────
elseif ($action === 'generate_invoice') {
    $id = (int)($_GET['id'] ?? 0);
    $manager = Database::fetchOne(
        "SELECT u.*, am.id as mgr_id, am.commission_rate, am.balance
         FROM users u
         JOIN affiliate_managers am ON am.user_id=u.id
         WHERE am.id=?",
        [$id]
    );
    if (!$manager) Helpers::redirect('/admin/affiliate-managers');

    $mgrBalance = ManagerCommissionService::getManagerBalance((int)$manager['mgr_id']);
    $errors     = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $amount      = round((float)Helpers::postRaw('amount'), 4);
        $periodStart = Helpers::post('period_start') ?: date('Y-m-01');
        $periodEnd   = Helpers::post('period_end')   ?: date('Y-m-d');
        $dueDate     = Helpers::post('due_date')     ?: null;
        $notes       = trim(Helpers::post('notes')   ?? '');

        if ($amount <= 0)                                $errors[] = 'Amount must be greater than zero.';
        if ($amount > $mgrBalance['balance'] + 0.0001)  $errors[] = 'Amount exceeds available commission balance ($' . number_format($mgrBalance['balance'], 2) . ').';
        if (!$periodStart || !$periodEnd)                $errors[] = 'Period start and end dates are required.';
        if ($periodEnd < $periodStart)                   $errors[] = 'Period end must be on or after period start.';

        if (!$errors) {
            $balanceBefore = (float)$mgrBalance['balance'];
            $balanceAfter  = max(0.0, round($balanceBefore - $amount, 4));

            $lineNotes = "Commission Balance Before Payment: $" . number_format($balanceBefore, 2)
                       . "\nPayment Amount: $"                  . number_format($amount, 2)
                       . "\nCommission Balance After Payment: $" . number_format($balanceAfter, 2);
            if ($notes) $lineNotes .= "\n\n" . $notes;

            $items = [[
                'description' => 'Commission Earnings — ' . date('M j, Y', strtotime($periodStart)) . ' to ' . date('M j, Y', strtotime($periodEnd)),
                'qty'    => 1,
                'rate'   => $amount,
                'amount' => $amount,
            ]];

            $invNum = 'MGR-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            Database::begin();
            try {
                // Atomically deduct from balance — only succeeds if balance is sufficient.
                $upd = Database::query(
                    "UPDATE affiliate_managers
                     SET balance = GREATEST(0, balance - ?)
                     WHERE id = ? AND balance >= ?",
                    [$amount, (int)$manager['mgr_id'], $amount]
                );
                if ($upd->rowCount() === 0) {
                    throw new \RuntimeException('Balance insufficient or changed — please refresh and try again.');
                }

                $newId = Database::insert('invoices', [
                    'invoice_number' => $invNum,
                    'type'           => 'manager_fee',
                    'manager_id'     => (int)$manager['mgr_id'],
                    'affiliate_id'   => null,
                    'advertiser_id'  => null,
                    'period_start'   => $periodStart,
                    'period_end'     => $periodEnd,
                    'items'          => json_encode($items),
                    'subtotal'       => $amount,
                    'tax_rate'       => 0,
                    'tax_amount'     => 0,
                    'total'          => $amount,
                    'status'         => 'sent',
                    'notes'          => $lineNotes,
                    'due_date'       => $dueDate,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'created_by'     => Auth::id(),
                ]);

                // Mark commissions in the period as paid
                ManagerCommissionService::markPaidForPeriod(
                    (int)$manager['mgr_id'],
                    $periodStart,
                    $periodEnd
                );

                Database::commit();

                Helpers::flash('success', "Invoice {$invNum} generated. $" . number_format($amount, 2) . " deducted from balance.");
                Helpers::redirect('/admin/affiliate-managers/' . $id);
            } catch (\Throwable $e) {
                Database::rollback();
                $errors[] = $e->getMessage();
            }
        }
    }

    require BASE_PATH . '/views/admin/affiliate_managers/generate_invoice.php';
}

// ── COMMISSION REPORT ─────────────────────────────────────────────────────────
elseif ($action === 'commission_report') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        $manager = Database::fetchOne(
            "SELECT u.*, am.id as mgr_id, am.commission_rate, am.balance
             FROM users u
             JOIN affiliate_managers am ON am.user_id=u.id
             WHERE am.id=?",
            [$id]
        );
        if (!$manager) Helpers::redirect('/admin/affiliate-managers');

        // ── Per-record approve / delete ───────────────────────────────────────
        if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
            $crAction = Helpers::post('cr_action');
            $mcId     = (int)Helpers::postRaw('mc_id');

            if ($crAction === 'approve' && $mcId) {
                Database::query(
                    "UPDATE manager_commissions SET status='approved' WHERE id=? AND manager_id=? AND status='pending'",
                    [$mcId, (int)$manager['mgr_id']]
                );
                Helpers::flash('success', 'Commission approved.');

            } elseif ($crAction === 'delete' && $mcId) {
                $mc = Database::fetchOne(
                    "SELECT manager_id, commission_amount, status FROM manager_commissions WHERE id=? AND manager_id=?",
                    [$mcId, (int)$manager['mgr_id']]
                );
                if ($mc && in_array($mc['status'], ['pending', 'approved'])) {
                    Database::query("DELETE FROM manager_commissions WHERE id=?", [$mcId]);
                    Database::query(
                        "UPDATE affiliate_managers SET balance = GREATEST(0, balance - ?) WHERE id=?",
                        [(float)$mc['commission_amount'], (int)$mc['manager_id']]
                    );
                    Helpers::flash('success', 'Commission record deleted and balance adjusted.');
                } elseif ($mc) {
                    // Already reversed/paid — just delete the record
                    Database::query("DELETE FROM manager_commissions WHERE id=?", [$mcId]);
                    Helpers::flash('success', 'Commission record deleted.');
                }
            }

            $from = Helpers::get('from') ?: Helpers::post('from') ?: date('Y-m-d', strtotime('-29 days'));
            $to   = Helpers::get('to')   ?: Helpers::post('to')   ?: date('Y-m-d');
            Helpers::redirect('/admin/affiliate-managers?action=commission_report&id=' . $id . '&from=' . $from . '&to=' . $to);
        }

        $from = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
        $to   = Helpers::get('to')   ?: date('Y-m-d');

        $commissionSummary = ManagerCommissionService::getSummaryForManager((int)$manager['mgr_id']);
        $mgrBalance        = ManagerCommissionService::getManagerBalance((int)$manager['mgr_id']);
        $commissionRecords = ManagerCommissionService::getRecordsForManager((int)$manager['mgr_id'], $from, $to);
        $pageTitle         = 'Commission Report — ' . $manager['first_name'] . ' ' . $manager['last_name'];

        require BASE_PATH . '/views/admin/affiliate_managers/commission_report.php';
    } else {
        $allManagersSummary = ManagerCommissionService::getAllManagersSummary();
        $pageTitle          = 'Commission Overview — All Managers';
        require BASE_PATH . '/views/admin/affiliate_managers/commission_overview.php';
    }
}

// ── MANAGER INVOICE REQUESTS ──────────────────────────────────────────────────
elseif ($action === 'invoice_requests') {
    // Ensure the table exists (self-healing).
    try {
        Database::query("CREATE TABLE IF NOT EXISTS `manager_invoice_requests` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `manager_id`   INT UNSIGNED NOT NULL,
            `affiliate_id` INT UNSIGNED NULL DEFAULT NULL,
            `amount`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
            `period_start` DATE NOT NULL,
            `period_end`   DATE NOT NULL,
            `notes`        TEXT NULL,
            `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `admin_note`   TEXT NULL,
            `reviewed_by`  INT UNSIGNED NULL,
            `reviewed_at`  DATETIME NULL,
            `invoice_id`   INT UNSIGNED NULL,
            `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_manager_id` (`manager_id`),
            INDEX `idx_status`     (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (\Throwable $_e) {}
    // Self-healing: add affiliate_id if missing (migration 0015).
    try { Database::query("ALTER TABLE `manager_invoice_requests` ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL AFTER `manager_id`"); } catch (\Throwable $_e) {}

    $pageTitle = 'Manager Invoice Requests';

    // ── POST: approve or reject ───────────────────────────────────────────────
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $reqId  = (int)Helpers::postRaw('id');
        $verb   = Helpers::post('action');   // 'approve' or 'reject'
        $note   = trim((string)(Helpers::postRaw('admin_note') ?? ''));

        $req = Database::fetchOne(
            "SELECT mir.*, am.id AS mgr_id, am.balance AS mgr_balance,
                    u.first_name, u.last_name, u.email
             FROM manager_invoice_requests mir
             JOIN affiliate_managers am ON am.id = mir.manager_id
             JOIN users u ON u.id = am.user_id
             WHERE mir.id = ? AND mir.status = 'pending'",
            [$reqId]
        );

        if (!$req) {
            Helpers::flash('error', 'Request not found or already reviewed.');
            Helpers::redirect('/admin/affiliate-managers/invoice-requests');
        }

        if ($verb === 'approve') {
            $amount      = round((float)Helpers::postRaw('amount'), 4);
            $periodStart = Helpers::post('period_start') ?: $req['period_start'];
            $periodEnd   = Helpers::post('period_end')   ?: $req['period_end'];
            $dueDate     = Helpers::post('due_date')     ?: null;
            $mgrId       = (int)$req['mgr_id'];
            $affiliateId = (int)($req['affiliate_id'] ?? 0);

            // ── Affiliate invoice request ─────────────────────────────────────
            // When the request is tied to a specific affiliate, generate an
            // affiliate_payout invoice and deduct from the affiliate's balance.
            if ($affiliateId > 0) {
                $affRow = Database::fetchOne(
                    "SELECT af.id, af.balance, af.payment_method, af.payment_details,
                            af.affiliate_code, u.email, u.first_name, u.last_name
                     FROM affiliates af JOIN users u ON u.id = af.user_id
                     WHERE af.id = ?",
                    [$affiliateId]
                );

                if (!$affRow) {
                    Helpers::flash('error', 'Affiliate not found.');
                    Helpers::redirect('/admin/affiliate-managers/invoice-requests');
                }

                $affBalance = (float)$affRow['balance'];

                if ($amount <= 0 || $amount > $affBalance + 0.0001) {
                    Helpers::flash('error', sprintf(
                        'Amount ($%s) is invalid or exceeds the affiliate\'s available balance ($%s).',
                        number_format($amount, 2),
                        number_format($affBalance, 2)
                    ));
                    Helpers::redirect('/admin/affiliate-managers/invoice-requests');
                }

                $balanceBefore = $affBalance;
                $balanceAfter  = max(0.0, round($balanceBefore - $amount, 4));

                $lineNotes = "Affiliate Balance Before: $" . number_format($balanceBefore, 2)
                           . "\nPayout Amount: $"          . number_format($amount, 2)
                           . "\nAffiliate Balance After: $" . number_format($balanceAfter, 2);
                if ($note) $lineNotes .= "\n\nAdmin Note: " . $note;

                $items = [[
                    'description' => 'Affiliate Payout — ' . date('M j, Y', strtotime($periodStart)) . ' to ' . date('M j, Y', strtotime($periodEnd)),
                    'qty'    => 1,
                    'rate'   => $amount,
                    'amount' => $amount,
                ]];

                $invNum     = 'INV-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $entityName  = trim($affRow['first_name'] . ' ' . $affRow['last_name']);
                $entityEmail = $affRow['email'] ?? '';

                Database::begin();
                try {
                    // Atomically deduct from affiliate balance.
                    $upd = Database::query(
                        "UPDATE affiliates SET balance = GREATEST(0, balance - ?) WHERE id = ? AND balance >= ?",
                        [$amount, $affiliateId, $amount]
                    );
                    if ($upd->rowCount() === 0) {
                        throw new \RuntimeException('Affiliate balance insufficient or changed — please refresh.');
                    }

                    $invId = Database::insert('invoices', [
                        'invoice_number'  => $invNum,
                        'type'            => 'affiliate_payout',
                        'affiliate_id'    => $affiliateId,
                        'manager_id'      => null,
                        'advertiser_id'   => null,
                        'entity_name'     => $entityName,
                        'entity_email'    => $entityEmail,
                        'period_start'    => $periodStart,
                        'period_end'      => $periodEnd,
                        'items'           => json_encode($items),
                        'subtotal'        => $amount,
                        'tax_rate'        => 0,
                        'tax_amount'      => 0,
                        'total'           => $amount,
                        'status'          => 'sent',
                        'notes'           => $lineNotes,
                        'due_date'        => $dueDate,
                        'balance_before'  => $balanceBefore,
                        'balance_after'   => $balanceAfter,
                        'balance_deducted'=> 1,
                        'created_by'      => Auth::id(),
                    ]);

                    // Update the request record
                    Database::update('manager_invoice_requests', [
                        'status'      => 'approved',
                        'admin_note'  => $note ?: null,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'invoice_id'  => $invId,
                    ], 'id=?', [$reqId]);

                    // Notify the manager
                    $mgrUserId = Database::fetchOne("SELECT user_id FROM affiliate_managers WHERE id=?", [$mgrId])['user_id'] ?? null;
                    if ($mgrUserId) {
                        Database::insert('notifications', [
                            'user_id'     => (int)$mgrUserId,
                            'target_role' => null,
                            'type'        => 'success',
                            'title'       => 'Invoice Request Approved',
                            'message'     => 'Your invoice request of $' . number_format($amount, 2) . ' for affiliate ' . $affRow['affiliate_code'] . ' has been approved. Invoice ' . $invNum . ' is now available.',
                            'link'        => '/affiliate_manager/invoices',
                            'is_read'     => 0,
                        ]);
                    }

                    // Notify the affiliate
                    $affUserId = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affiliateId])['user_id'] ?? null;
                    if ($affUserId) {
                        Database::insert('notifications', [
                            'user_id'     => (int)$affUserId,
                            'target_role' => null,
                            'type'        => 'info',
                            'title'       => 'Invoice Generated',
                            'message'     => 'An invoice of $' . number_format($amount, 2) . ' has been generated and your balance has been updated. Invoice: ' . $invNum . '.',
                            'link'        => '/affiliate/invoices',
                            'is_read'     => 0,
                        ]);
                    }

                    // Generate PDF & email the affiliate
                    try {
                        $invRow = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$invId]);
                        if ($invRow) {
                            $pdfPath = InvoicePDF::generate($invRow, $items, $entityName, $entityEmail, $affRow['payment_method'] ?? '', $affRow['payment_details'] ?? '');
                            if ($entityEmail) {
                                $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
                                $appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
                                Mailer::sendEventWithPdf(
                                    $entityEmail, $entityName, 'invoice_created',
                                    [
                                        'name'           => $entityName,
                                        'email'          => $entityEmail,
                                        'site_name'      => $appName,
                                        'app_url'        => $appUrl,
                                        'invoice_number' => $invNum,
                                        'total'          => '$' . number_format($amount, 2),
                                        'due_date'       => $dueDate ?: 'N/A',
                                        'period_start'   => date('M j, Y', strtotime($periodStart)),
                                        'period_end'     => date('M j, Y', strtotime($periodEnd)),
                                    ],
                                    $pdfPath, $invNum . '.pdf'
                                );
                            }
                        }
                    } catch (\Throwable $_pe) {}

                    Database::commit();
                    Helpers::flash('success', "Invoice {$invNum} generated for affiliate {$affRow['affiliate_code']}. \${$amount} deducted from affiliate balance.");
                } catch (\Throwable $e) {
                    Database::rollback();
                    Helpers::flash('error', 'Failed to approve: ' . $e->getMessage());
                }

            } else {
            // ── Manager (commission) invoice request ──────────────────────────
            // No affiliate — standard flow: manager_fee invoice, deduct manager balance.

            if ($amount <= 0 || $amount > (float)$req['mgr_balance'] + 0.0001) {
                Helpers::flash('error', 'Amount is invalid or exceeds available balance.');
                Helpers::redirect('/admin/affiliate-managers/invoice-requests');
            }

            $balanceBefore = (float)$req['mgr_balance'];
            $balanceAfter  = max(0.0, round($balanceBefore - $amount, 4));

            $lineNotes = "Commission Balance Before: $" . number_format($balanceBefore, 2)
                       . "\nPayment Amount: $"           . number_format($amount, 2)
                       . "\nCommission Balance After: $" . number_format($balanceAfter, 2);
            if ($note) $lineNotes .= "\n\n" . $note;

            $items = [[
                'description' => 'Commission Earnings — ' . date('M j, Y', strtotime($periodStart)) . ' to ' . date('M j, Y', strtotime($periodEnd)),
                'qty'    => 1,
                'rate'   => $amount,
                'amount' => $amount,
            ]];

            $invNum = 'MGR-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            Database::begin();
            try {
                $upd = Database::query(
                    "UPDATE affiliate_managers SET balance = GREATEST(0, balance - ?) WHERE id = ? AND balance >= ?",
                    [$amount, $mgrId, $amount]
                );
                if ($upd->rowCount() === 0) {
                    throw new \RuntimeException('Balance insufficient or changed — please refresh.');
                }

                $invId = Database::insert('invoices', [
                    'invoice_number' => $invNum,
                    'type'           => 'manager_fee',
                    'manager_id'     => $mgrId,
                    'affiliate_id'   => null,
                    'advertiser_id'  => null,
                    'period_start'   => $periodStart,
                    'period_end'     => $periodEnd,
                    'items'          => json_encode($items),
                    'subtotal'       => $amount,
                    'tax_rate'       => 0,
                    'tax_amount'     => 0,
                    'total'          => $amount,
                    'status'         => 'sent',
                    'notes'          => $lineNotes,
                    'due_date'       => $dueDate,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'created_by'     => Auth::id(),
                ]);

                // Mark commissions in period as paid
                ManagerCommissionService::markPaidForPeriod($mgrId, $periodStart, $periodEnd);

                // Update the request record
                Database::update('manager_invoice_requests', [
                    'status'      => 'approved',
                    'admin_note'  => $note ?: null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'invoice_id'  => $invId,
                ], 'id=?', [$reqId]);

                // Notify the manager
                $mgrUserId = Database::fetchOne("SELECT user_id FROM affiliate_managers WHERE id=?", [$mgrId])['user_id'] ?? null;
                if ($mgrUserId) {
                    Database::insert('notifications', [
                        'user_id'     => (int)$mgrUserId,
                        'target_role' => null,
                        'type'        => 'success',
                        'title'       => 'Invoice Request Approved',
                        'message'     => 'Your invoice request of $' . number_format($amount, 2) . ' has been approved. Invoice ' . $invNum . ' is now available.',
                        'link'        => '/affiliate_manager/invoices?tab=my_invoices',
                        'is_read'     => 0,
                    ]);
                }

                Database::commit();
                Helpers::flash('success', "Invoice {$invNum} generated and $" . number_format($amount, 2) . " deducted from manager balance.");
            } catch (\Throwable $e) {
                Database::rollback();
                Helpers::flash('error', 'Failed to approve: ' . $e->getMessage());
            }
            } // end else (manager invoice)

        } elseif ($verb === 'reject') {
            Database::update('manager_invoice_requests', [
                'status'      => 'rejected',
                'admin_note'  => $note ?: null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ], 'id=?', [$reqId]);

            // Notify the manager
            $mgrUserId = Database::fetchOne("SELECT user_id FROM affiliate_managers WHERE id=?", [(int)$req['mgr_id']])['user_id'] ?? null;
            if ($mgrUserId) {
                Database::insert('notifications', [
                    'user_id'     => (int)$mgrUserId,
                    'target_role' => null,
                    'type'        => 'warning',
                    'title'       => 'Invoice Request Rejected',
                    'message'     => 'Your invoice request of $' . number_format((float)$req['amount'], 2) . ' was rejected.' . ($note ? ' Reason: ' . $note : ''),
                    'link'        => '/affiliate_manager/invoices?action=request_invoice',
                    'is_read'     => 0,
                ]);
            }

            Helpers::flash('success', 'Invoice request rejected.');
        }

        Helpers::redirect('/admin/affiliate-managers/invoice-requests');
    }

    // ── LIST ──────────────────────────────────────────────────────────────────
    $status       = Helpers::get('status') ?: 'pending';
    $pendingCount = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM manager_invoice_requests WHERE status='pending'")['c'] ?? 0);

    $where  = "1=1";
    $params = [];
    if (in_array($status, ['pending','approved','rejected'], true)) {
        $where = "mir.status = ?";
        $params[] = $status;
    } else {
        $status = 'all';
    }

    $requests = Database::fetchAll(
        "SELECT mir.*,
                u.first_name, u.last_name, u.email,
                am.balance AS mgr_balance,
                af.affiliate_code,
                af.balance AS aff_balance,
                CONCAT(au.first_name,' ',au.last_name) AS aff_name,
                au.email AS aff_email
         FROM manager_invoice_requests mir
         JOIN affiliate_managers am ON am.id = mir.manager_id
         JOIN users u ON u.id = am.user_id
         LEFT JOIN affiliates af ON af.id = mir.affiliate_id
         LEFT JOIN users au ON au.id = af.user_id
         WHERE $where
         ORDER BY mir.id DESC
         LIMIT 500",
        $params
    );

    require BASE_PATH . '/views/admin/affiliate_managers/invoice_requests.php';
}
