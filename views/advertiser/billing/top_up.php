<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>Top Up Balance</h1>
        <p>Add funds to your advertiser account via manual payment</p>
    </div>
    <a href="/advertiser/billing" class="btn btn-secondary">&larr; Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error"><?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php
// Build per-coin wallet map from admin config — passed as JS for dynamic display
$cryptoWallets  = $methods['crypto_wallets'] ?? [];
$cryptoCoinMeta = [
    'usdt' => ['label' => 'USDT (Tether · TRC20)', 'icon' => '🔵', 'placeholder' => 'Enter your USDT TRC20 wallet address (starts with T)'],
    'btc'  => ['label' => 'BTC (Bitcoin)',          'icon' => '🟠', 'placeholder' => 'Enter your Bitcoin wallet address'],
    'ltc'  => ['label' => 'LTC (Litecoin)',         'icon' => '⚪', 'placeholder' => 'Enter your Litecoin wallet address'],
    'eth'  => ['label' => 'ETH (Ethereum)',         'icon' => '🔷', 'placeholder' => 'Enter your Ethereum wallet address (0x…)'],
    'bnb'  => ['label' => 'BNB (Binance · BEP20)',  'icon' => '🟡', 'placeholder' => 'Enter your BNB BEP20 wallet address (0x…)'],
    'trx'  => ['label' => 'TRX (Tron)',             'icon' => '🔴', 'placeholder' => 'Enter your Tron wallet address (starts with T)'],
    'other'=> ['label' => 'Other',                  'icon' => '⚙️', 'placeholder' => 'Enter your wallet address'],
];
// Only expose coins that have an admin-configured wallet (+ Other always shown)
$availableCoins = ['other'];
foreach (['usdt','btc','ltc','eth','bnb','trx'] as $c) {
    if (!empty($cryptoWallets[$c])) array_unshift($availableCoins, $c);
}
$selectedCoin = strtolower($_POST['crypto_type'] ?? '');
?>

<div class="card" style="max-width:680px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="topup-form" novalidate>
            <?= Helpers::csrf() ?>

            <!-- Method selector -->
            <div class="form-group">
                <label style="font-weight:700">Payment Method *</label>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:8px">
                    <?php foreach (['bank' => ['Bank', '🏦'], 'crypto' => ['Crypto', '🪙'], 'capitalist' => ['Capitalist', '💳']] as $key => $meta):
                        $available = !empty($methods[$key]);
                        $checked   = ($_POST['method'] ?? '') === $key;
                    ?>
                    <label class="pm-label" data-method="<?= $key ?>" style="display:flex;align-items:flex-start;gap:10px;padding:14px 16px;border:2px solid <?= $checked ? '#0F766E' : '#E2E8F0' ?>;border-radius:10px;cursor:<?= $available ? 'pointer' : 'not-allowed' ?>;background:<?= $checked ? '#ECFEFF' : '#fff' ?>;opacity:<?= $available ? 1 : .5 ?>;transition:.15s">
                        <input type="radio" name="method" value="<?= $key ?>" <?= $checked ? 'checked' : '' ?> <?= $available ? '' : 'disabled' ?> style="margin-top:3px;accent-color:#0F766E;flex-shrink:0">
                        <div>
                            <div style="font-weight:700;font-size:14px"><?= $meta[1] ?> <?= $meta[0] ?></div>
                            <div style="font-size:11px;color:#64748B;margin-top:2px"><?= $available ? 'Available' : 'Unavailable' ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bank / Capitalist wallet display -->
            <?php foreach (['bank','capitalist'] as $key):
                if (empty($methods[$key])) continue;
                $show = (($_POST['method'] ?? '') === $key);
            ?>
            <div id="pm-<?= $key ?>" class="pm-details" style="display:<?= $show ? 'block' : 'none' ?>;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px 16px;margin-bottom:18px">
                <div style="font-size:11px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">
                    <?= ucfirst($key) ?> &middot; Send payment to:
                </div>
                <pre style="margin:0;font-size:13px;color:#0F172A;white-space:pre-wrap;font-family:monospace;background:transparent;padding:0"><?= Helpers::e($methods[$key]) ?></pre>
            </div>
            <?php endforeach; ?>

            <!-- Crypto panel: coin dropdown + dynamic wallet address display -->
            <?php $showCrypto = (($_POST['method'] ?? '') === 'crypto'); ?>
            <div id="pm-crypto" class="pm-details" style="display:<?= $showCrypto ? 'block' : 'none' ?>;margin-bottom:18px">

                <!-- Coin selector dropdown -->
                <div class="form-group" style="margin-bottom:12px">
                    <label style="font-weight:600;font-size:13px">Select Cryptocurrency *</label>
                    <select id="crypto-select" name="crypto_type" class="form-control" style="font-size:14px;padding:10px 12px;border:2px solid #E2E8F0;border-radius:8px;margin-top:6px">
                        <option value="">— Choose a cryptocurrency —</option>
                        <?php foreach ($availableCoins as $coin):
                            $meta = $cryptoCoinMeta[$coin];
                        ?>
                        <option value="<?= $coin ?>" <?= ($selectedCoin === $coin) ? 'selected' : '' ?>><?= $meta['icon'] ?> <?= Helpers::e($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Admin wallet address display (for configured coins) -->
                <div id="crypto-wallet-display" style="display:none;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;margin-bottom:12px">
                    <div style="font-size:11px;font-weight:700;color:#065F46;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">
                        Send <span id="wallet-coin-label"></span> to this address:
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
                        <!-- QR Code -->
                        <div style="flex-shrink:0;text-align:center">
                            <div id="wallet-qr-code" style="background:#fff;border:1px solid #D1FAE5;border-radius:8px;padding:8px;display:inline-block;line-height:0"></div>
                            <div style="font-size:10px;color:#64748B;margin-top:4px">Scan to copy address</div>
                        </div>
                        <!-- Address text + copy -->
                        <div style="flex:1;min-width:0">
                            <code id="wallet-address-text" style="display:block;font-size:13px;font-weight:700;color:#0F172A;word-break:break-all;background:#fff;border:1px solid #D1FAE5;border-radius:6px;padding:8px 10px;font-family:monospace;margin-bottom:8px"></code>
                            <button type="button" id="copy-wallet-btn" onclick="copyWallet()" title="Copy address"
                                    style="padding:7px 14px;border:1px solid #6EE7B7;border-radius:6px;background:#ECFDF5;color:#065F46;font-size:12px;cursor:pointer;font-weight:600">
                                📋 Copy Address
                            </button>
                            <div id="copy-msg" style="font-size:11px;color:#059669;margin-top:6px;display:none">✓ Copied to clipboard!</div>
                        </div>
                    </div>
                </div>

                <!-- "Other" wallet input (custom coin not in admin list) -->
                <div id="other-wallet-wrap" style="display:none;margin-bottom:12px">
                    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;font-size:13px;color:#92400E;margin-bottom:10px">
                        ⚠️ Please contact support to confirm the wallet address for your cryptocurrency before sending.
                    </div>
                </div>

                <!-- Per-coin format hint -->
                <div id="wallet-format-hint" style="display:none;font-size:12px;color:#64748B;margin-top:4px;padding:0 2px">
                    <span id="wallet-format-text"></span>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Amount (USD) *</label>
                    <input type="number" step="0.01" min="1" name="amount" class="form-control" required
                           value="<?= Helpers::e($_POST['amount'] ?? '') ?>" placeholder="100.00">
                </div>
                <div class="form-group">
                    <label>Transaction ID / Reference *</label>
                    <input type="text" name="txn_id" class="form-control" required
                           value="<?= Helpers::e($_POST['txn_id'] ?? '') ?>" placeholder="TX1234567890">
                </div>
            </div>

            <div class="form-group">
                <label>Payment Screenshot <span style="color:#94A3B8;font-weight:400">(JPG/PNG/WEBP/PDF, max 6 MB)</span></label>
                <input type="file" name="screenshot" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf">
                <div class="form-hint">Attach a receipt or transaction screenshot so we can verify your payment faster.</div>
            </div>

            <div class="alert alert-info" style="font-size:13px">
                <strong>How it works:</strong> Send the amount using the selected method, fill in the transaction details above, and submit. An admin will verify the payment and credit your balance — typically within a few hours.
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary" id="submit-btn">Submit Top-Up Request</button>
                <a href="/advertiser/billing" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    // ── QR Code generator ──────────────────────────────────────────────────
    var _qrInstance = null;
    function renderQr(address) {
        var container = document.getElementById('wallet-qr-code');
        container.innerHTML = '';
        if (!address || typeof QRCode === 'undefined') return;
        _qrInstance = new QRCode(container, {
            text:           address,
            width:          140,
            height:         140,
            colorDark:      '#0F172A',
            colorLight:     '#FFFFFF',
            correctLevel:   QRCode.CorrectLevel.M,
        });
    }
    // ── Wallet data from admin config ──────────────────────────────────────
    var WALLETS = <?= json_encode(array_map('strval', array_filter(array_intersect_key($cryptoWallets, array_flip(['usdt','btc','ltc','eth','bnb','trx'])), fn($v) => $v !== '')), JSON_UNESCAPED_SLASHES) ?>;

    var COIN_META = {
        usdt:  { label: 'USDT (TRC20)', hint: 'TRC20 address — starts with T, 34 characters',
                  regex: /^T[1-9A-HJ-NP-Za-km-z]{33}$/ },
        btc:   { label: 'BTC',  hint: 'Bitcoin address — starts with 1, 3, or bc1',
                  regex: /^(1[1-9A-HJ-NP-Za-km-z]{24,33}|3[1-9A-HJ-NP-Za-km-z]{24,33}|bc1[a-z0-9]{6,87})$/ },
        ltc:   { label: 'LTC',  hint: 'Litecoin address — starts with L, M, or ltc1',
                  regex: /^([LM][1-9A-HJ-NP-Za-km-z]{25,34}|ltc1[a-z0-9]{6,87})$/ },
        eth:   { label: 'ETH',  hint: 'Ethereum address — 0x followed by 40 hex characters',
                  regex: /^0x[0-9a-fA-F]{40}$/ },
        bnb:   { label: 'BNB (BEP20)', hint: 'BEP20 address — 0x followed by 40 hex characters',
                  regex: /^0x[0-9a-fA-F]{40}$/ },
        trx:   { label: 'TRX',  hint: 'Tron address — starts with T, 34 characters',
                  regex: /^T[1-9A-HJ-NP-Za-km-z]{33}$/ },
        other: { label: 'Crypto', hint: 'Enter your wallet address', regex: null },
    };

    // ── Method radio toggle ────────────────────────────────────────────────
    document.querySelectorAll('input[name="method"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.pm-details').forEach(function (el) { el.style.display = 'none'; });
            document.querySelectorAll('.pm-label').forEach(function (l) {
                l.style.borderColor = '#E2E8F0'; l.style.background = '#fff';
            });
            var panel = document.getElementById('pm-' + this.value);
            if (panel) panel.style.display = 'block';
            this.closest('label').style.borderColor = '#0F766E';
            this.closest('label').style.background  = '#ECFEFF';
            if (this.value !== 'crypto') resetCryptoPanel();
        });
    });

    // ── Crypto coin dropdown ───────────────────────────────────────────────
    var sel      = document.getElementById('crypto-select');
    var walletDisp = document.getElementById('crypto-wallet-display');
    var walletText = document.getElementById('wallet-address-text');
    var coinLabel  = document.getElementById('wallet-coin-label');
    var otherWrap  = document.getElementById('other-wallet-wrap');
    var hintBox    = document.getElementById('wallet-format-hint');
    var hintText   = document.getElementById('wallet-format-text');

    sel.addEventListener('change', updateCryptoPanel);

    // Run on page load if a coin was already selected (POST re-render)
    if (sel.value) updateCryptoPanel();

    function updateCryptoPanel() {
        var coin = sel.value;
        var meta = COIN_META[coin];
        if (!coin || !meta) { resetCryptoPanel(); return; }

        coinLabel.textContent = meta.label;
        hintText.textContent  = '📐 Format: ' + meta.hint;
        hintBox.style.display = 'block';

        var adminWallet = WALLETS[coin] || '';
        if (adminWallet && coin !== 'other') {
            walletText.textContent   = adminWallet;
            walletDisp.style.display = 'block';
            otherWrap.style.display  = 'none';
            renderQr(adminWallet);
        } else {
            walletDisp.style.display = 'none';
            otherWrap.style.display  = (coin === 'other') ? 'block' : 'none';
            renderQr('');
        }
    }

    function resetCryptoPanel() {
        walletDisp.style.display = 'none';
        otherWrap.style.display  = 'none';
        hintBox.style.display    = 'none';
        renderQr('');
    }

    // ── Copy wallet address ────────────────────────────────────────────────
    window.copyWallet = function () {
        var txt = walletText.textContent.trim();
        if (!txt) return;
        navigator.clipboard.writeText(txt).then(function () {
            var msg = document.getElementById('copy-msg');
            msg.style.display = 'block';
            setTimeout(function () { msg.style.display = 'none'; }, 2000);
        });
    };

    // ── Client-side form validation ────────────────────────────────────────
    document.getElementById('topup-form').addEventListener('submit', function (e) {
        var method = document.querySelector('input[name="method"]:checked');
        if (!method) { e.preventDefault(); alert('Please select a payment method.'); return; }

        if (method.value === 'crypto') {
            var coin = sel.value;
            if (!coin) { e.preventDefault(); alert('Please select a cryptocurrency.'); sel.focus(); return; }

            // Validate txn_id is filled (already required, but reinforce for crypto)
            var txn = document.querySelector('input[name="txn_id"]');
            if (!txn.value.trim()) { e.preventDefault(); alert('Transaction ID is required.'); txn.focus(); return; }
        }
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>

