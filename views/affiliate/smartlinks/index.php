<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<div class="page-header">
    <div><h1>Smartlinks</h1><p>Request access to rotating offer links — traffic is automatically distributed across the best offers</p></div>
</div>

<?php if(empty($smartlinks)): ?>
<div class="empty-state"><div class="icon">&#128279;</div><h3>No smartlinks available</h3><p>The admin will create smartlinks for you to use.</p></div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:20px">
<?php foreach($smartlinks as $sl):
    $req     = $requestMap[$sl['id']] ?? null;
    $reqStatus = $req['status'] ?? null;
    $approved  = $reqStatus === 'approved';
    $smartUrl  = $approved ? ($appUrl.'/smartlink/'.$sl['slug'].'?aff='.$aff['affiliate_code'].'&sub1=') : '';
?>
<div class="card smartlink-card" style="border-radius:16px;box-shadow:0 4px 20px -2px rgba(0,0,0,0.06);border:1px solid <?= $approved ? '#A7F3D0' : '#E2E8F0' ?>;transition:all 0.25s ease;overflow:hidden">
    <div class="card-body" style="padding:22px">
        <div class="d-flex justify-between align-center mb-2" style="flex-wrap:wrap;gap:8px">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:11px;color:#64748B;font-family:monospace;font-weight:700;background:#F1F5F9;padding:2px 8px;border-radius:6px">#<?= $sl['id'] ?></span>
                <h3 style="font-size:16px;font-weight:800;color:var(--text);margin:0"><?= Helpers::e($sl['name']) ?></h3>
            </div>
            <span class="badge badge-info" style="border-radius:20px;padding:4px 12px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#DBEAFE,#EFF6FF);color:#1D4ED8;border:1px solid #BFDBFE"><?= $sl['rotation_type'] ?></span>
        </div>

        <?php if($sl['description']): ?>
        <div class="text-sm text-muted mb-3" style="line-height:1.5;font-size:13px">
            <span id="sl-desc-short-<?= $sl['id'] ?>"><?= Helpers::e(substr($sl['description'], 0, 120)) ?><?= mb_strlen($sl['description']) > 120 ? '...' : '' ?></span>
            <button type="button" onclick="showOfferDetailsModal(<?= $sl['id'] ?>)" style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:8px;color:#4F46E5;background:#EEF2FF;border:1px solid #C7D2FE;cursor:pointer;margin-left:6px;transition:all .15s" title="View details">📄 Details</button>
        </div>
        <?php endif; ?>

        <div style="display:inline-flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:700;color:#475569;margin-bottom:14px">
            <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block"></span>
            <strong><?= $sl['offer_count'] ?></strong> active offers in rotation
        </div>

        <?php if (isset($slPayoutMap[$sl['id']])): $slp = $slPayoutMap[$sl['id']]; ?>
        <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#F0FDF4,#ECFDF5);border:1px solid #A7F3D0;border-radius:12px;padding:10px 16px;margin-bottom:14px">
            <div>
                <div style="font-size:10px;font-weight:800;color:#047857;letter-spacing:.05em;text-transform:uppercase">YOUR PAYOUT</div>
                <div style="font-size:20px;font-weight:800;color:#059669">$<?= number_format((float)$slp['payout'],2) ?></div>
            </div>
            <?php if ((float)$slp['revenue'] > 0): ?>
            <div style="border-left:1px solid #6EE7B7;padding-left:14px">
                <div style="font-size:10px;font-weight:800;color:#64748B;letter-spacing:.05em;text-transform:uppercase">REVENUE</div>
                <div style="font-size:17px;font-weight:700;color:#334155">$<?= number_format((float)$slp['revenue'],2) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Access Status -->
        <?php if ($approved): ?>
        <div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #A7F3D0;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;color:#065F46;font-weight:700;display:flex;align-items:center;gap:6px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Access Granted — you can use this smartlink</span>
        </div>
        <div class="copy-group" style="gap:8px">
            <input type="text" id="sl-<?= $sl['id'] ?>" class="form-control" value="<?= Helpers::e($smartUrl) ?>" readonly style="font-size:12px;border-radius:8px;background:#F8FAFC">
            <button class="btn btn-secondary btn-sm" data-copy="sl-<?= $sl['id'] ?>" style="border-radius:8px;font-weight:700">Copy</button>
            <button class="btn btn-sm" onclick="openSlGenModal(<?= $sl['id'] ?>, document.getElementById('sl-<?= $sl['id'] ?>').value)" style="background:#0EA5E9;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;padding:0 12px;white-space:nowrap" title="Build link with tracking parameters">🔗 Build</button>
            <?php if (!empty($shortenerEnabled)): ?>
            <button id="sl-short-btn-<?= $sl['id'] ?>" class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;padding:0 12px" onclick="shortenSmartlink(<?= $sl['id'] ?>, document.getElementById('sl-<?= $sl['id'] ?>').value)">✂ Short</button>
            <?php endif; ?>
        </div>
        <div class="form-hint mt-1" style="font-size:11.5px">Replace <code>sub1=</code> with your sub-parameter value</div>

        <?php elseif ($reqStatus === 'pending'): ?>
        <div style="background:linear-gradient(135deg,#FEF9C3,#FEF08A);border:1px solid #FDE047;border-radius:10px;padding:12px 14px;font-size:12.5px;color:#713F12;font-weight:600">
            ⏳ <strong>Request Pending</strong> — your access request is under review. You'll be notified once approved.
        </div>

        <?php elseif ($reqStatus === 'rejected'): ?>
        <div style="background:linear-gradient(135deg,#FEE2E2,#FECACA);border:1px solid #FCA5A5;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:12.5px;color:#7F1D1D;font-weight:600">
            ✕ <strong>Request Rejected</strong><?= $req['admin_note'] ? ' — ' . Helpers::e($req['admin_note']) : '' ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="openRequestModal(<?= $sl['id'] ?>, '<?= htmlspecialchars(addslashes($sl['name'])) ?>')" style="border-radius:8px;font-weight:700">↺ Re-apply for Access</button>

        <?php else:
            $autoApprove = empty($sl['require_approval']);
        ?>
        <div style="background:linear-gradient(135deg,#FAF5FF 0%,#F3E8FF 100%);border:1px solid #E9D5FF;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:12.5px;color:#6B21A8;font-weight:600">
            <?= $autoApprove ? '⚡ Instant Access — click below to get your link immediately' : '🔒 Access Required — send a request to use this smartlink' ?>
        </div>
        <button type="button" class="btn btn-primary" onclick="openRequestModal(<?= $sl['id'] ?>, '<?= htmlspecialchars(addslashes($sl['name'])) ?>')" style="width:100%;border-radius:10px;padding:11px 18px;font-size:14px;font-weight:700;background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 100%);box-shadow:0 4px 14px rgba(124,58,237,0.35);justify-content:center">
            <?= $autoApprove ? '⚡ Get Instant Access' : '⏳ Request Access' ?>
        </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── BUILD TRACKING LINK MODAL (Smartlink) ──────────────────────────── -->
<div id="slGenLinkModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:14px;padding:28px 28px 24px;max-width:580px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.25);max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div>
                <h3 style="margin:0;font-size:17px;font-weight:700">&#128279; Build Smartlink Tracking Link</h3>
                <p style="margin:4px 0 0;font-size:12px;color:#64748B">Add click_id, source &amp; sub parameters to your smartlink URL</p>
            </div>
            <button onclick="closeSlGenModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94A3B8;line-height:1">&times;</button>
        </div>

        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">BASE SMARTLINK URL</label>
            <input type="text" id="sl-gen-base-url" class="form-control" readonly style="font-size:11px;background:#F8FAFC;color:#475569">
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px">
            <div style="border:1px solid #C7D2FE;border-radius:8px;padding:11px;background:#F5F3FF">
                <label style="font-size:11px;font-weight:700;color:#4338CA;letter-spacing:.5px;display:block;margin-bottom:4px">
                    CLICK ID <span style="background:#4F46E5;color:#fff;border-radius:3px;padding:1px 5px;font-size:9px;font-weight:700;margin-left:3px">REQUIRED</span>
                </label>
                <input type="text" id="sl-gen-clickid" class="form-control" placeholder="e.g. {clickid} or ##CLICKID##" oninput="slRebuildGenUrl()" style="font-size:12px;border-color:#A5B4FC">
                <div style="font-size:10px;color:#6366F1;margin-top:4px">Your tracker's click ID token → appended as <code>click_id=</code></div>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">
                    SOURCE <span style="color:#94A3B8;font-weight:400">(traffic source)</span>
                </label>
                <input type="text" id="sl-gen-source" class="form-control" placeholder="e.g. facebook, push, native" oninput="slRebuildGenUrl()" style="font-size:12px">
                <div style="font-size:10px;color:#94A3B8;margin-top:3px">Appended as <code>&amp;source=</code></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:12px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_1 <span style="color:#94A3B8;font-weight:400">→ stored in sub2</span></label>
                <input type="text" id="sl-gen-sub1" class="form-control" placeholder="e.g. {affid}" oninput="slRebuildGenUrl()" style="font-size:12px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_2 <span style="color:#94A3B8;font-weight:400">→ stored in sub3</span></label>
                <input type="text" id="sl-gen-sub2" class="form-control" placeholder="e.g. {creative}" oninput="slRebuildGenUrl()" style="font-size:12px">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:12px">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_3</label>
                <input type="text" id="sl-gen-sub3" class="form-control" placeholder="e.g. {keyword}" oninput="slRebuildGenUrl()" style="font-size:12px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_4</label>
                <input type="text" id="sl-gen-sub4" class="form-control" placeholder="e.g. {placement}" oninput="slRebuildGenUrl()" style="font-size:12px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">SUB_ID_5</label>
                <input type="text" id="sl-gen-sub5" class="form-control" placeholder="e.g. {zone}" oninput="slRebuildGenUrl()" style="font-size:12px">
            </div>
        </div>

        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9989; GENERATED TRACKING LINK</label>
            <div style="display:flex;gap:6px;align-items:center">
                <input type="text" id="sl-gen-output" class="form-control" readonly style="font-size:11px;background:#F0FDF4;border-color:#86EFAC;font-family:monospace">
                <button class="btn btn-secondary btn-sm" onclick="slCopyGenOutput()" id="sl-gen-copy-btn" style="white-space:nowrap">Copy</button>
            </div>
        </div>

        <?php if (!empty($shortenerEnabled)): ?>
        <div style="margin-bottom:16px">
            <label style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;display:block;margin-bottom:4px">&#9986; SHORT LINK</label>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                <input type="text" id="sl-gen-short-output" class="form-control" readonly placeholder="Click 'Shorten' to shorten the generated URL..." style="font-size:11px;background:#F5F3FF;border-color:#C4B5FD;font-family:monospace">
                <button class="btn btn-sm" id="sl-gen-shorten-btn" onclick="slGenModalShorten()" style="white-space:nowrap;background:#7C3AED;border-color:#7C3AED;color:#fff">&#9986; Shorten</button>
                <a id="sl-gen-short-open" href="#" target="_blank" style="display:none;background:#5B21B6;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;text-decoration:none">&#8599;</a>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-bottom:20px">
            <div style="font-size:11px;font-weight:700;color:#64748B;letter-spacing:.5px;margin-bottom:8px">QUICK PRESETS — click to fill click ID field with your tracker's macro</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <button class="btn btn-sm" onclick="slApplyPreset('{clickid}')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{clickid}</button>
                <button class="btn btn-sm" onclick="slApplyPreset('{click_id}')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">{click_id}</button>
                <button class="btn btn-sm" onclick="slApplyPreset('[clickid]')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">[clickid]</button>
                <button class="btn btn-sm" onclick="slApplyPreset('##CLICKID##')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">##CLICKID##</button>
                <button class="btn btn-sm" onclick="slApplyPreset('%7Bclickid%7D')" style="font-size:11px;background:#F1F5F9;border:1px solid #CBD5E1;color:#475569">%7Bclickid%7D</button>
            </div>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #E2E8F0;padding-top:16px">
            <button class="btn btn-secondary" onclick="slResetGenModal()">&#8635; Reset</button>
            <button class="btn btn-primary" onclick="slApplyGenToSmartlink()" style="background:#0EA5E9;border-color:#0EA5E9">Apply to Smartlink</button>
            <button class="btn btn-secondary" onclick="closeSlGenModal()">Close</button>
        </div>
    </div>
</div>

<!-- Request Access Modal -->
<div id="req-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:440px;width:90%">
        <h3 style="margin-bottom:6px">Request Smartlink Access</h3>
        <p id="req-modal-desc" style="color:#64748B;font-size:13px;margin-bottom:20px"></p>
        <div class="form-group">
            <label>Message (optional)</label>
            <textarea id="req-note" class="form-control" rows="3" placeholder="Tell us how you plan to use this smartlink…"></textarea>
        </div>
        <div style="display:flex;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-primary" id="req-submit-btn" onclick="submitRequest()">Send Request</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('req-modal').style.display='none'">Cancel</button>
        </div>
        <div id="req-msg" style="margin-top:12px;font-size:13px"></div>
    </div>
</div>

<script>
function toggleSlDesc(id) {
    var s = document.getElementById('sl-desc-short-' + id);
    var f = document.getElementById('sl-desc-full-'  + id);
    var t = document.getElementById('sl-desc-toggle-' + id);
    if (!f) return;
    var expanded = f.style.display !== 'none';
    f.style.display = expanded ? 'none' : 'inline';
    if (s) s.style.display = expanded ? 'inline' : 'none';
    if (t) t.textContent = expanded ? 'More' : 'Less';
}

var _currentSlId = null;

function openRequestModal(slId, slName) {
    _currentSlId = slId;
    document.getElementById('req-modal-desc').textContent = 'Requesting access to: "' + slName + '"';
    document.getElementById('req-note').value = '';
    document.getElementById('req-msg').textContent = '';
    document.getElementById('req-modal').style.display = 'flex';
}

function submitRequest() {
    if (!_currentSlId) return;
    var btn  = document.getElementById('req-submit-btn');
    var note = document.getElementById('req-note').value;
    btn.disabled = true; btn.textContent = 'Sending…';

    fetch('/affiliate/smartlinks?action=request_access', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_csrfToken)
            + '&smartlink_id=' + _currentSlId
            + '&note=' + encodeURIComponent(note)
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; btn.textContent = 'Send Request';
        if (d.ok) {
            var msg = d.auto_approved
                ? '<span style="color:#059669;font-weight:600">&#10003; Access granted! Reloading…</span>'
                : '<span style="color:#059669;font-weight:600">&#10003; Request sent! You\'ll be notified once reviewed.</span>';
            document.getElementById('req-msg').innerHTML = msg;
            setTimeout(() => { document.getElementById('req-modal').style.display='none'; location.reload(); }, 1800);
        } else {
            document.getElementById('req-msg').innerHTML = '<span style="color:#DC2626">&#9888; ' + (d.error || 'Failed to send request') + '</span>';
        }
    })
    .catch(() => {
        btn.disabled = false; btn.textContent = 'Send Request';
        document.getElementById('req-msg').innerHTML = '<span style="color:#DC2626">Network error. Please try again.</span>';
    });
}

// Copy buttons
document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var inp = document.getElementById(this.dataset.copy);
        if (!inp) return;
        inp.select(); document.execCommand('copy');
        var orig = this.textContent;
        this.textContent = '✓ Copied';
        setTimeout(() => this.textContent = orig, 1500);
    });
});

// ── Build Tracking Link modal handlers ──────────────────────────────────────
var _slGenSlId   = null;
var _slGenBaseUrl = '';

function openSlGenModal(slId, baseUrl) {
    _slGenSlId = slId;
    // Strip any previously appended params so base is always clean
    var clean = (baseUrl || '')
        .replace(/[&?]source=[^&]*/g, '')
        .replace(/[&?]sub_id_[1-9]=[^&]*/g, '')
        .replace(/[&?]click_id=[^&]*/g, '')
        .replace(/&&/g, '&').replace(/\?&/, '?').replace(/[?&]$/, '');
    _slGenBaseUrl = clean;
    slResetGenFields();
    slRebuildGenUrl();
    document.getElementById('slGenLinkModal').style.display = 'flex';
}
function closeSlGenModal() { document.getElementById('slGenLinkModal').style.display = 'none'; }
function slResetGenFields() {
    ['sl-gen-source','sl-gen-clickid','sl-gen-sub1','sl-gen-sub2','sl-gen-sub3','sl-gen-sub4','sl-gen-sub5'].forEach(function(id){
        var el = document.getElementById(id); if (el) el.value = '';
    });
    var so = document.getElementById('sl-gen-short-output'); if (so) so.value = '';
    var gso = document.getElementById('sl-gen-short-open');  if (gso) { gso.href='#'; gso.style.display='none'; }
}
function slResetGenModal() { slResetGenFields(); slRebuildGenUrl(); }
function slApplyPreset(macro) {
    var el = document.getElementById('sl-gen-clickid'); if (el) { el.value = macro; slRebuildGenUrl(); }
}
function slRebuildGenUrl() {
    var base = _slGenBaseUrl || '';
    var sep  = base.indexOf('?') === -1 ? '?' : '&';
    var parts = [];
    var v = function(id){ var e=document.getElementById(id); return e ? e.value.trim() : ''; };
    var cid  = v('sl-gen-clickid');
    var src  = v('sl-gen-source');
    var s1   = v('sl-gen-sub1'), s2 = v('sl-gen-sub2'), s3 = v('sl-gen-sub3'), s4 = v('sl-gen-sub4'), s5 = v('sl-gen-sub5');
    if (cid) parts.push('click_id=' + cid);
    if (src) parts.push('source='   + encodeURIComponent(src));
    if (s1)  parts.push('sub_id_1=' + encodeURIComponent(s1));
    if (s2)  parts.push('sub_id_2=' + encodeURIComponent(s2));
    if (s3)  parts.push('sub_id_3=' + encodeURIComponent(s3));
    if (s4)  parts.push('sub_id_4=' + encodeURIComponent(s4));
    if (s5)  parts.push('sub_id_5=' + encodeURIComponent(s5));
    var finalUrl = parts.length > 0 ? base + sep + parts.join('&') : base;
    var bu = document.getElementById('sl-gen-base-url'); if (bu) bu.value = base;
    var go = document.getElementById('sl-gen-output');   if (go) go.value = finalUrl;
    var so = document.getElementById('sl-gen-short-output'); if (so) so.value = '';
    var gso= document.getElementById('sl-gen-short-open');   if (gso) { gso.href='#'; gso.style.display='none'; }
}
function slCopyGenOutput() {
    var el = document.getElementById('sl-gen-output');
    var btn = document.getElementById('sl-gen-copy-btn');
    if (!el) return;
    el.select(); el.setSelectionRange(0, 99999);
    try { document.execCommand('copy'); } catch(e) { navigator.clipboard.writeText(el.value); }
    btn.textContent = '✓ Copied!';
    setTimeout(function(){ btn.textContent = 'Copy'; }, 1800);
}
function slApplyGenToSmartlink() {
    if (!_slGenSlId) return;
    var url = document.getElementById('sl-gen-output').value;
    var input = document.getElementById('sl-' + _slGenSlId);
    if (input && url) input.value = url;
    closeSlGenModal();
}
function slGenModalShorten() {
    var url = document.getElementById('sl-gen-output').value;
    if (!url) { alert('Please build a tracking link first.'); return; }
    var btn = document.getElementById('sl-gen-shorten-btn');
    var si  = document.getElementById('sl-gen-short-output');
    var gso = document.getElementById('sl-gen-short-open');
    btn.textContent = '...'; btn.disabled = true;
    var fd = new FormData(); fd.append('url', url); fd.append('_token', _csrfToken);
    fetch('/affiliate/shorten', { method:'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            btn.innerHTML = '&#9986; Shorten'; btn.disabled = false;
            if (d.error) { alert('Shortener error: ' + d.error); return; }
            si.value = d.short_url;
            if (gso) { gso.href = d.short_url; gso.style.display = 'inline'; }
        })
        .catch(function(){
            btn.innerHTML = '&#9986; Shorten'; btn.disabled = false;
            alert('Failed to shorten link. Please try again.');
        });
}
// Close modal on backdrop click
document.getElementById('slGenLinkModal') && document.getElementById('slGenLinkModal').addEventListener('click', function(e){
    if (e.target === this) closeSlGenModal();
});

function shortenSmartlink(id, url) {
    var btn = document.getElementById('sl-short-btn-' + id);
    btn.disabled = true; btn.textContent = '...';
    fetch('/affiliate/shorten', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_csrfToken) + '&url=' + encodeURIComponent(url)
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; btn.textContent = '✂ Short';
        if (d.short_url) {
            document.getElementById('sl-short-url-' + id).value = d.short_url;
            document.getElementById('sl-short-result-' + id).style.display = 'block';
            document.getElementById('sl-short-open-' + id).href = d.short_url;
        } else { alert(d.error || 'Failed to shorten'); }
    })
    .catch(() => { btn.disabled = false; btn.textContent = '✂ Short'; });
}
</script>

<?php
$jsOfferDetails = [];
foreach($smartlinks as $sl) {
    $jsOfferDetails[$sl['id']] = [
        'name' => $sl['name'],
        'desc' => $sl['description'] ?? '',
        'terms' => '' // smartlinks do not have terms in this schema
    ];
}
?>
<script>
var _offerDetails = <?= json_encode($jsOfferDetails) ?>;
function showOfferDetailsModal(id) {
    var data = _offerDetails[id];
    if(!data) return;
    document.getElementById('od-modal-title').textContent = data.name;
    document.getElementById('od-modal-desc').innerHTML = data.desc.replace(/\n/g, '<br>');
    var tEl = document.getElementById('od-modal-terms-wrap');
    if(data.terms) {
        document.getElementById('od-modal-terms').innerHTML = data.terms;
        tEl.style.display = 'block';
    } else {
        tEl.style.display = 'none';
    }
    document.getElementById('offer-details-modal').style.display = 'flex';
}
</script>

<!-- Offer Details Modal -->
<div id="offer-details-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;padding:24px;max-width:600px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 10px 25px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 id="od-modal-title" style="margin:0;font-size:18px;font-weight:700"></h3>
            <button type="button" onclick="document.getElementById('offer-details-modal').style.display='none'" style="background:none;border:none;font-size:24px;line-height:1;cursor:pointer;color:#94A3B8">&times;</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding-right:8px;font-size:13px;color:#334155;line-height:1.6">
            <div id="od-modal-desc" style="margin-bottom:20px;white-space:pre-wrap"></div>
            <div id="od-modal-terms-wrap" style="display:none;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;padding:12px">
                <div style="font-weight:700;color:#92400E;margin-bottom:8px">&#128221; Terms &amp; Conditions</div>
                <div id="od-modal-terms" style="color:#78350F;white-space:pre-line"></div>
            </div>
        </div>
        <div style="margin-top:20px;text-align:right">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('offer-details-modal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
