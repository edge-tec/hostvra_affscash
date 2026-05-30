<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div style="max-width: 680px; margin: 40px auto; padding: 20px 15px;">
    <div style="background: rgba(30, 27, 75, 0.45); backdrop-filter: blur(16px); border: 1px solid rgba(139, 92, 246, 0.22); border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.5); overflow: hidden;">
        
        <!-- Header -->
        <div style="background: rgba(139, 92, 246, 0.08); border-bottom: 1px solid rgba(139, 92, 246, 0.18); padding: 22px 28px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-family: var(--font-heading); font-size: 20px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 8px;">
                    <span style="color: #8B5CF6;">🔒</span> Secure Plan Activation
                </h3>
                <p style="margin: 4px 0 0; font-size: 12.5px; color: #94A3B8;">Select your preferred payment gateway below to unlock your tracker dashboard.</p>
            </div>
            <a href="/admin/subscription/renew" class="btn btn-sm btn-outline-secondary" style="border-color: rgba(255,255,255,0.1); color: #C7D2FE; font-weight: 600; text-decoration: none; padding: 6px 12px; border-radius: 6px;">&larr; Plans</a>
        </div>

        <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border-left: 4px solid #EF4444; color: #FCA5A5; padding: 14px 20px; font-size: 13.5px; margin: 20px 24px 0;">
            ⚠️ <?= Helpers::e($error) ?>
        </div>
        <?php endif; ?>

        <form action="/admin/subscription/checkout" method="POST" id="checkoutForm" style="padding: 28px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
            <input type="hidden" name="payment_method" id="payment_method" value="stripe">

            <!-- Order Summary Card -->
            <div style="background: linear-gradient(135deg, rgba(15, 12, 38, 0.6) 0%, rgba(30, 27, 75, 0.4) 100%); border: 1px solid rgba(139, 92, 246, 0.15); border-radius: 12px; padding: 20px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #A78BFA; font-weight: 700; letter-spacing: 0.1em; display: block; margin-bottom: 4px;">Selected Workspace Plan</span>
                    <h4 style="margin: 0; color: #FFFFFF; font-size: 18px; font-weight: 700;"><?= Helpers::e($plan['name']) ?> Plan</h4>
                    <span style="font-size: 13px; color: #94A3B8; display: block; margin-top: 4px;">Membership Period: <strong><?= $plan['duration'] ?> Days</strong></span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 26px; font-weight: 800; color: #FFFFFF; font-family: var(--font-heading);">$<?= number_format($plan['price'], 2) ?></span>
                    <span style="font-size: 12px; color: #34D399; display: block; margin-top: 2px;">⚡ Lifetime Domain Binds</span>
                </div>
            </div>

            <!-- Payment Methods Select Grid -->
            <div style="margin-bottom: 28px;">
                <label style="display: block; font-size: 13.5px; font-weight: 600; margin-bottom: 10px; color: #C7D2FE;">Select Payment Method</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Credit Card (Stripe) Option -->
                    <div id="btnStripe" onclick="setPaymentMethod('stripe')" style="border: 2px solid #8B5CF6; background: rgba(139, 92, 246, 0.08); padding: 18px; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <span style="font-size: 24px;">💳</span>
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Credit/Debit Card</span>
                            <span style="font-size: 11.5px; color: #A78BFA;">Powered by Stripe</span>
                        </div>
                    </div>

                    <!-- Cryptocurrency Option -->
                    <div id="btnCrypto" onclick="setPaymentMethod('crypto')" style="border: 1px solid rgba(255, 255, 255, 0.08); background: transparent; padding: 18px; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <span style="font-size: 24px;">🪙</span>
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Cryptocurrency</span>
                            <span style="font-size: 11.5px; color: #94A3B8;">USDT, BTC, ETH, BNB...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 1: Stripe Credit Card Form -->
            <div id="panelStripe" style="display: block; background: rgba(15, 12, 38, 0.3); border: 1px solid rgba(139, 92, 246, 0.1); border-radius: 12px; padding: 22px; margin-bottom: 28px;">
                <?php 
                $stripeSandbox = ($gw['stripe']['sandbox_mode'] ?? '1') === '1'; 
                if ($stripeSandbox): 
                ?>
                <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="color: #A5B4FC; font-weight: 700; font-size: 12px; text-transform: uppercase; display: block;">🛠️ Stripe Sandbox Sandbox Mode</span>
                        <span style="font-size: 12px; color: #94A3B8;">You can bypass billing live charges and instantly activate your admin account using test cards.</span>
                    </div>
                    <button type="button" onclick="autoFillStripeSandbox()" class="btn btn-sm btn-primary" style="background: #6366F1; border: none; font-size: 12px; font-weight: 600; padding: 8px 12px; border-radius: 6px; color: #FFFFFF; cursor: pointer;">
                        Auto-Fill Test Card
                    </button>
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; color: #94A3B8; margin-bottom: 6px;">Cardholder Name</label>
                    <input type="text" id="stripe_cardholder" class="glass-input" style="width: 100%;" placeholder="e.g. John Doe">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; color: #94A3B8; margin-bottom: 6px;">Card Number</label>
                    <div style="position: relative;">
                        <input type="text" id="stripe_cardnumber" class="glass-input" style="width: 100%; padding-right: 45px;" placeholder="4242 4242 4242 4242" maxlength="19">
                        <span style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); font-size: 18px;">💳</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12.5px; color: #94A3B8; margin-bottom: 6px;">Expiration Date</label>
                        <input type="text" id="stripe_expiry" class="glass-input" style="width: 100%;" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12.5px; color: #94A3B8; margin-bottom: 6px;">CVC / CVV</label>
                        <input type="password" id="stripe_cvc" class="glass-input" style="width: 100%;" placeholder="•••" maxlength="4">
                    </div>
                </div>
            </div>

            <!-- Panel 2: Crypto Currency Setup -->
            <div id="panelCrypto" style="display: none; background: rgba(15, 12, 38, 0.3); border: 1px solid rgba(139, 92, 246, 0.1); border-radius: 12px; padding: 22px; margin-bottom: 28px;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; color: #C7D2FE; margin-bottom: 8px;">Select Cryptocurrency Coin</label>
                    <select name="crypto_currency" id="crypto_currency_selector" class="glass-input" style="width: 100%; color: #FFFFFF;" onchange="updateCryptoDetails()">
                        <option value="USDT-TRC20" selected>USDT (Tether TRC-20) - Popular &amp; Fast</option>
                        <option value="USDT-ERC20">USDT (Tether ERC-20)</option>
                        <option value="BTC">BTC (Bitcoin Mainnet)</option>
                        <option value="ETH">ETH (Ethereum Mainnet)</option>
                        <option value="BNB">BNB (Binance Smart Chain BEP-20)</option>
                        <option value="LTC">LTC (Litecoin)</option>
                        <option value="DOGE">DOGE (Dogecoin)</option>
                    </select>
                </div>

                <!-- Live Wallet Details -->
                <div style="background: rgba(15, 12, 38, 0.5); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; padding: 18px; text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 11.5px; color: #94A3B8; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 12px;">Scan QR Code to Pay</span>
                    
                    <!-- QR Code Frame -->
                    <div style="background: #FFFFFF; padding: 10px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.3); margin-bottom: 14px;">
                        <img id="crypto_qrcode" src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=Placeholder" style="width: 140px; height: 140px; display: block;" alt="QR Code">
                    </div>

                    <!-- Wallet Address Text and copy -->
                    <div style="margin-top: 8px;">
                        <span style="font-size: 12px; color: #C7D2FE; display: block; margin-bottom: 6px;">Deposit Wallet Address:</span>
                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                            <input type="text" readonly id="crypto_wallet_address" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(139, 92, 246, 0.2); color: #34D399; font-family: monospace; font-size: 13px; padding: 8px 12px; border-radius: 6px; width: 80%; text-align: center;" value="TR7NHqJdjGdNF2DzFauk3FGLqmiejGLARi">
                            <button type="button" onclick="copyWalletAddress()" class="btn btn-sm btn-outline-info" style="border: 1px solid #06B6D4; color: #22D3EE; padding: 6px 10px; border-radius: 6px; cursor: pointer; background: transparent; font-size: 12px; font-weight: 600;">Copy</button>
                        </div>
                    </div>
                </div>

                <div style="font-size: 12px; color: #94A3B8; line-height: 1.6; padding: 8px 4px;">
                    ℹ️ Send exactly <strong>$<?= number_format($plan['price'], 2) ?> USD</strong> value of selected crypto. Your plan activates immediately once block confirmations are processed on-chain.
                </div>
            </div>

            <!-- Reference / Verification Code input -->
            <div style="margin-bottom: 28px;">
                <label id="lblReference" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #C7D2FE;">Card Transaction Receipt Code</label>
                <input type="text" name="reference" id="checkout_reference" required class="glass-input" style="width: 100%; border: 1px solid rgba(139, 92, 246, 0.18); background: rgba(15, 12, 38, 0.6); color: #FFFFFF; border-radius: 8px; padding: 10px 14px; font-size: 14px;" placeholder="e.g. Stripe charge reference or Receipt ID" value="CH-SANDBOX-<?= strtoupper(bin2hex(random_bytes(4))) ?>">
                <span id="descReference" style="display: block; font-size: 11.5px; color: #94A3B8; margin-top: 6px;">Submit a mock Stripe ID in sandbox mode to auto-activate the subscription instantly.</span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-premium" style="display: block; width: 100%; text-align: center; border: none; padding: 14px 20px; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 700; color: #FFFFFF;">
                🔒 Secure Checkout &amp; Activate Tenant Account
            </button>

        </form>
    </div>
</div>

<script>
// Wallet addresses loaded dynamically from config database gateways
const cryptoWallets = <?php echo json_encode([
    'BTC' => $gw['crypto']['btc_address'] ?? '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
    'ETH' => $gw['crypto']['eth_address'] ?? '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
    'USDT-TRC20' => $gw['crypto']['usdt_trc20'] ?? 'TR7NHqJdjGdNF2DzFauk3FGLqmiejGLARi',
    'USDT-ERC20' => $gw['crypto']['usdt_erc20'] ?? '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
    'BNB' => $gw['crypto']['bnb_address'] ?? '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
    'LTC' => $gw['crypto']['ltc_address'] ?? 'LXPp1nZf2iHnB8p3C7t3U4p8X1i2X3Y4Z5',
    'DOGE' => $gw['crypto']['doge_address'] ?? 'D6t2Dq3r3v3b4n5m6p7q8r9s0t1u2v3w4x'
]); ?>;

function setPaymentMethod(method) {
    document.getElementById('payment_method').value = method;
    
    var btnStripe = document.getElementById('btnStripe');
    var btnCrypto = document.getElementById('btnCrypto');
    var panelStripe = document.getElementById('panelStripe');
    var panelCrypto = document.getElementById('panelCrypto');
    var lblReference = document.getElementById('lblReference');
    var descReference = document.getElementById('descReference');
    var checkoutRef = document.getElementById('checkout_reference');

    if (method === 'stripe') {
        btnStripe.style.borderColor = '#8B5CF6';
        btnStripe.style.background = 'rgba(139, 92, 246, 0.08)';
        btnCrypto.style.borderColor = 'rgba(255, 255, 255, 0.08)';
        btnCrypto.style.background = 'transparent';
        
        panelStripe.style.display = 'block';
        panelCrypto.style.display = 'none';
        
        lblReference.innerText = 'Card Transaction Receipt Code';
        descReference.innerText = 'Submit a mock Stripe ID in sandbox mode to auto-activate the subscription instantly.';
        checkoutRef.value = 'CH-SANDBOX-' + Math.random().toString(36).substring(2, 10).toUpperCase();
        checkoutRef.placeholder = 'e.g. Stripe charge reference or Receipt ID';
    } else {
        btnStripe.style.borderColor = 'rgba(255, 255, 255, 0.08)';
        btnStripe.style.background = 'transparent';
        btnCrypto.style.borderColor = '#8B5CF6';
        btnCrypto.style.background = 'rgba(139, 92, 246, 0.08)';
        
        panelStripe.style.display = 'none';
        panelCrypto.style.display = 'block';
        
        lblReference.innerText = 'Blockchain Transaction Hash / TXID';
        descReference.innerText = 'Paste your transaction TXID hash to verify the block receipt on the ledger.';
        checkoutRef.value = '';
        checkoutRef.placeholder = 'e.g. 0x8b9c... or trc20 txid';
        
        // Update to initial selected coin
        updateCryptoDetails();
    }
}

function updateCryptoDetails() {
    var selector = document.getElementById('crypto_currency_selector');
    var coin = selector.value;
    var addressInput = document.getElementById('crypto_wallet_address');
    var qrImage = document.getElementById('crypto_qrcode');
    
    var walletAddress = cryptoWallets[coin] || 'TR7NHqJdjGdNF2DzFauk3FGLqmiejGLARi';
    addressInput.value = walletAddress;
    
    // Generate QR URL using a free QR code API
    qrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' + encodeURIComponent(walletAddress);
}

function copyWalletAddress() {
    var copyText = document.getElementById('crypto_wallet_address');
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices
    navigator.clipboard.writeText(copyText.value);
    
    alert('Wallet address copied to clipboard: ' + copyText.value);
}

function autoFillStripeSandbox() {
    document.getElementById('stripe_cardholder').value = 'Mizanur Rahman';
    document.getElementById('stripe_cardnumber').value = '4242 4242 4242 4242';
    document.getElementById('stripe_expiry').value = '12/28';
    document.getElementById('stripe_cvc').value = '311';
    
    var checkoutRef = document.getElementById('checkout_reference');
    checkoutRef.value = 'CH-SANDBOX-' + Math.random().toString(36).substring(2, 10).toUpperCase();
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>

