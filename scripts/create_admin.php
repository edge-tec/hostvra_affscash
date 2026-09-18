<?php
/**
 * CLI Admin User Creation & Password Reset Script
 * Usage: php scripts/create_admin.php
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

$configFile = CONFIG_PATH . '/config.json';
if (!file_exists($configFile)) {
    echo "[-] Error: config/config.json not found. Please complete the installer or create config/config.json first.\n";
    exit(1);
}

$cfg = json_decode(file_get_contents($configFile), true);
$db = $cfg['database'] ?? null;
if (!$db || empty($db['name'])) {
    echo "[-] Error: Database configuration missing in config/config.json.\n";
    exit(1);
}

$host = $db['host'] ?? '127.0.0.1';
$port = $db['port'] ?? 3306;
$name = $db['name'];
$user = $db['user'] ?? 'root';
$pass = $db['password'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $email = 'admin@affscash.net';
    $password = 'edge2129';
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Check if table users exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if (!$tableCheck) {
        echo "[-] Error: Table 'users' does not exist in database '{$name}'. Please run installer or schema first.\n";
        exit(1);
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, role, status, first_name, last_name, timezone)
        VALUES (?, ?, 'admin', 'active', 'Admin', 'Affscash', 'UTC')
        ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            role = 'admin',
            status = 'active',
            first_name = 'Admin',
            last_name = 'Affscash'
    ");
    $stmt->execute([$email, $hash]);

    echo "\n======================================================\n";
    echo "  [SUCCESS] Admin Account Created / Reset Successfully!\n";
    echo "======================================================\n";
    echo "  Email    : {$email}\n";
    echo "  Password : {$password}\n";
    echo "  Role     : admin\n";
    echo "  Status   : active\n";
    echo "======================================================\n\n";

} catch (PDOException $e) {
    echo "[-] Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
