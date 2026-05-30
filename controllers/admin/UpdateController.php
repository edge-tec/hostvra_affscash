<?php
Auth::check('admin');
$pageTitle = 'Script Upgrade';

// Paths never replaced during upgrade (as a regular variable, no const redefinition)
$UPGRADE_SKIP = ['config/', 'storage/', 'uploads/', 'logs/', '.git/', 'node_modules/'];

// ─────────────────────────────────────────────────────────────────────────────
// AJAX: chunked upload support — check PHP limits and increase if possible
// ─────────────────────────────────────────────────────────────────────────────
@ini_set('upload_max_filesize', '256M');
@ini_set('post_max_size',       '256M');
@ini_set('max_execution_time',  '300');
@ini_set('memory_limit',        '256M');

// ─────────────────────────────────────────────────────────────────────────────
// POST HANDLERS
// ─────────────────────────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::postRaw('action') ?? '';

    // ── UPLOAD & ANALYZE ─────────────────────────────────────────────────────
    if ($action === 'upload_analyze') {
        $err = $_FILES['script_zip']['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($err !== UPLOAD_ERR_OK) {
            $errMsgs = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (upload_max_filesize). Ask your host to increase it.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded. Try again.',
                UPLOAD_ERR_NO_FILE    => 'No file selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server has no temp folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not write the file.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
            ];
            Helpers::flash('danger', $errMsgs[$err] ?? 'Upload error code ' . $err);
            Helpers::redirect('/admin/system-update');
        }

        $tmpPath  = $_FILES['script_zip']['tmp_name'];
        $origName = $_FILES['script_zip']['name'];
        $fileSize = $_FILES['script_zip']['size'];

        if (strtolower(pathinfo($origName, PATHINFO_EXTENSION)) !== 'zip') {
            Helpers::flash('danger', 'Only .zip files are accepted. Got: ' . htmlspecialchars($origName));
            Helpers::redirect('/admin/system-update');
        }
        if (!file_exists($tmpPath) || $fileSize < 10) {
            Helpers::flash('danger', 'Uploaded file is empty or missing. Check server upload limits.');
            Helpers::redirect('/admin/system-update');
        }
        if (!class_exists('ZipArchive')) {
            Helpers::flash('danger', 'PHP ZipArchive extension is not installed on this server. Contact your host.');
            Helpers::redirect('/admin/system-update');
        }

        // Clean old staging
        $oldDir = $_SESSION['upg_stage_dir'] ?? '';
        if ($oldDir && is_dir($oldDir)) upg_rmdir($oldDir);

        // Create staging directory
        $stageId  = date('YmdHis') . '_' . substr(md5($origName . $fileSize), 0, 6);
        $stageDir = BASE_PATH . '/storage/update_staging/' . $stageId;
        if (!@mkdir($stageDir, 0755, true) && !is_dir($stageDir)) {
            Helpers::flash('danger', 'Could not create staging directory. Check storage/ folder permissions.');
            Helpers::redirect('/admin/system-update');
        }

        $zip = new ZipArchive();
        $res = $zip->open($tmpPath);
        if ($res !== true) {
            $zipErrors = [
                ZipArchive::ER_NOZIP    => 'Not a valid ZIP file.',
                ZipArchive::ER_INCONS   => 'ZIP file is inconsistent/corrupt.',
                ZipArchive::ER_MEMORY   => 'Server ran out of memory.',
                ZipArchive::ER_NOENT    => 'File not found.',
                ZipArchive::ER_OPEN     => 'Cannot open file.',
                ZipArchive::ER_READ     => 'Read error.',
                ZipArchive::ER_SEEK     => 'Seek error.',
            ];
            Helpers::flash('danger', 'ZIP error: ' . ($zipErrors[$res] ?? 'Code '.$res));
            upg_rmdir($stageDir);
            Helpers::redirect('/admin/system-update');
        }
        $zip->extractTo($stageDir);
        $zip->close();

        // Detect if ZIP has a root subfolder (common: myapp-2.0/ wrapping all files)
        $stageRoot = upg_detect_root($stageDir);

        // Read new version.json
        $newVersionData = [];
        if (file_exists($stageRoot . '/version.json')) {
            $newVersionData = json_decode(file_get_contents($stageRoot . '/version.json'), true) ?: [];
        }

        // Read changelog
        $changelog = '';
        foreach (['CHANGELOG.md','changelog.md','RELEASE_NOTES.md','WHAT_IS_NEW.txt','release_notes.txt'] as $cf) {
            if (file_exists($stageRoot . '/' . $cf)) {
                $changelog = file_get_contents($stageRoot . '/' . $cf);
                break;
            }
        }
        if (!$changelog && !empty($newVersionData['release_notes'])) {
            $changelog = $newVersionData['release_notes'];
        }

        // Find new migration .sql files
        $newMigrations = upg_find_new_migrations($stageRoot);

        // Auto-copy new migration files immediately so they appear in pending list
        $copiedCount = 0;
        foreach ($newMigrations as &$m) {
            $dest = BASE_PATH . '/install/migrations/' . $m['name'];
            if (!file_exists($dest)) {
                if (copy($m['src'], $dest)) {
                    $copiedCount++;
                    $m['copied'] = true;
                }
            }
        }
        unset($m);
        if ($copiedCount > 0) Migrator::invalidateLock();

        // Find changed code files
        $changedFiles = upg_analyze_files($stageRoot, $UPGRADE_SKIP);

        // Save to session
        $_SESSION['upg_stage_dir']     = $stageDir;
        $_SESSION['upg_stage_root']    = $stageRoot;
        $_SESSION['upg_stage_id']      = $stageId;
        $_SESSION['upg_new_version']   = $newVersionData;
        $_SESSION['upg_changelog']     = $changelog;
        $_SESSION['upg_migrations']    = $newMigrations;
        $_SESSION['upg_files']         = $changedFiles;
        $_SESSION['upg_copied_mig']    = $copiedCount;
        $_SESSION['upg_orig_name']     = $origName;
        $_SESSION['upg_file_size']     = $fileSize;
        unset($_SESSION['upg_result']);  // clear any previous result

        Helpers::redirect('/admin/system-update');
    }

    // ── RUN FULL MIGRATION (one-click upgrade) ────────────────────────────────
    if ($action === 'run_full_upgrade') {
        $stageRoot    = $_SESSION['upg_stage_root'] ?? '';
        $changedFiles = $_SESSION['upg_files']      ?? [];

        if (empty($stageRoot) || !is_dir($stageRoot)) {
            Helpers::flash('danger', 'Staging package not found. Please upload the ZIP again.');
            Helpers::redirect('/admin/system-update');
        }

        $log      = [];
        $errors   = [];
        $backupId = date('YmdHis');
        $backupDir= BASE_PATH . '/storage/backups/code/' . $backupId;
        @mkdir($backupDir, 0755, true);

        // STEP 1 — Database backup
        $dbResult = upg_backup_db($backupId);
        $log[] = $dbResult['ok']
            ? ['ok'=>true,  'step'=>'Database Backup',    'msg'=>'Saved to storage/backups/code/'.$backupId.'/db_backup.sql ('.round(filesize($backupDir.'/db_backup.sql')/1024).' KB)']
            : ['ok'=>false, 'step'=>'Database Backup',    'msg'=>'Skipped — '.$dbResult['error'].' (upgrade continued)'];

        // STEP 2 — Apply database migrations
        Migrator::init();
        $migResults = Migrator::runAll();
        $migOk   = count(array_filter($migResults, fn($r) => $r['status'] === 'applied'));
        $migSkip = count(array_filter($migResults, fn($r) => $r['status'] === 'skipped'));
        $migFail = count(array_filter($migResults, fn($r) => $r['status'] === 'error'));
        if (empty($migResults)) {
            $log[] = ['ok'=>true, 'step'=>'Database Migrations', 'msg'=>'Already up to date — no changes needed'];
        } elseif ($migFail === 0) {
            $log[] = ['ok'=>true, 'step'=>'Database Migrations', 'msg'=>$migOk.' migration(s) applied successfully'];
        } else {
            $log[] = ['ok'=>false,'step'=>'Database Migrations', 'msg'=>$migOk.' applied, '.$migFail.' failed'];
            foreach (array_filter($migResults, fn($r) => $r['status'] === 'error') as $mf) {
                $errors[] = 'Migration '.$mf['file'].': '.$mf['message'];
            }
        }
        foreach ($migResults as $mr) {
            $log[] = ['ok'=>$mr['status']!=='error', 'step'=>'  → '.$mr['file'], 'msg'=>$mr['message'].' ('.$mr['ms'].'ms)', 'sub'=>true];
        }

        // STEP 3 — Sync config defaults
        if (file_exists($stageRoot . '/install/config_defaults.json')) {
            @copy($stageRoot . '/install/config_defaults.json', BASE_PATH . '/install/config_defaults.json');
        }
        $cfgChanged = Migrator::syncConfig();
        $log[] = ['ok'=>true, 'step'=>'Config Sync', 'msg'=>$cfgChanged ? 'New config keys merged into config.json' : 'Config already up to date'];

        // STEP 4 — Replace code files
        $fileOk   = 0;
        $fileSkip = 0;
        $fileFail = [];
        foreach ($changedFiles as $cf) {
            $rel = $cf['path'];
            if (strpos($rel, '..') !== false) continue;
            // Skip protected paths
            $skip = false;
            foreach ($UPGRADE_SKIP as $sp) { if (strpos($rel, $sp) === 0) { $skip = true; break; } }
            if ($skip) { $fileSkip++; continue; }

            $src  = $stageRoot . '/' . $rel;
            $dest = BASE_PATH  . '/' . $rel;
            if (!file_exists($src)) continue;

            // Backup existing file first
            if (file_exists($dest)) {
                $bkp = $backupDir . '/' . $rel;
                @mkdir(dirname($bkp), 0755, true);
                @copy($dest, $bkp);
            }
            @mkdir(dirname($dest), 0755, true);
            if (@copy($src, $dest)) {
                $fileOk++;
            } else {
                $fileFail[] = $rel;
                $errors[]   = 'Cannot copy file: ' . $rel . ' (check permissions)';
            }
        }
        $log[] = ['ok'=>empty($fileFail), 'step'=>'Code Files',
            'msg'=>$fileOk.' file(s) updated, '.$fileSkip.' skipped (protected)'.(!empty($fileFail)?', '.count($fileFail).' failed':'')];

        // STEP 5 — Copy new version.json
        if (file_exists($stageRoot . '/version.json')) {
            @copy($stageRoot . '/version.json', BASE_PATH . '/version.json');
            $log[] = ['ok'=>true, 'step'=>'Version File', 'msg'=>'version.json updated'];
        }

        // STEP 6 — Clear OPcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $log[] = ['ok'=>true, 'step'=>'OPcache', 'msg'=>'Cleared — new code is live immediately'];
        }

        // Save upgrade log to file
        $logText = date('Y-m-d H:i:s') . " — Script Upgrade\n\n";
        foreach ($log as $l) { $logText .= ($l['ok']?'✓ ':'✗ ') . $l['step'] . ': ' . $l['msg'] . "\n"; }
        if ($errors) { $logText .= "\nERRORS:\n" . implode("\n", $errors); }
        @file_put_contents($backupDir . '/upgrade.log', $logText);

        // Save result to session, clear staging
        $_SESSION['upg_result'] = [
            'log'      => $log,
            'errors'   => $errors,
            'backup'   => $backupId,
            'success'  => empty($errors),
            'new_ver'  => $_SESSION['upg_new_version']['version'] ?? '',
        ];
        unset($_SESSION['upg_stage_dir'], $_SESSION['upg_stage_root'],
              $_SESSION['upg_stage_id'],  $_SESSION['upg_new_version'],
              $_SESSION['upg_changelog'], $_SESSION['upg_migrations'],
              $_SESSION['upg_files'],     $_SESSION['upg_copied_mig'],
              $_SESSION['upg_orig_name'], $_SESSION['upg_file_size']);

        Helpers::redirect('/admin/system-update');
    }

    // ── APPLY DB MIGRATIONS ONLY ──────────────────────────────────────────────
    if ($action === 'migrate_only') {
        Migrator::init();
        $results = Migrator::runAll();
        $ok   = count(array_filter($results, fn($r) => $r['status'] === 'applied'));
        $fail = count(array_filter($results, fn($r) => $r['status'] === 'error'));
        if (empty($results)) {
            Helpers::flash('info', 'Database is already up to date — no migrations to apply.');
        } elseif ($fail === 0) {
            Helpers::flash('success', '✓ '.$ok.' migration(s) applied successfully. All data preserved.');
        } else {
            Helpers::flash('danger', $ok.' applied, '.$fail.' failed. See Migration History below for details.');
        }
        Helpers::redirect('/admin/system-update');
    }

    // ── RUN SINGLE MIGRATION ──────────────────────────────────────────────────
    if ($action === 'run_one') {
        $file = basename(Helpers::postRaw('file'));
        Migrator::init();
        $r = Migrator::runOne($file);
        Helpers::flash($r['status'] === 'applied' ? 'success' : 'danger', $file . ': ' . $r['message']);
        Helpers::redirect('/admin/system-update');
    }

    // ── SYNC CONFIG ───────────────────────────────────────────────────────────
    if ($action === 'sync_config') {
        $changed = Migrator::syncConfig();
        Helpers::flash('success', $changed ? 'Config defaults merged successfully.' : 'Config is already up to date.');
        Helpers::redirect('/admin/system-update');
    }

    // ── ROLLBACK ──────────────────────────────────────────────────────────────
    if ($action === 'rollback') {
        $bid  = preg_replace('/[^a-zA-Z0-9_]/', '', Helpers::postRaw('backup_id'));
        $bDir = BASE_PATH . '/storage/backups/code/' . $bid;
        if (!$bid || !is_dir($bDir)) {
            Helpers::flash('danger', 'Backup not found.');
            Helpers::redirect('/admin/system-update');
        }
        $restored = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($bDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $f) {
            if (!$f->isFile()) continue;
            $base = $f->getBasename();
            if (in_array($base, ['upgrade.log','db_backup.sql'])) continue;
            $rel  = ltrim(str_replace($bDir, '', $f->getPathname()), '/\\');
            $dest = BASE_PATH . '/' . $rel;
            @mkdir(dirname($dest), 0755, true);
            if (@copy($f->getPathname(), $dest)) $restored++;
        }
        if (function_exists('opcache_reset')) opcache_reset();
        Helpers::flash('success', 'Rollback complete — '.$restored.' file(s) restored from backup.');
        Helpers::redirect('/admin/system-update');
    }

    // ── CANCEL STAGED UPGRADE ─────────────────────────────────────────────────
    if ($action === 'cancel_staged') {
        $d = $_SESSION['upg_stage_dir'] ?? '';
        if ($d && is_dir($d)) upg_rmdir($d);
        foreach (['upg_stage_dir','upg_stage_root','upg_stage_id','upg_new_version',
                  'upg_changelog','upg_migrations','upg_files','upg_copied_mig',
                  'upg_orig_name','upg_file_size','upg_result'] as $k) unset($_SESSION[$k]);
        Helpers::flash('info', 'Upgrade cancelled.');
        Helpers::redirect('/admin/system-update');
    }

    // ── DISMISS RESULT ────────────────────────────────────────────────────────
    if ($action === 'dismiss_result') {
        unset($_SESSION['upg_result']);
        Helpers::redirect('/admin/system-update');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────────────────────
function upg_detect_root(string $dir): string
{
    $items = array_values(array_diff(scandir($dir), ['.','..']));
    if (count($items) === 1 && is_dir($dir.'/'.$items[0])) {
        return $dir . '/' . $items[0];
    }
    return $dir;
}

function upg_find_new_migrations(string $root): array
{
    $existing = array_map('basename', glob(BASE_PATH . '/install/migrations/*.sql') ?: []);
    $migDir   = $root . '/install/migrations';
    if (!is_dir($migDir)) return [];
    $found = [];
    foreach (glob($migDir . '/*.sql') ?: [] as $f) {
        $base = basename($f);
        if (!preg_match('/^\d{4}_/', $base)) continue;
        $found[] = [
            'src'    => $f,
            'name'   => $base,
            'status' => in_array($base, $existing) ? 'existing' : 'new',
            'copied' => false,
        ];
    }
    usort($found, fn($a,$b) => strcmp($a['name'],$b['name']));
    return $found;
}

function upg_analyze_files(string $root, array $skip): array
{
    $changed = [];
    $exts    = ['php','js','css','html','json','sql','md','txt'];
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            if (!in_array(strtolower($file->getExtension()), $exts)) continue;
            $rel = ltrim(str_replace('\\','/',str_replace($root,'',$file->getPathname())),'/');
            $skp = false;
            foreach ($skip as $s) { if (strpos($rel,$s)===0){$skp=true;break;} }
            if ($skp) continue;
            $dest = BASE_PATH . '/' . $rel;
            if (!file_exists($dest)) {
                $changed[] = ['path'=>$rel,'type'=>'new','size'=>$file->getSize()];
            } elseif (md5_file($file->getPathname()) !== md5_file($dest)) {
                $changed[] = ['path'=>$rel,'type'=>'modified','size'=>$file->getSize()];
            }
        }
    } catch (\Exception $e) {}
    return $changed;
}

function upg_backup_db(string $backupId): array
{
    $bDir = BASE_PATH . '/storage/backups/code/' . $backupId;
    @mkdir($bDir, 0755, true);
    $outFile = $bDir . '/db_backup.sql';
    try {
        $cfg  = Config::get('config','database') ?? [];
        $host = $cfg['host']     ?? '127.0.0.1';
        $port = $cfg['port']     ?? '3306';
        $name = $cfg['database'] ?? $cfg['name'] ?? '';
        $user = $cfg['username'] ?? $cfg['user'] ?? '';
        $pass = $cfg['password'] ?? '';
        if (!$name) return ['ok'=>false,'error'=>'DB name not configured'];

        // Try mysqldump
        $cmd  = sprintf('mysqldump --no-tablespaces -h%s -P%s -u%s -p%s %s 2>/dev/null',
            escapeshellarg($host), escapeshellarg($port),
            escapeshellarg($user), escapeshellarg($pass),
            escapeshellarg($name));
        ob_start(); @passthru($cmd . ' > ' . escapeshellarg($outFile)); ob_end_clean();
        if (file_exists($outFile) && filesize($outFile) > 200) {
            return ['ok'=>true,'file'=>$outFile];
        }

        // PHP fallback
        $pdo    = Database::getInstance();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $sql    = "-- Backup: {$name} — ".date('Y-m-d H:i:s')."\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $tbl) {
            $cr  = $pdo->query("SHOW CREATE TABLE `{$tbl}`")->fetch(\PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `{$tbl}`;\n" . $cr[1] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM `{$tbl}`")->fetchAll(\PDO::FETCH_ASSOC);
            if ($rows) {
                $cols  = '`'.implode('`,`',array_keys($rows[0])).'`';
                $vals  = [];
                foreach ($rows as $row) {
                    $esc = array_map(fn($v) => $v===null?'NULL':$pdo->quote((string)$v), array_values($row));
                    $vals[] = '('.implode(',',$esc).')';
                }
                $sql .= "INSERT INTO `{$tbl}` ({$cols}) VALUES\n".implode(",\n",$vals).";\n\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($outFile, $sql);
        return ['ok'=>true,'file'=>$outFile];
    } catch (\Exception $e) {
        return ['ok'=>false,'error'=>$e->getMessage()];
    }
}

function upg_rmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($dir);
}

function upg_list_backups(): array
{
    $dir = BASE_PATH . '/storage/backups/code';
    if (!is_dir($dir)) return [];
    $list = [];
    foreach (array_reverse(array_diff(scandir($dir),['.','..'])) as $d) {
        $path = $dir.'/'.$d;
        if (!is_dir($path)) continue;
        $hasDb = file_exists($path.'/db_backup.sql');
        $list[] = [
            'id'      => $d,
            'date'    => preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',$d,$m)
                           ? "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}" : $d,
            'has_db'  => $hasDb,
            'db_size' => $hasDb ? number_format(round(filesize($path.'/db_backup.sql')/1024)).' KB' : '—',
            'log'     => file_exists($path.'/upgrade.log') ? file_get_contents($path.'/upgrade.log') : '',
        ];
    }
    return array_slice($list, 0, 8);
}

function upg_system_checks(): array
{
    $checks = [];
    $writable = true;
    foreach ([BASE_PATH.'/core',BASE_PATH.'/controllers',BASE_PATH.'/views',
              BASE_PATH.'/storage',BASE_PATH.'/install/migrations'] as $d) {
        if (!is_writable($d)) { $writable = false; break; }
    }
    $checks[] = ['label'=>'Write Permissions', 'ok'=>$writable,
        'detail'=>$writable ? 'All required directories are writable' : 'Some directories are NOT writable — fix chmod'];
    $phpOk = PHP_MAJOR_VERSION >= 7 && (PHP_MAJOR_VERSION > 7 || PHP_MINOR_VERSION >= 4);
    $checks[] = ['label'=>'PHP Version','ok'=>$phpOk,'detail'=>'PHP '.PHP_VERSION.($phpOk?'':' (min 7.4 required)')];
    $zipOk = class_exists('ZipArchive');
    $checks[] = ['label'=>'ZipArchive','ok'=>$zipOk,'detail'=>$zipOk?'Installed':'NOT installed — ask host to enable php-zip'];
    $free = @disk_free_space(BASE_PATH);
    $diskOk = $free !== false && $free > 50*1024*1024;
    $checks[] = ['label'=>'Free Disk Space','ok'=>$diskOk,
        'detail'=>$free!==false ? round($free/1024/1024).' MB free'.(!$diskOk?' (need 50MB+)':'') : 'Cannot check'];
    $uploadMax = ini_get('upload_max_filesize');
    $checks[] = ['label'=>'Upload Limit','ok'=>true,'detail'=>'upload_max_filesize: '.$uploadMax.' · post_max_size: '.ini_get('post_max_size')];
    return $checks;
}

// ─────────────────────────────────────────────────────────────────────────────
// LOAD DATA FOR VIEW
// ─────────────────────────────────────────────────────────────────────────────
Migrator::init();

$pending       = Migrator::getPending();
$migHistory    = Migrator::getHistory();
$currentVer    = Migrator::getCurrentVersion();
$allMigFiles   = array_map('basename', glob(BASE_PATH.'/install/migrations/*.sql') ?: []);
sort($allMigFiles);

$appliedMap = [];
foreach ($migHistory as $h) $appliedMap[$h['migration']] = $h;
$migrationList = [];
foreach ($allMigFiles as $f) {
    $migrationList[] = array_merge(
        ['file'=>$f,'status'=>'pending','applied_at'=>null,'execution_ms'=>null,
         'message'=>null,'batch'=>null,'error_message'=>null],
        $appliedMap[$f] ?? []
    );
}

$versionFile = BASE_PATH . '/version.json';
$appVersion  = file_exists($versionFile) ? (json_decode(file_get_contents($versionFile),true)?:[]) : [];

// Staged package
$hasStagedPackage = isset($_SESSION['upg_stage_root']) && is_dir($_SESSION['upg_stage_root']??'');
$staged = $hasStagedPackage ? [
    'version'   => $_SESSION['upg_new_version'] ?? [],
    'changelog' => $_SESSION['upg_changelog']   ?? '',
    'migrations'=> $_SESSION['upg_migrations']  ?? [],
    'files'     => $_SESSION['upg_files']       ?? [],
    'copied'    => $_SESSION['upg_copied_mig']  ?? 0,
    'orig_name' => $_SESSION['upg_orig_name']   ?? '',
    'file_size' => $_SESSION['upg_file_size']   ?? 0,
] : null;

// Last result
$upgradeResult = $_SESSION['upg_result'] ?? null;

$backups      = upg_list_backups();
$systemChecks = upg_system_checks();

// Missing config keys
$cfgDefaults = [];
$cfgDefsFile = BASE_PATH.'/install/config_defaults.json';
if (file_exists($cfgDefsFile)) $cfgDefaults = json_decode(file_get_contents($cfgDefsFile),true) ?: [];
$missingKeys = [];
foreach ($cfgDefaults as $k => $v) {
    if (Config::get('config',$k) === null) $missingKeys[$k] = $v;
}

require BASE_PATH . '/views/admin/update/index.php';
