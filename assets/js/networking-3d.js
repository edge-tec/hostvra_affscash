/**
 * 3D Connected Networking Constellation Animation
 * Designed for Affscash Tracking System (Light/Dark Theme background effect)
 */
(function() {
    var canvas = null;
    var ctx = null;
    var particles = [];
    var w = window.innerWidth;
    var h = window.innerHeight;
    var mouse = { x: null, y: null, active: false };
    var animationFrameId = null;
    var isRunning = false;
    var fov = 400; // Focal length for 3D projection

    var themeColorSet = {
        dark: {
            p1: 'rgba(124, 58, 237, 0.4)',  // Violet/purple node
            p2: 'rgba(14, 165, 233, 0.35)', // Cyan/blue node
            line: 'rgba(124, 58, 237, ',
            mouse: 'rgba(14, 165, 233, '
        },
        light: {
            p1: 'rgba(79, 70, 229, 0.28)',   // Indigo node (more visible on light bg)
            p2: 'rgba(16, 185, 129, 0.25)',  // Green node
            line: 'rgba(79, 70, 229, ',
            mouse: 'rgba(16, 185, 129, '
        }
    };

    function ensureCanvas() {
        if (canvas) return true;
        
        canvas = document.getElementById('heroParticlesCanvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'heroParticlesCanvas';
            canvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:0;display:none;';
            document.body.insertBefore(canvas, document.body.firstChild);
        }
        ctx = canvas.getContext('2d');
        return true;
    }

    function resize() {
        if (!canvas) return;
        w = canvas.width = window.innerWidth;
        h = canvas.height = window.innerHeight;
    }

    function initParticles() {
        particles = [];
        var numParticles = 115;
        for (var i = 0; i < numParticles; i++) {
            particles.push({
                x: (Math.random() - 0.5) * 800, // 3D local X
                y: (Math.random() - 0.5) * 500, // 3D local Y
                z: (Math.random() - 0.5) * 400, // 3D local Z
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                vz: (Math.random() - 0.5) * 0.4,
                size: Math.random() * 2 + 1.5,
                type: Math.random() > 0.5 ? 'p1' : 'p2'
            });
        }
    }

    function draw() {
        if (!isRunning || !ctx) return;
        ctx.clearRect(0, 0, w, h);
        
        var theme = document.documentElement.getAttribute('data-theme') || 'light';
        var colors = themeColorSet[theme] || themeColorSet.light;
        
        var projected = [];
        particles.forEach(function(p) {
            // Move in 3D
            p.x += p.vx;
            p.y += p.vy;
            p.z += p.vz;
            
            // Bounce within 3D box limits
            if (Math.abs(p.x) > 450) p.vx *= -1;
            if (Math.abs(p.y) > 300) p.vy *= -1;
            if (Math.abs(p.z) > 200) p.vz *= -1;
            
            // Slow rotation in Y
            var rotY = 0.0006;
            var cosY = Math.cos(rotY), sinY = Math.sin(rotY);
            var x1 = p.x * cosY - p.z * sinY;
            var z1 = p.z * cosY + p.x * sinY;
            p.x = x1; p.z = z1;
            
            // Slow rotation in X
            var rotX = 0.0004;
            var cosX = Math.cos(rotX), sinX = Math.sin(rotX);
            var y1 = p.y * cosX - p.z * sinX;
            var z2 = p.z * cosX + p.y * sinX;
            p.y = y1; p.z = z2;
            
            // Perspective projection
            var scale = fov / (fov + p.z);
            var px = (p.x * scale) + (w / 2);
            var py = (p.y * scale) + (h / 2);
            
            projected.push({
                x: px,
                y: py,
                z: p.z,
                size: p.size * scale,
                color: colors[p.type]
            });
        });
        
        // Draw connection lines
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
                        ctx.strokeStyle = colors.line + opacity + ')';
                        ctx.lineWidth = 0.8 * (1 - (pa.z + pb.z) / 400);
                        ctx.stroke();
                    }
                }
            }
            
            // Draw node
            if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
                ctx.fillStyle = pa.color;
                ctx.beginPath();
                ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
                ctx.fill();
                
                if (pa.size > 2) {
                    ctx.strokeStyle = pa.color.replace('0.4', '0.08').replace('0.35', '0.08').replace('0.28', '0.06').replace('0.25', '0.06');
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.arc(pa.x, pa.y, pa.size * 2, 0, Math.PI * 2);
                    ctx.stroke();
                }
            }
        }
        
        // Mouse interaction connections
        if (mouse.active) {
            projected.forEach(function(p) {
                var dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
                if (dist < 150) {
                    var opacity = (1 - (dist / 150)) * 0.22;
                    ctx.beginPath();
                    ctx.moveTo(mouse.x, mouse.y);
                    ctx.lineTo(p.x, p.y);
                    ctx.strokeStyle = colors.mouse + opacity + ')';
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            });
        }
        
        animationFrameId = requestAnimationFrame(draw);
    }

    function checkTheme() {
        if (ensureCanvas()) {
            canvas.style.display = 'block';
            if (!isRunning) {
                isRunning = true;
                resize();
                if (particles.length === 0) initParticles();
                draw();
            }
        }
    }

    function onMouseMove(e) {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        mouse.active = true;
    }

    function onMouseLeave() {
        mouse.active = false;
    }

    function handleVisibility() {
        if (document.hidden) {
            if (isRunning) {
                isRunning = false;
                if (animationFrameId) {
                    cancelAnimationFrame(animationFrameId);
                    animationFrameId = null;
                }
            }
        } else {
            checkTheme();
        }
    }

    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function run() {
                document.removeEventListener('DOMContentLoaded', run);
                start();
            });
        } else {
            start();
        }
    }

    function start() {
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseleave', onMouseLeave);
        window.addEventListener('resize', resize);
        document.addEventListener('visibilitychange', handleVisibility);
        document.addEventListener('themechange', checkTheme);
        checkTheme();
    }

    init();
})();
