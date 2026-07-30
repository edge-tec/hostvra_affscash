<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; VPN &amp; Proxy Blocked Log</h1>
        <p>Tracking clicks blocked due to VPN or proxy detection for your assigned affiliates</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?php if ($vpnEnabled): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;background:#DCFCE7;color:#15803D;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px">
            <span style="width:7px;height:7px;border-radius:50%;background:#15803D;display:inline-block"></span>
            Detection Active
        </span>
        <?php else: ?>
        <span style="display:inline-flex;align-items:center;gap:5px;background:#FEF2F2;color:#991B1B;font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px">
            <span style="width:7px;height:7px;border-radius:50%;background:#991B1B;display:inline-block"></span>
            Detection Disabled
        </span>
        <?php endif; ?>
    </div>
</div>

<?php foreach (Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>

<?php if (!$vpnEnabled): ?>
<div class="alert alert-warning" style="margin-bottom:16px">
    &#9888; VPN &amp; Proxy detection is currently <strong>disabled</strong> system-wide.
</div>
<?php endif; ?>

<style>
/* 3D Glassmorphism VPN Log Control Panel */
#vpn-filter-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.9) 100%) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(99, 102, 241, 0.2) !important;
    border-radius: 18px !important;
    box-shadow: 0 16px 40px -10px rgba(99, 102, 241, 0.12), 0 4px 16px rgba(0, 0, 0, 0.04) !important;
    margin-bottom: 24px !important;
    overflow: hidden !important;
}

html[data-theme="dark"] #vpn-filter-card {
    background: linear-gradient(180deg, rgba(24, 18, 55, 0.95) 0%, rgba(18, 12, 42, 0.9) 100%) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4) !important;
}

.vpn-filter-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) !important;
    gap: 16px 20px !important;
    align-items: flex-end !important;
    width: 100% !important;
}

.vpn-filter-grid .form-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
}

.vpn-filter-grid label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #475569 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    margin-bottom: 0 !important;
}

html[data-theme="dark"] .vpn-filter-grid label {
    color: rgba(255, 255, 255, 0.7) !important;
}

.vpn-filter-grid .form-control {
    font-size: 13px !important;
    font-weight: 600 !important;
    padding: 9px 13px !important;
    border: 1.5px solid #E2E8F0 !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    color: #1E293B !important;
    outline: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    width: 100% !important;
    box-sizing: border-box !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02) !important;
}

html[data-theme="dark"] .vpn-filter-grid .form-control {
    background: rgba(30, 24, 60, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #ffffff !important;
}

.vpn-filter-grid .form-control:hover {
    border-color: #A5B4FC !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.08) !important;
}

.vpn-filter-grid .form-control:focus {
    border-color: #6366F1 !important;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18) !important;
}

.vpn-btn-apply {
    background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 13.5px !important;
    padding: 10px 24px !important;
    border-radius: 12px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    width: 100% !important;
}

.vpn-btn-apply:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.45) !important;
    color: #ffffff !important;
}

.vpn-btn-export {
    background: #F1F5F9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    padding: 9px 18px !important;
    border-radius: 12px !important;
    border: 1px solid #CBD5E1 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
}

.vpn-btn-export:hover {
    background: #E2E8F0 !important;
    color: #1E293B !important;
}

/* 3D KPI Stats Grid */
.vpn-stats-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.vpn-stat-card {
    background: #ffffff !important;
    border: 1px solid #E2E8F0 !important;
    border-radius: 16px !important;
    padding: 18px 22px !important;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.03) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

html[data-theme="dark"] .vpn-stat-card {
    background: rgba(20, 14, 45, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

.vpn-stat-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 24px rgba(99, 102, 241, 0.15) !important;
    border-color: rgba(99, 102, 241, 0.3) !important;
}

.vpn-stat-card.stat-today { border-top: 3px solid #DC2626 !important; }
.vpn-stat-card.stat-30days { border-top: 3px solid #EA580C !important; }
.vpn-stat-card.stat-type { border-top: 3px solid #7C3AED !important; }

.vpn-stat-label {
    font-size: 11px !important;
    font-weight: 800 !important;
    color: #64748B !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    margin-bottom: 4px !important;
}

html[data-theme="dark"] .vpn-stat-label {
    color: rgba(255, 255, 255, 0.65) !important;
}

.vpn-stat-value {
    font-size: 28px !important;
    font-weight: 800 !important;
    color: #0F172A !important;
    line-height: 1.1 !important;
}

html[data-theme="dark"] .vpn-stat-value {
    color: #ffffff !important;
}
</style>

<!-- Summary Cards Grid -->
<div class="vpn-stats-grid">
    <div class="vpn-stat-card stat-today">
        <div class="vpn-stat-label">Blocked Today</div>
        <div class="vpn-stat-value" style="color:#DC2626"><?= number_format((int)($todayBlocked['cnt'] ?? 0)) ?></div>
    </div>
    <div class="vpn-stat-card stat-30days">
        <div class="vpn-stat-label">Last 30 Days</div>
        <div class="vpn-stat-value" style="color:#EA580C"><?= number_format((int)($totalBlocked['cnt'] ?? 0)) ?></div>
    </div>
    <?php foreach ($typeCounts as $tc): ?>
    <div class="vpn-stat-card stat-type">
        <div class="vpn-stat-label"><?= Helpers::e($tc['detection_type']) ?></div>
        <div class="vpn-stat-value" style="color:#7C3AED"><?= number_format((int)$tc['cnt']) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3 filter-card filter-open" id="vpn-filter-card">
    <div class="card-body" style="padding: 22px 24px">
        <form method="GET" action="/affiliate_manager/vpn-log">
            <div class="vpn-filter-grid">
                <div class="form-group">
                    <label>IP Address</label>
                    <input type="text" name="q_ip" class="form-control" placeholder="e.g. 192.168." value="<?= Helpers::e($qIp) ?>">
                </div>
                <div class="form-group">
                    <label>Affiliate</label>
                    <input type="text" name="q_aff" class="form-control" placeholder="Name or ID" value="<?= Helpers::e($qAff) ?>">
                </div>
                <div class="form-group">
                    <label>Detection Type</label>
                    <select name="q_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="Proxy"          <?= $qType === 'Proxy'          ? 'selected' : '' ?>>Proxy</option>
                        <option value="VPN/Hosting"    <?= $qType === 'VPN/Hosting'    ? 'selected' : '' ?>>VPN/Hosting</option>
                        <option value="VPN"            <?= $qType === 'VPN'            ? 'selected' : '' ?>>VPN (FraudIQ)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= Helpers::e($dateFrom) ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= Helpers::e($dateTo) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="vpn-btn-apply">Filter</button>
                </div>
                <?php if ($qIp || $qAff || $qType || $dateFrom || $dateTo): ?>
                <div class="form-group">
                    <a href="/affiliate_manager/vpn-log" class="vpn-btn-export" style="text-align:center;text-decoration:none">Clear</a>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <button type="submit" name="export" value="1" class="vpn-btn-export">&#128190; Export CSV</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card" style="border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,0.03);overflow:hidden">
    <div class="card-header" style="background:#f8fafc;padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center">
        <span class="card-title" style="font-weight:800;font-size:14px;color:#1e293b">Blocked Attempts <span style="font-weight:400;color:#94A3B8;font-size:13px">(showing latest 1,000)</span></span>
        <form method="POST" onsubmit="return confirm('Delete log entries older than 30 days for your assigned affiliates?')">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="clear_log">
            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:12px;border-radius:10px;padding:6px 14px;font-weight:600">&#128465; Clear Old Entries (&gt;30 days)</button>
        </form>
    </div>
    <div class="table-wrap">
        <?php if (empty($logs)): ?>
        <div style="text-align:center;padding:60px 24px;color:#94A3B8">
            <div style="font-size:36px;margin-bottom:10px">&#128737;</div>
            <div style="font-size:15px;font-weight:600;margin-bottom:4px">No blocked attempts found</div>
            <div style="font-size:13px">
                No VPN or proxy traffic detected for your assigned affiliates.
            </div>
        </div>
        <?php else: ?>
        <table style="font-size:13px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date &amp; Time</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>IP Address</th>
                    <th>Country</th>
                    <th>Detection Type</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="color:#94A3B8;font-size:11px"><?= (int)$log['id'] ?></td>
                <td style="white-space:nowrap;font-size:12px"><?= Helpers::e($log['blocked_at']) ?></td>
                <td>
                    <?php 
                    $affId = !empty($log['effective_affiliate_id']) ? (int)$log['effective_affiliate_id'] : (!empty($log['affiliate_id']) ? (int)$log['affiliate_id'] : null);
                    $affName = !empty($log['aff_name']) ? trim($log['aff_name']) : '';
                    $affCode = !empty($log['affiliate_code']) ? trim($log['affiliate_code']) : '';
                    ?>
                    <?php if ($affId): ?>
                    <a href="/affiliate_manager/affiliates?q=<?= $affId ?>" style="color:#4F46E5;font-weight:600;text-decoration:none">
                        <?= Helpers::e($affName !== '' ? $affName : ('Affiliate #' . $affId)) ?>
                    </a>
                    <div style="font-size:11px;color:#94A3B8">
                        <?= Helpers::e($affCode !== '' ? ($affCode . ' (ID: ' . $affId . ')') : ('ID: ' . $affId)) ?>
                    </div>
                    <?php elseif ($affName !== '' || $affCode !== ''): ?>
                    <div style="font-weight:600"><?= Helpers::e($affName !== '' ? $affName : 'Unknown Affiliate') ?></div>
                    <?php if ($affCode !== ''): ?>
                    <div style="font-size:11px;color:#94A3B8"><?= Helpers::e($affCode) ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    $slId   = !empty($log['effective_smartlink_id']) ? (int)$log['effective_smartlink_id'] : (!empty($log['smartlink_id']) ? (int)$log['smartlink_id'] : null);
                    $slName = !empty($log['effective_smartlink_name']) ? trim($log['effective_smartlink_name']) : (!empty($log['smartlink_name']) ? trim($log['smartlink_name']) : '');
                    $offId  = !empty($log['offer_id']) ? (int)$log['offer_id'] : null;
                    $offName = !empty($log['offer_name']) ? trim($log['offer_name']) : (!empty($log['db_offer_name']) ? trim($log['db_offer_name']) : '');
                    ?>
                    <?php if ($slId || $slName !== ''): ?>
                    <div style="display:inline-flex;align-items:center;gap:4px">
                        <span style="background:#F0FDF4;color:#166534;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;border:1px solid #BBF7D0">SmartLink</span>
                        <span style="font-weight:600;color:#0F172A"><?= Helpers::e($slName !== '' ? $slName : ('SmartLink #' . $slId)) ?></span>
                    </div>
                    <?php if ($slId): ?>
                    <div style="font-size:11px;color:#64748B">SmartLink ID: <?= $slId ?></div>
                    <?php endif; ?>
                    <?php if ($offName !== '' || $offId): ?>
                    <div style="font-size:11px;color:#94A3B8;margin-top:2px">
                        Selected Offer: <?= Helpers::e($offName !== '' ? $offName : ('Offer #' . $offId)) ?> <?= $offId ? '(ID: '.$offId.')' : '' ?>
                    </div>
                    <?php endif; ?>
                    <?php elseif ($offName !== '' || $offId): ?>
                    <div style="font-weight:600"><?= Helpers::e($offName !== '' ? $offName : ('Offer #' . $offId)) ?></div>
                    <?php if ($offId): ?>
                    <div style="font-size:11px;color:#94A3B8">ID: <?= $offId ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <code style="font-size:12px;background:#F1F5F9;padding:2px 6px;border-radius:4px"><?= Helpers::e($log['ip_address']) ?></code>
                </td>
                <td style="font-size:12px;font-weight:600"><?= $log['country'] ? Helpers::e(strtoupper($log['country'])) : '—' ?></td>
                <td>
                    <?php
                    $typeColors = [
                        'Proxy'       => ['bg' => '#FFF7ED', 'color' => '#C2410C', 'border' => '#FED7AA'],
                        'VPN/Hosting' => ['bg' => '#EEF2FF', 'color' => '#4338CA', 'border' => '#C7D2FE'],
                        'VPN'         => ['bg' => '#FDF4FF', 'color' => '#7E22CE', 'border' => '#E9D5FF'],
                    ];
                    $tc = $typeColors[$log['detection_type']] ?? ['bg' => '#F1F5F9', 'color' => '#475569', 'border' => '#CBD5E1'];
                    ?>
                    <span style="display:inline-block;background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;border:1px solid <?= $tc['border'] ?>;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700">
                        <?= Helpers::e($log['detection_type']) ?>
                    </span>
                </td>
                <td style="max-width:220px">
                    <div style="font-size:11px;color:#64748B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($log['user_agent'] ?? '') ?>">
                        <?= Helpers::e(substr($log['user_agent'] ?? '—', 0, 80)) ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
