/**
 * 3D Connected Networking & Galaxy Constellation Space Engine
 * Designed for Affscash Tracking System (Light/Dark Theme background effects)
 * Supports dynamic speed, density, mouse parallax, scroll parallax, and market status glows.
 */
(function() {
    var existingCanvas = document.getElementById('heroParticlesCanvas');
    if (existingCanvas && window.spaceEngineInstance) {
        return;
    }
    window.spaceEngineInstance = true;

    var canvas = null;
    var ctx = null;
    var particles = [];
    var w = window.innerWidth;
    var h = window.innerHeight;
    var mouse = { x: 0, y: 0, actualX: 0, actualY: 0, active: false };
    var camX = 0, camY = 0; // Smooth camera angles
    var animationFrameId = null;
    var isRunning = false;
    var fov = 400; // Focal length for 3D projection
    var currentTheme = 'light';
    var lastTime = performance.now();
    var frameCount = 0;
    
    function getGlowColor(colorStr, opacity) {
        if (typeof colorStr !== 'string') {
            return 'rgba(124, 58, 237, ' + opacity + ')';
        }
        return colorStr.replace(/[\d\.]+\)$/, opacity + ')');
    }

    // Safe localStorage helper functions
    function safeGet(key, def) {
        try {
            if (window.localStorage) {
                var val = localStorage.getItem(key);
                if (val !== null) return val;
            }
        } catch (e) {}
        return def;
    }

    function safeSet(key, val) {
        try {
            if (window.localStorage) {
                localStorage.setItem(key, val);
            }
        } catch (e) {}
    }

    // Read global configurations from meta tag (set by database configuration)
    var meta = document.querySelector('meta[name="theme-prefs"]');
    var globalEnabled = meta ? meta.getAttribute('data-galaxy-enabled') !== '0' : true;
    var globalSpeed = parseFloat(meta ? meta.getAttribute('data-galaxy-speed') || '1.0' : '1.0');
    var globalDensity = parseFloat(meta ? meta.getAttribute('data-galaxy-density') || '1.0' : '1.0');
    var globalMotion = parseFloat(meta ? meta.getAttribute('data-galaxy-motion') || '1.0' : '1.0');
    var globalStatus = meta ? meta.getAttribute('data-galaxy-status') || 'neutral' : 'neutral';

    // Galaxy / Constellation configuration settings (with localStorage persistence fallback to global defaults)
    var settings = {
        enabled: safeGet('galaxy_enabled', globalEnabled ? 'true' : 'false') !== 'false',
        speed: parseFloat(safeGet('galaxy_speed', String(globalSpeed))),
        density: parseFloat(safeGet('galaxy_density', String(globalDensity))),
        motion: parseFloat(safeGet('galaxy_motion', String(globalMotion))),
        marketStatus: safeGet('galaxy_market_status', globalStatus)
    };

    // Check system prefers-reduced-motion
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        settings.speed = 0.1;
        settings.motion = 0.0;
    }

    // Safety fallbacks for speed/density
    if (isNaN(settings.speed) || settings.speed <= 0.05) {
        settings.speed = 1.0;
    }
    if (isNaN(settings.density) || settings.density <= 0.05) {
        settings.density = 1.0;
    }
    if (isNaN(settings.motion)) {
        settings.motion = 1.0;
    }

    console.log("3D Space Engine: Active Settings:", JSON.stringify(settings));

    var themeColorSet = {
        dark: {
            p1: 'rgba(124, 58, 237, 0.4)',  // Violet/purple node
            p2: 'rgba(14, 165, 233, 0.35)', // Cyan/blue node
            p3: 'rgba(124, 58, 237, 0.4)',
            line: 'rgba(124, 58, 237, ',
            mouse: 'rgba(14, 165, 233, '
        },
        light: {
            p1: 'rgba(79, 70, 229, 0.65)',   // Indigo node
            p2: 'rgba(16, 185, 129, 0.60)',  // Emerald node
            p3: 'rgba(232, 25, 122, 0.65)',  // Pink node
            p4: 'rgba(245, 158, 11, 0.70)',   // Golden node
            line: 'rgba(124, 58, 237, ',    // Violet network connection lines
            mouse: 'rgba(79, 70, 229, '
        }
    };

    function ensureCanvas() {
        if (canvas) return true;
        if (!document.body) return false;
        
        canvas = document.getElementById('heroParticlesCanvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'heroParticlesCanvas';
            canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;display:none;';
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
        
        // Base densities
        var baseCount = currentTheme === 'light' ? 120 : 115;
        var numParticles = Math.floor(baseCount * settings.density);
        
        // 3D boundaries to match the landing page's elegant constellation clustering
        var limitX = Math.max(450, w * 1.2);
        var limitY = Math.max(400, h * 1.2);
        var limitZ = 250;

        for (var i = 0; i < numParticles; i++) {
            var pType = 'p1';
            var baseSize = 1.2;
            
            if (currentTheme === 'light') {
                var rand = Math.random();
                if (rand > 0.85) pType = 'p4'; // Gold
                else if (rand > 0.60) pType = 'p3'; // Pink
                else if (rand > 0.35) pType = 'p2'; // Green
                
                // Increased size (2 steps bigger)
                baseSize = Math.random() * 2.5 + 2.5;
            } else {
                pType = Math.random() > 0.5 ? 'p1' : 'p2';
                baseSize = Math.random() * 2.5 + 2.5;
            }

            particles.push({
                x: (Math.random() - 0.5) * limitX * 2,
                y: (Math.random() - 0.5) * limitY * 2,
                z: (Math.random() - 0.5) * limitZ * 2,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                vz: (Math.random() - 0.5) * 0.4,
                size: baseSize,
                type: pType
            });
        }
    }

    function draw() {
        if (!isRunning || !ctx) return;
        
        // If the canvas was detached from the DOM, stop this loop
        if (canvas && !document.body.contains(canvas)) {
            isRunning = false;
            animationFrameId = null;
            return;
        }
        
        try {
            frameCount++;
            if (frameCount % 300 === 0) {
                console.log("3D Space Engine Status: running, frames=" + frameCount + ", particles=" + particles.length + ", size=" + w + "x" + h);
            }

            ctx.clearRect(0, 0, w, h);
            
            var theme = document.documentElement.getAttribute('data-theme') || 'light';
            var colors = themeColorSet[theme] || themeColorSet.light;
            
            // Update color types in place if theme changes on the fly to avoid resets/size shifts
            if (theme !== currentTheme) {
                if (particles.length > 0) {
                    particles.forEach(function(p) {
                        if (theme === 'light') {
                            var rand = Math.random();
                            if (rand > 0.85) p.type = 'p4';
                            else if (rand > 0.60) p.type = 'p3';
                            else if (rand > 0.35) p.type = 'p2';
                            else p.type = 'p1';
                        } else {
                            p.type = Math.random() > 0.5 ? 'p1' : 'p2';
                        }
                    });
                } else {
                    initParticles();
                }
                currentTheme = theme;
            }

            // Draw ambient lighting reactive to market activity (Light theme only)
            if (theme === 'light') {
                var glowColor1, glowColor2;
                if (settings.marketStatus === 'positive') {
                    glowColor1 = 'rgba(16, 185, 129, 0.09)'; // emerald green glow
                    glowColor2 = 'rgba(52, 211, 153, 0.05)';
                } else if (settings.marketStatus === 'negative') {
                    glowColor1 = 'rgba(239, 68, 68, 0.09)';  // red glow
                    glowColor2 = 'rgba(248, 113, 113, 0.05)';
                } else {
                    glowColor1 = 'rgba(124, 58, 237, 0.07)'; // soft violet
                    glowColor2 = 'rgba(14, 165, 233, 0.06)';  // soft blue
                }

                var g1 = ctx.createRadialGradient(w * 0.8, h * 0.2, 0, w * 0.8, h * 0.2, Math.max(w, h) * 0.6);
                g1.addColorStop(0, glowColor1);
                g1.addColorStop(1, 'rgba(255,255,255,0)');
                ctx.fillStyle = g1;
                ctx.fillRect(0, 0, w, h);

                var g2 = ctx.createRadialGradient(w * 0.2, h * 0.7, 0, w * 0.2, h * 0.7, Math.max(w, h) * 0.5);
                g2.addColorStop(0, glowColor2);
                g2.addColorStop(1, 'rgba(255,255,255,0)');
                ctx.fillStyle = g2;
                ctx.fillRect(0, 0, w, h);
            }
            
            // Camera parallax calculations (mouse + scroll offsets)
            var targetCamX = mouse.active ? (mouse.actualX - w/2) * 0.08 * settings.motion : 0;
            var targetCamY = mouse.active ? (mouse.actualY - h/2) * 0.08 * settings.motion : 0;
            camX += (targetCamX - camX) * 0.04;
            camY += (targetCamY - camY) * 0.04;

            var scrollYOffset = Math.min(40, window.scrollY * 0.08) * settings.motion;
            
            // Draw the Galaxy Shadow (Center 3D Depth Nebula Glow/Shadow)
            var centerX = w / 2 - camX * 0.4;
            var centerY = h / 2 - camY * 0.4 - scrollYOffset * 0.4;
            var maxRadius = Math.max(w, h) * 0.5;
            
            if (theme === 'dark') {
                // Dark Mode: Deep cosmic dust lane shadow & colored core glow
                var centerGlow = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, maxRadius);
                
                var coreGlowColor = 'rgba(124, 58, 237, 0.12)'; // default purple/violet
                if (settings.marketStatus === 'positive') {
                    coreGlowColor = 'rgba(16, 185, 129, 0.10)'; // green
                } else if (settings.marketStatus === 'negative') {
                    coreGlowColor = 'rgba(239, 68, 68, 0.10)'; // red
                }
                
                // Outer shadow ring
                centerGlow.addColorStop(0, 'rgba(6, 4, 16, 0.45)'); // Deep central black hole core
                centerGlow.addColorStop(0.18, 'rgba(6, 4, 16, 0.35)'); // central shadow
                centerGlow.addColorStop(0.45, coreGlowColor); // Nebula glowing gas
                centerGlow.addColorStop(0.75, 'rgba(14, 165, 233, 0.02)'); // outer faint cyan glow
                centerGlow.addColorStop(1, 'rgba(0, 0, 0, 0)');
                
                ctx.fillStyle = centerGlow;
                ctx.fillRect(0, 0, w, h);
                
                // Also draw a subtle inner core shadow to simulate a black hole gravitational shadow
                var coreShadow = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, 140);
                coreShadow.addColorStop(0, 'rgba(0, 0, 0, 0.50)');
                coreShadow.addColorStop(0.6, 'rgba(6, 4, 16, 0.25)');
                coreShadow.addColorStop(1, 'rgba(0, 0, 0, 0)');
                ctx.fillStyle = coreShadow;
                ctx.beginPath();
                ctx.arc(centerX, centerY, 140, 0, Math.PI * 2);
                ctx.fill();
            } else {
                // Light Mode: Soft glowing shadow behind the center of the galaxy to give 3D contrast
                var centerGlow = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, maxRadius);
                
                var coreGlowColor = 'rgba(79, 70, 229, 0.06)'; // indigo
                if (settings.marketStatus === 'positive') {
                    coreGlowColor = 'rgba(16, 185, 129, 0.06)';
                } else if (settings.marketStatus === 'negative') {
                    coreGlowColor = 'rgba(239, 68, 68, 0.06)';
                }
                
                centerGlow.addColorStop(0, 'rgba(235, 240, 255, 0.45)'); // Soft light core shadow
                centerGlow.addColorStop(0.35, coreGlowColor);
                centerGlow.addColorStop(0.75, 'rgba(124, 58, 237, 0.02)');
                centerGlow.addColorStop(1, 'rgba(255, 255, 255, 0)');
                
                ctx.fillStyle = centerGlow;
                ctx.fillRect(0, 0, w, h);
            }
            
            var projected = [];
            var limitX = Math.max(450, w * 1.2);
            var limitY = Math.max(400, h * 1.2);
            var limitZ = 250;

            particles.forEach(function(p) {
                // Natural 3D floating movement scaled by speed setting
                p.x += p.vx * settings.speed;
                p.y += p.vy * settings.speed;
                p.z += p.vz * settings.speed;
                
                // Boundary bounce checks with edge correction (keeps them floating inside viewport)
                if (Math.abs(p.x) > limitX) {
                    p.vx *= -1;
                    p.x = Math.sign(p.x) * limitX;
                }
                if (Math.abs(p.y) > limitY) {
                    p.vy *= -1;
                    p.y = Math.sign(p.y) * limitY;
                }
                if (Math.abs(p.z) > limitZ) {
                    p.vz *= -1;
                    p.z = Math.sign(p.z) * limitZ;
                }

                // True 3D orbital rotation around center (landing page physics, speed scaled)
                var rotY = 0.0006 * settings.speed;
                var cosY = Math.cos(rotY), sinY = Math.sin(rotY);
                var rx1 = p.x * cosY - p.z * sinY;
                var rz1 = p.z * cosY + p.x * sinY;
                p.x = rx1; p.z = rz1;
                
                var rotX = 0.0004 * settings.speed;
                var cosX = Math.cos(rotX), sinX = Math.sin(rotX);
                var ry1 = p.y * cosX - p.z * sinX;
                var rz2 = p.z * cosX + p.y * sinX;
                p.y = ry1; p.z = rz2;
                
                // Camera position offsets (applying parallax pivot rotation)
                var cx = p.x - camX;
                var cy = p.y - camY - scrollYOffset;
                var cz = p.z;
                
                // 3D perspective projection with safety camera near-plane clipping
                var safeZ = Math.max(-190, Math.min(190, cz));
                var scale = fov / (fov + safeZ);
                var px = (cx * scale) + (w / 2);
                var py = (cy * scale) + (h / 2);
                
                projected.push({
                    x: px,
                    y: py,
                    z: safeZ,
                    size: p.size * scale,
                    color: colors[p.type] || colors.p1 || 'rgba(124, 58, 237, 0.4)'
                });
            });
            
            // Draw connection network lines
            for (var a = 0; a < projected.length; a++) {
                var pa = projected[a];
                for (var b = a + 1; b < projected.length; b++) {
                    var pb = projected[b];
                    var dx = pa.x - pb.x;
                    var dy = pa.y - pb.y;
                    var dist = Math.hypot(dx, dy);
                    
                    var connectLimit = theme === 'light' ? 90 : 110;
                    if (dist < connectLimit) {
                        var opacityFactor = theme === 'light' ? 0.35 : 0.14;
                        var opacity = (1 - (dist / connectLimit)) * opacityFactor * (1 - (pa.z + pb.z) / 400);
                        if (opacity > 0) {
                            ctx.beginPath();
                            ctx.moveTo(pa.x, pa.y);
                            ctx.lineTo(pb.x, pb.y);
                            ctx.strokeStyle = colors.line + opacity + ')';
                            ctx.lineWidth = (theme === 'light' ? 1.2 : 0.8) * (1 - (pa.z + pb.z) / 400);
                            ctx.stroke();
                        }
                    }
                }
                
                // Draw nodes/stars
                if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
                    // 1. Draw outer soft shadow glow bubble
                    var glowOpacity = theme === 'dark' ? '0.12' : '0.18';
                    ctx.fillStyle = getGlowColor(pa.color, glowOpacity);
                    ctx.beginPath();
                    ctx.arc(pa.x, pa.y, pa.size * 2.8, 0, Math.PI * 2);
                    ctx.fill();
                    
                    // 2. Draw central bright core node/bubble
                    ctx.fillStyle = pa.color;
                    ctx.beginPath();
                    ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
                    ctx.fill();
                    
                    // 3. Draw a tiny white inner highlight to make it look like a glossy bubble/sphere!
                    if (pa.size > 2.0) {
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.65)';
                        ctx.beginPath();
                        ctx.arc(pa.x - pa.size * 0.22, pa.y - pa.size * 0.22, pa.size * 0.22, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }
            }
            
            // Interactive mouse connection effect
            if (mouse.active) {
                projected.forEach(function(p) {
                    var dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
                    if (dist < 150) {
                        var opacity = (1 - (dist / 150)) * 0.20;
                        ctx.beginPath();
                        ctx.moveTo(mouse.x, mouse.y);
                        ctx.lineTo(p.x, p.y);
                        ctx.strokeStyle = colors.mouse + opacity + ')';
                        ctx.lineWidth = 1;
                        ctx.stroke();
                    }
                });
            }
        } catch (err) {
            console.error("3D Space Engine draw error:", err);
        } finally {
            if (isRunning) {
                animationFrameId = requestAnimationFrame(draw);
            }
        }
    }

    function checkTheme() {
        if (!settings.enabled) {
            if (canvas) canvas.style.display = 'none';
            if (isRunning) {
                isRunning = false;
                if (animationFrameId) {
                    cancelAnimationFrame(animationFrameId);
                    animationFrameId = null;
                }
            }
            return;
        }

        if (ensureCanvas()) {
            canvas.style.display = 'block';
            if (!isRunning) {
                isRunning = true;
                lastTime = performance.now();
                resize();
                initParticles();
                draw();
            }
        }
    }

    function onMouseMove(e) {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        // Dampen camera coordinate offset to prevent sudden jumps
        mouse.actualX += (e.clientX - mouse.actualX) * 0.1;
        mouse.actualY += (e.clientY - mouse.actualY) * 0.1;
        mouse.active = true;
    }

    function onMouseLeave() {
        mouse.active = false;
    }

    function handleVisibility() {
        // Run constantly, no pausing on tab visibility change
    }

    // ── Inject Settings Panel Interface ──
    function injectSettingsPanel() {
        if (document.getElementById('galaxySettingsBtn')) return;
        
        // Find suitable insertion target in topbar
        var target = document.querySelector('.topbar-actions') || 
                     document.querySelector('.topbar > div:last-child') ||
                     document.querySelector('.topbar');
        
        if (!target) return;

        // 1. Create floating gear/galaxy switch button
        var btn = document.createElement('button');
        btn.id = 'galaxySettingsBtn';
        btn.className = 'galaxy-settings-btn';
        btn.type = 'button';
        btn.title = 'Space Engine Settings';
        btn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M3 12a9 9 0 0 1 9-9 9 9 0 0 1 9 9 9 9 0 0 1-9 9 9 9 0 0 1-9-9Z"/>
                <path d="M12 7.5a4.5 4.5 0 0 1 4.5 4.5"/>
            </svg>
        `;
        
        // Append button
        target.insertBefore(btn, target.firstChild);

        // 2. Create glassmorphic configuration panel
        var panel = document.createElement('div');
        panel.id = 'galaxySettingsPanel';
        panel.className = 'galaxy-settings-panel';
        panel.innerHTML = `
            <div class="galaxy-settings-header">
                <h3>3D Space Engine</h3>
                <button type="button" id="galaxySettingsClose">&times;</button>
            </div>
            <div class="galaxy-settings-body">
                <div class="galaxy-setting-row">
                    <label class="galaxy-toggle-label">
                        <input type="checkbox" id="galaxyEnabled" ${settings.enabled ? 'checked' : ''}>
                        <span>Enable Galaxy Background</span>
                    </label>
                </div>
                <div class="galaxy-setting-row">
                    <label>Rotation Speed</label>
                    <input type="range" id="galaxySpeed" min="0" max="2" step="0.1" value="${settings.speed}">
                </div>
                <div class="galaxy-setting-row">
                    <label>Star Density</label>
                    <input type="range" id="galaxyDensity" min="0.2" max="2" step="0.1" value="${settings.density}">
                </div>
                <div class="galaxy-setting-row">
                    <label>Motion Parallax</label>
                    <input type="range" id="galaxyMotion" min="0" max="2" step="0.1" value="${settings.motion}">
                </div>
                <div class="galaxy-setting-row">
                    <label>Market Condition Glow</label>
                    <div class="galaxy-glow-group">
                        <button type="button" class="galaxy-glow-btn ${settings.marketStatus === 'neutral' ? 'active' : ''}" data-status="neutral">Neutral</button>
                        <button type="button" class="galaxy-glow-btn ${settings.marketStatus === 'positive' ? 'active' : ''}" data-status="positive" style="color:#10b981;">Positive</button>
                        <button type="button" class="galaxy-glow-btn ${settings.marketStatus === 'negative' ? 'active' : ''}" data-status="negative" style="color:#ef4444;">Negative</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(panel);

        // Event listener connections
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            panel.classList.toggle('show');
        });

        document.getElementById('galaxySettingsClose').addEventListener('click', function() {
            panel.classList.remove('show');
        });

        panel.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.addEventListener('click', function() {
            panel.classList.remove('show');
        });

        // Controls interactions
        document.getElementById('galaxyEnabled').addEventListener('change', function(e) {
            settings.enabled = e.target.checked;
            safeSet('galaxy_enabled', settings.enabled);
            checkTheme();
        });

        document.getElementById('galaxySpeed').addEventListener('input', function(e) {
            settings.speed = parseFloat(e.target.value);
            safeSet('galaxy_speed', settings.speed);
            initParticles(); // refresh speeds
        });

        document.getElementById('galaxyDensity').addEventListener('input', function(e) {
            settings.density = parseFloat(e.target.value);
            safeSet('galaxy_density', settings.density);
            initParticles(); // rebuild stars list
        });

        document.getElementById('galaxyMotion').addEventListener('input', function(e) {
            settings.motion = parseFloat(e.target.value);
            safeSet('galaxy_motion', settings.motion);
        });

        panel.querySelectorAll('.galaxy-glow-btn').forEach(function(b) {
            b.addEventListener('click', function() {
                panel.querySelectorAll('.galaxy-glow-btn').forEach(function(el) { el.classList.remove('active'); });
                b.classList.add('active');
                settings.marketStatus = b.dataset.status;
                safeSet('galaxy_market_status', settings.marketStatus);
            });
        });
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
        // Running constantly, no visibility event pausing needed
        document.addEventListener('themechange', checkTheme);
        
        injectSettingsPanel();
        // Fallback injection retry for slower DOM templates
        setTimeout(injectSettingsPanel, 1000);
        
        checkTheme();
    }

    init();
})();
