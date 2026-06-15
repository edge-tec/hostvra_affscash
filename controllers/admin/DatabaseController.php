<?php
Auth::check('admin');
$pageTitle = 'Database Tools';

$action = Helpers::post('action') ?: Helpers::get('action') ?: 'index';

// ── Export SQL dump ─────────────────────────────────────────────────────────
if ($action === 'export') {
    $cfg  = Config::get('config', 'database');
    $pdo  = Database::getInstance();
    $name = $cfg['name'] ?? 'affiliatetracker';

    $filename = 'db_backup_' . $name . '_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    echo "-- AffiliateTracker Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Database: " . $name . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n";
    echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    echo "SET NAMES utf8mb4;\n\n";

    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // DROP + CREATE
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = end($create); // second column
        echo "-- Table: `$table`\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $createSql . ";\n\n";

        // Rows
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) continue;

        $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
        echo "INSERT INTO `$table` ($cols) VALUES\n";
        $chunks = array_chunk($rows, 50);
        foreach ($chunks as $ci => $chunk) {
            $valGroups = [];
            foreach ($chunk as $row) {
                $vals = [];
                foreach ($row as $v) {
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = $pdo->quote((string)$v);
                    }
                }
                $valGroups[] = '(' . implode(', ', $vals) . ')';
            }
            // If this is not the last chunk of the last INSERT block, use comma
            echo implode(",\n", $valGroups);
            // Close with semicolon, then new INSERT for next chunk
            echo ";\n";
            if (($ci + 1) < count($chunks) && count($chunks) > 1) {
                echo "INSERT INTO `$table` ($cols) VALUES\n";
            }
        }
        echo "\n";
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

// ── Import SQL file ─────────────────────────────────────────────────────────
if ($action === 'import' && Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    if (empty($_FILES['sql_file']['tmp_name']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        Helpers::flash('error', 'No file uploaded or upload error.');
        Helpers::redirect('/admin/database');
    }

    $file = $_FILES['sql_file'];

    // Safety: only allow .sql files, max 50 MB
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        Helpers::flash('error', 'Only .sql files are allowed.');
        Helpers::redirect('/admin/database');
    }
    if ($file['size'] > 50 * 1024 * 1024) {
        Helpers::flash('error', 'File too large (max 50 MB).');
        Helpers::redirect('/admin/database');
    }

    $sql = file_get_contents($file['tmp_name']);
    if (!$sql) {
        Helpers::flash('error', 'Could not read uploaded file.');
        Helpers::redirect('/admin/database');
    }

    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        // Split into statements (simple split on ";\n")
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*\n/', $sql)),
            fn($s) => strlen($s) > 0 && strpos($s, '--') !== 0
        );

        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach ($statements as $stmt) {
            if (trim($stmt) === '') continue;
            $pdo->exec($stmt);
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        Helpers::flash('success', 'Database imported successfully. ' . count($statements) . ' statements executed.');
    } catch (\Throwable $e) {
        Helpers::flash('error', 'Import failed: ' . $e->getMessage());
    }

    Helpers::redirect('/admin/database');
}

// ── Table stats for display ─────────────────────────────────────────────────
$cfg = Config::get('config', 'database');
$dbName = $cfg['name'] ?? '';
$tableStats = Database::fetchAll(
    "SELECT table_name, table_rows, ROUND((data_length+index_length)/1024/1024,2) as size_mb, engine, table_collation, create_time
     FROM information_schema.tables WHERE table_schema=? ORDER BY table_name",
    [$dbName]
);

require BASE_PATH . '/views/admin/database/index.php';
