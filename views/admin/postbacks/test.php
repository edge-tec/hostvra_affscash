<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.pt-wrap   { display:grid; grid-template-columns:460px 1fr; gap:22px; align-items:start; }
@media(max-width:960px){ .pt-wrap{ grid-template-columns:1fr; } }
.pt-empty  { text-align:center; padding:52px 24px; color:#94A3B8; }

/* Step timeline */
.pt-steps { margin-bottom:16px; }
.pt-step  { display:flex; gap:12px; align-items:flex-start; padding:10px 0; border-bottom:1px solid #F1F5F9; }
.pt-step:last-child { border-bottom:none; }
.pt-step-num { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0; margin-top:1px; }
.pt-step-num.ok   { background:#D1FAE5; color:#065F46; }
.pt-step-num.warn { background:#FEF3C7; color:#92400E; }
.pt-step-num.err  { background:#FEE2E2; color:#991B1B; }
.pt-step-label  { font-size:13px; font-weight:700; color:#1E293B; margin-bottom:2px; }
.pt-step-detail { font-size:12px; color:#64748B; line-height:1.5; font-family:monospace; }

/* Result cards */
.pt-result-card { border-radius:10px; overflow:hidden; margin-bottom:12px; }
.pt-result-card.ok  { border:1px solid #A7F3D0; }
.pt-result-card.err { border:1px solid #FECACA; }
.pt-result-head     { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:11px 16px; flex-wrap:wrap; }
.pt-result-head.ok  { background:#ECFDF5; }
.pt-result-head.err { background:#FEF2F2; }
.pt-result-body     { padding:14px 16px; border-top:1px solid #F1F5F9; background:#fff; }
.pt-url-box { font-family:monospace; font-size:11px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; padding:8px 10px; word-break:break-all; line-height:1.6; color:#334155; }
.pt-url-box.ok  { border-color:#A7F3D0; background:#F0FDF4; }
.pt-url-box.err { border-color:#FECACA; background:#FFF5F5; }
.pt-resp-box { background:#0F172A; border-radius:7px; padding:10px 12px; font-family:monospace; font-size:11px; color:#94A3B8; max-height:120px; overflow-y:auto; white-space:pre-wrap; word-break:break-all; margin-top:8px; }
.pt-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:10px; font-size:11px; font-weight:700; }
.pt-badge.ok  { background:#D1FAE5; color:#065F46; }
.pt-badge.err { background:#FEE2E2; color:#991B1B; }

/* ID box */
.pt-id-box  { background:linear-gradient(135deg,#1E1B4B,#312E81); border-radius:10px; padding:16px 20px; margin-bottom:14px; }
.pt-id-row  { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px; }
.pt-id-row:last-child{ margin-bottom:0; }
.pt-id-label{ font-size:10px; font-weight:700; color:rgba(255,255,255,.45); text-transform:uppercase; letter-spacing:.05em; min-width:100px; }
.pt-id-val  { font-family:monospace; font-size:11px; color:#C7D2FE; word-break:break-all; flex:1; }
.pt-copy-btn{ background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); color:#C7D2FE; border-radius:5px; padding:2px 8px; font-size:10px; cursor:pointer; white-space:nowrap; transition:.12s; }
.pt-copy-btn:hover{ background:rgba(255,255,255,.2); }

/* Summary */
.pt-summary { border-radius:10px; padding:14px 16px; margin-bottom:16px; display:flex; align-items:center; gap:12px; }
.pt-summary.ok  { background:#ECFDF5; border:1px solid #A7F3D0; }
.pt-summary.warn{ background:#FFF7ED; border:1px solid #FED7AA; }

.pt-log-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
.pt-log-dot.ok  { background:#10B981; }
.pt-log-dot.err { background:#EF4444; }

.pt-spinner { display:none; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:ptSpin .6s linear infinite; }
@keyframes ptSpin { to{ transform:rotate(360deg); } }

/* Postback preview */
.pb-preview { background:#F0FDF4; border:1px solid #A7F3D0; border-radius:8px; padding:10px 14px; margin-top:12px; }
.pb-preview-none { background:#FFF7ED; border:1px solid #FED7AA; border-radius:8px; padding:10px 14px; margin-top:12px; font-size:12px; color:#92400E; }

/* Workflow steps in sidebar */
.workflow-step { display:flex; gap:10px; align-items:flex-start; padding:8px 0; }
.workflow-num  { width:22px; height:22px; border-radius:50%; background:#EDE9FE; color:#5B21B6; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
</style>

<div class="page-header">
    <div>
        <h1>Postback Test</h1>
        <p>Simulate a real conversion for any affiliate tracking link — tests the full click → conversion → postback pipeline.</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/admin/postbacks" class="btn btn-secondary">📡 Manage Postbacks</a>
        <a href="/admin/postbacks" class="btn btn-secondary">← Global Postbacks</a>
    </div>
</div>

<div class="pt-wrap">

    <!-- ══ LEFT: Form ══ -->
    <div>
        <div class="card">
            <div class="card-header"><span class="card-title">🚀 Conversion Test</span></div>
            <div class="card-body">

                <!-- How it works -->
                <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 14px;margin-bottom:20px">
                    <div style="font-size:11px;font-weight:700;color:#1E40AF;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">How It Works</div>
                    <?php $wSteps = [
                        'Affiliate sends you their tracker link (any format)',
                        'Select the affiliate and our offer from the dropdowns',
                        'Paste their tracking link below',
                        'Click Test — a real click + conversion is recorded and the postback fires to their tracker with click_id + payout',
                    ]; foreach ($wSteps as $i => $s): ?>
                    <div class="workflow-step">
                        <div class="workflow-num"><?= $i+1 ?></div>
                        <div style="font-size:12px;color:#1E40AF;line-height:1.5"><?= $s ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form id="pt-form">
                    <?= Helpers::csrf() ?>

                    <!-- Affiliate select -->
                    <div class="form-group">
                        <label style="font-weight:700;font-size:13px">
                            Affiliate <span style="color:#EF4444">*</span>
                        </label>
                        <select id="pt-affiliate" name="affiliate_id" class="form-control" required>
                            <option value="">— Select Affiliate —</option>
                            <?php foreach ($affiliateList as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Offer select (loaded dynamically) -->
                    <div class="form-group">
                        <label style="font-weight:700;font-size:13px">
                            Offer <span style="color:#EF4444">*</span>
                        </label>
                        <select id="pt-offer" name="offer_id" class="form-control" required disabled>
                            <option value="">— Select Affiliate First —</option>
                        </select>
                        <div id="pt-payout-hint" style="font-size:11px;color:#94A3B8;margin-top:4px;display:none">
                            Default payout: <strong id="pt-payout-val"></strong>
                        </div>
                    </div>

                    <!-- Postback preview -->
                    <div id="pt-pb-preview" style="display:none"></div>

                    <!-- Tracking link -->
                    <div class="form-group">
                        <label style="font-weight:700;font-size:13px">
                            Affiliate Tracking Link <span style="color:#EF4444">*</span>
                        </label>
                        <textarea id="pt-url" name="tracking_link" class="form-control" rows="3"
                            placeholder="https://tracker.example.com/click?campaign=123&sub1={clickid}&#10;(Any tracker format — Binom, Voluum, Keitaro, etc.)"
                            style="font-size:12px;font-family:monospace;resize:vertical" required></textarea>
                        <div style="font-size:11px;color:#94A3B8;margin-top:4px">
                            Paste the affiliate's tracker link in any format. We don't parse it — we just record it and fire their postback URL.
                        </div>
                    </div>

                    <!-- Sub1 / Sub2 overrides -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group mb-0">
                            <label style="font-weight:700;font-size:13px">Sub1 (optional)</label>
                            <input type="text" id="pt-sub1" name="sub1" class="form-control"
                                   placeholder="e.g. campaign_id" style="font-size:12px">
                        </div>
                        <div class="form-group mb-0">
                            <label style="font-weight:700;font-size:13px">Sub2 (optional)</label>
                            <input type="text" id="pt-sub2" name="sub2" class="form-control"
                                   placeholder="e.g. creative_id" style="font-size:12px">
                        </div>
                    </div>

                    <!-- Payout override -->
                    <div class="form-group" style="margin-top:14px">
                        <label style="font-weight:700;font-size:13px">Payout Override ($)</label>
                        <input type="number" id="pt-payout" name="payout" class="form-control"
                               min="0" step="0.01" placeholder="Leave blank — uses offer default"
                               style="max-width:200px">
                    </div>

                    <button type="submit" class="btn btn-primary" id="pt-btn"
                            style="width:100%;justify-content:center;gap:8px;font-size:14px;padding:12px;margin-top:4px">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span id="pt-btn-label">▶ Run Conversion Test</span>
                        <span class="pt-spinner" id="pt-spinner"></span>
                    </button>

                    <div style="margin-top:10px;font-size:11px;color:#94A3B8;text-align:center">
                        ⚡ This creates a real click + conversion and credits the affiliate's balance.
                    </div>
                </form>

            </div>
        </div>

        <!-- Recent Tests -->
        <div class="card" style="margin-top:16px">
            <div class="card-header">
                <span class="card-title">Recent Tests</span>
                <button onclick="loadLogs()" class="btn btn-secondary btn-sm">↻ Refresh</button>
            </div>
            <div class="table-wrap" id="pt-log-wrap">
                <?php if (empty($recentLogs)): ?>
                <div style="text-align:center;padding:20px;color:#94A3B8;font-size:13px">No tests run yet</div>
                <?php else: ?>
                <table style="font-size:12px">
                    <thead><tr><th></th><th>Affiliate</th><th>Offer</th><th>Click ID</th><th>Payout</th><th>HTTP</th><th>When</th></tr></thead>
                    <tbody>
                    <?php foreach($recentLogs as $log): ?>
                    <tr>
                        <td><span class="pt-log-dot <?= $log['is_success'] ? 'ok' : 'err' ?>"></span></td>
                        <td class="fw-bold"><?= Helpers::e($log['affiliate_code']) ?></td>
                        <td class="text-muted">#<?= $log['offer_id'] ?></td>
                        <td style="font-family:monospace"><?= substr($log['click_id'],0,8) ?>…</td>
                        <td>$<?= number_format($log['payout'],2) ?></td>
                        <?php
                            $httpStatus = (int)$log['http_status'];
                            $isSuccess = (bool)$log['is_success'];
                            if (!$isSuccess) {
                                $badgeCls = 'danger';
                                $badgeText = $log['http_status'] ?: 'Error';
                            } elseif ($httpStatus >= 200 && $httpStatus < 300) {
                                $badgeCls = 'success';
                                $badgeText = $httpStatus;
                            } else {
                                $badgeCls = 'warning';
                                $badgeText = $httpStatus . '*';
                            }
                        ?>
                        <td><span class="badge badge-<?= $badgeCls ?>" title="<?= $isSuccess ? ($httpStatus >= 200 && $httpStatus < 300 ? 'Success' : 'Expected non-2xx in test mode') : 'Connection error or self-loop' ?>"><?= $badgeText ?></span></td>
                        <td class="text-muted" style="white-space:nowrap"><?= date('M j H:i', strtotime($log['tested_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <div style="font-size:10px;color:#94A3B8;padding:6px 16px 10px;border-top:1px solid #F1F5F9">
                <strong>*</strong> Non-2xx HTTP (e.g. 404) is <em>expected</em> in test mode — the external tracker has no record of the synthetic click_id. Green dot = URL reachable ✓
            </div>
        </div>
    </div>

    <!-- ══ RIGHT: Results ══ -->
    <div id="pt-results">
        <div class="card" style="border:2px dashed #E2E8F0">
            <div class="pt-empty">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
                <div style="font-size:15px;font-weight:600;color:#CBD5E1;margin-bottom:6px">No test run yet</div>
                <div style="font-size:13px">Select affiliate, offer, paste tracking link and click Run.</div>
            </div>
        </div>
    </div>

</div>

<script>
(function(){
var CSRF = '<?= Auth::generateCsrf() ?>';
var offerPayouts = {};

// ── Load offers when affiliate changes ───────────────────────────────────
document.getElementById('pt-affiliate').addEventListener('change', function(){
    var affId = this.value;
    var offerSel = document.getElementById('pt-offer');
    var pbPrev   = document.getElementById('pt-pb-preview');
    offerSel.innerHTML = '<option value="">Loading…</option>';
    offerSel.disabled = true;
    pbPrev.style.display = 'none';
    document.getElementById('pt-payout-hint').style.display = 'none';

    if (!affId) {
        offerSel.innerHTML = '<option value="">— Select Affiliate First —</option>';
        return;
    }

    fetch('/admin/postback-test?action=get_offers&affiliate_id=' + affId)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var offers = d.offers || [];
            offerPayouts = {};
            if (!offers.length) {
                offerSel.innerHTML = '<option value="">No approved offers found</option>';
            } else {
                offerSel.innerHTML = '<option value="">— Select Offer —</option>';
                offers.forEach(function(o){
                    offerPayouts[o.id] = parseFloat(o.payout_amount);
                    offerSel.innerHTML += '<option value="'+o.id+'">'+esc(o.name)+' ($'+parseFloat(o.payout_amount).toFixed(2)+')</option>';
                });
                offerSel.disabled = false;
            }
        }).catch(function(){
            offerSel.innerHTML = '<option value="">Error loading offers</option>';
        });
});

// ── Load postback preview when offer changes ──────────────────────────────
document.getElementById('pt-offer').addEventListener('change', function(){
    var offerId  = this.value;
    var affId    = document.getElementById('pt-affiliate').value;
    var pbPrev   = document.getElementById('pt-pb-preview');
    var hint     = document.getElementById('pt-payout-hint');
    var payoutFld = document.getElementById('pt-payout');

    if (offerId && offerPayouts[offerId]) {
        document.getElementById('pt-payout-val').textContent = '$' + offerPayouts[offerId].toFixed(2);
        hint.style.display = 'block';
        if (!payoutFld.value) payoutFld.placeholder = 'Default: $' + offerPayouts[offerId].toFixed(2);
    } else {
        hint.style.display = 'none';
    }

    pbPrev.style.display = 'none';
    if (!offerId || !affId) return;

    fetch('/admin/postback-test?action=get_postbacks&affiliate_id='+affId+'&offer_id='+offerId)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var pbs = d.postbacks || [];
            pbPrev.style.display = 'block';
            if (!pbs.length) {
                pbPrev.innerHTML = '<div class="pb-preview-none">⚠ No active postback URL configured for this affiliate. Set one in <a href="/admin/postbacks" style="color:#92400E;font-weight:700">Manage Postbacks</a> before testing. The conversion will still be recorded.</div>';
            } else {
                var html = '<div class="pb-preview"><div style="font-size:11px;font-weight:700;color:#065F46;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">✓ '+pbs.length+' Postback URL(s) Ready</div>';
                pbs.forEach(function(pb){
                    html += '<div style="font-family:monospace;font-size:11px;color:#334155;background:#fff;border:1px solid #D1FAE5;border-radius:5px;padding:6px 8px;margin-bottom:6px;word-break:break-all">'+esc(pb.url)+'</div>';
                });
                html += '</div>';
                pbPrev.innerHTML = html;
            }
        }).catch(function(){
            pbPrev.style.display = 'none';
        });
});

// ── Form submit ───────────────────────────────────────────────────────────
document.getElementById('pt-form').addEventListener('submit', function(e){
    e.preventDefault();

    var affId    = document.getElementById('pt-affiliate').value;
    var offerId  = document.getElementById('pt-offer').value;
    var trackUrl = document.getElementById('pt-url').value.trim();

    if (!affId)    { alert('Please select an affiliate.'); return; }
    if (!offerId)  { alert('Please select an offer.'); return; }
    if (!trackUrl) { alert('Please paste the affiliate tracking link.'); return; }

    if (!confirm('This will create a real click record, record an approved conversion, and fire the postback to the affiliate\'s tracker URL.\n\nContinue?')) return;

    var btn     = document.getElementById('pt-btn');
    var spinner = document.getElementById('pt-spinner');
    var results = document.getElementById('pt-results');
    btn.disabled = true;
    spinner.style.display = 'inline-block';
    document.getElementById('pt-btn-label').textContent = 'Running…';
    results.innerHTML = '<div class="card"><div class="pt-empty" style="padding:40px"><div style="font-size:14px;color:#94A3B8">Processing test conversion…</div></div></div>';

    var fd = new FormData(this);
    fd.set('_token', CSRF);

    fetch('/admin/postback-test?action=run_test', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.disabled = false;
            spinner.style.display = 'none';
            document.getElementById('pt-btn-label').textContent = '▶ Run Conversion Test';
            results.innerHTML = buildResults(d);
            loadLogs();
        })
        .catch(function(err){
            btn.disabled = false;
            spinner.style.display = 'none';
            document.getElementById('pt-btn-label').textContent = '▶ Run Conversion Test';
            results.innerHTML = errCard('Network error: ' + err);
        });
});

// ── Build results panel ───────────────────────────────────────────────────
function buildResults(d) {
    if (d.error) return errCard(d.error);

    var allStepsOk = (d.steps || []).every(function(s){ return s.status !== 'err'; });
    var anyWarn    = (d.steps || []).some(function(s){ return s.status === 'warn'; });
    var sumCls     = allStepsOk ? (anyWarn ? 'warn' : 'ok') : 'err';

    var html = '';

    // Summary bar
    html += '<div class="pt-summary '+sumCls+'">';
    html += '<div style="flex-shrink:0;font-size:22px">'+(allStepsOk ? (anyWarn ? '⚠️' : '✅') : '❌')+'</div>';
    html += '<div style="flex:1">';
    html += '<div style="font-weight:700;font-size:14px;margin-bottom:3px">'+esc(d.affiliate)+' — '+esc(d.offer)+'</div>';
    html += '<div style="font-size:12px;color:#475569">Payout: <strong>$'+parseFloat(d.payout).toFixed(2)+'</strong> · Real conversion recorded</div>';
    html += '</div></div>';

    // IDs box
    html += '<div class="pt-id-box">';
    html += idRow('Click ID',       d.click_id);
    html += idRow('Conversion ID',  d.conv_id);
    html += idRow('Affiliate',      d.affiliate + ' (' + d.affiliate_code + ')');
    html += idRow('Offer ID',       '#' + d.offer_id);
    html += idRow('Payout',         '$' + parseFloat(d.payout).toFixed(2));
    if (d.sub1) html += idRow('Sub1', d.sub1);
    html += '</div>';

    // Pipeline steps
    html += '<div class="card" style="margin-bottom:14px">';
    html += '<div class="card-header"><span class="card-title">Pipeline Steps</span></div>';
    html += '<div class="card-body" style="padding:8px 16px">';
    html += '<div class="pt-steps">';
    (d.steps || []).forEach(function(s){
        var icon = s.status === 'ok' ? '✓' : (s.status === 'warn' ? '!' : '✗');
        html += '<div class="pt-step">';
        html += '<div class="pt-step-num '+s.status+'">'+icon+'</div>';
        html += '<div><div class="pt-step-label">'+esc(s.label)+'</div>';
        html += '<div class="pt-step-detail">'+esc(s.detail)+'</div></div>';
        html += '</div>';
    });
    html += '</div></div></div>';

    // Postback results
    if (d.no_postbacks) {
        html += '<div class="card"><div class="card-body">';
        html += '<div class="alert alert-warning" style="margin-bottom:0"><strong>No Postback URL Configured</strong><br>';
        html += 'The conversion was recorded and the affiliate\'s balance was credited, but no tracker postback URL was fired.<br>';
        html += 'To enable postback firing, configure the postback URL in <a href="/admin/postbacks">Manage Postbacks</a>.</div>';
        html += '</div></div>';
        return html;
    }

    if (!d.postback_results || !d.postback_results.length) return html;

    html += '<div class="card">';
    html += '<div class="card-header"><span class="card-title">Postback Fire Results</span></div>';
    html += '<div class="card-body" style="padding:12px 16px">';
    // Check if any result is a self-postback (pointing back to own domain)
    var hasSelf = d.postback_results && d.postback_results.some(function(r){ return r.is_self; });
    if (hasSelf) {
        html += '<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:12px;color:#991B1B">';
        html += '<strong>⚠️ Self-Postback Loop Detected</strong><br><br>';
        html += 'The affiliate\'s postback URL points back to <strong>your own system</strong> (<code>/postback</code>). ';
        html += 'This is the <strong>Advertiser Postback URL</strong> — it is meant for advertisers to send conversions <em>to you</em>. ';
        html += 'It should <strong>not</strong> be used as the affiliate\'s outgoing postback URL.<br><br>';
        html += '<strong>How to fix:</strong><br>';
        html += '1. The affiliate must set their postback URL to <strong>their tracker\'s postback endpoint</strong> (e.g. AffsCash, Binom, Voluum, Keitaro).<br>';
        html += '2. Go to <a href="/admin/postbacks" style="color:#991B1B;font-weight:700">Manage Postbacks</a> and delete the incorrect URL.<br>';
        html += '3. Ask the affiliate for the correct postback URL from their tracker dashboard and add it there.<br><br>';
        html += '<strong>Correct setup:</strong><br>';
        html += '• <strong>Advertiser → Your system:</strong> <code>/postback?click_id={click_id}&payout={payout}</code><br>';
        html += '• <strong>Your system → Affiliate tracker:</strong> <code>https://their-tracker.com/postback?cid={click_id}&payout={payout}</code>';
        html += '</div>';
    } else {
        html += '<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#92400E">';
        html += '<strong>ℹ️ Note about non-2xx responses in test mode:</strong> If the external tracker returns 404 / "Invalid Click ID", ';
        html += 'this is expected — the synthetic test click_id was not generated by the affiliate\'s tracker, so they have no record of it. ';
        html += 'In real traffic the click_id comes from the affiliate\'s tracker and will match. ';
        html += 'The postback URL and macro replacement shown above are what matters — verify those look correct.';
        html += '</div>';
    }

    d.postback_results.forEach(function(r){
        // r.success = true when URL was reachable (any HTTP > 0, not a self-loop)
        // r.real_http_ok = true only for 2xx responses
        var cls   = r.is_self ? 'err' : (r.success ? 'ok' : 'err');
        var badge;
        if (r.is_self) {
            badge = '<span class="pt-badge err">⚠️ SELF-LOOP</span>';
        } else if (!r.success) {
            badge = '<span class="pt-badge err">❌ Network Error</span>';
        } else if (r.real_http_ok) {
            badge = '<span class="pt-badge ok">✅ HTTP '+r.http_status+'</span>';
        } else {
            // Non-2xx from external tracker — expected in test mode
            badge = '<span class="pt-badge" style="background:#FEF3C7;color:#92400E">⚠ HTTP '+r.http_status+' (expected in test)</span>';
        }
        html += '<div class="pt-result-card '+cls+'">';
        html += '<div class="pt-result-head '+cls+'">';
        html += '<div style="font-size:13px;font-weight:700">Postback #'+r.postback_id+' <span style="font-size:11px;font-weight:400;color:#64748B">('+esc(r.method)+')</span></div>';
        html += '<div style="display:flex;gap:6px;align-items:center">'+badge+'<span style="font-size:11px;color:#94A3B8">'+r.time_ms+'ms</span></div>';
        html += '</div>';
        html += '<div class="pt-result-body">';
        html += '<div style="font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px">Postback Template URL</div>';
        html += '<div class="pt-url-box" style="margin-bottom:10px">'+esc(r.template_url)+'</div>';
        html += '<div style="font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px">Fired URL (macros replaced)</div>';
        html += '<div class="pt-url-box '+cls+'" style="margin-bottom:10px">'+highlightIds(r.fired_url)+'</div>';
        html += '<div style="font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px">Server Response</div>';
        if (r.response && r.response.trim()) {
            html += '<div class="pt-resp-box">'+esc(r.response)+'</div>';
        } else {
            html += '<div style="font-size:12px;color:#94A3B8;font-style:italic">(empty response)</div>';
        }
        html += '</div></div>';
    });

    html += '</div></div>';
    return html;
}

function idRow(label, val) {
    return '<div class="pt-id-row"><span class="pt-id-label">'+esc(label)+'</span><span class="pt-id-val">'+esc(val)+'</span><button class="pt-copy-btn" onclick="doCopy(\''+escAttr(val)+'\',this)">Copy</button></div>';
}

function highlightIds(url) {
    if (!url) return '';
    return esc(url).replace(/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/gi,'<strong style="color:#A78BFA">$1</strong>');
}

function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
function escAttr(s){ return (s||'').replace(/'/g,"\\'"); }
function errCard(msg){ return '<div class="card"><div class="card-body"><div class="alert alert-danger"><strong>Error:</strong> '+esc(msg)+'</div></div></div>'; }

window.doCopy = function(text, btn) {
    navigator.clipboard.writeText(text).then(function(){
        var orig = btn.textContent; btn.textContent = '✓';
        setTimeout(function(){ btn.textContent = orig; }, 1800);
    }).catch(function(){
        var ta = document.createElement('textarea'); ta.value=text;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        var orig = btn.textContent; btn.textContent = '✓';
        setTimeout(function(){ btn.textContent = orig; }, 1800);
    });
};

window.loadLogs = function() {
    var wrap = document.getElementById('pt-log-wrap');
    fetch('/admin/postback-test?action=get_logs')
        .then(function(r){ return r.json(); })
        .then(function(d){
            var logs = d.logs || [];
            if (!logs.length) { wrap.innerHTML='<div style="text-align:center;padding:20px;color:#94A3B8;font-size:13px">No tests run yet</div>'; return; }
            var rows = logs.map(function(l){
                var dot = '<span class="pt-log-dot '+(l.is_success?'ok':'err')+'"></span>';
                var httpOk = l.is_success && l.http_status >= 200 && l.http_status < 300;
                var badgeCls = !l.is_success ? 'danger' : (httpOk ? 'success' : 'warning');
                var badgeTxt = !l.http_status ? 'Error' : (httpOk ? l.http_status : l.http_status+'*');
                var badgeTip = !l.is_success ? 'Connection error or self-loop' : (httpOk ? 'Success' : 'Expected non-2xx in test mode');
                var badge = '<span class="badge badge-'+badgeCls+'" title="'+badgeTip+'">'+(badgeTxt)+'</span>';
                var when  = new Date(l.tested_at.replace(' ','T'));
                var ws    = when.toLocaleDateString('en',{month:'short',day:'numeric'})+' '+when.toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'});
                return '<tr><td>'+dot+'</td><td class="fw-bold">'+esc(l.affiliate_code)+'</td><td class="text-muted">#'+l.offer_id+'</td><td style="font-family:monospace">'+l.click_id.substr(0,8)+'…</td><td>$'+parseFloat(l.payout).toFixed(2)+'</td><td>'+badge+'</td><td class="text-muted" style="white-space:nowrap">'+esc(ws)+'</td></tr>';
            }).join('');
            wrap.innerHTML = '<table style="font-size:12px"><thead><tr><th></th><th>Affiliate</th><th>Offer</th><th>Click ID</th><th>Payout</th><th>HTTP</th><th>When</th></tr></thead><tbody>'+rows+'</tbody></table>';
        }).catch(function(){});
};

})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
