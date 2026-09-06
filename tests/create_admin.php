<?php
/**
 * create_admin.php
 * One-time admin account creation / password reset utility.
 * ─────────────────────────────────────────────────────────
 * HOW TO USE:
 *   1. Open this file in your browser: https://yourdomain.com/create_admin.php
 *   2. Fill in the form and submit.
 *   3. Log in at /tracker/login.php
 *   4. DELETE this file immediately after creating your account!
 * ─────────────────────────────────────────────────────────
 */

// ── Security check: CLI execution only ────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access denied: For security, this admin utility can only be run via CLI command line (e.g. php tests/create_admin.php).\n");
}

// ── Minimal bootstrap ─────────────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

$error   = '';
$success = '';

// ── Load DB config ─────────────────────────────────────────────────────────────
$configFile = CONFIG_PATH . '/config.json';
$dbConfig   = null;

if (file_exists($configFile)) {
    $cfg = json_decode(file_get_contents($configFile), true);
    $dbConfig = $cfg['database'] ?? null;
}

// ── Handle form submit ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host      = trim($_POST['db_host']     ?? 'localhost');
    $port      = (int)($_POST['db_port']    ?? 3306);
    $dbName    = trim($_POST['db_name']     ?? '');
    $dbUser    = trim($_POST['db_user']     ?? '');
    $dbPass    = $_POST['db_pass']          ?? '';
    $firstName = trim($_POST['first_name']  ?? '');
    $lastName  = trim($_POST['last_name']   ?? '');
    $email     = trim($_POST['email']       ?? '');
    $password  = $_POST['password']         ?? '';
    $confirm   = $_POST['confirm']          ?? '';

    if (!$dbName || !$dbUser)           $error = 'Database name and user are required.';
    elseif (!$firstName || !$lastName)  $error = 'First and last name are required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Valid email is required.';
    elseif (strlen($password) < 8)      $error = 'Password must be at least 8 characters.';
    elseif ($password !== $confirm)     $error = 'Passwords do not match.';

    if (!$error) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Ensure users table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `email`         VARCHAR(255) NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
                `role`          ENUM('admin','affiliate','advertiser','affiliate_manager') NOT NULL DEFAULT 'admin',
                `status`        ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
                `first_name`    VARCHAR(100) NOT NULL DEFAULT '',
                `last_name`     VARCHAR(100) NOT NULL DEFAULT '',
                `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Check if email already exists → update, else insert
            $existing = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $existing->execute([$email]);
            $row = $existing->fetch();

            if ($row) {
                $stmt = $pdo->prepare(
                    "UPDATE users SET password_hash=?, role='admin', status='active',
                     first_name=?, last_name=? WHERE email=?"
                );
                $stmt->execute([$hash, $firstName, $lastName, $email]);
                $success = '✅ Admin account <strong>' . htmlspecialchars($email) . '</strong> updated successfully.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (email, password_hash, role, status, first_name, last_name)
                     VALUES (?, ?, 'admin', 'active', ?, ?)"
                );
                $stmt->execute([$email, $hash, $firstName, $lastName]);
                $success = '✅ Admin account <strong>' . htmlspecialchars($email) . '</strong> created successfully.';
            }

            // Save DB config if not already configured
            if (!file_exists($configFile)) {
                if (!is_dir(CONFIG_PATH)) mkdir(CONFIG_PATH, 0755, true);
                $newCfg = [
                    'database' => [
                        'host'    => $host,
                        'port'    => $port,
                        'dbname'  => $dbName,
                        'username'=> $dbUser,
                        'password'=> $dbPass,
                    ],
                    'app' => [
                        'name'     => 'AffsCash',
                        'url'      => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
                        'timezone' => 'UTC',
                        'logo'     => '',
                    ],
                ];
                file_put_contents($configFile, json_encode($newCfg, JSON_PRETTY_PRINT));
                $success .= '<br>📝 config.json created. <strong>Run the installer at /install/ to complete full setup.</strong>';
            }

        } catch (\PDOException $e) {
            $error = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Prefill from config if available
$pre = $dbConfig ?? [];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Account Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:linear-gradient(135deg,#EEF2FF,#F0FDF4);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.1);width:100%;max-width:520px;overflow:hidden}
.header{background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:28px 32px;color:#fff}
.header h1{font-size:20px;font-weight:800;margin-bottom:4px}
.header p{font-size:13px;opacity:.85}
.body{padding:28px 32px}
.warning{background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:12px 16px;font-size:13px;color:#92400E;margin-bottom:20px;line-height:1.6}
.error{background:#FEE2E2;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;font-size:13px;color:#991B1B;margin-bottom:20px}
.success{background:#ECFDF5;border:1px solid #10B981;border-radius:10px;padding:14px 16px;font-size:14px;color:#065F46;margin-bottom:20px;line-height:1.7}
.section-title{font-size:12px;font-weight:700;color:#6366F1;text-transform:uppercase;letter-spacing:.8px;margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid #E2E8F0}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{margin-bottom:14px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:5px}
input{width:100%;padding:10px 12px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:14px;color:#1F2937;transition:.2s;outline:none}
input:focus{border-color:#4F46E5;box-shadow:0 0 0 3px rgba(79,70,229,.08)}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-top:4px;transition:all .2s}
.btn:hover{opacity:.9;}
.login-link{display:block;text-align:center;margin-top:16px;padding:12px;background:#F0FDF4;border:1px solid #A7F3D0;border-radius:10px;font-size:14px;color:#065F46;text-decoration:none;font-weight:600}
.delete-note{margin-top:14px;text-align:center;font-size:12px;color:#EF4444;font-weight:600}
</style>
</head>
<body>
<div class="box">
  <div class="header">
    <h1>⚙ Admin Account Setup</h1>
    <p>Create or reset your admin credentials to access the tracker</p>
  </div>
  <div class="body">

    <div class="warning">
      ⚠ <strong>Security Notice:</strong> Delete this file immediately after creating your admin account.<br>
      Anyone with access to this URL can create or overwrite admin credentials.
    </div>

    <?php if ($error): ?>
    <div class="error">❌ <?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="success">
      <?= $success ?><br><br>
      <strong>Next steps:</strong><br>
      1. <a href="/tracker/login.php" style="color:#047857;font-weight:700">Login at /tracker/login.php →</a><br>
      2. Complete setup at <a href="/install/" style="color:#047857;font-weight:700">/install/</a> if not done yet<br>
      3. <strong style="color:#DC2626">Delete this file from your server!</strong>
    </div>
    <a href="/tracker/login.php" class="login-link">→ Go to Login Page</a>
    <?php else: ?>

    <form method="POST">
      <div class="section-title">Database Connection</div>
      <div class="row">
        <div class="form-group">
          <label>DB Host</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($pre['host'] ?? 'localhost') ?>" required>
        </div>
        <div class="form-group">
          <label>DB Port</label>
          <input type="number" name="db_port" value="<?= (int)($pre['port'] ?? 3306) ?>">
        </div>
      </div>
      <div class="row">
        <div class="form-group">
          <label>Database Name <span style="color:#EF4444">*</span></label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($pre['dbname'] ?? '') ?>" required placeholder="e.g. affscash_db">
        </div>
        <div class="form-group">
          <label>DB Username <span style="color:#EF4444">*</span></label>
          <input type="text" name="db_user" value="<?= htmlspecialchars($pre['username'] ?? '') ?>" required placeholder="e.g. root">
        </div>
      </div>
      <div class="form-group">
        <label>DB Password</label>
        <input type="password" name="db_pass" placeholder="Leave blank if none">
      </div>

      <div class="section-title">Admin Account</div>
      <div class="row">
        <div class="form-group">
          <label>First Name <span style="color:#EF4444">*</span></label>
          <input type="text" name="first_name" required placeholder="John">
        </div>
        <div class="form-group">
          <label>Last Name <span style="color:#EF4444">*</span></label>
          <input type="text" name="last_name" required placeholder="Smith">
        </div>
      </div>
      <div class="form-group">
        <label>Email Address <span style="color:#EF4444">*</span></label>
        <input type="email" name="email" required placeholder="admin@yourdomain.com">
      </div>
      <div class="row">
        <div class="form-group">
          <label>Password <span style="color:#EF4444">*</span></label>
          <input type="password" name="password" minlength="8" required placeholder="Min 8 characters">
        </div>
        <div class="form-group">
          <label>Confirm Password <span style="color:#EF4444">*</span></label>
          <input type="password" name="confirm" required placeholder="Repeat password">
        </div>
      </div>

      <button type="submit" class="btn">⚙ Create / Reset Admin Account</button>
    </form>
    <?php endif; ?>

    <p class="delete-note">🗑 DELETE this file from your server after use!</p>
  </div>
</div>
</body>
</html>
