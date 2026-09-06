<?php
Auth::check('admin');
$pageTitle = 'My Profile';

$userId    = Auth::id();
$user      = Database::fetchOne("SELECT * FROM users WHERE id=?", [$userId]);
$activeTab = Helpers::get('tab') ?: 'profile';
$errors    = [];

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $tab = Helpers::post('tab') ?: 'profile';

    if ($tab === 'profile') {
        $firstName = trim(Helpers::post('first_name'));
        $lastName  = trim(Helpers::post('last_name'));
        $company   = trim(Helpers::post('company'));
        $phone     = trim(Helpers::post('phone'));
        $newEmail  = strtolower(trim(Helpers::postRaw('email')));

        if (!$firstName || !$lastName) $errors[] = 'First and last name are required.';
        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

        // Profile picture upload
        $profilePic = $user['profile_pic'] ?? null;
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
                $name = 'admin_avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = BASE_PATH . '/assets/uploads/' . $name;
                @mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $profilePic = '/assets/uploads/' . $name;
                }
            }
        }

        if (!$errors) {
            // Check email uniqueness
            if ($newEmail !== $user['email']) {
                if (Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$newEmail, $userId])) {
                    $errors[] = 'That email address is already in use.';
                }
            }
            if (!$errors) {
                Database::update('users', [
                    'first_name'  => $firstName,
                    'last_name'   => $lastName,
                    'email'       => $newEmail,
                    'company'     => $company,
                    'phone'       => $phone,
                    'profile_pic' => $profilePic,
                ], 'id=?', [$userId]);
                Helpers::flash('success', 'Profile updated successfully.');
                Helpers::redirect('/admin/profile?tab=profile');
            }
        }
    }

    elseif ($tab === 'security') {
        $current = Helpers::postRaw('current_password');
        $newPass = Helpers::postRaw('new_password');
        $confirm = Helpers::postRaw('confirm_password');

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            Database::update('users', [
                'password_hash' => password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12])
            ], 'id=?', [$userId]);
            Helpers::flash('success', 'Password changed successfully.');
            Helpers::redirect('/admin/profile?tab=security');
        }
    }
}

// Re-fetch user after possible update
$user = Database::fetchOne("SELECT * FROM users WHERE id=?", [$userId]);

require BASE_PATH . '/views/admin/profile.php';
