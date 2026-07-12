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
.footer-link { color:#64748B; text-decoration:none; display:inline-flex; align-items:center; }
.footer-link:hover { color:#475569; }
</style>
<footer style="text-align:center;padding:20px;font-size:12px;color:#94A3B8;border-top:1px solid #1E293B;background:#0F172A;position:relative;overflow:hidden;">
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
    <div class="mobile-footer-logo" style="margin-bottom:16px;">
        <?php if ($siteLogo): ?>
        <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px; max-width:140px; object-fit:contain; filter:brightness(0) invert(1);">
        <?php else: ?>
        <span style="font-weight:bold; color:#94A3B8; font-size:16px;"><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
        <?php endif; ?>
    </div>
    <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:16px; margin-bottom:12px;">
        <a href="/terms-of-service" target="_blank" class="footer-link"><?= $iconHtml ?>Terms & Conditions</a>
        <a href="/privacy-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Privacy Policy</a>
        <a href="/affiliate-agreement" target="_blank" class="footer-link"><?= $iconHtml ?>Affiliate Agreement</a>
        <a href="/anti-fraud-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Anti-Fraud Policy</a>
        <a href="/gdpr-compliance-policy" target="_blank" class="footer-link"><?= $iconHtml ?>GDPR Compliance</a>
        <a href="/refund-payment-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Refund Policy</a>
        <a href="/cookie-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Cookie Policy</a>
        <a href="/dashboard-disclaimers" target="_blank" class="footer-link"><?= $iconHtml ?>Dashboard Disclaimers</a>
    </div>
    <?php if ($copyright): ?>
    <div><?= Helpers::e($copyright) ?></div>
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
