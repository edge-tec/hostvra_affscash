<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Private Offers</h1>
        <p>Restricted-access offers — only visible to admin-granted affiliates.</p>
    </div>
</div>

<!-- Convert a public offer to private ------------------------------------- -->
<div class="card" style="margin-bottom:18px">
    <div class="card-header" style="font-weight:700;font-size:14px">Convert an Offer to Private</div>
    <div style="padding:16px">
        <form method="POST" action="/admin/private-offers" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="set_private">
            <input type="hidden" name="private" value="1">
            <div class="form-group" style="flex:1;min-width:280px;margin:0">
                <label style="font-size:12px;font-weight:600">Pick a public offer to mark Private</label>
                <select name="offer_id" class="form-control" required>
                    <option value="">— Select offer —</option>
                    <?php foreach ($convertable as $o): ?>
                    <option value="<?= (int)$o['id'] ?>">
                        OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?> &middot; <?= Helpers::e($o['name']) ?>
                        &middot; <?= Helpers::e($o['payout_type']) ?> $<?= number_format((float)$o['payout_amount'], 2) ?>
                        <?php if ($o['status'] !== 'active'): ?> [<?= Helpers::e($o['status']) ?>]<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:38px">Mark as Private</button>
        </form>
        <p style="font-size:12px;color:var(--text-muted);margin:10px 0 0">
            Once marked private, the offer disappears from every affiliate's dashboard. Grant access to specific affiliates on the offer's detail page.
        </p>
    </div>
</div>

<!-- Current private offers ------------------------------------------------ -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-weight:700;font-size:14px">Active Private Offers</span>
        <span class="badge badge-muted"><?= count($privateOffers) ?> total</span>
    </div>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Payout</th>
                    <th>Status</th>
                    <th>Granted Affiliates</th>
                    <th style="width:1%;white-space:nowrap">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($privateOffers)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:28px">No private offers yet. Use the form above to mark one.</td></tr>
            <?php else: foreach ($privateOffers as $o): ?>
                <tr>
                    <td class="fw-bold">
                        OFF-<?= str_pad((int)$o['id'], 4, '0', STR_PAD_LEFT) ?>
                        &middot; <?= Helpers::e($o['name']) ?>
                    </td>
                    <td>
                        <?= Helpers::e($o['payout_type']) ?>
                        $<?= number_format((float)$o['payout_amount'], 2) ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $o['status']==='active' ? 'success' : 'muted' ?>">
                            <?= Helpers::e($o['status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-info"><?= (int)$o['access_count'] ?> granted</span>
                    </td>
                    <td style="white-space:nowrap">
                        <a href="/admin/private-offers?id=<?= (int)$o['id'] ?>" class="btn btn-primary btn-sm">Manage Access</a>
                        <form method="POST" action="/admin/private-offers" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="set_private">
                            <input type="hidden" name="private" value="0">
                            <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm"
                                onclick="return confirm('Revert this offer to public visibility? Existing grants will remain but the visibility filter will no longer apply.')">
                                Make Public
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent activity log --------------------------------------------------- -->
<div class="card" style="margin-top:18px">
    <div class="card-header" style="font-weight:700;font-size:14px">Recent Activity</div>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Action</th>
                    <th>Offer</th>
                    <th>Affiliate</th>
                    <th>Actor</th>
                    <th>Source</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentLog)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:24px">No activity logged yet.</td></tr>
            <?php else: foreach ($recentLog as $r):
                $badge = match($r['action']) {
                    'grant'   => 'success',
                    'allow'   => 'info',
                    'enable'  => 'success',
                    'revoke'  => 'warning',
                    'disable' => 'warning',
                    'deny'    => 'danger',
                    default   => 'muted',
                };
            ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px"><?= Helpers::e($r['created_at']) ?></td>
                    <td><span class="badge badge-<?= $badge ?>"><?= Helpers::e($r['action']) ?></span></td>
                    <td><?= Helpers::e($r['offer_name'] ?: ('OFF-' . str_pad((int)$r['offer_id'], 4, '0', STR_PAD_LEFT))) ?></td>
                    <td>
                        <?php if ($r['affiliate_id']): ?>
                            <?= Helpers::e($r['aff_name'] ?? '') ?>
                            <small class="text-muted">(<?= Helpers::e($r['affiliate_code'] ?: '#'.$r['affiliate_id']) ?>)</small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= Helpers::e($r['actor_name'] ?: '—') ?></td>
                    <td><?= Helpers::e($r['source']) ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= Helpers::e($r['ip_address'] ?: '—') ?></td>
                    <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= Helpers::e($r['details'] ?? '') ?>"><?= Helpers::e($r['details'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
