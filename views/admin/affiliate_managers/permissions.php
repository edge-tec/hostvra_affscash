<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.amp-card{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
.amp-head{padding:18px 22px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#F8FAFC,#EEF2FF);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
.amp-head h2{margin:0;font-size:16px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:10px;}
.amp-head h2::before{content:"";width:6px;height:18px;border-radius:3px;background:linear-gradient(135deg,#4F46E5,#7C3AED);}
.amp-help{font-size:12.5px;color:var(--text-muted);line-height:1.5;padding:14px 22px;background:#EFF6FF;border-bottom:1px solid #DBEAFE;color:#1E40AF}
.amp-wrap{overflow-x:auto}
.amp-table{width:100%;border-collapse:collapse;font-size:13px;min-width:1100px;}
.amp-table th,.amp-table td{padding:11px 14px;text-align:center;border-bottom:1px solid var(--border);vertical-align:middle;white-space:nowrap;}
.amp-table thead th{position:sticky;top:0;background:var(--bg);font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);z-index:2;}
.amp-table thead th.sticky-left{left:0;z-index:4;text-align:left;min-width:240px}
.amp-table tbody td.sticky-left{position:sticky;left:0;background:var(--card-bg);text-align:left;font-weight:600;color:var(--text);box-shadow:1px 0 0 var(--border);}
.amp-table tbody tr:hover td{background:var(--bg);}
.amp-mgr-meta{font-size:11px;color:var(--text-muted);font-weight:500;margin-top:2px}
/* Pretty toggle switch */
.amp-tgl{position:relative;display:inline-block;width:38px;height:22px;}
.amp-tgl input{opacity:0;width:0;height:0;}
.amp-tgl .track{position:absolute;cursor:pointer;inset:0;background:#CBD5E1;border-radius:99px;transition:background .18s;}
.amp-tgl .knob{position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:left .18s;box-shadow:0 1px 3px rgba(0,0,0,.2);}
.amp-tgl input:checked + .track{background:#10B981;}
.amp-tgl input:checked + .track .knob{left:19px;}
.amp-section-tag{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:2px 7px;border-radius:99px;background:#EEF2FF;color:#4338CA;}
.amp-section-tag.fraud{background:#FEF2F2;color:#B91C1C;}
.amp-section-tag.invoice{background:#FFFBEB;color:#92400E;}
.amp-section-tag.support{background:#ECFDF5;color:#065F46;}
.amp-actions{padding:14px 22px;border-top:1px solid var(--border);background:#F8FAFC;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
.amp-bulk{display:flex;gap:8px;align-items:center;font-size:12px;color:var(--text-muted)}
.amp-bulk a{font-size:11.5px;font-weight:600;cursor:pointer;padding:4px 10px;border-radius:6px;background:#fff;border:1px solid var(--border);color:var(--text);text-decoration:none}
.amp-bulk a:hover{background:var(--bg);}
</style>

<div class="page-header">
    <div>
        <h1>Affiliate Manager Permissions</h1>
        <p>Granular admin-only access control. Changes apply instantly to the next request.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/admin/affiliate-managers" class="btn btn-secondary btn-sm">← Back to Managers</a>
        <a href="/admin/affiliate-managers/fraud-rejections" class="btn btn-secondary btn-sm">Fraud Rejection History</a>
        <a href="/admin/affiliate-managers/messages" class="btn btn-secondary btn-sm">Messages</a>
    </div>
</div>

<div class="amp-card">
    <div class="amp-help">
        <strong>Permission catalogue.</strong> Every action that requires one of these permissions calls <code>ManagerPermissions::requirePermission(&hellip;)</code> server-side, so checks cannot be bypassed from the browser. The invoice rules follow a tier: managers can always submit <em>requests</em> to admin for approval — <strong>Allow invoice generator access</strong> elevates them to generating invoices directly, and <strong>Allow mark-invoice-paid</strong> is a separate, even higher tier.
    </div>

    <form method="POST">
        <?= Helpers::csrf() ?>

        <?php if (empty($managers)): ?>
        <div style="padding:40px;text-align:center;color:var(--text-muted)">No affiliate managers found.</div>
        <?php else: ?>
        <div class="amp-wrap">
            <table class="amp-table">
                <thead>
                    <tr>
                        <th class="sticky-left">Manager</th>
                        <?php foreach (ManagerPermissions::CATALOGUE as $key => $meta):
                            $grpCls = $meta['group'] === 'fraud' ? 'fraud'
                                    : ($meta['group'] === 'invoice' ? 'invoice'
                                    : ($meta['group'] === 'support' ? 'support' : ''));
                        ?>
                        <th>
                            <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                                <span><?= Helpers::e($meta['label']) ?></span>
                                <span class="amp-section-tag <?= $grpCls ?>"><?= Helpers::e($meta['group']) ?></span>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($managers as $m):
                    $mid    = (int)$m['mgr_id'];
                    $rowMap = $permMatrix[$mid] ?? [];
                ?>
                    <tr data-mgr="<?= $mid ?>">
                        <td class="sticky-left">
                            <div><?= Helpers::e($m['name']) ?></div>
                            <div class="amp-mgr-meta"><?= Helpers::e($m['email']) ?> · <?= Helpers::e($m['status']) ?></div>
                        </td>
                        <?php foreach (ManagerPermissions::CATALOGUE as $key => $meta):
                            $on = !empty($rowMap[$key]);
                        ?>
                        <td>
                            <label class="amp-tgl" title="<?= Helpers::e($meta['label']) ?>">
                                <input type="checkbox" name="perm[<?= $mid ?>][<?= Helpers::e($key) ?>]" value="1" <?= $on ? 'checked' : '' ?>>
                                <span class="track"><span class="knob"></span></span>
                            </label>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="amp-actions">
            <div class="amp-bulk">
                Bulk:
                <a data-bulk="all">Select all</a>
                <a data-bulk="defaults">Reset to defaults</a>
                <a data-bulk="none">Clear all</a>
            </div>
            <button type="submit" class="btn btn-primary">Save Permissions</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
(function(){
    // Bulk-action handlers — affect every visible row.
    var defaults = <?= json_encode(array_map(fn($m) => (int)$m['default'], ManagerPermissions::CATALOGUE)) ?>;
    var keys     = <?= json_encode(array_keys(ManagerPermissions::CATALOGUE)) ?>;
    document.querySelectorAll('[data-bulk]').forEach(function(a){
        a.addEventListener('click', function(){
            var mode = a.dataset.bulk;
            document.querySelectorAll('tbody tr[data-mgr]').forEach(function(tr){
                keys.forEach(function(k, idx){
                    var cb = tr.querySelector('input[name="perm['+ tr.dataset.mgr +']['+ k +']"]');
                    if (!cb) return;
                    if (mode === 'all')      cb.checked = true;
                    else if (mode === 'none') cb.checked = false;
                    else if (mode === 'defaults') cb.checked = !!defaults[k];
                });
            });
        });
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
