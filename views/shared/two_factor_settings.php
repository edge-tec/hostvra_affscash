<?php
/**
 * Shared 2FA settings view. The parent layout is picked by role so the page
 * inherits the correct sidebar / topbar without a separate copy per role.
 */
$_layoutMap = [
    'admin'             => '/views/layouts/admin.php',
    'affiliate'         => '/views/layouts/affiliate.php',
    'affiliate_manager' => '/views/layouts/affiliate_manager.php',
];
$_layoutFile = $_layoutMap[$role] ?? $_layoutMap['affiliate'];
require BASE_PATH . $_layoutFile;
?>

<div class="page-header">
    <div>
        <h1>&#128274; Google Authenticator</h1>
        <p>Account Settings &rarr; Security &rarr; Google Authenticator</p>
    </div>
    <?php
    $_profileUrl = '/' . $role . '/profile';
    ?>
    <a href="<?= Helpers::e($_profileUrl) ?>" class="btn btn-secondary">&larr; Back to Profile</a>
</div>

<?php foreach (Helpers::getFlash() as $_f): ?>
<div class="alert alert-<?= $_f['type'] === 'error' ? 'danger' : 'success' ?> mb-3"><?= Helpers::e($_f['message']) ?></div>
<?php endforeach; ?>

<div class="card mb-3" style="max-width:760px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span class="card-title">&#128737; Two-Factor Authentication Status</span>
        <?php if ($enabled): ?>
        <span class="badge" style="background:#D1FAE5;color:#065F46;font-weight:700;padding:4px 12px;border-radius:999px;font-size:12px">&#10003; Enabled</span>
        <?php else: ?>
        <span class="badge" style="background:#FEE2E2;color:#991B1B;font-weight:700;padding:4px 12px;border-radius:999px;font-size:12px">&#10005; Disabled</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p style="font-size:14px;color:var(--text-muted);margin-bottom:16px">
            Adds a second verification step at sign-in. After entering your password you'll
            also need a 6-digit code from <strong>Google Authenticator</strong>, <strong>Authy</strong>
            or <strong>Microsoft Authenticator</strong> on your phone.
        </p>

        <?php if ($enabled): ?>
        <!-- ─────────── Manage / Disable / Reconnect ─────────── -->
        <div style="background:#F0FDF4;border:1px solid #86EFAC;border-radius:10px;padding:14px 18px;margin-bottom:16px">
            <div style="font-weight:700;color:#065F46;font-size:14px;margin-bottom:4px">&#10003; Authenticator paired</div>
            <div style="font-size:13px;color:#047857">
                Enabled on
                <strong><?= $enabledAt ? Helpers::e(date('M j, Y H:i', strtotime($enabledAt))) : '—' ?></strong>
                · Account: <code style="background:#fff;padding:1px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($accountEmail) ?></code>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            <form method="POST" style="display:inline">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="start">
                <button class="btn btn-secondary">&#8635; Reconnect / Regenerate QR</button>
            </form>
        </div>

        <hr style="margin:18px 0;border:none;border-top:1px solid var(--border)">

        <h3 style="font-size:15px;font-weight:700;color:#991B1B;margin-bottom:10px">&#9888; Disable Google Authenticator</h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
            To disable 2FA, confirm with <strong>either</strong> your current account password <strong>or</strong> a 6-digit code from your authenticator app.
        </p>
        <form method="POST" onsubmit="return confirm('Disable Google Authenticator on this account?');" style="max-width:420px">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="disable">
            <div class="form-group">
                <label>Current password <span style="color:#94A3B8;font-weight:400">(or use OTP below)</span></label>
                <input type="password" name="current_password" class="form-control" autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>OR 6-digit authenticator code</label>
                <input type="text" name="otp_code" class="form-control" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code"
                       style="font-family:monospace;letter-spacing:8px;text-align:center;font-size:22px">
            </div>
            <button class="btn btn-danger">&#10005; Disable 2FA</button>
        </form>

        <?php elseif ($step === 'verify' && $pendingSecret !== ''): ?>
        <!-- ─────────── Step 2: Pair + verify a fresh secret ─────────── -->
        <div style="display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start" class="g2fa-grid">
            <div style="text-align:center">
                <div style="background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:8px;display:inline-block">
                    <!-- QR rendered client-side by qrcode.js (no third-party request).
                         The <img> is a same-origin proxy fallback if JS is unavailable. -->
                    <div id="g2fa-qr" style="width:220px;height:220px"></div>
                    <noscript>
                        <img src="<?= Helpers::e($qrUrl) ?>" alt="Authenticator QR code" style="width:220px;height:220px;display:block">
                    </noscript>
                </div>
                <div style="font-size:11px;color:#94A3B8;margin-top:8px">Scan with your authenticator app</div>
            </div>
            <div>
                <h3 style="font-size:15px;font-weight:700;margin-bottom:10px">Step 1 — Scan the QR code</h3>
                <ol style="font-size:13px;color:#475569;line-height:1.7;margin-left:18px;margin-bottom:16px">
                    <li>Install <strong>Google Authenticator</strong>, <strong>Authy</strong>, or <strong>Microsoft Authenticator</strong>.</li>
                    <li>Open the app and tap <em>Add account → Scan QR code</em>.</li>
                    <li>Scan the code on the left.</li>
                </ol>

                <h3 style="font-size:15px;font-weight:700;margin-bottom:8px">Or enter this key manually</h3>
                <div style="background:#F1F5F9;border:1px solid #CBD5E1;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:14px;letter-spacing:2px;color:#334155;margin-bottom:16px;word-break:break-all">
                    <?= Helpers::e(implode(' ', str_split($pendingSecret, 4))) ?>
                </div>

                <h3 style="font-size:15px;font-weight:700;margin-bottom:10px">Step 2 — Enter the 6-digit code</h3>
                <form method="POST" style="max-width:300px">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="verify">
                    <input type="text" name="otp_code" class="form-control" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code"
                           required autofocus
                           placeholder="123456"
                           style="font-family:monospace;letter-spacing:10px;text-align:center;font-size:26px;font-weight:800">
                    <div style="display:flex;gap:8px;margin-top:14px">
                        <button class="btn btn-primary">&#10003; Verify &amp; Activate</button>
                        <a href="<?= Helpers::e($g2faBase) ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ─────────── Intro / Enable ─────────── -->
        <ul style="font-size:13px;color:#475569;line-height:1.8;margin-bottom:16px;padding-left:18px">
            <li>Codes refresh every 30 seconds and work even when offline.</li>
            <li>Compatible with Google Authenticator, Authy, Microsoft Authenticator, 1Password, and most TOTP apps.</li>
            <li>You can disable or reconnect to a new device at any time from this page.</li>
        </ul>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="start">
            <button class="btn btn-primary">&#128274; Enable Google Authenticator</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width:640px) {
    .g2fa-grid { grid-template-columns: 1fr !important; }
    .g2fa-grid #g2fa-qr,
    .g2fa-grid img { width: 180px !important; height: 180px !important; }
}
#g2fa-qr canvas, #g2fa-qr img { width: 100% !important; height: 100% !important; display:block; }
</style>

<?php if ($step === 'verify' && $pendingSecret !== '' && !empty($otpauthUri)): ?>
<!-- Client-side QR generation. qrcode.js is ~6 KB, loaded from a public CDN.
     If the CDN is unreachable, we fall back to api.qrserver.com via JS. -->
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"
        onerror="this.dataset.failed='1'"></script>
<script>
(function(){
    var uri    = <?= json_encode($otpauthUri, JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var target = document.getElementById('g2fa-qr');
    if (!target) return;

    function showFallbackImage(){
        target.innerHTML = '';
        var img = document.createElement('img');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&ecc=M&margin=8&data=' + encodeURIComponent(uri);
        img.alt = 'Authenticator QR code';
        img.style.cssText = 'width:100%;height:100%;display:block';
        img.onerror = function(){
            target.innerHTML = '<div style="font-size:11px;color:#991B1B;padding:14px;text-align:center;line-height:1.5">'
                + 'QR rendering failed.<br>Use the manual key below.</div>';
        };
        target.appendChild(img);
    }

    try {
        if (typeof qrcode !== 'function') { showFallbackImage(); return; }
        // Type 0 = auto, 'M' = ~15% error correction (matches authenticator app expectations).
        var qr = qrcode(0, 'M');
        qr.addData(uri);
        qr.make();
        // 4-pixel module size, 8-pixel quiet zone — auto-scaled to 220px container by CSS.
        target.innerHTML = qr.createImgTag(4, 8);
        var img = target.querySelector('img');
        if (img) { img.style.width = '100%'; img.style.height = '100%'; img.style.display = 'block'; }
    } catch (e) {
        showFallbackImage();
    }
})();
</script>
<?php endif; ?>
