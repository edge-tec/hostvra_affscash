<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
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
.pw-strength { height:4px; border-radius:2px; margin-top:6px; transition:width .3s,background .3s; width:0; }
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
        <p>Set your new password</p>
    </div>

    <div class="auth-body">
        <?php if (!$tokenValid): ?>
            <!-- Invalid / expired token -->
            <div class="alert alert-error"><?= Helpers::e($error) ?></div>
            <div style="text-align:center;margin-top:20px">
                <a href="/forgot-password" class="btn btn-primary" style="display:inline-block">
                    Request a New Reset Link
                </a>
            </div>

        <?php else: ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= Helpers::e($error) ?></div>
            <?php endif; ?>

            <p style="font-size:13px;color:var(--text-muted);margin:0 0 20px">
                Choose a strong password of at least 8 characters.
            </p>

            <form method="POST" action="/reset-password?token=<?= urlencode($token) ?>" id="resetForm">
                <?= Helpers::csrf() ?>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
                    <div id="pwStrengthBar" class="pw-strength"></div>
                    <div id="pwStrengthLabel" style="font-size:11px;color:#94A3B8;margin-top:4px"></div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                           placeholder="Repeat your new password" required minlength="8" autocomplete="new-password">
                    <div id="pwMatchHint" style="font-size:11px;margin-top:4px"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
                    Set New Password
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="auth-footer">
        <a href="/login">&#8592; Back to Sign In</a>
    </div>
</div>
<script src="/assets/js/app.js"></script>
<?php if ($tokenValid): ?>
<script>
(function(){
    var pw   = document.getElementById('password');
    var conf = document.getElementById('password_confirm');
    var bar  = document.getElementById('pwStrengthBar');
    var lbl  = document.getElementById('pwStrengthLabel');
    var hint = document.getElementById('pwMatchHint');

    function strength(v){
        var s=0;
        if(v.length>=8)s++;
        if(v.length>=12)s++;
        if(/[A-Z]/.test(v))s++;
        if(/[0-9]/.test(v))s++;
        if(/[^A-Za-z0-9]/.test(v))s++;
        return s;
    }
    pw.addEventListener('input',function(){
        var s=strength(this.value);
        var pct=[0,25,50,75,90,100][s];
        var col=['#CBD5E1','#EF4444','#F59E0B','#10B981','#4F46E5','#059669'][s];
        var txt=['','Weak','Fair','Good','Strong','Very Strong'][s];
        bar.style.width=pct+'%';
        bar.style.background=col;
        lbl.textContent=txt;
        lbl.style.color=col;
        checkMatch();
    });
    conf.addEventListener('input', checkMatch);
    function checkMatch(){
        if(!conf.value) { hint.textContent=''; return; }
        if(pw.value===conf.value){
            hint.textContent='✓ Passwords match';
            hint.style.color='#10B981';
        } else {
            hint.textContent='✗ Passwords do not match';
            hint.style.color='#EF4444';
        }
    }
    document.getElementById('resetForm').addEventListener('submit',function(e){
        if(pw.value!==conf.value){
            e.preventDefault();
            hint.textContent='✗ Passwords do not match';
            hint.style.color='#EF4444';
            conf.focus();
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
