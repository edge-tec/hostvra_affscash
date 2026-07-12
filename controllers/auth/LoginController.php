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
}

require BASE_PATH . '/views/auth/login.php';
