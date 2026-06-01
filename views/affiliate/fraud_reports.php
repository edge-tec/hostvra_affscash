<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header" style="margin-bottom:20px">
    <div>
        <h1 style="margin:0;font-size:22px;font-weight:800;color:#1E293B">🛡 My Fraud Reports</h1>
        <p style="margin:4px 0 0;color:#64748B;font-size:14px">Your personalized fraud activity reports — Click and Conversion reports are separate.</p>
    </div>
</div>

<!-- Tab navigation -->
<?php $activeTab = $_GET['tab'] ?? 'clicks'; ?>
<div style="display:flex;gap:4px;background:#F1F5F9;padding:4px;border-radius:8px;margin-bottom:20px;width:fit-content">
    <a href="?tab=clicks" style="padding:8px 20px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;<?= $activeTab === 'clicks' ? 'background:#fff;color:#4F46E5;box-shadow:0 1px 3px rgba(0,0,0,.1)' : 'color:#64748B' ?>">
        🖱 Fraud Click Reports
    </a>
    <a href="?tab=conversions" style="padding:8px 20px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;<?= $activeTab === 'conversions' ? 'background:#fff;color:#DC2626;box-shadow:0 1px 3px rgba(0,0,0,.1)' : 'color:#64748B' ?>">
        💰 Fraud Conversion Reports
    </a>
</div>

<?php
// ── Shared pagination ─────────────────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// ─────────────────────────────────────────────────────────────────────────────
// CLICK REPORTS TAB
// ─────────────────────────────────────────────────────────────────────────────
if ($activeTab === 'clicks'):
    $totalRows = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM fraud_report_logs WHERE affiliate_id=? AND report_type='click'",
        [$affId]
    )['c'] ?? 0);
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;

    $reports = Database::fetchAll(
        "SELECT * FROM fraud_report_logs WHERE affiliate_id=? AND report_type='click'
         ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
        [$affId]
    ) ?: [];

    // Aggregate for the summary card (last 30 days)
    $agg = Database::fetchOne(
        "SELECT COUNT(*) AS total_reports,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.total_clicks')),0)    AS total_clicks,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.fraud_clicks')),0)    AS fraud_clicks,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.vpn_clicks')),0)      AS vpn_clicks,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.proxy_clicks')),0)    AS proxy_clicks,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.bot_clicks')),0)      AS bot_clicks,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.datacenter_clicks')),0) AS datacenter_clicks
         FROM fraud_report_logs
         WHERE affiliate_id=? AND report_type='click' AND created_at >= NOW() - INTERVAL 30 DAY",
        [$affId]
    ) ?: [];
?>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
    <?php
    $cards = [
        ['Total Clicks',     number_format($agg['total_clicks'] ?? 0),       '#4F46E5', '#EEF2FF'],
        ['Fraud Clicks',     number_format($agg['fraud_clicks'] ?? 0),        '#EF4444', '#FEF2F2'],
        ['VPN Traffic',      number_format($agg['vpn_clicks'] ?? 0),          '#F59E0B', '#FFF7ED'],
        ['Proxy Traffic',    number_format($agg['proxy_clicks'] ?? 0),        '#F59E0B', '#FFF7ED'],
        ['Bot Traffic',      number_format($agg['bot_clicks'] ?? 0),          '#DC2626', '#FEF2F2'],
        ['Datacenter',       number_format($agg['datacenter_clicks'] ?? 0),   '#6366F1', '#EEF2FF'],
    ];
    foreach ($cards as [$label, $val, $color, $bg]):
    ?>
    <div style="background:<?= $bg ?>;border-radius:10px;padding:14px;text-align:center">
        <div style="font-size:11px;font-weight:600;color:<?= $color ?>;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em"><?= $label ?></div>
        <div style="font-size:22px;font-weight:800;color:#1E293B"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($reports)): ?>
<div style="text-align:center;padding:48px 20px;color:#94A3B8">
    <div style="font-size:48px;margin-bottom:12px">🖱</div>
    <div style="font-size:16px;font-weight:600;color:#64748B;margin-bottom:6px">No Click Reports Yet</div>
    <div style="font-size:13px">Fraud Click Reports are generated automatically by the system and will appear here once available.</div>
</div>
<?php else: ?>
<!-- Report cards -->
<?php foreach ($reports as $rep):
    $d       = json_decode($rep['data_json'], true) ?? [];
    $quality = (float)($d['click_quality'] ?? 100);
    $qColor  = $quality >= 80 ? '#10B981' : ($quality >= 50 ? '#F59E0B' : '#EF4444');
?>
<div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:16px;overflow:hidden">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#EEF2FF 0%,#E0E7FF 100%);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #C7D2FE">
        <div>
            <div style="font-size:14px;font-weight:700;color:#3730A3">🖱 Fraud Click Report</div>
            <div style="font-size:12px;color:#6366F1;margin-top:2px">
                <?= date('M d, Y H:i', strtotime($rep['period_from'])) ?> → <?= date('M d, Y H:i', strtotime($rep['period_to'])) ?>
            </div>
        </div>
        <div style="text-align:right">
            <div style="font-size:24px;font-weight:800;color:<?= $qColor ?>"><?= $quality ?>%</div>
            <div style="font-size:11px;color:#6366F1;font-weight:600">Click Quality</div>
        </div>
    </div>
    <!-- Metrics grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:0;padding:0">
        <?php
        $metrics = [
            ['Total Clicks',   $d['total_clicks'] ?? 0,     '#1E293B'],
            ['Fraud Clicks',   $d['fraud_clicks'] ?? 0,     '#EF4444'],
            ['Blocked Clicks', $d['blocked_clicks'] ?? 0,   '#EF4444'],
            ['VPN Traffic',    $d['vpn_clicks'] ?? 0,       '#F59E0B'],
            ['Proxy Traffic',  $d['proxy_clicks'] ?? 0,     '#F59E0B'],
            ['Bot Traffic',    $d['bot_clicks'] ?? 0,       '#DC2626'],
            ['Datacenter',     $d['datacenter_clicks'] ?? 0,'#6366F1'],
        ];
        foreach ($metrics as [$label, $val, $color]):
        ?>
        <div style="padding:14px 16px;border-right:1px solid #F1F5F9;border-bottom:1px solid #F1F5F9">
            <div style="font-size:11px;color:#94A3B8;font-weight:600;margin-bottom:3px;text-transform:uppercase;letter-spacing:.04em"><?= $label ?></div>
            <div style="font-size:18px;font-weight:800;color:<?= $color ?>"><?= number_format($val) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Footer -->
    <div style="padding:10px 20px;background:#F8FAFC;border-top:1px solid #F1F5F9;font-size:11px;color:#94A3B8">
        Generated <?= date('M d, Y \a\t H:i', strtotime($rep['created_at'])) ?>
        <?php if ($rep['email_sent']): ?>&nbsp;|&nbsp;<span style="color:#10B981">✓ Email sent</span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?tab=clicks&page=<?= $i ?>" style="padding:6px 12px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;<?= $i === $page ? 'background:#4F46E5;color:#fff' : 'background:#F1F5F9;color:#64748B' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; // empty check ?>

<?php
// ─────────────────────────────────────────────────────────────────────────────
// CONVERSION REPORTS TAB
// ─────────────────────────────────────────────────────────────────────────────
elseif ($activeTab === 'conversions'):
    $totalRows = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM fraud_report_logs WHERE affiliate_id=? AND report_type='conversion'",
        [$affId]
    )['c'] ?? 0);
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;

    $reports = Database::fetchAll(
        "SELECT * FROM fraud_report_logs WHERE affiliate_id=? AND report_type='conversion'
         ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
        [$affId]
    ) ?: [];

    $agg = Database::fetchOne(
        "SELECT COUNT(*) AS total_reports,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.total_conversions')),0)   AS total_conversions,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.fraud_conversions')),0)   AS fraud_conversions,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.invalid_leads')),0)       AS invalid_leads,
                COALESCE(SUM(JSON_EXTRACT(data_json,'$.suspicious_activity')),0) AS suspicious_activity
         FROM fraud_report_logs
         WHERE affiliate_id=? AND report_type='conversion' AND created_at >= NOW() - INTERVAL 30 DAY",
        [$affId]
    ) ?: [];

    $overallFraudRate = ($agg['total_conversions'] ?? 0) > 0
        ? round(($agg['fraud_conversions'] / $agg['total_conversions']) * 100, 2)
        : 0;
?>
<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px">
    <?php
    $cards = [
        ['Total Conversions', number_format($agg['total_conversions'] ?? 0), '#4F46E5', '#EEF2FF'],
        ['Fraud Conversions', number_format($agg['fraud_conversions'] ?? 0), '#EF4444', '#FEF2F2'],
        ['Fraud Rate',        $overallFraudRate . '%',                        '#EF4444', '#FEF2F2'],
        ['Invalid Leads',     number_format($agg['invalid_leads'] ?? 0),     '#F59E0B', '#FFF7ED'],
        ['Suspicious Activity', number_format($agg['suspicious_activity'] ?? 0), '#DC2626', '#FEF2F2'],
    ];
    foreach ($cards as [$label, $val, $color, $bg]):
    ?>
    <div style="background:<?= $bg ?>;border-radius:10px;padding:14px;text-align:center">
        <div style="font-size:11px;font-weight:600;color:<?= $color ?>;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em"><?= $label ?></div>
        <div style="font-size:22px;font-weight:800;color:#1E293B"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($reports)): ?>
<div style="text-align:center;padding:48px 20px;color:#94A3B8">
    <div style="font-size:48px;margin-bottom:12px">💰</div>
    <div style="font-size:16px;font-weight:600;color:#64748B;margin-bottom:6px">No Conversion Reports Yet</div>
    <div style="font-size:13px">Fraud Conversion Reports are generated automatically and will appear here once available.</div>
</div>
<?php else: ?>
<?php foreach ($reports as $rep):
    $d       = json_decode($rep['data_json'], true) ?? [];
    $quality = (float)($d['conversion_quality'] ?? 100);
    $qColor  = $quality >= 80 ? '#10B981' : ($quality >= 50 ? '#F59E0B' : '#EF4444');
?>
<div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:16px;overflow:hidden">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#FFF7ED 0%,#FEE2E2 100%);padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #FED7AA">
        <div>
            <div style="font-size:14px;font-weight:700;color:#92400E">💰 Fraud Conversion Report</div>
            <div style="font-size:12px;color:#B45309;margin-top:2px">
                <?= date('M d, Y H:i', strtotime($rep['period_from'])) ?> → <?= date('M d, Y H:i', strtotime($rep['period_to'])) ?>
            </div>
        </div>
        <div style="text-align:right">
            <div style="font-size:24px;font-weight:800;color:<?= $qColor ?>"><?= $quality ?>%</div>
            <div style="font-size:11px;color:#D97706;font-weight:600">Conv. Quality</div>
        </div>
    </div>
    <!-- Metrics grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:0;padding:0">
        <?php
        $metrics = [
            ['Total Conversions',    $d['total_conversions'] ?? 0,   '#1E293B'],
            ['Fraud Conversions',    $d['fraud_conversions'] ?? 0,   '#EF4444'],
            ['Fraud Rate',           ($d['fraud_rate'] ?? 0) . '%',  '#EF4444'],
            ['Invalid Leads',        $d['invalid_leads'] ?? 0,       '#F59E0B'],
            ['Suspicious Activity',  $d['suspicious_activity'] ?? 0, '#DC2626'],
        ];
        foreach ($metrics as [$label, $val, $color]):
        ?>
        <div style="padding:14px 16px;border-right:1px solid #F1F5F9;border-bottom:1px solid #F1F5F9">
            <div style="font-size:11px;color:#94A3B8;font-weight:600;margin-bottom:3px;text-transform:uppercase;letter-spacing:.04em"><?= $label ?></div>
            <div style="font-size:18px;font-weight:800;color:<?= $color ?>"><?= is_numeric($val) ? number_format($val) : $val ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Conversion quality analysis bar -->
    <div style="padding:12px 20px;background:#F8FAFC;border-top:1px solid #F1F5F9">
        <div style="font-size:11px;color:#64748B;font-weight:600;margin-bottom:6px">Conversion Quality Analysis</div>
        <div style="background:#E2E8F0;border-radius:999px;height:8px;overflow:hidden">
            <div style="background:<?= $qColor ?>;width:<?= $quality ?>%;height:100%;border-radius:999px;transition:width .6s ease"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#94A3B8;margin-top:4px">
            <span>0% (All Fraud)</span><span>100% (No Fraud)</span>
        </div>
    </div>
    <!-- Footer -->
    <div style="padding:10px 20px;border-top:1px solid #F1F5F9;font-size:11px;color:#94A3B8">
        Generated <?= date('M d, Y \a\t H:i', strtotime($rep['created_at'])) ?>
        <?php if ($rep['email_sent']): ?>&nbsp;|&nbsp;<span style="color:#10B981">✓ Email sent</span><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:6px;margin-top:16px">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?tab=conversions&page=<?= $i ?>" style="padding:6px 12px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;<?= $i === $page ? 'background:#DC2626;color:#fff' : 'background:#F1F5F9;color:#64748B' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; // empty check ?>
<?php endif; // tab check ?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
