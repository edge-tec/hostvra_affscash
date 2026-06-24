<?php
Auth::check('affiliate');
$pageTitle = 'My Settings';

$affId   = Auth::affiliateId();
$userId  = Auth::id();

// Ensure optional columns exist before reading them
try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS payment_details TEXT DEFAULT NULL"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS payment_terms VARCHAR(20) NOT NULL DEFAULT 'monthly'"); } catch(\Throwable $e) {}
try { Database::query("ALTER TABLE affiliates ADD COLUMN IF NOT EXISTS allow_email_change TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $e) {}

$aff = Database::fetchOne(
    "SELECT af.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.country, u.profile_pic 
     FROM users u 
     LEFT JOIN affiliates af ON u.id=af.user_id 
     WHERE u.id=?", 
    [$userId]
);

if (empty($aff['id'])) {
    $code = strtoupper(substr(md5(uniqid('',true)), 0, 8));
    $affId = Database::insert('affiliates', [
        'user_id' => $userId,
        'affiliate_code' => $code
    ]);
    $_SESSION['affiliate_id'] = $affId;
    $aff = Database::fetchOne(
        "SELECT af.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.country, u.profile_pic 
         FROM users u 
         LEFT JOIN affiliates af ON u.id=af.user_id 
         WHERE u.id=?", 
        [$userId]
    );
}

$activeTab = Helpers::get('tab') ?: 'profile';

// Fetch available payment methods (include method_type for structured-field rendering)
try { Database::query("ALTER TABLE `payment_methods` ADD COLUMN `method_type` VARCHAR(50) NOT NULL DEFAULT 'custom'"); } catch(\Throwable $e) {}
$paymentMethods = Database::fetchAll("SELECT * FROM payment_methods WHERE is_active=1 ORDER BY is_default DESC, name");

// Fetch manager info if assigned
// affiliates.manager_id stores affiliate_managers.id (am.id), not users.id
$manager = null;
if (!empty($aff['manager_id'])) {
    $manager = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email, u.company, u.phone, am.commission_rate
         FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
        [$aff['manager_id']]
    );
}

$errors  = [];
$success = false;

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $tab = Helpers::post('tab') ?: 'profile';

    if ($tab === 'profile') {
        $firstName = trim(Helpers::post('first_name'));
        $lastName  = trim(Helpers::post('last_name'));
        $company   = trim(Helpers::post('company'));
        $phone     = trim(Helpers::post('phone'));

        if (!$firstName || !$lastName) $errors[] = 'First and last name are required.';

        // Handle profile picture upload
        $profilePic = $aff['profile_pic'];
        if (!empty($_FILES['profile_pic']['tmp_name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['profile_pic'];
            $allowed  = ['image/png','image/jpeg','image/gif','image/webp'];
            $mime     = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowed)) {
                $errors[] = 'Profile picture must be PNG, JPG, GIF or WebP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile picture must be under 2 MB.';
            } else {
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $name = 'avatar_' . $userId . '_' . time() . '.' . strtolower($ext);
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
                'company'     => $company,
                'phone'       => $phone,
                'profile_pic' => $profilePic,
            ], 'id=?', [$userId]);

            // Email change (only if allowed)
            $newEmail = strtolower(trim(Helpers::postRaw('email')));
            if ($aff['allow_email_change'] && $newEmail && $newEmail !== $aff['email']) {
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email address.';
                } elseif (Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$newEmail, $userId])) {
                    $errors[] = 'Email already in use.';
                } else {
                    Database::update('users', ['email' => $newEmail], 'id=?', [$userId]);
                }
            }

            if (!$errors) {
                $success = true;
                Helpers::flash('success', 'Profile updated successfully.');
                Helpers::redirect('/affiliate/profile?tab=profile');
            }
        }
    }

    elseif ($tab === 'security') {
        $current  = Helpers::postRaw('current_password');
        $newPass  = Helpers::postRaw('new_password');
        $confirm  = Helpers::postRaw('confirm_password');

        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id=?", [$userId]);
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            Database::update('users', ['password_hash' => password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12])], 'id=?', [$userId]);
            Helpers::flash('success', 'Password changed successfully.');
            Helpers::redirect('/affiliate/profile?tab=security');
        }
    }

    elseif ($tab === 'payment') {
        $method   = trim(Helpers::post('payment_method') ?? '');
        $pmType   = Helpers::post('pd_method_type') ?: 'custom';
        $pdFields = $_POST['pd_field'] ?? null;

        // Encode structured fields as JSON when a typed method is selected
        if ($pdFields && is_array($pdFields) && $pmType !== 'custom') {
            $details = json_encode(array_map('trim', $pdFields), JSON_UNESCAPED_UNICODE);
        } else {
            $details = Helpers::postRaw('payment_details') ?: '';
        }

        // Auto-migrate column from ENUM to VARCHAR if needed (safe to run repeatedly)
        try {
            $colType = Database::fetchOne(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME='payment_method'"
            );
            if ($colType && stripos($colType['COLUMN_TYPE'], 'enum') !== false) {
                Database::query("ALTER TABLE `affiliates` MODIFY `payment_method` VARCHAR(100) NOT NULL DEFAULT ''");
            }
        } catch (\Throwable $e) {}

        // Validate: must be one of the known payment methods (or a built-in fallback)
        $validMethods = array_column(
            Database::fetchAll("SELECT name FROM payment_methods WHERE is_active=1"),
            'name'
        );
        if (empty($validMethods)) {
            $validMethods = ['PayPal', 'Wire Transfer', 'Cryptocurrency', 'Payoneer', 'Wise'];
        }
        if ($method !== '' && !in_array($method, $validMethods, true)) {
            $method = '';
        }

        $method = substr($method, 0, 100);

        Database::update('affiliates', [
            'payment_method'  => $method,
            'payment_details' => $details,
        ], 'id=?', [$affId]);

        Helpers::flash('success', 'Payment details saved.');
        Helpers::redirect('/affiliate/profile?tab=payment');
    }
}

require BASE_PATH . '/views/affiliate/profile.php';
