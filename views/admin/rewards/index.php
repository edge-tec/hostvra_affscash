<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
/* Scoped to /admin/rewards only — Shop-style modular layout */

/* ── Tabs ───────────────────────────────────────────────────────────── */
.rw-tab {
    display: inline-block;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    flex-shrink: 0;
}
.rw-tab.active {
    color: #4F46E5;
    border-color: #4F46E5;
}
.rw-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #E2E8F0;
    margin-bottom: 18px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* ── Filter Bar ─────────────────────────────────────────────────────── */
.rw-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 16px;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 10px 14px;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-filter input, .rw-filter select {
    height: 36px;
    font-size: 12.5px;
    padding: 4px 10px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    background: #fff;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-filter .grow { flex: 1 1 200px; min-width: 0; }

@media (max-width: 575px) {
    .rw-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px;
    }
    .rw-filter select,
    .rw-filter input,
    .rw-filter button,
    .rw-filter a {
        width: 100% !important;
        flex: 1 1 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
    }
}

/* ── Rules Table & Card Wrapper ──────────────────────────────────────── */
.rw-table-wrap {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    max-width: 100%;
    display: block;
    box-sizing: border-box;
}
.rw-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    max-width: 100%;
}
.rw-table thead th {
    background: #F8FAFC;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748B;
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid #E2E8F0;
}
.rw-table tbody td {
    padding: 12px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}
.rw-table tbody tr:hover td { background: #FAFAFC; }
.rw-table tbody tr.dragging { opacity: .45; }

.rw-handle {
    cursor: grab;
    color: #94A3B8;
    font-size: 18px;
    line-height: 1;
    user-select: none;
    padding: 0 4px;
}
.rw-handle:active { cursor: grabbing; }

.rw-thumb {
    width: 54px;
    height: 36px;
    border-radius: 6px;
    background: #F1F5F9 center/cover no-repeat;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94A3B8;
    font-size: 18px;
    flex-shrink: 0;
}

.rw-badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 99px;
    font-size: 10.5px;
    font-weight: 700;
    color: #fff;
    letter-spacing: .02em;
    text-transform: uppercase;
    max-width: 100%;
    word-break: break-word;
    white-space: nowrap;
}

.rw-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
}
.rw-toggle input { opacity: 0; width: 0; height: 0; }
.rw-toggle .t {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #CBD5E1;
    border-radius: 99px;
    transition: .18s;
}
.rw-toggle .k {
    position: absolute;
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .18s;
    box-shadow: 0 1px 2px rgba(0,0,0,.18);
}
.rw-toggle input:checked + .t { background: #10B981; }
.rw-toggle input:checked + .t .k { left: 19px; }

.rw-vis-tag {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 99px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.rw-vis-public   { background: #E0F2FE; color: #075985; }
.rw-vis-affiliate{ background: #EEF2FF; color: #3730A3; }
.rw-vis-vip      { background: #FEF3C7; color: #92400E; }
.rw-vis-private  { background: #F1F5F9; color: #475569; }

/* ── Mobile Responsive Card Layout (< 768px) ─────────────────────────── */
@media (max-width: 767px) {
    .rw-table-wrap {
        background: transparent !important;
        border: none !important;
        overflow-x: hidden !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }
    .rw-table { display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    .rw-table thead { display: none !important; }
    .rw-table tbody { display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    .rw-table tbody tr {
        display: block !important;
        background: #fff !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 12px !important;
        margin-bottom: 14px !important;
        padding: 12px 14px !important;
        box-shadow: 0 2px 8px rgba(15,23,42,.04) !important;
        box-sizing: border-box !important;
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    .rw-table tbody td {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 0 !important;
        border-bottom: 1px solid #F1F5F9 !important;
        font-size: 13px !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        gap: 8px !important;
        min-width: 0 !important;
    }
    .rw-table tbody td:last-child { border-bottom: none !important; }
    .rw-table tbody td[data-label]::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748B;
        flex-shrink: 0;
        min-width: 80px;
    }
    .rw-table tbody td[data-label] > * {
        text-align: right !important;
        max-width: calc(100% - 88px) !important;
        flex: 1 1 auto !important;
        min-width: 0 !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }
    .rw-table tbody td.rw-mini-actions {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
        max-width: calc(100% - 88px) !important;
        white-space: normal !important;
    }
    .rw-table tbody td.rw-mini-actions form {
        display: inline-block !important;
        margin: 0 !important;
    }
    .rw-table tbody td.rw-mini-actions .btn {
        padding: 4px 10px !important;
        font-size: 11.5px !important;
        flex-shrink: 0 !important;
    }
    .rw-table tbody td.rw-td-top {
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
        border-bottom: 1px solid #E2E8F0 !important;
        padding-bottom: 10px !important;
        margin-bottom: 4px !important;
        justify-content: flex-start !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        min-width: 0 !important;
    }
    .rw-table tbody td.rw-td-top::before { display: none !important; }
    .rw-table tbody td.rw-td-top > div {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        text-align: left !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }
    .gt_float_switcher, .gtranslate_wrapper {
        bottom: 6px !important;
        left: 6px !important;
        transform: scale(0.8) !important;
        transform-origin: bottom left !important;
        z-index: 999 !important;
    }
}
</style>

<div class="page-header">
    <div>
        <h1>Rewards Module</h1>
        <p>Earning-milestone rewards with image, badge, embed and visibility controls.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/admin/rewards?action=create" class="btn btn-primary">+ New Reward Rule</a>
    </div>
</div>

<div class="rw-tabs">
    <a href="/admin/rewards?tab=rules"  class="rw-tab <?= $tab==='rules'?'active':'' ?>">Reward Rules</a>
    <a href="/admin/rewards?tab=grants" class="rw-tab <?= $tab==='grants'?'active':'' ?>">Granted Rewards</a>
</div>

<?php if ($tab === 'rules'): ?>

<!-- Search + Filter Bar -->
<form class="rw-filter" method="GET">
    <input type="hidden" name="tab" value="rules">
    <input type="text" name="q" class="grow" placeholder="Search title / description / badge…" value="<?= Helpers::e($filters['search']) ?>">
    <select name="kind">
        <option value="">All kinds</option>
        <?php foreach (['cash','bonus_credit','voucher','product'] as $k): ?>
        <option value="<?= $k ?>" <?= $filters['kind']===$k?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$k)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="vis">
        <option value="">All visibility</option>
        <?php foreach (['public','affiliate','vip','private'] as $v): ?>
        <option value="<?= $v ?>" <?= $filters['visibility']===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="active">
        <option value="">Any status</option>
        <option value="1" <?= $filters['active']==='1'?'selected':'' ?>>Active</option>
        <option value="0" <?= $filters['active']==='0'?'selected':'' ?>>Disabled</option>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <?php if ($filters['search']||$filters['kind']||$filters['visibility']||$filters['active']!==''): ?>
    <a href="/admin/rewards" class="btn btn-secondary btn-sm">Clear</a>
    <?php endif; ?>
</form>

<!-- Reward Rules List -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">Configured Milestone Rules</span>
        <a href="/admin/rewards?action=create" class="btn btn-primary btn-sm">+ New Reward</a>
    </div>
    <form method="POST" id="rwOrderForm">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="submit_type" value="save_order">
        <div class="rw-table-wrap">
            <table class="rw-table">
                <thead>
                    <tr>
                        <th style="width:30px"></th>
                        <th>Reward</th>
                        <th>Visibility</th>
                        <th>Threshold</th>
                        <th>Reward Kind</th>
                        <th>Active</th>
                        <th style="white-space:nowrap;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rwSortBody">
                <?php foreach ($rules as $r):
                    $valLabel = ($r['kind'] === 'cash' || $r['kind'] === 'bonus_credit')
                        ? '$' . number_format((float)$r['value_amount'], 2)
                        : (string)$r['value_text'];
                    $bg = $r['badge_color'] ?: '#94A3B8';
                    $cleanDesc = trim(strip_tags(html_entity_decode((string)($r['description'] ?? ''))));
                ?>
                <tr data-rid="<?= (int)$r['id'] ?>">
                    <td class="rw-td-top">
                        <span class="rw-handle" title="Drag to reorder">≡</span>
                        <input type="hidden" name="order[]" value="<?= (int)$r['id'] ?>">
                        <div class="rw-thumb"<?= !empty($r['image_path']) ? ' style="background-image:url(\'' . Helpers::e($r['image_path']) . '\')"' : '' ?>>
                            <?= empty($r['image_path']) ? '🎁' : '' ?>
                        </div>
                        <div>
                            <div style="font-weight:700;line-height:1.25;color:#0F172A"><?= Helpers::e($r['title']) ?></div>
                            <?php if ($cleanDesc !== ''): ?>
                            <div style="font-size:11.5px;color:#64748B;margin-top:2px"><?= Helpers::e(mb_strimwidth($cleanDesc, 0, 85, '…')) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($r['badge_label'])): ?>
                            <span class="rw-badge-chip" style="background:<?= Helpers::e($bg) ?>;margin-top:4px"><?= Helpers::e($r['badge_label']) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td data-label="Visibility"><span class="rw-vis-tag rw-vis-<?= Helpers::e($r['visibility'] ?? 'public') ?>"><?= Helpers::e($r['visibility'] ?? 'public') ?></span></td>
                    <td data-label="Threshold">$<?= number_format((float)$r['threshold_usd'], 2) ?></td>
                    <td data-label="Reward">
                        <div>
                            <span class="badge badge-info"><?= Helpers::e(ucfirst(str_replace('_',' ',$r['kind']))) ?></span>
                            <?php if ($valLabel !== ''): ?>
                            <span style="font-size:11.5px;color:#64748B;margin-left:4px"><?= Helpers::e($valLabel) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td data-label="Active">
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="toggle_active">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <label class="rw-toggle">
                                <input type="checkbox" <?= (int)$r['active']===1?'checked':'' ?> onchange="this.form.submit()">
                                <span class="t"><span class="k"></span></span>
                            </label>
                        </form>
                    </td>
                    <td data-label="Actions" class="rw-mini-actions" style="white-space:nowrap">
                        <a href="/admin/rewards?action=edit&id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="duplicate_rule">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-secondary btn-sm" type="submit" title="Duplicate">⧉</button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this rule? Existing grants are kept.')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="delete_rule">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit" title="Delete">×</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rules)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:32px">No reward rules match the current filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($rules)): ?>
        <div style="padding:12px;display:flex;justify-content:flex-end">
            <button class="btn btn-secondary btn-sm" type="submit">Save order</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php else: /* tab === 'grants' */ ?>

<div class="card">
    <div class="card-header"><span class="card-title">Granted Rewards</span></div>
    <div class="rw-table-wrap">
        <table class="rw-table">
            <thead>
                <tr>
                    <th>Granted</th>
                    <th>Affiliate</th>
                    <th>Reward</th>
                    <th class="text-right">Threshold</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grants as $g): ?>
            <tr>
                <td data-label="Granted" class="text-muted text-sm"><?= Helpers::e(date('M j H:i', strtotime((string)$g['granted_at']))) ?></td>
                <td data-label="Affiliate"><?= Helpers::e($g['aff_name'] ?: ('#' . $g['affiliate_id'])) ?></td>
                <td data-label="Reward">
                    <div><strong><?= Helpers::e($g['title_snapshot']) ?></strong></div>
                    <div class="text-muted" style="font-size:11px">
                        <?= Helpers::e($g['kind']) ?>
                        <?= $g['value_amount'] !== null ? ' · $' . number_format((float)$g['value_amount'], 2) : '' ?>
                        <?= !empty($g['value_text']) ? ' · ' . Helpers::e($g['value_text']) : '' ?>
                    </div>
                </td>
                <td data-label="Threshold" class="text-right">$<?= number_format((float)$g['lifetime_at_grant'], 2) ?></td>
                <td data-label="Status">
                    <?php $b = ['granted'=>'warning','claimed'=>'info','paid'=>'success','cancelled'=>'danger'][$g['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($g['status']) ?></span>
                </td>
                <td data-label="Action">
                    <details>
                        <summary class="btn btn-secondary btn-sm" style="cursor:pointer">Update</summary>
                        <form method="POST" style="margin-top:8px;background:#F8FAFC;padding:10px;border-radius:6px;max-width:100%;box-sizing:border-box">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="update_grant">
                            <input type="hidden" name="grant_id" value="<?= (int)$g['id'] ?>">
                            <select name="status" class="form-control" style="margin-bottom:6px">
                                <?php foreach (['granted','claimed','paid','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $g['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_note" class="form-control" rows="2" placeholder="Note" style="margin-bottom:6px"><?= Helpers::e($g['admin_note'] ?? '') ?></textarea>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </form>
                    </details>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($grants)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No rewards granted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>

<script>
// Drag-and-drop reordering for rules list
(function(){
    var tbody = document.getElementById('rwSortBody');
    if (!tbody) return;
    var dragRow = null;
    tbody.querySelectorAll('tr').forEach(function(tr){
        tr.setAttribute('draggable', 'true');
        tr.addEventListener('dragstart', function(){ dragRow = tr; tr.classList.add('dragging'); });
        tr.addEventListener('dragend',   function(){ if (dragRow) dragRow.classList.remove('dragging'); dragRow = null; });
        tr.addEventListener('dragover',  function(e){
            e.preventDefault();
            if (!dragRow || dragRow === tr) return;
            var rect = tr.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            tr.parentNode.insertBefore(dragRow, after ? tr.nextSibling : tr);
        });
    });
})();
</script>
