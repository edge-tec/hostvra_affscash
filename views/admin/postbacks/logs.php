<?php
// ── POST: Manual re-fire actions ──────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    require_once BASE_PATH . '/core/PostbackFirer.php';
    $logAction = Helpers::post('log_action');

    // ── Re-fire a single postback log entry ────────────────────────────────
    if ($logAction === 'refire_log') {
        $logId = (int)Helpers::postRaw('log_id');
        $log   = Database::fetchOne(
            "SELECT pl.*, COALESCE(pb.method, 'GET') as pb_method
             FROM postback_logs pl
             LEFT JOIN postbacks pb ON pb.id = pl.postback_id AND pl.postback_id > 0
             WHERE pl.id = ?",
            [$logId]
        );
        if ($log && !empty($log['fired_url'])) {
            $result = Helpers::firePostback($log['fired_url'], $log['pb_method'] ?: 'GET');
            Database::query(
                "UPDATE postback_logs
                 SET is_success=?, http_status=?, response_body=?,
                     attempt_count=attempt_count+1, fired_at=NOW()
                 WHERE id=?",
                [$result['success'] ? 1 : 0, $result['status'],
                 substr(($result['body'] ?? '') . (!empty($result['error']) ? ' [' . $result['error'] . ']' : ''), 0, 1000),
                 $logId]
            );
            // If now successful, mark conversion postback_sent=1
            if ($result['success'] && !empty($log['conversion_id'])) {
                try {
                    Database::query(
                        "UPDATE conversions SET postback_sent=1, postback_sent_at=NOW()
                         WHERE conversion_id=? AND postback_sent=0",
                        [$log['conversion_id']]
                    );
                } catch (\Throwable $e) {}
            }
            PostbackFirer::log(
                "[PostbackLogs] Manual re-fire log #{$logId} conv={$log['conversion_id']}: "
                . "HTTP={$result['status']} success=" . ($result['success'] ? 'YES' : 'NO')
                . (!empty($result['error']) ? " error={$result['error']}" : '')
            );
            Helpers::flash(
                $result['success'] ? 'success' : 'error',
                $result['success']
                    ? "Postback re-fired successfully (HTTP {$result['status']})."
                    : "Re-fire failed — HTTP {$result['status']}. " . ($result['error'] ? $result['error'] : 'Check the URL.')
            );
        } else {
            Helpers::flash('error', 'Postback log entry not found.');
        }
        $back = Helpers::postRaw('redirect_back') ?: '/admin/postback-logs?success=0';
        Helpers::redirect($back);
    }

    // ── Re-fire all failed entries in date range ───────────────────────────
    if ($logAction === 'refire_all_failed') {
        require_once BASE_PATH . '/core/PostbackFirer.php';
        $fromDate = Helpers::postRaw('from') ?: date('Y-m-d');
        $toDate   = Helpers::postRaw('to')   ?: date('Y-m-d');

        $failedLogs = Database::fetchAll(
            "SELECT pl.*, COALESCE(pb.method, 'GET') as pb_method
             FROM postback_logs pl
             LEFT JOIN postbacks pb ON pb.id = pl.postback_id AND pl.postback_id > 0
             WHERE pl.is_success = 0
               AND pl.fired_at BETWEEN ? AND ?
             ORDER BY pl.fired_at ASC
             LIMIT 200",
            [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']
        );

        $refired = 0; $refiredOk = 0;
        foreach ($failedLogs as $log) {
            if (empty($log['fired_url'])) continue;
            $result = Helpers::firePostback($log['fired_url'], $log['pb_method'] ?: 'GET');
            Database::query(
                "UPDATE postback_logs
                 SET is_success=?, http_status=?, response_body=?,
                     attempt_count=attempt_count+1, fired_at=NOW()
                 WHERE id=?",
                [$result['success'] ? 1 : 0, $result['status'],
                 substr(($result['body'] ?? '') . (!empty($result['error']) ? ' [' . $result['error'] . ']' : ''), 0, 1000),
                 $log['id']]
            );
            if ($result['success'] && !empty($log['conversion_id'])) {
                try {
                    Database::query(
                        "UPDATE conversions SET postback_sent=1, postback_sent_at=NOW()
                         WHERE conversion_id=? AND postback_sent=0",
                        [$log['conversion_id']]
                    );
                } catch (\Throwable $e) {}
            }
            $refired++;
            if ($result['success']) $refiredOk++;
        }
        PostbackFirer::log("[PostbackLogs] Bulk re-fire: {$refiredOk}/{$refired} succeeded ({$fromDate} → {$toDate}).");
        Helpers::flash(
            $refiredOk > 0 ? 'success' : ($refired > 0 ? 'error' : 'info'),
            $refired === 0
                ? 'No failed postbacks found for this date range.'
                : "Re-fired {$refired} failed postback(s) — {$refiredOk} succeeded, " . ($refired - $refiredOk) . " still failing."
        );
        Helpers::redirect('/admin/postback-logs?from=' . urlencode($fromDate) . '&to=' . urlencode($toDate) . '&success=0');
    }
}

// ── Filters + data fetch (must run before any HTML output so the
//    CSV export below can still send headers) ─────────────────────────────
$from    = Helpers::get('from') ?: date('Y-m-d');
$to      = Helpers::get('to')   ?: date('Y-m-d');
$affId   = (int)Helpers::get('aff_id');
$success = Helpers::get('success'); // '1', '0', or ''
$type    = Helpers::get('type');    // 'affiliate', 'global', ''

// ── Affiliate list for filter ─────────────────────────────────────────────
$affList = Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' (',af.affiliate_code,')') as label
     FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY label"
);

// ── Build WHERE ───────────────────────────────────────────────────────────
$from_dt  = $from . ' 00:00:00';
$to_dt    = $to   . ' 23:59:59';
$pbWhere  = ['pl.fired_at BETWEEN ? AND ?'];
$pbParams = [$from_dt, $to_dt];

if ($affId)           { $pbWhere[] = 'cv.affiliate_id = ?'; $pbParams[] = $affId; }
if ($success !== '')  { $pbWhere[] = 'pl.is_success = ?'; $pbParams[] = (int)$success; }
if ($type === 'global')    { $pbWhere[] = 'pl.postback_id = 0'; }
if ($type === 'affiliate') { $pbWhere[] = 'pl.postback_id > 0'; }
$whereStr = implode(' AND ', $pbWhere);

$logs = Database::fetchAll(
    "SELECT pl.id, pl.postback_id, pl.conversion_id, pl.fired_url,
            pl.http_status, pl.response_body, pl.is_success,
            COALESCE(pl.attempt_count, 1) as attempt_count,
            pl.fired_at,
            cv.status as conv_status, cv.payout,
            CONCAT(u.first_name,' ',u.last_name) as aff_name,
            af.affiliate_code,
            o.name as offer_name,
            COALESCE(pb.event, 'conversion') as event,
            COALESCE(pb.method, 'GET') as method
     FROM postback_logs pl
     LEFT JOIN conversions cv   ON cv.conversion_id  = pl.conversion_id
     LEFT JOIN affiliates af    ON af.id = cv.affiliate_id
     LEFT JOIN users u          ON u.id  = af.user_id
     LEFT JOIN offers o         ON o.id  = cv.offer_id
     LEFT JOIN postbacks pb     ON pb.id = pl.postback_id AND pl.postback_id > 0
     WHERE $whereStr
     GROUP BY pl.id
     ORDER BY pl.fired_at DESC LIMIT 2000",
    $pbParams
);

$totalFired    = count($logs);
$successCount  = count(array_filter($logs, fn($r) => $r['is_success']));
$failCount     = $totalFired - $successCount;
$globalCount   = count(array_filter($logs, fn($r) => (int)$r['postback_id'] === 0));
$affLevelCount = $totalFired - $globalCount;

// ── CSV export — runs BEFORE the layout so headers can still be sent ─────
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="postback-logs-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['ID','TYPE','CONVERSION ID','AFFILIATE','AFF CODE','OFFER','EVENT','METHOD',
                 'HTTP STATUS','SUCCESS','ATTEMPTS','FIRED URL','RESPONSE','CONV STATUS','PAYOUT','FIRED AT']);
    foreach ($logs as $r) {
        $type_label = ((int)$r['postback_id'] === 0) ? 'Global (Tracker→Affiliate)' : 'Affiliate Postback';
        fputcsv($f, [$r['id'],$type_label,$r['conversion_id'],$r['aff_name'],$r['affiliate_code'],
                     $r['offer_name'],$r['event'],$r['method'],$r['http_status'],
                     $r['is_success'] ? 'Yes' : 'No',$r['attempt_count'],
                     substr($r['fired_url'],0,300),$r['response_body'],
                     $r['conv_status'],$r['payout'],$r['fired_at']]);
    }
    fclose($f); exit;
}

require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div>
        <h1>Postback Logs</h1>
        <p>Full audit trail — Advertiser &rarr; My Tracker &rarr; Affiliate Tracker</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?php if ($failCount > 0): ?>
        <form method="POST" onsubmit="return confirm('Re-fire all <?= $failCount ?> failed postback(s) for <?= Helpers::e($from) ?> → <?= Helpers::e($to) ?>?')">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="log_action" value="refire_all_failed">
            <input type="hidden" name="from" value="<?= Helpers::e($from) ?>">
            <input type="hidden" name="to" value="<?= Helpers::e($to) ?>">
            <button type="submit" class="btn btn-warning" style="font-size:13px">
                &#8635; Re-fire All Failed (<?= $failCount ?>)
            </button>
        </form>
        <?php endif; ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">&#8595; Export CSV</a>
    </div>
</div>

<?php foreach (Helpers::getFlash() as $_fl):
    $_bg  = $_fl['type']==='success' ? '#D1FAE5' : ($_fl['type']==='error' ? '#FEE2E2' : '#DBEAFE');
    $_col = $_fl['type']==='success' ? '#065F46' : ($_fl['type']==='error' ? '#7F1D1D' : '#1E3A8A');
    $_bdr = $_fl['type']==='success' ? '#6EE7B7' : ($_fl['type']==='error' ? '#FCA5A5' : '#93C5FD');
    $_ico = $_fl['type']==='success' ? '&#10003;' : '&#9888;';
?>
<div style="margin-bottom:14px;padding:12px 16px;border-radius:8px;background:<?= $_bg ?>;color:<?= $_col ?>;border:1px solid <?= $_bdr ?>;font-size:13px">
    <?= $_ico ?> <?= Helpers::e($_fl['message']) ?>
</div>
<?php endforeach; ?>

<!-- Stats -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
    <div class="stat-card"><div class="stat-label">Total Fired</div><div class="stat-value"><?= number_format($totalFired) ?></div></div>
    <div class="stat-card"><div class="stat-label">Success</div><div class="stat-value" style="color:var(--secondary)"><?= number_format($successCount) ?></div></div>
    <div class="stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value" style="color:<?= $failCount > 0 ? 'var(--danger,#ef4444)' : 'inherit' ?>"><?= number_format($failCount) ?></div>
        <?php if ($failCount > 0): ?><div style="font-size:10px;color:var(--danger,#ef4444);margin-top:2px">&#9888; needs attention</div><?php endif; ?>
    </div>
    <div class="stat-card"><div class="stat-label">Success Rate</div><div class="stat-value"><?= $totalFired > 0 ? round($successCount/$totalFired*100,1) : 0 ?>%</div></div>
    <div class="stat-card"><div class="stat-label">Global (&rarr;Aff)</div><div class="stat-value" style="color:var(--primary)"><?= number_format($globalCount) ?></div></div>
    <div class="stat-card"><div class="stat-label">Aff-Level</div><div class="stat-value"><?= number_format($affLevelCount) ?></div></div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div class="form-group mb-0">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select name="aff_id" class="form-control">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $affId === (int)$a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="global" <?= $type==='global'?'selected':'' ?>>Global (Tracker &rarr; Affiliate)</option>
                    <option value="affiliate" <?= $type==='affiliate'?'selected':'' ?>>Affiliate-level postbacks</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Result</label>
                <select name="success" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?= $success==='1'?'selected':'' ?>>Success only</option>
                    <option value="0" <?= $success==='0'?'selected':'' ?>>Failed only</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="/admin/postback-logs" class="btn btn-secondary">Clear</a>
            <a href="?<?= http_build_query(array_merge(['from'=>$from,'to'=>$to], ['success'=>'0'])) ?>" class="btn btn-secondary" style="border-color:#ef4444;color:#ef4444">&#9888; Failed Only</a>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <div>
            <span class="card-title">Postback fire log</span>
            <span class="text-sm text-muted"><?= Helpers::e($from) ?> &rarr; <?= Helpers::e($to) ?></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <button type="button" id="btn-toggle-all-details" class="btn btn-sm btn-outline-primary" onclick="toggleAllLogDetails()" style="font-size:12px" data-expanded="false">
                👁 Expand All (Full URL &amp; Response)
            </button>
        </div>
    </div>
    <div class="table-wrap" style="overflow-x:auto">
        <table id="tbl-pb-logs" style="font-size:12px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Conv Status</th>
                    <th>HTTP</th>
                    <th>Result</th>
                    <th>Attempts</th>
                    <th style="min-width:200px">Fired URL</th>
                    <th style="min-width:160px">Response</th>
                    <th>Fired At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="12" class="text-center text-muted" style="padding:32px">
                <?= $success === '0' ? '&#10003; No failed postbacks for this period.' : 'No postback logs for this period.' ?>
            </td></tr>
            <?php else: foreach ($logs as $r):
                $isGlobal  = ((int)$r['postback_id'] === 0);
                $httpCode  = (int)($r['http_status'] ?? 0);
                $isSuccess = (bool)$r['is_success'];
                $httpColor = $httpCode >= 200 && $httpCode < 300 ? 'var(--secondary)' : ($httpCode > 0 ? 'var(--danger)' : '#94A3B8');
                $bm        = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
                $currentUrl = '/admin/postback-logs?' . http_build_query(array_filter(['from'=>$from,'to'=>$to,'aff_id'=>$affId?:null,'success'=>$success,'type'=>$type]));
            ?>
            <tr style="<?= !$isSuccess ? 'background:#FFF5F5' : '' ?>">
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
                <td><?= Helpers::e($r['offer_name'] ?: 'All') ?></td>
                <td>
                    <?php if (!empty($r['conv_status'])): ?>
                    <span class="badge badge-<?= $bm[$r['conv_status']] ?? 'muted' ?>"><?= $r['conv_status'] ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <span style="font-weight:600;color:<?= $httpColor ?>">
                        <?= $httpCode ?: '—' ?>
                    </span>
                </td>
                <td>
                    <?= $isSuccess
                        ? '<span class="badge badge-success">&#10003; OK</span>'
                        : '<span class="badge badge-danger">&#10007; Fail</span>' ?>
                </td>
                <td style="text-align:center">
                    <span title="<?= (int)($r['attempt_count'] ?? 1) ?> attempt(s) total" style="font-weight:<?= (int)($r['attempt_count']??1) >= 3 ? '700' : 'normal' ?>;color:<?= (int)($r['attempt_count']??1) >= 4 ? '#ef4444' : 'inherit' ?>">
                        <?= (int)($r['attempt_count'] ?? 1) ?>
                    </span>
                </td>
                <td class="pb-url-cell" style="min-width:220px;max-width:380px;cursor:pointer;vertical-align:top" onclick="toggleLogCell(this)" title="Click to expand/collapse full URL">
                    <div class="pb-short-val" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <code style="font-size:10px;color:#2563EB"><?= Helpers::e($r['fired_url']) ?></code>
                    </div>
                    <div class="pb-full-val" style="display:none;word-break:break-all;white-space:pre-wrap;font-size:11px;background:#F8FAFC;padding:6px 8px;border-radius:6px;border:1px solid #CBD5E1;margin-top:2px">
                        <code style="font-size:11px;color:#1E40AF;word-break:break-all"><?= Helpers::e($r['fired_url']) ?></code>
                        <div style="margin-top:4px;text-align:right">
                            <button type="button" class="btn btn-xs btn-light" style="font-size:10px;padding:1px 6px" onclick="event.stopPropagation();copyPbText(this, '<?= Helpers::e(addslashes($r['fired_url'])) ?>')">📋 Copy URL</button>
                        </div>
                    </div>
                </td>
                <td class="pb-resp-cell" style="min-width:180px;max-width:300px;cursor:pointer;vertical-align:top" onclick="toggleLogCell(this)" title="Click to expand/collapse response body">
                    <div class="pb-short-val" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <span style="font-size:11px"><?= Helpers::e($r['response_body'] ?? '—') ?></span>
                    </div>
                    <div class="pb-full-val" style="display:none;word-break:break-all;white-space:pre-wrap;font-size:11px;background:#F8FAFC;padding:6px 8px;border-radius:6px;border:1px solid #CBD5E1;margin-top:2px;max-height:200px;overflow-y:auto">
                        <pre style="margin:0;font-size:11px;white-space:pre-wrap;word-break:break-all;font-family:monospace"><?= Helpers::e($r['response_body'] ?? '—') ?></pre>
                        <?php if (!empty($r['response_body'])): ?>
                        <div style="margin-top:4px;text-align:right">
                            <button type="button" class="btn btn-xs btn-light" style="font-size:10px;padding:1px 6px" onclick="event.stopPropagation();copyPbText(this, '<?= Helpers::e(addslashes($r['response_body'])) ?>')">📋 Copy Response</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, H:i:s', strtotime($r['fired_at'])) ?></td>
                <td style="white-space:nowrap">
                    <?php if (!$isSuccess): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Re-fire this postback now?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="log_action" value="refire_log">
                        <input type="hidden" name="log_id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="redirect_back" value="<?= Helpers::e($currentUrl) ?>">
                        <button type="submit" class="btn btn-warning btn-sm" style="font-size:11px;padding:3px 8px">
                            &#8635; Re-fire
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">&#10003;</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleLogCell(cell) {
    var shortEl = cell.querySelector('.pb-short-val');
    var fullEl = cell.querySelector('.pb-full-val');
    if (!shortEl || !fullEl) return;
    if (fullEl.style.display === 'none' || fullEl.style.display === '') {
        fullEl.style.display = 'block';
        shortEl.style.display = 'none';
    } else {
        fullEl.style.display = 'none';
        shortEl.style.display = 'block';
    }
}

function toggleAllLogDetails(forcedState) {
    var btn = document.getElementById('btn-toggle-all-details');
    var allShorts = document.querySelectorAll('.pb-short-val');
    var allFulls = document.querySelectorAll('.pb-full-val');
    
    var currentlyExpanded = (btn && btn.getAttribute('data-expanded') === 'true');
    var shouldExpand = (typeof forcedState === 'boolean') ? forcedState : !currentlyExpanded;
    
    allShorts.forEach(function(el) {
        el.style.display = shouldExpand ? 'none' : 'block';
    });
    allFulls.forEach(function(el) {
        el.style.display = shouldExpand ? 'block' : 'none';
    });
    
    if (btn) {
        btn.setAttribute('data-expanded', shouldExpand ? 'true' : 'false');
        btn.innerHTML = shouldExpand
            ? '🔒 Collapse All (Compact View)'
            : '👁 Expand All (Full URL &amp; Response)';
    }
    try {
        localStorage.setItem('postback_logs_full_view', shouldExpand ? '1' : '0');
    } catch(e) {}
}

function copyPbText(btn, text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '✓ Copied!';
            setTimeout(function() { btn.innerHTML = orig; }, 2000);
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        var orig = btn.innerHTML;
        btn.innerHTML = '✓ Copied!';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        if (localStorage.getItem('postback_logs_full_view') === '1') {
            toggleAllLogDetails(true);
        }
    } catch(e) {}
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
