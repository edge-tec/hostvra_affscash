<?php
/**
 * Admin — Advertiser Postback Log
 *
 * Admin view of all incoming postbacks from all advertisers.
 * Filterable by date, advertiser, offer, and status.
 */
Auth::check('admin');
$pageTitle = 'Advertiser Postback Log';

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
$filterStatus   = Helpers::get('status') ?: 'all';
$filterAdv      = (int)Helpers::get('advertiser_id');
$filterOffer    = (int)Helpers::get('offer_id');
$from           = Helpers::get('from') ?: date('Y-m-d', strtotime('-7 days'));
$to             = Helpers::get('to')   ?: date('Y-m-d');
$dateFrom       = date('Y-m-d 00:00:00', strtotime($from));
$dateTo         = date('Y-m-d 23:59:59', strtotime($to));

$where  = ['pl.created_at BETWEEN ? AND ?'];
$params = [$dateFrom, $dateTo];

if (in_array($filterStatus, ['accepted','duplicate','invalid_click','blocked','capped','hidden','fraud_blocked','error'])) {
    $where[]  = 'pl.status = ?';
    $params[] = $filterStatus;
}
if ($filterAdv) {
    $where[]  = 'pl.advertiser_id = ?';
    $params[] = $filterAdv;
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
            "SELECT pl.*,
                    o.name as offer_name,
                    CONCAT(u.first_name,' ',u.last_name) as adv_name
             FROM advertiser_postback_logs pl
             LEFT JOIN offers o ON o.id = pl.offer_id
             LEFT JOIN advertisers a ON a.id = pl.advertiser_id
             LEFT JOIN users u ON u.id = a.user_id
             WHERE $whereSQL ORDER BY pl.created_at DESC LIMIT 10000",
            $params
        );
    } catch (\Throwable $e) { $rows = []; }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="advertiser_postback_log_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    $fh = fopen('php://output', 'w');
    fputcsv($fh, ['ID','Advertiser','Offer','Click ID','Conversion ID','Payout','Status','Reason','IP','Date']);
    foreach ($rows as $r) {
        fputcsv($fh, [
            $r['id'], $r['adv_name'] ?? ('ID:'.$r['advertiser_id']),
            $r['offer_name'] ?? '—', $r['click_id'],
            $r['conversion_id'] ?? '—', number_format((float)$r['payout'], 4),
            $r['status'], $r['reject_reason'] ?? '', $r['request_ip'] ?? '', $r['created_at'],
        ]);
    }
    fclose($fh);
    exit;
}

// ── Fetch logs ────────────────────────────────────────────────────────────
try {
    $logs = Database::fetchAll(
        "SELECT pl.*,
                o.name as offer_name,
                CONCAT(u.first_name,' ',u.last_name) as adv_name
         FROM advertiser_postback_logs pl
         LEFT JOIN offers o ON o.id = pl.offer_id
         LEFT JOIN advertisers a ON a.id = pl.advertiser_id
         LEFT JOIN users u ON u.id = a.user_id
         WHERE $whereSQL ORDER BY pl.created_at DESC LIMIT 2000",
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
         WHERE pl.created_at BETWEEN ? AND ?",
        [$dateFrom, $dateTo]
    );
} catch (\Throwable $e) { $stats = []; }

// ── Advertiser list for filter ────────────────────────────────────────────
try {
    $advertiserList = Database::fetchAll(
        "SELECT a.id, CONCAT(u.first_name,' ',u.last_name) as name
         FROM advertisers a JOIN users u ON u.id = a.user_id
         WHERE u.status='active' ORDER BY u.first_name"
    );
} catch (\Throwable $e) { $advertiserList = []; }

// ── Offer list for filter ─────────────────────────────────────────────────
try {
    $offerList = Database::fetchAll("SELECT id, name FROM offers WHERE status IN ('active','paused') ORDER BY name");
} catch (\Throwable $e) { $offerList = []; }

require BASE_PATH . '/views/admin/advertiser_postback_logs/index.php';
