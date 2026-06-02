<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<style>
/* ── Offer Overview Responsive ───────────────────────────── */
.ov-main-grid    { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }
.ov-stat-grid    { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px; }
.ov-track-row    { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.ov-track-col-sm { flex:1; min-width:200px; }
.ov-track-col-lg { flex:2; min-width:260px; }
@media (max-width:900px) {
    .ov-main-grid { grid-template-columns:1fr; }
    .ov-stat-grid { grid-template-columns:repeat(2,1fr); }
}
@media (max-width:560px) {
    .ov-stat-grid    { grid-template-columns:1fr; }
    .ov-track-col-sm { min-width:100%; }
    .ov-track-col-lg { min-width:100%; }
}
@media (max-width:700px) {
    .ov-inner-grid { grid-template-columns:repeat(2,1fr) !important; }
}
@media (max-width:420px) {
    .ov-inner-grid { grid-template-columns:1fr !important; }
}
</style>

<div class="page-header">
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <a href="/admin/offers" style="color:var(--text-muted);text-decoration:none;font-size:13px">&#8592; All Offers</a>
        </div>
        <h1><?= Helpers::e($offer['name']) ?></h1>
        <p><?= Helpers::e($offer['category'] ?: 'No category') ?> &bull; <?= Helpers::e($offer['adv_name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/offers/<?= $offer['id'] ?>" class="btn btn-secondary">&#9998; Edit Offer</a>
        <?php
        $statusColors = ['active'=>'var(--secondary)','paused'=>'#F59E0B','expired'=>'#94A3B8','pending'=>'var(--primary)'];
        $statusColor  = $statusColors[$offer['status']] ?? '#94A3B8';
        ?>
        <span class="badge" style="background:<?= $statusColor ?>;color:#fff;padding:8px 16px;font-size:13px;border-radius:6px"><?= strtoupper($offer['status']) ?></span>
    </div>
</div>

<!-- ── Offer Detail Card ─────────────────────────────────────────────── -->
<div class="ov-main-grid mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Offer Details</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px" class="ov-inner-grid">
                <div>
                    <div class="text-muted text-sm mb-1">Payout Type</div>
                    <div class="fw-bold"><span class="badge badge-info"><?= Helpers::e($offer['payout_type']) ?></span></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Affiliate Payout</div>
                    <div class="fw-bold" style="font-size:18px;color:var(--secondary)">$<?= number_format($offer['payout_amount'],2) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Advertiser Revenue</div>
                    <div class="fw-bold" style="font-size:18px;color:var(--primary)">$<?= number_format($offer['revenue_amount'],2) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Visibility</div>
                    <div class="fw-bold"><?= ucfirst($offer['visibility'] ?? 'public') ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Require Approval</div>
                    <div class="fw-bold"><?= $offer['require_approval'] ? '&#9989; Yes' : '&#10060; No' ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Advertiser</div>
                    <div class="fw-bold"><?= Helpers::e($offer['adv_name']) ?></div>
                    <?php if ($offer['adv_company']): ?><div class="text-sm text-muted"><?= Helpers::e($offer['adv_company']) ?></div><?php endif; ?>
                </div>
                <?php
                $geos    = $offer['geo_targeting']    ? json_decode($offer['geo_targeting'],    true) : [];
                $devices = $offer['device_targeting'] ? json_decode($offer['device_targeting'], true) : [];
                ?>
                <div>
                    <div class="text-muted text-sm mb-1">GEO Targeting</div>
                    <div class="fw-bold text-sm"><?= Helpers::geoList($geos) ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Device Targeting</div>
                    <div class="fw-bold text-sm"><?= $devices ? implode(', ', $devices) : 'All Devices' ?></div>
                </div>
                <div>
                    <div class="text-muted text-sm mb-1">Created</div>
                    <div class="fw-bold text-sm"><?= date('M j, Y', strtotime($offer['created_at'])) ?></div>
                </div>
            </div>
            <?php if ($offer['description']): ?>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #E2E8F0">
                <div class="text-muted text-sm mb-1">Description</div>
                <div style="font-size:14px"><?= nl2br(Helpers::e($offer['description'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($offer['offer_url']): ?>
            <div style="margin-top:12px">
                <div class="text-muted text-sm mb-1">Offer URL</div>
                <code style="font-size:12px;background:#F1F5F9;padding:4px 8px;border-radius:4px;word-break:break-all"><?= Helpers::e($offer['offer_url']) ?></code>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
        <!-- Cap usage -->
        <div class="card">
            <div class="card-header"><span class="card-title">Cap Usage</span></div>
            <div class="card-body">
                <?php
                $dailyCap = (int)$offer['daily_cap'];
                $totalCap = (int)$offer['total_cap'];
                // $todayCapUsed / $totalCapUsed = conversions count (matches click.php enforcement)
                $dailyPct = $dailyCap > 0 ? min(100, round($todayCapUsed / $dailyCap * 100)) : 0;
                $totalPct = $totalCap > 0 ? min(100, round($totalCapUsed / $totalCap  * 100)) : 0;
                ?>
                <div style="margin-bottom:16px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                        <span class="text-sm fw-bold">Daily Cap <span style="font-weight:400;color:#9CA3AF;font-size:10px">(resets midnight)</span></span>
                        <span class="text-sm text-muted"><?= number_format($todayCapUsed) ?> / <?= $dailyCap ? number_format($dailyCap) : '&infin;' ?></span>
                    </div>
                    <?php if ($dailyCap > 0): ?>
                    <div style="background:#E2E8F0;border-radius:4px;height:8px">
                        <div style="background:<?= $dailyPct>=100?'var(--danger)':($dailyPct>=80?'#F59E0B':'var(--secondary)') ?>;width:<?= $dailyPct ?>%;height:8px;border-radius:4px;transition:width .3s"></div>
                    </div>
                    <div class="text-sm" style="margin-top:2px;color:<?= $dailyPct>=100?'var(--danger)':'var(--text-muted)' ?>">
                        <?= $dailyPct ?>% used today<?= $dailyPct>=100?' &mdash; <strong>Cap reached. New clicks redirected to Traffic Back URL.</strong>':'' ?>
                    </div>
                    <?php else: ?>
                    <div class="text-sm text-muted">No daily cap</div>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                        <span class="text-sm fw-bold">Total Cap</span>
                        <span class="text-sm text-muted"><?= number_format($totalCapUsed) ?> / <?= $totalCap ? number_format($totalCap) : '&infin;' ?></span>
                    </div>
                    <?php if ($totalCap > 0): ?>
                    <div style="background:#E2E8F0;border-radius:4px;height:8px">
                        <div style="background:<?= $totalPct>=100?'var(--danger)':($totalPct>=80?'#F59E0B':'var(--secondary)') ?>;width:<?= $totalPct ?>%;height:8px;border-radius:4px;transition:width .3s"></div>
                    </div>
                    <div class="text-sm text-muted" style="margin-top:2px"><?= $totalPct ?>% used all-time</div>
                    <?php else: ?>
                    <div class="text-sm text-muted">No total cap</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Affiliate access -->
        <div class="card">
            <div class="card-header"><span class="card-title">Affiliate Access</span></div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-around;text-align:center">
                    <div>
                        <div style="font-size:28px;font-weight:800;color:var(--secondary)"><?= $approvedAffCount ?></div>
                        <div class="text-sm text-muted">Approved</div>
                    </div>
                    <div style="width:1px;background:#E2E8F0"></div>
                    <div>
                        <div style="font-size:28px;font-weight:800;color:#F59E0B"><?= $pendingAffCount ?></div>
                        <div class="text-sm text-muted">Pending</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Pending Affiliate Applications ────────────────────────────────── -->
<?php if (!empty($pendingApplications)): ?>
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Pending Access Requests <span class="badge badge-warning" style="margin-left:6px"><?= count($pendingApplications) ?></span></span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Affiliate</th><th>Code</th><th>Promotion Description</th><th>Applied</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pendingApplications as $pa): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($pa['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($pa['aff_email']) ?></div>
                </td>
                <td><code style="font-size:11px;background:#F1F5F9;padding:1px 5px;border-radius:3px"><?= Helpers::e($pa['affiliate_code']) ?></code></td>
                <td>
                    <?php if (!empty($pa['notes'])): ?>
                    <div style="font-size:13px;max-width:360px;line-height:1.5;color:#374151"><?= nl2br(Helpers::e($pa['notes'])) ?></div>
                    <?php else: ?>
                    <span class="text-muted text-sm">— No description provided —</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($pa['applied_at'])) ?></td>
                <td>
                    <form method="POST" action="/admin/affiliates/<?= $pa['id'] ?>/approve-offer" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="affiliate_offer_id" value="<?= $pa['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="redirect" value="/admin/offers/<?= $offer['id'] ?>/overview">
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="/admin/affiliates/<?= $pa['id'] ?>/approve-offer" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="affiliate_offer_id" value="<?= $pa['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="redirect" value="/admin/offers/<?= $offer['id'] ?>/overview">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Advertiser Postback URL ─────────────────────────────────────────── -->
<div class="card mb-3" style="border-left:4px solid #F59E0B;border-radius:0 var(--radius) var(--radius) 0">
    <div class="card-header" style="background:#FFFBEB">
        <span class="card-title">&#128232; Advertiser Postback URL — give this to the advertiser</span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px">
            The advertiser fires this URL from their server when a conversion is confirmed.
            Only <strong>click_id</strong> and <strong>payout</strong> are required.
        </p>
        <div class="copy-group">
            <input type="text" id="adv-pb-url" class="form-control" readonly
                   value="<?= Helpers::e(Helpers::trackingUrl()) ?>/postback?click_id={click_id}&payout={payout}&goal={goal}&txn_id={txn_id}"
                   style="font-size:12px;font-family:monospace;background:#FFFBEB">
            <button class="btn btn-sm" style="background:#F59E0B;color:#fff;border-color:#F59E0B"
                    onclick="(function(){var el=document.getElementById('adv-pb-url');navigator.clipboard?navigator.clipboard.writeText(el.value):(el.select(),document.execCommand('copy'));var b=event.target;b.textContent='Copied!';setTimeout(function(){b.textContent='Copy';},1500)})()">Copy</button>
        </div>
        <div style="margin-top:8px;font-size:12px;color:var(--text-muted)">
            <strong>{click_id}</strong> required — UUID from the offer URL &nbsp;|&nbsp;
            <strong>{payout}</strong> required — conversion amount &nbsp;|&nbsp;
            <strong>{goal}</strong> optional — goal name (e.g. Deposit) &nbsp;|&nbsp;
            <strong>{txn_id}</strong> optional — your order/transaction ID
        </div>
        <div style="margin-top:10px;padding:10px 14px;background:#FEF3C7;border-radius:6px;font-size:12px;color:#92400E">
            &#9888; When you add this offer's landing page to the advertiser system, append
            <code style="background:#FDE68A;padding:1px 5px;border-radius:3px">?click_id={click_id}&aff_id={aff_id}</code>
            to the destination URL so the advertiser's page receives the click ID and affiliate mapping.
        </div>
    </div>
</div>

<!-- ── Generate Affiliate Tracking Link ──────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">&#128279; Generate Tracking Link for Affiliate</span>
    </div>
    <div class="card-body">
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
            Canonical format: <code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:11px"><?= Helpers::e(Helpers::trackingUrl()) ?>/click/<?= $offer['id'] ?>?aff_id={AFF_CODE}&click_id={TRACKER_CLICK_ID}</code>
        </p>
        <div class="ov-track-row">
            <div class="ov-track-col-sm">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Select Affiliate</label>
                <select id="aff-link-select" class="form-control" onchange="buildAffLink()" style="font-size:13px">
                    <option value="">— Choose affiliate —</option>
                    <?php foreach ($allAffiliates as $a): ?>
                    <option value="<?= Helpers::e($a['affiliate_code']) ?>" data-name="<?= Helpers::e($a['name']) ?>">
                        <?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ov-track-col-sm">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">
                    Tracker click ID macro <span style="color:#10B981;font-size:10px">(required for postback)</span>
                </label>
                <input type="text" id="aff-link-cid" class="form-control"
                       placeholder="e.g. {CLICKID} or {click_id}" oninput="buildAffLink()" style="font-size:13px">
            </div>
            <div class="ov-track-col-lg">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px">Generated Tracking Link</label>
                <div class="copy-group">
                    <input type="text" id="aff-link-output" class="form-control" readonly
                           placeholder="Select affiliate above..." style="font-size:12px;background:#F8FAFC">
                    <button class="btn btn-primary btn-sm" onclick="copyAffLink()">Copy</button>
                </div>
            </div>
        </div>
        <div id="aff-link-note" style="display:none;margin-top:10px;padding:8px 12px;background:#EFF6FF;border-radius:6px;font-size:12px;color:#1D4ED8"></div>
    </div>
</div>
<script>
var _affLinkBase = '<?= Helpers::trackingUrl() ?>/click/<?= $offer['id'] ?>?';
function buildAffLink(){
    var sel = document.getElementById('aff-link-select');
    var cid = document.getElementById('aff-link-cid').value.trim();
    var out = document.getElementById('aff-link-output');
    var note = document.getElementById('aff-link-note');
    if(!sel.value){out.value='';note.style.display='none';return;}
    var opt = sel.options[sel.selectedIndex];
    // Canonical: aff_id= and click_id=
    var url = _affLinkBase + 'aff_id=' + sel.value;
    if(cid) url += '&click_id=' + cid;
    out.value = url;
    note.style.display='block';
    note.innerHTML = 'Link for: <strong>' + opt.getAttribute('data-name') + '</strong> ('
        + sel.value + ')' + (!cid
            ? ' &nbsp;<span style="color:#F59E0B">&#9888; Add &amp;click_id={TRACKER_MACRO} for postback to work</span>'
            : ' &nbsp;<span style="color:#10B981">&#10003; Postback will return {click_id}='+cid+'</span>');
}
function copyAffLink(){
    var out = document.getElementById('aff-link-output');
    if(!out.value) return;
    navigator.clipboard ? navigator.clipboard.writeText(out.value) : (out.select(), document.execCommand('copy'));
    var btn = event.target; btn.textContent='Copied!';
    setTimeout(function(){btn.textContent='Copy';},1500);
}
</script>

<!-- ── Summary Stat Cards ────────────────────────────────────────────── -->
<div class="ov-stat-grid">
    <!-- Today -->
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">Today</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($todayStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($todayStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format($todayStats['payout'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">Revenue</div><div class="fw-bold" style="color:var(--primary)">$<?= number_format($todayStats['revenue'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">CR%</div><div class="fw-bold"><?= ($todayStats['clicks'] ?? 0) > 0 ? round(($todayStats['conversions'] ?? 0) / $todayStats['clicks'] * 100, 2) : 0 ?>%</div></div>
            <div><div class="text-muted text-sm">Fraud</div><div class="fw-bold" style="color:var(--danger)"><?= number_format($todayStats['fraud_clicks'] ?? 0) ?></div></div>
        </div>
    </div>
    <!-- 30-day -->
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">Last 30 Days</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($monthStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($monthStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format($monthStats['payout'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">Revenue</div><div class="fw-bold" style="color:var(--primary)">$<?= number_format($monthStats['revenue'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">Approved</div><div class="fw-bold"><?= number_format($monthStats['approved'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Rejected</div><div class="fw-bold" style="color:var(--danger)"><?= number_format($monthStats['rejected'] ?? 0) ?></div></div>
        </div>
    </div>
    <!-- All-time -->
    <div class="card">
        <div class="card-header" style="background:#F8FAFC"><span class="card-title">All Time</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><div class="text-muted text-sm">Clicks</div><div class="fw-bold" style="font-size:20px"><?= number_format($allStats['clicks'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Conversions</div><div class="fw-bold" style="font-size:20px"><?= number_format($allStats['conversions'] ?? 0) ?></div></div>
            <div><div class="text-muted text-sm">Payout</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format($allStats['payout'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">Revenue</div><div class="fw-bold" style="color:var(--primary)">$<?= number_format($allStats['revenue'] ?? 0, 2) ?></div></div>
            <div><div class="text-muted text-sm">EPC</div><div class="fw-bold"><?= ($allStats['clicks'] ?? 0) > 0 ? '$'.round(($allStats['payout'] ?? 0) / $allStats['clicks'], 4) : '$0' ?></div></div>
            <div><div class="text-muted text-sm">Profit</div><div class="fw-bold" style="color:var(--secondary)">$<?= number_format(($allStats['revenue'] ?? 0) - ($allStats['payout'] ?? 0), 2) ?></div></div>
        </div>
    </div>
</div>

<!-- ── Row: 30-day chart + Conversion Status pie ─────────────────────── -->
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header">
            <span class="card-title">30-Day Performance</span>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary btn-sm chart-type-btn active" data-chart="offerPerfChart" data-type="line">Line</button>
                <button class="btn btn-secondary btn-sm chart-type-btn" data-chart="offerPerfChart" data-type="bar">Bar</button>
            </div>
        </div>
        <div class="card-body"><div class="chart-container"><canvas id="offerPerfChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Conversion Status</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center">
            <div style="position:relative;height:260px;width:100%;max-width:320px;margin:0 auto">
                <canvas id="offerConvPie"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Row: 7-day revenue bar + Top Countries ────────────────────────── -->
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">7-Day Revenue vs Payout</span></div>
        <div class="card-body"><div class="chart-container"><canvas id="offerWeekChart"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Top Countries (30 days)</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Country</th><th>Clicks</th><th>Unique</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?></tr></thead>
                <tbody>
                <?php if (empty($topCountries)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:24px">No data yet</td></tr>
                <?php else: foreach ($topCountries as $c): ?>
                <tr>
                    <td>
                        <?php if (!empty($c['country'])): ?>
                        <img src="https://flagcdn.com/16x12/<?= strtolower(Helpers::e($c['country'])) ?>.png" onerror="this.style.display='none'" style="vertical-align:middle;margin-right:5px">
                        <?php endif; ?>
                        <span class="fw-bold"><?= Helpers::e($c['country'] ?: '—') ?></span>
                    </td>
                    <td><?= number_format($c['clicks']) ?></td>
                    <td><?= number_format($c['uclicks']) ?></td>
                    <td>$<?= number_format($c['payout'], 2) ?></td>
                    <td>$<?= number_format($c['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Top Affiliates ─────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Top Affiliates (30 days)</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-top-aff">
            <thead><tr><th>Affiliate</th><th>Code</th><th>Clicks</th><th>Conv.</th><th>Approved</th><th>CR%</th><th>EPC</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Profit</th></tr></thead>
            <tbody>
            <?php if (empty($topAffiliates)): ?>
            <tr><td colspan="10" class="text-center text-muted" style="padding:24px">No affiliate data yet</td></tr>
            <?php else: foreach ($topAffiliates as $a):
                $cr     = $a['clicks'] > 0 ? round($a['conversions'] / $a['clicks'] * 100, 2) : 0;
                $epc    = $a['clicks'] > 0 ? round($a['payout'] / $a['clicks'], 4) : 0;
                $profit = (float)$a['revenue'] - (float)$a['payout'];
            ?>
            <tr>
                <td class="fw-bold"><?= Helpers::e($a['name']) ?></td>
                <td><code style="font-size:11px;background:#F1F5F9;padding:1px 5px;border-radius:3px"><?= Helpers::e($a['affiliate_code']) ?></code></td>
                <td><?= number_format($a['clicks']) ?></td>
                <td><?= number_format($a['conversions']) ?></td>
                <td><?= number_format($a['approved']) ?></td>
                <td><?= $cr ?>%</td>
                <td>$<?= $epc ?></td>
                <td class="fw-bold" style="color:var(--secondary)">$<?= number_format($a['payout'], 2) ?></td>
                <td>$<?= number_format($a['revenue'], 2) ?></td>
                <td style="color:<?= $profit >= 0 ? 'var(--secondary)' : 'var(--danger)' ?>">$<?= number_format($profit, 2) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Recent Conversions ─────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Recent Conversions</span>
        <a href="/admin/conversions?offer_id=<?= $offer['id'] ?>" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-wrap">
        <table id="tbl-conv">
            <thead><tr><th>Affiliate</th><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Status</th><th>Goal</th><th>Time</th></tr></thead>
            <tbody>
            <?php if (empty($recentConversions)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No conversions yet</td></tr>
            <?php else: foreach ($recentConversions as $cv):
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($cv['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($cv['affiliate_code']) ?></div>
                </td>
                <td class="fw-bold" style="color:var(--secondary)">$<?= number_format($cv['payout'], 2) ?></td>
                <td>$<?= number_format($cv['revenue'], 2) ?></td>
                <td><span class="badge badge-<?= $bm[$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span></td>
                <td class="text-sm text-muted"><?= Helpers::e($cv['goal_name'] ?: '—') ?></td>
                <td class="text-sm text-muted"><?= date('M j, H:i', strtotime($cv['converted_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const COLORS = ['#4F46E5','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#06B6D4','#F97316'];
    const alpha  = (hex, a) => hex + Math.round(a*255).toString(16).padStart(2,'0');
    const registry = {};

    function makeChart(id, cfg) {
        const ctx = document.getElementById(id);
        if (!ctx || typeof Chart === 'undefined') return null;
        const ch = new Chart(ctx, cfg);
        registry[id] = { chart: ch };
        return ch;
    }

    function switchType(chartId, newType) {
        if (!registry[chartId]) return;
        const { chart } = registry[chartId];
        const labels = chart.data.labels;
        const datasets = chart.data.datasets.map((ds, i) => ({
            ...ds, type: undefined,
            backgroundColor: newType === 'pie' ? COLORS.slice(0, labels.length) : COLORS.slice(i, i+1).map(c => alpha(c, '30')),
            borderColor: newType === 'pie' ? '#fff' : COLORS[i % COLORS.length],
            fill: newType === 'line', tension: newType === 'line' ? .3 : 0,
        }));
        chart.config.type = newType;
        chart.data.datasets = datasets;
        chart.options.scales = newType === 'pie' ? {} : { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { color: '#F1F5F9' } } };
        chart.update();
    }

    document.querySelectorAll('.chart-type-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchType(this.dataset.chart, this.dataset.type);
            this.closest('.card-header').querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    /* 30-day performance */
    makeChart('offerPerfChart', {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chartData, 'stat_date')) ?>,
            datasets: [
                { label:'Clicks',      data: <?= json_encode(array_map('intval',   array_column($chartData,'clicks'))) ?>,      borderColor:'#4F46E5', backgroundColor:'rgba(79,70,229,.09)',  tension:.3, fill:true },
                { label:'Conversions', data: <?= json_encode(array_map('intval',   array_column($chartData,'conversions'))) ?>, borderColor:'#10B981', backgroundColor:'rgba(16,185,129,.09)', tension:.3, fill:true },
            ]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'}},x:{grid:{color:'#F1F5F9'}}} }
    });

    /* Conversion status pie */
    <?php
    $pieL = array_column($convStatus, 'status');
    $pieV = array_map('intval', array_column($convStatus, 'cnt'));
    $pieC = ['#10B981','#F59E0B','#EF4444','#94A3B8','#3B82F6'];
    ?>
    makeChart('offerConvPie', {
        type: 'pie',
        data: {
            labels: <?= json_encode($pieL ?: ['No data']) ?>,
            datasets: [{ data: <?= json_encode($pieV ?: [1]) ?>, backgroundColor: <?= json_encode(array_slice($pieC, 0, max(1, count($pieL)))) ?>, borderColor:'#fff', borderWidth:2 }]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });

    /* 7-day revenue vs payout */
    makeChart('offerWeekChart', {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($weekData, 'stat_date')) ?>,
            datasets: [
                { label:'Revenue', data: <?= json_encode(array_map('floatval', array_column($weekData,'revenue'))) ?>, backgroundColor:'rgba(79,70,229,.75)',  borderColor:'#4F46E5', borderWidth:1 },
                { label:'Payout',  data: <?= json_encode(array_map('floatval', array_column($weekData,'payout'))) ?>,  backgroundColor:'rgba(16,185,129,.75)', borderColor:'#10B981', borderWidth:1 },
            ]
        },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{callback:v=>'$'+v}},x:{grid:{color:'#F1F5F9'}}} }
    });

    /* DataTables */
    $.fn.dataTable.ext.errMode = 'none';
    function dtInit(id, opts) { var $t=$('#'+id); if(!$t.length)return; if(opts.scrollX)opts.scrollX=$t.find('tbody tr').length>0; $t.DataTable(opts); }
    $(function() {
        dtInit('tbl-top-aff', { destroy:true, pageLength:10, order:[[7,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No data yet'} });
        dtInit('tbl-conv',    { destroy:true, pageLength:10, order:[[5,'desc']], language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No conversions yet'} });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
