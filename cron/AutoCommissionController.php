<?php
/**
 * AutoCommissionController — Cron: Auto-calculate manager commissions
 *
 * Runs on a schedule (e.g. every 5–15 minutes via cron) and automatically
 * calculates commissions for ALL active affiliate managers who have:
 *   • A commission_rate > 0 (all-offer mode), OR
 *   • Per-offer commission entries in manager_offer_commissions (selected-offer mode)
 *
 * Safe to run as frequently as needed — INSERT IGNORE prevents any duplicates.
 *
 * Cron entry example (every 10 minutes):
 *   */10 * * * * php /path/to/public/index.php /cron/auto-commission >> /dev/null 2>&1
 *
 * Or call via HTTP with a secret token:
 *   https://yourdomain.com/cron/auto-commission?token=YOUR_CRON_SECRET
 */

// ── Security: allow CLI or token-protected HTTP calls only ─────────────────
if (PHP_SAPI !== 'cli') {
    $cronSecret = Config::get('config', 'cron.secret') ?: '';
    $tokenOk    = $cronSecret !== '' && (Helpers::get('token') === $cronSecret);
    // Also allow calls from admin session
    $adminOk    = Auth::check() && Auth::user()['role'] === 'admin';
    if (!$tokenOk && !$adminOk) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once BASE_PATH . '/core/ManagerCommissionService.php';

$startTime = microtime(true);
$results   = [];
$errors    = [];

try {
    // ── Get all active managers that have commission configuration ───────────
    $managers = Database::fetchAll(
        "SELECT am.id AS mgr_id, am.commission_rate, am.balance,
                CONCAT(u.first_name, ' ', u.last_name) AS manager_name
         FROM affiliate_managers am
         JOIN users u ON u.id = am.user_id
         WHERE u.status = 'active'
         ORDER BY am.id ASC"
    );

    foreach ($managers as $mgr) {
        $mgrId   = (int)$mgr['mgr_id'];
        $mgrRate = (float)$mgr['commission_rate'];

        // Check if this manager has per-offer overrides
        $hasOfferOverrides = false;
        try {
            $hasOfferOverrides = (int)(Database::fetchOne(
                "SELECT COUNT(*) AS c FROM manager_offer_commissions WHERE manager_id = ?",
                [$mgrId]
            )['c'] ?? 0) > 0;
        } catch (\Throwable $_e) {}

        // Skip managers with no commission configuration at all
        if ($mgrRate <= 0 && !$hasOfferOverrides) {
            continue;
        }

        // Count approved conversions not yet assigned a commission record
        $unconvertedCount = 0;
        try {
            $unconvertedCount = (int)(Database::fetchOne(
                "SELECT COUNT(*) AS c
                 FROM conversions c
                 JOIN affiliates af ON af.id = c.affiliate_id AND af.manager_id = ?
                 LEFT JOIN manager_commissions mc ON mc.conversion_id = c.id AND mc.manager_id = ?
                 WHERE c.status = 'approved'
                   AND COALESCE(c.is_hidden, 0) = 0
                   AND c.payout > 0
                   AND mc.id IS NULL",
                [$mgrId, $mgrId]
            )['c'] ?? 0);
        } catch (\Throwable $_e) {
            $errors[] = "Manager #{$mgrId} count error: " . $_e->getMessage();
            continue;
        }

        if ($unconvertedCount === 0) {
            // Nothing to process for this manager
            continue;
        }

        // Run backfill — only processes unrecorded conversions (INSERT IGNORE skips existing)
        try {
            $r = ManagerCommissionService::backfillForManager($mgrId, '2000-01-01', date('Y-m-d'));

            $results[] = [
                'manager_id'   => $mgrId,
                'manager_name' => $mgr['manager_name'],
                'processed'    => $r['processed'],
                'credited'     => $r['credited'],
                'skipped'      => $r['skipped'],
                'total_added'  => $r['total_added'],
                'error'        => $r['error'] ?? '',
            ];

            if (!empty($r['error'])) {
                $errors[] = "Manager #{$mgrId} ({$mgr['manager_name']}): " . $r['error'];
                error_log("[AutoCommission] Manager #{$mgrId} error: " . $r['error']);
            } elseif ($r['credited'] > 0) {
                error_log(sprintf(
                    "[AutoCommission] Manager #%d (%s): credited %d commission(s), total balance $%.2f",
                    $mgrId, $mgr['manager_name'], $r['credited'], $r['total_added']
                ));
            }
        } catch (\Throwable $e) {
            $errors[] = "Manager #{$mgrId} ({$mgr['manager_name']}): " . $e->getMessage();
            error_log("[AutoCommission] Exception for manager #{$mgrId}: " . $e->getMessage());
        }
    }
} catch (\Throwable $e) {
    $errors[] = 'Fatal: ' . $e->getMessage();
    error_log('[AutoCommission] Fatal error: ' . $e->getMessage());
}

$elapsed = round(microtime(true) - $startTime, 3);
$totalCredited = array_sum(array_column($results, 'credited'));

// ── Output response ──────────────────────────────────────────────────────────
if (PHP_SAPI === 'cli') {
    // CLI output
    echo "[AutoCommission] Done in {$elapsed}s — {$totalCredited} commission(s) credited across " . count($results) . " manager(s).\n";
    if ($errors) {
        echo "[AutoCommission] Errors:\n";
        foreach ($errors as $err) {
            echo "  - {$err}\n";
        }
    }
} else {
    // HTTP JSON output
    header('Content-Type: application/json');
    echo json_encode([
        'status'        => empty($errors) ? 'ok' : 'partial',
        'elapsed_sec'   => $elapsed,
        'managers_run'  => count($results),
        'total_credited'=> $totalCredited,
        'results'       => $results,
        'errors'        => $errors,
    ], JSON_PRETTY_PRINT);
}
