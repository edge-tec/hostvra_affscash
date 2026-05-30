<?php
// ════════════════════════════════════════════════════════════════
// db.php — Affscash Reviews Database Config
//
// SETUP INSTRUCTIONS:
//   1. cPanel → MySQL Databases
//   2. Fill in DB_NAME, DB_USER, DB_PASS below
//   3. Upload ALL files inside the  reviews/  folder on your server
//   4. Visit install.php in browser to create tables
//   5. DELETE install.php after setup is complete
// ════════════════════════════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'metmtahv_review');     // ← cPanel database name
define('DB_USER', 'metmtahv_review');     // ← cPanel database username
define('DB_PASS', 'metmtahv_review');     // ← cPanel database password

define('SITE_URL',   'https://affscash.net');
define('ADMIN_PWD',  'Miz@n2129');

// ── Upload paths: must point to the  uploads/  folder next to db.php ──
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/reviews/uploads/');

// ════════════════════════════════════════════════════════════════
// DO NOT EDIT BELOW THIS LINE
// ════════════════════════════════════════════════════════════════

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

function resp(bool $ok, array $data = [], string $error = ''): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => $ok], $data, $error ? ['error' => $error] : []));
    exit;
}

function requireAdmin(): void {
    $auth = $_SERVER['HTTP_X_ADMIN_AUTH']
         ?? $_GET['admin_pwd']
         ?? $_POST['admin_pwd']
         ?? '';
    if ($auth !== ADMIN_PWD) {
        http_response_code(401);
        resp(false, [], 'Unauthorized');
    }
}

function genId(string $prefix = 'r'): string {
    return $prefix . '_' . bin2hex(random_bytes(8));
}
