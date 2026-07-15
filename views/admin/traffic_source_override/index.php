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
                    <label>Target Original Sources</label>
                    <select name="target_original_sources[]" class="form-control select2-tags-multi" multiple>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Telegram">Telegram</option>
                        <option value="Facebook Messenger">Facebook Messenger</option>
                        <option value="Instagram Direct">Instagram Direct</option>
                        <option value="Threads">Threads</option>
                        <option value="Discord">Discord</option>
                        <option value="Skype">Skype</option>
                        <option value="Signal">Signal</option>
                        <option value="WeChat">WeChat</option>
                        <option value="LINE">LINE</option>
                        <option value="Viber">Viber</option>
                        <option value="Reddit">Reddit</option>
                        <option value="TikTok">TikTok</option>
                        <option value="Direct">Direct</option>
                    </select>
                    <div class="form-hint">Leave blank to apply to ALL sources.</div>
                </div>

                <div class="form-group">
                    <label>Override As (Destination)</label>
                    <select name="override_source" class="form-control select2-tags-single" required>
                        <option value="">Select or type destination source...</option>
                        <option value="Paid Ads">Paid Ads</option>
                        <option value="SEO">SEO</option>
                        <option value="Email">Email</option>
                        <option value="Display">Display</option>
                        <option value="Native">Native</option>
                        <option value="Push">Push</option>
                        <option value="Social">Social</option>
                        <option value="Search">Search</option>
                        <option value="Organic">Organic</option>
                        <option value="Influencer">Influencer</option>
                        <option value="Referral">Referral</option>
                    </select>
                    <div class="form-hint">Select a common source or type your own.</div>
                </div>

                <hr style="margin: 15px 0; border: none; border-top: 1px dashed #ccc;">
                <p style="font-weight:600;font-size:12px;margin-bottom:10px;text-transform:uppercase">Optional Conditions</p>

                <div class="form-group">
                    <label>Affiliates</label>
                    <select name="affiliate_ids[]" class="form-control select2-multi" multiple>
                        <?php foreach ($affiliates as $aff): ?>
                            <option value="<?= $aff['id'] ?>"><?= Helpers::e($aff['name']) ?> (ID: <?= $aff['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Offers</label>
                    <select name="offer_ids[]" class="form-control select2-multi" multiple>
                        <?php foreach ($offers as $off): ?>
                            <option value="<?= $off['id'] ?>"><?= Helpers::e($off['name']) ?> (ID: <?= $off['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Advertisers</label>
                    <select name="advertiser_ids[]" class="form-control select2-multi" multiple>
                        <?php foreach ($advertisers as $adv): ?>
                            <option value="<?= $adv['id'] ?>"><?= Helpers::e($adv['name']) ?> (ID: <?= $adv['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Countries</label>
                    <select name="countries[]" class="form-control select2-multi" multiple>
                        <option value="US">United States</option>
                        <option value="GB">United Kingdom</option>
                        <option value="CA">Canada</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="IN">India</option>
                        <option value="BD">Bangladesh</option>
                        <!-- more countries can be typed in select2 -->
                    </select>
                </div>

                <div class="form-group">
                    <label>Device Types</label>
                    <select name="device_types[]" class="form-control select2-multi" multiple>
                        <option value="Mobile">Mobile</option>
                        <option value="Desktop">Desktop</option>
                        <option value="Tablet">Tablet</option>
                        <option value="Bot">Bot</option>
                    </select>
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

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 4px;
        min-height: 38px;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #80bdff;
        outline: 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #f8f9fa;
        border: 1px solid #dae0e5;
        border-radius: 3px;
        padding: 2px 6px;
        margin-top: 5px;
    }
</style>
<script>
    // Note: ensure jQuery is loaded. If it's already in the admin layout, this will just use it.
    // If not, we just loaded it above.
    $(document).ready(function() {
        $('.select2-multi').select2({
            width: '100%',
            placeholder: "Select options"
        });
        $('.select2-tags-multi').select2({
            width: '100%',
            placeholder: "Select options or type custom ones...",
            tags: true
        });
        $('.select2-tags-single').select2({
            width: '100%',
            placeholder: "Select or type destination...",
            tags: true
        });
    });
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
