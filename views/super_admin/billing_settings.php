<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">💳 Payment Gateways &amp; Subscription Settings</h2>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($success) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($error) ?>
</div>
<?php endif; ?>

<form action="/super_admin/billing-settings" method="POST">
    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
        
        <!-- Stripe Gateway Config -->
        <div class="glass-card">
            <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="glass-title" style="display: flex; align-items: center; gap: 10px;">
                    <span>💳</span> Stripe Card Processing
                </h3>
                <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                    <input type="checkbox" name="stripe_enabled" value="1" <?= ($gw['stripe']['enabled'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;" class="toggle-switch">
                    <span style="position: absolute; cursor: pointer; inset: 0; background-color: rgba(255,255,255,0.1); border: 1px solid var(--border-glass); border-radius: 24px; transition: .3s;" class="slider"></span>
                </label>
            </div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; background: rgba(30,27,75,0.2); padding: 10px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.04);">
                    <span style="font-size: 13px; color: #C7D2FE;">Sandbox Sandbox Mode</span>
                    <label style="position: relative; display: inline-block; width: 40px; height: 20px;">
                        <input type="checkbox" name="stripe_sandbox" value="1" <?= ($gw['stripe']['sandbox_mode'] ?? '1') === '1' ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; inset: 0; background-color: rgba(255,255,255,0.1); border-radius: 20px; transition: .3s;" class="slider"></span>
                    </label>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Stripe Secret Key (sk_live_... / sk_test_...)</label>
                    <input type="password" name="stripe_secret" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['stripe']['secret_key'] ?? '') ?>" placeholder="sk_test_...">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Stripe Webhook Signing Secret (whsec_...)</label>
                    <input type="password" name="stripe_webhook" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['stripe']['webhook_secret'] ?? '') ?>" placeholder="whsec_...">
                </div>
                
                <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 10px;">
                    * Sync Webhook Target Endpoint to: <code style="background: rgba(15,12,38,0.5); padding: 2px 6px; border-radius: 4px; color: #A78BFA;"><?= Helpers::e(Config::get('config','app.url')) ?>/api/webhooks/payment</code>
                </span>
            </div>
        </div>

        <!-- Free Trial configurations -->
        <div class="glass-card" style="display: flex; flex-direction: column;">
            <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="glass-title" style="display: flex; align-items: center; gap: 10px;">
                    <span>🎁</span> Free Trial Conversions
                </h3>
                <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                    <input type="checkbox" name="trial_enabled" value="1" <?= ($gw['trial']['enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;" class="toggle-switch">
                    <span style="position: absolute; cursor: pointer; inset: 0; background-color: rgba(255,255,255,0.1); border: 1px solid var(--border-glass); border-radius: 24px; transition: .3s;" class="slider"></span>
                </label>
            </div>
            <div style="padding: 24px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Configured Trial Duration (Days)</label>
                    <input type="number" name="trial_duration" required class="glass-input" style="width: 100%;" value="<?= (int)($gw['trial']['duration_days'] ?? 14) ?>" min="1">
                </div>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6; margin: 20px 0 0;">
                    Admins checking "Start Free Trial" will receive this number of trial days. Expired accounts automatically freeze tracking rotators and redirect admins to subscription checkouts.
                </p>
            </div>
        </div>

        <!-- Crypto Payout wallets -->
        <div class="glass-card" style="grid-column: 1/-1;">
            <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="glass-title" style="display: flex; align-items: center; gap: 10px;">
                    <span>🪙</span> Crypto Payment Gateway Integration
                </h3>
                <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                    <input type="checkbox" name="crypto_enabled" value="1" <?= ($gw['crypto']['enabled'] ?? '0') === '1' ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;" class="toggle-switch">
                    <span style="position: absolute; cursor: pointer; inset: 0; background-color: rgba(255,255,255,0.1); border: 1px solid var(--border-glass); border-radius: 24px; transition: .3s;" class="slider"></span>
                </label>
            </div>
            <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">BTC Wallet Address (Bitcoin Mainnet)</label>
                    <input type="text" name="btc_address" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['btc_address'] ?? '') ?>" placeholder="1A1zP1eP5QG...">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">ETH Wallet Address (ERC-20)</label>
                    <input type="text" name="eth_address" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['eth_address'] ?? '') ?>" placeholder="0x71C765...">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">USDT Wallet Address (TRC-20)</label>
                    <input type="text" name="usdt_trc20" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['usdt_trc20'] ?? '') ?>" placeholder="TR7NHqJdj...">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">USDT Wallet Address (ERC-20)</label>
                    <input type="text" name="usdt_erc20" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['usdt_erc20'] ?? '') ?>" placeholder="0x71C765...">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">BNB Address (BEP-20)</label>
                    <input type="text" name="bnb_address" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['bnb_address'] ?? '') ?>" placeholder="0x71C765...">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">LTC Address (Litecoin)</label>
                    <input type="text" name="ltc_address" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['ltc_address'] ?? '') ?>" placeholder="LXPp1nZf...">
                </div>
                <div style="grid-column: 1/-1;">
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">DOGE Address (Dogecoin)</label>
                    <input type="text" name="doge_address" class="glass-input" style="width: 100%;" value="<?= Helpers::e($gw['crypto']['doge_address'] ?? '') ?>" placeholder="D6t2Dq3...">
                </div>
            </div>
        </div>

        <!-- SMTP Config Section -->
        <div class="glass-card" style="grid-column: 1/-1;">
            <div class="glass-header">
                <h3 class="glass-title" style="display: flex; align-items: center; gap: 10px;">
                    <span>✉️</span> Global SaaS SMTP System Mailer
                </h3>
            </div>
            <div style="padding: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">SMTP Server Host</label>
                    <input type="text" name="smtp_host" class="glass-input" style="width: 100%;" value="<?= Helpers::e(Config::get('config', 'smtp.host') ?? '') ?>" placeholder="e.g. smtp.mailgun.org or smtp.gmail.com">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">SMTP Port</label>
                    <input type="number" name="smtp_port" class="glass-input" style="width: 100%;" value="<?= (int)(Config::get('config', 'smtp.port') ?: 587) ?>" placeholder="e.g. 587 or 465">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">SMTP Encryption</label>
                    <select name="smtp_encryption" class="glass-select" style="width: 100%; height: 42px;">
                        <?php $enc = strtolower(Config::get('config', 'smtp.encryption') ?? 'tls'); ?>
                        <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
                        <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="none" <?= $enc === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">SMTP Username</label>
                    <input type="text" name="smtp_username" class="glass-input" style="width: 100%;" value="<?= Helpers::e(Config::get('config', 'smtp.username') ?? '') ?>" placeholder="e.g. username@domain.com">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">SMTP Password</label>
                    <input type="password" name="smtp_password" class="glass-input" style="width: 100%;" value="<?= Helpers::e(Config::get('config', 'smtp.password') ?? '') ?>" placeholder="••••••••••••">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Sender Display Name</label>
                    <input type="text" name="smtp_from_name" class="glass-input" style="width: 100%;" value="<?= Helpers::e(Config::get('config', 'smtp.from_name') ?? '') ?>" placeholder="e.g. Acme Network Support">
                </div>
                <div style="grid-column: 1/-1;">
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Sender Email Address (From)</label>
                    <input type="email" name="smtp_from_email" class="glass-input" style="width: 100%;" value="<?= Helpers::e(Config::get('config', 'smtp.from_email') ?? '') ?>" placeholder="e.g. no-reply@acme.com">
                    <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 6px;">
                        * Used SaaS-wide to dispatch OTP signup codes and activation alerts to tenant administrators.
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Submit block -->
    <div class="glass-card" style="padding: 20px; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn-premium" style="padding: 14px 40px; font-size: 15px; font-weight: 700;">💾 Save Payment &amp; SMTP Settings</button>
    </div>
</form>

<style>
/* Cohesive Toggle Switch CSS custom properties */
.toggle-switch:checked + .slider {
    background-color: var(--primary-color) !important;
}
.toggle-switch:checked + .slider::before {
    transform: translateX(20px);
}
.slider::before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: .3s;
}
.toggle-switch:checked + .slider::before {
    transform: translateX(20px);
}
</style>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
