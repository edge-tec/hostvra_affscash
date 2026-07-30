<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<?php $terms = ['weekly'=>'Weekly','net15'=>'Net-15','net30'=>'Net-30','monthly'=>'Monthly']; ?>

<div class="page-header">
    <div>
        <?php if (!empty($aff['profile_pic'])): ?>
        <img src="<?= Helpers::e($aff['profile_pic']) ?>" alt="Profile" style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-right:12px;vertical-align:middle;border:2px solid #E2E8F0">
        <?php endif; ?>
        <h1 style="display:inline"><?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?></h1>
        <p style="margin-top:4px">Account Settings</p>
    </div>
</div>

<!-- Tab Nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0;flex-wrap:wrap">
    <?php foreach(['profile'=>'Profile','security'=>'Security','payment'=>'Payment','manager'=>'My Manager'] as $k=>$lbl): ?>
    <a href="/affiliate/profile?tab=<?= $k ?>"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab===$k?'#10B981':'transparent' ?>;margin-bottom:-2px;color:<?= $activeTab===$k?'#10B981':'#64748B' ?>">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
    <a href="/affiliate/2fa"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">
        &#128274; Google Authenticator
    </a>
    <a href="/affiliate/delete-account"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#EF4444;margin-left:auto">
        &#128465; Delete Account
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- PROFILE TAB -->
<?php if ($activeTab === 'profile'): ?>
<div class="card" style="max-width:640px">
    <div class="card-header"><span class="card-title">Profile Information</span></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="profile">

            <!-- Profile Picture -->
            <div class="form-group">
                <label>Profile Picture</label>
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px">
                    <?php if (!empty($aff['profile_pic'])): ?>
                    <img src="<?= Helpers::e($aff['profile_pic']) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E2E8F0">
                    <?php else: ?>
                    <div style="width:64px;height:64px;border-radius:50%;background:#10B981;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700"><?= strtoupper(substr($aff['first_name'],'0',1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <input type="file" name="profile_pic" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                        <div class="form-hint">PNG, JPG, GIF, WebP · Max 2 MB</div>
                    </div>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($aff['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($aff['last_name']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <?php if ($aff['allow_email_change']): ?>
                <input type="email" name="email" class="form-control" value="<?= Helpers::e($aff['email']) ?>">
                <div class="form-hint" style="color:#10B981">&#10003; Email change is permitted by your manager</div>
                <?php else: ?>
                <input type="email" class="form-control" value="<?= Helpers::e($aff['email']) ?>" disabled>
                <div class="form-hint">Contact your manager to change your email address</div>
                <?php endif; ?>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($aff['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($aff['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- Social Media Verification -->
            <div class="form-row cols-2" style="margin-top:4px">
                <div class="form-group">
                    <label>Social Media Platform</label>
                    <select name="social_platform" class="form-control">
                        <option value="">— None —</option>
                        <?php
                        $spVal = $aff['social_platform'] ?? '';
                        $spOpts = ['facebook'=>'Facebook','instagram'=>'Instagram','x'=>'X (Twitter)','linkedin'=>'LinkedIn','tiktok'=>'TikTok','youtube'=>'YouTube','telegram'=>'Telegram','reddit'=>'Reddit','snapchat'=>'Snapchat','pinterest'=>'Pinterest','threads'=>'Threads','other'=>'Other'];
                        foreach ($spOpts as $spKey=>$spLabel): ?>
                        <option value="<?= $spKey ?>" <?= $spVal === $spKey ? 'selected' : '' ?>><?= $spLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Social Media Profile URL</label>
                    <input type="url" name="social_profile_url" class="form-control" placeholder="https://facebook.com/yourprofile"
                           value="<?= Helpers::e($aff['social_profile_url'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Profile</button>
        </form>
    </div>
</div>

<!-- SECURITY TAB -->
<?php elseif ($activeTab === 'security'): ?>
<div class="card" style="max-width:480px">
    <div class="card-header"><span class="card-title">Change Password</span></div>
    <div class="card-body">
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="security">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required placeholder="Minimum 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<!-- PAYMENT TAB -->
<?php elseif ($activeTab === 'payment'): ?>
<?php
// Build method-type lookup map
$_pmTypeMap = [];
$_pmInstrMap = [];
foreach ($paymentMethods as $_pm) {
    $_pmTypeMap[$_pm['name']]  = $_pm['method_type']  ?? 'custom';
    $_pmInstrMap[$_pm['name']] = $_pm['instructions'] ?? '';
}

$_currentMethod = $aff['payment_method'] ?? '';
$_currentType   = $_pmTypeMap[$_currentMethod] ?? 'custom';

// Parse saved JSON details into array
$_saved = [];
$_detailsRaw = $aff['payment_details'] ?? '';
if ($_detailsRaw !== '' && $_detailsRaw[0] === '{') {
    $_saved = json_decode($_detailsRaw, true) ?: [];
}
$_legacyText = ($_detailsRaw !== '' && $_detailsRaw[0] !== '{') ? $_detailsRaw : '';

// Helper: get saved value
$_v = function(string $key) use ($_saved): string {
    return htmlspecialchars($_saved[$key] ?? '', ENT_QUOTES);
};

// Crypto options
$_cryptoOptions  = ['USDT','Bitcoin (BTC)','Ethereum (ETH)','Litecoin (LTC)','BNB (BEP20)','TRON (TRX)','XRP (Ripple)','USDC','Dogecoin (DOGE)','Solana (SOL)','Other'];
$_networkOptions = ['TRC20 (Tron)','ERC20 (Ethereum)','BEP20 (BSC)','Bitcoin Network','Litecoin Network','Ethereum Network','Solana Network','XRP Ledger','Other'];
?>

<div style="max-width:720px">

<!-- Payment Terms info bar -->
<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap">
        <div style="font-size:13px">
            <span style="color:#64748B;font-weight:600">Payment Terms</span>
            <span class="badge badge-info" style="margin-left:8px"><?= $terms[$aff['payment_terms'] ?? 'monthly'] ?? 'Monthly' ?></span>
        </div>
        <div style="font-size:13px">
            <span style="color:#64748B;font-weight:600">Min. Threshold</span>
            <span style="margin-left:8px;font-size:14px;font-weight:700;color:#10B981">$<?= number_format((float)($aff['payment_threshold'] ?? 100), 2) ?></span>
        </div>
    </div>
    <div style="font-size:11px;color:#94A3B8">Set by your account manager</div>
</div>

<form method="POST" id="paymentForm">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="tab" value="payment">
    <input type="hidden" name="pd_method_type" id="hdnMethodType" value="<?= Helpers::e($_currentType) ?>">

    <!-- Method Selector -->
    <div class="card mb-3">
        <div class="card-header"><span class="card-title">Select Payment Method</span></div>
        <div class="card-body" style="padding-bottom:0">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:16px" id="methodGrid">
            <?php if (!empty($paymentMethods)): ?>
                <?php foreach ($paymentMethods as $_pm):
                    $isSelected = $_currentMethod === $_pm['name'];
                    $t = $_pmTypeMap[$_pm['name']];
                    $icon = match($t) {
                        'payoneer' => '💳', 'paypal' => '🅿', 'wise' => '🌐',
                        'wire'     => '🏦', 'crypto' => '₿', default => '⚙',
                    };
                ?>
                <label style="cursor:pointer;user-select:none">
                    <input type="radio" name="payment_method" value="<?= Helpers::e($_pm['name']) ?>"
                           class="pm-radio" style="display:none"
                           <?= $isSelected ? 'checked' : '' ?>
                           data-type="<?= Helpers::e($t) ?>"
                           data-instructions="<?= Helpers::e($_pmInstrMap[$_pm['name']]) ?>"
                           onchange="onMethodChange(this)">
                    <div class="pm-card<?= $isSelected ? ' pm-card-active' : '' ?>" style="border:2px solid <?= $isSelected ? '#10B981' : '#E2E8F0' ?>;border-radius:10px;padding:14px 10px;text-align:center;transition:all .15s;background:<?= $isSelected ? '#ECFDF5' : '#fff' ?>">
                        <div style="font-size:24px;margin-bottom:6px"><?= $icon ?></div>
                        <div style="font-size:12px;font-weight:700;color:<?= $isSelected ? '#059669' : '#374151' ?>"><?= Helpers::e($_pm['name']) ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach(['PayPal'=>'🅿','Wire Transfer'=>'🏦','Cryptocurrency'=>'₿','Payoneer'=>'💳','Wise'=>'🌐'] as $_n=>$_ic): ?>
                <label style="cursor:pointer">
                    <input type="radio" name="payment_method" value="<?= $_n ?>" class="pm-radio" style="display:none"
                           <?= $_currentMethod === $_n ? 'checked' : '' ?>
                           data-type="custom" onchange="onMethodChange(this)">
                    <div class="pm-card<?= $_currentMethod === $_n ? ' pm-card-active' : '' ?>" style="border:2px solid <?= $_currentMethod === $_n ? '#10B981' : '#E2E8F0' ?>;border-radius:10px;padding:14px 10px;text-align:center;background:<?= $_currentMethod === $_n ? '#ECFDF5' : '#fff' ?>">
                        <div style="font-size:24px;margin-bottom:6px"><?= $_ic ?></div>
                        <div style="font-size:12px;font-weight:700"><?= $_n ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>

            <!-- Instructions banner -->
            <div id="pmInstructions" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:11px 14px;margin-bottom:16px;font-size:13px;color:#1E40AF;display:<?= $_currentMethod && ($_pmInstrMap[$_currentMethod] ?? '') ? 'flex' : 'none' ?>;align-items:flex-start;gap:8px">
                <span style="font-size:16px;flex-shrink:0">ℹ️</span>
                <span id="pmInstructionsText"><?= Helpers::e($_pmInstrMap[$_currentMethod] ?? '') ?></span>
            </div>
        </div>
    </div>

    <!-- Structured Detail Fields -->
    <div class="card" id="detailCard" style="<?= $_currentMethod ? '' : 'display:none' ?>">
        <div class="card-header">
            <span class="card-title">Payment Details</span>
            <span class="text-muted" style="font-size:12px;float:right" id="methodLabel"><?= Helpers::e($_currentMethod) ?></span>
        </div>
        <div class="card-body">

            <?php if ($_legacyText): ?>
            <div style="padding:10px 14px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:12px;color:#92400E;margin-bottom:16px">
                ⚠️ Previous free-text details detected. Fill the fields below and save to upgrade to structured format.
                <div style="font-family:monospace;margin-top:4px;font-size:11px;color:#78350F"><?= Helpers::e($_legacyText) ?></div>
            </div>
            <?php endif; ?>

            <!-- ── PAYONEER / PAYPAL / WISE ────────────────────────────── -->
            <div id="fields-simple" style="display:<?= in_array($_currentType, ['payoneer','paypal','wise']) ? 'block' : 'none' ?>">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Account Holder Name <span style="color:#EF4444">*</span></label>
                        <input type="text" name="pd_field[account_holder_name]" class="form-control"
                               placeholder="Full name on account"
                               value="<?= $_v('account_holder_name') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email / Account ID <span style="color:#EF4444">*</span></label>
                        <input type="email" name="pd_field[email]" class="form-control"
                               placeholder="account@example.com"
                               value="<?= $_v('email') ?>">
                    </div>
                </div>
            </div>

            <!-- ── WIRE TRANSFER ─────────────────────────────────────────── -->
            <div id="fields-wire" style="display:<?= $_currentType === 'wire' ? 'block' : 'none' ?>">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Account Holder Name <span style="color:#EF4444">*</span></label>
                        <input type="text" name="pd_field[account_holder_name]" class="form-control"
                               placeholder="Full legal name"
                               value="<?= $_v('account_holder_name') ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Name <span style="color:#EF4444">*</span></label>
                        <input type="text" name="pd_field[bank_name]" class="form-control"
                               placeholder="e.g. HSBC, Chase, Deutsche Bank"
                               value="<?= $_v('bank_name') ?>">
                    </div>
                    <div class="form-group">
                        <label>Account Number <span style="color:#EF4444">*</span></label>
                        <input type="text" name="pd_field[account_number]" class="form-control"
                               placeholder="Bank account number"
                               value="<?= $_v('account_number') ?>">
                    </div>
                    <div class="form-group">
                        <label>IBAN / SWIFT Code</label>
                        <input type="text" name="pd_field[iban_swift]" class="form-control"
                               placeholder="e.g. GB29NWBK60161331926819 / BOFAUS3N"
                               value="<?= $_v('iban_swift') ?>">
                    </div>
                    <div class="form-group">
                        <label>Routing Number</label>
                        <input type="text" name="pd_field[routing_number]" class="form-control"
                               placeholder="ABA routing number"
                               value="<?= $_v('routing_number') ?>">
                    </div>
                    <div class="form-group">
                        <label>Branch Name</label>
                        <input type="text" name="pd_field[branch_name]" class="form-control"
                               placeholder="Branch name (optional)"
                               value="<?= $_v('branch_name') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Bank Address <span style="color:#EF4444">*</span></label>
                    <textarea name="pd_field[bank_address]" class="form-control" rows="2"
                              placeholder="Full bank address including city and country"><?= $_v('bank_address') ?></textarea>
                </div>
            </div>

            <!-- ── CRYPTOCURRENCY ─────────────────────────────────────────── -->
            <div id="fields-crypto" style="display:<?= $_currentType === 'crypto' ? 'block' : 'none' ?>">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Cryptocurrency <span style="color:#EF4444">*</span></label>
                        <select name="pd_field[crypto_type]" class="form-control">
                            <option value="">— Select Cryptocurrency —</option>
                            <?php foreach ($_cryptoOptions as $_c): ?>
                            <option value="<?= Helpers::e($_c) ?>" <?= ($_saved['crypto_type'] ?? '') === $_c ? 'selected' : '' ?>><?= Helpers::e($_c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Network Type <span style="color:#EF4444">*</span></label>
                        <select name="pd_field[network_type]" class="form-control">
                            <option value="">— Select Network —</option>
                            <?php foreach ($_networkOptions as $_n): ?>
                            <option value="<?= Helpers::e($_n) ?>" <?= ($_saved['network_type'] ?? '') === $_n ? 'selected' : '' ?>><?= Helpers::e($_n) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Wallet Address <span style="color:#EF4444">*</span></label>
                    <input type="text" name="pd_field[wallet_address]" class="form-control"
                           style="font-family:monospace;font-size:13px"
                           placeholder="0x... or T... or bc1..."
                           value="<?= $_v('wallet_address') ?>">
                    <div class="form-hint">Double-check your wallet address — crypto payments are irreversible</div>
                </div>

                <!-- Crypto quick-reference chips -->
                <div style="margin-bottom:8px">
                    <div style="font-size:12px;font-weight:600;color:#64748B;margin-bottom:8px">Supported Cryptocurrencies</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        <?php
                        $cryptoChips = [
                            ['USDT','#26A17B'],['BTC','#F7931A'],['ETH','#627EEA'],
                            ['LTC','#BFBBBB'],['BNB','#F3BA2F'],['TRX','#E8272A'],
                            ['XRP','#00AAE4'],['USDC','#2775CA'],['DOGE','#C2A633'],['SOL','#9945FF'],
                        ];
                        foreach ($cryptoChips as [$sym, $col]):
                        ?>
                        <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $col ?>22;color:<?= $col ?>;border:1px solid <?= $col ?>44"><?= $sym ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── CUSTOM / FALLBACK ─────────────────────────────────────── -->
            <div id="fields-custom" style="display:<?= $_currentType === 'custom' || !$_currentMethod ? 'block' : 'none' ?>">
                <div class="form-group">
                    <label>Payment Details</label>
                    <textarea name="payment_details" class="form-control" rows="4"
                              placeholder="Enter your payment account details (email, bank details, wallet address, etc.)"><?= Helpers::e($_legacyText ?: '') ?></textarea>
                    <div class="form-hint">This information is used to process your payouts. Keep it accurate and up to date.</div>
                </div>
            </div>

            <div style="margin-top:8px">
                <button type="submit" class="btn btn-primary" style="padding-left:28px;padding-right:28px">
                    &#10003; Save Payment Details
                </button>
            </div>
        </div>
    </div>

    <!-- No method selected placeholder -->
    <div id="noMethodCard" style="<?= $_currentMethod ? 'display:none' : '' ?>">
        <div style="text-align:center;padding:32px;background:#F8FAFC;border:2px dashed #E2E8F0;border-radius:12px;color:#94A3B8">
            <div style="font-size:36px;margin-bottom:10px">💳</div>
            <div style="font-weight:600;font-size:15px;margin-bottom:4px">Select a Payment Method</div>
            <div style="font-size:13px">Choose how you'd like to receive your payouts</div>
        </div>
    </div>

</form>
</div>

<!-- MANAGER TAB -->
<?php elseif ($activeTab === 'manager'): ?>
<div class="card" style="max-width:560px">
    <div class="card-header"><span class="card-title">Your Affiliate Manager</span></div>
    <div class="card-body">
        <?php if ($manager): ?>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #F1F5F9">
            <div style="width:56px;height:56px;border-radius:50%;background:#7C3AED;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;flex-shrink:0">
                <?= strtoupper(substr($manager['first_name'] ?? 'M', 0, 1)) ?>
            </div>
            <div>
                <div style="font-size:18px;font-weight:700"><?= Helpers::e($manager['first_name'] . ' ' . $manager['last_name']) ?></div>
                <?php if (!empty($manager['company'])): ?>
                <div class="text-muted text-sm"><?= Helpers::e($manager['company']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <table style="width:100%">
            <tr><td style="padding:8px 0;color:var(--text-muted);font-size:13px;width:40%">Email</td><td style="font-size:13px"><a href="mailto:<?= Helpers::e($manager['email']) ?>"><?= Helpers::e($manager['email']) ?></a></td></tr>
            <?php if (!empty($manager['phone'])): ?>
            <tr><td style="padding:8px 0;color:var(--text-muted);font-size:13px">Phone</td><td style="font-size:13px"><?= Helpers::e($manager['phone']) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:8px 0;color:var(--text-muted);font-size:13px">Email Change</td><td style="font-size:13px"><?= $aff['allow_email_change'] ? '<span class="badge badge-success">Allowed</span>' : '<span class="badge badge-muted">Not Allowed</span>' ?></td></tr>
            <tr><td style="padding:8px 0;color:var(--text-muted);font-size:13px">Payment Terms</td><td style="font-size:13px"><span class="badge badge-info"><?= $terms[$aff['payment_terms'] ?? 'monthly'] ?? 'Monthly' ?></span></td></tr>
        </table>
        <?php else: ?>
        <div class="text-center text-muted" style="padding:32px 0">
            <div style="font-size:36px;margin-bottom:12px">&#128101;</div>
            <div>No affiliate manager assigned to your account.</div>
            <div style="font-size:13px;margin-top:4px">Contact support if you need assistance.</div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// ── Payment method switcher ───────────────────────────────────────────────
var _pmTypeMap = <?= json_encode(
    !empty($paymentMethods)
        ? array_combine(array_column($paymentMethods,'name'), array_column($paymentMethods,'method_type'))
        : []
) ?>;

function onMethodChange(radio) {
    var name  = radio.value;
    var type  = radio.dataset.type || _pmTypeMap[name] || 'custom';
    var instr = radio.dataset.instructions || '';

    // Update hidden field
    var hdn = document.getElementById('hdnMethodType');
    if (hdn) hdn.value = type;

    // Update method label
    var lbl = document.getElementById('methodLabel');
    if (lbl) lbl.textContent = name;

    // Style all method cards
    document.querySelectorAll('.pm-card').forEach(function(card) {
        card.style.border       = '2px solid #E2E8F0';
        card.style.background   = '#fff';
        card.querySelector('div:last-child').style.color = '#374151';
    });
    var activeCard = radio.closest('label').querySelector('.pm-card');
    if (activeCard) {
        activeCard.style.border     = '2px solid #10B981';
        activeCard.style.background = '#ECFDF5';
        activeCard.querySelector('div:last-child').style.color = '#059669';
    }

    // Instructions banner
    var instrBox  = document.getElementById('pmInstructions');
    var instrText = document.getElementById('pmInstructionsText');
    if (instrBox && instrText) {
        if (instr) {
            instrText.textContent = instr;
            instrBox.style.display = 'flex';
        } else {
            instrBox.style.display = 'none';
        }
    }

    // Show detail card, hide placeholder
    var detailCard  = document.getElementById('detailCard');
    var noMethodCard= document.getElementById('noMethodCard');
    if (detailCard)   detailCard.style.display  = '';
    if (noMethodCard) noMethodCard.style.display = 'none';

    // Show the right field group
    var groups = {
        'simple': ['payoneer','paypal','wise'],
        'wire':   ['wire'],
        'crypto': ['crypto'],
        'custom': ['custom','']
    };
    Object.keys(groups).forEach(function(groupKey) {
        var el = document.getElementById('fields-' + groupKey);
        if (!el) return;
        el.style.display = groups[groupKey].indexOf(type) !== -1 ? 'block' : 'none';
    });
    // Fallback: if no group matched, show custom
    var anyVisible = ['simple','wire','crypto'].some(function(g){
        var el = document.getElementById('fields-' + g);
        return el && el.style.display !== 'none';
    });
    var customEl = document.getElementById('fields-custom');
    if (customEl && !anyVisible) customEl.style.display = 'block';
}

// Run on page load to style the pre-selected card
document.addEventListener('DOMContentLoaded', function() {
    var checked = document.querySelector('.pm-radio:checked');
    if (checked) onMethodChange(checked);
});
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
