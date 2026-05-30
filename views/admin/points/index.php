<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Points Module</h1><p>Rule-based earning-to-points conversion. Read-only against the existing earnings system.</p></div>
</div>

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">
    <div class="card">
        <div class="card-header"><span class="card-title">Conversion Rule</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_config">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px">
                        <input type="checkbox" name="enabled" value="1" <?= !empty($cfg['enabled']) ? 'checked' : '' ?>>
                        <span style="font-weight:600">Points module enabled</span>
                    </label>
                    <div class="form-hint">When disabled, no new points are credited but existing balances stay intact.</div>
                </div>
                <div class="form-group">
                    <label>USD per Point</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:13px;color:#64748B">$</span>
                        <input type="number" name="usd_per_point" step="1" min="1" max="100" class="form-control" style="max-width:160px" value="<?= Helpers::e((int)$cfg['usd_per_point']) ?>" required>
                        <span style="font-size:13px;color:#64748B">= 1 point</span>
                    </div>
                    <div class="form-hint">Enter any whole number from 1 to 100. Example: 5 means the affiliate earns 1 point for every $5 of approved earnings.</div>
                </div>
                <button class="btn btn-primary" type="submit">Save Rule</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Manual Adjustment</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="adjust">
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group"><label>Affiliate ID</label><input type="number" name="affiliate_id" class="form-control" required min="1"></div>
                    <div class="form-group"><label>Delta (+ or −)</label><input type="number" name="delta" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Reason</label><input type="text" name="reason" class="form-control" placeholder="e.g. Compensate bug-related lost earnings"></div>
                <button class="btn btn-secondary" type="submit">Apply Adjustment</button>
                <div class="form-hint" style="margin-top:8px">Negative deltas are gated against the current balance — the request is rejected if it would create a negative balance.</div>
            </form>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
        <span class="card-title">Auto-Points Sync</span>
        <span class="badge badge-info" style="font-size:11px">Backfill approved conversions</span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:#64748B;margin-bottom:14px">
            Scans all <strong>approved</strong> conversions and automatically credits missing points
            following the active USD-per-point rule. Runs are idempotent — already-credited
            conversions are skipped. Use <em>Dry Run</em> to preview without writing.
        </p>
        <form method="POST" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="sync_all">
            <div class="form-group" style="margin:0;flex:0 0 180px">
                <label style="font-size:12px;font-weight:600;margin-bottom:4px;display:block">Only since date (optional)</label>
                <input type="date" name="since" class="form-control" style="height:36px;font-size:13px"
                       placeholder="e.g. 2024-01-01">
            </div>
            <div class="form-group" style="margin:0;display:flex;align-items:center;gap:6px">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                    <input type="checkbox" name="dry_run" value="1">
                    <span>Dry Run (preview only)</span>
                </label>
            </div>
            <button class="btn btn-primary" type="submit" style="height:36px;padding:0 18px">
                ▶ Run Auto-Sync
            </button>
        </form>
        <?php if (!empty($syncLog)): ?>
        <div style="margin-top:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:12px;max-height:240px;overflow-y:auto">
            <div style="font-size:11px;font-weight:700;color:#64748B;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">Sync Log</div>
            <?php foreach ($syncLog as $line): ?>
            <div style="font-size:12px;font-family:monospace;color:#1E293B;line-height:1.7;white-space:pre"><?= Helpers::e((string)$line) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- Cron job instruction removed per user request: Points are credited in real-time upon conversion approval. -->
    </div>
</div>

<div class="card mb-3">
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th>Affiliate</th><th>Email</th><th class="text-right">Balance</th><th class="text-right">Earned (lifetime)</th><th class="text-right">Spent (lifetime)</th><th>Last updated</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong>#<?= (int)$r['affiliate_id'] ?></strong> · <?= Helpers::e((string)($r['name'] ?? '—')) ?></td>
                <td class="text-muted"><?= Helpers::e((string)($r['email'] ?? '')) ?></td>
                <td class="text-right" style="font-weight:700;color:#059669"><?= number_format((int)$r['balance']) ?></td>
                <td class="text-right"><?= number_format((int)$r['lifetime_earned']) ?></td>
                <td class="text-right"><?= number_format((int)$r['lifetime_spent']) ?></td>
                <td class="text-muted text-sm"><?= $r['updated_at'] ? Helpers::e(date('M j, H:i', strtotime((string)$r['updated_at']))) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No balances yet — approved conversions will credit points automatically.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Recent Ledger</span></div>
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th>Time</th><th>Affiliate</th><th>Kind</th><th class="text-right">Delta</th><th>Reason</th><th>Ref</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r):
                $color   = (int)$r['delta'] >= 0 ? '#059669' : '#DC2626';
                // Defensive casts: every JOIN column can be NULL (admin
                // adjustment for a deleted affiliate, refund with no ref, etc).
                $rAffNm  = (string)($r['aff_name']    ?? '');
                $rKind   = (string)($r['kind']        ?? '');
                $rReason = (string)($r['reason']      ?? '');
                $rRefT   = (string)($r['ref_type']    ?? '');
                $rRefId  = (string)($r['ref_id']      ?? '');
                $rCreat  = (string)($r['created_at']  ?? '');
            ?>
            <tr>
                <td class="text-muted text-sm"><?= $rCreat ? Helpers::e(date('M j H:i', strtotime($rCreat))) : '—' ?></td>
                <td><?= Helpers::e($rAffNm !== '' ? $rAffNm : '—') ?> <span class="text-muted" style="font-size:11px">#<?= (int)$r['affiliate_id'] ?></span></td>
                <td><span class="badge badge-info"><?= Helpers::e($rKind) ?></span></td>
                <td class="text-right" style="font-weight:700;color:<?= $color ?>"><?= (int)$r['delta'] > 0 ? '+' : '' ?><?= number_format((int)$r['delta']) ?></td>
                <td class="text-muted text-sm"><?= Helpers::e($rReason) ?></td>
                <td class="text-muted text-sm"><?= $rRefT !== '' ? Helpers::e($rRefT . '#' . $rRefId) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No ledger entries yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
