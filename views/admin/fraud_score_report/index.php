<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Fraud Score Report</h1>
        <p>All conversions with fraud scores — filter, sort, approve or reject</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <!-- Live streaming indicator — replaces the old "Scores pending /
             Re-check" warning. Polling runs silently in the background and
             writes scores into the table in place; no admin action needed. -->
        <span id="fsr-live-badge" style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;color:#065F46;font-size:12px;font-weight:700;letter-spacing:.02em">
            <span id="fsr-live-heartbeat" style="width:8px;height:8px;border-radius:50%;background:#10B981;box-shadow:0 0 0 0 rgba(16,185,129,.55);animation:fsrPulse 1.6s infinite;display:inline-block;transition:opacity .2s"></span>
            <span id="fsr-live-status">Live · streaming</span>
            <?php if (!empty($hasPendingScores)): ?>
            <span style="font-size:11px;color:#065F46;opacity:.75">·</span>
            <span style="font-size:11px;color:#065F46;opacity:.75"><span id="fsr-pending-count">…</span> queued</span>
            <?php else: ?>
            <span id="fsr-pending-count" style="display:none">0</span>
            <?php endif; ?>
        </span>
        <style>@keyframes fsrPulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.55)}70%{box-shadow:0 0 0 8px rgba(16,185,129,0)}100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}}</style>
        <?php require BASE_PATH . '/views/partials/export_buttons.php'; ?>
    </div>
</div>

<!-- Cron URL panel removed per user request: The page now exclusively relies on the live JS streaming engine for realtime updates without needing a manual cron job. -->

<?php if ($fraudMode === 'score_only'): ?>
<div style="padding:12px 16px;border-radius:10px;background:#EFF6FF;border:1px solid #BFDBFE;margin-bottom:16px;display:flex;align-items:center;gap:10px">
    <span style="font-size:18px">🔍</span>
    <div>
        <strong style="color:#1D4ED8;font-size:13px">Score-Only Mode Active</strong>
        <span style="color:#3B82F6;font-size:13px;margin-left:8px">Fraud scores are recorded for review. Conversions are never blocked automatically — use the Approve/Reject buttons below for manual action.</span>
    </div>
</div>
<?php endif; ?>

<!-- Summary Stats -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px">
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:16px 20px">
            <div class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Conversions</div>
            <div style="font-size:28px;font-weight:700;color:#1E293B;margin-top:4px"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:16px 20px">
            <div class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Pending Check</div>
            <div style="font-size:28px;font-weight:700;color:#F59E0B;margin-top:4px"><?= number_format($stats['pending_check'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:16px 20px">
            <div class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Avg Fraud Score</div>
            <div style="font-size:28px;font-weight:700;color:#F59E0B;margin-top:4px"><?= $stats['avg_score'] !== null ? number_format($stats['avg_score'], 1) : '—' ?></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:16px 20px">
            <div class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Max Score Seen</div>
            <div style="font-size:28px;font-weight:700;color:#EF4444;margin-top:4px"><?= $stats['max_score'] !== null ? (int)$stats['max_score'] : '—' ?></div>
        </div>
    </div>
    <div class="card" style="margin:0">
        <div class="card-body" style="padding:16px 20px">
            <div class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">High Risk (≥75)</div>
            <div style="font-size:28px;font-weight:700;color:#DC2626;margin-top:4px"><?= number_format($stats['high_risk'] ?? 0) ?></div>
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" id="fsr-filter-form" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div style="width:100%">
                <?php $drpFromId='fsr-from'; $drpToId='fsr-to'; $drpFormId='fsr-filter-form'; include BASE_PATH.'/views/partials/date_range_picker.php'; ?>
            </div>
            <div class="form-group mb-0" style="min-width:130px">
                <label style="font-size:12px">From</label>
                <input type="date" id="fsr-from" name="from" class="form-control" value="<?= Helpers::e($from) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="min-width:130px">
                <label style="font-size:12px">To</label>
                <input type="date" id="fsr-to" name="to" class="form-control" value="<?= Helpers::e($to) ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="min-width:140px">
                <label style="font-size:12px">Status</label>
                <select name="status" class="form-control" style="font-size:13px">
                    <option value="all" <?= $filterStatus==='all'?'selected':'' ?>>All Statuses</option>
                    <option value="pending" <?= $filterStatus==='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $filterStatus==='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $filterStatus==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="form-group mb-0" style="min-width:180px">
                <label style="font-size:12px">Affiliate</label>
                <select id="fsr-aff-sel" name="affiliate_id" class="form-control" style="font-size:13px">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $filterAffiliate===$a['id']?'selected':'' ?>><?= Helpers::e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0" style="min-width:130px">
                <label style="font-size:12px">Aff Code</label>
                <input type="text" id="fsr-aff-id" name="affiliate_code" class="form-control"
                       placeholder="AFFF06092C5" style="font-size:13px;font-family:monospace;text-transform:uppercase"
                       value="<?= Helpers::e(Helpers::get('affiliate_code') ?? '') ?>">
            </div>
            <script>
            (function(){
                var sel=document.getElementById('fsr-aff-sel'),txt=document.getElementById('fsr-aff-id');
                if(!sel||!txt)return;
                if(txt.value){sel.value='';}
                sel.addEventListener('change',function(){if(this.value)txt.value='';});
                txt.addEventListener('input',function(){if(this.value.trim())sel.value='';});
            })();
            </script>
            <div class="form-group mb-0" style="min-width:180px">
                <label style="font-size:12px">Offer</label>
                <select name="offer_id" class="form-control" style="font-size:13px">
                    <option value="">All Offers</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $filterOffer===$o['id']?'selected':'' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0" style="width:90px">
                <label style="font-size:12px">Score Min</label>
                <input type="number" name="score_min" class="form-control" min="0" max="100" value="<?= $filterScoreMin ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="width:90px">
                <label style="font-size:12px">Score Max</label>
                <input type="number" name="score_max" class="form-control" min="0" max="100" value="<?= $filterScoreMax ?>" style="font-size:13px">
            </div>
            <div class="form-group mb-0" style="min-width:140px">
                <label style="font-size:12px">Sort By</label>
                <select name="sort" class="form-control" style="font-size:13px">
                    <option value="converted_at" <?= $sortBy==='converted_at'?'selected':'' ?>>Date</option>
                    <option value="fraud_score" <?= $sortBy==='fraud_score'?'selected':'' ?>>Fraud Score</option>
                </select>
            </div>
            <div class="form-group mb-0" style="min-width:110px">
                <label style="font-size:12px">Direction</label>
                <select name="dir" class="form-control" style="font-size:13px">
                    <option value="desc" <?= $sortDir==='DESC'?'selected':'' ?>>Descending</option>
                    <option value="asc" <?= $sortDir==='ASC'?'selected':'' ?>>Ascending</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;padding-bottom:1px">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="/admin/fraud-score-report" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">Conversions (<?= number_format(count($conversions)) ?> shown)</span>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Helpers::csrf() ?>
            <button id="fsr-bulk-reject-btn" class="btn btn-danger btn-sm" style="display:none;align-items:center;gap:6px" onclick="bulkRejectSelected()">
                &#10007; Reject Selected
            </button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" id="fsr-select-all" onclick="fsrToggleAll(this)"></th>
                    <th>Conv ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>IP Address</th>
                    <th>IPQS Score</th>
                    <th>IPQuery</th>
                    <th>FraudDefense</th>
                    <th>Scamalytics</th>
                    <th>ProxyCheck</th>
                    <th>FraudLabs Pro</th>
                    <th>Checked At</th>
                    <th>Status</th>
                    <th>Payout</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($conversions)): ?>
            <tr><td colspan="15" class="text-center text-muted" style="padding:32px">No conversions found matching the filters</td></tr>
            <?php else: ?>
            <?php foreach ($conversions as $cv):
                // "Pending" = API check hasn't run yet (fraud_checked_at is NULL).
                // A score of 0 with fraud_checked_at set = checked, clean IP.
                $scoreNull = ($cv['fraud_checked_at'] === null || $cv['fraud_checked_at'] === '');
                $score = $scoreNull ? null : (int)$cv['fraud_score'];
                if ($scoreNull) {
                    $scoreColor = '#6B7280'; $scoreBg = '#F3F4F6';
                } elseif ($score >= 75) {
                    $scoreColor = '#DC2626'; $scoreBg = '#FEF2F2';
                } elseif ($score >= 40) {
                    $scoreColor = '#D97706'; $scoreBg = '#FFFBEB';
                } else {
                    $scoreColor = '#059669'; $scoreBg = '#ECFDF5';
                }
            ?>
            <tr data-conv="<?= Helpers::e($cv['conversion_id']) ?>">
                <td>
                    <?php if ($cv['status'] !== 'rejected'): ?>
                    <input type="checkbox" class="fsr-conv-cb" value="<?= Helpers::e($cv['conversion_id']) ?>" onchange="fsrCheckSelection()">
                    <?php endif; ?>
                </td>
                <td>
                    <span class="text-muted" style="font-size:11px;font-family:monospace"><?= Helpers::e(substr($cv['conversion_id'],0,16)) ?>…</span>
                </td>
                <td>
                    <div class="fw-bold text-sm"><?= Helpers::e($cv['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($cv['affiliate_code']) ?> · ID <?= $cv['affiliate_id'] ?></div>
                </td>
                <td>
                    <div class="text-sm"><?= Helpers::e($cv['offer_name'] ?: '— Custom URL —') ?></div>
                    <div class="text-muted" style="font-size:11px"><?= $cv['offer_id'] ? 'ID ' . $cv['offer_id'] : 'SmartLink' ?></div>
                </td>
                <?php
                // Helper: render a small score badge (0–100, null=N/A)
                $scoreBadge = function(?int $s, string $provider = '') use ($cv): string {
                    if ($s === null) return '<span style="color:#D1D5DB;font-size:11px">—</span>';
                    if ($s >= 75)      { $bg='#FEF2F2'; $col='#DC2626'; }
                    elseif ($s >= 40)  { $bg='#FFFBEB'; $col='#D97706'; }
                    else               { $bg='#ECFDF5'; $col='#059669'; }
                    return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:700;background:'.$bg.';color:'.$col.'">'.$s.'</span>';
                };
                // IPQuery flags
                $iqFlags = '';
                if (!empty($cv['ipquery_vpn']))        $iqFlags .= '<span style="font-size:10px;background:#ede9fe;color:#7c3aed;padding:1px 4px;border-radius:3px;margin:1px">VPN</span>';
                if (!empty($cv['ipquery_proxy']))       $iqFlags .= '<span style="font-size:10px;background:#fef3c7;color:#b45309;padding:1px 4px;border-radius:3px;margin:1px">PRX</span>';
                if (!empty($cv['ipquery_tor']))         $iqFlags .= '<span style="font-size:10px;background:#fee2e2;color:#dc2626;padding:1px 4px;border-radius:3px;margin:1px">TOR</span>';
                if (!empty($cv['ipquery_datacenter']))  $iqFlags .= '<span style="font-size:10px;background:#f0f9ff;color:#0369a1;padding:1px 4px;border-radius:3px;margin:1px">DC</span>';
                $iqLevel = $cv['ipquery_risk_level'] ?? '';
                $iqLevelStyle = $iqLevel === 'high' ? 'color:#DC2626' : ($iqLevel === 'medium' ? 'color:#D97706' : 'color:#059669');
                ?>
                <td style="font-family:monospace;font-size:12px"><?= Helpers::e($cv['ip_address']) ?></td>
                <td data-cell="ipqs">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:13px;font-weight:700;background:<?= $scoreBg ?>;color:<?= $scoreColor ?>">
                        <?php if ($scoreNull): ?>
                        <span style="width:7px;height:7px;border-radius:50%;background:#9CA3AF;animation:pulse 1.5s ease-in-out infinite;display:inline-block"></span>
                        Pending
                        <?php else: ?>
                        <?= $score ?>
                        <?php endif; ?>
                    </span>
                </td>
                <!-- IPQuery score -->
                <td data-cell="ipquery" style="white-space:nowrap">
                    <?= $scoreBadge(array_key_exists('ipquery_risk_score', $cv) && $cv['ipquery_risk_score'] !== null ? (int)$cv['ipquery_risk_score'] : null) ?>
                    <?php if ($iqLevel): ?><div style="font-size:10px;<?= $iqLevelStyle ?>;text-transform:uppercase"><?= $iqLevel ?></div><?php endif; ?>
                    <?php if ($iqFlags): ?><div style="margin-top:2px"><?= $iqFlags ?></div><?php endif; ?>
                </td>
                <!-- FraudDefense score -->
                <td data-cell="frauddefense" style="white-space:nowrap">
                    <?= $scoreBadge(array_key_exists('frauddefense_score', $cv) && $cv['frauddefense_score'] !== null ? (int)$cv['frauddefense_score'] : null) ?>
                    <?php if (!empty($cv['frauddefense_status']) && $cv['frauddefense_status'] !== 'allowed'):
                        $fdStCol = $cv['frauddefense_status'] === 'blocked' ? '#DC2626' : '#D97706'; ?>
                    <div style="font-size:10px;color:<?= $fdStCol ?>;text-transform:uppercase"><?= Helpers::e($cv['frauddefense_status']) ?></div>
                    <?php endif; ?>
                </td>
                <!-- Scamalytics score -->
                <td data-cell="scamalytics" style="white-space:nowrap">
                    <?= $scoreBadge(array_key_exists('scamalytics_score', $cv) && $cv['scamalytics_score'] !== null ? (int)$cv['scamalytics_score'] : null) ?>
                    <?php if (!empty($cv['scamalytics_status']) && $cv['scamalytics_status'] !== 'allowed'):
                        $scStCol = $cv['scamalytics_status'] === 'blocked' ? '#DC2626' : '#D97706'; ?>
                    <div style="font-size:10px;color:<?= $scStCol ?>;text-transform:uppercase"><?= Helpers::e($cv['scamalytics_status']) ?></div>
                    <?php endif; ?>
                </td>
                <!-- ProxyCheck score -->
                <td data-cell="proxycheck" style="white-space:nowrap">
                    <?= $scoreBadge(array_key_exists('proxycheck_score', $cv) && $cv['proxycheck_score'] !== null ? (int)$cv['proxycheck_score'] : null) ?>
                    <?php if (!empty($cv['proxycheck_is_proxy'])): ?><div style="font-size:10px;background:#fef3c7;color:#b45309;padding:1px 4px;border-radius:3px;display:inline-block">PRX</div><?php endif; ?>
                    <?php if (!empty($cv['proxycheck_is_vpn'])): ?><div style="font-size:10px;background:#ede9fe;color:#7c3aed;padding:1px 4px;border-radius:3px;display:inline-block">VPN</div><?php endif; ?>
                </td>
                <!-- FraudLabs Pro score -->
                <td data-cell="fraudlabspro" style="white-space:nowrap">
                    <?= $scoreBadge(array_key_exists('fraudlabspro_score', $cv) && $cv['fraudlabspro_score'] !== null ? (int)$cv['fraudlabspro_score'] : null) ?>
                    <?php if (!empty($cv['fraudlabspro_flp_status'])):
                        $flpSt = strtoupper($cv['fraudlabspro_flp_status']);
                        $flpStCol = $flpSt === 'REJECT' ? '#DC2626' : ($flpSt === 'REVIEW' ? '#D97706' : '#059669'); ?>
                    <div style="font-size:10px;color:<?= $flpStCol ?>;font-weight:700"><?= Helpers::e($flpSt) ?></div>
                    <?php endif; ?>
                </td>
                <td data-cell="checked_at" class="text-muted" style="font-size:11px;white-space:nowrap">
                    <?php if ($cv['fraud_checked_at']): ?>
                        <?= Helpers::e(date('M j H:i', strtotime($cv['fraud_checked_at']))) ?>
                    <?php else: ?>
                        <span style="color:#D1D5DB">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $stBadge = match($cv['status']) {
                        'approved' => 'badge-success',
                        'rejected' => 'badge-danger',
                        default    => 'badge-warning',
                    };
                    ?>
                    <span class="badge <?= $stBadge ?>"><?= ucfirst(Helpers::e($cv['status'])) ?></span>
                </td>
                <td class="fw-bold text-sm">$<?= number_format($cv['payout'], 2) ?></td>
                <td class="text-muted" style="font-size:12px;white-space:nowrap"><?= Helpers::e(date('M j, Y H:i', strtotime($cv['converted_at']))) ?></td>
                <td>
                    <?php if ($cv['status'] !== 'approved'): ?>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="update_conv_status">
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($cv['conversion_id']) ?>">
                        <input type="hidden" name="conv_status" value="approved">
                        <button class="btn btn-success btn-sm" title="Approve">&#10003;</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($cv['status'] !== 'rejected'): ?>
                    <button type="button" class="btn btn-danger btn-sm" title="Reject"
                            onclick="openRejectModal('<?= Helpers::e($cv['conversion_id']) ?>')">&#10007;</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
/* ── Fraud Score Report — Live streaming engine ──────────────────────────
 *
 * Replaces the previous "click to recheck → reload page" flow with a
 * silent background polling loop that:
 *
 *   1. Fires immediately on page load (no admin click needed)
 *   2. Calls /admin/fraud-score-report?action=tick which scores up to N
 *      pending conversions and returns the freshly computed scores
 *   3. Updates the matching <tr data-conv="…"> cells IN PLACE via DOM —
 *      never reloads the page, never disturbs the admin's scroll position
 *   4. Slows polling once everything is fresh (heartbeat mode)
 *   5. Pauses when the browser tab is hidden (saves API quota)
 *
 * The legacy `recheckPending()` and the auto-reload timer are gone —
 * admins now see scores stream in live without any manual action.
 * ───────────────────────────────────────────────────────────────────── */
(function(){
    var BATCH         = 5;       // conversions per tick
    var FAST_POLL_MS  = 4000;    // poll cadence while there is a backlog
    var SLOW_POLL_MS  = 30000;   // heartbeat once everything is fresh
    var ENDPOINT      = '/admin/fraud-score-report';

    var heartbeatEl   = document.getElementById('fsr-live-heartbeat');
    var statusEl      = document.getElementById('fsr-live-status');
    var pendingEl     = document.getElementById('fsr-pending-count');
    var timer         = null;
    var inflight      = false;

    function setStatus(text){ if (statusEl) statusEl.textContent = text; }
    function pulseRow(tr){
        tr.style.transition = 'background-color .8s';
        tr.style.backgroundColor = '#ECFDF5';
        setTimeout(function(){ tr.style.backgroundColor = ''; }, 1500);
    }

    // Render a score badge identical to the server-side $scoreBadge helper.
    function scoreBadge(score){
        if (score === null || score === undefined || score === '') {
            return '<span style="color:#D1D5DB;font-size:11px">—</span>';
        }
        var n = Number(score);
        var bg, col;
        if (n >= 75)      { bg = '#FEF2F2'; col = '#DC2626'; }
        else if (n >= 40) { bg = '#FFFBEB'; col = '#D97706'; }
        else              { bg = '#ECFDF5'; col = '#059669'; }
        return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:700;background:'+bg+';color:'+col+'">'+n+'</span>';
    }
    function pillFlag(label, fg, bg){
        return '<span style="font-size:10px;background:'+bg+';color:'+fg+';padding:1px 4px;border-radius:3px;margin:1px">'+label+'</span>';
    }

    // Apply one row of fresh data from the server to the live DOM.
    function applyUpdate(convId, data){
        var tr = document.querySelector('tr[data-conv="' + (window.CSS && CSS.escape ? CSS.escape(convId) : convId) + '"]');
        if (!tr) return;

        // IPQS / aggregate
        var ipqsCell = tr.querySelector('[data-cell="ipqs"]');
        if (ipqsCell && data.fraud_score !== null && data.fraud_score !== undefined) {
            var s = Number(data.fraud_score);
            var bg = s >= 75 ? '#FEF2F2' : (s >= 40 ? '#FFFBEB' : '#ECFDF5');
            var col = s >= 75 ? '#DC2626' : (s >= 40 ? '#D97706' : '#059669');
            ipqsCell.innerHTML = '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:13px;font-weight:700;background:'+bg+';color:'+col+'">'+s+'</span>';
        }

        // IPQuery
        var iqCell = tr.querySelector('[data-cell="ipquery"]');
        if (iqCell) {
            var iq = data.ipquery_risk_score;
            var flags = '';
            if (data.ipquery_vpn)        flags += pillFlag('VPN','#7c3aed','#ede9fe');
            if (data.ipquery_proxy)      flags += pillFlag('PRX','#b45309','#fef3c7');
            if (data.ipquery_tor)        flags += pillFlag('TOR','#dc2626','#fee2e2');
            if (data.ipquery_datacenter) flags += pillFlag('DC', '#0369a1','#f0f9ff');
            var levelHtml = '';
            if (data.ipquery_risk_level) {
                var lvl = data.ipquery_risk_level;
                var lvlCol = lvl === 'high' ? '#DC2626' : (lvl === 'medium' ? '#D97706' : '#059669');
                levelHtml = '<div style="font-size:10px;color:'+lvlCol+';text-transform:uppercase">'+lvl+'</div>';
            }
            iqCell.innerHTML = scoreBadge(iq) + levelHtml + (flags ? '<div style="margin-top:2px">'+flags+'</div>' : '');
        }

        // Other provider columns (same pattern)
        function setProviderCell(sel, score, status, statusColors){
            var el = tr.querySelector(sel); if (!el) return;
            var extra = '';
            if (status && status !== 'allowed') {
                var c = statusColors[status] || '#D97706';
                extra = '<div style="font-size:10px;color:'+c+';text-transform:uppercase">'+String(status).toUpperCase()+'</div>';
            }
            el.innerHTML = scoreBadge(score) + extra;
        }
        setProviderCell('[data-cell="frauddefense"]', data.frauddefense_score, data.frauddefense_status, {blocked:'#DC2626'});
        setProviderCell('[data-cell="scamalytics"]',  data.scamalytics_score,  data.scamalytics_status,  {blocked:'#DC2626'});

        var pcCell = tr.querySelector('[data-cell="proxycheck"]');
        if (pcCell) {
            var extra = '';
            if (data.proxycheck_is_proxy) extra += '<div style="font-size:10px;background:#fef3c7;color:#b45309;padding:1px 4px;border-radius:3px;display:inline-block">PRX</div>';
            if (data.proxycheck_is_vpn)   extra += '<div style="font-size:10px;background:#ede9fe;color:#7c3aed;padding:1px 4px;border-radius:3px;display:inline-block">VPN</div>';
            pcCell.innerHTML = scoreBadge(data.proxycheck_score) + extra;
        }

        var flpCell = tr.querySelector('[data-cell="fraudlabspro"]');
        if (flpCell) {
            var extra = '';
            if (data.fraudlabspro_flp_status) {
                var st = String(data.fraudlabspro_flp_status).toUpperCase();
                var stCol = st === 'REJECT' ? '#DC2626' : (st === 'REVIEW' ? '#D97706' : '#059669');
                extra = '<div style="font-size:10px;color:'+stCol+';font-weight:700">'+st+'</div>';
            }
            flpCell.innerHTML = scoreBadge(data.fraudlabspro_score) + extra;
        }

        var chkCell = tr.querySelector('[data-cell="checked_at"]');
        if (chkCell && data.fraud_checked_at) {
            var d = new Date(String(data.fraud_checked_at).replace(' ', 'T'));
            var label = d.toLocaleDateString(undefined, { month:'short', day:'numeric' }) + ' ' +
                        d.toLocaleTimeString(undefined,  { hour:'2-digit', minute:'2-digit', hour12:false });
            chkCell.textContent = label;
        }

        pulseRow(tr);
    }

    function tick(){
        if (inflight) return;
        if (document.hidden) return;        // pause when tab not visible
        inflight = true;
        setStatus('Live · streaming');
        if (heartbeatEl) heartbeatEl.style.opacity = '1';

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=tick&batch=' + BATCH
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            inflight = false;
            if (!d) return;
            var updates = d.updates || {};
            Object.keys(updates).forEach(function(convId){
                applyUpdate(convId, updates[convId]);
            });
            if (pendingEl) pendingEl.textContent = d.remaining;
            // Switch cadence once the backlog clears.
            schedule(d.remaining > 0 ? FAST_POLL_MS : SLOW_POLL_MS);
            setStatus(d.remaining > 0
                ? 'Live · streaming (' + d.remaining + ' queued)'
                : 'Live · up to date');
        })
        .catch(function(){
            inflight = false;
            setStatus('Live · paused (network)');
            schedule(SLOW_POLL_MS);
        });
    }

    function schedule(ms){
        if (timer) clearTimeout(timer);
        timer = setTimeout(tick, ms);
    }

    // Resume promptly on tab refocus.
    document.addEventListener('visibilitychange', function(){
        if (!document.hidden) { tick(); }
    });

    // Boot the loop. First call fires immediately so the admin sees data
    // updating the moment the page renders.
    tick();

    // ── Bulk Reject Logic ──
    window.fsrToggleAll = function(el) {
        var cbs = document.querySelectorAll('.fsr-conv-cb');
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = el.checked;
        fsrCheckSelection();
    };
    window.fsrCheckSelection = function() {
        var btn = document.getElementById('fsr-bulk-reject-btn');
        var checkedCount = document.querySelectorAll('.fsr-conv-cb:checked').length;
        if (checkedCount > 0) {
            btn.style.display = 'flex';
            btn.innerHTML = '&#10007; Reject Selected (' + checkedCount + ')';
        } else {
            btn.style.display = 'none';
        }
    };
    window.bulkRejectSelected = function() {
        var cbs = document.querySelectorAll('.fsr-conv-cb:checked');
        if (cbs.length === 0) return;
        var reason = prompt("Enter rejection reason (optional):", "Bulk Fraud Rejection");
        if (reason === null) return; // user cancelled

        var formData = new URLSearchParams();
        var tokenInput = document.querySelector('input[name="_token"]');
        formData.append('_token', tokenInput ? tokenInput.value : '');
        formData.append('action', 'bulk_reject_fraud');
        formData.append('rejection_reason', reason);
        for (var i = 0; i < cbs.length; i++) {
            formData.append('conversion_ids[]', cbs[i].value);
        }

        var btn = document.getElementById('fsr-bulk-reject-btn');
        var oldHtml = btn.innerHTML;
        btn.innerHTML = 'Working...';
        btn.disabled = true;

        fetch('/admin/fraud-score-report', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData.toString()
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.status === 'ok') {
                window.location.reload();
            } else {
                alert("Error rejecting conversions.");
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }
        })
        .catch(function(){
            alert("Network error.");
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        });
    };
})();
</script>

<?php
// Reject-with-reason modal — submits to current page with the same hidden
// fields the existing approve form uses (action + conversion_id + conv_status).
$rejectFormAction  = '/admin/fraud-score-report';
$rejectStatusField = 'conv_status';
$rejectStatusValue = 'rejected';
$rejectExtraHidden = ['action' => 'update_conv_status'];
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
