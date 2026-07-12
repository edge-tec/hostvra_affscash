<style>
  :root {
      --ink:        #ffffff !important;
      --ink-soft:   rgba(255,255,255,0.75) !important;
      --ink-muted:  rgba(255,255,255,0.5) !important;
      --teal:       #7c3aed !important;
      --teal-light: rgba(124,58,237,0.15) !important;
      --teal-mid:   #a855f7 !important;
      --blue:       #0ea5e9 !important;
      --border:     rgba(255,255,255,0.06) !important;
      --bg:         #05020c !important;
      --white:      rgba(15,10,36,0.65) !important;
      --accent:     #e8197a !important;
      --accent2:    #0ea5e9 !important;
      --radius:     16px !important;
      --p:          #7c3aed !important;
      --pl:         #ec4899 !important;
      --card:       rgba(15,10,36,0.65) !important;
      --border2:    rgba(255,255,255,0.08) !important;
      --text:       #ffffff !important;
      --muted:      rgba(255,255,255,0.7) !important;
      --muted2:     rgba(255,255,255,0.55) !important;
      --grad-primary: linear-gradient(135deg,#7c3aed 0%,#ec4899 100%) !important;
  }
  body {
      background: #05020c !important;
      background-image: 
          radial-gradient(ellipse 50% 60% at 80% 30%,rgba(124,58,237,.06),transparent),
          radial-gradient(ellipse 40% 45% at 10% 70%,rgba(232,25,122,.05),transparent) !important;
      color: #fff !important;
  }
  
  /* Topnav / Nav Header Matches Landing Page Floating Glass */
  .topnav, nav {
      position: fixed !important;
      top: 16px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      width: 92% !important;
      max-width: 1280px !important;
      z-index: 1000 !important;
      background: rgba(15,10,36,0.7) !important;
      backdrop-filter: blur(20px) !important;
      -webkit-backdrop-filter: blur(20px) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      border-radius: 24px !important;
      padding: 0 24px !important;
      box-shadow: 0 12px 40px rgba(0,0,0,0.3) !important;
      height: 68px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
  }
  
  .topnav-brand img, .landing-logo-img, .nav-logo img, nav .logo, nav img {
      filter: brightness(0) invert(1) !important;
      max-height: 40px !important;
      width: auto !important;
      display: block !important;
  }
  
  .topnav-brand, .nav-logo, nav a:first-child {
      color: #fff !important;
      font-weight: 700 !important;
      text-decoration: none !important;
      background: none !important;
      -webkit-text-fill-color: #fff !important;
  }
  
  .topnav-links a, .nav-links a, nav a, .nav-actions a {
      color: #fff !important;
      font-size: 13px !important;
      font-weight: 500 !important;
      padding: 7px 11px !important;
      border-radius: 8px !important;
      transition: all .22s !important;
      text-decoration: none !important;
      background: none !important;
  }
  
  .topnav-links a:hover, nav a:hover, .nav-actions a:hover {
      background: rgba(124,58,237,0.15) !important;
      color: #0ea5e9 !important;
  }
  
  .btn-login, .btn-primary, .btn.btn-primary, .topnav-links a.cta {
      background: linear-gradient(135deg,#7c3aed 0%,#3b82f6 50%,#0ea5e9 100%) !important;
      color: #fff !important;
      padding: 8px 20px !important;
      border-radius: 8px !important;
      font-weight: 600 !important;
      box-shadow: 0 4px 16px rgba(124,58,237,0.3) !important;
      border: none !important;
  }
  
  .btn-signup, .btn-ghost, .btn.btn-ghost {
      background: rgba(255,255,255,0.03) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      color: #fff !important;
      border-radius: 8px !important;
      padding: 8px 18px !important;
  }
  
  /* Layout Spacing */
  .header, .page-hero {
      padding-top: 130px !important;
      padding-bottom: 40px !important;
      background: transparent !important;
      border-bottom: 1px solid rgba(255,255,255,0.06) !important;
  }
  .header h1, .page-hero h1 {
      color: #fff !important;
  }
  
  /* Sidebar and content glassmorphism */
  .toc, .toc-wrap, .section, .contact-card, .sidebar-promo, .toc-progress, .toc-scroll {
      background: rgba(15,10,36,0.65) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      border-radius: 18px !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1) !important;
      color: #fff !important;
  }
  
  .toc-title, .section-head h2 {
      color: #fff !important;
      border-bottom-color: rgba(255,255,255,0.08) !important;
  }
  
  .section-icon, .toc-title-icon {
      background: rgba(124,58,237,0.15) !important;
      color: #a855f7 !important;
      border: 1px solid rgba(124,58,237,0.22) !important;
      border-radius: 8px !important;
  }
  
  .toc-num {
      background: rgba(255,255,255,0.05) !important;
      color: rgba(255,255,255,0.6) !important;
  }
  
  .toc-list a {
      color: rgba(255,255,255,0.7) !important;
  }
  .toc-list a:hover {
      background: rgba(124,58,237,0.15) !important;
      color: #0ea5e9 !important;
  }
  .toc-list a.active {
      color: #fff !important;
      font-weight: 700 !important;
  }
  
  .contact-card-value {
      color: #0ea5e9 !important;
  }
  
  /* Footer layout */
  .footer, footer {
      background: #05020c !important;
      border-top: 1px solid rgba(255,255,255,0.06) !important;
      color: rgba(255,255,255,0.4) !important;
      padding: 40px 0 !important;
  }
  .footer a, footer a {
      color: rgba(255,255,255,0.55) !important;
      text-decoration: none !important;
  }
  /* Accordion sections glassmorphism overrides */
  .tos-section, .section {
      background: rgba(15,10,36,0.65) !important;
      border: 1px solid rgba(255,255,255,0.08) !important;
      border-radius: 18px !important;
      backdrop-filter: blur(12px) !important;
      -webkit-backdrop-filter: blur(12px) !important;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 1px 1px rgba(255,255,255,0.1) !important;
      color: #fff !important;
      margin-bottom: 20px !important;
      overflow: hidden !important;
  }
  
  .accordion-header {
      background: rgba(255,255,255,0.02) !important;
      border-bottom: 1px solid rgba(255,255,255,0.06) !important;
      color: #fff !important;
  }
  .accordion-header:hover {
      background: rgba(124,58,237,0.08) !important;
  }
  
  .accordion-content {
      background: transparent !important;
      color: rgba(255,255,255,0.85) !important;
  }
  .accordion-content p {
      color: rgba(255,255,255,0.85) !important;
  }
  
  /* Alert / warning notice and callout blocks dark theme contrast fixes */
  .notice-box {
      background: rgba(239, 68, 68, 0.1) !important;
      border: 1px solid rgba(239, 68, 68, 0.3) !important;
      border-radius: 12px !important;
  }
  .notice-box p {
      color: #fca5a5 !important;
  }
  
  .callout.info    { background: rgba(59, 130, 246, 0.1) !important; border: 1px solid rgba(59, 130, 246, 0.3) !important; color: #93c5fd !important; }
  .callout.warning { background: rgba(245, 158, 11, 0.1) !important; border: 1px solid rgba(245, 158, 11, 0.3) !important; color: #fde047 !important; }
  .callout.success { background: rgba(16, 185, 129, 0.1) !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; color: #6ee7b7 !important; }
  .callout.teal    { background: rgba(20, 184, 166, 0.1) !important; border: 1px solid rgba(20, 184, 166, 0.3) !important; color: #2dd4bf !important; }
  .callout p, .callout { color: inherit !important; }

  /* Global introductory notice block styling */
  .notice-block {
      background: rgba(255, 255, 255, 0.03) !important;
      border: 1px solid rgba(255, 255, 255, 0.08) !important;
      border-radius: 12px !important;
      color: rgba(255, 255, 255, 0.85) !important;
      backdrop-filter: blur(8px) !important;
      -webkit-backdrop-filter: blur(8px) !important;
  }
  .notice-block strong {
      color: #fff !important;
  }
</style>

<!-- Canvas for full-page background network constellation animation -->
<canvas id="policyParticlesCanvas" style="position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:0"></canvas>

<script>
  (function() {
    var canvas = document.getElementById('policyParticlesCanvas');
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
