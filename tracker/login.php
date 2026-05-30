<?php
/**
 * /tracker/login.php
 * Standalone login page — shares the same session & DB as the main tracker.
 * Served directly by Apache (real file, bypasses router).
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Redirect to installer if not set up
if (!file_exists(CONFIG_PATH . '/config.json')) {
    header('Location: /install/');
    exit;
}

// Bootstrap the tracker framework (same as index.php)
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Activity.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Mailer.php';
require BASE_PATH . '/core/Turnstile.php';

Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// Start the SAME session as the main tracker (session name: AFFILIATETRACKSID)
Auth::start();

// Already logged in → go to dashboard
if (Auth::id()) {
    $role = Auth::role();
    Helpers::redirect("/$role/dashboard");
}

// ── Handle POST (login submission) ────────────────────────────────────────────
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $error = 'Invalid form submission. Please refresh and try again.';
    }

    // Turnstile CAPTCHA
    if (!$error && Turnstile::isEnabled()) {
        $tsToken = trim($_POST['cf-turnstile-response'] ?? '');
        if (!Turnstile::verify($tsToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
            $error = 'CAPTCHA verification failed. Please complete the challenge.';
        }
    }

    if (!$error) {
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = Auth::login($email, $password);
            if ($result['success']) {
                // 2FA required → redirect to tracker 2FA page
                if (!empty($result['2fa_required'])) {
                    Helpers::redirect('/login/2fa');
                }
                // Success → go to role dashboard
                Helpers::redirect('/' . $result['role'] . '/dashboard');
            } else {
                $error = $result['error'];
            }
        }
    }
}

// ── Collect flash messages ─────────────────────────────────────────────────────
$flashMessages = Helpers::getFlash();

// ── Config values for the view ────────────────────────────────────────────────
$appName  = Config::get('config', 'app.name') ?? 'AffsCash';
$appLogo  = Config::get('config', 'app.logo') ?: Config::get('config', 'app.login_logo');
$logoSrc  = $appLogo ?: '/logoo.png';

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<?php if (Turnstile::isEnabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#EEF2FF 0%,#F0FDF4 100%)}
  .auth-box{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);width:100%;max-width:420px;overflow:hidden}
  .auth-header{background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:32px;text-align:center;color:#fff}
  .auth-header h1{font-size:22px;font-weight:800}
  .auth-header p{font-size:13px;opacity:.85;margin-top:4px}
  .auth-body{padding:32px}
  .auth-footer{padding:16px 32px;background:var(--bg);border-top:1px solid var(--border);text-align:center;font-size:13px;color:var(--text-muted)}
  .auth-footer a{color:var(--primary);font-weight:600}
  .logo-icon{font-size:36px;margin-bottom:12px}
  .back-home{display:block;margin-top:10px;font-size:12px;color:var(--text-muted)!important;font-weight:400!important}
</style>
</head>
<body>
<div class="auth-box">

  <!-- Header -->
  <div class="auth-header">
    <?php if ($appLogo): ?>
      <img src="<?= htmlspecialchars($appLogo, ENT_QUOTES, 'UTF-8') ?>"
           alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
           style="max-height:56px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto">
    <?php else: ?>
      <div class="logo-icon">&#127760;</div>
    <?php endif; ?>
    <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Sign in to your account</p>
  </div>

  <!-- Body -->
  <div class="auth-body">

    <!-- Flash messages -->
    <?php foreach ($flashMessages as $flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'info' ?>">
        <?= nl2br(htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8')) ?>
      </div>
    <?php endforeach; ?>

    <!-- Error -->
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Login form — posts back to THIS file -->
    <form method="POST" action="/tracker/login.php">
      <?= Helpers::csrf() ?>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="you@example.com" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>

      <?php if (Turnstile::isEnabled()): ?>
        <div style="margin-top:16px;display:flex;justify-content:center">
          <div class="cf-turnstile"
               data-sitekey="<?= htmlspecialchars(Turnstile::siteKey(), ENT_QUOTES, 'UTF-8') ?>"
               data-callback="onTurnstileSuccess"
               data-expired-callback="onTurnstileExpired"
               data-theme="light"></div>
        </div>
        <button type="submit" id="loginSubmitBtn" class="btn btn-primary"
                style="width:100%;margin-top:14px;opacity:.5;cursor:not-allowed" disabled>
          Sign In
        </button>
      <?php else: ?>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
          Sign In
        </button>
      <?php endif; ?>
    </form>

  </div>

  <!-- Footer -->
  <div class="auth-footer">
    New here? <a href="/tracker/register.php">Create an account</a>
    &nbsp;|&nbsp; Advertiser? <a href="/register/advertiser">Register here</a>
    <a href="/" class="back-home">&#8592; Back to Home</a>
  </div>

</div>

<script src="/assets/js/app.js"></script>
<?php if (Turnstile::isEnabled()): ?>
<script>
function onTurnstileSuccess(token) {
  var btn = document.getElementById('loginSubmitBtn');
  if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
}
function onTurnstileExpired() {
  var btn = document.getElementById('loginSubmitBtn');
  if (btn) { btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed'; }
}
</script>
<?php endif; ?>
</body>
</html>
