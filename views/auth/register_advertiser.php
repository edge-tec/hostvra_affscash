<!DOCTYPE html>
<html lang="en" data-theme="<?= Theme::current() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Advertiser Registration — <?= Helpers::e(Config::get('config','app.name') ?? 'AffiliateTracker') ?></title>
<link rel="canonical" href="<?= htmlspecialchars(rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?'), '/'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="/assets/css/app.min.css">
<?php require BASE_PATH . '/views/partials/theme_head.php'; ?>
<?php require BASE_PATH . '/views/partials/auth_theme.php'; ?>
<?php $_authBgKey = 'auth_bg_advreg'; require BASE_PATH . '/views/partials/auth_bg.php'; ?>
<?php if (Turnstile::isEnabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
body {
<?php if (empty($_authBgActive)): ?>
  background: #05020c !important;
  background-image: 
      radial-gradient(ellipse 50% 60% at 80% 30%,rgba(124,58,237,.08),transparent),
      radial-gradient(ellipse 40% 45% at 10% 70%,rgba(232,25,122,.06),transparent) !important;
<?php endif; ?>
  color: #fff !important;
  padding:40px 20px;
  overflow-x: hidden;
}
.auth-box {
  background: rgba(15, 10, 36, 0.65) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-radius: 20px !important;
  box-shadow: 0 20px 50px rgba(0,0,0,0.35), inset 0 1px 1px rgba(255,255,255,0.15) !important;
  backdrop-filter: blur(20px) !important;
  -webkit-backdrop-filter: blur(20px) !important;
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
  overflow: hidden;
  position: relative;
  z-index: 1;
}
.auth-header { padding:28px 32px 16px; color:#fff; text-align:center; }
.auth-header h1 { font-size:20px; font-weight:700; color:#fff; }
.auth-header p { font-size:13px; color:rgba(255,255,255,0.6); margin-top:4px; }
.auth-footer { padding:16px 32px; background:rgba(15,10,36,0.3) !important; border-top:1px solid rgba(255,255,255,0.06) !important; text-align:center; font-size:13px; color:rgba(255,255,255,0.5) !important; border-radius:0 0 16px 16px; }
.auth-footer a { color:#a855f7; font-weight:600; text-decoration:none; }
.section-title { font-size:13px; font-weight:700; color:#a855f7; text-transform:uppercase; letter-spacing:.06em; margin:24px 0 16px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.08); }
.form-label, label { color: rgba(255,255,255,0.8) !important; font-size:12px !important; }
.form-control, .form-control-custom, input[type="text"], input[type="password"], input[type="email"], select, textarea {
  background: rgba(255,255,255,0.03) !important;
  border: 1px solid rgba(255,255,255,0.08) !important;
  color: #fff !important;
}
.form-control:focus, .form-control-custom:focus {
  border-color: rgba(124,58,237,0.4) !important;
  box-shadow: 0 0 12px rgba(124,58,237,0.2) !important;
}
/* Autofill override: prevents browser autofill from making the box background white */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active,
select:-webkit-autofill {
  -webkit-text-fill-color: #ffffff !important;
  -webkit-box-shadow: 0 0 0 1000px #0b071e inset !important;
  box-shadow: 0 0 0 1000px #0b071e inset !important;
  transition: background-color 5000s ease-in-out 0s;
}
.btn-primary, button[type="submit"] {
  background: linear-gradient(135deg,#7c3aed 0%,#3b82f6 50%,#0ea5e9 100%) !important;
  border: none !important;
  color: #fff !important;
  box-shadow: 0 4px 16px rgba(124,58,237,0.3) !important;
}

/* Responsive media query overrides for form grids */
@media (max-width: 768px) {
  .form-row.cols-2,
  .form-row,
  div[style*="grid-template-columns"],
  #contact-fields {
    grid-template-columns: 1fr !important;
  }
}
.contact-alert {
  background: rgba(245, 158, 11, 0.1) !important;
  border: 1px solid rgba(245, 158, 11, 0.3) !important;
  color: #fde047 !important;
  border-radius: 8px !important;
  padding: 10px 14px !important;
  margin-bottom: 14px !important;
  font-size: 13px !important;
}

/* Password input wrapper styling */
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.password-input-wrapper input {
    width: 100%;
    padding-right: 40px !important;
}
.password-toggle-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 16px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
    z-index: 10;
}
.password-toggle-btn:hover {
    color: var(--primary);
}

/* Password strength meter styling */
.password-strength-container {
    margin-top: 12px;
    margin-bottom: 20px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 14px;
    transition: all 0.3s ease;
}
.password-strength-label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 600;
}
.password-strength-label-row .title {
    color: var(--text);
}
.password-strength-status {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
}
.status-default { background: var(--border); color: var(--text-muted); }
.status-weak { background: #FEE2E2; color: #EF4444; }
.status-medium { background: #FEF3C7; color: #D97706; }
.status-good { background: #D1FAE5; color: #059669; }
.status-strong { background: #DCFCE7; color: #15803D; }

.password-strength-bar {
    height: 6px;
    background: var(--border);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 12px;
}
.password-strength-progress {
    height: 100%;
    width: 0;
    border-radius: 3px;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.password-requirements-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}
.password-requirements-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 6px 16px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.password-requirement-item {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s ease;
}
.password-requirement-item.valid {
    color: #10B981;
}
.password-requirement-item .icon {
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
</head>
<body>
<div class="auth-theme-picker"><?php require BASE_PATH . '/views/partials/theme_toggle.php'; ?></div>
<div class="auth-box">
    <div class="auth-header">
        <?php
        $loginLogo      = Config::get('config','app.login_logo') ?: Config::get('config','app.logo');
        $loginLogoWhite = !empty(Config::get('config','app.login_logo_white'));
        if ($loginLogo): ?>
        <img src="<?= Helpers::e($loginLogo) ?>" alt="Logo" style="max-height:40px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto">
        <?php endif; ?>
        <h1>&#128200; Advertiser Registration</h1>
        <p>Start running offers on our network. Post your CPA campaigns today.</p>
    </div>
    <div class="auth-body">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?= Helpers::csrf() ?>
            <p class="section-title">Company Information</p>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Company Name *</label>
                <input type="text" name="company" class="form-control" required value="<?= Helpers::e($_POST['company'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Email *</label>
                <input type="email" name="email" class="form-control" required value="<?= Helpers::e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" required placeholder="+1 555 000 0000" value="<?= Helpers::e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Country *</label>
                    <select name="country" class="form-control" required>
                        <option value="">— Select Country —</option>
                        <?php
                        $countries = [
                            'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AR'=>'Argentina','AU'=>'Australia',
                            'AT'=>'Austria','AZ'=>'Azerbaijan','BH'=>'Bahrain','BD'=>'Bangladesh','BY'=>'Belarus',
                            'BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BO'=>'Bolivia','BA'=>'Bosnia and Herzegovina',
                            'BR'=>'Brazil','BN'=>'Brunei','BG'=>'Bulgaria','KH'=>'Cambodia','CA'=>'Canada',
                            'CL'=>'Chile','CN'=>'China','CO'=>'Colombia','CR'=>'Costa Rica','HR'=>'Croatia',
                            'CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DO'=>'Dominican Republic',
                            'EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','EE'=>'Estonia','ET'=>'Ethiopia',
                            'FI'=>'Finland','FR'=>'France','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana',
                            'GR'=>'Greece','GT'=>'Guatemala','HN'=>'Honduras','HK'=>'Hong Kong','HU'=>'Hungary',
                            'IN'=>'India','ID'=>'Indonesia','IR'=>'Iran','IQ'=>'Iraq','IE'=>'Ireland',
                            'IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JO'=>'Jordan',
                            'KZ'=>'Kazakhstan','KE'=>'Kenya','KW'=>'Kuwait','LV'=>'Latvia','LB'=>'Lebanon',
                            'LT'=>'Lithuania','LU'=>'Luxembourg','MY'=>'Malaysia','MV'=>'Maldives','MX'=>'Mexico',
                            'MD'=>'Moldova','MN'=>'Mongolia','MA'=>'Morocco','MM'=>'Myanmar','NP'=>'Nepal',
                            'NL'=>'Netherlands','NZ'=>'New Zealand','NG'=>'Nigeria','NO'=>'Norway','OM'=>'Oman',
                            'PK'=>'Pakistan','PS'=>'Palestine','PA'=>'Panama','PY'=>'Paraguay','PE'=>'Peru',
                            'PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','QA'=>'Qatar','RO'=>'Romania',
                            'RU'=>'Russia','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SG'=>'Singapore',
                            'SK'=>'Slovakia','ZA'=>'South Africa','KR'=>'South Korea','ES'=>'Spain','LK'=>'Sri Lanka',
                            'SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syria','TW'=>'Taiwan','TJ'=>'Tajikistan',
                            'TZ'=>'Tanzania','TH'=>'Thailand','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan',
                            'UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom',
                            'US'=>'United States','UY'=>'Uruguay','UZ'=>'Uzbekistan','VE'=>'Venezuela',
                            'VN'=>'Vietnam','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe',
                        ];
                        $selCountry = strtoupper($_POST['country'] ?? '');
                        foreach ($countries as $code => $name):
                        ?>
                        <option value="<?= $code ?>" <?= $selCountry === $code ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="section-title">Contact Details <span style="color:#EF4444;font-size:11px;font-weight:600;text-transform:none;letter-spacing:0">(at least one required)</span></p>
            <div class="contact-alert">
                &#9888; Please provide at least one contact method so we can reach you.
            </div>
            <div class="form-row cols-2" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:5px;font-weight:600">
                        <span style="background:#2AABEE;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px">TG</span> Telegram ID
                    </label>
                    <input type="text" name="telegram" id="adv_telegram" class="form-control" placeholder="@username"
                           value="<?= Helpers::e($_POST['telegram'] ?? '') ?>"
                           oninput="checkAdvContact()">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:5px;font-weight:600">
                        <span style="background:#00AFF0;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px">SK</span> Skype ID
                    </label>
                    <input type="text" name="skype" id="adv_skype" class="form-control" placeholder="live:username"
                           value="<?= Helpers::e($_POST['skype'] ?? '') ?>"
                           oninput="checkAdvContact()">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:5px;font-weight:600">
                        <span style="background:#5865F2;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px">DC</span> Discord ID
                    </label>
                    <input type="text" name="discord" id="adv_discord" class="form-control" placeholder="username"
                           value="<?= Helpers::e($_POST['discord'] ?? '') ?>"
                           oninput="checkAdvContact()">
                </div>
            </div>
            <div id="adv-contact-warn" style="display:none;color:#DC2626;font-size:12px;margin:-8px 0 12px;font-weight:600">
                &#9888; Fill in at least one contact method to continue.
            </div>

            <p class="section-title">Account Security</p>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="reg_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('reg_password', this)">
                            👁️
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)">
                            👁️
                        </button>
                    </div>
                </div>
            </div>

            <!-- Visual Password Strength Meter Container -->
            <div class="password-strength-container">
                <div class="password-strength-label-row">
                    <span class="title">Password Strength</span>
                    <span id="strength-status-text" class="password-strength-status status-default">Too Short</span>
                </div>
                <div class="password-strength-bar">
                    <div id="strength-bar-progress" class="password-strength-progress"></div>
                </div>
                <div class="password-requirements-title">Requirements</div>
                <ul class="password-requirements-list">
                    <li id="req-length" class="password-requirement-item">
                        <span class="icon">❌</span> 8+ characters
                    </li>
                    <li id="req-uppercase" class="password-requirement-item">
                        <span class="icon">❌</span> 1+ uppercase letter
                    </li>
                    <li id="req-lowercase" class="password-requirement-item">
                        <span class="icon">❌</span> 1+ lowercase letter
                    </li>
                    <li id="req-number" class="password-requirement-item">
                        <span class="icon">❌</span> 1+ number
                    </li>
                    <li id="req-special" class="password-requirement-item">
                        <span class="icon">❌</span> 1+ special character
                    </li>
                </ul>
            </div>

            <?php if (!empty($questions)): ?>
            <p class="section-title">Additional Information</p>
            <?php foreach ($questions as $q): ?>
            <div class="form-group">
                <label><?= Helpers::e($q['question_text']) ?><?= $q['is_required'] ? ' *' : '' ?></label>
                <?php
                $opts   = $q['options'] ? json_decode($q['options'], true) : [];
                $posted = $_POST['q_' . $q['id']] ?? '';
                if ($q['field_type'] === 'select'): ?>
                    <select name="q_<?= $q['id'] ?>" class="form-control" <?= $q['is_required'] ? 'required' : '' ?>>
                        <option value="">Select...</option>
                        <?php foreach($opts as $opt): ?>
                        <option value="<?= Helpers::e($opt) ?>" <?= $posted === $opt ? 'selected' : '' ?>><?= Helpers::e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($q['field_type'] === 'textarea'): ?>
                    <textarea name="q_<?= $q['id'] ?>" class="form-control"><?= Helpers::e(is_string($posted) ? $posted : '') ?></textarea>
                <?php else: ?>
                    <input type="text" name="q_<?= $q['id'] ?>" class="form-control" value="<?= Helpers::e(is_string($posted) ? $posted : '') ?>" <?= $q['is_required'] ? 'required' : '' ?>>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Mandatory consent — Privacy Policy + Terms & Conditions -->
            <div style="margin-top:20px;padding:14px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;color:rgba(255,255,255,0.85)">
                    <input type="checkbox" name="agree_privacy" value="1" required
                           <?= !empty($_POST['agree_privacy']) ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#7c3aed;width:16px;height:16px;flex-shrink:0">
                    <span>I have read and accept the <a href="/privacy-policy" target="_blank" style="color:#a78bfa;font-weight:600">Privacy Policy</a>. <span style="color:#EF4444">*</span></span>
                </label>
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;color:rgba(255,255,255,0.85);margin-top:10px">
                    <input type="checkbox" name="agree_terms" value="1" required
                           <?= !empty($_POST['agree_terms']) ? 'checked' : '' ?>
                           style="margin-top:3px;accent-color:#7c3aed;width:16px;height:16px;flex-shrink:0">
                    <span>I agree to the <a href="/terms-of-service" target="_blank" style="color:#a78bfa;font-weight:600">Terms &amp; Conditions</a>. <span style="color:#EF4444">*</span></span>
                </label>
            </div>

            <?php if (Turnstile::isEnabled()): ?>
            <!-- Cloudflare Turnstile widget -->
            <div style="margin-top:20px;display:flex;justify-content:center">
                <div class="cf-turnstile"
                     data-sitekey="<?= Helpers::e(Turnstile::siteKey()) ?>"
                     data-callback="onTurnstileSuccess"
                     data-expired-callback="onTurnstileExpired"
                     data-theme="light">
                </div>
            </div>
            <button type="submit" id="advRegSubmitBtn"
                    class="btn btn-primary"
                    style="width:100%;margin-top:14px;background:linear-gradient(135deg,#0F766E,#0891B2);opacity:.5;cursor:not-allowed"
                    disabled>
                Submit Application
            </button>
            <?php else: ?>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;background:linear-gradient(135deg,#0F766E,#0891B2)">Submit Application</button>
            <?php endif; ?>
        </form>
    </div>
    <div class="auth-footer">
        Already have an account? <a href="/login">Sign in</a>
        <div style="margin-top:10px;font-size:12px">
            <a href="/privacy-policy" target="_blank" style="color:var(--text-muted)">Privacy Policy</a>
            &nbsp;&middot;&nbsp;
            <a href="/terms-of-service" target="_blank" style="color:var(--text-muted)">Terms &amp; Conditions</a>
        </div>
        <div style="margin-top:10px"><a href="/" style="color:var(--text-muted);font-size:12px">&#8592; Back to Home</a></div>
    </div>
</div>
<script src="/assets/js/app.min.js"></script>
<script>
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

function checkAdvContact() {
    var tg = document.getElementById('adv_telegram').value.trim();
    var sk = document.getElementById('adv_skype').value.trim();
    var dc = document.getElementById('adv_discord').value.trim();
    var warn = document.getElementById('adv-contact-warn');
    if (!tg && !sk && !dc) {
        warn.style.display = 'block';
        ['adv_telegram','adv_skype','adv_discord'].forEach(function(id) {
            document.getElementById(id).style.borderColor = '#FCA5A5';
        });
    } else {
        warn.style.display = 'none';
        ['adv_telegram','adv_skype','adv_discord'].forEach(function(id) {
            document.getElementById(id).style.borderColor = '';
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            var tg = document.getElementById('adv_telegram').value.trim();
            var sk = document.getElementById('adv_skype').value.trim();
            var dc = document.getElementById('adv_discord').value.trim();
            if (!tg && !sk && !dc) {
                e.preventDefault();
                document.getElementById('adv-contact-warn').style.display = 'block';
                document.getElementById('adv_telegram').scrollIntoView({behavior:'smooth', block:'center'});
            }
        });
    }

    var passwordInput = document.getElementById('reg_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            var val = passwordInput.value;
            
            // Validation tests
            var lengthValid = val.length >= 8;
            var upperValid = /[A-Z]/.test(val);
            var lowerValid = /[a-z]/.test(val);
            var numberValid = /[0-9]/.test(val);
            var specialValid = /[^a-zA-Z0-9]/.test(val);
            
            // Update checklist items
            updateReqItem('req-length', lengthValid);
            updateReqItem('req-uppercase', upperValid);
            updateReqItem('req-lowercase', lowerValid);
            updateReqItem('req-number', numberValid);
            updateReqItem('req-special', specialValid);
            
            // Calculate score (0 to 5)
            var score = 0;
            if (val.length > 0) {
                if (lengthValid) score++;
                if (upperValid) score++;
                if (lowerValid) score++;
                if (numberValid) score++;
                if (specialValid) score++;
            }
            
            // Update strength bar UI
            var bar = document.getElementById('strength-bar-progress');
            var statusText = document.getElementById('strength-status-text');
            
            if (val.length === 0) {
                bar.style.width = '0%';
                bar.style.backgroundColor = '';
                statusText.textContent = 'Too Short';
                statusText.className = 'password-strength-status status-default';
            } else if (score <= 2) {
                bar.style.width = '25%';
                bar.style.backgroundColor = '#EF4444'; // Red
                statusText.textContent = 'Weak';
                statusText.className = 'password-strength-status status-weak';
            } else if (score === 3) {
                bar.style.width = '50%';
                bar.style.backgroundColor = '#F59E0B'; // Orange/Amber
                statusText.textContent = 'Medium';
                statusText.className = 'password-strength-status status-medium';
            } else if (score === 4) {
                bar.style.width = '75%';
                bar.style.backgroundColor = '#10B981'; // Green (emerald)
                statusText.textContent = 'Good';
                statusText.className = 'password-strength-status status-good';
            } else if (score === 5) {
                bar.style.width = '100%';
                bar.style.backgroundColor = '#059669'; // Dark green
                statusText.textContent = 'Strong';
                statusText.className = 'password-strength-status status-strong';
            }
        });
    }
    
    function updateReqItem(id, isValid) {
        var li = document.getElementById(id);
        if (li) {
            if (isValid) {
                li.classList.add('valid');
                li.querySelector('.icon').textContent = '✅';
            } else {
                li.classList.remove('valid');
                li.querySelector('.icon').textContent = '❌';
            }
        }
    }
});
</script>
<?php if (Turnstile::isEnabled()): ?>
<script>
function onTurnstileSuccess(token) {
    var btn = document.getElementById('advRegSubmitBtn');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}
function onTurnstileExpired() {
    var btn = document.getElementById('advRegSubmitBtn');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '.5';
        btn.style.cursor = 'not-allowed';
    }
}
</script>
<?php endif; ?>
<?php if (empty($_authBgActive)): ?>
<?php if (empty($_authBgActive)): ?>
<canvas id="authParticlesCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
<?php endif; ?>
<script>
  (function() {
    var canvas = document.getElementById('authParticlesCanvas');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var particles = [];
    var w, h;
    var mouse = { x: null, y: null, active: false };
    
    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);
    
    window.addEventListener('mousemove', function(e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
      mouse.active = true;
    });
    window.addEventListener('mouseleave', function() {
      mouse.active = false;
    });
    
    var numParticles = 80;
    var initLimitX = Math.max(450, w * 1.2);
    var initLimitY = Math.max(300, h * 1.2);
    var initLimitZ = 200;
    for (var i = 0; i < numParticles; i++) {
      particles.push({
        x: (Math.random() - 0.5) * initLimitX * 2,
        y: (Math.random() - 0.5) * initLimitY * 2,
        z: (Math.random() - 0.5) * initLimitZ * 2,
        vx: (Math.random() - 0.5) * 0.4,
        vy: (Math.random() - 0.5) * 0.4,
        vz: (Math.random() - 0.5) * 0.4,
        size: Math.random() * 2 + 1.5,
        color: Math.random() > 0.5 ? 'rgba(124, 58, 237, 0.35)' : 'rgba(14, 165, 233, 0.3)'
      });
    }
    
    var fov = 400;
    
    function draw() {
      ctx.clearRect(0, 0, w, h);
      
      var projected = [];
      var limitX = Math.max(450, w * 1.2);
      var limitY = Math.max(300, h * 1.2);
      var limitZ = 200;
      particles.forEach(function(p) {
        p.x += p.vx;
        p.y += p.vy;
        p.z += p.vz;
        
        if (Math.abs(p.x) > limitX) p.vx *= -1;
        if (Math.abs(p.y) > limitY) p.vy *= -1;
        if (Math.abs(p.z) > limitZ) p.vz *= -1;
        
        var rotY = 0.0006;
        var cosY = Math.cos(rotY), sinY = Math.sin(rotY);
        var x1 = p.x * cosY - p.z * sinY;
        var z1 = p.z * cosY + p.x * sinY;
        p.x = x1; p.z = z1;
        
        var rotX = 0.0004;
        var cosX = Math.cos(rotX), sinX = Math.sin(rotX);
        var y1 = p.y * cosX - p.z * sinX;
        var z2 = p.z * cosX + p.y * sinX;
        p.y = y1; p.z = z2;
        
        var scale = fov / (fov + p.z);
        var px = (p.x * scale) + (w / 2);
        var py = (p.y * scale) + (h / 2);
        
        projected.push({
          x: px,
          y: py,
          z: p.z,
          size: p.size * scale,
          color: p.color
        });
      });
      
      for (var a = 0; a < projected.length; a++) {
        var pa = projected[a];
        for (var b = a + 1; b < projected.length; b++) {
          var pb = projected[b];
          var dx = pa.x - pb.x;
          var dy = pa.y - pb.y;
          var dist = Math.hypot(dx, dy);
          
          if (dist < 110) {
            var opacity = (1 - (dist / 110)) * 0.15 * (1 - (pa.z + pb.z) / 400);
            if (opacity > 0) {
              ctx.beginPath();
              ctx.moveTo(pa.x, pa.y);
              ctx.lineTo(pb.x, pb.y);
              ctx.strokeStyle = 'rgba(124, 58, 237, ' + opacity + ')';
              ctx.lineWidth = 0.8 * (1 - (pa.z + pb.z) / 400);
              ctx.stroke();
            }
          }
        }
        
        if (pa.x >= 0 && pa.x <= w && pa.y >= 0 && pa.y <= h) {
          ctx.fillStyle = pa.color;
          ctx.beginPath();
          ctx.arc(pa.x, pa.y, Math.max(0.5, pa.size), 0, Math.PI * 2);
          ctx.fill();
        }
      }
      
      if (mouse.active) {
        projected.forEach(function(p) {
          var dist = Math.hypot(mouse.x - p.x, mouse.y - p.y);
          if (dist < 150) {
            var opacity = (1 - (dist / 150)) * 0.22;
            ctx.beginPath();
            ctx.moveTo(mouse.x, mouse.y);
            ctx.lineTo(p.x, p.y);
            ctx.strokeStyle = 'rgba(14, 165, 233, ' + opacity + ')';
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        });
      }
      
      requestAnimationFrame(draw);
    }
    draw();
  })();
</script>
<?php endif; ?>
</body>
</html>
