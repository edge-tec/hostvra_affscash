<?php
/**
 * Theme-aware backdrop + shell styles for /views/auth/* screens.
 *
 * Include this partial AFTER /assets/css/app.min.css and the theme_head
 * partial. It assumes the page renders an <html data-theme="…"> root
 * and a `.auth-box` shell. Each theme paints body + shell with the
 * appropriate surface treatment without altering the page's brand
 * gradient (which stays for light mode and rides on the colored
 * .auth-header band).
 *
 * Static guard so multiple includes are safe.
 */
static $_authThemeDone = false;
if ($_authThemeDone) return;
$_authThemeDone = true;
?>
<style>
/* ── Dark Mode auth backdrop + shell ─────────────────────────── */
html[data-theme="dark"] body {
    background:
        radial-gradient(1200px 600px at 0% -10%, rgba(99,102,241,.10), transparent 60%),
        radial-gradient(900px 500px at 100% 110%, rgba(16,185,129,.06), transparent 60%),
        var(--bg) !important;
}
html[data-theme="dark"] .auth-box {
    background: var(--card-bg) !important;
    border-color: var(--border-strong) !important;
    box-shadow: var(--shadow-md);
}
html[data-theme="dark"] .auth-footer {
    background: var(--surface-2) !important;
    border-top-color: var(--border) !important;
    color: var(--text-muted) !important;
}
html[data-theme="dark"] .section-title { color: var(--text-muted) !important; border-bottom-color: var(--border) !important; }

/* Theme picker pinned top-right on auth screens */
.auth-theme-picker { position: fixed; top: 18px; right: 18px; z-index: 50; }
/* On auth screens the trigger lives at the top-right corner of the
   viewport, so the centered default (set inline in theme_toggle.php)
   would push the dropdown off the right edge. Re-anchor it to the
   trigger's right edge here so it stays fully visible. */
.auth-theme-picker .theme-picker-menu {
    left: auto !important;
    right: 0 !important;
    transform: none !important;
}
</style>
