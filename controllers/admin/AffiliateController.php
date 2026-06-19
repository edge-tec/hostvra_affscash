<?php
Auth::check('admin');
$pageTitle = 'Affiliates';
try { Database::query("ALTER TABLE users MODIFY COLUMN status ENUM('active','pending','suspended','rejected','deleted') NOT NULL DEFAULT 'pending'"); } catch(\Throwable $_e) {}
// Approval Management: extend status ENUM with 'removed' so admin can revoke
// approval in a way that allows the affiliate to submit a fresh request
// (vs. 'rejected'/'blocked' which are permanent until admin override).
try { Database::query("ALTER TABLE affiliate_offers MODIFY COLUMN status ENUM('pending','approved','rejected','blocked','removed') NOT NULL DEFAULT 'pending'"); } catch(\Throwable $_e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'view' : 'index');

// ── Impersonate ────────────────────────────────────────────────────────────
if ($action === 'impersonate') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $user = Database::fetchOne("SELECT u.id FROM users u JOIN affiliates af ON af.user_id=u.id WHERE u.id=? AND u.role='affiliate'", [$userId]);
    if ($user) {
        $_SESSION['impersonate_return_url'] = $_SERVER['HTTP_REFERER'] ?? '/admin/affiliates';
        if (Auth::impersonate($userId)) {
            Helpers::redirect('/affiliate/dashboard');
        }
    }
    Helpers::flash('error', 'Could not switch to that account.');
    Helpers::redirect('/admin/affiliates');
}

// ── Create ─────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $managers = Database::fetchAll("SELECT am.id, am.user_id, CONCAT(u.first_name,' ',u.last_name) as label FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE u.status='active' ORDER BY u.first_name");
    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = Helpers::post('first_name');
        $lname   = Helpers::post('last_name');
        $email   = strtolower(trim(Helpers::postRaw('email')));
        $pass    = Helpers::postRaw('password');
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = Helpers::post('country');
        $status  = in_array(Helpers::post('status'), ['active','pending','suspended']) ? Helpers::post('status') : 'active';
        $managerId = (int)Helpers::postRaw('manager_id') ?: null;

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
                    'manager_id'           => $managerId,
                ]);
                Database::commit();

                if ($status === 'active') {
                    Mailer::sendEvent($email, "$fname $lname", 'affiliate_approved', [
                        'name'      => "$fname $lname",
                        'email'     => $email,
                        'site_name' => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'   => rtrim(Config::get('config','app.url') ?? '', '/'),
                    ]);
                } else {
                    Mailer::sendEvent($email, "$fname $lname", 'affiliate_created', [
                        'name'           => "$fname $lname",
                        'email'          => $email,
                        'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
                        'affiliate_code' => $affCode,
                    ]);
                }

                Helpers::flash('success', "Affiliate account created for $fname $lname.");
                Helpers::redirect('/admin/affiliates');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to create account. Please try again.';
            }
        }
    }
    require BASE_PATH . '/views/admin/affiliates/create.php';
}

// ── Index ──────────────────────────────────────────────────────────────────
elseif ($action === 'index') {
    $status = Helpers::get('status') ?: 'all';
    $where  = $status !== 'all' ? "u.status=? AND u.status!='deleted'" : "u.status!='deleted'";
    $params = $status !== 'all' ? [$status] : [];
    // fraud_score is computed live from each affiliate's checked conversions
    // (same source as the Affiliate Conversion Fraud Report), so the badge
    // always reflects current fraud history rather than a stale stored value.
    // Idempotent migrations so the cron columns are queryable even before
    // the cron has run once on a fresh install.
    try { Database::query("ALTER TABLE users ADD COLUMN inactivity_warned_at      DATETIME NULL"); } catch (\Throwable $_) {}
    try { Database::query("ALTER TABLE users ADD COLUMN inactivity_deactivated_at DATETIME NULL"); } catch (\Throwable $_) {}

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

    $affiliates = Database::fetchAll(
        "SELECT u.id as user_id,u.email,u.first_name,u.last_name,u.company,u.phone,u.country,u.status,u.created_at,
                u.last_login, u.inactivity_deactivated_at,
                CASE WHEN u.last_login IS NULL THEN NULL ELSE DATEDIFF(NOW(), u.last_login) END AS days_inactive,
                af.affiliate_code,af.balance,af.payment_method,af.payment_threshold,af.id as aff_id,
                ROUND(COALESCE(AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0
                                         THEN cv.fraud_score END), 0)) as fraud_score,
                SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as fraud_checked_count
         FROM users u
         JOIN affiliates af ON af.user_id=u.id
         LEFT JOIN conversions cv ON cv.affiliate_id = af.id AND COALESCE(cv.is_hidden,0)=0
         WHERE u.role='affiliate' AND $where
         GROUP BY u.id,u.email,u.first_name,u.last_name,u.company,u.phone,u.country,u.status,u.created_at,
                  u.last_login, u.inactivity_deactivated_at,
                  af.affiliate_code,af.balance,af.payment_method,af.payment_threshold,af.id
         ORDER BY u.created_at DESC",
        $params
    );
    // Surface the configured period to the view so it can colour-code "Days Inactive".
    $inactivityCfgDays = (int)(Config::get('config', 'app.inactivity_days') ?? 30);

    if (Helpers::get('export') === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="affiliates_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['ID','Code','First Name','Last Name','Email','Company','Phone','Country','Status','Balance','Payment Method','Payment Threshold','Fraud Score','Joined']);
        foreach ($affiliates as $a) {
            fputcsv($f, [$a['aff_id'],$a['affiliate_code'],$a['first_name'],$a['last_name'],$a['email'],$a['company'],$a['phone'],$a['country'],$a['status'],$a['balance'],$a['payment_method'],$a['payment_threshold'],$a['fraud_score'],$a['created_at']]);
        }
        fclose($f);
        exit;
    }

    require BASE_PATH . '/views/admin/affiliates/index.php';
}

// ── Edit ───────────────────────────────────────────────────────────────────
elseif ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $affiliate = Database::fetchOne(
        "SELECT u.*,af.* FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=? AND u.status!='deleted'", [$id]
    );
    if (!$affiliate) { Helpers::redirect('/admin/affiliates'); }

    $managers = Database::fetchAll("SELECT am.id, am.user_id, CONCAT(u.first_name,' ',u.last_name) as label FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE u.status='active' ORDER BY u.first_name");
    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = trim(Helpers::post('first_name'));
        $lname   = trim(Helpers::post('last_name'));
        $email   = strtolower(trim(Helpers::postRaw('email')));
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = strtoupper(substr(Helpers::post('country') ?: 'US', 0, 2));
        $status  = in_array(Helpers::post('status'), ['active','pending','suspended','rejected']) ? Helpers::post('status') : $affiliate['status'];
        $mgrId   = Helpers::postRaw('manager_id') !== '' ? ((int)Helpers::postRaw('manager_id') ?: null) : null;
        $payMethod       = Helpers::post('payment_method');
        $payThreshold    = (float)Helpers::postRaw('payment_threshold');
        $payDetails      = Helpers::postRaw('payment_details');
        $payTerms        = in_array(Helpers::post('payment_terms'), ['weekly','net15','net30','monthly']) ? Helpers::post('payment_terms') : 'monthly';
        $allowEmailChange = isset($_POST['allow_email_change']) ? 1 : 0;
        $newPass         = Helpers::postRaw('new_password');
        $telegram        = trim(Helpers::post('telegram') ?? '') ?: null;
        $skype           = trim(Helpers::post('skype')    ?? '') ?: null;
        $discord         = trim(Helpers::post('discord')  ?? '') ?: null;

        if (!$fname || !$lname) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if ($newPass && strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';

        // Check email uniqueness (exclude current user)
        if (!$errors) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$email, $affiliate['user_id']]);
            if ($existing) $errors[] = 'Email already in use by another account.';
        }

        if (!$errors) {
            $userUpdate = [
                'first_name' => $fname,
                'last_name'  => $lname,
                'email'      => $email,
                'company'    => $company,
                'phone'      => $phone,
                'country'    => $country,
                'status'     => $status,
                'telegram'   => $telegram,
                'skype'      => $skype,
                'discord'    => $discord,
            ];
            if ($newPass) {
                $userUpdate['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            }
            Database::update('users', $userUpdate, 'id=?', [$affiliate['user_id']]);
            // Ensure columns exist before updating
            try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS payment_details TEXT DEFAULT NULL"); } catch(\Throwable $e) {}
            try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS payment_terms VARCHAR(20) NOT NULL DEFAULT 'monthly'"); } catch(\Throwable $e) {}
            try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS allow_email_change TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}
            Database::update('affiliates', [
                'manager_id'        => $mgrId,
                'payment_method'    => $payMethod,
                'payment_threshold' => $payThreshold,
                'payment_details'   => $payDetails,
                'payment_terms'     => $payTerms,
                'allow_email_change'=> $allowEmailChange,
            ], 'id=?', [$id]);

            // Notify if status changed
            if ($status !== $affiliate['status']) {
                $emailEvent = match($status) {
                    'active'    => 'affiliate_approved',
                    'rejected'  => 'affiliate_rejected',
                    'suspended' => 'affiliate_suspended',
                    default     => null,
                };
                if ($emailEvent) {
                    Mailer::sendEvent($email, "$fname $lname", $emailEvent, [
                        'name'      => "$fname $lname",
                        'email'     => $email,
                        'site_name' => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'   => rtrim(Config::get('config','app.url') ?? '', '/'),
                    ]);
                }
            }

            Helpers::flash('success', 'Affiliate details updated successfully.');
            Helpers::redirect('/admin/affiliates/' . $id);
        }
    }
    require BASE_PATH . '/views/admin/affiliates/edit.php';
}

// ── Reactivate (clears auto-deactivation) ─────────────────────────────────
// Sets users.status back to 'active' and wipes the inactivity stamps so the
// next cron sweep does not immediately deactivate the account again. Only
// runs on POST + valid CSRF to prevent CSRF-driven mass reactivations.
elseif ($action === 'reactivate') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id  = (int)Helpers::postRaw('affiliate_id');
        $aff = Database::fetchOne(
            "SELECT u.id AS user_id, u.email, u.status, u.first_name, u.last_name
             FROM users u JOIN affiliates af ON af.user_id=u.id
             WHERE af.id=? AND u.role='affiliate' AND u.status!='deleted'",
            [$id]
        );
        if (!$aff) {
            Helpers::flash('error', 'Affiliate not found.');
        } elseif ($aff['status'] === 'active') {
            Helpers::flash('info', 'Affiliate is already active.');
        } else {
            // Ensure columns exist on fresh installs that haven't run the cron yet.
            try { Database::query("ALTER TABLE users ADD COLUMN inactivity_warned_at      DATETIME NULL"); } catch (\Throwable $_) {}
            try { Database::query("ALTER TABLE users ADD COLUMN inactivity_deactivated_at DATETIME NULL"); } catch (\Throwable $_) {}

            Database::query(
                "UPDATE users
                 SET status='active', last_login=NOW(),
                     inactivity_warned_at=NULL, inactivity_deactivated_at=NULL
                 WHERE id=?",
                [$aff['user_id']]
            );
            Helpers::flash('success', 'Affiliate account reactivated. Inactivity timer has been reset.');
        }
    }
    Helpers::redirect('/admin/affiliates');
}

// ── Delete ─────────────────────────────────────────────────────────────────
elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)Helpers::postRaw('affiliate_id');
        $aff = Database::fetchOne("SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$id]);
        if ($aff) {
            // Soft delete — mark user as deleted, deactivate affiliate offers
            Database::update('users', ['status' => 'deleted'], 'id=?', [$aff['user_id']]);
            Database::query("UPDATE affiliate_offers SET status='blocked' WHERE affiliate_id=?", [$id]);
            Database::query("DELETE FROM user_active_sessions WHERE user_id=?", [$aff['user_id']]);
            Helpers::flash('success', 'Affiliate account has been deleted.');
        } else {
            Helpers::flash('error', 'Affiliate not found.');
        }
    }
    Helpers::redirect('/admin/affiliates');
}

// ── Block Offer ────────────────────────────────────────────────────────────
elseif ($action === 'block_offer') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $offerId = (int)Helpers::postRaw('offer_id');

        $ao = Database::fetchOne("SELECT ao.*, o.name as offer_name FROM affiliate_offers ao JOIN offers o ON o.id=ao.offer_id WHERE ao.affiliate_id=? AND ao.offer_id=?", [$affId, $offerId]);
        if ($ao) {
            Database::update('affiliate_offers', ['status' => 'blocked'], 'affiliate_id=? AND offer_id=?', [$affId, $offerId]);

            // Email notification
            $affUser = Database::fetchOne("SELECT u.email, u.first_name, u.last_name FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$affId]);
            if ($affUser) {
                Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_blocked', [
                    'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                    'email'      => $affUser['email'],
                    'offer_name' => $ao['offer_name'],
                    'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                    'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                ]);
            }

            // In-app notification
            if ($affUser) {
                $userId = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
                if ($userId && $userId['user_id']) {
                    Database::insert('notifications', [
                        'user_id' => $userId['user_id'],
                        'type'    => 'warning',
                        'title'   => 'Offer Access Blocked',
                        'message' => 'Your access to offer "' . $ao['offer_name'] . '" has been blocked.',
                        'link'    => '/affiliate/offers',
                    ]);
                    try {
                        require_once BASE_PATH . '/core/FirebaseMessaging.php';
                        FirebaseMessaging::sendToUser($userId['user_id'], 'Offer Access Blocked', 'Your access to offer "' . $ao['offer_name'] . '" has been blocked.', ['type' => 'offer']);
                    } catch (\Throwable $e) {}
                }
            }

            Helpers::flash('success', 'Offer access blocked and affiliate notified.');
        } else {
            Helpers::flash('error', 'Offer assignment not found.');
        }
    }
    $refId = (int)Helpers::postRaw('affiliate_id');
    Helpers::redirect('/admin/affiliates/' . $refId);
}

// ── Approve Offer ──────────────────────────────────────────────────────────
elseif ($action === 'approve_offer') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $offerId = (int)Helpers::postRaw('offer_id');

        $ao = Database::fetchOne("SELECT ao.*, o.name as offer_name FROM affiliate_offers ao JOIN offers o ON o.id=ao.offer_id WHERE ao.affiliate_id=? AND ao.offer_id=?", [$affId, $offerId]);
        if ($ao) {
            Database::update('affiliate_offers',
                ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s'), 'approved_by' => Auth::id()],
                'affiliate_id=? AND offer_id=?',
                [$affId, $offerId]
            );

            // Notification + Email
            $affUser = Database::fetchOne("SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => null,
                    'type'        => 'success',
                    'title'       => 'Offer Access Approved',
                    'message'     => 'Your access to offer "' . $ao['offer_name'] . '" has been approved.',
                    'link'        => '/affiliate/offers',
                ]);
                try {
                    require_once BASE_PATH . '/core/FirebaseMessaging.php';
                    FirebaseMessaging::sendToUser($affUser['user_id'], 'Offer Access Approved', 'Your access to offer "' . $ao['offer_name'] . '" has been approved.', ['type' => 'offer']);
                } catch (\Throwable $e) {}

                try {
                    Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_approved', [
                        'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                        'email'      => $affUser['email'],
                        'offer_name' => $ao['offer_name'],
                        'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                    ]);
                } catch (Exception $e) {}
            }

            Helpers::flash('success', 'Offer access approved.');
        } else {
            Helpers::flash('error', 'Offer assignment not found.');
        }
    }
    $refId = (int)Helpers::postRaw('affiliate_id');
    $from  = Helpers::postRaw('from');
    Helpers::redirect($from === 'approvals' ? '/admin/offer-approvals' : '/admin/affiliates/' . $refId);
}

// ── Reject Offer ───────────────────────────────────────────────────────────
elseif ($action === 'reject_offer') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $offerId = (int)Helpers::postRaw('offer_id');

        $ao = Database::fetchOne("SELECT ao.*, o.name as offer_name FROM affiliate_offers ao JOIN offers o ON o.id=ao.offer_id WHERE ao.affiliate_id=? AND ao.offer_id=?", [$affId, $offerId]);
        if ($ao) {
            Database::update('affiliate_offers',
                ['status' => 'rejected'],
                'affiliate_id=? AND offer_id=?',
                [$affId, $offerId]
            );

            $affUser = Database::fetchOne("SELECT u.id as user_id, u.email, u.first_name, u.last_name FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => null,
                    'type'        => 'warning',
                    'title'       => 'Offer Access Rejected',
                    'message'     => 'Your access request for offer "' . $ao['offer_name'] . '" was not approved.',
                    'link'        => '/affiliate/offers',
                ]);
                try {
                    require_once BASE_PATH . '/core/FirebaseMessaging.php';
                    FirebaseMessaging::sendToUser($affUser['user_id'], 'Offer Access Rejected', 'Your access request for offer "' . $ao['offer_name'] . '" was not approved.', ['type' => 'offer']);
                } catch (\Throwable $e) {}
                try {
                    Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_rejected', [
                        'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                        'offer_name' => $ao['offer_name'],
                        'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                    ]);
                } catch (Exception $e) {}
            }

            Helpers::flash('success', 'Offer access rejected.');
        }
    }
    $refId = (int)Helpers::postRaw('affiliate_id');
    $from  = Helpers::postRaw('from');
    Helpers::redirect($from === 'approvals' ? '/admin/offer-approvals' : '/admin/affiliates/' . $refId);
}

// ── Remove Offer ───────────────────────────────────────────────────────────
// "Approval removed" — revokes current access immediately (tracking links,
// dashboard visibility, conversions). Unlike 'rejected' or 'blocked', the
// affiliate IS allowed to submit a fresh approval request for the same offer.
// All existing access tokens / tracking links become invalid the instant the
// status row is updated (click.php denies rejected/blocked/removed in real time).
elseif ($action === 'remove_offer') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $offerId = (int)Helpers::postRaw('offer_id');
        $existing = Database::fetchOne(
            "SELECT ao.id, o.name as offer_name
             FROM affiliate_offers ao
             JOIN offers o ON o.id = ao.offer_id
             WHERE ao.affiliate_id=? AND ao.offer_id=?",
            [$affId, $offerId]
        );
        if ($existing) {
            Database::update('affiliate_offers',
                ['status' => 'removed', 'approved_at' => null, 'approved_by' => null],
                'affiliate_id=? AND offer_id=?',
                [$affId, $offerId]
            );

            // Notify affiliate (in-app + email) that approval was revoked but
            // they may submit a new request.
            $affUser = Database::fetchOne(
                "SELECT u.id as user_id, u.email, u.first_name, u.last_name
                 FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?",
                [$affId]
            );
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id' => $affUser['user_id'],
                    'type'    => 'warning',
                    'title'   => 'Offer Approval Removed',
                    'message' => 'Your approval for offer "' . $existing['offer_name'] . '" has been removed. Your tracking links for this offer are now disabled. You may submit a new approval request from the Offers page.',
                    'link'    => '/affiliate/offers',
                ]);
                try {
                    require_once BASE_PATH . '/core/FirebaseMessaging.php';
                    FirebaseMessaging::sendToUser($affUser['user_id'], 'Offer Approval Removed', 'Your approval for offer "' . $existing['offer_name'] . '" has been removed.', ['type' => 'offer']);
                } catch (\Throwable $e) {}
                try {
                    Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_blocked', [
                        'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                        'email'      => $affUser['email'],
                        'offer_name' => $existing['offer_name'],
                        'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                    ]);
                } catch (Exception $_) {}
            }
            Helpers::flash('success', 'Offer approval removed. The affiliate may re-request access for this offer.');
        } else {
            // No prior row — nothing to revoke. Don't auto-create a placeholder
            // since the affiliate effectively had no access in the first place.
            Helpers::flash('warning', 'This affiliate had no existing approval for that offer.');
        }
    }
    $refId = (int)Helpers::postRaw('affiliate_id');
    Helpers::redirect('/admin/affiliates/' . $refId);
}

// ── View ───────────────────────────────────────────────────────────────────
elseif ($action === 'view' || isset($_GET['id'])) {
    $id = (int)($_GET['id'] ?? 0);
    $affiliate = Database::fetchOne(
        "SELECT u.*,af.* FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=? AND u.status!='deleted'", [$id]
    );
    if (!$affiliate) { Helpers::redirect('/admin/affiliates'); }

    $managers = Database::fetchAll("SELECT am.id, am.user_id, CONCAT(u.first_name,' ',u.last_name) as label FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE u.status='active' ORDER BY u.first_name");

    // Handle POST — status/notes/manager update
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        if (Helpers::post('offer_config_action') === 'save_offer_config') {
            $offerId = (int)Helpers::postRaw('offer_config_offer_id');
            $campaignName = Helpers::post('cfg_campaign_name');
            $customPayout = strlen(trim(Helpers::postRaw('cfg_custom_payout'))) > 0 ? (float)Helpers::postRaw('cfg_custom_payout') : null;

            $cpCountries = $_POST['cfg_country_payout_country'] ?? [];
            $cpAmounts   = $_POST['cfg_country_payout_amount']   ?? [];
            $countryPayouts = [];
            foreach ($cpCountries as $i => $cc) {
                $cc = strtoupper(trim($cc));
                $amt = (float)($cpAmounts[$i] ?? 0);
                if (strlen($cc) === 2 && $amt > 0) $countryPayouts[] = ['country' => $cc, 'payout' => $amt];
            }
            $devicePayouts = [];
            foreach (['desktop','mobile','tablet'] as $dev) {
                $amt = (float)Helpers::postRaw('cfg_device_payout_' . $dev);
                if ($amt > 0) $devicePayouts[$dev] = $amt;
            }

            $existing = Database::fetchOne("SELECT id FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?", [$id, $offerId]);
            if ($existing) {
                Database::update('affiliate_offers', [
                    'campaign_name'   => $campaignName,
                    'custom_payout'   => $customPayout,
                    'country_payouts' => $countryPayouts ? json_encode($countryPayouts) : null,
                    'device_payouts'  => $devicePayouts  ? json_encode($devicePayouts)  : null,
                ], 'affiliate_id=? AND offer_id=?', [$id, $offerId]);
            } else {
                Database::insert('affiliate_offers', [
                    'affiliate_id'    => $id,
                    'offer_id'        => $offerId,
                    'status'          => 'approved',
                    'campaign_name'   => $campaignName,
                    'custom_payout'   => $customPayout,
                    'country_payouts' => $countryPayouts ? json_encode($countryPayouts) : null,
                    'device_payouts'  => $devicePayouts  ? json_encode($devicePayouts)  : null,
                ]);
                $offerRow = Database::fetchOne("SELECT name FROM offers WHERE id=?", [$offerId]);
                $affUser  = Database::fetchOne("SELECT u.email, u.first_name, u.last_name FROM users u JOIN affiliates af ON af.user_id=u.id WHERE af.id=?", [$id]);
                if ($affUser) {
                    Mailer::sendEvent($affUser['email'], $affUser['first_name'].' '.$affUser['last_name'], 'offer_approved', [
                        'name'       => $affUser['first_name'].' '.$affUser['last_name'],
                        'email'      => $affUser['email'],
                        'site_name'  => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'    => rtrim(Config::get('config','app.url') ?? '', '/'),
                        'offer_name' => $offerRow['name'] ?? 'the offer',
                    ]);
                }
            }
            Helpers::flash('success', 'Offer configuration saved.');
            Helpers::redirect('/admin/affiliates/' . $id);
        }

        $newStatus = Helpers::post('status');
        $notes     = Helpers::post('notes');
        if (in_array($newStatus, ['active','suspended','rejected','pending'])) {
            Database::update('users', ['status' => $newStatus], 'id=?', [$affiliate['user_id']]);
            $newManagerId = Helpers::postRaw('manager_id') !== '' ? ((int)Helpers::postRaw('manager_id') ?: null) : $affiliate['manager_id'];
            Database::update('affiliates', ['manager_id' => $newManagerId], 'id=?', [$id]);
            if ($notes) Database::update('affiliates', ['notes' => $notes], 'id=?', [$id]);

            Database::insert('notifications', [
                'user_id'  => $affiliate['user_id'],
                'type'     => $newStatus === 'active' ? 'success' : 'warning',
                'title'    => 'Account Status Updated',
                'message'  => 'Your account status has been changed to: ' . $newStatus,
            ]);

            try {
                require_once BASE_PATH . '/core/FirebaseMessaging.php';
                FirebaseMessaging::sendToUser($affiliate['user_id'], 'Account Status Updated', 'Your account status has been changed to: ' . $newStatus, ['type' => 'account']);
            } catch (\Throwable $e) {}

            $emailEvent = match($newStatus) {
                'active'    => 'affiliate_approved',
                'rejected'  => 'affiliate_rejected',
                'suspended' => 'affiliate_suspended',
                default     => null,
            };
            if ($emailEvent) {
                Mailer::sendEvent($affiliate['email'], $affiliate['first_name'].' '.$affiliate['last_name'], $emailEvent, [
                    'name'      => $affiliate['first_name'].' '.$affiliate['last_name'],
                    'email'     => $affiliate['email'],
                    'site_name' => Config::get('config','app.name') ?? 'AffiliateTracker',
                    'app_url'   => rtrim(Config::get('config','app.url') ?? '', '/'),
                ]);
            }

            Helpers::flash('success', 'Affiliate status updated.');
            Helpers::redirect('/admin/affiliates/' . $id);
        }
    }

    $offers = Database::fetchAll(
        "SELECT ao.*, o.name, o.payout_amount FROM affiliate_offers ao JOIN offers o ON o.id=ao.offer_id WHERE ao.affiliate_id=?",
        [$id]
    );
    $stats = Database::fetchOne(
        "SELECT SUM(clicks) as clicks, SUM(conversions) as conv, SUM(payout) as payout FROM stats_daily WHERE affiliate_id=?",
        [$id]
    );
    $answers   = json_decode($affiliate['registration_answers'] ?? '{}', true);
    $questions = Database::fetchAll("SELECT * FROM registration_questions WHERE target_role IN ('affiliate','both') AND is_active=1 ORDER BY sort_order");

    require BASE_PATH . '/views/admin/affiliates/view.php';
}

// fallback
else {
    Helpers::redirect('/admin/affiliates');
}
