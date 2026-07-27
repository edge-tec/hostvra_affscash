<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Blocked — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.min.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<style>
body {
    background: linear-gradient(135deg, #1E1B4B 0%, #312E81 40%, #4C1D95 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    margin: 0;
}

.vpn-block-box {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #E2E8F0);
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255,255,255,0.05) inset;
    width: 100%;
    max-width: 520px;
    overflow: hidden;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

.vpn-block-header {
    background: linear-gradient(135deg, #DC2626, #B91C1C);
    padding: 36px 32px 28px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.vpn-block-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
    animation: shimmer 6s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { transform: translate(0, 0); }
    50%      { transform: translate(10%, 10%); }
}

.vpn-block-icon {
    width: 72px;
    height: 72px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 36px;
    backdrop-filter: blur(4px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.2); }
    50%      { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
}

.vpn-block-header h1 {
    color: #fff;
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 6px;
    position: relative;
    z-index: 1;
    letter-spacing: -0.01em;
}

.vpn-block-header p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 13px;
    margin: 0;
    position: relative;
    z-index: 1;
}

.vpn-block-body {
    padding: 32px;
}

.vpn-block-message {
    background: linear-gradient(135deg, #FEF2F2, #FFF1F2);
    border: 1px solid #FECACA;
    border-left: 4px solid #DC2626;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 24px;
}

.vpn-block-message p {
    color: #991B1B;
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
    font-weight: 500;
}

.vpn-block-reason {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg, #F8FAFC);
    border: 1px solid var(--border, #E2E8F0);
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 24px;
}

.vpn-block-reason .badge {
    background: #DC2626;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}

.vpn-block-reason .text {
    color: var(--text, #334155);
    font-size: 13px;
    font-weight: 600;
}

.vpn-block-steps {
    margin-bottom: 24px;
}

.vpn-block-steps h3 {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-muted, #64748B);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 14px;
}

.vpn-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
}

.vpn-step:not(:last-child) {
    border-bottom: 1px solid var(--border, #F1F5F9);
}

.vpn-step-num {
    width: 26px;
    height: 26px;
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.vpn-step-text {
    color: var(--text, #334155);
    font-size: 13px;
    line-height: 1.5;
    padding-top: 3px;
}

.vpn-block-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.vpn-block-actions .btn-retry {
    display: block;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.vpn-block-actions .btn-retry:hover {
    
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.vpn-block-footer {
    padding: 16px 32px;
    background: var(--bg, #F8FAFC);
    border-top: 1px solid var(--border, #E2E8F0);
    text-align: center;
    border-radius: 0 0 20px 20px;
}

.vpn-block-footer a {
    color: var(--text-muted, #64748B);
    font-size: 12px;
    text-decoration: none;
    transition: color 0.2s ease;
}

.vpn-block-footer a:hover {
    color: var(--primary, #4F46E5);
}

.vpn-block-footer .sep {
    margin: 0 8px;
    color: var(--border, #CBD5E1);
}
</style>
</head>
<body>
<div class="vpn-block-box">
    <div class="vpn-block-header">
        <div class="vpn-block-icon">🛡️</div>
        <h1>Access Blocked</h1>
        <p>Security protection is active</p>
    </div>
    <div class="vpn-block-body">
        <div class="vpn-block-message">
            <p>Registration is not allowed while using VPN or Proxy. Please disable it and try again.</p>
        </div>

        <?php if (!empty($_vpnBlockReason)): ?>
        <div class="vpn-block-reason">
            <span class="badge">Blocked</span>
            <span class="text"><?= Helpers::e($_vpnBlockReason) ?></span>
        </div>
        <?php endif; ?>

        <div class="vpn-block-steps">
            <h3>How to proceed</h3>
            <div class="vpn-step">
                <span class="vpn-step-num">1</span>
                <span class="vpn-step-text">Disconnect from your VPN, Proxy, or TOR browser</span>
            </div>
            <div class="vpn-step">
                <span class="vpn-step-num">2</span>
                <span class="vpn-step-text">Use your regular internet connection (home WiFi or mobile data)</span>
            </div>
            <div class="vpn-step">
                <span class="vpn-step-num">3</span>
                <span class="vpn-step-text">Refresh this page or click the button below to try again</span>
            </div>
        </div>

        <div class="vpn-block-actions">
            <a href="javascript:location.reload()" class="btn-retry">&#128260; Try Again</a>
        </div>
    </div>
    <div class="vpn-block-footer">
        <a href="/login">Sign In</a>
        <span class="sep">·</span>
        <a href="/">Back to Home</a>
        <span class="sep">·</span>
        <a href="/privacy-policy" target="_blank">Privacy Policy</a>
    </div>
</div>
</body>
</html>
