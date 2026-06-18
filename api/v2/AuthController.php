<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        exit;
    }

    $result = Auth::login($email, $password, true); // True to remember session
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'role' => $result['role'],
            'session_id' => session_id(),
            'message' => 'Login successful',
            'user' => Auth::currentUser()
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
} elseif ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} elseif ($action === 'forgot_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = strtolower(trim($data['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address']);
        exit;
    }

    $user = Database::fetchOne("SELECT id, first_name, last_name, email, status FROM users WHERE email = ?", [$email]);
    if ($user && $user['status'] === 'active') {
        $existing = Database::fetchOne("SELECT password_reset_expires FROM users WHERE id = ?", [$user['id']]);
        $canSend = true;
        if (!empty($existing['password_reset_expires'])) {
            $expiresAt = strtotime($existing['password_reset_expires']);
            $issuedAt  = $expiresAt - 900; // token lifetime = 15 mins
            if ((time() - $issuedAt) < 60) {
                $canSend = false;
            }
        }
        if ($canSend) {
            $otp = sprintf("%06d", random_int(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            Database::query("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?", [$otp, $expires, $user['id']]);

            $siteName  = Config::get('config', 'app.name') ?? 'AffiliateTracker';
            $toName    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
            $body = '
<p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
<p>We received a request to reset your password for your <strong>' . htmlspecialchars($siteName) . '</strong> account.</p>
<p>Your password reset OTP is:</p>
<div style="text-align:center;margin:28px 0">
  <span style="background:#F1F5F9;color:#0F172A;padding:14px 32px;border-radius:8px;font-family:monospace;font-weight:700;font-size:24px;letter-spacing:4px;display:inline-block">
    ' . htmlspecialchars($otp) . '
  </span>
</div>
<p style="color:#64748B;font-size:13px">This OTP expires in <strong>15 minutes</strong>. If you did not request a password reset, you can safely ignore this email.</p>';
            $body = Mailer::applyTheme($body, 'Password Reset OTP');
            try {
                Mailer::sendRaw($user['email'], $toName, "[$siteName] Password Reset OTP", $body, 'password_reset');
            } catch (Exception $e) {}
        }
    }
    // Always return success to prevent enumeration
    echo json_encode(['success' => true, 'message' => 'If an account exists, an OTP has been sent.']);
} elseif ($action === 'verify_otp') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = strtolower(trim($data['email'] ?? ''));
    $otp = trim($data['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'error' => 'Email and OTP are required']);
        exit;
    }

    $user = Database::fetchOne("SELECT id, password_reset_token, password_reset_expires FROM users WHERE email = ? AND status = 'active'", [$email]);
    if ($user && $user['password_reset_token'] === $otp && strtotime($user['password_reset_expires']) > time()) {
        // Valid OTP. Generate a secure token to use for actual reset.
        $secureToken = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 900);
        Database::query("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?", [$secureToken, $expires, $user['id']]);
        echo json_encode(['success' => true, 'token' => $secureToken]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP']);
    }
} elseif ($action === 'reset_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = strtolower(trim($data['email'] ?? ''));
    $token = trim($data['token'] ?? '');
    $newPassword = $data['password'] ?? '';

    if (empty($email) || empty($token) || empty($newPassword)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
        exit;
    }

    $user = Database::fetchOne("SELECT id, password_reset_token, password_reset_expires FROM users WHERE email = ? AND status = 'active'", [$email]);
    if ($user && strlen($token) === 64 && $user['password_reset_token'] === $token && strtotime($user['password_reset_expires']) > time()) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        Database::query("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?", [$hash, $user['id']]);
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired reset token']);
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action not found']);
}
