<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Login';
$error = '';

if (Auth::id()) {
    Helpers::redirect('/' . Auth::role() . '/dashboard');
}

if (Helpers::isPost()) {
    try {
        // ── Cloudflare Turnstile verification ─────────────────────────────────
        if (Turnstile::isEnabled()) {
            $tsToken = trim($_POST['cf-turnstile-response'] ?? '');
            if (!Turnstile::verify($tsToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
                $error = 'CAPTCHA verification failed. Please complete the challenge and try again.';
            }
        }

        if (!$error) {
            // ── CSRF verification ────────────────────────────────────────────
            if (!Auth::verifyCsrf(Helpers::postRaw('_token') ?? '')) {
                $error = 'Invalid form submission. Please try again.';
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!$error) {
            $email    = Helpers::postRaw('email');
            $password = Helpers::postRaw('password');
            $remember = (Helpers::postRaw('remember') === '1');

            // ── VPN/Proxy/TOR login guard ─────────────────────────────────────
            // Only active when admin enables it. Admin accounts are always exempt.
            $_vpnLoginEnabled = (Config::get('config', 'vpn_detection.login_enabled') ?? '0') === '1';
            if ($_vpnLoginEnabled) {
                // Quick role lookup — do NOT reveal whether the email exists
                $_loginUser = Database::fetchOne("SELECT `role` FROM `users` WHERE `email` = ?", [strtolower(trim($email))]);
                if ($_loginUser && $_loginUser['role'] !== 'admin') {
                    $_vpnCheck = RegistrationVpnGuard::checkIp(Helpers::getIp());
                    if ($_vpnCheck['blocked']) {
                        if ($isAjax) {
                            Helpers::json(['success' => false, 'error' => 'Login is not allowed while using VPN or Proxy. Please disconnect and try again.']);
                        }
                        http_response_code(403);
                        $_vpnBlockReason = $_vpnCheck['reason'];
                        require BASE_PATH . '/views/auth/login_vpn_blocked.php';
                        return;
                    }
                }
            }

            $result   = Auth::login($email, $password, $remember);
            if ($result['success']) {
                if (!empty($result['2fa_required'])) {
                    if ($isAjax) Helpers::json(['success' => true, 'redirect' => '/login/2fa']);
                    Helpers::redirect('/login/2fa');
                }
                $role = $result['role'];
                if ($isAjax) Helpers::json(['success' => true, 'redirect' => "/$role/dashboard"]);
                Helpers::redirect("/$role/dashboard");
            } else {
                $error = $result['error'];
            }
        }
        
        if ($isAjax && $error) {
            Helpers::json(['success' => false, 'error' => $error]);
        }
    } catch (\Throwable $t) {
        echo "<h1>FATAL ERROR ENCOUNTERED DURING LOGIN POST:</h1>";
        echo "<p><strong>Error Message:</strong> " . htmlspecialchars($t->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($t->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . htmlspecialchars($t->getLine()) . "</p>";
        echo "<h2>Stack Trace:</h2><pre>" . htmlspecialchars($t->getTraceAsString()) . "</pre>";
        exit;
    }
}

require BASE_PATH . '/views/auth/login.php';
