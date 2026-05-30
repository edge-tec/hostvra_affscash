<?php
/**
 * Advertiser — Postback Log
 *
 * Shows the advertiser a log of every postback they fired to this tracker:
 * accepted conversions, duplicates, invalid click IDs, capped events, etc.
 * Scoped strictly to the logged-in advertiser (advertiser_id from session).
 */
Auth::check('advertiser');
$pageTitle   = 'Postback Log';
$advertiserId = (int)Auth::advertiserId();

// ── Auto-create table if it doesn't exist yet ─────────────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `advertiser_postback_logs` (
        `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `advertiser_id` INT UNSIGNED DEFAULT NULL,
        `offer_id`      INT UNSIGNED DEFAULT NULL,
        `click_id`      VARCHAR(255) DEFAULT NULL,
        `conversion_id` CHAR(36) DEFAULT NULL,
        `payout`        DECIMAL(10,4) DEFAULT 0.0000,
        `status`        VARCHAR(30) NOT NULL DEFAULT 'error',
        `reject_reason` VARCHAR(500) DEFAULT NULL,
        `request_ip`    VARCHAR(45) DEFAULT NULL,
        `request_url`   TEXT DEFAULT NULL,
        `response_body` TEXT DEFAULT NULL,
        `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_adv_id`   (`advertiser_id`),
        INDEX `idx_click_id` (`click_id`),
        INDEX `idx_created`  (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable $e) {}

// ── Filters ───────────────────────────────────────────────────────────────
$filterStatus = Helpers::get('status') ?: 'all';
$filterOffer  = (int)Helpers::get('offer_id');
$from         = Helpers::get('from') ?: date('Y-m-d', strtotime('-7 days'));
$to           = Helpers::get('to')   ?: date('Y-m-d');
$dateFrom     = date('Y-m-d 00:00:00', strtotime($from));
$dateTo       = date('Y-m-d 23:59:59', strtotime($to));

$where  = ['pl.advertiser_id = ?', 'pl.created_at BETWEEN ? AND ?'];
$params = [$advertiserId, $dateFrom, $dateTo];

if (in_array($filterStatus, ['accepted','duplicate','invalid_click','blocked','capped','hidden','fraud_blocked','error'])) {
    $where[]  = 'pl.status = ?';
    $params[] = $filterStatus;
}
if ($filterOffer) {
    $where[]  = 'pl.offer_id = ?';
    $params[] = $filterOffer;
}
$whereSQL = implode(' AND ', $where);

// ── CSV Export ────────────────────────────────────────────────────────────
if (Helpers::get('export') === 'csv') {
    try {
        $rows = Database::fetchAll(
            "SELECT pl.*, o.name as offer_name
             FROM advertiser_postback_logs pl
             LEFT JOIN offers o ON o.id = pl.offer_id
             WHERE $whereSQL ORDER BY pl.created_at DESC LIMIT 10000",
            $params
        );
    } catch (\Throwable $e) { $rows = []; }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="postback_log_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    $fh = fopen('php://output', 'w');
    fputcsv($fh, ['ID','Offer','Click ID','Conversion ID','Payout','Status','Reason','IP','Date']);
    foreach ($rows as $r) {
        fputcsv($fh, [
            $r['id'], $r['offer_name'] ?? '—', $r['click_id'], $r['conversion_id'] ?? '—',
            number_format((float)$r['payout'], 4), $r['status'],
            $r['reject_reason'] ?? '', $r['request_ip'] ?? '', $r['created_at'],
        ]);
    }
    fclose($fh);
    exit;
}

// ── Fetch logs ────────────────────────────────────────────────────────────
try {
    $logs = Database::fetchAll(
        "SELECT pl.*, o.name as offer_name
         FROM advertiser_postback_logs pl
         LEFT JOIN offers o ON o.id = pl.offer_id
         WHERE $whereSQL ORDER BY pl.created_at DESC LIMIT 1000",
        $params
    );
} catch (\Throwable $e) { $logs = []; }

// ── Summary stats ─────────────────────────────────────────────────────────
try {
    $stats = Database::fetchOne(
        "SELECT COUNT(*) as total,
                SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status='duplicate' THEN 1 ELSE 0 END) as duplicate,
                SUM(CASE WHEN status IN ('invalid_click','blocked','capped','hidden','fraud_blocked','error') THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status='accepted' THEN payout ELSE 0 END) as total_payout
         FROM advertiser_postback_logs pl
         WHERE pl.advertiser_id=? AND pl.created_at BETWEEN ? AND ?",
        [$advertiserId, $dateFrom, $dateTo]
    );
} catch (\Throwable $e) { $stats = []; }

// ── Offer list for filter ─────────────────────────────────────────────────
try {
    $offerList = Database::fetchAll(
        "SELECT id, name FROM offers WHERE advertiser_id=? AND status IN ('active','paused') ORDER BY name",
        [$advertiserId]
    );
} catch (\Throwable $e) { $offerList = []; }

require BASE_PATH . '/views/advertiser/postback_logs/index.php';
