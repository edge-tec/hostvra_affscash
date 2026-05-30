<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>Postback Setup</h1>
        <p>Set up your global postback to receive conversions directly in your tracker — works with any tracker.</p>
    </div>
</div>

<?php
$flash = Helpers::getFlash();
if (!empty($flash['success'])): ?>
<div class="alert alert-success mb-3">✓ <?= Helpers::e($flash['success']) ?></div>
<?php elseif (!empty($flash['error'])): ?>
<div class="alert alert-danger mb-3">⚠ <?= Helpers::e($flash['error']) ?></div>
<?php endif; ?>

<?php
$_gpbStatus = $globalPbAdminStatus ?? null;
if (empty($globalPostbackUrl)) {
    $_bg = '#F1F5F9'; $_clr = '#64748B'; $_txt = '○ Not Set';
} elseif ($_gpbStatus === 'approved') {
    $_bg = '#D1FAE5'; $_clr = '#065F46'; $_txt = '● Active';
} elseif ($_gpbStatus === 'rejected') {
    $_bg = '#FEE2E2'; $_clr = '#991B1B'; $_txt = '✕ Rejected';
} else {
    $_bg = '#FEF3C7'; $_clr = '#92400E'; $_txt = '⏳ Pending Approval';
}
$_appUrl = Config::get('config', 'app.url') ?? '';
?>

<!-- HOW IT WORKS -->
<div class="card mb-3" style="border-left:4px solid #0EA5E9;max-width:900px">
    <div class="card-header">
        <span class="card-title">📡 How Global Postback Works</span>
    </div>
    <div class="card-body" style="padding-bottom:14px">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0;font-size:13px">
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;min-width:130px;padding:10px 8px">
                <div style="width:42px;height:42px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:6px">🧑‍💻</div>
                <div style="font-weight:700;color:#1E40AF;font-size:12px">STEP 1</div>
                <div style="color:#475569;font-size:11px;margin-top:3px">You build your offer link with <strong>click_id</strong> macro from your tracker</div>
            </div>
            <div style="color:#94A3B8;font-size:20px;padding:0 2px">→</div>
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;min-width:130px;padding:10px 8px">
                <div style="width:42px;height:42px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:6px">👤</div>
                <div style="font-weight:700;color:#1E40AF;font-size:12px">STEP 2</div>
                <div style="color:#475569;font-size:11px;margin-top:3px">Visitor clicks → our tracker records the click &amp; stores your click_id</div>
            </div>
            <div style="color:#94A3B8;font-size:20px;padding:0 2px">→</div>
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;min-width:130px;padding:10px 8px">
                <div style="width:42px;height:42px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:6px">💰</div>
                <div style="font-weight:700;color:#1E40AF;font-size:12px">STEP 3</div>
                <div style="color:#475569;font-size:11px;margin-top:3px">Visitor converts → advertiser fires conversion postback to our tracker</div>
            </div>
            <div style="color:#94A3B8;font-size:20px;padding:0 2px">→</div>
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;min-width:130px;padding:10px 8px">
                <div style="width:42px;height:42px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:6px">🔔</div>
                <div style="font-weight:700;color:#1E40AF;font-size:12px">STEP 4</div>
                <div style="color:#475569;font-size:11px;margin-top:3px">Our tracker records conversion &amp; fires <strong>your global postback</strong> URL</div>
            </div>
            <div style="color:#94A3B8;font-size:20px;padding:0 2px">→</div>
            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;min-width:130px;padding:10px 8px">
                <div style="width:42px;height:42px;border-radius:50%;background:#F0FDF4;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:6px">✅</div>
                <div style="font-weight:700;color:#065F46;font-size:12px">RESULT</div>
                <div style="color:#475569;font-size:11px;margin-top:3px">Your tracker sees the conversion with your <strong>click_id</strong> &amp; <strong>payout</strong> in real time</div>
            </div>
        </div>
        <div style="background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px;padding:10px 14px;margin-top:8px;font-size:12px;color:#0369A1">
            <strong>💡 Works with any tracker</strong> — Binom, Keitaro, RedTrack, FunnelFlux, Voluum, or any custom tracker. No special integration needed. Just paste your tracker's postback URL below.
        </div>
    </div>
</div>

<!-- STEP 1 — Build Your Offer Link -->
<div class="card mb-3" style="border-left:4px solid #7C3AED;max-width:900px">
    <div class="card-header">
        <span class="card-title">🔗 Step 1 — Build Your Offer Link</span>
        <span style="font-size:12px;color:#64748B">Go to <a href="/affiliate/offers" style="color:#7C3AED;font-weight:600">Available Offers</a>, find your offer, and click <strong>🔗 Build</strong></span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:#475569;margin-bottom:12px">
            When building your tracking link using the <strong>🔗 Build</strong> button on any offer, fill in these parameters so our tracker can pass your click ID back to you when a conversion fires:
        </p>
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#991B1B">
            <strong>⚠ Important:</strong> Each parameter has its own dedicated slot. <code>click_id</code> and <code>aff_sub1</code> are <strong>separate</strong> — never share the same column.
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-bottom:14px">

            <!-- click_id → sub1 (REQUIRED) -->
            <div style="background:#F8FAFC;border:1px solid #4F46E5;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#4F46E5;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">click_id</span>
                    <span style="background:#FEF3C7;color:#92400E;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">REQUIRED</span>
                    <span style="font-size:10px;color:#64748B">→ sub1</span>
                </div>
                <div style="font-size:12px;color:#475569;margin-bottom:7px">Your tracker's click ID. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{click_id}</code> in postback so your tracker can match the conversion.</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px">
                    <span style="background:#F1F5F9;border:1px solid #CBD5E1;color:#475569;border-radius:4px;padding:2px 7px;font-size:10px;font-family:monospace">{clickid}</span>
                    <span style="background:#F1F5F9;border:1px solid #CBD5E1;color:#475569;border-radius:4px;padding:2px 7px;font-size:10px;font-family:monospace">{click_id}</span>
                    <span style="background:#F1F5F9;border:1px solid #CBD5E1;color:#475569;border-radius:4px;padding:2px 7px;font-size:10px;font-family:monospace">##CLICKID##</span>
                </div>
                <div style="font-size:10px;color:#94A3B8;margin-top:5px">Use your tracker's macro token format above</div>
            </div>

            <!-- sub_id_1 → sub2 -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#0EA5E9;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">sub_id_1</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                    <span style="font-size:10px;color:#64748B">→ sub2</span>
                </div>
                <div style="font-size:12px;color:#475569">Your tracker's affiliate ID or 1st custom parameter. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{sub_id_1}</code> in postback. Also accepts <code>affid=</code>.</div>
            </div>

            <!-- sub_id_2 → sub3 -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#10B981;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">sub_id_2</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                    <span style="font-size:10px;color:#64748B">→ sub3</span>
                </div>
                <div style="font-size:12px;color:#475569">Campaign name or 2nd segmentation value. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{sub_id_2}</code>. Also accepts <code>aff_sub1=</code>.</div>
            </div>

            <!-- sub_id_3 → sub4 -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#10B981;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">sub_id_3</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                    <span style="font-size:10px;color:#64748B">→ sub4</span>
                </div>
                <div style="font-size:12px;color:#475569">Creative ID or keyword. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{sub_id_3}</code>. Also accepts <code>aff_sub2=</code>.</div>
            </div>

            <!-- sub_id_4 → sub5 -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#10B981;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">sub_id_4</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                    <span style="font-size:10px;color:#64748B">→ sub5</span>
                </div>
                <div style="font-size:12px;color:#475569">Placement or zone ID. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{sub_id_4}</code>. Also accepts <code>aff_sub3=</code>.</div>
            </div>

            <!-- sub_id_5 → sub6 -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#10B981;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">sub_id_5</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                    <span style="font-size:10px;color:#64748B">→ sub6</span>
                </div>
                <div style="font-size:12px;color:#475569">5th segmentation value. Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{sub_id_5}</code>. Also accepts <code>aff_sub4=</code>.</div>
            </div>

            <!-- source -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
                    <span style="background:#F59E0B;color:#fff;border-radius:4px;padding:1px 7px;font-size:10px;font-weight:700;font-family:monospace">source</span>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700">optional</span>
                </div>
                <div style="font-size:12px;color:#475569">Traffic source name (e.g. facebook, push, native). Returned as <code style="background:#EEF2FF;padding:1px 4px;border-radius:3px">{source}</code>.</div>
            </div>

        </div>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:11px 14px;font-size:12px;color:#92400E">
            <strong>📌 Example offer link with all parameters:</strong>
            <code style="display:block;margin-top:5px;background:#FEF3C7;padding:6px 10px;border-radius:5px;font-size:11px;word-break:break-all">https://<?= htmlspecialchars(parse_url($_appUrl, PHP_URL_HOST) ?: 'yourtracker.com', ENT_QUOTES) ?>/click/42?aff=AFF123&amp;click_id=<strong>{clickid}</strong>&amp;sub_id_1={affid}&amp;sub_id_2={campaign}&amp;sub_id_3={creative}&amp;source=facebook</code>
            <span style="display:block;margin-top:5px">Replace <code>{clickid}</code>, <code>{affid}</code>, <code>{campaign}</code>, <code>{creative}</code> with your tracker's actual macro tokens.</span>
        </div>
    </div>
</div>

<!-- STEP 2 — Global Postback URL -->
<div class="card mb-3" style="border-left:4px solid #4F46E5;max-width:900px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">⚙️ Step 2 — Add Your Tracker's Global Postback URL</span>
        <span style="background:<?= $_bg ?>;color:<?= $_clr ?>;border-radius:20px;padding:3px 14px;font-size:12px;font-weight:700">
            <?= $_txt ?>
        </span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:#475569;margin-bottom:14px">
            In your tracker, find the <strong>S2S / postback URL</strong> section and copy the postback URL. 
            Replace your tracker's click ID placeholder with <code style="background:#EEF2FF;padding:1px 5px;border-radius:3px">{click_id}</code> 
            and the payout/revenue placeholder with <code style="background:#EEF2FF;padding:1px 5px;border-radius:3px">{payout}</code>, then paste the full URL below.
        </p>

        <details style="margin-bottom:14px">
            <summary style="cursor:pointer;font-size:12px;font-weight:600;color:#4F46E5;padding:7px 0">📋 Show example postback URL formats for popular trackers</summary>
            <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px">
                <?php
                $examples = [
                    ['Binom',      'https://binom.example.com/postback?click_id={click_id}&payout={payout}&sub1={aff_sub1}'],
                    ['Keitaro',    'https://keitaro.example.com/postback?subid={click_id}&revenue={payout}'],
                    ['RedTrack',   'https://postback.redtrack.io/postback?clickid={click_id}&cost={payout}'],
                    ['FunnelFlux', 'https://i.funnelflux.pro/postback?tid={click_id}&payout={payout}'],
                    ['Voluum',     'https://trk.voluum.com/postback?cid={click_id}&payout={payout}'],
                    ['Any tracker','https://yourtracker.com/postback?click_id={click_id}&payout={payout}&affid={affid}'],
                ];
                foreach ($examples as [$label, $url]): ?>
                <div style="display:flex;align-items:center;gap:8px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:7px 10px">
                    <span style="min-width:90px;font-size:11px;font-weight:700;color:#64748B"><?= $label ?></span>
                    <code style="font-size:11px;color:#4F46E5;word-break:break-all;flex:1"><?= Helpers::e($url) ?></code>
                </div>
                <?php endforeach; ?>
            </div>
        </details>

        <?php if ($_gpbStatus === 'rejected' && !empty($globalPostbackUrl)): ?>
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:11px 14px;margin-bottom:14px;font-size:12px;color:#991B1B">
            <strong>✕ Rejected by admin.</strong>
            <?php if (!empty($globalPbAdminNote)): ?>
            Reason: <em><?= Helpers::e($globalPbAdminNote) ?></em> —
            <?php endif; ?>
            Please update your URL below and save again.
        </div>
        <?php elseif ($_gpbStatus === 'pending' && !empty($globalPostbackUrl)): ?>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:11px 14px;margin-bottom:14px;font-size:12px;color:#92400E">
            <strong>⏳ Pending admin approval.</strong> Your postback will not fire until approved.
        </div>
        <?php endif; ?>

        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
            <div style="display:flex;align-items:center;gap:6px;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#4338CA">{click_id}</code>
                <span style="color:#4338CA">= your tracker's click ID (passed as click_id= in offer link)</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#15803D">{payout}</code>
                <span style="color:#15803D">= conversion payout amount</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#C2410C">{sub_id_1}</code>
                <span style="color:#C2410C">= sub_id_1= from offer link (stored in sub2)</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#475569">{sub_id_2}</code>
                <span style="color:#475569">= sub_id_2= from offer link (stored in sub3)</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#475569">{sub_id_3}</code>
                <span style="color:#475569">= sub_id_3= from offer link (stored in sub4)</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#475569">{sub_id_4}</code>
                <span style="color:#475569">= sub_id_4= from offer link (stored in sub5)</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:6px 10px;font-size:12px">
                <code style="font-weight:700;color:#475569">{sub_id_5}</code>
                <span style="color:#475569">= sub_id_5= from offer link (stored in sub6)</span>
            </div>
        </div>

        <form method="POST" id="global-pb-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_global">
            <div style="display:flex;gap:8px;align-items:stretch;flex-wrap:wrap">
                <input type="text" id="globalPbUrlInput" name="global_postback_url"
                       class="form-control"
                       value="<?= Helpers::e($globalPostbackUrl ?? '') ?>"
                       placeholder="https://yourtracker.com/postback?click_id={click_id}&payout={payout}"
                       style="font-family:monospace;font-size:12px;flex:1;min-width:280px"
                       oninput="validateGlobalUrl(this)">
                <button type="submit" class="btn btn-primary" style="white-space:nowrap">💾 Save Postback URL</button>
                <?php if (!empty($globalPostbackUrl)): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Remove your global postback URL? Conversions will no longer be sent to your tracker.')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="clear_global">
                    <button class="btn btn-danger" title="Remove postback URL" style="white-space:nowrap">🗑 Remove</button>
                </form>
                <?php endif; ?>
            </div>
            <div id="global-url-error" style="display:none;color:#EF4444;font-size:12px;margin-top:5px">
                ⚠ This looks like our Advertiser Postback URL. Please enter <strong>your tracker's</strong> postback URL instead.
            </div>
            <div style="font-size:11px;color:#94A3B8;margin-top:7px">
                This fires automatically on every approved conversion across all offers tied to your affiliate ID.
                Must contain <code>{click_id}</code> (required) and <code>{payout}</code>.
                Changing the URL requires admin re-approval.
            </div>
        </form>

        <?php if (!empty($globalPostbackUrl) && $_gpbStatus === 'approved'): ?>
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 14px;margin-top:14px;font-size:12px;color:#15803D">
            <strong>✅ Active global postback URL:</strong>
            <code style="display:block;margin-top:4px;background:#DCFCE7;padding:5px 8px;border-radius:4px;word-break:break-all;font-size:11px;color:#166534"><?= Helpers::e($globalPostbackUrl) ?></code>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- RECENT FIRES LOG -->
<div class="card" style="max-width:900px">
    <div class="card-header">
        <span class="card-title" style="color:#4F46E5;font-weight:700">📋 Recent Global Postback Fires</span>
        <span class="text-muted text-sm">Last <?= count($logs) ?> events</span>
    </div>
    <?php if (empty($logs)): ?>
    <div style="padding:36px;text-align:center;color:#94A3B8;font-size:13px">
        <div style="font-size:28px;margin-bottom:8px">📭</div>
        No postback fires yet. Once a conversion is recorded for your affiliate ID, it will appear here.
        <?php if (empty($globalPostbackUrl)): ?>
        <div style="margin-top:8px;color:#F59E0B;font-size:12px">⚠ Set up your global postback URL above first.</div>
        <?php elseif ($_gpbStatus !== 'approved'): ?>
        <div style="margin-top:8px;color:#F59E0B;font-size:12px">⚠ Your postback URL is <?= $_txt ?> — fires will begin once approved by admin.</div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-wrap" style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead>
                <tr>
                    <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap">Conversion</th>
                    <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;background:#F8FAFC;border-bottom:2px solid #E2E8F0">Fired URL (your tracker)</th>
                    <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap">HTTP</th>
                    <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap">Result</th>
                    <th style="padding:9px 14px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.04em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap">Fired At</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log):
                $hs = (int)($log['http_status'] ?? 0);
                $ok = (bool)$log['is_success'];
                $hsBg  = ($hs >= 200 && $hs < 300) ? '#D1FAE5' : ($hs > 0 ? '#FEF3C7' : '#FEE2E2');
                $hsFg  = ($hs >= 200 && $hs < 300) ? '#065F46' : ($hs > 0 ? '#92400E' : '#991B1B');
            ?>
            <tr style="border-bottom:1px solid #F1F5F9;background:<?= $ok ? '#fff' : '#FFF5F5' ?>">
                <td style="padding:9px 14px;font-family:monospace;font-size:11px;color:#64748B;white-space:nowrap">
                    <?= substr(Helpers::e($log['conversion_id']), 0, 8) ?>…
                </td>
                <td style="padding:9px 14px;max-width:360px">
                    <div style="font-family:monospace;font-size:10px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:350px"
                         title="<?= Helpers::e($log['fired_url'] ?? '') ?>">
                        <?= Helpers::e($log['fired_url'] ?? '—') ?>
                    </div>
                </td>
                <td style="padding:9px 14px;white-space:nowrap">
                    <span style="background:<?= $hsBg ?>;color:<?= $hsFg ?>;border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700;font-family:monospace">
                        <?= $hs ?: 'Timeout' ?>
                    </span>
                </td>
                <td style="padding:9px 14px">
                    <?= $ok
                        ? '<span style="color:#10B981;font-weight:700;font-size:13px">✓ Sent</span>'
                        : '<span style="color:#EF4444;font-weight:700;font-size:13px">✗ Failed</span>' ?>
                </td>
                <td style="padding:9px 14px;color:#94A3B8;font-size:11px;white-space:nowrap">
                    <?= date('M j, H:i', strtotime($log['fired_at'])) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
var ownDomain = <?= json_encode(rtrim(parse_url($_appUrl, PHP_URL_HOST) ?? '', '/')) ?>;

function validateGlobalUrl(el) {
    var val    = el.value.toLowerCase();
    var errDiv = document.getElementById('global-url-error');
    if (ownDomain && val.indexOf(ownDomain) !== -1 && val.indexOf('/postback') !== -1) {
        errDiv.style.display = 'block';
    } else {
        errDiv.style.display = 'none';
    }
}

document.getElementById('global-pb-form').addEventListener('submit', function(e) {
    var url = document.getElementById('globalPbUrlInput').value.toLowerCase();
    if (ownDomain && url.indexOf(ownDomain) !== -1 && url.indexOf('/postback') !== -1) {
        e.preventDefault();
        document.getElementById('global-url-error').style.display = 'block';
        document.getElementById('globalPbUrlInput').focus();
        alert('⚠ You entered our system\'s Advertiser Postback URL. Please enter your own tracker\'s postback URL instead.');
        return false;
    }
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
