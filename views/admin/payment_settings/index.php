<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Payment Settings</h1><p>Manage payment methods, terms, and manager commissions</p></div>
</div>

<!-- Tab Nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0;flex-wrap:wrap">
    <?php foreach(['methods'=>'Payment Methods','terms'=>'Payment Terms','commission'=>'Manager Commission','payout_info'=>'&#128179; Payout Info'] as $k=>$lbl): ?>
    <a href="/admin/payment-settings?tab=<?= $k ?>"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab===$k?'#4F46E5':'transparent' ?>;margin-bottom:-2px;color:<?= $activeTab===$k?'#4F46E5':'#64748B' ?>">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ─── PAYMENT METHODS TAB ─── -->
<?php if ($activeTab === 'methods'): ?>

<?php
// Method type labels + icons for display
$_pmTypeLabels = [
    'payoneer' => ['label'=>'Payoneer',       'icon'=>'💳', 'color'=>'#FF4800'],
    'paypal'   => ['label'=>'PayPal',          'icon'=>'🅿',  'color'=>'#003087'],
    'wire'     => ['label'=>'Wire Transfer',   'icon'=>'🏦', 'color'=>'#059669'],
    'wise'     => ['label'=>'Wise',            'icon'=>'🌐', 'color'=>'#9FE870'],
    'crypto'   => ['label'=>'Cryptocurrency',  'icon'=>'₿',  'color'=>'#F7931A'],
    'custom'   => ['label'=>'Custom',          'icon'=>'⚙',  'color'=>'#64748B'],
];
?>

<div class="grid-2 mb-3">
    <!-- ADD FORM -->
    <div class="card">
        <div class="card-header"><span class="card-title">Add Payment Method</span></div>
        <div class="card-body">
            <form method="POST" id="addPmForm">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="methods">
                <input type="hidden" name="pm_action" value="add">

                <div class="form-group">
                    <label>Method Type</label>
                    <select name="pm_method_type" class="form-control" onchange="pmTypeChanged(this,'add')" id="addPmType">
                        <option value="custom">— Custom (free-form) —</option>
                        <option value="payoneer">💳 Payoneer</option>
                        <option value="paypal">🅿 PayPal</option>
                        <option value="wire">🏦 Wire Transfer</option>
                        <option value="wise">🌐 Wise</option>
                        <option value="crypto">₿ Cryptocurrency</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Method Name *</label>
                    <input type="text" name="pm_name" id="addPmName" class="form-control" required placeholder="e.g. PayPal, Wire Transfer, USDT">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="pm_description" class="form-control" placeholder="Short description shown to affiliates">
                </div>
                <div class="form-group">
                    <label>Payment Instructions</label>
                    <textarea name="pm_instructions" id="addPmInstructions" class="form-control" rows="3" placeholder="Instructions shown to affiliates when they select this method"></textarea>
                </div>

                <!-- Type-specific info panel (read-only, shows which fields affiliates will fill) -->
                <div id="addPmFieldPreview" style="display:none;margin-bottom:16px">
                    <div style="padding:10px 14px;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px">
                        <div style="font-size:12px;font-weight:600;color:#0369A1;margin-bottom:6px">Fields affiliates will fill for this method type:</div>
                        <div id="addPmFieldList" style="font-size:12px;color:#0C4A6E;line-height:1.8"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Method</button>
            </form>
        </div>
    </div>

    <!-- ACTIVE METHODS LIST -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <span class="card-title">Active Payment Methods</span>
            <span class="text-muted" style="font-size:12px"><?= count($paymentMethods) ?> method<?= count($paymentMethods)!==1?'s':'' ?></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($paymentMethods)): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No payment methods yet</td></tr>
                <?php else: ?>
                <?php foreach ($paymentMethods as $pm):
                    $pmType = $pm['method_type'] ?? 'custom';
                    $pmTypeInfo = $_pmTypeLabels[$pmType] ?? $_pmTypeLabels['custom'];
                ?>
                <tr>
                    <td>
                        <div class="fw-bold" style="display:flex;align-items:center;gap:6px">
                            <span><?= Helpers::e($pm['name']) ?></span>
                            <?php if (!empty($pm['is_default'])): ?>
                            <span style="font-size:10px;padding:1px 6px;border-radius:8px;background:#EDE9FE;color:#6D28D9;font-weight:700">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($pm['description']): ?><div class="text-sm text-muted"><?= Helpers::e($pm['description']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:12px;padding:2px 8px;border-radius:8px;background:#F8FAFC;border:1px solid #E2E8F0;font-weight:600">
                            <?= $pmTypeInfo['icon'] ?> <?= Helpers::e($pmTypeInfo['label']) ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= $pm['is_active'] ? 'success' : 'muted' ?>"><?= $pm['is_active'] ? '● Active' : '○ Inactive' ?></span></td>
                    <td style="white-space:nowrap">
                        <button class="btn btn-secondary btn-sm" onclick="openEditPm(<?= htmlspecialchars(json_encode($pm), ENT_QUOTES) ?>)" style="margin-right:2px">Edit</button>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="methods">
                            <input type="hidden" name="pm_action" value="toggle">
                            <input type="hidden" name="pm_id" value="<?= $pm['id'] ?>">
                            <button class="btn btn-<?= $pm['is_active'] ? 'warning' : 'success' ?> btn-sm" style="margin-right:2px"><?= $pm['is_active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="methods">
                            <input type="hidden" name="pm_action" value="delete">
                            <input type="hidden" name="pm_id" value="<?= $pm['id'] ?>">
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this payment method?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Method Type Field Reference Card -->
<div class="card mb-3">
    <div class="card-header" style="cursor:pointer" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
        <span class="card-title">&#9432; Payment Method Field Reference</span>
        <span class="text-muted" style="font-size:12px;float:right">Click to expand — shows which fields affiliates fill per method type</span>
    </div>
    <div style="display:none">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;padding:20px">
        <?php
        $fieldRef = [
            'payoneer' => ['icon'=>'💳','color'=>'#FF4800','bg'=>'#FFF5F2','fields'=>['Account Holder Name','Email / Account ID']],
            'paypal'   => ['icon'=>'🅿','color'=>'#003087','bg'=>'#F0F4FF','fields'=>['Account Holder Name','Email / Account ID']],
            'wise'     => ['icon'=>'🌐','color'=>'#00B2A9','bg'=>'#F0FFF4','fields'=>['Account Holder Name','Email / Account ID']],
            'wire'     => ['icon'=>'🏦','color'=>'#059669','bg'=>'#ECFDF5','fields'=>['Account Holder Name','Bank Name','Account Number','IBAN / SWIFT Code','Routing Number','Bank Address','Branch Name']],
            'crypto'   => ['icon'=>'₿','color'=>'#F7931A','bg'=>'#FFFBEB','fields'=>['Cryptocurrency (USDT / BTC / ETH / LTC / BNB / TRX / XRP / USDC)','Wallet Address','Network Type (TRC20 / ERC20 / BEP20 / Bitcoin / Ethereum / Solana)']],
            'custom'   => ['icon'=>'⚙','color'=>'#64748B','bg'=>'#F8FAFC','fields'=>['Free-form text (affiliates fill anything)']],
        ];
        foreach ($fieldRef as $key => $ref):
        ?>
        <div style="border:1px solid <?= $ref['color'] ?>33;border-radius:10px;background:<?= $ref['bg'] ?>;padding:14px">
            <div style="font-weight:700;font-size:13px;color:<?= $ref['color'] ?>;margin-bottom:8px"><?= $ref['icon'] ?> <?= $_pmTypeLabels[$key]['label'] ?></div>
            <ul style="margin:0;padding-left:18px;font-size:12px;color:#374151;line-height:1.8">
                <?php foreach ($ref['fields'] as $f): ?><li><?= Helpers::e($f) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
    </div>
</div>

<!-- ─── EDIT METHOD MODAL ─── -->
<div id="editPmOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:14px;padding:28px;width:540px;max-width:94vw;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <button onclick="closeEditPm()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#64748B">&#10005;</button>
        <h3 style="margin:0 0 20px;font-size:18px;color:#1E293B">Edit Payment Method</h3>
        <form method="POST" id="editPmForm">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="methods">
            <input type="hidden" name="pm_action" value="edit">
            <input type="hidden" name="pm_id" id="editPmId">

            <div class="form-group">
                <label>Method Type</label>
                <select name="pm_method_type" class="form-control" onchange="pmTypeChanged(this,'edit')" id="editPmType">
                    <option value="custom">— Custom (free-form) —</option>
                    <option value="payoneer">💳 Payoneer</option>
                    <option value="paypal">🅿 PayPal</option>
                    <option value="wire">🏦 Wire Transfer</option>
                    <option value="wise">🌐 Wise</option>
                    <option value="crypto">₿ Cryptocurrency</option>
                </select>
            </div>
            <div class="form-group">
                <label>Method Name *</label>
                <input type="text" name="pm_name" id="editPmName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="pm_description" id="editPmDescription" class="form-control">
            </div>
            <div class="form-group">
                <label>Payment Instructions</label>
                <textarea name="pm_instructions" id="editPmInstructions" class="form-control" rows="4"></textarea>
            </div>

            <div id="editPmFieldPreview" style="display:none;margin-bottom:16px">
                <div style="padding:10px 14px;background:#F0F9FF;border:1px solid #BAE6FD;border-radius:8px">
                    <div style="font-size:12px;font-weight:600;color:#0369A1;margin-bottom:6px">Fields affiliates will fill for this method type:</div>
                    <div id="editPmFieldList" style="font-size:12px;color:#0C4A6E;line-height:1.8"></div>
                </div>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeEditPm()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── PAYMENT TERMS TAB ─── -->
<?php elseif ($activeTab === 'terms'): ?>
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Set Payment Terms</span></div>
        <div class="card-body">
            <form method="POST" id="termsForm">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="terms">
                <div class="form-group">
                    <label>Payment Terms</label>
                    <select name="payment_terms" class="form-control">
                        <option value="weekly">Weekly</option>
                        <option value="net15">Net-15</option>
                        <option value="net30">Net-30</option>
                        <option value="monthly" selected>Monthly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Apply To</label>
                    <div style="display:flex;gap:16px;margin-top:6px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                            <input type="radio" name="scope" value="all" onchange="toggleAffiliateList(this)"> All Affiliates
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                            <input type="radio" name="scope" value="selected" checked onchange="toggleAffiliateList(this)"> Selected Affiliates
                        </label>
                    </div>
                </div>
                <div id="affiliateListSection">
                    <div class="form-group">
                        <label>Select Affiliates</label>
                        <div style="max-height:220px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:6px;padding:8px">
                        <?php foreach ($affiliates as $a): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:5px 8px;cursor:pointer;font-size:13px;border-radius:4px">
                            <input type="checkbox" name="affiliate_ids[]" value="<?= $a['id'] ?>">
                            <span><?= Helpers::e($a['name']) ?></span>
                            <span class="text-muted" style="font-size:11px;margin-left:auto"><?= Helpers::e($a['email']) ?></span>
                            <span class="badge badge-info" style="font-size:10px"><?= Helpers::e($a['payment_terms']) ?></span>
                        </label>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Apply Payment Terms</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Current Payment Terms</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Affiliate</th><th>Terms</th></tr></thead>
                <tbody>
                <?php if (empty($affiliates)): ?>
                <tr><td colspan="2" class="text-center text-muted" style="padding:24px">No active affiliates found</td></tr>
                <?php else: ?>
                <?php foreach ($affiliates as $a): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-sm"><?= Helpers::e($a['name']) ?></div>
                        <div class="text-muted" style="font-size:11px"><?= Helpers::e($a['email']) ?></div>
                    </td>
                    <td><span class="badge badge-info"><?= Helpers::e($a['payment_terms']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── MANAGER COMMISSION TAB ─── -->
<?php elseif ($activeTab === 'commission'): ?>
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Manager Commission Rates</span></div>
        <div class="card-body">
            <?php if (empty($managers)): ?>
            <p class="text-muted">No affiliate managers found. <a href="/admin/affiliate-managers/create">Create one</a>.</p>
            <?php else: ?>
            <?php foreach ($managers as $mgr): ?>
            <form method="POST" style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #F1F5F9">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="commission">
                <input type="hidden" name="manager_id" value="<?= $mgr['user_id'] ?>">
                <div style="flex:1">
                    <div class="fw-bold text-sm"><?= Helpers::e($mgr['name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($mgr['email']) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <input type="number" name="commission_rate" class="form-control" step="0.01" min="0" max="100" style="width:90px" value="<?= Helpers::e($mgr['commission_rate']) ?>">
                    <span class="text-muted" style="font-size:13px">%</span>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </form>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Affiliate Email Change Permission</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Affiliate</th><th>Email Change</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($affiliates)): ?>
                <tr><td colspan="3" class="text-center text-muted" style="padding:24px">No active affiliates found</td></tr>
                <?php else: ?>
                <?php foreach ($affiliates as $a): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-sm"><?= Helpers::e($a['name']) ?></div>
                        <div class="text-muted" style="font-size:11px"><?= Helpers::e($a['email']) ?></div>
                    </td>
                    <td><span class="badge badge-<?= $a['allow_email_change'] ? 'success' : 'muted' ?>"><?= $a['allow_email_change'] ? 'Allowed' : 'Locked' ?></span></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="tab" value="email_perm">
                            <input type="hidden" name="affiliate_id" value="<?= $a['id'] ?>">
                            <?php if (!$a['allow_email_change']): ?>
                            <input type="hidden" name="allow_email_change" value="1">
                            <button class="btn btn-success btn-sm">Allow</button>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm">Revoke</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Offer-Specific Commission Overrides (below Manager Commission Rates) ─── -->
<div class="card mb-3">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title">Offer-Specific Commission Overrides</span>
        <span class="text-muted" style="font-size:12px">Overrides the default manager rate for a specific offer only</span>
    </div>
    <div class="card-body" style="border-bottom:1px solid #F1F5F9">
        <form method="POST" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="offer_commission">
            <input type="hidden" name="oc_action" value="save">
            <div class="form-group" style="margin:0;flex:1;min-width:160px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Manager</label>
                <select name="oc_manager_id" class="form-control" required>
                    <option value="">— Select Manager —</option>
                    <?php foreach ($managers as $mgr): ?>
                    <option value="<?= $mgr['mgr_id'] ?>"><?= Helpers::e($mgr['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:2;min-width:200px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Offer</label>
                <select name="oc_offer_id" class="form-control" required>
                    <option value="">— Select Offer —</option>
                    <?php foreach ($offers as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Commission Rate</label>
                <div style="display:flex;align-items:center;gap:6px">
                    <input type="number" name="oc_rate" class="form-control" step="0.01" min="0" max="100" placeholder="0.00" required style="width:90px">
                    <span class="text-muted" style="font-size:13px">%</span>
                </div>
            </div>
            <div style="flex-shrink:0">
                <button type="submit" class="btn btn-primary">Add / Update Override</button>
            </div>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Manager</th>
                    <th>Offer</th>
                    <th style="width:130px">Override Rate</th>
                    <th style="width:80px">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($offerCommissions)): ?>
            <tr><td colspan="4" class="text-center text-muted" style="padding:20px">No offer-specific overrides configured yet</td></tr>
            <?php else: ?>
            <?php foreach ($offerCommissions as $oc): ?>
            <tr>
                <td>
                    <div class="fw-bold text-sm"><?= Helpers::e($oc['manager_name']) ?></div>
                </td>
                <td>
                    <div class="text-sm"><?= Helpers::e($oc['offer_name']) ?></div>
                </td>
                <td>
                    <span style="font-weight:700;color:#4F46E5;font-size:14px"><?= number_format((float)$oc['commission_rate'], 2) ?>%</span>
                    <span class="text-muted" style="font-size:11px;margin-left:4px">(override)</span>
                </td>
                <td>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Remove this override?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="tab" value="offer_commission">
                        <input type="hidden" name="oc_action" value="delete">
                        <input type="hidden" name="oc_id" value="<?= $oc['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── PAYOUT INFO TAB ─── -->
<?php elseif ($activeTab === 'payout_info'): ?>

<?php
// Build a map: method name → method_type, for structured field rendering
$_pmTypeMap = [];
foreach ($paymentMethods as $_pm) {
    $_pmTypeMap[$_pm['name']] = $_pm['method_type'] ?? 'custom';
}

// Helper: render structured payment detail fields for a given entity
// $details is the stored JSON or plain text; $entityId and $prefix used for IDs
function renderPaymentDetailFields(string $methodName, string $pmTypeMap, string $details, string $prefix, int $entityId): void {
    $type   = $pmTypeMap; // already resolved method_type string
    $parsed = [];
    if ($details && $details[0] === '{') {
        $parsed = json_decode($details, true) ?: [];
    }

    // For backward compat: if details is plain text and type needs structured, show legacy + structured
    $legacyText = (!empty($details) && $details[0] !== '{') ? $details : '';

    if (in_array($type, ['payoneer','paypal','wise'])) {
        // 2 fields: account_holder_name, email
        $fields = [
            ['name'=>'account_holder_name', 'label'=>'Account Holder Name', 'type'=>'text', 'placeholder'=>'Full name on account'],
            ['name'=>'email',               'label'=>'Email / Account ID',   'type'=>'email','placeholder'=>'account@example.com'],
        ];
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
        foreach ($fields as $f) {
            $val = htmlspecialchars($parsed[$f['name']] ?? '', ENT_QUOTES);
            echo '<div>';
            echo '<label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:2px">' . $f['label'] . '</label>';
            echo '<input type="' . $f['type'] . '" name="pd_field[' . $f['name'] . ']" class="form-control" style="font-size:12px" placeholder="' . $f['placeholder'] . '" value="' . $val . '">';
            echo '</div>';
        }
        echo '</div>';

    } elseif ($type === 'wire') {
        // 7 fields for wire transfer
        $fields = [
            ['name'=>'account_holder_name','label'=>'Account Holder Name','type'=>'text',     'full'=>false,'placeholder'=>'Full legal name'],
            ['name'=>'bank_name',          'label'=>'Bank Name',          'type'=>'text',     'full'=>false,'placeholder'=>'e.g. HSBC, Chase, Deutsche Bank'],
            ['name'=>'account_number',     'label'=>'Account Number',     'type'=>'text',     'full'=>false,'placeholder'=>'Bank account number'],
            ['name'=>'iban_swift',         'label'=>'IBAN / SWIFT Code',  'type'=>'text',     'full'=>false,'placeholder'=>'e.g. GB29NWBK60161331926819'],
            ['name'=>'routing_number',     'label'=>'Routing Number',     'type'=>'text',     'full'=>false,'placeholder'=>'ABA routing number'],
            ['name'=>'branch_name',        'label'=>'Branch Name',        'type'=>'text',     'full'=>false,'placeholder'=>'Branch name (optional)'],
            ['name'=>'bank_address',       'label'=>'Bank Address',       'type'=>'textarea', 'full'=>true, 'placeholder'=>'Full bank address including city and country'],
        ];
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
        foreach ($fields as $f) {
            $val = htmlspecialchars($parsed[$f['name']] ?? '', ENT_QUOTES);
            $span = $f['full'] ? ' style="grid-column:1/-1"' : '';
            echo '<div' . $span . '>';
            echo '<label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:2px">' . $f['label'] . '</label>';
            if ($f['type'] === 'textarea') {
                echo '<textarea name="pd_field[' . $f['name'] . ']" class="form-control" style="font-size:12px;resize:vertical" rows="2" placeholder="' . $f['placeholder'] . '">' . $val . '</textarea>';
            } else {
                echo '<input type="text" name="pd_field[' . $f['name'] . ']" class="form-control" style="font-size:12px" placeholder="' . $f['placeholder'] . '" value="' . $val . '">';
            }
            echo '</div>';
        }
        echo '</div>';

    } elseif ($type === 'crypto') {
        $cryptoOptions  = ['USDT','Bitcoin (BTC)','Ethereum (ETH)','Litecoin (LTC)','BNB (BEP20)','TRON (TRX)','XRP (Ripple)','USDC','Dogecoin (DOGE)','Solana (SOL)','Other'];
        $networkOptions = ['TRC20 (Tron)','ERC20 (Ethereum)','BEP20 (BSC)','Bitcoin Network','Litecoin Network','Ethereum Network','Solana Network','XRP Ledger','Other'];
        $selCrypto  = $parsed['crypto_type']    ?? '';
        $selNetwork = $parsed['network_type']   ?? '';
        $wallet     = htmlspecialchars($parsed['wallet_address'] ?? '', ENT_QUOTES);
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
        // Crypto type
        echo '<div><label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:2px">Cryptocurrency</label>';
        echo '<select name="pd_field[crypto_type]" class="form-control" style="font-size:12px"><option value="">— Select —</option>';
        foreach ($cryptoOptions as $co) {
            $sel = $selCrypto === $co ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($co,ENT_QUOTES) . '"' . $sel . '>' . htmlspecialchars($co) . '</option>';
        }
        echo '</select></div>';
        // Network
        echo '<div><label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:2px">Network Type</label>';
        echo '<select name="pd_field[network_type]" class="form-control" style="font-size:12px"><option value="">— Select —</option>';
        foreach ($networkOptions as $no) {
            $sel = $selNetwork === $no ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($no,ENT_QUOTES) . '"' . $sel . '>' . htmlspecialchars($no) . '</option>';
        }
        echo '</select></div>';
        // Wallet address (full width)
        echo '<div style="grid-column:1/-1"><label style="font-size:11px;font-weight:600;color:#64748B;display:block;margin-bottom:2px">Wallet Address</label>';
        echo '<input type="text" name="pd_field[wallet_address]" class="form-control" style="font-size:12px;font-family:monospace" placeholder="0x... or T... or bc1..." value="' . $wallet . '"></div>';
        echo '</div>';

    } else {
        // Custom / legacy: plain textarea
        $val = htmlspecialchars($legacyText ?: $details, ENT_QUOTES);
        echo '<textarea name="payment_details" class="form-control" rows="2" style="font-size:12px;resize:vertical" placeholder="Account number, wallet address, etc.">' . $val . '</textarea>';
    }

    // Hidden field to carry the method_type so controller knows how to encode
    echo '<input type="hidden" name="pd_method_type" value="' . htmlspecialchars($type, ENT_QUOTES) . '">';
    if ($legacyText) {
        echo '<div style="font-size:10px;color:#F59E0B;margin-top:4px">&#9888; Legacy free-text detected — fill the structured fields above and Save to upgrade</div>';
    }
}
?>

<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Affiliate Payout Info</span>
        <span class="text-muted" style="font-size:12px;float:right">Structured fields shown based on payment method type</span>
    </div>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead><tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B">Affiliate</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B;width:190px">Payment Method</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B">Payment Details</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B;width:70px">Action</th>
        </tr></thead>
        <tbody>
        <?php if (empty($affiliates)): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No active affiliates found</td></tr>
        <?php else: ?>
        <?php foreach ($affiliates as $a):
            $affPmType = $_pmTypeMap[$a['payment_method']] ?? 'custom';
        ?>
        <tr style="border-bottom:1px solid #F1F5F9;vertical-align:top">
            <td style="padding:12px 16px">
                <div class="fw-bold text-sm"><?= Helpers::e($a['name']) ?></div>
                <div class="text-muted" style="font-size:11px"><?= Helpers::e($a['email']) ?></div>
            </td>
            <td style="padding:12px 16px">
                <form method="POST" id="aff-form-<?= $a['id'] ?>">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="payout_info">
                <input type="hidden" name="payout_type" value="affiliate">
                <input type="hidden" name="entity_id" value="<?= $a['id'] ?>">
                <select name="payment_method" class="form-control" style="font-size:13px" onchange="this.form.submit()">
                    <option value="">— Select —</option>
                    <?php foreach ($paymentMethods as $pm): ?>
                    <option value="<?= Helpers::e($pm['name']) ?>" <?= $a['payment_method'] === $pm['name'] ? 'selected' : '' ?>><?= Helpers::e($pm['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($a['payment_method'] && !in_array($a['payment_method'], array_column($paymentMethods, 'name'))): ?>
                    <option value="<?= Helpers::e($a['payment_method']) ?>" selected><?= Helpers::e($a['payment_method']) ?></option>
                    <?php endif; ?>
                </select>
            </td>
            <td style="padding:12px 16px">
                <?php renderPaymentDetailFields($a['payment_method'], $affPmType, $a['payment_details'], 'aff', (int)$a['id']); ?>
            </td>
            <td style="padding:12px 16px">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">Manager Payout Info</span>
        <span class="text-muted" style="font-size:12px;float:right">Structured fields shown based on payment method type</span>
    </div>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead><tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B">Manager</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B;width:190px">Payment Method</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B">Payment Details</th>
            <th style="padding:10px 16px;text-align:left;font-size:12px;font-weight:700;color:#64748B;width:70px">Action</th>
        </tr></thead>
        <tbody>
        <?php if (empty($managers)): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No active managers found</td></tr>
        <?php else: ?>
        <?php foreach ($managers as $mgr):
            $mgrPmType = $_pmTypeMap[$mgr['payment_method']] ?? 'custom';
        ?>
        <tr style="border-bottom:1px solid #F1F5F9;vertical-align:top">
            <td style="padding:12px 16px">
                <div class="fw-bold text-sm"><?= Helpers::e($mgr['name']) ?></div>
                <div class="text-muted" style="font-size:11px"><?= Helpers::e($mgr['email']) ?></div>
            </td>
            <td style="padding:12px 16px">
                <form method="POST" id="mgr-form-<?= $mgr['mgr_id'] ?>">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="tab" value="payout_info">
                <input type="hidden" name="payout_type" value="manager">
                <input type="hidden" name="entity_id" value="<?= $mgr['mgr_id'] ?>">
                <select name="payment_method" class="form-control" style="font-size:13px" onchange="this.form.submit()">
                    <option value="">— Select —</option>
                    <?php foreach ($paymentMethods as $pm): ?>
                    <option value="<?= Helpers::e($pm['name']) ?>" <?= $mgr['payment_method'] === $pm['name'] ? 'selected' : '' ?>><?= Helpers::e($pm['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($mgr['payment_method'] && !in_array($mgr['payment_method'], array_column($paymentMethods, 'name'))): ?>
                    <option value="<?= Helpers::e($mgr['payment_method']) ?>" selected><?= Helpers::e($mgr['payment_method']) ?></option>
                    <?php endif; ?>
                </select>
            </td>
            <td style="padding:12px 16px">
                <?php renderPaymentDetailFields($mgr['payment_method'], $mgrPmType, $mgr['payment_details'], 'mgr', (int)$mgr['mgr_id']); ?>
            </td>
            <td style="padding:12px 16px">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </form>
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
function toggleAffiliateList(radio) {
    document.getElementById('affiliateListSection').style.display = radio.value === 'selected' ? 'block' : 'none';
}

// ── Payment Method type → field info for the Add/Edit forms ──────────────
var pmFieldInfo = {
    'payoneer': { name:'Payoneer', instructions:'Please provide your Payoneer Account Holder Name and registered Email / Account ID.',
                  fields:['Account Holder Name','Email / Account ID'] },
    'paypal':   { name:'PayPal',   instructions:'Please provide your PayPal Account Holder Name and registered Email.',
                  fields:['Account Holder Name','Email / Account ID'] },
    'wise':     { name:'Wise',     instructions:'Please provide your Wise Account Holder Name and registered Email.',
                  fields:['Account Holder Name','Email / Account ID'] },
    'wire':     { name:'Wire Transfer', instructions:'Please provide full bank details: account number, IBAN/SWIFT, routing number, and bank address.',
                  fields:['Account Holder Name','Bank Name','Account Number','IBAN / SWIFT Code','Routing Number','Branch Name','Bank Address'] },
    'crypto':   { name:'Cryptocurrency', instructions:'Please provide your wallet address, cryptocurrency type, and network.',
                  fields:['Cryptocurrency (USDT / BTC / ETH / LTC / BNB / TRX / XRP / USDC / DOGE / SOL)','Wallet Address','Network Type (TRC20 / ERC20 / BEP20 / Bitcoin / Ethereum / Solana)'] },
    'custom':   { name:'', instructions:'', fields:[] }
};

function pmTypeChanged(sel, prefix) {
    var type     = sel.value;
    var info     = pmFieldInfo[type] || pmFieldInfo['custom'];
    var nameEl   = document.getElementById(prefix + 'PmName');
    var instrEl  = document.getElementById(prefix + 'PmInstructions');
    var preview  = document.getElementById(prefix + 'PmFieldPreview');
    var fieldList= document.getElementById(prefix + 'PmFieldList');

    // Auto-fill name and instructions for known types (if field is empty)
    if (nameEl && info.name && !nameEl.value) nameEl.value = info.name;
    if (instrEl && info.instructions && !instrEl.value) instrEl.value = info.instructions;

    // Show field preview
    if (info.fields && info.fields.length > 0) {
        fieldList.innerHTML = info.fields.map(function(f){ return '&#8226; ' + f; }).join('<br>');
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
}

// ── Edit Method Modal ─────────────────────────────────────────────────────
function openEditPm(pm) {
    var overlay = document.getElementById('editPmOverlay');
    document.getElementById('editPmId').value          = pm.id;
    document.getElementById('editPmName').value        = pm.name || '';
    document.getElementById('editPmDescription').value = pm.description || '';
    document.getElementById('editPmInstructions').value= pm.instructions || '';
    var typeEl = document.getElementById('editPmType');
    typeEl.value = pm.method_type || 'custom';
    // Trigger field preview update
    pmTypeChanged(typeEl, 'edit');
    // Override name since it's already filled from existing data
    document.getElementById('editPmName').value = pm.name || '';
    document.getElementById('editPmInstructions').value = pm.instructions || '';
    overlay.style.display = 'flex'; // flex so inner div centres
    document.body.style.overflow = 'hidden';
}
function closeEditPm() {
    document.getElementById('editPmOverlay').style.display = 'none';
    document.body.style.overflow = '';
}
// Close on overlay click (only when overlay exists in the DOM)
var _overlay = document.getElementById('editPmOverlay');
if (_overlay) {
    _overlay.addEventListener('click', function(e) {
        if (e.target === this) closeEditPm();
    });
}
// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('editPmOverlay')) closeEditPm();
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
