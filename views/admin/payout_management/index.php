<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.pm-tabs { display:flex; gap:0; flex-wrap:wrap; border-bottom:2px solid #E2E8F0; margin-bottom:24px; }
/* ── Filter bar ── */
.pm-filter-bar {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.pm-filter-bar .fg label {
    font-size: 11px; font-weight: 700; color: #64748B;
    text-transform: uppercase; letter-spacing: .4px;
    display: block; margin-bottom: 5px;
}
.pm-filter-bar .fg select {
    height: 36px; padding: 0 10px; border: 1px solid #CBD5E1;
    border-radius: 6px; font-size: 13px; background: #fff;
    color: #334155; min-width: 200px; cursor: pointer;
}
.pm-filter-bar .fg select:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.pm-filter-bar .filter-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #EEF2FF; color: #3730A3; border: 1px solid #C7D2FE;
    border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600;
}
.pm-filter-bar .reset-btn {
    height: 36px; padding: 0 12px; background: none;
    border: 1px solid #CBD5E1; border-radius: 6px;
    font-size: 12px; color: #64748B; cursor: pointer; white-space: nowrap;
}
.pm-filter-bar .reset-btn:hover { background: #F1F5F9; }
.pm-tab  {
    display:inline-flex; align-items:center; gap:7px; padding:11px 18px;
    font-size:13px; font-weight:600; border-radius:8px 8px 0 0;
    border:1px solid transparent; border-bottom:2px solid transparent;
    margin-bottom:-2px; text-decoration:none; color:var(--text-muted);
    white-space:nowrap; transition:color .15s;
}
.pm-tab:hover { color:var(--primary); text-decoration:none; }
.pm-tab.active {
    border-color:#E2E8F0; border-bottom-color:#fff;
    background:#fff; color:var(--primary);
}
.pm-tab .tab-count {
    background:var(--primary); color:#fff;
    font-size:10px; font-weight:700; border-radius:10px;
    padding:1px 7px; min-width:18px; text-align:center;
}
.pm-form-card { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:20px 24px; margin-bottom:24px; }
.pm-form-card h3 { font-size:14px; font-weight:700; color:var(--text); margin:0 0 16px; display:flex; align-items:center; gap:8px; }
.pm-inline-form { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.pm-inline-form .fg { display:flex; flex-direction:column; gap:5px; }
.pm-inline-form .fg label { font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.4px; }
.pm-inline-form .fg input,
.pm-inline-form .fg select { height:38px; padding:0 10px; border:1px solid var(--border); border-radius:6px; font-size:13px; background:#fff; color:var(--text); min-width:120px; }
.pm-inline-form .fg input:focus,
.pm-inline-form .fg select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.pm-inline-form .fg select.aff-select { min-width:220px; }
.pm-inline-form .fg input.w-sm { width:90px; min-width:80px; }
.pm-inline-form .fg input.w-cc { width:72px; min-width:60px; text-transform:uppercase; }
.device-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.device-badge.desktop { background:#EFF6FF; color:#1D4ED8; }
.device-badge.mobile  { background:#F0FDF4; color:#15803D; }
.device-badge.tablet  { background:#FFF7ED; color:#C2410C; }
.flag { font-size:16px; }
.pm-empty { text-align:center; padding:40px 20px; color:var(--text-muted); font-size:14px; }
.pm-empty svg { opacity:.3; margin-bottom:10px; }
.opt-slider { -webkit-appearance:none; appearance:none; width:160px; height:6px; border-radius:3px; background:#E2E8F0; outline:none; cursor:pointer; }
.opt-slider::-webkit-slider-thumb { -webkit-appearance:none; width:18px; height:18px; border-radius:50%; background:var(--primary); cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.2); }
</style>

<div class="page-header">
    <div>
        <h1>&#9881; Advanced Payout Management</h1>
        <p>Configure global and per-affiliate payout rules, daily caps, and conversion optimization</p>
    </div>
</div>

<?php if ($flash && !empty($flash['success'])): ?>
<div class="alert alert-success mb-3" style="background:#DCFCE7;border:1px solid #BBF7D0;border-radius:8px;padding:12px 18px;color:#15803D;font-size:13px;margin-bottom:16px">
    ✓ <?= Helpers::e($flash['success']) ?>
</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3" style="background:#FEE2E2;border:1px solid #FECACA;border-radius:8px;padding:12px 18px;color:#B91C1C;font-size:13px;margin-bottom:16px">
    <?php foreach ($errors as $e): ?><div>⚠ <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$tabs = [
    'country_payouts'  => ['icon'=>'🌍','label'=>'Country Payouts',    'count'=>count($countryPayouts)],
    'device_payouts'   => ['icon'=>'📱','label'=>'Device Payouts',      'count'=>count($devicePayouts)],
    'aff_capping'      => ['icon'=>'🔒','label'=>'Affiliate Capping',   'count'=>count($affCaps)],
    'aff_payouts'      => ['icon'=>'💰','label'=>'Affiliate Payouts',   'count'=>count($affPayouts)],
    'conversion_optimize' => ['icon'=>'⚡','label'=>'Conversion Optimize','count'=>count($optimizeRules)],
];
?>
<div class="pm-tabs">
    <?php foreach ($tabs as $key => $info): ?>
    <a href="?tab=<?= $key ?>" class="pm-tab <?= $tab === $key ? 'active' : '' ?>">
        <span><?= $info['icon'] ?></span>
        <?= $info['label'] ?>
        <?php if ($info['count'] > 0): ?>
        <span class="tab-count"><?= $info['count'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>


<?php /* ══════════════════════════════════════════════════════════
         TAB 1 — COUNTRY PAYOUTS
   ══════════════════════════════════════════════════════════ */ ?>
<?php if ($tab === 'country_payouts'): ?>

<div class="pm-form-card">
    <h3>🌍 Add / Update Country Payout Rule</h3>
    <form method="POST">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_country_payout">
        <div class="pm-inline-form">
            <div class="fg">
                <label>Affiliate <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="affiliate_id" class="aff-select" style="min-width:200px">
                    <option value="">— All Affiliates —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Offer <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="offer_id" style="min-width:200px">
                    <option value="">— All Offers —</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Country Code <span style="color:#EF4444">*</span></label>
                <input type="text" name="country" class="w-cc" placeholder="US" maxlength="2" required>
            </div>
            <div class="fg">
                <label>Revenue ($)</label>
                <input type="number" name="revenue" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>Payout ($)</label>
                <input type="number" name="payout" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm">💾 Save Rule</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Country Payout Rules</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($countryPayouts) ?> rule<?= count($countryPayouts)!==1?'s':'' ?> configured</span>
    </div>

    <!-- Filter bar -->
    <div class="pm-filter-bar" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;background:#FAFBFC">
        <div class="fg">
            <label>🔍 Filter by Country</label>
            <select id="flt-cp-country" onchange="filterTable('tbl-cp', this.value, 0)" style="min-width:160px">
                <option value="">All Countries</option>
                <?php foreach ($countryPayouts as $r): ?>
                <option value="<?= Helpers::e($r['country']) ?>"><?= Helpers::flag($r['country']) ?> <?= Helpers::e($r['country']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="reset-btn" onclick="resetFilter('flt-cp-country','tbl-cp',0)">✕ Reset</button>
    </div>

    <div class="table-wrap">
        <table id="tbl-cp">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Country</th>
                    <?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?>
                    <th>Payout</th>
                    <?php if (Auth::role() === "admin"): ?><th>Margin</th><?php endif; ?>
                    <th>Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($countryPayouts)): ?>
            <?php foreach ($countryPayouts as $r):
                $margin = $r['revenue'] > 0 ? (($r['revenue']-$r['payout'])/$r['revenue']*100) : 0;
            ?>
            <tr>
                <td class="text-sm">
                    <?php if ($r['aff_name']): ?>
                    <div class="fw-bold" style="font-size:12px"><?= Helpers::e($r['aff_name']) ?></div>
                    <code style="font-size:10px;color:#64748B"><?= Helpers::e($r['affiliate_code'] ?? '') ?></code>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">All Affiliates</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm">
                    <?php if ($r['offer_name']): ?>
                    <div style="font-size:12px"><?= Helpers::e($r['offer_name']) ?></div>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">All Offers</span>
                    <?php endif; ?>
                </td>
                <td>
                    <code style="background:#F1F5F9;padding:3px 8px;border-radius:5px;font-size:13px;font-weight:700"><?= Helpers::flag($r['country']) ?> <?= Helpers::e($r['country']) ?></code>
                </td>
                <?php if (Auth::role() === "admin"): ?><td><strong>$<?= number_format($r['revenue'],4) ?></strong></td><?php endif; ?>
                <td><strong style="color:var(--secondary)">$<?= number_format($r['payout'],4) ?></strong></td>
                <td>
                    <span class="badge <?= $margin >= 30 ? 'badge-success' : ($margin >= 10 ? 'badge-warning' : 'badge-danger') ?> no-dot">
                        <?= number_format($margin,1) ?>%
                    </span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this country payout rule?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="delete_country_payout">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ══════════════════════════════════════════════════════════
         TAB 2 — DEVICE PAYOUTS
   ══════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'device_payouts'): ?>

<div class="pm-form-card">
    <h3>📱 Add / Update Country + Device Payout Rule</h3>
    <form method="POST">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_device_payout">
        <div class="pm-inline-form">
            <div class="fg">
                <label>Affiliate <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="affiliate_id" class="aff-select" style="min-width:200px">
                    <option value="">— All Affiliates —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Offer <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="offer_id" style="min-width:200px">
                    <option value="">— All Offers —</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Device <span style="color:#EF4444">*</span></label>
                <select name="device">
                    <option value="desktop">🖥 Desktop</option>
                    <option value="mobile">📱 Mobile</option>
                    <option value="tablet">⬛ Tablet</option>
                </select>
            </div>
            <div class="fg">
                <label>Country Code</label>
                <input type="text" name="country" class="w-cc" placeholder="US" maxlength="2">
            </div>
            <div class="fg">
                <label>Revenue ($)</label>
                <input type="number" name="revenue" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>Payout ($)</label>
                <input type="number" name="payout" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm">💾 Save Rule</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Device Payout Rules</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($devicePayouts) ?> rule<?= count($devicePayouts)!==1?'s':'' ?> configured</span>
    </div>

    <div class="pm-filter-bar" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;background:#FAFBFC">
        <div class="fg">
            <label>🔍 Filter by Device</label>
            <select id="flt-dp-device" onchange="filterTable('tbl-dp', this.value, 1)" style="min-width:160px">
                <option value="">All Devices</option>
                <option value="desktop">🖥 Desktop</option>
                <option value="mobile">📱 Mobile</option>
                <option value="tablet">⬛ Tablet</option>
            </select>
        </div>
        <div class="fg">
            <label>🌍 Filter by Country</label>
            <select id="flt-dp-country" onchange="filterTable('tbl-dp', this.value, 0)" style="min-width:140px">
                <option value="">All Countries</option>
                <?php foreach ($devicePayouts as $r): ?>
                <?php if ($r['country']): ?><option value="<?= Helpers::e($r['country']) ?>"><?= Helpers::flag($r['country']) ?> <?= Helpers::e($r['country']) ?></option><?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="reset-btn" onclick="resetFilter('flt-dp-device','tbl-dp',1);resetFilter('flt-dp-country','tbl-dp',0)">✕ Reset</button>
    </div>

    <div class="table-wrap">
        <table id="tbl-dp">
            <thead>
                <tr><th>Affiliate</th><th>Offer</th><th>Country</th><th>Device</th><?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?><th>Payout</th><?php if (Auth::role() === "admin"): ?><th>Margin</th><?php endif; ?><th>Added</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (!empty($devicePayouts)): ?>
            <?php foreach ($devicePayouts as $r):
                $margin = $r['revenue'] > 0 ? (($r['revenue']-$r['payout'])/$r['revenue']*100) : 0;
                $devIcons = ['desktop'=>'🖥 Desktop','mobile'=>'📱 Mobile','tablet'=>'⬛ Tablet'];
            ?>
            <tr>
                <td class="text-sm">
                    <?php if ($r['aff_name']): ?>
                    <div class="fw-bold" style="font-size:12px"><?= Helpers::e($r['aff_name']) ?></div>
                    <code style="font-size:10px;color:#64748B"><?= Helpers::e($r['affiliate_code'] ?? '') ?></code>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">All</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm">
                    <?php if ($r['offer_name']): ?>
                    <span style="font-size:12px"><?= Helpers::e($r['offer_name']) ?></span>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">All</span>
                    <?php endif; ?>
                </td>
                <td>
                    <code style="background:#F1F5F9;padding:3px 8px;border-radius:5px;font-size:13px;font-weight:700">
                        <?= $r['country'] ? Helpers::flag($r['country']) . ' ' . Helpers::e($r['country']) : '<span style="color:var(--text-muted)">ALL</span>' ?>
                    </code>
                </td>
                <td><span class="device-badge <?= $r['device'] ?>"><?= $devIcons[$r['device']] ?? $r['device'] ?></span></td>
                <?php if (Auth::role() === "admin"): ?><td><strong>$<?= number_format($r['revenue'],4) ?></strong></td><?php endif; ?>
                <td><strong style="color:var(--secondary)">$<?= number_format($r['payout'],4) ?></strong></td>
                <td>
                    <span class="badge <?= $margin >= 30 ? 'badge-success' : ($margin >= 10 ? 'badge-warning' : 'badge-danger') ?> no-dot">
                        <?= number_format($margin,1) ?>%
                    </span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this device payout rule?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="delete_device_payout">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ══════════════════════════════════════════════════════════
         TAB 3 — AFFILIATE CAPPING
   ══════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'aff_capping'): ?>

<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:12px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#92400E">
    <span style="font-size:18px">ℹ</span>
    <div><strong>Daily Cap</strong> — Limits the number of conversions an affiliate can generate per calendar day. Set to <strong>0</strong> for unlimited.</div>
</div>

<div class="pm-form-card">
    <h3>🔒 Set Affiliate Daily Cap</h3>
    <form method="POST">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_aff_cap">
        <div class="pm-inline-form">
            <div class="fg">
                <label>Affiliate <span style="color:#EF4444">*</span></label>
                <select name="affiliate_id" class="aff-select" required>
                    <option value="">— Select Affiliate —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Offer <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="offer_id" style="min-width:200px">
                    <option value="">— All Offers —</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Daily Cap (conversions) <span style="color:#EF4444">*</span></label>
                <input type="number" name="daily_cap" min="0" step="1" placeholder="e.g. 100" style="width:130px" required>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm">💾 Save Rule</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Custom Affiliate Capping</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($affCaps) ?> affiliate<?= count($affCaps)!==1?'s':'' ?> with custom caps</span>
    </div>

    <div class="pm-filter-bar" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;background:#FAFBFC">
        <div class="fg">
            <label>👤 Filter by Affiliate</label>
            <select id="flt-caps-aff" onchange="filterTable('tbl-caps', this.value, 0)">
                <option value="">All Affiliates</option>
                <?php foreach ($affiliateList as $a): ?>
                <option value="<?= Helpers::e($a['label']) ?>"><?= Helpers::e($a['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="reset-btn" onclick="resetFilter('flt-caps-aff','tbl-caps',0)">✕ Reset</button>
    </div>

    <div class="table-wrap">
        <table id="tbl-caps">
            <thead>
                <tr><th>Affiliate</th><th>Offer</th><th>Daily Cap</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (!empty($affCaps)): ?>
            <?php foreach ($affCaps as $r): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted text-sm"><code style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></code></div>
                </td>
                <td class="text-sm">
                    <?php if (!empty($r['offer_name'])): ?>
                    <span><?= Helpers::e($r['offer_name']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">All Offers</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['daily_cap'] == 0): ?>
                    <span class="badge badge-muted no-dot">Unlimited</span>
                    <?php else: ?>
                    <span style="font-size:18px;font-weight:700;color:var(--text)"><?= number_format($r['daily_cap']) ?></span>
                    <span class="text-muted text-sm"> / day</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Remove this affiliate cap?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="delete_aff_cap">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ══════════════════════════════════════════════════════════
         TAB 4 — CUSTOM AFFILIATE PAYOUTS
   ══════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'aff_payouts'): ?>

<div class="pm-form-card">
    <h3>💰 Add / Update Custom Affiliate Payout (Offer-Based)</h3>
    <p style="font-size:13px;color:var(--text-muted);margin:-8px 0 16px">Both <strong>Affiliate</strong> and <strong>Offer</strong> are required. If a rule already exists for this combination it will be updated.</p>
    <form method="POST" id="frmAffPayout">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_aff_payout">
        <div class="pm-inline-form">
            <div class="fg">
                <label>Affiliate <span style="color:#EF4444">*</span></label>
                <select name="affiliate_id" id="selAffPayout" class="aff-select" required onchange="validateAffPayoutForm()">
                    <option value="">— Select Affiliate —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Offer <span style="color:#EF4444">*</span></label>
                <select name="offer_id" id="selOfferPayout" style="min-width:220px" required onchange="validateAffPayoutForm()">
                    <option value="">— Select Offer —</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= $o['is_inhouse'] ? '[IN-HOUSE] ' : '' ?><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Revenue ($)</label>
                <input type="number" name="revenue" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>Payout ($)</label>
                <input type="number" name="payout" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" id="btnAffPayout" class="btn btn-primary btn-sm" disabled style="opacity:.5;cursor:not-allowed">+ Add Rule</button>
            </div>
        </div>
        <div id="affPayoutHint" style="margin-top:10px;font-size:12px;color:#F59E0B;display:none">
            ⚠ A rule for this Affiliate + Offer combination already exists — saving will <strong>update</strong> the existing values.
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Custom Affiliate Payouts (Offer-Based)</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($affPayouts) ?> rule<?= count($affPayouts)!==1?'s':'' ?> configured</span>
    </div>

    <div class="pm-filter-bar" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;background:#FAFBFC">
        <div class="fg">
            <label>👤 Filter by Affiliate</label>
            <select id="flt-ap-aff" onchange="filterMulti('tbl-ap')" style="min-width:220px">
                <option value="">All Affiliates</option>
                <?php foreach ($affiliateList as $a): ?>
                <option value="<?= Helpers::e($a['id']) ?>"><?= Helpers::e($a['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fg">
            <label>🏷 Filter by Offer</label>
            <select id="flt-ap-offer" onchange="filterMulti('tbl-ap')" style="min-width:200px">
                <option value="">All Offers</option>
                <?php foreach ($offerList as $o): ?>
                <option value="<?= Helpers::e($o['id']) ?>"><?= $o['is_inhouse'] ? '[IN-HOUSE] ' : '' ?><?= Helpers::e($o['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="reset-btn" onclick="document.getElementById('flt-ap-aff').value='';document.getElementById('flt-ap-offer').value='';filterMulti('tbl-ap')">✕ Reset</button>
        <div id="flt-ap-badge" style="display:none" class="filter-badge">
            <span id="flt-ap-badge-text"></span>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tbl-ap">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?>
                    <th>Payout</th>
                    <?php if (Auth::role() === "admin"): ?><th>Margin</th><?php endif; ?>
                    <th>Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($affPayouts)): ?>
            <?php foreach ($affPayouts as $r):
                $margin = $r['revenue'] > 0 ? (($r['revenue']-$r['payout'])/$r['revenue']*100) : 0;
            ?>
            <tr data-aff-id="<?= (int)$r['affiliate_id'] ?>" data-offer-id="<?= (int)$r['offer_id'] ?>">
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted text-sm"><code style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></code></div>
                </td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['offer_name'] ?: '— Offer deleted —') ?></div>
                    <div class="text-muted text-sm">ID #<?= (int)$r['offer_id'] ?></div>
                </td>
                <?php if (Auth::role() === "admin"): ?><td><strong>$<?= number_format($r['revenue'],4) ?></strong></td><?php endif; ?>
                <td><strong style="color:var(--secondary)">$<?= number_format($r['payout'],4) ?></strong></td>
                <td>
                    <span class="badge <?= $margin >= 30 ? 'badge-success' : ($margin >= 10 ? 'badge-warning' : 'badge-danger') ?> no-dot">
                        <?= number_format($margin,1) ?>%
                    </span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this payout rule?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="delete_aff_payout">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Smartlink Custom Payouts ──────────────────────────────────────────── -->
<div class="pm-form-card" style="margin-top:24px">
    <h3>🔗 Add / Update Custom Affiliate Payout (Smartlink-Based)</h3>
    <p style="font-size:13px;color:var(--text-muted);margin:-8px 0 16px">Set a custom payout amount shown to a specific affiliate for a Smartlink. Both fields are required.</p>
    <form method="POST" id="frmSlPayout">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_aff_sl_payout">
        <div class="pm-inline-form">
            <div class="fg">
                <label>Affiliate <span style="color:#EF4444">*</span></label>
                <select name="affiliate_id" id="selSlAff" class="aff-select" required onchange="validateSlPayoutForm()">
                    <option value="">— Select Affiliate —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Smartlink <span style="color:#EF4444">*</span></label>
                <select name="smartlink_id" id="selSlLink" style="min-width:220px" required onchange="validateSlPayoutForm()">
                    <option value="">— Select Smartlink —</option>
                    <?php foreach ($smartlinkList as $sl): ?>
                    <option value="<?= $sl['id'] ?>"><?= Helpers::e($sl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Revenue ($)</label>
                <input type="number" name="sl_revenue" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>Payout ($)</label>
                <input type="number" name="sl_payout" class="w-sm" step="0.0001" min="0" placeholder="0.00" required>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" id="btnSlPayout" class="btn btn-primary btn-sm" disabled style="opacity:.5;cursor:not-allowed">+ Add Rule</button>
            </div>
        </div>
        <div id="slPayoutHint" style="margin-top:10px;font-size:12px;color:#F59E0B;display:none">
            ⚠ A rule for this Affiliate + Smartlink combination already exists — saving will <strong>update</strong> the existing values.
        </div>
    </form>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header">
        <span class="card-title">Custom Affiliate Payouts (Smartlink-Based)</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($affSmartlinkPayouts) ?> rule<?= count($affSmartlinkPayouts)!==1?'s':'' ?> configured</span>
    </div>
    <div class="table-wrap">
        <table id="tbl-sl-ap">
            <thead>
                <tr>
                    <th>Affiliate</th>
                    <th>Smartlink</th>
                    <?php if (Auth::role() === "admin"): ?><th>Revenue</th><?php endif; ?>
                    <th>Payout</th>
                    <?php if (Auth::role() === "admin"): ?><th>Margin</th><?php endif; ?>
                    <th>Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($affSmartlinkPayouts)): ?>
            <?php foreach ($affSmartlinkPayouts as $r):
                $margin = $r['revenue'] > 0 ? (($r['revenue']-$r['payout'])/$r['revenue']*100) : 0;
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted text-sm"><code style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></code></div>
                </td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($r['sl_name'] ?: '— Smartlink deleted —') ?></div>
                    <div class="text-muted text-sm">ID #<?= (int)$r['smartlink_id'] ?></div>
                </td>
                <?php if (Auth::role() === "admin"): ?><td><strong>$<?= number_format($r['revenue'],4) ?></strong></td><?php endif; ?>
                <td><strong style="color:var(--secondary)">$<?= number_format($r['payout'],4) ?></strong></td>
                <td>
                    <span class="badge <?= $margin >= 30 ? 'badge-success' : ($margin >= 10 ? 'badge-warning' : 'badge-danger') ?> no-dot">
                        <?= number_format($margin,1) ?>%
                    </span>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this smartlink payout rule?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="delete_aff_sl_payout">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted" style="padding:20px">No smartlink payout rules configured yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Build existing combos for duplicate detection
var existingAffPayouts = <?= json_encode(array_map(fn($r) => ['aff'=>(string)$r['affiliate_id'],'offer'=>(string)$r['offer_id']], $affPayouts)) ?>;
var existingSlPayouts  = <?= json_encode(array_map(fn($r) => ['aff'=>(string)$r['affiliate_id'],'sl'=>(string)$r['smartlink_id']], $affSmartlinkPayouts)) ?>;

function validateAffPayoutForm() {
    var affId   = document.getElementById('selAffPayout').value;
    var offerId = document.getElementById('selOfferPayout').value;
    var btn     = document.getElementById('btnAffPayout');
    var hint    = document.getElementById('affPayoutHint');
    var ready   = affId !== '' && offerId !== '';
    btn.disabled = !ready;
    btn.style.opacity = ready ? '1' : '.5';
    btn.style.cursor  = ready ? 'pointer' : 'not-allowed';
    if (ready) {
        var isDupe = existingAffPayouts.some(function(r){ return r.aff===affId && r.offer===offerId; });
        hint.style.display = isDupe ? 'block' : 'none';
        btn.textContent = isDupe ? '💾 Update Rule' : '+ Add Rule';
    } else {
        hint.style.display = 'none';
        btn.textContent = '+ Add Rule';
    }
}

function validateSlPayoutForm() {
    var affId = document.getElementById('selSlAff').value;
    var slId  = document.getElementById('selSlLink').value;
    var btn   = document.getElementById('btnSlPayout');
    var hint  = document.getElementById('slPayoutHint');
    var ready = affId !== '' && slId !== '';
    btn.disabled = !ready;
    btn.style.opacity = ready ? '1' : '.5';
    btn.style.cursor  = ready ? 'pointer' : 'not-allowed';
    if (ready) {
        var isDupe = existingSlPayouts.some(function(r){ return r.aff===affId && r.sl===slId; });
        hint.style.display = isDupe ? 'block' : 'none';
        btn.textContent = isDupe ? '💾 Update Rule' : '+ Add Rule';
    } else {
        hint.style.display = 'none';
        btn.textContent = '+ Add Rule';
    }
}

// When a filter dropdown changes, also pre-fill the add-form selects
document.addEventListener('DOMContentLoaded', function() {
    var fltAff   = document.getElementById('flt-ap-aff');
    var fltOffer = document.getElementById('flt-ap-offer');
    if (fltAff) {
        fltAff.addEventListener('change', function() {
            var formSel = document.getElementById('selAffPayout');
            if (formSel && this.value) { formSel.value = this.value; validateAffPayoutForm(); }
        });
    }
    if (fltOffer) {
        fltOffer.addEventListener('change', function() {
            var formSel = document.getElementById('selOfferPayout');
            if (formSel && this.value) { formSel.value = this.value; validateAffPayoutForm(); }
        });
    }
});
</script>

<?php /* ══════════════════════════════════════════════════════════
         TAB 7 — CONVERSION AUTO HIDE OPTIMIZATION
   ══════════════════════════════════════════════════════════ */ ?>
<?php elseif ($tab === 'conversion_optimize'): ?>

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#1D4ED8">
    <span style="font-size:18px">⚡</span>
    <div>
        <strong>Conversion Auto-Hide Optimization</strong> — Set a percentage of conversions to automatically hide per offer.
        For example, a value of <strong>20%</strong> will hide 1 in every 5 conversions from affiliates for that offer, preserving margin.
        Hidden conversions are invisible to affiliates and their managers.
    </div>
</div>

<div class="pm-form-card">
    <h3>⚡ Add / Update Optimize Rule</h3>
    <form method="POST" id="optForm">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="payout_action" value="save_optimize_rule">
        <div class="pm-inline-form" style="align-items:center">
            <div class="fg">
                <label>Offer <span style="color:#EF4444">*</span></label>
                <select name="offer_id" style="min-width:220px" required>
                    <option value="">— Select Offer —</option>
                    <?php foreach ($offerList as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Affiliate <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <select name="affiliate_id" class="aff-select" style="min-width:200px">
                    <option value="">— All Affiliates —</option>
                    <?php foreach ($affiliateList as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg" style="align-items:center">
                <label>Optimize Value: <strong id="optValLabel">10%</strong></label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="range" class="opt-slider" name="optimize_value" id="optSlider" min="1" max="100" value="10" oninput="document.getElementById('optValLabel').textContent=this.value+'%';document.getElementById('optValNum').value=this.value">
                    <input type="number" id="optValNum" min="1" max="100" step="0.01" value="10" style="width:70px;height:38px;padding:0 8px;border:1px solid var(--border);border-radius:6px;font-size:13px" oninput="document.getElementById('optSlider').value=this.value;document.getElementById('optValLabel').textContent=this.value+'%'">
                    <span style="font-size:13px;color:var(--text-muted)">%</span>
                </div>
            </div>
            <div class="fg">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm">💾 Save Rule</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Conversion Auto-Hide Optimization Rules</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($optimizeRules) ?> rule<?= count($optimizeRules)!==1?'s':'' ?> — <?= count(array_filter($optimizeRules, fn($r) => $r['is_active'])) ?> active</span>
    </div>

    <div class="pm-filter-bar" style="border-radius:0;border-left:none;border-right:none;border-top:none;margin-bottom:0;background:#FAFBFC">
        <div class="fg">
            <label>🏷 Filter by Offer</label>
            <select id="flt-opt-offer" onchange="filterTable('tbl-opt', this.value, 1)" style="min-width:220px">
                <option value="">All Offers</option>
                <?php foreach ($offerList as $o): ?>
                <option value="<?= Helpers::e($o['name']) ?>"><?= Helpers::e($o['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fg">
            <label>📊 Filter by Status</label>
            <select id="flt-opt-status" onchange="filterTable('tbl-opt', this.value, 4)" style="min-width:130px">
                <option value="">All Statuses</option>
                <option value="Active">● Active</option>
                <option value="Paused">○ Paused</option>
            </select>
        </div>
        <button class="reset-btn" onclick="resetFilter('flt-opt-offer','tbl-opt',1);resetFilter('flt-opt-status','tbl-opt',4)">✕ Reset</button>
    </div>

    <div class="table-wrap">
        <table id="tbl-opt">
            <thead>
                <tr><th>Offer ID</th><th>Offer Name</th><th>Affiliate</th><th>Optimize Value</th><th>Status</th><th>Added</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!empty($optimizeRules)): ?>
            <?php foreach ($optimizeRules as $r): ?>
            <tr>
                <td><code style="background:#F1F5F9;padding:3px 8px;border-radius:5px;font-size:13px">#<?= $r['offer_id'] ?></code></td>
                <td class="fw-bold"><?= Helpers::e($r['offer_name'] ?: '— Offer not found —') ?></td>
                <td class="text-sm">
                    <?php if (!empty($r['aff_name'])): ?>
                    <div class="fw-bold" style="font-size:12px"><?= Helpers::e($r['aff_name']) ?></div>
                    <code style="font-size:10px;color:#64748B"><?= Helpers::e($r['affiliate_code'] ?? '') ?></code>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">All Affiliates</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="background:#E2E8F0;border-radius:20px;height:8px;width:120px;overflow:hidden">
                            <div style="background:<?= $r['optimize_value'] >= 40 ? '#EF4444' : ($r['optimize_value'] >= 20 ? '#F59E0B' : '#6366F1') ?>;height:100%;width:<?= min(100, $r['optimize_value']) ?>%;border-radius:20px;transition:width .3s"></div>
                        </div>
                        <span style="font-size:15px;font-weight:700;color:<?= $r['optimize_value'] >= 40 ? '#B91C1C' : ($r['optimize_value'] >= 20 ? '#92400E' : 'var(--primary)') ?>">
                            <?= number_format($r['optimize_value'], 2) ?>%
                        </span>
                    </div>
                </td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="payout_action" value="toggle_optimize_rule">
                        <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="badge <?= $r['is_active'] ? 'badge-success' : 'badge-muted' ?>" style="border:none;cursor:pointer;font-size:12px;font-weight:600;padding:4px 12px">
                            <?= $r['is_active'] ? '● Active' : '○ Paused' ?>
                        </button>
                    </form>
                </td>
                <td class="text-sm text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="/admin/offers/<?= $r['offer_id'] ?>/overview" class="btn btn-secondary btn-sm" title="View Offer">📊 Overview</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this optimization rule?')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="payout_action" value="delete_optimize_rule">
                            <input type="hidden" name="rule_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
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

<?php endif; ?>

<script>
// ── DataTable init ────────────────────────────────────────────────────────
$(function() {
    var tableIds = ['tbl-cp','tbl-dp','tbl-caps','tbl-ap','tbl-opt'];
    tableIds.forEach(function(id) {
        var $t = $('#'+id);
        if (!$t.length) return;
        $t.DataTable({
            destroy: true,
            pageLength: 25,
            order: [],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                emptyTable: '<span style="color:#94A3B8;font-size:13px">No rules configured yet — use the form above to add one.</span>',
                zeroRecords: 'No matching rules found.'
            }
        });
    });
});

// ── Single-column DataTable filter ───────────────────────────────────────
// Used for tabs where we filter by one column value (country, device, affiliate name)
function filterTable(tableId, value, colIndex) {
    var table = $('#' + tableId).DataTable();
    if (!table) return;
    table.column(colIndex).search(value ? '^' + $.fn.dataTable.util.escapeRegex(value) + '$' : '', true, false).draw();
}

function resetFilter(selectId, tableId, colIndex) {
    document.getElementById(selectId).value = '';
    filterTable(tableId, '', colIndex);
}

// ── Multi-column filter for tbl-ap (affiliate_id + offer_id via data attrs) ──
// Because DataTable text search on the affiliate name column can be ambiguous
// when names share words. We use data attributes for exact ID matching.
function filterMulti(tableId) {
    var affId   = document.getElementById('flt-ap-aff').value;
    var offerId = document.getElementById('flt-ap-offer').value;
    var badge   = document.getElementById('flt-ap-badge');
    var badgeTxt= document.getElementById('flt-ap-badge-text');

    // Get affiliate label for display
    var affSel  = document.getElementById('flt-ap-aff');
    var offerSel= document.getElementById('flt-ap-offer');
    var affLabel  = affSel.options[affSel.selectedIndex].text;
    var offerLabel= offerSel.options[offerSel.selectedIndex].text;

    // Show/hide badge
    if (affId || offerId) {
        var parts = [];
        if (affId)   parts.push('Aff: ' + affLabel);
        if (offerId) parts.push('Offer: ' + offerLabel);
        badgeTxt.textContent = parts.join(' · ');
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }

    // Filter table rows directly (bypass DataTable search for exact ID match)
    var tbody = document.querySelector('#tbl-ap tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr');
    var visCount = 0;
    rows.forEach(function(row) {
        var rowAff   = row.getAttribute('data-aff-id')   || '';
        var rowOffer = row.getAttribute('data-offer-id') || '';
        var show = true;
        if (affId   && rowAff   !== affId)   show = false;
        if (offerId && rowOffer !== offerId)  show = false;
        row.style.display = show ? '' : 'none';
        if (show) visCount++;
    });

    // Update the "rows" count in header
    var countEl = document.querySelector('#tbl-ap').closest('.card').querySelector('.text-muted.text-sm');
    if (countEl && (affId || offerId)) {
        countEl.textContent = visCount + ' rule' + (visCount !== 1 ? 's' : '') + ' shown (filtered)';
    }
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
