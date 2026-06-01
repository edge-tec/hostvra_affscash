<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

// Ensure fraud_report_logs table exists
try {
    Database::query("CREATE TABLE IF NOT EXISTS `fraud_report_logs` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id`  INT UNSIGNED NOT NULL,
        `report_type`   ENUM('click','conversion') NOT NULL,
        `period_from`   DATETIME NOT NULL,
        `period_to`     DATETIME NOT NULL,
        `data_json`     JSON NOT NULL,
        `email_sent`    TINYINT(1) NOT NULL DEFAULT 0,
        `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_aff`     (`affiliate_id`),
        INDEX `idx_type`    (`report_type`),
        INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {}

// ── Filters ───────────────────────────────────────────────────────────────────
$affId      = (int)($_GET['affiliate_id'] ?? 0);
$typeFilter = $_GET['report_type'] ?? '';  // click | conversion | ''
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 30;

$dr      = fraud_date_range(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
$_df     = $dr['date_from'];
$_dt     = $dr['date_to'];

$affiliateList = Database::fetchAll(
    "SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
     FROM affiliates a LEFT JOIN users u ON u.id=a.user_id
     ORDER BY a.affiliate_code"
) ?: [];

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = "l.created_at BETWEEN ? AND ?";
$params = [$_df, $_dt];
if ($affId > 0)         { $where .= " AND l.affiliate_id=?"; $params[] = $affId; }
if ($typeFilter !== '')  { $where .= " AND l.report_type=?";  $params[] = $typeFilter; }

// ── Counts ────────────────────────────────────────────────────────────────────
$total = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM fraud_report_logs l WHERE {$where}", $params
)['c'] ?? 0);
$pag = fraud_paginate($total, $perPage, $page);

// ── Fetch rows ────────────────────────────────────────────────────────────────
$logs = Database::fetchAll(
    "SELECT l.id, l.affiliate_id, l.report_type, l.period_from, l.period_to,
            l.data_json, l.email_sent, l.created_at,
            a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS affiliate_name
     FROM fraud_report_logs l
     LEFT JOIN affiliates a ON a.id = l.affiliate_id
     LEFT JOIN users u ON u.id = a.user_id
     WHERE {$where}
     ORDER BY l.created_at DESC
     LIMIT {$perPage} OFFSET {$pag['offset']}",
    $params
) ?: [];

// ── Summary KPIs ──────────────────────────────────────────────────────────────
$kpis = [
    'total_reports'  => $total,
    'click_reports'  => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_report_logs l WHERE {$where} AND l.report_type='click'", $params)['c'] ?? 0),
    'conv_reports'   => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_report_logs l WHERE {$where} AND l.report_type='conversion'", $params)['c'] ?? 0),
    'emails_sent'    => (int)(Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_report_logs l WHERE {$where} AND l.email_sent=1", $params)['c'] ?? 0),
];

require BASE_PATH . '/views/admin/fraud_center/report_logs.php';
