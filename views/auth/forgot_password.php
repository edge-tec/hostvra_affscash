<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="canonical" href="<?= htmlspecialchars(rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?'), '/'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="/assets/css/app.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg,#F8FAFC 0%,#E2E8F0 100%); }
.auth-box { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,.08); width:100%; max-width:420px; overflow:hidden; }
.auth-header { padding:32px 32px 16px; color:var(--text); text-align:center; }
.auth-header h1 { font-size:22px; font-weight:800; color:var(--text); }
.auth-header p { font-size:14px; color:var(--text-muted); margin-top:6px; }
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
        <img src="<?= Helpers::e($loginLogo) ?>" alt="Logo" style="max-height:56px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto">
        <?php else: ?>
        <div class="logo-icon">&#128274;</div>
        <?php endif; ?>
        <h1><?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></h1>
        <p>Reset your password</p>
    </div>

    <div class="auth-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= Helpers::e($success) ?></div>
            <div style="text-align:center;margin-top:16px">
                <a href="/login" style="color:var(--primary);font-weight:600;font-size:14px">&#8592; Back to Sign In</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= Helpers::e($error) ?></div>
            <?php endif; ?>

            <p style="font-size:13px;color:var(--text-muted);margin:0 0 20px">Enter the email address linked to your account and we will send you a password reset link.</p>

            <form method="POST">
                <?= Helpers::csrf() ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="you@example.com" required autofocus
                           value="<?= Helpers::e($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
                    Send Reset Link
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="auth-footer">
        Remembered your password? <a href="/login">Sign In</a>
        <div style="margin-top:10px">
            <a href="/" style="color:var(--text-muted);font-size:12px">&#8592; Back to Home</a>
        </div>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
