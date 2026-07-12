/**
 * 3D Connected Networking & Galaxy Constellation Animation
 * Designed for Affscash Tracking System (Light/Dark Theme background effects)
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
    var currentTheme = 'light';

    var themeColorSet = {
        dark: {
            p1: 'rgba(124, 58, 237, 0.4)',  // Violet/purple node
            p2: 'rgba(14, 165, 233, 0.35)', // Cyan/blue node
            p3: 'rgba(124, 58, 237, 0.4)',
            line: 'rgba(124, 58, 237, ',
            mouse: 'rgba(14, 165, 233, '
        },
        light: {
            p1: 'rgba(79, 70, 229, 0.32)',   // Indigo node
            p2: 'rgba(16, 185, 129, 0.28)',  // Green node
            p3: 'rgba(232, 25, 122, 0.32)',  // Pink node
            line: 'rgba(232, 25, 122, ',    // Pink galaxy connection lines
            mouse: 'rgba(79, 70, 229, '
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
        currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        
        if (currentTheme === 'light') {
            // Arrange particles in a 3D spiral galaxy structure
            var numParticles = 145;
            var numArms = 2;
            for (var i = 0; i < numParticles; i++) {
                var arm = i % numArms;
                var dist = Math.random(); // normalized distance from center
                // logarithmic spiral layout
                var angle = (arm * Math.PI) + (dist * Math.PI * 2.8);
                
                var x = Math.cos(angle) * dist * 450 + (Math.random() - 0.5) * 70;
                var y = (Math.random() - 0.5) * 50; // thickness of galaxy disk
                var z = Math.sin(angle) * dist * 400 + (Math.random() - 0.5) * 70;
                
                particles.push({
                    x: x,
                    y: y,
                    z: z,
                    angle: angle,
                    dist: dist,
                    speed: 0.002 + (1 - dist) * 0.004, // Keplerian-ish rotation speed
                    size: Math.random() * 2 + 1.2,
                    type: Math.random() > 0.6 ? 'p1' : (Math.random() > 0.35 ? 'p3' : 'p2')
                });
            }
        } else {
            // Constellation network in dark mode
            var numParticles = 115;
            for (var i = 0; i < numParticles; i++) {
                particles.push({
                    x: (Math.random() - 0.5) * 800,
                    y: (Math.random() - 0.5) * 500,
                    z: (Math.random() - 0.5) * 400,
                    vx: (Math.random() - 0.5) * 0.4,
                    vy: (Math.random() - 0.5) * 0.4,
                    vz: (Math.random() - 0.5) * 0.4,
                    size: Math.random() * 2 + 1.5,
                    type: Math.random() > 0.5 ? 'p1' : 'p2'
                });
            }
        }
    }

    function draw() {
        if (!isRunning || !ctx) return;
        ctx.clearRect(0, 0, w, h);
        
        var theme = document.documentElement.getAttribute('data-theme') || 'light';
        var colors = themeColorSet[theme] || themeColorSet.light;
        
        // Re-initialize if the theme changes on the fly
        if (theme !== currentTheme) {
            initParticles();
            currentTheme = theme;
        }
        
        var projected = [];
        particles.forEach(function(p) {
            if (theme === 'light') {
                // Galaxy spiral rotation logic
                p.angle += p.speed;
                var targetX = Math.cos(p.angle) * p.dist * 450;
                var targetZ = Math.sin(p.angle) * p.dist * 400;
                p.x = targetX;
                p.z = targetZ;
                p.y += (Math.random() - 0.5) * 0.15;
                if (Math.abs(p.y) > 70) p.y *= -0.9;
            } else {
                // Constellation linear bounce logic
                p.x += p.vx;
                p.y += p.vy;
                p.z += p.vz;
                if (Math.abs(p.x) > 450) p.vx *= -1;
                if (Math.abs(p.y) > 300) p.vy *= -1;
                if (Math.abs(p.z) > 200) p.vz *= -1;
            }
            
            // Slow orbital pitch rotation in Y/X for 3D depth
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
                
                var connectLimit = theme === 'light' ? 95 : 110; // galaxy is slightly denser
                if (dist < connectLimit) {
                    var opacity = (1 - (dist / connectLimit)) * 0.16 * (1 - (pa.z + pb.z) / 400);
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
            
            // Draw nodes
            if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
                ctx.fillStyle = pa.color;
                ctx.beginPath();
                ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
                ctx.fill();
                
                if (pa.size > 2) {
                    ctx.strokeStyle = pa.color.replace('0.4', '0.08').replace('0.35', '0.08').replace('0.32', '0.06').replace('0.28', '0.06');
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
                initParticles();
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
