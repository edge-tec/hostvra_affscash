
<footer>
  <div class="container">
    <img src="<?= $logoSrc ?>" alt="<?= $appName ?>" height="36" style="margin-bottom:16px;filter:brightness(0) invert(1);">
    <div class="footer-links">
      <a href="/">Home</a>
      <a href="/offers">Offers</a>
      <a href="/#about">About</a>
      <a href="/blog">Blog</a>
      <a href="/reviews">Reviews</a>
      <a href="/#contact">Contact</a>
      <a href="/register/affiliate">Sign Up</a>
      <a href="/login">Login</a>
      <a href="/terms-of-service" target="_blank">Terms &amp; Conditions</a>
      <a href="/privacy-policy" target="_blank">Privacy Policy</a>
    </div>
    <div style="display:flex;justify-content:center;gap:18px;margin:18px 0">
      <a href="https://t.me/affscashnet" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-telegram"></i></a>
      <a href="https://www.linkedin.com/company/89707239/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-linkedin"></i></a>
      <a href="https://www.facebook.com/affscash/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-facebook"></i></a>
    </div>
    <p style="font-size:12px;color:rgba(255,255,255,.35)">&copy; 2019 &ndash; <?= date('Y') ?> EdgeSoft Ltd. All Rights Reserved. | <?= $appName ?> CPA Affiliate Network</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Hamburger menu
  var hb = document.getElementById('hamburger');
  var mm = document.getElementById('mobile-menu');
  if (hb && mm) {
    hb.addEventListener('click', function() {
      mm.classList.toggle('open');
      hb.classList.toggle('open');
    });
  }
  // Sticky header shadow on scroll
  window.addEventListener('scroll', function() {
    var h = document.querySelector('.site-header');
    if (h) h.style.boxShadow = window.scrollY > 10 ? '0 4px 30px rgba(124,58,237,.13)' : '0 2px 24px rgba(124,58,237,.08)';
  });

  // ── Shared Public 3D Background Particles Animation (3D Connected Networking Constellation) ──
  (function() {
    var canvas = document.getElementById('publicParticlesCanvas');
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
    
    var numParticles = 90;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * 800,
        y: (Math.random() - 0.5) * 500,
        z: (Math.random() - 0.5) * 400,
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
      particles.forEach(function(p) {
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        if (Math.abs(p.x) > 450) p.vx *= -1;
        if (Math.abs(p.y) > 300) p.vy *= -1;
        if (Math.abs(p.z) > 200) p.vz *= -1;
        
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
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
