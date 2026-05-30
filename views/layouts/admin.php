<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= Auth::generateCsrf() ?>">
<title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<?php if ($fav = Config::get('config','app.favicon')): ?><link rel="icon" href="<?= Helpers::e($fav) ?>"><?php endif; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<style>
/* ── In-House Fraud Detection Sidebar Module ───────────────────────── */
.fds-nav-section { margin: 4px 0; }
.fds-nav-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 700;
    color: #C7D2FE;
    transition: all .2s;
    margin: 2px 0;
    letter-spacing: .01em;
}
.fds-nav-toggle:hover { background: linear-gradient(135deg, #312E81 0%, #4338CA 100%); color: #fff; }
.fds-nav-toggle.open  { background: linear-gradient(135deg, #312E81 0%, #4F46E5 100%); color: #fff; border-radius: 8px 8px 0 0; }
.fds-nav-toggle-left  { display: flex; align-items: center; gap: 8px; }
.fds-nav-toggle-left svg { flex-shrink: 0; }
.fds-live-badge {
    font-size: 9px;
    font-weight: 800;
    background: #EF4444;
    color: #fff;
    padding: 2px 5px;
    border-radius: 4px;
    letter-spacing: .05em;
    animation: fds-pulse-bg 2s ease-in-out infinite;
}
@keyframes fds-pulse-bg {
    0%,100% { background: #EF4444; }
    50%      { background: #DC2626; }
}
.fds-chevron {
    transition: transform .25s;
    flex-shrink: 0;
}
.fds-nav-toggle.open .fds-chevron { transform: rotate(180deg); }
.fds-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease;
    opacity: 0;
    background: #1E1B4B;
    border-radius: 0 0 8px 8px;
    margin-bottom: 2px;
}
.fds-submenu.open { max-height: 600px; opacity: 1; }
.fds-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px 7px 18px;
    font-size: 12px;
    font-weight: 500;
    color: #A5B4FC;
    text-decoration: none;
    transition: all .15s;
    border-left: 2px solid transparent;
    position: relative;
}
.fds-link svg { flex-shrink:0; width:14px; height:14px; }
.fds-link:hover { color: #fff; background: rgba(99,102,241,.15); border-left-color: #6366F1; text-decoration: none; }
.fds-link.active { color: #fff; background: rgba(99,102,241,.25); border-left-color: #A5B4FC; font-weight: 700; }
.fds-pulse-dot {
    width: 7px; height: 7px;
    background: #10B981;
    border-radius: 50%;
    margin-left: auto;
    flex-shrink: 0;
    animation: fds-dot-pulse 1.5s ease-in-out infinite;
}
@keyframes fds-dot-pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50%      { opacity: .4; transform: scale(.7); }
}
/* ── Fraud Center page chrome ──────────────────────────────────────── */
.fds-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
    padding: 20px 24px;
    background: linear-gradient(135deg, #1E1B4B 0%, #312E81 50%, #4F46E5 100%);
    border-radius: 14px;
    color: #fff;
}
.fds-page-header h1 { margin: 0; font-size: 20px; font-weight: 800; color: #fff; display:flex; align-items:center; gap:10px; }
.fds-page-header p  { margin: 4px 0 0; font-size: 13px; color: #C7D2FE; }
.fds-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 22px; }
.fds-stat {
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
}
.fds-stat::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--fds-color, #4F46E5);
}
.fds-stat-label  { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
.fds-stat-value  { font-size: 26px; font-weight: 800; color: #111827; line-height: 1; }
.fds-stat-sub    { font-size: 11px; color: #9CA3AF; margin-top: 4px; }
.fds-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
.fds-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #F3F4F6;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
}
.fds-card-body   { padding: 18px; }
.fds-table       { width: 100%; border-collapse: collapse; font-size: 13px; }
.fds-table thead th { padding: 10px 12px; background: #F9FAFB; font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #E5E7EB; text-align: left; white-space: nowrap; }
.fds-table tbody td { padding: 11px 12px; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
.fds-table tbody tr:last-child td { border-bottom: none; }
.fds-table tbody tr:hover { background: #FAFBFF; }
.fds-score { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; padding: 3px 8px; border-radius: 20px; white-space: nowrap; }
.fds-score-low      { background: #DCFCE7; color: #166534; }
.fds-score-medium   { background: #FEF9C3; color: #854D0E; }
.fds-score-high     { background: #FFEDD5; color: #9A3412; }
.fds-score-critical { background: #FEE2E2; color: #991B1B; }
.fds-badge-allow  { background: #DCFCE7; color: #166534; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fds-badge-flag   { background: #FEF3C7; color: #92400E; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fds-badge-block  { background: #FEE2E2; color: #991B1B; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fds-empty { text-align: center; padding: 48px 20px; color: #9CA3AF; }
.fds-signal-tag { display: inline-block; background: #EEF2FF; color: #4338CA; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px; margin: 1px; }
/* ── Additional FDS classes ─────────────────────────────────────────── */
.fds-page-header-left { display:flex;align-items:center;gap:14px; }
.fds-page-icon { font-size:28px;line-height:1; }
.fds-page-title { font-size:20px;font-weight:800;color:#fff;margin:0; }
.fds-page-sub  { font-size:13px;color:#C7D2FE;margin:3px 0 0; }
.fds-card-title { font-size:14px;font-weight:700;color:#111827; }
.fds-table-wrap { overflow-x:auto; }
.fds-section-label { font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px; }
.fds-text-sm  { font-size:12px; }
.fds-text-muted { color:#9CA3AF; }
.fds-link { color:#7C3AED;text-decoration:none; }
.fds-link:hover { text-decoration:underline; }
/* KPI grid */
.fds-kpi-grid { display:grid;gap:14px;margin-bottom:16px; }
.fds-kpi { background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:16px 18px;position:relative;overflow:hidden; }
.fds-kpi::before { content:'';position:absolute;top:0;left:0;right:0;height:3px;background:#4F46E5; }
.fds-kpi-danger::before { background:#EF4444; }
.fds-kpi-warn::before   { background:#F59E0B; }
.fds-kpi-label { font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px; }
.fds-kpi-val   { font-size:26px;font-weight:800;color:#111827;line-height:1; }
/* Badge base */
.fds-badge { display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700; }
.fds-badge-critical { background:#FEE2E2;color:#991B1B; }
.fds-badge-high     { background:#FFEDD5;color:#9A3412; }
.fds-badge-medium   { background:#FEF9C3;color:#854D0E; }
.fds-badge-low      { background:#DCFCE7;color:#166534; }
.fds-badge-muted    { background:#F3F4F6;color:#6B7280; }
/* Buttons */
.fds-btn { display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:opacity .15s; }
.fds-btn:hover { opacity:.85; }
.fds-btn-sm { padding:4px 10px;font-size:12px; }
.fds-btn-primary { background:#4F46E5;color:#fff; }
.fds-btn-outline  { background:transparent;border:1px solid #D1D5DB;color:#374151; }
.fds-btn-danger   { background:#EF4444;color:#fff; }
/* Toggle */
.fds-toggle { width:36px;height:20px;border-radius:20px;background:#D1D5DB;border:none;cursor:pointer;position:relative;transition:background .2s; }
.fds-toggle::after { content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;border-radius:50%;background:#fff;transition:left .2s; }
.fds-toggle-on { background:#4F46E5; }
.fds-toggle-on::after { left:19px; }
/* Tabs */
.fds-tabs { display:flex;gap:4px;margin-bottom:16px;border-bottom:2px solid #E5E7EB;padding-bottom:0; }
.fds-tab { padding:8px 14px;font-size:13px;font-weight:600;color:#6B7280;text-decoration:none;border-radius:8px 8px 0 0;border:1px solid transparent;border-bottom:none;margin-bottom:-2px; }
.fds-tab.active { color:#4F46E5;background:#fff;border-color:#E5E7EB;border-bottom-color:#fff; }
.fds-tab:hover:not(.active) { color:#374151;background:#F9FAFB; }
/* Pulse */
@keyframes fds-pulse { 0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.65)} }
/* Pagination */
.fds-pagination { display:flex;gap:4px;flex-wrap:wrap;align-items:center; }
.fds-page-btn { padding:5px 11px;border-radius:7px;font-size:12px;font-weight:600;border:1px solid #D1D5DB;background:#fff;color:#374151;text-decoration:none; }
.fds-page-btn.active { background:#4F46E5;color:#fff;border-color:#4F46E5; }
.fds-page-btn:hover:not(.active) { background:#F3F4F6; }
</style>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// App-wide timezone (IANA) set by admin in Settings — used for all timestamp formatting
window.APP_TIMEZONE = <?= json_encode(Config::get('config','app.timezone') ?? 'UTC') ?>;
function fmtTs(ts, opts) {
    try {
        return new Intl.DateTimeFormat(navigator.language || 'en', Object.assign(
            { timeZone: window.APP_TIMEZONE, year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' },
            opts || {}
        )).format(new Date(ts));
    } catch(e) { return ts; }
}
</script>
</head>
<body>
<div class="app-layout">
<!-- Sidebar -->
<aside class="sidebar">
    <a class="sidebar-logo" href="/admin/dashboard">
        <?php if ($siteLogo = Config::get('config','app.logo')): ?>
        <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px;max-width:140px;object-fit:contain">
        <?php else: ?>
        <div class="logo-icon">&#127760;</div>
        <span><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
        <?php endif; ?>
    </a>

    <p class="sidebar-section">Main</p>
    <a href="/admin/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/dashboard') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="/admin/news" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/news') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
        News
        <?php
        try { $draftNews = Database::count('news','status=?',['draft']); if($draftNews): ?>
        <span class="nav-badge" style="background:#F59E0B"><?= $draftNews ?></span>
        <?php endif; } catch(\Throwable $e) {} ?>
    </a>

    <p class="sidebar-section">Landing Page</p>
    <a href="/admin/landing/sliders" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/landing/sliders') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Sliders
    </a>
    <a href="/admin/landing/blog" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/landing/blog') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        Blog Posts
        <?php
        try { $draftPosts = Database::count('landing_posts','status=?',['draft']); if($draftPosts): ?>
        <span class="nav-badge" style="background:#F59E0B"><?= $draftPosts ?></span>
        <?php endif; } catch(\Throwable $e) {} ?>
    </a>
    <a href="/admin/landing/reviews" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/landing/reviews') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        Reviews
        <?php
        try { $pendingRevs = Database::count('landing_reviews','status=?',['pending']); if($pendingRevs): ?>
        <span class="nav-badge" style="background:#F59E0B"><?= $pendingRevs ?></span>
        <?php endif; } catch(\Throwable $e) {} ?>
    </a>

    <a href="/admin/referrals" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/referrals') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
        Referral System
    </a>

    <p class="sidebar-section">Users</p>
    <a href="/admin/affiliates" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliates') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Affiliates
        <?php $pending = Database::count('users','role=? AND status=?',['affiliate','pending']); if($pending): ?>
        <span class="nav-badge"><?= $pending ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/advertisers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/advertisers') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 11a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3 .18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 7.91a16 16 0 0 0 5.5 5.5l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 14h.92z"/></svg>
        Advertisers
    </a>

    <p class="sidebar-section">Offers & Links</p>
    <a href="/admin/offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/offers') && !str_contains($_SERVER['REQUEST_URI'],'/admin/offer-approvals') && !str_contains($_SERVER['REQUEST_URI'],'/admin/inhouse-offers') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        Offers
    </a>
    <a href="/admin/offer-approvals" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/offer-approvals') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="9 15 11 17 15 13"/></svg>
        Approval Requests
        <?php $pendingOfferReqs = Database::count('affiliate_offers','status=?',['pending']); if($pendingOfferReqs): ?>
        <span class="nav-badge"><?= $pendingOfferReqs ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/inhouse-offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/inhouse-offers') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        In-House Offers
    </a>
    <a href="/admin/private-offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/private-offers') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Private Offers
    </a>
    <a href="/admin/smartlinks" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/smartlinks') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Smartlinks
        <?php
        try {
            $_slPending = Database::fetchOne("SELECT COUNT(*) as cnt FROM smartlink_requests WHERE status='pending'");
            if (!empty($_slPending['cnt'])): ?>
            <span style="background:#EF4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700;margin-left:auto"><?= (int)$_slPending['cnt'] ?></span>
        <?php endif; } catch(\Exception $e) {} ?>
    </a>

    <p class="sidebar-section">Tracking</p>
    <a href="/admin/conversions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/conversions') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Conversions
    </a>
    <a href="/admin/rejection-reasons" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/rejection-reasons') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        Rejection Reasons
    </a>
    <a href="/admin/postbacks" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/postbacks') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
        Global Postbacks
    </a>
    <a href="/admin/affiliate-global-postbacks" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/affiliate-global-postbacks') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/><path d="M12 8v4l3 3"/></svg>
        Global PB Mgmt
        <?php try {
            $gpbPending = Database::fetchOne("SELECT COUNT(*) as c FROM affiliates WHERE global_pb_admin_status='pending' AND global_postback_url IS NOT NULL AND global_postback_url!=''")['c'] ?? 0;
            if ($gpbPending > 0): ?>
        <span class="nav-badge"><?= $gpbPending ?></span>
        <?php endif; } catch(\Throwable $e){} ?>
    </a>
    <a href="/admin/postback-logs" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/postback-logs') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Postback Logs
        <?php try {
            $_pbFailed = Database::fetchOne("SELECT COUNT(*) as cnt FROM postback_logs WHERE is_success=0 AND fired_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            if (!empty($_pbFailed['cnt'])): ?>
        <span class="nav-badge" style="background:#EF4444"><?= (int)$_pbFailed['cnt'] ?></span>
        <?php endif; } catch(\Throwable $e){} ?>
    </a>
    <a href="/admin/advertiser-postback-logs" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/advertiser-postback-logs') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Adv Postback Log</a>
    <a href="/admin/affiliate-global-pb-test" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/affiliate-global-pb-test') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
        Postback Test
    </a>
    <a href="/admin/vpn-log" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/vpn-log') ? 'active' : '' ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        VPN Blocked Log
    </a>
    <a href="/admin/vpn-proxy-skip" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/vpn-proxy-skip') ? 'active' : '' ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        VPN/Proxy Skip List
    </a>
    <a href="/admin/fraud" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/fraud') && !str_contains($_SERVER['REQUEST_URI'],'/admin/fraud-score-report') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Fraud Detector
    </a>
    <a href="/admin/fraud-score-report" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-score-report') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Fraud Score Report
    </a>
    <a href="/admin/fraud-alerts" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-alerts') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Fraud Alerts
    </a>
    <a href="/admin/autohide" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/autohide') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        Auto Hide
    </a>
    <a href="/admin/reports" class="nav-link <?= str_starts_with(strtok($_SERVER['REQUEST_URI'],'?'),'/admin/reports') && !str_contains($_SERVER['REQUEST_URI'],'/admin/reports/clicks') && !str_contains($_SERVER['REQUEST_URI'],'/admin/reports/affiliates') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Reports
    </a>
    <a href="/admin/reports/clicks" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/reports/clicks') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9z"/><polyline points="15 3 15 9 21 9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
        Click Report
    </a>
    <a href="/admin/reports/affiliates" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/reports/affiliates') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Affiliate Report
    </a>
    <a href="/admin/reports/duplicate-conversions" class="nav-link <?= str_starts_with(strtok($_SERVER['REQUEST_URI'],'?'),'/admin/reports/duplicate-conversions') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        Duplicate Conversions
    </a>
    <a href="/admin/traffic" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/traffic') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Traffic Stats
    </a>

    <p class="sidebar-section">Finance</p>
    <a href="/admin/invoices" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/invoices') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Invoices
    </a>
    <a href="/admin/payment-settings" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/payment-settings') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Payment Settings
    </a>
    <?php
    // Pending top-up badge — pulls a fast COUNT once per request.
    $_pmPending = 0;
    try { $_pmPending = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM advertiser_payment_requests WHERE status='pending'")['c'] ?? 0); } catch (\Throwable $_pmE) {}
    ?>
    <a href="/admin/payment-requests" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/payment-requests') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Advertiser Top-Ups
        <?php if ($_pmPending > 0): ?>
        <span style="margin-left:auto;background:#EF4444;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 7px"><?= $_pmPending ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/account-delete-requests" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/account-delete-requests') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        Delete Requests
        <?php try { $_delPending = Database::fetchOne("SELECT COUNT(*) as c FROM account_delete_requests WHERE status='pending'")['c'] ?? 0; if ($_delPending > 0): ?>
        <span style="margin-left:auto;background:#EF4444;color:#fff;border-radius:999px;font-size:10px;font-weight:700;padding:1px 7px"><?= $_delPending ?></span>
        <?php endif; } catch(\Throwable $e) {} ?>
    </a>

    <p class="sidebar-section">Settings</p>
    <a href="/admin/affiliate-managers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliate-managers') && !str_contains($_SERVER['REQUEST_URI'],'/permissions') && !str_contains($_SERVER['REQUEST_URI'],'/fraud-rejections') && !str_contains($_SERVER['REQUEST_URI'],'/messages') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Aff. Managers
        <?php $pendingMgr = Database::count('users','role=? AND status=?',['affiliate_manager','pending']); if($pendingMgr): ?>
        <span class="nav-badge"><?= $pendingMgr ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/affiliate-managers/permissions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliate-managers/permissions') ? 'active' : '' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Permissions
    </a>
    <a href="/admin/affiliate-managers/fraud-rejections" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliate-managers/fraud-rejections') ? 'active' : '' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        Fraud Rejections
    </a>
    <a href="/admin/affiliate-managers/messages" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliate-managers/messages') ? 'active' : '' ?>" style="padding-left:36px;font-size:13px">
        <?php
        // Unread badge (admin side) — sums unread messages across all threads.
        try { ManagerPermissions::ensureSchema(); $_amUnread = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM manager_messages WHERE sender_role='manager' AND read_by_admin=0")['c'] ?? 0); } catch (\Throwable $_) { $_amUnread = 0; }
        ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        Messages
        <?php if ($_amUnread > 0): ?><span class="nav-badge" style="background:#EF4444"><?= $_amUnread ?></span><?php endif; ?>
    </a>
    <a href="/admin/affiliate-managers/invoice-requests" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/affiliate-managers/invoice-requests') ? 'active' : '' ?>" style="padding-left:36px;font-size:13px">
        <?php
        try { $_invReqPending = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM manager_invoice_requests WHERE status='pending'")['c'] ?? 0); } catch (\Throwable $_) { $_invReqPending = 0; }
        ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Invoice Requests
        <?php if ($_invReqPending > 0): ?><span class="nav-badge" style="background:#7C3AED"><?= $_invReqPending ?></span><?php endif; ?>
    </a>
    <a href="/admin/settings" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/settings') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Settings
    </a>

    <p class="sidebar-section">Rewards &amp; Shop</p>
    <a href="/admin/points" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/points') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M9 9h4.5a2 2 0 1 1 0 4H9m0 0h4.5a2 2 0 1 1 0 4H9"/></svg>
        Points
    </a>
    <a href="/admin/shop" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/shop') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        Shop
    </a>
    <a href="/admin/rewards" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/rewards') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
        Rewards
    </a>
    <a href="/admin/popup" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/popup') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Popup
    </a>
    <a href="/admin/registration-questions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'registration-questions') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Reg. Questions
    </a>
    <a href="/admin/notifications" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/notifications') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifications
    </a>
    <a href="/admin/email" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/email') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Email Notifications
    </a>
    <a href="/admin/search-console" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/search-console') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Search Console
    </a>
    <a href="/admin/database" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/admin/database') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        Database Tools
    </a>

    <?php
    // ── In-House Fraud Detection System ─────────────────────────────────────
    $_fraudActive  = str_starts_with($_SERVER['REQUEST_URI'], '/admin/fraud-center');
    $_fraudOpen    = $_fraudActive || !empty($_SESSION['fraud_menu_open']);
    $_fraudEnabled = (bool)(Config::get('config', 'fraud.enabled') ?? 1);
    ?>
    <div class="fds-nav-section">
        <button class="fds-nav-toggle <?= $_fraudOpen ? 'open' : '' ?>"
                onclick="toggleFraudMenu(this)" type="button">
            <span class="fds-nav-toggle-left">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9 12l2 2 4-4" stroke-width="2.5"/>
                </svg>
                <span>Fraud Detection</span>
            </span>
            <span style="display:flex;align-items:center;gap:5px">
                <?php if ($_fraudEnabled): ?>
                <span class="fds-live-badge">LIVE</span>
                <?php else: ?>
                <span class="fds-live-badge" style="background:rgba(239,68,68,.25);color:#FCA5A5;border-color:rgba(239,68,68,.4);animation:none">OFF</span>
                <?php endif; ?>
                <svg class="fds-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
        </button>
        <div class="fds-submenu <?= $_fraudOpen ? 'open' : '' ?>" id="fraudSubmenu">
            <a href="/admin/fraud-center/live-monitor"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/live-monitor') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Live Threat Monitor
                <span class="fds-pulse-dot"></span>
            </a>
            <a href="/admin/fraud-center/click-intelligence"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/click-intelligence') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9z"/><polyline points="15 3 15 9 21 9"/></svg>
                Click Intelligence
            </a>
            <a href="/admin/fraud-center/bot-detection"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/bot-detection') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Bot Detection Center
            </a>
            <a href="/admin/fraud-center/conversion-scanner"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/conversion-scanner') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Conversion Fraud Scanner
                <?php try {
                    $fds_sus_conv = Database::fetchOne(
                        "SELECT COUNT(*) as c FROM conversions
                         WHERE status='pending'
                         AND (TIMESTAMPDIFF(SECOND, (SELECT clicked_at FROM clicks WHERE click_id=conversions.click_id LIMIT 1), converted_at) < 30
                         OR converted_at >= NOW() - INTERVAL 24 HOUR)"
                    )['c'] ?? 0;
                    if ($fds_sus_conv > 0): ?>
                <span class="nav-badge" style="background:#F59E0B;margin-left:auto"><?= $fds_sus_conv ?></span>
                <?php endif; } catch(\Throwable $e){} ?>
            </a>
            <a href="/admin/fraud-center/risk-engine"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/risk-engine') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Risk Engine
            </a>
            <a href="/admin/fraud-center/ip-intelligence"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/ip-intelligence') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
                Device & IP Intelligence
            </a>
            <a href="/admin/fraud-center/cases"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/cases') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Fraud Cases
                <?php try {
                    $fds_open_cases = Database::fetchOne("SELECT COUNT(*) as c FROM fraud_cases WHERE status='open'")['c'] ?? 0;
                    if ($fds_open_cases > 0): ?>
                <span class="nav-badge" style="background:#EF4444;margin-left:auto"><?= $fds_open_cases ?></span>
                <?php endif; } catch(\Throwable $e){} ?>
            </a>
            <a href="/admin/fraud-center/auto-rules"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/auto-rules') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Auto Rules Engine
            </a>
            <a href="/admin/fraud-center/blocklist"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/blocklist') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Blocklist Manager
            </a>
            <a href="/admin/fraud-center/fraud-reports"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/fraud-reports') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Fraud Reports
                <?php try {
                    $fds_fraud_pending = Database::fetchOne("SELECT COUNT(*) AS c FROM conversions WHERE status='pending' AND (is_fraud=1 OR fraud_score>=50)")['c'] ?? 0;
                    if ($fds_fraud_pending > 0):
                ?><span class="nav-badge" style="background:#EF4444;margin-left:auto"><?= $fds_fraud_pending ?></span>
                <?php endif; } catch(\Throwable $e){} ?>
            </a>
            <a href="/admin/fraud-center/analytics"
               class="fds-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/fraud-center/analytics') ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Reports & Analytics
            </a>
        </div>
    </div>

    <p class="sidebar-section">Activity</p>
    <a href="/admin/login-logs" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/login-logs') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Login Activity
        <span id="admin-online-badge" style="display:none;background:#10B981;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:auto">0</span>
    </a>
    <a href="/admin/ip-bans" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/ip-bans') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        Login IP Bans
    </a>

    <p class="sidebar-section">Support</p>
    <a href="/admin/support" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/admin/support') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Live Support
        <span id="admin-chat-badge" style="display:none;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:auto">0</span>
    </a>
</aside>

<!-- Main -->
<div class="main-content">
<header class="topbar">
    <button id="sidebarToggle" type="button" aria-label="Toggle menu" onclick="window.toggleSidebar&&window.toggleSidebar(event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" pointer-events="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span class="topbar-title"><?= Helpers::e($pageTitle ?? 'Dashboard') ?></span>
    <div class="topbar-actions">
        <?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?>
        <!-- Chat bell -->
        <a href="/admin/support" style="position:relative;display:flex;align-items:center;padding:6px;color:var(--text-muted);text-decoration:none;border-radius:6px" title="Live Support">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span id="topbar-chat-badge" style="display:none;position:absolute;top:1px;right:1px;background:#EF4444;color:#fff;border-radius:50%;width:15px;height:15px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
        </a>
        <a href="/admin/notifications" class="notification-btn" style="text-decoration:none">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="notif-badge" style="display:none">0</span>
        </a>
        <div class="user-menu" style="position:relative">
            <div class="user-avatar"><?= strtoupper(substr(Auth::currentUser()['first_name'] ?? 'A', 0, 1)) ?></div>
            <span><?= Helpers::e(Auth::currentUser()['first_name'] ?? 'Admin') ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="dropdown-menu">
                <span class="dropdown-item text-muted text-sm" style="font-size:11px;padding:8px 14px 4px;color:#94A3B8"><?= Helpers::e(Auth::currentUser()['email'] ?? '') ?></span>
                <hr class="dropdown-divider">
                <a href="/admin/profile" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    My Profile
                </a>
                <a href="/admin/support" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Live Support
                </a>
                <hr class="dropdown-divider">
                <a href="/logout" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</header>
<script>
// User-menu dropdown toggle (logout, profile etc.)
document.addEventListener('click', function(e) {
    var um = e.target.closest('.user-menu');
    if (um) {
        var dm = um.querySelector('.dropdown-menu');
        if (dm) { dm.classList.toggle('show'); e.stopPropagation(); }
    } else {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(d){ d.classList.remove('show'); });
    }
});
</script>

<main class="page-body">
<?php foreach (Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type'] === 'error' ? 'error' : ($f['type'] === 'success' ? 'success' : 'info') ?>" data-auto-hide>
    <?= Helpers::e($f['message']) ?>
</div>
<?php endforeach; ?>
<script>
(function pollAdminChat(){
    fetch('/api/chat?action=unread_count')
    .then(function(r){return r.json();})
    .then(function(d){
        var n = d.count||0;
        var sb = document.getElementById('admin-chat-badge');
        var tb = document.getElementById('topbar-chat-badge');
        if (sb) { sb.textContent=n; sb.style.display=n>0?'inline':'none'; }
        if (tb) { tb.textContent=n; tb.style.display=n>0?'flex':'none'; }
    }).catch(function(){});
    setTimeout(pollAdminChat, 30000);
})();

(function pollOnlineBadge(){
    fetch('/api/activity?action=online_count')
    .then(function(r){return r.json();})
    .then(function(d){
        var n = d.count||0;
        var b = document.getElementById('admin-online-badge');
        if (b) { b.textContent=n; b.style.display=n>0?'inline':'none'; }
    }).catch(function(){});
    setTimeout(pollOnlineBadge, 30000);
})();

(function heartbeat(){
    fetch('/api/activity', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=heartbeat&page='+encodeURIComponent(window.location.pathname)
    }).then(function(r){return r.json();}).then(function(d){if(d&&d.error==='session_killed')window.location.href='/login';}).catch(function(){});
    setTimeout(heartbeat, 30000);
})();

// ── Mobile sidebar toggle (hamburger) ──────────────────────────────────
// Single authoritative implementation. window.toggleSidebar is called by
// the button's inline onclick AND by the addEventListener binding below.
// Guard flag (sbBound) prevents duplicate listener registration on any
// page including AJAX-heavy sections (Fraud Alerts, Affiliate Report,
// Duplicate Conversions, VPN/Proxy, Payment Requests, etc.).
(function(){
    var bd = null;
    var _toggling = false; // debounce: prevents double-fire on fast taps

    function getSidebar(){ return document.querySelector('.sidebar'); }

    function ensureBackdrop(){
        // Reuse existing backdrop if already in DOM (survives AJAX redraws)
        if (!bd || !document.body.contains(bd)){
            // Remove any stale .sidebar-overlay injected by older app.js versions
            document.querySelectorAll('.sidebar-overlay').forEach(function(el){ el.remove(); });
            bd = document.createElement('div');
            bd.id = 'sidebarBackdrop';
            // z-index 249: above sidebar (200), below topbar dropdowns (9999)
            bd.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);'
                             + 'z-index:249;display:none;touch-action:none;'
                             + '-webkit-tap-highlight-color:transparent;';
            // touchend fires ~300ms before click — instant response on mobile
            bd.addEventListener('touchend', function(e){ e.preventDefault(); sbClose(); }, {passive:false});
            bd.addEventListener('click', sbClose);
            document.body.appendChild(bd);
            if (!document.getElementById('sidebarBackdropStyles')){
                var st = document.createElement('style');
                st.id = 'sidebarBackdropStyles';
                st.textContent = '@keyframes sb-fade{from{opacity:0}to{opacity:1}}'
                               + '#sidebarBackdrop.visible{display:block;animation:sb-fade .18s ease-out}';
                document.head.appendChild(st);
            }
        }
        return bd;
    }

    function sbOpen(){
        var sb = getSidebar(); if (!sb) return;
        sb.style.willChange = 'transform'; // prime GPU layer before CSS transition
        sb.classList.add('open');
        ensureBackdrop().classList.add('visible');
        document.body.style.overflow = 'hidden';
        // Release will-change after transition to free GPU memory
        setTimeout(function(){ if (sb) sb.style.willChange = ''; }, 320);
    }

    function sbClose(){
        var sb = getSidebar();
        if (sb){ sb.classList.remove('open'); sb.style.willChange = ''; }
        if (bd){ bd.classList.remove('visible'); bd.style.display = 'none'; }
        document.body.style.overflow = '';
    }

    // Globally exposed — used by the button's inline onclick attribute
    window.toggleSidebar = function(e){
        if (e && e.stopPropagation) e.stopPropagation();
        if (e && e.preventDefault) e.preventDefault();
        if (_toggling) return; // debounce — ignore rapid re-taps
        _toggling = true;
        setTimeout(function(){ _toggling = false; }, 300);
        var sb = getSidebar(); if (!sb) return;
        if (sb.classList.contains('open')) sbClose(); else sbOpen();
    };

    // Bind touchend + click to the hamburger button.
    // data-sbBound prevents duplicate listeners on page transitions / AJAX.
    function bind(){
        var btn = document.getElementById('sidebarToggle');
        if (!btn || btn.dataset.sbBound === '1') return;
        btn.dataset.sbBound = '1';
        // touchend fires before click — zero-delay response on Android/iOS
        btn.addEventListener('touchend', function(e){
            e.preventDefault(); // suppress the follow-up click event
            window.toggleSidebar(e);
        }, {passive:false});
        btn.addEventListener('click', window.toggleSidebar);
        // touch-action: respond to taps, block accidental swipe-scrolling
        btn.style.touchAction = 'manipulation';
        btn.style.webkitTapHighlightColor = 'transparent';
        btn.style.userSelect = 'none';
        btn.style.webkitUserSelect = 'none';
    }

    bind(); // run immediately (script is after the button in DOM)
    if (document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', bind);
    }

    // Close when tapping a real sidebar nav link
    document.addEventListener('click', function(e){
        var sb = getSidebar();
        if (!sb || !sb.classList.contains('open')) return;
        var a = e.target.closest('.sidebar a');
        if (a && a.getAttribute('href') && a.getAttribute('href') !== '#') sbClose();
    }, {passive:true});

    document.addEventListener('keydown', function(e){
        var sb = getSidebar();
        if (e.key === 'Escape' && sb && sb.classList.contains('open')) sbClose();
    }, {passive:true});

    // Auto-close on resize to desktop
    window.addEventListener('resize', function(){
        var sb = getSidebar();
        if (window.innerWidth > 768 && sb && sb.classList.contains('open')) sbClose();
    }, {passive:true});
})();

// ── Fraud Detection sidebar toggle ────────────────────────────────────
function toggleFraudMenu(btn) {
    var sub = document.getElementById('fraudSubmenu');
    var isOpen = sub.classList.contains('open');
    if (isOpen) {
        sub.classList.remove('open');
        btn.classList.remove('open');
    } else {
        sub.classList.add('open');
        btn.classList.add('open');
    }
}
</script>
