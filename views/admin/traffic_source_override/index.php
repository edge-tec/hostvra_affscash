<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
        <h1>&#128259; Traffic Source Override</h1>
        <p>Advanced system to dynamically map traffic sources to advertiser-compliant sources based on flexible rules.</p>
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
    <strong>⚠ Feature Disabled.</strong> Traffic source override is currently turned off globally.
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
                    <label>Rule Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Map WhatsApp to Paid Ads" required>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <input type="number" name="priority" class="form-control" value="0">
                    <div class="form-hint">Higher number = higher priority. Evaluated first.</div>
                </div>

                <div class="form-group">
                    <label>Target Original Sources (CSV)</label>
                    <input type="text" name="target_original_sources" class="form-control" placeholder="WhatsApp, Telegram">
                    <div class="form-hint">Comma separated. Leave blank to apply to ALL sources.</div>
                </div>

                <div class="form-group">
                    <label>Override As (Destination)</label>
                    <input type="text" name="override_source" class="form-control" placeholder="e.g. Paid Ads" required>
                    <div class="form-hint">The new source to send to advertisers.</div>
                </div>

                <hr style="margin: 15px 0; border: none; border-top: 1px dashed #ccc;">
                <p style="font-weight:600;font-size:12px;margin-bottom:10px;text-transform:uppercase">Optional Conditions</p>

                <div class="form-group">
                    <label>Affiliate IDs (CSV)</label>
                    <input type="text" name="affiliate_ids" class="form-control" placeholder="e.g. 10, 25">
                </div>

                <div class="form-group">
                    <label>Offer IDs (CSV)</label>
                    <input type="text" name="offer_ids" class="form-control" placeholder="e.g. 100, 205">
                </div>
                
                <div class="form-group">
                    <label>Advertiser IDs (CSV)</label>
                    <input type="text" name="advertiser_ids" class="form-control" placeholder="e.g. 5, 8">
                </div>

                <div class="form-group">
                    <label>Countries (CSV)</label>
                    <input type="text" name="countries" class="form-control" placeholder="e.g. US, GB, CA">
                </div>

                <div class="form-group">
                    <label>Device Types (CSV)</label>
                    <input type="text" name="device_types" class="form-control" placeholder="e.g. Mobile, Desktop">
                </div>

                <button class="btn btn-primary" style="width:100%">Save Override Rule</button>
            </form>
        </div>
    </div>

    <!-- ── Rules Table ───────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Active Rules (<?= count($rules) ?>)</span>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($rules)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                No override rules configured yet.
            </div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Pri</th>
                        <th>Name</th>
                        <th>Target Sources</th>
                        <th>Override To</th>
                        <th>Conditions</th>
                        <th style="width:80px">Status</th>
                        <th style="width:160px;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $r): ?>
                    <?php 
                        $targets = json_decode($r['target_original_sources'] ?? '[]', true);
                        $conditions = json_decode($r['conditions'] ?? '{}', true);
                    ?>
                    <tr>
                        <td><code><?= (int)$r['priority'] ?></code></td>
                        <td style="font-weight:600"><?= Helpers::e($r['name']) ?></td>
                        <td>
                            <?php if (empty($targets)): ?>
                                <span class="badge badge-secondary">ALL</span>
                            <?php else: ?>
                                <?= Helpers::e(implode(', ', $targets)) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="background:#DBEAFE;color:#1E40AF;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600">
                                <?= Helpers::e($r['override_source']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;">
                            <?php if (empty($conditions)): ?>
                                <em style="color:#999">None (Global)</em>
                            <?php else: ?>
                                <?php foreach ($conditions as $k => $v): ?>
                                    <strong><?= Helpers::e($k) ?>:</strong> <?= Helpers::e(implode(', ', $v)) ?><br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['enabled']): ?>
                                <span style="color:#059669;font-weight:600;font-size:12px">&#9679; Active</span>
                            <?php else: ?>
                                <span style="color:#9CA3AF;font-weight:600;font-size:12px">&#9679; Off</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
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

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
