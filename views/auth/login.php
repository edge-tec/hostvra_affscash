<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="canonical" href="<?= htmlspecialchars(rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?'), '/'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="/assets/css/app.min.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<?php $_authBgKey = 'auth_bg_login';  require BASE_PATH . '/views/partials/auth_bg.php'; ?>
<?= RecaptchaService::renderHeadScript() ?>
<?php if (Turnstile::isEnabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
body {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
<?php if (empty($_authBgActive)): ?>
  background: #05020c !important;
  background-image: 
      radial-gradient(ellipse 50% 60% at 80% 30%,rgba(124,58,237,.08),transparent),
      radial-gradient(ellipse 40% 45% at 10% 70%,rgba(232,25,122,.06),transparent) !important;
<?php endif; ?>
  color: #fff !important;
  overflow-x: hidden;
}
.auth-box {
  background: rgba(15, 10, 36, 0.65) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-radius: 20px !important;
  box-shadow: 0 20px 50px rgba(0,0,0,0.35), inset 0 1px 1px rgba(255,255,255,0.15) !important;
  backdrop-filter: blur(20px) !important;
  -webkit-backdrop-filter: blur(20px) !important;
  width: 100%;
  max-width: 420px;
  overflow: hidden;
  position: relative;
  z-index: 1;
}
.auth-header { padding:32px 32px 16px; text-align:center; color:#fff; }
.auth-header h1 { font-size:22px; font-weight:800; color:#fff; }
.auth-header p { font-size:14px; color:rgba(255,255,255,0.6); margin-top:6px; }
.auth-body { padding:32px; }
.auth-footer { padding:16px 32px; background:rgba(15,10,36,0.3) !important; border-top:1px solid rgba(255,255,255,0.06) !important; text-align:center; font-size:13px; color:rgba(255,255,255,0.5) !important; }
.auth-footer a { color:#a855f7; font-weight:600; text-decoration:none; }
.logo-icon { font-size:36px; margin-bottom:12px; }
.form-label, label { color: rgba(255,255,255,0.8) !important; font-size:12px !important; }
.form-control, .form-control-custom, input[type="text"], input[type="password"], input[type="email"] {
  background: rgba(255,255,255,0.03) !important;
  border: 1px solid rgba(255,255,255,0.08) !important;
  color: #fff !important;
}
.form-control:focus, .form-control-custom:focus {
  border-color: rgba(124,58,237,0.4) !important;
  box-shadow: 0 0 12px rgba(124,58,237,0.2) !important;
}
/* Autofill override: prevents browser autofill from making the box background white */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active {
  -webkit-text-fill-color: #ffffff !important;
  -webkit-box-shadow: 0 0 0 1000px #0b071e inset !important;
  box-shadow: 0 0 0 1000px #0b071e inset !important;
  transition: background-color 5000s ease-in-out 0s;
}
.btn-primary, button[type="submit"] {
  background: linear-gradient(135deg,#7c3aed 0%,#3b82f6 50%,#0ea5e9 100%) !important;
  border: none !important;
  color: #fff !important;
  box-shadow: 0 4px 16px rgba(124,58,237,0.3) !important;
}
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

        <form method="POST" action="/login"
              <?php if (RecaptchaService::isEnabled()): ?>
              data-recaptcha-sitekey="<?= Helpers::e(RecaptchaService::siteKey()) ?>"
              data-recaptcha-action="login"
              <?php endif; ?>>
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
            <div class="form-group" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <input type="checkbox" id="remember" name="remember" value="1" <?= (isset($_POST['remember']) && $_POST['remember'] === '1') ? 'checked' : '' ?>>
                <label for="remember" style="margin:0;font-size:13px;font-weight:500;">Remember Me for 30 days</label>
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
        <div style="margin-top:12px;font-size:11.5px;display:flex;flex-wrap:wrap;justify-content:center;gap:6px 12px;opacity:0.85">
            <a href="/affiliate-agreement" target="_blank" style="color:var(--text-muted);text-decoration:none">Affiliate Agreement</a>
            <a href="/anti-fraud-policy" target="_blank" style="color:var(--text-muted);text-decoration:none">Anti-Fraud Policy</a>
            <a href="/gdpr-compliance-policy" target="_blank" style="color:var(--text-muted);text-decoration:none">GDPR Compliance</a>
            <a href="/refund-payment-policy" target="_blank" style="color:var(--text-muted);text-decoration:none">Refund Policy</a>
            <a href="/cookie-policy" target="_blank" style="color:var(--text-muted);text-decoration:none">Cookie Policy</a>
            <a href="/dashboard-disclaimers" target="_blank" style="color:var(--text-muted);text-decoration:none">Dashboard Disclaimers</a>
            <a href="/privacy-policy" target="_blank" style="color:var(--text-muted);text-decoration:none">Privacy Policy</a>
            <a href="/terms-of-service" target="_blank" style="color:var(--text-muted);text-decoration:none">Terms &amp; Conditions</a>
        </div>
        <div style="margin-top:10px"><a href="/" style="color:var(--text-muted);font-size:12px">&#8592; Back to Home</a></div>
    </div>
</div>
<script src="/assets/js/app.min.js"></script>
<script src="/assets/js/recaptcha.js"></script>
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
<?php if (empty($_authBgActive)): ?>
<canvas id="authParticlesCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
<script>
  (function() {
    var canvas = document.getElementById('authParticlesCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var particles = [];
    var w, h;
    var mouse = { x: null, y: null, active: false };
    
    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);
    
    window.addEventListener('mousemove', function(e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      mouse.active = true;
    });
    window.addEventListener('mouseleave', function() {
      mouse.active = false;
    });
    
    var numParticles = 80;
    var initLimitX = Math.max(450, w * 1.2);
    var initLimitY = Math.max(300, h * 1.2);
    var initLimitZ = 200;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * initLimitX * 2,
        y: (Math.random() - 0.5) * initLimitY * 2,
        z: (Math.random() - 0.5) * initLimitZ * 2,
        vx: (Math.random() - 0.5) * 0.4,
        vy: (Math.random() - 0.5) * 0.4,
        vz: (Math.random() - 0.5) * 0.4,
        size: Math.random() * 2 + 1.5,
        color: Math.random() > 0.5 ? 'rgba(124, 58, 237, 0.35)' : 'rgba(14, 165, 233, 0.3)'
      });
    }
    
    var fov = 400;
    
    function draw() {
      ctx.clearRect(0, 0, w, h);
      
      var projected = [];
      var limitX = Math.max(450, w * 1.2);
      var limitY = Math.max(300, h * 1.2);
      var limitZ = 200;
      particles.forEach(function(p) {
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        if (Math.abs(p.x) > limitX) p.vx *= -1;
        if (Math.abs(p.y) > limitY) p.vy *= -1;
        if (Math.abs(p.z) > limitZ) p.vz *= -1;
        
        var rotY = 0.0006;
        var cosY = Math.cos(rotY), sinY = Math.sin(rotY);
        var x1 = p.x * cosY - p.z * sinY;
        var z1 = p.z * cosY + p.x * sinY;
        p.x = x1; p.z = z1;
        
        var rotX = 0.0004;
        var cosX = Math.cos(rotX), sinX = Math.sin(rotX);
        var y1 = p.y * cosX - p.z * sinX;
        var z2 = p.z * cosX + p.y * sinX;
        p.y = y1; p.z = z2;
        
        var scale = fov / (fov + p.z);
        var px = (p.x * scale) + (w / 2);
        var py = (p.y * scale) + (h / 2);
        
        projected.push({
          x: px,
          y: py,
          z: p.z,
          size: p.size * scale,
          color: p.color
        });
      });
      
      for (var a = 0; a < projected.length; a++) {
        var pa = projected[a];
        for (var b = a + 1; b < projected.length; b++) {
          var pb = projected[b];
          var dx = pa.x - pb.x;
          var dy = pa.y - pb.y;
          var dist = Math.hypot(dx, dy);
          
          if (dist < 110) {
            var opacity = (1 - (dist / 110)) * 0.15 * (1 - (pa.z + pb.z) / 400);
            if (opacity > 0) {
              ctx.beginPath();
              ctx.moveTo(pa.x, pa.y);
              ctx.lineTo(pb.x, pb.y);
              ctx.strokeStyle = 'rgba(124, 58, 237, ' + opacity + ')';
              ctx.lineWidth = 0.8 * (1 - (pa.z + pb.z) / 400);
              ctx.stroke();
            }
          }
        }
        
        if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
          ctx.fillStyle = pa.color;
          ctx.beginPath();
          ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
          ctx.fill();
        }
      }
      
      if (mouse.active) {
        projected.forEach(function(p) {
          var dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
          if (dist < 150) {
            var opacity = (1 - (dist / 150)) * 0.22;
            ctx.beginPath();
            ctx.moveTo(mouse.x, mouse.y);
            ctx.lineTo(p.x, p.y);
            ctx.strokeStyle = 'rgba(14, 165, 233, ' + opacity + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        });
      }
      
      requestAnimationFrame(draw);
    }
    draw();
  })();
</script>
<?php endif; ?>
</body>
</html>
