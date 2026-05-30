<?php
/**
 * sync_points.php — Auto-Points Sync Program
 * ============================================
 * Scans every approved conversion that has NOT yet been credited with points
 * and applies the USD-per-point rule from points_config.
 *
 * Safe to run repeatedly: PointsService::credit() is idempotent via the
 * (affiliate_id, ref_type='conversion', ref_id=conversion_id, kind='earn')
 * unique guard — duplicate runs will skip already-credited rows.
 *
 * Usage (CLI):
 *   php sync_points.php
 *   php sync_points.php --dry-run          # preview only, no DB writes
 *   php sync_points.php --batch=500        # rows per query pass (default 200)
 *   php sync_points.php --since=2024-01-01 # only conversions approved after date
 *
 * Recommended cron (runs every 5 minutes):
 *   * /5 * * * * php /path/to/cron/sync_points.php >> /var/log/points_sync.log 2>&1
 *
 * Output format (one line per action):
 *   [2025-06-01 12:00:01] SKIP  conv#1234  aff#22  already credited
 *   [2025-06-01 12:00:01] CREDIT conv#1235  aff#27  $40.00 → 2 pts  (balance: 12)
 *   [2025-06-01 12:00:01] SKIP  conv#1236  payout=0, skipping
 *   [2025-06-01 12:00:01] DONE  processed=150 credited=12 skipped=138 errors=0
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────

define('RUNNING_FROM_CLI', php_sapi_name() === 'cli');

// Locate the project root (two levels up from cron/).
$baseDir = dirname(__DIR__);

// Allow running from web for manual trigger or background async fetch.
if (!RUNNING_FROM_CLI) {
    $tokenOk = false;
    
    // Check if called with a valid cron secret token
    if (file_exists($baseDir . '/core/Config.php')) {
        require_once $baseDir . '/core/Config.php';
        try { Config::init($baseDir . '/config'); } catch (\Throwable $_) {}
        $fraudToken = Config::get('config', 'app.fraud_scan_cron_token');
        $cronSecret = Config::get('config', 'cron.secret') ?: $fraudToken;
        if ($cronSecret !== '' && (($_GET['token'] ?? '') === $cronSecret)) {
            $tokenOk = true;
        }
    }

    // If no valid token, require admin auth
    if (!$tokenOk) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['admin_id'])) {
            http_response_code(403);
            die(json_encode(['error' => 'Forbidden']));
        }
    }
}
$bootstrap = $baseDir . '/bootstrap.php';

if (!file_exists($bootstrap)) {
    // Try common alternate paths.
    foreach (['init.php', 'load.php', 'index.php'] as $candidate) {
        if (file_exists($baseDir . '/' . $candidate)) {
            $bootstrap = $baseDir . '/' . $candidate;
            break;
        }
    }
}

if (!file_exists($bootstrap)) {
    fwrite(STDERR, "[sync_points] Cannot find bootstrap file in {$baseDir}\n");
    exit(1);
}

// Bootstrap sets BASE_PATH, auto-loads classes (Database, PointsService, etc.)
define('BASE_PATH', $baseDir);
require $bootstrap;

// ── Parse CLI arguments ───────────────────────────────────────────────────────

$dryRun   = false;
$batchSz  = 200;
$since    = null;

if (RUNNING_FROM_CLI) {
    foreach ($argv as $arg) {
        if ($arg === '--dry-run')                  $dryRun  = true;
        if (preg_match('/^--batch=(\d+)$/', $arg, $m)) $batchSz = max(1, (int)$m[1]);
        if (preg_match('/^--since=(.+)$/',  $arg, $m)) $since   = $m[1];
    }
} else {
    // HTTP parameters for manual web trigger.
    $dryRun  = !empty($_GET['dry_run']);
    $batchSz = max(1, min(1000, (int)($_GET['batch'] ?? 200)));
    $since   = $_GET['since'] ?? null;
}

// ── Helper ────────────────────────────────────────────────────────────────────

function log_line(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if (RUNNING_FROM_CLI) {
        echo $line . "\n";
    } else {
        // Collect for JSON response.
        $GLOBALS['_sync_log'][] = $line;
    }
}

$GLOBALS['_sync_log'] = [];

// ── Main sync logic ───────────────────────────────────────────────────────────

function run_sync(bool $dryRun, int $batchSz, ?string $since): array
{
    $cfg = PointsService::config();

    if (!$cfg['enabled'] && !$dryRun) {
        log_line('ABORT  Points module is disabled — nothing to do. Enable it in Admin → Points Module.');
        return ['processed' => 0, 'credited' => 0, 'skipped' => 0, 'errors' => 0];
    }

    $usdPerPt = max(0.01, (float)$cfg['usd_per_point']);
    log_line(sprintf(
        'START  rule=$%.2f per point  dry_run=%s  batch=%d',
        $usdPerPt,
        $dryRun ? 'YES' : 'NO',
        $batchSz
    ));

    $processed = 0;
    $credited  = 0;
    $skipped   = 0;
    $errors    = 0;
    $offset    = 0;

    $sinceClause = '';
    $sinceParams = [];
    if ($since) {
        $sinceClause = 'AND c.updated_at >= ?';
        $sinceParams = [$since];
    }

    /*
     * Strategy: page through approved, non-hidden conversions with payout > 0.
     * For each, check if a points_transactions row already exists for
     * ref_type='conversion' + ref_id=conversion_id. If not, credit.
     *
     * We LEFT JOIN to points_transactions so the DB does the dedup check,
     * keeping PHP loops small.
     */
    while (true) {
        $rows = Database::fetchAll(
            "SELECT
                c.id            AS conv_id,
                c.affiliate_id,
                c.payout,
                COALESCE(c.is_hidden, 0) AS is_hidden,
                pt.id           AS already_credited
             FROM conversions c
             LEFT JOIN points_transactions pt
                    ON pt.affiliate_id = c.affiliate_id
                   AND pt.ref_type    = 'conversion'
                   AND pt.ref_id      = CAST(c.id AS CHAR)
                   AND pt.kind        = 'earn'
             WHERE c.status = 'approved'
               AND c.payout > 0
               $sinceClause
             ORDER BY c.id ASC
             LIMIT $batchSz OFFSET $offset",
            $sinceParams
        );

        if (empty($rows)) break;

        foreach ($rows as $row) {
            $processed++;
            $convId = (int)$row['conv_id'];
            $affId  = (int)$row['affiliate_id'];
            $payout = (float)$row['payout'];

            // Skip hidden conversions.
            if ((int)$row['is_hidden'] === 1) {
                log_line(sprintf('SKIP   conv#%d  aff#%d  hidden conversion', $convId, $affId));
                $skipped++;
                continue;
            }

            // Already credited?
            if ($row['already_credited'] !== null) {
                log_line(sprintf('SKIP   conv#%d  aff#%d  already credited', $convId, $affId));
                $skipped++;
                continue;
            }

            // Calculate points.
            $points = (int)floor($payout / $usdPerPt);
            if ($points <= 0) {
                log_line(sprintf('SKIP   conv#%d  aff#%d  $%.2f → 0 pts (below threshold)', $convId, $affId, $payout));
                $skipped++;
                continue;
            }

            if ($dryRun) {
                log_line(sprintf('DRY    conv#%d  aff#%d  $%.2f → %d pts  (would credit)', $convId, $affId, $payout, $points));
                $credited++;
                continue;
            }

            // Credit via PointsService (idempotent).
            try {
                $newBalance = PointsService::credit(
                    $affId,
                    $points,
                    sprintf('Auto-sync: earnings $%.2f ÷ $%.2f = %d pts', $payout, $usdPerPt, $points),
                    'conversion',
                    (string)$convId
                );
                log_line(sprintf(
                    'CREDIT conv#%d  aff#%d  $%.2f → +%d pts  (balance: %d)',
                    $convId, $affId, $payout, $points, $newBalance
                ));
                $credited++;
            } catch (Throwable $e) {
                log_line(sprintf('ERROR  conv#%d  aff#%d  %s', $convId, $affId, $e->getMessage()));
                $errors++;
            }
        }

        $offset += $batchSz;

        // Safety: if we got fewer rows than batch size, we're done.
        if (count($rows) < $batchSz) break;
    }

    log_line(sprintf(
        'DONE   processed=%d  credited=%d  skipped=%d  errors=%d',
        $processed, $credited, $skipped, $errors
    ));

    return compact('processed', 'credited', 'skipped', 'errors');
}

// ── Run ───────────────────────────────────────────────────────────────────────

$result = run_sync($dryRun, $batchSz, $since);

if (!RUNNING_FROM_CLI) {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'     => $result['errors'] === 0,
        'result' => $result,
        'log'    => $GLOBALS['_sync_log'],
    ]);
}

exit($result['errors'] > 0 ? 1 : 0);
