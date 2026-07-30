<?php
$title = "Traffic Back Conversions Report";
require BASE_PATH . '/views/layouts/admin.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════
   TRAFFIC BACK CONVERSIONS KPI CARDS & RESPONSIVE GRID
   ═══════════════════════════════════════════════════════════════════════ */
.tb-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.tb-kpi-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 18px 20px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
}

html[data-theme="dark"] .tb-kpi-card {
    background: rgba(20, 14, 45, 0.75) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25) !important;
}

.tb-kpi-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08) !important;
}

.tb-card-top {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 10px !important;
}

.tb-kpi-card .tb-title {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: #64748b !important;
}

html[data-theme="dark"] .tb-kpi-card .tb-title {
    color: rgba(255, 255, 255, 0.65) !important;
}

.tb-card-icon {
    width: 38px !important;
    height: 38px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
}

.tb-kpi-card .tb-val {
    font-size: 26px !important;
    font-weight: 800 !important;
    line-height: 1.1 !important;
    color: #0f172a !important;
}

html[data-theme="dark"] .tb-kpi-card .tb-val {
    color: #ffffff !important;
}

.tb-kpi-card .tb-sub {
    font-size: 12.5px !important;
    font-weight: 600 !important;
    margin-top: 6px !important;
}

/* Color Accent Variations */
.tb-card-total .tb-card-icon { background: rgba(99, 102, 241, 0.12) !important; color: #6366f1 !important; }
.tb-card-approved .tb-card-icon { background: rgba(16, 185, 129, 0.12) !important; color: #10b981 !important; }
.tb-card-approved .tb-val { color: #10b981 !important; }
.tb-card-revenue .tb-card-icon { background: rgba(6, 182, 212, 0.12) !important; color: #06b6d4 !important; }
.tb-card-revenue .tb-val { color: #06b6d4 !important; }
.tb-card-pending .tb-card-icon { background: rgba(245, 158, 11, 0.12) !important; color: #f59e0b !important; }
.tb-card-pending .tb-val { color: #f59e0b !important; }
.tb-card-rejected .tb-card-icon { background: rgba(239, 68, 68, 0.12) !important; color: #ef4444 !important; }
.tb-card-rejected .tb-val { color: #ef4444 !important; }

/* Desktop & Horizontal Table Wrapper */
.tb-table-wrapper {
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
    border-radius: 0 0 14px 14px !important;
}

.tb-conv-table {
    width: 100% !important;
    min-width: 980px !important;
    border-collapse: collapse !important;
}

.tb-conv-table code {
    white-space: nowrap !important;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    font-size: 12px !important;
}

/* Mobile Card View (for screens <= 768px) */
.tb-mobile-card-list {
    display: none;
    padding: 12px;
}

.tb-mobile-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

html[data-theme="dark"] .tb-mobile-item {
    background: rgba(20, 14, 45, 0.9);
    border-color: rgba(255,255,255,0.1);
}

.tb-mobile-item .m-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

html[data-theme="dark"] .tb-mobile-item .m-header {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

.tb-mobile-item .m-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 6px;
    font-size: 13px;
}

.tb-mobile-item .m-label {
    color: #64748b;
    font-weight: 600;
    font-size: 12px;
}

html[data-theme="dark"] .tb-mobile-item .m-label {
    color: rgba(255,255,255,0.6);
}

.tb-mobile-item .m-val {
    text-align: right;
    font-weight: 500;
}

/* Responsive Visibility Switch */
@media (max-width: 768px) {
    .tb-desktop-table-container {
        display: none !important;
    }
    .tb-mobile-card-list {
        display: block !important;
    }
    .tb-kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
}

@media (max-width: 480px) {
    .tb-kpi-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="page-header d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 font-weight-bold mb-1">Traffic Back Conversions Report</h1>
        <p class="text-muted mb-0">Conversions generated from Traffic Back URL redirects (Admin Exclusive Report).</p>
    </div>
    <div>
        <a href="<?= Helpers::e($_SERVER['REQUEST_URI']) . (str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?') . 'export=csv' ?>" class="btn btn-outline-primary shadow-sm">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
        </a>
    </div>
</div>

<!-- Responsive 5-Column KPI Grid -->
<div class="tb-kpi-grid">
    <!-- Card 1: Total -->
    <div class="tb-kpi-card tb-card-total">
        <div class="tb-card-top">
            <span class="tb-title">Total Traffic Back Convs</span>
            <div class="tb-card-icon">🔄</div>
        </div>
        <div class="tb-val"><?= number_format($summary['total']) ?></div>
        <div class="tb-sub text-muted">All tracked instances</div>
    </div>

    <!-- Card 2: Approved -->
    <div class="tb-kpi-card tb-card-approved">
        <div class="tb-card-top">
            <span class="tb-title">Approved Convs</span>
            <div class="tb-card-icon">✅</div>
        </div>
        <div class="tb-val"><?= number_format($summary['approved_count']) ?></div>
        <div class="tb-sub text-success font-weight-bold">$<?= number_format((float)$summary['approved_payout'], 2) ?> Payout</div>
    </div>

    <!-- Card 3: Revenue -->
    <div class="tb-kpi-card tb-card-revenue">
        <div class="tb-card-top">
            <span class="tb-title">Traffic Back Revenue</span>
            <div class="tb-card-icon">💵</div>
        </div>
        <div class="tb-val">$<?= number_format((float)$summary['approved_revenue'], 2) ?></div>
        <div class="tb-sub text-info font-weight-bold">Gross Earned Revenue</div>
    </div>

    <!-- Card 4: Pending -->
    <div class="tb-kpi-card tb-card-pending">
        <div class="tb-card-top">
            <span class="tb-title">Pending Convs</span>
            <div class="tb-card-icon">⏳</div>
        </div>
        <div class="tb-val"><?= number_format($summary['pending_count']) ?></div>
        <div class="tb-sub text-warning font-weight-bold">Awaiting review</div>
    </div>

    <!-- Card 5: Rejected -->
    <div class="tb-kpi-card tb-card-rejected">
        <div class="tb-card-top">
            <span class="tb-title">Rejected Convs</span>
            <div class="tb-card-icon">❌</div>
        </div>
        <div class="tb-val"><?= number_format($summary['rejected_count']) ?></div>
        <div class="tb-sub text-danger font-weight-bold">Declined or blocked</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/reports/traffic-back-conversions" class="d-flex gap-3 align-items-center" style="flex-wrap:wrap">
            <div class="form-group mb-0">
                <label>Date From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Date To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">-- All Offers --</option>
                    <?php foreach ($offerList as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $offerId == $o['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($o['name']) ?> (ID: <?= $o['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">-- All Affiliates --</option>
                    <?php foreach ($affList as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $affId == $a['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($a['name']) ?> (ID: <?= $a['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>-- All Statuses --</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <option value="100"  <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                    <option value="500"  <?= $limit === 500 ? 'selected' : '' ?>>500</option>
                    <option value="1000" <?= $limit === 1000 ? 'selected' : '' ?>>1000</option>
                    <option value="5000" <?= $limit === 5000 ? 'selected' : '' ?>>5000</option>
                </select>
            </div>
            <div style="align-self:flex-end" class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/admin/reports/traffic-back-conversions" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Main Container -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title mb-0 font-weight-bold">Traffic Back Conversions List</span>
        <span class="badge bg-secondary"><?= count($conversions) ?> records</span>
    </div>

    <!-- Desktop & Tablet Smooth Scroll Table -->
    <div class="tb-desktop-table-container">
        <div class="tb-table-wrapper">
            <table class="table table-hover align-middle mb-0 tb-conv-table">
                <thead>
                    <tr>
                        <th style="width:170px">Conversion ID</th>
                        <th style="width:140px">Click ID</th>
                        <th style="width:180px">Affiliate</th>
                        <th>Offer</th>
                        <th style="width:120px">Status</th>
                        <th style="width:150px">IP / Country</th>
                        <th style="width:110px">Payout</th>
                        <th style="width:110px">Revenue</th>
                        <th style="width:160px">Converted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conversions)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No Traffic Back conversions found for the selected criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($conversions as $c): ?>
                            <tr>
                                <td><code title="<?= Helpers::e($c['conversion_id']) ?>"><?= Helpers::e(substr($c['conversion_id'], 0, 16)) ?>...</code></td>
                                <td><code title="<?= Helpers::e($c['click_id']) ?>"><?= Helpers::e(substr($c['click_id'], 0, 12)) ?>...</code></td>
                                <td>
                                    <strong><?= Helpers::e($c['aff_name'] ?: 'N/A') ?></strong>
                                    <?php if (!empty($c['affiliate_code'])): ?>
                                        <br><span class="badge bg-dark" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= Helpers::e($c['offer_name'] ?: 'N/A') ?></strong></td>
                                <td>
                                    <?php if ($c['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($c['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                        <?php if (!empty($c['rejection_reason'])): ?>
                                            <br><small class="text-muted" style="font-size:10px"><?= Helpers::e($c['rejection_reason']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size:12px;font-family:monospace"><?= Helpers::e($c['ip_address']) ?></span>
                                    <?php if (!empty($c['country'])): ?>
                                        <span class="badge bg-light text-dark ms-1"><?= Helpers::e($c['country']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-success">$<?= number_format((float)$c['payout'], 4) ?></strong></td>
                                <td><strong class="text-info">$<?= number_format((float)$c['revenue'], 4) ?></strong></td>
                                <td class="text-nowrap small text-muted"><?= Helpers::e($c['converted_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Stacked Card List (Screens <= 768px) -->
    <div class="tb-mobile-card-list">
        <?php if (empty($conversions)): ?>
            <div class="text-center text-muted py-4">No Traffic Back conversions found for the selected criteria.</div>
        <?php else: ?>
            <?php foreach ($conversions as $c): ?>
                <div class="tb-mobile-item">
                    <div class="m-header">
                        <div>
                            <?php if ($c['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php elseif ($c['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <span class="text-success font-weight-bold" style="font-size:14px">$<?= number_format((float)$c['payout'], 2) ?></span>
                            <span class="text-muted mx-1">|</span>
                            <span class="text-info font-weight-bold" style="font-size:14px">$<?= number_format((float)$c['revenue'], 2) ?> Rev</span>
                        </div>
                    </div>

                    <div class="m-row">
                        <span class="m-label">Offer Name</span>
                        <span class="m-val text-primary font-weight-bold"><?= Helpers::e($c['offer_name'] ?: 'N/A') ?></span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">Affiliate</span>
                        <span class="m-val">
                            <?= Helpers::e($c['aff_name'] ?: 'N/A') ?>
                            <?php if (!empty($c['affiliate_code'])): ?>
                                <span class="badge bg-dark ms-1" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">IP / Country</span>
                        <span class="m-val">
                            <code style="font-size:11px"><?= Helpers::e($c['ip_address']) ?></code>
                            <?php if (!empty($c['country'])): ?>
                                <span class="badge bg-light text-dark ms-1"><?= Helpers::e($c['country']) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">Conversion ID</span>
                        <span class="m-val"><code style="font-size:11px"><?= Helpers::e($c['conversion_id']) ?></code></span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">Click ID</span>
                        <span class="m-val"><code style="font-size:11px"><?= Helpers::e($c['click_id']) ?></code></span>
                    </div>

                    <?php if (!empty($c['rejection_reason'])): ?>
                        <div class="m-row">
                            <span class="m-label text-danger">Reason</span>
                            <span class="m-val text-danger small"><?= Helpers::e($c['rejection_reason']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="m-row mt-2 pt-2 style="border-top:1px dashed rgba(0,0,0,0.1)">
                        <span class="m-label">Converted At</span>
                        <span class="m-val text-muted small"><?= Helpers::e($c['converted_at']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
