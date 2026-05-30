<?php
/**
 * Auto Database Installer Wizard
 */
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Block re-installation
if (file_exists(BASE_PATH . '/install/install.lock')) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fff8f0;border-left:4px solid #e67e22;margin:40px auto;max-width:600px;border-radius:8px;">
        <h2 style="color:#e67e22;">Already Installed</h2>
        <p>The application is already installed. Please <a href="/login">go to login</a>.</p>
        <p style="color:#999;font-size:13px;">To reinstall, delete the <code>install/install.lock</code> file.</p>
    </div>');
}

/**
 * Split a SQL file into individual statements correctly.
 * Handles semicolons inside single-quoted strings (e.g. HTML in INSERT values),
 * double-quoted strings, and -- line comments.
 */
function splitSqlStatements(string $sql): array {
    $statements  = [];
    $current     = '';
    $inSingle    = false;
    $inDouble    = false;
    $len         = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        // Single-quote toggle (handle escaped '' inside strings)
        if ($ch === "'" && !$inDouble) {
            if ($inSingle && isset($sql[$i + 1]) && $sql[$i + 1] === "'") {
                $current .= "''";
                $i++;       // skip the second quote of the escape pair
                continue;
            }
            $inSingle = !$inSingle;
            $current .= $ch;
            continue;
        }

        // Double-quote toggle
        if ($ch === '"' && !$inSingle) {
            $inDouble = !$inDouble;
            $current .= $ch;
            continue;
        }

        // -- line comment (only outside strings)
        if (!$inSingle && !$inDouble && $ch === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            $current .= "\n";
            continue;
        }

        // Statement terminator (only outside strings)
        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($current);
            // Skip empty or comment-only chunks
            if ($stmt !== '' && trim(preg_replace('/--[^\n]*/', '', $stmt)) !== '') {
                $statements[] = $stmt;
            }
            $current = '';
            continue;
        }

        $current .= $ch;
    }

    // Trailing statement without semicolon
    $stmt = trim($current);
    if ($stmt !== '' && trim(preg_replace('/--[^\n]*/', '', $stmt)) !== '') {
        $statements[] = $stmt;
    }

    return $statements;
}

session_start();
$step = (int)($_GET['step'] ?? 1);
$errors = [];
$success = [];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'check_requirements') {
        $_SESSION['install_step'] = 1;
        header('Location: ?step=2');
        exit;
    }

    if ($action === 'test_database') {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = (int)($_POST['db_port'] ?? 3306);
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';

        try {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");

            // Disable FK checks and strict mode at connection level before anything else
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $pdo->exec("SET SESSION sql_mode=''");

            // Run schema — proper SQL statement splitter that respects quoted strings
            $sql   = file_get_contents(__DIR__ . '/schema.sql');
            $stmts = splitSqlStatements($sql);

            // ── DATA-SAFETY GUARD ─────────────────────────────────────────────
            // Only drop and recreate tables on a confirmed EMPTY database.
            // If the `users` table already exists, the database has been installed
            // before. Dropping tables here would permanently destroy all user
            // accounts, offers, conversions, and payouts. Never do that.
            // The schema uses CREATE TABLE IF NOT EXISTS everywhere, so running
            // the statements on an existing DB is fully safe — new tables are
            // added and existing ones are left untouched.
            $dbAlreadyHasTables = false;
            try {
                $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
                $dbAlreadyHasTables = ($check !== false);
            } catch (\Exception $checkE) {}

            if (!$dbAlreadyHasTables) {
                // Fresh database — pre-drop in reverse order to avoid FK issues
                $tableNames = [];
                foreach ($stmts as $s) {
                    if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`/i', trim($s), $m)) {
                        $tableNames[] = $m[1];
                    }
                }
                foreach (array_reverse($tableNames) as $tbl) {
                    try { $pdo->exec("DROP TABLE IF EXISTS `{$tbl}`"); } catch (\Exception $de) {}
                }
            }

            // Execute schema — IF NOT EXISTS clauses make this safe to re-run
            // on an existing DB: existing tables are skipped, missing ones added.
            foreach ($stmts as $stmt) {
                try { $pdo->exec($stmt); } catch (\Exception $se) {
                    // Ignore — expected on existing tables (duplicate column, etc.)
                }
            }

            // Re-enable FK checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

            // Save DB config
            $config = [
                'app' => [
                    'name'     => 'AffiliateTracker',
                    'url'      => '',
                    'timezone' => 'UTC',
                    'version'  => '1.0.0',
                    'debug'    => false,
                ],
                'database' => [
                    'host'    => $host,
                    'port'    => $port,
                    'name'    => $name,
                    'user'    => $user,
                    'password'=> $pass,
                    'charset' => 'utf8mb4',
                ],
                'session' => [
                    'lifetime' => 7200,
                    'secure'   => false,
                    'same_site'=> 'Lax',
                ],
            ];
            if (!is_dir(CONFIG_PATH)) mkdir(CONFIG_PATH, 0755, true);
            file_put_contents(CONFIG_PATH . '/config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

            // Default fraud config
            $fraudConfig = [
                'enabled'          => false,
                'api_key'          => '',
                'api_endpoint'     => 'https://www.fraudlabspro.com/api/v1/order',
                'timeout_seconds'  => 2,
                'fail_open'        => true,
                'score_threshold'  => 75,
                'block_threshold'  => 90,
                'checks'           => ['vpn'=>true,'proxy'=>true,'tor'=>true,'bot'=>true,'datacenter'=>false],
            ];
            file_put_contents(CONFIG_PATH . '/fraud.json', json_encode($fraudConfig, JSON_PRETTY_PRINT), LOCK_EX);

            // Default postback config
            file_put_contents(CONFIG_PATH . '/postback.json', json_encode(['global_secret' => bin2hex(random_bytes(16))], JSON_PRETTY_PRINT), LOCK_EX);

            $_SESSION['install_db_ok'] = true;
            $_SESSION['install_pdo_params'] = compact('host','port','name','user','pass');
            header('Location: ?step=3');
            exit;
        } catch (\Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if ($action === 'create_admin') {
        if (!isset($_SESSION['install_db_ok'])) {
            header('Location: ?step=2');
            exit;
        }
        $params  = $_SESSION['install_pdo_params'];
        $fname   = trim($_POST['first_name'] ?? '');
        $lname   = trim($_POST['last_name'] ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $pass    = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $appName = trim($_POST['app_name'] ?? 'AffiliateTracker');
        $appUrl  = rtrim(trim($_POST['app_url'] ?? ''), '/');
        $tz      = $_POST['timezone'] ?? 'UTC';

        if (!$fname || !$lname || !$email || !$pass) {
            $errors[] = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } elseif (strlen($pass) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($pass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$params['host']};port={$params['port']};dbname={$params['name']};charset=utf8mb4",
                    $params['user'], $params['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // ── Ensure users table has all required columns ───────────────
                // This handles the case where the table was created by a previous
                // install attempt with an older/different schema, missing columns.
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                $pdo->exec("SET SESSION sql_mode=''");

                // Check existing columns on users table
                try {
                    $existingCols = [];
                    $colRows = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($colRows as $col) { $existingCols[] = $col['Field']; }

                    $required = [
                        'email'         => "VARCHAR(255) NOT NULL DEFAULT ''",
                        'password_hash' => "VARCHAR(255) NOT NULL DEFAULT ''",
                        'role'          => "ENUM('admin','affiliate','advertiser','affiliate_manager') NOT NULL DEFAULT 'admin'",
                        'status'        => "ENUM('pending','active','suspended','rejected') DEFAULT 'active'",
                        'first_name'    => "VARCHAR(100) DEFAULT ''",
                        'last_name'     => "VARCHAR(100) DEFAULT ''",
                        'company'       => "VARCHAR(255) DEFAULT ''",
                        'phone'         => "VARCHAR(50) DEFAULT ''",
                        'country'       => "CHAR(2) DEFAULT ''",
                        'profile_pic'   => "VARCHAR(255) DEFAULT ''",
                    ];
                    foreach ($required as $colName => $colDef) {
                        if (!in_array($colName, $existingCols)) {
                            $pdo->exec("ALTER TABLE `users` ADD COLUMN `{$colName}` {$colDef}");
                        }
                    }
                    // Ensure email has a UNIQUE index (may be missing if altered)
                    try {
                        $pdo->exec("ALTER TABLE `users` ADD UNIQUE INDEX `idx_email` (`email`)");
                    } catch (\Exception $idxE) { /* index may already exist */ }

                } catch (\Exception $schemaE) {
                    // users table doesn't exist at all — re-run schema
                    $sql = file_get_contents(__DIR__ . '/schema.sql');
                    foreach (splitSqlStatements($sql) as $stmt) {
                        try { $pdo->exec($stmt); } catch (\Exception $se) {}
                    }
                }
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

                // ── Duplicate user check ──────────────────────────────────────
                // Check by both email and username (first+last) to prevent duplicates.
                $dupCheck = $pdo->prepare("SELECT id FROM `users` WHERE `email` = ? LIMIT 1");
                $dupCheck->execute([$email]);
                if ($dupCheck->fetchColumn() !== false) {
                    $errors[] = 'User already exists. An admin account with the email "' . htmlspecialchars($email) . '" is already registered. Please log in instead, or use a different email address.';
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pdo->prepare("INSERT INTO `users` (`email`,`password_hash`,`role`,`status`,`first_name`,`last_name`) VALUES (?,?,'admin','active',?,?)")
                        ->execute([$email, $hash, $fname, $lname]);

                    // Update app config — read existing config to preserve all keys,
                    // then overwrite only the fields submitted in this step.
                    $configPath   = CONFIG_PATH . '/config.json';
                    $configData   = file_exists($configPath)
                        ? (json_decode(file_get_contents($configPath), true) ?? [])
                        : [];
                    if (!isset($configData['app']) || !is_array($configData['app'])) {
                        $configData['app'] = [];
                    }
                    $configData['app']['name']     = $appName;
                    $configData['app']['url']      = $appUrl;
                    $configData['app']['timezone'] = $tz;
                    $written = file_put_contents($configPath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
                    if ($written === false) {
                        $errors[] = 'Could not write config/config.json. Please check directory permissions and try again.';
                    } else {
                        // Write install lock
                        file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s'));

                        $_SESSION['install_complete'] = true;
                        header('Location: ?step=4');
                        exit;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = 'Error creating admin: ' . $e->getMessage();
            }
        }
    }
}

// Requirements check
$requirements = [
    'PHP >= 8.0'    => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL'     => extension_loaded('pdo_mysql'),
    'JSON Extension'=> extension_loaded('json'),
    'cURL Extension'=> extension_loaded('curl'),
    'OpenSSL'       => extension_loaded('openssl'),
    'config/ writable' => (is_dir(CONFIG_PATH) ? is_writable(CONFIG_PATH) : is_writable(dirname(CONFIG_PATH))),
];
$allOk = !in_array(false, $requirements, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AffiliateTracker - Installation Wizard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#EEF2FF 0%,#F0FDF4 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.wizard{background:#fff;border-radius:16px;box-shadow:0 4px 30px rgba(0,0,0,.08);width:100%;max-width:640px;overflow:hidden}
.wizard-header{background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:32px;color:#fff}
.wizard-header h1{font-size:24px;font-weight:700;margin-bottom:4px}
.wizard-header p{opacity:.85;font-size:14px}
.steps{display:flex;padding:20px 32px;background:#F8FAFC;border-bottom:1px solid #E2E8F0;gap:8px}
.step{flex:1;text-align:center;font-size:12px;color:#94A3B8;font-weight:500}
.step.active{color:#4F46E5;font-weight:700}
.step.done{color:#10B981}
.step-num{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:#E2E8F0;color:#64748B;font-size:12px;font-weight:700;margin-bottom:4px}
.step.active .step-num{background:#4F46E5;color:#fff}
.step.done .step-num{background:#10B981;color:#fff}
.wizard-body{padding:32px}
.form-group{margin-bottom:20px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
input[type=text],input[type=email],input[type=password],input[type=number],select{width:100%;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:14px;color:#1F2937;transition:.2s;background:#fff}
input:focus,select:focus{outline:none;border-color:#4F46E5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:.2s}
.btn-primary{background:#4F46E5;color:#fff;width:100%}
.btn-primary:hover{background:#4338CA}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px}
.alert-error{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-success{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.req-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;margin-bottom:8px;font-size:13px}
.req-item.ok{background:#F0FDF4;color:#15803D}
.req-item.fail{background:#FEF2F2;color:#DC2626}
.req-icon{font-size:16px}
h2{font-size:18px;font-weight:700;color:#1F2937;margin-bottom:6px}
.sub{font-size:13px;color:#64748B;margin-bottom:24px}
.success-box{text-align:center;padding:20px 0}
.success-icon{font-size:64px;margin-bottom:16px}
code{background:#F1F5F9;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:12px}
.warning-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:16px;margin-top:20px;font-size:13px;color:#92400E}
</style>
</head>
<body>
<div class="wizard">
    <div class="wizard-header">
        <h1>&#127760; AffiliateTracker</h1>
        <p>CPA Affiliate Marketing Platform - Installation Wizard</p>
    </div>
    <div class="steps">
        <?php
        $stepNames = ['Requirements','Database','Admin Account','Complete'];
        for ($i = 1; $i <= 4; $i++):
            $cls = $i < $step ? 'done' : ($i === $step ? 'active' : '');
        ?>
        <div class="step <?= $cls ?>">
            <div class="step-num"><?= $i < $step ? '&#10003;' : $i ?></div>
            <div><?= $stepNames[$i-1] ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="wizard-body">
        <?php foreach ($errors as $e): ?>
        <div class="alert alert-error">&#9888; <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <?php if ($step === 1): ?>
        <h2>System Requirements</h2>
        <p class="sub">Checking your server meets the minimum requirements.</p>
        <?php foreach ($requirements as $label => $ok): ?>
        <div class="req-item <?= $ok ? 'ok' : 'fail' ?>">
            <span class="req-icon"><?= $ok ? '&#10003;' : '&#10007;' ?></span>
            <span><?= htmlspecialchars($label) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if ($allOk): ?>
        <form method="POST" style="margin-top:24px">
            <input type="hidden" name="action" value="check_requirements">
            <button class="btn btn-primary" type="submit">Continue &#8594;</button>
        </form>
        <?php else: ?>
        <div class="alert alert-error" style="margin-top:20px">&#10007; Please fix the failing requirements above before continuing.</div>
        <?php endif; ?>

        <?php elseif ($step === 2): ?>
        <h2>Database Configuration</h2>
        <p class="sub">Enter your MySQL database credentials. The installer will create the database and tables automatically.</p>
        <form method="POST">
            <input type="hidden" name="action" value="test_database">
            <div class="form-row">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="127.0.0.1" required>
                </div>
                <div class="form-group">
                    <label>Port</label>
                    <input type="number" name="db_port" value="3306" required>
                </div>
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" placeholder="affiliate_tracker" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="db_user" placeholder="root" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="db_pass" placeholder="Leave blank if none">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Create Database &amp; Continue &#8594;</button>
        </form>

        <?php elseif ($step === 3): ?>
        <h2>Admin Account &amp; App Settings</h2>
        <p class="sub">Create your administrator account and configure basic application settings.</p>
        <form method="POST">
            <input type="hidden" name="action" value="create_admin">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" required>
                </div>
            </div>
            <hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0">
            <div class="form-group">
                <label>Application Name</label>
                <input type="text" name="app_name" value="AffiliateTracker" required>
            </div>
            <div class="form-group">
                <label>Application URL (no trailing slash)</label>
                <input type="text" name="app_url" placeholder="https://yourdomain.com" required>
            </div>
            <div class="form-group">
                <label>Timezone</label>
                <select name="timezone">
                    <?php foreach (\DateTimeZone::listIdentifiers() as $tz): ?>
                    <option value="<?= $tz ?>" <?= $tz === 'UTC' ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Complete Installation &#8594;</button>
        </form>

        <?php elseif ($step === 4): ?>
        <div class="success-box">
            <div class="success-icon">&#127881;</div>
            <h2 style="color:#16A34A;margin-bottom:8px">Installation Complete!</h2>
            <p style="color:#64748B;margin-bottom:24px">AffiliateTracker has been successfully installed and configured.</p>
            <a href="/login" class="btn btn-primary" style="text-decoration:none;display:inline-flex;width:auto">Go to Login &#8594;</a>
        </div>
        <div class="warning-box">
            <strong>&#9888; Security Warning:</strong> Please delete or restrict access to the <code>install/</code> directory from your web server for security. The <code>install.lock</code> file has been created to prevent reinstallation, but removing the directory is best practice.
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
