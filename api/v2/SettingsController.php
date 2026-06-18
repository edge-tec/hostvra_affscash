<?php
header('Content-Type: application/json');

Auth::check('affiliate');

$affId = Auth::affiliateId();
$userId = Auth::id();
$action = $_GET['action'] ?? 'load';

if ($action === 'load') {
    $aff = Database::fetchOne("SELECT af.*, u.first_name, u.last_name, u.email, u.company, u.phone, u.country, u.profile_pic, u.google2fa_enabled FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?", [$affId]);
    
    if (!$aff) {
        $u = Database::fetchOne("SELECT first_name, last_name, email, company, phone, google2fa_enabled FROM users WHERE id=?", [$userId]);
        $aff = [
            'first_name' => $u['first_name'] ?? '',
            'last_name' => $u['last_name'] ?? '',
            'email' => $u['email'] ?? '',
            'company' => $u['company'] ?? '',
            'phone' => $u['phone'] ?? '',
            'google2fa_enabled' => $u['google2fa_enabled'] ?? 0,
            'payment_method' => null,
            'payment_details' => null,
            'manager_id' => null
        ];
    }
    
    // Fetch available payment methods
    $paymentMethods = Database::fetchAll("SELECT name FROM payment_methods WHERE is_active=1 ORDER BY is_default DESC, name");
    $pmList = array_column($paymentMethods, 'name');
    if (empty($pmList)) {
        $pmList = ['PayPal', 'Wire Transfer', 'Cryptocurrency', 'Payoneer', 'Wise'];
    }

    // Fetch manager info
    $manager = null;
    if (!empty($aff['manager_id'])) {
        $manager = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email, u.company, u.phone, am.skype, am.telegram
             FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
            [$aff['manager_id']]
        );
    }

    // Check for pending delete request
    $deleteRequest = null;
    if ($affId > 0) {
        $deleteRequest = Database::fetchOne(
            "SELECT status, requested_at, reason FROM account_delete_requests WHERE affiliate_id=? ORDER BY requested_at DESC LIMIT 1",
            [$affId]
        );
    }

    echo json_encode([
        'success' => true,
        'profile' => [
            'first_name' => $aff['first_name'],
            'last_name' => $aff['last_name'],
            'email' => $aff['email'],
            'company' => $aff['company'],
            'phone' => $aff['phone'],
            'global_postback_url' => $aff['global_postback_url'] ?? null
        ],
        'payment' => [
            'method' => $aff['payment_method'],
            'details' => $aff['payment_details']
        ],
        'manager' => $manager,
        'payment_methods' => $pmList,
        'two_factor_enabled' => (bool)$aff['google2fa_enabled'],
        'delete_request' => $deleteRequest
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if ($action === 'update_profile') {
        $firstName = trim($input['first_name'] ?? '');
        $lastName  = trim($input['last_name'] ?? '');
        $company   = trim($input['company'] ?? '');
        $phone     = trim($input['phone'] ?? '');

        if (!$firstName || !$lastName) {
            echo json_encode(['success' => false, 'error' => 'First and last name are required.']);
            exit;
        }

        Database::update('users', [
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'company'     => $company,
            'phone'       => $phone,
        ], 'id=?', [$userId]);

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
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
        $details = trim($input['payment_details'] ?? '');

        if ($affId > 0) {
            Database::update('affiliates', [
                'payment_method'  => $method,
                'payment_details' => $details,
            ], 'id=?', [$affId]);
        }

        echo json_encode(['success' => true, 'message' => 'Payment details saved.']);
        exit;
    }

    if ($action === 'update_global_postback') {
        $url = trim($input['global_postback_url'] ?? '');
        if ($url && !filter_var(strtok($url, '?'), FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid URL. Must start with https://']);
            exit;
        }

        if ($affId > 0) {
            if ($url) {
                Database::update('affiliates', [
                    'global_postback_url' => $url,
                    'global_pb_admin_status' => 'approved',
                    'global_pb_active' => 1
                ], 'id=?', [$affId]);
            } else {
                Database::update('affiliates', [
                    'global_postback_url' => null,
                    'global_pb_admin_status' => null,
                    'global_pb_admin_note' => null,
                    'global_pb_submitted_at' => null
                ], 'id=?', [$affId]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Global postback URL saved.']);
        exit;
    }

    if ($action === '2fa_start') {
        $secret = Totp::generateSecret(16);
        
        // Store in a temporary app-specific session or directly return it to be used within the app flow
        // Since API is stateless via JWT or Session, we can just return it, and the app must send it back on verify
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

    if ($action === 'request_account_delete') {
        $reason = trim($input['reason'] ?? '');
        if (strlen($reason) < 10) {
            echo json_encode(['success' => false, 'error' => 'Please provide a reason (at least 10 characters).']);
            exit;
        }

        $existing = Database::fetchOne(
            "SELECT id FROM account_delete_requests WHERE affiliate_id=? AND status='pending' ORDER BY requested_at DESC LIMIT 1",
            [$affId]
        );

        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'You already have a pending account deletion request. Please wait for admin review.']);
            exit;
        }

        Database::insert('account_delete_requests', [
            'affiliate_id' => $affId,
            'user_id'      => $userId,
            'reason'       => $reason,
            'status'       => 'pending',
            'requested_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode(['success' => true, 'message' => 'Your account deletion request has been submitted.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
exit;
