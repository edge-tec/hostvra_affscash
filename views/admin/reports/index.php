<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Reports</h1><p>Performance analytics, click logs, conversions &amp; postbacks</p></div>
    <?php
    // Excel format is only implemented for conversion-status tabs (which serve
    // Approved / Rejected / Pending / Autohide). Other tabs offer CSV only.
    $exportXlsEnabled = in_array($tab, ['conversions','rejected','pending','autohide'], true);
    require BASE_PATH . '/views/partials/export_buttons.php';
    ?>
</div>

<?php
$tabs = [
    'performance'   => 'Performance',
    'clicks'        => 'Clicks',
    'conversions'   => 'Conversions',
    'rejected'      => 'Rejected',
    'pending'       => 'Pending',
    'autohide'      => 'Autohide',
    'offer_report'  => 'Offer Reports',
    'postback'      => 'Postback Log',
    'sl_clicks'     => 'SmartLink Clicks',
    'sl_conversions'=> 'SmartLink Conversions',
    'sl_affiliates' => 'SmartLink Affiliates',
];
$tabIcons = [
    'performance'   => '&#128200;',
    'clicks'        => '&#128432;',
    'conversions'   => '&#9989;',
    'rejected'      => '&#10060;',
    'pending'       => '&#9203;',
    'autohide'      => '&#128683;',
    'offer_report'  => '&#128196;',
    'postback'      => '&#128258;',
    'sl_clicks'     => '&#128279;',
    'sl_conversions'=> '&#128279;',
    'sl_affiliates' => '&#128279;',
];
?>

<!-- Tab bar -->
<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:16px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <?php foreach ($tabs as $key => $label): ?>
    <?php
        $isActive  = $tab === $key;
        $qs = http_build_query(array_merge($_GET, ['tab'=>$key, 'export'=>null]));
        $qs = preg_replace('/export=[^&]*&?/', '', $qs);
    ?>
    <a href="?<?= $qs ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;border:1px solid <?= $isActive ? '#E2E8F0' : 'transparent' ?>;border-bottom:<?= $isActive ? '2px solid #fff' : '2px solid transparent' ?>;margin-bottom:-2px;background:<?= $isActive ? '#fff' : 'transparent' ?>;color:<?= $isActive ? 'var(--primary)' : 'var(--text-muted)' ?>;text-decoration:none;transition:color .15s">
        <?= $tabIcons[$key] ?> <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="report-filter-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <input type="hidden" name="tab" value="<?= Helpers::e($tab) ?>">

            <?php $drpFromId='rpt-from'; $drpToId='rpt-to'; $drpFormId='report-filter-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>

            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="rpt-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="rpt-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>

            <?php if ($tab === 'performance'): ?>
            <div class="form-group mb-0">
                <label>Group By</label>
                <select name="group_by" class="form-control">
                    <option value="date"      <?= ($groupBy??'')==='date'      ?'selected':'' ?>>Day</option>
                    <option value="offer"     <?= ($groupBy??'')==='offer'     ?'selected':'' ?>>Offer</option>
                    <option value="affiliate" <?= ($groupBy??'')==='affiliate' ?'selected':'' ?>>Affiliate</option>
                    <option value="country"   <?= ($groupBy??'')==='country'   ?'selected':'' ?>>Country</option>
                    <option value="sub"       <?= ($groupBy??'')==='sub'       ?'selected':'' ?>>Aff Sub 1</option>
                </select>
            </div>
            <?php endif; ?>

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
                <label>Affiliate</label>
                <select id="rpt-aff-sel" name="affiliate_id" class="form-control">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $affId==$a['id']?'selected':'' ?>><?= Helpers::e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Aff Code</label>
                <input type="text" id="rpt-aff-id" name="affiliate_code" class="form-control"
                       placeholder="AFFF06092C5" style="width:130px;font-family:monospace;font-size:13px;text-transform:uppercase"
                       value="<?= Helpers::e(Helpers::get('affiliate_code') ?? '') ?>">
            </div>
            <script>
            (function(){
                var sel=document.getElementById('rpt-aff-sel'),txt=document.getElementById('rpt-aff-id');
                if(!sel||!txt)return;
                if(txt.value){sel.value='';}
                sel.addEventListener('change',function(){if(this.value)txt.value='';});
                txt.addEventListener('input',function(){if(this.value.trim())sel.value='';});
            })();
            </script>

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
                <input type="text" name="sub1" class="form-control" value="<?= Helpers::e($sub1) ?>" placeholder="sub1 value" style="width:110px">
            </div>

            <?php if (in_array($tab, ['sl_clicks','sl_conversions','sl_affiliates'])): ?>
            <div class="form-group mb-0">
                <label>SmartLink</label>
                <select name="sl_id" class="form-control">
                    <option value="">All SmartLinks</option>
                    <?php foreach ($slList as $sl): ?>
                    <option value="<?= $sl['id'] ?>" <?= $slId==$sl['id']?'selected':'' ?>><?= Helpers::e($sl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($tab !== 'performance'): ?>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000,10000] as $l): ?>
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

<?php /* ═══════════════════ PERFORMANCE TAB ═══════════════════ */ ?>
<?php if ($tab === 'performance'): ?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format($totals['clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Unique</div><div class="stat-value"><?= number_format($totals['uclicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($totals['conversions']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($totals['approved']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Rejected</div><div class="stat-value" style="color:var(--danger)"><?= number_format($totals['rejected']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Fraud Clicks</div><div class="stat-value" style="color:var(--danger)"><?= number_format($totals['fraud_clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($totals['payout'],2) ?></div></div>
    <?php if (Auth::role() === "admin"): ?><div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($totals['revenue'],2) ?></div></div><?php endif; ?>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= $totals['profit']>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($totals['profit'],2) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Report — Group by <?= ucfirst($groupBy) ?></span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap">
        <table id="tbl-perf">
            <thead>
                <tr>
                    <th><?= ucfirst($groupBy) ?></th>
                    <th>Clicks</th><th>Unique</th><th>Conv.</th><th>Approved</th>
                    <th>Rejected</th>
                    <th>Fraud</th>
                    <th>CR%</th><th>EPC</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><th>Profit</th><?php endif; ?>
                    <th title="Conversions with IPQualityScore fraud_score ≥ 60">Fraud Conv.</th>
                    <th title="Total conversions checked by IPQS · Rejected among checked">Rejected Conv. (IPQS)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $rowIdx => $row):
                $cr     = $row['clicks'] > 0 ? round($row['conversions']/$row['clicks']*100,2) : 0;
                $epc    = $row['clicks'] > 0 ? round($row['payout']/$row['clicks'],4) : 0;
                $profit = $row['revenue'] - $row['payout'];
                $rowLabel = $row['label'] ?: '—';
                // IPQS stats for this row
                $iq = $perfIpqsStats[$rowLabel] ?? null;
                $iqAvg = $iq ? (float)$iq['avg_score'] : null;
                $panelId = 'perf-ipqs-' . $rowIdx;
                if ($iq && $iqAvg !== null) {
                    if ($iqAvg >= 75)     { $iqBg = '#FEF2F2'; $iqCol = '#DC2626'; $iqRisk = 'High Risk'; }
                    elseif ($iqAvg >= 40) { $iqBg = '#FFFBEB'; $iqCol = '#D97706'; $iqRisk = 'Medium Risk'; }
                    else                  { $iqBg = '#ECFDF5'; $iqCol = '#059669'; $iqRisk = 'Low Risk'; }
                } else {
                    $iqBg = '#F8FAFC'; $iqCol = '#94A3B8'; $iqRisk = 'No Data';
                }
            ?>
            <tr data-perf-label="<?= Helpers::e($rowLabel) ?>">
                <td class="fw-bold"><?php
                    if ($groupBy === 'country' && $rowLabel !== '—'):
                        ?><img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($rowLabel)) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:4px"><?php
                    endif;
                    echo Helpers::e($rowLabel);
                ?></td>
                <td><?= number_format($row['clicks']) ?></td>
                <td><?= number_format($row['uclicks']) ?></td>
                <td><?= number_format($row['conversions']) ?></td>
                <td><?= number_format($row['approved']) ?></td>
                <td data-perf-rejected="<?= Helpers::e($rowLabel) ?>"><?= number_format($row['rejected']) ?></td>
                <td><?= number_format($row['fraud_clicks']) ?></td>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td>$<?= number_format($row['payout'],2) ?></td>
                <td>$<?= number_format($row['revenue'],2) ?></td>
                <td style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,2) ?></td>
                <?php
                    $iqFraudConv = $iq ? (int)($iq['fraud_conv'] ?? 0) : 0;
                    $iqRejConv   = $iq ? (int)($iq['rejected_conv'] ?? 0) : 0;
                    $iqChecked   = $iq ? (int)($iq['total_checked'] ?? 0) : 0;
                ?>
                <td title="Fraud Conv. (IPQS ≥ 60)">
                    <?php if ($iqFraudConv > 0): ?>
                        <span style="color:#DC2626;font-weight:700"><?= number_format($iqFraudConv) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">0</span>
                    <?php endif; ?>
                </td>
                <td title="Rejected Conv. · <?= $iqChecked ?> checked by IPQS">
                    <?php if ($iqRejConv > 0): ?>
                        <span style="color:#DC2626;font-weight:700"><?= number_format($iqRejConv) ?></span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($iqChecked) ?></span>
                    <?php elseif ($iqChecked > 0): ?>
                        <span style="color:#059669">0</span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($iqChecked) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr id="<?= $panelId ?>" style="display:none;background:#F8FAFC">
                <td colspan="14" style="padding:0">
                    <div style="padding:16px 20px 18px;border-top:2px solid <?= $iqBg === '#F8FAFC' ? '#E2E8F0' : $iqBg ?>">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                            <span style="font-size:13px;font-weight:700;color:#1E293B">IPQualityScore Fraud Report</span>
                            <span style="font-size:11px;color:#94A3B8">— <?= Helpers::e($groupBy) ?>: <strong><?= Helpers::e($rowLabel) ?></strong> · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
                            <a href="/admin/fraud-score-report?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?><?= $groupBy==='affiliate' && !empty($iq['key_id']) ? '&affiliate_id='.urlencode($iq['key_id']) : '' ?><?= $groupBy==='offer' && !empty($iq['key_id']) ? '&offer_id='.urlencode($iq['key_id']) : '' ?>"
                               style="margin-left:auto;font-size:11px;color:#6366F1;text-decoration:none;font-weight:600">Full Report →</a>
                        </div>
                        <?php if (!$iq || (int)$iq['total_checked'] === 0): ?>
                        <div style="color:#94A3B8;font-size:13px;padding:8px 0">No IPQS fraud data available for this period.</div>
                        <?php else:
                            $tc2   = (int)$iq['total_checked'];
                            $hi2   = (int)$iq['high_risk'];
                            $med2  = (int)$iq['medium_risk'];
                            $lo2   = (int)$iq['low_risk'];
                            $pend2 = (int)$iq['pending_check'];
                            $vpn2  = (int)$iq['vpn_count'];
                            $prx2  = (int)$iq['proxy_count'];
                            $tor2  = (int)$iq['tor_count'];
                            $dc2   = (int)$iq['datacenter_count'];
                            $max2  = $iq['max_score'] !== null ? (int)$iq['max_score'] : null;
                            $rej2  = (int)$iq['rejected_conv'];
                            $hiPct = $tc2 > 0 ? round($hi2/$tc2*100,1) : 0;
                        ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:14px">
                        <?php
                        $sc = function(string $lbl, $val, string $col='#1E293B', string $bg='#fff', string $brd='#E2E8F0') {
                            echo '<div style="background:'.$bg.';border:1px solid '.$brd.';border-radius:8px;padding:10px 14px">'
                               . '<div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748B;margin-bottom:4px">'.htmlspecialchars($lbl,ENT_QUOTES).'</div>'
                               . '<div style="font-size:21px;font-weight:700;color:'.$col.'">'.htmlspecialchars((string)$val,ENT_QUOTES).'</div></div>';
                        };
                        $sc('Conversions', number_format($tc2));
                        $sc('Avg Score', $iqAvg !== null ? number_format($iqAvg,1) : '—', $iqCol, $iqBg);
                        $sc('Max Score', $max2 !== null ? $max2 : '—', $max2 !== null && $max2 >= 75 ? '#DC2626' : ($max2 !== null && $max2 >= 40 ? '#D97706' : '#059669'));
                        $sc('High Risk ≥75', number_format($hi2).' ('.$hiPct.'%)', '#DC2626', '#FEF2F2', '#FECACA');
                        $sc('Medium Risk', number_format($med2), '#D97706', '#FFFBEB', '#FDE68A');
                        $sc('Clean <40', number_format($lo2), '#059669', '#ECFDF5', '#A7F3D0');
                        $sc('Rejected Conv.', number_format($rej2), $rej2 > 0 ? '#DC2626' : '#059669', $rej2 > 0 ? '#FEF2F2' : '#ECFDF5');
                        if ($pend2 > 0) $sc('Pending', number_format($pend2), '#9CA3AF', '#F9FAFB', '#E5E7EB');
                        ?>
                        </div>
                        <?php if ($vpn2 + $prx2 + $tor2 + $dc2 > 0): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px">
                            <span style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em">IP Flags:</span>
                            <?php if ($vpn2): ?><span style="padding:3px 10px;border-radius:6px;background:#EDE9FE;color:#7C3AED;font-size:11px;font-weight:700">VPN <?= $vpn2 ?></span><?php endif; ?>
                            <?php if ($prx2): ?><span style="padding:3px 10px;border-radius:6px;background:#FEF3C7;color:#B45309;font-size:11px;font-weight:700">PROXY <?= $prx2 ?></span><?php endif; ?>
                            <?php if ($tor2): ?><span style="padding:3px 10px;border-radius:6px;background:#FEE2E2;color:#DC2626;font-size:11px;font-weight:700">TOR <?= $tor2 ?></span><?php endif; ?>
                            <?php if ($dc2):  ?><span style="padding:3px 10px;border-radius:6px;background:#F0F9FF;color:#0369A1;font-size:11px;font-weight:700">DATACENTER <?= $dc2 ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($tc2 > 0): ?>
                        <div>
                            <div style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px">Score Distribution</div>
                            <div style="display:flex;height:10px;border-radius:6px;overflow:hidden;background:#F1F5F9">
                                <?php if ($hi2):  ?><div style="width:<?= round($hi2/$tc2*100,1)  ?>%;background:#EF4444" title="High Risk: <?= $hi2 ?>"></div><?php endif; ?>
                                <?php if ($med2): ?><div style="width:<?= round($med2/$tc2*100,1) ?>%;background:#F59E0B" title="Medium: <?= $med2 ?>"></div><?php endif; ?>
                                <?php if ($lo2):  ?><div style="width:<?= round($lo2/$tc2*100,1)  ?>%;background:#10B981" title="Clean: <?= $lo2 ?>"></div><?php endif; ?>
                                <?php if ($pend2): ?><div style="width:<?= round($pend2/$tc2*100,1) ?>%;background:#E5E7EB" title="Pending: <?= $pend2 ?>"></div><?php endif; ?>
                            </div>
                            <div style="display:flex;gap:12px;margin-top:5px;flex-wrap:wrap">
                                <span style="font-size:10px;color:#EF4444;font-weight:600">■ High ≥75 (<?= $hi2 ?>)</span>
                                <span style="font-size:10px;color:#F59E0B;font-weight:600">■ Medium 40–74 (<?= $med2 ?>)</span>
                                <span style="font-size:10px;color:#10B981;font-weight:600">■ Clean &lt;40 (<?= $lo2 ?>)</span>
                                <?php if ($pend2): ?><span style="font-size:10px;color:#9CA3AF;font-weight:600">■ Pending (<?= $pend2 ?>)</span><?php endif; ?>
                                <?php if ($rej2):  ?><span style="font-size:10px;color:#DC2626;font-weight:600">■ Rejected Conv. (<?= $rej2 ?>)</span><?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <?php
                $tfTotalFraudConv = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['fraud_conv'] ?? 0)), array_column($rows, 'label')));
                $tfTotalRejConv   = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['rejected_conv'] ?? 0)), array_column($rows, 'label')));
                $tfTotalChecked   = array_sum(array_map(fn($lbl) => (int)(($perfIpqsStats[$lbl]['total_checked'] ?? 0)), array_column($rows, 'label')));
                $tfProfit = $totals['revenue'] - $totals['payout'];
            ?>
            <tr style="font-weight:700;background:var(--surface-alt,#F8FAFC);border-top:2px solid var(--border,#E2E8F0)">
                <td style="font-weight:800">TOTAL</td>
                <td><?= number_format($totals['clicks']) ?></td>
                <td><?= number_format($totals['uclicks']) ?></td>
                <td><?= number_format($totals['conversions']) ?></td>
                <td><?= number_format($totals['approved']) ?></td>
                <td><?= number_format($totals['rejected']) ?></td>
                <td><?= number_format($totals['fraud_clicks']) ?></td>
                <td>—</td>
                <td>—</td>
                <td>$<?= number_format($totals['payout'],2) ?></td>
                <td>$<?= number_format($totals['revenue'],2) ?></td>
                <td style="color:<?= $tfProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($tfProfit,2) ?></td>
                <td>
                    <?php if ($tfTotalFraudConv > 0): ?>
                        <span style="color:#DC2626;font-weight:800"><?= number_format($tfTotalFraudConv) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($tfTotalRejConv > 0): ?>
                        <span style="color:#DC2626;font-weight:800"><?= number_format($tfTotalRejConv) ?></span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($tfTotalChecked) ?></span>
                    <?php elseif ($tfTotalChecked > 0): ?>
                        <span style="color:#059669">0</span>
                        <span style="color:#94A3B8;font-size:10px"> / <?= number_format($tfTotalChecked) ?></span>
                    <?php else: ?>
                        <span style="color:#94A3B8">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php /* ═══════════════════ CLICKS TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'clicks'): ?>

<?php
$totalClicks  = count($clicks);
$fraudCount   = count(array_filter($clicks, fn($r)=>$r['is_fraud']));
$totRevenue   = array_sum(array_column($clicks,'revenue'));
$totPayout    = array_sum(array_column($clicks,'payout'));
?>
<?php
$convertedCount = count(array_filter($clicks, fn($r)=>(bool)$r['has_conversion']));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($totalClicks) ?></div></div>
    <div class="stat-card"><div class="stat-label">Fraud</div><div class="stat-value" style="color:var(--danger)"><?= number_format($fraudCount) ?></div><div class="stat-sub"><?= $totalClicks>0?round($fraudCount/$totalClicks*100,1):0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Converted</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($convertedCount) ?></div><div class="stat-sub"><?= $totalClicks>0?round($convertedCount/$totalClicks*100,1):0 ?>% CR</div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($totRevenue,2) ?></div><div class="stat-sub" style="font-size:10px;color:var(--text-muted)">converted only</div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($totPayout,2) ?></div><div class="stat-sub" style="font-size:10px;color:var(--text-muted)">converted only</div></div>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= ($totRevenue-$totPayout)>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($totRevenue-$totPayout,2) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Click Log</span>
        <span class="text-muted text-sm"><?= number_format($totalClicks) ?> rows · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-clicks" style="min-width:2200px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th><th>AFFILIATE</th><th>CLICK ID</th>
                    <th>AFF CLICK ID</th><th>AFF SUB 1</th><th>AFF SUB 2</th><th>AFF SUB 3</th>
                    <th>SOURCE</th><th>FRAUD</th><th>OS NAME</th><th>BROWSER</th>
                    <th>BROWSER VER</th><th>USER AGENT</th><th>DEVICE BRAND</th><th>DEVICE MODEL</th>
                    <th>OS VER</th><th>IP ADDRESS</th><th>COUNTRY</th><th>CITY</th><th>REGION</th>
                    <th>CONVERTED</th><th>REVENUE</th><th>PAYOUT</th><th>PROFIT</th><th>CLICK TIME</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clicks)): ?>
            <tr><td colspan="26" class="text-center text-muted" style="padding:32px">No clicks for selected filters</td></tr>
            <?php else: foreach ($clicks as $c):
                $profit   = (float)$c['revenue'] - (float)$c['payout'];
                $isFraud  = (bool)$c['is_fraud'];
                $uaParsed = Helpers::e($c['user_agent'] ?? '');
                $browserVer = '—';
                if (!empty($c['browser']) && !empty($c['user_agent'])) {
                    if (preg_match('/'.preg_quote($c['browser'],'/').'[\/\s]([\d\.]+)/i', $c['user_agent'], $m)) $browserVer = $m[1];
                }
                $osVer = '—';
                if (!empty($c['user_agent'])) {
                    if (preg_match('/Windows NT ([\d\.]+)/i', $c['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/Android ([\d\.]+)/i', $c['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/OS ([\d_]+) like/i', $c['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                    elseif (preg_match('/Mac OS X ([\d_\.]+)/i', $c['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                }
                $deviceBrand = ucfirst($c['device_type'] ?? '—');
                $deviceModel = '—';
                if (!empty($c['user_agent'])) {
                    $ua = $c['user_agent'];
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
            ?>
            <tr style="<?= $isFraud?'background:#FFF5F5':'' ?>">
                <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($c['offer_name']) ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1']?:'—') ?></td>
                <td><?= Helpers::e($c['sub2']?:'—') ?></td>
                <td><?= Helpers::e($c['sub3']?:'—') ?></td>
                <td><?= Helpers::e($c['sub4']?:'—') ?></td>
                <td style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($c['referer']??'') ?>"><?= Helpers::e($c['referer'] ? (parse_url($c['referer'],PHP_URL_HOST) ?: $c['referer']) : '—') ?></td>
                <td>
                    <?php if ($isFraud): ?>
                    <span class="badge badge-danger" title="Score:<?= (int)$c['fraud_score'] ?> — <?= Helpers::e($c['fraud_reasons']??'') ?>">Fraud <?= (int)$c['fraud_score'] ?></span>
                    <?php else: ?><span class="badge badge-success">Clean</span><?php endif; ?>
                </td>
                <td><?= Helpers::e($c['os']?:'—') ?></td>
                <td><?= Helpers::e($c['browser']?:'—') ?></td>
                <td><?= Helpers::e($browserVer) ?></td>
                <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= $uaParsed ?>"><?= $uaParsed?:'—' ?></td>
                <td><?= Helpers::e($deviceBrand) ?></td>
                <td><?= Helpers::e($deviceModel) ?></td>
                <td><?= Helpers::e($osVer) ?></td>
                <td><?= Helpers::e($c['ip_address']) ?></td>
                <td><?php if (!empty($c['country'])): ?><img src="https://flagcdn.com/16x12/<?= strtolower($c['country']) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?= Helpers::e($c['country']) ?><?php else: ?>—<?php endif; ?></td>
                <td><?= Helpers::e($c['city']?:'—') ?></td>
                <td><?= Helpers::e($c['region']?:'—') ?></td>
                <td>
                    <?php if ($c['has_conversion']): ?>
                    <span class="badge badge-success" style="font-size:10px">✓ <?= Helpers::e(ucfirst($c['conv_status'] ?? 'approved')) ?></span>
                    <?php else: ?>
                    <span class="badge badge-muted" style="font-size:10px;background:#F1F5F9;color:#94A3B8">No conv</span>
                    <?php endif; ?>
                </td>
                <?php if (Auth::role() === "admin"): ?><td><?= $c['has_conversion'] ? '$'.number_format((float)$c['revenue'],4) : '<span style="color:#CBD5E1">—</span>' ?></td><?php endif; ?>
                <td><?= $c['has_conversion'] ? '$'.number_format((float)$c['payout'],4)  : '<span style="color:#CBD5E1">—</span>' ?></td>
                <td><?php if ($c['has_conversion']): ?><span style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,4) ?></span><?php else: ?><span style="color:#CBD5E1">—</span><?php endif; ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s',strtotime($c['clicked_at'])) ?></td>
                <td>
                    <a href="/admin/fraud?click_id=<?= urlencode($c['click_id']) ?>" class="btn btn-secondary btn-sm">Fraud Log</a>
                    <button type="button" class="btn btn-secondary btn-sm" style="border-color:#7C3AED;color:#7C3AED" onclick="openScoreModal('<?= Helpers::e($c['ip_address']) ?>')">🛡️ Score</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════ CONVERSIONS / REJECTED / PENDING / AUTOHIDE TABS ═══════════════════ */ ?>
<?php elseif (in_array($tab, ['conversions','rejected','pending','autohide'])): ?>

<?php
$tabLabel = ['conversions'=>'Approved Conversions','rejected'=>'Rejected Conversions','pending'=>'Pending Conversions','autohide'=>'Hidden Conversions (Auto-hide / Fraud)'][$tab];
$totPayout   = array_sum(array_column($convRows,'payout'));
$totRevenue  = array_sum(array_column($convRows,'revenue'));
$totProfit   = $totRevenue - $totPayout;
$postbackSent = count(array_filter($convRows, fn($r) => $r['postback_sent']));
?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Count</div><div class="stat-value"><?= number_format(count($convRows)) ?></div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($totPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($totRevenue,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= $totProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($totProfit,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Postback Sent</div><div class="stat-value"><?= number_format($postbackSent) ?></div></div>
</div>

<?php
$notSent = count($convRows) - $postbackSent;
if ($notSent > 0 && in_array($tab, ['conversions','pending'])):
    $refireBack = '/admin/reports?' . http_build_query(array_filter($_GET));
?>
<form method="POST" action="/admin/conversions" style="margin-bottom:12px" onsubmit="return confirm('Re-fire postbacks for <?= $notSent ?> unsent conversion(s)?')">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="action" value="refire_all_postbacks">
    <input type="hidden" name="from" value="<?= Helpers::e($from) ?>">
    <input type="hidden" name="to" value="<?= Helpers::e($to) ?>">
    <input type="hidden" name="redirect_back" value="<?= Helpers::e($refireBack) ?>">
    <button class="btn btn-warning">&#8635; Re-fire All Unsent Postbacks (<?= $notSent ?>)</button>
</form>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><?= $tabLabel ?></span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-conv" style="font-size:12px">
            <?php
            // Device brand/model parser (shared by all rows)
            $knownBrands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
            function parseDeviceInfo(string $ua, array $knownBrands): array {
                $brand = ''; $model = '';
                if ($ua === '') return [$brand, $model];
                if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $ua, $m)) {
                    $raw = trim($m[1]); $b = '';
                    foreach ($knownBrands as $kb) { if (stripos($raw,$kb)===0){$b=$kb;break;} }
                    if ($b){$brand=$b;$model=trim(substr($raw,strlen($b)))?:$raw;}
                    else{$brand='Android';$model=$raw;}
                } elseif (stripos($ua,'iPhone')!==false){$brand='Apple';$model='iPhone';}
                elseif (stripos($ua,'iPad')!==false){$brand='Apple';$model='iPad';}
                elseif (stripos($ua,'Macintosh')!==false){$brand='Apple';$model='Mac';}
                elseif (stripos($ua,'Windows')!==false){$brand='PC';$model='Windows';}
                return [$brand, $model];
            }
            ?>
            <thead>
                <tr>
                    <th>OFFER</th><th>AFFILIATE</th><th>CLICK ID</th><th>CONVERSION ID</th>
                    <th>AFF CLICK ID</th><th>AFF SUB 2</th><th>STATUS</th>
                    <th>PAYOUT</th><th>REVENUE</th><th>PROFIT</th>
                    <th>GOAL</th><th>TXN ID</th><th>COUNTRY</th><th>CITY</th><th>STATE</th><th>OS</th><th>BROWSER</th>
                    <th>CONV IP</th><th>USER AGENT</th>
                    <th>DEVICE BRAND</th><th>DEVICE MODEL</th>
                    <th>CATEGORY</th><th>PRELAND</th><th>LP NAME</th><th>OFFER PAGE</th><th>FLOW ID</th>
                    <th>CR (VISIT)</th><th>CR (CLICK)</th><th>CR (UNIQUE)</th><th>CTR</th>
                    <th>IPQS SCORE</th><th>POSTBACK</th><th>CONVERTED AT</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($convRows as $r):
                $profit = (float)$r['revenue'] - (float)$r['payout'];
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];

                // Device brand / model
                [$dBrand, $dModel] = parseDeviceInfo($r['user_agent'] ?? '', $knownBrands);

                // Preland: resolve landing page URL + Name from index
                $preland = '—';
                $prelandName = '';
                if ($r['landing_page_idx'] !== null) {
                    $lpArr  = !empty($r['offer_landing_pages'])      ? json_decode($r['offer_landing_pages'], true)      : null;
                    $lpNArr = !empty($r['offer_landing_page_names']) ? json_decode($r['offer_landing_page_names'], true) : null;
                    $_idx   = (int)$r['landing_page_idx'];
                    if (is_array($lpArr)  && isset($lpArr[$_idx]))  $preland     = $lpArr[$_idx];
                    if (is_array($lpNArr) && isset($lpNArr[$_idx])) $prelandName = $lpNArr[$_idx];
                }

                // CR / CTR from pre-computed offer stats map
                $oid = (int)$r['offer_id'];
                $os  = $offerStatMap[$oid] ?? [];
                $crVisit  = (!empty($os['total_impr'])   && (int)$os['total_impr']  > 0) ? round((int)$os['total_conv']/(int)$os['total_impr']*100,2).'%'  : '—';
                $crClick  = (!empty($os['total_clicks']) && (int)$os['total_clicks']> 0) ? round((int)$os['total_conv']/(int)$os['total_clicks']*100,2).'%' : '—';
                $crUnique = (!empty($os['total_unique']) && (int)$os['total_unique']> 0) ? round((int)$os['total_conv']/(int)$os['total_unique']*100,2).'%' : '—';
                $ctr      = (!empty($os['total_impr'])   && (int)$os['total_impr']  > 0) ? round((int)$os['total_clicks']/(int)$os['total_impr']*100,2).'%': '—';
            ?>
            <tr>
                <td class="fw-bold"><?= Helpers::e($r['offer_name'] ?: '— Custom URL —') ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1']?:'—') ?></td>
                <td><?= Helpers::e($r['sub2']?:'—') ?></td>
                <td><span class="badge badge-<?= $bm[$r['status']]??'muted' ?>"><?= $r['status'] ?></span>
                    <?php if ($r['is_fraud']): ?><span class="badge badge-danger" style="margin-left:2px">fraud</span><?php endif; ?>
                    <?php if ($r['status'] === 'rejected' && !empty($r['rejection_reason'])): ?>
                    <div style="margin-top:3px;font-size:10px;color:#b91c1c;line-height:1.35;max-width:160px;white-space:normal"
                         title="<?= Helpers::e($r['rejection_reason']) ?>">
                        <strong>Reason:</strong> <?= Helpers::e(mb_strlen($r['rejection_reason'])>40?mb_substr($r['rejection_reason'],0,40).'…':$r['rejection_reason']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($r['rejected_at'])): ?>
                    <div style="font-size:9px;color:#94A3B8;margin-top:2px">Rejected <?= Helpers::e(date('M j H:i', strtotime($r['rejected_at']))) ?></div>
                    <?php endif; ?>
                </td>
                <td>$<?= number_format((float)$r['payout'],4) ?></td>
                <?php if (Auth::role() === "admin"): ?><td>$<?= number_format((float)$r['revenue'],4) ?></td><?php endif; ?>
                <td style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,4) ?></td>
                <td><?= Helpers::e($r['goal_name']?:'—') ?></td>
                <td class="text-sm text-muted"><?= Helpers::e($r['transaction_id']?:'—') ?></td>
                <td><?php if (!empty($r['country'])): ?><img src="https://flagcdn.com/16x12/<?= strtolower($r['country']) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?= Helpers::e($r['country']) ?><?php else: ?>—<?php endif; ?></td>
                <td><?= Helpers::e($r['city'] ?: '—') ?></td>
                <td><?= Helpers::e($r['region'] ?: '—') ?></td>
                <td><?= Helpers::e($r['os']?:'—') ?></td>
                <td><?= Helpers::e($r['browser']?:'—') ?></td>
                <td style="font-family:monospace;font-size:11px;white-space:nowrap">
                    <?= $r['conv_ip'] ? Helpers::e($r['conv_ip']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td style="max-width:220px">
                    <?php if (!empty($r['user_agent'])): ?>
                    <span title="<?= Helpers::e($r['user_agent']) ?>"
                          style="display:block;font-size:10px;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;cursor:help">
                        <?= Helpers::e($r['user_agent']) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($dBrand ?: '—') ?></td>
                <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= Helpers::e($dModel) ?>"><?= Helpers::e($dModel ?: '—') ?></td>
                <td><?= Helpers::e($r['offer_category'] ?? '—') ?></td>
<!-- placeholder -->
                <td style="max-width:160px">
                    <?php if ($preland !== '—'): ?>
                    <a href="<?= Helpers::e($preland) ?>" target="_blank" rel="noopener"
                       style="font-size:10px;color:#6366F1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:160px"
                       title="<?= Helpers::e($preland) ?>">
                        <?= Helpers::e(parse_url($preland, PHP_URL_HOST) ?: $preland) ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;font-weight:600">
                    <?php if ($prelandName !== ''): ?><?= Helpers::e($prelandName) ?><?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td style="max-width:160px">
                    <?php if (!empty($r['offer_page'])): ?>
                    <a href="<?= Helpers::e($r['offer_page']) ?>" target="_blank" rel="noopener"
                       style="font-size:10px;color:#6366F1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:160px"
                       title="<?= Helpers::e($r['offer_page']) ?>">
                        <?= Helpers::e(parse_url($r['offer_page'], PHP_URL_HOST) ?: $r['offer_page']) ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($r['flow_id'] ? (string)$r['flow_id'] : '—') ?></td>
                <td style="text-align:right;white-space:nowrap"><?= $crVisit ?></td>
                <td style="text-align:right;white-space:nowrap"><?= $crClick ?></td>
                <td style="text-align:right;white-space:nowrap"><?= $crUnique ?></td>
                <td style="text-align:right;white-space:nowrap"><?= $ctr ?></td>
                <td>
                    <?php
                    $fraudChecked = !empty($r['fraud_checked_at']);
                    $fs = $fraudChecked ? (int)($r['fraud_score'] ?? 0) : null;
                    ?>
                    <?php if ($fs === null): ?>
                    <span style="font-size:11px;padding:2px 7px;border-radius:8px;background:#FEF3C7;color:#92400E;font-weight:600" title="IPQS check pending">&#9203; Pending</span>
                    <?php elseif ($fs >= 75): ?>
                    <span class="badge badge-danger" title="IPQS Score: <?= $fs ?> — High Risk"><?= $fs ?></span>
                    <?php elseif ($fs >= 40): ?>
                    <span class="badge badge-warning" title="IPQS Score: <?= $fs ?> — Medium Risk"><?= $fs ?></span>
                    <?php elseif ($fs > 0): ?>
                    <span class="badge badge-muted" title="IPQS Score: <?= $fs ?> — Low Risk"><?= $fs ?></span>
                    <?php else: ?>
                    <span style="font-size:11px;padding:2px 7px;border-radius:8px;background:#ECFDF5;color:#065F46;font-weight:600" title="IPQS Score: 0 — Clean IP">&#10003; 0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['postback_sent']): ?>
                    <span class="badge badge-success">&#10003; Sent</span>
                    <?php else: ?>
                    <span class="badge badge-danger">&#10007; No</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($r['converted_at'])) ?></td>
                <td style="white-space:nowrap">
                    <?php
                    // Same return-to-Reports path used by every action button below.
                    $_back = '/admin/reports?' . http_build_query(array_filter($_GET));
                    ?>
                    <a href="/admin/conversions?id=<?= $r['conversion_id'] ?>" class="btn btn-secondary btn-sm">View</a>

                    <?php if ($r['status'] !== 'approved'): ?>
                    <form method="POST" action="/admin/conversions" style="display:inline"
                          onsubmit="return confirm('Approve this conversion?\nPayout $<?= number_format((float)$r['payout'],2) ?> will be credited to the affiliate.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($r['conversion_id']) ?>">
                        <input type="hidden" name="status" value="approved">
                        <input type="hidden" name="redirect_back" value="<?= Helpers::e($_back) ?>">
                        <button class="btn btn-success btn-sm" style="white-space:nowrap">&#10003; Approved</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($r['status'] !== 'rejected'): ?>
                    <button type="button" class="btn btn-danger btn-sm" style="white-space:nowrap"
                            onclick="openRejectModal('<?= Helpers::e($r['conversion_id']) ?>')">&#10005; Reject</button>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'approved'): ?>
                    <form method="POST" action="/admin/conversions" style="display:inline"
                          onsubmit="return confirm('Mark this conversion as chargebacked?\nPayout $<?= number_format((float)$r['payout'],2) ?> will be deducted from the affiliate.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($r['conversion_id']) ?>">
                        <input type="hidden" name="status" value="chargebacked">
                        <input type="hidden" name="redirect_back" value="<?= Helpers::e($_back) ?>">
                        <button class="btn btn-warning btn-sm" style="white-space:nowrap">&#8617; CB</button>
                    </form>
                    <?php endif; ?>

                    <?php if (!$r['postback_sent']): ?>
                    <form method="POST" action="/admin/conversions" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="refire_postback">
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($r['conversion_id']) ?>">
                        <input type="hidden" name="redirect_back" value="<?= Helpers::e($_back) ?>">
                        <button class="btn btn-warning btn-sm" title="Re-fire postback to affiliate tracker" onclick="return confirm('Re-fire postback for this conversion?')">&#8635; Re-fire</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Reject-with-reason modal — submits to /admin/conversions with the same
// hidden fields the existing approve form uses (status='rejected'). After
// the controller saves the row it honours `redirect_back` (set above on
// every action button) so admins land back on this Reports tab.
$rejectFormAction  = '/admin/conversions';
$rejectStatusField = 'status';
$rejectStatusValue = 'rejected';
$rejectExtraHidden = ['redirect_back' => '/admin/reports?' . http_build_query(array_filter($_GET))];
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php /* ═══════════════════ OFFER REPORTS TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'offer_report'): ?>

<?php
$orTotals = $offerReportTotals;
$orProfit = (float)$orTotals['revenue'] - (float)$orTotals['payout'];
?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Offers</div><div class="stat-value"><?= number_format(count($offerReportRows)) ?></div></div>
    <div class="stat-card"><div class="stat-label">Impressions</div><div class="stat-value"><?= number_format($orTotals['impressions']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Clicks</div><div class="stat-value"><?= number_format($orTotals['clicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Unique</div><div class="stat-value"><?= number_format($orTotals['uclicks']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($orTotals['conversions']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Approved</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($orTotals['approved']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Rejected</div><div class="stat-value" style="color:var(--danger)"><?= number_format($orTotals['rejected']) ?></div></div>
    <div class="stat-card" title="Conversions with IPQualityScore (IPQS) fraud_score ≥ 60. Visibility overlay only — never modifies payout / revenue."><div class="stat-label">Fraud Conv. (IPQS)</div><div class="stat-value" style="color:#DC2626"><?= number_format($orTotals['fraud_conv'] ?? 0) ?></div><div class="stat-sub">Real-time</div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($orTotals['payout'],2) ?></div></div>
    <?php if (Auth::role() === "admin"): ?><div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($orTotals['revenue'],2) ?></div></div><?php endif; ?>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= $orProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($orProfit,2) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Offer Reports</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-offer-report" style="font-size:12px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>OFFER</th>
                    <th>STATUS</th>
                    <th>CATEGORY</th>
                    <th>TYPE</th>
                    <th>RATE</th>
                    <th>DAILY CAP</th>
                    <th>TOTAL CAP</th>
                    <th>AFFILIATES</th>
                    <th>IMPRESSIONS</th>
                    <th>CLICKS</th>
                    <th>UNIQUE</th>
                    <th>CONV.</th>
                    <th>APPROVED</th>
                    <th>REJECTED</th>
                    <th>FRAUD</th>
                    <th title="Conversions with IPQualityScore (IPQS) fraud_score ≥ 60">FRAUD CONV.</th>
                    <th>CR%</th>
                    <th>EPC</th>
                    <th>PAYOUT</th>
                    <th>REVENUE</th>
                    <th>PROFIT</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($offerReportRows)): ?>
            <tr><td colspan="23" class="text-center text-muted" style="padding:32px">No offer data for the selected date range and filters.</td></tr>
            <?php else: foreach ($offerReportRows as $i => $r):
                $cr     = $r['clicks'] > 0 ? round($r['conversions'] / $r['clicks'] * 100, 2) : 0;
                $epc    = $r['clicks'] > 0 ? round($r['payout'] / $r['clicks'], 4) : 0;
                $profit = (float)$r['revenue'] - (float)$r['payout'];
                $statusColor = ['active'=>'var(--secondary)','paused'=>'#F59E0B','expired'=>'#94A3B8','pending'=>'var(--primary)'][$r['offer_status']] ?? '#94A3B8';
                $statusDot   = ['active'=>'success','paused'=>'warning','expired'=>'muted','pending'=>'info'][$r['offer_status']] ?? 'muted';
            ?>
            <tr>
                <td class="text-muted" style="font-size:11px"><?= $i + 1 ?></td>
                <td style="white-space:nowrap;min-width:180px">
                    <div class="fw-bold"><?= Helpers::e($r['offer_name']) ?></div>
                    <div style="font-size:10px;color:#94A3B8">#<?= (int)$r['offer_id'] ?></div>
                </td>
                <td>
                    <span class="badge badge-<?= $statusDot ?>" style="font-size:10px">
                        <?= ucfirst($r['offer_status']) ?>
                    </span>
                </td>
                <td><?= Helpers::e($r['category'] ?: '—') ?></td>
                <td>
                    <span style="display:inline-block;background:#EEF2FF;color:#4338CA;border-radius:4px;padding:2px 7px;font-size:10px;font-weight:700">
                        <?= Helpers::e($r['payout_type']) ?>
                    </span>
                </td>
                <td style="white-space:nowrap">
                    <span style="color:var(--secondary);font-weight:700">$<?= number_format((float)$r['payout_amount'],2) ?></span>
                    <?php if ((float)$r['revenue_amount'] > 0): ?>
                    <?php if (Auth::role() === "admin"): ?><span style="font-size:10px;color:#94A3B8"> / $<?= number_format((float)$r['revenue_amount'],2) ?></span><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <?php if ($r['daily_cap'] > 0): ?>
                    <span style="font-size:11px;color:#475569"><?= number_format($r['daily_cap']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">∞</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <?php if ($r['total_cap'] > 0): ?>
                    <span style="font-size:11px;color:#475569"><?= number_format($r['total_cap']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">∞</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-info" style="background:#EFF6FF;color:#1D4ED8"><?= (int)$r['aff_count'] ?></span>
                </td>
                <td style="text-align:right"><?= number_format($r['impressions']) ?></td>
                <td style="text-align:right;font-weight:600"><?= number_format($r['clicks']) ?></td>
                <td style="text-align:right"><?= number_format($r['uclicks']) ?></td>
                <td style="text-align:right;font-weight:600"><?= number_format($r['conversions']) ?></td>
                <td style="text-align:right;color:var(--secondary)"><?= number_format($r['approved']) ?></td>
                <td style="text-align:right;color:var(--danger)"><?= number_format($r['rejected']) ?></td>
                <td style="text-align:right">
                    <?php if ($r['fraud_clicks'] > 0): ?>
                    <span style="color:#DC2626;font-weight:600"><?= number_format($r['fraud_clicks']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right" title="IPQualityScore (IPQS) fraud-score ≥ 60">
                    <?php $_frCv = (int)($r['fraud_conv'] ?? 0); if ($_frCv > 0): ?>
                    <span style="color:#DC2626;font-weight:700"><?= number_format($_frCv) ?></span>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;white-space:nowrap">
                    <span style="font-weight:700;color:<?= $cr>=5?'var(--secondary)':($cr>=1?'#F59E0B':'#94A3B8') ?>">
                        <?= $cr ?>%
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap">$<?= number_format($epc,4) ?></td>
                <td style="text-align:right;white-space:nowrap;font-weight:600">$<?= number_format((float)$r['payout'],2) ?></td>
                <td style="text-align:right;white-space:nowrap">$<?= number_format((float)$r['revenue'],2) ?></td>
                <td style="text-align:right;white-space:nowrap;color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>;font-weight:700">
                    $<?= number_format($profit,2) ?>
                </td>
                <td style="white-space:nowrap">
                    <a href="/admin/offers/<?= (int)$r['offer_id'] ?>/overview" class="btn btn-secondary btn-sm">Overview</a>
                    <a href="?tab=conversions&offer_id=<?= (int)$r['offer_id'] ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" class="btn btn-secondary btn-sm">Conversions</a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($offerReportRows)): ?>
            <tfoot>
                <tr style="background:#F8FAFC;font-weight:700;font-size:12px">
                    <td colspan="9" style="text-align:right;padding-right:12px">TOTALS</td>
                    <td style="text-align:right"><?= number_format($orTotals['impressions']) ?></td>
                    <td style="text-align:right"><?= number_format($orTotals['clicks']) ?></td>
                    <td style="text-align:right"><?= number_format($orTotals['uclicks']) ?></td>
                    <td style="text-align:right"><?= number_format($orTotals['conversions']) ?></td>
                    <td style="text-align:right;color:var(--secondary)"><?= number_format($orTotals['approved']) ?></td>
                    <td style="text-align:right;color:var(--danger)"><?= number_format($orTotals['rejected']) ?></td>
                    <td style="text-align:right"><?= number_format($orTotals['fraud_clicks']) ?></td>
                    <td style="text-align:right"><?= $orTotals['clicks'] > 0 ? round($orTotals['conversions']/$orTotals['clicks']*100,2) : 0 ?>%</td>
                    <td style="text-align:right">$<?= $orTotals['clicks'] > 0 ? number_format($orTotals['payout']/$orTotals['clicks'],4) : '0.0000' ?></td>
                    <td style="text-align:right">$<?= number_format($orTotals['payout'],2) ?></td>
                    <td style="text-align:right">$<?= number_format($orTotals['revenue'],2) ?></td>
                    <td style="text-align:right;color:<?= $orProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($orProfit,2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php /* ═══════════════════ POSTBACK LOG TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'postback'): ?>

<?php
$totalFired   = count($postbackRows);
$successCount = count(array_filter($postbackRows, fn($r)=>$r['is_success']));
$failCount    = $totalFired - $successCount;
?>

<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Total Fired</div><div class="stat-value"><?= number_format($totalFired) ?></div></div>
    <div class="stat-card"><div class="stat-label">Success</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($successCount) ?></div></div>
    <div class="stat-card"><div class="stat-label">Failed</div><div class="stat-value" style="color:var(--danger)"><?= number_format($failCount) ?></div></div>
    <div class="stat-card"><div class="stat-label">Success Rate</div><div class="stat-value"><?= $totalFired>0?round($successCount/$totalFired*100,1):0 ?>%</div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Postback Fire Log</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-postback" style="font-size:12px">
            <thead>
                <tr>
                    <th>#</th><th>TYPE</th><th>AFFILIATE</th><th>OFFER</th><th>EVENT</th><th>METHOD</th>
                    <th>CONVERSION ID</th><th>CONV STATUS</th><th>HTTP</th><th>SUCCESS</th>
                    <th>ATTEMPTS</th><th>FIRED URL</th><th>RESPONSE</th><th>FIRED AT</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($postbackRows)): ?>
            <tr><td colspan="14" class="text-center text-muted" style="padding:32px">No postback logs for selected filters</td></tr>
            <?php else: foreach ($postbackRows as $r):
                $isGlobal = ((int)($r['postback_id'] ?? 1) === 0);
            ?>
            <tr style="<?= !$r['is_success']?'background:#FFF5F5':'' ?>">
                <td class="text-muted"><?= $r['id'] ?></td>
                <td>
                    <?php if ($isGlobal): ?>
                    <span class="badge" style="background:#EEF2FF;color:#4F46E5;font-size:10px">&#127760; Global</span>
                    <?php else: ?>
                    <span class="badge badge-muted" style="font-size:10px">Affiliate</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($r['aff_name'] ?? '—') ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_code'] ?? '') ?></div>
                </td>
                <td><?= Helpers::e($r['offer_name']?:'All') ?></td>
                <td><span class="badge badge-<?= ($r['event']??'')==='conversion'?'success':(($r['event']??'')==='click'?'info':'danger') ?>"><?= Helpers::e($r['event'] ?? 'conversion') ?></span></td>
                <td><span class="badge badge-muted"><?= Helpers::e($r['method'] ?? 'GET') ?></span></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                <td><?php if (!empty($r['conv_status'])):
                    $bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
                    ?><span class="badge badge-<?= $bm[$r['conv_status']]??'muted' ?>"><?= $r['conv_status'] ?></span><?php else: ?>—<?php endif; ?></td>
                <td>
                    <?php $httpCode = (int)($r['http_status'] ?? 0); ?>
                    <span style="font-weight:600;color:<?= $httpCode>=200&&$httpCode<300?'var(--secondary)':($httpCode>0?'var(--danger)':'#999') ?>">
                        <?= $httpCode ?: '—' ?>
                    </span>
                </td>
                <td><?= $r['is_success'] ? '<span class="badge badge-success">&#10003; OK</span>' : '<span class="badge badge-danger">&#10007; Fail</span>' ?></td>
                <td><?= (int)($r['attempt_count'] ?? 1) ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($r['fired_url']) ?>">
                    <code style="font-size:10px"><?= Helpers::e(substr($r['fired_url'],0,80)) ?>…</code>
                </td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($r['response_body']??'') ?>">
                    <?= Helpers::e(substr($r['response_body']??'',0,60)) ?>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s',strtotime($r['fired_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════ SMARTLINK CLICKS TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'sl_clicks'): ?>

<?php
$_slTotalClicks    = count($slClicks ?? []);
$_slFraudCount     = count(array_filter($slClicks ?? [], fn($r) => $r['is_fraud']));
$_slConvCount      = count(array_filter($slClicks ?? [], fn($r) => $r['has_conversion']));
$_slTotRevenue     = array_sum(array_column($slClicks ?? [], 'revenue'));
$_slTotPayout      = array_sum(array_column($slClicks ?? [], 'payout'));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($_slTotalClicks) ?></div></div>
    <div class="stat-card"><div class="stat-label">Fraud</div><div class="stat-value" style="color:var(--danger)"><?= number_format($_slFraudCount) ?></div><div class="stat-sub"><?= $_slTotalClicks>0?round($_slFraudCount/$_slTotalClicks*100,1):0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Converted</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($_slConvCount) ?></div><div class="stat-sub"><?= $_slTotalClicks>0?round($_slConvCount/$_slTotalClicks*100,1):0 ?>% CR</div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($_slTotRevenue,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($_slTotPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= ($_slTotRevenue-$_slTotPayout)>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($_slTotRevenue-$_slTotPayout,2) ?></div></div>
</div>
<div class="card">
    <div class="card-header">
        <span class="card-title">SmartLink Click Log</span>
        <span class="text-muted text-sm"><?= number_format($_slTotalClicks) ?> rows · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-clicks" style="font-size:12px;min-width:1600px">
            <thead>
                <tr>
                    <th>SMARTLINK</th><th>OFFER</th><th>AFFILIATE</th><th>CLICK ID</th>
                    <th>SUB1</th><th>SUB2</th><th>SOURCE</th>
                    <th>FRAUD</th><th>OS</th><th>BROWSER</th><th>DEVICE</th>
                    <th>IP</th><th>COUNTRY</th><th>CITY</th>
                    <th>CONVERTED</th><th>REVENUE</th><th>PAYOUT</th><th>CLICK TIME</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($slClicks)): ?>
            <tr><td colspan="18" class="text-center text-muted" style="padding:32px">No SmartLink clicks for selected filters</td></tr>
            <?php else: foreach ($slClicks as $c):
                $isFraud = (bool)$c['is_fraud'];
            ?>
            <tr style="<?= $isFraud?'background:#FFF5F5':'' ?>">
                <td class="fw-bold" style="white-space:nowrap">
                    <span style="display:inline-block;background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700"><?= Helpers::e($c['smartlink_name']) ?></span>
                </td>
                <td style="white-space:nowrap;font-size:11px"><?= Helpers::e($c['offer_name']) ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($c['click_id']) ?></code></td>
                <td><?= Helpers::e($c['sub1']?:'—') ?></td>
                <td><?= Helpers::e($c['sub2']?:'—') ?></td>
                <td style="max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helpers::e($c['source']?:'—') ?></td>
                <td><?= $isFraud ? '<span class="badge badge-danger">Fraud '.(int)$c['fraud_score'].'</span>' : '<span class="badge badge-success">Clean</span>' ?></td>
                <td><?= Helpers::e($c['os']?:'—') ?></td>
                <td><?= Helpers::e($c['browser']?:'—') ?></td>
                <td><?= Helpers::e($c['device_type']?:'—') ?></td>
                <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address']) ?></td>
                <td><?php if (!empty($c['country'])): ?><img src="https://flagcdn.com/16x12/<?= strtolower($c['country']) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?= Helpers::e($c['country']) ?><?php else: ?>—<?php endif; ?></td>
                <td><?= Helpers::e($c['city']?:'—') ?></td>
                <td>
                    <?php if ($c['has_conversion']): ?>
                    <span class="badge badge-success" style="font-size:10px">&#10003; <?= Helpers::e(ucfirst($c['conv_status'] ?? 'approved')) ?></span>
                    <?php else: ?>
                    <span class="badge badge-muted" style="font-size:10px;background:#F1F5F9;color:#94A3B8">No conv</span>
                    <?php endif; ?>
                </td>
                <?php if (Auth::role() === "admin"): ?><td><?= $c['has_conversion'] ? '$'.number_format((float)$c['revenue'],4) : '<span style="color:#CBD5E1">—</span>' ?></td><?php endif; ?>
                <td><?= $c['has_conversion'] ? '$'.number_format((float)$c['payout'],4)  : '<span style="color:#CBD5E1">—</span>' ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($c['clicked_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════ SMARTLINK CONVERSIONS TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'sl_conversions'): ?>

<?php
$_slcTotPayout  = array_sum(array_column($slConversions ?? [], 'payout'));
$_slcTotRevenue = array_sum(array_column($slConversions ?? [], 'revenue'));
$_slcTotProfit  = $_slcTotRevenue - $_slcTotPayout;
$_slcPostbacks  = count(array_filter($slConversions ?? [], fn($r) => $r['postback_sent']));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Count</div><div class="stat-value"><?= number_format(count($slConversions ?? [])) ?></div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($_slcTotPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($_slcTotRevenue,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= $_slcTotProfit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($_slcTotProfit,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Postback Sent</div><div class="stat-value"><?= number_format($_slcPostbacks) ?></div></div>
</div>
<div class="card">
    <div class="card-header">
        <span class="card-title">SmartLink Conversion Log</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-conv" style="font-size:12px">
            <thead>
                <tr>
                    <th>SMARTLINK</th><th>OFFER</th><th>AFFILIATE</th>
                    <th>CLICK ID</th><th>CONVERSION ID</th>
                    <th>SUB1</th><th>SUB2</th><th>STATUS</th>
                    <th>PAYOUT</th><th>REVENUE</th><th>PROFIT</th>
                    <th>GOAL</th><th>TXN ID</th><th>COUNTRY</th><th>CITY</th><th>STATE</th><th>OS</th><th>BROWSER</th><th>DEVICE</th>
                    <th>POSTBACK</th><th>CONVERTED AT</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($slConversions)): ?>
            <tr><td colspan="21" class="text-center text-muted" style="padding:32px">No SmartLink conversions for selected filters</td></tr>
            <?php else: foreach ($slConversions as $r):
                $profit = (float)$r['revenue'] - (float)$r['payout'];
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr>
                <td class="fw-bold" style="white-space:nowrap">
                    <span style="display:inline-block;background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700"><?= Helpers::e($r['smartlink_name']) ?></span>
                </td>
                <td style="font-size:11px"><?= Helpers::e($r['offer_name']) ?></td>
                <td style="white-space:nowrap">
                    <div><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['click_id']) ?></code></td>
                <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['conversion_id']) ?></code></td>
                <td><?= Helpers::e($r['sub1']?:'—') ?></td>
                <td><?= Helpers::e($r['sub2']?:'—') ?></td>
                <td><span class="badge badge-<?= $bm[$r['status']]??'muted' ?>"><?= $r['status'] ?></span></td>
                <td>$<?= number_format((float)$r['payout'],4) ?></td>
                <?php if (Auth::role() === "admin"): ?><td>$<?= number_format((float)$r['revenue'],4) ?></td><?php endif; ?>
                <td style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,4) ?></td>
                <td><?= Helpers::e($r['goal_name']?:'—') ?></td>
                <td class="text-muted" style="font-size:11px"><?= Helpers::e($r['transaction_id']?:'—') ?></td>
                <td><?php if (!empty($r['country'])): ?><img src="https://flagcdn.com/16x12/<?= strtolower($r['country']) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?= Helpers::e($r['country']) ?><?php else: ?>—<?php endif; ?></td>
                <td><?= Helpers::e($r['city']??'—') ?></td>
                <td><?= Helpers::e($r['region']??'—') ?></td>
                <td><?= Helpers::e($r['os']?:'—') ?></td>
                <td><?= Helpers::e($r['browser']?:'—') ?></td>
                <td><?= Helpers::e($r['device_type']?:'—') ?></td>
                <td><?= $r['postback_sent'] ? '<span class="badge badge-success">&#10003; Sent</span>' : '<span class="badge badge-danger">&#10007; No</span>' ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i',strtotime($r['converted_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ═══════════════════ SMARTLINK AFFILIATES TAB ═══════════════════ */ ?>
<?php elseif ($tab === 'sl_affiliates'): ?>

<?php
$_slaRows      = $slAffiliates ?? [];
$_slaTotClicks = array_sum(array_column($_slaRows, 'clicks'));
$_slaConvs     = array_sum(array_column($_slaRows, 'conversions'));
$_slaPayout    = array_sum(array_column($_slaRows, 'payout'));
$_slaRevenue   = array_sum(array_column($_slaRows, 'revenue'));
?>
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Affiliates</div><div class="stat-value"><?= number_format(count(array_unique(array_column($_slaRows,'aff_id')))) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Clicks</div><div class="stat-value"><?= number_format($_slaTotClicks) ?></div></div>
    <div class="stat-card"><div class="stat-label">Conversions</div><div class="stat-value"><?= number_format($_slaConvs) ?></div></div>
    <div class="stat-card"><div class="stat-label">CR%</div><div class="stat-value"><?= $_slaTotClicks>0?round($_slaConvs/$_slaTotClicks*100,2):0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Payout</div><div class="stat-value">$<?= number_format($_slaPayout,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">$<?= number_format($_slaRevenue,2) ?></div></div>
    <div class="stat-card"><div class="stat-label">Profit</div><div class="stat-value" style="color:<?= ($_slaRevenue-$_slaPayout)>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($_slaRevenue-$_slaPayout,2) ?></div></div>
</div>
<div class="card">
    <div class="card-header">
        <span class="card-title">SmartLink Affiliate Performance</span>
        <span class="text-muted text-sm"><?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-sl-aff" style="font-size:12px">
            <thead>
                <tr>
                    <th>AFFILIATE</th><th>SMARTLINK</th>
                    <th>CLICKS</th><th>UNIQUE</th><th>FRAUD</th>
                    <th>CONVERSIONS</th><th>APPROVED</th><th>PENDING</th>
                    <th>REJECTED</th>
                    <th title="Conversions with IPQualityScore (IPQS) fraud_score ≥ 60">FRAUD CONV.</th>
                    <th>CR%</th><th>EPC</th>
                    <th>PAYOUT</th><th>REVENUE</th><th>PROFIT</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($_slaRows)): ?>
            <tr><td colspan="15" class="text-center text-muted" style="padding:32px">No SmartLink affiliate data for selected filters</td></tr>
            <?php else: foreach ($_slaRows as $r):
                $cr     = $r['clicks'] > 0 ? round($r['conversions'] / $r['clicks'] * 100, 2) : 0;
                $epc    = $r['clicks'] > 0 ? round($r['payout']      / $r['clicks'], 4)        : 0;
                $profit = (float)$r['revenue'] - (float)$r['payout'];
            ?>
            <tr>
                <td style="white-space:nowrap">
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:10px"><?= Helpers::e($r['affiliate_code']) ?></div>
                </td>
                <td>
                    <span style="display:inline-block;background:#EEF2FF;color:#4F46E5;border-radius:4px;padding:1px 6px;font-size:11px;font-weight:700"><?= Helpers::e($r['smartlink_name']) ?></span>
                </td>
                <td><?= number_format($r['clicks']) ?></td>
                <td><?= number_format($r['uclicks']) ?></td>
                <td><?= (int)$r['fraud_clicks'] > 0 ? '<span style="color:var(--danger)">'.(int)$r['fraud_clicks'].'</span>' : '0' ?></td>
                <td><?= number_format($r['conversions']) ?></td>
                <td style="color:var(--secondary)"><?= number_format($r['approved']) ?></td>
                <td style="color:#D97706"><?= number_format($r['pending_conv']) ?></td>
                <td style="color:var(--danger)"><?= number_format((int)($r['rejected_conv'] ?? 0)) ?></td>
                <td title="IPQualityScore (IPQS) fraud-score ≥ 60"><?php $_frCv = (int)($r['fraud_conv'] ?? 0); ?><?php if ($_frCv > 0): ?><span style="color:#DC2626;font-weight:700"><?= number_format($_frCv) ?></span><?php else: ?>0<?php endif; ?></td>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td>$<?= number_format((float)$r['payout'],2) ?></td>
                <td>$<?= number_format((float)$r['revenue'],2) ?></td>
                <td style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,2) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
// ── Performance tab: IPQS panel toggle ───────────────────────────────────
function togglePerfPanel(id) {
    var row = document.getElementById(id);
    if (!row) return;
    var chv = document.getElementById(id + '-chv');
    var open = row.style.display !== 'none';
    row.style.display = open ? 'none' : 'table-row';
    if (chv) chv.style.transform = open ? '' : 'rotate(180deg)';
}

// ── Real-time rejected conversion polling (performance tab, date/offer/affiliate) ──
(function () {
    var groupBy = <?= json_encode($groupBy ?? '') ?>;
    if (!['date','offer','affiliate'].includes(groupBy)) return;

    var POLL_MS  = 20000;   // 20 s between polls
    var ENDPOINT = '/admin/reports?tab=performance&action=rt_rejected'
                 + '&group_by=' + encodeURIComponent(groupBy)
                 + '&from='     + encodeURIComponent(<?= json_encode($from) ?>)
                 + '&to='       + encodeURIComponent(<?= json_encode($to) ?>)
                 + <?= json_encode($offerId > 0 ? '&offer_id='.(int)$offerId : '') ?>
                 + <?= json_encode($affId   > 0 ? '&affiliate_id='.(int)$affId : '') ?>;

    var timer = null;

    function poll() {
        if (document.hidden) { schedule(); return; }
        fetch(ENDPOINT, { method: 'GET' })
            .then(function(r){ return r.json(); })
            .then(function(d) {
                if (!d || !d.ok || !d.data) return;
                Object.keys(d.data).forEach(function(label) {
                    var cells = document.querySelectorAll('[data-perf-rejected="' + CSS.escape(label) + '"]');
                    cells.forEach(function(cell) {
                        var newVal = d.data[label].rejected;
                        var cur    = parseInt(cell.textContent.replace(/,/g,''), 10);
                        if (isNaN(cur) || cur === newVal) return;
                        // Flash highlight on change
                        cell.textContent = newVal.toLocaleString();
                        cell.style.transition = 'background-color .6s';
                        cell.style.backgroundColor = newVal > cur ? '#FEF2F2' : '#ECFDF5';
                        setTimeout(function(){ cell.style.backgroundColor = ''; }, 2000);
                    });
                });
                schedule();
            })
            .catch(function(){ schedule(); });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(poll, POLL_MS);
    }

    document.addEventListener('visibilitychange', function(){
        if (!document.hidden) poll();
    });

    // Kick off first poll after a short delay so page render is complete
    setTimeout(poll, 5000);
})();

$.fn.dataTable.ext.errMode = 'none';
function dtInit(id, opts) {
    var $t = $('#' + id);
    if (!$t.length) return;
    if (opts.scrollX) opts.scrollX = $t.find('tbody tr').length > 0;
    $t.DataTable(opts);
}
$(function() {
    dtInit('tbl-perf',    { destroy:true, pageLength:50, order:[], columnDefs:[{targets:12,orderable:false,searchable:false}], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data for selected filters'} });
    dtInit('tbl-clicks',  { destroy:true, pageLength:50, order:[[23,'desc']], scrollX:true, columnDefs:[{orderable:false,targets:[12,13]}], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No clicks for selected filters'} });
    dtInit('tbl-conv',    { destroy:true, pageLength:50, order:[[17,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data for selected filters'} });
    dtInit('tbl-postback',  { destroy:true, pageLength:50, order:[[12,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No postback logs found'} });
    dtInit('tbl-sl-clicks', { destroy:true, pageLength:50, order:[[17,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink clicks for selected filters'} });
    dtInit('tbl-sl-conv',   { destroy:true, pageLength:50, order:[[20,'desc']], scrollX:true, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink conversions for selected filters'} });
    // SmartLink Affiliate Performance: only 13 columns, fits without DataTables'
    // own scroll wrapper. Letting the parent .table-wrap (overflow-x:auto)
    // handle horizontal scroll fixes the header/body column misalignment that
    // scrollX:true was causing on this table.
    dtInit('tbl-sl-aff',    { destroy:true, pageLength:50, order:[[2,'desc']], autoWidth:false, language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No SmartLink affiliate data for selected filters'} });

    // Re-compute column widths after window resize / orientation change so the
    // headers stay perfectly aligned with the body cells at every viewport.
    var _resizeTimer = null;
    window.addEventListener('resize', function(){
        clearTimeout(_resizeTimer);
        _resizeTimer = setTimeout(function(){
            ['tbl-perf','tbl-clicks','tbl-conv','tbl-postback','tbl-sl-clicks','tbl-sl-conv','tbl-sl-aff'].forEach(function(id){
                if ($.fn.dataTable.isDataTable('#'+id)) $('#'+id).DataTable().columns.adjust();
            });
        }, 200);
    });
});
</script>

<?php require BASE_PATH . '/views/partials/ip_score_modal.php'; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
