<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Manager Fraud Rejections</h1>
        <p>Every conversion rejected as fraud by an Affiliate Manager — full audit trail.</p>
    </div>
    <a href="/admin/affiliate-managers/permissions" class="btn btn-secondary btn-sm">← Permissions</a>
</div>

<div class="card">
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Rejected At</th>
                    <th>Manager</th>
                    <th>Conversion ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:36px">No fraud rejections logged yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
            <tr>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= Helpers::e(date('M j, Y H:i', strtotime($r['rejected_at']))) ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['manager_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['manager_email']) ?></div>
                </td>
                <td><code style="font-size:11.5px;background:#F1F5F9;padding:2px 6px;border-radius:4px"><?= Helpers::e(substr($r['conversion_id'],0,16)) ?>…</code></td>
                <td><?= $r['affiliate_id'] ? '#' . (int)$r['affiliate_id'] : '—' ?></td>
                <td><?= $r['offer_id'] ? '#' . (int)$r['offer_id'] : '—' ?></td>
                <td style="max-width:380px;white-space:normal;color:#475569"><?= Helpers::e($r['reason'] ?: '— no reason provided —') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
