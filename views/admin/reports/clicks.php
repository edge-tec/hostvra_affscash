<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Click Report</h1><p>Raw click-level data with device, geo, and fraud details</p></div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#11123; Export CSV</a>
</div>

<!-- Find by Click ID -->
<div class="card mb-3" style="border-left:4px solid var(--primary)">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="d-flex gap-3 align-items-center" style="flex-wrap:wrap">
            <label style="font-weight:600;white-space:nowrap;margin:0">&#128269; Find by Click ID</label>
            <input type="text" name="filter_click_id"
                   class="form-control"
                   placeholder="Paste full or partial Click ID..."
                   value="<?= Helpers::e($filterClickId ?? '') ?>"
                   style="min-width:340px;max-width:480px;font-family:monospace;font-size:13px">
            <button type="submit" class="btn btn-primary" style="white-space:nowrap">Find Click</button>
            <?php if (!empty($filterClickId)): ?>
            <a href="/admin/reports/clicks" class="btn btn-secondary" style="white-space:nowrap">Clear</a>
            <?php endif; ?>
            <?php if (!empty($filterClickId) && !empty($clicks)): ?>
            <span class="badge" style="background:#e8f5e9;color:#2e7d32;font-size:13px;padding:6px 12px;border-radius:6px">
                <?= count($clicks) ?> result<?= count($clicks) !== 1 ? 's' : '' ?> found
            </span>
            <?php elseif (!empty($filterClickId) && empty($clicks)): ?>
            <span class="badge" style="background:#fdecea;color:#c62828;font-size:13px;padding:6px 12px;border-radius:6px">
                No click found for this ID
            </span>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" id="click-report-form" class="d-flex gap-3 align-center" style="flex-wrap:wrap">
            <?php $drpFromId='cr-from'; $drpToId='cr-to'; $drpFormId='click-report-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" id="cr-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" id="cr-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $offerId == $o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select id="cr-aff-sel" name="affiliate_id" class="form-control">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $affId == $a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Aff Code</label>
                <input type="text" id="cr-aff-id" name="affiliate_code" class="form-control"
                       placeholder="AFFF06092C5" style="width:130px;font-family:monospace;font-size:13px;text-transform:uppercase"
                       value="<?= Helpers::e(Helpers::get('affiliate_code') ?? '') ?>">
            </div>
            <script>
            (function(){
                var sel=document.getElementById('cr-aff-sel'),txt=document.getElementById('cr-aff-id');
                if(!sel||!txt)return;
                if(txt.value){sel.value='';}
                sel.addEventListener('change',function(){if(this.value)txt.value='';});
                txt.addEventListener('input',function(){if(this.value.trim())sel.value='';});
            })();
            </script>
            <div class="form-group mb-0">
                <label>Fraud</label>
                <select name="fraud" class="form-control">
                    <option value="" <?= $fraud==='' ? 'selected':'' ?>>All</option>
                    <option value="0" <?= $fraud==='0' ? 'selected':'' ?>>Clean</option>
                    <option value="1" <?= $fraud==='1' ? 'selected':'' ?>>Fraud Only</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <?php foreach ([200,500,1000,2000,5000] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit==$l ? 'selected':'' ?>><?= number_format($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Click ID</label>
                <input type="text" name="filter_click_id" class="form-control" placeholder="Starts with..."
                       value="<?= Helpers::e($filterClickId ?? '') ?>" style="min-width:130px">
            </div>
            <div class="form-group mb-0">
                <label>Sub1</label>
                <input type="text" name="filter_sub1" class="form-control" placeholder="Exact match"
                       value="<?= Helpers::e($filterSub1 ?? '') ?>" style="min-width:110px">
            </div>
            <div class="form-group mb-0">
                <label>Sub2</label>
                <input type="text" name="filter_sub2" class="form-control" placeholder="Exact match"
                       value="<?= Helpers::e($filterSub2 ?? '') ?>" style="min-width:110px">
            </div>
            <div class="form-group mb-0">
                <label>Sub3</label>
                <input type="text" name="filter_sub3" class="form-control" placeholder="Exact match"
                       value="<?= Helpers::e($filterSub3 ?? '') ?>" style="min-width:110px">
            </div>
            <div class="form-group mb-0">
                <label>In-House Only</label>
                <select name="inhouse" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?= ($inhouseOnly ?? false) ? 'selected' : '' ?>>In-House Only</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Click Type</label>
                <select name="click_filter" class="form-control">
                    <option value="all"       <?= ($clickFilter ?? 'all') === 'all'       ? 'selected' : '' ?>>All Clicks</option>
                    <option value="converted" <?= ($clickFilter ?? 'all') === 'converted' ? 'selected' : '' ?>>Converted Clicks</option>
                    <option value="approved"  <?= ($clickFilter ?? 'all') === 'approved'  ? 'selected' : '' ?>>Approved Conversions</option>
                </select>
            </div>
            <div style="align-self:flex-end">
                <button class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary cards -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card">
        <div class="stat-label">Total Clicks</div>
        <div class="stat-value"><?= number_format($totalClicks) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fraud Clicks</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($fraudCount) ?></div>
        <div class="stat-sub"><?= $totalClicks > 0 ? round($fraudCount/$totalClicks*100,1) : 0 ?>% fraud rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Approved Convs</div>
        <div class="stat-value" style="color:var(--primary)"><?= number_format($convCount) ?></div>
        <div class="stat-sub"><?= $totalClicks > 0 ? round($convCount/$totalClicks*100,1) : 0 ?>% CR</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Revenue <span style="font-size:9px;opacity:.6">(approved)</span></div>
        <div class="stat-value">$<?= number_format($totalRevenue, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Payout <span style="font-size:9px;opacity:.6">(approved)</span></div>
        <div class="stat-value">$<?= number_format($totalPayout, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Profit <span style="font-size:9px;opacity:.6">(approved)</span></div>
        <div class="stat-value" style="color:<?= $totalProfit >= 0 ? 'var(--secondary)' : 'var(--danger)' ?>">$<?= number_format($totalProfit, 2) ?></div>
    </div>
</div>

<!-- Click table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Click Log</span>
        <span class="text-muted text-sm">Showing <?= number_format($totalClicks) ?> clicks · <?= Helpers::e($from) ?> to <?= Helpers::e($to) ?></span>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-clicks" style="min-width:2400px;font-size:12px">
            <thead>
                <tr>
                    <th>OFFER</th>
                    <th>AFFILIATE</th>
                    <th>CLICK ID</th>
                    <th>AFF CLICK ID</th>
                    <th>AFF SUB 1</th>
                    <th>AFF SUB 2</th>
                    <th>AFF SUB 3</th>
                    <th>SUB 4</th>
                    <th>SUB 5</th>
                    <th>SOURCE</th>
                    <th>FRAUD</th>
                    <th>OS NAME</th>
                    <th>BROWSER</th>
                    <th>BROWSER VERSION</th>
                    <th>USER AGENT</th>
                    <th>DEVICE BRAND</th>
                    <th>DEVICE MODEL</th>
                    <th>OS VERSION</th>
                    <th>IP ADDRESS</th>
                    <th>COUNTRY</th>
                    <th>CITY</th>
                    <th>REGION</th>
                    <th>LANDING PAGE</th>
                    <th>REVENUE</th>
                    <th>PAYOUT</th>
                    <th>PROFIT</th>
                    <th>CONV STATUS</th>
                    <th>CONVERTED AT</th>
                    <th>CLICK TIME</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clicks)): ?>
            <tr class="dt-empty-row"><td colspan="30" class="text-center text-muted" style="padding:32px">No clicks found for the selected filters</td></tr>
            <?php else: ?>
            <?php foreach ($clicks as $c):
                $profit   = (float)$c['revenue'] - (float)$c['payout'];
                $isFraud  = (bool)$c['is_fraud'];
                $uaParsed = Helpers::e($c['user_agent'] ?? '');
                // Try to extract browser version from user agent (simple regex)
                $browserVer = '—';
                if (!empty($c['browser']) && !empty($c['user_agent'])) {
                    if (preg_match('/' . preg_quote($c['browser'], '/') . '[\/\s]([\d\.]+)/i', $c['user_agent'], $m)) {
                        $browserVer = $m[1];
                    }
                }
                // OS version
                $osVer = '—';
                if (!empty($c['user_agent'])) {
                    if (preg_match('/Windows NT ([\d\.]+)/i', $c['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/Android ([\d\.]+)/i', $c['user_agent'], $m)) $osVer = $m[1];
                    elseif (preg_match('/OS ([\d_]+) like/i', $c['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                    elseif (preg_match('/Mac OS X ([\d_\.]+)/i', $c['user_agent'], $m)) $osVer = str_replace('_','.',$m[1]);
                }
                // Device brand/model parsed from User-Agent
                $deviceBrand = ucfirst($c['device_type'] ?? '—');
                $deviceModel = '—';
                if (!empty($c['user_agent'])) {
                    $ua = $c['user_agent'];
                    if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $ua, $m)) {
                        // Android: extract model from "Android X.Y; MODEL Build/"
                        $raw = trim($m[1]);
                        $knownBrands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
                        $brand = '';
                        foreach ($knownBrands as $b) {
                            if (stripos($raw, $b) === 0) { $brand = $b; break; }
                        }
                        if ($brand) {
                            $deviceBrand = $brand;
                            $deviceModel = trim(substr($raw, strlen($brand))) ?: $raw;
                        } else {
                            $deviceModel = $raw;
                        }
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
                <td><?= Helpers::e($c['sub4'] ?? '' ?: '—') ?></td>
                <td><?= Helpers::e($c['sub5'] ?? '' ?: '—') ?></td>
                <td><?= Helpers::e($c['sub6'] ?? '' ?: '—') ?></td>
                <td><?= Helpers::e($c['source'] ?? '' ?: '—') ?></td>
                <td>
                    <?php if ($isFraud): ?>
                    <span class="badge badge-danger" title="Score: <?= (int)$c['fraud_score'] ?>. <?= Helpers::e($c['fraud_reasons'] ?? '') ?>">Fraud&nbsp;<?= (int)$c['fraud_score'] ?></span>
                    <?php else: ?>
                    <span class="badge badge-success">Clean</span>
                    <?php endif; ?>
                </td>
                <td><?= Helpers::e($c['os'] ?: '—') ?></td>
                <td><?= Helpers::e($c['browser'] ?: '—') ?></td>
                <td><?= Helpers::e($browserVer) ?></td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= $uaParsed ?>"><?= $uaParsed ?: '—' ?></td>
                <td><?= Helpers::e($deviceBrand) ?></td>
                <td><?= Helpers::e($deviceModel) ?></td>
                <td><?= Helpers::e($osVer) ?></td>
                <td><?= Helpers::e($c['ip_address']) ?></td>
                <td>
                    <?php if (!empty($c['country'])): ?>
                    <span title="<?= Helpers::e($c['country']) ?>">
                        <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png"
                             onerror="this.style.display='none'"
                             style="vertical-align:middle;margin-right:3px">
                        <?= Helpers::e($c['country']) ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= Helpers::e($c['city'] ?: '—') ?></td>
                <td><?= Helpers::e($c['region'] ?: '—') ?></td>
                <td>
                    <?php if (!empty($c['lp_name']) || !empty($c['lp_url'])): ?>
                        <?php if (!empty($c['lp_name'])): ?>
                        <div style="font-weight:600"><?= Helpers::e($c['lp_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['lp_url'])): ?>
                        <a href="<?= Helpers::e($c['lp_url']) ?>" target="_blank" rel="noopener" class="text-muted" style="font-size:11px;word-break:break-all">
                            <?= Helpers::e(mb_strlen($c['lp_url']) > 48 ? mb_substr($c['lp_url'],0,48).'…' : $c['lp_url']) ?>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($c['conversion_id']) && !empty($c['conv_revenue'])): ?>
                    <span style="color:var(--secondary);font-weight:600">$<?= number_format((float)$c['conv_revenue'], 4) ?></span>
                    <?php elseif (!empty($c['conversion_id'])): ?>
                    <span style="color:var(--secondary);font-weight:600">$<?= number_format((float)$c['revenue'], 4) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($c['conversion_id']) && !empty($c['conv_payout'])): ?>
                    <span style="font-weight:600">$<?= number_format((float)$c['conv_payout'], 4) ?></span>
                    <?php elseif (!empty($c['conversion_id'])): ?>
                    <span style="font-weight:600">$<?= number_format((float)$c['payout'], 4) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <?php
                    if (!empty($c['conversion_id'])) {
                        $dispRevenue = !empty($c['conv_revenue']) ? (float)$c['conv_revenue'] : (float)$c['revenue'];
                        $dispPayout  = !empty($c['conv_payout'])  ? (float)$c['conv_payout']  : (float)$c['payout'];
                        $dispProfit  = $dispRevenue - $dispPayout;
                        $profitCell  = '<td style="color:'.($dispProfit >= 0 ? 'var(--secondary)' : 'var(--danger)').'">$'.number_format($dispProfit, 4).'</td>';
                    } else {
                        $profitCell = '<td><span class="text-muted">—</span></td>';
                    }
                    echo $profitCell;
                ?>
                <td>
                    <?php if (!empty($c['conv_status'])): ?>
                    <?php $csm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger']; ?>
                    <span class="badge badge-<?= $csm[$c['conv_status']] ?? 'muted' ?>"><?= Helpers::e($c['conv_status']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted" style="white-space:nowrap">
                    <?= !empty($c['converted_at']) ? date('M j, Y H:i:s', strtotime($c['converted_at'])) : '—' ?>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($c['clicked_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
                        <a href="/admin/fraud?click_id=<?= urlencode($c['click_id']) ?>"
                           class="btn btn-secondary btn-sm" style="white-space:nowrap">Fraud Log</a>

                        <?php
                        $filterHidden = '';
                        foreach (['from','to','offer_id','affiliate_id','fraud','limit'] as $_k) {
                            if (!empty($_GET[$_k])) {
                                $filterHidden .= '<input type="hidden" name="'.$_k.'" value="'.Helpers::e($_GET[$_k]).'">';
                            }
                        }
                        ?>

                        <?php if (empty($c['conversion_id'])): ?>
                        <!-- No conversion yet — show Convert button -->
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Convert this click to an approved conversion?\nPayout: $<?= number_format((float)$c['payout'],2) ?>')">
                            <?= Helpers::csrf() ?>
                            <?= $filterHidden ?>
                            <input type="hidden" name="conv_action" value="convert_click">
                            <input type="hidden" name="click_id"   value="<?= Helpers::e($c['click_id']) ?>">
                            <button type="submit" class="btn btn-success btn-sm" style="white-space:nowrap">
                                ⚡ Convert
                            </button>
                        </form>

                        <?php else: ?>
                        <!-- Has conversion — show Approve / Reject based on current status -->
                        <?php if ($c['conv_status'] !== 'approved'): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <?= $filterHidden ?>
                            <input type="hidden" name="conversion_id" value="<?= Helpers::e($c['conversion_id']) ?>">
                            <input type="hidden" name="conv_action"   value="approved">
                            <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Approve this conversion?')" style="white-space:nowrap">✓ Approve</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($c['conv_status'] !== 'rejected'): ?>
                        <button type="button" class="btn btn-danger btn-sm" style="white-space:nowrap"
                                onclick="openRejectModal('<?= Helpers::e($c['conversion_id']) ?>')">✕ Reject</button>
                        <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function() {
    $('#tbl-clicks .dt-empty-row').remove();
    $('#tbl-clicks').DataTable({
        destroy: true,
        pageLength: 50,
        order: [[25, 'desc']],
        scrollX: true,
        columnDefs: [
            { orderable: false, targets: [12, 13] }
        ],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries', emptyTable: 'No clicks found for the selected filters' }
    });
});
</script>

<?php
// Reject-with-reason modal — submits to the SAME URL (preserving filter query string),
// so ClickReportController's $qs-based redirect re-applies the current filter view.
$_currentQs = $_SERVER['QUERY_STRING'] ?? '';
$rejectFormAction  = '/admin/reports/clicks' . ($_currentQs !== '' ? '?' . $_currentQs : '');
$rejectStatusField = 'conv_action';
$rejectStatusValue = 'rejected';
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
