<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.apb-tabs { display:flex;gap:0;border-bottom:2px solid #E2E8F0;margin-bottom:20px;flex-wrap:wrap; }
.apb-tab  { padding:9px 18px;font-size:13px;font-weight:600;color:#64748B;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.15s;white-space:nowrap; }
.apb-tab:hover  { color:#334155;text-decoration:none; }
.apb-tab.active { color:#4F46E5;border-bottom-color:#4F46E5; }
.status-pill { display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:700; }
.sp-pending  { background:#FEF3C7;color:#92400E; }
.sp-approved { background:#D1FAE5;color:#065F46; }
.sp-rejected { background:#FEE2E2;color:#991B1B; }
.sp-active   { background:#D1FAE5;color:#065F46; }
.sp-inactive { background:#F1F5F9;color:#64748B; }
.url-cell { font-family:monospace;font-size:11px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;display:block; }
.edit-form-row { display:none;background:#F8FAFC;border-top:1px solid #E2E8F0; }
.edit-form-row.open { display:table-row; }
/* Edit panel rendered as div outside table */
.edit-panel { display:none; background:#F0F4FF; border:1px solid #C7D2FE; border-radius:8px; padding:14px 16px; margin-top:6px; }
.edit-panel.open { display:block; }
</style>

<div class="page-header">
    <div>
        <h1>📡 Affiliate Postback Management</h1>
        <p>View, approve, reject, and manage every affiliate's postback URLs.</p>
    </div>
    <?php if ($pendingCount > 0): ?>
    <span style="background:#FEF3C7;color:#92400E;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:700">⏳ <?= $pendingCount ?> pending</span>
    <?php endif; ?>
</div>

<?php $flash = Helpers::getFlash(); if (!empty($flash['success'])): ?>
<div class="alert alert-success mb-3">✓ <?= Helpers::e($flash['success']) ?></div>
<?php endif; ?>

<!-- Status filter tabs -->
<div class="apb-tabs">
    <a href="?status=all<?= $filterAffId?'&aff='.$filterAffId:'' ?>"      class="apb-tab <?= $filterStatus==='all'?'active':'' ?>">All <span style="background:#E2E8F0;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $totalCount ?></span></a>
    <a href="?status=pending<?= $filterAffId?'&aff='.$filterAffId:'' ?>"  class="apb-tab <?= $filterStatus==='pending'?'active':'' ?>">⏳ Pending <span style="background:#FEF3C7;color:#92400E;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $pendingCount ?></span></a>
    <a href="?status=active<?= $filterAffId?'&aff='.$filterAffId:'' ?>"   class="apb-tab <?= $filterStatus==='active'?'active':'' ?>">● Active</a>
    <a href="?status=rejected<?= $filterAffId?'&aff='.$filterAffId:'' ?>" class="apb-tab <?= $filterStatus==='rejected'?'active':'' ?>">✕ Rejected</a>
</div>

<!-- Affiliate filter + Add form -->
<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;margin-bottom:20px;align-items:start">

    <!-- Filter by affiliate -->
    <div class="card">
        <div class="card-body" style="padding:12px 16px">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <input type="hidden" name="status" value="<?= Helpers::e($filterStatus) ?>">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748B;display:block;margin-bottom:3px">Filter by Affiliate</label>
                    <select name="aff" class="form-control" style="min-width:220px">
                        <option value="">All Affiliates</option>
                        <?php foreach ($affiliateList as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $filterAffId==$a['id']?'selected':'' ?>>
                            <?= Helpers::e($a['label']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if ($filterAffId): ?>
                <a href="?status=<?= Helpers::e($filterStatus) ?>" class="btn btn-secondary btn-sm">✕ Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Admin: Add postback for affiliate -->
    <div class="card">
        <div class="card-header"><span class="card-title">➕ Add Postback for Affiliate</span></div>
        <div class="card-body" style="padding:12px 16px">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="pb_admin_action" value="add">
                <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                <div style="display:flex;flex-direction:column;gap:8px">
                    <select name="affiliate_id" class="form-control" required>
                        <option value="">— Select Affiliate —</option>
                        <?php foreach ($affiliateList as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $filterAffId==$a['id']?'selected':'' ?>>
                            <?= Helpers::e($a['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="offer_id" class="form-control">
                        <option value="">All Offers (global)</option>
                        <?php foreach ($offerList as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="url" class="form-control" required
                           placeholder="https://tracker.com/pb?cid={click_id}&payout={payout}"
                           style="font-family:monospace;font-size:11px">
                    <div style="display:flex;gap:8px">
                        <select name="method" class="form-control" style="width:90px">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                        </select>
                        <select name="event" class="form-control">
                            <option value="conversion">Conversion</option>
                            <option value="click">Click</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Postbacks table -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Affiliate Postback URLs</span>
        <span class="text-muted text-sm"><?= count($postbacks) ?> results</span>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px" id="tbl-apb">
            <thead>
                <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Affiliate</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Offer</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Event</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Postback URL</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Status</th>
                    <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($postbacks)): ?>
            <tr><td colspan="6" style="padding:40px;text-align:center;color:#94A3B8">
                <div style="font-size:28px;margin-bottom:8px">📭</div>
                No postbacks found<?= $filterStatus!=='all'?' for this filter':'' ?>.
            </td></tr>
            <?php else: ?>
            <?php foreach ($postbacks as $pb):
                $adminStatus = $pb['admin_status'] ?? 'approved';
                $rowBg = match($adminStatus) {
                    'pending'  => '#FFFDF0',
                    'rejected' => '#FFF5F5',
                    default    => '#fff',
                };
            ?>
            <tr style="border-bottom:1px solid #F1F5F9;background:<?= $rowBg ?>" id="row-<?= $pb['id'] ?>">
                <td style="padding:10px 14px">
                    <div class="fw-bold"><?= Helpers::e($pb['aff_name']) ?></div>
                    <div style="font-size:11px;color:#94A3B8"><code><?= Helpers::e($pb['affiliate_code']) ?></code></div>
                </td>
                <td style="padding:10px 14px">
                    <div style="font-size:12px"><?= $pb['offer_name'] ? Helpers::e($pb['offer_name']) : '<span style="color:#94A3B8">Global</span>' ?></div>
                </td>
                <td style="padding:10px 14px">
                    <span class="badge badge-info" style="font-size:10px;text-transform:uppercase"><?= Helpers::e($pb['event']) ?></span>
                    <div style="font-size:10px;color:#94A3B8;margin-top:2px"><?= Helpers::e($pb['method']) ?></div>
                </td>
                <td style="padding:10px 14px;max-width:280px">
                    <span class="url-cell" title="<?= Helpers::e($pb['url']) ?>"><?= Helpers::e($pb['url']) ?></span>
                    <button type="button" onclick="toggleEdit(<?= $pb['id'] ?>)"
                            style="background:none;border:none;color:#4F46E5;font-size:10px;cursor:pointer;padding:2px 0;font-weight:600">
                        ✏ Edit URL
                    </button>
                </td>
                <td style="padding:10px 14px">
                    <span class="status-pill sp-<?= htmlspecialchars($adminStatus) ?>">
                        <?php echo match($adminStatus) {
                            'pending'  => '⏳ Pending',
                            'rejected' => '✕ Rejected',
                            default    => ($pb['status']==='active' ? '● Active' : '○ Paused'),
                        }; ?>
                    </span>
                    <?php if (!empty($pb['admin_note'])): ?>
                    <div style="font-size:10px;color:#EF4444;margin-top:2px" title="<?= Helpers::e($pb['admin_note']) ?>">
                        Note: <?= Helpers::e(substr($pb['admin_note'],0,30)) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($pb['last_fired_at']): ?>
                    <div style="font-size:10px;color:#94A3B8;margin-top:2px">Last: <?= date('M j H:i', strtotime($pb['last_fired_at'])) ?></div>
                    <?php endif; ?>
                </td>
                <td style="padding:10px 14px">
                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                        <?php if ($adminStatus === 'pending'): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_admin_action" value="approve">
                            <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                            <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                            <button class="btn btn-success btn-sm" onclick="return confirm('Approve this postback?')">✓ Approve</button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return rejectConfirm(this)">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_admin_action" value="reject">
                            <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                            <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                            <input type="hidden" name="admin_note" id="note-<?= $pb['id'] ?>" value="">
                            <button class="btn btn-danger btn-sm" type="submit">✕ Reject</button>
                        </form>
                        <?php elseif ($adminStatus === 'approved'): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_admin_action" value="toggle">
                            <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                            <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                            <button class="btn btn-secondary btn-sm"><?= $pb['status']==='active'?'⏸ Pause':'▶ Enable' ?></button>
                        </form>
                        <?php elseif ($adminStatus === 'rejected'): ?>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_admin_action" value="approve">
                            <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                            <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                            <button class="btn btn-secondary btn-sm" onclick="return confirm('Re-approve this postback?')">↩ Re-approve</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this postback permanently?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="pb_admin_action" value="delete">
                            <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                            <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                            <button class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <!-- Inline edit row — rendered as div OUTSIDE table to avoid DataTables column count error -->
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Edit panels outside the table so DataTables doesn't count their cells -->
        <?php if (!empty($postbacks)): foreach ($postbacks as $pb): ?>
        <div class="edit-panel" id="edit-<?= $pb['id'] ?>">
            <form method="POST" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="pb_admin_action" value="edit">
                <input type="hidden" name="pb_id" value="<?= $pb['id'] ?>">
                <input type="hidden" name="redirect_aff" value="<?= $filterAffId ?>">
                <div style="flex:1;min-width:260px">
                    <label style="font-size:11px;font-weight:700;color:#64748B;display:block;margin-bottom:3px">Edit Postback URL</label>
                    <input type="text" name="url" class="form-control" value="<?= Helpers::e($pb['url']) ?>"
                           style="font-family:monospace;font-size:11px" required>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#64748B;display:block;margin-bottom:3px">Method</label>
                    <select name="method" class="form-control" style="width:90px">
                        <option value="GET" <?= $pb['method']==='GET'?'selected':'' ?>>GET</option>
                        <option value="POST" <?= $pb['method']==='POST'?'selected':'' ?>>POST</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">💾 Save</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit(<?= $pb['id'] ?>)">Cancel</button>
            </form>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
function toggleEdit(id) {
    var panel = document.getElementById('edit-' + id);
    if (!panel) return;
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
function rejectConfirm(form) {
    var pbId = form.querySelector('[name=pb_id]').value;
    var note = prompt('Rejection reason (optional):', '');
    if (note === null) return false;
    document.getElementById('note-' + pbId).value = note;
    return true;
}
$(function(){
    if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';
    var $t = $('#tbl-apb');
    if ($t.length) {
        $t.DataTable({
            destroy: true,
            pageLength: 25,
            order: [],
            language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries',
                        emptyTable: 'No postbacks found.' }
        });
    }
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
