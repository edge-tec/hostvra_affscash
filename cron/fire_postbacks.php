<?php
/**
 * Cron: Re-fire failed postbacks
 *
 * Run every 5 minutes:
 *   * /5 * * * * php /path/to/cron/fire_postbacks.php >> /path/to/logs/cron.log 2>&1
 *
 * Retries:
 *   1. Failed affiliate-level postbacks  (postback_logs where postback_id > 0)
 *   2. Failed affiliate GLOBAL postbacks (postback_logs where postback_id = 0)
 *   3. Failed admin global postbacks     (global_postback_logs)
 *
 * Each row is retried up to MAX_ATTEMPTS times within RETRY_WINDOW minutes.
 * After MAX_ATTEMPTS the row is left as-is so the log remains visible for
 * admin inspection.
 */

define('BASE_PATH',   dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/PostbackFirer.php';

Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

const MAX_ATTEMPTS  = 4;          // total tries (1 original + 3 retries)
const RETRY_WINDOW  = 120;        // only retry within this many minutes of original fire
const RETRY_BACKOFF = [0, 5, 20, 60]; // minutes between attempts (attempt_count => delay)

$retried  = 0;
$success  = 0;
$stillFail = 0;

// ── Ensure attempt_count and first_fired_at columns exist in postback_logs ──
try {
    Database::query("ALTER TABLE `postback_logs` ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED DEFAULT 1");
} catch (\Throwable $e) {}
try {
    // first_fired_at = original fire time; fired_at = most recent attempt time.
    // RETRY_WINDOW must check first_fired_at so we don't retry forever
    // (fired_at is reset to NOW() on each attempt, making the window roll forward).
    Database::query("ALTER TABLE `postback_logs` ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL");
    // Backfill: set first_fired_at = fired_at for rows that were created before this column existed
    Database::query("UPDATE `postback_logs` SET `first_fired_at`=`fired_at` WHERE `first_fired_at` IS NULL");
} catch (\Throwable $e) {}
try {
    Database::query("ALTER TABLE `global_postback_logs` ADD COLUMN IF NOT EXISTS `attempt_count` TINYINT UNSIGNED DEFAULT 1");
} catch (\Throwable $e) {}
try {
    Database::query("ALTER TABLE `global_postback_logs` ADD COLUMN IF NOT EXISTS `first_fired_at` DATETIME DEFAULT NULL");
    Database::query("UPDATE `global_postback_logs` SET `first_fired_at`=`fired_at` WHERE `first_fired_at` IS NULL");
} catch (\Throwable $e) {}

// ────────────────────────────────────────────────────────────────────────
// 1. AFFILIATE-LEVEL POSTBACKS  (postback_logs, postback_id > 0)
// ────────────────────────────────────────────────────────────────────────
try {
    $failed = Database::fetchAll(
        "SELECT pl.*, pb.method, pb.url AS template_url
         FROM postback_logs pl
         JOIN postbacks pb ON pb.id = pl.postback_id
         WHERE pl.is_success   = 0
           AND pl.attempt_count < ?
           AND COALESCE(pl.first_fired_at, pl.fired_at) > DATE_SUB(NOW(), INTERVAL ? MINUTE)
           AND pl.postback_id  > 0
         ORDER BY pl.fired_at ASC
         LIMIT 200",
        [MAX_ATTEMPTS, RETRY_WINDOW]
    );
} catch (\Throwable $e) {
    PostbackFirer::log("[cron/fire_postbacks] DB error loading affiliate postbacks: " . $e->getMessage());
    $failed = [];
}

foreach ($failed as $log) {
    $attempt  = (int)($log['attempt_count'] ?? 1);
    $delay    = RETRY_BACKOFF[$attempt] ?? 60;
    // Back-off uses fired_at (last attempt time) — prevents retrying too soon after last try
    $firedAgo = (int)((time() - strtotime($log['fired_at'])) / 60);

    // Respect back-off: don't retry too soon
    if ($firedAgo < $delay) {
        continue;
    }

    $result = Helpers::firePostback($log['fired_url'], $log['method'] ?? 'GET');

    $note = '';
    if (!empty($result['error'])) {
        $note = '[cURL: ' . $result['error'] . '] ';
    }

    try {
        Database::query(
            "UPDATE postback_logs
             SET is_success     = ?,
                 http_status    = ?,
                 response_body  = ?,
                 attempt_count  = attempt_count + 1,
                 fired_at       = NOW()
             WHERE id = ?",
            [
                $result['success'] ? 1 : 0,
                $result['status'],
                substr($note . ($result['body'] ?? ''), 0, 1000),
                $log['id'],
            ]
        );
    } catch (\Throwable $e) {
        PostbackFirer::log("[cron] Failed to update postback_log #{$log['id']}: " . $e->getMessage());
    }

    $retried++;
    if ($result['success']) {
        $success++;
        echo "✓ Affiliate postback log #{$log['id']} retry OK (HTTP {$result['status']})\n";
    } else {
        $stillFail++;
        echo "✗ Affiliate postback log #{$log['id']} retry FAIL (HTTP {$result['status']}) {$result['error']}\n";
    }
}

// ────────────────────────────────────────────────────────────────────────
// 2. AFFILIATE GLOBAL POSTBACKS  (postback_logs, postback_id = 0)
//    These use the fired_url directly — no join needed.
// ────────────────────────────────────────────────────────────────────────
try {
    $failedGlobalAff = Database::fetchAll(
        "SELECT pl.*
         FROM postback_logs pl
         WHERE pl.is_success   = 0
           AND pl.attempt_count < ?
           AND COALESCE(pl.first_fired_at, pl.fired_at) > DATE_SUB(NOW(), INTERVAL ? MINUTE)
           AND pl.postback_id  = 0
         ORDER BY pl.fired_at ASC
         LIMIT 200",
        [MAX_ATTEMPTS, RETRY_WINDOW]
    );
} catch (\Throwable $e) {
    PostbackFirer::log("[cron/fire_postbacks] DB error loading aff global postbacks: " . $e->getMessage());
    $failedGlobalAff = [];
}

foreach ($failedGlobalAff as $log) {
    $attempt  = (int)($log['attempt_count'] ?? 1);
    $delay    = RETRY_BACKOFF[$attempt] ?? 60;
    // Back-off uses fired_at (last attempt) not first_fired_at (original fire)
    $firedAgo = (int)((time() - strtotime($log['fired_at'])) / 60);

    if ($firedAgo < $delay) {
        continue;
    }

    // Verify the affiliate's global postback is still approved & active
    // before re-firing, to avoid sending to a deactivated/rejected URL.
    try {
        $conv = Database::fetchOne(
            "SELECT cv.affiliate_id FROM conversions cv WHERE cv.conversion_id = ? LIMIT 1",
            [$log['conversion_id']]
        );
        if ($conv) {
            $affRow = Database::fetchOne(
                "SELECT global_pb_admin_status, global_pb_active FROM affiliates WHERE id = ?",
                [(int)$conv['affiliate_id']]
            );
            $pbStatus    = $affRow['global_pb_admin_status'] ?? null;
            $pbActiveRaw = $affRow['global_pb_active'] ?? null;
            $pbActive    = ($pbActiveRaw === null) ? null : (int)$pbActiveRaw;
            // Mirror PostbackFirer::fireAffiliateGlobalPostback canFire logic:
            // only block if explicitly rejected/pending or explicitly deactivated (=0)
            if (
                !($pbStatus === 'approved' || $pbStatus === null) ||
                ($pbActive === 0)
            ) {
                // Postback no longer active — mark as permanently skipped
                Database::query(
                    "UPDATE postback_logs SET attempt_count = ?, response_body = ? WHERE id = ?",
                    [MAX_ATTEMPTS, '[SKIPPED: global postback deactivated or rejected]', $log['id']]
                );
                continue;
            }
        }
    } catch (\Throwable $e) {}

    $result = Helpers::firePostback($log['fired_url'], 'GET');

    $note = !empty($result['error']) ? '[cURL: ' . $result['error'] . '] ' : '';

    try {
        Database::query(
            "UPDATE postback_logs
             SET is_success    = ?,
                 http_status   = ?,
                 response_body = ?,
                 attempt_count = attempt_count + 1,
                 fired_at      = NOW()
             WHERE id = ?",
            [
                $result['success'] ? 1 : 0,
                $result['status'],
                substr($note . ($result['body'] ?? ''), 0, 1000),
                $log['id'],
            ]
        );
    } catch (\Throwable $e) {
        PostbackFirer::log("[cron] Failed to update global-aff postback_log #{$log['id']}: " . $e->getMessage());
    }

    $retried++;
    if ($result['success']) {
        $success++;
        echo "✓ Aff-global postback log #{$log['id']} retry OK (HTTP {$result['status']})\n";
    } else {
        $stillFail++;
        echo "✗ Aff-global postback log #{$log['id']} retry FAIL (HTTP {$result['status']}) {$result['error']}\n";
    }
}

// ────────────────────────────────────────────────────────────────────────
// 3. ADMIN GLOBAL POSTBACKS  (global_postback_logs)
// ────────────────────────────────────────────────────────────────────────
try {
    $failedGlobal = Database::fetchAll(
        "SELECT gpl.*, gp.method, gp.status AS pb_status
         FROM global_postback_logs gpl
         JOIN global_postbacks gp ON gp.id = gpl.global_postback_id
         WHERE gpl.is_success   = 0
           AND gpl.attempt_count < ?
           AND COALESCE(gpl.first_fired_at, gpl.fired_at) > DATE_SUB(NOW(), INTERVAL ? MINUTE)
           AND gp.status        = 'active'
         ORDER BY gpl.fired_at ASC
         LIMIT 200",
        [MAX_ATTEMPTS, RETRY_WINDOW]
    );
} catch (\Throwable $e) {
    PostbackFirer::log("[cron/fire_postbacks] DB error loading global_postback_logs: " . $e->getMessage());
    $failedGlobal = [];
}

foreach ($failedGlobal as $log) {
    $attempt  = (int)($log['attempt_count'] ?? 1);
    $delay    = RETRY_BACKOFF[$attempt] ?? 60;
    // Back-off uses fired_at (last attempt) so we space out retries correctly
    $firedAgo = (int)((time() - strtotime($log['fired_at'])) / 60);

    if ($firedAgo < $delay) {
        continue;
    }

    $result = Helpers::firePostback($log['fired_url'], $log['method'] ?? 'GET');

    $note = !empty($result['error']) ? '[cURL: ' . $result['error'] . '] ' : '';

    try {
        Database::query(
            "UPDATE global_postback_logs
             SET is_success    = ?,
                 http_status   = ?,
                 response_body = ?,
                 attempt_count = attempt_count + 1,
                 fired_at      = NOW()
             WHERE id = ?",
            [
                $result['success'] ? 1 : 0,
                $result['status'],
                substr($note . ($result['body'] ?? ''), 0, 1000),
                $log['id'],
            ]
        );
    } catch (\Throwable $e) {
        PostbackFirer::log("[cron] Failed to update global_postback_log #{$log['id']}: " . $e->getMessage());
    }

    $retried++;
    if ($result['success']) {
        $success++;
        echo "✓ Global postback log #{$log['id']} retry OK (HTTP {$result['status']})\n";
    } else {
        $stillFail++;
        echo "✗ Global postback log #{$log['id']} retry FAIL (HTTP {$result['status']}) {$result['error']}\n";
    }
}

// ────────────────────────────────────────────────────────────────────────
// 4. ORPHANED CONVERSIONS  (postback_sent=0 with NO log entry at all)
//    These are conversions where the affiliate's global postback URL was
//    'pending' at conversion time — the postback was never even attempted.
//    Now that the URL is approved, we fire it automatically here.
//    Window: last 7 days.  Limit: 100 per run to avoid timeouts.
// ────────────────────────────────────────────────────────────────────────
try {
    $orphaned = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.offer_id, cv.affiliate_id,
                cv.payout, cv.revenue, cv.status,
                ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.sub5,
                COALESCE(ck.sub6, '') as sub6, ck.source
         FROM conversions cv
         LEFT JOIN clicks ck ON ck.click_id = cv.click_id
         WHERE cv.postback_sent = 0
           AND cv.status IN ('approved','pending')
           AND COALESCE(cv.is_hidden, 0) = 0
           AND cv.converted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND NOT EXISTS (
               SELECT 1 FROM postback_logs pl WHERE pl.conversion_id = cv.conversion_id
           )
         ORDER BY cv.converted_at ASC
         LIMIT 100",
        []
    );
} catch (\Throwable $e) {
    PostbackFirer::log("[cron/fire_postbacks] DB error loading orphaned conversions: " . $e->getMessage());
    $orphaned = [];
}

$orphanFired = 0;
foreach ($orphaned as $conv) {
    // Force 'approved' status when firing orphaned postbacks so the affiliate
    // global postback fires (PostbackFirer::fireAffiliateGlobalPostback only
    // fires on 'approved'; orphaned conversions are ones we missed at conversion
    // time — they should always trigger the global postback now).
    $fireStatus = 'approved';
    $convForPostback = [
        'conversion_id' => $conv['conversion_id'],
        'click_id'      => $conv['click_id'],
        'offer_id'      => $conv['offer_id'],
        'affiliate_id'  => $conv['affiliate_id'],
        'payout'        => (float)$conv['payout'],
        'revenue'       => (float)($conv['revenue'] ?? $conv['payout']),
        'status'        => $fireStatus,
        'source'        => $conv['source'] ?? '',
        'sub1'          => $conv['sub1'] ?? '',
        'sub2'          => $conv['sub2'] ?? '',
        'sub3'          => $conv['sub3'] ?? '',
        'sub4'          => $conv['sub4'] ?? '',
        'sub5'          => $conv['sub5'] ?? '',
        'sub6'          => $conv['sub6'] ?? '',
    ];
    try {
        PostbackFirer::fireAll($convForPostback, $fireStatus, true);
        $orphanFired++;
        echo "↺ Auto-fired orphaned postback for conversion {$conv['conversion_id']}\n";
    } catch (\Throwable $e) {
        PostbackFirer::log("[cron/fire_postbacks] Error firing orphaned conv {$conv['conversion_id']}: " . $e->getMessage());
    }
}
if ($orphanFired > 0) {
    PostbackFirer::log("[cron/fire_postbacks] Auto-fired {$orphanFired} orphaned conversion postbacks.");
}

// ────────────────────────────────────────────────────────────────────────
// 5. FLAG CLEANUP  (postback_sent=0 but postback_logs entries exist)
//    Catches the rare case where fireAll() ran (creating log entries) but
//    the final UPDATE postback_sent=1 was lost due to a crash or DB error.
//    The individual log retries (sections 1-3) handle actual delivery;
//    this section just corrects the flag so the admin UI shows accurate data.
//    Only touches conversions older than 10 minutes to avoid racing with
//    an in-progress initial fire.
// ────────────────────────────────────────────────────────────────────────
$flagFixed = 0;
try {
    $staleFlags = Database::fetchAll(
        "SELECT cv.conversion_id
         FROM conversions cv
         WHERE cv.postback_sent = 0
           AND COALESCE(cv.is_hidden, 0) = 0
           AND cv.converted_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
           AND cv.converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
           AND EXISTS (
               SELECT 1 FROM postback_logs pl WHERE pl.conversion_id = cv.conversion_id
           )
         LIMIT 200",
        []
    );
    foreach ($staleFlags as $row) {
        try {
            Database::query(
                "UPDATE conversions SET postback_sent=1 WHERE conversion_id=? AND postback_sent=0",
                [$row['conversion_id']]
            );
            $flagFixed++;
        } catch (\Throwable $e) {}
    }
} catch (\Throwable $e) {
    PostbackFirer::log("[cron/fire_postbacks] DB error in flag cleanup: " . $e->getMessage());
}
if ($flagFixed > 0) {
    PostbackFirer::log("[cron/fire_postbacks] Fixed {$flagFixed} stale postback_sent=0 flags.");
    echo "⚑ Fixed {$flagFixed} stale postback_sent flags\n";
}

// ── Summary ───────────────────────────────────────────────────────────────
$summary = sprintf(
    "Done. Retried: %d | Succeeded: %d | Still failing: %d | Orphans auto-fired: %d | Flags fixed: %d | Time: %s\n",
    $retried, $success, $stillFail, $orphanFired, $flagFixed, date('Y-m-d H:i:s')
);
echo $summary;
PostbackFirer::log("[cron/fire_postbacks] " . trim($summary));
