<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= Auth::generateCsrf() ?>">
<title><?= Helpers::e($pageTitle ?? 'Super Admin') ?> — EliteAli SaaS Control Panel</title>
<?php if ($fav = Config::get('config','app.favicon')): ?><link rel="icon" href="<?= Helpers::e($fav) ?>"><?php endif; ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="/assets/css/app.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<style>
/* ── Premium SaaS Super Admin Styling ────────────────────────────────────── */
:root {
    --bg-main: #090816;
    --bg-card: rgba(22, 19, 48, 0.45);
    --border-glass: rgba(139, 92, 246, 0.18);
    --primary-grad: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
    --accent-glow: rgba(139, 92, 246, 0.25);
    --text-muted: #94A3B8;
    --font-heading: 'Rajdhani', sans-serif;
    --font-body: 'DM Sans', sans-serif;
}

/* Light Theme Variables & Elements Overrides */
html[data-theme="light"] {
    --bg-main: #F1F5F9;
    --bg-card: rgba(255, 255, 255, 0.75);
    --border-glass: rgba(99, 102, 241, 0.12);
    --accent-glow: rgba(99, 102, 241, 0.08);
    --text-muted: #475569;
}
html[data-theme="light"] body {
    color: #1E293B !important;
    background-color: #F8FAFC !important;
    background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.04) 0%, transparent 40%),
                      radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.04) 0%, transparent 40%) !important;
}
html[data-theme="light"] .sidebar {
    background: #FFFFFF !important;
    border-right: 1px solid rgba(99, 102, 241, 0.1) !important;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.03);
}
html[data-theme="light"] .sidebar-logo {
    border-bottom: 1px solid rgba(99, 102, 241, 0.1);
}
html[data-theme="light"] .sidebar-logo span {
    background: linear-gradient(to right, #4F46E5, #7C3AED);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
html[data-theme="light"] .topbar {
    background: #FFFFFF !important;
    border-bottom: 1px solid rgba(99, 102, 241, 0.1) !important;
}
html[data-theme="light"] .topbar-title {
    color: #0F172A !important;
}
html[data-theme="light"] .nav-link {
    color: #475569 !important;
}
html[data-theme="light"] .nav-link:hover {
    color: #4F46E5 !important;
    background: rgba(99, 102, 241, 0.06) !important;
}
html[data-theme="light"] .nav-link.active {
    color: #FFFFFF !important;
    background: var(--primary-grad) !important;
}
html[data-theme="light"] .glass-table th {
    background: rgba(99, 102, 241, 0.04) !important;
    color: #4F46E5 !important;
    border-bottom: 1px solid rgba(99, 102, 241, 0.1) !important;
}
html[data-theme="light"] .glass-table td {
    color: #334155 !important;
    border-bottom: 1px solid rgba(99, 102, 241, 0.06) !important;
}
html[data-theme="light"] .glass-table tr:hover td {
    background: rgba(99, 102, 241, 0.02) !important;
}
html[data-theme="light"] .glass-input {
    background: #FFFFFF !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    color: #1E293B !important;
}
html[data-theme="light"] .glass-input:focus {
    border-color: #6366F1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
}
html[data-theme="light"] .glass-select {
    background-color: #FFFFFF !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    color: #1E293B !important;
}
html[data-theme="light"] .glass-select option {
    background-color: #FFFFFF !important;
    color: #1E293B !important;
}
html[data-theme="light"] .user-menu {
    border: 1px solid rgba(99, 102, 241, 0.15) !important;
    background: rgba(255, 255, 255, 0.9) !important;
    color: #1E293B !important;
}
html[data-theme="light"] .user-menu span {
    color: #1E293B !important;
}
html[data-theme="light"] .dropdown-menu {
    background: #FFFFFF !important;
    border: 1px solid rgba(99, 102, 241, 0.15) !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06) !important;
}
html[data-theme="light"] .dropdown-item {
    color: #334155 !important;
}
html[data-theme="light"] .dropdown-item:hover {
    background: rgba(99, 102, 241, 0.05) !important;
    color: #4F46E5 !important;
}
html[data-theme="light"] .dropdown-divider {
    border-top: 1px solid rgba(99, 102, 241, 0.1) !important;
}
html[data-theme="light"] .topbar-actions .theme-picker {
    background: #FFFFFF !important;
    border: 1px solid rgba(99, 102, 241, 0.15) !important;
}

/* ═══════════════════════════════════════════════════════════════════════════
   COMPREHENSIVE LIGHT THEME TEXT OVERRIDES
   Forces ALL text to dark/black in light mode — overrides inline styles too.
   ═══════════════════════════════════════════════════════════════════════════ */

/* --- Global headings, body text, strong, labels --- */
html[data-theme="light"] h1,
html[data-theme="light"] h2,
html[data-theme="light"] h3,
html[data-theme="light"] h4,
html[data-theme="light"] h5,
html[data-theme="light"] h6,
html[data-theme="light"] p,
html[data-theme="light"] li,
html[data-theme="light"] strong,
html[data-theme="light"] b,
html[data-theme="light"] label,
html[data-theme="light"] legend,
html[data-theme="light"] figcaption,
html[data-theme="light"] blockquote,
html[data-theme="light"] small,
html[data-theme="light"] dt,
html[data-theme="light"] dd {
    color: #0F172A !important;
}

/* --- Spans (exclude status badges, sliders, buttons) --- */
html[data-theme="light"] span:not(.slider):not(.btn-premium):not(.btn):not([style*="border-radius: 12px"]):not([style*="border-radius:12px"]) {
    color: #1E293B !important;
}

/* --- Table cells --- */
html[data-theme="light"] td,
html[data-theme="light"] th {
    color: #0F172A !important;
}

/* --- Glass card / Glass header / Glass title --- */
html[data-theme="light"] .glass-title {
    color: #0F172A !important;
}
html[data-theme="light"] .glass-header {
    background: rgba(99, 102, 241, 0.04) !important;
}

/* --- Stat cards --- */
html[data-theme="light"] .stat-card {
    background: rgba(255, 255, 255, 0.85) !important;
    border-color: rgba(99, 102, 241, 0.12) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
}
html[data-theme="light"] .stat-label {
    color: #4F46E5 !important;
}
html[data-theme="light"] .stat-value {
    color: #0F172A !important;
}
html[data-theme="light"] .stat-card div[style*="font-size: 11.5px"] {
    color: #475569 !important;
}

/* --- Sidebar section headers (in light mode) --- */
html[data-theme="light"] .sidebar-section {
    color: #4F46E5 !important;
}

/* --- All code elements --- */
html[data-theme="light"] code {
    background: rgba(99, 102, 241, 0.08) !important;
    color: #4F46E5 !important;
}

/* --- Glass inputs and selects (already covered above, reinforcing placeholder) --- */
html[data-theme="light"] .glass-input::placeholder {
    color: #94A3B8 !important;
}

/* --- Links inside content (not nav) --- */
html[data-theme="light"] .page-body a:not(.nav-link):not(.btn-premium):not(.dropdown-item) {
    color: #4F46E5 !important;
}

/* --- DataTables wrappers --- */
html[data-theme="light"] .dataTables_wrapper,
html[data-theme="light"] .dataTables_info,
html[data-theme="light"] .dataTables_length,
html[data-theme="light"] .dataTables_length label,
html[data-theme="light"] .dataTables_filter,
html[data-theme="light"] .dataTables_filter label,
html[data-theme="light"] .dataTables_paginate,
html[data-theme="light"] .dataTables_paginate a,
html[data-theme="light"] .dataTables_empty {
    color: #1E293B !important;
}
html[data-theme="light"] .dataTables_paginate a {
    background: rgba(99, 102, 241, 0.06) !important;
    border-color: rgba(99, 102, 241, 0.15) !important;
}
html[data-theme="light"] .dataTables_paginate a.current {
    background: var(--primary-grad) !important;
    color: #FFFFFF !important;
    border-color: transparent !important;
}
html[data-theme="light"] .dataTables_length select,
html[data-theme="light"] .dataTables_filter input {
    background: #FFFFFF !important;
    color: #1E293B !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 6px !important;
}

/* --- Text muted --- */
html[data-theme="light"] .text-muted,
html[data-theme="light"] [class*="text-muted"] {
    color: #64748B !important;
}

/* --- Modal overlays --- */
html[data-theme="light"] .modal-content,
html[data-theme="light"] div[style*="background: rgba(15, 12, 38"],
html[data-theme="light"] div[style*="background:rgba(15,12,38"],
html[data-theme="light"] div[style*="background: rgba(11, 9, 26"],
html[data-theme="light"] div[style*="background:rgba(11,9,26"] {
    background: rgba(255, 255, 255, 0.98) !important;
    color: #0F172A !important;
}
html[data-theme="light"] div[style*="background: rgba(30,27,75"] {
    background: rgba(99, 102, 241, 0.04) !important;
}

/* --- SVG icons in content --- */
html[data-theme="light"] .page-body svg {
    color: #475569 !important;
}
html[data-theme="light"] .nav-link.active svg {
    color: #FFFFFF !important;
}

/* --- Alert boxes --- */
html[data-theme="light"] .alert-success {
    background: rgba(16, 185, 129, 0.1) !important;
    color: #065F46 !important;
}
html[data-theme="light"] .alert-error {
    background: rgba(239, 68, 68, 0.1) !important;
    color: #991B1B !important;
}

/* --- Action buttons in tables (Assign Domains, Set Status) --- */
html[data-theme="light"] button[style*="color: #C7D2FE"] {
    color: #4F46E5 !important;
}

/* --- h4 section headers inside modals --- */
html[data-theme="light"] h4[style*="color: #818CF8"] {
    color: #4F46E5 !important;
}

/* --- Modal close buttons (× with inline color: #FFFFFF) --- */
html[data-theme="light"] button[style*="color: #FFFFFF"],
html[data-theme="light"] button[style*="color:#FFFFFF"] {
    color: #1E293B !important;
}

/* --- Cancel buttons in modals --- */
html[data-theme="light"] button[style*="background: rgba(255,255,255,0.1)"],
html[data-theme="light"] button[style*="background:rgba(255,255,255,0.1)"] {
    background: rgba(99, 102, 241, 0.08) !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    color: #4F46E5 !important;
}

/* --- Glass cards in light mode --- */
html[data-theme="light"] .glass-card {
    background: rgba(255, 255, 255, 0.85) !important;
    border-color: rgba(99, 102, 241, 0.1) !important;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04) !important;
}

/* --- Inline monospace / colored code blocks --- */
html[data-theme="light"] div[style*="font-family: monospace"] {
    color: #4F46E5 !important;
    background: rgba(99, 102, 241, 0.06) !important;
}

/* --- Sandbox mode toggle row backgrounds --- */
html[data-theme="light"] div[style*="background: rgba(30,27,75,0.2)"] {
    background: rgba(99, 102, 241, 0.04) !important;
}

/* --- Plan card footer rows --- */
html[data-theme="light"] div[style*="background: rgba(11, 9, 26, 0.4)"] {
    background: rgba(99, 102, 241, 0.03) !important;
}

/* --- Inline colored spans forced to dark: emails, action text --- */
html[data-theme="light"] span[style*="color: #E2E8F0"],
html[data-theme="light"] span[style*="color:#E2E8F0"],
html[data-theme="light"] span[style*="color: #C7D2FE"],
html[data-theme="light"] span[style*="color:#C7D2FE"],
html[data-theme="light"] span[style*="color: #A5B4FC"],
html[data-theme="light"] span[style*="color:#A5B4FC"],
html[data-theme="light"] span[style*="color: #F8FAFC"],
html[data-theme="light"] span[style*="color:#F8FAFC"],
html[data-theme="light"] strong[style*="color: #F8FAFC"],
html[data-theme="light"] strong[style*="color:#F8FAFC"],
html[data-theme="light"] strong[style*="color: #FFFFFF"],
html[data-theme="light"] strong[style*="color:#FFFFFF"] {
    color: #0F172A !important;
}

/* --- Inline accent-colored text (plans, domains) force to indigo --- */
html[data-theme="light"] span[style*="color: #818CF8"],
html[data-theme="light"] span[style*="color:#818CF8"],
html[data-theme="light"] strong[style*="color: #818CF8"],
html[data-theme="light"] strong[style*="color:#818CF8"],
html[data-theme="light"] span[style*="color: #A78BFA"],
html[data-theme="light"] span[style*="color:#A78BFA"],
html[data-theme="light"] strong[style*="color: #A78BFA"],
html[data-theme="light"] strong[style*="color:#A78BFA"],
html[data-theme="light"] span[style*="color: #C084FC"],
html[data-theme="light"] span[style*="color:#C084FC"],
html[data-theme="light"] strong[style*="color: #C084FC"],
html[data-theme="light"] strong[style*="color:#C084FC"] {
    color: #4F46E5 !important;
}

/* --- Toggle slider background (checked) in light mode --- */
html[data-theme="light"] .toggle-switch:checked + .slider {
    background-color: #6366F1 !important;
}

/* ═══════════════════════════════════════════════════════════════════════════ */

body {
    background-color: var(--bg-main) !important;
    background-image: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                      radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.08) 0%, transparent 40%) !important;
    color: #F8FAFC !important;
    font-family: var(--font-body);
}

.sidebar {
    background: rgba(11, 9, 26, 0.8) !important;
    backdrop-filter: blur(20px) !important;
    border-right: 1px solid var(--border-glass) !important;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
}

.sidebar-logo {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 20px;
    letter-spacing: 0.05em;
    background: linear-gradient(to right, #818CF8, #C084FC);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    border-bottom: 1px solid var(--border-glass);
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.sidebar-section {
    font-family: var(--font-heading);
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #818CF8 !important;
    text-transform: uppercase;
    font-size: 11px !important;
    margin: 20px 0 10px 12px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    font-size: 13.5px;
    font-weight: 500;
    color: #94A3B8 !important;
    text-decoration: none;
    border-radius: 10px;
    margin: 4px 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 3px solid transparent;
}

.nav-link svg {
    width: 18px;
    height: 18px;
    stroke-width: 2px;
    transition: transform 0.3s;
}

.nav-link:hover {
    color: #FFFFFF !important;
    background: rgba(99, 102, 241, 0.1);
    border-left-color: rgba(139, 92, 246, 0.5);
    transform: translateX(4px);
}

.nav-link.active {
    color: #FFFFFF !important;
    background: var(--primary-grad) !important;
    border-left-color: #A78BFA;
    box-shadow: 0 4px 20px var(--accent-glow);
    font-weight: 700;
}

.nav-link.active svg {
    transform: scale(1.1);
}

.topbar {
    background: rgba(11, 9, 26, 0.6) !important;
    backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid var(--border-glass) !important;
    padding: 15px 30px;
}

.topbar-title {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 24px;
    color: #F8FAFC;
    letter-spacing: 0.02em;
}

.main-content {
    background: transparent !important;
}

.page-body {
    padding: 30px;
}

/* Glassmorphic Cards & UI Elements */
.glass-card {
    background: var(--bg-card);
    backdrop-filter: blur(12px);
    border: 1px solid var(--border-glass);
    border-radius: 16px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.glass-card:hover {
    border-color: rgba(139, 92, 246, 0.3);
    box-shadow: 0 12px 40px 0 rgba(139, 92, 246, 0.1);
}

.glass-header {
    background: rgba(30, 27, 75, 0.3);
    border-bottom: 1px solid var(--border-glass);
    padding: 16px 24px;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}

.glass-title {
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: 700;
    color: #F1F5F9;
}

/* Stat Grid */
.stat-card {
    background: linear-gradient(135deg, rgba(26, 21, 60, 0.65) 0%, rgba(15, 12, 38, 0.65) 100%);
    border: 1px solid var(--border-glass);
    border-radius: 16px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.25);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: rgba(139, 92, 246, 0.4);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--primary-grad);
}

.stat-label {
    font-family: var(--font-heading);
    font-weight: 700;
    color: #A5B4FC;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: #FFFFFF;
    line-height: 1;
}

/* Custom premium buttons */
.btn-premium {
    background: var(--primary-grad);
    color: #FFFFFF !important;
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
    transition: all 0.3s;
}

.btn-premium:hover {
    opacity: 0.9;
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.5);
    transform: translateY(-1px);
}

/* Glassmorphic Tables */
.glass-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
}

.glass-table th {
    background: rgba(30, 27, 75, 0.4);
    font-family: var(--font-heading);
    font-weight: 700;
    color: #C7D2FE;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border-glass);
}

.glass-table td {
    padding: 14px 20px;
    border-bottom: 1px solid rgba(139, 92, 246, 0.08);
    color: #E2E8F0;
    vertical-align: middle;
}

.glass-table tr:hover td {
    background: rgba(139, 92, 246, 0.05);
}

/* Form Styles */
.glass-input {
    background: rgba(15, 12, 38, 0.6) !important;
    border: 1px solid var(--border-glass) !important;
    color: #FFFFFF !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
    font-size: 14px !important;
    transition: all 0.3s !important;
}

.glass-input:focus {
    border-color: #8B5CF6 !important;
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.25) !important;
    outline: none !important;
}

.glass-select {
    background-color: rgba(15, 12, 38, 0.6) !important;
    border: 1px solid var(--border-glass) !important;
    color: #FFFFFF !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
}

.glass-select option {
    background-color: #0F0C26;
    color: #FFFFFF;
}

.user-avatar {
    background: var(--primary-grad) !important;
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.4);
}
</style>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <span>🤖 ELITEALI SAAS</span>
    </div>

    <p class="sidebar-section">Global Control</p>
    <a href="/super_admin/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/dashboard') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Overview
    </a>
    <a href="/super_admin/tenants" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/tenants') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Manage Tenants
    </a>
    <a href="/super_admin/plans" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/plans') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"/></svg>
        Subscription Plans
    </a>
    <a href="/super_admin/landing_control" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/landing_control') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Landing Control
    </a>
    <a href="/super_admin/domains" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/domains') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        Assigned Domains
    </a>

    <p class="sidebar-section">Financials</p>
    <a href="/super_admin/billing" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/billing') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        SaaS Billing Logs
    </a>
    <a href="/super_admin/billing-settings" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/billing-settings') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        Billing Settings
    </a>

    <p class="sidebar-section">System Security</p>
    <a href="/super_admin/security" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/super_admin/security') ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Security &amp; Audits
    </a>
    
    <p class="sidebar-section">Super Admin</p>
    <a href="/logout" class="nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
    </a>
</aside>

<!-- Main -->
<div class="main-content">
<header class="topbar">
    <span class="topbar-title"><?= Helpers::e($pageTitle ?? 'SaaS Overview') ?></span>
    <div class="topbar-actions" style="display: flex; align-items: center; gap: 16px;">
        <?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?>
        <div class="user-menu" style="position:relative">
            <div class="user-avatar"><?= strtoupper(substr(Auth::currentUser()['first_name'] ?? 'S', 0, 1)) ?></div>
            <span><?= Helpers::e(Auth::currentUser()['first_name'] ?? 'Super') ?> (SaaS)</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="dropdown-menu">
                <span class="dropdown-item text-muted text-sm" style="font-size:11px;padding:8px 14px 4px;color:#94A3B8"><?= Helpers::e(Auth::currentUser()['email'] ?? '') ?></span>
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
// User-menu dropdown toggle
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
<script src="/assets/js/theme.js"></script>

<main class="page-body">
<?php if (!empty($_GET['success'])): ?>
<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($_GET['success']) ?>
</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($_GET['error']) ?>
</div>
<?php endif; ?>
