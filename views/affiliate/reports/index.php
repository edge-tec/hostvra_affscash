<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<?php $hideFraudRejected = (string)(Config::get('config', 'conversion.hide_fraud_rejected_reports') ?? '0') === '1'; ?>

<div class="page-header">
    <div><h1>My Reports</h1><p>Performance, clicks &amp; conversion breakdown</p></div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#11123; Export CSV</a>
</div>

<?php
$tabs = [
    'day'        => ['icon'=>'&#128197;', 'label'=>'By Day'],
    'offer'      => ['icon'=>'&#128200;', 'label'=>'By Offer'],
    'country'    => ['icon'=>'&#127760;', 'label'=>'By Country'],
    'sub'        => ['icon'=>'&#128279;', 'label'=>'By Aff Sub'],
    'click'      => ['icon'=>'&#128432;', 'label'=>'Click Log'],
    'conversion' => ['icon'=>'&#9989;',   'label'=>'Conversions'],
    'sl_report'  => ['icon'=>'&#128279;', 'label'=>'SmartLink Report'],
];
?>

<!-- Tab bar -->
<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:16px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <?php foreach ($tabs as $key => $t):
        $isActive = $tab === $key;
        $qs = http_build_query(array_merge($_GET, ['tab'=>$key, 'export'=>null]));
        $qs = preg_replace('/export=[^&]*&?/', '', $qs);
    ?>
    <a href="?<?= $qs ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;border:1px solid <?= $isActive?'#E2E8F0':'transparent' ?>;border-bottom:<?= $isActive?'2px solid #fff':'2px solid transparent' ?>;margin-bottom:-2px;background:<?= $isActive?'#fff':'transparent' ?>;color:<?= $isActive?'var(--primary)':'var(--text-muted)' ?>;text-decoration:none">
        <?= $t['icon'] ?> <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="card mb-3 filter-card" id="aff-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span>🔍 Filters</span>
        <span class="filter-toggle-icon">▼</span>
    </button>
    <div class="card-body">
        <form method="GET" id="aff-report-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <input type="hidden" name="tab" value="<?= Helpers::e($tab) ?>">
            <?php $drpFromId='aff-from'; $drpToId='aff-to'; $drpFormId='aff-report-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>

            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="aff-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="aff-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>

            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All My Offers</option>
                    <?php foreach ($myOffers as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $offerId==$o['id']?'selected':'' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Country</label>
                <select name="country" class="form-control">
                    <option value="">All Countries</option>
                    <?php foreach ($countryList as $c): ?>
                    <option value="<?= Helpers::e($c['country']) ?>" <?= $country===$c['country']?'selected':'' ?>><?= Helpers::flag($c['country']) ?> <?= Helpers::e($c['country']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Aff Sub 1</label>
                <input type="text" name="sub1" class="form-control" value="<?= Helpers::e($sub1) ?>" placeholder="sub1…" style="width:110px">
            </div>

            <?php if ($tab === 'sl_report' && !empty($mySlList)): ?>
            <div class="form-group mb-0">
                <label>SmartLink</label>
                <select name="sl_id" class="form-control">
                    <option value="">All SmartLinks</option>
                    <?php foreach ($mySlList as $sl): ?>
                    <option value="<?= $sl['id'] ?>" <?= $slId==$sl['id']?'selected':'' ?>><?= Helpers::e($sl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['click','conversion','sl_report'])): ?>
            <div class="form-group mb-0">
                <label>Click ID</label>
                <input type="text" name="click_id" class="form-control" value="<?= Helpers::e($clickId ?? '') ?>" placeholder="Search Click ID..." style="width:130px">
            </div>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>><?= number_format($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($tab === 'click'): ?>
            <div class="form-group mb-0">
                <label>Click Type</label>
                <select name="click_filter" class="form-control">
                    <option value="all"       <?= ($clickFilter ?? 'all') === 'all'       ? 'selected' : '' ?>>All Clicks</option>
                    <option value="converted" <?= ($clickFilter ?? 'all') === 'converted' ? 'selected' : '' ?>>Converted Clicks</option>
                    <option value="approved"  <?= ($clickFilter ?? 'all') === 'approved'  ? 'selected' : '' ?>>Approved Conversions</option>
                </select>
            </div>
            <?php endif; ?>

            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>


<?php /* ═══════════════════ PERFORMANCE TABS (day / offer / country / sub) ═══════════════════ */ ?>
<?php if (in_array($tab, ['day','offer','country','sub'])): ?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format($totals['clicks']) ?></div><div class="stat-sub">Unique: <?= number_format($totals['uclicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totals['conv']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($totals['approved']) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <div class="stat-card"><div class="stat-label">Rejected</div><div class="stat-value" style="color:var(--danger)"><?= number_format($totals['rejected']) ?></div></div>
    <div class="stat-card" title="Conversions with IPQualityScore (IPQS) fraud_score ≥ 60. Visibility-only overlay; does not affect payout."><div class="stat-label">Fraud conversion</div><div class="stat-value" style="color:#DC2626"><?= number_format($totals['fraud'] ?? 0) ?></div><div class="stat-sub">Total High risk fraud</div></div>
    <?php endif; ?>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value" style="color:var(--secondary)">$<?= number_format($totals['payout'],2) ?></div><div class="stat-sub">Approved only</div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Report — <?= $tabs[$tab]['label'] ?></span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap">
        <table id="tbl-perf">
            <thead>
                <tr>
                    <th><?= ['day'=>'Date','offer'=>'Offer','country'=>'Country','sub'=>'Aff Sub 1'][$tab] ?></th>
                    <th>Clicks</th><th>Unique</th><th>Conv.</th><th>Approved</th>
                    <?php if (!$hideFraudRejected): ?>
                    <th>Rejected</th>
                    <th title="Conversions with IPQualityScore (IPQS) fraud_score ≥ 60 — real-time">Fraud conversion</th>
                    <?php endif; ?>
                    <th>CR%</th><th>EPC</th><th>Payout</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="<?= $hideFraudRejected ? 8 : 10 ?>" class="text-center text-muted" style="padding:32px">No data for this period</td></tr>
            <?php else: foreach ($rows as $row):
                $cr  = $row['clicks'] > 0 ? round($row['conv']/$row['clicks']*100,2) : 0;
                $epc = $row['clicks'] > 0 ? round($row['payout']/$row['clicks'],4) : 0;
                $_fraudN = (int)($row['fraud'] ?? 0);
            ?>
            <tr>
                <td class="fw-bold"><?php
                    if ($tab === 'country' && !empty($row['label'])):
                        ?><img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($row['label'])) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:4px"><?php
                    endif;
                    echo Helpers::e($row['label'] ?: '—');
                ?></td>
                <td><?= number_format($row['clicks']) ?></td>
                <td><?= number_format($row['uclicks']) ?></td>
                <td><?= number_format($row['conv']) ?></td>
                <td><?= number_format($row['approved']) ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td><?= number_format($row['rejected']) ?></td>
                <td><?php if ($_fraudN > 0): ?><span style="color:#DC2626;font-weight:700"><?= number_format($_fraudN) ?></span><?php else: ?>0<?php endif; ?></td>
                <?php endif; ?>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td class="fw-bold">$<?= number_format($row['payout'],2) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php /* ═══════════════════ CLICK LOG TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'click'): ?>

<?php $totalClicks = count($clicks); ?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card">
        <div class="stat-label">Total Clicks</div>
        <div class="stat-value"><?= number_format($totalClicks) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Approved Payout</div>
        <div class="stat-value" style="color:var(--secondary)">$<?= number_format($clickTotalPayout, 2) ?></div>
        <div class="stat-sub">Approved conversions only</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Period</div>
        <div class="stat-value" style="font-size:15px"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Click Log</span>
        <span class="text-muted text-sm"><?= number_format($totalClicks) ?> rows</span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-clicks" style="min-width:1600px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>CLICK ID</th>
                    <th>SUB 1</th>
                    <th>SUB 2</th>
                    <th>SUB 3</th>
                    <th>SOURCE</th>
                    <th>OS</th>
                    <th>BROWSER</th>
                    <th>DEVICE</th>
                    <th>IP ADDRESS</th>
                    <th>COUNTRY</th>
                    <th>CITY</th>
                    <th>CONV STATUS</th>
                    <th>PAYOUT</th>
                    <th>CLICK TIME</th>
                    <th>CONV TIME</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clicks)): ?>
            <tr><td colspan="16" class="text-center text-muted" style="padding:32px">No clicks found for selected filters</td></tr>
            <?php else: foreach ($clicks as $c):
                $convStatus = $c['conv_status'] ?? null;
                $convPayout = $convStatus === 'approved' ? (float)$c['conv_payout'] : 0;
                $statusBadge = match($convStatus) {
                    'approved' => '<span class="badge badge-success">Approved</span>',
                    'pending'  => '<span class="badge badge-warning">Pending</span>',
                    'rejected' => '<span class="badge badge-danger">Rejected</span>',
                    default    => '<span class="badge badge-muted" style="color:#94A3B8">No Conv.</span>',
                };
                $sourceLabel = $c['source'] ?: ($c['referer'] ? (parse_url($c['referer'], PHP_URL_HOST) ?: $c['referer']) : '—');
            ?>
            <tr>
                <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($c['offer_name'] ?: '— Custom URL —') ?></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 5px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1'] ?: '—') ?></td>
                <td><?= Helpers::e($c['sub2'] ?: '—') ?></td>
                <td><?= Helpers::e($c['sub3'] ?: '—') ?></td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="<?= Helpers::e($c['source'] ?: ($c['referer'] ?? '')) ?>"><?= Helpers::e($sourceLabel) ?></td>
                <td><?= Helpers::e($c['os'] ?: '—') ?></td>
                <td><?= Helpers::e($c['browser'] ?: '—') ?></td>
                <td><?= Helpers::e(ucfirst($c['device_type'] ?? '—')) ?></td>
                <td><?= Helpers::e($c['ip_address']) ?></td>
                <td>
                    <?php if (!empty($c['country'])): ?>
                    <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png"
                         onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px">
                    <?= Helpers::e($c['country']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($c['city'] ?: '—') ?></td>
                <td>
                    <?= $statusBadge ?>
                    <?php if ($convStatus === 'rejected' && !empty($c['rejection_reason'])):
                        $_rcFull  = (string)$c['rejection_reason'];
                        $_rcShort = mb_strlen($_rcFull) > 60 ? mb_substr($_rcFull, 0, 60) . '…' : $_rcFull;
                    ?>
                    <div style="margin-top:3px;font-size:11px;color:#b91c1c;line-height:1.4;max-width:180px;white-space:normal"
                         title="<?= Helpers::e($_rcFull) ?>">
                        <strong>Reason:</strong> <?= Helpers::e($_rcShort) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="fw-bold" style="color:<?= $convPayout > 0 ? 'var(--secondary)' : '#94A3B8' ?>">
                    <?= $convPayout > 0 ? '$'.number_format($convPayout, 2) : '—' ?>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($c['clicked_at'])) ?></td>
                <td class="text-muted" style="white-space:nowrap">
                    <?= $c['conv_time'] ? date('M j, Y H:i', strtotime($c['conv_time'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php /* ═══════════════════ CONVERSIONS TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'conversion'): ?>

<?php
// Only count approved conversions toward total payout
$totPayout = array_sum(array_map(
    fn($r) => $r['status'] === 'approved' ? (float)$r['payout'] : 0.0,
    $convRows
));
$statusCounts = array_count_values(array_column($convRows, 'status'));
?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= number_format(count($convRows)) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($statusCounts['approved'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:#F59E0B"><?= number_format($statusCounts['pending'] ?? 0) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <div class="stat-card"><div class="stat-label">Rejected</div><div class="stat-value" style="color:var(--danger)"><?= number_format($statusCounts['rejected'] ?? 0) ?></div></div>
    <?php endif; ?>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value">$<?= number_format($totPayout, 2) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Conversion Log</span>
        <span class="text-muted text-sm"><?= number_format(count($convRows)) ?> rows · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-conv" style="min-width:2000px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>CLICK ID</th>
                    <th>AFF CLICK ID</th>
                    <th>AFF SUB 1</th>
                    <th>AFF SUB 2</th>
                    <th>AFF SUB 3</th>
                    <th>SOURCE</th>
                    <th>OS NAME</th>
                    <th>BROWSER</th>
                    <th>BROWSER VERSION</th>
                    <th>DEVICE BRAND</th>
                    <th>DEVICE MODEL</th>
                    <th>OS VERSION</th>
                    <th>IP ADDRESS</th>
                    <th>COUNTRY</th>
                    <th>CITY</th>
                    <th>REGION</th>
                    <th>PAYOUT</th>
                    <th>STATUS</th>
                    <th>GOAL</th>
                    <th>TXN ID</th>
                    <th>CONVERT TIME</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($convRows as $r):
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
                // Parse browser version
                $browserVer = '—';
                if (!empty($r['browser']) && !empty($r['user_agent'])) {
                    if (preg_match('/'.preg_quote($r['browser'],'/').'[\/\s]([\d\.]+)/i', $r['user_agent'], $m)) {
                        $browserVer = $m[1];
                    }
                }
                // Parse OS version
                $osVer = '—';
                if (!empty($r['user_agent'])) {
                    if (preg_match('/Windows NT ([\d\.]+)/i', $r['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/Android ([\d\.]+)/i', $r['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/OS ([\d_]+) like/i', $r['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                    elseif (preg_match('/Mac OS X ([\d_\.]+)/i', $r['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                }
                // Parse device brand/model
                $deviceBrand = ucfirst($r['device_type'] ?? '—');
                $deviceModel = '—';
                if (!empty($r['user_agent'])) {
                    $ua = $r['user_agent'];
                    if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $ua, $m)) {
                        $raw = trim($m[1]);
                        $knownBrands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
                        $brand = '';
                        foreach ($knownBrands as $b) {
                            if (stripos($raw, $b) === 0) { $brand = $b; break; }
                        }
                        if ($brand) { $deviceBrand = $brand; $deviceModel = trim(substr($raw, strlen($brand))) ?: $raw; }
                        else { $deviceModel = $raw; }
                    } elseif (stripos($ua, 'iPhone') !== false) {
                        $deviceBrand = 'Apple'; $deviceModel = 'iPhone';
                    } elseif (stripos($ua, 'iPad') !== false) {
                        $deviceBrand = 'Apple'; $deviceModel = 'iPad';
                    } elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS X') !== false) {
                        $deviceBrand = 'Apple'; $deviceModel = 'Mac';
                    } elseif (stripos($ua, 'Windows') !== false) {
                        $deviceBrand = 'PC'; $deviceModel = 'Windows';
                    }
                }
                $sourceHost = $r['referer'] ? (parse_url($r['referer'], PHP_URL_HOST) ?: $r['referer']) : '—';
            ?>
            <tr>
                <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($r['offer_name'] ?: '— Custom URL —') ?></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 5px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1'] ?: '—') ?></td>
                <td><?= Helpers::e($r['sub2'] ?: '—') ?></td>
                <td><?= Helpers::e($r['sub3'] ?: '—') ?></td>
                <td><?= Helpers::e(($r['sub4'] ?? '') ?: '—') ?></td>
                <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($r['referer'] ?? '') ?>"><?= Helpers::e($sourceHost) ?></td>
                <td><?= Helpers::e($r['os'] ?: '—') ?></td>
                <td><?= Helpers::e($r['browser'] ?: '—') ?></td>
                <td><?= Helpers::e($browserVer) ?></td>
                <td><?= Helpers::e($deviceBrand) ?></td>
                <td><?= Helpers::e($deviceModel) ?></td>
                <td><?= Helpers::e($osVer) ?></td>
                <td><?= Helpers::e($r['ip_address'] ?: '—') ?></td>
                <td>
                    <?php if (!empty($r['country'])): ?>
                    <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($r['country'])) ?>.png"
                         onerror="this.style.display='none'"
                         style="vertical-align:middle;margin-right:3px">
                    <?= Helpers::e($r['country']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($r['city'] ?: '—') ?></td>
                <td><?= Helpers::e($r['region'] ?: '—') ?></td>
                <td class="fw-bold">$<?= number_format((float)$r['payout'], 4) ?></td>
                <td>
                    <span class="badge badge-<?= $bm[$r['status']] ?? 'muted' ?>"><?= $r['status'] ?></span>
                    <?php if ($r['status'] === 'rejected' && !empty($r['rejection_reason'])):
                        $_reasonFull  = (string)$r['rejection_reason'];
                        $_reasonShort = mb_strlen($_reasonFull) > 80 ? mb_substr($_reasonFull, 0, 80) . '…' : $_reasonFull;
                    ?>
                    <div style="margin-top:4px;font-size:11px;color:#b91c1c;line-height:1.4;max-width:200px;white-space:normal"
                         title="<?= Helpers::e($_reasonFull) ?>">
                        <strong>Reason:</strong> <?= Helpers::e($_reasonShort) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($r['rejected_at'])): ?>
                    <div style="font-size:10px;color:#94A3B8;margin-top:2px">Rejected <?= Helpers::e(date('M j, H:i', strtotime($r['rejected_at']))) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($r['goal_name'] ?: '—') ?></td>
                <td class="text-sm text-muted"><?= Helpers::e($r['transaction_id'] ?: '—') ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($r['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════ SMARTLINK REPORT TAB (affiliate) ═══════════ */ ?>
<?php elseif ($tab === 'sl_report'): ?>

<?php
$_slTot   = count($slClicks ?? []);
$_slConvs = count($slConversions ?? []);
$_slPay   = array_sum(array_column($slConversions ?? [], 'payout'));
$_slFraud = count(array_filter($slClicks ?? [], fn($r) => $r['is_fraud']));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
    <div class="stat-card"><div class="stat-label">SL Clicks</div><div class="stat-value"><?= number_format($_slTot) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($_slConvs) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR%</div><div class="stat-value"><?= $_slTot>0?round($_slConvs/$_slTot*100,2):0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value">$<?= number_format($_slPay,2) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <div class="stat-card"><div class="stat-label">Fraud Clicks</div><div class="stat-value" style="color:var(--danger)"><?= number_format($_slFraud) ?></div></div>
    <?php endif; ?>
</div>

<!-- SmartLink Clicks table -->
<div class="card mb-3">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">&#128279; SmartLink Clicks</span>
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','export_type'=>'clicks'])) ?>" class="btn btn-secondary btn-sm">&#11123; Export</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-clicks" style="font-size:12px;min-width:1200px">
            <thead><tr>
                <th>SMARTLINK</th><th>OFFER</th><th>CLICK ID</th><th>SUB1</th><th>SUB2</th>
                <th>SOURCE</th>
                <?php if (!$hideFraudRejected): ?><th>FRAUD</th><?php endif; ?>
                <th>OS</th><th>BROWSER</th><th>DEVICE</th>
                <th>IP</th><th>COUNTRY</th><th>CITY</th><th>CONVERTED</th><th>CONV STATUS</th><th>CLICK TIME</th>
            </tr></thead>
            <tbody>
            <?php if (empty($slClicks)): ?>
            <tr><td colspan="<?= $hideFraudRejected ? 15 : 16 ?>" class="text-center text-muted" style="padding:28px">No SmartLink clicks for the selected period</td></tr>
            <?php else: foreach ($slClicks as $c): ?>
            <tr style="<?= $c['is_fraud']?'background:#FFF5F5':'' ?>">
                <td><span style="background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;white-space:nowrap"><?= Helpers::e($c['smartlink_name']) ?></span></td>
                <td style="font-size:11px;white-space:nowrap"><?= Helpers::e($c['offer_name']) ?></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1']?:'—') ?></td>
                <td><?= Helpers::e($c['sub2']?:'—') ?></td>
                <td><?= Helpers::e($c['source']?:'—') ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td><?= $c['is_fraud']?'<span class="badge badge-danger">Fraud</span>':'<span class="badge badge-success">Clean</span>' ?></td>
                <?php endif; ?>
                <td><?= Helpers::e($c['os']?:'—') ?></td>
                <td><?= Helpers::e($c['browser']?:'—') ?></td>
                <td><?= Helpers::e($c['device_type']?:'—') ?></td>
                <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address']) ?></td>
                <td><?php if(!empty($c['country'])):?><img src="https://flagcdn.com/16x12/<?=strtolower($c['country'])?>	.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?=Helpers::e($c['country'])?><?php else:?>—<?php endif;?></td>
                <td><?= Helpers::e($c['city']?:'—') ?></td>
                <td><?= $c['has_conversion']?'<span class="badge badge-success" style="font-size:10px">&#10003; Yes</span>':'<span class="badge badge-muted" style="font-size:10px">No</span>' ?></td>
                <td><?php if($c['conv_status']):$bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger'];?><span class="badge badge-<?=$bm[$c['conv_status']]??'muted'?>"><?=$c['conv_status']?></span><?php else:?>—<?php endif;?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($c['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SmartLink Conversions table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">&#9989; SmartLink Conversions</span>
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','export_type'=>'conversions'])) ?>" class="btn btn-secondary btn-sm">&#11123; Export</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-conv" style="font-size:12px">
            <thead><tr>
                <th>SMARTLINK</th><th>OFFER</th><th>CLICK ID</th><th>CONV ID</th>
                <th>SUB1</th><th>SUB2</th><th>STATUS</th><th>PAYOUT</th>
                <th>GOAL</th><th>TXN ID</th><th>COUNTRY</th><th>OS</th><th>DEVICE</th><th>CONVERTED AT</th>
            </tr></thead>
            <tbody>
            <?php if (empty($slConversions)): ?>
            <tr><td colspan="14" class="text-center text-muted" style="padding:28px">No SmartLink conversions for the selected period</td></tr>
            <?php else: foreach ($slConversions as $r):
                $bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger'];
            ?>
            <tr>
                <td><span style="background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;white-space:nowrap"><?= Helpers::e($r['smartlink_name']) ?></span></td>
                <td style="font-size:11px"><?= Helpers::e($r['offer_name']) ?></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1']?:'—') ?></td>
                <td><?= Helpers::e($r['sub2']?:'—') ?></td>
                <td><span class="badge badge-<?=$bm[$r['status']]??'muted'?>"><?=$r['status']?></span></td>
                <td class="fw-bold">$<?= number_format((float)$r['payout'],4) ?></td>
                <td><?= Helpers::e($r['goal_name']?:'—') ?></td>
                <td class="text-muted" style="font-size:11px"><?= Helpers::e($r['transaction_id']?:'—') ?></td>
                <td><?php if(!empty($r['country'])):?><img src="https://flagcdn.com/16x12/<?=strtolower($r['country'])?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?=Helpers::e($r['country'])?><?php else:?>—<?php endif;?></td>
                <td><?= Helpers::e($r['os']?:'—') ?></td>
                <td><?= Helpers::e($r['device_type']?:'—') ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($r['converted_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
$.fn.dataTable.ext.errMode = 'none';
function dtInit(id, opts) {
    var $t = $('#' + id);
    if (!$t.length) return;
    if (opts.scrollX) opts.scrollX = $t.find('tbody tr').length > 0;
    $t.DataTable(opts);
}
$(function() {
    dtInit('tbl-perf',      { destroy:true, pageLength:50, order:[], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data for selected filters'} });
    dtInit('tbl-clicks',    { destroy:true, pageLength:50, order:[[14,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No clicks found for selected filters'} });
    dtInit('tbl-conv',      { destroy:true, pageLength:50, order:[[21,'desc']], scrollX:true, columnDefs:[{orderable:false,targets:[6,10,11]}], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No conversions found for selected filters'} });
    dtInit('tbl-sl-clicks', { destroy:true, pageLength:50, order:[[15,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink clicks found'} });
    dtInit('tbl-sl-conv',   { destroy:true, pageLength:50, order:[[13,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink conversions found'} });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
