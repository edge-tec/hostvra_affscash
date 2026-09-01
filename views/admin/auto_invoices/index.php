<?php
/**
 * Automatic Invoice Generator & Billing Scheduler — Master View
 */
$pageTitle = 'Automatic Invoice Generator';
require BASE_PATH . '/views/layouts/admin.php';
?>

<!-- TomSelect CDN for Multi-Select -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
/* ── Auto Invoice Module Custom Styles ── */
.aig-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.aig-title h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-primary, #1e293b);
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.aig-title p {
    color: var(--text-muted, #64748b);
    font-size: 14px;
    margin: 0;
}

/* ── KPI Cards Grid ── */
.aig-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.aig-kpi-card {
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.aig-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.aig-kpi-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #64748b);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.aig-kpi-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-primary, #0f172a);
    line-height: 1.2;
}
.aig-kpi-sub {
    font-size: 12px;
    color: var(--text-muted, #94a3b8);
    margin-top: 4px;
}

/* ── Modern Pill Tabs ── */
.aig-tabs-nav {
    display: flex;
    gap: 8px;
    background: var(--card-bg, #ffffff);
    padding: 6px;
    border-radius: 12px;
    border: 1px solid var(--border-color, #e2e8f0);
    margin-bottom: 24px;
    overflow-x: auto;
}
.aig-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-muted, #64748b);
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
    border: none;
    background: transparent;
}
.aig-tab-btn:hover {
    color: #4f46e5;
    background: rgba(79, 70, 229, 0.05);
}
.aig-tab-btn.active {
    color: #ffffff;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
}
.aig-tab-btn svg {
    width: 16px;
    height: 16px;
}

/* ── Form & Switch styling ── */
.aig-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.aig-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.aig-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .3s;
    border-radius: 24px;
}
.aig-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .aig-slider {
    background-color: #10b981;
}
input:checked + .aig-slider:before {
    transform: translateX(20px);
}

/* ── Badges ── */
.badge-status-sent { background: #e0e7ff; color: #4338ca; }
.badge-status-paid { background: #d1fae5; color: #065f46; }
.badge-status-draft { background: #f1f5f9; color: #475569; }
.badge-status-void, .badge-status-cancelled { background: #fee2e2; color: #991b1b; }
.badge-status-viewed { background: #fef3c7; color: #92400e; }

/* ── Modal styling ── */
.aig-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.aig-modal {
    background: var(--card-bg, #ffffff);
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.aig-modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.aig-modal-body {
    padding: 24px;
}
.aig-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color, #e2e8f0);
    background: var(--bg-muted, #f8fafc);
    border-radius: 0 0 16px 16px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* ── TomSelect Dropdown Fix (Opaque Background & High Contrast) ── */
.ts-wrapper {
    position: relative;
    z-index: 20;
}
.ts-control {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    min-height: 42px !important;
    padding: 6px 12px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    font-size: 13.5px !important;
}
.ts-dropdown {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 8px !important;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22), 0 4px 10px rgba(0, 0, 0, 0.1) !important;
    z-index: 99999 !important;
    max-height: 250px !important;
    overflow-y: auto !important;
    margin-top: 4px !important;
    opacity: 1 !important;
}
.ts-dropdown .ts-dropdown-content {
    background-color: #ffffff !important;
    background: #ffffff !important;
}
.ts-dropdown .option {
    padding: 10px 14px !important;
    color: #0f172a !important;
    background-color: #ffffff !important;
    background: #ffffff !important;
    font-size: 13px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    cursor: pointer !important;
    opacity: 1 !important;
}
.ts-dropdown .option:hover,
.ts-dropdown .active {
    background-color: #eef2ff !important;
    background: #eef2ff !important;
    color: #4338ca !important;
    font-weight: 600 !important;
}
.ts-dropdown .selected {
    background-color: #f8fafc !important;
    color: #64748b !important;
}
.ts-control .item {
    background: #e0e7ff !important;
    color: #3730a3 !important;
    border: 1px solid #c7d2fe !important;
    border-radius: 6px !important;
    padding: 2px 8px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
}
</style>

<!-- Module Header -->
<div class="aig-header">
    <div class="aig-title">
        <h1>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="M7 15h2"/><path d="M15 15h2"/>
            </svg>
            Automatic Invoice Generator &amp; Scheduler
        </h1>
        <p>Configure automated affiliate billing cycles, custom priority rules, live previews &amp; cron delivery.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <button type="button" class="btn btn-secondary" onclick="triggerRunNow()" style="display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            Run Scheduler Now
        </button>
        <a href="/admin/auto-invoices?tab=manual" class="btn btn-primary" style="display:flex;align-items:center;gap:6px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Manual Invoice
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="aig-kpi-grid">
    <div class="aig-kpi-card" style="border-top: 3.5px solid <?= $stats['auto_enabled'] ? '#10b981' : '#ef4444' ?>;">
        <div class="aig-kpi-label">
            <span>Scheduler Status</span>
            <span class="badge badge-<?= $stats['auto_enabled'] ? 'success' : 'danger' ?>" style="font-size:10px;padding:2px 8px">
                <?= $stats['auto_enabled'] ? 'ENABLED' : 'DISABLED' ?>
            </span>
        </div>
        <div class="aig-kpi-value" style="font-size:18px;margin-top:4px">
            <?= ucfirst(str_replace('_', ' ', $stats['frequency'] ?? 'monthly')) ?>
        </div>
        <div class="aig-kpi-sub">
            Next Run: <?= $stats['next_run_at'] ? date('M j, H:i', strtotime($stats['next_run_at'])) . ' UTC' : 'Pending' ?>
        </div>
    </div>

    <div class="aig-kpi-card" style="border-top: 3.5px solid #6366f1;">
        <div class="aig-kpi-label">Invoices This Month</div>
        <div class="aig-kpi-value"><?= number_format($stats['invoices_this_month']) ?></div>
        <div class="aig-kpi-sub">$<?= number_format($stats['invoiced_amount_month'], 2) ?> invoiced</div>
    </div>

    <div class="aig-kpi-card" style="border-top: 3.5px solid #f59e0b;">
        <div class="aig-kpi-label">Pending Payouts</div>
        <div class="aig-kpi-value" style="color:#d97706">$<?= number_format($stats['pending_amount'], 2) ?></div>
        <div class="aig-kpi-sub"><?= number_format($stats['pending_count']) ?> awaiting payment</div>
    </div>

    <div class="aig-kpi-card" style="border-top: 3.5px solid #10b981;">
        <div class="aig-kpi-label">Total Paid (All-Time)</div>
        <div class="aig-kpi-value" style="color:#059669">$<?= number_format($stats['paid_amount'], 2) ?></div>
        <div class="aig-kpi-sub"><?= number_format($stats['paid_count']) ?> invoices marked paid</div>
    </div>

    <div class="aig-kpi-card" style="border-top: 3.5px solid #8b5cf6;">
        <div class="aig-kpi-label">Custom Rules Active</div>
        <div class="aig-kpi-value" style="font-size:20px"><?= $stats['affiliate_rules_count'] ?> Aff / <?= $stats['offer_rules_count'] ?> Offer</div>
        <div class="aig-kpi-sub">Priority rule overrides</div>
    </div>
</div>

<!-- Tab Navigation -->
<nav class="aig-tabs-nav">
    <a href="/admin/auto-invoices?tab=scheduler" class="aig-tab-btn <?= $tab === 'scheduler' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        1. Invoice Scheduler
    </a>
    <a href="/admin/auto-invoices?tab=affiliate_rules" class="aig-tab-btn <?= $tab === 'affiliate_rules' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        2. Affiliate Billing Rules
    </a>
    <a href="/admin/auto-invoices?tab=offer_rules" class="aig-tab-btn <?= $tab === 'offer_rules' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
        3. Offer Billing Rules
    </a>
    <a href="/admin/auto-invoices?tab=invoices" class="aig-tab-btn <?= $tab === 'invoices' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
        4. Generated Invoices
    </a>
    <a href="/admin/auto-invoices?tab=logs" class="aig-tab-btn <?= $tab === 'logs' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
        5. Invoice Logs
    </a>
    <a href="/admin/auto-invoices?tab=manual" class="aig-tab-btn <?= $tab === 'manual' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        6. Manual Generate Invoice
    </a>
</nav>

<!-- Tab Content -->
<div class="aig-tab-content">
    <?php
    switch ($tab) {
        case 'affiliate_rules':
            require BASE_PATH . '/views/admin/auto_invoices/tabs/affiliate_rules.php';
            break;
        case 'offer_rules':
            require BASE_PATH . '/views/admin/auto_invoices/tabs/offer_rules.php';
            break;
        case 'invoices':
            require BASE_PATH . '/views/admin/auto_invoices/tabs/invoices.php';
            break;
        case 'logs':
            require BASE_PATH . '/views/admin/auto_invoices/tabs/logs.php';
            break;
        case 'manual':
            require BASE_PATH . '/views/admin/auto_invoices/tabs/manual.php';
            break;
        case 'scheduler':
        default:
            require BASE_PATH . '/views/admin/auto_invoices/tabs/scheduler.php';
            break;
    }
    ?>
</div>

<!-- Preview Modal -->
<?php require BASE_PATH . '/views/admin/auto_invoices/preview_modal.php'; ?>

<!-- Global Action JS -->
<script>
if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.errMode = 'none';
}

function triggerRunNow() {
    if (!confirm('Run the automated invoice scheduler now? This will evaluate all eligible conversions and generate invoices for qualifying affiliates.')) return;
    
    var btn = event.currentTarget;
    var origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Processing...';

    var token = document.querySelector('meta[name="csrf-token"]').content;
    var fd = new FormData();
    fd.append('_token', token);
    fd.append('action', 'run_scheduler_now');

    fetch('/admin/auto-invoices', {
        method: 'POST',
        body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) {
            alert(d.message);
            window.location.reload();
        } else {
            alert('Error: ' + (d.error || 'Failed to run scheduler.'));
        }
    })
    .catch(function(err){
        alert('Network error while running scheduler.');
    })
    .finally(function(){
        btn.disabled = false;
        btn.innerHTML = origText;
    });
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
