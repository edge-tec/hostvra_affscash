<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <h1>&#128259; Traffic Source Override</h1>
        <p>Reclassify chat-based traffic (Telegram, WhatsApp, Messenger, etc.) as another traffic type for reporting.</p>
    </div>
    <form method="POST" style="display:inline">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="action" value="toggle_global">
        <button type="submit" class="btn <?= $globalEnabled ? 'btn-danger' : 'btn-primary' ?>" style="min-width:160px">
            <?= $globalEnabled ? '&#9724; Disable Globally' : '&#9654; Enable Globally' ?>
        </button>
    </form>
</div>

<?php foreach (Helpers::getFlash() as $_f): ?>
<div class="alert alert-<?= $_f['type'] === 'error' ? 'danger' : 'success' ?> mb-3"><?= Helpers::e($_f['message']) ?></div>
<?php endforeach; ?>

<?php if (!$globalEnabled): ?>
<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#92400E">
    <strong>⚠ Feature Disabled.</strong> Traffic source override is currently turned off globally. All chat traffic will be recorded with its original source. Enable the feature above to start overriding chat traffic sources.
</div>
<?php else: ?>
<div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:8px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#065F46">
    <strong>&#10003; Feature Active.</strong> Chat traffic matching the rules below will be reclassified in click tracking. Original sources are preserved in the database for admin-only auditing. Affiliates will only see the overridden traffic type.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:16px;align-items:flex-start">

    <!-- ── Add New Rule ──────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><span class="card-title">+ Add Override Rule</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="add_rule">

                <div class="form-group">
                    <label>Affiliate</label>
                    <select name="affiliate_id" class="form-control" id="tsoAffSelect">
                        <option value="">— All Affiliates (Global Rule) —</option>
                        <?php foreach ($affiliates as $a): ?>
                        <option value="<?= (int)$a['id'] ?>">
                            #<?= (int)$a['id'] ?> &middot; <?= Helpers::e($a['affiliate_code']) ?> &middot; <?= Helpers::e($a['name']) ?>
                            <?= !empty($a['email']) ? ' (' . Helpers::e($a['email']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Leave as "All Affiliates" for a global rule, or select a specific affiliate.</div>
                </div>

                <div class="form-group">
                    <label>Original Chat Source</label>
                    <select name="original_source" class="form-control" required>
                        <option value="">— Select source —</option>
                        <?php foreach (TrafficSourceOverride::CHAT_SOURCES as $src): ?>
                        <option value="<?= $src ?>"><?= TrafficSourceOverride::sourceLabel($src) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">The chat platform traffic coming from.</div>
                </div>

                <div class="form-group">
                    <label>Override As (Destination)</label>
                    <select name="override_source" class="form-control" required>
                        <option value="">— Select destination —</option>
                        <?php foreach (TrafficSourceOverride::OVERRIDE_DESTINATIONS as $key => $label): ?>
                        <option value="<?= $key ?>"><?= Helpers::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">The traffic type to show in reports instead.</div>
                </div>

                <button class="btn btn-primary" style="width:100%">Add Override Rule</button>
            </form>
        </div>
    </div>

    <!-- ── Rules Table ───────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Active Rules (<?= count($rules) ?>)</span>
            <input type="text" id="tsoRuleSearch" class="form-control" placeholder="Search rules…"
                   style="max-width:220px;height:34px;font-size:13px">
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($rules)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                No override rules configured yet. Add one using the form on the left.
            </div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table" style="margin:0" id="tsoRulesTable">
                <thead>
                    <tr>
                        <th style="width:55px">ID</th>
                        <th>Affiliate</th>
                        <th>Original Source</th>
                        <th>→</th>
                        <th>Override As</th>
                        <th style="width:80px">Status</th>
                        <th style="width:150px">Created</th>
                        <th style="width:160px;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $r): ?>
                    <tr data-search="<?= Helpers::e(strtolower(($r['affiliate_code'] ?? '') . ' ' . ($r['affiliate_name'] ?? '') . ' ' . $r['original_source'] . ' ' . $r['override_source'])) ?>">
                        <td><code><?= (int)$r['id'] ?></code></td>
                        <td>
                            <?php if ($r['affiliate_id']): ?>
                                <strong>#<?= (int)$r['affiliate_id'] ?></strong>
                                <?php if ($r['affiliate_code']): ?>
                                    <span style="color:var(--text-muted)">&middot; <?= Helpers::e($r['affiliate_code']) ?></span>
                                <?php endif; ?>
                                <?php if ($r['affiliate_name']): ?>
                                    <br><small style="color:var(--text-muted)"><?= Helpers::e($r['affiliate_name']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="background:var(--primary);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700">ALL</span>
                                <span style="color:var(--text-muted);font-size:12px;margin-left:4px">Global Rule</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="background:#FEE2E2;color:#991B1B;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600">
                                <?= Helpers::e(TrafficSourceOverride::sourceLabel($r['original_source'])) ?>
                            </span>
                        </td>
                        <td style="font-size:18px;color:var(--text-muted)">→</td>
                        <td>
                            <span style="background:#DBEAFE;color:#1E40AF;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600">
                                <?= Helpers::e(TrafficSourceOverride::destinationLabel($r['override_source'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['enabled']): ?>
                                <span style="color:#059669;font-weight:600;font-size:12px">&#9679; Active</span>
                            <?php else: ?>
                                <span style="color:#9CA3AF;font-weight:600;font-size:12px">&#9679; Off</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)">
                            <?= date('M j, Y H:i', strtotime($r['created_at'])) ?>
                            <?php if ($r['admin_first']): ?>
                                <br><small>by <?= Helpers::e($r['admin_first'] . ' ' . $r['admin_last']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <!-- Toggle -->
                            <form method="POST" style="display:inline">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="toggle_rule">
                                <input type="hidden" name="rule_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $r['enabled'] ? 'btn-secondary' : 'btn-primary' ?>"
                                        title="<?= $r['enabled'] ? 'Disable' : 'Enable' ?>"
                                        style="padding:4px 10px;font-size:11px">
                                    <?= $r['enabled'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <!-- Edit (modal trigger) -->
                            <button type="button" class="btn btn-sm btn-secondary tso-edit-btn"
                                    data-id="<?= (int)$r['id'] ?>"
                                    data-override="<?= Helpers::e($r['override_source']) ?>"
                                    data-original-label="<?= Helpers::e(TrafficSourceOverride::sourceLabel($r['original_source'])) ?>"
                                    style="padding:4px 10px;font-size:11px"
                                    title="Edit">
                                Edit
                            </button>
                            <!-- Delete -->
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this override rule?')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="delete_rule">
                                <input type="hidden" name="rule_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        style="padding:4px 10px;font-size:11px" title="Delete">
                                    Del
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Edit Modal ─────────────────────────────────────────────────────── -->
<div id="tsoEditModal" style="display:none;position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:var(--card-bg,#fff);border-radius:12px;padding:24px;width:100%;max-width:440px;box-shadow:0 10px 30px rgba(0,0,0,.15)">
        <h3 style="margin:0 0 16px;font-size:16px">Edit Override Rule</h3>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="edit_rule">
            <input type="hidden" name="rule_id" id="tsoEditRuleId">
            <div class="form-group">
                <label>Original Source</label>
                <input type="text" class="form-control" id="tsoEditOriginalLabel" disabled>
            </div>
            <div class="form-group">
                <label>Override As (Destination)</label>
                <select name="override_source" class="form-control" id="tsoEditOverride" required>
                    <?php foreach (TrafficSourceOverride::OVERRIDE_DESTINATIONS as $key => $label): ?>
                    <option value="<?= $key ?>"><?= Helpers::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('tsoEditModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Audit Log ──────────────────────────────────────────────────────── -->
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <span class="card-title">Audit Log (Recent 30)</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($auditLog)): ?>
        <div style="padding:32px;text-align:center;color:var(--text-muted)">No audit log entries yet.</div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table" style="margin:0;font-size:13px">
            <thead>
                <tr>
                    <th style="width:160px">Time</th>
                    <th style="width:120px">Action</th>
                    <th>Affiliate</th>
                    <th>Source</th>
                    <th>Override</th>
                    <th>Change</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLog as $log): ?>
                <tr>
                    <td style="font-size:12px;color:var(--text-muted)"><?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                        <?php
                        $actionColors = [
                            'created' => '#059669', 'updated' => '#D97706', 'deleted' => '#DC2626',
                            'toggled' => '#6366F1', 'global_enabled' => '#059669', 'global_disabled' => '#DC2626',
                        ];
                        $color = $actionColors[$log['action']] ?? '#6B7280';
                        ?>
                        <span style="color:<?= $color ?>;font-weight:600;font-size:12px"><?= strtoupper($log['action']) ?></span>
                    </td>
                    <td>
                        <?= $log['affiliate_id'] ? '#' . (int)$log['affiliate_id'] : '<em style="color:var(--text-muted)">Global</em>' ?>
                    </td>
                    <td><?= $log['original_source'] ? Helpers::e(TrafficSourceOverride::sourceLabel($log['original_source'])) : '—' ?></td>
                    <td><?= $log['override_source'] ? Helpers::e(TrafficSourceOverride::destinationLabel($log['override_source'])) : '—' ?></td>
                    <td style="font-size:12px">
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                            <span style="color:#DC2626"><?= Helpers::e($log['old_value'] ?? '—') ?></span>
                            →
                            <span style="color:#059669"><?= Helpers::e($log['new_value'] ?? '—') ?></span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">
                        <?= $log['admin_email'] ? Helpers::e($log['admin_email']) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Detection Info ─────────────────────────────────────────────────── -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><span class="card-title">&#128270; Chat Source Detection Patterns</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
            The system automatically detects chat traffic using the following signals. No configuration needed — just create override rules above.
        </p>
        <div class="table-wrap">
        <table class="table" style="margin:0;font-size:13px">
            <thead>
                <tr>
                    <th style="width:140px">Platform</th>
                    <th>Referer Domains</th>
                    <th>User-Agent Keywords</th>
                    <th>UTM Parameter</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><strong>Telegram</strong></td><td><code>t.me</code>, <code>telegram.org</code>, <code>web.telegram.org</code></td><td><code>TelegramBot</code>, <code>Telegram</code></td><td><code>utm_source=telegram</code></td></tr>
                <tr><td><strong>WhatsApp</strong></td><td><code>wa.me</code>, <code>whatsapp.com</code>, <code>api.whatsapp.com</code></td><td><code>WhatsApp</code></td><td><code>utm_source=whatsapp</code></td></tr>
                <tr><td><strong>Messenger</strong></td><td><code>m.me</code>, <code>messenger.com</code>, <code>l.facebook.com</code></td><td><code>FBAN/Messenger</code></td><td><code>utm_source=messenger</code></td></tr>
                <tr><td><strong>Discord</strong></td><td><code>discord.com</code>, <code>discordapp.com</code>, <code>discord.gg</code></td><td><code>Discordbot</code></td><td><code>utm_source=discord</code></td></tr>
                <tr><td><strong>Signal</strong></td><td><code>signal.org</code>, <code>signal.link</code></td><td><em style="color:var(--text-muted)">none</em></td><td><code>utm_source=signal</code></td></tr>
                <tr><td><strong>Viber</strong></td><td><code>viber.com</code>, <code>chats.viber.com</code></td><td><code>Viber</code></td><td><code>utm_source=viber</code></td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
// ── Edit modal ──────────────────────────────────────────────────────────
document.querySelectorAll('.tso-edit-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('tsoEditRuleId').value = this.dataset.id;
        document.getElementById('tsoEditOriginalLabel').value = this.dataset.originalLabel;
        document.getElementById('tsoEditOverride').value = this.dataset.override;
        document.getElementById('tsoEditModal').style.display = 'flex';
    });
});
// Close modal on background click
document.getElementById('tsoEditModal').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
});

// ── Search filter ───────────────────────────────────────────────────────
var searchInput = document.getElementById('tsoRuleSearch');
if (searchInput) {
    searchInput.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#tsoRulesTable tbody tr');
        rows.forEach(function(row){
            var text = row.getAttribute('data-search') || '';
            row.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        });
    });
}
</script>
