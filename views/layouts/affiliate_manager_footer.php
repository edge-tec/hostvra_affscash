</main>
</div>
</div>
<?php $copyright = Config::get('config','app.footer_copyright'); if ($copyright): ?>
<footer style="text-align:center;padding:12px 20px;font-size:12px;color:#94A3B8;border-top:1px solid #E2E8F0;background:#fff"><?= Helpers::e($copyright) ?></footer>
<?php endif; ?>
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
