<?php
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
        $email    = Helpers::postRaw('email');
        $password = Helpers::postRaw('password');
        $remember = (Helpers::postRaw('remember') === '1');
        $result   = Auth::login($email, $password, $remember);
        if ($result['success']) {
            if (!empty($result['2fa_required'])) {
                Helpers::redirect('/login/2fa');
            }
            $role = $result['role'];
            Helpers::redirect("/$role/dashboard");
        } else {
            $error = $result['error'];
        }
    }
}

require BASE_PATH . '/views/auth/login.php';
