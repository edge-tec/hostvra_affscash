<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.ref-tabs { display:flex; gap:0; border-bottom:2px solid #E2E8F0; margin-bottom:24px; }
.ref-tab  { padding:10px 20px; font-size:13px; font-weight:600; color:#64748B; text-decoration:none;
            border-bottom:2px solid transparent; margin-bottom:-2px; transition:.15s; white-space:nowrap; }
.ref-tab:hover  { color:#334155; text-decoration:none; }
.ref-tab.active { color:#4F46E5; border-bottom-color:#4F46E5; }
.ref-stat { background:#fff; border:1px solid #E2E8F0; border-radius:10px; padding:16px 20px; }
.ref-stat .label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.ref-stat .value { font-size:24px; font-weight:800; color:#0F172A; }
.ref-stat .sub   { font-size:12px; color:#94A3B8; margin-top:4px; }
.badge-role-mgr  { background:#EFF6FF; color:#1D4ED8; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; }
.badge-role-aff  { background:#F0FDF4; color:#15803D; border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; }
.copy-btn { background:#EEF2FF; color:#4338CA; border:none; border-radius:5px; padding:3px 10px; font-size:11px; cursor:pointer; font-weight:600; }
.copy-btn:hover { background:#4F46E5; color:#fff; }
</style>

<div class="page-header">
    <div>
        <h1>🔗 Referral System</h1>
        <p>Track referral links, signups, and commissions across the platform</p>
    </div>
    <a href="/admin/settings?tab=commission" class="btn btn-secondary">⚙ Commission Settings</a>
</div>

<!-- Summary stats -->
<?php
$totalSignups = Database::fetchOne("SELECT COUNT(*) as c FROM referral_signups")['c'] ?? 0;
$totalCodes   = Database::fetchOne("SELECT COUNT(*) as c FROM referral_codes")['c'] ?? 0;
$totalComm    = Database::fetchOne("SELECT COALESCE(SUM(commission_amount),0) as a FROM referral_commissions WHERE status='approved'")['a'] ?? 0;
$pendingComm  = Database::fetchOne("SELECT COUNT(*) as c FROM referral_commissions WHERE status='pending'")['c'] ?? 0;
$commRate     = Config::get('config','app.refer_commission_rate') ?? '5';
$commType     = Config::get('config','app.refer_commission_type') ?? 'percent';
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:24px">
    <div class="ref-stat">
        <div class="label">Total Referral Links</div>
        <div class="value"><?= number_format($totalCodes) ?></div>
        <div class="sub">Active codes</div>
    </div>
    <div class="ref-stat">
        <div class="label">Referred Signups</div>
        <div class="value"><?= number_format($totalSignups) ?></div>
        <div class="sub">Via referral links</div>
    </div>
    <div class="ref-stat">
        <div class="label">Total Commissions Paid</div>
        <div class="value" style="color:#10B981">$<?= number_format($totalComm, 2) ?></div>
        <div class="sub"><?= $pendingComm ?> pending</div>
    </div>
    <div class="ref-stat">
        <div class="label">Current Rate</div>
        <div class="value" style="color:#4F46E5"><?= Helpers::e($commRate) ?><?= $commType==='percent'?'%':' USD' ?></div>
        <div class="sub"><?= $commType==='percent'?'% of each payout':'Fixed per conversion' ?></div>
    </div>
</div>

<!-- Tabs -->
<div class="ref-tabs">
    <a href="?tab=signups"     class="ref-tab <?= $tab==='signups'?'active':'' ?>">📋 Referred Signups</a>
    <a href="?tab=commissions" class="ref-tab <?= $tab==='commissions'?'active':'' ?>">💰 Commissions</a>
    <a href="?tab=codes"       class="ref-tab <?= $tab==='codes'?'active':'' ?>">🔗 Referral Codes</a>
</div>

<?php /* ══ TAB: SIGNUPS ══ */ ?>
<?php if ($tab === 'signups'): ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Referred Signups</span>
        <span class="text-muted text-sm"><?= number_format(count($signups)) ?> total</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-signups">
            <thead>
                <tr>
                    <th>Referrer</th><th>Role</th><th>Referred Affiliate</th>
                    <th>Referred Status</th><th>Balance</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($signups)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No referrals yet</td></tr>
            <?php else: foreach ($signups as $s): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($s['referrer_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($s['referrer_email']) ?></div>
                </td>
                <td>
                    <?php if ($s['referrer_role']==='affiliate_manager'): ?>
                    <span class="badge-role-mgr">Manager</span>
                    <?php else: ?>
                    <span class="badge-role-aff">Affiliate</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($s['referred_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($s['referred_email']) ?> · <code style="font-size:10px"><?= Helpers::e($s['referred_aff_code']) ?></code></div>
                </td>
                <td>
                    <span class="badge badge-<?= $s['referred_status']==='active'?'success':($s['referred_status']==='pending'?'warning':'muted') ?>">
                        <?= $s['referred_status'] ?>
                    </span>
                </td>
                <td class="fw-bold">$<?= number_format($s['referred_balance'],2) ?></td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ══ TAB: COMMISSIONS ══ */ ?>
<?php elseif ($tab === 'commissions'): ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:20px">
    <div class="ref-stat"><div class="label">Total Commissions</div><div class="value"><?= number_format((int)($commTotals['total'] ?? 0)) ?></div></div>
    <div class="ref-stat"><div class="label">Approved</div><div class="value" style="color:#10B981"><?= number_format((int)($commTotals['approved'] ?? 0)) ?></div></div>
    <div class="ref-stat"><div class="label">Pending</div><div class="value" style="color:#F59E0B"><?= number_format((int)($commTotals['pending'] ?? 0)) ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Referral Commissions</span>
        <span class="text-muted text-sm">Total paid: <strong style="color:#10B981">$<?= number_format($commTotals['amount'],2) ?></strong></span>
    </div>
    <div class="table-wrap">
        <table id="tbl-comm">
            <thead>
                <tr><th>Referrer</th><th>Referred Affiliate</th><th>Base Payout</th><th>Rate</th><th>Commission</th><th>Status</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (empty($commissions)): ?>
            <tr><td colspan="8" class="text-center text-muted" style="padding:32px">No commissions recorded yet</td></tr>
            <?php else: foreach ($commissions as $c): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($c['referrer_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><code><?= Helpers::e($c['referrer_code']) ?></code></div>
                </td>
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
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <?php if ($c['status']==='pending'): ?>
                    <div style="display:flex;gap:4px">
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="approve_commission">
                            <input type="hidden" name="commission_id" value="<?= $c['id'] ?>">
                            <button class="btn btn-success btn-sm">✓ Approve</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="reject_commission">
                            <input type="hidden" name="commission_id" value="<?= $c['id'] ?>">
                            <button class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </div>
                    <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ══ TAB: CODES ══ */ ?>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Referral Codes</span>
        <span class="text-muted text-sm"><?= number_format(count($codes)) ?> codes</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-codes">
            <thead>
                <tr><th>User</th><th>Role</th><th>Code</th><th>Referral Link</th><th>Signups</th><th>Earned</th><th>Created</th></tr>
            </thead>
            <tbody>
            <?php if (empty($codes)): ?>
            <tr><td colspan="7" class="text-center text-muted" style="padding:32px">No referral codes generated yet</td></tr>
            <?php else: foreach ($codes as $c): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($c['user_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($c['email']) ?></div>
                </td>
                <td>
                    <?php if ($c['role']==='affiliate_manager'): ?>
                    <span class="badge-role-mgr">Manager</span>
                    <?php else: ?>
                    <span class="badge-role-aff">Affiliate</span>
                    <?php endif; ?>
                </td>
                <td><code style="background:#F1F5F9;padding:3px 8px;border-radius:5px;font-size:12px"><?= Helpers::e($c['code']) ?></code></td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px">
                        <code style="font-size:10px;color:#64748B;max-width:220px;overflow:hidden;text-overflow:ellipsis;display:inline-block;white-space:nowrap">
                            <?= Helpers::e($appUrl.'/register?ref='.$c['code']) ?>
                        </code>
                        <button class="copy-btn" onclick="copyRef('<?= Helpers::e($appUrl.'/register?ref='.$c['code']) ?>', this)">Copy</button>
                    </div>
                </td>
                <td class="fw-bold"><?= number_format($c['signup_count']) ?></td>
                <td class="fw-bold" style="color:#10B981">$<?= number_format($c['total_earned'],2) ?></td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
// Suppress DataTables alert popups — show errors in console only
if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';

function copyRef(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        btn.textContent = '✓ Copied!';
        btn.style.background = '#10B981'; btn.style.color = '#fff';
        setTimeout(function(){ btn.textContent = 'Copy'; btn.style.background = ''; btn.style.color = ''; }, 2000);
    }).catch(function() {
        var ta = document.createElement('textarea'); ta.value = url;
        document.body.appendChild(ta); ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = 'Copy'; }, 2000);
    });
}

$(function() {
    if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';
    // Only initialise the table that is actually rendered on the current tab
    ['tbl-signups','tbl-comm','tbl-codes'].forEach(function(id) {
        var $t = $('#' + id);
        if ($t.length && $t.find('thead th').length > 0) {
            try {
                $t.DataTable({
                    destroy: true,
                    pageLength: 25,
                    order: [],
                    language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries',
                                emptyTable: 'No records found.' }
                });
            } catch(e) {}
        }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
