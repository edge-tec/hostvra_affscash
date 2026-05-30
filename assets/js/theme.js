/**
 * Theme controller — light/dark switcher with server-side persistence.
 *
 * Initial paint:
 *   The layout sets <html data-theme="…"> server-side based on the resolved
 *   user preference + admin default, so the first paint is correct and there
 *   is no flash. If the user selected "system", we adjust here on load.
 *
 * Toggling:
 *   Any element with [data-action="toggle-theme"] flips the theme. The new
 *   value is written immediately to <html data-theme> for instant UI update,
 *   then POSTed to /api/theme to persist. A "themechange" CustomEvent is
 *   dispatched on document so charts and other widgets can react.
 */
(function () {
    var STORAGE_KEY = 'aff_theme';
    var THEMES      = ['light', 'dark'];
    var META        = document.querySelector('meta[name="theme-prefs"]');
    var prefs = {
        user:    META ? META.getAttribute('data-user')    : '',  // light|dark|system|''
        default: META ? META.getAttribute('data-default') : 'light',
        force:   META ? META.getAttribute('data-force')   : '',  // one-shot: admin just changed default
        csrf:    META ? META.getAttribute('data-csrf')    : ''
    };

    function isValid(t) { return THEMES.indexOf(t) !== -1; }

    function systemTheme() {
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }

    function resolveInitial() {
        // Priority order (matches the FOUC guard in theme_head.php):
        //   1. One-shot `force` — admin just changed platform default;
        //      flushes localStorage to match before returning.
        //   2. Explicit user preference saved on the server (logged-in)
        //   3. 'system' preference → media query
        //   4. localStorage from a prior session (covers logged-out screens)
        //   5. Server-rendered <html data-theme> (admin default)
        //   6. 'light' as last-resort fallback
        if (isValid(prefs.force)) {
            try { localStorage.setItem(STORAGE_KEY, prefs.force); } catch (e) {}
            return prefs.force;
        }
        if (isValid(prefs.user))           return prefs.user;
        if (prefs.user === 'system')       return systemTheme();
        try {
            var cached = localStorage.getItem(STORAGE_KEY);
            if (isValid(cached)) return cached;
        } catch (e) {}
        var ssr = document.documentElement.getAttribute('data-theme');
        if (isValid(ssr)) return ssr;
        return isValid(prefs.default) ? prefs.default : 'light';
    }

    function syncPickerUI(theme) {
        document.querySelectorAll('.theme-picker-check').forEach(function (el) {
            el.style.display = (el.getAttribute('data-for') === theme) ? 'inline' : 'none';
        });
    }

    function applyTheme(theme) {
        if (!isValid(theme)) return;
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
        syncPickerUI(theme);
        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: theme } }));
    }

    function persist(theme) {
        if (!prefs.csrf) return;
        try {
            var body = new URLSearchParams();
            body.set('theme', theme);
            body.set('_token', prefs.csrf);
            fetch('/api/theme', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/x-www-form-urlencoded',
                               'X-Requested-With': 'XMLHttpRequest' },
                body:        body.toString()
            }).catch(function () {});
        } catch (e) {}
    }

    function cycle() {
        // Click the icon (without opening the menu): cycle light → dark → light.
        var cur = document.documentElement.getAttribute('data-theme');
        var i   = THEMES.indexOf(cur);
        var next = THEMES[(i + 1) % THEMES.length];
        applyTheme(next);
        persist(next);
    }

    function closeMenus() {
        document.querySelectorAll('.theme-picker-menu').forEach(function (m) { m.style.display = 'none'; });
    }

    // Apply initial (handles 'system' override). Idempotent vs. SSR value.
    applyTheme(resolveInitial());

    // Click handler — delegated so dynamically-injected pickers still work.
    document.addEventListener('click', function (e) {
        // 1. Explicit picker option
        var opt = e.target.closest('[data-theme-set]');
        if (opt) {
            e.preventDefault();
            var t = opt.getAttribute('data-theme-set');
            applyTheme(t);
            persist(t);
            closeMenus();
            return;
        }
        // 2. Picker trigger — open the menu
        var trigger = e.target.closest('[data-action="open-theme-picker"]');
        if (trigger) {
            e.preventDefault();
            var menu = trigger.parentElement.querySelector('.theme-picker-menu');
            if (menu) {
                var wasOpen = menu.style.display === 'block';
                closeMenus();
                menu.style.display = wasOpen ? 'none' : 'block';
            }
            return;
        }
        // 3. Legacy cycle button (kept for backwards compatibility with any
        //    page that still uses the old single-button toggle)
        var legacy = e.target.closest('[data-action="toggle-theme"]');
        if (legacy) {
            e.preventDefault();
            cycle();
            return;
        }
        // 4. Outside-click closes any open picker menus
        if (!e.target.closest('.theme-picker')) closeMenus();
    });

    // Re-apply on OS preference change if user is on 'system'.
    if (prefs.user === 'system' && window.matchMedia) {
        try {
            window.matchMedia('(prefers-color-scheme: dark)')
                .addEventListener('change', function () { applyTheme(systemTheme()); });
        } catch (e) { /* Safari < 14 — ignore */ }
    }

    // Chart.js theme adapter — paints axes/grid/labels in the active palette
    // and re-renders existing charts when the theme flips.
    function paintChartDefaults(theme) {
        if (typeof window.Chart === 'undefined') return;
        var isDark = (theme === 'dark');
        var defaults = window.Chart.defaults;
        defaults.color           = isDark ? '#CBD5E1' : '#475569';
        defaults.borderColor     = isDark ? 'rgba(148,163,184,.20)' : 'rgba(15,23,42,.10)';
        if (defaults.scale && defaults.scale.grid)  defaults.scale.grid.color = defaults.borderColor;
        if (defaults.scales) {
            ['x','y','r'].forEach(function (k) {
                if (defaults.scales[k]) {
                    if (defaults.scales[k].grid)        defaults.scales[k].grid.color        = defaults.borderColor;
                    if (defaults.scales[k].ticks)       defaults.scales[k].ticks.color       = defaults.color;
                    if (defaults.scales[k].angleLines)  defaults.scales[k].angleLines.color  = defaults.borderColor;
                    if (defaults.scales[k].pointLabels) defaults.scales[k].pointLabels.color = defaults.color;
                }
            });
        }
        if (defaults.plugins) {
            if (defaults.plugins.legend && defaults.plugins.legend.labels) {
                defaults.plugins.legend.labels.color = defaults.color;
            }
            if (defaults.plugins.title) defaults.plugins.title.color = defaults.color;
        }
        // Re-render any already-mounted Chart.js instances.
        try {
            var registry = window.Chart.instances || {};
            Object.keys(registry).forEach(function (k) {
                var c = registry[k]; if (!c || typeof c.update !== 'function') return;
                if (c.options && c.options.scales) {
                    Object.keys(c.options.scales).forEach(function (sk) {
                        var s = c.options.scales[sk]; if (!s) return;
                        if (s.grid)        s.grid.color        = defaults.borderColor;
                        if (s.ticks)       s.ticks.color       = defaults.color;
                        if (s.angleLines)  s.angleLines.color  = defaults.borderColor;
                        if (s.pointLabels) s.pointLabels.color = defaults.color;
                    });
                }
                if (c.options && c.options.plugins && c.options.plugins.legend && c.options.plugins.legend.labels) {
                    c.options.plugins.legend.labels.color = defaults.color;
                }
                c.update('none');
            });
        } catch (e) {}
    }

    // Paint Chart.js immediately if it's already loaded, otherwise on window-load
    paintChartDefaults(document.documentElement.getAttribute('data-theme'));
    window.addEventListener('load', function () {
        paintChartDefaults(document.documentElement.getAttribute('data-theme'));
    });
    document.addEventListener('themechange', function (e) {
        paintChartDefaults(e.detail && e.detail.theme);
    });

    // Tiny public API for pages that need to query/set the active theme.
    window.AppTheme = {
        current: function () {
            var t = document.documentElement.getAttribute('data-theme');
            return isValid(t) ? t : 'light';
        },
        set:    function (t) { applyTheme(t); persist(t); },
        cycle:  cycle,
        // Alias retained so any older caller invoking AppTheme.toggle()
        // keeps working — it now cycles through all three themes.
        toggle: cycle
    };
})();
