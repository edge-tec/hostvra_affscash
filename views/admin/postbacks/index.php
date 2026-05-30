<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
/* ── Type badge ── */
.gpb-type { display:inline-flex;align-items:center;gap:5px;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700; }
.gpb-type-advertiser { background:#EEF2FF;color:#3730A3; }
.gpb-type-affiliate  { background:#F0FDF4;color:#166534; }

/* ── Status pill ── */
.status-pill { display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700; }
.status-pill.active   { background:#D1FAE5;color:#065F46; }
.status-pill.inactive { background:#F1F5F9;color:#64748B; }

/* ── Table ── */
.pb-th { padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap; }
.pb-td { padding:12px 16px;border-bottom:1px solid #F1F5F9;font-size:13px;vertical-align:middle; }

/* ── Log table ── */
.log-th { padding:9px 12px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;background:#F8FAFC;border-bottom:2px solid #E2E8F0; }
.log-td { padding:8px 12px;border-bottom:1px solid #F1F5F9;font-size:12px; }

/* ── Add form card ── */
.aff-selector { display:none; }
.aff-selector.show { display:block; }
</style>

<div class="page-header">
    <div>
        <h1>Global Postbacks</h1>
        <p>Postbacks fired for conversions — configure per affiliate or across all advertisers.</p>
    </div>
    <?php if ($activeCount > 0): ?>
    <span style="background:#D1FAE5;color:#065F46;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:700">● <?= $activeCount ?> Active</span>
    <?php endif; ?>
</div>

<?php $flash2 = Helpers::getFlash(); if (!empty($flash2['success'])): ?>
<div class="alert alert-success mb-3">✓ <?= Helpers::e($flash2['success']) ?></div>
<?php elseif (!empty($flash2['error'])): ?>
<div class="alert alert-danger mb-3">⚠ <?= Helpers::e($flash2['error']) ?></div>
<?php endif; ?>

<!-- Advertiser Postback URL reference -->
<div class="card mb-3" style="border-left:4px solid #4F46E5">
    <div class="card-body" style="padding:14px 20px">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="flex:1;min-width:280px">
                <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">📡 Advertiser Postback URL</div>
                <div style="font-family:monospace;font-size:12px;background:#1E1B4B;color:#C7D2FE;padding:9px 14px;border-radius:6px;word-break:break-all">
                    <?= Helpers::e(Helpers::trackingUrl()) ?>/postback?<span style="color:#6EE7B7;font-weight:700">click_id={click_id}</span>&amp;<span style="color:#6EE7B7;font-weight:700">payout={payout}</span><span style="color:#FCD34D">&amp;goal={goal}&amp;txn_id={txn_id}</span>
                </div>
                <div style="font-size:11px;color:#94A3B8;margin-top:5px">Share with advertisers. <code style="color:#6EE7B7">click_id</code> &amp; <code style="color:#6EE7B7">payout</code> are required. <code style="color:#FCD34D">goal</code> &amp; <code style="color:#FCD34D">txn_id</code> are optional (shown in reports).</div>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="copyAdvertiserUrl()" id="copy-adv-btn">📋 Copy URL</button>
        </div>
    </div>
</div>

<div class="grid-2 mb-3">

    <!-- ══ Add Form ══ -->
    <div class="card">
        <div class="card-header"><span class="card-title">Add Global Postback</span></div>
        <div class="card-body">
            <form method="POST" id="add-pb-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="pb_action" value="add">

                <!-- Type selector -->
                <div class="form-group">
                    <label style="font-weight:700">Type <span style="color:#EF4444">*</span></label>
                    <div style="display:flex;gap:10px;margin-top:4px">
                        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;padding:10px 14px;border:2px solid #E2E8F0;border-radius:8px;flex:1;transition:.15s" id="type-lbl-advertiser">
                            <input type="radio" name="type" value="advertiser" checked onchange="onTypeChange(this)">
                            <div>
                                <div style="font-weight:700;font-size:13px;color:#3730A3">Advertiser</div>
                                <div style="font-size:11px;color:#64748B">Fires for ALL conversions, all affiliates</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;padding:10px 14px;border:2px solid #E2E8F0;border-radius:8px;flex:1;transition:.15s" id="type-lbl-affiliate">
                            <input type="radio" name="type" value="affiliate" onchange="onTypeChange(this)">
                            <div>
                                <div style="font-weight:700;font-size:13px;color:#166534">Affiliate</div>
                                <div style="font-size:11px;color:#64748B">Fires only for one affiliate's conversions</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Affiliate selector (shown only for affiliate type) -->
                <div class="form-group aff-selector" id="aff-selector-wrap">
                    <label style="font-weight:700">Affiliate <span style="color:#EF4444">*</span></label>
                    <select name="affiliate_id" class="form-control" id="affiliate_id_select">
                        <option value="">— Select Affiliate —</option>
                        <?php foreach ($affiliateList as $a): ?>
                        <option value="<?= (int)$a['id'] ?>">
                            <?= Helpers::e($a['label']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:11px;color:#94A3B8;margin-top:4px">
                        The postback URL will be saved directly to this affiliate's account.
                    </div>
                </div>

                <!-- Method -->
                <div class="form-group">
                    <label style="font-weight:700">Method</label>
                    <select name="method" class="form-control" style="max-width:110px">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </div>

                <!-- URL -->
                <div class="form-group">
                    <label style="font-weight:700">Postback URL <span style="color:#EF4444">*</span></label>
                    <input type="text" id="globalPbUrl" name="url" class="form-control" required
                           placeholder="https://tracker.com/postback?cid={click_id}&payout={payout}"
                           style="font-family:monospace;font-size:12px">
                    <!-- Macro quick-insert -->
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px">
                        <?php foreach (['{click_id}','{payout}','{offer_id}','{sub1}','{aff_id}','{internal_click_id}'] as $m): ?>
                        <span onclick="insertMacro('globalPbUrl','<?= $m ?>')"
                              style="display:inline-flex;align-items:center;background:#EEF2FF;border:1px solid #C7D2FE;color:#3730A3;border-radius:5px;padding:3px 9px;font-family:monospace;font-size:11px;font-weight:600;cursor:pointer;transition:.12s"
                              onmouseover="this.style.background='#4F46E5';this.style.color='#fff'"
                              onmouseout="this.style.background='#EEF2FF';this.style.color='#3730A3'"><?= $m ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:5px">
                        <strong>{click_id}</strong> — affiliate's tracker click ID &nbsp;·&nbsp;
                        <strong>{payout}</strong> — conversion payout in USD
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Postback</button>
            </form>
        </div>
    </div>

    <!-- ══ Recent Fires ══ -->
    <div class="card" style="overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:2px solid #F1F5F9">
            <span style="font-size:15px;font-weight:700;color:#4F46E5">🔥 Recent Postback Fires</span>
            <span style="font-size:12px;color:#94A3B8">last <?= count($logs) ?></span>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead><tr>
                    <th class="log-th">Type</th>
                    <th class="log-th">Conversion</th>
                    <th class="log-th">Fired URL</th>
                    <th class="log-th">HTTP</th>
                    <th class="log-th">OK?</th>
                    <th class="log-th">Time</th>
                </tr></thead>
                <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="padding:36px;text-align:center;color:#94A3B8">
                    <div style="font-size:28px;margin-bottom:8px">🔕</div>
                    <div style="font-weight:600;margin-bottom:3px">No postback fires yet</div>
                    <div style="font-size:11px">Fires appear here once active postbacks receive conversions.</div>
                </td></tr>
                <?php else: foreach($logs as $log):
                    $hs  = (int)($log['http_status'] ?? 0);
                    $ok  = (bool)$log['is_success'];
                    $hsBg = ($hs >= 200 && $hs < 300) ? '#D1FAE5' : '#FEE2E2';
                    $hsFg = ($hs >= 200 && $hs < 300) ? '#065F46' : '#991B1B';
                ?><tr style="background:<?= $ok ? '#fff' : '#FFF5F5' ?>">
                    <td class="log-td">
                        <span class="gpb-type gpb-type-<?= Helpers::e($log['pb_type'] ?? 'advertiser') ?>">
                            <?= ($log['pb_type'] ?? 'advertiser') === 'affiliate' ? 'Affiliate' : 'Advertiser' ?>
                        </span>
                    </td>
                    <td class="log-td" style="font-family:monospace;font-size:10px;color:#64748B;white-space:nowrap">
                        <?= substr(Helpers::e($log['conversion_id'] ?? ''), 0, 8) ?>…
                    </td>
                    <td class="log-td" style="max-width:200px">
                        <div style="font-family:monospace;font-size:10px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:190px;cursor:pointer"
                             title="<?= Helpers::e($log['fired_url'] ?? '') ?>"
                             onclick="showFullUrl(this.title)">
                            <?= Helpers::e($log['fired_url'] ?? '—') ?>
                        </div>
                    </td>
                    <td class="log-td" style="white-space:nowrap">
                        <span style="background:<?= $hsBg ?>;color:<?= $hsFg ?>;border-radius:6px;padding:2px 7px;font-size:11px;font-weight:700;font-family:monospace">
                            <?= $hs ?: '—' ?>
                        </span>
                    </td>
                    <td class="log-td" style="text-align:center">
                        <?= $ok ? '<span style="color:#10B981;font-size:15px">✓</span>' : '<span style="color:#EF4444;font-size:15px">✗</span>' ?>
                    </td>
                    <td class="log-td" style="white-space:nowrap;color:#64748B;font-size:11px">
                        <?= date('M j, H:i', strtotime($log['fired_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- end grid-2 -->

<!-- ══ All Global Postbacks table ══ -->
<div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:2px solid #F1F5F9">
        <span style="font-size:15px;font-weight:700;color:#0F172A">📋 All Global Postbacks</span>
        <span style="font-size:12px;color:#94A3B8"><?= count($postbacks) ?> total · only <strong>Active</strong> postbacks fire</span>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr>
                <th class="pb-th">Type</th>
                <th class="pb-th">Affiliate</th>
                <th class="pb-th">Method</th>
                <th class="pb-th">Postback URL</th>
                <th class="pb-th">Status</th>
                <th class="pb-th">Added</th>
                <th class="pb-th">Actions</th>
            </tr></thead>
            <tbody>
            <?php if (empty($postbacks)): ?>
            <tr><td colspan="7" style="padding:48px;text-align:center;color:#94A3B8">
                <div style="font-size:32px;margin-bottom:10px">📭</div>
                <div style="font-weight:600;font-size:14px;margin-bottom:4px">No global postbacks configured yet</div>
                <div style="font-size:12px">Add one using the form above.</div>
            </td></tr>
            <?php else: foreach($postbacks as $pb):
                $isActive = $pb['status'] === 'active';
            ?><tr style="background:<?= $isActive ? '#fff' : '#FAFAFA' ?>">
                <td class="pb-td">
                    <span class="gpb-type gpb-type-<?= Helpers::e($pb['type']) ?>">
                        <?= $pb['type'] === 'affiliate' ? '👤 Affiliate' : '🌐 Advertiser' ?>
                    </span>
                </td>
                <td class="pb-td">
                    <?php if ($pb['type'] === 'affiliate' && $pb['affiliate_id']): ?>
                    <div style="font-weight:700;color:#1E293B;font-size:12px"><?= Helpers::e($pb['aff_name'] ?? '—') ?></div>
                    <div style="font-size:10px;color:#94A3B8;font-family:monospace"><?= Helpers::e($pb['affiliate_code'] ?? '') ?></div>
                    <?php else: ?>
                    <span style="color:#94A3B8;font-size:12px">All affiliates</span>
                    <?php endif; ?>
                </td>
                <td class="pb-td">
                    <span style="background:#F1F5F9;color:#475569;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700;font-family:monospace">
                        <?= Helpers::e($pb['method']) ?>
                    </span>
                </td>
                <td class="pb-td" style="max-width:300px">
                    <div style="font-family:monospace;font-size:11px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px;cursor:pointer"
                         title="<?= Helpers::e($pb['url']) ?>"
                         onclick="showFullUrl('<?= addslashes(Helpers::e($pb['url'])) ?>')">
                        <?= Helpers::e($pb['url']) ?>
                    </div>
                    <button type="button" onclick="copyTextInline('<?= addslashes(Helpers::e($pb['url'])) ?>', this)"
                            style="background:none;border:none;color:#4F46E5;font-size:10px;cursor:pointer;padding:2px 0;font-weight:600">
                        📋 Copy
                    </button>
                </td>
                <td class="pb-td">
                    <span class="status-pill <?= $isActive ? 'active' : 'inactive' ?>">
                        <?= $isActive ? '● Active' : '○ Inactive' ?>
                    </span>
                </td>
                <td class="pb-td" style="font-size:11px;color:#94A3B8;white-space:nowrap">
                    <?= date('M j, Y', strtotime($pb['created_at'])) ?>
                </td>
                <td class="pb-td">
                    <div style="display:flex;gap:5px;flex-wrap:wrap">
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$pb['id'] ?>">
                            <button class="btn btn-secondary btn-sm">
                                <?= $isActive ? '⏸ Deactivate' : '▶ Activate' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Delete this postback permanently?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$pb['id'] ?>">
                            <button class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Full URL modal -->
<div id="url-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:20px 24px;max-width:700px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px">🔗 Full Postback URL</div>
        <div id="url-modal-text" style="font-family:monospace;font-size:11px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:10px 12px;word-break:break-all;color:#1E293B;max-height:200px;overflow-y:auto"></div>
        <div style="display:flex;gap:8px;margin-top:14px">
            <button onclick="navigator.clipboard.writeText(document.getElementById('url-modal-text').textContent)" class="btn btn-primary btn-sm">📋 Copy</button>
            <button onclick="document.getElementById('url-modal').style.display='none'" class="btn btn-secondary btn-sm">Close</button>
        </div>
    </div>
</div>

<script>
function onTypeChange(radio) {
    var wrap  = document.getElementById('aff-selector-wrap');
    var lblA  = document.getElementById('type-lbl-advertiser');
    var lblAf = document.getElementById('type-lbl-affiliate');
    if (radio.value === 'affiliate') {
        wrap.classList.add('show');
        document.getElementById('affiliate_id_select').required = true;
        lblAf.style.borderColor = '#4F46E5';
        lblA.style.borderColor  = '#E2E8F0';
    } else {
        wrap.classList.remove('show');
        document.getElementById('affiliate_id_select').required = false;
        lblA.style.borderColor  = '#4F46E5';
        lblAf.style.borderColor = '#E2E8F0';
    }
}
// Highlight the default-selected type on load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('type-lbl-advertiser').style.borderColor = '#4F46E5';
});

function insertMacro(inputId, macro) {
    var el = document.getElementById(inputId);
    if (!el) return;
    var s = el.selectionStart, e = el.selectionEnd;
    el.value = el.value.substring(0,s) + macro + el.value.substring(e);
    el.selectionStart = el.selectionEnd = s + macro.length;
    el.focus();
}

function copyAdvertiserUrl() {
    var appUrl = <?= json_encode(Helpers::trackingUrl()) ?>;
    copyTextInline(appUrl + '/postback?click_id={click_id}&payout={payout}&goal={goal}&txn_id={txn_id}', document.getElementById('copy-adv-btn'));
}
function copyTextInline(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.textContent; btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = orig; }, 2000);
    }).catch(function() {
        var ta = document.createElement('textarea'); ta.value = text;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        var orig = btn.textContent; btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = orig; }, 2000);
    });
}
function showFullUrl(url) {
    document.getElementById('url-modal-text').textContent = url;
    document.getElementById('url-modal').style.display = 'flex';
}
document.getElementById('url-modal').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
