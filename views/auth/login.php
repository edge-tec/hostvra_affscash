<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<?php $_authBgKey = 'auth_bg_login';  require BASE_PATH . '/views/partials/auth_bg.php'; ?>
<?php if (Turnstile::isEnabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg,#EEF2FF 0%,#F0FDF4 100%); }
.auth-box { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); width:100%; max-width:420px; overflow:hidden; }
.auth-header { background:linear-gradient(135deg,#4F46E5,#7C3AED); padding:32px; text-align:center; color:#fff; }
.auth-header h1 { font-size:22px; font-weight:800; }
.auth-header p { font-size:13px; opacity:.85; margin-top:4px; }
.auth-body { padding:32px; }
.auth-footer { padding:16px 32px; background:var(--bg); border-top:1px solid var(--border); text-align:center; font-size:13px; color:var(--text-muted); }
.auth-footer a { color:var(--primary); font-weight:600; }
.logo-icon { font-size:36px; margin-bottom:12px; }
</style>
</head>
<body>
<div class="auth-theme-picker"><?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?></div>
<div class="auth-box">
    <div class="auth-header">
        <?php
        $loginLogo      = Config::get('config','app.login_logo') ?: Config::get('config','app.logo');
        $loginLogoWhite = !empty(Config::get('config','app.login_logo_white'));
        if ($loginLogo): ?>
        <img src="<?= Helpers::e($loginLogo) ?>" alt="Logo" style="max-height:56px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto<?= $loginLogoWhite ? ';filter:brightness(0) invert(1)' : '' ?>">
        <?php else: ?>
        <div class="logo-icon">&#127760;</div>
        <?php endif; ?>
        <h1><?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></h1>
        <p>Sign in to your account</p>
    </div>
    <div class="auth-body">
        <?php foreach (Helpers::getFlash() as $f): ?>
        <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'info' ?>">
            <?= nl2br(Helpers::e($f['message'])) ?>
        </div>
        <?php endforeach; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= Helpers::csrf() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required autofocus value="<?= Helpers::e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <label for="password" style="margin:0">Password</label>
                    <a href="/forgot-password" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <?php if (Turnstile::isEnabled()): ?>
            <!-- Cloudflare Turnstile widget -->
            <div style="margin-top:16px;display:flex;justify-content:center">
                <div class="cf-turnstile"
                     data-sitekey="<?= Helpers::e(Turnstile::siteKey()) ?>"
                     data-callback="onTurnstileSuccess"
                     data-expired-callback="onTurnstileExpired"
                     data-theme="light">
                </div>
            </div>
            <button type="submit" id="loginSubmitBtn" class="btn btn-primary"
                    style="width:100%;margin-top:14px;opacity:.5;cursor:not-allowed"
                    disabled>
                Sign In
            </button>
            <?php else: ?>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">Sign In</button>
            <?php endif; ?>
        </form>
    </div>
    <div class="auth-footer">
        Affiliate? <a href="/register/affiliate">Register here</a>
        <?php if ((Config::get('config','app.advertiser_registration_enabled') ?? '1') === '1'): ?>
        &nbsp;|&nbsp; Advertiser? <a href="/register/advertiser">Register here</a>
        <?php endif; ?>
        <div style="margin-top:10px;font-size:12px">
            <a href="/privacy-policy" target="_blank" style="color:var(--text-muted)">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="/TRC.html" target="_blank" style="color:var(--text-muted)">Terms &amp; Conditions</a>
        </div>
        <div style="margin-top:10px"><a href="/" style="color:var(--text-muted);font-size:12px">&#8592; Back to Home</a></div>
    </div>
</div>
<script src="/assets/js/app.js"></script>
<?php if (Turnstile::isEnabled()): ?>
<script>
function onTurnstileSuccess(token) {
    var btn = document.getElementById('loginSubmitBtn');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}
function onTurnstileExpired() {
    var btn = document.getElementById('loginSubmitBtn');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '.5';
        btn.style.cursor = 'not-allowed';
    }
}
</script>
<?php endif; ?>
</body>
</html>
