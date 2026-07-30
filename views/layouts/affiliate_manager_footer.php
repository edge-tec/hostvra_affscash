</main>
</div>
</div>
<?php 
$copyright = Config::get('config','app.footer_copyright'); 
$siteLogo = Config::get('config','app.logo');
$favIcon = Config::get('config','app.favicon');
$defaultIcon = '<svg style="width:12px;height:12px;margin-right:4px;vertical-align:middle;color:#94A3B8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
$iconHtml = $favIcon ? '<img src="'.Helpers::e($favIcon).'" style="width:12px;height:12px;margin-right:4px;vertical-align:middle;object-fit:contain">' : $defaultIcon;
?>
<style>
@media (min-width: 769px) { .mobile-footer-logo { display: none !important; } }

/* 3D Glassmorphism Responsive Dashboard Footer */
footer.app-3d-footer {
    text-align: center !important;
    padding: 24px 20px 20px !important;
    font-size: 12.5px !important;
    color: rgba(255, 255, 255, 0.75) !important;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.96) 0%, rgba(10, 15, 30, 0.99) 100%) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-top: 1px solid rgba(99, 102, 241, 0.25) !important;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
    position: relative !important;
    overflow: hidden !important;
    z-index: 10 !important;
}

html[data-theme="light"] footer.app-3d-footer {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(241, 245, 249, 0.98) 100%) !important;
    border-top-color: rgba(99, 102, 241, 0.2) !important;
    color: #475569 !important;
    box-shadow: 0 -8px 30px rgba(99, 102, 241, 0.08) !important;
}

.footer-nav-container {
    display: flex !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 8px 12px !important;
    margin: 0 auto 16px !important;
    max-width: 1200px !important;
    position: relative !important;
    z-index: 2 !important;
}

.footer-link {
    color: rgba(255, 255, 255, 0.8) !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 6px 14px !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    border-radius: 20px !important;
    background: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(8px) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    white-space: nowrap !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1) !important;
}

html[data-theme="light"] .footer-link {
    color: #334155 !important;
    background: rgba(255, 255, 255, 0.8) !important;
    border-color: rgba(226, 232, 240, 0.9) !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
}

.footer-link:hover {
    color: #ffffff !important;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.4) 0%, rgba(79, 70, 229, 0.5) 100%) !important;
    border-color: rgba(129, 140, 248, 0.6) !important;
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35) !important;
}

html[data-theme="light"] .footer-link:hover {
    color: #ffffff !important;
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    border-color: transparent !important;
}

.footer-copyright-text {
    font-size: 12px !important;
    font-weight: 600 !important;
    color: rgba(255, 255, 255, 0.6) !important;
    position: relative !important;
    z-index: 2 !important;
    letter-spacing: 0.01em !important;
}

html[data-theme="light"] .footer-copyright-text {
    color: #64748B !important;
}
</style>

<footer class="app-3d-footer">
    <canvas class="footer-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
    <script>
    (function() {
        var footer = document.querySelector('footer');
        var canvas = footer ? footer.querySelector('.footer-canvas') : null;
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w, h;
        function resize() {
            w = canvas.width = canvas.offsetWidth;
            h = canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);
        
        var bg = window.getComputedStyle(footer).backgroundColor;
        var isDark = true;
        if (bg) {
            var rgb = bg.match(/\d+/g);
            if (rgb && rgb.length >= 3) {
                var brightness = (parseInt(rgb[0])*299 + parseInt(rgb[1])*587 + parseInt(rgb[2])*114) / 1000;
                if (brightness > 128) isDark = false;
            }
        }
        var colorNode = isDark ? 'rgba(232, 25, 122, 0.4)' : 'rgba(124, 58, 237, 0.4)';
        var colorLine = isDark ? 'rgba(232, 25, 122, 0.12)' : 'rgba(124, 58, 237, 0.12)';
        
        var nodes = [];
        var density = Math.min(25, Math.floor(w / 50));
        for (var i = 0; i < density; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 2 + 1
            });
        }
        function animate() {
            if (!canvas.offsetParent) {
                requestAnimationFrame(animate);
                return;
            }
            ctx.clearRect(0, 0, w, h);
            ctx.fillStyle = colorNode;
            nodes.forEach(function(n) {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > w) n.vx *= -1;
                if (n.y < 0 || n.y > h) n.vy *= -1;
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
            });
            ctx.lineWidth = 0.8;
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    var dist = Math.hypot(dx, dy);
                    if (dist < 100) {
                        ctx.strokeStyle = colorLine;
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }
        animate();
    })();
    </script>
    <div class="mobile-footer-logo" style="margin-bottom:16px;position:relative;z-index:2">
        <?php if ($siteLogo): ?>
        <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px; max-width:140px; object-fit:contain; filter:brightness(0) invert(1);">
        <?php else: ?>
        <span style="font-weight:bold; color:#94A3B8; font-size:16px;"><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
        <?php endif; ?>
    </div>
    <div class="footer-nav-container">
        <a href="/terms-of-service" target="_blank" class="footer-link">Terms &amp; Conditions</a>
        <a href="/privacy-policy" target="_blank" class="footer-link">Privacy Policy</a>
        <a href="/affiliate-agreement" target="_blank" class="footer-link">Affiliate Agreement</a>
        <a href="/anti-fraud-policy" target="_blank" class="footer-link">Anti-Fraud Policy</a>
        <a href="/gdpr-compliance-policy" target="_blank" class="footer-link">GDPR Compliance</a>
        <a href="/refund-payment-policy" target="_blank" class="footer-link">Refund Policy</a>
        <a href="/cookie-policy" target="_blank" class="footer-link">Cookie Policy</a>
        <a href="/dashboard-disclaimers" target="_blank" class="footer-link">Dashboard Disclaimers</a>
    </div>
    <?php if ($copyright): ?>
    <div class="footer-copyright-text"><?= Helpers::e($copyright) ?></div>
    <?php endif; ?>
</footer>
<script src="/assets/js/app.min.js"></script>
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
