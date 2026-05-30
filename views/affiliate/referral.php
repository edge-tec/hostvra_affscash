<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.ref-hero { background:linear-gradient(135deg,#4F46E5,#7C3AED); border-radius:14px; padding:28px 32px; color:#fff; margin-bottom:24px; }
.ref-hero h2 { font-size:20px; font-weight:800; margin:0 0 6px; }
.ref-hero p  { font-size:13px; opacity:.85; margin:0 0 20px; }
.ref-link-box { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:10px 14px; }
.ref-link-box code { flex:1; font-size:12px; color:#E0E7FF; word-break:break-all; }
.ref-link-copy { background:#fff; color:#4F46E5; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; flex-shrink:0; }
.ref-link-copy:hover { background:#EEF2FF; }
.ref-stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
.ref-stat-card { background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:16px 20px; text-align:center; }
.ref-stat-card .label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
.ref-stat-card .value { font-size:28px; font-weight:800; color:#0F172A; }
.ref-stat-card .sub   { font-size:12px; color:#94A3B8; margin-top:4px; }
.ref-tabs { display:flex; gap:0; border-bottom:2px solid #E2E8F0; margin-bottom:20px; }
.ref-tab  { padding:10px 18px; font-size:13px; font-weight:600; color:#64748B; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:.15s; }
.ref-tab.active { color:#4F46E5; border-bottom-color:#4F46E5; }
.ref-panel { display:none; } .ref-panel.active { display:block; }
</style>

<!-- Hero: referral link -->
<div class="ref-hero">
    <h2>🔗 Your Referral Link</h2>
    <p>Share this link with other affiliates. When they sign up and earn, you get a <?= Helpers::e($commRate) ?><?= $commType==='percent'?'%':' USD' ?> commission on their payouts!</p>
    <div class="ref-link-box">
        <code id="ref-link-text"><?= Helpers::e($refLink) ?></code>
        <button class="ref-link-copy" onclick="copyRefLink()" id="ref-copy-btn">📋 Copy Link</button>
    </div>
    <div style="margin-top:12px;font-size:12px;opacity:.7">
        Your referral code: <strong><?= Helpers::e($refCode) ?></strong>
    </div>
</div>

<!-- Stats -->
<div class="ref-stat-row">
    <div class="ref-stat-card">
        <div class="label">Total Referrals</div>
        <div class="value"><?= number_format($stats['signups']) ?></div>
        <div class="sub">Affiliates you referred</div>
    </div>
    <div class="ref-stat-card">
        <div class="label">Commissions Earned</div>
        <div class="value" style="color:#10B981">$<?= number_format($stats['earned'],2) ?></div>
        <div class="sub"><?= number_format($stats['total_commissions']) ?> commission payouts</div>
    </div>
    <div class="ref-stat-card">
        <div class="label">Commission Rate</div>
        <div class="value" style="color:#4F46E5"><?= Helpers::e($commRate) ?><?= $commType==='percent'?'%':' USD' ?></div>
        <div class="sub"><?= $commType==='percent'?'Of each referred payout':'Per referred conversion' ?></div>
    </div>
</div>

<!-- How it works -->
<div class="card mb-3" style="border-left:4px solid #4F46E5">
    <div class="card-body" style="padding:14px 18px;font-size:13px;color:#475569">
        <strong style="color:#4F46E5">How it works:</strong>
        Share your referral link → Affiliate registers → They click offers → They earn payout →
        You automatically earn <strong><?= Helpers::e($commRate) ?><?= $commType==='percent'?'%':' USD' ?></strong> of their payout as a commission, credited to your balance.
    </div>
</div>

<!-- Tabs -->
<div class="ref-tabs">
    <button class="ref-tab active" onclick="showRefTab('referred', this)">👥 Referred Affiliates (<?= count($referred) ?>)</button>
    <button class="ref-tab" onclick="showRefTab('commissions', this)">💰 My Commissions (<?= count($commissions) ?>)</button>
</div>

<!-- Panel: Referred Affiliates -->
<div class="ref-panel active" id="ref-panel-referred">
    <?php if (empty($referred)): ?>
    <div class="card">
        <div style="text-align:center;padding:48px 24px;color:#94A3B8">
            <div style="font-size:40px;margin-bottom:12px">👥</div>
            <h3 style="font-size:15px;color:#94A3B8;margin-bottom:6px">No referrals yet</h3>
            <p style="font-size:13px">Share your referral link to start earning commissions!</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table id="tbl-referred" style="font-size:13px">
                <thead>
                    <tr><th>Affiliate</th><th>Status</th><th>Conversions</th><th>Balance</th><th>Joined</th></tr>
                </thead>
                <tbody>
                <?php foreach ($referred as $r): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= Helpers::e($r['name']) ?></div>
                        <div class="text-muted" style="font-size:11px"><code><?= Helpers::e($r['affiliate_code']) ?></code></div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $r['status']==='active'?'success':($r['status']==='pending'?'warning':'muted') ?>">
                            <?= $r['status'] ?>
                        </span>
                    </td>
                    <td><?= number_format($r['conv_count']) ?></td>
                    <td class="fw-bold">$<?= number_format($r['balance'],2) ?></td>
                    <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Panel: Commissions -->
<div class="ref-panel" id="ref-panel-commissions">
    <?php if (empty($commissions)): ?>
    <div class="card">
        <div style="text-align:center;padding:48px 24px;color:#94A3B8">
            <div style="font-size:40px;margin-bottom:12px">💰</div>
            <h3 style="font-size:15px;color:#94A3B8;margin-bottom:6px">No commissions yet</h3>
            <p style="font-size:13px">Commissions are earned each time a referred affiliate gets an approved conversion.</p>
        </div>
    </div>
    <?php else: ?>
    <?php
    $approvedTotal  = array_sum(array_map(fn($c) => $c['status']==='approved' ? (float)$c['commission_amount'] : 0, $commissions));
    $pendingTotal   = array_sum(array_map(fn($c) => $c['status']==='pending'  ? (float)$c['commission_amount'] : 0, $commissions));
    ?>
    <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap">
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:10px 16px;font-size:13px">
            ✅ <strong>Approved:</strong> $<?= number_format($approvedTotal,2) ?>
        </div>
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px 16px;font-size:13px">
            ⏳ <strong>Pending:</strong> $<?= number_format($pendingTotal,2) ?>
        </div>
    </div>
    <div class="card">
        <div class="table-wrap">
            <table id="tbl-commissions" style="font-size:13px">
                <thead>
                    <tr><th>Referred Affiliate</th><th>Base Payout</th><th>Rate</th><th>Commission</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ($commissions as $c): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= Helpers::e($c['referred_name']) ?></div>
                        <div class="text-muted" style="font-size:11px"><code><?= Helpers::e($c['referred_code']) ?></code></div>
                    </td>
                    <td>$<?= number_format($c['base_payout'],4) ?></td>
                    <td><?= $c['commission_rate'] ?><?= $c['commission_type']==='percent'?'%':' USD' ?></td>
                    <td class="fw-bold" style="color:#4F46E5">$<?= number_format($c['commission_amount'],4) ?></td>
                    <td>
                        <span class="badge badge-<?= $c['status']==='approved'?'success':($c['status']==='pending'?'warning':'danger') ?>">
                            <?= $c['status'] ?>
                        </span>
                    </td>
                    <td class="text-sm text-muted"><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyRefLink() {
    var url = document.getElementById('ref-link-text').textContent.trim();
    var btn = document.getElementById('ref-copy-btn');
    navigator.clipboard.writeText(url).then(function() {
        btn.textContent = '✓ Copied!';
        btn.style.background = '#ECFDF5'; btn.style.color = '#065F46';
        setTimeout(function(){ btn.textContent = '📋 Copy Link'; btn.style.background=''; btn.style.color=''; }, 2200);
    }).catch(function() {
        var ta = document.createElement('textarea'); ta.value = url;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = '📋 Copy Link'; }, 2200);
    });
}

function showRefTab(panel, btn) {
    document.querySelectorAll('.ref-tab').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.ref-panel').forEach(function(p){ p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('ref-panel-' + panel).classList.add('active');
}

$(function(){
    if ($('#tbl-referred').length)    $('#tbl-referred').DataTable({destroy:true,pageLength:25,order:[],language:{search:'Search:'}});
    if ($('#tbl-commissions').length) $('#tbl-commissions').DataTable({destroy:true,pageLength:25,order:[[5,'desc']],language:{search:'Search:'}});
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
