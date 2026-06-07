</main>
</div>
</div>
<?php $copyright = Config::get('config','app.footer_copyright'); ?>
<footer style="text-align:center;padding:16px 20px;font-size:12px;color:#94A3B8;border-top:1px solid #E2E8F0;background:#fff">
    <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:16px; margin-bottom:8px;">
        <a href="#" style="color:#64748B; text-decoration:none;">Terms & Conditions</a>
        <a href="#" style="color:#64748B; text-decoration:none;">Affiliate Agreement</a>
        <a href="#" style="color:#64748B; text-decoration:none;">Anti-Fraud Policy</a>
        <a href="#" style="color:#64748B; text-decoration:none;">Dashboard Disclaimers</a>
        <a href="#" style="color:#64748B; text-decoration:none;">Refund Policy</a>
    </div>
    <?php if ($copyright): ?>
    <div><?= Helpers::e($copyright) ?></div>
    <?php endif; ?>
</footer>
<script src="/assets/js/app.js"></script>
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
