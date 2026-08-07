<?php
Auth::check('affiliate_manager');
ManagerPermissions::requirePermission('view_affiliates');   // admin-controlled gate
if (!Auth::hasPermission('view_affiliates')) {
    Helpers::flash('error', 'You do not have permission to view affiliates.');
    Helpers::redirect('/affiliate_manager/dashboard');
}
$pageTitle = 'My Affiliates';

$mgrUserId = Auth::id();
// affiliates.manager_id stores affiliate_managers.id (the am table PK), not users.id
$mgrRow    = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [$mgrUserId]);
$mgrAffMgrId = $mgrRow ? (int)$mgrRow['id'] : null;
$affIds = Auth::managerAffiliateIds();

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'view' : 'index');

// ── Create Affiliate ───────────────────────────────────────────────────────
if ($action === 'create') {
    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = Helpers::post('first_name');
        $lname   = Helpers::post('last_name');
        $email   = strtolower(trim(Helpers::postRaw('email')));
        $pass    = Helpers::postRaw('password');
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = Helpers::post('country');
        $status  = in_array(Helpers::post('status'), ['active','pending']) ? Helpers::post('status') : 'active';

        if (!$fname || !$lname) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
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
                    'role'          => 'affiliate',
                    'status'        => $status,
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                    'company'       => $company,
                    'phone'         => $phone,
                    'country'       => strtoupper(substr($country ?: 'US', 0, 2)),
                ]);
                $affCode = 'AFF' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                Database::insert('affiliates', [
                    'user_id'              => $userId,
                    'affiliate_code'       => $affCode,
                    'registration_answers' => '{}',
                    'manager_id'           => $mgrAffMgrId, // store affiliate_managers.id
                ]);
                Database::commit();

                Mailer::sendEvent($email, "$fname $lname", $status === 'active' ? 'affiliate_approved' : 'affiliate_created', [
                    'name'           => "$fname $lname",
                    'email'          => $email,
                    'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                    'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
                    'affiliate_code' => $affCode,
                ]);

                Helpers::flash('success', "Affiliate account created for $fname $lname and assigned to you.");
                Helpers::redirect('/affiliate_manager/affiliates');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to create account. Please try again.';
            }
        }
    }
    require BASE_PATH . '/views/affiliate_manager/affiliate_create.php';
}

// ── View Affiliate ─────────────────────────────────────────────────────────
elseif ($action === 'view') {
    if (!Auth::hasPermission('view_affiliates')) {
        Helpers::flash('error', 'No permission to view affiliates.');
        Helpers::redirect('/affiliate_manager/affiliates');
    }
    $affId = (int)$_GET['id'];
    if (!in_array($affId, $affIds)) {
        Helpers::flash('error', 'Affiliate not found or not assigned to you.');
        Helpers::redirect('/affiliate_manager/affiliates');
    }
    $affiliate = Database::fetchOne(
        "SELECT u.*, af.id as aff_id, af.affiliate_code, af.balance, af.fraud_score, af.payment_method, af.payment_threshold
         FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?",
        [$affId]
    );
    if (!$affiliate) Helpers::redirect('/affiliate_manager/affiliates');

    $stats = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(approved) as approved, SUM(payout) as payout
         FROM stats_daily WHERE affiliate_id=?",
        [$affId]
    );

    if (Helpers::isPost() && Helpers::post('action') === 'update_payout') {
        ManagerPermissions::requirePermission('edit_affiliate_payouts');
        if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
            Helpers::flash('error', 'Invalid token.');
        } else {
            $offerId = (int)Helpers::post('offer_id');
            $payout  = (float)Helpers::post('payout');
            
            $ao = Database::fetchOne("SELECT id FROM affiliate_offers WHERE affiliate_id=? AND offer_id=? AND status='approved'", [$affId, $offerId]);
            if ($ao) {
                Database::query("UPDATE affiliate_offers SET custom_payout=? WHERE id=?", [$payout, $ao['id']]);
                $exists = Database::fetchOne("SELECT id FROM aff_custom_payouts WHERE affiliate_id=? AND offer_id=?", [$affId, $offerId]);
                if ($exists) {
                    Database::query("UPDATE aff_custom_payouts SET payout=? WHERE id=?", [$payout, $exists['id']]);
                } else {
                    $offerData = Database::fetchOne("SELECT revenue_amount FROM offers WHERE id=?", [$offerId]);
                    $revenue = (float)($offerData['revenue_amount'] ?? 0);
                    Database::insert('aff_custom_payouts', [
                        'affiliate_id' => $affId,
                        'offer_id'     => $offerId,
                        'revenue'      => $revenue,
                        'payout'       => $payout
                    ]);
                }
                Helpers::flash('success', 'Custom payout updated successfully.');
            } else {
                Helpers::flash('error', 'Offer not approved or invalid.');
            }
        }
        Helpers::redirect("/affiliate_manager/affiliates?action=view&id={$affId}");
    }

    $approvedOffers = Database::fetchAll(
        "SELECT o.id, o.name, o.payout_amount as default_payout, o.payout_type, ao.custom_payout
         FROM affiliate_offers ao
         JOIN offers o ON ao.offer_id = o.id
         WHERE ao.affiliate_id=? AND ao.status='approved'
         ORDER BY o.name ASC",
        [$affId]
    );
    require BASE_PATH . '/views/affiliate_manager/affiliate_view.php';
}

// ── Edit Affiliate ─────────────────────────────────────────────────────────
elseif ($action === 'edit') {
    if (!Auth::hasPermission('view_affiliates')) {
        Helpers::flash('error', 'No permission to edit affiliates.');
        Helpers::redirect('/affiliate_manager/affiliates');
    }
    $affId = (int)$_GET['id'];
    if (!in_array($affId, $affIds)) {
        Helpers::flash('error', 'Affiliate not found or not assigned to you.');
        Helpers::redirect('/affiliate_manager/affiliates');
    }
    $affiliate = Database::fetchOne(
        "SELECT u.*, af.id as aff_id, af.affiliate_code, af.balance, af.payment_method, af.payment_threshold
         FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?",
        [$affId]
    );
    if (!$affiliate) Helpers::redirect('/affiliate_manager/affiliates');

    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = Helpers::post('first_name');
        $lname   = Helpers::post('last_name');
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = strtoupper(substr(Helpers::post('country') ?: 'US', 0, 2));
        $notes   = Helpers::post('notes');

        if (!$fname || !$lname) $errors[] = 'First and last name are required.';

        if (!$errors) {
            Database::update('users', [
                'first_name' => $fname,
                'last_name'  => $lname,
                'company'    => $company,
                'phone'      => $phone,
                'country'    => $country,
            ], 'id=?', [$affiliate['id']]);
            Helpers::flash('success', 'Affiliate profile updated.');
            Helpers::redirect('/affiliate_manager/affiliates?action=view&id=' . $affId);
        }
    }
    require BASE_PATH . '/views/affiliate_manager/affiliate_edit.php';
}

// ── Index: list all affiliates ─────────────────────────────────────────────
else {
    // --- REALTIME INACTIVITY SWEEP ---
    $inactivityEnabled = (Config::get('config', 'app.inactivity_enabled') ?? '0') === '1';
    if ($inactivityEnabled) {
        $inactivityDays = (int)(Config::get('config', 'app.inactivity_days') ?? 30);
        if ($inactivityDays > 0) {
            Database::query("
                UPDATE users 
                SET status='suspended', inactivity_deactivated_at=NOW()
                WHERE role='affiliate' 
                  AND status='active'
                  AND last_login IS NOT NULL
                  AND DATEDIFF(NOW(), last_login) >= ?
            ", [$inactivityDays]);
        }
    }
    // fraud_score computed live from each affiliate's checked conversions
    // (same source as the Affiliate Conversion Fraud Report).
    $affiliates = empty($affIds) ? [] : Database::fetchAll(
        "SELECT u.id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at,
                af.affiliate_code, af.balance, af.id as aff_id,
                ROUND(COALESCE(AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0
                                         THEN cv.fraud_score END), 0)) as fraud_score,
                SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as fraud_checked_count
         FROM users u
         JOIN affiliates af ON af.user_id=u.id
         LEFT JOIN conversions cv ON cv.affiliate_id = af.id AND COALESCE(cv.is_hidden,0)=0 AND (cv.hide_reason IS NULL OR cv.hide_reason NOT LIKE '%traffic_back%') AND NOT EXISTS (SELECT 1 FROM clicks _ck_tb WHERE _ck_tb.click_id = cv.click_id AND _ck_tb.source = 'traffic_back') AND NOT EXISTS (SELECT 1 FROM traffic_back_logs _tbl_tb WHERE _tbl_tb.click_id = cv.click_id)
         WHERE af.id IN (" . implode(',', array_fill(0, count($affIds), '?')) . ")
         GROUP BY u.id, u.email, u.first_name, u.last_name, u.company, u.status, u.created_at,
                  af.affiliate_code, af.balance, af.id
         ORDER BY u.created_at DESC",
        $affIds
    );

    // Handle approval if permission granted
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Auth::hasPermission('approve_affiliates')) {
        $targetId  = (int)Helpers::post('aff_id');
        $newStatus = Helpers::post('status');
        if (in_array($targetId, $affIds) && in_array($newStatus, ['active','suspended','rejected','pending'])) {
            $aff = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$targetId]);
            if ($aff) {
                if ($newStatus === 'active') {
                    Database::query(
                        "UPDATE users 
                         SET status='active', last_login=NOW(),
                             inactivity_warned_at=NULL, inactivity_deactivated_at=NULL
                         WHERE id=?",
                        [$aff['user_id']]
                    );
                } else {
                    Database::update('users', ['status' => $newStatus], 'id=?', [$aff['user_id']]);
                }
                Helpers::flash('success', 'Affiliate status updated.');
            }
        }
        Helpers::redirect('/affiliate_manager/affiliates');
    }

    require BASE_PATH . '/views/affiliate_manager/affiliates.php';
}
