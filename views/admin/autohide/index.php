<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
/* ── Autohide page responsive overrides ─────────────────────────── */

/* Stats grids: collapse to 2 cols on tablet, 1 col on mobile */
.ah-grid-4 { display:grid; gap:16px; grid-template-columns:repeat(4,1fr); }
.ah-grid-3 { display:grid; gap:16px; grid-template-columns:repeat(3,1fr); }

@media(max-width:900px){
    .ah-grid-4 { grid-template-columns:repeat(2,1fr); }
    .ah-grid-3 { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:560px){
    .ah-grid-4,
    .ah-grid-3 { grid-template-columns:1fr; }
}

/* Create-rule form grids */
.ah-form-3 { display:grid; gap:16px; grid-template-columns:1fr 1fr 1fr; margin-bottom:16px; }
.ah-form-2 { display:grid; gap:16px; grid-template-columns:1fr 1fr; margin-bottom:16px; }

@media(max-width:860px){
    .ah-form-3 { grid-template-columns:1fr 1fr; }
}
@media(max-width:560px){
    .ah-form-3,
    .ah-form-2 { grid-template-columns:1fr; }
}

/* Card header: allow date-range label to wrap */
.ah-card-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:8px;
}
.ah-card-head .text-muted { font-size:12px; }

/* Date-filter form */
.ah-date-form {
    display:flex;
    align-items:flex-end;
    gap:12px;
    flex-wrap:wrap;
}
.ah-date-form .form-group { flex:1; min-width:140px; margin-bottom:0; }

/* Manual-hide form */
.ah-hide-form {
    display:flex;
    align-items:flex-end;
    gap:12px;
    flex-wrap:wrap;
}
.ah-hide-form .form-group { flex:1; min-width:200px; margin-bottom:0; }

/* Tab bar: scroll on very narrow screens */
.ah-tabs {
    display:flex;
    gap:4px;
    flex-wrap:wrap;
    margin-bottom:16px;
    border-bottom:2px solid #E2E8F0;
    padding-bottom:0;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

/* Table: enforce horizontal scroll and sensible min-widths */
.ah-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
#tbl-hidden th, #tbl-hidden td,
#tbl-rules  th, #tbl-rules  td  { white-space:nowrap; }

/* Stat cards inside ah-grid */
.ah-grid-4 .stat-card,
.ah-grid-3 .stat-card { margin:0; }

/* Alert banner: shrink on mobile */
@media(max-width:560px){
    .ah-alert { font-size:12px; }
}

/* DataTables scrollX wrapper fix inside card */
.dataTables_scrollBody { overflow-x:auto !important; }
div.dataTables_wrapper { overflow-x:auto; }
</style>

<div class="page-header">
    <div>
        <h1>&#128683; Auto Hide Conversions</h1>
        <p>Automatically hide conversions from affiliates and managers based on rules</p>
    </div>
</div>

<!-- Summary cards -->
<div class="ah-grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon red">&#128683;</div>
        <div class="stat-label">Total Hidden (All Time)</div>
        <div class="stat-value"><?= number_format($totalHiddenAll) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#128176;</div>
        <div class="stat-label">Payout Saved (All Time)</div>
        <div class="stat-value">$<?= number_format($totalHiddenPaid, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">&#9881;</div>
        <div class="stat-label">Active Rules</div>
        <div class="stat-value"><?= number_format($activeRules) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">&#128736;</div>
        <div class="stat-label">Total Rules</div>
        <div class="stat-value"><?= number_format(count($rules)) ?></div>
    </div>
</div>

<!-- Alert banner -->
<div class="ah-alert" style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
    <span style="font-size:20px;flex-shrink:0">&#9888;</span>
    <div style="font-size:13px;color:#92400E">
        <strong>Admin Only Feature</strong> — Hidden conversions are <strong>invisible</strong> to affiliates and managers.
        Their dashboards, reports, and payouts will not include hidden conversions.
        Only the admin panel shows and manages hidden conversions.
    </div>
</div>

<!-- Tab bar -->
<?php
$tabs = ['rules'=>'&#9881; Rules', 'hidden'=>'&#128683; Hidden Conversions', 'create'=>'&#43; New Rule'];
?>
<div class="ah-tabs">
    <?php foreach ($tabs as $key => $label):
        $isActive = $tab === $key;
        $href = $key === 'create' ? '#' : '?tab='.$key;
        $onclick = $key === 'create' ? ' onclick="document.getElementById(\'create-form\').scrollIntoView({behavior:\'smooth\'});return false;"' : '';
    ?>
    <a href="<?= $href ?>"<?= $onclick ?>
       style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;border:1px solid <?= $isActive?'#E2E8F0':'transparent' ?>;border-bottom:<?= $isActive?'2px solid #fff':'2px solid transparent' ?>;margin-bottom:-2px;background:<?= $isActive?'#fff':'transparent' ?>;color:<?= $isActive?'var(--primary)':'var(--text-muted)' ?>;text-decoration:none">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3"><?php foreach ($errors as $e) echo '<div>'.Helpers::e($e).'</div>'; ?></div>
<?php endif; ?>


<?php /* ═══════════════ RULES TAB ═══════════════ */ ?>
<?php if ($tab === 'rules'): ?>

<div class="card mb-3">
    <div class="card-header"><span class="card-title">Auto-Hide Rules</span></div>
    <div class="ah-table-wrap">
        <table id="tbl-rules" style="font-size:13px">
            <thead>
                <tr>
                    <th>Rule Name</th><th>Type</th><th>Target</th><th>Hide %</th>
                    <th>Reason</th><th>Status</th><th>Created</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rules)): ?>
            <tr class="dt-empty-row"><td colspan="8" class="text-center text-muted" style="padding:32px">No rules yet. <a href="#create-form" onclick="document.getElementById('create-form').scrollIntoView({behavior:'smooth'});return false;">Create your first rule &#8595;</a></td></tr>
            <?php else: foreach ($rules as $r): ?>
            <tr style="opacity:<?= $r['is_active'] ? 1 : .5 ?>">
                <td class="fw-bold"><?= Helpers::e($r['name']) ?></td>
                <td>
                    <?php $typeColors = ['global'=>'var(--danger)','offer'=>'var(--primary)','affiliate'=>'#8B5CF6']; ?>
                    <span class="badge" style="background:<?= $typeColors[$r['type']] ?? '#94A3B8' ?>;color:#fff"><?= ucfirst($r['type']) ?></span>
                </td>
                <td>
                    <?php if ($r['type'] === 'offer'): ?>
                        <span class="text-sm"><?= Helpers::e($r['offer_name'] ?? '—') ?></span>
                    <?php elseif ($r['type'] === 'affiliate'): ?>
                        <span class="text-sm"><?= Helpers::e($r['aff_name'] ?? '—') ?></span>
                        <?php if ($r['affiliate_code']): ?><br><code style="font-size:10px;background:#F1F5F9;padding:1px 4px;border-radius:3px"><?= Helpers::e($r['affiliate_code']) ?></code><?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted text-sm">All Conversions</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size:20px;font-weight:800;color:<?= $r['hide_percent'] >= 50 ? 'var(--danger)' : ($r['hide_percent'] >= 20 ? '#F59E0B' : 'var(--secondary)') ?>">
                        <?= number_format($r['hide_percent'], 1) ?>%
                    </span>
                </td>
                <td class="text-sm text-muted"><?= Helpers::e($r['reason'] ?: '—') ?></td>
                <td>
                    <?php if ($r['is_active']): ?>
                    <span class="badge badge-success">&#9679; Active</span>
                    <?php else: ?>
                    <span class="badge badge-muted">&#9675; Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-sm <?= $r['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                            <?= $r['is_active'] ? 'Disable' : 'Enable' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this rule? Hidden conversions will not be restored.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Rule Form -->
<div class="card" id="create-form">
    <div class="card-header" style="background:linear-gradient(135deg,#1E1B4B,#4F46E5);color:#fff">
        <span class="card-title" style="color:#fff">&#43; Create New Auto-Hide Rule</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="tab" value="rules">

            <div class="ah-form-3">
                <div class="form-group mb-0">
                    <label>Rule Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Hide 20% - Offer XYZ" required>
                </div>
                <div class="form-group mb-0">
                    <label>Rule Type <span style="color:var(--danger)">*</span></label>
                    <select name="type" class="form-control" id="ruleType" onchange="updateRuleType(this.value)">
                        <option value="global">Global — all conversions</option>
                        <option value="offer">By Offer</option>
                        <option value="affiliate">By Affiliate</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Hide Percentage <span style="color:var(--danger)">*</span></label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="number" name="hide_percent" class="form-control" id="hideRange"
                               min="0.01" max="100" step="0.01" value="10"
                               oninput="document.getElementById('hidePctDisplay').textContent=this.value+'%'"
                               style="width:90px">
                        <input type="range" min="1" max="100" step="1" value="10" style="flex:1"
                               oninput="document.getElementById('hideRange').value=this.value;document.getElementById('hidePctDisplay').textContent=this.value+'%'">
                        <span id="hidePctDisplay" style="font-weight:700;color:var(--danger);min-width:40px">10%</span>
                    </div>
                    <div class="text-muted" style="font-size:11px;margin-top:4px">% of incoming conversions that will be hidden</div>
                </div>
            </div>

            <div class="ah-form-2">
                <div class="form-group mb-0" id="offerRow" style="display:none">
                    <label>Select Offer</label>
                    <select name="offer_id" class="form-control">
                        <option value="">— Select offer —</option>
                        <?php foreach ($offerList as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0" id="affRow" style="display:none">
                    <label>Select Affiliate</label>
                    <select name="affiliate_id" class="form-control">
                        <option value="">— Select affiliate —</option>
                        <?php foreach ($affList as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Internal Reason <span class="text-muted" style="font-size:11px">(admin only, not shown to affiliates)</span></label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g. Quality control, high fraud rate, advertiser request...">
                </div>
            </div>

            <!-- Retroactive apply -->
            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px;margin-bottom:16px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
                    <input type="checkbox" name="apply_existing" value="1" style="margin-top:2px">
                    <div>
                        <span class="fw-bold" style="font-size:14px">Apply to existing conversions retroactively</span>
                        <div class="text-muted" style="font-size:12px;margin-top:2px">
                            Will hide past conversions matching this rule at the set percentage.
                            Affiliate balances will be adjusted. <span style="color:var(--danger)">This cannot be undone automatically.</span>
                        </div>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Create Auto-Hide Rule</button>
        </form>
    </div>
</div>


<?php /* ═══════════════ HIDDEN CONVERSIONS TAB ═══════════════ */ ?>
<?php elseif ($tab === 'hidden'): ?>

<!-- Date filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="ah-date-form">
            <input type="hidden" name="tab" value="hidden">
            <div class="form-group">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($hiddenFrom) ?>">
            </div>
            <div class="form-group">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($hiddenTo) ?>">
            </div>
            <div>
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="ah-grid-3 mb-3">
    <div class="stat-card">
        <div class="stat-label">Hidden in Period</div>
        <div class="stat-value" style="color:var(--danger)"><?= number_format($hiddenTotal) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Payout Saved</div>
        <div class="stat-value" style="color:var(--secondary)">$<?= number_format($hiddenPayout, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">All Time Hidden</div>
        <div class="stat-value"><?= number_format($totalHiddenAll) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header ah-card-head">
        <span class="card-title">&#128683; Hidden Conversions</span>
        <span class="text-muted"><?= Helpers::e($hiddenFrom) ?> → <?= Helpers::e($hiddenTo) ?> &bull; Affiliates &amp; managers cannot see these</span>
    </div>
    <div class="ah-table-wrap">
        <table id="tbl-hidden" style="font-size:12px">
            <thead>
                <tr>
                    <th>CONVERSION ID</th>
                    <th>OFFER</th>
                    <th>AFFILIATE</th>
                    <th>PAYOUT</th>
                    <th>STATUS</th>
                    <th>HIDE REASON</th>
                    <th>CONVERTED AT</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($hiddenConversions)): ?>
            <tr class="dt-empty-row"><td colspan="8" class="text-center text-muted" style="padding:32px">No hidden conversions in this period</td></tr>
            <?php else: foreach ($hiddenConversions as $cv):
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr style="background:#FFF5F5">
                <td>
                    <code style="font-size:10px;background:#F1F5F9;padding:1px 5px;border-radius:3px"><?= Helpers::e(substr($cv['conversion_id'],0,18)).'…' ?></code>
                </td>
                <td class="fw-bold"><?= Helpers::e($cv['offer_name']) ?></td>
                <td>
                    <div><?= Helpers::e($cv['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($cv['affiliate_code']) ?></div>
                </td>
                <td class="fw-bold">$<?= number_format($cv['payout'], 2) ?></td>
                <td>
                    <span class="badge badge-<?= $bm[$cv['status']] ?? 'muted' ?>"><?= $cv['status'] ?></span>
                    <span class="badge badge-danger" style="margin-left:2px">hidden</span>
                </td>
                <td class="text-sm text-muted"><?= Helpers::e($cv['hide_reason'] ?: '—') ?></td>
                <td class="text-muted" style="white-space:nowrap"><?= date('M j, Y H:i', strtotime($cv['converted_at'])) ?></td>
                <td>
                    <!-- Unhide -->
                    <form method="POST" style="display:inline" onsubmit="return confirm('Unhide this conversion? Affiliate balance will be restored if approved.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="unhide">
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($cv['conversion_id']) ?>">
                        <button class="btn btn-sm btn-success">Unhide</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Manual hide form -->
<div class="card mt-3">
    <div class="card-header"><span class="card-title">Manually Hide a Conversion</span></div>
    <div class="card-body">
        <form method="POST" class="ah-hide-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="hide_one">
            <div class="form-group" style="flex:2;min-width:240px">
                <label>Conversion ID (UUID)</label>
                <input type="text" name="conversion_id" class="form-control" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
            </div>
            <div class="form-group" style="min-width:200px">
                <label>Reason <span class="text-muted" style="font-size:11px">(admin only)</span></label>
                <input type="text" name="reason" class="form-control" placeholder="Manual hide reason…">
            </div>
            <div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Hide this conversion from affiliate view?')">Hide Conversion</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
function updateRuleType(type) {
    document.getElementById('offerRow').style.display = type === 'offer'     ? '' : 'none';
    document.getElementById('affRow').style.display   = type === 'affiliate' ? '' : 'none';
}
$.fn.dataTable.ext.errMode = 'none';
$(function() {
    $('#tbl-rules .dt-empty-row').remove();
    $('#tbl-rules').DataTable({
        destroy:true, pageLength:25, order:[[6,'desc']], scrollX:true, autoWidth:false,
        language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No rules yet.'}
    });
    $('#tbl-hidden .dt-empty-row').remove();
    $('#tbl-hidden').DataTable({
        destroy:true, pageLength:50, order:[[6,'desc']], scrollX:true, autoWidth:false,
        language:{search:'Search:',lengthMenu:'Show _MENU_ entries',emptyTable:'No hidden conversions in this period.'},
        columnDefs:[
            {targets:0, width:'170px'},
            {targets:1, width:'120px'},
            {targets:2, width:'140px'},
            {targets:3, width:'80px'},
            {targets:4, width:'140px'},
            {targets:5, width:'130px'},
            {targets:6, width:'140px'},
            {targets:7, width:'90px'}
        ]
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
