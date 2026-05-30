<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<?php $hideFraudRejected = (string)(Config::get('config', 'conversion.hide_fraud_rejected_reports') ?? '0') === '1'; ?>

<div class="page-header">
    <div><h1>Reports</h1><p>Performance, clicks &amp; conversions for your managed affiliates</p></div>
    <div style="display:flex;gap:8px;align-items:center">

        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#11123; Export CSV</a>
    </div>
</div>

<?php
$tabs = [
    'day'        => ['icon'=>'&#128197;', 'label'=>'By Day'],
    'offer'      => ['icon'=>'&#128200;', 'label'=>'By Offer'],
    'country'    => ['icon'=>'&#127760;', 'label'=>'By Country'],
    'sub'        => ['icon'=>'&#128279;', 'label'=>'By Aff Sub'],
    'affiliate'  => ['icon'=>'&#128101;', 'label'=>'Affiliate Report'],
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
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="mgr-report-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <input type="hidden" name="tab" value="<?= Helpers::e($tab) ?>">
            <?php $drpFromId='mgr-from'; $drpToId='mgr-to'; $drpFormId='mgr-report-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>

            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="mgr-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="mgr-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>

            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">All Managed</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $affId==$a['id']?'selected':'' ?>><?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
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

            <?php if ($tab === 'sl_report' && !empty($mgrSlList)): ?>
            <div class="form-group mb-0">
                <label>SmartLink</label>
                <select name="sl_id" class="form-control">
                    <option value="">All SmartLinks</option>
                    <?php foreach ($mgrSlList as $sl): ?>
                    <option value="<?= $sl['id'] ?>" <?= $slId==$sl['id']?'selected':'' ?>><?= Helpers::e($sl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['click','conversion','sl_report'])): ?>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>><?= number_format($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$hasAffiliates): ?>
<div class="alert alert-warning">No affiliates are assigned to your account yet.</div>
<?php endif; ?>


<?php /* ═══════════════════ PERFORMANCE TABS ═══════════════════ */ ?>
<?php if (in_array($tab, ['day','offer','country','sub','affiliate'])): ?>



<div class="stats-grid mb-3" style="grid-template-columns:repeat(<?= $hideFraudRejected ? 4 : 6 ?>,1fr)">
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format($totals['clicks']) ?></div><div class="stat-sub">Unique: <?= number_format($totals['uclicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totals['conversions']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($totals['approved']) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <!-- REJECTED card -->
    <div class="stat-card" style="cursor:pointer;border:1.5px solid transparent;transition:border-color .2s" onclick="openRejectedDetail()" onmouseenter="this.style.borderColor='#EF4444'" onmouseleave="this.style.borderColor='transparent'">
        <div class="stat-label">Rejected</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($totals['rejected']) ?></div>
        <div class="stat-sub" style="color:#EF4444">Click to drill down ↗</div>
    </div>
    <!-- FRAUD card -->
    <div class="stat-card" style="cursor:pointer;border:1.5px solid transparent;transition:border-color .2s" onclick="openFraudScorePanel()" onmouseenter="this.style.borderColor='#7C3AED'" onmouseleave="this.style.borderColor='transparent'">
        <div class="stat-label">Fraud Clicks</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($totals['fraud_clicks']) ?></div>
        <div class="stat-sub" style="color:#7C3AED">IPQS score ↗</div>
    </div>
    <?php endif; ?>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value" style="color:var(--secondary)">$<?= number_format($totals['payout'],2) ?></div><div class="stat-sub">Approved only</div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= $tabs[$tab]['label'] ?></span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap">
        <table id="tbl-perf">
            <thead>
                <tr>
                    <th><?= ['day'=>'Date','offer'=>'Offer','country'=>'Country','sub'=>'Aff Sub 1','affiliate'=>'Affiliate'][$tab] ?></th>
                    <th>Clicks</th><th>Unique</th><th>Conv.</th><th>Approved</th>
                    <th>CR%</th><th>EPC</th><th>Payout</th>
                    <?php if (!$hideFraudRejected): ?>
                    <th title="Conversions with IPQS fraud_score ≥ 60">Fraud Conv.</th>
                    <th title="Rejected conversions · total IPQS checked">Rejected Conv. (IPQS)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="<?= $hideFraudRejected ? 8 : 10 ?>" class="text-center text-muted" style="padding:32px">No data for selected filters</td></tr>
            <?php else: foreach ($rows as $row):
                $cr  = $row['clicks'] > 0 ? round($row['conversions']/$row['clicks']*100,2) : 0;
                $epc = $row['clicks'] > 0 ? round($row['payout']/$row['clicks'],4) : 0;
            ?>
            <tr>
                <td class="fw-bold"><?php
                    if ($tab === 'country' && !empty($row['label'])):
                        ?><img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($row['label'])) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:4px"><?php
                    endif;
                    if ($tab === 'offer' && !empty($row['row_id'])):
                        ?><a href="/affiliate_manager/offers/<?= (int)$row['row_id'] ?>" style="color:inherit"><?= Helpers::e($row['label'] ?: '—') ?></a><?php
                    else:
                        echo Helpers::e($row['label'] ?: '—');
                    endif;
                ?></td>
                <td><?= number_format($row['clicks']) ?></td>
                <td><?= number_format($row['uclicks']) ?></td>
                <td><?= number_format($row['conversions']) ?></td>
                <td><?= number_format($row['approved']) ?></td>
            <?php
            $rowLabel2 = $row['label'] ?: '—';
            $iq2 = $perfIpqsStats[$rowLabel2] ?? null;
            $iqFraudConv2 = $iq2 ? (int)($iq2['fraud_conv'] ?? 0) : 0;
            $iqRejConv2   = $iq2 ? (int)($iq2['rejected_conv'] ?? 0) : 0;
            $iqChecked2   = $iq2 ? (int)($iq2['total_checked'] ?? 0) : 0;
            ?>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td class="fw-bold">$<?= number_format($row['payout'],2) ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td title="Fraud Conv. (IPQS ≥ 60)">
                    <?php if ($iqFraudConv2 > 0): ?>
                        <span style="color:#DC2626;font-weight:700"><?= number_format($iqFraudConv2) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">0</span>
                    <?php endif; ?>
                </td>
                <td title="Rejected Conv. · <?= $iqChecked2 ?> checked by IPQS">
                    <?php if ($iqRejConv2 > 0): ?>
                        <span style="color:#DC2626;font-weight:700"><?= number_format($iqRejConv2) ?></span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($iqChecked2) ?></span>
                    <?php elseif ($iqChecked2 > 0): ?>
                        <span style="color:#059669">0</span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($iqChecked2) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
            <?php
                $tfTotalFraudConv2 = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['fraud_conv'] ?? 0)), array_column($rows ?: [], 'label')));
                $tfTotalRejConv2   = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['rejected_conv'] ?? 0)), array_column($rows ?: [], 'label')));
                $tfTotalChecked2   = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['total_checked'] ?? 0)), array_column($rows ?: [], 'label')));
            ?>
            <tr style="font-weight:700;background:var(--surface-alt,#F8FAFC);border-top:2px solid var(--border,#E2E8F0)">
                <td style="font-weight:800">TOTAL</td>
                <td><?= number_format($totals['clicks']) ?></td>
                <td><?= number_format($totals['uclicks']) ?></td>
                <td><?= number_format($totals['conversions']) ?></td>
                <td><?= number_format($totals['approved']) ?></td>
                <td>—</td>
                <td>—</td>
                <td>$<?= number_format($totals['payout'],2) ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td>
                    <?php if ($tfTotalFraudConv2 > 0): ?>
                        <span style="color:#DC2626;font-weight:800"><?= number_format($tfTotalFraudConv2) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($tfTotalRejConv2 > 0): ?>
                        <span style="color:#DC2626;font-weight:800"><?= number_format($tfTotalRejConv2) ?></span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($tfTotalChecked2) ?></span>
                    <?php elseif ($tfTotalChecked2 > 0): ?>
                        <span style="color:#059669">0</span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($tfTotalChecked2) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            </tfoot>
        </table>
    </div>
</div>


<?php /* ═══════════════════ CLICK LOG TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'click'): ?>

<?php
$totalClicks    = count($clicks);
$fraudCount     = count(array_filter($clicks, fn($r) => $r['is_fraud']));
$approvedPayoutTotal = 0;
foreach ($clicks as $cl) {
    if (($cl['conv_status'] ?? '') === 'approved') {
        $approvedPayoutTotal += (float)$cl['conv_payout'];
    }
}
?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(<?= $hideFraudRejected ? 3 : 4 ?>,1fr)">
    <div class="stat-card"><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($totalClicks) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <div class="stat-card" style="cursor:pointer" onclick="openFraudScorePanel()">
        <div class="stat-label">Fraud</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($fraudCount) ?></div>
        <div class="stat-sub"><?= $totalClicks>0?round($fraudCount/$totalClicks*100,1):0 ?>% rate</div>
    </div>
    <?php endif; ?>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value" style="color:var(--secondary)">$<?= number_format($approvedPayoutTotal,2) ?></div><div class="stat-sub">Approved conversions only</div></div>
    <div class="stat-card"><div class="stat-label">Period</div><div class="stat-value" style="font-size:14px"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Click Log</span>
        <span class="text-muted text-sm"><?= number_format($totalClicks) ?> rows</span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-clicks" style="min-width:1800px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>AFFILIATE</th>
                    <th>CLICK ID</th>
                    <th>SUB 1</th>
                    <th>SUB 2</th>
                    <th>SUB 3</th>
                    <th>SOURCE</th>
                    <?php if (!$hideFraudRejected): ?>
                    <th>FRAUD</th>
                    <?php endif; ?>
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
            <tr><td colspan="<?= $hideFraudRejected ? 17 : 18 ?>" class="text-center text-muted" style="padding:32px">No clicks found for selected filters</td></tr>
            <?php else: foreach ($clicks as $c):
                $isFraud    = (bool)$c['is_fraud'];
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
            <tr style="<?= $isFraud ? 'background:#FFF5F5' : '' ?>">
                <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($c['offer_name'] ?: '— Custom URL —') ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1'] ?: '—') ?></td>
                <td><?= Helpers::e($c['sub2'] ?: '—') ?></td>
                <td><?= Helpers::e($c['sub3'] ?: '—') ?></td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="<?= Helpers::e($c['source'] ?: ($c['referer'] ?? '')) ?>"><?= Helpers::e($sourceLabel) ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td>
                    <?php if ($isFraud): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px">
                        <span class="badge badge-danger" title="Score: <?= (int)$c['fraud_score'] ?>. <?= Helpers::e($c['fraud_reasons'] ?? '') ?>">Fraud&nbsp;<?= (int)$c['fraud_score'] ?></span>
                        <?php if (!empty($c['ip_address'])): ?>
                        <button type="button" onclick="ipqsLookupIp('<?= Helpers::e($c['ip_address']) ?>')"
                                style="background:none;border:none;cursor:pointer;font-size:10px;color:#7C3AED;text-decoration:underline;padding:0">IPQS</button>
                        <?php endif; ?>
                    </span>
                    <?php else: ?>
                    <span style="display:inline-flex;align-items:center;gap:4px">
                        <span class="badge badge-success" style="background:#D1FAE5;color:#065F46">● Clean</span>
                        <?php if (!empty($c['ip_address'])): ?>
                        <button type="button" onclick="ipqsLookupIp('<?= Helpers::e($c['ip_address']) ?>')"
                                style="background:none;border:none;cursor:pointer;font-size:10px;color:#94A3B8;text-decoration:underline;padding:0">check</button>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
                <td><?= Helpers::e($c['os'] ?: '—') ?></td>
                <td><?= Helpers::e($c['browser'] ?: '—') ?></td>
                <td><?= Helpers::e(ucfirst($c['device_type'] ?? '—')) ?></td>
                <td>
                    <span style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address']) ?></span>
                    <?php if (!empty($c['ip_address'])): ?>
                    <button type="button" onclick="ipqsLookupIp('<?= Helpers::e($c['ip_address']) ?>')"
                            style="display:block;background:none;border:none;cursor:pointer;font-size:9px;color:#7C3AED;text-decoration:underline;padding:0;margin-top:1px">🛡️ Score</button>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($c['country'])): ?>
                    <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png"
                         onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px">
                    <?= Helpers::e($c['country']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($c['city'] ?: '—') ?></td>
                <td><?= $statusBadge ?></td>
                <td class="fw-bold" style="color:<?= $convPayout > 0 ? 'var(--secondary)' : '#94A3B8' ?>">
                    <?= $convPayout > 0 ? '$'.number_format($convPayout, 2) : '—' ?>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($c['clicked_at'])) ?></td>
                <td class="text-muted" style="white-space:nowrap">
                    <?= !empty($c['conv_time']) ? date('M j, Y H:i', strtotime($c['conv_time'])) : '—' ?>
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
$totPayout    = array_sum(array_map(fn($r) => $r['status'] === 'approved' ? (float)$r['payout'] : 0.0, $convRows));
$statusCounts = array_count_values(array_column($convRows, 'status'));
?>



<div class="stats-grid mb-3" style="grid-template-columns:repeat(<?= $hideFraudRejected ? 4 : 5 ?>,1fr)">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= number_format(count($convRows)) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($statusCounts['approved'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value" style="color:#F59E0B"><?= number_format($statusCounts['pending'] ?? 0) ?></div></div>
    <?php if (!$hideFraudRejected): ?>
    <div class="stat-card" style="cursor:pointer" onclick="openRejectedDetail()">
        <div class="stat-label">Rejected</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($statusCounts['rejected'] ?? 0) ?></div>
        <div class="stat-sub" style="color:#EF4444">Drill down ↗</div>
    </div>
    <?php endif; ?>
    <div class="stat-card"><div class="stat-label">Approved Payout</div><div class="stat-value" style="color:var(--secondary)">$<?= number_format($totPayout,2) ?></div><div class="stat-sub">Approved only</div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Conversion Log</span>
        <span class="text-muted text-sm"><?= number_format(count($convRows)) ?> rows · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-conv" style="min-width:2200px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th><th>AFFILIATE</th><th>CLICK ID</th><th>AFF CLICK ID</th>
                    <th>AFF SUB 1</th><th>AFF SUB 2</th><th>AFF SUB 3</th><th>SOURCE</th>
                    <th>OS NAME</th><th>BROWSER</th><th>BROWSER VERSION</th>
                    <th>DEVICE BRAND</th><th>DEVICE MODEL</th><th>OS VERSION</th>
                    <th>IP ADDRESS</th><th>COUNTRY</th><th>CITY</th><th>REGION</th>
                    <th>PAYOUT</th>
                    <th>STATUS</th>
                    <th>GOAL</th><th>CONVERT TIME</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($convRows as $r):
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];

                $browserVer = '—';
                if (!empty($r['browser']) && !empty($r['user_agent'])) {
                    if (preg_match('/'.preg_quote($r['browser'],'/').'[\/\s]([\d\.]+)/i', $r['user_agent'], $m))
                        $browserVer = $m[1];
                }
                $osVer = '—';
                if (!empty($r['user_agent'])) {
                    if (preg_match('/Windows NT ([\d\.]+)/i', $r['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/Android ([\d\.]+)/i', $r['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/OS ([\d_]+) like/i', $r['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                    elseif (preg_match('/Mac OS X ([\d_\.]+)/i', $r['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                }
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
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1'] ?: '—') ?></td>
                <td><?= Helpers::e($r['sub2'] ?: '—') ?></td>
                <td><?= Helpers::e($r['sub3'] ?: '—') ?></td>
                <td><?= Helpers::e($r['sub4'] ?: '—') ?></td>
                <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($r['referer'] ?? '') ?>"><?= Helpers::e($sourceHost) ?></td>
                <td><?= Helpers::e($r['os'] ?: '—') ?></td>
                <td><?= Helpers::e($r['browser'] ?: '—') ?></td>
                <td><?= Helpers::e($browserVer) ?></td>
                <td><?= Helpers::e($deviceBrand) ?></td>
                <td><?= Helpers::e($deviceModel) ?></td>
                <td><?= Helpers::e($osVer) ?></td>
                <td>
                    <span style="font-family:monospace;font-size:11px"><?= Helpers::e($r['ip_address'] ?: '—') ?></span>
                    <?php if (!empty($r['ip_address'])): ?>
                    <button type="button" onclick="ipqsLookupIp('<?= Helpers::e($r['ip_address']) ?>')"
                            style="display:block;background:none;border:none;cursor:pointer;font-size:9px;color:#7C3AED;text-decoration:underline;padding:0;margin-top:1px">🛡️ Score</button>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($r['country'])): ?>
                    <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($r['country'])) ?>.png"
                         onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px">
                    <?= Helpers::e($r['country']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($r['city'] ?: '—') ?></td>
                <td><?= Helpers::e($r['region'] ?: '—') ?></td>
                <td class="fw-bold">$<?= number_format((float)$r['payout'],4) ?></td>
                <td>
                    <span class="badge badge-<?= $bm[$r['status']]??'muted' ?>">
                        <?= $r['status'] ?><?= $r['is_fraud'] ? ' <span style="font-size:10px">⚠</span>' : '' ?>
                    </span>
                    <?php if ($r['status'] === 'rejected' && !empty($r['ip_address'])): ?>
                    <button type="button" onclick="ipqsLookupIp('<?= Helpers::e($r['ip_address']) ?>')"
                            style="display:block;background:none;border:none;cursor:pointer;font-size:9px;color:#EF4444;text-decoration:underline;padding:0;margin-top:1px">🛡️ IPQS</button>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($r['goal_name'] ?: '—') ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($r['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════ SMARTLINK REPORT TAB (manager) ═══════════ */ ?>
<?php elseif ($tab === 'sl_report'): ?>

<?php
$_slaRows   = $slAffSum  ?? [];
$_slcRows   = $slClicks  ?? [];
$_slvRows   = $slConversions ?? [];
$_slaTotClk = array_sum(array_column($_slaRows,'clicks'));
$_slaTotCv  = array_sum(array_column($_slaRows,'conversions'));
$_slaPay    = array_sum(array_column($_slaRows,'payout'));
$_slaRev    = array_sum(array_column($_slaRows,'revenue'));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
    <div class="stat-card"><div class="stat-label">SL Clicks</div><div class="stat-value"><?= number_format($_slaTotClk) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($_slaTotCv) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR%</div><div class="stat-value"><?= $_slaTotClk>0?round($_slaTotCv/$_slaTotClk*100,2):0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($_slaPay,2) ?></div></div>
</div>

<!-- Affiliate Summary table -->
<div class="card mb-3">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">&#128101; Affiliate SmartLink Summary</span>
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','export_type'=>'summary'])) ?>" class="btn btn-secondary btn-sm">&#11123; Export</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-aff" style="font-size:12px;min-width:900px;width:100%">
            <thead><tr>
                <th style="min-width:160px">AFFILIATE</th>
                <th style="min-width:160px">SMARTLINK</th>
                <th>CLICKS</th><th>UNIQUE</th>
                <?php if (!$hideFraudRejected): ?><th>FRAUD</th><?php endif; ?>
                <th>CONVERSIONS</th><th>APPROVED</th><th>CR%</th><th>EPC</th><th>PAYOUT</th>
            </tr></thead>
            <tbody>
            <?php if (empty($_slaRows)): ?>
            <tr><td colspan="<?= $hideFraudRejected ? 9 : 10 ?>" class="text-center text-muted" style="padding:28px">No SmartLink data for selected period</td></tr>
            <?php else: foreach ($_slaRows as $r):
                $cr=$r['clicks']>0?round($r['conversions']/$r['clicks']*100,2):0;
                $epc=$r['clicks']>0?round($r['payout']/$r['clicks'],4):0;
            ?>
            <tr>
                <td style="white-space:nowrap;min-width:160px">
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td style="min-width:160px"><span style="background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700"><?= Helpers::e($r['smartlink_name']) ?></span></td>
                <td><?= number_format($r['clicks']) ?></td>
                <td><?= number_format($r['uclicks']) ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td><?= (int)$r['fraud_clicks']>0?'<span style="color:var(--danger)">'.(int)$r['fraud_clicks'].'</span>':'0' ?></td>
                <?php endif; ?>
                <td><?= number_format($r['conversions']) ?></td>
                <td style="color:var(--secondary)"><?= number_format($r['approved']) ?></td>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td>$<?= number_format((float)$r['payout'],2) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Click Log table -->
<div class="card mb-3">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">&#128432; SmartLink Clicks</span>
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','export_type'=>'clicks'])) ?>" class="btn btn-secondary btn-sm">&#11123; Export</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-clicks" style="font-size:12px;min-width:1200px">
            <thead><tr>
                <th>SMARTLINK</th><th>OFFER</th><th>AFFILIATE</th><th>CLICK ID</th>
                <th>SUB1</th><th>SOURCE</th>
                <?php if (!$hideFraudRejected): ?><th>FRAUD</th><?php endif; ?>
                <th>OS</th><th>BROWSER</th>
                <th>DEVICE</th><th>IP</th><th>COUNTRY</th><th>CITY</th><th>CONVERTED</th><th>CLICK TIME</th>
            </tr></thead>
            <tbody>
            <?php if (empty($_slcRows)): ?>
            <tr><td colspan="<?= $hideFraudRejected ? 14 : 15 ?>" class="text-center text-muted" style="padding:28px">No SmartLink clicks for selected filters</td></tr>
            <?php else: foreach ($_slcRows as $c): ?>
            <tr style="<?= $c['is_fraud']?'background:#FFF5F5':'' ?>">
                <td><span style="background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;white-space:nowrap"><?= Helpers::e($c['smartlink_name']) ?></span></td>
                <td style="font-size:11px"><?= Helpers::e($c['offer_name']) ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1']?:'—') ?></td>
                <td><?= Helpers::e($c['source']?:'—') ?></td>
                <?php if (!$hideFraudRejected): ?>
                <td><?= $c['is_fraud']?'<span class="badge badge-danger">Fraud</span>':'<span class="badge badge-success">Clean</span>' ?></td>
                <?php endif; ?>
                <td><?= Helpers::e($c['os']?:'—') ?></td>
                <td><?= Helpers::e($c['browser']?:'—') ?></td>
                <td><?= Helpers::e($c['device_type']?:'—') ?></td>
                <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address']) ?></td>
                <td><?php if(!empty($c['country'])):?><img src="https://flagcdn.com/16x12/<?=strtolower($c['country'])?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?=Helpers::e($c['country'])?><?php else:?>—<?php endif;?></td>
                <td><?= Helpers::e($c['city']?:'—') ?></td>
                <td><?= $c['has_conversion']?'<span class="badge badge-success" style="font-size:10px">&#10003; Yes</span>':'<span class="badge badge-muted" style="font-size:10px">No</span>' ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($c['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Conversion Log table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">&#9989; SmartLink Conversions</span>
        <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv','export_type'=>'conversions'])) ?>" class="btn btn-secondary btn-sm">&#11123; Export</a>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-conv" style="font-size:12px">
            <thead><tr>
                <th>SMARTLINK</th><th>OFFER</th><th>AFFILIATE</th>
                <th>CLICK ID</th><th>CONV ID</th><th>SUB1</th>
                <th>STATUS</th><th>PAYOUT</th><th>GOAL</th><th>TXN ID</th>
                <th>COUNTRY</th><th>OS</th><th>DEVICE</th><th>CONVERTED AT</th>
            </tr></thead>
            <tbody>
            <?php if (empty($_slvRows)): ?>
            <tr><td colspan="14" class="text-center text-muted" style="padding:28px">No SmartLink conversions for selected filters</td></tr>
            <?php else: foreach ($_slvRows as $r):
                $bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger'];
            ?>
            <tr>
                <td><span style="background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:700;white-space:nowrap"><?= Helpers::e($r['smartlink_name']) ?></span></td>
                <td style="font-size:11px"><?= Helpers::e($r['offer_name']) ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1']?:'—') ?></td>
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


<!-- ═══════════════ IPQS FRAUD SCORE PANEL ═══════════════ -->
<div id="ipqs-panel" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.7);z-index:9999;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px)">
    <div style="background:#0F172A;border-radius:16px;max-width:780px;width:100%;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 80px -10px rgba(0,0,0,.8);border:1px solid rgba(124,58,237,.3)">
        <!-- Panel header -->
        <div style="padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(135deg,rgba(124,58,237,.2),rgba(79,70,229,.1));display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:22px">🛡️</span>
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#EDE9FE">IPQualityScore — Real-Time Fraud Report</h3>
                    <p style="margin:0;font-size:11px;color:#A78BFA">Live fraud scoring · Rejected & Fraud analysis</p>
                </div>
                <span id="ipqs-panel-dot" style="width:9px;height:9px;border-radius:50%;background:#22C55E;animation:pulse-dot 1.5s infinite;margin-left:4px"></span>
            </div>
            <button onclick="closeIpqsPanel()" style="background:rgba(255,255,255,.1);border:none;color:#94A3B8;width:30px;height:30px;border-radius:6px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center">&times;</button>
        </div>

        <!-- IP lookup input -->
        <div style="padding:16px 22px;border-bottom:1px solid rgba(255,255,255,.06);flex-shrink:0">
            <div style="display:flex;gap:8px">
                <input type="text" id="ipqs-ip-input" placeholder="Enter IP address to score (e.g. 8.8.8.8)"
                       style="flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:9px 14px;color:#E2E8F0;font-size:13px;outline:none"
                       onkeydown="if(event.key==='Enter')ipqsLookupFromInput()">
                <button onclick="ipqsLookupFromInput()"
                        style="background:#7C3AED;color:#fff;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">
                    🔍 Score IP
                </button>
                <button onclick="runIpqsPanel()"
                        style="background:rgba(255,255,255,.08);color:#A5B4FC;border:1px solid rgba(124,58,237,.3);border-radius:8px;padding:9px 14px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">
                    📊 Batch Scan
                </button>
            </div>
            <p style="margin:6px 0 0;font-size:11px;color:#64748B">
                Uses IPQualityScore API · Configure your API key in Settings → Fraud → IPQS
            </p>
        </div>

        <!-- Summary stats row -->
        <div id="ipqs-summary-row" style="padding:14px 22px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;gap:12px;flex-wrap:wrap;flex-shrink:0">
            <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#FCA5A5;font-size:9px;font-weight:700;text-transform:uppercase">Rejected</div>
                <div style="color:#FEF2F2;font-size:20px;font-weight:800" id="ipqs-sum-rejected">0</div>
            </div>
            <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#FCA5A5;font-size:9px;font-weight:700;text-transform:uppercase">Fraud</div>
                <div style="color:#FEF2F2;font-size:20px;font-weight:800" id="ipqs-sum-fraud">0</div>
            </div>
            <div style="background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#C4B5FD;font-size:9px;font-weight:700;text-transform:uppercase">Avg Score</div>
                <div style="color:#EDE9FE;font-size:20px;font-weight:800" id="ipqs-sum-avg">—</div>
            </div>
            <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#86EFAC;font-size:9px;font-weight:700;text-transform:uppercase">Clean</div>
                <div style="color:#F0FDF4;font-size:20px;font-weight:800" id="ipqs-sum-clean">—</div>
            </div>
            <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#FCD34D;font-size:9px;font-weight:700;text-transform:uppercase">VPN/Proxy</div>
                <div style="color:#FFFBEB;font-size:20px;font-weight:800" id="ipqs-sum-vpn">—</div>
            </div>
            <div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:8px 14px;min-width:100px">
                <div style="color:#A5B4FC;font-size:9px;font-weight:700;text-transform:uppercase">Bot</div>
                <div style="color:#EEF2FF;font-size:20px;font-weight:800" id="ipqs-sum-bot">—</div>
            </div>
        </div>

        <!-- Results table -->
        <div style="overflow-y:auto;flex:1">
            <div id="ipqs-loading" style="display:none;text-align:center;padding:40px;color:#A78BFA">
                <div style="font-size:32px;animation:spin 1s linear infinite;display:inline-block">⟳</div>
                <div style="margin-top:10px;font-size:13px">Scoring IPs with IPQualityScore…</div>
            </div>
            <div id="ipqs-empty" style="text-align:center;padding:50px;color:#475569">
                <div style="font-size:40px;margin-bottom:12px">🔍</div>
                <div style="font-size:14px;font-weight:600;color:#94A3B8">Enter an IP or click Batch Scan to score IPs from this report</div>
                <div style="font-size:12px;color:#64748B;margin-top:6px">Scores are fetched live from IPQualityScore API</div>
            </div>
            <table id="ipqs-results-table" style="display:none;width:100%;font-size:12px;border-collapse:collapse">
                <thead>
                    <tr style="background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.06)">
                        <th style="padding:10px 14px;text-align:left;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">IP Address</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Fraud Score</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Risk</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">VPN</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Proxy</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Bot</th>
                        <th style="padding:10px 14px;text-align:center;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">TOR</th>
                        <th style="padding:10px 14px;text-align:left;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Country</th>
                        <th style="padding:10px 14px;text-align:left;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">ISP</th>
                        <th style="padding:10px 14px;text-align:left;color:#94A3B8;font-size:10px;font-weight:700;text-transform:uppercase">Context</th>
                    </tr>
                </thead>
                <tbody id="ipqs-results-body"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejected detail drill-down panel -->
<div id="rejected-panel" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.7);z-index:9998;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px)">
    <div style="background:#fff;border-radius:14px;max-width:700px;width:100%;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 80px -10px rgba(0,0,0,.4)">
        <div style="padding:18px 22px;border-bottom:1px solid #E2E8F0;background:linear-gradient(135deg,#FEF2F2,#FFE4E6);display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:800;color:#991B1B">📊 Rejected Conversion Report</h3>
                <p style="margin:2px 0 0;font-size:11px;color:#DC2626">Real-time breakdown with IPQS fraud scoring</p>
            </div>
            <button onclick="closeRejectedPanel()" style="background:rgba(0,0,0,.06);border:none;color:#94A3B8;width:30px;height:30px;border-radius:6px;cursor:pointer;font-size:18px">&times;</button>
        </div>
        <div style="padding:16px 22px;border-bottom:1px solid #F1F5F9;display:flex;gap:12px;flex-shrink:0">
            <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 16px;text-align:center;min-width:90px">
                <div style="color:#DC2626;font-size:9px;font-weight:700;text-transform:uppercase">Total Rejected</div>
                <div style="color:#991B1B;font-size:22px;font-weight:800" id="rej-total">—</div>
            </div>
            <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:10px 16px;text-align:center;min-width:90px">
                <div style="color:#D97706;font-size:9px;font-weight:700;text-transform:uppercase">Fraud Flagged</div>
                <div style="color:#92400E;font-size:22px;font-weight:800" id="rej-fraud">—</div>
            </div>
            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 16px;text-align:center;min-width:90px">
                <div style="color:#15803D;font-size:9px;font-weight:700;text-transform:uppercase">Rate</div>
                <div style="color:#14532D;font-size:22px;font-weight:800" id="rej-rate">—</div>
            </div>
        </div>
        <div style="overflow-y:auto;flex:1;padding:16px 22px">
            <div id="rej-content" style="color:#64748B;font-size:13px;text-align:center;padding:32px">
                Loading rejected conversion details…
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse-dot {
    0%,100%{opacity:1;transform:scale(1)}
    50%{opacity:.5;transform:scale(1.4)}
}
@keyframes spin {
    from{transform:rotate(0deg)} to{transform:rotate(360deg)}
}
#ipqs-ip-input:focus {
    border-color:#7C3AED !important;
    box-shadow:0 0 0 2px rgba(124,58,237,.2);
}
</style>

<script>
// ── IPQS Panel helpers ──────────────────────────────────────────────────────

const IPQS_API_KEY = window.IPQS_API_KEY || '<?= Helpers::e(defined('IPQS_API_KEY') ? IPQS_API_KEY : '') ?>';

// Collect IPs from current page table
function collectPageIps() {
    const ips = new Set();
    document.querySelectorAll('table tbody tr').forEach(tr => {
        tr.querySelectorAll('td').forEach(td => {
            const txt = td.textContent.trim();
            if (/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(txt)) ips.add(txt);
        });
    });
    return [...ips].slice(0, 50); // cap at 50 for batch
}

function openFraudScorePanel() {
    document.getElementById('ipqs-panel').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeIpqsPanel() {
    document.getElementById('ipqs-panel').style.display = 'none';
    document.body.style.overflow = '';
}

function openRejectedDetail() {
    const panel = document.getElementById('rejected-panel');
    panel.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    renderRejectedDetail();
}
function closeRejectedPanel() {
    document.getElementById('rejected-panel').style.display = 'none';
    document.body.style.overflow = '';
}

function renderRejectedDetail() {
    const rows = [];
    document.querySelectorAll('table tbody tr').forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (!cells.length) return;
        // Look for rejected badge
        if (tr.innerHTML.includes('badge-danger') && tr.innerHTML.toLowerCase().includes('rejected')) {
            rows.push(tr);
        }
    });

    const total = rows.length;
    const fraudFlagged = rows.filter(r => r.innerHTML.toLowerCase().includes('fraud') || r.style.background.includes('FFF5F5')).length;
    const rate = total > 0 ? Math.round(fraudFlagged/total*100) : 0;

    document.getElementById('rej-total').textContent = total || '<?= number_format($totals["rejected"] ?? $statusCounts["rejected"] ?? 0) ?>';
    document.getElementById('rej-fraud').textContent = fraudFlagged;
    document.getElementById('rej-rate').textContent = rate + '%';

    const content = document.getElementById('rej-content');
    if (total === 0) {
        content.innerHTML = '<div style="text-align:center;color:#94A3B8;padding:32px"><div style="font-size:32px;margin-bottom:8px">✅</div>No rejected conversions visible on this page.<br><small>Switch to Conversions tab for full detail.</small></div>';
    } else {
        let html = '<table style="width:100%;font-size:12px;border-collapse:collapse">';
        html += '<thead><tr style="background:#F8FAFC"><th style="padding:8px;text-align:left;color:#64748B;font-size:10px;font-weight:700;border-bottom:1px solid #E2E8F0">ROW</th><th style="padding:8px;text-align:left;color:#64748B;font-size:10px;font-weight:700;border-bottom:1px solid #E2E8F0">DETAILS</th><th style="padding:8px;text-align:center;color:#64748B;font-size:10px;font-weight:700;border-bottom:1px solid #E2E8F0">FRAUD FLAG</th></tr></thead><tbody>';
        rows.slice(0,20).forEach((tr, i) => {
            const cells = tr.querySelectorAll('td');
            const hasFraud = tr.innerHTML.toLowerCase().includes('fraud') || tr.style.background.includes('FFF5F5');
            html += `<tr style="border-bottom:1px solid #F1F5F9;${hasFraud?'background:#FFF5F5':''}">
                <td style="padding:8px;color:#64748B">#${i+1}</td>
                <td style="padding:8px;color:#1E293B">${cells[0]?.textContent?.trim() || '—'}</td>
                <td style="padding:8px;text-align:center">${hasFraud ? '<span style="background:#FEE2E2;color:#DC2626;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700">⚠ FRAUD</span>' : '<span style="background:#D1FAE5;color:#065F46;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700">✓ CLEAN</span>'}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        if (total > 20) html += `<p style="text-align:center;color:#94A3B8;font-size:11px;margin-top:10px">Showing 20 of ${total} rejected rows. Use Conversions tab for full list.</p>`;
        content.innerHTML = html;
    }
}

// Score a single IP via IPQualityScore
async function ipqsLookupIp(ip) {
    openFraudScorePanel();
    document.getElementById('ipqs-ip-input').value = ip;
    await scoreIps([ip]);
}

async function ipqsLookupFromInput() {
    const ip = document.getElementById('ipqs-ip-input').value.trim();
    if (!ip || !/^\d{1,3}(\.\d{1,3}){3}$/.test(ip)) {
        alert('Please enter a valid IPv4 address');
        return;
    }
    await scoreIps([ip]);
}

async function runIpqsPanel() {
    openFraudScorePanel();
    const ips = collectPageIps();
    if (ips.length === 0) {
        document.getElementById('ipqs-empty').style.display = 'block';
        document.getElementById('ipqs-empty').innerHTML = '<div style="font-size:32px;margin-bottom:10px">⚠️</div><div style="color:#94A3B8;font-size:13px">No IP addresses found on current page.<br>Switch to Click Log or Conversions tab first.</div>';
        return;
    }
    await scoreIps(ips);
}

async function runIpqsBulkClickScan() {
    openFraudScorePanel();
    const ips = collectPageIps();
    await scoreIps(ips.length ? ips : []);
}

function ipqsScanRow(label, rejectedCount) {
    openFraudScorePanel();
    document.getElementById('ipqs-empty').style.display = 'block';
    document.getElementById('ipqs-empty').innerHTML = `<div style="font-size:32px;margin-bottom:10px">📊</div><div style="color:#94A3B8;font-size:13px"><strong style="color:#E2E8F0">${label}</strong> — ${rejectedCount} rejected conversions<br>Switch to Conversions tab and use IP Score buttons for individual lookup.</div>`;
}

async function scoreIps(ips) {
    const loading = document.getElementById('ipqs-loading');
    const empty = document.getElementById('ipqs-empty');
    const table = document.getElementById('ipqs-results-table');
    const tbody = document.getElementById('ipqs-results-body');

    if (!ips.length) {
        empty.style.display = 'block';
        empty.innerHTML = '<div style="font-size:32px;margin-bottom:10px">⚠️</div><div style="color:#94A3B8;font-size:13px">No IPs to scan. Navigate to Click Log tab first.</div>';
        return;
    }

    loading.style.display = 'block';
    empty.style.display = 'none';
    table.style.display = 'none';
    tbody.innerHTML = '';

    const apiKey = IPQS_API_KEY;
    let results = [], fraud = 0, clean = 0, vpn = 0, bot = 0, totalScore = 0;

    // Score IPs via your backend proxy (prevents CORS & keeps API key server-side)
    // Falls back to direct API call if proxy not configured
    for (const ip of ips) {
        try {
            let data;
            // Try server-side proxy first
            const proxyResp = await fetch(`/admin/fraud/ipqs-proxy?ip=${encodeURIComponent(ip)}`).catch(() => null);
            if (proxyResp && proxyResp.ok) {
                data = await proxyResp.json();
            } else if (apiKey) {
                // Direct API call (requires CORS header or same-origin)
                const resp = await fetch(`https://www.ipqualityscore.com/api/json/ip/${apiKey}/${ip}?strictness=1&allow_public_access_points=true&fast=false&lighter_penalties=false`);
                data = await resp.json();
            } else {
                // Demo mode: simulate realistic data
                data = simulateIpqsScore(ip);
            }

            const score = data.fraud_score ?? 0;
            const isHigh = score >= 75;
            const isMed  = score >= 40 && score < 75;
            totalScore += score;
            if (isHigh) fraud++;
            else clean++;
            if (data.vpn || data.proxy) vpn++;
            if (data.bot_status) bot++;

            results.push({ ip, data, score });

            // Update summary live
            document.getElementById('ipqs-sum-rejected').textContent = '<?= $totals["rejected"] ?? $statusCounts["rejected"] ?? 0 ?>';
            document.getElementById('ipqs-sum-fraud').textContent = fraud;
            document.getElementById('ipqs-sum-avg').textContent = Math.round(totalScore / results.length);
            document.getElementById('ipqs-sum-clean').textContent = clean;
            document.getElementById('ipqs-sum-vpn').textContent = vpn;
            document.getElementById('ipqs-sum-bot').textContent = bot;

            // Update inline avg score
            const avgEl = document.getElementById('ipqs-avg-score');
            if (avgEl) avgEl.textContent = Math.round(totalScore / results.length);
            const lblEl = document.getElementById('ipqs-score-label');
            if (lblEl) lblEl.textContent = `${results.length} IP${results.length!==1?'s':''} scored`;

            // Render row
            const scoreColor = isHigh ? '#EF4444' : isMed ? '#F59E0B' : '#22C55E';
            const riskLabel = isHigh ? '<span style="background:#FEE2E2;color:#DC2626;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700">HIGH</span>'
                            : isMed  ? '<span style="background:#FEF3C7;color:#D97706;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700">MED</span>'
                            :          '<span style="background:#D1FAE5;color:#065F46;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700">LOW</span>';
            const yesNo = v => v ? '<span style="color:#EF4444;font-weight:700">Yes</span>' : '<span style="color:#94A3B8">No</span>';

            tbody.insertAdjacentHTML('beforeend', `
                <tr style="border-bottom:1px solid rgba(255,255,255,.04);${isHigh?'background:rgba(239,68,68,.05)':''}">
                    <td style="padding:9px 14px;font-family:monospace;color:#E2E8F0;font-size:11px">${ip}</td>
                    <td style="padding:9px 14px;text-align:center">
                        <span style="font-size:18px;font-weight:800;color:${scoreColor}">${score}</span>
                        <div style="height:3px;background:rgba(255,255,255,.08);border-radius:2px;margin-top:3px;width:60px;margin-left:auto;margin-right:auto">
                            <div style="height:100%;width:${Math.min(score,100)}%;background:${scoreColor};border-radius:2px"></div>
                        </div>
                    </td>
                    <td style="padding:9px 14px;text-align:center">${riskLabel}</td>
                    <td style="padding:9px 14px;text-align:center;font-size:12px">${yesNo(data.vpn)}</td>
                    <td style="padding:9px 14px;text-align:center;font-size:12px">${yesNo(data.proxy)}</td>
                    <td style="padding:9px 14px;text-align:center;font-size:12px">${yesNo(data.bot_status)}</td>
                    <td style="padding:9px 14px;text-align:center;font-size:12px">${yesNo(data.tor)}</td>
                    <td style="padding:9px 14px;color:#CBD5E1;font-size:11px">${data.country_code || '—'}</td>
                    <td style="padding:9px 14px;color:#94A3B8;font-size:10px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${data.ISP || data.organization || '—'}</td>
                    <td style="padding:9px 14px;color:#64748B;font-size:10px">${data.message || 'Scored live'}</td>
                </tr>
            `);

            table.style.display = '';
        } catch (e) {
            console.warn('IPQS error for ' + ip, e);
        }
    }

    loading.style.display = 'none';
}

// Demo/fallback simulator (used when no API key configured)
function simulateIpqsScore(ip) {
    const seed = ip.split('.').reduce((a, b) => a + parseInt(b), 0);
    const score = (seed * 37 + 13) % 101;
    return {
        fraud_score: score,
        vpn: score > 70,
        proxy: score > 65,
        tor: score > 90,
        bot_status: score > 80,
        country_code: ['US','DE','RU','CN','BR','IN','FR','GB'][seed % 8],
        ISP: ['Cloudflare','Amazon AWS','DigitalOcean','Google LLC','Comcast'][seed % 5],
        message: '⚠ Demo mode — configure IPQS API key in Settings'
    };
}

// Close on backdrop click
document.getElementById('ipqs-panel').addEventListener('click', function(e) {
    if (e.target === this) closeIpqsPanel();
});
document.getElementById('rejected-panel').addEventListener('click', function(e) {
    if (e.target === this) closeRejectedPanel();
});

// DataTables
$.fn.dataTable.ext.errMode = 'none';
function dtInit(id, opts) {
    var $t = $('#' + id);
    if (!$t.length) return;
    if (opts.scrollX) opts.scrollX = $t.find('tbody tr').length > 0;
    $t.DataTable(opts);
}
$(function() {
    dtInit('tbl-perf',      { destroy:true, pageLength:50, order:[], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data for selected filters'} });
    dtInit('tbl-clicks',    { destroy:true, pageLength:50, order:[[16,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No clicks found for selected filters'} });
    dtInit('tbl-conv',      { destroy:true, pageLength:50, order:[[21,'desc']], scrollX:true, columnDefs:[{orderable:false,targets:[11,12]}], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No conversions found for selected filters'} });
    dtInit('tbl-sl-aff',    { destroy:true, pageLength:50, order:[[2,'desc']],  language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink data'} });
    dtInit('tbl-sl-clicks', { destroy:true, pageLength:50, order:[[14,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink clicks found'} });
    dtInit('tbl-sl-conv',   { destroy:true, pageLength:50, order:[[13,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink conversions found'} });
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
