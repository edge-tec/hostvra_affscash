<?php
/**
 * CLI Admin User Creation & Password Reset Script
 * Usage:
 *   php scripts/create_admin.php
 *   OR with arguments:
 *   php scripts/create_admin.php [db_name] [db_user] [db_password] [db_host]
 */

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

$host = '127.0.0.1';
$port = 3306;
$name = '';
$user = 'root';
$pass = '';

// Check CLI arguments first
if (!empty($argv[1])) {
    $name = $argv[1];
    $user = $argv[2] ?? 'root';
    $pass = $argv[3] ?? '';
    $host = $argv[4] ?? '127.0.0.1';
} else {
    // Check config/config.json
    $configFile = CONFIG_PATH . '/config.json';
    if (file_exists($configFile)) {
        $cfg = json_decode(file_get_contents($configFile), true);
        $db = $cfg['database'] ?? [];
        $host = $db['host'] ?? '127.0.0.1';
        $port = (int)($db['port'] ?? 3306);
        $name = $db['name'] ?? '';
        $user = $db['user'] ?? 'root';
        $pass = $db['password'] ?? '';
    }

    // Check .env if DB name still empty
    if (empty($name) && file_exists(BASE_PATH . '/.env')) {
        $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($k, $v) = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if ($k === 'DB_HOST') $host = $v;
                if ($k === 'DB_PORT') $port = (int)$v;
                if ($k === 'DB_DATABASE') $name = $v;
                if ($k === 'DB_USERNAME') $user = $v;
                if ($k === 'DB_PASSWORD') $pass = $v;
            }
        }
    }
}

if (empty($name)) {
    echo "[-] Error: Database name not found.\n";
    echo "    Usage: php scripts/create_admin.php <database_name> <database_user> <database_password>\n";
    exit(1);
}

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
        echo "[-] Error: Table 'users' does not exist in database '{$name}'. Please run the installer schema first.\n";
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
