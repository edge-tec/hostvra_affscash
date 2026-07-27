<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
/* ── Layout ── */
.gpt-wrap   { display:grid; grid-template-columns:480px 1fr; gap:24px; align-items:start; }
@media(max-width:980px){ .gpt-wrap{ grid-template-columns:1fr; } }

/* ── Form card ── */
.gpt-form-card  { background:#fff; border:1px solid #E2E8F0; border-radius:12px; overflow:hidden; }
.gpt-form-head  { background:linear-gradient(135deg,#1E1B4B,#4F46E5); padding:18px 22px; }
.gpt-form-title { color:#fff; font-size:16px; font-weight:800; margin:0 0 2px; }
.gpt-form-sub   { color:rgba(255,255,255,.65); font-size:12px; margin:0; }
.gpt-form-body  { padding:22px; }

/* ── Inputs ── */
.gpt-label { font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; display:block; margin-bottom:6px; }
.gpt-input { width:100%; padding:10px 12px; border:1.5px solid #CBD5E1; border-radius:8px; font-size:13px; color:#1E293B; background:#F8FAFC; transition:.15s; box-sizing:border-box; font-family:monospace; }
.gpt-input:focus { outline:none; border-color:#4F46E5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }
.gpt-select { width:100%; padding:10px 12px; border:1.5px solid #CBD5E1; border-radius:8px; font-size:13px; color:#1E293B; background:#F8FAFC; transition:.15s; box-sizing:border-box; appearance:none; cursor:pointer; }
.gpt-select:focus { outline:none; border-color:#4F46E5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,.1); }

/* ── Button ── */
.gpt-btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px; background:linear-gradient(135deg,#4F46E5,#7C3AED); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:700; cursor:pointer; transition:.15s; margin-top:18px; }
.gpt-btn:hover:not(:disabled) { background:linear-gradient(135deg,#4338CA,#6D28D9);  box-shadow:0 4px 12px rgba(79,70,229,.35); }
.gpt-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; box-shadow:none; }

/* ── Spinner ── */
.gpt-spinner { width:16px; height:16px; border:2px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:gptSpin .65s linear infinite; display:none; }
@keyframes gptSpin { to{ transform:rotate(360deg); } }

/* ── Postback URL preview ── */
.gpt-pb-preview { background:#F0FDF4; border:1px solid #A7F3D0; border-radius:8px; padding:10px 14px; margin-top:10px; font-family:monospace; font-size:11px; color:#065F46; word-break:break-all; line-height:1.6; display:none; }
.gpt-pb-none    { background:#FFF7ED; border:1px solid #FED7AA; border-radius:8px; padding:10px 14px; margin-top:10px; font-size:12px; color:#92400E; display:none; }

/* ── How it works card ── */
.gpt-how-card { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:18px 20px; }
.gpt-how-title { font-size:12px; font-weight:800; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:14px; }
.gpt-how-step  { display:flex; gap:10px; align-items:flex-start; padding:7px 0; border-bottom:1px dashed #E2E8F0; }
.gpt-how-step:last-child { border-bottom:none; }
.gpt-how-num   { width:22px; height:22px; border-radius:50%; background:#EDE9FE; color:#5B21B6; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.gpt-how-text  { font-size:12px; color:#475569; line-height:1.5; }
.gpt-how-text strong { color:#1E293B; }

/* ── Result panel ── */
.gpt-result-panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; overflow:hidden; }
.gpt-result-empty { padding:56px 24px; text-align:center; color:#94A3B8; }
.gpt-result-empty-icon { font-size:34px; margin-bottom:10px; }
.gpt-result-empty-title { font-size:15px; font-weight:700; color:#CBD5E1; margin-bottom:4px; }
.gpt-result-empty-sub   { font-size:12px; }

/* ── Result content ── */
.gpt-result-banner { padding:18px 22px; display:flex; align-items:center; gap:14px; }
.gpt-result-banner.success { background:linear-gradient(135deg,#ECFDF5,#D1FAE5); border-bottom:1px solid #A7F3D0; }
.gpt-result-banner.failed  { background:linear-gradient(135deg,#FEF2F2,#FEE2E2); border-bottom:1px solid #FECACA; }
.gpt-result-banner.self    { background:linear-gradient(135deg,#FFFBEB,#FEF3C7); border-bottom:1px solid #FDE68A; }
.gpt-result-banner-icon { font-size:28px; flex-shrink:0; }
.gpt-result-banner-title { font-size:16px; font-weight:800; }
.gpt-result-banner-sub   { font-size:12px; opacity:.8; margin-top:2px; }
.gpt-result-banner.success .gpt-result-banner-title { color:#065F46; }
.gpt-result-banner.failed  .gpt-result-banner-title { color:#991B1B; }
.gpt-result-banner.self    .gpt-result-banner-title { color:#92400E; }

/* ── Info grid ── */
.gpt-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:18px 22px; border-bottom:1px solid #F1F5F9; }
@media(max-width:600px){ .gpt-info-grid{ grid-template-columns:1fr; } }
.gpt-info-item { background:#F8FAFC; border-radius:8px; padding:10px 14px; }
.gpt-info-item.full { grid-column:1/-1; }
.gpt-info-label { font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.gpt-info-value { font-family:monospace; font-size:12px; color:#1E293B; word-break:break-all; line-height:1.5; }
.gpt-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:8px; font-size:11px; font-weight:700; }
.gpt-badge.ok   { background:#D1FAE5; color:#065F46; }
.gpt-badge.warn { background:#FEF3C7; color:#92400E; }
.gpt-badge.err  { background:#FEE2E2; color:#991B1B; }

/* ── Steps timeline ── */
.gpt-steps { padding:18px 22px; border-bottom:1px solid #F1F5F9; }
.gpt-steps-title { font-size:11px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:12px; }
.gpt-step { display:flex; gap:12px; align-items:flex-start; padding:9px 0; border-bottom:1px solid #F8FAFC; }
.gpt-step:last-child { border-bottom:none; }
.gpt-step-num { width:26px; height:26px; border-radius:50%; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.gpt-step-num.ok   { background:#D1FAE5; color:#065F46; }
.gpt-step-num.warn { background:#FEF3C7; color:#92400E; }
.gpt-step-num.err  { background:#FEE2E2; color:#991B1B; }
.gpt-step-label  { font-size:13px; font-weight:700; color:#1E293B; margin-bottom:2px; }
.gpt-step-detail { font-size:11px; color:#64748B; font-family:monospace; line-height:1.6; word-break:break-all; }

/* ── Response body ── */
.gpt-resp-title { font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; margin-top:10px; }
.gpt-resp-box { background:#0F172A; border-radius:7px; padding:10px 12px; font-family:monospace; font-size:11px; color:#94A3B8; max-height:100px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; }

/* ── Log table ── */
.gpt-log-section { padding:18px 22px; }
.gpt-log-header  { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.gpt-log-title   { font-size:13px; font-weight:700; color:#1E293B; }
.gpt-log-table   { width:100%; border-collapse:collapse; font-size:12px; }
.gpt-log-table th { padding:8px 12px; text-align:left; font-size:10px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.04em; background:#F8FAFC; border-bottom:2px solid #E2E8F0; white-space:nowrap; }
.gpt-log-table td { padding:9px 12px; border-bottom:1px solid #F8FAFC; vertical-align:top; }
.gpt-log-table tr:last-child td { border-bottom:none; }
.gpt-log-table tr:hover td { background:#FAFAFA; }
.gpt-log-empty { text-align:center; padding:30px; color:#94A3B8; font-size:12px; }

/* ── No PB affiliates notice ── */
.gpt-no-pb-notice { background:#FFF7ED; border:1px solid #FED7AA; border-radius:10px; padding:16px 18px; margin-bottom:20px; font-size:13px; color:#92400E; }
</style>

<div class="page-header">
    <div>
        <h1>Affiliate Global Postback Test</h1>
        <p>Verify that an affiliate's global postback integration is working correctly — end-to-end, zero real impact.</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/admin/postbacks" class="btn btn-secondary">← Affiliate Postbacks</a>
    </div>
</div>

<?php if (empty($affiliatesWithPb)): ?>
<div class="gpt-no-pb-notice">
    <strong>⚠ No affiliates have set a Global Postback URL yet.</strong><br>
    Affiliates must go to their <strong>Postback Settings</strong> and save their tracker's global postback URL before you can run a test here.
</div>
<?php endif; ?>

<div class="gpt-wrap">

    <!-- ══ LEFT: Form ══════════════════════════════════════════════════════ -->
    <div>
        <div class="gpt-form-card">
            <div class="gpt-form-head">
                <p class="gpt-form-title">🔬 Run Postback Test</p>
                <p class="gpt-form-sub">Paste the affiliate's tracker-generated tracking link and click Test.</p>
            </div>
            <div class="gpt-form-body">

                <!-- Affiliate selector -->
                <div style="margin-bottom:16px">
                    <label class="gpt-label">Select Affiliate</label>
                    <select id="gpt-affiliate" class="gpt-select" onchange="gptOnAffChange()">
                        <option value="">— Select affiliate —</option>
                        <?php foreach ($affiliatesWithPb as $aff): ?>
                        <option value="<?= (int)$aff['id'] ?>"
                                data-pb="<?= Helpers::e($aff['global_postback_url']) ?>"
                                data-code="<?= Helpers::e($aff['affiliate_code']) ?>">
                            <?= Helpers::e($aff['name']) ?> (<?= Helpers::e($aff['affiliate_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Affiliate's current global postback URL preview -->
                    <div id="gpt-pb-preview" class="gpt-pb-preview">
                        <span style="font-size:10px;font-weight:700;color:#065F46;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:3px">Affiliate Global Postback URL</span>
                        <span id="gpt-pb-url-text" style="color:#047857"></span>
                    </div>
                    <div id="gpt-pb-none" class="gpt-pb-none">
                        ⚠ This affiliate has not set a global postback URL yet.
                    </div>
                </div>

                <!-- Tracking link -->
                <div style="margin-bottom:4px">
                    <label class="gpt-label" for="gpt-tracking-link">
                        Affiliate Tracking Link
                        <span style="color:#EF4444;margin-left:2px">*</span>
                    </label>
                    <textarea id="gpt-tracking-link" class="gpt-input"
                              rows="3"
                              placeholder="https://yourtracker.com/click?campaign_id=123&affiliate_id=ABC&click_id={clickid}"
                              style="resize:vertical;font-size:12px;line-height:1.6"></textarea>
                    <div style="font-size:11px;color:#94A3B8;margin-top:5px;line-height:1.5">
                        Paste the full tracking link generated by the affiliate's tracker. It should contain their click ID macro (e.g. <code>{clickid}</code>).
                    </div>
                </div>

                <!-- CSRF -->
                <input type="hidden" id="gpt-csrf" value="<?= Auth::generateCsrf() ?>">
                <input type="hidden" id="gpt-aff-id" value="">

                <!-- Start Test button -->
                <button class="gpt-btn" id="gpt-btn" onclick="gptRunTest()">
                    <span id="gpt-btn-label">▶ Start Test</span>
                    <span class="gpt-spinner" id="gpt-spinner"></span>
                </button>

                <div style="margin-top:12px;background:#F0F4FF;border:1px solid #C7D2FE;border-radius:8px;padding:10px 14px;font-size:11px;color:#3730A3;line-height:1.6">
                    🔒 <strong>Safe to run multiple times.</strong> Test conversions are zero-payout, hidden from reports, and do not affect affiliate balance, stats, or caps.
                </div>

            </div>
        </div>

        <!-- How it works -->
        <div class="gpt-how-card" style="margin-top:16px">
            <div class="gpt-how-title">How the Test Works</div>
            <?php foreach ([
                ['1', '<strong>Select the affiliate</strong> whose postback you want to verify.'],
                ['2', '<strong>Paste their tracking link</strong> — the link their tracker generated for your offer.'],
                ['3', 'System <strong>simulates a click</strong> on the tracking link with a unique test click ID.'],
                ['4', 'A <strong>test conversion</strong> is triggered (zero payout, hidden, no stats impact).'],
                ['5', 'System <strong>fires the global postback URL</strong> with the test click ID back to their tracker.'],
                ['6', 'Affiliate checks their tracker — a <strong>conversion should appear ✓</strong>'],
            ] as [$n, $txt]): ?>
            <div class="gpt-how-step">
                <div class="gpt-how-num"><?= $n ?></div>
                <div class="gpt-how-text"><?= $txt ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══ RIGHT: Result + Log ═════════════════════════════════════════════ -->
    <div>

        <!-- Result panel -->
        <div class="gpt-result-panel" id="gpt-result-panel">
            <div class="gpt-result-empty" id="gpt-result-empty">
                <div class="gpt-result-empty-icon">📡</div>
                <div class="gpt-result-empty-title">No test run yet</div>
                <div class="gpt-result-empty-sub">Configure the form and click <strong>Start Test</strong> to verify the postback.</div>
            </div>
            <div id="gpt-result-content" style="display:none"></div>
        </div>

        <!-- Test Log -->
        <div class="gpt-result-panel" style="margin-top:20px">
            <div class="gpt-log-section">
                <div class="gpt-log-header">
                    <div class="gpt-log-title">📋 Test History</div>
                    <button class="btn btn-secondary btn-sm" id="gpt-clear-btn" onclick="gptClearLogs()"
                            style="font-size:11px">🗑 Clear</button>
                </div>
                <div id="gpt-log-wrap">
                    <?php if (empty($recentLogs)): ?>
                    <div class="gpt-log-empty">No tests run yet.</div>
                    <?php else: ?>
                    <?php echo buildLogTable($recentLogs); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
function buildLogTable(array $logs): string {
    if (empty($logs)) return '<div class="gpt-log-empty">No tests run yet.</div>';
    $html = '<div style="overflow-x:auto"><table class="gpt-log-table">';
    $html .= '<thead><tr>
        <th>Affiliate</th>
        <th>Click ID</th>
        <th>HTTP</th>
        <th>Status</th>
        <th>Time</th>
        <th>Tested At</th>
    </tr></thead><tbody>';
    foreach ($logs as $log) {
        $ok  = (bool)$log['is_success'];
        $hs  = (int)($log['http_status'] ?? 0);
        $dot = $ok ? '#10B981' : '#EF4444';
        $html .= '<tr>';
        $html .= '<td><div style="font-weight:700;color:#1E293B;font-size:12px">' . htmlspecialchars($log['affiliate_name'] ?? '—') . '</div>'
               . '<div style="font-size:10px;color:#94A3B8;font-family:monospace">' . htmlspecialchars($log['affiliate_code'] ?? '') . '</div></td>';
        $html .= '<td style="font-family:monospace;font-size:10px;color:#64748B;white-space:nowrap">'
               . htmlspecialchars(substr($log['click_id'] ?? '—', 0, 16)) . '…</td>';
        $bg  = ($hs >= 200 && $hs < 300) ? '#D1FAE5' : ($hs > 0 ? '#FEF3C7' : '#FEE2E2');
        $clr = ($hs >= 200 && $hs < 300) ? '#065F46' : ($hs > 0 ? '#92400E' : '#991B1B');
        $html .= '<td><span style="background:{$bg};color:{$clr};border-radius:5px;padding:2px 7px;font-size:11px;font-weight:700;font-family:monospace;background:' . $bg . ';color:' . $clr . '">'
               . ($hs ?: 'Fail') . '</span></td>';
        $html .= '<td><span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:' . ($ok ? '#065F46' : '#991B1B') . '">'
               . '<span style="width:7px;height:7px;border-radius:50%;background:' . $dot . ';display:inline-block"></span>'
               . ($ok ? 'Success' : 'Failed') . '</span></td>';
        $html .= '<td style="font-size:11px;color:#94A3B8;white-space:nowrap">' . ($log['time_ms'] ? $log['time_ms'] . 'ms' : '—') . '</td>';
        $html .= '<td style="font-size:11px;color:#64748B;white-space:nowrap">' . date('M j, H:i:s', strtotime($log['tested_at'])) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}
?>

<script>
var _gptCsrf = document.getElementById('gpt-csrf').value;

function gptOnAffChange() {
    var sel = document.getElementById('gpt-affiliate');
    var opt = sel.options[sel.selectedIndex];
    var pb  = opt ? opt.getAttribute('data-pb') : '';
    var id  = opt ? opt.value : '';

    document.getElementById('gpt-aff-id').value = id;

    var preview = document.getElementById('gpt-pb-preview');
    var none    = document.getElementById('gpt-pb-none');
    var urlText = document.getElementById('gpt-pb-url-text');

    if (id && pb) {
        urlText.textContent = pb;
        preview.style.display = 'block';
        none.style.display    = 'none';
    } else if (id && !pb) {
        preview.style.display = 'none';
        none.style.display    = 'block';
    } else {
        preview.style.display = 'none';
        none.style.display    = 'none';
    }
}

function gptRunTest() {
    var trackingLink = document.getElementById('gpt-tracking-link').value.trim();
    var affId        = document.getElementById('gpt-aff-id').value;
    var btn          = document.getElementById('gpt-btn');
    var label        = document.getElementById('gpt-btn-label');
    var spinner      = document.getElementById('gpt-spinner');

    if (!trackingLink) {
        alert('Please paste the affiliate tracking link.');
        document.getElementById('gpt-tracking-link').focus();
        return;
    }

    // Show loading state
    btn.disabled          = true;
    label.textContent     = 'Running Test…';
    spinner.style.display = 'inline-block';

    // Show skeleton loading in result panel
    document.getElementById('gpt-result-empty').style.display = 'none';
    document.getElementById('gpt-result-content').style.display = 'block';
    document.getElementById('gpt-result-content').innerHTML = gptLoadingHTML();

    var fd = new FormData();
    fd.append('_token',       _gptCsrf);
    fd.append('tracking_link', trackingLink);
    if (affId) fd.append('affiliate_id', affId);

    fetch('/admin/affiliate-global-pb-test?action=run_test', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
    })
    .then(function(r) {
        return r.text().then(function(t) {
            try { return JSON.parse(t); }
            catch(e) { throw new Error('Unexpected response: ' + t.substring(0, 200)); }
        });
    })
    .then(function(d) {
        gptRenderResult(d);
        gptRefreshLogs();
        // Refresh CSRF for next run
        _gptCsrf = document.getElementById('gpt-csrf').value;
    })
    .catch(function(e) {
        gptRenderResult({ error: e.message });
    })
    .finally(function() {
        btn.disabled          = false;
        label.textContent     = '▶ Start Test';
        spinner.style.display = 'none';
    });
}

function gptLoadingHTML() {
    return '<div style="padding:30px 22px;text-align:center;color:#94A3B8">'
         + '<div style="width:36px;height:36px;border:3px solid #E2E8F0;border-top-color:#4F46E5;border-radius:50%;animation:gptSpin .65s linear infinite;margin:0 auto 12px"></div>'
         + '<div style="font-weight:700;color:#64748B;font-size:14px">Running postback test…</div>'
         + '<div style="font-size:12px;margin-top:4px">Simulating click → conversion → postback</div>'
         + '</div>';
}

function gptRenderResult(d) {
    var html = '';

    if (d.error) {
        html += '<div class="gpt-result-banner failed">'
              + '<div class="gpt-result-banner-icon">✗</div>'
              + '<div><div class="gpt-result-banner-title">Test Failed</div>'
              + '<div class="gpt-result-banner-sub">' + escHtml(d.error) + '</div></div>'
              + '</div>';
        document.getElementById('gpt-result-content').innerHTML = html;
        return;
    }

    var bannerClass = d.success ? 'success' : (d.is_self ? 'self' : 'failed');
    var bannerIcon  = d.success ? '✓' : (d.is_self ? '⚠️' : '✗');
    var bannerTitle = d.success
        ? 'Postback Reached Affiliate Tracker!'
        : (d.is_self
            ? 'Self-Loop Detected'
            : 'Postback Failed to Reach Tracker');
    var bannerSub = d.success
        ? 'The global postback fired successfully. Affiliate\'s tracker should show a conversion.'
        : (d.is_self
            ? 'The affiliate\'s postback URL points back to this system. They must use an external tracker URL.'
            : 'Connection failed (HTTP 0). Check the affiliate\'s postback URL domain and firewall settings.');

    html += '<div class="gpt-result-banner ' + bannerClass + '">'
          + '<div class="gpt-result-banner-icon">' + bannerIcon + '</div>'
          + '<div><div class="gpt-result-banner-title">' + escHtml(bannerTitle) + '</div>'
          + '<div class="gpt-result-banner-sub">' + escHtml(bannerSub) + '</div></div>'
          + '</div>';

    // Info grid
    var httpBadgeClass = d.http_status >= 200 && d.http_status < 300 ? 'ok' : (d.http_status > 0 ? 'warn' : 'err');
    var httpLabel = d.http_status > 0 ? 'HTTP ' + d.http_status : 'Failed (0 / Timeout)';
    var statusBadgeClass = d.success ? 'ok' : (d.is_self ? 'warn' : 'err');
    var statusLabel = d.success ? '✓ Success' : (d.is_self ? '⚠ Self-Loop' : '✗ Failed');

    html += '<div class="gpt-info-grid">'
          + gptInfoItem('Affiliate', escHtml(d.affiliate_name || '—') + ' <span style="color:#94A3B8;font-size:10px">' + escHtml(d.affiliate_code || '') + '</span>')
          + gptInfoItem('Postback Status', '<span class="gpt-badge ' + statusBadgeClass + '">' + statusLabel + '</span>')
          + gptInfoItem('Click ID (Internal)', escHtml(d.click_id || '—'))
          + gptInfoItem('Tracker Click ID', escHtml(d.tracker_click_id || '—'))
          + gptInfoItem('Conversion ID', escHtml(d.conversion_id || '—'))
          + gptInfoItem('HTTP Response', '<span class="gpt-badge ' + httpBadgeClass + '">' + escHtml(httpLabel) + '</span>')
          + gptInfoItem('Response Time', d.time_ms ? d.time_ms + 'ms' : '—')
          + gptInfoItem('Conversion Status', '<span class="gpt-badge ok">✓ Test · No Real Impact</span>')
          + '</div>';

    // Fired URL
    html += '<div style="padding:14px 22px;border-bottom:1px solid #F1F5F9">'
          + '<div class="gpt-info-label" style="font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Fired Postback URL</div>'
          + '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:7px;padding:9px 12px;font-family:monospace;font-size:11px;color:#334155;word-break:break-all;line-height:1.6">'
          + escHtml(d.fired_url || '—')
          + '</div>'
          + (d.response ? '<div class="gpt-resp-title">Tracker Response Body</div>'
             + '<div class="gpt-resp-box">' + escHtml(d.response) + '</div>' : '')
          + '</div>';

    // Steps
    if (d.steps && d.steps.length) {
        html += '<div class="gpt-steps"><div class="gpt-steps-title">Test Steps</div>';
        d.steps.forEach(function(s) {
            html += '<div class="gpt-step">'
                  + '<div class="gpt-step-num ' + (s.status || 'ok') + '">' + s.step + '</div>'
                  + '<div><div class="gpt-step-label">' + escHtml(s.label) + '</div>'
                  + '<div class="gpt-step-detail">' + escHtml(s.detail) + '</div></div>'
                  + '</div>';
        });
        html += '</div>';
    }

    // Timestamp
    var now = new Date();
    html += '<div style="padding:10px 22px;background:#F8FAFC;font-size:11px;color:#94A3B8;border-top:1px solid #F1F5F9">'
          + '🕐 Tested at: ' + now.toLocaleString()
          + '</div>';

    document.getElementById('gpt-result-content').innerHTML = html;
}

function gptInfoItem(label, val) {
    return '<div class="gpt-info-item">'
         + '<div class="gpt-info-label">' + label + '</div>'
         + '<div class="gpt-info-value">' + val + '</div>'
         + '</div>';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function gptRefreshLogs() {
    fetch('/admin/affiliate-global-pb-test?action=get_logs', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.logs) return;
        // Re-render via server template by doing a small page section reload
        fetch('/admin/affiliate-global-pb-test?_log_fragment=1', { credentials: 'same-origin' })
        .then(function() {})
        .catch(function() {});
        // Build log table in JS
        var wrap = document.getElementById('gpt-log-wrap');
        if (!wrap) return;
        if (!d.logs.length) {
            wrap.innerHTML = '<div class="gpt-log-empty">No tests run yet.</div>';
            return;
        }
        var html = '<div style="overflow-x:auto"><table class="gpt-log-table">';
        html += '<thead><tr><th>Affiliate</th><th>Click ID</th><th>HTTP</th><th>Status</th><th>Time</th><th>Tested At</th></tr></thead><tbody>';
        d.logs.forEach(function(log) {
            var ok  = log.is_success == 1;
            var hs  = parseInt(log.http_status) || 0;
            var dot = ok ? '#10B981' : '#EF4444';
            var hsBg  = (hs >= 200 && hs < 300) ? '#D1FAE5' : (hs > 0 ? '#FEF3C7' : '#FEE2E2');
            var hsClr = (hs >= 200 && hs < 300) ? '#065F46' : (hs > 0 ? '#92400E' : '#991B1B');
            html += '<tr>'
                  + '<td><div style="font-weight:700;color:#1E293B;font-size:12px">' + escHtml(log.affiliate_name||'—') + '</div>'
                  + '<div style="font-size:10px;color:#94A3B8;font-family:monospace">' + escHtml(log.affiliate_code||'') + '</div></td>'
                  + '<td style="font-family:monospace;font-size:10px;color:#64748B;white-space:nowrap">' + escHtml((log.click_id||'—').substring(0,16)) + '…</td>'
                  + '<td><span style="background:' + hsBg + ';color:' + hsClr + ';border-radius:5px;padding:2px 7px;font-size:11px;font-weight:700;font-family:monospace">' + (hs || 'Fail') + '</span></td>'
                  + '<td><span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:' + (ok?'#065F46':'#991B1B') + '">'
                  + '<span style="width:7px;height:7px;border-radius:50%;background:' + dot + ';display:inline-block"></span>'
                  + (ok ? 'Success' : 'Failed') + '</span></td>'
                  + '<td style="font-size:11px;color:#94A3B8;white-space:nowrap">' + (log.time_ms ? log.time_ms + 'ms' : '—') + '</td>'
                  + '<td style="font-size:11px;color:#64748B;white-space:nowrap">' + escHtml(log.tested_at||'') + '</td>'
                  + '</tr>';
        });
        html += '</tbody></table></div>';
        wrap.innerHTML = html;
    })
    .catch(function() {});
}

function gptClearLogs() {
    if (!confirm('Clear all postback test history?')) return;
    var fd = new FormData();
    fd.append('_token', _gptCsrf);
    fetch('/admin/affiliate-global-pb-test?action=clear_logs', {
        method: 'POST', credentials: 'same-origin', body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            document.getElementById('gpt-log-wrap').innerHTML = '<div class="gpt-log-empty">No tests run yet.</div>';
        }
    })
    .catch(function() {});
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
