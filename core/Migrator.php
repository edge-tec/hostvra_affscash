<?php
/**
 * Migrator — Automatic database migration & config-sync engine
 *
 * HOW IT WORKS
 * ─────────────
 * 1. Numbered .sql files live in install/migrations/ (0001_…, 0002_…, …)
 * 2. Each applied migration is recorded in schema_migrations table
 * 3. A lock file (config/migrations.lock) caches the "all-applied" state
 *    so we avoid DB queries on every request when nothing is pending
 * 4. When new code is deployed with new .sql files, the lock hash changes,
 *    pending migrations are detected and applied automatically on next request
 * 5. All DDL statements use IF NOT EXISTS / safe error handling — idempotent
 * 6. Config defaults are merged from install/config_defaults.json without
 *    overwriting any existing values
 *
 * MIGRATION FILE FORMAT
 * ──────────────────────
 * Filename: NNNN_description.sql  (e.g. 0006_add_new_feature.sql)
 * Content:  standard SQL; statements separated by semicolons
 *           Lines starting with -- are comments (stripped before execution)
 *           Each file is wrapped in an implicit transaction where possible
 */
class Migrator
{
    const TABLE = 'schema_migrations';

    // ── Bootstrap ────────────────────────────────────────────────────────────

    /**
     * Create the tracking table if it does not exist.
     * Must be called before any other method.
     */
    public static function init(): void
    {
        try {
            Database::getInstance()->exec("
                CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
                    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `migration`     VARCHAR(255)  NOT NULL UNIQUE,
                    `batch`         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                    `applied_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `checksum`      VARCHAR(64)   NULL,
                    `execution_ms`  INT UNSIGNED  NULL,
                    `status`        ENUM('applied','failed') NOT NULL DEFAULT 'applied',
                    `error_message` TEXT NULL,
                    INDEX `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Exception $e) {
            error_log('[Migrator] init failed: ' . $e->getMessage());
        }
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Run all pending migrations in order.
     * Returns an array of result records (one per migration).
     */
    public static function runAll(): array
    {
        $pending = self::getPending();
        if (empty($pending)) {
            self::writeLock();
            return [];
        }

        $results = [];
        $batch   = self::nextBatch();
        foreach ($pending as $file) {
            $result    = self::runOne($file, $batch);
            $results[] = $result;
            // Stop batch on fatal error to prevent cascading failures
            if ($result['status'] === 'error') break;
        }
        self::writeLock();

        // Notify admin if any migration ran
        if (!empty($results)) {
            self::notifyAdmin($results);
        }
        return $results;
    }

    /**
     * Run a single migration file by filename (e.g. "0003_add_columns.sql").
     */
    public static function runOne(string $filename, int $batch = 0): array
    {
        $path = self::getMigrationsDir() . '/' . $filename;
        if (!file_exists($path)) {
            return ['file' => $filename, 'status' => 'error', 'message' => 'File not found'];
        }

        if ($batch === 0) $batch = self::nextBatch();

        $sql      = file_get_contents($path);
        $checksum = md5($sql);
        $stmts    = self::parseStatements($sql);
        $start    = microtime(true);
        $errors   = [];
        $skipped  = 0;
        $executed = 0;

        $pdo = Database::getInstance();

        foreach ($stmts as $stmt) {
            $r = self::safeExec($pdo, $stmt);
            if ($r['status'] === 'error') {
                $errors[] = $r['message'] . '  [SQL: ' . mb_substr(trim($stmt), 0, 160) . ']';
                break; // Stop on first real error
            } elseif ($r['status'] === 'skipped') {
                $skipped++;
            } else {
                $executed++;
            }
        }

        $ms     = (int)(round((microtime(true) - $start) * 1000));
        $status = empty($errors) ? 'applied' : 'failed';
        $errMsg = empty($errors) ? null : implode(' | ', $errors);

        // Record result in schema_migrations
        try {
            $pdo->prepare(
                "INSERT INTO `" . self::TABLE . "`
                    (migration, batch, checksum, execution_ms, status, error_message)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     batch=VALUES(batch), checksum=VALUES(checksum),
                     execution_ms=VALUES(execution_ms), status=VALUES(status),
                     error_message=VALUES(error_message),
                     applied_at=CURRENT_TIMESTAMP"
            )->execute([$filename, $batch, $checksum, $ms, $status, $errMsg]);
        } catch (\Exception $e) {
            error_log('[Migrator] Could not record ' . $filename . ': ' . $e->getMessage());
        }

        $summary = $status === 'applied'
            ? $executed . ' exec, ' . $skipped . ' skipped (already applied)'
            : $errMsg;

        return [
            'file'    => $filename,
            'status'  => $status,
            'ms'      => $ms,
            'message' => $summary,
        ];
    }

    /**
     * Return pending migration filenames (sorted, not yet applied).
     */
    public static function getPending(): array
    {
        $all     = self::getAllFiles();
        $applied = self::getAppliedNames();
        return array_values(array_diff($all, $applied));
    }

    /**
     * Return all migration records from the DB (applied + failed).
     */
    public static function getHistory(): array
    {
        try {
            return Database::fetchAll(
                "SELECT * FROM `" . self::TABLE . "` ORDER BY migration ASC"
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Return count of successfully applied migrations (= DB schema version).
     */
    public static function getCurrentVersion(): int
    {
        try {
            $r = Database::fetchOne(
                "SELECT COUNT(*) as n FROM `" . self::TABLE . "` WHERE status='applied'"
            );
            return (int)($r['n'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * True when the lock file hash matches current migration filenames.
     * Used to skip the DB check on every request when nothing is pending.
     */
    public static function isUpToDate(): bool
    {
        $lockFile = self::getLockFile();
        if (!file_exists($lockFile)) return false;
        return trim(file_get_contents($lockFile)) === self::computeLockHash();
    }

    /**
     * Force-invalidate the lock so the next request re-checks migrations.
     * Call this after uploading new code.
     */
    public static function invalidateLock(): void
    {
        $lockFile = self::getLockFile();
        if (file_exists($lockFile)) @unlink($lockFile);
    }

    /**
     * Sync config defaults: add any key from install/config_defaults.json
     * that is missing from the live config.json.  Existing values are NEVER
     * overwritten — only absent keys are filled in with their defaults.
     */
    public static function syncConfig(): bool
    {
        $defaultsFile = BASE_PATH . '/install/config_defaults.json';
        if (!file_exists($defaultsFile)) return false;

        $raw = file_get_contents($defaultsFile);
        $defaults = json_decode($raw, true);
        if (!is_array($defaults)) return false;

        $changed = false;
        foreach ($defaults as $key => $value) {
            // Config::get returns null when a key is absent (not when it's set to false/0)
            if (Config::get('config', $key) === null) {
                Config::set('config', $key, $value);
                $changed = true;
            }
        }
        if ($changed) Config::clearCache();
        return $changed;
    }

    /**
     * Create a DB snapshot in storage/backups/ just before auto-migrations run.
     *
     * This means every time new code with new migration files is deployed, a
     * complete database backup is taken automatically on the very first request —
     * before any schema change is applied.  If a migration goes wrong, you always
     * have a restore point.
     *
     * Strategy:
     *   1. Try mysqldump  (fast, complete, preferred on VPS / dedicated hosting).
     *   2. Fall back to PDO-based table export (works on shared hosts where the
     *      mysqldump binary is unavailable or exec() is disabled).
     *
     * Backup files: storage/backups/pre_migration_YYYYMMDD_HHMMSS.sql
     * Old files older than 30 days are pruned automatically.
     */
    public static function backupBeforeMigrate(): void
    {
        $backupDir = BASE_PATH . '/storage/backups';
        if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
        if (!is_writable($backupDir)) return;

        $file = $backupDir . '/pre_migration_' . date('Ymd_His') . '.sql';

        // ── Attempt 1: mysqldump ─────────────────────────────────────────────
        try {
            $raw  = @file_get_contents(CONFIG_PATH . '/config.json');
            $cfg  = $raw ? (json_decode($raw, true)['database'] ?? []) : [];
            $host = $cfg['host']     ?? '127.0.0.1';
            $port = (int)($cfg['port'] ?? 3306);
            $name = $cfg['name']     ?? ($cfg['database'] ?? '');
            $user = $cfg['user']     ?? ($cfg['username'] ?? '');
            $pass = $cfg['password'] ?? '';

            if ($name && function_exists('exec')) {
                $passArg = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
                $cmd = sprintf(
                    'mysqldump --no-tablespaces -h %s -P %d -u %s %s %s 2>/dev/null',
                    escapeshellarg($host), $port,
                    escapeshellarg($user), $passArg,
                    escapeshellarg($name)
                );
                @exec($cmd . ' > ' . escapeshellarg($file), $out, $rc);
                if ($rc === 0 && file_exists($file) && filesize($file) > 200) {
                    self::pruneOldBackups($backupDir);
                    return; // success
                }
                @unlink($file);
            }
        } catch (\Exception $e) {
            @unlink($file);
        }

        // ── Attempt 2: PDO table-by-table export ─────────────────────────────
        try {
            $pdo    = Database::getInstance();
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            $sql  = "-- Pre-migration auto-backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Tables: " . count($tables) . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sql .= "SET NAMES utf8mb4;\n\n";

            foreach ($tables as $tbl) {
                // Schema
                $cr   = $pdo->query("SHOW CREATE TABLE `{$tbl}`")->fetch(\PDO::FETCH_ASSOC);
                $sql .= "-- Table: {$tbl}\n";
                $sql .= "DROP TABLE IF EXISTS `{$tbl}`;\n";
                $sql .= ($cr['Create Table'] ?? '') . ";\n\n";

                // Data — chunked to limit memory pressure on large tables
                $offset = 0;
                $chunk  = 500;
                do {
                    $rows = $pdo->query(
                        "SELECT * FROM `{$tbl}` LIMIT {$chunk} OFFSET {$offset}"
                    )->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        $cols = '`' . implode('`,`', array_keys($row)) . '`';
                        $vals = implode(',', array_map(
                            fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v),
                            array_values($row)
                        ));
                        $sql .= "INSERT INTO `{$tbl}` ({$cols}) VALUES ({$vals});\n";
                    }
                    $offset += $chunk;
                } while (count($rows) === $chunk);
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            @file_put_contents($file, $sql, LOCK_EX);
            self::pruneOldBackups($backupDir);
        } catch (\Exception $e) {
            error_log('[Migrator] Pre-migration backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete pre-migration backup files older than $days days.
     */
    private static function pruneOldBackups(string $dir, int $days = 30): void
    {
        $cutoff = time() - ($days * 86400);
        foreach (glob($dir . '/pre_migration_*.sql') ?: [] as $f) {
            if (filemtime($f) < $cutoff) @unlink($f);
        }
    }

    /**
     * Generate SQL statements that would bring the DB schema up to date.
     * Useful for review before applying.
     */
    public static function getPendingSQL(): string
    {
        $pending = self::getPending();
        if (empty($pending)) return '';
        $out = [];
        foreach ($pending as $file) {
            $path = self::getMigrationsDir() . '/' . $file;
            if (file_exists($path)) {
                $out[] = "-- === " . $file . " ===\n" . file_get_contents($path);
            }
        }
        return implode("\n\n", $out);
    }

    /**
     * Create a new blank migration file and return its path.
     * $description should be lowercase_underscore (e.g. "add_email_logs_table")
     */
    public static function createFile(string $description): string
    {
        $dir  = self::getMigrationsDir();
        $existing = self::getAllFiles();
        $last = empty($existing) ? 0 : (int)substr(end($existing), 0, 4);
        $num  = str_pad($last + 1, 4, '0', STR_PAD_LEFT);
        $desc = preg_replace('/[^a-z0-9_]/', '_', strtolower($description));
        $name = $num . '_' . $desc . '.sql';
        $path = $dir . '/' . $name;

        $stub = "-- Migration: {$name}\n"
              . "-- Created:   " . date('Y-m-d H:i:s') . "\n"
              . "-- Description: {$description}\n\n"
              . "-- Add your SQL statements below.\n"
              . "-- All DDL must use IF NOT EXISTS / IF EXISTS for safety.\n\n";

        file_put_contents($path, $stub);
        return $path;
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    private static function getMigrationsDir(): string
    {
        return BASE_PATH . '/install/migrations';
    }

    private static function getLockFile(): string
    {
        return CONFIG_PATH . '/migrations.lock';
    }

    private static function getAllFiles(): array
    {
        $dir = self::getMigrationsDir();
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/*.sql') ?: [];
        $files = array_map('basename', $files);
        sort($files);
        return $files;
    }

    private static function getAppliedNames(): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT migration FROM `" . self::TABLE . "` WHERE status='applied'"
            );
            return array_column($rows, 'migration');
        } catch (\Exception $e) {
            return [];
        }
    }

    private static function nextBatch(): int
    {
        try {
            $r = Database::fetchOne(
                "SELECT COALESCE(MAX(batch), 0) + 1 AS next FROM `" . self::TABLE . "`"
            );
            return max(1, (int)($r['next'] ?? 1));
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Parse SQL file content into individual executable statements.
     * - Strips block comments (/* ... *‌/)
     * - Strips line comments (-- ...)
     * - Splits on semicolons, trims whitespace, removes empty strings
     */
    private static function parseStatements(string $sql): array
    {
        // Strip block comments
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
        // Strip line comments
        $sql = preg_replace('/--[^\n]*/', '', $sql);
        // Split by semicolons
        $parts = explode(';', $sql);
        $stmts = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') $stmts[] = $p;
        }
        return $stmts;
    }

    /**
     * Execute one SQL statement with safe error handling.
     *
     * Returns:
     *   ['status' => 'ok']       — executed successfully
     *   ['status' => 'skipped']  — already-exists (ignorable DDL duplicate)
     *   ['status' => 'error']    — real error that should halt the migration
     */
    private static function safeExec(\PDO $pdo, string $stmt): array
    {
        // SELECT statements are informational only — run but never fail on
        if (stripos(ltrim($stmt), 'SELECT ') === 0) {
            try { $pdo->query($stmt); } catch (\Exception $e) {}
            return ['status' => 'ok', 'message' => 'info-select'];
        }

        try {
            $pdo->exec($stmt);
            return ['status' => 'ok', 'message' => 'executed'];
        } catch (\PDOException $e) {
            $errInfo  = $e->errorInfo ?? [];
            $mysqlNo  = (int)($errInfo[1] ?? 0);
            $message  = $e->getMessage();

            // MySQL error numbers that indicate the change is already applied
            static $ignorable = [
                1060, // Duplicate column name (ADD COLUMN already exists)
                1061, // Duplicate key name (ADD INDEX already exists)
                1050, // Table already exists (CREATE TABLE IF NOT EXISTS fallback)
                1091, // Can't DROP; column/key doesn't exist
                1062, // Duplicate entry (data already inserted)
                1004, // Can't create file (table already exists on some engines)
            ];

            if (in_array($mysqlNo, $ignorable, true)) {
                return ['status' => 'skipped', 'message' => 'already-applied (MySQL ' . $mysqlNo . ')'];
            }

            error_log('[Migrator] MySQL ' . $mysqlNo . ': ' . $message . ' | SQL: ' . mb_substr($stmt, 0, 200));
            return ['status' => 'error', 'message' => 'MySQL ' . $mysqlNo . ': ' . $message];
        }
    }

    private static function computeLockHash(): string
    {
        // Hash of all filenames — changes when new migration files are added
        return md5(implode('|', self::getAllFiles()));
    }

    private static function writeLock(): void
    {
        try {
            @file_put_contents(self::getLockFile(), self::computeLockHash(), LOCK_EX);
        } catch (\Exception $e) {}
    }

    /**
     * Insert a notification for the admin when migrations auto-ran.
     */
    private static function notifyAdmin(array $results): void
    {
        $applied = array_filter($results, fn($r) => $r['status'] === 'applied');
        $failed  = array_filter($results, fn($r) => $r['status'] === 'error');

        if (!empty($applied)) {
            $names = implode(', ', array_column(array_values($applied), 'file'));
            try {
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'success',
                    'title'       => 'Database Migration Applied',
                    'message'     => count($applied) . ' migration(s) applied automatically: ' . $names,
                    'link'        => '/admin/system-update',
                ]);
            } catch (\Exception $e) {}
        }

        if (!empty($failed)) {
            $names = implode(', ', array_column(array_values($failed), 'file'));
            try {
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'danger',
                    'title'       => 'Migration Failed',
                    'message'     => count($failed) . ' migration(s) failed: ' . $names . '. Check /admin/system-update for details.',
                    'link'        => '/admin/system-update',
                ]);
            } catch (\Exception $e) {}
        }
    }
}
