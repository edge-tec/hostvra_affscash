<?php
/**
 * Auth-page background applier.
 *
 * Renders up to four stacked layers on a fixed full-viewport
 * container that sits behind the auth shell:
 *
 *     z:-4  static image          (existing behaviour)
 *     z:-3  background video      (new)
 *     z:-2  animated background   (new — particles / gradient / glass / shapes / glow / mesh)
 *     z:-1  semi-transparent overlay (colour + opacity)  (new)
 *
 * Each layer is opt-in. If nothing is configured / nothing is enabled
 * the partial emits nothing and the page falls back to its original
 * gradient — guaranteed back-compat with the existing UI.
 *
 * The caller sets $_authBgKey to one of:
 *     'auth_bg_login' | 'auth_bg_affreg' | 'auth_bg_advreg'
 * which is mapped to the per-page settings stored by SettingsController.
 *
 * Performance notes:
 *   - image gets <link rel="preload" as="image" fetchpriority="high">
 *   - video uses preload="metadata" + playsinline + muted so iOS will
 *     actually autoplay; poster falls back to the static image so users
 *     never see a black flash while the video buffers
 *   - animation CSS is loaded with a mtime cache-buster
 *   - particles JS is loaded only when the particles animation is picked
 *   - everything is inside .auth-bg-stack with `contain:strict` so
 *     layout / paint is isolated from the rest of the page
 */

$_authBgAllowed = ['auth_bg_login','auth_bg_affreg','auth_bg_advreg'];
if (!isset($_authBgKey) || !in_array($_authBgKey, $_authBgAllowed, true)) return;

$_pg = preg_replace('/^auth_bg_/', '', $_authBgKey);   // login | affreg | advreg

// ── Image config ────────────────────────────────────────────────────────
$imgPath = (string)(Config::get('config', 'app.auth_bg_' . $_pg) ?? '');
$imgOn   = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_enabled') ?? '0') === '1';
$imgFs   = $imgPath !== '' ? BASE_PATH . $imgPath : '';
$imgOk   = $imgOn && $imgPath !== '' && is_file($imgFs);
$imgUrl  = $imgOk ? htmlspecialchars($imgPath . '?v=' . (@filemtime($imgFs) ?: '1'), ENT_QUOTES) : '';

// ── Video config ────────────────────────────────────────────────────────
$vidPath     = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video') ?? '');
$vidOn       = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video_enabled') ?? '0') === '1';
$vidAutoplay = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video_autoplay') ?? '1') === '1';
$vidLoop     = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video_loop')     ?? '1') === '1';
$vidMute     = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video_mute')     ?? '1') === '1';
$vidFit      = in_array((string)(Config::get('config', 'app.auth_bg_' . $_pg . '_video_fit') ?? 'cover'), ['cover','contain'], true)
             ? (string)Config::get('config', 'app.auth_bg_' . $_pg . '_video_fit') : 'cover';
$vidFs       = $vidPath !== '' ? BASE_PATH . $vidPath : '';
$vidOk       = $vidOn && $vidPath !== '' && is_file($vidFs);
$vidUrl      = $vidOk ? htmlspecialchars($vidPath . '?v=' . (@filemtime($vidFs) ?: '1'), ENT_QUOTES) : '';
$vidMime     = $vidOk ? (strtolower(pathinfo($vidPath, PATHINFO_EXTENSION)) === 'webm' ? 'video/webm' : 'video/mp4') : '';

// ── Animation config ────────────────────────────────────────────────────
$animKey       = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_anim') ?? '');
$animAllowed   = ['particles','gradient','glass','shapes','glow','mesh'];
$animOn        = in_array($animKey, $animAllowed, true);
$animSpeed     = (int)(Config::get('config', 'app.auth_bg_' . $_pg . '_anim_speed') ?? 5);
$animOpacity   = (int)(Config::get('config', 'app.auth_bg_' . $_pg . '_anim_opacity') ?? 70);
if ($animSpeed < 1 || $animSpeed > 10)   $animSpeed   = 5;
if ($animOpacity < 10 || $animOpacity > 100) $animOpacity = 70;

// ── Overlay config ──────────────────────────────────────────────────────
$ovColor   = (string)(Config::get('config', 'app.auth_bg_' . $_pg . '_overlay_color') ?? '#0F172A');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $ovColor)) $ovColor = '#0F172A';
$ovOpacity = (int)(Config::get('config', 'app.auth_bg_' . $_pg . '_overlay_opacity') ?? 0);
if ($ovOpacity < 0 || $ovOpacity > 90) $ovOpacity = 0;
$ovOn      = $ovOpacity > 0;

// ── Blur config ─────────────────────────────────────────────────────────
// Applied to the image and video layers only — overlay, animation and the
// auth form itself stay crisp. A small scale-up masks the soft edges that
// CSS filter:blur introduces at the viewport border.
$bgBlur    = (int)(Config::get('config', 'app.auth_bg_' . $_pg . '_blur') ?? 0);
if ($bgBlur < 0)  $bgBlur = 0;
if ($bgBlur > 30) $bgBlur = 30;
$bgBlurOn  = $bgBlur > 0;

// If nothing at all is configured, emit nothing — existing gradient stays.
if (!$imgOk && !$vidOk && !$animOn && !$ovOn) return;

$_authBgActive = true;

// Convert hex → rgba for the overlay
$ovR = hexdec(substr($ovColor, 1, 2));
$ovG = hexdec(substr($ovColor, 3, 2));
$ovB = hexdec(substr($ovColor, 5, 2));
$ovRgba = sprintf('rgba(%d,%d,%d,%.2f)', $ovR, $ovG, $ovB, $ovOpacity / 100);

// Animation CSS cache buster
$_animCssFs   = BASE_PATH . '/assets/css/auth_bg_animations.css';
$_animCssVer  = @filemtime($_animCssFs) ?: '1';
$_animJsFs    = BASE_PATH . '/assets/js/auth_bg_animations.js';
$_animJsVer   = @filemtime($_animJsFs) ?: '1';
?>
<?php if ($imgOk): ?>
<link rel="preload" as="image" href="<?= $imgUrl ?>" fetchpriority="high">
<?php endif; ?>
<?php if ($animOn): ?>
<link rel="stylesheet" href="/assets/css/auth_bg_animations.css?v=<?= $_animCssVer ?>">
<?php endif; ?>

<?php if ($vidOk || $animOn): // need always-on layer when video or animation are active so they sit behind the form ?>
<style>
/* Auth-page background stack — neutralise the page-local body gradient
   only when a layered background is active, so the layers underneath
   become visible. When nothing is active we leave the existing body
   gradient alone (back-compat). */
html body, html[data-theme="dark"] body { background:transparent !important; }

/* Hard guarantee: the auth shell sits ABOVE the background stack and is
   never overlapped by the video/animation. The stack uses z-index:-10
   (set in auth_bg_animations.css) so any positive / auto z-index wins. */
.auth-box, .auth-theme-picker { position:relative; z-index:1; }
<?php if ($bgBlurOn): ?>
/* Admin-controlled blur for image + video layers. Animations stay sharp
   so dots / shapes don't smear. Slight scale-up hides the soft edge
   that `filter:blur()` produces at the viewport border. */
.auth-bg-img, .auth-bg-vid > video{
    filter: blur(<?= $bgBlur ?>px);
    transform: scale(1.06);
    transform-origin: center center;
    will-change: filter, transform;
}
<?php endif; ?>
</style>
<?php elseif ($imgOk): ?>
<?php if ($bgBlurOn): ?>
<style>
/* Static image + admin-controlled blur. Painting blur on `background-image`
   is not possible directly, so we drop the image into a fixed pseudo-layer
   and blur THAT — the existing body gradient stays on top for legibility. */
html body, html[data-theme="dark"] body {
    background: transparent !important;
}
html body::before {
    content:""; position:fixed; inset:0; z-index:-10;
    background:url("<?= $imgUrl ?>") center center / cover no-repeat;
    filter: blur(<?= $bgBlur ?>px);
    transform: scale(1.06);
    transform-origin: center center;
    will-change: filter, transform;
    pointer-events:none;
}
html body::after {
    content:""; position:fixed; inset:0; z-index:-9; pointer-events:none;
    background:linear-gradient(135deg, rgba(238,242,255,.72) 0%, rgba(240,253,244,.72) 100%);
}
html[data-theme="dark"] body::after {
    background:linear-gradient(135deg, rgba(10,15,31,.78) 0%, rgba(20,28,52,.72) 100%);
}
.auth-box, .auth-theme-picker { position:relative; z-index:1; }
</style>
<?php else: ?>
<style>
/* Static image only — with glassmorphism we no longer need the body tint */
html body {
    background: url("<?= $imgUrl ?>") center center / cover no-repeat fixed !important;
    background-color:#EEF2FF;
}
html[data-theme="dark"] body {
    background: url("<?= $imgUrl ?>") center center / cover no-repeat fixed !important;
    background-color:#0A0F1F;
}
@media (hover: none), (pointer: coarse), (max-width: 768px){
    html body, html[data-theme="dark"] body { background-attachment:scroll !important; }
}
</style>
<?php endif; ?>
<?php endif; ?>

<?php if ($vidOk || $animOn): ?>
<div class="auth-bg-stack" aria-hidden="true">
    <?php if ($imgOk): ?>
    <div class="auth-bg-img" style="background-image:url('<?= $imgUrl ?>');"></div>
    <?php endif; ?>

    <?php if ($vidOk): ?>
    <div class="auth-bg-vid" data-fit="<?= htmlspecialchars($vidFit, ENT_QUOTES) ?>">
        <video
            <?= $vidAutoplay ? 'autoplay' : '' ?>
            <?= $vidLoop     ? 'loop'     : '' ?>
            <?= $vidMute     ? 'muted'    : '' ?>
            playsinline
            preload="metadata"
            <?php if ($imgOk): ?>poster="<?= $imgUrl ?>"<?php endif; ?>
            disablepictureinpicture
            disableremoteplayback>
            <source src="<?= $vidUrl ?>" type="<?= htmlspecialchars($vidMime, ENT_QUOTES) ?>">
        </video>
    </div>
    <?php endif; ?>

    <?php if ($animOn): ?>
    <div class="auth-bg-anim bg-anim-<?= htmlspecialchars($animKey, ENT_QUOTES) ?>"
         data-speed="<?= $animSpeed ?>"
         style="--bg-speed:<?= $animSpeed ?>;--bg-opacity:<?= number_format($animOpacity / 100, 2) ?>"></div>
    <?php endif; ?>

    <?php if ($ovOn): ?>
    <div class="auth-bg-over" style="background:<?= htmlspecialchars($ovRgba, ENT_QUOTES) ?>;"></div>
    <?php endif; ?>
</div>

<?php if ($vidOk): ?>
<script>
// Mobile Safari occasionally refuses to start an autoplay video until
// it's been "touched" by user interaction. Re-poke it once on the first
// pointer/touch so the autoplay+muted+playsinline combo actually fires.
(function(){
    var v = document.querySelector('.auth-bg-vid video');
    if (!v) return;
    function tryPlay(){ var p = v.play && v.play(); if (p && p.catch) p.catch(function(){}); }
    document.addEventListener('visibilitychange', function(){ if (!document.hidden) tryPlay(); });
    ['pointerdown','touchstart','click'].forEach(function(ev){
        document.addEventListener(ev, function once(){ tryPlay(); document.removeEventListener(ev, once); }, { once:true, passive:true });
    });
    tryPlay();
})();
</script>
<?php endif; ?>

<?php if ($animKey === 'particles'): ?>
<script src="/assets/js/auth_bg_animations.js?v=<?= $_animJsVer ?>" defer></script>
<?php endif; ?>
</div>
<?php elseif ($ovOn): ?>
<div class="auth-bg-stack" aria-hidden="true">
    <div class="auth-bg-over" style="background:<?= htmlspecialchars($ovRgba, ENT_QUOTES) ?>;"></div>
</div>
<?php endif; ?>

<?php if ($imgOk || $vidOk || $animOn): ?>
<style>
/* Glassmorphism for the auth box when a background is active */
.auth-box {
    background: rgba(255, 255, 255, 0.65) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.5) !important;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15) !important;
}
html[data-theme="dark"] .auth-box {
    background: rgba(15, 23, 42, 0.60) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35) !important;
}
.auth-footer {
    background: rgba(255, 255, 255, 0.35) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.4) !important;
}
html[data-theme="dark"] .auth-footer {
    background: rgba(15, 23, 42, 0.4) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}
/* Inputs should also have slight transparency to match the glass look */
.auth-body input[type="text"], 
.auth-body input[type="email"], 
.auth-body input[type="password"] {
    background: rgba(255, 255, 255, 0.6) !important;
}
html[data-theme="dark"] .auth-body input[type="text"], 
html[data-theme="dark"] .auth-body input[type="email"], 
html[data-theme="dark"] .auth-body input[type="password"] {
    background: rgba(15, 23, 42, 0.6) !important;
}
.auth-body input:focus {
    background: #fff !important;
}
html[data-theme="dark"] .auth-body input:focus {
    background: rgba(30, 41, 59, 0.9) !important;
}
</style>
<?php endif; ?>
