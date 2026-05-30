<?php
/**
 * Super Admin Credentials Reset & DB Auto-Migrator Utility
 */
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');

if (!file_exists(CONFIG_PATH . '/config.json')) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fff8f0;border-left:4px solid #e67e22;margin:40px auto;max-width:600px;border-radius:8px;">
        <h2 style="color:#e67e22;">App Not Installed</h2>
        <p>The application configuration file config/config.json does not exist. Please run the installer first at <a href="/install/">/install/</a>.</p>
    </div>');
}

require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/Migrator.php';

Config::init(CONFIG_PATH);

try {
    // 1. Initialize DB Connection
    $db = Database::getInstance();

    // 2. Force invalidate the migration lock file and trigger database migration
    Migrator::init();
    Migrator::invalidateLock();
    $migrationResults = Migrator::runAll();
    Migrator::syncConfig();

    // 3. Clear existing super admin entries (to avoid duplicate key violations)
    $email = 'superadmin@eliteali.com';
    Database::query("DELETE FROM `users` WHERE `email` = ?", [$email]);
    Database::query("DELETE FROM `super_admins` WHERE `email` = ?", [$email]);

    // 4. Generate guaranteed valid PHP BCRYPT password hash for "superadmin123"
    $rawPassword = 'superadmin123';
    $passwordHash = password_hash($rawPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // 5. Insert new Super Admin entries
    // Insert into super_admins
    Database::query(
        "INSERT INTO `super_admins` (`id`, `email`, `password_hash`, `name`) VALUES (1, ?, ?, 'Super Admin')",
        [$email, $passwordHash]
    );

    // Insert into users
    Database::query(
        "INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `status`, `first_name`, `last_name`, `company`, `created_at`) 
         VALUES (99999, ?, ?, 'super_admin', 'active', 'Super', 'Admin', 'EliteAli SaaS', NOW())",
         [$email, $passwordHash]
    );

    echo '<div style="font-family:sans-serif;padding:40px;background:#f0fdf4;border-left:4px solid #10b981;margin:40px auto;max-width:600px;border-radius:8px;box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <h2 style="color:#10b981;margin-top:0;">✅ Super Admin Reset Successful!</h2>
        <p>The database migrations have been successfully checked/applied and the Super Admin account has been securely recreated with verified credentials.</p>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">
        <p><strong>Login Link:</strong> <a href="/login" style="color:#4f46e5;text-decoration:none;font-weight:bold;">Go to Login Panel &rarr;</a></p>
        <p><strong>Username/Email:</strong> <code>superadmin@eliteali.com</code></p>
        <p><strong>Password:</strong> <code>superadmin123</code></p>
        <div style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:12px;border-radius:6px;font-size:13px;margin-top:20px;">
            ⚠️ <strong>Security Notice:</strong> Please delete this file (<code>reset_superadmin.php</code>) from your server root immediately after logging in!
        </div>
    </div>';

} catch (\Exception $e) {
    die('<div style="font-family:sans-serif;padding:40px;background:#fef2f2;border-left:4px solid #ef4444;margin:40px auto;max-width:600px;border-radius:8px;">
        <h2 style="color:#ef4444;margin-top:0;">❌ Reset Failed</h2>
        <p>An error occurred while resetting the Super Admin account:</p>
        <pre style="background:#f8fafc;padding:15px;border-radius:6px;border:1px solid #e2e8f0;font-size:13px;overflow-x:auto;">' . htmlspecialchars($e->getMessage()) . '</pre>
        <p>Please make sure your database connection details are correct in your <code>config/config.json</code> file.</p>
    </div>');
}
