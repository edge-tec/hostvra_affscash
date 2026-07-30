<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Conversions</h1><p>Review and manage conversion records</p></div>
    <div class="d-flex gap-2">
        <?php foreach(['all','pending','approved','rejected'] as $s): ?>
        <a href="/admin/conversions?<?= http_build_query(['status'=>$s,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]) ?>" class="btn btn-sm <?= $status===$s?'btn-primary':'btn-secondary' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
        <?php
        $exportBaseGet = ['status' => $status, 'from' => $from, 'to' => $to, 'click_id' => $clickId ?? null];
        require BASE_PATH . '/views/partials/export_buttons.php';
        ?>
    </div>
</div>

<!-- Date filter bar -->
<div class="card mb-3 filter-card filter-open" id="conv-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span class="filter-toggle-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            <span>Filter Parameters</span>
        </span>
        <span class="filter-toggle-icon">▲</span>
    </button>
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" id="conv-filter-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="status" value="<?= Helpers::e($status) ?>">

            <div style="width:100%">
                <?php $drpFromId='conv-from'; $drpToId='conv-to'; $drpFormId='conv-filter-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            </div>

            <div class="form-group mb-0">
                <label style="font-size:12px">From</label>
                <input type="date" id="conv-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px">To</label>
                <input type="date" id="conv-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0">
                <label style="font-size:12px">Click ID</label>
                <input type="text" name="click_id" class="form-control" placeholder="Search by Click ID..." value="<?= Helpers::e($clickId ?? '') ?>" style="font-size:13px; min-width:180px;">
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="/admin/conversions?status=<?= Helpers::e($status) ?>" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%;">
        <table id="tbl-conversions">
            <thead><tr><th>Conversion ID</th><th>Affiliate</th><th>Offer</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Transaction</th><th>Status</th><th>Device</th><th>OS Version</th><th>Landing Page</th><th>Visit Info</th><th>Fraud Scores</th><th>Date</th><th>Actions</th></tr></thead>
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

                // Resolve all visit fields — prefer stored conversion columns,
                // fall back to the originating click row for old conversions.
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
                    } elseif (stripos($ua, 'iPhone')    !== false) { $deviceBrand = 'Apple'; $deviceModel = 'iPhone'; }
                    elseif (stripos($ua, 'iPad')         !== false) { $deviceBrand = 'Apple'; $deviceModel = 'iPad'; }
                    elseif (stripos($ua, 'Macintosh')    !== false) { $deviceBrand = 'Apple'; $deviceModel = 'Mac'; }
                    elseif (stripos($ua, 'Windows')      !== false) { $deviceBrand = 'PC';    $deviceModel = 'Windows'; }
                }

                // OS version
                $osVersion = $c['os_version'] ?? '';
                if ($osVersion === '') {
                    // Try to extract from UA first; fall back to click's basic os field
                    if ($ua !== '') {
                        if      (preg_match('/Android\s+([\d.]+)/i', $ua, $ov))       { $osVersion = 'Android ' . $ov[1]; }
                        elseif  (preg_match('/iPhone OS ([\d_]+)/i', $ua, $ov))       { $osVersion = 'iOS ' . str_replace('_', '.', $ov[1]); }
                        elseif  (preg_match('/iPad.*?OS ([\d_]+)/i', $ua, $ov))       { $osVersion = 'iPadOS ' . str_replace('_', '.', $ov[1]); }
                        elseif  (preg_match('/Windows NT ([\d.]+)/i', $ua, $ov)) {
                            $ntMap = ['10.0'=>'10/11','6.3'=>'8.1','6.2'=>'8','6.1'=>'7','6.0'=>'Vista','5.1'=>'XP'];
                            $osVersion = 'Windows ' . ($ntMap[$ov[1]] ?? $ov[1]);
                        }
                        elseif  (preg_match('/Mac OS X ([\d_]+)/i', $ua, $ov))        { $osVersion = 'macOS ' . str_replace('_', '.', $ov[1]); }
                    }
                    if ($osVersion === '' && !empty($c['ck_os'])) {
                        $osVersion = $c['ck_os']; // plain OS name from click row
                    }
                }

                // Landing page (URL + optional friendly Name resolved by index)
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
            ?>
            <tr>
                <td style="font-family:monospace;font-size:11px;white-space:nowrap">
                    <div style="font-weight:600" title="Conversion ID: <?= Helpers::e($c['conversion_id']) ?>">
                        <?= Helpers::e($c['conversion_id']) ?>
                        <button type="button" class="btn btn-link p-0 ms-1 text-muted" style="font-size:10px;text-decoration:none" onclick="navigator.clipboard.writeText('<?= Helpers::e($c['conversion_id']) ?>');this.innerText='✓';setTimeout(()=>this.innerText='📋',1000)" title="Copy Conversion ID">📋</button>
                    </div>
                    <?php if (!empty($c['click_id'])): ?>
                    <div style="font-size:10.5px;color:var(--text-muted);margin-top:2px" title="Click ID: <?= Helpers::e($c['click_id']) ?>">
                        <span style="opacity:0.7">Click:</span> <?= Helpers::e($c['click_id']) ?>
                        <button type="button" class="btn btn-link p-0 ms-1 text-muted" style="font-size:10px;text-decoration:none" onclick="navigator.clipboard.writeText('<?= Helpers::e($c['click_id']) ?>');this.innerText='✓';setTimeout(()=>this.innerText='📋',1000)" title="Copy Click ID">📋</button>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($c['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($c['affiliate_code']) ?></div>
                </td>
                <td class="text-sm"><?= Helpers::e($c['offer_name'] ?: '— Custom URL —') ?></td>
                <td class="fw-bold">$<?= number_format($c['payout'],2) ?></td>
                <td>$<?= number_format($c['revenue'],2) ?></td>
                <td class="text-sm" style="font-family:monospace"><?= Helpers::e($c['transaction_id'] ?: '—') ?></td>
                <td style="white-space:nowrap">
                    <?php if ($deviceBrand !== '' && $deviceBrand !== null): ?>
                    <div style="font-size:12px;font-weight:600"><?= Helpers::e($deviceBrand) ?></div>
                    <?php if ($deviceModel !== '' && $deviceModel !== null): ?>
                    <div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($deviceModel) ?></div>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm" style="white-space:nowrap">
                    <?php if ($osVersion !== ''): ?>
                    <span style="font-size:11px;font-weight:600;color:var(--text)"><?= Helpers::e($osVersion) ?></span>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td style="max-width:180px">
                    <?php if ($landingPageName !== ''): ?>
                    <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:2px"><?= Helpers::e($landingPageName) ?></div>
                    <?php endif; ?>
                    <?php if ($landingPage !== ''): ?>
                    <a href="<?= Helpers::e($landingPage) ?>" target="_blank" rel="noopener"
                       title="<?= Helpers::e($landingPage) ?>"
                       style="font-size:11px;color:#2563eb;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:170px">
                        <?= Helpers::e(preg_replace('#^https?://#', '', $landingPage)) ?>
                    </a>
                    <?php elseif ($landingPageName === ''): ?><span class="text-muted" style="font-size:11px">—</span><?php endif; ?>
                </td>
                <td style="max-width:160px">
                    <?php if ($visitRef !== ''): ?>
                    <a href="<?= Helpers::e($visitRef) ?>" target="_blank" rel="noopener"
                       title="<?= Helpers::e($visitRef) ?>"
                       style="font-size:11px;color:#6366f1;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px">
                        <?= Helpers::e(preg_replace('#^https?://([^/]+).*#', '$1', $visitRef)) ?>
                    </a>
                    <?php else: ?><span class="text-muted" style="font-size:11px">—</span><?php endif; ?>
                </td>
                <td>
                    <?php $bm=['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted']; ?>
                    <span class="badge badge-<?= $bm[$c['status']]??'muted' ?>"><?= $c['status'] ?></span>
                    <?php if($c['is_fraud']): ?><span class="badge badge-danger" style="margin-left:4px">FRAUD</span><?php endif; ?>
                    <?php if ($c['status'] === 'rejected' && !empty($c['rejection_reason'])): ?>
                    <div style="margin-top:4px;font-size:11px;color:#b91c1c;line-height:1.35"
                         title="<?= Helpers::e($c['rejection_reason']) ?>">
                        <strong>Reason:</strong> <?= Helpers::e(mb_strlen($c['rejection_reason'])>40?mb_substr($c['rejection_reason'],0,40).'…':$c['rejection_reason']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($c['rejected_at'])): ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px">Rejected <?= Helpers::e($c['rejected_at']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="min-width:160px">
                    <?php
                    // Helper: score badge colour
                    $sb = function($score, $high=75, $med=40) {
                        if ($score >= $high) return 'background:#fee2e2;color:#b91c1c';
                        if ($score >= $med)  return 'background:#fef3c7;color:#92400e';
                        if ($score > 0)      return 'background:#f1f5f9;color:#475569';
                        return 'background:#d1fae5;color:#065f46';
                    };
                    $pill = '<span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;white-space:nowrap;';
                    ?>
                    <table style="border-collapse:collapse;width:100%;font-size:11px">
                    <?php if ($fs !== null): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">IPQS</td>
                        <td><?= $pill . $sb($fs) . '">' . $fs . '</span>' ?></td>
                    </tr>
                    <?php elseif ($fraudChecked === false): ?>
                    <tr><td colspan="2"><span style="font-size:10px;color:#92400e">&#9203; Pending</span></td></tr>
                    <?php endif; ?>

                    <?php $ipqS = isset($c['ipquery_risk_score']) ? (int)$c['ipquery_risk_score'] : null; ?>
                    <?php if ($ipqS !== null): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">IPQuery</td>
                        <td><?= $pill . $sb($ipqS) . '">' . $ipqS . '</span>' ?>
                            <?php if (!empty($c['ipquery_vpn'])): ?><span style="font-size:9px;margin-left:2px;color:#7c3aed">VPN</span><?php endif; ?>
                            <?php if (!empty($c['ipquery_proxy'])): ?><span style="font-size:9px;margin-left:2px;color:#b45309">PX</span><?php endif; ?>
                            <?php if (!empty($c['ipquery_tor'])): ?><span style="font-size:9px;margin-left:2px;color:#dc2626">TOR</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $scamS = isset($c['scamalytics_score']) ? (int)$c['scamalytics_score'] : null; ?>
                    <?php if ($scamS !== null): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">Scamalytics</td>
                        <td><?= $pill . $sb($scamS) . '">' . $scamS . '</span>' ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php $pcS = isset($c['proxycheck_score']) ? (int)$c['proxycheck_score'] : null; ?>
                    <?php if ($pcS !== null): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">ProxyCheck</td>
                        <td><?= $pill . $sb($pcS, 50, 30) . '">' . $pcS . '</span>' ?>
                            <?php if (!empty($c['proxycheck_is_vpn'])): ?><span style="font-size:9px;margin-left:2px;color:#7c3aed">VPN</span><?php endif; ?>
                            <?php if (!empty($c['proxycheck_is_proxy'])): ?><span style="font-size:9px;margin-left:2px;color:#b45309">PX</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (isset($c['botscout_is_bot'])): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">BotScout</td>
                        <td><?php if ($c['botscout_is_bot']): ?>
                            <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;background:#fee2e2;color:#b91c1c">BOT</span>
                            <?php if ((int)($c['botscout_count'] ?? 0) > 0): ?>
                            <span style="font-size:9px;color:var(--text-muted)"><?= (int)$c['botscout_count'] ?>x</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;background:#d1fae5;color:#065f46">Clean</span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $fdS = isset($c['frauddefense_score']) ? (int)$c['frauddefense_score'] : null; ?>
                    <?php if ($fdS !== null): ?>
                    <tr>
                        <td style="color:var(--text-muted);padding:1px 4px 1px 0;white-space:nowrap">FraudDef.</td>
                        <td><?= $pill . $sb($fdS, 50, 30) . '">' . $fdS . '</span>' ?></td>
                    </tr>
                    <?php endif; ?>
                    </table>
                </td>
                <td class="text-sm text-muted"><?= date('M j, H:i', strtotime($c['converted_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
                        <?php if ($c['status'] !== 'approved'): ?>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Approve this conversion?\nPayout $<?= number_format((float)$c['payout'],2) ?> will be credited to the affiliate.')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                            <input type="hidden" name="status" value="approved">
                            <input type="hidden" name="redirect_back" value="/admin/conversions?<?= Helpers::e(http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))) ?>">
                            <button class="btn btn-success btn-sm" style="white-space:nowrap">✓ Approve</button>
                        </form>
                        <?php else: ?>
                        <span style="background:#D1FAE5;color:#065F46;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;white-space:nowrap">✓ Approved</span>
                        <?php endif; ?>

                        <?php if ($c['status'] !== 'rejected'): ?>
                        <button type="button" class="btn btn-danger btn-sm"
                                style="white-space:nowrap"
                                onclick="openRejectModal('<?= Helpers::e($c['conversion_id']) ?>')">✕ Reject</button>
                        <?php endif; ?>

                        <?php if ($c['status'] === 'approved'): ?>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Mark this conversion as chargebacked?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                            <input type="hidden" name="status" value="chargebacked">
                            <input type="hidden" name="redirect_back" value="/admin/conversions?<?= Helpers::e(http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))) ?>">
                            <button class="btn btn-warning btn-sm" style="white-space:nowrap">↩ CB</button>
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
// Reject-with-reason modal — submits to /admin/conversions with the same hidden
// fields that the existing approve form already uses (conversion_id + status).
$rejectFormAction  = '/admin/conversions';
$rejectStatusField = 'status';
$rejectStatusValue = 'rejected';
$rejectExtraHidden = ['redirect_back' => '/admin/conversions?' . http_build_query(array_filter(['status'=>$status,'from'=>$from,'to'=>$to,'click_id'=>$clickId??null]))];
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
