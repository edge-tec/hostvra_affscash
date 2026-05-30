<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<style>
.ref-hero { background:linear-gradient(135deg,#1E40AF,#4F46E5); border-radius:14px; padding:28px 32px; color:#fff; margin-bottom:24px; }
.ref-hero h2 { font-size:20px; font-weight:800; margin:0 0 6px; }
.ref-hero p  { font-size:13px; opacity:.85; margin:0 0 20px; }
.ref-link-box { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:10px 14px; }
.ref-link-box code { flex:1; font-size:12px; color:#BFDBFE; word-break:break-all; }
.ref-link-copy { background:#fff; color:#1E40AF; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap; flex-shrink:0; }
.ref-link-copy:hover { background:#DBEAFE; }
.ref-stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
.ref-stat-card { background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:16px 20px; text-align:center; }
.ref-stat-card .label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
.ref-stat-card .value { font-size:28px; font-weight:800; color:#0F172A; }
.ref-stat-card .sub   { font-size:12px; color:#94A3B8; margin-top:4px; }
</style>

<!-- Hero -->
<div class="ref-hero">
    <h2>🔗 Your Manager Referral Link</h2>
    <p>When a new affiliate registers using your link, they are automatically assigned to your team — no manual assignment needed.</p>
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
        <div class="label">Referred Affiliates</div>
        <div class="value"><?= number_format($stats['signups']) ?></div>
        <div class="sub">Auto-assigned to your team</div>
    </div>
    <div class="ref-stat-card">
        <div class="label">Active</div>
        <div class="value" style="color:#10B981"><?= number_format(count(array_filter($referred, fn($r)=>$r['status']==='active'))) ?></div>
        <div class="sub">Of <?= count($referred) ?> referred affiliates</div>
    </div>
    <div class="ref-stat-card">
        <div class="label">Total Earned (Referred)</div>
        <div class="value" style="color:#4F46E5">$<?= number_format(array_sum(array_column($referred,'total_earned')),2) ?></div>
        <div class="sub">By your referred affiliates</div>
    </div>
</div>

<!-- How it works -->
<div class="card mb-3" style="border-left:4px solid #1E40AF">
    <div class="card-body" style="padding:14px 18px;font-size:13px;color:#475569">
        <strong style="color:#1E40AF">How it works:</strong>
        Share your unique referral link → New affiliates register using it → They are automatically assigned to your team →
        They appear in your <a href="/affiliate_manager/affiliates">My Affiliates</a> list immediately.
    </div>
</div>

<!-- Referred affiliates table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Affiliates Referred by You</span>
        <span class="text-muted text-sm"><?= number_format(count($referred)) ?> total</span>
    </div>
    <div class="table-wrap">
        <?php if (empty($referred)): ?>
        <div style="text-align:center;padding:48px 24px;color:#94A3B8">
            <div style="font-size:40px;margin-bottom:12px">👥</div>
            <h3 style="font-size:15px;color:#94A3B8;margin-bottom:6px">No referrals yet</h3>
            <p style="font-size:13px">Share your referral link to grow your team automatically.</p>
        </div>
        <?php else: ?>
        <table id="tbl-referred" style="font-size:13px">
            <thead>
                <tr><th>Affiliate</th><th>Status</th><th>Clicks</th><th>Conversions</th><th>Total Earned</th><th>Balance</th><th>Joined via Ref</th></tr>
            </thead>
            <tbody>
            <?php foreach ($referred as $r): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['email']) ?> · <code style="font-size:10px"><?= Helpers::e($r['affiliate_code']) ?></code></div>
                </td>
                <td>
                    <span class="badge badge-<?= $r['status']==='active'?'success':($r['status']==='pending'?'warning':'muted') ?>">
                        <?= $r['status'] ?>
                    </span>
                </td>
                <td><?= number_format($r['click_count']) ?></td>
                <td><?= number_format($r['conv_count']) ?></td>
                <td class="fw-bold" style="color:#4F46E5">$<?= number_format($r['total_earned'],2) ?></td>
                <td class="fw-bold">$<?= number_format($r['balance'],2) ?></td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
function copyRefLink() {
    var url = document.getElementById('ref-link-text').textContent.trim();
    var btn = document.getElementById('ref-copy-btn');
    navigator.clipboard.writeText(url).then(function() {
        btn.textContent = '✓ Copied!';
        btn.style.background = '#DBEAFE'; btn.style.color = '#1E40AF';
        setTimeout(function(){ btn.textContent = '📋 Copy Link'; btn.style.background=''; btn.style.color=''; }, 2200);
    }).catch(function() {
        var ta = document.createElement('textarea'); ta.value = url;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = '📋 Copy Link'; }, 2200);
    });
}
$(function(){
    if ($('#tbl-referred').length) $('#tbl-referred').DataTable({destroy:true,pageLength:25,order:[[6,'desc']],language:{search:'Search:'}});
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
