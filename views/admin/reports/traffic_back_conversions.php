<?php
$title = "Traffic Back Conversions Report";
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1>Traffic Back Conversions Report</h1>
        <p class="text-muted">Conversions generated from Traffic Back URL redirects (Admin Exclusive Report).</p>
    </div>
    <div>
        <a href="<?= Helpers::e($_SERVER['REQUEST_URI']) . (str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?') . 'export=csv' ?>" class="btn btn-outline-primary">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm" style="background:rgba(255,255,255,0.03);border-radius:12px">
            <div class="text-muted small text-uppercase font-weight-bold">Total Traffic Back Convs</div>
            <div class="h3 mb-0 mt-1 font-weight-bold"><?= number_format($summary['total']) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm" style="background:rgba(16,185,129,0.05);border:1px solid rgba(16,185,129,0.2) !important;border-radius:12px">
            <div class="text-success small text-uppercase font-weight-bold">Approved Convs</div>
            <div class="h3 mb-0 mt-1 font-weight-bold text-success"><?= number_format($summary['approved_count']) ?></div>
            <div class="small text-muted mt-1">$<?= number_format((float)$summary['approved_payout'], 2) ?> Payout</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm" style="background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.2) !important;border-radius:12px">
            <div class="text-warning small text-uppercase font-weight-bold">Pending Convs</div>
            <div class="h3 mb-0 mt-1 font-weight-bold text-warning"><?= number_format($summary['pending_count']) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card p-3 border-0 shadow-sm" style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2) !important;border-radius:12px">
            <div class="text-danger small text-uppercase font-weight-bold">Rejected Convs</div>
            <div class="h3 mb-0 mt-1 font-weight-bold text-danger"><?= number_format($summary['rejected_count']) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
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

<!-- Table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="card-title mb-0">Traffic Back Conversions List</span>
        <span class="badge bg-secondary"><?= count($conversions) ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Conversion ID</th>
                    <th>Click ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Status</th>
                    <th>IP / Country</th>
                    <th>Payout</th>
                    <th>Revenue</th>
                    <th>Converted At</th>
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
                            <td><code><?= Helpers::e($c['conversion_id']) ?></code></td>
                            <td><code title="<?= Helpers::e($c['click_id']) ?>"><?= Helpers::e(substr($c['click_id'], 0, 16)) ?>...</code></td>
                            <td>
                                <strong><?= Helpers::e($c['aff_name'] ?: 'N/A') ?></strong>
                                <?php if (!empty($c['affiliate_code'])): ?>
                                    <br><span class="badge bg-dark" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= Helpers::e($c['offer_name'] ?: 'N/A') ?></td>
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
                                <?= Helpers::e($c['ip_address']) ?>
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
