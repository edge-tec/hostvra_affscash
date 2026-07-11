
<footer>
  <div class="container">
    <img src="<?= $logoSrc ?>" alt="<?= $appName ?>" height="36" style="margin-bottom:16px;filter:brightness(0) invert(1);">
    <div class="footer-links">
      <a href="/">Home</a>
      <a href="/#offers">Offers</a>
      <a href="/#about">About</a>
      <a href="/blog">Blog</a>
      <a href="/reviews">Reviews</a>
      <a href="/#contact">Contact</a>
      <a href="/register/affiliate">Sign Up</a>
      <a href="/login">Login</a>
      <a href="/terms-of-service" target="_blank">Terms &amp; Conditions</a>
      <a href="/privacy-policy" target="_blank">Privacy Policy</a>
    </div>
    <div style="display:flex;justify-content:center;gap:18px;margin:18px 0">
      <a href="https://t.me/affscashnet" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-telegram"></i></a>
      <a href="https://www.linkedin.com/company/89707239/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-linkedin"></i></a>
      <a href="https://www.facebook.com/affscash/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.45);font-size:22px"><i class="fa-brands fa-facebook"></i></a>
    </div>
    <p style="font-size:12px;color:rgba(255,255,255,.35)">&copy; 2019 &ndash; <?= date('Y') ?> EdgeSoft Ltd. All Rights Reserved. | <?= $appName ?> CPA Affiliate Network</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Hamburger menu
  var hb = document.getElementById('hamburger');
  var mm = document.getElementById('mobile-menu');
  if (hb && mm) {
    hb.addEventListener('click', function() {
      mm.classList.toggle('open');
      hb.classList.toggle('open');
    });
  }
  // Sticky header shadow on scroll
  window.addEventListener('scroll', function() {
    var h = document.querySelector('.site-header');
    if (h) h.style.boxShadow = window.scrollY > 10 ? '0 4px 30px rgba(124,58,237,.13)' : '0 2px 24px rgba(124,58,237,.08)';
  });
</script>
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
