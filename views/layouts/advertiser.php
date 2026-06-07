<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
</head>
<body>
<?php if (Auth::isImpersonating()): ?>
<div style="background:#F59E0B;color:#1F2937;padding:9px 20px;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9999;box-shadow:0 1px 4px rgba(0,0,0,.15)">
    <span>&#128064; Admin mode &mdash; viewing as <strong><?= Helpers::e($_SESSION['user_name']) ?></strong> (Advertiser)</span>
    <a href="/admin/stop-impersonate" style="background:#1F2937;color:#fff;padding:4px 14px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;">&#8617; Return to Admin</a>
</div>
<?php endif; ?>
<style>
/* Advertiser mobile polish — pill compact + bell only on tiny phones. */
@media (max-width: 480px){
    #adv-bal-pill{ padding:4px 9px !important; font-size:11px !important; }
    .topbar-actions .user-menu span{ display:none !important; }
}
@media (max-width: 360px){
    #adv-notif-bell{ display:none !important; }
}
</style>
<div class="app-layout">
<aside class="sidebar">
    <div class="sidebar-top">
        <a class="sidebar-logo" href="/advertiser/dashboard">
            <?php if ($siteLogo = Config::get('config','app.logo')): ?>
            <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px;max-width:140px;object-fit:contain">
            <?php else: ?>
            <div class="logo-icon" style="background:#0F766E">&#128200;</div>
            <span><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
            <?php endif; ?>
        </a>
        <button class="mobile-close-btn" onclick="window.toggleSidebar&&window.toggleSidebar(event)" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </button>
    </div>
    <p class="sidebar-section">Menu</p>
    <a href="/advertiser/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/dashboard')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="/advertiser/offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/offers')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>My Offers</a>
    <a href="/advertiser/reports" class="nav-link <?= ($_SERVER['REQUEST_URI']==='/advertiser/reports' || $_SERVER['REQUEST_URI']==='/advertiser/reports/')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Reports</a>
    <a href="/advertiser/reports/clicks" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/reports/clicks')?'active':'' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Click Report</a>
    <a href="/advertiser/reports/conversions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/reports/conversions')?'active':'' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/><path d="M22 12a10 10 0 1 1-7-9.5"/></svg>Conversion Report</a>
    <a href="/advertiser/reports/performance" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/reports/performance')?'active':'' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Performance</a>
    <a href="/advertiser/reports/offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/reports/offers')?'active':'' ?>" style="padding-left:36px;font-size:13px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/></svg>Offer Report</a>
    <a href="/advertiser/postback-logs" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/postback-logs')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Postback Log</a>
    <a href="/advertiser/billing" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/advertiser/billing')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Billing</a>
</aside>
<div class="main-content">
<?php
// Pre-compute advertiser balance + unread notification count for the topbar.
try { AdvBudget::ensureSchema(); } catch(\Throwable $_e) {}
$_advRow = Database::fetchOne("SELECT balance FROM advertisers WHERE user_id=?", [Auth::id()]);
$_advBal = (float)($_advRow['balance'] ?? 0);
try { $_advUnread = (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_read=0", [Auth::id()])['c'] ?? 0); } catch(\Throwable $_e) { $_advUnread = 0; }
?>
<header class="topbar">
    <button id="sidebarToggle" type="button" aria-label="Toggle menu" onclick="window.toggleSidebar&&window.toggleSidebar(event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" pointer-events="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span class="topbar-title"><?= Helpers::e($pageTitle ?? 'Dashboard') ?></span>
    <div class="topbar-actions">
        <?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?>
        <!-- Balance pill -->
        <a href="/advertiser/billing" id="adv-bal-pill"
           title="Available balance — click to top up"
           style="display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;font-size:12px;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(15,118,110,.25)">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span id="adv-bal-display">$<?= number_format($_advBal, 2) ?></span>
        </a>
        <!-- Notifications bell -->
        <a href="/advertiser/billing" id="adv-notif-bell"
           title="Notifications"
           style="position:relative;padding:6px;border-radius:6px;color:var(--text-muted);text-decoration:none;display:flex;align-items:center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span id="adv-notif-badge" style="display:<?= $_advUnread > 0 ? 'flex' : 'none' ?>;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1"><?= $_advUnread > 9 ? '9+' : $_advUnread ?></span>
        </a>
        <div class="user-menu" style="position:relative">
            <div class="user-avatar" style="background:#0F766E"><?= strtoupper(substr(Auth::currentUser()['first_name']??'A',0,1)) ?></div>
            <span><?= Helpers::e(Auth::currentUser()['first_name']??'Advertiser') ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="dropdown-menu">
                <a href="/advertiser/billing" class="dropdown-item">💰 Billing &amp; Top-Up</a>
                <hr class="dropdown-divider">
                <a href="/logout" class="dropdown-item">Sign Out</a>
            </div>
        </div>
    </div>
</header>
<script>
// User-menu dropdown + mobile sidebar toggle.
document.addEventListener('click', function(e){
    var um = e.target.closest('.user-menu');
    if (um) {
        var dm = um.querySelector('.dropdown-menu');
        if (dm) { dm.classList.toggle('show'); e.stopPropagation(); }
    } else {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(d){ d.classList.remove('show'); });
    }
});
(function(){
    var bd = null;
    var _toggling = false;
    function getSidebar(){ return document.querySelector('.sidebar'); }
    function ensureBackdrop(){
        if (bd && document.body.contains(bd)) return bd;
        bd = document.createElement('div');
        bd.id = 'sidebarBackdrop';
        bd.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:150;display:none;touch-action:none;-webkit-tap-highlight-color:transparent;';
        bd.addEventListener('touchend', function(e){ e.preventDefault(); sbClose(); }, {passive:false});
        bd.addEventListener('click', sbClose);
        document.body.appendChild(bd);
        return bd;
    }
    function sbOpen(){
        var sb = getSidebar(); if (!sb) return;
        sb.classList.add('open');
        ensureBackdrop().style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function sbClose(){
        var sb = getSidebar(); if (sb) sb.classList.remove('open');
        if (bd) bd.style.display = 'none';
        document.body.style.overflow = '';
    }
    window.toggleSidebar = function(e){
        if (e && e.stopPropagation) e.stopPropagation();
        if (e && e.preventDefault) e.preventDefault();
        if (_toggling) return; // debounce
        _toggling = true;
        setTimeout(function(){ _toggling = false; }, 300);
        var sb = getSidebar(); if (!sb) return;
        if (sb.classList.contains('open')) sbClose(); else sbOpen();
    };
    function bind(){
        var btn = document.getElementById('sidebarToggle');
        if (!btn || btn.dataset.sbBound === '1') return;
        btn.dataset.sbBound = '1';
        btn.addEventListener('touchend', function(e){
            e.preventDefault();
            window.toggleSidebar(e);
        }, {passive:false});
        btn.addEventListener('click', window.toggleSidebar);
        btn.style.touchAction = 'manipulation';
        btn.style.webkitTapHighlightColor = 'transparent';
        btn.style.userSelect = 'none';
        btn.style.webkitUserSelect = 'none';
    }
    bind();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    document.addEventListener('click', function(e){
        var sb = getSidebar();
        if (!sb || !sb.classList.contains('open')) return;
        var a = e.target.closest('.sidebar a');
        if (a && a.getAttribute('href') && a.getAttribute('href') !== '#') sbClose();
    });
    document.addEventListener('keydown', function(e){
        var sb = getSidebar();
        if (e.key === 'Escape' && sb && sb.classList.contains('open')) sbClose();
    });
    window.addEventListener('resize', function(){
        var sb = getSidebar();
        if (window.innerWidth > 768 && sb && sb.classList.contains('open')) sbClose();
    });
})();
// Live balance + notification badge refresh — keeps the topbar accurate
// without a page reload. Fires every 30s; pauses while tab is hidden.
(function(){
    function poll(){
        if (document.hidden) return;
        fetch('/advertiser/api/balance', { cache:'no-store' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d && typeof d.balance !== 'undefined') {
                    var el = document.getElementById('adv-bal-display');
                    if (el) el.textContent = '$' + Number(d.balance).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
                }
                var badge = document.getElementById('adv-notif-badge');
                if (badge) {
                    if (d && d.unread > 0) {
                        badge.textContent = d.unread > 9 ? '9+' : d.unread;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }).catch(function(){});
    }
    setInterval(poll, 30000);
    document.addEventListener('visibilitychange', function(){ if (!document.hidden) poll(); });
})();

// ── Preserve Sidebar Scroll Position ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var sb = document.querySelector('.sidebar');
    if (!sb) return;
    var savedPos = sessionStorage.getItem('adv_sidebar_scroll');
    if (savedPos) sb.scrollTop = parseInt(savedPos, 10);
    
    var saveScroll = function() { sessionStorage.setItem('adv_sidebar_scroll', sb.scrollTop); };
    sb.addEventListener('scroll', saveScroll, {passive: true});
    window.addEventListener('beforeunload', saveScroll);
});
</script>
<main class="page-body">
<?php foreach(Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type']==='error'?'error':($f['type']==='success'?'success':'info') ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>
