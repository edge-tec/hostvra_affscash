<?php
/**
 * Topbar theme picker — two options (Light / Dark).
 *
 * Clicking the button opens a small dropdown. Each option carries
 * data-theme-set="light|dark" and is wired by theme.js (delegated
 * click handler) to call AppTheme.set(). The active option's check mark
 * is updated client-side on theme change so the picker stays in sync
 * without needing a page reload.
 */
?>
<div class="theme-picker" style="position:relative">
    <button type="button" class="theme-toggle"
            data-action="open-theme-picker"
            aria-label="Change theme" title="Change theme">
        <!-- One icon shown at a time depending on active theme -->
        <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
        <svg class="theme-icon-dark"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </button>
    <div class="theme-picker-menu" style="display:none;position:absolute;left:50%;transform:translateX(-50%);top:calc(100% + 6px);min-width:170px;max-width:calc(100vw - 16px);background:var(--card-bg);border:1px solid var(--border);border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.18);z-index:9999;overflow:hidden">
        <button type="button" class="theme-picker-opt" data-theme-set="light"
                style="display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;background:none;border:0;color:var(--text);font-size:13px;cursor:pointer;text-align:left">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            <span style="flex:1">Light Mode</span>
            <span class="theme-picker-check" data-for="light" style="display:none;color:var(--primary);font-weight:700">&#10003;</span>
        </button>
        <button type="button" class="theme-picker-opt" data-theme-set="dark"
                style="display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;background:none;border:0;color:var(--text);font-size:13px;cursor:pointer;text-align:left">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <span style="flex:1">Dark Mode</span>
            <span class="theme-picker-check" data-for="dark" style="display:none;color:var(--primary);font-weight:700">&#10003;</span>
        </button>
    </div>
</div>
