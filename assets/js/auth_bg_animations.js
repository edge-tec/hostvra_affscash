/* ─────────────────────────────────────────────────────────────
 * Auth-page particles animation.
 *
 * Only spins up when the page actually contains
 *   .auth-bg-anim.bg-anim-particles  (set by auth_bg.php).
 * Everything else (gradient, mesh, glass, shapes, glow) is pure
 * CSS and does not need JS — so this file stays tiny and ships
 * only the canvas renderer.
 *
 * Safeguards baked in:
 *   - early-exit when prefers-reduced-motion is set
 *   - pauses via cancelAnimationFrame when the tab is hidden
 *   - caps particle count for mobile / small viewports
 *   - uses requestAnimationFrame, no setInterval
 *   - cleanly handles resize via ResizeObserver
 * ────────────────────────────────────────────────────────────── */
(function(){
    'use strict';

    var host = document.querySelector('.auth-bg-anim.bg-anim-particles');
    if (!host) return;
    if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var canvas = document.createElement('canvas');
    host.appendChild(canvas);
    var ctx = canvas.getContext('2d', { alpha:true });
    if (!ctx) return;

    var DPR = Math.min(window.devicePixelRatio || 1, 2); // cap DPR cost on retina phones
    var W = 0, H = 0;
    var particles = [];
    var rafId = null;
    var speed = parseFloat(host.dataset.speed || '5') || 5; // 1..10

    function resize(){
        W = host.clientWidth  || window.innerWidth;
        H = host.clientHeight || window.innerHeight;
        canvas.width  = W * DPR;
        canvas.height = H * DPR;
        canvas.style.width  = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
        // Particle budget scales with viewport area, capped so phones
        // stay smooth (≈80) and ultra-wide desktops don't go over 180.
        var target = Math.round(Math.min(180, Math.max(40, (W * H) / 14000)));
        if (particles.length < target) {
            while (particles.length < target) particles.push(spawn());
        } else if (particles.length > target) {
            particles.length = target;
        }
    }

    function spawn(){
        return {
            x: Math.random() * W,
            y: Math.random() * H,
            vx: (Math.random() - .5) * (.25 + speed * .05),
            vy: (Math.random() - .5) * (.25 + speed * .05),
            r:  .8 + Math.random() * 2.2,
            a:  .35 + Math.random() * .45
        };
    }

    function step(){
        ctx.clearRect(0, 0, W, H);
        // Soft connecting lines (looks expensive, costs ~nothing because
        // we early-exit on distance > 120 before stroking).
        for (var i = 0; i < particles.length; i++){
            var p = particles[i];
            p.x += p.vx;
            p.y += p.vy;
            // Wrap around the viewport — cheaper than reflection and
            // keeps the field looking continuous.
            if (p.x < -10)    p.x = W + 10;
            if (p.x > W + 10) p.x = -10;
            if (p.y < -10)    p.y = H + 10;
            if (p.y > H + 10) p.y = -10;

            // Dot
            ctx.beginPath();
            ctx.fillStyle = 'rgba(255,255,255,' + p.a + ')';
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();

            // Lines to neighbours — capped at 8 nearest by array order
            for (var j = i + 1; j < Math.min(particles.length, i + 9); j++){
                var q = particles[j];
                var dx = p.x - q.x, dy = p.y - q.y;
                var d2 = dx * dx + dy * dy;
                if (d2 < 120 * 120){
                    var alpha = (1 - Math.sqrt(d2) / 120) * .25;
                    ctx.strokeStyle = 'rgba(168,180,255,' + alpha + ')';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(q.x, q.y);
                    ctx.stroke();
                }
            }
        }
        rafId = requestAnimationFrame(step);
    }

    function start(){
        if (rafId == null) rafId = requestAnimationFrame(step);
    }
    function stop(){
        if (rafId != null) { cancelAnimationFrame(rafId); rafId = null; }
    }

    // Pause when tab hidden — saves battery on mobile and stops the
    // canvas from running while the user has switched away.
    document.addEventListener('visibilitychange', function(){
        if (document.hidden) stop(); else start();
    });

    // ResizeObserver keeps the canvas in sync if the URL bar collapses
    // or the device is rotated. Fall back to window resize otherwise.
    if (typeof ResizeObserver !== 'undefined'){
        var ro = new ResizeObserver(resize);
        ro.observe(host);
    } else {
        window.addEventListener('resize', resize, { passive: true });
    }

    resize();
    start();
})();
