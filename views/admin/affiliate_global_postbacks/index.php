<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.gpb-tabs { display:flex;gap:0;border-bottom:2px solid #E2E8F0;margin-bottom:22px;flex-wrap:wrap; }
.gpb-tab  { padding:9px 20px;font-size:13px;font-weight:600;color:#64748B;text-decoration:none;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;white-space:nowrap;display:flex;align-items:center;gap:6px; }
.gpb-tab:hover  { color:#334155;text-decoration:none; }
.gpb-tab.active { color:#4F46E5;border-bottom-color:#4F46E5; }
.gpb-cnt { border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700; }
.gpb-cnt-pending  { background:#FEF3C7;color:#92400E; }
.gpb-cnt-approved { background:#D1FAE5;color:#065F46; }
.gpb-cnt-rejected { background:#FEE2E2;color:#991B1B; }
.gpb-cnt-all      { background:#E2E8F0;color:#475569; }

.gpb-pill { display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:4px 12px;font-size:11px;font-weight:700;white-space:nowrap; }
.gpb-pill-pending  { background:#FEF3C7;color:#92400E; }
.gpb-pill-approved { background:#D1FAE5;color:#065F46; }
.gpb-pill-rejected { background:#FEE2E2;color:#991B1B; }
.gpb-pill-active   { background:#D1FAE5;color:#065F46; }
.gpb-pill-inactive { background:#F1F5F9;color:#64748B; }

.gpb-table     { width:100%;border-collapse:collapse;font-size:13px; }
.gpb-table th  { padding:10px 16px;text-align:left;font-size:10px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.05em;background:#F8FAFC;border-bottom:2px solid #E2E8F0;white-space:nowrap; }
.gpb-table td  { padding:12px 16px;border-bottom:1px solid #F1F5F9;vertical-align:middle; }
.gpb-table tr:last-child td { border-bottom:none; }
.gpb-table tr.row-pending  td { background:#FFFDF0; }
.gpb-table tr.row-rejected td { background:#FFF8F8; }
.gpb-table tr:hover td { filter:brightness(.985); }

.gpb-url-cell { font-family:monospace;font-size:11px;color:#334155;word-break:break-all;line-height:1.5;max-width:320px; }
.gpb-actions  { display:flex;flex-wrap:wrap;gap:5px;align-items:center; }

/* Reject modal */
.gpb-modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center; }
.gpb-modal-overlay.open { display:flex; }
.gpb-modal { background:#fff;border-radius:12px;padding:28px 30px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2); }
.gpb-modal-title { font-size:16px;font-weight:800;color:#1E293B;margin-bottom:6px; }
.gpb-modal-sub   { font-size:13px;color:#64748B;margin-bottom:18px; }
.gpb-modal textarea { width:100%;padding:10px 12px;border:1.5px solid #CBD5E1;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;margin-bottom:14px; }
.gpb-modal textarea:focus { outline:none;border-color:#EF4444;box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.gpb-modal-btns { display:flex;gap:8px;justify-content:flex-end; }

.gpb-empty { text-align:center;padding:52px 24px;color:#94A3B8; }
.gpb-empty-icon  { font-size:36px;margin-bottom:10px; }
.gpb-empty-title { font-size:15px;font-weight:700;color:#CBD5E1;margin-bottom:4px; }
</style>

<div class="page-header">
    <div>
        <h1>🌐 Global Postback Management</h1>
        <p>Review and control every affiliate's global postback URL — approve, reject, activate, deactivate, or delete.</p>
    </div>
    <?php if (($counts['pending'] ?? 0) > 0): ?>
    <span style="background:#FEF3C7;color:#92400E;border-radius:20px;padding:6px 16px;font-size:13px;font-weight:700">
        ⏳ <?= $counts['pending'] ?> pending review
    </span>
    <?php endif; ?>
</div>

<?php $flash = Helpers::getFlash(); if (!empty($flash['success'])): ?>
<div class="alert alert-success mb-3">✓ <?= Helpers::e($flash['success']) ?></div>
<?php elseif (!empty($flash['error'])): ?>
<div class="alert alert-danger mb-3">⚠ <?= Helpers::e($flash['error']) ?></div>
<?php endif; ?>

<!-- ── Status tabs ── -->
<div class="gpb-tabs">
    <a href="?status=all"      class="gpb-tab <?= $filterStatus==='all'?'active':'' ?>">
        All <span class="gpb-cnt gpb-cnt-all"><?= $counts['all'] ?></span>
    </a>
    <a href="?status=pending"  class="gpb-tab <?= $filterStatus==='pending'?'active':'' ?>">
        ⏳ Pending <span class="gpb-cnt gpb-cnt-pending"><?= $counts['pending'] ?></span>
    </a>
    <a href="?status=approved" class="gpb-tab <?= $filterStatus==='approved'?'active':'' ?>">
        ● Approved <span class="gpb-cnt gpb-cnt-approved"><?= $counts['approved'] ?></span>
    </a>
    <a href="?status=rejected" class="gpb-tab <?= $filterStatus==='rejected'?'active':'' ?>">
        ✕ Rejected <span class="gpb-cnt gpb-cnt-rejected"><?= $counts['rejected'] ?></span>
    </a>
</div>

<!-- ── Filter bar ── -->
<?php if (!empty($affiliateList)): ?>
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <input type="hidden" name="status" value="<?= Helpers::e($filterStatus) ?>">
            <div>
                <label style="font-size:11px;font-weight:700;color:#64748B;display:block;margin-bottom:3px">Filter by Affiliate</label>
                <select name="aff" class="form-control" style="min-width:220px">
                    <option value="">All Affiliates</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $filterAffId==(int)$a['id']?'selected':'' ?>>
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
<?php endif; ?>

<!-- ── Main table ── -->
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">Affiliate Global Postback URLs</span>
        <span class="text-muted text-sm"><?= count($rows) ?> result<?= count($rows)!==1?'s':'' ?></span>
    </div>
    <div style="overflow-x:auto">
        <table class="gpb-table" id="gpb-tbl">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Global Postback URL</th>
                    <th>Admin Status</th>
                    <th>Live Status</th>
                    <th>Submitted</th>
                    <th>Reviewed</th>
                    <th style="min-width:200px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7">
                <div class="gpb-empty">
                    <div class="gpb-empty-icon">📭</div>
                    <div class="gpb-empty-title">No global postback URLs found</div>
                    <div style="font-size:12px">
                        <?php if ($filterStatus === 'pending'): ?>
                        No pending postbacks to review.
                        <?php elseif ($filterStatus === 'approved'): ?>
                        No approved postbacks yet.
                        <?php elseif ($filterStatus === 'rejected'): ?>
                        No rejected postbacks.
                        <?php else: ?>
                        No affiliates have set a global postback URL yet.
                        <?php endif; ?>
                    </div>
                </div>
            </td></tr>
            <?php else: foreach ($rows as $row):
                $adminStatus = $row['global_pb_admin_status'] ?? 'pending';
                $isActive    = (int)($row['global_pb_active'] ?? 0);
                $rowClass    = match($adminStatus) {
                    'pending'  => 'row-pending',
                    'rejected' => 'row-rejected',
                    default    => '',
                };
            ?>
            <tr class="<?= $rowClass ?>" id="gpb-row-<?= (int)$row['aff_id'] ?>">

                <!-- Affiliate -->
                <td>
                    <div style="font-weight:700;color:#1E293B"><?= Helpers::e($row['aff_name']) ?></div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:2px">
                        <code><?= Helpers::e($row['affiliate_code']) ?></code>
                    </div>
                    <div style="font-size:11px;color:#94A3B8"><?= Helpers::e($row['aff_email']) ?></div>
                </td>

                <!-- URL -->
                <td>
                    <div class="gpb-url-cell" title="<?= Helpers::e($row['global_postback_url']) ?>">
                        <?= Helpers::e($row['global_postback_url']) ?>
                    </div>
                    <?php if (!empty($row['global_pb_admin_note'])): ?>
                    <div style="font-size:11px;color:#EF4444;margin-top:4px;background:#FEF2F2;border-radius:4px;padding:3px 7px;display:inline-block">
                        Admin note: <?= Helpers::e($row['global_pb_admin_note']) ?>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Admin Status -->
                <td>
                    <span class="gpb-pill gpb-pill-<?= Helpers::e($adminStatus) ?>">
                        <?php echo match($adminStatus) {
                            'pending'  => '⏳ Pending',
                            'approved' => '✓ Approved',
                            'rejected' => '✕ Rejected',
                            default    => '? Unknown',
                        }; ?>
                    </span>
                </td>

                <!-- Live Status (only meaningful when approved) -->
                <td>
                    <?php if ($adminStatus === 'approved'): ?>
                    <span class="gpb-pill <?= $isActive ? 'gpb-pill-active' : 'gpb-pill-inactive' ?>">
                        <?= $isActive ? '● Firing' : '○ Paused' ?>
                    </span>
                    <?php else: ?>
                    <span style="color:#CBD5E1;font-size:12px">—</span>
                    <?php endif; ?>
                </td>

                <!-- Submitted -->
                <td style="font-size:11px;color:#64748B;white-space:nowrap">
                    <?= $row['global_pb_submitted_at'] ? date('M j Y, H:i', strtotime($row['global_pb_submitted_at'])) : '—' ?>
                </td>

                <!-- Reviewed -->
                <td style="font-size:11px;color:#64748B;white-space:nowrap">
                    <?= $row['global_pb_reviewed_at'] ? date('M j Y, H:i', strtotime($row['global_pb_reviewed_at'])) : '—' ?>
                </td>

                <!-- Actions -->
                <td>
                    <div class="gpb-actions">

                        <?php if ($adminStatus === 'pending'): ?>
                        <!-- Approve -->
                        <form method="POST">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action"  value="approve">
                            <input type="hidden" name="aff_id"  value="<?= (int)$row['aff_id'] ?>">
                            <button class="btn btn-success btn-sm"
                                    onclick="return confirm('Approve this global postback URL?')">
                                ✓ Approve
                            </button>
                        </form>
                        <!-- Reject -->
                        <button class="btn btn-danger btn-sm"
                                onclick="openRejectModal(<?= (int)$row['aff_id'] ?>, '<?= Helpers::e(addslashes($row['aff_name'])) ?>')">
                            ✕ Reject
                        </button>

                        <?php elseif ($adminStatus === 'approved'): ?>
                        <!-- Toggle active/inactive -->
                        <form method="POST">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action"  value="toggle">
                            <input type="hidden" name="aff_id"  value="<?= (int)$row['aff_id'] ?>">
                            <button class="btn btn-secondary btn-sm">
                                <?= $isActive ? '⏸ Deactivate' : '▶ Activate' ?>
                            </button>
                        </form>
                        <!-- Reject (demote approved → rejected) -->
                        <button class="btn btn-danger btn-sm"
                                onclick="openRejectModal(<?= (int)$row['aff_id'] ?>, '<?= Helpers::e(addslashes($row['aff_name'])) ?>')">
                            ✕ Reject
                        </button>

                        <?php elseif ($adminStatus === 'rejected'): ?>
                        <!-- Re-approve -->
                        <form method="POST">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action"  value="reapprove">
                            <input type="hidden" name="aff_id"  value="<?= (int)$row['aff_id'] ?>">
                            <button class="btn btn-success btn-sm"
                                    onclick="return confirm('Re-approve this global postback URL?')">
                                ↩ Re-approve
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Delete (always available) -->
                        <form method="POST" onsubmit="return confirm('Delete this affiliate\'s global postback URL permanently? The affiliate will need to resubmit it.')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action"  value="delete">
                            <input type="hidden" name="aff_id"  value="<?= (int)$row['aff_id'] ?>">
                            <button class="btn btn-danger btn-sm" title="Delete URL permanently">🗑</button>
                        </form>

                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── How it works info panel ── -->
<div class="card mt-3" style="border-left:4px solid #4F46E5">
    <div class="card-header"><span class="card-title">ℹ️ How Global Postback Control Works</span></div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;padding:16px 20px">
        <?php foreach ([
            ['⏳ Pending',    '#FEF3C7', '#92400E', 'Affiliate submitted a new or changed URL. It will NOT fire until you approve it.'],
            ['✓ Approved',    '#D1FAE5', '#065F46', 'URL is approved. It fires on every conversion as long as Live Status is Firing.'],
            ['⏸ Deactivated', '#F1F5F9', '#475569', 'Approved but temporarily paused. No postbacks fire. Re-activate any time.'],
            ['✕ Rejected',    '#FEE2E2', '#991B1B', 'URL was rejected. Affiliate is notified with your reason. They can resubmit.'],
            ['🗑 Deleted',     '#FEF2F2', '#991B1B', 'URL removed entirely from the affiliate\'s account. Affiliate must resubmit.'],
        ] as [$label, $bg, $color, $desc]): ?>
        <div style="background:<?= $bg ?>;border-radius:8px;padding:12px 14px">
            <div style="font-weight:700;font-size:12px;color:<?= $color ?>;margin-bottom:4px"><?= $label ?></div>
            <div style="font-size:12px;color:#475569;line-height:1.5"><?= $desc ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Reject Modal ── -->
<div class="gpb-modal-overlay" id="gpb-reject-modal">
    <div class="gpb-modal">
        <div class="gpb-modal-title">✕ Reject Global Postback</div>
        <div class="gpb-modal-sub" id="gpb-modal-sub">Affiliate: <strong id="gpb-modal-aff-name"></strong></div>
        <form method="POST" id="gpb-reject-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action"   value="reject">
            <input type="hidden" name="aff_id"   id="gpb-modal-aff-id" value="">
            <label style="font-size:11px;font-weight:700;color:#64748B;display:block;margin-bottom:6px">
                Rejection Reason <span style="font-weight:400;color:#94A3B8">(optional — affiliate will see this)</span>
            </label>
            <textarea name="admin_note" id="gpb-modal-note" rows="3"
                      placeholder="e.g. URL is not reachable / invalid format / suspicious domain…"></textarea>
            <div class="gpb-modal-btns">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">✕ Reject Postback</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(affId, affName) {
    document.getElementById('gpb-modal-aff-id').value   = affId;
    document.getElementById('gpb-modal-aff-name').textContent = affName;
    document.getElementById('gpb-modal-note').value     = '';
    document.getElementById('gpb-reject-modal').classList.add('open');
    setTimeout(function(){ document.getElementById('gpb-modal-note').focus(); }, 50);
}
function closeRejectModal() {
    document.getElementById('gpb-reject-modal').classList.remove('open');
}
document.getElementById('gpb-reject-modal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeRejectModal();
});
$(function(){
    if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';
    var $t = $('#gpb-tbl');
    if ($t.length) {
        $t.DataTable({
            destroy: true,
            pageLength: 25,
            order: [],
            columnDefs: [{ orderable: false, targets: 6 }],
            language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries', emptyTable: 'No global postbacks found.' }
        });
    }
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
