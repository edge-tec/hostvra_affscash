<?php
/**
 * <head> snippet that wires the light/dark theme system into a layout.
 *
 * Outputs:
 *  - <link> to dark-theme.css (rules only apply when html[data-theme="dark"])
 *  - <meta name="theme-prefs"> with the user preference, admin default, and CSRF
 *  - inline JS that sets html[data-theme] before paint so there is no flash
 *  - <script src="/assets/js/theme.min.js"> for toggling + persistence
 *
 * Must be included inside <head>, AFTER /assets/css/app.min.css. Safe to require
 * multiple times — uses a static guard to avoid duplicate output.
 */
static $_themeHeadDone = false;
if ($_themeHeadDone) return;
$_themeHeadDone = true;

$_themeUserPref    = Theme::userPreference();        // light|dark|system|null
$_themeDefault     = Theme::defaultTheme();          // light|dark
$_themeServerSide  = Theme::current();               // resolved
$_themeCsrf        = Auth::generateCsrf();

// Admin toggle: convert any colored logo to white when dark theme is active.
// Defaults to ON (back-compat with the prior always-on behaviour) when the
// setting has never been written.
$_darkLogoCfg   = Config::get('config', 'app.dark_logo_white');
$_darkLogoWhite = ($_darkLogoCfg === null || $_darkLogoCfg === '') ? '1' : ($_darkLogoCfg === '1' ? '1' : '0');

// Cache-buster for dark-theme.css. cPanel / LiteSpeed hosts add aggressive
// browser-cache headers by default, so without ?v=<mtime> visitors keep
// being served the previous CSS and the white-logo filter never shows up
// after we update the file.
$_darkCssPath = __DIR__ . '/../../assets/css/dark-theme.min.css';
$_darkCssVer  = @filemtime($_darkCssPath) ?: '1';

// One-shot "force" value set by admin Settings when the default theme was
// just changed. Consumed once: theme.js wipes localStorage and applies this
// value so the admin's previously-cached client-side pref can't overwrite
// the new default on the very next paint.
$_themeForce       = $_SESSION['_theme_force'] ?? '';
if ($_themeForce !== '') { unset($_SESSION['_theme_force']); }
?>
<link rel="stylesheet" href="/assets/css/dark-theme.min.css?v=<?= $_darkCssVer ?>">
<meta name="theme-prefs"
      data-user="<?= htmlspecialchars((string)$_themeUserPref, ENT_QUOTES) ?>"
      data-default="<?= htmlspecialchars($_themeDefault, ENT_QUOTES) ?>"
      data-force="<?= htmlspecialchars($_themeForce, ENT_QUOTES) ?>"
      data-dark-logo="<?= htmlspecialchars($_darkLogoWhite, ENT_QUOTES) ?>"
      data-csrf="<?= htmlspecialchars($_themeCsrf, ENT_QUOTES) ?>"
      data-galaxy-enabled="<?= htmlspecialchars((string)Config::get('config', 'space_engine.enabled'), ENT_QUOTES) ?>"
      data-galaxy-speed="<?= htmlspecialchars((string)Config::get('config', 'space_engine.speed'), ENT_QUOTES) ?>"
      data-galaxy-density="<?= htmlspecialchars((string)Config::get('config', 'space_engine.density'), ENT_QUOTES) ?>"
      data-galaxy-motion="<?= htmlspecialchars((string)Config::get('config', 'space_engine.motion'), ENT_QUOTES) ?>"
      data-galaxy-status="<?= htmlspecialchars((string)Config::get('config', 'space_engine.market_status'), ENT_QUOTES) ?>"
      data-galaxy-bubble-style="<?= htmlspecialchars((string)Config::get('config', 'space_engine.bubble_style'), ENT_QUOTES) ?>"
      data-galaxy-bubble-size="<?= htmlspecialchars((string)Config::get('config', 'space_engine.bubble_size'), ENT_QUOTES) ?>"
      data-galaxy-network-style="<?= htmlspecialchars((string)Config::get('config', 'space_engine.network_style'), ENT_QUOTES) ?>">
<script>
// FOUC guard — apply the resolved theme attribute synchronously, before
// any stylesheet repaints. Runs on every page load so logged-out auth
// screens honour the visitor's previously-saved choice (localStorage)
// instead of falling back to the admin default.
//
// Priority (matches theme.js):
//   1. Explicit user preference on the server (logged in, picked a theme)
//   2. localStorage value from a prior session (works while logged out)
//   3. Server-rendered admin default
//   4. 'light' as last-resort fallback
(function () {
    try {
        var THEMES = ['light', 'dark'];
        var html   = document.documentElement;
        var meta   = document.querySelector('meta[name="theme-prefs"]');
        var user   = meta ? meta.getAttribute('data-user')      : '';
        var def    = meta ? meta.getAttribute('data-default')   : 'light';
        var force  = meta ? meta.getAttribute('data-force')     : '';
        var dlogo  = meta ? meta.getAttribute('data-dark-logo') : '1';
        var ssr    = html.getAttribute('data-theme') || def;

        // Mirror the admin "Dark Mode Logo Color" toggle onto <html> so the
        // CSS rule (html[data-theme="dark"][data-dark-logo="1"] .logo img)
        // can gate the white-filter on/off without a server round-trip.
        html.setAttribute('data-dark-logo', dlogo === '0' ? '0' : '1');

        function valid(t) { return THEMES.indexOf(t) !== -1; }

        // Force takes top priority — admin just changed the platform default
        // and we want the new value to override stale client-side state.
        if (valid(force)) {
            try { localStorage.setItem('aff_theme', force); } catch (e) {}
            html.setAttribute('data-theme', force);
            return;
        }

        var pick = ssr;

        // Logged-in user with an explicit preference → server wins.
        if (valid(user)) {
            pick = user;
        }
        // 'system' preference → resolve via media query.
        else if (user === 'system' && window.matchMedia) {
            pick = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        // No server preference → check localStorage so the choice survives
        // logout/login and follows the visitor across browser sessions.
        else {
            var cached = null;
            try { cached = localStorage.getItem('aff_theme'); } catch (e) {}
            if (valid(cached)) pick = cached;
        }

        if (valid(pick)) html.setAttribute('data-theme', pick);
    } catch (e) {}
})();
</script>
<script src="/assets/js/theme.min.js" defer></script>
<script src="/assets/js/networking-3d.js?v=<?= filemtime(BASE_PATH . '/assets/js/networking-3d.js') ?>" defer></script>
<?php if ($fav = Config::get('config','app.favicon')): ?><link rel="icon" href="<?= Helpers::e($fav) ?>"><?php endif; ?>
