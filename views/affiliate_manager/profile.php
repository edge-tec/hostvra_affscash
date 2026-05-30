<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <?php if (!empty($mgr['profile_pic'])): ?>
        <img src="<?= Helpers::e($mgr['profile_pic']) ?>" alt="Profile"
             style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-right:12px;vertical-align:middle;border:2px solid #E2E8F0">
        <?php endif; ?>
        <h1 style="display:inline"><?= Helpers::e($mgr['first_name'] . ' ' . $mgr['last_name']) ?></h1>
        <p style="margin-top:4px">Profile Settings</p>
    </div>
</div>

<!-- Tab Nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0;flex-wrap:wrap">
    <?php foreach(['profile'=>'&#128100; Profile','security'=>'&#128274; Security','payment'=>'&#128179; Payment'] as $k=>$lbl): ?>
    <a href="/affiliate_manager/profile?tab=<?= $k ?>"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid <?= $activeTab===$k?'#7C3AED':'transparent' ?>;margin-bottom:-2px;color:<?= $activeTab===$k?'#7C3AED':'#64748B' ?>">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
    <a href="/affiliate_manager/2fa"
       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">
        &#128274; Google Authenticator
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="max-width:640px">
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
                    <?php if (!empty($mgr['profile_pic'])): ?>
                    <img src="<?= Helpers::e($mgr['profile_pic']) ?>"
                         style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #E2E8F0">
                    <?php else: ?>
                    <div style="width:64px;height:64px;border-radius:50%;background:#7C3AED;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700">
                        <?= strtoupper(substr($mgr['first_name'], 0, 1)) ?>
                    </div>
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
                    <input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($mgr['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($mgr['last_name']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= Helpers::e($mgr['email']) ?>">
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($mgr['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($mgr['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- Contact Handles -->
            <div style="border-top:1px solid #F1F5F9;margin:16px 0 14px;padding-top:14px">
                <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 12px">Contact Handles</p>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:5px">
                            <span style="color:#00AFF0">&#128222;</span> Skype
                        </label>
                        <input type="text" name="skype" class="form-control" placeholder="live:username"
                               value="<?= Helpers::e($mgr['skype'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:5px">
                            <span style="color:#2AABEE">&#128232;</span> Telegram
                        </label>
                        <input type="text" name="telegram" class="form-control" placeholder="@username"
                               value="<?= Helpers::e($mgr['telegram'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:5px">
                            <span style="color:#5865F2">&#127918;</span> Discord
                        </label>
                        <input type="text" name="discord" class="form-control" placeholder="username"
                               value="<?= Helpers::e($mgr['discord'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:8px">Save Profile</button>
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
$savedMethod  = $mgr['payment_method'] ?? '';
$savedDetails = !empty($mgr['payment_details']) ? (json_decode($mgr['payment_details'], true) ?: []) : [];
?>
<div class="card" style="max-width:640px">
    <div class="card-header"><span class="card-title">Payment Details</span></div>
    <div class="card-body">
        <?php if ($savedMethod): ?>
        <div class="alert alert-success" style="margin-bottom:20px">
            &#10003; Payment method saved: <strong><?= Helpers::e(ucfirst($savedMethod)) ?></strong>
        </div>
        <?php endif; ?>

        <form method="POST" id="payment-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="tab" value="payment">

            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" id="pm-select" class="form-control" onchange="showPmFields(this.value)">
                    <option value="">— Select method —</option>
                    <option value="paypal"  <?= $savedMethod==='paypal' ?'selected':'' ?>>PayPal</option>
                    <option value="bank"    <?= $savedMethod==='bank'   ?'selected':'' ?>>Bank Transfer (Wire)</option>
                    <option value="crypto"  <?= $savedMethod==='crypto' ?'selected':'' ?>>Crypto Wallet</option>
                    <option value="wise"    <?= $savedMethod==='wise'   ?'selected':'' ?>>Wise (TransferWise)</option>
                    <option value="other"   <?= $savedMethod==='other'  ?'selected':'' ?>>Other</option>
                </select>
            </div>

            <!-- PayPal -->
            <div id="pm-paypal" class="pm-fields" style="display:none">
                <div class="form-group">
                    <label>PayPal Email</label>
                    <input type="email" name="paypal_email" class="form-control" placeholder="you@example.com"
                           value="<?= Helpers::e($savedMethod==='paypal' ? ($savedDetails['email'] ?? '') : '') ?>">
                </div>
            </div>

            <!-- Bank Transfer -->
            <div id="pm-bank" class="pm-fields" style="display:none">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. HSBC"
                               value="<?= Helpers::e($savedMethod==='bank' ? ($savedDetails['bank_name'] ?? '') : '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Account Holder Name</label>
                        <input type="text" name="bank_account_name" class="form-control"
                               value="<?= Helpers::e($savedMethod==='bank' ? ($savedDetails['account_name'] ?? '') : '') ?>">
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Account Number / IBAN</label>
                        <input type="text" name="bank_account_no" class="form-control"
                               value="<?= Helpers::e($savedMethod==='bank' ? ($savedDetails['account_no'] ?? '') : '') ?>">
                    </div>
                    <div class="form-group">
                        <label>SWIFT / BIC</label>
                        <input type="text" name="bank_swift" class="form-control" placeholder="e.g. HBUKGB4B"
                               value="<?= Helpers::e($savedMethod==='bank' ? ($savedDetails['swift'] ?? '') : '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>IBAN (if applicable)</label>
                    <input type="text" name="bank_iban" class="form-control" placeholder="GB29NWBK60161331926819"
                           value="<?= Helpers::e($savedMethod==='bank' ? ($savedDetails['iban'] ?? '') : '') ?>">
                </div>
            </div>

            <!-- Crypto -->
            <div id="pm-crypto" class="pm-fields" style="display:none">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Wallet Address</label>
                        <input type="text" name="crypto_address" class="form-control" placeholder="0x… or bc1…"
                               value="<?= Helpers::e($savedMethod==='crypto' ? ($savedDetails['address'] ?? '') : '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Network / Coin</label>
                        <select name="crypto_network" class="form-control">
                            <?php foreach(['USDT (TRC20)','USDT (ERC20)','Bitcoin (BTC)','Ethereum (ETH)','BNB (BSC)','USDC','Other'] as $cn): ?>
                            <option value="<?= $cn ?>" <?= ($savedMethod==='crypto' && ($savedDetails['network'] ?? ''))===$cn?'selected':'' ?>><?= $cn ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Wise -->
            <div id="pm-wise" class="pm-fields" style="display:none">
                <div class="form-group">
                    <label>Wise Email</label>
                    <input type="email" name="wise_email" class="form-control" placeholder="you@example.com"
                           value="<?= Helpers::e($savedMethod==='wise' ? ($savedDetails['email'] ?? '') : '') ?>">
                </div>
            </div>

            <!-- Other -->
            <div id="pm-other" class="pm-fields" style="display:none">
                <div class="form-group">
                    <label>Payment Details</label>
                    <textarea name="other_details" class="form-control" rows="4" placeholder="Describe your preferred payment method..."><?= Helpers::e($savedMethod==='other' ? ($savedDetails['info'] ?? '') : '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:8px" id="pm-save-btn" disabled>Save Payment Details</button>
        </form>
    </div>
</div>

<script>
function showPmFields(method) {
    document.querySelectorAll('.pm-fields').forEach(function(el){ el.style.display='none'; });
    if (method) {
        var el = document.getElementById('pm-' + method);
        if (el) el.style.display = 'block';
    }
    document.getElementById('pm-save-btn').disabled = !method;
}
// Show on load if already saved
(function(){ showPmFields(document.getElementById('pm-select').value); })();
</script>

<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
