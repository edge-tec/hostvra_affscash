<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

$userId = Auth::id();
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled` TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `users` ADD COLUMN `google2fa_enabled_at` DATETIME DEFAULT NULL"); } catch (\Throwable $_e) {}

$mgr = Database::fetchOne(
    "SELECT am.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.profile_pic, u.google2fa_enabled 
     FROM affiliate_managers am 
     JOIN users u ON u.id = am.user_id 
     WHERE am.user_id = ?",
    [$userId]
);

if (!$mgr) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Manager profile not found.']);
    exit;
}

$action = $_GET['action'] ?? 'load';

if ($action === 'load') {
    echo json_encode([
        'success' => true,
        'profile' => [
            'first_name' => $mgr['first_name'],
            'last_name' => $mgr['last_name'],
            'email' => $mgr['email'],
            'company' => $mgr['company'],
            'phone' => $mgr['phone'],
            'skype' => $mgr['skype'],
            'telegram' => $mgr['telegram'],
            'discord' => $mgr['discord'],
            'profile_pic' => $mgr['profile_pic'] ? (Config::get('config', 'app.url') . $mgr['profile_pic']) : null
        ],
        'payment' => [
            'method' => $mgr['payment_method'],
            'details' => $mgr['payment_details']
        ],
        'two_factor_enabled' => (bool)$mgr['google2fa_enabled']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine input type (multipart vs json)
    $input = [];
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }

    if ($action === 'update_profile') {
        $firstName = trim($input['first_name'] ?? '');
        $lastName  = trim($input['last_name'] ?? '');
        $email     = strtolower(trim($input['email'] ?? ''));
        $company   = trim($input['company'] ?? '');
        $phone     = trim($input['phone'] ?? '');
        $skype     = trim($input['skype'] ?? '');
        $telegram  = trim($input['telegram'] ?? '');
        $discord   = trim($input['discord'] ?? '');

        if (!$firstName || !$lastName) {
            echo json_encode(['success' => false, 'error' => 'First and last name are required.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
            exit;
        }
        if (Database::fetchOne("SELECT id FROM users WHERE email=? AND id!=?", [$email, $userId])) {
            echo json_encode(['success' => false, 'error' => 'That email is already in use by another account.']);
            exit;
        }

        $profilePic = $mgr['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $mimeMap = [
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $mime = mime_content_type($file['tmp_name']);
            if (isset($mimeMap[$mime]) && $file['size'] <= 2 * 1024 * 1024) {
                $ext = $mimeMap[$mime];
                $name = 'mgr_avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = BASE_PATH . '/assets/uploads/' . $name;
                if (!is_dir(BASE_PATH . '/assets/uploads')) @mkdir(BASE_PATH . '/assets/uploads', 0775, true);
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $profilePic = '/assets/uploads/' . $name;
                }
            }
        }

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

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.', 'profile_pic' => $profilePic ? (Config::get('config', 'app.url') . $profilePic) : null]);
        exit;
    }

    if ($action === 'update_security') {
        $current = $input['current_password'] ?? '';
        $newPass = $input['new_password'] ?? '';

        $user = Database::fetchOne("SELECT password_hash FROM users WHERE id=?", [$userId]);
        if (!password_verify($current, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
            exit;
        } elseif (strlen($newPass) < 8) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters.']);
            exit;
        }

        Database::update('users', ['password_hash' => password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12])], 'id=?', [$userId]);
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        exit;
    }

    if ($action === 'update_payment') {
        $method = trim($input['payment_method'] ?? '');
        $details = $input['payment_details'] ?? '';

        Database::update('affiliate_managers', [
            'payment_method'  => $method,
            'payment_details' => is_array($details) ? json_encode($details) : $details,
        ], 'user_id=?', [$userId]);

        echo json_encode(['success' => true, 'message' => 'Payment details saved.']);
        exit;
    }

    if ($action === '2fa_start') {
        require_once BASE_PATH . '/core/Totp.php';
        $secret = Totp::generateSecret(16);
        $user = Database::fetchOne("SELECT email FROM users WHERE id=?", [$userId]);
        $siteName = Config::get('config', 'app.name') ?: 'AffiliateTracker';
        
        $otpauthUri = Totp::otpauthUri($siteName, $user['email'], $secret);
        $qrUrl = Totp::qrCodeUrl($otpauthUri);

        echo json_encode([
            'success' => true,
            'secret' => $secret,
            'qr_url' => $qrUrl
        ]);
        exit;
    }

    if ($action === '2fa_verify') {
        require_once BASE_PATH . '/core/Totp.php';
        $code = trim($input['code'] ?? '');
        $secret = trim($input['secret'] ?? '');

        if (Totp::verify($secret, $code)) {
            Database::update('users', [
                'google2fa_enabled'    => 1,
                'google2fa_secret'     => Totp::encrypt($secret),
                'google2fa_enabled_at' => date('Y-m-d H:i:s'),
            ], 'id=?', [$userId]);
            
            echo json_encode(['success' => true, 'message' => '2FA enabled successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid authentication code.']);
        }
        exit;
    }

    if ($action === '2fa_disable') {
        require_once BASE_PATH . '/core/Totp.php';
        $pwd = trim($input['password'] ?? '');
        $code = trim($input['code'] ?? '');
        
        $row = Database::fetchOne("SELECT password_hash, google2fa_secret FROM users WHERE id=?", [$userId]);
        $secret  = $row ? Totp::decrypt((string)$row['google2fa_secret']) : '';

        $okPwd  = ($pwd !== '' && password_verify($pwd, $row['password_hash']));
        $okCode = ($code !== '' && $secret !== '' && Totp::verify($secret, $code));

        if (!$okPwd && !$okCode) {
            echo json_encode(['success' => false, 'error' => 'Verification failed. Provide correct password or OTP.']);
            exit;
        }

        Database::update('users', [
            'google2fa_enabled'    => 0,
            'google2fa_secret'     => null,
            'google2fa_enabled_at' => null,
        ], 'id=?', [$userId]);

        echo json_encode(['success' => true, 'message' => '2FA disabled successfully.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
exit;
