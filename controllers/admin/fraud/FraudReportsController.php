<?php
Auth::checkAny(['admin', 'affiliate_manager']);
if (Auth::role() === 'affiliate_manager') {
    ManagerPermissions::requirePermission('view_fraud_reports');
}
$isManager = Auth::role() === 'affiliate_manager';
$managerAffIds = $isManager ? Auth::managerAffiliateIds() : [];

require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$message = '';
$error   = '';

// ── Bulk / single conversion action ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isManager) ManagerPermissions::requirePermission('reject_fraud_conv');
    Auth::verifyCsrf(Helpers::postRaw('_token'));

    $action     = $_POST['action'] ?? '';
    $convId     = trim($_POST['conversion_id'] ?? '');
    $bulkIds    = $_POST['bulk_ids'] ?? [];   // array of conversion_id strings
    $newStatus  = $action === 'approve' ? 'approved' : ($action === 'block' ? 'rejected' : '');

    if ($newStatus !== '' && ($convId !== '' || !empty($bulkIds))) {
        $ids = $convId !== '' ? [$convId] : $bulkIds;
        $ids = array_filter(array_map('trim', $ids));
        $rejectReason = trim((string)($_POST['rejection_reason'] ?? ''));
        $adminId      = (int)(Auth::id() ?? 0);
        $done = 0;
        foreach ($ids as $cid) {
            try {
                $conv = Database::fetchOne(
                    "SELECT cv.*, ck.source FROM conversions cv
                     LEFT JOIN clicks ck ON ck.click_id = cv.click_id
                     WHERE cv.conversion_id = ?", [$cid]
                );
                if (!$conv) continue;
                if ($isManager && !in_array($conv['affiliate_id'], $managerAffIds)) continue;
                $oldStatus = $conv['status'];

                $payload = RejectionHelper::buildUpdatePayload($newStatus, $rejectReason, $adminId);
                Database::update('conversions', $payload, 'conversion_id=?', [$cid]);

                // Notify affiliate per row when transitioning into rejected-style.
                // Works for both single Block and bulk Block-Selected actions.
                if ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
                    try { RejectionNotifier::afterReject((string)$cid); } catch (\Throwable $_rn) {}
                }

                // Adjust affiliate balance
                if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                    Database::query("UPDATE affiliates SET balance = balance + ? WHERE id = ?",
                        [$conv['payout'], $conv['affiliate_id']]);
                } elseif ($oldStatus === 'approved' && $newStatus !== 'approved') {
                    Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?",
                        [$conv['payout'], $conv['affiliate_id']]);
                }
                $done++;
            } catch (\Exception $e) {}
        }
        $message = $done . ' conversion(s) marked as ' . $newStatus . '.';
    }
}

// ── Filters ──────────────────────────────────────────────────────────────────
$tab         = $_GET['tab']       ?? 'conversions';   // conversions | clicks
$affId       = (int)($_GET['aff_id']    ?? 0);
$offerId     = (int)($_GET['offer_id']  ?? 0);
$statusFilter = $_GET['status']   ?? '';              // all | pending | approved | rejected
$fraudOnly   = (int)($_GET['fraud_only'] ?? 0);       // 1 = fraud flagged only
// Accept the standard `from`/`to` params (used by the shared date-range picker)
// while keeping the legacy `date_from`/`date_to` aliases working for old links.
$dr = fraud_date_range(
    $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
    $_GET['date_to']   ?? date('Y-m-d')
);
$dateFrom = $dr['from'];
$dateTo   = $dr['to'];
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 50;
$search      = trim($_GET['q'] ?? '');

// ── Dropdown data ─────────────────────────────────────────────────────────────
if ($isManager) {
    if (empty($managerAffIds)) {
        $affiliateList = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($managerAffIds), '?'));
        $affiliateList = Database::fetchAll(
            "SELECT a.id, a.affiliate_code, u.first_name, u.last_name
             FROM affiliates a LEFT JOIN users u ON u.id=a.user_id
             WHERE a.id IN ($placeholders)
             ORDER BY a.affiliate_code ASC", $managerAffIds
        ) ?: [];
    }
} else {
    $affiliateList = Database::fetchAll(
        "SELECT a.id, a.affiliate_code, u.first_name, u.last_name
         FROM affiliates a LEFT JOIN users u ON u.id=a.user_id
         ORDER BY a.affiliate_code ASC"
    ) ?: [];
}

$offerList = Database::fetchAll("SELECT id, name FROM offers ORDER BY name ASC") ?: [];

// ── Conversion report ─────────────────────────────────────────────────────────
$conversions = [];
$convPag     = ['total'=>0,'pages'=>1,'page'=>1,'offset'=>0,'per_page'=>$perPage];
$convSummary = [];

if ($tab === 'conversions') {
    $where  = "cv.converted_at BETWEEN ? AND ?";
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

    if ($isManager) {
        if (empty($managerAffIds)) { $where .= " AND 1=0"; }
        else {
            $placeholders = implode(',', array_fill(0, count($managerAffIds), '?'));
            $where .= " AND cv.affiliate_id IN ($placeholders)";
            $params = array_merge($params, $managerAffIds);
        }
    }

    if ($affId)           { $where .= " AND cv.affiliate_id=?";          $params[] = $affId; }
    if ($offerId)         { $where .= " AND cv.offer_id=?";              $params[] = $offerId; }
    if ($statusFilter !== '' && $statusFilter !== 'all') {
                            $where .= " AND cv.status=?";                $params[] = $statusFilter; }
    if ($fraudOnly)       { $where .= " AND (cv.is_fraud=1 OR cv.fraud_score>=50)"; }
    if ($search !== '')   { $where .= " AND (cv.conversion_id LIKE ? OR cv.ip_address LIKE ? OR a.affiliate_code LIKE ?)";
                            $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }

    $totalRow = Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions cv
         LEFT JOIN affiliates a ON a.id=cv.affiliate_id
         WHERE $where", $params
    );
    $convTotal = $totalRow['c'] ?? 0;
    $convPag   = fraud_paginate($convTotal, $perPage, $page);

    $conversions = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.ip_address, cv.payout, cv.revenue,
                cv.status, cv.is_fraud, cv.fraud_score, cv.goal_name, cv.transaction_id,
                cv.converted_at, cv.approved_at,
                o.name AS offer_name, o.id AS offer_id,
                a.affiliate_code, a.id AS affiliate_id,
                u.first_name, u.last_name,
                TIMESTAMPDIFF(SECOND,
                    (SELECT ck.clicked_at FROM clicks ck WHERE ck.click_id=cv.click_id LIMIT 1),
                    cv.converted_at) AS click_to_conv_secs
         FROM conversions cv
         LEFT JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN affiliates a ON a.id = cv.affiliate_id
         LEFT JOIN users u ON u.id = a.user_id
         WHERE $where
         ORDER BY cv.converted_at DESC
         LIMIT $perPage OFFSET {$convPag['offset']}",
        $params
    ) ?: [];

    // Summary bar
    $convSummary = Database::fetchOne(
        "SELECT COUNT(*) AS total,
                SUM(cv.payout) AS total_payout,
                SUM(cv.revenue) AS total_revenue,
                SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN cv.status='pending'  THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN cv.status='rejected' THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN cv.is_fraud=1 OR cv.fraud_score>=50 THEN 1 ELSE 0 END) AS flagged
         FROM conversions cv
         LEFT JOIN affiliates a ON a.id=cv.affiliate_id
         WHERE $where", $params
    ) ?: [];
}

// ── Click report ──────────────────────────────────────────────────────────────
$clicks   = [];
$clickPag = ['total'=>0,'pages'=>1,'page'=>1,'offset'=>0,'per_page'=>$perPage];
$clickSummary = [];

if ($tab === 'clicks') {
    $where  = "c.clicked_at BETWEEN ? AND ?";
    $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

    if ($isManager) {
        if (empty($managerAffIds)) { $where .= " AND 1=0"; }
        else {
            $placeholders = implode(',', array_fill(0, count($managerAffIds), '?'));
            $where .= " AND c.affiliate_id IN ($placeholders)";
            $params = array_merge($params, $managerAffIds);
        }
    }

    if ($affId)   { $where .= " AND c.affiliate_id=?"; $params[] = $affId; }
    if ($offerId) { $where .= " AND c.offer_id=?";     $params[] = $offerId; }
    if ($search !== '') {
        $where .= " AND (c.ip_address LIKE ? OR a.affiliate_code LIKE ?)";
        $s = "%$search%"; $params[] = $s; $params[] = $s;
    }

    $totalRow  = Database::fetchOne(
        "SELECT COUNT(*) AS c FROM clicks c LEFT JOIN affiliates a ON a.id=c.affiliate_id WHERE $where", $params
    );
    $clickTotal = $totalRow['c'] ?? 0;
    $clickPag   = fraud_paginate($clickTotal, $perPage, $page);

    $clicks = Database::fetchAll(
        "SELECT c.id, c.click_id, c.ip_address, c.user_agent, c.country, c.device_type,
                c.is_fraud, c.fraud_score, c.clicked_at, c.status,
                o.name AS offer_name, a.affiliate_code,
                u.first_name, u.last_name,
                (SELECT COUNT(*) FROM conversions cv WHERE cv.click_id=c.click_id) AS has_conv
         FROM clicks c
         LEFT JOIN offers o ON o.id = c.offer_id
         LEFT JOIN affiliates a ON a.id = c.affiliate_id
         LEFT JOIN users u ON u.id = a.user_id
         WHERE $where
         ORDER BY c.clicked_at DESC
         LIMIT $perPage OFFSET {$clickPag['offset']}",
        $params
    ) ?: [];

    $clickSummary = Database::fetchOne(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN c.is_fraud=1 OR c.fraud_score>=50 THEN 1 ELSE 0 END) AS flagged,
                COUNT(DISTINCT c.ip_address) AS unique_ips,
                COUNT(DISTINCT c.affiliate_id) AS unique_affs
         FROM clicks c LEFT JOIN affiliates a ON a.id=c.affiliate_id WHERE $where", $params
    ) ?: [];
}

// CSV / XLS export
$_frExportFmt = $_GET['export'] ?? '';
if ($_frExportFmt === 'csv' || $_frExportFmt === 'xls') {
    if ($tab === 'conversions') {
        $headerRow = ['Conversion ID','Click ID','IP','Offer','Affiliate','Payout','Revenue','Status','Fraud Score','Goal','Converted At'];
    } else {
        $headerRow = ['Click ID','IP','User Agent','Country','Device','Offer','Affiliate','Fraud Score','Clicked At','Status'];
    }
    if ($_frExportFmt === 'xls') {
        ExportHelper::beginXls('fraud-report-'.$tab);
        ExportHelper::xlsHeaderRow($headerRow);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        $fn = 'fraud-report-'.$tab.'-'.date('Y-m-d').'.csv';
        header('Content-Disposition: attachment; filename="'.$fn.'"');
        $f = fopen('php://output', 'w');
        fputcsv($f, $headerRow);
    }
    if ($tab === 'conversions') {
        foreach ($conversions as $r) {
            $rowCells = [$r['conversion_id'],$r['click_id'],$r['ip_address'],$r['offer_name'],$r['affiliate_code'],number_format($r['payout'],4),number_format($r['revenue'],4),$r['status'],$r['fraud_score']??'',$r['goal_name']??'',$r['converted_at']];
            if ($_frExportFmt === 'xls') ExportHelper::xlsRow($rowCells); else fputcsv($f, $rowCells);
        }
    } else {
        foreach ($clicks as $r) {
            $rowCells = [$r['click_id'],$r['ip_address'],$r['user_agent'],$r['country'],$r['device_type'],$r['offer_name'],$r['affiliate_code'],$r['fraud_score']??'',$r['clicked_at'],$r['status']];
            if ($_frExportFmt === 'xls') ExportHelper::xlsRow($rowCells); else fputcsv($f, $rowCells);
        }
    }
    if ($_frExportFmt === 'xls') ExportHelper::endXls();
    else                         fclose($f);
    exit;
}

require BASE_PATH . '/views/admin/fraud_center/fraud_reports.php';
