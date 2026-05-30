<?php
/**
 * views/admin/settings/crypto_wallet_qr.php
 *
 * Admin Crypto Wallet QR Code Page
 * ---------------------------------
 * Reads the saved wallet addresses from Config (same source as the Budget System
 * tab in Settings) and renders a read-only QR code card for each configured
 * wallet. Advertisers scan the QR or copy the address to send payment.
 *
 * HOW TO WIRE THIS PAGE IN:
 *
 * 1. Drop this file into:  views/admin/settings/crypto_wallet_qr.php
 *
 * 2. Add a controller entry (e.g. in your router / controllers/admin/SettingsController.php
 *    or a new CryptoWalletQrController.php):
 *
 *       $pageTitle = 'Crypto Wallet QR Codes';
 *       $cfg = Config::getGroup('config');          // same call used in SettingsController
 *       require BASE_PATH . '/views/admin/settings/crypto_wallet_qr.php';
 *
 * 3. Register a route, e.g.:
 *       '/admin/crypto-wallets' => 'admin/CryptoWalletQrController'
 *
 * 4. Add a sidebar / nav link pointing to /admin/crypto-wallets
 *
 * No database changes, no POST handlers — this page is purely read-only.
 */

Auth::check('admin');
$pageTitle = 'Crypto Wallet QR Codes';

// ── Pull wallet data from the same config key used by SettingsController ──────
$cfg           = Config::getGroup('config');
$pm            = $cfg['app']['payment_methods'] ?? [];
$cryptoWallets = $pm['crypto_wallets'] ?? [];

// Backward-compat: old single-string crypto field → pre-fill USDT
if (!is_array($cryptoWallets) && !empty($pm['crypto'])) {
    $cryptoWallets = ['usdt' => $pm['crypto']];
}

// Coin metadata: [label, network hint, icon, brand colour, QR foreground colour]
$coinMeta = [
    'usdt' => ['USDT',    'TRC20 · Tron Network',          '🔵', '#26A17B', '#26A17B'],
    'btc'  => ['Bitcoin', 'Bitcoin Network',                '🟠', '#F7931A', '#F7931A'],
    'ltc'  => ['Litecoin','Litecoin Network',               '⚪', '#345D9D', '#345D9D'],
    'eth'  => ['Ethereum','Ethereum Mainnet (ERC20)',        '🔷', '#627EEA', '#627EEA'],
    'bnb'  => ['BNB',     'BNB Smart Chain (BEP20)',        '🟡', '#F3BA2F', '#B8860B'],
    'trx'  => ['TRON',    'Tron Network',                   '🔴', '#FF0013', '#CC0010'],
];

// Only show coins that have a non-empty wallet address
$activeWallets = [];
foreach ($coinMeta as $coin => $meta) {
    $addr = trim($cryptoWallets[$coin] ?? '');
    if ($addr !== '') {
        $activeWallets[$coin] = array_merge($meta, ['address' => $addr]);
    }
}

require BASE_PATH . '/views/layouts/admin.php';
?>

<!-- ── Page header ─────────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1>🪙 Crypto Wallet QR Codes</h1>
        <p style="color:var(--text-muted);margin:0">
            Scan a QR code or copy the address below to make a cryptocurrency payment.
            Wallet addresses are configured by the administrator and cannot be edited here.
        </p>
    </div>
    <div>
        <a href="/admin/settings?tab=budget_system" class="btn btn-secondary btn-sm">
            ⚙️ Edit Wallet Addresses
        </a>
    </div>
</div>

<!-- ── Styles (scoped, no global pollution) ────────────────────────── -->
<style>
.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 8px;
}

.qr-card {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    transition: box-shadow .18s;
}
.qr-card:hover {
    box-shadow: 0 4px 18px rgba(0,0,0,.10);
}

.qr-card-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #F1F5F9;
}
.qr-coin-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.qr-coin-name {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.2;
}
.qr-coin-network {
    font-size: 11px;
    color: #64748B;
    font-weight: 500;
}

.qr-body {
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    flex: 1;
}

/* QR canvas wrapper */
.qr-canvas-wrap {
    width: 200px;
    height: 200px;
    border: 3px solid #F1F5F9;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    position: relative;
}
.qr-canvas-wrap canvas {
    display: block;
}

/* Coin logo overlay on QR centre */
.qr-centre-logo {
    position: absolute;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: #fff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px rgba(0,0,0,.08);
    pointer-events: none;
}

/* Wallet address box */
.qr-address-wrap {
    width: 100%;
}
.qr-address-label {
    font-size: 11px;
    font-weight: 600;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 6px;
}
.qr-address-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 8px;
    padding: 10px 12px;
}
.qr-address-text {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    color: #1E293B;
    font-weight: 600;
    word-break: break-all;
    flex: 1;
    line-height: 1.5;
}
.qr-copy-btn {
    flex-shrink: 0;
    background: none;
    border: 1.5px solid #CBD5E1;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, color .15s, border-color .15s;
}
.qr-copy-btn:hover {
    background: #4F46E5;
    color: #fff;
    border-color: #4F46E5;
}
.qr-copy-btn.copied {
    background: #16A34A;
    color: #fff;
    border-color: #16A34A;
}

/* Download button */
.qr-dl-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #4F46E5;
    background: #EEF2FF;
    border: none;
    border-radius: 6px;
    padding: 6px 14px;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, color .15s;
}
.qr-dl-btn:hover {
    background: #4F46E5;
    color: #fff;
}

/* Empty state */
.qr-empty {
    text-align: center;
    padding: 60px 24px;
    color: #94A3B8;
}
.qr-empty-icon { font-size: 48px; margin-bottom: 12px; }
.qr-empty-title { font-size: 18px; font-weight: 700; color: #475569; margin-bottom: 6px; }
.qr-empty-desc  { font-size: 14px; line-height: 1.6; }

/* Print */
@media print {
    .page-header a, .qr-copy-btn, .qr-dl-btn { display: none !important; }
    .qr-card { break-inside: avoid; border: 1px solid #ccc; box-shadow: none; }
}
</style>

<!-- ── Main content ────────────────────────────────────────────────── -->
<?php if (empty($activeWallets)): ?>
<!-- No wallets configured yet -->
<div class="card">
    <div class="card-body qr-empty">
        <div class="qr-empty-icon">🪙</div>
        <div class="qr-empty-title">No Crypto Wallets Configured</div>
        <div class="qr-empty-desc">
            Go to <strong>Settings → Budget System</strong> and enter at least one
            cryptocurrency wallet address. It will appear here as a scannable QR code.
        </div>
        <a href="/admin/settings?tab=budget_system" class="btn btn-primary" style="margin-top:18px">
            ⚙️ Configure Wallets
        </a>
    </div>
</div>

<?php else: ?>
<!-- Info banner -->
<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;color:#1E40AF">
    <span style="font-size:18px">ℹ️</span>
    <span>
        <strong><?= count($activeWallets) ?> wallet<?= count($activeWallets) > 1 ? 's' : '' ?> configured.</strong>
        Share these QR codes or addresses with advertisers so they can top up their account balance.
        Wallet addresses can only be changed in <a href="/admin/settings?tab=budget_system" style="color:#2563EB;font-weight:700">Settings → Budget System</a>.
    </span>
</div>

<!-- QR grid -->
<div class="qr-grid" id="qr-grid">
    <?php foreach ($activeWallets as $coin => [$label, $network, $icon, $brandColor, $qrColor, $address]): ?>
    <div class="qr-card" id="card-<?= $coin ?>">

        <!-- Card header -->
        <div class="qr-card-header">
            <div class="qr-coin-icon" style="background:<?= $brandColor ?>22">
                <span><?= $icon ?></span>
            </div>
            <div>
                <div class="qr-coin-name"><?= htmlspecialchars($label) ?></div>
                <div class="qr-coin-network"><?= htmlspecialchars($network) ?></div>
            </div>
        </div>

        <!-- Card body -->
        <div class="qr-body">

            <!-- QR code canvas -->
            <div class="qr-canvas-wrap">
                <canvas id="qr-<?= $coin ?>" width="194" height="194"></canvas>
                <div class="qr-centre-logo" id="logo-<?= $coin ?>"><?= $icon ?></div>
            </div>

            <!-- Wallet address display + copy -->
            <div class="qr-address-wrap">
                <div class="qr-address-label">Wallet Address</div>
                <div class="qr-address-box">
                    <span class="qr-address-text" id="addr-<?= $coin ?>"><?= htmlspecialchars($address) ?></span>
                    <button class="qr-copy-btn" onclick="copyAddr('<?= $coin ?>')" id="copy-<?= $coin ?>">
                        📋 Copy
                    </button>
                </div>
            </div>

            <!-- Download QR -->
            <button class="qr-dl-btn" onclick="downloadQr('<?= $coin ?>', '<?= htmlspecialchars($label, ENT_QUOTES) ?>')">
                ⬇️ Download QR
            </button>

        </div><!-- /.qr-body -->
    </div><!-- /.qr-card -->
    <?php endforeach; ?>
</div><!-- /.qr-grid -->
<?php endif; ?>

<!-- ── QR generation script ────────────────────────────────────────── -->
<!--
     Uses the lightweight qrcode.js library (MIT licence, no build step needed).
     CDN: https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js
     If your server is offline or you prefer self-hosting, download the file and
     change the src below to a local path, e.g. /assets/js/qrcode.min.js
-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
(function () {

    // ── Wallet data injected from PHP (read-only) ──────────────────
    var WALLETS = <?= json_encode(
        array_map(
            fn($w) => [
                'coin'    => $w[0],   // label
                'network' => $w[1],
                'icon'    => $w[2],
                'color'   => $w[4],   // qr foreground colour
                'address' => $w[5],
            ],
            $activeWallets
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>;

    // ── Generate one QR per wallet ─────────────────────────────────
    Object.keys(WALLETS).forEach(function (coin) {
        var info    = WALLETS[coin];
        var canvas  = document.getElementById('qr-' + coin);
        if (!canvas) return;

        // qrcode.js renders into a <div>; we redirect it into our canvas
        // by using a temporary off-screen div then copying the img src.
        var tmpDiv = document.createElement('div');
        tmpDiv.style.display = 'none';
        document.body.appendChild(tmpDiv);

        new QRCode(tmpDiv, {
            text:           info.address,
            width:          194,
            height:         194,
            colorDark:      info.color,
            colorLight:     '#ffffff',
            correctLevel:   QRCode.CorrectLevel.H  // High — leaves room for centre logo
        });

        // qrcode.js creates an <img> inside tmpDiv; copy its src to our canvas
        var img = tmpDiv.querySelector('img');
        if (!img) { document.body.removeChild(tmpDiv); return; }

        img.onload = function () {
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, 194, 194);
            ctx.drawImage(img, 0, 0, 194, 194);
            document.body.removeChild(tmpDiv);
        };

        // If img already loaded (cache hit)
        if (img.complete) img.onload();
    });

    // ── Copy address to clipboard ──────────────────────────────────
    window.copyAddr = function (coin) {
        var addrEl  = document.getElementById('addr-' + coin);
        var copyBtn = document.getElementById('copy-' + coin);
        if (!addrEl || !copyBtn) return;

        var text = addrEl.textContent.trim();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                flashCopied(copyBtn);
            }).catch(function () {
                legacyCopy(text, copyBtn);
            });
        } else {
            legacyCopy(text, copyBtn);
        }
    };

    function legacyCopy(text, btn) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); flashCopied(btn); } catch (e) {}
        document.body.removeChild(ta);
    }

    function flashCopied(btn) {
        btn.textContent = '✅ Copied!';
        btn.classList.add('copied');
        setTimeout(function () {
            btn.textContent = '📋 Copy';
            btn.classList.remove('copied');
        }, 2200);
    }

    // ── Download QR as PNG ─────────────────────────────────────────
    window.downloadQr = function (coin, coinLabel) {
        var canvas = document.getElementById('qr-' + coin);
        if (!canvas) return;

        // Create a larger export canvas (400×400) with padding + label
        var exp = document.createElement('canvas');
        exp.width  = 460;
        exp.height = 500;
        var ctx = exp.getContext('2d');

        // White background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, exp.width, exp.height);

        // Rounded rect border
        ctx.strokeStyle = '#E2E8F0';
        ctx.lineWidth   = 2;
        roundRect(ctx, 10, 10, exp.width - 20, exp.height - 20, 16);
        ctx.stroke();

        // Coin label at top
        ctx.fillStyle  = '#0F172A';
        ctx.font       = 'bold 22px system-ui, sans-serif';
        ctx.textAlign  = 'center';
        ctx.fillText(coinLabel + ' Wallet', exp.width / 2, 52);

        // Draw QR (scaled up from 194 → 340)
        ctx.drawImage(canvas, 60, 72, 340, 340);

        // Address text below QR
        var addrEl  = document.getElementById('addr-' + coin);
        var address = addrEl ? addrEl.textContent.trim() : '';
        ctx.fillStyle = '#475569';
        ctx.font      = '11px Courier New, monospace';
        ctx.textAlign = 'center';
        wrapText(ctx, address, exp.width / 2, 432, exp.width - 60, 16);

        // Trigger download
        var link  = document.createElement('a');
        link.download = 'wallet-qr-' + coin + '.png';
        link.href = exp.toDataURL('image/png');
        link.click();
    };

    // Canvas helper — rounded rectangle path
    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.arcTo(x + w, y, x + w, y + r, r);
        ctx.lineTo(x + w, y + h - r);
        ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
        ctx.lineTo(x + r, y + h);
        ctx.arcTo(x, y + h, x, y + h - r, r);
        ctx.lineTo(x, y + r);
        ctx.arcTo(x, y, x + r, y, r);
        ctx.closePath();
    }

    // Canvas helper — word-wrap long strings
    function wrapText(ctx, text, cx, y, maxWidth, lineHeight) {
        // Break into chunks of ~38 chars for monospace address readability
        var chunkSize = 38;
        var chunks = [];
        for (var i = 0; i < text.length; i += chunkSize) {
            chunks.push(text.slice(i, i + chunkSize));
        }
        chunks.forEach(function (chunk) {
            ctx.fillText(chunk, cx, y);
            y += lineHeight;
        });
    }

})();
</script>
