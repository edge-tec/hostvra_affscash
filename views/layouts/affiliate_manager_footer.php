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
<footer style="text-align:center;padding:20px;font-size:12px;color:#94A3B8;border-top:1px solid #E2E8F0;background:#fff">
    <div class="mobile-footer-logo" style="margin-bottom:16px;">
        <?php if ($siteLogo): ?>
        <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px; max-width:140px; object-fit:contain; filter:grayscale(100%); opacity:0.7;">
        <?php else: ?>
        <span style="font-weight:bold; color:#94A3B8; font-size:16px;"><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
        <?php endif; ?>
    </div>
    <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:16px; margin-bottom:12px;">
        <a href="/policy/terms-conditions" class="footer-link"><?= $iconHtml ?>Terms & Conditions</a>
        <a href="/policy/privacy-policy" class="footer-link"><?= $iconHtml ?>Privacy Policy</a>
        <a href="/policy/affiliate-agreement" class="footer-link"><?= $iconHtml ?>Affiliate Agreement</a>
        <a href="/policy/anti-fraud-policy" class="footer-link"><?= $iconHtml ?>Anti-Fraud Policy</a>
        <a href="/policy/gdpr-compliance" class="footer-link"><?= $iconHtml ?>GDPR Compliance</a>
        <a href="/policy/refund-policy" class="footer-link"><?= $iconHtml ?>Refund Policy</a>
        <a href="/policy/cookie-policy" class="footer-link"><?= $iconHtml ?>Cookie Policy</a>
        <a href="/policy/dashboard-disclaimers" class="footer-link"><?= $iconHtml ?>Dashboard Disclaimers</a>
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
