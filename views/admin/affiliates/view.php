<?php
require BASE_PATH . '/views/layouts/admin.php';
require_once BASE_PATH . '/views/admin/_payment_details_display.php';
?>

<div class="page-header">
    <div>
        <h1><?= Helpers::e($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?></h1>
        <p>Code: <strong><?= Helpers::e($affiliate['affiliate_code']) ?></strong> &bull; <?= Helpers::e($affiliate['email']) ?></p>
    </div>
    <div class="d-flex gap-2" style="flex-wrap:wrap">
        <a href="/admin/affiliates/<?= $affiliate['id'] ?>?action=edit" class="btn btn-primary">&#9998; Edit</a>
        <a href="/admin/affiliates?action=impersonate&user_id=<?= $affiliate['user_id'] ?>" class="btn btn-secondary" onclick="return confirm('Login as this affiliate?')">&#128064; Login As</a>
        <a href="/admin/invoices?affiliate_id=<?= $affiliate['id'] ?>" class="btn btn-secondary">&#128176; View Invoices</a>
        <button type="button" class="btn btn-secondary" style="background:#ECFDF5;color:#065F46;border-color:#A7F3D0" onclick="document.getElementById('invoice-modal').style.display='flex'">&#10133; Generate Invoice</button>

        <?php if (!empty($affiliate['google2fa_enabled'])): ?>
        <form method="POST" action="/admin/users/2fa-reset" style="display:inline" onsubmit="return confirm('Reset Google Authenticator 2FA for this affiliate?\nThey will need to re-enable it from their account.');">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="user_id" value="<?= (int)$affiliate['user_id'] ?>">
            <input type="hidden" name="redirect_back" value="/admin/affiliates/<?= (int)$affiliate['id'] ?>">
            <button class="btn btn-secondary" title="2FA enabled — click to reset" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A">
                &#128274; 2FA <span style="font-weight:700">ON</span> &nbsp;<span style="opacity:.85">· Reset</span>
            </button>
        </form>
        <?php else: ?>
        <span class="btn" style="background:#F1F5F9;color:#475569;border-color:#E2E8F0;cursor:default" title="Affiliate has not enabled 2FA">&#128275; 2FA Off</span>
        <?php endif; ?>

        <button type="button" class="btn btn-danger" onclick="document.getElementById('delete-modal').style.display='flex'">&#128465; Delete</button>
        <a href="/admin/affiliates" class="btn btn-secondary">← Back</a>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:420px;width:90%;text-align:center">
        <div style="font-size:40px;margin-bottom:12px">&#9888;&#65039;</div>
        <h3 style="margin-bottom:8px">Delete Affiliate?</h3>
        <p style="color:#64748B;margin-bottom:24px">This will permanently deactivate <strong><?= Helpers::e($affiliate['first_name'] . ' ' . $affiliate['last_name']) ?></strong>'s account and block all their offer access. This action cannot be undone.</p>
        <div style="display:flex;gap:12px;justify-content:center">
            <form method="POST" action="/admin/affiliates?action=delete">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
                <button type="submit" class="btn btn-danger">Yes, Delete Account</button>
            </form>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('delete-modal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>

<!-- Generate Invoice Modal -->
<div id="invoice-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:32px;max-width:420px;width:90%;text-align:center">
        <h3 style="margin-bottom:8px">Generate Invoice</h3>
        <p style="color:#64748B;margin-bottom:24px">Select the billing period for this invoice.</p>
        <form method="GET" action="/admin/invoices">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
            <div style="text-align:left;margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600">Period Start <span style="color:#EF4444">*</span></label>
                <input type="date" name="period_start" class="form-control" required value="<?= date('Y-m-01') ?>">
            </div>
            <div style="text-align:left;margin-bottom:24px">
                <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600">Period End <span style="color:#EF4444">*</span></label>
                <input type="date" name="period_end" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div style="display:flex;gap:12px;justify-content:center">
                <button type="submit" class="btn btn-primary" style="background:#16A34A;border-color:#16A34A">Continue</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('invoice-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="grid-2 mb-3">
    <div class="stats-grid" style="grid-template-columns:1fr 1fr">
        <div class="stat-card">
            <div class="stat-label">Total Clicks</div>
            <div class="stat-value"><?= number_format($stats['clicks'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Conversions</div>
            <div class="stat-value"><?= number_format($stats['conv'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Payout</div>
            <div class="stat-value">$<?= number_format($stats['payout'] ?? 0,2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Balance</div>
            <div class="stat-value">$<?= number_format($affiliate['balance'],2) ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Account Status</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach(['active','pending','suspended','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $affiliate['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Admin Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= Helpers::e($affiliate['notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Affiliate Manager</label>
                    <select name="manager_id" class="form-control">
                        <option value="">— No Manager —</option>
                        <?php foreach ($managers as $mgr): ?>
                        <option value="<?= $mgr['id'] ?>" <?= ($affiliate['manager_id'] ?? '') == $mgr['id'] ? 'selected' : '' ?>><?= Helpers::e($mgr['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>
</div>

<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Profile Information</span></div>
        <div class="card-body">
            <table style="width:100%">
                <?php $fields = ['email'=>'Email','company'=>'Company','phone'=>'Phone','country'=>'Country','payment_method'=>'Payment Method','payment_threshold'=>'Payment Threshold','fraud_score'=>'Fraud Score']; ?>
                <?php foreach($fields as $k=>$label): ?>
                <tr>
                    <td style="padding:6px 0;color:var(--text-muted);font-size:13px;width:40%"><?= $label ?></td>
                    <td style="padding:6px 0;font-size:13px"><?= Helpers::e((string)($affiliate[$k] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td style="padding:6px 0;color:var(--text-muted);font-size:13px;width:40%">Registration IP</td>
                    <td style="padding:6px 0;font-size:13px">
                        <?php if (!empty($affiliate['registration_ip'])): ?>
                            <span style="font-family:monospace"><?= Helpers::e($affiliate['registration_ip']) ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php
        // Payment method row (already in the $fields table above as text)
        // Show payment_details beneath the table with structured formatting
        $_pdMethod  = $affiliate['payment_method']  ?? '';
        $_pdDetails = $affiliate['payment_details'] ?? '';
        if ($_pdMethod || $_pdDetails):
        ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
            <div style="font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px">Payment Details</div>
            <?php if ($_pdMethod): ?>
            <div style="font-size:13px;font-weight:700;color:#1E293B;margin-bottom:4px"><?= Helpers::e($_pdMethod) ?></div>
            <?php endif; ?>
            <?= formatPaymentDetailsHtml($_pdMethod, $_pdDetails) ?>
        </div>
        <?php endif; ?>
        <div class="mt-2">
                <a href="/admin/affiliates/<?= $affiliate['id'] ?>?action=edit" class="btn btn-secondary btn-sm">&#9998; Edit Profile</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">&#128172; Contact Details</span>
        </div>
        <div class="card-body">
            <?php
            $contacts = [
                'telegram' => ['label'=>'Telegram ID',  'color'=>'#2AABEE', 'badge'=>'TG', 'prefix'=>'https://t.me/'],
                'skype'    => ['label'=>'Skype ID',     'color'=>'#00AFF0', 'badge'=>'SK', 'prefix'=>'skype:'],
                'discord'  => ['label'=>'Discord ID',   'color'=>'#5865F2', 'badge'=>'DC', 'prefix'=>null],
            ];
            $hasAny = false;
            foreach ($contacts as $field => $cfg):
                $val = trim($affiliate[$field] ?? '');
                if (!$val) continue;
                $hasAny = true;
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="background:<?= $cfg['color'] ?>;color:#fff;border-radius:4px;padding:3px 8px;font-size:11px;font-weight:700;flex-shrink:0"><?= $cfg['badge'] ?></span>
                <span style="font-size:12px;color:var(--text-muted);width:90px;flex-shrink:0"><?= $cfg['label'] ?></span>
                <?php if ($cfg['prefix'] && $field !== 'discord'): ?>
                <a href="<?= $cfg['prefix'] . ltrim(Helpers::e($val), '@') ?>" target="_blank"
                   style="font-size:13px;font-weight:600;color:<?= $cfg['color'] ?>;text-decoration:none">
                    <?= Helpers::e($val) ?>
                </a>
                <?php else: ?>
                <span style="font-size:13px;font-weight:600"><?= Helpers::e($val) ?></span>
                <?php endif; ?>
                <button type="button" onclick="navigator.clipboard.writeText('<?= Helpers::e($val) ?>')"
                        style="margin-left:auto;font-size:11px;padding:2px 8px;border:1px solid var(--border);border-radius:4px;background:var(--bg);cursor:pointer;color:var(--text-muted)"
                        title="Copy">&#128203;</button>
            </div>
            <?php endforeach; ?>
            <?php if (!$hasAny): ?>
            <div style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">
                No contact details provided.
            </div>
            <?php endif; ?>
            <?php
            $address = trim($affiliate['address'] ?? '');
            if ($address):
            ?>
            <div style="margin-top:12px">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:2px;font-weight:600">Street Address</div>
                <div style="font-size:13px"><?= Helpers::e($address) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($questions)): ?>
<!-- Registration Q&A — read-only view of what the affiliate filled in at signup. -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Registration Answers</span>
        <span style="font-size:11px;color:var(--text-muted)">Submitted at signup</span>
    </div>
    <div class="card-body">
        <?php $_anyAnswered = false; foreach ($questions as $q): if (!empty($answers[$q['id']])): $_anyAnswered = true; ?>
        <div class="form-group mb-2">
            <label style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($q['question_text']) ?></label>
            <div style="font-size:13px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;padding:8px 12px;color:#0F172A"><?= nl2br(Helpers::e($answers[$q['id']])) ?></div>
        </div>
        <?php endif; endforeach; ?>
        <?php if (!$_anyAnswered): ?>
        <div style="font-size:13px;color:var(--text-muted);font-style:italic">
            <?php if (!empty($affiliate['registration_answers']) && $affiliate['registration_answers'] !== '{}'): ?>
                This affiliate's answers don't match any currently active questions (questions may have been edited or deactivated since signup).
            <?php else: ?>
                This affiliate registered before the questionnaire was added — no answers on file.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Offer Access with Block/Approve/Remove -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Offer Access</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Offer</th>
                    <th>Campaign</th>
                    <th>Std. Payout</th>
                    <th>Custom Payout</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($offers)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No offer assignments</td></tr>
            <?php else: ?>
            <?php foreach($offers as $o):
                $affCp = !empty($o['aff_country_payouts']) ? json_decode($o['aff_country_payouts'], true) : [];
                $affDp = !empty($o['aff_device_payouts'])  ? json_decode($o['aff_device_payouts'],  true) : [];
            ?>
            <tr>
                <td class="fw-bold"><?= Helpers::e($o['name']) ?></td>
                <td><?= Helpers::e($o['campaign_name'] ?: '—') ?></td>
                <td>$<?= number_format($o['payout_amount'],2) ?></td>
                <td><?= $o['custom_payout'] !== null ? '$'.number_format($o['custom_payout'],2) : '—' ?></td>
                <td>
                    <?php
                    $badgeMap = [
                        'approved' => 'success',
                        'pending'  => 'warning',
                        'blocked'  => 'danger',
                        'rejected' => 'danger',
                        'removed'  => 'muted',
                    ];
                    $badge = $badgeMap[$o['status']] ?? 'muted';
                    ?>
                    <span class="badge badge-<?= $badge ?>"><?= $o['status'] ?></span>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <!-- Approve -->
                        <?php if ($o['status'] !== 'approved'): ?>
                        <form method="POST" action="/admin/affiliates/approve-offer" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
                            <input type="hidden" name="offer_id" value="<?= $o['offer_id'] ?>">
                            <input type="hidden" name="from" value="affiliate">
                            <button type="submit" class="btn btn-success btn-sm" title="Approve">&#10003; Approve</button>
                        </form>
                        <?php endif; ?>

                        <!-- Reject (only for pending) -->
                        <?php if ($o['status'] === 'pending'): ?>
                        <form method="POST" action="/admin/affiliates/reject-offer" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
                            <input type="hidden" name="offer_id" value="<?= $o['offer_id'] ?>">
                            <input type="hidden" name="from" value="affiliate">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this access request?')" title="Reject">&#10007; Reject</button>
                        </form>
                        <?php endif; ?>

                        <!-- Block -->
                        <?php if ($o['status'] !== 'blocked'): ?>
                        <form method="POST" action="/admin/affiliates?action=block_offer" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
                            <input type="hidden" name="offer_id" value="<?= $o['offer_id'] ?>">
                            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Block this affiliate\'s access to the offer?')" title="Block">&#128683; Block</button>
                        </form>
                        <?php endif; ?>

                        <!-- Configure -->
                        <details>
                            <summary style="cursor:pointer;color:var(--primary);font-size:12px;padding:4px 8px;border:1px solid var(--primary);border-radius:6px">&#9881; Config</summary>
                            <div style="padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:6px;margin-top:8px;min-width:400px;position:relative;z-index:10">
                                <form method="POST" action="/admin/affiliates/<?= $affiliate['id'] ?>">
                                    <?= Helpers::csrf() ?>
                                    <input type="hidden" name="offer_config_action" value="save_offer_config">
                                    <input type="hidden" name="offer_config_offer_id" value="<?= $o['offer_id'] ?>">
                                    <div class="form-group">
                                        <label style="font-size:13px">Campaign Name</label>
                                        <input type="text" name="cfg_campaign_name" class="form-control" value="<?= Helpers::e($o['campaign_name'] ?? '') ?>" placeholder="e.g. Summer2025">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size:13px">Custom Payout ($)</label>
                                        <input type="number" step="0.01" min="0" name="cfg_custom_payout" class="form-control" value="<?= $o['custom_payout'] !== null ? Helpers::e($o['custom_payout']) : '' ?>" placeholder="Leave empty to use offer default">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size:13px">Country Payout Overrides</label>
                                        <div id="cfg-country-container-<?= $o['offer_id'] ?>">
                                        <?php foreach ($affCp as $cpRow): ?>
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                                            <input type="text" name="cfg_country_payout_country[]" class="form-control" placeholder="US" maxlength="2" style="text-transform:uppercase;max-width:80px" value="<?= Helpers::e($cpRow['country'] ?? '') ?>">
                                            <input type="number" name="cfg_country_payout_amount[]" class="form-control" step="0.01" min="0" placeholder="5.00" value="<?= Helpers::e($cpRow['payout'] ?? '') ?>">
                                            <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm">&#10005;</button>
                                        </div>
                                        <?php endforeach; ?>
                                        </div>
                                        <button type="button" onclick="addCfgCountryRow('<?= $o['offer_id'] ?>')" class="btn btn-secondary btn-sm">+ Add Country Rule</button>
                                    </div>
                                    <div class="form-group">
                                        <label style="font-size:13px">Device Payout Overrides</label>
                                        <div style="display:flex;gap:12px">
                                            <?php foreach(['desktop','mobile','tablet'] as $dev): ?>
                                            <div>
                                                <label style="font-size:12px"><?= ucfirst($dev) ?> ($)</label>
                                                <input type="number" step="0.01" min="0" name="cfg_device_payout_<?= $dev ?>" class="form-control" placeholder="0.00" value="<?= Helpers::e($affDp[$dev] ?? '') ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </div>
                        </details>

                        <!-- Remove -->
                        <form method="POST" action="/admin/affiliates?action=remove_offer" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="affiliate_id" value="<?= $affiliate['id'] ?>">
                            <input type="hidden" name="offer_id" value="<?= $o['offer_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this offer access?')" title="Remove">&#10005;</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addCfgCountryRow(offerId) {
    var container = document.getElementById('cfg-country-container-' + offerId);
    var div = document.createElement('div');
    div.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:8px';
    div.innerHTML = '<input type="text" name="cfg_country_payout_country[]" class="form-control" placeholder="US" maxlength="2" style="text-transform:uppercase;max-width:80px">'
        + '<input type="number" name="cfg_country_payout_amount[]" class="form-control" step="0.01" min="0" placeholder="5.00">'
        + '<button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm">&#10005;</button>';
    container.appendChild(div);
    div.querySelector('input').focus();
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
