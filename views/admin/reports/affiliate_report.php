<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Affiliate Report</h1>
        <p>Per-affiliate analytics, traffic detail &amp; conversion breakdown</p>
    </div>
    <?php $exportXlsEnabled = true; require BASE_PATH . '/views/partials/export_buttons.php'; ?>
</div>

<!-- ── Filter bar ─────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="aff-rep-filter" class="d-flex gap-3 align-center" style="flex-wrap:wrap">

            <?php $drpFromId='ar-from'; $drpToId='ar-to'; $drpFormId='aff-rep-filter'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>

            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="ar-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="ar-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>

            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select id="ar-aff-sel" name="affiliate_id" class="form-control" style="min-width:180px">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $affId == $a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['name']) ?> · <?= Helpers::e($a['affiliate_code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Aff Code</label>
                <input type="text" id="ar-aff-code" name="affiliate_code" class="form-control"
                       placeholder="AFFF06092C5" style="width:140px;font-family:monospace;font-size:13px;text-transform:uppercase"
                       value="<?= Helpers::e(Helpers::get('affiliate_code') ?? '') ?>">
            </div>

            <div class="form-group mb-0">
                <label>Affiliate Name</label>
                <input type="text" name="affiliate_name" class="form-control" style="width:160px"
                       value="<?= Helpers::e(Helpers::get('affiliate_name') ?? '') ?>" placeholder="name contains…">
            </div>

            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control" style="min-width:160px">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $offerId == $o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Country</label>
                <select name="country" class="form-control" style="min-width:130px">
                    <option value="">All</option>
                    <?php foreach ($countryList as $c): ?>
                    <option value="<?= Helpers::e($c['country']) ?>" <?= $country === $c['country'] ? 'selected' : '' ?>><?= Helpers::e($c['country']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Conversion Status</label>
                <select name="conv_status" class="form-control" style="min-width:130px">
                    <option value="">All</option>
                    <option value="approved" <?= $convStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="pending"  <?= $convStatus === 'pending'  ? 'selected' : '' ?>>Pending</option>
                    <option value="rejected" <?= $convStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Device</label>
                <select name="device" class="form-control" style="min-width:130px">
                    <option value="">All</option>
                    <?php foreach (['desktop','mobile','tablet','bot','unknown'] as $d): ?>
                    <option value="<?= $d ?>" <?= $device === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>Traffic Status</label>
                <select name="traffic_status" class="form-control" style="min-width:130px">
                    <option value="">All</option>
                    <option value="clean"   <?= $trafficSt === 'clean'   ? 'selected' : '' ?>>Clean</option>
                    <option value="fraud"   <?= $trafficSt === 'fraud'   ? 'selected' : '' ?>>Fraud</option>
                    <option value="blocked" <?= $trafficSt === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
            </div>

            <div class="form-group mb-0">
                <label>IP Address</label>
                <input type="text" name="ip" class="form-control" style="width:140px;font-family:monospace"
                       value="<?= Helpers::e($ipFilter) ?>" placeholder="e.g. 192.168.">
            </div>

            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000,10000] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= number_format($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply</button>
                <a href="/admin/reports/affiliates" class="btn btn-secondary">Clear</a>
            </div>

            <script>
            (function(){
                var sel=document.getElementById('ar-aff-sel'),txt=document.getElementById('ar-aff-code');
                if(!sel||!txt)return;
                if(txt.value){sel.value='';}
                sel.addEventListener('change',function(){if(this.value)txt.value='';});
                txt.addEventListener('input',function(){if(this.value.trim())sel.value='';});
            })();
            </script>
        </form>
    </div>
</div>

<!-- ── Per-affiliate aggregation table ────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Affiliate Performance</span>
        <span class="text-muted text-sm"><?= number_format(count($aggRows)) ?> affiliates · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-aff-rep" style="min-width:1900px;font-size:12px">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Status</th>
                    <th>Clicks</th><th>Unique</th><th>Fraud</th><th>Blocked</th>
                    <th>Conversions</th><th>Approved</th><th>Pending</th><th>Rejected</th>
                    <th>CR %</th><th>EPC</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><th>Profit</th><?php endif; ?>
                    <th>Traffic Quality</th><th>IPQS Fraud</th><th>Last Activity</th><th>Registered</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aggRows)): ?>
                <tr><td colspan="22" class="text-center text-muted" style="padding:32px">No affiliate activity for selected filters.</td></tr>
            <?php else: foreach ($aggRows as $r):
                $tc      = (int)$r['total_clicks'];
                $fb      = (int)$r['fraud_clicks'] + (int)$r['blocked_clicks'];
                $cr      = $tc > 0 ? round($r['conv_total'] / $tc * 100, 2) : 0;
                $epc     = $tc > 0 ? round($r['payout']     / $tc, 4)        : 0;
                $profit  = (float)$r['revenue'] - (float)$r['payout'];
                $tqRatio = $tc > 0 ? $fb / $tc : 0;
                $tqLabel = $tc === 0 ? '—' : ($tqRatio >= 0.20 ? 'Low' : ($tqRatio >= 0.05 ? 'Medium' : 'High'));
                $tqColor = ['Low'=>'#EF4444','Medium'=>'#F59E0B','High'=>'#10B981','—'=>'#94A3B8'][$tqLabel];
                $lastAct = $r['last_click_at'] ?: $r['last_conv_at'] ?: $r['last_login'];
                $userSt  = strtolower((string)$r['user_status']);
                $userBadge = ['active'=>'badge-success','pending'=>'badge-warning','suspended'=>'badge-danger','rejected'=>'badge-danger'][$userSt] ?? 'badge-muted';
                // IPQS fraud stats for this affiliate
                $iq = $ipqsStatsByAffiliate[(int)$r['affiliate_id']] ?? null;
                $iqAvg = $iq ? (float)$iq['avg_score'] : null;
                $iqPanelId = 'ipqs-panel-' . (int)$r['affiliate_id'];
                if ($iq && $iqAvg !== null) {
                    if ($iqAvg >= 75)      { $iqBg = '#FEF2F2'; $iqCol = '#DC2626'; $iqRisk = 'High Risk'; }
                    elseif ($iqAvg >= 40)  { $iqBg = '#FFFBEB'; $iqCol = '#D97706'; $iqRisk = 'Medium Risk'; }
                    else                   { $iqBg = '#ECFDF5'; $iqCol = '#059669'; $iqRisk = 'Low Risk'; }
                } else {
                    $iqBg = '#F8FAFC'; $iqCol = '#94A3B8'; $iqRisk = 'No Data';
                }
            ?>
                <tr>
                    <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= (int)$r['affiliate_id'] ?></code></td>
                    <td class="fw-bold" style="white-space:nowrap">
                        <div><?= Helpers::e($r['aff_name']) ?></div>
                        <div class="text-muted" style="font-size:11px;font-family:monospace"><?= Helpers::e($r['affiliate_code']) ?></div>
                    </td>
                    <td style="white-space:nowrap"><?= Helpers::e($r['email']) ?></td>
                    <td><span class="badge <?= $userBadge ?>"><?= ucfirst($userSt) ?: '—' ?></span></td>
                    <td><?= number_format($tc) ?></td>
                    <td><?= number_format((int)$r['unique_clicks']) ?></td>
                    <td style="color:<?= ((int)$r['fraud_clicks'])>0?'var(--danger)':'inherit' ?>"><?= number_format((int)$r['fraud_clicks']) ?></td>
                    <td style="color:<?= ((int)$r['blocked_clicks'])>0?'var(--danger)':'inherit' ?>"><?= number_format((int)$r['blocked_clicks']) ?></td>
                    <td><?= number_format((int)$r['conv_total']) ?></td>
                    <td style="color:var(--secondary)"><?= number_format((int)$r['conv_approved']) ?></td>
                    <td style="color:#F59E0B"><?= number_format((int)$r['conv_pending']) ?></td>
                    <td style="color:var(--danger)"><?= number_format((int)$r['conv_rejected']) ?></td>
                    <td><?= $cr ?>%</td>
                    <td>$<?= $epc ?></td>
                    <td>$<?= number_format((float)$r['payout'],2) ?></td>
                    <td>$<?= number_format((float)$r['revenue'],2) ?></td>
                    <td style="color:<?= $profit>=0?'var(--secondary)':'var(--danger)' ?>">$<?= number_format($profit,2) ?></td>
                    <td><span style="display:inline-block;padding:2px 8px;border-radius:10px;background:<?= $tqColor ?>;color:#fff;font-size:11px;font-weight:600"><?= $tqLabel ?></span></td>
                    <td>
                        <button type="button"
                                onclick="toggleIpqsPanel('<?= $iqPanelId ?>')"
                                style="display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:8px;border:1px solid <?= $iqBg === '#F8FAFC' ? '#E2E8F0' : $iqBg ?>;background:<?= $iqBg ?>;color:<?= $iqCol ?>;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap">
                            <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor" style="opacity:.8"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 3a1 1 0 110 2 1 1 0 010-2zm0 3.5c.55 0 1 .45 1 1V11a1 1 0 01-2 0V8.5c0-.55.45-1 1-1z"/></svg>
                            <?php if ($iq && $iqAvg !== null): ?>
                                Avg <?= number_format($iqAvg, 0) ?> · <?= $iqRisk ?>
                            <?php else: ?>
                                IPQS
                            <?php endif; ?>
                            <svg id="<?= $iqPanelId ?>-chevron" width="10" height="10" viewBox="0 0 16 16" fill="currentColor" style="transition:transform .2s"><path d="M4 6l4 4 4-4"/></svg>
                        </button>
                    </td>
                    <td class="text-muted"><?= $lastAct ? date('M j, Y H:i', strtotime($lastAct)) : '—' ?></td>
                    <td class="text-muted"><?= $r['registered_at'] ? date('M j, Y', strtotime($r['registered_at'])) : '—' ?></td>
                    <td><a href="?<?= http_build_query(array_merge($_GET, ['affiliate_id'=>$r['affiliate_id'],'affiliate_code'=>''])) ?>" class="btn btn-secondary btn-sm">View</a></td>
                </tr>
                <?php /* ── IPQS Fraud Report expandable panel ── */ ?>
                <tr id="<?= $iqPanelId ?>" style="display:none;background:#F8FAFC">
                    <td colspan="22" style="padding:0">
                        <div style="padding:16px 20px 18px;border-top:2px solid <?= $iqBg === '#F8FAFC' ? '#E2E8F0' : $iqBg ?>">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                                <span style="font-size:13px;font-weight:700;color:#1E293B">IPQualityScore Fraud Report</span>
                                <span style="font-size:11px;color:#94A3B8;font-weight:500">— <?= Helpers::e($r['aff_name']) ?> · <?= Helpers::e($r['affiliate_code']) ?> · <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?></span>
                                <a href="/admin/fraud-score-report?affiliate_id=<?= (int)$r['affiliate_id'] ?>&from=<?= Helpers::e($from) ?>&to=<?= Helpers::e($to) ?>"
                                   style="margin-left:auto;font-size:11px;color:#6366F1;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px">
                                    Full Report →
                                </a>
                            </div>
                            <?php if (!$iq || (int)$iq['total_checked'] === 0): ?>
                            <div style="color:#94A3B8;font-size:13px;padding:12px 0">No IPQS fraud data available for this affiliate in the selected period.</div>
                            <?php else:
                                $totalChk   = (int)$iq['total_checked'];
                                $highRisk   = (int)$iq['high_risk'];
                                $medRisk    = (int)$iq['medium_risk'];
                                $lowRisk    = (int)$iq['low_risk'];
                                $pending    = (int)$iq['pending_check'];
                                $vpnCnt     = (int)$iq['vpn_count'];
                                $proxyCnt   = (int)$iq['proxy_count'];
                                $torCnt     = (int)$iq['tor_count'];
                                $dcCnt      = (int)$iq['datacenter_count'];
                                $maxScore   = $iq['max_score'] !== null ? (int)$iq['max_score'] : null;
                                $highPct    = $totalChk > 0 ? round($highRisk / $totalChk * 100, 1) : 0;
                            ?>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:16px">
                                <?php
                                $stat = function(string $label, $val, string $color = '#1E293B', string $bg = '#fff', string $border = '#E2E8F0') {
                                    echo '<div style="background:'.$bg.';border:1px solid '.$border.';border-radius:8px;padding:10px 14px">'
                                       . '<div style="font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748B;margin-bottom:4px">'.htmlspecialchars($label,ENT_QUOTES).'</div>'
                                       . '<div style="font-size:22px;font-weight:700;color:'.$color.'">'.htmlspecialchars((string)$val,ENT_QUOTES).'</div>'
                                       . '</div>';
                                };
                                $stat('Conversions', number_format($totalChk));
                                $stat('Avg Score', $iqAvg !== null ? number_format($iqAvg, 1) : '—', $iqCol, $iqBg);
                                $stat('Max Score', $maxScore !== null ? $maxScore : '—', $maxScore >= 75 ? '#DC2626' : ($maxScore >= 40 ? '#D97706' : '#059669'));
                                $stat('High Risk ≥75', number_format($highRisk).' ('.$highPct.'%)', '#DC2626', '#FEF2F2', '#FECACA');
                                $stat('Medium Risk', number_format($medRisk), '#D97706', '#FFFBEB', '#FDE68A');
                                $stat('Clean <40', number_format($lowRisk), '#059669', '#ECFDF5', '#A7F3D0');
                                if ($pending > 0) $stat('Pending Check', number_format($pending), '#9CA3AF', '#F9FAFB', '#E5E7EB');
                                ?>
                            </div>
                            <?php if ($vpnCnt + $proxyCnt + $torCnt + $dcCnt > 0): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px">
                                <span style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em">IP Flags:</span>
                                <?php if ($vpnCnt > 0): ?><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;background:#EDE9FE;color:#7C3AED;font-size:11px;font-weight:700">VPN <strong><?= $vpnCnt ?></strong></span><?php endif; ?>
                                <?php if ($proxyCnt > 0): ?><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;background:#FEF3C7;color:#B45309;font-size:11px;font-weight:700">PROXY <strong><?= $proxyCnt ?></strong></span><?php endif; ?>
                                <?php if ($torCnt > 0): ?><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;background:#FEE2E2;color:#DC2626;font-size:11px;font-weight:700">TOR <strong><?= $torCnt ?></strong></span><?php endif; ?>
                                <?php if ($dcCnt > 0): ?><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;background:#F0F9FF;color:#0369A1;font-size:11px;font-weight:700">DATACENTER <strong><?= $dcCnt ?></strong></span><?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <!-- Score distribution bar -->
                            <?php if ($totalChk > 0): ?>
                            <div style="margin-top:4px">
                                <div style="font-size:11px;font-weight:600;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Score Distribution</div>
                                <div style="display:flex;height:10px;border-radius:6px;overflow:hidden;background:#F1F5F9;width:100%">
                                    <?php if ($highRisk > 0): ?><div style="width:<?= round($highRisk/$totalChk*100,1) ?>%;background:#EF4444" title="High Risk: <?= $highRisk ?>"></div><?php endif; ?>
                                    <?php if ($medRisk > 0): ?><div style="width:<?= round($medRisk/$totalChk*100,1) ?>%;background:#F59E0B" title="Medium Risk: <?= $medRisk ?>"></div><?php endif; ?>
                                    <?php if ($lowRisk > 0): ?><div style="width:<?= round($lowRisk/$totalChk*100,1) ?>%;background:#10B981" title="Clean: <?= $lowRisk ?>"></div><?php endif; ?>
                                    <?php if ($pending > 0): ?><div style="width:<?= round($pending/$totalChk*100,1) ?>%;background:#E5E7EB" title="Pending: <?= $pending ?>"></div><?php endif; ?>
                                </div>
                                <div style="display:flex;gap:14px;margin-top:5px">
                                    <span style="font-size:10px;color:#EF4444;font-weight:600">■ High ≥75 (<?= $highRisk ?>)</span>
                                    <span style="font-size:10px;color:#F59E0B;font-weight:600">■ Medium 40–74 (<?= $medRisk ?>)</span>
                                    <span style="font-size:10px;color:#10B981;font-weight:600">■ Clean &lt;40 (<?= $lowRisk ?>)</span>
                                    <?php if ($pending > 0): ?><span style="font-size:10px;color:#9CA3AF;font-weight:600">■ Pending (<?= $pending ?>)</span><?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($affId > 0): ?>
<!-- ── Traffic detail (only when single affiliate is selected) ──────────── -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Traffic Detail — Affiliate #<?= (int)$affId ?></span>
        <span class="text-muted text-sm"><?= number_format(count($trafficRows)) ?> clicks shown</span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-aff-traffic" style="min-width:2400px;font-size:12px">
            <thead>
                <tr>
                    <th>Offer</th><th>Click ID</th><th>Aff Click ID</th>
                    <th>Sub 1</th><th>Sub 2</th><th>Sub 3</th>
                    <th>IP Address</th><th>Country</th><th>City</th><th>Region</th>
                    <th>Device</th><th>OS</th><th>Browser</th><th>User Agent</th>
                    <th>Click Status</th><th>Fraud</th><th>Conv Status</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Clicked At</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($trafficRows)): ?>
                <tr><td colspan="20" class="text-center text-muted" style="padding:32px">No clicks for this affiliate in the selected window.</td></tr>
            <?php else: foreach ($trafficRows as $t):
                $isFraud   = (bool)$t['is_fraud'];
                $isBlocked = ($t['click_status'] === 'blocked');
                $convSt    = $t['conv_status'] ?? null;
                $convBadge = $convSt === 'approved' ? 'badge-success' : ($convSt === 'pending' ? 'badge-warning' : ($convSt === 'rejected' ? 'badge-danger' : 'badge-muted'));
            ?>
                <tr style="<?= $isBlocked ? 'background:#FEF2F2' : ($isFraud ? 'background:#FFF5F5' : '') ?>">
                    <td class="fw-bold" style="white-space:nowrap"><?= Helpers::e($t['offer_name']) ?></td>
                    <td><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($t['click_id']) ?></code></td>
                    <td><?= Helpers::e($t['sub1'] ?: '—') ?></td>
                    <td><?= Helpers::e($t['sub2'] ?: '—') ?></td>
                    <td><?= Helpers::e($t['sub3'] ?: '—') ?></td>
                    <td><?= Helpers::e($t['sub4'] ?: '—') ?></td>
                    <td style="font-family:monospace"><?= Helpers::e($t['ip_address']) ?></td>
                    <td><?php if (!empty($t['country'])): ?><img src="https://flagcdn.com/16x12/<?= strtolower($t['country']) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:3px"><?= Helpers::e($t['country']) ?><?php else: ?>—<?php endif; ?></td>
                    <td><?= Helpers::e($t['city'] ?: '—') ?></td>
                    <td><?= Helpers::e($t['region'] ?: '—') ?></td>
                    <td><?= Helpers::e(ucfirst($t['device_type'] ?: '—')) ?></td>
                    <td><?= Helpers::e($t['os'] ?: '—') ?></td>
                    <td><?= Helpers::e($t['browser'] ?: '—') ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helpers::e($t['user_agent'] ?? '') ?>"><?= Helpers::e($t['user_agent'] ?: '—') ?></td>
                    <td>
                        <?php if ($isBlocked): ?>
                            <span class="badge badge-danger">Blocked</span>
                        <?php elseif ($t['click_status'] === 'duplicate'): ?>
                            <span class="badge badge-muted">Duplicate</span>
                        <?php elseif ($t['click_status'] === 'fraud'): ?>
                            <span class="badge badge-danger">Fraud</span>
                        <?php else: ?>
                            <span class="badge badge-success">Valid</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $isFraud ? '<span class="badge badge-danger">'.(int)$t['fraud_score'].'</span>' : '<span class="badge badge-success">Clean</span>' ?></td>
                    <td><?php if ($convSt): ?><span class="badge <?= $convBadge ?>"><?= ucfirst($convSt) ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                    <td><?= $convSt ? '$'.number_format((float)$t['conv_payout'],4)  : '<span style="color:#CBD5E1">—</span>' ?></td>
                    <td><?= $convSt ? '$'.number_format((float)$t['conv_revenue'],4) : '<span style="color:#CBD5E1">—</span>' ?></td>
                    <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($t['clicked_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
// ── IPQS panel toggle ─────────────────────────────────────────────────────
function toggleIpqsPanel(id) {
    var row = document.getElementById(id);
    if (!row) return;
    var chevron = document.getElementById(id + '-chevron');
    var isOpen = row.style.display !== 'none';
    row.style.display = isOpen ? 'none' : 'table-row';
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// DataTables init — gives the affiliate report table a built-in search box,
// column sorting, and client-side pagination. The Action column is excluded
// from sorting and search because it just renders a deep-link.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;
    var $t = jQuery('#tbl-aff-rep');
    if (!$t.length || $t.find('tbody tr').length === 0) return;
    // Skip when the table only contains the "No affiliate activity" placeholder.
    if ($t.find('tbody tr td[colspan]').length) return;
    $t.DataTable({
        stateSave:  true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order:      [[15, 'desc']], // sort by Revenue by default
        columnDefs: [
            { targets: -1, orderable: false, searchable: false }, // Action col
            { targets: 18, orderable: false, searchable: false }  // IPQS Fraud col
        ],
        rowCallback: function(row) {
            // Hide IPQS panel rows from DataTables' view to avoid layout issues
            if (row.id && row.id.indexOf('ipqs-panel-') === 0) {
                jQuery(row).addClass('dt-hidden-panel');
            }
        }
        language:   { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});

// Same for the per-affiliate Traffic Detail table when it is rendered.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.DataTable) return;
    var $t = jQuery('#tbl-aff-traffic');
    if (!$t.length || $t.find('tbody tr').length === 0) return;
    if ($t.find('tbody tr td[colspan]').length) return;
    $t.DataTable({
        stateSave:  true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order:      [[19, 'desc']], // sort by Clicked At
        language:   { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
});
</script>
