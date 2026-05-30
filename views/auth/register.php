<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Registration Wizard — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<?php $_authBgKey = 'auth_bg_register'; require BASE_PATH . '/views/partials/auth_bg.php'; ?>
<style>
body { 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    min-height: 100vh; 
    background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent), radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.12), transparent), #0B0F19; 
    font-family: 'Inter', sans-serif;
    color: #F8FAFC;
    padding: 40px 0;
}
.wizard-box { 
    background: rgba(17, 24, 39, 0.8); 
    backdrop-filter: blur(16px); 
    border: 1px solid rgba(255, 255, 255, 0.08); 
    border-radius: 20px; 
    box-shadow: 0 20px 50px rgba(0,0,0,0.6); 
    width: 100%; 
    max-width: <?= ($step === 3) ? '880px' : '520px' ?>; 
    overflow: hidden; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.wizard-header { 
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%); 
    border-bottom: 1px solid rgba(255,255,255,0.06); 
    padding: 30px 40px; 
    text-align: center; 
}
.wizard-header h1 { 
    font-size: 24px; 
    font-weight: 800; 
    background: linear-gradient(to right, #A5B4FC, #C7D2FE);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}
.wizard-header p { 
    font-size: 13.5px; 
    color: #94A3B8; 
    margin-top: 6px; 
}
.wizard-steps {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 20px;
}
.step-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    transition: all 0.3s;
}
.step-dot.active {
    background: #6366F1;
    box-shadow: 0 0 10px rgba(99, 102, 241, 0.6);
    transform: scale(1.3);
}
.step-dot.completed {
    background: #34D399;
}
.wizard-body { 
    padding: 40px; 
}
.form-group {
    margin-bottom: 22px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #C7D2FE;
    margin-bottom: 8px;
}
.wizard-footer { 
    padding: 20px 40px; 
    background: rgba(15, 23, 42, 0.4); 
    border-top: 1px solid rgba(255, 255, 255, 0.04); 
    text-align: center; 
    font-size: 13.5px; 
    color: #64748B; 
}
.wizard-footer a { 
    color: #818CF8; 
    font-weight: 600; 
    text-decoration: none;
}
.wizard-footer a:hover {
    text-decoration: underline;
}
.glass-input {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #F8FAFC;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14.5px;
    width: 100%;
    transition: all 0.3s;
}
.glass-input:focus {
    border-color: rgba(99, 102, 241, 0.6);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    outline: none;
}
.btn-wizard {
    background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 15px;
    border: none;
    padding: 14px 24px;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}
.btn-wizard:hover {
    transform: translateY(-2px);
    opacity: 0.95;
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}
.plan-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}
.plan-card {
    background: rgba(30, 41, 59, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.plan-card:hover {
    border-color: rgba(99, 102, 241, 0.3);
    background: rgba(30, 41, 59, 0.7);
    transform: translateY(-4px);
}
.plan-price {
    font-size: 32px;
    font-weight: 800;
    color: #FFFFFF;
    margin: 15px 0;
}
.plan-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    font-size: 13.5px;
    color: #94A3B8;
    text-align: left;
}
.plan-features li {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.domain-prefix-input {
    display: flex;
    align-items: center;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    overflow: hidden;
    padding-right: 14px;
}
.domain-prefix-input input {
    border: none;
    background: transparent;
    padding: 12px 16px;
    color: #F8FAFC;
    font-size: 14.5px;
    width: 60%;
    outline: none;
}
.domain-prefix-input span {
    color: #818CF8;
    font-weight: 600;
    font-size: 13.5px;
    text-align: right;
    width: 40%;
    white-space: nowrap;
}
</style>
</head>
<body>
<div class="auth-theme-picker"><?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?></div>

<div class="wizard-box">
    
    <!-- Wizard Step Header -->
    <div class="wizard-header">
        <div style="font-size: 36px; margin-bottom: 10px;">⚡</div>
        <h1>Deploy Admin Tracker Workspace</h1>
        <p>
            <?php if ($step === 1): ?>
                Step 1: Account Creation &amp; Subdomain Prefixes
            <?php elseif ($step === 2): ?>
                Step 2: Identity OTP Verification Code
            <?php elseif ($step === 3): ?>
                Step 3: Select Workspace Membership Plan
            <?php endif; ?>
        </p>

        <!-- Dots -->
        <div class="wizard-steps">
            <div class="step-dot <?= ($step >= 1) ? 'active' : '' ?> <?= ($step > 1) ? 'completed' : '' ?>"></div>
            <div class="step-dot <?= ($step >= 2) ? 'active' : '' ?> <?= ($step > 2) ? 'completed' : '' ?>"></div>
            <div class="step-dot <?= ($step >= 3) ? 'active' : '' ?>"></div>
        </div>
    </div>

    <!-- Wizard Form Body -->
    <div class="wizard-body">
        
        <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border-left: 4px solid #EF4444; color: #FCA5A5; padding: 14px 20px; border-radius: 8px; font-size: 13.5px; margin-bottom: 25px;">
            ⚠️ <?= Helpers::e($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- STEP 1 Form -->
            <form method="POST" action="/signup?step=1">
                <?= Helpers::csrf() ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required class="glass-input" placeholder="First Name" value="<?= Helpers::e($_POST['first_name'] ?? $wizard['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required class="glass-input" placeholder="Last Name" value="<?= Helpers::e($_POST['last_name'] ?? $wizard['last_name']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="company_name">Company / Network Name</label>
                    <input type="text" id="company_name" name="company_name" required class="glass-input" placeholder="e.g. Acme Tracker Network" value="<?= Helpers::e($_POST['company_name'] ?? $wizard['company_name']) ?>">
                </div>

                <div class="form-group">
                    <label for="domain_prefix">Desired Workspace Subdomain Prefix</label>
                    <div class="domain-prefix-input">
                        <input type="text" id="domain_prefix" name="domain_prefix" required placeholder="e.g. acme" value="<?= Helpers::e($_POST['domain_prefix'] ?? $wizard['domain_prefix']) ?>">
                        <?php 
                        $appDomain = parse_url(Config::get('config', 'app.url') ?? 'http://eliteali.com', PHP_URL_HOST) ?: 'eliteali.com';
                        ?>
                        <span>.<?= Helpers::e($appDomain) ?></span>
                    </div>
                    <span style="font-size: 11px; color: #94A3B8; margin-top: 6px; display: block;">This acts as your main login and dashboard entry point.</span>
                </div>

                <div class="form-group">
                    <label for="email">Contact Administrator Email</label>
                    <input type="email" id="email" name="email" required class="glass-input" placeholder="you@company.com" value="<?= Helpers::e($_POST['email'] ?? $wizard['email']) ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="password">Choose Password</label>
                        <input type="password" id="password" name="password" required class="glass-input" placeholder="Min. 8 characters">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="glass-input" placeholder="Re-type password">
                    </div>
                </div>

                <button type="submit" class="btn-wizard" style="margin-top: 10px;">
                    Continue to Email Verification &rarr;
                </button>
            </form>

        <?php elseif ($step === 2): ?>
            <!-- STEP 2 Form -->
            <form method="POST" action="/signup?step=2">
                <?= Helpers::csrf() ?>
                
                <div style="text-align: center; margin-bottom: 30px;">
                    <span style="font-size: 32px;">✉️</span>
                    <p style="font-size: 14.5px; color: #94A3B8; margin-top: 12px; line-height: 1.6;">
                        A 6-digit verification code has been dispatched to:<br>
                        <strong style="color: #FFFFFF;"><?= Helpers::e($wizard['email']) ?></strong>.<br>
                        Please input the verification code below to verify your identity.
                    </p>
                </div>

                <div class="form-group" style="text-align: center;">
                    <label for="verification_code" style="text-align: center; display: block; font-size: 14px;">Enter 6-Digit Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" required class="glass-input" style="font-size: 26px; font-weight: 800; text-align: center; letter-spacing: 6px; max-width: 240px; margin: 10px auto 0;" placeholder="000000" maxlength="6" autofocus>
                </div>

                <button type="submit" class="btn-wizard" style="margin-top: 20px;">
                    Verify Identity &amp; Setup Workspace &rarr;
                </button>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="/signup?step=1" style="color: #94A3B8; font-size: 13px; text-decoration: none;">&larr; Back to Step 1</a>
                </div>
            </form>

        <?php elseif ($step === 3): ?>
            <!-- STEP 3 Form -->
            <form method="POST" action="/signup?step=3" id="activationForm">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="plan_option" id="plan_option" value="trial">
                <input type="hidden" name="plan_id" id="selected_plan_id" value="1">

                <!-- 14-Day Free Trial Card -->
                <?php if (($gw['trial']['enabled'] ?? '1') === '1'): ?>
                <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.08) 100%); border: 2px solid #6366F1; border-radius: 16px; padding: 24px 30px; margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #34D399; background: rgba(52, 211, 153, 0.1); padding: 4px 8px; border-radius: 6px; display: inline-block; margin-bottom: 8px;">Recommended For Starters</span>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #FFFFFF;">Deploy 14-Day Free Trial Space</h3>
                        <p style="margin: 4px 0 0; font-size: 13.5px; color: #94A3B8;">Get instant access to full click-tracking features. No credit card required.</p>
                    </div>
                    <div>
                        <button type="button" onclick="activateTrial()" class="btn-wizard" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                            🚀 Launch Free Trial
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div style="text-align: center; margin-bottom: 25px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #FFFFFF;">Pro Subscription Plans</h3>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #94A3B8;">Activate premium tier limits with high click traffic rotations.</p>
                </div>

                <!-- Plans Grid -->
                <div class="plan-grid">
                    <?php foreach ($plans as $p): ?>
                        <div class="plan-card">
                            <div>
                                <h4 style="margin: 0; font-size: 16px; color: #A5B4FC; font-weight: 700;"><?= Helpers::e($p['name']) ?></h4>
                                <div class="plan-price">$<?= number_format($p['price'], 0) ?><span style="font-size: 13px; font-weight: 400; color: #94A3B8;">/mo</span></div>
                                <ul class="plan-features">
                                    <li><span>✅</span> <?= (int)$p['max_users'] ? $p['max_users'] . ' Team Members' : 'Unlimited Team' ?></li>
                                    <li><span>✅</span> <?= (int)$p['max_offers'] ? $p['max_offers'] . ' Campaigns/Offers' : 'Unlimited Campaigns' ?></li>
                                    <li><span>✅</span> <?= (int)$p['max_click_limits'] ? number_format($p['max_click_limits']) . ' Clicks/mo' : 'Unlimited Clicks' ?></li>
                                    <li><span>✅</span> Custom Domains &amp; SSL</li>
                                </ul>
                            </div>
                            <button type="button" onclick="selectPaidPlan(<?= $p['id'] ?>)" style="width: 100%; border: 1px solid #818CF8; background: transparent; color: #FFFFFF; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13.5px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='#818CF8'" onmouseout="this.style.background='transparent'">
                                Subscribe Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

            </form>
        <?php endif; ?>

    </div>

    <!-- Wizard Footer -->
    <div class="wizard-footer">
        Already have an administrator account? <a href="/login">Log in here</a>
    </div>

</div>

<script src="/assets/js/app.js"></script>
<script>
function activateTrial() {
    document.getElementById('plan_option').value = 'trial';
    document.getElementById('activationForm').submit();
}

function selectPaidPlan(planId) {
    document.getElementById('plan_option').value = 'paid';
    document.getElementById('selected_plan_id').value = planId;
    document.getElementById('activationForm').submit();
}
</script>
</body>
</html>
