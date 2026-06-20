<?php
// Fetch assigned affiliate manager for sidebar card
$_layoutMgr = null;
try {
    $_affId = Auth::affiliateId();
    if ($_affId) {
        $_layoutMgr = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email, u.phone, u.profile_pic,
                    am.skype, am.telegram, am.discord
             FROM affiliates af
             JOIN affiliate_managers am ON am.id = af.manager_id
             JOIN users u ON u.id = am.user_id
             WHERE af.id = ? LIMIT 1",
            [$_affId]
        ) ?: null;
    }
} catch(\Exception $_e) {}

// Fetch affiliate balance + earnings breakdown for topbar
$_affBalance  = 0.00;
$_affApproved = 0.00;
$_affPending  = 0.00;
$_affMonthly  = 0.00;
try {
    if ($_affId) {
        $_balRow      = Database::fetchOne("SELECT balance FROM affiliates WHERE id=?", [$_affId]);
        $_affBalance  = (float)($_balRow['balance'] ?? 0);
        $_msStart     = date('Y-m-01');
        $_affApproved = (float)(Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='approved'", [$_affId])['t'] ?? 0);
        $_affPending  = (float)(Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='pending'",  [$_affId])['t'] ?? 0);
        $_affMonthly  = (float)(Database::fetchOne("SELECT COALESCE(SUM(payout),0) as t FROM conversions WHERE affiliate_id=? AND status='approved' AND converted_at>=?", [$_affId, $_msStart])['t'] ?? 0);
    }
} catch(\Exception $_e) {}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>">
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
<?php
    $returnUrl   = $_SESSION['impersonate_return_url'] ?? '/admin/stop-impersonate';
    $stopUrl     = (strpos($returnUrl, 'affiliate_manager') !== false)
                    ? '/affiliate_manager/stop-impersonate'
                    : '/admin/stop-impersonate';
    $actorLabel  = (strpos($returnUrl, 'affiliate_manager') !== false) ? 'Manager' : 'Admin';
?>
<div style="background:#F59E0B;color:#1F2937;padding:9px 20px;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9999;box-shadow:0 1px 4px rgba(0,0,0,.15)">
    <span>&#128064; <?= $actorLabel ?> mode &mdash; viewing as <strong><?= Helpers::e($_SESSION['user_name']) ?></strong> (Affiliate)</span>
    <a href="<?= $stopUrl ?>" style="background:#1F2937;color:#fff;padding:4px 14px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:600;">&#8617; Return to <?= $actorLabel ?></a>
</div>
<?php endif; ?>
<div class="app-layout">
<aside class="sidebar">
    <div class="sidebar-top">
        <a class="sidebar-logo" href="/affiliate/dashboard">
            <?php if ($siteLogo = Config::get('config','app.logo')): ?>
            <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px;max-width:140px;object-fit:contain">
            <?php else: ?>
            <div class="logo-icon" style="background:#10B981">&#127760;</div>
            <span><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
            <?php endif; ?>
        </a>
        <button class="mobile-close-btn" onclick="window.toggleSidebar&&window.toggleSidebar(event)" aria-label="Close menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </button>
    </div>


    <p class="sidebar-section">Menu</p>
    <a href="/affiliate/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/dashboard')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="/affiliate/news" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/news')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
        News
        <?php try { $newsUnread = Database::fetchOne("SELECT COUNT(*) as c FROM news n WHERE n.status='published' AND NOT EXISTS (SELECT 1 FROM news_reads nr WHERE nr.news_id=n.id AND nr.user_id=?)",[ Auth::id()])['c']??0; if($newsUnread>0): ?><span class="nav-badge" style="background:#4F46E5"><?= $newsUnread ?></span><?php endif; } catch(\Throwable $e){} ?>
    </a>
    <a href="/affiliate/referral" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/referral')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/></svg>
        Referral Program
    </a>
    <a href="/affiliate/offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/offers')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>Offers</a>
    <a href="/affiliate/inhouse-offers" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/inhouse-offers')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        In-House Offers</a>
    <a href="/affiliate/smartlinks" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/smartlinks')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Smartlinks</a>
    <a href="/affiliate/postbacks" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/postbacks')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>Postbacks</a>
    <a href="/affiliate/reports" class="nav-link <?= ((str_contains($_SERVER['REQUEST_URI'],'/affiliate/reports')) && !str_contains($_SERVER['REQUEST_URI'],'/affiliate/fraud-report'))?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Reports</a>
    <a href="/affiliate/duplicate-conversions" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/duplicate-conversions')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Duplicate Conversions</a>
    <a href="/affiliate/fraud-report" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/fraud-report') && !str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/fraud-reports') ?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Fraud Report</a>
    <a href="/affiliate/fraud-reports" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/fraud-reports') ?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
        Fraud Reports
    </a>
    <a href="/affiliate/analytics" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/analytics')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Analytics</a>
    <a href="/affiliate/support" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/support')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Live Support</a>
    <a href="/affiliate/invoices" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/invoices')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>Invoices</a>

    <a href="/affiliate/shop" class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'],'/affiliate/shop')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        Shop
    </a>
    <a href="/affiliate/rewards" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/rewards')?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
        Rewards
    </a>

    <a href="/affiliate/profile" class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'],'/affiliate/profile') || str_contains($_SERVER['REQUEST_URI'],'/affiliate/delete-account'))?'active':'' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>Settings</a>
    <a href="/affiliate/delete-account" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/delete-account')?'active':'' ?>" style="color:#EF4444">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>Delete Account</a>

    <?php if ($_layoutMgr): ?>
    <p class="sidebar-section">Manager</p>
    <div style="padding:10px 10px 14px">
        <a href="/affiliate/manager" style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:linear-gradient(145deg,#0F172A,#1E1B4B,#1E293B);border-radius:12px;text-decoration:none;transition:opacity .18s,transform .15s,box-shadow .18s;box-shadow:0 4px 16px rgba(15,23,42,.35);<?= str_contains($_SERVER['REQUEST_URI'],'/affiliate/manager') ? 'box-shadow:0 4px 20px rgba(79,70,229,.45);opacity:.95;' : '' ?>" onmouseover="this.style.opacity='.88';this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 22px rgba(15,23,42,.45)'" onmouseout="this.style.opacity='1';this.style.transform='translateY(0)';this.style.boxShadow='0 4px 16px rgba(15,23,42,.35)'">
            <div style="width:32px;height:32px;border-radius:9px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.9)" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </div>
            <div style="min-width:0;flex:1">
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.9);letter-spacing:.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Affiliate Manager</div>
                <div style="font-size:10px;color:rgba(255,255,255,.4);margin-top:1px">View details</div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.35)" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    </div>
    <?php endif; ?>

</aside>

<?php /* Reward pop-up overlay — self-renders nothing if disabled or already dismissed.
       Sits between the sidebar and the main content so it overlays the page on first paint. */ ?>
<?php require BASE_PATH . '/views/partials/affiliate_popup.php'; ?>

<div class="main-content">
<header class="topbar" style="background: linear-gradient(90deg, #a1c4fd 0%, #eac2da 100%); border-bottom: none;">
    <button id="sidebarToggle" type="button" aria-label="Toggle menu" onclick="window.toggleSidebar&&window.toggleSidebar(event)" style="color:var(--text)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" pointer-events="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span class="topbar-title" style="color:var(--text); font-weight: 700;"><?= Helpers::e($pageTitle ?? 'Dashboard') ?></span>
    <div style="display:flex;align-items:center;gap:8px;margin-right:8px">
        <!-- Affiliate Balance Dropdown -->
        <div style="position:relative" id="aff-bal-wrap">
            <button onclick="toggleAffBal()" id="aff-bal-btn"
                title="Your earnings — click for details"
                style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:24px;background:rgba(255,255,255,0.25);border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#059669;font-weight:800;font-size:13px;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(0,0,0,0.05)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span id="aff-balance-display">$<?= number_format($_affBalance, 2) ?></span>
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" id="aff-bal-caret"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div id="aff-bal-dropdown" class="aff-bal-dropdown" style="display:none;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid #E2E8F0;z-index:9999;overflow:hidden">
                <div style="background:linear-gradient(135deg,#059669,#047857);padding:14px 16px">
                    <div style="color:rgba(255,255,255,.75);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Available Balance</div>
                    <div id="aff-bal-main" style="color:#fff;font-size:22px;font-weight:800;margin-top:2px;letter-spacing:-.5px">$<?= number_format($_affBalance, 2) ?></div>
                </div>
                <div style="padding:14px 16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                        <span style="font-size:12px;color:#64748B">&#9203; Pending</span>
                        <span id="aff-bal-pending" style="font-size:12px;font-weight:700;color:#D97706">$<?= number_format($_affPending, 2) ?></span>
                    </div>
                    <div style="border-top:1px solid #F1F5F9;padding-top:10px">
                        <a href="/affiliate/invoices" style="display:flex;justify-content:space-between;align-items:center;color:#4F46E5;font-size:12px;font-weight:600;text-decoration:none">
                            View Invoices <span>&#8594;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- News Bell -->
        <a href="/affiliate/news" id="news-bell-wrap" style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);text-decoration:none;transition:.2s" title="News" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8z"/></svg>
            <span id="news-badge" style="display:none;position:absolute;top:2px;right:2px;background:#4F46E5;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
        </a>
        <!-- Notification Bell -->
        <div style="position:relative" id="notif-wrap">
            <button onclick="toggleNotifDropdown()" style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);cursor:pointer;transition:.2s" title="Notifications" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span id="notif-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
            </button>
            <div id="notif-dropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;max-width:calc(100vw - 16px);background:#fff;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid #E2E8F0;z-index:9999">
                <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:700;font-size:14px">Notifications</span>
                    <button onclick="markAllRead()" style="background:none;border:none;cursor:pointer;font-size:12px;color:var(--primary)">Mark all read</button>
                </div>
                <div id="notif-list" style="max-height:320px;overflow-y:auto"></div>
            </div>
        </div>
        <!-- Fraud Alert bell — independent of normal notifications. Pulses red
             when there's at least one HIGH-risk (score ≥ 80) unread alert. -->
        <div style="position:relative" id="fa-wrap">
            <button onclick="toggleFraudAlerts()" id="fa-btn" title="Fraud Alerts" aria-label="Fraud Alerts"
                    style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);cursor:pointer;transition:.2s" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span id="fa-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
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
                    <a href="/affiliate/fraud-report" style="font-size:12px;color:#4F46E5;font-weight:600;text-decoration:none">View full fraud report &rarr;</a>
                </div>
            </div>
        </div>
        <!-- Real-time Fraud Alert toasts (appended outside the topbar so they
             can use fixed positioning without being clipped). -->
        <div id="fa-toast-stack" aria-live="polite" aria-atomic="false"></div>
        <!-- Live Support Chat -->
        <a href="/affiliate/support" style="position:relative;display:flex;align-items:center;padding:6px;border-radius:12px;color:var(--text);border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.25);text-decoration:none;transition:.2s" title="Live Support" id="chat-bell" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span id="chat-badge" style="display:none;position:absolute;top:2px;right:2px;background:#EF4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1">0</span>
        </a>
    </div>

    <!-- Fraud Alert CSS — High-risk red pulse animation + Medium-risk yellow. -->
    <style>
        /* Desktop: anchored under the bell, fixed-width.
           Mobile (<=640px): pinned to viewport edges as a full-width sheet so
           nothing ever gets clipped by topbar overflow. */
        /* Affiliate Balance dropdown — same anchored/full-width pattern as the
           fraud dropdown so it never gets clipped by topbar overflow. */
        .aff-bal-dropdown{
            position:absolute;
            right:0;
            top:calc(100% + 8px);
            width:260px;
            max-width:calc(100vw - 24px);
        }
        @media (max-width:640px){
            .aff-bal-dropdown{
                position:fixed;
                top:60px;
                right:8px;
                left:8px;
                width:auto;
                max-width:none;
            }
        }
        .fa-dropdown{
            position:absolute;
            right:0;
            top:calc(100% + 8px);
            width:380px;
            max-width:calc(100vw - 24px);
            border-radius:10px;
        }
        /* On phones the topbar can clip an absolute-positioned dropdown, so
           switch to fixed positioning anchored to the viewport edges. Almost
           full-width with small gutters — nothing gets cut off. */
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
        /* New-arrival flash on the bell when a fresh alert lands. */
        @keyframes fa-flash{0%,100%{background:transparent}50%{background:rgba(239,68,68,.18)}}
        #fa-btn.fa-just-arrived{animation:fa-flash .9s ease-in-out 3}
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
        .fa-card{padding:12px 16px;border-bottom:1px solid #F1F5F9;display:block;text-decoration:none;color:inherit;position:relative}
        .fa-card:hover{background:#FAFBFC}
        .fa-card.high{border-left:3px solid #DC2626;background:linear-gradient(90deg,rgba(220,38,38,.06),#fff)}
        .fa-card.medium{border-left:3px solid #F59E0B;background:linear-gradient(90deg,rgba(245,158,11,.06),#fff)}
        .fa-card.resolved{opacity:.55}
        .fa-pill{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:1px 8px;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
        .fa-pill.high{background:#FEE2E2;color:#991B1B}
        .fa-pill.medium{background:#FEF3C7;color:#92400E}
        .fa-pill.resolved{background:#E2E8F0;color:#475569}
        .fa-dot-pulse{width:7px;height:7px;border-radius:50%;background:#DC2626;animation:fa-pulse-red 1.4s infinite;display:inline-block}
        .fa-pill.medium .fa-dot-pulse{background:#F59E0B;animation:none}
    </style>
    <div class="topbar-actions">
        <?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?>
        <div class="user-menu" style="position:relative; background:rgba(255,255,255,0.25); border:1px solid rgba(255,255,255,0.3); border-radius:24px; padding:4px 10px 4px 4px;">
            <div class="user-avatar" style="width:24px; height:24px; font-size:11px; background:#10B981"><?= strtoupper(substr(Auth::currentUser()['first_name']??'A',0,1)) ?></div>
            <span style="color:var(--text); font-weight: 600; font-size: 13px;"><?= Helpers::e(Auth::currentUser()['first_name']??'Affiliate') ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:var(--text)"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="dropdown-menu">
                <a href="/affiliate/profile" class="dropdown-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    My Profile
                </a>
                <div class="dropdown-divider"></div>
                <?php if (Auth::isImpersonating()): ?>
                    <?php 
                    $returnUrl   = $_SESSION['impersonate_return_url'] ?? '/admin/stop-impersonate';
                    $stopUrl     = (strpos($returnUrl, 'affiliate_manager') !== false) ? '/affiliate_manager/stop-impersonate' : '/admin/stop-impersonate';
                    $actorLabel  = (strpos($returnUrl, 'affiliate_manager') !== false) ? 'Manager' : 'Admin';
                    ?>
                    <a href="<?= $stopUrl ?>" class="dropdown-item" style="color:#F59E0B">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 10 4 15 9 20"/><path d="M20 4v7a4 4 0 0 1-4 4H4"/></svg>
                        Return to <?= $actorLabel ?>
                    </a>
                <?php else: ?>
                    <a href="/logout" class="dropdown-item" style="color:#EF4444">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<?php
// ── Mobile App install popup ─────────────────────────────────────────────
// Renders ONCE per logged-in session. Admin can change the URL/name/enabled
// flag any time at /admin/settings?tab=mobile_app — changes take effect on
// the next page load (no server restart needed).
//
// IMPORTANT: NEVER show this popup to users who are already inside the
// official Android app. We detect "app mode" two ways:
//   1) URL parameter ?source=app|android (the app passes this on first load)
//   2) User-Agent contains "AffsCashApp" (the app's custom UA marker)
// Either signal flips `$_SESSION['is_native_app']` for the rest of the session
// so the popup, footer install promo, and any future install prompts stay off.
if (!isset($_SESSION['is_native_app'])) $_SESSION['is_native_app'] = false;
$_uaSrc = strtolower((string)($_GET['source'] ?? ''));
$_ua    = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
if ($_uaSrc === 'app' || $_uaSrc === 'android' || stripos($_ua, 'AffsCashApp') !== false) {
    $_SESSION['is_native_app'] = true;
}
$_isNativeApp = !empty($_SESSION['is_native_app']);

$_maUrl     = trim((string)(Config::get('config','app.mobile_app_url') ?: ''));
$_maName    = (string)(Config::get('config','app.mobile_app_name') ?: 'AffsCash');
$_maEnabled = (string)(Config::get('config','app.mobile_app_popup_enabled') ?: '0') === '1';
// Native-app sessions NEVER see the install popup — clean login experience.
$_maShow    = $_maEnabled && $_maUrl !== '' && !$_isNativeApp && empty($_SESSION['mobile_app_popup_shown']);
if ($_maShow) { $_SESSION['mobile_app_popup_shown'] = 1; }
?>
<?php if ($_maShow): ?>
<div id="mobile-app-popup" role="dialog" aria-labelledby="map-title" aria-describedby="map-desc"
     style="position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:100000;display:flex;align-items:center;justify-content:center;padding:18px;animation:map-fade .25s ease-out">
    <div style="background:#fff;border-radius:14px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;animation:map-pop .3s cubic-bezier(.34,1.56,.64,1)">
        <div style="padding:22px 24px 4px;display:flex;align-items:center;gap:12px">
            <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="2" width="14" height="20" rx="2.5"/><line x1="11" y1="18" x2="13" y2="18"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0">
                <div id="map-title" style="font-size:17px;font-weight:800;color:#0F172A;line-height:1.3">
                    Install our app <?= Helpers::e($_maName) ?>
                </div>
                <div id="map-desc" style="font-size:13px;color:#64748B;margin-top:2px">for a better experience</div>
            </div>
            <button onclick="document.getElementById('mobile-app-popup').remove()" aria-label="Close"
                    style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:22px;line-height:1;padding:4px 8px;border-radius:6px">&times;</button>
        </div>
        <div style="padding:14px 24px 22px;display:flex;flex-direction:column;gap:10px">
            <p style="margin:0;font-size:13.5px;color:#475569;line-height:1.55">
                Get faster access to your offers, stats, and payouts directly from your phone — anytime, anywhere.
            </p>
            <a href="<?= Helpers::e($_maUrl) ?>" target="_blank" rel="noopener"
               onclick="document.getElementById('mobile-app-popup').remove()"
               style="margin-top:6px;display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#000;color:#fff;padding:11px 18px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px">
                <svg width="22" height="22" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#EA4335" d="M325.3 234.3 104.4 13.4l270.6 156.3-49.7 64.6z"/>
                    <path fill="#FBBC04" d="M104.4 13.4 325.3 234.3l-49.7 64.6L104.4 498.6V13.4z"/>
                    <path fill="#34A853" d="M375 169.7 104.4 13.4l-7.7 245.3 278.3-89z"/>
                    <path fill="#4285F4" d="M375 342.3 104.4 498.6 325.3 277.7z"/>
                </svg>
                <span style="text-align:left;line-height:1.15">
                    <span style="display:block;font-size:10px;color:#bbb;letter-spacing:.04em">GET IT ON</span>
                    <span style="display:block;font-size:15px;font-weight:600">Google Play</span>
                </span>
            </a>
            <button type="button" onclick="document.getElementById('mobile-app-popup').remove()"
                    style="background:none;border:none;cursor:pointer;color:#94A3B8;font-size:12px;padding:6px;margin-top:2px">
                Not now
            </button>
        </div>
    </div>
</div>
<style>
@keyframes map-fade{from{opacity:0}to{opacity:1}}
@keyframes map-pop{from{transform:scale(.92);opacity:0}to{transform:scale(1);opacity:1}}
@media (max-width:480px){
    #mobile-app-popup > div{max-width:none;border-radius:12px}
}
</style>
<?php endif; ?>
<main class="page-body">
<?php foreach(Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type']==='error'?'error':($f['type']==='success'?'success':'info') ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>
<script>
// ── Balance dropdown ──────────────────────────────────────────────────────
function toggleAffBal() {
    var dd = document.getElementById('aff-bal-dropdown');
    var open = dd.style.display !== 'none';
    dd.style.display = open ? 'none' : 'block';
    var caret = document.getElementById('aff-bal-caret');
    if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
}
document.addEventListener('click',function(e){
    var bw = document.getElementById('aff-bal-wrap');
    if (bw && !bw.contains(e.target)) {
        var dd = document.getElementById('aff-bal-dropdown');
        if (dd) { dd.style.display = 'none'; var c = document.getElementById('aff-bal-caret'); if(c) c.style.transform=''; }
    }
    var nw=document.getElementById('notif-wrap');
    if(nw&&!nw.contains(e.target)){var nd=document.getElementById('notif-dropdown');if(nd)nd.style.display='none';}
    // User-menu dropdown toggle
    var um=e.target.closest('.user-menu');
    if(um){
        var dm=um.querySelector('.dropdown-menu');
        if(dm){dm.classList.toggle('show');e.stopPropagation();}
    } else {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(d){d.classList.remove('show');});
    }
});
function toggleNotifDropdown(){
    var d=document.getElementById('notif-dropdown');
    var opening=d.style.display==='none';
    d.style.display=opening?'block':'none';
    if(opening){
        loadNotifications();
        // On small screens use fixed positioning so the dropdown
        // never overflows the left viewport edge.
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

// ── Fraud Alerts ────────────────────────────────────────────────────────────
// Independent of the regular notifications bell. Pulses red when any unread
// alert is High Risk (score ≥ 80); plain badge for Medium Risk (60–79).
var _faCsrf = '<?= Auth::generateCsrf() ?>';
function _faEscape(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
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
            return '<a class="fa-card '+risk+(resolved?' resolved':'')+'" href="'+_faEscape(a.link||'#')+'" onclick="markOneFraudRead('+a.id+')">' +
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
// Slow full-list reconciliation every 60s (covers resolved/edited rows).
setInterval(loadFraudAlerts, 60000);

// ── Real-time delivery (no manual refresh required) ───────────────────────
// Lightweight 'since' poll every 8s — only fetches alerts created since the
// last seen id, so the request stays cheap even with thousands of records.
// On a new arrival we: (1) update the bell badge, (2) flash + shake the bell,
// (3) play a short chime, (4) push a toast with conversion id / offer / time.
var FA_LS_KEY = 'fa_last_seen_id';
var faLastSeen = parseInt(localStorage.getItem(FA_LS_KEY) || '0', 10) || 0;
var faAudioCtx = null;
function faPlayChime(highRisk){
    try {
        if (!window.AudioContext && !window.webkitAudioContext) return;
        if (!faAudioCtx) faAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var ctx = faAudioCtx;
        var t   = ctx.currentTime;
        // Two short tones; higher pitch & louder for high risk.
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
                ' has been detected as fraudulent.' +
            '</div>' +
            (a.offer_name ? '<div style="font-size:11px;color:#475569;margin-top:3px">Offer: <strong>' + _faEscape(a.offer_name) + '</strong></div>' : '') +
            '<div class="fa-toast-meta">' +
                '<a href="' + _faEscape(a.link || '/affiliate/fraud-report') + '" style="color:#4F46E5;font-weight:600">View &rarr;</a>' +
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
    // Auto-dismiss after 10s (high) or 8s (medium).
    setTimeout(dismiss, risk === 'high' ? 10000 : 8000);
}
function faOnNewAlerts(arr, unread){
    // Update bell badge immediately.
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
        // Force re-trigger animation.
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
    // If the dropdown is open, refresh it so the new rows show without a click.
    var d = document.getElementById('fa-dropdown');
    if (d && d.style.display === 'block') loadFraudAlerts();
}
function faPollSince(){
    if (document.hidden) return; // skip when tab is backgrounded — resume on visibility change
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
            // Keep the badge in sync even when nothing new arrived (resolved/mark-read).
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
// On first load, seed faLastSeen from the current max id (so we don't toast
// old alerts the affiliate has already seen).
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
        // Start the fast poll AFTER seeding so we never miss a row.
        setInterval(faPollSince, 8000);
        // Run once immediately so a brand-new alert between seed and first
        // interval still triggers within ~1s instead of waiting 8s.
        setTimeout(faPollSince, 1200);
    }).catch(function(){
        // If seed fails, still start the loop — worst case we miss one cycle.
        setInterval(faPollSince, 8000);
    });
})();
// Catch up immediately when the tab becomes visible again.
document.addEventListener('visibilitychange', function(){
    if (!document.hidden) faPollSince();
});

// ── News unread badge ──────────────────────────────────────────────────────
function loadNewsBadge() {
    fetch('/affiliate/news?action=unread_count')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            var badge = document.getElementById('news-badge');
            if (!badge) return;
            var count = d.unread || 0;
            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }).catch(function(){});
}
loadNewsBadge();
setInterval(loadNewsBadge, 120000);

(function heartbeat(){
    fetch('/api/activity', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=heartbeat&page='+encodeURIComponent(window.location.pathname)
    }).then(function(r){return r.json();}).then(function(d){if(d&&d.error==='session_killed')window.location.href='/login';}).catch(function(){});
    setTimeout(heartbeat, 30000);
})();

// ── Affiliate Balance — live update ───────────────────────────────────────
function _affFmt(n){ return '$'+parseFloat(n||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }
function refreshAffBalance() {
    fetch('/api/stats')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.error) return;
            var balEl = document.getElementById('aff-balance-display');
            if (balEl) {
                var fmtd = _affFmt(d.balance);
                if (balEl.dataset.prev && balEl.dataset.prev !== fmtd) {
                    balEl.style.transition = 'color .4s';
                    balEl.style.color = '#059669';
                    setTimeout(function(){ balEl.style.color = ''; }, 1400);
                }
                balEl.textContent = fmtd;
                balEl.dataset.prev = fmtd;
            }
            var mainEl = document.getElementById('aff-bal-main');
            if (mainEl) mainEl.textContent = _affFmt(d.balance);
            var apEl = document.getElementById('aff-bal-approved');
            if (apEl) apEl.textContent = _affFmt(d.approved_total);
            var peEl = document.getElementById('aff-bal-pending');
            if (peEl) peEl.textContent = _affFmt(d.pending_total);
            var moEl = document.getElementById('aff-bal-month');
            if (moEl) moEl.textContent = _affFmt(d.month_approved);
        }).catch(function(){});
}
setInterval(refreshAffBalance, 30000);
document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'visible') refreshAffBalance();
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

// ── Preserve Sidebar Scroll Position ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var sb = document.querySelector('.sidebar');
    if (!sb) return;
    var savedPos = sessionStorage.getItem('aff_sidebar_scroll');
    if (savedPos) sb.scrollTop = parseInt(savedPos, 10);
    
    var saveScroll = function() { sessionStorage.setItem('aff_sidebar_scroll', sb.scrollTop); };
    sb.addEventListener('scroll', saveScroll, {passive: true});
    window.addEventListener('beforeunload', saveScroll);
});
</script>
