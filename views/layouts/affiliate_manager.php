<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="/assets/css/app.min.css?v=<?= filemtime(BASE_PATH . '/assets/css/app.min.css') ?>">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
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
<?php if (Auth::isImpersonating()): ?>
<div style="background:#F59E0B;color:#1F2937;padding:9px 20px;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9999">
    <span>&#128064; Admin mode — viewing as <strong><?= Helpers::e($_SESSION['user_name']) ?></strong> (Affiliate Manager)</span>
    <a href="/admin/stop-impersonate" style="background:#1F2937;color:#fff;padding:4px 14px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;">&#8617; Return to Admin</a>
</div>
<?php endif; ?>
<div class="app-layout">
<aside class="sidebar">
    <div class="sidebar-top">
        <a class="sidebar-logo" href="/affiliate_manager/dashboard">
            <?php if ($siteLogo = Config::get('config','app.logo')): ?>
            <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px;max-width:140px;object-fit:contain">
            <?php else: ?>
            <div class="logo-icon" style="background:#7C3AED">&#127775;</div>
            <span><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
            <?php endif; ?>
        </a>
        <button class="mobile-close-btn" onclick="window.toggleSidebar&&window.toggleSidebar(event)" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </button>
    </div>

    <p class="sidebar-section">Manager Panel</p>
    <a href="/affiliate_manager/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/dashboard')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>

    <a href="/affiliate_manager/offers" class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/offers') && !str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/inhouse-offers'))?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        Offers
    </a>

    <a href="/affiliate_manager/inhouse-offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/inhouse-offers')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        In-House Offers
    </a>

    <a href="/affiliate_manager/smartlinks" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/smartlinks')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Smart Links
        <?php
        try {
            $_slAffIds = Auth::managerAffiliateIds();
            if (!empty($_slAffIds)) {
                $mgrSlIn = implode(',', array_fill(0, count($_slAffIds), '?'));
                $mgrSlPending = Database::fetchOne(
                    "SELECT COUNT(*) as c FROM smartlink_requests WHERE status='pending' AND affiliate_id IN ($mgrSlIn)",
                    $_slAffIds
                )['c'] ?? 0;
                if ($mgrSlPending > 0): ?>
        <span class="nav-badge"><?= $mgrSlPending ?></span>
        <?php   endif;
            }
        } catch (\Throwable $e) {}
        ?>
    </a>

    <?php if (Auth::hasPermission('view_affiliates')): ?>
    <a href="/affiliate_manager/affiliates" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/affiliates')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        My Affiliates
    </a>
    <a href="/affiliate_manager/offer-approvals" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/offer-approvals')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="9 15 11 17 15 13"/></svg>
        Approval Requests
        <?php
        try {
            $mgrAffIds = Auth::managerAffiliateIds();
            if (!empty($mgrAffIds)) {
                $mgrInSql = implode(',', array_fill(0, count($mgrAffIds), '?'));
                $mgrPending = Database::fetchOne(
                    "SELECT COUNT(*) as c FROM affiliate_offers WHERE status='pending' AND affiliate_id IN ($mgrInSql)",
                    $mgrAffIds
                )['c'] ?? 0;
                if ($mgrPending > 0): ?>
        <span class="nav-badge"><?= $mgrPending ?></span>
        <?php   endif;
            }
        } catch (\Throwable $e) {}
        ?>
    </a>
    <a href="/affiliate_manager/referral" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/referral')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
        Referral Link
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('view_conversions')): ?>
    <a href="/affiliate_manager/conversions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/conversions')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Conversions
    </a>
    <?php endif; ?>

    <?php if (Auth::hasPermission('view_reports')): ?>
    <a href="/affiliate_manager/reports" class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/reports') && !str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/fraud-report'))?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Reports
    </a>
    <a href="/affiliate_manager/duplicate-conversions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/duplicate-conversions')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        Duplicate Conversions
    </a>
    <?php if (ManagerPermissions::can(ManagerPermissions::currentManagerId(), 'view_fraud_reports')): ?>
    <a href="/affiliate_manager/fraud-report" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate_manager/fraud-report')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Fraud Report
    </a>
    <a href="/affiliate_manager/vpn-log" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate_manager/vpn-log')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        VPN &amp; Proxy Blocked Log
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <a href="/affiliate_manager/analytics" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/analytics')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Analytics
    </a>

    <a href="/affiliate_manager/click_report" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/click_report')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Click Report
    </a>

    <a href="/affiliate_manager/invoices?tab=my_invoices" class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/invoices') && ($_GET['tab']??'')==='my_invoices')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        My Commissions
    </a>
    <a href="/affiliate_manager/invoices" class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/invoices') && ($_GET['tab']??'')==='affiliates')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Invoices
    </a>

    <a href="/affiliate_manager/support" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/support')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Support Inbox
    </a>

    <?php
    // Unread badge for messages-from-admin. Independent of permission system —
    // every manager always has the right to message the admin.
    try { ManagerPermissions::ensureSchema();
        $_mmMgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [(int)Auth::id()]);
        $_mmMgrId  = (int)($_mmMgrRow['id'] ?? 0);
        $_mmUnread = $_mmMgrId > 0 ? (int)(Database::fetchOne("SELECT COUNT(*) c FROM manager_messages WHERE manager_id=? AND sender_role='admin' AND read_by_manager=0", [$_mmMgrId])['c'] ?? 0) : 0;
    } catch (\Throwable $_) { $_mmUnread = 0; } ?>
    <a href="/affiliate_manager/admin-messages" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/admin-messages')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        Admin Messages
        <?php if ($_mmUnread > 0): ?><span class="nav-badge" style="background:#EF4444"><?= $_mmUnread ?></span><?php endif; ?>
    </a>

    <a href="/affiliate_manager/profile" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate_manager/profile')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
    </a>

</aside>

<div class="main-content">
<header class="topbar" style="position: relative;">
    <div style="position: absolute; inset: 0; border-radius: inherit; overflow: hidden; pointer-events: none; z-index: 0;">
        <canvas class="topbar-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
    </div>
    <script>
    (function() {
        var canvas = document.querySelector('.topbar-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w, h;
        function resize() {
            w = canvas.width = canvas.offsetWidth;
            h = canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);
        var nodes = [];
        var density = Math.min(20, Math.floor(w / 60));
        for (var i = 0; i < density; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 2 + 1
            });
        }
        function animate() {
            if (!canvas.offsetParent) {
                requestAnimationFrame(animate);
                return;
            }
            ctx.clearRect(0, 0, w, h);
            ctx.fillStyle = 'rgba(124, 58, 237, 0.4)';
            nodes.forEach(function(n) {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > w) n.vx *= -1;
                if (n.y < 0 || n.y > h) n.vy *= -1;
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
            });
            ctx.lineWidth = 0.8;
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    var dist = Math.hypot(dx, dy);
                    if (dist < 120) {
                        var alpha = (1 - dist / 120) * 0.15;
                        ctx.strokeStyle = 'rgba(124, 58, 237, ' + alpha + ')';
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }
        animate();
    })();
    </script>
    <button id="sidebarToggle" type="button" aria-label="Toggle menu" onclick="window.toggleSidebar&&window.toggleSidebar(event)" style="color:var(--text)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" pointer-events="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span class="topbar-title" style="color:var(--text); font-weight: 700;"><?= Helpers::e($pageTitle ?? 'Dashboard') ?></span>
    <?php
    // Pre-compute balance data for topbar dropdown — reads from manager_commissions + affiliate_managers.balance
    $_mgrBalData = ['balance'=>0.0,'earned'=>0.0,'paid'=>0.0,'pending'=>0.0];
    try {
        $_tbMgr2 = Database::fetchOne("SELECT am.id, am.balance FROM affiliate_managers am WHERE am.user_id=?", [Auth::id()]);
        if ($_tbMgr2) {
            // Available balance is the authoritative column in affiliate_managers
            $_mgrBalData['balance'] = (float)($_tbMgr2['balance'] ?? 0);
            // Pull aggregated commission stats from manager_commissions
            $_tbAgg = Database::fetchOne(
                "SELECT
                    COALESCE(SUM(CASE WHEN status='pending'  THEN commission_amount ELSE 0 END),0) AS pending,
                    COALESCE(SUM(CASE WHEN status='paid'     THEN commission_amount ELSE 0 END),0) AS paid,
                    COALESCE(SUM(CASE WHEN status!='reversed' THEN commission_amount ELSE 0 END),0) AS earned
                 FROM manager_commissions WHERE manager_id=?",
                [$_tbMgr2['id']]
            );
            if ($_tbAgg) {
                $_mgrBalData['earned']  = (float)$_tbAgg['earned'];
                $_mgrBalData['paid']    = (float)$_tbAgg['paid'];
                $_mgrBalData['pending'] = (float)$_tbAgg['pending'];
            }
        }
    } catch (\Throwable $_e) {}
    ?>
    <div style="display:flex;align-items:center;gap:8px;margin-right:8px">
        <!-- Commission Balance Dropdown -->
        <div style="position:relative" id="mgr-bal-wrap">
            <button onclick="toggleMgrBal()" id="mgr-bal-btn"
                title="Commission balance — click for details"
                style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:24px;background:rgba(255,255,255,0.25);border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#059669;font-weight:800;font-size:13px;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(0,0,0,0.05)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span id="mgr-bal-display">$<?= number_format($_mgrBalData['balance'], 2) ?></span>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" id="mgr-bal-caret"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div id="mgr-bal-dropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:250px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid #E2E8F0;z-index:9999;overflow:hidden">
                <div style="background:linear-gradient(135deg,#059669,#047857);padding:14px 16px">
                    <div style="color:rgba(255,255,255,.75);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Commission Balance</div>
                    <div id="mgr-bal-main" style="color:#fff;font-size:22px;font-weight:800;margin-top:2px;letter-spacing:-.5px">$<?= number_format($_mgrBalData['balance'], 2) ?></div>
                </div>
                <div style="padding:14px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                        <span style="font-size:12px;color:#64748B">&#128179; Total Paid Out</span>
                        <span id="mgr-bal-paid" style="font-size:12px;font-weight:700;color:#64748B">$<?= number_format($_mgrBalData['paid'], 2) ?></span>
                    </div>
                    <div style="border-top:1px solid #F1F5F9;padding-top:10px">
                        <a href="/affiliate_manager/invoices?tab=my_invoices" style="display:flex;justify-content:space-between;align-items:center;color:#4F46E5;font-size:12px;font-weight:600;text-decoration:none">
                            View My Invoices <span>&#8594;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fraud Alert bell — same realtime system as the affiliate side.
             Managers see fraud alerts for every affiliate assigned to them. -->
        <div style="position:relative" id="fa-wrap">
            <button onclick="toggleFraudAlerts()" id="fa-btn" title="Fraud Alerts" aria-label="Fraud Alerts"
                    style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);cursor:pointer;transition:.2s" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span id="fa-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;min-width:16px;height:16px;border-radius:10px;padding:0 4px;box-sizing:border-box;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
            </button>
            <div id="fa-dropdown" class="fa-dropdown" style="display:none;background:#fff;box-shadow:0 12px 40px rgba(0,0,0,.22);border:1px solid #E2E8F0;z-index:9999">
                <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#FEF2F2,#FFE4E6);border-radius:10px 10px 0 0">
                    <span style="font-weight:800;font-size:14px;color:#991B1B;display:flex;align-items:center;gap:6px">
                        <span style="font-size:16px">&#9888;&#65039;</span> Fraud Alerts
                    </span>
                    <button onclick="markAllFraudRead()" style="background:none;border:none;cursor:pointer;font-size:11px;color:#991B1B;font-weight:600">Mark all read</button>
                </div>
                <div id="fa-list" style="max-height:420px;overflow-y:auto">
                    <div style="padding:18px;text-align:center;color:var(--text-muted);font-size:12px">Loading…</div>
                </div>
                <div style="padding:8px 12px;border-top:1px solid #F1F5F9;text-align:center">
                    <a href="/affiliate_manager/fraud-report" style="font-size:12px;color:#4F46E5;font-weight:600;text-decoration:none">View full fraud report &rarr;</a>
                </div>
            </div>
        </div>
        <!-- Notification Bell -->
        <div style="position:relative" id="notif-wrap">
            <button onclick="toggleNotifDropdown()" style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);cursor:pointer;transition:.2s" title="Notifications" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span id="notif-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;min-width:16px;height:16px;border-radius:10px;padding:0 4px;box-sizing:border-box;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
            </button>
            <div id="notif-dropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;max-width:calc(100vw - 16px);background:#fff;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid #E2E8F0;z-index:9999">
                <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:700;font-size:14px">Notifications</span>
                    <button onclick="markAllRead()" style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--primary)">Mark all read</button>
                </div>
                <div id="notif-list" style="max-height:320px;overflow-y:auto"></div>
            </div>
        </div>
        <!-- Support Inbox -->
        <a href="/affiliate_manager/support" style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);text-decoration:none;transition:.2s" title="Support Inbox" id="chat-bell" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span id="chat-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;min-width:16px;height:16px;border-radius:10px;padding:0 4px;box-sizing:border-box;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
        </a>
    </div>
    <div class="topbar-actions">
        <?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?>
        <div class="user-menu" style="position:relative; background:rgba(255,255,255,0.25); border:1px solid rgba(255,255,255,0.3); border-radius:24px; padding:4px 10px 4px 4px;">
            <div class="user-avatar" style="width:24px; height:24px; font-size:11px; background:#7C3AED"><?= strtoupper(substr(Auth::currentUser()['first_name']??'M',0,1)) ?></div>
            <span style="color:var(--text); font-weight: 600; font-size: 13px;"><?= Helpers::e(Auth::currentUser()['first_name']??'Manager') ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:var(--text)"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="dropdown-menu">
                <a href="/affiliate_manager/profile" class="dropdown-item">&#9881; My Profile</a>
                <a href="/affiliate_manager/profile?tab=security" class="dropdown-item">&#128274; Change Password</a>
                <div style="border-top:1px solid #F1F5F9;margin:4px 0"></div>
                <a href="/logout" class="dropdown-item">Sign Out</a>
            </div>
        </div>
    </div>
</header>

<!-- Real-time Fraud Alert toasts (rendered outside the topbar so position:fixed isn't clipped). -->
<div id="fa-toast-stack" aria-live="polite" aria-atomic="false"></div>

<style>
    /* Fraud Alert dropdown — anchored under the bell on desktop, full-width
       sheet on mobile so nothing gets clipped by topbar overflow. */
    .fa-dropdown{
        position:absolute;
        right:0;
        top:calc(100% + 8px);
        width:380px;
        max-width:calc(100vw - 24px);
        border-radius:10px;
    }
    @media (max-width:640px){
        .fa-dropdown{
            position:fixed;
            top:60px;
            right:8px;
            left:8px;
            width:auto;
            max-width:none;
        }
        .fa-dropdown #fa-list{ max-height:calc(100vh - 200px) !important; }
    }
    @keyframes fa-pulse-red{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.7)}50%{box-shadow:0 0 0 8px rgba(239,68,68,0)}}
    @keyframes fa-shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-1px)}75%{transform:translateX(1px)}}
    #fa-badge.high-risk{background:#DC2626;animation:fa-pulse-red 1.4s infinite}
    #fa-btn.high-risk{animation:fa-shake .6s ease-in-out infinite}
    @keyframes fa-flash{0%,100%{background:transparent}50%{background:rgba(239,68,68,.18)}}
    #fa-btn.fa-just-arrived{animation:fa-flash .9s ease-in-out 3}
    /* Per-alert card inside the dropdown */
    .fa-card{display:block;padding:10px 14px;border-bottom:1px solid #F1F5F9;text-decoration:none;color:#1E293B;transition:background .15s}
    .fa-card:hover{background:#F8FAFC}
    .fa-card.high{border-left:3px solid #DC2626}
    .fa-card.medium{border-left:3px solid #F59E0B}
    .fa-card.resolved{opacity:.55}
    .fa-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 9px;font-size:9px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
    .fa-pill.high{background:#FEE2E2;color:#991B1B}
    .fa-pill.medium{background:#FEF3C7;color:#92400E}
    .fa-pill.resolved{background:#D1FAE5;color:#065F46}
    .fa-dot-pulse{width:6px;height:6px;border-radius:50%;background:currentColor;animation:fa-pulse-red 1.4s infinite}
    /* Real-time toast stack — bottom-right, slides in, auto-dismisses. */
    #fa-toast-stack{position:fixed;right:18px;bottom:90px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;max-width:360px}
    @media (max-width:640px){ #fa-toast-stack{right:8px;left:8px;bottom:80px;max-width:none} }
    @keyframes fa-toast-in{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
    @keyframes fa-toast-out{from{transform:translateX(0);opacity:1}to{transform:translateX(110%);opacity:0}}
    .fa-toast{pointer-events:auto;background:#fff;border-left:4px solid #DC2626;box-shadow:0 12px 32px rgba(0,0,0,.18);
              border-radius:10px;padding:12px 14px;font-size:12.5px;color:#0F172A;
              animation:fa-toast-in .35s ease-out;display:flex;gap:10px;align-items:flex-start}
    .fa-toast.medium{border-left-color:#F59E0B}
    .fa-toast .fa-toast-icon{font-size:18px;line-height:1;flex-shrink:0}
    .fa-toast .fa-toast-body{flex:1;min-width:0}
    .fa-toast .fa-toast-title{font-weight:800;font-size:13px;color:#991B1B;margin-bottom:3px;display:flex;align-items:center;gap:6px}
    .fa-toast.medium .fa-toast-title{color:#92400E}
    .fa-toast .fa-toast-meta{font-size:11px;color:#64748B;margin-top:4px;display:flex;justify-content:space-between;gap:8px}
    .fa-toast .fa-toast-close{background:none;border:none;cursor:pointer;color:#94A3B8;font-size:16px;line-height:1;padding:0 2px}
    .fa-toast a{color:inherit;text-decoration:none;display:block}
    .fa-toast.closing{animation:fa-toast-out .35s ease-in forwards}
</style>

<script>
// ── Fraud Alerts (manager scope: sees alerts for every assigned affiliate) ──
var _faCsrf = '<?= Auth::generateCsrf() ?>';
function _faEscape(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
// Managers should never be sent to an affiliate's report page. The notification
// row is shared, so we rewrite the link client-side for this role.
function _faLink(url){
    if (!url) return '/affiliate_manager/fraud-report';
    return String(url).replace('/affiliate/fraud-report', '/affiliate_manager/fraud-report');
}
function _faTimeAgo(s){
    try {
        var d = new Date(String(s||'').replace(' ','T'));
        var diff = Math.max(0, (Date.now() - d.getTime())/1000);
        if (diff < 60)    return Math.floor(diff)+'s ago';
        if (diff < 3600)  return Math.floor(diff/60)+'m ago';
        if (diff < 86400) return Math.floor(diff/3600)+'h ago';
        return Math.floor(diff/86400)+'d ago';
    } catch(e) { return ''; }
}
function loadFraudAlerts(){
    fetch('/api/fraud-alerts?action=list&limit=20')
    .then(function(r){return r.json();})
    .then(function(d){
        var badge = document.getElementById('fa-badge');
        var btn   = document.getElementById('fa-btn');
        var list  = document.getElementById('fa-list');
        var alerts = d.alerts || [];
        var unread = d.unread || 0;
        var hasHigh = alerts.some(function(a){ return !a.is_read && !a.resolved_at && a.risk_level === 'high'; });

        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.style.display = 'flex';
            badge.classList.toggle('high-risk', hasHigh);
            btn.classList.toggle('high-risk', hasHigh);
        } else {
            badge.style.display = 'none';
            badge.classList.remove('high-risk');
            btn.classList.remove('high-risk');
        }

        if (!alerts.length) {
            list.innerHTML = '<div style="padding:22px;text-align:center;color:var(--text-muted);font-size:13px">&#9989; No fraud alerts</div>';
            return;
        }
        list.innerHTML = alerts.map(function(a){
            var resolved = !!a.resolved_at;
            var risk = a.risk_level === 'high' ? 'high' : 'medium';
            var pill = resolved
                ? '<span class="fa-pill resolved">&#10003; Resolved</span>'
                : ('<span class="fa-pill '+risk+'"><span class="fa-dot-pulse"></span> '+ (risk === 'high' ? 'High Risk' : 'Medium Risk') +'</span>');
            var msg = _faEscape(a.message || '').replace(/\n/g,'<br>');
            return '<a class="fa-card '+risk+(resolved?' resolved':'')+'" href="'+_faEscape(_faLink(a.link))+'" onclick="markOneFraudRead('+a.id+')">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px">' +
                    '<strong style="font-size:13px;color:#0F172A;display:flex;align-items:center;gap:6px">&#9888;&#65039; '+_faEscape(a.title||'Fraud Alert')+'</strong>' +
                    pill +
                '</div>' +
                '<div style="font-size:11px;color:#475569;line-height:1.5">'+msg+'</div>' +
                '<div style="display:flex;justify-content:space-between;font-size:10px;color:#94A3B8;margin-top:6px">' +
                    '<span>'+(a.offer_name ? _faEscape(a.offer_name) : '')+'</span>' +
                    '<span>'+_faTimeAgo(a.detected_at)+'</span>' +
                '</div>' +
                '</a>';
        }).join('');
    }).catch(function(){});
}
window.toggleFraudAlerts = function(){
    var d = document.getElementById('fa-dropdown');
    d.style.display = d.style.display === 'block' ? 'none' : 'block';
    if (d.style.display === 'block') loadFraudAlerts();
};
window.markAllFraudRead = function(){
    var fd = new FormData();
    fd.append('action','mark_read');
    fd.append('all','1');
    fd.append('_token', _faCsrf);
    fetch('/api/fraud-alerts?action=mark_read', { method:'POST', body: fd })
    .then(function(){ loadFraudAlerts(); });
};
window.markOneFraudRead = function(id){
    var fd = new FormData();
    fd.append('action','mark_read');
    fd.append('id', id);
    fd.append('_token', _faCsrf);
    fetch('/api/fraud-alerts?action=mark_read', { method:'POST', body: fd });
};
// Close dropdown when clicking outside.
document.addEventListener('click', function(e){
    var wrap = document.getElementById('fa-wrap');
    var drop = document.getElementById('fa-dropdown');
    if (wrap && drop && drop.style.display === 'block' && !wrap.contains(e.target)) {
        drop.style.display = 'none';
    }
});
loadFraudAlerts();
setInterval(loadFraudAlerts, 60000);

// ── Real-time delivery (no manual refresh required) ───────────────────────
var FA_LS_KEY = 'fa_last_seen_id_mgr';
var faLastSeen = parseInt(localStorage.getItem(FA_LS_KEY) || '0', 10) || 0;
var faAudioCtx = null;
function faPlayChime(highRisk){
    try {
        if (!window.AudioContext && !window.webkitAudioContext) return;
        if (!faAudioCtx) faAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var ctx = faAudioCtx;
        var t   = ctx.currentTime;
        [highRisk ? 880 : 660, highRisk ? 1175 : 880].forEach(function(freq, i){
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.0001, t + i*0.18);
            gain.gain.exponentialRampToValueAtTime(highRisk ? 0.25 : 0.16, t + i*0.18 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + i*0.18 + 0.22);
            osc.connect(gain).connect(ctx.destination);
            osc.start(t + i*0.18);
            osc.stop(t + i*0.18 + 0.24);
        });
    } catch(e) {}
}
function faShowToast(a){
    var stack = document.getElementById('fa-toast-stack');
    if (!stack) return;
    var risk = a.risk_level === 'high' ? 'high' : 'medium';
    var riskTxt = risk === 'high' ? 'High Risk' : 'Medium Risk';
    var el = document.createElement('div');
    el.className = 'fa-toast ' + risk;
    el.innerHTML =
        '<div class="fa-toast-icon">&#9888;&#65039;</div>' +
        '<div class="fa-toast-body">' +
            '<div class="fa-toast-title">' +
                'Fraud Alert &middot; ' + _faEscape(riskTxt) +
            '</div>' +
            '<div style="font-size:12px;color:#334155;line-height:1.5">' +
                'Conversion <span style="font-family:monospace">#' + _faEscape(a.short_id || (a.conversion_id||'').substring(0,8)) + '</span>' +
                ' detected as fraudulent on an affiliate you manage.' +
            '</div>' +
            (a.offer_name ? '<div style="font-size:11px;color:#475569;margin-top:3px">Offer: <strong>' + _faEscape(a.offer_name) + '</strong></div>' : '') +
            '<div class="fa-toast-meta">' +
                '<a href="' + _faEscape(_faLink(a.link)) + '" style="color:#4F46E5;font-weight:600">View &rarr;</a>' +
                '<span>' + _faTimeAgo(a.detected_at || a.created_at) + '</span>' +
            '</div>' +
        '</div>' +
        '<button class="fa-toast-close" aria-label="Close" type="button">&times;</button>';
    var dismiss = function(){
        if (el.classList.contains('closing')) return;
        el.classList.add('closing');
        setTimeout(function(){ if (el.parentNode) el.parentNode.removeChild(el); }, 380);
    };
    el.querySelector('.fa-toast-close').addEventListener('click', dismiss);
    stack.appendChild(el);
    setTimeout(dismiss, risk === 'high' ? 10000 : 8000);
}
function faOnNewAlerts(arr, unread){
    var badge = document.getElementById('fa-badge');
    var btn   = document.getElementById('fa-btn');
    if (badge && btn) {
        if (unread > 0) {
            badge.textContent = unread > 9 ? '9+' : unread;
            badge.style.display = 'flex';
            var hasHigh = arr.some(function(a){ return a.risk_level === 'high'; });
            badge.classList.toggle('high-risk', hasHigh);
            btn.classList.toggle('high-risk', hasHigh);
        }
        btn.classList.remove('fa-just-arrived');
        void btn.offsetWidth;
        btn.classList.add('fa-just-arrived');
        setTimeout(function(){ btn.classList.remove('fa-just-arrived'); }, 2800);
    }
    var anyHigh = false;
    arr.forEach(function(a){
        if (a.risk_level === 'high') anyHigh = true;
        faShowToast(a);
    });
    faPlayChime(anyHigh);
    var d = document.getElementById('fa-dropdown');
    if (d && d.style.display === 'block') loadFraudAlerts();
}
function faPollSince(){
    if (document.hidden) return;
    fetch('/api/fraud-alerts?action=since&since_id=' + encodeURIComponent(faLastSeen), { cache:'no-store' })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (!d || typeof d.max_id === 'undefined') return;
        var newOnes = Array.isArray(d.new) ? d.new : [];
        if (d.max_id > faLastSeen) {
            faLastSeen = d.max_id;
            try { localStorage.setItem(FA_LS_KEY, String(faLastSeen)); } catch(e) {}
        }
        if (newOnes.length) faOnNewAlerts(newOnes, d.unread || newOnes.length);
        else {
            var badge = document.getElementById('fa-badge');
            var btn   = document.getElementById('fa-btn');
            if (badge && btn) {
                if ((d.unread || 0) > 0) {
                    badge.textContent = d.unread > 9 ? '9+' : d.unread;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                    badge.classList.remove('high-risk');
                    btn.classList.remove('high-risk');
                }
            }
        }
    }).catch(function(){});
}
(function seedFaLastSeen(){
    fetch('/api/fraud-alerts?action=list&limit=1', { cache:'no-store' })
    .then(function(r){ return r.json(); })
    .then(function(d){
        var maxId = 0;
        if (d && Array.isArray(d.alerts)) {
            d.alerts.forEach(function(a){ if (a.id > maxId) maxId = a.id; });
        }
        if (maxId > faLastSeen) {
            faLastSeen = maxId;
            try { localStorage.setItem(FA_LS_KEY, String(maxId)); } catch(e) {}
        }
        setInterval(faPollSince, 8000);
        setTimeout(faPollSince, 1200);
    }).catch(function(){
        setInterval(faPollSince, 8000);
    });
})();
document.addEventListener('visibilitychange', function(){
    if (!document.hidden) faPollSince();
});
</script>

<script>
// ── Balance dropdown ───────────────────────────────────────────────────────
function toggleMgrBal() {
    var dd = document.getElementById('mgr-bal-dropdown');
    var open = dd.style.display !== 'none';
    dd.style.display = open ? 'none' : 'block';
    var caret = document.getElementById('mgr-bal-caret');
    if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
}
function _mgrFmt(n){ return '$'+parseFloat(n||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }
function refreshMgrBalance() {
    fetch('/api/stats')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.error || d.commission_balance === undefined) return;
            var balBtn = document.getElementById('mgr-bal-display');
            if (balBtn) {
                var fmtd = _mgrFmt(d.commission_balance);
                if (balBtn.dataset.prev && balBtn.dataset.prev !== fmtd) {
                    balBtn.style.transition = 'color .4s';
                    balBtn.style.color = '#A7F3D0';
                    setTimeout(function(){ balBtn.style.color = ''; }, 1400);
                }
                balBtn.textContent = fmtd;
                balBtn.dataset.prev = fmtd;
            }
            var mainEl = document.getElementById('mgr-bal-main');
            if (mainEl) mainEl.textContent = _mgrFmt(d.commission_balance);
            var paidEl = document.getElementById('mgr-bal-paid');
            if (paidEl) paidEl.textContent = _mgrFmt(d.commission_paid);
        }).catch(function(){});
}
setInterval(refreshMgrBalance, 30000);
document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'visible') refreshMgrBalance();
});

// ── Click-outside: close balance + notification dropdowns ──────────────────
document.addEventListener('click', function(e) {
    var bw = document.getElementById('mgr-bal-wrap');
    if (bw && !bw.contains(e.target)) {
        var dd = document.getElementById('mgr-bal-dropdown');
        if (dd) { dd.style.display = 'none'; var c = document.getElementById('mgr-bal-caret'); if(c) c.style.transform=''; }
    }
    // User-menu dropdown toggle (logout, profile etc.)
    var um = e.target.closest('.user-menu');
    if (um) {
        var dm = um.querySelector('.dropdown-menu');
        if (dm) { dm.classList.toggle('show'); e.stopPropagation(); }
    } else {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(d){ d.classList.remove('show'); });
    }
});

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
        if (!bd || !document.body.contains(bd)){
            document.querySelectorAll('.sidebar-overlay').forEach(function(el){ el.remove(); });
            bd = document.createElement('div');
            bd.id = 'sidebarBackdrop';
            bd.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);'
                             + 'z-index:199;display:none;touch-action:none;'
                             + '-webkit-tap-highlight-color:transparent;';
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
        sb.style.willChange = 'transform';
        sb.classList.add('open');
        ensureBackdrop().classList.add('visible');
        document.body.style.overflow = 'hidden';
        setTimeout(function(){ if (sb) sb.style.willChange = ''; }, 320);
    }

    function sbClose(){
        var sb = getSidebar();
        if (sb){ sb.classList.remove('open'); sb.style.willChange = ''; }
        if (bd){ bd.classList.remove('visible'); bd.style.display = 'none'; }
        document.body.style.overflow = '';
    }

    window.toggleSidebar = function(e){
        if (e && e.stopPropagation) e.stopPropagation();
        if (e && e.preventDefault) e.preventDefault();
        if (_toggling) return;
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
    if (document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', bind);
    }

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

    window.addEventListener('resize', function(){
        var sb = getSidebar();
        if (window.innerWidth > 768 && sb && sb.classList.contains('open')) sbClose();
    }, {passive:true});
})();

</script>

<main class="page-body">
<?php foreach(Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type']==='error'?'error':($f['type']==='success'?'success':'info') ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>
<script>
function toggleNotifDropdown(){
    var d=document.getElementById('notif-dropdown');
    var opening=d.style.display==='none';
    d.style.display=opening?'block':'none';
    if(opening){
        loadNotifications();
        if(window.innerWidth<=640){
            var w=Math.min(320,window.innerWidth-16);
            d.style.position='fixed';
            d.style.right='8px';
            d.style.left='auto';
            d.style.top='60px';
            d.style.width=w+'px';
        } else {
            d.style.position='absolute';
            d.style.right='0';
            d.style.left='auto';
            d.style.top='calc(100% + 8px)';
            d.style.width='320px';
        }
    }
}
document.addEventListener('click',function(e){
    var w=document.getElementById('notif-wrap');
    if(w&&!w.contains(e.target)){
        var d=document.getElementById('notif-dropdown');
        if(d) d.style.display='none';
    }
});
function loadNotifications(){
    fetch('/api/notifications?action=list')
    .then(function(r){return r.json();})
    .then(function(data){
        var badge=document.getElementById('notif-badge');
        if((data.unread||0)>0){badge.textContent=data.unread;badge.style.display='flex';}
        else badge.style.display='none';
        var list=document.getElementById('notif-list');
        if(!data.notifications||!data.notifications.length){list.innerHTML='<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">No notifications</div>';return;}
        list.innerHTML='';
        data.notifications.forEach(function(n){
            var el=document.createElement('a');
            el.href=n.link||'#';
            el.style.cssText='display:block;padding:12px 16px;border-bottom:1px solid #F8FAFC;text-decoration:none;color:inherit;background:'+(n.is_read?'#fff':'#EFF6FF');
            el.innerHTML='<div style="font-weight:'+(n.is_read?'400':'600')+';font-size:13px">'+escN(n.title)+'</div>'+
                '<div style="font-size:12px;color:var(--text-muted);margin-top:2px">'+escN(n.message)+'</div>';
            el.onclick=function(){markOneRead(n.id);};
            list.appendChild(el);
        });
        fetch('/api/chat?action=unread_count')
        .then(function(r){return r.json();}).then(function(d){
            var cb=document.getElementById('chat-badge');
            if((d.count||0)>0){cb.textContent=d.count;cb.style.display='flex';}
            else cb.style.display='none';
        });
    });
}
function escN(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function markAllRead(){
    fetch('/api/notifications?action=mark_read',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read'})
    .then(function(){loadNotifications();});
}
function markOneRead(id){
    fetch('/api/notifications?action=mark_read',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read&id='+id});
}
loadNotifications();
setInterval(loadNotifications, 60000);

(function heartbeat(){
    fetch('/api/activity', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=heartbeat&page='+encodeURIComponent(window.location.pathname)
    }).then(function(r){return r.json();}).then(function(d){if(d&&d.error==='session_killed')window.location.href='/login';}).catch(function(){});
    setTimeout(heartbeat, 30000);
})();

// ── Preserve Sidebar Scroll Position ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var sb = document.querySelector('.sidebar');
    if (!sb) return;
    var savedPos = sessionStorage.getItem('aff_mgr_sidebar_scroll');
    if (savedPos) sb.scrollTop = parseInt(savedPos, 10);
    
    var saveScroll = function() { sessionStorage.setItem('aff_mgr_sidebar_scroll', sb.scrollTop); };
    sb.addEventListener('scroll', saveScroll, {passive: true});
    window.addEventListener('beforeunload', saveScroll);
});
</script>
