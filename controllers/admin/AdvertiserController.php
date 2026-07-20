<?php
Auth::check('admin');
$pageTitle = 'Advertisers';
// Ensure 'deleted' is a valid status value in the users ENUM
try { Database::query("ALTER TABLE users MODIFY COLUMN status ENUM('active','pending','suspended','rejected','deleted') NOT NULL DEFAULT 'pending'"); } catch(\Throwable $_e) {}
AdvBudget::ensureSchema();

$action = Helpers::get('action') ?: ($_POST['action'] ?? '') ?: (isset($_GET['id']) ? 'view' : 'index');

// ── AJAX: Toggle budget exemption for an advertiser ───────────────────────
if ($action === 'toggle_budget_exempt') {
    header('Content-Type: application/json');
    if (!Helpers::isPost() || !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        echo json_encode(['ok' => false, 'message' => 'Invalid request.']); exit;
    }
    $advId  = (int)Helpers::postRaw('adv_id');
    $exempt = Helpers::postRaw('exempt') === '1';
    AdvBudget::setBudgetExempt($advId, $exempt);
    echo json_encode(['ok' => true, 'exempt' => $exempt]);
    exit;
}

if ($action === 'impersonate') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $user = Database::fetchOne("SELECT u.id FROM users u JOIN advertisers ad ON ad.user_id=u.id WHERE u.id=? AND u.role='advertiser'", [$userId]);
    if ($user) {
        $_SESSION['impersonate_return_url'] = $_SERVER['HTTP_REFERER'] ?? '/admin/advertisers';
        if (Auth::impersonate($userId)) {
            Helpers::redirect('/advertiser/dashboard');
        }
    }
    Helpers::flash('error', 'Could not switch to that account.');
    Helpers::redirect('/admin/advertisers');
}

elseif ($action === 'create') {
    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = Helpers::post('first_name');
        $lname   = Helpers::post('last_name');
        $email   = strtolower(trim(Helpers::postRaw('email')));
        $pass    = Helpers::postRaw('password');
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = Helpers::post('country');
        $billing = Helpers::post('billing_email') ?: $email;
        $credit  = (float)(Helpers::post('credit_limit') ?: 0);
        $status  = in_array(Helpers::post('status'), ['active','pending','suspended']) ? Helpers::post('status') : 'active';

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
                    'role'          => 'advertiser',
                    'status'        => $status,
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                    'company'       => $company,
                    'phone'         => $phone,
                    'country'       => strtoupper(substr($country ?: 'US', 0, 2)),
                ]);
                $advCode = 'ADV' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                Database::insert('advertisers', [
                    'user_id'          => $userId,
                    'advertiser_code'  => $advCode,
                    'billing_email'    => $billing,
                    'credit_limit'     => $credit,
                ]);
                Database::commit();
                Helpers::flash('success', "Advertiser account created for $fname $lname.");
                Helpers::redirect('/admin/advertisers');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to create account. Please try again.';
            }
        }
    }
    require BASE_PATH . '/views/admin/advertisers/create.php';
}

else

if ($action === 'index') {
    $advertisers = Database::fetchAll(
        "SELECT u.id,u.email,u.first_name,u.last_name,u.company,u.status,u.created_at,adv.advertiser_code,adv.balance,adv.id as adv_id,adv.budget_exempt
         FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE u.role='advertiser' AND u.status != 'deleted' ORDER BY u.created_at DESC"
    );
    require BASE_PATH . '/views/admin/advertisers/index.php';
}

elseif ($action === 'edit') {
    $id = (int)$_GET['id'];
    // Idempotent column ensure — covers admins viewing legacy advertisers
    // that registered before the answers column existed.
    try { Database::query("ALTER TABLE advertisers ADD COLUMN registration_answers TEXT NULL"); } catch(\Throwable $e) {}
    $advertiser = Database::fetchOne("SELECT u.*,adv.* FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE adv.id=? AND u.status != 'deleted'", [$id]);
    if (!$advertiser) Helpers::redirect('/admin/advertisers');

    // Registration Q&A — show ALL questions the advertiser answered (active or deactivated),
    // plus any currently active questions they may not have filled in.
    $answers = json_decode($advertiser['registration_answers'] ?? '{}', true) ?: [];
    $answeredIds = array_values(array_filter(array_keys($answers), fn($k) => is_numeric($k) && trim((string)($answers[$k] ?? '')) !== ''));
    if ($answeredIds) {
        $ph = implode(',', array_fill(0, count($answeredIds), '?'));
        $questions = Database::fetchAll(
            "SELECT * FROM registration_questions WHERE target_role IN ('advertiser','both') AND (is_active=1 OR id IN ($ph)) ORDER BY sort_order",
            $answeredIds
        );
    } else {
        $questions = Database::fetchAll(
            "SELECT * FROM registration_questions WHERE target_role IN ('advertiser','both') AND is_active=1 ORDER BY sort_order"
        );
    }

    $errors = [];
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $fname   = trim(Helpers::post('first_name'));
        $lname   = trim(Helpers::post('last_name'));
        $email   = strtolower(trim(Helpers::postRaw('email')));
        $company = Helpers::post('company');
        $phone   = Helpers::post('phone');
        $country = strtoupper(substr(Helpers::post('country') ?: 'US', 0, 2));
        $billing  = Helpers::post('billing_email') ?: $email;
        $credit   = (float)(Helpers::postRaw('credit_limit') ?: 0);
        $status   = in_array(Helpers::post('status'), ['active','pending','suspended','rejected']) ? Helpers::post('status') : $advertiser['status'];
        $newPass  = Helpers::postRaw('new_password');
        $telegram = trim(Helpers::post('telegram') ?? '') ?: null;
        $skype    = trim(Helpers::post('skype')    ?? '') ?: null;
        $discord  = trim(Helpers::post('discord')  ?? '') ?: null;

        if (!$fname || !$lname) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if ($newPass && strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
        if (!$errors) {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$email, $advertiser['user_id']]);
            if ($existing) $errors[] = 'Email already in use by another account.';
        }

        if (!$errors) {
            $userUpdate = [
                'first_name' => $fname, 'last_name' => $lname, 'email' => $email,
                'company' => $company, 'phone' => $phone, 'country' => $country, 'status' => $status,
                'telegram' => $telegram, 'skype' => $skype, 'discord' => $discord,
            ];
            if ($newPass) $userUpdate['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            Database::update('users', $userUpdate, 'id=?', [$advertiser['user_id']]);
            Database::update('advertisers', [
                'billing_email' => $billing,
                'credit_limit'  => $credit,
                'budget_exempt' => isset($_POST['budget_exempt']) ? 1 : 0,
            ], 'id=?', [$id]);
            Helpers::flash('success', 'Advertiser updated successfully.');
            Helpers::redirect('/admin/advertisers/' . $id);
        }
    }
    require BASE_PATH . '/views/admin/advertisers/edit.php';
}

elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)Helpers::postRaw('adv_id');
        $adv = Database::fetchOne("SELECT u.id as user_id FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE adv.id=?", [$id]);
        if ($adv) {
            try {
                Database::begin();

                // 1. Get all offer IDs owned by this advertiser
                $offers = Database::fetchAll("SELECT id FROM offers WHERE advertiser_id=?", [$id]);
                $offerIds = array_column($offers, 'id');

                // 2. Delete offer sub-records (affiliate_offers, smartlink_offers, offer_links)
                if ($offerIds) {
                    $ph = implode(',', array_fill(0, count($offerIds), '?'));
                    Database::query("DELETE FROM affiliate_offers WHERE offer_id IN ($ph)", $offerIds);
                    try { Database::query("DELETE FROM smartlink_offers WHERE offer_id IN ($ph)", $offerIds); } catch (\Throwable $e) {}
                    try { Database::query("DELETE FROM offer_links WHERE offer_id IN ($ph)", $offerIds); } catch (\Throwable $e) {}
                }

                // 3. Delete offers
                Database::query("DELETE FROM offers WHERE advertiser_id=?", [$id]);

                // 4. Nullify advertiser_id in invoices (preserve invoice history)
                try { Database::query("UPDATE invoices SET advertiser_id=NULL WHERE advertiser_id=?", [$id]); } catch (\Throwable $e) {}

                // 5. Delete clicks referencing this advertiser (or nullify)
                try { Database::query("UPDATE clicks SET advertiser_id=NULL WHERE advertiser_id=?", [$id]); } catch (\Throwable $e) {}

                // 6. Delete notifications for this user
                try { Database::query("DELETE FROM notifications WHERE user_id=?", [$adv['user_id']]); } catch (\Throwable $e) {}

                // 7. Delete user devices
                try { Database::query("DELETE FROM user_devices WHERE user_id=?", [$adv['user_id']]); } catch (\Throwable $e) {}

                // 8. Delete the advertiser record
                Database::query("DELETE FROM advertisers WHERE id=?", [$id]);

                // 9. Delete the user account
                Database::query("DELETE FROM users WHERE id=?", [$adv['user_id']]);

                Database::commit();
                Helpers::flash('success', 'Advertiser account permanently deleted.');
            } catch (\Throwable $e) {
                Database::rollback();
                Helpers::flash('error', 'Delete failed: ' . $e->getMessage());
            }
        } else {
            Helpers::flash('error', 'Advertiser not found.');
        }
    }
    Helpers::redirect('/admin/advertisers');
}

elseif ($action === 'view') {
    $id = (int)$_GET['id'];
    $advertiser = Database::fetchOne("SELECT u.*,adv.* FROM users u JOIN advertisers adv ON adv.user_id=u.id WHERE adv.id=? AND u.status != 'deleted'", [$id]);
    if (!$advertiser) Helpers::redirect('/admin/advertisers');

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $newStatus = Helpers::post('status');
        if (in_array($newStatus, ['active','suspended','rejected','pending'])) {
            Database::update('users', ['status' => $newStatus], 'id=?', [$advertiser['user_id']]);
            Database::insert('notifications', [
                'user_id' => $advertiser['user_id'],
                'type'    => $newStatus === 'active' ? 'success' : 'warning',
                'title'   => 'Account Status Updated',
                'message' => 'Your advertiser account status has been changed to: ' . $newStatus,
            ]);
            Helpers::flash('success', 'Advertiser status updated.');
            Helpers::redirect('/admin/advertisers/' . $id);
        }
    }

    $offers = Database::fetchAll("SELECT * FROM offers WHERE advertiser_id=? ORDER BY created_at DESC", [$id]);
    try { Database::query("ALTER TABLE advertisers ADD COLUMN registration_answers TEXT NULL"); } catch(\Throwable $e) {}
    // Registration Q&A — show ALL questions the advertiser answered (active or deactivated).
    $answers = json_decode($advertiser['registration_answers'] ?? '{}', true) ?: [];
    $answeredIds = array_values(array_filter(array_keys($answers), fn($k) => is_numeric($k) && trim((string)($answers[$k] ?? '')) !== ''));
    if ($answeredIds) {
        $ph = implode(',', array_fill(0, count($answeredIds), '?'));
        $questions = Database::fetchAll(
            "SELECT * FROM registration_questions WHERE target_role IN ('advertiser','both') AND (is_active=1 OR id IN ($ph)) ORDER BY sort_order",
            $answeredIds
        );
    } else {
        $questions = Database::fetchAll(
            "SELECT * FROM registration_questions WHERE target_role IN ('advertiser','both') AND is_active=1 ORDER BY sort_order"
        );
    }
    require BASE_PATH . '/views/admin/advertisers/index.php';
}
