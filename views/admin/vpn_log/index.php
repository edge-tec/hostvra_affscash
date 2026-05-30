<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; VPN &amp; Proxy Blocked Log</h1>
        <p>All tracking clicks blocked due to VPN or proxy detection</p>
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
        <a href="/admin/settings?tab=vpn_detection" class="btn btn-secondary btn-sm">&#9881; Settings</a>
    </div>
</div>

<?php foreach (Helpers::getFlash() as $f): ?>
<div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'error' ?>" data-auto-hide><?= Helpers::e($f['message']) ?></div>
<?php endforeach; ?>

<?php if (!$vpnEnabled): ?>
<div class="alert alert-warning" style="margin-bottom:16px">
    &#9888; VPN &amp; Proxy detection is currently <strong>disabled</strong>. Traffic is not being blocked or logged.
    <a href="/admin/settings?tab=vpn_detection" style="color:#92400E;font-weight:700;margin-left:8px">Enable it in Settings &rarr;</a>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:20px">
    <div class="card" style="padding:16px 20px">
        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Blocked Today</div>
        <div style="font-size:28px;font-weight:800;color:#DC2626"><?= number_format((int)($todayBlocked['cnt'] ?? 0)) ?></div>
    </div>
    <div class="card" style="padding:16px 20px">
        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Last 30 Days</div>
        <div style="font-size:28px;font-weight:800;color:#EA580C"><?= number_format((int)($totalBlocked['cnt'] ?? 0)) ?></div>
    </div>
    <?php foreach ($typeCounts as $tc): ?>
    <div class="card" style="padding:16px 20px">
        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px"><?= Helpers::e($tc['detection_type']) ?></div>
        <div style="font-size:28px;font-weight:800;color:#7C3AED"><?= number_format((int)$tc['cnt']) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:14px 16px">
        <form method="GET" action="/admin/vpn-log" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">IP Address</label>
                <input type="text" name="q_ip" class="form-control" placeholder="e.g. 192.168." value="<?= Helpers::e($qIp) ?>" style="width:160px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Affiliate</label>
                <input type="text" name="q_aff" class="form-control" placeholder="Name or ID" value="<?= Helpers::e($qAff) ?>" style="width:150px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Detection Type</label>
                <select name="q_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="Proxy"          <?= $qType === 'Proxy'          ? 'selected' : '' ?>>Proxy</option>
                    <option value="VPN/Hosting"    <?= $qType === 'VPN/Hosting'    ? 'selected' : '' ?>>VPN/Hosting</option>
                    <option value="VPN"            <?= $qType === 'VPN'            ? 'selected' : '' ?>>VPN (FraudIQ)</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= Helpers::e($dateFrom) ?>">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= Helpers::e($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($qIp || $qAff || $qType || $dateFrom || $dateTo): ?>
            <a href="/admin/vpn-log" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">Blocked Attempts <span style="font-weight:400;color:#94A3B8;font-size:13px">(showing latest 1,000)</span></span>
        <form method="POST" onsubmit="return confirm('Delete all log entries older than 30 days?')">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="clear_log">
            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:12px">&#128465; Clear Old Entries (&gt;30 days)</button>
        </form>
    </div>
    <div class="table-wrap">
        <?php if (empty($logs)): ?>
        <div style="text-align:center;padding:60px 24px;color:#94A3B8">
            <div style="font-size:36px;margin-bottom:10px">&#128737;</div>
            <div style="font-size:15px;font-weight:600;margin-bottom:4px">No blocked attempts found</div>
            <div style="font-size:13px">
                <?= $vpnEnabled ? 'No VPN/proxy traffic has been detected yet.' : 'Enable VPN detection in Settings to start logging.' ?>
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
                    <?php if ($log['affiliate_id']): ?>
                    <a href="/admin/affiliates/<?= (int)$log['affiliate_id'] ?>/view"
                       style="color:#4F46E5;font-weight:600;text-decoration:none">
                        <?= Helpers::e($log['aff_name'] ?? 'Affiliate #' . $log['affiliate_id']) ?>
                    </a>
                    <div style="font-size:11px;color:#94A3B8"><?= Helpers::e($log['affiliate_code'] ?? '') ?></div>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($log['offer_id']): ?>
                    <div style="font-weight:600"><?= Helpers::e($log['offer_name'] ?? 'Offer #' . $log['offer_id']) ?></div>
                    <div style="font-size:11px;color:#94A3B8">ID: <?= (int)$log['offer_id'] ?></div>
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

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
