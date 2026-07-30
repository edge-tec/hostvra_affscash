<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
/* ═══════════════════════════════════════════════════════════════════════
   3D GLASSMORPHISM CONVERSIONS CONTROL CENTER STYLES
   ═══════════════════════════════════════════════════════════════════════ */

/* Status Nav Bar */
.ac-status-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.ac-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 10px;
    text-decoration: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(226, 232, 240, 0.8);
    background: #ffffff;
    color: #64748b;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

html[data-theme="dark"] .ac-status-pill {
    background: rgba(30, 24, 60, 0.7);
    border-color: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.7);
}

.ac-status-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    color: #1e293b;
}

.ac-status-pill.active {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
    color: #ffffff !important;
    border-color: transparent !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
}

/* KPI Summary Cards Grid */
.ac-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.ac-kpi-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 16px 20px !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.25s ease !important;
}

html[data-theme="dark"] .ac-kpi-card {
    background: rgba(20, 14, 45, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25) !important;
}

.ac-kpi-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08) !important;
}

.ac-kpi-title {
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: #64748b !important;
    margin-bottom: 6px !important;
}

html[data-theme="dark"] .ac-kpi-title {
    color: rgba(255, 255, 255, 0.6) !important;
}

.ac-kpi-val {
    font-size: 24px !important;
    font-weight: 800 !important;
    line-height: 1.1 !important;
    color: #0f172a !important;
}

html[data-theme="dark"] .ac-kpi-val {
    color: #ffffff !important;
}

/* Glassmorphism Filter Card */
.ac-filter-card {
    background: rgba(255, 255, 255, 0.9) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03) !important;
}

html[data-theme="dark"] .ac-filter-card {
    background: rgba(22, 16, 48, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.ac-filter-card .form-control {
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 8px 12px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.ac-filter-card .form-control:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
}

/* Table Container & 3D Rows */
.ac-table-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 16px !important;
    overflow: hidden !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03) !important;
}

html[data-theme="dark"] .ac-table-card {
    background: rgba(18, 12, 38, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.ac-table-wrap {
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
}

#tbl-conversions {
    width: 100% !important;
    min-width: 1100px !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

#tbl-conversions thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 8px 10px !important;
    border-bottom: 2px solid #e2e8f0 !important;
}

html[data-theme="dark"] #tbl-conversions thead th {
    background: rgba(30, 22, 60, 0.9) !important;
    color: rgba(255, 255, 255, 0.75) !important;
    border-bottom-color: rgba(255, 255, 255, 0.1) !important;
}

/* Status Row Tinting */
tr.ac-row-approved { background: rgba(16, 185, 129, 0.02) !important; }
tr.ac-row-rejected { background: rgba(239, 68, 68, 0.025) !important; }
tr.ac-row-pending  { background: rgba(245, 158, 11, 0.025) !important; }

#tbl-conversions tbody tr {
    transition: all 0.2s ease !important;
}

#tbl-conversions tbody tr:hover {
    background: rgba(99, 102, 241, 0.05) !important;
}

#tbl-conversions tbody td {
    padding: 7px 10px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

html[data-theme="dark"] #tbl-conversions tbody td {
    border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}

/* ═══════════════════════════════════════════════════════════════════════
   COMPACT VIBRANT 3D STATUS BADGES & DISTINCT ACTION BUTTONS
   ═══════════════════════════════════════════════════════════════════════ */

/* Status Badges */
.ac-badge-approved {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10px !important;
    padding: 3px 8px !important;
    border-radius: 5px !important;
    box-shadow: 0 2px 5px rgba(16, 185, 129, 0.25) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
    letter-spacing: 0.02em !important;
}

.ac-badge-rejected {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10px !important;
    padding: 3px 8px !important;
    border-radius: 5px !important;
    box-shadow: 0 2px 5px rgba(239, 68, 68, 0.25) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
    letter-spacing: 0.02em !important;
}

.ac-badge-pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10px !important;
    padding: 3px 8px !important;
    border-radius: 5px !important;
    box-shadow: 0 2px 5px rgba(245, 158, 11, 0.25) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
    letter-spacing: 0.02em !important;
}

/* Distinct Compact 3D Action Buttons */
.ac-btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10.5px !important;
    padding: 3px 9px !important;
    border-radius: 5px !important;
    border: none !important;
    box-shadow: 0 2px 5px rgba(16, 185, 129, 0.25) !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
}

.ac-btn-approve:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35) !important;
    color: #ffffff !important;
}

.ac-btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10.5px !important;
    padding: 3px 9px !important;
    border-radius: 5px !important;
    border: none !important;
    box-shadow: 0 2px 5px rgba(239, 68, 68, 0.25) !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
}

.ac-btn-reject:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35) !important;
    color: #ffffff !important;
}

.ac-btn-cb {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 10.5px !important;
    padding: 3px 9px !important;
    border-radius: 5px !important;
    border: none !important;
    box-shadow: 0 2px 5px rgba(245, 158, 11, 0.25) !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
    white-space: nowrap !important;
}

.ac-btn-cb:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(245, 158, 11, 0.35) !important;
    color: #ffffff !important;
}

/* Compact Horizontal Fraud Score Grid */
.ac-fraud-score-grid {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 4px !important;
    max-width: 220px !important;
}

.ac-fs-pill {
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    font-size: 10.5px !important;
    font-weight: 700 !important;
    padding: 2px 7px !important;
    border-radius: 6px !important;
    white-space: nowrap !important;
}

.ac-fs-green { background: rgba(16, 185, 129, 0.12) !important; color: #10b981 !important; }
.ac-fs-yellow { background: rgba(245, 158, 11, 0.15) !important; color: #d97706 !important; }
.ac-fs-red { background: rgba(239, 68, 68, 0.15) !important; color: #dc2626 !important; }

/* Mobile Card View (Screens <= 768px) */
.ac-mobile-card-list {
    display: none;
    padding: 12px;
}

.ac-mobile-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

html[data-theme="dark"] .ac-mobile-card {
    background: rgba(20, 14, 45, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .ac-desktop-table-container { display: none !important; }
    .ac-mobile-card-list { display: block !important; }
}
</style>

<!-- Page Header Banner -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 font-weight-bold mb-1">Conversions Control Center</h1>
        <p class="text-muted mb-0">Monitor real-time conversions, payouts, revenues, and fraud risk scores.</p>
    </div>

    <!-- Status Navigation Pills & Export -->
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="ac-status-pills">
            <?php foreach(['all' => 'All Conversions', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $sKey => $sLabel): ?>
                <a href="/admin/conversions?<?= http_build_query(['status' => $sKey, 'from' => $from, 'to' => $to, 'click_id' => $clickId ?? null]) ?>"
                   class="ac-status-pill <?= $status === $sKey ? 'active' : '' ?>">
                    <span><?= $sLabel ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        $exportBaseGet = ['status' => $status, 'from' => $from, 'to' => $to, 'click_id' => $clickId ?? null];
        require BASE_PATH . '/views/partials/export_buttons.php';
        ?>
    </div>
</div>

<!-- Calculate Top KPI Stats -->
<?php
$totalCount = count($conversions);
$appCount = 0; $appPayout = 0; $appRev = 0;
$penCount = 0; $rejCount = 0;
foreach ($conversions as $cvItem) {
    $st = $cvItem['status'] ?? 'pending';
    if ($st === 'approved') {
        $appCount++;
        $appPayout += (float)($cvItem['payout'] ?? 0);
        $appRev    += (float)($cvItem['revenue'] ?? 0);
    } elseif ($st === 'pending') {
        $penCount++;
    } elseif ($st === 'rejected' || $st === 'chargebacked') {
        $rejCount++;
    }
}
?>

<!-- KPI Stats Overview Cards -->
<div class="ac-kpi-grid">
    <div class="ac-kpi-card">
        <div class="ac-kpi-title">Total Records</div>
        <div class="ac-kpi-val"><?= number_format($totalCount) ?></div>
        <div class="small text-muted mt-1">In current view</div>
    </div>
    <div class="ac-kpi-card" style="border-left:4px solid #10b981 !important">
        <div class="ac-kpi-title text-success">Approved Convs</div>
        <div class="ac-kpi-val text-success"><?= number_format($appCount) ?></div>
        <div class="small text-success font-weight-bold mt-1">$<?= number_format($appPayout, 2) ?> Payout</div>
    </div>
    <?php if (Auth::role() === 'admin'): ?>
    <div class="ac-kpi-card" style="border-left:4px solid #06b6d4 !important">
        <div class="ac-kpi-title text-info">Approved Revenue</div>
        <div class="ac-kpi-val text-info">$<?= number_format($appRev, 2) ?></div>
        <div class="small text-info font-weight-bold mt-1">Gross System Rev</div>
    </div>
    <?php endif; ?>
    <div class="ac-kpi-card" style="border-left:4px solid #f59e0b !important">
        <div class="ac-kpi-title text-warning">Pending Review</div>
        <div class="ac-kpi-val text-warning"><?= number_format($penCount) ?></div>
        <div class="small text-warning font-weight-bold mt-1">Awaiting approval</div>
    </div>
    <div class="ac-kpi-card" style="border-left:4px solid #ef4444 !important">
        <div class="ac-kpi-title text-danger">Rejected / Fraud</div>
        <div class="ac-kpi-val text-danger"><?= number_format($rejCount) ?></div>
        <div class="small text-danger font-weight-bold mt-1">Declined or blocked</div>
    </div>
</div>

<!-- Date Filter Bar -->
<div class="card ac-filter-card mb-4 filter-card filter-open" id="conv-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Parameters</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body" style="padding:18px 22px">
        <form method="GET" id="conv-filter-form" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="status" value="<?= Helpers::e($status) ?>">

            <div style="width:100%">
                <?php $drpFromId='conv-from'; $drpToId='conv-to'; $drpFormId='conv-filter-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            </div>

            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:700">From Date</label>
                <input type="date" id="conv-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:700">To Date</label>
                <input type="date" id="conv-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px;font-weight:700">Click ID</label>
                <input type="text" name="click_id" class="form-control" placeholder="Search by Click ID..." value="<?= Helpers::e($clickId ?? '') ?>" style="font-size:13px; min-width:200px;">
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary px-4 shadow-sm" style="border-radius:10px;font-weight:700">Apply Filter</button>
                <a href="/admin/conversions?status=<?= Helpers::e($status) ?>" class="btn btn-secondary px-3" style="border-radius:10px;font-weight:600">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card Container -->
<div class="ac-table-card">
    <div class="ac-desktop-table-container">
        <div class="ac-table-wrap">
            <table id="tbl-conversions">
                <thead>
                    <tr>
                        <th style="width:190px">IDs (Conv & Click)</th>
                        <th>Affiliate</th>
                        <th>Offer</th>
                        <th>Payout</th>
                        <?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?>
                        <th>Transaction</th>
                        <th>Status</th>
                        <th>Device & OS</th>
                        <th>Landing Page</th>
                        <th>Referrer Info</th>
                        <th>Fraud Scores</th>
                        <th>Converted At</th>
                        <th style="width:170px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($conversions as $c):
                    $fraudChecked = !empty($c['fraud_checked_at']);
                    $fs = $fraudChecked ? (int)($c['fraud_score'] ?? 0) : null;

                    // Build multi-provider tooltip
                    $providerParts = [];
                    if (isset($c['ipquery_risk_score']))  $providerParts[] = 'IPQ:' . (int)$c['ipquery_risk_score'];
                    if (isset($c['scamalytics_score']))   $providerParts[] = 'Scam:' . (int)$c['scamalytics_score'];
                    if (isset($c['proxycheck_score']))    $providerParts[] = 'PC:' . (int)$c['proxycheck_score'];
                    if (isset($c['botscout_is_bot']))     $providerParts[] = 'Bot:' . ($c['botscout_is_bot'] ? 'Y' : 'N');
                    if (isset($c['frauddefense_score']))  $providerParts[] = 'FD:' . (int)$c['frauddefense_score'];
                    $providerTip = implode(' | ', $providerParts);

                    // Resolve visit fields
                    $ua = $c['user_agent'] ?: ($c['ck_user_agent'] ?? '');

                    // Device brand / model
                    $deviceBrand = $c['device_brand'] ?: ucfirst($c['ck_device_type'] ?? $c['device_type'] ?? '');
                    $deviceModel = $c['device_model'] ?? '';
                    if (($deviceBrand === '' || $deviceModel === '') && $ua !== '') {
                        $knownBrands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
                        if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $ua, $m)) {
                            $raw = trim($m[1]); $brand = '';
                            foreach ($knownBrands as $b) { if (stripos($raw, $b) === 0) { $brand = $b; break; } }
                            if ($brand) { $deviceBrand = $brand; $deviceModel = trim(substr($raw, strlen($brand))) ?: $raw; }
                            else        { $deviceBrand = 'Android'; $deviceModel = $raw; }
                        } elseif (stripos($ua, 'iPhone') !== false) { $deviceBrand = 'Apple'; $deviceModel = 'iPhone'; }
                        elseif (stripos($ua, 'iPad') !== false) { $deviceBrand = 'Apple'; $deviceModel = 'iPad'; }
                        elseif (stripos($ua, 'Macintosh') !== false) { $deviceBrand = 'Apple'; $deviceModel = 'Mac'; }
                        elseif (stripos($ua, 'Windows') !== false) { $deviceBrand = 'PC'; $deviceModel = 'Windows'; }
                    }

                    // OS version
                    $osVersion = $c['os_version'] ?? '';
                    if ($osVersion === '') {
                        if ($ua !== '') {
                            if      (preg_match('/Android\s+([\d.]+)/i', $ua, $ov)) { $osVersion = 'Android ' . $ov[1]; }
                            elseif  (preg_match('/iPhone OS ([\d_]+)/i', $ua, $ov)) { $osVersion = 'iOS ' . str_replace('_', '.', $ov[1]); }
                            elseif  (preg_match('/iPad.*?OS ([\d_]+)/i', $ua, $ov)) { $osVersion = 'iPadOS ' . str_replace('_', '.', $ov[1]); }
                            elseif  (preg_match('/Windows NT ([\d.]+)/i', $ua, $ov)) {
                                $ntMap = ['10.0'=>'10/11','6.3'=>'8.1','6.2'=>'8','6.1'=>'7','6.0'=>'Vista','5.1'=>'XP'];
                                $osVersion = 'Windows ' . ($ntMap[$ov[1]] ?? $ov[1]);
                            }
                            elseif  (preg_match('/Mac OS X ([\d_]+)/i', $ua, $ov)) { $osVersion = 'macOS ' . str_replace('_', '.', $ov[1]); }
                        }
                        if ($osVersion === '' && !empty($c['ck_os'])) {
                            $osVersion = $c['ck_os'];
                        }
                    }

                    // Landing page
                    $landingPage = $c['landing_page'] ?? '';
                    $landingPageName = '';
                    if (isset($c['ck_lp_idx']) && $c['ck_lp_idx'] !== null) {
                        $lpArr  = !empty($c['offer_landing_pages'])      ? json_decode($c['offer_landing_pages'], true)      : null;
                        $lpNArr = !empty($c['offer_landing_page_names']) ? json_decode($c['offer_landing_page_names'], true) : null;
                        $_lpIdx = (int)$c['ck_lp_idx'];
                        if ($landingPage === '' && is_array($lpArr) && isset($lpArr[$_lpIdx])) {
                            $landingPage = $lpArr[$_lpIdx];
                        }
                        if (is_array($lpNArr) && isset($lpNArr[$_lpIdx])) {
                            $landingPageName = $lpNArr[$_lpIdx];
                        }
                    }

                    // Referrer
                    $visitRef = $c['referrer'] ?: ($c['ck_referer'] ?? '');
                    $rowClass = 'ac-row-' . ($c['status'] ?? 'pending');
                ?>
                <tr class="<?= $rowClass ?>">
                    <!-- IDs Column -->
                    <td style="font-family:monospace;font-size:11px;white-space:nowrap">
                        <div style="font-weight:700;color:#6366f1" title="Conversion ID: <?= Helpers::e($c['conversion_id']) ?>">
                            <?= Helpers::e($c['conversion_id']) ?>
                            <button type="button" class="btn btn-link p-0 ms-1 text-muted" style="font-size:10px;text-decoration:none" onclick="navigator.clipboard.writeText('<?= Helpers::e($c['conversion_id']) ?>');this.innerText='✓';setTimeout(()=>this.innerText='📋',1000)" title="Copy Conversion ID">📋</button>
                        </div>
                        <?php if (!empty($c['click_id'])): ?>
                        <div style="font-size:10.5px;color:var(--text-muted);margin-top:3px" title="Click ID: <?= Helpers::e($c['click_id']) ?>">
                            <span style="opacity:0.75">Click:</span> <?= Helpers::e($c['click_id']) ?>
                            <button type="button" class="btn btn-link p-0 ms-1 text-muted" style="font-size:10px;text-decoration:none" onclick="navigator.clipboard.writeText('<?= Helpers::e($c['click_id']) ?>');this.innerText='✓';setTimeout(()=>this.innerText='📋',1000)" title="Copy Click ID">📋</button>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Affiliate Column -->
                    <td>
                        <div class="fw-bold" style="font-size:13px"><?= Helpers::e($c['aff_name']) ?></div>
                        <span class="badge bg-dark mt-1" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></span>
                    </td>

                    <!-- Offer Column -->
                    <td style="max-width:200px">
                        <strong class="text-primary d-block text-truncate" style="font-size:12.5px" title="<?= Helpers::e($c['offer_name']) ?>">
                            <?= Helpers::e($c['offer_name'] ?: '— Custom URL —') ?>
                        </strong>
                    </td>

                    <!-- Payout Column -->
                    <td>
                        <strong class="text-success" style="font-size:14px">$<?= number_format($c['payout'],2) ?></strong>
                    </td>

                    <!-- Revenue Column -->
                    <?php if (Auth::role() === "admin"): ?>
                    <td>
                        <strong class="text-info" style="font-size:14px">$<?= number_format($c['revenue'],2) ?></strong>
                    </td>
                    <?php endif; ?>

                    <!-- Transaction ID -->
                    <td>
                        <code style="font-size:11px"><?= Helpers::e($c['transaction_id'] ?: '—') ?></code>
                    </td>

                    <!-- Status Column -->
                    <td>
                        <?php if ($c['status'] === 'approved'): ?>
                            <span class="ac-badge-approved">✓ APPROVED</span>
                        <?php elseif ($c['status'] === 'pending'): ?>
                            <span class="ac-badge-pending">⏳ PENDING</span>
                        <?php else: ?>
                            <span class="ac-badge-rejected">✕ REJECTED</span>
                        <?php endif; ?>

                        <?php if(!empty($c['is_fraud'])): ?>
                            <span class="badge bg-danger ms-1" style="font-size:9px">FRAUD</span>
                        <?php endif; ?>

                        <?php if ($c['status'] === 'rejected' && !empty($c['rejection_reason'])): ?>
                            <div style="margin-top:4px;font-size:10.5px;color:#dc2626;line-height:1.3" title="<?= Helpers::e($c['rejection_reason']) ?>">
                                <strong>Reason:</strong> <?= Helpers::e(mb_strlen($c['rejection_reason'])>35 ? mb_substr($c['rejection_reason'],0,35).'…' : $c['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Device & OS -->
                    <td>
                        <?php if ($deviceBrand !== '' && $deviceBrand !== null): ?>
                            <div style="font-size:12px;font-weight:700"><?= Helpers::e($deviceBrand) ?></div>
                        <?php endif; ?>
                        <?php if ($osVersion !== ''): ?>
                            <span class="badge bg-light text-dark border mt-1" style="font-size:10px"><?= Helpers::e($osVersion) ?></span>
                        <?php endif; ?>
                    </td>

                    <!-- Landing Page -->
                    <td style="max-width:160px">
                        <?php if ($landingPageName !== ''): ?>
                            <div style="font-size:11.5px;font-weight:700;margin-bottom:2px"><?= Helpers::e($landingPageName) ?></div>
                        <?php endif; ?>
                        <?php if ($landingPage !== ''): ?>
                            <a href="<?= Helpers::e($landingPage) ?>" target="_blank" rel="noopener"
                               title="<?= Helpers::e($landingPage) ?>"
                               style="font-size:11px;color:#2563eb;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= Helpers::e(preg_replace('#^https?://#', '', $landingPage)) ?>
                            </a>
                        <?php else: ?><span class="text-muted" style="font-size:11px">—</span><?php endif; ?>
                    </td>

                    <!-- Referrer Info -->
                    <td style="max-width:150px">
                        <?php if ($visitRef !== ''): ?>
                            <a href="<?= Helpers::e($visitRef) ?>" target="_blank" rel="noopener"
                               title="<?= Helpers::e($visitRef) ?>"
                               style="font-size:11px;color:#6366f1;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= Helpers::e(preg_replace('#^https?://([^/]+).*#', '$1', $visitRef)) ?>
                            </a>
                        <?php else: ?><span class="text-muted" style="font-size:11px">—</span><?php endif; ?>
                    </td>

                    <!-- Compact 3D Fraud Risk Score Grid -->
                    <td>
                        <div class="ac-fraud-score-grid">
                            <?php
                            $getScoreClass = function($s) {
                                if ($s >= 60) return 'ac-fs-red';
                                if ($s >= 25) return 'ac-fs-yellow';
                                return 'ac-fs-green';
                            };
                            ?>

                            <?php if ($fs !== null): ?>
                                <span class="ac-fs-pill <?= $getScoreClass($fs) ?>" title="IPQS Risk Score">IPQS: <?= $fs ?></span>
                            <?php endif; ?>

                            <?php $ipqS = isset($c['ipquery_risk_score']) ? (int)$c['ipquery_risk_score'] : null; ?>
                            <?php if ($ipqS !== null): ?>
                                <span class="ac-fs-pill <?= $getScoreClass($ipqS) ?>" title="IPQuery Score">IPQ: <?= $ipqS ?></span>
                            <?php endif; ?>

                            <?php $scamS = isset($c['scamalytics_score']) ? (int)$c['scamalytics_score'] : null; ?>
                            <?php if ($scamS !== null): ?>
                                <span class="ac-fs-pill <?= $getScoreClass($scamS) ?>" title="Scamalytics Score">Scam: <?= $scamS ?></span>
                            <?php endif; ?>

                            <?php $pcS = isset($c['proxycheck_score']) ? (int)$c['proxycheck_score'] : null; ?>
                            <?php if ($pcS !== null): ?>
                                <span class="ac-fs-pill <?= $getScoreClass($pcS) ?>" title="ProxyCheck Score">PC: <?= $pcS ?></span>
                            <?php endif; ?>

                            <?php if (isset($c['botscout_is_bot'])): ?>
                                <span class="ac-fs-pill <?= $c['botscout_is_bot'] ? 'ac-fs-red' : 'ac-fs-green' ?>">
                                    Bot: <?= $c['botscout_is_bot'] ? 'YES' : 'Clean' ?>
                                </span>
                            <?php endif; ?>

                            <?php $fdS = isset($c['frauddefense_score']) ? (int)$c['frauddefense_score'] : null; ?>
                            <?php if ($fdS !== null): ?>
                                <span class="ac-fs-pill <?= $getScoreClass($fdS) ?>" title="FraudDefense Score">FD: <?= $fdS ?></span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Date -->
                    <td class="text-nowrap small text-muted font-weight-bold">
                        <?= date('M j, H:i', strtotime($c['converted_at'])) ?>
                    </td>

                    <!-- 3D Actions Column with Distinct Gradient Buttons -->
                    <td>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <?php if ($c['status'] !== 'approved'): ?>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Approve this conversion?\nPayout $<?= number_format((float)$c['payout'],2) ?> will be credited to the affiliate.')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                                <input type="hidden" name="status" value="approved">
                                <input type="hidden" name="redirect_back" value="/admin/conversions?<?= Helpers::e(http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))) ?>">
                                <button class="ac-btn-approve">✓ Approve</button>
                            </form>
                            <?php else: ?>
                            <span class="ac-badge-approved">✓ APPROVED</span>
                            <?php endif; ?>

                            <?php if ($c['status'] !== 'rejected'): ?>
                            <button type="button" class="ac-btn-reject"
                                    onclick="openRejectModal('<?= Helpers::e($c['conversion_id']) ?>')">✕ Reject</button>
                            <?php else: ?>
                            <span class="ac-badge-rejected">✕ REJECTED</span>
                            <?php endif; ?>

                            <?php if ($c['status'] === 'approved'): ?>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Mark this conversion as chargebacked?')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                                <input type="hidden" name="status" value="chargebacked">
                                <input type="hidden" name="redirect_back" value="/admin/conversions?<?= Helpers::e(http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))) ?>">
                                <button class="ac-btn-cb">↩ CB</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Stacked Card List (Screens <= 768px) -->
    <div class="ac-mobile-card-list">
        <?php if (empty($conversions)): ?>
            <div class="text-center text-muted py-4">No conversions found matching criteria.</div>
        <?php else: ?>
            <?php foreach ($conversions as $c): ?>
                <div class="ac-mobile-card">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <?php if ($c['status'] === 'approved'): ?>
                                <span class="ac-badge-approved">APPROVED</span>
                            <?php elseif ($c['status'] === 'pending'): ?>
                                <span class="ac-badge-pending">PENDING</span>
                            <?php else: ?>
                                <span class="ac-badge-rejected">REJECTED</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="text-success font-weight-bold" style="font-size:14px">$<?= number_format((float)$c['payout'], 2) ?></span>
                            <?php if (Auth::role() === "admin"): ?>
                            <span class="text-muted mx-1">|</span>
                            <span class="text-info font-weight-bold" style="font-size:14px">$<?= number_format((float)$c['revenue'], 2) ?> Rev</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Offer Name:</span>
                        <strong class="text-primary small text-end"><?= Helpers::e($c['offer_name'] ?: 'N/A') ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Affiliate:</span>
                        <span class="small font-weight-bold">
                            <?= Helpers::e($c['aff_name'] ?: 'N/A') ?>
                            <span class="badge bg-dark ms-1" style="font-size:9px"><?= Helpers::e($c['affiliate_code']) ?></span>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Conversion ID:</span>
                        <code><?= Helpers::e($c['conversion_id']) ?></code>
                    </div>

                    <?php if (!empty($c['click_id'])): ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Click ID:</span>
                        <code><?= Helpers::e($c['click_id']) ?></code>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <span class="text-muted small"><?= date('M j, H:i', strtotime($c['converted_at'])) ?></span>
                        <div class="d-flex gap-1">
                            <?php if ($c['status'] !== 'approved'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Approve this conversion?')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                                <input type="hidden" name="status" value="approved">
                                <input type="hidden" name="redirect_back" value="/admin/conversions?<?= Helpers::e(http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))) ?>">
                                <button class="ac-btn-approve py-1 px-2" style="font-size:11px">✓</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($c['status'] !== 'rejected'): ?>
                            <button type="button" class="ac-btn-reject py-1 px-2" style="font-size:11px" onclick="openRejectModal('<?= Helpers::e($c['conversion_id']) ?>')">✕</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    $('#tbl-conversions').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>

<?php
// Reject-with-reason modal
$rejectFormAction  = '/admin/conversions';
$rejectStatusField = 'status';
$rejectStatusValue = 'rejected';
$rejectExtraHidden = ['redirect_back' => '/admin/conversions?' . http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))];
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
