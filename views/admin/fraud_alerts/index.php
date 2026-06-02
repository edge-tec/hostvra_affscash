<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<div class="page-header">
    <div>
        <h1>&#9888;&#65039; Fraud Alerts</h1>
        <p>Every High/Medium risk fraud alert sent to an affiliate. Mark them as resolved once reviewed.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" style="display:inline-block;margin:0"
              onsubmit="return confirm('Rebuild missing fraud alerts from history?\nAny high-risk conversion without an alert will be added to every affiliate’s bell.');">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="backfill">
            <button type="submit" class="btn btn-primary" title="Find every high-risk conversion that doesn't have a fraud alert yet and create one">
                &#8635; Backfill Missing Alerts
            </button>
        </form>
        <a href="/admin/fraud-score-report" class="btn btn-secondary">&larr; Fraud Score Report</a>
    </div>
</div>
<?php if (!empty($backfillMessage)): ?>
<div class="alert alert-info" data-auto-hide style="margin-bottom:14px"><?= Helpers::e($backfillMessage) ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div class="form-group mb-0">
                <label style="font-size:11px">Status</label>
                <select name="status" class="form-control" style="font-size:13px;min-width:130px">
                    <?php foreach (['open'=>'Open','resolved'=>'Resolved','all'=>'All'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= ($status===$k?'selected':'') ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:11px">Risk</label>
                <select name="risk" class="form-control" style="font-size:13px;min-width:130px">
                    <?php foreach (['all'=>'All Risk','high'=>'High Only','medium'=>'Medium Only'] as $k=>$lbl): ?>
                    <option value="<?= $k ?>" <?= ($risk===$k?'selected':'') ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label style="font-size:11px">Affiliate ID</label>
                <input type="number" name="affiliate_id" class="form-control" value="<?= $affId ?: '' ?>" placeholder="e.g. 42" style="width:120px;font-size:13px">
            </div>
            <button class="btn btn-primary btn-sm">Filter</button>
            <a href="/admin/fraud-alerts" class="btn btn-secondary btn-sm">Clear</a>
        </form>
    </div>
</div>

<style>
@keyframes fa-adm-pulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.7)}50%{box-shadow:0 0 0 8px rgba(239,68,68,0)}}
.fa-adm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 10px;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.fa-adm-pill.high{background:#FEE2E2;color:#991B1B}
.fa-adm-pill.medium{background:#FEF3C7;color:#92400E}
.fa-adm-pill.resolved{background:#D1FAE5;color:#065F46}
.fa-adm-pill .dot{width:7px;height:7px;border-radius:50%;background:currentColor}
.fa-adm-pill.high .dot{animation:fa-adm-pulse 1.4s infinite}
.fa-adm-row.high{border-left:3px solid #DC2626}
.fa-adm-row.medium{border-left:3px solid #F59E0B}
.fa-adm-row.resolved{opacity:.6}
.fa-adm-row td{padding:12px 14px;vertical-align:top;border-bottom:1px solid #F1F5F9}
</style>

<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">Alerts (<?= number_format(count($rows)) ?>)</span>
    </div>
    <div class="table-wrap">
        <table style="margin:0">
            <thead>
                <tr>
                    <th style="width:110px">Risk</th>
                    <th>Affiliate</th>
                    <th>Conversion</th>
                    <th>Offer</th>
                    <th style="width:90px">Score</th>
                    <th>Detected</th>
                    <th style="width:200px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">No fraud alerts match these filters.</td></tr>
                <?php else: foreach ($rows as $r):
                    $m = $r['_meta'];
                    $resolved = !empty($r['resolved_at']);
                    $rowClass = $r['_risk'] . ($resolved ? ' resolved' : '');
                ?>
                <tr class="fa-adm-row <?= $rowClass ?>" id="fa-row-<?= (int)$r['id'] ?>">
                    <td>
                        <?php if ($resolved): ?>
                        <span class="fa-adm-pill resolved">&#10003; Resolved</span>
                        <?php else: ?>
                        <span class="fa-adm-pill <?= $r['_risk'] ?>"><span class="dot"></span><?= $r['_risk'] === 'high' ? 'High Risk' : 'Medium Risk' ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold"><?= Helpers::e($r['affiliate_name'] ?: '— (deleted) —') ?></div>
                        <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_email'] ?? '') ?></div>
                        <?php if (!empty($r['affiliate_code'])): ?>
                        <div style="font-family:monospace;font-size:10px;color:#94A3B8;margin-top:2px"><?= Helpers::e($r['affiliate_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-family:monospace;font-size:11px"><?= Helpers::e(substr($m['conversion_id'] ?? '', 0, 12)) ?>…</div>
                        <a href="/admin/conversions?status=all&search=<?= urlencode($m['conversion_id'] ?? '') ?>" style="font-size:11px;color:#4F46E5">View conversion &rarr;</a>
                    </td>
                    <td><?= Helpers::e($m['offer_name'] ?? '—') ?></td>
                    <td style="font-weight:700;color:<?= ($m['fraud_score'] ?? 0) >= 80 ? '#DC2626' : '#D97706' ?>"><?= (int)($m['fraud_score'] ?? 0) ?></td>
                    <td style="font-size:12px">
                        <?= Helpers::e($m['detected_at'] ?? $r['created_at']) ?>
                        <?php if ($resolved): ?>
                        <div style="font-size:10px;color:#065F46;margin-top:2px">Resolved <?= Helpers::e($r['resolved_at']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right">
                        <?php if ($resolved): ?>
                        <button class="btn btn-secondary btn-sm" onclick="setResolved(<?= (int)$r['id'] ?>, 0)">&#8635; Reopen</button>
                        <?php else: ?>
                        <button class="btn btn-success btn-sm" onclick="setResolved(<?= (int)$r['id'] ?>, 1)">&#10003; Mark Resolved</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function setResolved(id, on) {
    if (on === 1 && !confirm('Mark this fraud alert as resolved?\nThe affiliate sees it as Resolved in their bell.')) return;
    if (on === 0 && !confirm('Reopen this fraud alert?')) return;
    var fd = new FormData();
    fd.append('action', on === 1 ? 'resolve' : 'unresolve');
    fd.append('id', id);
    fd.append('_token', _csrfToken);
    fetch('/api/fraud-alerts?action=' + (on === 1 ? 'resolve' : 'unresolve'), { method:'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        location.reload();
    });
}
</script>
