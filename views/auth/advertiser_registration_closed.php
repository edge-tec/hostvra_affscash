<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Registration Closed — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<?php $_authBgKey = 'auth_bg_advreg'; require BASE_PATH . '/views/partials/auth_bg.php'; ?>
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px; background:linear-gradient(135deg,#EEF2FF 0%,#F0FDF4 100%); }
.auth-box { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); width:100%; max-width:480px; overflow:hidden; text-align:center; }
.closed-icon { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,#FCA5A5,#EF4444); display:flex; align-items:center; justify-content:center; margin:32px auto 18px; box-shadow:0 8px 24px rgba(239,68,68,.30); }
.closed-icon svg { width:32px; height:32px; color:#fff; }
.closed-title { font-size:20px; font-weight:800; color:var(--text); margin:0 6px; }
.closed-body { padding:0 32px 28px; }
.closed-msg { font-size:14.5px; color:var(--text-muted); line-height:1.55; margin:14px 0 22px; white-space:pre-line; }
.closed-actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
.closed-actions .btn { padding:10px 18px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; }
.btn-back { background:var(--primary); color:#fff; }
.btn-secondary-ghost { background:var(--bg); color:var(--text); border:1px solid var(--border); }
.auth-footer { padding:14px 24px; background:var(--bg); border-top:1px solid var(--border); font-size:12.5px; color:var(--text-muted); }
.auth-footer a { color:var(--primary); font-weight:600; text-decoration:none; }
</style>
</head>
<body>
<div class="auth-theme-picker"><?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?></div>
<div class="auth-box">
    <div class="closed-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <h1 class="closed-title">Advertiser Registration Closed</h1>
    <div class="closed-body">
        <div class="closed-msg"><?= Helpers::e($_advClosedMsg ?? 'Advertiser registration is currently closed.') ?></div>
        <div class="closed-actions">
            <a href="/login" class="btn btn-back">Sign In</a>
            <a href="/" class="btn btn-secondary-ghost">← Back to Home</a>
        </div>
    </div>
    <div class="auth-footer">
        Affiliate? <a href="/register/affiliate">Register as an affiliate instead</a>
    </div>
</div>
</body>
</html>
