<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Two-Step Verification — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<?php if ($fav = Config::get('config','app.favicon')): ?><link rel="icon" href="<?= Helpers::e($fav) ?>"><?php endif; ?>
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:linear-gradient(135deg,#F8FAFC 0%,#E2E8F0 100%); }
.auth-box { background:#fff; border:1px solid var(--border); border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,.08); width:100%; max-width:420px; overflow:hidden; }
.auth-header { padding:32px 32px 16px; color:var(--text); text-align:center; }
.auth-header h1 { font-size:22px; font-weight:800; color:var(--text); }
.auth-header p { font-size:14px; color:var(--text-muted); margin-top:6px; line-height:1.5 }
.auth-body { padding:32px; }
.auth-footer { padding:16px 32px; background:var(--bg); border-top:1px solid var(--border); text-align:center; font-size:13px; color:var(--text-muted); }
.auth-footer a { color:var(--primary); font-weight:600; }
.otp-input {
    text-align: center;
    font-size: 32px;
    font-weight: 800;
    letter-spacing: 10px;
    padding: 16px 20px;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    width: 100%;
    color: #4F46E5;
    background: #F8FAFC;
    transition: border-color .2s, box-shadow .2s;
}
.otp-input:focus {
    outline: none;
    border-color: #4F46E5;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(79,70,229,.12);
}
.shield-icon { font-size:40px; margin-bottom:12px; display:block; }
.countdown { font-size:12px; color:#94A3B8; text-align:center; margin-top:12px; }
</style>
</head>
<body>
<div class="auth-box">
    <div class="auth-header">
        <?php
        $loginLogo      = Config::get('config','app.login_logo') ?: Config::get('config','app.logo');
        $loginLogoWhite = !empty(Config::get('config','app.login_logo_white'));
        if ($loginLogo):
        ?>
        <img src="<?= Helpers::e($loginLogo) ?>" alt="Logo" style="max-height:56px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto">
        <?php else: ?>
        <span class="shield-icon">🛡️</span>
        <?php endif; ?>
        <h1>Two-Step Verification</h1>
        <?php if (($twoFaMethod ?? 'email') === 'totp'): ?>
        <p>Open <strong>Google Authenticator</strong> (or Authy / Microsoft Authenticator) and enter the 6-digit code for<br><strong><?= Helpers::e($_SESSION['2fa_pending_email'] ?? '') ?></strong></p>
        <?php else: ?>
        <p>We sent a 6-digit code to<br><strong><?= Helpers::e($_SESSION['2fa_pending_email'] ?? '') ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="auth-body">
        <?php if ($error): ?>
        <div class="alert alert-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= Helpers::csrf() ?>
            <div class="form-group" style="margin-bottom:8px">
                <label style="text-align:center;display:block;margin-bottom:10px;font-size:13px;color:#64748B">Enter your verification code</label>
                <input type="text" name="otp_code" class="otp-input" maxlength="6" pattern="[0-9]{6}" placeholder="——————" autocomplete="one-time-code" inputmode="numeric" autofocus required>
            </div>
            <p class="countdown" id="countdown-text"></p>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px">Verify & Sign In</button>
        </form>
    </div>
    <div class="auth-footer">
        Didn't receive the code? &nbsp;<a href="/login">Back to Login</a>
    </div>
</div>
<script>
// Countdown timer (10 min from page load)
var expires = <?= (int)($_SESSION['2fa_expires'] ?? time()+600) ?>;
function tick() {
    var remaining = expires - Math.floor(Date.now()/1000);
    var el = document.getElementById('countdown-text');
    if (remaining <= 0) {
        el.textContent = 'Code expired. Please sign in again.';
        el.style.color = '#EF4444';
    } else {
        var m = Math.floor(remaining/60), s = remaining%60;
        el.textContent = 'Code expires in ' + m + ':' + (s<10?'0':'') + s;
    }
}
tick();
setInterval(tick, 1000);

// Auto-submit when 6 digits entered
document.querySelector('[name=otp_code]').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g,'').slice(0,6);
    if (e.target.value.length === 6) e.target.closest('form').submit();
});
</script>
</body>
</html>
