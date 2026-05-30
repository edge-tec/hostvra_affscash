<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>
            <a href="/admin/private-offers" style="text-decoration:none;color:inherit">Private Offers</a>
            <span style="color:var(--text-muted);font-weight:400">›</span>
            <?= Helpers::e($offer['name']) ?>
        </h1>
        <p>
            OFF-<?= str_pad((int)$offer['id'], 4, '0', STR_PAD_LEFT) ?>
            &middot; <?= Helpers::e($offer['payout_type']) ?> $<?= number_format((float)$offer['payout_amount'], 2) ?>
            &middot; Visibility:
            <strong style="color:#7C3AED">
                <?= Helpers::e(strtoupper($offer['visibility'] ?? 'public')) ?>
            </strong>
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <form method="POST" action="/admin/private-offers" style="display:inline">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="set_private">
            <input type="hidden" name="private" value="<?= ($offer['visibility'] ?? 'public') === 'private' ? 0 : 1 ?>">
            <input type="hidden" name="offer_id" value="<?= (int)$offer['id'] ?>">
            <?php if (($offer['visibility'] ?? 'public') === 'private'): ?>
            <button type="submit" class="btn btn-secondary"
                onclick="return confirm('Revert this offer to public visibility?')">Make Public</button>
            <?php else: ?>
            <button type="submit" class="btn btn-primary">Make Private</button>
            <?php endif; ?>
        </form>
        <a href="/admin/offers/<?= (int)$offer['id'] ?>" class="btn btn-secondary">Edit Offer</a>
    </div>
</div>

<!-- Grant a new affiliate ------------------------------------------------- -->
<div class="card" style="margin-bottom:18px">
    <div class="card-header" style="font-weight:700;font-size:14px">Grant Affiliate Access</div>
    <div style="padding:16px">
        <form method="POST" action="/admin/private-offers?id=<?= (int)$offer['id'] ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="grant_access">
            <input type="hidden" name="offer_id" value="<?= (int)$offer['id'] ?>">
            <div class="form-group" style="flex:2;min-width:240px;margin:0">
                <label style="font-size:12px;font-weight:600">Affiliate ID, code, or email</label>
                <input type="text" name="affiliate_identifier" class="form-control"
                       placeholder="123  ·  AFFABCD1234  ·  user@example.com" required>
            </div>
            <div class="form-group" style="flex:2;min-width:240px;margin:0">
                <label style="font-size:12px;font-weight:600">Notes (optional)</label>
                <input type="text" name="notes" class="form-control" maxlength="500"
                       placeholder="e.g. VIP tier, contract #2024-08">
            </div>
            <button type="submit" class="btn btn-primary" style="height:38px">Grant Access</button>
        </form>
    </div>
</div>

<!-- Current grants -------------------------------------------------------- -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-weight:700;font-size:14px">Affiliates with Access</span>
        <span class="badge badge-muted"><?= count($grants) ?> granted</span>
    </div>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Code</th>
                    <th>Email</th>
                    <th>Account</th>
                    <th>Granted</th>
                    <th>Notes</th>
                    <th style="width:1%;white-space:nowrap">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($grants)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:24px">No affiliates granted access yet.</td></tr>
            <?php else: foreach ($grants as $g): ?>
                <tr>
                    <td class="fw-bold"><?= Helpers::e($g['first_name'].' '.$g['last_name']) ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= Helpers::e($g['affiliate_code']) ?></td>
                    <td><?= Helpers::e($g['email']) ?></td>
                    <td>
                        <span class="badge badge-<?= $g['user_status']==='active' ? 'success' : 'muted' ?>">
                            <?= Helpers::e($g['user_status']) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;font-size:12px"><?= Helpers::e($g['granted_at']) ?></td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="<?= Helpers::e($g['notes'] ?? '') ?>"><?= Helpers::e($g['notes'] ?? '') ?></td>
                    <td style="white-space:nowrap">
                        <form method="POST" action="/admin/private-offers?id=<?= (int)$offer['id'] ?>" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="action" value="revoke_access">
                            <input type="hidden" name="offer_id" value="<?= (int)$offer['id'] ?>">
                            <input type="hidden" name="affiliate_id" value="<?= (int)$g['affiliate_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Revoke this affiliate\'s access to the private offer?')">Revoke</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Activity log for this offer ------------------------------------------ -->
<div class="card" style="margin-top:18px">
    <div class="card-header" style="font-weight:700;font-size:14px">Access Log (this offer)</div>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Action</th>
                    <th>Affiliate</th>
                    <th>Actor</th>
                    <th>Source</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($log)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:24px">No log entries for this offer yet.</td></tr>
            <?php else: foreach ($log as $r):
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
