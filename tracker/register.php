<?php
/**
 * /tracker/register.php
 * Standalone affiliate registration page — shares the same session & DB as the main tracker.
 * Served directly by Apache (real file, bypasses router).
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

if (!file_exists(CONFIG_PATH . '/config.json')) {
    header('Location: /install/');
    exit;
}

// Bootstrap the tracker framework
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Activity.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Mailer.php';
require BASE_PATH . '/core/Referral.php';
require BASE_PATH . '/core/Turnstile.php';

Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// Start the same session as the main tracker
Auth::start();

// Already logged in → go to dashboard
if (Auth::id()) {
    Helpers::redirect('/' . Auth::role() . '/dashboard');
}

// ── Safe column migrations (idempotent) ───────────────────────────────────────
foreach (['skype VARCHAR(200)','telegram VARCHAR(200)','discord VARCHAR(200)',
          'email_verify_token VARCHAR(64)','email_verified_at DATETIME'] as $col) {
    try { Database::query("ALTER TABLE users ADD COLUMN $col DEFAULT NULL"); } catch (\Throwable $e) {}
}

// ── Referral handling ─────────────────────────────────────────────────────────
if (!empty($_GET['ref'])) {
    $_SESSION['referral_code'] = strtoupper(trim($_GET['ref']));
}
$refCode      = $_SESSION['referral_code'] ?? '';
$referrerInfo = $refCode ? Referral::resolveCode($refCode) : null;

// ── Dynamic registration questions from DB ────────────────────────────────────
$questions = [];
try {
    $questions = Database::fetchAll(
        "SELECT * FROM `registration_questions`
         WHERE target_role IN ('affiliate','both') AND is_active=1
         ORDER BY sort_order"
    );
} catch (\Throwable $e) { /* table may not exist yet */ }

// ── Handle POST ───────────────────────────────────────────────────────────────
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {

        // Turnstile CAPTCHA
        if (Turnstile::isEnabled()) {
            $tsToken = trim($_POST['cf-turnstile-response'] ?? '');
            if (!Turnstile::verify($tsToken, $_SERVER['REMOTE_ADDR'] ?? '')) {
                $errors[] = 'CAPTCHA verification failed. Please complete the challenge.';
            }
        }

        // Sanitize inputs
        $fname    = htmlspecialchars(trim($_POST['first_name']  ?? ''), ENT_QUOTES, 'UTF-8');
        $lname    = htmlspecialchars(trim($_POST['last_name']   ?? ''), ENT_QUOTES, 'UTF-8');
        $email    = strtolower(trim(filter_var($_POST['email']  ?? '', FILTER_SANITIZE_EMAIL)));
        $company  = htmlspecialchars(trim($_POST['company']     ?? ''), ENT_QUOTES, 'UTF-8');
        $phone    = htmlspecialchars(trim($_POST['phone']       ?? ''), ENT_QUOTES, 'UTF-8');
        $country  = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $_POST['country'] ?? ''), 0, 2));
        $skype    = htmlspecialchars(trim($_POST['skype']       ?? ''), ENT_QUOTES, 'UTF-8') ?: null;
        $telegram = htmlspecialchars(trim($_POST['telegram']    ?? ''), ENT_QUOTES, 'UTF-8') ?: null;
        $discord  = htmlspecialchars(trim($_POST['discord']     ?? ''), ENT_QUOTES, 'UTF-8') ?: null;
        $pass     = $_POST['password']         ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Validation
        if (!$fname || !$lname)                          $errors[] = 'First and last name are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Please enter a valid email address.';
        if (strlen($pass) < 8)                           $errors[] = 'Password must be at least 8 characters.';
        if ($pass !== $confirm)                          $errors[] = 'Passwords do not match.';

        // Duplicate email check (prepared statement — SQL injection safe)
        if (!$errors) {
            try {
                if (Database::fetchOne("SELECT id FROM `users` WHERE email = ?", [$email])) {
                    $errors[] = 'That email address is already registered.';
                }
            } catch (\Throwable $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }

        // Collect dynamic question answers
        $answers = [];
        foreach ($questions as $q) {
            $val = $_POST['q_' . $q['id']] ?? '';
            if (is_array($val)) $val = implode(', ', $val);
            $val = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
            if ($q['is_required'] && $val === '') {
                $errors[] = 'Please answer: ' . htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8');
            }
            $answers[$q['id']] = $val;
        }

        // ── Create account ────────────────────────────────────────────────────
        if (!$errors) {
            // Run Referral DDL BEFORE opening the transaction (prevents implicit commit inside txn)
            try { Referral::migrate(); } catch (\Throwable $e) {}

            Database::begin();
            try {
                $userId = Database::insert('users', [
                    'email'         => $email,
                    'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role'          => 'affiliate',
                    'status'        => 'pending',
                    'first_name'    => $fname,
                    'last_name'     => $lname,
                    'company'       => $company,
                    'phone'         => $phone,
                    'country'       => $country,
                    'skype'         => $skype,
                    'telegram'      => $telegram,
                    'discord'       => $discord,
                ]);

                $affCode = 'AFF' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                Database::insert('affiliates', [
                    'user_id'              => $userId,
                    'affiliate_code'       => $affCode,
                    'registration_answers' => json_encode($answers),
                ]);

                // Hook referral (after affiliate row exists)
                if ($referrerInfo && ($referrerInfo['status'] ?? '') === 'active') {
                    $newAff = Database::fetchOne("SELECT id FROM affiliates WHERE user_id = ?", [$userId]);
                    if ($newAff) {
                        Referral::recordSignup(
                            (int)$referrerInfo['user_id'],
                            $referrerInfo['role'],
                            (int)$newAff['id'],
                            $userId
                        );
                    }
                }
                unset($_SESSION['referral_code']);

                // Admin notification
                try {
                    Database::insert('notifications', [
                        'user_id'     => null,
                        'target_role' => 'admin',
                        'type'        => 'info',
                        'title'       => 'New Affiliate Registration',
                        'message'     => "$fname $lname has registered as an affiliate and is pending approval.",
                        'link'        => '/admin/affiliates',
                    ]);
                } catch (\Throwable $e) {}

                // Email verification (optional feature)
                $emailVerify = (Config::get('config', 'app.email_verification') === '1');
                if ($emailVerify) {
                    $verifyToken = bin2hex(random_bytes(32));
                    Database::update('users', ['email_verify_token' => $verifyToken], 'id=?', [$userId]);
                    $appUrl   = rtrim(Config::get('config', 'app.url') ?? '', '/');
                    $siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
                    $verifyUrl = $appUrl . '/verify-email?token=' . $verifyToken;
                    $body = '<div style="font-family:sans-serif;max-width:520px;margin:0 auto">
                        <h2 style="color:#4F46E5">Verify Your Email — ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</h2>
                        <p>Hello <strong>' . htmlspecialchars("$fname $lname", ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                        <p>Please verify your email by clicking below:</p>
                        <div style="text-align:center;margin:28px 0">
                          <a href="' . $verifyUrl . '" style="background:#4F46E5;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700">Verify Email</a>
                        </div></div>';
                    try { Mailer::sendRaw($email, "$fname $lname", "Verify your email — $siteName", $body, 'email_verification'); } catch (\Exception $_e) {}
                }

                Database::commit();

                // Welcome email
                try {
                    Mailer::sendEvent($email, "$fname $lname", 'affiliate_created', [
                        'name'           => "$fname $lname",
                        'email'          => $email,
                        'site_name'      => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                        'app_url'        => rtrim(Config::get('config', 'app.url') ?? '', '/'),
                        'affiliate_code' => $affCode,
                    ]);
                } catch (\Exception $_e) {}

                // Flash success message and redirect to login
                $regMsg = Config::get('config', 'app.registration_message')
                    ?: "Registration successful! Your account is pending approval. Contact support via Telegram: @affscashnet";
                if ($emailVerify) {
                    $regMsg = "Registration successful! A verification link has been sent to $email. Please verify your email to continue.";
                }
                Helpers::flash('success', $regMsg);
                Helpers::redirect('/tracker/login.php');

            } catch (\Exception $e) {
                try { Database::rollback(); } catch (\Throwable $rb) {}
                $errors[] = 'Registration failed. Please try again or contact support.';
            }
        }
    }
}

// ── View config ───────────────────────────────────────────────────────────────
$appName = Config::get('config', 'app.name') ?? 'AffsCash';
$appLogo = Config::get('config', 'app.logo');

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Affiliate Registration</title>
<link rel="stylesheet" href="/assets/css/app.min.css">
<?php if (Turnstile::isEnabled()): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<style>
  body{background:linear-gradient(135deg,#EEF2FF 0%,#F0FDF4 100%);padding:40px 20px}
  .auth-box{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);width:100%;max-width:600px;margin:0 auto;overflow:hidden}
  .auth-header{background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:28px 32px;color:#fff}
  .auth-header h1{font-size:20px;font-weight:700}
  .auth-header p{font-size:13px;opacity:.85;margin-top:4px}
  .auth-body{padding:32px}
  .auth-footer{padding:16px 32px;background:var(--bg);border-top:1px solid var(--border);text-align:center;font-size:13px;color:var(--text-muted);border-radius:0 0 16px 16px}
  .auth-footer a{color:var(--primary);font-weight:600}
  .section-title{font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin:24px 0 16px;padding-bottom:8px;border-bottom:1px solid var(--border)}
  .back-home{display:block;margin-top:10px;font-size:12px;color:var(--text-muted)!important;font-weight:400!important}
</style>
</head>
<body>
<div class="auth-box">

  <!-- Header -->
  <div class="auth-header">
    <?php if ($appLogo): ?>
      <img src="<?= htmlspecialchars($appLogo, ENT_QUOTES, 'UTF-8') ?>"
           alt="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
           style="max-height:44px;max-width:180px;object-fit:contain;margin-bottom:12px;display:block">
    <?php endif; ?>
    <h1>&#128101; Affiliate Registration</h1>
    <p>Join <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> and start earning. Registration is free.</p>
  </div>

  <!-- Body -->
  <div class="auth-body">

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?>
          <div>&#8226; <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Referral notice -->
    <?php if ($referrerInfo): ?>
      <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#1E40AF;display:flex;align-items:center;gap:10px">
        <span style="font-size:20px">🔗</span>
        <div>
          <strong>Referral Link</strong><br>
          You were referred by <strong><?= htmlspecialchars($referrerInfo['first_name'] . ' ' . $referrerInfo['last_name'], ENT_QUOTES, 'UTF-8') ?></strong>.
          <?= $referrerInfo['role'] === 'affiliate_manager' ? 'You will be assigned to their team.' : 'Your activity will earn them a referral commission.' ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Registration form — posts back to THIS file -->
    <form method="POST" action="/tracker/register.php">
      <?= Helpers::csrf() ?>

      <p class="section-title">Personal Information</p>
      <div class="form-row cols-2">
        <div class="form-group">
          <label>First Name *</label>
          <input type="text" name="first_name" class="form-control" required
                 value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <input type="text" name="last_name" class="form-control" required
                 value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" class="form-control" required
               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-row cols-2">
        <div class="form-group">
          <label>Company / Website</label>
          <input type="text" name="company" class="form-control"
                 value="<?= htmlspecialchars($_POST['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Country (2-letter code)</label>
        <input type="text" name="country" class="form-control" placeholder="US" maxlength="2"
               value="<?= htmlspecialchars($_POST['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-row cols-2" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:5px"><span style="color:#00AFF0">&#128222;</span> Skype</label>
          <input type="text" name="skype" class="form-control" placeholder="live:username"
                 value="<?= htmlspecialchars($_POST['skype'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:5px"><span style="color:#2AABEE">&#128232;</span> Telegram</label>
          <input type="text" name="telegram" class="form-control" placeholder="@username"
                 value="<?= htmlspecialchars($_POST['telegram'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:5px"><span style="color:#5865F2">&#127918;</span> Discord</label>
          <input type="text" name="discord" class="form-control" placeholder="username"
                 value="<?= htmlspecialchars($_POST['discord'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>

      <p class="section-title">Account Security</p>
      <div class="form-row cols-2">
        <div class="form-group">
          <label>Password * <small style="color:var(--text-muted);font-weight:400">(min 8 chars)</small></label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
      </div>

      <!-- Dynamic questions from DB -->
      <?php if (!empty($questions)): ?>
        <p class="section-title">Additional Information</p>
        <?php foreach ($questions as $q): ?>
          <div class="form-group">
            <label><?= htmlspecialchars($q['question_text'], ENT_QUOTES, 'UTF-8') ?><?= $q['is_required'] ? ' *' : '' ?></label>
            <?php
              $opts   = $q['options'] ? json_decode($q['options'], true) : [];
              $posted = $_POST['q_' . $q['id']] ?? '';
              if ($q['field_type'] === 'text' || $q['field_type'] === 'url'): ?>
                <input type="<?= $q['field_type'] === 'url' ? 'url' : 'text' ?>"
                       name="q_<?= (int)$q['id'] ?>" class="form-control"
                       value="<?= htmlspecialchars(is_string($posted) ? $posted : '', ENT_QUOTES, 'UTF-8') ?>"
                       <?= $q['is_required'] ? 'required' : '' ?>>
            <?php elseif ($q['field_type'] === 'textarea'): ?>
                <textarea name="q_<?= (int)$q['id'] ?>" class="form-control"
                          <?= $q['is_required'] ? 'required' : '' ?>><?= htmlspecialchars(is_string($posted) ? $posted : '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php elseif ($q['field_type'] === 'select'): ?>
                <select name="q_<?= (int)$q['id'] ?>" class="form-control" <?= $q['is_required'] ? 'required' : '' ?>>
                  <option value="">Select...</option>
                  <?php foreach ((array)$opts as $opt): ?>
                    <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                      <?= $posted === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
            <?php elseif ($q['field_type'] === 'radio'): ?>
                <?php foreach ((array)$opts as $opt): ?>
                  <div class="form-check">
                    <input type="radio" name="q_<?= (int)$q['id'] ?>"
                           value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $posted === $opt ? 'checked' : '' ?>
                           <?= $q['is_required'] ? 'required' : '' ?>>
                    <label><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></label>
                  </div>
                <?php endforeach; ?>
            <?php elseif ($q['field_type'] === 'checkbox'): ?>
                <?php $postedArr = is_array($posted) ? $posted : []; ?>
                <?php foreach ((array)$opts as $opt): ?>
                  <div class="form-check">
                    <input type="checkbox" name="q_<?= (int)$q['id'] ?>[]"
                           value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                           <?= in_array($opt, $postedArr) ? 'checked' : '' ?>>
                    <label><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></label>
                  </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Turnstile CAPTCHA -->
      <?php if (Turnstile::isEnabled()): ?>
        <div style="margin-top:20px;padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
          <div style="font-size:12px;font-weight:600;color:#64748B;margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em">
            🛡 Security Verification
          </div>
          <div class="cf-turnstile"
               data-sitekey="<?= htmlspecialchars(Turnstile::siteKey(), ENT_QUOTES, 'UTF-8') ?>"
               data-callback="onTurnstileSuccess"
               data-expired-callback="onTurnstileExpired"
               data-theme="light"></div>
        </div>
        <button type="submit" id="regSubmitBtn" class="btn btn-primary"
                style="width:100%;margin-top:14px;opacity:.5;cursor:not-allowed" disabled>
          Submit Application
        </button>
      <?php else: ?>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">
          Submit Application
        </button>
      <?php endif; ?>

    </form>
  </div>

  <!-- Footer -->
  <div class="auth-footer">
    Already have an account? <a href="/tracker/login.php">Sign in</a>
    <a href="/" class="back-home">&#8592; Back to Home</a>
  </div>

</div>

<script src="/assets/js/app.min.js"></script>
<?php if (Turnstile::isEnabled()): ?>
<script>
function onTurnstileSuccess(token) {
  var btn = document.getElementById('regSubmitBtn');
  if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
}
function onTurnstileExpired() {
  var btn = document.getElementById('regSubmitBtn');
  if (btn) { btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed'; }
}
</script>
<?php endif; ?>
</body>
</html>
