<?php
Auth::check('affiliate_manager');
$pageTitle = 'My Profile';

$userId = Auth::id();
$mgr    = Database::fetchOne(
    "SELECT am.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.profile_pic
     FROM affiliate_managers am
     JOIN users u ON u.id = am.user_id
     WHERE am.user_id = ?",
    [$userId]
);

if (!$mgr) {
    Helpers::flash('error', 'Profile not found.');
    Helpers::redirect('/affiliate_manager/dashboard');
}

$activeTab = Helpers::get('tab') ?: 'profile';

// Ensure payment columns exist (schema guard)
try {
    Database::query("ALTER TABLE affiliate_managers ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT ''");
} catch (\Throwable $e) {}
try {
    Database::query("ALTER TABLE affiliate_managers ADD COLUMN payment_details TEXT DEFAULT NULL");
} catch (\Throwable $e) {}

// Re-fetch after possible schema change
$mgr = Database::fetchOne(
    "SELECT am.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.profile_pic
     FROM affiliate_managers am JOIN users u ON u.id = am.user_id WHERE am.user_id = ?",
    [$userId]
);
$errors    = [];

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $tab = Helpers::post('tab') ?: 'profile';

    // ── Profile tab ──────────────────────────────────────────────────────
    if ($tab === 'profile') {
        $firstName = trim(Helpers::post('first_name'));
        $lastName  = trim(Helpers::post('last_name'));
        $email     = strtolower(trim(Helpers::postRaw('email')));
        $company   = trim(Helpers::post('company'));
        $phone     = trim(Helpers::post('phone'));
        $skype     = trim(Helpers::post('skype'));
        $telegram  = trim(Helpers::post('telegram'));
        $discord   = trim(Helpers::post('discord'));

        if (!$firstName || !$lastName) $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Invalid email address.';
        if (!$errors && Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$email, $userId])) {
            $errors[] = 'That email is already in use by another account.';
        }

        // Profile picture upload
        $profilePic = $mgr['profile_pic'];
        if (!empty($_FILES['profile_pic']['tmp_name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['profile_pic'];
            $mimeMap = [
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $mime    = mime_content_type($file['tmp_name']);
            if (!isset($mimeMap[$mime])) {
                $errors[] = 'Profile picture must be PNG, JPG, GIF or WebP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile picture must be under 2 MB.';
            } else {
                $ext  = $mimeMap[$mime];
                $name = 'mgr_avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = BASE_PATH . '/assets/uploads/' . $name;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $profilePic = '/assets/uploads/' . $name;
                }
            }
        }

        if (!$errors) {
            Database::update('users', [
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'email'       => $email,
                'company'     => $company,
                'phone'       => $phone,
                'profile_pic' => $profilePic,
            ], 'id=?', [$userId]);

            Database::update('affiliate_managers', [
                'skype'    => $skype,
                'telegram' => $telegram,
                'discord'  => $discord,
            ], 'user_id=?', [$userId]);

            Helpers::flash('success', 'Profile updated successfully.');
            Helpers::redirect('/affiliate_manager/profile?tab=profile');
        }
    }

    // ── Security tab ─────────────────────────────────────────────────────
    elseif ($tab === 'security') {
        $current = Helpers::postRaw('current_password');
        $newPass = Helpers::postRaw('new_password');
        $confirm = Helpers::postRaw('confirm_password');

        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id=?", [$userId]);
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            Database::update('users', [
                'password_hash' => password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]),
            ], 'id=?', [$userId]);
            Helpers::flash('success', 'Password changed successfully.');
            Helpers::redirect('/affiliate_manager/profile?tab=security');
        }
    }

    // ── Payment tab ───────────────────────────────────────────────────────
    elseif ($tab === 'payment') {
        $paymentMethod = Helpers::post('payment_method') ?: '';
        $details = [];

        if ($paymentMethod === 'paypal') {
            $details = ['email' => trim(Helpers::post('paypal_email'))];
            if (empty($details['email'])) $errors[] = 'PayPal email is required.';
        } elseif ($paymentMethod === 'bank') {
            $details = [
                'bank_name'    => trim(Helpers::post('bank_name')),
                'account_name' => trim(Helpers::post('bank_account_name')),
                'account_no'   => trim(Helpers::post('bank_account_no')),
                'swift'        => trim(Helpers::post('bank_swift')),
                'iban'         => trim(Helpers::post('bank_iban')),
            ];
            if (empty($details['account_no'])) $errors[] = 'Bank account number is required.';
        } elseif ($paymentMethod === 'crypto') {
            $details = [
                'address' => trim(Helpers::post('crypto_address')),
                'network' => trim(Helpers::post('crypto_network')),
            ];
            if (empty($details['address'])) $errors[] = 'Wallet address is required.';
        } elseif ($paymentMethod === 'wise') {
            $details = ['email' => trim(Helpers::post('wise_email'))];
            if (empty($details['email'])) $errors[] = 'Wise email is required.';
        } elseif ($paymentMethod === 'other') {
            $details = ['info' => trim(Helpers::post('other_details'))];
        }

        if (!$errors) {
            Database::update('affiliate_managers', [
                'payment_method'  => $paymentMethod,
                'payment_details' => $paymentMethod ? json_encode($details) : null,
            ], 'user_id=?', [$userId]);
            Helpers::flash('success', 'Payment details saved successfully.');
            Helpers::redirect('/affiliate_manager/profile?tab=payment');
        }
    }
}

require BASE_PATH . '/views/affiliate_manager/profile.php';
