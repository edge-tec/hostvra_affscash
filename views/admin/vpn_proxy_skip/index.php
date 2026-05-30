<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; VPN/Proxy Detection &mdash; Excluded Affiliates</h1>
        <p>Affiliates listed here bypass VPN, Proxy, Tor and datacenter blocking. Detection still runs (for fraud-score visibility) but the block + log are suppressed for their traffic.</p>
    </div>
    <a href="/admin/settings?tab=vpn_detection" class="btn btn-secondary">&larr; VPN &amp; Proxy Settings</a>
</div>

<?php foreach (Helpers::getFlash() as $_f): ?>
<div class="alert alert-<?= $_f['type'] === 'error' ? 'danger' : 'success' ?> mb-3"><?= Helpers::e($_f['message']) ?></div>
<?php endforeach; ?>

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1E3A8A">
    <strong>How the bypass works:</strong> When an affiliate is on this list, their tracking links, smartlinks, click tracking and conversion tracking will not be blocked even if the visitor's IP is flagged as VPN, Proxy, Tor or hosting/datacenter. The Live Threat Monitor and <code>vpn_blocked_log</code> intentionally do not record blocks for these affiliates. All other affiliates continue to be filtered by your existing detection rules.
</div>

<div class="grid-2" style="grid-template-columns:1fr 1.4fr;gap:16px;align-items:flex-start">

    <!-- ── Add to skip list ─────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header"><span class="card-title">+ Add Affiliate</span></div>
        <div class="card-body">
            <?php if (empty($affiliates)): ?>
            <div class="text-muted" style="font-size:13px">All active affiliates are already on the skip list.</div>
            <?php else: ?>
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Affiliate</label>
                    <select name="affiliate_id" class="form-control" required>
                        <option value="">— Select an affiliate —</option>
                        <?php foreach ($affiliates as $a): ?>
                        <option value="<?= (int)$a['id'] ?>">
                            #<?= (int)$a['id'] ?> &middot; <?= Helpers::e($a['affiliate_code']) ?> &middot; <?= Helpers::e($a['name']) ?>
                            <?= !empty($a['email']) ? ' (' . Helpers::e($a['email']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Only active affiliates are listed.</div>
                </div>
                <div class="form-group">
                    <label>Note <span style="color:#94A3B8;font-weight:400;font-size:12px">(optional)</span></label>
                    <textarea name="note" class="form-control" rows="2" maxlength="500"
                              placeholder="Why is this affiliate excluded? e.g. trusted partner, all traffic from datacenter office IP, …"></textarea>
                </div>
                <button class="btn btn-primary">Add to Skip List</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Current skip list ────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Excluded Affiliates (<?= count($entries) ?>)</span>
            <span class="text-muted" style="font-size:12px">VPN/Proxy detection skipped for these IDs</span>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($entries)): ?>
            <div style="padding:32px;text-align:center;color:var(--text-muted)">
                No affiliates are currently excluded. VPN/Proxy detection runs for everyone.
            </div>
            <?php else: ?>
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:90px">Aff ID</th>
                        <th>Affiliate Username</th>
                        <th>Note</th>
                        <th style="width:170px">Date Added</th>
                        <th style="width:160px">Added By Admin</th>
                        <th style="width:100px;text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td>
                            <code style="background:#F1F5F9;border-radius:4px;padding:2px 6px;font-size:12px">#<?= (int)$e['affiliate_id'] ?></code>
                            <div class="text-muted" style="font-size:11px;margin-top:2px"><?= Helpers::e($e['affiliate_code'] ?? '—') ?></div>
                        </td>
                        <td>
                            <div class="fw-bold"><?= Helpers::e($e['affiliate_name'] ?: '— deleted —') ?></div>
                            <?php if (!empty($e['affiliate_email'])): ?>
                            <div class="text-muted" style="font-size:11px"><?= Helpers::e($e['affiliate_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);max-width:260px">
                            <?= !empty($e['note']) ? Helpers::e($e['note']) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td style="font-size:12px"><?= Helpers::e($e['created_at']) ?></td>
                        <td style="font-size:12px">
                            <?php if (!empty($e['admin_first']) || !empty($e['admin_last'])): ?>
                            <?= Helpers::e(trim(($e['admin_first'] ?? '') . ' ' . ($e['admin_last'] ?? ''))) ?>
                            <?php if (!empty($e['admin_email'])): ?>
                            <div class="text-muted" style="font-size:11px"><?= Helpers::e($e['admin_email']) ?></div>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this affiliate from the VPN/Proxy skip list? Detection will resume for their traffic.');">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                <button class="btn btn-danger btn-sm">&#10005; Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
